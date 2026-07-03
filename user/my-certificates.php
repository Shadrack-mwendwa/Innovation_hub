<?php
$page_title = 'My Certificates - Innovation Hub';
require_once '../includes/header.php';
require_once '../includes/auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT c.*, t.title as training_title, t.start_date 
                       FROM certifications c 
                       JOIN trainings t ON c.training_id = t.id 
                       WHERE c.user_id = ? AND c.status = 'issued'
                       ORDER BY c.issue_date DESC");
$stmt->execute([$user_id]);
$certificates = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-5 fw-bold">My Certificates</h1>
            <p class="text-muted">View and download your earned certificates</p>
        </div>
    </div>
    
    <?php if(empty($certificates)): ?>
        <div class="text-center py-5">
            <i class="fas fa-certificate fa-4x text-muted mb-3"></i>
            <h3>No certificates yet</h3>
            <p class="text-muted">Complete trainings to earn certificates</p>
            <a href="../trainings.php" class="btn btn-primary">Browse Trainings</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach($certificates as $cert): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-certificate fa-4x text-warning mb-3"></i>
                            <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($cert['training_title']); ?></h4>
                            <p class="text-muted">Certificate #: <?php echo $cert['certificate_number']; ?></p>
                            <p class="mb-2">Issued on: <?php echo date('F d, Y', strtotime($cert['issue_date'])); ?></p>
                            <?php if($cert['expiry_date']): ?>
                                <p class="text-muted small">Valid until: <?php echo date('F d, Y', strtotime($cert['expiry_date'])); ?></p>
                            <?php endif; ?>
                            <a href="download-certificate.php?id=<?php echo $cert['id']; ?>" class="btn btn-primary w-100 mt-3">
                                <i class="fas fa-download me-2"></i>Download Certificate
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>