<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

// Get current view
$current_view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

// ========== FUNCTION TO AUTO-UPDATE EVENT STATUS BASED ON DATE & TIME ==========
function autoUpdateEventStatuses($pdo) {
    $now = date('Y-m-d H:i:s'); // Current date and time
    
    // Update events to 'ongoing' if current time is between start and end
    $pdo->prepare("
        UPDATE events 
        SET status = 'ongoing' 
        WHERE deleted_at IS NULL 
        AND status != 'ongoing'
        AND start_datetime <= ? 
        AND end_datetime >= ?
    ")->execute([$now, $now]);
    
    // Update events to 'upcoming' if current time is before start
    $pdo->prepare("
        UPDATE events 
        SET status = 'upcoming' 
        WHERE deleted_at IS NULL 
        AND status != 'upcoming'
        AND start_datetime > ?
    ")->execute([$now]);
    
    // Update events to 'completed' if current time is after end
    $pdo->prepare("
        UPDATE events 
        SET status = 'completed' 
        WHERE deleted_at IS NULL 
        AND status != 'completed'
        AND end_datetime < ?
    ")->execute([$now]);
}

// Run auto-update on page load
autoUpdateEventStatuses($pdo);

// ========== EVENT MANAGEMENT ==========

// Handle Add Event
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $end_date = $_POST['end_date'] ?? $start_date;
    $venue = trim($_POST['venue']);
    $max_participants = (int)($_POST['max_participants'] ?? 100);
    $organizer_name = trim($_POST['organizer_name'] ?? '');
    
    // Combine date and time for datetime comparison
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime = $end_date . ' ' . $end_time;
    $now = date('Y-m-d H:i:s');
    
    // Determine status based on current time vs event time range
    if($now < $start_datetime) {
        $status = 'upcoming';
    } elseif($now >= $start_datetime && $now <= $end_datetime) {
        $status = 'ongoing';
    } else {
        $status = 'completed';
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO events (
            title, description, start_date, start_time, end_time, end_date, 
            start_datetime, end_datetime, venue, max_participants, organizer_name, status, deleted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
    ");
    
    if($stmt->execute([$title, $description, $start_date, $start_time, $end_time, $end_date, 
        $start_datetime, $end_datetime, $venue, $max_participants, $organizer_name, $status])) {
        $_SESSION['success_message'] = "Event added successfully! Status: " . ucfirst($status);
    } else {
        $_SESSION['error_message'] = "Failed to add event.";
    }
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Edit Event
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_event'])) {
    $event_id = (int)$_POST['event_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $end_date = $_POST['end_date'] ?? $start_date;
    $venue = trim($_POST['venue']);
    $max_participants = (int)($_POST['max_participants'] ?? 100);
    $organizer_name = trim($_POST['organizer_name'] ?? '');
    
    // Combine date and time for datetime comparison
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime = $end_date . ' ' . $end_time;
    $now = date('Y-m-d H:i:s');
    
    // Determine status based on current time vs event time range
    if($now < $start_datetime) {
        $status = 'upcoming';
    } elseif($now >= $start_datetime && $now <= $end_datetime) {
        $status = 'ongoing';
    } else {
        $status = 'completed';
    }
    
    $stmt = $pdo->prepare("
        UPDATE events SET 
            title = ?, description = ?, 
            start_date = ?, start_time = ?, end_time = ?, end_date = ?,
            start_datetime = ?, end_datetime = ?,
            venue = ?, max_participants = ?, organizer_name = ?, status = ?
        WHERE id = ?
    ");
    
    if($stmt->execute([$title, $description, $start_date, $start_time, $end_time, $end_date,
        $start_datetime, $end_datetime, $venue, $max_participants, $organizer_name, $status, $event_id])) {
        $_SESSION['success_message'] = "Event updated successfully! Status: " . ucfirst($status);
    } else {
        $_SESSION['error_message'] = "Failed to update event.";
    }
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Confirm Booking
if(isset($_GET['action']) && $_GET['action'] == 'confirm_booking' && isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];
    $pdo->prepare("UPDATE event_bookings SET status = 'confirmed' WHERE id = ?")->execute([$booking_id]);
    $_SESSION['success_message'] = "Booking confirmed! You can now edit it.";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Add Booking as New Event
if(isset($_POST['add_booking_as_event']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM event_bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if($booking && $booking['status'] == 'confirmed') {
        $title = $booking['event_name'];
        $description = "Event organized by " . $booking['organizer_name'];
        if(!empty($booking['expected_attendees'])) {
            $description .= ". Expected attendees: " . $booking['expected_attendees'];
        }
        
        $start_date = $booking['event_date'];
        $start_time = $booking['start_time'] ?? '10:00:00';
        $end_time = $booking['end_time'] ?? '17:00:00';
        $start_datetime = $start_date . ' ' . $start_time;
        $end_datetime = $start_date . ' ' . $end_time;
        $venue = "Greenpark, Makueni";
        $max_participants = 100;
        $organizer_name = $booking['organizer_name'];
        
        $now = date('Y-m-d H:i:s');
        
        if($now < $start_datetime) {
            $status = 'upcoming';
        } elseif($now >= $start_datetime && $now <= $end_datetime) {
            $status = 'ongoing';
        } else {
            $status = 'completed';
        }
        
        $stmt2 = $pdo->prepare("
            INSERT INTO events (title, description, start_date, start_time, end_time, start_datetime, end_datetime, venue, max_participants, organizer_name, status, deleted_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
        ");
        
        if($stmt2->execute([$title, $description, $start_date, $start_time, $end_time, $start_datetime, $end_datetime, $venue, $max_participants, $organizer_name, $status])) {
            $stmt3 = $pdo->prepare("UPDATE event_bookings SET status = 'completed' WHERE id = ?");
            $stmt3->execute([$booking_id]);
            $_SESSION['success_message'] = "Event has been added to the public events list! Status: " . ucfirst($status);
        } else {
            $_SESSION['error_message'] = "Failed to add event from booking.";
        }
    } else {
        $_SESSION['error_message'] = "Booking not found or not confirmed yet.";
    }
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Edit Booking
if(isset($_POST['update_booking_details']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    $check = $pdo->prepare("SELECT status FROM event_bookings WHERE id = ?");
    $check->execute([$booking_id]);
    $booking_status = $check->fetchColumn();
    
    if($booking_status == 'confirmed') {
        $event_name = trim($_POST['event_name']);
        $event_date = $_POST['event_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $organizer_name = trim($_POST['organizer_name']);
        $organizer_type = $_POST['organizer_type'];
        $phone = trim($_POST['phone']);
        $alt_phone = trim($_POST['alt_phone']);
        $email = trim($_POST['email']);
        $number_of_people = (int)$_POST['number_of_people'];
        $expected_attendees = trim($_POST['expected_attendees']);
        $duration_hours = (int)$_POST['duration_hours'];
        $special_requests = trim($_POST['special_requests']);
        
        $stmt = $pdo->prepare("
            UPDATE event_bookings SET 
                event_name = ?, event_date = ?, start_time = ?, end_time = ?,
                organizer_name = ?, organizer_type = ?, 
                phone = ?, alt_phone = ?, email = ?,
                number_of_people = ?, expected_attendees = ?,
                duration_hours = ?, special_requests = ?
            WHERE id = ?
        ");
        
        if($stmt->execute([$event_name, $event_date, $start_time, $end_time, $organizer_name, $organizer_type, $phone, $alt_phone, $email, $number_of_people, $expected_attendees, $duration_hours, $special_requests, $booking_id])) {
            $_SESSION['success_message'] = "Booking details updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update booking.";
        }
    } else {
        $_SESSION['error_message'] = "Booking must be confirmed before editing.";
    }
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Delete Event
if(isset($_GET['action']) && $_GET['action'] == 'delete_event' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE events SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Event moved to trash!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Restore Event
if(isset($_GET['action']) && $_GET['action'] == 'restore_event' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE events SET deleted_at = NULL WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Event restored!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Permanent Delete Event
if(isset($_GET['action']) && $_GET['action'] == 'permanent_delete_event' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Event permanently deleted!";
    header("Location: dashboard.php?view=events");
    exit();
}

// Handle Delete Booking
if(isset($_GET['action']) && $_GET['action'] == 'delete_booking' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM event_bookings WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Booking deleted successfully!";
    header("Location: dashboard.php?view=events");
    exit();
}

// ========== CLASS MANAGEMENT ==========

// Handle Add Class
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $duration_weeks = (int)$_POST['duration_weeks'];
    $price = (float)$_POST['price'];
    
    $stmt = $pdo->prepare("
        INSERT INTO computer_classes (class_name, description, start_date, duration_weeks, price, status) 
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    
    if($stmt->execute([$class_name, $description, $start_date, $duration_weeks, $price])) {
        $_SESSION['success_message'] = "Class added successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to add class.";
    }
    header("Location: dashboard.php?view=classes");
    exit();
}

// Handle Edit Class
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_class'])) {
    $class_id = (int)$_POST['class_id'];
    $class_name = trim($_POST['class_name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $duration_weeks = (int)$_POST['duration_weeks'];
    $price = (float)$_POST['price'];
    
    $stmt = $pdo->prepare("
        UPDATE computer_classes 
        SET class_name = ?, description = ?, start_date = ?, duration_weeks = ?, price = ?
        WHERE id = ?
    ");
    
    if($stmt->execute([$class_name, $description, $start_date, $duration_weeks, $price, $class_id])) {
        $_SESSION['success_message'] = "Class updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update class.";
    }
    header("Location: dashboard.php?view=classes");
    exit();
}

// Handle Delete Class
if(isset($_GET['action']) && $_GET['action'] == 'delete_class' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM computer_classes WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "Class deleted successfully!";
    header("Location: dashboard.php?view=classes");
    exit();
}

// Handle Issue Certificate (AJAX)
if(isset($_POST['action']) && $_POST['action'] == 'issue_certificate') {
    header('Content-Type: application/json');
    
    $registration_id = (int)$_POST['registration_id'];
    $completion_date = $_POST['completion_date'];
    
    // Get student and class details
    $stmt = $pdo->prepare("
        SELECT cr.*, c.class_name, c.price 
        FROM class_registrations cr
        JOIN computer_classes c ON cr.class_id = c.id
        WHERE cr.id = ?
    ");
    $stmt->execute([$registration_id]);
    $student = $stmt->fetch();
    
    if(!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }
    
    // Generate unique certificate number
    $certificate_number = 'MCIH-' . date('Y') . '-' . str_pad($registration_id, 5, '0', STR_PAD_LEFT);
    
    try {
        // Update student record
        $pdo->prepare("
            UPDATE class_registrations 
            SET certificate_issued = 1, 
                certificate_number = ?, 
                completion_date = ?,
                graduation_status = 'graduated'
            WHERE id = ?
        ")->execute([$certificate_number, $completion_date, $registration_id]);
        
        // Insert into certificates table
        $pdo->prepare("
            INSERT INTO certificates (registration_id, student_name, course_name, certificate_number, issue_date, completion_date)
            VALUES (?, ?, ?, ?, CURDATE(), ?)
        ")->execute([
            $registration_id,
            $student['full_name'],
            $student['class_name'],
            $certificate_number,
            $completion_date
        ]);
        
        echo json_encode([
            'success' => true, 
            'certificate_number' => $certificate_number,
            'message' => 'Certificate issued successfully'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// ========== AJIRY MANAGEMENT ==========

if(isset($_POST['update_ajiry_status']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE ajiry_bookings SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $booking_id]);
    
    $_SESSION['success_message'] = "Booking updated to " . ucfirst($new_status);
    header("Location: dashboard.php?view=ajiry");
    exit();
}

if(isset($_GET['action']) && $_GET['action'] == 'delete_ajiry_booking' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM ajiry_bookings WHERE id = ?")->execute([$id]);
    $_SESSION['success_message'] = "AJIRY booking deleted!";
    header("Location: dashboard.php?view=ajiry");
    exit();
}

// ========== GET STATISTICS ==========

// Event Statistics
$total_events = $pdo->query("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL")->fetchColumn();
$upcoming_events = $pdo->query("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL AND status = 'upcoming' AND start_datetime > NOW()")->fetchColumn();
$ongoing_events = $pdo->query("SELECT COUNT(*) FROM events WHERE deleted_at IS NULL AND status = 'ongoing' AND start_datetime <= NOW() AND end_datetime >= NOW()")->fetchColumn();

// Event Bookings Statistics
$total_event_bookings = $pdo->query("SELECT COUNT(*) FROM event_bookings")->fetchColumn();
$pending_event_bookings = $pdo->query("SELECT COUNT(*) FROM event_bookings WHERE status = 'pending'")->fetchColumn();
$confirmed_event_bookings = $pdo->query("SELECT COUNT(*) FROM event_bookings WHERE status = 'confirmed'")->fetchColumn();

// AJIRY Statistics
$total_ajiry_bookings = $pdo->query("SELECT COUNT(*) FROM ajiry_bookings")->fetchColumn();
$pending_ajiry = $pdo->query("SELECT COUNT(*) FROM ajiry_bookings WHERE status = 'pending'")->fetchColumn();
$confirmed_ajiry = $pdo->query("SELECT COUNT(*) FROM ajiry_bookings WHERE status = 'confirmed'")->fetchColumn();

// Class Statistics
$total_class_students = $pdo->query("SELECT COUNT(*) FROM class_registrations")->fetchColumn();
$total_graduates = $pdo->query("SELECT COUNT(*) FROM class_registrations WHERE certificate_issued = 1")->fetchColumn();

// Other Statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_feedback = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$pending_feedback = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status='pending'")->fetchColumn();
$total_mentors = $pdo->query("SELECT COUNT(*) FROM mentors")->fetchColumn();

// ========== FETCH ALL DATA ==========

// Events
$all_events = $pdo->query("SELECT * FROM events WHERE deleted_at IS NULL ORDER BY start_datetime ASC")->fetchAll();
$trashed_events = $pdo->query("SELECT * FROM events WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC")->fetchAll();

// Event Bookings
$event_bookings = $pdo->query("SELECT * FROM event_bookings ORDER BY created_at DESC")->fetchAll();

// AJIRY Bookings
$ajiry_bookings = $pdo->query("SELECT * FROM ajiry_bookings ORDER BY created_at DESC")->fetchAll();

// Class Registrations with full details
$class_registrations = $pdo->query("
    SELECT r.*, c.class_name, c.price as class_price 
    FROM class_registrations r 
    JOIN computer_classes c ON r.class_id = c.id 
    ORDER BY r.created_at DESC
")->fetchAll();

// All classes for dropdown
$all_classes = $pdo->query("SELECT * FROM computer_classes ORDER BY class_name")->fetchAll();

// Other data
$all_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$all_feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC")->fetchAll();
$mentors_list = $pdo->query("SELECT m.*, u.full_name, u.email FROM mentors m JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC")->fetchAll();

$total_paid = 0;
$total_balance = 0;
foreach($class_registrations as $reg) {
    $total_paid += $reg['payment_amount'];
    $total_balance += (1000 - $reg['payment_amount']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Makueni County Innovation Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-brand { font-weight: bold; display: flex; align-items: center; }
        .bg-success-custom { background: linear-gradient(135deg, #0a3d2a, #1a5c3a); }
        .county-logo-nav {
            height: 45px; width: auto; margin-right: 12px; object-fit: contain;
            background: white; border-radius: 50%; padding: 5px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .dashboard-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
        .card-header-custom {
            padding: 25px;
            background: linear-gradient(135deg, #1a5c3a, #0a3d2a);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-icon { width: 65px; height: 65px; background: rgba(255,255,255,0.2); border-radius: 15px; display: flex; align-items: center; justify-content: center; }
        .card-icon i { font-size: 32px; }
        .card-stats { font-size: 36px; font-weight: 800; }
        .card-label { font-size: 14px; opacity: 0.9; }
        
        .detail-view {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .back-btn {
            background: #1a5c3a;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            margin-bottom: 20px;
        }
        .back-btn:hover { background: #0a3d2a; transform: translateX(-5px); }
        .section-title {
            font-size: 24px;
            font-weight: bold;
            color: #1a5c3a;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #FFD700;
            display: inline-block;
        }
        .summary-stats { background: #e8f5e9; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .table-custom { width: 100%; font-size: 14px; border-collapse: collapse; }
        .table-custom th { background: #f8f9fa; padding: 12px; font-weight: 600; text-align: left; border-bottom: 2px solid #dee2e6; }
        .table-custom td { padding: 10px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .btn-action { padding: 4px 10px; font-size: 12px; border-radius: 15px; margin: 2px; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-pending { background: #ffc107; color: #856404; }
        .status-confirmed { background: #28a745; color: white; }
        .status-completed { background: #6c757d; color: white; }
        .status-upcoming { background: #17a2b8; color: white; }
        .status-ongoing { background: #28a745; color: white; }
        .status-enrolled { background: #ffc107; color: #856404; }
        .status-graduated { background: #28a745; color: white; }
        
        .modal-content { border-radius: 20px; overflow: hidden; }
        .modal-header { background: linear-gradient(135deg, #0a3d2a, #1a5c3a); color: white; border: none; padding: 20px; }
        .form-label { font-weight: 600; color: #1a5c3a; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #ddd; padding: 10px 15px; }
        .trash-item { background: #fff3cd; }
        .nav-tabs .nav-link { color: #1a5c3a; font-weight: 500; }
        .nav-tabs .nav-link.active { background: #1a5c3a; color: white; border-color: #1a5c3a; }
        
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .card-stats { font-size: 28px; }
            .table-custom { font-size: 12px; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success-custom">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/images/logo/makueni-logo.png" alt="Makueni County Logo" class="county-logo-nav" onerror="this.style.display='none'">
                <div><div>MAKUENI COUNTY</div><small>INNOVATION HUB - ADMIN</small></div>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="bg-success text-white p-4 rounded-4">
                    <h2 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>!</h2>
                    <p class="mb-0">Manage Makueni County Innovation Hub - Click on any card to view details</p>
                </div>
            </div>
        </div>

        <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if($current_view == 'dashboard'): ?>
        <div class="dashboard-grid">
            <!-- Card 1: Events -->
            <div class="dashboard-card" onclick="window.location.href='?view=events'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="text-end">
                        <div class="card-stats"><?php echo $total_events; ?></div>
                        <div class="card-label">Total Events</div>
                        <small><?php echo $total_event_bookings; ?> Bookings | <?php echo $pending_event_bookings; ?> Pending</small>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: AJIRY Bookings -->
            <div class="dashboard-card" onclick="window.location.href='?view=ajiry'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-microphone-alt"></i></div>
                    <div class="text-end">
                        <div class="card-stats"><?php echo $total_ajiry_bookings; ?></div>
                        <div class="card-label">AJIRY Bookings</div>
                        <?php if($pending_ajiry > 0): ?>
                            <small class="text-warning"><?php echo $pending_ajiry; ?> pending</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Card 3: Students Enrolled (Computer Classes) -->
            <div class="dashboard-card" onclick="window.location.href='?view=classes'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-laptop-code"></i></div>
                    <div class="text-end">
                        <div class="card-stats"><?php echo $total_class_students; ?></div>
                        <div class="card-label">Students Enrolled</div>
                        <small><?php echo $total_graduates; ?> Graduates</small>
                    </div>
                </div>
            </div>
            
            <!-- Card 4: Users -->
            <div class="dashboard-card" onclick="window.location.href='?view=users'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="text-end"><div class="card-stats"><?php echo $total_users; ?></div><div class="card-label">Total Users</div></div>
                </div>
            </div>
            
            <!-- Card 5: Feedback -->
            <div class="dashboard-card" onclick="window.location.href='?view=feedback'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-comment"></i></div>
                    <div class="text-end"><div class="card-stats"><?php echo $total_feedback; ?></div><div class="card-label">Feedback (<?php echo $pending_feedback; ?> pending)</div></div>
                </div>
            </div>
            
            <!-- Card 6: Mentors -->
            <div class="dashboard-card" onclick="window.location.href='?view=mentors'">
                <div class="card-header-custom">
                    <div class="card-icon"><i class="fas fa-user-tie"></i></div>
                    <div class="text-end"><div class="card-stats"><?php echo $total_mentors; ?></div><div class="card-label">Mentors</div></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==================== COMBINED EVENTS & BOOKINGS VIEW ==================== -->
        <?php if($current_view == 'events'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</button>
            
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="section-title"><i class="fas fa-calendar-alt me-2"></i>Events & Bookings Management</h3>
                <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="fas fa-plus me-2"></i> Add New Event
                </button>
            </div>
            
            <div class="summary-stats">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h5>Total Events</h5>
                        <h3 class="text-primary"><?php echo $total_events; ?></h3>
                    </div>
                    <div class="col-md-4">
                        <h5>Upcoming</h5>
                        <h3 class="text-info"><?php echo $upcoming_events; ?></h3>
                    </div>
                    <div class="col-md-4">
                        <h5>Ongoing</h5>
                        <h3 class="text-success"><?php echo $ongoing_events; ?></h3>
                    </div>
                </div>
            </div>
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#eventsList">📅 Events List</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bookingsList">📋 Member Bookings (<?php echo $pending_event_bookings; ?> pending)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#trashedEvents">🗑️ Trash</button></li>
            </ul>
            
            <div class="tab-content">
                <!-- EVENTS LIST TAB -->
                <div class="tab-pane fade show active" id="eventsList">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Title</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Venue</th>
                                    <th>Organizer</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($all_events)): ?>
                                    <tr><td colspan="8" class="text-center py-4">No events found. Click "Add New Event" to create one.</span></td>
                                <?php endif; ?>
                                <?php foreach($all_events as $event): ?>
                                <table>
                                    <td><?php echo $event['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($event['title']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars(substr($event['description'] ?? '', 0, 40)); ?></small></td>
                                    <td><?php echo date('M d, Y', strtotime($event['start_date'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($event['start_time'])) . ' - ' . date('g:i A', strtotime($event['end_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['venue'] ?? 'Greenpark'); ?></td>
                                    <td><?php echo htmlspecialchars($event['organizer_name'] ?? 'Greenpark'); ?></td>
                                    <td><span class="status-badge status-<?php echo $event['status'] ?? 'upcoming'; ?>"><?php echo ucfirst($event['status'] ?? 'Upcoming'); ?></span></td>
                                    <td class="action-buttons" style="white-space: nowrap;">
                                        <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal" data-bs-target="#editEventModal<?php echo $event['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?view=events&action=delete_event&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Move to trash?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                     </span></td>
                                </tr>
                                
                                <!-- Edit Event Modal -->
                                <div class="modal fade" id="editEventModal<?php echo $event['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header bg-warning text-dark">
                                                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Event</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Event Title *</label>
                                                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Description</label>
                                                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Event Date *</label>
                                                            <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d', strtotime($event['start_date'])); ?>" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Start Time *</label>
                                                            <input type="time" name="start_time" class="form-control" value="<?php echo date('H:i', strtotime($event['start_time'])); ?>" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">End Time *</label>
                                                            <input type="time" name="end_time" class="form-control" value="<?php echo date('H:i', strtotime($event['end_time'])); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Venue</label>
                                                            <input type="text" name="venue" class="form-control" value="<?php echo htmlspecialchars($event['venue'] ?? 'Greenpark, Makueni'); ?>">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Max Participants</label>
                                                            <input type="number" name="max_participants" class="form-control" value="<?php echo $event['max_participants'] ?? 100; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Organizer Name</label>
                                                        <input type="text" name="organizer_name" class="form-control" value="<?php echo htmlspecialchars($event['organizer_name'] ?? ''); ?>">
                                                    </div>
                                                    <div class="alert alert-info mt-2">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        Event status will be automatically updated based on current date and time.
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="edit_event" class="btn btn-warning">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- MEMBER BOOKINGS TAB -->
                <div class="tab-pane fade" id="bookingsList">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Name</th>
                                    <th>Organizer</th>
                                    <th>Phone</th>
                                    <th>Event Date</th>
                                    <th>Time</th>
                                    <th>People</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($event_bookings)): ?>
                                    <tr><td colspan="9" class="text-center py-4">No bookings found</span></td>
                                <?php endif; ?>
                                
                                <?php foreach($event_bookings as $booking): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($booking['event_name'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['organizer_name'] ?? 'N/A'); ?><br><small><?php echo ucfirst($booking['organizer_type'] ?? ''); ?></small></td>
                                    <td><?php echo htmlspecialchars($booking['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['start_time'] ?? 'TBA') . ' - ' . htmlspecialchars($booking['end_time'] ?? 'TBA'); ?></td>
                                    <td class="text-center"><?php echo $booking['number_of_people'] ?? 0; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status'] ?? 'pending'; ?>">
                                            <?php echo ucfirst($booking['status'] ?? 'Pending'); ?>
                                        </span>
                                     </span></td>
                                    <td class="action-buttons" style="white-space: nowrap;">
                                        <?php if(($booking['status'] ?? 'pending') == 'pending'): ?>
                                            <a href="?view=events&action=confirm_booking&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-success btn-action" onclick="return confirm('Confirm this booking?');">
                                                <i class="fas fa-check-circle"></i> Confirm
                                            </a>
                                        <?php elseif(($booking['status'] ?? 'pending') == 'confirmed'): ?>
                                            <button class="btn btn-sm btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#editBookingModal<?php echo $booking['id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('Add this booking as a new event?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" name="add_booking_as_event" class="btn btn-sm btn-info btn-action">
                                                    <i class="fas fa-calendar-plus"></i> Add as Event
                                                </button>
                                            </form>
                                        <?php elseif(($booking['status'] ?? 'pending') == 'completed'): ?>
                                            <button class="btn btn-sm btn-secondary btn-action" disabled>Added</button>
                                        <?php endif; ?>
                                        <a href="?view=events&action=delete_booking&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Delete this booking?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                     </span></td>
                                </tr>
                                
                                <!-- Edit Booking Modal -->
                                <div class="modal fade" id="editBookingModal<?php echo $booking['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Booking #<?php echo $booking['id']; ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    
                                                    <div class="row">
                                                        <div class="col-md-8 mb-3">
                                                            <label class="form-label">Event Name *</label>
                                                            <input type="text" name="event_name" class="form-control" value="<?php echo htmlspecialchars($booking['event_name'] ?? ''); ?>" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Organizer Type</label>
                                                            <select name="organizer_type" class="form-select">
                                                                <option value="individual" <?php echo ($booking['organizer_type'] ?? '') == 'individual' ? 'selected' : ''; ?>>Individual</option>
                                                                <option value="group" <?php echo ($booking['organizer_type'] ?? '') == 'group' ? 'selected' : ''; ?>>Group</option>
                                                                <option value="organization" <?php echo ($booking['organizer_type'] ?? '') == 'organization' ? 'selected' : ''; ?>>Organization</option>
                                                                <option value="company" <?php echo ($booking['organizer_type'] ?? '') == 'company' ? 'selected' : ''; ?>>Company</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Event Date *</label>
                                                            <input type="date" name="event_date" class="form-control" value="<?php echo $booking['event_date'] ?? ''; ?>" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">Start Time *</label>
                                                            <input type="time" name="start_time" class="form-control" value="<?php echo $booking['start_time'] ?? '10:00'; ?>" required>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label class="form-label">End Time *</label>
                                                            <input type="time" name="end_time" class="form-control" value="<?php echo $booking['end_time'] ?? '17:00'; ?>" required>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Organizer Name *</label>
                                                        <input type="text" name="organizer_name" class="form-control" value="<?php echo htmlspecialchars($booking['organizer_name'] ?? ''); ?>" required>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Phone Number *</label>
                                                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($booking['phone'] ?? ''); ?>" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Alternative Phone</label>
                                                            <input type="tel" name="alt_phone" class="form-control" value="<?php echo htmlspecialchars($booking['alt_phone'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Email</label>
                                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($booking['email'] ?? ''); ?>">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Duration (Hours)</label>
                                                            <input type="number" name="duration_hours" class="form-control" value="<?php echo $booking['duration_hours'] ?? 2; ?>" min="1">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Number of People</label>
                                                            <input type="number" name="number_of_people" class="form-control" value="<?php echo $booking['number_of_people'] ?? 1; ?>" min="1">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Expected Attendees</label>
                                                            <input type="text" name="expected_attendees" class="form-control" value="<?php echo htmlspecialchars($booking['expected_attendees'] ?? ''); ?>" placeholder="e.g., 50-100 people">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Special Requests</label>
                                                        <textarea name="special_requests" class="form-control" rows="2"><?php echo htmlspecialchars($booking['special_requests'] ?? ''); ?></textarea>
                                                    </div>
                                                    
                                                    <div class="alert alert-info mt-2">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        After editing, click "Save Changes" then use the <strong>"Add as Event"</strong> button.
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="update_booking_details" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- TRASH TAB -->
                <div class="tab-pane fade" id="trashedEvents">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead><tr><th>ID</th><th>Event Title</th><th>Deleted On</th><th>Original Date</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($trashed_events as $event): ?>
                                <tr class="trash-item">
                                    <td><?php echo $event['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($event['deleted_at'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($event['start_date'])); ?></td>
                                    <td>
                                        <a href="?view=events&action=restore_event&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-success btn-action">Restore</a>
                                        <a href="?view=events&action=permanent_delete_event&id=<?php echo $event['id']; ?>" class="btn btn-sm btn-dark btn-action" onclick="return confirm('Permanently delete?')">Permanent Delete</a>
                                     </span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==================== COMPUTER CLASSES MANAGEMENT (ALL IN ONE CARD) ==================== -->
        <?php if($current_view == 'classes'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</button>
            
            <h3 class="section-title"><i class="fas fa-laptop-code me-2"></i>Computer Classes & Student Management</h3>
            
            <!-- Statistics Summary -->
            <div class="summary-stats">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h5>Total Classes</h5>
                        <h3 class="text-primary"><?php echo count($all_classes); ?></h3>
                    </div>
                    <div class="col-md-3">
                        <h5>Total Students</h5>
                        <h3 class="text-info"><?php echo $total_class_students; ?></h3>
                    </div>
                    <div class="col-md-3">
                        <h5>Total Graduates</h5>
                        <h3 class="text-success" id="totalGraduates"><?php echo $total_graduates; ?></h3>
                    </div>
                    <div class="col-md-3">
                        <h5>Total Revenue</h5>
                        <h3 class="text-warning">KES <?php echo number_format($total_paid, 2); ?></h3>
                    </div>
                </div>
            </div>
            
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#classesManage">📚 Manage Classes</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#studentsManage">👨‍🎓 Enrolled Students (<?php echo $total_class_students; ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#graduatesManage">🎓 Graduates (<?php echo $total_graduates; ?>)</button></li>
            </ul>
            
            <div class="tab-content">
                <!-- TAB 1: MANAGE CLASSES -->
                <div class="tab-pane fade show active" id="classesManage">
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
                            <i class="fas fa-plus me-2"></i> Add New Class
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Class Name</th>
                                    <th>Description</th>
                                    <th>Start Date</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Enrolled</th>
                                    <th>Graduated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $all_computer_classes = $pdo->query("SELECT * FROM computer_classes ORDER BY id DESC")->fetchAll();
                                foreach($all_computer_classes as $class):
                                    $enrolled_stmt = $pdo->prepare("SELECT COUNT(*) FROM class_registrations WHERE class_id = ?");
                                    $enrolled_stmt->execute([$class['id']]);
                                    $enrolled_count = $enrolled_stmt->fetchColumn();
                                    
                                    $grad_stmt = $pdo->prepare("SELECT COUNT(*) FROM class_registrations WHERE class_id = ? AND certificate_issued = 1");
                                    $grad_stmt->execute([$class['id']]);
                                    $graduated_count = $grad_stmt->fetchColumn();
                                ?>
                                <tr>
                                    <td><?php echo $class['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(substr($class['description'] ?? '', 0, 60)); ?>...</span></td>
                                    <td><?php echo date('M d, Y', strtotime($class['start_date'] ?? 'now')); ?></td>
                                    <td><?php echo $class['duration_weeks'] ?? '8'; ?> weeks</span></td>
                                    <td>KES <?php echo number_format($class['price'] ?? 1000, 2); ?></td>
                                    <td class="text-center"><?php echo $enrolled_count; ?></td>
                                    <td class="text-center"><?php echo $graduated_count; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning btn-action" onclick="editClass(<?php echo $class['id']; ?>, '<?php echo htmlspecialchars($class['class_name']); ?>', '<?php echo htmlspecialchars($class['description']); ?>', '<?php echo $class['start_date']; ?>', <?php echo $class['duration_weeks'] ?? 8; ?>, <?php echo $class['price'] ?? 1000; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?view=classes&action=delete_class&id=<?php echo $class['id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Delete this class? This will also remove all enrolled students.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                     </span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($all_computer_classes)): ?>
                                <tr><td colspan="9" class="text-center py-4">No classes found. Click "Add New Class" to create one.</span></td>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- TAB 2: ENROLLED STUDENTS -->
                <div class="tab-pane fade" id="studentsManage">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Amount Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($class_registrations as $reg): 
                                    $balance = 1000 - ($reg['payment_amount'] ?? 0);
                                ?>
                                <tr>
                                    <td><?php echo $reg['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($reg['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($reg['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['email'] ?? 'N/A'); ?></td>
                                    <td>KES <?php echo number_format($reg['payment_amount'] ?? 0, 2); ?></td>
                                    <td>KES <?php echo number_format($balance, 2); ?></span></td>
                                    <td>
                                        <?php if(($reg['certificate_issued'] ?? 0) == 1): ?>
                                            <span class="status-badge status-graduated">🎓 Graduated</span>
                                        <?php else: ?>
                                            <span class="status-badge status-enrolled">📚 Enrolled</span>
                                        <?php endif; ?>
                                     </span></td>
                                    <td>
                                        <?php if(($reg['certificate_issued'] ?? 0) != 1): ?>
                                            <button class="btn btn-sm btn-success btn-action" onclick="issueCertificate(<?php echo $reg['id']; ?>, '<?php echo htmlspecialchars($reg['full_name']); ?>', '<?php echo htmlspecialchars($reg['class_name']); ?>')">
                                                <i class="fas fa-certificate"></i> Issue Certificate
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary btn-action" disabled>
                                                <i class="fas fa-check"></i> Certified
                                            </button>
                                        <?php endif; ?>
                                     </span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($class_registrations)): ?>
                                <tr><td colspan="9" class="text-center py-4">No students enrolled yet.</span></td>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- TAB 3: GRADUATES -->
                <div class="tab-pane fade" id="graduatesManage">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Certificate #</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Completion Date</th>
                                    <th>Issue Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $graduates = $pdo->query("
                                    SELECT cr.*, c.certificate_number, c.issue_date, c.completion_date
                                    FROM class_registrations cr
                                    LEFT JOIN certificates c ON cr.id = c.registration_id
                                    WHERE cr.certificate_issued = 1
                                    ORDER BY c.issue_date DESC
                                ")->fetchAll();
                                ?>
                                <?php foreach($graduates as $grad): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grad['certificate_number'] ?? 'N/A'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($grad['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($grad['class_name']); ?></td>
                                    <td><?php echo isset($grad['completion_date']) ? date('M d, Y', strtotime($grad['completion_date'])) : date('M d, Y', strtotime($grad['created_at'])); ?></td>
                                    <td><?php echo isset($grad['issue_date']) ? date('M d, Y', strtotime($grad['issue_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <a href="download_certificate.php?id=<?php echo $grad['id']; ?>" class="btn btn-sm btn-primary btn-action" target="_blank">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                     </span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($graduates)): ?>
                                <tr><td colspan="6" class="text-center py-4">No graduates yet. Issue certificates to students.</span></td>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Class Modal -->
        <div class="modal fade" id="addClassModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Class</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Class Name *</label>
                                <input type="text" name="class_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Date *</label>
                                    <input type="date" name="start_date" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Duration (Weeks)</label>
                                    <input type="number" name="duration_weeks" class="form-control" value="8">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price (KES)</label>
                                <input type="number" name="price" class="form-control" value="1000">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_class" class="btn btn-success">Add Class</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Class Modal -->
        <div class="modal fade" id="editClassModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Class</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="class_id" id="edit_class_id">
                            <div class="mb-3">
                                <label class="form-label">Class Name *</label>
                                <input type="text" name="class_name" id="edit_class_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Date *</label>
                                    <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Duration (Weeks)</label>
                                    <input type="number" name="duration_weeks" id="edit_duration_weeks" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Price (KES)</label>
                                <input type="number" name="price" id="edit_price" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="edit_class" class="btn btn-warning">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Issue Certificate Modal -->
        <div class="modal fade" id="certificateModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-certificate me-2"></i>Issue Certificate</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Student Name</label>
                            <input type="text" id="certStudentName" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Course Name</label>
                            <input type="text" id="certCourseName" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Completion Date</label>
                            <input type="date" id="completionDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            A unique certificate number will be generated automatically.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="confirmIssueCertificate()">Issue Certificate</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ==================== AJIRY MANAGEMENT VIEW ==================== -->
        <?php if($current_view == 'ajiry'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back</button>
            <h3 class="section-title"><i class="fas fa-microphone-alt me-2"></i>AJIRY Studio Bookings</h3>
            <div class="summary-stats"><div class="row text-center"><div class="col-md-4"><h5>Total</h5><h3><?php echo $total_ajiry_bookings; ?></h3></div><div class="col-md-4"><h5>Pending</h5><h3><?php echo $pending_ajiry; ?></h3></div><div class="col-md-4"><h5>Confirmed</h5><h3><?php echo $confirmed_ajiry; ?></h3></div></div></div>
            <table class="table-custom">
                <thead><tr><th>ID</th><th>Booking Code</th><th>Customer</th><th>Services</th><th>Preferred Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($ajiry_bookings as $booking): ?>
                    <tr>
                        <td><?php echo $booking['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($booking['booking_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($booking['full_name']); ?><br><small><?php echo htmlspecialchars($booking['email']); ?></small></td>
                        <td><?php echo htmlspecialchars(substr($booking['services'], 0, 30)); ?>...</span></td>
                        <td><?php echo date('M d, Y', strtotime($booking['preferred_date'])); ?></td>
                        <td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateAjiryModal<?php echo $booking['id']; ?>">Update</button>
                            <a href="?view=ajiry&action=delete_ajiry_booking&id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                         </span></td>
                    </tr>
                    <div class="modal fade" id="updateAjiryModal<?php echo $booking['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header bg-primary text-white"><h5 class="modal-title">Update AJIRY Booking</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <div class="mb-3"><label>Status</label><select name="status" class="form-select"><option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option><option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option><option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option><option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option></select></div>
                                    </div>
                                    <div class="modal-footer"><button type="submit" name="update_ajiry_status" class="btn btn-primary">Update</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- OTHER VIEWS (Users, Feedback, Mentors) -->
        <?php if($current_view == 'users'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back</button>
            <h3 class="section-title"><i class="fas fa-users me-2"></i>Manage Users</h3>
            <table class="table-custom"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Actions</th></tr></thead><tbody><?php foreach($all_users as $user): ?><tr><td><?php echo htmlspecialchars($user['full_name']); ?></td><td><?php echo htmlspecialchars($user['email']); ?></td><td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td><td><span class="status-badge <?php echo $user['role'] == 'admin' ? 'status-confirmed' : 'status-pending'; ?>"><?php echo ucfirst($user['role']); ?></span></td><td><?php if($user['role'] == 'user'): ?><a href="?view=users&action=make_admin&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Make Admin</a><?php elseif($user['id'] != $_SESSION['user_id']): ?><a href="?view=users&action=remove_admin&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">Remove Admin</a><?php endif; ?><?php if($user['id'] != $_SESSION['user_id']): ?><a href="?view=users&action=delete_user&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete user?')">Delete</a><?php endif; ?></td></td><?php endforeach; ?></tbody></table>
        </div>
        <?php endif; ?>

        <?php if($current_view == 'feedback'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back</button>
            <h3 class="section-title"><i class="fas fa-comment me-2"></i>User Feedback</h3>
            <table class="table-custom"><thead><tr><th>Name</th><th>Message</th><th>Rating</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($all_feedback as $fb): ?><tr><td><?php echo htmlspecialchars($fb['full_name']); ?></td><td><?php echo htmlspecialchars(substr($fb['message'], 0, 60)); ?>...</td><td><?php for($i=1;$i<=5;$i++) echo $i<=$fb['rating'] ? '★' : '☆'; ?></td><td><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></td><td><span class="status-badge <?php echo $fb['status'] == 'approved' ? 'status-confirmed' : 'status-pending'; ?>"><?php echo ucfirst($fb['status']); ?></span></td><td><?php if($fb['status'] == 'pending'): ?><a href="?view=feedback&action=approve_feedback&id=<?php echo $fb['id']; ?>" class="btn btn-sm btn-success">Approve</a><?php endif; ?><a href="?view=feedback&action=delete_feedback&id=<?php echo $fb['id']; ?>" class="btn btn-sm btn-danger">Delete</a></td></tr><?php endforeach; ?></tbody></table>
        </div>
        <?php endif; ?>

        <?php if($current_view == 'mentors'): ?>
        <div class="detail-view">
            <button class="back-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-arrow-left me-2"></i> Back</button>
            <h3 class="section-title"><i class="fas fa-user-tie me-2"></i>Mentors</h3>
            <table class="table-custom"><thead><tr><th>Name</th><th>Email</th><th>Expertise</th><th>Experience</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($mentors_list as $mentor): ?><tr><td><?php echo htmlspecialchars($mentor['full_name']); ?></td><td><?php echo htmlspecialchars($mentor['email']); ?></td><td><?php echo htmlspecialchars(substr($mentor['expertise'] ?? '', 0, 40)); ?></td><td><?php echo $mentor['years_experience'] ?? 0; ?> yrs</span></td><td><span class="status-badge <?php echo ($mentor['is_verified'] ?? 0) ? 'status-confirmed' : 'status-pending'; ?>"><?php echo ($mentor['is_verified'] ?? 0) ? 'Verified' : 'Pending'; ?></span></td><td><a href="edit-mentor.php?id=<?php echo $mentor['id']; ?>" class="btn btn-sm btn-primary">Edit</a></td></tr><?php endforeach; ?></tbody></table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Add New Event</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Event Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Event Date *</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Start Time *</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">End Time *</label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Venue</label>
                                <input type="text" name="venue" class="form-control" value="Greenpark, Makueni">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Participants</label>
                                <input type="number" name="max_participants" class="form-control" value="100">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Organizer Name</label>
                            <input type="text" name="organizer_name" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_event" class="btn btn-success">Add Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentRegistrationId = null;
        
        function editClass(id, name, description, startDate, durationWeeks, price) {
            document.getElementById('edit_class_id').value = id;
            document.getElementById('edit_class_name').value = name;
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit_start_date').value = startDate;
            document.getElementById('edit_duration_weeks').value = durationWeeks;
            document.getElementById('edit_price').value = price;
            new bootstrap.Modal(document.getElementById('editClassModal')).show();
        }
        
        function issueCertificate(id, name, course) {
            currentRegistrationId = id;
            document.getElementById('certStudentName').value = name;
            document.getElementById('certCourseName').value = course;
            new bootstrap.Modal(document.getElementById('certificateModal')).show();
        }
        
        function confirmIssueCertificate() {
            const completionDate = document.getElementById('completionDate').value;
            
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=issue_certificate&registration_id=' + currentRegistrationId + '&completion_date=' + completionDate
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('✅ Certificate issued successfully!\n\nCertificate Number: ' + data.certificate_number);
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error issuing certificate: ' + error);
            });
        }
    </script>
</body>
</html>