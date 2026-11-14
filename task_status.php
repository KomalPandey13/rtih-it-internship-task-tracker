<?php
/**
 * RTIH Task Tracker - Inline Quick Status Update Endpoint
 * 
 * Provides fast, single-click task status transitioning from the dashboard:
 * - Requires authentication
 * - Enforces POST method and CSRF token check
 * - Validates target status against allowed enums ('Pending', 'In Progress', 'Completed')
 * - Enforces strict ownership check (WHERE id = :id AND user_id = :user_id)
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

define('RTIH_APP', true);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Authentication guard
require_login();

// Method guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: index.php');
    exit;
}

// CSRF guard
validate_csrf_or_abort();

$userId = current_user_id();
$taskId = (int)($_POST['task_id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$allowedStatuses = ['Pending', 'In Progress', 'Completed'];

if ($taskId <= 0 || !in_array($status, $allowedStatuses, true)) {
    set_flash('danger', 'Invalid task or status value.');
    header('Location: index.php');
    exit;
}

try {
    // Strictly update only if the task belongs to the current user
    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET status = :status, updated_at = NOW() 
        WHERE id = :id AND user_id = :user_id
    ");

    $stmt->execute([
        ':status'  => $status,
        ':id'      => $taskId,
        ':user_id' => $userId,
    ]);

    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Task status updated to "' . e($status) . '".');
    } else {
        // Either status did not change, or user does not own this task
        // Verify ownership to give precise feedback
        $checkStmt = $pdo->prepare("SELECT status FROM tasks WHERE id = :id AND user_id = :user_id");
        $checkStmt->execute([':id' => $taskId, ':user_id' => $userId]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            set_flash('info', 'Task was already set to "' . e($status) . '".');
        } else {
            set_flash('danger', 'Task not found or access denied.');
        }
    }
} catch (PDOException $e) {
    error_log("Quick status update error: " . $e->getMessage());
    set_flash('danger', 'Database error while updating task status.');
}

// Redirect back to dashboard preserving filters if available
$redirectUrl = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : 'index.php';
// Prevent open redirect by ensuring relative URL
if (strpos($redirectUrl, 'http') === 0 || strpos($redirectUrl, '//') === 0) {
    $redirectUrl = 'index.php';
}

header('Location: ' . $redirectUrl);
exit;

