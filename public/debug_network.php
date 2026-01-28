<?php
echo "<h3>cURL Connectivity Test</h3>";

function testUrl($url, $postData = null) {
    echo "<hr><strong>Testing: $url</strong><br>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for local XAMPP
    
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }

    $start = microtime(true);
    $response = curl_exec($ch);
    $end = microtime(true);
    
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);

    echo "Time: " . round($end - $start, 4) . "s<br>";
    echo "HTTP Code: " . $info['http_code'] . "<br>";
    
    if ($error) {
        echo "cURL Error: " . $error . "<br>";
    } else {
        echo "Response Length: " . strlen($response) . " chars<br>";
        echo "Response Preview: " . htmlspecialchars(substr($response, 0, 500)) . "<br>";
    }
}

// 1. Test Generic Public API (Echo)
echo "<h4>1. Testing HTTPBin (Public Echo API)</h4>";
testUrl('https://httpbin.org/post', ['test' => 'hello']);

// 2. Test Angel One Login URL (Connectivity Only)
echo "<h4>2. Testing Angel One Login Endpoint (Connectivity Check)</h4>";
// We expect a 400 or 401 because we send empty data, but we should get *some* response
testUrl('https://apiconnect.angelbroking.com/rest/secure/angelbroking/user/v1/loginByPassword', ['clientcode' => 'dummy']);

?>
