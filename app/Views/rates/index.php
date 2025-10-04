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
                <h2 class="mb-0">Rate Management</h2>
                <p class="text-muted mb-0">Manage AG1 and AG2 percentage rates</p>
            </div>
            <?php if ($data['can_create']): ?>
          <!--  <a href="<?= appUrl('rates/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add New Rate
            </a> -->
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <?php if (!empty($data['rates'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-header">
                            <tr>
                                <th>Effective Date</th>
                                <th class="text-end">AG1 Rate</th>
                                <th class="text-end">AG2 Rate</th>
                                <th>Note</th>
                                <th>Created</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['rates'] as $rate): ?>
                            <tr class="<?= $rate['is_current'] ? 'table-success' : '' ?>">
                                <td>
                                    <strong><?= date('M j, Y', strtotime($rate['effective_on'])) ?></strong>
                                    <?php if ($rate['is_current']): ?>
                                        <span class="badge bg-success ms-2">Current</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold"><?= Money::formatPercentage($rate['rate_ag1']) ?></td>
                                <td class="text-end fw-semibold"><?= Money::formatPercentage($rate['rate_ag2']) ?></td>
                                <td class="text-muted">
                                    <?= htmlspecialchars(substr($rate['note'] ?? '', 0, 50)) ?>
                                    <?= strlen($rate['note'] ?? '') > 50 ? '...' : '' ?>
                                </td>
                                <td class="text-muted small">
                                    <?= date('M j, Y', strtotime($rate['created_at'])) ?>
                                    <br>
                                    <?= htmlspecialchars($rate['created_by_name'] ?? 'System') ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if ($data['can_edit']): ?>
                                        <a href="<?= appUrl('rates/edit?id=' . $rate['id']) ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($data['can_delete']): ?>
                                            <?php if ($rate['is_current']): ?>
                                            <button type="button" class="btn btn-outline-secondary" title="Cannot delete current rate" disabled>
                                                <i class="bi bi-lock"></i>
                                            </button>
                                            <?php else: ?>
                                        <button type="button" class="btn btn-outline-danger" title="Delete"
                                                onclick="deleteRate(<?= $rate['id'] ?>, '<?= htmlspecialchars($rate['effective_on']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-percent fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">No rates configured</h5>
                    <p class="text-muted">Add your first rate to start calculating transactions.</p>
                    <?php if ($data['can_create']): ?>
                    <a href="<?= appUrl('rates/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add First Rate
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
                <p>Are you sure you want to delete the rate effective <strong id="deleteDate"></strong>?</p>
                <p class="text-muted">This action cannot be undone and may affect historical calculations.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="<?= appUrl('rates/delete') ?>" class="d-inline">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger">Delete Rate</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteRate(id, date) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteDate').textContent = date;
    
    const modal = new coreui.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
