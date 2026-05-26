<?php
$host = "mysql-3b20f171-bandarasamarakoon95-aad3.c.aivencloud.com";
$db = "defaultdb";
$user = "avnadmin";
$pass = "AVNS_zgvqLQH5FNWEU7A4kHR";
$port = "14778";

try {
    // SSL සම්බන්ධතාවය DS එක තුළම අර්ථ දක්වන්න
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4;sslmode=required";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $conn = new PDO($dsn, $user, $pass, $options);
    $conn->exec("SET NAMES utf8mb4");
    
} catch(PDOException $e) {
    // දෝෂය පෙන්වීමට die() භාවිතා කරන්න, එවිට අපට ගැටලුව බලාගත හැක
    die("Database Connection Error: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['user_id']) && isset($conn)) {
    $u_id = intval($_SESSION['user_id']);
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $u_id]);
}
?>
