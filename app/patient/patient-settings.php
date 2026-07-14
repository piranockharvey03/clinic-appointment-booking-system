<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Start patient-specific session
startSession('patient');

// Redirect to login if not authenticated or not a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../../public/login.html');
    exit;
}

$fullName = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Settings | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <style>
        /* Password strength bar */
        .strength-bar { height: 4px; border-radius: 9999px; transition: width 0.3s, background-color 0.3s; }
        /* Animated modal backdrop */
        #passwordModal { transition: opacity 0.2s; }
        #passwordModal.hidden { pointer-events: none; opacity: 0; }
        #passwordModal:not(.hidden) { opacity: 1; }
        #modalCard { transition: transform 0.25s, opacity 0.25s; }
        #passwordModal.hidden #modalCard { transform: scale(0.95); opacity: 0; }
        #passwordModal:not(.hidden) #modalCard { transform: scale(1); opacity: 1; }
    </style>
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
                            <a href="patient-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                My Appointments
                            </a>
                            <a href="patient-profile.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="user" class="mr-3 h-5 w-5"></i>
                                My Profile
                            </a>
                            <a href="how-appointments-work.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="book-open" class="mr-3 h-5 w-5"></i>
                                How It Works
                            </a>
                            <a href="patient-messages.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="message-circle" class="mr-3 h-5 w-5"></i>
                                Messages
                            </a>
                        </div>
                        <div class="mt-8 pt-8 border-t border-blue-700">
                            <a href="patient-settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
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

        <!-- Main content -->
        <div class="main-content flex-1 overflow-auto w-full">
            <!-- Top navigation -->
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">Settings</h1>
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
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 py-3 border-b border-gray-200">
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
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 py-3 border-b border-gray-200">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">Email Notifications</h3>
                                    <p class="text-sm text-gray-500">Receive appointment reminders via email</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700" id="emailStatus">On</span>
                                    <div class="toggle-switch active" id="emailToggle"></div>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 py-3">
                                <div>
                                    <h3 class="text-base font-medium text-gray-900">SMS Notifications</h3>
                                    <p class="text-sm text-gray-500">Receive appointment reminders via SMS</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium text-gray-700" id="smsStatus">Off</span>
                                    <div class="toggle-switch" id="smsToggle"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Settings -->
                    <div class="bg-white shadow rounded-lg p-6 mb-6">
                        <h2 class="text-xl font-bold mb-4 text-gray-900">Security</h2>
                        <p class="text-sm text-gray-500 mb-5">Keep your account secure by using a strong, unique password.</p>
                        <button id="openPasswordModalBtn" onclick="showPasswordModal()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-150">
                            <i data-feather="lock" class="h-4 w-4"></i>
                            Change Password
                        </button>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         Change Password Modal
    ═══════════════════════════════════════════════════ -->
    <div id="passwordModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden"
         role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div id="modalCard" class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <i data-feather="lock" class="h-5 w-5 text-blue-600"></i>
                    </div>
                    <h3 id="modalTitle" class="text-xl font-semibold text-gray-900">Change Password</h3>
                </div>
                <button onclick="hidePasswordModal()"
                    class="text-gray-400 hover:text-gray-600 rounded-full p-1 hover:bg-gray-100 transition-colors">
                    <i data-feather="x" class="h-5 w-5"></i>
                </button>
            </div>

            <!-- Alert banners -->
            <div id="passwordError"
                 class="hidden mb-4 flex items-start gap-2 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <i data-feather="alert-circle" class="h-4 w-4 mt-0.5 flex-shrink-0"></i>
                <span id="passwordErrorText"></span>
            </div>
            <div id="passwordSuccess"
                 class="hidden mb-4 flex items-start gap-2 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
                <i data-feather="check-circle" class="h-4 w-4 mt-0.5 flex-shrink-0"></i>
                <span id="passwordSuccessText"></span>
            </div>

            <!-- Form -->
            <form id="changePasswordForm" onsubmit="return submitPasswordChange()" novalidate>

                <!-- Current Password -->
                <div class="mb-4">
                    <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                    <div class="relative">
                        <input type="password" id="currentPassword" name="current_password"
                            autocomplete="current-password"
                            placeholder="Enter your current password"
                            class="w-full px-3 py-2.5 pr-11 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <button type="button" tabindex="-1"
                            onclick="toggleVis('currentPassword', 'eyeCurrentIcon')"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i id="eyeCurrentIcon" data-feather="eye" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                <!-- New Password -->
                <div class="mb-4">
                    <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                    <div class="relative">
                        <input type="password" id="newPassword" name="new_password"
                            autocomplete="new-password"
                            placeholder="At least 8 characters"
                            oninput="updateStrength(this.value)"
                            class="w-full px-3 py-2.5 pr-11 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <button type="button" tabindex="-1"
                            onclick="toggleVis('newPassword', 'eyeNewIcon')"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i id="eyeNewIcon" data-feather="eye" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <!-- Strength meter -->
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-1">
                            <div id="strengthBar" class="strength-bar h-1 w-0 bg-gray-300"></div>
                        </div>
                        <p id="strengthLabel" class="text-xs text-gray-400 mt-1">Enter a password to see its strength</p>
                    </div>
                    <!-- Requirements checklist -->
                    <ul class="mt-2 space-y-1 text-xs text-gray-500">
                        <li id="req-length" class="flex items-center gap-1.5">
                            <i data-feather="circle" class="h-3 w-3"></i> At least 8 characters
                        </li>
                        <li id="req-letter" class="flex items-center gap-1.5">
                            <i data-feather="circle" class="h-3 w-3"></i> At least one letter
                        </li>
                        <li id="req-number" class="flex items-center gap-1.5">
                            <i data-feather="circle" class="h-3 w-3"></i> At least one number
                        </li>
                    </ul>
                </div>

                <!-- Confirm Password -->
                <div class="mb-6">
                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                    <div class="relative">
                        <input type="password" id="confirmPassword" name="confirm_password"
                            autocomplete="new-password"
                            placeholder="Re-enter your new password"
                            oninput="checkMatch()"
                            class="w-full px-3 py-2.5 pr-11 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <button type="button" tabindex="-1"
                            onclick="toggleVis('confirmPassword', 'eyeConfirmIcon')"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i id="eyeConfirmIcon" data-feather="eye" class="h-4 w-4"></i>
                        </button>
                    </div>
                    <p id="matchMsg" class="text-xs mt-1 hidden"></p>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button type="button" onclick="hidePasswordModal()"
                        class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="submitBtn"
                        class="flex-1 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <span id="submitBtnText">Update Password</span>
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script src="../assets/js/dark-mode.js"></script>
    <script>
        feather.replace();

        // ─────────────────────────────────────────────
        // Modal open / close
        // ─────────────────────────────────────────────
        function showPasswordModal() {
            const modal = document.getElementById('passwordModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                document.getElementById('currentPassword').focus();
                feather.replace();
            }, 50);
        }

        function hidePasswordModal() {
            const modal = document.getElementById('passwordModal');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            document.getElementById('changePasswordForm').reset();
            document.getElementById('passwordError').classList.add('hidden');
            document.getElementById('passwordSuccess').classList.add('hidden');
            document.getElementById('strengthBar').style.width = '0';
            document.getElementById('strengthLabel').textContent = 'Enter a password to see its strength';
            document.getElementById('matchMsg').classList.add('hidden');
            // Reset requirement icons
            ['req-length','req-letter','req-number'].forEach(id => setReq(id, null));
        }

        // Close on backdrop click
        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) hidePasswordModal();
        });

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hidePasswordModal();
        });

        // ─────────────────────────────────────────────
        // Password visibility toggle
        // ─────────────────────────────────────────────
        function toggleVis(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-feather', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-feather', 'eye');
            }
            feather.replace();
        }

        // ─────────────────────────────────────────────
        // Password strength meter
        // ─────────────────────────────────────────────
        function updateStrength(val) {
            const bar   = document.getElementById('strengthBar');
            const label = document.getElementById('strengthLabel');
            const hasLen    = val.length >= 8;
            const hasLetter = /[A-Za-z]/.test(val);
            const hasNum    = /[0-9]/.test(val);
            const hasSpec   = /[^A-Za-z0-9]/.test(val);

            setReq('req-length', hasLen);
            setReq('req-letter', hasLetter);
            setReq('req-number', hasNum);

            let score = 0;
            if (hasLen)    score++;
            if (hasLetter) score++;
            if (hasNum)    score++;
            if (hasSpec)   score++;

            const levels = [
                { w:'0%',   color:'bg-gray-300', text:'',                 textColor:'text-gray-400' },
                { w:'25%',  color:'bg-red-500',  text:'Weak',             textColor:'text-red-600'  },
                { w:'50%',  color:'bg-orange-500',text:'Fair',            textColor:'text-orange-600'},
                { w:'75%',  color:'bg-yellow-500',text:'Good',            textColor:'text-yellow-600'},
                { w:'100%', color:'bg-green-500', text:'Strong',          textColor:'text-green-600' },
            ];

            if (val.length === 0) { bar.style.width='0'; label.textContent='Enter a password to see its strength'; label.className='text-xs mt-1 text-gray-400'; return; }

            const lvl = levels[score] || levels[0];
            bar.style.width   = lvl.w;
            bar.className     = `strength-bar h-1 ${lvl.color}`;
            label.textContent = lvl.text;
            label.className   = `text-xs mt-1 ${lvl.textColor}`;

            checkMatch();
        }

        function setReq(id, passed) {
            const el   = document.getElementById(id);
            const icon = el.querySelector('[data-feather]');
            if (passed === null) {
                el.className = 'flex items-center gap-1.5 text-gray-400';
                icon.setAttribute('data-feather','circle');
            } else if (passed) {
                el.className = 'flex items-center gap-1.5 text-green-600';
                icon.setAttribute('data-feather','check-circle');
            } else {
                el.className = 'flex items-center gap-1.5 text-red-500';
                icon.setAttribute('data-feather','x-circle');
            }
            feather.replace();
        }

        function checkMatch() {
            const np  = document.getElementById('newPassword').value;
            const cp  = document.getElementById('confirmPassword').value;
            const msg = document.getElementById('matchMsg');
            if (!cp) { msg.classList.add('hidden'); return; }
            msg.classList.remove('hidden');
            if (np === cp) {
                msg.textContent = '✓ Passwords match';
                msg.className   = 'text-xs mt-1 text-green-600';
            } else {
                msg.textContent = '✗ Passwords do not match';
                msg.className   = 'text-xs mt-1 text-red-500';
            }
        }

        // ─────────────────────────────────────────────
        // Show / hide alert banners
        // ─────────────────────────────────────────────
        function showError(msg) {
            document.getElementById('passwordErrorText').textContent = msg;
            document.getElementById('passwordError').classList.remove('hidden');
            document.getElementById('passwordSuccess').classList.add('hidden');
            feather.replace();
        }

        function showSuccess(msg) {
            document.getElementById('passwordSuccessText').textContent = msg;
            document.getElementById('passwordSuccess').classList.remove('hidden');
            document.getElementById('passwordError').classList.add('hidden');
            feather.replace();
        }

        // ─────────────────────────────────────────────
        // Form submission
        // ─────────────────────────────────────────────
        function submitPasswordChange() {
            const current = document.getElementById('currentPassword').value.trim();
            const newPwd  = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const btn     = document.getElementById('submitBtn');
            const btnText = document.getElementById('submitBtnText');

            // Client-side validation
            if (!current || !newPwd || !confirm) {
                showError('All fields are required.');
                return false;
            }
            if (newPwd.length < 8) {
                showError('New password must be at least 8 characters long.');
                return false;
            }
            if (!/[A-Za-z]/.test(newPwd) || !/[0-9]/.test(newPwd)) {
                showError('New password must contain at least one letter and one number.');
                return false;
            }
            if (newPwd !== confirm) {
                showError('New passwords do not match.');
                return false;
            }

            // Loading state
            btn.disabled    = true;
            btnText.textContent = 'Updating...';

            fetch('../auth/change-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    current_password: current,
                    new_password:     newPwd,
                    confirm_password: confirm,
                }),
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled    = false;
                btnText.textContent = 'Update Password';
                if (data.success) {
                    showSuccess(data.message || 'Password updated successfully!');
                    document.getElementById('changePasswordForm').reset();
                    ['req-length','req-letter','req-number'].forEach(id => setReq(id, null));
                    document.getElementById('strengthBar').style.width = '0';
                    document.getElementById('strengthLabel').textContent = 'Enter a password to see its strength';
                    // Auto-close after 3 s
                    setTimeout(hidePasswordModal, 3000);
                } else {
                    showError(data.error || 'Failed to change password. Please try again.');
                }
            })
            .catch(() => {
                btn.disabled    = false;
                btnText.textContent = 'Update Password';
                showError('Network error. Please check your connection and try again.');
            });

            return false;
        }

        // ─────────────────────────────────────────────
        // Notification toggles (persisted to localStorage)
        // ─────────────────────────────────────────────
        function loadSettings() {
            const darkOn = localStorage.getItem('darkMode') === 'true';
            if (darkOn) {
                document.getElementById('darkModeToggle').classList.add('active');
                document.getElementById('darkModeStatus').textContent = 'On';
            }

            const emailOff = localStorage.getItem('patient_email_notifications') === 'false';
            if (emailOff) {
                document.getElementById('emailToggle').classList.remove('active');
                document.getElementById('emailStatus').textContent = 'Off';
            }

            const smsOn = localStorage.getItem('patient_sms_notifications') === 'true';
            if (smsOn) {
                document.getElementById('smsToggle').classList.add('active');
                document.getElementById('smsStatus').textContent = 'On';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadSettings();

            document.getElementById('emailToggle').addEventListener('click', function () {
                this.classList.toggle('active');
                const active = this.classList.contains('active');
                document.getElementById('emailStatus').textContent = active ? 'On' : 'Off';
                localStorage.setItem('patient_email_notifications', active);
            });

            document.getElementById('smsToggle').addEventListener('click', function () {
                this.classList.toggle('active');
                const active = this.classList.contains('active');
                document.getElementById('smsStatus').textContent = active ? 'On' : 'Off';
                localStorage.setItem('patient_sms_notifications', active);
            });

            setTimeout(() => feather.replace(), 100);
        });
    </script>
</body>

</html>