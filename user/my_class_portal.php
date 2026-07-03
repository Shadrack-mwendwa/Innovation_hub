<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is NOT an admin (admins cannot access class portal)
if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    $_SESSION['error_message'] = "Administrators cannot access class portal. Please use a regular user account.";
    header("Location: ../admin/dashboard.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle feedback submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $registration_id = (int)$_POST['registration_id'];
    $class_id = (int)$_POST['class_id'];
    $message = trim($_POST['message']);
    $rating = (int)$_POST['rating'];
    
    if(!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO class_feedback (registration_id, user_id, class_id, message, rating) VALUES (?, ?, ?, ?, ?)");
        if($stmt->execute([$registration_id, $user_id, $class_id, $message, $rating])) {
            $success_message = "Thank you for your feedback!";
        } else {
            $error_message = "Failed to submit feedback. Please try again.";
        }
    } else {
        $error_message = "Please enter your feedback message.";
    }
}

// Get user's registered classes
$stmt = $pdo->prepare("
    SELECT r.*, c.class_name, c.description, c.duration_weeks, c.start_date, c.end_date, c.schedule, c.fee_amount
    FROM class_registrations r 
    JOIN computer_classes c ON r.class_id = c.id 
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id]);
$my_classes = $stmt->fetchAll();

// Get registered class names for duplicate prevention
$stmt = $pdo->prepare("
    SELECT DISTINCT c.class_name 
    FROM class_registrations r 
    JOIN computer_classes c ON r.class_id = c.id 
    WHERE r.user_id = ?
");
$stmt->execute([$user_id]);
$registered_class_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
$_SESSION['registered_class_names'] = $registered_class_names;

// Get active classes for dropdown
$active_classes = [];
try {
    $stmt = $pdo->query("SELECT * FROM computer_classes WHERE deleted_at IS NULL ORDER BY start_date ASC LIMIT 10");
    $active_classes = $stmt->fetchAll();
} catch(PDOException $e) {
    $active_classes = [];
}

// Get selected class
$selected_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : (isset($my_classes[0]) ? $my_classes[0]['class_id'] : 0);
$selected_registration = null;
$class_notes = [];
$class_feedback = [];

if($selected_class_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM class_registrations WHERE class_id = ? AND user_id = ?");
    $stmt->execute([$selected_class_id, $user_id]);
    $selected_registration = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM class_notes WHERE class_id = ? ORDER BY week_number ASC");
    $stmt->execute([$selected_class_id]);
    $class_notes = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT f.*, u.full_name 
        FROM class_feedback f 
        JOIN users u ON f.user_id = u.id 
        WHERE f.class_id = ? 
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$selected_class_id]);
    $class_feedback = $stmt->fetchAll();
}

