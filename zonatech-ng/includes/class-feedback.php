<?php
/**
 * Feedback Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Feedback {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_zonatech_submit_feedback', array($this, 'submit_feedback'));
        add_action('wp_ajax_nopriv_zonatech_submit_feedback', array($this, 'submit_feedback'));
    }
    
    public function submit_feedback() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? 'General Feedback');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        
        // Validation
        if (empty($name)) {
            wp_send_json_error(array('message' => 'Please enter your name.'));
            return;
        }
        
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
            return;
        }
        
        if (empty($message)) {
            wp_send_json_error(array('message' => 'Please enter your feedback message.'));
            return;
        }
        
        // Store feedback in database
        global $wpdb;
        $table_feedback = $wpdb->prefix . 'zonatech_feedback';
        
        // Check if table exists, create if not
        $this->maybe_create_table();
        
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        
        $inserted = $wpdb->insert($table_feedback, array(
            'user_id' => $user_id,
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'rating' => $rating,
            'status' => 'unread',
            'created_at' => current_time('mysql')
        ));
        
        if (!$inserted) {
            wp_send_json_error(array('message' => 'Failed to save feedback. Please try again.'));
            return;
        }
        
        // Send email notification to support
        $this->send_feedback_email($name, $email, $subject, $message, $rating);
        
        // Log activity if user is logged in
        if ($user_id > 0) {
            ZonaTech_Activity_Log::log($user_id, 'feedback_submitted', 'Submitted feedback: ' . $subject);
        }
        
        wp_send_json_success(array(
            'message' => 'Thank you for your feedback! We appreciate your input and will review it shortly.'
        ));
    }
    
    private function maybe_create_table() {
        global $wpdb;
        $table_feedback = $wpdb->prefix . 'zonatech_feedback';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_feedback'") !== $table_feedback) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_feedback (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) DEFAULT 0,
                name varchar(100) NOT NULL,
                email varchar(100) NOT NULL,
                subject varchar(255) DEFAULT '',
                message text NOT NULL,
                rating int(1) DEFAULT 0,
                status varchar(20) DEFAULT 'unread',
                admin_response text DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY status (status)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    private function send_feedback_email($name, $email, $subject, $message, $rating) {
        $to = ZONATECH_SUPPORT_EMAIL;
        $email_subject = "New Feedback from ZonaTech NG: " . $subject;
        
        $stars = $rating > 0 ? str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) : 'Not rated';
        
        $html_message = $this->get_feedback_email_template($name, $email, $subject, $message, $stars);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <' . ZONATECH_SUPPORT_EMAIL . '>',
            'Reply-To: ' . $name . ' <' . $email . '>'
        );
        
        wp_mail($to, $email_subject, $html_message, $headers);
    }
    
    private function get_feedback_email_template($name, $email, $subject, $message, $stars) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh;">
    <div style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">
        <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(99, 102, 241, 0.1)); border-radius: 20px; padding: 40px; border: 1px solid rgba(139, 92, 246, 0.3); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #8b5cf6; margin: 0; font-size: 28px;">📬 New Feedback Received</h1>
                <p style="color: #a78bfa; margin-top: 10px;">ZonaTech NG</p>
            </div>
            
            <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 25px; margin-bottom: 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px 0; color: #a78bfa; font-weight: bold;">From:</td>
                        <td style="padding: 10px 0; color: #ffffff;">' . esc_html($name) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: #a78bfa; font-weight: bold;">Email:</td>
                        <td style="padding: 10px 0; color: #ffffff;"><a href="mailto:' . esc_attr($email) . '" style="color: #8b5cf6;">' . esc_html($email) . '</a></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: #a78bfa; font-weight: bold;">Subject:</td>
                        <td style="padding: 10px 0; color: #ffffff;">' . esc_html($subject) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: #a78bfa; font-weight: bold;">Rating:</td>
                        <td style="padding: 10px 0; color: #fbbf24; font-size: 18px;">' . $stars . '</td>
                    </tr>
                </table>
            </div>
            
            <div style="background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 25px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <h3 style="color: #a78bfa; margin-top: 0;">Message:</h3>
                <p style="color: #ffffff; line-height: 1.6; white-space: pre-wrap;">' . esc_html($message) . '</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="mailto:' . esc_attr($email) . '" style="display: inline-block; background: linear-gradient(135deg, #8b5cf6, #6366f1); color: white; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold;">Reply to User</a>
            </div>
            
            <p style="color: #6b7280; font-size: 12px; text-align: center; margin-top: 30px;">
                This feedback was submitted via ZonaTech NG platform.
            </p>
        </div>
    </div>
</body>
</html>';
    }
    
    public static function get_all_feedback($limit = 50, $status = '') {
        global $wpdb;
        $table_feedback = $wpdb->prefix . 'zonatech_feedback';
        
        $where = '';
        if (!empty($status)) {
            $where = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        return $wpdb->get_results(
            "SELECT * FROM $table_feedback $where ORDER BY created_at DESC LIMIT $limit"
        );
    }
    
    public static function mark_as_read($feedback_id) {
        global $wpdb;
        $table_feedback = $wpdb->prefix . 'zonatech_feedback';
        
        return $wpdb->update(
            $table_feedback,
            array('status' => 'read'),
            array('id' => $feedback_id)
        );
    }
    
    public static function get_unread_count() {
        global $wpdb;
        $table_feedback = $wpdb->prefix . 'zonatech_feedback';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_feedback WHERE status = 'unread'");
    }
}