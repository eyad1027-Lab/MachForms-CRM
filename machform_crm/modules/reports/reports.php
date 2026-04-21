<?php
/**
 * Machform CRM - Reports Page
 */
require_once __DIR__ . '/../../config/database.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';
require_once MODULES_PATH . '/reports/ReportModel.php';

$auth = new Auth();
$auth->requireLogin();

$reportModel = new ReportModel();
$stats = $reportModel->getDashboardStats();

$pageTitle = 'Reports';
$currentPage = 'reports';

ob_start();
?>
<div class="page-header">
    <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
</div>
<div class="card">
    <div class="card-body">
        <p>Reports module - View comprehensive analytics and export data.</p>
        <div class="report-options">
            <a href="?export=dashboard" class="btn btn-primary">Export Dashboard Stats</a>
            <a href="?export=entries" class="btn btn-secondary">Export Entries Report</a>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/header.php';
?>
