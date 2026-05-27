<?php
$is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$is_secure,'httponly'=>true,'samesite'=>'Lax']);
session_start();

date_default_timezone_set('Asia/Colombo');
include 'config/db.php';



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

// ── Brevo API ලා OTP Email යවනවා ─────────────────────────────────────────────
$api_key   = getenv('BREVO_API_KEY') ?: '';
$mail_from = getenv('MAIL_FROM')     ?: 'manathungamahaththaya@gmail.com';

$html = "
<div style='background-color:#050505;padding:40px;font-family:Arial,sans-serif;text-align:center;'>
    <div style='max-width:500px;margin:0 auto;background:#0f111a;border:1px solid #0073ff;border-radius:10px;padding:40px 30px;'>
        <h2 style='color:#0073ff;font-size:26px;font-weight:800;margin-bottom:5px;'>SmartCricket Arena</h2>
        <p style='color:#888;font-size:14px;margin-top:0;margin-bottom:25px;'>Security Verification Access</p>
        <hr style='border:0;border-top:1px solid rgba(255,255,255,0.05);margin-bottom:25px;'>
        <p style='color:#fff;font-size:15px;font-weight:500;line-height:1.6;margin-bottom:25px;'>Your OTP code is:</p>
        <div style='display:inline-block;border:1px solid #0073ff;border-radius:6px;padding:15px 35px;margin-bottom:30px;'>
            <span style='color:#fff;font-size:32px;font-weight:800;letter-spacing:6px;font-family:monospace;'>{$otp}</span>
        </div>
        <p style='color:#666;font-size:12px;margin-top:20px;'>This OTP will expire in 10 minutes.</p>
    </div>
</div>";

$data = [
    'sender'      => ['name' => 'SmartCricket Arena', 'email' => $mail_from],
    'to'          => [['email' => $email]],
    'subject'     => 'Your OTP Code - SmartCricket',
    'htmlContent' => $html,
];

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'api-key: ' . $api_key,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 201) {
    header("Location: verify_otp.php");
    exit;
} else {
    error_log("Brevo send error: " . $response);
    die("Error: OTP could not be sent. Please try again.");
}
?>
