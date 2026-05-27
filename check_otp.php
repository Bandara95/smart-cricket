<?php
$is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$is_secure,'httponly'=>true,'samesite'=>'Lax']);
session_start();
date_default_timezone_set('Asia/Colombo');
include 'config/db.php';

$error_message = "";
$success       = false;
$redirect_page = "booking.php";

if (!isset($_SESSION['temp_user_id']) && !isset($_SESSION['pending_user'])) {
    header("Location: login.php");
    exit;
}

$otp_input = trim($_POST['otp_input'] ?? '');

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit;
}

// ── FIX: OTP Brute-Force Rate Limiting ──────────────────────────────────────
// Session ලා attempt count track කරනවා
if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts']    = 0;
    $_SESSION['otp_locked_until'] = 0;
}

// Lock check — locked ද?
if (time() < $_SESSION['otp_locked_until']) {
    $wait = ceil(($_SESSION['otp_locked_until'] - time()) / 60);
    $error_message = "Too many failed attempts. Please wait {$wait} minute(s) and try again.";
    // Error page render කරලා exit
    goto render;
}

// ── Validation ───────────────────────────────────────────────────────────────
if (empty($otp_input)) {
    $error_message = "All validation fields are required.";
} elseif (!preg_match('/^\d{6}$/', $otp_input)) {
    $error_message = "OTP must be a 6-digit number.";
} else {

    // ── REGISTER FLOW ────────────────────────────────────────────────────────
    if (isset($_SESSION['pending_user'])) {

        $current_time   = date("Y-m-d H:i:s");
        $pending_otp    = $_SESSION['pending_otp']    ?? '';
        $pending_expiry = $_SESSION['pending_expiry'] ?? '';

        if (
            !empty($pending_otp) &&
            hash_equals((string)$pending_otp, $otp_input) &&
            $current_time <= $pending_expiry
        ) {
            // ✅ OTP හරි — reset attempts
            $_SESSION['otp_attempts']     = 0;
            $_SESSION['otp_locked_until'] = 0;

            $u = $_SESSION['pending_user'];

            try {
                $stmt = $conn->prepare("INSERT INTO users (full_name, nic_number, address, mobile_number, email, is_verified) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$u['name'], $u['nic'], $u['address'], $u['mobile'], $u['email']]);

                $new_user_id = $conn->lastInsertId();

                unset($_SESSION['pending_user'], $_SESSION['pending_email'], $_SESSION['pending_otp'], $_SESSION['pending_expiry']);
                unset($_SESSION['otp_attempts'], $_SESSION['otp_locked_until']);

                session_regenerate_id(true);
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id']   = $new_user_id;
                $_SESSION['is_admin']  = false;
                $_SESSION['user_role'] = 'user';

                $success       = true;
                $redirect_page = "booking.php";

            } catch (PDOException $e) {
                error_log("Registration error: " . $e->getMessage());
                $error_message = "Registration failed. Please try again.";
            }

        } else {
            // ❌ Wrong OTP — increment attempts
            $_SESSION['otp_attempts']++;
            if ($_SESSION['otp_attempts'] >= 5) {
                $_SESSION['otp_locked_until'] = time() + (5 * 60); // 5 minutes lock
                $_SESSION['otp_attempts']     = 0;
                $error_message = "Too many failed attempts. Locked for 5 minutes.";
            } else {
                $remaining     = 5 - $_SESSION['otp_attempts'];
                $error_message = "Invalid or expired OTP code. {$remaining} attempt(s) remaining.";
            }
        }

    // ── LOGIN FLOW ───────────────────────────────────────────────────────────
    } elseif (isset($_SESSION['temp_user_id'])) {

        $user_id = $_SESSION['temp_user_id'];

        $stmt = $conn->prepare("SELECT otp_code, otp_expiry FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        $current_time = date("Y-m-d H:i:s");

        if (
            $user &&
            !empty($user['otp_code']) &&
            hash_equals($user['otp_code'], $otp_input) &&
            $current_time <= $user['otp_expiry']
        ) {
            // ✅ OTP හරි — reset attempts
            $_SESSION['otp_attempts']     = 0;
            $_SESSION['otp_locked_until'] = 0;

            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id']   = $user_id;
            $success               = true;

            unset($_SESSION['temp_user_id']);
            unset($_SESSION['otp_attempts'], $_SESSION['otp_locked_until']);

            $conn->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expiry = NULL WHERE user_id = ?")
                 ->execute([$user_id]);

            $role_stmt = $conn->prepare("SELECT is_admin FROM users WHERE user_id = ?");
            $role_stmt->execute([$user_id]);
            $role = $role_stmt->fetch();

            if ($role && $role['is_admin'] == 1) {
                $_SESSION['is_admin']  = true;
                $_SESSION['user_role'] = 'admin';
                $redirect_page         = "admin_dashboard.php";
            } else {
                $_SESSION['is_admin']  = false;
                $_SESSION['user_role'] = 'user';

                $booking_stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
                $booking_stmt->execute([$user_id]);
                $redirect_page = ($booking_stmt->fetchColumn() > 0) ? "dashboard.php" : "booking.php";
            }

        } else {
            // ❌ Wrong OTP — increment attempts
            $_SESSION['otp_attempts']++;
            if ($_SESSION['otp_attempts'] >= 5) {
                $_SESSION['otp_locked_until'] = time() + (5 * 60);
                $_SESSION['otp_attempts']     = 0;
                $error_message = "Too many failed attempts. Locked for 5 minutes.";
            } else {
                $remaining     = 5 - $_SESSION['otp_attempts'];
                $error_message = "Invalid or expired OTP code. {$remaining} attempt(s) remaining.";
            }
        }
    }
}

render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Verification - SmartCricket Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="Assets/CSS/style.css">
    <?php if ($success): ?>
        <meta http-equiv="refresh" content="3;url=<?php echo $redirect_page; ?>">
    <?php endif; ?>
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #0b0c10; margin: 0; padding: 20px;">
    <div class="modal-content" style="opacity: 1; pointer-events: auto; position: relative; max-width: 440px; width: 100%; padding: 40px 30px; text-align: center; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
        <?php if ($success): ?>
            <i class="fas fa-circle-check" style="font-size: 50px; color: #00e676; margin-bottom: 20px; filter: drop-shadow(0 0 10px rgba(0, 230, 118, 0.3));"></i>
            <h3 style="font-size: 22px; font-weight: 800; color: #ffffff; margin-bottom: 10px;">Verification Successful!</h3>
            <p style="font-size: 14px; color: #aaaaaa; line-height: 1.6; margin-bottom: 25px;">Your identity has been authenticated. Secure session generated successfully.</p>
            <div style="display: inline-flex; align-items: center; gap: 10px; background: rgba(0, 115, 255, 0.1); padding: 10px 20px; border-radius: 50px; border: 1px solid rgba(0, 115, 255, 0.2);">
                <i class="fas fa-spinner fa-spin" style="color: #0073ff;"></i>
                <span style="font-size: 13px; font-weight: 600; color: #ffffff;">
                    Redirecting to <?php echo ($redirect_page === "admin_dashboard.php") ? "Admin Panel..." : "your destination..."; ?>
                </span>
            </div>
        <?php else: ?>
            <i class="fas fa-circle-exclamation" style="font-size: 50px; color: #ff3d00; margin-bottom: 20px; filter: drop-shadow(0 0 10px rgba(255, 61, 0, 0.3));"></i>
            <h3 style="font-size: 22px; font-weight: 800; color: #ffffff; margin-bottom: 10px;">Verification Failed</h3>
            <p style="font-size: 14px; color: #ff8a80; line-height: 1.6; background: rgba(255, 61, 0, 0.05); padding: 12px; border-radius: 6px; border: 1px solid rgba(255, 61, 0, 0.15); margin-bottom: 25px;">
                <?php echo htmlspecialchars($error_message); ?>
            </p>
            <a href="verify_otp.php" style="display: block; width: 100%; padding: 12px; font-size: 13px; font-weight: 600; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: #ffffff; border-radius: 6px; text-decoration: none; cursor: pointer; transition: 0.25s;"
               onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.04)'">
                <i class="fas fa-rotate-left" style="margin-right: 6px;"></i> Try Entering Code Again
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
