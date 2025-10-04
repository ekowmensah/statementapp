<?php
/**
 * Daily Statement App - Front Controller
 * Single entry point for all requests
 */

// Start output buffering
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('America/New_York');

// Include configuration and dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/csrf.php';
require_once __DIR__ . '/../app/Helpers/validate.php';
require_once __DIR__ . '/../app/Helpers/response.php';
require_once __DIR__ . '/../app/Helpers/money.php';
require_once __DIR__ . '/../app/Helpers/rate_picker.php';

// Initialize authentication
Auth::init();

// Get the route from URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Remove query string from URI
$route = strtok($requestUri, '?');

// Remove base path if running in subdirectory
$config = require __DIR__ . '/../config/config.php';
$basePath = $config['app']['base_path'] ?? '';
if ($basePath && strpos($route, $basePath) === 0) {
    $route = substr($route, strlen($basePath));
}

// Ensure route starts with /
if (!$route || $route[0] !== '/') {
    $route = '/' . $route;
}

// Load routes configuration
$routes = require __DIR__ . '/../config/routes.php';

// Find matching route
$matchedRoute = null;
$routeParams = [];

foreach ($routes as $pattern => $handler) {
    // Parse pattern (e.g., "GET|POST /login" or "GET /daily/edit")
    $parts = explode(' ', $pattern, 2);
    $methods = explode('|', $parts[0]);
    $path = $parts[1] ?? $parts[0];
    
    // Check if method matches
    if (!in_array($requestMethod, $methods)) {
        continue;
    }
    
    // Simple route matching (exact match for now)
    if ($path === $route) {
        $matchedRoute = $handler;
        break;
    }
    
    // Handle parameterized routes (basic implementation)
    if (strpos($path, '{') !== false) {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $route, $matches)) {
            $matchedRoute = $handler;
            array_shift($matches); // Remove full match
            $routeParams = $matches;
            break;
        }
    }
}

// Handle route not found
if (!$matchedRoute) {
    // Check for common routes that might need redirects
    if ($route === '/' || $route === '') {
        if (Auth::check()) {
            Response::redirect('dashboard');
        } else {
            Response::redirect('login');
        }
    }
    
    // Debug information
    if ($config['app']['debug'] ?? false) {
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
        echo "<p><strong>Requested Route:</strong> " . htmlspecialchars($route) . "</p>";
        echo "<p><strong>Request URI:</strong> " . htmlspecialchars($requestUri) . "</p>";
        echo "<p><strong>Base Path:</strong> " . htmlspecialchars($basePath) . "</p>";
        echo "<p><strong>Request Method:</strong> " . htmlspecialchars($requestMethod) . "</p>";
        echo "<h3>Available Routes:</h3>";
        echo "<ul>";
        foreach ($routes as $pattern => $handler) {
            echo "<li>" . htmlspecialchars($pattern) . " => " . htmlspecialchars($handler) . "</li>";
        }
        echo "</ul>";
        exit;
    }
    
    http_response_code(404);
    echo "404 - Page Not Found: " . htmlspecialchars($route);
    exit;
}

// Parse controller and method
list($controllerName, $methodName) = explode('@', $matchedRoute);

// Build controller file path
$controllerFile = __DIR__ . "/../app/Controllers/{$controllerName}.php";

if (!file_exists($controllerFile)) {
    http_response_code(500);
    echo "500 - Controller not found: {$controllerName}";
    exit;
}

// Include controller
require_once $controllerFile;

// Check if controller class exists
if (!class_exists($controllerName)) {
    http_response_code(500);
    echo "500 - Controller class not found: {$controllerName}";
    exit;
}

// Create controller instance
$controller = new $controllerName();

// Check if method exists
if (!method_exists($controller, $methodName)) {
    http_response_code(500);
    echo "500 - Controller method not found: {$controllerName}@{$methodName}";
    exit;
}

// Authentication middleware (skip for login routes)
$publicRoutes = ['/login'];
$apiRoutes = ['/api/preview'];

if (!in_array($route, $publicRoutes) && !Auth::check()) {
    // Store intended URL for redirect after login
    if ($requestMethod === 'GET' && !in_array($route, $apiRoutes)) {
        $_SESSION['intended_url'] = $requestUri;
    }
    
    if (Response::expectsJson() || in_array($route, $apiRoutes)) {
        Response::error('Authentication required', null, 401);
    } else {
        Response::redirect('login');
    }
}

try {
    // Call controller method
    if (!empty($routeParams)) {
        call_user_func_array([$controller, $methodName], $routeParams);
    } else {
        $controller->$methodName();
    }
} catch (Exception $e) {
    // Log error
    error_log("Application Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    if (Response::expectsJson()) {
        Response::error('Internal server error', null, 500);
    } else {
        http_response_code(500);
        
        // Show detailed error in development
        $config = require __DIR__ . '/../config/config.php';
        if ($config['app']['debug'] ?? false) {
            echo "<h1>Application Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            echo "500 - Internal Server Error";
        }
    }
}

// End output buffering and send response
ob_end_flush();
