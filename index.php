<?php
// Session එක ආරම්භ කිරීම සහ ඩේටාබේස් සම්බන්ධතාවය
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'config/db.php';

// SECURITY FIX: last_activity update only for verified + logged in users
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && isset($conn)) {
    $u_id = intval($_SESSION['user_id']);
    $stmt = $conn->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ? AND is_verified = 1");
    $stmt->execute([$u_id]);
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
        /* ============================================================
           MOBILE RESPONSIVE OVERRIDES
           (ඔබේ style.css එකට add කරන්න, otherwise ඒකෙ duplicate වෙනවා)
        ============================================================ */

        /* --- Base Reset --- */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* ============================================================
           HERO SECTION
        ============================================================ */
        .arena-hero {
            position: relative;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 6rem 1.25rem 3rem;
            background: #0a0a0a;
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.3) 60%, rgba(0,0,0,0.7) 100%);
            z-index: 1;
        }

        .arena-hero-content {
            position: relative;
            z-index: 2;
            max-width: 720px;
            width: 100%;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-block;
            font-size: clamp(0.6rem, 2.5vw, 0.75rem);
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #f5c518;
            border: 1px solid #f5c518;
            padding: 0.35rem 0.9rem;
            border-radius: 2rem;
            margin-bottom: 1.25rem;
        }

        .hero-title {
            font-size: clamp(2rem, 8vw, 4.5rem);
            font-weight: 800;
            line-height: 1.1;
            color: #fff;
            margin: 0 0 1rem;
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            font-size: clamp(0.9rem, 2.5vw, 1.1rem);
            color: rgba(255,255,255,0.75);
            line-height: 1.65;
            margin: 0 auto 1.75rem;
            max-width: 520px;
        }

        .hero-pricing-tag {
            margin-bottom: 2rem;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            color: rgba(255,255,255,0.7);
        }

        .hero-pricing-tag span {
            font-size: clamp(1.2rem, 4vw, 1.6rem);
            font-weight: 700;
            color: #f5c518;
        }

        .arena-hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* --- Buttons --- */
        .arena-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem 2rem;
            border-radius: 0.4rem;
            font-size: clamp(0.85rem, 2.5vw, 1rem);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            min-width: 140px;
        }

        .arena-btn-solid {
            background: #f5c518;
            color: #0a0a0a;
        }

        .arena-btn-solid:hover {
            background: #e0b515;
            transform: translateY(-2px);
        }

        .arena-btn-outline {
            background: transparent;
            color: #fff;
            border: 2px solid rgba(255,255,255,0.5);
        }

        .arena-btn-outline:hover {
            border-color: #fff;
            background: rgba(255,255,255,0.08);
            transform: translateY(-2px);
        }

        /* ============================================================
           FEATURES SECTION
        ============================================================ */
        .arena-features {
            padding: clamp(3rem, 8vw, 6rem) 1.25rem;
            background: #f9f9f7;
        }

        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 3rem;
        }

        .section-header h2 {
            font-size: clamp(1.6rem, 5vw, 2.5rem);
            font-weight: 800;
            color: #0a0a0a;
            margin: 0 0 0.75rem;
        }

        .section-header p {
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            color: #666;
            line-height: 1.65;
            margin: 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .feature-card {
            background: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 1rem;
            padding: 2rem 1.5rem;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
        }

        .feature-card i {
            font-size: 2rem;
            color: #f5c518;
            margin-bottom: 1rem;
            display: block;
        }

        .feature-card h3 {
            font-size: clamp(1rem, 2.5vw, 1.2rem);
            font-weight: 700;
            color: #0a0a0a;
            margin: 0 0 0.75rem;
        }

        .feature-card p {
            font-size: clamp(0.85rem, 2vw, 0.95rem);
            color: #666;
            line-height: 1.65;
            margin: 0;
        }

        /* ============================================================
           LOCATION SECTION
        ============================================================ */
        .arena-location {
            padding: clamp(3rem, 8vw, 6rem) 1.25rem;
            background: #fff;
        }

        .location-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        /* Stack on mobile */
        @media (max-width: 700px) {
            .location-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        .location-text .hero-badge {
            color: #333;
            border-color: #ccc;
            margin-bottom: 1rem;
        }

        .location-text h2 {
            font-size: clamp(1.6rem, 5vw, 2.25rem);
            font-weight: 800;
            color: #0a0a0a;
            margin: 0 0 1rem;
        }

        .location-text p {
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            color: #555;
            line-height: 1.7;
            margin: 0 0 1.5rem;
        }

        .location-meta {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: clamp(0.85rem, 2.2vw, 0.95rem);
            color: #555;
        }

        .meta-item i {
            color: #f5c518;
            width: 16px;
            flex-shrink: 0;
        }

        .location-map-box {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            aspect-ratio: 4 / 3;
        }

        .map-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* ============================================================
           FOOTER
        ============================================================ */
        .arena-footer {
            background: #0a0a0a;
            color: rgba(255,255,255,0.5);
            text-align: center;
            padding: 1.75rem 1.25rem;
            font-size: clamp(0.75rem, 2vw, 0.85rem);
        }

        .arena-footer p {
            margin: 0;
        }

        /* ============================================================
           NAVBAR SCROLL STATE (existing script support)
        ============================================================ */
        .arena-navbar-ford.scrolled {
            background: rgba(10,10,10,0.95);
            backdrop-filter: blur(8px);
        }

        /* ============================================================
           SMALL PHONE TWEAKS (< 400px)
        ============================================================ */
        @media (max-width: 400px) {
            .arena-btn {
                width: 100%;
                min-width: unset;
            }

            .arena-hero-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <!-- HERO -->
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

    <!-- FEATURES -->
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

    <!-- LOCATION -->
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

    <!-- FOOTER -->
    <footer class="arena-footer">
        <p>&copy; 2026 SmartCricket Arena Management System. All Rights Reserved.</p>
    </footer>

    <script>
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.arena-navbar-ford');
            if (header) {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            }
        });
    </script>
</body>
</html>
