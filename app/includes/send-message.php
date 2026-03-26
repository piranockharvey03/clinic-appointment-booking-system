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
if (!isset($_POST['conversation_id']) || !isset($_POST['message_text'])) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id and message_text are required']);
    exit;
}

try {
    $conn = getDBConnection();
    $userId = intval($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'];
    $conversationId = intval($_POST['conversation_id']);
    $messageText = trim($_POST['message_text']);

    // Validate message text
    if (empty($messageText) || strlen($messageText) > 5000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message must be between 1 and 5000 characters']);
        exit;
    }

    // Fetch conversation and validate user belongs to it
    if ($userRole === 'patient') {
        $stmt = $conn->prepare("
            SELECT patient_id, doctor_id FROM conversations
            WHERE conversation_id = ? AND patient_id = ?
        ");
        $stmt->bind_param("ii", $conversationId, $userId);
        $receiverRole = 'doctor';
    } else {
        $stmt = $conn->prepare("
            SELECT patient_id, doctor_id FROM conversations
            WHERE conversation_id = ? AND doctor_id = ?
        ");
        $stmt->bind_param("ii", $conversationId, $userId);
        $receiverRole = 'patient';
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'conversation not found or unauthorized']);
        exit;
    }

    $conv = $result->fetch_assoc();
    $stmt->close();
    $result->free();

    $receiverId = ($userRole === 'patient') ? $conv['doctor_id'] : $conv['patient_id'];

    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO messages
        (conversation_id, sender_role, sender_id, receiver_role, receiver_id, message_text, delivery_status, is_read, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'sent', 0, NOW())
    ");
    $stmt->bind_param("isisis", $conversationId, $userRole, $userId, $receiverRole, $receiverId, $messageText);

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert message: " . $stmt->error);
    }

    $messageId = $stmt->insert_id;
    $stmt->close();

    // Update conversation last_message_at
    $stmt = $conn->prepare("
        UPDATE conversations
        SET last_message_at = NOW()
        WHERE conversation_id = ?
    ");
    $stmt->bind_param("i", $conversationId);
    $stmt->execute();
    $stmt->close();

    closeDBConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'created_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Error sending message: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
}
