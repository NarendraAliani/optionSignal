<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/MarketAPI.php';
require_once __DIR__ . '/../src/SignalEngine.php'; // Ensure we load this to access its helper methods if they were public, or we'll replicate logic here

echo "<h2>Scanner Debugger</h2>";

// 1. Init
$settings = new Settings();
$db = Database::getInstance()->getConnection();
$apiKey = $settings->get('market_api_key');
$token = $settings->get('access_token');

echo "API Key: " . substr($apiKey, 0, 5) . "...<br>";
echo "Token: " . substr($token, 0, 10) . "...<br>";

$api = new MarketAPI($apiKey); // Access token handling is internal or relies on Settings
// Force load settings in API if needed/check API constructor
// Actually MarketAPI constructor in previous code was: new MarketAPI(); (it uses Settings internally)
// Let's verify how MarketAPI is instantiated in scan.php
// scan.php: $marketApi = new MarketAPI($apiKey, $apiUrl); -- Wait, let's check MarketAPI definition again.

// RE-READING MarketAPI.php to be sure about constructor
// Code snippet from earlier view_file:
// class MarketAPI { ... public function __construct($apiKey = null, $baseUrl = null) { ... } }
// So passing it is fine.

$marketApi = new MarketAPI(); // It should auto-load from Settings
$engine = new SignalEngine($marketApi);

// 2. Mock Params
$timeframe = '15min'; // User said 15min
// Backtest Date Logic (Yesterday)
$date = new DateTime();
$day = $date->format('N');
$sub = ($day == 1) ? 3 : 1; 
$date->sub(new DateInterval("P{$sub}D"));
$fromDate = $date->format('Y-m-d'); // e.g. 2023-10-27
$toDate = $date->format('Y-m-d') . ' 15:30';

echo "<h3>Backtest Params</h3>";
echo "Date: $fromDate<br>";
echo "Timeframe: $timeframe<br>";

// 3. Get Universe
$stmt = $db->query("SELECT * FROM stocks WHERE is_active = 1");
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Universe</h3>";
if (empty($stocks)) {
    echo "<span style='color:red'>Stocks table is empty!</span><br>";
    exit;
}

foreach ($stocks as $stock) {
    echo "<strong>Checking: {$stock['symbol']} (Token: {$stock['token']})</strong><br>";

    // 4. Get Quote
    // Note: getMarketQuote likely needs exchange? Usually NSE.
    // Let's check getMarketQuote signature from previous views.
    // Assuming signatures.
    $cmpData = $marketApi->getMarketQuote($stock['token']); 
    // Wait, getMarketQuote usually returns array.
    
    // Debug output of CMP
    if (!$cmpData) {
        echo "<span style='color:red'>Failed to fetch CMP for {$stock['symbol']}</span><br>";
        continue;
    }
    
    // Assuming structure: $cmpData['ltp'] or similar. 
    // Let's output raw first
    echo "CMP Raw: " . json_encode($cmpData) . "<br>";
    
    $ltp = $cmpData['data']['ltp'] ?? 0; // Adjust based on actual structure
    echo "LTP: $ltp<br>";
    
    if ($ltp <= 0) continue;

    // 5. Calculate Strikes
    $step = 50; // Nifty default
    if ($stock['symbol'] == 'BANKNIFTY') $step = 100;
    
    $center = round($ltp / $step) * $step;
    echo "ATM Strike: $center<br>";
    
    // Check just one strike for debug
    $testStrike = $center;
    $type = 'CE';
    
    echo "Looking up Token for: {$stock['symbol']} $testStrike $type...<br>";
    
    // Manual Query to check what getOptionToken does
    $sql = "SELECT symbol, token, expiry_date FROM option_contracts 
            WHERE name = ? AND option_type = ? AND strike_price = ? 
            AND expiry_date >= CURDATE() 
            ORDER BY expiry_date ASC LIMIT 1";
    $stmtOpt = $db->prepare($sql);
    $stmtOpt->execute([$stock['symbol'], $type, $testStrike]);
    $opt = $stmtOpt->fetch(PDO::FETCH_ASSOC);
    
    if ($opt) {
        echo "<span style='color:green'>Found Contract! Token: {$opt['token']}, Expiry: {$opt['expiry_date']}</span><br>";
        
        // 6. Fetch OHLC
        echo "Fetching Candles ($fromDate)...<br>";
        $candles = $marketApi->getOHLC($opt['token'], $timeframe, $fromDate, $toDate);
        
        if ($candles) {
            echo "Candles Found: " . count($candles) . "<br>";
            $last = end($candles);
            $prev = prev($candles);
            echo "Last Close: {$last['close']}, Prev Close: {$prev['close']}<br>";
            
            // 7. Check Logic
            if ($last['close'] >= (2 * $prev['close'])) {
                echo "<span style='color:green; font-weight:bold'>SIGNAL MATCH!</span><br>";
            } else {
                echo "Signal Condition Fail (Need 100% gain)<br>";
            }
        } else {
            echo "<span style='color:red'>No Candle Data Returned via API</span><br>";
            // Debug API Error?
        }
        
    } else {
        echo "<span style='color:red'>Contract Lookup Failed in DB!</span> check if import_master.php ran successfully.<br>";
        // Dump similar
        $check = $db->query("SELECT * FROM option_contracts LIMIT 5")->fetchAll();
        echo "First 5 rows in DB: <pre>" . print_r($check, true) . "</pre>";
    }
    
    echo "<hr>";
}
?>
