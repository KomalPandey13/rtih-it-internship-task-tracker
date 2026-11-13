<?php
/**
 * RTIH Task Tracker - Site Footer Template
 * 
 * Provides consistent footer branding, system status, and Bootstrap 5.3 bundle scripts.
 * 
 * @package RTIH_Task_Tracker
 * @author Ratan Tata Innovation Hub Amaravati Foundation (RTIH) - Intern IT Assignment
 */

if (!defined('RTIH_APP')) {
    define('RTIH_APP', true);
}
?>
        </div><!-- /.container -->
    </main>

    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container text-center text-muted small">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start mb-2 mb-md-0">
                    &copy; <?= date('Y') ?> <strong>Ratan Tata Innovation Hub Amaravati Foundation (RTIH)</strong>. All rights reserved.
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-secondary-subtle text-secondary me-2">Core PHP 8.x + MySQL PDO</span>
                    <span class="text-success"><i class="bi bi-shield-check"></i> CSRF &amp; PDO Protected</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3.3 Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Client-side confirmation helpers and tooltips -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Bootstrap tooltips if any
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
