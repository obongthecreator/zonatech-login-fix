<?php
/**
 * Plugin Name: ZonaTech NG
 * Plugin URI: https://zonatechng.com
 * Description: Educational platform for JAMB, WAEC, NECO past questions, NIN services, and scratch card purchases with Paystack integration.
 * Version: 1.0.0
 * Author: ZonaTech NG
 * Author URI: https://zonatechng.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zonatech-ng
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('ZONATECH_VERSION', '1.0.0');
define('ZONATECH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZONATECH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ZONATECH_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Paystack Configuration - Keys are loaded from options, with validation
$zonatech_paystack_public = get_option('zonatech_paystack_public_key', '');
$zonatech_paystack_secret = get_option('zonatech_paystack_secret_key', '');

// Validate key format (basic check)
if (!empty($zonatech_paystack_public) && strpos($zonatech_paystack_public, 'pk_') !== 0) {
    $zonatech_paystack_public = ''; // Invalid format
}
if (!empty($zonatech_paystack_secret) && strpos($zonatech_paystack_secret, 'sk_') !== 0) {
    $zonatech_paystack_secret = ''; // Invalid format
}

define('ZONATECH_PAYSTACK_PUBLIC_KEY', $zonatech_paystack_public);
define('ZONATECH_PAYSTACK_SECRET_KEY', $zonatech_paystack_secret);

// Support Contact Info
define('ZONATECH_WHATSAPP_NUMBER', '08035328591');
define('ZONATECH_SUPPORT_EMAIL', 'support@zonatechng.com');

// Price Constants (in Naira)
define('ZONATECH_SUBJECT_PRICE', 5000);
define('ZONATECH_MONTHLY_PRICE', 5000);           // Monthly subscription - ₦5,000/month
define('ZONATECH_6MONTH_PRICE', 25000);           // 6-month subscription - ₦25,000/6 months
define('ZONATECH_FREE_QUESTIONS_LIMIT', 10);      // Number of free questions before payment required
define('ZONATECH_NIN_SLIP_PRICE', 2000);          // Premium NIN Slip
define('ZONATECH_NIN_STANDARD_SLIP_PRICE', 1000); // Standard NIN Slip
define('ZONATECH_CATEGORY_PRICE', 5000);           // Category price (same as monthly)
define('ZONATECH_SCRATCH_CARD_PRICE', 5000);      // Default scratch card price
define('ZONATECH_WAEC_CARD_PRICE', 3850);         // WAEC scratch card price
define('ZONATECH_NECO_CARD_PRICE', 2550);         // NECO scratch card price

// NIN Services Prices (with ₦300 markup)
define('ZONATECH_NIN_SLIP_DOWNLOAD_PRICE', 1300);      // NIN Slip Download - Original ~1000 + 300
define('ZONATECH_NIN_MODIFICATION_PRICE', 3800);       // NIN Data Modification - Original ~3500 + 300
define('ZONATECH_NIN_DOB_CORRECTION_PRICE', 5300);     // Date of Birth Correction - Original ~5000 + 300

// Session timeout (3 days in seconds)
define('ZONATECH_SESSION_TIMEOUT', 3 * DAY_IN_SECONDS);

/**
 * Main Plugin Class
 */
