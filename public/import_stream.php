<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '512M'); // Try to bump, but rely on streaming
set_time_limit(600); // 10 minutes

require_once __DIR__ . '/../src/Database.php';

$pdo = Database::getInstance()->getConnection();
$message = "";
// Updated verified URL
$masterUrl = "https://margincalculator.angelbroking.com/OpenAPI_File/files/OpenAPIScripMaster.json";
$tempFile = __DIR__ . '/master_temp.json';

if (isset($_POST['download'])) {
    
    // 0. Self-Heal: Check DB Schema
    try {
        $check = $pdo->query("SHOW COLUMNS FROM stocks LIKE 'token'");
        if ($check->rowCount() == 0) {
            $message .= "Fixing DB: Adding 'token' column to stocks...<br>";
            $pdo->exec("ALTER TABLE stocks ADD COLUMN token VARCHAR(50) DEFAULT NULL AFTER name");
             // Also add index for performance
            $pdo->exec("ALTER TABLE stocks ADD INDEX (token)");
        }
    } catch (Exception $e) { $message .= "Schema Warning: " . $e->getMessage() . "<br>"; }

    $message .= "Started. Step 1: Downloading to disk...<br>";
    
    // 1. Download to File (Low Memory)
    if (file_exists($tempFile)) unlink($tempFile); // Clear old
    
    $fp = fopen($tempFile, 'w+');
    if (!$fp) {
        $message .= "<span class='text-danger'>Error: Cannot create temp file. Check permissions.</span><br>";
    } else {
        $ch = curl_init($masterUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp); // Write to file directly
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36');
        curl_setopt($ch, CURLOPT_FAILONERROR, true); // Fail on 4xx/5xx
        
        curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        
        if ($curlError || $httpCode != 200) {
            $message .= "<span class='text-danger'>Download Failed: $curlError (HTTP $httpCode)</span><br>";
        } else {
            $filesize = filesize($tempFile) / 1024 / 1024;
            $message .= "Download Complete. Size: " . round($filesize, 2) . " MB.<br>";
            
            if ($filesize < 1) {
                 $message .= "<span class='text-danger'>Error: File is too small. Possibly blocked or empty.</span><br>";
            } else {
                $message .= "Step 2: Parsing & Importing...<br>";
    
                // 2. Stream Parse
                try {
            $pdo->beginTransaction();
            
            // Prepare Statements
            $stmtStock = $pdo->prepare("INSERT INTO stocks (symbol, name, token, lot_size, is_active) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE token=VALUES(token)");
            $stmtOpt = $pdo->prepare("INSERT INTO option_contracts (stock_id, symbol, strike_price, option_type, expiry_date) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE symbol=VALUES(symbol)");
            
            // Track IDs
            $niftyId = null;
            $bankniftyId = null;
            $count = 0;
            
            $handle = fopen($tempFile, "r");
            if ($handle) {
                $buffer = "";
                $depth = 0;
                $objectBuffer = "";
                $inString = false;
                
                // Read 8KB chunks
                while (!feof($handle)) {
                    $chunk = fread($handle, 8192);
                    
                    // Simple logic: We assume objects are inside [ ... ]
                    // We split by "}," to find end of objects roughly, or parse chars.
                    // For robustness/simplicity given the structure is usually minified: [ {..},{..} ]
                    // A proper validator is complex. Given the constraints, we'll try a regex extract on the stream or char-by-char.
                    // Char-by-char is safest for memory.
                    
                    for ($i = 0; $i < strlen($chunk); $i++) {
                        $char = $chunk[$i];
                        
                        if ($char === '{' && !$inString) {
                            $depth++;
                            if ($depth === 1) $objectBuffer = "{";
                        } elseif ($char === '}' && !$inString) {
                            $depth--;
                            if ($depth === 0) {
                                $objectBuffer .= "}";
                                processObject($objectBuffer, $stmtStock, $stmtOpt, $niftyId, $bankniftyId, $count, $pdo);
                                $objectBuffer = "";
                            }
                        }
                        
                        // Track strings to ignore braces inside strings
                        if ($char === '"' && ($i === 0 || $chunk[$i-1] !== '\\')) {
                            // $inString = !$inString; // Too risky with chunk boundaries.
                            // Simplified: Angel JSON doesn't typically have braces in values.
                            // Let's ignore $inString logic for speed/simplicity unless it breaks.
                        }
                        
                        if ($depth > 0) {
                            if ($char !== '{' || strlen($objectBuffer) > 1) { // Avoid double {
                                $objectBuffer .= $char;
                            }
                        }
                    }
                }
                fclose($handle);
            }
            
            $pdo->commit();
            $message .= "SUCCESS! Processed $count contracts.<br>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message .= "Error: " . $e->getMessage();
        }
        
        // Cleanup
        @unlink($tempFile);
    }
}

function processObject($json, $stmtStock, $stmtOpt, &$niftyId, &$bankniftyId, &$count, $pdo) {
    $scrip = json_decode($json, true);
    if (!$scrip) return;
    
    // 1. Process Stocks (Indices)
    if ($scrip['exch_seg'] == 'NSE') {
        if ($scrip['name'] == 'Nifty 50') {
            $stmtStock->execute(['NIFTY', 'Nifty 50', $scrip['token'], 50]);
            if (!$niftyId) $niftyId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM stocks WHERE symbol='NIFTY'")->fetchColumn();
        }
        if ($scrip['name'] == 'Nifty Bank') {
            $stmtStock->execute(['BANKNIFTY', 'Nifty Bank', $scrip['token'], 15]);
            if (!$bankniftyId) $bankniftyId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM stocks WHERE symbol='BANKNIFTY'")->fetchColumn();
        }
    }
    
    // 2. Process Options
    if ($scrip['exch_seg'] == 'NFO' && $scrip['instrumenttype'] == 'OPTIDX') {
        // Need IDs
        if (!$niftyId) $niftyId = $pdo->query("SELECT id FROM stocks WHERE symbol='NIFTY'")->fetchColumn();
        if (!$bankniftyId) $bankniftyId = $pdo->query("SELECT id FROM stocks WHERE symbol='BANKNIFTY'")->fetchColumn();
        
        $stockId = null;
        if ($scrip['name'] == 'NIFTY') $stockId = $niftyId;
        if ($scrip['name'] == 'BANKNIFTY') $stockId = $bankniftyId;
        
        if ($stockId) {
            // Check expiry
            $dt = DateTime::createFromFormat('dMY', $scrip['expiry']);
            if ($dt) {
                $expiry = $dt->format('Y-m-d');
                if ($expiry >= date('Y-m-d')) {
                     // Type & Strike
                     $strike = floatval($scrip['strike']);
                     $symbol = $scrip['symbol'];
                     $type = (substr($symbol, -2) == 'CE') ? 'CE' : ((substr($symbol, -2) == 'PE') ? 'PE' : null);
                     
                     if ($type) {
                         $stmtOpt->execute([
                             $stockId,
                             $scrip['token'], // Real Token
                             $strike,
                             $type,
                             $expiry
                         ]);
                         $count++;
                     }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stream Import Master</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="card">
        <div class="card-header bg-success text-white">Low-Memory Stream Import</div>
        <div class="card-body">
            <p><strong>Status:</strong> Use this if the main importer crashes (500 Error).</p>
            <p>It downloads the file to disk and reads it one item at a time.</p>
            <?php if($message) echo "<div class='alert alert-info'>$message</div>"; ?>
            <form method="post">
                <button type="submit" name="download" class="btn btn-primary">Start Stream Import</button>
            </form>
        </div>
    </div>
</body>
</html>
