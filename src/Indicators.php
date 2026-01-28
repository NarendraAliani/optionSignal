<?php

class Indicators {
    
    /**
     * Calculate EMA
     * @param array $prices Array of close prices
     * @param int $period
     * @return array
     */
    public static function ema($prices, $period) {
        $ema = [];
        $k = 2 / ($period + 1);
        
        // First EMA is SMA
        $sma = array_sum(array_slice($prices, 0, $period)) / $period;
        $ema[$period - 1] = $sma;

        for ($i = $period; $i < count($prices); $i++) {
            $ema[$i] = ($prices[$i] * $k) + ($ema[$i - 1] * (1 - $k));
        }
        
        return $ema;
    }

    /**
     * Calculate RSI
     * @param array $prices
     * @param int $period
     * @return array
     */
    public static function rsi($prices, $period = 14) {
        $rsi = [];
        $gains = [];
        $losses = [];

        for ($i = 1; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }

        // First AVG Gain/Loss
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        // First RSI
        $rs = $avgLoss == 0 ? 0 : $avgGain / $avgLoss;
        $rsi[$period] = 100 - (100 / (1 + $rs));

        // Subsequent RSI (Smoothed)
        for ($i = $period + 1; $i < count($prices); $i++) {
            $currentGain = $gains[$i - 1];
            $currentLoss = $losses[$i - 1];

            $avgGain = (($avgGain * ($period - 1)) + $currentGain) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $currentLoss) / $period;

            $rs = $avgLoss == 0 ? 0 : $avgGain / $avgLoss;
            $rsi[$i] = 100 - (100 / (1 + $rs));
        }

        return $rsi;
    }
}
