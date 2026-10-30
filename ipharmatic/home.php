<?php
declare(strict_types=1);

/* 1. LOAD SHARED FILES */
require_once __DIR__ . '/function.php';
require_once __DIR__ . '/session.php';

send_security_headers();


/* 2. ALLOW GET REQUESTS ONLY */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit('Method not allowed.');
}


/* 3. CHECK USER SESSION */
$user = currentAppUser();

$logoutToken = null;


/* GENERATE LOGOUT CSRF TOKEN */
if ($user !== null) {

    if (
        !isset($_SESSION['logout_csrf']) ||
        !is_string($_SESSION['logout_csrf']) ||
        $_SESSION['logout_csrf'] === ''
    ) {

        $_SESSION['logout_csrf'] = bin2hex(
            random_bytes(32)
        );
    }

    $logoutToken = $_SESSION['logout_csrf'];
}


/* SAVE AND CLOSE SESSION */
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}


/* 4. INITIALIZE HOME PAGE VARIABLES */
$searchResults = null;
$searchMessage = '';
$searchTerm = '';

$categories = [];
$featured = [];

$pageError = '';


/* 5. CONNECT TO SHARED DATABASE */
try {

    require_once __DIR__ . '/db.php';

    if (
        !isset($pdo) ||
        !$pdo instanceof PDO
    ) {

        throw new RuntimeException(
            'Shared database connection is unavailable.'
        );
    }


    /* 6. HANDLE MEDICINE SEARCH */
    if (array_key_exists('q', $_GET)) {

        $term = clean_search($_GET['q']);

        if ($term === null) {

            $searchMessage =
                'Invalid search. Use 1–80 letters, numbers, spaces, ' .
                'hyphens, apostrophes or periods.';

            $searchResults = [];

        } else {

            $searchTerm = $term;


            /* PREPARED STATEMENT AGAINST SQL INJECTION */
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


            /* NO SEARCH RESULTS */
            if (!$searchResults) {

                $searchMessage =
                    'No products match your search.';
            }
        }
    }


    /* 7. LOAD PRODUCT CATEGORIES */
    $catStmt = $pdo->prepare(
        'SELECT
            category_id,
            name

         FROM category

         ORDER BY name'
    );

    $catStmt->execute();

    $categories = $catStmt->fetchAll();


    /* 8. LOAD NEWEST AVAILABLE PRODUCTS */
    $prodStmt = $pdo->prepare(
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

    $prodStmt->execute();

    $featured = $prodStmt->fetchAll();


} catch (Throwable $error) {

    /* LOG TECHNICAL ERRORS SERVER-SIDE */
    error_log(
        'iPHARMATIC home: ' . $error->getMessage()
    );


    /* DISPLAY SAFE ERROR MESSAGE */
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

    <link rel="stylesheet" href="home.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Aleo:wght@400;700&display=swap"
        rel="stylesheet">

</head>

<body>

    <!-- 1. HEADER -->
    <header>

        <!-- IPHARMATIC LOGO -->
        <a href="home.php" class="logo">

            <img
                src="assets/logo.png"
                class="logo-icon"
                alt="iPHARMATIC Logo">

            <span>iPHARMATIC</span>

        </a>


        <!-- 2. NAVIGATION -->
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

            <a href="login.html">
                LOG IN
            </a>

            <a href="signup.html">
                SIGN UP
            </a>

        </nav>


        <!-- 3. SHOPPING CART -->
        <a
            class="cart"
            href="cart.php"
            aria-label="Cart (login required)">

            <span>0 item(s)</span>

            <svg
                viewBox="0 0 24 24"
                width="32"
                height="32"
                fill="currentColor"
                aria-hidden="true">

                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0 0 20 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>

            </svg>

        </a>

    </header>


    <!-- 4. MAIN CONTENT -->
    <main>


        <!-- 5. HERO SECTION -->
        <section class="hero">

            <div class="hero-text">


                <!-- MAIN HEADING -->
                <h1>
                    YOUR HEALTH, OUR PRIORITY!
                </h1>


                <!-- FIRST DESCRIPTION -->
                <p>
                    We are dedicated to providing
                    top-quality pharmacy services
                    in a modernized way never seen before.
                </p>


                <!-- SECOND DESCRIPTION -->
                <p>
                    Order over-the-counter and prescription
                    medication online, verified by our
                    pharmacists, delivered to your door.
                </p>


                <!-- 6. MEDICINE SEARCH BAR -->
                <form
                    class="search"
                    id="searchForm"
                    action="home.php#results"
                    method="get">

                    <div class="search-bar">


                        <!-- ACCESSIBLE LABEL -->
                        <label
                            class="sr-only"
                            for="searchInput">

                            Search medicines

                        </label>


                        <!-- SEARCH INPUT -->
                        <input
                            type="text"
                            id="searchInput"
                            name="q"
                            placeholder="Type here"
                            required
                            maxlength="80">


                        <!-- SEARCH BUTTON -->
                        <button type="submit">
                            Search
                        </button>

                    </div>


                    <!-- SEARCH VALIDATION MESSAGE -->
                    <span
                        id="searchError"
                        class="search-error"
                        role="alert"
                        aria-live="polite">
                    </span>

                </form>


                <!-- 7. ACTION BUTTONS -->
                <div class="actions">


                    <!-- SHOP MEDICINES -->
                    <a
                        href="home.php#catalog"
                        class="btn">

                        Shop Medicines

                    </a>


                    <!-- UPLOAD PRESCRIPTION -->
                    <a
                        href="login.html"
                        class="btn">

                        Upload Prescription

                    </a>

                </div>


                <!-- 8. SERVICE BADGES -->
                <div class="badges">


                    <!-- VERIFIED -->
                    <span>

                        <img
                            src="assets/verified.png"
                            alt=""
                            class="badge-icon">

                        Verified

                    </span>


                    <!-- FAST DELIVERY -->
                    <span>

                        <img
                            src="assets/fast delivery.png"
                            alt=""
                            class="badge-icon">

                        Fast Delivery

                    </span>


                    <!-- MEDICINE AVAILABILITY -->
                    <span>

                        <img
                            src="assets/medavailability.png"
                            alt=""
                            class="badge-icon">

                        MedAvailability

                    </span>

                </div>

            </div>


            <!-- 9. HERO IMAGE -->
            <img
                src="assets/pills.jpg"
                alt="Bottle with green capsules"
                class="hero-img">

        </section>


        <!-- 10. SHOP BY CATEGORY -->
        <section
            class="home-section"
            id="catalog">

            <h2>
                Shop by category
            </h2>

            <p class="empty">
                No categories yet. Check back soon.
            </p>

        </section>


        <!-- 11. NEW IN STOCK -->
        <section class="home-section">

            <h2>
                New in stock
            </h2>

            <p class="empty">
                No products available right now.
            </p>

        </section>

    </main>


    <!-- 12. HOMEPAGE JAVASCRIPT -->
    <script src="home.js"></script>

</body>
</html>