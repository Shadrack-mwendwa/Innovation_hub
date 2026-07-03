<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/functions.php';

$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$news = null;

// Get news data
if($news_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$news_id]);
    $news = $stmt->fetch();
}

if(!$news) {
    header("Location: dashboard.php?view=news");
    exit();
}

// Get gallery images
$gallery_images = [];
$stmt = $pdo->prepare("SELECT * FROM news_gallery WHERE news_id = ? ORDER BY sort_order ASC");
$stmt->execute([$news_id]);
$gallery_images = $stmt->fetchAll();

$error = '';
$success = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])) {
    $title = trim($_POST['title']);
    $title_color = trim($_POST['title_color']);
    $title_font_size = trim($_POST['title_font_size']);
    $featured_image_height = trim($_POST['featured_image_height']);
    $gallery_image_height = trim($_POST['gallery_image_height']);
    $summary = trim($_POST['summary']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    if(empty($title) || empty($content)) {
        $error = "Please fill in at least the title and content.";
    } else {
        $featured_image = $news['featured_image'];
        if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $target_dir = "../assets/images/news/";
            if(!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['featured_image']['name']);
            $target_file = $target_dir . $file_name;
            $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            
            if(in_array($image_file_type, $allowed_types)) {
                if(move_uploaded_file($_FILES['featured_image']['tmp_name'], $target_file)) {
                    $featured_image = 'assets/images/news/' . $file_name;
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
            }
        }
        
        if(empty($error)) {
            $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : null;
            
            // Update with separate featured and gallery image heights
            $stmt = $pdo->prepare("
                UPDATE news SET 
                    title = ?, title_color = ?, title_font_size = ?, 
                    featured_image_height = ?, gallery_image_height = ?,
                    slug = ?, summary = ?, content = ?, featured_image = ?,
                    category = ?, status = ?, updated_at = NOW(), published_at = ?
                WHERE id = ?
            ");
            
            // image_height is kept for backward compatibility, but we use featured_image_height and gallery_image_height
            if($stmt->execute([$title, $title_color, $title_font_size, $featured_image_height, $gallery_image_height, $slug, $summary, $content, $featured_image, $category, $status, $published_at, $news_id])) {
                // Handle gallery images
                if(isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
                    $gallery_dir = "../assets/images/news/gallery/";
                    if(!is_dir($gallery_dir)) {
                        mkdir($gallery_dir, 0777, true);
                    }
                    
                    $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                    $max_sort = $pdo->prepare("SELECT MAX(sort_order) FROM news_gallery WHERE news_id = ?");
                    $max_sort->execute([$news_id]);
                    $sort_order = ($max_sort->fetchColumn() ?? 0) + 1;
                    
                    foreach($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                        if($_FILES['gallery_images']['error'][$key] == 0) {
                            $file_name = time() . '_' . $key . '_' . basename($_FILES['gallery_images']['name'][$key]);
                            $target_file = $gallery_dir . $file_name;
                            $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                            
                            if(in_array($image_file_type, $allowed_types)) {
                                if(move_uploaded_file($tmp_name, $target_file)) {
                                    $gallery_path = 'assets/images/news/gallery/' . $file_name;
                                    $caption = trim($_POST['gallery_caption'][$key] ?? '');
                                    
                                    $stmt2 = $pdo->prepare("
                                        INSERT INTO news_gallery (news_id, image_path, caption, sort_order) 
                                        VALUES (?, ?, ?, ?)
                                    ");
                                    $stmt2->execute([$news_id, $gallery_path, $caption, $sort_order]);
                                    $sort_order++;
                                }
                            }
                        }
                    }
                }
                
                // Handle delete gallery images
                if(isset($_POST['delete_gallery']) && !empty($_POST['delete_gallery'])) {
                    foreach($_POST['delete_gallery'] as $gallery_id) {
                        $stmt = $pdo->prepare("SELECT image_path FROM news_gallery WHERE id = ?");
                        $stmt->execute([$gallery_id]);
                        $img = $stmt->fetch();
                        if($img) {
                            $file_path = "../" . $img['image_path'];
                            if(file_exists($file_path)) {
                                unlink($file_path);
                            }
                        }
                        $pdo->prepare("DELETE FROM news_gallery WHERE id = ?")->execute([$gallery_id]);
                    }
                }
                
                $success = "News updated successfully!";
                
                // Refresh gallery data
                $stmt = $pdo->prepare("SELECT * FROM news_gallery WHERE news_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$news_id]);
                $gallery_images = $stmt->fetchAll();
                
                // Refresh news data
                $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
                $stmt->execute([$news_id]);
                $news = $stmt->fetch();
            } else {
                $error = "Failed to update news.";
            }
        }
    }
}

// Handle preview
$preview_data = null;
if(isset($_POST['preview_news'])) {
    $preview_data = [
        'title' => trim($_POST['title']),
        'title_color' => trim($_POST['title_color']),
        'title_font_size' => trim($_POST['title_font_size']),
        'featured_image_height' => trim($_POST['featured_image_height']),
        'gallery_image_height' => trim($_POST['gallery_image_height']),
        'summary' => trim($_POST['summary']),
        'content' => trim($_POST['content']),
        'category' => trim($_POST['category']),
        'featured_image' => isset($_FILES['featured_image']['name']) ? $_FILES['featured_image']['name'] : $news['featured_image']
    ];
}

$font_sizes = ['16px', '18px', '20px', '22px', '24px', '28px', '32px', '36px', '40px', '44px', '48px'];
$image_heights = ['200px', '250px', '300px', '350px', '400px', '450px', '500px', '550px', '600px', '650px', '700px'];

function getCategoryLabel($category) {
    $labels = [
        'agriculture' => 'Agriculture & Livestock',
        'county_attorney' => 'County Attorney',
        'devolution' => 'Devolution & Public Participation',
        'finance' => 'Finance & Planning',
        'gender' => 'Gender & Social Services',
        'health' => 'Health Services',
        'ict' => 'ICT & Education',
        'infrastructure' => 'Infrastructure & Energy',
        'lands' => 'Lands & Environment',
        'trade' => 'Trade & Tourism',
        'water' => 'Water & Sanitation',
        'governor' => 'Office of the Governor',
        'general' => 'General'
    ];
    return $labels[$category] ?? ucfirst(str_replace('_', ' ', $category));
}

function getCategoryColor($category) {
    $colors = [
        'agriculture' => 'success',
        'county_attorney' => 'primary',
        'devolution' => 'info',
        'finance' => 'warning',
        'gender' => 'danger',
        'health' => 'danger',
        'ict' => 'primary',
        'infrastructure' => 'secondary',
        'lands' => 'success',
        'trade' => 'warning',
        'water' => 'info',
        'governor' => 'dark',
        'general' => 'secondary'
    ];
    return $colors[$category] ?? 'secondary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit News - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .form-container { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); }
        .btn-admin { background: #1a5c3a; color: white; padding: 10px 30px; border: none; border-radius: 8px; }
        .btn-admin:hover { background: #0a3d2a; color: white; }
        .ql-container { min-height: 200px; }
        .ql-editor { min-height: 200px; }
        .gallery-preview { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .gallery-item { position: relative; width: 150px; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; background: #f9f9f9; }
        .gallery-item img { width: 100%; height: 120px; object-fit: cover; }
        .gallery-item .caption-input { width: 100%; padding: 5px; border: none; border-top: 1px solid #ddd; font-size: 12px; }
        .gallery-item .remove-btn { position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-size: 12px; }
        .gallery-item .sort-handle { position: absolute; bottom: 30px; left: 5px; background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 4px; padding: 2px 8px; cursor: move; font-size: 11px; }
        .color-preview { width: 30px; height: 30px; border-radius: 50%; display: inline-block; margin-left: 10px; border: 2px solid #ddd; vertical-align: middle; }
        .preview-box { border: 2px dashed #ddd; padding: 20px; border-radius: 8px; margin-top: 10px; background: #f9f9f9; text-align: center; }
        
        /* Preview Modal Styles */
        .preview-modal-content {
            background: #f5f5f5;
            padding: 0;
            border-radius: 20px;
            overflow: hidden;
        }
        .preview-modal-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            color: white;
            padding: 20px;
            border-bottom: none;
        }
        .preview-body {
            padding: 30px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .preview-article {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .preview-featured-image {
            width: 100%;
            object-fit: cover;
            border-radius: 16px;
            margin-bottom: 30px;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        .preview-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .preview-gallery-item {
            background: #f8f9fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            border: 2px dashed #ddd;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-edit me-2"></i>Edit News</h1>
                <div>
                    <a href="dashboard.php?view=news" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-container">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="newsForm">
                        <!-- Title -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Title *</label>
                            <input type="text" name="title" id="newsTitle" class="form-control" required value="<?php echo htmlspecialchars($news['title']); ?>" oninput="previewTitle()">
                        </div>

                        <!-- Title Customization -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Title Color</label>
                                <div class="input-group">
                                    <input type="color" name="title_color" id="titleColor" class="form-control" style="width: 60px; padding: 2px;" value="<?php echo htmlspecialchars($news['title_color'] ?? '#1a5c3a'); ?>" oninput="previewTitle()">
                                    <span class="input-group-text">Color</span>
                                    <span class="color-preview" id="colorPreview" style="background-color: <?php echo htmlspecialchars($news['title_color'] ?? '#1a5c3a'); ?>;"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Title Font Size</label>
                                <select name="title_font_size" id="titleFontSize" class="form-select" onchange="previewTitle()">
                                    <?php foreach($font_sizes as $size): ?>
                                        <option value="<?php echo $size; ?>" <?php echo ($news['title_font_size'] ?? '28px') == $size ? 'selected' : ''; ?>>
                                            <?php echo $size; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Image Heights -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Featured Image Height</label>
                                <select name="featured_image_height" class="form-select">
                                    <?php foreach($image_heights as $height): ?>
                                        <option value="<?php echo $height; ?>" <?php echo ($news['featured_image_height'] ?? '400px') == $height ? 'selected' : ''; ?>>
                                            <?php echo $height; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Height for the main featured image</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Gallery Image Height</label>
                                <select name="gallery_image_height" class="form-select">
                                    <?php foreach($image_heights as $height): ?>
                                        <option value="<?php echo $height; ?>" <?php echo ($news['gallery_image_height'] ?? '250px') == $height ? 'selected' : ''; ?>>
                                            <?php echo $height; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Height for gallery images</small>
                            </div>
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Department/Category</label>
                            <select name="category" class="form-select">
                                <option value="agriculture" <?php echo ($news['category'] ?? '') == 'agriculture' ? 'selected' : ''; ?>>Agriculture, Irrigation, Livestock, Fisheries & Cooperative Development</option>
                                <option value="county_attorney" <?php echo ($news['category'] ?? '') == 'county_attorney' ? 'selected' : ''; ?>>County Attorney</option>
                                <option value="devolution" <?php echo ($news['category'] ?? '') == 'devolution' ? 'selected' : ''; ?>>Devolution, Public Participation, County Administration & Special Programs</option>
                                <option value="finance" <?php echo ($news['category'] ?? '') == 'finance' ? 'selected' : ''; ?>>Finance, Planning, Budget & Revenue</option>
                                <option value="gender" <?php echo ($news['category'] ?? '') == 'gender' ? 'selected' : ''; ?>>Gender, Children, Youth, Sports & Social Service</option>
                                <option value="health" <?php echo ($news['category'] ?? '') == 'health' ? 'selected' : ''; ?>>Health Services</option>
                                <option value="ict" <?php echo ($news['category'] ?? '') == 'ict' ? 'selected' : ''; ?>>ICT, Education & Internship</option>
                                <option value="infrastructure" <?php echo ($news['category'] ?? '') == 'infrastructure' ? 'selected' : ''; ?>>Infrastructure, Transport, Public Works & Energy</option>
                                <option value="lands" <?php echo ($news['category'] ?? '') == 'lands' ? 'selected' : ''; ?>>Lands Urban planning & Development, Environment & Climate Change</option>
                                <option value="trade" <?php echo ($news['category'] ?? '') == 'trade' ? 'selected' : ''; ?>>Trade, Marketing, Industry, Culture & Tourism</option>
                                <option value="water" <?php echo ($news['category'] ?? '') == 'water' ? 'selected' : ''; ?>>Water and Sanitation</option>
                                <option value="governor" <?php echo ($news['category'] ?? '') == 'governor' ? 'selected' : ''; ?>>Office of the Governor</option>
                                <option value="general" <?php echo ($news['category'] ?? '') == 'general' ? 'selected' : ''; ?>>General</option>
                            </select>
                        </div>

                        <!-- Live Preview -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Live Preview</label>
                            <div class="preview-box">
                                <h3 id="previewTitle" style="color: <?php echo htmlspecialchars($news['title_color'] ?? '#1a5c3a'); ?>; font-size: <?php echo htmlspecialchars($news['title_font_size'] ?? '28px'); ?>; margin: 0;">
                                    <?php echo htmlspecialchars($news['title']); ?>
                                </h3>
                                <small class="text-muted">This is how the title will appear on the user side</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Summary</label>
                            <input type="text" name="summary" class="form-control" value="<?php echo htmlspecialchars($news['summary'] ?? ''); ?>" placeholder="Short summary (max 500 chars)">
                        </div>

                        <!-- Content -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Content *</label>
                            <?php 
                            $editor_content = html_entity_decode($news['content'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            ?>
                            <div id="editor" style="min-height: 200px;"><?php echo $editor_content; ?></div>
                            <textarea name="content" id="content" style="display:none;"><?php echo htmlspecialchars($news['content']); ?></textarea>
                        </div>

                        <!-- Featured Image -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Featured Image</label>
                            <?php if($news['featured_image']): ?>
                                <div class="mb-2">
                                    <img src="../<?php echo htmlspecialchars($news['featured_image']); ?>" style="max-height: 150px; border-radius: 8px;" alt="Current Image">
                                    <br><small class="text-muted">Current featured image</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="featured_image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>

                        <!-- Gallery Images -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Gallery Images</label>
                            <?php if(!empty($gallery_images)): ?>
                                <div class="gallery-preview mb-2">
                                    <?php foreach($gallery_images as $img): ?>
                                    <div class="gallery-item" data-id="<?php echo $img['id']; ?>">
                                        <img src="../<?php echo htmlspecialchars($img['image_path']); ?>" alt="Gallery Image">
                                        <input type="text" name="gallery_caption[]" class="caption-input" placeholder="Caption" value="<?php echo htmlspecialchars($img['caption'] ?? ''); ?>">
                                        <input type="hidden" name="gallery_id[]" value="<?php echo $img['id']; ?>">
                                        <button type="button" class="remove-btn" onclick="removeGalleryImage(<?php echo $img['id']; ?>)">×</button>
                                        <span class="sort-handle">↕</span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="delete_gallery[]" id="deleteGallery" value="">
                            <?php endif; ?>
                            <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                            <small class="text-muted">Add more images to the gallery (JPG, PNG, WEBP)</small>
                            <div id="galleryPreview" class="gallery-preview"></div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?php echo $news['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $news['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo $news['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="edit_news" class="btn btn-admin flex-grow-1">
                                <i class="fas fa-save me-2"></i> Update News
                            </button>
                            <button type="submit" name="preview_news" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#previewModal">
                                <i class="fas fa-eye me-2"></i> Preview
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content preview-modal-content">
                <div class="modal-header preview-modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>News Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body preview-body">
                    <?php if($preview_data): 
                        $category_color = getCategoryColor($preview_data['category'] ?? 'general');
                        $category_label = getCategoryLabel($preview_data['category'] ?? 'general');
                        $title_color = $preview_data['title_color'] ?? '#1a5c3a';
                        $title_font_size = $preview_data['title_font_size'] ?? '28px';
                        $featured_height = $preview_data['featured_image_height'] ?? '400px';
                        $gallery_height = $preview_data['gallery_image_height'] ?? '250px';
                    ?>
                    <div class="preview-article">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div>
                                <span class="badge bg-<?php echo $category_color; ?> mb-2">
                                    <?php echo $category_label; ?>
                                </span>
                                <h1 style="color: <?php echo $title_color; ?>; font-size: <?php echo $title_font_size; ?>; font-weight: 700; margin-bottom: 10px;">
                                    <?php echo htmlspecialchars($preview_data['title'] ?? 'Preview Title'); ?>
                                </h1>
                                <div class="text-muted" style="font-size: 0.9rem;">
                                    <i class="far fa-calendar-alt me-1"></i> <?php echo date('F d, Y'); ?>
                                    <span class="mx-2">|</span>
                                    <i class="far fa-user me-1"></i> Admin
                                    <span class="mx-2">|</span>
                                    <i class="far fa-eye me-1"></i> 0 views
                                </div>
                            </div>
                        </div>

                        <?php if($preview_data['featured_image']): ?>
                            <div class="preview-featured-image" style="height: <?php echo $featured_height; ?>; background: #e8f5e9; margin-top: 20px;">
                                <span><i class="fas fa-image me-2"></i> <?php echo htmlspecialchars($preview_data['featured_image']); ?></span>
                            </div>
                        <?php else: ?>
                            <div class="preview-featured-image" style="height: <?php echo $featured_height; ?>; background: #e8f5e9; margin-top: 20px;">
                                <span><i class="fas fa-image me-2"></i> No featured image selected</span>
                            </div>
                        <?php endif; ?>

                        <div style="font-size: 1.05rem; line-height: 1.8; color: #333; margin-top: 20px;">
                            <?php 
                            $content = html_entity_decode($preview_data['content'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            echo $content ?: '<p class="text-muted">No content added yet.</p>';
                            ?>
                        </div>

                        <!-- Gallery Preview -->
                        <div style="margin-top: 30px;">
                            <h4 style="color: #1a5c3a; font-weight: 700; margin-bottom: 20px;">
                                <i class="fas fa-images me-2"></i>Image Gallery
                            </h4>
                            <div class="preview-gallery-grid">
                                <?php if(!empty($gallery_images)): ?>
                                    <?php foreach($gallery_images as $img): ?>
                                    <div class="preview-gallery-item" style="height: <?php echo $gallery_height; ?>;">
                                        <img src="../<?php echo htmlspecialchars($img['image_path']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;" alt="Gallery Image">
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="preview-gallery-item" style="height: <?php echo $gallery_height; ?>;">
                                        <span><i class="fas fa-image me-2"></i> No gallery images</span>
                                    </div>
                                    <div class="preview-gallery-item" style="height: <?php echo $gallery_height; ?>;">
                                        <span><i class="fas fa-image me-2"></i> No gallery images</span>
                                    </div>
                                    <div class="preview-gallery-item" style="height: <?php echo $gallery_height; ?>;">
                                        <span><i class="fas fa-image me-2"></i> No gallery images</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">Gallery images will appear here when uploaded</small>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-eye fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Fill in the form and click "Preview" to see how your news will appear.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });
        
        document.querySelector('form').onsubmit = function(e) {
            if(e.submitter && e.submitter.name === 'preview_news') {
                document.querySelector('#content').value = quill.root.innerHTML;
                return true;
            }
            document.querySelector('#content').value = quill.root.innerHTML;
        };

        function previewTitle() {
            const title = document.getElementById('newsTitle').value || 'Preview Title Here';
            const color = document.getElementById('titleColor').value;
            const size = document.getElementById('titleFontSize').value;
            
            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewTitle').style.color = color;
            document.getElementById('previewTitle').style.fontSize = size;
            document.getElementById('colorPreview').style.backgroundColor = color;
        }
        previewTitle();

        document.querySelector('input[name="gallery_images[]"]').addEventListener('change', function(e) {
            const preview = document.getElementById('galleryPreview');
            const files = this.files;
            
            for(let i = 0; i < files.length; i++) {
                const reader = new FileReader();
                const div = document.createElement('div');
                div.className = 'gallery-item';
                div.style.width = '150px';
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.height = '120px';
                    img.style.width = '100%';
                    img.style.objectFit = 'cover';
                    div.appendChild(img);
                };
                reader.readAsDataURL(files[i]);
                preview.appendChild(div);
            }
        });

        function removeGalleryImage(id) {
            if(confirm('Remove this image from gallery?')) {
                const deleteInput = document.getElementById('deleteGallery');
                const currentValues = deleteInput.value ? deleteInput.value.split(',') : [];
                currentValues.push(id);
                deleteInput.value = currentValues.join(',');
                
                const item = document.querySelector(`.gallery-item[data-id="${id}"]`);
                if(item) item.remove();
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>