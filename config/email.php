<?php
/**
 * Email Configuration
 * 
 * This file contains email credentials and settings
 * for sending hospital registration confirmation emails.
 * 
 * IMPORTANT: Keep this file secure and never expose credentials on frontend
 */

// Email Configuration
$email_config = [
    // SMTP Configuration
    'smtp' => [
        'host' => 'smtp.gmail.com',           // Replace with your SMTP host
        'port' => 587,                         // SMTP port
        'username' => 'your-email@gmail.com', // Replace with your email
        'password' => 'your-app-password',    // Replace with your app password
        'encryption' => 'tls',                // tls or ssl
        'from_email' => 'your-email@gmail.com', // From email address
        'from_name' => 'Hospital Booking System' // From name
    ],
    
    // Email settings
    'settings' => [
        'enable_emails' => true,
        'admin_email' => 'admin@hospitalbooking.com', // Admin notification email
        'log_errors' => true,
        'log_file' => __DIR__ . '/../logs/emails.log'
    ]
];

/**
 * Email Sender Class
 * 
 * Handles sending emails using PHPMailer or native PHP mail
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
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $altBody Alternative plain text body
     * @return bool Success status
     */
    public function sendEmail($to, $subject, $body, $altBody = '') {
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
            // SMTP settings
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
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $altBody Alternative plain text body
 * @return bool Success status
 */
function sendEmail($to, $subject, $body, $altBody = '') {
    $sender = new EmailSender();
    return $sender->sendEmail($to, $subject, $body, $altBody);
}
?>
