<?php
/**
 * Role Controller
 * Handles role and permission management
 */

require_once __DIR__ . '/../Models/Role.php';
require_once __DIR__ . '/../Models/Permission.php';

class RoleController
{
    private $roleModel;
    private $permissionModel;
    
    public function __construct()
    {
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
    }

    /**
     * Display roles list
     */
    public function index()
    {
        Auth::requirePermission('manage_users');

        try {
            $roles = $this->roleModel->getWithUserCount();
            
            $data = [
                'title' => 'Role Management - Daily Statement App',
                'roles' => $roles
            ];

            include __DIR__ . '/../Views/roles/index.php';

        } catch (Exception $e) {
            error_log("Role index error: " . $e->getMessage());
            $data = [
                'title' => 'Role Management - Daily Statement App',
                'roles' => [],
                'error' => 'Failed to load roles'
            ];
            include __DIR__ . '/../Views/roles/index.php';
        }
    }

    /**
     * Show role permissions edit form
     */
    public function editPermissions()
    {
        Auth::requirePermission('manage_users');

        $roleId = $_GET['id'] ?? null;
        if (!$roleId) {
            Response::redirect('roles?error=' . urlencode('Role ID required'));
            return;
        }

        try {
            $role = $this->roleModel->find($roleId);
            if (!$role) {
                Response::redirect('roles?error=' . urlencode('Role not found'));
                return;
            }

            // Get all permissions grouped by category
            $allPermissions = $this->permissionModel->getAllGroupedByCategory();
            
            // Get current role permissions
            $rolePermissions = $this->permissionModel->getByRole($roleId);
            $rolePermissionIds = array_column($rolePermissions, 'id');

            $data = [
                'title' => 'Edit Role Permissions - Daily Statement App',
                'role' => $role,
                'permissions' => $allPermissions,
                'rolePermissions' => $rolePermissionIds
            ];

            include __DIR__ . '/../Views/roles/edit-permissions.php';

        } catch (Exception $e) {
            error_log("Role edit permissions error: " . $e->getMessage());
            Response::redirect('roles?error=' . urlencode('Failed to load role permissions'));
        }
    }

    /**
     * Update role permissions
     */
    public function updatePermissions()
    {
        Auth::requirePermission('manage_users');

        $roleId = $_POST['role_id'] ?? null;
        if (!$roleId) {
            Response::redirect('roles?error=' . urlencode('Role ID required'));
            return;
        }

        try {
            $role = $this->roleModel->find($roleId);
            if (!$role) {
                Response::redirect('roles?error=' . urlencode('Role not found'));
                return;
            }

            $selectedPermissions = $_POST['permissions'] ?? [];
            
            // Update role permissions
            $this->permissionModel->updateRolePermissions($roleId, $selectedPermissions);

            Response::redirect('roles?success=' . urlencode('Role permissions updated successfully'));

        } catch (Exception $e) {
            error_log("Role update permissions error: " . $e->getMessage());
            Response::redirect('roles?error=' . urlencode('Failed to update role permissions: ' . $e->getMessage()));
        }
    }

    /**
     * Create new role
     */
    public function create()
    {
        Auth::requirePermission('manage_users');

        $data = [
            'title' => 'Create Role - Daily Statement App',
            'role' => []
        ];

        include __DIR__ . '/../Views/roles/form.php';
    }

    /**
     * Store new role
     */
    public function store()
    {
        Auth::requirePermission('manage_users');

        try {
            $this->validateRoleData($_POST);

            // Check if role name already exists
            if ($this->roleModel->nameExists($_POST['name'])) {
                throw new Exception('Role name already exists');
            }

            $roleData = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? '')
            ];

            $roleId = $this->roleModel->create($roleData);

            Response::redirect('roles?success=' . urlencode('Role created successfully'));

        } catch (Exception $e) {
            $data = [
                'title' => 'Create Role - Daily Statement App',
                'role' => $_POST,
                'error' => $e->getMessage()
            ];

            include __DIR__ . '/../Views/roles/form.php';
        }
    }

    /**
     * Show edit role form
     */
    public function edit()
    {
        Auth::requirePermission('manage_users');

        $roleId = $_GET['id'] ?? null;
        if (!$roleId) {
            Response::redirect('roles?error=' . urlencode('Role ID required'));
            return;
        }

        try {
            $role = $this->roleModel->find($roleId);
            if (!$role) {
                Response::redirect('roles?error=' . urlencode('Role not found'));
                return;
            }

            $data = [
                'title' => 'Edit Role - Daily Statement App',
                'role' => $role,
                'editing' => true
            ];

            include __DIR__ . '/../Views/roles/form.php';

        } catch (Exception $e) {
            error_log("Role edit error: " . $e->getMessage());
            Response::redirect('roles?error=' . urlencode('Failed to load role'));
        }
    }

    /**
     * Update role
     */
    public function update()
    {
        Auth::requirePermission('manage_users');

        $roleId = $_POST['id'] ?? null;
        if (!$roleId) {
            Response::redirect('roles?error=' . urlencode('Role ID required'));
            return;
        }

        try {
            $role = $this->roleModel->find($roleId);
            if (!$role) {
                Response::redirect('roles?error=' . urlencode('Role not found'));
                return;
            }

            $this->validateRoleData($_POST, $roleId);

            // Check if role name already exists (excluding current role)
            if ($this->roleModel->nameExists($_POST['name'], $roleId)) {
                throw new Exception('Role name already exists');
            }

            $roleData = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? '')
            ];

            $this->roleModel->update($roleId, $roleData);

            Response::redirect('roles?success=' . urlencode('Role updated successfully'));

        } catch (Exception $e) {
            $role = $this->roleModel->find($roleId);
            $data = [
                'title' => 'Edit Role - Daily Statement App',
                'role' => array_merge($role, $_POST),
                'editing' => true,
                'error' => $e->getMessage()
            ];

            include __DIR__ . '/../Views/roles/form.php';
        }
    }

    /**
     * Delete role
     */
    public function delete()
    {
        Auth::requirePermission('manage_users');

        $roleId = $_POST['id'] ?? null;
        if (!$roleId) {
            Response::redirect('roles?error=' . urlencode('Role ID required'));
            return;
        }

        try {
            $role = $this->roleModel->find($roleId);
            if (!$role) {
                Response::redirect('roles?error=' . urlencode('Role not found'));
                return;
            }

            // Check if role can be deleted (no users assigned)
            if (!$this->roleModel->canDelete($roleId)) {
                Response::redirect('roles?error=' . urlencode('Cannot delete role: users are still assigned to this role'));
                return;
            }

            $this->roleModel->delete($roleId);

            Response::redirect('roles?success=' . urlencode('Role deleted successfully'));

        } catch (Exception $e) {
            error_log("Role delete error: " . $e->getMessage());
            Response::redirect('roles?error=' . urlencode('Failed to delete role: ' . $e->getMessage()));
        }
    }

    /**
     * Validate role data
     */
    private function validateRoleData($data, $excludeId = null)
    {
        if (empty($data['name'])) {
            throw new Exception('Role name is required');
        }

        if (strlen($data['name']) < 2) {
            throw new Exception('Role name must be at least 2 characters');
        }

        if (strlen($data['name']) > 100) {
            throw new Exception('Role name must not exceed 100 characters');
        }
    }
}
