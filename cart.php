<?php
declare(strict_types=1);

/* 1. LOAD SECURITY & CONFIG */
require_once __DIR__ . '/function.php';
require_once __DIR__ . '/session.php';

startAppSession(); 

require_once __DIR__ . '/cart_config.php';

/* 2. APPLY SECURITY HEADERS */
send_security_headers();

/* 3. CSRF TOKEN SETUP */
if (empty($_SESSION['cart_csrf_token'])) {
    $_SESSION['cart_csrf_token'] = bin2hex(random_bytes(32));
}

/* 4. INITIALIZE CART IN SESSION */
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = ['prod_1' => 1, 'prod_2' => 1, 'prod_3' => 1];
}

/* 5. HANDLE FORM SUBMISSIONS SECURELY */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals($_SESSION['cart_csrf_token'], $submitted)) {
        http_response_code(403);
        exit('Security error: Invalid request.');
    }

    $action  = $_POST['action'] ?? '';
    $prod_id = $_POST['product_id'] ?? '';

    if ($action === 'update' && isset($secure_products[$prod_id])) {
        $qty = (int)($_POST['quantity'] ?? 1);
        $_SESSION['cart'][$prod_id] = max(1, min($qty, MAX_QUANTITY));
    } elseif ($action === 'remove' && isset($secure_products[$prod_id])) {
        unset($_SESSION['cart'][$prod_id]);
    }

    $_SESSION['cart_csrf_token'] = bin2hex(random_bytes(32));
    header('Location: cart.php');
    exit;
}

/* 6. CALCULATE TOTALS (SERVER-SIDE ONLY) */
$total = 0.0;
$original_total = 0.0;
$item_count = 0;
$cart_items = [];

foreach ($_SESSION['cart'] as $prod_id => $qty) {
    if (isset($secure_products[$prod_id])) {
        $p = $secure_products[$prod_id];
        $total          += $p['price'] * $qty;
        $original_total += $p['original_price'] * $qty;
        $item_count     += $qty;

        $cart_items[] = [
            'id'             => $prod_id,
            'name'           => $p['name'],
            'price'          => $p['price'],
            'original_price' => $p['original_price'],
            'discount'       => $p['discount'],
            'qty'            => $qty,
        ];
    }
}

$csrf = $_SESSION['cart_csrf_token'];

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Cart - iPharmatic</title>
    <link rel="stylesheet" href="cart.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- ========== NAVIGATION BAR WITH BACK ARROW ========== -->
    <nav class="nav-bar">
        <a href="home.php" class="back-arrow" title="Back to Home" aria-label="Back to Home">
            <i class="fas fa-arrow-left"></i>
        </a>

        <a class="logo" href="home.php">
            <img src="assets/logo.png" class="logo-icon" alt="Logo">
            <span>iPHARMATIC</span>
        </a>

        <div class="nav-icons">
            <i class="fas fa-bars"></i>
            <i class="fas fa-user"></i>
        </div>
    </nav>

    <!-- ========== SHIPPING BANNER ========== -->
    <div class="shipping-banner">
        <i class="fas fa-check-circle"></i>
        <span>Free shipping on orders above Rs 500!</span>
        <span class="limited">Limited-time</span>
    </div>

    <!-- ========== CART HEADER ========== -->
    <div class="cart-header">
        <label class="select-all">
            <input type="checkbox" id="selectAll">
            <span>All</span>
        </label>
        <h2>Cart (<span><?php echo e($item_count); ?></span>)</h2>
    </div>

    <!-- ========== CART ITEMS ========== -->
    <div class="cart-items">
        <?php if ($item_count === 0): ?>
            <p style="text-align:center; padding: 20px;">Your cart is empty.</p>
        <?php else: ?>
            <?php foreach ($cart_items as $item): ?>
            <div class="cart-item" data-product-id="<?php echo e($item['id']); ?>">

                <!-- Checkbox -->
                <input type="checkbox" class="item-checkbox">

                <!-- Product Image -->
                <div class="item-image">
                    <img src="https://via.placeholder.com/100x100/333/fff?text=<?php echo urlencode(explode(' ', $item['name'])[0]); ?>"
                         alt="<?php echo e($item['name']); ?>">
                </div>

                <!-- Product Details -->
                <div class="item-details">
                    <div class="item-name"><?php echo e($item['name']); ?></div>
                    <div class="item-variant">Strip/10pcs <i class="fas fa-chevron-down"></i></div>
                    <div class="item-price">
                        <span class="current-price">Rs <?php echo e(number_format($item['price'], 2)); ?></span>
                        <span class="original-price"><?php echo e(number_format($item['original_price'], 2)); ?></span>
                        <span class="discount">-<?php echo e($item['discount']); ?>%</span>
                    </div>

                    <!-- Quantity Buttons -->
                    <form method="POST" class="item-quantity">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?php echo e($item['id']); ?>">

                        <button type="submit" name="quantity"
                                value="<?php echo e(max(1, $item['qty'] - 1)); ?>"
                                class="qty-btn minus">-</button>
                        <span class="qty-value"><?php echo e($item['qty']); ?></span>
                        <button type="submit" name="quantity"
                                value="<?php echo e(min($item['qty'] + 1, MAX_QUANTITY)); ?>"
                                class="qty-btn plus">+</button>
                    </form>
                </div>

                <!-- DELETE BUTTON (trash icon) -->
                <form method="POST" class="delete-form">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?php echo e($item['id']); ?>">
                    <button type="submit" class="delete-btn" title="Remove item" aria-label="Remove item">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>

            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ========== INFO MESSAGE ========== -->
    <div class="info-messages">
        <p><i class="fas fa-info-circle"></i> Item availability and pricing are verified server-side before payment.</p>
    </div>

    <!-- ========== BOTTOM BAR ========== -->
    <div class="bottom-bar">
        <div class="total-section">
            <span class="total-original"><?php echo e(number_format($original_total, 2)); ?></span>
            <span class="total-final">Rs <?php echo e(number_format($total, 2)); ?></span>
        </div>
        <button class="checkout-btn" <?php echo $item_count === 0 ? 'disabled' : ''; ?>>
            Checkout (<?php echo e($item_count); ?>)
        </button>
    </div>

    <script src="cart-ui.js"></script>
</body>
</html>