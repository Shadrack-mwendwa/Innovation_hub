<?php
require_once 'includes/send_email.php';

echo "<h2>Testing Email Configuration</h2>";

$test_email = "shadrackmwendwa477@gmail.com"; // Your email
$test_name = "Test User";
$test_link = "http://localhost/innovation_hub/test.php";

echo "<p> Sending test email to: <strong>$test_email</strong></p>";

if (sendPasswordResetEmail($test_email, $test_name, $test_link)) {
    echo "<p style='color: green; font-weight: bold;'> Email sent successfully! Check your inbox.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'> Failed to send email. Check your SMTP settings.</p>";
}

echo "<hr>";
echo "<p><a href='forgot_password.php'> Go to Forgot Password</a></p>";
?>