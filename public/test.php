<?php
echo "<h1>Test Page</h1>";
echo "<p>If you can see this, PHP is working.</p>";
echo "<p>Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";
echo "<p>Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</p>";
echo "<p>Query String: " . ($_SERVER['QUERY_STRING'] ?? 'Not set') . "</p>";

// Test if mod_rewrite is working
if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== '/accountstatement/public/test.php') {
    echo "<p style='color: green;'>✅ mod_rewrite appears to be working!</p>";
} else {
    echo "<p style='color: red;'>❌ mod_rewrite may not be working properly.</p>";
}

phpinfo();
?>
