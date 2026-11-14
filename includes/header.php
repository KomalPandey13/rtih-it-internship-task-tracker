<?php
/**
 * RTIH Task Tracker - Site Header Template
 * 
 * Includes responsive Bootstrap 5.3 styling, Bootstrap Icons, responsive navigation,
 * user identity presentation, and global flash alert messaging.
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

if (!defined('RTIH_APP')) {
    define('RTIH_APP', true);
}

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user = current_user();
$pageTitle = $pageTitle ?? 'Task Tracker';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | RTIH Task Tracker</title>

    <!-- Bootstrap 5.3.3 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --rtih-primary: #0d47a1;
            --rtih-accent: #1976d2;
            --rtih-gold: #fbc02d;
            --rtih-bg: #f8fafc;
        }
        body {
            background-color: var(--rtih-bg);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
        }
        .navbar-brand-rtih {
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #ffffff !important;
        }
        .navbar-rtih {
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.12);
        }
        .card {
            border-radius: 0.6rem;
        }
        .metric-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08) !important;
        }
        .table > :not(caption) > * > * {
            vertical-align: middle;
        }
    </style>
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-rtih sticky-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-rtih d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-kanban-fill fs-4 text-warning"></i>
                <span>RTIH <span class="fw-light opacity-75">Task Tracker</span></span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                    <?php if (is_logged_in() && $user): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white-50 active d-flex align-items-center gap-1" href="index.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item d-none d-lg-block text-white-50 px-1">|</li>
                        <li class="nav-item d-flex align-items-center gap-2 py-1 py-lg-0">
                            <span class="text-light fw-medium">
                                <i class="bi bi-person-circle me-1 opacity-75"></i><?= e($user['name']) ?>
                            </span>
                            <?= role_badge($user['role']) ?>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <!-- CSRF-Protected POST Logout Form -->
                            <form action="logout.php" method="POST" class="d-inline m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-light rounded-pill px-3" title="Secure Logout">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-sm btn-warning fw-semibold rounded-pill px-3 text-dark" href="register.php">
                                <i class="bi bi-person-plus-fill me-1"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sub-header organization banner -->
    <div class="bg-white border-bottom py-1 small text-muted">
        <div class="container d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-building me-1 text-primary"></i> Ratan Tata Innovation Hub Amaravati Foundation
            </span>
            <span class="badge bg-light text-secondary border">IT Technical Assignment</span>
        </div>
    </div>

    <!-- Main Content Container -->
    <main class="py-4">
        <div class="container">
            <!-- Global Flash Messages Notification Block -->
            <?php foreach (get_flash() as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <?php if ($flash['type'] === 'success'): ?>
                            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                        <?php elseif ($flash['type'] === 'danger'): ?>
                            <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
                        <?php elseif ($flash['type'] === 'warning'): ?>
                            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                        <?php else: ?>
                            <i class="bi bi-info-circle-fill fs-5 me-2"></i>
                        <?php endif; ?>
                        <div><?= e($flash['message']) ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

