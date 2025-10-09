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
    font-size: 1.3rem;
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
               <!-- <p class="text-muted mb-0 fs-5">Comprehensive analysis for <?= htmlspecialchars($data['month_name']) ?></p> -->
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
                <h2 class="kpi-value"><?= $data['kpis']['mtd_ca_formatted'] ?? 'GH₵0.00' ?></h2>
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
                <h2 class="kpi-value"><?= $data['kpis']['mtd_fi_formatted'] ?? 'GH₵0.00' ?></h2>
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
                <h2 class="kpi-value"><?= $data['kpis']['ytd_fi_formatted'] ?? 'GH₵0.00' ?></h2>
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
                        <small class="opacity-75">Gross Margin %</small>
                    </div>
                    <i class="bi bi-speedometer2 fs-3 opacity-75"></i>
                </div>
                <h2 class="kpi-value"><?= round($data['kpis']['efficiency_ratio'] ?? 0, 1) ?>%</h2>
                <div class="kpi-change">
                    <i class="bi bi-lightning"></i>
                    <span id="profitability-display">Net Profit Margin: <?= round($data['kpis']['profitability_ratio'] ?? 0, 1) ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Performance Metrics -->
<div class="row mb-4">
  <div class="col-12">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-primary text-white d-flex align-items-center">
        <i class="bi bi-graph-up-arrow me-2"></i>
        <h5 class="mb-0">Performance Metrics</h5>
      </div>

      <div class="card-body">
        <!-- Responsive grid: 2 cols (xs), 3 cols (sm), 6 cols (lg) -->
        <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-3 text-center">

          <div class="col">
            <div class="metric-badge h-100">
              <div class="metric-label">Avg Transaction</div>
              <div class="metric-value" id="metric-avg-transaction"
                   aria-label="Average Transaction">
                <?= $data['performance_metrics']['avg_transaction_size'] ?? 'GH₵0' ?>
              </div>
            </div>
          </div>

          <div class="col">
            <div class="metric-badge metric--blue h-100">
              <div class="metric-label">Best Day</div>
              <div class="metric-value" id="metric-best-day"
                   aria-label="Best Day">
                <?= $data['performance_metrics']['best_day'] ?? 'GH₵0' ?>
              </div>
            </div>
          </div>

          <div class="col">
            <div class="metric-badge metric--orange h-100">
              <div class="metric-label">Consistency</div>
              <div class="metric-value" id="metric-consistency"
                   aria-label="Consistency Score">
                <?= $data['performance_metrics']['consistency_score'] ?? 0 ?>%
              </div>
            </div>
          </div>

          <div class="col">
            <div class="metric-badge metric--teal h-100">
              <div class="metric-label">Avg AG1 Rate</div>
              <div class="metric-value" id="metric-ag1-rate"
                   aria-label="Average AG1 Rate">
                <?= $data['performance_metrics']['avg_ag1_rate'] ?? 0 ?>%
              </div>
            </div>
          </div>

          <div class="col">
            <div class="metric-badge metric--purple h-100">
              <div class="metric-label">Avg AG2 Rate</div>
              <div class="metric-value" id="metric-ag2-rate"
                   aria-label="Average AG2 Rate">
                <?= $data['performance_metrics']['avg_ag2_rate'] ?? 0 ?>%
              </div>
            </div>
          </div>

          <div class="col">
            <div class="metric-badge metric--red h-100">
              <div class="metric-label">Total Trx</div>
              <div class="metric-value" id="metric-total-transactions"
                   aria-label="Total Transactions">
                <?= $data['performance_metrics']['total_transactions'] ?? 0 ?>
              </div>
            </div>
          </div>

        </div><!-- /row -->
      </div><!-- /card-body -->
    </div>
  </div>
</div>

