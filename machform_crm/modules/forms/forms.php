<?php
/**
 * Machform CRM - Forms Management Page
 * List, create, edit, and delete forms
 */

// Load configuration and core files
require_once __DIR__ . '/../../config/database.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';
require_once MODULES_PATH . '/forms/FormModel.php';

// Initialize
$db = Database::getInstance();
$auth = new Auth();
$auth->requireLogin();

$formModel = new FormModel();

// Handle actions
$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

// Handle form creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $formData = [
        'form_name' => $_POST['form_name'] ?? 'Untitled Form',
        'form_description' => $_POST['form_description'] ?? '',
        'form_email' => $_POST['form_email'] ?? '',
        'form_active' => isset($_POST['form_active']) ? 1 : 0,
        'form_tags' => $_POST['form_tags'] ?? '',
        'form_created_by' => $auth->getUserId()
    ];
    
    $formId = $formModel->createForm($formData);
    
    if ($formId) {
        header('Location: forms.php?success=created');
        exit;
    } else {
        $message = 'Failed to create form.';
        $messageType = 'error';
    }
}

// Handle form deletion
if ($action === 'delete' && isset($_GET['id'])) {
    $formId = (int)$_GET['id'];
    if ($formModel->deleteForm($formId)) {
        header('Location: forms.php?success=deleted');
        exit;
    }
}

// Handle toggle status
if ($action === 'toggle' && isset($_GET['id'])) {
    $formId = (int)$_GET['id'];
    $formModel->toggleFormStatus($formId);
    header('Location: forms.php');
    exit;
}

// Get success message from URL
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created':
            $message = 'Form created successfully!';
            $messageType = 'success';
            break;
        case 'updated':
            $message = 'Form updated successfully!';
            $messageType = 'success';
            break;
        case 'deleted':
            $message = 'Form deleted successfully!';
            $messageType = 'success';
            break;
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$forms = !empty($search) ? $formModel->searchForms($search) : $formModel->getAllForms();

// Add entry counts to forms
foreach ($forms as &$form) {
    $form['entry_count'] = $formModel->getEntryCount($form['form_id']);
}

// Set page variables
$pageTitle = 'Forms Management';
$currentPage = 'forms';
$breadcrumbs = [
    ['name' => 'Dashboard', 'url' => APP_URL . '/index.php'],
    ['name' => 'Forms', 'url' => APP_URL . '/modules/forms/forms.php']
];

// Start output buffering
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-file-alt"></i> Forms Management</h1>
        <p>Manage all your Machform forms</p>
    </div>
    <div class="page-header-right">
        <?php if ($action !== 'create'): ?>
            <a href="?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Form
            </a>
        <?php else: ?>
            <a href="forms.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($action === 'create'): ?>
    <!-- Create Form -->
    <div class="card">
        <div class="card-header">
            <h2>Create New Form</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="" class="form-horizontal">
                <div class="form-group">
                    <label for="form_name">Form Name *</label>
                    <input type="text" id="form_name" name="form_name" required 
                           class="form-control" placeholder="Enter form name">
                </div>
                
                <div class="form-group">
                    <label for="form_description">Description</label>
                    <textarea id="form_description" name="form_description" rows="3" 
                              class="form-control" placeholder="Enter form description"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="form_email">Notification Email</label>
                    <input type="email" id="form_email" name="form_email" 
                           class="form-control" placeholder="notifications@example.com">
                </div>
                
                <div class="form-group">
                    <label for="form_tags">Tags</label>
                    <input type="text" id="form_tags" name="form_tags" 
                           class="form-control" placeholder="comma, separated, tags">
                    <small>Separate multiple tags with commas</small>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="form_active" checked>
                        <span>Active (Enable form immediately)</span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Form
                    </button>
                    <a href="forms.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Forms List -->
    <div class="card">
        <div class="card-header">
            <h2>All Forms</h2>
            <div class="card-actions">
                <form method="GET" action="" class="search-form">
                    <input type="text" name="search" placeholder="Search forms..." 
                           value="<?= htmlspecialchars($search) ?>" class="search-input">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if ($search): ?>
                        <a href="forms.php" class="btn-clear">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($forms)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Forms Found</h3>
                    <p>Get started by creating your first form.</p>
                    <a href="?action=create" class="btn btn-primary">Create Form</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Form Name</th>
                            <th>Description</th>
                            <th width="100">Entries</th>
                            <th width="80">Status</th>
                            <th width="100">Created</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forms as $form): ?>
                            <tr>
                                <td>#<?= $form['form_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($form['form_name'] ?? 'Untitled') ?></strong>
                                    <?php if ($form['form_approval_enable']): ?>
                                        <span class="badge badge-info" title="Approval Enabled">
                                            <i class="fas fa-check-double"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($form['payment_enable_merchant']): ?>
                                        <span class="badge badge-warning" title="Payment Enabled">
                                            <i class="fas fa-credit-card"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars(substr($form['form_description'] ?? '', 0, 60)) ?>...</small>
                                </td>
                                <td>
                                    <span class="entry-count"><?= number_format($form['entry_count']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $form['form_active'] ? 'success' : 'secondary' ?>">
                                        <?= $form['form_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= date('M d, Y', strtotime($form['form_created_date'])) ?></small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?= APP_URL ?>/modules/entries/entries.php?form_id=<?= $form['form_id'] ?>" 
                                           class="btn-icon btn-sm" title="View Entries">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=edit&id=<?= $form['form_id'] ?>" 
                                           class="btn-icon btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=toggle&id=<?= $form['form_id'] ?>" 
                                           class="btn-icon btn-sm" 
                                           title="<?= $form['form_active'] ? 'Deactivate' : 'Activate' ?>">
                                            <i class="fas fa-<?= $form['form_active'] ? 'pause' : 'play' ?>"></i>
                                        </a>
                                        <a href="?action=delete&id=<?= $form['form_id'] ?>" 
                                           class="btn-icon btn-sm btn-danger" 
                                           data-confirm-delete="Are you sure you want to delete this form?"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 5px;
}

.page-header p {
    color: #666;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #4CAF50;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.checkbox-label input {
    margin-right: 10px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.btn {
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
    border: none;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.btn-primary {
    background-color: #4CAF50;
    color: white;
}

.btn-primary:hover {
    background-color: #388E3C;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.search-form {
    display: flex;
    gap: 5px;
}

.search-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    width: 250px;
}

.btn-search, .btn-clear {
    padding: 8px 12px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    color: #666;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    padding: 5px 10px;
    background: #f5f5f5;
    border-radius: 4px;
    text-decoration: none;
    color: #666;
    transition: all 0.3s;
}

.btn-icon:hover {
    background: #4CAF50;
    color: white;
}

.btn-icon.btn-danger:hover {
    background: #f44336;
    color: white;
}

.entry-count {
    font-weight: 600;
    color: #4CAF50;
}
</style>

<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/header.php';
?>
