<?php
// config/session.php

class SessionManager {
    public static function start() {
        // Check if session is already active and close it if necessary
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Configure session settings before starting the session
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            if (!headers_sent()) {
                ini_set('session.use_only_cookies', 1);
                ini_set('session.use_strict_mode', 1);
                ini_set('session.cookie_httponly', 1);
                
                // Set session cookie parameters
                session_set_cookie_params([
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => !empty($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            // Start the session
            session_start();
        }

        // Initialize session if new
        if (!isset($_SESSION['initialized'])) {
            session_regenerate_id(true);
            $_SESSION['initialized'] = true;
            $_SESSION['last_regeneration'] = time();
            $_SESSION['created'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }

        // Security checks
        self::performSecurityChecks();

        // Regenerate session ID periodically to prevent fixation
        if (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
            self::regenerateSession();
        }
    }

    private static function performSecurityChecks() {
        // Verify session hasn't expired
        if (isset($_SESSION['created']) && (time() - $_SESSION['created'] > 86400)) { // 24 hours
            self::destroy();
            return false;
        }

        // Verify user agent hasn't changed
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            self::destroy();
            return false;
        }

        // Verify IP hasn't changed (optional, might cause issues with mobile users)
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            self::destroy();
            return false;
        }

        return true;
    }

    public static function regenerateSession() {
        // Back up session data
        $sessionData = $_SESSION;
        
        // Destroy old session
        session_destroy();
        
        // Create new session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Restore session data
        $_SESSION = $sessionData;
        
        // Update regeneration time
        $_SESSION['last_regeneration'] = time();
        
        // Generate new session ID
        session_regenerate_id(true);
    }

    public static function destroy() {
        // Unset all session variables
        $_SESSION = [];

        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => !empty($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }

        // Destroy the session
        session_destroy();
    }

    public static function setFlash($key, $message) {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
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

    public static function hasFlash($key) {
        return isset($_SESSION['flash_messages'][$key]);
    }
}