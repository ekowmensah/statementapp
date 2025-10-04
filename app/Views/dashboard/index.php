<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}
?>




<style>
.kpi-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 15px;
    transition: transform 0.3s ease;
}
.kpi-card:hover {
    transform: translateY(-5px);
}
.kpi-value {
    font-size: 2.5rem;
    font-weight: bold;
    margin: 0;
}
.kpi-change {
    font-size: 0.9rem;
    opacity: 0.9;
}
.chart-container {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
}
.insight-card {
    border-left: 4px solid #007bff;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
}
.metric-badge {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: bold;
    display: inline-block;
    margin: 5px;
}
.chart-container canvas {
    min-height: 300px !important;
    max-height: 400px !important;
}
#mainChart, #rateChart, #compareChart {
    min-height: 300px !important;
}

/* Mobile Responsiveness for Dashboard */
@media (max-width: 768px) {
    .kpi-value {
        font-size: 1.8rem !important;
    }
    
    .kpi-card .card-body {
        padding: 1rem;
    }
    
    .chart-container {
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .chart-container canvas {
        min-height: 250px !important;
        max-height: 300px !important;
    }
    
    #mainChart, #rateChart, #compareChart {
        min-height: 250px !important;
    }
    
    .metric-badge {
        font-size: 0.75rem;
        padding: 6px 12px;
        margin: 3px;
    }
    
    .insight-card {
        padding: 10px;
        margin-bottom: 8px;
    }
    
    /* Stack dashboard header elements on mobile */
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .form-select {
        width: 100% !important;
    }
}

@media (max-width: 576px) {
    .kpi-value {
        font-size: 1.5rem !important;
    }
    
    .kpi-card .fs-3 {
        display: none !important;
    }
    
    .chart-container {
        padding: 10px;
    }
    
    .chart-container canvas {
        min-height: 200px !important;
        max-height: 250px !important;
    }
    
    #mainChart, #rateChart, #compareChart {
        min-height: 200px !important;
    }
    
    .metric-badge {
        font-size: 0.7rem;
        padding: 4px 8px;
        margin: 2px;
    }
}
</style>

