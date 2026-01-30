<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/Settings.php';
require_once __DIR__ . '/../../src/MarketAPI.php';
require_once __DIR__ . '/../../src/SignalEngine.php';

try {
    $timeframe = $_GET['timeframe'] ?? '15min';
    $mode = $_GET['mode'] ?? 'live';
    $threshold = floatval($_GET['threshold'] ?? 2.0); // Default 2.0 = 100% gain
    
    // Default Dates
    $fromDate = date('Y-m-d 09:15');
    $toDate = date('Y-m-d H:i');
    
    if ($mode === 'backtest') {
        // User inputs
        $inDate = $_GET['date'] ?? date('Y-m-d', strtotime('-1 day')); // Default yesterday
        $inFromTime = $_GET['fromTime'] ?? '09:15';
        $inToTime = $_GET['toTime'] ?? '15:30';
        
        $fromDate = "$inDate $inFromTime";
        $toDate = "$inDate $inToTime";
    }

    $api = new MarketAPI();
    $engine = new SignalEngine($api);
    
    $results = $engine->scan($timeframe, $fromDate, $toDate, $threshold);
    
    echo json_encode(['status' => 'success', 'data' => $results['signals'], 'debug' => $results['debug']]);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
