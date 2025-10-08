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
                // Determine the correct service worker path
                const baseUrl = window.AppConfig?.baseUrl || '';
                const swPath = baseUrl + '/sw.js?v=2.1.0';
                
                console.log('PWA: Registering service worker at:', swPath);
                
                navigator.serviceWorker.register(swPath)
                    .then((registration) => {
                        console.log('PWA: Service Worker registered successfully:', registration);
                        
                        // Force update check
                        registration.update();
                        
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
                        
                        // Listen for messages from SW
                        navigator.serviceWorker.addEventListener('message', (event) => {
                            if (event.data && event.data.type === 'SW_ACTIVATED') {
                                console.log('PWA: Service Worker activated, version:', event.data.version);
                            }
                        });
                    })
                    .catch((error) => {
                        console.log('PWA: Service Worker registration failed:', error);
                        console.log('PWA: Trying fallback registration...');
                        
                        // Fallback: try relative path
                        navigator.serviceWorker.register('./sw.js?v=2.1.0')
                            .then((registration) => {
                                console.log('PWA: Service Worker registered successfully (fallback):', registration);
                                registration.update();
                            })
                            .catch((fallbackError) => {
                                console.log('PWA: Fallback service worker registration also failed:', fallbackError);
                            });
                    });
            });
        }

        // PWA Install Prompt
        let deferredPrompt;
        let installButton;
        let installPromptShown = false;
        let userEngaged = false;

        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA: Install prompt available');
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button immediately
            showInstallButton();
            
            // Show notification immediately for faster UX
            if (!installPromptShown) {
                setTimeout(() => {
                    showInstallNotification();
                }, 500); // Reduced from 2000ms to 500ms
                installPromptShown = true;
            }
        });

        // Additional event listener for Chrome Android
        document.addEventListener('DOMContentLoaded', function() {
            // Force check for install prompt availability after DOM load
            setTimeout(() => {
                if (!deferredPrompt) {
                    console.log('PWA: No deferred prompt detected, checking for Chrome Android');
                    const isChrome = /Chrome/.test(navigator.userAgent) && !/Edg/.test(navigator.userAgent);
                    const isAndroid = /Android/.test(navigator.userAgent);
                    
                    if (isChrome && isAndroid) {
                        console.log('PWA: Chrome Android detected, setting up enhanced install detection');
                        // Set up additional listeners for Chrome Android
                        setupChromeAndroidInstall();
                    }
                }
            }, 1000);
        });

        function setupChromeAndroidInstall() {
            // Create a more aggressive install prompt detection for Chrome Android
            let installPromptAttempts = 0;
            const maxAttempts = 3;
            
            const checkForInstallPrompt = () => {
                installPromptAttempts++;
                console.log(`PWA: Checking for install prompt (attempt ${installPromptAttempts})`);
                
                if (!deferredPrompt && installPromptAttempts < maxAttempts) {
                    // Try to trigger the beforeinstallprompt event
                    setTimeout(checkForInstallPrompt, 2000);
                } else if (!deferredPrompt && installPromptAttempts >= maxAttempts) {
                    console.log('PWA: Max attempts reached, showing manual install option');
                    if (!installButton) {
                        showManualInstallOption();
                    }
                }
            };
            
            checkForInstallPrompt();
        }

        window.addEventListener('appinstalled', (e) => {
            console.log('PWA: App installed successfully');
            
            // Mark as installed in localStorage
            localStorage.setItem('pwa-installed', 'true');
            localStorage.setItem('pwa-install-date', new Date().toISOString());
            
            // Hide install button and show success message
            hideInstallButton();
            showInstallSuccess();
            
            // Clear deferred prompt
            deferredPrompt = null;
            
            // Clear any existing install prompts or notifications
            document.querySelectorAll('.alert').forEach(alert => {
                if (alert.textContent.includes('Install') || alert.textContent.includes('install')) {
                    alert.remove();
                }
            });
        });

        // Enhanced app installation detection
        window.addEventListener('load', () => {
            checkInstallationStatus();
        });

        async function checkInstallationStatus() {
            console.log('PWA: Checking installation status...');
            
            // Check multiple indicators of installation
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            const isIOSStandalone = window.navigator.standalone === true;
            const isInWebApk = document.referrer.includes('android-app://');
            const isInstalledFlag = localStorage.getItem('pwa-installed') === 'true';
            
            // Check URL parameters that indicate PWA launch
            const urlParams = new URLSearchParams(window.location.search);
            const isPWALaunch = urlParams.has('utm_source') && urlParams.get('utm_source') === 'pwa';
            
            // Check for installed related apps (newer API)
            let hasInstalledRelatedApps = false;
            if ('getInstalledRelatedApps' in navigator) {
                try {
                    const relatedApps = await navigator.getInstalledRelatedApps();
                    hasInstalledRelatedApps = relatedApps.length > 0;
                    console.log('PWA: Related apps found:', relatedApps.length);
                } catch (error) {
                    console.log('PWA: getInstalledRelatedApps error:', error);
                }
            }
            
            // Check if running in TWA (Trusted Web Activity)
            const isTWA = document.referrer.startsWith('android-app://') || 
                         window.location.search.includes('utm_source=twa');
            
            console.log('PWA: Installation indicators:', {
                isStandalone,
                isIOSStandalone,
                isInWebApk,
                isInstalledFlag,
                isPWALaunch,
                hasInstalledRelatedApps,
                isTWA,
                displayMode: window.matchMedia('(display-mode: standalone)').matches ? 'standalone' : 'browser',
                userAgent: navigator.userAgent.substring(0, 100) + '...'
            });
            
            // If any indicator shows the app is installed, hide install button
            if (isStandalone || isIOSStandalone || isInWebApk || isInstalledFlag || 
                isPWALaunch || hasInstalledRelatedApps || isTWA) {
                console.log('PWA: App detected as installed');
                hideInstallButton();
                
                // Mark as installed in localStorage for future visits
                localStorage.setItem('pwa-installed', 'true');
                localStorage.setItem('pwa-install-date', new Date().toISOString());
                
                return; // Exit early, don't show install prompts
            }
            
            console.log('PWA: App not detected as installed, setting up install prompts');
            
            // Track user engagement for faster prompt triggering
            const engagementEvents = ['click', 'scroll', 'keydown', 'touchstart'];
            const trackEngagement = () => {
                if (!userEngaged) {
                    userEngaged = true;
                    console.log('PWA: User engagement detected');
                    // Remove listeners after first engagement
                    engagementEvents.forEach(event => {
                        document.removeEventListener(event, trackEngagement);
                    });
                }
            };
            
            engagementEvents.forEach(event => {
                document.addEventListener(event, trackEngagement, { once: true });
            });
            
            // If no install prompt after 2 seconds, show manual install option
            setTimeout(() => {
                if (!deferredPrompt && !installButton && !localStorage.getItem('pwa-installed')) {
                    console.log('PWA: No install prompt detected, showing manual install option');
                    showManualInstallOption();
                }
            }, 2000);
        }

        function showInstallButton() {
            // Create install button if it doesn't exist
            if (!installButton) {
                installButton = document.createElement('button');
                installButton.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-download me-2"></i>
                        <span>Install App</span>
                        <div class="install-shine"></div>
                    </div>
                `;
                installButton.className = 'btn position-fixed install-btn';
                installButton.style.cssText = `
                    bottom: 20px;
                    right: 20px;
                    z-index: 1050;
                    border-radius: 24px;
                    padding: 0.75rem 1.25rem;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    background: #ffffff;
                    border: 1px solid #e0e0e0;
                    color: #667eea;
                    font-weight: 500;
                    font-size: 0.875rem;
                    letter-spacing: 0.2px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    animation: cleanPulse 4s ease-in-out infinite;
                    position: relative;
                    overflow: hidden;
                `;
                
                // Add clean button styles
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes cleanPulse {
                        0%, 100% { 
                            transform: scale(1);
                            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                        }
                        50% { 
                            transform: scale(1.02);
                            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                        }
                    }
                    
                    .install-btn:hover {
                        background: #667eea !important;
                        color: white !important;
                        border-color: #667eea !important;
                        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3) !important;
                        animation: none !important;
                    }
                    
                    .install-btn:active {
                        transform: scale(0.98) !important;
                        transition: all 0.1s ease !important;
                    }
                    
                    .install-btn i {
                        font-size: 0.875rem;
                    }
                    
                    .install-btn span {
                        font-weight: 500;
                    }
                    
                    @keyframes slideIn {
                        from {
                            transform: translateX(100%);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                `;
                document.head.appendChild(style);
                
                installButton.addEventListener('click', attemptDirectInstall);
                installButton.addEventListener('mouseenter', () => {
                    installButton.style.animation = 'none';
                });
                installButton.addEventListener('mouseleave', () => {
                    installButton.style.animation = 'cleanPulse 4s ease-in-out infinite';
                });
                
                document.body.appendChild(installButton);
                
                // Reduce intensity after 8 seconds but keep visible
                setTimeout(() => {
                    if (installButton && installButton.parentNode) {
                        installButton.style.opacity = '0.95';
                        installButton.style.animation = 'cleanPulse 6s ease-in-out infinite';
                    }
                }, 8000);
            }
        }

        function hideInstallButton() {
            console.log('PWA: Hiding install button');
            
            // Remove the main install button
            if (installButton && installButton.parentNode) {
                installButton.remove();
                installButton = null;
            }
            
            // Remove any other install-related elements
            document.querySelectorAll('.install-btn, .manual-install').forEach(btn => {
                if (btn.parentNode) {
                    btn.remove();
                }
            });
            
            // Remove install notifications
            document.querySelectorAll('.alert').forEach(alert => {
                if (alert.textContent.includes('Install') || 
                    alert.textContent.includes('install') ||
                    alert.textContent.includes('Add to Home')) {
                    alert.remove();
                }
            });
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

        // Add function to reset PWA installation state (for debugging/testing)
        window.resetPWAInstallState = function() {
            console.log('PWA: Resetting installation state');
            localStorage.removeItem('pwa-installed');
            localStorage.removeItem('pwa-install-date');
            location.reload();
        };

        // Monitor for app uninstallation
        window.addEventListener('beforeunload', () => {
            // If the app is being closed from standalone mode, it might be uninstalled
            if (window.matchMedia('(display-mode: standalone)').matches) {
                // Don't reset the flag immediately, as this could be just a normal close
                // The flag will be validated on next load
            }
        });

        // Periodically check if the app is still considered installed
        setInterval(async () => {
            if (localStorage.getItem('pwa-installed') === 'true') {
                // Double-check installation status
                const isStillInstalled = window.matchMedia('(display-mode: standalone)').matches ||
                                       window.navigator.standalone === true;
                
                if (!isStillInstalled && 'getInstalledRelatedApps' in navigator) {
                    try {
                        const relatedApps = await navigator.getInstalledRelatedApps();
                        if (relatedApps.length === 0) {
                            console.log('PWA: App appears to be uninstalled, resetting state');
                            localStorage.removeItem('pwa-installed');
                            localStorage.removeItem('pwa-install-date');
                            // Don't reload automatically, just reset the state
                        }
                    } catch (error) {
                        console.log('PWA: Error checking installation status:', error);
                    }
                }
            }
        }, 30000); // Check every 30 seconds

        function showInstallNotification() {
            const notification = document.createElement('div');
            notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 320px;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-radius: 10px;
            `;
            
            notification.innerHTML = `
                <div class="d-flex align-items-start">
                    <i class="bi bi-phone me-2 mt-1"></i>
                    <div class="flex-grow-1">
                        <strong>Install Daily Statement App</strong><br>
                        <small>Add to your home screen for quick access and offline use!</small>
                    </div>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 8 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 8000);
        }

        function showManualInstallOption() {
            // Create manual install button that tries to trigger native install
            if (!installButton) {
                installButton = document.createElement('button');
                installButton.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="bi bi-download me-2"></i>
                        <span>Install App</span>
                    </div>
                `;
                installButton.className = 'btn position-fixed install-btn manual-install';
                installButton.style.cssText = `
                    bottom: 20px;
                    right: 20px;
                    z-index: 1050;
                    border-radius: 24px;
                    padding: 0.75rem 1.25rem;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    background: #ffffff;
                    border: 1px solid #e0e0e0;
                    color: #667eea;
                    font-weight: 500;
                    font-size: 0.875rem;
                    letter-spacing: 0.2px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    animation: cleanPulse 4s ease-in-out infinite;
                    position: relative;
                    overflow: hidden;
                `;
                
                // Manual install uses same clean styles
                const manualStyle = document.createElement('style');
                manualStyle.textContent = `
                    .manual-install:hover {
                        background: #667eea !important;
                        color: white !important;
                        border-color: #667eea !important;
                        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3) !important;
                        animation: none !important;
                    }
                    
                    .manual-install:active {
                        transform: scale(0.98) !important;
                        transition: all 0.1s ease !important;
                    }
                `;
                document.head.appendChild(manualStyle);
                
                // Try to trigger native install or show instructions
                installButton.addEventListener('click', attemptDirectInstall);
                installButton.addEventListener('mouseenter', () => {
                    installButton.style.animation = 'none';
                });
                installButton.addEventListener('mouseleave', () => {
                    installButton.style.animation = 'cleanPulse 4s ease-in-out infinite';
                });
                
                document.body.appendChild(installButton);
            }
        }

        function attemptDirectInstall() {
            console.log('PWA: Attempting direct install...');
            
            // First try: Use deferred prompt if available (Chrome, Edge, etc.)
            if (deferredPrompt) {
                console.log('PWA: Using deferred prompt');
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('PWA: User accepted the install prompt');
                        hideInstallButton();
                    } else {
                        console.log('PWA: User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
                return;
            }
            
            // Second try: Force trigger install prompt for Chrome/Android
            const isChrome = /Chrome/.test(navigator.userAgent) && !/Edg/.test(navigator.userAgent);
            const isAndroid = /Android/.test(navigator.userAgent);
            
            if (isChrome && isAndroid) {
                console.log('PWA: Android Chrome detected, attempting to force install prompt');
                // Try to manually trigger the beforeinstallprompt event
                forceInstallPrompt();
                return;
            }
            
            // Third try: Check if browser supports install prompt
            if ('getInstalledRelatedApps' in navigator) {
                navigator.getInstalledRelatedApps().then((relatedApps) => {
                    if (relatedApps.length === 0) {
                        // App not installed, try to trigger install
                        triggerNativeInstall();
                    } else {
                        showAlreadyInstalledMessage();
                    }
                });
            } else {
                // Fourth try: Platform-specific install attempts
                triggerNativeInstall();
            }
        }

        function forceInstallPrompt() {
            // Try to force the install prompt on Android Chrome
            console.log('PWA: Forcing install prompt for Android Chrome');
            
            // First, check if PWA criteria are met
            if (checkPWACriteria()) {
                console.log('PWA: PWA criteria met, attempting to trigger install');
                
                // Try multiple methods to trigger the install prompt
                const methods = [
                    () => {
                        // Method 1: Try to dispatch a custom beforeinstallprompt event
                        const event = new Event('beforeinstallprompt');
                        event.preventDefault = () => {};
                        event.prompt = () => Promise.resolve();
                        event.userChoice = Promise.resolve({ outcome: 'accepted' });
                        window.dispatchEvent(event);
                    },
                    () => {
                        // Method 2: Check for Chrome's install API
                        if ('getInstalledRelatedApps' in navigator) {
                            navigator.getInstalledRelatedApps().then(apps => {
                                if (apps.length === 0) {
                                    showAndroidChromeDirectInstall();
                                }
                            });
                        } else {
                            showAndroidChromeDirectInstall();
                        }
                    },
                    () => {
                        // Method 3: Direct guidance
                        showAndroidChromeDirectInstall();
                    }
                ];
                
                // Try each method with delays
                methods.forEach((method, index) => {
                    setTimeout(method, index * 500);
                });
            } else {
                console.log('PWA: PWA criteria not fully met');
                showAndroidChromeDirectInstall();
            }
        }

        function checkPWACriteria() {
            // Check if basic PWA criteria are met
            const hasManifest = document.querySelector('link[rel="manifest"]');
            const hasServiceWorker = 'serviceWorker' in navigator;
            const isHTTPS = location.protocol === 'https:' || location.hostname === 'localhost';
            
            console.log('PWA: Criteria check:', {
                hasManifest: !!hasManifest,
                hasServiceWorker,
                isHTTPS
            });
            
            return hasManifest && hasServiceWorker && isHTTPS;
        }

        function showAndroidChromeDirectInstall() {
            // First, try one more time to trigger the native prompt
            if (deferredPrompt) {
                console.log('PWA: Found deferred prompt, triggering it now');
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('PWA: User accepted the install prompt');
                        hideInstallButton();
                    } else {
                        console.log('PWA: User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
                return;
            }
            
            const notification = document.createElement('div');
            notification.className = 'alert alert-warning alert-dismissible fade show position-fixed';
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 320px;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-radius: 10px;
                animation: slideIn 0.3s ease-out;
                border-left: 4px solid #ffc107;
            `;
            
            notification.innerHTML = `
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle me-2 mt-1" style="color: #856404; font-size: 1.2rem;"></i>
                    <div class="flex-grow-1">
                        <strong>Install Not Available</strong><br>
                        <small style="color: #856404;">
                            Chrome hasn't shown the install prompt yet.<br>
                            <strong>Try:</strong> Refresh the page or visit more pages<br>
                            <strong>Or:</strong> Menu (⋮) → "Add to Home screen"
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 12 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 12000);
        }

        function triggerNativeInstall() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
            const isAndroid = /Android/.test(navigator.userAgent);
            const isSafari = /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);
            
            console.log('PWA: Triggering native install for platform:', { isIOS, isAndroid, isSafari });
            
            if (isIOS && isSafari) {
                // iOS Safari - show specific instructions
                showIOSInstallPrompt();
            } else if (isAndroid) {
                // Android - try to trigger Chrome install prompt
                if ('BeforeInstallPromptEvent' in window) {
                    // Modern Android browsers
                    showAndroidInstallPrompt();
                } else {
                    // Fallback for older Android browsers
                    showAndroidInstallPrompt();
                }
            } else {
                // Desktop browsers
                showDesktopInstallPrompt();
            }
        }

        function showIOSInstallPrompt() {
            const notification = document.createElement('div');
            notification.className = 'alert alert-primary alert-dismissible fade show position-fixed';
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 320px;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-radius: 10px;
                animation: slideIn 0.3s ease-out;
            `;
            
            notification.innerHTML = `
                <div class="d-flex align-items-start">
                    <i class="bi bi-phone me-2 mt-1" style="color: #007AFF;"></i>
                    <div class="flex-grow-1">
                        <strong>Install on iPhone</strong><br>
                        <small>1. Tap the Share button <i class="bi bi-share"></i> below<br>
                        2. Scroll down and tap "Add to Home Screen"<br>
                        3. Tap "Add" to install the app</small>
                    </div>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 15 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 15000);
        }

        function showAndroidInstallPrompt() {
            const notification = document.createElement('div');
            notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 320px;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-radius: 10px;
                animation: slideIn 0.3s ease-out;
            `;
            
            notification.innerHTML = `
                <div class="d-flex align-items-start">
                    <i class="bi bi-android2 me-2 mt-1" style="color: #3DDC84;"></i>
                    <div class="flex-grow-1">
                        <strong>Install on Android</strong><br>
                        <small>1. Tap the menu (⋮) in your browser<br>
                        2. Look for "Add to Home screen" or "Install app"<br>
                        3. Tap "Install" to add the app</small>
                    </div>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 15 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 15000);
        }

        function showDesktopInstallPrompt() {
            const notification = document.createElement('div');
            notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 320px;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-radius: 10px;
                animation: slideIn 0.3s ease-out;
            `;
            
            notification.innerHTML = `
                <div class="d-flex align-items-start">
                    <i class="bi bi-laptop me-2 mt-1" style="color: #0078D4;"></i>
                    <div class="flex-grow-1">
                        <strong>Install on Desktop</strong><br>
                        <small>1. Look for the install icon <i class="bi bi-plus-square"></i> in your address bar<br>
                        2. Or use browser menu → "Install Daily Statement App"<br>
                        3. Click "Install" to add the app</small>
                    </div>
                    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 15 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 15000);
        }

        function showAlreadyInstalledMessage() {
            FormUtils.showSuccess('Daily Statement App is already installed on your device!');
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
