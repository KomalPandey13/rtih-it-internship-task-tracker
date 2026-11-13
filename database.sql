-- ==============================================================================
-- Ratan Tata Innovation Hub Amaravati Foundation (RTIH)
-- Intern IT Technical Assignment - Database Schema & Seed Data
-- Database Name: rtih_task_tracker
-- Character Set: utf8mb4 / utf8mb4_unicode_ci
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `rtih_task_tracker` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `rtih_task_tracker`;

-- Disable foreign key checks during schema rebuild
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. Table structure for table `users`
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'intern', 'user') NOT NULL DEFAULT 'intern',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Table structure for table `tasks`
-- ------------------------------------------------------------------------------
CREATE TABLE `tasks` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('Pending', 'In Progress', 'Completed') NOT NULL DEFAULT 'Pending',
  `priority` ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
  `due_date` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tasks_user_id` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  INDEX `idx_tasks_user_id` (`user_id`),
  INDEX `idx_tasks_status` (`status`),
  INDEX `idx_tasks_priority` (`priority`),
  INDEX `idx_tasks_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 3. Seed Data: Pre-configured Users
-- Both demo accounts have password: Password@123
-- Hash: $2y$12$LPrMMMYB1oGvr1Vnpywi5ukKySGCtnnt.90661E2SpMU0OL7JT5CC
-- ------------------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Priya Sharma', 'priya@rtih.org', '$2y$12$LPrMMMYB1oGvr1Vnpywi5ukKySGCtnnt.90661E2SpMU0OL7JT5CC', 'intern', NOW()),
(2, 'Rahul Verma', 'rahul@rtih.org', '$2y$12$LPrMMMYB1oGvr1Vnpywi5ukKySGCtnnt.90661E2SpMU0OL7JT5CC', 'admin', NOW());

-- ------------------------------------------------------------------------------
-- 4. Seed Data: Sample Tasks
-- 8 realistic IT tasks split across User 1 (Priya) and User 2 (Rahul)
-- Enables immediate testing of multi-tenant ownership authorization
-- ------------------------------------------------------------------------------

-- Priya's Tasks (User ID = 1)
INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Configure Apache Virtual Host for RTIH portal', 'Set up local virtual host definitions on port 8000 with mod_rewrite enabled for clean routing and HTTPS headers.', 'In Progress', 'High', '2026-11-18', NOW(), NOW()),
(2, 1, 'Write unit tests for authentication helpers', 'Cover session initialization, CSRF token generation, password hashing verification, and input trimming routines.', 'Pending', 'Medium', '2026-11-20', NOW(), NOW()),
(3, 1, 'Document database schema relationships', 'Create an ER diagram illustrating foreign key cascades between users and tasks tables for technical handoff.', 'Completed', 'Low', '2026-11-10', NOW(), NOW()),
(4, 1, 'Prepare MySQL backup and restore procedures', 'Document mysqldump commands with automated backup script templates for automated nightly database archiving.', 'Pending', 'High', '2026-11-25', NOW(), NOW()),
(5, 1, 'Refactor UI with Bootstrap 5.3 components', 'Ensure all task badges, metric cards, and modal forms conform to responsive mobile viewport standards.', 'In Progress', 'Medium', '2026-11-19', NOW(), NOW()),
(6, 1, 'Perform OWASP Top 10 security audit', 'Review prepared statements, htmlspecialchars output escaping, and session fixation safeguards on login.', 'Completed', 'High', '2026-11-12', NOW(), NOW());

-- Rahul's Tasks (User ID = 2)
INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `status`, `priority`, `due_date`, `created_at`, `updated_at`) VALUES
(7, 2, 'Audit server security headers and SSL certificates', 'Verify HSTS, X-Content-Type-Options, X-Frame-Options, and TLS 1.3 cipher suite configurations across RTIH servers.', 'Completed', 'High', '2026-11-08', NOW(), NOW()),
(8, 2, 'Review RTIH intern onboarding repository PRs', 'Perform code reviews on assignment submissions, checking for PDO prepared statements and multi-tenant authorization.', 'In Progress', 'High', '2026-11-17', NOW(), NOW()),
(9, 2, 'Optimize MySQL indexes for task filtering queries', 'Analyze composite index performance on status, priority, and due_date columns under high concurrency.', 'Pending', 'Medium', '2026-11-22', NOW(), NOW()),
(10, 2, 'Set up automated daily database backup cron job', 'Schedule mysqldump cron task on Linux server with encrypted off-site cloud storage synchronization.', 'Pending', 'Low', '2026-11-28', NOW(), NOW());
