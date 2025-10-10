<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}
?>

<style>
.statement-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 15px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    min-height: 140px;
}
.statement-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}
.statement-card.active {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    transform: translateY(-3px);
}
.statement-preview {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    min-height: 600px;
}
.metric-card {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
    padding: 20px;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 15px;
    transition: transform 0.2s ease;
}
.metric-card:hover {
    transform: scale(1.05);
}
.insight-badge {
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 0.875rem;
    font-weight: 500;
    margin: 5px;
    display: inline-block;
}
.insight-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.insight-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.insight-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.statement-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px 15px 0 0;
    margin: -20px -20px 20px -20px;
}
.comparison-arrow {
    font-size: 1.2rem;
    margin: 0 10px;
}
.trend-up { color: #28a745; }
.trend-down { color: #dc3545; }
.trend-stable { color: #6c757d; }

/* Mobile Responsiveness for Statements */
@media (max-width: 768px) {
    .statement-card {
        min-height: 120px;
    }
    
    .statement-card .card-body {
        padding: 1rem;
    }
    
    .statement-card h6 {
        font-size: 0.9rem;
    }
    
    .statement-card small {
        font-size: 0.75rem;
    }
    
    .statement-preview {
        min-height: 400px;
    }
    
    .statement-header {
        padding: 20px;
        margin: -15px -15px 15px -15px;
    }
    
    .statement-header h2 {
        font-size: 1.5rem;
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
    
    .comparison-arrow {
        font-size: 1rem;
        margin: 0 5px;
    }
}

@media (max-width: 576px) {
    .statement-card {
        min-height: 100px;
    }
    
    .statement-card .fs-2 {
        font-size: 1.5rem !important;
    }
    
    .statement-header {
        padding: 15px;
    }
    
    .statement-header h2 {
        font-size: 1.25rem;
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
    
    .comparison-arrow {
        font-size: 0.9rem;
        margin: 0 3px;
    }
    
    /* Stack all columns on very small screens */
    .col-lg-4, .col-md-4, .col-md-6 {
        margin-bottom: 1rem;
    }
}
</style>

<!-- Professional Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-0 text-primary">📊 Professional Statement Generator</h1>
                <p class="text-muted mb-0 fs-5">Advanced financial statement generation with comprehensive analysis</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="refreshStatements()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                </button>
                <button class="btn btn-outline-success" onclick="exportCurrentStatement()" id="exportBtn" disabled>
                    <i class="bi bi-download me-2"></i>Export Statement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statement Template Selection -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Statement Templates</h5>
            </div>
            <div class="card-body">
                <div class="row" id="templateCards">
                    <?php foreach ($data['templates'] as $key => $template): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="statement-card" data-template="<?= $key ?>" onclick="selectTemplate('<?= $key ?>')">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-text fs-2 mb-2"></i>
                                <h6 class="card-title"><?= htmlspecialchars($template['name']) ?></h6>
                                <small class="opacity-75"><?= htmlspecialchars($template['description']) ?></small>
                                <div class="mt-2">
                                    <?php foreach ($template['includes'] as $include): ?>
                                        <span class="badge bg-light text-dark me-1"><?= ucfirst($include) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statement Parameters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Statement Parameters</h5>
            </div>
            <div class="card-body">
                <form id="statementForm" class="row g-3">
                    <input type="hidden" id="template" name="template" value="comprehensive">
                    
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
                    
                    <div class="col-md-2">
                        <label for="group_by" class="form-label">Group By</label>
                        <select class="form-select" id="group_by" name="group_by">
                            <option value="day" selected>Daily</option>
                            <option value="week">Weekly</option>
                            <option value="month">Monthly</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="company_id" class="form-label">Company</label>
                        <select class="form-select" id="company_id" name="company_id">
                            <option value="">All Companies</option>
                            <?php foreach ($data['companies'] as $company): ?>
                                <option value="<?= $company['id'] ?>">
                                    <?= htmlspecialchars($company['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-file-earmark-plus me-2"></i>Generate Statement
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

<!-- Statement Results -->
<div id="statementResults" style="display: none;">
    <!-- Statement Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="statement-preview">
                <div class="statement-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-0" id="statementTitle">Professional Financial Statement</h2>
                            <p class="mb-0 opacity-75" id="statementPeriod">Period: Loading...</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="text-white">
                                <small>Generated: <span id="generatedDate"></span></small><br>
                                <small>By: <span id="generatedBy"></span></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Key Metrics -->
                <div class="row mb-4" id="keyMetrics">
                    <!-- Dynamic metric cards will be inserted here -->
                </div>
                
                <!-- Analysis & Insights -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h5><i class="bi bi-graph-up me-2"></i>Financial Analysis</h5>
                        <div id="analysisContent">
                            <!-- Analysis content will be inserted here -->
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h5><i class="bi bi-lightbulb me-2"></i>Key Insights</h5>
                        <div id="insightsContent">
                            <!-- Insights will be inserted here -->
                        </div>
                    </div>
                </div>
                
                <!-- Comparative Analysis -->
                <div class="row mb-4" id="comparativeSection" style="display: none;">
                    <div class="col-12">
                        <h5><i class="bi bi-arrow-left-right me-2"></i>Period Comparison</h5>
                        <div class="row" id="comparisonContent">
                            <!-- Comparison content will be inserted here -->
                        </div>
                    </div>
                </div>
                
                <!-- Transaction Details -->
                <div class="row" id="transactionSection">
                    <div class="col-12">
                        <h5><i class="bi bi-list-ul me-2"></i>Transaction Details</h5>
                        <div class="table-responsive" id="transactionTable">
                            <!-- Transaction table will be inserted here -->
                        </div>
                    </div>
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
                    <h5>Generating Professional Statement...</h5>
                    <p class="text-muted">Please wait while we analyze your financial data</p>
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
                    <i class="bi bi-file-earmark-text fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">Professional Statement Generator</h5>
                    <p class="text-muted mb-4">Select a statement template above and configure your parameters to generate comprehensive financial statements with advanced analytics.</p>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <i class="bi bi-file-earmark-check fs-2 text-primary"></i>
                                        <h6 class="mt-2">Multiple Templates</h6>
                                        <small class="text-muted">Comprehensive, Summary, Detailed, Financial & Audit statements</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <i class="bi bi-graph-up-arrow fs-2 text-success"></i>
                                        <h6 class="mt-2">Advanced Analytics</h6>
                                        <small class="text-muted">Performance analysis, trends, ratios & insights</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <i class="bi bi-calendar-range fs-2 text-info"></i>
                                        <h6 class="mt-2">Flexible Periods</h6>
                                        <small class="text-muted">Custom date ranges with comparative analysis</small>
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
let currentStatementData = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form handlers
    document.getElementById('statementForm').addEventListener('submit', function(e) {
        e.preventDefault();
        generateStatement();
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
    
    // Select first template by default
    selectTemplate('comprehensive');
});

function selectTemplate(templateType) {
    // Update UI
    document.querySelectorAll('.statement-card').forEach(card => {
        card.classList.remove('active');
    });
    document.querySelector(`[data-template="${templateType}"]`).classList.add('active');
    
    // Update form
    document.getElementById('template').value = templateType;
    
    console.log('Selected template:', templateType);
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

function generateStatement() {
    showLoadingState();
    
    const formData = new FormData(document.getElementById('statementForm'));
    const params = new URLSearchParams(formData);
    
    fetch(`<?= appUrl('statement/generate') ?>?${params}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Full response data:', data);
            if (data.success) {
                currentStatementData = data.data;
                displayStatement(data.data);
                document.getElementById('exportBtn').disabled = false;
            } else {
                console.error('Statement generation failed:', data.message);
                console.error('Debug info:', data.debug);
                showError('Failed to generate statement: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error generating statement:', error);
            hideLoadingState();
            showError('Failed to generate statement. Please check your connection and try again.');
        });
}

function displayStatement(statementData) {
    hideLoadingState();
    showStatementResults();
    
    updateStatementHeader(statementData);
    updateKeyMetrics(statementData);
    updateAnalysis(statementData);
    updateInsights(statementData);
    updateComparison(statementData);
    updateTransactionTable(statementData);
}

function updateStatementHeader(data) {
    document.getElementById('statementTitle').textContent = data.template.charAt(0).toUpperCase() + data.template.slice(1) + ' Statement';
    document.getElementById('statementPeriod').textContent = 'Period: ' + data.period.label;
    document.getElementById('generatedDate').textContent = new Date(data.metadata.generated_at).toLocaleString();
    document.getElementById('generatedBy').textContent = data.metadata.generated_by;
}

function updateKeyMetrics(data) {
    const keyMetrics = document.getElementById('keyMetrics');
    const totals = data.totals;
    
    const metrics = [
        { key: 'fi', label: 'Final Income', value: totals.fi, color: 'success' },
        { key: 'ca', label: 'Gross Inflow', value: totals.ca, color: 'primary' },
        { key: 'profit_margin', label: 'Profit Margin', value: totals.profit_margin + '%', color: 'info' },
        { key: 'total_expenses', label: 'Total Expenses', value: totals.total_expenses, color: 'warning' }
    ];
    
    let metricsHtml = '';
    metrics.forEach(metric => {
        const formattedValue = metric.key === 'profit_margin' ? metric.value : formatMoney(metric.value);
        metricsHtml += `
            <div class="col-md-3">
                <div class="metric-card bg-${metric.color}">
                    <h6 class="mb-1">${metric.label}</h6>
                    <h3 class="mb-0">${formattedValue}</h3>
                </div>
            </div>
        `;
    });
    
    keyMetrics.innerHTML = metricsHtml;
}

function updateAnalysis(data) {
    const analysisContent = document.getElementById('analysisContent');
    let analysisHtml = '';
    
    if (data.analysis) {
        Object.keys(data.analysis).forEach(key => {
            const analysis = data.analysis[key];
            analysisHtml += `
                <div class="mb-3">
                    <h6 class="text-primary">${key.charAt(0).toUpperCase() + key.slice(1)} Analysis</h6>
                    <div class="bg-light p-3 rounded">
                        ${JSON.stringify(analysis, null, 2).replace(/[{}",]/g, '').replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;
        });
    }
    
    analysisContent.innerHTML = analysisHtml || '<p class="text-muted">No analysis available for this template.</p>';
}

function updateInsights(data) {
    const insightsContent = document.getElementById('insightsContent');
    let insightsHtml = '';
    
    if (data.insights && data.insights.length > 0) {
        data.insights.forEach(insight => {
            insightsHtml += `
                <div class="insight-badge insight-${insight.type}">
                    <strong>${insight.title}</strong><br>
                    <small>${insight.message}</small>
                </div>
            `;
        });
    } else {
        insightsHtml = '<p class="text-muted">No specific insights available.</p>';
    }
    
    insightsContent.innerHTML = insightsHtml;
}

function updateComparison(data) {
    const comparativeSection = document.getElementById('comparativeSection');
    const comparisonContent = document.getElementById('comparisonContent');
    
    if (data.comparative && data.comparative.comparison) {
        comparativeSection.style.display = 'block';
        
        let comparisonHtml = '';
        Object.keys(data.comparative.comparison).forEach(metric => {
            const comp = data.comparative.comparison[metric];
            const trendClass = comp.trend === 'up' ? 'trend-up' : (comp.trend === 'down' ? 'trend-down' : 'trend-stable');
            const trendIcon = comp.trend === 'up' ? 'bi-arrow-up' : (comp.trend === 'down' ? 'bi-arrow-down' : 'bi-dash');
            
            comparisonHtml += `
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h6>${metric.toUpperCase()}</h6>
                            <div class="d-flex align-items-center justify-content-center">
                                <span>${formatMoney(comp.previous)}</span>
                                <i class="bi ${trendIcon} comparison-arrow ${trendClass}"></i>
                                <span>${formatMoney(comp.current)}</span>
                            </div>
                            <small class="${trendClass}">${comp.change > 0 ? '+' : ''}${comp.change.toFixed(1)}%</small>
                        </div>
                    </div>
                </div>
            `;
        });
        
        comparisonContent.innerHTML = comparisonHtml;
    } else {
        comparativeSection.style.display = 'none';
    }
}

function updateTransactionTable(data) {
    const transactionTable = document.getElementById('transactionTable');
    
    if (data.transactions && data.transactions.length > 0) {
        let tableHtml = `
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th class="text-end">CA</th>
                        <th class="text-end">GA</th>
                        <th class="text-end">JE</th>
                        <th class="text-end">FI</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.transactions.forEach(txn => {
            tableHtml += `
                <tr>
                    <td>
                        <strong>${txn.formatted_date}</strong><br>
                        <small class="text-muted">${txn.day_of_week}</small>
                    </td>
                    <td class="text-end table-money">${formatMoney(txn.ca)}</td>
                    <td class="text-end table-money">${formatMoney(txn.ga)}</td>
                    <td class="text-end table-money">${formatMoney(txn.je)}</td>
                    <td class="text-end table-money fw-bold text-success">${formatMoney(txn.fi)}</td>
                    <td class="text-muted">${txn.note || '-'}</td>
                </tr>
            `;
        });
        
        tableHtml += '</tbody></table>';
        transactionTable.innerHTML = tableHtml;
    } else {
        transactionTable.innerHTML = '<p class="text-muted text-center py-4">No transactions found for the selected period.</p>';
    }
}

function exportCurrentStatement() {
    if (!currentStatementData) {
        showError('Please generate a statement first.');
        return;
    }
    
    const formData = new FormData(document.getElementById('statementForm'));
    const params = new URLSearchParams(formData);
    params.append('export', 'csv');
    
    window.open(`<?= appUrl('statement/generate') ?>?${params}`, '_blank');
}

function refreshStatements() {
    if (currentStatementData) {
        generateStatement();
    }
}

// Utility functions
function showLoadingState() {
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('statementResults').style.display = 'none';
    document.getElementById('loadingState').style.display = 'block';
}

function hideLoadingState() {
    document.getElementById('loadingState').style.display = 'none';
}

function showStatementResults() {
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('statementResults').style.display = 'block';
}

function showError(message) {
    hideLoadingState();
    
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.row').parentNode;
    container.insertAdjacentHTML('afterbegin', alertHtml);
}

function formatMoney(amount) {
    if (typeof amount !== 'number') return amount;
    return 'GH₵' + amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
