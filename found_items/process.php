<?php
// Legacy entry point — processing handled in add.php, edit.php, view.php.
require_once dirname(__DIR__) . '/config/database.php';
header('Location: ' . BASE_URL . '/found_items/');
exit;