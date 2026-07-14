<?php
require_once '../../config/db-config.php';
require_once 'FileCache.php';

header('Content-Type: application/json');

try {
    // Get filters from query parameters
    $department  = trim($_GET['department'] ?? '');
    $searchQuery = trim($_GET['search'] ?? '');

    $cache = new FileCache();

    // Only cache non-search requests (search queries vary too much to cache efficiently)
    $useCache   = empty($searchQuery);
    $cacheKey   = 'doctors_dept_' . ($department !== '' ? md5($department) : 'all');
    $cacheTtl   = 3600; // 1 hour

    if ($useCache) {
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            echo json_encode([
                'success'    => true,
                'doctors'    => $cached['doctors'],
                'count'      => $cached['count'],
                'from_cache' => true,
            ]);
            exit;
        }
    }

    $conn = getDBConnection();

    if (empty($department) && empty($searchQuery)) {
        // Return all active doctors
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
        $types  = "";

        if (!empty($department)) {
            $query   .= " AND dd.department = ?";
            $params[] = $department;
            $types   .= "s";
        }

        if (!empty($searchQuery)) {
            $query   .= " AND d.full_name LIKE ?";
            $params[] = "%{$searchQuery}%";
            $types   .= "s";
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

    // Cache the result for non-search requests
    if ($useCache) {
        $cache->set($cacheKey, [
            'doctors' => $doctors,
            'count'   => count($doctors),
        ], $cacheTtl);
    }

    echo json_encode([
        'success'    => true,
        'doctors'    => $doctors,
        'count'      => count($doctors),
        'from_cache' => false,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Failed to fetch doctors',
        'message' => $e->getMessage(),
    ]);
    error_log("Get doctors by department error: " . $e->getMessage());
}
