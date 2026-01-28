<?php
// tests/test_logic.php

require_once __DIR__ . '/../src/Indicators.php';

echo "Testing Indicators...\n";

// Test EMA
$prices = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
$ema = Indicators::ema($prices, 5);
echo "EMA(5) last value: " . end($ema) . " (Expected approx 18-19)\n";

// Test RSI
// Simple uptrend
$rsiPrices = [10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40];
$rsi = Indicators::rsi($rsiPrices, 14);
echo "RSI(14) last value: " . end($rsi) . " (Expected 100 or very high)\n";

echo "\nTesting Signal Logic Condition (2x)...\n";
$prevClose = 100;
$currClose = 200;

if ($currClose >= (2 * $prevClose)) {
    echo "Signal PASS: 200 >= 2 * 100\n";
} else {
    echo "Signal FAIL\n";
}

$currClose = 199;
if ($currClose >= (2 * $prevClose)) {
    echo "Signal PASS: 199 >= 2 * 100\n";
} else {
    echo "Signal REJECTED (Correct): 199 < 200\n";
}
