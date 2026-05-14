<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Redirect to login if not authenticated or not a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../../public/login.html');
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
    autoMarkNoShowAppointments($conn, 30);

    // Get patient appointments only (filter by logged-in patient)
    $stmt = $conn->prepare("SELECT *, appointment_id as id, appointment_date as date, appointment_time as time FROM appointments WHERE patient_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

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
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
                            <a href="patient-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                            <a href="../../public/patient-book.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="plus-circle" class="mr-3 h-5 w-5"></i>
                                Book Appointment
                            </a>
                            <a href="how-appointments-work.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="book-open" class="mr-3 h-5 w-5"></i>
                                How It Works
                            </a>
                            <a href="patient-messages.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="message-circle" class="mr-3 h-5 w-5"></i>
                                Messages
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="patient-profile.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="user" class="mr-3 h-5 w-5"></i>
                                Profile
                            </a>
                            <a href="patient-settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="settings" class="mr-3 h-5 w-5"></i>
                                Settings
                            </a>
                            <a href="../auth/logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
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
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <button id="patientNotificationBtn" class="relative p-1 text-gray-600 hover:text-gray-900 focus:outline-none">
                                <i data-feather="bell" class="h-6 w-6"></i>
                                <span id="patientNotificationBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                            </button>
                            <div id="patientNotificationDropdown" class="hidden absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-sm sm:w-80 bg-white rounded-md shadow-lg overflow-hidden z-50">
                                <div class="py-1">
                                    <div class="px-4 py-2 bg-blue-600 text-white font-medium flex justify-between items-center">
                                        <span>Notifications</span>
                                        <button id="markAllPatientNotificationsReadBtn" class="text-xs bg-blue-500 hover:bg-blue-700 px-2 py-1 rounded text-white" style="display: none;">
                                            Mark All Read
                                        </button>
                                    </div>
                                    <div id="patientNotificationList" class="max-h-96 overflow-y-auto">
                                        <div class="px-4 py-3 text-center text-gray-500">Loading notifications...</div>
                                    </div>
                                    <div class="px-4 py-2 bg-gray-100 text-right">
                                        <span class="text-sm text-gray-600">View appointment updates</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="relative">
                            <button class="flex items-center space-x-2">
                                <span class="hidden sm:inline text-sm font-medium text-gray-700"><?php echo htmlspecialchars($fullName); ?></span>
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
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-bold text-white">Welcome back, <?php echo htmlspecialchars($firstName); ?>!</h2>
                            <p class="mt-1 text-blue-100">Here's what's happening with your health today.</p>
                        </div>
                        <a href="../../public/patient-book.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-blue-600 bg-white hover:bg-blue-50">
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Confirmed</dt>
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
                            <a href="patient-appointments.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all →</a>
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
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
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
                                        <div class="w-full sm:w-auto flex flex-wrap items-center gap-2 justify-start sm:justify-end">
                                            <span class="px-3 py-1.5 text-xs font-semibold rounded-full border <?= $badgeClass ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                            <a href="patient-appointments.php" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                                <a href="../../public/patient-book.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
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
    <script src="../assets/js/notification-dropdown.js"></script>
    <script src="../assets/js/messaging.js"></script>
    <script>
        feather.replace();

        // Patient notification system
        $(document).ready(function() {
            NotificationDropdown.init({
                buttonSelector: '#patientNotificationBtn',
                dropdownSelector: '#patientNotificationDropdown',
                badgeSelector: '#patientNotificationBadge',
                listSelector: '#patientNotificationList',
                markAllSelector: '#markAllPatientNotificationsReadBtn',
                markReadButtonClass: 'mark-patient-read-btn',
                fetchUrl: '../includes/get-patient-notifications.php',
                markReadUrl: '../includes/mark-patient-notifications-read.php',
                pollIntervalMs: 30000,
                emptyText: 'No new notifications',
                iconMap: {
                    approved: '<i data-feather="check-circle" class="h-4 w-4 text-green-500"></i>',
                    canceled: '<i data-feather="x-circle" class="h-4 w-4 text-red-500"></i>',
                    rescheduled: '<i data-feather="refresh-cw" class="h-4 w-4 text-blue-500"></i>',
                    completed: '<i data-feather="check-square" class="h-4 w-4 text-indigo-500"></i>'
                }
            });
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