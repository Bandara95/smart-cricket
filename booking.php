<?php
include 'config/db.php';
include 'auth_ping.php';

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header("Location: admin_dashboard.php?page=bookings");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$stmt = $conn->prepare("SELECT slot_time FROM bookings WHERE booking_date = ?");
$stmt->execute([$selected_date]);
$booked_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cricket Arena - Premium Booking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* ── Slot Grid ─────────────────────────────────────────── */
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            max-height: 260px;
            overflow-y: auto;
            overflow-x: hidden;        /* fix: no horizontal cut */
            padding-right: 5px;
            margin-top: 8px;
            width: 100%;
            box-sizing: border-box;
        }

        .slots-grid::-webkit-scrollbar { width: 5px; }
        .slots-grid::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); border-radius: 4px; }
        .slots-grid::-webkit-scrollbar-thumb { background: rgba(0,115,255,0.3); border-radius: 4px; }

        /* ── Slot Card ─────────────────────────────────────────── */
        .slot-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
            width: 100%;
            box-sizing: border-box;
        }

        .slot-card input[type="radio"] { display: none; }

        .slot-time-text {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 4px;
            word-break: break-word;     /* fix: text won't overflow */
        }

        .slot-status {
            font-size: 10px;
            font-weight: 500;
            color: #00e676;
        }

        .slot-card:not(.disabled):hover {
            border-color: #0073ff;
            background: rgba(0,115,255,0.05);
            transform: translateY(-2px);
        }

        .slot-card.selected {
            background: linear-gradient(135deg, rgba(0,115,255,0.2) 0%, rgba(0,230,118,0.1) 100%);
            border-color: #0073ff;
            box-shadow: 0 0 12px rgba(0,115,255,0.25);
        }

        .slot-card.selected .slot-time-text { color: #00b0ff; }

        .slot-card.disabled {
            background: rgba(255,0,0,0.02);
            border-color: rgba(255,255,255,0.03);
            cursor: not-allowed;
            opacity: 0.4;
        }

        .slot-card.disabled .slot-time-text { color: #777777; text-decoration: line-through; }
        .slot-card.disabled .slot-status { color: #ff3d00; }

        /* ── Booking form wrapper ──────────────────────────────── */
        .booking-form-box {
            width: 100%;
            max-width: 440px;
            padding: 25px 30px;
            background: #14171c;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.6);
            box-sizing: border-box;
        }

        /* ── Mobile overrides ──────────────────────────────────── */
        @media (max-width: 768px) {
            .booking-form-box {
                max-width: 100%;
                padding: 20px 14px;
            }

            .slots-grid {
                gap: 8px;
                max-height: 280px;
            }

            .slot-card { padding: 10px 6px; }
            .slot-time-text { font-size: 10px; }
            .slot-status { font-size: 9px; }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

    <main style="display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 90px 16px 30px 16px; box-sizing: border-box;">

        <div class="booking-form-box">

            <div style="text-align: center; margin-bottom: 15px;">
                <i class="fas fa-cubes-stacked" style="font-size: 32px; margin-bottom: 10px; color: #0073ff;"></i>
                <h3 style="font-size: 20px; margin-bottom: 2px;">Premium Slot Booking</h3>
                <p style="font-size: 13px; color: #888; margin-bottom: 0;">Pick a date and choose an available interactive grid slot</p>
            </div>

            <form action="save_booking.php" method="POST" id="bookingForm">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

                <div class="input-group" style="margin-bottom: 15px;">
                    <label for="booking_date" style="font-size: 11px; margin-bottom: 6px; display: block;">
                        <i class="far fa-calendar-alt"></i> Booking Date
                    </label>
                    <input type="date" id="booking_date" name="booking_date"
                        value="<?php echo $selected_date; ?>"
                        min="<?php echo date('Y-m-d'); ?>"
                        onchange="window.location.href='booking.php?date=' + this.value"
                        required style="padding: 12px; font-size: 14px; color-scheme: dark; width: 100%; box-sizing: border-box;">
                </div>

                <div class="input-group" style="margin-bottom: 20px;">
                    <label style="font-size: 11px; margin-bottom: 4px; display: block;">
                        <i class="far fa-clock"></i> Select Time Slot Grid
                    </label>

                    <div class="slots-grid">
                        <?php
                        $all_slots = [
                            "08:00 AM - 09:00 AM", "09:00 AM - 10:00 AM", "10:00 AM - 11:00 AM",
                            "11:00 AM - 12:00 PM", "12:00 PM - 01:00 PM", "01:00 PM - 02:00 PM",
                            "02:00 PM - 03:00 PM", "03:00 PM - 04:00 PM", "04:00 PM - 05:00 PM",
                            "05:00 PM - 06:00 PM", "06:00 PM - 07:00 PM", "07:00 PM - 08:00 PM"
                        ];

                        foreach ($all_slots as $slot) {
                            $is_booked = in_array($slot, $booked_slots);
                            if ($is_booked) {
                                echo '
                                <div class="slot-card disabled">
                                    <span class="slot-time-text">'.$slot.'</span>
                                    <span class="slot-status"><i class="fas fa-ban"></i> Booked</span>
                                </div>';
                            } else {
                                echo '
                                <label class="slot-card">
                                    <input type="radio" name="slot_time" value="'.$slot.'" required onclick="selectSlot(this)">
                                    <span class="slot-time-text">'.$slot.'</span>
                                    <span class="slot-status"><i class="fas fa-circle-check"></i> Available</span>
                                </label>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <button type="submit" class="modal-btn success-btn" style="padding: 12px; font-size: 13px; font-weight: 600; width: 100%; box-sizing: border-box;">
                    Confirm &amp; Reserve Now
                </button>
            </form>

        </div>
    </main>

    <script>
        function selectSlot(element) {
            document.querySelectorAll('.slot-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.parentElement.classList.add('selected');
        }

        window.addEventListener('scroll', function() {
            const header = document.querySelector('.arena-navbar-ford');
            if (header) {
                header.classList.toggle('scrolled', window.scrollY > 50);
            }
        });
    </script>
</body>
</html>
