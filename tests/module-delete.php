<?php
define('PROJECT_ROOT', dirname(dirname(__FILE__)));
require_once PROJECT_ROOT . '/config/init.php';
require_once PROJECT_ROOT . '/classes/Auth.php';
require_once PROJECT_ROOT . '/classes/Module.php';

// Only define header function if it doesn't exist and we're in CLI
if (php_sapi_name() === 'cli' && !function_exists('header')) {
    function header($str) {
        echo "Header would redirect to: $str\n";
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

function testModuleDeletion($moduleId) {
    try {
        $moduleObj = new Module(Database::getInstance()->getConnection());
        echo "Attempting to delete module ID: $moduleId\n";
        
        $result = $moduleObj->deleteModule($moduleId);
        
        if ($result) {
            echo "✓ Successfully deleted module\n";
        } else {
            echo "✗ Failed to delete module\n";
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

// Test with a specific module ID
$moduleId = isset($argv[1]) ? $argv[1] : 1; // Default to ID 1 if not provided
testModuleDeletion($moduleId);