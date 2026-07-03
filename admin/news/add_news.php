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

$error = '';
$success = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $title_color = trim($_POST['title_color']);
    $title_font_size = trim($_POST['title_font_size']);
    $image_height = trim($_POST['image_height']);
    $summary = trim($_POST['summary']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    if(empty($title) || empty($content)) {
        $error = "Please fill in at least the title and content.";
    } else {
        $featured_image = '';
        if(isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
            $target_dir = "../../assets/images/news/";
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
            $user_id = $_SESSION['user_id'];
            $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO news (title, title_color, title_font_size, image_height, slug, summary, content, featured_image, category, status, created_by, created_at, published_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            
            if($stmt->execute([$title, $title_color, $title_font_size, $image_height, $slug, $summary, $content, $featured_image, $category, $status, $user_id, $published_at])) {
                $news_id = $pdo->lastInsertId();
                
                // Handle gallery images
                if(isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
                    $gallery_dir = "../../assets/images/news/gallery/";
                    if(!is_dir($gallery_dir)) {
                        mkdir($gallery_dir, 0777, true);
                    }
                    
                    $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                    $sort_order = 0;
                    
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
                
                $success = "News article added successfully!";
                $_POST = [];
            } else {
                $error = "Failed to add news.";
            }
        }
    }
}

// Get font size options
$font_sizes = ['16px', '18px', '20px', '22px', '24px', '28px', '32px', '36px', '40px'];
$image_heights = ['200px', '250px', '300px', '350px', '400px', '450px', '500px', '550px', '600px'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add News - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
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
        .gallery-item { position: relative; width: 120px; height: 120px; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
        .gallery-item .remove-btn { position: absolute; top: 5px; right: 5px; background: red; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; }
        .color-preview { width: 30px; height: 30px; border-radius: 50%; display: inline-block; margin-left: 10px; border: 2px solid #ddd; vertical-align: middle; }
        .preview-box { border: 2px dashed #ddd; padding: 20px; border-radius: 8px; margin-top: 10px; background: #f9f9f9; text-align: center; }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-plus-circle me-2"></i>Add News</h1>
                <div>
                    <a href="manage_news.php" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                    <a href="../dashboard.php" class="btn btn-outline-light btn-sm">
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
                            <input type="text" name="title" id="newsTitle" class="form-control" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" oninput="previewTitle()">
                        </div>

                        <!-- Title Customization -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Title Color</label>
                                <div class="input-group">
                                    <input type="color" name="title_color" id="titleColor" class="form-control" style="width: 60px; padding: 2px;" value="<?php echo htmlspecialchars($_POST['title_color'] ?? '#1a5c3a'); ?>" oninput="previewTitle()">
                                    <span class="input-group-text">Color</span>
                                    <span class="color-preview" id="colorPreview" style="background-color: <?php echo htmlspecialchars($_POST['title_color'] ?? '#1a5c3a'); ?>;"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Title Font Size</label>
                                <select name="title_font_size" id="titleFontSize" class="form-select" onchange="previewTitle()">
                                    <?php foreach($font_sizes as $size): ?>
                                        <option value="<?php echo $size; ?>" <?php echo (isset($_POST['title_font_size']) && $_POST['title_font_size'] == $size) ? 'selected' : ($size == '28px' ? 'selected' : ''); ?>>
                                            <?php echo $size; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Image Height -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Uniform Image Height</label>
                                <select name="image_height" class="form-select">
                                    <?php foreach($image_heights as $height): ?>
                                        <option value="<?php echo $height; ?>" <?php echo (isset($_POST['image_height']) && $_POST['image_height'] == $height) ? 'selected' : ($height == '400px' ? 'selected' : ''); ?>>
                                            <?php echo $height; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">All images in this news will display at this height</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Category</label>
                                <select name="category" class="form-select">
                                    <option value="general" <?php echo (isset($_POST['category']) && $_POST['category'] == 'general') ? 'selected' : ''; ?>>General</option>
                                    <option value="announcement" <?php echo (isset($_POST['category']) && $_POST['category'] == 'announcement') ? 'selected' : ''; ?>>Announcement</option>
                                    <option value="event" <?php echo (isset($_POST['category']) && $_POST['category'] == 'event') ? 'selected' : ''; ?>>Event</option>
                                    <option value="training" <?php echo (isset($_POST['category']) && $_POST['category'] == 'training') ? 'selected' : ''; ?>>Training</option>
                                    <option value="success_story" <?php echo (isset($_POST['category']) && $_POST['category'] == 'success_story') ? 'selected' : ''; ?>>Success Story</option>
                                </select>
                            </div>
                        </div>

                        <!-- Live Preview -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Live Preview</label>
                            <div class="preview-box">
                                <h3 id="previewTitle" style="color: #1a5c3a; font-size: 28px; margin: 0;">Preview Title Here</h3>
                                <small class="text-muted">This is how the title will appear on the user side</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Summary</label>
                            <input type="text" name="summary" class="form-control" value="<?php echo htmlspecialchars($_POST['summary'] ?? ''); ?>" placeholder="Short summary (max 500 chars)">
                        </div>

                        <!-- Content -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Content *</label>
                            <div id="editor" style="min-height: 200px;"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></div>
                            <textarea name="content" id="content" style="display:none;"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        </div>

                        <!-- Featured Image -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Featured Image</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/*">
                            <small class="text-muted">Recommended: 1200x600px (JPG, PNG, WEBP)</small>
                        </div>

                        <!-- Gallery Images -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Gallery Images</label>
                            <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                            <small class="text-muted">Upload multiple images for the gallery (JPG, PNG, WEBP)</small>
                            <div id="galleryPreview" class="gallery-preview"></div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo (!isset($_POST['status']) || $_POST['status'] == 'published') ? 'selected' : ''; ?>>Publish Now</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <button type="submit" name="add_news" class="btn btn-admin">
                            <i class="fas fa-paper-plane me-2"></i> Publish News
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        // Quill Editor
        var quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Write your news content here...',
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
        
        document.querySelector('form').onsubmit = function() {
            document.querySelector('#content').value = quill.root.innerHTML;
        };

        // Title Preview
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

        // Gallery Preview
        document.querySelector('input[name="gallery_images[]"]').addEventListener('change', function(e) {
            const preview = document.getElementById('galleryPreview');
            preview.innerHTML = '';
            const files = this.files;
            
            for(let i = 0; i < files.length; i++) {
                const reader = new FileReader();
                const div = document.createElement('div');
                div.className = 'gallery-item';
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    div.appendChild(img);
                };
                reader.readAsDataURL(files[i]);
                preview.appendChild(div);
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>