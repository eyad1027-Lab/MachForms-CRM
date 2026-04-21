<?php
/**
 * Machform CRM - Entries Management Page
 * View and manage form submissions
 */

// Load configuration and core files
require_once __DIR__ . '/../../config/database.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';
require_once MODULES_PATH . '/forms/FormModel.php';
require_once MODULES_PATH . '/entries/EntryModel.php';

// Initialize
$db = Database::getInstance();
$auth = new Auth();
$auth->requireLogin();

$formModel = new FormModel();
$entryModel = new EntryModel();

// Get form ID from URL
$formId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : null;

// Handle export
if (isset($_GET['export'])) {
    if ($formId) {
        $entryModel->exportToCSV($formId);
    }
    exit;
}

// Handle entry actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                $entryId = (int)$_POST['entry_id'];
                $entryModel->deleteEntry($formId, $entryId);
                header('Location: entries.php?form_id=' . $formId . '&success=deleted');
                exit;
            case 'update_status':
                $entryId = (int)$_POST['entry_id'];
                $status = (int)$_POST['status'];
                $entryModel->updateEntryStatus($formId, $entryId, $status);
                header('Location: entries.php?form_id=' . $formId . '&success=updated');
                exit;
        }
    }
}

// Get form details
$form = $formId ? $formModel->getFormById($formId) : null;

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = DEFAULT_PAGE_SIZE;

// Filters
$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Get entries
$entriesData = $formId ? $entryModel->getEntries($formId, $page, $pageSize, $filters) : null;

// Get all forms for dropdown
$allForms = $formModel->getActiveForms();

// Set page variables
$pageTitle = 'Entries Management';
$currentPage = 'entries';
$breadcrumbs = [
    ['name' => 'Dashboard', 'url' => APP_URL . '/index.php'],
    ['name' => 'Entries', 'url' => APP_URL . '/modules/entries/entries.php']
];

// Start output buffering
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-inbox"></i> Form Entries</h1>
        <p>View and manage form submissions</p>
    </div>
    <?php if ($formId && $entriesData['total'] > 0): ?>
        <a href="?form_id=<?= $formId ?>&export=csv" class="btn btn-primary">
            <i class="fas fa-file-export"></i> Export CSV
        </a>
    <?php endif; ?>
</div>

