<?php
/**
 * Money Helper
 * Handles money formatting and calculations with proper rounding
 */

class Money
{
    /**
     * Format money value to 2 decimal places
     */
    public static function format($amount, $currency = 'GH₵', $decimals = 2)
    {
        if ($amount === null || $amount === '') {
            return $currency . '0.00';
        }
        
        return $currency . number_format((float)$amount, $decimals, '.', ',');
    }

    /**
     * Round money value to 2 decimal places
     */
    public static function round($amount, $decimals = 2)
    {
        if ($amount === null || $amount === '') {
            return 0.00;
        }
        
        return round((float)$amount, $decimals);
    }

    /**
     * Parse money input (remove currency symbols, commas)
     */
    public static function parse($input)
    {
        if ($input === null || $input === '') {
            return 0.00;
        }
        
        // Remove currency symbols and commas
        $cleaned = preg_replace('/[^\d.-]/', '', $input);
        
        return (float)$cleaned;
    }

    /**
     * Add two money values with proper rounding
     */
    public static function add($amount1, $amount2)
    {
        return self::round(self::parse($amount1) + self::parse($amount2));
    }

    /**
     * Subtract two money values with proper rounding
     */
    public static function subtract($amount1, $amount2)
    {
        return self::round(self::parse($amount1) - self::parse($amount2));
    }

    /**
     * Multiply money value by a factor with proper rounding
     */
    public static function multiply($amount, $factor)
    {
        return self::round(self::parse($amount) * (float)$factor);
    }

    /**
     * Divide money value by a factor with proper rounding
     */
    public static function divide($amount, $factor)
    {
        if ($factor == 0) {
            return 0.00;
        }
        
        return self::round(self::parse($amount) / (float)$factor);
    }

    /**
     * Calculate percentage of money value
     */
    public static function percentage($amount, $percentage)
    {
        return self::multiply($amount, $percentage / 100);
    }

    /**
     * Compare two money values (returns -1, 0, or 1)
     */
    public static function compare($amount1, $amount2)
    {
        $a1 = self::parse($amount1);
        $a2 = self::parse($amount2);
        
        if ($a1 < $a2) return -1;
        if ($a1 > $a2) return 1;
        return 0;
    }

    /**
     * Check if two money values are equal
     */
    public static function equals($amount1, $amount2)
    {
        return self::compare($amount1, $amount2) === 0;
    }

    /**
     * Get absolute value of money amount
     */
    public static function abs($amount)
    {
        return self::round(abs(self::parse($amount)));
    }

    /**
     * Check if amount is positive
     */
    public static function isPositive($amount)
    {
        return self::parse($amount) > 0;
    }

    /**
     * Check if amount is negative
     */
    public static function isNegative($amount)
    {
        return self::parse($amount) < 0;
    }

    /**
     * Check if amount is zero
     */
    public static function isZero($amount)
    {
        return self::parse($amount) == 0;
    }

    /**
     * Sum array of money values
     */
    public static function sum($amounts)
    {
        $total = 0.00;
        
        foreach ($amounts as $amount) {
            $total = self::add($total, $amount);
        }
        
        return $total;
    }

    /**
     * Get minimum value from array of money amounts
     */
    public static function min($amounts)
    {
        if (empty($amounts)) {
            return 0.00;
        }
        
        $min = self::parse($amounts[0]);
        
        foreach ($amounts as $amount) {
            $parsed = self::parse($amount);
            if ($parsed < $min) {
                $min = $parsed;
            }
        }
        
        return self::round($min);
    }

    /**
     * Get maximum value from array of money amounts
     */
    public static function max($amounts)
    {
        if (empty($amounts)) {
            return 0.00;
        }
        
        $max = self::parse($amounts[0]);
        
        foreach ($amounts as $amount) {
            $parsed = self::parse($amount);
            if ($parsed > $max) {
                $max = $parsed;
            }
        }
        
        return self::round($max);
    }

    /**
     * Calculate average of money amounts
     */
    public static function average($amounts)
    {
        if (empty($amounts)) {
            return 0.00;
        }
        
        return self::divide(self::sum($amounts), count($amounts));
    }

    /**
     * Format for input field (no currency symbol, proper decimal)
     */
    public static function formatForInput($amount)
    {
        if ($amount === null || $amount === '') {
            return '0.00';
        }
        
        return number_format((float)$amount, 2, '.', '');
    }

    /**
     * Format for display in tables (with currency, proper formatting)
     */
    public static function formatForDisplay($amount, $currency = 'GH₵')
    {
        return self::format($amount, $currency);
    }

    /**
     * Format percentage
     */
    public static function formatPercentage($rate, $decimals = 2)
    {
        if ($rate === null || $rate === '') {
            return '0.00%';
        }
        
        return number_format((float)$rate * 100, $decimals, '.', ',') . '%';
    }

    /**
     * Convert percentage to decimal
     */
    public static function percentageToDecimal($percentage)
    {
        return self::round((float)$percentage / 100, 4);
    }

    /**
     * Convert decimal to percentage
     */
    public static function decimalToPercentage($decimal)
    {
        return self::round((float)$decimal * 100, 2);
    }
}
