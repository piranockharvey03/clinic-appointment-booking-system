<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Redirect to login if not authenticated or not an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../public/admin-login.html');
    exit;
}

// Load statistics from database
$stats = [
    'total_doctors' => 0,
    'active_doctors' => 0,
    'inactive_doctors' => 0,
    'total_appointments' => 0,
    'pending_appointments' => 0,
    'completed_appointments' => 0
];

try {
    $conn = getDBConnection();

    // Get doctor statistics
    $result = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
        FROM doctors");
    if ($row = $result->fetch_assoc()) {
        $stats['total_doctors'] = $row['total'];
        $stats['active_doctors'] = $row['active'];
        $stats['inactive_doctors'] = $row['inactive'];
    }

    // Get appointment statistics
    $result = $conn->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM appointments");
    if ($row = $result->fetch_assoc()) {
        $stats['total_appointments'] = $row['total'];
        $stats['pending_appointments'] = $row['pending'];
        $stats['completed_appointments'] = $row['completed'];
    }

    // Get recent doctors
    $recentDoctors = [];
    $result = $conn->query("SELECT id, full_name, email, specialty, department, status, created_at 
                           FROM doctors 
                           ORDER BY created_at DESC 
                           LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        $recentDoctors[] = $row;
    }

    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MediCare Clinic</title>
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
                        <a href="new-admin-dashboard.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                            <i data-feather="home" class="mr-3 h-5 w-5"></i>
                            Dashboard
                        </a>
                        <a href="manage-doctors.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="users" class="mr-3 h-5 w-5"></i>
                            Manage Doctors
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
                        <h1 class="text-2xl font-semibold text-gray-900">Admin Dashboard</h1>
                    </div>
                    <div class="ml-4 flex items-center md:ml-6">
                        <div class="relative mr-4">
                            <button id="adminNotificationBtn" class="relative p-1 text-gray-600 hover:text-gray-900 focus:outline-none" type="button" aria-label="Notifications">
                                <i data-feather="bell" class="h-6 w-6"></i>
                                <span id="adminNotificationBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                            </button>
                            <div id="adminNotificationDropdown" class="hidden absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-sm sm:w-80 bg-white rounded-md shadow-lg overflow-hidden z-50">
                                <div class="py-2">
                                    <div class="px-4 py-2 text-sm font-medium text-gray-700 border-b border-gray-200 flex justify-between items-center">
                                        <span>Notifications</span>
                                        <button id="adminMarkAllReadBtn" class="text-xs bg-blue-500 hover:bg-blue-700 px-2 py-1 rounded text-white" style="display: none;" type="button">Mark all read</button>
                                    </div>
                                    <div id="adminNotificationList" class="max-h-96 overflow-y-auto">
                                        <div class="px-4 py-3 text-center text-gray-500">Loading notifications...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
                            <!-- Total Doctors -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i data-feather="users" class="h-6 w-6 text-blue-600"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Doctors</dt>
                                                <dd class="flex items-baseline">
                                                    <div class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($stats['total_doctors']) ?></div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-5 py-3">
                                    <div class="text-sm">
                                        <a href="manage-doctors.php" class="font-medium text-blue-600 hover:text-blue-500">View all →</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Doctors -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i data-feather="check-circle" class="h-6 w-6 text-green-600"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Active Doctors</dt>
                                                <dd class="flex items-baseline">
                                                    <div class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($stats['active_doctors']) ?></div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-5 py-3">
                                    <div class="text-sm">
                                        <a href="manage-doctors.php?status=active" class="font-medium text-blue-600 hover:text-blue-500">View active →</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Appointments -->
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-5">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i data-feather="calendar" class="h-6 w-6 text-purple-600"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Appointments</dt>
                                                <dd class="flex items-baseline">
                                                    <div class="text-2xl font-semibold text-gray-900"><?= htmlspecialchars($stats['total_appointments']) ?></div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-5 py-3">
                                    <div class="text-sm">
                                        <a href="reports.php" class="font-medium text-blue-600 hover:text-blue-500">View reports →</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Doctors Table -->
                        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                            <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Doctors</h3>
                                <a href="manage-doctors.php" class="text-sm text-blue-600 hover:text-blue-500">View all →</a>
                            </div>
                            <div class="border-t border-gray-200">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialty</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (empty($recentDoctors)): ?>
                                                <tr>
                                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No doctors found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recentDoctors as $doctor): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            <?= htmlspecialchars($doctor['full_name']) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars($doctor['email']) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars($doctor['specialty']) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars($doctor['department']) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <?php if ($doctor['status'] === 'active'): ?>
                                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                                            <?php else: ?>
                                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= date('M d, Y', strtotime($doctor['created_at'])) ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                            <i data-feather="user-plus" class="h-6 w-6 text-white"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <h3 class="text-lg font-medium text-gray-900">Add New Doctor</h3>
                                            <p class="mt-1 text-sm text-gray-500">Register a new doctor to the system</p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <a href="manage-doctors.php?action=add" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                            Add Doctor
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                            <i data-feather="file-text" class="h-6 w-6 text-white"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <h3 class="text-lg font-medium text-gray-900">View Reports</h3>
                                            <p class="mt-1 text-sm text-gray-500">Access system reports and analytics</p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <a href="reports.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700">
                                            View Reports
                                        </a>
                                    </div>
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
    <script src="../assets/js/notification-dropdown.js"></script>
    <script>
        feather.replace();

        document.addEventListener('DOMContentLoaded', function() {
            NotificationDropdown.init({
                buttonSelector: '#adminNotificationBtn',
                dropdownSelector: '#adminNotificationDropdown',
                badgeSelector: '#adminNotificationBadge',
                listSelector: '#adminNotificationList',
                markAllSelector: '#adminMarkAllReadBtn',
                markReadButtonClass: 'admin-mark-read-btn',
                fetchUrl: '../includes/get-notifications.php',
                markReadUrl: '../includes/mark-notifications-read.php',
                pollIntervalMs: 30000,
                emptyText: 'No new notifications',
                iconMap: {
                    new_appointment: '<i data-feather="calendar" class="h-4 w-4 text-blue-500"></i>',
                    approved: '<i data-feather="check-circle" class="h-4 w-4 text-green-500"></i>',
                    canceled: '<i data-feather="x-circle" class="h-4 w-4 text-red-500"></i>',
                    rescheduled: '<i data-feather="refresh-cw" class="h-4 w-4 text-blue-500"></i>',
                    completed: '<i data-feather="check-square" class="h-4 w-4 text-indigo-500"></i>'
                }
            });
        });
    </script>
</body>

</html>