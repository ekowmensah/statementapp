<?php
/**
 * Permission Model
 * Handles permission data operations
 */

require_once __DIR__ . '/../../config/db.php';

class Permission
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find permission by ID
     */
    public function find($id)
    {
        return $this->db->fetch("SELECT * FROM permissions WHERE id = ?", [$id]);
    }

    /**
     * Get all permissions
     */
    public function getAll()
    {
        return $this->db->fetchAll("SELECT * FROM permissions ORDER BY category ASC, name ASC");
    }

    /**
     * Get all permissions grouped by category
     */
    public function getAllGroupedByCategory()
    {
        $permissions = $this->getAll();
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $category = $permission['category'] ?? 'general';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }
        
        return $grouped;
    }

    /**
     * Get permissions for a specific role
     */
    public function getByRole($roleId)
    {
        return $this->db->fetchAll(
            "SELECT p.* FROM permissions p 
             JOIN role_permissions rp ON p.id = rp.permission_id 
             WHERE rp.role_id = ? 
             ORDER BY p.category ASC, p.name ASC",
            [$roleId]
        );
    }

    /**
     * Update role permissions
     */
    public function updateRolePermissions($roleId, $permissionIds)
    {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Remove all existing permissions for this role
            $this->db->delete("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);

            // Add new permissions
            if (!empty($permissionIds)) {
                $sql = "INSERT INTO role_permissions (role_id, permission_id) VALUES ";
                $values = [];
                $params = [];

                foreach ($permissionIds as $permissionId) {
                    $values[] = "(?, ?)";
                    $params[] = $roleId;
                    $params[] = $permissionId;
                }

                $sql .= implode(", ", $values);
                $this->db->query($sql, $params);
            }

            // Commit transaction
            $this->db->commit();
            return true;

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Create new permission
     */
    public function create($data)
    {
        return $this->db->insert(
            "INSERT INTO permissions (name, description, category) VALUES (?, ?, ?)",
            [$data['name'], $data['description'] ?? '', $data['category'] ?? 'general']
        );
    }

    /**
     * Update permission
     */
    public function update($id, $data)
    {
        return $this->db->update(
            "UPDATE permissions SET name = ?, description = ?, category = ? WHERE id = ?",
            [$data['name'], $data['description'] ?? '', $data['category'] ?? 'general', $id]
        );
    }

    /**
     * Delete permission
     */
    public function delete($id)
    {
        return $this->db->delete("DELETE FROM permissions WHERE id = ?", [$id]);
    }

    /**
     * Check if permission name exists
     */
    public function nameExists($name, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM permissions WHERE name = ?";
        $params = [$name];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'] > 0;
    }

    /**
     * Get permission categories
     */
    public function getCategories()
    {
        $result = $this->db->fetchAll("SELECT DISTINCT category FROM permissions ORDER BY category ASC");
        return array_column($result, 'category');
    }

    /**
     * Get permissions count by category
     */
    public function getCountByCategory()
    {
        return $this->db->fetchAll(
            "SELECT category, COUNT(*) as count 
             FROM permissions 
             GROUP BY category 
             ORDER BY category ASC"
        );
    }

    /**
     * Check if permission can be deleted (not assigned to any role)
     */
    public function canDelete($id)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM role_permissions WHERE permission_id = ?",
            [$id]
        );
        
        return $result['count'] == 0;
    }

    /**
     * Get roles that have a specific permission
     */
    public function getRolesWithPermission($permissionId)
    {
        return $this->db->fetchAll(
            "SELECT r.* FROM roles r 
             JOIN role_permissions rp ON r.id = rp.role_id 
             WHERE rp.permission_id = ? 
             ORDER BY r.name ASC",
            [$permissionId]
        );
    }
}
