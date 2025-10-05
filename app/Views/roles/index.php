<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path = '') {
    return Response::url($path);
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-shield-check me-2"></i>Role Management
                </h2>
                <p class="text-muted mb-0">Manage roles and their permissions</p>
            </div>
            <div class="btn-toolbar">
                <a href="<?= appUrl('roles/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Role
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Roles List -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>System Roles
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($data['roles'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Description</th>
                                <th>Users</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['roles'] as $role): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                            <i class="bi bi-shield"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($role['name']) ?></h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        <?= htmlspecialchars($role['description'] ?? 'No description') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $role['user_count'] ?> user<?= $role['user_count'] != 1 ? 's' : '' ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($role['created_at'])) ?>
                                    </small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= appUrl('roles/edit-permissions?id=' . $role['id']) ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Manage Permissions">
                                            <i class="bi bi-key"></i>
                                        </a>
                                        <a href="<?= appUrl('roles/edit?id=' . $role['id']) ?>" 
                                           class="btn btn-outline-secondary" 
                                           title="Edit Role">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($role['user_count'] == 0): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                onclick="deleteRole(<?= $role['id'] ?>, '<?= htmlspecialchars($role['name']) ?>')"
                                                title="Delete Role">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger disabled" 
                                                title="Cannot delete role with assigned users">
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
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-shield-x display-1 text-muted"></i>
                    <h4 class="mt-3">No Roles Found</h4>
                    <p class="text-muted">Create your first role to get started.</p>
                    <a href="<?= appUrl('roles/create') ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Create Role
                    </a>
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
                <h5 class="modal-title">Delete Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the role <strong id="deleteRoleName"></strong>?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= appUrl('roles/delete') ?>" style="display: inline;">
                    <input type="hidden" name="id" id="deleteRoleId">
                    <button type="submit" class="btn btn-danger">Delete Role</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
    function deleteRole(id, name) {
        document.getElementById('deleteRoleId').value = id;
        document.getElementById('deleteRoleName').textContent = name;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>

<style>
.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.875rem;
}
</style>
