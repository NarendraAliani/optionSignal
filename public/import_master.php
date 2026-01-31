<?php
ini_set('memory_limit', '1024M');
set_time_limit(300); // 5 minutes

require_once __DIR__ . '/../src/Database.php';

$pdo = Database::getInstance()->getConnection();

$message = "";
$masterUrl = "https://margincalculator.angelbroking.com/OpenAPIScripMaster.json";

if (isset($_POST['download'])) {
    $message .= "Downloading Scrip Master (via cURL)...<br>";
    
    // Use cURL for better compatibility (Shared hosts often disable file_get_contents for URLs)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $masterUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Handle potential SSL issues on host
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes timeout for large file
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36');
    
    $json = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($json && !$error) {
        $data = json_decode($json, true);
        $message .= "Download Complete. Total Scrips: " . count($data) . "<br>";
        
        // Transaction start
        $pdo->beginTransaction();
        
        try {
            // clear old contracts if needed, or update
            // $pdo->exec("TRUNCATE TABLE option_contracts"); // Dangerous if we want to keep history, but for now we want fresh tokens
            
            $count = 0;
            
            // 1. First Pass: Find & Insert Underlying Indices (NIFTY, BANKNIFTY)
            // We need their NSE Tokens for fetching CMP (LTP)
            $indices = [
                'Nifty 50' => 'NIFTY',
                'Nifty Bank' => 'BANKNIFTY'
            ];
            
            $stockTokens = [];
            
            foreach ($data as $scrip) {
                if ($scrip['exch_seg'] == 'NSE' && array_key_exists($scrip['name'], $indices)) { // Angel uses "Nifty 50" for name
                    $symbol = $indices[$scrip['name']]; // Map "Nifty 50" -> "NIFTY"
                    $token = $scrip['token'];
                    
                    // Insert/Update Stock
                    $stmtStock = $pdo->prepare("INSERT INTO stocks (symbol, name, token, lot_size, is_active) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE token=VALUES(token)");
                    // Lot sizes: Nifty 50, BN 15 (Standard defaults, can be updated)
                    $lot = ($symbol == 'NIFTY') ? 50 : 15;
                    $stmtStock->execute([$symbol, $scrip['name'], $token, $lot]);
                    
                    $stockTokens[$symbol] = $token;
                }
            }
            
            // Fallback if not found in JSON (Indices sometimes have weird names/segments)
            // Hardcoded known index tokens for Angel One if loop failed
            if (!isset($stockTokens['NIFTY'])) {
                 $pdo->exec("INSERT INTO stocks (symbol, name, token, lot_size, is_active) VALUES ('NIFTY', 'Nifty 50', '99926000', 50, 1) ON DUPLICATE KEY UPDATE token='99926000'");
            }
            if (!isset($stockTokens['BANKNIFTY'])) {
                 $pdo->exec("INSERT INTO stocks (symbol, name, token, lot_size, is_active) VALUES ('BANKNIFTY', 'Nifty Bank', '99926009', 15, 1) ON DUPLICATE KEY UPDATE token='99926009'");
            }

            // Reload IDs
            $niftyId = $pdo->query("SELECT id FROM stocks WHERE symbol='NIFTY'")->fetchColumn();
            $bankniftyId = $pdo->query("SELECT id FROM stocks WHERE symbol='BANKNIFTY'")->fetchColumn();
            
            $stockMap = [
                'NIFTY' => $niftyId,
                'BANKNIFTY' => $bankniftyId
            ];

            $stmt = $pdo->prepare("INSERT INTO option_contracts (stock_id, symbol, strike_price, option_type, expiry_date) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE symbol=VALUES(symbol)");
            
            foreach ($data as $scrip) {
                // Filter: ExchSegment 'NFO', InstrumentType 'OPTIDX' (Option Index)
                if ($scrip['exch_seg'] == 'NFO' && $scrip['instrumenttype'] == 'OPTIDX') {
                    
                    $name = $scrip['name']; // e.g. NIFTY
                    if (!isset($stockMap[$name]) || !$stockMap[$name]) continue;
                    
                    // Format Expiry: 28MAR2024 -> 2024-03-28
                    // Angel format: 28MAR2024
                    $expiryRaw = $scrip['expiry'];
                    $dt = DateTime::createFromFormat('dMY', $expiryRaw);
                    if (!$dt) continue;
                    $expiry = $dt->format('Y-m-d');
                    
                    // Filter Expired
                    if ($expiry < date('Y-m-d')) continue;
                    
                    // Parse Option Type (CE/PE) - usually last 2 chars or from symbol '...CE'
                    // symbol: NIFTY28MAR2418000CE
                    // regex to extract type
                    $symbol = $scrip['symbol'];
                    $type = (substr($symbol, -2) == 'CE') ? 'CE' : ((substr($symbol, -2) == 'PE') ? 'PE' : null);
                    if (!$type) continue;
                    
                    // Strike
                    $strike = floatval($scrip['strike']) / 100; // API usually gives strike*100 ? No, check raw.
                    // Actually ScripMaster 'strike' is usually correct like "18000.000000".
                    $strike = floatval($scrip['strike']); 

                    $token = $scrip['token']; // This is the symbolToken we need!
                    
                    $stmt->execute([
                        $stockMap[$name],
                        $token, // storing token in 'symbol' column as discussed
                        $strike,
                        $type,
                        $expiry
                    ]);
                    
                    $count++;
                }
            }
            
            $pdo->commit();
            $message .= "Success! Imported $count option contracts for NIFTY/BANKNIFTY.<br>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message .= "Error: " . $e->getMessage();
        }
        
    } else {
        $message .= "Failed to download JSON.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Import Master</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="card">
        <div class="card-header">Import Angel One Scrip Master</div>
        <div class="card-body">
            <p>This will download the ~100MB Master JSON and populate NIFTY/BANKNIFTY options.</p>
            <?php if($message) echo "<div class='alert alert-info'>$message</div>"; ?>
            <form method="post">
                <button type="submit" name="download" class="btn btn-primary">Download & Import</button>
            </form>
        </div>
    </div>
</body>
</html>
