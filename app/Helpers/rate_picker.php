<?php
/**
 * Rate Picker Helper
 * Finds the effective rate for a given date
 */

class RatePicker
{
    /**
     * Get effective rate for a specific date
     * Returns the most recent rate where effective_on <= date
     */
    public static function getEffectiveRate($date)
    {
        $db = Database::getInstance();
        
        $rate = $db->fetch(
            "SELECT * FROM rates 
             WHERE effective_on <= ? 
             ORDER BY effective_on DESC 
             LIMIT 1",
            [$date]
        );
        
        return $rate;
    }

    /**
     * Get effective rates for multiple dates (optimized for bulk operations)
     */
    public static function getEffectiveRatesForDates($dates)
    {
        if (empty($dates)) {
            return [];
        }
        
        $db = Database::getInstance();
        $rates = [];
        
        // Sort dates to optimize queries
        sort($dates);
        
        foreach ($dates as $date) {
            $rate = self::getEffectiveRate($date);
            $rates[$date] = $rate;
        }
        
        return $rates;
    }

    /**
     * Calculate computed values for given inputs and date
     */
    public static function calculateValues($date, $ca, $ga, $je)
    {
        $rate = self::getEffectiveRate($date);
        
        if (!$rate) {
            throw new Exception("No effective rate found for date: {$date}");
        }
        
        // Parse inputs
        $ca = Money::parse($ca);
        $ga = Money::parse($ga);
        $je = Money::parse($je);
        
        // Get rates
        $rateAg1 = (float)$rate['rate_ag1'];
        $rateAg2 = (float)$rate['rate_ag2'];
        
        // Calculate cascade with proper rounding
        $ag1 = Money::multiply($ca, $rateAg1);
        $av1 = Money::subtract($ca, $ag1);
        $ag2 = Money::multiply($av1, $rateAg2);
        $av2 = Money::subtract($av1, $ag2);
        $re = Money::subtract($av2, $ga);
        $fi = Money::subtract($re, $je);
        
        return [
            'date' => $date,
            'ca' => $ca,
            'ga' => $ga,
            'je' => $je,
            'rate_ag1' => $rateAg1,
            'rate_ag2' => $rateAg2,
            'ag1' => $ag1,
            'av1' => $av1,
            'ag2' => $ag2,
            'av2' => $av2,
            're' => $re,
            'fi' => $fi,
            'rate_info' => $rate
        ];
    }

    /**
     * Get all rates for dropdown/selection
     */
    public static function getAllRates()
    {
        $db = Database::getInstance();
        
        return $db->fetchAll(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             ORDER BY r.effective_on DESC"
        );
    }

    /**
     * Get rate by ID
     */
    public static function getRateById($id)
    {
        $db = Database::getInstance();
        
        return $db->fetch(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             WHERE r.id = ?",
            [$id]
        );
    }

    /**
     * Check if a rate exists for a specific date
     */
    public static function rateExistsForDate($date, $excludeId = null)
    {
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(*) as count FROM rates WHERE effective_on = ?";
        $params = [$date];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $db->fetch($sql, $params);
        
        return $result['count'] > 0;
    }

    /**
     * Get rate history for a date range
     */
    public static function getRateHistory($startDate, $endDate)
    {
        $db = Database::getInstance();
        
        return $db->fetchAll(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             WHERE r.effective_on BETWEEN ? AND ? 
             ORDER BY r.effective_on ASC",
            [$startDate, $endDate]
        );
    }

    /**
     * Get the most recent rate
     */
    public static function getLatestRate()
    {
        $db = Database::getInstance();
        
        return $db->fetch(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             ORDER BY r.effective_on DESC 
             LIMIT 1"
        );
    }

    /**
     * Validate rate values
     */
    public static function validateRate($rateAg1, $rateAg2)
    {
        $errors = [];
        
        // Convert to float
        $ag1 = (float)$rateAg1;
        $ag2 = (float)$rateAg2;
        
        // Check AG1 rate
        if ($ag1 < 0 || $ag1 > 1) {
            $errors[] = 'AG1 rate must be between 0 and 1 (0% to 100%)';
        }
        
        // Check AG2 rate
        if ($ag2 < 0 || $ag2 > 1) {
            $errors[] = 'AG2 rate must be between 0 and 1 (0% to 100%)';
        }
        
        // Business rule: AG1 + AG2 should not exceed 100% (optional check)
        if ($ag1 + $ag2 > 1) {
            $errors[] = 'Combined AG1 and AG2 rates should not exceed 100%';
        }
        
        return $errors;
    }

    /**
     * Format rate for display
     */
    public static function formatRate($rate, $decimals = 2)
    {
        return Money::formatPercentage($rate, $decimals);
    }

    /**
     * Get rate changes between two dates
     */
    public static function getRateChanges($startDate, $endDate)
    {
        $db = Database::getInstance();
        
        $rates = $db->fetchAll(
            "SELECT r.*, u.name as created_by_name,
                    LAG(r.rate_ag1) OVER (ORDER BY r.effective_on) as prev_ag1,
                    LAG(r.rate_ag2) OVER (ORDER BY r.effective_on) as prev_ag2
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             WHERE r.effective_on BETWEEN ? AND ? 
             ORDER BY r.effective_on ASC",
            [$startDate, $endDate]
        );
        
        $changes = [];
        
        foreach ($rates as $rate) {
            $change = [
                'date' => $rate['effective_on'],
                'ag1_rate' => $rate['rate_ag1'],
                'ag2_rate' => $rate['rate_ag2'],
                'created_by' => $rate['created_by_name'],
                'note' => $rate['note']
            ];
            
            // Calculate changes from previous rate
            if ($rate['prev_ag1'] !== null) {
                $change['ag1_change'] = $rate['rate_ag1'] - $rate['prev_ag1'];
                $change['ag2_change'] = $rate['rate_ag2'] - $rate['prev_ag2'];
            }
            
            $changes[] = $change;
        }
        
        return $changes;
    }
}
