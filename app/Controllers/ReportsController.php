<?php
/**
 * Professional Reports Controller
 * Comprehensive reporting and analytics system
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/DailyTxn.php';
require_once __DIR__ . '/../Models/MonthLock.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/response.php';
require_once __DIR__ . '/../Helpers/money.php';

class ReportsController
{
    private $dailyTxnModel;
    private $monthLockModel;
    private $db;

    public function __construct()
    {
        $this->dailyTxnModel = new DailyTxn();
        $this->monthLockModel = new MonthLock();
        $this->db = Database::getInstance();
    }

    /**
     * Show reports dashboard
     */
    public function index()
    {
        Auth::requirePermission('view_reports');

        // Get available date range
        $dateRange = $this->getAvailableDateRange();
        
        // Get report types
        $reportTypes = $this->getReportTypes();

        $data = [
            'title' => 'Professional Reports & Analytics - Daily Statement App',
            'date_range' => $dateRange,
            'report_types' => $reportTypes,
            'default_start_date' => $dateRange['min_date'] ?? date('Y-m-01'),
            'default_end_date' => $dateRange['max_date'] ?? date('Y-m-d')
        ];

        include __DIR__ . '/../Views/reports/index.php';
    }

    /**
     * Test reports system - debugging endpoint
     */
    public function test()
    {
        Auth::requirePermission('view_reports');

        echo "<h2>Reports System Test</h2>";

        // Test 1: Database connection
        echo "<h3>1. Database Connection Test</h3>";
        try {
            $testQuery = "SELECT 1 as test";
            $result = $this->db->fetch($testQuery);
            echo "<span style='color: green'>✓ Database connection successful</span><br>";
        } catch (Exception $e) {
            echo "<span style='color: red'>✗ Database connection failed: " . $e->getMessage() . "</span><br>";
        }

        // Test 2: Check if v_daily_txn view exists
        echo "<h3>2. View Existence Test</h3>";
        try {
            $viewTest = "SELECT COUNT(*) as count FROM v_daily_txn LIMIT 1";
            $result = $this->db->fetch($viewTest);
            echo "<span style='color: green'>✓ v_daily_txn view exists, contains " . $result['count'] . " records</span><br>";
        } catch (Exception $e) {
            echo "<span style='color: red'>✗ v_daily_txn view error: " . $e->getMessage() . "</span><br>";
            
            // Try alternative table
            try {
                $altTest = "SELECT COUNT(*) as count FROM daily_txn LIMIT 1";
                $altResult = $this->db->fetch($altTest);
                echo "<span style='color: orange'>! Alternative: daily_txn table exists with " . $altResult['count'] . " records</span><br>";
            } catch (Exception $altE) {
                echo "<span style='color: red'>✗ daily_txn table also not found: " . $altE->getMessage() . "</span><br>";
            }
        }

        // Test 3: Sample query
        echo "<h3>3. Sample Query Test</h3>";
        try {
            $sampleQuery = "SELECT txn_date, ca, fi FROM v_daily_txn ORDER BY txn_date DESC LIMIT 5";
            $sampleData = $this->db->fetchAll($sampleQuery);
            echo "<span style='color: green'>✓ Sample query successful, " . count($sampleData) . " rows returned</span><br>";
            echo "<pre>" . print_r($sampleData, true) . "</pre>";
        } catch (Exception $e) {
            echo "<span style='color: red'>✗ Sample query failed: " . $e->getMessage() . "</span><br>";
        }

        // Test 4: Date range test
        echo "<h3>4. Date Range Test</h3>";
        try {
            $dateRange = $this->getAvailableDateRange();
            echo "<span style='color: green'>✓ Date range: " . json_encode($dateRange) . "</span><br>";
        } catch (Exception $e) {
            echo "<span style='color: red'>✗ Date range failed: " . $e->getMessage() . "</span><br>";
        }

        // Test 5: Report generation test
        echo "<h3>5. Report Generation Test</h3>";
        try {
            $testStart = date('Y-m-01');
            $testEnd = date('Y-m-d');
            $reportData = $this->generateFinancialSummary($testStart, $testEnd, 'day');
            echo "<span style='color: green'>✓ Report generation successful</span><br>";
            echo "<pre>Chart data points: " . count($reportData['chart']['datasets'][0]['data'] ?? []) . "</pre>";
        } catch (Exception $e) {
            echo "<span style='color: red'>✗ Report generation failed: " . $e->getMessage() . "</span><br>";
        }

        echo "<hr><p><a href='" . Response::url('reports') . "'>← Back to Reports</a></p>";
    }

    /**
     * Simple API test endpoint
     */
    public function apiTest()
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode([
            'success' => true,
            'message' => 'API endpoint is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => Auth::check() ? Auth::user()['name'] : 'Not authenticated'
        ]);
        exit;
    }

    /**
     * Get report data (API)
     */
    public function getData()
    {
        // Suppress warnings to prevent HTML output in JSON response
        error_reporting(E_ERROR | E_PARSE);
        
        // Ensure we always return JSON, even on fatal errors
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && $error['type'] === E_ERROR) {
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Fatal error occurred',
                        'debug' => ['error' => $error['message'], 'file' => $error['file'], 'line' => $error['line']]
                    ]);
                }
            }
        });
        
        // Check authentication and permissions for API endpoint
        if (!Auth::check()) {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required',
                'debug' => ['error_type' => 'AuthenticationError']
            ]);
            exit;
        }
        
        if (!Auth::can('view_reports')) {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient permissions to view reports',
                'debug' => ['error_type' => 'PermissionError']
            ]);
            exit;
        }

        try {
            // Log request parameters for debugging
            error_log("Reports getData request: " . json_encode($_GET));
            error_log("Reports getData method reached successfully");

            // Validate and sanitize inputs
            $reportType = $_GET['report_type'] ?? 'financial_summary';
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $groupBy = $_GET['group_by'] ?? 'day';
            $export = $_GET['export'] ?? false;

            error_log("Report parameters: type=$reportType, start=$startDate, end=$endDate, group=$groupBy");

            // Test database connection
            $testQuery = "SELECT COUNT(*) as count FROM v_daily_txn LIMIT 1";
            try {
                $testResult = $this->db->fetch($testQuery);
                error_log("Database test successful: " . json_encode($testResult));
            } catch (Exception $dbTest) {
                error_log("Database test failed: " . $dbTest->getMessage());
                throw new Exception("Database connection failed: " . $dbTest->getMessage());
            }

            // Validate date range
            if (!$this->validateDateRange($startDate, $endDate)) {
                throw new Exception('Invalid date range provided');
            }

            // Generate report based on type
            $reportData = $this->generateReport($reportType, $startDate, $endDate, $groupBy);

            error_log("Report data generated successfully");

            if ($export) {
                $this->exportReport($reportData, $export, $reportType);
                return;
            }

            // Prevent caching of API responses
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo json_encode([
                'success' => true,
                'data' => $reportData,
                'debug' => [
                    'report_type' => $reportType,
                    'date_range' => $startDate . ' to ' . $endDate,
                    'group_by' => $groupBy,
                    'data_count' => is_array($reportData) ? count($reportData) : 'N/A',
                    'data_type' => is_array($reportData) && count($reportData) > 0 ? gettype($reportData[0]) : gettype($reportData)
                ]
            ]);

        } catch (Exception $e) {
            error_log("Reports getData error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("Request parameters: " . json_encode($_GET));
            
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => [
                    'error_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'request_params' => $_GET,
                    'php_version' => PHP_VERSION,
                    'memory_usage' => memory_get_usage(true)
                ]
            ]);
        }
        exit;
    }

    /**
     * Get available report types
     */
    private function getReportTypes()
    {
        return [
            // 'financial_summary' => [
            //     'name' => 'Financial Summary',
            //     'description' => 'Comprehensive financial overview with all metrics',
            //     'metrics' => ['ca', 'ga', 'je', 'fi', 'ag1', 'ag2', 're']
            // ],
            // 'profit_analysis' => [
            //     'name' => 'Profit Analysis',
            //     'description' => '',
            //     'metrics' => ['fi', 're', 'ca']
            // ],
            // 'comparative_analysis' => [
            //     'name' => 'Comparative Analysis',
            //     'description' => '',
            //     'metrics' => ['ca', 'fi', 'ga', 'je']
            // ],
            'ca_analysis' => [
                'name' => 'CA Analysis',
                'description' => '',
                'metrics' => ['ca']
            ],
            'ga_analysis' => [
                'name' => 'GA Analysis',
                'description' => '',
                'metrics' => ['ga']
            ],
            're_analysis' => [
                'name' => 'RE Analysis',
                'description' => '',
                'metrics' => ['re']
            ],
            'je_analysis' => [
                'name' => 'JE Analysis',
                'description' => '',
                'metrics' => ['je']
            ]
        ];
    }

    /**
     * Generate report based on type
     */
    private function generateReport($reportType, $startDate, $endDate, $groupBy)
    {
        switch ($reportType) {
            case 'financial_summary':
                return $this->generateFinancialSummary($startDate, $endDate, $groupBy);
            case 'profit_analysis':
                return $this->generateProfitAnalysis($startDate, $endDate, $groupBy);
            case 'comparative_analysis':
                return $this->generateComparativeAnalysis($startDate, $endDate, $groupBy);
            case 'ca_analysis':
                return $this->generateCAAnalysis($startDate, $endDate, $groupBy);
            case 'ga_analysis':
                return $this->generateGAAnalysis($startDate, $endDate, $groupBy);
            case 're_analysis':
                return $this->generateREAnalysis($startDate, $endDate, $groupBy);
            case 'je_analysis':
                return $this->generateJEAnalysis($startDate, $endDate, $groupBy);
            default:
                throw new Exception('Invalid report type');
        }
    }

    /**
     * Generate Financial Summary Report
     */
    private function generateFinancialSummary($startDate, $endDate, $groupBy)
    {
        try {
            $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate);
            error_log("Financial Summary SQL: " . $sql);
            error_log("Parameters: " . json_encode([$startDate, $endDate]));
            
            $data = $this->db->fetchAll($sql, [$startDate, $endDate]);
            error_log("Query returned " . count($data) . " rows");

            $chartData = $this->processChartData($data, ['ca', 'fi', 'ga', 'je'], $groupBy);
            $summary = $this->calculateSummaryStats($data, ['ca', 'fi', 'ga', 'je']);
            $tableData = $this->processTableData($data, $groupBy);

            return [
                'type' => 'financial_summary',
                'title' => 'Financial Summary Report',
                'period' => $this->formatPeriod($startDate, $endDate),
                'chart' => $chartData,
                'summary' => $summary,
                'table' => $tableData,
                'insights' => $this->generateFinancialInsights($summary)
            ];
        } catch (Exception $e) {
            error_log("generateFinancialSummary error: " . $e->getMessage());
            throw new Exception("Failed to generate financial summary: " . $e->getMessage());
        }
    }

    /**
     * Generate Profit Analysis Report
     */
    private function generateProfitAnalysis($startDate, $endDate, $groupBy)
    {
        $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate);
        $data = $this->db->fetchAll($sql, [$startDate, $endDate]);

        $chartData = $this->processChartData($data, ['fi', 're'], $groupBy);
        $summary = $this->calculateSummaryStats($data, ['fi', 're', 'ca']);
        
        // Calculate profitability ratios
        $profitability = $this->calculateProfitabilityMetrics($data);
        
        return [
            'type' => 'profit_analysis',
            'title' => 'Profit Analysis Report',
            'period' => $this->formatPeriod($startDate, $endDate),
            'chart' => $chartData,
            'summary' => array_merge($summary, $profitability),
            'table' => $this->processTableData($data, $groupBy),
            'insights' => $this->generateProfitInsights($summary, $profitability)
        ];
    }


    /**
     * Generate Comparative Analysis Report
     */
    private function generateComparativeAnalysis($startDate, $endDate, $groupBy)
    {
        try {
            error_log("Comparative Analysis: start=$startDate, end=$endDate, group=$groupBy");
            
            // Get current period data
            error_log("Getting current period data...");
            $currentData = $this->getComparativePeriodData($startDate, $endDate, $groupBy);
            error_log("Current data rows: " . count($currentData['chart']['datasets'][0]['data'] ?? []));
            
            // Simple previous period calculation (30 days back)
            error_log("Calculating simple previous period...");
            $prevStart = date('Y-m-d', strtotime($startDate . ' -30 days'));
            $prevEnd = date('Y-m-d', strtotime($endDate . ' -30 days'));
            error_log("Simple previous period: $prevStart to $prevEnd");
            
            error_log("Getting previous period data...");
            $previousData = $this->getComparativePeriodData($prevStart, $prevEnd, $groupBy);
            error_log("Previous data rows: " . count($previousData['chart']['datasets'][0]['data'] ?? []));
            
            // Simple comparison
            $currentTotal = $currentData['summary']['ca']['total'] ?? 0;
            $previousTotal = $previousData['summary']['ca']['total'] ?? 0;
            $change = $previousTotal > 0 ? (($currentTotal - $previousTotal) / $previousTotal) * 100 : 0;
            
            $comparison = [
                'ca' => [
                    'current' => $currentTotal,
                    'previous' => $previousTotal,
                    'change' => $change,
                    'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
                ]
            ];
            
            error_log("Simple comparison calculated: " . json_encode($comparison));
            
            $insights = [
                [
                    'type' => $change > 0 ? 'success' : 'info',
                    'title' => 'CA Comparison',
                    'message' => sprintf('CA has %s by %.1f%% compared to previous period', 
                        $change > 0 ? 'increased' : 'decreased', abs($change))
                ]
            ];
            
            return [
                'type' => 'comparative_analysis',
                'title' => 'Comparative Analysis Report',
                'period' => $this->formatPeriod($startDate, $endDate),
                'current_period' => $currentData,
                'previous_period' => $previousData,
                'comparison' => $comparison,
                'insights' => $insights,
                'chart' => [
                    'type' => 'bar',
                    'data' => [
                        'labels' => ['Current Period', 'Previous Period'],
                        'datasets' => [
                            [
                                'label' => 'CA Amount',
                                'data' => [$currentTotal, $previousTotal],
                                'backgroundColor' => ['#667eea', '#764ba2']
                            ]
                        ]
                    ]
                ]
            ];
        } catch (Exception $e) {
            error_log("generateComparativeAnalysis error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw new Exception("Failed to generate comparative analysis: " . $e->getMessage());
        }
    }

    /**
     * Build grouped SQL query
     */
    private function buildGroupedQuery($groupBy, $startDate, $endDate, $includeRates = false)
    {
        $dateFormat = $this->getDateFormat($groupBy);
        $rateFields = $includeRates ? ', AVG(rate_ag1) as avg_rate_ag1, AVG(rate_ag2) as avg_rate_ag2' : '';
        
        return "SELECT 
                    {$dateFormat} as period_key,
                    COUNT(*) as transaction_count,
                    SUM(ca) as total_ca,
                    SUM(ga) as total_ga,
                    SUM(je) as total_je,
                    SUM(ag1) as total_ag1,
                    SUM(ag2) as total_ag2,
                    SUM(re) as total_re,
                    SUM(fi) as total_fi,
                    AVG(ca) as avg_ca,
                    AVG(fi) as avg_fi
                    {$rateFields}
                FROM v_daily_txn 
                WHERE txn_date BETWEEN ? AND ?
                GROUP BY {$dateFormat}
                ORDER BY period_key";
    }

    /**
     * Get date format for grouping
     */
    private function getDateFormat($groupBy)
    {
        switch ($groupBy) {
            case 'week':
                return "DATE_FORMAT(txn_date, '%Y-%u')";
            case 'month':
                return "DATE_FORMAT(txn_date, '%Y-%m')";
            case 'year':
                return "DATE_FORMAT(txn_date, '%Y')";
            default:
                return "txn_date";
        }
    }

    /**
     * Process chart data for multiple metrics
     */
    private function processChartData($data, $metrics, $groupBy)
    {
        $labels = [];
        $datasets = [];
        
        // Initialize datasets for each metric
        $colors = [
            'ca' => ['border' => '#007bff', 'background' => 'rgba(0, 123, 255, 0.1)'],
            'fi' => ['border' => '#28a745', 'background' => 'rgba(40, 167, 69, 0.1)'],
            'ga' => ['border' => '#ffc107', 'background' => 'rgba(255, 193, 7, 0.1)'],
            'je' => ['border' => '#dc3545', 'background' => 'rgba(220, 53, 69, 0.1)'],
            're' => ['border' => '#17a2b8', 'background' => 'rgba(23, 162, 184, 0.1)'],
            'ag1' => ['border' => '#6f42c1', 'background' => 'rgba(111, 66, 193, 0.1)'],
            'ag2' => ['border' => '#e83e8c', 'background' => 'rgba(232, 62, 140, 0.1)']
        ];
        
        foreach ($metrics as $metric) {
            $datasets[$metric] = [
                'label' => strtoupper($metric),
                'data' => [],
                'borderColor' => $colors[$metric]['border'] ?? '#6c757d',
                'backgroundColor' => $colors[$metric]['background'] ?? 'rgba(108, 117, 125, 0.1)',
                'borderWidth' => 2,
                'fill' => false,
                'tension' => 0.4
            ];
        }
        
        foreach ($data as $row) {
            $labels[] = $this->formatPeriodLabel($row['period_key'], $groupBy);
            
            foreach ($metrics as $metric) {
                $datasets[$metric]['data'][] = round($row['total_' . $metric] ?? 0, 2);
            }
        }
        
        return [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => array_values($datasets)
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'y' => [
                        'beginAtZero' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummaryStats($data, $metrics)
    {
        $stats = [];
        
        foreach ($metrics as $metric) {
            $values = array_column($data, 'total_' . $metric);
            $values = array_map('floatval', $values);
            
            $stats[$metric] = [
                'total' => array_sum($values),
                'average' => count($values) > 0 ? array_sum($values) / count($values) : 0,
                'max' => count($values) > 0 ? max($values) : 0,
                'min' => count($values) > 0 ? min($values) : 0,
                'count' => count($values)
            ];
        }
        
        return $stats;
    }

    /**
     * Validate date range
     */
    private function validateDateRange($startDate, $endDate)
    {
        $start = DateTime::createFromFormat('Y-m-d', $startDate);
        $end = DateTime::createFromFormat('Y-m-d', $endDate);
        
        return $start && $end && $start <= $end;
    }

    /**
     * Get available date range from database
     */
    private function getAvailableDateRange()
    {
        $result = $this->db->fetch(
            "SELECT MIN(txn_date) as min_date, MAX(txn_date) as max_date FROM v_daily_txn"
        );
        
        return $result ?: ['min_date' => date('Y-m-01'), 'max_date' => date('Y-m-d')];
    }

    /**
     * Format period label for display
     */
    private function formatPeriodLabel($periodKey, $groupBy)
    {
        switch ($groupBy) {
            case 'week':
                $parts = explode('-', $periodKey);
                return 'Week ' . $parts[1] . ', ' . $parts[0];
            case 'month':
                return date('M Y', strtotime($periodKey . '-01'));
            case 'year':
                return $periodKey;
            default:
                return date('M j', strtotime($periodKey));
        }
    }

    /**
     * Format period range for display
     */
    private function formatPeriod($startDate, $endDate)
    {
        if ($startDate === $endDate) {
            return date('F j, Y', strtotime($startDate));
        }
        
        return date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
    }

    /**
     * Process table data
     */
    private function processTableData($data, $groupBy)
    {
        $tableData = [];
        
        foreach ($data as $row) {
            $tableData[] = [
                'period' => $this->formatPeriodLabel($row['period_key'], $groupBy),
                'transactions' => $row['transaction_count'],
                'ca' => $row['total_ca'],
                'ga' => $row['total_ga'],
                'je' => $row['total_je'],
                'fi' => $row['total_fi'],
                'avg_fi' => $row['avg_fi']
            ];
        }
        
        return $tableData;
    }

    /**
     * Generate financial insights
     */
    private function generateFinancialInsights($summary)
    {
        $insights = [];
        
        if (isset($summary['fi']) && isset($summary['ca'])) {
            $profitMargin = $summary['ca']['total'] > 0 ? 
                ($summary['fi']['total'] / $summary['ca']['total']) * 100 : 0;
            
            $insights[] = [
                'type' => 'info',
                'title' => 'Profit Margin',
                'message' => sprintf('Overall profit margin is %.1f%%', $profitMargin)
            ];
        }
        
        return $insights;
    }

    /**
     * Export report data
     */
    private function exportReport($reportData, $format, $reportType)
    {
        if ($format === 'csv') {
            $this->exportCSV($reportData, $reportType);
        }
    }

    /**
     * Export data as CSV
     */
    private function exportCSV($reportData, $reportType)
    {
        $filename = $reportType . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (isset($reportData['table']) && !empty($reportData['table'])) {
            // Write headers
            $headers = array_keys($reportData['table'][0]);
            fputcsv($output, $headers);
            
            // Write data
            foreach ($reportData['table'] as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Calculate profitability metrics
     */
    private function calculateProfitabilityMetrics($data)
    {
        $totalCA = array_sum(array_column($data, 'total_ca'));
        $totalFI = array_sum(array_column($data, 'total_fi'));
        $totalRE = array_sum(array_column($data, 'total_re'));
        
        $profitMargin = $totalCA > 0 ? ($totalFI / $totalCA) * 100 : 0;
        $efficiencyRatio = $totalCA > 0 ? ($totalRE / $totalCA) * 100 : 0;
        $avgDailyProfit = count($data) > 0 ? $totalFI / count($data) : 0;
        
        return [
            'profit_margin' => [
                'total' => $profitMargin,
                'average' => $profitMargin,
                'max' => $profitMargin,
                'min' => $profitMargin,
                'count' => 1
            ],
            'efficiency_ratio' => [
                'total' => $efficiencyRatio,
                'average' => $efficiencyRatio,
                'max' => $efficiencyRatio,
                'min' => $efficiencyRatio,
                'count' => 1
            ],
            'total_profit' => [
                'total' => $totalFI,
                'average' => $avgDailyProfit,
                'max' => $totalFI,
                'min' => $totalFI,
                'count' => count($data)
            ]
        ];
    }

    /**
     * Get comparative period data
     */
    private function getComparativePeriodData($startDate, $endDate, $groupBy)
    {
        $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate);
        $data = $this->db->fetchAll($sql, [$startDate, $endDate]);
        
        return [
            'summary' => $this->calculateSummaryStats($data, ['ca', 'fi', 'ga', 'je']),
            'chart' => $this->processChartData($data, ['ca', 'fi'], $groupBy),
            'period' => $this->formatPeriod($startDate, $endDate)
        ];
    }

    /**
     * Calculate previous period
     */
    private function calculatePreviousPeriod($startDate, $endDate)
    {
        try {
            error_log("calculatePreviousPeriod: start=$startDate, end=$endDate");
            
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $diff = $start->diff($end);
            
            error_log("Date diff: " . $diff->days . " days");
            
            $prevEnd = clone $start;
            $prevEnd->sub(new DateInterval('P1D'));
            
            $prevStart = clone $prevEnd;
            $prevStart->sub($diff);
            
            $result = [
                'start' => $prevStart->format('Y-m-d'),
                'end' => $prevEnd->format('Y-m-d')
            ];
            
            error_log("Previous period calculated: " . json_encode($result));
            return $result;
            
        } catch (Exception $e) {
            error_log("calculatePreviousPeriod error: " . $e->getMessage());
            throw new Exception("Failed to calculate previous period: " . $e->getMessage());
        }
    }

    /**
     * Calculate period comparison
     */
    private function calculatePeriodComparison($currentData, $previousData)
    {
        $comparison = [];
        $metrics = ['ca', 'fi', 'ga', 'je'];
        
        foreach ($metrics as $metric) {
            $current = $currentData['summary'][$metric]['total'] ?? 0;
            $previous = $previousData['summary'][$metric]['total'] ?? 0;
            
            $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
            
            $comparison[$metric] = [
                'current' => $current,
                'previous' => $previous,
                'change' => $change,
                'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
            ];
        }
        
        return $comparison;
    }

    /**
     * Generate comparative insights
     */
    private function generateComparativeInsights($comparison)
    {
        $insights = [];
        
        foreach ($comparison as $metric => $data) {
            if (abs($data['change']) > 10) {
                $direction = $data['change'] > 0 ? 'increased' : 'decreased';
                $insights[] = [
                    'type' => $data['change'] > 0 ? 'success' : 'warning',
                    'title' => strtoupper($metric) . ' Change',
                    'message' => sprintf('%s has %s by %.1f%% compared to previous period', 
                        strtoupper($metric), $direction, abs($data['change']))
                ];
            }
        }
        
        return $insights;
    }

    /**
     * Generate profit insights
     */
    private function generateProfitInsights($summary, $profitability)
    {
        $insights = [];
        
        $profitMargin = $profitability['profit_margin']['total'] ?? 0;
        
        if ($profitMargin < 50) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Low Profit Margin',
                'message' => sprintf('Profit margin is %.1f%%, consider revenue optimization', $profitMargin)
            ];
        }
        
        return $insights;
    }

    /**
     * Calculate volatility (standard deviation)
     */
    private function calculateVolatility($values)
    {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);
        
        return sqrt($variance) * 100; // Return as percentage
    }

    /**
     * Generate CA (Customer Acquisition) Analysis Report
     */
    private function generateCAAnalysis($startDate, $endDate, $groupBy)
    {
        try {
            $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate);
            $data = $this->db->fetchAll($sql, [$startDate, $endDate]);
            
            $chartData = $this->processChartData($data, ['ca'], $groupBy);
            $summary = $this->calculateSummaryStats($data, ['ca']);
            
            // CA-specific analysis
            $caValues = array_column($data, 'total_ca');
            $caAnalysis = $this->calculateDetailedMetrics($caValues, 'CA');
            
            // Growth analysis
            $growthAnalysis = $this->calculateGrowthTrends($data, 'ca');
            
            // Performance benchmarks
            $benchmarks = $this->calculateCABenchmarks($data);
            
            return [
                'type' => 'ca_analysis',
                'title' => 'Customer Acquisition (CA) Analysis',
                'period' => $this->formatPeriod($startDate, $endDate),
                'chart' => $chartData,
                'summary' => array_merge($summary, $caAnalysis),
                'growth' => $growthAnalysis,
                'benchmarks' => $benchmarks,
                'table' => $this->processTableData($data, $groupBy),
                'insights' => $this->generateCAInsights($summary, $caAnalysis, $growthAnalysis)
            ];
        } catch (Exception $e) {
            error_log("generateCAAnalysis error: " . $e->getMessage());
            throw new Exception("Failed to generate CA analysis: " . $e->getMessage());
        }
    }

    /**
     * Generate GA (General & Administrative) Analysis Report
     */
    private function generateGAAnalysis($startDate, $endDate, $groupBy)
    {
        try {
            $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate);
            $data = $this->db->fetchAll($sql, [$startDate, $endDate]);
            
            // Debug logging for GA analysis
            error_log("GA Analysis: Retrieved " . count($data) . " rows for date range $startDate to $endDate");
            
            // Check if we have data
            if (empty($data)) {
                return [
                    'type' => 'ga_analysis',
                    'title' => 'General & Administrative (GA) Analysis',
                    'period' => $this->formatPeriod($startDate, $endDate),
                    'chart' => [
                        'type' => 'line',
                        'data' => [
                            'labels' => [],
                            'datasets' => [[
                                'label' => 'GA',
                                'data' => [],
                                'borderColor' => '#dc3545',
                                'backgroundColor' => 'rgba(220, 53, 69, 0.1)',
                                'borderWidth' => 2,
                                'fill' => false,
                                'tension' => 0.4
                            ]]
                        ],
                        'options' => [
                            'responsive' => true,
                            'maintainAspectRatio' => false,
                            'scales' => ['y' => ['beginAtZero' => true]]
                        ]
                    ],
                    'summary' => ['ga' => ['total' => 0, 'average' => 0, 'max' => 0, 'min' => 0, 'count' => 0]],
                    'efficiency' => [
                        'efficiency_ratio' => 0,
                        'cost_per_transaction' => 0,
                        'total_expenses' => 0,
                        'efficiency_rating' => 'no_data'
                    ],
                    'cost_control' => [
                        'budget' => 0,
                        'avg_spend' => 0,
                        'budget_utilization' => 0,
                        'variance' => 0,
                        'control_score' => 0
                    ],
                    'table' => [],
                    'insights' => [[
                        'type' => 'info',
                        'title' => 'No Data Available',
                        'message' => 'No GA data found for the selected date range. Please try a different date range.'
                    ]]
                ];
            }
            
            $chartData = $this->processChartData($data, ['ga'], $groupBy);
            $summary = $this->calculateSummaryStats($data, ['ga']);
            
            // GA-specific analysis
            $gaValues = array_column($data, 'total_ga');
            $gaAnalysis = $this->calculateDetailedMetrics($gaValues, 'GA');
            
            // Expense efficiency analysis
            $efficiencyAnalysis = $this->calculateGAEfficiency($data);
            
            // Cost control metrics
            $costControl = $this->calculateCostControlMetrics($data, 'ga');
            
            return [
                'type' => 'ga_analysis',
                'title' => 'General & Administrative (GA) Analysis',
                'period' => $this->formatPeriod($startDate, $endDate),
                'chart' => $chartData,
                'summary' => array_merge($summary, $gaAnalysis),
                'efficiency' => $efficiencyAnalysis,
                'cost_control' => $costControl,
                'table' => $this->processTableData($data, $groupBy),
                'insights' => $this->generateGAInsights($summary, $efficiencyAnalysis, $costControl)
            ];
        } catch (Exception $e) {
            error_log("generateGAAnalysis error: " . $e->getMessage());
            throw new Exception("Failed to generate GA analysis: " . $e->getMessage());
        }
    }

    /**
     * Generate RE (Revenue Enhancement) Analysis Report
     */
    private function generateREAnalysis($startDate, $endDate, $groupBy)
    {
        try {
            $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate);
            $data = $this->db->fetchAll($sql, [$startDate, $endDate]);
            
            $chartData = $this->processChartData($data, ['re'], $groupBy);
            $summary = $this->calculateSummaryStats($data, ['re']);
            
            // RE-specific analysis
            $reValues = array_column($data, 'total_re');
            $reAnalysis = $this->calculateDetailedMetrics($reValues, 'RE');
            
            // Revenue optimization analysis
            $optimizationAnalysis = $this->calculateREOptimization($data);
            
            // Performance tracking
            $performanceTracking = $this->calculateREPerformance($data);
            
            return [
                'type' => 're_analysis',
                'title' => 'Revenue Enhancement (RE) Analysis',
                'period' => $this->formatPeriod($startDate, $endDate),
                'chart' => $chartData,
                'summary' => array_merge($summary, $reAnalysis),
                'optimization' => $optimizationAnalysis,
                'performance' => $performanceTracking,
                'table' => $this->processTableData($data, $groupBy),
                'insights' => $this->generateREInsights($summary, $optimizationAnalysis, $performanceTracking)
            ];
        } catch (Exception $e) {
            error_log("generateREAnalysis error: " . $e->getMessage());
            throw new Exception("Failed to generate RE analysis: " . $e->getMessage());
        }
    }

    /**
     * Generate JE (Joint Expenses) Analysis Report
     */
    private function generateJEAnalysis($startDate, $endDate, $groupBy)
    {
        try {
            $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate);
            $data = $this->db->fetchAll($sql, [$startDate, $endDate]);
            
            $chartData = $this->processChartData($data, ['je'], $groupBy);
            $summary = $this->calculateSummaryStats($data, ['je']);
            
            // JE-specific analysis
            $jeValues = array_column($data, 'total_je');
            $jeAnalysis = $this->calculateDetailedMetrics($jeValues, 'JE');
            
            // Expense allocation analysis
            $allocationAnalysis = $this->calculateJEAllocation($data);
            
            // Cost optimization opportunities
            $optimizationOpportunities = $this->calculateJEOptimization($data);
            
            return [
                'type' => 'je_analysis',
                'title' => 'Joint Expenses (JE) Analysis',
                'period' => $this->formatPeriod($startDate, $endDate),
                'chart' => $chartData,
                'summary' => array_merge($summary, $jeAnalysis),
                'allocation' => $allocationAnalysis,
                'optimization' => $optimizationOpportunities,
                'table' => $this->processTableData($data, $groupBy),
                'insights' => $this->generateJEInsights($summary, $allocationAnalysis, $optimizationOpportunities)
            ];
        } catch (Exception $e) {
            error_log("generateJEAnalysis error: " . $e->getMessage());
            throw new Exception("Failed to generate JE analysis: " . $e->getMessage());
        }
    }

    /**
     * Calculate detailed metrics for specific metric
     */
    private function calculateDetailedMetrics($values, $metricName)
    {
        if (empty($values)) {
            return [
                'volatility' => 0,
                'consistency_score' => 0,
                'trend_direction' => 'stable',
                'growth_rate' => 0
            ];
        }

        $volatility = $this->calculateVolatility($values);
        $consistencyScore = max(0, 100 - $volatility);
        
        // Calculate trend
        $firstHalf = array_slice($values, 0, ceil(count($values) / 2));
        $secondHalf = array_slice($values, floor(count($values) / 2));
        
        $firstAvg = count($firstHalf) > 0 ? array_sum($firstHalf) / count($firstHalf) : 0;
        $secondAvg = count($secondHalf) > 0 ? array_sum($secondHalf) / count($secondHalf) : 0;
        
        $growthRate = $firstAvg > 0 ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;
        $trendDirection = $growthRate > 5 ? 'increasing' : ($growthRate < -5 ? 'decreasing' : 'stable');
        
        return [
            'volatility' => round($volatility, 2),
            'consistency_score' => round($consistencyScore, 1),
            'trend_direction' => $trendDirection,
            'growth_rate' => round($growthRate, 2)
        ];
    }

    /**
     * Calculate growth trends
     */
    private function calculateGrowthTrends($data, $metric)
    {
        $values = array_column($data, 'total_' . $metric);
        if (count($values) < 2) {
            return ['trend' => 'insufficient_data', 'rate' => 0];
        }

        $recent = array_slice($values, -3); // Last 3 periods
        $earlier = array_slice($values, 0, 3); // First 3 periods
        
        $recentAvg = array_sum($recent) / count($recent);
        $earlierAvg = array_sum($earlier) / count($earlier);
        
        $growthRate = $earlierAvg > 0 ? (($recentAvg - $earlierAvg) / $earlierAvg) * 100 : 0;
        
        return [
            'trend' => $growthRate > 10 ? 'strong_growth' : ($growthRate > 0 ? 'growth' : ($growthRate < -10 ? 'decline' : 'stable')),
            'rate' => round($growthRate, 2),
            'recent_average' => round($recentAvg, 2),
            'earlier_average' => round($earlierAvg, 2)
        ];
    }

    /**
     * Calculate CA benchmarks
     */
    private function calculateCABenchmarks($data)
    {
        $caValues = array_column($data, 'total_ca');
        $fiValues = array_column($data, 'total_fi');
        
        $totalCA = array_sum($caValues);
        $totalFI = array_sum($fiValues);
        
        $conversionRate = $totalCA > 0 ? ($totalFI / $totalCA) * 100 : 0;
        $avgDealSize = count($caValues) > 0 ? $totalCA / count($caValues) : 0;
        
        return [
            'conversion_rate' => round($conversionRate, 2),
            'average_deal_size' => round($avgDealSize, 2),
            'total_volume' => $totalCA,
            'performance_rating' => $conversionRate > 60 ? 'excellent' : ($conversionRate > 40 ? 'good' : 'needs_improvement')
        ];
    }

    /**
     * Calculate GA efficiency
     */
    private function calculateGAEfficiency($data)
    {
        $gaValues = array_column($data, 'total_ga');
        $caValues = array_column($data, 'total_ca');
        
        $totalGA = array_sum($gaValues);
        $totalCA = array_sum($caValues);
        
        $efficiencyRatio = $totalCA > 0 ? ($totalGA / $totalCA) * 100 : 0;
        $costPerTransaction = count($data) > 0 ? $totalGA / count($data) : 0;
        
        return [
            'efficiency_ratio' => round($efficiencyRatio, 2),
            'cost_per_transaction' => round($costPerTransaction, 2),
            'total_expenses' => $totalGA,
            'efficiency_rating' => $efficiencyRatio < 15 ? 'excellent' : ($efficiencyRatio < 25 ? 'good' : 'needs_optimization')
        ];
    }

    /**
     * Calculate cost control metrics
     */
    private function calculateCostControlMetrics($data, $metric)
    {
        $values = array_column($data, 'total_' . $metric);
        
        // Check if values array is empty to prevent max() error
        if (empty($values)) {
            return [
                'budget' => 0,
                'avg_spend' => 0,
                'budget_utilization' => 0,
                'variance' => 0,
                'control_score' => 0
            ];
        }
        
        $budget = max($values) * 1.1; // Assume budget is 110% of max
        
        $avgSpend = count($values) > 0 ? array_sum($values) / count($values) : 0;
        $budgetUtilization = $budget > 0 ? ($avgSpend / $budget) * 100 : 0;
        
        return [
            'budget_utilization' => round($budgetUtilization, 2),
            'average_spend' => round($avgSpend, 2),
            'max_spend' => max($values),
            'control_status' => $budgetUtilization < 80 ? 'under_control' : ($budgetUtilization < 95 ? 'monitor' : 'over_budget')
        ];
    }

    /**
     * Calculate RE optimization
     */
    private function calculateREOptimization($data)
    {
        $reValues = array_column($data, 'total_re');
        $caValues = array_column($data, 'total_ca');
        
        $totalRE = array_sum($reValues);
        $totalCA = array_sum($caValues);
        
        $enhancementRatio = $totalCA > 0 ? ($totalRE / $totalCA) * 100 : 0;
        $avgEnhancement = count($reValues) > 0 ? $totalRE / count($reValues) : 0;
        
        return [
            'enhancement_ratio' => round($enhancementRatio, 2),
            'average_enhancement' => round($avgEnhancement, 2),
            'total_enhancement' => $totalRE,
            'optimization_level' => $enhancementRatio > 20 ? 'high' : ($enhancementRatio > 10 ? 'moderate' : 'low')
        ];
    }

    /**
     * Calculate RE performance
     */
    private function calculateREPerformance($data)
    {
        $reValues = array_column($data, 'total_re');
        $fiValues = array_column($data, 'total_fi');
        
        $totalRE = array_sum($reValues);
        $totalFI = array_sum($fiValues);
        
        $contributionRatio = $totalFI > 0 ? ($totalRE / $totalFI) * 100 : 0;
        
        return [
            'contribution_to_profit' => round($contributionRatio, 2),
            'performance_score' => min(100, $contributionRatio * 2), // Scale to 100
            'status' => $contributionRatio > 30 ? 'excellent' : ($contributionRatio > 15 ? 'good' : 'needs_improvement')
        ];
    }

    /**
     * Calculate JE allocation
     */
    private function calculateJEAllocation($data)
    {
        $jeValues = array_column($data, 'total_je');
        $caValues = array_column($data, 'total_ca');
        
        $totalJE = array_sum($jeValues);
        $totalCA = array_sum($caValues);
        
        $allocationRatio = $totalCA > 0 ? ($totalJE / $totalCA) * 100 : 0;
        $avgAllocation = count($jeValues) > 0 ? $totalJE / count($jeValues) : 0;
        
        return [
            'allocation_ratio' => round($allocationRatio, 2),
            'average_allocation' => round($avgAllocation, 2),
            'total_allocation' => $totalJE,
            'allocation_efficiency' => $allocationRatio < 20 ? 'efficient' : ($allocationRatio < 30 ? 'moderate' : 'review_needed')
        ];
    }

    /**
     * Calculate JE optimization
     */
    private function calculateJEOptimization($data)
    {
        $jeValues = array_column($data, 'total_je');
        $volatility = $this->calculateVolatility($jeValues);
        
        $optimizationPotential = $volatility > 20 ? 'high' : ($volatility > 10 ? 'moderate' : 'low');
        $stabilityScore = max(0, 100 - $volatility);
        
        return [
            'optimization_potential' => $optimizationPotential,
            'stability_score' => round($stabilityScore, 1),
            'volatility' => round($volatility, 2),
            'recommendation' => $volatility > 20 ? 'implement_cost_controls' : 'maintain_current_approach'
        ];
    }

    /**
     * Generate CA insights
     */
    private function generateCAInsights($summary, $analysis, $growth)
    {
        $insights = [];
        
        if ($growth['rate'] > 15) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Strong CA Growth',
                'message' => sprintf('CA showing strong growth of %.1f%% - excellent acquisition performance', $growth['rate'])
            ];
        }
        
        if ($analysis['consistency_score'] > 80) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Consistent Performance',
                'message' => sprintf('CA performance is highly consistent (%.1f%% score)', $analysis['consistency_score'])
            ];
        }
        
        return $insights;
    }

    /**
     * Generate GA insights
     */
    private function generateGAInsights($summary, $efficiency, $costControl)
    {
        $insights = [];
        
        if ($efficiency['efficiency_ratio'] > 25) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'High GA Ratio',
                'message' => sprintf('GA expenses are %.1f%% of CA - consider cost optimization', $efficiency['efficiency_ratio'])
            ];
        }
        
        if ($costControl['control_status'] === 'under_control') {
            $insights[] = [
                'type' => 'success',
                'title' => 'Cost Control Effective',
                'message' => 'GA expenses are well controlled within budget parameters'
            ];
        }
        
        return $insights;
    }

    /**
     * Generate RE insights
     */
    private function generateREInsights($summary, $optimization, $performance)
    {
        $insights = [];
        
        if ($performance['contribution_to_profit'] > 25) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Strong RE Contribution',
                'message' => sprintf('RE contributes %.1f%% to total profit - excellent enhancement', $performance['contribution_to_profit'])
            ];
        }
        
        if ($optimization['optimization_level'] === 'low') {
            $insights[] = [
                'type' => 'info',
                'title' => 'RE Optimization Opportunity',
                'message' => 'Revenue enhancement has potential for improvement - consider new strategies'
            ];
        }
        
        return $insights;
    }

    /**
     * Generate JE insights
     */
    private function generateJEInsights($summary, $allocation, $optimization)
    {
        $insights = [];
        
        if ($allocation['allocation_ratio'] > 25) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'High JE Allocation',
                'message' => sprintf('Joint expenses are %.1f%% of CA - review allocation efficiency', $allocation['allocation_ratio'])
            ];
        }
        
        if ($optimization['optimization_potential'] === 'high') {
            $insights[] = [
                'type' => 'info',
                'title' => 'Optimization Opportunity',
                'message' => 'High volatility in JE suggests optimization opportunities available'
            ];
        }
        
        return $insights;
    }
}
