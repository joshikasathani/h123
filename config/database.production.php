<?php
/**
 * Production Database Configuration
 * 
 * This file contains production database settings.
 * Update these values for your production environment.
 * 
 * IMPORTANT: Keep this file secure and never expose credentials
 */

// Production Database Settings
$production_db_config = [
    'host' => 'localhost',           // Replace with your production database host
    'username' => 'your_db_user',    // Replace with your production database username
    'password' => 'your_db_password', // Replace with your production database password
    'database' => 'hospital_booking', // Replace with your production database name
    'port' => 3306,                  // MySQL port (usually 3306)
    'charset' => 'utf8mb4'
];

// Create production database connection
function getProductionConnection() {
    global $production_db_config;
    
    try {
        $conn = new mysqli(
            $production_db_config['host'],
            $production_db_config['username'],
            $production_db_config['password'],
            $production_db_config['database'],
            $production_db_config['port']
        );
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset
        $conn->set_charset($production_db_config['charset']);
        
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return null;
    }
}

// Production database helper functions (same as development)
function executeProductionQuery($conn, $sql, $params = [], $types = "") {
    try {
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt;
    } catch (Exception $e) {
        error_log("Query execution error: " . $e->getMessage());
        return false;
    }
}

function fetchProductionOne($stmt) {
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

function fetchProductionAll($stmt) {
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}
?>
