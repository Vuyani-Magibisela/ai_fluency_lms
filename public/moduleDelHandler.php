<?php
require_once '../config/init.php';
require_once '../classes/Auth.php';
require_once '../classes/Module.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: modules.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: modules.php');
    exit();
}

$moduleObj = new Module(Database::getInstance()->getConnection());

try {
    if ($moduleObj->deleteModule($_GET['id'])) {
        $_SESSION['success_message'] = 'Module deleted successfully';
    } else {
        $_SESSION['error_message'] = 'Failed to delete module';
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
}

header('Location: modules.php');
exit();