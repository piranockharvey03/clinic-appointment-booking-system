<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';
require_once '../includes/FileCache.php';

// Start admin-specific session
startSession('admin');

// Redirect to login if not authenticated or not an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../public/admin-login.html');
    exit;
}

$successMessage = '';
$errorMessage = '';

// Handle doctor addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialty = trim($_POST['specialty']);
    $departments = $_POST['departments'] ?? []; // Array of selected departments
    $additionalSpecialties = $_POST['additional_specialties'] ?? []; // Array of additional specialties
    $qualification = trim($_POST['qualification'] ?? '');
    $experienceYears = intval($_POST['experience_years'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'];
    $photoPath = null;

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
            $errorMessage = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
        } elseif ($_FILES['photo']['size'] > $maxSize) {
            $errorMessage = "File size too large. Maximum size is 5MB.";
        } else {
            $uploadDir = '../../public/uploads/doctors/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('doctor_', true) . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                $photoPath = 'uploads/doctors/' . $fileName;
            } else {
                $errorMessage = "Failed to upload photo. Please try again.";
            }
        }
    }

    // Validate at least one department selected
    if (empty($departments) && empty($errorMessage)) {
        $errorMessage = "Please select at least one department.";
    } elseif (empty($errorMessage)) {
        try {
            $conn = getDBConnection();

            // Check if email already exists
            $checkStmt = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $errorMessage = "A doctor with this email already exists.";
            } else {
                // Hash password and insert doctor (keep first department for backward compatibility)
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $primaryDept = $departments[0];
                $stmt = $conn->prepare("INSERT INTO doctors (full_name, email, phone, password, specialty, department, qualification, experience_years, status, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssdss", $fullName, $email, $phone, $hashedPassword, $specialty, $primaryDept, $qualification, $experienceYears, $status, $photoPath);

                if ($stmt->execute()) {
                    $doctorId = $conn->insert_id;

                    // Insert all departments into junction table
                    $deptStmt = $conn->prepare("INSERT INTO doctor_departments (doctor_id, department) VALUES (?, ?) ON DUPLICATE KEY UPDATE department = VALUES(department)");
                    foreach ($departments as $dept) {
                        $deptStmt->bind_param("is", $doctorId, $dept);
                        $deptStmt->execute();
                    }
                    $deptStmt->close();

                    // Insert additional specialties into junction table
                    if (!empty($additionalSpecialties)) {
                        $specStmt = $conn->prepare("INSERT INTO doctor_specialties (doctor_id, specialty) VALUES (?, ?) ON DUPLICATE KEY UPDATE specialty = VALUES(specialty)");
                        foreach ($additionalSpecialties as $spec) {
                            $specStmt->bind_param("is", $doctorId, $spec);
                            $specStmt->execute();
                        }
                        $specStmt->close();
                    }

                    $successMessage = "Doctor added successfully with " . count($departments) . " department(s) and " . count($additionalSpecialties) . " additional specialt" . (count($additionalSpecialties) === 1 ? 'y' : 'ies') . "!";
                    // Invalidate doctor-related caches
                    (new FileCache())->flush();
                } else {
                    $errorMessage = "Failed to add doctor. Please try again.";
                }
                $stmt->close();
            }
            $checkStmt->close();
            closeDBConnection($conn);
        } catch (Exception $e) {
            $errorMessage = "Error: " . $e->getMessage();
            error_log("Add doctor error: " . $e->getMessage());
        }
    }
}

