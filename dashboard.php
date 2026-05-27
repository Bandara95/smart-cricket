<?php
include 'config/db.php';
include 'auth_ping.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $user_stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $current_user = $user_stmt->fetch();
    
    if (!$current_user) {
        session_destroy();
        header("Location: login.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database Error (User Fetch): " . $e->getMessage());
}

try {
    $booking_stmt = $conn->prepare("SELECT booking_date, slot_time FROM bookings WHERE user_id = ? ORDER BY booking_date DESC, slot_time DESC");
    $booking_stmt->execute([$user_id]);
    $my_bookings = $booking_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error (Bookings Fetch): " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Cricket Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Mobile table fix */
        .bookings-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .dashboard-table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .dashboard-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            min-width: 420px;
        }

        @media (max-width: 768px) {
            .dashboard-main {
                margin-top: 80px !important;
                padding: 0 12px 40px 12px !important;
            }
            .dashboard-welcome h2 {
                font-size: 20px !important;
            }
            .bookings-card {
                padding: 18px 14px !important;
            }
            .bookings-card-header h3 {
                font-size: 15px !important;
            }
            .dashboard-table th,
            .dashboard-table td {
                padding: 12px 8px !important;
                font-size: 12px !important;
            }
            /* Time slot pill smaller */
            .slot-pill {
                font-size: 10px !important;
                padding: 4px 6px !important;
            }
            /* Status badge smaller */
            .status-confirmed {
                font-size: 10px !important;
                padding: 3px 7px !important;
            }
            /* Book new slot button */
            .btn-book-new {
                padding: 7px 12px !important;
                font-size: 11px !important;
            }
        }

        @media (max-width: 480px) {
            .dashboard-table {
                min-width: 360px;
            }
            .dashboard-table th:first-child,
            .dashboard-table td:first-child {
                display: none; /* hide # column on very small screens */
            }
        }
    </style>
</head>
<body>
    
<?php include 'includes/header.php'; ?>

    <main class="dashboard-main" style="max-width: 1000px; margin: 120px auto 40px auto; padding: 0 20px;">
        
        <div class="dashboard-welcome" style="margin-bottom: 30px;">
            <h2 style="font-size: 26px; font-weight: 800; margin-bottom: 5px; color: #ffffff;">
                Welcome Back, <?php echo htmlspecialchars($current_user['full_name']); ?>!
            </h2>
            <p style="color: #888888; font-size: 14px;">Manage and keep track of your scheduled cricket net reservations.</p>
        </div>

        <div class="bookings-card" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            
            <div class="bookings-card-header">
                <h3 style="font-size: 18px; font-weight: 600; color: #0073ff; margin: 0;">
                    <i class="fas fa-list-check" style="margin-right: 8px;"></i> My Slot Bookings
                </h3>
                <a href="booking.php" class="btn-book-new modal-btn success-btn" style="padding: 8px 16px; font-size: 12px; margin: 0; width: auto; text-decoration: none; text-align: center; border-radius: 6px; background: #0073ff; border-color: #0073ff; color: #fff;">
                    + Book New Slot
                </a>
            </div>

            <!-- Wrapper div for horizontal scroll on mobile -->
            <div class="dashboard-table-wrapper">
                <table class="dashboard-table">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.08);">
                            <th style="padding: 16px 14px; font-size: 12px; color: #888888; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">#</th>
                            <th style="padding: 16px 14px; font-size: 12px; color: #888888; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Reserved Date</th>
                            <th style="padding: 16px 14px; font-size: 12px; color: #888888; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Time Slot</th>
                            <th style="padding: 16px 14px; font-size: 12px; color: #888888; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($my_bookings) > 0): ?>
                            <?php foreach ($my_bookings as $index => $booking): ?>
                                <tr style="transition: 0.2s; border-bottom: 1px solid rgba(255,255,255,0.04);">
                                    <td style="padding: 16px 14px; color: #666666; font-size: 14px;"><?php echo $index + 1; ?></td>
                                    
                                    <td style="padding: 16px 14px; font-weight: 600; font-size: 14px; color: #ffffff;">
                                        <i class="far fa-calendar-check" style="color: #00b0ff; margin-right: 8px;"></i>
                                        <?php echo htmlspecialchars($booking['booking_date']); ?>
                                    </td>
                                    
                                    <td style="padding: 16px 14px; font-size: 14px;">
                                        <span class="slot-pill" style="background: rgba(0,115,255,0.15); border: 1px solid rgba(0,115,255,0.3); padding: 5px 10px; border-radius: 6px; font-family: monospace; color: #00b0ff; font-weight: 600;">
                                            <?php echo strtoupper(htmlspecialchars($booking['slot_time'])); ?>
                                        </span>
                                    </td>
                                    
                                    <td style="padding: 16px 14px; font-size: 14px;">
                                        <span class="status-confirmed" style="background: rgba(0,230,118,0.1); border: 1px solid rgba(0,230,118,0.2); color: #00e676; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                            <span style="width: 6px; height: 6px; background-color: #00e676; border-radius: 50%;"></span> Confirmed
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="padding: 50px 20px; text-align: center; color: #555555; font-size: 14px;">
                                    <i class="far fa-calendar-times" style="font-size: 40px; margin-bottom: 12px; display: block; color: #333333;"></i>
                                    No scheduled cricket slot bookings found. Click "+ Book New Slot" to get started.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div><!-- end dashboard-table-wrapper -->

        </div>

    </main>

    <script>
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.arena-navbar-ford');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
