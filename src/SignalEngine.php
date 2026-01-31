<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/MarketAPI.php';
require_once __DIR__ . '/Indicators.php';

class SignalEngine {
    private $db;
    private $api;

    public function __construct(MarketAPI $api) {
        $this->db = Database::getInstance()->getConnection();
        $this->api = $api;
    }

    /**
     * Run the scanner
     * @param string $timeframe
     * @param string $fromDate (Active Date)
     * @param string $toDate (End Time, usually 'now' or end of day)
     * @param float $threshold Price multiplier (default 2.0 = 100% gain)
     * @return array Signals and debug info
     */
    public function scan($timeframe = '1min', $fromDate = 'today', $toDate = 'now', $threshold = 2.0) {
        $startTime = microtime(true);
        
        $signals = [];
        $stocks = $this->getUniverse();
        
        // Debug counters
        $debug = [
            'stocks_scanned' => 0,
            'contracts_checked' => 0,
            'candles_analyzed' => 0,
            'matches_found' => 0,
            'threshold' => $threshold,
            'execution_time' => '',
            'details' => [] // Detailed logging
        ];

        foreach ($stocks as $stock) {
            $debug['stocks_scanned']++;
            
            $stockDebug = [
                'name' => $stock['name'],
                'symbol' => $stock['symbol'],
                'token' => $stock['token'],
                'cmp' => null,
                'cmp_source' => null,
                'strikes_found' => 0,
                'error' => null
            ];
            
            // 1. Get Stock CMP (Using TOKEN, not Symbol)
            // Angel One API requires the numeric token for queries.
            $cmp = $this->api->getMarketQuote($stock['token']);
            
            if ($cmp) {
                $stockDebug['cmp'] = $cmp;
                $stockDebug['cmp_source'] = 'live_quote';
            }
            
            // Fallback for Backtest: Use Historical Data if Live Quote fails
            if (!$cmp) {
                // Try fetching history for the stock itself using its Token
                // Note: We need to ensure $stock['token'] exists.
                if (isset($stock['token'])) {
                    $stockCandles = $this->api->getOHLC($stock['token'], '1day', $fromDate, $toDate);
                    if (!empty($stockCandles)) {
                        $cmp = end($stockCandles)['close'];
                        $stockDebug['cmp'] = $cmp;
                        $stockDebug['cmp_source'] = 'historical_data';
                    } else {
                        $stockDebug['error'] = 'No historical data returned';
                    }
                } else {
                    $stockDebug['error'] = 'Token not set';
                }
            }

            if (!$cmp) {
                // If still no CMP, maybe we can't scan this stock.
                $stockDebug['error'] = $stockDebug['error'] ?? 'Could not fetch CMP';
                $debug['details'][] = $stockDebug;
                continue;
            }

            // 2. Identify Strikes (Dynamic from DB)
            $strikes = $this->getNearbyStrikes($stock['id'], $cmp);
            $stockDebug['strikes_found'] = count($strikes);
            
            if (count($strikes) == 0) {
                $stockDebug['error'] = 'No strikes found in database within range';
                // Add range info
                $range = $cmp * 0.10;
                $stockDebug['strike_range'] = [
                    'min' => round($cmp - $range, 2),
                    'max' => round($cmp + $range, 2)
                ];
            }
            
            // Greeks Cache logic...
            $greeksData = [];
           
            foreach ($strikes as $strikeObj) {
                $debug['contracts_checked']++;
                
                // 3. Use REAL Token
                $symbolToken = $strikeObj['token']; 
                
                // Fetch candles
                $candles = $this->api->getOHLC($symbolToken, $timeframe, $fromDate, $toDate);
                
                if (count($candles) < 2) continue;
                
                $debug['candles_analyzed'] += count($candles);

                // 4. Signal Logic
                $latest = $candles[count($candles) - 1];
                $previous = $candles[count($candles) - 2];

                // Rule: Latest Close >= threshold * Previous Close
                // User-configurable threshold (default 2.0 = 100% gain)
                if ($latest['close'] >= ($threshold * $previous['close']) && $latest['volume'] > 0) { 
                    $debug['matches_found']++;
                    
                    // Basic Indicators
                    $closes = array_column($candles, 'close');
                    $rsi = Indicators::rsi($closes);
                    $latestRsi = !empty($rsi) ? end($rsi) : 0;
                    
                    // EMAs
                    $ema10 = Indicators::ema($closes, 10);
                    $ema20 = Indicators::ema($closes, 20);
                    $ema50 = Indicators::ema($closes, 50);
                    $ema200 = Indicators::ema($closes, 200);

                    // Fetch Greeks
                    $delta = '-'; $iv = '-';
                    // ... (Greeks logic preserved) ...
                    if (isset($strikeObj['expiry'])) {
                         // ... (keep existing greeks logic) ...
                         // Copy fetch logic here if needed or assure it's preserved by surrounding lines
                         $expiry = $strikeObj['expiry'];
                         if (!isset($greeksData[$expiry])) {
                            $greeksData[$expiry] = $this->api->getOptionGreeks($stock['name'], $expiry);
                         }
                         foreach ($greeksData[$expiry] as $g) {
                             if (abs($g['strikePrice'] - $strikeObj['strike']) < 0.1 && $g['optionType'] == $strikeObj['type']) {
                                 $delta = $g['delta'];
                                 $iv = $g['impliedVolatility'];
                                 break;
                             }
                         }
                    }

                    // Format Expiry
                    $expiryStr = '';
                    if (isset($strikeObj['expiry'])) {
                        $expiryStr = date('d M', strtotime($strikeObj['expiry'])); 
                    }
                    $typeFull = ($strikeObj['type'] == 'CE') ? 'CALL' : 'PUT';
                    $contractName = "{$stock['symbol']} $expiryStr {$strikeObj['strike']} $typeFull";

                    $signals[] = [
                        'stock' => $contractName,
                        'contract' => $contractName,
                        'strike' => $strikeObj['strike'],
                        'type' => $typeFull,
                        'pclp' => $previous['close'],
                        'cclp' => $latest['close'],
                        'change_pct' => round((($latest['close'] / $previous['close']) * 100) - 100, 2),
                        
                        'open' => $latest['open'],
                        'high' => $latest['high'],
                        'low' => $latest['low'],
                        'volume' => $latest['volume'],
                        
                        'rsi' => round($latestRsi, 2),
                        'ema10' => !empty($ema10) ? round(end($ema10), 2) : '-',
                        'ema20' => !empty($ema20) ? round(end($ema20), 2) : '-',
                        'ema50' => !empty($ema50) ? round(end($ema50), 2) : '-',
                        'ema200' => !empty($ema200) ? round(end($ema200), 2) : '-',
                        
                        'delta' => $delta,
                        'iv' => $iv,
                        'timestamp' => date('H:i', strtotime($latest['time']))
                    ];
                }
            }
            
            $debug['details'][] = $stockDebug;
        }
        
        $endTime = microtime(true);
        $debug['execution_time'] = round($endTime - $startTime, 2) . 's';
        
        return [
            'signals' => $signals,
            'debug' => $debug
        ];
    }

