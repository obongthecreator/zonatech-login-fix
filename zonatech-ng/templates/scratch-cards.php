<?php
/**
 * Scratch Cards Template
 */

if (!defined('ABSPATH')) exit;
$is_guest = !is_user_logged_in();
?>

<div class="zonatech-container">
    <div class="zonatech-wrapper">
        <!-- Header -->
        <div class="zonatech-header glass-effect">
            <div class="zonatech-logo">
                <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                <span>ZonaTech NG</span>
            </div>
            <nav class="zonatech-nav">
                <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
                <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
                <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>" class="active"><i class="fas fa-credit-card"></i> Scratch Cards</a>
                <a href="<?php echo site_url('/zonatech-nin-service/'); ?>"><i class="fas fa-id-card"></i> NIN Service</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Register</a>
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
            <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>" class="active"><i class="fas fa-credit-card"></i> Scratch Cards</a>
            <a href="<?php echo site_url('/zonatech-nin-service/'); ?>"><i class="fas fa-id-card"></i> NIN Service</a>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="<?php echo site_url('/zonatech-register/'); ?>"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </nav>
        
        <?php if ($is_guest): ?>
        <!-- Guest User Prompt -->
        <div class="glass-card glass-effect-purple" style="max-width: 600px; margin: 0 auto 2rem; text-align: center;">
            <div style="width: 80px; height: 80px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; background: rgba(139, 92, 246, 0.2); border-radius: 50%; font-size: 2rem; color: var(--zona-purple-light);">
                <i class="fas fa-user-lock"></i>
            </div>
            <h3 class="text-white"><i class="fas fa-lock"></i> Login Required to Purchase</h3>
            <p class="text-muted" style="margin-bottom: 1.5rem;">Create an account or login to purchase scratch cards and PINs.</p>
            <div class="d-flex justify-center gap-2" style="flex-wrap: wrap;">
                <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Create Account
                </a>
                <a href="<?php echo site_url('/zonatech-login/'); ?>" class="btn btn-secondary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Card Types -->
        <div class="cards-grid mb-3">
            <?php foreach ($card_types as $type => $card): ?>
                <div class="service-card animate-card">
                    <div class="service-card-icon" style="background: linear-gradient(135deg, <?php echo $card['color']; ?>20 0%, <?php echo $card['color']; ?>10 100%); color: <?php echo $card['color']; ?>;">
                        <i class="<?php echo esc_attr($card['icon']); ?>"></i>
                    </div>
                    <h3 class="service-card-title"><?php echo esc_html($card['full_name']); ?></h3>
                    <p class="service-card-desc"><?php echo esc_html($card['description']); ?></p>
                    <p class="service-card-price">₦<?php echo number_format($card['price']); ?></p>
                    <?php if ($is_guest): ?>
                        <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Register to Buy
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary buy-scratch-card-btn" data-card-type="<?php echo esc_attr($type); ?>">
                            <i class="fas fa-shopping-cart"></i> Buy Now
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!$is_guest): ?>
        <!-- My Purchased Cards -->
        <div class="section">
            <div class="section-header">
                <h2 class="text-white"><i class="fas fa-ticket-alt"></i> My Purchased Cards</h2>
                <p class="text-muted">View all the scratch cards and PINs you've purchased</p>
            </div>
            <div id="user-cards-container">
                <div class="loading"><div class="spinner"></div></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- How It Works -->
        <div class="section">
            <div class="section-header">
                <h2 class="text-white"><i class="fas fa-question-circle"></i> How It Works</h2>
            </div>
            <div class="cards-grid" style="max-width: 900px; margin: 0 auto;">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mouse-pointer"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">1. Select Card</h4>
                        <p>Choose the type of scratch card you need</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">2. Make Payment</h4>
                        <p>Pay securely with Paystack</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">3. Get Your PIN</h4>
                        <p>Instantly receive your PIN and serial number</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Important Notice -->
        <div class="alert alert-warning" style="max-width: 800px; margin: 2rem auto;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Important:</strong> Once purchased, scratch cards cannot be refunded. 
            Please ensure you purchase the correct card type for your needs.
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
});
</script>