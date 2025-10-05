<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path = '') {
    return Response::url($path);
}

$user = $data['user'];
$roles = $data['roles'];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">
                    <i class="bi bi-person-circle me-2"></i>My Profile
                </h2>
                <p class="text-muted mb-0">Manage your account settings and information</p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_GET['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($data['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($data['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-badge me-2"></i>Profile Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= appUrl('users/update-profile') ?>" novalidate>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">
                                                    Full Name <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                       value="<?= htmlspecialchars($user['name']) ?>" 
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
                                                       value="<?= htmlspecialchars($user['email']) ?>" 
                                                       required>
                                                <div class="invalid-feedback">
                                                    Please provide a valid email address.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <h6 class="mb-3">Change Password</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">New Password</label>
                                                <input type="password" class="form-control" id="password" name="password">
                                                <div class="form-text">Leave blank to keep current password</div>
                                                <div class="invalid-feedback">
                                                    Password must be at least 6 characters.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password_confirm" class="form-label">Confirm New Password</label>
                                                <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                                                <div class="invalid-feedback">
                                                    Passwords must match.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-1"></i>Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

            <!-- Profile Summary -->
            <div class="col-lg-4">
                <!-- User Info Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-person me-2"></i>Account Summary
                        </h6>
                    </div>
                    <div class="card-body text-center">
                                <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                    <?= strtoupper(substr($user['name'], 0, 2)) ?>
                                </div>
                                <h5 class="mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                                <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                                <span class="badge bg-success">Active Account</span>
                            </div>
                        </div>

                        <!-- Roles Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-shield-check me-2"></i>Your Roles
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($roles)): ?>
                                    <?php foreach ($roles as $role): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-primary me-2"><?= htmlspecialchars($role['name']) ?></span>
                                        <?php if (!empty($role['description'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($role['description']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0">No roles assigned</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Account Info Card -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="bi bi-info-circle me-2"></i>Account Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <small class="text-muted">Member Since</small>
                                        <div class="fw-bold"><?= date('M Y', strtotime($user['created_at'])) ?></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Last Updated</small>
                                        <div class="fw-bold"><?= date('M j, Y', strtotime($user['updated_at'] ?? $user['created_at'])) ?></div>
                                    </div>
                                </div>

                                <hr>

                                <div class="mb-3">
                                    <h6>Security Tips</h6>
                                    <ul class="list-unstyled small">
                                        <li><i class="bi bi-check text-success me-1"></i>Use a strong, unique password</li>
                                        <li><i class="bi bi-check text-success me-1"></i>Keep your email address current</li>
                                        <li><i class="bi bi-check text-success me-1"></i>Log out when using shared computers</li>
                                    </ul>
                                </div>
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
            
            if (password && password !== confirm) {
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

    <style>
        .avatar-lg {
            width: 80px;
            height: 80px;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
