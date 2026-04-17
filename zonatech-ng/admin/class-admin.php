<?php
/**
 * Admin Panel Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array($this, 'show_setup_notice'));
        
        // AJAX handlers for admin actions
        add_action('wp_ajax_zonatech_save_pricing', array($this, 'handle_save_pricing'));
        add_action('wp_ajax_zonatech_get_pricing', array($this, 'handle_get_pricing'));
        
        // AJAX handlers for user access management
        add_action('wp_ajax_zonatech_search_users', array($this, 'handle_search_users'));
        add_action('wp_ajax_zonatech_grant_access', array($this, 'handle_grant_access'));
        add_action('wp_ajax_zonatech_revoke_access', array($this, 'handle_revoke_access'));
        add_action('wp_ajax_zonatech_get_user_access', array($this, 'handle_get_user_access'));
    }
    
    public function show_setup_notice() {
        // Only show on ZonaTech pages or when keys are not configured
        $screen = get_current_screen();
        
        if (empty(ZONATECH_PAYSTACK_PUBLIC_KEY) || empty(ZONATECH_PAYSTACK_SECRET_KEY)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>ZonaTech NG:</strong> Paystack API keys are not configured. 
                    Payment functionality will not work until you configure your keys.
                    <a href="<?php echo admin_url('admin.php?page=zonatech-settings'); ?>">Configure now</a>
                </p>
            </div>
            <?php
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'ZonaTech NG',
            'ZonaTech NG',
            'manage_options',
            'zonatech-ng',
            array($this, 'render_dashboard'),
            'dashicons-welcome-learn-more',
            30
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'zonatech-ng',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Questions',
            'Questions',
            'manage_options',
            'zonatech-questions',
            array($this, 'render_questions')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Scratch Cards',
            'Scratch Cards',
            'manage_options',
            'zonatech-cards',
            array($this, 'render_cards')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Activity Log',
            'Activity Log',
            'manage_options',
            'zonatech-activity',
            array($this, 'render_activity')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Users',
            'Users',
            'manage_options',
            'zonatech-users',
            array($this, 'render_users')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'User Access',
            'User Access',
            'manage_options',
            'zonatech-user-access',
            array($this, 'render_user_access')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Feedback',
            'Feedback',
            'manage_options',
            'zonatech-feedback',
            array($this, 'render_feedback')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Pricing',
            'Pricing',
            'manage_options',
            'zonatech-pricing',
            array($this, 'render_pricing')
        );
        
        add_submenu_page(
            'zonatech-ng',
            'Settings',
            'Settings',
            'manage_options',
            'zonatech-settings',
            array($this, 'render_settings')
        );
    }
    
    public function register_settings() {
        // Paystack settings
        register_setting('zonatech_settings', 'zonatech_paystack_public_key');
        register_setting('zonatech_settings', 'zonatech_paystack_secret_key');
        
        // Support settings
        register_setting('zonatech_settings', 'zonatech_whatsapp_number');
        register_setting('zonatech_settings', 'zonatech_support_email');
        
        // Pricing settings - Exam types
        register_setting('zonatech_pricing', 'zonatech_subject_price');
        register_setting('zonatech_pricing', 'zonatech_monthly_price');
        register_setting('zonatech_pricing', 'zonatech_6month_price');
        register_setting('zonatech_pricing', 'zonatech_free_questions_limit');
        
        // Pricing settings - Scratch Cards
        register_setting('zonatech_pricing', 'zonatech_scratch_card_price');
        register_setting('zonatech_pricing', 'zonatech_waec_card_price');
        register_setting('zonatech_pricing', 'zonatech_neco_card_price');
        register_setting('zonatech_pricing', 'zonatech_jamb_card_price');
        
        // Pricing settings - NIN Services
        register_setting('zonatech_pricing', 'zonatech_nin_slip_price');
        register_setting('zonatech_pricing', 'zonatech_nin_standard_slip_price');
        register_setting('zonatech_pricing', 'zonatech_nin_slip_download_price');
        register_setting('zonatech_pricing', 'zonatech_nin_modification_price');
        register_setting('zonatech_pricing', 'zonatech_nin_dob_correction_price');
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'zonatech') === false) {
            return;
        }
        
        wp_enqueue_style('zonatech-admin', ZONATECH_PLUGIN_URL . 'assets/css/admin.css', array(), ZONATECH_VERSION);
        wp_enqueue_script('zonatech-admin', ZONATECH_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ZONATECH_VERSION, true);
        
        wp_localize_script('zonatech-admin', 'zonatech_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zonatech_nonce')
        ));
    }
    
    /**
     * Get price from options with fallback to constant
     */
    public static function get_price($option_name, $constant_name) {
        $price = get_option($option_name);
        if ($price === false || $price === '') {
            return defined($constant_name) ? constant($constant_name) : 0;
        }
        return intval($price);
    }
    
    /**
     * Handle AJAX save pricing
     */
    public function handle_save_pricing() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }
        
        $pricing_fields = array(
            'zonatech_subject_price',
            'zonatech_monthly_price',
            'zonatech_6month_price',
            'zonatech_free_questions_limit',
            'zonatech_scratch_card_price',
            'zonatech_waec_card_price',
            'zonatech_neco_card_price',
            'zonatech_jamb_card_price',
            'zonatech_nin_slip_price',
            'zonatech_nin_standard_slip_price',
            'zonatech_nin_slip_download_price',
            'zonatech_nin_modification_price',
            'zonatech_nin_dob_correction_price'
        );
        
        foreach ($pricing_fields as $field) {
            if (isset($_POST[$field])) {
                $value = intval($_POST[$field]);
                update_option($field, $value);
            }
        }
        
        wp_send_json_success(array('message' => 'Pricing updated successfully!'));
    }
    
    /**
     * Handle AJAX get pricing
     */
    public function handle_get_pricing() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }
        
        $pricing = array(
            'subject_price' => self::get_price('zonatech_subject_price', 'ZONATECH_SUBJECT_PRICE'),
            'monthly_price' => self::get_price('zonatech_monthly_price', 'ZONATECH_MONTHLY_PRICE'),
            '6month_price' => self::get_price('zonatech_6month_price', 'ZONATECH_6MONTH_PRICE'),
            'free_questions_limit' => self::get_price('zonatech_free_questions_limit', 'ZONATECH_FREE_QUESTIONS_LIMIT'),
            'scratch_card_price' => self::get_price('zonatech_scratch_card_price', 'ZONATECH_SCRATCH_CARD_PRICE'),
            'waec_card_price' => self::get_price('zonatech_waec_card_price', 'ZONATECH_WAEC_CARD_PRICE'),
            'neco_card_price' => self::get_price('zonatech_neco_card_price', 'ZONATECH_NECO_CARD_PRICE'),
            'jamb_card_price' => self::get_price('zonatech_jamb_card_price', 'ZONATECH_SCRATCH_CARD_PRICE'),
            'nin_slip_price' => self::get_price('zonatech_nin_slip_price', 'ZONATECH_NIN_SLIP_PRICE'),
            'nin_standard_slip_price' => self::get_price('zonatech_nin_standard_slip_price', 'ZONATECH_NIN_STANDARD_SLIP_PRICE'),
            'nin_slip_download_price' => self::get_price('zonatech_nin_slip_download_price', 'ZONATECH_NIN_SLIP_DOWNLOAD_PRICE'),
            'nin_modification_price' => self::get_price('zonatech_nin_modification_price', 'ZONATECH_NIN_MODIFICATION_PRICE'),
            'nin_dob_correction_price' => self::get_price('zonatech_nin_dob_correction_price', 'ZONATECH_NIN_DOB_CORRECTION_PRICE'),
        );
        
        wp_send_json_success($pricing);
    }
    
    public function render_dashboard() {
        global $wpdb;
        
        // Get statistics
        $users_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
        
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $table_purchases WHERE status = 'completed'") ?? 0;
        $purchases_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_purchases WHERE status = 'completed'");
        
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        $quizzes_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_quiz");
        
        $recent_activities = ZonaTech_Activity_Log::get_recent_activities(20);
        
        include ZONATECH_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    public function render_questions() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/questions.php';
    }
    
    public function render_cards() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/cards.php';
    }
    
    public function render_activity() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/activity.php';
    }
    
    public function render_users() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/users.php';
    }
    
    public function render_feedback() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/feedback.php';
    }
    
    public function render_pricing() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/pricing.php';
    }
    
    public function render_settings() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    public function render_user_access() {
        include ZONATECH_PLUGIN_DIR . 'admin/views/user-access.php';
    }
    
    /**
     * AJAX: Search users by email or username
     */
    public function handle_search_users() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        if (strlen($search) < 2) {
            wp_send_json_error(array('message' => 'Please enter at least 2 characters.'));
            return;
        }
        
        $users = get_users(array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'user_email', 'display_name'),
            'number' => 20,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        $results = array();
        foreach ($users as $user) {
            $results[] = array(
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'username' => $user->user_login
            );
        }
        
        wp_send_json_success(array('users' => $results));
    }
    
    /**
     * AJAX: Grant access to a user (admin manually approves)
     */
    public function handle_grant_access() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $exam_type = strtolower(sanitize_text_field($_POST['exam_type'] ?? ''));
        $category = sanitize_text_field($_POST['category'] ?? '');
        $duration = sanitize_text_field($_POST['duration'] ?? 'monthly');
        
        if (!$user_id || !$exam_type) {
            wp_send_json_error(array('message' => 'Please fill in all required fields.'));
            return;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found.'));
            return;
        }
        
        $valid_exam_types = array('jamb', 'waec', 'neco', 'nin');
        if (!in_array($exam_type, $valid_exam_types)) {
            wp_send_json_error(array('message' => 'Invalid exam type.'));
            return;
        }
        
        $valid_categories = array('science', 'arts', 'business', 'all', 'past_questions', 'scratch_cards', 'nin_service');
        if (!empty($category) && !in_array($category, $valid_categories)) {
            wp_send_json_error(array('message' => 'Invalid category.'));
            return;
        }
        
        // Default category if not provided
        if (empty($category)) {
            if ($exam_type === 'nin') {
                $category = 'nin_service';
            } else {
                $category = 'all';
            }
        }
        
        // Calculate expiration
        switch ($duration) {
            case 'sixmonth':
                $expires_at = date('Y-m-d H:i:s', strtotime('+6 months'));
                break;
            case 'lifetime':
                $expires_at = null;
                break;
            case 'monthly':
            default:
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 month'));
                break;
        }
        
        // Determine which categories to grant
        $single_category_types = array('scratch_cards', 'nin_service', 'past_questions');
        if ($category === 'all') {
            $categories_to_grant = array('science', 'arts', 'business');
        } elseif (in_array($category, $single_category_types)) {
            $categories_to_grant = array($category);
        } else {
            $categories_to_grant = array($category);
        }
        
        global $wpdb;
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        
        // Ensure the table exists and has the category column
        self::ensure_access_table($wpdb, $table_access);
        
        $granted = array();
        $skipped = array();
        
        foreach ($categories_to_grant as $cat) {
            // Check if user already has active access for this exam_type + category
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_access 
                 WHERE user_id = %d AND exam_type = %s AND category = %s 
                 AND (expires_at IS NULL OR expires_at > NOW())",
                $user_id,
                $exam_type,
                $cat
            ));
            
            if ($existing) {
                $skipped[] = ucfirst($cat);
                continue;
            }
            
            // Use raw SQL to properly handle NULL values (wpdb->insert can fail with NULLs)
            if ($expires_at === null) {
                $result = $wpdb->query($wpdb->prepare(
                    "INSERT INTO $table_access (user_id, exam_type, category, subject, purchase_id, expires_at, created_at) 
                     VALUES (%d, %s, %s, %s, 0, NULL, NOW())",
                    $user_id,
                    $exam_type,
                    $cat,
                    ''
                ));
            } else {
                $result = $wpdb->query($wpdb->prepare(
                    "INSERT INTO $table_access (user_id, exam_type, category, subject, purchase_id, expires_at, created_at) 
                     VALUES (%d, %s, %s, %s, 0, %s, NOW())",
                    $user_id,
                    $exam_type,
                    $cat,
                    '',
                    $expires_at
                ));
            }
            
            if ($result !== false && $result > 0) {
                $granted[] = ucfirst($cat);
            } else {
                error_log('ZonaTech: Failed to insert access record. DB Error: ' . $wpdb->last_error);
            }
        }
        
        if (empty($granted) && !empty($skipped)) {
            wp_send_json_error(array('message' => 'User already has active access for: ' . implode(', ', $skipped) . '.'));
            return;
        }
        
        if (empty($granted)) {
            wp_send_json_error(array('message' => 'Failed to grant access. DB Error: ' . $wpdb->last_error));
            return;
        }
        
        // Log the activity
        if (class_exists('ZonaTech_Activity_Log')) {
            $duration_label = $duration === 'lifetime' ? 'lifetime' : ($duration === 'sixmonth' ? '6 months' : '1 month');
            $cat_label = ($category === 'all') ? 'All Categories' : ucfirst($category);
            ZonaTech_Activity_Log::log(
                $user_id,
                'admin_grant_access',
                sprintf('Admin granted %s %s access (%s)', strtoupper($exam_type), $cat_label, $duration_label),
                array('exam_type' => $exam_type, 'category' => $category, 'duration' => $duration, 'granted_by' => get_current_user_id())
            );
        }
        
        $message = sprintf('Access granted to %s for %s %s.', esc_html($user->display_name), strtoupper($exam_type), implode(', ', $granted));
        if (!empty($skipped)) {
            $message .= ' (Already had access to: ' . implode(', ', $skipped) . ')';
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * AJAX: Revoke access for a user
     */
    public function handle_revoke_access() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }
        
        $access_id = intval($_POST['access_id'] ?? 0);
        
        if (!$access_id) {
            wp_send_json_error(array('message' => 'Invalid access record.'));
            return;
        }
        
        global $wpdb;
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        
        // Get the record first for logging
        $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_access WHERE id = %d", $access_id));
        
        if (!$record) {
            wp_send_json_error(array('message' => 'Access record not found.'));
            return;
        }
        
        $result = $wpdb->delete($table_access, array('id' => $access_id), array('%d'));
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to revoke access. Please try again.'));
            return;
        }
        
        // Log the activity
        if (class_exists('ZonaTech_Activity_Log')) {
            ZonaTech_Activity_Log::log(
                $record->user_id,
                'admin_revoke_access',
                sprintf('Admin revoked %s %s access', strtoupper($record->exam_type), ucfirst($record->category ?: $record->subject ?: '')),
                array('access_id' => $access_id, 'revoked_by' => get_current_user_id())
            );
        }
        
        wp_send_json_success(array('message' => 'Access revoked successfully.'));
    }
    
    /**
     * AJAX: Get access records for a specific user
     */
    public function handle_get_user_access() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'));
            return;
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Invalid user.'));
            return;
        }
        
        global $wpdb;
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        
        $records = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_access WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        $formatted = array();
        foreach ($records as $record) {
            $is_active = empty($record->expires_at) || strtotime($record->expires_at) > time();
            $formatted[] = array(
                'id' => $record->id,
                'exam_type' => strtoupper($record->exam_type),
                'category' => ucfirst($record->category ?: ''),
                'subject' => $record->subject ?: '',
                'purchase_id' => $record->purchase_id,
                'status' => $is_active ? 'active' : 'expired',
                'expires_at' => $record->expires_at ? date('M j, Y g:i A', strtotime($record->expires_at)) : 'Lifetime',
                'created_at' => date('M j, Y g:i A', strtotime($record->created_at))
            );
        }
        
        $user = get_userdata($user_id);
        
        wp_send_json_success(array(
            'records' => $formatted,
            'user' => array(
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email
            )
        ));
    }
    
    /**
     * Ensure the zonatech_user_access table exists and has the category column.
     * Older installations may have the table without the category column.
     */
    private static function ensure_access_table($wpdb, $table_access) {
        // Check if the table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_access));
        
        if ($table_exists !== $table_access) {
            // Table doesn't exist — create it with full schema
            $charset_collate = $wpdb->get_charset_collate();
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query("CREATE TABLE IF NOT EXISTS `$table_access` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `user_id` bigint(20) NOT NULL,
                `exam_type` varchar(20) NOT NULL,
                `subject` varchar(100) DEFAULT NULL,
                `category` varchar(50) DEFAULT NULL,
                `purchase_id` bigint(20) DEFAULT NULL,
                `expires_at` datetime DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `user_id` (`user_id`),
                KEY `exam_subject` (`exam_type`, `subject`),
                KEY `exam_category` (`exam_type`, `category`)
            ) $charset_collate;");
        } else {
            // Table exists — check if category column exists
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM `$table_access` LIKE 'category'");
            if (empty($column_exists)) {
                // Add the category column
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $wpdb->query("ALTER TABLE `$table_access` ADD COLUMN `category` varchar(50) DEFAULT NULL AFTER `subject`");
                // Add index for category
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $wpdb->query("ALTER TABLE `$table_access` ADD KEY `exam_category` (`exam_type`, `category`)");
            }
            
            // Ensure subject column allows NULL (older installs may have NOT NULL)
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query("ALTER TABLE `$table_access` MODIFY COLUMN `subject` varchar(100) DEFAULT NULL");
            // Ensure purchase_id column allows NULL
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query("ALTER TABLE `$table_access` MODIFY COLUMN `purchase_id` bigint(20) DEFAULT NULL");
        }
    }
}