<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'config/database.php';

$booking_status = null;
$error = null;
$debug_info = null;

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $search_type = $_POST['search_type'] ?? 'code';
    $booking_code = trim($_POST['booking_code'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if($search_type == 'code' && empty($booking_code)) {
        $error = "Please enter your booking code";
    } elseif($search_type == 'email' && empty($email)) {
        $error = "Please enter your email address";
    } else {
        try {
            if($search_type == 'code') {
                // First try exact match
                $stmt = $pdo->prepare("
                    SELECT * FROM event_bookings 
                    WHERE booking_code = ?
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$booking_code]);
                $booking_status = $stmt->fetch();
                
                // If not found, try with LIKE (case insensitive)
                if(!$booking_status) {
                    $stmt = $pdo->prepare("
                        SELECT * FROM event_bookings 
                        WHERE booking_code LIKE ?
                        ORDER BY created_at DESC
                        LIMIT 1
                    ");
                    $stmt->execute(['%' . $booking_code . '%']);
                    $booking_status = $stmt->fetch();
                }
                
                // If still not found, show similar codes
                if(!$booking_status) {
                    $stmt = $pdo->prepare("
                        SELECT booking_code FROM event_bookings 
                        WHERE booking_code LIKE ?
                        LIMIT 5
                    ");
                    $stmt->execute(['%' . substr($booking_code, 0, 5) . '%']);
                    $similar_codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if(!empty($similar_codes)) {
                        $error = "No booking found with code: <strong>" . htmlspecialchars($booking_code) . "</strong>";
                        $debug_info = "Did you mean: " . implode(', ', array_map('htmlspecialchars', $similar_codes));
                    } else {
                        $error = "No booking found with the provided information. Please check and try again.";
                    }
                }
            } else {
                // Search by email - case insensitive
                $stmt = $pdo->prepare("
                    SELECT * FROM event_bookings 
                    WHERE LOWER(email) = LOWER(?)
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$email]);
                $booking_status = $stmt->fetch();
                
                if(!$booking_status) {
                    $error = "No bookings found for email: <strong>" . htmlspecialchars($email) . "</strong>";
                }
            }
            
            // If found, check if code was retrieved
            if($booking_status && !isset($booking_status['id'])) {
                $error = "Booking found but data is incomplete. Please contact support.";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get total bookings count for debugging (admin only)
$total_bookings = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_bookings");
    $total_bookings = $stmt->fetchColumn();
} catch(PDOException $e) {
    $total_bookings = 0;
}

$page_title = 'Check Event Booking Status - Makueni County Innovation Hub';
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
        
        .page-header {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            padding: 60px 0;
            color: white;
            text-align: center;
            margin-top: 70px;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
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
        
        .check-card-header h3 {
            margin-bottom: 5px;
        }
        
        .check-card-body {
            padding: 35px;
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
        
        .btn-check-status:hover {
            background: #0a3d2a;
            transform: translateY(-2px);
        }
        
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
        
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            border-radius: 12px;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 12px;
        }
        
        .detail-row {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #1a5c3a;
        }
        
        .search-type-tab {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .search-type-btn {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: none;
            border: none;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 10px;
        }
        
        .search-type-btn.active {
            background: #1a5c3a;
            color: white;
        }
        
        .search-section {
            display: none;
        }
        
        .search-section.active {
            display: block;
        }
        
        .debug-info {
            background: #f0f0f0;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px;
            color: #555;
            margin-top: 10px;
        }
        
        footer { background: #0d3d2a; color: white; padding: 30px 0 15px; margin-top: 50px; }
        footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; }
        footer a:hover { color: #FFD700; }
        
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
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="county-logo" onerror="this.style.display='none'">
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="user/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <h1><i class="fas fa-calendar-check me-2"></i>Check Your Event Booking Status</h1>
            <p class="lead mb-0">Enter your booking code or email to view your booking status</p>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="check-card">
                    <div class="check-card-header">
                        <h3><i class="fas fa-search me-2"></i>Find Your Event Booking</h3>
                        <p class="mb-0 opacity-75">Enter the details from your confirmation</p>
                    </div>
                    <div class="check-card-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                                <?php if($debug_info): ?>
                                    <div class="debug-info"><i class="fas fa-lightbulb me-2"></i> <?php echo $debug_info; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($booking_status): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> Booking found! Here are the details:
                            </div>
                        <?php endif; ?>
                        
                        <!-- Search Type Tabs -->
                        <div class="search-type-tab">
                            <button type="button" class="search-type-btn active" data-type="code">
                                <i class="fas fa-ticket-alt me-2"></i> Search by Booking Code
                            </button>
                            <button type="button" class="search-type-btn" data-type="email">
                                <i class="fas fa-envelope me-2"></i> Search by Email
                            </button>
                        </div>
                        
                        <form method="POST" id="statusForm">
                            <input type="hidden" name="search_type" id="search_type" value="code">
                            
                            <!-- Search by Booking Code -->
                            <div id="searchByCode" class="search-section active">
                                <div class="mb-4">
                                    <label class="form-label">Booking Reference Number</label>
                                    <input type="text" name="booking_code" class="form-control" placeholder="e.g., BK-5F3E8A2B or GPBK-20260608-310" value="<?php echo htmlspecialchars($_POST['booking_code'] ?? ''); ?>">
                                    <div class="info-text mt-1 text-muted small">Enter the booking code you received in your email (case-sensitive)</div>
                                    <?php if($total_bookings > 0): ?>
                                        <div class="info-text mt-1 text-muted small"><i class="fas fa-database me-1"></i> <?php echo $total_bookings; ?> total bookings in system</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Search by Email -->
                            <div id="searchByEmail" class="search-section">
                                <div class="mb-4">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" placeholder="Email used when booking" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    <div class="info-text mt-1 text-muted small">Enter the email address you used for this booking</div>
                                </div>
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
                                        <div class="col-sm-4 detail-label">Event Name:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($booking_status['event_name']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Event Date:</div>
                                        <div class="col-sm-8"><?php echo date('l, F d, Y', strtotime($booking_status['event_date'])); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Event Time:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($booking_status['start_time'] ?? 'TBA') . ' - ' . htmlspecialchars($booking_status['end_time'] ?? 'TBA'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Organizer Name:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($booking_status['organizer_name']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Phone Number:</div>
                                        <div class="col-sm-8"><?php echo htmlspecialchars($booking_status['phone']); ?></div>
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
                                        <div class="col-sm-4 detail-label">Expected Guests:</div>
                                        <div class="col-sm-8"><?php echo $booking_status['number_of_people']; ?> people</div>
                                    </div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Booked On:</div>
                                        <div class="col-sm-8"><?php echo date('F d, Y g:i A', strtotime($booking_status['created_at'])); ?></div>
                                    </div>
                                </div>
                                
                                <?php if($booking_status['special_requests']): ?>
                                <div class="detail-row">
                                    <div class="row">
                                        <div class="col-sm-4 detail-label">Special Requests:</div>
                                        <div class="col-sm-8"><?php echo nl2br(htmlspecialchars($booking_status['special_requests'])); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($booking_status['status'] == 'confirmed'): ?>
                                <div class="alert alert-success mt-4 mb-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Your booking is confirmed!</strong> We look forward to hosting your event at Greenpark.
                                </div>
                                <?php elseif($booking_status['status'] == 'pending'): ?>
                                <div class="alert alert-warning mt-4 mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Your booking is pending confirmation.</strong> We will contact you within 24 hours.
                                </div>
                                <?php elseif($booking_status['status'] == 'cancelled'): ?>
                                <div class="alert alert-danger mt-4 mb-0">
                                    <i class="fas fa-times-circle me-2"></i>
                                    <strong>Your booking has been cancelled.</strong> Please contact us for more information.
                                </div>
                                <?php elseif($booking_status['status'] == 'completed'): ?>
                                <div class="alert alert-info mt-4 mb-0">
                                    <i class="fas fa-check-double me-2"></i>
                                    <strong>Event completed!</strong> Thank you for choosing Greenpark.
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
                            <li>Make sure you're using the correct email address or booking code</li>
                            <li>The booking code is case-sensitive</li>
                            <li>Booking codes can be in formats like: <code>BK-XXXXXXXX</code> or <code>GPBK-YYYYMMDD-NNN</code></li>
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
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> County Government of Makueni. All rights reserved.</p>
                    <p class="small mt-1">Powered by Makueni County Innovation Hub</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search type toggle
        const typeBtns = document.querySelectorAll('.search-type-btn');
        const searchByCode = document.getElementById('searchByCode');
        const searchByEmail = document.getElementById('searchByEmail');
        const searchTypeInput = document.getElementById('search_type');
        
        typeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                typeBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const type = this.getAttribute('data-type');
                searchTypeInput.value = type;
                
                if(type === 'code') {
                    searchByCode.classList.add('active');
                    searchByEmail.classList.remove('active');
                } else {
                    searchByCode.classList.remove('active');
                    searchByEmail.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>