<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Settings.php';

echo "<h2>Write Debugger</h2>";

// Test 1: Direct DB Write
try {
    echo "<h3>Test 1: Direct SQL Write</h3>";
    $db = Database::getInstance()->getConnection();
    
    $key = 'test_key_' . rand(100,999);
    $val = 'test_val';
    
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    if ($stmt->execute([$key, $val])) {
        echo "<strong style='color:green'>SUCCESS: Direct SQL Write worked.</strong> Key: $key<br>";
    } else {
        echo "<strong style='color:red'>FAIL: Direct SQL Write failed.</strong><br>";
        print_r($stmt->errorInfo());
    }
} catch (Exception $e) {
    echo "<strong style='color:red'>EXCEPTION (Test 1): " . $e->getMessage() . "</strong><br>";
}

// Test 2: Settings Class Write (Encryption)
try {
    echo "<h3>Test 2: Settings Class Write</h3>";
    $settings = new Settings();
    
    // Check Encryption Key
    echo "Checking Encryption Constants...<br>";
    if (defined('ENCRYPTION_KEY')) {
        echo "ENCRYPTION_KEY is defined (Length: " . strlen(ENCRYPTION_KEY) . ")<br>";
    } else {
        echo "<strong style='color:red'>CRITICAL: ENCRYPTION_KEY is NOT defined!</strong><br>";
    }
    
    $key2 = 'test_class_key';
    $val2 = 'secret_value';
    
    if ($settings->set($key2, $val2)) {
        echo "<strong style='color:green'>SUCCESS: Settings->set() returned true.</strong><br>";
        
        // Read back
        $read = $settings->get($key2);
        if ($read === $val2) {
            echo "<strong style='color:green'>SUCCESS: Read back matches written value.</strong><br>";
        } else {
            echo "<strong style='color:red'>FAIL: Read back mismatch. Got: [$read]</strong><br>";
        }
        
    } else {
        echo "<strong style='color:red'>FAIL: Settings->set() returned false.</strong><br>";
    }
    
} catch (Exception $e) {
    echo "<strong style='color:red'>EXCEPTION (Test 2): " . $e->getMessage() . "</strong><br>";
}
?>
