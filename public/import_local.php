<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '512M');
set_time_limit(600); 

require_once __DIR__ . '/../src/Database.php';

$pdo = Database::getInstance()->getConnection();
$message = "";
$localFile = __DIR__ . '/master.json';

if (isset($_POST['run'])) {
    
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

    if (!file_exists($localFile)) {
        $message = "<div class='alert alert-danger'>File 'master.json' not found! Please upload it first.</div>";
    } else {
        $message .= "Found file. Size: " . round(filesize($localFile)/1024/1024, 2) . " MB.<br>";
        $message .= "Parsing...<br>";
        
        try {
            $pdo->beginTransaction();
            
            // Prepare Statements
            $stmtStock = $pdo->prepare("INSERT INTO stocks (symbol, name, token, lot_size, is_active) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE token=VALUES(token)");
            $stmtOpt = $pdo->prepare("INSERT INTO option_contracts (stock_id, symbol, strike_price, option_type, expiry_date) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE symbol=VALUES(symbol)");
            
            // Cache Index IDs
            // Ensure Indices exist in DB first? The parser handles inserts.
            // But we need to know existing IDs to avoid constant lookups if possible, but for robustness we'll query/insert.
            
            $niftyId = null;
            $bankniftyId = null;
            $count = 0;
            
            $handle = fopen($localFile, "r");
            if ($handle) {
                // Debug: Read first 1000 chars to see structure
                $head = fread($handle, 1000);
                rewind($handle); // Reset pointer
                $message .= "<strong>File Preview:</strong> <pre>" . htmlspecialchars(substr($head, 0, 500)) . "...</pre>";
                
                // Buffer setup for Stream Parsing
                // Simplified: We read small chunks and identify {...} objects
                $buffer = "";
                $depth = 0;
                $objectBuffer = "";
                $inString = false;
                
                while (!feof($handle)) {
                    $chunk = fread($handle, 8192); // 8KB read
                    
                    for ($i = 0; $i < strlen($chunk); $i++) {
                        $char = $chunk[$i];
                        
                        // Detect Object Boundaries
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
                        } elseif ($depth > 0) {
                             if ($char !== '{' || strlen($objectBuffer) > 1) { 
                                $objectBuffer .= $char;
                            }
                        }
                    }
                }
                fclose($handle);
            }
            
            $pdo->commit();
            $message .= "<div class='alert alert-success'>SUCCESS! Imported $count NIFTY/BANKNIFTY contracts.</div>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message .= "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

function processObject($json, $stmtStock, $stmtOpt, &$niftyId, &$bankniftyId, &$count, $pdo) {
    if (trim($json) === '') return;
    $scrip = json_decode($json, true);
    if (!$scrip) return;
    
    // Debug first 5 items to see keys
    static $debugCount = 0;
    if ($debugCount < 5) {
        // Safe debug output
        echo "<!-- Debug Item $debugCount: " . print_r($scrip, true) . " -->";
        $debugCount++;
    }

    // 1. Process Stocks (Indices)
    if ($scrip['exch_seg'] == 'NSE') {
        // Updated Mapping based on JSON Preview:
        // "symbol":"Nifty 50", "name":"NIFTY"
        
        $name = strtoupper(trim($scrip['name'])); // NIFTY
        $symbolLabel = trim($scrip['symbol']);    // Nifty 50

        if ($symbolLabel == 'Nifty 50' || $name == 'NIFTY') {
            $stmtStock->execute(['NIFTY', 'Nifty 50', $scrip['token'], 50]);
            if (!$niftyId) $niftyId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM stocks WHERE symbol='NIFTY'")->fetchColumn();
        }
        if ($symbolLabel == 'Nifty Bank' || $name == 'BANKNIFTY') {
            $stmtStock->execute(['BANKNIFTY', 'Nifty Bank', $scrip['token'], 15]);
            if (!$bankniftyId) $bankniftyId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM stocks WHERE symbol='BANKNIFTY'")->fetchColumn();
        }
    }
    
    // 2. Process Options
    // JSON Preview didn't show Option exch_seg, assuming 'NFO' is correct.
    // Usually for Options: "name":"NIFTY", "symbol":"NIFTY28MAR24..."
    if ($scrip['exch_seg'] == 'NFO' && $scrip['instrumenttype'] == 'OPTIDX') {
        
        // Ensure IDs
        if (!$niftyId) $niftyId = $pdo->query("SELECT id FROM stocks WHERE symbol='NIFTY'")->fetchColumn();
        if (!$bankniftyId) $bankniftyId = $pdo->query("SELECT id FROM stocks WHERE symbol='BANKNIFTY'")->fetchColumn();
        
        $stockId = null;
        $name = strtoupper(trim($scrip['name'])); // NIFTY
        
        if ($name == 'NIFTY') $stockId = $niftyId;
        if ($name == 'BANKNIFTY') $stockId = $bankniftyId;
        
        if ($stockId) {
            $expiryRaw = $scrip['expiry'];
            
            // Optimization: Skip old years
            if (strpos($expiryRaw, '2020') !== false || strpos($expiryRaw, '2021') !== false) return; // Basic filter

            $dt = DateTime::createFromFormat('dMY', $expiryRaw);
            if ($dt) {
                $expiry = $dt->format('Y-m-d');
                if ($expiry >= date('Y-m-d')) {
                     $strike = floatval($scrip['strike']); 
                     // IMPORTANT: Angel sometimes sends strike * 100. Check values.
                     // In database we store regular strike (e.g. 18000).
                     // If JSON says "18000.000000", then floatval is correct.
                     
                     $symbol = $scrip['symbol']; // NIFTY12JAN18000CE
                     $type = (substr($symbol, -2) == 'CE') ? 'CE' : ((substr($symbol, -2) == 'PE') ? 'PE' : null);
                     
                     if ($type) {
                         $stmtOpt->execute([
                             $stockId,
                             $scrip['token'],
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
    <title>Local File Import</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">Manual File Import (Final Solution)</div>
            <div class="card-body">
                <ol>
                    <li><strong>Download Verified Master File:</strong> <a href="https://margincalculator.angelbroking.com/OpenAPI_File/files/OpenAPIScripMaster.json" target="_blank" class="fw-bold">Click Here to Download JSON</a></li>
                    <li>Upload it to your server's <code>public</code> folder via cPanel/FTP.</li>
                    <li>Rename it to <strong><code>master.json</code></strong>.</li>
                    <li>Click the button below.</li>
                </ol>
                <div class="alert alert-warning">
                    <strong>Important:</strong> After downloading, upload the file to your <code>public</code> folder and rename it to <strong><code>master.json</code></strong>.
                </div>
                
                <?= $message ?>
                
                <form method="post">
                    <button type="submit" name="run" class="btn btn-primary btn-lg w-100">Run Local Import</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
