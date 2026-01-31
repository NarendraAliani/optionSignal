<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Settings.php';

echo "<h1>API Connectivity Test (Raw Debug)</h1>";

try {
    $settings = new Settings();
    $marketKey = $settings->get('market_api_key');
    $histKey = $settings->get('hist_api_key');
    $token = $settings->get('access_token');
    
    echo "<h3>1. Credentials Check</h3>";
    echo "Market API Key: " . ($marketKey ? "✅ Set (".strlen($marketKey)." chars)" : "❌ MISSING") . "<br>";
    echo "Historical API Key: " . ($histKey ? "✅ Set (".strlen($histKey)." chars)" : "❌ MISSING") . "<br>";
    echo "Access Token: " . ($token ? "✅ Set (".strlen($token)." chars)" : "❌ MISSING") . "<br>";

    if (!$marketKey || !$histKey || !$token) {
        die("<br>❌ <strong>Cannot test API: Missing credentials.</strong> Please go to Settings and configure them.");
    }
    
    // Test Historical Data (NIFTY 50 - Token 26000)
    // Note: Use correct token. Nifty 50 is often 99926000 (Index) or similar.
    // In DB we usually store as 26000 ?? In Angel One Index tokens are different. 
    // Let's try to lookup NIFTY in DB first to be sure what we are using.
    
    $pdo = Database::getInstance()->getConnection();
    // Assuming 26000 from the screenshot logic
    $tokenToTest = "26000"; 
    $dbToken = $pdo->query("SELECT token FROM stocks WHERE symbol='NIFTY'")->fetchColumn();
    if ($dbToken) $tokenToTest = $dbToken;
    
    echo "<h3>2. Testing Historical Data API</h3>";
    echo "Target Token: $tokenToTest (NIFTY)<br>";
    
    $url = "https://apiconnect.angelone.in/rest/secure/angelbroking/historical/v1/getCandleData";
    
    // Request last 1 day
    $body = json_encode([
        "exchange" => "NSE", // Try NSE for Index, or NFO? Indices are usually NSE.
        "symboltoken" => $tokenToTest,
        "interval" => "ONE_DAY",
        "fromdate" => date('Y-m-d H:i', strtotime("-5 days")),
        "todate" => date('Y-m-d H:i')
    ]); 
    
    $headers = [
        "Content-Type: application/json",
        "X-PrivateKey: " . $histKey,
        "Authorization: Bearer " . $token,
        "X-ClientLocalIP: 127.0.0.1",
        "X-ClientPublicIP: 127.0.0.1",
        "X-MACAddress: 00:00:00:00:00:00",
        "Accept: application/json",
        "X-UserType: USER",
        "X-SourceID: WEB"
    ];
    
    echo "<strong>Request:</strong> $url<br>";
    echo "Body: $body<br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<strong>Response Code:</strong> $httpCode<br>";
    echo "<strong>Raw Response:</strong><br><textarea style='width:100%; height:150px;'>$response</textarea><br>";
    
    $json = json_decode($response, true);
    if ($json && isset($json['status']) && $json['status'] == true) {
        echo "<div style='color:green; font-weight:bold;'>✅ SUCCESS: Historical Data Fetched!</div>";
    } else {
        echo "<div style='color:red; font-weight:bold;'>❌ FAILED: API Error</div>";
        if (isset($json['message'])) echo "Message: " . $json['message'];
        if (isset($json['errorcode'])) echo " (Code: " . $json['errorcode'] . ")";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
