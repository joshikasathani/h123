<?php
/**
 * Database Configuration - SQLITE VERSION
 * Enhanced for Vercel deployment with SQLite
 */

// Database Configuration
$default_db_path = __DIR__ . '/../data/hospital_booking.db';
$env_db_path = getenv('SQLITE_DB_PATH');
$db_path = $env_db_path ? $env_db_path : $default_db_path;

// If the selected folder isn't writable (common on serverless), fall back to /tmp.
$db_dir = dirname($db_path);
if (!is_dir($db_dir) || !is_writable($db_dir)) {
    $db_path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'hospital_booking.db';
}

$db_config = [
    'type' => 'sqlite',
    'database_path' => $db_path,
    'charset' => 'utf8'
];

// Create data directory if it doesn't exist
$data_dir = dirname($db_config['database_path']);
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}

// Create connection with error handling
try {
    $conn = new PDO("sqlite:" . $db_config['database_path']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign keys
    $conn->exec("PRAGMA foreign_keys = ON");
    
    // Create tables if they don't exist
    createTables($conn);
    
} catch (PDOException $e) {
    error_log("Database initialization error: " . $e->getMessage());
    
    // For development, show error. For production, return null
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        die("Database Error: " . $e->getMessage());
    } else {
        die("Database connection failed. Please contact administrator.");
    }
}

// Create tables function
function createTables($conn) {
    try {
        // Helper: add missing columns for existing DBs (non-destructive migrations)
        $ensureColumn = function($table, $column, $definition) use ($conn) {
            $cols = $conn->query("PRAGMA table_info($table)")->fetchAll();
            foreach ($cols as $c) {
                if (isset($c['name']) && $c['name'] === $column) {
                    return;
                }
            }
            $conn->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        };

        // Hospitals table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS hospitals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                hospital_name TEXT NOT NULL,
                address TEXT NOT NULL,
                phone_number TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                specialization TEXT NOT NULL,
                available_days TEXT NOT NULL,
                available_timings TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Ensure updated_at exists for older DBs
        $ensureColumn('hospitals', 'updated_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');
        
        // Appointments table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS appointments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                hospital_id INTEGER NOT NULL,
                patient_name TEXT NOT NULL,
                phone_number TEXT NOT NULL,
                appointment_date DATE NOT NULL,
                appointment_time TIME NOT NULL,
                status TEXT DEFAULT 'pending',
                treatment_status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE CASCADE
            )
        ");

        // If DB was created with patient_phone, keep it but also add phone_number and backfill.
        try {
            $ensureColumn('appointments', 'phone_number', 'TEXT');
            $conn->exec("UPDATE appointments SET phone_number = patient_phone WHERE phone_number IS NULL AND patient_phone IS NOT NULL");
        } catch (Exception $e) {
            // ignore
        }

        $ensureColumn('appointments', 'treatment_status', "TEXT DEFAULT 'pending'");
        $ensureColumn('appointments', 'updated_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');
        
        // Payments table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                appointment_id INTEGER NOT NULL,
                hospital_id INTEGER NOT NULL,
                patient_name TEXT NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                admin_amount DECIMAL(10,2) NOT NULL,
                hospital_amount DECIMAL(10,2) NOT NULL,
                admin_percentage DECIMAL(5,2) DEFAULT 10.00,
                hospital_percentage DECIMAL(5,2) DEFAULT 90.00,
                payment_method TEXT DEFAULT 'cash',
                payment_status TEXT DEFAULT 'pending',
                payment_date DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE,
                FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE CASCADE
            )
        ");

        // Ensure key columns exist for older DBs
        $ensureColumn('payments', 'hospital_id', 'INTEGER');
        $ensureColumn('payments', 'patient_name', 'TEXT');
        $ensureColumn('payments', 'total_amount', 'DECIMAL(10,2)');
        $ensureColumn('payments', 'admin_amount', 'DECIMAL(10,2)');
        $ensureColumn('payments', 'hospital_amount', 'DECIMAL(10,2)');
        $ensureColumn('payments', 'admin_percentage', 'DECIMAL(5,2) DEFAULT 10.00');
        $ensureColumn('payments', 'hospital_percentage', 'DECIMAL(5,2) DEFAULT 90.00');
        $ensureColumn('payments', 'payment_method', "TEXT DEFAULT 'cash'");
        $ensureColumn('payments', 'payment_status', "TEXT DEFAULT 'pending'");
        $ensureColumn('payments', 'payment_date', 'DATE');
        $ensureColumn('payments', 'updated_at', 'DATETIME DEFAULT CURRENT_TIMESTAMP');
        
        // Admin revenue table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS admin_revenue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                appointment_id INTEGER NOT NULL,
                commission_amount DECIMAL(10,2) NOT NULL,
                payment_date DATE,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE
            )
        ");
        
        // Hospital revenue table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS hospital_revenue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                hospital_id INTEGER NOT NULL,
                appointment_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_date DATE,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (hospital_id) REFERENCES hospitals (id) ON DELETE CASCADE,
                FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE
            )
        ");
        
        // Bills table
        $conn->exec("
            CREATE TABLE IF NOT EXISTS bills (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                appointment_id INTEGER NOT NULL,
                patient_name TEXT NOT NULL,
                hospital_name TEXT NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                consultation_fee DECIMAL(10,2) NOT NULL,
                medicine_cost DECIMAL(10,2) DEFAULT 0,
                other_charges DECIMAL(10,2) DEFAULT 0,
                payment_status TEXT DEFAULT 'pending',
                generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE
            )
        ");
        
        error_log("SQLite database tables created/verified successfully");
        
    } catch (PDOException $e) {
        throw new Exception("Failed to create tables: " . $e->getMessage());
    }
}

// Database functions
function executeQuery($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        throw new Exception("Query execution failed: " . $e->getMessage());
    }
}

function fetchAll($stmt) {
    return $stmt->fetchAll();
}

function fetchOne($stmt) {
    return $stmt->fetch();
}

function lastInsertId($conn) {
    return $conn->lastInsertId();
}

?>