    private function getUniverse() {
        $stmt = $this->db->query("SELECT * FROM stocks WHERE is_active = 1");
        return $stmt->fetchAll();
    }

    private function getOptionToken($name, $type, $strike) {
        // Find the nearest expiry contract
        // Adjust Name map: NIFTY -> NIFTY, BANKNIFTY -> BANKNIFTY
        // Angel One symbols in Master might be 'NIFTY' or 'NIFTY 50' etc. usually 'NIFTY'
        
        $sql = "SELECT symbol as token FROM option_contracts 
                WHERE name = ? AND option_type = ? AND strike_price = ? 
                AND expiry_date >= CURDATE() 
                ORDER BY expiry_date ASC LIMIT 1";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$name, $type, $strike]);
        return $stmt->fetchColumn();
    }

    private function getStrikes($cmp, $lotSize, $step = null) {
        $strikes = [];
        // Generic Logic: Find nearest strikes from DB
        // Range: +/- 5% of CMP? Or simple limit?
        $range = $cmp * 0.05; // 5% range
        $min = $cmp - $range;
        $max = $cmp + $range;

        // Query available strikes for this stock in this range
        $sql = "SELECT DISTINCT strike_price FROM option_contracts 
                WHERE stock_id = (SELECT id FROM stocks WHERE token = ?) 
                AND strike_price BETWEEN ? AND ?
                AND expiry_date >= CURDATE()
                ORDER BY ABS(strike_price - ?) ASC
                LIMIT 10"; 
                // We order by closeness to CMP and take top 10 (roughly 5 CE/PL pairs effectively)
        
        // Wait, we need Stock ID. getStrikes is called inside loop where we have $stock['symbol']
        // Actually, passed $cmp, $lotSize. We need the stock identifier.
        // Let's change signature of getStrikes or use a helper.
        // Best way: do the query in the main scan loop or pass the stock ID.
        return []; // Replaced by logic in scan()
    }
    
    // Helper to get strikes directly
    private function getNearbyStrikes($stockId, $cmp) {
        $strikes = [];
        
        $range = $cmp * 0.10;
        $min = $cmp - $range;
        $max = $cmp + $range;
        
        // Fetch Token and Expiry too!
        // Group by Strike to avoid duplicates? No, distinct contract.
        // We want the NNear expiry for each strike.
        $sql = "SELECT symbol as token, strike_price, option_type, expiry_date 
                FROM option_contracts 
                WHERE stock_id = ? 
                AND expiry_date >= CURDATE() 
                AND strike_price BETWEEN ? AND ? 
                ORDER BY expiry_date ASC, ABS(strike_price - ?) ASC LIMIT 16";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$stockId, $min, $max, $cmp]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $r) {
            $strikes[] = [
                'token' => $r['token'],
                'strike' => $r['strike_price'], 
                'type' => $r['option_type'],
                'expiry' => $r['expiry_date']
            ];
        }
        return $strikes;
    }
}
