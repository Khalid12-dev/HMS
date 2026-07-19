<?php
// sms_gateway.php
function sendSMS($phone, $message) {
    // Enable detailed error logging
    error_log("Attempting to send SMS to: $phone, Message: $message");
    
    // Format the phone number
    $formattedPhone = formatPhoneNumber($phone);
    if (!$formattedPhone) {
        error_log("Invalid phone number format: $phone");
        return false;
    }
    
    // For testing - comment this out in production
    return testSendSMS($formattedPhone, $message);
    
    // Your SMS provider configuration
    $config = [
        'username' => 'your_username',
        'password' => 'your_password',
        'sender' => 'MediCare',
        'api_url' => 'http://sms.pk/api/send'
    ];
    
    // Build the API request
    $query = http_build_query([
        'username' => $config['username'],
        'password' => $config['password'],
        'sender' => $config['sender'],
        'mobilenum' => $formattedPhone,
        'message' => $message
    ]);
    
    $url = $config['api_url'] . '?' . $query;
    error_log("SMS API URL: " . $url);
    
    // Send using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    
    error_log("SMS API Response: HTTP $httpCode - $response");
    
    // Check if SMS was sent successfully
    return strpos($response, 'Sent') !== false;
}

function formatPhoneNumber($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check for Pakistani phone number format
    if (preg_match('/^(0|92|\+92)?(3[0-9]{9})$/', $phone, $matches)) {
        return '92' . $matches[2]; // Return in 923001234567 format
    }
    
    return false;
}

function testSendSMS($phone, $message) {
    // For testing - log to file and session
    $log = date('Y-m-d H:i:s') . " - To: $phone\nMessage: $message\n\n";
    file_put_contents('sms_test.log', $log, FILE_APPEND);
    
    // Store OTP in session for debugging
    $_SESSION['debug_otp'] = substr($message, strpos($message, ':') + 2, 6);
    $_SESSION['debug_phone'] = $phone;
    
    return true;
}