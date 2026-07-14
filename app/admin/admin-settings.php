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

$fullName = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings | MediCare Clinic</title>
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
                            <a href="reports.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="bar-chart-2" class="mr-3 h-5 w-5"></i>
                                Reports
                            </a>
                            <a href="doctor-evaluation.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="activity" class="mr-3 h-5 w-5"></i>
                                Doctor Evaluation
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
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h2 class="text-xl font-bold mb-6 text-gray-900">Account</h2>
                        <div class="space-y-4">
                            <button onclick="showAdminPasswordModal()"
                                class="w-full text-left px-4 py-3 border border-gray-300 rounded-md hover:bg-gray-50 text-gray-700">
                                <i data-feather="key" class="inline h-5 w-5 mr-2"></i>
                                Change Password
                            </button>
                            <button onclick="performDatabaseBackup()" id="backupBtn"
                                class="w-full text-left px-4 py-3 border border-gray-300 rounded-md hover:bg-gray-50 text-gray-700">
                                <i data-feather="database" class="inline h-5 w-5 mr-2"></i>
                                Backup Database
                            </button>
                            <div id="backupMessage" class="hidden mt-2 p-3 rounded-md text-sm"></div>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <div class="relative">
                            <input type="password" id="adminNewPassword" name="new_password" required minlength="6"
                                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('adminNewPassword', 'toggleAdminNewPasswordIcon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i id="toggleAdminNewPasswordIcon" data-feather="eye" class="h-5 w-5"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Password must be at least 6 characters long</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                        <div class="relative">
                            <input type="password" id="adminConfirmPassword" name="confirm_password" required
                                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('adminConfirmPassword', 'toggleAdminConfirmPasswordIcon')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <i id="toggleAdminConfirmPasswordIcon" data-feather="eye" class="h-5 w-5"></i>
                            </button>
                        </div>
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
        // Initialize feather icons immediately
        feather.replace();

        // Toggle password visibility function
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-feather', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-feather', 'eye');
            }
            feather.replace();
        }

        // Simple admin password modal functions
        function showAdminPasswordModal() {
            document.getElementById('adminPasswordModal').style.display = 'block';
            document.getElementById('adminCurrentPassword').focus();
            // Re-render feather icons in modal
            setTimeout(() => feather.replace(), 50);
        }

        function hideAdminPasswordModal() {
            document.getElementById('adminPasswordModal').style.display = 'none';
            // Reset form fields manually
            document.getElementById('adminNewPassword').value = '';
            document.getElementById('adminConfirmPassword').value = '';
            document.getElementById('adminPasswordError').style.display = 'none';
            document.getElementById('adminPasswordSuccess').style.display = 'none';
        }

        // Simple form submission
        function submitAdminPasswordChange() {
            const newPassword = document.getElementById('adminNewPassword').value;
            const confirmPassword = document.getElementById('adminConfirmPassword').value;

            // Basic validation
            if (!newPassword || !confirmPassword) {
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
            xhr.open('POST', '../auth/change-password.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {

                    submitBtn.textContent = 'Change Password';
                    submitBtn.disabled = false;

                    if (xhr.status === 200) {
                        // First, check if response is empty
                        if (!xhr.responseText || xhr.responseText.trim() === '') {
                            console.error('Empty response from server');
                            document.getElementById('adminPasswordError').textContent = 'Server returned empty response. Please try again.';
                            document.getElementById('adminPasswordError').style.display = 'block';
                            document.getElementById('adminPasswordSuccess').style.display = 'none';
                            return;
                        }

                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                document.getElementById('adminPasswordSuccess').textContent = response.message || 'Password changed successfully';
                                document.getElementById('adminPasswordSuccess').style.display = 'block';
                                document.getElementById('adminPasswordError').style.display = 'none';

                                // Clear the form fields
                                document.getElementById('adminNewPassword').value = '';
                                document.getElementById('adminConfirmPassword').value = '';

                                setTimeout(function() {
                                    hideAdminPasswordModal();
                                }, 2000);
                            } else {
                                document.getElementById('adminPasswordError').textContent = response.error || 'Failed to change password';
                                document.getElementById('adminPasswordError').style.display = 'block';
                                document.getElementById('adminPasswordSuccess').style.display = 'none';
                            }
                        } catch (e) {
                            console.error('Parse error:', e);
                            console.error('Response text:', xhr.responseText);
                            document.getElementById('adminPasswordError').textContent = 'Invalid response from server. Please check the browser console for details.';
                            document.getElementById('adminPasswordError').style.display = 'block';
                            document.getElementById('adminPasswordSuccess').style.display = 'none';
                        }
                    } else if (xhr.status === 401) {
                        document.getElementById('adminPasswordError').textContent = 'Session expired. Please login again.';
                        document.getElementById('adminPasswordError').style.display = 'block';
                        document.getElementById('adminPasswordSuccess').style.display = 'none';
                        setTimeout(function() {
                            window.location.href = '../../public/admin-login.html';
                        }, 2000);
                    } else if (xhr.status === 0) {
                        console.error('Network error - status 0');
                        document.getElementById('adminPasswordError').textContent = 'Network error. Please check your connection.';
                        document.getElementById('adminPasswordError').style.display = 'block';
                        document.getElementById('adminPasswordSuccess').style.display = 'none';
                    } else {
                        console.error('HTTP Status:', xhr.status);
                        console.error('Response:', xhr.responseText);
                        document.getElementById('adminPasswordError').textContent = 'Error: ' + (xhr.responseText || 'Failed to change password');
                        document.getElementById('adminPasswordError').style.display = 'block';
                        document.getElementById('adminPasswordSuccess').style.display = 'none';
                    }
                }
            };

            // Handle network errors
            xhr.onerror = function() {
                submitBtn.textContent = 'Change Password';
                submitBtn.disabled = false;
                console.error('Network error occurred');
                document.getElementById('adminPasswordError').textContent = 'Network error. Please check your connection and try again.';
                document.getElementById('adminPasswordError').style.display = 'block';
                document.getElementById('adminPasswordSuccess').style.display = 'none';
            };

            // Handle timeout
            xhr.ontimeout = function() {
                submitBtn.textContent = 'Change Password';
                submitBtn.disabled = false;
                console.error('Request timeout');
                document.getElementById('adminPasswordError').textContent = 'Request timed out. Please try again.';
                document.getElementById('adminPasswordError').style.display = 'block';
                document.getElementById('adminPasswordSuccess').style.display = 'none';
            };

            const params = 'current_password=' + encodeURIComponent('') +
                '&new_password=' + encodeURIComponent(newPassword) +
                '&confirm_password=' + encodeURIComponent(confirmPassword);

            xhr.send(params);

            return false; // Prevent form submission
        }

        // Database backup function
        function performDatabaseBackup() {
            const backupBtn = document.getElementById('backupBtn');
            const backupMessage = document.getElementById('backupMessage');

            backupBtn.disabled = true;
            backupBtn.innerHTML = '<i data-feather="loader" class="inline h-5 w-5 mr-2 animate-spin"></i> Creating Backup...';
            feather.replace();

            backupMessage.className = 'hidden mt-2 p-3 rounded-md text-sm';

            // Call backup endpoint
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'backup-database.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    backupBtn.disabled = false;
                    backupBtn.innerHTML = '<i data-feather="database" class="inline h-5 w-5 mr-2"></i> Backup Database';
                    feather.replace();

                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                backupMessage.textContent = response.message + ' at ' + response.timestamp;
                                backupMessage.className = 'mt-2 p-3 rounded-md text-sm bg-green-100 text-green-800';

                                // Store last backup time
                                localStorage.setItem('admin_last_backup', response.timestamp);
                            } else {
                                backupMessage.textContent = response.error || 'Backup failed';
                                backupMessage.className = 'mt-2 p-3 rounded-md text-sm bg-red-100 text-red-800';
                            }
                        } catch (e) {
                            backupMessage.textContent = 'Invalid response from server';
                            backupMessage.className = 'mt-2 p-3 rounded-md text-sm bg-red-100 text-red-800';
                        }
                    } else {
                        backupMessage.textContent = 'Failed to create backup. Please try again.';
                        backupMessage.className = 'mt-2 p-3 rounded-md text-sm bg-red-100 text-red-800';
                    }

                    // Hide message after 5 seconds
                    setTimeout(function() {
                        backupMessage.className = 'hidden mt-2 p-3 rounded-md text-sm';
                    }, 5000);
                }
            };

            xhr.send();
        }

        // Load saved settings from localStorage
        function loadSettings() {
            // Load email notifications setting
            const emailEnabled = localStorage.getItem('admin_email_notifications');
            if (emailEnabled !== null) {
                const isEnabled = emailEnabled === 'true';
                if (isEnabled) {
                    document.getElementById('emailToggle').classList.add('active');
                    document.getElementById('emailStatus').textContent = 'On';
                } else {
                    document.getElementById('emailToggle').classList.remove('active');
                    document.getElementById('emailStatus').textContent = 'Off';
                }
            }

            // Load auto-approve setting
            const autoApprove = localStorage.getItem('admin_auto_approve');
            if (autoApprove !== null) {
                const isEnabled = autoApprove === 'true';
                if (isEnabled) {
                    document.getElementById('autoApproveToggle').classList.add('active');
                    document.getElementById('autoApproveStatus').textContent = 'On';
                } else {
                    document.getElementById('autoApproveToggle').classList.remove('active');
                    document.getElementById('autoApproveStatus').textContent = 'Off';
                }
            }

            // Load maintenance mode setting
            const maintenanceMode = localStorage.getItem('admin_maintenance_mode');
            if (maintenanceMode !== null) {
                const isEnabled = maintenanceMode === 'true';
                if (isEnabled) {
                    document.getElementById('maintenanceToggle').classList.add('active');
                    document.getElementById('maintenanceStatus').textContent = 'On';
                } else {
                    document.getElementById('maintenanceToggle').classList.remove('active');
                    document.getElementById('maintenanceStatus').textContent = 'Off';
                }
            }
        }

        // Add event listeners when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Load saved settings
            loadSettings();

            // Email Notifications Toggle
            document.getElementById('emailToggle').addEventListener('click', function() {
                this.classList.toggle('active');
                const isActive = this.classList.contains('active');
                document.getElementById('emailStatus').textContent = isActive ? 'On' : 'Off';
                localStorage.setItem('admin_email_notifications', isActive);
            });

            // Auto-Approve Toggle
            document.getElementById('autoApproveToggle').addEventListener('click', function() {
                this.classList.toggle('active');
                const isActive = this.classList.contains('active');
                document.getElementById('autoApproveStatus').textContent = isActive ? 'On' : 'Off';
                localStorage.setItem('admin_auto_approve', isActive);
            });

            // Maintenance Mode Toggle
            document.getElementById('maintenanceToggle').addEventListener('click', function() {
                this.classList.toggle('active');
                const isActive = this.classList.contains('active');
                document.getElementById('maintenanceStatus').textContent = isActive ? 'On' : 'Off';
                localStorage.setItem('admin_maintenance_mode', isActive);

                // Show warning if maintenance mode is enabled
                if (isActive) {
                    customAlert('Maintenance mode enabled. Users may experience limited functionality.', 'Maintenance Mode', 'warning');
                }
            });

            // Close modal when clicking outside
            document.getElementById('adminPasswordModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideAdminPasswordModal();
                }
            });

            // Re-render feather icons to ensure all icons are loaded
            setTimeout(() => feather.replace(), 100);
        });
    </script>
    <script src="../assets/js/custom-modal.js"></script>
</body>

</html>