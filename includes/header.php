<?php
// Global Header Template
require_once __DIR__ . '/auth.php';

// Fetch Wishlist count for current user if logged in
$wishlistCount = 0;
if (isLoggedIn()) {
    $userId = getLoggedInUserId();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wishlistCount = $stmt->fetchColumn();
    } catch (\PDOException $e) {
        // Suppress or log in production
    }
}

// Get current filename for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TravelGo - Plan your dream vacation with our premium tour packages. Secure bookings, gorgeous destinations, and top-tier guides.">
    <title><?php echo isset($page_title) ? $page_title . " - TravelGo" : "TravelGo - Premium Travel Agency"; ?></title>
    
    <!-- Google Fonts & Icon Packs -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-user-id="<?php echo isLoggedIn() ? getLoggedInUserId() : ''; ?>">

    <!-- Global Sticky Header -->
    <header>
        <div class="container d-flex align-center justify-between nav-container">
            <!-- Brand Logo -->
            <a href="index.php" class="logo">
                <i class="fa-solid fa-paper-plane text-primary"></i> Travel<span>Go</span>
            </a>

            <!-- Navigation Links -->
            <nav class="nav-links">
                <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="packages.php" class="<?php echo $current_page == 'packages.php' ? 'active' : ''; ?>">Packages</a>
                <?php if (isLoggedIn()): ?>
                    <a href="wishlist.php" class="<?php echo $current_page == 'wishlist.php' ? 'active' : ''; ?>">
                        Wishlist 
                        <?php if ($wishlistCount > 0): ?>
                            <span class="badge" style="background-color: var(--danger); color: var(--white); font-size: 0.7rem; font-weight: 800; padding: 2px 6px; border-radius: 99px; margin-left: 2px; vertical-align: top;"><?php echo $wishlistCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="booking-history.php" class="<?php echo $current_page == 'booking-history.php' ? 'active' : ''; ?>">My Bookings</a>
                <?php endif; ?>
            </nav>

            <!-- Navigation Actions / Profile Dropdown -->
            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                    <div class="user-menu-container">
                        <button type="button" class="user-profile-btn">
                            <i class="fa-solid fa-circle-user"></i>
                            <span class="user-name-label" style="font-weight:600; font-size:0.9rem;"><?php echo htmlspecialchars(getLoggedInUserName()); ?></span>
                            <i class="fa-solid fa-chevron-down" style="font-size:0.75rem; opacity:0.7;"></i>
                        </button>
                        
                        <!-- Premium Dropdown Menu -->
                        <div class="user-dropdown">
                            <div class="user-dropdown-header">
                                <h4><?php echo htmlspecialchars(getLoggedInUserName()); ?></h4>
                                <p><?php echo htmlspecialchars(getLoggedInUserEmail()); ?></p>
                            </div>
                            
                            <?php if (isAdmin()): ?>
                                <a href="admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Admin Dashboard</a>
                            <?php endif; ?>
                            
                            <a href="booking-history.php"><i class="fa-solid fa-suitcase"></i> My Bookings</a>
                            <a href="wishlist.php"><i class="fa-solid fa-heart"></i> Saved Packages</a>
                            <a href="logout.php" class="dropdown-logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline btn-sm">Sign In</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
