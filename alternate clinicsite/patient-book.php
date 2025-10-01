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
    <title>Book Appointment | MediCare Clinic</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        .sidebar { transition: all 0.3s; }
        .sidebar-collapsed { width: 5rem; }
        .sidebar-expanded { width: 16rem; }
        .main-content { transition: margin-left 0.3s; }
        .time-slot:hover { background-color: #3b82f6; color: white; }
        .time-slot.selected { background-color: #3b82f6; color: white; }
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
                            <a href="patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                            <a href="patient-book.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
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
                    <h1 class="text-lg font-semibold text-gray-900">Book New Appointment</h1>
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
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-6">Select a doctor and time for your appointment</h2>

                        <div class="mb-8">
                            <h3 class="text-md font-medium text-gray-900 mb-4">1. Select Department</h3>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <button class="department-btn p-4 border border-gray-200 rounded-lg text-left hover:bg-blue-50 hover:border-blue-300">
                                    <i data-feather="heart" class="h-6 w-6 text-red-500"></i>
                                    <h4 class="mt-2 font-medium text-gray-900">Cardiology</h4>
                                    <p class="mt-1 text-sm text-gray-500">Heart and cardiovascular system</p>
                                </button>
                                <button class="department-btn p-4 border border-gray-200 rounded-lg text-left hover:bg-blue-50 hover:border-blue-300">
                                    <i data-feather="activity" class="h-6 w-6 text-green-500"></i>
                                    <h4 class="mt-2 font-medium text-gray-900">General Medicine</h4>
                                    <p class="mt-1 text-sm text-gray-500">General health and wellness</p>
                                </button>
                                <button class="department-btn p-4 border border-gray-200 rounded-lg text-left hover:bg-blue-50 hover:border-blue-300">
                                    <i data-feather="eye" class="h-6 w-6 text-purple-500"></i>
                                    <h4 class="mt-2 font-medium text-gray-900">Ophthalmology</h4>
                                    <p class="mt-1 text-sm text-gray-500">Eye and vision care</p>
                                </button>
                                <button class="department-btn p-4 border border-gray-200 rounded-lg text-left hover:bg-blue-50 hover:border-blue-300">
                                    <i data-feather="bone" class="h-6 w-6 text-yellow-500"></i>
                                    <h4 class="mt-2 font-medium text-gray-900">Orthopedics</h4>
                                    <p class="mt-1 text-sm text-gray-500">Bones and joints</p>
                                </button>
                                <button class="department-btn p-4 border border-gray-200 rounded-lg text-left hover:bg-blue-50 hover:border-blue-300">
                                    <i data-feather="thermometer" class="h-6 w-6 text-blue-500"></i>
                                    <h4 class="mt-2 font-medium text-gray-900">Pediatrics</h4>
                                    <p class="mt-1 text-sm text-gray-500">Children's health</p>
                                </button>
                                <button class="department-btn p-4 border border-gray-200 rounded-lg text-left hover:bg-blue-50 hover:border-blue-300">
                                    <i data-feather="user" class="h-6 w-6 text-pink-500"></i>
                                    <h4 class="mt-2 font-medium text-gray-900">Dermatology</h4>
                                    <p class="mt-1 text-sm text-gray-500">Skin, hair and nails</p>
                                </button>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-md font-medium text-gray-900 mb-4">2. Select Doctor</h3>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div class="doctor-card p-4 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300">
                                    <div class="flex items-center">
                                        <img class="h-12 w-12 rounded-full" src="http://static.photos/people/200x200/2" alt="Doctor">
                                        <div class="ml-4">
                                            <h4 class="text-sm font-semibold text-gray-900">Dr. Sarah Johnson</h4>
                                            <div class="text-sm text-gray-500">Cardiology</div>
                                            <div class="mt-1 flex items-center">
                                                <i data-feather="star" class="h-4 w-4 text-yellow-400 mr-1"></i>
                                                <span class="text-xs text-gray-500">4.8 (120 reviews)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="doctor-card p-4 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300">
                                    <div class="flex items-center">
                                        <img class="h-12 w-12 rounded-full" src="http://static.photos/people/200x200/3" alt="Doctor">
                                        <div class="ml-4">
                                            <h4 class="text-sm font-semibold text-gray-900">Dr. Michael Chen</h4>
                                            <div class="text-sm text-gray-500">General Medicine</div>
                                            <div class="mt-1 flex items-center">
                                                <i data-feather="star" class="h-4 w-4 text-yellow-400 mr-1"></i>
                                                <span class="text-xs text-gray-500">4.6 (98 reviews)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="doctor-card p-4 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300">
                                    <div class="flex items-center">
                                        <img class="h-12 w-12 rounded-full" src="http://static.photos/people/200x200/5" alt="Doctor">
                                        <div class="ml-4">
                                            <h4 class="text-sm font-semibold text-gray-900">Dr. Emily Wilson</h4>
                                            <div class="text-sm text-gray-500">Pediatrics</div>
                                            <div class="mt-1 flex items-center">
                                                <i data-feather="star" class="h-4 w-4 text-yellow-400 mr-1"></i>
                                                <span class="text-xs text-gray-500">4.9 (140 reviews)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-md font-medium text-gray-900 mb-4">3. Select Time</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                                <button class="time-slot border border-gray-200 text-gray-700 py-2 rounded">9:00 AM</button>
                                <button class="time-slot border border-gray-200 text-gray-700 py-2 rounded">9:30 AM</button>
                                <button class="time-slot border border-gray-200 text-gray-700 py-2 rounded">10:00 AM</button>
                                <button class="time-slot border border-gray-200 text-gray-700 py-2 rounded">10:30 AM</button>
                                <button class="time-slot border border-gray-200 text-gray-700 py-2 rounded">11:00 AM</button>
                                <button class="time-slot border border-gray-200 text-gray-700 py-2 rounded">11:30 AM</button>
                            </div>
                        </div>

                        <div class="text-right">
                            <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                Confirm Booking
                            </button>
                        </div>
                    </div>
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
    </script>
</body>
</html>


