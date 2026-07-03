<?php
// SMS Configuration - Africa's Talking
// Sign up at https://account.africastalking.com

$sms_config = [
    'username' => 'sandbox', // Use 'sandbox' for testing, or your actual username
    'api_key' => 'YOUR_API_KEY_HERE', // Get from Africa's Talking dashboard
    'shortcode' => '20800', // Africa's Talking shortcode
    'sender_id' => 'MAKUENI' // Sender ID (max 11 characters)
];

// For testing without real SMS, set to true
$sms_test_mode = true; // Set to false when you have real API key

// Function to send SMS
function sendSMS($phone, $message) {
    global $sms_config, $sms_test_mode;
    
    // Format phone number (remove leading 0, add 254)
    $phone = preg_replace('/^0/', '254', $phone);
    $phone = preg_replace('/^\+/', '', $phone);
    
    if($sms_test_mode) {
        // Log SMS for testing
        $log_file = __DIR__ . '/../logs/sms_log.txt';
        $log_dir = dirname($log_file);
        if(!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        $log_entry = date('Y-m-d H:i:s') . " - To: $phone - Message: $message\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        return ['status' => 'success', 'message' => 'SMS logged (test mode)'];
    }
    
    // Real SMS via Africa's Talking API
    $url = 'https://api.africastalking.com/version1/messaging';
    $data = [
        'username' => $sms_config['username'],
        'to' => $phone,
        'message' => $message,
        'from' => $sms_config['sender_id']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apiKey: ' . $sms_config['api_key'],
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if($http_code == 201) {
        return ['status' => 'success', 'response' => json_decode($response, true)];
    } else {
        return ['status' => 'error', 'message' => 'SMS sending failed'];
    }
}
?>