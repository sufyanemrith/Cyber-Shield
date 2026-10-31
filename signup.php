<?php
declare(strict_types=1);

/* 1. USE POST TO SUBMIT REGISTRATION */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

/* 2. RECEIVE AND CHECK FORM VALUES */
foreach (['username', 'fullname', 'email', 'password'] as $field) {
    if (!isset($_POST[$field]) || !is_string($_POST[$field])) {
        http_response_code(400);
        exit('Invalid registration form.');
    }
}

$username = trim($_POST['username']);
$fullname = trim($_POST['fullname']);
$email = strtolower(trim($_POST['email']));
$password = $_POST['password'];
$terms = $_POST['terms'] ?? null;

/* Check fields before inserting into the database */
if (
    !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username) ||
    $fullname === '' ||
    strlen($fullname) > 100
) {
    http_response_code(422);
    exit('Invalid username or full name.');
}

if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    exit('Invalid email address.');
}

/* 3. ENFORCE THE SAME PASSWORD RULES ON THE SERVER */
if (
    strlen($password) < 8 ||
    strlen($password) > 72 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[@#$*&!]/', $password)
) {
    http_response_code(422);
    exit('Password does not meet the requirements.');
}

if ($terms !== 'on') {
    http_response_code(422);
    exit('You must accept the Terms and Conditions.');
}

/* 4. HASH PASSWORD AND SAVE USING A PREPARED STATEMENT */
try {
    require_once __DIR__ . '/db.php';

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO customer (username, name, email, password_hash)
         VALUES (?, ?, ?, ?)'
    );

    $stmt->execute([$username, $fullname, $email, $passwordHash]);

    header('Content-Type: text/html; charset=utf-8');
    echo 'Account created successfully. <a href="login.html">Log in</a>.';

} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        http_response_code(409);
        exit('Username or email already exists.');
    }

    error_log('iPHARMATIC signup database error: ' . $e->getMessage());
    http_response_code(500);
    exit('Registration failed. Please try again.');

} catch (Throwable $e) {
    error_log('iPHARMATIC signup error: ' . $e->getMessage());
    http_response_code(500);
    exit('Registration failed. Please try again.');
}
