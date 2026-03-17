// Mobile menu toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const menuBtn = document.getElementById('menuBtn');

    function isMobileView() {
        return window.matchMedia('(max-width: 768px)').matches;
    }

    function openSidebar() {
        if (sidebar && overlay && isMobileView()) {
            sidebar.classList.add('mobile-open');
            overlay.classList.add('active');
        }
    }

    function closeSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }
    }

    function toggleSidebar() {
        if (sidebar && overlay && isMobileView()) {
            if (sidebar.classList.contains('mobile-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', toggleSidebar);
    }

    if (menuBtn) {
        menuBtn.addEventListener('click', toggleSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    if (sidebar) {
        const sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (isMobileView()) {
                    closeSidebar();
                }
            });
        });
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    window.addEventListener('resize', function() {
        if (!isMobileView()) {
            closeSidebar();
        }
    });

    // Ensure overlay is reset on initial desktop load.
    if (!isMobileView()) {
        closeSidebar();
    }

    // Re-render feather icons after DOM changes
    if (typeof feather !== 'undefined') {
        setTimeout(() => feather.replace(), 100);
    }
});
