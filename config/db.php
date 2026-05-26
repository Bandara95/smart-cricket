<?php
// Aiven හි දත්ත මෙතැනට පුරවන්න
$host = "mysql-3b20f171-bandarasamarakoon95-aad3.c.aivencloud.com";
$db = "defaultdb"; // Aiven එකේ database නම
$user = "avnadmin";
$pass = "AVNS_zgvqLQH5FNWEU7A4kHR"; // මෙතනට ඔබේ പാස්වර්ඩ් එක දෙන්න
$port = "14778";

try {
    // SSL අවශ්‍ය නිසා options එකතු කරන්න
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt', // මෙය ඇතැම් විට අවශ්‍ය විය හැක
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $conn = new PDO($dsn, $user, $pass, $options);
    
    $conn->exec("SET NAMES utf8mb4");
    
} catch(PDOException $e) {
    echo "Connection error: " . $e->getMessage();
}

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['user_id']) && isset($conn)) {
    $u_id = intval($_SESSION['user_id']);
    // $conn->query එක වෙනුවට prepare භාවිතා කිරීම වඩාත් ආරක්ෂිතයි
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $u_id]);
}
?>
