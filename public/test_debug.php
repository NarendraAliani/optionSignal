<?php
// public/test_debug.php
require_once __DIR__ . '/../src/MarketAPI.php';
require_once __DIR__ . '/../src/Database.php';

echo "<h2>🔍 API Connectivity & Data Proof</h2>";

try {
    // 1. Database Connection
    $db = Database::getInstance()->getConnection();
    echo "✅ Database Connection: OK<br>";
    
    // 2. Fetch one stock
    $stmt = $db->query("SELECT * FROM stocks WHERE is_active = 1 LIMIT 1");
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stock) {
        die("❌ No active stocks found in DB. Please run Import first.");
    }
    
    echo "<h3>Testing with Stock: {$stock['name']}</h3>";
    echo "<ul>";
    echo "<li>Symbol: <b>{$stock['symbol']}</b></li>";
    echo "<li>Token: <b>{$stock['token']}</b></li>";
    echo "</ul>";
    
    // 3. Initialize API
    $api = new MarketAPI();
    echo "✅ API Initialized<br>";
    
    echo "<hr>";
    
    // TEST A: Current Logic (Using Symbol)
    echo "<h4>Test A: Fetching Price using SYMBOL ('{$stock['symbol']}')</h4>";
    $responseA = $api->getMarketQuote($stock['symbol']);
    echo "Result: ";
    if ($responseA) {
        echo "<b style='color:green'>SUCCESS</b>: " . json_encode($responseA);
    } else {
        echo "<b style='color:red'>FAILED</b> (Returned null/false)";
    }
    
    echo "<hr>";
    
    // TEST B: Correct Logic (Using Token)
    echo "<h4>Test B: Fetching Price using TOKEN ('{$stock['token']}')</h4>";
    $responseB = $api->getMarketQuote($stock['token']);
    echo "Result: ";
    if ($responseB) {
        echo "<b style='color:green'>SUCCESS</b>: " . json_encode($responseB);
        echo "<br><i>Price Found: " . $responseB . "</i>";
    } else {
        echo "<b style='color:red'>FAILED</b> (Returned null/false)";
    }
    
    echo "<hr>";
    
    // Conclusion
    if (!$responseA && $responseB) {
        echo "<h3>💡 DIAGNOSIS:</h3>";
        echo "<p style='color:blue; font-size:1.2em;'>The Scanner was sending the <b>NAME</b> instead of the <b>TOKEN</b>. The server rejected the Name, but accepts the Token. I will apply this fix now.</p>";
    } elseif ($responseA && $responseB) {
         echo "Both methods worked? Then logic issue is elsewhere.";
    } else {
         echo "<h3>⚠️ CRITICAL:</h3> Both failed. The Token itself might be wrong or API is down.";
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
