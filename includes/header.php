<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $conn;

// Detect the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Create a helper variable to easily check if the logged-in user is an Admin
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin');
?>

<header class="arena-navbar-ford" style="padding: 15px 0; transition: background 0.4s ease, padding 0.4s ease; position: fixed; top: 0; width: 100%; z-index: 1000; background: #0b0c10;">
    <div class="navbar-container" style="display: grid; grid-template-columns: auto 1fr auto; align-items: center; max-width: 1450px; margin: 0 auto; padding: 0 40px;">
        
        <div class="arena-logo" style="color: #fff; font-weight: 800; font-size: 30px; margin-right: 50px;">
            <i class="fas fa-cricket-bat-ball" style="color: #0073ff;"></i> SMART<span>CRICKET</span>
        </div>
        
        <nav class="arena-nav" style="display: flex; gap: 40px; justify-content: center;">
            <a href="index.php" class="nav-item <?php echo ($current_page === 'index.php') ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px;">Home</a>
            
            <?php if ($is_admin): ?>
                <a href="admin_dashboard.php?page=bookings" class="nav-item <?php echo ($current_page === 'admin_dashboard.php' && isset($_GET['page']) && $_GET['page'] === 'bookings') ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px; color: #0073ff;">Book A Slot (Admin)</a>
                
                <a href="admin_dashboard.php?page=dashboard" class="nav-item <?php echo ($current_page === 'admin_dashboard.php' && (!isset($_GET['page']) || $_GET['page'] === 'dashboard')) ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px; color: #0073ff;">Admin Dashboard</a>
            <?php else: ?>
                <a href="booking.php" class="nav-item <?php echo ($current_page === 'booking.php') ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px;">Book A Slot</a>
                <a href="dashboard.php" class="nav-item <?php echo ($current_page === 'dashboard.php') ? 'nav-active' : ''; ?>" style="text-decoration: none; font-size: 14px;">My Dashboard</a>
            <?php endif; ?>
        </nav>

        <div class="arena-auth" style="display: flex; align-items: center; justify-self: end;">
    <?php if(isset($_SESSION['user_id']) && isset($conn)): 
        // If logged in: Display name and Logout button directly
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $name = isset($user['full_name']) ? $user['full_name'] : 'User';
    ?>
        <div style="display: flex; align-items: center; gap: 15px; color: #fff; font-size: 14px;">
            <span style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-user" style="color: #0073ff;"></i> <?php echo htmlspecialchars($name); ?>
            </span>
            <a href="logout.php" style="color: #ff3d00; text-decoration: none; font-size: 14px; font-weight: 600;">
                <i class="fas fa-sign-out-alt"></i></a>
        </div>
    <?php else: ?>
        <div class="user-menu" style="cursor: pointer; position: relative; padding: 10px 0;">
            <div style="color: #fff; font-size: 14px; display: flex; align-items: center; gap: 10px;">
                <i class="far fa-user" style="color: #0073ff;"></i> Account
            </div>
            <div class="user-dropdown">
                <a href="login.php">Sign In</a>
                <a href="register.php">Create Account</a>
            </div>
        </div>
    <?php endif; ?>
</div>
</header>

<style>
    .nav-item { color: #fff; transition: color 0.2s; }
    .nav-item:hover { color: #0073ff !important; }
    .nav-active { color: #0073ff !important; border-bottom: 2px solid #0073ff; }
    
    /* Dropdown CSS - Keeps the menu visible while hovering */
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
    
    /* Show dropdown menu when hovering over user-menu icon or the dropdown itself */
    .user-menu:hover .user-dropdown { 
        display: block; 
    }
    
    .user-dropdown a { 
        display: block; 
        padding: 8px 20px; 
        color: #fff; 
        text-decoration: none; 
        font-size: 14px; 
    }
    .user-dropdown a:hover { 
        background: #0073ff; 
        color: #fff; 
    }
</style>