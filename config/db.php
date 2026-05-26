<?php
$host = "mysql-3b20f171-bandarasamarakoon95-aad3.c.aivencloud.com";
$db = "defaultdb";
$user = "avnadmin";
$pass = "AVNS_zgvqLQH5FNWEU7A4kHR";
$port = "14778";

try {
    // කිසිදු SSL Constant එකක් භාවිතා නොකර සාමාන්‍ය සම්බන්ධතාවයක් ගොඩනගමු
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $conn = new PDO($dsn, $user, $pass, $options);
    
    // සම්බන්ධතාවය ගොඩනැගුණු පසු SSL සක්‍රීය කරමු
    // මෙය MySQL ධාවනය වන විට SSL ඉල්ලා සිටින ප්‍රබල ක්‍රමයකි
    $conn->exec("SET SESSION ssl_mode = 'REQUIRED'");
    
    $conn->exec("SET NAMES utf8mb4");
    
} catch(PDOException $e) {
    error_log("Connection error: " . $e->getMessage());
    die("Database connection failed.");
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['user_id']) && isset($conn)) {
    $u_id = intval($_SESSION['user_id']);
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $u_id]);
}
?>
