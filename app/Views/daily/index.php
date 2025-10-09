<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}
?>

<style>
/* GAI GA Purple Color */
.text-purple { color: #8b5cf6; }

/* Mobile Responsiveness for Daily Transactions */
@media (max-width: 768px) {
    /* Stack header elements on mobile */
    .d-flex.justify-content-between {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .d-flex.gap-2 {
        margin-top: 1rem;
        width: 100%;
        justify-content: flex-start;
    }
    
    /* Mobile-friendly buttons */
    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* Mobile table improvements */
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .table th, .table td {
        padding: 0.5rem 0.25rem;
        vertical-align: middle;
    }
    
    .table-money {
        font-size: 0.8rem;
    }
    
    /* Mobile filter improvements */
    .card-body {
        padding: 1rem;
    }
    
    .row.g-3 > div {
        margin-bottom: 0.75rem;
    }
    
    /* Mobile pagination */
    .pagination {
        font-size: 0.875rem;
    }
    
    .page-link {
        padding: 0.375rem 0.75rem;
    }
}

@media (max-width: 576px) {
    h2 {
        font-size: 1.5rem;
    }
    
    .btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.6rem;
    }
    
    .table th, .table td {
        padding: 0.25rem 0.125rem;
        font-size: 0.75rem;
    }
    
    .table-money {
        font-size: 0.7rem;
    }
    
    /* Stack action buttons vertically on very small screens */
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .dropdown-menu {
        font-size: 0.875rem;
    }
    
    /* Compact pagination for mobile */
    .pagination {
        font-size: 0.75rem;
    }
    
    .page-link {
        padding: 0.25rem 0.5rem;
    }
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Daily Transactions</h2>
                <p class="text-muted mb-0">
                    <?= $data['date_range']['label'] ?>
                    <?php if ($data['is_locked']): ?>
                        <span class="locked-badge ms-2">
                            <i class="bi bi-lock me-1"></i>Locked
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($data['can_create'] && !$data['is_locked']): ?>
                <a href="<?= appUrl('daily/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add Transaction
                </a>
                <?php endif; ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-coreui-toggle="dropdown">
                        <i class="bi bi-three-dots me-2"></i>Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= appUrl('export/csv?month=' . $data['selected_month'] . '&year=' . $data['selected_year']) ?>">
                            <i class="bi bi-filetype-csv me-2"></i>Export CSV
                        </a></li>
                        <li><a class="dropdown-item" href="<?= appUrl('export/pdf?month=' . $data['selected_month'] . '&year=' . $data['selected_year']) ?>">
                            <i class="bi bi-filetype-pdf me-2"></i>Export PDF
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= appUrl('statement?month=' . $data['selected_month'] . '&year=' . $data['selected_year']) ?>">
                            <i class="bi bi-table me-2"></i>Statement View
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters & Search</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= appUrl('daily') ?>" class="row g-3" id="filterForm">
                    <!-- Filter Type -->
                    <div class="col-md-2">
                        <label for="filter" class="form-label">Period</label>
                        <select class="form-select" id="filter" name="filter">
                            <option value="day" <?= $data['filter_type'] == 'day' ? 'selected' : '' ?>>Today</option>
                            <option value="week" <?= $data['filter_type'] == 'week' ? 'selected' : '' ?>>This Week</option>
                            <option value="month" <?= $data['filter_type'] == 'month' ? 'selected' : '' ?>>Month</option>
                            <option value="year" <?= $data['filter_type'] == 'year' ? 'selected' : '' ?>>Year</option>
                        </select>
                    </div>
                    
                    <!-- Month (shown when filter is month) -->
                    <div class="col-md-2" id="monthFilter" style="<?= $data['filter_type'] != 'month' ? 'display: none;' : '' ?>">
                        <label for="month" class="form-label">Month</label>
                        <select class="form-select" id="month" name="month">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == $data['selected_month'] ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <!-- Year (shown when filter is month or year) -->
                    <div class="col-md-2" id="yearFilter" style="<?= !in_array($data['filter_type'], ['month', 'year']) ? 'display: none;' : '' ?>">
                        <label for="year" class="form-label">Year</label>
                        <select class="form-select" id="year" name="year">
                            <?php 
                            $minYear = $data['year_range']['min'] ?? (date('Y') - 2);
                            $maxYear = $data['year_range']['max'] ?? (date('Y') + 1);
                            for ($y = $minYear; $y <= $maxYear; $y++): 
                            ?>
                                <option value="<?= $y ?>" <?= $y == $data['selected_year'] ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <!-- Company Filter -->
                    <div class="col-md-2">
                        <label for="company_id" class="form-label">Company</label>
                        <select class="form-select" id="company_id" name="company_id">
                            <option value="">All Companies</option>
                            <?php foreach ($data['companies'] ?? [] as $company): ?>
                                <option value="<?= $company['id'] ?>" <?= $data['company_id'] == $company['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($company['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Search -->
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by date, note, or company..." value="<?= htmlspecialchars($data['search']) ?>">
                    </div>
                    
                    <!-- Per Page -->
                    <div class="col-md-2">
                        <label for="per_page" class="form-label">Per Page</label>
                        <select class="form-select" id="per_page" name="per_page">
                            <option value="10" <?= $data['per_page'] == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $data['per_page'] == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $data['per_page'] == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $data['per_page'] == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>
                    
                    <!-- Filter Button -->
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
                
                <!-- Quick Filter Buttons -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="<?= appUrl('daily?filter=day') ?>" class="btn btn-outline-secondary <?= $data['filter_type'] == 'day' ? 'active' : '' ?>">
                                <i class="bi bi-calendar-day me-1"></i>Today
                            </a>
                            <a href="<?= appUrl('daily?filter=week') ?>" class="btn btn-outline-secondary <?= $data['filter_type'] == 'week' ? 'active' : '' ?>">
                                <i class="bi bi-calendar-week me-1"></i>This Week
                            </a>
                            <a href="<?= appUrl('daily?filter=month&month=' . date('n') . '&year=' . date('Y')) ?>" class="btn btn-outline-secondary <?= $data['filter_type'] == 'month' ? 'active' : '' ?>">
                                <i class="bi bi-calendar-month me-1"></i>This Month
                            </a>
                            <a href="<?= appUrl('daily?filter=year&year=' . date('Y')) ?>" class="btn btn-outline-secondary <?= $data['filter_type'] == 'year' ? 'active' : '' ?>">
                                <i class="bi bi-calendar me-1"></i>This Year
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transactions Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <?php if (!empty($data['transactions'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-header">
                            <tr>
                                <th>Date</th>
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
                                <th>Company</th>
                                <th>Note</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $currentDate = null;
                            $dateTransactionCount = [];
                            
                            // Group transactions by date to show counts
                            foreach ($data['transactions'] as $txn) {
                                $date = $txn['txn_date'];
                                if (!isset($dateTransactionCount[$date])) {
                                    $dateTransactionCount[$date] = 0;
                                }
                                $dateTransactionCount[$date]++;
                            }
                            ?>
                            
                            <?php foreach ($data['transactions'] as $txn): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <a href="<?= appUrl('daily/show?id=' . $txn['id']) ?>" class="text-decoration-none fw-semibold">
                                                <?= date('M j, Y', strtotime($txn['txn_date'])) ?>
                                            </a>
                                            <br>
                                            <small class="text-muted"><?= date('l', strtotime($txn['txn_date'])) ?></small>
                                        </div>
                                        <?php if ($dateTransactionCount[$txn['txn_date']] > 1): ?>
                                        <div class="ms-2">
                                            <a href="<?= appUrl('daily/show-by-date?date=' . $txn['txn_date']) ?>" 
                                               class="badge bg-info text-decoration-none" 
                                               title="<?= $dateTransactionCount[$txn['txn_date']] ?> transactions on this date">
                                                <?= $dateTransactionCount[$txn['txn_date']] ?>x
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                        <?php if (isset($txn['sequence_number']) && $txn['sequence_number'] > 1): ?>
                                        <div class="ms-1">
                                            <small class="badge bg-secondary">#<?= $txn['sequence_number'] ?></small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-end table-money"><?= Money::format($txn['ca']) ?></td>
                                <td class="text-end table-money"><?= Money::format($txn['ag1']) ?></td>
                                <td class="text-end table-money"><?= Money::format($txn['av1']) ?></td>
                                <td class="text-end table-money"><?= Money::format($txn['ag2']) ?></td>
                                <td class="text-end table-money"><?= Money::format($txn['av2']) ?></td>
                                <td class="text-end table-money"><?= Money::format($txn['ga']) ?></td>
                                <td class="text-end table-money"><?= Money::format($txn['re']) ?></td>
                                <td class="text-end table-money"><?= Money::format($txn['je']) ?></td>
                                <td class="text-end table-money fw-bold text-success"><?= Money::format($txn['fi']) ?></td>
                                <td class="text-end table-money text-purple"><?= Money::format($txn['gai_ga'] ?? 0) ?></td>
                                <td>
                                    <?php if ($txn['company_name']): ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars($txn['company_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted">
                                    <?php if (!empty($txn['note'])): ?>
                                        <?= htmlspecialchars(substr($txn['note'], 0, 30)) ?>
                                        <?= strlen($txn['note']) > 30 ? '...' : '' ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= appUrl('daily/show?id=' . $txn['id']) ?>" class="btn btn-outline-info" title="View Transaction">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($dateTransactionCount[$txn['txn_date']] > 1): ?>
                                        <a href="<?= appUrl('daily/show-by-date?date=' . $txn['txn_date']) ?>" class="btn btn-outline-secondary" title="View All Transactions for This Date">
                                            <i class="bi bi-calendar-day"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($data['can_edit'] && !$data['is_locked']): ?>
                                        <a href="<?= appUrl('daily/edit?id=' . $txn['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($data['can_delete'] && !$data['is_locked']): ?>
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                                onclick="deleteTransaction(<?= $txn['id'] ?>, '<?= htmlspecialchars($txn['txn_date']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Totals Row -->
                            <?php if ($data['totals'] && $data['totals']['days_count'] > 0): ?>
                            <tr class="table-warning fw-bold">
                                <td>TOTALS</td>
                                <td class="text-end table-money"><?= Money::format($data['totals']['total_ca'] ?? 0) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['totals']['total_ag1'] ?? 0) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['totals']['total_av1'] ?? 0) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['totals']['total_ag2'] ?? 0) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['totals']['total_av2'] ?? 0) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['totals']['total_ga'] ?? 0) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['totals']['total_re'] ?? 0) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['totals']['total_je'] ?? 0) ?></td>
                                <td class="text-end table-money text-success"><?= Money::format($data['totals']['total_fi'] ?? 0) ?></td>
                                <td class="text-end table-money text-purple"><?= Money::format($data['totals']['total_gai_ga'] ?? 0) ?></td>
                                <td></td>
                                <td class="text-muted"><?= $data['totals']['transaction_count'] ?? $data['totals']['days_count'] ?> records</td>
                                <td></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Enhanced Pagination -->
                <?php if ($data['total_pages'] > 1): ?>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="text-muted">
                                Showing <?= (($data['current_page'] - 1) * $data['per_page']) + 1 ?> to 
                                <?= min($data['current_page'] * $data['per_page'], $data['total_count']) ?> 
                                of <?= $data['total_count'] ?> transactions
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Transactions pagination">
                                <ul class="pagination justify-content-end mb-0">
                                    <li class="page-item <?= $data['current_page'] <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $data['current_page'] > 1 ? '?' . http_build_query(array_merge($_GET, ['page' => $data['current_page'] - 1])) : '#' ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php 
                                    $start = max(1, $data['current_page'] - 2);
                                    $end = min($data['total_pages'], $data['current_page'] + 2);
                                    
                                    if ($start > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                                        </li>
                                        <?php if ($start > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $data['current_page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end < $data['total_pages']): ?>
                                        <?php if ($end < $data['total_pages'] - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $data['total_pages']])) ?>"><?= $data['total_pages'] ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <li class="page-item <?= $data['current_page'] >= $data['total_pages'] ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $data['current_page'] < $data['total_pages'] ? '?' . http_build_query(array_merge($_GET, ['page' => $data['current_page'] + 1])) : '#' ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">No transactions found</h5>
                    <p class="text-muted">
                        <?php if ($data['search']): ?>
                            No transactions match your search criteria.
                        <?php else: ?>
                            No transactions found for <?= date('F Y', mktime(0, 0, 0, $data['selected_month'], 1, $data['selected_year'])) ?>.
                        <?php endif; ?>
                    </p>
                    <?php if ($data['can_create'] && !$data['is_locked']): ?>
                    <a href="<?= appUrl('daily/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add First Transaction
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the transaction for <strong id="deleteDate"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="<?= appUrl('daily/delete') ?>" class="d-inline">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Delete Transaction</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteTransaction(id, date) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteDate').textContent = date;
    
    const modal = new coreui.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('filter');
    const monthFilter = document.getElementById('monthFilter');
    const yearFilter = document.getElementById('yearFilter');
    
    // Handle filter type changes
    filterSelect.addEventListener('change', function() {
        const filterType = this.value;
        
        // Show/hide month and year filters based on selection
        if (filterType === 'month') {
            monthFilter.style.display = 'block';
            yearFilter.style.display = 'block';
        } else if (filterType === 'year') {
            monthFilter.style.display = 'none';
            yearFilter.style.display = 'block';
        } else {
            monthFilter.style.display = 'none';
            yearFilter.style.display = 'none';
        }
    });
    
    // Auto-submit form when key filters change
    document.getElementById('filter').addEventListener('change', function() {
        // For day and week, submit immediately
        if (this.value === 'day' || this.value === 'week') {
            this.form.submit();
        }
    });
    
    // Auto-submit when month/year changes (if visible)
    document.getElementById('month').addEventListener('change', function() {
        if (monthFilter.style.display !== 'none') {
            this.form.submit();
        }
    });
    
    document.getElementById('year').addEventListener('change', function() {
        if (yearFilter.style.display !== 'none') {
            this.form.submit();
        }
    });
    
    // Auto-submit when per_page changes
    document.getElementById('per_page').addEventListener('change', function() {
        this.form.submit();
    });
    
    // Auto-submit when company filter changes
    document.getElementById('company_id').addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
