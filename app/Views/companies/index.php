<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}
?>

<style>
/* Mobile Responsiveness for Companies */
@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .d-flex.gap-2 {
        margin-top: 1rem;
        width: 100%;
        justify-content: flex-start;
    }
    
    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .table th, .table td {
        padding: 0.5rem 0.25rem;
        vertical-align: middle;
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
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
}
</style>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Companies</h2>
                <p class="text-muted mb-0">Manage companies for transaction categorization</p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($data['can_create']): ?>
                <a href="<?= appUrl('companies/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add Company
                </a>
                <?php endif; ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-coreui-toggle="dropdown">
                        <i class="bi bi-three-dots me-2"></i>Actions
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= appUrl('companies/export') ?>">
                            <i class="bi bi-download me-2"></i>Export Companies
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Search & Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= appUrl('companies') ?>" class="row g-3" id="filterForm">
                    <!-- Search -->
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by company name or description..." value="<?= htmlspecialchars($data['search']) ?>">
                    </div>
                    
                    <!-- Per Page -->
                    <div class="col-md-3">
                        <label for="per_page" class="form-label">Per Page</label>
                        <select class="form-select" id="per_page" name="per_page">
                            <option value="10" <?= $data['per_page'] == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $data['per_page'] == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $data['per_page'] == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $data['per_page'] == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>
                    
                    <!-- Search Button -->
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Companies Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <?php if (!empty($data['companies'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-header">
                            <tr>
                                <th>Company Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Usage Count</th>
                                <th>Created By</th>
                                <th>Created Date</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['companies'] as $company): ?>
                            <tr>
                                <td>
                                    <a href="<?= appUrl('companies/show?id=' . $company['id']) ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($company['name']) ?>
                                    </a>
                                </td>
                                <td class="text-muted">
                                    <?= htmlspecialchars(substr($company['description'] ?? '', 0, 50)) ?>
                                    <?= strlen($company['description'] ?? '') > 50 ? '...' : '' ?>
                                </td>
                                <td>
                                    <?php if ($company['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $company['transaction_count'] ?? 0 ?> transactions
                                    </span>
                                </td>
                                <td class="text-muted">
                                    <?= htmlspecialchars($company['created_by_name'] ?? 'System') ?>
                                </td>
                                <td class="text-muted">
                                    <?= date('M j, Y', strtotime($company['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= appUrl('companies/show?id=' . $company['id']) ?>" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($data['can_edit']): ?>
                                        <a href="<?= appUrl('companies/edit?id=' . $company['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($data['can_edit']): ?>
                                        <button type="button" class="btn btn-outline-warning" title="Toggle Status"
                                                onclick="toggleCompanyStatus(<?= $company['id'] ?>, '<?= htmlspecialchars($company['name']) ?>', <?= $company['is_active'] ? 'true' : 'false' ?>)">
                                            <i class="bi bi-toggle-<?= $company['is_active'] ? 'on' : 'off' ?>"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($data['can_delete'] && ($company['transaction_count'] ?? 0) == 0): ?>
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                                onclick="deleteCompany(<?= $company['id'] ?>, '<?= htmlspecialchars($company['name']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($data['pagination']['total_pages'] > 1): ?>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="text-muted">
                                Showing <?= (($data['pagination']['current_page'] - 1) * $data['pagination']['per_page']) + 1 ?> to 
                                <?= min($data['pagination']['current_page'] * $data['pagination']['per_page'], $data['pagination']['total_count']) ?> 
                                of <?= $data['pagination']['total_count'] ?> companies
                            </div>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Companies pagination">
                                <ul class="pagination justify-content-end mb-0">
                                    <li class="page-item <?= $data['pagination']['current_page'] <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $data['pagination']['current_page'] > 1 ? '?' . http_build_query(array_merge($_GET, ['page' => $data['pagination']['current_page'] - 1])) : '#' ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php 
                                    $start = max(1, $data['pagination']['current_page'] - 2);
                                    $end = min($data['pagination']['total_pages'], $data['pagination']['current_page'] + 2);
                                    
                                    if ($start > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                                        </li>
                                        <?php if ($start > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $data['pagination']['current_page'] ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end < $data['pagination']['total_pages']): ?>
                                        <?php if ($end < $data['pagination']['total_pages'] - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $data['pagination']['total_pages']])) ?>"><?= $data['pagination']['total_pages'] ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <li class="page-item <?= $data['pagination']['current_page'] >= $data['pagination']['total_pages'] ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= $data['pagination']['current_page'] < $data['pagination']['total_pages'] ? '?' . http_build_query(array_merge($_GET, ['page' => $data['pagination']['current_page'] + 1])) : '#' ?>">
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
                    <i class="bi bi-building fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">No companies found</h5>
                    <p class="text-muted">
                        <?php if ($data['search']): ?>
                            No companies match your search criteria.
                        <?php else: ?>
                            No companies have been created yet.
                        <?php endif; ?>
                    </p>
                    <?php if ($data['can_create']): ?>
                    <a href="<?= appUrl('companies/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add First Company
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
                <p>Are you sure you want to delete the company <strong id="deleteCompanyName"></strong>?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="<?= appUrl('companies/delete') ?>" class="d-inline">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Delete Company</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toggle Status Confirmation Modal -->
<div class="modal fade" id="toggleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Status Change</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong id="toggleAction"></strong> the company <strong id="toggleCompanyName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                <form id="toggleForm" method="POST" action="<?= appUrl('companies/toggle-active') ?>" class="d-inline">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" id="toggleId">
                    <button type="submit" class="btn btn-warning" id="toggleButton">Change Status</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCompany(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteCompanyName').textContent = name;
    
    const modal = new coreui.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function toggleCompanyStatus(id, name, isActive) {
    document.getElementById('toggleId').value = id;
    document.getElementById('toggleCompanyName').textContent = name;
    document.getElementById('toggleAction').textContent = isActive ? 'deactivate' : 'activate';
    
    const modal = new coreui.Modal(document.getElementById('toggleModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit when per_page changes
    document.getElementById('per_page').addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
