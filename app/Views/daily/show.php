<?php
require_once __DIR__ . '/../layouts/header.php';

$transaction = $data['transaction'];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Transaction Details</h2>
                <p class="text-muted mb-0">
                    <?= date('F j, Y (l)', strtotime($transaction['txn_date'])) ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($data['can_edit']): ?>
                <a href="<?= Response::url('daily/edit') ?>?id=<?= $transaction['id'] ?>" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>Edit
                </a>
                <?php endif; ?>
                <a href="<?= Response::url('daily') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Transaction Details -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transaction Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-borderless">
                        <tbody>
                            <tr class="border-bottom">
                                <td class="fw-semibold text-muted">Transaction Date:</td>
                                <td><?= date('F j, Y (l)', strtotime($transaction['txn_date'])) ?></td>
                            </tr>
                            <?php if ($transaction['note']): ?>
                            <tr class="border-bottom">
                                <td class="fw-semibold text-muted">Note:</td>
                                <td><?= htmlspecialchars($transaction['note']) ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <h6 class="mt-4 mb-3">Input Values</h6>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title text-primary">CA</h5>
                                <h3 class="mb-0"><?= Money::format($transaction['ca']) ?></h3>
                                <small class="text-muted">Gross Inflow</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title text-warning">GA</h5>
                                <h3 class="mb-0"><?= Money::format($transaction['ga']) ?></h3>
                                <small class="text-muted">Daily Fixed Deduction</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h5 class="card-title text-info">JE</h5>
                                <h3 class="mb-0"><?= Money::format($transaction['je']) ?></h3>
                                <small class="text-muted">Journal Entry</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h6 class="mt-4 mb-3">Computed Values</h6>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <td class="fw-semibold">AG1 
                                    <small class="text-muted">(<?= Money::formatPercentage($transaction['rate_ag1'] ?? 0) ?>)</small>
                                </td>
                                <td class="text-end table-money"><?= Money::format($transaction['ag1'] ?? 0) ?></td>
                                <td class="text-muted small">CA × AG1 Rate</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">AV1</td>
                                <td class="text-end table-money"><?= Money::format($transaction['av1'] ?? 0) ?></td>
                                <td class="text-muted small">CA - AG1</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">AG2 
                                    <small class="text-muted">(<?= Money::formatPercentage($transaction['rate_ag2'] ?? 0) ?>)</small>
                                </td>
                                <td class="text-end table-money"><?= Money::format($transaction['ag2'] ?? 0) ?></td>
                                <td class="text-muted small">AV1 × AG2 Rate</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">AV2</td>
                                <td class="text-end table-money"><?= Money::format($transaction['av2'] ?? 0) ?></td>
                                <td class="text-muted small">AV1 - AG2</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">RE</td>
                                <td class="text-end table-money"><?= Money::format($transaction['re'] ?? 0) ?></td>
                                <td class="text-muted small">AV2 - GA</td>
                            </tr>
                            <tr class="table-success">
                                <td class="fw-bold">FI (Final)</td>
                                <td class="text-end table-money fw-bold fs-5"><?= Money::format($transaction['fi'] ?? 0) ?></td>
                                <td class="text-muted small">RE - JE</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Metadata -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Rate Information</h5>
            </div>
            <div class="card-body">
                <?php if (isset($transaction['rate_ag1'])): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">AG1 Rate:</span>
                    <span class="fw-semibold"><?= Money::formatPercentage($transaction['rate_ag1']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">AG2 Rate:</span>
                    <span class="fw-semibold"><?= Money::formatPercentage($transaction['rate_ag2']) ?></span>
                </div>
                <?php else: ?>
                <div class="text-muted">Rate information not available</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Record Information</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Created:</span>
                    <span class="fw-semibold"><?= date('M j, Y g:i A', strtotime($transaction['created_at'])) ?></span>
                </div>
                <?php if (isset($transaction['updated_at']) && $transaction['updated_at'] !== $transaction['created_at']): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Updated:</span>
                    <span class="fw-semibold"><?= date('M j, Y g:i A', strtotime($transaction['updated_at'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if (isset($transaction['created_by_name'])): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Created By:</span>
                    <span class="fw-semibold"><?= htmlspecialchars($transaction['created_by_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($data['can_delete']): ?>
        <div class="card mt-3 border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="card-title mb-0">Danger Zone</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Deleting this transaction will permanently remove it from the system.
                </p>
                <button type="button" class="btn btn-outline-danger btn-sm" 
                        onclick="deleteTransaction(<?= $transaction['id'] ?>, '<?= htmlspecialchars($transaction['txn_date']) ?>')">
                    <i class="bi bi-trash me-2"></i>Delete Transaction
                </button>
            </div>
        </div>
        <?php endif; ?>
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
                <form id="deleteForm" method="POST" action="<?= Response::url('daily/delete') ?>" class="d-inline">
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
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
