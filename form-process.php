<?php
/**
 * Contact Form Processor with Bot Protection
 * 
 * IMPORTANT: This is a sample file. You need to:
 * 1. Configure email settings
 * 2. Add your reCAPTCHA secret key if using reCAPTCHA
 * 3. Customize validation rules
 */

// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

// Bot Protection Checks
function validateBotProtection() {
    // 1. Honeypot check - if website field is filled, it's a bot
    if (isset($_POST['website']) && !empty($_POST['website'])) {
        return false;
    }
    
    // 2. Time-based validation - check if form was submitted too quickly
    if (isset($_POST['form_timestamp'])) {
        $formStartTime = intval($_POST['form_timestamp']);
        $currentTime = time() * 1000; // Convert to milliseconds
        $timeSpent = ($currentTime - $formStartTime) / 1000; // in seconds
        
        // If submitted in less than 3 seconds, likely a bot
        if ($timeSpent < 3) {
            return false;
        }
        
        // Also check if submitted too slowly (more than 1 hour) - might be stale
        if ($timeSpent > 3600) {
            return false;
        }
    }
    
    // 3. Basic spam pattern detection
    $email = isset($_POST['email']) ? strtolower($_POST['email']) : '';
    $message = isset($_POST['message']) ? strtolower($_POST['message']) : '';
    
    $spamPatterns = array(
        '/\b(viagra|cialis|casino|poker|loan|mortgage|credit|bitcoin|crypto)\b/i',
        '/\b(http|https|www\.)\b/i', // URLs in message
    );
    
    foreach ($spamPatterns as $pattern) {
        if (preg_match($pattern, $message) || preg_match($pattern, $email)) {
            error_log("Suspicious form submission detected: " . $email);
            return false; // Block spam
        }
    }
    
    return true;
}

// Validate bot protection
if (!validateBotProtection()) {
    http_response_code(403);
    echo "Spam detected";
    exit;
}

// Sanitize and validate input
$fname = isset($_POST['fname']) ? trim(htmlspecialchars($_POST['fname'], ENT_QUOTES, 'UTF-8')) : '';
$email = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
$message = isset($_POST['message']) ? trim(htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8')) : '';

// Validate required fields
if (empty($fname) || empty($email) || empty($message)) {
    echo "Please fill in all required fields";
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email address";
    exit;
}

// Validate message length
if (strlen($message) < 10) {
    echo "Message is too short";
    exit;
}

if (strlen($message) > 5000) {
    echo "Message is too long";
    exit;
}

// Email configuration
$to = "info@hbclinic.sk"; // Change to your email
$subject = "Nová správa z kontaktného formulára - " . $fname;
$headers = "From: " . $email . "\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Email body
$emailBody = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #D34C7D; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #333; }
        .value { color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Nová správa z kontaktného formulára</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>Meno:</div>
                <div class='value'>" . $fname . "</div>
            </div>
            <div class='field'>
                <div class='label'>E-mail:</div>
                <div class='value'>" . $email . "</div>
            </div>
            <div class='field'>
                <div class='label'>Správa:</div>
                <div class='value'>" . nl2br($message) . "</div>
            </div>
        </div>
    </div>
</body>
</html>
";

// Send email
if (mail($to, $subject, $emailBody, $headers)) {
    echo "success";
} else {
    echo "Error sending email. Please try again later.";
}
?>

