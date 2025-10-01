<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require_auth();

$stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = :id');
$stmt->execute([':id' => current_user_id()]);
$user = $stmt->fetch();
$fullName = $user ? $user['full_name'] : 'Patient';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | MediCare Clinic</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .sidebar { transition: all 0.3s; }
        .sidebar-collapsed { width: 5rem; }
        .sidebar-expanded { width: 16rem; }
        .main-content { transition: margin-left 0.3s; }
        .appointment-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .tab-active { border-bottom: 2px solid #3b82f6; color: #3b82f6; }
    </style>
    <script>
        function logout() { window.location.href = 'logout.php'; }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <div class="sidebar sidebar-expanded bg-blue-800 text-white">
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
                            <a href="patient-book.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="plus-circle" class="mr-3 h-5 w-5"></i>
                                Book Appointment
                            </a>
                            <a href="#" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="file-text" class="mr-3 h-5 w-5"></i>
                                Medical Records
                            </a>
                            <a href="#" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="file-plus" class="mr-3 h-5 w-5"></i>
                                Prescriptions
                            </a>
                            <a href="#" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="message-square" class="mr-3 h-5 w-5"></i>
                                Messages
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="#" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="user" class="mr-3 h-5 w-5"></i>
                                Profile
                            </a>
                            <a href="#" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="settings" class="mr-3 h-5 w-5"></i>
                                Settings
                            </a>
                            <a href="javascript:logout()" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
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
                    <h1 class="text-lg font-semibold text-gray-900">My Appointments</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-1 text-gray-400 hover:text-gray-500">
                            <i data-feather="bell" class="h-6 w-6"></i>
                        </button>
                        <div class="relative">
                            <button class="flex items-center space-x-2">
                                <img class="h-8 w-8 rounded-full" src="http://static.photos/people/200x200/1" alt="User profile">
                                <span class="text-sm font-medium text-gray-700"><?php echo h($fullName); ?></span>
                                <i data-feather="chevron-down" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:px-6 lg:px-8">
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <a href="#" class="tab-active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Upcoming
                        </a>
                        <a href="#" class="whitespace-nowrap py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Past
                        </a>
                        <a href="#" class="whitespace-nowrap py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            Cancelled
                        </a>
                    </nav>
                </div>

                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="divide-y divide-gray-200">
                        <div class="appointment-card transition duration-300 ease-in-out p-6 hover:bg-gray-50">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start">
                                    <img class="h-12 w-12 rounded-full" src="http://static.photos/people/200x200/2" alt="Doctor">
                                    <div class="ml-4">
                                        <h4 class="text-lg font-semibold text-gray-900">Dr. Sarah Johnson</h4>
                                        <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6">
                                            <div class="flex items-center text-sm text-gray-500">
                                                <i data-feather="clock" class="h-4 w-4 mr-1"></i>
                                                <span>Tomorrow, June 8, 10:30 AM - 11:00 AM</span>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-500 mt-1 sm:mt-0">
                                                <i data-feather="map-pin" class="h-4 w-4 mr-1"></i>
                                                <span>Cardiology Department, Room 205</span>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Confirmed
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 sm:mt-0 flex space-x-3">
                                    <button class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i data-feather="message-square" class="h-4 w-4 mr-1"></i>
                                        Message
                                    </button>
                                    <button class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                        <i data-feather="video" class="h-4 w-4 mr-1"></i>
                                        Join
                                    </button>
                                    <button class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i data-feather="x" class="h-4 w-4 mr-1"></i>
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="appointment-card transition duration-300 ease-in-out p-6 hover:bg-gray-50">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start">
                                    <img class="h-12 w-12 rounded-full" src="http://static.photos/people/200x200/3" alt="Doctor">
                                    <div class="ml-4">
                                        <h4 class="text-lg font-semibold text-gray-900">Dr. Michael Chen</h4>
                                        <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6">
                                            <div class="flex items-center text-sm text-gray-500">
                                                <i data-feather="clock" class="h-4 w-4 mr-1"></i>
                                                <span>Friday, June 10, 2:15 PM - 2:45 PM</span>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-500 mt-1 sm:mt-0">
                                                <i data-feather="map-pin" class="h-4 w-4 mr-1"></i>
                                                <span>General Medicine, Room 112</span>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Pending Confirmation
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 sm:mt-0 flex space-x-3">
                                    <button class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i data-feather="message-square" class="h-4 w-4 mr-1"></i>
                                        Message
                                    </button>
                                    <button class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i data-feather="x" class="h-4 w-4 mr-1"></i>
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="appointment-card transition duration-300 ease-in-out p-6 hover:bg-gray-50">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start">
                                    <img class="h-12 w-12 rounded-full" src="http://static.photos/people/200x200/5" alt="Doctor">
                                    <div class="ml-4">
                                        <h4 class="text-lg font-semibold text-gray-900">Dr. Emily Wilson</h4>
                                        <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6">
                                            <div class="flex items-center text-sm text-gray-500">
                                                <i data-feather="clock" class="h-4 w-4 mr-1"></i>
                                                <span>Monday, June 13, 9:00 AM - 9:30 AM</span>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-500 mt-1 sm:mt-0">
                                                <i data-feather="map-pin" class="h-4 w-4 mr-1"></i>
                                                <span>Pediatrics, Room 301</span>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Confirmed
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 sm:mt-0 flex space-x-3">
                                    <button class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i data-feather="message-square" class="h-4 w-4 mr-1"></i>
                                        Message
                                    </button>
                                    <button class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                        <i data-feather="video" class="h-4 w-4 mr-1"></i>
                                        Join
                                    </button>
                                    <button class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <i data-feather="x" class="h-4 w-4 mr-1"></i>
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="patient-book.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        <i data-feather="plus" class="mr-2"></i>
                        Book New Appointment
                    </a>
                </div>
            </main>
        </div>
    </div>

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

        document.querySelectorAll('nav a').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('nav a').forEach(t => t.classList.remove('tab-active'));
                this.classList.add('tab-active');
            });
        });
    </script>
</body>
</html>


