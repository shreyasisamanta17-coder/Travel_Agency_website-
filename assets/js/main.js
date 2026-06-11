// TravelGo Core Frontend Engine
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Sticky Header scroll effect
    const header = document.querySelector('header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // 2. User Profile Dropdown Toggle
    const profileBtn = document.querySelector('.user-profile-btn');
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (profileBtn && userDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!profileBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });
    }

    // 3. Dynamic Guest Counter & Price Calculator (Package Details Page)
    const guestInput = document.getElementById('guests');
    const summaryGuests = document.getElementById('summary-guests');
    const summarySubtotal = document.getElementById('summary-subtotal');
    const summaryTax = document.getElementById('summary-tax');
    const summaryTotal = document.getElementById('summary-total');
    const basePriceEl = document.getElementById('base-price');
    const totalPriceInput = document.getElementById('total_price');

    if (guestInput && basePriceEl) {
        const basePrice = parseFloat(basePriceEl.getAttribute('data-price'));
        
        function calculatePrice() {
            const guests = parseInt(guestInput.value) || 1;
            const subtotal = basePrice * guests;
            const tax = subtotal * 0.12; // 12% standard travel GST/VAT/Tax
            const total = subtotal + tax;
            
            if (summaryGuests) summaryGuests.textContent = `${guests} Guest${guests > 1 ? 's' : ''}`;
            if (summarySubtotal) summarySubtotal.textContent = `$${subtotal.toFixed(2)}`;
            if (summaryTax) summaryTax.textContent = `$${tax.toFixed(2)}`;
            if (summaryTotal) summaryTotal.textContent = `$${total.toFixed(2)}`;
            if (totalPriceInput) totalPriceInput.value = total.toFixed(2);
        }
        
        guestInput.addEventListener('input', calculatePrice);
        guestInput.addEventListener('change', calculatePrice);
        
        // Initial run
        calculatePrice();
    }

    // 4. AJAX Wishlist Toggle
    const wishlistButtons = document.querySelectorAll('.wishlist-btn');
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const packageId = this.getAttribute('data-id');
            const isAdding = !this.classList.contains('active');
            
            // Check if user is logged in (could check page state via HTML data attribute)
            const userId = document.body.getAttribute('data-user-id');
            if (!userId) {
                // Redirect to login if not logged in
                window.location.href = 'login.php';
                return;
            }
            
            // Send AJAX Fetch Request to handle-wishlist.php
            fetch('wishlist.php?ajax=1', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `package_id=${packageId}&action=${isAdding ? 'add' : 'remove'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (isAdding) {
                        this.classList.add('active');
                        this.querySelector('i').className = 'fa-solid fa-heart';
                    } else {
                        this.classList.remove('active');
                        this.querySelector('i').className = 'fa-regular fa-heart';
                        
                        // If we are currently on the wishlist page, remove the card from the UI
                        if (window.location.pathname.includes('wishlist.php')) {
                            const card = this.closest('.package-card');
                            if (card) {
                                card.style.opacity = '0';
                                setTimeout(() => {
                                    card.remove();
                                    // If wishlist is empty, refresh page to show empty state
                                    const remaining = document.querySelectorAll('.package-card');
                                    if (remaining.length === 0) {
                                        window.location.reload();
                                    }
                                }, 300);
                            }
                        }
                    }
                } else {
                    alert(data.message || 'Operation failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error toggling wishlist:', error);
            });
        });
    });

    // 5. Packages Directory Real-Time Instant Filtering (Packages Page)
    const searchInput = document.getElementById('search-input');
    const locationFilter = document.getElementById('location-filter');
    const durationFilter = document.getElementById('duration-filter');
    const priceRange = document.getElementById('price-range');
    const priceVal = document.getElementById('price-val');
    const packageCards = document.querySelectorAll('.package-card');
    
    if (priceRange && priceVal) {
        priceRange.addEventListener('input', function() {
            priceVal.textContent = `$${this.value}`;
            filterPackages();
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', filterPackages);
    }
    
    if (locationFilter) {
        locationFilter.addEventListener('change', filterPackages);
    }

    if (durationFilter) {
        durationFilter.addEventListener('change', filterPackages);
    }
    
    function filterPackages() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const maxPrice = priceRange ? parseFloat(priceRange.value) : Infinity;
        const selectedLoc = locationFilter ? locationFilter.value : '';
        const selectedDuration = durationFilter ? durationFilter.value : '';
        
        let visibleCount = 0;
        
        packageCards.forEach(card => {
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
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        const noResults = document.getElementById('no-results-msg');
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }
});
