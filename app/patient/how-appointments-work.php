<?php
require_once '../../config/session-config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../../public/login.html');
    exit;
}

$fullName = $_SESSION['user_name'];
$firstName = trim(explode(' ', $fullName)[0]);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How Appointments Work | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
</head>

<body class="bg-gray-50 font-sans antialiased">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="flex h-screen overflow-hidden">
        <div class="sidebar bg-blue-800 text-white" id="sidebar">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-blue-700">
                    <div class="flex items-center">
                        <i data-feather="heart" class="h-8 w-8 text-white"></i>
                        <span class="ml-2 text-xl font-bold">MediCare</span>
                    </div>
                    <button class="text-blue-200 hover:text-white md:hidden" id="menuBtn" type="button">
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
                            <a href="patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                            <a href="../../public/patient-book.html" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="plus-circle" class="mr-3 h-5 w-5"></i>
                                Book Appointment
                            </a>
                            <a href="how-appointments-work.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
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

        <div class="main-content flex-1 overflow-auto w-full">
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn" type="button">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">How Appointments Work</h1>
                    <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($firstName); ?></span>
                </div>
            </header>

            <main class="p-4 sm:px-6 lg:px-8">
                <div class="bg-blue-600 rounded-lg shadow-sm p-6 mb-6 text-white">
                    <h2 class="text-2xl font-bold">Appointment Guide</h2>
                    <p class="mt-2 text-blue-100">This page explains how booking, confirmation, rescheduling, check-in, and notifications work.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <section class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i data-feather="clipboard" class="h-5 w-5 text-blue-600"></i>
                            1) Booking an Appointment
                        </h3>
                        <ol class="list-decimal pl-5 text-sm text-gray-700 space-y-2">
                            <li>Select a department and doctor.</li>
                            <li>Choose your date and time slot.</li>
                            <li>Enter your phone and reason for visit.</li>
                            <li>Click Confirm Booking to submit.</li>
                        </ol>
                        <p class="mt-4 text-sm text-gray-600">If a doctor is already booked for that exact date and time, the booking will be blocked and you must choose another slot.</p>
                    </section>

                    <section class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i data-feather="check-circle" class="h-5 w-5 text-green-600"></i>
                            2) Status Meanings
                        </h3>
                        <ul class="text-sm text-gray-700 space-y-2">
                            <li><span class="font-semibold">Pending:</span> Appointment request sent, waiting for doctor action.</li>
                            <li><span class="font-semibold">Approved:</span> Doctor confirmed your appointment.</li>
                            <li><span class="font-semibold">Rescheduled:</span> Doctor moved your date/time and you were notified.</li>
                            <li><span class="font-semibold">Canceled:</span> Appointment canceled by patient, doctor, or no-show policy.</li>
                            <li><span class="font-semibold">Completed:</span> Appointment finished by doctor.</li>
                        </ul>
                    </section>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <section class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i data-feather="refresh-cw" class="h-5 w-5 text-indigo-600"></i>
                            3) Rescheduling Policy
                        </h3>
                        <ul class="text-sm text-gray-700 space-y-2">
                            <li>Only doctors can reschedule appointments.</li>
                            <li>Doctors cannot reschedule to a slot already occupied by another active appointment.</li>
                            <li>When a reschedule happens, you receive a notification with the old and new schedule.</li>
                        </ul>
                    </section>

                    <section class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i data-feather="map-pin" class="h-5 w-5 text-teal-600"></i>
                            4) Check-In and No-Show
                        </h3>
                        <ul class="text-sm text-gray-700 space-y-2">
                            <li>Check-in is available on the appointment day for approved appointments.</li>
                            <li>After check-in, you get a verification code for the doctor.</li>
                            <li>If check-in is not completed in time, the system may mark the appointment as no-show and cancel it automatically.</li>
                        </ul>
                    </section>
                </div>

                <section class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i data-feather="bell" class="h-5 w-5 text-amber-600"></i>
                        5) Notifications You Receive
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                        <div class="p-4 rounded-md bg-gray-50 border border-gray-200">
                            <p class="font-semibold text-gray-900">Approved</p>
                            <p class="mt-1">When your appointment gets confirmed.</p>
                        </div>
                        <div class="p-4 rounded-md bg-gray-50 border border-gray-200">
                            <p class="font-semibold text-gray-900">Rescheduled</p>
                            <p class="mt-1">When your doctor changes your appointment date/time.</p>
                        </div>
                        <div class="p-4 rounded-md bg-gray-50 border border-gray-200">
                            <p class="font-semibold text-gray-900">Canceled</p>
                            <p class="mt-1">When canceled by patient, doctor, or due to no-show.</p>
                        </div>
                        <div class="p-4 rounded-md bg-gray-50 border border-gray-200">
                            <p class="font-semibold text-gray-900">Completed</p>
                            <p class="mt-1">When the doctor marks your appointment complete.</p>
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap gap-3">
                    <a href="../../public/patient-book.html" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium">
                        <i data-feather="plus" class="h-4 w-4 mr-2"></i>
                        Book an Appointment
                    </a>
                    <a href="patient-appointments.php" class="inline-flex items-center px-4 py-2 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        <i data-feather="calendar" class="h-4 w-4 mr-2"></i>
                        View My Appointments
                    </a>
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