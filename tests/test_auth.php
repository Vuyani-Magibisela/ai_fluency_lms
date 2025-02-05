<?php
// test_auth.php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/init.php';
require_once ROOT_PATH . '/classes/Auth.php';

function testAuth() {
    try {
        $auth = new Auth();
        
        // Test 1: Register new user
        echo "Testing registration...\n";
        $testEmail = "test_" . time() . "@example.com";
        $testUsername = "testuser_" . time();
        $testPassword = "Test123!@#";
        
        try {
            $auth->register($testUsername, $testEmail, $testPassword);
            echo "✓ Registration successful\n";
        } catch (Exception $e) {
            echo "✗ Registration failed: " . $e->getMessage() . "\n";
            return;
        }
        
        // Test 2: Login with new credentials
        echo "\nTesting login...\n";
        try {
            $loginResult = $auth->login($testEmail, $testPassword);
            if ($loginResult) {
                echo "✓ Login successful\n";
                echo "✓ Session variables set:\n";
                echo "  - user_id: " . ($_SESSION['user_id'] ?? 'not set') . "\n";
                echo "  - username: " . ($_SESSION['username'] ?? 'not set') . "\n";
                echo "  - email: " . ($_SESSION['email'] ?? 'not set') . "\n";
                echo "  - role: " . ($_SESSION['role'] ?? 'not set') . "\n";
            } else {
                echo "✗ Login failed\n";
            }
        } catch (Exception $e) {
            echo "✗ Login failed: " . $e->getMessage() . "\n";
        }
        
        // Test 3: Check admin status
        echo "\nTesting role checks...\n";
        echo "Is Admin: " . (Auth::isAdmin() ? "Yes" : "No") . "\n";
        echo "Current Role: " . Auth::getRole() . "\n";
        
        // Prevent automatic redirect during testing
        echo "\nTesting logout (preventing redirect)...\n";
        try {
            // Store the original header function
            $header = function_exists('header') ? 'header' : null;
            
            // Temporarily override header function
            if (!function_exists('header')) {
                function header($str) {
                    echo "Header would redirect to: $str\n";
                }
            }
            
            $auth->logout();
            echo "✓ Logout successful\n";
        } catch (Exception $e) {
            echo "✗ Logout failed: " . $e->getMessage() . "\n";
        }
        
    } catch (Exception $e) {
        echo "Test failed with error: " . $e->getMessage() . "\n";
    }
}

// Run the tests
testAuth();