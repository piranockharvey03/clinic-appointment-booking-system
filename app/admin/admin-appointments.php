<?php
session_start();
require_once '../../config/db-config.php';

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login if not authenticated or not an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../public/admin-login.html');
    exit;
}

// Handle admin actions: approve, cancel, reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['appt_id'])) {
    try {
        $conn = getDBConnection();
        $apptId = $_POST['appt_id'];

        // Get appointment details for notification
        $apptResult = $conn->query("SELECT patient_id, patient_name, appointment_date, appointment_time, doctor_name FROM appointments WHERE appointment_id = '$apptId'");
        $appointment = $apptResult ? $apptResult->fetch_assoc() : null;
        $apptResult?->free();

        if ($_POST['action'] === 'approve') {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'approved' WHERE appointment_id = ?");
            $stmt->bind_param("s", $apptId);
            $stmt->execute();
            $stmt->close();

            // Create patient notification for approval
            if ($appointment) {
                $message = "Your appointment with Dr. " . $appointment['doctor_name'] . " on " . $appointment['appointment_date'] . " at " . $appointment['appointment_time'] . " has been approved.";
                $notificationStmt = $conn->prepare("INSERT INTO patient_notifications (patient_id, appointment_id, patient_name, notification_type, message) VALUES (?, ?, ?, 'approved', ?)");
                $notificationStmt->bind_param("isss", $appointment['patient_id'], $apptId, $appointment['patient_name'], $message);
                $notificationStmt->execute();
                $notificationStmt->close();
            }

            // Create admin notification for approval
            $adminMessage = "Appointment approved: " . $appointment['patient_name'] . " with Dr. " . $appointment['doctor_name'] . " on " . $appointment['appointment_date'] . " at " . $appointment['appointment_time'];
            $adminNotificationStmt = $conn->prepare("INSERT INTO notifications (type, message, appointment_id) VALUES ('approved', ?, ?)");
            $adminNotificationStmt->bind_param("ss", $adminMessage, $apptId);
            $adminNotificationStmt->execute();
            $adminNotificationStmt->close();
        } elseif ($_POST['action'] === 'cancel') {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'canceled' WHERE appointment_id = ?");
            $stmt->bind_param("s", $apptId);
            $stmt->execute();
            $stmt->close();

            // Create patient notification for cancellation
            if ($appointment) {
                $message = "Your appointment with Dr. " . $appointment['doctor_name'] . " on " . $appointment['appointment_date'] . " at " . $appointment['appointment_time'] . " has been canceled.";
                $notificationStmt = $conn->prepare("INSERT INTO patient_notifications (patient_id, appointment_id, patient_name, notification_type, message) VALUES (?, ?, ?, 'canceled', ?)");
                $notificationStmt->bind_param("isss", $appointment['patient_id'], $apptId, $appointment['patient_name'], $message);
                $notificationStmt->execute();
                $notificationStmt->close();
            }

            // Create admin notification for cancellation
            $adminMessage = "Appointment canceled: " . $appointment['patient_name'] . " with Dr. " . $appointment['doctor_name'] . " on " . $appointment['appointment_date'] . " at " . $appointment['appointment_time'];
            $adminNotificationStmt = $conn->prepare("INSERT INTO notifications (type, message, appointment_id) VALUES ('canceled', ?, ?)");
            $adminNotificationStmt->bind_param("ss", $adminMessage, $apptId);
            $adminNotificationStmt->execute();
            $adminNotificationStmt->close();
        } elseif ($_POST['action'] === 'reschedule' && !empty($_POST['new_date']) && !empty($_POST['new_time'])) {
            $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = 'rescheduled' WHERE appointment_id = ?");
            $stmt->bind_param("sss", $_POST['new_date'], $_POST['new_time'], $apptId);
            $stmt->execute();
            $stmt->close();

            // Create patient notification for rescheduling
            if ($appointment) {
                $message = "Your appointment with Dr. " . $appointment['doctor_name'] . " has been rescheduled to " . $_POST['new_date'] . " at " . $_POST['new_time'] . ".";
                $notificationStmt = $conn->prepare("INSERT INTO patient_notifications (patient_id, appointment_id, patient_name, notification_type, message) VALUES (?, ?, ?, 'rescheduled', ?)");
                $notificationStmt->bind_param("isss", $appointment['patient_id'], $apptId, $appointment['patient_name'], $message);
                $notificationStmt->execute();
                $notificationStmt->close();
            }

            // Create admin notification for rescheduling
            $adminMessage = "Appointment rescheduled: " . $appointment['patient_name'] . " with Dr. " . $appointment['doctor_name'] . " to " . $_POST['new_date'] . " at " . $_POST['new_time'];
            $adminNotificationStmt = $conn->prepare("INSERT INTO notifications (type, message, appointment_id) VALUES ('rescheduled', ?, ?)");
            $adminNotificationStmt->bind_param("ss", $adminMessage, $apptId);
            $adminNotificationStmt->execute();
            $adminNotificationStmt->close();
        } elseif ($_POST['action'] === 'complete') {
            $stmt = $conn->prepare("UPDATE appointments SET status = 'completed' WHERE appointment_id = ?");
            $stmt->bind_param("s", $apptId);
            $stmt->execute();
            $stmt->close();

            // Create patient notification for completion
            if ($appointment) {
                $message = "Your appointment with Dr. " . $appointment['doctor_name'] . " on " . $appointment['appointment_date'] . " at " . $appointment['appointment_time'] . " has been marked as completed.";
                $notificationStmt = $conn->prepare("INSERT INTO patient_notifications (patient_id, appointment_id, patient_name, notification_type, message) VALUES (?, ?, ?, 'completed', ?)");
                $notificationStmt->bind_param("isss", $appointment['patient_id'], $apptId, $appointment['patient_name'], $message);
                $notificationStmt->execute();
                $notificationStmt->close();
            }

            // Create admin notification for completion
            $adminMessage = "Appointment completed: " . $appointment['patient_name'] . " with Dr. " . $appointment['doctor_name'] . " on " . $appointment['appointment_date'] . " at " . $appointment['appointment_time'];
            $adminNotificationStmt = $conn->prepare("INSERT INTO notifications (type, message, appointment_id) VALUES ('completed', ?, ?)");
            $adminNotificationStmt->bind_param("ss", $adminMessage, $apptId);
            $adminNotificationStmt->execute();
            $adminNotificationStmt->close();
        }

        closeDBConnection($conn);
    } catch (Exception $e) {
        error_log("Admin appointment action error: " . $e->getMessage());
    }

    // Redirect to avoid form resubmission
    header("Location: admin-appointments.php?tab=" . ($_GET['tab'] ?? 'pending'));
    exit;
}

