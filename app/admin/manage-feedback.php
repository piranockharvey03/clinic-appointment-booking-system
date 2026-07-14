<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Start admin-specific session
startSession('admin');

// Redirect to login if not authenticated or not an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../public/admin-login.html');
    exit;
}

$successMessage = '';
$errorMessage = '';

// Handle feedback deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $feedbackId = intval($_POST['feedback_id']);
    
    try {
        $conn = getDBConnection();
        
        // Delete feedback
        $deleteStmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
        $deleteStmt->bind_param("i", $feedbackId);
        
        if ($deleteStmt->execute()) {
            $successMessage = "Feedback deleted successfully.";
            logActivity($conn, $_SESSION['user_id'], $_SESSION['user_name'], 'admin', 'delete_feedback', "Deleted feedback ID: {$feedbackId}");
        } else {
            $errorMessage = "Failed to delete feedback. Please try again.";
        }
        $deleteStmt->close();
        
        closeDBConnection($conn);
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
        error_log("Delete feedback error: " . $e->getMessage());
    }
}

// Load all feedback with filtering
$feedback = [];
$filterService = isset($_GET['service']) ? $_GET['service'] : '';
$filterRating = isset($_GET['rating']) ? intval($_GET['rating']) : '';

try {
    $conn = getDBConnection();
    
    // Build query with filters
    $query = "SELECT * FROM feedback";
    $params = [];
    $types = '';
    
    if (!empty($filterService)) {
        $query .= " WHERE service = ?";
        $params[] = $filterService;
        $types .= 's';
    }
    
    if (!empty($filterRating)) {
        if (empty($filterService)) {
            $query .= " WHERE rating = ?";
        } else {
            $query .= " AND rating = ?";
        }
        $params[] = $filterRating;
        $types .= 'i';
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $feedback[] = $row;
        }
        $result->free();
    }
    
    $stmt->close();
    
    // Get unique services for filter dropdown
    $services = [];
    $serviceResult = $conn->query("SELECT DISTINCT service FROM feedback ORDER BY service");
    if ($serviceResult) {
        while ($row = $serviceResult->fetch_assoc()) {
            $services[] = $row['service'];
        }
        $serviceResult->free();
    }
    
    // Get statistics
    $stats = [
        'total' => count($feedback),
        'avg_rating' => 0,
        'by_service' => []
    ];
    
    if (count($feedback) > 0) {
        $totalRating = 0;
        foreach ($feedback as $f) {
            $totalRating += $f['rating'];
            $service = $f['service'];
            if (!isset($stats['by_service'][$service])) {
                $stats['by_service'][$service] = ['count' => 0, 'total_rating' => 0];
            }
            $stats['by_service'][$service]['count']++;
            $stats['by_service'][$service]['total_rating'] += $f['rating'];
        }
        $stats['avg_rating'] = round($totalRating / count($feedback), 1);
        
        // Calculate average per service
        foreach ($stats['by_service'] as $service => $data) {
            $stats['by_service'][$service]['avg'] = round($data['total_rating'] / $data['count'], 1);
        }
    }
    
    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Failed to load feedback: " . $e->getMessage());
    $errorMessage = "Failed to load feedback. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback | MediCare Clinic</title>
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
                        <a href="manage-doctors.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="users" class="mr-3 h-5 w-5"></i>
                            Manage Doctors
                        </a>
                        <a href="manage-patients.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="user" class="mr-3 h-5 w-5"></i>
                            Manage Patients
                        </a>
                        <a href="manage-feedback.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
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
                        <h1 class="text-2xl font-semibold text-gray-900">Manage Feedback</h1>
                    </div>
                    <div class="ml-4 flex items-center md:ml-6">
                        <div class="ml-3 relative">
                            <div class="flex items-center">
                                <span class="text-gray-700 text-sm font-medium mr-2"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                                <i data-feather="user" class="h-5 w-5 text-gray-500"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <main class="flex-1 relative overflow-y-auto focus:outline-none">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <!-- Success/Error Messages -->
                        <?php if ($successMessage): ?>
                            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                                <span class="block sm:inline"><?= htmlspecialchars($successMessage) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($errorMessage): ?>
                            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                                <span class="block sm:inline"><?= htmlspecialchars($errorMessage) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i data-feather="message-square" class="h-6 w-6 text-blue-600"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Feedback</dt>
                                                <dd class="flex items-baseline">
                                                    <div class="text-2xl font-semibold text-gray-900"><?= $stats['total'] ?></div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i data-feather="star" class="h-6 w-6 text-yellow-600"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Average Rating</dt>
                                                <dd class="flex items-baseline">
                                                    <div class="text-2xl font-semibold text-gray-900"><?= $stats['avg_rating'] ?>/5</div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i data-feather="grid" class="h-6 w-6 text-purple-600"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Services Reviewed</dt>
                                                <dd class="flex items-baseline">
                                                    <div class="text-2xl font-semibold text-gray-900"><?= count($stats['by_service']) ?></div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="bg-white shadow rounded-lg mb-6 p-4">
                            <form method="GET" class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Service</label>
                                    <select name="service" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                        <option value="">All Services</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?= htmlspecialchars($service) ?>" <?= $filterService === $service ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($service) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Rating</label>
                                    <select name="rating" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                        <option value="">All Ratings</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>" <?= $filterRating === $i ? 'selected' : '' ?>>
                                                <?= $i ?> Star<?= $i !== 1 ? 's' : '' ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                        Apply Filters
                                    </button>
                                    <a href="manage-feedback.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                                        Clear
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Feedback Table -->
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">All Feedback</h3>
                                <div class="text-sm text-gray-500">
                                    <?= count($feedback) ?> feedback item<?= count($feedback) !== 1 ? 's' : '' ?>
                                </div>
                            </div>
                            <div class="border-t border-gray-200">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feedback</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (empty($feedback)): ?>
                                                <tr>
                                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No feedback found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($feedback as $item): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            <?= htmlspecialchars($item['name']) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars($item['email']) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars($item['service']) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <div class="flex items-center">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <?php if ($i <= $item['rating']): ?>
                                                                        <i data-feather="star" class="h-4 w-4 text-yellow-400 fill-current"></i>
                                                                    <?php else: ?>
                                                                        <i data-feather="star" class="h-4 w-4 text-gray-300"></i>
                                                                    <?php endif; ?>
                                                                <?php endfor; ?>
                                                                <span class="ml-2"><?= $item['rating'] ?>/5</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs">
                                                            <div class="truncate" title="<?= htmlspecialchars($item['feedback']) ?>">
                                                                <?= htmlspecialchars(substr($item['feedback'], 0, 100)) ?><?= strlen($item['feedback']) > 100 ? '...' : '' ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= date('M d, Y', strtotime($item['created_at'])) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="feedback_id" value="<?= $item['id'] ?>">
                                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm">
                                                                    Delete
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
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <script>
        feather.replace();
    </script>
</body>

</html>
