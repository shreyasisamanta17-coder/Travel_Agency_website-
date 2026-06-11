<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if (isset($_GET['success'])) {
    $success = "Thank you! Your trip reservation has been placed successfully. It is currently under review by our agents.";
}

// 1. Process User Booking Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($token)) {
        $error = "CSRF Token validation failed. Please try again.";
    } else {
        $bookingId = intval($_POST['booking_id']);
        
        try {
            // Verify booking belongs to the current logged-in user and is still pending
            $checkStmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$bookingId, getLoggedInUserId()]);
            $bookingStatus = $checkStmt->fetchColumn();
            
            if ($bookingStatus === 'pending') {
                $cancelStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                $cancelStmt->execute([$bookingId]);
                $success = "Your booking reservation has been successfully cancelled.";
            } else {
                $error = "You can only cancel bookings that are in 'Pending' status.";
            }
        } catch (\PDOException $e) {
            $error = "Cancellation failed: " . $e->getMessage();
        }
    }
}

// 2. Fetch User Bookings
$userId = getLoggedInUserId();
$bookingsList = [];

try {
    $stmt = $pdo->prepare("
        SELECT b.*, p.title, p.location, p.image_url, p.duration 
        FROM bookings b 
        JOIN packages p ON b.package_id = p.id 
        WHERE b.user_id = ? 
        ORDER BY b.id DESC
    ");
    $stmt->execute([$userId]);
    $bookingsList = $stmt->fetchAll();
} catch (\PDOException $e) {
    $error = "Error loading bookings list: " . $e->getMessage();
}

$csrf_token = generateCsrfToken();
$page_title = "My Booking Console";
require_once 'includes/header.php';
?>

<div class="container section">
    
    <!-- Dashboard Side Panel Navigation sub-layout -->
    <div class="dashboard-layout">
        
        <!-- Sidebar Navigation Console -->
        <aside class="dashboard-sidebar">
            <div class="dashboard-sidebar-profile">
                <i class="fa-solid fa-circle-user text-primary"></i>
                <h3><?php echo htmlspecialchars(getLoggedInUserName()); ?></h3>
                <p><?php echo htmlspecialchars(getLoggedInUserEmail()); ?></p>
            </div>
            
            <nav class="dashboard-nav">
                <a href="booking-history.php" class="active"><i class="fa-solid fa-suitcase"></i> My Bookings</a>
                <a href="wishlist.php"><i class="fa-solid fa-heart"></i> Saved Wishlist</a>
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php"><i class="fa-solid fa-gauge"></i> Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php" style="color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
            </nav>
        </aside>

        <!-- Main Panel Content -->
        <main class="dashboard-panel">
            <h2>My Booking Reservations</h2>
            
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

            <?php if (!empty($bookingsList)): ?>
                <?php foreach ($bookingsList as $booking): ?>
                    <div class="booking-history-item">
                        <div class="booking-history-img">
                            <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>">
                        </div>
                        
                        <div class="booking-history-details">
                            <div class="booking-history-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                                    <span class="text-muted" style="font-size: 0.85rem; font-weight: 500;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($booking['location']); ?></span>
                                </div>
                                
                                <!-- Status Badge Display -->
                                <span class="status-indicator status-<?php echo $booking['status']; ?>">
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </span>
                            </div>

                            <div class="booking-history-footer">
                                <div>
                                    <span>Travel Date: <strong><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></strong></span>
                                    <span style="margin-left: 20px;">Guests: <strong><?php echo $booking['guests']; ?></strong></span>
                                </div>
                                <div class="price">
                                    $<?php echo number_format($booking['total_price'], 2); ?>
                                </div>
                            </div>
                            
                            <!-- Action: Cancel pending bookings -->
                            <?php if ($booking['status'] === 'pending'): ?>
                                <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                                    <form action="booking-history.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this pending booking?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="cancel_booking" value="1">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger); padding: 6px 12px; font-size: 0.8rem;">
                                            Cancel Reservation <i class="fa-solid fa-rectangle-xmark"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="padding: 60px 20px;">
                    <i class="fa-solid fa-suitcase-rolling text-muted" style="font-size: 3.5rem; margin-bottom: 16px; opacity: 0.7;"></i>
                    <h3>No Active Reservations</h3>
                    <p class="text-muted" style="margin-top: 8px; margin-bottom: 24px;">You haven't reserved any vacation packages yet. What are you waiting for?</p>
                    <a href="packages.php" class="btn btn-primary btn-sm">Find Your Dream Trip</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
