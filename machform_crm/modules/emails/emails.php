<?php
require_once __DIR__ . '/../../config/database.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Auth.php';

$auth = new Auth();
$auth->requireLogin();

$pageTitle = ucfirst($module);
$currentPage = $module;

ob_start();
?>
<div class="page-header">
    <h1><i class="fas fa-cog"></i> <?= ucfirst($module) ?></h1>
</div>
<div class="card">
    <div class="card-body">
        <p><?= ucfirst($module) ?> module - Coming soon.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
include TEMPLATES_PATH . '/header.php';
?>
