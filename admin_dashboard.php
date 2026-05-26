<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FIX: CSRF token generate කරනවා (delete booking form ලා use කරනවා)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['is_admin']) ||
    $_SESSION['is_admin'] !== true
) {
    header("Location: login.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Security Bug Fix: user_role එක 'admin' ද කියලා විතරක්ම බලනවා
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'config/db.php';

$success_msg = "";
$error_msg = "";

// දැනට ලොග් වෙලා ඉන්න Admin ගේ ID එක (වැරදිලා තමන්වම Demote කරගැනීම වැළැක්වීමට)
$logged_in_admin_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// ── 1. User කෙනෙක්ව Admin කෙනෙක් බවට පත් කිරීම (Promote) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_admin') {
    $user_id_to_promote = intval($_POST['user_id']);
    
    if ($user_id_to_promote > 0) {
        $promote_stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE user_id = ?");
        if ($promote_stmt->execute([$user_id_to_promote])) {
            header("Location: admin_dashboard.php?page=users&status=promoted");
            exit;
        } else {
            $error_msg = "Failed to update user privileges.";
        }
    }
}

// ── 2. Admin කෙනෙක්ව සාමාන්‍ය User කෙනෙක් බවට පත් කිරීම (Demote) ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'demote_admin') {
    $user_id_to_demote = intval($_POST['user_id']);
    
    if ($user_id_to_demote > 0) {
        // ආරක්ෂාවට: තමන්වම Demote කරන්න හදනවා නම් ඉඩ දෙන්නේ නැහැ
        if ($user_id_to_demote === $logged_in_admin_id) {
            $error_msg = "You cannot demote yourself!";
        } else {
            $demote_stmt = $conn->prepare("UPDATE users SET is_admin = 0 WHERE user_id = ?");
            if ($demote_stmt->execute([$user_id_to_demote])) {
                header("Location: admin_dashboard.php?page=users&status=demoted");
                exit;
            } else {
                $error_msg = "Failed to demote admin staff.";
            }
        }
    }
}

// සාර්ථක පණිවිඩ පෙන්වීම
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'promoted') {
        $success_msg = "User successfully promoted to Admin privileges!";
    } elseif ($_GET['status'] === 'demoted') {
        $success_msg = "Admin successfully demoted to standard user privileges!";
    }
}

// Sidebar active tab එක අල්ලගන්න
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Fetch all users
$users_stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Admin සහ Registered Users (Normal Players) වෙන් කරගැනීම
$admin_users  = array_filter($all_users, fn($user) => isset($user['is_admin']) && $user['is_admin'] == 1);
$normal_users = array_filter($all_users, fn($user) => !isset($user['is_admin']) || $user['is_admin'] == 0);

// Online Users Logic (පසුගිය විනාඩි 5 ඇතුලත last_activity තිබූ අය)
// SECURITY FIX: only verified users update last_activity
$update_admin_activity = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ? AND is_verified = 1");
$update_admin_activity->execute([$logged_in_admin_id]);

// SECURITY FIX: online = is_verified=1 only
$online_stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE last_activity >= NOW() - INTERVAL 1 MINUTE
    AND is_verified = 1
    ORDER BY last_activity DESC
"); 
$online_stmt->execute();
$online_users = $online_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all bookings
$bookings_stmt = $conn->query("SELECT bookings.*, users.full_name FROM bookings JOIN users ON bookings.user_id = users.user_id ORDER BY booking_date DESC");
$all_bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_users   = count($all_users);
$total_bookings = count($all_bookings);

