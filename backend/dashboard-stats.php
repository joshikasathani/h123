<?php
// Dashboard Statistics API
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if (!isset($conn)) {
            throw new Exception('Database connection failed');
        }
        
        // Get total hospitals
        $sql = "SELECT COUNT(*) as total FROM hospitals";
        $stmt = executeQuery($conn, $sql);
        $result = fetchOne($stmt);
        $totalHospitals = $result['total'] ?? 0;
        
        // Get total appointments
        $sql = "SELECT COUNT(*) as total FROM appointments";
        $stmt = executeQuery($conn, $sql);
        $result = fetchOne($stmt);
        $totalAppointments = $result['total'] ?? 0;
        
        // Get today's appointments
        $sql = "SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = DATE('now')";
        $stmt = executeQuery($conn, $sql);
        $result = fetchOne($stmt);
        $todayAppointments = $result['total'] ?? 0;
        
        // Get completed appointments (treatment completed)
        $sql = "SELECT COUNT(*) as total FROM appointments WHERE treatment_status = 'completed'";
        $stmt = executeQuery($conn, $sql);
        $result = fetchOne($stmt);
        $completedAppointments = $result['total'] ?? 0;
        
        // Get pending appointments
        $sql = "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'";
        $stmt = executeQuery($conn, $sql);
        $result = fetchOne($stmt);
        $pendingAppointments = $result['total'] ?? 0;
        
        // Get total revenue from payments
        $sql = "SELECT SUM(total_amount) as total FROM payments WHERE payment_status = 'paid'";
        $stmt = executeQuery($conn, $sql);
        $result = fetchOne($stmt);
        $totalRevenue = $result['total'] ?? 0;
        
        // Get hospital performance data
        $sql = "SELECT h.hospital_name, COUNT(a.id) as appointment_count 
                FROM hospitals h 
                LEFT JOIN appointments a ON h.id = a.hospital_id 
                GROUP BY h.id, h.hospital_name 
                ORDER BY appointment_count DESC 
                LIMIT 10";
        $stmt = executeQuery($conn, $sql);
        $hospitalPerformance = fetchAll($stmt);
        
        // Get weekly appointments data
        $sql = "SELECT DATE(appointment_date) as date, COUNT(*) as count 
                FROM appointments 
                WHERE DATE(appointment_date) >= DATE('now','-7 day')
                GROUP BY DATE(appointment_date) 
                ORDER BY date";
        $stmt = executeQuery($conn, $sql);
        $weeklyAppointments = fetchAll($stmt);
        
        // Get recent activities
        $sql = "SELECT a.patient_name, h.hospital_name, a.appointment_date, a.appointment_time, a.status, a.created_at
                FROM appointments a
                JOIN hospitals h ON a.hospital_id = h.id
                ORDER BY a.created_at DESC
                LIMIT 10";
        $stmt = executeQuery($conn, $sql);
        $recentActivities = fetchAll($stmt);
        
        // Format recent activities
        $formattedActivities = [];
        foreach ($recentActivities as $activity) {
            $formattedActivities[] = [
                'text' => "{$activity['patient_name']} booked appointment at {$activity['hospital_name']}",
                'time' => date('M j, Y H:i', strtotime($activity['created_at'])),
                'status' => $activity['status']
            ];
        }
        
        // Prepare response
        $response = [
            'success' => true,
            'data' => [
                'totalHospitals' => $totalHospitals,
                'totalAppointments' => $totalAppointments,
                'todayAppointments' => $todayAppointments,
                'completedAppointments' => $completedAppointments,
                'pendingAppointments' => $pendingAppointments,
                'totalRevenue' => '$' . number_format($totalRevenue, 2),
                'hospitalPerformance' => $hospitalPerformance,
                'weeklyAppointments' => $weeklyAppointments,
                'recentActivities' => $formattedActivities,
                'lastUpdated' => date('Y-m-d H:i:s')
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching dashboard data: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
