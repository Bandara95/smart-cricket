<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $conn;

$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin');
?>

<header class="arena-navbar-ford" style="padding: 15px 0; transition: background 0.4s ease, padding 0.4s ease; position: fixed; top: 0; width: 100%; z-index: 1000; background: #0b0c10;">
    <div class="navbar-container" style="display: grid; grid-template-columns: auto 1fr auto; align-items: center; max-width: 1450px; margin: 0 auto; padding: 0 40px;">

        <div class="arena-logo" style="color: #fff; font-weight: 800; font-size: 30px; margin-right: 50px;">
            <i class="fas fa-cricket-bat-ball" style="color: #0073ff;"></i> SMART<span>CRICKET</span>
        </div>

        <nav class="arena-nav" style="display: flex; gap: 40px; justify-content: center;">
            <!-- Close button (mobile only) -->
            <button class="mobile-nav-close" onclick="closeMobileMenu()" aria-label="Close menu">&times;</button>

            <a href="index.php" class="nav-item <?php echo ($current_page === 'index.php') ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px;" onclick="closeMobileMenu()">Home</a>

            <?php if ($is_admin): ?>
                <a href="admin_dashboard.php?page=bookings" class="nav-item <?php echo ($current_page === 'admin_dashboard.php' && isset($_GET['page']) && $_GET['page'] === 'bookings') ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px; color: #0073ff;" onclick="closeMobileMenu()">Book A Slot (Admin)</a>
                <a href="admin_dashboard.php?page=dashboard" class="nav-item <?php echo ($current_page === 'admin_dashboard.php' && (!isset($_GET['page']) || $_GET['page'] === 'dashboard')) ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px; color: #0073ff;" onclick="closeMobileMenu()">Admin Dashboard</a>
            <?php else: ?>
                <a href="booking.php" class="nav-item <?php echo ($current_page === 'booking.php') ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px;" onclick="closeMobileMenu()">Book A Slot</a>
                <a href="dashboard.php" class="nav-item <?php echo ($current_page === 'dashboard.php') ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px;" onclick="closeMobileMenu()">My Dashboard</a>
            <?php endif; ?>
        </nav>

        <div style="display: flex; align-items: center; gap: 14px; justify-self: end;">

            <div class="arena-auth">
                <?php if(isset($_SESSION['user_id']) && isset($conn)):
                    $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    $name = isset($user['full_name']) ? $user['full_name'] : 'User';
                ?>
                    <div style="display: flex; align-items: center; gap: 15px; color: #fff; font-size: 14px;">
                        <span class="nav-username" style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-user" style="color: #0073ff;"></i>
                            <span class="username-text"><?php echo htmlspecialchars($name); ?></span>
                        </span>
                        <a href="logout.php" style="color: #ff3d00; text-decoration: none; font-size: 14px; font-weight: 600;" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="user-menu" style="cursor: pointer; position: relative; padding: 10px 0;">
                        <div style="color: #fff; font-size: 14px; display: flex; align-items: center; gap: 10px;">
                            <i class="far fa-user" style="color: #0073ff;"></i>
                            <span class="account-label">Account</span>
                        </div>
                        <div class="user-dropdown">
                            <a href="login.php">Sign In</a>
                            <a href="register.php">Create Account</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Hamburger button (mobile only) -->
            <button class="hamburger" id="hamburgerBtn" onclick="toggleMobileMenu()" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

        </div>

    </div>
</header>

<style>
    .nav-item { color: #fff; transition: color 0.2s; }
    .nav-item:hover { color: #0073ff !important; }
    .nav-active { color: #0073ff !important; border-bottom: 2px solid #0073ff; }

    .mobile-nav-close { display: none; }

    .hamburger {
        display: none;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
        background: none;
        border: none;
        padding: 6px;
        z-index: 1100;
    }
    .hamburger span {
        display: block;
        width: 24px;
        height: 2px;
        background: #ffffff;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    .hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .hamburger.open span:nth-child(2) { opacity: 0; }
    .hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    .user-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
        padding: 10px 0;
        width: 150px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        z-index: 9999;
    }
    .user-menu:hover .user-dropdown { display: block; }
    .user-dropdown a { display: block; padding: 8px 20px; color: #fff; text-decoration: none; font-size: 14px; }
    .user-dropdown a:hover { background: #0073ff; color: #fff; }

    @media (max-width: 768px) {

        .arena-navbar-ford .navbar-container {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px !important;
        }

        .hamburger { display: flex; }

        .arena-logo { font-size: 18px !important; margin-right: 0 !important; }

        .mobile-nav-close {
            display: block;
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: #fff;
            font-size: 32px;
            cursor: pointer;
            z-index: 1060;
            line-height: 1;
        }

        .arena-nav {
            display: none !important;
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(11, 12, 16, 0.98);
            flex-direction: column !important;
            align-items: center;
            justify-content: center;
            gap: 32px !important;
            z-index: 1050;
        }

        .arena-nav.mobile-open {
            display: flex !important;
        }

        .arena-nav .nav-item {
            font-size: 22px !important;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .username-text { display: none; }
        .account-label { display: none; }
    }

    @media (max-width: 480px) {
        .arena-logo { font-size: 15px !important; }
    }
</style>

<script>
    function toggleMobileMenu() {
        const nav = document.querySelector('.arena-nav');
        const btn = document.getElementById('hamburgerBtn');
        nav.classList.toggle('mobile-open');
        btn.classList.toggle('open');
        document.body.style.overflow = nav.classList.contains('mobile-open') ? 'hidden' : '';
    }

    function closeMobileMenu() {
        const nav = document.querySelector('.arena-nav');
        const btn = document.getElementById('hamburgerBtn');
        nav.classList.remove('mobile-open');
        btn.classList.remove('open');
        document.body.style.overflow = '';
    }

    // Close menu on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeMobileMenu();
    });
</script>
