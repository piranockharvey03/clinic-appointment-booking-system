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
if (!isset($_POST['conversation_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id is required']);
    exit;
}

try {
    $conn = getDBConnection();
    $userId = intval($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'];
    $conversationId = intval($_POST['conversation_id']);

    // Verify user belongs to this conversation
    if ($userRole === 'patient') {
        $stmt = $conn->prepare("
            SELECT conversation_id FROM conversations
            WHERE conversation_id = ? AND patient_id = ?
        ");
        $stmt->bind_param("ii", $conversationId, $userId);
    } else {
        $stmt = $conn->prepare("
            SELECT conversation_id FROM conversations
            WHERE conversation_id = ? AND doctor_id = ?
        ");
        $stmt->bind_param("ii", $conversationId, $userId);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'conversation not found or unauthorized']);
        exit;
    }

    $stmt->close();

    // Mark unread messages as read
    $stmt = $conn->prepare("
        UPDATE messages
        SET is_read = 1, read_at = NOW()
        WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->bind_param("ii", $conversationId, $userId);
    $stmt->execute();

    $rows = $stmt->affected_rows;
    $stmt->close();

    closeDBConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'messages_marked_read' => $rows
    ]);
} catch (Exception $e) {
    error_log("Error marking messages as read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to mark messages as read']);
}