// Handle doctor update/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $doctorId = $_POST['doctor_id'];
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialty = trim($_POST['specialty']);
    $departments = $_POST['departments'] ?? []; // Array of selected departments
    $additionalSpecialties = $_POST['additional_specialties'] ?? []; // Array of additional specialties
    $qualification = trim($_POST['qualification'] ?? '');
    $experienceYears = intval($_POST['experience_years'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $newPassword = trim($_POST['new_password'] ?? '');
    $photoPath = null;

    // Validate at least one department selected
    if (empty($departments)) {
        $errorMessage = "Please select at least one department.";
    } else {
        try {
            $conn = getDBConnection();

            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['photo']['type'], $allowedTypes)) {
                    $errorMessage = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
                } elseif ($_FILES['photo']['size'] > $maxSize) {
                    $errorMessage = "File size too large. Maximum size is 5MB.";
                } else {
                    $uploadDir = '../../public/uploads/doctors/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid('doctor_', true) . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                        $photoPath = 'uploads/doctors/' . $fileName;
                        
                        // Delete old photo if exists
                        $oldPhotoStmt = $conn->prepare("SELECT photo FROM doctors WHERE id = ?");
                        $oldPhotoStmt->bind_param("i", $doctorId);
                        $oldPhotoStmt->execute();
                        $oldPhotoResult = $oldPhotoStmt->get_result();
                        if ($oldPhotoRow = $oldPhotoResult->fetch_assoc()) {
                            if ($oldPhotoRow['photo'] && file_exists('../../public/' . $oldPhotoRow['photo'])) {
                                unlink('../../public/' . $oldPhotoRow['photo']);
                            }
                        }
                        $oldPhotoStmt->close();
                    } else {
                        $errorMessage = "Failed to upload photo. Please try again.";
                    }
                }
            }

            if (empty($errorMessage)) {
                // Check if email already exists for a different doctor
                $checkStmt = $conn->prepare("SELECT id FROM doctors WHERE email = ? AND id != ?");
                $checkStmt->bind_param("si", $email, $doctorId);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    $errorMessage = "A doctor with this email already exists.";
                } else {
                    $primaryDept = $departments[0];

                    // Check if password should be updated
                    if (!empty($newPassword)) {
                        // Update doctor with new password
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        if ($photoPath) {
                            $stmt = $conn->prepare("UPDATE doctors SET full_name = ?, email = ?, phone = ?, specialty = ?, department = ?, qualification = ?, experience_years = ?, status = ?, password = ?, photo = ? WHERE id = ?");
                            $stmt->bind_param("ssssssdsssi", $fullName, $email, $phone, $specialty, $primaryDept, $qualification, $experienceYears, $status, $hashedPassword, $photoPath, $doctorId);
                        } else {
                            $stmt = $conn->prepare("UPDATE doctors SET full_name = ?, email = ?, phone = ?, specialty = ?, department = ?, qualification = ?, experience_years = ?, status = ?, password = ? WHERE id = ?");
                            $stmt->bind_param("ssssssdssi", $fullName, $email, $phone, $specialty, $primaryDept, $qualification, $experienceYears, $status, $hashedPassword, $doctorId);
                        }

                        if ($stmt->execute()) {
                            // Update departments in junction table
                            // First delete existing departments
                            $delStmt = $conn->prepare("DELETE FROM doctor_departments WHERE doctor_id = ?");
                            $delStmt->bind_param("i", $doctorId);
                            $delStmt->execute();
                            $delStmt->close();

                            // Insert new departments
                            $deptStmt = $conn->prepare("INSERT INTO doctor_departments (doctor_id, department) VALUES (?, ?)");
                            foreach ($departments as $dept) {
                                $deptStmt->bind_param("is", $doctorId, $dept);
                                $deptStmt->execute();
                            }
                            $deptStmt->close();

                            // Update additional specialties
                            // First delete existing specialties
                            $delSpecStmt = $conn->prepare("DELETE FROM doctor_specialties WHERE doctor_id = ?");
                            $delSpecStmt->bind_param("i", $doctorId);
                            $delSpecStmt->execute();
                            $delSpecStmt->close();

                            // Insert new specialties
                            if (!empty($additionalSpecialties)) {
                                $specStmt = $conn->prepare("INSERT INTO doctor_specialties (doctor_id, specialty) VALUES (?, ?)");
                                foreach ($additionalSpecialties as $spec) {
                                    $specStmt->bind_param("is", $doctorId, $spec);
                                    $specStmt->execute();
                                }
                                $specStmt->close();
                            }

                            $successMessage = "Doctor details and password updated successfully with " . count($departments) . " department(s) and " . count($additionalSpecialties) . " additional specialt" . (count($additionalSpecialties) === 1 ? 'y' : 'ies') . "!";
                            // Invalidate doctor-related caches
                            (new FileCache())->flush();
                        } else {
                            $errorMessage = "Failed to update doctor. Please try again.";
                        }
                    } else {
                        // Update doctor without password
                        if ($photoPath) {
                            $stmt = $conn->prepare("UPDATE doctors SET full_name = ?, email = ?, phone = ?, specialty = ?, department = ?, qualification = ?, experience_years = ?, status = ?, photo = ? WHERE id = ?");
                            $stmt->bind_param("ssssssdssi", $fullName, $email, $phone, $specialty, $primaryDept, $qualification, $experienceYears, $status, $photoPath, $doctorId);
                        } else {
                            $stmt = $conn->prepare("UPDATE doctors SET full_name = ?, email = ?, phone = ?, specialty = ?, department = ?, qualification = ?, experience_years = ?, status = ? WHERE id = ?");
                            $stmt->bind_param("ssssssdsi", $fullName, $email, $phone, $specialty, $primaryDept, $qualification, $experienceYears, $status, $doctorId);
                        }

                        if ($stmt->execute()) {
                            // Update departments in junction table
                            // First delete existing departments
                            $delStmt = $conn->prepare("DELETE FROM doctor_departments WHERE doctor_id = ?");
                            $delStmt->bind_param("i", $doctorId);
                            $delStmt->execute();
                            $delStmt->close();

                            // Insert new departments
                            $deptStmt = $conn->prepare("INSERT INTO doctor_departments (doctor_id, department) VALUES (?, ?)");
                            foreach ($departments as $dept) {
                                $deptStmt->bind_param("is", $doctorId, $dept);
                                $deptStmt->execute();
                            }
                            $deptStmt->close();

                            // Update additional specialties
                            // First delete existing specialties
                            $delSpecStmt = $conn->prepare("DELETE FROM doctor_specialties WHERE doctor_id = ?");
                            $delSpecStmt->bind_param("i", $doctorId);
                            $delSpecStmt->execute();
                            $delSpecStmt->close();

                            // Insert new specialties
                            if (!empty($additionalSpecialties)) {
                                $specStmt = $conn->prepare("INSERT INTO doctor_specialties (doctor_id, specialty) VALUES (?, ?)");
                                foreach ($additionalSpecialties as $spec) {
                                    $specStmt->bind_param("is", $doctorId, $spec);
                                    $specStmt->execute();
                                }
                                $specStmt->close();
                            }

                            $successMessage = "Doctor details updated successfully with " . count($departments) . " department(s) and " . count($additionalSpecialties) . " additional specialt" . (count($additionalSpecialties) === 1 ? 'y' : 'ies') . "!";
                            // Invalidate doctor-related caches
                            (new FileCache())->flush();
                        } else {
                            $errorMessage = "Failed to update doctor. Please try again.";
                        }
                    }
                    $stmt->close();
                }
                $checkStmt->close();
                closeDBConnection($conn);
            }
        } catch (Exception $e) {
            $errorMessage = "Error: " . $e->getMessage();
            error_log("Edit doctor error: " . $e->getMessage());
        }
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $doctorId = $_POST['doctor_id'];
    $newStatus = $_POST['new_status'];

    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE doctors SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $doctorId);

        if ($stmt->execute()) {
            $successMessage = "Doctor status updated successfully!";
            // Invalidate doctor-related caches
            (new FileCache())->flush();
        } else {
            $errorMessage = "Failed to update status.";
        }
        $stmt->close();
        closeDBConnection($conn);
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Load doctors from database
$doctors = [];
$filterStatus = $_GET['status'] ?? 'all';
$filterDepartment = $_GET['department'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

try {
    $conn = getDBConnection();

    // Query with department and specialty joins to get all data
    $query = "SELECT d.id, d.full_name, d.email, d.phone, d.specialty, d.department, 
              d.qualification, d.experience_years, d.status, d.created_at,
              GROUP_CONCAT(DISTINCT dd.department SEPARATOR ', ') as all_departments,
              GROUP_CONCAT(DISTINCT ds.specialty SEPARATOR ', ') as additional_specialties
              FROM doctors d
              LEFT JOIN doctor_departments dd ON d.id = dd.doctor_id
              LEFT JOIN doctor_specialties ds ON d.id = ds.doctor_id";

    // Build WHERE clause based on filters
    $whereClauses = [];
    $params = [];
    $types = "";

    if ($filterStatus !== 'all') {
        $whereClauses[] = "d.status = ?";
        $params[] = $filterStatus;
        $types .= "s";
    }

    if ($filterDepartment !== 'all') {
        $whereClauses[] = "EXISTS (SELECT 1 FROM doctor_departments dd2 WHERE dd2.doctor_id = d.id AND dd2.department = ?)";
        $params[] = $filterDepartment;
        $types .= "s";
    }

    if (!empty($searchQuery)) {
        $whereClauses[] = "d.full_name LIKE ?";
        $params[] = "%" . $searchQuery . "%";
        $types .= "s";
    }

    if (!empty($whereClauses)) {
        $query .= " WHERE " . implode(" AND ", $whereClauses);
    }

    $query .= " GROUP BY d.id ORDER BY d.created_at DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt = $stmt->get_result();
    } else {
        $stmt = $conn->query($query);
    }

    while ($row = $stmt->fetch_assoc()) {
        // Add departments array for JavaScript
        $row['departments_array'] = !empty($row['all_departments']) ? explode(', ', $row['all_departments']) : [];
        // Add additional specialties array for JavaScript
        $row['specialties_array'] = !empty($row['additional_specialties']) ? explode(', ', $row['additional_specialties']) : [];
        $doctors[] = $row;
    }

    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Load doctors error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors | MediCare Admin</title>
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-gray-50">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="sidebar bg-blue-800 text-white" id="sidebar">
            <div class="flex flex-col w-64">
                <div class="flex flex-col flex-grow bg-blue-800 pt-5 pb-4 overflow-y-auto">
                    <div class="flex items-center justify-between flex-shrink-0 px-4">
                        <div class="flex items-center">
                            <i data-feather="activity" class="text-white mr-2"></i>
                            <span class="text-xl font-semibold text-white">MediCare Admin</span>
                        </div>
                        <button class="text-blue-200 hover:text-white md:hidden" id="menuBtn" type="button">
                            <i data-feather="menu" class="h-6 w-6"></i>
                        </button>
                    </div>
                    <nav class="mt-5 flex-1 px-2 space-y-1">
                        <a href="new-admin-dashboard.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="home" class="mr-3 h-5 w-5"></i>
                            Dashboard
                        </a>
                        <a href="manage-doctors.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                            <i data-feather="users" class="mr-3 h-5 w-5"></i>
                            Manage Doctors
                        </a>
                        <a href="manage-patients.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="user" class="mr-3 h-5 w-5"></i>
                            Manage Patients
                        </a>
                        <a href="manage-feedback.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="message-square" class="mr-3 h-5 w-5"></i>
                            Feedback
                        </a>
                        <a href="reports.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="bar-chart-2" class="mr-3 h-5 w-5"></i>
                            Reports
                        </a>
                        <a href="doctor-evaluation.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="activity" class="mr-3 h-5 w-5"></i>
                            Doctor Evaluation
                        </a>
                        <a href="admin-appointments.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                            Appointments
                        </a>
                    </nav>
                    <div class="flex-shrink-0 flex border-t border-blue-700 p-4">
                        <a href="admin-settings.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white w-full">
                            <i data-feather="settings" class="mr-3 h-5 w-5"></i>
                            Settings
                        </a>
                    </div>
                    <div class="flex-shrink-0 flex p-4">
                        <a href="admin-logout.php" class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white w-full">
                            <i data-feather="log-out" class="mr-3 h-5 w-5"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <div class="main-content flex flex-col w-0 flex-1 overflow-hidden">
            <div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow">
                <button type="button" id="mobileMenuBtn" class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 md:hidden">
                    <i data-feather="menu" class="h-6 w-6"></i>
                </button>
                <div class="flex-1 px-4 flex justify-between">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-semibold text-gray-900">Manage Doctors</h1>
                    </div>
                    <div class="ml-4 flex items-center md:ml-6">
                        <button onclick="showAddDoctorModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i data-feather="plus" class="h-4 w-4 mr-2"></i>
                            Add Doctor
                        </button>
                    </div>
                </div>
            </div>

            <main class="flex-1 relative overflow-y-auto focus:outline-none">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">

                        <!-- Success/Error Messages -->
                        <?php if ($successMessage): ?>
                            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline"><?= htmlspecialchars($successMessage) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($errorMessage): ?>
                            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline"><?= htmlspecialchars($errorMessage) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Filters Section -->
                        <div class="mb-6 bg-white shadow rounded-lg p-4">
                            <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                                <!-- Department Filter -->
                                <div class="flex-1">
                                    <label for="departmentFilter" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i data-feather="filter" class="inline h-4 w-4 mr-1"></i>
                                        Filter by Department
                                    </label>
                                    <select id="departmentFilter" onchange="filterByDepartment(this.value)"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="all" <?= $filterDepartment === 'all' ? 'selected' : '' ?>>All Departments</option>
                                        <option value="Cardiology Department" <?= $filterDepartment === 'Cardiology Department' ? 'selected' : '' ?>>Cardiology</option>
                                        <option value="Dermatology Department" <?= $filterDepartment === 'Dermatology Department' ? 'selected' : '' ?>>Dermatology</option>
                                        <option value="Emergency Department" <?= $filterDepartment === 'Emergency Department' ? 'selected' : '' ?>>Emergency</option>
                                        <option value="Endocrinology Department" <?= $filterDepartment === 'Endocrinology Department' ? 'selected' : '' ?>>Endocrinology</option>
                                        <option value="General Medicine Department" <?= $filterDepartment === 'General Medicine Department' ? 'selected' : '' ?>>General Medicine</option>
                                        <option value="Gastroenterology Department" <?= $filterDepartment === 'Gastroenterology Department' ? 'selected' : '' ?>>Gastroenterology</option>
                                        <option value="General Surgery Department" <?= $filterDepartment === 'General Surgery Department' ? 'selected' : '' ?>>General Surgery</option>
                                        <option value="Obstetrics & Gynecology (OB/GYN)" <?= $filterDepartment === 'Obstetrics & Gynecology (OB/GYN)' ? 'selected' : '' ?>>OB/GYN</option>
                                        <option value="Internal Medicine Department" <?= $filterDepartment === 'Internal Medicine Department' ? 'selected' : '' ?>>Internal Medicine</option>
                                        <option value="Neurology Department" <?= $filterDepartment === 'Neurology Department' ? 'selected' : '' ?>>Neurology</option>
                                        <option value="Oncology Department" <?= $filterDepartment === 'Oncology Department' ? 'selected' : '' ?>>Oncology</option>
                                        <option value="Ophthalmology Department" <?= $filterDepartment === 'Ophthalmology Department' ? 'selected' : '' ?>>Ophthalmology</option>
                                        <option value="Orthopedics Department" <?= $filterDepartment === 'Orthopedics Department' ? 'selected' : '' ?>>Orthopedics</option>
                                        <option value="Otolaryngology (ENT) Department" <?= $filterDepartment === 'Otolaryngology (ENT) Department' ? 'selected' : '' ?>>ENT</option>
                                        <option value="Pediatrics Department" <?= $filterDepartment === 'Pediatrics Department' ? 'selected' : '' ?>>Pediatrics</option>
                                        <option value="Psychiatry Department" <?= $filterDepartment === 'Psychiatry Department' ? 'selected' : '' ?>>Psychiatry</option>
                                        <option value="Pulmonology Department" <?= $filterDepartment === 'Pulmonology Department' ? 'selected' : '' ?>>Pulmonology</option>
                                        <option value="Radiology Department" <?= $filterDepartment === 'Radiology Department' ? 'selected' : '' ?>>Radiology</option>
                                        <option value="Rheumatology Department" <?= $filterDepartment === 'Rheumatology Department' ? 'selected' : '' ?>>Rheumatology</option>
                                        <option value="Urology Department" <?= $filterDepartment === 'Urology Department' ? 'selected' : '' ?>>Urology</option>
                                    </select>
                                </div>

                                <!-- Search by Name -->
                                <div class="flex-1">
                                    <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i data-feather="search" class="inline h-4 w-4 mr-1"></i>
                                        Search by Name
                                    </label>
                                    <div class="flex gap-2">
                                        <input type="text" id="searchInput" placeholder="Enter doctor name..." value="<?= htmlspecialchars($searchQuery) ?>"
                                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onkeypress="if(event.key === 'Enter') searchDoctors()">
                                        <button onclick="searchDoctors()"
                                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <i data-feather="search" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Clear Filters -->
                                <?php if ($filterDepartment !== 'all' || !empty($searchQuery)): ?>
                                    <div class="flex items-end">
                                        <a href="manage-doctors.php?status=<?= $filterStatus ?>"
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <i data-feather="x" class="h-4 w-4 mr-2"></i>
                                            Clear Filters
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($filterDepartment !== 'all' || !empty($searchQuery)): ?>
                                <div class="mt-3 text-sm text-gray-600">
                                    <i data-feather="info" class="inline h-4 w-4 mr-1"></i>
                                    <?php if ($filterDepartment !== 'all' && !empty($searchQuery)): ?>
                                        Showing doctors in <span class="font-semibold text-blue-600"><?= htmlspecialchars(str_replace(' Department', '', $filterDepartment)) ?></span>
                                        matching "<span class="font-semibold text-blue-600"><?= htmlspecialchars($searchQuery) ?></span>"
                                    <?php elseif ($filterDepartment !== 'all'): ?>
                                        Showing doctors in: <span class="font-semibold text-blue-600"><?= htmlspecialchars(str_replace(' Department', '', $filterDepartment)) ?></span>
                                    <?php else: ?>
                                        Showing doctors matching: "<span class="font-semibold text-blue-600"><?= htmlspecialchars($searchQuery) ?></span>"
                                    <?php endif; ?>
                                    <span class="ml-2 text-gray-500">(<?= count($doctors) ?> doctor<?= count($doctors) !== 1 ? 's' : '' ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Filter Tabs -->
                        <div class="mb-6 border-b border-gray-200">
                            <nav class="-mb-px flex flex-wrap gap-3 sm:gap-8">
                                <?php
                                $tabParams = '';
                                if ($filterDepartment !== 'all') $tabParams .= '&department=' . urlencode($filterDepartment);
                                if (!empty($searchQuery)) $tabParams .= '&search=' . urlencode($searchQuery);
                                ?>
                                <a href="manage-doctors.php?status=all<?= $tabParams ?>" class="<?= $filterStatus === 'all' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    All Doctors
                                </a>
                                <a href="manage-doctors.php?status=active<?= $tabParams ?>" class="<?= $filterStatus === 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Active
                                </a>
                                <a href="manage-doctors.php?status=inactive<?= $tabParams ?>" class="<?= $filterStatus === 'inactive' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Inactive
                                </a>
                            </nav>
                        </div>

                        <!-- Doctors Table -->
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialty</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Experience</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php if (empty($doctors)): ?>
                                            <tr>
                                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">No doctors found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($doctors as $doctor): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($doctor['full_name']) ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?= htmlspecialchars($doctor['email']) ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?= htmlspecialchars($doctor['phone']) ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?= htmlspecialchars($doctor['specialty']) ?>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        <div class="max-w-xs">
                                                            <?php
                                                            if (!empty($doctor['all_departments'])) {
                                                                $depts = explode(', ', $doctor['all_departments']);
                                                                $shortDepts = array_map(function ($d) {
                                                                    return str_replace(' Department', '', $d);
                                                                }, $depts);
                                                                echo htmlspecialchars(implode(', ', $shortDepts));
                                                            } else {
                                                                echo htmlspecialchars($doctor['department']);
                                                            }
                                                            ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?= htmlspecialchars($doctor['experience_years']) ?> years
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <?php if ($doctor['status'] === 'active'): ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                                        <?php else: ?>
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <button onclick='showEditDoctorModal(<?= json_encode($doctor) ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                            Edit
                                                        </button>
                                                        <form method="POST" class="inline-block">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="doctor_id" value="<?= $doctor['id'] ?>">
                                                            <input type="hidden" name="new_status" value="<?= $doctor['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                            <button type="submit" class="text-blue-600 hover:text-blue-900">
                                                                <?= $doctor['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Doctor Modal -->
    <div id="addDoctorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add New Doctor</h3>
                    <button onclick="hideAddDoctorModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-feather="x" class="h-6 w-6"></i>
                    </button>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="full_name" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="tel" name="phone" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specialty *</label>
                            <select name="specialty" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Specialty</option>
                                <option value="Cardiology">Cardiology</option>
                                <option value="Dermatology">Dermatology</option>
                                <option value="Emergency Medicine">Emergency Medicine</option>
                                <option value="Endocrinology">Endocrinology</option>
                                <option value="Family Medicine">Family Medicine</option>
                                <option value="Gastroenterology">Gastroenterology</option>
                                <option value="General Surgery">General Surgery</option>
                                <option value="Gynecology">Gynecology</option>
                                <option value="Internal Medicine">Internal Medicine</option>
                                <option value="Neurology">Neurology</option>
                                <option value="Obstetrics">Obstetrics</option>
                                <option value="Oncology">Oncology</option>
                                <option value="Ophthalmology">Ophthalmology</option>
                                <option value="Orthopedics">Orthopedics</option>
                                <option value="Otolaryngology (ENT)">Otolaryngology (ENT)</option>
                                <option value="Pediatrics">Pediatrics</option>
                                <option value="Psychiatry">Psychiatry</option>
                                <option value="Pulmonology">Pulmonology</option>
                                <option value="Radiology">Radiology</option>
                                <option value="Rheumatology">Rheumatology</option>
                                <option value="Urology">Urology</option>
                            </select>
                        </div>

                        <div class="mb-4 col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Specialties (Optional)</label>
                            <p class="text-xs text-gray-500 mb-2">Select other specialties this doctor has experience with</p>
                            <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Anesthesiology" class="rounded">
                                        <span class="text-sm">Anesthesiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Cardiology" class="rounded">
                                        <span class="text-sm">Cardiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Dermatology" class="rounded">
                                        <span class="text-sm">Dermatology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Emergency Medicine" class="rounded">
                                        <span class="text-sm">Emergency Med</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Endocrinology" class="rounded">
                                        <span class="text-sm">Endocrinology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Family Medicine" class="rounded">
                                        <span class="text-sm">Family Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Gastroenterology" class="rounded">
                                        <span class="text-sm">Gastroenterology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="General Surgery" class="rounded">
                                        <span class="text-sm">General Surgery</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Geriatrics" class="rounded">
                                        <span class="text-sm">Geriatrics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Hematology" class="rounded">
                                        <span class="text-sm">Hematology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Infectious Disease" class="rounded">
                                        <span class="text-sm">Infectious Dis</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Internal Medicine" class="rounded">
                                        <span class="text-sm">Internal Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Nephrology" class="rounded">
                                        <span class="text-sm">Nephrology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Neurology" class="rounded">
                                        <span class="text-sm">Neurology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Obstetrics & Gynecology" class="rounded">
                                        <span class="text-sm">OB/GYN</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Oncology" class="rounded">
                                        <span class="text-sm">Oncology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Ophthalmology" class="rounded">
                                        <span class="text-sm">Ophthalmology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Orthopedics" class="rounded">
                                        <span class="text-sm">Orthopedics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Otolaryngology (ENT)" class="rounded">
                                        <span class="text-sm">ENT</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Pain Management" class="rounded">
                                        <span class="text-sm">Pain Management</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Pathology" class="rounded">
                                        <span class="text-sm">Pathology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Pediatrics" class="rounded">
                                        <span class="text-sm">Pediatrics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Physical Medicine" class="rounded">
                                        <span class="text-sm">Physical Med</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Preventive Medicine" class="rounded">
                                        <span class="text-sm">Preventive Med</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Psychiatry" class="rounded">
                                        <span class="text-sm">Psychiatry</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Pulmonology" class="rounded">
                                        <span class="text-sm">Pulmonology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Radiology" class="rounded">
                                        <span class="text-sm">Radiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Rheumatology" class="rounded">
                                        <span class="text-sm">Rheumatology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Sports Medicine" class="rounded">
                                        <span class="text-sm">Sports Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Urology" class="rounded">
                                        <span class="text-sm">Urology</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Departments * (Select one or more)</label>
                            <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Cardiology Department" class="rounded">
                                        <span class="text-sm">Cardiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Dermatology Department" class="rounded">
                                        <span class="text-sm">Dermatology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Emergency Department" class="rounded">
                                        <span class="text-sm">Emergency</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Endocrinology Department" class="rounded">
                                        <span class="text-sm">Endocrinology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="General Medicine Department" class="rounded">
                                        <span class="text-sm">General Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Gastroenterology Department" class="rounded">
                                        <span class="text-sm">Gastroenterology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="General Surgery Department" class="rounded">
                                        <span class="text-sm">General Surgery</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Obstetrics & Gynecology (OB/GYN)" class="rounded">
                                        <span class="text-sm">OB/GYN</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Internal Medicine Department" class="rounded">
                                        <span class="text-sm">Internal Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Neurology Department" class="rounded">
                                        <span class="text-sm">Neurology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Oncology Department" class="rounded">
                                        <span class="text-sm">Oncology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Ophthalmology Department" class="rounded">
                                        <span class="text-sm">Ophthalmology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Orthopedics Department" class="rounded">
                                        <span class="text-sm">Orthopedics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Otolaryngology (ENT) Department" class="rounded">
                                        <span class="text-sm">ENT</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Pediatrics Department" class="rounded">
                                        <span class="text-sm">Pediatrics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Psychiatry Department" class="rounded">
                                        <span class="text-sm">Psychiatry</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Pulmonology Department" class="rounded">
                                        <span class="text-sm">Pulmonology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Radiology Department" class="rounded">
                                        <span class="text-sm">Radiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Rheumatology Department" class="rounded">
                                        <span class="text-sm">Rheumatology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Urology Department" class="rounded">
                                        <span class="text-sm">Urology</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Qualification</label>
                            <input type="text" name="qualification"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Experience (Years)</label>
                            <input type="number" name="experience_years" min="0" value="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select name="status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="mb-4 col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" name="password" required minlength="6"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Password must be at least 6 characters long</p>
                        </div>

                        <div class="mb-4 col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Photo (Optional)</label>
                            <input type="file" name="photo" accept="image/jpeg,image/jpg,image/png,image/gif"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Upload a photo (JPEG, PNG, or GIF, max 5MB)</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="hideAddDoctorModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Add Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div id="editDoctorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Doctor Details</h3>
                    <button onclick="hideEditDoctorModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-feather="x" class="h-6 w-6"></i>
                    </button>
                </div>

                <form method="POST" id="editDoctorForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="doctor_id" id="edit_doctor_id">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="full_name" id="edit_full_name" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" id="edit_email" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                            <input type="tel" name="phone" id="edit_phone" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specialty *</label>
                            <select name="specialty" id="edit_specialty" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Specialty</option>
                                <option value="Cardiology">Cardiology</option>
                                <option value="Dermatology">Dermatology</option>
                                <option value="Emergency Medicine">Emergency Medicine</option>
                                <option value="Endocrinology">Endocrinology</option>
                                <option value="Family Medicine">Family Medicine</option>
                                <option value="Gastroenterology">Gastroenterology</option>
                                <option value="General Surgery">General Surgery</option>
                                <option value="Gynecology">Gynecology</option>
                                <option value="Internal Medicine">Internal Medicine</option>
                                <option value="Neurology">Neurology</option>
                                <option value="Obstetrics">Obstetrics</option>
                                <option value="Oncology">Oncology</option>
                                <option value="Ophthalmology">Ophthalmology</option>
                                <option value="Orthopedics">Orthopedics</option>
                                <option value="Otolaryngology (ENT)">Otolaryngology (ENT)</option>
                                <option value="Pediatrics">Pediatrics</option>
                                <option value="Psychiatry">Psychiatry</option>
                                <option value="Pulmonology">Pulmonology</option>
                                <option value="Radiology">Radiology</option>
                                <option value="Rheumatology">Rheumatology</option>
                                <option value="Urology">Urology</option>
                            </select>
                        </div>

                        <div class="mb-4 col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Specialties (Optional)</label>
                            <p class="text-xs text-gray-500 mb-2">Select other specialties this doctor has experience with</p>
                            <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Anesthesiology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Anesthesiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Cardiology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Cardiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Dermatology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Dermatology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Emergency Medicine" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Emergency Med</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Endocrinology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Endocrinology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Family Medicine" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Family Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Gastroenterology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Gastroenterology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="General Surgery" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">General Surgery</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Geriatrics" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Geriatrics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Hematology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Hematology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Infectious Disease" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Infectious Dis</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Internal Medicine" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Internal Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Nephrology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Nephrology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Neurology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Neurology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Obstetrics & Gynecology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">OB/GYN</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Oncology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Oncology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Ophthalmology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Ophthalmology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Orthopedics" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Orthopedics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Otolaryngology (ENT)" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">ENT</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Pain Management" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Pain Management</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Pathology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Pathology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Pediatrics" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Pediatrics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Physical Medicine" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Physical Med</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Preventive Medicine" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Preventive Med</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Psychiatry" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Psychiatry</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Pulmonology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Pulmonology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Radiology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Radiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Rheumatology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Rheumatology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Sports Medicine" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Sports Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="additional_specialties[]" value="Urology" class="rounded edit-spec-checkbox">
                                        <span class="text-sm">Urology</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Departments * (Select one or more)</label>
                            <div id="edit_departments_container" class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Cardiology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Cardiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Dermatology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Dermatology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Emergency Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Emergency</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Endocrinology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Endocrinology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="General Medicine Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">General Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Gastroenterology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Gastroenterology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="General Surgery Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">General Surgery</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Obstetrics & Gynecology (OB/GYN)" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">OB/GYN</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Internal Medicine Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Internal Medicine</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Neurology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Neurology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Oncology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Oncology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Ophthalmology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Ophthalmology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Orthopedics Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Orthopedics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Otolaryngology (ENT) Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">ENT</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Pediatrics Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Pediatrics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Psychiatry Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Psychiatry</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Pulmonology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Pulmonology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Radiology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Radiology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Rheumatology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Rheumatology</span>
                                    </label>
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="departments[]" value="Urology Department" class="rounded edit-dept-checkbox">
                                        <span class="text-sm">Urology</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Qualification</label>
                            <input type="text" name="qualification" id="edit_qualification"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Experience (Years)</label>
                            <input type="number" name="experience_years" id="edit_experience_years" min="0" value="0"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select name="status" id="edit_status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="mb-4 col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password (Optional)</label>
                            <input type="password" name="new_password" id="edit_new_password" minlength="6"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password. Enter new password (min 6 characters) to reset.</p>
                        </div>

                        <div class="mb-4 col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Photo (Optional)</label>
                            <input type="file" name="photo" id="edit_photo" accept="image/jpeg,image/jpg,image/png,image/gif"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Upload a new photo (JPEG, PNG, or GIF, max 5MB). Leave blank to keep current photo.</p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p class="text-sm text-yellow-800">
                            <i data-feather="alert-triangle" class="inline h-4 w-4 mr-1"></i>
                            <strong>Password Reset:</strong> Use this field to reset a doctor's password if they forget it. The doctor will need the new password to login.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="hideEditDoctorModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update Doctor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <script>
        feather.replace();

        function showAddDoctorModal() {
            document.getElementById('addDoctorModal').style.display = 'block';
            setTimeout(() => feather.replace(), 50);
        }

        function hideAddDoctorModal() {
            document.getElementById('addDoctorModal').style.display = 'none';
        }

        function showEditDoctorModal(doctor) {
            document.getElementById('edit_doctor_id').value = doctor.id;
            document.getElementById('edit_full_name').value = doctor.full_name;
            document.getElementById('edit_email').value = doctor.email;
            document.getElementById('edit_phone').value = doctor.phone;
            document.getElementById('edit_specialty').value = doctor.specialty;

            // Handle multiple departments - uncheck all first, then check doctor's departments
            const deptCheckboxes = document.querySelectorAll('.edit-dept-checkbox');
            deptCheckboxes.forEach(checkbox => checkbox.checked = false);

            if (doctor.departments_array && doctor.departments_array.length > 0) {
                doctor.departments_array.forEach(dept => {
                    deptCheckboxes.forEach(checkbox => {
                        if (checkbox.value === dept) {
                            checkbox.checked = true;
                        }
                    });
                });
            }

            // Handle additional specialties - uncheck all first, then check doctor's specialties
            const specCheckboxes = document.querySelectorAll('.edit-spec-checkbox');
            specCheckboxes.forEach(checkbox => checkbox.checked = false);

            if (doctor.specialties_array && doctor.specialties_array.length > 0) {
                doctor.specialties_array.forEach(spec => {
                    specCheckboxes.forEach(checkbox => {
                        if (checkbox.value === spec) {
                            checkbox.checked = true;
                        }
                    });
                });
            }

            document.getElementById('edit_qualification').value = doctor.qualification || '';
            document.getElementById('edit_experience_years').value = doctor.experience_years;
            document.getElementById('edit_status').value = doctor.status;

            document.getElementById('editDoctorModal').style.display = 'block';
            setTimeout(() => feather.replace(), 50);
        }

        function hideEditDoctorModal() {
            document.getElementById('editDoctorModal').style.display = 'none';
        }

        // Filter doctors by department
        function filterByDepartment(department) {
            const currentStatus = '<?= $filterStatus ?>';
            const searchQuery = document.getElementById('searchInput').value.trim();
            let url = 'manage-doctors.php?status=' + currentStatus;
            if (department !== 'all') {
                url += '&department=' + encodeURIComponent(department);
            }
            if (searchQuery) {
                url += '&search=' + encodeURIComponent(searchQuery);
            }
            window.location.href = url;
        }

        // Search doctors by name
        function searchDoctors() {
            const currentStatus = '<?= $filterStatus ?>';
            const currentDepartment = '<?= $filterDepartment ?>';
            const searchQuery = document.getElementById('searchInput').value.trim();
            let url = 'manage-doctors.php?status=' + currentStatus;
            if (currentDepartment !== 'all') {
                url += '&department=' + encodeURIComponent(currentDepartment);
            }
            if (searchQuery) {
                url += '&search=' + encodeURIComponent(searchQuery);
            }
            window.location.href = url;
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addDoctorModal');
            const editModal = document.getElementById('editDoctorModal');
            if (event.target == addModal) {
                hideAddDoctorModal();
            }
            if (event.target == editModal) {
                hideEditDoctorModal();
            }
        }
    </script>
</body>

</html>