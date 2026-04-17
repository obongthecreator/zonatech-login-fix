<?php
/**
 * Login Template
 */

if (!defined('ABSPATH')) exit;

// Check for session expired message
$session_expired = isset($_GET['session_expired']) && $_GET['session_expired'] == '1';
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
            <a href="<?php echo site_url(); ?>" class="btn btn-ghost btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
        
        <!-- Header -->
        <div class="zonatech-header glass-effect">
            <a href="<?php echo site_url(); ?>" class="zonatech-logo">
                <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                <span>ZonaTech NG</span>
            </a>
            <nav class="zonatech-nav">
                <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
                <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
                <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Register</a>
            </nav>
            
            <!-- Hamburger Menu -->
            <div class="hamburger-menu" id="hamburger-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        
        <!-- Mobile Navigation Overlay -->
        <div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>
        
        <!-- Mobile Navigation -->
        <nav class="mobile-nav" id="mobile-nav">
            <div class="mobile-nav-header">
                <a href="<?php echo site_url(); ?>" class="zonatech-logo">
                    <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                    <span>ZonaTech NG</span>
                </a>
                <button class="mobile-nav-close" id="mobile-nav-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
            <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
            <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>"><i class="fas fa-credit-card"></i> Scratch Cards</a>
            <a href="<?php echo site_url('/zonatech-nin-service/'); ?>"><i class="fas fa-id-card"></i> NIN Service</a>
            <a href="<?php echo site_url('/zonatech-login/'); ?>" class="active"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="<?php echo site_url('/zonatech-register/'); ?>"><i class="fas fa-user-plus"></i> Create Account</a>
        </nav>
        
        <div class="auth-card glass-effect">
            <div class="auth-header">
                <a href="<?php echo site_url(); ?>" class="zonatech-logo mb-2">
                    <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                    <span>ZonaTech NG</span>
                </a>
                <h2 class="text-white"><i class="fas fa-sign-in-alt"></i> Welcome Back</h2>
                <p class="text-muted">Sign in to your account</p>
            </div>
            
            <?php if ($session_expired): ?>
            <div class="alert alert-warning">
                <i class="fas fa-clock"></i> Your session has expired due to inactivity. Please log in again.
            </div>
            <?php endif; ?>
            
            <form id="zonatech-login-form">
                <div class="form-group">
                    <label for="email" class="text-white"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="text-white"><i class="fas fa-lock"></i> Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required style="padding-right: 45px;">
                        <button type="button" id="toggle-password" class="password-toggle-btn" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #8b5cf6; padding: 5px; z-index: 2; font-size: 1.1rem;">
                            <i class="fas fa-eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group d-flex justify-between align-center">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin: 0; cursor: pointer;">
                        <input type="checkbox" name="remember" style="width: auto; accent-color: #8b5cf6;">
                        <span style="font-size: 0.8rem;" class="text-white">Remember me</span>
                    </label>
                    <a href="#" id="forgot-password-link" style="font-size: 0.8rem;"><i class="fas fa-question-circle"></i> Forgot password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> <span>Sign In</span>
                </button>
            </form>
            
            <div class="auth-divider">
                <span>or</span>
            </div>
            
            <p class="text-center text-muted" style="font-size: 0.85rem;">
                Don't have an account? 
                <a href="<?php echo site_url('/zonatech-register/'); ?>"><i class="fas fa-user-plus"></i> Create one</a>
            </p>
        </div>
        
        <!-- Forgot Password Modal -->
        <div id="forgot-password-modal" class="glass-card" style="display: none; max-width: 420px; margin: 2rem auto;">
            <h3 class="text-white"><i class="fas fa-key"></i> Reset Password</h3>
            <p class="text-muted">Enter your email to receive a password reset link.</p>
            
            <form id="zonatech-reset-form">
                <div class="form-group">
                    <label for="reset-email" class="text-white"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" id="reset-email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
                    <button type="button" class="btn btn-ghost" id="back-to-login"><i class="fas fa-arrow-left"></i> Back to Login</button>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <footer class="zonatech-footer" style="margin-top: auto;">
            <div class="footer-content">
                <p class="footer-copyright">
                    © <?php echo date('Y'); ?> ZonaTech NG. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Hide loading screen - FAST!
    requestAnimationFrame(function() {
        $('#zonatech-loading-screen').addClass('fade-out');
        setTimeout(function() {
            $('#zonatech-loading-screen').hide();
        }, 100);
    });
    
    // Password visibility toggle
    $('#toggle-password').on('click', function() {
        var passwordField = $('#password');
        var toggleIcon = $('#password-toggle-icon');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            $(this).css('color', '#a78bfa'); // lighter purple when showing
        } else {
            passwordField.attr('type', 'password');
            toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            $(this).css('color', '#8b5cf6'); // purple when hidden
        }
    });
    
    $('#forgot-password-link').on('click', function(e) {
        e.preventDefault();
        $('.auth-card').hide();
        $('#forgot-password-modal').fadeIn();
    });
    
    $('#back-to-login').on('click', function() {
        $('#forgot-password-modal').hide();
        $('.auth-card').fadeIn();
    });
    
    // Mobile Navigation
    var hamburger = $('#hamburger-menu');
    var mobileNav = $('#mobile-nav');
    var mobileNavOverlay = $('#mobile-nav-overlay');
    var mobileNavClose = $('#mobile-nav-close');
    
    function openMobileNav() {
        hamburger.addClass('active');
        mobileNav.addClass('active');
        mobileNavOverlay.addClass('active');
        $('body').css('overflow', 'hidden');
    }
    
    function closeMobileNav() {
        hamburger.removeClass('active');
        mobileNav.removeClass('active');
        mobileNavOverlay.removeClass('active');
        $('body').css('overflow', '');
    }
    
    hamburger.on('click', function() {
        if (mobileNav.hasClass('active')) {
            closeMobileNav();
        } else {
            openMobileNav();
        }
    });
    
    mobileNavClose.on('click', closeMobileNav);
    mobileNavOverlay.on('click', closeMobileNav);
    
    mobileNav.find('a').on('click', function() {
        closeMobileNav();
    });
});
</script>