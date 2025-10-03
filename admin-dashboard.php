<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: adminlogin.php');
    exit;
}

// Use the correct path!
$jsonFile = __DIR__ . '/data/appointments.json';
$appointments = [];
if (file_exists($jsonFile)) {
    $appointments = json_decode(file_get_contents($jsonFile), true) ?? [];
}

// Approve action
if (isset($_POST['approve_id'])) {
    foreach ($appointments as &$appt) {
        if ($appt['id'] === $_POST['approve_id']) {
            $appt['status'] = 'approved';
            break;
        }
    }
    file_put_contents($jsonFile, json_encode($appointments, JSON_PRETTY_PRINT));
    header("Location: admin-dashboard.php");
    exit;
}

// Reschedule action
if (isset($_POST['reschedule_id']) && isset($_POST['new_date']) && isset($_POST['new_time'])) {
    foreach ($appointments as &$appt) {
        if ($appt['id'] === $_POST['reschedule_id']) {
            $appt['date'] = $_POST['new_date'];
            $appt['time'] = $_POST['new_time'];
            $appt['status'] = 'rescheduled';
            break;
        }
    }
    file_put_contents($jsonFile, json_encode($appointments, JSON_PRETTY_PRINT));
    header("Location: admin-dashboard.php");
    exit;
}

// Filter: Only show pending or empty status
$filteredAppointments = array_filter($appointments, function ($appt) {
    $status = strtolower($appt['status'] ?? 'pending');
    return $status === 'pending' || $status === '';
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
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
                            <a href="logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
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
                <div class="max-w-5xl mx-auto bg-white shadow rounded-lg p-8">
                    <h2 class="text-2xl font-bold mb-6 text-gray-900">Pending Appointments</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($filteredAppointments)): ?>
                                    <?php foreach ($filteredAppointments as $appt): ?>
                                        <tr>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['patientName'] ?? ''); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['doctorName'] ?? ''); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['department'] ?? ''); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['date'] ?? ''); ?></td>
                                            <td class="px-4 py-2"><?php echo htmlspecialchars($appt['time'] ?? ''); ?></td>
                                            <td class="px-4 py-2 capitalize"><?php echo htmlspecialchars($appt['status'] ?? 'pending'); ?></td>
                                            <td class="px-4 py-2 space-x-2">
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="approve_id" value="<?php echo htmlspecialchars($appt['id']); ?>">
                                                    <button type="submit" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">Approve</button>
                                                </form>
                                                <button onclick="showReschedule('<?php echo $appt['id']; ?>', '<?php echo htmlspecialchars($appt['date']); ?>', '<?php echo htmlspecialchars($appt['time']); ?>')" class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">Reschedule</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-4 py-2 text-center text-gray-500">No pending appointments found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Reschedule Modal -->
                    <div id="rescheduleModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50 hidden">
                        <div class="bg-white rounded-lg p-6 w-full max-w-sm">
                            <h3 class="text-lg font-bold mb-4">Reschedule Appointment</h3>
                            <form method="post" id="rescheduleForm">
                                <input type="hidden" name="reschedule_id" id="reschedule_id">
                                <label class="block mb-2 text-sm font-medium">New Date</label>
                                <input type="date" name="new_date" id="new_date" required class="w-full px-3 py-2 border rounded mb-4">
                                <label class="block mb-2 text-sm font-medium">New Time</label>
                                <input type="time" name="new_time" id="new_time" required class="w-full px-3 py-2 border rounded mb-4">
                                <div class="flex justify-end space-x-2">
                                    <button type="button" onclick="closeReschedule()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        function showReschedule(id, date, time) {
                            document.getElementById('reschedule_id').value = id;
                            document.getElementById('new_date').value = date;
                            document.getElementById('new_time').value = time;
                            document.getElementById('rescheduleModal').classList.remove('hidden');
                        }

                        function closeReschedule() {
                            document.getElementById('rescheduleModal').classList.add('hidden');
                        }
                        window.onclick = function(event) {
                            var modal = document.getElementById('rescheduleModal');
                            if (event.target === modal) {
                                closeReschedule();
                            }
                        }
                    </script>
                </div>
            </main>
        </div>
    </div>
    <script>
        feather.replace();
    </script>
</body>

</html>