<?php
// Get Hospitals API
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT id, hospital_name, address, phone_number, email, specialization, available_days, available_timings 
            FROM hospitals 
            ORDER BY hospital_name ASC";
    
    $stmt = executeQuery($conn, $sql);
    $hospitals = fetchAll($stmt);
    
    echo json_encode([
        'success' => true,
        'hospitals' => $hospitals,
        'count' => count($hospitals)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching hospitals: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
