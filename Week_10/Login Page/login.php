<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

/* 1. SEND JSON RESPONSE */
function sendJson(int $status, array $body): never
{
    http_response_code($status);

    echo json_encode($body);

    exit;
}

/* 2. ALLOW POST REQUESTS ONLY */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Allow: POST');

    sendJson(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

/* 3. RECEIVE AND VALIDATE INPUTS */
$emailInput = $_POST['email'] ?? null;
$password = $_POST['password'] ?? null;

if (!is_string($emailInput) || !is_string($password)) {

    sendJson(400, [
        'success' => false,
        'message' => 'Invalid form submission.'
    ]);
}

$email = strtolower(trim($emailInput));

// Match the shared customer.email VARCHAR(150) schema.
if (
    strlen($email) > 150 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $password === '' ||
    strlen($password) > 1024
) {

    sendJson(422, [
        'success' => false,
        'message' => 'Enter a valid email and password.'
    ]);
}

/* 4. CONNECT TO SHARED DATABASE */
try {

    // db.php exports $pdo for the teammate's signup as well.
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/session.php';

    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new RuntimeException(
            'Database connection was not initialized.'
        );
    }

    // Protect concurrent updates to the same email's attempt counter.
    $pdo->beginTransaction();

    /* 5. CREATE LOGIN LIMIT RECORD */
    $createLimit = $pdo->prepare(
        'INSERT INTO login_limits (email)
         VALUES (?)
         ON DUPLICATE KEY UPDATE email = email'
    );

    $createLimit->execute([$email]);

    /* 6. CHECK CURRENT LOGIN RATE LIMIT */
    $getLimit = $pdo->prepare(
        'SELECT
            failed_attempts,
            locked_until,

            COALESCE(
                locked_until > UTC_TIMESTAMP(),
                0
            ) AS is_locked,

            COALESCE(
                GREATEST(
                    0,
                    TIMESTAMPDIFF(
                        SECOND,
                        UTC_TIMESTAMP(),
                        locked_until
                    )
                ),
                0
            ) AS seconds_left,

            COALESCE(
                last_failed_at <
                    UTC_TIMESTAMP() - INTERVAL 5 MINUTE,
                0
            ) AS window_expired

         FROM login_limits

         WHERE email = ?

         FOR UPDATE'
    );

    $getLimit->execute([$email]);

    $limit = $getLimit->fetch();

    if (!$limit) {
        throw new RuntimeException(
            'Login limiter row was not found.'
        );
    }

    /* 7. REJECT LOGIN WHEN LOCKED */
    if ((int) $limit['is_locked'] === 1) {

        $seconds = max(
            1,
            (int) $limit['seconds_left']
        );

        $minutes = (int) ceil($seconds / 60);

        $pdo->commit();

        header('Retry-After: ' . $seconds);

        sendJson(429, [
            'success' => false,
            'message' =>
                "Too many failed attempts. " .
                "Try again in about $minutes minute(s)."
        ]);
    }

    /* 8. RESET EXPIRED FAILED ATTEMPTS */
    $attempts = (int) $limit['failed_attempts'];

    if (
        (int) $limit['window_expired'] === 1 ||
        $limit['locked_until'] !== null
    ) {
        $attempts = 0;
    }

    /* 9. FIND CUSTOMER IN TEAM DATABASE */
    $findUser = $pdo->prepare(
        'SELECT
            customer_id,
            username,
            email,
            password_hash,
            role

         FROM customer

         WHERE email = ?

         LIMIT 1'
    );

    $findUser->execute([$email]);

    $user = $findUser->fetch();

    /* 10. VERIFY PASSWORD HASH */
    if (
        $user &&
        password_verify(
            $password,
            $user['password_hash']
        )
    ) {

        // Reset failed attempts after successful authentication.
        $clearLimit = $pdo->prepare(
            'DELETE FROM login_limits WHERE email = ?'
        );

        $clearLimit->execute([$email]);

        $pdo->commit();

        /* 11. CREATE AUTHENTICATED SESSION */
        startAppSession();

        $_SESSION = [];

        if (!session_regenerate_id(true)) {
            throw new RuntimeException(
                'Session renewal failed.'
            );
        }

        $_SESSION['user_id'] = (int) $user['customer_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        // Save the session before the browser opens the dashboard.
        session_write_close();

        /* 12. SUCCESSFUL LOGIN */
        sendJson(200, [
            'success' => true,
            'message' => 'Login successful.',
            'redirect' => 'home.php'
        ]);
    }

    /* 13. RECORD FAILED ATTEMPT */
    // The same response is used for unknown emails and incorrect passwords.
    $attempts++;

    $updateLimit = $pdo->prepare(
        'UPDATE login_limits

         SET failed_attempts = ?,

             last_failed_at = UTC_TIMESTAMP(),

             locked_until = IF(
                 ? >= 3,
                 UTC_TIMESTAMP() + INTERVAL 5 MINUTE,
                 NULL
             )

         WHERE email = ?'
    );

    $updateLimit->execute([
        $attempts,
        $attempts,
        $email
    ]);

    $pdo->commit();

    /* 14. LOCK LOGIN AFTER THREE FAILURES */
    if ($attempts >= 3) {

        header('Retry-After: 300');

        sendJson(429, [
            'success' => false,
            'message' =>
                'Too many failed attempts. ' .
                'Login temporarily locked for 5 minutes.'
        ]);
    }

    /* 15. DISPLAY REMAINING ATTEMPTS */
    sendJson(401, [
        'success' => false,
        'message' =>
            'Invalid email or password. ' .
            (3 - $attempts) . ' attempt(s) remaining.'
    ]);


} catch (Throwable $error) {

    // Undo unfinished database operations.
    if (
        isset($pdo) &&
        $pdo instanceof PDO &&
        $pdo->inTransaction()
    ) {
        $pdo->rollBack();
    }

    // Technical details are logged, not exposed to customers.
    error_log('iPHARMATIC login: ' . $error->getMessage());

    sendJson(500, [
        'success' => false,
        'message' =>
            'A server error occurred. Please try again later.'
    ]);
}