<?php
session_start();

$reg_error = $_SESSION['reg_error'] ?? '';
$old       = $_SESSION['reg_old']   ?? [];
unset($_SESSION['reg_error'], $_SESSION['reg_old']);

$err_nic    = ($reg_error === 'nic');
$err_email  = ($reg_error === 'email');
$err_mobile = ($reg_error === 'mobile');

// Helper function to keep input values after a failed submission
function old($key, $old) {
    return htmlspecialchars($old[$key] ?? '');
}

$error_messages = [
    'nic'    => 'This NIC number is already registered.',
    'mobile' => 'This Mobile number is already registered.',
    'email'  => 'This Email address is already registered.',
    
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cricket Arena - Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="Assets/CSS/style.css">
    <style>
        .field-error { border-color: #ff4d4d !important; }
        .error-msg   { color: #ff4d4d; font-size: 11px; margin-top: 4px; display: flex; align-items: center; gap: 5px; }
        .alert-box   { background: rgba(255,77,77,0.08); border: 1px solid rgba(255,77,77,0.25); border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; color: #ff4d4d; font-size: 13px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main class="booking-section" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 80px); padding: 90px 20px 20px 20px;">
        <div class="modal-content" style="opacity: 1; pointer-events: auto; position: relative; max-width: 420px; padding: 20px 25px; text-align: left;">
            
            <div class="modal-header" style="text-align: center; margin-bottom: 12px;">
                <i class="fas fa-user-plus" style="color: #0073ff; font-size: 32px; margin-bottom: 8px;"></i>
                <h3 style="font-size: 20px; margin-bottom: 2px;">User Registration</h3>
                <p style="font-size: 13px; margin-bottom: 10px;">Create your account to book cricket slots</p>
            </div>

            <?php if ($reg_error && isset($error_messages[$reg_error])): ?>
                <div class="alert-box">
                    <i class="fas fa-circle-exclamation"></i>
                    <?= $error_messages[$reg_error] ?> Please use a different one.
                </div>
            <?php endif; ?>

            <form action="register_user.php" method="POST">
                
                <div class="input-group" style="margin-bottom: 12px;">
                    <label for="full_name" style="font-size: 11px; margin-bottom: 4px;">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required
                           value="<?= old('name', $old) ?>"
                           style="padding: 10px; font-size: 14px;">
                </div>

                <div class="input-group" style="margin-bottom: 12px;">
                    <label for="nic_number" style="font-size: 11px; margin-bottom: 4px;">NIC Number</label>
                    <input type="text" id="nic_number" name="nic_number" placeholder="e.g., 199912345678 or 991234567V" required
                           value="<?= old('nic', $old) ?>"
                           class="<?= $err_nic ? 'field-error' : '' ?>"
                           style="padding: 10px; font-size: 14px;">
                    <?php if ($err_nic): ?>
                        <p class="error-msg"></p>
                    <?php endif; ?>
                </div>

                <div class="input-group" style="margin-bottom: 12px;">
                    <label for="address" style="font-size: 11px; margin-bottom: 4px;">Address</label>
                    <textarea id="address" name="address" placeholder="Enter your address" required
                              style="width: 100%; padding: 10px; background-color: #0b0c10; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 4px; color: #ffffff; font-size: 14px; outline: none; resize: none; min-height: 55px; font-family: 'Inter', sans-serif;"><?= old('address', $old) ?></textarea>
                </div>

                <div class="input-group" style="margin-bottom: 12px;">
                    <label for="mobile" style="font-size: 11px; margin-bottom: 4px;">Mobile Number</label>
                    <input type="text" id="mobile" name="mobile" placeholder="e.g., 0771234567" required
                           value="<?= old('mobile', $old) ?>"
                           class="<?= $err_mobile ? 'field-error' : '' ?>"
                           style="padding: 10px; font-size: 14px;">
                    <?php if ($err_mobile): ?>
                        <p class="error-msg"></p>
                    <?php endif; ?>
                </div>

                <div class="input-group" style="margin-bottom: 16px;">
                    <label for="email" style="font-size: 11px; margin-bottom: 4px;">Your Email</label>
                    <input type="email" id="email" name="email" placeholder="e.g., name@example.com" required
                           value="<?= old('email', $old) ?>"
                           class="<?= $err_email ? 'field-error' : '' ?>"
                           style="padding: 10px; font-size: 14px;">
                    <?php if ($err_email): ?>
                        <p class="error-msg"></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="modal-btn success-btn" style="padding: 12px; font-size: 13px;">Register Account</button>
            </form>

            <div style="margin-top: 15px; font-size: 13px; color: #888888; text-align: center;">
                Already have an account? <a href="login.php" style="color: #0073ff; text-decoration: none; font-weight: 600;">Login Here</a>
            </div>

        </div>
    </main>

    <script>
        const textarea = document.getElementById('address');
        textarea.addEventListener('focus', () => textarea.style.borderColor = '#0073ff');
        textarea.addEventListener('blur', () => textarea.style.borderColor = 'rgba(255, 255, 255, 0.12)');
    </script>

</body>
</html>