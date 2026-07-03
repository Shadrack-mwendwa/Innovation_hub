<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is NOT an admin (admins cannot access user dashboard)
if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$user_id = $_SESSION['user_id'];

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_feedback = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE user_id = ? AND status = 'approved'");
$stmt->execute([$user_id]);
$approved_feedback = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM certifications WHERE user_id = ? AND status = 'issued'");
$stmt->execute([$user_id]);
$certificates = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM class_registrations WHERE user_id = ?");
$stmt->execute([$user_id]);
$registered_classes = $stmt->fetchColumn();

// Get active classes for dropdown
$active_classes = [];
try {
    $stmt = $pdo->query("SELECT * FROM computer_classes WHERE deleted_at IS NULL ORDER BY start_date ASC LIMIT 10");
    $active_classes = $stmt->fetchAll();
} catch(PDOException $e) {
    $active_classes = [];
}

// Get recent activities
$stmt = $pdo->prepare("
    SELECT 'feedback' as type, message as name, created_at 
    FROM feedback 
    WHERE user_id = ? 
    UNION ALL 
    SELECT 'class' as type, c.class_name as name, r.created_at 
    FROM class_registrations r 
    JOIN computer_classes c ON r.class_id = c.id 
    WHERE r.user_id = ?
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id, $user_id]);
$activities = $stmt->fetchAll();

$page_title = 'My Dashboard - Makueni County Innovation Hub';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #2d6a4f;
            --secondary-green: #40916c;
            --light-green: #74c69d;
            --dark-green: #1b4332;
            --accent: #ffb703;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
        }
        
        /* Header Styles */
        .county-header {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%);
        }
        
        .county-logo {
            width: 50px;
            height: 50px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .county-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .navbar-brand-custom {
            color: white !important;
            font-weight: bold;
        }
        
        .gov-badge {
            background-color: var(--accent);
            color: var(--dark-green);
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .nav-link-custom {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-link-custom:hover {
            color: var(--accent) !important;
            transform: translateY(-2px);
        }
        
        /* Dropdown Menu Styles - Hover Effect */
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
            background: white;
        }
        
        /* Hover effect - shows dropdown on hover */
        .nav-item:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            padding: 10px 25px;
            font-size: 14px;
            transition: all 0.2s ease;
            color: #333;
        }
        
        .dropdown-item i {
            width: 25px;
            margin-right: 8px;
            color: var(--primary-green);
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, var(--dark-green), var(--primary-green));
            color: white;
            padding-left: 30px;
        }
        
        .dropdown-item:hover i {
            color: white;
        }
        
        /* Dropdown arrow indicator */
        .dropdown-toggle::after {
            transition: transform 0.3s ease;
        }
        
        .nav-item:hover .dropdown-toggle::after {
            transform: rotate(180deg);
        }
        
        /* Make dropdown toggle not navigate */
        .dropdown-toggle {
            cursor: pointer;
        }
        
        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-accent {
            background-color: var(--accent);
            color: var(--dark-green);
            border: none;
            font-weight: bold;
        }
        
        .btn-accent:hover {
            background-color: #e6a800;
            color: var(--dark-green);
        }
        
        .footer {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%);
            color: white;
        }
        
        .fs-10 {
            font-size: 10px;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%);
        }
        
        .quick-action-btn {
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            transform: translateX(5px);
        }
        
        /* Mobile responsive dropdown fix */
        @media (max-width: 768px) {
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
            }
            .dropdown-menu .dropdown-item:hover {
                background: rgba(255,255,255,0.1);
                color: var(--accent);
            }
            .dropdown-menu .dropdown-item i {
                color: var(--accent);
            }
            .dropdown-toggle::after {
                display: inline-block;
            }
        }
    </style>
</head>
<body>

