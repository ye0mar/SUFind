<?php
// Legacy entry point — all processing is handled inside report.php, edit.php, view.php.
require_once dirname(__DIR__) . '/config/database.php';
header('Location: ' . BASE_URL . '/lost_items/');
exit;