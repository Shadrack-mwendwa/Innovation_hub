<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once '../config/database.php';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $id_number = trim($_POST['id_number']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $services = isset($_POST['services']) ? implode(', ', $_POST['services']) : '';
    $preferred_date = $_POST['preferred_date'];
    $additional_info = trim($_POST['additional_info']);
    
    $errors = [];
    
    // Validation
    if(empty($full_name)) $errors[] = "Full name is required";
    if(empty($id_number)) $errors[] = "ID number is required";
    if(empty($phone_number)) $errors[] = "Phone number is required";
    if(empty($email)) $errors[] = "Email address is required (for booking confirmation)";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address";
    if(empty($services)) $errors[] = "Please select at least one service";
    if(empty($preferred_date)) $errors[] = "Preferred date is required";
    
    if(empty($errors)) {
        try {
            // Generate unique booking code
            $booking_code = 'AJIRY-' . strtoupper(uniqid());
            
            $stmt = $pdo->prepare("
                INSERT INTO ajiry_bookings (
                    booking_code, full_name, id_number, phone_number, email, 
                    services, preferred_date, additional_info, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $booking_code, $full_name, $id_number, $phone_number, $email,
                $services, $preferred_date, $additional_info
            ]);
            
            // Send email confirmation
            $to = $email;
            $subject = "AJIRY Studio Booking Confirmation - " . $booking_code;
            $message = "Dear " . $full_name . ",\n\n";
            $message .= "Thank you for booking AJIRY Studio!\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "YOUR BOOKING DETAILS\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "Booking Code: " . $booking_code . "\n";
            $message .= "Full Name: " . $full_name . "\n";
            $message .= "ID Number: " . $id_number . "\n";
            $message .= "Phone: " . $phone_number . "\n";
            $message .= "Selected Services: " . $services . "\n";
            $message .= "Preferred Date: " . date('l, F d, Y', strtotime($preferred_date)) . "\n\n";
            
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "IMPORTANT INFORMATION\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "• Maximum 3 people TOTAL per session (including staff)\n";
            $message .= "• Session duration: 2 hours\n";
            $message .= "• Please arrive 15 minutes early\n";
            $message .= "• Valid ID required for entry\n";
            $message .= "• FREE booking - No payment required\n";
            $message .= "• Cancellation requires 24 hours notice\n\n";
            
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "STUDIO RULES\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "✓ Monday to Friday only\n";
            $message .= "✓ Max 3 people TOTAL per session\n";
            $message .= "✓ Session duration: 2 hours\n";
            $message .= "✓ FREE Booking - No payment required\n";
            $message .= "✓ Valid ID required\n";
            $message .= "✓ Arrive 15 minutes early\n";
            $message .= "✓ Cancellation: 24hrs notice\n\n";
            
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "WHAT TO BRING\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "• Valid National ID or Passport\n";
            $message .= "• Your booking code: " . $booking_code . "\n";
            $message .= "• Any materials you need for your session\n\n";
            
            $message .= "We will contact you within 24 hours to confirm your booking.\n\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "NEED TO CHECK YOUR STATUS?\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            $message .= "Use your booking code: " . $booking_code . "\n\n";
            
            $message .= "Best regards,\n";
            $message .= "AJIRY Studio Team\n";
            $message .= "Makueni County Innovation Hub\n";
            $message .= "Email: info@makueni.go.ke | Phone: 0795717885";
            
            $headers = "From: AJIRY Studio <bookings@makueniinnovationhub.co.ke>\r\n";
            $headers .= "Reply-To: info@makueni.go.ke\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "X-Priority: 1\r\n";
            
            mail($to, $subject, $message, $headers);
            
            $_SESSION['booking_success'] = "Booking submitted successfully! Check your email for confirmation. Your booking code is: " . $booking_code;
            header("Location: studio_book.php");
            exit();
            
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    if(!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_data'] = $_POST;
        header("Location: studio_book.php");
        exit();
    }
}

// Get available dates (next 30 days, weekdays only)
$available_dates = [];
$date = new DateTime();
$date->modify('+1 day');
$count = 0;
while($count < 14) {
    if($date->format('N') < 6) { // Monday to Friday only
        $available_dates[] = $date->format('Y-m-d');
        $count++;
    }
    $date->modify('+1 day');
}

$next_available = isset($available_dates[0]) ? date('l, F d, Y', strtotime($available_dates[0])) : 'Check back soon';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book AJIRY Studio - Makueni County Innovation Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand { display: flex; align-items: center; }
        .county-logo {
            height: 60px;
            width: 60px;
            margin-right: 12px;
            background: white;
            border-radius: 50%;
            padding: 8px;
            object-fit: contain;
            border: 2px solid #FFD700;
        }
        .brand-title { font-size: 1.1rem; font-weight: 700; color: white; }
        .brand-subtitle { font-size: 0.65rem; color: #FFD700; }
        
        .booking-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 40px 0;
            color: white;
            text-align: center;
            margin-top: 70px;
        }
        
        .booking-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: -30px;
            margin-bottom: 50px;
        }
        
        .booking-card-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 25px;
            color: white;
        }
        
        .booking-card-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: #1a5c3a;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 12px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #1a5c3a;
            box-shadow: 0 0 0 0.2rem rgba(26,92,58,0.25);
        }
        
        .btn-submit {
            background: #1a5c3a;
            color: white;
            border-radius: 50px;
            padding: 14px 30px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-submit:hover {
            background: #0a3d2a;
            transform: translateY(-2px);
        }
        
        .info-card {
            background: #e8f5e9;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .rules-list {
            list-style: none;
            padding: 0;
        }
        
        .rules-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .rules-list li:last-child {
            border-bottom: none;
        }
        
        .rules-list i {
            color: #1a5c3a;
            width: 25px;
        }
        
        .badge-free {
            background: #28a745;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 12px;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            border-radius: 12px;
        }
        
        .email-required-note {
            background: #fff3cd;
            border-left: 4px solid #FFD700;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 5px;
        }
        
        footer { background: #0d3d2a; color: white; padding: 30px 0 15px; margin-top: 50px; }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; }
        footer a:hover { color: #FFD700; }
        
        @media (max-width: 768px) {
            .county-logo { height: 45px; width: 45px; }
            .booking-card-body { padding: 20px; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="county-logo" onerror="this.style.display='none'">
                <div>
                    <div class="brand-title">MAKUENI COUNTY</div>
                    <div class="brand-subtitle">INNOVATION HUB</div>
                </div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="../user/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                    <?php else: ?>
                        
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="booking-header">
        <div class="container">
            <h1><i class="fas fa-microphone-alt me-2"></i>Book AJIRY Studio</h1>
            <p class="lead mb-0">Professional recording studio - Free booking, get email confirmation</p>
        </div>
    </div>

    <div class="container">
        <!-- Success/Error Messages -->
        <?php if(isset($_SESSION['booking_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['booking_success']; unset($_SESSION['booking_success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['booking_errors'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i> Please fix the following errors:
                <ul class="mb-0 mt-2">
                    <?php foreach($_SESSION['booking_errors'] as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['booking_errors']); ?>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="booking-card">
                    <div class="booking-card-header">
                        <h3 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Studio Booking Form</h3>
                        <p class="mb-0 mt-2 opacity-75">Fill in your details to book AJIRY Studio</p>
                    </div>
                    <div class="booking-card-body">
                        <div class="info-card">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <span class="badge-free mb-2"><i class="fas fa-gift me-1"></i> FREE BOOKING</span>
                                    <h4 class="mt-2">No payment required</h4>
                                    <p class="text-muted small mb-0">Maximum 3 people TOTAL per session (including staff)</p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <i class="fas fa-users fa-3x text-success opacity-50"></i>
                                    <span class="d-block fw-bold text-success">Max 3 people</span>
                                    <span class="text-muted small">Session: 2 hours</span>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['booking_data']['full_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ID Number *</label>
                                    <input type="text" name="id_number" class="form-control" value="<?php echo htmlspecialchars($_SESSION['booking_data']['id_number'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($_SESSION['booking_data']['phone_number'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['booking_data']['email'] ?? ''); ?>" required>
                                    <div class="email-required-note">
                                        <small><i class="fas fa-envelope me-1"></i> Required for booking confirmation</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Select Services * (Select multiple)</label>
                                <div class="row">
                                    <?php
                                    $services_list = [
                                        'Music Production' => '🎵',
                                        'Guitar Recording' => '🎸',
                                        'Shoot Video' => '📹',
                                        'Voice Over' => '🎤',
                                        'Podcast Recording' => '🎙️',
                                        'Mixing & Mastering' => '🎧',
                                        'Other' => '📝'
                                    ];
                                    $selected_services = $_SESSION['booking_data']['services'] ?? [];
                                    foreach($services_list as $service => $icon):
                                    ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="services[]" value="<?php echo $service; ?>" id="service_<?php echo md5($service); ?>" <?php echo (in_array($service, (array)$selected_services)) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="service_<?php echo md5($service); ?>">
                                                <?php echo $icon; ?> <?php echo $service; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Preferred Date *</label>
                                    <select name="preferred_date" class="form-select" required>
                                        <option value="">Select preferred date</option>
                                        <?php foreach($available_dates as $date): ?>
                                        <option value="<?php echo $date; ?>" <?php echo (($_SESSION['booking_data']['preferred_date'] ?? '') == $date) ? 'selected' : ''; ?>>
                                            <?php echo date('l, F d, Y', strtotime($date)); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Monday to Friday only</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Additional Information</label>
                                    <textarea name="additional_info" class="form-control" rows="3" placeholder="Any special requests or additional information"><?php echo htmlspecialchars($_SESSION['booking_data']['additional_info'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Only 3 people TOTAL allowed per session (including staff member). Session duration: 2 hours.
                            </div>
                            
                            <button type="submit" class="btn-submit mt-3">
                                <i class="fas fa-check-circle me-2"></i> Submit Booking Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Studio Rules Card -->
                <div class="booking-card">
                    <div class="booking-card-header">
                        <h4 class="mb-0"><i class="fas fa-gavel me-2"></i>Studio Rules</h4>
                    </div>
                    <div class="booking-card-body">
                        <ul class="rules-list">
                            <li><i class="fas fa-calendar me-2"></i> Monday to Friday only</li>
                            <li><i class="fas fa-users me-2"></i> Max 3 people TOTAL (including staff)</li>
                            <li><i class="fas fa-clock me-2"></i> Session: 2 hours</li>
                            <li><i class="fas fa-gift me-2"></i> FREE Booking - No payment</li>
                            <li><i class="fas fa-id-card me-2"></i> Valid ID required</li>
                            <li><i class="fas fa-clock me-2"></i> Arrive 15 minutes early</li>
                            <li><i class="fas fa-calendar-times me-2"></i> Cancellation: 24hrs notice</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Available Services Card -->
                <div class="booking-card">
                    <div class="booking-card-header">
                        <h4 class="mb-0"><i class="fas fa-list-check me-2"></i>Available Services</h4>
                    </div>
                    <div class="booking-card-body">
                        <div class="row">
                            <div class="col-6 mb-2">
                                <span class="badge bg-light text-dark p-2 w-100"><i class="fas fa-music me-1"></i> Music Production</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="badge bg-light text-dark p-2 w-100"><i class="fas fa-guitar me-1"></i> Guitar Recording</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="badge bg-light text-dark p-2 w-100"><i class="fas fa-video me-1"></i> Video Shooting</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="badge bg-light text-dark p-2 w-100"><i class="fas fa-microphone me-1"></i> Voice Over</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="badge bg-light text-dark p-2 w-100"><i class="fas fa-podcast me-1"></i> Podcast Recording</span>
                            </div>
                            <div class="col-6 mb-2">
                                <span class="badge bg-light text-dark p-2 w-100"><i class="fas fa-sliders-h me-1"></i> Mixing & Mastering</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Email Confirmation Card -->
                <div class="booking-card">
                    <div class="booking-card-header">
                        <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Email Confirmation</h4>
                    </div>
                    <div class="booking-card-body">
                        <div class="text-center">
                            <i class="fas fa-envelope-open-text fa-3x text-success mb-3"></i>
                            <p class="small"><strong>Email is required</strong> for booking confirmation</p>
                            <p class="small">You will receive an email with your booking details and unique booking code.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold">Makueni County Innovation Hub</h6>
                    <p class="small opacity-75">Empowering innovators, entrepreneurs, and tech enthusiasts.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> County Government of Makueni. All rights reserved.</p>
                    <p class="small mt-1">Powered by Makueni County Innovation Hub</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>