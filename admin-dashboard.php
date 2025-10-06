<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: adminlogin.php');
    exit;
}

// Load appointments data
$jsonFile = __DIR__ . '/data/appointments.json';
$appointments = [];
if (file_exists($jsonFile)) {
    $appointments = json_decode(file_get_contents($jsonFile), true) ?? [];
}

// Calculate statistics
$stats = [
    'total' => count($appointments),
    'pending' => 0,
    'approved' => 0,
    'rescheduled' => 0,
    'canceled' => 0
];

foreach ($appointments as $appt) {
    $status = strtolower($appt['status'] ?? 'pending');
    if (isset($stats[$status])) {
        $stats[$status]++;
    }
}

// Get recent appointments (last 5)
$recentAppointments = array_slice(array_reverse($appointments), 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | MediCare Clinic</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar sidebar-expanded bg-blue-800 text-white">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-blue-700">
                    <div class="flex items-center">
                        <i data-feather="heart" class="h-8 w-8 text-white"></i>
                        <span class="ml-2 text-xl font-bold">MediCare</span>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <nav class="p-4">
                        <div class="space-y-1">
                            <a href="admin-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="admin-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="admin-login.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="log-out" class="mr-3 h-5 w-5"></i>
                                Logout
                            </a>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Main content -->
        <div class="main-content flex-1 overflow-auto">
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-lg font-semibold text-gray-900">Admin Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <i data-feather="user" class="h-6 w-6 text-blue-600"></i>
                    </div>
                </div>
            </header>
            <main class="p-4 sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 uppercase">Total Appointments</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['total'] ?></p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i data-feather="calendar" class="h-8 w-8 text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 uppercase">Pending</p>
                                <p class="text-3xl font-bold text-yellow-600 mt-2"><?= $stats['pending'] ?></p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-full">
                                <i data-feather="clock" class="h-8 w-8 text-yellow-600"></i>
                            </div>
                        </div>
                        <a href="admin-appointments.php?tab=pending" class="text-xs text-blue-600 hover:underline mt-3 inline-block">View all →</a>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 uppercase">Approved</p>
                                <p class="text-3xl font-bold text-green-600 mt-2"><?= $stats['approved'] ?></p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i data-feather="check-circle" class="h-8 w-8 text-green-600"></i>
                            </div>
                        </div>
                        <a href="admin-appointments.php?tab=approved" class="text-xs text-blue-600 hover:underline mt-3 inline-block">View all →</a>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 uppercase">Rescheduled</p>
                                <p class="text-3xl font-bold text-purple-600 mt-2"><?= $stats['rescheduled'] ?></p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <i data-feather="refresh-cw" class="h-8 w-8 text-purple-600"></i>
                            </div>
                        </div>
                        <a href="admin-appointments.php?tab=rescheduled" class="text-xs text-blue-600 hover:underline mt-3 inline-block">View all →</a>
                    </div>
                </div>

                <!-- Recent Appointments -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">Recent Appointments</h2>
                            <a href="admin-appointments.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all →</a>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if (!empty($recentAppointments)): ?>
                            <?php foreach ($recentAppointments as $appt): ?>
                                <?php 
                                    $status = strtolower($appt['status'] ?? 'pending');
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'approved' => 'bg-green-100 text-green-800 border-green-200',
                                        'rescheduled' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'canceled' => 'bg-red-100 text-red-800 border-red-200'
                                    ];
                                    $badgeClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                ?>
                                <div class="p-5 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <i data-feather="user" class="h-5 w-5 text-blue-600"></i>
                                                </div>
                                                <div>
                                                    <h3 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($appt['patientName'] ?? 'Unknown Patient') ?></h3>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <span class="font-medium">Dr. <?= htmlspecialchars($appt['doctorName'] ?? 'Unknown') ?></span> • 
                                                        <?= htmlspecialchars($appt['department'] ?? '') ?>
                                                    </p>
                                                    <p class="text-xs text-gray-400 mt-0.5">
                                                        <i data-feather="calendar" class="h-3 w-3 inline"></i>
                                                        <?= htmlspecialchars($appt['date'] ?? '') ?> at <?= htmlspecialchars($appt['time'] ?? '') ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1.5 text-xs font-semibold rounded-full border <?= $badgeClass ?>">
                                            <?= ucfirst($status) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-12 text-center text-gray-500">
                                <i data-feather="inbox" class="h-16 w-16 mx-auto mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium">No appointments yet</p>
                                <p class="text-sm mt-1">Appointments will appear here once patients start booking</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        feather.replace();
    </script>
</body>

</html>