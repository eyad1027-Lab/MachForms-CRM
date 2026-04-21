<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Machform CRM' ?> - <?= APP_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= APP_URL ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="<?= APP_URL ?>/<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?= $bodyClass ?? '' ?>">
    <?php if ($auth->isLoggedIn()): ?>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><i class="fas fa-form"></i> <?= APP_NAME ?></h1>
                <span class="version">v<?= APP_VERSION ?></span>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="<?= APP_URL ?>/index.php" class="<?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/modules/forms/forms.php" class="<?= ($currentPage ?? '') === 'forms' ? 'active' : '' ?>">
                            <i class="fas fa-file-alt"></i>
                            <span>Forms</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/modules/entries/entries.php" class="<?= ($currentPage ?? '') === 'entries' ? 'active' : '' ?>">
                            <i class="fas fa-inbox"></i>
                            <span>Entries</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/modules/reports/reports.php" class="<?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/modules/approval/approvals.php" class="<?= ($currentPage ?? '') === 'approval' ? 'active' : '' ?>">
                            <i class="fas fa-check-circle"></i>
                            <span>Approvals</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/modules/emails/emails.php" class="<?= ($currentPage ?? '') === 'emails' ? 'active' : '' ?>">
                            <i class="fas fa-envelope"></i>
                            <span>Email Templates</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/modules/webhooks/webhooks.php" class="<?= ($currentPage ?? '') === 'webhooks' ? 'active' : '' ?>">
                            <i class="fas fa-webhook"></i>
                            <span>Webhooks</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_URL ?>/modules/settings/settings.php" class="<?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <span class="username"><?= htmlspecialchars($auth->getUsername()) ?></span>
                        <span class="role"><?= htmlspecialchars($auth->getRole()) ?></span>
                    </div>
                </div>
                <a href="<?= APP_URL ?>/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <?php if (isset($breadcrumbs)): ?>
                            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                                <?php if ($i < count($breadcrumbs) - 1): ?>
                                    <a href="<?= $crumb['url'] ?>"><?= htmlspecialchars($crumb['name']) ?></a>
                                    <span class="separator">/</span>
                                <?php else: ?>
                                    <span class="current"><?= htmlspecialchars($crumb['name']) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="search-box">
                        <input type="text" placeholder="Search..." id="globalSearch">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="notifications">
                        <button class="notification-btn">
                            <i class="fas fa-bell"></i>
                            <span class="badge">3</span>
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="page-content">
                <?php if (isset($successMessage)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($successMessage) ?>
                        <button class="close-alert">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errorMessage)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($errorMessage) ?>
                        <button class="close-alert">&times;</button>
                    </div>
                <?php endif; ?>
                
                <?= $content ?? '' ?>
            </div>
        </main>
    <?php else: ?>
        <?= $content ?? '' ?>
    <?php endif; ?>
    
    <!-- Scripts -->
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="<?= APP_URL ?>/<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
