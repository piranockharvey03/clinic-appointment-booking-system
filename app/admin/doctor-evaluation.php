<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Restrict to admin only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../public/admin-login.html');
    exit;
}

$conn = getDBConnection();

// Backward compatibility: some databases may not yet have appointments.cancel_reason.
$hasCancelReason = false;
$cancelReasonCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'cancel_reason'");
if ($cancelReasonCheck && $cancelReasonCheck->num_rows > 0) {
    $hasCancelReason = true;
}
if ($cancelReasonCheck) {
    $cancelReasonCheck->free();
}

// Per-doctor summary stats
$summaryRows = [];
$summaryResult = $conn->query(
    "SELECT
        d.id            AS doctor_id,
        d.full_name     AS doctor_name,
        d.specialty,
        d.department,
        d.status        AS doctor_status,
        COUNT(a.appointment_id)                                              AS total,
        SUM(a.status = 'completed')                                          AS completed,
        SUM(a.status = 'canceled')                                           AS canceled,
        SUM(a.status = 'approved')                                           AS approved,
        SUM(a.status = 'pending')                                            AS pending,
        SUM(a.status = 'rescheduled')                                        AS rescheduled
    FROM doctors d
    LEFT JOIN appointments a ON CAST(a.doctor_id AS CHAR) = CAST(d.id AS CHAR)
    GROUP BY d.id, d.full_name, d.specialty, d.department, d.status
    ORDER BY d.full_name ASC"
);
if ($summaryResult) {
    while ($row = $summaryResult->fetch_assoc()) {
        $summaryRows[] = $row;
    }
    $summaryResult->free();
}

// Drill-down: appointments for a specific doctor
$selectedDoctorId  = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : null;
$selectedDoctor    = null;
$drillAppointments = [];

if ($selectedDoctorId) {
    // Fetch doctor info
    $dStmt = $conn->prepare("SELECT id, full_name, specialty, department FROM doctors WHERE id = ?");
    $dStmt->bind_param("i", $selectedDoctorId);
    $dStmt->execute();
    $dRes = $dStmt->get_result();
    if ($dRes->num_rows === 1) {
        $selectedDoctor = $dRes->fetch_assoc();
    }
    $dRes->free();
    $dStmt->close();

    if ($selectedDoctor) {
        $drillStatus = $_GET['filter_status'] ?? 'all';
        $allowedStatuses = ['all', 'pending', 'approved', 'completed', 'canceled', 'rescheduled'];
        if (!in_array($drillStatus, $allowedStatuses, true)) {
            $drillStatus = 'all';
        }

        $cancelReasonSelect = $hasCancelReason ? 'cancel_reason' : 'NULL AS cancel_reason';

        if ($drillStatus === 'all') {
            $aStmt = $conn->prepare(
                "SELECT appointment_id, patient_name, phone, department, doctor_name,
                        appointment_date, appointment_time, reason, status, {$cancelReasonSelect}, created_at
                 FROM appointments
                 WHERE CAST(doctor_id AS CHAR) = CAST(? AS CHAR)
                 ORDER BY appointment_date DESC, appointment_time DESC"
            );
            $aStmt->bind_param("i", $selectedDoctorId);
        } else {
            $aStmt = $conn->prepare(
                "SELECT appointment_id, patient_name, phone, department, doctor_name,
                        appointment_date, appointment_time, reason, status, {$cancelReasonSelect}, created_at
                 FROM appointments
                 WHERE CAST(doctor_id AS CHAR) = CAST(? AS CHAR) AND status = ?
                 ORDER BY appointment_date DESC, appointment_time DESC"
            );
            $aStmt->bind_param("is", $selectedDoctorId, $drillStatus);
        }
        $aStmt->execute();
        $aRes = $aStmt->get_result();
        while ($row = $aRes->fetch_assoc()) {
            $drillAppointments[] = $row;
        }
        $aRes->free();
        $aStmt->close();
    }
}

closeDBConnection($conn);

