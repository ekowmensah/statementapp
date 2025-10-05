<?php
/**
 * Authentication Helper
 * Handles user authentication, authorization, and session management
 */

class Auth
{
    private static $user = null;
    private static $roles = null;

    /**
     * Start session and initialize auth
     */
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../config/config.php';
            session_name($config['security']['session_name']);
            session_start();
            
            // Set session timeout
            if (isset($_SESSION['last_activity']) && 
                (time() - $_SESSION['last_activity'] > $config['security']['session_lifetime'])) {
                self::logout();
            }
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Attempt to log in a user
     */
    public static function attempt($email, $password)
    {
        $db = Database::getInstance();
        
        $user = $db->fetch(
            "SELECT u.*, GROUP_CONCAT(r.name) as roles 
             FROM users u 
             LEFT JOIN user_roles ur ON u.id = ur.user_id 
             LEFT JOIN roles r ON ur.role_id = r.id 
             WHERE u.email = ? AND u.is_active = 1 
             GROUP BY u.id",
            [$email]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
            
            self::$user = $user;
            self::$roles = $_SESSION['user_roles'];
            
            return true;
        }
        
        return false;
    }

    /**
     * Log out the current user
     */
    public static function logout()
    {
        session_destroy();
        self::$user = null;
        self::$roles = null;
    }

    /**
     * Check if user is authenticated
     */
    public static function check()
    {
        self::init();
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user data
     */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }

        if (self::$user === null) {
            $db = Database::getInstance();
            self::$user = $db->fetch(
                "SELECT * FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );
        }

        return self::$user;
    }

    /**
     * Get current user ID
     */
    public static function id()
    {
        return self::check() ? $_SESSION['user_id'] : null;
    }

    /**
     * Get current user roles
     */
    public static function roles()
    {
        if (!self::check()) {
            return [];
        }

        if (self::$roles === null) {
            self::$roles = $_SESSION['user_roles'] ?? [];
        }

        return self::$roles;
    }

    /**
     * Check if user has a specific role
     */
    public static function hasRole($role)
    {
        return in_array($role, self::roles());
    }

    /**
     * Check if user has any of the specified roles
     */
    public static function hasAnyRole($roles)
    {
        return !empty(array_intersect($roles, self::roles()));
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        return self::hasRole('admin');
    }

    /**
     * Check if user can perform action based on role
     */
    public static function can($action, $resource = null)
    {
        $roles = self::roles();
        
        // Admin can do everything
        if (in_array('admin', $roles)) {
            return true;
        }

        // Define permissions
        $permissions = [
            'accountant' => [
                'view_dashboard',
                'view_daily', 'create_daily', 'edit_daily', 'delete_daily',
                'view_rates', 'create_rates', 'edit_rates',
                'view_statement', 'view_reports',
                'export_csv', 'export_pdf'
            ],
            'viewer' => [
                'view_dashboard',
                'view_daily', 'view_rates', 'view_statement', 'view_reports',
                'export_csv', 'export_pdf'
            ]
        ];

        foreach ($roles as $role) {
            if (isset($permissions[$role]) && in_array($action, $permissions[$role])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    public static function requireAuth()
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Require specific role - show 403 if not authorized
     */
    public static function requireRole($role)
    {
        self::requireAuth();
        
        if (!self::hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            header('Location: /403');
            exit;
        }
    }

    /**
     * Require permission - show 403 if not authorized
     */
    public static function requirePermission($action, $resource = null)
    {
        self::requireAuth();
        
        if (!self::can($action, $resource)) {
            header('HTTP/1.1 403 Forbidden');
            header('Location: /403');
            exit;
        }
    }

    /**
     * Hash password
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Refresh user data in session
     */
    public static function refreshUser($userData)
    {
        if (!self::check()) {
            return false;
        }

        // Update session with fresh user data
        $_SESSION['user_data'] = $userData;
        
        // Clear cached user data to force refresh
        self::$user = null;
        self::$roles = null;

        return true;
    }
}
