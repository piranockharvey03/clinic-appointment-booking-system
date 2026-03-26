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
if (!isset($_POST['conversation_id']) || !isset($_POST['is_typing'])) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id and is_typing are required']);
    exit;
}

try {
    $conn = getDBConnection();
    $userId = intval($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'];
    $conversationId = intval($_POST['conversation_id']);
    $isTyping = intval($_POST['is_typing']) ? 1 : 0;

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
        closeDBConnection($conn);
        echo json_encode(['error' => 'conversation not found or unauthorized']);
        exit;
    }

    $stmt->close();
    $result->free();

    // Update or insert typing status
    $expiresAt = date('Y-m-d H:i:s', time() + 5); // Expire after 5 seconds
    $stmt = $conn->prepare("
        INSERT INTO typing_status (conversation_id, user_role, user_id, is_typing, expires_at)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            is_typing = VALUES(is_typing),
            expires_at = VALUES(expires_at),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param("issis", $conversationId, $userRole, $userId, $isTyping, $expiresAt);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update typing status: " . $stmt->error);
    }
    $stmt->close();

    closeDBConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'is_typing' => $isTyping === 1
    ]);
} catch (Exception $e) {
    error_log("Error setting typing status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update typing status']);
}
