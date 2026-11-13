<?php
/**
 * RTIH Task Tracker - Delete Task Endpoint
 * 
 * Safely removes a task belonging to the authenticated user:
 * - Requires authentication
 * - Enforces POST method to prevent accidental or malicious GET deletion
 * - Enforces CSRF token validation
 * - Enforces strict ownership check (DELETE ... WHERE id = :id AND user_id = :user_id)
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

if ($taskId <= 0) {
    set_flash('danger', 'Invalid task identifier.');
    header('Location: index.php');
    exit;
}

try {
    // Delete with strict user_id boundary
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id'      => $taskId,
        ':user_id' => $userId,
    ]);

    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Task deleted successfully.');
    } else {
        set_flash('danger', 'Task not found or access denied. You can only delete your own tasks.');
    }
} catch (PDOException $e) {
    error_log("Task deletion error: " . $e->getMessage());
    set_flash('danger', 'Failed to delete task due to a database error.');
}

// Redirect back to dashboard
$redirectUrl = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : 'index.php';
if (strpos($redirectUrl, 'http') === 0 || strpos($redirectUrl, '//') === 0) {
    $redirectUrl = 'index.php';
}

header('Location: ' . $redirectUrl);
exit;
