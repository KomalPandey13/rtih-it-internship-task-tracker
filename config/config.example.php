<?php
/**
 * RTIH Task Tracker - Configuration Template
 * 
 * Copy this file to 'config/config.php' and update with your local database credentials.
 * NOTE: 'config/config.php' is git-ignored to prevent credential leaks.
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

// Prevent direct script access
if (!defined('RTIH_APP')) {
    define('RTIH_APP', true);
}

return [
    // Database Configuration
    'db' => [
        'host'     => '127.0.0.1',             // Database host (usually localhost or 127.0.0.1)
        'port'     => 3306,                    // Default MySQL port
        'name'     => 'rtih_task_tracker',     // Database name (created via database.sql)
        'user'     => 'root',                  // MySQL username (default: 'root' in XAMPP/Laragon)
        'pass'     => '',                      // MySQL password (default: empty '' in XAMPP/Laragon)
        'charset'  => 'utf8mb4',               // Recommended modern UTF-8 charset
    ],

    // Application Settings
    'app' => [
        'name'        => 'RTIH Task Tracker',
        'env'         => 'development',        // 'development' (detailed errors) or 'production' (safe masked errors)
        'url'         => 'http://localhost:8000',
        'per_page'    => 5,                    // Tasks per page for pagination
        'session_ttl' => 7200,                 // Session lifetime in seconds (2 hours)
    ],
];

