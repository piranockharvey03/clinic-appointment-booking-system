<?php
// Simple booking handler that stores appointments in data/appointments.json

// Ensure request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: patient-book.html');
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
    header('Location: patient-book.html');
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

// Build appointment record
$appointment = [
    'id' => uniqid('appt_', true),
    'createdAt' => date('c'),
    'department' => $department,
    'doctorId' => $doctorId,
    'doctorName' => $doctor['name'],
    'doctorSpecialty' => $doctor['specialty'],
    'doctorPhoto' => $doctor['photo'],
    'date' => $date,
    'time' => $time,
    'reason' => $reason,
    'notes' => $notes,
    'phone' => $phone,
    // In a real app, bind to logged-in patient id. For demo, static placeholder.
    'patientName' => 'John Doe',
    // Default status is pending until admin approval
    'status' => 'pending'
];

// Ensure data directory exists
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$dataFile = $dataDir . DIRECTORY_SEPARATOR . 'appointments.json';
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

// Read, append, and write atomically
$appointments = [];
$raw = file_get_contents($dataFile);
if ($raw !== false && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $appointments = $decoded;
    }
}

$appointments[] = $appointment;

// Write with locking
$fp = fopen($dataFile, 'c+');
if ($fp) {
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($appointments, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

// Redirect to the dynamic appointments page
header('Location: patient-appointments.php');
exit;