<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
            <div class="mb-3 mb-md-0">
                <h1 class="mb-0 text-primary">📊 Financial Analytics Dashboard</h1>
                <p class="text-muted mb-0 fs-5">Comprehensive analysis for <?= htmlspecialchars($data['month_name']) ?></p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
                <select class="form-select" id="monthSelector">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $data['current_month'] ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select class="form-select" id="yearSelector">
                    <?php 
                    $minYear = $data['year_range']['min'] ?? (date('Y') - 2);
                    $maxYear = $data['year_range']['max'] ?? (date('Y') + 1);
                    for ($y = $minYear; $y <= $maxYear; $y++): 
                    ?>
                        <option value="<?= $y ?>" <?= $y == $data['current_year'] ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                    <i class="bi bi-arrow-clockwise"></i> <span class="d-none d-sm-inline">Refresh</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card">
            <div class="card-body text-center">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-title mb-0">💰 Total CA</h6>
                        <small class="opacity-75">Current Month</small>
                    </div>
                    <i class="bi bi-cash-stack fs-3 opacity-75"></i>
                </div>
                <h2 class="kpi-value"><?= $data['kpis']['mtd_ca_formatted'] ?? '$0.00' ?></h2>
                <div class="kpi-change">
                    <?php 
                    $caChange = $data['kpis']['ca_change'] ?? 0;
                    $changeClass = $caChange >= 0 ? 'text-success' : 'text-danger';
                    $changeIcon = $caChange >= 0 ? 'bi-arrow-up' : 'bi-arrow-down';
                    ?>
                    <i class="bi <?= $changeIcon ?>"></i>
                    <span class="<?= $changeClass ?>"><?= abs($caChange) ?>%</span> vs last month
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body text-center">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-title mb-0">📈 Final Income</h6>
                        <small class="opacity-75">Current Month</small>
                    </div>
                    <i class="bi bi-graph-up fs-3 opacity-75"></i>
                </div>
                <h2 class="kpi-value"><?= $data['kpis']['mtd_fi_formatted'] ?? '$0.00' ?></h2>
                <div class="kpi-change">
                    <?php 
                    $fiChange = $data['kpis']['fi_change'] ?? 0;
                    $changeClass = $fiChange >= 0 ? 'text-success' : 'text-danger';
                    $changeIcon = $fiChange >= 0 ? 'bi-arrow-up' : 'bi-arrow-down';
                    ?>
                    <i class="bi <?= $changeIcon ?>"></i>
                    <span class="<?= $changeClass ?>"><?= abs($fiChange) ?>%</span> vs last month
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-center">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-title mb-0">📊 YTD Total</h6>
                        <small class="opacity-75">Year to Date</small>
                    </div>
                    <i class="bi bi-calendar-check fs-3 opacity-75"></i>
                </div>
                <h2 class="kpi-value"><?= $data['kpis']['ytd_fi_formatted'] ?? '$0.00' ?></h2>
                <div class="kpi-change">
                    <i class="bi bi-info-circle"></i>
                    <span id="ytd-transaction-count"><?= $data['kpis']['ytd_transaction_count'] ?? 0 ?> transactions</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-center">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="card-title mb-0">⚡ Efficiency</h6>
                        <small class="opacity-75">Performance Ratio</small>
                    </div>
                    <i class="bi bi-speedometer2 fs-3 opacity-75"></i>
                </div>
                <h2 class="kpi-value"><?= round($data['kpis']['efficiency_ratio'] ?? 0, 1) ?>%</h2>
                <div class="kpi-change">
                    <i class="bi bi-lightning"></i>
                    <span>Profitability: <?= round($data['kpis']['profitability_ratio'] ?? 0, 1) ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Metrics Row -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Performance Metrics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2">
                        <div class="metric-badge">
                            Avg Transaction<br>
                            <strong id="metric-avg-transaction"><?= $data['performance_metrics']['avg_transaction_size'] ?? '$0' ?></strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="metric-badge" style="background: linear-gradient(45deg, #007bff, #6610f2);">
                            Best Day<br>
                            <strong id="metric-best-day"><?= $data['performance_metrics']['best_day'] ?? '$0' ?></strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="metric-badge" style="background: linear-gradient(45deg, #fd7e14, #e83e8c);">
                            Consistency<br>
                            <strong id="metric-consistency"><?= $data['performance_metrics']['consistency_score'] ?? 0 ?>%</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="metric-badge" style="background: linear-gradient(45deg, #20c997, #17a2b8);">
                            Avg AG1 Rate<br>
                            <strong id="metric-ag1-rate"><?= $data['performance_metrics']['avg_ag1_rate'] ?? 0 ?>%</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="metric-badge" style="background: linear-gradient(45deg, #6f42c1, #e83e8c);">
                            Avg AG2 Rate<br>
                            <strong id="metric-ag2-rate"><?= $data['performance_metrics']['avg_ag2_rate'] ?? 0 ?>%</strong>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="metric-badge" style="background: linear-gradient(45deg, #dc3545, #fd7e14);">
                            Total Transactions<br>
                            <strong id="metric-total-transactions"><?= $data['performance_metrics']['total_transactions'] ?? 0 ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="chart-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Monthly Financial Performance</h5>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary active" onclick="switchChart('monthly_trends')">Monthly Trends</button>
                    <button type="button" class="btn btn-outline-primary" onclick="switchChart('daily_performance')">Daily Performance</button>
                </div>
            </div>
            <canvas id="mainChart" height="300"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-container">
            <h5 class="mb-3"><i class="bi bi-pie-chart me-2"></i>Rate Analysis</h5>
            <canvas id="rateChart" height="300"></canvas>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6">
        <div class="chart-container">
            <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i>Comparative Analysis</h5>
            <canvas id="compareChart" height="250"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Financial Insights & Trends</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($data['trend_analysis'])): ?>
                <div class="insight-card">
                    <h6 class="text-primary">📈 Growth Trend</h6>
                    <p class="mb-1">
                        <strong>CA Trend:</strong> 
                        <span class="badge bg-<?= $data['trend_analysis']['ca_trend'] == 'upward' ? 'success' : ($data['trend_analysis']['ca_trend'] == 'downward' ? 'danger' : 'secondary') ?>">
                            <?= ucfirst(str_replace('_', ' ', $data['trend_analysis']['ca_trend'])) ?>
                        </span>
                    </p>
                    <p class="mb-1">
                        <strong>FI Trend:</strong> 
                        <span class="badge bg-<?= $data['trend_analysis']['fi_trend'] == 'upward' ? 'success' : ($data['trend_analysis']['fi_trend'] == 'downward' ? 'danger' : 'secondary') ?>">
                            <?= ucfirst(str_replace('_', ' ', $data['trend_analysis']['fi_trend'])) ?>
                        </span>
                    </p>
                    <p class="mb-0">
                        <strong>Growth Rate:</strong> <?= $data['trend_analysis']['growth_rate'] ?>%
                    </p>
                </div>

                <div class="insight-card">
                    <h6 class="text-success">🎯 Rate Stability</h6>
                    <p class="mb-1">
                        <strong>Rate Consistency:</strong> 
                        <span class="badge bg-<?= $data['trend_analysis']['rate_stability'] == 'stable' ? 'success' : 'warning' ?>">
                            <?= ucfirst(str_replace('_', ' ', $data['trend_analysis']['rate_stability'])) ?>
                        </span>
                    </p>
                </div>

                <?php if (isset($data['trend_analysis']['forecast']) && $data['trend_analysis']['forecast']['status'] == 'available'): ?>
                <div class="insight-card">
                    <h6 class="text-warning">🔮 Forecast</h6>
                    <p class="mb-1">
                        <strong>Next Month FI:</strong> <?= $data['trend_analysis']['forecast']['next_month_fi'] ?>
                    </p>
                    <p class="mb-0">
                        <strong>Confidence:</strong> 
                        <span class="badge bg-<?= $data['trend_analysis']['forecast']['confidence'] == 'high' ? 'success' : ($data['trend_analysis']['forecast']['confidence'] == 'medium' ? 'warning' : 'danger') ?>">
                            <?= ucfirst($data['trend_analysis']['forecast']['confidence']) ?>
                        </span>
                    </p>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <p class="text-muted">No trend data available yet. Add more transactions to see insights.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Transactions</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($data['recent_transactions'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>CA Amount</th>
                                <th>AG1 Rate</th>
                                <th>AG2 Rate</th>
                                <th>Final Income</th>
                                <th>Efficiency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($data['recent_transactions'], 0, 5) as $txn): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($txn['txn_date'])) ?></td>
                                <td><?= $txn['ca_formatted'] ?></td>
                                <td><span class="badge bg-primary"><?= $txn['ag1_rate_percent'] ?>%</span></td>
                                <td><span class="badge bg-info"><?= $txn['ag2_rate_percent'] ?>%</span></td>
                                <td class="fw-bold text-success"><?= $txn['fi_formatted'] ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-<?= $txn['efficiency'] > 50 ? 'success' : ($txn['efficiency'] > 25 ? 'warning' : 'danger') ?>" 
                                             style="width: <?= min(100, abs($txn['efficiency'])) ?>%">
                                            <?= $txn['efficiency'] ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="<?= appUrl('daily') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-list me-2"></i>View All Transactions
                    </a>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No recent transactions found</p>
                    <a href="<?= appUrl('daily/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add First Transaction
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Chart instances
let mainChart, rateChart, compareChart;

// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    
    // Add event listeners for month/year selectors
    document.getElementById('monthSelector').addEventListener('change', refreshDashboard);
    document.getElementById('yearSelector').addEventListener('change', refreshDashboard);
});

function initializeCharts() {
    try {
        // Main Chart (Monthly Trends)
        const mainCtx = document.getElementById('mainChart');
        if (mainCtx) {
            const monthlyData = <?= json_encode($data['chart_data']['monthly_trends'] ?? []) ?>;
            console.log('Monthly data:', monthlyData);
            
            if (monthlyData && monthlyData.data) {
                const chartOptions = {
                    ...monthlyData.options,
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                };
                
                mainChart = new Chart(mainCtx, {
                    type: monthlyData.type || 'line',
                    data: monthlyData.data,
                    options: chartOptions
                });
            }
        }

        // Rate Analysis Chart
        const rateCtx = document.getElementById('rateChart');
        if (rateCtx) {
            const rateData = <?= json_encode($data['chart_data']['rate_analysis'] ?? []) ?>;
            console.log('Rate data:', rateData);
            
            if (rateData && rateData.data) {
                const rateOptions = {
                    ...rateData.options,
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                };
                
                rateChart = new Chart(rateCtx, {
                    type: rateData.type || 'line',
                    data: rateData.data,
                    options: rateOptions
                });
            }
        }

        // Comparative Analysis Chart
        const compareCtx = document.getElementById('compareChart');
        if (compareCtx) {
            const compareData = <?= json_encode($data['chart_data']['comparative_analysis'] ?? []) ?>;
            console.log('Compare data:', compareData);
            
            if (compareData && compareData.data) {
                const compareOptions = {
                    ...compareData.options,
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                };
                
                compareChart = new Chart(compareCtx, {
                    type: compareData.type || 'bar',
                    data: compareData.data,
                    options: compareOptions
                });
            }
        }
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
}

function switchChart(chartType) {
    // Update button states
    document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Destroy existing chart
    if (mainChart) {
        mainChart.destroy();
    }
    
    // Load new chart data
    const year = document.getElementById('yearSelector').value;
    const month = document.getElementById('monthSelector').value;
    
    const timestamp = Date.now();
    fetch(`<?= appUrl('api/dashboard/chart') ?>?type=${chartType}&year=${year}&month=${month}&_t=${timestamp}`, {
        cache: 'no-cache',
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const ctx = document.getElementById('mainChart').getContext('2d');
                
                // Create chart options with proper formatting
                const chartOptions = {
                    ...data.data.options,
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (chartType === 'rate_analysis') {
                                        return value + '%';
                                    }
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                };
                
                mainChart = new Chart(ctx, {
                    type: data.data.type || 'line',
                    data: data.data.data,
                    options: chartOptions
                });
            }
        })
        .catch(error => {
            console.error('Error loading chart:', error);
        });
}

