<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Settings.php';

try {
    echo "Testing Database Connection...\n";
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Database Connection Successful!\n\n";
    
    echo "Checking Settings Table...\n";
    $settings = new Settings();
    $all = $settings->getAll();
    
    $keysToCheck = ['market_api_key', 'hist_api_key', 'access_token'];
    foreach ($keysToCheck as $key) {
        if (!empty($all[$key])) {
            $val = $all[$key];
            if ($key === 'access_token') {
                // Show expiry if possible (JWT) or just length
                echo "✅ $key is SET (Length: " . strlen($val) . ")\n";
            } else {
                echo "✅ $key is SET (Value: " . substr($val, 0, 4) . "...)\n";
            }
        } else {
            echo "❌ $key is MISSING or EMPTY\n";
        }
    }
    
    // Check Option Contracts Count
    $count = $pdo->query("SELECT COUNT(*) FROM option_contracts")->fetchColumn();
    echo "\nExisting Option Contracts: $count\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
