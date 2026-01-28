<?php
require_once __DIR__ . '/../src/Database.php';

$db = Database::getInstance()->getConnection();

$stocks = [
    ['symbol' => 'NIFTY', 'name' => 'Nifty 50', 'lot_size' => 50],
    ['symbol' => 'BANKNIFTY', 'name' => 'Nifty Bank', 'lot_size' => 15],
    ['symbol' => 'RELIANCE', 'name' => 'Reliance Industries', 'lot_size' => 250],
    ['symbol' => 'TCS', 'name' => 'Tata Consultancy Services', 'lot_size' => 175],
    ['symbol' => 'INFY', 'name' => 'Infosys', 'lot_size' => 400]
];

echo "Seeding Stocks...\n";
$stmt = $db->prepare("INSERT IGNORE INTO stocks (symbol, name, lot_size) VALUES (:symbol, :name, :lot_size)");

foreach ($stocks as $stock) {
    $stmt->execute($stock);
    echo "Inserted/Ignored: {$stock['symbol']}\n";
}
echo "Done.\n";