$today = date('Y-m-d');
$bookings_today = count(array_filter($all_bookings, fn($b) => $b['booking_date'] === $today));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartCricket Arena</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0a0a0a;
            --surface:   #111111;
            --surface2:  #181818;
            --border:    rgba(255,255,255,0.07);
            --border2:   rgba(255,255,255,0.12);
            --text:      #f0f0f0;
            --text-dim:  #888;
            --text-hint: #555;
            --accent:    #00d2ff;
            --accent-bg: rgba(0,210,255,0.08);
            --danger:    #ff4d4d;
            --danger-bg: rgba(255,77,77,0.08);
            --success:   #22c55e;
            --success-bg:rgba(34,197,94,0.08);
            --radius:    10px;
            --radius-lg: 14px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ── Sidebar ─────────────────────────────── */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: 220px; height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            padding: 24px 0; z-index: 100;
        }
        .sidebar-logo { padding: 0 20px 24px; border-bottom: 1px solid var(--border); margin-bottom: 16px; }
        .sidebar-logo span { font-size: 20px; font-weight: 600; color: var(--accent); letter-spacing: -0.3px; }
        .sidebar-logo p { font-size: 11px; color: var(--text-hint); margin-top: 2px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 20px; font-size: 13px; color: var(--text-dim); cursor: pointer; border-left: 2px solid transparent; transition: all .15s; text-decoration: none; }
        .nav-item:hover { color: var(--text); background: var(--surface2); }
        .nav-item.active { color: var(--accent); border-left-color: var(--accent); background: var(--accent-bg); }
        .nav-item i { width: 16px; text-align: center; font-size: 14px; }
        .sidebar-footer { margin-top: auto; padding: 16px 20px 0; border-top: 1px solid var(--border); }
        .logout-btn { display: flex; align-items: center; gap: 8px; width: 100%; padding: 9px 12px; background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(255,77,77,0.2); border-radius: var(--radius); font-size: 13px; cursor: pointer; text-decoration: none; transition: background .15s; }
        .logout-btn:hover { background: rgba(255,77,77,0.15); }

        /* ── Main content ─────────────────────────── */
        .main { margin-left: 220px; padding: 36px 40px; min-height: 100vh; }
        .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
        .topbar-title { font-size: 22px; font-weight: 600; letter-spacing: -0.4px; }
        .topbar-sub { font-size: 13px; color: var(--text-dim); margin-top: 3px; }
        .admin-badge { display: flex; align-items: center; gap: 8px; background: var(--surface2); border: 1px solid var(--border); border-radius: 20px; padding: 6px 14px; font-size: 12px; color: var(--text-dim); }
        .admin-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--success); box-shadow: 0 0 6px var(--success); }

        /* ── Stats grid ───────────────────────────── */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 32px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px 22px; }
        .stat-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 16px; margin-bottom: 14px; }
        .stat-icon.blue  { background: var(--accent-bg);   color: var(--accent); }
        .stat-icon.green { background: var(--success-bg);  color: var(--success); }
        .stat-icon.warn  { background: rgba(245,158,11,0.08);     color: #f59e0b; }
        .stat-label { font-size: 11px; color: var(--text-hint); text-transform: uppercase; letter-spacing: .06em; }
        .stat-value { font-size: 30px; font-weight: 600; color: var(--text); margin: 4px 0 6px; letter-spacing: -1px; }

        /* ── Section card ─────────────────────────── */
        .section-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); margin-bottom: 24px; overflow: hidden; }
        .section-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 22px; border-bottom: 1px solid var(--border); gap: 15px; }
        .section-title { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 500; }
        .section-title i { color: var(--accent); font-size: 15px; }
        .section-count { font-size: 11px; background: var(--accent-bg); color: var(--accent); border-radius: 20px; padding: 1px 8px; margin-left: 6px; }

        /* ── Tabs Style ───────────────────────────── */
        .tab-container { display: flex; gap: 10px; padding: 12px 22px; background: var(--surface2); border-bottom: 1px solid var(--border); }
        .tab-btn { background: transparent; border: none; outline: none; color: var(--text-dim); font-size: 13px; font-weight: 500; padding: 6px 16px; border-radius: 20px; cursor: pointer; transition: all 0.2s; }
        .tab-btn:hover { color: var(--text); background: rgba(255,255,255,0.03); }
        .tab-btn.active { background: var(--accent); color: #000; font-weight: 600; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .search-wrap { display: flex; align-items: center; gap: 7px; background: var(--surface2); border: 1px solid var(--border); border-radius: var(--radius); padding: 7px 12px; }
        .search-wrap i { color: var(--text-hint); font-size: 13px; }
        .search-wrap input { background: transparent; border: none; outline: none; color: var(--text); font-size: 13px; width: 160px; }

        /* Action Buttons */
        .promote-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 500; color: var(--accent); background: var(--accent-bg); border: 1px solid rgba(0,210,255,0.2); border-radius: var(--radius); padding: 5px 12px; cursor: pointer; transition: background .15s; }
        .promote-btn:hover { background: rgba(0,210,255,0.18); }

        .demote-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 500; color: var(--danger); background: var(--danger-bg); border: 1px solid rgba(255,77,77,0.2); border-radius: var(--radius); padding: 5px 12px; cursor: pointer; transition: background .15s; }
        .demote-btn:hover { background: rgba(255,77,77,0.18); }

        table { width: 100%; border-collapse: collapse; }
        thead th { padding: 11px 22px; font-size: 11px; font-weight: 500; color: var(--text-hint); text-transform: uppercase; letter-spacing: .06em; text-align: left; background: var(--surface2); border-bottom: 1px solid var(--border); }
        tbody td { padding: 13px 22px; font-size: 13px; border-bottom: 1px solid var(--border); color: var(--text); vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: var(--surface2); }

        .name-cell { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; flex-shrink: 0; }
        .av-blue  { background: var(--accent-bg);   color: var(--accent); }
        .av-green { background: var(--success-bg);  color: var(--success); }
        .av-warn  { background: rgba(245,158,11,0.08);      color: #f59e0b; }
        
        .role-badge { font-size: 11px; font-weight: 600; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; display: inline-block; }
        .role-badge.admin { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(255,77,77,0.15); }
        .role-badge.online { background: var(--success-bg); color: var(--success); border: 1px solid rgba(34,197,94,0.15); font-size: 10px; padding: 1px 6px; }

        /* Online Pulse Animation */
        .online-dot { width: 8px; height: 8px; border-radius: 50%; background-color: var(--success); display: inline-block; position: relative; }
        .online-dot::after { content: ''; position: absolute; width: 100%; height: 100%; top: 0; left: 0; background: var(--success); border-radius: 50%; animation: pulse 1.5s infinite ease-in-out; }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(2.5); opacity: 0; } }

        .slot-pill { display: inline-block; background: var(--accent-bg); color: var(--accent); font-size: 12px; font-family: monospace; padding: 3px 10px; border-radius: 20px; border: 1px solid rgba(0,210,255,0.15); }
        .booking-id { font-size: 12px; color: var(--text-hint); font-weight: 500; }
        .del-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; color: var(--danger); background: var(--danger-bg); border: 1px solid rgba(255,77,77,0.2); border-radius: var(--radius); padding: 5px 12px; cursor: pointer; text-decoration: none; }
        .del-btn:hover { background: rgba(255,77,77,0.18); }
        
        /* Alert Banners */
        .alert { padding: 12px 20px; border-radius: var(--radius); margin-bottom: 20px; font-size: 13px; font-weight: 500; }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid rgba(34,197,94,0.2); }
        .alert-error { background: var(--danger-bg); color: var(--danger); border: 1px solid rgba(255,77,77,0.2); }

        .pagination { display: flex; align-items: center; justify-content: flex-end; padding: 14px 22px; border-top: 1px solid var(--border); background: var(--surface); }
        .pag-info { font-size: 12px; color: var(--text-dim); }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 3px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <a href="index.php" style="text-decoration: none; color: inherit; display: block;">
            <span><i class="fas fa-cricket-bat-ball"></i> SmartCricket</span>
            <p>Admin Portal</p>
        </a>
    </div>
    <nav>
        <a href="admin_dashboard.php?page=dashboard" class="nav-item <?= $current_page == 'dashboard' ? 'active' : '' ?>">
            <i class="fas fa-gauge-high"></i> Dashboard
        </a>
        <a href="admin_dashboard.php?page=users" class="nav-item <?= $current_page == 'users' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Users
        </a>
        <a href="admin_dashboard.php?page=bookings" class="nav-item <?= $current_page == 'bookings' ? 'active' : '' ?>">
            <i class="fas fa-calendar-check"></i> Bookings
        </a>
        <a href="admin_dashboard.php?page=slots" class="nav-item <?= $current_page == 'slots' ? 'active' : '' ?>">
            <i class="fas fa-clock"></i> Slots
        </a>
        <a href="admin_dashboard.php?page=settings" class="nav-item <?= $current_page == 'settings' ? 'active' : '' ?>">
            <i class="fas fa-gear"></i> Settings
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>
    </div>
