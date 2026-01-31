<?php
require_once __DIR__ . '/../src/Database.php';

// IMPORTANT: In a real Scenario, would need to scrape/download the OpenAPIScripMaster.json
// For now, I will add placeholder tokens. YOU MUST REPLACE THESE WITH REAL TOKENS for live usage.
// NIFTY 50 Token usually 99926000 (Indices) or similar.

$db = Database::getInstance()->getConnection();

// Add 'exchange_token' column if not exists
try {
    $db->query("ALTER TABLE stocks ADD COLUMN exchange_token VARCHAR(20) DEFAULT NULL");
    echo "Added exchange_token column.\n";
} catch (Exception $e) {
    // Column likely exists (or ignore if first run)
}

$stocks = [
    ['symbol' => 'NIFTY', 'name' => 'Nifty 50', 'lot_size' => 50, 'token' => '99926000'], 
    ['symbol' => 'BANKNIFTY', 'name' => 'Nifty Bank', 'lot_size' => 15, 'token' => '99926009'],
    ['symbol' => 'RELIANCE', 'name' => 'Reliance Industries', 'lot_size' => 250, 'token' => '2885'],
    ['symbol' => 'TCS', 'name' => 'Tata Consultancy Services', 'lot_size' => 175, 'token' => '11536'],
    ['symbol' => 'INFY', 'name' => 'Infosys', 'lot_size' => 400, 'token' => '1594']
];

echo "Updating Stock Tokens...\n";
$stmt = $db->prepare("INSERT INTO stocks (symbol, name, lot_size, exchange_token) VALUES (:symbol, :name, :lot_size, :token) ON DUPLICATE KEY UPDATE exchange_token = :token");

foreach ($stocks as $stock) {
    $stmt->execute(['symbol' => $stock['symbol'], 'name' => $stock['name'], 'lot_size' => $stock['lot_size'], 'token' => $stock['token']]);
    echo "Updated: {$stock['symbol']}\n";
}
