<?php
declare(strict_types=1);

/* 1. ALLOW POST REQUESTS ONLY */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Allow: POST');

    http_response_code(405);

    exit('Method not allowed.');
}

/* 2. START THE EXISTING SESSION */
require_once __DIR__ . '/session.php';

startAppSession();

/* 3. RECEIVE CSRF TOKEN */
$submittedToken = $_POST['csrf_token'] ?? null;

$savedToken = $_SESSION['logout_csrf'] ?? null;

/* 4. VALIDATE THE LOGOUT REQUEST */
if (
    !isset($_SESSION['user_id']) ||
    !is_string($submittedToken) ||
    !is_string($savedToken) ||
    !hash_equals($savedToken, $submittedToken)
) {

    http_response_code(403);

    exit('Invalid logout request.');
}

/* 5. DESTROY AUTHENTICATED SESSION */
destroyAppSession();

/* 6. REDIRECT TO LOGIN PAGE */
header('Location: login.html');

exit;