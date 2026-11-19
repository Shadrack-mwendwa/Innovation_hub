<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'config/database.php';

// ========== AUTO-UPDATE EVENT STATUSES ==========
function updateEventStatuses($pdo) {
    $now = date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("
        UPDATE events 
        SET status = 'ongoing' 
        WHERE status = 'upcoming' 
        AND CONCAT(start_date, ' ', start_time) <= ? 
        AND CONCAT(end_date, ' ', end_time) >= ?
    ");
    $stmt->execute([$now, $now]);
    
    $stmt = $pdo->prepare("
        UPDATE events 
        SET status = 'completed' 
        WHERE status IN ('upcoming', 'ongoing') 
        AND CONCAT(end_date, ' ', end_time) < ?
    ");
    $stmt->execute([$now]);
    
    $stmt = $pdo->prepare("
        UPDATE events 
        SET status = 'completed' 
        WHERE status IN ('upcoming', 'ongoing') 
        AND end_date != '0000-00-00'
        AND CONCAT(end_date, ' ', end_time) < ?
    ");
    $stmt->execute([$now]);
}

updateEventStatuses($pdo);

// ========== GET UPCOMING & ONGOING EVENTS ==========
$upcoming_events = [];
$ongoing_events = [];

try {
    $stmt = $pdo->query("
        SELECT * FROM events 
        WHERE deleted_at IS NULL 
        AND status = 'upcoming' 
        AND CONCAT(start_date, ' ', start_time) > NOW()
        ORDER BY start_datetime ASC 
        LIMIT 10
    ");
    $upcoming_events = $stmt->fetchAll();
} catch(PDOException $e) {
    $upcoming_events = [];
}

try {
    $stmt = $pdo->query("
        SELECT * FROM events 
        WHERE deleted_at IS NULL 
        AND status = 'ongoing' 
        AND CONCAT(start_date, ' ', start_time) <= NOW() 
        AND CONCAT(end_date, ' ', end_time) >= NOW()
        ORDER BY start_datetime ASC 
        LIMIT 10
    ");
    $ongoing_events = $stmt->fetchAll();
} catch(PDOException $e) {
    $ongoing_events = [];
}

// ========== GET ACTIVE EVENTS COUNT ==========
$totalActiveEvents = 0;
$totalOngoingEvents = 0;
$totalUpcomingEvents = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL AND status IN ('upcoming', 'ongoing')");
    $totalActiveEvents = $stmt->fetchColumn();
} catch(PDOException $e) {
    $totalActiveEvents = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL AND status = 'ongoing'");
    $totalOngoingEvents = $stmt->fetchColumn();
} catch(PDOException $e) {
    $totalOngoingEvents = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL AND status = 'upcoming'");
    $totalUpcomingEvents = $stmt->fetchColumn();
} catch(PDOException $e) {
    $totalUpcomingEvents = 0;
}

// ========== GET LATEST NEWS ==========
$latest_news = [];
try {
    $stmt = $pdo->query("
        SELECT * FROM news 
        WHERE deleted_at IS NULL AND status = 'published' 
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $latest_news = $stmt->fetchAll();
} catch(PDOException $e) {
    $latest_news = [];
}

function getCategoryBadgeIndex($category) {
    $colors = [
        'agriculture' => 'success',
        'county_attorney' => 'primary',
        'devolution' => 'info',
        'finance' => 'warning',
        'gender' => 'danger',
        'health' => 'danger',
        'ict' => 'primary',
        'infrastructure' => 'secondary',
        'lands' => 'success',
        'trade' => 'warning',
        'water' => 'info',
        'governor' => 'dark',
        'general' => 'secondary'
    ];
    return $colors[$category] ?? 'secondary';
}

function getCategoryLabelIndex($category) {
    $labels = [
        'agriculture' => 'Agriculture & Livestock',
        'county_attorney' => 'County Attorney',
        'devolution' => 'Devolution & Public Participation',
        'finance' => 'Finance & Planning',
        'gender' => 'Gender & Social Services',
        'health' => 'Health Services',
        'ict' => 'ICT & Education',
        'infrastructure' => 'Infrastructure & Energy',
        'lands' => 'Lands & Environment',
        'trade' => 'Trade & Tourism',
        'water' => 'Water & Sanitation',
        'governor' => 'Office of the Governor',
        'general' => 'General'
    ];
    return $labels[$category] ?? ucfirst(str_replace('_', ' ', $category));
}

// ========== REAL-TIME STATISTICS ==========
$totalEvents = $totalActiveEvents;
$totalUsers = 0;
$totalGraduates = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'");
    $totalUsers = $stmt->fetchColumn();
} catch(PDOException $e) {
    $totalUsers = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM class_registrations WHERE certificate_issued = 1");
    $totalGraduates = $stmt->fetchColumn();
} catch(PDOException $e) {
    $totalGraduates = 0;
}

// ========== COMPUTER CLASSES ==========
$active_classes = [];
try {
    $stmt = $pdo->query("SELECT * FROM computer_classes WHERE deleted_at IS NULL ORDER BY start_date ASC LIMIT 10");
    $active_classes = $stmt->fetchAll();
} catch(PDOException $e) {
    $active_classes = [];
}

$hero_backgrounds = [
    '/innovation_hub/assets/images/slider/slide1.jpg',
    '/innovation_hub/assets/images/slider/slide2.jpg',
    '/innovation_hub/assets/images/slider/slide3.jpg'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makueni County Innovation Hub</title>
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
            padding: 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .navbar-brand { display: flex; align-items: center; }
        .county-logo {
            height: 50px;
            width: 50px;
            margin-right: 12px;
            background: white;
            border-radius: 50%;
            padding: 6px;
            object-fit: contain;
            border: 2px solid #FFD700;
            transition: transform 0.3s ease;
        }
        .county-logo:hover {
            transform: scale(1.05);
        }
        .brand-title { font-size: 1rem; font-weight: 700; color: white; }
        .brand-subtitle { font-size: 0.6rem; color: #FFD700; }
        
        /* Modern Dropdown Menu - Hover Effect */
        .nav-item {
            position: relative;
        }
        
        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border: none;
            padding: 8px 0;
            margin-top: 10px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            display: block;
            transform: translateY(-10px);
            min-width: 220px;
        }
        
        /* Modern hover effect - shows dropdown on hover */
        .nav-item:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            padding: 10px 25px;
            font-size: 14px;
            transition: all 0.2s ease;
            position: relative;
            color: #333;
        }
        
        .dropdown-item i {
            width: 25px;
            margin-right: 8px;
            color: #1a5c3a;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            color: white;
            padding-left: 30px;
        }
        
        .dropdown-item:hover i {
            color: white;
        }
        
        .dropdown-toggle::after {
            transition: transform 0.3s ease;
        }
        
        .nav-item:hover .dropdown-toggle::after {
            transform: rotate(180deg);
        }
        
        .nav-link { 
            color: white !important; 
            font-weight: 500;
            font-size: 0.85rem;
            padding: 6px 12px !important;
            transition: all 0.2s ease;
            position: relative;
            white-space: nowrap;
        }
        
        .nav-link:hover { 
            color: #FFD700 !important;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            color: #FFD700 !important;
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 2px;
            background: #FFD700;
            border-radius: 2px;
        }
        
        .btn-login-nav {
            background: transparent;
            border: 1px solid white;
            border-radius: 20px;
            padding: 4px 15px !important;
            margin-left: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-login-nav:hover {
            background: white;
            color: #1a5c3a !important;
            transform: translateY(-2px);
        }
        
        .btn-register-nav {
            background: #FFD700;
            color: #1a5c3a !important;
            font-weight: 600;
            border-radius: 20px;
            padding: 4px 15px !important;
            margin-left: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-register-nav:hover {
            background: #e6c200;
            transform: translateY(-2px);
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            margin-top: 60px;
            min-height: 85vh;
            display: flex;
            align-items: center;
            text-align: center;
            color: white;
        }
        
        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            transition: opacity 1.5s ease-in-out;
            z-index: 0;
        }
        .hero-background.active { opacity: 1; }
        .hero-background:not(.active) { opacity: 0; }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(26, 92, 58, 0.85);
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .hero-content h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
        .hero-content .lead { font-size: 1.1rem; margin-bottom: 1rem; opacity: 0.9; }
        
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .hero-stat-box { text-align: center; }
        .hero-stat-box h3 { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .hero-stat-box p { font-size: 0.85rem; margin: 0; opacity: 0.9; }
        
        /* Events Section */
        .events-section {
            padding: 60px 0;
            background: white;
        }
        
        .events-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .event-category {
            margin-bottom: 40px;
        }
        
        .category-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a5c3a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #FFD700;
            display: inline-block;
        }
        
        .event-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .event-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            transition: all 0.2s ease;
        }
        
        .event-item:hover {
            background: #f9f9f9;
            padding-left: 10px;
        }
        
        .event-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .event-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        
        .event-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            white-space: nowrap;
        }
        
        .status-upcoming {
            background: #17a2b8;
            color: white;
        }
        .status-ongoing {
            background: #28a745;
            color: white;
        }
        .status-completed {
            background: #6c757d;
            color: white;
        }
        
        .event-date {
            font-size: 0.85rem;
            color: #888;
            margin-top: 5px;
        }
        
        .event-date i {
            margin-right: 6px;
            color: #1a5c3a;
        }
        
        .empty-events {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 16px;
            color: #666;
        }
        
        .book-event-section {
            text-align: center;
            padding: 40px 0;
            background: #f8f9fa;
            margin-top: 20px;
        }
        
        .btn-book-event {
            background: #FFD700;
            color: #1a5c3a;
            border-radius: 50px;
            padding: 14px 40px;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-book-event:hover {
            background: #e6c200;
            transform: translateY(-3px);
            color: #1a5c3a;
        }
        
        /* News Section */
        .news-section {
            padding: 60px 0;
            background: #f8f9fa;
        }
        
        .news-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .news-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .news-card .card-body {
            padding: 20px;
        }
        
        .news-card .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a5c3a;
        }
        
        /* Classes Section */
        .classes-section {
            padding: 80px 0;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/background/class-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }
        
        .classes-section .section-title {
            color: white;
        }
        
        .classes-section .section-title:after {
            background: #FFD700;
        }
        
        .classes-section .text-muted {
            color: rgba(255,255,255,0.8) !important;
        }
        
        .class-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 25px;
            overflow: hidden;
            height: 100%;
            background: white;
        }
        
        .class-card:hover { 
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        }
        
        .class-icon {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 25px;
            text-align: center;
            color: white;
        }
        
        /* AJIRY Section */
        .ajiry-section {
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }
        
        .ajiry-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('assets/images/background/studio-bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.7;
            z-index: 0;
        }
        
        .ajiry-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1;
        }
        
        .ajiry-section .container {
            position: relative;
            z-index: 2;
        }
        
        .ajiry-section .section-title {
            color: white;
        }
        
        .ajiry-section .section-title:after {
            background: #FFD700;
        }
        
        .ajiry-section .text-muted {
            color: rgba(255,255,255,0.8) !important;
        }
        
        .ajiry-main-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
            backdrop-filter: blur(2px);
        }
        
        .ajiry-main-card:hover {
            transform: translateY(-5px);
        }
        
        .ajiry-service-block {
            padding: 40px 30px;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .ajiry-service-block:first-child {
            border-right: 1px solid rgba(0,0,0,0.1);
        }
        
        .ajiry-icon {
            width: 80px;
            height: 80px;
            background: rgba(26,92,58,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .ajiry-icon i {
            font-size: 35px;
            color: #1a5c3a;
        }
        
        .ajiry-service-block h4 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a5c3a;
            margin-bottom: 15px;
        }
        
        .service-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            text-align: left;
            display: inline-block;
        }
        
        .service-list li {
            padding: 8px 0;
            font-size: 0.9rem;
            color: #555;
        }
        
        .office-visit-note {
            background: #fff3cd;
            border-left: 4px solid #FFD700;
            padding: 12px 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #856404;
            text-align: center;
        }
        
        /* Modal Styles */
        .modal-content {
            border-radius: 20px;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            color: white;
            border: none;
            padding: 20px;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: #1a5c3a;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 12px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #1a5c3a;
            box-shadow: 0 0 0 0.2rem rgba(26,92,58,0.25);
        }
        
        .btn-submit-booking {
            background: #1a5c3a;
            color: white;
            border-radius: 30px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-submit-booking:hover {
            background: #0a3d2a;
            transform: translateY(-2px);
        }
        
        footer { background: #0d3d2a; color: white; padding: 30px 0 15px; margin-top: 50px; }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; transition: all 0.2s ease; }
        footer a:hover { color: #FFD700; transform: translateX(3px); }
        .footer-links a { margin: 0 12px; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background: rgba(255,255,255,0.9);
            border-radius: 16px;
            color: #666;
        }
        
        /* Mobile responsive - show dropdown on click for mobile */
        @media (max-width: 992px) {
            .navbar-nav {
                flex-wrap: wrap;
                justify-content: center;
            }
            .nav-link {
                font-size: 0.8rem;
                padding: 5px 10px !important;
            }
            .dropdown-menu {
                opacity: 1;
                visibility: visible;
                transform: none;
                position: static;
                float: none;
                width: auto;
                margin-top: 0;
                background-color: transparent;
                box-shadow: none;
                padding-left: 20px;
            }
            .dropdown-menu .dropdown-item {
                color: white;
                padding: 8px 15px;
            }
            .dropdown-menu .dropdown-item:hover {
                background: rgba(255,255,255,0.1);
                color: #FFD700;
            }
            .dropdown-menu .dropdown-item i {
                color: #FFD700;
            }
            .dropdown-toggle::after {
                display: inline-block;
            }
        }
        
        @media (max-width: 768px) {
            .county-logo { height: 40px; width: 40px; }
            .hero-content h1 { font-size: 1.6rem; }
            .hero-stats { gap: 20px; }
            .hero-stat-box h3 { font-size: 1.4rem; }
            .event-row { flex-direction: column; align-items: flex-start; }
            .event-status { align-self: flex-start; }
            .category-title { font-size: 1.2rem; }
            .ajiry-service-block:first-child {
                border-right: none;
                border-bottom: 1px solid rgba(0,0,0,0.1);
            }
            .ajiry-service-block {
                padding: 30px 20px;
            }
            .classes-section {
                background-attachment: scroll;
            }
            .news-image {
                height: 150px;
            }
            .nav-link {
                font-size: 0.7rem;
                padding: 4px 8px !important;
            }
            .btn-login-nav, .btn-register-nav {
                padding: 3px 10px !important;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
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
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    
                    <!-- Classes Dropdown - Hover -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button">Classes</a>
                        <ul class="dropdown-menu">
                            <?php foreach($active_classes as $class): ?>
                            <li><a class="dropdown-item" href="register_class.php?id=<?php echo $class['id']; ?>">
                                <i class="fas fa-laptop-code"></i> <?php echo htmlspecialchars($class['class_name']); ?>
                            </a></li>
                            <?php endforeach; ?>
                            <?php if(empty($active_classes)): ?>
                            <li><a class="dropdown-item" href="register_class.php"><i class="fas fa-plus-circle"></i> Register for a Class</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- AJIRY Dropdown - Hover -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button">AJIRY</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#ajiry"><i class="fas fa-microphone-alt"></i> Studio Booking</a></li>
                            <li><a class="dropdown-item" href="ajiry/check_booking_status.php"><i class="fas fa-calendar-check"></i> Check Status</a></li>
                        </ul>
                    </li>
                    
                    <!-- Events Dropdown - Hover -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button">Events</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#ongoing"><i class="fas fa-play-circle"></i> Ongoing Events</a></li>
                            <li><a class="dropdown-item" href="#upcoming"><i class="fas fa-calendar-week"></i> Incoming Events</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bookingModal"><i class="fas fa-calendar-plus"></i> Book Your Event</a></li>
                            <li><a class="dropdown-item" href="event_check_status.php"><i class="fas fa-calendar-check"></i> Check Status</a></li>
                        </ul>
                    </li>
               
                    <!-- News -->
                    <li class="nav-item">
                        <a class="nav-link" href="news.php">News</a>
                    </li>
                    
                    <!-- Feedback Button -->
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                            Feedback
                        </a>
                    </li>
                    
                    <!-- About Us -->
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    
                    <!-- Contact Us Button -->
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">
                            Contact Us
                        </a>
                    </li>
                    
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

    <main>
        <!-- HERO SECTION -->
        <section class="hero-section" id="home">
            <?php foreach($hero_backgrounds as $index => $bg): ?>
            <div class="hero-background" style="background-image: url('<?php echo $bg; ?>');" data-index="<?php echo $index; ?>"></div>
            <?php endforeach; ?>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <div class="container">
                    <span class="badge bg-warning text-dark mb-3 px-3 py-1 rounded-pill">
                        <i class="fas fa-map-marker-alt me-1"></i> Official County Government Initiative
                    </span>
                    <h1>Welcome to <span style="color: #FFD700;">Makueni County</span><br>Innovation Hub</h1>
                    <p class="lead">Empowering innovators, entrepreneurs, and tech enthusiasts to transform ideas into reality.</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="#upcoming" class="btn btn-light px-4 rounded-pill">View Events</a>
                        <a href="#classes" class="btn btn-warning px-4 rounded-pill">Computer Classes</a>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat-box">
                            <h3><?php echo max(0, $totalActiveEvents); ?>+</h3>
                            <p>Events</p>
                        </div>
                        <div class="hero-stat-box"><h3><?php echo max(0, $totalUsers); ?>+</h3><p>Members</p></div>
                        <div class="hero-stat-box"><h3><?php echo max(0, $totalGraduates); ?>+</h3><p>Graduates</p></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== UPCOMING EVENTS SECTION ==================== -->
        <section class="events-section" id="upcoming">
            <div class="container">
                <div class="events-container">
                    <div class="event-category">
                        <h3 class="category-title">
                            <i class="fas fa-calendar-week me-2"></i>Incoming Events 
                            <?php if($totalUpcomingEvents > 0): ?>
                                <span class="badge bg-info ms-2"><?php echo $totalUpcomingEvents; ?></span>
                            <?php endif; ?>
                        </h3>
                        <?php if(empty($upcoming_events)): ?>
                            <div class="empty-events">
                                <i class="fas fa-calendar-alt fa-2x mb-2 d-block" style="color: #1a5c3a;"></i>
                                <p>No upcoming events at the moment</p>
                            </div>
                        <?php else: ?>
                            <ul class="event-list">
                                <?php foreach($upcoming_events as $event): ?>
                                <li class="event-item">
                                    <div class="event-row">
                                        <span class="event-name"><?php echo htmlspecialchars($event['title']); ?></span>
                                        <span class="event-status status-upcoming">Upcoming</span>
                                    </div>
                                    <div class="event-date">
                                        <i class="fas fa-calendar"></i> <?php echo date('F d, Y', strtotime($event['start_date'])); ?><br>
                                        <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['start_time'])) . ' - ' . date('g:i A', strtotime($event['end_time'])); ?>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== ONGOING EVENTS SECTION ==================== -->
        <section class="events-section" id="ongoing" style="background: white;">
            <div class="container">
                <div class="events-container">
                    <div class="event-category">
                        <h3 class="category-title">
                            <i class="fas fa-play-circle me-2"></i>Ongoing Events 
                            <?php if($totalOngoingEvents > 0): ?>
                                <span class="badge bg-success ms-2"><?php echo $totalOngoingEvents; ?></span>
                            <?php endif; ?>
                        </h3>
                        <?php if(empty($ongoing_events)): ?>
                            <div class="empty-events">
                                <i class="fas fa-calendar-check fa-2x mb-2 d-block" style="color: #1a5c3a;"></i>
                                <p>No ongoing events at the moment</p>
                            </div>
                        <?php else: ?>
                            <ul class="event-list">
                                <?php foreach($ongoing_events as $event): ?>
                                <li class="event-item">
                                    <div class="event-row">
                                        <span class="event-name"><?php echo htmlspecialchars($event['title']); ?></span>
                                        <span class="event-status status-ongoing">Ongoing</span>
                                    </div>
                                    <div class="event-date">
                                        <i class="fas fa-calendar"></i> <?php echo date('F d, Y', strtotime($event['start_date'])); ?><br>
                                        <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['start_time'])) . ' - ' . date('g:i A', strtotime($event['end_time'])); ?>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== BOOK YOUR EVENT BUTTON ==================== -->
        <div class="book-event-section">
            <div class="container">
                <h3 class="mb-3" style="color: #1a5c3a;">Want to host your event at Greenpark?</h3>
                <button class="btn-book-event" data-bs-toggle="modal" data-bs-target="#bookingModal">
                    <i class="fas fa-calendar-plus me-2"></i> Book Your Event Now
                </button>
            </div>
        </div>

        <!-- ==================== NEWS SECTION ==================== -->
        <section class="news-section" id="news">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="fw-bold" style="color: #1a5c3a;"><i class="fas fa-newspaper me-2"></i>Latest News</h2>
                    <p class="text-muted">Stay updated with what's happening at the Makueni county</p>
                </div>
                
                <?php if(empty($latest_news)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No news articles yet. Check back soon!</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach($latest_news as $news): 
                            $title_color = $news['title_color'] ?? '#1a5c3a';
                            $title_font_size = $news['title_font_size'] ?? '22px';
                            $image_height = $news['image_height'] ?? '200px';
                            $category_color = getCategoryBadgeIndex($news['category'] ?? 'general');
                            $category_label = getCategoryLabelIndex($news['category'] ?? 'general');
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <?php if($news['featured_image']): ?>
                                    <img src="<?php echo htmlspecialchars($news['featured_image']); ?>" 
                                         class="card-img-top" 
                                         style="height: <?php echo $image_height; ?>; object-fit: cover; border-radius: 16px 16px 0 0;" 
                                         alt="<?php echo htmlspecialchars($news['title']); ?>">
                                <?php else: ?>
                                    <div class="card-img-top d-flex align-items-center justify-content-center bg-light" 
                                         style="height: <?php echo $image_height; ?>; border-radius: 16px 16px 0 0; background: #f8f9fa;">
                                        <i class="fas fa-newspaper fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <span class="badge bg-<?php echo $category_color; ?> mb-2">
                                        <?php echo $category_label; ?>
                                    </span>
                                    <h5 class="card-title fw-bold" style="color: <?php echo $title_color; ?>; font-size: <?php echo $title_font_size; ?>;">
                                        <?php echo htmlspecialchars($news['title']); ?>
                                    </h5>
                                    <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($news['summary'] ?? strip_tags($news['content']), 0, 120)); ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($news['created_at'])); ?></small>
                                        <a href="news_detail.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-success rounded-pill px-3">Read More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="news.php" class="btn btn-success rounded-pill px-4 py-2">
                            View All News <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ==================== COMPUTER CLASSES SECTION ==================== -->
        <section class="classes-section" id="classes">
            <div class="container">
                <h2 class="section-title">Computer Classes</h2>
                <p class="text-center text-white-50 mb-5">Learn digital skills and get certified</p>
                
                <?php if(empty($active_classes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-laptop-code fa-3x mb-3 d-block" style="color: #1a5c3a;"></i>
                        <p>No classes available at the moment. Please check back later.</p>
                    </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach($active_classes as $class): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="class-card">
                            <div class="class-icon">
                                <i class="fas fa-laptop-code fa-3x"></i>
                            </div>
                            <div class="p-3">
                                <h5><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                <p class="text-muted small"><?php echo htmlspecialchars(substr($class['description'] ?? '', 0, 80)); ?>...</p>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">
                                            <i class="fas fa-calendar me-1"></i> Starts: <?php echo date('M d, Y', strtotime($class['start_date'] ?? 'now')); ?>
                                        </span>
                                        <span class="fw-bold text-success">KES <?php echo number_format($class['fee_amount'] ?? 1000, 2); ?></span>
                                    </div>
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        <a href="register_class.php?id=<?php echo $class['id']; ?>" class="btn btn-success btn-sm rounded-pill px-3 w-100">Enroll Now</a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-warning btn-sm rounded-pill px-3 w-100">Login to Enroll</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ==================== AJIRY SECTION ==================== -->
        <section class="ajiry-section" id="ajiry">
            <div class="container">
                <h2 class="section-title">AJIRY Services</h2>
                <p class="text-center text-white-50 mb-5">Your creative partner for studio and professional document services</p>
                
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="ajiry-main-card">
                            <div class="row g-0">
                                <div class="col-md-6">
                                    <div class="ajiry-service-block">
                                        <div class="ajiry-icon"><i class="fas fa-microphone-alt"></i></div>
                                        <h4>AJIRY Studio</h4>
                                        <p class="text-muted">Professional recording studio for podcasts, music, voice-overs, and content creation.</p>
                                        <ul class="service-list">
                                            <li><i class="fas fa-check-circle text-success me-2"></i> Professional sound equipment</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i> Soundproof recording room</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i> Audio mixing & mastering</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i> Podcast production support</li>
                                        </ul>
                                        <a href="ajiry/studio_book.php" class="btn btn-success rounded-pill px-4 mt-3">
                                            <i class="fas fa-calendar-plus me-2"></i> Book Studio
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ajiry-service-block">
                                        <div class="ajiry-icon"><i class="fas fa-file-alt"></i></div>
                                        <h4>Document Services</h4>
                                        <p class="text-muted">Professional document preparation and editing services.</p>
                                        <ul class="service-list">
                                            <li><i class="fas fa-check-circle text-success me-2"></i> CV / Resume Writing</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i> Report Writing & Editing</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i> Office & Official Letters</li>
                                            <li><i class="fas fa-check-circle text-success me-2"></i> Document Proofreading</li>
                                        </ul>
                                        <div class="office-visit-note">
                                            <i class="fas fa-map-marker-alt me-2"></i> 
                                            <strong>Visit our AJIRY office</strong> for CV, report, and letter services
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ==================== BOOKING MODAL ==================== -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Book Your Event at Greenpark</h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="submit_venue_booking.php">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Event Name *</label>
                                <input type="text" name="event_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Event Date *</label>
                                <input type="date" name="event_date" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Start Time *</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">End Time *</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Organizer Name *</label>
                                <input type="text" name="organizer_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number 1 *</label>
                                <input type="tel" name="phone_primary" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number 2</label>
                                <input type="tel" name="phone_secondary" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required>
                                <small class="text-muted">We'll send your booking confirmation to this email</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Number of People *</label>
                                <input type="number" name="number_of_people" class="form-control" min="1" max="200" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Event Type</label>
                                <select name="event_type" class="form-select">
                                    <option value="wedding">Wedding</option>
                                    <option value="conference">Conference/Seminar</option>
                                    <option value="birthday">Birthday Party</option>
                                    <option value="corporate">Corporate Event</option>
                                    <option value="workshop">Workshop/Training</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Special Requirements</label>
                                <textarea name="special_requirements" class="form-control" rows="3" placeholder="Any special requests (catering, equipment, setup, etc.)"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit-booking">
                            <i class="fas fa-check-circle me-2"></i> Submit Booking Request
                        </button>
                        <p class="text-muted small text-center mt-3 mb-0">
                            <i class="fas fa-info-circle me-1"></i> A confirmation email will be sent to your email address
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== FEEDBACK MODAL ==================== -->
    <div class="modal fade feedback-modal" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fas fa-comment me-2"></i>Share Your Feedback</h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="submit_feedback.php">
                        <div class="mb-3">
                            <label class="form-label">Your Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rating *</label>
                            <select name="rating" class="form-select" required>
                                <option value="5">★★★★★ (Excellent)</option>
                                <option value="4">★★★★☆ (Good)</option>
                                <option value="3">★★★☆☆ (Average)</option>
                                <option value="2">★★☆☆☆ (Poor)</option>
                                <option value="1">★☆☆☆☆ (Very Poor)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Your Message *</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Tell us about your experience..." required></textarea>
                        </div>
                        <button type="submit" class="btn-submit-booking">
                            <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== FOOTER ==================== -->
    <footer id="contact">
        <div class="container">
            <div class="row">
                <div class="col-md-5">
                    <h6 class="fw-bold">Makueni County Innovation Hub</h6>
                    <p class="small opacity-75">Empowering innovators, entrepreneurs, and tech enthusiasts in Makueni County to transform ideas into reality through mentorship, training, and funding opportunities.</p>
                </div>
                <div class="col-md-3">
                    <h6 class="fw-bold">Quick Links</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="#upcoming">Incoming Events</a></li>
                        <li class="mb-2"><a href="#ongoing">Ongoing Events</a></li>
                        <li class="mb-2"><a href="#classes">Computer Classes</a></li>
                        <li class="mb-2"><a href="#ajiry">AJIRY Services</a></li>
                        <li class="mb-2"><a href="news.php">News</a></li>
                        <li class="mb-2"><a href="about.php">About Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold">Contact Information</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-warning"></i> P.O. Box 78 – 90-300 Wote, Makueni</li>
                        <li class="mb-2"><i class="fas fa-phone me-2 text-warning"></i> 0792953121</li>
                        <li class="mb-2"><i class="fas fa-mobile-alt me-2 text-warning"></i> 0703125815</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2 text-warning"></i> info@makueni.go.ke</li>
                    </ul>
                </div>
            </div>
            <hr class="bg-white opacity-25 my-3">
            <div class="text-center small">
                <p>&copy; <?php echo date('Y'); ?> County Government of Makueni. All rights reserved.</p>
                <p class="mt-1">Empowering Innovators, Transforming Lives | Wote,Green Park, Makueni County</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true, offset: 100 });
        
        let currentBg = 0;
        const backgrounds = document.querySelectorAll('.hero-background');
        const totalBgs = backgrounds.length;
        function showBackground(index) {
            if (index < 0) index = totalBgs - 1;
            if (index >= totalBgs) index = 0;
            backgrounds.forEach(bg => bg.classList.remove('active'));
            backgrounds[index].classList.add('active');
            currentBg = index;
        }
        if (totalBgs > 0) {
            showBackground(0);
            setInterval(() => { showBackground(currentBg + 1); }, 5000);
        }
    </script>
</body>
</html>