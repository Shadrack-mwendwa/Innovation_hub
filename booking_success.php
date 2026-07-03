<?php
session_start();

if(!isset($_SESSION['last_booking'])) {
    header("Location: index.php");
    exit();
}

$booking = $_SESSION['last_booking'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Makueni County Innovation Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0a3d2a, #1a5c3a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .success-card {
            background: white;
            border-radius: 30px;
            padding: 40px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            animation: fadeInUp 0.6s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .success-icon i { font-size: 45px; color: white; }
        .event-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .event-details p { margin-bottom: 12px; font-size: 14px; }
        .event-details strong { color: #1a5c3a; width: 120px; display: inline-block; }
        .booking-code {
            background: #1a5c3a;
            color: white;
            padding: 15px;
            border-radius: 12px;
            font-family: monospace;
            font-size: 18px;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .booking-code:hover { background: #0a3d2a; transform: scale(1.02); }
        .btn-exit {
            background: #1a5c3a;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
        }
        .btn-exit:hover { background: #0a3d2a; transform: translateY(-2px); }
        .check-email {
            background: #e8f5e9;
            border-radius: 10px;
            padding: 12px;
            margin-top: 20px;
            font-size: 13px;
        }
        h2 { color: #1a5c3a; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h2 class="fw-bold">Booking Confirmed!</h2>
        <p class="text-muted">You have successfully secured your event booking</p>
        
        <div class="event-details">
            <p><strong>📅 Event:</strong> <?php echo htmlspecialchars($booking['event_name'] ?? 'N/A'); ?></p>
            <p><strong>📆 Date:</strong> <?php echo isset($booking['event_date']) ? date('F d, Y', strtotime($booking['event_date'])) : 'N/A'; ?></p>
            <p><strong>⏰ Time:</strong> <?php echo date('g:i A', strtotime($booking['start_time'] ?? '10:00')) . ' - ' . date('g:i A', strtotime($booking['end_time'] ?? '17:00')); ?></p>
            <p><strong>👤 Organizer:</strong> <?php echo htmlspecialchars($booking['organizer_name'] ?? 'N/A'); ?></p>
            <p><strong>📍 Venue:</strong> Greenpark, Makueni County</p>
            <p><strong>👥 Expected Guests:</strong> <?php echo $booking['number_of_people'] ?? 0; ?> people</p>
        </div>
        
        <div class="booking-code" onclick="copyBookingCode('<?php echo $booking['booking_code'] ?? 'N/A'; ?>')">
            <i class="fas fa-ticket-alt me-2"></i> <?php echo $booking['booking_code'] ?? 'N/A'; ?>
            <span style="font-size: 12px; margin-left: 10px;"><i class="fas fa-copy"></i> Click to copy</span>
        </div>
        
        <div class="check-email">
            <i class="fas fa-envelope me-2"></i>
            <strong>Check your email!</strong><br>
            We've sent a confirmation to <strong><?php echo htmlspecialchars($booking['email'] ?? 'your email'); ?></strong>
        </div>
        
        <button class="btn-exit" onclick="window.location.href='index.php'">
            <i class="fas fa-home me-2"></i> Exit to Homepage
        </button>
    </div>
    
    <script>
        function copyBookingCode(code) {
            const tempInput = document.createElement('input');
            tempInput.value = code;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            alert('Booking code copied to clipboard!');
        }
    </script>
</body>
</html>