// Load appointments from database
$appointments = [];
try {
    $conn = getDBConnection();
    $result = $conn->query("SELECT *, appointment_id as id, appointment_date as date, appointment_time as time FROM appointments ORDER BY created_at DESC");

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
        }
        $result->free();
    }

    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Failed to load appointments: " . $e->getMessage());
}

// Determine which tab to show
$tab = $_GET['tab'] ?? 'pending';
function filter_appointments($appointments, $tab)
{
    $statusOf = fn($a) => strtolower($a['status'] ?? 'pending');
    if ($tab === 'pending') return array_filter($appointments, fn($a) => $statusOf($a) === 'pending');
    if ($tab === 'approved') return array_filter($appointments, fn($a) => $statusOf($a) === 'approved');
    if ($tab === 'canceled') return array_filter($appointments, fn($a) => $statusOf($a) === 'canceled');
    if ($tab === 'rescheduled') return array_filter($appointments, fn($a) => $statusOf($a) === 'rescheduled');
    if ($tab === 'completed') return array_filter($appointments, fn($a) => $statusOf($a) === 'completed');
    return $appointments;
}
$filtered = array_reverse(filter_appointments($appointments, $tab));

// For reschedule modal
$reschedule_id = $_GET['reschedule'] ?? null;
$reschedule_appt = null;
if ($reschedule_id) {
    foreach ($appointments as $a) {
        if ($a['id'] == $reschedule_id) {
            $reschedule_appt = $a;
            break;
        }
    }
}