// Helper: completion rate
function completionRate(int $completed, int $total): string
{
    if ($total === 0) return 'N/A';
    return round(($completed / $total) * 100) . '%';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Evaluation | MediCare Clinic</title>
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
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
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
                            <a href="new-admin-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="manage-doctors.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="users" class="mr-3 h-5 w-5"></i>
                                Manage Doctors
                            </a>
                            <a href="reports.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="bar-chart-2" class="mr-3 h-5 w-5"></i>
                                Reports
                            </a>
                            <a href="doctor-evaluation.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="activity" class="mr-3 h-5 w-5"></i>
                                Doctor Evaluation
                            </a>
                            <a href="admin-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
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

        <!-- Main content -->
        <div class="main-content flex-1 overflow-auto w-full">
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">Doctor Evaluation</h1>
                </div>
            </header>

            <main class="p-4 sm:px-6 lg:px-8">

                <?php if ($selectedDoctor): ?>
                    <!-- ── DRILL-DOWN VIEW ───────────────────────────── -->
                    <div class="mb-6">
                        <a href="doctor-evaluation.php" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 font-medium">
                            <i data-feather="arrow-left" class="h-4 w-4"></i>
                            Back to all doctors
                        </a>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($selectedDoctor['full_name']) ?></h2>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($selectedDoctor['specialty']) ?> &bull; <?= htmlspecialchars($selectedDoctor['department']) ?></p>
                    </div>

                    <!-- Status filter tabs -->
                    <div class="bg-white rounded-lg shadow mb-4">
                        <div class="flex overflow-x-auto border-b border-gray-200 px-4">
                            <?php
                            $drillTabs = ['all' => 'All', 'completed' => 'Completed', 'canceled' => 'Canceled', 'approved' => 'Approved', 'pending' => 'Pending', 'rescheduled' => 'Rescheduled'];
                            foreach ($drillTabs as $key => $label):
                                $isActive = ($drillStatus ?? 'all') === $key;
                            ?>
                                <a href="?doctor_id=<?= $selectedDoctorId ?>&filter_status=<?= $key ?>"
                                    class="whitespace-nowrap py-3 px-4 border-b-2 text-sm font-medium <?= $isActive ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                                    <?= $label ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Appointments table -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <?php if (empty($drillAppointments)): ?>
                            <div class="p-8 text-center">
                                <i data-feather="calendar" class="h-12 w-12 mx-auto mb-3 text-gray-300"></i>
                                <p class="text-gray-500 text-sm">No appointments found for this filter.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Date &amp; Time</th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Reason for Visit</th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Cancel Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($drillAppointments as $appt):
                                            $statusColors = [
                                                'completed'  => 'bg-green-100 text-green-700',
                                                'canceled'   => 'bg-red-100 text-red-600',
                                                'approved'   => 'bg-blue-100 text-blue-700',
                                                'pending'    => 'bg-yellow-100 text-yellow-700',
                                                'rescheduled' => 'bg-purple-100 text-purple-700',
                                            ];
                                            $sClass = $statusColors[$appt['status']] ?? 'bg-gray-100 text-gray-700';
                                        ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($appt['patient_name']) ?></div>
                                                    <?php if ($appt['phone']): ?>
                                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($appt['phone']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($appt['department']) ?></td>
                                                <td class="px-4 py-3 text-gray-600">
                                                    <?= htmlspecialchars($appt['appointment_date']) ?><br>
                                                    <span class="text-xs"><?= htmlspecialchars($appt['appointment_time']) ?></span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate"><?= htmlspecialchars($appt['reason'] ?? '—') ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $sClass ?>">
                                                        <?= ucfirst($appt['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-500 text-xs">
                                                    <?= !empty($appt['cancel_reason']) ? htmlspecialchars($appt['cancel_reason']) : '—' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 py-3 bg-gray-50 text-sm text-gray-500 border-t">
                                <?= count($drillAppointments) ?> appointment<?= count($drillAppointments) !== 1 ? 's' : '' ?> shown
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- ── SUMMARY VIEW ─────────────────────────────── -->
                    <div class="mb-6">
                        <p class="text-sm text-gray-600">Overview of each doctor's appointment outcomes. Click a doctor's name to view their appointment details.</p>
                    </div>

                    <!-- Overall stats bar -->
                    <?php
                    $grandTotal     = array_sum(array_column($summaryRows, 'total'));
                    $grandCompleted = array_sum(array_column($summaryRows, 'completed'));
                    $grandCanceled  = array_sum(array_column($summaryRows, 'canceled'));
                    $grandApproved  = array_sum(array_column($summaryRows, 'approved'));
                    $grandPending   = array_sum(array_column($summaryRows, 'pending'));
                    ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg shadow p-4 text-center">
                            <p class="text-2xl font-bold text-gray-900"><?= $grandTotal ?></p>
                            <p class="text-sm text-gray-500">Total Appointments</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 text-center">
                            <p class="text-2xl font-bold text-green-600"><?= $grandCompleted ?></p>
                            <p class="text-sm text-gray-500">Completed</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 text-center">
                            <p class="text-2xl font-bold text-red-600"><?= $grandCanceled ?></p>
                            <p class="text-sm text-gray-500">Canceled</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 text-center">
                            <p class="text-2xl font-bold text-blue-600"><?= $grandTotal > 0 ? round(($grandCompleted / $grandTotal) * 100) . '%' : 'N/A' ?></p>
                            <p class="text-sm text-gray-500">Overall Completion Rate</p>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="mb-4">
                        <input type="text" id="doctorEvalSearch" placeholder="Search by doctor name, specialty or department…"
                            class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Doctors table -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <?php if (empty($summaryRows)): ?>
                            <div class="p-8 text-center">
                                <i data-feather="users" class="h-12 w-12 mx-auto mb-3 text-gray-300"></i>
                                <p class="text-gray-500 text-sm">No doctors found.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm" id="evalTable">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Specialty / Dept.</th>
                                            <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                            <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                                            <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Canceled</th>
                                            <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Approved</th>
                                            <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Pending</th>
                                            <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                                            <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="evalTableBody">
                                        <?php foreach ($summaryRows as $doc):
                                            $total     = (int)$doc['total'];
                                            $completed = (int)$doc['completed'];
                                            $canceled  = (int)$doc['canceled'];
                                            $approved  = (int)$doc['approved'];
                                            $pending   = (int)$doc['pending'];
                                            $rate      = completionRate($completed, $total);
                                            $rateNum   = $total > 0 ? round(($completed / $total) * 100) : -1;
                                            $rateColor = $rateNum >= 75 ? 'text-green-600' : ($rateNum >= 40 ? 'text-yellow-600' : ($rateNum === -1 ? 'text-gray-400' : 'text-red-600'));
                                            $statusBadge = $doc['doctor_status'] === 'active'
                                                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-green-100 text-green-700">Active</span>'
                                                : '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-500">Inactive</span>';
                                        ?>
                                            <tr class="hover:bg-gray-50 eval-row"
                                                data-name="<?= strtolower(htmlspecialchars($doc['doctor_name'])) ?>"
                                                data-specialty="<?= strtolower(htmlspecialchars($doc['specialty'])) ?>"
                                                data-department="<?= strtolower(htmlspecialchars($doc['department'])) ?>">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($doc['doctor_name']) ?></div>
                                                    <div class="mt-0.5"><?= $statusBadge ?></div>
                                                </td>
                                                <td class="px-4 py-3 text-gray-600">
                                                    <?= htmlspecialchars($doc['specialty']) ?>
                                                    <div class="text-xs text-gray-400"><?= htmlspecialchars($doc['department']) ?></div>
                                                </td>
                                                <td class="px-4 py-3 text-center font-semibold text-gray-900"><?= $total ?></td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="font-semibold text-green-600"><?= $completed ?></span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="font-semibold text-red-600"><?= $canceled ?></span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="font-semibold text-blue-600"><?= $approved ?></span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="font-semibold text-yellow-600"><?= $pending ?></span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="font-bold <?= $rateColor ?>"><?= $rate ?></span>
                                                    <?php if ($total > 0): ?>
                                                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                                            <div class="h-1.5 rounded-full <?= $rateNum >= 75 ? 'bg-green-500' : ($rateNum >= 40 ? 'bg-yellow-500' : 'bg-red-500') ?>"
                                                                style="width:<?= $rateNum ?>%"></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <a href="?doctor_id=<?= intval($doc['doctor_id']) ?>"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs bg-blue-600 text-white hover:bg-blue-700">
                                                        <i data-feather="eye" class="h-3 w-3"></i>
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 py-3 bg-gray-50 text-sm text-gray-500 border-t" id="evalCount">
                                <?= count($summaryRows) ?> doctor<?= count($summaryRows) !== 1 ? 's' : '' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script>
        feather.replace();

        // Search filter for summary table
        const searchInput = document.getElementById('doctorEvalSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('.eval-row');
                let visible = 0;
                rows.forEach(function(row) {
                    const matches = !term ||
                        row.getAttribute('data-name').includes(term) ||
                        row.getAttribute('data-specialty').includes(term) ||
                        row.getAttribute('data-department').includes(term);
                    row.style.display = matches ? '' : 'none';
                    if (matches) visible++;
                });
                const countEl = document.getElementById('evalCount');
                if (countEl) {
                    countEl.textContent = visible + ' doctor' + (visible !== 1 ? 's' : '');
                }
            });
        }
    </script>
</body>

</html>