<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $organizer_name = trim($_POST['organizer_name']);
    $phone_primary = trim($_POST['phone_primary']);
    $phone_secondary = trim($_POST['phone_secondary']);
    $email = trim($_POST['email']);
    $number_of_people = (int)$_POST['number_of_people'];
    $event_type = $_POST['event_type'];
    $special_requirements = trim($_POST['special_requirements']);
    
    $errors = [];
    
    // Validation
    if(empty($event_name)) $errors[] = "Event name is required";
    if(empty($event_date)) $errors[] = "Event date is required";
    if(empty($start_time)) $errors[] = "Start time is required";
    if(empty($end_time)) $errors[] = "End time is required";
    if(empty($organizer_name)) $errors[] = "Organizer name is required";
    if(empty($phone_primary)) $errors[] = "Primary phone number is required";
    if(empty($email)) $errors[] = "Email address is required";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address";
    if($number_of_people < 1) $errors[] = "Number of people must be at least 1";
    
    // Generate unique booking code
    $booking_code = 'BK-' . strtoupper(uniqid());
    
    if(empty($errors)) {
        try {
            // Create table if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `event_bookings` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `event_name` varchar(200) NOT NULL,
                    `event_date` date NOT NULL,
                    `start_time` time DEFAULT NULL,
                    `end_time` time DEFAULT NULL,
                    `organizer_name` varchar(100) NOT NULL,
                    `organizer_type` enum('individual','group','organization','company') DEFAULT 'individual',
                    `phone` varchar(20) NOT NULL,
                    `alt_phone` varchar(20) DEFAULT NULL,
                    `email` varchar(100) DEFAULT NULL,
                    `number_of_people` int(11) DEFAULT 1,
                    `expected_attendees` varchar(50) DEFAULT NULL,
                    `event_type` varchar(50) DEFAULT 'other',
                    `duration_hours` int(11) DEFAULT 2,
                    `special_requests` text,
                    `booking_code` varchar(50) DEFAULT NULL,
                    `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `booking_code` (`booking_code`)
                )
            ");
            
            $stmt = $pdo->prepare("
                INSERT INTO event_bookings (
                    event_name, event_date, start_time, end_time, organizer_name,
                    phone, alt_phone, email, number_of_people, event_type,
                    special_requests, booking_code, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $event_name, $event_date, $start_time, $end_time, $organizer_name,
                $phone_primary, $phone_secondary, $email, $number_of_people, $event_type,
                $special_requirements, $booking_code
            ]);
            
            // Send email confirmation
            $to = $email;
            $subject = "Booking Confirmation: " . $event_name . " at Greenpark";
            
            $email_message = "Dear " . $organizer_name . ",\n\n";
            $email_message .= "✅ Your event booking has been successfully received!\n\n";
            $email_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $email_message .= "EVENT DETAILS\n";
            $email_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $email_message .= "Event: " . $event_name . "\n";
            $email_message .= "Date: " . date('F d, Y', strtotime($event_date)) . "\n";
            $email_message .= "Time: " . date('g:i A', strtotime($start_time)) . " - " . date('g:i A', strtotime($end_time)) . "\n";
            $email_message .= "Venue: Greenpark, Makueni County\n";
            $email_message .= "Expected Guests: " . $number_of_people . " people\n";
            $email_message .= "Booking Code: " . $booking_code . "\n\n";
            
            $email_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $email_message .= "WHAT HAPPENS NEXT?\n";
            $email_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            $email_message .= "1. We will review your booking request\n";
            $email_message .= "2. You will receive a confirmation call within 24 hours\n";
            $email_message .= "3. Once confirmed, your event will be added to our calendar\n\n";
            
            $email_message .= "Best regards,\nGreenpark Events Team\nMakueni County Innovation Hub\n📞 0795717885 | 📧 info@makueni.go.ke";
            
            $headers = "From: Greenpark Events <events@makueniinnovationhub.co.ke>\r\n";
            $headers .= "Reply-To: info@makueni.go.ke\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            mail($to, $subject, $email_message, $headers);
            
            // Store in session for success page
            $_SESSION['last_booking'] = [
                'event_name' => $event_name,
                'event_date' => $event_date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'organizer_name' => $organizer_name,
                'email' => $email,
                'number_of_people' => $number_of_people,
                'booking_code' => $booking_code
            ];
            
            $_SESSION['success_message'] = "Booking submitted successfully! Check your email.";
            header("Location: booking_success.php");
            exit();
            
        } catch(PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    if(!empty($errors)) {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_data'] = $_POST;
        header("Location: index.php#book-venue");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>