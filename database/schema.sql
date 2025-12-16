-- Hospital Appointment Booking System Database Schema

-- Create database (uncomment if needed)
-- CREATE DATABASE hospital_booking;
-- USE hospital_booking;

-- Hospitals table
CREATE TABLE IF NOT EXISTS hospitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    specialization VARCHAR(255) NOT NULL,
    available_days VARCHAR(255) NOT NULL,
    available_timings VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
);

-- Payments table for post-treatment billing
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
);

-- Update appointments table to add treatment status
ALTER TABLE appointments ADD COLUMN treatment_status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending' AFTER status;

-- Insert sample hospital data (optional)
INSERT INTO hospitals (hospital_name, address, phone_number, email, specialization, available_days, available_timings) VALUES
('City General Hospital', '123 Main Street, City Center', '+1-234-567-8900', 'info@citygeneral.com', 'General Medicine', 'Mon-Fri', '9:00 AM - 6:00 PM'),
('Heart Care Center', '456 Medical Avenue', '+1-234-567-8901', 'contact@heartcare.com', 'Cardiology', 'Mon-Sat', '8:00 AM - 8:00 PM'),
('Children\'s Hospital', '789 Kids Lane', '+1-234-567-8902', 'hello@childshospital.com', 'Pediatrics', 'Mon-Fri', '10:00 AM - 7:00 PM');
