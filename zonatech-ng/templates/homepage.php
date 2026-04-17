<?php
/**
 * Homepage Template
 */

if (!defined('ABSPATH')) exit;

$zonatech_site_name = get_option('zonatech_site_name', 'ZonaTech NG');
$zonatech_site_logo = get_option('zonatech_site_logo', '');
$zonatech_logo_url = !empty($zonatech_site_logo) ? $zonatech_site_logo : ZONATECH_PLUGIN_URL . 'assets/images/logo.png';
?>

<div class="zonatech-container">
    <!-- Loading Screen -->
    <div id="zonatech-loading-screen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2><?php echo esc_html($zonatech_site_name); ?></h2>
            <p>Loading...</p>
        </div>
    </div>

    <!-- Welcome Animation Overlay -->
    <div id="zonatech-welcome-overlay" class="welcome-overlay" style="display: none;">
        <div class="welcome-content">
            <i class="fas fa-graduation-cap welcome-icon"></i>
            <h1>Welcome to <?php echo esc_html($zonatech_site_name); ?></h1>
            <p>Your Gateway to Academic Excellence</p>
        </div>
    </div>

    <div class="zonatech-wrapper">
        <!-- Header -->
        <div class="zonatech-header glass-effect">
            <a href="<?php echo site_url(); ?>" class="zonatech-logo">
                <img src="<?php echo esc_url($zonatech_logo_url); ?>" alt="<?php echo esc_attr($zonatech_site_name); ?>" class="zonatech-logo-img">
                <span><?php echo esc_html($zonatech_site_name); ?></span>
            </a>
            <nav class="zonatech-nav">
                <a href="#services"><i class="fas fa-concierge-bell"></i> Services</a>
                <a href="#past-questions"><i class="fas fa-book-open"></i> Past Questions</a>
                <a href="#scratch-cards"><i class="fas fa-credit-card"></i> Scratch Cards</a>
                <a href="<?php echo site_url('/zonatech-feedback/'); ?>"><i class="fas fa-comment-dots"></i> Feedback</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="<?php echo wp_logout_url(site_url()); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="<?php echo site_url('/zonatech-login/'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Get Started</a>
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
                <a href="<?php echo site_url(); ?>" class="zonatech-logo">
                    <img src="<?php echo esc_url($zonatech_logo_url); ?>" alt="<?php echo esc_attr($zonatech_site_name); ?>" class="zonatech-logo-img">
                    <span><?php echo esc_html($zonatech_site_name); ?></span>
                </a>
                <button class="mobile-nav-close" id="mobile-nav-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
            <a href="#services"><i class="fas fa-concierge-bell"></i> Services</a>
            <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
            <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>"><i class="fas fa-credit-card"></i> Scratch Cards</a>
            <a href="<?php echo site_url('/zonatech-nin-service/'); ?>"><i class="fas fa-id-card"></i> NIN Service</a>
            <a href="<?php echo site_url('/zonatech-feedback/'); ?>"><i class="fas fa-comment-dots"></i> Feedback</a>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="<?php echo wp_logout_url(site_url()); ?>" class="mobile-logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="<?php echo site_url('/zonatech-register/'); ?>"><i class="fas fa-user-plus"></i> Create Account</a>
            <?php endif; ?>
        </nav>
        
        <!-- Hero Section -->
        <section class="section" style="text-align: center; padding: 4rem 0;">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;" class="text-gradient-animate text-white">
                Your Gateway to Academic Excellence
            </h1>
            <p style="font-size: 1.1rem; max-width: 600px; margin: 0 auto 2rem;" class="text-white">
                Access JAMB, WAEC, and NECO past questions, purchase scratch cards, 
                and retrieve your NIN - all in one place.
            </p>
            <div class="d-flex justify-center gap-2" style="flex-wrap: wrap;">
                <a href="<?php echo site_url('/zonatech-past-questions/'); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-book-open"></i> Explore Past Questions
                </a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>" class="btn btn-secondary btn-lg">
                        <i class="fas fa-credit-card"></i> Buy Scratch Cards
                    </a>
                <?php else: ?>
                    <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-secondary btn-lg">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Stats Section -->
        <section class="section">
            <div class="cards-grid" style="max-width: 900px; margin: 0 auto;">
                <div class="stats-card glass-effect">
                    <div class="stats-value">15+</div>
                    <div class="stats-label">Years of Questions</div>
                </div>
                <div class="stats-card glass-effect">
                    <div class="stats-value">10+</div>
                    <div class="stats-label">Subjects</div>
                </div>
                <div class="stats-card glass-effect">
                    <div class="stats-value">3</div>
                    <div class="stats-label">Exam Bodies</div>
                </div>
                <div class="stats-card glass-effect">
                    <div class="stats-value">24/7</div>
                    <div class="stats-label">Access</div>
                </div>
            </div>
        </section>
        
        <!-- Services Section -->
        <section id="services" class="section">
            <div class="section-header">
                <h2 class="text-white"><i class="fas fa-concierge-bell"></i> Our Services</h2>
                <p class="text-muted">Everything you need to succeed in your examinations</p>
            </div>
            
            <div class="cards-grid">
                <!-- Past Questions -->
                <div class="service-card animate-card">
                    <div class="service-card-icon purple">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="service-card-title">Past Questions</h3>
                    <p class="service-card-desc">Access JAMB, WAEC, and NECO past questions with detailed answers from combined years.</p>
                    <a href="<?php echo site_url('/zonatech-past-questions/'); ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> View Questions
                    </a>
                </div>
                
                <!-- Scratch Cards -->
                <div class="service-card animate-card">
                    <div class="service-card-icon green">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3 class="service-card-title">Scratch Cards</h3>
                    <p class="service-card-desc">Purchase WAEC, NECO, and JAMB scratch cards & PINs instantly.</p>
                    <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Buy Cards
                    </a>
                </div>
                
                <!-- NIN Service -->
                <div class="service-card animate-card">
                    <div class="service-card-icon orange">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <h3 class="service-card-title">NIN Service</h3>
                    <p class="service-card-desc">Retrieve your NIN number and download your premium NIN slip.</p>
                    <a href="<?php echo site_url('/zonatech-nin-service/'); ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Get NIN Slip
                    </a>
                </div>
            </div>
        </section>
        
        <!-- Past Questions Section -->
        <section id="past-questions" class="section">
            <div class="section-header">
                <h2 class="text-white"><i class="fas fa-book-open"></i> Past Questions</h2>
                <p class="text-muted">Comprehensive past questions for all major examinations</p>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($exam_types as $type => $exam): ?>
                    <div class="glass-card text-center">
                        <div style="width: 80px; height: 80px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; background: <?php echo $exam['color']; ?>20; border-radius: 50%; font-size: 2rem; color: <?php echo $exam['color']; ?>;">
                            <i class="<?php echo esc_attr($exam['icon']); ?>"></i>
                        </div>
                        <h3 class="text-white"><?php echo esc_html($exam['name']); ?></h3>
                        <p class="text-muted"><?php echo esc_html($exam['full_name']); ?></p>
                        <p style="font-size: 0.85rem; color: var(--zona-white-muted);">Combined Years • 10+ Subjects</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Features Section -->
        <section class="section">
            <div class="section-header">
                <h2 class="text-white"><i class="fas fa-medal"></i> Why Choose <?php echo esc_html($zonatech_site_name); ?>?</h2>
            </div>
            
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">Mobile App</h4>
                        <p>Install our web app and study offline anytime</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">Practice Tests</h4>
                        <p>Take timed quizzes and track your progress</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">Instant Corrections</h4>
                        <p>Get detailed explanations for every question</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">Instant Delivery</h4>
                        <p>Get your scratch cards and PINs immediately</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">Secure Payments</h4>
                        <p>Pay safely with Paystack integration</p>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">24/7 Support</h4>
                        <p>Get help anytime via WhatsApp or email</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- CTA Section -->
        <section class="section">
            <div class="glass-card glass-effect-purple text-center" style="padding: 3rem;">
                <h2 class="text-white"><i class="fas fa-rocket"></i> Ready to Start Learning?</h2>
                <p class="text-muted" style="max-width: 500px; margin: 0 auto 1.5rem;">
                    Join thousands of students who are preparing for success with <?php echo esc_html($zonatech_site_name); ?>.
                </p>
                <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary btn-lg glow">
                    <i class="fas fa-rocket"></i> Get Started Now
                </a>
            </div>
        </section>
        
        <!-- Feedback Section -->
        <section id="feedback" class="section">
            <div class="section-header">
                <h2 class="text-white"><i class="fas fa-comment-dots"></i> Share Your Feedback</h2>
                <p class="text-muted">We value your opinion! Help us improve <?php echo esc_html($zonatech_site_name); ?></p>
            </div>
            
            <div class="glass-card" style="max-width: 800px; margin: 0 auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: center;">
                    <div>
                        <div style="width: 100px; height: 100px; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; background: rgba(139, 92, 246, 0.2); border-radius: 50%; font-size: 2.5rem; color: var(--zona-purple-light);">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <h3 class="text-white text-center">Your Voice Matters!</h3>
                        <p class="text-muted text-center">
                            Whether you have a suggestion, question, or just want to say hello, we'd love to hear from you.
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="text-white"><i class="fas fa-lightbulb"></i> How to Leave Feedback</h4>
                        <ul style="list-style: none; padding: 0; margin: 1rem 0;">
                            <li style="padding: 0.5rem 0; color: var(--zona-white-muted);">
                                <i class="fas fa-check-circle" style="color: var(--zona-purple-light); margin-right: 0.5rem;"></i>
                                Share your experience using our platform
                            </li>
                            <li style="padding: 0.5rem 0; color: var(--zona-white-muted);">
                                <i class="fas fa-check-circle" style="color: var(--zona-purple-light); margin-right: 0.5rem;"></i>
                                Suggest new features or improvements
                            </li>
                            <li style="padding: 0.5rem 0; color: var(--zona-white-muted);">
                                <i class="fas fa-check-circle" style="color: var(--zona-purple-light); margin-right: 0.5rem;"></i>
                                Report any issues you've encountered
                            </li>
                            <li style="padding: 0.5rem 0; color: var(--zona-white-muted);">
                                <i class="fas fa-check-circle" style="color: var(--zona-purple-light); margin-right: 0.5rem;"></i>
                                Rate your overall experience
                            </li>
                            <li style="padding: 0.5rem 0; color: var(--zona-white-muted);">
                                <i class="fas fa-check-circle" style="color: var(--zona-purple-light); margin-right: 0.5rem;"></i>
                                All feedback is read and appreciated!
                            </li>
                        </ul>
                        
                        <a href="<?php echo site_url('/zonatech-feedback/'); ?>" class="btn btn-primary btn-lg" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> Leave Feedback
                        </a>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Download App Section -->
        <section id="download-app" class="section">
            <div class="download-app-card glass-effect">
                <div class="download-app-content">
                    <div class="download-app-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="download-app-text">
                        <h2 class="text-white"><i class="fas fa-download"></i> Download Our App</h2>
                        <p class="text-muted">Install the <?php echo esc_html($zonatech_site_name); ?> web app on your device for offline access to past questions and a better experience.</p>
                        <div class="download-app-features">
                            <div class="download-feature">
                                <i class="fas fa-wifi-slash"></i>
                                <span>Access offline</span>
                            </div>
                            <div class="download-feature">
                                <i class="fas fa-bolt"></i>
                                <span>Faster loading</span>
                            </div>
                            <div class="download-feature">
                                <i class="fas fa-bell"></i>
                                <span>Notifications</span>
                            </div>
                            <div class="download-feature">
                                <i class="fas fa-mobile-alt"></i>
                                <span>Home screen icon</span>
                            </div>
                        </div>
                        <button id="download-app-btn" class="btn btn-primary btn-lg">
                            <i class="fas fa-download"></i> Install App Now
                        </button>
                        <p class="download-app-note text-muted">
                            <i class="fas fa-info-circle"></i> Works on Android, iOS, Windows, and Mac
                        </p>
                    </div>
                    <div class="download-app-image">
                        <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/icon-512.png" alt="<?php echo esc_attr($zonatech_site_name); ?> App">
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Footer -->
        <footer class="zonatech-footer">
            <div class="footer-content">
                <a href="<?php echo site_url(); ?>" class="footer-logo">
                    <img src="<?php echo esc_url($zonatech_logo_url); ?>" alt="<?php echo esc_attr($zonatech_site_name); ?>" class="footer-logo-img">
                    <span><?php echo esc_html($zonatech_site_name); ?></span>
                </a>
                <p class="footer-tagline">Your Gateway to Academic Excellence</p>
                
                <div class="footer-links">
                    <a href="<?php echo site_url('/zonatech-past-questions/'); ?>">
                        <i class="fas fa-book-open"></i> Past Questions
                    </a>
                    <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>">
                        <i class="fas fa-credit-card"></i> Scratch Cards
                    </a>
                    <a href="<?php echo site_url('/zonatech-nin-service/'); ?>">
                        <i class="fas fa-id-card"></i> NIN Service
                    </a>
                    <a href="<?php echo site_url('/zonatech-feedback/'); ?>">
                        <i class="fas fa-comment-dots"></i> Feedback
                    </a>
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
                    © <?php echo date('Y'); ?> <?php echo esc_html($zonatech_site_name); ?>. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Loading screen and welcome animation - OPTIMIZED for speed
    var loadingScreen = $('#zonatech-loading-screen');
    var welcomeOverlay = $('#zonatech-welcome-overlay');
    
    // Hide loading screen immediately (fast!)
    requestAnimationFrame(function() {
        loadingScreen.addClass('fade-out');
        setTimeout(function() {
            loadingScreen.hide();
            
            // Show welcome animation for first-time visitors or new sessions
            if (!sessionStorage.getItem('zonatech_welcomed')) {
                welcomeOverlay.show().addClass('animate');
                sessionStorage.setItem('zonatech_welcomed', 'true');
                
                setTimeout(function() {
                    welcomeOverlay.addClass('fade-out');
                    setTimeout(function() {
                        welcomeOverlay.hide();
                    }, 150);
                }, 800);
            }
        }, 100);
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
    
    // Close mobile nav when clicking links
    mobileNav.find('a').on('click', function() {
        closeMobileNav();
    });
});
</script>