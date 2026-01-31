<?php
require_once __DIR__ . '/../src/Database.php';

$pdo = Database::getInstance()->getConnection();


// Buffer output
ob_start();

echo "DEBUGGING SIGNAL LOGIC\n----------------------\n";

// 1. Check Stocks
echo "Checking Stocks:\n";
$stmt = $pdo->query("SELECT id, symbol, name, token FROM stocks");
$stocks = $stmt->fetchAll();
foreach ($stocks as $s) {
    echo "ID: {$s['id']}, Symbol: {$s['symbol']}, Name: {$s['name']}, Token: {$s['token']}\n";
    
    // 2. Check Contracts for this Stock
    echo "  -> Checking Contracts for StockID {$s['id']}...\n";
    $c = $pdo->query("SELECT COUNT(*) FROM option_contracts WHERE stock_id = {$s['id']}")->fetchColumn();
    echo "     Count: $c\n";
    
    if ($c > 0) {
        $minMax = $pdo->query("SELECT MIN(strike_price) as min_s, MAX(strike_price) as max_s FROM option_contracts WHERE stock_id = {$s['id']}")->fetch();
        echo "     Strike Range (DB): {$minMax['min_s']} - {$minMax['max_s']}\n";
        
        // Check if strikes are scaled by 100 (e.g., 2500000 instead of 25000)
        if ($minMax['min_s'] > 200000) {
             echo "     ⚠️ DETECTED HIGH STRIKE PRICES! Likely x100 scaling issue.\n";
        }

        // Check specific query params from screenshot
        // Nifty (Token 26000) CMP 25320.65
        // Range 22788 - 27852
        if ($s['symbol'] == 'NIFTY' || $s['name'] == 'NIFTY' || $s['symbol'] == 'Nifty 50') {
            $min = 22788;
            $max = 27852;
            echo "     Simulating Query (Range $min - $max, >= Today):\n";
            $sql = "SELECT COUNT(*) FROM option_contracts 
                    WHERE stock_id = {$s['id']} 
                    AND expiry_date >= CURDATE()
                    AND strike_price BETWEEN $min AND $max";
            $matches = $pdo->query($sql)->fetchColumn();
            echo "     Matches Found: $matches\n";
            
            if ($matches == 0) {
                 echo "     !! NO MATCHES in range.\n";
            }
        }
    }
}

$output = ob_get_clean();
file_put_contents(__DIR__ . '/debug_log.txt', $output);
echo "Log written to debug_log.txt";

