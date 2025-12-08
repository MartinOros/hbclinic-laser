<?php
/**
 * Chat Form Processor
 * Handles email submissions from chat bubble
 * 
 * CONFIGURATION:
 * - Option 1: Use PHP mail() function (may not work on all servers)
 * - Option 2: Use SMTP (recommended - more reliable)
 * 
 * To use SMTP, set USE_SMTP to true and configure SMTP settings below
 */

// Start output buffering to prevent any output before JSON
if (ob_get_level() == 0) {
    ob_start();
}

// Disable error display for production (errors will be logged)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Debug mode - set to true to see error details in response (for development only)
define('DEBUG_MODE', false);

// ============================================
// Helper function to send JSON response
// ============================================
function sendJsonResponse($success, $message, $debug = null) {
    // Clean output buffer
    if (ob_get_level() > 0) {
        ob_clean();
    }
    
    // Set JSON header (must be before any output)
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate');
    }
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (DEBUG_MODE && $debug !== null) {
        $response['debug'] = $debug;
    }
    
    $json = json_encode($response, JSON_UNESCAPED_UNICODE);
    
    // Ensure we have valid JSON
    if ($json === false) {
        error_log("JSON encode failed: " . json_last_error_msg());
        $json = json_encode(['success' => false, 'message' => 'Server error encoding response'], JSON_UNESCAPED_UNICODE);
    }
    
    echo $json;
    
    // End output buffering and send
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    
    // Try to finish request if available (for FastCGI)
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    exit;
}

// ============================================
// Global error handler
// ============================================
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    
    // Only handle fatal errors
    if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        if (function_exists('sendJsonResponse')) {
            sendJsonResponse(false, 'Server error occurred. Please try again later.');
        } else {
            // Fallback if sendJsonResponse not yet defined
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Server error occurred. Please try again later.'], JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            exit;
        }
    }
    
    return false; // Let PHP handle other errors
}

// Set error handler
set_error_handler('handleError');

// ============================================
// Shutdown handler for fatal errors
// ============================================
function handleShutdown() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        if (function_exists('sendJsonResponse')) {
            sendJsonResponse(false, 'Fatal server error occurred. Please try again later.');
        } else {
            // Fallback if sendJsonResponse not yet defined
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Fatal server error occurred. Please try again later.'], JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            exit;
        }
    }
}

register_shutdown_function('handleShutdown');

// ============================================
// EMAIL CONFIGURATION
// ============================================
define('USE_SMTP', true); // Set to true to use SMTP instead of mail()

// SMTP Configuration (Websupport.sk)
define('SMTP_HOST', 'smtp.m1.websupport.sk');
define('SMTP_PORT', 465); // Port 465 for SSL
define('SMTP_USERNAME', 'noreply@hbclinic.sk');
define('SMTP_PASSWORD', '}7<%+d1lDxj/)gp>XM``');
define('SMTP_ENCRYPTION', 'ssl'); // 'ssl' for port 465
define('SMTP_FROM_EMAIL', 'noreply@hbclinic.sk');
define('SMTP_FROM_NAME', 'HB Clinic');

// Recipient email (TESTING - change to info@hbclinic.sk for production)
define('TO_EMAIL', 'info@hbclinic.sk');

// Google reCAPTCHA v3 Configuration
define('RECAPTCHA_ENABLED', true); // reCAPTCHA is enabled
define('RECAPTCHA_SECRET_KEY', '6LfV8SQsAAAAAL0QxkdYCnWriLjLIBjveIyw15Wp'); // Secret key
define('RECAPTCHA_SCORE_THRESHOLD', 0.5); // Score threshold (0.0 = bot, 1.0 = human)

