<?php
// Get patient notifications by appointment IDs
require_once 'db-config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // Get all unread patient notifications (in production, you'd filter by patient_id or appointment IDs)
    // For demo purposes, we'll get all unread notifications
    $result = $conn->query("
        SELECT id, notification_type, message, created_at
        FROM patient_notifications
        WHERE is_read = FALSE
        ORDER BY created_at DESC
        LIMIT 10
    ");

    $notifications = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'type' => $row['notification_type'],
                'message' => $row['message'],
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
