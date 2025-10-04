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
     * Get report data (API)
     */
    public function getData()
    {
        Auth::requirePermission('view_reports');

        try {
            // Validate and sanitize inputs
            $reportType = $_GET['report_type'] ?? 'financial_summary';
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $groupBy = $_GET['group_by'] ?? 'day';
            $export = $_GET['export'] ?? false;

            // Validate date range
            if (!$this->validateDateRange($startDate, $endDate)) {
                throw new Exception('Invalid date range provided');
            }

            // Generate report based on type
            $reportData = $this->generateReport($reportType, $startDate, $endDate, $groupBy);

            // Handle export
            if ($export) {
                $this->exportReport($reportData, $export, $reportType);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $reportData
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
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
            'financial_summary' => [
                'name' => 'Financial Summary',
                'description' => 'Comprehensive financial overview with all metrics',
                'metrics' => ['ca', 'ga', 'je', 'fi', 'ag1', 'ag2', 're']
            ],
            'profit_analysis' => [
                'name' => 'Profit Analysis',
                'description' => 'Focus on profitability and final income trends',
                'metrics' => ['fi', 're', 'ca']
            ],
            'rate_analysis' => [
                'name' => 'Rate Analysis',
                'description' => 'AG1 and AG2 rate performance over time',
                'metrics' => ['rate_ag1', 'rate_ag2']
            ],
            'expense_breakdown' => [
                'name' => 'Expense Breakdown',
                'description' => 'Analysis of GA and JE expenses',
                'metrics' => ['ga', 'je']
            ],
            'comparative_analysis' => [
                'name' => 'Comparative Analysis',
                'description' => 'Period-over-period comparison',
                'metrics' => ['ca', 'fi', 'ga', 'je']
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
            case 'rate_analysis':
                return $this->generateRateAnalysis($startDate, $endDate, $groupBy);
            case 'expense_breakdown':
                return $this->generateExpenseBreakdown($startDate, $endDate, $groupBy);
            case 'comparative_analysis':
                return $this->generateComparativeAnalysis($startDate, $endDate, $groupBy);
            default:
                throw new Exception('Invalid report type');
        }
    }

    /**
     * Generate Financial Summary Report
     */
    private function generateFinancialSummary($startDate, $endDate, $groupBy)
    {
        $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate);
        $data = $this->db->fetchAll($sql, [$startDate, $endDate]);

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
     * Generate Rate Analysis Report
     */
    private function generateRateAnalysis($startDate, $endDate, $groupBy)
    {
        $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate, true);
        $data = $this->db->fetchAll($sql, [$startDate, $endDate]);

        $chartData = $this->processRateChartData($data, $groupBy);
        $summary = $this->calculateRateSummaryStats($data);
        
        return [
            'type' => 'rate_analysis',
            'title' => 'Rate Analysis Report',
            'period' => $this->formatPeriod($startDate, $endDate),
            'chart' => $chartData,
            'summary' => $summary,
            'table' => $this->processRateTableData($data, $groupBy),
            'insights' => $this->generateRateInsights($summary)
        ];
    }

    /**
     * Generate Expense Breakdown Report
     */
    private function generateExpenseBreakdown($startDate, $endDate, $groupBy)
    {
        $sql = $this->buildGroupedQuery($groupBy, $startDate, $endDate);
        $data = $this->db->fetchAll($sql, [$startDate, $endDate]);

        $chartData = $this->processChartData($data, ['ga', 'je'], $groupBy);
        $summary = $this->calculateSummaryStats($data, ['ga', 'je', 'ca']);
        
        // Calculate expense ratios
        $expenseRatios = $this->calculateExpenseRatios($data);
        
        return [
            'type' => 'expense_breakdown',
            'title' => 'Expense Breakdown Report',
            'period' => $this->formatPeriod($startDate, $endDate),
            'chart' => $chartData,
            'summary' => array_merge($summary, $expenseRatios),
            'table' => $this->processTableData($data, $groupBy),
            'insights' => $this->generateExpenseInsights($summary, $expenseRatios)
        ];
    }

    /**
     * Generate Comparative Analysis Report
     */
    private function generateComparativeAnalysis($startDate, $endDate, $groupBy)
    {
        // Get current period data
        $currentData = $this->getComparativePeriodData($startDate, $endDate, $groupBy);
        
        // Get previous period data for comparison
        $previousPeriod = $this->calculatePreviousPeriod($startDate, $endDate);
        $previousData = $this->getComparativePeriodData($previousPeriod['start'], $previousPeriod['end'], $groupBy);
        
        $comparison = $this->calculatePeriodComparison($currentData, $previousData);
        
        return [
            'type' => 'comparative_analysis',
            'title' => 'Comparative Analysis Report',
            'period' => $this->formatPeriod($startDate, $endDate),
            'current_period' => $currentData,
            'previous_period' => $previousData,
            'comparison' => $comparison,
            'insights' => $this->generateComparativeInsights($comparison)
        ];
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
     * Process rate chart data
     */
    private function processRateChartData($data, $groupBy)
    {
        $labels = [];
        $ag1Data = [];
        $ag2Data = [];
        
        foreach ($data as $row) {
            $labels[] = $this->formatPeriodLabel($row['period_key'], $groupBy);
            $ag1Data[] = round(($row['avg_rate_ag1'] ?? 0) * 100, 2);
            $ag2Data[] = round(($row['avg_rate_ag2'] ?? 0) * 100, 2);
        }
        
        return [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'AG1 Rate (%)',
                        'data' => $ag1Data,
                        'borderColor' => '#007bff',
                        'backgroundColor' => 'rgba(0, 123, 255, 0.1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'AG2 Rate (%)',
                        'data' => $ag2Data,
                        'borderColor' => '#28a745',
                        'backgroundColor' => 'rgba(40, 167, 69, 0.1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4
                    ]
                ]
            ]
        ];
    }

    /**
     * Calculate rate summary statistics
     */
    private function calculateRateSummaryStats($data)
    {
        $ag1Rates = array_column($data, 'avg_rate_ag1');
        $ag2Rates = array_column($data, 'avg_rate_ag2');
        
        // Also get the actual AG1 and AG2 amounts for totals
        $ag1Amounts = array_column($data, 'total_ag1');
        $ag2Amounts = array_column($data, 'total_ag2');
        
        return [
            'ag1' => [
                'total' => array_sum($ag1Amounts),
                'average' => count($ag1Rates) > 0 ? array_sum($ag1Rates) / count($ag1Rates) * 100 : 0,
                'max' => count($ag1Rates) > 0 ? max($ag1Rates) * 100 : 0,
                'min' => count($ag1Rates) > 0 ? min($ag1Rates) * 100 : 0,
                'count' => count($ag1Rates),
                'volatility' => $this->calculateVolatility($ag1Rates)
            ],
            'ag2' => [
                'total' => array_sum($ag2Amounts),
                'average' => count($ag2Rates) > 0 ? array_sum($ag2Rates) / count($ag2Rates) * 100 : 0,
                'max' => count($ag2Rates) > 0 ? max($ag2Rates) * 100 : 0,
                'min' => count($ag2Rates) > 0 ? min($ag2Rates) * 100 : 0,
                'count' => count($ag2Rates),
                'volatility' => $this->calculateVolatility($ag2Rates)
            ]
        ];
    }

    /**
     * Process rate table data
     */
    private function processRateTableData($data, $groupBy)
    {
        $tableData = [];
        
        foreach ($data as $row) {
            $tableData[] = [
                'period' => $this->formatPeriodLabel($row['period_key'], $groupBy),
                'transactions' => $row['transaction_count'],
                'ag1_rate' => round(($row['avg_rate_ag1'] ?? 0) * 100, 2),
                'ag2_rate' => round(($row['avg_rate_ag2'] ?? 0) * 100, 2),
                'total_ca' => $row['total_ca'],
                'total_fi' => $row['total_fi']
            ];
        }
        
        return $tableData;
    }

    /**
     * Generate rate insights
     */
    private function generateRateInsights($summary)
    {
        $insights = [];
        
        if (isset($summary['ag1'])) {
            $ag1Volatility = $summary['ag1']['volatility'];
            if ($ag1Volatility > 0.5) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'High AG1 Rate Volatility',
                    'message' => sprintf('AG1 rates show high volatility (%.2f%%), consider rate stabilization', $ag1Volatility)
                ];
            }
        }
        
        return $insights;
    }

    /**
     * Calculate expense ratios
     */
    private function calculateExpenseRatios($data)
    {
        $totalCA = array_sum(array_column($data, 'total_ca'));
        $totalGA = array_sum(array_column($data, 'total_ga'));
        $totalJE = array_sum(array_column($data, 'total_je'));
        
        $gaRatio = $totalCA > 0 ? ($totalGA / $totalCA) * 100 : 0;
        $jeRatio = $totalCA > 0 ? ($totalJE / $totalCA) * 100 : 0;
        $totalExpenseRatio = $totalCA > 0 ? (($totalGA + $totalJE) / $totalCA) * 100 : 0;
        
        return [
            'ga_ratio' => [
                'total' => $gaRatio,
                'average' => $gaRatio,
                'max' => $gaRatio,
                'min' => $gaRatio,
                'count' => 1
            ],
            'je_ratio' => [
                'total' => $jeRatio,
                'average' => $jeRatio,
                'max' => $jeRatio,
                'min' => $jeRatio,
                'count' => 1
            ],
            'total_expense_ratio' => [
                'total' => $totalExpenseRatio,
                'average' => $totalExpenseRatio,
                'max' => $totalExpenseRatio,
                'min' => $totalExpenseRatio,
                'count' => 1
            ]
        ];
    }

    /**
     * Generate expense insights
     */
    private function generateExpenseInsights($summary, $expenseRatios)
    {
        $insights = [];
        
        $totalExpenseRatio = $expenseRatios['total_expense_ratio']['total'] ?? 0;
        
        if ($totalExpenseRatio > 50) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'High Expense Ratio',
                'message' => sprintf('Total expenses are %.1f%% of revenue, consider cost optimization', $totalExpenseRatio)
            ];
        }
        
        return $insights;
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
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $diff = $start->diff($end);
        
        $prevEnd = clone $start;
        $prevEnd->sub(new DateInterval('P1D'));
        
        $prevStart = clone $prevEnd;
        $prevStart->sub($diff);
        
        return [
            'start' => $prevStart->format('Y-m-d'),
            'end' => $prevEnd->format('Y-m-d')
        ];
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
}
