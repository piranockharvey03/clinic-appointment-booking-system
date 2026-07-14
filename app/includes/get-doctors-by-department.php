<?php
require_once '../../config/db-config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // Get filters from query parameters
    $department = $_GET['department'] ?? '';
    $searchQuery = $_GET['search'] ?? '';

    if (empty($department) && empty($searchQuery)) {
        // Return all active doctors grouped by their departments and specialties
        $query = "SELECT DISTINCT d.id, d.full_name, d.specialty, d.qualification, d.experience_years, d.photo,
                  GROUP_CONCAT(DISTINCT dd.department SEPARATOR ', ') as departments,
                  GROUP_CONCAT(DISTINCT ds.specialty SEPARATOR ', ') as additional_specialties
                  FROM doctors d
                  LEFT JOIN doctor_departments dd ON d.id = dd.doctor_id
                  LEFT JOIN doctor_specialties ds ON d.id = ds.doctor_id
                  WHERE d.status = 'active'
                  GROUP BY d.id
                  ORDER BY d.full_name ASC";
        $stmt = $conn->query($query);
    } else {
        // Build dynamic query based on filters
        $query = "SELECT DISTINCT d.id, d.full_name, d.specialty, d.qualification, d.experience_years, d.photo,
                  GROUP_CONCAT(DISTINCT dd.department SEPARATOR ', ') as departments,
                  GROUP_CONCAT(DISTINCT ds.specialty SEPARATOR ', ') as additional_specialties
                  FROM doctors d
                  LEFT JOIN doctor_departments dd ON d.id = dd.doctor_id
                  LEFT JOIN doctor_specialties ds ON d.id = ds.doctor_id
                  WHERE d.status = 'active'";

        $params = [];
        $types = "";

        if (!empty($department)) {
            $query .= " AND dd.department = ?";
            $params[] = $department;
            $types .= "s";
        }

        if (!empty($searchQuery)) {
            $query .= " AND d.full_name LIKE ?";
            $params[] = "%{$searchQuery}%";
            $types .= "s";
        }

        $query .= " GROUP BY d.id ORDER BY d.full_name ASC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $stmt = $stmt->get_result();
    }

    $doctors = [];
    while ($row = $stmt->fetch_assoc()) {
        $doctors[] = $row;
    }

    closeDBConnection($conn);

    echo json_encode([
        'success' => true,
        'doctors' => $doctors,
        'count' => count($doctors)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch doctors',
        'message' => $e->getMessage()
    ]);
    error_log("Get doctors by department error: " . $e->getMessage());
}
