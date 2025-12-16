<?php
// Get Hospital Details API
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $hospitalId = (int)$_GET['id'];
    
    if ($hospitalId > 0) {
        try {
            $sql = "SELECT id, hospital_name, address, phone_number, email, specialization, available_days, available_timings 
                    FROM hospitals 
                    WHERE id = ?";
            
            $stmt = executeQuery($conn, $sql, [$hospitalId], "i");
            $hospital = fetchOne($stmt);
            
            if ($hospital) {
                echo json_encode([
                    'success' => true,
                    'hospital' => $hospital
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Hospital not found'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching hospital details: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid hospital ID'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

$conn->close();
?>
