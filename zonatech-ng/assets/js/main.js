/**
 * ZonaTech NG - Main JavaScript
 * OPTIMIZED for blazing fast performance
 */

(function($) {
    'use strict';
    
    const ZonaTech = {
        init: function() {
            // Use requestIdleCallback for non-critical tasks
            if ('requestIdleCallback' in window) {
                requestIdleCallback(() => {
                    this.initScrollProgress();
                    this.initAnimations();
                });
            } else {
                setTimeout(() => {
                    this.initScrollProgress();
                    this.initAnimations();
                }, 1);
            }
            
            // Critical tasks immediately
            this.initDigitalClock();
            this.initTabs();
            this.initMobileMenu();
            this.initNotifications();
            this.initCalculator();
            this.initContentProtection();
        },
        
        // Scroll Progress Bar - OPTIMIZED with throttle
        initScrollProgress: function() {
            const progressBar = document.createElement('div');
            progressBar.className = 'scroll-progress';
            document.body.prepend(progressBar);
            
            let ticking = false;
            const updateProgress = () => {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                const scrollPercent = (scrollTop / docHeight) * 100;
                progressBar.style.width = scrollPercent + '%';
                ticking = false;
            };
            
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(updateProgress);
                    ticking = true;
                }
            }, { passive: true });
        },
        
        // Scroll Animations - OPTIMIZED with IntersectionObserver
        initAnimations: function() {
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            
            if (animatedElements.length === 0) return;
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animated');
                        observer.unobserve(entry.target); // Stop observing once animated
                    }
                });
            }, { threshold: 0.1, rootMargin: '50px' });
            
            animatedElements.forEach(el => observer.observe(el));
            
            // Add button animations with native JS
            document.querySelectorAll('.btn').forEach(btn => {
                btn.classList.add('btn-animated', 'btn-ripple');
            });
        },
        
        // Digital Clock
        initDigitalClock: function() {
            const clockElement = $('#digital-clock');
            if (!clockElement.length) return;
            
            const updateClock = () => {
                const now = new Date();
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12;
                hours = String(hours).padStart(2, '0');
                
                const options = { weekday: 'short', month: 'short', day: 'numeric' };
                const dateStr = now.toLocaleDateString('en-US', options);
                
                clockElement.find('.clock-time').text(`${hours}:${minutes}:${seconds}`);
                clockElement.find('.clock-date').text(dateStr);
                clockElement.find('.clock-ampm').text(ampm);
            };
            
            updateClock();
            setInterval(updateClock, 1000);
        },
        
        // Tab Navigation
        initTabs: function() {
            $('.tab-btn').on('click', function() {
                const tabId = $(this).data('tab');
                const tabGroup = $(this).closest('.tab-container');
                
                tabGroup.find('.tab-btn').removeClass('active');
                $(this).addClass('active');
                
                tabGroup.find('.tab-content').removeClass('active');
                tabGroup.find('#' + tabId).addClass('active');
            });
        },
        
        // Basic client-side content protection (deterrent only). Use .zonatech-allow-copy to allow copying.
        initContentProtection: function() {
            const allowedSelector = '.zonatech-allow-copy';
            const formControls = 'input, textarea, select, button';
            const isAllowedTarget = (target) => {
                if (!target || target.nodeType !== Node.ELEMENT_NODE) {
                    return false;
                }
                return !!target.closest(allowedSelector);
            };
            const $containers = $('.zonatech-container');
            $containers.find(formControls).addClass('zonatech-allow-copy');
            $(document).on('copy cut paste contextmenu selectstart', '.zonatech-container', function(e) {
                if (isAllowedTarget(e.target)) {
                    return;
                }
                e.preventDefault();
            });
        },
        
        // Mobile Menu
        initMobileMenu: function() {
            const toggle = $('.mobile-menu-toggle');
            const sidebar = $('.dashboard-sidebar');
            const overlay = $('.sidebar-overlay');
            
            toggle.on('click', function() {
                sidebar.toggleClass('active');
                overlay.toggleClass('active');
            });
            
            overlay.on('click', function() {
                sidebar.removeClass('active');
                overlay.removeClass('active');
            });
        },
        
        // Notifications
        initNotifications: function() {
            window.ZonaTechNotify = {
                show: function(message, type = 'info', duration = 4000) {
                    const container = $('#zonatech-notifications');
                    if (!container.length) {
                        $('body').append('<div id="zonatech-notifications" style="position: fixed; top: 20px; right: 20px; z-index: 99999;"></div>');
                    }
                    
                    const notification = $(`
                        <div class="zonatech-notification notification-enter" style="
                            padding: 1rem 1.25rem; 
                            margin-bottom: 0.75rem; 
                            border-radius: 0.75rem; 
                            max-width: 400px;
                            background: ${this.getBgColor(type)};
                            border: 2px solid ${this.getBorderColor(type)};
                            box-shadow: 0 4px 20px rgba(0,0,0,0.5), 0 0 20px ${this.getGlowColor(type)};
                        ">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <i class="fas ${this.getIcon(type)}" style="color: ${this.getColor(type)}; font-size: 1.25rem;"></i>
                                <span style="font-size: 0.95rem; font-weight: 600; color: ${this.getColor(type)}; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">${message}</span>
                            </div>
                        </div>
                    `);
                    
                    $('#zonatech-notifications').append(notification);
                    
                    setTimeout(() => {
                        notification.addClass('notification-exit');
                        setTimeout(() => notification.remove(), 300);
                    }, duration);
                },
                
                success: function(message, duration = 4000) {
                    this.show(message, 'success', duration);
                },
                
                error: function(message, duration = 4000) {
                    this.show(message, 'error', duration);
                },
                
                warning: function(message, duration = 4000) {
                    this.show(message, 'warning', duration);
                },
                
                info: function(message, duration = 4000) {
                    this.show(message, 'info', duration);
                },
                
                getIcon: function(type) {
                    const icons = {
                        success: 'fa-check-circle',
                        error: 'fa-exclamation-circle',
                        warning: 'fa-exclamation-triangle',
                        info: 'fa-info-circle'
                    };
                    return icons[type] || icons.info;
                },
                
                getColor: function(type) {
                    const colors = {
                        success: '#4ade80',
                        error: '#f87171',
                        warning: '#fbbf24',
                        info: '#60a5fa'
                    };
                    return colors[type] || colors.info;
                },
                
                getBgColor: function(type) {
                    const colors = {
                        success: 'rgba(34, 197, 94, 0.15)',
                        error: 'rgba(239, 68, 68, 0.15)',
                        warning: 'rgba(245, 158, 11, 0.15)',
                        info: 'rgba(59, 130, 246, 0.15)'
                    };
                    return colors[type] || colors.info;
                },
                
                getBorderColor: function(type) {
                    const colors = {
                        success: 'rgba(34, 197, 94, 0.5)',
                        error: 'rgba(239, 68, 68, 0.5)',
                        warning: 'rgba(245, 158, 11, 0.5)',
                        info: 'rgba(59, 130, 246, 0.5)'
                    };
                    return colors[type] || colors.info;
                },
                
                getGlowColor: function(type) {
                    const colors = {
                        success: 'rgba(34, 197, 94, 0.3)',
                        error: 'rgba(239, 68, 68, 0.3)',
                        warning: 'rgba(245, 158, 11, 0.3)',
                        info: 'rgba(59, 130, 246, 0.3)'
                    };
                    return colors[type] || colors.info;
                }
            };
        },
        
        // Global Scientific Calculator
        initCalculator: function() {
            // Calculator state variables
            let calcExpression = '';
            let calcResult = '0';
            let lastWasResult = false;
            
            // Toggle calculator panel
            $(document).on('click', '#calculator-toggle', function() {
                const panel = $('#calculator-panel');
                if (panel.is(':visible')) {
                    panel.slideUp(200);
                } else {
                    panel.slideDown(200);
                    $('#calc-display').val('0');
                }
            });
            
            // Close calculator
            $(document).on('click', '#calculator-close', function() {
                $('#calculator-panel').slideUp(200);
            });
            
            // Calculator button handling
            $(document).on('click', '.calc-btn', function() {
                const action = $(this).data('action');
                const display = $('#calc-display');
                
                // Clear
                if (action === 'clear') {
                    calcExpression = '';
                    calcResult = '0';
                    lastWasResult = false;
                    display.val('0');
                    return;
                }
                
                // Backspace
                if (action === 'backspace') {
                    if (lastWasResult) {
                        calcExpression = '';
                        calcResult = '0';
                        lastWasResult = false;
                    } else {
                        calcExpression = calcExpression.slice(0, -1);
                    }
                    display.val(calcExpression || '0');
                    return;
                }
                
                // If last action was result and user presses a number, start fresh
                if (lastWasResult && !isNaN(action)) {
                    calcExpression = action;
                    lastWasResult = false;
                    display.val(calcExpression);
                    return;
                }
                
                // If last action was result and user presses operator, continue from result
                if (lastWasResult && ['+', '-', '*', '/'].includes(action)) {
                    calcExpression = calcResult + action;
                    lastWasResult = false;
                    display.val(calcExpression);
                    return;
                }
                
                lastWasResult = false;
                
                // Scientific functions
                if (action === 'sin') {
                    try {
                        const val = parseFloat(calcExpression || calcResult);
                        calcResult = String(Math.sin(val * Math.PI / 180));
                        calcExpression = calcResult;
                        lastWasResult = true;
                        display.val(calcResult.substring(0, 12));
                    } catch(e) { display.val('Error'); }
                    return;
                }
                
                if (action === 'cos') {
                    try {
                        const val = parseFloat(calcExpression || calcResult);
                        calcResult = String(Math.cos(val * Math.PI / 180));
                        calcExpression = calcResult;
                        lastWasResult = true;
                        display.val(calcResult.substring(0, 12));
                    } catch(e) { display.val('Error'); }
                    return;
                }
                
                if (action === 'tan') {
                    try {
                        const val = parseFloat(calcExpression || calcResult);
                        calcResult = String(Math.tan(val * Math.PI / 180));
                        calcExpression = calcResult;
                        lastWasResult = true;
                        display.val(calcResult.substring(0, 12));
                    } catch(e) { display.val('Error'); }
                    return;
                }
                
                if (action === 'sqrt') {
                    try {
                        const val = parseFloat(calcExpression || calcResult);
                        calcResult = String(Math.sqrt(val));
                        calcExpression = calcResult;
                        lastWasResult = true;
                        display.val(calcResult.substring(0, 12));
                    } catch(e) { display.val('Error'); }
                    return;
                }
                
                if (action === 'pow') {
                    try {
                        const val = parseFloat(calcExpression || calcResult);
                        calcResult = String(Math.pow(val, 2));
                        calcExpression = calcResult;
                        lastWasResult = true;
                        display.val(calcResult.substring(0, 12));
                    } catch(e) { display.val('Error'); }
                    return;
                }
                
                if (action === 'pi') {
                    calcExpression += String(Math.PI);
                    display.val(calcExpression.substring(0, 15));
                    return;
                }
                
                if (action === 'percent') {
                    try {
                        const val = parseFloat(calcExpression || calcResult);
                        calcResult = String(val / 100);
                        calcExpression = calcResult;
                        lastWasResult = true;
                        display.val(calcResult.substring(0, 12));
                    } catch(e) { display.val('Error'); }
                    return;
                }
                
                if (action === 'negate') {
                    try {
                        const val = parseFloat(calcExpression || calcResult);
                        calcResult = String(-val);
                        calcExpression = calcResult;
                        lastWasResult = true;
                        display.val(calcResult.substring(0, 12));
                    } catch(e) { display.val('Error'); }
                    return;
                }
                
                // Equals - evaluate expression
                if (action === '=') {
                    try {
                        // Safe evaluation
                        calcResult = String(Function('"use strict"; return (' + calcExpression + ')')());
                        // Handle infinity and NaN
                        if (!isFinite(parseFloat(calcResult))) {
                            display.val('Error');
                            calcExpression = '';
                            calcResult = '0';
                        } else {
                            display.val(calcResult.substring(0, 12));
                            calcExpression = calcResult;
                            lastWasResult = true;
                        }
                    } catch(e) {
                        display.val('Error');
                        calcExpression = '';
                        calcResult = '0';
                    }
                    return;
                }
                
                // Numbers and operators
                calcExpression += action;
                display.val(calcExpression);
            });
            
            // Make calculator follow page scroll
            const widget = document.getElementById('calculator-widget');
            if (widget) {
                let scrollTicking = false;
                const updateCalculatorPosition = () => {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    widget.style.top = (scrollTop + 20) + 'px';
                    scrollTicking = false;
                };
                
                window.addEventListener('scroll', function() {
                    if (!scrollTicking) {
                        requestAnimationFrame(updateCalculatorPosition);
                        scrollTicking = true;
                    }
                }, { passive: true });
                
                // Set initial position
                updateCalculatorPosition();
            }
        },
        
        // AJAX Helper
        ajax: function(action, data, callback) {
            data = data || {};
            data.action = action;
            data.nonce = zonatech_ajax.nonce;
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (callback) callback(response);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    if (callback) callback({ success: false, data: { message: 'Request failed. Please try again.' }});
                }
            });
        },
        
        // Format currency
        formatCurrency: function(amount) {
            return '₦' + parseFloat(amount).toLocaleString('en-NG');
        },
        
        // Format date
        formatDate: function(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-NG', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
    
    // Expose globally
    window.ZonaTech = ZonaTech;
    
    // Initialize when DOM is ready
    $(function() {
        ZonaTech.init();
    });
    
})(jQuery);