<?php
require_once 'config/database.php';

try {
    echo "Creating news tables...<br>";
    
    // Drop existing tables if they exist
    echo "Dropping existing tables if they exist...<br>";
    $pdo->exec("DROP TABLE IF EXISTS news_gallery");
    $pdo->exec("DROP TABLE IF EXISTS news");
    echo "Tables dropped successfully.<br>";
    
    // Create the main news table
    $sql = "
        CREATE TABLE news (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            title_color varchar(20) DEFAULT '#1a5c3a' COMMENT 'Custom title color for user side',
            title_font_size varchar(10) DEFAULT '28px' COMMENT 'Custom font size for user side',
            slug varchar(255) NOT NULL,
            content text NOT NULL,
            summary varchar(500) DEFAULT NULL,
            featured_image varchar(255) DEFAULT NULL COMMENT 'Main featured image',
            image_height varchar(20) DEFAULT '400px' COMMENT 'Uniform image height for display',
            category varchar(50) DEFAULT 'general',
            status varchar(20) DEFAULT 'draft',
            views int(11) DEFAULT 0,
            created_by int(11) DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL,
            published_at timestamp NULL DEFAULT NULL,
            deleted_at timestamp NULL DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $pdo->exec($sql);
    echo "✅ Table 'news' created successfully!<br>";
    
    // Create the gallery images table
    $sql2 = "
        CREATE TABLE news_gallery (
            id int(11) NOT NULL AUTO_INCREMENT,
            news_id int(11) NOT NULL COMMENT 'Reference to news article',
            image_path varchar(255) NOT NULL COMMENT 'Path to gallery image',
            caption varchar(255) DEFAULT NULL COMMENT 'Optional image caption',
            sort_order int(11) DEFAULT 0 COMMENT 'Order of display',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY news_id (news_id),
            KEY sort_order (sort_order),
            CONSTRAINT news_gallery_ibfk_1 FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $pdo->exec($sql2);
    echo "✅ Table 'news_gallery' created successfully!<br>";
    
    echo "<br><strong>✅ All tables created successfully!</strong>";
    echo "<br><br>📌 New features added:";
    echo "<br>• 🎨 Title Color customization (title_color)";
    echo "<br>• 📏 Title Font Size customization (title_font_size)";
    echo "<br>• 🖼️ Image Height uniformity (image_height)";
    echo "<br>• 📸 Gallery Images support (news_gallery table)";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>