<?php
/**
 * Register Template
 * 
 * User registration form - creates account directly and redirects to login
 */

if (!defined('ABSPATH')) exit;
?>

<div class="zonatech-container">
    <!-- Loading Screen -->
    <div id="zonatech-loading-screen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2>ZonaTech NG</h2>
            <p>Loading...</p>
        </div>
    </div>

    <div class="zonatech-wrapper">
        <!-- Back to Home -->
        <div class="back-to-home">
            <a href="<?php echo esc_url(site_url()); ?>" class="btn btn-ghost btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
        
        <!-- Registration Form -->
        <div class="auth-card glass-effect" id="register-card">
            <div class="auth-header">
                <a href="<?php echo esc_url(site_url()); ?>" class="zonatech-logo mb-2">
                    <img src="<?php echo esc_url(ZONATECH_PLUGIN_URL . 'assets/images/logo.png'); ?>" alt="ZonaTech NG" class="zonatech-logo-img">
                    <span>ZonaTech NG</span>
                </a>
                <h2 class="text-white"><i class="fas fa-user-plus"></i> Create Account</h2>
                <p class="text-muted">Join thousands of students preparing for success</p>
            </div>
            
            <form id="zonatech-register-form" novalidate>
                <div class="row">
                    <div class="col col-sm-12" style="flex: 1; min-width: 140px;">
                        <div class="form-group">
                            <label for="first_name" class="text-white"><i class="fas fa-user"></i> First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" placeholder="First name" required autocomplete="given-name">
                        </div>
                    </div>
                    <div class="col col-sm-12" style="flex: 1; min-width: 140px;">
                        <div class="form-group">
                            <label for="last_name" class="text-white"><i class="fas fa-user"></i> Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Last name" required autocomplete="family-name">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="text-white"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="text-white"><i class="fas fa-phone"></i> Phone Number <span class="text-muted">(Optional)</span></label>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="e.g., 08012345678" autocomplete="tel">
                </div>
                
                <div class="form-group">
                    <label for="reg_password" class="text-white"><i class="fas fa-lock"></i> Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="reg_password" class="form-control" placeholder="Create a password" required autocomplete="new-password" style="padding-right: 45px;">
                        <button type="button" class="password-toggle-btn" data-target="reg_password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #8b5cf6; padding: 5px; z-index: 2; font-size: 1.1rem;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="reg_confirm_password" class="text-white"><i class="fas fa-lock"></i> Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" id="reg_confirm_password" class="form-control" placeholder="Confirm your password" required autocomplete="new-password" style="padding-right: 45px;">
                        <button type="button" class="password-toggle-btn" data-target="reg_confirm_password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #8b5cf6; padding: 5px; z-index: 2; font-size: 1.1rem;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="terms" id="terms" required style="width: auto; margin-top: 0.25rem; accent-color: #8b5cf6;">
                        <span style="font-size: 0.8rem; line-height: 1.5;" class="text-white">
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;" id="register-submit-btn">
                    <i class="fas fa-user-plus"></i> <span>Create Account</span>
                </button>
            </form>
            
            <div class="auth-divider">
                <span>or</span>
            </div>
            
            <p class="text-center text-muted" style="font-size: 0.85rem;">
                Already have an account? 
                <a href="<?php echo esc_url(site_url('/zonatech-login/')); ?>"><i class="fas fa-sign-in-alt"></i> Sign in</a>
            </p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Hide loading screen with fade effect
    setTimeout(function() {
        $('#zonatech-loading-screen').addClass('fade-out');
        setTimeout(function() {
            $('#zonatech-loading-screen').hide();
        }, 300);
    }, 100);
    
    // Password visibility toggle
    $(document).on('click', '.password-toggle-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var targetId = $(this).data('target');
        var $input = $('#' + targetId);
        var $icon = $(this).find('i');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Notification helper
    function showNotification(message, type) {
        if (typeof ZonaTechNotify !== 'undefined' && ZonaTechNotify) {
            if (type === 'success') {
                ZonaTechNotify.success(message);
            } else if (type === 'error') {
                ZonaTechNotify.error(message);
            } else {
                ZonaTechNotify.show(message, type);
            }
        } else {
            alert(message);
        }
    }
    
    // Form validation
    function validateForm() {
        var firstName = $.trim($('#first_name').val());
        var lastName = $.trim($('#last_name').val());
        var email = $.trim($('#email').val());
        var password = $('#reg_password').val();
        var confirmPassword = $('#reg_confirm_password').val();
        var termsChecked = $('#terms').is(':checked');
        
        if (!firstName) {
            showNotification('Please enter your first name.', 'error');
            $('#first_name').focus();
            return false;
        }
        if (!lastName) {
            showNotification('Please enter your last name.', 'error');
            $('#last_name').focus();
            return false;
        }
        if (!email) {
            showNotification('Please enter your email address.', 'error');
            $('#email').focus();
            return false;
        }
        // Email format validation
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            showNotification('Please enter a valid email address.', 'error');
            $('#email').focus();
            return false;
        }
        if (!password) {
            showNotification('Please enter a password.', 'error');
            $('#reg_password').focus();
            return false;
        }
        if (password !== confirmPassword) {
            showNotification('Passwords do not match.', 'error');
            $('#reg_confirm_password').focus();
            return false;
        }
        if (!termsChecked) {
            showNotification('Please accept the Terms of Service and Privacy Policy.', 'error');
            return false;
        }
        
        return true;
    }
    
    // Form submission
    $('#zonatech-register-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form first
        if (!validateForm()) {
            return false;
        }
        
        var $form = $(this);
        var $btn = $('#register-submit-btn');
        var originalHtml = $btn.html();
        
        // Disable button and show loading
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating Account...');
        
        // Prepare form data
        var formData = {
            action: 'zonatech_register',
            nonce: zonatech_ajax.nonce,
            first_name: $.trim($('#first_name').val()),
            last_name: $.trim($('#last_name').val()),
            email: $.trim($('#email').val()),
            phone: $.trim($('#phone').val()),
            password: $('#reg_password').val(),
            confirm_password: $('#reg_confirm_password').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 60000, // 60 second timeout
            cache: false,
            success: function(response) {
                if (response && response.success === true) {
                    showNotification(response.data.message || 'Account created successfully!', 'success');
                    
                    // Clear form
                    $form[0].reset();
                    
                    // Redirect to login after short delay
                    var redirectUrl = (response.data && response.data.redirect) ? response.data.redirect : '<?php echo esc_url(site_url('/zonatech-login/')); ?>';
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 1500);
                } else {
                    // Error response
                    var errorMsg = 'Registration failed. Please try again.';
                    if (response && response.data && response.data.message) {
                        errorMsg = response.data.message;
                    }
                    showNotification(errorMsg, 'error');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr, status, error) {
                console.error('Registration AJAX Error:', status, error, xhr.responseText);
                
                var errorMsg = 'Connection error. Please try again.';
                
                if (status === 'timeout') {
                    errorMsg = 'Request timed out. Please try again.';
                } else if (xhr.status === 0) {
                    errorMsg = 'No internet connection. Please check and try again.';
                } else if (xhr.status === 403) {
                    errorMsg = 'Session expired. Please refresh the page.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error. Please try again later.';
                } else if (xhr.responseText) {
                    // Try to parse response for error message
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp && resp.data && resp.data.message) {
                            errorMsg = resp.data.message;
                        }
                    } catch (parseErr) {
                        // Ignore parse error
                    }
                }
                
                showNotification(errorMsg, 'error');
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
        
        return false;
    });
});
</script>