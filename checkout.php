<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$error = '';

// 1. Process Reservation Submission Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verifyCsrfToken($token)) {
        $error = "CSRF Token validation failed. Please try again.";
    } else {
        $package_id = intval($_POST['package_id']);
        $guests = intval($_POST['guests']);
        $travel_date = $_POST['travel_date'];
        $card_number = trim($_POST['card_number'] ?? '');
        $card_name = trim($_POST['card_name'] ?? '');
        $expiry = trim($_POST['expiry'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');
        
        // Basic payment credentials validation
        if (empty($card_number) || empty($card_name) || empty($expiry) || empty($cvv)) {
            $error = "All credit card payment fields are required.";
        } elseif (strlen($cvv) < 3 || strlen($cvv) > 4) {
            $error = "Invalid CVV security code format.";
        } else {
            try {
                // Fetch package details for price calculation
                $pkgStmt = $pdo->prepare("SELECT price FROM packages WHERE id = ?");
                $pkgStmt->execute([$package_id]);
                $pkg = $pkgStmt->fetch();
                
                if ($pkg) {
                    $basePrice = floatval($pkg['price']);
                    $subtotal = $basePrice * $guests;
                    $tax = $subtotal * 0.12;
                    $grandTotal = $subtotal + $tax;
                    
                    // Insert into bookings table
                    $bookStmt = $pdo->prepare("
                        INSERT INTO bookings (user_id, package_id, booking_date, travel_date, guests, total_price, status) 
                        VALUES (?, ?, CURDATE(), ?, ?, ?, 'pending')
                    ");
                    $bookStmt->execute([getLoggedInUserId(), $package_id, $travel_date, $guests, $grandTotal]);
                    
                    // Success! Redirect to history
                    header("Location: booking-history.php?success=1");
                    exit();
                } else {
                    $error = "The selected travel package is no longer available.";
                }
            } catch (\PDOException $e) {
                $error = "Failed to record booking: " . $e->getMessage();
            }
        }
    }
}

// 2. Initial Page GET View Validation
$package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
$guests = isset($_POST['guests']) ? intval($_POST['guests']) : 1;
$travel_date = isset($_POST['travel_date']) ? $_POST['travel_date'] : '';

if ($package_id <= 0 || empty($travel_date)) {
    // If not direct post parameters, redirect to package listing
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
    
    // Calculate final billing details
    $basePrice = floatval($package['price']);
    $subtotal = $basePrice * $guests;
    $tax = $subtotal * 0.12;
    $grandTotal = $subtotal + $tax;
} catch (\PDOException $e) {
    die("Error validating order details: " . $e->getMessage());
}

$csrf_token = generateCsrfToken();
$page_title = "Billing Checkout";
require_once 'includes/header.php';
?>

<div class="container section">
    <div class="section-header" style="text-align: left; max-width: 100%; margin-bottom: 20px; border-bottom: 1px solid var(--gray-200); padding-bottom: 20px;">
        <h2>Order Billing Checkout</h2>
        <p>Complete your booking reservation securely. Your details are fully encrypted.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Checkout Sub-Grid layout -->
    <div class="checkout-grid">
        
        <!-- Form Column -->
        <main>
            <form action="checkout.php" method="POST" class="auth-form" style="display: contents;">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                <input type="hidden" name="guests" value="<?php echo $guests; ?>">
                <input type="hidden" name="travel_date" value="<?php echo htmlspecialchars($travel_date); ?>">
                <input type="hidden" name="confirm_booking" value="1">
                
                <!-- Box: Billing Details -->
                <div class="checkout-box">
                    <h2><i class="fa-solid fa-user-tag text-primary"></i> Billing Information</h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div class="form-group">
                            <label>Client Name</label>
                            <input type="text" value="<?php echo htmlspecialchars(getLoggedInUserName()); ?>" disabled style="opacity: 0.8;">
                        </div>
                        <div class="form-group">
                            <label>Registered Email</label>
                            <input type="email" value="<?php echo htmlspecialchars(getLoggedInUserEmail()); ?>" disabled style="opacity: 0.8;">
                        </div>
                    </div>
                </div>

                <!-- Box: Secure Simulated Card Payment -->
                <div class="checkout-box">
                    <h2><i class="fa-solid fa-shield-halved text-primary"></i> Secure Credit Payment</h2>
                    
                    <div class="payment-options">
                        <div class="payment-card-option active">
                            <i class="fa-solid fa-credit-card"></i>
                            Credit Card
                        </div>
                        <div class="payment-card-option" onclick="alert('Digital Wallets are currently undergoing routine maintenance. Please use Credit Card.');">
                            <i class="fa-brands fa-paypal"></i>
                            PayPal
                        </div>
                        <div class="payment-card-option" onclick="alert('Net Banking is currently undergoing routine maintenance. Please use Credit Card.');">
                            <i class="fa-solid fa-building-columns"></i>
                            Net Banking
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="card_name">Cardholder Name</label>
                        <input type="text" id="card_name" name="card_name" placeholder="John Doe" required value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="4111 2222 3333 4444" required maxlength="19" value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>">
                    </div>

                    <div class="payment-form-row">
                        <div class="form-group">
                            <label for="expiry">Expiry MM/YY</label>
                            <input type="text" id="expiry" name="expiry" placeholder="12/28" required maxlength="5" value="<?php echo htmlspecialchars($_POST['expiry'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV Code</label>
                            <input type="password" id="cvv" name="cvv" placeholder="123" required maxlength="4">
                        </div>
                    </div>

                    <div style="margin-top: 24px; display: flex; align-items: flex-start; gap: 10px;">
                        <input type="checkbox" id="agree" required style="width: 18px; height: 18px; margin-top: 3px; cursor: pointer;">
                        <label for="agree" style="font-size: 0.85rem; font-weight: 500; color: var(--gray-700); cursor: pointer;">
                            I accept the TravelGo general Terms of Service and Cancellation Policies.
                        </label>
                    </div>
                </div>

                <!-- Box: Booking Button Trigger -->
                <button type="submit" class="btn btn-primary btn-block mb-3" style="font-size: 1.1rem; padding: 16px;">
                    Confirm & Book Trip ($<?php echo number_format($grandTotal, 2); ?>) <i class="fa-solid fa-plane-departure"></i>
                </button>
            </form>
        </main>

        <!-- Sidebar Tour Overview Box -->
        <aside>
            <div class="booking-sidebar" style="position: static; width: 100%;">
                <h3 style="font-size: 1.25rem; border-bottom: 1px solid var(--gray-200); padding-bottom: 12px; margin-bottom: 20px;">
                    Reservation Overview
                </h3>

                <div class="booking-tour-summary-card" style="display: flex; gap: 16px; margin-bottom: 20px; align-items: center;">
                    <div style="width: 90px; height: 70px; border-radius: var(--radius-sm); overflow: hidden;">
                        <img src="<?php echo htmlspecialchars($package['image_url']); ?>" alt="<?php echo htmlspecialchars($package['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div>
                        <h4 style="font-size: 0.95rem; margin-bottom: 4px;"><?php echo htmlspecialchars($package['title']); ?></h4>
                        <span class="text-muted" style="font-size: 0.8rem; font-weight: 600;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($package['location']); ?></span>
                    </div>
                </div>

                <div style="font-size: 0.9rem; line-height: 2; margin-bottom: 20px; border-bottom: 1px solid var(--gray-200); padding-bottom: 16px;">
                    <div class="d-flex justify-between">
                        <span class="text-muted">Target Travel Date:</span>
                        <strong style="color: var(--dark);"><?php echo date('F d, Y', strtotime($travel_date)); ?></strong>
                    </div>
                    <div class="d-flex justify-between">
                        <span class="text-muted">Guests Count:</span>
                        <strong style="color: var(--dark);"><?php echo $guests; ?> Guest<?php echo $guests > 1 ? 's' : ''; ?></strong>
                    </div>
                    <div class="d-flex justify-between">
                        <span class="text-muted">Total Trip Duration:</span>
                        <strong style="color: var(--dark);"><?php echo htmlspecialchars($package['duration']); ?></strong>
                    </div>
                </div>

                <h3 style="font-size: 1.15rem; margin-bottom: 16px;">Billing Summary</h3>
                <div class="booking-summary-box" style="margin-bottom: 0;">
                    <div class="summary-row">
                        <span>Base Rate (x<?php echo $guests; ?>)</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Travel Tax (12%)</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Grand Total Summary</span>
                        <span style="font-weight: 800; color: var(--primary); font-size: 1.25rem;">$<?php echo number_format($grandTotal, 2); ?></span>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    // Format card number with spaces automatically
    document.getElementById('card_number').addEventListener('input', function(e) {
        let v = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let matches = v.match(/\d{4,16}/g);
        let match = matches && matches[0] || '';
        let parts = [];

        for (let i = 0, len = match.length; i < len; i += 4) {
            parts.push(match.substring(i, i + 4));
        }

        if (parts.length > 0) {
            e.target.value = parts.join(' ');
        } else {
            e.target.value = v;
        }
    });

    // Format Expiry Date MM/YY automatically
    document.getElementById('expiry').addEventListener('input', function(e) {
        let v = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        if (v.length >= 2) {
            e.target.value = v.substring(0,2) + '/' + v.substring(2,4);
        } else {
            e.target.value = v;
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
