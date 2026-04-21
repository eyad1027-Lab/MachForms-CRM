<?php
/**
 * Machform CRM - Dashboard (index.php)
 * Main dashboard page with statistics and overview
 */

// Load configuration and core files
require_once __DIR__ . '/config/database.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';

// Initialize database and auth
$db = Database::getInstance();
$auth = new Auth();

// Require login
$auth->requireLogin();

// Load models
require_once MODULES_PATH . '/forms/FormModel.php';
require_once MODULES_PATH . '/entries/EntryModel.php';
require_once MODULES_PATH . '/reports/ReportModel.php';

// Initialize models
$formModel = new FormModel();
$entryModel = new EntryModel();
$reportModel = new ReportModel();

// Get dashboard statistics
$stats = $reportModel->getDashboardStats();
$entriesTrend = $reportModel->getEntriesTrend(7);
$healthStatus = $reportModel->getSystemHealth();

// Set page variables
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$breadcrumbs = [
    ['name' => 'Dashboard', 'url' => APP_URL . '/index.php']
];

// Start output buffering for content
ob_start();
?>

<!-- Dashboard Stats Cards -->
<div class="dashboard-stats">
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-details">
            <h3><?= number_format($stats['total_forms']) ?></h3>
            <p>Total Forms</p>
        </div>
        <div class="stat-trend <?= $stats['active_forms'] > 0 ? 'trend-up' : '' ?>">
            <span><?= number_format($stats['active_forms']) ?> active</span>
        </div>
    </div>
    
    <div class="stat-card stat-success">
        <div class="stat-icon">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="stat-details">
            <h3><?= number_format($stats['total_entries']) ?></h3>
            <p>Total Entries</p>
        </div>
        <div class="stat-trend">
            <span>+<?= number_format($stats['entries_today']) ?> today</span>
        </div>
    </div>
    
    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-details">
            <h3><?= number_format($stats['entries_this_week']) ?></h3>
            <p>This Week</p>
        </div>
        <div class="stat-trend">
            <span><?= number_format($stats['entries_this_month']) ?> this month</span>
        </div>
    </div>
    
    <div class="stat-card stat-info">
        <div class="stat-icon">
            <i class="fas fa-check-double"></i>
        </div>
        <div class="stat-details">
            <h3><?= number_format($stats['approval_enabled_forms']) ?></h3>
            <p>Approval Enabled</p>
        </div>
        <div class="stat-trend">
            <span><?= number_format($stats['payment_enabled_forms']) ?> with payment</span>
        </div>
    </div>
</div>

