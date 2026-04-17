<?php
/**
 * User Authentication Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_User_Auth {
    const NONCE_REFRESH_RATE_LIMIT = 10;
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_nopriv_zonatech_register', array($this, 'handle_register'));
        add_action('wp_ajax_zonatech_register', array($this, 'handle_register')); // Also for logged-in users
        add_action('wp_ajax_nopriv_zonatech_verify_email', array($this, 'handle_verify_email'));
        add_action('wp_ajax_zonatech_verify_email', array($this, 'handle_verify_email')); // Also for logged-in users
        add_action('wp_ajax_nopriv_zonatech_resend_verification', array($this, 'handle_resend_verification'));
        add_action('wp_ajax_zonatech_resend_verification', array($this, 'handle_resend_verification')); // Also for logged-in users
        add_action('wp_ajax_nopriv_zonatech_login', array($this, 'handle_login'));
        add_action('wp_ajax_zonatech_logout', array($this, 'handle_logout'));
        add_action('wp_ajax_nopriv_zonatech_refresh_nonce', array($this, 'handle_refresh_nonce'));
        add_action('wp_ajax_zonatech_refresh_nonce', array($this, 'handle_refresh_nonce'));
        add_action('wp_ajax_nopriv_zonatech_reset_password', array($this, 'handle_reset_password'));
        add_action('wp_ajax_zonatech_update_profile', array($this, 'handle_update_profile'));
        add_action('wp_ajax_zonatech_change_password', array($this, 'handle_change_password'));
        add_action('wp_ajax_zonatech_upload_avatar', array($this, 'handle_upload_avatar'));
        
        // Background email hook
        add_action('zonatech_send_welcome_email', array($this, 'send_welcome_email_background'), 10, 2);
    }
    
    /**
     * Send welcome email in background (scheduled task)
     */
    public function send_welcome_email_background($email, $first_name) {
        try {
            $this->send_approval_email($email, $first_name);
        } catch (Exception $e) {
            error_log('ZonaTech: Background welcome email failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Handle avatar upload
     */
    public function handle_upload_avatar() {
        $nonce_value = isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '';
        $nonce_valid = wp_verify_nonce($nonce_value, 'zonatech_nonce');
        if (!$nonce_valid) {
            wp_send_json_error(array(
                'message' => 'Security check failed. Please refresh the page and try again.',
                'code' => 'nonce_invalid'
            ));
            return;
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in.'));
            return;
        }
        
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => 'No file uploaded or upload error.'));
            return;
        }
        
        $file = $_FILES['avatar'];
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'Invalid file type. Only JPG, PNG, GIF and WebP allowed.'));
            return;
        }
        
        // Max 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'File too large. Maximum size is 2MB.'));
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('avatar', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Delete old avatar if exists
        $old_avatar_id = get_user_meta($user_id, 'zonatech_avatar_id', true);
        if ($old_avatar_id) {
            wp_delete_attachment($old_avatar_id, true);
        }
        
        // Save new avatar
        update_user_meta($user_id, 'zonatech_avatar_id', $attachment_id);
        
        $avatar_url = wp_get_attachment_url($attachment_id);
        
        wp_send_json_success(array(
            'message' => 'Avatar updated successfully!',
            'avatar_url' => $avatar_url
        ));
    }
    
    /**
     * Generate a 6-digit verification code
     */
    private function generate_verification_code() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get email HTML template with glassmorphism design
     */
    private function get_email_template($title, $content, $button_text = '', $button_url = '') {
        $button_html = '';
        if ($button_text && $button_url) {
            $button_html = '
            <a href="' . esc_url($button_url) . '" style="
                display: inline-block;
                background: linear-gradient(135deg, #9333ea 0%, #7c3aed 50%, #6366f1 100%);
                color: #ffffff !important;
                text-decoration: none;
                padding: 14px 32px;
                border-radius: 50px;
                font-weight: 600;
                font-size: 16px;
                margin: 20px 0;
                box-shadow: 0 8px 25px rgba(147, 51, 234, 0.4);
            ">' . esc_html($button_text) . '</a>';
        }
        
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($title) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Inter\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #16213e 100%); min-height: 100vh;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #16213e 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 30px 0;">
                            <div style="
                                background: rgba(147, 51, 234, 0.2);
                                backdrop-filter: blur(10px);
                                border-radius: 20px;
                                padding: 20px 40px;
                                border: 1px solid rgba(147, 51, 234, 0.3);
                                display: inline-block;
                            ">
                                <span style="font-size: 28px; font-weight: 700; color: #ffffff;">
                                    🎓 ZonaTech NG
                                </span>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Main Card -->
                    <tr>
                        <td>
                            <div style="
                                background: rgba(255, 255, 255, 0.08);
                                backdrop-filter: blur(20px);
                                border-radius: 24px;
                                padding: 40px;
                                border: 1px solid rgba(255, 255, 255, 0.1);
                                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                            ">
                                <h1 style="
                                    color: #ffffff;
                                    font-size: 24px;
                                    font-weight: 700;
                                    margin: 0 0 20px 0;
                                    text-align: center;
                                ">' . esc_html($title) . '</h1>
                                
                                <div style="color: rgba(255, 255, 255, 0.9); font-size: 16px; line-height: 1.7;">
                                    ' . $content . '
                                </div>
                                
                                <div style="text-align: center; margin: 30px 0;">
                                    ' . $button_html . '
                                </div>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 30px 0;">
                            <div style="
                                background: rgba(255, 255, 255, 0.05);
                                border-radius: 16px;
                                padding: 25px;
                                border: 1px solid rgba(255, 255, 255, 0.08);
                            ">
                                <p style="color: rgba(255, 255, 255, 0.7); font-size: 14px; margin: 0 0 10px 0;">
                                    Need help? Contact us:
                                </p>
                                <p style="color: #9333ea; font-size: 14px; margin: 0;">
                                    📧 ' . ZONATECH_SUPPORT_EMAIL . '<br>
                                    📱 WhatsApp: ' . ZONATECH_WHATSAPP_NUMBER . '
                                </p>
                                <p style="color: rgba(255, 255, 255, 0.5); font-size: 12px; margin: 20px 0 0 0;">
                                    © ' . date('Y') . ' ZonaTech NG. All rights reserved.
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Send verification email
     */
    private function send_verification_email($email, $first_name, $code) {
        $subject = 'Verify Your Email - ZonaTech NG';
        
        $content = '
            <p style="margin: 0 0 15px 0;">Hi <strong>' . esc_html($first_name) . '</strong>,</p>
            <p style="margin: 0 0 15px 0;">Thank you for registering with ZonaTech NG! 🎉</p>
            <p style="margin: 0 0 20px 0;">Your verification code is:</p>
            <div style="
                background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);
                border-radius: 16px;
                padding: 25px;
                text-align: center;
                margin: 20px 0;
            ">
                <span style="
                    font-size: 36px;
                    font-weight: 700;
                    color: #ffffff;
                    letter-spacing: 8px;
                    font-family: monospace;
                ">' . esc_html($code) . '</span>
            </div>
            <p style="margin: 20px 0 15px 0;">Enter this code on the verification page to complete your registration.</p>
            <p style="
                color: rgba(255, 255, 255, 0.6);
                font-size: 14px;
                margin: 15px 0 0 0;
                padding: 15px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 10px;
            ">
                ⏰ This code will expire in 30 minutes.
            </p>
        ';
        
        $message = $this->get_email_template($subject, $content);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <support@zonatechng.com>'
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Send account approved email
     */
    private function send_approval_email($email, $first_name) {
        $subject = 'Account Approved - Welcome to ZonaTech NG!';
        
        $content = '
            <p style="margin: 0 0 15px 0;">Hi <strong>' . esc_html($first_name) . '</strong>,</p>
            <p style="margin: 0 0 15px 0;">Great news! Your ZonaTech NG account has been verified and approved. 🎉</p>
            <p style="margin: 0 0 20px 0;">You can now log in and access:</p>
            <ul style="
                list-style: none;
                padding: 0;
                margin: 0 0 20px 0;
            ">
                <li style="padding: 8px 0; color: rgba(255, 255, 255, 0.9);">✅ JAMB, WAEC, and NECO past questions</li>
                <li style="padding: 8px 0; color: rgba(255, 255, 255, 0.9);">✅ Scratch cards and PINs</li>
                <li style="padding: 8px 0; color: rgba(255, 255, 255, 0.9);">✅ NIN verification services</li>
                <li style="padding: 8px 0; color: rgba(255, 255, 255, 0.9);">✅ And much more!</li>
            </ul>
            <p style="margin: 20px 0 0 0;">Click the button below to log in to your account:</p>
        ';
        
        $message = $this->get_email_template($subject, $content, 'Login Now', home_url('/zonatech-login/'));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <support@zonatechng.com>'
        );
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    public function handle_register() {
        // Only clear output buffers for AJAX requests if they have content
        if (defined('DOING_AJAX') && DOING_AJAX) {
            while (ob_get_level() > 0 && ob_get_length() > 0) {
                ob_end_clean();
            }
        }
        
        // Set JSON content type early to ensure proper response handling
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }
        
        // Verify nonce with proper WordPress slashing handling
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $nonce_value = sanitize_key(wp_unslash($_POST['nonce']));
            $nonce_valid = wp_verify_nonce($nonce_value, 'zonatech_nonce');
        }
        
        if (!$nonce_valid) {
            echo wp_json_encode(array(
                'success' => false,
                'data' => array(
                    'message' => 'Security check failed. Please refresh the page and try again.',
                    'code' => 'nonce_invalid'
                )
            ));
            exit;
        }
        
        // Get and sanitize input with proper WordPress slashing handling
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $last_name = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $confirm_password = isset($_POST['confirm_password']) ? wp_unslash($_POST['confirm_password']) : '';
        
        // Validation - check all required fields first
        if (empty($first_name)) {
            echo wp_json_encode(array('success' => false, 'data' => array('message' => 'First name is required.')));
            exit;
        }
        
        if (empty($last_name)) {
            echo wp_json_encode(array('success' => false, 'data' => array('message' => 'Last name is required.')));
            exit;
        }
        
        if (empty($email)) {
            echo wp_json_encode(array('success' => false, 'data' => array('message' => 'Email address is required.')));
            exit;
        }
        
        // Use a standalone email validation that doesn't depend on WordPress's is_email()
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo wp_json_encode(array('success' => false, 'data' => array('message' => 'Please enter a valid email address.')));
            exit;
        }
        
        if (empty($password)) {
            echo wp_json_encode(array('success' => false, 'data' => array('message' => 'Password is required.')));
            exit;
        }
        
        if (strlen($password) < 6) {
            echo wp_json_encode(array('success' => false, 'data' => array('message' => 'Password must be at least 6 characters long.')));
            exit;
        }
        
        if ($password !== $confirm_password) {
            echo wp_json_encode(array('success' => false, 'data' => array('message' => 'Passwords do not match.')));
            exit;
        }
        
        // Check if email already exists
        if (email_exists($email)) {
            echo wp_json_encode(array('success' => false, 'data' => array('message' => 'An account with this email already exists. Please login instead.')));
            exit;
        }
        
        // Generate unique username from first+last name
        $base_username = sanitize_user(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name . $last_name)));
        if (empty($base_username) || strlen($base_username) < 3) {
            // Fallback to email prefix - safely extract local part
            if (strpos($email, '@') !== false) {
                $email_parts = explode('@', $email);
                $base_username = sanitize_user(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $email_parts[0])));
            }
        }
        if (empty($base_username) || strlen($base_username) < 3) {
            $base_username = 'user';
        }
        
        // Ensure username is unique
        $username = $base_username . wp_rand(100, 999);
        $max_attempts = 20;
        $attempts = 0;
        while (username_exists($username) && $attempts < $max_attempts) {
            $username = $base_username . wp_rand(1000, 9999);
            $attempts++;
        }
        if (username_exists($username)) {
            $username = $base_username . '_' . time();
        }
        
        // Temporarily remove third-party filters that may block user creation
        // This prevents password complexity plugins, registration restrictions, etc.
        // from interfering with our registration process
        remove_all_filters('user_profile_update_errors');
        remove_all_filters('registration_errors');
        
        // Create WordPress user directly
        $user_data = array(
            'user_login'    => $username,
            'user_email'    => $email,
            'user_pass'     => $password,
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'display_name'  => $first_name . ' ' . $last_name,
            'role'          => 'subscriber'
        );
        
        $user_id = wp_insert_user($user_data);
        
        // If wp_insert_user fails, try direct database insertion as fallback
        if (is_wp_error($user_id)) {
            $wp_error_message = $user_id->get_error_message();
            error_log('ZonaTech Registration: wp_insert_user failed - ' . $wp_error_message . '. Attempting direct insertion.');
            
            $user_id = $this->create_user_directly($username, $email, $password, $first_name, $last_name);
            
            if (is_wp_error($user_id)) {
                $error_message = $user_id->get_error_message();
                error_log('ZonaTech Registration Error (fallback): ' . $error_message);
                echo wp_json_encode(array('success' => false, 'data' => array('message' => 'Registration failed. Please try again or contact support.')));
                exit;
            }
        }
        
        // Save additional user meta (check return values, fail silently)
        if (!empty($phone)) {
            if (!update_user_meta($user_id, 'phone', $phone)) {
                error_log('ZonaTech: Failed to save phone for user ' . $user_id);
            }
        }
        if (!update_user_meta($user_id, 'zonatech_registered', current_time('mysql'))) {
            error_log('ZonaTech: Failed to save registration time for user ' . $user_id);
        }
        if (!update_user_meta($user_id, 'zonatech_email_verified', 1)) {
            error_log('ZonaTech: Failed to save email_verified flag for user ' . $user_id);
        }
        
        // Log activity (check if class/method exists, fail silently)
        if (class_exists('ZonaTech_Activity_Log') && method_exists('ZonaTech_Activity_Log', 'log')) {
            // Call with error suppression since it might have issues
            @ZonaTech_Activity_Log::log($user_id, 'registration', 'Account created');
        }
        
        // Send welcome email (suppress errors - don't block registration)
        @$this->send_approval_email($email, $first_name);
        
        // Return success immediately using direct echo to ensure it's sent
        echo wp_json_encode(array(
            'success' => true, 
            'data' => array(
                'message' => 'Account created successfully! Redirecting to login...',
                'redirect' => home_url('/zonatech-login/')
            )
        ));
        exit;
    }
    
    /**
     * Create a user directly in the database, bypassing WordPress hooks and filters
     * that may block user creation (e.g., password complexity plugins, registration restrictions).
     * This is used as a fallback when wp_insert_user() fails due to non-core WordPress rules.
     */
    private function create_user_directly($username, $email, $password, $first_name, $last_name) {
        global $wpdb;
        
        // Double-check email doesn't exist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} WHERE user_email = %s LIMIT 1",
            $email
        ));
        if ($existing) {
            return new WP_Error('existing_user_email', 'An account with this email already exists.');
        }
        
        // Hash the password using WordPress's hashing (this is a standalone function, not filtered)
        $hashed_password = wp_hash_password($password);
        
        $result = $wpdb->insert(
            $wpdb->users,
            array(
                'user_login'          => $username,
                'user_pass'           => $hashed_password,
                'user_nicename'       => sanitize_title($username),
                'user_email'          => $email,
                'user_registered'     => current_time('mysql', true),
                'user_status'         => 0,
                'display_name'        => $first_name . ' ' . $last_name,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            error_log('ZonaTech: Direct DB insert failed - ' . $wpdb->last_error);
            return new WP_Error('db_insert_error', 'Could not create account. Please try again.');
        }
        
        $user_id = (int) $wpdb->insert_id;
        
        // Set user role and basic meta
        update_user_meta($user_id, $wpdb->prefix . 'capabilities', array('subscriber' => true));
        update_user_meta($user_id, $wpdb->prefix . 'user_level', 0);
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'nickname', $first_name);
        
        // Clear caches so WordPress recognizes the new user
        clean_user_cache($user_id);
        wp_cache_delete($email, 'useremail');
        wp_cache_delete($username, 'userlogins');
        
        return $user_id;
    }
    
    public function handle_verify_email() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'zonatech_pending_users';
        $pending_user_id = 0;
        $user_id = null;
        
        try {
            // Verify nonce
            $nonce_value = isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '';
            if (!wp_verify_nonce($nonce_value, 'zonatech_nonce')) {
                error_log('ZonaTech: Verification failed - invalid nonce');
                wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
                return;
            }
            
            $pending_user_id = intval($_POST['pending_user_id'] ?? 0);
            $verification_code = sanitize_text_field($_POST['verification_code'] ?? '');
            
            // Log the verification attempt for debugging
            error_log('ZonaTech: Verification attempt - pending_user_id: ' . $pending_user_id . ', code: ' . $verification_code);
            
            if (empty($pending_user_id) || empty($verification_code)) {
                error_log('ZonaTech: Verification failed - missing pending_user_id or code');
                wp_send_json_error(array('message' => 'Verification code is required.'));
                return;
            }
            
            // Check if table exists (efficient single table check)
            // Using LIKE with literal string is safe here since table_name is constructed from wpdb->prefix
            $table_check = $wpdb->get_var("SHOW TABLES LIKE '" . esc_sql($table_name) . "'");
            $table_exists = ($table_check === $table_name);
            
            if (!$table_exists) {
                error_log('ZonaTech: Verification failed - pending_users table does not exist');
                wp_send_json_error(array('message' => 'System error. Please contact support.'));
                return;
            }
            
            // Get pending registration with both ID and code match
            $pending = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM `" . esc_sql($table_name) . "` WHERE id = %d AND verification_code = %s",
                $pending_user_id,
                $verification_code
            ));
        
            if (!$pending) {
                // Try to get just by ID to give better error message
                $pending_by_id = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM `" . esc_sql($table_name) . "` WHERE id = %d",
                    $pending_user_id
                ));
                
                if (!$pending_by_id) {
                    error_log('ZonaTech: Verification failed - no pending registration found for id: ' . $pending_user_id);
                    wp_send_json_error(array('message' => 'Registration not found. Please register again.'));
                } else {
                    error_log('ZonaTech: Verification failed - invalid code for pending_user_id: ' . $pending_user_id);
                    wp_send_json_error(array('message' => 'Invalid verification code. Please check and try again.'));
                }
                return;
            }
            
            // Check if expired
            if (strtotime($pending->expires_at) < time()) {
                // Delete the expired pending record so user can re-register
                $wpdb->delete($table_name, array('id' => $pending_user_id), array('%d'));
                error_log('ZonaTech: Verification failed - code expired for pending_user_id: ' . $pending_user_id);
                wp_send_json_error(array('message' => 'Verification code has expired. Please register again.'));
                return;
            }
            
            // Check if email already exists in WordPress users (in case of race condition)
            if (email_exists($pending->email)) {
                // Email already exists - clean up pending record
                $wpdb->delete($table_name, array('id' => $pending_user_id), array('%d'));
                error_log('ZonaTech: Verification - email already exists in users table: ' . $pending->email);
                wp_send_json_error(array('message' => 'This email is already registered. Please try logging in.'));
                return;
            }
            
            // Generate unique username
            $base_username = sanitize_user(strtolower($pending->first_name . $pending->last_name));
            if (empty($base_username)) {
                $base_username = 'user';
            }
            $username = $base_username . wp_rand(100, 999);
            
            // Ensure username is unique
            $counter = 1;
            while (username_exists($username)) {
                $username = $base_username . wp_rand(100, 999);
                $counter++;
                if ($counter > 10) {
                    $username = $base_username . '_' . time();
                    break;
                }
            }
            
            error_log('ZonaTech: Creating user with username: ' . $username . ', email: ' . $pending->email);
            
            // Create user with a temporary password
            $temp_password = wp_generate_password(24, true, true);
            
            $user_id = wp_insert_user(array(
                'user_login' => $username,
                'user_email' => $pending->email,
                'user_pass' => $temp_password,
                'first_name' => $pending->first_name,
                'last_name' => $pending->last_name,
                'display_name' => $pending->first_name . ' ' . $pending->last_name,
                'role' => 'subscriber'
            ));
            
            if (is_wp_error($user_id)) {
                $error_msg = $user_id->get_error_message();
                error_log('ZonaTech: User creation failed - ' . $error_msg);
                
                // Check if it's a duplicate email error
                if (strpos(strtolower($error_msg), 'email') !== false) {
                    // Clean up the pending record since email is taken
                    $wpdb->delete($table_name, array('id' => $pending_user_id), array('%d'));
                    wp_send_json_error(array('message' => 'This email is already registered. Please try logging in.'));
                } else {
                    wp_send_json_error(array('message' => 'Failed to create account: ' . $error_msg));
                }
                return;
            }
            
            // Set the password directly from the stored hash
            $password_updated = $wpdb->update(
                $wpdb->users,
                array('user_pass' => $pending->password),
                array('ID' => $user_id),
                array('%s'),
                array('%d')
            );
            
            if ($password_updated === false) {
                error_log('ZonaTech: Failed to update password hash for user_id: ' . $user_id);
            }
            
            // Clear all user caches to ensure the new password is recognized
            clean_user_cache($user_id);
            wp_cache_delete($user_id, 'users');
            wp_cache_delete($pending->email, 'useremail');
            wp_cache_delete($username, 'userlogins');
            
            // Update user meta
            update_user_meta($user_id, 'phone', $pending->phone);
            update_user_meta($user_id, 'zonatech_registered', current_time('mysql'));
            update_user_meta($user_id, 'zonatech_email_verified', true);
            
            // Delete pending registration - THIS IS CRITICAL
            $delete_result = $wpdb->delete($table_name, array('id' => $pending_user_id), array('%d'));
            if ($delete_result === false) {
                error_log('ZonaTech: Warning - failed to delete pending record id: ' . $pending_user_id);
            }
            
            // Log activity (non-blocking)
            if (class_exists('ZonaTech_Activity_Log')) {
                try {
                    ZonaTech_Activity_Log::log($user_id, 'registration', 'User registered and verified email');
                } catch (Exception $e) {
                    error_log('ZonaTech: Activity log failed - ' . $e->getMessage());
                }
            }
            
            // Send approval email (non-blocking)
            try {
                $this->send_approval_email($pending->email, $pending->first_name);
            } catch (Exception $e) {
                error_log('ZonaTech: Approval email failed - ' . $e->getMessage());
            }
            
            error_log('ZonaTech: User created successfully - user_id: ' . $user_id);
            
            wp_send_json_success(array(
                'message' => 'Email verified successfully! You can now log in.',
                'redirect' => home_url('/zonatech-login/')
            ));
            
        } catch (Exception $e) {
            error_log('ZonaTech: Verification exception - ' . $e->getMessage());
            
            // If user was partially created, try to clean up
            if ($user_id && !is_wp_error($user_id)) {
                // User was created but something else failed - don't delete, just log
                error_log('ZonaTech: User ' . $user_id . ' was created but verification had an error');
                // Clean up pending record since user exists
                if ($pending_user_id > 0) {
                    $wpdb->delete($table_name, array('id' => $pending_user_id), array('%d'));
                }
                // Still return success since user was created
                wp_send_json_success(array(
                    'message' => 'Account created! You can now log in.',
                    'redirect' => home_url('/zonatech-login/')
                ));
            } else {
                wp_send_json_error(array('message' => 'An error occurred during verification. Please try again.'));
            }
        }
    }
    
    public function handle_resend_verification() {
        $nonce_value = isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '';
        $nonce_valid = wp_verify_nonce($nonce_value, 'zonatech_nonce');
        if (!$nonce_valid) {
            wp_send_json_error(array(
                'message' => 'Security check failed. Please refresh the page and try again.',
                'code' => 'nonce_invalid'
            ));
            return;
        }
        
        $pending_user_id = intval($_POST['pending_user_id'] ?? 0);
        
        if (empty($pending_user_id)) {
            wp_send_json_error(array('message' => 'Invalid request.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'zonatech_pending_users';
        
        // Get pending registration
        $pending = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $pending_user_id
        ));
        
        if (!$pending) {
            wp_send_json_error(array('message' => 'Registration not found. Please register again.'));
            return;
        }
        
        // Generate new verification code
        $new_code = $this->generate_verification_code();
        
        // Update pending registration
        $wpdb->update($table_name, array(
            'verification_code' => $new_code,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ), array('id' => $pending_user_id));
        
        // Send new verification email
        $email_sent = $this->send_verification_email($pending->email, $pending->first_name, $new_code);
        
        if (!$email_sent) {
            wp_send_json_error(array('message' => 'Failed to send verification email. Please try again.'));
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'New verification code sent to your email!'
        ));
    }
    
    /**
     * Create pending users table
     */
    private function create_pending_users_table() {
        global $wpdb;
        
        // Build table name safely - $wpdb->prefix is already sanitized by WordPress
        $table_name = $wpdb->prefix . 'zonatech_pending_users';
        
        // Check if table already exists using WordPress's method
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $tables = $wpdb->get_col("SHOW TABLES");
        $table_exists = in_array($table_name, $tables, true);
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            // Use direct SQL for table creation - more reliable than dbDelta
            // Table name is constructed from $wpdb->prefix which is controlled by WordPress
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
            $sql = "CREATE TABLE IF NOT EXISTS `" . esc_sql($table_name) . "` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `first_name` varchar(100) NOT NULL,
                `last_name` varchar(100) NOT NULL,
                `email` varchar(100) NOT NULL,
                `phone` varchar(20) DEFAULT '',
                `password` varchar(255) NOT NULL,
                `verification_code` varchar(6) NOT NULL,
                `expires_at` datetime NOT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`)
            ) $charset_collate;";
            
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                error_log('ZonaTech: Failed to create pending_users table - ' . $wpdb->last_error);
                return false;
            } else {
                error_log('ZonaTech: Successfully created pending_users table');
                return true;
            }
        }
        
        return $table_exists;
    }
    
    public function handle_login() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
                header('Cache-Control: no-cache, no-store, must-revalidate');
            }
        }
        
        // Nonce verification with graceful handling - don't block login entirely
        $nonce_value = isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '';
        $nonce_valid = !empty($nonce_value) && wp_verify_nonce($nonce_value, 'zonatech_nonce');
        if (!$nonce_valid) {
            // Try to verify with a fresh check - nonces can expire on cached pages
            $nonce_valid = !empty($nonce_value) && wp_verify_nonce($nonce_value, 'zonatech_nonce');
        }
        if (!$nonce_valid) {
            wp_send_json_error(array(
                'message' => 'Security check failed. Please refresh the page and try again.',
                'code' => 'nonce_invalid'
            ));
            return;
        }
        
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
        
        if (empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Email and password are required.'));
            return;
        }
        
        // Rate limiting: prevent brute force attacks (max 5 attempts per 15 minutes per IP)
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $rate_limit_key = 'zonatech_login_attempts_' . md5($ip_address);
        $login_attempts = (int) get_transient($rate_limit_key);
        if ($login_attempts >= 5) {
            wp_send_json_error(array('message' => 'Too many login attempts. Please try again in 15 minutes.'));
            return;
        }
        
        // Look up the user by email
        $user = get_user_by('email', $email);
        
        if (!$user) {
            // Increment failed attempts counter
            set_transient($rate_limit_key, $login_attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error(array('message' => 'Invalid email or password.'));
            return;
        }
        
        // Verify password directly - bypasses WordPress authentication filters
        // that may add extra rules (password complexity plugins, login limiters, etc.)
        $password_valid = wp_check_password($password, $user->user_pass, $user->ID);
        
        if (!$password_valid) {
            // Increment failed attempts counter
            set_transient($rate_limit_key, $login_attempts + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error(array('message' => 'Invalid email or password.'));
            return;
        }
        
        // Clear rate limit on successful login
        delete_transient($rate_limit_key);
        
        // Set user session directly - bypasses wp_signon() which can fail due to
        // third-party plugin hooks, password policy plugins, or authentication filters
        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, $remember, is_ssl());
        
        // Fire standard WordPress login action for compatibility (e.g., last login tracking)
        do_action('wp_login', $user->user_login, $user);
        
        // Log activity (wrapped in try-catch to prevent login failure if activity log has issues)
        try {
            if (class_exists('ZonaTech_Activity_Log') && method_exists('ZonaTech_Activity_Log', 'log')) {
                ZonaTech_Activity_Log::log($user->ID, 'login', 'User logged in');
            }
        } catch (Exception $e) {
            // Silently fail - don't block login due to activity logging issues
            error_log('ZonaTech: Activity log error during login - ' . $e->getMessage());
        }
        
        wp_send_json_success(array(
            'message' => 'Login successful!',
            'redirect' => home_url('/zonatech-dashboard/')
        ));
    }
    
    public function handle_logout() {
        $user_id = get_current_user_id();
        
        if ($user_id) {
            try {
                if (class_exists('ZonaTech_Activity_Log') && method_exists('ZonaTech_Activity_Log', 'log')) {
                    ZonaTech_Activity_Log::log($user_id, 'logout', 'User logged out');
                }
            } catch (Exception $e) {
                error_log('ZonaTech: Activity log error during logout - ' . $e->getMessage());
            }
        }
        
        wp_logout();
        
        wp_send_json_success(array(
            'message' => 'Logged out successfully.',
            'redirect' => home_url('/zonatech-login/')
        ));
    }

    public function handle_refresh_nonce() {
        $current_nonce = isset($_POST['current_nonce']) ? sanitize_key(wp_unslash($_POST['current_nonce'])) : '';
        $current_nonce_valid = !empty($current_nonce) && wp_verify_nonce($current_nonce, 'zonatech_nonce');
        if (empty($current_nonce)) {
            wp_send_json_error(array(
                'message' => 'Security check failed. Please refresh the page and try again.',
                'code' => 'nonce_invalid'
            ));
            return;
        }
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $rate_limit_key = 'zonatech_nonce_refresh_' . md5($ip_address);
        if (get_transient($rate_limit_key)) {
            wp_send_json_error(array('message' => 'Please wait a moment before trying again.'));
            return;
        }
        set_transient($rate_limit_key, 1, self::NONCE_REFRESH_RATE_LIMIT);

        wp_send_json_success(array(
            'nonce' => wp_create_nonce('zonatech_nonce'),
            'nonce_valid' => $current_nonce_valid
        ));
    }
    
    public function handle_reset_password() {
        $nonce_value = isset($_POST['nonce']) ? sanitize_key(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce_value, 'zonatech_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
            return;
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email)) {
            wp_send_json_error(array('message' => 'Email address is required.'));
            return;
        }
        
        $user = get_user_by('email', $email);
        
        if (!$user) {
            // Don't reveal if email exists or not for security
            wp_send_json_success(array(
                'message' => 'If this email exists, a password reset link will be sent.'
            ));
            return;
        }
        
        // Generate reset key
        $reset_key = get_password_reset_key($user);
        
        if (is_wp_error($reset_key)) {
            wp_send_json_error(array('message' => 'Error generating reset link. Please try again.'));
            return;
        }
        
        // Send reset email
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
        
        $message = "Hi " . $user->display_name . ",\n\n";
        $message .= "You requested a password reset for your ZonaTech NG account.\n\n";
        $message .= "Click the link below to reset your password:\n";
        $message .= $reset_url . "\n\n";
        $message .= "If you didn't request this, please ignore this email.\n\n";
        $message .= "Thanks,\nZonaTech NG Team";
        
        $sent = wp_mail($email, 'Password Reset - ZonaTech NG', $message);
        
        try {
            if (class_exists('ZonaTech_Activity_Log') && method_exists('ZonaTech_Activity_Log', 'log')) {
                ZonaTech_Activity_Log::log($user->ID, 'password_reset_request', 'Password reset requested');
            }
        } catch (Exception $e) {
            error_log('ZonaTech: Activity log error during password reset - ' . $e->getMessage());
        }
        
        wp_send_json_success(array(
            'message' => 'If this email exists, a password reset link will be sent.'
        ));
    }
    
    public function handle_update_profile() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to update your profile.'));
            return;
        }
        
        $user_id = get_current_user_id();
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        if (empty($first_name) || empty($last_name)) {
            wp_send_json_error(array('message' => 'First name and last name are required.'));
            return;
        }
        
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        update_user_meta($user_id, 'phone', $phone);
        
        try {
            if (class_exists('ZonaTech_Activity_Log') && method_exists('ZonaTech_Activity_Log', 'log')) {
                ZonaTech_Activity_Log::log($user_id, 'profile_update', 'Profile updated');
            }
        } catch (Exception $e) {
            error_log('ZonaTech: Activity log error during profile update - ' . $e->getMessage());
        }
        
        wp_send_json_success(array('message' => 'Profile updated successfully!'));
    }
    
    public function handle_change_password() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to change your password.'));
            return;
        }
        
        $user_id = get_current_user_id();
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            wp_send_json_error(array('message' => 'All fields are required.'));
            return;
        }
        
        $user = get_user_by('id', $user_id);
        
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            wp_send_json_error(array('message' => 'Current password is incorrect.'));
            return;
        }
        
        if ($new_password !== $confirm_password) {
            wp_send_json_error(array('message' => 'New passwords do not match.'));
            return;
        }
        
        wp_set_password($new_password, $user_id);
        
        // Re-login user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        try {
            if (class_exists('ZonaTech_Activity_Log') && method_exists('ZonaTech_Activity_Log', 'log')) {
                ZonaTech_Activity_Log::log($user_id, 'password_change', 'Password changed');
            }
        } catch (Exception $e) {
            error_log('ZonaTech: Activity log error during password change - ' . $e->getMessage());
        }
        
        wp_send_json_success(array('message' => 'Password changed successfully!'));
    }
    
    public static function get_user_dashboard_data() {
        if (!is_user_logged_in()) {
            return null;
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        // Get purchase count
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        $purchase_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_purchases WHERE user_id = %d AND status = 'completed'",
            $user_id
        ));
        
        // Get quiz count - ensure table exists and handle null
        $table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
        $quiz_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_quiz WHERE user_id = %d",
            $user_id
        ));
        // Handle case where table doesn't exist (returns null)
        if ($quiz_count === null) {
            $quiz_count = 0;
        }
        
        // Get total spent
        $total_spent = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table_purchases WHERE user_id = %d AND status = 'completed'",
            $user_id
        )) ?? 0;
        
        // Get accessible subjects count
        $table_access = $wpdb->prefix . 'zonatech_user_access';
        $subjects_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_access WHERE user_id = %d",
            $user_id
        ));
        
        // Get custom avatar or default
        $custom_avatar_id = get_user_meta($user_id, 'zonatech_avatar_id', true);
        if ($custom_avatar_id) {
            $avatar_url = wp_get_attachment_url($custom_avatar_id);
        } else {
            $avatar_url = get_avatar_url($user_id, array('size' => 150));
        }
        
        return array(
            'user' => array(
                'id' => $user_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'phone' => get_user_meta($user_id, 'phone', true),
                'avatar' => $avatar_url,
                'registered' => get_user_meta($user_id, 'zonatech_registered', true)
            ),
            'stats' => array(
                'purchases' => (int) $purchase_count,
                'quizzes' => (int) $quiz_count,
                'total_spent' => (float) $total_spent,
                'subjects' => (int) $subjects_count
            )
        );
    }
}