<?php


// Load saved appointments
$dataFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'appointments.json';
$appointments = [];
if (file_exists($dataFile)) {
    $raw = file_get_contents($dataFile);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $appointments = $decoded;
    }
}

// Handle admin actions: approve, cancel, reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['appt_id'])) {
    foreach ($appointments as &$appt) {
        if ($appt['id'] == $_POST['appt_id']) {
            if ($_POST['action'] === 'approve') {
                $appt['status'] = 'approved';
            } elseif ($_POST['action'] === 'cancel') {
                $appt['status'] = 'canceled';
            } elseif ($_POST['action'] === 'reschedule' && !empty($_POST['new_date']) && !empty($_POST['new_time'])) {
                $appt['date'] = $_POST['new_date'];
                $appt['time'] = $_POST['new_time'];
                $appt['status'] = 'rescheduled';
            }
            // Save changes
            file_put_contents($dataFile, json_encode($appointments, JSON_PRETTY_PRINT));
            break;
        }
    }
    unset($appt);
    // Redirect to avoid form resubmission
    header("Location: admin-appointments.php?tab=" . ($_GET['tab'] ?? 'pending'));
    exit;
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
    <title>Admin Appointments | MediCare Clinic</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
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
    <div class="flex h-screen overflow-hidden">
        <div class="sidebar sidebar-expanded bg-blue-800 text-white">
            <!-- ...existing sidebar code... -->
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-blue-700">
                    <div class="flex items-center">
                        <i data-feather="heart" class="h-8 w-8 text-white"></i>
                        <span class="ml-2 text-xl font-bold">MediCare</span>
                    </div>
                    <button class="text-blue-200 hover:text-white">
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
                            <a href="admin-login.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="log-out" class="mr-3 h-5 w-5"></i>
                                Logout
                            </a>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <div class="main-content flex-1 overflow-auto">
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-lg font-semibold text-gray-900">Admin - Manage Appointments</h1>
                </div>
            </header>
            <main class="p-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                            <div class="flex items-center gap-2">
                                <a href="?tab=pending" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Pending</a>
                                <a href="?tab=approved" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'approved' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Approved</a>
                                <a href="?tab=rescheduled" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'rescheduled' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Rescheduled</a>
                                <a href="?tab=canceled" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'canceled' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Canceled</a>
                            </div>
                            <div class="relative">
                                <i data-feather="search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                                <input class="pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm" placeholder="Search doctor or department" />
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php if (empty($filtered)) : ?>
                                <div class="p-6 text-sm text-gray-600">No appointments in this category.</div>
                            <?php else : ?>
                                <?php foreach ($filtered as $appt): ?>
                                    <div class="appointment-card p-4 flex items-center justify-between">
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
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="approve" class="px-3 py-1.5 text-sm rounded-md text-white bg-green-600 hover:bg-green-700">Approve</button>
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
                                                <form method="get" style="display:inline;">
                                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                                    <input type="hidden" name="reschedule" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button type="submit" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-blue-700">Reschedule</button>
                                                </form>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="cancel" class="px-3 py-1.5 text-sm rounded-md text-red-600 border border-red-200" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                                </form>
                                                <span class="px-3 py-1.5 text-sm rounded-md bg-green-100 text-green-700">Approved</span>
                                            <?php elseif ($status === 'rescheduled'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="approve" class="px-3 py-1.5 text-sm rounded-md text-white bg-green-600 hover:bg-green-700">Approve</button>
                                                </form>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="cancel" class="px-3 py-1.5 text-sm rounded-md text-red-600 border border-red-200" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                                </form>
                                                <span class="px-3 py-1.5 text-sm rounded-md bg-blue-100 text-blue-700">Rescheduled</span>
                                            <?php else: ?>
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
    <script>
        feather.replace();
        document.querySelector('[data-feather="menu"]').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');
            const mainContent = document.querySelector('.main-content');
            if (sidebar.classList.contains('sidebar-collapsed')) {
                mainContent.classList.add('ml-20');
            } else {
                mainContent.classList.remove('ml-20');
            }
        });
    </script>
</body>

</html>