<?php
/**
 * Shortcodes Handler Class
 * Rebuilt from scratch to fix navigation and login detection issues
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Shortcodes {
    
    private static $instance = null;
    
    // Redirect delay in milliseconds - only for login/register pages
    const REDIRECT_DELAY_MS = 1500;
    
    // Cache the login status to avoid multiple checks
    private $is_logged_in = null;
    private $current_user_id = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register all shortcodes
        add_shortcode('zonatech_login', array($this, 'render_login'));
        add_shortcode('zonatech_register', array($this, 'render_register'));
        add_shortcode('zonatech_verify_email', array($this, 'render_verify_email'));
        add_shortcode('zonatech_dashboard', array($this, 'render_dashboard'));
        add_shortcode('zonatech_past_questions', array($this, 'render_past_questions'));
        add_shortcode('zonatech_nin_service', array($this, 'render_nin_service'));
        add_shortcode('zonatech_scratch_cards', array($this, 'render_scratch_cards'));
        add_shortcode('zonatech_payment', array($this, 'render_payment'));
        add_shortcode('zonatech_homepage', array($this, 'render_homepage'));
        add_shortcode('zonatech_feedback', array($this, 'render_feedback'));
        add_shortcode('zonatech_admin_dashboard', array($this, 'render_admin_dashboard'));
        
        // Add nocache headers for pages that check login status
        add_action('template_redirect', array($this, 'add_nocache_headers'));
    }
    
    /**
     * Add nocache headers for ZonaTech pages to prevent caching issues
     * This ensures login status is always checked fresh
     */
    public function add_nocache_headers() {
        if (is_page()) {
            $page_slug = get_post_field('post_name', get_post());
            $zonatech_pages = array(
                'zonatech-dashboard',
                'zonatech-past-questions',
                'zonatech-nin-service',
                'zonatech-scratch-cards',
                'zonatech-payment',
                'zonatech-login',
                'zonatech-register',
                'zonatech-admin'
            );
            
            if (in_array($page_slug, $zonatech_pages)) {
                // Prevent caching of these pages
                nocache_headers();
            }
        }
    }
    
    /**
     * Check if user is logged in - with multiple verification methods
     * This handles edge cases where is_user_logged_in() might return incorrect values
     * Returns true if logged in, false if not
     */
    private function check_login() {
        // Return cached result if already checked
        if ($this->is_logged_in !== null) {
            return $this->is_logged_in;
        }
        
        // Primary check using WordPress function
        $this->is_logged_in = is_user_logged_in();
        
        // If primary check says not logged in, verify with current user check
        if (!$this->is_logged_in) {
            $user_id = get_current_user_id();
            if ($user_id > 0) {
                $this->is_logged_in = true;
                $this->current_user_id = $user_id;
            }
        } else {
            $this->current_user_id = get_current_user_id();
        }
        
        // Additional check: if still not logged in, try wp_get_current_user directly
        // This is a read-only approach that doesn't modify global state
        if (!$this->is_logged_in) {
            $current_user = wp_get_current_user();
            if ($current_user && $current_user->ID > 0) {
                $this->is_logged_in = true;
                $this->current_user_id = $current_user->ID;
            }
        }
        
        return $this->is_logged_in;
    }
    
    /**
     * Get current user ID (cached)
     */
    private function get_user_id() {
        if ($this->current_user_id !== null) {
            return $this->current_user_id;
        }
        $this->check_login();
        return $this->current_user_id ?: get_current_user_id();
    }
    
    /**
     * Get fallback exam types data
     */
    private static function get_fallback_exam_types() {
        return array(
            'jamb' => array(
                'name' => 'JAMB',
                'full_name' => 'Joint Admissions and Matriculation Board',
                'icon' => 'fas fa-graduation-cap',
                'color' => '#8b5cf6'
            ),
            'waec' => array(
                'name' => 'WAEC',
                'full_name' => 'West African Examinations Council',
                'icon' => 'fas fa-book-open',
                'color' => '#22c55e'
            ),
            'neco' => array(
                'name' => 'NECO',
                'full_name' => 'National Examinations Council',
                'icon' => 'fas fa-scroll',
                'color' => '#f59e0b'
            )
        );
    }
    
    /**
     * Render login required message for shortcodes (NO auto-redirect)
     * User must click the button to go to login
     */
    private function render_login_required($redirect_page = '') {
        $login_url = site_url('/zonatech-login/');
        if (!empty($redirect_page)) {
            $login_url .= '?redirect=' . urlencode($redirect_page);
        }
        
        ob_start();
        ?>
        <div class="zonatech-container">
            <div class="glass-card text-center" style="padding: 3rem; max-width: 500px; margin: 2rem auto;">
                <i class="fas fa-user-lock" style="font-size: 4rem; color: #8b5cf6; margin-bottom: 1.5rem;"></i>
                <h2 class="text-white" style="margin-bottom: 1rem;">Login Required</h2>
                <p class="text-muted" style="margin-bottom: 1.5rem;">Please login to access this page.</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo esc_url($login_url); ?>" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="<?php echo esc_url(site_url('/zonatech-register/')); ?>" class="btn btn-secondary">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_login() {
        // If already logged in, show redirect message and auto-redirect to dashboard
        // This is ONLY for the login page - not for other pages
        if (is_user_logged_in()) {
            $dashboard_url = site_url('/zonatech-dashboard/');
            $delay_ms = self::REDIRECT_DELAY_MS;
            ob_start();
            ?>
            <div class="zonatech-container">
                <div class="glass-card text-center" style="padding: 3rem; max-width: 500px; margin: 2rem auto;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #22c55e; margin-bottom: 1.5rem;"></i>
                    <h2 class="text-white" style="margin-bottom: 1rem;">Already Logged In</h2>
                    <p class="text-muted" style="margin-bottom: 1.5rem;">Redirecting to your dashboard...</p>
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                </div>
            </div>
            <script>
            setTimeout(function() {
                window.location.href = '<?php echo esc_js($dashboard_url); ?>';
            }, <?php echo intval($delay_ms); ?>);
            </script>
            <?php
            return ob_get_clean();
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/login.php';
        return ob_get_clean();
    }
    
    public function render_register() {
        // If already logged in, show redirect message and auto-redirect
        // This is ONLY for the register page
        if (is_user_logged_in()) {
            $dashboard_url = site_url('/zonatech-dashboard/');
            $delay_ms = self::REDIRECT_DELAY_MS;
            ob_start();
            ?>
            <div class="zonatech-container">
                <div class="glass-card text-center" style="padding: 3rem; max-width: 500px; margin: 2rem auto;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #22c55e; margin-bottom: 1.5rem;"></i>
                    <h2 class="text-white" style="margin-bottom: 1rem;">Already Logged In</h2>
                    <p class="text-muted" style="margin-bottom: 1.5rem;">You already have an account. Redirecting to dashboard...</p>
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                </div>
            </div>
            <script>
            setTimeout(function() {
                window.location.href = '<?php echo esc_js($dashboard_url); ?>';
            }, <?php echo intval($delay_ms); ?>);
            </script>
            <?php
            return ob_get_clean();
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/register.php';
        return ob_get_clean();
    }
    
    public function render_verify_email() {
        // If already logged in, redirect to dashboard
        // This is ONLY for the verify-email page
        if (is_user_logged_in()) {
            $dashboard_url = site_url('/zonatech-dashboard/');
            $delay_ms = self::REDIRECT_DELAY_MS;
            ob_start();
            ?>
            <div class="zonatech-container">
                <div class="glass-card text-center" style="padding: 3rem; max-width: 500px; margin: 2rem auto;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #22c55e; margin-bottom: 1.5rem;"></i>
                    <h2 class="text-white" style="margin-bottom: 1rem;">Already Verified</h2>
                    <p class="text-muted" style="margin-bottom: 1.5rem;">Your email is already verified. Redirecting to dashboard...</p>
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                </div>
            </div>
            <script>
            setTimeout(function() {
                window.location.href = '<?php echo esc_js($dashboard_url); ?>';
            }, <?php echo intval($delay_ms); ?>);
            </script>
            <?php
            return ob_get_clean();
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/verify-email.php';
        return ob_get_clean();
    }
    
    public function render_dashboard() {
        // Check login - show login required if not logged in (no auto-redirect)
        if (!$this->check_login()) {
            return $this->render_login_required('dashboard');
        }
        
        // Get user data with error handling
        try {
            $user_data = ZonaTech_User_Auth::get_user_dashboard_data();
        } catch (Exception $e) {
            error_log('ZonaTech Dashboard Error: ' . $e->getMessage());
            // Cache wp_get_current_user() call
            $current_user = wp_get_current_user();
            $user_data = array(
                'user' => array(
                    'display_name' => $current_user->display_name,
                    'first_name' => $current_user->first_name ?: 'User',
                    'last_name' => $current_user->last_name,
                    'email' => $current_user->user_email,
                    'avatar' => get_avatar_url(get_current_user_id()),
                    'phone' => '',
                    'registered' => $current_user->user_registered
                ),
                'stats' => array(
                    'subjects' => 0,
                    'quizzes' => 0,
                    'purchases' => 0,
                    'total_spent' => 0
                )
            );
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Get exam types with fallback
     */
    private function get_exam_types_safe() {
        try {
            $exam_types = ZonaTech_Past_Questions::get_exam_types();
            if (!is_array($exam_types) || empty($exam_types)) {
                return self::get_fallback_exam_types();
            }
            return $exam_types;
        } catch (Exception $e) {
            error_log('ZonaTech get_exam_types Error: ' . $e->getMessage());
            return self::get_fallback_exam_types();
        }
    }
    
    /**
     * Render Past Questions page
     * This should ALWAYS work for logged-in users - no redirect to dashboard
     */
    public function render_past_questions() {
        // If user is logged in, show past questions content directly
        if ($this->check_login()) {
            $exam_types = $this->get_exam_types_safe();
            $is_guest = false;
            
            ob_start();
            include ZONATECH_PLUGIN_DIR . 'templates/past-questions.php';
            return ob_get_clean();
        }
        
        // Not logged in - show login required (no auto-redirect)
        return $this->render_login_required('past-questions');
    }
    
    public function render_nin_service() {
        // Check login - show login required if not logged in
        if (!$this->check_login()) {
            return $this->render_login_required('nin-service');
        }
        
        $is_guest = false;
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/nin-service.php';
        return ob_get_clean();
    }
    
    public function render_scratch_cards() {
        // Check login - show login required if not logged in
        if (!$this->check_login()) {
            return $this->render_login_required('scratch-cards');
        }
        
        // Get card types with error handling
        try {
            $card_types = ZonaTech_Scratch_Cards::get_card_types();
        } catch (Exception $e) {
            error_log('ZonaTech Scratch Cards Error: ' . $e->getMessage());
            $card_types = array();
        }
        
        $is_guest = false;
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/scratch-cards.php';
        return ob_get_clean();
    }
    
    public function render_payment() {
        // Check login - show login required if not logged in
        if (!$this->check_login()) {
            return $this->render_login_required('payment');
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/payment.php';
        return ob_get_clean();
    }
    
    public function render_homepage() {
        // Get exam types using safe helper
        $exam_types = $this->get_exam_types_safe();
        
        // Get card types with error handling
        try {
            $card_types = ZonaTech_Scratch_Cards::get_card_types();
        } catch (Exception $e) {
            $card_types = array();
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/homepage.php';
        return ob_get_clean();
    }
    
    public function render_feedback() {
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/feedback.php';
        return ob_get_clean();
    }
    
    public function render_admin_dashboard() {
        // Check if user is admin
        if (!current_user_can('manage_options')) {
            $dashboard_url = site_url('/zonatech-dashboard/');
            $delay_ms = self::REDIRECT_DELAY_MS;
            ob_start();
            ?>
            <div class="zonatech-container">
                <div class="glass-card text-center" style="padding: 3rem; max-width: 500px; margin: 2rem auto;">
                    <i class="fas fa-lock" style="font-size: 4rem; color: #ef4444; margin-bottom: 1.5rem;"></i>
                    <h2 class="text-white" style="margin-bottom: 1rem;">Access Denied</h2>
                    <p class="text-muted" style="margin-bottom: 1.5rem;">You don't have permission to access the admin dashboard.</p>
                    <a href="<?php echo esc_url($dashboard_url); ?>" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                </div>
            </div>
            <script>
            setTimeout(function() {
                window.location.href = '<?php echo esc_js($dashboard_url); ?>';
            }, <?php echo intval($delay_ms); ?>);
            </script>
            <?php
            return ob_get_clean();
        }
        
        ob_start();
        include ZONATECH_PLUGIN_DIR . 'templates/admin-dashboard.php';
        return ob_get_clean();
    }
}