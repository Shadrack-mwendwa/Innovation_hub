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

$user_id = $_SESSION['user_id'];

$feedback = $pdo->prepare("SELECT * FROM feedback WHERE user_id = ? ORDER BY created_at DESC");
$feedback->execute([$user_id]);
$my_feedback = $feedback->fetchAll();

$page_title = 'My Feedback';
require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="display-5 fw-bold">My Feedback</h1>
            <a href="submit_feedback.php" class="btn btn-success btn-lg">
                <i class="fas fa-comment me-2"></i>Submit New Feedback
            </a>
        </div>
        <p class="text-muted">Track and manage all your submitted feedback</p>
    </div>
</div>

<?php if(empty($my_feedback)): ?>
    <div class="text-center py-5">
        <i class="fas fa-comment fa-4x text-muted mb-3"></i>
        <h3>No feedback submitted yet</h3>
        <p class="text-muted">Share your experience with us</p>
        <a href="submit_feedback.php" class="btn btn-success">Submit Your First Feedback</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach($my_feedback as $item): ?>
            <div class="col-md-6">
                <div class="card h-100 shadow-sm border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-<?php echo $item['status'] == 'approved' ? 'success' : ($item['status'] == 'pending' ? 'warning' : 'danger'); ?> fs-6 px-3 py-2 me-2">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                                <div class="text-warning mt-2">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $item['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <small class="text-muted"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></small>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($item['message'])); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>