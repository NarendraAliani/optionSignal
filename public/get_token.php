<?php
session_start();
require_once __DIR__ . '/../src/Settings.php';

// Use specific layout or standalone
$pageTitle = 'Generate Token';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$settings = new Settings();
$marketKey = $settings->get('market_api_key');
$message = '';
$error = '';
$generatedToken = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientCode = $_POST['client_code'];
    $password = $_POST['password'];
    $totp = $_POST['totp'];
    $apiKey = $_POST['api_key_input']; // Use the visible input

    if (empty($clientCode) || empty($password) || empty($totp) || empty($apiKey)) {
        $error = "All fields are required, including API Key.";
    } else {
        // ... (API Call) ...
        $url = "https://apiconnect.angelbroking.com/rest/secure/angelbroking/user/v1/loginByPassword";
        
        // ... request setup ...
        $body = json_encode([
            "clientcode" => $clientCode,
            "password" => $password,
            "totp" => $totp
        ]);

        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "X-UserType: USER",
            "X-SourceID: ALL", // Changed from WEB
            "X-PrivateKey: " . $apiKey,
            "X-ClientLocalIP: 127.0.0.1",
            "X-ClientPublicIP: 127.0.0.1",
            "X-MACAddress: 00-00-00-00-00-00"
        ];

        // ... execute curl ...
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Fix for empty responses: Add User-Agent and handle GZIP
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_ENCODING, ""); // Handle all encodings (gzip, etc.)
        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_setopt($ch, CURLOPT_HEADER, true); // Capture headers in response
        
        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        
        curl_close($ch);
        
        $headerStr = substr($rawResponse, 0, $headerSize);
        $response = substr($rawResponse, $headerSize);
        
        if ($rawResponse === false) {
             $error = "cURL Error: " . $curlError;
        } else {
            // ... (rest of logic) ...
            $json = json_decode($response, true); // JSON decode the body part

            if (isset($json['status']) && $json['status'] == true) {
                $generatedToken = $json['data']['jwtToken'];
                $message = "Token generated successfully!";
                
                // Auto-save if requested
                if (isset($_POST['autosave'])) {
                    $settings->set('access_token', $generatedToken);
                    $settings->set('market_api_key', $apiKey); // Also save the key
                    $message .= " Token and API Key saved.";
                }
            } else {
                $error = "Login Failed: " . ($json['message'] ?? 'Unknown Error');
                if (isset($json['data']['error_code'])) {
                    $error .= " (Code: " . $json['data']['error_code'] . ")";
                }
                
                // Detailed Debug Info
                if (strpos($error, 'Unknown Error') !== false || true) { 
                    $error .= "<hr><small><strong>Debug Info:</strong><br>";
                    $error .= "HTTP Code: " . $httpCode . "<br>";
                    $error .= "<strong>Response Headers:</strong><br><pre>" . htmlspecialchars($headerStr) . "</pre>";
                    $error .= "<strong>Raw Body:</strong> '" . htmlspecialchars($response) . "'</small>";
                }
            }
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Generate Angel One Token</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Angel One Client ID</label>
                            <input type="text" name="client_code" class="form-control" required placeholder="e.g. S123456">
                        </div>
                        <div class="mb-3">
                            <label>Account Password</label>
                            <input type="password" name="password" class="form-control" required placeholder="Your Angel One alphanumeric password">
                        </div>
                        <div class="mb-3">
                            <label>TOTP (Authenticator App Code)</label>
                            <input type="text" name="totp" class="form-control" required placeholder="6-digit code">
                        </div>
                        
                        <!-- Allow manual API Key entry if not in settings, or override -->
                        <div class="mb-3">
                            <label>Market API Key</label>
                            <input type="text" name="api_key_input" class="form-control" value="<?= htmlspecialchars($marketKey) ?>" placeholder="Enter your Market API Key (e.g. cDyd...)" required>
                            <div class="form-text">Required to sign the login request.</div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="autosave" name="autosave" checked>
                            <label class="form-check-label" for="autosave">Automatically save Token AND API Key to Settings</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Generate Token</button>
                    </form>

                    <?php if ($generatedToken): ?>
                        <div class="mt-4">
                            <label>Generated Token</label>
                            <textarea class="form-control" rows="3" readonly><?= $generatedToken ?></textarea>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Manual Alternative</h5>
                </div>
                <div class="card-body">
                    <p>If the above tool fails (due to XAMPP/Firewall issues), please generate the token manually:</p>
                    <ol>
                        <li>Go to the <a href="https://smartapi.angelbroking.com/docs/User" target="_blank">SmartAPI Documentation</a>.</li>
                        <li>Click on <strong>Login</strong> > <strong>POST /loginByPassword</strong>.</li>
                        <li>Click <strong>Try it out</strong>.</li>
                        <li>Enter your Client Code, Password, TOTP, and API Key (in headers).</li>
                        <li>Copy the <code>jwtToken</code> from the response.</li>
                        <li>Paste it into the <a href="settings.php">Settings Page</a>.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
