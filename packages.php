<?php
$page_title = "Explore Tour Packages";
require_once 'includes/header.php';
require_once 'includes/db.php';

// Parse query variables for initial search loading
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$duration_query = isset($_GET['duration']) ? $_GET['duration'] : '';
$price_query = isset($_GET['price']) ? $_GET['price'] : '';

try {
    // Fetch all packages to load into client-side dynamic search list
    $allStmt = $pdo->prepare("SELECT * FROM packages ORDER BY id DESC");
    $allStmt->execute();
    $packages = $allStmt->fetchAll();

    // Fetch unique locations to populate the filter options dropdown
    $locStmt = $pdo->query("SELECT DISTINCT location FROM packages ORDER BY location ASC");
    $locations = $locStmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch user wishlist if logged in to show active heart icon
    $wishlistItems = [];
    if (isLoggedIn()) {
        $wishlistStmt = $pdo->prepare("SELECT package_id FROM wishlist WHERE user_id = ?");
        $wishlistStmt->execute([getLoggedInUserId()]);
        $wishlistItems = $wishlistStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (\PDOException $e) {
    $packages = [];
    $locations = [];
}
?>

<div class="container section">
    <div class="section-header" style="text-align: left; max-width: 100%; margin-bottom: 20px; border-bottom: 1px solid var(--gray-200); padding-bottom: 20px;">
        <h2>Explore Tour Packages</h2>
        <p>Browse our selection of premium curated packages or use the live filters to discover your next adventure.</p>
    </div>

    <!-- Main Responsive Layout (Sidebar + Main Listings Grid) -->
    <div class="packages-layout">
        
        <!-- Interactive Filter Sidebar -->
        <aside class="filter-sidebar">
            <h3>Refine Search</h3>
            
            <!-- Filter: Keyword -->
            <div class="filter-group">
                <label for="search-input">Keyword</label>
                <input type="text" id="search-input" placeholder="e.g. Bali, Paris, beach..." value="<?php echo htmlspecialchars($search_query); ?>">
            </div>

            <!-- Filter: Destination location -->
            <div class="filter-group">
                <label for="location-filter">Destination</label>
                <select id="location-filter">
                    <option value="">All Destinations</option>
                    <?php foreach ($locations as $loc): ?>
                        <?php 
                            // Check if current location query is contained
                            $selected = (!empty($search_query) && strpos(strtolower($loc), strtolower($search_query)) !== false) ? 'selected' : '';
                        ?>
                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($loc); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter: Duration -->
            <div class="filter-group">
                <label for="duration-filter">Duration</label>
                <select id="duration-filter">
                    <option value="">Any Duration</option>
                    <option value="4" <?php echo $duration_query === '4' ? 'selected' : ''; ?>>1 - 4 Days</option>
                    <option value="6" <?php echo $duration_query === '6' ? 'selected' : ''; ?>>5 - 6 Days</option>
                    <option value="7" <?php echo $duration_query === '7' ? 'selected' : ''; ?>>7+ Days</option>
                </select>
            </div>

            <!-- Filter: Price Range Slider -->
            <div class="filter-group">
                <label>Max Budget</label>
                <div class="range-wrap">
                    <input type="range" id="price-range" min="500" max="2500" step="50" value="<?php echo !empty($price_query) ? htmlspecialchars($price_query) : '2000'; ?>">
                    <div class="range-values">
                        <span>$500</span>
                        <span id="price-val" class="text-primary" style="font-weight: 700;">$<?php echo !empty($price_query) ? htmlspecialchars($price_query) : '2000'; ?></span>
                        <span>$2500</span>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn btn-outline btn-block btn-sm" onclick="document.getElementById('search-input').value=''; document.getElementById('location-filter').value=''; document.getElementById('duration-filter').value=''; document.getElementById('price-range').value='2000'; document.getElementById('price-val').textContent='$2000'; window.dispatchEvent(new Event('input'));" style="margin-top: 10px;">
                Reset Filters <i class="fa-solid fa-arrows-rotate"></i>
            </button>
        </aside>

        <!-- Main Package Listing Grid -->
        <main>
            <div id="no-results-msg" class="text-center" style="display: none; padding: 60px 20px; background-color: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--gray-200);">
                <i class="fa-solid fa-map-location-dot text-muted" style="font-size: 3.5rem; margin-bottom: 20px;"></i>
                <h3>No Packages Match Your Filters</h3>
                <p class="text-muted" style="margin-top: 8px;">Try widening your budget, modifying keywords, or resetting your filter options.</p>
            </div>

            <div class="grid grid-2" id="packages-grid">
                <?php if (!empty($packages)): ?>
                    <?php foreach ($packages as $pkg): ?>
                        <?php 
                            $inWishlist = in_array($pkg['id'], $wishlistItems);
                            
                            // Extract days count from duration string (e.g. "5 Days, 4 Nights" -> 5)
                            $days = 0;
                            if (preg_match('/(\d+)\s+Days/i', $pkg['duration'], $matches)) {
                                $days = intval($matches[1]);
                            }

                            // Server-side pre-filtering from index.php GET queries
                            $displayStyle = 'flex';
                            if (!empty($search_query)) {
                                $matchesTitle = strpos(strtolower($pkg['title']), strtolower($search_query)) !== false;
                                $matchesDesc = strpos(strtolower($pkg['description']), strtolower($search_query)) !== false;
                                $matchesLoc = strpos(strtolower($pkg['location']), strtolower($search_query)) !== false;
                                if (!$matchesTitle && !$matchesDesc && !$matchesLoc) {
                                    $displayStyle = 'none';
                                }
                            }
                            if (!empty($price_query) && $pkg['price'] > floatval($price_query)) {
                                $displayStyle = 'none';
                            }
                            if (!empty($duration_query)) {
                                $matchesDuration = false;
                                if ($duration_query === '4') {
                                    $matchesDuration = ($days >= 1 && $days <= 4);
                                } elseif ($duration_query === '6') {
                                    $matchesDuration = ($days >= 5 && $days <= 6);
                                } elseif ($duration_query === '7') {
                                    $matchesDuration = ($days >= 7);
                                }
                                if (!$matchesDuration) {
                                    $displayStyle = 'none';
                                }
                            }
                        ?>
                        <div class="package-card" style="display: <?php echo $displayStyle; ?>;" data-price="<?php echo $pkg['price']; ?>" data-location="<?php echo htmlspecialchars($pkg['location']); ?>" data-duration-days="<?php echo $days; ?>">
                            <div class="package-img">
                                <img src="<?php echo htmlspecialchars($pkg['image_url']); ?>" alt="<?php echo htmlspecialchars($pkg['title']); ?>">
                                <?php if ($pkg['featured']): ?>
                                    <span class="featured-badge">Featured</span>
                                <?php endif; ?>
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
                                
                                <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline btn-block" style="margin-top: 20px;">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center" style="grid-column: span 2; padding: 40px 0;">
                        <p>No packages available in the catalog. Log in as Admin to create new packages.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Run client-side filter logic immediately after DOM matches to adjust pre-filtered visible states -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Trigger initial filter script call if prefilled values exist
        const searchInput = document.getElementById('search-input');
        const priceRange = document.getElementById('price-range');
        const priceVal = document.getElementById('price-val');
        const locationFilter = document.getElementById('location-filter');
        const durationFilter = document.getElementById('duration-filter');
        
        if ((searchInput && searchInput.value) || (priceRange && priceRange.value !== '2000') || (locationFilter && locationFilter.value) || (durationFilter && durationFilter.value)) {
            // Trigger custom input filtering manually
            const cards = document.querySelectorAll('.package-card');
            let count = 0;
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const maxPrice = priceRange ? parseFloat(priceRange.value) : Infinity;
            const selectedLoc = locationFilter ? locationFilter.value : '';
            const selectedDuration = durationFilter ? durationFilter.value : '';
            
            cards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const desc = card.querySelector('.desc').textContent.toLowerCase();
                const location = card.getAttribute('data-location').toLowerCase();
                const price = parseFloat(card.getAttribute('data-price'));
                const durationDays = parseInt(card.getAttribute('data-duration-days')) || 0;
                
                const matchesQuery = query === '' || title.includes(query) || desc.includes(query) || location.includes(query);
                const matchesPrice = price <= maxPrice;
                const matchesLocation = selectedLoc === '' || location.includes(selectedLoc.toLowerCase());
                
                let matchesDuration = true;
                if (selectedDuration === '4') {
                    matchesDuration = durationDays >= 1 && durationDays <= 4;
                } else if (selectedDuration === '6') {
                    matchesDuration = durationDays >= 5 && durationDays <= 6;
                } else if (selectedDuration === '7') {
                    matchesDuration = durationDays >= 7;
                }
                
                if (matchesQuery && matchesPrice && matchesLocation && matchesDuration) {
                    card.style.display = 'flex';
                    count++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            const noResults = document.getElementById('no-results-msg');
            if (noResults) noResults.style.display = count === 0 ? 'block' : 'none';
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
