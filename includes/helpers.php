<?php
/**
 * RTIH Task Tracker - Utility & View Helper Functions
 * 
 * Provides robust HTML escaping for XSS prevention, date formatting,
 * status & priority UI badge rendering, and URL query parameter preservation.
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

if (!defined('RTIH_APP')) {
    define('RTIH_APP', true);
}

/**
 * Escapes strings for safe HTML output (XSS defense).
 *
 * @param mixed $value The string or value to escape
 * @return string HTML-safe escaped string
 */
function e($value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formats a MySQL date/datetime string into a human-readable display string.
 *
 * @param string|null $date Date string (e.g., '2026-11-20' or '2026-11-20 14:30:00')
 * @param string $format Target PHP date format (default: 'M d, Y')
 * @return string
 */
function format_date(?string $date, string $format = 'M d, Y'): string
{
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '<span class="text-muted">None</span>';
    }

    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return e($date);
    }
}

/**
 * Checks whether a task's due date has passed without being completed.
 *
 * @param string|null $dueDate
 * @param string $status
 * @return bool
 */
function is_overdue(?string $dueDate, string $status): bool
{
    if (empty($dueDate) || $status === 'Completed') {
        return false;
    }

    $today = date('Y-m-d');
    return $dueDate < $today;
}

/**
 * Generates a Bootstrap 5.3 badge for task status.
 * Exact assignment enum values: 'Pending', 'In Progress', 'Completed'.
 *
 * @param string $status
 * @return string HTML badge snippet
 */
function status_badge(string $status): string
{
    switch ($status) {
        case 'Completed':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>';
        case 'In Progress':
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-arrow-repeat me-1"></i>In Progress</span>';
        case 'Pending':
        default:
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>Pending</span>';
    }
}

/**
 * Generates a Bootstrap 5.3 badge for task priority.
 * Exact assignment enum values: 'Low', 'Medium', 'High'.
 *
 * @param string $priority
 * @return string HTML badge snippet
 */
function priority_badge(string $priority): string
{
    switch ($priority) {
        case 'High':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>High</span>';
        case 'Medium':
            return '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-dash-circle me-1"></i>Medium</span>';
        case 'Low':
        default:
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-arrow-down-circle me-1"></i>Low</span>';
    }
}

/**
 * Generates a Bootstrap 5.3 badge for user role.
 *
 * @param string $role
 * @return string HTML badge snippet
 */
function role_badge(string $role): string
{
    switch (strtolower($role)) {
        case 'admin':
            return '<span class="badge bg-danger text-white rounded-pill px-2">Admin</span>';
        case 'intern':
            return '<span class="badge bg-info text-dark rounded-pill px-2">RTIH Intern</span>';
        default:
            return '<span class="badge bg-secondary text-white rounded-pill px-2">' . e(ucfirst($role)) . '</span>';
    }
}

/**
 * Builds a clean URL query string preserving current GET parameters (search, status, priority)
 * while overriding specified keys (e.g. ['page' => 2]).
 *
 * This directly satisfies the Pagination filter preservation requirement.
 *
 * @param array $overrides Key-value pairs to override or set
 * @return string Query string starting with '?' or empty string
 */
function build_query_params(array $overrides = []): string
{
    $params = $_GET;

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    $queryString = http_build_query($params);
    return $queryString !== '' ? '?' . $queryString : '';
}
