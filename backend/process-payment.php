<?php
// Payment Processing Backend
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $appointmentId = (int)($_POST['appointment_id'] ?? 0);
    $hospitalId = (int)($_POST['hospital_id'] ?? 0);
    $patientName = trim($_POST['patient_name'] ?? '');
    $totalAmount = (float)($_POST['total_amount'] ?? 0);
    $paymentMethod = trim($_POST['payment_method'] ?? '');
    
    // Revenue split percentages
    $adminPercentage = 10.0;  // 10% to admin
    $hospitalPercentage = 90.0; // 90% to hospital
    
    // Calculate revenue split
    $adminAmount = $totalAmount * ($adminPercentage / 100);
    $hospitalAmount = $totalAmount * ($hospitalPercentage / 100);
    
    // Validate required fields
    $errors = [];
    
    if ($appointmentId <= 0) {
        $errors[] = 'Invalid appointment ID';
    }
    
    if ($hospitalId <= 0) {
        $errors[] = 'Invalid hospital ID';
    }
    
    if (empty($patientName)) {
        $errors[] = 'Patient name is required';
    }
    
    if ($totalAmount <= 0) {
        $errors[] = 'Total amount must be greater than 0';
    }
    
    if (empty($paymentMethod)) {
        $errors[] = 'Payment method is required';
    }
    
    // Check if appointment exists and treatment is completed
    if (empty($errors)) {
        $stmt = executeQuery($conn, "SELECT id, treatment_status FROM appointments WHERE id = ?", [$appointmentId], "i");
        $appointment = fetchOne($stmt);
        
        if (!$appointment) {
            $errors[] = 'Appointment not found';
        } elseif ($appointment['treatment_status'] !== 'completed') {
            $errors[] = 'Payment can only be processed for completed treatments';
        }
    }
    
    // Check if payment already exists for this appointment
    if (empty($errors)) {
        $stmt = executeQuery($conn, "SELECT id FROM payments WHERE appointment_id = ?", [$appointmentId], "i");
        $existingPayment = fetchOne($stmt);
        
        if ($existingPayment) {
            $errors[] = 'Payment has already been processed for this appointment';
        }
    }
    
    // If no errors, process payment
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO payments 
                    (appointment_id, hospital_id, patient_name, total_amount, admin_amount, hospital_amount, 
                     admin_percentage, hospital_percentage, payment_method, payment_status, payment_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', CURDATE())";
            
            $params = [
                $appointmentId, 
                $hospitalId, 
                $patientName, 
                $totalAmount, 
                $adminAmount, 
                $hospitalAmount, 
                $adminPercentage, 
                $hospitalPercentage, 
                $paymentMethod
            ];
            $types = "iisdddds";
            
            $stmt = executeQuery($conn, $sql, $params, $types);
            
            if ($stmt) {
                // Log payment details
                $paymentLog = date('Y-m-d H:i:s') . " - Payment processed: Appointment ID: $appointmentId, Total: $totalAmount, Admin: $adminAmount, Hospital: $hospitalAmount\n";
                file_put_contents(__DIR__ . '/../logs/payments.log', $paymentLog, FILE_APPEND | LOCK_EX);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment processed successfully! Revenue distributed: Hospital ($' . number_format($hospitalAmount, 2) . ') and Admin ($' . number_format($adminAmount, 2) . ')'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to process payment. Please try again.'
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