$page_title = 'My Classes - Makueni County Innovation Hub';
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Navbar - Matching index.php */
        .navbar {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
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
            transition: transform 0.3s ease;
        }
        
        .county-logo:hover {
            transform: scale(1.05);
        }
        
        .brand-title { font-size: 1.1rem; font-weight: 700; color: white; }
        .brand-subtitle { font-size: 0.65rem; color: #FFD700; }
        
        /* Dropdown Menu */
        .nav-item { position: relative; }
        
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
        
        .nav-item:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-item {
            padding: 10px 25px;
            font-size: 14px;
            transition: all 0.2s ease;
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
        
        .nav-link { 
            color: white !important; 
            font-weight: 500;
            font-size: 0.95rem;
            padding: 8px 15px !important;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover { 
            color: #FFD700 !important;
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            color: #FFD700 !important;
        }
        
        .btn-login-nav {
            background: transparent;
            border: 1px solid white;
            border-radius: 25px;
            padding: 6px 20px !important;
            margin-left: 10px;
        }
        
        .btn-login-nav:hover {
            background: white;
            color: #1a5c3a !important;
        }
        
        .btn-register-nav {
            background: #FFD700;
            color: #1a5c3a !important;
            font-weight: 600;
            border-radius: 25px;
            padding: 6px 20px !important;
            margin-left: 8px;
        }
        
        .btn-register-nav:hover {
            background: #e6c200;
        }
        
        /* Page Content */
        .page-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            color: white;
            padding: 60px 0 40px;
            margin-top: 70px;
        }
        
        .btn-primary-custom {
            background-color: #1a5c3a;
            color: white;
            border: none;
            font-weight: bold;
            border-radius: 25px;
            padding: 10px 25px;
        }
        
        .btn-primary-custom:hover {
            background-color: #0a3d2a;
            color: white;
        }
        
        .blue-partition {
            border-bottom: 2px solid #1a5276;
            margin: 15px 0;
        }
        
        .list-group-item-class {
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .list-group-item-class:hover {
            border-left-color: #1a5c3a;
            background-color: #f8f9fc;
        }
        
        .list-group-item-class.active {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a) !important;
            border-left-color: #FFD700;
            color: white;
        }
        
        .rating-star {
            color: #ffc107;
        }
        
        .class-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
        }
        
        .balance-due {
            background-color: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        
        .amount-paid {
            background-color: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        
        /* Footer matching index.php */
        footer { 
            background: #0d3d2a; 
            color: white; 
            padding: 30px 0 15px; 
            margin-top: 50px; 
        }
        
        footer a { 
            color: rgba(255,255,255,0.7); 
            text-decoration: none; 
            font-size: 0.8rem; 
            transition: all 0.2s ease; 
        }
        
        footer a:hover { 
            color: #FFD700; 
            transform: translateX(3px); 
        }
        
        .footer-links a { 
            margin: 0 12px; 
        }
        
        @media (max-width: 768px) {
            .county-logo { height: 45px; width: 45px; }
            .page-header { margin-top: 60px; padding: 40px 0 30px; }
            .dropdown-menu {
                opacity: 1;
                visibility: visible;
                transform: none;
                position: static;
                background-color: transparent;
                box-shadow: none;
                padding-left: 20px;
            }
            .dropdown-menu .dropdown-item {
                color: white;
            }
            .dropdown-menu .dropdown-item:hover {
                background: rgba(255,255,255,0.1);
            }
        }
    </style>
</head>
<body>

<!-- Navbar - Matching index.php exactly -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <img src="../assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="county-logo" onerror="this.style.display='none'">
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
                <li class="nav-item"><a class="nav-link" href="../index.php#home">Home</a></li>
                
                <!-- Classes Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button">Classes</a>
                    <ul class="dropdown-menu">
                        <?php foreach($active_classes as $class): ?>
                        <li><a class="dropdown-item" href="../register_class.php?id=<?php echo $class['id']; ?>">
                            <i class="fas fa-laptop-code"></i> <?php echo htmlspecialchars($class['class_name']); ?>
                        </a></li>
                        <?php endforeach; ?>
                        <?php if(empty($active_classes)): ?>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-info-circle"></i> No classes available</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <!-- AJIRY Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button">AJIRY</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../ajiry/studio_book.php"><i class="fas fa-microphone-alt"></i> Studio Booking</a></li>
                        <li><a class="dropdown-item" href="../ajiry/check_booking_status.php"><i class="fas fa-calendar-check"></i> Check Status</a></li>
                    </ul>
                </li>
                
                <!-- Events Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button">Events</a>
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
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                        <i class="fas fa-comment me-1"></i> Feedback
                    </a>
                </li>
                
                <!-- Contact Us Button -->
                <li class="nav-item">
                    <a class="nav-link" href="../index.php#contact">
                        <i class="fas fa-phone-alt me-1"></i> Contact Us
                    </a>
                </li>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link btn-login-nav" href="../login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn-register-nav" href="../register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1 class="display-5 fw-bold">My Class Portal</h1>
        <p class="lead">Access your registered classes, learning materials, and submit feedback</p>
    </div>
</div>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card class-card">
                <div class="card-header" style="background-color: #1a5c3a; color: white; border: none; border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0"><i class="fas fa-laptop-code me-2"></i>My Registered Classes</h5>
                </div>
                <div class="card-body p-0">
                    <?php if(empty($my_classes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                            <p class="text-muted">You haven't registered for any computer classes yet.</p>
                            <p class="small text-muted">Note: You can only register for each class once.</p>
                            <a href="../register_class.php" class="btn btn-primary-custom mt-2">Browse Classes</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($my_classes as $class): ?>
                                <a href="?class_id=<?php echo $class['class_id']; ?>" 
                                   class="list-group-item list-group-item-action list-group-item-class <?php echo $selected_class_id == $class['class_id'] ? 'active' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-graduation-cap me-2"></i>
                                            <strong><?php echo htmlspecialchars($class['class_name'] ?? 'Class'); ?></strong>
                                            <br>
                                            <small class="<?php echo $selected_class_id == $class['class_id'] ? 'text-white-50' : 'text-muted'; ?>">
                                                <i class="fas fa-calendar me-1"></i>Starts: <?php echo date('M d, Y', strtotime($class['start_date'] ?? 'now')); ?>
                                            </small>
                                        </div>
                                        <?php if(($class['payment_status'] ?? '') == 'paid'): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-clock text-warning"></i>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if($selected_registration && ($selected_registration['letter_downloaded'] ?? 0) == 0): ?>
                <div class="card class-card mt-4">
                    <div class="card-body text-center">
                        <i class="fas fa-download fa-3x" style="color: #1a5c3a;"></i>
                        <p class="mb-2 mt-2">Download your admission letter</p>
                        <a href="../download_letter.php?id=<?php echo $selected_registration['id']; ?>" class="btn btn-primary-custom">
                            <i class="fas fa-file-pdf me-2"></i> Download Letter
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-8">
            <?php if($selected_class_id > 0 && $selected_registration): ?>
                <?php
                $class_name = '';
                $class_description = '';
                $class_start_date = '';
                $class_duration_weeks = '';
                $class_schedule = '';
                $amount_paid = $selected_registration['payment_amount'] ?? 0;
                $total_fee = 0;
                
                foreach($my_classes as $class) {
                    if($class['class_id'] == $selected_class_id) {
                        $class_name = $class['class_name'] ?? 'Class';
                        $class_description = $class['description'] ?? 'No description available';
                        $class_start_date = $class['start_date'] ?? date('Y-m-d');
                        $class_duration_weeks = $class['duration_weeks'] ?? 4;
                        $class_schedule = $class['schedule'] ?? 'To be announced';
                        $total_fee = $class['fee_amount'] ?? 1000;
                        break;
                    }
                }
                $balance = $total_fee - $amount_paid;
                ?>
                
                <div class="card class-card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div>
                                <h2 class="fw-bold" style="color: #1a5c3a;"><?php echo htmlspecialchars($class_name); ?></h2>
                                <p class="text-muted"><?php echo htmlspecialchars($class_description); ?></p>
                            </div>
                            <div class="text-end">
                                <span class="badge fs-6 px-3 py-2" style="background-color: #1a5c3a;">
                                    <i class="fas fa-calendar me-1"></i> Starts: <?php echo date('M d, Y', strtotime($class_start_date)); ?>
                                </span>
                            </div>
                        </div>
                        <div class="blue-partition"></div>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <i class="fas fa-hourglass-half fa-2x" style="color: #1a5c3a;"></i>
                                <p class="mb-0 mt-2"><strong><?php echo $class_duration_weeks; ?> weeks</strong></p>
                                <small class="text-muted">Duration</small>
                            </div>
                            <div class="col-md-3">
                                <i class="fas fa-calendar-week fa-2x" style="color: #1a5c3a;"></i>
                                <p class="mb-0 mt-2"><strong><?php echo htmlspecialchars($class_schedule); ?></strong></p>
                                <small class="text-muted">Schedule</small>
                            </div>
                            <div class="col-md-3">
                                <i class="fas fa-money-bill-wave fa-2x" style="color: #1a5c3a;"></i>
                                <p class="mb-0 mt-2"><strong>KES <?php echo number_format($amount_paid, 2); ?></strong></p>
                                <small class="text-muted amount-paid">Amount Paid</small>
                            </div>
                            <div class="col-md-3">
                                <i class="fas fa-coins fa-2x" style="color: #1a5c3a;"></i>
                                <p class="mb-0 mt-2"><strong>KES <?php echo number_format($balance, 2); ?></strong></p>
                                <small class="text-muted balance-due">Balance Due</small>
                            </div>
                        </div>
                        <?php if($balance > 0): ?>
                            <div class="alert alert-warning mt-3 mb-0 text-center">
                                <i class="fas fa-info-circle me-2"></i> Balance of KES <?php echo number_format($balance, 2); ?> is pending. Please complete your payment.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mt-3 mb-0 text-center">
                                <i class="fas fa-check-circle me-2"></i> Fully Paid - Thank you!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card class-card mb-4">
                    <div class="card-header bg-white border-0 pt-4">
                        <h4 class="fw-bold mb-0">
                            <i class="fas fa-file-alt me-2" style="color: #1a5c3a;"></i>Class Materials & Notes
                        </h4>
                    </div>
                    <div class="blue-partition mx-3"></div>
                    <div class="card-body">
                        <?php if(empty($class_notes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                <p class="text-muted">No notes uploaded for this class yet. Check back later!</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr><th>Week</th><th>Title</th><th>Uploaded</th><th>Action</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($class_notes as $note): ?>
                                        <tr>
                                            <td><span class="badge" style="background-color: #1a5c3a;">Week <?php echo $note['week_number']; ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($note['title']); ?></strong></td>
                                            <td><?php echo date('M d, Y', strtotime($note['created_at'])); ?></td>
                                            <td>
                                                <a href="../assets/uploads/notes/<?php echo $note['file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card class-card">
                    <div class="card-header bg-white border-0 pt-4">
                        <h4 class="fw-bold mb-0">
                            <i class="fas fa-star me-2" style="color: #ffc107;"></i>Feedback & Reviews
                        </h4>
                    </div>
                    <div class="blue-partition mx-3"></div>
                    <div class="card-body">
                        <?php if($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        <?php if($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <div class="bg-light p-4 rounded-4 mb-4">
                            <h5 class="fw-bold mb-3">Share Your Experience</h5>
                            <form method="POST">
                                <input type="hidden" name="registration_id" value="<?php echo $selected_registration['id']; ?>">
                                <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <select name="rating" class="form-select" style="width: auto;">
                                        <option value="5">★★★★★ (5 - Excellent)</option>
                                        <option value="4">★★★★☆ (4 - Good)</option>
                                        <option value="3">★★★☆☆ (3 - Average)</option>
                                        <option value="2">★★☆☆☆ (2 - Poor)</option>
                                        <option value="1">★☆☆☆☆ (1 - Very Poor)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Your Feedback</label>
                                    <textarea name="message" class="form-control" rows="4" required placeholder="Tell us about your learning experience..."></textarea>
                                </div>
                                <button type="submit" name="submit_feedback" class="btn btn-primary-custom">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                                </button>
                            </form>
                        </div>
                        
                        <h5 class="fw-bold mb-3">Student Reviews (<?php echo count($class_feedback); ?>)</h5>
                        <?php if(empty($class_feedback)): ?>
                            <p class="text-muted text-center py-3">No feedback yet. Be the first to share your experience!</p>
                        <?php else: ?>
                            <?php foreach($class_feedback as $feedback): ?>
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($feedback['full_name']); ?></strong>
                                            <div class="rating-star">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <?php if($i <= $feedback['rating']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></small>
                                    </div>
                                    <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php elseif(!empty($my_classes)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <h5>Select a Class</h5>
                    <p class="mb-0">Please select a class from the left menu to view notes and materials.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h5>No Registrations Found</h5>
                    <p>You haven't registered for any computer classes yet.</p>
                    <p class="small text-muted">Note: You can only register for each class once.</p>
                    <a href="../register_class.php" class="btn btn-primary-custom mt-2">Browse Classes</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Footer - Matching index.php -->
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
                    <li class="mb-2"><a href="../index.php#upcoming">Incoming Events</a></li>
                    <li class="mb-2"><a href="../index.php#ongoing">Ongoing Events</a></li>
                    <li class="mb-2"><a href="../index.php#classes">Computer Classes</a></li>
                    <li class="mb-2"><a href="../index.php#ajiry">AJIRY Services</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Contact Information</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-warning"></i> P.O. Box 78 – 90-300 Wote, Makueni</li>
                    <li class="mb-2"><i class="fas fa-phone me-2 text-warning"></i> 0795717885</li>
                    <li class="mb-2"><i class="fas fa-mobile-alt me-2 text-warning"></i> 0780717885</li>
                    <li class="mb-2"><i class="fas fa-envelope me-2 text-warning"></i> info@makueni.go.ke</li>
                </ul>
            </div>
        </div>
        <hr class="bg-white opacity-25 my-3">
        <div class="text-center small">
            <p>&copy; <?php echo date('Y'); ?> County Government of Makueni. All rights reserved.</p>
            <p class="mt-1">Empowering Innovators, Transforming Lives | Wote, Makueni County</p>
        </div>
    </div>
</footer>

<!-- Booking Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0a3d2a, #1a5c3a); color: white;">
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
            <div class="modal-header" style="background: linear-gradient(135deg, #0a3d2a, #1a5c3a); color: white;">
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