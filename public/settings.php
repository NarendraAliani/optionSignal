<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../src/Settings.php';

$settingsManager = new Settings();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsManager->set('market_api_key', $_POST['market_api_key']);
    $settingsManager->set('hist_api_key', $_POST['hist_api_key']);
    $settingsManager->set('access_token', $_POST['access_token']);
    $message = 'Settings updated successfully.';
}

$currentSettings = $settingsManager->getAll();

$pageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Angel One Configuration</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= $message ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="alert alert-info">
                            <small>
                                <strong>Note:</strong> You need to generate the <code>Access Token</code> using your Client ID, Password, and TOTP. <a href="get_token.php" class="alert-link">Click here to Generate Token</a>.
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Market API Key (Live Data)</label>
                            <input type="text" name="market_api_key" class="form-control" value="<?= htmlspecialchars($currentSettings['market_api_key'] ?? '') ?>" placeholder="cDyd..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Historical API Key (Backtest/Candles)</label>
                            <input type="text" name="hist_api_key" class="form-control" value="<?= htmlspecialchars($currentSettings['hist_api_key'] ?? '') ?>" placeholder="FY2U..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Access Token (JWT)</label>
                            <textarea name="access_token" class="form-control" rows="3" placeholder="ey..." required><?= htmlspecialchars($currentSettings['access_token'] ?? '') ?></textarea>
                            <div class="form-text">Paste the active 'Authorization' Bearer token here.</div>
                        </div>

                        <button type="submit" class="btn btn-success">Update Credentials</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
