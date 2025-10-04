<?php
/**
 * Enhanced Dashboard Controller - Complete Implementation
 * Comprehensive financial analytics and KPI dashboard
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/DailyTxn.php';
require_once __DIR__ . '/../Models/MonthLock.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/response.php';
require_once __DIR__ . '/../Helpers/money.php';

class DashboardController
{
    private $dailyTxnModel;
    private $monthLockModel;

    public function __construct()
    {
        $this->dailyTxnModel = new DailyTxn();
        $this->monthLockModel = new MonthLock();
    }

    /**
     * Enhanced Dashboard with Professional Analytics
     */
    public function index()
    {
        Auth::requirePermission('view_dashboard');

        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Core Financial KPIs
        $kpis = $this->calculateAdvancedKPIs($currentYear, $currentMonth);
        
        // Recent transactions with enhanced data
        $recentTransactions = $this->getEnhancedRecentTransactions();
        
        // Multiple chart datasets for comprehensive analysis
        $chartData = [
            'monthly_trends' => $this->getMonthlyTrendsChart($currentYear),
            'daily_performance' => $this->getDailyPerformanceChart($currentYear, $currentMonth),
            'rate_analysis' => $this->getRateAnalysisChart($currentYear, $currentMonth),
            'comparative_analysis' => $this->getComparativeAnalysisChart($currentYear, $currentMonth)
        ];
        
        // Financial insights and alerts
        $insights = $this->generateFinancialInsights($currentYear, $currentMonth);
        
        $performanceMetrics = $this->calculatePerformanceMetrics($currentYear, $currentMonth);
        
        // Trend analysis
        $trendAnalysis = $this->analyzeTrends($currentYear, $currentMonth);

        // Get available year range from database
        $yearRange = $this->getAvailableYearRange();
        
        $data = [
            'title' => 'Financial Analytics Dashboard - Daily Statement App',
            'current_year' => $currentYear,
            'current_month' => $currentMonth,
            'month_name' => date('F Y'),
            'year_range' => $yearRange,
            'kpis' => $kpis,
            'performance_metrics' => $performanceMetrics,
            'chart_data' => $chartData,
            'insights' => $insights,
            'trend_analysis' => $trendAnalysis,
            'user' => Auth::user()
        ];

        include __DIR__ . '/../Views/dashboard/index.php';
    }

    /**
     * Get KPI data (API endpoint)
     */
    public function getKpis()
    {
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('n');
        
        $kpis = $this->calculateAdvancedKPIs($year, $month);
        
        // Also get performance metrics for the same period
        $performanceMetrics = $this->calculatePerformanceMetrics($year, $month);
        
        // Get trend analysis for the selected period
        $trendAnalysis = $this->analyzeTrends($year, $month);
        
        // Get insights for the selected period
        $insights = $this->generateFinancialInsights($year, $month);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'kpis' => $kpis,
                'performance_metrics' => $performanceMetrics,
                'trend_analysis' => $trendAnalysis,
                'insights' => $insights
            ]
        ]);
        exit;
    }

    /**
     * Get Chart data (API endpoint)
     */
    public function getChartData()
    {
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('n');
        $type = $_GET['type'] ?? 'monthly_trends';
        
        try {
            $chartData = null;
            
            switch ($type) {
                case 'monthly_trends':
                    $chartData = $this->getMonthlyTrendsChart($year);
                    break;
                case 'daily_performance':
                    $chartData = $this->getDailyPerformanceChart($year, $month);
                    break;
                case 'rate_analysis':
                    $chartData = $this->getRateAnalysisChart($year, $month);
                    break;
                case 'comparative_analysis':
                    $chartData = $this->getComparativeAnalysisChart($year, $month);
                    break;
                default:
                    $chartData = $this->getMonthlyTrendsChart($year);
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $chartData
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Calculate Advanced KPIs
     */
    private function calculateAdvancedKPIs($year, $month)
    {
        $db = Database::getInstance();
        
        // Current month data
        $currentData = $db->fetch(
            "SELECT 
                SUM(ca) as mtd_ca,
                SUM(ga) as mtd_ga,
                SUM(je) as mtd_je,
                SUM(fi) as mtd_fi,
                AVG(rate_ag1) as avg_ag1,
                AVG(rate_ag2) as avg_ag2,
                COUNT(*) as transaction_count
             FROM v_daily_txn 
             WHERE YEAR(txn_date) = ? AND MONTH(txn_date) = ?",
            [$year, $month]
        );
        
        // Year to date data
        $ytdData = $db->fetch(
            "SELECT 
                SUM(ca) as ytd_ca,
                SUM(fi) as ytd_fi,
                COUNT(*) as ytd_transactions
             FROM v_daily_txn 
             WHERE YEAR(txn_date) = ?",
            [$year]
        );
        
        // Previous month for comparison
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        
        $prevData = $db->fetch(
            "SELECT 
                SUM(ca) as prev_ca,
                SUM(fi) as prev_fi
             FROM v_daily_txn 
             WHERE YEAR(txn_date) = ? AND MONTH(txn_date) = ?",
            [$prevYear, $prevMonth]
        );
        
        return [
            'mtd_ca_formatted' => Money::format($currentData['mtd_ca'] ?? 0),
            'mtd_ca_raw' => $currentData['mtd_ca'] ?? 0,
            'mtd_fi_formatted' => Money::format($currentData['mtd_fi'] ?? 0),
            'mtd_fi_raw' => $currentData['mtd_fi'] ?? 0,
            'ytd_ca_formatted' => Money::format($ytdData['ytd_ca'] ?? 0),
            'ytd_fi_formatted' => Money::format($ytdData['ytd_fi'] ?? 0),
            'transaction_count' => $currentData['transaction_count'] ?? 0,
            'ytd_transaction_count' => $ytdData['ytd_transactions'] ?? 0,
            'avg_ag1_rate' => round(($currentData['avg_ag1'] ?? 0) * 100, 2),
            'avg_ag2_rate' => round(($currentData['avg_ag2'] ?? 0) * 100, 2),
            'efficiency_ratio' => $this->calculateEfficiencyRatio($currentData['mtd_ca'] ?? 0, $currentData['mtd_ga'] ?? 0),
            'profitability_ratio' => $this->calculateProfitabilityRatio($currentData),
            'ca_change' => $this->calculatePercentageChange($prevData['prev_ca'] ?? 0, $currentData['mtd_ca'] ?? 0),
            'fi_change' => $this->calculatePercentageChange($prevData['prev_fi'] ?? 0, $currentData['mtd_fi'] ?? 0)
        ];
    }

    /**
     * Calculate efficiency ratio
     */
    private function calculateEfficiencyRatio($ca, $ga)
    {
        return $ca > 0 ? round((($ca - $ga) / $ca) * 100, 2) : 0;
    }

    /**
     * Get available year range from database
     * Calculate Profitability Ratio
     */
    private function calculateProfitabilityRatio($data)
    {
        $totalRevenue = $data['mtd_ca'] ?? 0;
        $totalProfit = $data['mtd_fi'] ?? 0;
        
        return $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
    }

    /**
     * Get Enhanced Recent Transactions
     */
    private function getEnhancedRecentTransactions()
    {
        $transactions = $this->dailyTxnModel->getRecent(10);
        
        // Add calculated fields and formatting
        foreach ($transactions as &$txn) {
            $txn['ca_formatted'] = Money::format($txn['ca']);
            $txn['fi_formatted'] = Money::format($txn['fi']);
            $txn['ag1_rate_percent'] = round($txn['rate_ag1'] * 100, 2);
            $txn['ag2_rate_percent'] = round($txn['rate_ag2'] * 100, 2);
            $txn['efficiency'] = $txn['ca'] > 0 ? round(($txn['fi'] / $txn['ca']) * 100, 1) : 0;
        }
        
        return $transactions;
    }

    /**
     * Calculate Performance Metrics
     */
    private function calculatePerformanceMetrics($year, $month)
    {
        $db = Database::getInstance();
        
        // Get detailed metrics with proper handling of empty results
        $metrics = $db->fetch(
            "SELECT 
                COALESCE(AVG(ca), 0) as avg_ca,
                COALESCE(AVG(fi), 0) as avg_fi,
                COALESCE(MAX(fi), 0) as max_fi,
                COALESCE(MIN(fi), 0) as min_fi,
                COALESCE(STDDEV(fi), 0) as fi_volatility,
                COALESCE(AVG(rate_ag1), 0) as avg_ag1_rate,
                COALESCE(AVG(rate_ag2), 0) as avg_ag2_rate,
                COUNT(*) as total_transactions
             FROM v_daily_txn 
             WHERE YEAR(txn_date) = ? AND MONTH(txn_date) = ?",
            [$year, $month]
        );

        // Ensure we always have a valid result
        if (!$metrics) {
            $metrics = [
                'avg_ca' => 0,
                'avg_fi' => 0,
                'max_fi' => 0,
                'min_fi' => 0,
                'fi_volatility' => 0,
                'avg_ag1_rate' => 0,
                'avg_ag2_rate' => 0,
                'total_transactions' => 0
            ];
        }

        // Debug logging for performance metrics
        error_log("Performance metrics for {$year}-{$month}: " . json_encode($metrics));

        return [
            'avg_transaction_size' => Money::format($metrics['avg_ca'] ?? 0),
            'avg_daily_profit' => Money::format($metrics['avg_fi'] ?? 0),
            'best_day' => Money::format($metrics['max_fi'] ?? 0),
            'worst_day' => Money::format($metrics['min_fi'] ?? 0),
            'volatility' => round($metrics['fi_volatility'] ?? 0, 2),
            'avg_ag1_rate' => round(($metrics['avg_ag1_rate'] ?? 0) * 100, 2),
            'avg_ag2_rate' => round(($metrics['avg_ag2_rate'] ?? 0) * 100, 2),
            'total_transactions' => $metrics['total_transactions'] ?? 0,
            'consistency_score' => $this->calculateConsistencyScore($metrics)
        ];
    }

    /**
     * Calculate Consistency Score
     */
    private function calculateConsistencyScore($metrics)
    {
        $avgFi = $metrics['avg_fi'] ?? 0;
        $volatility = $metrics['fi_volatility'] ?? 0;
        
        if ($avgFi == 0) return 0;
        
        $coefficientOfVariation = ($volatility / abs($avgFi)) * 100;
        $consistencyScore = max(0, 100 - $coefficientOfVariation);
        
        return round($consistencyScore, 1);
    }

    /**
     * Analyze Trends
     */
    private function analyzeTrends($year, $month)
    {
        $db = Database::getInstance();
        
        // Get 6 months of data leading up to and including the selected month for trend analysis
        $selectedDate = sprintf('%04d-%02d-01', $year, $month);
        $trendData = $db->fetchAll(
            "SELECT 
                YEAR(txn_date) as year,
                MONTH(txn_date) as month,
                SUM(ca) as total_ca,
                SUM(fi) as total_fi,
                AVG(rate_ag1) as avg_ag1,
                AVG(rate_ag2) as avg_ag2
             FROM v_daily_txn 
             WHERE txn_date >= DATE_SUB(?, INTERVAL 5 MONTH) 
             AND txn_date <= LAST_DAY(?)
             GROUP BY YEAR(txn_date), MONTH(txn_date)
             ORDER BY YEAR(txn_date), MONTH(txn_date)",
            [$selectedDate, $selectedDate]
        );

        $trends = [
            'ca_trend' => $this->calculateTrend($trendData, 'total_ca'),
            'fi_trend' => $this->calculateTrend($trendData, 'total_fi'),
            'rate_stability' => $this->calculateRateStability($trendData),
            'growth_rate' => $this->calculateGrowthRate($trendData),
            'forecast' => $this->generateForecast($trendData)
        ];

        return $trends;
    }

    /**
     * Calculate Trend Direction
     */
    private function calculateTrend($data, $field)
    {
        if (count($data) < 2) return 'insufficient_data';
        
        $values = array_column($data, $field);
        $n = count($values);
        
        // Simple linear regression slope
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $values[$i];
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        if ($slope > 100) return 'strong_upward';
        if ($slope > 10) return 'upward';
        if ($slope > -10) return 'stable';
        if ($slope > -100) return 'downward';
        return 'strong_downward';
    }

    /**
     * Calculate Rate Stability
     */
    private function calculateRateStability($data)
    {
        if (empty($data)) return 'no_data';
        
        $ag1Rates = array_column($data, 'avg_ag1');
        $ag2Rates = array_column($data, 'avg_ag2');
        
        $ag1Variance = $this->calculateVariance($ag1Rates);
        $ag2Variance = $this->calculateVariance($ag2Rates);
        
        $avgVariance = ($ag1Variance + $ag2Variance) / 2;
        
        if ($avgVariance < 0.0001) return 'very_stable';
        if ($avgVariance < 0.001) return 'stable';
        if ($avgVariance < 0.01) return 'moderate';
        return 'volatile';
    }

    /**
     * Calculate Variance
     */
    private function calculateVariance($values)
    {
        if (empty($values)) return 0;
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values);
        
        return array_sum($squaredDiffs) / count($values);
    }

    /**
     * Calculate Growth Rate
     */
    private function calculateGrowthRate($data)
    {
        if (count($data) < 2) return 0;
        
        $firstMonth = reset($data);
        $lastMonth = end($data);
        
        $initialFi = $firstMonth['total_fi'];
        $finalFi = $lastMonth['total_fi'];
        
        if ($initialFi == 0) return 0;
        
        return round((($finalFi - $initialFi) / $initialFi) * 100, 2);
    }

    /**
     * Generate Simple Forecast
     */
    private function generateForecast($data)
    {
        if (count($data) < 3) return ['status' => 'insufficient_data'];
        
        $fiValues = array_column($data, 'total_fi');
        $trend = $this->calculateTrend($data, 'total_fi');
        $lastValue = end($fiValues);
        $avgGrowth = $this->calculateGrowthRate($data) / count($data);
        
        $nextMonthForecast = $lastValue * (1 + ($avgGrowth / 100));
        
        return [
            'status' => 'available',
            'next_month_fi' => Money::format($nextMonthForecast),
            'confidence' => $this->calculateForecastConfidence($trend),
            'trend_direction' => $trend
        ];
    }

    /**
     * Calculate Forecast Confidence
     */
    private function calculateForecastConfidence($trend)
    {
        switch ($trend) {
            case 'very_stable':
            case 'stable':
                return 'high';
            case 'upward':
            case 'downward':
                return 'medium';
            default:
                return 'low';
        }
    }

    /**
     * Calculate Percentage Change
     */
    private function calculatePercentageChange($oldValue, $newValue)
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }

    /**
     * Get Monthly Trends Chart Data
     */
    private function getMonthlyTrendsChart($year)
    {
        $db = Database::getInstance();
        
        $data = $db->fetchAll(
            "SELECT 
                MONTH(txn_date) as month,
                SUM(ca) as total_ca,
                SUM(fi) as total_fi,
                COUNT(*) as transaction_count
             FROM v_daily_txn 
             WHERE YEAR(txn_date) = ?
             GROUP BY MONTH(txn_date)
             ORDER BY MONTH(txn_date)",
            [$year]
        );
        
        $months = [];
        $caData = [];
        $fiData = [];
        
        foreach ($data as $row) {
            $months[] = date('M', mktime(0, 0, 0, $row['month'], 1));
            $caData[] = round($row['total_ca'], 2);
            $fiData[] = round($row['total_fi'], 2);
        }
        
        return [
            'type' => 'line',
            'data' => [
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => 'CA Amount',
                        'data' => $caData,
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'FI Amount',
                        'data' => $fiData,
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'borderColor' => 'rgba(75, 192, 192, 1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4
                    ]
                ]
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
     * Get Daily Performance Chart Data
     */
    private function getDailyPerformanceChart($year, $month)
    {
        $db = Database::getInstance();
        
        $data = $db->fetchAll(
            "SELECT 
                DAY(txn_date) as day,
                SUM(ca) as daily_ca,
                SUM(fi) as daily_fi
             FROM v_daily_txn 
             WHERE YEAR(txn_date) = ? AND MONTH(txn_date) = ?
             GROUP BY DAY(txn_date)
             ORDER BY DAY(txn_date)",
            [$year, $month]
        );
        
        $days = [];
        $caData = [];
        $fiData = [];
        
        foreach ($data as $row) {
            $days[] = $row['day'];
            $caData[] = round($row['daily_ca'], 2);
            $fiData[] = round($row['daily_fi'], 2);
        }
        
        return [
            'type' => 'line',
            'data' => [
                'labels' => $days,
                'datasets' => [
                    [
                        'label' => 'Daily CA',
                        'data' => $caData,
                        'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                        'borderColor' => 'rgba(255, 99, 132, 1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'Daily FI',
                        'data' => $fiData,
                        'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
                        'borderColor' => 'rgba(153, 102, 255, 1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4
                    ]
                ]
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
     * Get Rate Analysis Chart Data
     */
    private function getRateAnalysisChart($year, $month)
    {
        $db = Database::getInstance();
        
        $data = $db->fetchAll(
            "SELECT 
                txn_date,
                rate_ag1,
                rate_ag2
             FROM v_daily_txn 
             WHERE YEAR(txn_date) = ? AND MONTH(txn_date) = ?
             ORDER BY txn_date",
            [$year, $month]
        );
        
        $dates = [];
        $ag1Rates = [];
        $ag2Rates = [];
        
        foreach ($data as $row) {
            $dates[] = date('M j', strtotime($row['txn_date']));
            $ag1Rates[] = round($row['rate_ag1'] * 100, 2);
            $ag2Rates[] = round($row['rate_ag2'] * 100, 2);
        }
        
        return [
            'type' => 'line',
            'data' => [
                'labels' => $dates,
                'datasets' => [
                    [
                        'label' => 'AG1 Rate (%)',
                        'data' => $ag1Rates,
                        'backgroundColor' => 'rgba(255, 206, 86, 0.2)',
                        'borderColor' => 'rgba(255, 206, 86, 1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4
                    ],
                    [
                        'label' => 'AG2 Rate (%)',
                        'data' => $ag2Rates,
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'borderColor' => 'rgba(75, 192, 192, 1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4
                    ]
                ]
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
     * Get Comparative Analysis Chart Data
     */
    private function getComparativeAnalysisChart($year, $month)
    {
        $db = Database::getInstance();
        
        // Get current month and previous month data
        $prevMonth = $month == 1 ? 12 : $month - 1;
        $prevYear = $month == 1 ? $year - 1 : $year;
        
        $currentData = $db->fetch(
            "SELECT 
                SUM(ca) as total_ca,
                SUM(ga) as total_ga,
                SUM(je) as total_je,
                SUM(fi) as total_fi
             FROM v_daily_txn 
             WHERE YEAR(txn_date) = ? AND MONTH(txn_date) = ?",
            [$year, $month]
        );
        
        $prevData = $db->fetch(
            "SELECT 
                SUM(ca) as total_ca,
                SUM(ga) as total_ga,
                SUM(je) as total_je,
                SUM(fi) as total_fi
             FROM v_daily_txn 
             WHERE YEAR(txn_date) = ? AND MONTH(txn_date) = ?",
            [$prevYear, $prevMonth]
        );
        
        return [
            'type' => 'bar',
            'data' => [
                'labels' => ['CA', 'GA', 'JE', 'FI'],
                'datasets' => [
                    [
                        'label' => 'Current Month',
                        'data' => [
                            round($currentData['total_ca'] ?? 0, 2),
                            round($currentData['total_ga'] ?? 0, 2),
                            round($currentData['total_je'] ?? 0, 2),
                            round($currentData['total_fi'] ?? 0, 2)
                        ],
                        'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    ],
                    [
                        'label' => 'Previous Month',
                        'data' => [
                            round($prevData['total_ca'] ?? 0, 2),
                            round($prevData['total_ga'] ?? 0, 2),
                            round($prevData['total_je'] ?? 0, 2),
                            round($prevData['total_fi'] ?? 0, 2)
                        ],
                        'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                        'borderColor' => 'rgba(255, 99, 132, 1)',
                        'borderWidth' => 1
                    ]
                ]
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
     * Generate Financial Insights
     */
    private function generateFinancialInsights($year, $month)
    {
        $insights = [];
        
        // Check for locked months
        $recentLocks = $this->monthLockModel->getRecent(3);
        foreach ($recentLocks as $lock) {
            if (strtotime($lock['locked_at']) > strtotime('-7 days')) {
                $monthName = date('F Y', mktime(0, 0, 0, $lock['month_num'], 1, $lock['year_num']));
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'Month Locked',
                    'message' => "{$monthName} has been locked by {$lock['locked_by_name']}",
                    'date' => $lock['locked_at']
                ];
            }
        }
        
        return $insights;
    }

    /**
     * Get available year range from database
     */
    private function getAvailableYearRange()
    {
        $db = Database::getInstance();
        
        $result = $db->fetch(
            "SELECT 
                MIN(YEAR(txn_date)) as min_year,
                MAX(YEAR(txn_date)) as max_year
             FROM v_daily_txn"
        );
        
        $minYear = $result['min_year'] ?? date('Y');
        $maxYear = $result['max_year'] ?? date('Y');
        
        // Ensure we have at least current year
        $minYear = min($minYear, date('Y'));
        $maxYear = max($maxYear, date('Y'));
        
        return [
            'min' => (int)$minYear,
            'max' => (int)$maxYear
        ];
    }
}
?>
