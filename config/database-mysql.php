<?php
/**
 * Database Configuration - FIXED VERSION
 * Enhanced with proper error handling and connection management
 */

// Database Configuration
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'hospital_booking',
    'charset' => 'utf8mb4'
];

// Create connection with error handling
try {
    $conn = new mysqli($db_config['host'], $db_config['username'], $db_config['password']);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset($db_config['charset']);
    
    // Check if database exists, create if not
    $result = $conn->query("SHOW DATABASES LIKE '" . $db_config['database'] . "'");
    if ($result->num_rows === 0) {
        // Database doesn't exist, create it
        if (!$conn->query("CREATE DATABASE `" . $db_config['database'] . "`")) {
            throw new Exception("Failed to create database: " . $conn->error);
        }
        error_log("Database '" . $db_config['database'] . "' created successfully");
    }
    
    // Select database
    if (!$conn->select_db($db_config['database'])) {
        throw new Exception("Failed to select database: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    
    // For development, show error. For production, return null
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        die("Database Error: " . $e->getMessage());
    } else {
        die("Database connection failed. Please contact administrator.");
    }
}

/**
 * Execute prepared query safely
 */
function executeQuery($conn, $sql, $params = [], $types = "") {
    try {
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute() === false) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        return $stmt;
        
    } catch (Exception $e) {
        error_log("Query execution error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Fetch multiple rows
 */
function fetchAll($stmt) {
    try {
        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception("Error getting result set: " . $stmt->error);
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Fetch all error: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch single row
 */
function fetchOne($stmt) {
    try {
        $result = $stmt->get_result();
        if ($result === false) {
            throw new Exception("Error getting result set: " . $stmt->error);
        }
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Fetch one error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if table exists
 */
function tableExists($conn, $tableName) {
    try {
        $result = $conn->query("SHOW TABLES LIKE '" . $tableName . "'");
        return $result->num_rows > 0;
    } catch (Exception $e) {
        error_log("Table check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create tables if they don't exist
 */
function initializeTables($conn) {
    $tables = [
        "hospitals" => "
            CREATE TABLE IF NOT EXISTS hospitals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                hospital_name VARCHAR(255) NOT NULL,
                address TEXT NOT NULL,
                phone_number VARCHAR(20) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                specialization VARCHAR(255) NOT NULL,
                available_days VARCHAR(255) NOT NULL,
                available_timings VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
        
        "appointments" => "
            CREATE TABLE IF NOT EXISTS appointments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                hospital_id INT NOT NULL,
                patient_name VARCHAR(255) NOT NULL,
                phone_number VARCHAR(20) NOT NULL,
                appointment_date DATE NOT NULL,
                appointment_time TIME NOT NULL,
                status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
                treatment_status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
            )",
        
        "payments" => "
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                appointment_id INT NOT NULL,
                hospital_id INT NOT NULL,
                patient_name VARCHAR(255) NOT NULL,
                total_amount DECIMAL(10, 2) NOT NULL,
                admin_amount DECIMAL(10, 2) NOT NULL,
                hospital_amount DECIMAL(10, 2) NOT NULL,
                admin_percentage DECIMAL(5, 2) DEFAULT 10.00,
                hospital_percentage DECIMAL(5, 2) DEFAULT 90.00,
                payment_method VARCHAR(50) DEFAULT 'cash',
                payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
                payment_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
                FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
            )"
    ];
    
    foreach ($tables as $tableName => $sql) {
        if (!tableExists($conn, $tableName)) {
            if (!$conn->query($sql)) {
                error_log("Failed to create table '$tableName': " . $conn->error);
            } else {
                error_log("Table '$tableName' created successfully");
            }
        }
    }
}

// Initialize tables if needed
initializeTables($conn);

?>
