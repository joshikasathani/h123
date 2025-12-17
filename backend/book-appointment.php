<?php
// Database connection
include "config/db.php";

// WhatsApp configuration
include "config/whatsapp.php";

// Check request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $hospital_id = $_POST['hospital_id'];
    $patient_name = $_POST['patient_name'];
    $patient_phone = $_POST['patient_phone'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];

    // Insert appointment
    $insertQuery = "
        INSERT INTO appointments 
        (hospital_id, patient_name, patient_phone, appointment_date, appointment_time)
        VALUES 
        ('$hospital_id', '$patient_name', '$patient_phone', '$appointment_date', '$appointment_time')
    ";

    $result = mysqli_query($conn, $insertQuery);

    if ($result) {

        // Fetch hospital details
        $hospitalQuery = mysqli_query(
            $conn,
            "SELECT name, phone FROM hospitals WHERE id = '$hospital_id'"
        );
        $hospital = mysqli_fetch_assoc($hospitalQuery);

        // WhatsApp message to patient
        $patientMessage =
            "Hello $patient_name,\n\n" .
            "Your appointment at {$hospital['name']} is confirmed.\n\n" .
            "📅 Date: $appointment_date\n" .
            "⏰ Time: $appointment_time\n\n" .
            "Thank you for using our platform.";

        // WhatsApp message to hospital/admin
        $hospitalMessage =
            "New Appointment Booked!\n\n" .
            "Hospital: {$hospital['name']}\n" .
            "Patient: $patient_name\n" .
            "Phone: $patient_phone\n" .
            "Date: $appointment_date\n" .
            "Time: $appointment_time";

        // Send WhatsApp messages (non-blocking)
        try {
            sendWhatsAppMessage($patient_phone, $patientMessage);
            sendWhatsAppMessage($hospital['phone'], $hospitalMessage);
        } catch (Exception $e) {
            error_log("WhatsApp Error: " . $e->getMessage());
        }

        echo "Appointment booked successfully";

    } else {
        echo "Failed to book appointment";
    }
}
?>
