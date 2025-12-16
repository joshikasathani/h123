<?php
// Update Treatment Status Backend
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    $treatmentStatus = trim($_POST['treatment_status'] ?? '');
    
    // Validate required fields
    $errors = [];
    
    if ($appointmentId <= 0) {
        $errors[] = 'Invalid appointment ID';
    }
    
    if (empty($treatmentStatus)) {
        $errors[] = 'Treatment status is required';
    } elseif (!in_array($treatmentStatus, ['pending', 'completed', 'cancelled'])) {
        $errors[] = 'Invalid treatment status';
    }
    
    // Check if appointment exists
    if (empty($errors)) {
        $stmt = executeQuery($conn, "SELECT id FROM appointments WHERE id = ?", [$appointmentId], "i");
        $appointment = fetchOne($stmt);
        
        if (!$appointment) {
            $errors[] = 'Appointment not found';
        }
    }
    
    // If no errors, update treatment status
    if (empty($errors)) {
        try {
            $sql = "UPDATE appointments SET treatment_status = ? WHERE id = ?";
            
            $params = [$treatmentStatus, $appointmentId];
            $types = "si";
            
            $stmt = executeQuery($conn, $sql, $params, $types);
            
            if ($stmt) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Treatment status updated successfully!',
                    'payment_url' => $treatmentStatus === 'completed' ? "payment.html?appointment_id=$appointmentId" : null
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update treatment status. Please try again.'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Validation errors occurred',
            'errors' => $errors
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?>
