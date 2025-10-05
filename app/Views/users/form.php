<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path = '') {
    return Response::url($path);
}

$editing = isset($data['editing']) && $data['editing'];
$user = $data['user'] ?? [];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-person<?= $editing ? '-gear' : '-plus' ?> me-2"></i>
                    <?= $editing ? 'Edit User' : 'Create User' ?>
                </h2>
                <p class="text-muted mb-0">
                    <?= $editing ? 'Update user information and role assignments' : 'Create a new user account with role assignments' ?>
                </p>
            </div>
            <div class="btn-toolbar">
                <a href="<?= appUrl('users') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Users
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

        <!-- User Form -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-person-badge me-2"></i>User Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= appUrl($editing ? 'users/update' : 'users/store') ?>" novalidate>
                                    <?php if ($editing): ?>
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id'] ?? '') ?>">
                                    <?php endif; ?>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">
                                                    Full Name <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?= htmlspecialchars($user['name'] ?? '') ?>" 
                                                       required>
                                                <div class="invalid-feedback">
                                                    Please provide a valid name.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">
                                                    Email Address <span class="text-danger">*</span>
                                                </label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                                                       required>
                                                <div class="invalid-feedback">
                                                    Please provide a valid email address.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">
                                                    Password 
                                                    <?php if (!$editing): ?>
                                                    <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </label>
                                                <input type="password" class="form-control" id="password" name="password" 
                                                       <?= !$editing ? 'required' : '' ?>>
                                                <?php if ($editing): ?>
                                                <div class="form-text">Leave blank to keep current password</div>
                                                <?php endif; ?>
                                                <div class="invalid-feedback">
                                                    Password must be at least 6 characters.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password_confirm" class="form-label">
                                                    Confirm Password 
                                                    <?php if (!$editing): ?>
                                                    <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </label>
                                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                                       <?= !$editing ? 'required' : '' ?>>
                                                <div class="invalid-feedback">
                                                    Passwords must match.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">User Roles</label>
                                        <div class="row">
                                            <?php foreach ($data['roles'] as $role): ?>
                                            <div class="col-md-6 col-lg-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="role_<?= $role['id'] ?>" 
                                                           name="roles[]" 
                                                           value="<?= $role['id'] ?>"
                                                           <?= in_array($role['id'], $user['role_ids'] ?? []) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="role_<?= $role['id'] ?>">
                                                        <?= htmlspecialchars($role['name']) ?>
                                                        <?php if (!empty($role['description'])): ?>
                                                        <small class="text-muted d-block"><?= htmlspecialchars($role['description']) ?></small>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (empty($data['roles'])): ?>
                                        <div class="text-muted">No roles available</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                   <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_active">
                                                Active User
                                                <small class="text-muted d-block">Inactive users cannot log in</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="<?= appUrl('users') ?>" class="btn btn-secondary">
                                            <i class="bi bi-x-circle me-1"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-<?= $editing ? 'check' : 'plus' ?>-circle me-1"></i>
                                            <?= $editing ? 'Update User' : 'Create User' ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-info-circle me-2"></i>User Guidelines
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6>Password Requirements</h6>
                                    <ul class="list-unstyled small">
                                        <li><i class="bi bi-check text-success me-1"></i>Minimum 6 characters</li>
                                        <li><i class="bi bi-check text-success me-1"></i>Mix of letters and numbers recommended</li>
                                        <li><i class="bi bi-check text-success me-1"></i>Special characters allowed</li>
                                    </ul>
                                </div>

                                <div class="mb-3">
                                    <h6>User Roles</h6>
                                    <p class="small text-muted">
                                        Assign appropriate roles to control what the user can access and modify in the system.
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <h6>Account Status</h6>
                                    <p class="small text-muted">
                                        Inactive users will not be able to log in to the system but their data will be preserved.
                                    </p>
                                </div>

                                <?php if ($editing): ?>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="bi bi-info-circle me-1"></i>
                                        <strong>Last Updated:</strong><br>
                                        <?= date('M j, Y g:i A', strtotime($user['updated_at'] ?? $user['created_at'])) ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
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

        // Password confirmation validation
        document.getElementById('password_confirm').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const confirm = document.getElementById('password_confirm');
            if (confirm.value) {
                confirm.dispatchEvent(new Event('input'));
            }
        });
    </script>
