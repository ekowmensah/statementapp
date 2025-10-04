<?php
/**
 * Quick test script for dashboard API endpoints
 */

// Test the KPIs endpoint
echo "Testing KPIs API endpoint...\n";
$kpisUrl = "http://localhost/accountstatement/public/api/dashboard/kpis?year=2024&month=10";
$kpisResponse = file_get_contents($kpisUrl);
echo "KPIs Response: " . $kpisResponse . "\n\n";

// Test the Chart Data endpoint
echo "Testing Chart Data API endpoint...\n";
$chartUrl = "http://localhost/accountstatement/public/api/dashboard/chart?type=monthly_trends&year=2024&month=10";
$chartResponse = file_get_contents($chartUrl);
echo "Chart Response: " . substr($chartResponse, 0, 200) . "...\n\n";

echo "API test completed.\n";
?>
