<?php
// ============================================================
// session_manager.php — Central Session Helper
//
// WHAT THIS FILE DOES:
//   - Starts a secure PHP session (called on every page that needs sessions)
//   - Provides helper functions to save/read booking data from $_SESSION
//   - Provides a helper to sanitize user input (prevents XSS)
//
// HOW TO USE:
//   Just add this at the top of any PHP page:
//     require 'session_manager.php';
//
// WHY PHP SESSIONS?
//   Unlike sessionStorage (JavaScript, lost on tab close / back-nav wipes),
//   PHP $_SESSION lives on the SERVER for the duration of the browser session.
//   The browser just holds a tiny cookie (PHPSESSID) as a key.
//   This means data survives back-button navigation perfectly.
// ============================================================


// ---- Start the session securely ----
// session_start() must be called before any output (HTML, echo, etc.)
// We check session_status() first so we don't start it twice.

if (session_status() === PHP_SESSION_NONE) {

    // Security settings for the session cookie:
    session_set_cookie_params([
        'lifetime' => 0,          // Cookie lasts until browser closes
        'path'     => '/',        // Available on the whole site
        'secure'   => false,      // Set to TRUE on HTTPS (leave false for XAMPP localhost)
        'httponly' => true,       // JS cannot read the session cookie (prevents XSS theft)
        'samesite' => 'Strict'    // Prevents CSRF attacks
    ]);

    session_start();
}


// ============================================================
// sanitize($value)
//
// Cleans user input to prevent:
//   - XSS (Cross-Site Scripting): strips/escapes <script> tags etc.
//   - Basic injection: htmlspecialchars converts < > " ' & to safe HTML entities
//
// Always use this before storing or displaying user-supplied text.
// ============================================================
function sanitize($value) {
    // trim()             → removes leading/trailing whitespace
    // htmlspecialchars() → converts special chars to HTML entities
    // ENT_QUOTES         → converts both single and double quotes
    // 'UTF-8'            → handles international characters
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}


// ============================================================
// saveBookingData($key, $value)
// readBookingData($key, $default)
//
// Thin wrappers around $_SESSION so every file uses the same
// session key names consistently.
// ============================================================
function saveBookingData($key, $value) {
    $_SESSION['booking'][$key] = $value;
}

function readBookingData($key, $default = '') {
    return $_SESSION['booking'][$key] ?? $default;
}


// ============================================================
// clearBookingData()
//
// Called after a successful booking (payment confirmed).
// Wipes only the booking sub-array, not the admin session.
// ============================================================
function clearBookingData() {
    unset($_SESSION['booking']);
}


// ============================================================
// isAdminLoggedIn()
//
// Returns true if an admin has authenticated this session.
// Used to protect the admin dashboard.
// ============================================================
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}