</aside>

<main class="main">

    <?php if(!empty($success_msg)): ?>
        <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= $success_msg ?></div>
    <?php endif; if(!empty($error_msg)): ?>
        <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= $error_msg ?></div>
    <?php endif; ?>

    <div class="topbar">
        <div>
            <div class="topbar-title" style="text-transform: capitalize;"><?= htmlspecialchars($current_page) ?></div>
            <div class="topbar-sub">Control panel management overview</div>
        </div>
        <div class="admin-badge">
            <span class="dot"></span>
            Admin — <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?>
        </div>
    </div>

    <?php switch($current_page): 
        case 'dashboard': ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                    <div class="stat-label">Total users</div>
                    <div class="stat-value"><?= $total_users ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-label">Total bookings</div>
                    <div class="stat-value"><?= $total_bookings ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warn"><i class="fas fa-sun"></i></div>
                    <div class="stat-label">Bookings today</div>
                    <div class="stat-value"><?= $bookings_today ?></div>
                </div>
            </div>

            <h3 style="margin-bottom: 16px; font-weight: 500; font-size: 15px; color: var(--text-dim);">Quick Menu</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <a href="admin_dashboard.php?page=users" style="text-decoration: none; display: flex; align-items: center; justify-content: space-between; background: var(--surface); border: 1px solid var(--border); padding: 20px; border-radius: var(--radius-lg);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: var(--accent-bg); color: var(--accent); width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-users"></i></div>
                        <div>
                            <h4 style="color: var(--text); font-size: 14px; font-weight: 500;">Manage Accounts</h4>
                            <p style="color: var(--text-hint); font-size: 12px;">Separate views for Admins & Registered Users</p>
                        </div>
                    </div>
                    <i class="fas fa-arrow-right" style="color: var(--text-hint);"></i>
                </a>
                <a href="admin_dashboard.php?page=bookings" style="text-decoration: none; display: flex; align-items: center; justify-content: space-between; background: var(--surface); border: 1px solid var(--border); padding: 20px; border-radius: var(--radius-lg);">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: var(--success-bg); color: var(--success); width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-calendar-check"></i></div>
                        <div>
                            <h4 style="color: var(--text); font-size: 14px; font-weight: 500;">Net Reservations</h4>
                            <p style="color: var(--text-hint); font-size: 12px;">View active slots and cancellations</p>
                        </div>
                    </div>
                    <i class="fas fa-arrow-right" style="color: var(--text-hint);"></i>
                </a>
            </div>
        <?php break; ?>

        <?php 
        case 'users': ?>
            <div class="section-card">
                <div class="section-head">
                    <div class="section-title"><i class="fas fa-users"></i> System Accounts <span class="section-count"><?= $total_users ?></span></div>
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search accounts..." id="userSearch" oninput="filterActiveTable()">
                    </div>
                </div>

                <div class="tab-container">
                    <button class="tab-btn active" onclick="switchTab('admins-tab', this)">Admins (<?= count($admin_users) ?>)</button>
                    <button class="tab-btn" onclick="switchTab('players-tab', this)">Registered Users (<?= count($normal_users) ?>)</button>
                    <button class="tab-btn" onclick="switchTab('online-tab', this)" style="display: flex; align-items: center; gap: 6px;">
                        <span class="online-dot"></span> Online Now (<?= count($online_users) ?>)
                    </button>
                </div>

                <div id="admins-tab" class="tab-content active">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:28%">Full name</th>
                                <th style="width:28%">Email</th>
                                <th style="width:24%">NIC number</th>
                                <th style="width:20%; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="adminBody">
                            <?php
                            $av_classes = ['av-blue','av-green','av-warn'];
                            $i = 0;
                            foreach ($admin_users as $user):
                                $initials = implode('', array_map(fn($w) => strtoupper($w ?? ''), explode(' ', $user['full_name'])));
                                $initials = substr($initials, 0, 2);
                                $av = $av_classes[$i % 3]; $i++;
                                $is_self = (intval($user['user_id']) === $logged_in_admin_id);
                            ?>
                            <tr>
                                <td><div class="name-cell"><span class="avatar <?= $av ?>"><?= htmlspecialchars($initials) ?></span><?= htmlspecialchars($user['full_name']) ?> <?= $is_self ? '<span style="color:var(--accent); font-size:11px;">(You)</span>' : '' ?></div></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['nic_number']) ?></td>
                                <td style="text-align: right;">
                                    <?php if (!$is_self): ?>
                                        <form method="POST" action="admin_dashboard.php?page=users" style="display:inline;" onsubmit="return confirm('Are you sure you want to demote <?= htmlspecialchars($user['full_name']) ?> to a standard user?')">
                                            <input type="hidden" name="action" value="demote_admin">
                                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                            <button type="submit" class="demote-btn">
                                                <i class="fas fa-user-minus"></i> Demote
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="role-badge admin">Active Admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($admin_users)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--text-hint); padding: 20px;">No admin accounts found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <span class="pag-info">Showing <?= count($admin_users) ?> admin accounts</span>
                    </div>
                </div>

                <div id="players-tab" class="tab-content">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:28%">Full name</th>
                                <th style="width:28%">Email</th>
                                <th style="width:24%">NIC number</th>
                                <th style="width:20%; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="playerBody">
                            <?php
                            $i = 0;
                            foreach ($normal_users as $user):
                                $initials = implode('', array_map(fn($w) => strtoupper($w ?? ''), explode(' ', $user['full_name'])));
                                $initials = substr($initials, 0, 2);
                                $av = $av_classes[$i % 3]; $i++;
                            ?>
                            <tr>
                                <td><div class="name-cell"><span class="avatar <?= $av ?>"><?= htmlspecialchars($initials) ?></span><?= htmlspecialchars($user['full_name']) ?></div></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['nic_number']) ?></td>
                                <td style="text-align: right;">
                                    <form method="POST" action="admin_dashboard.php?page=users" style="display:inline;" onsubmit="return confirm('Are you sure you want to promote <?= htmlspecialchars($user['full_name']) ?> to Admin?')">
                                        <input type="hidden" name="action" value="make_admin">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <button type="submit" class="promote-btn">
                                            <i class="fas fa-user-shield"></i> Make Admin
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($normal_users)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--text-hint); padding: 20px;">No registered users found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <span class="pag-info">Showing <?= count($normal_users) ?> registered users</span>
                    </div>
                </div>

                <div id="online-tab" class="tab-content">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:28%">Full name</th>
                                <th style="width:28%">Email</th>
                                <th style="width:24%">Status</th>
                                <th style="width:20%; text-align: right;">Type</th>
                            </tr>
                        </thead>
                        <tbody id="onlineBody">
                            <?php
                            $i = 0;
                            foreach ($online_users as $user):
                                $initials = implode('', array_map(fn($w) => strtoupper($w ?? ''), explode(' ', $user['full_name'])));
                                $initials = substr($initials, 0, 2);
                                $av = $av_classes[$i % 3]; $i++;
                                $is_admin = (isset($user['is_admin']) && $user['is_admin'] == 1);
                            ?>
                            <tr>
                                <td>
                                    <div class="name-cell">
                                        <span class="avatar <?= $av ?>"><?= htmlspecialchars($initials) ?></span>
                                        <?= htmlspecialchars($user['full_name']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span class="online-dot"></span>
                                        <span class="role-badge online">Online</span>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($is_admin): ?>
                                        <span class="role-badge admin" style="font-size: 10px;">Admin Staff</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-dim); font-size: 12px;">Registered User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($online_users)): ?>
                                <tr><td colspan="4" style="text-align:center; color:var(--text-hint); padding: 30px;"><i class="fas fa-user-slash" style="margin-bottom:8px; display:block; font-size:20px;"></i> No users online at the moment.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <span class="pag-info">Showing <?= count($online_users) ?> users currently online</span>
                    </div>
                </div>

            </div>
        <?php break; ?>

        <?php case 'bookings': ?>
            <style>
                #adminBookingModal { display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.85); backdrop-filter:blur(12px); animation:fadeIn 0.3s ease; }
                .premium-modal { background:#0f1116; border:1px solid #333; margin:4% auto; padding:35px; width:480px; border-radius:24px; color:#fff; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); }
                .modal-header i { background:linear-gradient(135deg, #0073ff, #00d2ff); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-size:40px; }
                .premium-input { width:100%; padding:14px; margin-bottom:15px; background:rgba(255,255,255,0.03); border:1px solid #333; color:#fff; border-radius:12px; }
                .premium-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:20px 0; max-height:250px; overflow-y:auto; padding-right:8px; }
                .slot-label { background:rgba(255,255,255,0.03); border:1px solid #333; padding:12px; border-radius:12px; cursor:pointer; text-align:center; transition:0.2s; }
                .slot-label:hover { border-color:#0073ff; }
                .premium-grid input[type="radio"] { display:none; }
                .premium-grid input[type="radio"]:checked + .slot-label { background:#0073ff; border-color:#0073ff; box-shadow:0 0 15px rgba(0,115,255,0.3); }
                .slot-time { display:block; font-size:13px; font-weight:600; margin-bottom:2px; }
                /* ඔයා ඉල්ලපු කලින් තිබ්බ පාට (Green) */
                .slot-status { font-size:10px; color:#00e676; opacity:0.9; }
                .confirm-btn { width:100%; padding:16px; background:#0073ff; border:none; border-radius:14px; font-weight:700; cursor:pointer; color:#fff; }
                @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
            </style>

            <div id="adminBookingModal">
                <div class="premium-modal">
                    <span onclick="document.getElementById('adminBookingModal').style.display='none'" style="float:right; cursor:pointer; font-size:24px;">&times;</span>
                    <div class="modal-header" style="text-align:center; margin-bottom:25px;">
                        <i class="fas fa-cubes-stacked"></i>
                        <h2 style="font-size:24px; margin:10px 0;">Premium Slot Booking</h2>
                    </div>
                    <form action="save_admin_booking.php" method="POST">
                        <input type="number" name="user_id" placeholder="Enter User ID" class="premium-input" required>
                        <input type="date" name="booking_date" class="premium-input" value="<?= date('Y-m-d') ?>" required style="color-scheme:dark;">
                        <div class="premium-grid">
                            <?php 
                            $slots = ["08:00 AM - 09:00 AM", "09:00 AM - 10:00 AM", "10:00 AM - 11:00 AM", "11:00 AM - 12:00 PM", "12:00 PM - 01:00 PM", "01:00 PM - 02:00 PM", "02:00 PM - 03:00 PM", "03:00 PM - 04:00 PM", "04:00 PM - 05:00 PM", "05:00 PM - 06:00 PM", "06:00 PM - 07:00 PM", "07:00 PM - 08:00 PM"];
                            foreach($slots as $s) {
                                echo '<label><input type="radio" name="slot_time" value="'.$s.'" required><div class="slot-label"><span class="slot-time">'.$s.'</span><span class="slot-status"><i class="fas fa-circle-check"></i> Available</span></div></label>';
                            }
                            ?>
                        </div>
                        <button type="submit" class="confirm-btn">CONFIRM & RESERVE NOW</button>
                    </form>
                </div>
            </div>

            <div class="section-card">
                <div class="section-head" style="display:flex; justify-content:space-between; align-items:center;">
                    <div class="section-title"><i class="fas fa-calendar-check"></i> Lane Reservations <span class="section-count"><?= $total_bookings ?></span></div>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <button onclick="document.getElementById('adminBookingModal').style.display='block'" class="promote-btn" style="cursor:pointer;">
                            <i class="fas fa-plus"></i> Add Manual Booking
                        </button>
                        <div class="search-wrap">
                            <i class="fas fa-search"></i>
                            <input type="text" placeholder="Search bookings..." id="bookSearch" oninput="filterTable('bookBody','bookSearch')">
                        </div>
                    </div>
                </div>
                <table>
                    <thead><tr><th>ID</th><th>User name</th><th>Slot time</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody id="bookBody">
                        <?php foreach ($all_bookings as $booking): ?>
                        <tr>
                            <td><span class="booking-id">#<?= htmlspecialchars($booking['booking_id']) ?></span></td>
                            <td><?= htmlspecialchars($booking['full_name']) ?></td>
                            <td><span class="slot-pill"><?= htmlspecialchars($booking['slot_time']) ?></span></td>
                            <td><?= htmlspecialchars($booking['booking_date']) ?></td>
                            <td>
                                <form method="POST" action="delete_booking.php" style="display:inline;" onsubmit="return confirm('Delete this booking?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="booking_id" value="<?= intval($booking['booking_id']) ?>">
                                    <button type="submit" class="del-btn"><i class="fas fa-trash-can"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php break; ?>

        <?php case 'slots': ?>
            <div class="section-card" style="padding: 40px; text-align: center; color: var(--text-dim);">
                <i class="fas fa-clock" style="font-size: 32px; color: #f59e0b; margin-bottom: 12px;"></i>
                <h4>Manage Net Practice Slots</h4>
                <p style="font-size: 13px; margin-top: 4px;">Time slots configuration options go here.</p>
            </div>
        <?php break; ?>

        <?php case 'settings': ?>
            <div class="section-card" style="padding: 40px; text-align: center; color: var(--text-dim);">
                <i class="fas fa-gear" style="font-size: 32px; color: var(--accent); margin-bottom: 12px;"></i>
                <h4>System Settings</h4>
                <p style="font-size: 13px; margin-top: 4px;">Configurations can be added inside this section.</p>
            </div>
        <?php break; ?>

    <?php endswitch; ?>

</main>

<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
    
    document.getElementById('userSearch').value = '';
    filterActiveTable();
}

function filterActiveTable() {
    const q = document.getElementById('userSearch').value.toLowerCase();
    document.querySelectorAll('.tab-content.active tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
}

function filterTable(bodyId, inputId) {
    const q = document.getElementById(inputId).value.toLowerCase();
    document.querySelectorAll('#' + bodyId + ' tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>

</body>
</html>