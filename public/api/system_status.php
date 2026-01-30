<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/Settings.php';

try {
    $settings = new Settings();
    $token = $settings->get('access_token');
    
    // 1. Check Token Status (Basic check: is it non-empty?)
    // Ideally we would validate expiry, but for now existence is the proxy for "Connected"
    $isTokenConnected = !empty($token) && strlen($token) > 20;

    // 2. Check Market Status (NSE Hours: 09:15 - 15:30 IST, Mon-Fri)
    $timezone = new DateTimeZone('Asia/Kolkata');
    $now = new DateTime('now', $timezone);
    $currentDay = $now->format('N'); // 1 (Mon) to 7 (Sun)
    $currentTime = $now->format('H:i');

    $isMarketOpen = false;
    if ($currentDay >= 1 && $currentDay <= 5) {
        if ($currentTime >= '09:15' && $currentTime <= '15:30') {
            $isMarketOpen = true;
        }
    }

    echo json_encode([
        'status' => true,
        'token_connected' => $isTokenConnected,
        'market_live' => $isMarketOpen,
        'server_time' => $now->format('d-M-Y H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'error' => $e->getMessage()
    ]);
}
