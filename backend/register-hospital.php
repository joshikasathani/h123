<?php
/**
 * Hospital Registration Backend - FIXED VERSION
 * Enhanced with proper error handling, logging, and graceful email failures
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/registration_errors.log');

// Ensure log directory exists
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Set JSON response header
header('Content-Type: application/json');

// Log function
function logError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] ERROR: $message" . PHP_EOL;
    error_log($log_message, 3, __DIR__ . '/../logs/registration_errors.log');
}

function logSuccess($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] SUCCESS: $message" . PHP_EOL;
    error_log($log_message, 3, __DIR__ . '/../logs/registration_errors.log');
}

// Database connection with error handling
function getDatabaseConnection() {
    try {
        require_once '../config/database.php';
        
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Connection not initialized'));
        }
        
        // Test if database exists
        $result = $conn->query("SHOW DATABASES LIKE 'hospital_booking'");
        if ($result->num_rows === 0) {
            throw new Exception("Database 'hospital_booking' does not exist");
        }
        
        // Select database
        if (!$conn->select_db('hospital_booking')) {
            throw new Exception("Cannot select database 'hospital_booking'");
        }
        
        return $conn;
        
    } catch (Exception $e) {
        logError("Database connection error: " . $e->getMessage());
        throw $e;
    }
}

// Main registration logic
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Only POST allowed.");
    }
    
    // Get and sanitize form data
    $hospitalName = trim($_POST['hospital_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $availableDays = trim($_POST['available_days'] ?? '');
    $availableTimings = trim($_POST['available_timings'] ?? '');
    
    logSuccess("Registration attempt for hospital: $hospitalName, email: $email");
    
    // Validate required fields
    $errors = [];
    
    if (empty($hospitalName)) {
        $errors[] = 'Hospital name is required';
    } elseif (strlen($hospitalName) < 3) {
        $errors[] = 'Hospital name must be at least 3 characters';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    } elseif (strlen($address) < 10) {
        $errors[] = 'Address must be at least 10 characters';
    }
    
    if (empty($phoneNumber)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[\d\s\-\+\(\)]+$/', $phoneNumber)) {
        $errors[] = 'Please enter a valid phone number';
    } elseif (strlen(preg_replace('/\D/', '', $phoneNumber)) < 10) {
        $errors[] = 'Phone number must be at least 10 digits';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($specialization)) {
        $errors[] = 'Specialization is required';
    } elseif (strlen($specialization) < 3) {
        $errors[] = 'Specialization must be at least 3 characters';
    }
    
    if (empty($availableDays)) {
        $errors[] = 'Available days are required';
    }
    
    if (empty($availableTimings)) {
        $errors[] = 'Available timings are required';
    }
    
    // If validation errors, return them
    if (!empty($errors)) {
        logError("Validation failed: " . implode(', ', $errors));
        echo json_encode([
            'success' => false,
            'message' => 'Validation errors occurred',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Get database connection
    $conn = getDatabaseConnection();
    
    // Check for duplicate email
    $stmt = $conn->prepare("SELECT id FROM hospitals WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare duplicate check query: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = 'A hospital with this email already exists';
        logError("Duplicate email attempt: $email");
        echo json_encode([
            'success' => false,
            'message' => 'Validation errors occurred',
            'errors' => $errors
        ]);
        exit;
    }
    $stmt->close();
    
    // Insert hospital record
    $sql = "INSERT INTO hospitals (hospital_name, address, phone_number, email, specialization, available_days, available_timings) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare insert query: " . $conn->error);
    }
    
    $stmt->bind_param("sssssss", $hospitalName, $address, $phoneNumber, $email, $specialization, $availableDays, $availableTimings);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert hospital record: " . $stmt->error);
    }
    
    $hospitalId = $conn->insert_id;
    $stmt->close();
    $conn->close();
    
    logSuccess("Hospital registered successfully. ID: $hospitalId, Name: $hospitalName");
    
    // Send confirmation email (non-blocking)
    $emailSent = false;
    $emailError = '';
    
    try {
        if (file_exists('../config/email.php')) {
            require_once '../config/email.php';
            
            if (function_exists('sendEmail')) {
                $subject = "Welcome to Hospital Booking System - Registration Successful";
                $emailBody = "
                <html>
                <head>
                    <title>Hospital Registration Confirmation</title>
                </head>
                <body style='font-family: Arial, sans-serif; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                        <h2 style='color: #667eea; text-align: center;'>Welcome to Hospital Booking System!</h2>
                        <p>Dear $hospitalName Team,</p>
                        <p>Congratulations! Your hospital has been successfully registered in our Hospital Booking System.</p>
                        
                        <h3>Your Hospital Details:</h3>
                        <ul>
                            <li><strong>Hospital Name:</strong> $hospitalName</li>
                            <li><strong>Address:</strong> $address</li>
                            <li><strong>Phone:</strong> $phoneNumber</li>
                            <li><strong>Email:</strong> $email</li>
                            <li><strong>Specialization:</strong> $specialization</li>
                            <li><strong>Available Days:</strong> $availableDays</li>
                            <li><strong>Available Timings:</strong> $availableTimings</li>
                        </ul>
                        
                        <p>Patients can now book appointments at your hospital through our platform. You will receive appointment notifications via WhatsApp and email.</p>
                        
                        <p>For any questions or support, please contact our admin team.</p>
                        
                        <p style='text-align: center; margin-top: 30px;'>
                            <strong>Best regards,<br>
                            Hospital Booking System Team</strong>
                        </p>
                    </div>
                </body>
                </html>";
                
                $altBody = "Welcome to Hospital Booking System! Your hospital '$hospitalName' has been successfully registered. Patients can now book appointments through our platform.";
                
                $emailSent = sendEmail($email, $subject, $emailBody, $altBody);
                if ($emailSent) {
                    logSuccess("Confirmation email sent to: $email");
                } else {
                    $emailError = "Email sending failed (check email configuration)";
                    logError("Email sending failed to: $email");
                }
            } else {
                $emailError = "Email function not available";
                logError("Email function not found in config/email.php");
            }
        } else {
            $emailError = "Email configuration file missing";
            logError("config/email.php not found");
        }
    } catch (Exception $e) {
        $emailError = $e->getMessage();
        logError("Email sending exception: " . $e->getMessage());
    }
    
    // Return success response
    $responseMessage = 'Hospital registered successfully!';
    if (!$emailSent && !empty($emailError)) {
        $responseMessage .= ' (Note: ' . $emailError . ')';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'hospital_id' => $hospitalId,
        'email_sent' => $emailSent,
        'email_status' => $emailSent ? 'sent' : 'failed'
    ]);
    
} catch (Exception $e) {
    logError("Registration failed: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage(),
        'debug_info' => 'Check registration_errors.log for details'
    ]);
}
?>
