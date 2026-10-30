<?php
declare(strict_types=1);

/* 1. SHARED DATABASE CONNECTION */
function connectDatabase(): PDO
{
    $host = "127.0.0.1";
    $port = 3306;
    $dbname = "ipharmatic_database";
    $username = "root";
    $password = "";

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Use UTC for consistent timestamps.
    $pdo->exec("SET time_zone = '+00:00'");

    return $pdo;
}

/* 2. PROVIDE THE PDO VARIABLE TO SIGNUP AND LOGIN */
// Both signup.php and login.php can use $pdo directly.
$pdo = connectDatabase();