<?php
session_start();
require_once 'config/database.php';
require_once 'includes/send_email.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ? AND role = 'user'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save token to database
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                $stmt->execute([$token, $expiry, $user['id']]);
                
                // Create reset link
                $reset_link = "http://localhost/innovation_hub/reset_password.php?token=" . $token;
                
                // Send email
                if (sendPasswordResetEmail($user['email'], $user['full_name'], $reset_link)) {
                    $success = "✅ Password reset link has been sent to your email address. Please check your inbox (and spam folder).";
                } else {
                    $error = "❌ Failed to send email. Please try again later or contact support.";
                }
            } else {
                $success = "If your email is registered, you will receive a password reset link.";
            }
        } catch(PDOException $e) {
            $error = "An error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4>Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Send Reset Link</button>
                        </form>
                        <div class="mt-3 text-center">
                            <a href="login.php">Back to Login</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>