<!-- Form Selector -->
<div class="card mb-20">
    <div class="card-body">
        <form method="GET" action="" class="form-inline">
            <div class="form-group">
                <label for="form_id">Select Form:</label>
                <select name="form_id" id="form_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select a Form --</option>
                    <?php foreach ($allForms as $f): ?>
                        <option value="<?= $f['form_id'] ?>" <?= $formId == $f['form_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['form_name']) ?> (#<?= $f['form_id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($formId && $form): ?>
    <!-- Form Info & Stats -->
    <div class="stats-row mb-20">
        <div class="stat-mini">
            <span class="stat-label">Total Entries</span>
            <span class="stat-value"><?= number_format($entriesData['total']) ?></span>
        </div>
        <div class="stat-mini">
            <span class="stat-label">Current Page</span>
            <span class="stat-value"><?= $page ?> / <?= max(1, $entriesData['pages']) ?></span>
        </div>
        <div class="stat-mini">
            <span class="stat-label">Form Status</span>
            <span class="stat-value badge badge-<?= $form['form_active'] ? 'success' : 'secondary' ?>">
                <?= $form['form_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-20">
        <div class="card-body">
            <form method="GET" action="" class="filters-form">
                <input type="hidden" name="form_id" value="<?= $formId ?>">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search entries..." 
                           value="<?= htmlspecialchars($filters['search']) ?>" class="form-control">
                    
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="1" <?= $filters['status'] == '1' ? 'selected' : '' ?>>Active</option>
                        <option value="2" <?= $filters['status'] == '2' ? 'selected' : '' ?>>Pending</option>
                        <option value="3" <?= $filters['status'] == '3' ? 'selected' : '' ?>>Approved</option>
                        <option value="4" <?= $filters['status'] == '4' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                    
                    <input type="date" name="date_from" placeholder="From Date" 
                           value="<?= htmlspecialchars($filters['date_from']) ?>" class="form-control">
                    <input type="date" name="date_to" placeholder="To Date" 
                           value="<?= htmlspecialchars($filters['date_to']) ?>" class="form-control">
                    
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?form_id=<?= $formId ?>" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Entries Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($entriesData['entries'])): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Entries Found</h3>
                    <p>This form doesn't have any entries yet or no entries match your filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th width="150">Date Created</th>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <th>Element <?= $i ?></th>
                                <?php endfor; ?>
                                <th width="100">IP Address</th>
                                <th width="80">Status</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entriesData['entries'] as $entry): ?>
                                <tr>
                                    <td>#<?= $entry['id'] ?></td>
                                    <td>
                                        <small><?= date('M d, Y H:i', strtotime($entry['date_created'])) ?></small>
                                    </td>
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <td>
                                            <small><?= htmlspecialchars(substr($entry['element_' . $i] ?? '', 0, 30)) ?>...</small>
                                        </td>
                                    <?php endfor; ?>
                                    <td><small><?= htmlspecialchars($entry['ip_address'] ?? 'N/A') ?></small></td>
                                    <td>
                                        <span class="badge badge-<?= getStatusBadge($entry['status']) ?>">
                                            <?= getStatusLabel($entry['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="viewEntry(<?= $entry['id'] ?>)" 
                                                    class="btn-icon btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" 
                                                  onsubmit="return confirm('Delete this entry?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                                <button type="submit" class="btn-icon btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($entriesData['pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?form_id=<?= $formId ?>&page=<?= $page - 1 ?><?= buildQueryString($filters) ?>" 
                               class="btn btn-sm">Previous</a>
                        <?php endif; ?>
                        
                        <span class="page-info">Page <?= $page ?> of <?= $entriesData['pages'] ?></span>
                        
                        <?php if ($page < $entriesData['pages']): ?>
                            <a href="?form_id=<?= $formId ?>&page=<?= $page + 1 ?><?= buildQueryString($filters) ?>" 
                               class="btn btn-sm">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php elseif (!$formId): ?>
    <div class="empty-state">
        <i class="fas fa-arrow-up"></i>
        <h3>Select a Form</h3>
        <p>Please select a form from the dropdown above to view its entries.</p>
    </div>
<?php endif; ?>

<style>
.mb-20 { margin-bottom: 20px; }

.stats-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-mini {
    background: white;
    padding: 15px 25px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    min-width: 150px;
}

.stat-label {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 5px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
}

.form-inline {
    display: flex;
    gap: 15px;
    align-items: center;
}

.filters-form .filter-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filters-form .form-control {
    min-width: 150px;
}

.table-responsive {
    overflow-x: auto;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.page-info {
    color: #666;
    font-size: 0.9rem;
}
</style>

<script>
function viewEntry(entryId) {
    // Implement entry view modal or redirect
    alert('View entry #' + entryId);
}
</script>

<?php
// Helper functions
function getStatusBadge($status) {
    $badges = [
        1 => 'success',
        2 => 'warning',
        3 => 'info',
        4 => 'danger'
    ];
    return $badges[$status] ?? 'secondary';
}

function getStatusLabel($status) {
    $labels = [
        1 => 'Active',
        2 => 'Pending',
        3 => 'Approved',
        4 => 'Rejected'
    ];
    return $labels[$status] ?? 'Unknown';
}

function buildQueryString($filters) {
    $params = [];
    foreach ($filters as $key => $value) {
        if (!empty($value)) {
            $params[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    return !empty($params) ? '&' . implode('&', $params) : '';
}

$content = ob_get_clean();
include TEMPLATES_PATH . '/header.php';
?>
