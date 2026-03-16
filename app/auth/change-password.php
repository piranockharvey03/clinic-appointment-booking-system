<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Enable error logging for debugging (never display errors in JSON responses)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../password-change-errors.log');

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login again']);
    ob_end_flush();
    exit;
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate input  
if (empty($newPassword) || empty($confirmPassword)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    ob_end_flush();
    exit;
}

if ($newPassword !== $confirmPassword) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
    ob_end_flush();
    exit;
}

if (strlen($newPassword) < 6) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long']);
    ob_end_flush();
    exit;
}

try {
    $conn = getDBConnection();
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];

    // Determine which table to use based on user role
    if ($userRole === 'admin') {
        $tableName = 'admin';
    } elseif ($userRole === 'patient') {
        $tableName = 'users';
    } elseif ($userRole === 'doctor') {
        $tableName = 'doctors';
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid user role']);
        ob_end_flush();
        exit;
    }

    // Hash new password (no current password verification needed)
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Log before update attempt
    error_log("Attempting password update (no verification) for {$userRole} user ID: {$userId} in table: {$tableName}");
    error_log("New password hash: " . substr($hashedPassword, 0, 20) . "...");

    // Update password
    $updateQuery = "UPDATE `{$tableName}` SET password = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        error_log("Update prepare failed: " . $conn->error);
        closeDBConnection($conn);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Database error occurred']);
        ob_end_flush();
        exit;
    }

    $updateStmt->bind_param("si", $hashedPassword, $userId);

    if ($updateStmt->execute()) {
        $affectedRows = $updateStmt->affected_rows;
        $updateStmt->close();

        // Log the update result
        error_log("Password update executed for {$userRole} user ID: {$userId}, affected_rows: {$affectedRows}");
        error_log("New hash length: " . strlen($hashedPassword));

        if ($affectedRows > 0) {
            logActivity($conn, $userId, $_SESSION['user_name'] ?? 'Unknown', $userRole, 'change_password', 'Password changed successfully');
            closeDBConnection($conn);
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
            ob_end_flush();
        } else {
            closeDBConnection($conn);
            error_log("WARNING: execute() succeeded but affected_rows is 0 for user ID: {$userId}");
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Password update failed - no rows affected. Please contact support.'
            ]);
            ob_end_flush();
        }
    } else {
        $error = $updateStmt->error;
        $updateStmt->close();
        closeDBConnection($conn);
        error_log("Failed to update password: " . $error);
        throw new Exception("Failed to update password");
    }
} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    ob_clean(); // Clear any accidental output
    echo json_encode(['success' => false, 'error' => 'Failed to change password. Please try again.']);
}

// End output buffering and send the response
ob_end_flush();
