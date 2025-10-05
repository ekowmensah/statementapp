<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}

$consolidatedData = $data['consolidated_data'];
$summaryStats = $data['summary_stats'];
$trendAnalysis = $data['trend_analysis'];
$metrics = $data['metrics'];
?>

<style>
.metric-card {
    transition: transform 0.2s ease-in-out;
    border-left: 4px solid;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.trend-icon {
    font-size: 1.2em;
}

.trend-up { color: #198754; }
.trend-down { color: #dc3545; }
.trend-stable { color: #6c757d; }

.chart-container {
    position: relative;
    height: 400px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .metric-card {
        margin-bottom: 1rem;
    }
    
    .chart-container {
        height: 300px;
    }
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Consolidated Financial Reports</h2>
                <p class="text-muted mb-0">
                    Comprehensive analysis of all financial metrics
                    <?php if (!empty($data['company_id'])): ?>
                        <?php 
                        $selectedCompany = null;
                        foreach ($data['companies'] as $company) {
                            if ($company['id'] == $data['company_id']) {
                                $selectedCompany = $company;
                                break;
                            }
                        }
                        ?>
                        <?php if ($selectedCompany): ?>
                            • <span class="badge bg-primary"><?= htmlspecialchars($selectedCompany['name']) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        • <span class="badge bg-info">All Companies</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= appUrl('reports') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Reports
                </a>
                <button class="btn btn-outline-primary" onclick="showExportModal()">
                    <i class="bi bi-download me-2"></i>Export Report
                </button>
                <button class="btn btn-outline-secondary" onclick="printReport()">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Report Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= appUrl('reports/consolidated') ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?= htmlspecialchars($data['start_date']) ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?= htmlspecialchars($data['end_date']) ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="company_id" class="form-label">Company</label>
                        <select class="form-select" id="company_id" name="company_id">
                            <option value="" <?= empty($data['company_id']) ? 'selected' : '' ?>>All Companies</option>
                            <?php foreach ($data['companies'] as $company): ?>
                                <option value="<?= $company['id'] ?>" <?= $data['company_id'] == $company['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($company['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="group_by" class="form-label">Group By</label>
                        <select class="form-select" id="group_by" name="group_by">
                            <option value="month" <?= $data['group_by'] == 'month' ? 'selected' : '' ?>>Monthly</option>
                            <option value="quarter" <?= $data['group_by'] == 'quarter' ? 'selected' : '' ?>>Quarterly</option>
                            <option value="year" <?= $data['group_by'] == 'year' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Advanced Range Filters -->
                <div class="mt-3 d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="toggleRangeFilters()" id="rangeFilterToggle">
                        <i class="bi bi-sliders me-2"></i>Advanced Range Filters
                    </button>
                    
                    <?php 
                    $hasRangeFilters = false;
                    foreach ($data['range_filters'] as $filter => $value) {
                        if (!empty($value)) {
                            $hasRangeFilters = true;
                            break;
                        }
                    }
                    ?>
                    
                    <?php if ($hasRangeFilters): ?>
                        <span class="badge bg-success">
                            <i class="bi bi-funnel me-1"></i>Range Filters Active
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="mt-3" id="rangeFilters" style="display: none;">
                    <div class="card card-body">
                        <form method="GET" action="<?= appUrl('reports/consolidated') ?>" class="row g-2">
                            <!-- Preserve existing filters -->
                            <input type="hidden" name="start_date" value="<?= htmlspecialchars($data['start_date']) ?>">
                            <input type="hidden" name="end_date" value="<?= htmlspecialchars($data['end_date']) ?>">
                            <input type="hidden" name="company_id" value="<?= htmlspecialchars($data['company_id']) ?>">
                            <input type="hidden" name="group_by" value="<?= htmlspecialchars($data['group_by']) ?>">
                            
                            <div class="col-12">
                                <h6 class="text-muted mb-3">Filter by Value Ranges</h6>
                            </div>
                            
                            <?php foreach ($metrics as $key => $metric): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <div class="card border-0 bg-light">
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-2" style="color: <?= $metric['color'] ?>;">
                                            <?= $metric['name'] ?>
                                        </h6>
                                        <div class="row g-1">
                                            <div class="col-6">
                                                <input type="number" class="form-control form-control-sm" 
                                                       name="<?= $key ?>_min" 
                                                       placeholder="Min" 
                                                       step="0.01"
                                                       value="<?= htmlspecialchars($data['range_filters']["{$key}_min"] ?? '') ?>">
                                            </div>
                                            <div class="col-6">
                                                <input type="number" class="form-control form-control-sm" 
                                                       name="<?= $key ?>_max" 
                                                       placeholder="Max" 
                                                       step="0.01"
                                                       value="<?= htmlspecialchars($data['range_filters']["{$key}_max"] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="col-12 mt-3">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-funnel me-2"></i>Apply Range Filters
                                    </button>
                                    <a href="<?= appUrl('reports/consolidated') ?>?start_date=<?= $data['start_date'] ?>&end_date=<?= $data['end_date'] ?>&company_id=<?= $data['company_id'] ?>&group_by=<?= $data['group_by'] ?>" 
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-x-circle me-2"></i>Clear Filters
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Executive Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-primary"><?= number_format($summaryStats['total_transactions']) ?></h3>
                            <p class="text-muted mb-0">Total Transactions</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-success"><?= Money::format($summaryStats['grand_total_fi']) ?></h3>
                            <p class="text-muted mb-0">Total Final Income</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-info"><?= Money::format($summaryStats['overall_avg_fi']) ?></h3>
                            <p class="text-muted mb-0">Average Final Income</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-warning"><?= number_format($summaryStats['companies_involved']) ?></h3>
                            <p class="text-muted mb-0">Companies Involved</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Individual Metric Cards -->
<div class="row mb-4">
    <?php foreach ($metrics as $key => $metric): ?>
        <?php 
        $trend = $trendAnalysis['trends'][$key] ?? null;
        $trendIcon = '';
        $trendClass = '';
        if ($trend) {
            switch ($trend['direction']) {
                case 'up':
                    $trendIcon = 'bi-trending-up';
                    $trendClass = 'trend-up';
                    break;
                case 'down':
                    $trendIcon = 'bi-trending-down';
                    $trendClass = 'trend-down';
                    break;
                default:
                    $trendIcon = 'bi-dash';
                    $trendClass = 'trend-stable';
            }
        }
        ?>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card metric-card h-100" style="border-left-color: <?= $metric['color'] ?>;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title mb-0"><?= $metric['name'] ?></h6>
                        <?php if ($trend): ?>
                            <i class="bi <?= $trendIcon ?> trend-icon <?= $trendClass ?>"></i>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-2">
                                <small class="text-muted">Total</small>
                                <div class="fw-bold"><?= Money::format($summaryStats["grand_total_{$key}"] ?? 0) ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-2">
                                <small class="text-muted">Average</small>
                                <div class="fw-bold"><?= Money::format($summaryStats["overall_avg_{$key}"] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($trend): ?>
                    <div class="mt-2">
                        <small class="text-muted">Growth: </small>
                        <span class="<?= $trendClass ?> fw-bold">
                            <?= number_format($trend['growth_rate'], 1) ?>%
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Detailed Data Table -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>Detailed Breakdown</h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" onclick="toggleView('table')">
                        <i class="bi bi-table"></i> Table
                    </button>
                    <button class="btn btn-outline-secondary" onclick="toggleView('chart')">
                        <i class="bi bi-bar-chart"></i> Chart
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Table View -->
                <div id="tableView" class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th class="text-end">Transactions</th>
                                <th class="text-end">CA</th>
                                <th class="text-end">AG1</th>
                                <th class="text-end">AV1</th>
                                <th class="text-end">AG2</th>
                                <th class="text-end">AV2</th>
                                <th class="text-end">GA</th>
                                <th class="text-end">RE</th>
                                <th class="text-end">JE</th>
                                <th class="text-end">FI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consolidatedData as $row): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($row['period']) ?></td>
                                <td class="text-end"><?= number_format($row['transaction_count']) ?></td>
                                <td class="text-end"><?= Money::format($row['total_ca']) ?></td>
                                <td class="text-end"><?= Money::format($row['total_ag1']) ?></td>
                                <td class="text-end"><?= Money::format($row['total_av1']) ?></td>
                                <td class="text-end"><?= Money::format($row['total_ag2']) ?></td>
                                <td class="text-end"><?= Money::format($row['total_av2']) ?></td>
                                <td class="text-end"><?= Money::format($row['total_ga']) ?></td>
                                <td class="text-end"><?= Money::format($row['total_re']) ?></td>
                                <td class="text-end"><?= Money::format($row['total_je']) ?></td>
                                <td class="text-end fw-bold text-success"><?= Money::format($row['total_fi']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Chart View -->
                <div id="chartView" class="p-3" style="display: none;">
                    <div class="chart-container">
                        <canvas id="consolidatedChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Insights -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Performance Insights</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Best Performing Metrics</h6>
                        <?php 
                        $bestMetrics = [];
                        foreach ($trendAnalysis['growth_rates'] ?? [] as $metric => $rate) {
                            if ($rate > 0) {
                                $bestMetrics[$metric] = $rate;
                            }
                        }
                        arsort($bestMetrics);
                        ?>
                        <?php if (!empty($bestMetrics)): ?>
                            <?php foreach (array_slice($bestMetrics, 0, 3, true) as $metric => $rate): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?= $metrics[$metric]['name'] ?></span>
                                    <span class="badge bg-success">+<?= number_format($rate, 1) ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No positive growth metrics in selected period.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Areas for Improvement</h6>
                        <?php 
                        $worstMetrics = [];
                        foreach ($trendAnalysis['growth_rates'] ?? [] as $metric => $rate) {
                            if ($rate < 0) {
                                $worstMetrics[$metric] = $rate;
                            }
                        }
                        asort($worstMetrics);
                        ?>
                        <?php if (!empty($worstMetrics)): ?>
                            <?php foreach (array_slice($worstMetrics, 0, 3, true) as $metric => $rate): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span><?= $metrics[$metric]['name'] ?></span>
                                    <span class="badge bg-danger"><?= number_format($rate, 1) ?>%</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">All metrics showing positive or stable trends.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Professional Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-download me-2"></i>Export Consolidated Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closeExportModal()"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" method="GET" action="<?= appUrl('reports/export-consolidated') ?>">
                    <!-- Preserve current filters -->
                    <input type="hidden" name="start_date" value="<?= htmlspecialchars($data['start_date']) ?>">
                    <input type="hidden" name="end_date" value="<?= htmlspecialchars($data['end_date']) ?>">
                    <input type="hidden" name="company_id" value="<?= htmlspecialchars($data['company_id']) ?>">
                    <input type="hidden" name="group_by" value="<?= htmlspecialchars($data['group_by']) ?>">
                    
                    <!-- Preserve range filters -->
                    <?php foreach ($data['range_filters'] as $key => $value): ?>
                        <?php if (!empty($value)): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <div class="row">
                        <!-- Export Format Selection -->
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Export Format</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="format" id="formatPDF" value="pdf" checked>
                                        <label class="form-check-label" for="formatPDF">
                                            <i class="bi bi-filetype-pdf text-danger me-2"></i>
                                            <strong>PDF Report</strong>
                                            <small class="d-block text-muted">Professional formatted document, ideal for presentations</small>
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="format" id="formatExcel" value="excel">
                                        <label class="form-check-label" for="formatExcel">
                                            <i class="bi bi-filetype-xlsx text-success me-2"></i>
                                            <strong>Excel Spreadsheet</strong>
                                            <small class="d-block text-muted">Editable format for further analysis and calculations</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" id="formatCSV" value="csv">
                                        <label class="form-check-label" for="formatCSV">
                                            <i class="bi bi-filetype-csv text-info me-2"></i>
                                            <strong>CSV Data</strong>
                                            <small class="d-block text-muted">Raw data format, compatible with all spreadsheet applications</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Export Options -->
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="bi bi-gear me-2"></i>Export Options</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="include_summary" id="includeSummary" checked>
                                        <label class="form-check-label" for="includeSummary">
                                            <strong>Executive Summary</strong>
                                            <small class="d-block text-muted">Key performance indicators and totals</small>
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="include_details" id="includeDetails" checked>
                                        <label class="form-check-label" for="includeDetails">
                                            <strong>Detailed Breakdown</strong>
                                            <small class="d-block text-muted">Complete transaction data by period</small>
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="include_trends" id="includeTrends" checked>
                                        <label class="form-check-label" for="includeTrends">
                                            <strong>Trend Analysis</strong>
                                            <small class="d-block text-muted">Growth rates and performance insights</small>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="include_charts" id="includeCharts" checked>
                                        <label class="form-check-label" for="includeCharts">
                                            <strong>Charts & Graphs</strong>
                                            <small class="d-block text-muted">Visual representations (PDF only)</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Preview -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Export Preview</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Report Period:</small>
                                            <div class="fw-bold"><?= date('M j, Y', strtotime($data['start_date'])) ?> - <?= date('M j, Y', strtotime($data['end_date'])) ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Company Filter:</small>
                                            <div class="fw-bold">
                                                <?php if (!empty($data['company_id'])): ?>
                                                    <?php 
                                                    foreach ($data['companies'] as $company) {
                                                        if ($company['id'] == $data['company_id']) {
                                                            echo htmlspecialchars($company['name']);
                                                            break;
                                                        }
                                                    }
                                                    ?>
                                                <?php else: ?>
                                                    All Companies
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <small class="text-muted">Data Grouping:</small>
                                            <div class="fw-bold"><?= ucfirst($data['group_by']) ?>ly</div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Total Records:</small>
                                            <div class="fw-bold"><?= count($consolidatedData) ?> periods</div>
                                        </div>
                                    </div>
                                    
                                    <?php 
                                    $activeFilters = array_filter($data['range_filters']);
                                    if (!empty($activeFilters)): 
                                    ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Active Range Filters:</small>
                                        <div class="mt-1">
                                            <?php foreach ($activeFilters as $filter => $value): ?>
                                                <span class="badge bg-secondary me-1"><?= strtoupper(str_replace('_', ' ', $filter)) ?>: <?= $value ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExportModal()">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="executeExport()">
                    <i class="bi bi-download me-2"></i>Generate & Download
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let consolidatedChart = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeChart();
    
    // Show range filters if any are active
    <?php if ($hasRangeFilters): ?>
    toggleRangeFilters();
    <?php endif; ?>
});

function toggleView(view) {
    const tableView = document.getElementById('tableView');
    const chartView = document.getElementById('chartView');
    
    if (view === 'table') {
        tableView.style.display = 'block';
        chartView.style.display = 'none';
    } else {
        tableView.style.display = 'none';
        chartView.style.display = 'block';
        if (!consolidatedChart) {
            initializeChart();
        }
    }
}

function initializeChart() {
    const ctx = document.getElementById('consolidatedChart');
    if (!ctx) return;
    
    const data = <?= json_encode($consolidatedData) ?>;
    const metrics = <?= json_encode($metrics) ?>;
    
    const labels = data.map(row => row.period);
    const datasets = [];
    
    // Create datasets for key metrics
    const keyMetrics = ['ca', 'fi', 'ga', 'je'];
    keyMetrics.forEach(metric => {
        if (metrics[metric]) {
            datasets.push({
                label: metrics[metric].name,
                data: data.map(row => parseFloat(row[`total_${metric}`] || 0)),
                borderColor: metrics[metric].color,
                backgroundColor: metrics[metric].color + '20',
                tension: 0.4
            });
        }
    });
    
    consolidatedChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Financial Metrics Trend Analysis'
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'GH₵' + value.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

function showExportModal() {
    document.getElementById('exportModal').style.display = 'block';
    document.getElementById('exportModal').classList.add('show');
    document.body.classList.add('modal-open');
    
    // Create backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'exportModalBackdrop';
    document.body.appendChild(backdrop);
}

function closeExportModal() {
    document.getElementById('exportModal').style.display = 'none';
    document.getElementById('exportModal').classList.remove('show');
    document.body.classList.remove('modal-open');
    
    // Remove backdrop
    const backdrop = document.getElementById('exportModalBackdrop');
    if (backdrop) {
        backdrop.remove();
    }
}

function executeExport() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    
    // Show loading state
    const exportButton = document.querySelector('#exportModal .btn-primary');
    const originalText = exportButton.innerHTML;
    exportButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Generating...';
    exportButton.disabled = true;
    
    // Build URL with parameters
    const params = new URLSearchParams();
    for (let [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }
    
    // Add hidden inputs
    const hiddenInputs = form.querySelectorAll('input[type="hidden"]');
    hiddenInputs.forEach(input => {
        if (input.value) params.append(input.name, input.value);
    });
    
    const exportUrl = form.action + '?' + params.toString();
    
    // Create temporary link for download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Reset button after delay
    setTimeout(() => {
        exportButton.innerHTML = originalText;
        exportButton.disabled = false;
        closeExportModal();
    }, 2000);
}

function printReport() {
    window.print();
}

function toggleRangeFilters() {
    const rangeFilters = document.getElementById('rangeFilters');
    const toggleButton = document.getElementById('rangeFilterToggle');
    
    if (rangeFilters.style.display === 'none') {
        rangeFilters.style.display = 'block';
        toggleButton.innerHTML = '<i class="bi bi-sliders me-2"></i>Hide Range Filters';
        toggleButton.classList.remove('btn-outline-secondary');
        toggleButton.classList.add('btn-secondary');
    } else {
        rangeFilters.style.display = 'none';
        toggleButton.innerHTML = '<i class="bi bi-sliders me-2"></i>Advanced Range Filters';
        toggleButton.classList.remove('btn-secondary');
        toggleButton.classList.add('btn-outline-secondary');
    }
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
