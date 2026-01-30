<?php
// debug_backtest.php
require_once __DIR__ . '/../src/MarketAPI.php';
require_once __DIR__ . '/../src/SignalEngine.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Starting Debug Scan...</h3>";

try {
    $api = new MarketAPI();
    $db = Database::getInstance()->getConnection();
    
    // Check Stocks
    $stmt = $db->query("SELECT count(*) FROM stocks WHERE is_active = 1");
    $stockCount = $stmt->fetchColumn();
    echo "Active Stocks in DB: $stockCount<br>";
    
    if ($stockCount == 0) {
        die("No active stocks! Run import.");
    }

    // Check Options
    $stmt = $db->query("SELECT count(*) FROM option_contracts WHERE expiry_date >= CURDATE()");
    $optCount = $stmt->fetchColumn();
    echo "Active Options in DB: $optCount<br>";

    // Manual Scan Setup
    $stockSymbol = 'TCS'; // Test with one liquid stock
    $stmt = $db->prepare("SELECT * FROM stocks WHERE symbol = ?");
    $stmt->execute([$stockSymbol]);
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stock) {
        // Fallback to any stock
        $stmt = $db->query("SELECT * FROM stocks WHERE is_active = 1 LIMIT 1");
        $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    echo "Testing with Stock: {$stock['symbol']} (ID: {$stock['id']})<br>";
    
    // 1. Get CMP
    // Since this is backtest debug, let's try to mock or just fetch LIVE CMP for strike selection
    // But for Backtest we usually need Historical CMP to pick strikes... 
    // Wait! SignalEngine uses LIVE CMP ($this->api->getMarketQuote) even in backtest mode?
    // ERROR FOUND: SignalEngine currently calls getMarketQuote() which returns LIVE price.
    // If we backtest for yesterday, we should ideally use Yesterday's Close to pick strikes, 
    // OR just use valid strikes.
    // However, getMarketQuote might fail if market is closed or returns last traded price.
    // Let's see what getMarketQuote returns.
    
    $cmp = $api->getMarketQuote($stock['symbol']);
    echo "Live CMP fetched: " . json_encode($cmp) . "<br>";
    
    if (!$cmp) {
        die("Could not fetch CMP. Token issue?");
    }
    
    // 2. Get Strikes
    // Reflected method helper or just duplicate logic
    $range = $cmp * 0.10;
    $min = $cmp - $range;
    $max = $cmp + $range;
    
    echo "Searching strikes in range: $min - $max<br>";
    
    $sql = "SELECT symbol as token, strike_price, option_type, expiry_date 
            FROM option_contracts 
            WHERE stock_id = ? 
            AND expiry_date >= CURDATE() 
            AND strike_price BETWEEN ? AND ? 
            ORDER BY expiry_date ASC, ABS(strike_price - ?) ASC LIMIT 5";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$stock['id'], $min, $max, $cmp]);
    $strikes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($strikes) . " strikes.<br>";
    
    if (count($strikes) == 0) {
        die("No strikes found. Check option_contracts table or price range.");
    }
    
    foreach ($strikes as $s) {
        echo "Strike: {$s['strike_price']} {$s['option_type']} ({$s['expiry_date']}) - Token: {$s['token']}<br>";
        
        // 3. Fetch History
        $fromDate = date('Y-m-d 09:15', strtotime('-1 day'));
        $toDate = date('Y-m-d 15:30', strtotime('-1 day'));
        $timeframe = '5min';
        
        echo "Fetching candles for {$s['token']} from $fromDate to $toDate...<br>";
        
        $candles = $api->getOHLC($s['token'], $timeframe, $fromDate, $toDate);
        echo "Candles returned: " . count($candles) . "<br>";
        
        if (count($candles) > 0) {
            echo "First Candle: " . json_encode($candles[0]) . "<br>";
            echo "Last Candle: " . json_encode(end($candles)) . "<br>";
            
            // Check Logic
            foreach ($candles as $i => $c) {
                if ($i == 0) continue;
                $prev = $candles[$i-1];
                $curr = $c;
                
                $diff = (($curr['close'] - $prev['close']) / $prev['close']) * 100;
                
                if ($diff > 5) { // Show anything > 5%
                    echo "<b>[POTENTIAL SIGNAL]</b> Time: {$curr['time']} Change: $diff% <br>";
                }
                
                if ($curr['close'] >= 2 * $prev['close']) {
                     echo "<b style='color:green'>[MATCHES STRICT CRITERIA]</b> Time: {$curr['time']} DOUBLED! <br>";
                }
            }
        } else {
            echo "No candles. Check API response or Market Data subscription.<br>";
        }
        echo "<hr>";
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
