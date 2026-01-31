<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../src/Settings.php';

$message = "";
$error = "";
$generatedToken = "";

// Helper to generate fake MAC
function generateMac() {
    return implode(':', str_split(substr(md5(mt_rand()), 0, 12), 2));
}

// Get Server IP
$serverIP = $_SERVER['SERVER_ADDR'] ?? '10.0.0.1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientCode = trim($_POST['client_code']);
    $password = trim($_POST['password']);
    $totp = trim($_POST['totp']);
    $apiKey = trim($_POST['api_key']);

    if (empty($clientCode) || empty($password) || empty($totp) || empty($apiKey)) {
        $error = "All fields are required.";
    } else {
        $url = "https://apiconnect.angelone.in/rest/secure/angelbroking/user/v1/loginByPassword";
        
        $body = json_encode([
            "clientcode" => $clientCode,
            "password" => $password,
            "totp" => $totp
        ]);

        // Get User's Real IP
        $userIP = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "X-UserType: USER",
            "X-SourceID: WEB", // Changed back to WEB
            "X-PrivateKey: " . $apiKey,
            "X-ClientLocalIP: " . $userIP,
            "X-ClientPublicIP: " . $userIP, // Use the actual user's IP to avoid data-center block
            "X-MACAddress: " . generateMac(),
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
            "Accept-Language: en-US,en;q=0.9",
            "Referer: https://smartapi.angelbroking.com/",
            "Origin: https://smartapi.angelbroking.com"
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, ""); // Handle GZIP
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for some server configs

        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $json = json_decode($response, true);
        
        if ($json && isset($json['status']) && $json['status'] == true) {
            $generatedToken = $json['data']['jwtToken'];
            $message = "SUCCESS! Token Generated.";
            
            // Auto Update
            if (isset($_POST['save'])) {
                $settings = new Settings();
                $settings->set('access_token', $generatedToken);
                $settings->set('market_api_key', $apiKey);
                $message .= " Settings updated automatically.";
            }
        } else {
            $error = "Login Failed: " . ($json['message'] ?? 'Unknown Error');
            if (isset($json['data']['error_code'])) $error .= " (" . $json['data']['error_code'] . ")";
            $error .= "<br>HTTP Code: $httpCode<br>Response: " . htmlspecialchars($response);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Angel One Login (Advanced)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">Angel One Token Generator (WAF Bypass)</div>
            <div class="card-body">
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label>Client Code</label>
                        <input type="text" name="client_code" class="form-control" required placeholder="S123456" value="<?= $_POST['client_code'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Angel One Password">
                    </div>
                    <div class="mb-3">
                        <label>TOTP (from App)</label>
                        <input type="text" name="totp" class="form-control" required placeholder="6-digit code">
                    </div>
                    <div class="mb-3">
                        <label>Market API Key</label>
                        <input type="text" name="api_key" class="form-control" required value="<?= $_POST['api_key'] ?? '' ?>">
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="save" checked id="save">
                        <label class="form-check-label" for="save">Auto-save to Settings</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Generate Token</button>
                </form>
                
                <?php if ($generatedToken): ?>
                    <div class="mt-4">
                        <label>Generated Token:</label>
                        <textarea class="form-control" rows="5" readonly><?= $generatedToken ?></textarea>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
