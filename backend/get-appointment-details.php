<?php
// Get Appointment Details API
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $appointmentId = (int)$_GET['id'];
    
    if ($appointmentId > 0) {
        try {
            $sql = "SELECT a.id, a.hospital_id, a.patient_name, a.phone_number, a.appointment_date, a.appointment_time, a.status, a.treatment_status,
                    h.hospital_name, h.phone_number as hospital_phone
                    FROM appointments a
                    JOIN hospitals h ON a.hospital_id = h.id
                    WHERE a.id = ?";
            
            $stmt = executeQuery($conn, $sql, [$appointmentId], "i");
            $appointment = fetchOne($stmt);
            
            if ($appointment) {
                echo json_encode([
                    'success' => true,
                    'appointment' => $appointment
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Appointment not found'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching appointment details: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid appointment ID'
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
