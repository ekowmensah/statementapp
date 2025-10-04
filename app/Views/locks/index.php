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
                <h2 class="mb-0">Month Lock Management</h2>
                <p class="text-muted mb-0">Lock months to prevent accidental edits after closing periods</p>
            </div>
            <?php if ($data['can_manage']): ?>
            <button type="button" class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#lockModal">
                <i class="bi bi-lock me-2"></i>Lock Month
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <?php if (!empty($data['locks'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-header">
                            <tr>
                                <th>Month</th>
                                <th>Year</th>
                                <th>Locked Date</th>
                                <th>Locked By</th>
                                <th>Reason</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['locks'] as $lock): ?>
                            <tr>
                                <td>
                                    <strong><?= date('F', mktime(0, 0, 0, $lock['month_num'], 1)) ?></strong>
                                </td>
                                <td class="fw-semibold"><?= $lock['year_num'] ?></td>
                                <td class="text-muted">
                                    <?= date('M j, Y g:i A', strtotime($lock['locked_at'])) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($lock['locked_by_name']) ?>
                                </td>
                                <td class="text-muted">
                                    <?= htmlspecialchars(substr($lock['reason'] ?? 'Month closed', 0, 50)) ?>
                                    <?= strlen($lock['reason'] ?? '') > 50 ? '...' : '' ?>
                                </td>
                                <td>
                                    <?php if ($data['can_manage']): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                            onclick="unlockMonth(<?= $lock['id'] ?>, '<?= date('F Y', mktime(0, 0, 0, $lock['month_num'], 1, $lock['year_num'])) ?>')">
                                        <i class="bi bi-unlock me-1"></i>Unlock
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-unlock fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">No locked months</h5>
                    <p class="text-muted">All months are currently unlocked and can be edited.</p>
                    <?php if ($data['can_manage']): ?>
                    <button type="button" class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#lockModal">
                        <i class="bi bi-lock me-2"></i>Lock First Month
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Lock Month Modal -->
<div class="modal fade" id="lockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lock Month</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= appUrl('locks/lock') ?>">
                <div class="modal-body">
                    <?= CSRF::field() ?>
                    
                    <div class="mb-3">
                        <label for="month" class="form-label">Month <span class="text-danger">*</span></label>
                        <select class="form-select" id="month" name="month" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                        <select class="form-select" id="year" name="year" required>
                            <?php for ($y = date('Y') - 2; $y <= date('Y'); $y++): ?>
                                <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" 
                                  placeholder="Optional reason for locking this month...">Month closed for accounting period</textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Locking a month will prevent all edits to transactions in that period. 
                        This action should only be done after the accounting period is finalized.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-lock me-2"></i>Lock Month
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unlock Confirmation Modal -->
<div class="modal fade" id="unlockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Unlock</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to unlock <strong id="unlockMonth"></strong>?</p>
                <p class="text-muted">This will allow transactions in this month to be edited again.</p>
                
                <div class="mb-3">
                    <label for="unlock_reason" class="form-label">Reason for unlocking</label>
                    <textarea class="form-control" id="unlock_reason" name="unlock_reason" rows="3" 
                              placeholder="Please provide a reason for unlocking this month..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                <form id="unlockForm" method="POST" action="<?= appUrl('locks/unlock') ?>" class="d-inline">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" id="unlockId">
                    <input type="hidden" name="reason" id="unlockReasonHidden">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-unlock me-2"></i>Unlock Month
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function unlockMonth(id, monthName) {
    document.getElementById('unlockId').value = id;
    document.getElementById('unlockMonth').textContent = monthName;
    document.getElementById('unlock_reason').value = '';
    
    const modal = new coreui.Modal(document.getElementById('unlockModal'));
    modal.show();
}

// Update hidden field when reason changes
document.getElementById('unlock_reason').addEventListener('input', function() {
    document.getElementById('unlockReasonHidden').value = this.value;
});

// Form validation
document.getElementById('unlockForm').addEventListener('submit', function(e) {
    const reason = document.getElementById('unlock_reason').value.trim();
    if (!reason) {
        e.preventDefault();
        FormUtils.showError('Please provide a reason for unlocking this month.');
        return false;
    }
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
