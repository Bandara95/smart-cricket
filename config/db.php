<?php
$host = "localhost";
$db = "cricket_arena";
$user = "root";
$pass = ""; // Leave empty if you haven't set a MySQL password

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    
    // Set error mode to exception for better debugging
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set encoding to utf8 to support Sinhala characters
    $conn->exec("SET NAMES utf8");
    
} catch(PDOException $e) {
    echo "Connection error: " . $e->getMessage();
}
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (isset($_SESSION['user_id']) && isset($conn)) {
    $u_id = intval($_SESSION['user_id']);
    $conn->query("UPDATE users SET last_activity = NOW() WHERE user_id = $u_id");
}
?>
