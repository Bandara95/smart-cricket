<?php
session_start();
include 'config/db.php';

date_default_timezone_set('Asia/Colombo');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name    = trim($_POST['full_name']);
    $nic     = trim($_POST['nic_number']);
    $address = trim($_POST['address']);
    $mobile  = trim($_POST['mobile']);
    $email   = trim($_POST['email']);

    $old = compact('name', 'nic', 'address', 'mobile', 'email');

    // ── Duplicate Check ─────────────────────────────────────────────
    $check = $conn->prepare("SELECT user_id FROM users WHERE nic_number = ?");
    $check->execute([$nic]);
    if ($check->fetch()) {
        $_SESSION['reg_error'] = 'nic';
        $_SESSION['reg_old']   = $old;
        header("Location: register.php");
        exit;
    }

    $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $_SESSION['reg_error'] = 'email';
        $_SESSION['reg_old']   = $old;
        header("Location: register.php");
        exit;
    }

    $check = $conn->prepare("SELECT user_id FROM users WHERE mobile_number = ?");
    $check->execute([$mobile]);
    if ($check->fetch()) {
        $_SESSION['reg_error'] = 'mobile';
        $_SESSION['reg_old']   = $old;
        header("Location: register.php");
        exit;
    }

    // ── INSERT නොකර Session ලා data save කරනවා ─────────────────────
    // OTP verify වෙලාට check_otp.php ලා INSERT වෙනවා
    $_SESSION['pending_user'] = [
        'name'    => $name,
        'nic'     => $nic,
        'address' => $address,
        'mobile'  => $mobile,
        'email'   => $email,
    ];

    // send_otp.php ලා email එක ගන්න temp email session set කරනවා
    $_SESSION['pending_email'] = $email;

    header("Location: send_otp.php");
    exit;

} else {
    header("Location: register.php");
    exit;
}
?>