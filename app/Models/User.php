<?php
/**
 * User Model
 * Handles user data operations
 */

class User
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find user by ID
     */
    public function find($id)
    {
        return $this->db->fetch(
            "SELECT u.*, GROUP_CONCAT(r.name) as roles 
             FROM users u 
             LEFT JOIN user_roles ur ON u.id = ur.user_id 
             LEFT JOIN roles r ON ur.role_id = r.id 
             WHERE u.id = ? 
             GROUP BY u.id",
            [$id]
        );
    }

    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->db->fetch(
            "SELECT u.*, GROUP_CONCAT(r.name) as roles 
             FROM users u 
             LEFT JOIN user_roles ur ON u.id = ur.user_id 
             LEFT JOIN roles r ON ur.role_id = r.id 
             WHERE u.email = ? 
             GROUP BY u.id",
            [$email]
        );
    }

    /**
     * Get all users with their roles
     */
    public function getAll($limit = null, $offset = 0)
    {
        $sql = "SELECT u.*, GROUP_CONCAT(r.name) as roles 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                LEFT JOIN roles r ON ur.role_id = r.id 
                GROUP BY u.id 
                ORDER BY u.name ASC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Create new user
     */
    public function create($data)
    {
        $this->db->beginTransaction();
        
        try {
            // Insert user
            $userId = $this->db->insert(
                "INSERT INTO users (name, email, password_hash, is_active) 
                 VALUES (?, ?, ?, ?)",
                [
                    $data['name'],
                    $data['email'],
                    $data['password_hash'],
                    $data['is_active'] ?? 1
                ]
            );

            // Assign roles if provided
            if (!empty($data['roles'])) {
                $this->assignRoles($userId, $data['roles']);
            }

            $this->db->commit();
            return $userId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update user
     */
    public function update($id, $data)
    {
        $this->db->beginTransaction();
        
        try {
            // Prepare update fields
            $fields = [];
            $params = [];
            
            if (isset($data['name'])) {
                $fields[] = 'name = ?';
                $params[] = $data['name'];
            }
            
            if (isset($data['email'])) {
                $fields[] = 'email = ?';
                $params[] = $data['email'];
            }
            
            if (isset($data['password_hash'])) {
                $fields[] = 'password_hash = ?';
                $params[] = $data['password_hash'];
            }
            
            if (isset($data['is_active'])) {
                $fields[] = 'is_active = ?';
                $params[] = $data['is_active'];
            }

            if (!empty($fields)) {
                $params[] = $id;
                $this->db->update(
                    "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?",
                    $params
                );
            }

            // Update roles if provided
            if (isset($data['roles'])) {
                $this->updateRoles($id, $data['roles']);
            }

            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Delete user
     */
    public function delete($id)
    {
        return $this->db->delete("DELETE FROM users WHERE id = ?", [$id]);
    }

    /**
     * Assign roles to user
     */
    public function assignRoles($userId, $roleIds)
    {
        foreach ($roleIds as $roleId) {
            $this->db->insert(
                "INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)",
                [$userId, $roleId]
            );
        }
    }

    /**
     * Update user roles
     */
    public function updateRoles($userId, $roleIds)
    {
        // Remove existing roles
        $this->db->delete("DELETE FROM user_roles WHERE user_id = ?", [$userId]);
        
        // Assign new roles
        if (!empty($roleIds)) {
            $this->assignRoles($userId, $roleIds);
        }
    }

    /**
     * Get user roles
     */
    public function getRoles($userId)
    {
        return $this->db->fetchAll(
            "SELECT r.* FROM roles r 
             JOIN user_roles ur ON r.id = ur.role_id 
             WHERE ur.user_id = ?",
            [$userId]
        );
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Get active users count
     */
    public function getActiveCount()
    {
        $result = $this->db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        return $result['count'];
    }

    /**
     * Search users
     */
    public function search($query, $limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT u.*, GROUP_CONCAT(r.name) as roles 
             FROM users u 
             LEFT JOIN user_roles ur ON u.id = ur.user_id 
             LEFT JOIN roles r ON ur.role_id = r.id 
             WHERE u.name LIKE ? OR u.email LIKE ? 
             GROUP BY u.id 
             ORDER BY u.name ASC 
             LIMIT ?",
            ["%{$query}%", "%{$query}%", $limit]
        );
    }

    /**
     * Activate/deactivate user
     */
    public function setActive($id, $active = true)
    {
        return $this->db->update(
            "UPDATE users SET is_active = ? WHERE id = ?",
            [$active ? 1 : 0, $id]
        );
    }

    /**
     * Update last login
     */
    public function updateLastLogin($id)
    {
        return $this->db->update(
            "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get users by role
     */
    public function getByRole($roleName)
    {
        return $this->db->fetchAll(
            "SELECT u.* FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             JOIN roles r ON ur.role_id = r.id 
             WHERE r.name = ? AND u.is_active = 1 
             ORDER BY u.name ASC",
            [$roleName]
        );
    }
}
