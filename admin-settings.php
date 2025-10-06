<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header('Location: adminlogin.php');
    exit;
}

$fullName = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings | MediCare Clinic</title>
    <link rel="icon" type="image/x-icon" href="/static/favicon.ico">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="assets/js/dark-mode.js"></script>
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
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="sidebar sidebar-expanded bg-blue-800 text-white">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between p-4 border-b border-blue-700">
                    <div class="flex items-center">
                        <i data-feather="heart" class="h-8 w-8 text-white"></i>
                        <span class="ml-2 text-xl font-bold">MediCare Admin</span>
                    </div>
                    <button id="sidebarToggle" class="text-blue-200 hover:text-white">
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
                            <a href="admin-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                           
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="admin-settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
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
                    <h1 class="text-lg font-semibold text-gray-900">Admin Settings</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-1 text-gray-400 hover:text-gray-500">
                            <i data-feather="bell" class="h-6 w-6"></i>
                        </button>
                        <div class="relative">
                            <button class="flex items-center space-x-2">
                                <img class="h-8 w-8 rounded-full" src="http://static.photos/people/200x200/1" alt="Admin profile">
                                <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($fullName); ?></span>
                                <i data-feather="chevron-down" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto">
                    <!-- Appearance Settings -->
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h2 class="text-xl font-bold mb-6 text-gray-900">Appearance</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Dark Mode</h3>
                                    <p class="text-sm text-gray-500">Toggle dark mode for better viewing at night</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700" id="darkModeStatus">Off</span>
                                    <div class="toggle-switch" id="darkModeToggle"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h2 class="text-xl font-bold mb-6 text-gray-900">System Settings</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Email Notifications</h3>
                                    <p class="text-sm text-gray-500">Receive system notifications via email</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700" id="emailStatus">On</span>
                                    <div class="toggle-switch active" id="emailToggle"></div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Auto-Approve Appointments</h3>
                                    <p class="text-sm text-gray-500">Automatically approve new appointments</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700" id="autoApproveStatus">Off</span>
                                    <div class="toggle-switch" id="autoApproveToggle"></div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Maintenance Mode</h3>
                                    <p class="text-sm text-gray-500">Put the system in maintenance mode</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700" id="maintenanceStatus">Off</span>
                                    <div class="toggle-switch" id="maintenanceToggle"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Settings -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-6 text-gray-900">Account</h2>
                        <div class="space-y-4">
                            <button class="w-full text-left px-4 py-3 border border-gray-300 rounded-md hover:bg-gray-50 text-gray-700">
                                <i data-feather="key" class="inline h-5 w-5 mr-2"></i>
                                Change Password
                            </button>
                            <button class="w-full text-left px-4 py-3 border border-gray-300 rounded-md hover:bg-gray-50 text-gray-700">
                                <i data-feather="shield" class="inline h-5 w-5 mr-2"></i>
                                Two-Factor Authentication
                            </button>
                            <button class="w-full text-left px-4 py-3 border border-gray-300 rounded-md hover:bg-gray-50 text-gray-700">
                                <i data-feather="download" class="inline h-5 w-5 mr-2"></i>
                                Export Data
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize Feather icons
        feather.replace();

        // Sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
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

        // Dark mode is handled by assets/js/dark-mode.js

        // Email Notifications Toggle
        const emailToggle = document.getElementById('emailToggle');
        const emailStatus = document.getElementById('emailStatus');

        emailToggle.addEventListener('click', function() {
            emailToggle.classList.toggle('active');
            emailStatus.textContent = emailToggle.classList.contains('active') ? 'On' : 'Off';
        });

        // Auto-Approve Toggle
        const autoApproveToggle = document.getElementById('autoApproveToggle');
        const autoApproveStatus = document.getElementById('autoApproveStatus');

        autoApproveToggle.addEventListener('click', function() {
            autoApproveToggle.classList.toggle('active');
            autoApproveStatus.textContent = autoApproveToggle.classList.contains('active') ? 'On' : 'Off';
        });

        // Maintenance Mode Toggle
        const maintenanceToggle = document.getElementById('maintenanceToggle');
        const maintenanceStatus = document.getElementById('maintenanceStatus');

        maintenanceToggle.addEventListener('click', function() {
            maintenanceToggle.classList.toggle('active');
            maintenanceStatus.textContent = maintenanceToggle.classList.contains('active') ? 'On' : 'Off';
        });
    </script>
</body>

</html>
