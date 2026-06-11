<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Route safety: force admin access
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

// 1. Process Status Modification requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($token)) {
        $error = "CSRF Token validation failed.";
    } else {
        $bookingId = intval($_POST['booking_id']);
        $newStatus = $_POST['status'];
        
        if (in_array($newStatus, ['pending', 'approved', 'cancelled'])) {
            try {
                $upStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                $upStmt->execute([$newStatus, $bookingId]);
                $success = "Booking #{$bookingId} status successfully updated to '{$newStatus}'.";
            } catch (\PDOException $e) {
                $error = "Failed to update booking status: " . $e->getMessage();
            }
        } else {
            $error = "Invalid booking status value provided.";
        }
    }
}

// 2. Fetch All Bookings
try {
    $bookingsStmt = $pdo->query("
        SELECT b.*, u.name as user_name, u.email as user_email, p.title as package_title, p.location as package_location 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN packages p ON b.package_id = p.id
        ORDER BY b.id DESC
    ");
    $bookingsList = $bookingsStmt->fetchAll();
} catch (\PDOException $e) {
    $bookingsList = [];
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - TravelGo</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Sticky Admin Header -->
    <header style="background-color: var(--dark); color: var(--white); border-bottom: 1px solid var(--gray-700);">
        <div class="container d-flex align-center justify-between nav-container" style="height: 70px;">
            <a href="../index.php" class="logo" style="color: var(--white);">
                <i class="fa-solid fa-paper-plane text-primary"></i> Travel<span>Go</span> <span style="font-size: 0.8rem; background-color: var(--primary); color: var(--white); padding: 2px 8px; border-radius: var(--radius-sm); margin-left: 8px;">ADMIN</span>
            </a>

            <div class="nav-actions">
                <span class="text-muted" style="font-size: 0.9rem; font-weight: 600;"><i class="fa-solid fa-user-shield text-primary"></i> Administrator Panel</span>
                <a href="../index.php" class="btn btn-outline btn-sm" style="color: var(--white); border-color: var(--gray-500); margin-left: 16px;">Main Website</a>
            </div>
        </div>
    </header>

    <div class="container section" style="padding-top: 40px;">
        <div class="dashboard-layout">
            
            <!-- Admin Navigation Console -->
            <aside class="dashboard-sidebar">
                <div class="dashboard-sidebar-profile">
                    <i class="fa-solid fa-user-tie text-primary" style="font-size: 3rem; margin-bottom: 12px;"></i>
                    <h3>Admin Console</h3>
                    <p><?php echo htmlspecialchars(getLoggedInUserEmail()); ?></p>
                </div>
                
                <nav class="dashboard-nav">
                    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Home Dashboard</a>
                    <a href="manage-packages.php"><i class="fa-solid fa-earth-americas"></i> Manage Packages</a>
                    <a href="manage-bookings.php" class="active"><i class="fa-solid fa-suitcase"></i> Manage Bookings</a>
                    <a href="manage-users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
                    <a href="../logout.php" style="color: var(--danger); border-top: 1px solid var(--gray-100); margin-top: 10px; padding-top: 20px;"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
                </nav>
            </aside>

            <!-- Main screen -->
            <main>
                <div class="dashboard-panel">
                    <h2>Manage Customer Bookings</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client Information</th>
                                    <th>Tour Destination</th>
                                    <th>Travel Date</th>
                                    <th>Guests</th>
                                    <th>Grand Total</th>
                                    <th>Current Status</th>
                                    <th>Quick Moderation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($bookingsList)): ?>
                                    <?php foreach ($bookingsList as $bk): ?>
                                        <tr>
                                            <td>#<?php echo $bk['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($bk['user_name']); ?></strong><br>
                                                <span class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($bk['user_email']); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($bk['package_title']); ?></strong><br>
                                                <span class="text-muted" style="font-size: 0.8rem;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($bk['package_location']); ?></span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($bk['travel_date'])); ?></td>
                                            <td><strong><?php echo $bk['guests']; ?></strong></td>
                                            <td><strong>$<?php echo number_format($bk['total_price'], 2); ?></strong></td>
                                            <td>
                                                <span class="status-indicator status-<?php echo $bk['status']; ?>">
                                                    <?php echo htmlspecialchars($bk['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <!-- Action Form Dropdown -->
                                                <form action="manage-bookings.php" method="POST" style="display: flex; gap: 6px; align-items: center;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                    <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                                    <input type="hidden" name="update_status" value="1">
                                                    
                                                    <select name="status" style="padding: 6px 10px; border-radius: var(--radius-sm); border: 1px solid var(--gray-300); font-weight: 600; font-size: 0.85rem; background-color: var(--gray-100);">
                                                        <option value="pending" <?php echo $bk['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="approved" <?php echo $bk['status'] === 'approved' ? 'selected' : ''; ?>>Approve</option>
                                                        <option value="cancelled" <?php echo $bk['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancel</option>
                                                    </select>
                                                    <button type="submit" class="btn-icon btn-icon-approve" title="Save Status" style="border: none;"><i class="fa-solid fa-floppy-disk"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center" style="padding: 40px 0;">No bookings have been made by clients yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Small footer copyright -->
    <footer style="background-color: var(--dark); color: var(--gray-500); padding: 30px 0; border-top: 1px solid var(--gray-700); margin-top: 80px;">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> TravelGo Admin Panel. All Rights Reserved.</p>
        </div>
    </footer>

</body>
</html>