function refreshDashboard() {
    const year = document.getElementById('yearSelector').value;
    const month = document.getElementById('monthSelector').value;
    
    console.log('Refreshing dashboard for year:', year, 'month:', month);
    
    // Show loading state
    const refreshBtn = document.querySelector('.btn[onclick="refreshDashboard()"]');
    if (refreshBtn) {
        refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Loading...';
        refreshBtn.disabled = true;
    }
    
    // Update KPIs first
    updateKPIs(year, month)
        .then(() => updateCharts(year, month))
        .then(() => {
            console.log('Dashboard refresh completed');
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh';
                refreshBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Dashboard refresh failed:', error);
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh';
                refreshBtn.disabled = false;
            }
        });
}

function updateKPIs(year, month) {
    const timestamp = Date.now();
    const url = `<?= appUrl('api/dashboard/kpis') ?>?year=${year}&month=${month}&_t=${timestamp}`;
    console.log('Fetching KPIs from:', url);
    
    return fetch(url, {
        cache: 'no-cache',
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        }
    })
        .then(response => {
            console.log('KPIs response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('KPIs data received:', data);
            
            if (data.success && data.data) {
                const kpis = data.data.kpis;
                const performanceMetrics = data.data.performance_metrics;
                
                // Update KPI values
                const kpiElements = {
                    'mtd_ca_formatted': document.querySelector('.kpi-value'),
                    'mtd_fi_formatted': document.querySelectorAll('.kpi-value')[1],
                    'ytd_fi_formatted': document.querySelectorAll('.kpi-value')[2],
                    'efficiency_ratio': document.querySelectorAll('.kpi-value')[3]
                };
                
                if (kpiElements.mtd_ca_formatted && kpis.mtd_ca_formatted) {
                    kpiElements.mtd_ca_formatted.textContent = kpis.mtd_ca_formatted;
                }
                if (kpiElements.mtd_fi_formatted && kpis.mtd_fi_formatted) {
                    kpiElements.mtd_fi_formatted.textContent = kpis.mtd_fi_formatted;
                }
                if (kpiElements.ytd_fi_formatted && kpis.ytd_fi_formatted) {
                    kpiElements.ytd_fi_formatted.textContent = kpis.ytd_fi_formatted;
                }
                if (kpiElements.efficiency_ratio && kpis.efficiency_ratio) {
                    kpiElements.efficiency_ratio.textContent = kpis.efficiency_ratio + '%';
                }
                
                // Update YTD transaction count
                const ytdTransactionElement = document.getElementById('ytd-transaction-count');
                if (ytdTransactionElement && kpis.ytd_transaction_count !== undefined) {
                    ytdTransactionElement.textContent = kpis.ytd_transaction_count + ' transactions';
                }
                
                // Update Performance Metrics
                if (performanceMetrics) {
                    const metricElements = {
                        'avg_transaction_size': document.getElementById('metric-avg-transaction'),
                        'best_day': document.getElementById('metric-best-day'),
                        'consistency_score': document.getElementById('metric-consistency'),
                        'avg_ag1_rate': document.getElementById('metric-ag1-rate'),
                        'avg_ag2_rate': document.getElementById('metric-ag2-rate'),
                        'total_transactions': document.getElementById('metric-total-transactions')
                    };
                    
                    if (metricElements.avg_transaction_size && performanceMetrics.avg_transaction_size !== undefined) {
                        metricElements.avg_transaction_size.textContent = performanceMetrics.avg_transaction_size;
                    }
                    if (metricElements.best_day && performanceMetrics.best_day !== undefined) {
                        metricElements.best_day.textContent = performanceMetrics.best_day;
                    }
                    if (metricElements.consistency_score && performanceMetrics.consistency_score !== undefined) {
                        metricElements.consistency_score.textContent = performanceMetrics.consistency_score + '%';
                    }
                    if (metricElements.avg_ag1_rate && performanceMetrics.avg_ag1_rate !== undefined) {
                        metricElements.avg_ag1_rate.textContent = performanceMetrics.avg_ag1_rate + '%';
                    }
                    if (metricElements.avg_ag2_rate && performanceMetrics.avg_ag2_rate !== undefined) {
                        metricElements.avg_ag2_rate.textContent = performanceMetrics.avg_ag2_rate + '%';
                    }
                    if (metricElements.total_transactions && performanceMetrics.total_transactions !== undefined) {
                        metricElements.total_transactions.textContent = performanceMetrics.total_transactions;
                    }
                    
                    console.log('Performance metrics updated successfully');
                }
                
                // Update Trend Analysis
                if (data.data.trend_analysis) {
                    updateTrendAnalysis(data.data.trend_analysis);
                }
                
                console.log('KPIs updated successfully');
            } else {
                console.error('Invalid KPIs response format:', data);
            }
        })
        .catch(error => {
            console.error('Error updating KPIs:', error);
            throw error;
        });
}

