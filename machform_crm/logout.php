<?php
/**
 * Machform CRM - Logout Handler
 */

require_once __DIR__ . '/config/database.php';
require_once INCLUDES_PATH . '/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;
