// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const menuButton = document.querySelector('[data-feather="menu"]');
    if (menuButton) {
        menuButton.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('sidebar-collapsed');
                sidebar.classList.toggle('sidebar-expanded');
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    if (sidebar.classList.contains('sidebar-collapsed')) {
                        mainContent.classList.add('ml-20');
                    } else {
                        mainContent.classList.remove('ml-20');
                    }
                }
            }
        });
    }
});
