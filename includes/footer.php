<!-- Global Footer Template -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <!-- Column 1: Company Profile -->
                <div class="footer-col">
                    <a href="index.php" class="footer-logo">
                        <i class="fa-solid fa-paper-plane text-primary"></i> Travel<span>Go</span>
                    </a>
                    <p class="footer-desc">
                        TravelGo is a premier full-service travel agency providing curated destinations, custom tour packages, and seamless reservations since 2012. Our mission is to inspire, explore, and design memories that endure a lifetime.
                    </p>
                    <div class="social-links">
                        <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Home Base</a></li>
                        <li><a href="packages.php">Explore Packages</a></li>
                        <li><a href="register.php">Create Account</a></li>
                        <li><a href="login.php">Partner Sign-In</a></li>
                    </ul>
                </div>

                <!-- Column 3: Contact Details -->
                <div class="footer-col">
                    <h4>Reach Us</h4>
                    <ul class="footer-links" style="font-size: 0.9rem; line-height: 1.8;">
                        <li><i class="fa-solid fa-location-dot text-primary" style="margin-right: 8px;"></i> 123 Travel Avenue, Suite 400, NY</li>
                        <li><i class="fa-solid fa-phone text-primary" style="margin-right: 8px;"></i> +1 (555) 321-7654</li>
                        <li><i class="fa-solid fa-envelope text-primary" style="margin-right: 8px;"></i> contact@travelgo.com</li>
                    </ul>
                </div>

                <!-- Column 4: Newsletter -->
                <div class="footer-col">
                    <h4>Join Newsletter</h4>
                    <p style="font-size: 0.85rem; margin-bottom: 16px;">Subscribe to receive hot deals, seasonal tour updates, and destination blueprints directly in your inbox.</p>
                    <form action="#" class="newsletter-form" onsubmit="event.preventDefault(); alert('Subscribed successfully!'); this.reset();">
                        <input type="email" placeholder="Email address" required>
                        <button type="submit" class="btn btn-primary" style="padding: 12px 18px;"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>

            <!-- Footer copyright bar -->
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> TravelGo Agency. All Rights Reserved. Crafted for premium user experiences.</p>
            </div>
        </div>
    </footer>

    <!-- Main JS Application script -->
    <script src="assets/js/main.js"></script>
</body>
</html>
