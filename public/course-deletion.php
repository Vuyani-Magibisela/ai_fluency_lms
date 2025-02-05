<?php
require_once '../config/init.php';
require_once '../classes/Auth.php';
require_once '../classes/Course.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: courses.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: courses.php');
    exit();
}

$courseObj = new Course(Database::getInstance()->getConnection());

try {
    if ($courseObj->deleteCourse($_GET['id'])) {
        $_SESSION['success_message'] = 'Course deleted successfully';
    } else {
        $_SESSION['error_message'] = 'Failed to delete course';
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
}

header('Location: courses.php');
exit();