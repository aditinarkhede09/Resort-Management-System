<?php
// ============================================================
// logout.php — Admin Session Logout
//
// Called when admin wants to log out.
// Destroys the PHP session and redirects to login page.
//
// HOW TO USE:
//   Link to this page: <a href="logout.php">Logout</a>
//   Or redirect via JS: window.location.href = 'logout.php';
// ============================================================

require 'session_manager.php';  // starts session

// ---- Destroy the session ----
// 1. Clear all session variables
$_SESSION = [];

// 2. Delete the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// 3. Destroy the session on the server
session_destroy();

// ---- Redirect to login page ----
header('Location: login.html');
exit;
