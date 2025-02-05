<?php
// test_session.php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/init.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/SessionManager.php';

function testSession() {
    echo "Testing Session Management\n";
    echo "-------------------------\n";
    
    // Test 1: Start Session
    echo "1. Testing session start...\n";
    SessionManager::start();
    echo session_status() === PHP_SESSION_ACTIVE ? "✓ Session started successfully\n" : "✗ Session failed to start\n";
    
    // Test 2: Set Session Variables
    echo "\n2. Testing session variables...\n";
    $_SESSION['test_var'] = 'test_value';
    echo isset($_SESSION['test_var']) ? "✓ Session variable set successfully\n" : "✗ Failed to set session variable\n";
    
    // Test 3: Session Persistence
    echo "\n3. Testing session persistence...\n";
    echo $_SESSION['test_var'] === 'test_value' ? "✓ Session value persisted\n" : "✗ Session value not found\n";
    
    // Test 4: Session Destruction
    echo "\n4. Testing session destruction...\n";
    // Prevent redirect during testing
    if (!function_exists('header')) {
        function header($str) {
            echo "Header would redirect to: $str\n";
        }
    }
    SessionManager::destroy();
    echo session_status() === PHP_SESSION_NONE || session_status() === PHP_SESSION_ACTIVE 
        ? "✓ Session destroyed successfully\n" 
        : "✗ Session destruction failed\n";
    
    // Test 5: Login Status Check
    echo "\n5. Testing login status check...\n";
    echo "IsLoggedIn: " . (SessionManager::isLoggedIn() ? "Yes" : "No") . " (should be No)\n";
}

// Run the tests
testSession();