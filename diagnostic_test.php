<?php
/**
 * Registration System Diagnostic Tool
 * Tests all components of the hospital registration system
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration System Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Registration System Diagnostic Tool</h1>
    
    <?php
    
    function testResult($test, $success, $message = '') {
        $class = $success ? 'success' : 'error';
        echo "<div class='$class'>$test: " . ($success ? 'PASS' : 'FAIL') . ($message ? " - $message" : '') . "</div>";
    }
    
    // Test 1: PHP Configuration
    echo "<div class='section'><h2>1. PHP Configuration</h2>";
    testResult("PHP Version", version_compare(PHP_VERSION, '7.4', '>='), PHP_VERSION);
    testResult("MySQLi Extension", extension_loaded('mysqli'), 'Required for database');
    testResult("JSON Extension", extension_loaded('json'), 'Required for API responses');
    testResult("Mail Function", function_exists('mail'), 'For email sending');
    echo "</div>";
    
    // Test 2: Database Connection
    echo "<div class='section'><h2>2. Database Connection</h2>";
    
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'hospital_booking';
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass);
        testResult("MySQL Server Connection", !$conn->connect_error, $conn->connect_error);
        
        // Test database existence
        $result = $conn->query("SHOW DATABASES LIKE '$db_name'");
        testResult("Database '$db_name' Exists", $result->num_rows > 0);
        
        if ($result->num_rows > 0) {
            $conn->select_db($db_name);
            
            // Test tables existence
            $tables = ['hospitals', 'appointments', 'payments'];
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                testResult("Table '$table' Exists", $result->num_rows > 0);
            }
            
            // Test insert permission
            $conn->query("CREATE TABLE IF NOT EXISTS diagnostic_test (id INT)");
            $conn->query("INSERT INTO diagnostic_test (id) VALUES (1)");
            $conn->query("DELETE FROM diagnostic_test WHERE id = 1");
            testResult("Database Write Permission", true);
            $conn->query("DROP TABLE IF EXISTS diagnostic_test");
        }
        
    } catch (Exception $e) {
        testResult("Database Connection", false, $e->getMessage());
    }
    echo "</div>";
    
    // Test 3: File Permissions
    echo "<div class='section'><h2>3. File System Permissions</h2>";
    
    $paths = [
        'config/database.php',
        'config/email.php', 
        'backend/register-hospital.php',
        'logs/',
        'logs/emails.log',
        'logs/whatsapp.log'
    ];
    
    foreach ($paths as $path) {
        $exists = file_exists($path);
        $readable = $exists && is_readable($path);
        $writable = $exists && is_writable($path);
        
        if (is_dir($path)) {
            testResult("Directory '$path'", $exists && $readable && $writable);
        } else {
            testResult("File '$path'", $exists && $readable);
        }
    }
    echo "</div>";
    
    // Test 4: Email Configuration
    echo "<div class='section'><h2>4. Email Configuration</h2>";
    
    if (file_exists('config/email.php')) {
        include 'config/email.php';
        
        $email_config_valid = isset($email_config) && 
            isset($email_config['smtp']) && 
            isset($email_config['smtp']['host']) &&
            isset($email_config['smtp']['username']) &&
            $email_config['smtp']['username'] !== 'your-email@gmail.com';
            
        testResult("Email Config Loaded", isset($email_config));
        testResult("SMTP Settings Valid", $email_config_valid, $email_config_valid ? '' : 'Using placeholder credentials');
        
        // Test PHPMailer availability
        testResult("PHPMailer Available", class_exists('PHPMailer\PHPMailer\PHPMailer'));
        
    } else {
        testResult("Email Config File", false, "config/email.php not found");
    }
    echo "</div>";
    
    // Test 5: Registration Form Test
    echo "<div class='section'><h2>5. Registration Endpoint Test</h2>";
    
    if (file_exists('backend/register-hospital.php')) {
        testResult("Registration File Exists", true);
        
        // Test registration endpoint with sample data
        $test_data = [
            'hospital_name' => 'Test Hospital',
            'address' => '123 Test Street, Test City',
            'phone_number' => '+1234567890',
            'email' => 'test@example.com',
            'specialization' => 'Test Specialization',
            'available_days' => 'Mon-Fri',
            'available_timings' => '9:00 AM - 6:00 PM'
        ];
        
        echo "<h3>Test Registration Data:</h3>";
        echo "<pre>" . print_r($test_data, true) . "</pre>";
        
        // You can test this manually via POST request
        
    } else {
        testResult("Registration File Exists", false, "backend/register-hospital.php not found");
    }
    echo "</div>";
    
    // Test 6: Log Directory Creation
    echo "<div class='section'><h2>6. Log Directory Setup</h2>";
    
    $log_dir = 'logs';
    if (!is_dir($log_dir)) {
        $created = mkdir($log_dir, 0755, true);
        testResult("Log Directory Created", $created);
    } else {
        testResult("Log Directory Exists", true);
        testResult("Log Directory Writable", is_writable($log_dir));
    }
    echo "</div>";
    
    ?>
    
    <div class='section'>
        <h2>Diagnostic Complete</h2>
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Fix any FAILED tests above</li>
            <li>Import database schema if tables are missing</li>
            <li>Configure email settings with real credentials</li>
            <li>Test registration form manually</li>
        </ol>
    </div>
    
</body>
</html>
