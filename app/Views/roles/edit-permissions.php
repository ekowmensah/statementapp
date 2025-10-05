<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path = '') {
    return Response::url($path);
}

$role = $data['role'];
$permissions = $data['permissions'];
$rolePermissions = $data['rolePermissions'];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-key me-2"></i>Manage Permissions
                </h2>
                <p class="text-muted mb-0">
                    Configure permissions for role: <strong><?= htmlspecialchars($role['name']) ?></strong>
                </p>
            </div>
            <div class="btn-toolbar">
                <a href="<?= appUrl('roles') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Roles
                </a>
            </div>
        </div>

        <!-- Permission Management Form -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-shield-check me-2"></i>Role Permissions
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= appUrl('roles/update-permissions') ?>">
                            <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                            
                            <!-- Quick Actions -->
                            <div class="mb-4">
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAll()">
                                        <i class="bi bi-check-all me-1"></i>Select All
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="selectNone()">
                                        <i class="bi bi-x-circle me-1"></i>Select None
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="selectByCategory('users')">
                                        <i class="bi bi-people me-1"></i>User Management
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="selectByCategory('transactions')">
                                        <i class="bi bi-receipt me-1"></i>Transactions
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="selectByCategory('reports')">
                                        <i class="bi bi-graph-up me-1"></i>Reports
                                    </button>
                                </div>
                            </div>

                            <!-- Permissions by Category -->
                            <?php foreach ($permissions as $category => $categoryPermissions): ?>
                            <div class="permission-category mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <h6 class="mb-0 text-primary">
                                        <?= ucfirst(str_replace('_', ' ', $category)) ?> Permissions
                                    </h6>
                                    <div class="ms-auto">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary" 
                                                onclick="toggleCategory('<?= $category ?>')">
                                            <i class="bi bi-check-square me-1"></i>Toggle All
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <?php foreach ($categoryPermissions as $permission): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="form-check permission-item">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="permission_<?= $permission['id'] ?>" 
                                                   name="permissions[]" 
                                                   value="<?= $permission['id'] ?>"
                                                   data-category="<?= $category ?>"
                                                   <?= in_array($permission['id'], $rolePermissions) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="permission_<?= $permission['id'] ?>">
                                                <strong><?= htmlspecialchars($permission['name']) ?></strong>
                                                <?php if (!empty($permission['description'])): ?>
                                                <small class="text-muted d-block">
                                                    <?= htmlspecialchars($permission['description']) ?>
                                                </small>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <hr>
                            <?php endforeach; ?>

                            <!-- Submit Button -->
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i>Update Permissions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Role Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Role Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <h5 class="mb-1"><?= htmlspecialchars($role['name']) ?></h5>
                            <p class="text-muted mb-0">
                                <?= htmlspecialchars($role['description'] ?? 'No description') ?>
                            </p>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6>Current Permissions</h6>
                            <p class="mb-2">
                                <span class="badge bg-info" id="selectedCount">
                                    <?= count($rolePermissions) ?> selected
                                </span>
                            </p>
                            <div class="small text-muted">
                                <div id="permissionSummary">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6>Security Tips</h6>
                            <ul class="list-unstyled small">
                                <li><i class="bi bi-check text-success me-1"></i>Only assign necessary permissions</li>
                                <li><i class="bi bi-check text-success me-1"></i>Review permissions regularly</li>
                                <li><i class="bi bi-check text-success me-1"></i>Test with limited permissions first</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
    // Permission management functions
    function selectAll() {
        document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
            checkbox.checked = true;
        });
        updatePermissionCount();
    }

    function selectNone() {
        document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        updatePermissionCount();
    }

    function selectByCategory(category) {
        document.querySelectorAll(`input[data-category="${category}"]`).forEach(checkbox => {
            checkbox.checked = true;
        });
        updatePermissionCount();
    }

    function toggleCategory(category) {
        const categoryCheckboxes = document.querySelectorAll(`input[data-category="${category}"]`);
        const allChecked = Array.from(categoryCheckboxes).every(cb => cb.checked);
        
        categoryCheckboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });
        updatePermissionCount();
    }

    function updatePermissionCount() {
        const selectedCount = document.querySelectorAll('input[name="permissions[]"]:checked').length;
        document.getElementById('selectedCount').textContent = selectedCount + ' selected';
        
        // Update permission summary
        updatePermissionSummary();
    }

    function updatePermissionSummary() {
        const categories = {};
        document.querySelectorAll('input[name="permissions[]"]:checked').forEach(checkbox => {
            const category = checkbox.dataset.category;
            if (!categories[category]) {
                categories[category] = 0;
            }
            categories[category]++;
        });

        const summaryElement = document.getElementById('permissionSummary');
        let summaryHtml = '';
        
        for (const [category, count] of Object.entries(categories)) {
            const categoryName = category.charAt(0).toUpperCase() + category.slice(1).replace('_', ' ');
            summaryHtml += `<div>${categoryName}: ${count}</div>`;
        }
        
        summaryElement.innerHTML = summaryHtml || '<div class="text-muted">No permissions selected</div>';
    }

    // Initialize permission count on page load
    document.addEventListener('DOMContentLoaded', function() {
        updatePermissionCount();
        
        // Add event listeners to all checkboxes
        document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', updatePermissionCount);
        });
    });
</script>

<style>
.avatar-lg {
    width: 4rem;
    height: 4rem;
    font-size: 1.5rem;
}

.permission-category {
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    background-color: #f8f9fa;
}

.permission-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    transition: all 0.2s ease;
}

.permission-item:hover {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.permission-item .form-check-input:checked + .form-check-label {
    color: #0d6efd;
}
</style>
