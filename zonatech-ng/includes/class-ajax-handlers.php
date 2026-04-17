<?php
/**
 * AJAX Handlers Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Ajax_Handlers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Dashboard data handlers
        add_action('wp_ajax_zonatech_get_dashboard_data', array($this, 'get_dashboard_data'));
        add_action('wp_ajax_zonatech_get_payment_history', array($this, 'get_payment_history'));
        add_action('wp_ajax_zonatech_get_downloaded_documents', array($this, 'get_downloaded_documents'));
        
        // UltraMsg WhatsApp API handlers
        add_action('wp_ajax_zonatech_save_ultramsg_settings', array($this, 'save_ultramsg_settings'));
        add_action('wp_ajax_zonatech_test_ultramsg', array($this, 'test_ultramsg'));
        
        // Admin question management handlers
        add_action('wp_ajax_zonatech_delete_all_questions', array($this, 'delete_all_questions'));
    }
    
    public function get_dashboard_data() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_data = ZonaTech_User_Auth::get_user_dashboard_data();
        $quiz_stats = ZonaTech_Quiz_System::get_user_quiz_stats(get_current_user_id());
        $accessible_subjects = ZonaTech_Past_Questions::get_user_accessible_subjects(get_current_user_id());
        
        wp_send_json_success(array(
            'user' => $user_data['user'],
            'stats' => $user_data['stats'],
            'quiz_stats' => $quiz_stats,
            'accessible_subjects' => $accessible_subjects
        ));
    }
    
    public function get_payment_history() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_id = get_current_user_id();
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = 10;
        $offset = ($page - 1) * $per_page;
        
        global $wpdb;
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_purchases WHERE user_id = %d",
            $user_id
        ));
        
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_purchases 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id,
            $per_page,
            $offset
        ));
        
        foreach ($payments as &$payment) {
            $payment->formatted_amount = '₦' . number_format($payment->amount);
            $payment->formatted_date = date('M j, Y g:i A', strtotime($payment->created_at));
            $payment->status_class = $payment->status === 'completed' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'error');
        }
        
        wp_send_json_success(array(
            'payments' => $payments,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    public function get_downloaded_documents() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_downloads = $wpdb->prefix . 'zonatech_downloads';
        
        $documents = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_downloads WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        foreach ($documents as &$doc) {
            $doc->formatted_date = date('M j, Y', strtotime($doc->created_at));
            $doc->icon = $this->get_document_icon($doc->document_type);
        }
        
        wp_send_json_success(array('documents' => $documents));
    }
    
    private function get_document_icon($type) {
        $icons = array(
            'nin_slip' => 'fas fa-id-card',
            'scratch_card' => 'fas fa-credit-card',
            'receipt' => 'fas fa-receipt',
            'certificate' => 'fas fa-certificate'
        );
        
        return $icons[$type] ?? 'fas fa-file';
    }
    
    /**
     * Save UltraMsg WhatsApp API settings
     */
    public function save_ultramsg_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'zonatech_ultramsg_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Check if admin
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $api_url = sanitize_text_field($_POST['api_url'] ?? '');
        $instance_id = sanitize_text_field($_POST['instance_id'] ?? '');
        $token = sanitize_text_field($_POST['token'] ?? '');
        $admin_whatsapp = sanitize_text_field($_POST['admin_whatsapp'] ?? '');
        
        // If API URL is provided, extract instance ID from it
        if (!empty($api_url) && empty($instance_id)) {
            if (preg_match('/ultramsg\.com\/([^\/]+)/', $api_url, $matches)) {
                $instance_id = $matches[1];
            }
        }
        
        if (empty($instance_id) && empty($api_url)) {
            wp_send_json_error(array('message' => 'API URL or Instance ID is required.'));
        }
        
        if (empty($token) || $token === '••••••••••••••••') {
            // If token is masked, don't update it (keep existing)
            if ($token !== '••••••••••••••••') {
                wp_send_json_error(array('message' => 'Token is required.'));
            }
        } else {
            // Save the token
            update_option('zonatech_ultramsg_token', $token);
        }
        
        // Save API URL, instance ID and admin WhatsApp
        update_option('zonatech_ultramsg_api_url', $api_url);
        update_option('zonatech_ultramsg_instance_id', $instance_id);
        update_option('zonatech_admin_whatsapp', $admin_whatsapp);
        
        wp_send_json_success(array('message' => 'UltraMsg settings saved successfully!'));
    }
    
    /**
     * Test UltraMsg WhatsApp API connection
     */
    public function test_ultramsg() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'zonatech_ultramsg_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Check if admin
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        // Check if UltraMsg class exists
        if (!class_exists('ZonaTech_UltraMsg')) {
            wp_send_json_error(array('message' => 'UltraMsg class not found. Please ensure the plugin is properly installed.'));
        }
        
        $ultramsg = ZonaTech_UltraMsg::get_instance();
        
        if (!$ultramsg->is_configured()) {
            wp_send_json_error(array('message' => 'UltraMsg is not configured. Please save your Instance ID and Token first.'));
        }
        
        // Test the connection
        $test_result = $ultramsg->test_connection();
        
        if ($test_result['success']) {
            // Send a test message to admin
            $admin_phone = get_option('zonatech_admin_whatsapp', '');
            if (!empty($admin_phone)) {
                $test_message = "🔔 *ZonaTech NG Test Message*\n\n✅ Your UltraMsg WhatsApp integration is working correctly!\n\n📅 " . date('M j, Y g:i A');
                $send_result = $ultramsg->send_message($admin_phone, $test_message);
                
                if ($send_result['success']) {
                    wp_send_json_success(array('message' => 'Connection successful! Test message sent to your WhatsApp.'));
                } else {
                    wp_send_json_error(array('message' => 'Connected but failed to send test message: ' . $send_result['message']));
                }
            } else {
                wp_send_json_success(array('message' => 'Connection successful! Phone: ' . ($test_result['phone'] ?? 'Unknown') . '. Add your Admin WhatsApp number to receive notifications.'));
            }
        } else {
            wp_send_json_error(array('message' => $test_result['message']));
        }
    }
    
    /**
     * Delete all questions from the database
     */
    public function delete_all_questions() {
        // Verify nonce
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        // Check if admin
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access. Admin privileges required.'));
        }
        
        global $wpdb;
        $table_questions = $wpdb->prefix . 'zonatech_questions';
        
        // Get count before deletion
        $count_before = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_questions");
        
        if ($count_before === 0) {
            wp_send_json_success(array(
                'message' => 'No questions to delete. The database is already empty.',
                'deleted_count' => 0
            ));
            return;
        }
        
        // Delete all questions
        $result = $wpdb->query("TRUNCATE TABLE $table_questions");
        
        if ($result === false) {
            // If TRUNCATE fails, try DELETE
            $result = $wpdb->query("DELETE FROM $table_questions");
        }
        
        // Verify deletion
        $count_after = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_questions");
        
        if ($count_after === 0) {
            wp_send_json_success(array(
                'message' => "Successfully deleted all $count_before questions from the database.",
                'deleted_count' => $count_before
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Failed to delete all questions. Some questions may remain.',
                'remaining_count' => $count_after
            ));
        }
    }
}