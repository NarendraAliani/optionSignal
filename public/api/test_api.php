<?php
// Simple API Test - Returns JSON
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/MarketAPI.php';
require_once __DIR__ . '/../../src/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Check DB
    $stmt = $db->query("SELECT COUNT(*) as stock_count FROM stocks WHERE is_active = 1");
    $stockCount = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as option_count FROM option_contracts WHERE expiry_date >= CURDATE()");
    $optionCount = $stmt->fetchColumn();
    
    // 2. Get one stock
    $stmt = $db->query("SELECT * FROM stocks WHERE is_active = 1 LIMIT 1");
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stock) {
        echo json_encode(['error' => 'No stocks in database']);
        exit;
    }
    
    // 3. Test API
    $api = new MarketAPI();
    $cmp = $api->getMarketQuote($stock['token']);
    
    echo json_encode([
        'status' => 'success',
        'database' => [
            'stocks' => $stockCount,
            'options' => $optionCount
        ],
        'test_stock' => [
            'name' => $stock['name'],
            'symbol' => $stock['symbol'],
            'token' => $stock['token']
        ],
        'api_response' => [
            'cmp' => $cmp,
            'cmp_type' => gettype($cmp),
            'is_valid' => ($cmp > 0)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
