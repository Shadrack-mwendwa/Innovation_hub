<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once '../config/database.php';

// Handle settings update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save settings to a file or database
    $settings = [
        'site_title' => $_POST['site_title'],
        'motto' => $_POST['motto'],
        'footer_text' => $_POST['footer_text']
    ];
    file_put_contents('../config/settings.json', json_encode($settings));
    $success = "Settings updated successfully!";
}

// Load current settings
$settings = [];
if(file_exists('../config/settings.json')) {
    $settings = json_decode(file_get_contents('../config/settings.json'), true);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Site Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Site Settings (Admin Only)</h1>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Site Title</label>
                <input type="text" name="site_title" class="form-control" value="<?php echo $settings['site_title'] ?? 'Makueni County Innovation Hub'; ?>">
            </div>
            <div class="mb-3">
                <label>County Motto</label>
                <input type="text" name="motto" class="form-control" value="<?php echo $settings['motto'] ?? 'Waui wa kwika nesa na ulungalu!'; ?>">
            </div>
            <div class="mb-3">
                <label>Footer Text</label>
                <textarea name="footer_text" class="form-control" rows="3"><?php echo $settings['footer_text'] ?? '© County Government of Makueni. All rights reserved.'; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Settings</button>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>