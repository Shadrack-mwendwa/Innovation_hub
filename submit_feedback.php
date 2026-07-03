<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Handle feedback submission - Anyone can submit (no login required)
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $id_number = trim($_POST['id_number'] ?? '');
    $message = trim($_POST['message']);
    $rating = (int)$_POST['rating'];
    
    if(empty($full_name) || empty($phone) || empty($message)) {
        $error = "Please fill in all required fields (Name, Phone, and Feedback)";
    } elseif(strlen($message) < 10) {
        $error = "Please provide more detailed feedback (at least 10 characters)";
    } elseif(!preg_match('/^[0-9]{10,12}$/', $phone)) {
        $error = "Please enter a valid phone number (e.g., 0712345678)";
    } else {
        // user_id is NULL for public submissions (not logged in)
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        $stmt = $pdo->prepare("INSERT INTO feedback (user_id, full_name, phone, id_number, message, rating, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
        if($stmt->execute([$user_id, $full_name, $phone, $id_number, $message, $rating])) {
            $success = "Thank you for your valuable feedback! It has been submitted for review.";
        } else {
            $error = "Failed to submit feedback. Please try again.";
        }
    }
}

$page_title = 'Submit Feedback - Makueni County Innovation Hub';
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
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            min-height: 100vh;
            position: relative;
        }
        
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
        
        .container {
            position: relative;
            z-index: 1;
            padding: 60px 20px;
        }
        
        .feedback-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-width: 700px;
            margin: 0 auto;
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
            border: 1px solid #ddd;
            padding: 12px 15px;
        }
        
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(45,106,79,0.25);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #1a5c3a, #0a3d2a);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26,92,58,0.4);
            color: #FFD700;
        }
        
        .rating-options .form-check {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 10px;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        
        @media (max-width: 768px) {
            .feedback-card {
                padding: 25px;
            }
            .rating-options .form-check {
                margin-right: 8px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="feedback-card">
            <div class="text-center mb-4">
                <div class="form-icon">
                    <i class="fas fa-comment fa-2x text-success"></i>
                </div>
                <h2 class="fw-bold text-success mt-3">Share Your Feedback</h2>
                <p class="text-muted">Help us improve our services</p>
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
                <div class="text-center mt-4">
                    <a href="index.php" class="btn-submit" style="display: inline-block; width: auto; padding: 10px 25px;">
                        <i class="fas fa-home me-2"></i> Back to Home
                    </a>
                </div>
            <?php else: ?>
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
                            <i class="fas fa-phone text-success me-1"></i> Phone Number *
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
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rating5" value="5" required>
                            <label class="form-check-label" for="rating5">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <span class="ms-1">Excellent</span>
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rating4" value="4">
                            <label class="form-check-label" for="rating4">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="far fa-star text-secondary"></i>
                                <span class="ms-1">Very Good</span>
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rating3" value="3">
                            <label class="form-check-label" for="rating3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="far fa-star text-secondary"></i>
                                <i class="far fa-star text-secondary"></i>
                                <span class="ms-1">Good</span>
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rating2" value="2">
                            <label class="form-check-label" for="rating2">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="far fa-star text-secondary"></i>
                                <i class="far fa-star text-secondary"></i>
                                <i class="far fa-star text-secondary"></i>
                                <span class="ms-1">Fair</span>
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="rating" id="rating1" value="1">
                            <label class="form-check-label" for="rating1">
                                <i class="fas fa-star text-warning"></i>
                                <i class="far fa-star text-secondary"></i>
                                <i class="far fa-star text-secondary"></i>
                                <i class="far fa-star text-secondary"></i>
                                <i class="far fa-star text-secondary"></i>
                                <span class="ms-1">Poor</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        <i class="fas fa-comment-dots text-success me-1"></i> Your Feedback *
                    </label>
                    <textarea name="message" class="form-control" rows="5" required placeholder="Tell us about your experience with our services..."></textarea>
                    <small class="text-muted">Your feedback helps us improve</small>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                </button>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>