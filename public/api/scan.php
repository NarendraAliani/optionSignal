<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/Settings.php';
require_once __DIR__ . '/../../src/MarketAPI.php';
require_once __DIR__ . '/../../src/SignalEngine.php';

try {
    // Initialize components
    $settings = new Settings();
    $apiKey = $settings->get('api_key') ?? 'demo_key'; // Fallback for testing
    $apiUrl = $settings->get('api_url') ?? 'https://api.example.com';
    
    $marketApi = new MarketAPI($apiKey, $apiUrl);
    $engine = new SignalEngine($marketApi);

    $timeframe = $_GET['timeframe'] ?? '1min';
    
    // Run Scan
    $results = $engine->scan($timeframe);

    echo json_encode([
        'status' => 'success',
        'data' => $results
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
