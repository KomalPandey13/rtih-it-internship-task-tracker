<?php
/**
 * RTIH Task Tracker - Database Connection (PDO)
 * 
 * Establishes a secure PDO database connection with:
 * - Prepared statement emulation disabled (real prepared statements prevent SQL Injection)
 * - Default associative fetch mode
 * - Exception error mode for proper transaction and error management
 * - Safe exception handling preventing credential or stack trace exposure
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

if (!defined('RTIH_APP')) {
    define('RTIH_APP', true);
}

// Load configuration
$configFile = __DIR__ . '/../config/config.php';
$exampleConfigFile = __DIR__ . '/../config/config.example.php';

if (file_exists($configFile)) {
    $config = require $configFile;
} elseif (file_exists($exampleConfigFile)) {
    $config = require $exampleConfigFile;
} else {
    die("Configuration error: Neither 'config/config.php' nor 'config/config.example.php' was found.");
}

$dbConfig  = $config['db'] ?? [];
$appConfig = $config['app'] ?? [];
$isProd    = ($appConfig['env'] ?? 'development') === 'production';

/**
 * Returns the singleton PDO database connection instance.
 *
 * @return PDO
 */
function get_db(): PDO
{
    static $pdoInstance = null;

    if ($pdoInstance !== null) {
        return $pdoInstance;
    }

    global $dbConfig, $isProd;

    $host    = getenv('DB_HOST') ?: ($dbConfig['host'] ?? '127.0.0.1');
    $port    = (int)(getenv('DB_PORT') ?: ($dbConfig['port'] ?? 3306));
    $dbname  = getenv('DB_NAME') ?: ($dbConfig['name'] ?? 'rtih_task_tracker');
    $user    = getenv('DB_USER') ?: ($dbConfig['user'] ?? 'root');
    $pass    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($dbConfig['pass'] ?? '');
    $charset = $dbConfig['charset'] ?? 'utf8mb4';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Forces true native prepared statements
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdoInstance = new PDO($dsn, $user, $pass, $options);
        return $pdoInstance;
    } catch (PDOException $e) {
        // Log the error securely to server logs
        error_log("Database Connection Error: " . $e->getMessage());

        if ($isProd) {
            // Production: Mask raw error completely to prevent database name/host leakage
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Service Unavailable - RTIH Task Tracker</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            </head>
            <body class="bg-light d-flex align-items-center min-vh-100">
                <div class="container text-center py-5">
                    <div class="card shadow-sm p-4 mx-auto" style="max-width: 540px;">
                        <h2 class="text-danger mb-3">Database Connection Error</h2>
                        <p class="text-muted">We are experiencing technical difficulties connecting to the application database. Please try again shortly or contact the RTIH system administrator.</p>
                        <a href="login.php" class="btn btn-outline-primary mt-2">Retry</a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        } else {
            // Development: Display helpful setup diagnostic guidance
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Database Setup Required - RTIH Task Tracker</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            </head>
            <body class="bg-light py-5">
                <div class="container">
                    <div class="card shadow-sm border-danger p-4 mx-auto" style="max-width: 700px;">
                        <h3 class="text-danger mb-3">Database Connection Error (Development Mode)</h3>
                        <p>Could not connect to MySQL database <code><?= htmlspecialchars($dbname, ENT_QUOTES, 'UTF-8') ?></code> at <code><?= htmlspecialchars($host, ENT_QUOTES, 'UTF-8') ?>:<?= htmlspecialchars($port, ENT_QUOTES, 'UTF-8') ?></code>.</p>
                        <div class="alert alert-warning mb-3">
                            <strong>PDO Error:</strong> <?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <h5>How to Resolve:</h5>
                        <ol class="mb-3">
                            <li>Ensure MySQL is running (e.g., start MySQL in XAMPP / Laragon control panel).</li>
                            <li>Import the database schema:
                                <pre class="bg-dark text-light p-2 rounded mt-1">mysql -u root -p < database.sql</pre>
                            </li>
                            <li>Verify credentials in <code>config/config.php</code>.</li>
                        </ol>
                        <a href="" class="btn btn-primary">Refresh / Try Again</a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

// Global PDO handle for simple procedural scripts
$pdo = get_db();

