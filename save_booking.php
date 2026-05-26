<?php
session_start();
include 'config/db.php';

// ── AUTH CHECK ───────────────────────────────────────────────────────────────
// FIX 1: auth_ping include කරනවා — logged-out users block කරනවා
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// FIX 2: Admin redirect
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: admin_dashboard.php?page=bookings");
    exit;
}

// POST method check
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: booking.php");
    exit;
}

// FIX 3: user_id POST ලා ගන්නේ නෑ — SESSION ලා ගන්නවා (IDOR fix)
$user_id      = $_SESSION['user_id'];
$booking_date = trim($_POST['booking_date'] ?? '');
$slot_time    = trim($_POST['slot_time']    ?? '');

// FIX 4: Input validation
$valid_slots = [
    "08:00 AM - 09:00 AM", "09:00 AM - 10:00 AM", "10:00 AM - 11:00 AM",
    "11:00 AM - 12:00 PM", "12:00 PM - 01:00 PM", "01:00 PM - 02:00 PM",
    "02:00 PM - 03:00 PM", "03:00 PM - 04:00 PM", "04:00 PM - 05:00 PM",
    "05:00 PM - 06:00 PM", "06:00 PM - 07:00 PM", "07:00 PM - 08:00 PM"
];

if (empty($booking_date) || empty($slot_time)) {
    die("Error: Date and slot are required.");
}

// Date format validate
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date) || !strtotime($booking_date)) {
    die("Error: Invalid date format.");
}

// Past date block
if ($booking_date < date('Y-m-d')) {
    die("Error: Cannot book past dates.");
}

// Slot whitelist check
if (!in_array($slot_time, $valid_slots)) {
    die("Error: Invalid slot selected.");
}

// 1. Check user is verified
$stmt = $conn->prepare("SELECT is_verified FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || $user['is_verified'] != 1) {
    echo "<h3>Error: Account not verified.</h3>";
    echo "<p>Please <a href='verify_otp.php'>verify your account</a> first.</p>";
    exit;
}

// 2. Check slot not already booked (double-booking prevention)
$check = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_date = ? AND slot_time = ?");
$check->execute([$booking_date, $slot_time]);
if ($check->fetch()) {
    echo "<h3>Error: This slot is already booked.</h3>";
    echo "<p><a href='booking.php?date={$booking_date}'>Choose another slot</a>.</p>";
    exit;
}

// 3. Save booking
try {
    $sql  = "INSERT INTO bookings (user_id, booking_date, slot_time) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $booking_date, $slot_time]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Success - Cricket Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="Assets/CSS/style.css">
    <meta http-equiv="refresh" content="3;url=dashboard.php">
</head>
<body>
    <main class="booking-section" style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
        <div class="modal-content" style="opacity: 1; max-width: 400px; padding: 40px 30px; text-align: center; border: 2px solid #0073ff; box-shadow: 0 0 25px rgba(0, 115, 255, 0.4); background: #161a20; border-radius: 16px;">
            <div style="margin-bottom: 20px;">
                <i class="fas fa-circle-check" style="font-size: 65px; color: #00e676; filter: drop-shadow(0 0 12px rgba(0, 230, 118, 0.6));"></i>
            </div>
            <h2 style="font-size: 24px; color: #ffffff; margin-bottom: 10px;">Booking Successful!</h2>
            <p style="color: #aaaaaa; font-size: 14px; line-height: 1.6; margin-bottom: 25px;">
                Your indoor cricket net slot has been successfully reserved.
            </p>
            <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                <i class="fas fa-spinner fa-spin" style="color: #0073ff; font-size: 20px;"></i>
                <span style="font-size: 12px; color: #666666; letter-spacing: 0.5px;">
                    Redirecting to Dashboard in <span id="countdown" style="color: #0073ff; font-weight: bold;">3</span> seconds...
                </span>
            </div>
        </div>
    </main>
    <script>
        let timeLeft = 3;
        const countdownTimer = setInterval(() => {
            timeLeft--;
            document.getElementById('countdown').textContent = timeLeft;
            if (timeLeft <= 0) clearInterval(countdownTimer);
        }, 1000);
    </script>
</body>
</html>
<?php
} catch (PDOException $e) {
    // FIX 5: Error details expose නොකරනවා
    error_log("Booking save error: " . $e->getMessage());
    echo "Error: Booking could not be saved. Please try again.";
}
?>