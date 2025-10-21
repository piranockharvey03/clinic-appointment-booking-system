<?php
// Booking handler that stores appointments in database
session_start();
require_once 'db-config.php';

// Ensure request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/patient-book.html');
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
if ($department === '' || $doctorId === '' || $date === '' || $time === '') {
    // Missing required fields
    header('Location: ../html/patient-book.html');
    exit;
}

// Map doctorId to known metadata (kept in sync with patient-book.html)
$doctorDirectory = [
    'cardio-1' => ['name' => 'Dr. Sarah Johnson',  'specialty' => 'Cardiology',        'photo' => 'http://static.photos/people/200x200/2'],
    'gm-1'     => ['name' => 'Dr. Michael Chen',   'specialty' => 'General Medicine',  'photo' => 'http://static.photos/people/200x200/3'],
    'oph-1'    => ['name' => 'Dr. Elena Novak',    'specialty' => 'Ophthalmology',     'photo' => 'http://static.photos/people/200x200/5'],
    'ortho-1'  => ['name' => 'Dr. Rajesh Kumar',   'specialty' => 'Orthopedics',       'photo' => 'http://static.photos/people/200x200/6'],
    'ped-1'    => ['name' => 'Dr. Emily Carter',   'specialty' => 'Pediatrics',        'photo' => 'http://static.photos/people/200x200/7'],
    'derm-1'   => ['name' => 'Dr. Aisha Patel',    'specialty' => 'Dermatology',       'photo' => 'http://static.photos/people/200x200/4'],
];
$doctor = isset($doctorDirectory[$doctorId]) ? $doctorDirectory[$doctorId] : ['name' => 'Unknown', 'specialty' => $department, 'photo' => ''];

// Generate unique appointment ID
$appointmentId = uniqid('appt_', true);

// Get patient information from session (or use placeholder if not logged in)
$patientId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$patientName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'John Doe';

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
        // Redirect to the appointments page
        header('Location: patient-appointments.php');
        exit;
    } else {
        throw new Exception("Failed to create appointment: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Appointment booking error: " . $e->getMessage());
    // Redirect back to booking page with error
    header('Location: patient-book.html?error=1');
    exit;
}
