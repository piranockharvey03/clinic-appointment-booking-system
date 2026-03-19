<?php
require_once '../../config/session-config.php';

// Keep this page admin-only to avoid exposing internal test tooling.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../public/admin-login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Dev Test | MediCare Clinic</title>
    <link rel="icon" type="image/svg+xml" href="../../public/assets/images/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Notification Dropdown Dev Test</h1>
                    <p class="text-sm text-gray-600 mt-1">Uses mocked fetch responses to test UI behavior end-to-end.</p>
                </div>

                <div class="relative">
                    <button id="adminNotificationBtn" class="relative p-2 rounded-md text-gray-700 hover:bg-gray-100" type="button" aria-label="Notifications">
                        <i data-feather="bell" class="h-6 w-6"></i>
                        <span id="adminNotificationBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                    </button>
                    <div id="adminNotificationDropdown" class="hidden absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-sm sm:w-80 bg-white rounded-md shadow-lg overflow-hidden z-50 border border-gray-200">
                        <div class="py-2">
                            <div class="px-4 py-2 text-sm font-medium text-gray-700 border-b border-gray-200 flex justify-between items-center">
                                <span>Notifications</span>
                                <button id="adminMarkAllReadBtn" class="text-xs bg-blue-500 hover:bg-blue-700 px-2 py-1 rounded text-white" style="display: none;" type="button">Mark all read</button>
                            </div>
                            <div id="adminNotificationList" class="max-h-96 overflow-y-auto">
                                <div class="px-4 py-3 text-center text-gray-500">Loading notifications...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid sm:grid-cols-2 gap-3">
                <button id="addMockNotificationBtn" class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700" type="button">Add mock notification</button>
                <button id="clearMockNotificationsBtn" class="px-4 py-2 rounded-md bg-gray-700 text-white hover:bg-gray-800" type="button">Clear all mock notifications</button>
            </div>

            <div class="mt-4 rounded-md bg-gray-50 border border-gray-200 p-4">
                <p class="text-sm text-gray-700 font-medium">What to verify</p>
                <ul class="text-sm text-gray-600 mt-2 list-disc pl-5 space-y-1">
                    <li>Badge count updates when mock notifications are added</li>
                    <li>Badge pulse triggers when unread count increases</li>
                    <li>Single "mark as read" removes only one item</li>
                    <li>"Mark all read" clears all items and hides badge</li>
                </ul>
            </div>

            <div class="mt-4 text-xs text-gray-500">
                <p>Mock fetch endpoints: <span class="font-mono">/mock/get-notifications</span>, <span class="font-mono">/mock/mark-notifications-read</span></p>
            </div>
        </div>
    </div>

    <script src="../assets/js/notification-dropdown.js"></script>
    <script>
        feather.replace();

        // In-memory mock store for quick UI verification.
        let mockNotifications = [{
                id: 101,
                type: 'new_appointment',
                message: 'New appointment booked with Dr. Carter on 2026-03-19 at 10:00 AM',
                time: 'Mar 19, 2026 9:05 AM'
            },
            {
                id: 102,
                type: 'rescheduled',
                message: 'Appointment for John Doe was rescheduled to 3:30 PM',
                time: 'Mar 19, 2026 9:12 AM'
            }
        ];

        let nextId = 103;

        function jsonResponse(payload) {
            return Promise.resolve({
                ok: true,
                json: () => Promise.resolve(payload)
            });
        }

        const realFetch = window.fetch.bind(window);
        window.fetch = function(url, options) {
            const method = (options && options.method ? options.method : 'GET').toUpperCase();

            if (url === '/mock/get-notifications' && method === 'GET') {
                return jsonResponse({
                    success: true,
                    notifications: mockNotifications
                });
            }

            if (url === '/mock/mark-notifications-read' && method === 'POST') {
                const body = options && options.body ? String(options.body) : '';
                const params = new URLSearchParams(body);
                const ids = params.getAll('notification_ids[]').map((id) => Number(id)).filter((id) => Number.isInteger(id) && id > 0);
                const idSet = new Set(ids);
                mockNotifications = mockNotifications.filter((n) => !idSet.has(Number(n.id)));

                return jsonResponse({
                    success: true,
                    marked_count: ids.length,
                    unread_count: mockNotifications.length
                });
            }

            return realFetch(url, options);
        };

        const testDropdown = NotificationDropdown.init({
            buttonSelector: '#adminNotificationBtn',
            dropdownSelector: '#adminNotificationDropdown',
            badgeSelector: '#adminNotificationBadge',
            listSelector: '#adminNotificationList',
            markAllSelector: '#adminMarkAllReadBtn',
            markReadButtonClass: 'admin-mark-read-btn',
            fetchUrl: '/mock/get-notifications',
            markReadUrl: '/mock/mark-notifications-read',
            pollIntervalMs: 5000,
            emptyText: 'No new notifications',
            iconMap: {
                new_appointment: '<i data-feather="calendar" class="h-4 w-4 text-blue-500"></i>',
                approved: '<i data-feather="check-circle" class="h-4 w-4 text-green-500"></i>',
                canceled: '<i data-feather="x-circle" class="h-4 w-4 text-red-500"></i>',
                rescheduled: '<i data-feather="refresh-cw" class="h-4 w-4 text-blue-500"></i>',
                completed: '<i data-feather="check-square" class="h-4 w-4 text-indigo-500"></i>'
            }
        });

        document.getElementById('addMockNotificationBtn').addEventListener('click', function() {
            const types = ['new_appointment', 'approved', 'canceled', 'rescheduled', 'completed'];
            const type = types[Math.floor(Math.random() * types.length)];
            mockNotifications.unshift({
                id: nextId++,
                type: type,
                message: 'Mock ' + type.replace('_', ' ') + ' notification #' + (nextId - 1),
                time: new Date().toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                })
            });

            testDropdown.refresh();
        });

        document.getElementById('clearMockNotificationsBtn').addEventListener('click', function() {
            mockNotifications = [];
            testDropdown.refresh();
        });
    </script>
</body>

</html>