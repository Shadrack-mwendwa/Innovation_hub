<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

function getCategoryBadge($category) {
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

$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$news = null;

if($news_id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE news SET views = views + 1 WHERE id = ?");
        $stmt->execute([$news_id]);
        
        $stmt = $pdo->prepare("
            SELECT n.*, u.username as author 
            FROM news n 
            LEFT JOIN users u ON n.created_by = u.id 
            WHERE n.id = ? AND n.deleted_at IS NULL AND n.status = 'published'
        ");
        $stmt->execute([$news_id]);
        $news = $stmt->fetch();
    } catch(PDOException $e) {
        $news = null;
    }
}

if(!$news) {
    header("Location: news.php");
    exit();
}

$gallery_images = [];
$stmt = $pdo->prepare("SELECT * FROM news_gallery WHERE news_id = ? ORDER BY sort_order ASC");
$stmt->execute([$news_id]);
$gallery_images = $stmt->fetchAll();

// Use separate featured and gallery heights
$featured_image_height = $news['featured_image_height'] ?? '400px';
$gallery_image_height = $news['gallery_image_height'] ?? $news['image_height'] ?? '250px';
$title_color = $news['title_color'] ?? '#1a5c3a';
$title_font_size = $news['title_font_size'] ?? '32px';
$category_color = getCategoryBadge($news['category'] ?? 'general');
$category_label = getCategoryLabel($news['category'] ?? 'general');

$page_title = $news['title'] . ' - Makueni County Innovation Hub';
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f5f5f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .navbar {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand { display: flex; align-items: center; }
        .county-logo {
            height: 60px;
            width: 60px;
            margin-right: 12px;
            background: white;
            border-radius: 50%;
            padding: 8px;
            object-fit: contain;
            border: 2px solid #FFD700;
        }
        .brand-title { font-size: 1.1rem; font-weight: 700; color: white; }
        .brand-subtitle { font-size: 0.65rem; color: #FFD700; }
        
        .article-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin: 30px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .article-header { border-bottom: 2px solid #e8f5e9; padding-bottom: 20px; margin-bottom: 30px; }
        .article-title { font-weight: 700; margin-bottom: 10px; }
        .article-meta { color: #999; font-size: 0.9rem; }
        .article-content { font-size: 1.05rem; line-height: 1.8; color: #333; }
        .article-content img { max-width: 100%; height: auto; border-radius: 10px; margin: 20px 0; }
        .article-content h2, .article-content h3 { color: #1a5c3a; margin-top: 30px; }
        .article-content p { margin-bottom: 15px; }
        
        .featured-image { 
            width: 100%; 
            object-fit: cover; 
            border-radius: 16px; 
            margin-bottom: 30px; 
        }
        
        .gallery-section { margin: 30px 0; }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        .gallery-grid img {
            width: 100%;
            object-fit: cover;
            border-radius: 12px;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .gallery-grid img:hover { transform: scale(1.03); }
        .gallery-caption {
            text-align: center;
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }
        
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .lightbox.active { display: flex; }
        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 8px;
        }
        .lightbox .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
        }
        .lightbox .prev, .lightbox .next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 40px;
            cursor: pointer;
            padding: 20px;
            background: rgba(0,0,0,0.3);
            border-radius: 50%;
        }
        .lightbox .prev { left: 20px; }
        .lightbox .next { right: 20px; }
        .lightbox .prev:hover, .lightbox .next:hover { background: rgba(0,0,0,0.7); }
        
        .back-btn { background: #1a5c3a; color: white; border-radius: 30px; padding: 10px 25px; text-decoration: none; }
        .back-btn:hover { background: #0a3d2a; color: white; }
        
        footer { background: #0d3d2a; color: white; padding: 30px 0 15px; margin-top: 50px; }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; }
        footer a:hover { color: #FFD700; }
        
        @media (max-width: 768px) {
            .county-logo { height: 45px; width: 45px; }
            .article-title { font-size: 1.6rem !important; }
            .article-container { padding: 20px; }
            .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .lightbox .prev, .lightbox .next { font-size: 25px; padding: 10px; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="county-logo" onerror="this.style.display='none'">
                <div>
                    <div class="brand-title">MAKUENI COUNTY</div>
                    <div class="brand-subtitle">INNOVATION HUB</div>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="news.php">News</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="user/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="article-container">
            <div class="article-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <span class="badge bg-<?php echo $category_color; ?> mb-2">
                            <?php echo $category_label; ?>
                        </span>
                        <h1 class="article-title" style="color: <?php echo $title_color; ?>; font-size: <?php echo $title_font_size; ?>;">
                            <?php echo htmlspecialchars($news['title']); ?>
                        </h1>
                        <div class="article-meta">
                            <i class="far fa-calendar-alt me-1"></i> <?php echo date('F d, Y', strtotime($news['created_at'])); ?>
                            <span class="mx-2">|</span>
                            <i class="far fa-user me-1"></i> <?php echo htmlspecialchars($news['author'] ?? 'Admin'); ?>
                            <span class="mx-2">|</span>
                            <i class="far fa-eye me-1"></i> <?php echo $news['views']; ?> views
                        </div>
                    </div>
                    <a href="news.php" class="back-btn mt-2 mt-md-0">
                        <i class="fas fa-arrow-left me-2"></i> Back to News
                    </a>
                </div>
            </div>

            <?php if($news['featured_image']): ?>
                <img src="<?php echo htmlspecialchars($news['featured_image']); ?>" 
                     alt="<?php echo htmlspecialchars($news['title']); ?>" 
                     class="featured-image" 
                     style="height: <?php echo $featured_image_height; ?>;">
            <?php endif; ?>

            <div class="article-content">
                <?php 
                $content = html_entity_decode($news['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                echo $content;
                ?>
            </div>

            <?php if(!empty($gallery_images)): ?>
            <div class="gallery-section">
                <h4 class="fw-bold" style="color: #1a5c3a; margin-bottom: 20px;">
                    <i class="fas fa-images me-2"></i>Image Gallery
                </h4>
                <div class="gallery-grid">
                    <?php foreach($gallery_images as $img): ?>
                    <div>
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($img['caption'] ?? 'Gallery Image'); ?>"
                             style="height: <?php echo $gallery_image_height; ?>;"
                             onclick="openLightbox(<?php echo $img['id']; ?>)"
                             loading="lazy">
                        <?php if($img['caption']): ?>
                            <div class="gallery-caption"><?php echo htmlspecialchars($img['caption']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="lightbox" id="lightbox">
        <span class="close" onclick="closeLightbox()">&times;</span>
        <span class="prev" onclick="changeImage(-1)">&#10094;</span>
        <span class="next" onclick="changeImage(1)">&#10095;</span>
        <img id="lightboxImg" src="" alt="">
    </div>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold">Makueni County Innovation Hub</h6>
                    <p class="small opacity-75">Empowering innovators, entrepreneurs, and tech enthusiasts.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> County Government of Makueni. All rights reserved.</p>
                    <p class="small mt-1">Empowering Innovators, Transforming Lives | Wote, Makueni County</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentImageIndex = 0;
        let galleryImages = <?php echo json_encode($gallery_images); ?>;
        
        function openLightbox(id) {
            const index = galleryImages.findIndex(img => img.id === id);
            if(index !== -1) {
                currentImageIndex = index;
                updateLightboxImage();
                document.getElementById('lightbox').classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        function updateLightboxImage() {
            const img = galleryImages[currentImageIndex];
            if(img) {
                document.getElementById('lightboxImg').src = img.image_path;
                document.getElementById('lightboxImg').alt = img.caption || 'Gallery Image';
            }
        }
        
        function changeImage(direction) {
            currentImageIndex += direction;
            if(currentImageIndex < 0) currentImageIndex = galleryImages.length - 1;
            if(currentImageIndex >= galleryImages.length) currentImageIndex = 0;
            updateLightboxImage();
        }
        
        document.addEventListener('keydown', function(e) {
            if(document.getElementById('lightbox').classList.contains('active')) {
                if(e.key === 'Escape') closeLightbox();
                if(e.key === 'ArrowLeft') changeImage(-1);
                if(e.key === 'ArrowRight') changeImage(1);
            }
        });
    </script>
</body>
</html>