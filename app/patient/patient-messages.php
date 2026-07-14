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
$firstName = trim(explode(' ', $fullName)[0]);
$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <link rel="stylesheet" href="../assets/css/responsive-sidebar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                            <a href="patient-dashboard.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="home" class="mr-3 h-5 w-5"></i>
                                Dashboard
                            </a>
                            <a href="patient-appointments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="calendar" class="mr-3 h-5 w-5"></i>
                                Appointments
                            </a>
                            <a href="../../public/patient-book.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="plus-circle" class="mr-3 h-5 w-5"></i>
                                Book Appointment
                            </a>
                            <a href="how-appointments-work.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-blue-100 hover:bg-blue-700 hover:text-white">
                                <i data-feather="book-open" class="mr-3 h-5 w-5"></i>
                                How It Works
                            </a>
                            <a href="patient-messages.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md bg-blue-900 text-white">
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

        <!-- Main content -->
        <div class="main-content flex-1 overflow-auto w-full flex flex-col">
            <!-- Top navigation -->
            <header class="bg-white shadow-sm">
                <div class="px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <button class="md:hidden text-gray-600 hover:text-gray-900" id="mobileMenuBtn">
                        <i data-feather="menu" class="h-6 w-6"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-900">Messages</h1>
                    <div class="flex items-center gap-3">
                        <span class="hidden sm:inline text-sm font-medium text-gray-700"><?php echo htmlspecialchars($fullName); ?></span>
                    </div>
                </div>
            </header>

            <!-- Messaging interface -->
            <main class="flex-1 flex overflow-hidden">
                <!-- Conversations list -->
                <div class="w-full md:w-80 lg:w-96 border-r border-gray-200 bg-white flex flex-col">
                    <div class="p-4 border-b border-gray-200 bg-blue-600 text-white">
                        <h2 class="font-semibold">Doctor Conversations</h2>
                    </div>
                    <div id="conversationList" class="flex-1 overflow-y-auto">
                        <div class="px-4 py-8 text-center text-gray-500">
                            Loading conversations...
                        </div>
                    </div>
                </div>

                <!-- Chat area -->
                <div class="flex-1 flex flex-col bg-gray-50 hidden md:flex">
                    <div id="chatArea" class="flex-1 flex flex-col">
                        <div class="flex-1 flex items-center justify-center text-gray-500">
                            <div class="text-center">
                                <i data-feather="message-circle" class="h-16 w-16 mx-auto mb-4 text-gray-300"></i>
                                <p class="text-lg font-medium">Select a conversation to start messaging</p>
                                <p class="text-sm mt-2">Choose a doctor from the list to view or send messages</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active conversation view -->
                <div id="conversationView" class="flex-1 flex flex-col bg-white hidden md:flex">
                    <!-- Conversation header with back button (mobile only) -->
                    <div class="md:hidden border-b border-gray-200 px-4 py-3 bg-blue-600 text-white flex items-center gap-3">
                        <button id="backToConversationsBtn" class="text-white hover:text-blue-100">
                            <i data-feather="arrow-left" class="h-5 w-5"></i>
                        </button>
                        <span id="doctorNameHeader" class="font-semibold">Doctor Name</span>
                    </div>

                    <!-- Messages list -->
                    <div id="messagesList" class="flex-1 overflow-y-auto p-4 bg-gray-50">
                        <div class="text-center py-8 text-gray-500">Loading messages...</div>
                    </div>

                    <!-- Typing indicator -->
                    <div id="typingIndicator" class="px-4 py-2 bg-gray-50 border-t border-gray-200 min-h-7"></div>

                    <!-- Message input -->
                    <div class="border-t border-gray-200 p-4 bg-white">
                        <div class="flex gap-2">
                            <input
                                type="text"
                                id="messageInput"
                                placeholder="Type your message..."
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <button
                                id="sendMessageBtn"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i data-feather="send" class="h-5 w-5"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/mobile-menu.js"></script>
    <script src="../assets/js/messaging.js"></script>
    <script>
        feather.replace();

        document.addEventListener('DOMContentLoaded', function() {
            const messaging = new MessagingModule({
                userId: <?php echo intval($userId); ?>,
                userRole: 'patient',
                conversationListPollInterval: 5000
            });
            messaging.init();

            // Watch for conversation view updates to show/hide appropriately on mobile
            const conversationList = document.getElementById('conversationList');
            conversationList.addEventListener('click', function(e) {
                const item = e.target.closest('[data-conversation-id]');
                if (item && window.innerWidth < 768) {
                    document.getElementById('conversationPanel').style.display = 'none';
                    document.getElementById('chatAreaPanel').style.display = 'none';
                    document.getElementById('conversationView').style.display = 'flex';
                    // Scroll conversation into view
                    setTimeout(() => item.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    }), 100);
                }
            });

            // Handle back button on mobile
            document.getElementById('backToConversationsBtn')?.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    document.getElementById('conversationPanel').style.display = 'flex';
                    document.getElementById('conversationView').style.display = 'none';
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    // Show both panels on desktop
                    document.getElementById('conversationPanel').style.display = '';
                    document.getElementById('chatAreaPanel').style.display = 'flex';
                    document.getElementById('conversationView').style.display = 'flex';
                }
            });
        });

        // Handle mobile menu
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('active');
            document.getElementById('sidebarOverlay').classList.add('active');
        });
    </script>
</body>

</html>