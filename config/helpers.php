<?php
// config/helpers.php

// Authentication helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

// Authorization helpers
function requireLogin() {
    if (!isLoggedIn()) {
        SessionManager::setFlash('error', 'Please log in to access this page');
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        SessionManager::setFlash('error', 'Access denied. Admin privileges required.');
        header('Location: /dashboard.php');
        exit();
    }
}

// Security helpers
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// URL and Path helpers
function redirectTo($path) {
    header("Location: $path");
    exit();
}

function getBasePath() {
    return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
}

function assetUrl($path) {
    return getBasePath() . '/assets/' . ltrim($path, '/');
}

// Message helpers
function displayAlert($type = 'info') {
    $message = SessionManager::getFlash($type);
    if ($message) {
        return "<div class='alert alert-{$type}'>" . sanitizeInput($message) . "</div>";
    }
    return '';
}

// Validation helpers
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password) 
        && preg_match('/[a-z]/', $password) 
        && preg_match('/[0-9]/', $password);
}

// File handling helpers
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function generateUniqueFileName($originalName, $prefix = '') {
    $extension = getFileExtension($originalName);
    return $prefix . uniqid() . '_' . time() . '.' . $extension;
}

// Date and Time helpers
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}