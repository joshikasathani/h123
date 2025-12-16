<?php
// Get All Appointments API
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Build query with filters
    $sql = "SELECT a.id, a.hospital_id, a.patient_name, a.phone_number, a.appointment_date, a.appointment_time, a.status, a.treatment_status,
            h.hospital_name,
            CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END as payment_processed
            FROM appointments a
            JOIN hospitals h ON a.hospital_id = h.id
            LEFT JOIN payments p ON a.id = p.appointment_id";
    
    $params = [];
    $types = "";
    $whereClauses = [];
    
    // Add filters if provided
    if (isset($_GET['hospital_id']) && !empty($_GET['hospital_id'])) {
        $whereClauses[] = "a.hospital_id = ?";
        $params[] = (int)$_GET['hospital_id'];
        $types .= "i";
    }
    
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $whereClauses[] = "a.status = ?";
        $params[] = $_GET['status'];
        $types .= "s";
    }
    
    if (isset($_GET['treatment_status']) && !empty($_GET['treatment_status'])) {
        $whereClauses[] = "a.treatment_status = ?";
        $params[] = $_GET['treatment_status'];
        $types .= "s";
    }
    
    // Add WHERE clause if there are filters
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }
    
    // Order by date and time
    $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    
    if (!empty($params)) {
        $stmt = executeQuery($conn, $sql, $params, $types);
    } else {
        $stmt = executeQuery($conn, $sql);
    }
    
    $appointments = fetchAll($stmt);
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments,
        'count' => count($appointments)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching appointments: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
