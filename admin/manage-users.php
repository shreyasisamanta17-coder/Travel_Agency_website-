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

// 1. Process User Deletion
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    
    // Prevent self-deletion
    if ($delId === getLoggedInUserId()) {
        $error = "Self-destruction blocked. You cannot delete your own admin account.";
    } else {
        try {
            // Check if user to delete is an admin
            $checkStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $checkStmt->execute([$delId]);
            $userRole = $checkStmt->fetchColumn();
            
            if ($userRole === 'admin') {
                $error = "Deletions restricted. Admin accounts can only be removed directly from the database server.";
            } else {
                $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $delStmt->execute([$delId]);
                $success = "User account successfully terminated.";
            }
        } catch (\PDOException $e) {
            $error = "Failed to terminate user: " . $e->getMessage();
        }
    }
}

// 2. Fetch All Registered Users
try {
    $usersStmt = $pdo->query("SELECT * FROM users ORDER BY role ASC, id DESC");
    $usersList = $usersStmt->fetchAll();
} catch (\PDOException $e) {
    $usersList = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - TravelGo</title>
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
                    <a href="manage-bookings.php"><i class="fa-solid fa-suitcase"></i> Manage Bookings</a>
                    <a href="manage-users.php" class="active"><i class="fa-solid fa-users"></i> Manage Users</a>
                    <a href="../logout.php" style="color: var(--danger); border-top: 1px solid var(--gray-100); margin-top: 10px; padding-top: 20px;"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
                </nav>
            </aside>

            <!-- Main screen -->
            <main>
                <div class="dashboard-panel">
                    <h2>Manage Registered Users</h2>
                    
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
                                    <th>Full Name</th>
                                    <th>Email Address</th>
                                    <th>Access Privilege</th>
                                    <th>Registered Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($usersList)): ?>
                                    <?php foreach ($usersList as $usr): ?>
                                        <tr>
                                            <td>#<?php echo $usr['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($usr['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($usr['email']); ?></td>
                                            <td>
                                                <?php if ($usr['role'] === 'admin'): ?>
                                                    <span class="status-indicator status-approved" style="font-size: 0.75rem;"><i class="fa-solid fa-user-shield"></i> Administrator</span>
                                                <?php else: ?>
                                                    <span class="status-indicator status-pending" style="font-size: 0.75rem; background-color: var(--gray-200); color: var(--gray-500);"><i class="fa-solid fa-user"></i> Standard Client</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($usr['created_at'])); ?></td>
                                            <td>
                                                <div class="admin-actions">
                                                    <?php if ($usr['role'] !== 'admin'): ?>
                                                        <a href="manage-users.php?delete=<?php echo $usr['id']; ?>" class="btn-icon btn-icon-delete" title="Delete User Account" onclick="return confirm('Are you sure you want to permanently delete this user account? All associated bookings, wishlists, and sessions will be destroyed.');"><i class="fa-solid fa-user-minus"></i></a>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 0.8rem; font-style: italic;">Protected</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center" style="padding: 40px 0;">No registered users found.</td>
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
