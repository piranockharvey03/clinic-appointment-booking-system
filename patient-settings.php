<?php

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: login.html');
    exit;
}

$fullName = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | MediCare Clinic</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
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

        .dark-mode {
            background: #18181b !important;
            color: #e5e7eb !important;
        }

        .dark-mode .bg-white {
            background: #27272a !important;
        }

        .dark-mode .text-gray-900 {
            color: #e5e7eb !important;
        }

        .dark-mode .text-gray-500 {
            color: #a1a1aa !important;
        }

        .dark-mode input,
        .dark-mode select,
        .dark-mode textarea {
            background: #27272a !important;
            color: #e5e7eb !important;
            border-color: #52525b !important;
        }

        .dark-mode .shadow {
            box-shadow: 0 1px 3px 0 #0003, 0 1px 2px 0 #0002;
        }

        .dark-mode .bg-blue-800 {
            background: #232336 !important;
        }

        .dark-mode .text-white {
            color: #e5e7eb !important;
        }

        .dark-mode .border-blue-700 {
            border-color: #232336 !important;
        }

        .dark-mode .bg-blue-900 {
            background: #18181b !important;
        }

        .dark-mode .text-blue-100 {
            color: #a1a1aa !important;
        }

        .dark-mode .hover\:bg-blue-700:hover {
            background: #27272a !important;
        }

        .dark-mode .hover\:text-white:hover {
            color: #fff !important;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased" id="body">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
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
                            <a href="patient-profile.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="user" class="mr-3 h-5 w-5"></i>
                                Profile
                            </a>
                            <a href="patient-settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
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
        <!-- Main content -->
        <div class="main-content flex-1 overflow-auto">
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-lg font-semibold text-gray-900">Settings</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-1 text-gray-400 hover:text-gray-500">
                            <i data-feather="bell" class="h-6 w-6"></i>
                        </button>
                        <div class="relative">
                            <button class="flex items-center space-x-2">
                                <img class="h-8 w-8 rounded-full" src="http://static.photos/people/200x200/1" alt="User profile">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($fullName); ?></span>
                                <i data-feather="chevron-down" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            <main class="p-4 sm:px-6 lg:px-8">
                <div class="max-w-xl mx-auto bg-white shadow rounded-lg p-8">
                    <h2 class="text-xl font-bold mb-6 text-gray-900">Appearance</h2>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 font-medium">Dark Mode</span>
                        <label class="inline-flex relative items-center cursor-pointer">
                            <input type="checkbox" id="darkModeToggle" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer dark:bg-gray-700 peer-checked:bg-blue-600 transition"></div>
                            <span class="ml-3 text-sm font-medium text-gray-900">Enable</span>
                        </label>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        feather.replace();

        // Sidebar toggle
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

        // Dark mode toggle logic
        const darkToggle = document.getElementById('darkModeToggle');
        const body = document.getElementById('body');

        // Load preference from localStorage
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            darkToggle.checked = true;
        }

        darkToggle.addEventListener('change', function() {
            if (this.checked) {
                body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>
</body>

</html>