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
     * @return array Matches found
     */
    public function scan($timeframe = '1min') {
        $signals = [];
        $stocks = $this->getUniverse();

        foreach ($stocks as $stock) {
            // 1. Get Stock CMP
            $cmp = $this->api->getMarketQuote($stock['symbol']);
            if (!$cmp) continue;

            // 2. Identify Strikes (5 CE + 5 PE around CMP)
            // Ideally we fetch the option chain or calculate strikes if valid steps are known
            // For this implementation, let's assume we have a helper to get valid strikes or we just calculate logical ones.
            $strikes = $this->getStrikes($cmp, $stock['lot_size'], 100); // Assuming 100 strike gap for simplicity for now

            foreach ($strikes as $strikeObj) {
                // 3. Fetch OHLC for this Option Contract
                // We need to construct the symbol for the option, e.g. NIFTY23JAN18000CE
                // In a real app we'd map this from the DB or API.
                $optSymbol = $stock['symbol'] . date('yM') . $strikeObj['strike'] . $strikeObj['type']; // Pseudo-format
                
                // Fetch candles
                $candles = $this->api->getOHLC($optSymbol, $timeframe, 'today', 'now');
                if (count($candles) < 2) continue;

                // 4. Signal Logic
                $latest = $candles[count($candles) - 1];
                $previous = $candles[count($candles) - 2];

                // Rule: latest_close >= 2 * previous_close
                if ($latest['close'] >= (2 * $previous['close'])) {
                    
                    // Basic Indicators
                    $rsi = Indicators::rsi(array_column($candles, 'close'));
                    $latestRsi = end($rsi);

                    $signals[] = [
                        'stock' => $stock['symbol'],
                        'contract' => $optSymbol,
                        'strike' => $strikeObj['strike'],
                        'type' => $strikeObj['type'],
                        'price' => $latest['close'],
                        'prev_close' => $previous['close'],
                        'change_pct' => round((($latest['close'] - $previous['close']) / $previous['close']) * 100, 2),
                        'rsi' => round($latestRsi, 2),
                        'timestamp' => $latest['time']
                    ];
                }
            }
        }
        return $signals;
    }

    private function getUniverse() {
        $stmt = $this->db->query("SELECT * FROM stocks WHERE is_active = 1");
        return $stmt->fetchAll();
    }

    private function getStrikes($cmp, $lotSize, $step = 50) {
        $strikes = [];
        // Center strike
        $center = round($cmp / $step) * $step;

        for ($i = -5; $i <= 5; $i++) {
            if ($i == 0) continue; // Skip ATM if desired, or keep. Prompt said "5 CE + 5 PE around CMP", usually implies OTMS/ITMs
             
            $strikePrice = $center + ($i * $step);
            $strikes[] = ['strike' => $strikePrice, 'type' => 'CE'];
            $strikes[] = ['strike' => $strikePrice, 'type' => 'PE'];
        }
        return $strikes;
    }
}
