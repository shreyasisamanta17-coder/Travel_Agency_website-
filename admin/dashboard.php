<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Route safety: force admin access
if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

try {
    // 1. Fetch Key Metrics
    $pkgCount = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
    $userCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $bookingCount = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    $revenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'approved'")->fetchColumn() ?: 0.00;
    
    // 2. Fetch Recent Reservations
    $recentStmt = $pdo->query("
        SELECT b.*, u.name as user_name, u.email as user_email, p.title as package_title 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN packages p ON b.package_id = p.id
        ORDER BY b.id DESC LIMIT 5
    ");
    $recentBookings = $recentStmt->fetchAll();
} catch (\PDOException $e) {
    die("Metrics calculation failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TravelGo</title>
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
                    <a href="dashboard.php" class="active"><i class="fa-solid fa-gauge"></i> Home Dashboard</a>
                    <a href="manage-packages.php"><i class="fa-solid fa-earth-americas"></i> Manage Packages</a>
                    <a href="manage-bookings.php"><i class="fa-solid fa-suitcase"></i> Manage Bookings</a>
                    <a href="manage-users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
                    <a href="../logout.php" style="color: var(--danger); border-top: 1px solid var(--gray-100); margin-top: 10px; padding-top: 20px;"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
                </nav>
            </aside>

            <!-- Main metrics screen -->
            <main>
                <div class="dashboard-panel" style="background-color: transparent; border: none; padding: 0; box-shadow: none;">
                    <h2 style="margin-bottom: 30px;">Overview Statistics</h2>
                    
                    <!-- Metrics Cards Grid -->
                    <div class="admin-stats-grid">
                        <div class="admin-stat-card">
                            <div class="admin-stat-icon stat-icon-blue">
                                <i class="fa-solid fa-earth-americas"></i>
                            </div>
                            <div class="admin-stat-info">
                                <h3><?php echo $pkgCount; ?></h3>
                                <p>Active Packages</p>
                            </div>
                        </div>

                        <div class="admin-stat-card">
                            <div class="admin-stat-icon stat-icon-green">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                            <div class="admin-stat-info">
                                <h3>$<?php echo number_format($revenue, 2); ?></h3>
                                <p>Total Revenue</p>
                            </div>
                        </div>

                        <div class="admin-stat-card">
                            <div class="admin-stat-icon stat-icon-yellow">
                                <i class="fa-solid fa-suitcase"></i>
                            </div>
                            <div class="admin-stat-info">
                                <h3><?php echo $bookingCount; ?></h3>
                                <p>Total Bookings</p>
                            </div>
                        </div>

                        <div class="admin-stat-card">
                            <div class="admin-stat-icon stat-icon-red">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <div class="admin-stat-info">
                                <h3><?php echo $userCount; ?></h3>
                                <p>Registered Users</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Reservations Box -->
                    <div class="dashboard-panel">
                        <h2 style="font-size: 1.25rem; margin-bottom: 20px; border-bottom: 1px solid var(--gray-200); padding-bottom: 12px;">Recent Client Reservations</h2>
                        
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client Name</th>
                                        <th>Tour Package</th>
                                        <th>Travel Date</th>
                                        <th>Bill Paid</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recentBookings)): ?>
                                        <?php foreach ($recentBookings as $bk): ?>
                                            <tr>
                                                <td>#<?php echo $bk['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($bk['user_name']); ?></strong><br>
                                                    <span class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($bk['user_email']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($bk['package_title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($bk['travel_date'])); ?></td>
                                                <td><strong>$<?php echo number_format($bk['total_price'], 2); ?></strong></td>
                                                <td>
                                                    <span class="status-indicator status-<?php echo $bk['status']; ?>">
                                                        <?php echo htmlspecialchars($bk['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="manage-bookings.php" class="btn btn-outline btn-sm" style="padding: 6px 12px; font-size: 0.8rem;">Review</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center" style="padding: 30px 0;">No reservations have been recorded yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
