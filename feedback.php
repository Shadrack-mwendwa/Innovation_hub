<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Handle feedback submission - Anyone can submit
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $id_number = trim($_POST['id_number']);
    $message = trim($_POST['message']);
    $rating = (int)$_POST['rating'];
    
    if(empty($full_name) || empty($phone) || empty($message)) {
        $error = "Please fill in all required fields (Name, Phone, and Feedback)";
    } elseif(strlen($message) < 10) {
        $error = "Please provide more detailed feedback (at least 10 characters)";
    } elseif(!preg_match('/^[0-9]{10,12}$/', $phone)) {
        $error = "Please enter a valid phone number (e.g., 0712345678)";
    } else {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $stmt = $pdo->prepare("INSERT INTO feedback (user_id, full_name, phone, id_number, message, rating, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
        if($stmt->execute([$user_id, $full_name, $phone, $id_number, $message, $rating])) {
            $success = "Thank you for your valuable feedback! It has been submitted for review.";
        } else {
            $error = "Failed to submit feedback. Please try again.";
        }
    }
}

// Show feedback list based on user role
$show_feedback_list = false;
$all_feedback = [];
$my_feedback = [];

if(isset($_SESSION['user_role'])) {
    if($_SESSION['user_role'] == 'admin') {
        // Admin can see ALL feedback
        $show_feedback_list = true;
        $all_feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC")->fetchAll();
    } else {
        // Regular user can see ONLY their own feedback
        $show_feedback_list = true;
        $stmt = $pdo->prepare("SELECT * FROM feedback WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $my_feedback = $stmt->fetchAll();
    }
}

// Background images for hero section (same as index.php)
$background_images = [
    '/innovation_hub/assets/images/slider/slide1.jpg',
    '/innovation_hub/assets/images/slider/slide2.jpg',
    '/innovation_hub/assets/images/slider/slide3.jpg'
];

$page_title = 'Feedback - Makueni County Innovation Hub';
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
            --dark-green: #1b4332;
            --accent: #ffb703;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            position: relative;
        }
        
        /* Background Image Overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('assets/images/background/county-bg.png');
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #0a3d2a;
            opacity: 0.85;
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(10,61,42,0.3) 0%, rgba(26,92,58,0.3) 100%);
            z-index: 0;
            pointer-events: none;
        }
        
        /* Header Navigation */
        .main-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 20px 0;
            position: relative;
            z-index: 1001;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            padding: 0 20px;
        }
        
        .header-logo {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-logo-img {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            padding: 12px;
            border: 3px solid #FFD700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .header-logo-img:hover {
            transform: scale(1.05);
        }
        
        .header-title h2 {
            color: white;
            font-size: 1.8rem;
            margin: 0;
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header-title p {
            color: #FFD700;
            font-size: 0.9rem;
            margin: 0;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .nav-links {
            margin-left: auto;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 15px;
            border-radius: 25px;
        }
        
        .nav-links a:hover {
            color: #FFD700;
            background: rgba(255,255,255,0.1);
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.1);
            border-radius: 25px;
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            min-height: auto;
            padding: 60px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        
        .feedback-form-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .form-icon {
            width: 70px;
            height: 70px;
            background: rgba(26, 92, 58, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            border-color: #1a5c3a;
            box-shadow: 0 0 0 0.2rem rgba(26, 92, 58, 0.25);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #1a5c3a, #0a3d2a);
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 92, 58, 0.4);
        }
        
        .rating-options .form-check {
            margin-bottom: 8px;
        }
        
        .rating-options .form-check-input:checked {
            background-color: #1a5c3a;
            border-color: #1a5c3a;
        }
        
        /* Feedback List Table */
        .feedback-list-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 30px;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .badge-pending {
            background-color: #ffc107;
            color: #856404;
        }
        
        .badge-approved {
            background-color: #28a745;
            color: white;
        }
        
        /* Footer */
        .main-footer {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            color: white;
            padding: 40px 0 20px;
            position: relative;
            z-index: 1;
            margin-top: 50px;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 30px;
        }
        
        .footer-col {
            flex: 1;
            min-width: 200px;
        }
        
        .footer-col h5 {
            font-size: 1rem;
            margin-bottom: 15px;
            color: #FFD700;
        }
        
        .footer-col p, .footer-col li {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 8px;
        }
        
        .footer-col ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-col a {
            color: white;
            text-decoration: none;
        }
        
        .footer-col a:hover {
            color: #FFD700;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-size: 0.7rem;
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                text-align: center;
                justify-content: center;
            }
            .nav-links {
                margin-left: 0;
                justify-content: center;
            }
            .header-logo {
                flex-direction: column;
                text-align: center;
            }
            .header-logo-img {
                width: 70px;
                height: 70px;
            }
            .header-title h2 {
                font-size: 1.3rem;
            }
            .feedback-form-card {
                padding: 25px;
            }
            .footer-row {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header Navigation -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-logo">
                <img src="/innovation_hub/assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="header-logo-img" onerror="this.src='https://via.placeholder.com/100?text=MC'">
                <div class="header-title">
                    <h2>MAKUENI COUNTY</h2>
                    <p>INNOVATION HUB</p>
                </div>
            </div>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="register_class.php">Classes</a>
                <a href="ajiry/studio_book.php">AJIRY</a>
                <a href="index.php#events">Events</a>
                <a href="feedback.php" class="active">Feedback</a>
                <a href="index.php#contact">Contact Us</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="user/dashboard.php">Dashboard</a>
                    <a href="logout.php" class="btn-logout">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register_class.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section with Feedback Form -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="feedback-form-card">
                        <div class="text-center mb-4">
                            <div class="form-icon">
                                <i class="fas fa-comment fa-2x text-success"></i>
                            </div>
                            <h2 class="fw-bold text-success mt-3">Share Your Experience</h2>
                            <p class="text-muted">Your feedback helps us serve you better</p>
                        </div>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-user text-success me-1"></i> Full Name *
                                    </label>
                                    <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-phone text-success me-1"></i> Mobile Number *
                                    </label>
                                    <input type="tel" name="phone" class="form-control" placeholder="0712345678" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-id-card text-success me-1"></i> ID Number (Optional)
                                </label>
                                <input type="text" name="id_number" class="form-control" placeholder="ID/Passport number (optional)">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-star text-success me-1"></i> Rate Our Services *
                                </label>
                                <div class="rating-options">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="rating" id="rating5" value="5" required>
                                                <label class="form-check-label" for="rating5">
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <span class="ms-2">Excellent</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="rating" id="rating4" value="4">
                                                <label class="form-check-label" for="rating4">
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <span class="ms-2">Very Good</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="rating" id="rating3" value="3">
                                                <label class="form-check-label" for="rating3">
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <span class="ms-2">Good</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="rating" id="rating2" value="2">
                                                <label class="form-check-label" for="rating2">
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <span class="ms-2">Fair</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="rating" id="rating1" value="1">
                                                <label class="form-check-label" for="rating1">
                                                    <i class="fas fa-star text-warning"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <i class="far fa-star text-secondary"></i>
                                                    <span class="ms-2">Poor</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-comment-dots text-success me-1"></i> Your Feedback *
                                </label>
                                <textarea name="message" class="form-control" rows="4" required placeholder="Tell us about your experience with our services..."></textarea>
                                <small class="text-muted">Your feedback helps us improve</small>
                            </div>
                            
                            <button type="submit" name="submit_feedback" class="btn btn-success w-100 py-2 fw-bold">
                                <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-lock me-1"></i> Feedback submissions are reviewed by our team
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feedback List Section - Shows only after submission OR for logged in users -->
    <?php if($show_feedback_list && (isset($_SESSION['user_role']))): ?>
    <div class="container py-4">
        <div class="feedback-list-card">
            <div class="card-header bg-success text-white py-3">
                <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-list me-2"></i> All Feedback Submissions (<?php echo count($all_feedback); ?>) - Admin View
                    </h4>
                <?php else: ?>
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-history me-2"></i> My Feedback Submissions (<?php echo count($my_feedback); ?>)
                    </h4>
                    <p class="mb-0 small opacity-75">Here are all the feedback entries you have submitted</p>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <?php if(empty($all_feedback)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No feedback submissions yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>User ID</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Feedback</th>
                                        <th>Rating</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($all_feedback as $fb): ?>
                                    <tr>
                                        <td><?php echo $fb['id']; ?></td>
                                        <td><?php echo $fb['user_id'] ?? 'Guest'; ?></td>
                                        <td><strong><?php echo htmlspecialchars($fb['full_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($fb['phone']); ?></td>
                                        <td style="max-width: 300px;"><?php echo nl2br(htmlspecialchars(substr($fb['message'], 0, 100))); ?>...</td>
                                        <td>
                                            <div class="text-warning">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <?php if($i <= $fb['rating']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td><small><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></small></td>
                                        <td>
                                            <span class="badge <?php echo $fb['status'] == 'approved' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                <?php echo ucfirst($fb['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Regular User View - Only their own feedback -->
                    <?php if(empty($my_feedback)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">You haven't submitted any feedback yet.</p>
                            <p class="small text-muted">Use the form above to share your experience with us!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Feedback</th>
                                        <th>Rating</th>
                                        <th>Submitted On</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($my_feedback as $index => $fb): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td style="max-width: 400px;"><?php echo nl2br(htmlspecialchars($fb['message'])); ?></td>
                                        <td>
                                            <div class="text-warning">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <?php if($i <= $fb['rating']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </td>
                                        <td><small><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></small></td>
                                        <td>
                                            <span class="badge <?php echo $fb['status'] == 'approved' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                <?php echo ucfirst($fb['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-row">
                <div class="footer-col">
                    <h5>Makueni County Innovation Hub</h5>
                    <p>Empowering innovators, entrepreneurs, and tech enthusiasts in Makueni County to transform ideas into reality through mentorship, training, and funding opportunities.</p>
                </div>
                <div class="footer-col">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="register_class.php">Computer Classes</a></li>
                        <li><a href="ajiry/studio_book.php">AJIRY Services</a></li>
                        <li><a href="index.php#events">Events</a></li>
                        <li><a href="feedback.php">Feedback</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h5>Contact Information</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i> P.O. Box 78 – 90-300 Wote, Makueni</p>
                    <p><i class="fas fa-phone me-2"></i> 0795717885</p>
                    <p><i class="fas fa-phone me-2"></i> 0780717885</p>
                    <p><i class="fas fa-envelope me-2"></i> info@makueni.go.ke</p>
                </div>
                <div class="footer-col">
                    <h5>Follow Us</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white fs-5"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white fs-5"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white fs-5"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white fs-5"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 County Government of Makueni. All rights reserved.</p>
                <p>Empowering Innovators, Transforming Lives | Wote, Makueni County</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>