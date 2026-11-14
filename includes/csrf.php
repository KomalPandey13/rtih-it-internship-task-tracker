<?php
/**
 * RTIH Task Tracker - CSRF (Cross-Site Request Forgery) Protection
 * 
 * Provides cryptographic token generation and verification using:
 * - random_bytes(32) for high-entropy tokens
 * - hash_equals() for constant-time comparison to prevent timing attacks
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

if (!defined('RTIH_APP')) {
    define('RTIH_APP', true);
}

require_once __DIR__ . '/session.php';

/**
 * Retrieves the current session CSRF token, or generates a new one.
 *
 * @return string Cryptographically secure 64-character hex token
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Regenerates the CSRF token (recommended after privilege changes or logins).
 *
 * @return string Newly generated CSRF token
 */
function regenerate_csrf_token(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Returns a ready-to-render hidden HTML input containing the CSRF token.
 *
 * @return string HTML <input> element
 */
function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verifies a submitted CSRF token against the session token.
 *
 * @param string|null $token Explicit token string, or null to read from $_POST['csrf_token']
 * @return bool True if valid, false otherwise
 */
function verify_csrf(?string $token = null): bool
{
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    // Timing-attack safe comparison
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Enforces valid CSRF token; terminates request if verification fails.
 *
 * @return void
 */
function validate_csrf_or_abort(): void
{
    if (!verify_csrf()) {
        set_flash('danger', 'Security validation failed (Invalid or expired CSRF token). Please try again.');
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: " . $referer);
        exit;
    }
}

