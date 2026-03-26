<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Check if session is valid
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// Validate required params
if (!isset($_POST['message_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'message_id and status are required']);
    exit;
}

$validStatuses = ['sent', 'delivered', 'read'];
$status = $_POST['status'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    $userId = intval($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'];
    $messageId = intval($_POST['message_id']);

    // Get message and verify user is receiver
    $stmt = $conn->prepare("
        SELECT m.conversation_id, m.receiver_id, m.receiver_role, m.sender_id, m.sender_role
        FROM messages m
        WHERE m.message_id = ?
    ");
    $stmt->bind_param("i", $messageId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        closeDBConnection($conn);
        echo json_encode(['error' => 'message not found']);
        exit;
    }

    $msg = $result->fetch_assoc();
    $stmt->close();
    $result->free();

    // Only the receiver can update message status
    if (!($msg['receiver_id'] === $userId && $msg['receiver_role'] === $userRole)) {
        http_response_code(403);
        closeDBConnection($conn);
        echo json_encode(['error' => 'unauthorized to update this message']);
        exit;
    }

    // Update message status
    if ($status === 'read') {
        $stmt = $conn->prepare("
            UPDATE messages
            SET delivery_status = ?, is_read = 1, read_at = NOW(), delivered_at = NOW()
            WHERE message_id = ?
        ");
        $stmt->bind_param("si", $status, $messageId);
    } else {
        $stmt = $conn->prepare("
            UPDATE messages
            SET delivery_status = ?, delivered_at = NOW()
            WHERE message_id = ? AND delivered_at IS NULL
        ");
        $stmt->bind_param("si", $status, $messageId);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to update message status: " . $stmt->error);
    }
    $stmt->close();

    closeDBConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'delivery_status' => $status
    ]);
} catch (Exception $e) {
    error_log("Error updating message status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update message status']);
}
