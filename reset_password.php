<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$valid_token = false;
$user_id = null;

// Check if token is valid
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, full_name FROM users 
            WHERE reset_token = ? 
            AND reset_token_expiry > NOW()
            AND role = 'user'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $valid_token = true;
            $user_id = $user['id'];
        } else {
            $error = "Invalid or expired reset link. Please request a new one.";
        }
    } catch(PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in both fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, 
                    reset_token = NULL, 
                    reset_token_expiry = NULL,
                    last_password_change = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$hashed_password, $user_id]);
            
            $success = "✅ Password reset successfully! You can now login.";
            $valid_token = false;
        } catch(PDOException $e) {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Makueni County Innovation Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            width: 100%;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h4 {
            color: #1a5c3a;
            font-weight: 700;
        }
        .btn-reset {
            background: #1a5c3a;
            color: white;
            border-radius: 30px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .btn-reset:hover {
            background: #0a3d2a;
        }
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="logo">
            <img src="assets/images/logo/makueni-logo.png" alt="Makueni County Logo" style="height: 60px;" onerror="this.style.display='none'">
            <h4>Reset Password</h4>
            <?php if($valid_token): ?>
                <p class="text-muted">Create a new password for your account</p>
            <?php endif; ?>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-success rounded-pill px-4">
                    <i class="fas fa-sign-in-alt me-2"></i> Login Now
                </a>
            </div>
        <?php elseif($valid_token): ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password" required minlength="6">
                    </div>
                    <div class="password-strength" id="strengthBar"></div>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-check"></i></span>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required>
                    </div>
                    <small id="passwordMatch" class="text-muted"></small>
                </div>
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-save me-2"></i> Reset Password
                </button>
            </form>
            
            <script>
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                const strengthBar = document.getElementById('strengthBar');
                const passwordMatch = document.getElementById('passwordMatch');
                
                password.addEventListener('input', function() {
                    const val = this.value;
                    let strength = 0;
                    
                    if (val.length >= 6) strength++;
                    if (val.match(/[a-z]+/)) strength++;
                    if (val.match(/[A-Z]+/)) strength++;
                    if (val.match(/[0-9]+/)) strength++;
                    if (val.match(/[$@#&!]+/)) strength++;
                    
                    const colors = ['#dc3545', '#ffc107', '#28a745', '#17a2b8', '#1a5c3a'];
                    const widths = ['20%', '40%', '60%', '80%', '100%'];
                    
                    const index = Math.min(strength, 4);
                    strengthBar.style.width = widths[index];
                    strengthBar.style.background = colors[index];
                    
                    if (confirmPassword.value.length > 0) {
                        if (password.value === confirmPassword.value) {
                            passwordMatch.textContent = '✓ Passwords match';
                            passwordMatch.style.color = '#28a745';
                        } else {
                            passwordMatch.textContent = '✗ Passwords do not match';
                            passwordMatch.style.color = '#dc3545';
                        }
                    }
                });
                
                confirmPassword.addEventListener('input', function() {
                    if (this.value.length > 0) {
                        if (password.value === this.value) {
                            passwordMatch.textContent = '✓ Passwords match';
                            passwordMatch.style.color = '#28a745';
                        } else {
                            passwordMatch.textContent = '✗ Passwords do not match';
                            passwordMatch.style.color = '#dc3545';
                        }
                    } else {
                        passwordMatch.textContent = '';
                    }
                });
            </script>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo !empty($error) ? $error : 'Invalid or missing reset token. Please request a new password reset.'; ?>
            </div>
            <div class="text-center">
                <a href="forgot_password.php" class="btn btn-primary rounded-pill px-4">
                    <i class="fas fa-paper-plane me-2"></i> Request New Link
                </a>
                <br>
                <a href="login.php" class="btn btn-link mt-2">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>