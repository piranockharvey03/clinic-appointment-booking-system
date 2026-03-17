<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Redirect to login if not authenticated or not a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../../public/login.html');
    exit;
}

// Handle status change actions and reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['appt_id'])) {
    $conn = null;

    try {
        $conn = getDBConnection();
        beginDBTransaction($conn);

        $apptId = $_POST['appt_id'];
        $patientId = (int)$_SESSION['user_id'];

        if ($_POST['action'] === 'cancel') {
            $cancelReason = trim($_POST['cancel_reason'] ?? '');
            $stmt = prepareDBStatement($conn, "UPDATE appointments SET status = 'canceled', cancel_reason = ?, booking_slot_key = NULL WHERE appointment_id = ? AND patient_id = ?");
            $stmt->bind_param("ssi", $cancelReason, $apptId, $patientId);
            executeDBStatement($stmt);
            $stmt->close();
        } elseif ($_POST['action'] === 'reschedule' && !empty($_POST['new_date']) && !empty($_POST['new_time'])) {
            $newDate = $_POST['new_date'];
            $newTime = $_POST['new_time'];
            $stmt = prepareDBStatement($conn, "UPDATE appointments SET appointment_date = ?, appointment_time = ?, booking_slot_key = CONCAT(doctor_id, '|', ?, '|', ?), status = 'rescheduled' WHERE appointment_id = ? AND patient_id = ?");
            $stmt->bind_param("sssssi", $newDate, $newTime, $newDate, $newTime, $apptId, $patientId);
            executeDBStatement($stmt);
            $stmt->close();
        }

        commitDBTransaction($conn);
        closeDBConnection($conn);
    } catch (Exception $e) {
        if ($conn) {
            rollbackDBTransaction($conn);
            closeDBConnection($conn);
        }

        error_log("Patient appointment action error: " . $e->getMessage());
    }

    // Redirect to avoid form resubmission
    header("Location: patient-appointments.php?tab=" . ($_GET['tab'] ?? 'pending'));
    exit;
}

