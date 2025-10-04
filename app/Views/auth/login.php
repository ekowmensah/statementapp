<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= htmlspecialchars($data['title'] ?? 'Login - Daily Statement App') ?></title>
    
    <!-- CSRF Token -->
    <?= CSRF::meta() ?>
    
    <!-- CoreUI CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@coreui/coreui@4.2.6/dist/css/coreui.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .brand-logo {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .login-form {
            padding: 2rem;
        }
        
        .form-floating > label {
            color: #6c757d;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 500;
        }
        
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .footer-text {
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
            margin-top: 2rem;
        }
    </style>
</head>

<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-6">
                <div class="card login-card">
                    <div class="card-body login-form">
                        <div class="text-center mb-4">
                            <i class="bi bi-calculator brand-logo"></i>
                            <h3 class="fw-bold text-dark">Daily Statement App</h3>
                            <p class="text-muted">Sign in to your account</p>
                        </div>

                        <!-- Flash Messages -->
                        <?php
                        $flashMessages = Flash::all();
                        foreach ($flashMessages as $type => $message):
                            $alertClass = match($type) {
                                'success' => 'alert-success',
                                'error' => 'alert-danger',
                                'warning' => 'alert-warning',
                                'info' => 'alert-info',
                                default => 'alert-info'
                            };
                        ?>
                        <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                        </div>
                        <?php endforeach; ?>

                        <!-- Validation Errors -->
                        <?php if (isset($data['errors']) && !empty($data['errors'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($data['errors'] as $field => $fieldErrors): ?>
                                    <?php foreach ($fieldErrors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= Response::url('login') ?>">
                            <?= CSRF::field() ?>
                            
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="name@example.com" required 
                                       value="<?= htmlspecialchars($_SESSION['old_input']['email'] ?? '') ?>">
                                <label for="email">
                                    <i class="bi bi-envelope me-2"></i>Email Address
                                </label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required>
                                <label for="password">
                                    <i class="bi bi-lock me-2"></i>Password
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Sign In
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                Default credentials: admin@example.com / admin123
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="footer-text">
                    <small>
                        &copy; <?= date('Y') ?> Daily Statement App. 
                        Built with <i class="bi bi-heart-fill text-danger"></i> using CoreUI.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- CoreUI JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@4.2.6/dist/js/coreui.bundle.min.js"></script>
    
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new coreui.Alert(alert);
                    if (bsAlert) bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>

<?php
// Clear old input data
unset($_SESSION['old_input']);
?>
