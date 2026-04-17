<?php
/**
 * NIN Service Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_NIN_Service {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_zonatech_verify_nin', array($this, 'verify_nin'));
        add_action('wp_ajax_zonatech_request_nin_slip', array($this, 'request_nin_slip'));
        add_action('wp_ajax_zonatech_get_nin_requests', array($this, 'get_user_nin_requests'));
        add_action('wp_ajax_zonatech_fulfill_nin_request', array($this, 'fulfill_nin_request'));
    }
    
    public function verify_nin() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to verify NIN.'));
        }
        
        $nin = sanitize_text_field($_POST['nin'] ?? '');
        
        if (empty($nin)) {
            wp_send_json_error(array('message' => 'NIN number is required.'));
        }
        
        // Validate NIN format (11 digits)
        if (!preg_match('/^\d{11}$/', $nin)) {
            wp_send_json_error(array('message' => 'Invalid NIN format. NIN must be 11 digits.'));
        }
        
        // In production, this would connect to NIMC API or a verification service
        // For demo purposes, we'll simulate verification
        $verified = $this->simulate_nin_verification($nin);
        
        if (!$verified['status']) {
            wp_send_json_error(array('message' => $verified['message']));
        }
        
        $user_id = get_current_user_id();
        ZonaTech_Activity_Log::log($user_id, 'nin_verification', 'NIN verification attempted');
        
        wp_send_json_success(array(
            'message' => 'NIN verified successfully!',
            'data' => $verified['data']
        ));
    }
    
    public function request_nin_slip() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to request NIN slip.'));
        }
        
        $nin = sanitize_text_field($_POST['nin'] ?? '');
        $slip_type = sanitize_text_field($_POST['slip_type'] ?? 'standard');
        
        if (empty($nin)) {
            wp_send_json_error(array('message' => 'NIN number is required.'));
        }
        
        if (!preg_match('/^\d{11}$/', $nin)) {
            wp_send_json_error(array('message' => 'Invalid NIN format.'));
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
        
        // Check for existing pending request
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_nin WHERE user_id = %d AND nin_number = %s AND status IN ('pending', 'paid')",
            $user_id,
            $nin
        ));
        
        if ($existing) {
            if ($existing->status === 'paid') {
                wp_send_json_success(array(
                    'message' => 'NIN slip is being processed.',
                    'status' => 'processing'
                ));
            }
            wp_send_json_error(array('message' => 'You already have a pending request for this NIN.'));
        }
        
        // Determine payment type and amount based on slip type
        $payment_type = ($slip_type === 'premium') ? 'nin_slip' : 'nin_standard_slip';
        $amount = ($slip_type === 'premium') ? ZONATECH_NIN_SLIP_PRICE : ZONATECH_NIN_STANDARD_SLIP_PRICE;
        
        // Create new request
        $wpdb->insert($table_nin, array(
            'user_id' => $user_id,
            'nin_number' => $nin,
            'status' => 'pending'
        ));
        
        $request_id = $wpdb->insert_id;
        
        ZonaTech_Activity_Log::log($user_id, 'nin_slip_request', ucfirst($slip_type) . ' NIN slip requested');
        
        wp_send_json_success(array(
            'message' => ucfirst($slip_type) . ' NIN slip request created. Please complete payment.',
            'request_id' => $request_id,
            'require_payment' => true,
            'payment_type' => $payment_type,
            'amount' => $amount,
            'meta_data' => array(
                'nin_number' => $nin,
                'request_id' => $request_id,
                'slip_type' => $slip_type
            )
        ));
    }
    
    public function get_user_nin_requests() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login.'));
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
        
        $requests = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_nin WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        wp_send_json_success(array('requests' => $requests));
    }
    
    private function simulate_nin_verification($nin) {
        /**
         * IMPORTANT: This is a SIMULATION for demonstration purposes only.
         * 
         * For PRODUCTION use, integrate with one of these official services:
         * - NIMC Official API (nimc.gov.ng)
         * - Dojah (dojah.io)
         * - Prembly/Identitypass (prembly.com)
         * - Youverify (youverify.co)
         * 
         * The simulation below should NOT be used in production as it does not
         * perform actual NIN verification and could result in invalid data.
         */
        
        // Basic format validation
        if (strlen($nin) !== 11) {
            return array(
                'status' => false,
                'message' => 'Invalid NIN format. NIN must be 11 digits.'
            );
        }
        
        // Validate all characters are digits
        if (!ctype_digit($nin)) {
            return array(
                'status' => false,
                'message' => 'Invalid NIN. Only digits are allowed.'
            );
        }
        
        // Additional basic validation - NIN shouldn't start with 0
        if ($nin[0] === '0') {
            return array(
                'status' => false,
                'message' => 'Invalid NIN format.'
            );
        }
        
        // Simulate verification response (DEMO ONLY)
        // In production, this would call the actual verification API
        return array(
            'status' => true,
            'message' => 'NIN format validated. (Demo Mode - Production requires API integration)',
            'data' => array(
                'nin' => $nin,
                'verified' => true,
                'name' => '[DEMO] Verification pending API integration',
                'note' => 'Pay to download premium NIN slip. Full verification requires production API setup.',
                'demo_mode' => true
            )
        );
    }
    
    public static function get_user_nin_history($user_id) {
        global $wpdb;
        $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_nin WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
    }
    
    public function fulfill_nin_request() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['fulfill_nonce'] ?? '', 'zonatech_fulfill_nin')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Check if admin
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $request_id = intval($_POST['request_id'] ?? 0);
        $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
        $send_email = isset($_POST['send_email']);
        
        if (!$request_id) {
            wp_send_json_error(array('message' => 'Invalid request ID.'));
        }
        
        global $wpdb;
        $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
        
        // Get the request
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT n.*, u.display_name, u.user_email 
             FROM $table_nin n 
             LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID 
             WHERE n.id = %d",
            $request_id
        ));
        
        if (!$request) {
            wp_send_json_error(array('message' => 'Request not found.'));
        }
        
        // Handle file upload
        $file_url = '';
        if (!empty($_FILES['nin_document']['tmp_name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $upload = wp_handle_upload($_FILES['nin_document'], array('test_form' => false));
            
            if (!isset($upload['error'])) {
                $file_url = $upload['url'];
            }
        }
        
        // Update request status
        $wpdb->update(
            $table_nin,
            array(
                'status' => 'fulfilled',
                'file_url' => $file_url,
                'admin_notes' => $admin_notes,
                'fulfilled_at' => current_time('mysql')
            ),
            array('id' => $request_id)
        );
        
        // Send email to user
        if ($send_email && $request->user_email) {
            $this->send_fulfillment_email($request, $file_url);
        }
        
        wp_send_json_success(array('message' => 'Request fulfilled successfully.'));
    }
    
    private function send_fulfillment_email($request, $file_url) {
        $to = $request->user_email;
        $form_data = json_decode($request->form_data ?? '{}', true);
        
        $service_names = array(
            'nin_slip_download' => 'NIN Slip Download',
            'nin_modification' => 'NIN Data Modification',
            'nin_dob_correction' => 'NIN Date of Birth Correction',
            'nin_slip' => 'Premium NIN Slip',
            'nin_standard_slip' => 'Standard NIN Slip'
        );
        $service_name = $service_names[$request->service_type ?? ''] ?? 'NIN Service';
        
        $subject_line = '✅ Your ' . $service_name . ' is Ready - ZonaTech NG';
        
        $download_button = '';
        if ($file_url) {
            $download_button = '
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . esc_url($file_url) . '" style="display: inline-block; background: linear-gradient(135deg, #22c55e, #16a34a); color: #ffffff; padding: 15px 40px; border-radius: 10px; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-download"></i> Download Your Document
                    </a>
                </div>';
        }
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
            <style>
                body { font-family: Arial, sans-serif; background-color: #0f0f1a; color: #ffffff; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 15px; overflow: hidden; }
                .header { background: linear-gradient(135deg, #22c55e, #16a34a); padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .success-icon { font-size: 48px; text-align: center; margin-bottom: 20px; }
                .info-box { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 10px; padding: 20px; margin: 20px 0; }
                .footer { padding: 20px; text-align: center; color: rgba(255,255,255,0.6); font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 Your Document is Ready!</h1>
                </div>
                <div class="content">
                    <div class="success-icon">📄</div>
                    <h2 style="text-align: center; margin-bottom: 20px;">Your ' . esc_html($service_name) . ' has been processed!</h2>
                    
                    <div class="info-box">
                        <p><strong>NIN:</strong> ' . esc_html($request->nin_number) . '</p>
                        <p><strong>Service:</strong> ' . esc_html($service_name) . '</p>
                    </div>
                    
                    ' . $download_button . '
                    
                    <p style="text-align: center; color: rgba(255,255,255,0.7);">
                        If the download button does not work, copy this link:<br>
                        <small style="color: #a78bfa; word-break: break-all;">' . esc_url($file_url) . '</small>
                    </p>
                </div>
                <div class="footer">
                    <p>Thank you for choosing ZonaTech NG!</p>
                    <p>If you have any questions, contact us at ' . esc_html(ZONATECH_SUPPORT_EMAIL) . '</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <' . sanitize_email(ZONATECH_SUPPORT_EMAIL) . '>'
        );
        
        // If there's an attachment, we need to send it differently
        if ($file_url) {
            // For now, just include the download link in the email
            wp_mail($to, $subject_line, $message, $headers);
        } else {
            wp_mail($to, $subject_line, $message, $headers);
        }
    }
}