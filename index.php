<?php
/**
 * RTIH Task Tracker - Main Dashboard (Protected)
 * 
 * Features:
 * - Level 1: Full CRUD task management
 * - Level 2: Search by title, status filtering, priority filtering, and safe input validation
 * - Level 3: Strict user-boundary authorization (users only see and modify their own tasks)
 * - Optional Bonuses:
 *   - Top summary metric cards (Total, Pending, In Progress, Completed)
 *   - Task Priority (Low, Medium, High) & Due Date with overdue highlighting
 *   - Clean 5-per-page pagination preserving active search & filter query strings
 *   - Inline quick-status switching without leaving the dashboard
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

// Enforce login
require_login();

$userId   = current_user_id();
$user     = current_user();
$perPage  = 5; // Default 5 items per page (Bonus requirement)

// 1. Fetch Summary Metric Counts for Logged-In User
$metrics = [
    'total'       => 0,
    'pending'     => 0,
    'in_progress' => 0,
    'completed'   => 0,
];

try {
    $metricStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
        FROM tasks 
        WHERE user_id = :user_id
    ");
    $metricStmt->execute([':user_id' => $userId]);
    $counts = $metricStmt->fetch();

    if ($counts) {
        $metrics['total']       = (int)($counts['total'] ?? 0);
        $metrics['pending']     = (int)($counts['pending'] ?? 0);
        $metrics['in_progress'] = (int)($counts['in_progress'] ?? 0);
        $metrics['completed']   = (int)($counts['completed'] ?? 0);
    }
} catch (PDOException $e) {
    error_log("Metrics calculation error: " . $e->getMessage());
}

// 2. Process Search & Filter Parameters
$search   = trim($_GET['search'] ?? '');
$status   = trim($_GET['status'] ?? '');
$priority = trim($_GET['priority'] ?? '');

$allowedStatuses   = ['Pending', 'In Progress', 'Completed'];
$allowedPriorities = ['Low', 'Medium', 'High'];

// Build WHERE clauses strictly scoped to current user
$whereClauses = ['user_id = :user_id'];
$queryParams  = [':user_id' => $userId];

if ($search !== '') {
    $whereClauses[] = 'title LIKE :search';
    $queryParams[':search'] = '%' . $search . '%';
}

if ($status !== '' && in_array($status, $allowedStatuses, true)) {
    $whereClauses[] = 'status = :status';
    $queryParams[':status'] = $status;
}

if ($priority !== '' && in_array($priority, $allowedPriorities, true)) {
    $whereClauses[] = 'priority = :priority';
    $queryParams[':priority'] = $priority;
}

$whereSql = implode(' AND ', $whereClauses);

// 3. Count Total Matching Tasks for Pagination
$totalFilteredTasks = 0;
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE {$whereSql}");
    $countStmt->execute($queryParams);
    $totalFilteredTasks = (int)$countStmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Task count error: " . $e->getMessage());
}

// Pagination computations
$totalPages = max(1, (int)ceil($totalFilteredTasks / $perPage));
$page       = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset     = ($page - 1) * $perPage;

// 4. Fetch Paginated Tasks for Current User
$tasks = [];
try {
    // Note: LIMIT and OFFSET are bound as explicit integers to ensure maximum PDO driver compatibility
    $sql = "
        SELECT id, user_id, title, description, status, priority, due_date, created_at, updated_at
        FROM tasks
        WHERE {$whereSql}
        ORDER BY 
            CASE 
                WHEN status = 'In Progress' THEN 1
                WHEN status = 'Pending' THEN 2
                WHEN status = 'Completed' THEN 3
            END,
            CASE WHEN due_date IS NULL THEN 1 ELSE 0 END,
            due_date ASC,
            created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    
    // Bind where parameters
    foreach ($queryParams as $param => $val) {
        $stmt->bindValue($param, $val, PDO::PARAM_STR);
    }
    // Bind limit & offset as integers
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Task query error: " . $e->getMessage());
}

$currentQuery = $_SERVER['QUERY_STRING'] ? 'index.php?' . $_SERVER['QUERY_STRING'] : 'index.php';
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header / Welcome Banner -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">
            <i class="bi bi-speedometer2 text-primary me-2"></i>My Task Dashboard
        </h2>
        <p class="text-muted mb-0">
            Welcome, <strong><?= e($user['name']) ?></strong>. Monitor and track your assigned RTIH project tasks.
        </p>
    </div>
    <div>
        <!-- Trigger Modal for Creating a New Task -->
        <button type="button" class="btn btn-primary fw-semibold shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus-circle-fill me-1"></i> Add New Task
        </button>
    </div>
</div>

<!-- Summary Metric Cards (Optional Bonus) -->
<div class="row g-3 mb-4">
    <!-- Total Tasks -->
    <div class="col-6 col-lg-3">
        <div class="card metric-card shadow-sm border-0 border-start border-4 border-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Tasks</div>
                        <div class="fs-2 fw-bold text-dark"><?= $metrics['total'] ?></div>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="bi bi-kanban fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Tasks -->
    <div class="col-6 col-lg-3">
        <div class="card metric-card shadow-sm border-0 border-start border-4 border-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Pending</div>
                        <div class="fs-2 fw-bold text-warning-emphasis"><?= $metrics['pending'] ?></div>
                    </div>
                    <div class="bg-warning-subtle text-warning-emphasis p-3 rounded-circle">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- In Progress Tasks -->
    <div class="col-6 col-lg-3">
        <div class="card metric-card shadow-sm border-0 border-start border-4 border-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">In Progress</div>
                        <div class="fs-2 fw-bold text-primary"><?= $metrics['in_progress'] ?></div>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="bi bi-arrow-repeat fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Tasks -->
    <div class="col-6 col-lg-3">
        <div class="card metric-card shadow-sm border-0 border-start border-4 border-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Completed</div>
                        <div class="fs-2 fw-bold text-success"><?= $metrics['completed'] ?></div>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form action="index.php" method="GET" class="row g-2 align-items-center">
            <!-- Search Title -->
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           placeholder="Search tasks by title..." 
                           value="<?= e($search) ?>">
                </div>
            </div>

            <!-- Status Filter -->
            <div class="col-sm-6 col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <!-- Priority Filter -->
            <div class="col-sm-6 col-md-2">
                <select class="form-select" name="priority">
                    <option value="">All Priorities</option>
                    <option value="Low" <?= $priority === 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= $priority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High" <?= $priority === 'High' ? 'selected' : '' ?>>High</option>
                </select>
            </div>

            <!-- Submit and Reset Buttons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <?php if ($search !== '' || $status !== '' || $priority !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary" title="Clear Filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Task List View -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <div class="fw-bold text-secondary">
            <i class="bi bi-list-task me-1"></i> Tasks 
            <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1">
                <?= $totalFilteredTasks ?> result<?= $totalFilteredTasks !== 1 ? 's' : '' ?>
            </span>
        </div>
        <div class="small text-muted">
            Page <?= $page ?> of <?= $totalPages ?> (5 per page)
        </div>
    </div>

    <?php if (empty($tasks)): ?>
        <div class="card-body text-center py-5">
            <div class="text-muted mb-3">
                <i class="bi bi-clipboard-x fs-1"></i>
            </div>
            <?php if ($search !== '' || $status !== '' || $priority !== ''): ?>
                <h5 class="fw-bold">No tasks matched your criteria</h5>
                <p class="text-muted small mb-3">Try adjusting your keyword search or clearing your active filters.</p>
                <a href="index.php" class="btn btn-outline-primary btn-sm">Reset All Filters</a>
            <?php else: ?>
                <h5 class="fw-bold">No tasks found</h5>
                <p class="text-muted small mb-3">You don't have any tasks in your tracker yet. Start by creating one!</p>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                    <i class="bi bi-plus-circle me-1"></i> Create First Task
                </button>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Task Title &amp; Description</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 110px;">Priority</th>
                        <th style="width: 130px;">Due Date</th>
                        <th style="width: 200px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <?php 
                            $overdue = is_overdue($task['due_date'], $task['status']); 
                        ?>
                        <tr class="<?= $overdue ? 'table-danger-subtle' : '' ?>">
                            <td class="text-muted small"><?= (int)$task['id'] ?></td>
                            <td>
                                <div class="fw-bold text-dark mb-0">
                                    <a href="task_edit.php?id=<?= (int)$task['id'] ?>" class="text-dark text-decoration-none">
                                        <?= e($task['title']) ?>
                                    </a>
                                </div>
                                <?php if (!empty($task['description'])): ?>
                                    <div class="text-muted small text-truncate" style="max-width: 380px;">
                                        <?= e($task['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Inline Quick Status Switcher Form -->
                                <form action="task_status.php" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                    <input type="hidden" name="redirect_to" value="<?= e($currentQuery) ?>">
                                    <select name="status" 
                                            class="form-select form-select-sm border-0 shadow-none fw-semibold p-0 bg-transparent" 
                                            onchange="this.form.submit()" 
                                            title="Click to quickly switch status">
                                        <option value="Pending" <?= $task['status'] === 'Pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                        <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>⚡ In Progress</option>
                                        <option value="Completed" <?= $task['status'] === 'Completed' ? 'selected' : '' ?>>✅ Completed</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <?= priority_badge($task['priority']) ?>
                            </td>
                            <td>
                                <div class="small">
                                    <?= format_date($task['due_date']) ?>
                                    <?php if ($overdue): ?>
                                        <span class="badge bg-danger ms-1" title="This task is overdue!">Overdue</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- Edit Button -->
                                    <a href="task_edit.php?id=<?= (int)$task['id'] ?>" class="btn btn-outline-secondary" title="Edit Task">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- Delete Button (POST-only with JS confirmation) -->
                                    <form action="task_delete.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this task?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                        <input type="hidden" name="redirect_to" value="<?= e($currentQuery) ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Delete Task">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Filter-Preserving Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">
                    Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalFilteredTasks) ?> of <?= $totalFilteredTasks ?> tasks
                </span>

                <nav aria-label="Task pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- First Page -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= build_query_params(['page' => 1]) ?>" aria-label="First">
                                <i class="bi bi-chevron-double-left"></i>
                            </a>
                        </li>

                        <!-- Previous Page -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= build_query_params(['page' => $page - 1]) ?>" aria-label="Previous">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Numbered Pages -->
                        <?php 
                            $startPage = max(1, $page - 2);
                            $endPage   = min($totalPages, $page + 2);
                            for ($p = $startPage; $p <= $endPage; $p++): 
                        ?>
                            <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= build_query_params(['page' => $p]) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= build_query_params(['page' => $page + 1]) ?>" aria-label="Next">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>

                        <!-- Last Page -->
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= build_query_params(['page' => $totalPages]) ?>" aria-label="Last">
                                <i class="bi bi-chevron-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal: Add New Task -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="addTaskModalLabel">
                    <i class="bi bi-plus-circle me-1"></i> Create New Task
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="task_create.php" method="POST">
                <div class="modal-body p-4">
                    <?= csrf_field() ?>

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="modal_title" class="form-label fw-medium">Task Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="modal_title" 
                               name="title" 
                               placeholder="e.g. Set up Apache virtual host for RTIH staging" 
                               maxlength="255" 
                               required>
                        <div class="form-text small">Brief summary of the activity or deliverable.</div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="modal_description" class="form-label fw-medium">Description</label>
                        <textarea class="form-control" 
                                  id="modal_description" 
                                  name="description" 
                                  rows="3" 
                                  placeholder="Detailed acceptance criteria, notes, or implementation steps..."></textarea>
                    </div>

                    <div class="row g-3">
                        <!-- Status -->
                        <div class="col-md-4">
                            <label for="modal_status" class="form-label fw-medium">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_status" name="status" required>
                                <option value="Pending" selected>Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>

                        <!-- Priority -->
                        <div class="col-md-4">
                            <label for="modal_priority" class="form-label fw-medium">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_priority" name="priority" required>
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>

                        <!-- Due Date -->
                        <div class="col-md-4">
                            <label for="modal_due_date" class="form-label fw-medium">Due Date</label>
                            <input type="date" class="form-control" id="modal_due_date" name="due_date">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Create Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

