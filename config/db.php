<?php
// Render Environment Variable එකෙන් DATABASE_URL ගන්නවා
$db_url = getenv('DATABASE_URL');
if (!$db_url) {
    die("Database URL not found in environment variables.");
}

// URL parse කිරීම
$db_parts = parse_url($db_url);
$host = $db_parts['host'];
$db   = ltrim($db_parts['path'], '/');
$user = $db_parts['user'];
$pass = $db_parts['pass'];
$port = isset($db_parts['port']) ? $db_parts['port'] : 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Aiven SSL - CA certificate verify නොකර connect වෙනවා (Render environment)
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];

    $conn = new PDO($dsn, $user, $pass, $options);

} catch(PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
