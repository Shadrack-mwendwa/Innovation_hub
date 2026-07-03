<?php
session_start();
require_once 'config/database.php';

// Check if registration ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid registration ID.");
}

$registration_id = $_GET['id'];
$download = isset($_GET['download']) ? true : false;

// Fetch registration details with class info
try {
    $stmt = $pdo->prepare("
        SELECT cr.*, cc.class_name, cc.duration_weeks, cc.fee_amount, cc.start_date, cc.schedule
        FROM class_registrations cr
        JOIN computer_classes cc ON cr.class_id = cc.id
        WHERE cr.id = ?
    ");
    $stmt->execute([$registration_id]);
    $registration = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        die("Registration not found.");
    }
} catch(PDOException $e) {
    die("Error fetching registration: " . $e->getMessage());
}

// Calculate payment info
$total_fee = 1000; // Total course fee
$amount_paid = $registration['payment_amount'] ?? 0;
$remaining_balance = $total_fee - $amount_paid;
if ($remaining_balance < 0) $remaining_balance = 0;

// Get schedule
$schedule = $registration['schedule'] ?? 'Monday - Friday: 8:00 AM - 5:00 PM';

// Get logo path
$logo_path = 'assets/images/logo/makueni-logo.png';
$logo_base64 = '';

if (file_exists($logo_path)) {
    $logo_data = file_get_contents($logo_path);
    $logo_base64 = 'data:image/png;base64,' . base64_encode($logo_data);
}

