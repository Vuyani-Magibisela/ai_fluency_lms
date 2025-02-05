<?php
// includes/header.php
require_once __DIR__ . '/../config/init.php';
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Fluency LMS</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboardStyle.css">
    <link rel="stylesheet" href="../css/moduleStyle.css">
    <link rel="stylesheet" href="../css/courses.css">
</head>
<body>
    <header class="main-header">
        <nav class="nav-container">
            <div class="logo">
           <?php if (isLoggedIn()): ?>     
            <a href="../public/dashboard.php">AI Fluency LMS</a>
            <?php else: ?>
            <a href="../public/index.php">AI Fluency LMS</a>
            <?php endif ?>

        </div>
            <div class="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <li><a href="../public/dashboard.php">Dashboard</a></li>
                    <li><a href="../public/module.php">Modules</a></li>
                    <li><a href="../public/course-list.php">Courses</a></li>
                    <li><a href="../public/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="../public/login.php">Login</a></li>
                    <li><a href="../public/register.php" class="cta-button">Get Started</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>