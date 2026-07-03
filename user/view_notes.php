<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// Get classes the student is registered for
$stmt = $pdo->prepare("
    SELECT DISTINCT c.id, c.class_name 
    FROM class_registrations r 
    JOIN computer_classes c ON r.class_id = c.id 
    WHERE r.user_id = ? OR r.email = (SELECT email FROM users WHERE id = ?)
    ORDER BY c.class_name
");
$stmt->execute([$user_id, $user_id]);
$my_classes = $stmt->fetchAll();

// Get notes for selected class
$selected_class = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$notes = [];

if($selected_class > 0) {
    $stmt = $pdo->prepare("
        SELECT n.*, c.class_name 
        FROM class_notes n 
        JOIN computer_classes c ON n.class_id = c.id 
        WHERE n.class_id = ? 
        ORDER BY n.week_number ASC
    ");
    $stmt->execute([$selected_class]);
    $notes = $stmt->fetchAll();
}

$page_title = 'Class Notes';
require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white border-0 pt-4">
                <h4 class="fw-bold mb-0">
                    <i class="fas fa-laptop-code me-2 text-success"></i>My Classes
                </h4>
            </div>
            <div class="card-body">
                <?php if(empty($my_classes)): ?>
                    <p class="text-muted">You are not registered for any computer classes yet.</p>
                    <a href="../register_class.php" class="btn btn-success">Register Now</a>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($my_classes as $class): ?>
                            <a href="?class_id=<?php echo $class['id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo $selected_class == $class['id'] ? 'active bg-success' : ''; ?>">
                                <i class="fas fa-book me-2"></i>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white border-0 pt-4">
                <h4 class="fw-bold mb-0">
                    <i class="fas fa-file-alt me-2 text-success"></i>
                    Class Materials & Notes
                </h4>
            </div>
            <div class="card-body">
                <?php if($selected_class == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Select a class from the left to view notes and materials.</p>
                    </div>
                <?php elseif(empty($notes)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No notes uploaded for this class yet. Check back later!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Week</th>
                                    <th>Title</th>
                                    <th>Uploaded On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($notes as $note): ?>
                                    <tr>
                                        <td><span class="badge bg-success">Week <?php echo $note['week_number']; ?></span></td>
                                        <td><strong><?php echo htmlspecialchars($note['title']); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($note['created_at'])); ?></td>
                                        <td>
                                            <a href="../assets/uploads/notes/<?php echo $note['file_path']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="fas fa-eye me-1"></i> View
                                            </a>
                                            <a href="../assets/uploads/notes/<?php echo $note['file_path']; ?>" class="btn btn-sm btn-success" download>
                                                <i class="fas fa-download me-1"></i> Download
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
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>