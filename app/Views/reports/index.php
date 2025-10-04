<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}
?>

<style>
.report-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 15px;
    transition: transform 0.3s ease;
    cursor: pointer;
}
.report-card:hover {
    transform: translateY(-5px);
}
.report-card.active {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
}
.stat-card {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 15px;
}
.trend-up { color: #28a745; }
.trend-down { color: #dc3545; }
.trend-stable { color: #6c757d; }
#mainChart {
    min-height: 400px !important;
    max-height: 500px !important;
}
.chart-container canvas {
    min-height: 400px !important;
}

/* Mobile Responsiveness for Reports */
@media (max-width: 768px) {
    .report-card {
        min-height: 120px;
    }
    
    .report-card .card-body {
        padding: 1rem;
    }
    
    .report-card h6 {
        font-size: 0.9rem;
    }
    
    .report-card small {
        font-size: 0.75rem;
    }
    
    .chart-container {
        padding: 15px;
    }
    
    #mainChart {
        min-height: 250px !important;
        max-height: 300px !important;
    }
    
    .metric-card {
        padding: 15px;
        margin-bottom: 10px;
    }
    
    .metric-card h3 {
        font-size: 1.5rem;
    }
    
    .insight-badge {
        font-size: 0.75rem;
        padding: 6px 12px;
        margin: 3px;
    }
}

@media (max-width: 576px) {
    .report-card {
        min-height: 100px;
    }
    
    .report-card .fs-2 {
        font-size: 1.5rem !important;
    }
    
    #mainChart {
        min-height: 200px !important;
        max-height: 250px !important;
    }
    
    .metric-card h3 {
        font-size: 1.25rem;
    }
    
    .metric-card h6 {
        font-size: 0.8rem;
    }
    
    .insight-badge {
        font-size: 0.7rem;
        padding: 4px 8px;
        margin: 2px;
        display: block;
        text-align: center;
    }
}
</style>

<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-0 text-primary">📊 Professional Reports & Analytics</h1>
                <p class="text-muted mb-0 fs-5">Comprehensive financial reporting system</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="refreshReports()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                </button>
                <button class="btn btn-outline-success" onclick="exportAllReports()" id="exportAllBtn" disabled>
                    <i class="bi bi-download me-2"></i>Export All
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report Type Selection -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-collection me-2"></i>Report Types</h5>
            </div>
            <div class="card-body">
                <div class="row" id="reportTypeCards">
                    <?php foreach ($data['report_types'] as $key => $reportType): ?>
                    <div class="col-md-4 col-lg-2 mb-3">
                        <div class="report-card" data-report-type="<?= $key ?>" onclick="selectReportType('<?= $key ?>')">
                            <div class="card-body text-center">
                                <i class="bi bi-graph-up fs-2 mb-2"></i>
                                <h6 class="card-title"><?= htmlspecialchars($reportType['name']) ?></h6>
                                <small class="opacity-75"><?= htmlspecialchars($reportType['description']) ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Parameters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Report Parameters</h5>
            </div>
            <div class="card-body">
                <form id="reportForm" class="row g-3">
                    <input type="hidden" id="report_type" name="report_type" value="financial_summary">
                    
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?= $data['default_start_date'] ?>" 
                               min="<?= $data['date_range']['min_date'] ?>"
                               max="<?= $data['date_range']['max_date'] ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?= $data['default_end_date'] ?>"
                               min="<?= $data['date_range']['min_date'] ?>"
                               max="<?= $data['date_range']['max_date'] ?>" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="group_by" class="form-label">Group By</label>
                        <select class="form-select" id="group_by" name="group_by">
                            <option value="day">Daily</option>
                            <option value="week">Weekly</option>
                            <option value="month" selected>Monthly</option>
                            <option value="year">Yearly</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-play-fill me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Quick Date Ranges -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('today')">Today</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('week')">This Week</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('month')">This Month</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('quarter')">This Quarter</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('year')">This Year</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Results -->
