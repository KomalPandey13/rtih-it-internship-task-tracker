<?php
/**
 * RTIH Task Tracker - Create Task Endpoint
 * 
 * Handles new task creation:
 * - Requires active user authentication
 * - Enforces POST request method and CSRF protection
 * - Validates input parameters (title non-empty, status & priority enums, due_date format)
 * - Inserts task bound to current authenticated user's ID
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

// Collect and sanitize input
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$status      = trim($_POST['status'] ?? 'Pending');
$priority    = trim($_POST['priority'] ?? 'Medium');
$dueDateRaw  = trim($_POST['due_date'] ?? '');

$allowedStatuses   = ['Pending', 'In Progress', 'Completed'];
$allowedPriorities = ['Low', 'Medium', 'High'];

// Validation
$errors = [];

if ($title === '') {
    $errors[] = 'Task title cannot be empty or whitespace.';
} elseif (mb_strlen($title) > 255) {
    $errors[] = 'Task title cannot exceed 255 characters.';
}

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'Pending';
}

if (!in_array($priority, $allowedPriorities, true)) {
    $priority = 'Medium';
}

$dueDate = null;
if ($dueDateRaw !== '') {
    // Validate YYYY-MM-DD
    $d = DateTime::createFromFormat('Y-m-d', $dueDateRaw);
    if ($d && $d->format('Y-m-d') === $dueDateRaw) {
        $dueDate = $dueDateRaw;
    } else {
        $errors[] = 'Invalid due date format. Please use YYYY-MM-DD.';
    }
}

if (!empty($errors)) {
    set_flash('danger', implode(' ', $errors));
    header('Location: index.php');
    exit;
}

// Insert into database
try {
    $stmt = $pdo->prepare("
        INSERT INTO tasks (user_id, title, description, status, priority, due_date, created_at, updated_at)
        VALUES (:user_id, :title, :description, :status, :priority, :due_date, NOW(), NOW())
    ");

    $stmt->execute([
        ':user_id'     => $userId,
        ':title'       => $title,
        ':description' => $description !== '' ? $description : null,
        ':status'      => $status,
        ':priority'    => $priority,
        ':due_date'    => $dueDate,
    ]);

    set_flash('success', 'Task "' . e($title) . '" created successfully!');
} catch (PDOException $e) {
    error_log("Task creation error: " . $e->getMessage());
    set_flash('danger', 'Failed to create task due to a database error.');
}

header('Location: index.php');
exit;