// For view details modal
$view_id = $_GET['view'] ?? null;
$view_appt = null;
if ($view_id) {
    foreach ($appointments as $a) {
        if ($a['id'] == $view_id) {
            $view_appt = $a;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Appointments | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <style>
        .sidebar {
            transition: all 0.3s;
        }

        .sidebar-collapsed {
            width: 5rem;
        }

        .sidebar-expanded {
            width: 16rem;
        }

        .main-content {
            transition: margin-left 0.3s;
        }

        .appointment-card:hover {
            background-color: #f9fafb;
        }

        .modal-bg {
            background: rgba(0, 0, 0, 0.3);
        }

        .btn-spinner {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid #ffffff;
            border-radius: 50%;
            width: 14px;
            height: 14px;
            animation: spin 0.6s linear infinite;
            display: inline-block;
            vertical-align: middle;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="flex h-screen overflow-hidden">
        <div class="sidebar bg-blue-800 text-white" id="sidebar">
            <!-- ...existing sidebar code... -->
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
                            <a href="admin-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="admin-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="admin-settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="settings" class="mr-3 h-5 w-5"></i>
                                Settings
                            </a>
                            <a href="admin-logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="log-out" class="mr-3 h-5 w-5"></i>
                                Logout
                            </a>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <!--piranockharvey03-->
        <div class="main-content flex-1 overflow-auto w-full">
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">Admin - Manage Appointments</h1>
                </div>
            </header>
            <main class="p-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-4 sm:p-6">
                        <!-- Tabs -->
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            <a href="?tab=pending" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Pending</a>
                            <a href="?tab=approved" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'approved' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Confirmed</a>
                            <a href="?tab=rescheduled" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'rescheduled' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Rescheduled</a>
                            <a href="?tab=canceled" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'canceled' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Canceled</a>
                            <a href="?tab=completed" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'completed' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Completed</a>
                            <button id="toggleFilters" class="ml-auto px-3 py-1.5 text-sm rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 flex items-center gap-1">
                                <i data-feather="sliders" class="h-4 w-4"></i>
                                Filters
                            </button>
                        </div>

                        <!-- Advanced Filters (collapsible) -->
                        <div id="advancedFilters" class="hidden bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <!-- Patient Search -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Patient Name</label>
                                    <div class="relative">
                                        <i data-feather="user" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                                        <input id="patientSearch" type="text" class="pl-9 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm" placeholder="Search patient..." />
                                    </div>
                                </div>

                                <!-- Doctor/Department Search -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Doctor/Department</label>
                                    <div class="relative">
                                        <i data-feather="search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                                        <input id="doctorSearch" type="text" class="pl-9 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm" placeholder="Search doctor..." />
                                    </div>
                                </div>

                                <!-- Date From -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Date From</label>
                                    <div class="relative">
                                        <i data-feather="calendar" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                                        <input id="dateFrom" type="date" class="pl-9 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm" />
                                    </div>
                                </div>

                                <!-- Date To -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Date To</label>
                                    <div class="relative">
                                        <i data-feather="calendar" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                                        <input id="dateTo" type="date" class="pl-9 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm" />
                                    </div>
                                </div>
                            </div>

                            <!-- Filter Actions -->
                            <div class="flex items-center gap-2 mt-3">
                                <button id="clearFilters" class="px-3 py-1.5 text-sm rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                                    Clear All
                                </button>
                                <span id="filterCount" class="text-xs text-gray-600"></span>
                            </div>
                        </div>

                        <!-- Results Summary -->
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm text-gray-600">
                                Showing <span id="visibleCount" class="font-semibold text-gray-900"><?= count($filtered) ?></span> of <span class="font-semibold text-gray-900"><?= count($filtered) ?></span> appointments
                            </p>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php if (empty($filtered)) : ?>
                                <div id="noAppointments" class="p-6 text-sm text-gray-600">No appointments in this category.</div>
                            <?php else : ?>
                                <?php foreach ($filtered as $appt): ?>
                                    <div class="appointment-card p-4 flex items-center justify-between"
                                        data-patient-name="<?= htmlspecialchars(strtolower($appt['patientName'])) ?>"
                                        data-doctor-name="<?= htmlspecialchars(strtolower($appt['doctorName'])) ?>"
                                        data-department="<?= htmlspecialchars(strtolower($appt['department'])) ?>"
                                        data-doctor-specialty="<?= htmlspecialchars(strtolower($appt['doctorSpecialty'])) ?>"
                                        data-date="<?= htmlspecialchars($appt['date']) ?>">
                                        <div class="flex items-center">
                                            <?php if (!empty($appt['doctorPhoto'])): ?>
                                                <img class="h-12 w-12 rounded-full" src="<?= htmlspecialchars($appt['doctorPhoto']) ?>" alt="Doctor">
                                            <?php else: ?>
                                                <div class="h-12 w-12 rounded-full bg-gray-200"></div>
                                            <?php endif; ?>
                                            <div class="ml-4">
                                                <h4 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($appt['patientName'] ?? 'Unknown Patient') ?></h4>
                                                <div class="text-xs text-gray-500">
                                                    Dr. <?= htmlspecialchars($appt['doctorName']) ?> • <?= htmlspecialchars($appt['doctorSpecialty']) ?>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    <?= htmlspecialchars($appt['date']) ?>, <?= htmlspecialchars($appt['time']) ?>
                                                </div>
                                                <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">Department: <?= htmlspecialchars($appt['department']) ?></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <?php
                                            $status = strtolower($appt['status'] ?? 'pending');
                                            ?>
                                            <?php if ($status === 'pending'): ?>
                                                <a href="?tab=<?= htmlspecialchars($tab) ?>&view=<?= htmlspecialchars($appt['id']) ?>" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-gray-700">View Details</a>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="approve" class="px-3 py-1.5 text-sm rounded-md text-white bg-green-600 hover:bg-green-700 flex items-center gap-1">
                                                        <i data-feather="check" class="h-4 w-4"></i>
                                                        Confirm
                                                    </button>
                                                </form>
                                                <form method="get" style="display:inline;">
                                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                                    <input type="hidden" name="reschedule" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button type="submit" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-blue-700">Reschedule</button>
                                                </form>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="cancel" class="px-3 py-1.5 text-sm rounded-md text-red-600 border border-red-200" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                                </form>
                                            <?php elseif ($status === 'approved'): ?>
                                                <a href="?tab=<?= htmlspecialchars($tab) ?>&view=<?= htmlspecialchars($appt['id']) ?>" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-gray-700">View Details</a>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="complete" class="px-3 py-1.5 text-sm rounded-md text-white bg-blue-600 hover:bg-blue-700 flex items-center gap-1">
                                                        <i data-feather="check-circle" class="h-4 w-4"></i>
                                                        Complete
                                                    </button>
                                                </form>
                                                <form method="get" style="display:inline;">
                                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                                    <input type="hidden" name="reschedule" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button type="submit" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-blue-700">Reschedule</button>
                                                </form>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="cancel" class="px-3 py-1.5 text-sm rounded-md text-red-600 border border-red-200" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                                </form>
                                                <span class="px-3 py-1.5 text-sm rounded-md bg-green-100 text-green-700 flex items-center gap-1">
                                                    <i data-feather="check-circle" class="h-4 w-4"></i>
                                                    Confirmed
                                                </span>
                                            <?php elseif ($status === 'rescheduled'): ?>
                                                <a href="?tab=<?= htmlspecialchars($tab) ?>&view=<?= htmlspecialchars($appt['id']) ?>" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-gray-700">View Details</a>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="approve" class="px-3 py-1.5 text-sm rounded-md text-white bg-green-600 hover:bg-green-700 flex items-center gap-1">
                                                        <i data-feather="check" class="h-4 w-4"></i>
                                                        Confirm
                                                    </button>
                                                </form>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="cancel" class="px-3 py-1.5 text-sm rounded-md text-red-600 border border-red-200" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                                </form>
                                                <span class="px-3 py-1.5 text-sm rounded-md bg-purple-100 text-purple-700">Rescheduled</span>
                                            <?php elseif ($status === 'completed'): ?>
                                                <a href="?tab=<?= htmlspecialchars($tab) ?>&view=<?= htmlspecialchars($appt['id']) ?>" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-gray-700">View Details</a>
                                                <span class="px-3 py-1.5 text-sm rounded-md bg-gray-100 text-gray-700 flex items-center gap-1">
                                                    <i data-feather="check-circle" class="h-4 w-4"></i>
                                                    Completed
                                                </span>
                                                <span class="px-3 py-1.5 text-sm rounded-md bg-blue-100 text-blue-700">Rescheduled</span>
                                            <?php else: ?>
                                                <a href="?tab=<?= htmlspecialchars($tab) ?>&view=<?= htmlspecialchars($appt['id']) ?>" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-gray-700">View Details</a>
                                                <span class="px-3 py-1.5 text-sm rounded-md bg-red-100 text-red-600">
                                                    <?= ucfirst($status) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php if ($view_appt): ?>
        <!-- Modal for viewing appointment details -->
        <div class="fixed inset-0 flex items-center justify-center z-50 modal-bg">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Appointment Details</h3>
                    <a href="admin-appointments.php?tab=<?= htmlspecialchars($tab) ?>" class="text-gray-400 hover:text-gray-600">
                        <i data-feather="x" class="h-5 w-5"></i>
                    </a>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Patient Name</label>
                        <p class="text-gray-900"><?= htmlspecialchars($view_appt['patientName'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <p class="text-gray-900"><?= htmlspecialchars($view_appt['phone'] ?? 'Not provided') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit</label>
                        <p class="text-gray-900"><?= htmlspecialchars($view_appt['reason'] ?? 'Not provided') ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                        <p class="text-gray-900"><?= !empty($view_appt['notes']) ? htmlspecialchars($view_appt['notes']) : 'No additional notes' ?></p>
                    </div>
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Appointment Details</label>
                        <p class="text-gray-900">Dr. <?= htmlspecialchars($view_appt['doctorName']) ?></p>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($view_appt['doctorSpecialty']) ?></p>
                        <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($view_appt['date']) ?> at <?= htmlspecialchars($view_appt['time']) ?></p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <a href="admin-appointments.php?tab=<?= htmlspecialchars($tab) ?>" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">Close</a>
                </div>
            </div>
        </div>
        <style>
            body {
                overflow: hidden;
            }
        </style>
    <?php endif; ?>
    <?php if ($reschedule_appt): ?>
        <!-- Modal for rescheduling -->
        <div class="fixed inset-0 flex items-center justify-center z-50 modal-bg">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm relative">
                <form method="post">
                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($reschedule_appt['id']) ?>">
                    <input type="hidden" name="action" value="reschedule">
                    <label class="block mb-2 font-semibold text-gray-700">Reschedule Appointment</label>
                    <input type="date" name="new_date" class="border rounded px-3 py-2 w-full mb-4" value="<?= htmlspecialchars($reschedule_appt['date']) ?>" required min="<?= date('Y-m-d') ?>">
                    <input type="time" name="new_time" class="border rounded px-3 py-2 w-full mb-4" value="<?= htmlspecialchars($reschedule_appt['time']) ?>" required>
                    <div class="flex justify-end gap-2">
                        <a href="admin-appointments.php?tab=<?= htmlspecialchars($tab) ?>" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">Cancel</a>
                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
        <style>
            body {
                overflow: hidden;
            }
        </style>
    <?php endif; ?>
    <script src="../assets/js/mobile-menu.js"></script>
    <script>
        feather.replace();

        document.addEventListener('DOMContentLoaded', function() {
            // Toggle advanced filters
            const toggleFiltersBtn = document.getElementById('toggleFilters');
            const advancedFilters = document.getElementById('advancedFilters');

            if (toggleFiltersBtn) {
                toggleFiltersBtn.addEventListener('click', function() {
                    advancedFilters.classList.toggle('hidden');
                    feather.replace();
                });
            }

            // Get all filter inputs
            const patientSearch = document.getElementById('patientSearch');
            const doctorSearch = document.getElementById('doctorSearch');
            const dateFrom = document.getElementById('dateFrom');
            const dateTo = document.getElementById('dateTo');
            const clearFiltersBtn = document.getElementById('clearFilters');
            const appointmentCards = document.querySelectorAll('.appointment-card');
            const visibleCountSpan = document.getElementById('visibleCount');
            const filterCountSpan = document.getElementById('filterCount');
            const totalCount = appointmentCards.length;

            // Filter function
            function filterAppointments() {
                const patientTerm = (patientSearch?.value || '').toLowerCase().trim();
                const doctorTerm = (doctorSearch?.value || '').toLowerCase().trim();
                const fromDate = dateFrom?.value || '';
                const toDate = dateTo?.value || '';

                let visibleCount = 0;
                let activeFilters = 0;

                // Count active filters
                if (patientTerm) activeFilters++;
                if (doctorTerm) activeFilters++;
                if (fromDate) activeFilters++;
                if (toDate) activeFilters++;

                appointmentCards.forEach(card => {
                    const patientName = card.getAttribute('data-patient-name') || '';
                    const doctorName = card.getAttribute('data-doctor-name') || '';
                    const department = card.getAttribute('data-department') || '';
                    const doctorSpecialty = card.getAttribute('data-doctor-specialty') || '';
                    const apptDate = card.getAttribute('data-date') || '';

                    // Check each filter
                    const matchesPatient = !patientTerm || patientName.includes(patientTerm);
                    const matchesDoctor = !doctorTerm ||
                        doctorName.includes(doctorTerm) ||
                        department.includes(doctorTerm) ||
                        doctorSpecialty.includes(doctorTerm);
                    const matchesDateFrom = !fromDate || apptDate >= fromDate;
                    const matchesDateTo = !toDate || apptDate <= toDate;

                    // Show card if all active filters match
                    if (matchesPatient && matchesDoctor && matchesDateFrom && matchesDateTo) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Update visible count
                if (visibleCountSpan) {
                    visibleCountSpan.textContent = visibleCount;
                }

                // Update filter count badge
                if (filterCountSpan) {
                    if (activeFilters > 0) {
                        filterCountSpan.textContent = `${activeFilters} filter${activeFilters !== 1 ? 's' : ''} active`;
                        filterCountSpan.classList.add('font-semibold', 'text-blue-600');
                    } else {
                        filterCountSpan.textContent = '';
                        filterCountSpan.classList.remove('font-semibold', 'text-blue-600');
                    }
                }

                // Handle empty states
                const noAppointmentsMsg = document.getElementById('noAppointments');
                let noResultsMsg = document.getElementById('noSearchResults');

                if (totalCount === 0) {
                    // No appointments at all
                    if (noAppointmentsMsg) {
                        noAppointmentsMsg.style.display = 'block';
                    }
                    if (noResultsMsg) {
                        noResultsMsg.remove();
                    }
                } else if (visibleCount === 0) {
                    // Appointments exist but none match filters
                    if (noAppointmentsMsg) {
                        noAppointmentsMsg.style.display = 'none';
                    }
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.id = 'noSearchResults';
                        noResultsMsg.className = 'p-8 text-center';
                        noResultsMsg.innerHTML = `
                            <i data-feather="search" class="h-12 w-12 mx-auto mb-3 text-gray-300"></i>
                            <p class="text-sm font-medium text-gray-900 mb-1">No appointments found</p>
                            <p class="text-sm text-gray-600 mb-3">Try adjusting your filters to find what you're looking for</p>
                            <button onclick="clearAllFilters()" class="px-3 py-1.5 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Clear All Filters</button>
                        `;
                        document.querySelector('.divide-y').appendChild(noResultsMsg);
                        feather.replace();
                    }
                } else {
                    // Some appointments are visible
                    if (noAppointmentsMsg) {
                        noAppointmentsMsg.style.display = 'none';
                    }
                    if (noResultsMsg) {
                        noResultsMsg.remove();
                    }
                }
            }

            // Clear all filters
            window.clearAllFilters = function() {
                if (patientSearch) patientSearch.value = '';
                if (doctorSearch) doctorSearch.value = '';
                if (dateFrom) dateFrom.value = '';
                if (dateTo) dateTo.value = '';
                filterAppointments();
            };

            // Attach event listeners
            if (patientSearch) patientSearch.addEventListener('input', filterAppointments);
            if (doctorSearch) doctorSearch.addEventListener('input', filterAppointments);
            if (dateFrom) dateFrom.addEventListener('change', filterAppointments);
            if (dateTo) dateTo.addEventListener('change', filterAppointments);
            if (clearFiltersBtn) clearFiltersBtn.addEventListener('click', clearAllFilters);

            // Initial filter
            filterAppointments();
        });

        // Add loading spinners to all form submission buttons
        document.addEventListener('DOMContentLoaded', function() {
            const actionForms = document.querySelectorAll('form[method="post"]');

            actionForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.disabled = true;

                        // Add spinner to button
                        const spinner = document.createElement('span');
                        spinner.className = 'btn-spinner mr-1';
                        submitBtn.insertBefore(spinner, submitBtn.firstChild);

                        // Update button text
                        const btnText = submitBtn.querySelector('span') || submitBtn;
                        const originalText = btnText.textContent;
                        if (originalText.includes('Confirm')) btnText.textContent = ' Confirming...';
                        else if (originalText.includes('Cancel')) btnText.textContent = ' Canceling...';
                        else if (originalText.includes('Complete')) btnText.textContent = ' Completing...';
                    }
                });
            });

            // Handle reschedule form
            const rescheduleForm = document.querySelector('form[action*="reschedule"]');
            if (rescheduleForm) {
                rescheduleForm.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="btn-spinner mr-1"></span> Saving...';
                    }
                });
            }
        });
    </script>
</body>

</html>