<?php
/**
 * CSRF Protection Helper
 * Generates and validates CSRF tokens for form security
 */

class CSRF
{
    private static $tokenName = '_token';

    /**
     * Initialize CSRF protection
     */
    public static function init()
    {
        Auth::init(); // Ensure session is started
        
        $config = require __DIR__ . '/../../config/config.php';
        self::$tokenName = $config['security']['csrf_token_name'];
    }

    /**
     * Generate a new CSRF token
     */
    public static function generateToken()
    {
        self::init();
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }

    /**
     * Get current CSRF token (generate if none exists)
     */
    public static function getToken()
    {
        self::init();
        
        if (!isset($_SESSION['csrf_token']) || self::isTokenExpired()) {
            return self::generateToken();
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public static function validateToken($token)
    {
        self::init();
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        if (self::isTokenExpired()) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Check if token is expired (1 hour timeout)
     */
    private static function isTokenExpired()
    {
        if (!isset($_SESSION['csrf_token_time'])) {
            return true;
        }
        
        return (time() - $_SESSION['csrf_token_time']) > 3600; // 1 hour
    }

    /**
     * Validate CSRF token from request
     */
    public static function validateRequest()
    {
        $token = null;
        
        // Check POST data
        if (isset($_POST[self::$tokenName])) {
            $token = $_POST[self::$tokenName];
        }
        // Check headers (for AJAX requests)
        elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        if (!$token || !self::validateToken($token)) {
            return false;
        }
        
        return true;
    }

    /**
     * Get HTML input field for CSRF token
     */
    public static function field()
    {
        $token = self::getToken();
        $name = self::$tokenName;
        
        return "<input type=\"hidden\" name=\"{$name}\" value=\"{$token}\">";
    }

    /**
     * Get token for JavaScript/AJAX usage
     */
    public static function meta()
    {
        $token = self::getToken();
        
        return "<meta name=\"csrf-token\" content=\"{$token}\">";
    }

    /**
     * Clear CSRF token
     */
    public static function clearToken()
    {
        self::init();
        
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
    }
}