function updateTrendAnalysis(trendData) {
    console.log('Updating trend analysis:', trendData);
    
    try {
        // Update CA Trend
        const caTrendElement = document.querySelector('.insight-card .badge');
        if (caTrendElement && trendData.ca_trend) {
            caTrendElement.textContent = trendData.ca_trend.replace('_', ' ').toUpperCase();
            caTrendElement.className = `badge bg-${trendData.ca_trend === 'upward' ? 'success' : (trendData.ca_trend === 'downward' ? 'danger' : 'secondary')}`;
        }
        
        // Update FI Trend
        const fiTrendElement = document.querySelectorAll('.insight-card .badge')[1];
        if (fiTrendElement && trendData.fi_trend) {
            fiTrendElement.textContent = trendData.fi_trend.replace('_', ' ').toUpperCase();
            fiTrendElement.className = `badge bg-${trendData.fi_trend === 'upward' ? 'success' : (trendData.fi_trend === 'downward' ? 'danger' : 'secondary')}`;
        }
        
        // Update Growth Rate
        const growthRateElement = document.querySelector('.insight-card p:nth-child(4)');
        if (growthRateElement && trendData.growth_rate !== undefined) {
            growthRateElement.innerHTML = `<strong>Growth Rate:</strong> ${trendData.growth_rate}%`;
        }
        
        // Update Rate Stability
        const rateStabilityElement = document.querySelectorAll('.insight-card .badge')[2];
        if (rateStabilityElement && trendData.rate_stability) {
            rateStabilityElement.textContent = trendData.rate_stability.replace('_', ' ').toUpperCase();
            rateStabilityElement.className = `badge bg-${trendData.rate_stability === 'stable' ? 'success' : 'warning'}`;
        }
        
        // Update Forecast if available
        if (trendData.forecast && trendData.forecast.status === 'available') {
            const forecastFiElement = document.querySelector('.insight-card:last-child p:nth-child(2)');
            const forecastConfidenceElement = document.querySelector('.insight-card:last-child .badge:last-child');
            
            if (forecastFiElement && trendData.forecast.next_month_fi) {
                forecastFiElement.innerHTML = `<strong>Next Month FI:</strong> ${trendData.forecast.next_month_fi}`;
            }
            
            if (forecastConfidenceElement && trendData.forecast.confidence) {
                forecastConfidenceElement.textContent = trendData.forecast.confidence.toUpperCase();
                forecastConfidenceElement.className = `badge bg-${trendData.forecast.confidence === 'high' ? 'success' : (trendData.forecast.confidence === 'medium' ? 'warning' : 'danger')}`;
            }
        }
        
        console.log('Trend analysis updated successfully');
    } catch (error) {
        console.error('Error updating trend analysis:', error);
    }
}

