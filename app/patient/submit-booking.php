<?php
// Booking handler that stores appointments in database
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Check if user is logged in as patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../../public/login.html');
    exit;
}

// Ensure request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/patient-book.html');
    exit;
}

// Collect and sanitize inputs
function get_post($key)
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

$department = get_post('department');
$doctorId   = get_post('doctorId');
$date       = get_post('date');
$time       = get_post('time');
$reason     = get_post('reason');
$notes      = get_post('notes');
$phone      = get_post('phone');

// Basic validation
if (empty($department)) {
    header('Location: ../../public/patient-book.html?error=' . urlencode('Please select a department'));
    exit;
}

if (empty($doctorId)) {
    header('Location: ../../public/patient-book.html?error=' . urlencode('Please select a doctor'));
    exit;
}

if (empty($date)) {
    header('Location: ../../public/patient-book.html?error=' . urlencode('Please select an appointment date'));
    exit;
}

if (empty($time)) {
    header('Location: ../../public/patient-book.html?error=' . urlencode('Please select an appointment time'));
    exit;
}

// Validate date is not in the past
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    header('Location: ../../public/patient-book.html?error=' . urlencode('Please select a future date for your appointment'));
    exit;
}

// Validate phone if provided
if (empty($phone)) {
    header('Location: ../../public/patient-book.html?error=' . urlencode('Please provide your phone number'));
    exit;
}

// Look up doctor from database using the numeric doctor ID
$doctor = null;
try {
    $conn = getDBConnection();
    $docStmt = $conn->prepare("SELECT id, full_name, specialty, photo FROM doctors WHERE id = ? AND status = 'active'");
    $docStmt->bind_param("i", $doctorId);
    $docStmt->execute();
    $docResult = $docStmt->get_result();
    if ($docRow = $docResult->fetch_assoc()) {
        $doctor = [
            'name'      => $docRow['full_name'],
            'specialty' => $docRow['specialty'],
            'photo'     => $docRow['photo'] ?? ''
        ];
    }
    $docStmt->close();
    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Doctor lookup error: " . $e->getMessage());
}

if (!$doctor) {
    header('Location: ../../public/patient-book.html?error=' . urlencode('Selected doctor is not available. Please choose another doctor.'));
    exit;
}

// Generate unique appointment ID
$appointmentId = uniqid('appt_', true);

// Get patient information from session (already verified above)
$patientId = $_SESSION['user_id'];
$patientName = $_SESSION['user_name'];

try {
    // Get database connection
    $conn = getDBConnection();

    // Prepare SQL statement to insert appointment
    $stmt = $conn->prepare("
        INSERT INTO appointments 
        (appointment_id, patient_id, patient_name, phone, department, doctor_id, 
         doctor_name, doctor_specialty, doctor_photo, appointment_date, appointment_time, 
         reason, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->bind_param(
        "sisssssssssss",
        $appointmentId,
        $patientId,
        $patientName,
        $phone,
        $department,
        $doctorId,
        $doctor['name'],
        $doctor['specialty'],
        $doctor['photo'],
        $date,
        $time,
        $reason,
        $notes
    );

    // Execute the statement
    if ($stmt->execute()) {
        // Create a notification for the admin
        $notificationMessage = "New appointment booked with " . $doctor['name'] . " on " . $date . " at " . $time;
        $notificationStmt = $conn->prepare("
            INSERT INTO notifications 
            (type, message, appointment_id) 
            VALUES ('new_appointment', ?, ?)
        ");
        $notificationStmt->bind_param("ss", $notificationMessage, $appointmentId);
        $notificationStmt->execute();
        $notificationStmt->close();

        $stmt->close();
        closeDBConnection($conn);
        // Redirect to the appointments page with success message
        header('Location: patient-appointments.php?success=' . urlencode('Appointment booked successfully'));
        exit;
    } else {
        throw new Exception("Failed to create appointment. Please try again.");
    }
} catch (Exception $e) {
    error_log("Appointment booking error: " . $e->getMessage());
    // Redirect back to booking page with specific error
    $errorMsg = "Unable to book appointment. Please try again or contact support.";
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        $errorMsg = "This appointment time is already taken. Please select a different time.";
    }
    header('Location: ../../public/patient-book.html?error=' . urlencode($errorMsg));
    exit;
}
