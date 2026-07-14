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

$adminName = $_SESSION['user_name'];

// Load all doctors for filter dropdown
$doctors = [];
try {
    $conn = getDBConnection();
    $doctorResult = $conn->query("SELECT id, full_name, specialty, department FROM doctors ORDER BY full_name ASC");
    if ($doctorResult) {
        while ($row = $doctorResult->fetch_assoc()) {
            $doctors[] = $row;
        }
        $doctorResult->free();
    }
    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Admin appointments - doctor load error: " . $e->getMessage());
}

// Filter parameters
$selectedDoctorId = isset($_GET['doctor_id']) && $_GET['doctor_id'] !== '' ? (int)$_GET['doctor_id'] : null;
$selectedTab = $_GET['tab'] ?? 'all';

// Load appointments (filtered by doctor if selected)
$appointments = [];
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0, 'canceled' => 0, 'rescheduled' => 0];

try {
    $conn = getDBConnection();

    if ($selectedDoctorId !== null) {
        $stmt = $conn->prepare("SELECT * FROM appointments WHERE doctor_id = ? ORDER BY appointment_date DESC, appointment_time DESC");
        $doctorIdStr = (string)$selectedDoctorId;
        $stmt->bind_param("s", $doctorIdStr);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT * FROM appointments ORDER BY appointment_date DESC, appointment_time DESC");
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
            $status = strtolower($row['status'] ?? 'pending');
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }
        $result->free();
    }
    if (isset($stmt)) {
        $stmt->close();
    }

    $stats['total'] = count($appointments);
    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Admin appointments load error: " . $e->getMessage());
}

// Filter by tab/status
function admin_filter_appts(array $appts, string $tab): array
{
    if ($tab === 'all') return $appts;
    return array_values(array_filter($appts, fn($a) => strtolower($a['status'] ?? 'pending') === $tab));
}
$filtered = admin_filter_appts($appointments, $selectedTab);

// Find selected doctor name for display
$selectedDoctorName = null;
if ($selectedDoctorId !== null) {
    foreach ($doctors as $d) {
        if ((int)$d['id'] === $selectedDoctorId) {
            $selectedDoctorName = $d['full_name'];
            break;
        }
    }
}

