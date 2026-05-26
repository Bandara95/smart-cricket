<?php
session_start();
include 'config/db.php';

// ── AUTH CHECK ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// FIX 1: GET request ලා delete කරන්නේ නෑ — POST method only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

// FIX 2: CSRF token validate කරනවා
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    header("Location: admin_dashboard.php?error=invalid_request");
    exit;
}

// FIX 3: booking_id validate කරනවා
if (!isset($_POST['booking_id']) || !is_numeric($_POST['booking_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$booking_id = intval($_POST['booking_id']);

if ($booking_id <= 0) {
    header("Location: admin_dashboard.php");
    exit;
}

// Delete booking
$stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
$stmt->execute([$booking_id]);

header("Location: admin_dashboard.php?page=bookings&deleted=1");
exit;
?>