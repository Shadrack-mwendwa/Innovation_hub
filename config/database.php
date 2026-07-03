<?php
$host = 'localhost';
$dbname = 'innovation_hub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Make sure this path is correct for your setup
define('BASE_URL', 'http://localhost/innovation_hub/');
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');
?>