<!-- Makueni County Innovation Hub Header -->
<header class="county-header">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark py-3">
            <div class="container-fluid px-0">
                <div class="d-flex align-items-center">
                    <div class="county-logo me-3">
                    <img src="/innovation_hub/assets/images/logo/makueni-logo.png" alt="Makueni County Logo" onerror="this.src='https://via.placeholder.com/60?text=MC'">
                    </div>
                    <div>
                        <a class="navbar-brand-custom text-decoration-none" href="../index.php">
                            <h4 class="mb-0 fw-bold">MAKUENI COUNTY</h4>
                            <small class="gov-badge">INNOVATION HUB</small>
                        </a>
                    </div>
                </div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link nav-link-custom" href="../index.php">
                                <i class="fas fa-home me-1"></i> Home
                            </a>
                        </li>
                        
                        <!-- Classes Dropdown - Hover only -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button">
                                <i class="fas fa-laptop-code me-1"></i> Classes
                            </a>
                            <ul class="dropdown-menu">
                                <?php if(empty($active_classes)): ?>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-info-circle"></i> No classes available</a></li>
                                <?php else: ?>
                                    <?php foreach($active_classes as $class): ?>
                                    <li><a class="dropdown-item" href="../register_class.php?id=<?php echo $class['id']; ?>">
                                        <i class="fas fa-laptop-code"></i> <?php echo htmlspecialchars($class['class_name']); ?>
                                    </a></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </li>
                        
                        <!-- AJIRY Dropdown - Hover only -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button">
                                <i class="fas fa-microphone-alt me-1"></i> AJIRY
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../ajiry/studio_book.php"><i class="fas fa-microphone-alt"></i> Studio Booking</a></li>
                                <li><a class="dropdown-item" href="../ajiry/check_booking_status.php"><i class="fas fa-calendar-check"></i> Check Status</a></li>
                            </ul>
                        </li>
                        
                        <!-- Events Dropdown - Hover only -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button">
                                <i class="fas fa-calendar-alt me-1"></i> Events
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../index.php#ongoing"><i class="fas fa-play-circle"></i> Ongoing Events</a></li>
                                <li><a class="dropdown-item" href="../index.php#upcoming"><i class="fas fa-calendar-week"></i> Incoming Events</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bookingModal"><i class="fas fa-calendar-plus"></i> Book Your Event</a></li>
                                <li><a class="dropdown-item" href="../event_check_status.php"><i class="fas fa-calendar-check"></i> Check Status</a></li>
                            </ul>
                        </li>
                        
                        <!-- Feedback Button -->
                        <li class="nav-item">
                            <a class="nav-link nav-link-custom" href="#" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                                <i class="fas fa-comment me-1"></i> Feedback
                            </a>
                        </li>
                        
                        <!-- Contact Us Button -->
                        <li class="nav-item">
                            <a class="nav-link nav-link-custom" href="../index.php#contact">
                                <i class="fas fa-phone-alt me-1"></i> Contact Us
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link nav-link-custom active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-link-custom text-danger" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</header>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar - User Profile & Quick Actions -->
        <div class="col-md-4 col-lg-3">
            <div class="card shadow-sm border-0 rounded-4 bg-success text-white">
                <div class="card-body p-3">
                    <!-- User Info -->
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-white rounded-circle p-2 me-2">
                            <i class="fas fa-user-circle fa-2x text-success"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'User'); ?></h6>
                            <small class="opacity-75"><?php echo htmlspecialchars($_SESSION['user_email']); ?></small>
                        </div>
                    </div>
                    
                    <hr class="bg-white opacity-25 my-2">
                    
                    <!-- Quick Stats -->
                    <div class="row text-center g-1 mb-3">
                        <div class="col-4">
                            <div class="bg-white bg-opacity-25 rounded-2 p-1">
                                <h5 class="fw-bold mb-0"><?php echo $total_feedback; ?></h5>
                                <small class="fs-10">Feedback</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-25 rounded-2 p-1">
                                <h5 class="fw-bold mb-0"><?php echo $registered_classes; ?></h5>
                                <small class="fs-10">Classes</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-25 rounded-2 p-1">
                                <h5 class="fw-bold mb-0"><?php echo $certificates; ?></h5>
                                <small class="fs-10">Certs</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="bg-white opacity-25 my-2">
                    
                    <!-- Quick Actions -->
                    <h6 class="fw-bold mb-2"><i class="fas fa-bolt me-1"></i>Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="../register_class.php" class="btn btn-warning btn-sm text-dark fw-bold py-2 quick-action-btn">
                            <i class="fas fa-plus-circle me-2"></i>Register New Class
                        </a>
                        <a href="my_class_portal.php" class="btn btn-outline-light btn-sm py-2 quick-action-btn">
                            <i class="fas fa-laptop-code me-2"></i>My Class Portal
                        </a>
                        <a href="#" class="btn btn-outline-light btn-sm py-2 quick-action-btn" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                            <i class="fas fa-comment me-2"></i>Give Feedback
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="col-md-8 col-lg-9">
            <!-- Welcome Banner -->
            <div class="card shadow-sm border-0 rounded-4 mb-4 bg-gradient-primary text-white">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">
                                <i class="fas fa-hand-peace me-1"></i>
                                Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'User'); ?>!
                            </h5>
                            <p class="mb-0 small opacity-75">Track your innovation journey and manage your activities.</p>
                        </div>
                        <div class="text-center d-none d-md-block">
                            <i class="fas fa-chalkboard-user fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card h-100 shadow-sm border-0 rounded-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted small mb-0">My Feedback</p>
                                    <h4 class="fw-bold mb-0 text-success"><?php echo $total_feedback; ?></h4>
                                    <small class="text-muted fs-10"><?php echo $approved_feedback; ?> approved</small>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded-2 p-2">
                                    <i class="fas fa-comment fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card h-100 shadow-sm border-0 rounded-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted small mb-0">My Classes</p>
                                    <h4 class="fw-bold mb-0 text-primary"><?php echo $registered_classes; ?></h4>
                                    <small class="text-muted fs-10">registered</small>
                                </div>
                                <div class="bg-primary bg-opacity-10 rounded-2 p-2">
                                    <i class="fas fa-laptop-code fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card h-100 shadow-sm border-0 rounded-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="text-muted small mb-0">Certificates</p>
                                    <h4 class="fw-bold mb-0 text-warning"><?php echo $certificates; ?></h4>
                                    <small class="text-muted fs-10">earned</small>
                                </div>
                                <div class="bg-warning bg-opacity-10 rounded-2 p-2">
                                    <i class="fas fa-certificate fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Section -->
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fas fa-clock text-success me-1"></i>Recent Activity
                    </h6>
                </div>
                <div class="card-body p-3 pt-0">
                    <?php if(empty($activities)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                            <p class="small text-muted mb-0">No recent activities yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($activities as $activity): ?>
                            <div class="d-flex align-items-center mb-2 pb-1 border-bottom">
                                <div class="flex-shrink-0">
                                    <?php if($activity['type'] == 'feedback'): ?>
                                        <div class="bg-warning bg-opacity-10 rounded-circle p-2">
                                            <i class="fas fa-comment text-warning"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-success bg-opacity-10 rounded-circle p-2">
                                            <i class="fas fa-laptop-code text-success"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-0 fw-bold"><?php echo htmlspecialchars(substr($activity['name'], 0, 50)); ?></p>
                                    <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AJIRY Services Section -->
<section class="py-5 mt-4" style="background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);">
    <div class="container">
        <div class="text-center text-white mb-5">
            <h2 class="fw-bold">AJIRY Services</h2>
            <p class="lead">Your creative partner for studio and professional document services</p>
        </div>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-0 rounded-4 shadow">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-microphone-alt fa-3x me-3" style="color: var(--primary-green);"></i>
                            <h3 class="fw-bold mb-0">AJIRY Studio</h3>
                        </div>
                        <p>Professional recording studio for podcasts, music, voice-overs, and content creation.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check-circle text-success me-2"></i> Professional sound equipment</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Soundproof recording room</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Audio mixing & mastering</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Podcast production support</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100 border-0 rounded-4 shadow">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-file-alt fa-3x me-3" style="color: var(--primary-green);"></i>
                            <h3 class="fw-bold mb-0">Document Services</h3>
                        </div>
                        <p>Professional document preparation and editing services.</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check-circle text-success me-2"></i> CV / Resume Writing</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Report Writing & Editing</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Office & Official Letters</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Document Proofreading</li>
                        </ul>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-info-circle me-2"></i> Visit our AJIRY office for CV, report, and letter services
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold mb-3">Makueni County Innovation Hub</h5>
                <p>Empowering innovators, entrepreneurs, and tech enthusiasts in Makueni County to transform ideas into reality through mentorship, training, and funding opportunities.</p>
            </div>
            <div class="col-md-2 mb-4">
                <h5 class="fw-bold mb-3">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="../register_class.php" class="text-white text-decoration-none">Computer Classes</a></li>
                    <li><a href="../ajiry/studio_book.php" class="text-white text-decoration-none">AJIRY Services</a></li>
                    <li><a href="../index.php#ongoing" class="text-white text-decoration-none">Events</a></li>
                    <li><a href="#" class="text-white text-decoration-none" data-bs-toggle="modal" data-bs-target="#feedbackModal">Feedback</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5 class="fw-bold mb-3">Contact Information</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-map-marker-alt me-2"></i> P.O. Box 78 – 90-300 Wote, Makueni</li>
                    <li><i class="fas fa-phone me-2"></i> 0795717885</li>
                    <li><i class="fas fa-phone me-2"></i> 0780717885</li>
                    <li><i class="fas fa-envelope me-2"></i> info@makueni.go.ke</li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h5 class="fw-bold mb-3">Follow Us</h5>
                <div class="d-flex gap-3">
                    <a href="#" class="text-white fs-4"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-white fs-4"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white fs-4"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white fs-4"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <hr class="border-light">
        <div class="text-center">
            <p class="mb-0">&copy; 2026 County Government of Makueni. All rights reserved.</p>
            <small>Empowering Innovators, Transforming Lives | Wote, Makueni County</small>
        </div>
    </div>
</footer>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1b4332, #2d6a4f); color: white;">
                <h4 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Book Your Event at Greenpark</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="../submit_venue_booking.php">
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
                    <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-bold">
                        <i class="fas fa-check-circle me-2"></i> Submit Booking Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1b4332, #2d6a4f); color: white;">
                <h4 class="modal-title"><i class="fas fa-comment me-2"></i>Share Your Feedback</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="../submit_feedback.php">
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
                    <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-bold">
                        <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>