// ============================================
// Main execution wrapped in try-catch
// ============================================
try {
    // ============================================
    // Prevent direct access
    // ============================================
    // Allow GET only for testing/debugging (remove in production)
    $isTestMode = isset($_GET['test']) && $_GET['test'] === '1';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$isTestMode) {
        http_response_code(405);
        sendJsonResponse(false, 'Method not allowed', DEBUG_MODE ? [
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'expected' => 'POST',
            'content_type' => isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'not set'
        ] : null);
    }

    // Test mode - return configuration info (for debugging only)
    if ($isTestMode && $_SERVER['REQUEST_METHOD'] === 'GET') {
        sendJsonResponse(true, 'Test mode - configuration loaded', [
            'use_smtp' => USE_SMTP,
            'smtp_host' => USE_SMTP ? SMTP_HOST : 'N/A',
            'smtp_port' => USE_SMTP ? SMTP_PORT : 'N/A',
            'smtp_username' => USE_SMTP ? SMTP_USERNAME : 'N/A',
            'to_email' => TO_EMAIL,
            'phpmailer_exists' => class_exists('PHPMailer\PHPMailer\PHPMailer')
        ]);
    }

    // ============================================
    // Bot Protection Checks
    // ============================================
    function validateBotProtection() {
        // Honeypot check
        if (isset($_POST['website']) && !empty($_POST['website'])) {
            return false;
        }
        
        // Time-based validation
        if (isset($_POST['form_timestamp'])) {
            $formStartTime = intval($_POST['form_timestamp']);
            $currentTime = time() * 1000;
            $timeSpent = ($currentTime - $formStartTime) / 1000;
            
            if ($timeSpent < 2 || $timeSpent > 3600) {
                return false;
            }
        }
        
        // Google reCAPTCHA v3 verification (if enabled)
        if (RECAPTCHA_ENABLED) {
            if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
                $recaptchaResponse = $_POST['g-recaptcha-response'];
                $expectedAction = 'chat_submit'; // Action name for chat form
                
                $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';
                $data = array(
                    'secret' => RECAPTCHA_SECRET_KEY,
                    'response' => $recaptchaResponse,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                );
                
                $options = array(
                    'http' => array(
                        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method' => 'POST',
                        'content' => http_build_query($data),
                        'timeout' => 10
                    )
                );
                
                $context = stream_context_create($options);
                $result = @file_get_contents($verifyURL, false, $context);
                
                if ($result === false) {
                    error_log("reCAPTCHA verification failed: Could not connect to Google");
                    return false; // Fail closed - require reCAPTCHA to work
                }
                
                $resultJson = json_decode($result);
                
                // Verify reCAPTCHA response
                if (!$resultJson || !isset($resultJson->success) || !$resultJson->success) {
                    error_log("reCAPTCHA verification failed: Invalid token");
                    return false;
                }
                
                // Verify the action name matches
                if (!isset($resultJson->action) || $resultJson->action !== $expectedAction) {
                    error_log("reCAPTCHA action mismatch: expected {$expectedAction}, got " . (isset($resultJson->action) ? $resultJson->action : 'none'));
                    return false;
                }
                
                // Check score (0.0 = bot, 1.0 = human)
                if (!isset($resultJson->score) || $resultJson->score < RECAPTCHA_SCORE_THRESHOLD) {
                    error_log("reCAPTCHA score too low: " . (isset($resultJson->score) ? $resultJson->score : 'none') . " (threshold: " . RECAPTCHA_SCORE_THRESHOLD . ")");
                    return false;
                }
                
                // Optional: Verify hostname matches your domain
                $expectedHostname = $_SERVER['HTTP_HOST'];
                if (isset($resultJson->hostname) && $resultJson->hostname !== $expectedHostname) {
                    // Log but don't block - might be legitimate (subdomains, etc.)
                    error_log("reCAPTCHA hostname mismatch: expected {$expectedHostname}, got {$resultJson->hostname}");
                }
            } else {
                // reCAPTCHA is enabled but token is missing
                error_log("reCAPTCHA enabled but token missing");
                return false;
            }
        }
        
        return true;
    }

    // Validate bot protection
    if (!validateBotProtection()) {
        sendJsonResponse(false, 'Spam detected');
    }

    // ============================================
    // Sanitize and validate input
    // ============================================
    $name = isset($_POST['name']) ? trim(htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8')) : '';
    $email = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
    $message = isset($_POST['message']) ? trim(htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8')) : '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
        sendJsonResponse(false, 'Prosím vyplňte všetky povinné polia');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(false, 'Neplatná emailová adresa');
    }

    // Validate message length
    if (strlen($message) < 5) {
        sendJsonResponse(false, 'Správa je príliš krátka');
    }

    if (strlen($message) > 2000) {
        sendJsonResponse(false, 'Správa je príliš dlhá');
    }

    // ============================================
    // Email body
    // ============================================
    $subject = "Nová správa z chat bubliny epilaciakosice.sk - " . $name;
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #D34C7D; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { padding: 20px; background-color: #f9f9f9; border-radius: 0 0 5px 5px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #333; }
            .value { color: #666; margin-top: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Nová správa z chat bubliny epilaciakosice.sk</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>Meno:</div>
                    <div class='value'>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</div>
                </div>
                <div class='field'>
                    <div class='label'>E-mail:</div>
                    <div class='value'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Správa:</div>
                    <div class='value'>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</div>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";

    // ============================================
    // Send email
    // ============================================
    if (USE_SMTP) {
        // Use SMTP (requires PHPMailer library)
        $result = sendEmailSMTP($email, $name, $subject, $emailBody);
    } else {
        // Use PHP mail() function
        $result = sendEmailPHP($email, $name, $subject, $emailBody);
    }

    // Send JSON response
    if (isset($result['success']) && $result['success']) {
        sendJsonResponse(true, 'Ďakujeme! Vaša správa bola odoslaná.');
    } else {
        $errorMsg = isset($result['message']) ? $result['message'] : 'Chyba pri odosielaní emailu. Skúste to prosím znova.';
        sendJsonResponse(false, $errorMsg);
    }

} catch (\Throwable $e) {
    // Catch any unhandled exceptions
    error_log("Unhandled Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $debug = null;
    if (DEBUG_MODE) {
        $debug = [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
    }
    
    sendJsonResponse(false, 'Neočakávaná chyba servera. Skúste to prosím znova.', $debug);
}

// ============================================
// Email Functions
// ============================================

/**
 * Send email using PHP mail() function
 */
function sendEmailPHP($fromEmail, $fromName, $subject, $body) {
    try {
        $to = TO_EMAIL;
        $headers = "From: " . $fromEmail . "\r\n";
        $headers .= "Reply-To: " . $fromEmail . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        if (mail($to, $subject, $body, $headers)) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Chyba pri odosielaní emailu. Skúste to prosím znova.'];
        }
    } catch (\Exception $e) {
        error_log("mail() function error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Chyba pri odosielaní emailu. Skúste to prosím znova.'];
    }
}

/**
 * Send email using SMTP (requires PHPMailer)
 * Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
 * Place PHPMailer folder in your project root
 */
function sendEmailSMTP($fromEmail, $fromName, $subject, $body) {
    try {
        // Check if PHPMailer is available
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            // Try to include PHPMailer from PHPMailer-master folder
            $phpmailerPath = __DIR__ . '/PHPMailer-master/src/Exception.php';
            if (file_exists($phpmailerPath)) {
                // Suppress any output from require_once
                $oldErrorReporting = error_reporting(0);
                require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
                require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
                require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
                error_reporting($oldErrorReporting);
            } else {
                // Try alternative path (if renamed to PHPMailer)
                $phpmailerPathAlt = __DIR__ . '/PHPMailer/src/Exception.php';
                if (file_exists($phpmailerPathAlt)) {
                    $oldErrorReporting = error_reporting(0);
                    require_once __DIR__ . '/PHPMailer/src/Exception.php';
                    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
                    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
                    error_reporting($oldErrorReporting);
                } else {
                    error_log("PHPMailer not found. Checked paths: $phpmailerPath and $phpmailerPathAlt");
                    return ['success' => false, 'message' => 'PHPMailer nie je nainštalovaný. Použite mail() funkciu alebo nainštalujte PHPMailer.'];
                }
            }
        }
        
        // Verify PHPMailer class exists after loading
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log("PHPMailer class not found after require_once");
            return ['success' => false, 'message' => 'PHPMailer trieda nebola nájdená. Skontrolujte inštaláciu PHPMailer.'];
        }
        
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Disable SMTP debug output completely (can cause issues with JSON response)
        $mail->SMTPDebug = 0;
        // Don't set Debugoutput to avoid any potential output
        
        // Email content
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress(TO_EMAIL);
        $mail->addReplyTo($fromEmail, $fromName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return ['success' => true];
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        $errorMessage = 'Chyba pri odosielaní emailu cez SMTP.';
        if (DEBUG_MODE) {
            $errorMessage .= ' ' . $e->getMessage();
        }
        return ['success' => false, 'message' => $errorMessage];
    } catch (\Exception $e) {
        error_log("General Error in sendEmailSMTP: " . $e->getMessage());
        $errorMessage = 'Chyba pri odosielaní emailu.';
        if (DEBUG_MODE) {
            $errorMessage .= ' ' . $e->getMessage();
        }
        return ['success' => false, 'message' => $errorMessage];
    }
}
