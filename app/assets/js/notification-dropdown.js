(function(window, document) {
    'use strict';

    function toArray(value) {
        if (!value) return [];
        return Array.isArray(value) ? value : [value];
    }

    function defaultIcon(type) {
        return '<i data-feather="info" class="h-4 w-4 text-gray-500"></i>';
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function ensurePulseStyles() {
        if (document.getElementById('notification-dropdown-pulse-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'notification-dropdown-pulse-style';
        style.textContent = [
            '@keyframes notificationBadgePulse {',
            '  0% { transform: scale(1); }',
            '  40% { transform: scale(1.22); }',
            '  100% { transform: scale(1); }',
            '}',
            '.notification-badge-pulse {',
            '  animation: notificationBadgePulse 0.35s ease-out;',
            '}'
        ].join('\n');
        document.head.appendChild(style);
    }

    function renderErrorState(container, message) {
        if (!container) return;
        container.innerHTML = '<div class="px-4 py-3 text-center text-red-600">' + escapeHtml(message || 'Failed to load notifications') + '</div>';
    }

    function init(config) {
        const cfg = Object.assign({
            pollIntervalMs: 30000,
            emptyText: 'No new notifications',
            iconMap: {}
        }, config || {});

        const notificationBtn = document.querySelector(cfg.buttonSelector);
        const notificationDropdown = document.querySelector(cfg.dropdownSelector);
        const notificationBadge = document.querySelector(cfg.badgeSelector);
        const notificationList = document.querySelector(cfg.listSelector);
        const markAllReadBtn = document.querySelector(cfg.markAllSelector);

        if (!notificationBtn || !notificationDropdown || !notificationBadge || !notificationList || !markAllReadBtn) {
            return null;
        }

        const markReadButtonClass = cfg.markReadButtonClass || 'notification-mark-read-btn';
        let previousUnreadCount = null;

        function pulseBadgeIfNew(unreadCount) {
            if (previousUnreadCount !== null && unreadCount > previousUnreadCount) {
                ensurePulseStyles();
                notificationBadge.classList.remove('notification-badge-pulse');
                void notificationBadge.offsetWidth;
                notificationBadge.classList.add('notification-badge-pulse');
            }
            previousUnreadCount = unreadCount;
        }

        function renderIcon(type) {
            if (cfg.iconMap && cfg.iconMap[type]) {
                return cfg.iconMap[type];
            }
            return defaultIcon(type);
        }

        function renderNotifications(notifications) {
            const items = toArray(notifications);

            if (!items.length) {
                notificationList.innerHTML = '<div class="px-4 py-3 text-center text-gray-500">' + cfg.emptyText + '</div>';
                notificationBadge.classList.add('hidden');
                markAllReadBtn.style.display = 'none';
                pulseBadgeIfNew(0);
                if (window.feather && typeof window.feather.replace === 'function') {
                    window.feather.replace();
                }
                return;
            }

            notificationBadge.textContent = String(items.length);
            notificationBadge.classList.remove('hidden');
            markAllReadBtn.style.display = 'inline-block';
            pulseBadgeIfNew(items.length);

            notificationList.innerHTML = items.map(function(notification) {
                const id = Number(notification.id) || 0;
                const message = escapeHtml(notification.message || '');
                const time = escapeHtml(notification.time || '');
                const type = notification.type || '';

                return [
                    '<div class="border-b border-gray-200" data-notification-id="' + id + '">',
                    '    <div class="px-4 py-3 hover:bg-gray-50">',
                    '        <div class="flex items-start justify-between">',
                    '            <div class="flex items-start flex-1">',
                    '                <div class="flex-shrink-0 mt-0.5">',
                                     renderIcon(type),
                    '                </div>',
                    '                <div class="ml-3 flex-1">',
                    '                    <p class="text-sm font-medium text-gray-900">' + message + '</p>',
                    '                    <p class="text-xs text-gray-500 mt-1">' + time + '</p>',
                    '                </div>',
                    '            </div>',
                    '            <button class="' + markReadButtonClass + ' ml-2 p-1 text-gray-400 hover:text-green-600 focus:outline-none" data-notification-id="' + id + '" title="Mark as read" type="button">',
                    '                <i data-feather="check" class="h-4 w-4"></i>',
                    '            </button>',
                    '        </div>',
                    '    </div>',
                    '</div>'
                ].join('\n');
            }).join('');

            if (window.feather && typeof window.feather.replace === 'function') {
                window.feather.replace();
            }
        }

        async function fetchNotifications() {
            try {
                const response = await fetch(cfg.fetchUrl, {
                    method: 'GET',
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    renderErrorState(notificationList, 'Unable to fetch notifications');
                    return;
                }

                let data = null;
                try {
                    data = await response.json();
                } catch (parseError) {
                    renderErrorState(notificationList, 'Unexpected notification response');
                    return;
                }

                if (data && data.success) {
                    renderNotifications(data.notifications || []);
                } else {
                    renderErrorState(notificationList, (data && data.error) ? data.error : 'Failed to load notifications');
                }
            } catch (error) {
                console.error('Failed to fetch notifications', error);
                renderErrorState(notificationList, 'Unable to fetch notifications');
            }
        }

        async function markNotificationsAsRead(ids) {
            const notificationIds = toArray(ids)
                .map(function(id) {
                    return Number(id);
                })
                .filter(function(id) {
                    return Number.isInteger(id) && id > 0;
                });

            if (!notificationIds.length) {
                return;
            }

            const body = new URLSearchParams();
            notificationIds.forEach(function(id) {
                body.append('notification_ids[]', String(id));
            });

            try {
                const response = await fetch(cfg.markReadUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: body.toString()
                });

                if (!response.ok) {
                    renderErrorState(notificationList, 'Unable to mark notifications as read');
                    return;
                }

                let data = null;
                try {
                    data = await response.json();
                } catch (parseError) {
                    renderErrorState(notificationList, 'Unexpected update response');
                    return;
                }

                if (data && data.success) {
                    fetchNotifications();
                } else {
                    renderErrorState(notificationList, (data && data.error) ? data.error : 'Unable to mark notifications as read');
                }
            } catch (error) {
                console.error('Failed to mark notifications as read', error);
                renderErrorState(notificationList, 'Unable to mark notifications as read');
            }
        }

        notificationBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            notificationDropdown.classList.toggle('hidden');

            if (!notificationDropdown.classList.contains('hidden')) {
                fetchNotifications();
            }
        });

        document.addEventListener('click', function(event) {
            const target = event.target;
            if (!target.closest(cfg.buttonSelector + ', ' + cfg.dropdownSelector)) {
                notificationDropdown.classList.add('hidden');
            }
        });

        notificationList.addEventListener('click', function(event) {
            const button = event.target.closest('.' + markReadButtonClass);
            if (!button) return;

            const notificationId = Number(button.getAttribute('data-notification-id'));
            if (Number.isInteger(notificationId) && notificationId > 0) {
                markNotificationsAsRead([notificationId]);
            }
        });

        markAllReadBtn.addEventListener('click', function() {
            const ids = Array.from(notificationList.querySelectorAll('.' + markReadButtonClass))
                .map(function(btn) {
                    return Number(btn.getAttribute('data-notification-id'));
                })
                .filter(function(id) {
                    return Number.isInteger(id) && id > 0;
                });

            markNotificationsAsRead(ids);
        });

        fetchNotifications();
        const intervalId = window.setInterval(fetchNotifications, Number(cfg.pollIntervalMs) || 30000);

        return {
            refresh: fetchNotifications,
            destroy: function() {
                if (intervalId) {
                    window.clearInterval(intervalId);
                }
            }
        };
    }

    window.NotificationDropdown = {
        init: init
    };
})(window, document);