<!-- Charts and Recent Activity Row -->
<div class="dashboard-row">
    <!-- Entries Trend Chart -->
    <div class="dashboard-card chart-card">
        <div class="card-header">
            <h2><i class="fas fa-chart-line"></i> Entries Trend (Last 7 Days)</h2>
        </div>
        <div class="card-body">
            <canvas id="entriesTrendChart" height="100"></canvas>
        </div>
    </div>
    
    <!-- Status Breakdown -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-chart-pie"></i> Entry Status Breakdown</h2>
        </div>
        <div class="card-body">
            <div class="status-breakdown">
                <?php 
                $statuses = [
                    ['label' => 'Active', 'value' => $stats['status_breakdown']['active'] ?? 0, 'color' => '#4CAF50', 'icon' => 'fa-check-circle'],
                    ['label' => 'Pending', 'value' => $stats['status_breakdown']['pending'] ?? 0, 'color' => '#FF9800', 'icon' => 'fa-clock'],
                    ['label' => 'Approved', 'value' => $stats['status_breakdown']['approved'] ?? 0, 'color' => '#2196F3', 'icon' => 'fa-thumbs-up'],
                    ['label' => 'Rejected', 'value' => $stats['status_breakdown']['rejected'] ?? 0, 'color' => '#f44336', 'icon' => 'fa-thumbs-down']
                ];
                $total = array_sum(array_column($statuses, 'value'));
                ?>
                <?php foreach ($statuses as $status): ?>
                    <div class="status-item">
                        <div class="status-label">
                            <i class="fas <?= $status['icon'] ?>" style="color: <?= $status['color'] ?>"></i>
                            <span><?= $status['label'] ?></span>
                        </div>
                        <div class="status-bar-container">
                            <div class="status-bar" style="width: <?= $total > 0 ? ($status['value'] / $total * 100) : 0 ?>%; background: <?= $status['color'] ?>"></div>
                        </div>
                        <div class="status-value"><?= number_format($status['value']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Forms and System Health Row -->
<div class="dashboard-row">
    <!-- Top Forms -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-star"></i> Top Forms by Entries</h2>
            <a href="<?= APP_URL ?>/modules/forms/forms.php" class="btn-link">View All</a>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Form Name</th>
                        <th>Entries</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stats['top_forms'])): ?>
                        <?php foreach (array_slice($stats['top_forms'], 0, 5) as $form): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($form['form_name'] ?? 'Untitled') ?></strong>
                                    <br>
                                    <small>ID: #<?= $form['form_id'] ?></small>
                                </td>
                                <td><?= number_format($form['entry_count'] ?? 0) ?></td>
                                <td>
                                    <span class="badge badge-<?= ($form['form_active'] ?? 0) ? 'success' : 'secondary' ?>">
                                        <?= ($form['form_active'] ?? 0) ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= APP_URL ?>/modules/entries/entries.php?form_id=<?= $form['form_id'] ?>" class="btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No forms found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- System Health -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-heartbeat"></i> System Health</h2>
        </div>
        <div class="card-body">
            <div class="health-status">
                <div class="health-item <?= $healthStatus['database'] === 'Connected' ? 'healthy' : 'warning' ?>">
                    <i class="fas <?= $healthStatus['database'] === 'Connected' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                    <span>Database: <?= $healthStatus['database'] ?></span>
                </div>
                <div class="health-item">
                    <i class="fas fa-database"></i>
                    <span>Form Tables: <?= $healthStatus['form_tables'] ?></span>
                </div>
                
                <hr>
                
                <h4>Essential Tables</h4>
                <?php foreach ($healthStatus['tables'] as $table => $status): ?>
                    <div class="health-item <?= $status === 'Exists' ? 'healthy' : 'warning' ?>">
                        <i class="fas <?= $status === 'Exists' ? 'fa-check' : 'fa-times' ?>"></i>
                        <span><?= $table ?></span>
                        <span class="status-badge"><?= $status ?></span>
                    </div>
                <?php endforeach; ?>
                
                <?php if (!empty($healthStatus['warnings'])): ?>
                    <hr>
                    <div class="warnings">
                        <h4><i class="fas fa-exclamation-triangle"></i> Warnings</h4>
                        <?php foreach ($healthStatus['warnings'] as $warning): ?>
                            <div class="warning-item"><?= htmlspecialchars($warning) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="dashboard-card">
    <div class="card-header">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="<?= APP_URL ?>/modules/forms/forms.php?action=create" class="action-btn">
                <i class="fas fa-plus"></i>
                <span>Create Form</span>
            </a>
            <a href="<?= APP_URL ?>/modules/entries/entries.php" class="action-btn">
                <i class="fas fa-list"></i>
                <span>View Entries</span>
            </a>
            <a href="<?= APP_URL ?>/modules/reports/reports.php" class="action-btn">
                <i class="fas fa-file-export"></i>
                <span>Export Reports</span>
            </a>
            <a href="<?= APP_URL ?>/modules/settings/settings.php" class="action-btn">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Entries Trend Chart
const ctx = document.getElementById('entriesTrendChart').getContext('2d');
const trendData = <?= json_encode($entriesTrend) ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: trendData.map(item => item.date),
        datasets: [{
            label: 'Entries',
            data: trendData.map(item => item.count),
            borderColor: '#4CAF50',
            backgroundColor: 'rgba(76, 175, 80, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php
$content = ob_get_clean();

// Include the header/template
include TEMPLATES_PATH . '/header.php';
?>
