<?php
/**
 * Feedback Template
 */

if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$user_name = is_user_logged_in() ? $current_user->display_name : '';
$user_email = is_user_logged_in() ? $current_user->user_email : '';
?>

<div class="zonatech-container">
    <div class="zonatech-wrapper">
        <!-- Header -->
        <div class="zonatech-header glass-effect">
            <a href="<?php echo site_url(); ?>" class="zonatech-logo">
                <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                <span>ZonaTech NG</span>
            </a>
            <nav class="zonatech-nav">
                <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
                <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
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
                <div class="zonatech-logo">
                    <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                    <span>ZonaTech NG</span>
                </div>
                <button class="mobile-nav-close" id="mobile-nav-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
            <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="<?php echo site_url('/zonatech-register/'); ?>"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </nav>
        
        <!-- Feedback Form -->
        <div class="glass-card" style="max-width: 700px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="width: 80px; height: 80px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; background: rgba(139, 92, 246, 0.2); border-radius: 50%; font-size: 2rem; color: var(--zona-purple-light);">
                    <i class="fas fa-comment-dots"></i>
                </div>
                <h2 class="text-white"><i class="fas fa-paper-plane"></i> Send Feedback</h2>
                <p class="text-muted">We value your feedback! Help us improve ZonaTech NG by sharing your thoughts.</p>
            </div>
            
            <form id="feedback-form">
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="feedback-name" class="text-white"><i class="fas fa-user"></i> Your Name</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="feedback-name" class="form-control form-control-icon" placeholder="Enter your name" value="<?php echo esc_attr($user_name); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="feedback-email" class="text-white"><i class="fas fa-envelope"></i> Your Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="feedback-email" class="form-control form-control-icon" placeholder="Enter your email" value="<?php echo esc_attr($user_email); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="feedback-subject" class="text-white"><i class="fas fa-tag"></i> Subject</label>
                    <div class="input-with-icon">
                        <i class="fas fa-tag input-icon"></i>
                        <select id="feedback-subject" class="form-control form-control-icon" style="padding-left: 40px;">
                            <option value="General Feedback">General Feedback</option>
                            <option value="Feature Request">Feature Request</option>
                            <option value="Bug Report">Bug Report</option>
                            <option value="Suggestion">Suggestion</option>
                            <option value="Complaint">Complaint</option>
                            <option value="Question">Question</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="text-white"><i class="fas fa-star"></i> Rate Your Experience</label>
                    <div class="star-rating" id="star-rating">
                        <span class="star" data-value="1"><i class="far fa-star"></i></span>
                        <span class="star" data-value="2"><i class="far fa-star"></i></span>
                        <span class="star" data-value="3"><i class="far fa-star"></i></span>
                        <span class="star" data-value="4"><i class="far fa-star"></i></span>
                        <span class="star" data-value="5"><i class="far fa-star"></i></span>
                    </div>
                    <input type="hidden" id="feedback-rating" value="0">
                </div>
                
                <div class="form-group">
                    <label for="feedback-message" class="text-white"><i class="fas fa-message"></i> Your Message</label>
                    <textarea id="feedback-message" class="form-control" rows="5" placeholder="Please share your feedback, suggestions, or concerns..." required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>
            
            <div id="feedback-result" style="margin-top: 1.5rem;"></div>
        </div>
        
        <!-- Contact Info -->
        <div class="glass-card" style="max-width: 700px; margin: 2rem auto;">
            <h3 class="text-white"><i class="fas fa-phone-alt"></i> Other Ways to Reach Us</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
                <a href="https://wa.me/234<?php echo substr(ZONATECH_WHATSAPP_NUMBER, 1); ?>" target="_blank" class="btn btn-success" style="background: #25D366;">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="mailto:<?php echo ZONATECH_SUPPORT_EMAIL; ?>" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> Email Us
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="zonatech-footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="footer-logo-img">
                    <span>ZonaTech NG</span>
                </div>
                <div class="footer-social">
                    <a href="https://wa.me/234<?php echo substr(ZONATECH_WHATSAPP_NUMBER, 1); ?>" target="_blank" title="WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="mailto:<?php echo ZONATECH_SUPPORT_EMAIL; ?>" title="Email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
                <p class="footer-copyright">
                    © <?php echo date('Y'); ?> ZonaTech NG. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</div>

<style>
.star-rating {
    display: flex;
    gap: 0.5rem;
    font-size: 1.8rem;
    margin-top: 0.5rem;
}

.star-rating .star {
    cursor: pointer;
    color: rgba(255, 255, 255, 0.3);
    transition: all 0.2s ease;
}

.star-rating .star:hover,
.star-rating .star.active {
    color: #fbbf24;
}

.star-rating .star:hover i,
.star-rating .star.active i {
    font-weight: 900;
}

.star-rating .star.active i:before {
    content: "\f005";
}
</style>

<script>
jQuery(document).ready(function($) {
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
    
    // Star rating
    var rating = 0;
    $('.star-rating .star').on('click', function() {
        rating = $(this).data('value');
        $('#feedback-rating').val(rating);
        
        $('.star-rating .star').removeClass('active');
        $('.star-rating .star').each(function() {
            if ($(this).data('value') <= rating) {
                $(this).addClass('active');
                $(this).find('i').removeClass('far').addClass('fas');
            } else {
                $(this).find('i').removeClass('fas').addClass('far');
            }
        });
    });
    
    // Feedback form submission
    $('#feedback-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $(this).find('button[type="submit"]');
        var originalText = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Submitting...').prop('disabled', true);
        
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_submit_feedback',
                nonce: zonatech_ajax.nonce,
                name: $('#feedback-name').val(),
                email: $('#feedback-email').val(),
                subject: $('#feedback-subject').val(),
                message: $('#feedback-message').val(),
                rating: $('#feedback-rating').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#feedback-result').html(
                        '<div class="alert alert-success">' +
                        '<i class="fas fa-check-circle"></i> ' + response.data.message +
                        '</div>'
                    );
                    // Reset form
                    $('#feedback-form')[0].reset();
                    rating = 0;
                    $('.star-rating .star').removeClass('active');
                    $('.star-rating .star i').removeClass('fas').addClass('far');
                    $('#feedback-rating').val(0);
                } else {
                    $('#feedback-result').html(
                        '<div class="alert alert-error">' +
                        '<i class="fas fa-exclamation-circle"></i> ' + (response.data.message || 'Failed to submit feedback.') +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#feedback-result').html(
                    '<div class="alert alert-error">' +
                    '<i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.' +
                    '</div>'
                );
            },
            complete: function() {
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>