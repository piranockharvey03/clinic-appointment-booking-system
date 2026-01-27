<?php
session_start();

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login if not authenticated or not an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../html/admin-login.html');
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
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
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
                    <h1 class="text-lg font-semibold text-gray-900">Admin Settings</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($fullName); ?></span>
                        <i data-feather="user" class="h-6 w-6 text-blue-600"></i>
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

                    <!-- Notification Settings -->
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h2 class="text-xl font-bold mb-6 text-gray-900">Notifications</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Email Notifications</h3>
                                    <p class="text-sm text-gray-500">Receive system alerts via email</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700" id="emailStatus">On</span>
                                    <div class="toggle-switch active" id="emailToggle"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h2 class="text-xl font-bold mb-6 text-gray-900">System</h2>
                        <div class="space-y-4">
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
                            <button onclick="showAdminPasswordModal()"
                                    class="w-full text-left px-4 py-3 border border-gray-300 rounded-md hover:bg-gray-50 text-gray-700">
                                <i data-feather="key" class="inline h-5 w-5 mr-2"></i>
                                Change Password
                            </button>
                            <button class="w-full text-left px-4 py-3 border border-gray-300 rounded-md hover:bg-gray-50 text-gray-700">
                                <i data-feather="database" class="inline h-5 w-5 mr-2"></i>
                                Backup Database
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="adminPasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Change Admin Password</h3>
                    <button onclick="hideAdminPasswordModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-feather="x" class="h-6 w-6"></i>
                    </button>
                </div>

                <form onsubmit="return submitAdminPasswordChange()">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password" id="adminCurrentPassword" name="current_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" id="adminNewPassword" name="new_password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Password must be at least 6 characters long</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <input type="password" id="adminConfirmPassword" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div id="adminPasswordError" class="text-red-600 text-sm mb-4 hidden"></div>
                    <div id="adminPasswordSuccess" class="text-green-600 text-sm mb-4 hidden"></div>

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="hideAdminPasswordModal()"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                id="submitAdminPasswordChange">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <script>
        feather.replace();

        // Simple admin password modal functions
        function showAdminPasswordModal() {
            document.getElementById('adminPasswordModal').style.display = 'block';
            document.getElementById('adminCurrentPassword').focus();
        }

        function hideAdminPasswordModal() {
            document.getElementById('adminPasswordModal').style.display = 'none';
            document.getElementById('adminPasswordChangeForm').reset();
            document.getElementById('adminPasswordError').style.display = 'none';
            document.getElementById('adminPasswordSuccess').style.display = 'none';
        }

        // Simple form submission
        function submitAdminPasswordChange() {
            const currentPassword = document.getElementById('adminCurrentPassword').value;
            const newPassword = document.getElementById('adminNewPassword').value;
            const confirmPassword = document.getElementById('adminConfirmPassword').value;

            // Basic validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                document.getElementById('adminPasswordError').textContent = 'All fields are required';
                document.getElementById('adminPasswordError').style.display = 'block';
                document.getElementById('adminPasswordSuccess').style.display = 'none';
                return false;
            }

            if (newPassword !== confirmPassword) {
                document.getElementById('adminPasswordError').textContent = 'New passwords do not match';
                document.getElementById('adminPasswordError').style.display = 'block';
                document.getElementById('adminPasswordSuccess').style.display = 'none';
                return false;
            }

            if (newPassword.length < 6) {
                document.getElementById('adminPasswordError').textContent = 'Password must be at least 6 characters long';
                document.getElementById('adminPasswordError').style.display = 'block';
                document.getElementById('adminPasswordSuccess').style.display = 'none';
                return false;
            }

            // Show loading state
            const submitBtn = document.getElementById('submitAdminPasswordChange');
            submitBtn.textContent = 'Changing...';
            submitBtn.disabled = true;

            // Send request (simple XMLHttpRequest)
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'change-password.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    submitBtn.textContent = 'Change Password';
                    submitBtn.disabled = false;

                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                document.getElementById('adminPasswordSuccess').textContent = response.message;
                                document.getElementById('adminPasswordSuccess').style.display = 'block';
                                document.getElementById('adminPasswordError').style.display = 'none';
                                setTimeout(function() {
                                    hideAdminPasswordModal();
                                }, 2000);
                            } else {
                                document.getElementById('adminPasswordError').textContent = response.error;
                                document.getElementById('adminPasswordError').style.display = 'block';
                                document.getElementById('adminPasswordSuccess').style.display = 'none';
                            }
                        } catch (e) {
                            document.getElementById('adminPasswordError').textContent = 'Invalid response from server';
                            document.getElementById('adminPasswordError').style.display = 'block';
                            document.getElementById('adminPasswordSuccess').style.display = 'none';
                        }
                    } else {
                        document.getElementById('adminPasswordError').textContent = 'Failed to change password. Please try again.';
                        document.getElementById('adminPasswordError').style.display = 'block';
                        document.getElementById('adminPasswordSuccess').style.display = 'none';
                    }
                }
            };

            const params = 'current_password=' + encodeURIComponent(currentPassword) +
                          '&new_password=' + encodeURIComponent(newPassword) +
                          '&confirm_password=' + encodeURIComponent(confirmPassword);

            xhr.send(params);

            return false; // Prevent form submission
        }

        // Add event listeners when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Email Notifications Toggle
            document.getElementById('emailToggle').addEventListener('click', function() {
                this.classList.toggle('active');
                document.getElementById('emailStatus').textContent = this.classList.contains('active') ? 'On' : 'Off';
            });

            // Auto-Approve Toggle
            document.getElementById('autoApproveToggle').addEventListener('click', function() {
                this.classList.toggle('active');
                document.getElementById('autoApproveStatus').textContent = this.classList.contains('active') ? 'On' : 'Off';
            });

            // Maintenance Mode Toggle
            document.getElementById('maintenanceToggle').addEventListener('click', function() {
                this.classList.toggle('active');
                document.getElementById('maintenanceStatus').textContent = this.classList.contains('active') ? 'On' : 'Off';
            });

            // Close modal when clicking outside
            document.getElementById('adminPasswordModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideAdminPasswordModal();
                }
            });
        });
    </script>
</body>

</html>