// Generate HTML content for the letter
$letter_html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admission Letter - ' . htmlspecialchars($registration['full_name'] ?? 'Student') . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; background: white; padding: 40px 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .letter-container { max-width: 900px; margin: 0 auto; background: white; border-radius: 4px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .letter { padding: 50px; background: white; }
        .header { text-align: center; border-bottom: 2px solid #1a5c3a; padding-bottom: 20px; margin-bottom: 30px; }
        .logo-container { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 15px; flex-wrap: wrap; }
        .county-logo { width: 80px; height: 80px; object-fit: contain; }
        .header-text { text-align: center; }
        .header h1 { color: #1a5c3a; font-size: 24px; margin-bottom: 5px; }
        .header h2 { font-size: 18px; color: #333; margin-bottom: 5px; }
        .header p { font-size: 12px; color: #666; margin: 3px 0; }
        .title { text-align: center; font-size: 20px; font-weight: bold; text-decoration: underline; margin: 30px 0; }
        .date { text-align: right; margin-bottom: 25px; }
        .greeting { margin-bottom: 15px; }
        .content { line-height: 1.6; margin-bottom: 30px; }
        .confirmation { background: #e8f5e9; padding: 10px; text-align: center; font-weight: bold; color: #2e7d32; margin: 15px 0; border-left: 4px solid #4caf50; display: inline-block; width: auto; }
        .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .details-table td { padding: 10px; border-bottom: 1px solid #ddd; }
        .details-table td:first-child { font-weight: bold; width: 35%; background: #f5f5f5; }
        .payment-summary { background: #fff3e0; padding: 15px; margin: 20px 0; border-left: 4px solid #ff9800; }
        .important-info { background: #e3f2fd; padding: 15px; margin: 20px 0; border-left: 4px solid #2196f3; }
        .important-info h4 { color: #1565c0; margin-bottom: 10px; }
        .important-info ul { margin-left: 20px; }
        .important-info li { margin: 5px 0; }
        .signature { margin-top: 40px; padding-top: 20px; }
        .footer { text-align: center; font-size: 11px; color: #999; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; }
        @media print { body { background: white; padding: 0; margin: 0; } .letter { padding: 30px; } }
    </style>
</head>
<body>
    <div class="letter-container">
        <div class="letter">
            <div class="header">
                <div class="logo-container">';

if ($logo_base64) {
    $letter_html .= '<img src="' . $logo_base64 . '" alt="Makueni County Logo" class="county-logo">';
} else {
    $letter_html .= '<div style="width: 80px; height: 80px; background: #1a5c3a; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; text-align: center;">COUNTY<br>LOGO</div>';
}

$letter_html .= '
                    <div class="header-text">
                        <h1>MAKUENI COUNTY INNOVATION HUB</h1>
                        <h2>Computer Training Center</h2>
                        <p>Wote Town, Makueni County</p>
                        <p>Tel: +254 795 717 885 | Email: info@makueni.go.ke</p>
                    </div>
                </div>
            </div>
            
            <div class="title">ADMISSION LETTER</div>
            
            <div class="date">Date: ' . date('F d, Y') . '</div>
            
            <div class="greeting"><strong>To: ' . htmlspecialchars($registration['full_name'] ?? 'Student') . '</strong></div>
            
            <div class="content">
                <p>Dear ' . htmlspecialchars($registration['full_name'] ?? 'Student') . ',</p>
                <p>We are pleased to inform you that your registration for the <strong>' . htmlspecialchars($registration['class_name'] ?? 'Course') . '</strong> has been <span class="confirmation">CONFIRMED</span>.</p>
            </div>
            
            <h3>Registration Details:</h3>
            <table class="details-table">
                <tr><td>Registration Number:</td><td><strong>REG-' . str_pad($registration_id, 6, '0', STR_PAD_LEFT) . '</strong></td></tr>
                <tr><td>Student Name:</td><td><strong>' . htmlspecialchars($registration['full_name'] ?? 'N/A') . '</strong></td></tr>
                <tr><td>Phone Number:</td><td><strong>' . htmlspecialchars($registration['phone'] ?? 'N/A') . '</strong></td></tr>
                <tr><td>Email Address:</td><td><strong>' . htmlspecialchars($registration['email'] ?? 'N/A') . '</strong></td></tr>
                <tr><td>Course:</td><td><strong>' . htmlspecialchars($registration['class_name'] ?? 'N/A') . '</strong></td></tr>
                <tr><td>Duration:</td><td><strong>' . htmlspecialchars($registration['duration_weeks'] ?? 'N/A') . ' weeks</strong></td></tr>
                <tr><td>Start Date:</td><td><strong>' . date('F d, Y', strtotime($registration['start_date'] ?? 'now')) . '</strong></td></tr>
                <tr><td>Schedule:</td><td><strong>' . htmlspecialchars($schedule) . '</strong></td></tr>
            </table>
            
            <h3>Payment Details:</h3>
            <div class="payment-summary">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td style="padding: 8px;"><strong>Total Course Fee:</strong></td><td style="padding: 8px;">KES ' . number_format($total_fee, 2) . '</td></tr>
                    <tr><td style="padding: 8px;"><strong>Amount Paid:</strong></td><td style="padding: 8px;">KES ' . number_format($amount_paid, 2) . '</td></tr>
                    <tr style="background: #f5f5f5;"><td style="padding: 8px;"><strong>Remaining Balance:</strong></td>
                    <td style="padding: 8px;"><strong style="color: ' . ($remaining_balance > 0 ? '#e65100' : '#2e7d32') . ';">KES ' . number_format($remaining_balance, 2) . '</strong></td></tr>
                </table>';

if($remaining_balance > 0) {
    $letter_html .= '<p style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ffcc80; color: #e65100;">
                        <strong>Note:</strong> You have a remaining balance of KES ' . number_format($remaining_balance, 2) . '. 
                        Please complete the payment before the second week of classes.
                    </p>';
} else {
    $letter_html .= '<p style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ffcc80; color: #2e7d32;">
                        <strong>✓ Payment Complete!</strong> Your course fee is fully paid.
                    </p>';
}

$letter_html .= '
            </div>
            
            <div class="important-info">
                <h4>Important Information:</h4>
                <ul>
                    <li>Please report to the training center on the start date at 8:00 AM</li>
                    <li>Bring a printed copy of this letter on the first day</li>';
                    
if($remaining_balance > 0) {
    $letter_html .= '<li>Complete the remaining payment before the second week</li>';
}
                    
$letter_html .= '
                    <li>Attendance is mandatory for certification</li>
                    <li>For any inquiries, contact the admin office</li>
                </ul>
            </div>
            
            <div class="content">
                <p>We look forward to having you in our class and wish you success in your learning journey.</p>
            </div>
            
            <div class="signature">
                <p>_________________________</p>
                <p><strong>Training Coordinator</strong></p>
                <p>Makueni County Innovation Hub</p>
                <p style="font-size: 12px; color: #666;">This is a computer-generated document. No signature required.</p>
            </div>
            
            <div class="footer">
                <p>&copy; ' . date('Y') . ' Makueni County Innovation Hub. All rights reserved.</p>
                <p>Powered by County Government of Makueni</p>
            </div>
        </div>
    </div>
</body>
</html>';

// If download parameter is set, force download as HTML file
if ($download) {
    $filename = "Admission_Letter_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', ($registration['full_name'] ?? 'Student')) . ".html";
    
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($letter_html));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    echo $letter_html;
    exit;
}

// Otherwise, display the letter with download button
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Letter - <?php echo htmlspecialchars($registration['full_name'] ?? 'Student'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 30px; margin: 0; }
        .letter-wrapper { max-width: 1000px; margin: 0 auto; }
        .action-buttons { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .btn { display: inline-flex; align-items: center; gap: 10px; padding: 12px 25px; margin: 0 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s ease; border: none; cursor: pointer; }
        .btn-primary { background: #1a5c3a; color: white; }
        .btn-primary:hover { background: #0a3d2a; transform: translateY(-2px); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; transform: translateY(-2px); }
        .letter-content { background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .letter-frame { background: white; }
        .success-message { background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-left: 4px solid #28a745; }
        @media print { .action-buttons { display: none; } body { background: white; padding: 0; margin: 0; } }
    </style>
</head>
<body>
    <div class="letter-wrapper">
        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Letter
            </button>
            <a href="?id=<?php echo $registration_id; ?>&download=1" class="btn btn-success">
                <i class="fas fa-download"></i> Download Letter (HTML)
            </a>
            <a href="register_class.php" class="btn btn-secondary">
                <i class="fas fa-user-plus"></i> Register Another Student
            </a>
            <a href="user/dashboard.php" class="btn btn-secondary">
                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
            </a>
        </div>
        
        <div class="letter-content">
            <div class="letter-frame">
                <?php echo $letter_html; ?>
            </div>
        </div>
        
        <div class="action-buttons" style="margin-top: 20px;">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
            <a href="?id=<?php echo $registration_id; ?>&download=1" class="btn btn-success">
                <i class="fas fa-download"></i> Download Letter
            </a>
        </div>
    </div>
</body>
</html>