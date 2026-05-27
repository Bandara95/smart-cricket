<?php
$is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$is_secure,'httponly'=>true,'samesite'=>'Lax']);
session_start();
date_default_timezone_set('Asia/Colombo');
include 'config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// FIX: Mail credentials — env ලා / config file ලා ගන්නවා
if (file_exists('config/mail_config.php')) {
    include 'config/mail_config.php';
} else {
    define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: 'manathungamahaththaya@gmail.com');
    define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
    define('MAIL_FROM',     getenv('MAIL_FROM')     ?: 'manathungamahaththaya@gmail.com');
}

$user_id          = $_SESSION['temp_user_id'] ?? '';
$is_register_flow = isset($_SESSION['pending_user']);

// ── AJAX Resend OTP ───────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'resend') {

    header('Content-Type: application/json');

    // FIX: Resend rate limiting — session ලා resend count track කරනවා
    if (!isset($_SESSION['resend_count']))       $_SESSION['resend_count']      = 0;
    if (!isset($_SESSION['resend_locked_until'])) $_SESSION['resend_locked_until'] = 0;

    if (time() < $_SESSION['resend_locked_until']) {
        $wait = ceil(($_SESSION['resend_locked_until'] - time()) / 60);
        echo json_encode(['status' => 'error', 'message' => "Too many resend attempts. Wait {$wait} minute(s)."]);
        exit;
    }

    $_SESSION['resend_count']++;
    if ($_SESSION['resend_count'] > 3) {
        $_SESSION['resend_locked_until'] = time() + (10 * 60); // 10 min lock
        $_SESSION['resend_count']        = 0;
        echo json_encode(['status' => 'error', 'message' => 'Too many resend attempts. Locked for 10 minutes.']);
        exit;
    }

    $otp    = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    if (isset($_SESSION['pending_user'])) {
        $email = $_SESSION['pending_email'];
        $_SESSION['pending_otp']    = $otp;
        $_SESSION['pending_expiry'] = $expiry;
    } elseif (isset($_SESSION['temp_user_id'])) {
        $user_id = $_SESSION['temp_user_id'];
        $stmt    = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }
        $email = $user['email'];
        $conn->prepare("UPDATE users SET otp_code=?, otp_expiry=? WHERE user_id=?")
             ->execute([$otp, $expiry, $user_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
        exit;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom(MAIL_FROM, 'SmartCricket Arena');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your New OTP Code - SmartCricket';

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

        $mail->AltBody = 'Your requested fresh security authorization access code is: ' . $otp;
        $mail->send();

        echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully']);

    } catch (Exception $e) {
        error_log("Resend OTP error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP. Please try again.']);
    }

    exit;
}

// Session check
if (empty($user_id) && !$is_register_flow) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - SmartCricket Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="Assets/CSS/style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #0b0c10; margin: 0; padding: 20px;">

    <div class="modal-content" style="opacity: 1; pointer-events: auto; position: relative; max-width: 420px; width: 100%; padding: 35px 30px; text-align: left; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
        
        <div class="modal-header" style="text-align: center; margin-bottom: 25px;">
            <i class="fas fa-key" style="font-size: 36px; margin-bottom: 12px; color: #0073ff; filter: drop-shadow(0 0 10px rgba(0, 115, 255, 0.3));"></i>
            <h3 style="font-size: 22px; font-weight: 800; margin-bottom: 6px; color: #ffffff; letter-spacing: 0.5px;">Security Verification</h3>
            <p style="font-size: 13px; color: #888888; line-height: 1.5; margin: 0;">Please enter the One-Time Password (OTP) sent to your registered contact channel.</p>
        </div>

        <div id="resendStatus" style="display: none; font-size: 13px; padding: 10px 12px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: 500;"></div>

        <form action="check_otp.php" method="POST" id="otpForm">
            <div class="input-group" style="margin-bottom: 20px;">
                <label for="otp_input" style="font-size: 11px; margin-bottom: 6px; text-transform: uppercase; color: #aaaaaa; font-weight: 700; letter-spacing: 0.5px; display: block;">
                    <i class="fas fa-shield-alt" style="color: #0073ff; margin-right: 5px;"></i> Enter Your OTP Code
                </label>
                <input type="text" id="otp_input" name="otp_input" placeholder="eg: 123456" autocomplete="off" maxlength="6" required
                       inputmode="numeric" pattern="\d{6}"
                       style="width: 100%; padding: 12px 16px; font-size: 15px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 6px; color: #ffffff; transition: 0.25s; text-align: center; font-family: monospace; letter-spacing: 4px; font-weight: 600;">
            </div>

            <button type="submit" class="modal-btn success-btn"
                    style="width: 100%; padding: 12px; font-size: 14px; font-weight: 600; background: #0073ff; border: 1px solid #0073ff; color: #ffffff; border-radius: 6px; cursor: pointer; transition: all 0.25s ease;">
                Verify & Authenticate
            </button>
        </form>

        <div style="text-align: center; margin-top: 25px; font-size: 13px; color: #888888;">
            <span>Didn't receive the code or expired? </span><br style="margin-bottom: 5px;">
            <button type="button" id="resendBtn" style="background: none; border: none; color: #0073ff; font-weight: 600; cursor: pointer; padding: 0; font-size: 13px; transition: 0.2s; text-decoration: underline;">
                <i class="far fa-paper-plane" style="margin-right: 4px;"></i> Request New OTP Code
            </button>
        </div>

        <div style="text-align: center; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
            <a href="login.php" style="font-size: 12px; color: #666666; text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#666666'">
                <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Back to Login Page
            </a>
        </div>
    </div>

    <script>
        const otpInput   = document.getElementById('otp_input');
        const resendBtn  = document.getElementById('resendBtn');
        const resendStatus = document.getElementById('resendStatus');

        otpInput.addEventListener('focus', () => {
            otpInput.style.borderColor       = '#0073ff';
            otpInput.style.backgroundColor   = 'rgba(0, 115, 255, 0.02)';
            otpInput.style.boxShadow         = '0 0 10px rgba(0, 115, 255, 0.15)';
        });
        otpInput.addEventListener('blur', () => {
            otpInput.style.borderColor       = 'rgba(255, 255, 255, 0.08)';
            otpInput.style.backgroundColor   = 'rgba(255, 255, 255, 0.03)';
            otpInput.style.boxShadow         = 'none';
        });

        // Digits only
        otpInput.addEventListener('input', () => {
            otpInput.value = otpInput.value.replace(/\D/g, '');
        });

        resendBtn.addEventListener('click', function () {
            resendBtn.disabled    = true;
            resendBtn.style.opacity = '0.5';
            resendBtn.innerHTML   = '<i class="fas fa-spinner fa-spin"></i> Sending email...';
            resendStatus.style.display = 'none';

            fetch('verify_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=resend'
            })
            .then(r => r.json())
            .then(data => {
                resendStatus.style.display = 'block';
                if (data.status === 'success') {
                    resendStatus.style.background = 'rgba(0, 230, 118, 0.1)';
                    resendStatus.style.border      = '1px solid rgba(0, 230, 118, 0.2)';
                    resendStatus.style.color       = '#00e676';
                    resendStatus.innerHTML         = '<i class="fas fa-check-circle"></i> ' + data.message;

                    otpInput.value = '';

                    let countdown = 30;
                    const interval = setInterval(() => {
                        countdown--;
                        if (countdown <= 0) {
                            clearInterval(interval);
                            resendBtn.disabled      = false;
                            resendBtn.style.opacity = '1';
                            resendBtn.innerHTML     = '<i class="far fa-paper-plane" style="margin-right: 4px;"></i> Request New OTP Code';
                        } else {
                            resendBtn.innerHTML = `<i class="far fa-clock"></i> Wait ${countdown}s to retry`;
                        }
                    }, 1000);
                } else {
                    resendStatus.style.background = 'rgba(255, 61, 0, 0.1)';
                    resendStatus.style.border      = '1px solid rgba(255, 61, 0, 0.2)';
                    resendStatus.style.color       = '#ff3d00';
                    resendStatus.innerHTML         = '<i class="fas fa-exclamation-circle"></i> ' + data.message;

                    resendBtn.disabled      = false;
                    resendBtn.style.opacity = '1';
                    resendBtn.innerHTML     = '<i class="far fa-paper-plane" style="margin-right: 4px;"></i> Request New OTP Code';
                }
            })
            .catch(() => {
                resendStatus.style.display    = 'block';
                resendStatus.style.background = 'rgba(255, 61, 0, 0.1)';
                resendStatus.style.border      = '1px solid rgba(255, 61, 0, 0.2)';
                resendStatus.style.color       = '#ff3d00';
                resendStatus.innerHTML         = '<i class="fas fa-wifi"></i> Network error occurred. Please try again.';

                resendBtn.disabled      = false;
                resendBtn.style.opacity = '1';
                resendBtn.innerHTML     = '<i class="far fa-paper-plane" style="margin-right: 4px;"></i> Request New OTP Code';
            });
        });
    </script>

</body>
</html>
