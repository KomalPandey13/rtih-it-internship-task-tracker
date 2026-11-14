<?php
/**
 * RTIH Task Tracker - User Logout Endpoint
 * 
 * Secure POST-only logout handler:
 * - Enforces CSRF token check to prevent forced-logout CSRF attacks
 * - Clears session superglobal array
 * - Invalidates and removes the session cookie
 * - Destroys session server-side
 * - Starts a fresh session to deliver a friendly logout flash notification
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

define('RTIH_APP', true);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';

// Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Location: index.php');
    exit;
}

// Enforce CSRF verification
if (!verify_csrf()) {
    set_flash('danger', 'Invalid security token during logout. Please try again.');
    header('Location: index.php');
    exit;
}

// 1. Unset all session values
$_SESSION = [];

// 2. Destroy the session cookie in browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destroy session data on server
session_destroy();

// 4. Start a clean session exclusively to carry the logout flash message
session_start();
$_SESSION['flash_messages'][] = [
    'type'    => 'info',
    'message' => 'You have been safely logged out. See you soon!',
];

header('Location: login.php');
exit;

