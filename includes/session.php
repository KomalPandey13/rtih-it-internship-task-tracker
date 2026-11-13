<?php
/**
 * RTIH Task Tracker - Secure Session & Flash Message Management
 * 
 * Configures hardened session parameters:
 * - HttpOnly: prevents client-side JavaScript access to session cookies (XSS protection)
 * - SameSite=Lax: mitigates Cross-Site Request Forgery (CSRF)
 * - Strict Mode: rejects uninitialized session IDs
 * - Flash messaging helper functions for single-request user notifications
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

if (!defined('RTIH_APP')) {
    define('RTIH_APP', true);
}

// Start session securely if not already active
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        // Session cookie parameters
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        
        // PHP 7.3+ cookie options array
        session_set_cookie_params([
            'lifetime' => 0,              // Session cookie expires when browser closes
            'path'     => '/',
            'domain'   => '',             // Default to current host
            'secure'   => $isSecure,      // True only if HTTPS
            'httponly' => true,           // Inaccessible to JavaScript
            'samesite' => 'Lax',          // Mitigates CSRF attacks
        ]);

        // Enforce strict session handling
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_start();
    } else {
        @session_start();
    }
}

/**
 * Sets a flash message to be displayed on the subsequent HTTP request.
 *
 * @param string $type Bootstrap alert class: 'success', 'danger', 'warning', 'info'
 * @param string $message Flash message text
 * @return void
 */
function set_flash(string $type, string $message): void
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => in_array($type, ['success', 'danger', 'warning', 'info']) ? $type : 'info',
        'message' => $message,
    ];
}

/**
 * Checks whether any flash messages are queued.
 *
 * @return bool
 */
function has_flash(): bool
{
    return !empty($_SESSION['flash_messages']);
}

/**
 * Retrieves and clears all queued flash messages (one-time read).
 *
 * @return array List of flash messages: [['type' => '...', 'message' => '...'], ...]
 */
function get_flash(): array
{
    if (!isset($_SESSION['flash_messages'])) {
        return [];
    }

    $messages = $_SESSION['flash_messages'];
    unset($_SESSION['flash_messages']);
    return $messages;
}
