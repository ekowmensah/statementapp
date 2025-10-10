<?php
/**
 * Daily Transaction Model
 * Handles daily transaction data operations
 */

class DailyTxn
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find transaction by ID
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT t.*, 
                    uc.name as created_by_name, 
                    uu.name as updated_by_name 
             FROM daily_txn t 
             LEFT JOIN users uc ON t.created_by = uc.id 
             LEFT JOIN users uu ON t.updated_by = uu.id 
             WHERE t.id = ?",
            [$id]
        );
    }

    /**
     * Find transaction by date (returns first transaction)
     */
    public function findByDate($date)
    {
        return $this->db->fetch(
            "SELECT t.*, 
                    uc.name as created_by_name, 
                    uu.name as updated_by_name 
             FROM daily_txn t 
             LEFT JOIN users uc ON t.created_by = uc.id 
             LEFT JOIN users uu ON t.updated_by = uu.id 
             WHERE t.txn_date = ? 
             ORDER BY t.sequence_number ASC 
             LIMIT 1",
            [$date]
        );
    }

    /**
     * Find all transactions by date
     */
    public function findAllByDate($date)
    {
        return $this->db->fetchAll(
            "SELECT t.*, 
                    uc.name as created_by_name, 
                    uu.name as updated_by_name 
             FROM daily_txn t 
             LEFT JOIN users uc ON t.created_by = uc.id 
             LEFT JOIN users uu ON t.updated_by = uu.id 
             WHERE t.txn_date = ? 
             ORDER BY t.sequence_number ASC",
            [$date]
        );
    }

    /**
     * Get all transactions with computed values
     */
    public function getAllComputed($limit = null, $offset = 0, $orderBy = 'txn_date DESC')
    {
        $sql = "SELECT * FROM v_daily_txn ORDER BY {$orderBy}";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Get transactions for date range with computed values
     */
    public function getByDateRangeComputed($startDate, $endDate, $orderBy = 'txn_date ASC')
    {
        return $this->db->fetchAll(
            "SELECT * FROM v_daily_txn 
             WHERE txn_date BETWEEN ? AND ? 
             ORDER BY {$orderBy}",
            [$startDate, $endDate]
        );
    }

    /**
     * Get transactions for specific month with computed values
     */
    public function getByMonthComputed($year, $month)
    {
        return $this->db->fetchAll(
            "SELECT * FROM v_daily_txn 
             WHERE YEAR(txn_date) = ? AND MONTH(txn_date) = ? 
             ORDER BY txn_date ASC",
            [$year, $month]
        );
    }

    /**
     * Create new transaction
     */
    public function create($data)
    {
        // Get the next sequence number for this date
        $sequenceNumber = $this->getNextSequenceNumber($data['txn_date']);
        
        return $this->db->insert(
            "INSERT INTO daily_txn (txn_date, sequence_number, ca, ga, je, gai_ga, company_id, note, created_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['txn_date'],
                $sequenceNumber,
                $data['ca'],
                $data['ga'],
                $data['je'],
                $data['gai_ga'] ?? 0.00, // GAI GA field with default 0
                $data['company_id'], // Now required, no null fallback
                $data['note'] ?? null,
                $data['created_by']
            ]
        );
    }

    /**
     * Get the next sequence number for a given date
     */
    private function getNextSequenceNumber($date)
    {
        $result = $this->db->fetch(
            "SELECT COALESCE(MAX(sequence_number), 0) + 1 as next_sequence 
             FROM daily_txn 
             WHERE txn_date = ?",
            [$date]
        );
        
        return $result['next_sequence'];
    }

    /**
     * Update transaction
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        
        if (isset($data['txn_date'])) {
            $fields[] = 'txn_date = ?';
            $params[] = $data['txn_date'];
        }
        
        if (isset($data['ca'])) {
            $fields[] = 'ca = ?';
            $params[] = $data['ca'];
        }
        
        if (isset($data['ga'])) {
            $fields[] = 'ga = ?';
            $params[] = $data['ga'];
        }
        
        if (isset($data['je'])) {
            $fields[] = 'je = ?';
            $params[] = $data['je'];
        }
        
        if (isset($data['gai_ga'])) {
            $fields[] = 'gai_ga = ?';
            $params[] = $data['gai_ga'];
        }
        
        if (isset($data['company_id'])) {
            $fields[] = 'company_id = ?';
            $params[] = $data['company_id'];
        }
        
        if (isset($data['note'])) {
            $fields[] = 'note = ?';
            $params[] = $data['note'];
        }
        
        if (isset($data['updated_by'])) {
            $fields[] = 'updated_by = ?';
            $params[] = $data['updated_by'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        
        return $this->db->update(
            "UPDATE daily_txn SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
    }

    /**
     * Delete transaction
     */
    public function delete($id)
    {
        return $this->db->delete("DELETE FROM daily_txn WHERE id = ?", [$id]);
    }

    /**
     * Check if date exists (now returns count of transactions for that date)
     */
    public function dateExists($date, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM daily_txn WHERE txn_date = ?";
        $params = [$date];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }

    /**
     * Check if a specific date and sequence combination exists
     */
    public function dateSequenceExists($date, $sequenceNumber, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM daily_txn WHERE txn_date = ? AND sequence_number = ?";
        $params = [$date, $sequenceNumber];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Get monthly totals
     */
    public function getMonthlyTotals($year, $month)
    {
        return $this->db->fetch(
            "SELECT * FROM v_monthly_totals 
             WHERE year_num = ? AND month_num = ?",
            [$year, $month]
        );
    }

    /**
     * Get yearly totals
     */
    public function getYearlyTotals($year)
    {
        return $this->db->fetch(
            "SELECT 
                 SUM(total_ca) as total_ca,
                 SUM(total_ag1) as total_ag1,
                 SUM(total_av1) as total_av1,
                 SUM(total_ag2) as total_ag2,
                 SUM(total_av2) as total_av2,
                 SUM(total_ga) as total_ga,
                 SUM(total_re) as total_re,
                 SUM(total_je) as total_je,
                 SUM(total_fi) as total_fi,
                 SUM(days_count) as total_days
             FROM v_monthly_totals 
             WHERE year_num = ?",
            [$year]
        );
    }

    /**
     * Get recent transactions
     */
    public function getRecent($limit = 10)
    {
        return $this->db->fetchAll(
            "SELECT * FROM v_daily_txn 
             ORDER BY txn_date DESC 
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Get KPI data for dashboard
     */
    public function getKPIs($year, $month)
    {
        $monthlyTotals = $this->getMonthlyTotals($year, $month);
        
        if (!$monthlyTotals) {
            return [
                'mtd_ca' => 0,
                'mtd_ga' => 0,
                'mtd_je' => 0,
                'mtd_fi' => 0,
                'days_count' => 0
            ];
        }
        
        return [
            'mtd_ca' => $monthlyTotals['total_ca'],
            'mtd_ga' => $monthlyTotals['total_ga'],
            'mtd_je' => $monthlyTotals['total_je'],
            'mtd_fi' => $monthlyTotals['total_fi'],
            'days_count' => $monthlyTotals['days_count']
        ];
    }

    /**
     * Get chart data for dashboard
     */
    public function getChartData($year, $month)
    {
        $transactions = $this->getByMonthComputed($year, $month);
        
        $chartData = [
            'labels' => [],
            'fi_data' => [],
            'ca_data' => []
        ];
        
        foreach ($transactions as $txn) {
            $chartData['labels'][] = date('M j', strtotime($txn['txn_date']));
            $chartData['fi_data'][] = (float)$txn['fi'];
            $chartData['ca_data'][] = (float)$txn['ca'];
        }
        
        return $chartData;
    }

    /**
     * Validate transaction data
     */
    public function validate($data, $excludeId = null)
    {
        $validator = new Validate($data);
        
        $validator
            ->required('txn_date', 'Transaction date is required')
            ->date('txn_date', 'Y-m-d', 'Transaction date must be a valid date')
            ->required('ca', 'CA amount is required')
            ->numeric('ca', 'CA amount must be a number')
            ->min('ca', 0, 'CA amount must be at least 0')
            ->decimal('ca', 2, 'CA amount cannot have more than 2 decimal places')
            ->required('ga', 'GA amount is required')
            ->numeric('ga', 'GA amount must be a number')
            ->min('ga', 0, 'GA amount must be at least 0')
            ->decimal('ga', 2, 'GA amount cannot have more than 2 decimal places')
            ->required('je', 'JE amount is required')
            ->numeric('je', 'JE amount must be a number')
            ->min('je', 0, 'JE amount must be at least 0')
            ->decimal('je', 2, 'JE amount cannot have more than 2 decimal places')
            ->numeric('gai_ga', 'GAI GA amount must be a number')
            ->min('gai_ga', 0, 'GAI GA amount must be at least 0')
            ->decimal('gai_ga', 2, 'GAI GA amount cannot have more than 2 decimal places')
            ->required('company_id', 'Company is required')
            ->numeric('company_id', 'Please select a valid company')
            ->required('rate_ag1', 'AG1 rate is required')
            ->numeric('rate_ag1', 'AG1 rate must be a number')
            ->min('rate_ag1', 0, 'AG1 rate must be at least 0')
            ->max('rate_ag1', 100, 'AG1 rate cannot exceed 100%')
            ->required('rate_ag2', 'AG2 rate is required')
            ->numeric('rate_ag2', 'AG2 rate must be a number')
            ->min('rate_ag2', 0, 'AG2 rate must be at least 0')
            ->max('rate_ag2', 100, 'AG2 rate cannot exceed 100%');

        // Note: Removed unique date validation since we now allow multiple transactions per date
        // The database will handle uniqueness via the (txn_date, sequence_number) constraint

        return $validator;
    }

    /**
     * Get count of transactions
     */
    public function getCount()
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM daily_txn");
        return $result['count'];
    }

    /**
     * Search transactions
     */
    public function search($query, $limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT * FROM v_daily_txn 
             WHERE company_name LIKE ? OR note LIKE ? OR txn_date LIKE ? 
             ORDER BY txn_date DESC 
             LIMIT ?",
            ["%{$query}%", "%{$query}%", "%{$query}%", $limit]
        );
    }

    /**
     * Get transactions for export
     */
    public function getForExport($startDate, $endDate)
    {
        return $this->db->fetchAll(
            "SELECT 
                 v.txn_date as 'Date',
                 v.ca as 'CA',
                 v.ag1 as 'AG1',
                 v.av1 as 'AV1',
                 v.ag2 as 'AG2',
                 v.av2 as 'AV2',
                 v.ga as 'GA',
                 v.re as 'RE',
                 v.je as 'JE',
                 v.fi as 'FI',
                 v.gai_ga as 'GAI GA',
                 c.name as 'COMPANY',
                 v.note as 'Note'
             FROM v_daily_txn v
             LEFT JOIN companies c ON v.company_id = c.id
             WHERE v.txn_date BETWEEN ? AND ? 
             ORDER BY v.txn_date ASC",
            [$startDate, $endDate]
        );
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStats($startDate = null, $endDate = null)
    {
        $whereClause = '';
        $params = [];
        
        if ($startDate && $endDate) {
            $whereClause = 'WHERE txn_date BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
        }
        
        return $this->db->fetch(
            "SELECT 
                 COUNT(*) as total_days,
                 ROUND(SUM(ca), 2) as total_ca,
                 ROUND(SUM(ga), 2) as total_ga,
                 ROUND(SUM(je), 2) as total_je,
                 ROUND(SUM(fi), 2) as total_fi,
                 ROUND(AVG(ca), 2) as avg_ca,
                 ROUND(AVG(fi), 2) as avg_fi,
                 ROUND(MAX(ca), 2) as max_ca,
                 ROUND(MIN(ca), 2) as min_ca
             FROM v_daily_txn 
             {$whereClause}",
            $params
        );
    }

    /**
     * Check if month is locked for transaction date
     */
    public function isMonthLocked($date)
    {
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));
        
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM month_locks 
             WHERE year_num = ? AND month_num = ?",
            [$year, $month]
        );
        
        return $result['count'] > 0;
    }

    /**
     * Get transactions by company
     */
    public function getByCompany($companyId, $limit = null, $offset = 0)
    {
        $sql = "SELECT * FROM v_daily_txn WHERE company_id = ? ORDER BY txn_date DESC, sequence_number ASC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$companyId, $limit, $offset]);
        }
        
        return $this->db->fetchAll($sql, [$companyId]);
    }

    /**
     * Get daily totals (aggregated by date)
     */
    public function getDailyTotals($startDate = null, $endDate = null, $orderBy = 'txn_date DESC')
    {
        $whereClause = '';
        $params = [];
        
        if ($startDate && $endDate) {
            $whereClause = 'WHERE txn_date BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
        }
        
        return $this->db->fetchAll(
            "SELECT * FROM v_daily_totals {$whereClause} ORDER BY {$orderBy}",
            $params
        );
    }

    /**
     * Get transaction count for a specific date
     */
    public function getTransactionCountByDate($date)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM daily_txn WHERE txn_date = ?",
            [$date]
        );
        return $result['count'];
    }

    /**
     * Get transactions count by company
     */
    public function getCountByCompany($companyId)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM daily_txn WHERE company_id = ?",
            [$companyId]
        );
        return $result['count'];
    }
}