class ZonaTech_NG {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-database.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-user-auth.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-paystack.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-past-questions.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-nin-service.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-scratch-cards.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-quiz-system.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-activity-log.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-feedback.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-otapay.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-question-importer.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-gverifyer-api.php';
        require_once ZONATECH_PLUGIN_DIR . 'includes/class-ultramsg.php';
        require_once ZONATECH_PLUGIN_DIR . 'admin/class-admin.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'check_session_timeout'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_support_buttons'));
        add_action('wp_footer', array($this, 'render_pwa_prompt'));
        add_action('wp_footer', array($this, 'render_calculator_widget'));
        add_action('wp_head', array($this, 'add_pwa_meta'));
        add_action('wp_head', array($this, 'add_favicon'));
        add_action('wp_login', array($this, 'update_last_activity'), 10, 2);
        add_action('wp_loaded', array($this, 'track_user_activity'));
    }
    
    /**
     * Check session timeout and log out users after 3 days of inactivity
     */
    public function check_session_timeout() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $last_activity = get_user_meta($user_id, 'zonatech_last_activity', true);
        
        if ($last_activity && (time() - $last_activity) > ZONATECH_SESSION_TIMEOUT) {
            // Log out the user due to inactivity
            wp_logout();
            wp_redirect(site_url('/zonatech-login/?session_expired=1'));
            exit;
        }
    }
    
    /**
     * Track user activity
     */
    public function track_user_activity() {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, 'zonatech_last_activity', time());
        }
    }
    
    /**
     * Update last activity on login
     */
    public function update_last_activity($user_login, $user) {
        update_user_meta($user->ID, 'zonatech_last_activity', time());
    }
    
    /**
     * Add favicon to site
     */
    public function add_favicon() {
        ?>
        <!-- Favicon and App Icons -->
        <link rel="icon" type="image/x-icon" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/favicon.ico">
        <link rel="icon" type="image/png" sizes="16x16" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/favicon-16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/favicon-32.png">
        <link rel="icon" type="image/png" sizes="48x48" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/favicon-48.png">
        <link rel="icon" type="image/png" sizes="192x192" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/favicon-192.png">
        <link rel="icon" type="image/png" sizes="512x512" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/favicon-512.png">
        <link rel="shortcut icon" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/favicon.ico" type="image/x-icon">
        <link rel="apple-touch-icon" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/apple-touch-icon.png">
        <link rel="apple-touch-icon" sizes="152x152" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/icon-152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/apple-touch-icon.png">
        <meta name="msapplication-TileImage" content="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/icon-144.png">
        <meta name="msapplication-TileColor" content="#8b5cf6">
        <?php
    }
    
    public function init() {
        load_plugin_textdomain('zonatech-ng', false, dirname(ZONATECH_PLUGIN_BASENAME) . '/languages');
        
        // Ensure database tables exist (run once per version)
        $db_version = get_option('zonatech_db_version', '0');
        if (version_compare($db_version, ZONATECH_VERSION, '<')) {
            ZonaTech_Database::create_tables();
            update_option('zonatech_db_version', ZONATECH_VERSION);
        }
        
        // Initialize components
        ZonaTech_User_Auth::get_instance();
        ZonaTech_Past_Questions::get_instance();
        ZonaTech_NIN_Service::get_instance();
        ZonaTech_Scratch_Cards::get_instance();
        ZonaTech_Quiz_System::get_instance();
        ZonaTech_Activity_Log::get_instance();
        ZonaTech_Ajax_Handlers::get_instance();
        ZonaTech_Shortcodes::get_instance();
        ZonaTech_Feedback::get_instance();
        
        if (is_admin()) {
            ZonaTech_Admin::get_instance();
        }
        
        // Flush rewrite rules if needed (after activation)
        if (get_option('zonatech_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('zonatech_flush_rewrite_rules');
        }
        
        // Force check pages if accessing verify-email and it doesn't exist
        $this->check_critical_pages();
        
        // Ensure pages exist
        $this->ensure_pages_exist();
    }
    
    /**
     * Check critical pages and create them immediately if missing
     */
    private function check_critical_pages() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // Critical pages that must exist immediately
        $critical_pages = array(
            'zonatech-verify-email' => array(
                'title' => 'Verify Email',
                'content' => '[zonatech_verify_email]'
            ),
            'zonatech-login' => array(
                'title' => 'Login',
                'content' => '[zonatech_login]'
            ),
            'zonatech-register' => array(
                'title' => 'Register',
                'content' => '[zonatech_register]'
            ),
            'zonatech-feedback' => array(
                'title' => 'Feedback',
                'content' => '[zonatech_feedback]'
            ),
            'zonatech-admin' => array(
                'title' => 'Admin Dashboard',
                'content' => '[zonatech_admin_dashboard]'
            ),
            'zonatech-dashboard' => array(
                'title' => 'Dashboard',
                'content' => '[zonatech_dashboard]'
            ),
            'zonatech-past-questions' => array(
                'title' => 'Past Questions',
                'content' => '[zonatech_past_questions]'
            ),
            'zonatech-nin-service' => array(
                'title' => 'NIN Service',
                'content' => '[zonatech_nin_service]'
            ),
            'zonatech-scratch-cards' => array(
                'title' => 'Scratch Cards',
                'content' => '[zonatech_scratch_cards]'
            ),
            'zonatech-payment' => array(
                'title' => 'Payment',
                'content' => '[zonatech_payment]'
            )
        );
        
        foreach ($critical_pages as $slug => $page) {
            if (strpos($request_uri, $slug) !== false) {
                if (!get_page_by_path($slug)) {
                    // Create the page immediately
                    wp_insert_post(array(
                        'post_title' => $page['title'],
                        'post_name' => $slug,
                        'post_content' => $page['content'],
                        'post_status' => 'publish',
                        'post_type' => 'page'
                    ));
                    flush_rewrite_rules();
                }
                break;
            }
        }
    }
    
    private function ensure_pages_exist() {
        // Check more frequently (every hour) to ensure pages exist
        $last_check = get_option('zonatech_pages_check', 0);
        if (time() - $last_check < HOUR_IN_SECONDS) {
            return;
        }
        
        $required_pages = array(
            'zonatech-dashboard',
            'zonatech-login',
            'zonatech-register',
            'zonatech-verify-email',
            'zonatech-past-questions',
            'zonatech-nin-service',
            'zonatech-scratch-cards',
            'zonatech-payment',
            'zonatech-feedback',
            'zonatech-admin'
        );
        
        $missing_pages = array();
        foreach ($required_pages as $slug) {
            if (!get_page_by_path($slug)) {
                $missing_pages[] = $slug;
            }
        }
        
        if (!empty($missing_pages)) {
            $this->create_pages();
            flush_rewrite_rules();
        }
        
        update_option('zonatech_pages_check', time());
    }
    
    /**
     * Force create pages immediately (called from admin or API)
     */
    public function force_create_pages() {
        delete_option('zonatech_pages_check');
        $this->create_pages();
        flush_rewrite_rules();
    }
    
    public function activate() {
        ZonaTech_Database::create_tables();
        ZonaTech_Database::seed_sample_data();
        
        // Clear the pages check to force recreation
        delete_option('zonatech_pages_check');
        
        $this->create_pages();
        flush_rewrite_rules();
        
        // Set a flag to flush rewrite rules on next init
        update_option('zonatech_flush_rewrite_rules', true);
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_pages() {
        $pages = array(
            'home' => array(
                'title' => 'Homepage',
                'content' => '[zonatech_homepage]'
            ),
            'zonatech-dashboard' => array(
                'title' => 'Dashboard',
                'content' => '[zonatech_dashboard]'
            ),
            'zonatech-login' => array(
                'title' => 'Login',
                'content' => '[zonatech_login]'
            ),
            'zonatech-register' => array(
                'title' => 'Register',
                'content' => '[zonatech_register]'
            ),
            'zonatech-verify-email' => array(
                'title' => 'Verify Email',
                'content' => '[zonatech_verify_email]'
            ),
            'zonatech-past-questions' => array(
                'title' => 'Past Questions',
                'content' => '[zonatech_past_questions]'
            ),
            'zonatech-nin-service' => array(
                'title' => 'NIN Service',
                'content' => '[zonatech_nin_service]'
            ),
            'zonatech-scratch-cards' => array(
                'title' => 'Scratch Cards',
                'content' => '[zonatech_scratch_cards]'
            ),
            'zonatech-payment' => array(
                'title' => 'Payment',
                'content' => '[zonatech_payment]'
            ),
            'zonatech-feedback' => array(
                'title' => 'Feedback',
                'content' => '[zonatech_feedback]'
            ),
            'zonatech-admin' => array(
                'title' => 'Admin Dashboard',
                'content' => '[zonatech_admin_dashboard]'
            )
        );
        
        foreach ($pages as $slug => $page) {
            // Check if page exists by slug
            $existing_page = get_page_by_path($slug);
            
            if (!$existing_page) {
                $post_id = wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_name' => $slug,
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page'
                ));
                
                // Set homepage as front page
                if ($slug === 'home' && $post_id) {
                    update_option('show_on_front', 'page');
                    update_option('page_on_front', $post_id);
                }
            }
        }
    }
    
    public function enqueue_scripts() {
        // Font Awesome - Use official CDN with integrity check for reliability
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1');
        
        // Add Font Awesome Kit fallback in case CDN fails
        add_action('wp_head', function() {
            echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com">';
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        }, 1);
        
        // Styles
        wp_enqueue_style('zonatech-main', ZONATECH_PLUGIN_URL . 'assets/css/main.css', array('font-awesome'), ZONATECH_VERSION);
        wp_enqueue_style('zonatech-glassmorphism', ZONATECH_PLUGIN_URL . 'assets/css/glassmorphism.css', array(), ZONATECH_VERSION);
        wp_enqueue_style('zonatech-animations', ZONATECH_PLUGIN_URL . 'assets/css/animations.css', array(), ZONATECH_VERSION);
        wp_enqueue_style('zonatech-dashboard', ZONATECH_PLUGIN_URL . 'assets/css/dashboard.css', array(), ZONATECH_VERSION);
        
        // Scripts
        wp_enqueue_script('zonatech-main', ZONATECH_PLUGIN_URL . 'assets/js/main.js', array('jquery'), ZONATECH_VERSION, true);
        wp_enqueue_script('zonatech-auth', ZONATECH_PLUGIN_URL . 'assets/js/auth.js', array('jquery'), ZONATECH_VERSION, true);
        wp_enqueue_script('zonatech-quiz', ZONATECH_PLUGIN_URL . 'assets/js/quiz.js', array('jquery'), ZONATECH_VERSION, true);
        wp_enqueue_script('zonatech-payment', ZONATECH_PLUGIN_URL . 'assets/js/payment.js', array('jquery'), ZONATECH_VERSION, true);
        wp_enqueue_script('zonatech-pwa', ZONATECH_PLUGIN_URL . 'assets/js/pwa.js', array('jquery'), ZONATECH_VERSION, true);
        
        // Paystack
        wp_enqueue_script('paystack', 'https://js.paystack.co/v1/inline.js', array(), null, true);
        
        // Localize scripts
        wp_localize_script('zonatech-main', 'zonatech_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'site_url' => site_url(),
            'nonce' => wp_create_nonce('zonatech_nonce'),
            'paystack_public_key' => ZONATECH_PAYSTACK_PUBLIC_KEY,
            'paystack_configured' => !empty(ZONATECH_PAYSTACK_PUBLIC_KEY),
            'subject_price' => ZONATECH_SUBJECT_PRICE,
            'category_price' => ZONATECH_CATEGORY_PRICE,
            'monthly_price' => defined('ZONATECH_MONTHLY_PRICE') ? ZONATECH_MONTHLY_PRICE : 5000,
            'sixmonth_price' => defined('ZONATECH_6MONTH_PRICE') ? ZONATECH_6MONTH_PRICE : 25000,
            'free_questions_limit' => defined('ZONATECH_FREE_QUESTIONS_LIMIT') ? ZONATECH_FREE_QUESTIONS_LIMIT : 10,
            'nin_price' => ZONATECH_NIN_SLIP_PRICE,
            'nin_standard_price' => ZONATECH_NIN_STANDARD_SLIP_PRICE,
            'scratch_card_price' => ZONATECH_SCRATCH_CARD_PRICE,
            'waec_card_price' => defined('ZONATECH_WAEC_CARD_PRICE') ? ZONATECH_WAEC_CARD_PRICE : 3850,
            'neco_card_price' => defined('ZONATECH_NECO_CARD_PRICE') ? ZONATECH_NECO_CARD_PRICE : 2550,
            // NIN Service prices
            'nin_slip_download_price' => defined('ZONATECH_NIN_SLIP_DOWNLOAD_PRICE') ? ZONATECH_NIN_SLIP_DOWNLOAD_PRICE : 1300,
            'nin_modification_price' => defined('ZONATECH_NIN_MODIFICATION_PRICE') ? ZONATECH_NIN_MODIFICATION_PRICE : 3800,
            'nin_dob_correction_price' => defined('ZONATECH_NIN_DOB_CORRECTION_PRICE') ? ZONATECH_NIN_DOB_CORRECTION_PRICE : 5300,
            'whatsapp_number' => ZONATECH_WHATSAPP_NUMBER,
            'support_email' => ZONATECH_SUPPORT_EMAIL,
            'sw_url' => ZONATECH_PLUGIN_URL . 'sw.js'
        ));
    }
    
    public function add_pwa_meta() {
        ?>
        <!-- Font Awesome Direct Link -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="preconnect" href="https://cdnjs.cloudflare.com">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <meta name="theme-color" content="#1a1a2e">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="ZonaTech NG">
        <link rel="manifest" href="<?php echo ZONATECH_PLUGIN_URL; ?>manifest.json">
        <link rel="apple-touch-icon" href="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/icon-192.png">
        <?php
    }
    
    public function render_support_buttons() {
        ?>
        <div class="zonatech-support-buttons">
            <a href="https://wa.me/234<?php echo substr(ZONATECH_WHATSAPP_NUMBER, 1); ?>" 
               target="_blank" 
               class="zonatech-whatsapp-btn glass-effect"
               title="Chat on WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a href="mailto:<?php echo ZONATECH_SUPPORT_EMAIL; ?>" 
               class="zonatech-email-btn glass-effect"
               title="Email Support">
                <i class="fas fa-envelope"></i>
            </a>
        </div>
        <?php
    }
    
    public function render_pwa_prompt() {
        ?>
        <div id="zonatech-pwa-prompt" class="zonatech-pwa-prompt glass-effect" style="display: none;">
            <div class="pwa-prompt-content">
                <div class="pwa-icon">
                    <i class="fas fa-download"></i>
                </div>
                <div class="pwa-text">
                    <h4>Install ZonaTech NG App</h4>
                    <p>Access past questions offline anytime!</p>
                </div>
                <div class="pwa-actions">
                    <button id="zonatech-pwa-install" class="btn btn-primary btn-sm">Install</button>
                    <button id="zonatech-pwa-dismiss" class="btn btn-ghost btn-sm">Later</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render global calculator widget
     */
    public function render_calculator_widget() {
        ?>
        <div id="calculator-widget" class="calculator-widget" title="Calculator">
            <button id="calculator-toggle" class="calculator-toggle-btn" title="Calculator">
                <i class="fas fa-calculator"></i>
            </button>
            <div id="calculator-panel" class="calculator-panel" style="display: none;">
                <div class="calculator-header">
                    <span><i class="fas fa-calculator"></i> Calculator</span>
                    <button id="calculator-close" class="calculator-close-btn">&times;</button>
                </div>
                <div class="calculator-display">
                    <input type="text" id="calc-display" readonly value="0">
                </div>
                <div class="calculator-buttons">
                    <button class="calc-btn calc-clear" data-action="clear">C</button>
                    <button class="calc-btn calc-clear" data-action="backspace">⌫</button>
                    <button class="calc-btn calc-op" data-action="(">(</button>
                    <button class="calc-btn calc-op" data-action=")">)</button>
                    
                    <button class="calc-btn calc-func" data-action="sin">sin</button>
                    <button class="calc-btn calc-func" data-action="cos">cos</button>
                    <button class="calc-btn calc-func" data-action="tan">tan</button>
                    <button class="calc-btn calc-op" data-action="/">÷</button>
                    
                    <button class="calc-btn" data-action="7">7</button>
                    <button class="calc-btn" data-action="8">8</button>
                    <button class="calc-btn" data-action="9">9</button>
                    <button class="calc-btn calc-op" data-action="*">×</button>
                    
                    <button class="calc-btn" data-action="4">4</button>
                    <button class="calc-btn" data-action="5">5</button>
                    <button class="calc-btn" data-action="6">6</button>
                    <button class="calc-btn calc-op" data-action="-">−</button>
                    
                    <button class="calc-btn" data-action="1">1</button>
                    <button class="calc-btn" data-action="2">2</button>
                    <button class="calc-btn" data-action="3">3</button>
                    <button class="calc-btn calc-op" data-action="+">+</button>
                    
                    <button class="calc-btn" data-action="0">0</button>
                    <button class="calc-btn" data-action=".">.</button>
                    <button class="calc-btn calc-func" data-action="sqrt">√</button>
                    <button class="calc-btn calc-equals" data-action="=">=</button>
                    
                    <button class="calc-btn calc-func" data-action="pow">x²</button>
                    <button class="calc-btn calc-func" data-action="pi">π</button>
                    <button class="calc-btn calc-func" data-action="percent">%</button>
                    <button class="calc-btn calc-func" data-action="negate">±</button>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize Plugin
function zonatech_ng() {
    return ZonaTech_NG::get_instance();
}

add_action('plugins_loaded', 'zonatech_ng');