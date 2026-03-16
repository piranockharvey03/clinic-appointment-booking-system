<?php
require_once '../../config/db-config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // Get all departments with active doctors
    $query = "SELECT DISTINCT dd.department, COUNT(DISTINCT d.id) as doctor_count
              FROM doctor_departments dd
              INNER JOIN doctors d ON dd.doctor_id = d.id
              WHERE d.status = 'active'
              GROUP BY dd.department
              ORDER BY dd.department ASC";

    $stmt = $conn->query($query);

    $departments = [];
    while ($row = $stmt->fetch_assoc()) {
        $departments[] = $row;
    }

    closeDBConnection($conn);

    echo json_encode([
        'success' => true,
        'departments' => $departments,
        'count' => count($departments)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch departments',
        'message' => $e->getMessage()
    ]);
    error_log("Get departments error: " . $e->getMessage());
}
