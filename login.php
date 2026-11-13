<?php
/**
 * RTIH Task Tracker - User Authentication / Login
 * 
 * Authenticates users using:
 * - PDO prepared statement for email lookup
 * - password_verify() against bcrypt hashes
 * - session_regenerate_id(true) on success to prevent session fixation
 * - Generic error messaging to prevent user enumeration
 * - CSRF token verification on POST
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

// Redirect if already logged in
require_guest();

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    validate_csrf_or_abort();

    // 2. Collect and trim input
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 3. Basic validation
    if ($email === '' || $password === '') {
        $error = 'Please provide both your email address and password.';
    } else {
        try {
            // Prepared statement to look up user safely
            $stmt = $pdo->prepare("
                SELECT id, name, email, password, role 
                FROM users 
                WHERE email = :email 
                LIMIT 1
            ");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            // Verify password using native bcrypt verification
            if ($user && password_verify($password, $user['password'])) {
                // Prevent session fixation by issuing a fresh session identifier
                session_regenerate_id(true);

                // Populate session data
                $_SESSION['user_id']    = (int)$user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                // Issue a fresh CSRF token for the authenticated state
                regenerate_csrf_token();

                set_flash('success', 'Welcome back, ' . e($user['name']) . '!');
                header('Location: index.php');
                exit;
            } else {
                // Generic error prevents attackers from distinguishing valid vs invalid email addresses
                $error = 'Invalid email address or password.';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An authentication service error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-shield-lock fs-3"></i>
                    </div>
                    <h3 class="fw-bold">Sign In</h3>
                    <p class="text-muted small">Enter your credentials to access your RTIH tasks</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" novalidate>
                    <?= csrf_field() ?>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   placeholder="name@rtih.org" 
                                   value="<?= e($email) ?>" 
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label fw-medium mb-0">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter your password" 
                                   required>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>

                <div class="text-center mt-4 pt-2 border-top">
                    <p class="text-muted small mb-0">Don't have an account? <a href="register.php" class="text-primary fw-medium text-decoration-none">Register here</a></p>
                </div>
            </div>
        </div>

        <!-- Pre-seeded Test Accounts Callout -->
        <div class="card mt-3 bg-light border-0 shadow-none">
            <div class="card-body p-3">
                <h6 class="fw-bold text-secondary mb-2 small text-uppercase">
                    <i class="bi bi-info-circle me-1"></i> Pre-seeded Demo Accounts
                </h6>
                <div class="small text-muted">
                    <div class="d-flex justify-content-between mb-1">
                        <span><strong>Priya (Intern):</strong> <code>priya@rtih.org</code></span>
                        <span><code>Password@123</code></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><strong>Rahul (Admin):</strong> <code>rahul@rtih.org</code></span>
                        <span><code>Password@123</code></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