<!-- Company Summary Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-building me-2"></i>
                    <h5 class="mb-0">Company Performance Summary</h5>
                </div>
                <small class="opacity-75">Current Month Totals</small>
            </div>
            
            <!-- Company Filters -->
            <div class="card-body border-bottom bg-light">
                <!-- Date Range Filters -->
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-2">
                        <label for="dateRangeType" class="form-label fw-bold">
                            <i class="bi bi-calendar-range me-1"></i>Date Range
                        </label>
                        <select class="form-select" id="dateRangeType" onchange="handleDateRangeChange()">
                            <option value="current_month">Current Month</option>
                            <option value="last_7_days">Last 7 Days</option>
                            <option value="last_30_days">Last 30 Days</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="last_6_months">Last 6 Months</option>
                            <option value="current_year">Current Year</option>
                            <option value="last_year">Last Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-2" id="customStartDate" style="display: none;">
                        <label for="startDate" class="form-label fw-bold">Start Date</label>
                        <input type="date" class="form-control" id="startDate" onchange="handleCustomDateChange()">
                    </div>
                    <div class="col-md-2" id="customEndDate" style="display: none;">
                        <label for="endDate" class="form-label fw-bold">End Date</label>
                        <input type="date" class="form-control" id="endDate" onchange="handleCustomDateChange()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-info">
                            <i class="bi bi-info-circle me-1"></i>Selected Period
                        </label>
                        <div class="form-control-plaintext fw-bold text-info" id="selectedPeriodDisplay">
                            Current Month
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" onclick="refreshCompanyData()" id="refreshDataBtn">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Data
                        </button>
                    </div>
                </div>
                
                <!-- Company Filters -->
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="companySearch" class="form-label fw-bold">
                            <i class="bi bi-search me-1"></i>Search Company
                        </label>
                        <input type="text" class="form-control" id="companySearch" placeholder="Type company name..." onkeyup="filterCompanies()">
                    </div>
                    <div class="col-md-2">
                        <label for="sortBy" class="form-label fw-bold">
                            <i class="bi bi-sort-down me-1"></i>Sort By
                        </label>
                        <select class="form-select" id="sortBy" onchange="sortCompanies()">
                            <option value="fi_desc">FI (High to Low)</option>
                            <option value="fi_asc">FI (Low to High)</option>
                            <option value="ca_desc">CA (High to Low)</option>
                            <option value="ca_asc">CA (Low to High)</option>
                            <option value="trx_desc">Transactions (High to Low)</option>
                            <option value="trx_asc">Transactions (Low to High)</option>
                            <option value="name_asc">Company Name (A-Z)</option>
                            <option value="name_desc">Company Name (Z-A)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="minTransactions" class="form-label fw-bold">
                            <i class="bi bi-funnel me-1"></i>Min Transactions
                        </label>
                        <input type="number" class="form-control" id="minTransactions" placeholder="0" min="0" onchange="filterCompanies()">
                    </div>
                    <div class="col-md-2">
                        <label for="minFI" class="form-label fw-bold">Min FI Amount</label>
                        <input type="number" class="form-control" id="minFI" placeholder="0" min="0" step="0.01" onchange="filterCompanies()">
                    </div>
                    <div class="col-md-2">
                        <label for="showZeroTrx" class="form-label fw-bold">Display Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showZeroTrx" checked onchange="filterCompanies()">
                            <label class="form-check-label" for="showZeroTrx">
                                Show Zero Transactions
                            </label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-outline-secondary btn-sm w-100" onclick="resetFilters()" title="Reset Filters">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Filter Summary -->
                <div class="mt-2">
                    <small class="text-muted">
                        <span id="filterSummary">Showing all companies</span>
                        <span class="ms-3" id="companyCount"></span>
                    </small>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($data['company_summary'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Company</th>
                                <th class="text-center">Trx</th>
                                <th class="text-end">CA</th>
                                <th class="text-end">AG1</th>
                                <th class="text-end">AV1</th>
                                <th class="text-end">AG2</th>
                                <th class="text-end">AV2</th>
                                <th class="text-end">GA</th>
                                <th class="text-end">RE</th>
                                <th class="text-end">JE</th>
                                <th class="text-end">FI</th>
                                <th class="text-end">GAI GA</th>
                            </tr>
                        </thead>
                        <tbody id="companyTableBody">
                            <?php foreach ($data['company_summary'] as $company): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong class="text-primary"><?= htmlspecialchars($company['name']) ?></strong>
                                        <?php if (!empty($company['description'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($company['description']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary rounded-pill"><?= $company['transaction_count'] ?></span>
                                </td>
                                <td class="text-end">
                                    <strong class="text-primary"><?= $company['total_ca_formatted'] ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="text-info"><?= Money::format($company['total_ag1']) ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="text-secondary"><?= Money::format($company['total_av1']) ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="text-info"><?= Money::format($company['total_ag2']) ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="text-secondary"><?= Money::format($company['total_av2']) ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="text-warning"><?= $company['total_ga_formatted'] ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="text-dark"><?= Money::format($company['total_re']) ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="text-danger"><?= Money::format($company['total_je']) ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="text-success"><?= $company['total_fi_formatted'] ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="text-purple"><?= Money::format($company['total_gai_ga']) ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td>TOTALS</td>
                                <td class="text-center">
                                    <?php 
                                    $totalTransactions = array_sum(array_column($data['company_summary'], 'transaction_count'));
                                    echo $totalTransactions;
                                    ?>
                                </td>
                                <td class="text-end text-primary">
                                    <?php 
                                    $totalCA = array_sum(array_column($data['company_summary'], 'total_ca'));
                                    echo Money::format($totalCA);
                                    ?>
                                </td>
                                <td class="text-end text-info">
                                    <?php 
                                    $totalAG1 = array_sum(array_column($data['company_summary'], 'total_ag1'));
                                    echo Money::format($totalAG1);
                                    ?>
                                </td>
                                <td class="text-end text-secondary">
                                    <?php 
                                    $totalAV1 = array_sum(array_column($data['company_summary'], 'total_av1'));
                                    echo Money::format($totalAV1);
                                    ?>
                                </td>
                                <td class="text-end text-info">
                                    <?php 
                                    $totalAG2 = array_sum(array_column($data['company_summary'], 'total_ag2'));
                                    echo Money::format($totalAG2);
                                    ?>
                                </td>
                                <td class="text-end text-secondary">
                                    <?php 
                                    $totalAV2 = array_sum(array_column($data['company_summary'], 'total_av2'));
                                    echo Money::format($totalAV2);
                                    ?>
                                </td>
                                <td class="text-end text-warning">
                                    <?php 
                                    $totalGA = array_sum(array_column($data['company_summary'], 'total_ga'));
                                    echo Money::format($totalGA);
                                    ?>
                                </td>
                                <td class="text-end text-dark">
                                    <?php 
                                    $totalRE = array_sum(array_column($data['company_summary'], 'total_re'));
                                    echo Money::format($totalRE);
                                    ?>
                                </td>
                                <td class="text-end text-danger">
                                    <?php 
                                    $totalJE = array_sum(array_column($data['company_summary'], 'total_je'));
                                    echo Money::format($totalJE);
                                    ?>
                                </td>
                                <td class="text-end text-success">
                                    <?php 
                                    $totalFI = array_sum(array_column($data['company_summary'], 'total_fi'));
                                    echo Money::format($totalFI);
                                    ?>
                                </td>
                                <td class="text-end text-purple">
                                    <?php 
                                    $totalGAIGA = array_sum(array_column($data['company_summary'], 'total_gai_ga'));
                                    echo Money::format($totalGAIGA);
                                    ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <!-- Company Performance Insights -->
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h6 class="text-success">🏆 Top Performer</h6>
                                <?php 
                                $topCompany = !empty($data['company_summary']) ? $data['company_summary'][0] : null;
                                if ($topCompany && $topCompany['total_fi'] > 0): 
                                ?>
                                    <strong><?= htmlspecialchars($topCompany['name']) ?></strong><br>
                                    <small class="text-success"><?= $topCompany['total_fi_formatted'] ?> FI</small>
                                <?php else: ?>
                                    <small class="text-muted">No data available</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h6 class="text-info">📊 Most Active</h6>
                                <?php 
                                $mostActive = null;
                                $maxTransactions = 0;
                                foreach ($data['company_summary'] as $company) {
                                    if ($company['transaction_count'] > $maxTransactions) {
                                        $maxTransactions = $company['transaction_count'];
                                        $mostActive = $company;
                                    }
                                }
                                if ($mostActive && $mostActive['transaction_count'] > 0): 
                                ?>
                                    <strong><?= htmlspecialchars($mostActive['name']) ?></strong><br>
                                    <small class="text-info"><?= $mostActive['transaction_count'] ?> transactions</small>
                                <?php else: ?>
                                    <small class="text-muted">No data available</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h6 class="text-warning">💰 Highest CA</h6>
                                <?php 
                                $highestCA = null;
                                $maxCA = 0;
                                foreach ($data['company_summary'] as $company) {
                                    if ($company['total_ca'] > $maxCA && $company['transaction_count'] > 0) {
                                        $maxCA = $company['total_ca'];
                                        $highestCA = $company;
                                    }
                                }
                                if ($highestCA): 
                                ?>
                                    <strong><?= htmlspecialchars($highestCA['name']) ?></strong><br>
                                    <small class="text-warning"><?= $highestCA['total_ca_formatted'] ?> CA</small>
                                <?php else: ?>
                                    <small class="text-muted">No data available</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-building fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No company data available for the selected period</p>
                    <small class="text-muted">Add transactions with company assignments to see company performance</small>
                </div>
                <?php endif; ?>
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
                                    return 'GH₵' + value.toLocaleString();
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
                                    return 'GH₵' + value.toLocaleString();
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
                                    return 'GH₵' + value.toLocaleString();
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
                if (kpiElements.efficiency_ratio && kpis.efficiency_ratio !== undefined) {
                    kpiElements.efficiency_ratio.textContent = kpis.efficiency_ratio + '%';
                }
                
                // Update YTD transaction count
                const ytdTransactionElement = document.getElementById('ytd-transaction-count');
                if (ytdTransactionElement && kpis.ytd_transaction_count !== undefined) {
                    ytdTransactionElement.textContent = kpis.ytd_transaction_count + ' transactions';
                }
                
                // Update profitability display
                const profitabilityElement = document.getElementById('profitability-display');
                if (profitabilityElement && kpis.profitability_ratio !== undefined) {
                    profitabilityElement.textContent = 'Net Profit Margin: ' + Math.round(kpis.profitability_ratio * 10) / 10 + '%';
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

// Company filtering and sorting functionality
let originalCompanyData = [];
let filteredCompanyData = [];
let currentDateRange = {
    type: 'current_month',
    startDate: null,
    endDate: null
};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize date range
    initializeDateRange();
    // Store original company data
    const companyRows = document.querySelectorAll('#companyTableBody tr');
    originalCompanyData = Array.from(companyRows).map(row => {
        const cells = row.querySelectorAll('td');
        return {
            element: row,
            name: cells[0]?.textContent.trim().toLowerCase() || '',
            transactions: parseInt(cells[1]?.textContent.trim()) || 0,
            ca: parseFloat(cells[2]?.textContent.replace(/[^\d.-]/g, '')) || 0,
            ag1: parseFloat(cells[3]?.textContent.replace(/[^\d.-]/g, '')) || 0,
            av1: parseFloat(cells[4]?.textContent.replace(/[^\d.-]/g, '')) || 0,
            ag2: parseFloat(cells[5]?.textContent.replace(/[^\d.-]/g, '')) || 0,
            av2: parseFloat(cells[6]?.textContent.replace(/[^\d.-]/g, '')) || 0,
            ga: parseFloat(cells[7]?.textContent.replace(/[^\d.-]/g, '')) || 0,
            gai_ga: parseFloat(cells[8]?.textContent.replace(/[^\d.-]/g, '')) || 0,
            re: parseFloat(cells[9]?.textContent.replace(/[^\d.-]/g, '')) || 0,
            je: parseFloat(cells[10]?.textContent.replace(/[^\d.-]/g, '')) || 0,
            fi: parseFloat(cells[11]?.textContent.replace(/[^\d.-]/g, '')) || 0
        };
    });
    
    filteredCompanyData = [...originalCompanyData];
    updateCompanyCount();
});

function filterCompanies() {
    const searchTerm = document.getElementById('companySearch').value.toLowerCase();
    const minTransactions = parseInt(document.getElementById('minTransactions').value) || 0;
    const minFI = parseFloat(document.getElementById('minFI').value) || 0;
    const showZeroTrx = document.getElementById('showZeroTrx').checked;
    
    filteredCompanyData = originalCompanyData.filter(company => {
        // Search filter
        const matchesSearch = company.name.includes(searchTerm);
        
        // Transaction filter
        const meetsMinTransactions = company.transactions >= minTransactions;
        
        // FI filter
        const meetsMinFI = company.fi >= minFI;
        
        // Zero transaction filter
        const showZero = showZeroTrx || company.transactions > 0;
        
        return matchesSearch && meetsMinTransactions && meetsMinFI && showZero;
    });
    
    applyCurrentSort();
    renderFilteredCompanies();
    updateFilterSummary();
    updateCompanyCount();
}

function sortCompanies() {
    applyCurrentSort();
    renderFilteredCompanies();
}

function applyCurrentSort() {
    const sortBy = document.getElementById('sortBy').value;
    
    filteredCompanyData.sort((a, b) => {
        switch (sortBy) {
            case 'fi_desc':
                return b.fi - a.fi;
            case 'fi_asc':
                return a.fi - b.fi;
            case 'ca_desc':
                return b.ca - a.ca;
            case 'ca_asc':
                return a.ca - b.ca;
            case 'trx_desc':
                return b.transactions - a.transactions;
            case 'trx_asc':
                return a.transactions - b.transactions;
            case 'name_asc':
                return a.name.localeCompare(b.name);
            case 'name_desc':
                return b.name.localeCompare(a.name);
            default:
                return b.fi - a.fi;
        }
    });
}

function renderFilteredCompanies() {
    const tbody = document.getElementById('companyTableBody');
    if (!tbody) return;
    
    // Clear current rows
    tbody.innerHTML = '';
    
    // Add filtered rows
    filteredCompanyData.forEach(company => {
        tbody.appendChild(company.element);
    });
    
    // Update totals
    updateTotalsRow();
}

function updateTotalsRow() {
    const totalsRow = document.querySelector('tfoot tr');
    if (!totalsRow || filteredCompanyData.length === 0) return;
    
    const totals = filteredCompanyData.reduce((acc, company) => {
        acc.transactions += company.transactions;
        acc.ca += company.ca;
        acc.ag1 += company.ag1;
        acc.av1 += company.av1;
        acc.ag2 += company.ag2;
        acc.av2 += company.av2;
        acc.ga += company.ga;
        acc.gai_ga += company.gai_ga;
        acc.re += company.re;
        acc.je += company.je;
        acc.fi += company.fi;
        return acc;
    }, {
        transactions: 0, ca: 0, ag1: 0, av1: 0, ag2: 0, 
        av2: 0, ga: 0, gai_ga: 0, re: 0, je: 0, fi: 0
    });
    
    const cells = totalsRow.querySelectorAll('td');
    if (cells.length >= 12) {
        cells[1].textContent = totals.transactions;
        cells[2].textContent = formatCurrency(totals.ca);
        cells[3].textContent = formatCurrency(totals.ag1);
        cells[4].textContent = formatCurrency(totals.av1);
        cells[5].textContent = formatCurrency(totals.ag2);
        cells[6].textContent = formatCurrency(totals.av2);
        cells[7].textContent = formatCurrency(totals.ga);
        cells[8].textContent = formatCurrency(totals.gai_ga);
        cells[9].textContent = formatCurrency(totals.re);
        cells[10].textContent = formatCurrency(totals.je);
        cells[11].textContent = formatCurrency(totals.fi);
    }
}

function formatCurrency(amount) {
    return 'GH₵' + amount.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function updateFilterSummary() {
    const summary = document.getElementById('filterSummary');
    const searchTerm = document.getElementById('companySearch').value;
    const minTransactions = document.getElementById('minTransactions').value;
    const minFI = document.getElementById('minFI').value;
    const showZeroTrx = document.getElementById('showZeroTrx').checked;
    
    let summaryText = 'Showing ';
    const filters = [];
    
    if (searchTerm) filters.push(`search: "${searchTerm}"`);
    if (minTransactions) filters.push(`min transactions: ${minTransactions}`);
    if (minFI) filters.push(`min FI: GH₵${minFI}`);
    if (!showZeroTrx) filters.push('excluding zero transactions');
    
    if (filters.length > 0) {
        summaryText += `filtered companies (${filters.join(', ')})`;
    } else {
        summaryText += 'all companies';
    }
    
    summary.textContent = summaryText;
}

function updateCompanyCount() {
    const countElement = document.getElementById('companyCount');
    const total = originalCompanyData.length;
    const filtered = filteredCompanyData.length;
    
    if (filtered === total) {
        countElement.textContent = `${total} companies`;
    } else {
        countElement.textContent = `${filtered} of ${total} companies`;
    }
}

function resetFilters() {
    document.getElementById('companySearch').value = '';
    document.getElementById('minTransactions').value = '';
    document.getElementById('minFI').value = '';
    document.getElementById('showZeroTrx').checked = true;
    document.getElementById('sortBy').value = 'fi_desc';
    
    filteredCompanyData = [...originalCompanyData];
    applyCurrentSort();
    renderFilteredCompanies();
    updateFilterSummary();
    updateCompanyCount();
}

// Date filtering functions
function initializeDateRange() {
    const today = new Date();
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    
    currentDateRange = {
        type: 'current_month',
        startDate: startOfMonth,
        endDate: endOfMonth
    };
    
    updateSelectedPeriodDisplay();
}

function handleDateRangeChange() {
    const rangeType = document.getElementById('dateRangeType').value;
    const customStartDiv = document.getElementById('customStartDate');
    const customEndDiv = document.getElementById('customEndDate');
    
    if (rangeType === 'custom') {
        customStartDiv.style.display = 'block';
        customEndDiv.style.display = 'block';
        
        // Set default values for custom range
        const today = new Date();
        const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        
        document.getElementById('startDate').value = formatDateForInput(lastMonth);
        document.getElementById('endDate').value = formatDateForInput(today);
        
        handleCustomDateChange();
    } else {
        customStartDiv.style.display = 'none';
        customEndDiv.style.display = 'none';
        
        calculateDateRange(rangeType);
    }
}

function handleCustomDateChange() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (startDate && endDate) {
        currentDateRange = {
            type: 'custom',
            startDate: new Date(startDate),
            endDate: new Date(endDate)
        };
        
        updateSelectedPeriodDisplay();
    }
}

function calculateDateRange(rangeType) {
    const today = new Date();
    let startDate, endDate;
    
    switch (rangeType) {
        case 'current_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
            
        case 'last_7_days':
            startDate = new Date(today);
            startDate.setDate(today.getDate() - 7);
            endDate = new Date(today);
            break;
            
        case 'last_30_days':
            startDate = new Date(today);
            startDate.setDate(today.getDate() - 30);
            endDate = new Date(today);
            break;
            
        case 'last_3_months':
            startDate = new Date(today.getFullYear(), today.getMonth() - 3, 1);
            endDate = new Date(today);
            break;
            
        case 'last_6_months':
            startDate = new Date(today.getFullYear(), today.getMonth() - 6, 1);
            endDate = new Date(today);
            break;
            
        case 'current_year':
            startDate = new Date(today.getFullYear(), 0, 1);
            endDate = new Date(today.getFullYear(), 11, 31);
            break;
            
        case 'last_year':
            startDate = new Date(today.getFullYear() - 1, 0, 1);
            endDate = new Date(today.getFullYear() - 1, 11, 31);
            break;
            
        default:
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    }
    
    currentDateRange = {
        type: rangeType,
        startDate: startDate,
        endDate: endDate
    };
    
    updateSelectedPeriodDisplay();
}

function updateSelectedPeriodDisplay() {
    const display = document.getElementById('selectedPeriodDisplay');
    const { type, startDate, endDate } = currentDateRange;
    
    let displayText = '';
    
    switch (type) {
        case 'current_month':
            displayText = `Current Month (${formatDateDisplay(startDate)} - ${formatDateDisplay(endDate)})`;
            break;
        case 'last_7_days':
            displayText = `Last 7 Days (${formatDateDisplay(startDate)} - ${formatDateDisplay(endDate)})`;
            break;
        case 'last_30_days':
            displayText = `Last 30 Days (${formatDateDisplay(startDate)} - ${formatDateDisplay(endDate)})`;
            break;
        case 'last_3_months':
            displayText = `Last 3 Months (${formatDateDisplay(startDate)} - ${formatDateDisplay(endDate)})`;
            break;
        case 'last_6_months':
            displayText = `Last 6 Months (${formatDateDisplay(startDate)} - ${formatDateDisplay(endDate)})`;
            break;
        case 'current_year':
            displayText = `Current Year (${startDate.getFullYear()})`;
            break;
        case 'last_year':
            displayText = `Last Year (${startDate.getFullYear()})`;
            break;
        case 'custom':
            displayText = `Custom Range (${formatDateDisplay(startDate)} - ${formatDateDisplay(endDate)})`;
            break;
        default:
            displayText = 'Current Month';
    }
    
    display.textContent = displayText;
}

function formatDateForInput(date) {
    return date.toISOString().split('T')[0];
}

function formatDateDisplay(date) {
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric',
        year: 'numeric'
    });
}

function refreshCompanyData() {
    const refreshBtn = document.getElementById('refreshDataBtn');
    const originalText = refreshBtn.innerHTML;
    
    // Show loading state
    refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin me-1"></i>Loading...';
    refreshBtn.disabled = true;
    
    // Calculate date parameters based on current range
    const { startDate, endDate } = currentDateRange;
    
    // For now, we'll use the existing month/year system
    // In a full implementation, you'd need to modify the backend to accept date ranges
    const year = startDate.getFullYear();
    const month = startDate.getMonth() + 1;
    
    // Simulate API call - in reality, you'd fetch new data from the server
    setTimeout(() => {
        // Update the dashboard with new date range
        const currentYear = document.getElementById('yearSelector').value;
        const currentMonth = document.getElementById('monthSelector').value;
        
        // Set the selectors to match our date range
        document.getElementById('yearSelector').value = year;
        document.getElementById('monthSelector').value = month;
        
        // Trigger dashboard refresh
        refreshDashboard();
        
        // Reset button state
        refreshBtn.innerHTML = originalText;
        refreshBtn.disabled = false;
        
        // Update header to show new date range
        updateHeaderWithDateRange();
        
    }, 1000);
}

function updateHeaderWithDateRange() {
    const header = document.querySelector('.card-header small');
    const { type, startDate, endDate } = currentDateRange;
    
    let headerText = '';
    
    switch (type) {
        case 'current_month':
            headerText = 'Current Month Totals';
            break;
        case 'last_7_days':
            headerText = 'Last 7 Days Totals';
            break;
        case 'last_30_days':
            headerText = 'Last 30 Days Totals';
            break;
        case 'last_3_months':
            headerText = 'Last 3 Months Totals';
            break;
        case 'last_6_months':
            headerText = 'Last 6 Months Totals';
            break;
        case 'current_year':
            headerText = 'Current Year Totals';
            break;
        case 'last_year':
            headerText = 'Last Year Totals';
            break;
        case 'custom':
            headerText = `Custom Range Totals (${formatDateDisplay(startDate)} - ${formatDateDisplay(endDate)})`;
            break;
        default:
            headerText = 'Current Month Totals';
    }
    
    if (header) {
        header.textContent = headerText;
    }
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
