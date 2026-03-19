<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Redirect to login if not authenticated or not a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor') {
    header('Location: ../../public/doctor-login.html');
    exit;
}

// Load appointments data from database
$appointments = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rescheduled' => 0,
    'canceled' => 0
];

try {
    $conn = getDBConnection();

    // Get only this doctor's appointments
    $doctorId = (string)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT *, appointment_id as id, appointment_date as date, appointment_time as time FROM appointments WHERE doctor_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = [
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
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }
        $result->free();
    }
    $stmt->close();

    $stats['total'] = count($appointments);

    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Failed to load appointments: " . $e->getMessage());
}

// Get recent appointments (last 5)
$recentAppointments = array_slice($appointments, 0, 5);

// Get today's appointments
$todayDate = date('Y-m-d');
$todayAppointments = array_filter($appointments, function ($appt) use ($todayDate) {
    return $appt['date'] === $todayDate;
});
// Sort today's appointments by time
usort($todayAppointments, function ($a, $b) {
    return strcmp($a['time'], $b['time']);
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar bg-blue-800 text-white" id="sidebar">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-blue-700">
                    <div class="flex items-center">
                        <i data-feather="heart" class="h-8 w-8 text-white"></i>
                        <span class="ml-2 text-xl font-bold">MediCare</span>
                    </div>
                    <button class="text-blue-200 hover:text-white md:hidden" id="menuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <nav class="p-4">
                        <div class="space-y-1">
                            <a href="doctor-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="doctor-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="doctor-settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="settings" class="mr-3 h-5 w-5"></i>
                                Settings
                            </a>
                            <a href="doctor-logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
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
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">Doctor Dashboard</h1>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <button id="notificationBtn" class="relative p-1 text-gray-600 hover:text-gray-900 focus:outline-none">
                                <i data-feather="bell" class="h-6 w-6"></i>
                                <span id="notificationBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                            </button>
                            <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-sm sm:w-80 bg-white rounded-md shadow-lg overflow-hidden z-50">
                                <div class="py-1">
                                    <div class="px-4 py-2 bg-blue-600 text-white font-medium flex justify-between items-center">
                                        <span>Notifications</span>
                                        <button id="markAllReadBtn" class="text-xs bg-blue-500 hover:bg-blue-700 px-2 py-1 rounded text-white" style="display: none;">
                                            Mark All Read
                                        </button>
                                    </div>
                                    <div id="notificationList" class="max-h-96 overflow-y-auto">
                                        <div class="px-4 py-3 text-center text-gray-500">Loading notifications...</div>
                                    </div>
                                    <div class="px-4 py-2 bg-gray-100 text-right">
                                        <a href="doctor-appointments.php" class="text-sm text-blue-600 hover:underline">View all</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="hidden sm:inline text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <i data-feather="user" class="h-6 w-6 text-blue-600"></i>
                    </div>
                </div>
            </header>
            <main class="p-4 sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
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
                        <a href="doctor-appointments.php?tab=pending" class="text-xs text-blue-600 hover:underline mt-3 inline-block">View all →</a>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 uppercase">Confirmed</p>
                                <p class="text-3xl font-bold text-green-600 mt-2"><?= $stats['approved'] ?></p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <i data-feather="check-circle" class="h-8 w-8 text-green-600"></i>
                            </div>
                        </div>
                        <a href="doctor-appointments.php?tab=approved" class="text-xs text-blue-600 hover:underline mt-3 inline-block">View all →</a>
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
                        <a href="doctor-appointments.php?tab=rescheduled" class="text-xs text-blue-600 hover:underline mt-3 inline-block">View all →</a>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="bg-white rounded-lg shadow-md mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-bold text-gray-900">Today's Appointments</h2>
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= date('M d, Y') ?>
                                </span>
                            </div>
                            <?php if (count($todayAppointments) > 0): ?>
                                <span class="text-sm text-gray-600">
                                    <span class="font-semibold text-blue-600"><?= count($todayAppointments) ?></span> appointment<?= count($todayAppointments) !== 1 ? 's' : '' ?> today
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if (!empty($todayAppointments)): ?>
                            <?php foreach ($todayAppointments as $appt): ?>
                                <?php
                                $status = strtolower($appt['status'] ?? 'pending');
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'approved' => 'bg-green-100 text-green-800 border-green-200',
                                    'rescheduled' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'canceled' => 'bg-red-100 text-red-800 border-red-200'
                                ];
                                $badgeClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';

                                // Parse time to determine if it's upcoming or past
                                $currentTime = date('H:i');
                                $apptTime = date('H:i', strtotime($appt['time']));
                                $isPast = $apptTime < $currentTime;
                                $isUpcoming = !$isPast;
                                ?>
                                <div class="p-5 hover:bg-gray-50 transition-colors <?= $isPast ? 'opacity-60' : '' ?>">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <div class="h-12 w-12 rounded-full <?= $isUpcoming ? 'bg-blue-100' : 'bg-gray-100' ?> flex items-center justify-center flex-shrink-0">
                                                    <i data-feather="user" class="h-6 w-6 <?= $isUpcoming ? 'text-blue-600' : 'text-gray-500' ?>"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <h3 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($appt['patientName'] ?? 'Unknown Patient') ?></h3>
                                                        <?php if ($isUpcoming && $status === 'approved'): ?>
                                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-500 text-white animate-pulse">
                                                                Upcoming
                                                            </span>
                                                        <?php elseif ($isPast): ?>
                                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-400 text-white">
                                                                Past
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mt-1">
                                                        <span class="font-medium">Dr. <?= htmlspecialchars($appt['doctorName'] ?? 'Unknown') ?></span>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <?= htmlspecialchars($appt['department'] ?? '') ?> •
                                                        <?php if (!empty($appt['phone'])): ?>
                                                            <i data-feather="phone" class="h-3 w-3 inline"></i>
                                                            <?= htmlspecialchars($appt['phone']) ?>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-full sm:w-auto flex items-center justify-between sm:justify-end gap-3">
                                            <div class="text-right">
                                                <div class="flex items-center gap-1 text-lg font-bold <?= $isUpcoming ? 'text-blue-600' : 'text-gray-500' ?>">
                                                    <i data-feather="clock" class="h-5 w-5"></i>
                                                    <?= htmlspecialchars($appt['time'] ?? '') ?>
                                                </div>
                                            </div>
                                            <span class="px-3 py-1.5 text-xs font-semibold rounded-full border <?= $badgeClass ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php if (!empty($appt['reason'])): ?>
                                        <div class="mt-3 ml-15 pl-3 border-l-2 border-gray-200">
                                            <p class="text-xs text-gray-600">
                                                <span class="font-medium">Reason:</span> <?= htmlspecialchars($appt['reason']) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-12 text-center text-gray-500">
                                <i data-feather="calendar" class="h-16 w-16 mx-auto mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium">No appointments scheduled for today</p>
                                <p class="text-sm mt-1">Check back later or view all appointments</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Appointments -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">Recent Appointments</h2>
                            <a href="doctor-appointments.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all →</a>
                        </div>
                    </div>
                    <!--piranockharvey03-->
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
    <script src="../assets/js/mobile-menu.js"></script>
    <script src="../assets/js/notification-dropdown.js"></script>
    <script>
        feather.replace();

        // Notification system
        $(document).ready(function() {
            NotificationDropdown.init({
                buttonSelector: '#notificationBtn',
                dropdownSelector: '#notificationDropdown',
                badgeSelector: '#notificationBadge',
                listSelector: '#notificationList',
                markAllSelector: '#markAllReadBtn',
                markReadButtonClass: 'mark-read-btn',
                fetchUrl: '../includes/get-doctor-notifications.php',
                markReadUrl: '../includes/mark-doctor-notifications-read.php',
                pollIntervalMs: 30000,
                emptyText: 'No new notifications',
                iconMap: {
                    approved: '<i data-feather="check-circle" class="h-4 w-4 text-green-500"></i>',
                    canceled: '<i data-feather="x-circle" class="h-4 w-4 text-red-500"></i>',
                    rescheduled: '<i data-feather="refresh-cw" class="h-4 w-4 text-blue-500"></i>',
                    completed: '<i data-feather="check-square" class="h-4 w-4 text-indigo-500"></i>',
                    checked_in: '<i data-feather="user-check" class="h-4 w-4 text-emerald-500"></i>'
                }
            });

            // Request notification permission
            if (Notification.permission !== 'denied') {
                Notification.requestPermission();
            }
        });

        // Show login success popup
        document.addEventListener('DOMContentLoaded', function() {
            // Create popup element
            const popup = document.createElement('div');
            popup.id = 'login-success-popup';
            popup.innerHTML = `
                <div style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #10B981;
                    color: white;
                    padding: 16px 24px;
                    border-radius: 8px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                    z-index: 1000;
                    font-weight: 600;
                    font-size: 14px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    animation: slideInRight 0.5s ease-out;
                ">
                    <svg style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Login Successful!
                </div>
            `;

            // Add keyframes animation
            if (!document.querySelector('#login-popup-styles')) {
                const style = document.createElement('style');
                style.id = 'login-popup-styles';
                style.textContent = `
                    @keyframes slideInRight {
                        from {
                            transform: translateX(100%);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                    @keyframes fadeOut {
                        from {
                            opacity: 1;
                        }
                        to {
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(style);
            }

            document.body.appendChild(popup);

            // Remove popup after 3 seconds with fade effect
            setTimeout(() => {
                popup.style.animation = 'fadeOut 0.5s ease-in forwards';
                setTimeout(() => {
                    if (popup.parentNode) {
                        popup.parentNode.removeChild(popup);
                    }
                }, 500);
            }, 3000);
        });
    </script>
</body>

</html>