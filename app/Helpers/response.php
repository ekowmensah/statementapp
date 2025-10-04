<?php
/**
 * Response Helper
 * Handles HTTP responses, redirects, and JSON responses
 */

class Response
{
    /**
     * Send JSON response
     */
    public static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send success JSON response
     */
    public static function success($data = null, $message = 'Success', $status = 200)
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Send error JSON response
     */
    public static function error($message = 'Error', $errors = null, $status = 400)
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed')
    {
        self::error($message, $errors, 422);
    }

    /**
     * Redirect to URL
     */
    public static function redirect($url, $status = 302)
    {
        // If it's already a full URL, use as-is
        if (preg_match('/^https?:\/\//', $url)) {
            http_response_code($status);
            header("Location: {$url}");
            exit;
        }
        
        // If it's an absolute path starting with /, use as-is
        if ($url[0] === '/') {
            http_response_code($status);
            header("Location: {$url}");
            exit;
        }
        
        // For relative URLs, prepend base path
        $config = require __DIR__ . '/../../config/config.php';
        $basePath = $config['app']['base_path'] ?? '';
        
        if ($basePath) {
            $url = $basePath . '/' . ltrim($url, '/');
        } else {
            $url = '/' . ltrim($url, '/');
        }
        
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    /**
     * Redirect back to previous page
     */
    public static function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }

    /**
     * Redirect with flash message
     */
    public static function redirectWith($url, $type, $message)
    {
        Flash::set($type, $message);
        self::redirect($url);
    }

    /**
     * Redirect back with flash message
     */
    public static function backWith($type, $message)
    {
        Flash::set($type, $message);
        self::back();
    }

    /**
     * Set HTTP status code
     */
    public static function status($code)
    {
        http_response_code($code);
        return new self();
    }

    /**
     * Send file download
     */
    public static function download($filePath, $fileName = null, $contentType = null)
    {
        if (!file_exists($filePath)) {
            self::status(404);
            return;
        }

        $fileName = $fileName ?: basename($filePath);
        $contentType = $contentType ?: mime_content_type($filePath);

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        readfile($filePath);
        exit;
    }

    /**
     * Send CSV download
     */
    public static function csv($data, $filename = 'export.csv', $headers = null)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Write headers if provided
        if ($headers) {
            fputcsv($output, $headers);
        }

        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Send plain text response
     */
    public static function text($content, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: text/plain');
        echo $content;
        exit;
    }

    /**
     * Send HTML response
     */
    public static function html($content, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: text/html');
        echo $content;
        exit;
    }

    /**
     * Check if request expects JSON
     */
    public static function expectsJson()
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        return strpos($accept, 'application/json') !== false ||
               strpos($contentType, 'application/json') !== false ||
               isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Get current URL
     */
    public static function currentUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return "{$protocol}://{$host}{$uri}";
    }

    /**
     * Get base URL
     */
    public static function baseUrl()
    {
        $config = require __DIR__ . '/../../config/config.php';
        return $config['app']['url'];
    }

    /**
     * Generate URL with dynamic base path detection
     */
    public static function url($path = '')
    {
        // Try to get base path from config first
        $config = require __DIR__ . '/../../config/config.php';
        $basePath = $config['app']['base_path'] ?? '';
        
        // If no base path in config, detect it dynamically
        if (!$basePath) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            if ($scriptName) {
                // Extract base path from script name (remove /public/index.php)
                $basePath = dirname(dirname($scriptName));
                if ($basePath === '/' || $basePath === '\\') {
                    $basePath = '';
                }
            }
        }
        
        // Clean up the path
        $path = ltrim($path, '/');
        
        if ($basePath && $basePath !== '') {
            return rtrim($basePath, '/') . '/' . $path;
        }
        
        return '/' . $path;
    }
}

/**
 * Flash Message Helper
 */
class Flash
{
    /**
     * Set flash message
     */
    public static function set($type, $message)
    {
        Auth::init(); // Ensure session is started
        
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get flash message
     */
    public static function get($type)
    {
        Auth::init();
        
        if (isset($_SESSION['flash'][$type])) {
            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        
        return null;
    }

    /**
     * Check if flash message exists
     */
    public static function has($type)
    {
        Auth::init();
        return isset($_SESSION['flash'][$type]);
    }

    /**
     * Get all flash messages
     */
    public static function all()
    {
        Auth::init();
        
        $messages = $_SESSION['flash'] ?? [];
        $_SESSION['flash'] = [];
        
        return $messages;
    }

    /**
     * Set success message
     */
    public static function success($message)
    {
        self::set('success', $message);
    }

    /**
     * Set error message
     */
    public static function error($message)
    {
        self::set('error', $message);
    }

    /**
     * Set warning message
     */
    public static function warning($message)
    {
        self::set('warning', $message);
    }

    /**
     * Set info message
     */
    public static function info($message)
    {
        self::set('info', $message);
    }
}