function updateCharts(year, month) {
    const chartUpdates = [];
    const timestamp = Date.now();
    
    // Update main chart
    if (mainChart) {
        chartUpdates.push(
            fetch(`<?= appUrl('api/dashboard/chart') ?>?type=monthly_trends&year=${year}&month=${month}&_t=${timestamp}`, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Main chart data received:', data);
                    if (data.success && data.data && data.data.data) {
                        mainChart.data.labels = data.data.data.labels;
                        mainChart.data.datasets = data.data.data.datasets;
                        mainChart.update();
                        console.log('Main chart updated');
                    }
                })
                .catch(error => {
                    console.error('Error updating main chart:', error);
                })
        );
    }
    
    // Update rate chart
    if (rateChart) {
        chartUpdates.push(
            fetch(`<?= appUrl('api/dashboard/chart') ?>?type=rate_analysis&year=${year}&month=${month}&_t=${timestamp}`, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Rate chart data received:', data);
                    if (data.success && data.data && data.data.data) {
                        rateChart.data.labels = data.data.data.labels;
                        rateChart.data.datasets = data.data.data.datasets;
                        rateChart.update();
                        console.log('Rate chart updated');
                    }
                })
                .catch(error => {
                    console.error('Error updating rate chart:', error);
                })
        );
    }
    
    // Update comparison chart
    if (compareChart) {
        chartUpdates.push(
            fetch(`<?= appUrl('api/dashboard/chart') ?>?type=comparative_analysis&year=${year}&month=${month}&_t=${timestamp}`, {
                cache: 'no-cache',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            })
                .then(response => response.json())
                .then(data => {
                    console.log('Compare chart data received:', data);
                    if (data.success && data.data && data.data.data) {
                        compareChart.data.labels = data.data.data.labels;
                        compareChart.data.datasets = data.data.data.datasets;
                        compareChart.update();
                        console.log('Compare chart updated');
                    }
                })
                .catch(error => {
                    console.error('Error updating compare chart:', error);
                })
        );
    }
    
    return Promise.all(chartUpdates);
}

// Auto-refresh every 5 minutes
setInterval(function() {
    const year = document.getElementById('yearSelector').value;
    const month = document.getElementById('monthSelector').value;
    
    // Update KPIs via AJAX
    fetch(`<?= appUrl('api/dashboard/kpis') ?>?year=${year}&month=${month}`)
        .then(response => response.json())
        .then(data => {
            // Update KPI values
            if (data.mtd_ca_formatted) {
                document.querySelector('.kpi-value').textContent = data.mtd_ca_formatted;
            }
        })
        .catch(error => console.error('Auto-refresh error:', error));
}, 300000); // 5 minutes

// Add CSS for spinning animation
const style = document.createElement('style');
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
