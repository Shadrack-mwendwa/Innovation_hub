<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Handle Delete News (Soft Delete)
if(isset($_GET['action']) && $_GET['action'] == 'delete_news' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE news SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "News moved to trash!";
    header("Location: manage_news.php");
    exit();
}

// Handle Restore News
if(isset($_GET['action']) && $_GET['action'] == 'restore_news' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE news SET deleted_at = NULL WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "News restored!";
    header("Location: manage_news.php");
    exit();
}

// Handle Permanent Delete News
if(isset($_GET['action']) && $_GET['action'] == 'permanent_delete_news' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "News permanently deleted!";
    header("Location: manage_news.php");
    exit();
}

// Get all news
$all_news = [];
try {
    $stmt = $pdo->query("
        SELECT n.*, u.username as author 
        FROM news n 
        LEFT JOIN users u ON n.created_by = u.id 
        WHERE n.deleted_at IS NULL 
        ORDER BY n.created_at DESC
    ");
    $all_news = $stmt->fetchAll();
} catch(PDOException $e) {
    $all_news = [];
}

$trashed_news = [];
try {
    $stmt = $pdo->query("SELECT * FROM news WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
    $trashed_news = $stmt->fetchAll();
} catch(PDOException $e) {
    $trashed_news = [];
}

$total_news = count($all_news);
$published_news = 0;
$draft_news = 0;

foreach($all_news as $news) {
    if($news['status'] == 'published') $published_news++;
    if($news['status'] == 'draft') $draft_news++;
}

$page_title = 'Manage News - Admin Panel';
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
        body { background: #f5f5f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .table th { background: #f8f9fa; }
        .news-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-published { background: #28a745; color: white; }
        .status-draft { background: #ffc107; color: #856404; }
        .status-archived { background: #6c757d; color: white; }
        .btn-action { padding: 4px 10px; font-size: 12px; border-radius: 15px; margin: 2px; }
        .trash-item { background: #fff3cd; }
        .summary-stats { background: #e8f5e9; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .color-dot { display: inline-block; width: 16px; height: 16px; border-radius: 50%; border: 1px solid #ddd; vertical-align: middle; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-newspaper me-2"></i>Manage News</h1>
                <div>
                    <a href="add_news.php" class="btn btn-success btn-sm me-2">
                        <i class="fas fa-plus me-1"></i> Add News
                    </a>
                    <a href="../dashboard.php" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i> Dashboard
                    </a>
                    <a href="../../index.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-home me-1"></i> Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="summary-stats">
            <div class="row text-center">
                <div class="col-md-3"><h5>Total Articles</h5><h3 class="text-primary"><?php echo $total_news; ?></h3></div>
                <div class="col-md-3"><h5>Published</h5><h3 class="text-success"><?php echo $published_news; ?></h3></div>
                <div class="col-md-3"><h5>Draft</h5><h3 class="text-warning"><?php echo $draft_news; ?></h3></div>
                <div class="col-md-3"><h5>Trash</h5><h3 class="text-danger"><?php echo count($trashed_news); ?></h3></div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#newsList">📰 All News (<?php echo $total_news; ?>)</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#trashedNews">🗑️ Trash (<?php echo count($trashed_news); ?>)</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="newsList">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Author</th>
                                        <th>Views</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($all_news as $news): ?>
                                    <tr>
                                        <td><?php echo $news['id']; ?></td>
                                        <td>
                                            <?php if($news['featured_image']): ?>
                                                <img src="../../<?php echo htmlspecialchars($news['featured_image']); ?>" class="news-img" alt="Image">
                                            <?php else: ?>
                                                <div class="news-img d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong style="color: <?php echo htmlspecialchars($news['title_color'] ?? '#1a5c3a'); ?>; font-size: <?php echo htmlspecialchars($news['title_font_size'] ?? '16px'); ?>;">
                                                <?php echo htmlspecialchars($news['title']); ?>
                                            </strong>
                                            <br>
                                            <small class="text-muted">
                                                <span class="color-dot" style="background-color: <?php echo htmlspecialchars($news['title_color'] ?? '#1a5c3a'); ?>;"></span>
                                                <?php echo htmlspecialchars($news['title_color'] ?? '#1a5c3a'); ?> | 
                                                <?php echo htmlspecialchars($news['title_font_size'] ?? '28px'); ?>
                                            </small>
                                        </td>
                                        <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $news['category'] ?? 'General')); ?></span></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $news['status']; ?>">
                                                <?php echo ucfirst($news['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($news['author'] ?? 'Unknown'); ?></td>
                                        <td><?php echo $news['views']; ?></td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="edit_news.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-warning btn-action">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../../news_detail.php?id=<?php echo $news['id']; ?>" target="_blank" class="btn btn-sm btn-success btn-action">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?action=delete_news&id=<?php echo $news['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Move to trash?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($all_news)): ?>
                                    <tr><td colspan="8" class="text-center py-4">No news articles found. <a href="add_news.php">Add your first article</a></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="trashedNews">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Deleted On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($trashed_news as $news): ?>
                                    <tr class="trash-item">
                                        <td><?php echo $news['id']; ?></td>
                                        <td>
                                            <?php if($news['featured_image']): ?>
                                                <img src="../../<?php echo htmlspecialchars($news['featured_image']); ?>" class="news-img" alt="Image">
                                            <?php else: ?>
                                                <div class="news-img d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($news['title']); ?></strong></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($news['deleted_at'])); ?></td>
                                        <td>
                                            <a href="?action=restore_news&id=<?php echo $news['id']; ?>" class="btn btn-sm btn-success btn-action">
                                                <i class="fas fa-trash-restore"></i> Restore
                                            </a>
                                            <a href="?action=permanent_delete_news&id=<?php echo $news['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Permanently delete this news? This cannot be undone!')">
                                                <i class="fas fa-trash-alt"></i> Permanent Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($trashed_news)): ?>
                                    <tr><td colspan="5" class="text-center py-4">Trash is empty</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>