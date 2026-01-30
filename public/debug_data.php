<?php
require_once __DIR__ . '/../src/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check Stocks
    $stmt = $db->query("SELECT COUNT(*) as count FROM stocks");
    $stockCount = $stmt->fetchColumn();
    
    // Check Option Contracts
    $stmt = $db->query("SELECT COUNT(*) as count FROM option_contracts");
    $optCount = $stmt->fetchColumn();
    
    // Check a sample option
    $stmt = $db->query("SELECT * FROM option_contracts LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Database Status</h3>";
    echo "Stocks Active: <strong>$stockCount</strong><br>";
    echo "Option Contracts: <strong>$optCount</strong><br>";
    
    if ($sample) {
        echo "<hr>Sample Contract:<br><pre>" . print_r($sample, true) . "</pre>";
    } else {
        echo "<hr><strong style='color:red'>WARNING: Option Contracts table is empty! Run import_master.php</strong>";
    }

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}