$statusColors = [
    'pending'     => 'bg-yellow-100 text-yellow-800',
    'approved'    => 'bg-blue-100 text-blue-800',
    'completed'   => 'bg-green-100 text-green-800',
    'canceled'    => 'bg-red-100 text-red-800',
    'rescheduled' => 'bg-purple-100 text-purple-800',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | MediCare Admin</title>
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
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
                            <a href="new-admin-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="manage-doctors.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="users" class="mr-3 h-5 w-5"></i>
                                Manage Doctors
                            </a>
                            <a href="manage-patients.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="user" class="mr-3 h-5 w-5"></i>
                                Manage Patients
                            </a>
                            <a href="manage-feedback.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="message-square" class="mr-3 h-5 w-5"></i>
                                Feedback
                            </a>
                            <a href="reports.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="bar-chart-2" class="mr-3 h-5 w-5"></i>
                                Reports
                            </a>
                            <a href="doctor-evaluation.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="activity" class="mr-3 h-5 w-5"></i>
                                Doctor Evaluation
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

        <!-- Main content -->
        <div class="main-content flex-1 overflow-auto w-full">
            <!-- Top navigation -->
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">Appointments</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($adminName) ?></span>
                        <i data-feather="user" class="h-6 w-6 text-blue-600"></i>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:px-6 lg:px-8">

                <!-- Doctor Filter -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <form method="GET" class="flex flex-wrap items-end gap-4">
                        <div class="w-full sm:flex-1 sm:min-w-[12rem]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Doctor</label>
                            <select name="doctor_id" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value=""> All Doctors</option>
                                <?php foreach ($doctors as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>" <?= $selectedDoctorId === (int)$d['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($selectedTab) ?>">
                        <div class="w-full sm:w-auto flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                                Apply
                            </button>
                            <?php if ($selectedDoctorId !== null): ?>
                                <a href="admin-appointments.php?tab=<?= htmlspecialchars($selectedTab) ?>" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if ($selectedDoctorName): ?>
                        <p class="mt-2 text-sm text-blue-700 font-medium">Showing appointments for: <?= htmlspecialchars($selectedDoctorName) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
                    <?php
                    $statCards = [
                        ['label' => 'Total',       'key' => 'total',       'color' => 'bg-blue-50 text-blue-700',    'icon' => 'layers'],
                        ['label' => 'Pending',     'key' => 'pending',     'color' => 'bg-yellow-50 text-yellow-700', 'icon' => 'clock'],
                        ['label' => 'Approved',    'key' => 'approved',    'color' => 'bg-blue-50 text-blue-800',   'icon' => 'check-circle'],
                        ['label' => 'Completed',   'key' => 'completed',   'color' => 'bg-green-50 text-green-700', 'icon' => 'check-square'],
                        ['label' => 'Canceled',    'key' => 'canceled',    'color' => 'bg-red-50 text-red-700',     'icon' => 'x-circle'],
                        ['label' => 'Rescheduled', 'key' => 'rescheduled', 'color' => 'bg-purple-50 text-purple-700', 'icon' => 'refresh-cw'],
                    ];
                    foreach ($statCards as $card):
                    ?>
                        <div class="bg-white rounded-lg shadow p-4 text-center">
                            <i data-feather="<?= $card['icon'] ?>" class="h-6 w-6 mx-auto mb-1 <?= $card['color'] ?>"></i>
                            <div class="text-2xl font-bold <?= $card['color'] ?>"><?= $stats[$card['key']] ?></div>
                            <div class="text-xs text-gray-500 mt-1"><?= $card['label'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Status Tabs -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="border-b border-gray-200 px-4 pt-4">
                        <nav class="flex flex-wrap gap-2 pb-0">
                            <?php
                            $tabs = [
                                'all'         => 'All (' . $stats['total'] . ')',
                                'pending'     => 'Pending (' . $stats['pending'] . ')',
                                'approved'    => 'Approved (' . $stats['approved'] . ')',
                                'completed'   => 'Completed (' . $stats['completed'] . ')',
                                'canceled'    => 'Canceled (' . $stats['canceled'] . ')',
                                'rescheduled' => 'Rescheduled (' . $stats['rescheduled'] . ')',
                            ];
                            foreach ($tabs as $tabKey => $tabLabel):
                                $isActive = $selectedTab === $tabKey;
                                $qstr = http_build_query(array_filter(['doctor_id' => $selectedDoctorId, 'tab' => $tabKey]));
                            ?>
                                <a href="?<?= $qstr ?>"
                                    class="px-4 py-2 text-sm font-medium border-b-2 -mb-px <?= $isActive ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                                    <?= htmlspecialchars($tabLabel) ?>
                                </a>
                            <?php endforeach; ?>
                        </nav>
                    </div>

                    <!-- Appointments Table -->
                    <div class="p-4">
                        <?php if (empty($filtered)): ?>
                            <div class="text-center py-12 text-gray-400">
                                <i data-feather="calendar" class="h-12 w-12 mx-auto mb-3 opacity-40"></i>
                                <p class="text-sm">No appointments found for the selected filters.</p>
                            </div>
                        <?php else: ?>
                            <!-- Search -->
                            <div class="mb-4">
                                <div class="relative max-w-xs">
                                    <i data-feather="search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                                    <input id="tableSearch" type="text" placeholder="Search patient, doctor..."
                                        class="pl-9 pr-3 py-2 w-full border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm" id="appointmentsTable">
                                    <thead>
                                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                            <th class="px-4 py-3">Patient</th>
                                            <th class="px-4 py-3">Doctor</th>
                                            <th class="px-4 py-3">Department</th>
                                            <th class="px-4 py-3">Date &amp; Time</th>
                                            <th class="px-4 py-3">Status</th>
                                            <th class="px-4 py-3">Reason / Notes</th>
                                            <th class="px-4 py-3">Cancel Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php foreach ($filtered as $appt):
                                            $status = strtolower($appt['status'] ?? 'pending');
                                            $badge  = $statusColors[$status] ?? 'bg-gray-100 text-gray-700';
                                        ?>
                                            <tr class="hover:bg-gray-50 appt-row">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($appt['patient_name'] ?? '') ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($appt['phone'] ?? '') ?></div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($appt['doctor_name'] ?? '') ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($appt['doctor_specialty'] ?? '') ?></div>
                                                </td>
                                                <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($appt['department'] ?? '') ?></td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <div class="text-gray-900"><?= htmlspecialchars($appt['appointment_date'] ?? '') ?></div>
                                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($appt['appointment_time'] ?? '') ?></div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badge ?>">
                                                        <?= ucfirst($status) ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 max-w-xs">
                                                    <?php if (!empty($appt['reason'])): ?>
                                                        <div class="text-gray-700 text-xs truncate" title="<?= htmlspecialchars($appt['reason']) ?>">
                                                            <?= htmlspecialchars($appt['reason']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($appt['notes'])): ?>
                                                        <div class="text-gray-400 text-xs truncate mt-0.5" title="<?= htmlspecialchars($appt['notes']) ?>">
                                                            <?= htmlspecialchars($appt['notes']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 max-w-xs">
                                                    <?php if (!empty($appt['cancel_reason'])): ?>
                                                        <span class="text-red-700 text-xs" title="<?= htmlspecialchars($appt['cancel_reason']) ?>">
                                                            <?= htmlspecialchars($appt['cancel_reason']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-300 text-xs">â€”</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="mt-3 text-xs text-gray-500">
                                Showing <span id="visibleCount" class="font-semibold"><?= count($filtered) ?></span> of <?= count($filtered) ?> appointments
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script>
        feather.replace();

        // Table search
        const searchInput = document.getElementById('tableSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const q = this.value.toLowerCase();
                const rows = document.querySelectorAll('.appt-row');
                let visible = 0;
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const show = text.includes(q);
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                const countEl = document.getElementById('visibleCount');
                if (countEl) countEl.textContent = visible;
            });
        }
    </script>
</body>

</html>