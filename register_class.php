<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';

// Get all active computer classes for dropdown
$classes = [];
try {
    $stmt = $pdo->query("
        SELECT id, class_name, fee_amount 
        FROM computer_classes 
        WHERE deleted_at IS NULL 
        ORDER BY class_name ASC
    ");
    $classes = $stmt->fetchAll();
} catch(PDOException $e) {
    $classes = [];
}

// Handle form submission
$registration_success = false;
$error_message = '';
$show_letter_link = false;
$registration_id = null;
$class_name = '';
$total_fee = 0;
$amount_paid = 0;
$balance = 0;
$new_user_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_class'])) {
    $class_id = (int)$_POST['class_id'];
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $amount_paid = (float)$_POST['amount_paid'];
    
    // Validation
    if (empty($class_id)) {
        $error_message = "Please select a class.";
    } elseif (empty($full_name)) {
        $error_message = "Please enter your full name.";
    } elseif (empty($username)) {
        $error_message = "Please enter a username.";
    } elseif (empty($email)) {
        $error_message = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (empty($phone)) {
        $error_message = "Please enter your phone number.";
    } elseif (empty($password) || strlen($password) < 6) {
        $error_message = "Please enter a password with at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match. Please re-enter.";
    } elseif (empty($amount_paid) || $amount_paid < 200) {
        $error_message = "Please enter an amount of at least KES 200.00.";
    } else {
        // Get class details
        $stmt = $pdo->prepare("SELECT class_name, fee_amount FROM computer_classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch();
        
        if ($class) {
            $total_fee = $class['fee_amount'] ?? 1000;
            $balance = $total_fee - $amount_paid;
            
            // Determine payment status
            if ($balance <= 0) {
                $payment_status = 'paid';
            } elseif ($amount_paid > 0) {
                $payment_status = 'pending';
            } else {
                $payment_status = 'pending';
            }
            
            // Check if email already exists in users table
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                $user_id = $existing_user['id'];
                
                // Check if already registered for this class
                $check_stmt = $pdo->prepare("SELECT id FROM class_registrations WHERE user_id = ? AND class_id = ?");
                $check_stmt->execute([$user_id, $class_id]);
                if ($check_stmt->rowCount() > 0) {
                    $error_message = "You have already registered for this class.";
                } else {
                    // Register for class with existing user
                    $stmt = $pdo->prepare("
                        INSERT INTO class_registrations (user_id, class_id, full_name, email, phone, payment_amount, total_fee, remaining_balance, payment_status, registration_date, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    if ($stmt->execute([$user_id, $class_id, $full_name, $email, $phone, $amount_paid, $total_fee, $balance, $payment_status])) {
                        $registration_success = true;
                        $registration_id = $pdo->lastInsertId();
                        $class_name = $class['class_name'];
                        $new_user_id = $user_id;
                        
                        $_SESSION['last_registration'] = [
                            'id' => $registration_id,
                            'class_name' => $class_name,
                            'total_fee' => $total_fee,
                            'amount_paid' => $amount_paid,
                            'balance' => $balance,
                            'full_name' => $full_name,
                            'email' => $email
                        ];
                        $show_letter_link = true;
                    } else {
                        $error_message = "Registration failed. Please try again.";
                    }
                }
            } else {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $existing_username = $stmt->fetch();
                
                if ($existing_username) {
                    $error_message = "Username already taken. Please choose another username.";
                } else {
                    // Create new user
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (full_name, username, email, phone, password, role, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'user', NOW())
                    ");
                    if ($stmt->execute([$full_name, $username, $email, $phone, $hashed_password])) {
                        $user_id = $pdo->lastInsertId();
                        
                        // Register for class
                        $stmt = $pdo->prepare("
                            INSERT INTO class_registrations (user_id, class_id, full_name, email, phone, payment_amount, total_fee, remaining_balance, payment_status, registration_date, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        if ($stmt->execute([$user_id, $class_id, $full_name, $email, $phone, $amount_paid, $total_fee, $balance, $payment_status])) {
                            $registration_success = true;
                            $registration_id = $pdo->lastInsertId();
                            $class_name = $class['class_name'];
                            $new_user_id = $user_id;
                            
                            $_SESSION['last_registration'] = [
                                'id' => $registration_id,
                                'class_name' => $class_name,
                                'total_fee' => $total_fee,
                                'amount_paid' => $amount_paid,
                                'balance' => $balance,
                                'full_name' => $full_name,
                                'email' => $email
                            ];
                            $show_letter_link = true;
                            
                            // Auto-login the user
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['user_name'] = $full_name;
                            $_SESSION['username'] = $username;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_role'] = 'user';
                            $_SESSION['full_name'] = $full_name;
                        } else {
                            $error_message = "Registration failed. Please try again.";
                        }
                    } else {
                        $error_message = "Failed to create account. Please try again.";
                    }
                }
            }
        } else {
            $error_message = "Selected class not found.";
        }
    }
}

$page_title = 'Class Registration - Makueni County Innovation Hub';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            position: relative;
        }
        
        /* Background Image Overlay */
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
        
        /* Additional gradient overlay for better readability */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(10,61,42,0.3) 0%, rgba(26,92,58,0.3) 100%);
            z-index: 0;
            pointer-events: none;
        }
        
        /* Header Navigation - Left Aligned with Larger Logo */
        .main-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 20px 0;
            position: relative;
            z-index: 1001;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .header-container {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-logo {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-logo-img {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            padding: 12px;
            border: 3px solid #FFD700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .header-logo-img:hover {
            transform: scale(1.05);
        }
        
        .header-title h2 {
            color: white;
            font-size: 1.8rem;
            margin: 0;
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header-title p {
            color: #FFD700;
            font-size: 0.9rem;
            margin: 0;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        /* Government Banner */
        .gov-banner {
            background: #0d3d2a;
            color: #FFD700;
            text-align: center;
            padding: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 1px;
            position: relative;
            z-index: 1001;
        }
        
        .gov-banner i {
            margin-right: 8px;
        }
        
        /* Container */
        .register-container {
            width: 100%;
            max-width: 500px;
            margin: 40px auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        /* Logo Section inside Form */
        .form-logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-county-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            padding: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            border: 2px solid #FFD700;
        }
        
        .form-county-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .form-county-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1a5276;
            background: white;
            display: inline-block;
            padding: 5px 25px;
            border-radius: 40px;
            margin: 5px 0;
        }
        
        .form-county-tagline {
            font-size: 0.75rem;
            color: #1a5c3a;
            font-weight: 600;
            background: rgba(255,255,255,0.9);
            display: inline-block;
            padding: 4px 15px;
            border-radius: 20px;
        }
        
        /* Card */
        .register-card {
            background: white;
            border-radius: 20px;
            padding: 35px 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            border: none;
        }
        
        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a5c3a;
            margin-bottom: 5px;
            text-align: center;
        }
        
        .welcome-subtitle {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 25px;
            text-align: center;
        }
        
        /* Form Styles */
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group-custom i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #1a5c3a;
            font-size: 1rem;
            z-index: 1;
        }
        
        .form-control-custom {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control-custom:focus {
            outline: none;
            border-color: #1a5c3a;
            box-shadow: 0 0 0 3px rgba(26,92,58,0.1);
        }
        
        select.form-control-custom {
            appearance: none;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%231a5c3a"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 20px;
            cursor: pointer;
        }
        
        /* Amount Display */
        .amount-display {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 12px;
            padding: 12px 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .amount-display small {
            font-size: 0.7rem;
            color: #555;
            display: block;
        }
        
        .amount-display h3 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a5c3a;
            margin: 0;
        }
        
        /* Balance Display */
        .balance-display {
            background: #fff3cd;
            border-radius: 12px;
            padding: 12px 15px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        
        .balance-display .balance-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: #856404;
        }
        
        /* Button */
        .btn-register {
            background: linear-gradient(135deg, #1a5c3a, #0d3d2a);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26,92,58,0.4);
            color: #FFD700;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.4);
            color: white;
        }
        
        .btn-dashboard {
            background: linear-gradient(135deg, #1a5276, #2980b9);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26,82,118,0.4);
            color: white;
        }
        
        /* Login Link */
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #666;
        }
        
        .login-link a {
            color: #1a5c3a;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .go-home-link {
            display: inline-block;
            margin-top: 5px;
            font-size: 0.75rem;
        }
        
        .go-home-link a {
            color: #1a5c3a;
            text-decoration: none;
        }
        
        .go-home-link a:hover {
            text-decoration: underline;
        }
        
        /* Alert */
        .alert-custom {
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }
        
        .alert-success-custom {
            background: #d4edda;
            color: #155724;
            border: none;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .alert-success-custom i {
            font-size: 3rem;
            margin-bottom: 10px;
            display: block;
        }
        
        /* Success Page Seal */
        .success-seal {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            padding: 15px;
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 12px;
        }
        
        .success-seal img {
            width: 60px;
            height: 60px;
        }
        
        .success-seal .seal-text {
            text-align: left;
        }
        
        .success-seal .seal-text h6 {
            margin: 0;
            color: #1a5c3a;
            font-weight: bold;
        }
        
        .success-seal .seal-text p {
            margin: 0;
            font-size: 0.7rem;
            color: #555;
        }
        
        /* Footer */
        .main-footer {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            color: white;
            padding: 40px 0 20px;
            position: relative;
            z-index: 1;
            margin-top: 50px;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 30px;
        }
        
        .footer-col {
            flex: 1;
            min-width: 200px;
        }
        
        .footer-col h5 {
            font-size: 1rem;
            margin-bottom: 15px;
            color: #FFD700;
        }
        
        .footer-col p, .footer-col li {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 8px;
        }
        
        .footer-col ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-col a {
            color: white;
            text-decoration: none;
        }
        
        .footer-col a:hover {
            color: #FFD700;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-size: 0.7rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .header-logo-img {
                width: 70px;
                height: 70px;
            }
            .header-title h2 {
                font-size: 1.3rem;
            }
            .header-title p {
                font-size: 0.7rem;
            }
            .footer-row {
                flex-direction: column;
                text-align: center;
            }
            .register-container {
                margin: 20px auto;
            }
            .register-card {
                padding: 25px 20px;
            }
            .success-seal {
                flex-direction: column;
                text-align: center;
            }
            .success-seal .seal-text {
                text-align: center;
            }
            .form-county-logo {
                width: 60px;
                height: 60px;
            }
            .form-county-name {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Navigation - Left Aligned with Larger Logo -->
    <header class="main-header">
        <div class="container">
            <div class="header-container">
                <div class="header-logo">
                    <img src="/innovation_hub/assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="header-logo-img" onerror="this.src='https://via.placeholder.com/100?text=MC'">
                    <div class="header-title">
                        <h2>MAKUENI COUNTY</h2>
                        <p>INNOVATION HUB</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Government Banner -->
    <div class="gov-banner">
        <i class="fas fa-gavel"></i> Official County Government Initiative
    </div>

    <div class="register-container">
        <!-- Registration Card -->
        <div class="register-card">
            <!-- Form Logo Section -->
            <div class="form-logo-section">
                <div class="form-county-logo">
                    <img src="/innovation_hub/assets/images/logo/makueni-logo.png" alt="Makueni County Logo" onerror="this.src='https://via.placeholder.com/80?text=MC'">
                </div>
                <div class="form-county-name">MAKUENI COUNTY</div>
                <div class="form-county-tagline">INNOVATION HUB</div>
            </div>

            <div class="welcome-title">
                <i class="fas fa-graduation-cap me-2" style="color: #1a5c3a;"></i>Class Registration
            </div>
            <div class="welcome-subtitle">
                Register for a computer class to begin your journey
            </div>

            <!-- Success Message -->
            <?php if($registration_success): ?>
                <div class="alert-success-custom">
                    <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    <h5 class="fw-bold mb-2">Registration & Account Created Successfully!</h5>
                    <p>Welcome <strong><?php echo htmlspecialchars($full_name); ?></strong>!</p>
                    <p>You have successfully registered for <strong><?php echo htmlspecialchars($class_name); ?></strong></p>
                    
                    <div class="row mt-3">
                        <div class="col-6">
                            <small>Total Fee</small>
                            <p class="fw-bold">KES <?php echo number_format($total_fee, 2); ?></p>
                        </div>
                        <div class="col-6">
                            <small>Amount Paid</small>
                            <p class="fw-bold">KES <?php echo number_format($amount_paid, 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-2">
                        <strong>Balance: KES <?php echo number_format($balance, 2); ?></strong>
                        <?php if($balance > 0): ?>
                            <br><small>Please complete the balance payment to get your certificate</small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Makueni County Seal -->
                    <div class="success-seal">
                        <img src="assets/images/logo/seal.png" alt="Makueni County Seal" onerror="this.src='https://via.placeholder.com/60?text=SEAL'">
                        <div class="seal-text">
                            <h6>County Government of Makueni</h6>
                            <p>Official Registration Confirmation</p>
                            <p>This certifies that the above named has been registered for the computer class</p>
                        </div>
                    </div>
                    
                    <hr>
                    <div class="action-buttons">
                        <a href="download_letter.php?id=<?php echo $registration_id; ?>" class="btn-download">
                            <i class="fas fa-download me-2"></i> Download Letter
                        </a>
                        <a href="user/my_class_portal.php" class="btn-dashboard">
                            <i class="fas fa-laptop-code me-2"></i> View My Classes
                        </a>
                        <a href="user/dashboard.php" class="btn-dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </div>
                    <br>
                    <a href="index.php" class="btn btn-outline-success btn-sm">Go to Home Page</a>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if($error_message): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <?php if(!$registration_success): ?>
            <form method="POST" action="" id="registrationForm">
                <!-- Select Class -->
                <div class="input-group-custom">
                    <i class="fas fa-laptop-code"></i>
                    <select name="class_id" class="form-control-custom" required id="classSelect">
                        <option value="">Select a class</option>
                        <?php foreach($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" data-fee="<?php echo $class['fee_amount'] ?? 1000; ?>">
                            <?php echo htmlspecialchars($class['class_name']); ?> - KES <?php echo number_format($class['fee_amount'] ?? 1000, 2); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Full Name -->
                <div class="input-group-custom">
                    <i class="fas fa-user"></i>
                    <input type="text" name="full_name" class="form-control-custom" placeholder="Full Name" required>
                </div>

                <!-- Username -->
                <div class="input-group-custom">
                    <i class="fas fa-at"></i>
                    <input type="text" name="username" class="form-control-custom" placeholder="Username" required>
                </div>

                <!-- Email Address -->
                <div class="input-group-custom">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control-custom" placeholder="Email Address" required>
                </div>

                <!-- Phone Number -->
                <div class="input-group-custom">
                    <i class="fas fa-phone"></i>
                    <input type="tel" name="phone" class="form-control-custom" placeholder="Phone Number" required>
                </div>

                <!-- Password -->
                <div class="input-group-custom">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control-custom" placeholder="Password (min. 6 characters)" required minlength="6" id="password">
                </div>

                <!-- Confirm Password -->
                <div class="input-group-custom">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" name="confirm_password" class="form-control-custom" placeholder="Confirm Password" required id="confirmPassword">
                </div>

                <!-- Amount Paid -->
                <div class="input-group-custom">
                    <i class="fas fa-money-bill-wave"></i>
                    <input type="number" name="amount_paid" id="amountPaid" class="form-control-custom" placeholder="Amount Paid (Min. KES 200)" min="200" step="100" required>
                </div>

                <!-- Total Fee Display -->
                <div class="amount-display">
                    <small>Total Class Fee</small>
                    <h3 id="totalFeeDisplay">KES 0.00</h3>
                </div>

                <!-- Balance Display -->
                <div class="balance-display" id="balanceDisplay" style="display: none;">
                    <small>Remaining Balance</small>
                    <div class="balance-amount" id="balanceAmount">KES 0.00</div>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="register_class" class="btn-register" id="submitBtn">
                    <i class="fas fa-check-circle me-2"></i> Complete Registration
                </button>

                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                    <br>
                    <small class="go-home-link">or <a href="index.php">Go to Home Page</a></small>
                </div>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> An account will be created with your email and password
                    </small>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-row">
                <div class="footer-col">
                    <h5>Makueni County Innovation Hub</h5>
                    <p>Empowering innovators, entrepreneurs, and tech enthusiasts in Makueni County to transform ideas into reality through mentorship, training, and funding opportunities.</p>
                </div>
                <div class="footer-col">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="register_class.php">Computer Classes</a></li>
                        <li><a href="ajiry/studio_book.php">AJIRY Services</a></li>
                        <li><a href="index.php#events">Events</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#feedbackModal">Feedback</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h5>Contact Information</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i> P.O. Box 78 – 90-300 Wote, Makueni</p>
                    <p><i class="fas fa-phone me-2"></i> 0795717885</p>
                    <p><i class="fas fa-phone me-2"></i> 0780717885</p>
                    <p><i class="fas fa-envelope me-2"></i> info@makueni.go.ke</p>
                </div>
                <div class="footer-col">
                    <h5>Follow Us</h5>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white fs-5"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white fs-5"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white fs-5"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white fs-5"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 County Government of Makueni. All rights reserved.</p>
                <p>Empowering Innovators, Transforming Lives | Wote, Makueni County</p>
            </div>
        </div>
    </footer>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #1b4332, #2d6a4f); color: white;">
                    <h4 class="modal-title"><i class="fas fa-comment me-2"></i>Share Your Feedback</h4>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="submit_feedback.php">
                        <div class="mb-3">
                            <label class="form-label">Your Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rating *</label>
                            <select name="rating" class="form-select" required>
                                <option value="5">★★★★★ (Excellent)</option>
                                <option value="4">★★★★☆ (Good)</option>
                                <option value="3">★★★☆☆ (Average)</option>
                                <option value="2">★★☆☆☆ (Poor)</option>
                                <option value="1">★☆☆☆☆ (Very Poor)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Your Message *</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Tell us about your experience..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-bold">
                            <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const classSelect = document.getElementById('classSelect');
        const totalFeeDisplay = document.getElementById('totalFeeDisplay');
        const amountPaid = document.getElementById('amountPaid');
        const balanceDisplay = document.getElementById('balanceDisplay');
        const balanceAmount = document.getElementById('balanceAmount');
        let selectedFee = 0;
        
        classSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const fee = selectedOption.getAttribute('data-fee');
            if (fee && fee > 0) {
                selectedFee = parseFloat(fee);
                totalFeeDisplay.textContent = 'KES ' + selectedFee.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                updateBalance();
            } else {
                totalFeeDisplay.textContent = 'KES 0.00';
                selectedFee = 0;
                balanceDisplay.style.display = 'none';
            }
        });
        
        function updateBalance() {
            const amount = parseFloat(amountPaid.value) || 0;
            if (selectedFee > 0 && amount > 0) {
                const balance = selectedFee - amount;
                if (balance >= 0) {
                    balanceAmount.textContent = 'KES ' + balance.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    balanceDisplay.style.display = 'block';
                    if (balance === 0) {
                        balanceDisplay.style.background = '#d4edda';
                        balanceDisplay.style.borderLeftColor = '#28a745';
                        balanceAmount.style.color = '#155724';
                    } else {
                        balanceDisplay.style.background = '#fff3cd';
                        balanceDisplay.style.borderLeftColor = '#ffc107';
                        balanceAmount.style.color = '#856404';
                    }
                } else {
                    balanceDisplay.style.display = 'none';
                }
            } else {
                balanceDisplay.style.display = 'none';
            }
        }
        
        amountPaid.addEventListener('input', function() {
            updateBalance();
            if (this.value < 200 && this.value != '') {
                this.setCustomValidity('Minimum amount is KES 200.00');
            } else {
                this.setCustomValidity('');
            }
        });
        
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');
        
        function validatePasswordMatch() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        password.addEventListener('change', validatePasswordMatch);
        confirmPassword.addEventListener('keyup', validatePasswordMatch);
    </script>
</body>
</html>