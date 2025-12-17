<?php
/**
 * Email Configuration - FIXED VERSION
 * Enhanced with better error handling and fallback options
 */

// Email Configuration
$email_config = [
    // SMTP Configuration - UPDATE THESE VALUES
    'smtp' => [
        'host' => 'smtp.gmail.com',           // Your SMTP host
        'port' => 587,                         // SMTP port (587 for TLS, 465 for SSL)
        'username' => 'your-actual-email@gmail.com', // UPDATE: Your real email
        'password' => 'your-actual-app-password',    // UPDATE: Your app password
        'encryption' => 'tls',                // 'tls' or 'ssl'
        'from_email' => 'your-actual-email@gmail.com', // UPDATE: Same as username
        'from_name' => 'Hospital Booking System'
    ],
    
    // Email settings
    'settings' => [
        'enable_emails' => true,              // Set to false to disable emails
        'admin_email' => 'admin@hospitalbooking.com',
        'log_errors' => true,
        'log_file' => __DIR__ . '/../logs/emails.log'
    ]
];

/**
 * Email Sender Class - Enhanced
 */
class EmailSender {
    private $config;
    private $log_file;
    
    public function __construct() {
        global $email_config;
        $this->config = $email_config;
        $this->log_file = $this->config['settings']['log_file'];
        
        // Create log directory if it doesn't exist
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }
    
    /**
     * Send email with enhanced error handling
     */
    public function sendEmail($to, $subject, $body, $altBody = '') {
        // Check if emails are enabled
        if (!$this->config['settings']['enable_emails']) {
            $this->logError('Email sending is disabled in settings');
            return false;
        }
        
        // Validate email configuration
        if ($this->config['smtp']['username'] === 'your-actual-email@gmail.com') {
            $this->logError('Email configuration not updated - using placeholder credentials');
            return false;
        }
        
        try {
            // Try to use PHPMailer if available
            if ($this->usePHPMailer()) {
                return $this->sendViaPHPMailer($to, $subject, $body, $altBody);
            } else {
                return $this->sendViaNativeMail($to, $subject, $body, $altBody);
            }
        } catch (Exception $e) {
            $this->logError('Email send error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if PHPMailer is available
     */
    private function usePHPMailer() {
        return class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendViaPHPMailer($to, $subject, $body, $altBody) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = 0;                    // Disable verbose debug
            $mail->isSMTP();
            $mail->Host = $this->config['smtp']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp']['username'];
            $mail->Password = $this->config['smtp']['password'];
            $mail->SMTPSecure = $this->config['smtp']['encryption'];
            $mail->Port = $this->config['smtp']['port'];
            
            // Recipients
            $mail->setFrom($this->config['smtp']['from_email'], $this->config['smtp']['from_name']);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $altBody;
            
            $mail->send();
            $this->logSuccess("Email sent successfully to $to");
            return true;
            
        } catch (Exception $e) {
            $this->logError('PHPMailer error: ' . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send email using native PHP mail
     */
    private function sendViaNativeMail($to, $subject, $body, $altBody) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->config['smtp']['from_name'] . ' <' . $this->config['smtp']['from_email'] . '>',
            'Reply-To: ' . $this->config['smtp']['from_email']
        ];
        
        $headers = implode("\r\n", $headers);
        
        if (mail($to, $subject, $body, $headers)) {
            $this->logSuccess("Email sent successfully to $to");
            return true;
        } else {
            $this->logError('Native mail function failed');
            return false;
        }
    }
    
    /**
     * Log error message
     */
    private function logError($message) {
        if ($this->config['settings']['log_errors']) {
            $log_message = date('Y-m-d H:i:s') . ' - ERROR: ' . $message . PHP_EOL;
            file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Log success message
     */
    private function logSuccess($message) {
        if ($this->config['settings']['log_errors']) {
            $log_message = date('Y-m-d H:i:s') . ' - SUCCESS: ' . $message . PHP_EOL;
            file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
        }
    }
}

/**
 * Convenience function to send email
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    $sender = new EmailSender();
    return $sender->sendEmail($to, $subject, $body, $altBody);
}

?>
