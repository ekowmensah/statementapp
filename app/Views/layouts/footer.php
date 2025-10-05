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
            format: function(amount, currency = 'GH₵') {
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

        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('PWA: Service Worker registered successfully:', registration);
                        
                        // Check for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New version available
                                    showUpdateAvailable();
                                }
                            });
                        });
                    })
                    .catch((error) => {
                        console.log('PWA: Service Worker registration failed:', error);
                    });
            });
        }

        // PWA Install Prompt
        let deferredPrompt;
        let installButton;

        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA: Install prompt available');
            e.preventDefault();
            deferredPrompt = e;
            showInstallButton();
        });

        window.addEventListener('appinstalled', (e) => {
            console.log('PWA: App installed successfully');
            hideInstallButton();
            showInstallSuccess();
        });

        function showInstallButton() {
            // Create install button if it doesn't exist
            if (!installButton) {
                installButton = document.createElement('button');
                installButton.innerHTML = '<i class="bi bi-download me-2"></i>Install App';
                installButton.className = 'btn btn-outline-primary btn-sm position-fixed';
                installButton.style.cssText = `
                    bottom: 20px;
                    right: 20px;
                    z-index: 1050;
                    border-radius: 25px;
                    padding: 0.5rem 1rem;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    backdrop-filter: blur(10px);
                    background: rgba(255,255,255,0.9);
                `;
                
                installButton.addEventListener('click', installApp);
                document.body.appendChild(installButton);
                
                // Auto-hide after 10 seconds
                setTimeout(() => {
                    if (installButton && installButton.parentNode) {
                        installButton.style.opacity = '0.7';
                    }
                }, 10000);
            }
        }

        function hideInstallButton() {
            if (installButton && installButton.parentNode) {
                installButton.remove();
                installButton = null;
            }
        }

        function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('PWA: User accepted the install prompt');
                    } else {
                        console.log('PWA: User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                    hideInstallButton();
                });
            }
        }

        function showInstallSuccess() {
            FormUtils.showSuccess('Daily Statement App installed successfully! You can now access it from your home screen.');
        }

        function showUpdateAvailable() {
            const updateAlert = `
                <div class="alert alert-info alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    <strong>Update Available</strong><br>
                    <small>A new version of the app is available. Refresh to update.</small>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="window.location.reload()">
                        Refresh
                    </button>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', updateAlert);
        }

        // Update header date dynamically
        function updateHeaderDate() {
            const dateElement = document.getElementById('current-date');
            if (dateElement) {
                const now = new Date();
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                dateElement.textContent = now.toLocaleDateString('en-US', options);
            }
        }

        // Update date immediately and then every minute
        updateHeaderDate();
        setInterval(updateHeaderDate, 60000); // Update every minute

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
            
            // Add cache-busting to URL if it's a string and doesn't already have cache-busting
            if (typeof resource === 'string') {
                // Check if cache-busting parameters already exist
                if (!resource.includes('_t=') && !resource.includes('_cb=')) {
                    const separator = resource.includes('?') ? '&' : '?';
                    resource = resource + separator + '_cb=' + Date.now();
                }
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

    <!-- Real-time User Session Monitoring -->
    <script>
        (function() {
            let sessionCheckInterval;
            let lastCheckTime = Date.now();
            
            // Check for user session updates every 30 seconds
            function startSessionMonitoring() {
                sessionCheckInterval = setInterval(checkUserUpdates, 30000);
            }
            
            function checkUserUpdates() {
                fetch('<?= Response::url('api/user/check-updates') ?>', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.account_disabled) {
                            // Account was disabled - force logout
                            showAccountDisabledNotification(data.message);
                            setTimeout(() => {
                                window.location.href = '<?= Response::url('logout') ?>';
                            }, 3000);
                            return;
                        }
                        
                        if (data.has_changes) {
                            // Account was updated - show notification and refresh UI
                            showAccountUpdatedNotification(data.changes, data.user);
                            updateUserInterface(data.user);
                        }
                    }
                })
                .catch(error => {
                    console.error('Session check error:', error);
                });
            }
            
            function showAccountDisabledNotification(message) {
                const notification = createNotification('danger', message, 'Account Disabled');
                document.body.appendChild(notification);
                
                // Clear the interval since user will be logged out
                if (sessionCheckInterval) {
                    clearInterval(sessionCheckInterval);
                }
            }
            
            function showAccountUpdatedNotification(changes, user) {
                let changesList = [];
                
                if (changes.name) {
                    changesList.push(`Name: ${changes.name.old} → ${changes.name.new}`);
                }
                if (changes.email) {
                    changesList.push(`Email: ${changes.email.old} → ${changes.email.new}`);
                }
                if (changes.roles) {
                    changesList.push(`Roles updated`);
                }
                
            //     const message = `Your account has been updated:\n${changesList.join('\n')}`;
            //     const notification = createNotification('info', message, 'Account Updated');
            //     document.body.appendChild(notification);
            // }
            
            function updateUserInterface(user) {
                // Update header user info
                const userNameElements = document.querySelectorAll('.fw-semibold');
                userNameElements.forEach(element => {
                    if (element.textContent.trim() !== user.name) {
                        element.textContent = user.name;
                    }
                });
                
                const userEmailElements = document.querySelectorAll('.text-medium-emphasis.small');
                userEmailElements.forEach(element => {
                    if (element.textContent.trim() !== user.email) {
                        element.textContent = user.email;
                    }
                });
                
                // If we're on the profile page, update the profile info
                if (window.location.pathname.includes('/users/profile')) {
                    const profileNameElements = document.querySelectorAll('h5.mb-1');
                    profileNameElements.forEach(element => {
                        if (element.textContent.trim() !== user.name) {
                            element.textContent = user.name;
                        }
                    });
                    
                    const profileEmailElements = document.querySelectorAll('p.text-muted.mb-2');
                    profileEmailElements.forEach(element => {
                        if (element.textContent.trim() !== user.email) {
                            element.textContent = user.email;
                        }
                    });
                }
            }
            
            function createNotification(type, message, title) {
                const notification = document.createElement('div');
                notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                notification.style.cssText = `
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    min-width: 300px;
                    max-width: 500px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                `;
                
                const icon = type === 'danger' ? 'exclamation-triangle' : 'info-circle';
                
                notification.innerHTML = `
                    <div class="d-flex align-items-start">
                        <i class="bi bi-${icon} me-2 mt-1"></i>
                        <div class="flex-grow-1">
                            <strong>${title}</strong><br>
                            <small>${message.replace(/\n/g, '<br>')}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                // Auto-remove after 10 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 10000);
                
                return notification;
            }
            
            // Start monitoring when page loads
            document.addEventListener('DOMContentLoaded', function() {
                // Only start monitoring if user is logged in
                if (document.querySelector('.dropdown-menu')) {
                    startSessionMonitoring();
                }
            });
            
            // Stop monitoring when page unloads
            window.addEventListener('beforeunload', function() {
                if (sessionCheckInterval) {
                    clearInterval(sessionCheckInterval);
                }
            });
        })();
    </script>

</body>
</html>
