<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Store redirect URL if provided
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : (isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '');
if($redirect_url) {
    unset($_SESSION['redirect_after_login']);
}

// Database connection
$host = 'localhost';
$dbname = 'innovation_hub';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        
        if($redirect_url) {
            header("Location: " . $redirect_url);
        } elseif($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: user/dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Makueni County Innovation Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a3d2a 0%, #1a5c3a 50%, #0a3d2a 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }
        
        /* Background Image */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('assets/images/background/county-bg.png');
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #0a3d2a;
            opacity: 0.85;
            z-index: 0;
        }
        
        .login-container {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            max-width: 480px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 40px 30px;
            text-align: center;
            border-bottom: 3px solid #FFD700;
        }
        
        .county-logo {
            width: 110px;
            height: 110px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: 3px solid #FFD700;
            overflow: hidden;
        }
        
        .county-logo img {
            width: 85px;
            height: 85px;
            object-fit: contain;
        }
        
        .county-name {
            color: white;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        
        .county-subtitle {
            color: #FFD700;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .welcome-text {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .welcome-text h3 {
            color: #1a5c3a;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .welcome-text p {
            color: #666;
            font-size: 14px;
        }
        
        .input-group-custom {
            margin-bottom: 20px;
        }
        
        .input-group-custom label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .input-group-custom label i {
            color: #1a5c3a;
            margin-right: 8px;
        }
        
        .input-group-custom input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .input-group-custom input:focus {
            outline: none;
            border-color: #1a5c3a;
            box-shadow: 0 0 0 3px rgba(26, 92, 58, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 10px;
            width: 100%;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 92, 58, 0.4);
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 15px;
        }
        
        .forgot-password a {
            color: #1a5c3a;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .forgot-password a:hover {
            color: #FFD700;
            text-decoration: underline;
        }
        
        .forgot-password a i {
            margin-right: 5px;
        }
        
        .kenyan-seal {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .kenyan-seal img {
            width: 70px;
            height: auto;
            margin-bottom: 12px;
        }
        
        .kenyan-seal p {
            font-size: 11px;
            color: #999;
            margin: 3px 0;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: #999;
        }
        
        .home-link {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
        }
        
        .home-link a {
            color: #1a5c3a;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .home-link a:hover {
            color: #FFD700;
            transform: translateX(-3px);
        }
        
        .alert-custom {
            background: #f8d7da;
            border: none;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            color: #721c24;
            font-size: 14px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-card {
            animation: fadeIn 0.5s ease-out;
        }
        
        .motto {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            color: #FFD700;
            font-style: italic;
        }
        
        /* Footer Section */
        .page-footer {
            background: #0d3d2a;
            color: white;
            padding: 30px 0 20px;
            position: relative;
            z-index: 10;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 30px;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
        }
        
        .footer-section h5 {
            margin-bottom: 15px;
            font-weight: 600;
            position: relative;
            display: inline-block;
        }
        
        .footer-section h5:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 40px;
            height: 2px;
            background: #FFD700;
        }
        
        .footer-section p, .footer-section a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 13px;
            line-height: 1.8;
        }
        
        .footer-section a:hover {
            color: #FFD700;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .social-icons a {
            width: 35px;
            height: 35px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .social-icons a:hover {
            background: #FFD700;
            color: #0d3d2a;
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 12px;
            color: rgba(255,255,255,0.6);
        }
        
        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            .footer-section h5:after {
                left: 50%;
                transform: translateX(-50%);
            }
            .social-icons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Login Form -->
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="county-logo">
                    <img src="assets/images/logo/makueni-logo.png" alt="Makueni County Logo" onerror="this.src='https://via.placeholder.com/85?text=MAKUENI'">
                </div>
                <div class="county-name">MAKUENI COUNTY</div>
                <div class="county-subtitle">INNOVATION HUB</div>
                <div class="motto">
                    <i class="fas fa-map-marker-alt me-1"></i> "Waui wa kwika nesa na ulungalu!"
                </div>
            </div>
            
            <div class="login-body">
                <div class="welcome-text">
                    <h3>Karibu! Welcome Back</h3>
                    <p>Login to access the Innovation Hub Portal</p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="input-group-custom">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="input-group-custom">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <!-- Forgot Password Link -->
                    <div class="forgot-password">
                        <a href="forgot_password.php">
                            <i class="fas fa-key"></i> Forgot Password?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i> Login to Dashboard
                    </button>
                </form>
                
                <!-- Home link below login form -->
                <div class="home-link">
                    <a href="/innovation_hub/index.php">
                        <i class="fas fa-arrow-left"></i> Go to Homepage
                    </a>
                </div>
                
                <div class="kenyan-seal">
                    <img src="assets/images/logo/seal.png" alt="Kenyan Seal" onerror="this.src='https://via.placeholder.com/70?text=SEAL'">
                    <p><strong>OFFICIAL COUNTY GOVERNMENT OF MAKUENI PORTAL</strong></p>
                    <p>Empowering Innovators, Transforming Lives</p>
                </div>
                
                <div class="footer-text">
                    <p>Don't have an account? <a href="register.php" style="color: #1a5c3a; font-weight: bold;">Register here</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Footer with Makueni Details -->
    <footer class="page-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h5>Makueni County Innovation Hub</h5>
                <p>Empowering innovators through technology, mentorship, and funding opportunities in Makueni County.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h5>Quick Links</h5>
                <p><a href="/innovation_hub/feedback.php">Feedback</a></p>
                <p><a href="/innovation_hub/index.php#events">Events</a></p>
                <p><a href="/innovation_hub/register_class.php">Computer Classes</a></p>
                <p><a href="/innovation_hub/index.php#ajiry">Ajiry</a></p>
            </div>
            <div class="footer-section">
                <h5>Contact Information</h5>
                <p><i class="fas fa-map-marker-alt me-2 text-warning"></i> P.O. Box 78 – 90-300 Wote, Makueni</p>
                <p><i class="fas fa-phone me-2 text-warning"></i> 0795717885</p>
                <p><i class="fas fa-mobile-alt me-2 text-warning"></i> 0780717885</p>
                <p><i class="fas fa-envelope me-2 text-warning"></i> info@makueni.go.ke</p>
            </div>
            <div class="footer-section">
                <h5>AJIRY Services</h5>
                <p><a href="/innovation_hub/ajiry/studio_book.php">Book Studio</a></p>
                <p><a href="/innovation_hub/ajiry/check-booking.php">Check Booking</a></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> County Government of Makueni. All rights reserved.</p>
            <p>Powered by Makueni County Innovation Hub | Partnered with AJIRY</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>