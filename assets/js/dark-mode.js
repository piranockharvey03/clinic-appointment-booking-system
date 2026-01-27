// Dark Mode Global Script
// This script applies dark mode across all pages based on localStorage

(function() {
    'use strict';

    // Apply dark mode immediately to prevent flash
    function applyDarkMode() {
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }
    }

    // Apply dark mode as early as possible
    if (document.body) {
        applyDarkMode();
    } else {
        document.addEventListener('DOMContentLoaded', applyDarkMode);
    }

    // Function to enable dark mode
    window.enableDarkMode = function() {
        document.body.classList.add('dark-mode');
        localStorage.setItem('darkMode', 'enabled');
        
        // Update toggle if it exists
        const toggle = document.getElementById('darkModeToggle');
        const status = document.getElementById('darkModeStatus');
        if (toggle) toggle.classList.add('active');
        if (status) status.textContent = 'On';
    };

    // Function to disable dark mode
    window.disableDarkMode = function() {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('darkMode', 'disabled');
        
        // Update toggle if it exists
        const toggle = document.getElementById('darkModeToggle');
        const status = document.getElementById('darkModeStatus');
        if (toggle) toggle.classList.remove('active');
        if (status) status.textContent = 'Off';
    };

    // Function to toggle dark mode
    window.toggleDarkMode = function() {
        if (document.body.classList.contains('dark-mode')) {
            disableDarkMode();
        } else {
            enableDarkMode();
        }
    };

    // Initialize dark mode toggle on settings page
    document.addEventListener('DOMContentLoaded', function() {
        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkModeStatus = document.getElementById('darkModeStatus');

        // Set initial state
        if (localStorage.getItem('darkMode') === 'enabled') {
            if (darkModeToggle) darkModeToggle.classList.add('active');
            if (darkModeStatus) darkModeStatus.textContent = 'On';
        }

        // Add click listener
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', toggleDarkMode);
        }
    });
})();
