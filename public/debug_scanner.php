<?php
/**
 * Comprehensive Scanner Debug Tool
 * Tests all components: DB, API, Signal Engine, Data availability
 */

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Settings.php';
require_once __DIR__ . '/../src/MarketAPI.php';
require_once __DIR__ . '/../src/SignalEngine.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner Debug Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-pass { color: #28a745; font-weight: bold; }
        .test-fail { color: #dc3545; font-weight: bold; }
        .test-warn { color: #ffc107; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h1 class="mb-4">🔍 Scanner Debug Tool</h1>
        
        <?php
        $results = [];
        
        // Test 1: Database Connection
        echo "<div class='card mb-3'><div class='card-header'><h5>Test 1: Database Connection</h5></div><div class='card-body'>";
        try {
            $db = Database::getInstance()->getConnection();
            echo "<p class='test-pass'>✓ Database connected successfully</p>";
            $results['db'] = true;
        } catch (Exception $e) {
            echo "<p class='test-fail'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            $results['db'] = false;
        }
        echo "</div></div>";
        
        // Test 2: Active Stocks
        echo "<div class='card mb-3'><div class='card-header'><h5>Test 2: Active Stocks</h5></div><div class='card-body'>";
        try {
            $stmt = $db->query("SELECT * FROM stocks WHERE is_active = 1");
            $stocks = $stmt->fetchAll();
            $count = count($stocks);
            
            if ($count > 0) {
                echo "<p class='test-pass'>✓ Found {$count} active stocks</p>";
                echo "<pre>";
                foreach ($stocks as $stock) {
                    echo "- {$stock['name']} (Symbol: {$stock['symbol']}, Token: {$stock['token']})\n";
                }
                echo "</pre>";
                $results['stocks'] = true;
            } else {
                echo "<p class='test-fail'>✗ No active stocks found in database</p>";
                echo "<p class='text-muted'>Run import_local.php to populate stocks table</p>";
                $results['stocks'] = false;
            }
        } catch (Exception $e) {
            echo "<p class='test-fail'>✗ Error querying stocks: " . htmlspecialchars($e->getMessage()) . "</p>";
            $results['stocks'] = false;
        }
        echo "</div></div>";
        
        // Test 3: Option Contracts
        echo "<div class='card mb-3'><div class='card-header'><h5>Test 3: Option Contracts</h5></div><div class='card-body'>";
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM option_contracts WHERE expiry_date >= CURDATE()");
            $row = $stmt->fetch();
            $total = $row['total'];
            
            if ($total > 0) {
                echo "<p class='test-pass'>✓ Found {$total} active option contracts</p>";
                
                // Show sample contracts
                $stmt = $db->query("SELECT * FROM option_contracts WHERE expiry_date >= CURDATE() LIMIT 5");
                $samples = $stmt->fetchAll();
                echo "<p><strong>Sample contracts:</strong></p><pre>";
                foreach ($samples as $contract) {
                    echo "- {$contract['name']} {$contract['strike_price']} {$contract['option_type']} (Expiry: {$contract['expiry_date']}, Token: {$contract['symbol']})\n";
                }
                echo "</pre>";
                $results['contracts'] = true;
            } else {
                echo "<p class='test-fail'>✗ No active option contracts found</p>";
                echo "<p class='text-muted'>Run import_local.php to populate option_contracts table</p>";
                $results['contracts'] = false;
            }
        } catch (Exception $e) {
            echo "<p class='test-fail'>✗ Error querying contracts: " . htmlspecialchars($e->getMessage()) . "</p>";
            $results['contracts'] = false;
        }
        echo "</div></div>";
        
        // Test 4: API Settings
        echo "<div class='card mb-3'><div class='card-header'><h5>Test 4: API Settings</h5></div><div class='card-body'>";
        try {
            $settings = new Settings();
            $marketKey = $settings->get('market_api_key');
            $histKey = $settings->get('hist_api_key');
            $token = $settings->get('access_token');
            
            $hasMarketKey = !empty($marketKey);
            $hasHistKey = !empty($histKey);
            $hasToken = !empty($token);
            
            echo "<ul>";
            echo "<li>Market API Key: " . ($hasMarketKey ? "<span class='test-pass'>✓ Set</span>" : "<span class='test-fail'>✗ Missing</span>") . "</li>";
            echo "<li>Historical API Key: " . ($hasHistKey ? "<span class='test-pass'>✓ Set</span>" : "<span class='test-fail'>✗ Missing</span>") . "</li>";
            echo "<li>Access Token: " . ($hasToken ? "<span class='test-pass'>✓ Set</span>" : "<span class='test-fail'>✗ Missing</span>") . "</li>";
            echo "</ul>";
            
            $results['settings'] = ($hasMarketKey && $hasHistKey && $hasToken);
            
            if (!$results['settings']) {
                echo "<p class='text-muted'>Go to <a href='settings.php'>Settings page</a> to configure API credentials</p>";
            }
        } catch (Exception $e) {
            echo "<p class='test-fail'>✗ Error loading settings: " . htmlspecialchars($e->getMessage()) . "</p>";
            $results['settings'] = false;
        }
        echo "</div></div>";
        
        // Test 5: API Connectivity
        echo "<div class='card mb-3'><div class='card-header'><h5>Test 5: API Connectivity</h5></div><div class='card-body'>";
        if ($results['settings']) {
            try {
                $api = new MarketAPI();
                
                // Test with NIFTY token (99926000)
                echo "<p>Testing API with NIFTY 50 token (99926000)...</p>";
                $quote = $api->getMarketQuote('99926000');
                
                if ($quote) {
                    echo "<p class='test-pass'>✓ API connection successful</p>";
                    echo "<p>NIFTY 50 Current Price: <strong>₹" . number_format($quote, 2) . "</strong></p>";
                    $results['api'] = true;
                } else {
                    echo "<p class='test-warn'>⚠ API connected but no quote returned</p>";
                    echo "<p class='text-muted'>This might be normal if market is closed or token is invalid</p>";
                    $results['api'] = false;
                }
            } catch (Exception $e) {
                echo "<p class='test-fail'>✗ API connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
                $results['api'] = false;
            }
        } else {
            echo "<p class='test-warn'>⚠ Skipped (API settings not configured)</p>";
            $results['api'] = false;
        }
        echo "</div></div>";
        
        // Test 6: Signal Engine
        echo "<div class='card mb-3'><div class='card-header'><h5>Test 6: Signal Engine Test</h5></div><div class='card-body'>";
        if ($results['db'] && $results['stocks'] && $results['contracts'] && $results['settings']) {
            try {
                $api = new MarketAPI();
                $engine = new SignalEngine($api);
                
                echo "<p>Running scanner (backtest mode, yesterday)...</p>";
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $fromDate = "$yesterday 09:15";
                $toDate = "$yesterday 15:30";
                
                $signals = $engine->scan('15min', $fromDate, $toDate);
                
                $signalCount = count($signals);
                
                if ($signalCount > 0) {
                    echo "<p class='test-pass'>✓ Scanner executed successfully</p>";
                    echo "<p>Found <strong>{$signalCount}</strong> signals matching criteria (Close >= 2x Previous Close)</p>";
                    
                    echo "<p><strong>Sample signals:</strong></p><pre>";
                    foreach (array_slice($signals, 0, 3) as $signal) {
                        echo "- {$signal['stock']}: {$signal['pclp']} → {$signal['cclp']} ({$signal['change_pct']}%)\n";
                    }
                    echo "</pre>";
                    $results['engine'] = true;
                } else {
                    echo "<p class='test-warn'>⚠ Scanner executed but found no signals</p>";
                    echo "<p class='text-muted'>This is normal if no options doubled in price during the selected timeframe</p>";
                    echo "<p class='text-muted'>Try different dates or timeframes, or check if historical data is available</p>";
                    $results['engine'] = true; // Engine works, just no signals
                }
            } catch (Exception $e) {
                echo "<p class='test-fail'>✗ Scanner failed: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                $results['engine'] = false;
            }
        } else {
            echo "<p class='test-warn'>⚠ Skipped (prerequisites not met)</p>";
            $results['engine'] = false;
        }
        echo "</div></div>";
        
        // Summary
        echo "<div class='card mb-3 border-primary'><div class='card-header bg-primary text-white'><h5>Summary</h5></div><div class='card-body'>";
        $passed = array_filter($results);
        $total = count($results);
        $passCount = count($passed);
        
        echo "<p><strong>Tests Passed: {$passCount}/{$total}</strong></p>";
        
        if ($passCount === $total) {
            echo "<p class='test-pass'>✓ All systems operational!</p>";
            echo "<p>You can now use the scanner. If you're getting 'No signals found', it's likely because:</p>";
            echo "<ul>";
            echo "<li>No options have doubled in price during the selected timeframe (this is rare and normal)</li>";
            echo "<li>Market is closed and historical data is not available for the selected date</li>";
            echo "<li>Try different dates or timeframes</li>";
            echo "</ul>";
        } else {
            echo "<p class='test-fail'>⚠ Some tests failed. Please fix the issues above before using the scanner.</p>";
        }
        echo "</div></div>";
        ?>
        
        <div class="text-center mb-4">
            <a href="index.php" class="btn btn-primary">← Back to Scanner</a>
            <a href="javascript:location.reload()" class="btn btn-secondary">🔄 Refresh Tests</a>
        </div>
    </div>
</body>
</html>
