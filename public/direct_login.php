<?php
session_start();
require_once __DIR__ . '/../src/Settings.php';

$settings = new Settings();
$marketApiKey = $settings->get('market_api_key');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientCode = $_POST['client_code'];
    $pin = $_POST['pin'];
    $totp = $_POST['totp']; // User enters 6-digit TOTP from app
    
    // API Endpoint (New URL)
    $url = "https://apiconnect.angelone.in/rest/secure/angelbroking/user/v1/loginByPassword";
    
    // Get Server IP for headers
    $serverIp = $_SERVER['SERVER_ADDR'] ?? '10.10.10.10';
    
    // Prepare Request
    $body = json_encode([
        "clientcode" => $clientCode,
        "password" => $pin,
        "totp" => $totp
    ]);
    
    $headers = [
        "Content-Type: application/json",
        "Accept: application/json",
        "X-UserType: USER",
        "X-SourceID: WEB",
        "X-PrivateKey: " . $marketApiKey,
        "X-ClientLocalIP: " . $serverIp,
        "X-ClientPublicIP: " . $serverIp,
        "X-MACAddress: 02:00:00:00:00:00" // Generic MAC
    ];
    
    // Execute cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // HostGator sometimes needs this for external SSL
    curl_setopt($ch, CURLOPT_ENCODING, ''); // Handle GZIP if sent
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_HEADER, true); // Capture response headers too

    $rawResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $respHeaders = substr($rawResponse, 0, $headerSize);
    $response = substr($rawResponse, $headerSize);
    
    curl_close($ch);
    
    if ($curlError) {
        $error = "Connection Error: " . $curlError;
    } else {
        $json = json_decode($response, true); // Use the body part only
        
        if (isset($json['status']) && $json['status'] == true) {
            // Success!
            $jwtToken = $json['data']['jwtToken'];
            $feedToken = $json['data']['feedToken'];
            
            // Save to Settings
            $settings->set('access_token', $jwtToken);
            $settings->set('feed_token', $feedToken); // Optional if you added this key
            
            $message = "SUCCESS! Token generated and saved securely.";
        } else {
            // API Error
            $apiMsg = $json['message'] ?? 'Unknown API Error';
            $error = "Login Failed: " . $apiMsg;
            if (isset($json['errorcode'])) $error .= " (Code: " . $json['errorcode'] . ")";
            
            // Debug info - Show RAW response to user
            $error .= "<hr><strong>DEBUG RAW RESPONSE BODY:</strong><br><pre>" . htmlspecialchars($response) . "</pre>";
            $error .= "<br><strong>RESPONSE HEADERS:</strong><br><pre>" . htmlspecialchars($respHeaders) . "</pre>";
            $error .= "<br><strong>HTTP CODE:</strong> " . $httpCode;
        }
    }
}

$pageTitle = 'Direct Login';
// Only include header/navbar if they exist and don't break standalone usage
// Assuming standard layout:
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Angel One Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <!-- Navigation -->
                <div class="mb-3">
                    <a href="index.php" class="btn btn-outline-secondary">&larr; Back to Dashboard</a>
                    <a href="settings.php" class="btn btn-outline-primary float-end">Settings</a>
                </div>

                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Direct Angel One Login</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($marketApiKey)): ?>
                            <div class="alert alert-danger">
                                <strong>Error:</strong> Market API Key is missing. Please go to <a href="settings.php">Settings</a> first.
                            </div>
                        <?php else: ?>
                            
                            <?php if ($message): ?>
                                <div class="alert alert-success">
                                    <?= $message ?>
                                    <hr>
                                    <a href="index.php" class="btn btn-success">Go to Scanner</a>
                                </div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Client ID / User ID</label>
                                    <input type="text" name="client_code" class="form-control" placeholder="e.g. M156548" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">MPIN / Password</label>
                                    <input type="password" name="pin" class="form-control" placeholder="Your 4-digit PIN or Password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">TOTP (Authenticator Code)</label>
                                    <input type="text" name="totp" class="form-control" placeholder="6-digit code from App" required autocomplete="off">
                                    <div class="form-text">Open your Authenticator app and enter the current code for Angel One.</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Generate Token</button>
                            </form>
                            
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-3 text-muted small">
                    Using API Endpoint: <code>apiconnect.angelone.in</code>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
