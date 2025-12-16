<?php
/**
 * WhatsApp API Configuration
 * 
 * This file contains WhatsApp API credentials and settings
 * for sending appointment notifications.
 * 
 * IMPORTANT: Keep this file secure and never expose API keys on frontend
 */

// WhatsApp API Configuration
$whatsapp_config = [
    // Twilio WhatsApp API Configuration
    'twilio' => [
        'account_sid' => 'YOUR_TWILIO_ACCOUNT_SID',         // Replace with your Twilio Account SID
        'auth_token' => 'YOUR_TWILIO_AUTH_TOKEN',           // Replace with your Twilio Auth Token
        'from_number' => 'whatsapp:+14155238886',           // Twilio WhatsApp Sandbox number
        'api_url' => 'https://api.twilio.com/2010-04-01/Accounts/'
    ],
    
    // WhatsApp Cloud API Configuration (Alternative)
    'whatsapp_cloud' => [
        'access_token' => 'YOUR_WHATSAPP_ACCESS_TOKEN',    // Replace with your WhatsApp Access Token
        'phone_number_id' => 'YOUR_PHONE_NUMBER_ID',        // Replace with your WhatsApp Phone Number ID
        'api_version' => 'v18.0',
        'api_url' => 'https://graph.facebook.com/'
    ],
    
    // Choose which API to use: 'twilio' or 'whatsapp_cloud'
    'api_provider' => 'twilio',
    
    // Message settings
    'message_settings' => [
        'enable_patient_notifications' => true,
        'enable_hospital_notifications' => true,
        'admin_phone' => '+1234567890',                     // Admin phone number for notifications
        'log_errors' => true,
        'log_file' => __DIR__ . '/../logs/whatsapp.log'
    ]
];

/**
 * WhatsApp Message Sender Class
 * 
 * Handles sending WhatsApp messages using different API providers
 */
class WhatsAppMessageSender {
    private $config;
    private $log_file;
    
    public function __construct() {
        global $whatsapp_config;
        $this->config = $whatsapp_config;
        $this->log_file = $this->config['message_settings']['log_file'];
        
        // Create log directory if it doesn't exist
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
    }
    
    /**
     * Send WhatsApp message
     * 
     * @param string $to Phone number (with country code, e.g., +1234567890)
     * @param string $message Message content
     * @return bool Success status
     */
    public function sendMessage($to, $message) {
        try {
            // Clean phone number format
            $to = $this->cleanPhoneNumber($to);
            
            // Choose API provider
            if ($this->config['api_provider'] === 'twilio') {
                return $this->sendViaTwilio($to, $message);
            } else {
                return $this->sendViaWhatsAppCloud($to, $message);
            }
        } catch (Exception $e) {
            $this->logError('WhatsApp send error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send message via Twilio WhatsApp API
     */
    private function sendViaTwilio($to, $message) {
        $url = $this->config['twilio']['api_url'] . $this->config['twilio']['account_sid'] . '/Messages.json';
        
        $data = [
            'From' => $this->config['twilio']['from_number'],
            'To' => 'whatsapp:' . $to,
            'Body' => $message
        ];
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->config['twilio']['account_sid'] . ':' . $this->config['twilio']['auth_token']),
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        return $this->makeCurlRequest($url, $data, $headers);
    }
    
    /**
     * Send message via WhatsApp Cloud API
     */
    private function sendViaWhatsAppCloud($to, $message) {
        $url = $this->config['whatsapp_cloud']['api_url'] . $this->config['whatsapp_cloud']['api_version'] . '/' . $this->config['whatsapp_cloud']['phone_number_id'] . '/messages';
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $this->config['whatsapp_cloud']['access_token'],
            'Content-Type: application/json'
        ];
        
        return $this->makeCurlRequest($url, json_encode($data), $headers, true);
    }
    
    /**
     * Make cURL request to API
     */
    private function makeCurlRequest($url, $data, $headers, $is_json = false) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            $this->logError('cURL error: ' . $error);
            return false;
        }
        
        if ($http_code >= 200 && $http_code < 300) {
            $this->logSuccess('Message sent successfully to ' . $url);
            return true;
        } else {
            $this->logError('API error. HTTP Code: ' . $http_code . '. Response: ' . $response);
            return false;
        }
    }
    
    /**
     * Clean phone number format
     */
    private function cleanPhoneNumber($phone) {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Ensure it starts with +
        if (substr($phone, 0, 1) !== '+') {
            // Add country code if missing (assuming default country code)
            if (strlen($phone) === 10) {
                $phone = '+1' . $phone; // Default to US country code
            } else {
                $phone = '+' . $phone;
            }
        }
        
        return $phone;
    }
    
    /**
     * Log error message
     */
    private function logError($message) {
        if ($this->config['message_settings']['log_errors']) {
            $log_message = date('Y-m-d H:i:s') . ' - ERROR: ' . $message . PHP_EOL;
            file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Log success message
     */
    private function logSuccess($message) {
        if ($this->config['message_settings']['log_errors']) {
            $log_message = date('Y-m-d H:i:s') . ' - SUCCESS: ' . $message . PHP_EOL;
            file_put_contents($this->log_file, $log_message, FILE_APPEND | LOCK_EX);
        }
    }
}

/**
 * Convenience function to send WhatsApp message
 * 
 * @param string $to Phone number
 * @param string $message Message content
 * @return bool Success status
 */
function sendWhatsAppMessage($to, $message) {
    $sender = new WhatsAppMessageSender();
    return $sender->sendMessage($to, $message);
}
?>
