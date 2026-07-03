<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $id_number = trim($_POST['id_number']);
    $message = trim($_POST['message']);
    $rating = (int)$_POST['rating'];
    
    if(empty($full_name) || empty($phone) || empty($message)) {
        $error = "Please fill in all required fields (Name, Phone, and Feedback)";
    } elseif(strlen($message) < 10) {
        $error = "Please provide more detailed feedback (at least 10 characters)";
    } else {
        $stmt = $pdo->prepare("INSERT INTO feedback (user_id, full_name, phone, id_number, message, rating, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
        if($stmt->execute([$_SESSION['user_id'], $full_name, $phone, $id_number, $message, $rating])) {
            $success = "Thank you for your valuable feedback! It has been submitted for review.";
        } else {
            $error = "Failed to submit feedback. Please try again.";
        }
    }
}

$page_title = 'Submit Feedback';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <i class="fas fa-comment fa-3x text-success mb-3"></i>
                    <h2 class="fw-bold">Share Your Feedback</h2>
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
                    <div class="text-center mt-3">
                        <a href="../index.php" class="btn btn-success">
                            <i class="fas fa-home me-2"></i> Back to Home
                        </a>
                        <a href="submit_feedback.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-plus me-2"></i> Submit Another
                        </a>
                    </div>
                <?php else: ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-user text-success me-1"></i> Full Name *
                            </label>
                            <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-phone text-success me-1"></i> Mobile Number *
                            </label>
                            <input type="tel" name="phone" class="form-control" placeholder="0712345678" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-id-card text-success me-1"></i> ID Number (Optional)
                        </label>
                        <input type="text" name="id_number" class="form-control" placeholder="ID/Passport number (optional)">
                        <small class="text-muted">Not required, but helps us verify genuine feedback</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-star text-success me-1"></i> Rate Our Innovation Hub *
                        </label>
                        <div class="rating-section">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
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
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-comment-dots text-success me-1"></i> Your Feedback *
                        </label>
                        <textarea name="message" class="form-control" rows="5" required placeholder="Tell us about your experience with our services, what you liked, and what we can improve..."></textarea>
                        <small class="text-muted">Your feedback helps us serve you better</small>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                        <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Feedback Guidelines -->
        <div class="card shadow-sm border-0 rounded-4 mt-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-info-circle text-success me-2"></i>Feedback Guidelines</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex mb-2">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Be specific about your experience</span>
                        </div>
                        <div class="d-flex mb-2">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Mention what you liked or disliked</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex mb-2">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Suggest improvements for our services</span>
                        </div>
                        <div class="d-flex mb-2">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <span>Keep your feedback respectful</span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <i class="fas fa-laptop-code text-success me-1"></i>
                    <span class="small">Your feedback will be reviewed by admin before being published</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>