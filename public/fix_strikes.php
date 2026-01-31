<?php
require_once __DIR__ . '/../src/Database.php';
try {
    $pdo = Database::getInstance()->getConnection();
    echo "Fixing Strike Prices...\n";
    // Fix NIFTY/BANKNIFTY strikes which are > 200000
    // NIFTY is ~25000, BANKNIFTY ~48000. 
    // If strike is > 200000, it's definitely scaled.
    $rows = $pdo->exec("UPDATE option_contracts SET strike_price = strike_price / 100 WHERE strike_price > 200000");
    echo "✅ Updated $rows rows.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
