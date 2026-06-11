<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// 1. Handle Asynchronous AJAX Toggle Requests
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Please sign in to save packages.']);
        exit();
    }
    
    $userId = getLoggedInUserId();
    $packageId = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($packageId <= 0 || !in_array($action, ['add', 'remove'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters provided.']);
        exit();
    }
    
    try {
        if ($action === 'add') {
            // Save to wishlist
            $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, package_id) VALUES (?, ?)");
            $stmt->execute([$userId, $packageId]);
            echo json_encode(['success' => true, 'message' => 'Package saved to wishlist.']);
        } else {
            // Remove from wishlist
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND package_id = ?");
            $stmt->execute([$userId, $packageId]);
            echo json_encode(['success' => true, 'message' => 'Package removed from wishlist.']);
        }
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// 2. Handle Standard GET User View Pages
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$page_title = "My Saved Wishlist";
require_once 'includes/header.php';

$userId = getLoggedInUserId();
$wishlistPackages = [];

try {
    // Select all details of packages stored in user's wishlist
    $stmt = $pdo->prepare("
        SELECT p.* FROM packages p 
        JOIN wishlist w ON p.id = w.package_id 
        WHERE w.user_id = ? 
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    $wishlistPackages = $stmt->fetchAll();
} catch (\PDOException $e) {
    $error = "Error loading saved items: " . $e->getMessage();
}
?>

<div class="container section">
    <div class="section-header" style="text-align: left; max-width: 100%; margin-bottom: 20px; border-bottom: 1px solid var(--gray-200); padding-bottom: 20px;">
        <h2>My Saved Wishlist</h2>
        <p>Manage the dynamic travel deals you saved for future bookings. Click the heart icon to remove a saved package.</p>
    </div>

    <!-- Wishlist Listings Grid -->
    <main style="margin-top: 30px;">
        <?php if (!empty($wishlistPackages)): ?>
            <div class="grid grid-3">
                <?php foreach ($wishlistPackages as $pkg): ?>
                    <div class="package-card" data-price="<?php echo $pkg['price']; ?>" data-location="<?php echo htmlspecialchars($pkg['location']); ?>">
                        <div class="package-img">
                            <img src="<?php echo htmlspecialchars($pkg['image_url']); ?>" alt="<?php echo htmlspecialchars($pkg['title']); ?>">
                            <?php if ($pkg['featured']): ?>
                                <span class="featured-badge">Featured</span>
                            <?php endif; ?>
                            <!-- Wishlist button is always active inside wishlist.php view -->
                            <button type="button" class="wishlist-btn active" data-id="<?php echo $pkg['id']; ?>" title="Remove from Wishlist">
                                <i class="fa-solid fa-heart"></i>
                            </button>
                        </div>
                        
                        <div class="package-body">
                            <div class="package-meta">
                                <span><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($pkg['duration']); ?></span>
                                <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($pkg['location']); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($pkg['title']); ?></h3>
                            <p class="desc"><?php echo htmlspecialchars($pkg['description']); ?></p>
                            
                            <div class="package-footer">
                                <div class="rating-badge">
                                    <i class="fa-solid fa-star"></i> <?php echo number_format($pkg['rating'], 1); ?>
                                </div>
                                <div class="package-price">
                                    $<?php echo number_format($pkg['price'], 2); ?>
                                    <span>/ person</span>
                                </div>
                            </div>
                            
                            <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline btn-block mb-1" style="margin-top: 20px;">Book Now</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 80px 20px; background-color: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--gray-200); max-width: 600px; margin: 0 auto;">
                <i class="fa-solid fa-heart-crack text-muted" style="font-size: 4rem; margin-bottom: 24px; opacity: 0.8;"></i>
                <h3>Your Wishlist is Empty</h3>
                <p class="text-muted" style="margin-top: 8px; margin-bottom: 30px;">Explore our catalog of custom travel itineraries and add your favorites to start planning.</p>
                <a href="packages.php" class="btn btn-primary">
                    Find Travel Deals <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once 'includes/footer.php'; ?>
