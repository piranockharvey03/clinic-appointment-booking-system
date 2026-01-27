<?php
session_start();
require_once 'db-config.php';

// Only allow admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // Get unread notifications with appointment details
    $result = $conn->query("
        SELECT n.id, n.type, n.message, n.created_at, n.appointment_id,
               a.patient_name, a.appointment_date, a.appointment_time, a.department, a.doctor_name
        FROM notifications n
        LEFT JOIN appointments a ON n.appointment_id = a.appointment_id
        WHERE n.is_read = FALSE
        ORDER BY n.created_at DESC
        LIMIT 10
    ");

    $notifications = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'message' => $row['message'],
                'appointment_id' => $row['appointment_id'],
                'patient_name' => $row['patient_name'] ?? 'N/A',
                'appointment_date' => $row['appointment_date'] ?? '',
                'appointment_time' => $row['appointment_time'] ?? '',
                'department' => $row['department'] ?? '',
                'doctor_name' => $row['doctor_name'] ?? '',
                'time' => date('M j, Y g:i A', strtotime($row['created_at']))
            ];
        }
        $result->free();
    }

    closeDBConnection($conn);

    echo json_encode(['success' => true, 'notifications' => $notifications]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch notifications']);
}
?>
