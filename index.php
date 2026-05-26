<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'config/db.php';
// SECURITY FIX: last_activity update only for verified + logged in users
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && isset($conn)) {
    $u_id = intval($_SESSION['user_id']);
    $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ? AND is_verified = 1")
         ->execute([$u_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Indoor Cricket Arena | Premium Sports Booking</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
      
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="arena-hero">
        <div class="hero-overlay"></div>
        <div class="arena-hero-content">
            <span class="hero-badge">KADAWATHA'S PREMIER ARENA</span>
            <h1 class="hero-title">NEXT-GEN INDOOR <br>SOFT BALL CRICKET</h1>
            <p class="hero-subtitle">Premium turf, professional lighting, and an automated booking system tailored for maximum performance.</p>
            
            <div class="hero-pricing-tag">
                <p>Starting at <span>Rs. 4,000</span> / hour</p>
            </div>

            <div class="arena-hero-actions">
                <a href="booking.php" class="arena-btn arena-btn-solid">Book Now</a>
                <a href="#features" class="arena-btn arena-btn-outline">Explore Arena</a>
            </div>
        </div>
    </section>

    <section id="features" class="arena-features">
        <div class="section-header">
            <h2>Why Play At Our Arena?</h2>
            <p>Designed to give soft-ball cricket teams the ultimate professional indoor experience.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-baseball-bat-ball"></i>
                <h3>Soft Ball Specialized</h3>
                <p>Custom multi-layer turf and specialized netting optimized specifically for high-intensity soft ball cricket matches.</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-bolt"></i>
                <h3>Instant OTP Booking</h3>
                <p>No phone calls needed. Verify your mobile number instantly via automated OTP and secure your slot in seconds.</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-shield-halved"></i>
                <h3>Secured Payments</h3>
                <p>Pay your advance booking fee securely online with an integrated real-time venue confirmation workflow.</p>
            </div>
        </div>
    </section>

    <section id="location" class="arena-location">
        <div class="location-container">
            <div class="location-text">
                <span class="hero-badge">STRATEGIC LOCATION</span>
                <h2>150m from Kandy Road</h2>
                <p>Located just 150 meters down Pahala Biyanwila (Pinthaliya) Road, Kadawatha. Our arena provides direct and lightning-fast access right from the primary route, beating out long-distance competitor zones.</p>
                
                <div class="location-meta">
                    <div class="meta-item"><i class="fas fa-map-marker-alt"></i> Pinthaliya Road, Kadawatha</div>
                    <div class="meta-item"><i class="fas fa-clock"></i> Open 24/7</div>
                </div>
            </div>
            
            <div class="location-map-box">
                <img src="Assets/Images/image_216ff8.png" alt="Kadawatha Arena Location Map" class="map-img">
            </div>
        </div>
    </section>

    <footer class="arena-footer">
        <p>&copy; 2026 SmartCricket Arena Management System. All Rights Reserved.</p>
    </footer>

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
