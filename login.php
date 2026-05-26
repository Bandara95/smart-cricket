<?php
// secure=false for localhost (HTTP). Change to true on production HTTPS server.
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
include 'config/db.php';

/*
|--------------------------------------------------------------------------
| IMPORTANT FIX
|--------------------------------------------------------------------------
| කලින් තිබ්බ unset($_SESSION['user_id']);
| line එක අයින් කරලා තියෙන්නේ.
| ඒක නිසා OTP verify උනාට session delete වෙලා login page එකට යන issue එක fix වෙනවා.
|--------------------------------------------------------------------------
*/

// Already logged-in users redirect
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {

    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }

    exit;
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF Check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid request");
    }

    if (isset($_POST['nic_number'])) {

        $nic = trim($_POST['nic_number']);

        $stmt = $conn->prepare("SELECT user_id, is_admin, full_name FROM users WHERE nic_number = ?");
        $stmt->execute([$nic]);

        $user = $stmt->fetch();

        if ($user) {

            session_regenerate_id(true);

            // Temporary session data before OTP verification
            $_SESSION['nic_number']   = $nic;
            $_SESSION['temp_user_id'] = $user['user_id'];

            // Admin data
           $_SESSION['nic_number']   = $nic;
$_SESSION['temp_user_id'] = $user['user_id'];

// optional (safe to keep for UI only, NOT security)
$_SESSION['admin_name'] = $user['full_name'];

            header("Location: send_otp.php");
            exit;

        } else {

            $_SESSION['login_error'] = "NIC number is not registered! Please Register first.";
            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - SmartCricket Arena</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<?php include 'includes/header.php'; ?>

<main class="booking-section"
      style="display:flex;
             justify-content:center;
             align-items:center;
             min-height:calc(100vh - 80px);
             padding:80px 20px;">

    <div class="modal-content"
         style="max-width:380px;
                width:100%;
                padding:30px;
                background:rgba(255,255,255,0.02);
                border-radius:12px;
                border:1px solid rgba(255,255,255,0.1);">

        <div class="modal-header"
             style="text-align:center;
                    margin-bottom:20px;">

            <i class="fas fa-user-lock"
               style="font-size:32px;
                      margin-bottom:10px;
                      color:#0073ff;"></i>

            <h3 style="color:#fff;">User Login</h3>
        </div>

        <?php if ($error_message): ?>

            <div style="color:#ff3d00;
                        background:rgba(255,61,0,0.1);
                        padding:10px;
                        border-radius:6px;
                        margin-bottom:15px;
                        font-size:13px;
                        text-align:center;">

                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>

            </div>

        <?php endif; ?>

        <form action="login.php" method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="input-group" style="margin-bottom:15px;">

                <label style="color:#888;
                              font-size:12px;">
                    NIC Number
                </label>

                <input type="text"
                       name="nic_number"
                       placeholder="Enter NIC"
                       required

                       style="width:100%;
                              padding:12px;
                              background:#1a1a1a;
                              border:1px solid <?= $error_message ? '#ff3d00' : '#333' ?>;
                              color:#fff;
                              border-radius:6px;
                              outline:none;">

            </div>

            <button type="submit"
                    style="width:100%;
                           padding:12px;
                           background:#0073ff;
                           color:#fff;
                           border:none;
                           border-radius:6px;
                           cursor:pointer;
                           font-weight:600;">

                Login

            </button>

        </form>

        <div style="margin-top:20px;
                    text-align:center;
                    font-size:13px;
                    color:#888;">

            Don't have an account?

            <a href="register.php"
               style="color:#0073ff;
                      text-decoration:none;">

                Register Here

            </a>

        </div>

    </div>

</main>

</body>
</html>
