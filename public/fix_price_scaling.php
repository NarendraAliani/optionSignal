<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../src/Database.php';

$message = "";
$status = "";

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check for scaled strikes
    // Limit check to active contracts to be safe, or just check all
    $count = $pdo->query("SELECT COUNT(*) FROM option_contracts WHERE strike_price > 200000")->fetchColumn();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])) {
        if ($count > 0) {
            $updated = $pdo->exec("UPDATE option_contracts SET strike_price = strike_price / 100 WHERE strike_price > 200000");
            $message = "✅ Success! Corrected $updated contracts.";
            $status = "success";
            $count = 0; // Reset
        } else {
            $message = "No contracts needed fixing.";
            $status = "info";
        }
    }
    
} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
    $status = "danger";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Strike Prices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark fw-bold">⚠️ Database Repair Tool</div>
            <div class="card-body text-center">
                <h3>Strike Price Scaling Fix</h3>
                <p class="lead">Angel One data sometimes imports strike prices multiplied by 100 (e.g., 2500000 instead of 25000).</p>
                
                <hr>
                
                <div class="my-4">
                    <h5>Diagnosis:</h5>
                    <?php if ($count > 0): ?>
                        <div class="alert alert-danger">
                            <strong>ISSUE DETECTED:</strong> Found <strong><?= $count ?></strong> contracts with incorrect scaling (> 200,000).
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <strong>ALL GOOD:</strong> No scaling issues detected.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $status ?>"><?= $message ?></div>
                <?php endif; ?>
                
                <?php if ($count > 0): ?>
                <form method="post">
                    <button type="submit" name="fix" class="btn btn-danger btn-lg">🔧 Fix Now (Divide by 100)</button>
                </form>
                <?php else: ?>
                    <a href="index.php" class="btn btn-primary">Go to Scanner</a>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</body>
</html>
