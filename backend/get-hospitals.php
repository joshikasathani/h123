<?php
/**
 * Get Hospitals API - FIXED VERSION
 * Enhanced with proper error handling and database integration
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/hospitals_errors.log');

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
    error_log($log_message, 3, __DIR__ . '/../logs/hospitals_errors.log');
}

function logSuccess($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] SUCCESS: $message" . PHP_EOL;
    error_log($log_message, 3, __DIR__ . '/../logs/hospitals_errors.log');
}

// Main logic
try {
    // Get database connection
    require_once __DIR__ . '/../config/database.php';
    
    if (!isset($conn)) {
        throw new Exception("Database connection failed: Connection not initialized");
    }
    
    // Fetch hospitals
    $sql = "SELECT id, hospital_name, address, phone_number, email, specialization, available_days, available_timings 
            FROM hospitals 
            ORDER BY hospital_name ASC";
    
    $stmt = executeQuery($conn, $sql);
    $hospitals = fetchAll($stmt);
    
    logSuccess("Fetched " . count($hospitals) . " hospitals");
    
    echo json_encode([
        'success' => true,
        'hospitals' => $hospitals,
        'count' => count($hospitals)
    ]);
    
        
} catch (Exception $e) {
    logError("Error fetching hospitals: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching hospitals: ' . $e->getMessage(),
        'debug_info' => 'Check hospitals_errors.log for details'
    ]);
}
?>
