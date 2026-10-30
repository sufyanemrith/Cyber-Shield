<?php
declare(strict_types=1);

/* 1. USE THE SAME HELPERS AS LOGIN */
require_once __DIR__ . '/function.php';
require_once __DIR__ . '/session.php';

send_security_headers();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('Method not allowed.');
}

/* 2. CHECK EXISTING LOGIN SESSION */
/* Guests can access this homepage. */

$user = currentAppUser();

$logoutToken = null;

/* Generate a CSRF token for logout */
if ($user !== null) {

    if (
        !isset($_SESSION['logout_csrf']) ||
        !is_string($_SESSION['logout_csrf']) ||
        strlen($_SESSION['logout_csrf']) !== 64
    ) {
        $_SESSION['logout_csrf'] = bin2hex(
            random_bytes(32)
        );
    }

    $logoutToken = $_SESSION['logout_csrf'];
}

/* Save session */
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

/* 3. HOME PAGE VARIABLES */

$searchResults = null;
$searchMessage = '';
$searchTerm = '';

$categories = [];
$featured = [];

$pageError = '';

/* 4. CONNECT TO SHARED DATABASE */

try {

    /* Use the same db.php as login and signup. */
    require_once __DIR__ . '/db.php';

    if (
        !isset($pdo) ||
        !($pdo instanceof PDO)
    ) {
        throw new RuntimeException(
            'Shared database connection unavailable.'
        );
    }

    /* 5. MEDICINE SEARCH */

    if (array_key_exists('q', $_GET)) {

        $term = clean_search($_GET['q']);
        $searchResults = [];

        if ($term === null) {

            $searchMessage =
                'Invalid search. Use 1–80 letters, ' .
                'numbers, spaces, hyphens, ' .
                'apostrophes or periods.';

        } else {

            $searchTerm = $term;

            /* Prepared statement prevents SQL injection */

            $stmt = $pdo->prepare(
                'SELECT
                    product_id,
                    name,
                    price,
                    required_prescription

                 FROM products

                 WHERE name LIKE :q
                   AND stock_qty > 0
                   AND date_expired > CURDATE()

                 ORDER BY name

                 LIMIT 50'
            );

            $stmt->execute([
                ':q' => '%' . $term . '%'
            ]);

            $searchResults = $stmt->fetchAll();

            if ($searchResults === []) {
                $searchMessage =
                    'No products match your search.';
            }
        }
    }

    /* 6. LOAD PRODUCT CATEGORIES */

    $catStmt = $pdo->query(
        'SELECT
            category_id,
            name

         FROM category

         ORDER BY name'
    );

    $categories = $catStmt->fetchAll();

    /* 7. LOAD NEW PRODUCTS */

    $prodStmt = $pdo->query(
        'SELECT
            product_id,
            name,
            price,
            required_prescription

         FROM products

         WHERE stock_qty > 0
           AND date_expired > CURDATE()

         ORDER BY date_created DESC

         LIMIT 4'
    );

    $featured = $prodStmt->fetchAll();

} catch (Throwable $error) {

    /* Log technical error privately */
    error_log(
        'iPHARMATIC home: ' . $error->getMessage()
    );

    /* Show safe error message */
    $pageError =
        'Products are temporarily unavailable. ' .
        'Please try again later.';

    $searchResults = null;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>iPHARMATIC | Home</title>

    <!-- GOOGLE FONT MATCHING LOGIN -->

    <link
        href="https://fonts.googleapis.com/css2?family=Aleo:wght@400;700&display=swap"
        rel="stylesheet">

    <!-- HOME PAGE CSS -->

    <link rel="stylesheet" href="home.css">

    <!-- HOME PAGE JAVASCRIPT -->

    <script src="home.js" defer></script>

</head>

<body>

    <!-- 8. HEADER -->

    <header>

        <!-- PHARMACY LOGO -->

        <a
            class="logo"
            href="home.php"
            aria-label="iPHARMATIC home">

            <img
                src="assets/logo.png"
                class="logo-icon"
                alt="">

            <span>iPHARMATIC</span>

        </a>

        <!-- 9. NAVIGATION -->

        <nav
            class="navigation"
            aria-label="Main navigation">

            <a href="home.php" aria-current="page">
                HOME
            </a>

            <a href="faqs.html">
                FAQs
            </a>

            <a href="about.html">
                ABOUT US
            </a>

            <!-- SHOW LOGIN AND SIGNUP TO GUESTS -->

            <?php if ($user === null): ?>

                <a href="login.html">
                    LOG IN
                </a>

                <a href="signup.html">
                    SIGN UP
                </a>

            <?php endif; ?>

        </nav>

        <!-- 10. ACCOUNT AND CART -->

        <div class="account-actions">

            <!-- LOGGED-IN USER -->

            <?php if ($user !== null): ?>

                <span class="welcome">
                    Hello, <?= e($user['username']) ?>
                </span>

                <!-- CSRF-PROTECTED LOGOUT -->

                <form
                    class="logout-form"
                    action="logout.php"
                    method="post">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($logoutToken) ?>">

                    <button
                        class="logout-button"
                        type="submit">

                        LOG OUT

                    </button>

                </form>

            <?php endif; ?>

        <!--3. SHOPPING CART -->
        <div class="account-actions">
            <?php
            // Calculate cart items from session
            $cart_count = 0;
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                $cart_count = array_sum($_SESSION['cart']);
            }
            ?>
            <a href="cart.php" class="cart-link" title="Shopping cart" aria-label="Shopping cart">
                <span class="cart-count"><?php echo e($cart_count); ?> item(s)</span>
                <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor" aria-hidden="true">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0 0 20 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
            </a>
        </div>

        

    </header>

    <!-- 11. MAIN CONTENT -->

    <main>

        <!-- 12. ORIGINAL HERO SECTION -->

        <section class="hero">

            <div class="hero-text">

                <h1>
                    YOUR HEALTH, OUR PRIORITY!
                </h1>

                <p>
                    We are dedicated to providing
                    top-quality pharmacy services
                    in a modernized way never seen before.
                </p>

                <p>
                    Order over-the-counter and prescription
                    medication online, verified by our
                    pharmacists, delivered to your door.
                </p>

                <!-- 13. MEDICINE SEARCH -->

                <form
                    class="search"
                    id="searchForm"
                    action="home.php#results"
                    method="get"
                    novalidate>

                    <div class="search-bar">

                        <label
                            class="sr-only"
                            for="searchInput">

                            Search medicines

                        </label>

                        <input
                            type="text"
                            id="searchInput"
                            name="q"
                            placeholder="Type here"
                            maxlength="80"
                            required
                            aria-describedby="searchError"
                            value="<?= e($searchTerm) ?>">

                        <button type="submit">
                            Search
                        </button>

                    </div>

                    <span
                        class="search-error"
                        id="searchError"
                        role="alert"
                        aria-live="polite">
                    </span>

                </form>

                <!-- 14. ACTION BUTTONS -->

                <div class="actions">

                    <a
                        class="btn"
                        href="home.php#catalog">

                        Shop Medicines

                    </a>

                    <a
                        class="btn"
                        href="login.html">

                        Upload Prescription

                    </a>

                </div>

                <!-- 15. ORIGINAL SERVICE BADGES -->

                <div
                    class="badges"
                    aria-label="Our services">

                    <span>

                        <img
                            src="assets/verified.png"
                            class="badge-icon"
                            alt="">

                        Verified

                    </span>

                    <span>

                        <img
                            src="assets/fast delivery.png"
                            class="badge-icon"
                            alt="">

                        Fast Delivery

                    </span>

                    <span>

                        <img
                            src="assets/medavailability.png"
                            class="badge-icon"
                            alt="">

                        MedAvailability

                    </span>

                </div>

            </div>

            <!-- ORIGINAL HERO IMAGE -->

            <img
                class="hero-img"
                src="assets/pills.jpg"
                alt="Green medicine bottle with capsules">

        </section>

        <!-- 16. DATABASE ERROR MESSAGE -->

        <?php if ($pageError !== ''): ?>

            <section
                class="home-section home-section--notice"
                id="results">

                <p
                    class="empty"
                    role="alert">

                    <?= e($pageError) ?>

                </p>

            </section>

        <!-- 17. MEDICINE SEARCH RESULTS -->

        <?php elseif ($searchResults !== null): ?>

            <section
                class="home-section home-section--results"
                id="results">

                <h2>
                    Search results

                    <?php if ($searchTerm !== ''): ?>
                        for "<?= e($searchTerm) ?>"
                    <?php endif; ?>

                </h2>

                <?php if ($searchMessage !== ''): ?>

                    <p
                        class="empty"
                        role="status">

                        <?= e($searchMessage) ?>

                    </p>

                <?php endif; ?>

                <div class="product-grid">

                    <?php foreach ($searchResults as $product): ?>

                        <article class="product-card">

                            <h3>
                                <?= e($product['name']) ?>
                            </h3>

                            <p class="price">

                                Rs
                                <?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>

                            </p>

                            <?php if (
                                (int) $product['required_prescription'] === 1
                            ): ?>

                                <p class="rx">
                                    Prescription required
                                </p>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endif; ?>

        <!-- 18. SHOP BY CATEGORY -->

        <section
            class="home-section home-section--categories"
            id="catalog">

            <h2>
                Shop by category
            </h2>

            <?php if ($categories === []): ?>

                <p class="empty">
                    No categories yet.
                    Check back soon.
                </p>

            <?php else: ?>

                <div class="chips">

                    <?php foreach ($categories as $category): ?>

                        <span class="chip">
                            <?= e($category['name']) ?>
                        </span>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <!-- 19. NEW IN STOCK -->

        <section
            class="home-section home-section--stock"
            id="new-stock">

            <h2>
                New in stock
            </h2>

            <?php if ($featured === []): ?>

                <p class="empty">
                    No products available right now.
                </p>

            <?php else: ?>

                <div class="product-grid">

                    <?php foreach ($featured as $product): ?>

                        <article class="product-card">

                            <h3>
                                <?= e($product['name']) ?>
                            </h3>

                            <p class="price">

                                Rs
                                <?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>

                            </p>

                            <?php if (
                                (int) $product['required_prescription'] === 1
                            ): ?>

                                <p class="rx">
                                    Prescription required
                                </p>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </main>

</body>
</html>