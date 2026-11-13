<?php
/**
 * RTIH Task Tracker - User Registration
 * 
 * Handles new account creation with server-side validation:
 * - Full name (2-100 characters)
 * - Email format validation & duplicate email uniqueness check
 * - Password length check (minimum 8 characters) & confirmation match
 * - Secure password hashing using bcrypt via password_hash()
 * - CSRF verification
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

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    validate_csrf_or_abort();

    // 2. Collect and sanitize input
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // 3. Validation checks
    if ($name === '') {
        $errors['name'] = 'Full name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Name must be between 2 and 100 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 150) {
        $errors['email'] = 'Email must not exceed 150 characters.';
    } else {
        // Check if email already exists in database using prepared statement
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors['email'] = 'This email address is already registered. Please log in.';
            }
        } catch (PDOException $e) {
            error_log("Registration check error: " . $e->getMessage());
            $errors['general'] = 'A database error occurred. Please try again.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    if ($confirmPassword === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // 4. If no errors, persist the user
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'intern'; // Default role for new signups

            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, created_at)
                VALUES (:name, :email, :password, :role, NOW())
            ");
            $stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $passwordHash,
                ':role'     => $role,
            ]);

            set_flash('success', 'Registration successful! You may now log in with your credentials.');
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            error_log("User registration insertion error: " . $e->getMessage());
            $errors['general'] = 'Failed to create your account. Please try again.';
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-person-plus fs-3"></i>
                    </div>
                    <h3 class="fw-bold">Create an Account</h3>
                    <p class="text-muted small">Join RTIH Task Tracker to manage your internship activities</p>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><?= e($errors['general']) ?></div>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" novalidate>
                    <?= csrf_field() ?>

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-medium">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" 
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name" 
                                   placeholder="e.g. Priya Sharma" 
                                   value="<?= e($name) ?>" 
                                   required 
                                   autofocus>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" 
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" 
                                   name="email" 
                                   placeholder="name@rtih.org" 
                                   value="<?= e($email) ?>" 
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-medium">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" 
                                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                                   id="password" 
                                   name="password" 
                                   placeholder="At least 8 characters" 
                                   required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text small">Minimum 8 characters. Must contain letters and numbers.</div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-medium">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" 
                                   class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Re-enter your password" 
                                   required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?= e($errors['confirm_password']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Register Account
                    </button>
                </form>

                <div class="text-center mt-4 pt-2 border-top">
                    <p class="text-muted small mb-0">Already registered? <a href="login.php" class="text-primary fw-medium text-decoration-none">Log in here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
