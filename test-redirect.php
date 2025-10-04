<?php
// Test script to verify dynamic base URL handling
echo "<h2>Dynamic Base URL System Test</h2>";

// Test Response::url() method
require_once __DIR__ . '/app/Helpers/response.php';

echo "<h3>Environment Variables:</h3>";
echo "APP_URL: " . ($_ENV['APP_URL'] ?? 'NOT SET') . "<br>";
echo "APP_BASE_PATH: " . ($_ENV['APP_BASE_PATH'] ?? 'NOT SET') . "<br>";

echo "<h3>Server Variables:</h3>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "<br>";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "<br>";
echo "dirname(dirname(SCRIPT_NAME)): " . dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')) . "<br>";

echo "<h3>Configuration (after env loading):</h3>";
$config = require __DIR__ . '/config/config.php';
echo "App URL: " . $config['app']['url'] . "<br>";
echo "Base Path: " . $config['app']['base_path'] . "<br>";

echo "<h3>Response::url() Tests:</h3>";
echo "Response::url('') = " . Response::url('') . "<br>";
echo "Response::url('login') = " . Response::url('login') . "<br>";
echo "Response::url('daily') = " . Response::url('daily') . "<br>";
echo "Response::url('dashboard') = " . Response::url('dashboard') . "<br>";

echo "<h3>Test Links:</h3>";
echo "<a href='" . Response::url('') . "'>Home</a><br>";
echo "<a href='" . Response::url('login') . "'>Login</a><br>";
echo "<a href='" . Response::url('dashboard') . "'>Dashboard</a><br>";

echo "<h3>Dynamic Detection Test:</h3>";
// Test what happens if we clear the base path
$_ENV['APP_BASE_PATH'] = '';
echo "With empty APP_BASE_PATH:<br>";
echo "Response::url('test') = " . Response::url('test') . "<br>";
?>
