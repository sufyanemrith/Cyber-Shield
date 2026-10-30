<?php
declare(strict_types=1);

/* 1. SHARED SESSION CONFIGURATION */
function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('IPHARMATICSESSID');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    if (!session_start()) {
        throw new RuntimeException(
            'Could not start PHP session.'
        );
    }
}

/* 2. DESTROY SESSION */
function destroyAppSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?: 'Lax'
        ]);
    }

    session_destroy();
}

/* 3. CHECK OPTIONAL AUTHENTICATION */
/*This function is for public pages.Guest:Returns null.Authenticated user:Returns user details.Expired session:Destroys the session and returns null.*/

function currentAppUser(): ?array
{
    startAppSession();

    $userId = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? null;
    $email = $_SESSION['user_email'] ?? null;
    $role = $_SESSION['user_role'] ?? null;
    $lastActivity = $_SESSION['last_activity'] ?? null;

    /* ALLOW GUESTS */
    if (
        $userId === null &&
        $lastActivity === null
    ) {
        return null;
    }

    /* VALIDATE SESSION DATA */
    if (
        !is_int($userId) ||
        $userId < 1 ||

        !is_string($username) ||
        $username === '' ||

        !is_string($email) ||
        $email === '' ||

        !is_string($role) ||
        $role === '' ||

        !is_int($lastActivity) ||

        $lastActivity > time() ||

        time() - $lastActivity > 15 * 60
    ) {

        destroyAppSession();

        return null;
    }

    /* UPDATE LAST ACTIVITY */
    $_SESSION['last_activity'] = time();

    /* RETURN USER DETAILS */
    return [
        'user_id' => $userId,
        'username' => $username,
        'email' => $email,
        'role' => $role
    ];
}

/* 4. PROTECT RESTRICTED PAGES */
function requireAuthenticatedUser(
    array $allowedRoles = []
): void {

    $user = currentAppUser();

    /* NOT LOGGED IN */
    if ($user === null) {

        header('Location: login.html', true, 302);

        exit;
    }

    /* ROLE-BASED ACCESS CONTROL */
    if (
        $allowedRoles !== [] &&
        !in_array(
            $user['role'],
            $allowedRoles,
            true
        )
    ) {

        http_response_code(403);

        exit(
            'You are not authorized to access this page.'
        );
    }
}