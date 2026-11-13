<?php
/**
 * RTIH Task Tracker - Authentication & Authorization Guards
 * 
 * Manages user authentication state, session identity guards, and
 * guest/authenticated route redirects.
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

if (!defined('RTIH_APP')) {
    define('RTIH_APP', true);
}

require_once __DIR__ . '/session.php';

/**
 * Checks whether the visitor has an active authenticated session.
 *
 * @return bool
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Returns the currently authenticated user's ID, or 0 if unauthenticated.
 *
 * @return int
 */
function current_user_id(): int
{
    return is_logged_in() ? (int)$_SESSION['user_id'] : 0;
}

/**
 * Returns current authenticated user session data array.
 *
 * @return array{id: int, name: string, email: string, role: string}|null
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => (string)($_SESSION['user_name'] ?? 'User'),
        'email' => (string)($_SESSION['user_email'] ?? ''),
        'role'  => (string)($_SESSION['user_role'] ?? 'intern'),
    ];
}

/**
 * Guard: Enforces that the user MUST be logged in to view the page.
 * If not authenticated, queues a flash message and redirects to login.php.
 *
 * @return void
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to access your task dashboard.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Guard: Enforces that the visitor is a guest (NOT logged in).
 * If already authenticated, redirects them straight to index.php.
 * (UX/Security improvement: prevents logged-in users from seeing redundant login/register forms)
 *
 * @return void
 */
function require_guest(): void
{
    if (is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}
