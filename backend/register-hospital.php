<?php
// Hospital Registration Backend
require_once '../config/database.php';
require_once '../config/email.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $hospitalName = trim($_POST['hospital_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $availableDays = trim($_POST['available_days'] ?? '');
    $availableTimings = trim($_POST['available_timings'] ?? '');
    
    // Validate required fields
    $errors = [];
    
    if (empty($hospitalName)) {
        $errors[] = 'Hospital name is required';
    } elseif (strlen($hospitalName) < 3) {
        $errors[] = 'Hospital name must be at least 3 characters';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    } elseif (strlen($address) < 10) {
        $errors[] = 'Address must be at least 10 characters';
    }
    
    if (empty($phoneNumber)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[\d\s\-\+\(\)]+$/', $phoneNumber)) {
        $errors[] = 'Please enter a valid phone number';
    } elseif (strlen($phoneNumber) < 10) {
        $errors[] = 'Phone number must be at least 10 digits';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($specialization)) {
        $errors[] = 'Specialization is required';
    } elseif (strlen($specialization) < 3) {
        $errors[] = 'Specialization must be at least 3 characters';
    }
    
    if (empty($availableDays)) {
        $errors[] = 'Available days are required';
    }
    
    if (empty($availableTimings)) {
        $errors[] = 'Available timings are required';
    }
    
    // Check for duplicate email
    if (empty($errors)) {
        $stmt = executeQuery($conn, "SELECT id FROM hospitals WHERE email = ?", [$email], "s");
        $result = fetchOne($stmt);
        if ($result) {
            $errors[] = 'A hospital with this email already exists';
        }
    }
    
    // If no errors, insert hospital
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO hospitals (hospital_name, address, phone_number, email, specialization, available_days, available_timings) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $params = [$hospitalName, $address, $phoneNumber, $email, $specialization, $availableDays, $availableTimings];
            $types = "sssssss";
            
            $stmt = executeQuery($conn, $sql, $params, $types);
            
            if ($stmt) {
                // Send confirmation email to hospital
                try {
                    $subject = "Welcome to Hospital Booking System - Registration Successful";
                    $emailBody = "
                    <html>
                    <head>
                        <title>Hospital Registration Confirmation</title>
                    </head>
                    <body style='font-family: Arial, sans-serif; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                            <h2 style='color: #667eea; text-align: center;'>Welcome to Hospital Booking System!</h2>
                            <p>Dear $hospitalName Team,</p>
                            <p>Congratulations! Your hospital has been successfully registered in our Hospital Booking System.</p>
                            
                            <h3>Your Hospital Details:</h3>
                            <ul>
                                <li><strong>Hospital Name:</strong> $hospitalName</li>
                                <li><strong>Address:</strong> $address</li>
                                <li><strong>Phone:</strong> $phoneNumber</li>
                                <li><strong>Email:</strong> $email</li>
                                <li><strong>Specialization:</strong> $specialization</li>
                                <li><strong>Available Days:</strong> $availableDays</li>
                                <li><strong>Available Timings:</strong> $availableTimings</li>
                            </ul>
                            
                            <p>Patients can now book appointments at your hospital through our platform. You will receive appointment notifications via WhatsApp and email.</p>
                            
                            <p>For any questions or support, please contact our admin team.</p>
                            
                            <p style='text-align: center; margin-top: 30px;'>
                                <strong>Best regards,<br>
                                Hospital Booking System Team</strong>
                            </p>
                        </div>
                    </body>
                    </html>";
                    
                    $altBody = "Welcome to Hospital Booking System! Your hospital '$hospitalName' has been successfully registered. Patients can now book appointments through our platform.";
                    
                    sendEmail($email, $subject, $emailBody, $altBody);
                } catch (Exception $e) {
                    error_log("Email sending error: " . $e->getMessage());
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Hospital registered successfully! Confirmation email sent.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to register hospital. Please try again.'
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
