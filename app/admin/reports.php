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

// Load report data
$reportData = [
    'total_appointments' => 0,
    'appointments_by_status' => [],
    'appointments_by_doctor' => [],
    'appointments_by_month' => [],
    'activity_logs' => []
];

try {
    $conn = getDBConnection();

    // Get total appointments
    $result = $conn->query("SELECT COUNT(*) as total FROM appointments");
    if ($row = $result->fetch_assoc()) {
        $reportData['total_appointments'] = $row['total'];
    }

    // Get appointments by status
    $result = $conn->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
    while ($row = $result->fetch_assoc()) {
        $reportData['appointments_by_status'][] = $row;
    }

    // Get appointments by doctor
    $result = $conn->query("SELECT doctor_name, COUNT(*) as count FROM appointments GROUP BY doctor_name ORDER BY count DESC LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $reportData['appointments_by_doctor'][] = $row;
    }

    // Get appointments by month (last 6 months)
    $result = $conn->query("SELECT DATE_FORMAT(appointment_date, '%Y-%m') as month, COUNT(*) as count 
                           FROM appointments 
                           WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                           GROUP BY month 
                           ORDER BY month DESC");
    while ($row = $result->fetch_assoc()) {
        $reportData['appointments_by_month'][] = $row;
    }

    // Get recent doctor activity logs (last 200 entries)
    $result = $conn->query("SELECT user_name, user_role, action, description, ip_address, created_at
                           FROM activity_logs
                           WHERE user_role = 'doctor'
                           ORDER BY created_at DESC
                           LIMIT 200");
    while ($row = $result->fetch_assoc()) {
        $reportData['activity_logs'][] = $row;
    }

    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Reports error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | MediCare Admin</title>
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
                        <a href="manage-feedback.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                            <i data-feather="message-square" class="mr-3 h-5 w-5"></i>
                            Feedback
                        </a>
                        <a href="reports.php" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
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
                        <h1 class="text-2xl font-semibold text-gray-900">System Reports</h1>
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

                        <!-- Overall Statistics -->
                        <div class="mb-8">
                            <div class="bg-white overflow-hidden shadow rounded-lg">
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                            <i data-feather="calendar" class="h-8 w-8 text-white"></i>
                                        </div>
                                        <div class="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt class="text-sm font-medium text-gray-500 truncate">Total Appointments</dt>
                                                <dd class="flex items-baseline">
                                                    <div class="text-3xl font-semibold text-gray-900"><?= htmlspecialchars($reportData['total_appointments']) ?></div>
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Appointments by Status -->
                        <div class="mb-8">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Appointments by Status</h2>
                            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (empty($reportData['appointments_by_status'])): ?>
                                                <tr>
                                                    <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No data available</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($reportData['appointments_by_status'] as $row): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">
                                                            <?= htmlspecialchars($row['status']) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars($row['count']) ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Top Doctors -->
                        <div class="mb-8">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Top 10 Doctors by Appointments</h2>
                            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor Name</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Appointments</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (empty($reportData['appointments_by_doctor'])): ?>
                                                <tr>
                                                    <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No data available</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($reportData['appointments_by_doctor'] as $row): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            <?= htmlspecialchars($row['doctor_name']) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars($row['count']) ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Trend -->
                        <div class="mb-8">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Monthly Appointment Trend (Last 6 Months)</h2>
                            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointments</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (empty($reportData['appointments_by_month'])): ?>
                                                <tr>
                                                    <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">No data available</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($reportData['appointments_by_month'] as $row): ?>
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            <?= date('F Y', strtotime($row['month'] . '-01')) ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars($row['count']) ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Doctor Activity Logs -->
                        <div class="mb-8">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Doctor Activity Logs (Last 200)</h2>
                            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php if (empty($reportData['activity_logs'])): ?>
                                                <tr>
                                                    <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">No activity logs recorded yet</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($reportData['activity_logs'] as $log): ?>
                                                    <?php
                                                    $actionColors = [
                                                        'login'                  => 'bg-green-100 text-green-800',
                                                        'logout'                 => 'bg-gray-100 text-gray-800',
                                                        'login_failed'           => 'bg-red-100 text-red-800',
                                                        'approve_appointment'    => 'bg-blue-100 text-blue-800',
                                                        'cancel_appointment'     => 'bg-red-100 text-red-800',
                                                        'reschedule_appointment' => 'bg-yellow-100 text-yellow-800',
                                                        'complete_appointment'   => 'bg-purple-100 text-purple-800',
                                                        'change_password'        => 'bg-orange-100 text-orange-800',
                                                    ];
                                                    $badgeClass = $actionColors[$log['action']] ?? 'bg-gray-100 text-gray-800';
                                                    $actionLabel = ucwords(str_replace('_', ' ', $log['action']));
                                                    ?>
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            <?= htmlspecialchars($log['user_name']) ?>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $badgeClass ?>">
                                                                <?= htmlspecialchars($actionLabel) ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                                                            <?= htmlspecialchars($log['description'] ?? '—') ?>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars($log['ip_address'] ?? '—') ?>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                            <?= htmlspecialchars(date('M j, Y H:i:s', strtotime($log['created_at']))) ?>
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