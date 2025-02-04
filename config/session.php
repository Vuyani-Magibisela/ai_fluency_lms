<?php
// config/session.php

// Session configuration
class SessionManager {
    public static function start() {
        // Set secure session parameters
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['last_regeneration'])) {
            self::regenerateSession();
        } else if (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
            self::regenerateSession();
        }
    }

    public static function regenerateSession() {
        // Regenerate session ID and update timestamp
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    public static function destroy() {
        // Unset all session variables
        $_SESSION = [];

        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy the session
        session_destroy();
    }

    public static function setFlash($key, $message) {
        $_SESSION['flash_messages'][$key] = $message;
    }

    public static function getFlash($key) {
        if (isset($_SESSION['flash_messages'][$key])) {
            $message = $_SESSION['flash_messages'][$key];
            unset($_SESSION['flash_messages'][$key]);
            return $message;
        }
        return null;
    }
}