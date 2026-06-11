<?php
require_once 'includes/header.php';
require_once 'includes/db.php';

// Validate package ID
$package_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($package_id <= 0) {
    header("Location: packages.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$package_id]);
    $package = $stmt->fetch();
    
    if (!$package) {
        header("Location: packages.php");
        exit();
    }
} catch (\PDOException $e) {
    die("Error loading package details: " . $e->getMessage());
}
?>

<!-- 1. Beautiful Hero Photo Banner -->
<div class="details-hero" style="background-image: url('<?php echo htmlspecialchars($package['image_url']); ?>');">
    <div class="details-hero-overlay">
        <div class="container">
            <div class="details-header">
                <div class="details-badges">
                    <span><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($package['duration']); ?></span>
                    <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($package['location']); ?></span>
                    <span><i class="fa-solid fa-star" style="color: var(--warning);"></i> <?php echo number_format($package['rating'], 1); ?> Rating</span>
                </div>
                <h1><?php echo htmlspecialchars($package['title']); ?></h1>
            </div>
        </div>
    </div>
</div>

<!-- 2. Main Page Grid -->
<div class="container section">
    <div class="details-grid">
        
        <!-- Info Column -->
        <article class="details-info">
            <section>
                <h2>Overview & Highlight Description</h2>
                <p><?php echo nl2br(htmlspecialchars($package['description'])); ?></p>
            </section>

            <section>
                <h2>Premium Services Included</h2>
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fa-solid fa-plane"></i>
                        <span>Round-Trip Flights Included</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-hotel"></i>
                        <span>Luxury 5-Star Resort Accommodations</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-utensils"></i>
                        <span>Premium Buffet Daily Breakfasts</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-camera"></i>
                        <span>Private Guided City Excursions</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>All Visa Processing Fees Covered</span>
                    </div>
                    <div class="feature-item">
                        <i class="fa-solid fa-shield-heart"></i>
                        <span>Full Global Travel Protection Coverage</span>
                    </div>
                </div>
            </section>
            
            <section>
                <h2>Detailed Day-by-Day Itinerary</h2>
                <div class="itinerary" style="margin-top: 20px;">
                    <div style="margin-bottom: 24px; padding-left: 20px; border-left: 3px solid var(--primary);">
                        <h4 style="margin-bottom: 6px; font-size: 1.05rem;">Day 1: Arrival & Premium Resort Welcome</h4>
                        <p class="text-muted" style="font-size: 0.9rem;">Touch down at the international airport. Receive your exclusive private luxury vehicle transfer directly to your premium resort. Enjoy a welcome dinner featuring signature gourmet dishes.</p>
                    </div>

                    <div style="margin-bottom: 24px; padding-left: 20px; border-left: 3px solid var(--primary);">
                        <h4 style="margin-bottom: 6px; font-size: 1.05rem;">Day 2: Guided Historical Temple & Landmark Sightseeing</h4>
                        <p class="text-muted" style="font-size: 0.9rem;">Embark on a private guided city tour. Visit iconic landmarks, learn about ancient cultures and traditions, and capture pristine panoramic viewpoints under the guidance of our local certified expert.</p>
                    </div>

                    <div style="padding-left: 20px; border-left: 3px solid var(--primary);">
                        <h4 style="margin-bottom: 6px; font-size: 1.05rem;">Day 3: Scenic Leisure and Leisure Coastal Sunset Cruise</h4>
                        <p class="text-muted" style="font-size: 0.9rem;">Spend your morning relaxing by the beach or checking out the local shopping boutiques. In the late afternoon, step aboard our private sunset yacht cruise complete with delicious dynamic dining.</p>
                    </div>
                </div>
            </section>
        </article>

        <!-- Booking Sidebar Card -->
        <aside>
            <div class="booking-sidebar">
                <div class="price-box">
                    <p>Base Tour Package Rate</p>
                    <h3 id="base-price" data-price="<?php echo $package['price']; ?>">$<?php echo number_format($package['price'], 2); ?></h3>
                    <p>/ individual traveler</p>
                </div>

                <form action="checkout.php" method="POST" class="booking-form">
                    <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                    <input type="hidden" name="total_price" id="total_price" value="<?php echo $package['price']; ?>">

                    <!-- Input: Travel Date -->
                    <div class="form-group">
                        <label for="travel_date"><i class="fa-solid fa-calendar-days text-primary"></i> Target Travel Date</label>
                        <input type="date" id="travel_date" name="travel_date" required min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                    </div>

                    <!-- Input: Guests -->
                    <div class="form-group">
                        <label for="guests"><i class="fa-solid fa-users text-primary"></i> Number of Guests</label>
                        <input type="number" id="guests" name="guests" value="1" min="1" max="10" required>
                    </div>

                    <!-- Visual Billing Breakdown Summary -->
                    <div class="booking-summary-box">
                        <div class="summary-row">
                            <span>Travel Base (x<span id="summary-guests-label">1</span>)</span>
                            <span id="summary-subtotal">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Travel GST & Tax (12%)</span>
                            <span id="summary-tax">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Total Billing Summary</span>
                            <span id="summary-total" style="font-weight: 800; color: var(--primary); font-size: 1.15rem;">$0.00</span>
                        </div>
                    </div>

                    <!-- Check Log-in before checkout -->
                    <?php if (isLoggedIn()): ?>
                        <button type="submit" class="btn btn-primary btn-block">
                            Proceed to Checkout <i class="fa-solid fa-credit-card"></i>
                        </button>
                    <?php else: ?>
                        <a href="login.php?redirect=package-details.php?id=<?php echo $package['id']; ?>" class="btn btn-primary btn-block">
                            Sign In to Reserve <i class="fa-solid fa-right-to-bracket"></i>
                        </a>
                        <p class="text-center" style="font-size: 0.75rem; color: var(--gray-500); margin-top: 10px;">Please login to finalize your reservation.</p>
                    <?php endif; ?>
                </form>
            </div>
        </aside>
    </div>
</div>

<script>
    // Live update guests labels in real-time
    document.addEventListener('DOMContentLoaded', function() {
        const guestInput = document.getElementById('guests');
        const summaryGuestsLabel = document.getElementById('summary-guests-label');
        
        if (guestInput && summaryGuestsLabel) {
            guestInput.addEventListener('input', function() {
                summaryGuestsLabel.textContent = this.value || 1;
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
