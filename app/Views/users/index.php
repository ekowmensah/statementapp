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
                    <i class="bi bi-people me-2"></i>User Management
                </h2>
                <p class="text-muted mb-0">Manage user accounts, roles, and permissions</p>
            </div>
            <?php if ($data['can_create']): ?>
            <div class="btn-toolbar">
                <a href="<?= appUrl('users/create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add User
                </a>
            </div>
            <?php endif; ?>
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

        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                        <form method="GET" action="<?= appUrl('users') ?>" class="row g-3">
                            <div class="col-md-6">
                                <label for="search" class="form-label">Search Users</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?= htmlspecialchars($data['search']) ?>" 
                                       placeholder="Search by name or email...">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
                                <?php if ($data['search']): ?>
                                <a href="<?= appUrl('users') ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i>Clear
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-table me-2"></i>Users
                            <span class="badge bg-primary ms-2"><?= $data['pagination']['total_items'] ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($data['users'])): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-people display-1 text-muted"></i>
                            <h4 class="mt-3">No Users Found</h4>
                            <p class="text-muted">
                                <?php if ($data['search']): ?>
                                    No users match your search criteria.
                                <?php else: ?>
                                    No users have been created yet.
                                <?php endif; ?>
                            </p>
                            <?php if ($data['can_create'] && !$data['search']): ?>
                            <a href="<?= appUrl('users/create') ?>" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i>Create First User
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Roles</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th width="200">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['users'] as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                                                    <?php if ($user['id'] == Auth::user()['id']): ?>
                                                    <span class="badge bg-info ms-1">You</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <?php if ($user['roles']): ?>
                                                <?php foreach (explode(',', $user['roles']) as $role): ?>
                                                <span class="badge bg-secondary me-1"><?= htmlspecialchars(trim($role)) ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No roles</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if ($data['can_edit']): ?>
                                                <a href="<?= appUrl('users/edit') ?>?id=<?= $user['id'] ?>" 
                                                   class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($data['can_edit'] && $user['id'] != Auth::user()['id']): ?>
                                                <button type="button" class="btn btn-outline-warning" 
                                                        onclick="toggleUserStatus(<?= $user['id'] ?>, <?= $user['is_active'] ? 0 : 1 ?>)"
                                                        title="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                    <i class="bi bi-<?= $user['is_active'] ? 'pause' : 'play' ?>"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($data['can_delete'] && $user['id'] != Auth::user()['id']): ?>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')"
                                                        title="Delete">
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
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($data['pagination']['total_pages'] > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Users pagination">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($data['pagination']['has_prev']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= appUrl('users') ?>?page=<?= $data['pagination']['prev_page'] ?><?= $data['search'] ? '&search=' . urlencode($data['search']) : '' ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $data['pagination']['current_page'] - 2); $i <= min($data['pagination']['total_pages'], $data['pagination']['current_page'] + 2); $i++): ?>
                                <li class="page-item <?= $i == $data['pagination']['current_page'] ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= appUrl('users') ?>?page=<?= $i ?><?= $data['search'] ? '&search=' . urlencode($data['search']) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <?php if ($data['pagination']['has_next']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= appUrl('users') ?>?page=<?= $data['pagination']['next_page'] ?><?= $data['search'] ? '&search=' . urlencode($data['search']) : '' ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
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
                    <p>Are you sure you want to delete the user <strong id="deleteUserName"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="<?= appUrl('users/delete') ?>" style="display: inline;">
                        <input type="hidden" name="id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </form>
                </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
        function deleteUser(id, name) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUserName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function toggleUserStatus(id, active) {
            if (confirm(`Are you sure you want to ${active ? 'activate' : 'deactivate'} this user?`)) {
                fetch('<?= appUrl('users/toggle-active') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}&active=${active}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to update user status');
                    }
                })
                .catch(error => {
                    alert('An error occurred while updating user status');
                });
            }
        }
    </script>

    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
