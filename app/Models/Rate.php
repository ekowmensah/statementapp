<?php
/**
 * Rate Model
 * Handles rate data operations and effective rate calculations
 */

class Rate
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find rate by ID
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             WHERE r.id = ?",
            [$id]
        );
    }

    /**
     * Get all rates
     */
    public function getAll($limit = null, $offset = 0)
    {
        $sql = "SELECT r.*, u.name as created_by_name 
                FROM rates r 
                LEFT JOIN users u ON r.created_by = u.id 
                ORDER BY r.effective_on DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Get effective rate for a specific date
     */
    public function getEffectiveRate($date)
    {
        return $this->db->fetch(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             WHERE r.effective_on <= ? 
             ORDER BY r.effective_on DESC 
             LIMIT 1",
            [$date]
        );
    }

    /**
     * Create new rate
     */
    public function create($data)
    {
        return $this->db->insert(
            "INSERT INTO rates (effective_on, rate_ag1, rate_ag2, note, created_by) 
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['effective_on'],
                $data['rate_ag1'],
                $data['rate_ag2'],
                $data['note'] ?? null,
                $data['created_by']
            ]
        );
    }

    /**
     * Update rate
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        
        if (isset($data['effective_on'])) {
            $fields[] = 'effective_on = ?';
            $params[] = $data['effective_on'];
        }
        
        if (isset($data['rate_ag1'])) {
            $fields[] = 'rate_ag1 = ?';
            $params[] = $data['rate_ag1'];
        }
        
        if (isset($data['rate_ag2'])) {
            $fields[] = 'rate_ag2 = ?';
            $params[] = $data['rate_ag2'];
        }
        
        if (isset($data['note'])) {
            $fields[] = 'note = ?';
            $params[] = $data['note'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        
        return $this->db->update(
            "UPDATE rates SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
    }

    /**
     * Delete rate
     */
    public function delete($id)
    {
        return $this->db->delete("DELETE FROM rates WHERE id = ?", [$id]);
    }

    /**
     * Check if effective date exists
     */
    public function effectiveDateExists($date, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM rates WHERE effective_on = ?";
        $params = [$date];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Get rates for date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             WHERE r.effective_on BETWEEN ? AND ? 
             ORDER BY r.effective_on ASC",
            [$startDate, $endDate]
        );
    }

    /**
     * Get latest rate
     */
    public function getLatest()
    {
        return $this->db->fetch(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             ORDER BY r.effective_on DESC 
             LIMIT 1"
        );
    }

    /**
     * Get rate history with changes
     */
    public function getHistory($limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.name as created_by_name,
                    LAG(r.rate_ag1) OVER (ORDER BY r.effective_on) as prev_ag1,
                    LAG(r.rate_ag2) OVER (ORDER BY r.effective_on) as prev_ag2
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             ORDER BY r.effective_on DESC 
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Calculate values using rate
     */
    public function calculateValues($rateId, $ca, $ga, $je)
    {
        $rate = $this->find($rateId);
        
        if (!$rate) {
            throw new Exception("Rate not found");
        }
        
        return $this->calculateWithRate($rate, $ca, $ga, $je);
    }

    /**
     * Calculate values with rate data
     */
    public function calculateWithRate($rate, $ca, $ga, $je)
    {
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
            'fi' => $fi
        ];
    }

    /**
     * Get rates that affect transactions in date range
     */
    public function getAffectingRates($startDate, $endDate)
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             WHERE r.effective_on <= ? 
             AND (
                 SELECT COUNT(*) FROM rates r2 
                 WHERE r2.effective_on > r.effective_on 
                 AND r2.effective_on <= ?
             ) = 0
             OR r.effective_on BETWEEN ? AND ?
             ORDER BY r.effective_on ASC",
            [$endDate, $endDate, $startDate, $endDate]
        );
    }

    /**
     * Validate rate data
     */
    public function validate($data, $excludeId = null)
    {
        $validator = new Validate($data);
        
        $validator
            ->required('effective_on', 'Effective date is required')
            ->date('effective_on', 'Y-m-d', 'Effective date must be a valid date')
            ->required('rate_ag1', 'AG1 rate is required')
            ->numeric('rate_ag1', 'AG1 rate must be a number')
            ->min('rate_ag1', 0, 'AG1 rate must be at least 0')
            ->max('rate_ag1', 1, 'AG1 rate cannot exceed 1')
            ->required('rate_ag2', 'AG2 rate is required')
            ->numeric('rate_ag2', 'AG2 rate must be a number')
            ->min('rate_ag2', 0, 'AG2 rate must be at least 0')
            ->max('rate_ag2', 1, 'AG2 rate cannot exceed 1');

        // Check unique effective date
        if (isset($data['effective_on'])) {
            $validator->custom('effective_on', function($value) use ($excludeId) {
                return !$this->effectiveDateExists($value, $excludeId);
            }, 'A rate already exists for this effective date');
        }

        return $validator;
    }

    /**
     * Get count of rates
     */
    public function getCount()
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM rates");
        return $result['count'];
    }

    /**
     * Search rates
     */
    public function search($query, $limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.name as created_by_name 
             FROM rates r 
             LEFT JOIN users u ON r.created_by = u.id 
             WHERE r.note LIKE ? OR r.effective_on LIKE ? 
             ORDER BY r.effective_on DESC 
             LIMIT ?",
            ["%{$query}%", "%{$query}%", $limit]
        );
    }

    /**
     * Check if rate is being used by transactions
     */
    public function isInUse($rateId)
    {
        $rate = $this->find($rateId);
        
        if (!$rate) {
            return false;
        }

        // Check if any transactions would use this rate
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM daily_txn 
             WHERE txn_date >= ? 
             AND (
                 SELECT COUNT(*) FROM rates r2 
                 WHERE r2.effective_on > ? 
                 AND r2.effective_on <= txn_date
             ) = 0",
            [$rate['effective_on'], $rate['effective_on']]
        );

        return $result['count'] > 0;
    }
}
