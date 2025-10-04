<?php
/**
 * URL Helper - Global URL generation
 */

class Url {
    /**
     * Generate correct application URL
     */
    public static function to($path = '') {
        // Always use the correct base path for XAMPP
        $basePath = '/accountstatement/public';
        $path = ltrim($path, '/');
        
        if ($path) {
            return $basePath . '/' . $path;
        }
        
        return $basePath;
    }
    
    /**
     * Generate asset URL
     */
    public static function asset($path) {
        return self::to($path);
    }
}

/**
 * Global helper function
 */
function url($path = '') {
    return Url::to($path);
}
?>
