<?php
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../config/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendPasswordResetEmail($to_email, $to_name, $reset_link) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - Makueni County Innovation Hub';
        $mail->Body    = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1a5c3a; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { 
                    display: inline-block; 
                    padding: 12px 25px; 
                    background: #1a5c3a; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 20px 0;
                }
                .footer { font-size: 12px; color: #666; text-align: center; padding: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>MAKUENI COUNTY INNOVATION HUB</h2>
                    <p>Password Reset Request</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($to_name) . "</strong>,</p>
                    <p>We received a request to reset your password. Click the button below to create a new password:</p>
                    <p style='text-align: center;'>
                        <a href='" . $reset_link . "' class='button'>Reset Password</a>
                    </p>
                    <p>Or copy and paste this link into your browser:</p>
                    <p><a href='" . $reset_link . "'>" . $reset_link . "</a></p>
                    <p><strong>This link will expire in 1 hour.</strong></p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                    <p>For security reasons, do not share this link with anyone.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Makueni County Innovation Hub. All rights reserved.</p>
                    <p>This is an automated message, please do not reply directly to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Password Reset\n\nDear $to_name,\n\nClick the link below to reset your password:\n$reset_link\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.\n\nRegards,\nMakueni County Innovation Hub";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>