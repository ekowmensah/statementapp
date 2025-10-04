        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <span class="text-medium-emphasis">
                            &copy; <?= date('Y') ?> Daily Statement App. 
                            <i class="bi bi-heart-fill text-danger"></i>
                        </span>
                    </div>
                    <div class="col-md-6 text-end">
                        <!-- <span class="text-medium-emphasis">
                            Powered by PHP <?= PHP_VERSION ?>
                        </span>  -->
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- CoreUI JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@coreui/coreui@4.2.6/dist/js/coreui.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Global configuration
        window.AppConfig = {
            baseUrl: '<?= Response::baseUrl() ?>',
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };

        // CSRF token for AJAX requests
        function setupAjax() {
            // Set up CSRF token for all AJAX requests
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            // For fetch requests
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                if (options.method && options.method.toUpperCase() !== 'GET') {
                    options.headers = options.headers || {};
                    options.headers['X-CSRF-TOKEN'] = token;
                }
                return originalFetch(url, options);
            };
        }

        // Money formatting utilities
        window.MoneyUtils = {
            format: function(amount, currency = '$') {
                if (amount === null || amount === undefined || amount === '') {
                    return currency + '0.00';
                }
                return currency + parseFloat(amount).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },
            
            parse: function(input) {
                if (!input) return 0;
                return parseFloat(input.toString().replace(/[^\d.-]/g, '')) || 0;
            },
            
            formatInput: function(amount) {
                if (amount === null || amount === undefined || amount === '') {
                    return '0.00';
                }
                return parseFloat(amount).toFixed(2);
            }
        };

        // Form utilities
        window.FormUtils = {
            showLoading: function(element) {
                element.classList.add('loading');
                const spinner = element.querySelector('.spinner-border');
                if (spinner) spinner.style.display = 'inline-block';
            },
            
            hideLoading: function(element) {
                element.classList.remove('loading');
                const spinner = element.querySelector('.spinner-border');
                if (spinner) spinner.style.display = 'none';
            },
            
            showError: function(message) {
                this.showAlert(message, 'danger');
            },
            
            showSuccess: function(message) {
                this.showAlert(message, 'success');
            },
            
            showAlert: function(message, type = 'info') {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                    </div>
                `;
                
                const container = document.querySelector('.container-lg');
                if (container) {
                    container.insertAdjacentHTML('afterbegin', alertHtml);
                    
                    // Auto-dismiss after 5 seconds
                    setTimeout(() => {
                        const alert = container.querySelector('.alert');
                        if (alert) {
                            const bsAlert = new coreui.Alert(alert);
                            bsAlert.close();
                        }
                    }, 5000);
                }
            }
        };

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            setupAjax();
            
            // Handle mobile sidebar toggle
            const mobileMenuToggle = document.querySelector('#mobile-menu-toggle');
            const sidebar = document.querySelector('#sidebar');
            
            if (mobileMenuToggle && sidebar) {
                console.log('Setting up mobile menu toggle');
                
                mobileMenuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Mobile menu toggle clicked');
                    sidebar.classList.toggle('show');
                });
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    if (window.innerWidth < 992 && 
                        !sidebar.contains(e.target) && 
                        !mobileMenuToggle.contains(e.target) &&
                        sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                    }
                });
                
                // Close sidebar when window is resized to desktop
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 992 && sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                    }
                });
            } else {
                console.error('Mobile menu elements not found:', {
                    toggle: !!mobileMenuToggle,
                    sidebar: !!sidebar
                });
            }
            
            // Auto-dismiss alerts after 5 seconds
            document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new coreui.Alert(alert);
                    if (bsAlert) bsAlert.close();
                }, 5000);
            });
            
            // Format money inputs on blur
            document.querySelectorAll('.money-input').forEach(input => {
                input.addEventListener('blur', function() {
                    const value = MoneyUtils.parse(this.value);
                    this.value = MoneyUtils.formatInput(value);
                });
            });
            
            // Confirm delete actions
            document.querySelectorAll('[data-confirm]').forEach(element => {
                element.addEventListener('click', function(e) {
                    const message = this.getAttribute('data-confirm');
                    if (!confirm(message)) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        });
    </script>

    <!-- Page-specific scripts -->
    <?php if (isset($data['scripts'])): ?>
        <?php foreach ($data['scripts'] as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($data['inline_script'])): ?>
        <script>
            <?= $data['inline_script'] ?>
        </script>
    <?php endif; ?>

</body>
</html>
