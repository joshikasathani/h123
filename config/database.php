<?php
// Database Configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hospital_booking';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Function to execute prepared statements safely
function executeQuery($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute() === false) {
        die("Error executing statement: " . $stmt->error);
    }
    
    return $stmt;
}

// Function to fetch multiple rows
function fetchAll($stmt) {
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch single row
function fetchOne($stmt) {
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>
