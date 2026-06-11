<?php
$page_title = "Discover the World";
require_once 'includes/header.php';
require_once 'includes/db.php';

// Fetch featured packages
try {
    $featuredStmt = $pdo->prepare("SELECT * FROM packages WHERE featured = 1 LIMIT 3");
    $featuredStmt->execute();
    $featuredPackages = $featuredStmt->fetchAll();

    // Fetch user wishlist if logged in to display active heart icon
    $wishlistItems = [];
    if (isLoggedIn()) {
        $wishlistStmt = $pdo->prepare("SELECT package_id FROM wishlist WHERE user_id = ?");
        $wishlistStmt->execute([getLoggedInUserId()]);
        $wishlistItems = $wishlistStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (\PDOException $e) {
    $featuredPackages = [];
}
?>

<!-- 1. Hero Search Panel -->
<section class="hero">
    <div class="hero-content">
        <h1>Explore The World Together</h1>
        <p>Discover magnificent getaways, luxurious overwater villas, and rich cultural experiences designed for you.</p>
    </div>
</section>

<!-- Modern Search Overlay Bar -->
<div class="search-bar-container">
    <form action="packages.php" method="GET" class="search-bar">
        <div class="search-grid">
            <div class="search-item">
                <label for="search"><i class="fa-solid fa-location-dot"></i> Destination</label>
                <input type="text" id="search" name="search" placeholder="Where are you going?" value="">
            </div>
            
            <div class="search-item">
                <label for="duration"><i class="fa-solid fa-calendar-days"></i> Duration</label>
                <select id="duration" name="duration">
                    <option value="">Any Duration</option>
                    <option value="4">1 - 4 Days</option>
                    <option value="6">5 - 6 Days</option>
                    <option value="7">7+ Days</option>
                </select>
            </div>

            <div class="search-item">
                <label for="price"><i class="fa-solid fa-hand-holding-dollar"></i> Max Budget</label>
                <select id="price" name="price">
                    <option value="">Any Price</option>
                    <option value="700">Under $700</option>
                    <option value="1000">Under $1000</option>
                    <option value="1500">Under $1500</option>
                </select>
            </div>

            <div class="search-item" style="border: none; padding: 0;">
                <button type="submit" class="btn btn-primary btn-block" style="height: 100%; border-radius: var(--radius-md);">
                    Search Deals <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- 2. Value Propositions / Features Section -->
<section class="section container">
    <div class="section-header">
        <h2>Why Travel With Us?</h2>
        <p>We deliver an unparalleled global network of handpicked accommodations, seasoned guides, and secure processes.</p>
    </div>
    
    <div class="grid grid-3">
        <div class="card text-center" style="background-color: var(--white); border: 1px solid var(--gray-200); padding: 40px 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
            <div class="icon-wrap" style="width: 70px; height: 70px; border-radius: var(--radius-full); background-color: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 24px;">
                <i class="fa-solid fa-globe"></i>
            </div>
            <h3 class="mb-1">Diverse Destinations</h3>
            <p class="text-muted" style="font-size: 0.95rem;">Over 150+ pristine locations globally, matching high-adventure treks and romantic seaside breaks.</p>
        </div>

        <div class="card text-center" style="background-color: var(--white); border: 1px solid var(--gray-200); padding: 40px 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
            <div class="icon-wrap" style="width: 70px; height: 70px; border-radius: var(--radius-full); background-color: var(--success-light); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 24px;">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h3 class="mb-1">Guaranteed Safety</h3>
            <p class="text-muted" style="font-size: 0.95rem;">Round-the-clock client support, reliable local operators, and complete travel security assurance.</p>
        </div>

        <div class="card text-center" style="background-color: var(--white); border: 1px solid var(--gray-200); padding: 40px 30px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
            <div class="icon-wrap" style="width: 70px; height: 70px; border-radius: var(--radius-full); background-color: var(--warning-light); color: var(--warning); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 24px;">
                <i class="fa-solid fa-tag"></i>
            </div>
            <h3 class="mb-1">Best Rates Guaranteed</h3>
            <p class="text-muted" style="font-size: 0.95rem;">Unbeatable package rates with transparent pricing models and zero hidden booking service fees.</p>
        </div>
    </div>
</section>

<!-- 3. Featured Packages Grid -->
<section class="section" style="background-color: var(--white); border-top: 1px solid var(--gray-200); border-bottom: 1px solid var(--gray-200);">
    <div class="container">
        <div class="section-header">
            <h2>Trending Tour Packages</h2>
            <p>Our handpicked and highest-rated international itineraries, packed with premium experiences.</p>
        </div>

        <div class="grid grid-3">
            <?php if (!empty($featuredPackages)): ?>
                <?php foreach ($featuredPackages as $pkg): ?>
                    <?php 
                        $inWishlist = in_array($pkg['id'], $wishlistItems);
                    ?>
                    <div class="package-card" data-price="<?php echo $pkg['price']; ?>" data-location="<?php echo htmlspecialchars($pkg['location']); ?>">
                        <div class="package-img">
                            <img src="<?php echo htmlspecialchars($pkg['image_url']); ?>" alt="<?php echo htmlspecialchars($pkg['title']); ?>">
                            <span class="featured-badge">Featured</span>
                            <button type="button" class="wishlist-btn <?php echo $inWishlist ? 'active' : ''; ?>" data-id="<?php echo $pkg['id']; ?>" title="Save to Wishlist">
                                <i class="<?php echo $inWishlist ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
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
                            
                            <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline btn-block mb-1" style="margin-top: 20px;">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="grid-column: span 3; padding: 40px 0;">
                    <i class="fa-solid fa-briefcase text-muted" style="font-size: 3rem; margin-bottom: 16px;"></i>
                    <p>No featured packages found. Run the schema seeder to import default data.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center" style="margin-top: 50px;">
            <a href="packages.php" class="btn btn-primary">
                View All Packages <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- 4. Scenic Gallery Section -->
<section class="section container">
    <div class="section-header">
        <h2>Visual Exploration Gallery</h2>
        <p>Breathtaking snapshots captured by our global travel community in beautiful locations.</p>
    </div>
    
    <div class="gallery-grid">
        <div class="gallery-item">
            <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=400&q=80" alt="Beautiful Beach">
        </div>
        <div class="gallery-item">
            <img src="https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=400&q=80" alt="Scenic Canyon Highway">
        </div>
        <div class="gallery-item">
            <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=400&q=80" alt="Majestic Lake View">
        </div>
        <div class="gallery-item">
            <img src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=400&q=80" alt="Boating at Sunset">
        </div>
    </div>
</section>

<!-- 5. Testimonial Section -->
<section class="section" style="background-color: var(--white); border-top: 1px solid var(--gray-200);">
    <div class="container">
        <div class="section-header">
            <h2>Traveler Feedback</h2>
            <p>Read amazing stories and real feedback from families and solo adventurers who booked with us.</p>
        </div>
        
        <div class="grid grid-3">
            <div class="testimonial-card" style="border: 1px solid var(--gray-200); padding: 30px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
                <div class="rating-stars text-primary mb-2" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="text-muted mb-3" style="font-style: italic; font-size: 0.95rem;">"The tropical Bali retreat was spectacular! Every detail, from airport pickup to our ocean villa and private temple tours, was executed flawlessly by TravelGo. Highly recommend!"</p>
                <div class="reviewer d-flex align-center gap-2">
                    <i class="fa-solid fa-circle-user text-primary" style="font-size: 2.25rem;"></i>
                    <div>
                        <h4 style="font-size: 0.95rem;">Sarah Jenkins</h4>
                        <span class="text-muted" style="font-size: 0.8rem; font-weight: 500;">San Francisco, CA</span>
                    </div>
                </div>
            </div>

            <div class="testimonial-card" style="border: 1px solid var(--gray-200); padding: 30px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
                <div class="rating-stars text-primary mb-2" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>
                </div>
                <p class="text-muted mb-3" style="font-style: italic; font-size: 0.95rem;">"Booking our Parisian escape was incredibly fast. The local restaurant guides were absolute gems, and Eiffel Tower dinner tickets saved us hours of standing in queues."</p>
                <div class="reviewer d-flex align-center gap-2">
                    <i class="fa-solid fa-circle-user text-primary" style="font-size: 2.25rem;"></i>
                    <div>
                        <h4 style="font-size: 0.95rem;">Marcus Vance</h4>
                        <span class="text-muted" style="font-size: 0.8rem; font-weight: 500;">London, UK</span>
                    </div>
                </div>
            </div>

            <div class="testimonial-card" style="border: 1px solid var(--gray-200); padding: 30px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);">
                <div class="rating-stars text-primary mb-2" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                </div>
                <p class="text-muted mb-3" style="font-style: italic; font-size: 0.95rem;">"Tokyo Bullet trains and authentic Kyoto tea ceremonies were completely seamless. Incredible response rates and customer service. We will definitely book our next trek here!"</p>
                <div class="reviewer d-flex align-center gap-2">
                    <i class="fa-solid fa-circle-user text-primary" style="font-size: 2.25rem;"></i>
                    <div>
                        <h4 style="font-size: 0.95rem;">Elena Rostova</h4>
                        <span class="text-muted" style="font-size: 0.8rem; font-weight: 500;">Munich, Germany</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
