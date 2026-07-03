<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'About Us - Makueni County Innovation Hub';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand { display: flex; align-items: center; }
        .county-logo {
            height: 60px;
            width: 60px;
            margin-right: 12px;
            background: white;
            border-radius: 50%;
            padding: 8px;
            object-fit: contain;
            border: 2px solid #FFD700;
        }
        .brand-title { font-size: 1.1rem; font-weight: 700; color: white; }
        .brand-subtitle { font-size: 0.65rem; color: #FFD700; }
        
        .nav-link { 
            color: white !important; 
            font-weight: 500; 
            transition: all 0.3s ease;
            padding: 8px 15px !important;
        }
        .nav-link:hover { color: #FFD700 !important; }
        .nav-link.active { color: #FFD700 !important; }
        
        .btn-login-nav {
            background: transparent;
            border: 1px solid white;
            border-radius: 25px;
            padding: 6px 20px !important;
            margin-left: 10px;
            transition: all 0.3s ease;
        }
        .btn-login-nav:hover { background: white; color: #1a5c3a !important; }
        
        .btn-register-nav {
            background: #FFD700;
            color: #1a5c3a !important;
            font-weight: 600;
            border-radius: 25px;
            padding: 6px 20px !important;
            margin-left: 8px;
            transition: all 0.3s ease;
        }
        .btn-register-nav:hover { background: #e6c200; }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 80px 0 60px;
            color: white;
            text-align: center;
            margin-top: 70px;
        }
        .page-header h1 { font-size: 2.8rem; font-weight: 700; }
        .page-header .lead { font-size: 1.2rem; opacity: 0.9; }
        
        /* About Section */
        .about-section { padding: 60px 0; }
        .about-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            height: 100%;
            transition: transform 0.3s ease;
        }
        .about-card:hover { transform: translateY(-5px); }
        .about-card .icon { font-size: 3rem; color: #1a5c3a; margin-bottom: 20px; }
        .about-card h4 { color: #1a5c3a; font-weight: 700; }
        
        .mission-vision {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin: 30px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .mission-vision h3 { color: #1a5c3a; font-weight: 700; }
        
        .values-grid .value-item {
            background: white;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 3px 15px rgba(0,0,0,0.06);
            height: 100%;
            transition: all 0.3s ease;
        }
        .values-grid .value-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .values-grid .value-item i { font-size: 2.5rem; color: #1a5c3a; margin-bottom: 15px; }
        .values-grid .value-item h5 { color: #1a5c3a; font-weight: 700; }
        
        .seal-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
            margin: 30px 0;
        }
        .seal-container img { height: 120px; width: auto; }
        
        footer { background: #0d3d2a; color: white; padding: 30px 0 15px; margin-top: 50px; }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; }
        footer a:hover { color: #FFD700; }
        
        @media (max-width: 768px) {
            .county-logo { height: 45px; width: 45px; }
            .page-header h1 { font-size: 1.8rem; }
            .about-card { padding: 25px; }
            .navbar-nav { text-align: center; }
        }
    </style>
</head>
<body>
    <!-- Header - Only Home and News -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="county-logo" onerror="this.style.display='none'">
                <div>
                    <div class="brand-title">MAKUENI COUNTY</div>
                    <div class="brand-subtitle">INNOVATION HUB</div>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="news.php">News</a></li>
                    <li class="nav-item"><a class="nav-link active" href="about.php">About</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="user/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link btn-login-nav" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link btn-register-nav" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-info-circle me-2"></i>About Us</h1>
            <p class="lead">Empowering innovators, entrepreneurs, and tech enthusiasts in Makueni County</p>
        </div>
    </div>

    <div class="about-section">
        <div class="container">
            <!-- Introduction -->
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="fw-bold" style="color: #1a5c3a;">Welcome to Makueni County Innovation Hub</h2>
                    <p class="lead" style="color: #555;">Your gateway to innovation, technology, and entrepreneurship in Makueni County</p>
                    <div class="seal-container">
                        <img src="assets/images/logo/seal.png" alt="Makueni County Seal" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>

            <!-- Mission & Vision -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="mission-vision" data-aos="fade-up">
                        <div class="text-center">
                            <i class="fas fa-bullseye fa-3x" style="color: #1a5c3a;"></i>
                        </div>
                        <h3 class="text-center">Our Mission</h3>
                        <p class="text-center" style="font-size: 1.05rem;">
                            To empower innovators, entrepreneurs, and tech enthusiasts in Makueni County to 
                            transform ideas into reality through mentorship, training, and funding opportunities, 
                            fostering a sustainable culture of innovation and economic growth.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mission-vision" data-aos="fade-up" data-aos-delay="100">
                        <div class="text-center">
                            <i class="fas fa-eye fa-3x" style="color: #1a5c3a;"></i>
                        </div>
                        <h3 class="text-center">Our Vision</h3>
                        <p class="text-center" style="font-size: 1.05rem;">
                            To be the leading innovation ecosystem in Eastern Africa, where every idea finds 
                            the support, resources, and community it needs to become a reality that transforms 
                            lives and communities.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Our Story -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="about-card" data-aos="fade-up">
                        <div class="text-center">
                            <i class="fas fa-history icon"></i>
                            <h4>Our Story</h4>
                        </div>
                        <p style="font-size: 1.05rem; line-height: 1.8;">
                            The Makueni County Innovation Hub was established as a flagship initiative by 
                            the County Government of Makueni to harness the power of technology and innovation 
                            for socio-economic transformation. Recognizing the immense potential of the county's 
                            youth and entrepreneurs, the hub was created to provide a conducive environment for 
                            idea development, skills enhancement, and business growth.
                        </p>
                        <p style="font-size: 1.05rem; line-height: 1.8;">
                            Since its inception, the hub has been at the forefront of driving digital literacy, 
                            fostering entrepreneurship, and creating meaningful employment opportunities for the 
                            residents of Makueni County. Through strategic partnerships, capacity building programs, 
                            and state-of-the-art facilities, the hub continues to be a beacon of hope and a catalyst 
                            for innovation in the region.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Core Values -->
            <div class="row mt-5">
                <div class="col-12 text-center mb-4">
                    <h2 class="fw-bold" style="color: #1a5c3a;">Our Core Values</h2>
                    <p class="text-muted">The principles that guide everything we do</p>
                </div>
                <?php
                $values = [
                    ['icon' => 'fa-lightbulb', 'title' => 'Innovation', 'desc' => 'We foster creativity and embrace new ideas to drive positive change.'],
                    ['icon' => 'fa-handshake', 'title' => 'Integrity', 'desc' => 'We act with honesty, transparency, and accountability in all our dealings.'],
                    ['icon' => 'fa-users', 'title' => 'Collaboration', 'desc' => 'We believe in the power of partnerships and community engagement.'],
                    ['icon' => 'fa-graduation-cap', 'title' => 'Excellence', 'desc' => 'We strive for the highest quality in everything we do.'],
                    ['icon' => 'fa-seedling', 'title' => 'Sustainability', 'desc' => 'We create lasting impact through sustainable solutions.'],
                    ['icon' => 'fa-heart', 'title' => 'Inclusivity', 'desc' => 'We welcome and empower everyone regardless of background.']
                ];
                ?>
                <div class="row values-grid g-4">
                    <?php foreach($values as $value): ?>
                    <div class="col-md-4">
                        <div class="value-item" data-aos="fade-up">
                            <i class="fas <?php echo $value['icon']; ?>"></i>
                            <h5><?php echo $value['title']; ?></h5>
                            <p class="text-muted small"><?php echo $value['desc']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- What We Offer -->
            <div class="row mt-5">
                <div class="col-12 text-center mb-4">
                    <h2 class="fw-bold" style="color: #1a5c3a;">What We Offer</h2>
                    <p class="text-muted">Our comprehensive services designed to support your innovation journey</p>
                </div>
                <?php
                $offerings = [
                    ['icon' => 'fa-laptop-code', 'title' => 'Computer Classes', 'desc' => 'Learn essential digital skills through our comprehensive training programs.'],
                    ['icon' => 'fa-microphone-alt', 'title' => 'AJIRY Studio', 'desc' => 'Professional recording studio for podcasts, music, and content creation.'],
                    ['icon' => 'fa-calendar-alt', 'title' => 'Events & Networking', 'desc' => 'Connect with innovators, entrepreneurs, and industry experts.'],
                    ['icon' => 'fa-hand-holding-heart', 'title' => 'Mentorship Programs', 'desc' => 'Access to experienced mentors who guide you through your innovation journey.'],
                    ['icon' => 'fa-handshake', 'title' => 'Funding Opportunities', 'desc' => 'Information and support for funding your innovative ideas and startups.'],
                    ['icon' => 'fa-file-alt', 'title' => 'Document Services', 'desc' => 'Professional CV writing, report writing, and document preparation services.']
                ];
                ?>
                <div class="row g-4">
                    <?php foreach($offerings as $offering): ?>
                    <div class="col-md-4">
                        <div class="about-card" data-aos="fade-up">
                            <div class="text-center">
                                <i class="fas <?php echo $offering['icon']; ?>" style="font-size: 2.5rem; color: #1a5c3a;"></i>
                            </div>
                            <h5 class="text-center" style="color: #1a5c3a; font-weight: 700;"><?php echo $offering['title']; ?></h5>
                            <p class="text-center text-muted small"><?php echo $offering['desc']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold">Makueni County Innovation Hub</h6>
                    <p class="small opacity-75">Empowering innovators, entrepreneurs, and tech enthusiasts.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> County Government of Makueni. All rights reserved.</p>
                    <p class="small mt-1">Empowering Innovators, Transforming Lives | Wote, Makueni County</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true, offset: 100 });
    </script>
</body>
</html>