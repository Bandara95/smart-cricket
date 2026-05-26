<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Not logged in — redirect to login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// Update last activity — only if db connection exists
if (isset($conn)) {
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ? AND is_verified = 1");
    $stmt->execute([$_SESSION['user_id']]);
}
?>