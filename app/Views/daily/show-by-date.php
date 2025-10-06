<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Transactions for <?= date('F j, Y', strtotime($data['date'])) ?></h2>
                <p class="text-muted mb-0">
                    <?= $data['daily_totals']['transaction_count'] ?> transaction<?= $data['daily_totals']['transaction_count'] != 1 ? 's' : '' ?> on <?= date('l', strtotime($data['date'])) ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($data['can_create']): ?>
                <a href="<?= appUrl('daily/create?date=' . $data['date']) ?>" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>Add Another Transaction
                </a>
                <?php endif; ?>
                <a href="<?= appUrl('daily') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Transactions List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Individual Transactions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Seq</th>
                                <th class="text-end">CA</th>
                                <th class="text-end">AG1</th>
                                <th class="text-end">AV1</th>
                                <th class="text-end">AG2</th>
                                <th class="text-end">AV2</th>
                                <th class="text-end">GA</th>
                                <th class="text-end">RE</th>
                                <th class="text-end">JE</th>
                                <th class="text-end">FI</th>
                                <th>Company</th>
                                <th>Note</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['transactions'] as $txn): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary">#<?= $txn['sequence_number'] ?? 1 ?></span>
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
                                <td>
                                    <?php if ($txn['company_name']): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($txn['company_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted">
                                    <?php if (!empty($txn['note'])): ?>
                                        <?= htmlspecialchars($txn['note']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?= appUrl('daily/show?id=' . $txn['id']) ?>" class="btn btn-outline-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($data['can_edit']): ?>
                                        <a href="<?= appUrl('daily/edit?id=' . $txn['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($data['can_delete']): ?>
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                                onclick="deleteTransaction(<?= $txn['id'] ?>, '<?= htmlspecialchars($txn['txn_date']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Daily Totals Row -->
                            <tr class="table-warning fw-bold">
                                <td>TOTAL</td>
                                <td class="text-end table-money"><?= Money::format($data['daily_totals']['total_ca']) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['daily_totals']['total_ag1']) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['daily_totals']['total_av1']) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['daily_totals']['total_ag2']) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['daily_totals']['total_av2']) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['daily_totals']['total_ga']) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['daily_totals']['total_re']) ?></td>
                                <td class="text-end table-money"><?= Money::format($data['daily_totals']['total_je']) ?></td>
                                <td class="text-end table-money text-success"><?= Money::format($data['daily_totals']['total_fi']) ?></td>
                                <td></td>
                                <td class="text-muted"><?= $data['daily_totals']['transaction_count'] ?> transactions</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Daily Summary</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="fs-4 fw-bold text-primary"><?= $data['daily_totals']['transaction_count'] ?></div>
                            <div class="text-muted small">Transactions</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="fs-4 fw-bold text-success"><?= Money::format($data['daily_totals']['total_fi']) ?></div>
                            <div class="text-muted small">Total FI</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="fs-5 fw-bold text-info"><?= Money::format($data['daily_totals']['total_ca']) ?></div>
                            <div class="text-muted small">Total CA</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="fs-5 fw-bold text-warning"><?= Money::format($data['daily_totals']['total_ga']) ?></div>
                            <div class="text-muted small">Total GA</div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <?php if ($data['can_create']): ?>
                    <a href="<?= appUrl('daily/create?date=' . $data['date']) ?>" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Add Another Transaction
                    </a>
                    <?php endif; ?>
                    <a href="<?= appUrl('daily') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-list me-2"></i>View All Transactions
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Transaction Breakdown -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Transaction Breakdown</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td>Total CA:</td>
                                <td class="text-end fw-semibold"><?= Money::format($data['daily_totals']['total_ca']) ?></td>
                            </tr>
                            <tr>
                                <td>Total AG1:</td>
                                <td class="text-end"><?= Money::format($data['daily_totals']['total_ag1']) ?></td>
                            </tr>
                            <tr>
                                <td>Total AV1:</td>
                                <td class="text-end"><?= Money::format($data['daily_totals']['total_av1']) ?></td>
                            </tr>
                            <tr>
                                <td>Total AG2:</td>
                                <td class="text-end"><?= Money::format($data['daily_totals']['total_ag2']) ?></td>
                            </tr>
                            <tr>
                                <td>Total AV2:</td>
                                <td class="text-end"><?= Money::format($data['daily_totals']['total_av2']) ?></td>
                            </tr>
                            <tr>
                                <td>Total GA:</td>
                                <td class="text-end"><?= Money::format($data['daily_totals']['total_ga']) ?></td>
                            </tr>
                            <tr>
                                <td>Total RE:</td>
                                <td class="text-end"><?= Money::format($data['daily_totals']['total_re']) ?></td>
                            </tr>
                            <tr>
                                <td>Total JE:</td>
                                <td class="text-end"><?= Money::format($data['daily_totals']['total_je']) ?></td>
                            </tr>
                            <tr class="table-success fw-bold">
                                <td>Total FI:</td>
                                <td class="text-end"><?= Money::format($data['daily_totals']['total_fi']) ?></td>
                            </tr>
                        </tbody>
                    </table>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this transaction?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= appUrl('daily/delete') ?>" id="deleteForm" style="display: inline;">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" id="deleteTransactionId">
                    <button type="submit" class="btn btn-danger">Delete Transaction</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteTransaction(id, date) {
    document.getElementById('deleteTransactionId').value = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
