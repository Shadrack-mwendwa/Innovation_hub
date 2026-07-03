<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once '../config/database.php';

$message = '';
$error = '';

// Handle upload
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_note'])) {
    $class_id = (int)$_POST['class_id'];
    $title = trim($_POST['title']);
    $week_number = (int)$_POST['week_number'];
    
    $upload_dir = '../assets/uploads/notes/';
    if(!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if(isset($_FILES['note_file']) && $_FILES['note_file']['error'] == 0) {
        $filename = time() . '_' . basename($_FILES['note_file']['name']);
        $target_path = $upload_dir . $filename;
        
        if(move_uploaded_file($_FILES['note_file']['tmp_name'], $target_path)) {
            $stmt = $pdo->prepare("INSERT INTO class_notes (class_id, title, file_path, week_number, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            if($stmt->execute([$class_id, $title, $filename, $week_number, $_SESSION['user_id']])) {
                $message = "Note uploaded successfully!";
            } else {
                $error = "Database error";
            }
        } else {
            $error = "Failed to upload file";
        }
    } else {
        $error = "Please select a file";
    }
}

$classes = $pdo->query("SELECT id, class_name FROM computer_classes ORDER BY class_name")->fetchAll();
$notes = $pdo->query("SELECT n.*, c.class_name FROM class_notes n JOIN computer_classes c ON n.class_id = c.id ORDER BY n.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Class Notes - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin - Makueni Innovation Hub</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="classes.php">Classes</a></li>
                    <li class="nav-item"><a class="nav-link active" href="upload_class_notes.php">Upload Notes</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1><i class="fas fa-upload me-2"></i>Upload Class Notes (Admin Only)</h1>
        <p class="text-muted">Upload learning materials for students. Only admins can access this page.</p>
        
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-file-alt me-2"></i>Upload New Note
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Select Computer Class</label>
                                <select name="class_id" class="form-control" required>
                                    <option value="">Choose a class...</option>
                                    <?php foreach($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Note Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Week Number</label>
                                <input type="number" name="week_number" class="form-control" required placeholder="e.g., 1, 2, 3">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File (PDF, DOC, PPT, TXT)</label>
                                <input type="file" name="note_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" required>
                                <small class="text-muted">Max file size: 5MB</small>
                            </div>
                            <button type="submit" name="upload_note" class="btn btn-success w-100">
                                <i class="fas fa-cloud-upload-alt me-2"></i>Upload Note
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-list me-2"></i>Uploaded Notes
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if(empty($notes)): ?>
                            <p class="text-muted text-center">No notes uploaded yet.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach($notes as $note): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-success">Week <?php echo $note['week_number']; ?></span>
                                                <strong class="ms-2"><?php echo htmlspecialchars($note['title']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($note['class_name']); ?></small>
                                            </div>
                                            <div class="btn-group">
                                                <a href="../assets/uploads/notes/<?php echo $note['file_path']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="../assets/uploads/notes/<?php echo $note['file_path']; ?>" class="btn btn-sm btn-success" download>
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </div>
                                        </div>
                                        <small class="text-muted">Uploaded: <?php echo date('M d, Y', strtotime($note['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>