<?php
/**
 * User Controller
 * Handles user management operations
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Models/Role.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/response.php';

class UserController
{
    private $userModel;
    private $roleModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->roleModel = new Role();
    }

    /**
     * Display user list
     */
    public function index()
    {
        Auth::requirePermission('manage_users');

        $page = (int)($_GET['page'] ?? 1);
        $perPage = 20;
        $search = $_GET['search'] ?? '';

        if ($search) {
            $users = $this->userModel->search($search, $perPage);
            $totalUsers = count($users);
        } else {
            $users = $this->userModel->getAll($perPage, ($page - 1) * $perPage);
            $totalUsers = $this->userModel->getActiveCount();
        }

        $totalPages = ceil($totalUsers / $perPage);

        $data = [
            'title' => 'User Management - Daily Statement App',
            'users' => $users,
            'search' => $search,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'per_page' => $perPage,
                'total_items' => $totalUsers,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
                'prev_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null
            ],
            'can_create' => Auth::can('create_users'),
            'can_edit' => Auth::can('edit_users'),
            'can_delete' => Auth::can('delete_users')
        ];

        include __DIR__ . '/../Views/users/index.php';
    }

    /**
     * Show create user form
     */
    public function create()
    {
        Auth::requirePermission('create_users');

        $roles = $this->roleModel->getAll();

        $data = [
            'title' => 'Create User - Daily Statement App',
            'roles' => $roles,
            'user' => null
        ];

        include __DIR__ . '/../Views/users/form.php';
    }

    /**
     * Store new user
     */
    public function store()
    {
        Auth::requirePermission('create_users');

        try {
            $this->validateUserData($_POST);

            // Check if email already exists
            if ($this->userModel->emailExists($_POST['email'])) {
                throw new Exception('Email already exists');
            }

            $userData = [
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'password_hash' => Auth::hashPassword($_POST['password']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'roles' => $_POST['roles'] ?? []
            ];

            $userId = $this->userModel->create($userData);

            Response::redirect('users?success=' . urlencode('User created successfully'));

        } catch (Exception $e) {
            $roles = $this->roleModel->getAll();
            
            $data = [
                'title' => 'Create User - Daily Statement App',
                'roles' => $roles,
                'user' => $_POST,
                'error' => $e->getMessage()
            ];

            include __DIR__ . '/../Views/users/form.php';
        }
    }

    /**
     * Show edit user form
     */
    public function edit()
    {
        Auth::requirePermission('edit_users');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            Response::redirect('users?error=' . urlencode('User ID required'));
            return;
        }

        $user = $this->userModel->find($id);
        if (!$user) {
            Response::redirect('users?error=' . urlencode('User not found'));
            return;
        }

        $roles = $this->roleModel->getAll();
        $userRoles = $this->userModel->getRoles($id);
        $user['role_ids'] = array_column($userRoles, 'id');

        $data = [
            'title' => 'Edit User - Daily Statement App',
            'roles' => $roles,
            'user' => $user,
            'editing' => true
        ];

        include __DIR__ . '/../Views/users/form.php';
    }

    /**
     * Update user
     */
    public function update()
    {
        Auth::requirePermission('edit_users');

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Response::redirect('users?error=' . urlencode('User ID required'));
            return;
        }

        try {
            $this->validateUserData($_POST, $id);

            // Check if email already exists (excluding current user)
            if ($this->userModel->emailExists($_POST['email'], $id)) {
                throw new Exception('Email already exists');
            }

            $userData = [
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'roles' => $_POST['roles'] ?? []
            ];

            // Only update password if provided
            if (!empty($_POST['password'])) {
                $userData['password_hash'] = Auth::hashPassword($_POST['password']);
            }

            $this->userModel->update($id, $userData);

            // If the updated user is currently logged in, mark their session for refresh
            if ($id == Auth::user()['id']) {
                // Update current session immediately
                $freshUser = $this->userModel->find($id);
                Auth::refreshUser($freshUser);
            }

            Response::redirect('users?success=' . urlencode('User updated successfully'));

        } catch (Exception $e) {
            $user = $this->userModel->find($id);
            $roles = $this->roleModel->getAll();
            $userRoles = $this->userModel->getRoles($id);
            $user['role_ids'] = array_column($userRoles, 'id');
            
            // Merge form data
            $user = array_merge($user, $_POST);
            
            $data = [
                'title' => 'Edit User - Daily Statement App',
                'roles' => $roles,
                'user' => $user,
                'editing' => true,
                'error' => $e->getMessage()
            ];

            include __DIR__ . '/../Views/users/form.php';
        }
    }

    /**
     * Delete user
     */
    public function delete()
    {
        Auth::requirePermission('delete_users');

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Response::redirect('users?error=' . urlencode('User ID required'));
            return;
        }

        // Prevent deleting current user
        if ($id == Auth::user()['id']) {
            Response::redirect('users?error=' . urlencode('Cannot delete your own account'));
            return;
        }

        try {
            $this->userModel->delete($id);
            Response::redirect('users?success=' . urlencode('User deleted successfully'));
        } catch (Exception $e) {
            Response::redirect('users?error=' . urlencode('Failed to delete user: ' . $e->getMessage()));
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleActive()
    {
        Auth::requirePermission('edit_users');

        $id = $_POST['id'] ?? null;
        $active = $_POST['active'] ?? 0;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            return;
        }

        // Prevent deactivating current user
        if ($id == Auth::user()['id'] && !$active) {
            echo json_encode(['success' => false, 'message' => 'Cannot deactivate your own account']);
            return;
        }

        try {
            $this->userModel->setActive($id, $active);
            echo json_encode(['success' => true, 'message' => 'User status updated']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
        }
    }

    /**
     * Show user profile
     */
    public function profile()
    {
        $user = Auth::user();
        $userRoles = $this->userModel->getRoles($user['id']);

        $data = [
            'title' => 'My Profile - Daily Statement App',
            'user' => $user,
            'roles' => $userRoles
        ];

        include __DIR__ . '/../Views/users/profile.php';
    }

    /**
     * Update user profile
     */
    public function updateProfile()
    {
        $userId = Auth::user()['id'];

        try {
            $this->validateProfileData($_POST);

            // Check if email already exists (excluding current user)
            if ($this->userModel->emailExists($_POST['email'], $userId)) {
                throw new Exception('Email already exists');
            }

            $userData = [
                'name' => trim($_POST['name']),
                'email' => trim($_POST['email'])
            ];

            // Only update password if provided
            if (!empty($_POST['password'])) {
                $userData['password_hash'] = Auth::hashPassword($_POST['password']);
            }

            $this->userModel->update($userId, $userData);

            Response::redirect('users/profile?success=' . urlencode('Profile updated successfully'));

        } catch (Exception $e) {
            $user = Auth::user();
            $userRoles = $this->userModel->getRoles($userId);
            
            $data = [
                'title' => 'My Profile - Daily Statement App',
                'user' => array_merge($user, $_POST),
                'roles' => $userRoles,
                'error' => $e->getMessage()
            ];

            include __DIR__ . '/../Views/users/profile.php';
        }
    }

    /**
     * Validate user data
     */
    private function validateUserData($data, $excludeId = null)
    {
        if (empty($data['name'])) {
            throw new Exception('Name is required');
        }

        if (empty($data['email'])) {
            throw new Exception('Email is required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Password required for new users
        if (!$excludeId && empty($data['password'])) {
            throw new Exception('Password is required');
        }

        // Password validation if provided
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                throw new Exception('Password must be at least 6 characters');
            }

            if ($data['password'] !== $data['password_confirm']) {
                throw new Exception('Passwords do not match');
            }
        }
    }

    /**
     * Validate profile data
     */
    private function validateProfileData($data)
    {
        if (empty($data['name'])) {
            throw new Exception('Name is required');
        }

        if (empty($data['email'])) {
            throw new Exception('Email is required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Password validation if provided
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                throw new Exception('Password must be at least 6 characters');
            }

            if ($data['password'] !== $data['password_confirm']) {
                throw new Exception('Passwords do not match');
            }
        }
    }

    /**
     * Check for user session updates (API endpoint)
     */
    public function checkUpdates()
    {
        $currentUser = Auth::user();
        if (!$currentUser) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        try {
            // Get fresh user data from database
            $freshUser = $this->userModel->find($currentUser['id']);
            
            if (!$freshUser) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                return;
            }

            // Check if user is still active
            if (!$freshUser['is_active']) {
                echo json_encode([
                    'success' => true,
                    'account_disabled' => true,
                    'message' => 'Your account has been disabled. You will be logged out.'
                ]);
                return;
            }

            // Compare current session data with fresh data
            $hasChanges = false;
            $changes = [];

            // Check for name changes
            if ($currentUser['name'] !== $freshUser['name']) {
                $hasChanges = true;
                $changes['name'] = [
                    'old' => $currentUser['name'],
                    'new' => $freshUser['name']
                ];
            }

            // Check for email changes
            if ($currentUser['email'] !== $freshUser['email']) {
                $hasChanges = true;
                $changes['email'] = [
                    'old' => $currentUser['email'],
                    'new' => $freshUser['email']
                ];
            }

            // Check for role changes
            $currentRoles = $currentUser['roles'] ?? '';
            $freshRoles = $freshUser['roles'] ?? '';
            if ($currentRoles !== $freshRoles) {
                $hasChanges = true;
                $changes['roles'] = [
                    'old' => $currentRoles,
                    'new' => $freshRoles
                ];
            }

            if ($hasChanges) {
                // Update session with fresh data
                Auth::refreshUser($freshUser);
                
                echo json_encode([
                    'success' => true,
                    'has_changes' => true,
                    'changes' => $changes,
                    'user' => [
                        'name' => $freshUser['name'],
                        'email' => $freshUser['email'],
                        'roles' => $freshUser['roles']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'has_changes' => false
                ]);
            }

        } catch (Exception $e) {
            error_log("User update check error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error checking updates']);
        }
    }
}
