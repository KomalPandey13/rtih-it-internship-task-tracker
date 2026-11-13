<?php
/**
 * RTIH Task Tracker - Edit Task Page & Endpoint
 * 
 * Handles task viewing and editing with strict multi-tenant authorization:
 * - Requires authentication
 * - GET: Loads existing task data ONLY IF the task belongs to current user (WHERE id = :id AND user_id = :user_id)
 * - If user attempts to edit another user's task via URL tampering (?id=X), immediately rejects with 403/flash message
 * - POST: Validates CSRF, sanitizes input, and updates strictly within user's ownership boundary
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

$userId = current_user_id();
$taskId = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

if ($taskId <= 0) {
    set_flash('danger', 'Invalid task identifier.');
    header('Location: index.php');
    exit;
}

// Fetch task with strict user_id constraint
try {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->execute([
        ':id'      => $taskId,
        ':user_id' => $userId,
    ]);
    $task = $stmt->fetch();

    if (!$task) {
        // Access denied or does not exist
        set_flash('danger', 'Task not found or access denied. You can only view and edit your own tasks.');
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Task fetch error: " . $e->getMessage());
    set_flash('danger', 'Database error while loading task.');
    header('Location: index.php');
    exit;
}

$errors = [];
$title       = $task['title'];
$description = $task['description'] ?? '';
$status      = $task['status'];
$priority    = $task['priority'];
$dueDate     = $task['due_date'] ?? '';

// Handle POST Update Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    validate_csrf_or_abort();

    // 2. Collect inputs
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status      = trim($_POST['status'] ?? 'Pending');
    $priority    = trim($_POST['priority'] ?? 'Medium');
    $dueDateRaw  = trim($_POST['due_date'] ?? '');

    $allowedStatuses   = ['Pending', 'In Progress', 'Completed'];
    $allowedPriorities = ['Low', 'Medium', 'High'];

    // 3. Validation
    if ($title === '') {
        $errors['title'] = 'Task title cannot be empty.';
    } elseif (mb_strlen($title) > 255) {
        $errors['title'] = 'Task title cannot exceed 255 characters.';
    }

    if (!in_array($status, $allowedStatuses, true)) {
        $errors['status'] = 'Invalid status selected.';
    }

    if (!in_array($priority, $allowedPriorities, true)) {
        $errors['priority'] = 'Invalid priority selected.';
    }

    $formattedDueDate = null;
    if ($dueDateRaw !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $dueDateRaw);
        if ($d && $d->format('Y-m-d') === $dueDateRaw) {
            $formattedDueDate = $dueDateRaw;
        } else {
            $errors['due_date'] = 'Invalid due date format. Please use YYYY-MM-DD.';
        }
    }

    // 4. Update if validation passes
    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE tasks 
                SET title = :title,
                    description = :description,
                    status = :status,
                    priority = :priority,
                    due_date = :due_date,
                    updated_at = NOW()
                WHERE id = :id AND user_id = :user_id
            ");

            $updateStmt->execute([
                ':title'       => $title,
                ':description' => $description !== '' ? $description : null,
                ':status'      => $status,
                ':priority'    => $priority,
                ':due_date'    => $formattedDueDate,
                ':id'          => $taskId,
                ':user_id'     => $userId,
            ]);

            set_flash('success', 'Task "' . e($title) . '" updated successfully!');
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            error_log("Task update error: " . $e->getMessage());
            $errors['general'] = 'Failed to update task due to a database error.';
        }
    }
}

$pageTitle = 'Edit Task';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="bi bi-pencil-square me-2"></i>Edit Task #<?= (int)$taskId ?>
                </h5>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= e($errors['general']) ?></div>
                    </div>
                <?php endif; ?>

                <form action="task_edit.php?id=<?= (int)$taskId ?>" method="POST" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$taskId ?>">

                    <!-- Task Title -->
                    <div class="mb-3">
                        <label for="title" class="form-label fw-medium">Task Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" 
                               id="title" 
                               name="title" 
                               value="<?= e($title) ?>" 
                               placeholder="e.g. Implement user authentication tests" 
                               maxlength="255"
                               required 
                               autofocus>
                        <?php if (isset($errors['title'])): ?>
                            <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-medium">Description</label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  placeholder="Provide relevant task details, acceptance criteria, or links..."><?= e($description) ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <!-- Status -->
                        <div class="col-md-4">
                            <label for="status" class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status" required>
                                <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback"><?= e($errors['status']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Priority -->
                        <div class="col-md-4">
                            <label for="priority" class="form-label fw-medium">Priority <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors['priority']) ? 'is-invalid' : '' ?>" id="priority" name="priority" required>
                                <option value="Low" <?= $priority === 'Low' ? 'selected' : '' ?>>Low</option>
                                <option value="Medium" <?= $priority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="High" <?= $priority === 'High' ? 'selected' : '' ?>>High</option>
                            </select>
                            <?php if (isset($errors['priority'])): ?>
                                <div class="invalid-feedback"><?= e($errors['priority']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Due Date -->
                        <div class="col-md-4">
                            <label for="due_date" class="form-label fw-medium">Due Date</label>
                            <input type="date" 
                                   class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" 
                                   id="due_date" 
                                   name="due_date" 
                                   value="<?= e($dueDate) ?>">
                            <?php if (isset($errors['due_date'])): ?>
                                <div class="invalid-feedback"><?= e($errors['due_date']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Meta Information Box -->
                    <div class="bg-light p-3 rounded mb-4 text-muted small d-flex flex-wrap justify-content-between gap-2">
                        <span><i class="bi bi-calendar-plus me-1"></i> Created: <?= format_date($task['created_at'], 'M d, Y h:i A') ?></span>
                        <span><i class="bi bi-clock-history me-1"></i> Last Updated: <?= format_date($task['updated_at'], 'M d, Y h:i A') ?></span>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="index.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
