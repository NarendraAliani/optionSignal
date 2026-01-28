<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/Settings.php';

class MarketAPI {
    private $marketKey;
    private $histKey;
    private $accessToken;
    private $baseUrl = 'https://apiconnect.angelbroking.com';

    public function __construct() {
        $settings = new Settings();
        $this->marketKey = $settings->get('market_api_key');
        $this->histKey = $settings->get('hist_api_key');
        $this->accessToken = $settings->get('access_token');
    }

    /**
     * Fetch OHLC Data from Angel One
     * Endpoint: /rest/secure/angelbroking/historical/v1/getCandleData
     */
    public function getOHLC($symbolToken, $interval, $from, $to) {
        if (empty($this->accessToken) || $this->accessToken == 'demo_key') {
             return $this->generateMockCandles();
        }

        $url = $this->baseUrl . "/rest/secure/angelbroking/historical/v1/getCandleData";
        
        $body = json_encode([
            "exchange" => "NFO", // Options are usually NFO
            "symboltoken" => $symbolToken,
            "interval" => $this->mapInterval($interval),
            "fromdate" => $from, // Format: YYYY-MM-DD HH:mm
            "todate" => $to
        ]);

        $headers = [
            "Content-Type: application/json",
            "X-PrivateKey: " . $this->histKey,
            "Authorization: Mean " . $this->accessToken, // Angel One often uses 'Bearer' or 'Mean', usually Bearer in standard JWT
            "X-ClientLocalIP: 127.0.0.1",
            "X-ClientPublicIP: 127.0.0.1",
            "X-MACAddress: 00:00:00:00:00:00",
            "Accept: application/json",
            "X-UserType: USER",
            "X-SourceID: WEB"
        ];

        // IMPORTANT: Angel One docs say 'Authorization: Bearer <jwt>'
        $headers[2] = "Authorization: Bearer " . $this->accessToken;

        $response = $this->makePostRequest($url, $headers, $body);

        if (isset($response['data'])) {
            return $this->formatCandles($response['data']);
        }
        
        return [];
    }

    /**
     * Get Live Quote
     * Endpoint: /rest/secure/angelbroking/market/v1/quote
     */
    public function getMarketQuote($symbolToken, $exchange = 'NSE') {
        if ($this->accessToken === 'demo_key') return 2000.00;

         $url = $this->baseUrl . "/rest/secure/angelbroking/market/v1/quote";
         $body = json_encode([
             "mode" => "LTP",
             "exchangeTokens" => [
                 $exchange => [$symbolToken]
             ]
         ]);

         $headers = [
            "Content-Type: application/json",
            "X-PrivateKey: " . $this->marketKey,
            "Authorization: Bearer " . $this->accessToken
         ];

         $response = $this->makePostRequest($url, $headers, $body);
         
         if (isset($response['data']['fetched'][0]['ltp'])) {
             return $response['data']['fetched'][0]['ltp'];
         }
         return 0.0;
    }

    private function mapInterval($interval) {
        // Map app interval to SmartAPI interval
        // App: 1min, 3min, 5min
        // SmartAPI: ONE_MINUTE, THREE_MINUTE, FIVE_MINUTE
        $map = [
            '1min' => 'ONE_MINUTE',
            '3min' => 'THREE_MINUTE',
            '5min' => 'FIVE_MINUTE',
            '15min' => 'FIFTEEN_MINUTE',
            '30min' => 'THIRTY_MINUTE',
            '1hour' => 'ONE_HOUR',
            '1day' => 'ONE_DAY'
        ];
        return $map[$interval] ?? 'ONE_MINUTE';
    }

    private function formatCandles($data) {
        // SmartAPI returns: [timestamp, open, high, low, close, volume]
        $formatted = [];
        foreach ($data as $candle) {
            $formatted[] = [
                'time' => $candle[0],
                'open' => $candle[1],
                'high' => $candle[2],
                'low' => $candle[3],
                'close' => $candle[4],
                'volume' => $candle[5]
            ];
        }
        return $formatted;
    }

    private function generateMockCandles() {
        // ... (Keep existing mock logic for fallback) ...
        $candles = [];
        $close = 100; $now = time();
        for ($i = 0; $i < 50; $i++) {
            $time = $now - ((50 - $i) * 60);
            if ($i == 49) { $close = $close * 2.1; } 
            else { $close = $close * (1 + (rand(-10, 10) / 100)); }
            $candles[] = ['time' => date('Y-m-d H:i:s', $time), 'open' => $close, 'high' => $close, 'low' => $close, 'close' => $close, 'volume' => 100];
        }
        return $candles;
    }

    private function makePostRequest($url, $headers, $body) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
