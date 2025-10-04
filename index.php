<?php
/**
 * Root redirect to public directory
 * This ensures clean URLs work properly
 */

// Get the current request URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Build the redirect URL to public directory
$redirectUrl = rtrim($requestUri, '/') . '/public/';

// Preserve query string if present
if (!empty($_SERVER['QUERY_STRING'])) {
    $redirectUrl .= '?' . $_SERVER['QUERY_STRING'];
}

// Perform 301 redirect
header('Location: ' . $redirectUrl, true, 301);
exit;
?>
