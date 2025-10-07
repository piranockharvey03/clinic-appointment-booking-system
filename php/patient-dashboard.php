<?php
session_start();
require_once 'db-config.php';

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login if not authenticated or not a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../html/login.html');
    exit;
}

$fullName = $_SESSION['user_name'];
$firstName = trim(explode(' ', $fullName)[0]);

// Load appointments data from database
$patientAppointments = [];
$stats = [
    'total' => 0,
    'upcoming' => 0,
    'approved' => 0,
    'pending' => 0,
    'rescheduled' => 0,
    'canceled' => 0
];

try {
    $conn = getDBConnection();
    
    // Get all appointments (in production, filter by patient_id)
    $result = $conn->query("SELECT *, appointment_id as id, appointment_date as date, appointment_time as time FROM appointments ORDER BY created_at DESC");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $patientAppointments[] = [
                'id' => $row['appointment_id'],
                'patientName' => $row['patient_name'],
                'phone' => $row['phone'],
                'department' => $row['department'],
                'doctorId' => $row['doctor_id'],
                'doctorName' => $row['doctor_name'],
                'doctorSpecialty' => $row['doctor_specialty'],
                'doctorPhoto' => $row['doctor_photo'],
                'date' => $row['appointment_date'],
                'time' => $row['appointment_time'],
                'reason' => $row['reason'],
                'notes' => $row['notes'],
                'status' => $row['status'],
                'createdAt' => $row['created_at']
            ];
            
            // Count statistics
            $status = strtolower($row['status']);
            if ($status === 'pending') $stats['pending']++;
            if ($status === 'approved') $stats['approved']++;
            if ($status === 'rescheduled') $stats['rescheduled']++;
            if ($status === 'canceled') $stats['canceled']++;
            if (in_array($status, ['pending', 'approved', 'rescheduled'])) $stats['upcoming']++;
        }
        $result->free();
    }
    
    $stats['total'] = count($patientAppointments);
    
    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Failed to load appointments: " . $e->getMessage());
}

