<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

// Get all published news
$news_list = [];
$featured_news = [];

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

try {
    // Get all published news
    $stmt = $pdo->query("
        SELECT n.*, u.username as author 
        FROM news n 
        LEFT JOIN users u ON n.created_by = u.id 
        WHERE n.deleted_at IS NULL 
        AND n.status = 'published' 
        ORDER BY n.created_at DESC
    ");
    $news_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $news_list = [];
}

$page_title = 'News - Makueni County Innovation Hub';
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
        .nav-link { color: white !important; font-weight: 500; transition: all 0.3s ease; }
        .nav-link:hover { color: #FFD700 !important; }
        
        .page-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 60px 0;
            color: white;
            text-align: center;
            margin-top: 70px;
        }
        .page-header h1 { font-size: 2.5rem; font-weight: 700; }
        .page-header .lead { font-size: 1.1rem; opacity: 0.9; }
        
        /* News List - Uniform Cards */
        .news-list-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 0;
        }
        
        .news-item {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: row;
            height: 220px;
        }
        .news-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .news-item-image {
            width: 280px;
            min-width: 280px;
            height: 220px;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .news-item-image-placeholder {
            width: 280px;
            min-width: 280px;
            height: 220px;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            flex-shrink: 0;
        }
        
        .news-item-content {
            padding: 25px 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .news-item-category {
            font-size: 0.7rem;
            padding: 3px 12px;
            border-radius: 20px;
            display: inline-block;
            width: fit-content;
            margin-bottom: 8px;
        }
        
        .news-item-title {
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.3;
            transition: color 0.3s ease;
        }
        
        .news-item-title a {
            color: inherit;
            text-decoration: none;
        }
        
        .news-item-title a:hover {
            color: #1a5c3a !important;
        }
        
        .news-item-summary {
            font-size: 0.9rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .news-item-meta {
            font-size: 0.8rem;
            color: #999;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .news-item-meta i {
            margin-right: 4px;
        }
        
        .news-item-meta .separator {
            color: #ddd;
        }
        
        .news-item-readmore {
            background: #1a5c3a;
            color: white;
            border-radius: 25px;
            padding: 6px 20px;
            font-size: 0.8rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 5px;
            width: fit-content;
        }
        
        .news-item-readmore:hover {
            background: #0a3d2a;
            color: white;
            transform: translateX(3px);
        }
        
        .no-news {
            text-align: center;
            padding: 60px 0;
        }
        .no-news i { font-size: 4rem; color: #ccc; margin-bottom: 20px; }
        .no-news h4 { color: #555; }
        .no-news p { color: #999; }
        
        footer { background: #0d3d2a; color: white; padding: 30px 0 15px; margin-top: 50px; }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; }
        footer a:hover { color: #FFD700; }
        
        @media (max-width: 768px) {
            .county-logo { height: 45px; width: 45px; }
            .news-item {
                flex-direction: column;
                height: auto;
            }
            .news-item-image,
            .news-item-image-placeholder {
                width: 100%;
                min-width: unset;
                height: 200px;
            }
            .news-item-content { padding: 20px; }
            .page-header h1 { font-size: 1.8rem; }
            .news-item-meta { font-size: 0.7rem; gap: 10px; }
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
                    <li class="nav-item"><a class="nav-link active" href="news.php">News</a></li>
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

    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-newspaper me-2"></i>News & Updates</h1>
            <p class="lead">Stay informed about the latest happenings at Makueni County Innovation Hub</p>
        </div>
    </div>

    <div class="container">
        <div class="news-list-container">
            <?php if(empty($news_list)): ?>
                <div class="no-news">
                    <i class="fas fa-newspaper"></i>
                    <h4>No News Yet</h4>
                    <p>Check back soon for updates from the Innovation Hub</p>
                </div>
            <?php else: ?>
                <?php foreach($news_list as $news): 
                    $category_color = getCategoryBadge($news['category'] ?? 'general');
                    $category_label = getCategoryLabel($news['category'] ?? 'general');
                    $title_color = $news['title_color'] ?? '#1a5c3a';
                    $title_font_size = $news['title_font_size'] ?? '22px';
                    $image_height = $news['featured_image_height'] ?? $news['image_height'] ?? '220px';
                    $featured_image = $news['featured_image'] ?? null;
                ?>
                <div class="news-item">
                    <!-- Image Section -->
                    <?php if($featured_image): ?>
                        <img src="<?php echo htmlspecialchars($featured_image); ?>" 
                             alt="<?php echo htmlspecialchars($news['title']); ?>" 
                             class="news-item-image"
                             style="height: <?php echo $image_height; ?>;">
                    <?php else: ?>
                        <div class="news-item-image-placeholder" style="height: <?php echo $image_height; ?>;">
                            <i class="fas fa-newspaper fa-3x"></i>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Content Section -->
                    <div class="news-item-content">
                        <span class="news-item-category bg-<?php echo $category_color; ?> text-white">
                            <?php echo $category_label; ?>
                        </span>
                        
                        <h4 class="news-item-title" style="color: <?php echo $title_color; ?>; font-size: <?php echo $title_font_size; ?>;">
                            <a href="news_detail.php?id=<?php echo $news['id']; ?>" style="color: <?php echo $title_color; ?>;">
                                <?php echo htmlspecialchars($news['title']); ?>
                            </a>
                        </h4>
                        
                        <p class="news-item-summary">
                            <?php echo htmlspecialchars(substr($news['summary'] ?? strip_tags($news['content']), 0, 150)); ?>
                            <?php if(strlen($news['summary'] ?? strip_tags($news['content'])) > 150): ?>...<?php endif; ?>
                        </p>
                        
                        <div class="news-item-meta">
                            <span><i class="far fa-calendar-alt"></i> <?php echo date('F d, Y', strtotime($news['created_at'])); ?></span>
                            <span class="separator">|</span>
                            <span><i class="far fa-user"></i> <?php echo htmlspecialchars($news['author'] ?? 'Admin'); ?></span>
                            <span class="separator">|</span>
                            <span><i class="far fa-eye"></i> <?php echo $news['views']; ?> views</span>
                        </div>
                        
                        <a href="news_detail.php?id=<?php echo $news['id']; ?>" class="news-item-readmore">
                            Read Full Story <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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
</body>
</html>