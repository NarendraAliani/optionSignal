<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Settings.php';

echo "<h2>Settings Debugger</h2>";

try {
    $db = Database::getInstance()->getConnection();
    echo "DB Connection: OK<br>";
    
    // Check raw DB content
    $stmt = $db->query("SELECT * FROM settings");
    $all = $stmt->fetchAll();
    echo "<h3>Raw Database Content:</h3>";
    echo "<pre>";
    print_r($all);
    echo "</pre>";
    
    // Check Settings Class
    $settings = new Settings();
    $marketKey = $settings->get('market_api_key');
    
    echo "<h3>Settings Class Retrieval:</h3>";
    echo "Market API Key: [" . htmlspecialchars($marketKey) . "]<br>";
    
    if (empty($marketKey)) {
        echo "<strong style='color:red'>ERROR: Key is empty!</strong>";
    } else {
        echo "<strong style='color:green'>SUCCESS: Key found.</strong>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
