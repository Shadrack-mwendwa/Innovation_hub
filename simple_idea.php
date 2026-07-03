<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h1>Simple Idea Test</h1>";
echo "<p>If you see this, PHP is working.</p>";

// Test database
try {
    require_once 'config/database.php';
    echo "<p style='color:green'>Database connected!</p>";
    
    // Get first idea
    $stmt = $pdo->query("SELECT * FROM ideas LIMIT 1");
    $idea = $stmt->fetch();
    
    if($idea) {
        echo "<p>Idea found: " . $idea['title'] . "</p>";
        echo "<p>ID: " . $idea['id'] . "</p>";
    } else {
        echo "<p>No ideas found in database.</p>";
    }
} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>