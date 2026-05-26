<?php
// Render එකේදී Environment Variable එක හරහා සම්බන්ධ වීම
$db_url = getenv('DATABASE_URL');

if (!$db_url) {
    die("Database URL not found in environment variables.");
}

// URL එක parse කිරීම (parse_url function එකෙන් mysql://... වෙන් කරගන්නවා)
$db_parts = parse_url($db_url);

$host = $db_parts['host'];
$db   = ltrim($db_parts['path'], '/');
$user = $db_parts['user'];
$pass = $db_parts['pass'];
$port = $db_parts['port'];

try {
    // SSL අවශ්‍ය නිසා options වලට SSL සෙටින්ග්ස් එකතු කළ යුතුයි
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_SSL_MODE     => PDO::MYSQL_ATTR_SSL_CA // Aiven සඳහා SSL අවශ්‍යයි
    ];

    $conn = new PDO($dsn, $user, $pass, $options);
    
} catch(PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
