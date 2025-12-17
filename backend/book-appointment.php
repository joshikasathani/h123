<?php
/**
 * Book Appointment Backend - FIXED VERSION
 * Enhanced with proper error handling, security, and database integration
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/appointment_errors.log');

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
    error_log($log_message, 3, __DIR__ . '/../logs/appointment_errors.log');
}

function logSuccess($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] SUCCESS: $message" . PHP_EOL;
    error_log($log_message, 3, __DIR__ . '/../logs/appointment_errors.log');
}

// Main booking logic
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method. Only POST allowed.");
    }
    
    // Get and sanitize form data
    $hospitalId = filter_input(INPUT_POST, 'hospital_id', FILTER_VALIDATE_INT);
    $patientName = trim($_POST['patient_name'] ?? '');
    $patientPhone = trim($_POST['patient_phone'] ?? '');
    $appointmentDate = trim($_POST['appointment_date'] ?? '');
    $appointmentTime = trim($_POST['appointment_time'] ?? '');
    
    logSuccess("Appointment booking attempt: Hospital ID $hospitalId, Patient: $patientName");
    
    // Validate required fields
    $errors = [];
    
    if (!$hospitalId || $hospitalId <= 0) {
        $errors[] = 'Please select a valid hospital';
    }
    
    if (empty($patientName)) {
        $errors[] = 'Patient name is required';
    } elseif (strlen($patientName) < 3) {
        $errors[] = 'Patient name must be at least 3 characters';
    }
    
    if (empty($patientPhone)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[\d\s\-\+\(\)]+$/', $patientPhone)) {
        $errors[] = 'Please enter a valid phone number';
    } elseif (strlen(preg_replace('/\D/', '', $patientPhone)) < 10) {
        $errors[] = 'Phone number must be at least 10 digits';
    }
    
    if (empty($appointmentDate)) {
        $errors[] = 'Appointment date is required';
    } else {
        $selectedDate = new DateTime($appointmentDate);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($selectedDate < $today) {
            $errors[] = 'Appointment date cannot be in the past';
        }
    }
    
    if (empty($appointmentTime)) {
        $errors[] = 'Appointment time is required';
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
    require_once '../config/database.php';
    
    if (!isset($conn)) {
        throw new Exception("Database connection failed: Connection not initialized");
    }
    
    // Check if hospital exists
    $stmt = executeQuery($conn, "SELECT hospital_name, phone_number, email FROM hospitals WHERE id = ?", [$hospitalId]);
    $hospital = fetchOne($stmt);
    
    if (!$hospital) {
        $errors[] = 'Selected hospital not found';
        logError("Hospital not found: ID $hospitalId");
        echo json_encode([
            'success' => false,
            'message' => 'Validation errors occurred',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Insert appointment
    $sql = "INSERT INTO appointments (hospital_id, patient_name, patient_phone, appointment_date, appointment_time) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = executeQuery($conn, $sql, [$hospitalId, $patientName, $patientPhone, $appointmentDate, $appointmentTime]);
    
    $appointmentId = lastInsertId($conn);
    
    logSuccess("Appointment booked successfully. ID: $appointmentId, Hospital: {$hospital['hospital_name']}");
    
    // Send WhatsApp messages (non-blocking)
    $whatsappSent = false;
    $whatsappError = '';
    
    try {
        if (file_exists('../config/whatsapp.php')) {
            require_once '../config/whatsapp.php';
            
            if (function_exists('sendWhatsAppMessage')) {
                // WhatsApp message to patient
                $patientMessage = "Hello $patientName,\n\n" .
                    "Your appointment at {$hospital['hospital_name']} is confirmed.\n\n" .
                    "📅 Date: $appointmentDate\n" .
                    "⏰ Time: $appointmentTime\n\n" .
                    "Thank you for using our platform.";
                
                $patientResult = sendWhatsAppMessage($patientPhone, $patientMessage);
                
                // WhatsApp message to hospital
                $hospitalMessage = "New Appointment Booked!\n\n" .
                    "Hospital: {$hospital['hospital_name']}\n" .
                    "Patient: $patientName\n" .
                    "Phone: $patientPhone\n" .
                    "Date: $appointmentDate\n" .
                    "Time: $appointmentTime";
                
                $hospitalResult = sendWhatsAppMessage($hospital['phone_number'], $hospitalMessage);
                
                $whatsappSent = $patientResult || $hospitalResult;
                
                if (!$whatsappSent) {
                    $whatsappError = "WhatsApp notifications failed";
                    logError("WhatsApp sending failed for appointment ID $appointmentId");
                }
            } else {
                $whatsappError = "WhatsApp function not available";
                logError("WhatsApp function not found in config/whatsapp.php");
            }
        } else {
            $whatsappError = "WhatsApp configuration file missing";
            logError("config/whatsapp.php not found");
        }
    } catch (Exception $e) {
        $whatsappError = $e->getMessage();
        logError("WhatsApp exception: " . $e->getMessage());
    }
    
    // Return success response
    $responseMessage = 'Appointment booked successfully!';
    if (!$whatsappSent && !empty($whatsappError)) {
        $responseMessage .= ' (Note: ' . $whatsappError . ')';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'appointment_id' => $appointmentId,
        'hospital_name' => $hospital['hospital_name'],
        'whatsapp_sent' => $whatsappSent,
        'whatsapp_status' => $whatsappSent ? 'sent' : 'failed'
    ]);
    
} catch (Exception $e) {
    logError("Appointment booking failed: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Booking failed: ' . $e->getMessage(),
        'debug_info' => 'Check appointment_errors.log for details'
    ]);
}
?>
