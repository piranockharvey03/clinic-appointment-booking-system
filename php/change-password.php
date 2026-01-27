<?php
session_start();
require_once 'db-config.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate input
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long']);
    exit;
}

try {
    $conn = getDBConnection();
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];

    // Determine which table to use based on user role
    $tableName = ($userRole === 'admin') ? 'admin' : 'users';

    // Get current password from database
    $stmt = $conn->prepare("SELECT password FROM $tableName WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        closeDBConnection($conn);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $row = $result->fetch_assoc();
    $storedPassword = $row['password'];
    $stmt->close();

    // Verify current password
    if (!password_verify($currentPassword, $storedPassword)) {
        closeDBConnection($conn);
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
        exit;
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    $updateStmt = $conn->prepare("UPDATE $tableName SET password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $userId);

    if ($updateStmt->execute()) {
        $updateStmt->close();
        closeDBConnection($conn);

        // Log successful password change (optional)
        error_log("Password changed successfully for {$userRole} user ID: {$userId}");

        echo json_encode([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        throw new Exception("Failed to update password: " . $updateStmt->error);
    }

} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to change password. Please try again.']);
}
?>
