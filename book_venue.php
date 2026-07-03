<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $expected_attendees = (int)$_POST['expected_attendees'];
    $number_of_people = (int)$_POST['number_of_people'];
    $organizer_name = trim($_POST['organizer_name']);
    $organizer_type = $_POST['organizer_type'];
    $phone = trim($_POST['phone']);
    $alt_phone = trim($_POST['alt_phone']);
    $special_requests = trim($_POST['special_requests']);
    
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO event_bookings (
                event_name, event_date, event_time, expected_attendees, number_of_people,
                organizer_name, organizer_type, phone, alt_phone, special_requests, 
                user_id, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $stmt->execute([
            $event_name, $event_date, $event_time, $expected_attendees, $number_of_people,
            $organizer_name, $organizer_type, $phone, $alt_phone, $special_requests,
            $user_id
        ]);
        
        $success = "Your venue booking request has been submitted! Admin will review and confirm your booking.";
        
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Greenpark Venue - Makueni County Innovation Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f5f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .booking-header { background: linear-gradient(135deg, #0a3d2a, #1a5c3a); color: white; padding: 40px 0; }
        .booking-form { background: white; border-radius: 20px; padding: 30px; margin-top: -30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-label { font-weight: 600; color: #1a5c3a; }
        .btn-submit { background: #1a5c3a; color: white; padding: 12px 30px; border-radius: 30px; font-weight: 600; }
        .btn-submit:hover { background: #0a3d2a; }
        .venue-info { background: #e8f5e9; border-radius: 15px; padding: 20px; margin-bottom: 30px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-success-custom" style="background: linear-gradient(135deg, #0a3d2a, #1a5c3a);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo/makueni-logo.png" alt="Logo" style="height: 40px;">
                <span class="ms-2">Greenpark Venue Booking</span>
            </a>
            <a href="index.php" class="btn btn-outline-light">Back to Home</a>
        </div>
    </nav>

    <div class="booking-header">
        <div class="container text-center">
            <h1><i class="fas fa-calendar-plus me-2"></i>Book Greenpark Venue</h1>
            <p class="lead">Host your event at Makueni County's premier event space</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="booking-form">
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    <a href="index.php" class="btn btn-success btn-sm ms-3">Return to Home</a>
                </div>
            <?php else: ?>
            
            <div class="venue-info">
                <div class="row text-center">
                    <div class="col-md-3">
                        <i class="fas fa-chair fa-2x text-success mb-2"></i>
                        <h6>Seating Capacity</h6>
                        <p>Up to 100 guests</p>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-wifi fa-2x text-success mb-2"></i>
                        <h6>High-Speed WiFi</h6>
                        <p>Free internet</p>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-video fa-2x text-success mb-2"></i>
                        <h6>AV Equipment</h6>
                        <p>Projector & Sound</p>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-clock fa-2x text-success mb-2"></i>
                        <h6>Flexible Hours</h6>
                        <p>8 AM - 8 PM</p>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <h4 class="mb-4">Event Information</h4>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Event Name *</label>
                        <input type="text" name="event_name" class="form-control" required>
                    </div>
                    <!-- Start Time -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        
                        <!-- End Time -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">End Time *</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Event Time *</label>
                        <input type="time" name="event_time" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Expected Attendees *</label>
                        <input type="number" name="expected_attendees" class="form-control" placeholder="Estimated number of attendees" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Number of People *</label>
                        <input type="number" name="number_of_people" class="form-control" placeholder="Exact number for arrangements" required>
                    </div>
                </div>
                
                <h4 class="mb-4 mt-3">Organizer Information</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Organizer Name *</label>
                        <input type="text" name="organizer_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Organizer Type *</label>
                        <select name="organizer_type" class="form-select" required>
                            <option value="individual">Individual</option>
                            <option value="company">Company/Organization</option>
                            <option value="institution">Educational Institution</option>
                            <option value="government">Government Agency</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Alternative Phone</label>
                        <input type="tel" name="alt_phone" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Special Requests</label>
                        <textarea name="special_requests" class="form-control" rows="3" placeholder="Any special requirements (catering, equipment, setup, etc.)"></textarea>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-submit"><i class="fas fa-check-circle me-2"></i> Submit Booking Request</button>
                    <a href="index.php" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>