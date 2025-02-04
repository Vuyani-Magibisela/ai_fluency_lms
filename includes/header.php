<?php
// includes/header.php
require_once __DIR__ . '/../config/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Fluency LMS</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <header class="main-header">
        <nav class="nav-container">
            <div class="logo">AI Fluency LMS</div>
            <div class="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <li><a href="/dashboard.php">Dashboard</a></li>
                    <li><a href="/profile.php">Profile</a></li>
                    <li><a href="/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/login.php">Login</a></li>
                    <li><a href="/register.php" class="cta-button">Get Started</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>