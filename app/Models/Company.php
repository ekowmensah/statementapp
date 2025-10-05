<?php
/**
 * Company Model
 * Handles company data operations for transaction categorization
 */

class Company
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find company by ID
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT c.*, 
                    uc.name as created_by_name, 
                    uu.name as updated_by_name 
             FROM companies c 
             LEFT JOIN users uc ON c.created_by = uc.id 
             LEFT JOIN users uu ON c.updated_by = uu.id 
             WHERE c.id = ?",
            [$id]
        );
    }

    /**
     * Get all companies
     */
    public function getAll($orderBy = 'name ASC')
    {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    uc.name as created_by_name, 
                    uu.name as updated_by_name 
             FROM companies c 
             LEFT JOIN users uc ON c.created_by = uc.id 
             LEFT JOIN users uu ON c.updated_by = uu.id 
             ORDER BY {$orderBy}"
        );
    }

    /**
     * Get active companies only
     */
    public function getActive($orderBy = 'name ASC')
    {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    uc.name as created_by_name, 
                    uu.name as updated_by_name 
             FROM companies c 
             LEFT JOIN users uc ON c.created_by = uc.id 
             LEFT JOIN users uu ON c.updated_by = uu.id 
             WHERE c.is_active = 1 
             ORDER BY {$orderBy}"
        );
    }

    /**
     * Get companies with pagination
     */
    public function getPaginated($limit = 25, $offset = 0, $search = '', $orderBy = 'name ASC')
    {
        $whereConditions = [];
        $params = [];
        
        // Search filter
        if ($search) {
            $whereConditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM companies c {$whereClause}";
        $countResult = $this->db->fetch($countSql, $params);
        $totalCount = $countResult['total'];
        
        // Get companies
        $sql = "SELECT c.*, 
                       uc.name as created_by_name, 
                       uu.name as updated_by_name 
                FROM companies c 
                LEFT JOIN users uc ON c.created_by = uc.id 
                LEFT JOIN users uu ON c.updated_by = uu.id 
                {$whereClause} 
                ORDER BY {$orderBy} 
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $companies = $this->db->fetchAll($sql, $params);
        
        return [
            'companies' => $companies,
            'total_count' => $totalCount
        ];
    }

    /**
     * Create new company
     */
    public function create($data)
    {
        return $this->db->insert(
            "INSERT INTO companies (name, description, is_active, created_by) 
             VALUES (?, ?, ?, ?)",
            [
                $data['name'],
                $data['description'] ?? null,
                $data['is_active'] ?? 1,
                $data['created_by']
            ]
        );
    }

    /**
     * Update company
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        
        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
        }
        
        if (isset($data['description'])) {
            $fields[] = 'description = ?';
            $params[] = $data['description'];
        }
        
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $params[] = $data['is_active'];
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
            "UPDATE companies SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
    }

    /**
     * Delete company
     */
    public function delete($id)
    {
        // Check if company is used in transactions
        $usageCount = $this->getUsageCount($id);
        if ($usageCount > 0) {
            throw new Exception("Cannot delete company. It is used in {$usageCount} transaction(s).");
        }
        
        return $this->db->delete("DELETE FROM companies WHERE id = ?", [$id]);
    }

    /**
     * Check if company name exists
     */
    public function nameExists($name, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM companies WHERE name = ?";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Get usage count (how many transactions use this company)
     */
    public function getUsageCount($id)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM daily_txn WHERE company_id = ?",
            [$id]
        );
        return $result['count'];
    }

    /**
     * Get companies with transaction counts
     */
    public function getWithTransactionCounts()
    {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    COUNT(dt.id) as transaction_count,
                    uc.name as created_by_name
             FROM companies c 
             LEFT JOIN daily_txn dt ON c.id = dt.company_id 
             LEFT JOIN users uc ON c.created_by = uc.id 
             GROUP BY c.id 
             ORDER BY c.name ASC"
        );
    }

    /**
     * Search companies
     */
    public function search($query, $limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT * FROM companies 
             WHERE name LIKE ? OR description LIKE ? 
             AND is_active = 1 
             ORDER BY name ASC 
             LIMIT ?",
            ["%{$query}%", "%{$query}%", $limit]
        );
    }

    /**
     * Validate company data
     */
    public function validate($data, $excludeId = null)
    {
        $validator = new Validate($data);
        
        $validator
            ->required('name', 'Company name is required')
            ->minLength('name', 2, 'Company name must be at least 2 characters')
            ->maxLength('name', 100, 'Company name cannot exceed 100 characters')
            ->maxLength('description', 255, 'Description cannot exceed 255 characters');

        // Check unique name
        if (isset($data['name'])) {
            $validator->custom('name', function($value) use ($excludeId) {
                return !$this->nameExists($value, $excludeId);
            }, 'A company with this name already exists');
        }

        return $validator;
    }

    /**
     * Get count of companies
     */
    public function getCount()
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM companies");
        return $result['count'];
    }

    /**
     * Get active count of companies
     */
    public function getActiveCount()
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM companies WHERE is_active = 1");
        return $result['count'];
    }

    /**
     * Toggle company active status
     */
    public function toggleActive($id)
    {
        return $this->db->update(
            "UPDATE companies SET is_active = NOT is_active WHERE id = ?",
            [$id]
        );
    }
}
