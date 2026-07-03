<?php
require_once 'config/database.php';

echo "<h2>Database Connection Check</h2>";

try {
    // Test connection
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Users table exists</p>";
        
        // Show current columns in users table
        $stmt = $pdo->query("DESCRIBE users");
        echo "<h3>Current Columns in Users Table:</h3>";
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ Users table does not exist</p>";
    }
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>