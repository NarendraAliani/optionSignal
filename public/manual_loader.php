<?php
session_start();
require_once __DIR__ . '/../src/Settings.php';

$message = '';
$settings = new Settings();
$marketKey = $settings->get('market_api_key');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token']);
    if (!empty($token)) {
        // Strip "Bearer " if included by mistake
        if (stripos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        
        $settings->set('access_token', $token);
        $message = "SUCCESS! Token saved. You can now use the Scanner.";
    } else {
        $message = "Error: Token cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manual Token Loader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Manual Token Loader (Final Solution)</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Why use this?</strong><br>
                    Your HostGator server IP seems to be blocked by Angel One's firewall (hence the empty response).<br>
                    This tool lets you generate the token on your own computer and save it here.
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <?= $message ?> <a href="index.php" class="btn btn-sm btn-success ms-2">Go to Scanner</a>
                    </div>
                <?php endif; ?>

                <h5>Step 1: Generate Token</h5>
                <ol>
                    <li>Open this link in a new tab: <a href="https://smartapi.angelbroking.com/docs/User" target="_blank">Angel One API Docs</a></li>
                    <li>On the left menu, click <strong>Login</strong> -> <strong>POST /loginByPassword</strong>.</li>
                    <li>Click the <strong>Try it out</strong> button.</li>
                    <li>In <strong>Headers (X-PrivateKey)</strong>: Paste this Key:<br>
                        <code><?= htmlspecialchars($marketKey) ?></code>
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($marketKey) ?>')">Copy Key</button>
                    </li>
                    <li>In <strong>Body</strong>: Enter your Client ID, Password, and TOTP.</li>
                    <li>Click <strong>Execute</strong>.</li>
                    <li>Copy the long <code>jwtToken</code> from the Response (starts with <code>ey...</code>).</li>
                </ol>

                <hr>

                <h5>Alternative Method: PowerShell (Most Reliable)</h5>
                <p>If the API Docs link above gives you a "URL Rejected" error, use this PowerShell script. It bypasses the browser.</p>
                
                <ol>
                    <li>Search for <strong>PowerShell</strong> in your Start Menu.</li>
                    <li>Right-click and select <strong>Run as Administrator</strong>.</li>
                    <li>Copy the code below, paste it into PowerShell, and press <strong>Enter</strong>.</li>
                </ol>

                <div class="bg-dark text-light p-3 rounded mb-3 position-relative">
                    <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-2" onclick="copyPowerShell()">Copy Code</button>
                    <pre id="psCode" style="white-space: pre-wrap; word-break: break-all; margin: 0;">
# Force TLS 1.2 (Fix for older Windows)
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
    "X-UserType" = "USER"
    "X-SourceID" = "WEB"
    "X-ClientLocalIP" = "127.0.0.1"
    "X-ClientPublicIP" = "127.0.0.1"
    "X-MACAddress" = "02-00-00-00-00-00"
    "X-PrivateKey" = "<?= htmlspecialchars($marketKey) ?>"
}

$body = @{
    clientcode = "YOUR_CLIENT_ID"
    password = "YOUR_PIN_OR_PASSWORD"
    totp = "YOUR_TOTP_CODE"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "https://apiconnect.angelone.in/rest/auth/angelbroking/user/v1/loginByPassword" -Method Post -Headers $headers -Body $body

Write-Host "JWT TOKEN:" -ForegroundColor Green
Write-Host $response.data.jwtToken</pre>
                </div>

                <hr>

                <h5>Method 3: Postman (For Advanced Users)</h5>
                <p>If you prefer Postman, use these exact settings:</p>
                <table class="table table-bordered table-sm small">
                    <tr><th>Method</th><td><code>POST</code></td></tr>
                    <tr><th>URL</th><td><code>https://apiconnect.angelone.in/rest/auth/angelbroking/user/v1/loginByPassword</code></td></tr>
                    <tr><th>Headers</th><td>
                        <strong>Content-Type:</strong> application/json<br>
                        <strong>X-UserType:</strong> USER<br>
                        <strong>X-SourceID:</strong> WEB<br>
                        <strong>X-ClientLocalIP:</strong> 127.0.0.1<br>
                        <strong>X-ClientPublicIP:</strong> 127.0.0.1<br>
                        <strong>X-MACAddress:</strong> 02-00-00-00-00-00<br>
                        <strong>X-PrivateKey:</strong> <span class="text-danger"><?= htmlspecialchars($marketKey) ?></span>
                    </td></tr>
                    <tr><th>Body (Raw JSON)</th><td>
<pre style="margin:0">
{
    "clientcode": "YOUR_CLIENT_ID",
    "password": "YOUR_4_DIGIT_MPIN",
    "totp": "YOUR_APP_CODE"
}</pre>
                    </td></tr>
                </table>
                <div class="alert alert-warning mt-2">
                    <strong>Important:</strong> In the <code>password</code> field, you must enter your <strong>4-Digit MPIN</strong>, NOT your alphanumeric password.
                    If you get "LoginbyPassword is not allowed", it means you used the wrong password.
                </div>
                <hr>

                <script>
                function copyPowerShell() {
                    const code = document.getElementById('psCode').innerText;
                    navigator.clipboard.writeText(code).then(() => {
                        alert('Code copied! Paste it into PowerShell.');
                    });
                }
                </script>

                <hr>

                <h5>Step 2: Save Token</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Paste JWT Token Here</label>
                        <textarea name="token" class="form-control" rows="5" placeholder="ey..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Token & Activate</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
