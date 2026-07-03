<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once '../config/database.php';

$booking_status = null;
$error = null;

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_code = trim($_POST['booking_code']);
    $email = trim($_POST['email']);
    
    if(empty($booking_code)) {
        $error = "Please enter your booking code";
    } elseif(empty($email)) {
        $error = "Please enter your email address";
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM ajiry_bookings 
                WHERE booking_code = ? AND email = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$booking_code, $email]);
            $booking_status = $stmt->fetch();
            
            if(!$booking_status) {
                $error = "No booking found with the provided code and email. Please check and try again.";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check AJIRY Booking Status - Makueni County Innovation Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f5f5f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
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
        
        .page-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 60px 0;
            color: white;
            text-align: center;
            margin-top: 70px;
        }
        .page-header h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 10px; }
        
        .check-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: -40px;
            margin-bottom: 50px;
        }
        .check-card-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 25px;
            color: white;
            text-align: center;
        }
        .check-card-body { padding: 35px; }
        
        .form-label { font-weight: 600; color: #1a5c3a; margin-bottom: 8px; }
        .form-control {
            border-radius: 10px;
            border: 1px solid #ddd;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #1a5c3a;
            box-shadow: 0 0 0 0.2rem rgba(26,92,58,0.25);
        }
        
        .btn-check-status {
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
        .btn-check-status:hover { background: #0a3d2a; transform: translateY(-2px); }
        
        .status-card {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 25px;
            margin-top: 30px;
            border: 1px solid #e0e0e0;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-pending { background: #ffc107; color: #856404; }
        .status-confirmed { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        .status-completed { background: #17a2b8; color: white; }
        
        .alert-danger { background: #f8d7da; border-color: #f5c6cb; color: #721c24; border-radius: 12px; }
        .alert-success { background: #d4edda; border-color: #c3e6cb; color: #155724; border-radius: 12px; }
        .alert-warning { background: #fff3cd; border-color: #ffeeba; color: #856404; border-radius: 12px; }
        .alert-info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; border-radius: 12px; }
        
        .info-text { font-size: 12px; color: #6c757d; margin-top: 5px; }
        .detail-row { padding: 10px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 600; color: #1a5c3a; }
        
        footer { background: #0d3d2a; color: white; padding: 30px 0 20px; margin-top: 50px; }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.85rem; transition: all 0.2s ease; }
        footer a:hover { color: #FFD700; }
        .footer-links a { margin: 0 12px; }
        
        @media (max-width: 768px) {
            .county-logo { height: 45px; width: 45px; }
            .check-card-body { padding: 20px; }
            .page-header h1 { font-size: 1.8rem; }
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
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-calendar-check me-2"></i>Check Your AJIRY Booking Status</h1>
            <p class="lead mb-0">Enter your booking code and email to view your booking status</p>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="check-card">
                    <div class="check-card-header">
                        <h3><i class="fas fa-search me-2"></i>Find Your Booking</h3>
                        <p class="mb-0 opacity-75">Enter the details from your email confirmation</p>
                    </div>
                    <div class="check-card-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label">Booking Reference Number</label>
                                <input type="text" name="booking_code" class="form-control" placeholder="e.g., AJIRY-5F3E8A2B" value="<?php echo htmlspecialchars($_POST['booking_code'] ?? ''); ?>" required>
                                <div class="info-text">Enter the booking code you received in your email</div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="Email used when booking" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                <div class="info-text">Enter the email address you used for this booking</div>
                            </div>
                            
                            <button type="submit" class="btn-check-status">
                                <i class="fas fa-search me-2"></i> Check Status
                            </button>
                        </form>
                        
                        <?php if($booking_status): ?>
                            <div class="status-card">
                                <h4 class="mb-4"><i class="fas fa-info-circle me-2"></i>Booking Details</h4>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Booking Code:</div>
                                        <div class="col-sm-8"><strong><?php echo htmlspecialchars($booking_status['booking_code']); ?></strong></div>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Status:</div>
                                        <div class="col-sm-8">
                                            <span class="status-badge status-<?php echo $booking_status['status']; ?>">
                                                <?php echo ucfirst($booking_status['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Full Name:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($booking_status['full_name']); ?></div>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">ID Number:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($booking_status['id_number']); ?></div>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Phone Number:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($booking_status['phone_number']); ?></div>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Email:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($booking_status['email']); ?></div>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Selected Services:</div>
                                        <div class="col-sm-8"><?php echo nl2br(htmlspecialchars($booking_status['services'])); ?></div>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Preferred Date:</div>
                                        <div class="col-sm-8"><?php echo date('l, F d, Y', strtotime($booking_status['preferred_date'])); ?></div>
                                    </div>
                                </div>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Booked On:</div>
                                        <div class="col-sm-8"><?php echo date('F d, Y g:i A', strtotime($booking_status['created_at'])); ?></div>
                                    </div>
                                </div>
                                
                                <?php if($booking_status['additional_info']): ?>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Additional Info:</div>
                                        <div class="col-sm-8"><?php echo nl2br(htmlspecialchars($booking_status['additional_info'])); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($booking_status['status'] == 'confirmed'): ?>
                                <div class="alert alert-success mt-4 mb-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Your booking is confirmed!</strong> Please arrive 15 minutes before your scheduled time. Don't forget to bring your ID.
                                </div>
                                <?php elseif($booking_status['status'] == 'pending'): ?>
                                <div class="alert alert-warning mt-4 mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Your booking is pending confirmation.</strong> We will contact you within 24 hours to confirm your booking.
                                </div>
                                <?php elseif($booking_status['status'] == 'cancelled'): ?>
                                <div class="alert alert-danger mt-4 mb-0">
                                    <i class="fas fa-times-circle me-2"></i>
                                    <strong>Your booking has been cancelled.</strong> Please contact us at 0795717885 for more information.
                                </div>
                                <?php elseif($booking_status['status'] == 'completed'): ?>
                                <div class="alert alert-info mt-4 mb-0">
                                    <i class="fas fa-check-double me-2"></i>
                                    <strong>Booking completed!</strong> Thank you for using AJIRY Studio. We hope you had a great experience!
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Help Card -->
                <div class="check-card mt-4">
                    <div class="check-card-header">
                        <h4 class="mb-0"><i class="fas fa-question-circle me-2"></i>Need Help?</h4>
                    </div>
                    <div class="check-card-body">
                        <p class="mb-2">If you can't find your booking or need assistance:</p>
                        <ul class="mb-3">
                            <li>Check your email spam folder for the booking confirmation</li>
                            <li>Make sure you're using the correct email address</li>
                            <li>The booking code is case-sensitive</li>
                        </ul>
                        <div class="text-center pt-2">
                            <p class="mb-1"><strong>Contact us:</strong></p>
                            <p class="mb-0">
                                <i class="fas fa-phone me-2"></i> 0795717885<br>
                                <i class="fas fa-envelope me-2"></i> info@makueni.go.ke
                            </p>
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
                    <div class="footer-links">
                        <a href="../index.php"><i class="fas fa-home me-1"></i> Home</a>
                        <a href="studio_book.php"><i class="fas fa-microphone-alt me-1"></i> Book Studio</a>
                    </div>
                    <p class="small mt-2 mb-0">&copy; <?php echo date('Y'); ?> County Government of Makueni. All rights reserved.</p>
                    <p class="small mt-1">Powered by Makueni County Innovation Hub</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>