// Load appointments from database
$appointments = [];
try {
    $conn = getDBConnection();
    // Get patient appointments only (filter by logged-in patient)
    $stmt = $conn->prepare("SELECT *, appointment_id as id, appointment_date as date, appointment_time as time FROM appointments WHERE patient_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
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
                'checkedInAt' => $row['checked_in_at'] ?? null,
                'checkinToken' => $row['checkin_token'] ?? null,
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
    if ($tab === 'rescheduled') return array_filter($appointments, fn($a) => $statusOf($a) === 'rescheduled');
    if ($tab === 'canceled') return array_filter($appointments, fn($a) => $statusOf($a) === 'canceled');
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | MediCare Clinic</title>
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
                            <a href="patient-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                            <a href="../../public/patient-book.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="plus-circle" class="mr-3 h-5 w-5"></i>
                                Book Appointment
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
        <!--piranockharvey03-->
        <div class="main-content flex-1 overflow-auto w-full">
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">Appointments</h1>
                    <a href="../../public/patient-book.html" class="inline-flex items-center px-3 py-2 text-sm rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i data-feather="plus" class="mr-1 h-4 w-4"></i>
                        New
                    </a>
                </div>
            </header>
            <main class="p-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="?tab=pending" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Pending</a>
                                <a href="?tab=approved" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'approved' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Confirmed</a>
                                <a href="?tab=rescheduled" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'rescheduled' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Rescheduled</a>
                                <a href="?tab=canceled" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'canceled' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Canceled</a>
                                <a href="?tab=completed" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'completed' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Completed</a>
                            </div>
                            <div class="relative w-full sm:w-auto">
                                <i data-feather="search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                                <input id="patientApptSearch" class="pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm w-full sm:w-auto" placeholder="Search doctor or department" />
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php if (empty($filtered)) : ?>
                                <div class="p-6 text-sm text-gray-600">No appointments yet. <a class="text-blue-600" href="../../public/patient-book.html">Book your appointment here</a>.</div>
                            <?php else : ?>
                                <?php foreach ($filtered as $appt): ?>
                                    <div class="appointment-card p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
                                        data-doctor-name="<?= htmlspecialchars(strtolower($appt['doctorName'])) ?>"
                                        data-department="<?= htmlspecialchars(strtolower($appt['department'])) ?>"
                                        data-doctor-specialty="<?= htmlspecialchars(strtolower($appt['doctorSpecialty'])) ?>">
                                        <div class="flex items-center">
                                            <?php if (!empty($appt['doctorPhoto'])): ?>
                                                <img class="h-12 w-12 rounded-full" src="<?= htmlspecialchars($appt['doctorPhoto']) ?>" alt="Doctor">
                                            <?php else: ?>
                                                <div class="h-12 w-12 rounded-full bg-gray-200"></div>
                                            <?php endif; ?>
                                            <div class="ml-4">
                                                <h4 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($appt['doctorName']) ?></h4>
                                                <div class="text-xs text-gray-500">
                                                    <?= htmlspecialchars($appt['doctorSpecialty']) ?> • <?= htmlspecialchars($appt['date']) ?>, <?= htmlspecialchars($appt['time']) ?>
                                                </div>
                                                <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">Department: <?= htmlspecialchars($appt['department']) ?></div>
                                            </div>
                                        </div>
                                        <div class="w-full sm:w-auto flex flex-wrap items-center gap-2 justify-start sm:justify-end">
                                            <?php
                                            $status = strtolower($appt['status'] ?? 'pending');
                                            ?>
                                            <?php if (!in_array($status, ['canceled', 'past'], true)): ?>
                                                <?php if ($status === 'approved' && empty($appt['checkedInAt']) && $appt['date'] === date('Y-m-d')): ?>
                                                    <form method="post" action="checkin.php" style="display:inline;">
                                                        <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                        <button type="submit" class="px-3 py-1.5 text-sm rounded-md text-white bg-teal-600 hover:bg-teal-700 flex items-center gap-1">
                                                            <i data-feather="map-pin" class="h-4 w-4"></i>
                                                            Check In
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($status === 'approved' && !empty($appt['checkedInAt'])): ?>
                                                    <span class="px-2 py-1 text-xs rounded-full bg-teal-100 text-teal-700 flex items-center gap-1 font-medium">
                                                        <i data-feather="user-check" class="h-3 w-3"></i>
                                                        Checked In &middot; Code&nbsp;<strong><?= htmlspecialchars($appt['checkinToken'] ?? '----') ?></strong>
                                                    </span>
                                                <?php endif; ?>
                                                <form method="get" style="display:inline;">
                                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                                    <input type="hidden" name="reschedule" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button type="submit" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-blue-700">Reschedule</button>
                                                </form>
                                                <button type="button" onclick="openPatientCancelModal('<?= htmlspecialchars($appt['id']) ?>')" class="px-3 py-1.5 text-sm rounded-md text-red-600 border border-red-200">Cancel</button>
                                                <?php
                                                $badgeClass = $status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                                                $label = ucfirst($status);
                                                ?>
                                                <span class="px-3 py-1.5 text-sm rounded-md <?= $badgeClass ?>"><?= htmlspecialchars($label) ?></span>
                                            <?php else: ?>
                                                <span class="px-3 py-1.5 text-sm rounded-md <?= ($status === 'canceled') ? 'bg-red-100 text-red-600' : 'bg-gray-200 text-gray-700' ?>">
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
                        <a href="patient-appointments.php?tab=<?= htmlspecialchars($tab) ?>" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">Cancel</a>
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
    <!-- Patient Cancel Reason Modal -->
    <div id="patientCancelReasonModal" class="fixed inset-0 flex items-center justify-center z-50 modal-bg" style="display:none;">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm relative">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Reason for Cancellation</h3>
                <button type="button" onclick="closePatientCancelModal()" class="text-gray-400 hover:text-gray-600">
                    <i data-feather="x" class="h-5 w-5"></i>
                </button>
            </div>
            <p class="text-sm text-gray-600 mb-4">Please select a reason for canceling this appointment:</p>
            <div class="space-y-1 mb-4">
                <?php foreach (['Cannot attend', 'Feeling better – no longer needed', 'Found another provider', 'Schedule conflict', 'Transportation or distance issues', 'Financial reasons', 'Other'] as $pcr): ?>
                    <label class="flex items-center gap-3 p-2 rounded-md hover:bg-gray-50 cursor-pointer">
                        <input type="radio" name="patientCancelReasonChoice" value="<?= htmlspecialchars($pcr) ?>" class="h-4 w-4 text-red-600">
                        <span class="text-sm text-gray-700"><?= htmlspecialchars($pcr) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p id="patientCancelReasonError" class="text-sm text-red-600 mb-3 hidden">Please select a reason to continue.</p>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closePatientCancelModal()" class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm">Keep Appointment</button>
                <button type="button" id="confirmPatientCancelBtn" class="px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white text-sm">Confirm Cancel</button>
            </div>
        </div>
    </div>
    <form method="post" id="patientCancelForm" style="display:none;">
        <input type="hidden" name="action" value="cancel">
        <input type="hidden" name="appt_id" value="" id="patientCancelApptIdInput">
        <input type="hidden" name="cancel_reason" value="" id="patientCancelReasonValueInput">
    </form>
    <script src="../assets/js/mobile-menu.js"></script>
    <script src="../assets/js/custom-modal.js"></script>
    <script>
        feather.replace();

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('patientApptSearch');
            const cards = document.querySelectorAll('.appointment-card');
            const noApptMsg = document.querySelector('.divide-y > .p-6');

            function filterCards() {
                const term = (searchInput.value || '').toLowerCase().trim();
                let visible = 0;

                cards.forEach(function(card) {
                    const doctorName = card.getAttribute('data-doctor-name') || '';
                    const department = card.getAttribute('data-department') || '';
                    const specialty = card.getAttribute('data-doctor-specialty') || '';
                    const matches = !term ||
                        doctorName.includes(term) ||
                        department.includes(term) ||
                        specialty.includes(term);
                    card.style.display = matches ? 'flex' : 'none';
                    if (matches) visible++;
                });

                // Show/hide no-results message
                let noResults = document.getElementById('noSearchResults');
                if (cards.length > 0 && visible === 0 && term) {
                    if (!noResults) {
                        noResults = document.createElement('div');
                        noResults.id = 'noSearchResults';
                        noResults.className = 'p-6 text-sm text-gray-500 text-center';
                        noResults.textContent = 'No appointments match your search.';
                        document.querySelector('.divide-y').appendChild(noResults);
                    }
                } else if (noResults) {
                    noResults.remove();
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', filterCards);
            }
        });

        // Patient cancel reason modal
        window.openPatientCancelModal = function(apptId) {
            document.getElementById('patientCancelApptIdInput').value = apptId;
            document.getElementById('patientCancelReasonModal').style.display = 'flex';
            document.querySelectorAll('[name="patientCancelReasonChoice"]').forEach(r => r.checked = false);
            document.getElementById('patientCancelReasonError').classList.add('hidden');
            feather.replace();
        };
        window.closePatientCancelModal = function() {
            document.getElementById('patientCancelReasonModal').style.display = 'none';
        };
        document.getElementById('confirmPatientCancelBtn').addEventListener('click', function() {
            const selected = document.querySelector('[name="patientCancelReasonChoice"]:checked');
            if (!selected) {
                document.getElementById('patientCancelReasonError').classList.remove('hidden');
                return;
            }
            document.getElementById('patientCancelReasonValueInput').value = selected.value;
            document.getElementById('patientCancelReasonModal').style.display = 'none';
            document.getElementById('patientCancelForm').submit();
        });
    </script>
</body>

</html>