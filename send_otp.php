<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false, // production ලා true කරන්න (HTTPS)
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

date_default_timezone_set('Asia/Colombo');
include 'config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$otp    = rand(100000, 999999);
$expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// ── REGISTER FLOW ─────────────────────────────────────────────────────────────
if (isset($_SESSION['pending_user'])) {

    $email = $_SESSION['pending_email'];
    $_SESSION['pending_otp']    = $otp;
    $_SESSION['pending_expiry'] = $expiry;

// ── LOGIN FLOW ────────────────────────────────────────────────────────────────
} elseif (isset($_SESSION['temp_user_id'])) {

    $user_id = $_SESSION['temp_user_id'];

    $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Error: User not found.");
    }

    $email = $user['email'];

    $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE user_id = ?")
         ->execute([$otp, $expiry, $user_id]);

} else {
    header("Location: login.php");
    exit;
}

// ── FIX: SMTP credentials — env variable ලා ගන්නවා (hardcode නෑ) ─────────────
// config/mail_config.php file ලා credentials දාන්න (web root outside)
// නැත්නම් $_ENV / getenv() use කරන්න
if (file_exists('config/mail_config.php')) {
    include 'config/mail_config.php';
} else {
    // Fallback: env variable ලා ගන්නවා
    define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: 'manathungamahaththaya@gmail.com');
    define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
    define('MAIL_FROM',     getenv('MAIL_FROM')     ?: 'manathungamahaththaya@gmail.com');
}

// ── OTP Email යවනවා ───────────────────────────────────────────────────────────
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom(MAIL_FROM, 'Cricket Arena');
    $mail->addAddress($email);
    $mail->Subject = 'Your New OTP Code - SmartCricket';
    $mail->isHTML(true);

    $mail->Body = "
    <div style='background-color: #050505; padding: 40px; font-family: \"Inter\", sans-serif; text-align: center;'>
        <div style='max-width: 500px; margin: 0 auto; background: #0f111a; border: 1px solid #0073ff; border-radius: 10px; padding: 40px 30px; box-shadow: 0 4px 20px rgba(0, 115, 255, 0.15);'>
            <h2 style='color: #0073ff; font-size: 26px; font-weight: 800; margin-bottom: 5px; letter-spacing: 0.5px;'>SmartCricket Arena</h2>
            <p style='color: #888888; font-size: 14px; margin-top: 0; margin-bottom: 25px;'>Security Verification Access</p>
            <hr style='border: 0; border-top: 1px solid rgba(255, 255, 255, 0.05); margin-bottom: 25px;'>
            <p style='color: #ffffff; font-size: 15px; font-weight: 500; line-height: 1.6; margin-bottom: 25px;'>
                Your requested fresh security authorization access code is:
            </p>
            <div style='display: inline-block; background: rgba(255, 255, 255, 0.02); border: 1px solid #0073ff; border-radius: 6px; padding: 15px 35px; margin-bottom: 30px;'>
                <span style='color: #ffffff; font-size: 32px; font-weight: 800; letter-spacing: 6px; font-family: monospace;'>{$otp}</span>
            </div>
            <p style='color: #666666; font-size: 12px; margin-top: 20px; line-height: 1.5;'>
                This secure OTP parameter will strictly expire in 10 minutes.
            </p>
        </div>
    </div>
    ";

    $mail->AltBody = 'Your OTP code is: ' . $otp;
    $mail->send();

    header("Location: verify_otp.php");
    exit;

} catch (Exception $e) {
    error_log("Mail send error: " . $mail->ErrorInfo);
    die("Error: OTP could not be sent. Please try again.");
}
?>
