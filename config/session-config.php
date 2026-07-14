<?php

/**
 * Session Configuration
 * Centralized session settings to prevent premature session expiration
 * Supports role-based session separation for multi-tab authentication
 */

/**
 * Start session with optional role-specific name
 * @param string|null $role Role identifier (admin, doctor, patient) or null for default
 */
function startSession($role = null) {
    // Only configure if session hasn't started yet
    if (session_status() === PHP_SESSION_NONE) {
        // Set session name based on role to allow separate sessions per role
        if ($role) {
            session_name('medicare_' . $role . '_session');
        }
        
        // Set session cookie parameters before starting session
        // Cookie lifetime: 0 = until browser closes, or set to seconds (e.g., 86400 = 24 hours)
        $cookieLifetime = 0; // Session persists until browser is completely closed

        // Get the current cookie parameters
        $cookieParams = session_get_cookie_params();

        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => $cookieLifetime,
            'path' => '/',
            'domain' => $cookieParams['domain'],
            'secure' => false, // Set to true if using HTTPS
            'httponly' => true, // Helps prevent XSS attacks
            'samesite' => 'Lax' // Helps prevent CSRF attacks
        ]);

        // Set session configuration
        ini_set('session.gc_maxlifetime', 86400); // 24 hours in seconds
        ini_set('session.cookie_lifetime', $cookieLifetime);
        ini_set('session.use_strict_mode', 1); // Prevent session fixation
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);

        // Start the session
        session_start();
    }
}

// Note: Session is not auto-started anymore
// Each page must call startSession('role') with appropriate role
