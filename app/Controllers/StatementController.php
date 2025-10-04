<?php
/**
 * Professional Statement Controller
 * Advanced statement generation and financial reporting system
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/DailyTxn.php';
require_once __DIR__ . '/../Models/MonthLock.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/response.php';
require_once __DIR__ . '/../Helpers/money.php';

class StatementController
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
     * Professional Statement Dashboard
     */
    public function index()
    {
        Auth::requirePermission('view_statement');

        // Get available date range
        $dateRange = $this->getAvailableDateRange();
        
        // Get statement templates
        $templates = $this->getStatementTemplates();

        $data = [
            'title' => 'Professional Statement Generator - Daily Statement App',
            'date_range' => $dateRange,
            'templates' => $templates,
            'default_start_date' => $dateRange['min_date'] ?? date('Y-m-01'),
            'default_end_date' => $dateRange['max_date'] ?? date('Y-m-d'),
            'can_export' => Auth::can('export_csv')
        ];

        include __DIR__ . '/../Views/statement/index.php';
    }

    /**
     * Generate statement (API)
     */
    public function generate()
    {
        Auth::requirePermission('view_statement');

        try {
            // Get and validate parameters
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $template = $_GET['template'] ?? 'comprehensive';
            $groupBy = $_GET['group_by'] ?? 'day';
            $export = $_GET['export'] ?? false;

            // Debug logging
            error_log("Statement generation request: start_date=$startDate, end_date=$endDate, template=$template");

            // Validate date range
            if (!$this->validateDateRange($startDate, $endDate)) {
                throw new Exception('Invalid date range provided');
            }

            // Test database connection
            $testQuery = $this->db->fetch("SELECT COUNT(*) as count FROM v_daily_txn");
            error_log("Database test query result: " . json_encode($testQuery));

            // Generate statement data
            $statementData = $this->generateStatementData($startDate, $endDate, $template, $groupBy);

            // Handle export
            if ($export) {
                $this->exportStatement($statementData, $export, $template);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $statementData
            ]);

        } catch (Exception $e) {
            error_log("Statement generation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
        exit;
    }

    /**
     * Test endpoint for debugging
     */
    public function test()
    {
        try {
            // Test database connection
            $testQuery = $this->db->fetch("SELECT COUNT(*) as count FROM v_daily_txn");
            
            // Test date range
            $dateRange = $this->getAvailableDateRange();
            
            // Test templates
            $templates = $this->getStatementTemplates();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => [
                    'database_test' => $testQuery,
                    'date_range' => $dateRange,
                    'templates_count' => count($templates),
                    'templates' => array_keys($templates)
                ]
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        exit;
    }

    /**
     * Get available statement templates
     */
    private function getStatementTemplates()
    {
        return [
            'comprehensive' => [
                'name' => 'Comprehensive Statement',
                'description' => 'Complete financial statement with all metrics and analysis',
                'includes' => ['transactions', 'totals', 'analysis', 'charts', 'insights']
            ],
            'summary' => [
                'name' => 'Executive Summary',
                'description' => 'High-level overview with key metrics and totals',
                'includes' => ['totals', 'analysis', 'insights']
            ],
            'detailed' => [
                'name' => 'Detailed Transaction Report',
                'description' => 'Complete transaction listing with notes and calculations',
                'includes' => ['transactions', 'totals', 'notes']
            ],
            'financial' => [
                'name' => 'Financial Analysis',
                'description' => 'Focus on financial performance and ratios',
                'includes' => ['totals', 'analysis', 'ratios', 'trends']
            ],
            'audit' => [
                'name' => 'Audit Trail',
                'description' => 'Detailed audit trail with all transaction details',
                'includes' => ['transactions', 'totals', 'audit_info', 'signatures']
            ]
        ];
    }

    /**
     * Generate comprehensive statement data
     */
    private function generateStatementData($startDate, $endDate, $template, $groupBy)
    {
        // Get transactions for the period
        $transactions = $this->getTransactionsForPeriod($startDate, $endDate);
        
        // Calculate totals and statistics
        $totals = $this->calculatePeriodTotals($transactions);
        $statistics = $this->calculateStatistics($transactions);
        
        // Generate analysis based on template
        $analysis = $this->generateAnalysis($transactions, $totals, $template);
        
        // Get comparative data
        $comparative = $this->getComparativeData($startDate, $endDate);
        
        // Generate insights
        $insights = $this->generateInsights($totals, $statistics, $comparative);

        return [
            'template' => $template,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'label' => $this->formatPeriodLabel($startDate, $endDate),
                'days' => $this->calculateDaysBetween($startDate, $endDate)
            ],
            'transactions' => $this->formatTransactionsForStatement($transactions, $template),
            'totals' => $totals,
            'statistics' => $statistics,
            'analysis' => $analysis,
            'comparative' => $comparative,
            'insights' => $insights,
            'metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => Auth::user()['name'] ?? 'System',
                'total_records' => count($transactions),
                'template_used' => $template
            ]
        ];
    }

    /**
     * Get transactions for specified period
     */
    private function getTransactionsForPeriod($startDate, $endDate)
    {
        try {
            $sql = "SELECT * FROM v_daily_txn 
                    WHERE txn_date BETWEEN ? AND ? 
                    ORDER BY txn_date ASC";
            
            error_log("Executing query: $sql with params: $startDate, $endDate");
            $result = $this->db->fetchAll($sql, [$startDate, $endDate]);
            error_log("Query returned " . count($result) . " rows");
            
            return $result;
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }

    /**
     * Calculate comprehensive totals
     */
    private function calculatePeriodTotals($transactions)
    {
        $totals = [
            'ca' => 0, 'ga' => 0, 'je' => 0, 'fi' => 0,
            'ag1' => 0, 'ag2' => 0, 're' => 0, 'av1' => 0, 'av2' => 0
        ];

        foreach ($transactions as $txn) {
            foreach ($totals as $key => &$value) {
                $value += floatval($txn[$key] ?? 0);
            }
        }

        // Calculate derived metrics
        $totals['gross_profit'] = $totals['ca'] - $totals['ga'];
        $totals['net_profit'] = $totals['fi'];
        $totals['total_expenses'] = $totals['ga'] + $totals['je'];
        $totals['profit_margin'] = $totals['ca'] > 0 ? ($totals['fi'] / $totals['ca']) * 100 : 0;
        $totals['expense_ratio'] = $totals['ca'] > 0 ? ($totals['total_expenses'] / $totals['ca']) * 100 : 0;

        return $totals;
    }

    /**
     * Calculate detailed statistics
     */
    private function calculateStatistics($transactions)
    {
        if (empty($transactions)) {
            return [];
        }

        $fiValues = array_column($transactions, 'fi');
        $caValues = array_column($transactions, 'ca');
        
        return [
            'transaction_count' => count($transactions),
            'avg_daily_fi' => array_sum($fiValues) / count($fiValues),
            'max_daily_fi' => max($fiValues),
            'min_daily_fi' => min($fiValues),
            'avg_daily_ca' => array_sum($caValues) / count($caValues),
            'max_daily_ca' => max($caValues),
            'min_daily_ca' => min($caValues),
            'volatility' => $this->calculateVolatility($fiValues),
            'consistency_score' => $this->calculateConsistencyScore($fiValues)
        ];
    }

    /**
     * Generate analysis based on template
     */
    private function generateAnalysis($transactions, $totals, $template)
    {
        $analysis = [];

        switch ($template) {
            case 'comprehensive':
                $analysis = [
                    'performance' => $this->analyzePerformance($totals),
                    'trends' => $this->analyzeTrends($transactions),
                    'efficiency' => $this->analyzeEfficiency($totals),
                    'risk' => $this->analyzeRisk($transactions)
                ];
                break;
                
            case 'financial':
                $analysis = [
                    'profitability' => $this->analyzeProfitability($totals),
                    'liquidity' => $this->analyzeLiquidity($transactions),
                    'ratios' => $this->calculateFinancialRatios($totals)
                ];
                break;
                
            default:
                $analysis = [
                    'summary' => $this->generateSummaryAnalysis($totals)
                ];
        }

        return $analysis;
    }

    /**
     * Get comparative data for previous period
     */
    private function getComparativeData($startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $diff = $start->diff($end);
        
        // Calculate previous period
        $prevEnd = clone $start;
        $prevEnd->sub(new DateInterval('P1D'));
        $prevStart = clone $prevEnd;
        $prevStart->sub($diff);
        
        $prevTransactions = $this->getTransactionsForPeriod(
            $prevStart->format('Y-m-d'), 
            $prevEnd->format('Y-m-d')
        );
        
        $currentTotals = $this->calculatePeriodTotals($this->getTransactionsForPeriod($startDate, $endDate));
        $prevTotals = $this->calculatePeriodTotals($prevTransactions);
        
        return [
            'previous_period' => [
                'start_date' => $prevStart->format('Y-m-d'),
                'end_date' => $prevEnd->format('Y-m-d'),
                'totals' => $prevTotals
            ],
            'comparison' => $this->calculatePeriodComparison($currentTotals, $prevTotals)
        ];
    }

    /**
     * Generate actionable insights
     */
    private function generateInsights($totals, $statistics, $comparative)
    {
        $insights = [];

        // Profitability insights
        if ($totals['profit_margin'] > 70) {
            $insights[] = [
                'type' => 'success',
                'category' => 'Profitability',
                'title' => 'Excellent Profit Margin',
                'message' => sprintf('Profit margin of %.1f%% indicates strong financial performance', $totals['profit_margin'])
            ];
        } elseif ($totals['profit_margin'] < 50) {
            $insights[] = [
                'type' => 'warning',
                'category' => 'Profitability',
                'title' => 'Low Profit Margin',
                'message' => sprintf('Profit margin of %.1f%% may need attention', $totals['profit_margin'])
            ];
        }

        // Consistency insights
        if (isset($statistics['consistency_score']) && $statistics['consistency_score'] > 80) {
            $insights[] = [
                'type' => 'info',
                'category' => 'Performance',
                'title' => 'Consistent Performance',
                'message' => sprintf('Consistency score of %.1f%% shows stable operations', $statistics['consistency_score'])
            ];
        }

        // Comparative insights
        if (isset($comparative['comparison']['fi']['change'])) {
            $change = $comparative['comparison']['fi']['change'];
            if (abs($change) > 10) {
                $direction = $change > 0 ? 'increased' : 'decreased';
                $insights[] = [
                    'type' => $change > 0 ? 'success' : 'warning',
                    'category' => 'Trends',
                    'title' => 'Significant Change',
                    'message' => sprintf('Final income has %s by %.1f%% compared to previous period', $direction, abs($change))
                ];
            }
        }

        return $insights;
    }

    // Helper methods
    private function validateDateRange($startDate, $endDate)
    {
        $start = DateTime::createFromFormat('Y-m-d', $startDate);
        $end = DateTime::createFromFormat('Y-m-d', $endDate);
        return $start && $end && $start <= $end;
    }

    private function getAvailableDateRange()
    {
        $result = $this->db->fetch(
            "SELECT MIN(txn_date) as min_date, MAX(txn_date) as max_date FROM v_daily_txn"
        );
        return $result ?: ['min_date' => date('Y-m-01'), 'max_date' => date('Y-m-d')];
    }

    private function formatPeriodLabel($startDate, $endDate)
    {
        if ($startDate === $endDate) {
            return date('F j, Y', strtotime($startDate));
        }
        return date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate));
    }

    private function calculateDaysBetween($startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        return $start->diff($end)->days + 1;
    }

    private function formatTransactionsForStatement($transactions, $template)
    {
        // Format transactions based on template requirements
        return array_map(function($txn) use ($template) {
            $formatted = $txn;
            $formatted['formatted_date'] = date('M j, Y', strtotime($txn['txn_date']));
            $formatted['day_of_week'] = date('l', strtotime($txn['txn_date']));
            return $formatted;
        }, $transactions);
    }

    private function calculateVolatility($values)
    {
        if (count($values) < 2) return 0;
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);
        return sqrt($variance);
    }

    private function calculateConsistencyScore($values)
    {
        if (empty($values)) return 0;
        $volatility = $this->calculateVolatility($values);
        $mean = array_sum($values) / count($values);
        return $mean > 0 ? max(0, 100 - (($volatility / $mean) * 100)) : 0;
    }

    private function analyzePerformance($totals)
    {
        return [
            'rating' => $totals['profit_margin'] > 70 ? 'Excellent' : 
                       ($totals['profit_margin'] > 50 ? 'Good' : 'Needs Improvement'),
            'score' => min(100, $totals['profit_margin'] * 1.4)
        ];
    }

    private function analyzeTrends($transactions)
    {
        // Simple trend analysis
        $firstHalf = array_slice($transactions, 0, ceil(count($transactions) / 2));
        $secondHalf = array_slice($transactions, ceil(count($transactions) / 2));
        
        $firstAvg = array_sum(array_column($firstHalf, 'fi')) / count($firstHalf);
        $secondAvg = array_sum(array_column($secondHalf, 'fi')) / count($secondHalf);
        
        $trend = $secondAvg > $firstAvg ? 'Improving' : 'Declining';
        $change = $firstAvg > 0 ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;
        
        return [
            'direction' => $trend,
            'change_percent' => $change
        ];
    }

    private function analyzeEfficiency($totals)
    {
        return [
            'expense_ratio' => $totals['expense_ratio'],
            'efficiency_score' => max(0, 100 - $totals['expense_ratio'])
        ];
    }

    private function analyzeRisk($transactions)
    {
        $fiValues = array_column($transactions, 'fi');
        $volatility = $this->calculateVolatility($fiValues);
        $mean = array_sum($fiValues) / count($fiValues);
        
        $riskScore = $mean > 0 ? ($volatility / $mean) * 100 : 100;
        
        return [
            'volatility' => $volatility,
            'risk_score' => $riskScore,
            'risk_level' => $riskScore < 20 ? 'Low' : ($riskScore < 50 ? 'Medium' : 'High')
        ];
    }

    private function analyzeProfitability($totals)
    {
        return [
            'gross_margin' => $totals['ca'] > 0 ? ($totals['gross_profit'] / $totals['ca']) * 100 : 0,
            'net_margin' => $totals['profit_margin'],
            'return_efficiency' => $totals['total_expenses'] > 0 ? ($totals['fi'] / $totals['total_expenses']) * 100 : 0
        ];
    }

    private function analyzeLiquidity($transactions)
    {
        // Simple liquidity analysis based on cash flow patterns
        $dailyFI = array_column($transactions, 'fi');
        $positiveDays = count(array_filter($dailyFI, function($fi) { return $fi > 0; }));
        
        return [
            'positive_days_ratio' => count($dailyFI) > 0 ? ($positiveDays / count($dailyFI)) * 100 : 0,
            'cash_flow_stability' => $this->calculateConsistencyScore($dailyFI)
        ];
    }

    private function calculateFinancialRatios($totals)
    {
        return [
            'profit_margin' => $totals['profit_margin'],
            'expense_ratio' => $totals['expense_ratio'],
            'efficiency_ratio' => $totals['ca'] > 0 ? ($totals['re'] / $totals['ca']) * 100 : 0,
            'cost_coverage' => $totals['total_expenses'] > 0 ? ($totals['ca'] / $totals['total_expenses']) : 0
        ];
    }

    private function generateSummaryAnalysis($totals)
    {
        return [
            'performance_summary' => sprintf(
                'Generated %s in final income with a profit margin of %.1f%%',
                '$' . number_format($totals['fi'], 2),
                $totals['profit_margin']
            )
        ];
    }

    private function calculatePeriodComparison($current, $previous)
    {
        $comparison = [];
        $metrics = ['ca', 'fi', 'ga', 'je', 'profit_margin'];
        
        foreach ($metrics as $metric) {
            $currentVal = $current[$metric] ?? 0;
            $prevVal = $previous[$metric] ?? 0;
            
            $change = $prevVal > 0 ? (($currentVal - $prevVal) / $prevVal) * 100 : 0;
            
            $comparison[$metric] = [
                'current' => $currentVal,
                'previous' => $prevVal,
                'change' => $change,
                'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
            ];
        }
        
        return $comparison;
    }

    private function exportStatement($statementData, $format, $template)
    {
        if ($format === 'pdf') {
            $this->exportPDF($statementData, $template);
        } elseif ($format === 'csv') {
            $this->exportCSV($statementData, $template);
        }
    }

    private function exportCSV($statementData, $template)
    {
        $filename = 'statement_' . $template . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write statement header
        fputcsv($output, ['Statement Type', $statementData['template']]);
        fputcsv($output, ['Period', $statementData['period']['label']]);
        fputcsv($output, ['Generated', $statementData['metadata']['generated_at']]);
        fputcsv($output, []);
        
        // Write transactions if included
        if (!empty($statementData['transactions'])) {
            fputcsv($output, ['Date', 'CA', 'GA', 'JE', 'FI', 'Note']);
            foreach ($statementData['transactions'] as $txn) {
                fputcsv($output, [
                    $txn['txn_date'],
                    $txn['ca'],
                    $txn['ga'],
                    $txn['je'],
                    $txn['fi'],
                    $txn['note'] ?? ''
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    private function exportPDF($statementData, $template)
    {
        // PDF export would require a PDF library like TCPDF or FPDF
        // For now, return CSV as fallback
        $this->exportCSV($statementData, $template);
    }
}
