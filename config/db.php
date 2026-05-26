<?php
// Aiven දත්ත
$host = "mysql-3b20f171-bandarasamarakoon95-aad3.c.aivencloud.com";
$db = "defaultdb";
$user = "avnadmin";
$pass = "AVNS_zgvqLQH5FNWEU7A4kHR";
$port = "14778";

try {
    // SSL සක්‍රීය කිරීමට මෙන්න මේ කොටස නිවැරදිව පාවිච්චි කරන්න
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_MODE     => PDO::MYSQL_SSL_MODE_REQUIRED
    ];

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $conn = new PDO($dsn, $user, $pass, $options);
    
    $conn->exec("SET NAMES utf8mb4");
    
} catch(PDOException $e) {
    // දෝෂයක් ආවොත් පෙන්වන්න එපා, log එකකට පමණක් ගන්න
    error_log("Connection error: " . $e->getMessage());
    die("Database connection failed."); // මෙතන echo වෙනුවට die පාවිච්චි කරන්න
}

// Session එක ආරම්භ කිරීම
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// දත්ත යාවත්කාලීන කිරීම (ආරක්ෂිතව)
if (isset($_SESSION['user_id']) && isset($conn)) {
    $u_id = intval($_SESSION['user_id']);
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $u_id]);
}
?>
