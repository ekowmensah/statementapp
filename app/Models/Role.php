<?php
/**
 * Role Model
 * Handles role data operations
 */

require_once __DIR__ . '/../../config/db.php';

class Role
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find role by ID
     */
    public function find($id)
    {
        return $this->db->fetch("SELECT * FROM roles WHERE id = ?", [$id]);
    }

    /**
     * Find role by name
     */
    public function findByName($name)
    {
        return $this->db->fetch("SELECT * FROM roles WHERE name = ?", [$name]);
    }

    /**
     * Get all roles
     */
    public function getAll()
    {
        return $this->db->fetchAll("SELECT * FROM roles ORDER BY name ASC");
    }

    /**
     * Create new role
     */
    public function create($data)
    {
        $sql = "INSERT INTO roles (name" . (isset($data['description']) ? ", description" : "") . ") VALUES (?" . (isset($data['description']) ? ", ?" : "") . ")";
        $params = [$data['name']];
        if (isset($data['description'])) {
            $params[] = $data['description'];
        }
        
        return $this->db->insert($sql, $params);
    }

    /**
     * Update role
     */
    public function update($id, $data)
    {
        $sql = "UPDATE roles SET name = ?" . (isset($data['description']) ? ", description = ?" : "") . " WHERE id = ?";
        $params = [$data['name']];
        if (isset($data['description'])) {
            $params[] = $data['description'];
        }
        $params[] = $id;
        
        return $this->db->update($sql, $params);
    }

    /**
     * Delete role
     */
    public function delete($id)
    {
        return $this->db->delete("DELETE FROM roles WHERE id = ?", [$id]);
    }

    /**
     * Check if role name exists
     */
    public function nameExists($name, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM roles WHERE name = ?";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Get role with user count
     */
    public function getWithUserCount()
    {
        return $this->db->fetchAll(
            "SELECT r.*, COUNT(ur.user_id) as user_count 
             FROM roles r 
             LEFT JOIN user_roles ur ON r.id = ur.role_id 
             GROUP BY r.id 
             ORDER BY r.name ASC"
        );
    }

    /**
     * Get users for a role
     */
    public function getUsers($roleId)
    {
        return $this->db->fetchAll(
            "SELECT u.* FROM users u 
             JOIN user_roles ur ON u.id = ur.user_id 
             WHERE ur.role_id = ? 
             ORDER BY u.name ASC",
            [$roleId]
        );
    }

    /**
     * Check if role can be deleted (has no users)
     */
    public function canDelete($id)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM user_roles WHERE role_id = ?",
            [$id]
        );
        
        return $result['count'] == 0;
    }

    /**
     * Get role permissions (for future use)
     */
    public function getPermissions($roleId)
    {
        // This could be extended to support a permissions system
        $role = $this->find($roleId);
        
        if (!$role) {
            return [];
        }

        // Define default permissions based on role name
        $permissions = [
            'admin' => [
                'view_dashboard', 'view_daily', 'create_daily', 'edit_daily', 'delete_daily',
                'view_rates', 'create_rates', 'edit_rates', 'delete_rates',
                'view_statement', 'view_reports', 'manage_locks',
                'export_csv', 'export_pdf', 'manage_users'
            ],
            'accountant' => [
                'view_dashboard', 'view_daily', 'create_daily', 'edit_daily', 'delete_daily',
                'view_rates', 'create_rates', 'edit_rates',
                'view_statement', 'view_reports',
                'export_csv', 'export_pdf'
            ],
            'viewer' => [
                'view_dashboard', 'view_daily', 'view_rates', 'view_statement', 'view_reports',
                'export_csv', 'export_pdf'
            ]
        ];

        return $permissions[$role['name']] ?? [];
    }
}
