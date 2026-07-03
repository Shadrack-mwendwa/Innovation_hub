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

$error = '';
$success = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $title_color = trim($_POST['title_color']);
    $title_font_size = trim($_POST['title_font_size']);
    $featured_image_height = trim($_POST['featured_image_height']);
    $gallery_image_height = trim($_POST['gallery_image_height']);
    $summary = trim($_POST['summary']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];
    
    // Generate slug - FIXED
    if(empty($title)) {
        $slug = 'news-' . time();
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        // Remove multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        // If slug is empty after cleaning, use timestamp
        if(empty($slug)) {
            $slug = 'news-' . time();
        }
    }
    
    // Check for duplicate slug
    $check_stmt = $pdo->prepare("SELECT id FROM news WHERE slug = ?");
    $check_stmt->execute([$slug]);
    if($check_stmt->rowCount() > 0) {
        // Append timestamp to make it unique
        $slug = $slug . '-' . time();
    }
    
    if(empty($title) || empty($content)) {
        $error = "Please fill in at least the title and content.";
    } else {
        $featured_image = '';
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
            $user_id = $_SESSION['user_id'];
            $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO news (title, title_color, title_font_size, image_height, featured_image_height, gallery_image_height, slug, summary, content, featured_image, category, status, created_by, created_at, published_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            
            $image_height = $featured_image_height; // default to featured height
            
            if($stmt->execute([$title, $title_color, $title_font_size, $image_height, $featured_image_height, $gallery_image_height, $slug, $summary, $content, $featured_image, $category, $status, $user_id, $published_at])) {
                $news_id = $pdo->lastInsertId();
                
                // Handle gallery images
                if(isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
                    $gallery_dir = "../assets/images/news/gallery/";
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
$font_sizes = ['16px', '18px', '20px', '22px', '24px', '28px', '32px', '36px', '40px', '44px', '48px'];
$image_heights = ['200px', '250px', '300px', '350px', '400px', '450px', '500px', '550px', '600px', '650px', '700px'];

// Preview data
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
        'featured_image' => isset($_FILES['featured_image']['name']) ? $_FILES['featured_image']['name'] : null
    ];
}

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
    <title>Add News - Admin</title>
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
        .gallery-item { position: relative; width: 120px; height: 120px; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
        .color-preview { width: 30px; height: 30px; border-radius: 50%; display: inline-block; margin-left: 10px; border: 2px solid #ddd; vertical-align: middle; }
        .preview-box { border: 2px dashed #ddd; padding: 20px; border-radius: 8px; margin-top: 10px; background: #f9f9f9; text-align: center; }
        
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
                <h1><i class="fas fa-plus-circle me-2"></i>Add News</h1>
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

                        <!-- Image Heights -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Featured Image Height</label>
                                <select name="featured_image_height" class="form-select">
                                    <?php foreach($image_heights as $height): ?>
                                        <option value="<?php echo $height; ?>" <?php echo (isset($_POST['featured_image_height']) && $_POST['featured_image_height'] == $height) ? 'selected' : ($height == '400px' ? 'selected' : ''); ?>>
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
                                        <option value="<?php echo $height; ?>" <?php echo (isset($_POST['gallery_image_height']) && $_POST['gallery_image_height'] == $height) ? 'selected' : ($height == '250px' ? 'selected' : ''); ?>>
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
                                <option value="agriculture">Agriculture, Irrigation, Livestock, Fisheries & Cooperative Development</option>
                                <option value="county_attorney">County Attorney</option>
                                <option value="devolution">Devolution, Public Participation, County Administration & Special Programs</option>
                                <option value="finance">Finance, Planning, Budget & Revenue</option>
                                <option value="gender">Gender, Children, Youth, Sports & Social Service</option>
                                <option value="health">Health Services</option>
                                <option value="ict">ICT, Education & Internship</option>
                                <option value="infrastructure">Infrastructure, Transport, Public Works & Energy</option>
                                <option value="lands">Lands Urban planning & Development, Environment & Climate Change</option>
                                <option value="trade">Trade, Marketing, Industry, Culture & Tourism</option>
                                <option value="water">Water and Sanitation</option>
                                <option value="governor">Office of the Governor</option>
                                <option value="general">General</option>
                            </select>
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
                            <?php 
                            $editor_content = isset($_POST['content']) ? html_entity_decode($_POST['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                            ?>
                            <div id="editor" style="min-height: 200px;"><?php echo $editor_content; ?></div>
                            <textarea name="content" id="content" style="display:none;"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        </div>

                        <!-- Featured Image -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Featured Image</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/*">
                            <small class="text-muted">Main image for the news article</small>
                        </div>

                        <!-- Gallery Images -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Gallery Images</label>
                            <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                            <small class="text-muted">Additional images for the gallery</small>
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

                        <div class="d-flex gap-2">
                            <button type="submit" name="add_news" class="btn btn-admin flex-grow-1">
                                <i class="fas fa-paper-plane me-2"></i> Publish News
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
                                <div class="preview-gallery-item" style="height: <?php echo $gallery_height; ?>;">
                                    <span><i class="fas fa-image me-2"></i> Gallery Image 1</span>
                                </div>
                                <div class="preview-gallery-item" style="height: <?php echo $gallery_height; ?>;">
                                    <span><i class="fas fa-image me-2"></i> Gallery Image 2</span>
                                </div>
                                <div class="preview-gallery-item" style="height: <?php echo $gallery_height; ?>;">
                                    <span><i class="fas fa-image me-2"></i> Gallery Image 3</span>
                                </div>
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