<?php
/**
 * Month Lock Model
 * Handles month locking operations to prevent edits
 */

class MonthLock
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find lock by ID
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT ml.*, u.name as locked_by_name 
             FROM month_locks ml 
             LEFT JOIN users u ON ml.locked_by = u.id 
             WHERE ml.id = ?",
            [$id]
        );
    }

    /**
     * Find lock by year and month
     */
    public function findByYearMonth($year, $month)
    {
        return $this->db->fetch(
            "SELECT ml.*, u.name as locked_by_name 
             FROM month_locks ml 
             LEFT JOIN users u ON ml.locked_by = u.id 
             WHERE ml.year_num = ? AND ml.month_num = ?",
            [$year, $month]
        );
    }

    /**
     * Get all locks
     */
    public function getAll($limit = null, $offset = 0)
    {
        $sql = "SELECT ml.*, u.name as locked_by_name 
                FROM month_locks ml 
                LEFT JOIN users u ON ml.locked_by = u.id 
                ORDER BY ml.year_num DESC, ml.month_num DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Lock a month
     */
    public function lock($year, $month, $lockedBy, $note = null)
    {
        // Check if already locked
        if ($this->isLocked($year, $month)) {
            throw new Exception("Month {$year}-{$month} is already locked");
        }

        return $this->db->insert(
            "INSERT INTO month_locks (year_num, month_num, locked_by, note) 
             VALUES (?, ?, ?, ?)",
            [$year, $month, $lockedBy, $note]
        );
    }

    /**
     * Unlock a month
     */
    public function unlock($year, $month)
    {
        return $this->db->delete(
            "DELETE FROM month_locks 
             WHERE year_num = ? AND month_num = ?",
            [$year, $month]
        );
    }

    /**
     * Unlock a month by ID with audit trail
     */
    public function unlockById($id, $unlockedBy, $reason = null)
    {
        // First, log the unlock action (you might want to add an audit table)
        // For now, just delete the lock record
        return $this->db->delete(
            "DELETE FROM month_locks WHERE id = ?",
            [$id]
        );
    }

    /**
     * Check if month is locked
     */
    public function isLocked($year, $month)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM month_locks 
             WHERE year_num = ? AND month_num = ?",
            [$year, $month]
        );
        
        return $result['count'] > 0;
    }

    /**
     * Check if date is in locked month
     */
    public function isDateLocked($date)
    {
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));
        
        return $this->isLocked($year, $month);
    }

    /**
     * Get locked months for a year
     */
    public function getLockedMonthsForYear($year)
    {
        return $this->db->fetchAll(
            "SELECT ml.*, u.name as locked_by_name 
             FROM month_locks ml 
             LEFT JOIN users u ON ml.locked_by = u.id 
             WHERE ml.year_num = ? 
             ORDER BY ml.month_num ASC",
            [$year]
        );
    }

    /**
     * Get all locked months with transaction counts
     */
    public function getWithTransactionCounts()
    {
        return $this->db->fetchAll(
            "SELECT ml.*, u.name as locked_by_name,
                    COALESCE(txn_counts.txn_count, 0) as transaction_count
             FROM month_locks ml 
             LEFT JOIN users u ON ml.locked_by = u.id 
             LEFT JOIN (
                 SELECT 
                     YEAR(txn_date) as year_num,
                     MONTH(txn_date) as month_num,
                     COUNT(*) as txn_count
                 FROM daily_txn 
                 GROUP BY YEAR(txn_date), MONTH(txn_date)
             ) txn_counts ON ml.year_num = txn_counts.year_num 
                          AND ml.month_num = txn_counts.month_num
             ORDER BY ml.year_num DESC, ml.month_num DESC"
        );
    }

    /**
     * Get months that can be locked (have transactions)
     */
    public function getLockableMonths()
    {
        return $this->db->fetchAll(
            "SELECT 
                 YEAR(txn_date) as year_num,
                 MONTH(txn_date) as month_num,
                 MONTHNAME(txn_date) as month_name,
                 COUNT(*) as transaction_count,
                 CASE 
                     WHEN ml.id IS NOT NULL THEN 1 
                     ELSE 0 
                 END as is_locked
             FROM daily_txn dt
             LEFT JOIN month_locks ml ON YEAR(dt.txn_date) = ml.year_num 
                                     AND MONTH(dt.txn_date) = ml.month_num
             GROUP BY YEAR(txn_date), MONTH(txn_date)
             ORDER BY YEAR(txn_date) DESC, MONTH(txn_date) DESC"
        );
    }

    /**
     * Validate lock operation
     */
    public function validateLock($year, $month)
    {
        $errors = [];
        
        // Check if month/year is valid
        if ($month < 1 || $month > 12) {
            $errors[] = 'Month must be between 1 and 12';
        }
        
        if ($year < 2000 || $year > 2100) {
            $errors[] = 'Year must be between 2000 and 2100';
        }
        
        // Check if already locked
        if ($this->isLocked($year, $month)) {
            $errors[] = "Month {$year}-{$month} is already locked";
        }
        
        // Check if month has transactions
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM daily_txn 
             WHERE YEAR(txn_date) = ? AND MONTH(txn_date) = ?",
            [$year, $month]
        );
        
        if ($result['count'] == 0) {
            $errors[] = "Cannot lock month {$year}-{$month} - no transactions found";
        }
        
        return $errors;
    }

    /**
     * Get lock statistics
     */
    public function getStats()
    {
        return $this->db->fetch(
            "SELECT 
                 COUNT(*) as total_locked_months,
                 COUNT(DISTINCT year_num) as locked_years,
                 MIN(CONCAT(year_num, '-', LPAD(month_num, 2, '0'))) as earliest_lock,
                 MAX(CONCAT(year_num, '-', LPAD(month_num, 2, '0'))) as latest_lock
             FROM month_locks"
        );
    }

    /**
     * Get recent locks
     */
    public function getRecent($limit = 10)
    {
        return $this->db->fetchAll(
            "SELECT ml.*, u.name as locked_by_name 
             FROM month_locks ml 
             LEFT JOIN users u ON ml.locked_by = u.id 
             ORDER BY ml.locked_at DESC 
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Check if user can lock/unlock months
     */
    public function canManageLocks($userId)
    {
        // Only admins can manage locks
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count 
             FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             JOIN roles r ON ur.role_id = r.id 
             WHERE u.id = ? AND r.name = 'admin'",
            [$userId]
        );
        
        return $result['count'] > 0;
    }

    /**
     * Get months in date range that are locked
     */
    public function getLockedInRange($startDate, $endDate)
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT ml.year_num, ml.month_num 
             FROM month_locks ml 
             WHERE CONCAT(ml.year_num, '-', LPAD(ml.month_num, 2, '0'), '-01') 
                   BETWEEN ? AND ?",
            [
                date('Y-m-01', strtotime($startDate)),
                date('Y-m-01', strtotime($endDate))
            ]
        );
    }

    /**
     * Get lock info for display
     */
    public function getLockInfo($year, $month)
    {
        $lock = $this->findByYearMonth($year, $month);
        
        if (!$lock) {
            return null;
        }
        
        return [
            'is_locked' => true,
            'locked_by' => $lock['locked_by_name'],
            'locked_at' => $lock['locked_at'],
            'note' => $lock['note'],
            'month_name' => date('F Y', mktime(0, 0, 0, $month, 1, $year))
        ];
    }

    /**
     * Bulk lock multiple months
     */
    public function bulkLock($months, $lockedBy, $note = null)
    {
        $this->db->beginTransaction();
        
        try {
            $lockedCount = 0;
            
            foreach ($months as $monthData) {
                $year = $monthData['year'];
                $month = $monthData['month'];
                
                if (!$this->isLocked($year, $month)) {
                    $this->lock($year, $month, $lockedBy, $note);
                    $lockedCount++;
                }
            }
            
            $this->db->commit();
            return $lockedCount;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get count of locks
     */
    public function getCount()
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM month_locks");
        return $result['count'];
    }
}
