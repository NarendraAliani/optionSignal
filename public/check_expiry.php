<?php
require_once __DIR__ . '/../src/Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    echo "Checking Expiry Dates...\n";
    
    $stmt = $pdo->query("SELECT expiry_date, COUNT(*) as count FROM option_contracts GROUP BY expiry_date ORDER BY expiry_date");
    $rows = $stmt->fetchAll();
    
    if (empty($rows)) {
        echo "No contracts found.\n";
    } else {
        foreach ($rows as $row) {
            echo "Expiry: " . $row['expiry_date'] . " | Count: " . $row['count'] . "\n";
        }
    }
    
    // Check scanner query logic simulation
    echo "\nSimulating Scanner Query (CURDATE = " . date('Y-m-d') . "):\n";
    $count = $pdo->query("SELECT COUNT(*) FROM option_contracts WHERE expiry_date >= CURDATE()")->fetchColumn();
    echo "Contracts valid for TODAY: $count\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
