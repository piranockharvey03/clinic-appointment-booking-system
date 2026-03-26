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
if (!isset($_GET['conversation_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id is required']);
    exit;
}

header('Content-Type: application/json');

try {
    $conn = getDBConnection();
    $userId = intval($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'];
    $conversationId = intval($_GET['conversation_id']);

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

    // Get active typing status (not expired) - other participants only
    $stmt = $conn->prepare("
        SELECT user_role, user_id, is_typing, updated_at
        FROM typing_status
        WHERE conversation_id = ?
          AND expires_at > NOW()
          AND is_typing = 1
          AND NOT (user_role = ? AND user_id = ?)
        ORDER BY updated_at DESC
    ");
    $stmt->bind_param("isi", $conversationId, $userRole, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $typingUsers = [];
    while ($row = $result->fetch_assoc()) {
        $typingUsers[] = [
            'user_role' => $row['user_role'],
            'user_id' => $row['user_id']
        ];
    }

    $result->free();
    $stmt->close();
    closeDBConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'typing_users' => $typingUsers
    ]);
} catch (Exception $e) {
    error_log("Error fetching typing status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch typing status']);
}
