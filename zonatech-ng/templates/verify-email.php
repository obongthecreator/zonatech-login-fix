<?php
/**
 * Email Verification Template
 * 
 * Handles verification code input after registration
 */

if (!defined('ABSPATH')) exit;

// Get pending user ID from URL
$pending_user_id = isset($_GET['pending_id']) ? intval($_GET['pending_id']) : 0;
$pending_email = isset($_GET['email']) ? sanitize_email(urldecode($_GET['email'])) : '';
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
        <!-- Email Verification Card -->
        <div class="auth-card glass-effect" id="verification-card">
            <div class="auth-header">
                <div class="zonatech-logo mb-2">
                    <img src="<?php echo esc_url(ZONATECH_PLUGIN_URL . 'assets/images/logo.png'); ?>" alt="ZonaTech NG" class="zonatech-logo-img">
                    <span>ZonaTech NG</span>
                </div>
                <div style="font-size: 3rem; color: var(--zona-purple); margin-bottom: 1rem;">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <h2 class="text-white"><i class="fas fa-shield-alt"></i> Verify Your Email</h2>
                <p class="text-muted">We've sent a 6-digit verification code to your email address<?php echo $pending_email ? ' (' . esc_html($pending_email) . ')' : ''; ?>. Please enter it below.</p>
            </div>
            
            <form id="zonatech-verify-form" novalidate>
                <input type="hidden" name="pending_user_id" id="pending_user_id" value="<?php echo esc_attr($pending_user_id); ?>">
                
                <div class="form-group">
                    <label for="verification_code" class="text-white"><i class="fas fa-key"></i> Verification Code</label>
                    <div class="input-with-icon">
                        <i class="fas fa-key input-icon"></i>
                        <input type="text" name="verification_code" id="verification_code" class="form-control form-control-icon" placeholder="Enter 6-digit code" maxlength="6" pattern="\d{6}" inputmode="numeric" required style="letter-spacing: 8px; text-align: center; font-size: 1.5rem; font-weight: bold;" autocomplete="one-time-code">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;" id="verify-submit-btn">
                    <i class="fas fa-check-circle"></i> <span>Verify Email</span>
                </button>
                
                <div class="mt-2 text-center">
                    <p class="text-muted" style="font-size: 0.85rem;">
                        Didn't receive the code? 
                        <a href="#" id="resend-code-link"><i class="fas fa-redo"></i> Resend Code</a>
                    </p>
                </div>
            </form>
            
            <div class="auth-divider">
                <span>or</span>
            </div>
            
            <p class="text-center text-muted" style="font-size: 0.85rem;">
                <a href="<?php echo esc_url(site_url('/zonatech-register/')); ?>"><i class="fas fa-arrow-left"></i> Back to Registration</a>
            </p>
        </div>
        
        <!-- Success Card (Hidden by default) -->
        <div class="auth-card glass-effect" id="success-card" style="display: none;">
            <div class="auth-header">
                <div class="zonatech-logo mb-2">
                    <img src="<?php echo esc_url(ZONATECH_PLUGIN_URL . 'assets/images/logo.png'); ?>" alt="ZonaTech NG" class="zonatech-logo-img">
                    <span>ZonaTech NG</span>
                </div>
                <div style="font-size: 4rem; color: var(--zona-success); margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="text-white">Account Verified!</h2>
                <p class="text-muted">Your email has been verified successfully. You can now login to your account.</p>
            </div>
            
            <a href="<?php echo esc_url(site_url('/zonatech-login/')); ?>" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="fas fa-sign-in-alt"></i> <span>Login Now</span>
            </a>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';
    
    // Hide loading screen
    requestAnimationFrame(function() {
        $('#zonatech-loading-screen').addClass('fade-out');
        setTimeout(function() {
            $('#zonatech-loading-screen').hide();
        }, 100);
    });
    
    // Show notification helper
    function showNotification(message, type) {
        if (typeof ZonaTechNotify !== 'undefined') {
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
    
    // Auto-focus on verification code input
    $('#verification_code').focus();
    
    // Only allow numbers in verification code
    $('#verification_code').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    // Handle verification form submission
    $('#zonatech-verify-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $('#verify-submit-btn');
        var originalText = $btn.html();
        var pendingUserId = $('#pending_user_id').val();
        var verificationCode = $.trim($form.find('[name="verification_code"]').val());
        
        console.log('Verifying email for pending_user_id:', pendingUserId, 'code:', verificationCode);
        
        // Validate pending user ID
        if (!pendingUserId || pendingUserId === '0' || parseInt(pendingUserId, 10) <= 0) {
            showNotification('Invalid verification session. Please register again.', 'error');
            setTimeout(function() {
                window.location.href = '<?php echo esc_url(site_url('/zonatech-register/')); ?>';
            }, 2000);
            return;
        }
        
        // Validate verification code
        if (!verificationCode || verificationCode.length !== 6 || !/^\d{6}$/.test(verificationCode)) {
            showNotification('Please enter a valid 6-digit verification code.', 'error');
            return;
        }
        
        // Check if zonatech_ajax is defined
        if (typeof zonatech_ajax === 'undefined') {
            console.error('zonatech_ajax is not defined');
            showNotification('Page configuration error. Please refresh the page and try again.', 'error');
            return;
        }
        
        // Disable button and show loading
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
        
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            timeout: 60000, // 60 second timeout for verification
            data: {
                action: 'zonatech_verify_email',
                nonce: zonatech_ajax.nonce,
                pending_user_id: pendingUserId,
                verification_code: verificationCode
            },
            success: function(response) {
                console.log('Verification response:', response);
                
                if (response && response.success) {
                    // Show success notification
                    showNotification(response.data.message || 'Email verified successfully!', 'success');
                    
                    // Show success card
                    $('#verification-card').fadeOut(300, function() {
                        $('#success-card').fadeIn(300);
                    });
                    
                    // Auto-redirect to login after 2 seconds
                    setTimeout(function() {
                        var redirectUrl = (response.data && response.data.redirect) 
                            ? response.data.redirect 
                            : '<?php echo esc_url(site_url('/zonatech-login/')); ?>';
                        window.location.href = redirectUrl;
                    }, 2000);
                } else {
                    var errorMsg = (response && response.data && response.data.message) 
                        ? response.data.message 
                        : 'Verification failed. Please try again.';
                    showNotification(errorMsg, 'error');
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error, xhr.responseText);
                var errorMessage = 'An error occurred. Please try again.';
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (xhr.status === 0) {
                    errorMessage = 'Unable to connect to server. Please check your internet connection.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Session expired. Please refresh the page and try again.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
                
                // Try to parse response for more specific error
                if (xhr.responseText) {
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp.data && resp.data.message) {
                            errorMessage = resp.data.message;
                        }
                    } catch(parseError) {
                        console.warn('Could not parse error response');
                    }
                }
                
                showNotification(errorMessage, 'error');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Resend verification code
    $('#resend-code-link').on('click', function(e) {
        e.preventDefault();
        
        var pendingUserId = $('#pending_user_id').val();
        
        if (!pendingUserId || pendingUserId === '0' || parseInt(pendingUserId, 10) <= 0) {
            showNotification('Invalid verification session. Please register again.', 'error');
            setTimeout(function() {
                window.location.href = '<?php echo esc_url(site_url('/zonatech-register/')); ?>';
            }, 2000);
            return;
        }
        
        // Check if zonatech_ajax is defined
        if (typeof zonatech_ajax === 'undefined') {
            console.error('zonatech_ajax is not defined');
            showNotification('Page configuration error. Please refresh the page.', 'error');
            return;
        }
        
        var $link = $(this);
        var originalHtml = $link.html();
        $link.html('<i class="fas fa-spinner fa-spin"></i> Sending...');
        
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            timeout: 30000,
            data: {
                action: 'zonatech_resend_verification',
                nonce: zonatech_ajax.nonce,
                pending_user_id: pendingUserId
            },
            success: function(response) {
                console.log('Resend response:', response);
                if (response && response.success) {
                    showNotification(response.data.message || 'New code sent!', 'success');
                } else {
                    var errorMsg = (response && response.data && response.data.message) 
                        ? response.data.message 
                        : 'Failed to resend code. Please try again.';
                    showNotification(errorMsg, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $link.html(originalHtml);
            }
        });
    });
});
</script>