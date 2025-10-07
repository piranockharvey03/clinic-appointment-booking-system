// Initialize common libraries
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS (Animate On Scroll) if available
    if (typeof AOS !== 'undefined') {
        AOS.init();
    }
    
    // Initialize Feather Icons if available
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
