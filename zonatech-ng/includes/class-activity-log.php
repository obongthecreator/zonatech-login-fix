<?php
/**
 * Activity Log Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Activity_Log {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_zonatech_get_activity_log', array($this, 'get_user_activity'));
        add_action('wp_ajax_zonatech_get_all_activity', array($this, 'get_all_activity'));
    }
    
    public static function log($user_id, $activity_type, $description, $meta_data = array()) {
        global $wpdb;
        $table_activity = $wpdb->prefix . 'zonatech_activity_log';
        
        // Check if table exists before trying to insert
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_activity'") === $table_activity;
        if (!$table_exists) {
            // Table doesn't exist, silently skip logging
            return;
        }
        
        $ip_address = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        $wpdb->insert($table_activity, array(
            'user_id' => $user_id,
            'activity_type' => $activity_type,
            'description' => $description,
            'meta_data' => !empty($meta_data) ? wp_json_encode($meta_data) : null,
            'ip_address' => $ip_address
        ));
    }
    
    public function get_user_activity() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_id = get_current_user_id();
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        global $wpdb;
        $table_activity = $wpdb->prefix . 'zonatech_activity_log';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_activity WHERE user_id = %d",
            $user_id
        ));
        
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT activity_type, description, created_at 
             FROM $table_activity 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id,
            $per_page,
            $offset
        ));
        
        foreach ($activities as &$activity) {
            $activity->icon = $this->get_activity_icon($activity->activity_type);
            $activity->time_ago = human_time_diff(strtotime($activity->created_at), current_time('timestamp')) . ' ago';
        }
        
        wp_send_json_success(array(
            'activities' => $activities,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    public function get_all_activity() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        // Only for admin users
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = 50;
        $offset = ($page - 1) * $per_page;
        $activity_type = sanitize_text_field($_POST['activity_type'] ?? '');
        
        global $wpdb;
        $table_activity = $wpdb->prefix . 'zonatech_activity_log';
        
        $where = '1=1';
        $params = array();
        
        if (!empty($activity_type)) {
            $where .= ' AND activity_type = %s';
            $params[] = $activity_type;
        }
        
        if (empty($params)) {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_activity WHERE $where");
        } else {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_activity WHERE $where",
                $params
            ));
        }
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as user_name 
             FROM $table_activity a 
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
             WHERE $where 
             ORDER BY a.created_at DESC 
             LIMIT %d OFFSET %d",
            $params
        ));
        
        foreach ($activities as &$activity) {
            $activity->icon = $this->get_activity_icon($activity->activity_type);
            $activity->time_ago = human_time_diff(strtotime($activity->created_at), current_time('timestamp')) . ' ago';
        }
        
        wp_send_json_success(array(
            'activities' => $activities,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    private function get_activity_icon($type) {
        $icons = array(
            'registration' => 'fas fa-user-plus',
            'login' => 'fas fa-sign-in-alt',
            'logout' => 'fas fa-sign-out-alt',
            'password_reset_request' => 'fas fa-key',
            'password_change' => 'fas fa-lock',
            'profile_update' => 'fas fa-user-edit',
            'payment_completed' => 'fas fa-credit-card',
            'quiz_start' => 'fas fa-play-circle',
            'quiz_complete' => 'fas fa-check-circle',
            'view_corrections' => 'fas fa-eye',
            'view_questions' => 'fas fa-book-reader',
            'nin_verification' => 'fas fa-id-card',
            'nin_slip_request' => 'fas fa-file-download',
            'scratch_card_request' => 'fas fa-ticket-alt',
            'scratch_card_purchase' => 'fas fa-shopping-cart'
        );
        
        return $icons[$type] ?? 'fas fa-circle';
    }
    
    public static function get_recent_activities($limit = 10) {
        global $wpdb;
        $table_activity = $wpdb->prefix . 'zonatech_activity_log';
        
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as user_name 
             FROM $table_activity a 
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
             ORDER BY a.created_at DESC 
             LIMIT %d",
            $limit
        ));
        
        foreach ($activities as &$activity) {
            $activity->time_ago = human_time_diff(strtotime($activity->created_at), current_time('timestamp')) . ' ago';
        }
        
        return $activities;
    }
    
    public static function format_activity_message($activity) {
        $user_name = $activity->user_name ?? 'A user';
        $time = $activity->time_ago ?? '';
        
        $messages = array(
            'payment_completed' => "{$user_name} made a purchase {$time}",
            'quiz_complete' => "{$user_name} completed a quiz {$time}",
            'scratch_card_purchase' => "{$user_name} bought a scratch card {$time}",
            'registration' => "{$user_name} joined ZonaTech NG {$time}",
            'nin_slip_request' => "{$user_name} requested NIN slip {$time}"
        );
        
        return $messages[$activity->activity_type] ?? "{$user_name}: {$activity->description} {$time}";
    }
}