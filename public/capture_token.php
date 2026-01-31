<?php
session_start();
require_once __DIR__ . '/../src/Settings.php';

$settings = new Settings();
$message = "Waiting for token...";
$success = false;

// Angel One Publisher Login redirects with POST data usually, or GET parameters depending on configuration.
// Usually for SmartAPI, it sends data via POST if configured, or query params.
// Let's handle both.

$token = $_REQUEST['auth_token'] ?? $_REQUEST['jwtToken'] ?? null;
$feedToken = $_REQUEST['feedToken'] ?? null;

if ($token) {
    // Save to settings
    $settings->set('access_token', $token);
    
    // Sometimes feed token is separate
    if ($feedToken) {
        $settings->set('feed_token', $feedToken); // We might need to add this key support later if needed
    }
    
    $message = "SUCCESS! Token captured and saved.";
    $success = true;
} else {
    // Check POST body just in case
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    if (isset($data['jwtToken'])) {
        $settings->set('access_token', $data['jwtToken']);
        $message = "SUCCESS! Token captured and saved.";
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Capture Token</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow-sm" style="width: 500px;">
        <div class="card-body text-center">
            <?php if ($success): ?>
                <div class="display-1 text-success mb-3">✔</div>
                <h3 class="text-success">Token Saved</h3>
                <p class="mb-4">You can now use the scanner.</p>
                <a href="index.php" class="btn btn-primary w-100">Go to Scanner</a>
            <?php else: ?>
                <h4 class="mb-3">Token Capture Endpoint</h4>
                <div class="alert alert-warning">
                    No token received yet.
                </div>
                <p class="text-start small">
                    <strong>Instructions:</strong><br>
                    1. Go to Angel One App Settings.<br>
                    2. Set <strong>Redirect URL</strong> to:<br>
                    <code><?= "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] ?></code><br>
                    3. Save your <strong>Market API Key</strong> in THIS app's Settings page.<br>
                    4. Once saved, a green <strong>Login</strong> button will appear below.
                </p>
                <hr>
                <!-- NOTE: User needs to insert their API KEY here for the link to work -->
                <?php 
                    $apiKey = $settings->get('market_api_key'); 
                    if ($apiKey):
                ?>
                    <a href="https://smartapi.angelbroking.com/publisher-login?api_key=<?= htmlspecialchars($apiKey) ?>" class="btn btn-success w-100">Login with Angel One</a>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>Step 1 Required:</strong><br>
                        Please copy your <strong>Market API Key</strong> from the Angel One Dashboard and paste it in the Settings page.
                    </div>
                    <a href="settings.php" class="btn btn-primary w-100">Go to Settings Page</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
