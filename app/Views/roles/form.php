<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path = '') {
    return Response::url($path);
}

$editing = isset($data['editing']) && $data['editing'];
$role = $data['role'] ?? [];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-shield<?= $editing ? '-gear' : '-plus' ?> me-2"></i>
                    <?= $editing ? 'Edit Role' : 'Create Role' ?>
                </h2>
                <p class="text-muted mb-0">
                    <?= $editing ? 'Update role information' : 'Create a new role for the system' ?>
                </p>
            </div>
            <div class="btn-toolbar">
                <a href="<?= appUrl('roles') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Roles
                </a>
            </div>
        </div>

        <!-- Error Messages -->
        <?php if (isset($data['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($data['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Role Form -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-shield-check me-2"></i>Role Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= appUrl($editing ? 'roles/update' : 'roles/store') ?>" novalidate>
                            <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars($role['id'] ?? '') ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">
                                            Role Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?= htmlspecialchars($role['name'] ?? '') ?>" 
                                               required>
                                        <div class="invalid-feedback">
                                            Please provide a valid role name.
                                        </div>
                                        <div class="form-text">
                                            Enter a descriptive name for this role (e.g., "Manager", "Accountant")
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">
                                            Description
                                        </label>
                                        <input type="text" class="form-control" id="description" name="description" 
                                               value="<?= htmlspecialchars($role['description'] ?? '') ?>" 
                                               placeholder="Brief description of this role">
                                        <div class="form-text">
                                            Optional description to explain the role's purpose
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i>
                                    <?= $editing ? 'Update Role' : 'Create Role' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Role Info Sidebar -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Role Guidelines
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Role Naming</h6>
                            <ul class="list-unstyled small">
                                <li><i class="bi bi-check text-success me-1"></i>Use clear, descriptive names</li>
                                <li><i class="bi bi-check text-success me-1"></i>Keep names concise but meaningful</li>
                                <li><i class="bi bi-check text-success me-1"></i>Avoid special characters</li>
                            </ul>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6>Best Practices</h6>
                            <ul class="list-unstyled small">
                                <li><i class="bi bi-check text-success me-1"></i>Create roles based on job functions</li>
                                <li><i class="bi bi-check text-success me-1"></i>Follow principle of least privilege</li>
                                <li><i class="bi bi-check text-success me-1"></i>Review and update regularly</li>
                            </ul>
                        </div>

                        <?php if ($editing): ?>
                        <hr>
                        <div class="mb-3">
                            <h6>Role Actions</h6>
                            <div class="d-grid gap-2">
                                <a href="<?= appUrl('roles/edit-permissions?id=' . $role['id']) ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-key me-1"></i>Manage Permissions
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($editing && isset($role['created_at'])): ?>
                        <hr>
                        <div class="alert alert-info">
                            <small>
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Created:</strong><br>
                                <?= date('M j, Y g:i A', strtotime($role['created_at'])) ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
    // Form validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>
