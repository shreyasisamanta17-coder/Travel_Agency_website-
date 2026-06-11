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
$editPkg = null;

// 1. Process Actions (Delete)
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    try {
        $delStmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
        $delStmt->execute([$delId]);
        $success = "Package deleted successfully.";
    } catch (\PDOException $e) {
        $error = "Failed to delete package: " . $e->getMessage();
    }
}

// 2. Process Actions (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($token)) {
        $error = "CSRF Token validation failed.";
    } else {
        $pkgId = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        $title = trim($_POST['title'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $duration = trim($_POST['duration'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $rating = floatval($_POST['rating'] ?? 5.0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        if (empty($title) || empty($location) || empty($description) || $price <= 0 || empty($duration) || empty($image_url)) {
            $error = "All fields except featured are required, and price must be greater than zero.";
        } else {
            try {
                if ($pkgId > 0) {
                    // Update Package
                    $upStmt = $pdo->prepare("
                        UPDATE packages 
                        SET title = ?, location = ?, description = ?, price = ?, duration = ?, image_url = ?, rating = ?, featured = ? 
                        WHERE id = ?
                    ");
                    $upStmt->execute([$title, $location, $description, $price, $duration, $image_url, $rating, $featured, $pkgId]);
                    $success = "Package updated successfully.";
                } else {
                    // Create Package
                    $insStmt = $pdo->prepare("
                        INSERT INTO packages (title, location, description, price, duration, image_url, rating, featured) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insStmt->execute([$title, $location, $description, $price, $duration, $image_url, $rating, $featured]);
                    $success = "Package created successfully.";
                }
            } catch (\PDOException $e) {
                $error = "Database operation failed: " . $e->getMessage();
            }
        }
    }
}

// 3. Check for Edit loading request
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    try {
        $editStmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
        $editStmt->execute([$editId]);
        $editPkg = $editStmt->fetch();
    } catch (\PDOException $e) {
        $error = "Failed to load package for editing.";
    }
}

// 4. Fetch All Packages
try {
    $packagesStmt = $pdo->query("SELECT * FROM packages ORDER BY id DESC");
    $packagesList = $packagesStmt->fetchAll();
} catch (\PDOException $e) {
    $packagesList = [];
}

$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Packages - TravelGo</title>
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
                    <a href="manage-packages.php" class="active"><i class="fa-solid fa-earth-americas"></i> Manage Packages</a>
                    <a href="manage-bookings.php"><i class="fa-solid fa-suitcase"></i> Manage Bookings</a>
                    <a href="manage-users.php"><i class="fa-solid fa-users"></i> Manage Users</a>
                    <a href="../logout.php" style="color: var(--danger); border-top: 1px solid var(--gray-100); margin-top: 10px; padding-top: 20px;"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
                </nav>
            </aside>

            <!-- Main screen -->
            <main>
                <div class="dashboard-panel" style="background-color: transparent; border: none; padding: 0; box-shadow: none;">
                    
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

                    <!-- Form Box: Add or Update Packages -->
                    <div class="admin-card-form">
                        <h3><?php echo $editPkg ? "Edit Package Details" : "Create New Travel Package"; ?></h3>
                        
                        <form action="manage-packages.php" method="POST" class="auth-form" style="display: block;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <?php if ($editPkg): ?>
                                <input type="hidden" name="package_id" value="<?php echo $editPkg['id']; ?>">
                            <?php endif; ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="title">Package Title</label>
                                    <input type="text" id="title" name="title" placeholder="Grand Tokyo Tour" required value="<?php echo htmlspecialchars($editPkg['title'] ?? ''); ?>">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="location">Location</label>
                                    <input type="text" id="location" name="location" placeholder="Tokyo, Japan" required value="<?php echo htmlspecialchars($editPkg['location'] ?? ''); ?>">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="price">Price ($)</label>
                                    <input type="number" id="price" name="price" placeholder="950.00" step="0.01" required value="<?php echo htmlspecialchars($editPkg['price'] ?? ''); ?>">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="duration">Duration</label>
                                    <input type="text" id="duration" name="duration" placeholder="6 Days, 5 Nights" required value="<?php echo htmlspecialchars($editPkg['duration'] ?? ''); ?>">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="rating">Rating (0.0 - 5.0)</label>
                                    <input type="number" id="rating" name="rating" placeholder="4.8" step="0.1" min="0" max="5" value="<?php echo htmlspecialchars($editPkg['rating'] ?? '5.0'); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="image_url">Image URL</label>
                                <input type="url" id="image_url" name="image_url" placeholder="https://images.unsplash.com/photo-..." required value="<?php echo htmlspecialchars($editPkg['image_url'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="description">Detailed Description</label>
                                <textarea id="description" name="description" placeholder="Write comprehensive tour activities..." required style="width: 100%; min-height: 120px; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--gray-200); background-color: var(--gray-100); font-family: inherit; font-size: inherit; resize: vertical;"><?php echo htmlspecialchars($editPkg['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                                <input type="checkbox" id="featured" name="featured" style="width: 18px; height: 18px; cursor: pointer;" <?php echo (isset($editPkg['featured']) && $editPkg['featured']) ? 'checked' : ''; ?>>
                                <label for="featured" style="margin-bottom: 0; cursor: pointer; font-weight: 600;">Display as Featured package on homepage</label>
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: 24px;">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $editPkg ? "Update Package" : "Create Package"; ?> <i class="fa-solid fa-cloud-arrow-up"></i>
                                </button>
                                <?php if ($editPkg): ?>
                                    <a href="manage-packages.php" class="btn btn-outline">Cancel Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- List of Packages Box -->
                    <div class="dashboard-panel">
                        <h2>Existing Travel Packages</h2>
                        
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Details</th>
                                        <th>Duration</th>
                                        <th>Price</th>
                                        <th>Rating</th>
                                        <th>Featured</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($packagesList)): ?>
                                        <?php foreach ($packagesList as $pkg): ?>
                                            <tr>
                                                <td>#<?php echo $pkg['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($pkg['title']); ?></strong><br>
                                                    <span class="text-muted" style="font-size: 0.8rem;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($pkg['location']); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($pkg['duration']); ?></td>
                                                <td><strong>$<?php echo number_format($pkg['price'], 2); ?></strong></td>
                                                <td><i class="fa-solid fa-star text-primary" style="font-size: 0.8rem;"></i> <?php echo number_format($pkg['rating'], 1); ?></td>
                                                <td>
                                                    <?php if ($pkg['featured']): ?>
                                                        <span class="status-indicator status-approved" style="font-size: 0.7rem;">Featured</span>
                                                    <?php else: ?>
                                                        <span class="status-indicator status-pending" style="font-size: 0.7rem; background-color: var(--gray-200); color: var(--gray-500);">Standard</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="admin-actions">
                                                        <a href="manage-packages.php?edit=<?php echo $pkg['id']; ?>" class="btn-icon btn-icon-edit" title="Edit Package"><i class="fa-solid fa-pencil"></i></a>
                                                        <a href="manage-packages.php?delete=<?php echo $pkg['id']; ?>" class="btn-icon btn-icon-delete" title="Delete Package" onclick="return confirm('Are you sure you want to permanently delete this package? All associated wishlist and booking history entries will be cascades deleted.');"><i class="fa-solid fa-trash-can"></i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center" style="padding: 30px 0;">No travel packages exist in the database catalog. Use the form above to add some.</td>
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
