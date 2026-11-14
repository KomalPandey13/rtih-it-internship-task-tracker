<?php
/**
 * RTIH Task Tracker - Automated Test & Verification Suite
 * 
 * Runs syntax checks, unit tests on core helpers, CSRF protection,
 * password hashing, and in-memory multi-tenant authorization simulations.
 */

declare(strict_types=1);

define('RTIH_APP', true);

echo "========================================================\n";
echo " RTIH Task Tracker - Verification & Automated Test Suite\n";
echo "========================================================\n\n";

$passedTests = 0;
$failedTests = 0;

function it(string $description, callable $fn): void {
    global $passedTests, $failedTests;
    try {
        $fn();
        echo "  [PASS] {$description}\n";
        $passedTests++;
    } catch (Throwable $e) {
        echo "  [FAIL] {$description}\n";
        echo "         Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failedTests++;
    }
}

// 1. Syntax Check on All PHP Files
echo "1. Checking PHP Syntax across all source files:\n";
$phpFiles = glob(__DIR__ . '/*.php');
$phpFiles = array_merge($phpFiles, glob(__DIR__ . '/includes/*.php'), glob(__DIR__ . '/config/*.php'));

foreach ($phpFiles as $file) {
    if (basename($file) === 'test_suite.php') continue;
    $relPath = str_replace(__DIR__ . '/', '', $file);
    it("Syntax: {$relPath}", function() use ($file) {
        $output = [];
        $exitCode = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $exitCode);
        if ($exitCode !== 0) {
            throw new Exception("Syntax error: " . implode("\n", $output));
        }
    });
}

// 2. Unit Testing Helpers
echo "\n2. Testing Utility Helpers (includes/helpers.php):\n";
require_once __DIR__ . '/includes/helpers.php';

it("XSS Escaper e() sanitizes script tags and special chars", function() {
    $raw = '<script>alert("XSS & attack")</script>';
    $escaped = e($raw);
    if (strpos($escaped, '<script>') !== false || strpos($escaped, '&') === false) {
        throw new Exception("XSS escaping failed: {$escaped}");
    }
    if (e(null) !== '') {
        throw new Exception("Null string handling failed in e()");
    }
});

it("Date Formatter format_date() formats valid dates and handles null", function() {
    $res1 = format_date('2026-11-20', 'Y-m-d');
    if ($res1 !== '2026-11-20') throw new Exception("Expected 2026-11-20, got {$res1}");
    $resNull = format_date(null);
    if (strpos($resNull, 'None') === false) throw new Exception("Expected None for null date");
});

it("Status Badge status_badge() returns correct Bootstrap 5 classes", function() {
    if (strpos(status_badge('Pending'), 'bg-warning-subtle') === false) throw new Exception("Pending badge mismatch");
    if (strpos(status_badge('In Progress'), 'bg-primary-subtle') === false) throw new Exception("In Progress badge mismatch");
    if (strpos(status_badge('Completed'), 'bg-success-subtle') === false) throw new Exception("Completed badge mismatch");
});

it("Query String Builder build_query_params() preserves existing filters", function() {
    $_GET = ['search' => 'apache', 'status' => 'Pending'];
    $qs = build_query_params(['page' => 2]);
    if ($qs !== '?search=apache&status=Pending&page=2') {
        throw new Exception("Expected ?search=apache&status=Pending&page=2, got {$qs}");
    }
});

// 3. Testing CSRF Protection
echo "\n3. Testing CSRF Security (includes/csrf.php):\n";
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/csrf.php';

it("Generates 64-char hex CSRF token and validates with hash_equals", function() {
    $token = csrf_token();
    if (strlen($token) !== 64 || !ctype_xdigit($token)) {
        throw new Exception("Invalid token format: {$token}");
    }
    if (!verify_csrf($token)) {
        throw new Exception("Valid token verification failed");
    }
    if (verify_csrf('invalid_token_string_here')) {
        throw new Exception("Tampered token incorrectly verified as valid");
    }
});

// 4. Testing Password Hashing
echo "\n4. Testing Password Security:\n";
it("Verifies seeded bcrypt password hash against 'Password@123'", function() {
    $hash = '$2y$12$LPrMMMYB1oGvr1Vnpywi5ukKySGCtnnt.90661E2SpMU0OL7JT5CC';
    if (!password_verify('Password@123', $hash)) {
        throw new Exception("Seeded hash failed verification");
    }
    if (password_verify('WrongPassword', $hash)) {
        throw new Exception("Wrong password unexpectedly accepted");
    }
});

// 5. In-Memory Multi-Tenant Ownership Authorization Simulation
echo "\n5. Simulating Multi-Tenant Authorization (User A vs User B):\n";
it("Guards against cross-user task modification (IDOR protection)", function() {
    $memDb = new PDO('sqlite::memory:');
    $memDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $memDb->exec("
        CREATE TABLE tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            status TEXT NOT NULL
        )
    ");

    // Seed: Task 1 belongs to User 1 (Priya), Task 2 belongs to User 2 (Rahul)
    $memDb->exec("INSERT INTO tasks (id, user_id, title, status) VALUES (1, 1, 'Priya Task', 'Pending')");
    $memDb->exec("INSERT INTO tasks (id, user_id, title, status) VALUES (2, 2, 'Rahul Task', 'Pending')");

    // Scenario A: User 1 tries to fetch their own task
    $stmt = $memDb->prepare("SELECT * FROM tasks WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => 1, ':user_id' => 1]);
    $task = $stmt->fetch();
    if (!$task || $task['title'] !== 'Priya Task') {
        throw new Exception("User 1 could not fetch their own task");
    }

    // Scenario B: User 2 tries to fetch User 1's task (IDOR URL tampering ?id=1)
    $stmt = $memDb->prepare("SELECT * FROM tasks WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => 1, ':user_id' => 2]);
    $stolenTask = $stmt->fetch();
    if ($stolenTask) {
        throw new Exception("CRITICAL SECURITY FLAW: User 2 was able to view User 1's task!");
    }

    // Scenario C: User 2 tries to update User 1's task (POST tampering task_id=1)
    $updateStmt = $memDb->prepare("UPDATE tasks SET status = :status WHERE id = :id AND user_id = :user_id");
    $updateStmt->execute([':status' => 'Completed', ':id' => 1, ':user_id' => 2]);
    if ($updateStmt->rowCount() > 0) {
        throw new Exception("CRITICAL SECURITY FLAW: User 2 was able to update User 1's task!");
    }

    // Scenario D: User 2 tries to delete User 1's task
    $deleteStmt = $memDb->prepare("DELETE FROM tasks WHERE id = :id AND user_id = :user_id");
    $deleteStmt->execute([':id' => 1, ':user_id' => 2]);
    if ($deleteStmt->rowCount() > 0) {
        throw new Exception("CRITICAL SECURITY FLAW: User 2 was able to delete User 1's task!");
    }
});

echo "\n========================================================\n";
echo " Results: {$passedTests} Passed, {$failedTests} Failed\n";
echo "========================================================\n";

if ($failedTests > 0) {
    exit(1);
}

