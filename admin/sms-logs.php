<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$log_file = '../logs/sms_log.txt';
$logs = file_exists($log_file) ? file_get_contents($log_file) : 'No SMS logs yet.';
?>
<!DOCTYPE html>
<html>
<head>
    <title>SMS Logs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>SMS Notification Logs</h1>
        <div class="card">
            <div class="card-body">
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;"><?php echo htmlspecialchars($logs); ?></pre>
            </div>
        </div>
        <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>