<div id="reportResults" style="display: none;">
    <!-- Summary Cards -->
    <div class="row mb-4" id="summaryCards">
        <!-- Dynamic summary cards will be inserted here -->
    </div>

    <!-- Charts and Analysis -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="chart-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0" id="chartTitle"><i class="bi bi-bar-chart me-2"></i>Trend Analysis</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="switchChartType('line')">Line</button>
                        <button type="button" class="btn btn-outline-primary" onclick="switchChartType('bar')">Bar</button>
                        <button type="button" class="btn btn-outline-primary" onclick="switchChartType('area')">Area</button>
                    </div>
                </div>
                <canvas id="mainChart" height="400"></canvas>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Key Insights</h6>
                </div>
                <div class="card-body" id="insightsPanel">
                    <div class="text-center text-muted">
                        <i class="bi bi-lightbulb fs-1 d-block mb-2"></i>
                        <p>Generate a report to see insights</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analysis Section (for specific analysis reports) -->
    <div id="detailedAnalysisSection" style="display: none;">
        <div class="row mb-4" id="detailedAnalysisCards">
            <!-- Dynamic detailed analysis cards will be inserted here -->
        </div>
    </div>

    <!-- Data Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-table me-2"></i>Detailed Data</h6>
                    <button class="btn btn-sm btn-outline-light" onclick="exportCurrentReport()">
                        <i class="bi bi-download me-1"></i>Export CSV
                    </button>
                </div>
                <div class="card-body p-0" id="dataTable">
                    <!-- Table will be populated here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading State -->
<div id="loadingState" style="display: none;">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Generating Report...</h5>
                    <p class="text-muted">Please wait while we process your data</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Empty State -->
<div id="emptyState">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-graph-up fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">Professional Reporting System</h5>
                    <p class="text-muted mb-4">Select a report type above and configure your parameters to generate comprehensive financial reports with advanced analytics.</p>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <i class="bi bi-graph-up-arrow fs-2 text-primary"></i>
                                        <h6 class="mt-2">Multiple Report Types</h6>
                                        <small class="text-muted">Financial, Profit, Rate, Expense & Comparative Analysis</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <i class="bi bi-calendar-range fs-2 text-success"></i>
                                        <h6 class="mt-2">Flexible Date Ranges</h6>
                                        <small class="text-muted">Daily, Weekly, Monthly & Yearly grouping options</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <i class="bi bi-download fs-2 text-info"></i>
                                        <h6 class="mt-2">Export Capabilities</h6>
                                        <small class="text-muted">CSV export for further analysis</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let mainChart = null;
let currentReportData = null;
let currentChartType = 'line';

document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded!');
        showError('Chart.js library is not loaded. Please refresh the page.');
        return;
    }
    
    console.log('Chart.js version:', Chart.version);
    
    // Initialize form handlers
    document.getElementById('reportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        generateReport();
    });
    
    // Initialize date validation
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
    });
    
    endDate.addEventListener('change', function() {
        startDate.max = this.value;
    });
    
    // Select first report type by default
    selectReportType('financial_summary');
    
    console.log('Reports page initialized successfully');
});

function selectReportType(reportType) {
    // Update UI
    document.querySelectorAll('.report-card').forEach(card => {
        card.classList.remove('active');
    });
    document.querySelector(`[data-report-type="${reportType}"]`).classList.add('active');
    
    // Update form
    document.getElementById('report_type').value = reportType;
    
    console.log('Selected report type:', reportType);
}

function setDateRange(range) {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const today = new Date();
    
    let start, end;
    
    switch (range) {
        case 'today':
            start = end = today.toISOString().split('T')[0];
            break;
        case 'week':
            const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
            const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));
            start = startOfWeek.toISOString().split('T')[0];
            end = endOfWeek.toISOString().split('T')[0];
            break;
        case 'month':
            start = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            end = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
        case 'quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            start = new Date(today.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
            end = new Date(today.getFullYear(), quarter * 3 + 3, 0).toISOString().split('T')[0];
            break;
        case 'year':
            start = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            end = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
            break;
    }
    
    startDate.value = start;
    endDate.value = end;
}

