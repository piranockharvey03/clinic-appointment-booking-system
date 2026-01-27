<?php
session_start();
require_once 'db-config.php';

// Only allow admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get notification IDs from request
$notificationIds = isset($_POST['notification_ids']) ? $_POST['notification_ids'] : [];

if (empty($notificationIds)) {
    echo json_encode(['success' => false, 'error' => 'No notification IDs provided']);
    exit;
}

// Validate that notification_ids is an array
if (!is_array($notificationIds)) {
    $notificationIds = [$notificationIds];
}

try {
    $conn = getDBConnection();

    // Prepare the statement to mark notifications as read
    $placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = TRUE, read_at = NOW()
        WHERE id IN ($placeholders) AND is_read = FALSE
    ");

    // Bind parameters
    $types = str_repeat('i', count($notificationIds));
    $stmt->bind_param($types, ...$notificationIds);

    $success = $stmt->execute();

    if ($success) {
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        // Get the count of remaining unread notifications
        $countResult = $conn->query("SELECT COUNT(*) as unread_count FROM notifications WHERE is_read = FALSE");
        $unreadCount = 0;
        if ($countResult) {
            $countRow = $countResult->fetch_assoc();
            $unreadCount = $countRow['unread_count'];
            $countResult->free();
        }

        closeDBConnection($conn);

        echo json_encode([
            'success' => true,
            'marked_count' => $affectedRows,
            'unread_count' => $unreadCount
        ]);
    } else {
        throw new Exception("Failed to mark notifications as read: " . $stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to mark notifications as read']);
}
?>
