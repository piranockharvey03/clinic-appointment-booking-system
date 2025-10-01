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

// Handle status change actions and reschedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['appt_id'])) {
    foreach ($appointments as &$appt) {
        if ($appt['id'] == $_POST['appt_id']) {
            if ($_POST['action'] === 'cancel') {
                $appt['status'] = 'canceled';
            } elseif ($_POST['action'] === 'complete') {
                $appt['status'] = 'past';
            } elseif ($_POST['action'] === 'reschedule' && !empty($_POST['new_date']) && !empty($_POST['new_time'])) {
                $appt['date'] = $_POST['new_date'];
                $appt['time'] = $_POST['new_time'];
            }
            // Save changes
            file_put_contents($dataFile, json_encode($appointments, JSON_PRETTY_PRINT));
            break;
        }
    }
    unset($appt);
    // Redirect to avoid form resubmission
    header("Location: patient-appointments.php?tab=" . ($_GET['tab'] ?? 'upcoming'));
    exit;
}

// Determine which tab to show
$tab = $_GET['tab'] ?? 'upcoming';
function filter_appointments($appointments, $tab)
{
    if ($tab === 'past') return array_filter($appointments, fn($a) => ($a['status'] ?? 'upcoming') === 'past');
    if ($tab === 'canceled') return array_filter($appointments, fn($a) => ($a['status'] ?? 'upcoming') === 'canceled');
    return array_filter($appointments, fn($a) => !isset($a['status']) || $a['status'] === 'upcoming');
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
                            <a href="patient-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                            <a href="patient-book.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="plus-circle" class="mr-3 h-5 w-5"></i>
                                Book Appointment
                            </a>
                            <a href="patient-records.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="file-text" class="mr-3 h-5 w-5"></i>
                                Medical Records
                            </a>
                            <a href="patient-prescriptions.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="file-plus" class="mr-3 h-5 w-5"></i>
                                Prescriptions
                            </a>
                            <a href="patient-messages.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="message-square" class="mr-3 h-5 w-5"></i>
                                Messages
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="patient-profile.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="user" class="mr-3 h-5 w-5"></i>
                                Profile
                            </a>
                            <a href="patient-settings.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="settings" class="mr-3 h-5 w-5"></i>
                                Settings
                            </a>
                            <a href="index.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
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
                    <h1 class="text-lg font-semibold text-gray-900">Appointments</h1>
                    <a href="patient-book.html" class="inline-flex items-center px-3 py-2 text-sm rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i data-feather="plus" class="mr-1 h-4 w-4"></i>
                        New
                    </a>
                </div>
            </header>
            <main class="p-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                            <div class="flex items-center gap-2">
                                <a href="?tab=upcoming" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'upcoming' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Upcoming</a>
                                <a href="?tab=past" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'past' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Past</a>
                                <a href="?tab=canceled" class="px-3 py-1.5 text-sm rounded-md <?= $tab === 'canceled' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Canceled</a>
                            </div>
                            <div class="relative">
                                <i data-feather="search" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                                <input class="pl-9 pr-3 py-2 border border-gray-300 rounded-md text-sm" placeholder="Search doctor or department" />
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php if (empty($filtered)) : ?>
                                <div class="p-6 text-sm text-gray-600">No appointments yet. <a class="text-blue-600" href="patient-book.html">Book your appointment here</a>.</div>
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
                                                <h4 class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($appt['doctorName']) ?></h4>
                                                <div class="text-xs text-gray-500">
                                                    <?= htmlspecialchars($appt['doctorSpecialty']) ?> • <?= htmlspecialchars($appt['date']) ?>, <?= htmlspecialchars($appt['time']) ?>
                                                </div>
                                                <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">Department: <?= htmlspecialchars($appt['department']) ?></div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <?php if (($appt['status'] ?? 'upcoming') === 'upcoming'): ?>
                                                <form method="get" style="display:inline;">
                                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                                                    <input type="hidden" name="reschedule" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button type="submit" class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-100 text-blue-700">Reschedule</button>
                                                </form>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                                                    <button name="action" value="cancel" class="px-3 py-1.5 text-sm rounded-md text-red-600 border border-red-200" onclick="return confirm('Cancel this appointment?')">Cancel</button>
                                                    <button name="action" value="complete" class="px-3 py-1.5 text-sm rounded-md text-white bg-blue-600" onclick="return confirm('Mark as completed?')">Complete</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="px-3 py-1.5 text-sm rounded-md <?= ($appt['status'] === 'canceled') ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700' ?>">
                                                    <?= ucfirst($appt['status']) ?>
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