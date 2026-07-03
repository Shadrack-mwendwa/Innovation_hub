<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields";
    } elseif($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $exists = $stmt->fetchColumn();
        
        if($exists > 0) {
            $error = "Username or email already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, 'user')");
            
            if($stmt->execute([$full_name, $username, $email, $phone, $hashed_password])) {
                $success = "Registration successful! <a href='login.php'>Click here to login</a>";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Makueni County Innovation Hub</title>
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
        
        .register-container {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            max-width: 550px;
            width: 100%;
        }
        
        .register-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 30px;
            text-align: center;
            border-bottom: 3px solid #FFD700;
        }
        
        .county-logo {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: 3px solid #FFD700;
            overflow: hidden;
        }
        
        .county-logo img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        
        .county-name {
            color: white;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        
        .county-subtitle {
            color: #FFD700;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .register-body {
            padding: 30px;
        }
        
        .welcome-text {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .welcome-text h3 {
            color: #1a5c3a;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .welcome-text p {
            color: #666;
            font-size: 13px;
        }
        
        .input-group-custom {
            margin-bottom: 15px;
        }
        
        .input-group-custom label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }
        
        .input-group-custom label i {
            color: #1a5c3a;
            margin-right: 6px;
        }
        
        .input-group-custom input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .input-group-custom input:focus {
            outline: none;
            border-color: #1a5c3a;
            box-shadow: 0 0 0 3px rgba(26, 92, 58, 0.1);
        }
        
        .btn-register {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            border: none;
            padding: 12px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 8px;
            width: 100%;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 92, 58, 0.4);
        }
        
        .kenyan-seal {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        
        .kenyan-seal img {
            width: 60px;
            height: auto;
            margin-bottom: 8px;
        }
        
        .kenyan-seal p {
            font-size: 10px;
            color: #999;
            margin: 2px 0;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
            color: #999;
        }
        
        .home-link {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
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
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
            color: #721c24;
            font-size: 13px;
        }
        
        .alert-success-custom {
            background: #d4edda;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
            color: #155724;
            font-size: 13px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .register-card {
            animation: fadeIn 0.5s ease-out;
        }
        
        .motto {
            text-align: center;
            margin-top: 10px;
            font-size: 11px;
            color: #FFD700;
            font-style: italic;
        }
        
        .row {
            display: flex;
            gap: 15px;
        }
        
        .row .col {
            flex: 1;
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
        
        @media (max-width: 576px) {
            .row {
                flex-direction: column;
                gap: 0;
            }
            .register-card {
                max-width: 100%;
            }
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
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <div class="county-logo">
                    <img src="assets/images/logo/makueni-logo.png" alt="Makueni County Logo" onerror="this.src='https://via.placeholder.com/70?text=MAKUENI'">
                </div>
                <div class="county-name">MAKUENI COUNTY</div>
                <div class="county-subtitle">INNOVATION HUB</div>
                <div class="motto">
                    <i class="fas fa-map-marker-alt me-1"></i> "Waui wa kwika nesa na ulungalu!"
                </div>
            </div>
            
            <div class="register-body">
                <div class="welcome-text">
                    <h3>Create Account</h3>
                    <p>Join Makueni County Innovation Hub</p>
                </div>
                
                <?php if($error): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert-success-custom">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col">
                            <div class="input-group-custom">
                                <label><i class="fas fa-user"></i> Full Name *</label>
                                <input type="text" name="full_name" placeholder="Enter full name" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group-custom">
                                <label><i class="fas fa-at"></i> Username *</label>
                                <input type="text" name="username" placeholder="Choose username" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="input-group-custom">
                        <label><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" name="email" placeholder="Enter email address" required>
                    </div>
                    
                    <div class="input-group-custom">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" placeholder="Optional">
                    </div>
                    
                    <div class="row">
                        <div class="col">
                            <div class="input-group-custom">
                                <label><i class="fas fa-lock"></i> Password *</label>
                                <input type="password" name="password" placeholder="Minimum 6 characters" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group-custom">
                                <label><i class="fas fa-lock"></i> Confirm Password *</label>
                                <input type="password" name="confirm_password" placeholder="Retype password" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus me-2"></i> Register Account
                    </button>
                </form>
                
                <!-- Home link below register button -->
                <div class="home-link">
                    <a href="/innovation_hub/index.php">
                        <i class="fas fa-arrow-left"></i> Go to Homepage
                    </a>
                </div>
                
                <div class="kenyan-seal">
                    <img src="assets/images/logo/seal.png" alt="Kenyan Seal" onerror="this.src='https://via.placeholder.com/60?text=SEAL'">
                    <p><strong>OFFICIAL COUNTY GOVERNMENT OF MAKUENI PORTAL</strong></p>
                    <p>Empowering Innovators, Transforming Lives</p>
                </div>
                
                <div class="footer-text">
                    <p>Already have an account? <a href="login.php" style="color: #1a5c3a; font-weight: bold;">Login here</a></p>
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
            <div class="col-md-3">
                    <h6 class="fw-bold">Quick Links</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="index.php#upcoming">Incoming Events</a></li>
                        <li class="mb-2"><a href="index.php#ongoing">Ongoing Events</a></li>
                        <li class="mb-2"><a href="index.php#classes">Computer Classes</a></li>
                        <li class="mb-2"><a href="index.php#ajiry">AJIRY Services</a></li>
                    </ul>
                </div>

            <div class="col-md-4">
                    <h7 class="fw-bold">Contact Information</h7>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-warning"></i> P.O. Box 78 – 90-300 Wote, Makueni</li>
                        <li class="mb-2"><i class="fas fa-phone me-2 text-warning"></i> 0795717885</li>
                        <li class="mb-2"><i class="fas fa-mobile-alt me-2 text-warning"></i> 0780717885</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2 text-warning"></i> info@makueni.go.ke</li>
                    </ul>
                </div>
            <div class="footer-section">
                <h8>AJIRY Services</h8>
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