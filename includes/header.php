<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fix the path - go up one level to find config folder
require_once __DIR__ . '/../config/database.php';

// Define BASE_URL if not defined
if(!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/innovation_hub/');
}

// Simple functions if not exists
if(!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if(!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

// Fetch available classes for dropdown
$available_classes = [];
try {
    $stmt = $pdo->query("SELECT * FROM computer_classes WHERE status IN ('active', 'upcoming') ORDER BY class_name LIMIT 10");
    $available_classes = $stmt->fetchAll();
} catch(PDOException $e) {
    $available_classes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Makueni County Innovation Hub'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .navbar-brand { 
            font-weight: bold; 
            display: flex; 
            align-items: center; 
        }
        
        .bg-success-custom { 
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a); 
        }
        
        footer { 
            background: #1a5c3a; 
            color: white; 
            padding: 20px 0; 
            margin-top: 50px; 
        }
        
        .county-logo-nav {
            height: 45px;
            width: auto;
            margin-right: 12px;
            object-fit: contain;
            background: white;
            border-radius: 50%;
            padding: 5px;
        }
        
        .brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .brand-title {
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        .brand-subtitle {
            font-size: 0.7rem;
            opacity: 0.8;
        }
        
        /* Login Button Style */
        .btn-login-nav {
            background: transparent;
            border: 2px solid white;
            color: white !important;
            border-radius: 5px;
            padding: 8px 20px !important;
            margin: 0 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .btn-login-nav:hover {
            background: white;
            color: #1a5c3a !important;
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        /* Register Button Style */
        .btn-register-nav {
            background: #FFD700;
            color: #0a3d2a !important;
            font-weight: bold;
            border-radius: 5px;
            padding: 8px 20px !important;
            margin-left: 5px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-register-nav:hover {
            background: #FFC107;
            transform: translateY(-2px);
            text-decoration: none;
            color: #0a3d2a !important;
        }
        
        /* Regular nav links */
        .navbar-nav .nav-link {
            color: white !important;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }
        
        .navbar-nav .nav-link:hover {
            color: #FFD700 !important;
            transform: translateY(-2px);
        }
        
        /* Dropdown styles */
        .nav-item.dropdown {
            position: relative;
        }
        
        .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .dropdown-toggle::after {
            display: inline-block;
            margin-left: 0.3em;
            vertical-align: middle;
            content: "▼";
            font-size: 10px;
            border: none;
            transition: transform 0.3s ease;
        }
        
        .dropdown.show .dropdown-toggle::after {
            transform: rotate(180deg);
        }
        
        .dropdown-menu {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            margin-top: 12px;
            min-width: 280px;
            padding: 10px 0;
            animation: fadeInDown 0.3s ease;
            z-index: 1000;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dropdown-item {
            padding: 12px 20px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .dropdown-item i {
            width: 25px;
            font-size: 1rem;
            color: #1a5c3a;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #1a5c3a, #0a3d2a);
            color: white;
            transform: translateX(5px);
        }
        
        .dropdown-item:hover i {
            color: white;
        }
        
        .dropdown-divider {
            margin: 8px 0;
            border-color: #e0e0e0;
        }
        
        .dropdown-item.view-all {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .dropdown-item.view-all i {
            color: #FFD700;
        }
        
        .dropdown-item.view-all:hover {
            background: linear-gradient(135deg, #1a5c3a, #0a3d2a);
        }
        
        .class-badge {
            font-size: 0.7rem;
            background: #e8f5e9;
            color: #1a5c3a;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: auto;
        }
        
        .dropdown-item:hover .class-badge {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        @media (max-width: 991px) {
            .dropdown-menu {
                position: static !important;
                float: none;
                width: 100%;
                margin-top: 5px;
                box-shadow: none;
                border: 1px solid #e0e0e0;
                animation: none;
            }
            
            .dropdown-toggle::after {
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
            }
            
            .dropdown.show .dropdown-toggle::after {
                transform: translateY(-50%) rotate(180deg);
            }
            
            .nav-item.dropdown {
                width: 100%;
            }
            
            .dropdown-item {
                padding: 12px 20px;
            }
        }
        
        @media (max-width: 768px) {
            .brand-title {
                font-size: 0.9rem;
            }
            .brand-subtitle {
                font-size: 0.6rem;
            }
            .county-logo-nav {
                height: 35px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="/innovation_hub/index.php">
                <img src="/innovation_hub/assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="county-logo-nav">
                <div class="brand-text">
                    <span class="brand-title">MAKUENI COUNTY</span>
                    <span class="brand-subtitle">INNOVATION HUB</span>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Public Links -->
                    <li class="nav-item"><a class="nav-link" href="/innovation_hub/index.php">Home</a></li>
                    
<li class="nav-item"><a class="nav-link" href="/innovation_hub/feedback.php">Feedback</a></li>
                    <li class="nav-item"><a class="nav-link" href="/innovation_hub/event.php">Events</a></li>
                    
                    <!-- Classes Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="classesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-laptop-code me-1"></i>Classes
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="classesDropdown">
                            <?php if(empty($available_classes)): ?>
                                <li>
                                    <a class="dropdown-item" href="/innovation_hub/register_class.php">
                                        <i class="fas fa-plus-circle"></i> 
                                        <span>View All Classes</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <?php foreach($available_classes as $class): ?>
                                <li>
                                    <a class="dropdown-item" href="/innovation_hub/class-details.php?id=<?php echo $class['id']; ?>">
                                        <i class="fas fa-book"></i> 
                                        <span><?php echo htmlspecialchars($class['class_name']); ?></span>
                                        <span class="class-badge"><?php echo $class['duration_weeks']; ?> wks</span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item view-all" href="/innovation_hub/register_class.php">
                                        <i class="fas fa-plus-circle"></i> 
                                        <span>View All Classes</span>
                                        <i class="fas fa-arrow-right ms-auto"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <!-- AJIRY Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="ajiryDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-star me-1"></i>AJIRY
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="ajiryDropdown">
                            <li><a class="dropdown-item" href="/innovation_hub/ajiry/studio_book.php"><i class="fas fa-microphone-alt"></i> Book Studio</a></li>
                            <li><a class="dropdown-item" href="/innovation_hub/ajiry/check-booking.php"><i class="fas fa-calendar-check"></i> Check Booking</a></li>
                        </ul>
                    </li>
                    
                    <?php if(isLoggedIn()): ?>
                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/innovation_hub/user/dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a></li>
                                <?php if(!isAdmin()): ?>
                                <li><a class="dropdown-item" href="/innovation_hub/user/my_class_portal.php">
                                    <i class="fas fa-laptop-code"></i> My Class Portal
                                </a></li>
                                <li><a class="dropdown-item" href="/innovation_hub/user/my_ideas.php">
                                    <i class="fas fa-lightbulb"></i> My Ideas
                                </a></li>
                                <li><a class="dropdown-item" href="/innovation_hub/user/my-certificates.php">
                                    <i class="fas fa-certificate"></i> My Certificates
                                </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/innovation_hub/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                        
                        <?php if(isAdmin()): ?>
                            <!-- Admin Menu -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                    <i class="fas fa-shield-alt me-1"></i>Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/innovation_hub/admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="/innovation_hub/admin/classes.php">
                                        <i class="fas fa-laptop-code"></i> Manage Classes
                                    </a></li>
                                    <li><a class="dropdown-item" href="/innovation_hub/admin/events.php">
                                        <i class="fas fa-calendar-alt"></i> Manage Events
                                    </a></li>
                                    <li><a class="dropdown-item" href="/innovation_hub/admin/users.php">
                                        <i class="fas fa-users"></i> Manage Users
                                    </a></li>
                                   <li><a class="dropdown-item" href="/innovation_hub/user/my_feedback.php">
    <i class="fas fa-comment"></i> My Feedback
</a></li>
                                    <li><a class="dropdown-item" href="/innovation_hub/admin/upload_class_notes.php">
                                        <i class="fas fa-upload"></i> Upload Notes
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- Login/Register -->
                        <li class="nav-item">
                            <a class="nav-link btn-login-nav" href="/innovation_hub/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn-register-nav" href="/innovation_hub/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main style="margin-top: 80px;">