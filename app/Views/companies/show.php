<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}

$company = $data['company'];
$usageCount = $data['usage_count'];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0"><?= htmlspecialchars($company['name']) ?></h2>
                <p class="text-muted mb-0">Company Details</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= appUrl('companies') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Companies
                </a>
                <?php if ($data['can_edit']): ?>
                <a href="<?= appUrl('companies/edit?id=' . $company['id']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>Edit Company
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Company Information -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Company Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Company Name</label>
                            <div class="fw-semibold"><?= htmlspecialchars($company['name']) ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted">Status</label>
                            <div>
                                <?php if ($company['is_active']): ?>
                                    <span class="badge bg-success fs-6">
                                        <i class="bi bi-check-circle me-1"></i>Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary fs-6">
                                        <i class="bi bi-x-circle me-1"></i>Inactive
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted">Transaction Usage</label>
                            <div class="fw-semibold">
                                <span class="badge bg-info fs-6">
                                    <i class="bi bi-graph-up me-1"></i><?= $usageCount ?> transactions
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($company['description']): ?>
                <div class="mb-3">
                    <label class="form-label text-muted">Description</label>
                    <div class="fw-semibold"><?= nl2br(htmlspecialchars($company['description'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Recent Transactions</h6>
                <a href="<?= appUrl('daily?company_id=' . $company['id']) ?>" class="btn btn-sm btn-outline-primary">
                    View All <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body" id="recentTransactions">
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm me-2"></div>
                    <span>Loading recent transactions...</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions & Metadata -->
    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <?php if ($data['can_edit']): ?>
                <div class="d-grid gap-2">
                    <a href="<?= appUrl('companies/edit?id=' . $company['id']) ?>" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>Edit Company
                    </a>
                    
                    <button type="button" class="btn btn-outline-warning" 
                            onclick="toggleCompanyStatus(<?= $company['id'] ?>, '<?= htmlspecialchars($company['name']) ?>', <?= $company['is_active'] ? 'true' : 'false' ?>)">
                        <i class="bi bi-toggle-<?= $company['is_active'] ? 'on' : 'off' ?> me-2"></i>
                        <?= $company['is_active'] ? 'Deactivate' : 'Activate' ?>
                    </button>
                    
                    <?php if ($data['can_delete'] && $usageCount == 0): ?>
                    <button type="button" class="btn btn-outline-danger" 
                            onclick="deleteCompany(<?= $company['id'] ?>, '<?= htmlspecialchars($company['name']) ?>')">
                        <i class="bi bi-trash me-2"></i>Delete Company
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="d-grid">
                    <a href="<?= appUrl('daily?company_id=' . $company['id']) ?>" class="btn btn-outline-info">
                        <i class="bi bi-list-ul me-2"></i>View Transactions
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Metadata -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Metadata</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Created:</span>
                        <span class="fw-semibold"><?= date('M j, Y', strtotime($company['created_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Time:</span>
                        <span class="fw-semibold"><?= date('g:i A', strtotime($company['created_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">By:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($company['created_by_name'] ?? 'System') ?></span>
                    </div>
                </div>
                
                <?php if ($company['updated_at'] && $company['updated_at'] !== $company['created_at']): ?>
                <hr>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Updated:</span>
                        <span class="fw-semibold"><?= date('M j, Y', strtotime($company['updated_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Time:</span>
                        <span class="fw-semibold"><?= date('g:i A', strtotime($company['updated_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">By:</span>
                        <span class="fw-semibold"><?= htmlspecialchars($company['updated_by_name'] ?? 'System') ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="text-center">
                    <small class="text-muted">Company ID: <?= $company['id'] ?></small>
                </div>
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
                    <button type="submit" class="btn btn-warning">Change Status</button>
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
    // Load recent transactions
    loadRecentTransactions();
});

function loadRecentTransactions() {
    const container = document.getElementById('recentTransactions');
    
    fetch('<?= appUrl('api/companies/' . $company['id'] . '/transactions?limit=5') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                let html = '<div class="table-responsive">';
                html += '<table class="table table-sm mb-0">';
                html += '<thead><tr><th>Date</th><th class="text-end">CA</th><th class="text-end">FI</th></tr></thead>';
                html += '<tbody>';
                
                data.data.forEach(txn => {
                    html += `<tr>
                        <td><a href="<?= appUrl('daily/show?id=') ?>${txn.id}" class="text-decoration-none">${new Date(txn.txn_date).toLocaleDateString()}</a></td>
                        <td class="text-end">GH₵${parseFloat(txn.ca || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td class="text-end">GH₵${parseFloat(txn.fi || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        <p>No transactions found for this company</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading transactions:', error);
            container.innerHTML = `
                <div class="text-center py-3 text-muted">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    <p>Failed to load recent transactions</p>
                </div>
            `;
        });
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
