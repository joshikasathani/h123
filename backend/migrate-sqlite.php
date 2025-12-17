<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/database.php';

    if (!isset($conn)) {
        throw new Exception('Database connection failed');
    }

    $stmt = executeQuery($conn, "SELECT COUNT(*) AS c FROM hospitals");
    $row = fetchOne($stmt);
    $count = (int)($row['c'] ?? 0);

    $seeded = 0;
    if ($count === 0) {
        $sql = "INSERT INTO hospitals (hospital_name, address, phone_number, email, specialization, available_days, available_timings) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $rows = [
            ['City General Hospital', '123 Main Street, City Center', '+1-234-567-8900', 'info@citygeneral.com', 'General Medicine', 'Mon-Fri', '9:00 AM - 6:00 PM'],
            ['Heart Care Center', '456 Medical Avenue', '+1-234-567-8901', 'contact@heartcare.com', 'Cardiology', 'Mon-Sat', '8:00 AM - 8:00 PM'],
            ["Children's Hospital", '789 Kids Lane', '+1-234-567-8902', 'hello@childshospital.com', 'Pediatrics', 'Mon-Fri', '10:00 AM - 7:00 PM'],
            ['Orthopedic & Sports Medicine', '321 Sports Blvd', '+1-234-567-8903', 'appointments@orthosports.com', 'Orthopedics', 'Mon-Sat', '9:00 AM - 5:00 PM']
        ];

        foreach ($rows as $r) {
            executeQuery($conn, $sql, $r);
            $seeded++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $count === 0 ? 'SQLite migrated and seeded' : 'SQLite migrated (no seed needed)',
        'hospitals_existing' => $count,
        'hospitals_seeded' => $seeded,
        'db_path' => $db_config['database_path'] ?? null
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Migration failed: ' . $e->getMessage()
    ]);
}
