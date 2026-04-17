/**
 * ZonaTech NG - Authentication JavaScript
 */

(function($) {
    'use strict';
    
    // Helper function for notifications with fallback
    function showNotification(message, type) {
        if (typeof ZonaTechNotify !== 'undefined' && typeof ZonaTechNotify.show === 'function') {
            ZonaTechNotify.show(message, type);
        } else {
            alert(message);
        }
    }
    
    // Define ZonaTechAuth object first, then initialize
    const ZonaTechAuth = {
        init: function() {
            this.initLoginForm();
            this.initRegisterForm();
            this.initResetPasswordForm();
            this.initProfileForm();
            this.initChangePasswordForm();
            this.initLogout();
        },

        refreshNonce: function() {
            return $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: { action: 'zonatech_refresh_nonce', current_nonce: zonatech_ajax.nonce }
            });
        },
        
        // Login Form
        initLoginForm: function() {
            $('#zonatech-login-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                const nonceRetry = form.data('nonce-retry') === true;
                const handleLoginError = (message) => {
                    form.removeData('nonce-retry');
                    showNotification(message || 'Login failed. Please try again.', 'error');
                    submitBtn.prop('disabled', false).html(originalText);
                };
                
                // Prevent double submission
                if (submitBtn.prop('disabled')) return;
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Logging in...');
                
                const data = {
                    action: 'zonatech_login',
                    nonce: zonatech_ajax.nonce,
                    email: $.trim(form.find('[name="email"]').val()),
                    password: form.find('[name="password"]').val(),
                    remember: form.find('[name="remember"]').is(':checked') ? 'true' : 'false'
                };
                
                // Client-side validation
                if (!data.email || !data.password) {
                    handleLoginError('Email and password are required.');
                    return;
                }
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    timeout: 30000,
                    success: function(response) {
                        if (response.success) {
                            form.removeData('nonce-retry');
                            showNotification(response.data.message || 'Login successful!', 'success');
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        } else {
                            const errorCode = response.data && response.data.code ? response.data.code : '';
                            if (errorCode === 'nonce_invalid') {
                                if (nonceRetry) {
                                    handleLoginError(response.data.message);
                                    return;
                                }
                                ZonaTechAuth.refreshNonce().done(function(result) {
                                    if (result && result.data && result.data.nonce) {
                                        zonatech_ajax.nonce = result.data.nonce;
                                        form.data('nonce-retry', true);
                                        form.trigger('submit');
                                        return;
                                    }
                                    handleLoginError(response.data.message);
                                }).fail(function() {
                                    handleLoginError(response.data.message);
                                });
                                return;
                            }
                            handleLoginError(response.data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Login error:', status, error);
                        let errorMessage = 'An error occurred. Please try again.';
                        
                        if (status === 'timeout') {
                            errorMessage = 'Request timed out. Please check your connection and try again.';
                        } else if (xhr.status === 0) {
                            errorMessage = 'No internet connection. Please check your network.';
                        } else if (xhr.status === 403) {
                            errorMessage = 'Session expired. Please refresh the page and try again.';
                        } else if (xhr.status >= 500) {
                            errorMessage = 'Server error. Please try again later.';
                        }
                        
                        if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response && response.data && response.data.message) {
                                    errorMessage = response.data.message;
                                    const errorCode = response.data.code || '';
                                    if (errorCode === 'nonce_invalid') {
                                        if (nonceRetry) {
                                            handleLoginError(errorMessage);
                                            return;
                                        }
                                        ZonaTechAuth.refreshNonce().done(function(result) {
                                            if (result && result.data && result.data.nonce) {
                                                zonatech_ajax.nonce = result.data.nonce;
                                                form.data('nonce-retry', true);
                                                form.trigger('submit');
                                            } else {
                                                handleLoginError(errorMessage);
                                            }
                                        }).fail(function() {
                                            handleLoginError('Session expired. Please refresh the page.');
                                        });
                                        return;
                                    }
                                }
                            } catch (parseError) {
                                console.warn('Failed to parse error response:', parseError);
                            }
                        }
                        handleLoginError(errorMessage);
                    }
                });
            });
        },
        
        // Register Form
        initRegisterForm: function() {
            $('#zonatech-register-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                const nonceRetry = form.data('nonce-retry') === true;
                
                const handleRegisterError = (message) => {
                    form.removeData('nonce-retry');
                    showNotification(message || 'Registration failed. Please try again.', 'error');
                    submitBtn.prop('disabled', false).html(originalText);
                };

                // Basic validation
                const password = form.find('[name="password"]').val();
                const confirmPassword = form.find('[name="confirm_password"]').val();
                
                if (password !== confirmPassword) {
                    showNotification('Passwords do not match.', 'error');
                    return;
                }
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating account...');
                
                const data = {
                    action: 'zonatech_register',
                    nonce: zonatech_ajax.nonce,
                    first_name: form.find('[name="first_name"]').val(),
                    last_name: form.find('[name="last_name"]').val(),
                    email: form.find('[name="email"]').val(),
                    phone: form.find('[name="phone"]').val(),
                    password: password,
                    confirm_password: confirmPassword
                };
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.data.message, 'success');
                            setTimeout(() => {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        } else {
                            const errorCode = response.data && response.data.code ? response.data.code : '';
                            if (errorCode === 'nonce_invalid') {
                                if (nonceRetry) {
                                    handleRegisterError(response.data.message);
                                    return;
                                }
                                ZonaTechAuth.refreshNonce().done(function(result) {
                                    if (result && result.data && result.data.nonce) {
                                        zonatech_ajax.nonce = result.data.nonce;
                                        form.data('nonce-retry', true);
                                        form.trigger('submit');
                                        return;
                                    }
                                    handleRegisterError(response.data.message);
                                }).fail(function() {
                                    handleRegisterError(response.data.message);
                                });
                                return;
                            }
                            handleRegisterError(response.data.message);
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'An error occurred. Please try again.';
                        let errorCode = '';
                        if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response && response.data && response.data.message) {
                                    errorMsg = response.data.message;
                                    errorCode = response.data.code || '';
                                }
                            } catch (parseError) {
                                console.warn('Failed to parse error response:', parseError);
                            }
                        }
                        if (errorCode === 'nonce_invalid') {
                            if (nonceRetry) {
                                handleRegisterError(errorMsg);
                                return;
                            }
                            ZonaTechAuth.refreshNonce().done(function(result) {
                                if (result && result.data && result.data.nonce) {
                                    zonatech_ajax.nonce = result.data.nonce;
                                    form.data('nonce-retry', true);
                                    form.trigger('submit');
                                    return;
                                }
                                handleRegisterError(errorMsg);
                            }).fail(function() {
                                handleRegisterError(errorMsg);
                            });
                            return;
                        }
                        handleRegisterError(errorMsg);
                    }
                });
            });
        },
        
        // Reset Password Form
        initResetPasswordForm: function() {
            $('#zonatech-reset-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zonatech_reset_password',
                        nonce: zonatech_ajax.nonce,
                        email: form.find('[name="email"]').val()
                    },
                    success: function(response) {
                        showNotification(response.data.message, response.success ? 'success' : 'info');
                        submitBtn.prop('disabled', false).html(originalText);
                        if (response.success) {
                            form[0].reset();
                        }
                    },
                    error: function() {
                        showNotification('An error occurred. Please try again.', 'error');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        },
        
        // Profile Update Form
        initProfileForm: function() {
            $('#zonatech-profile-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zonatech_update_profile',
                        nonce: zonatech_ajax.nonce,
                        first_name: form.find('[name="first_name"]').val(),
                        last_name: form.find('[name="last_name"]').val(),
                        phone: form.find('[name="phone"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.data.message, 'success');
                        } else {
                            showNotification(response.data.message, 'error');
                        }
                        submitBtn.prop('disabled', false).html(originalText);
                    },
                    error: function() {
                        showNotification('An error occurred. Please try again.', 'error');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        },
        
        // Change Password Form
        initChangePasswordForm: function() {
            $('#zonatech-change-password-form').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                const newPassword = form.find('[name="new_password"]').val();
                const confirmPassword = form.find('[name="confirm_password"]').val();
                
                if (newPassword !== confirmPassword) {
                    showNotification('New passwords do not match.', 'error');
                    return;
                }
                
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Changing...');
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zonatech_change_password',
                        nonce: zonatech_ajax.nonce,
                        current_password: form.find('[name="current_password"]').val(),
                        new_password: newPassword,
                        confirm_password: confirmPassword
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.data.message, 'success');
                            form[0].reset();
                        } else {
                            showNotification(response.data.message, 'error');
                        }
                        submitBtn.prop('disabled', false).html(originalText);
                    },
                    error: function() {
                        showNotification('An error occurred. Please try again.', 'error');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        },
        
        // Logout
        initLogout: function() {
            $(document).on('click', '.zonatech-logout', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to logout?')) return;
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zonatech_logout',
                        nonce: zonatech_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.data.redirect;
                        }
                    }
                });
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        ZonaTechAuth.init();
    });
    
    window.ZonaTechAuth = ZonaTechAuth;
    
})(jQuery);