function generateReport() {
    showLoadingState();
    
    const formData = new FormData(document.getElementById('reportForm'));
    const params = new URLSearchParams(formData);
    
    fetch(`<?= appUrl('reports/data') ?>?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                currentReportData = data.data;
                displayReport(data.data);
                document.getElementById('exportAllBtn').disabled = false;
            } else {
                console.error('Report generation failed:', data.message);
                showError('Failed to generate report: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error generating report:', error);
            hideLoadingState();
            showError('Failed to generate report. Please check your connection and try again.<br><small>Debug: Check browser console for details or visit <a href="<?= Response::url('reports/test') ?>">reports test page</a></small>');
        });
}

function displayReport(reportData) {
    hideLoadingState();
    showReportResults();
    
    updateSummaryCards(reportData);
    updateChart(reportData);
    updateInsights(reportData);
    updateDataTable(reportData);
    
    // Handle detailed analysis sections for new report types
    if (reportData.type && reportData.type.includes('_analysis')) {
        updateDetailedAnalysis(reportData);
    }
    
    document.getElementById('chartTitle').innerHTML = 
        `<i class="bi bi-bar-chart me-2"></i>${reportData.title}`;
}

function updateSummaryCards(reportData) {
    const summaryCards = document.getElementById('summaryCards');
    let cardsHtml = '';
    
    if (reportData.summary) {
        const metrics = Object.keys(reportData.summary);
        const colors = ['primary', 'success', 'info', 'warning', 'danger'];
        
        // Filter out non-financial metrics for detailed analysis reports
        const financialMetrics = metrics.filter(metric => 
            !['volatility', 'consistency_score', 'trend_direction', 'growth_rate'].includes(metric)
        );
        
        // Use financial metrics first, then show analysis metrics if it's a detailed analysis
        const displayMetrics = financialMetrics.length > 0 ? financialMetrics : metrics;
        
        displayMetrics.slice(0, 4).forEach((metric, index) => {
            const stats = reportData.summary[metric];
            const color = colors[index % colors.length];
            
            // Handle different data structures for different report types
            let total, average, max, displayValue, subtitle;
            
            // Special handling for analysis metrics
            if (['volatility', 'consistency_score', 'trend_direction', 'growth_rate'].includes(metric)) {
                displayValue = formatAnalysisValue(metric, stats);
                subtitle = getAnalysisSubtitle(metric, stats);
            } else {
                // Regular financial metrics
                total = stats.total !== undefined ? stats.total : 'N/A';
                average = stats.average !== undefined ? stats.average : 0;
                max = stats.max !== undefined ? stats.max : 0;
                displayValue = total !== 'N/A' ? formatMoney(total) : total;
                subtitle = `Avg: ${formatMoney(average)} | Max: ${formatMoney(max)}`;
            }
            
            cardsHtml += `
                <div class="col-md-3">
                    <div class="card border-${color}">
                        <div class="card-body text-center">
                            <h6 class="card-title text-${color}">${metric.toUpperCase().replace('_', ' ')}</h6>
                            <h4 class="text-${color}">${displayValue}</h4>
                            <small class="text-muted">${subtitle}</small>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    summaryCards.innerHTML = cardsHtml;
}

function updateChart(reportData) {
    const ctx = document.getElementById('mainChart');
    
    if (!ctx) {
        console.error('Chart canvas element not found!');
        showError('Chart canvas element not found. Please refresh the page.');
        return;
    }
    
    if (mainChart) {
        mainChart.destroy();
        mainChart = null;
    }
    
    if (reportData.chart && reportData.chart.data) {
        try {
            // Validate chart data structure
            if (!reportData.chart.data.labels || !reportData.chart.data.datasets) {
                throw new Error('Invalid chart data structure');
            }
            
            const chartConfig = {
                type: currentChartType,
                data: reportData.chart.data,
                options: {
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
                }
            };
            
            // Adjust chart type specific options
            if (currentChartType === 'area') {
                chartConfig.type = 'line';
                chartConfig.data.datasets.forEach(dataset => {
                    dataset.fill = true;
                    dataset.backgroundColor = dataset.backgroundColor || 'rgba(54, 162, 235, 0.1)';
                });
            }
            
            mainChart = new Chart(ctx, chartConfig);
            
        } catch (error) {
            console.error('Error creating chart:', error);
            showError('Failed to create chart: ' + error.message);
        }
    } else {
        console.warn('No chart data available');
        showError('No chart data received from server');
    }
}

function updateInsights(reportData) {
    const insightsPanel = document.getElementById('insightsPanel');
    let insightsHtml = '';
    
    if (reportData.insights && reportData.insights.length > 0) {
        reportData.insights.forEach(insight => {
            const iconClass = insight.type === 'warning' ? 'bi-exclamation-triangle' : 
                            insight.type === 'success' ? 'bi-check-circle' : 'bi-info-circle';
            const colorClass = insight.type === 'warning' ? 'warning' : 
                              insight.type === 'success' ? 'success' : 'info';
            
            insightsHtml += `
                <div class="insight-card border-${colorClass}">
                    <div class="d-flex align-items-start">
                        <i class="bi ${iconClass} text-${colorClass} me-2 mt-1"></i>
                        <div>
                            <h6 class="mb-1">${insight.title}</h6>
                            <small class="text-muted">${insight.message}</small>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        insightsHtml = `
            <div class="text-center text-muted">
                <i class="bi bi-lightbulb fs-3 d-block mb-2"></i>
                <p>No specific insights available for this report.</p>
            </div>
        `;
    }
    
    insightsPanel.innerHTML = insightsHtml;
}

function updateDataTable(reportData) {
    const dataTable = document.getElementById('dataTable');
    
    if (reportData.table && reportData.table.length > 0) {
        let tableHtml = `
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
        `;
        
        // Generate headers from first row
        const headers = Object.keys(reportData.table[0]);
        headers.forEach(header => {
            const isNumeric = ['ca', 'fi', 'ga', 'je', 'transactions', 'avg_fi'].includes(header);
            tableHtml += `<th class="${isNumeric ? 'text-end' : ''}">${header.replace('_', ' ').toUpperCase()}</th>`;
        });
        
        tableHtml += `
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        // Generate rows
        reportData.table.forEach(row => {
            tableHtml += '<tr>';
            headers.forEach(header => {
                const value = row[header];
                const isNumeric = ['ca', 'fi', 'ga', 'je', 'avg_fi'].includes(header);
                const formattedValue = isNumeric && typeof value === 'number' ? 
                    formatMoney(value) : value;
                
                tableHtml += `<td class="${isNumeric ? 'text-end table-money' : ''}">${formattedValue}</td>`;
            });
            tableHtml += '</tr>';
        });
        
        tableHtml += `
                    </tbody>
                </table>
            </div>
        `;
        
        dataTable.innerHTML = tableHtml;
    } else {
        dataTable.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-table fs-3 d-block mb-2"></i>
                <p>No data available for the selected criteria.</p>
            </div>
        `;
    }
}

function switchChartType(type) {
    currentChartType = type;
    
    // Update button states
    document.querySelectorAll('.btn-group .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Regenerate chart if data exists
    if (currentReportData) {
        updateChart(currentReportData);
    }
}

function exportCurrentReport() {
    if (!currentReportData) {
        showError('Please generate a report first.');
        return;
    }
    
    const formData = new FormData(document.getElementById('reportForm'));
    const params = new URLSearchParams(formData);
    params.append('export', 'csv');
    
    window.open(`<?= appUrl('reports/data') ?>?${params}`, '_blank');
}

function exportAllReports() {
    // This would export all report types - placeholder for now
    showError('Export All functionality coming soon!');
}

function refreshReports() {
    if (currentReportData) {
        generateReport();
    }
}

// Utility functions
function showLoadingState() {
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('reportResults').style.display = 'none';
    document.getElementById('loadingState').style.display = 'block';
}

function hideLoadingState() {
    document.getElementById('loadingState').style.display = 'none';
}

function showReportResults() {
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('reportResults').style.display = 'block';
}

function showError(message) {
    hideLoadingState();
    
    // Create error alert
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
        </div>
    `;
    
    // Insert at top of page
    const container = document.querySelector('.row').parentNode;
    container.insertAdjacentHTML('afterbegin', alertHtml);
}

function formatMoney(amount) {
    if (typeof amount !== 'number') return amount;
    return '$' + amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatAnalysisValue(metric, value) {
    if (value === null || value === undefined) return 'N/A';
    
    switch(metric) {
        case 'volatility':
            return typeof value === 'number' ? `${value.toFixed(1)}%` : value;
        case 'consistency_score':
            return typeof value === 'number' ? `${value.toFixed(1)}%` : value;
        case 'growth_rate':
            return typeof value === 'number' ? `${value.toFixed(1)}%` : value;
        case 'trend_direction':
            return typeof value === 'string' ? value.replace('_', ' ').toUpperCase() : value;
        default:
            return value;
    }
}

function getAnalysisSubtitle(metric, value) {
    if (value === null || value === undefined) return 'No data available';
    
    switch(metric) {
        case 'volatility':
            const vol = typeof value === 'number' ? value : 0;
            return vol < 10 ? 'Low volatility' : vol < 20 ? 'Moderate volatility' : 'High volatility';
        case 'consistency_score':
            const score = typeof value === 'number' ? value : 0;
            return score > 80 ? 'Highly consistent' : score > 60 ? 'Moderately consistent' : 'Inconsistent';
        case 'growth_rate':
            const growth = typeof value === 'number' ? value : 0;
            return growth > 10 ? 'Strong growth' : growth > 0 ? 'Positive growth' : growth < -10 ? 'Declining' : 'Stable';
        case 'trend_direction':
            return 'Trend analysis';
        default:
            return 'Analysis metric';
    }
}

function updateDetailedAnalysis(reportData) {
    const detailedSection = document.getElementById('detailedAnalysisSection');
    const detailedCards = document.getElementById('detailedAnalysisCards');
    
    // Show the detailed analysis section
    detailedSection.style.display = 'block';
    
    let cardsHtml = '';
    
    // Handle different analysis types
    switch(reportData.type) {
        case 'ca_analysis':
            cardsHtml = generateCAAnalysisCards(reportData);
            break;
        case 'ga_analysis':
            cardsHtml = generateGAAnalysisCards(reportData);
            break;
        case 're_analysis':
            cardsHtml = generateREAnalysisCards(reportData);
            break;
        case 'je_analysis':
            cardsHtml = generateJEAnalysisCards(reportData);
            break;
        default:
            detailedSection.style.display = 'none';
            return;
    }
    
    detailedCards.innerHTML = cardsHtml;
}

function generateCAAnalysisCards(reportData) {
    const { growth, benchmarks } = reportData;
    
    return `
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-trending-up me-2"></i>Growth Analysis</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-${growth.rate > 0 ? 'success' : 'danger'}">${growth.rate}%</h4>
                                <small class="text-muted">Growth Rate</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-info">${growth.trend}</h4>
                                <small class="text-muted">Trend</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Recent Avg:</small><br>
                            <strong>${formatMoney(growth.recent_average)}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Earlier Avg:</small><br>
                            <strong>${formatMoney(growth.earlier_average)}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Performance Benchmarks</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-primary">${benchmarks.conversion_rate}%</h4>
                                <small class="text-muted">Conversion Rate</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-warning">${formatMoney(benchmarks.average_deal_size)}</h4>
                                <small class="text-muted">Avg Deal Size</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <span class="badge bg-${benchmarks.performance_rating === 'excellent' ? 'success' : benchmarks.performance_rating === 'good' ? 'warning' : 'danger'} fs-6">
                            ${benchmarks.performance_rating.replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateGAAnalysisCards(reportData) {
    const { efficiency, cost_control } = reportData;
    
    return `
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Efficiency Analysis</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-warning">${efficiency.efficiency_ratio}%</h4>
                                <small class="text-muted">Efficiency Ratio</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-info">${formatMoney(efficiency.cost_per_transaction)}</h4>
                                <small class="text-muted">Cost per Transaction</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <span class="badge bg-${efficiency.efficiency_rating === 'excellent' ? 'success' : efficiency.efficiency_rating === 'good' ? 'warning' : 'danger'} fs-6">
                            ${efficiency.efficiency_rating.replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Cost Control</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-info">${cost_control.budget_utilization}%</h4>
                                <small class="text-muted">Budget Utilization</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-primary">${formatMoney(cost_control.average_spend)}</h4>
                                <small class="text-muted">Average Spend</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <span class="badge bg-${cost_control.control_status === 'under_control' ? 'success' : cost_control.control_status === 'monitor' ? 'warning' : 'danger'} fs-6">
                            ${cost_control.control_status.replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateREAnalysisCards(reportData) {
    const { optimization, performance } = reportData;
    
    return `
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="bi bi-arrow-up-circle me-2"></i>Revenue Optimization</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-success">${optimization.enhancement_ratio}%</h4>
                                <small class="text-muted">Enhancement Ratio</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-primary">${formatMoney(optimization.average_enhancement)}</h4>
                                <small class="text-muted">Avg Enhancement</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <span class="badge bg-${optimization.optimization_level === 'high' ? 'success' : optimization.optimization_level === 'moderate' ? 'warning' : 'info'} fs-6">
                            ${optimization.optimization_level.toUpperCase()} OPTIMIZATION
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Performance Tracking</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-primary">${performance.contribution_to_profit}%</h4>
                                <small class="text-muted">Profit Contribution</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-warning">${performance.performance_score}</h4>
                                <small class="text-muted">Performance Score</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <span class="badge bg-${performance.status === 'excellent' ? 'success' : performance.status === 'good' ? 'warning' : 'danger'} fs-6">
                            ${performance.status.replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function generateJEAnalysisCards(reportData) {
    const { allocation, optimization } = reportData;
    
    return `
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Expense Allocation</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-danger">${allocation.allocation_ratio}%</h4>
                                <small class="text-muted">Allocation Ratio</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-warning">${formatMoney(allocation.average_allocation)}</h4>
                                <small class="text-muted">Avg Allocation</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <span class="badge bg-${allocation.allocation_efficiency === 'efficient' ? 'success' : allocation.allocation_efficiency === 'moderate' ? 'warning' : 'danger'} fs-6">
                            ${allocation.allocation_efficiency.replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="bi bi-tools me-2"></i>Optimization Opportunities</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-secondary">${optimization.stability_score}</h4>
                                <small class="text-muted">Stability Score</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h4 class="text-info">${optimization.volatility}%</h4>
                                <small class="text-muted">Volatility</small>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <span class="badge bg-${optimization.optimization_potential === 'high' ? 'danger' : optimization.optimization_potential === 'moderate' ? 'warning' : 'success'} fs-6">
                            ${optimization.optimization_potential.toUpperCase()} POTENTIAL
                        </span>
                    </div>
                </div>
            </div>
        </div>
    `;
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
