/**
 * ZonaTech NG - PWA JavaScript
 * Rebuilt from scratch to fix install button issues
 */

(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        ZonaTechPWA.init();
    });
    
    const ZonaTechPWA = {
        deferredPrompt: null,
        isIOS: false,
        isAndroid: false,
        isChrome: false,
        isFirefox: false,
        isSafari: false,
        isEdge: false,
        isStandalone: false,
        initialized: false,
        
        init: function() {
            // Prevent double initialization
            if (this.initialized) return;
            this.initialized = true;
            
            console.log('ZonaTechPWA: Initializing...');
            
            this.detectBrowser();
            this.registerServiceWorker();
            this.handleInstallPrompt();
            this.initPromptUI();
            this.checkStandalone();
            
            console.log('ZonaTechPWA: Initialized successfully');
        },
        
        // Detect browser and platform
        detectBrowser: function() {
            const ua = navigator.userAgent.toLowerCase();
            
            this.isIOS = /iphone|ipad|ipod/.test(ua);
            this.isAndroid = /android/.test(ua);
            this.isChrome = /chrome/.test(ua) && !/edge|edg/.test(ua);
            this.isFirefox = /firefox/.test(ua);
            this.isSafari = /safari/.test(ua) && !/chrome/.test(ua);
            this.isEdge = /edge|edg/.test(ua);
            this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                               window.navigator.standalone === true;
            
            console.log('ZonaTechPWA: Browser detection - iOS:', this.isIOS, 'Android:', this.isAndroid, 'Chrome:', this.isChrome);
        },
        
        // Check if already installed
        checkStandalone: function() {
            if (this.isStandalone) {
                // App is already installed, hide install prompts
                $('#zonatech-pwa-prompt').hide();
                $('#download-app-btn').html('<i class="fas fa-check-circle"></i> App Installed');
                console.log('ZonaTechPWA: App already installed');
            }
        },
        
        // Register Service Worker
        registerServiceWorker: function() {
            if ('serviceWorker' in navigator && typeof zonatech_ajax !== 'undefined' && zonatech_ajax.sw_url) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register(zonatech_ajax.sw_url)
                        .then(function(registration) {
                            console.log('ZonaTechPWA: ServiceWorker registered:', registration.scope);
                        })
                        .catch(function(error) {
                            console.log('ZonaTechPWA: ServiceWorker registration failed:', error);
                        });
                });
            }
        },
        
        // Handle install prompt
        handleInstallPrompt: function() {
            const self = this;
            
            window.addEventListener('beforeinstallprompt', function(e) {
                // Prevent Chrome 67+ from showing prompt automatically
                e.preventDefault();
                
                // Store event for later use
                self.deferredPrompt = e;
                
                console.log('ZonaTechPWA: Install prompt captured');
                
                // Update download button to show it's available
                $('#download-app-btn').html('<i class="fas fa-download"></i> Install App Now').removeClass('disabled');
                
                // Check if user has dismissed before
                if (!localStorage.getItem('zonatech_pwa_dismissed')) {
                    self.showInstallPrompt();
                }
            });
            
            // Track when app is installed
            window.addEventListener('appinstalled', function() {
                console.log('ZonaTechPWA: App installed');
                self.deferredPrompt = null;
                self.hideInstallPrompt();
                $('#download-app-btn').html('<i class="fas fa-check-circle"></i> App Installed');
                if (typeof ZonaTechNotify !== 'undefined') {
                    ZonaTechNotify.show('App installed successfully!', 'success');
                }
            });
        },
        
        // Initialize prompt UI - using event delegation to ensure it works
        initPromptUI: function() {
            const self = this;
            
            // Remove any existing handlers first to prevent duplicates
            $(document).off('click.zonatechpwa');
            
            // Install button in popup prompt - using event delegation
            // return false in jQuery handlers does preventDefault() and stopPropagation()
            $(document).on('click.zonatechpwa', '#zonatech-pwa-install', function() {
                console.log('ZonaTechPWA: Install button clicked (popup)');
                self.installApp();
                return false;
            });
            
            // Download button in Download App section - using event delegation
            // This is the main "Install App Now" button on the homepage
            $(document).on('click.zonatechpwa', '#download-app-btn', function() {
                console.log('ZonaTechPWA: Download app button clicked');
                self.triggerInstall();
                return false;
            });
            
            // Dismiss button - using event delegation
            $(document).on('click.zonatechpwa', '#zonatech-pwa-dismiss', function() {
                console.log('ZonaTechPWA: Dismiss clicked');
                self.hideInstallPrompt();
                localStorage.setItem('zonatech_pwa_dismissed', 'true');
                
                // Reset after 7 days
                setTimeout(function() {
                    localStorage.removeItem('zonatech_pwa_dismissed');
                }, 7 * 24 * 60 * 60 * 1000);
                return false;
            });
            
            console.log('ZonaTechPWA: UI event handlers attached');
        },
        
        // Show install prompt
        showInstallPrompt: function() {
            $('#zonatech-pwa-prompt').fadeIn(300);
        },
        
        // Hide install prompt
        hideInstallPrompt: function() {
            $('#zonatech-pwa-prompt').fadeOut(300);
        },
        
        // Trigger install based on browser
        triggerInstall: function() {
            console.log('ZonaTechPWA: triggerInstall called, deferredPrompt:', !!this.deferredPrompt);
            
            if (this.deferredPrompt) {
                this.installApp();
            } else if (this.isIOS) {
                this.showIOSInstructions();
            } else if (this.isSafari) {
                this.showSafariInstructions();
            } else if (this.isFirefox) {
                this.showFirefoxInstructions();
            } else if (this.isEdge) {
                this.showEdgeInstructions();
            } else if (this.isChrome) {
                this.showChromeInstructions();
            } else {
                this.showGenericInstructions();
            }
        },
        
        // Install app using deferred prompt
        installApp: function() {
            const self = this;
            
            console.log('ZonaTechPWA: installApp called');
            
            if (!this.deferredPrompt) {
                console.log('ZonaTechPWA: No deferred prompt, showing instructions');
                // Don't recursively call triggerInstall - show generic instructions directly
                this.showGenericInstructions();
                return;
            }
            
            // Show install prompt
            this.deferredPrompt.prompt();
            
            // Wait for user response
            this.deferredPrompt.userChoice.then(function(choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    console.log('ZonaTechPWA: User accepted install');
                    if (typeof ZonaTechNotify !== 'undefined') {
                        ZonaTechNotify.show('Installing app...', 'success');
                    }
                } else {
                    console.log('ZonaTechPWA: User dismissed install');
                }
                self.deferredPrompt = null;
                self.hideInstallPrompt();
            });
        },
        
        // iOS instructions
        showIOSInstructions: function() {
            this.showInstructionsModal(
                'Install on iOS',
                '<ol>' +
                '<li>Tap the <strong>Share</strong> button <i class="fas fa-share-square"></i> at the bottom of the screen</li>' +
                '<li>Scroll down and tap <strong>"Add to Home Screen"</strong></li>' +
                '<li>Tap <strong>"Add"</strong> in the top right corner</li>' +
                '</ol>',
                'ios'
            );
        },
        
        // Safari (Mac) instructions
        showSafariInstructions: function() {
            this.showInstructionsModal(
                'Install on Safari',
                '<ol>' +
                '<li>Click <strong>File</strong> in the menu bar</li>' +
                '<li>Select <strong>"Add to Dock"</strong></li>' +
                '<li>Or use the Share menu and select <strong>"Add to Home Screen"</strong></li>' +
                '</ol>',
                'safari'
            );
        },
        
        // Firefox instructions
        showFirefoxInstructions: function() {
            this.showInstructionsModal(
                'Install on Firefox',
                '<ol>' +
                '<li>Click the <strong>install icon</strong> <i class="fas fa-plus-square"></i> in the address bar (right side)</li>' +
                '<li>If you don\'t see it, click the menu <i class="fas fa-bars"></i> and look for <strong>"Install"</strong></li>' +
                '<li>Click <strong>"Install"</strong> to add the app</li>' +
                '</ol>' +
                '<p><em>Note: Firefox on mobile may not support PWA installation on all devices.</em></p>',
                'firefox'
            );
        },
        
        // Edge instructions
        showEdgeInstructions: function() {
            this.showInstructionsModal(
                'Install on Edge',
                '<ol>' +
                '<li>Click the <strong>menu</strong> <i class="fas fa-ellipsis-h"></i> (three dots) in the top right</li>' +
                '<li>Select <strong>"Apps"</strong> from the menu</li>' +
                '<li>Click <strong>"Install this site as an app"</strong></li>' +
                '<li>Click <strong>"Install"</strong> in the popup</li>' +
                '</ol>',
                'edge'
            );
        },
        
        // Chrome instructions
        showChromeInstructions: function() {
            this.showInstructionsModal(
                'Install on Chrome',
                '<ol>' +
                '<li>Click the <strong>menu</strong> <i class="fas fa-ellipsis-v"></i> (three dots) in the top right corner</li>' +
                '<li>On desktop: Look for <strong>"Install ZonaTech NG"</strong> or <strong>"Install app"</strong></li>' +
                '<li>On mobile: Tap <strong>"Add to Home screen"</strong></li>' +
                '<li>Click <strong>"Install"</strong> to confirm</li>' +
                '</ol>' +
                '<p><em>You may also see an install icon <i class="fas fa-plus-square"></i> in the address bar.</em></p>',
                'chrome'
            );
        },
        
        // Generic instructions
        showGenericInstructions: function() {
            this.showInstructionsModal(
                'Install App',
                '<p>To install the ZonaTech NG app:</p>' +
                '<ul>' +
                '<li><strong>Chrome/Edge:</strong> Click menu (⋮) → "Install app" or "Add to Home screen"</li>' +
                '<li><strong>Safari iOS:</strong> Tap Share → "Add to Home Screen"</li>' +
                '<li><strong>Firefox:</strong> Look for install icon in address bar</li>' +
                '</ul>' +
                '<p>The app will appear on your home screen or in your apps list.</p>',
                'generic'
            );
        },
        
        // Show instructions modal
        showInstructionsModal: function(title, content, browser) {
            // Remove existing modal
            $('.pwa-instructions-modal').remove();
            
            const modal = $('<div class="pwa-instructions-modal glass-effect">' +
                '<div class="pwa-modal-content">' +
                '<button class="pwa-modal-close"><i class="fas fa-times"></i></button>' +
                '<div class="pwa-modal-icon"><i class="' + this.getBrowserIcon(browser) + '"></i></div>' +
                '<h3>' + title + '</h3>' +
                '<div class="pwa-modal-body">' + content + '</div>' +
                '<button class="btn btn-primary pwa-modal-ok">Got it!</button>' +
                '</div>' +
                '</div>');
            
            $('body').append(modal);
            
            // Add styles if not already present
            if (!$('#pwa-modal-styles').length) {
                $('head').append('<style id="pwa-modal-styles">' +
                    '.pwa-instructions-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 99999; padding: 1rem; }' +
                    '.pwa-modal-content { background: rgba(26, 26, 46, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 1rem; padding: 2rem; max-width: 400px; width: 100%; position: relative; animation: modalSlideIn 0.3s ease; }' +
                    '@keyframes modalSlideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }' +
                    '.pwa-modal-close { position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: #fff; font-size: 1.25rem; cursor: pointer; opacity: 0.7; transition: opacity 0.2s; }' +
                    '.pwa-modal-close:hover { opacity: 1; }' +
                    '.pwa-modal-icon { font-size: 3rem; color: #8b5cf6; text-align: center; margin-bottom: 1rem; }' +
                    '.pwa-modal-content h3 { text-align: center; color: #fff; margin-bottom: 1rem; }' +
                    '.pwa-modal-body { color: #e5e5e5; font-size: 0.9rem; margin-bottom: 1.5rem; }' +
                    '.pwa-modal-body ol, .pwa-modal-body ul { padding-left: 1.5rem; margin: 0.5rem 0; }' +
                    '.pwa-modal-body li { margin-bottom: 0.5rem; }' +
                    '.pwa-modal-body strong { color: #a78bfa; }' +
                    '.pwa-modal-body em { color: #888; font-size: 0.85rem; }' +
                    '.pwa-modal-ok { width: 100%; }' +
                '</style>');
            }
            
            // Close handlers
            modal.find('.pwa-modal-close, .pwa-modal-ok').on('click', function() {
                modal.fadeOut(200, function() { modal.remove(); });
            });
            
            modal.on('click', function(e) {
                if ($(e.target).hasClass('pwa-instructions-modal')) {
                    modal.fadeOut(200, function() { modal.remove(); });
                }
            });
        },
        
        // Get browser icon
        getBrowserIcon: function(browser) {
            const icons = {
                'ios': 'fab fa-apple',
                'safari': 'fab fa-safari',
                'firefox': 'fab fa-firefox',
                'edge': 'fab fa-edge',
                'chrome': 'fab fa-chrome',
                'generic': 'fas fa-download'
            };
            return icons[browser] || icons.generic;
        },
        
        // Check if running as PWA
        isPWA: function() {
            return window.matchMedia('(display-mode: standalone)').matches || 
                   window.navigator.standalone === true;
        }
    };
    
    window.ZonaTechPWA = ZonaTechPWA;
    
})(jQuery);