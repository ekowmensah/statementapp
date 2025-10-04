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

        // PWA Install Prompt
        let deferredPrompt;
        let installButton;

        // Create install button
        function createInstallButton() {
            if (installButton) return;
            
            installButton = document.createElement('div');
            installButton.id = 'pwa-install-prompt';
            installButton.innerHTML = `
                <div class="alert alert-info alert-dismissible fade show position-fixed" 
                     style="bottom: 20px; right: 20px; z-index: 9999; max-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-download me-2"></i>
                        <div class="flex-grow-1">
                            <strong>Install App</strong><br>
                            <small>Add Daily Statement to your home screen for quick access!</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-primary btn-sm me-2" id="install-pwa-btn">
                            <i class="bi bi-plus-circle me-1"></i>Install
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="dismiss-pwa-btn">
                            Later
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(installButton);

            // Install button click
            document.getElementById('install-pwa-btn').addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log('PWA install outcome:', outcome);
                    deferredPrompt = null;
                }
                hideInstallPrompt();
            });

            // Dismiss button click
            document.getElementById('dismiss-pwa-btn').addEventListener('click', () => {
                hideInstallPrompt();
                localStorage.setItem('pwa-install-dismissed', Date.now());
            });
        }

        function hideInstallPrompt() {
            if (installButton) {
                installButton.remove();
                installButton = null;
            }
        }

        function shouldShowInstallPrompt() {
            // Don't show if already dismissed recently (7 days)
            const dismissed = localStorage.getItem('pwa-install-dismissed');
            if (dismissed && (Date.now() - parseInt(dismissed)) < 7 * 24 * 60 * 60 * 1000) {
                return false;
            }

            // Don't show if already installed
            if (window.matchMedia('(display-mode: standalone)').matches) {
                return false;
            }

            // Don't show on desktop (optional)
            if (window.innerWidth > 768) {
                return false;
            }

            return true;
        }

        // Listen for beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA install prompt available');
            e.preventDefault();
            deferredPrompt = e;

            if (shouldShowInstallPrompt()) {
                setTimeout(createInstallButton, 3000); // Show after 3 seconds
            }
        });

        // Listen for app installed event
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            hideInstallPrompt();
            deferredPrompt = null;
        });

        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= Response::url('sw.js') ?>')
                    .then((registration) => {
                        console.log('SW registered: ', registration);
                    })
                    .catch((registrationError) => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
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

    <script>
        // Cache management utilities
        function clearBrowserCache() {
            // Clear various browser caches
            if ('caches' in window) {
                caches.keys().then(function(names) {
                    names.forEach(function(name) {
                        caches.delete(name);
                    });
                });
            }
            
            // Clear localStorage and sessionStorage
            if (typeof Storage !== "undefined") {
                localStorage.clear();
                sessionStorage.clear();
            }
            
            // Force reload from server with cache bypass
            window.location.href = window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_refresh=' + Date.now();
        }
        
        // Add global keyboard shortcut for cache clearing (Ctrl+Shift+R)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'R') {
                e.preventDefault();
                clearBrowserCache();
            }
        });
        
        // Add cache-busting to all fetch requests globally
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            let [resource, config] = args;
            
            // Add cache-busting to URL if it's a string
            if (typeof resource === 'string') {
                const separator = resource.includes('?') ? '&' : '?';
                resource = resource + separator + '_cb=' + Date.now() + '&_r=' + Math.random();
            }
            
            // Add no-cache headers to config
            config = config || {};
            config.cache = 'no-cache';
            config.headers = config.headers || {};
            config.headers['Cache-Control'] = 'no-cache, no-store, must-revalidate, max-age=0';
            config.headers['Pragma'] = 'no-cache';
            config.headers['Expires'] = '0';
            
            return originalFetch(resource, config);
        };
        
        // Force page refresh on browser back/forward
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload(true);
            }
        });

        // Enhanced logout handling with CSRF error recovery
        document.addEventListener('DOMContentLoaded', function() {
            const logoutForm = document.getElementById('logoutForm');
            const logoutFallback = document.getElementById('logoutFallback');
            
            if (logoutForm) {
                logoutForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Show loading state
                    const button = this.querySelector('button');
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Logging out...';
                    button.disabled = true;
                    
                    // Submit form via fetch for better error handling
                    const formData = new FormData(this);
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (response.ok) {
                            // Successful logout - redirect
                            window.location.href = '<?= Response::url('login') ?>';
                        } else {
                            // CSRF or other error - show fallback
                            console.error('Logout failed, showing fallback option');
                            button.innerHTML = originalText;
                            button.disabled = false;
                            
                            if (logoutFallback) {
                                logoutFallback.classList.remove('d-none');
                                logoutForm.classList.add('d-none');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Logout error:', error);
                        // Show fallback on any error
                        button.innerHTML = originalText;
                        button.disabled = false;
                        
                        if (logoutFallback) {
                            logoutFallback.classList.remove('d-none');
                            logoutForm.classList.add('d-none');
                        }
                    });
                });
            }
        });
    </script>

</body>
</html>