// Get upcoming appointments (pending, approved, rescheduled - not canceled or past)
$upcomingAppointments = array_filter($patientAppointments, function ($appt) {
    $status = strtolower($appt['status'] ?? 'pending');
    return !in_array($status, ['canceled', 'past'], true);
});
$upcomingAppointments = array_slice($upcomingAppointments, 0, 3);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <style>
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar bg-blue-800 text-white" id="sidebar">
            <div class="flex flex-col h-full">
                <!-- Sidebar header -->
                <div class="flex items-center justify-between p-4 border-b border-blue-700">
                    <div class="flex items-center">
                        <i data-feather="heart" class="h-8 w-8 text-white"></i>
                        <span class="ml-2 text-xl font-bold">MediCare</span>
                    </div>
                    <button class="text-blue-200 hover:text-white md:hidden" id="menuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                </div>

                <!-- Sidebar content -->
                <div class="flex-1 overflow-y-auto">
                    <nav class="p-4">
                        <div class="space-y-1">
                            <a href="../html/patient-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="../html/patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                            <a href="../html/patient-book.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="plus-circle" class="mr-3 h-5 w-5"></i>
                                Book Appointment
                            </a>
                            <a href="../html/patient-records.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="file-text" class="mr-3 h-5 w-5"></i>
                                Medical Records
                            </a>
                            <a href="../html/patient-prescriptions.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="file-plus" class="mr-3 h-5 w-5"></i>
                                Prescriptions
                            </a>
                            <a href="../html/patient-messages.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="message-square" class="mr-3 h-5 w-5"></i>
                                Messages
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="../html/patient-profile.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="user" class="mr-3 h-5 w-5"></i>
                                Profile
                            </a>
                            <a href="../html/patient-settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="settings" class="mr-3 h-5 w-5"></i>
                                Settings
                            </a>
                            <a href="../php/logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="log-out" class="mr-3 h-5 w-5"></i>
                                Logout
                            </a>
                        </div>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="main-content flex-1 overflow-auto w-full">
            <!-- Top navigation -->
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-1 text-gray-400 hover:text-gray-500">
                            <i data-feather="bell" class="h-6 w-6"></i>
                        </button>
                        <div class="relative">
                            <button class="flex items-center space-x-2">
                                <img class="h-8 w-8 rounded-full" src="http://static.photos/people/200x200/1" alt="User profile">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($fullName); ?></span>
                                <i data-feather="chevron-down" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
  <!--piranockharvey03--> 
            <!-- Dashboard content -->
            <main class="p-4 sm:px-6 lg:px-8">
                <!-- Welcome banner -->
                <div class="bg-blue-600 rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white">Welcome back, <?php echo htmlspecialchars($firstName); ?>!</h2>
                            <p class="mt-1 text-blue-100">Here's what's happening with your health today.</p>
                        </div>
                        <a href="../html/patient-book.html" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-blue-600 bg-white hover:bg-blue-50">
                            Book Appointment
                            <i data-feather="plus" class="ml-2"></i>
                        </a>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                    <i data-feather="calendar" class="h-6 w-6 text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dt class="text-sm font-medium text-gray-500 truncate">Upcoming Appointments</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900"><?= $stats['upcoming'] ?></div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                    <i data-feather="check-circle" class="h-6 w-6 text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900"><?= $stats['approved'] ?></div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                    <i data-feather="file-text" class="h-6 w-6 text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900"><?= $stats['pending'] ?></div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                    <i data-feather="message-square" class="h-6 w-6 text-white"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Appointments</dt>
                                    <dd class="flex items-baseline">
                                        <div class="text-2xl font-semibold text-gray-900"><?= $stats['total'] ?></div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Appointments -->
                <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Upcoming Appointments</h3>
                            <a href="../html/patient-appointments.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all →</a>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if (!empty($upcomingAppointments)): ?>
                            <?php foreach ($upcomingAppointments as $appt): ?>
                                <?php
                                $status = strtolower($appt['status'] ?? 'pending');
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'approved' => 'bg-green-100 text-green-800 border-green-200',
                                    'rescheduled' => 'bg-blue-100 text-blue-800 border-blue-200'
                                ];
                                $badgeClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                ?>
                                <div class="appointment-card transition duration-300 ease-in-out p-5 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <?php if (!empty($appt['doctorPhoto'])): ?>
                                                <img class="h-12 w-12 rounded-full" src="<?= htmlspecialchars($appt['doctorPhoto']) ?>" alt="Doctor">
                                            <?php else: ?>
                                                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <i data-feather="user" class="h-6 w-6 text-blue-600"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="ml-4">
                                                <h4 class="text-sm font-semibold text-gray-900">Dr. <?= htmlspecialchars($appt['doctorName'] ?? 'Unknown') ?></h4>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <?= htmlspecialchars($appt['doctorSpecialty'] ?? '') ?> • <?= htmlspecialchars($appt['department'] ?? '') ?>
                                                </p>
                                                <div class="flex items-center text-xs text-gray-400 mt-1">
                                                    <i data-feather="calendar" class="h-3 w-3 mr-1"></i>
                                                    <span><?= htmlspecialchars($appt['date'] ?? '') ?> at <?= htmlspecialchars($appt['time'] ?? '') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-3 py-1.5 text-xs font-semibold rounded-full border <?= $badgeClass ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                            <a href="../html/patient-appointments.php" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-12 text-center text-gray-500">
                                <i data-feather="calendar" class="h-16 w-16 mx-auto mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium">No upcoming appointments</p>
                                <p class="text-sm mt-1 mb-4">Book your first appointment to get started</p>
                                <a href="../html/patient-book.html" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                    <i data-feather="plus" class="h-4 w-4 mr-2"></i>
                                    Book Appointment
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script>
        feather.replace();
    </script>
</body>

</html>

