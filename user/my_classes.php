<?php
$page_title = 'My Classes - Innovation Hub';
require_once '../includes/header.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get user's registrations
$stmt = $pdo->prepare("SELECT r.*, c.class_name, c.duration_weeks, c.schedule, c.start_date, c.end_date 
                       FROM class_registrations r 
                       JOIN computer_classes c ON r.class_id = c.id 
                       WHERE r.user_id = ? OR r.email = (SELECT email FROM users WHERE id = ?)
                       ORDER BY r.created_at DESC");
$stmt->execute([$user_id, $user_id]);
$registrations = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-5 fw-bold">My Computer Classes</h1>
            <p class="text-muted">View your registered classes and access learning materials</p>
        </div>
    </div>
    
    <?php if(empty($registrations)): ?>
        <div class="text-center py-5">
            <i class="fas fa-laptop-code fa-4x text-muted mb-3"></i>
            <h3>No classes registered yet</h3>
            <p class="text-muted">Register for a computer class to start learning</p>
            <a href="../register_class.php" class="btn btn-primary">Register Now</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach($registrations as $reg): ?>
                <div class="col-md-6" data-aos="fade-up">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h4 class="fw-bold"><?php echo htmlspecialchars($reg['class_name']); ?></h4>
                                <span class="badge bg-<?php echo $reg['payment_status'] == 'paid' ? 'success' : 'warning'; ?> fs-6">
                                    <?php echo ucfirst($reg['payment_status']); ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <div><i class="fas fa-calendar me-2 text-primary"></i> Starts: <?php echo date('M d, Y', strtotime($reg['start_date'])); ?></div>
                                <div><i class="fas fa-clock me-2 text-primary"></i> Schedule: <?php echo htmlspecialchars($reg['schedule']); ?></div>
                                <div><i class="fas fa-money-bill me-2 text-primary"></i> Paid: KES <?php echo number_format($reg['payment_amount'], 2); ?></div>
                            </div>
                            
                            <?php if($reg['letter_downloaded']): ?>
                                <a href="../download_letter.php?id=<?php echo $reg['id']; ?>" class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fas fa-download me-1"></i> Download Letter Again
                                </a>
                            <?php endif; ?>
                            
                            <a href="class-materials.php?class_id=<?php echo $reg['class_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-book-open me-1"></i> View Materials & Notes
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>