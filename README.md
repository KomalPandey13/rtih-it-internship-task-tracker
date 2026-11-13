# RTIH Task Tracker

> **Ratan Tata Innovation Hub Amaravati Foundation (RTIH)**  
> *Intern IT Technical Assignment – Core PHP 8.x + MySQL (PDO) Web Application*

[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B%20%2F%208.0%2B-orange.svg)](https://www.mysql.com/)
[![Bootstrap 5.3](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com/)
[![Security](https://img.shields.io/badge/Security-PDO%20%7C%20CSRF%20%7C%20Bcrypt-success.svg)](#security-architecture)

A complete, production-grade, beginner-readable Task Tracking system developed in vanilla Core PHP (PDO) and MySQL with Bootstrap 5.3. Implements full CRUD capabilities, multi-tenant ownership authorization (users can strictly view and edit only their own tasks), title search, status/priority filtering, inline status switching, filter-preserving pagination, and OWASP-compliant security defenses.

---

## Table of Contents
1. [Features & Assignment Scope](#features--assignment-scope)
2. [Project Structure](#project-structure)
3. [Quick Setup Guide](#quick-setup-guide)
   - [Option A: XAMPP / WAMP / Laragon](#option-a-xampp--wamp--laragon)
   - [Option B: PHP Built-in Server + MySQL](#option-b-php-built-in-server--mysql)
4. [Demo Accounts & Test Credentials](#demo-accounts--test-credentials)
5. [Database Schema Justification](#database-schema-justification)
6. [Security Architecture](#security-architecture)
7. [Testing the Application](#testing-the-application)
8. [MySQL Backup & Restore Instructions](#mysql-backup--restore-instructions)

---

## Features & Assignment Scope

### Level 1: Full CRUD Task Management
- **Create**: Add new tasks with Title, Description, Status, Priority, and Due Date.
- **Read**: View task list in a responsive dashboard table with status badges and overdue alerts.
- **Update**: Edit full task details or quickly switch status with a single click from the dashboard table.
- **Delete**: Remove tasks with JavaScript client-side confirmation dialog and CSRF-protected POST handler.
- **User Feedback**: Persistent Bootstrap 5 alert flash messages for all actions.

### Level 2: Search, Filter, and Validation
- **Search**: Real-time keyword search against task titles via prepared `LIKE` queries.
- **Status Filter**: Filter by exact enum values: `Pending`, `In Progress`, `Completed`.
- **Priority Filter**: Filter by `Low`, `Medium`, `High`.
- **Validation**: Strict server-side validation preventing empty/whitespace-only titles, validating dates, and maintaining field data across form errors.

### Level 3: Authentication & Multi-Tenant Authorization
- **Registration**: User signup with name, email uniqueness validation, and bcrypt password hashing.
- **Login**: Secure login with `password_verify()`, session fixation defense via `session_regenerate_id(true)`, and generic error messages preventing user enumeration.
- **Logout**: CSRF-protected POST-only logout that destroys server session and purges browser cookies.
- **Multi-Tenant Ownership**: Strict authorization where every SQL query enforces `WHERE user_id = :user_id`. Users can NEVER view, edit, update status, or delete another user's tasks, even by tampering with `?id=` query strings or POST payloads.

### Optional Bonuses (Implemented)
- **Top Metric Cards**: Summary counters showing Total, Pending, In Progress, and Completed tasks for the logged-in user.
- **Priority & Due Date**: Task priority levels (`Low`, `Medium`, `High`) and due dates with visual "Overdue" badges for past-due tasks.
- **Filter-Preserving Pagination**: 5 tasks per page with custom query-string generator preserving active search, status, and priority parameters during page navigation.
- **Inline Quick Status**: Dropdown selector in the dashboard table allowing immediate status transitions without opening the edit screen.

---

## Project Structure

```text
rtih-task-tracker/
│
├── .gitignore                   # Ignores config.php, .env, OS and IDE temporary files
├── database.sql                 # MySQL schema, indexes, foreign keys, and 2 seeded users + 8 tasks
├── README.md                    # Setup guide, architecture details, and interview preparation
├── SUBMISSION_NOTES.md          # Assignment checklist, test matrix, AI disclosure, and interview script
├── test_suite.php               # Standalone test runner for syntax, security, and authorization
│
├── config/
│   ├── config.example.php       # Template configuration with placeholder credentials
│   └── config.php               # Active local config (configured for XAMPP root/empty pass)
│
├── includes/
│   ├── auth.php                 # require_login(), require_guest(), current_user(), is_logged_in()
│   ├── csrf.php                 # csrf_token(), csrf_field(), verify_csrf() with hash_equals()
│   ├── db.php                   # Secure PDO connection, errMode exception, production error masking
│   ├── footer.php               # Bootstrap 5.3 bundle scripts, footer branding, tooltips
│   ├── header.php               # Responsive Bootstrap 5 navbar, user role badge, flash renderer
│   ├── helpers.php              # e() XSS escaper, format_date(), status/priority badges, query builder
│   └── session.php              # Secure session start (HttpOnly, SameSite=Lax), flash messaging
│
├── index.php                    # Protected dashboard: metrics, filters, paginated table, add modal
├── login.php                    # User authentication form & session initialization
├── logout.php                   # POST-only CSRF-protected session destruction endpoint
├── register.php                 # New user registration & duplicate email check
├── task_create.php              # POST-only task creation handler
├── task_delete.php              # POST-only task deletion handler with ownership guard
├── task_edit.php                # Task editing page (GET pre-fill & POST update with ownership guard)
└── task_status.php              # POST-only inline quick status update handler
```

---

## Quick Setup Guide

### Option A: XAMPP / WAMP / Laragon

1. **Place Project in Web Root**:
   - Copy or clone this folder into your web root:
     - XAMPP: `C:/xampp/htdocs/rtih-task-tracker/`
     - Laragon: `C:/laragon/www/rtih-task-tracker/`
     - MAMP: `/Applications/MAMP/htdocs/rtih-task-tracker/`

2. **Start Services**:
   - Start **Apache** and **MySQL** from your XAMPP/Laragon control panel.

3. **Import Database**:
   - Open **phpMyAdmin** at `http://localhost/phpmyadmin/`.
   - Click the **Import** tab.
   - Choose `database.sql` from the project directory and click **Go** / **Import**.
   - *(Or run via MySQL CLI: `mysql -u root -p < database.sql`)*.

4. **Verify Configuration**:
   - The file `config/config.php` is already configured for default XAMPP/Laragon credentials:
     - Host: `127.0.0.1`
     - Database: `rtih_task_tracker`
     - User: `root`
     - Password: `''` (empty)
   - If your local MySQL has a root password, update `config/config.php` accordingly.

5. **Open Application**:
   - Visit: `http://localhost/rtih-task-tracker/login.php` in your browser.

---

### Option B: PHP Built-in Server + MySQL

1. **Import Database**:
   ```bash
   mysql -u root -p < database.sql
   ```

2. **Copy Configuration (if config.php is missing)**:
   ```bash
   cp config/config.example.php config/config.php
   ```

3. **Start the PHP Development Server**:
   ```bash
   php -S localhost:8000
   ```

4. **Open Application**:
   - Visit: `http://localhost:8000/login.php`

---

## Demo Accounts & Test Credentials

The database script automatically seeds two distinct test users with realistic assigned tasks:

| User Name | Role | Email | Password | Pre-seeded Tasks |
|:---|:---|:---|:---|:---|
| **Priya Sharma** | `intern` | `priya@rtih.org` | `Password@123` | 6 tasks (Tasks #1 - #6) |
| **Rahul Verma** | `admin` | `rahul@rtih.org` | `Password@123` | 4 tasks (Tasks #7 - #10) |

> **Testing Cross-User Authorization:**  
> Log in as Priya (`priya@rtih.org`), then try navigating manually to `task_edit.php?id=7` (which belongs to Rahul). The system will block the request with a **"Task not found or access denied"** alert and redirect safely back to Priya's dashboard.

---

## Database Schema Justification

The relational schema is intentionally minimal, fully normalized (3NF), and optimized for data integrity:

```
+------------------------------------+           +---------------------------------------------+
|               users                |           |                    tasks                    |
+------------------------------------+           +---------------------------------------------+
| id          INT UNSIGNED (PK, AI)  | <----+    | id          INT UNSIGNED (PK, AI)           |
| name        VARCHAR(100)           |      |    | user_id     INT UNSIGNED (FK -> users.id)   |
| email       VARCHAR(150) UNIQUE    |      +--- | title       VARCHAR(255)                    |
| password    VARCHAR(255)           |           | description TEXT                            |
| role        ENUM('admin','intern') |           | status      ENUM('Pending',...)             |
| created_at  TIMESTAMP             |           | priority    ENUM('Low','Medium','High')     |
| updated_at  TIMESTAMP             |           | due_date    DATE                            |
+------------------------------------+           | created_at  TIMESTAMP                      |
                                                 | updated_at  TIMESTAMP                      |
                                                 +---------------------------------------------+
```

### Schema Design Decisions:
1. **Foreign Key with `ON DELETE CASCADE`:** If a user account is removed, all corresponding task records are cleaned up automatically by MySQL, preventing orphaned data.
2. **`INT UNSIGNED` Primary Keys:** Matches modern auto-incrementing key standards with large capacity.
3. **Explicit Indexes:**
   - `idx_users_email`: Guarantees $O(1)$ fast lookup during login and registration uniqueness checks.
   - `idx_tasks_user_id`: Essential for filtering tasks by owner in every query.
   - `idx_tasks_status` & `idx_tasks_priority`: Optimizes composite multi-column filter queries on the dashboard.
   - `idx_tasks_due_date`: Enables fast chronological sorting and overdue identification.
4. **`utf8mb4` Character Set:** Full 4-byte UTF-8 support for modern international text, emojis, and symbols.

---

## Security Architecture

The codebase adheres strictly to OWASP Web Security guidelines:

1. **SQL Injection Defense (100% Prepared Statements):**
   - PDO prepared statements with `PDO::ATTR_EMULATE_PREPARES => false`.
   - Parameter values are sent through a separate binary protocol to the database engine, making SQL injection mathematically impossible.

2. **Cross-Site Scripting (XSS) Prevention:**
   - All dynamic output is sanitized using the helper function `e($string)` wrapping `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.

3. **Cross-Site Request Forgery (CSRF) Tokens:**
   - High-entropy cryptographic tokens generated using `bin2hex(random_bytes(32))`.
   - Verified on all state-changing endpoints (`POST`) using `hash_equals()` to prevent timing attacks.

4. **Bcrypt Password Hashing:**
   - Passwords are encrypted using native PHP `password_hash($password, PASSWORD_DEFAULT)` and verified using constant-time `password_verify()`.

5. **Session Hardening:**
   - Cookies configured with `HttpOnly` (inaccessible to JavaScript), `SameSite=Lax` (anti-CSRF), and `use_strict_mode=1`.
   - `session_regenerate_id(true)` is executed upon successful login to mitigate session fixation attacks.

6. **Production Error Masking:**
   - When `APP_ENV === 'production'`, database connection failures and stack traces are suppressed from display and logged to server logs via `error_log()`, preventing sensitive credentials or schema paths from leaking.

---

## Testing the Application

### Automated Verification Suite
Run the included verification script from the terminal:
```bash
php test_suite.php
```
This performs:
- PHP 8.x linting and syntax checks across all 17 files.
- XSS escaping and date helper tests.
- Status and priority badge tests.
- CSRF token generation and validation tests.
- Bcrypt password verification.
- In-memory SQLite simulation of multi-tenant authorization (verifying that User B cannot read, update, or delete User A's tasks).

---

## MySQL Backup & Restore Instructions

### Export / Backup:
```bash
mysqldump -u root -p --routines --triggers rtih_task_tracker > backup_rtih_task_tracker.sql
```

### Import / Restore:
```bash
mysql -u root -p rtih_task_tracker < backup_rtih_task_tracker.sql
```
