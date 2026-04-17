<?php
/**
 * GVerifyer API Integration Class
 * Integrates with gverifyer.com for NIN verification and validation
 * 
 * API Documentation: https://gverifyer.com/documentation.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_GVerifyer_API {
    
    private static $instance = null;
    
    /**
     * API Base URL
     */
    private $api_base_url = 'https://gverifyer.com/api/verification/';
    
    /**
     * API Key - stored in WordPress options
     */
    private $api_key = '';
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->api_key = get_option('zonatech_gverifyer_api_key', '');
        
        // Register AJAX handlers
        add_action('wp_ajax_zonatech_gverifyer_verify', array($this, 'ajax_verify_nin'));
        add_action('wp_ajax_zonatech_save_gverifyer_settings', array($this, 'save_settings'));
        add_action('wp_ajax_zonatech_test_gverifyer_api', array($this, 'test_api_connection'));
    }
    
    /**
     * Get API Key
     */
    public function get_api_key() {
        return $this->api_key;
    }
    
    /**
     * Check if API is configured
     */
    public function is_configured() {
        return !empty($this->api_key);
    }
    
    /**
     * Save API settings
     */
    public function save_settings() {
        check_ajax_referer('zonatech_gverifyer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API key is required.'));
        }
        
        update_option('zonatech_gverifyer_api_key', $api_key);
        $this->api_key = $api_key;
        
        wp_send_json_success(array('message' => 'API settings saved successfully!'));
    }
    
    /**
     * Test API connection
     */
    public function test_api_connection() {
        check_ajax_referer('zonatech_gverifyer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        if (!$this->is_configured()) {
            wp_send_json_error(array('message' => 'API key not configured. Please save your API key first.'));
        }
        
        // Test with a sample request to check if API key is valid
        $response = $this->make_api_request('nin_by_nin.php', array(
            'nin' => '00000000000' // Test NIN - will return error but confirms API key works
        ));
        
        // If we get a response (even an error about invalid NIN), the API key is working
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'API connection failed: ' . $response->get_error_message()));
        }
        
        // Check if the error is about the API key or just invalid NIN
        if (isset($response['error']) && strpos(strtolower($response['error']), 'api') !== false) {
            wp_send_json_error(array('message' => 'Invalid API key. Please check your key and try again.'));
        }
        
        wp_send_json_success(array('message' => 'API connection successful! Your API key is valid.'));
    }
    
    /**
     * Make API request to GVerifyer
     * 
     * @param string $endpoint The API endpoint (e.g., 'nin_by_nin.php')
     * @param array $params Request parameters
     * @return array|WP_Error Response data or error
     */
    private function make_api_request($endpoint, $params = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('no_api_key', 'GVerifyer API key not configured.');
        }
        
        // Add API key to params
        $params['api_key'] = $this->api_key;
        
        $url = $this->api_base_url . $endpoint;
        
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($params)
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'API returned error code: ' . $response_code);
        }
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to parse API response.');
        }
        
        return $data;
    }
    
    /**
     * Verify NIN by NIN Number
     * 
     * @param string $nin 11-digit NIN number
     * @return array|WP_Error
     */
    public function verify_by_nin($nin) {
        // Validate NIN format
        if (!preg_match('/^\d{11}$/', $nin)) {
            return new WP_Error('invalid_nin', 'Invalid NIN format. Must be 11 digits.');
        }
        
        return $this->make_api_request('nin_by_nin.php', array(
            'nin' => $nin
        ));
    }
    
    /**
     * Verify NIN by Phone Number
     * 
     * @param string $phone 11-digit Nigerian phone number
     * @return array|WP_Error
     */
    public function verify_by_phone($phone) {
        // Validate phone format - Nigerian numbers: 0701..., 0801..., 0901... etc.
        if (!preg_match('/^0[7-9][0-1]\d{8}$/', $phone) && !preg_match('/^0[7-9]\d{9}$/', $phone)) {
            return new WP_Error('invalid_phone', 'Invalid phone format. Use Nigerian format (e.g., 08012345678).');
        }
        
        return $this->make_api_request('nin_by_phone.php', array(
            'phone' => $phone
        ));
    }
    
    /**
     * Verify NIN by Demographic Information
     * 
     * @param string $firstname First name
     * @param string $lastname Last name
     * @param string $dob Date of birth (DD-MM-YYYY format)
     * @param string $gender Gender (MALE/FEMALE)
     * @return array|WP_Error
     */
    public function verify_by_demographic($firstname, $lastname, $dob, $gender) {
        // Validate inputs
        if (empty($firstname) || empty($lastname)) {
            return new WP_Error('missing_name', 'First name and last name are required.');
        }
        
        if (empty($dob)) {
            return new WP_Error('missing_dob', 'Date of birth is required.');
        }
        
        // Convert date format if needed (from YYYY-MM-DD to DD-MM-YYYY)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            $parts = explode('-', $dob);
            $dob = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
        
        $gender = strtoupper($gender);
        if (!in_array($gender, array('MALE', 'FEMALE'))) {
            return new WP_Error('invalid_gender', 'Gender must be MALE or FEMALE.');
        }
        
        return $this->make_api_request('nin_by_demo.php', array(
            'firstname' => strtoupper($firstname),
            'lastname' => strtoupper($lastname),
            'dob' => $dob,
            'gender' => $gender
        ));
    }
    
    /**
     * AJAX handler for NIN verification (called from admin dashboard)
     */
    public function ajax_verify_nin() {
        check_ajax_referer('zonatech_gverifyer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $request_id = intval($_POST['request_id'] ?? 0);
        $verification_method = sanitize_text_field($_POST['verification_method'] ?? 'nin');
        
        if (!$request_id) {
            wp_send_json_error(array('message' => 'Invalid request ID.'));
        }
        
        // Get request details from database
        global $wpdb;
        $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
        
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_nin WHERE id = %d",
            $request_id
        ));
        
        if (!$request) {
            wp_send_json_error(array('message' => 'Request not found.'));
        }
        
        $form_data = json_decode($request->form_data ?? '{}', true);
        $result = null;
        
        // Determine which verification method to use
        switch ($verification_method) {
            case 'nin':
                $nin = $request->nin_number ?? ($form_data['nin'] ?? '');
                if (empty($nin)) {
                    wp_send_json_error(array('message' => 'NIN number not found in request.'));
                }
                $result = $this->verify_by_nin($nin);
                break;
                
            case 'phone':
                $phone = $form_data['phone_nin'] ?? ($form_data['phone'] ?? '');
                if (empty($phone)) {
                    wp_send_json_error(array('message' => 'Phone number not found in request.'));
                }
                $result = $this->verify_by_phone($phone);
                break;
                
            case 'demographic':
                $firstname = $form_data['first_name'] ?? ($form_data['firstname'] ?? '');
                $lastname = $form_data['last_name'] ?? ($form_data['lastname'] ?? '');
                $dob = $form_data['date_of_birth'] ?? ($form_data['dob'] ?? '');
                $gender = $form_data['gender'] ?? '';
                
                if (empty($firstname) || empty($lastname) || empty($dob)) {
                    wp_send_json_error(array('message' => 'Demographic information not found in request.'));
                }
                
                $result = $this->verify_by_demographic($firstname, $lastname, $dob, $gender);
                break;
                
            default:
                wp_send_json_error(array('message' => 'Invalid verification method.'));
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Check for API error in response
        if (isset($result['error'])) {
            wp_send_json_error(array(
                'message' => $result['error'],
                'response' => $result
            ));
        }
        
        // Log the successful verification
        $wpdb->update(
            $table_nin,
            array(
                'api_response' => json_encode($result),
                'api_verified_at' => current_time('mysql')
            ),
            array('id' => $request_id)
        );
        
        wp_send_json_success(array(
            'message' => 'Verification successful!',
            'data' => $result
        ));
    }
    
    /**
     * Process automatic verification after payment
     * 
     * @param int $request_id NIN request ID
     * @return array|WP_Error Verification result
     */
    public function process_automatic_verification($request_id) {
        global $wpdb;
        $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
        
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_nin WHERE id = %d",
            $request_id
        ));
        
        if (!$request) {
            return new WP_Error('not_found', 'Request not found.');
        }
        
        $form_data = json_decode($request->form_data ?? '{}', true);
        $verification_method = $form_data['verification_method'] ?? 'nin_number';
        $result = null;
        
        switch ($verification_method) {
            case 'nin_number':
                $nin = $request->nin_number ?? ($form_data['nin'] ?? '');
                $result = $this->verify_by_nin($nin);
                break;
                
            case 'phone_number':
                $phone = $form_data['phone_nin'] ?? '';
                $result = $this->verify_by_phone($phone);
                break;
                
            case 'demographic':
                $firstname = $form_data['first_name'] ?? '';
                $lastname = $form_data['last_name'] ?? '';
                $dob = $form_data['date_of_birth'] ?? '';
                $gender = $form_data['gender'] ?? 'MALE';
                $result = $this->verify_by_demographic($firstname, $lastname, $dob, $gender);
                break;
                
            case 'tracking_id':
                // Tracking ID verification may need different endpoint - fallback to NIN if available
                $nin = $request->nin_number ?? '';
                if (!empty($nin)) {
                    $result = $this->verify_by_nin($nin);
                } else {
                    return new WP_Error('not_supported', 'Tracking ID verification requires manual processing.');
                }
                break;
                
            default:
                return new WP_Error('invalid_method', 'Invalid verification method.');
        }
        
        if (is_wp_error($result)) {
            // Update request with error
            $wpdb->update(
                $table_nin,
                array(
                    'api_response' => json_encode(array('error' => $result->get_error_message())),
                    'api_verified_at' => current_time('mysql'),
                    'status' => 'api_error'
                ),
                array('id' => $request_id)
            );
            return $result;
        }
        
        // Check for success
        if (isset($result['status']) && $result['status'] === 'success') {
            // Update request with successful response
            $wpdb->update(
                $table_nin,
                array(
                    'api_response' => json_encode($result),
                    'api_verified_at' => current_time('mysql'),
                    'status' => 'verified'
                ),
                array('id' => $request_id)
            );
            
            // Send success notification to user
            $this->send_verification_result_email($request_id, $result);
        } else {
            // Update with API response
            $wpdb->update(
                $table_nin,
                array(
                    'api_response' => json_encode($result),
                    'api_verified_at' => current_time('mysql')
                ),
                array('id' => $request_id)
            );
        }
        
        return $result;
    }
    
    /**
     * Send verification result email to user
     */
    private function send_verification_result_email($request_id, $result) {
        global $wpdb;
        $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
        
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT n.*, u.user_email, u.display_name 
             FROM $table_nin n 
             LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID 
             WHERE n.id = %d",
            $request_id
        ));
        
        if (!$request || !$request->user_email) {
            return;
        }
        
        $form_data = json_decode($request->form_data ?? '{}', true);
        $user_email = $form_data['email'] ?? $request->user_email;
        
        // Extract relevant data from result
        $nin_data = $result['data'] ?? $result;
        
        $subject = '✅ Your NIN Verification Result - ZonaTech NG';
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background-color: #0f0f1a; color: #ffffff; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 15px; overflow: hidden; }
                .header { background: linear-gradient(135deg, #22c55e, #16a34a); padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .info-box { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 10px; padding: 20px; margin: 20px 0; }
                .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
                .info-label { color: rgba(255,255,255,0.6); }
                .info-value { color: #ffffff; font-weight: 600; }
                .footer { padding: 20px; text-align: center; color: rgba(255,255,255,0.6); font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 NIN Verification Complete!</h1>
                </div>
                <div class="content">
                    <p>Dear ' . esc_html($request->display_name) . ',</p>
                    <p>Your NIN verification has been completed successfully. Here are the details:</p>
                    
                    <div class="info-box">';
        
        // Add verification data
        if (is_array($nin_data)) {
            foreach ($nin_data as $key => $value) {
                if (!is_array($value) && !empty($value)) {
                    $label = ucwords(str_replace('_', ' ', $key));
                    $message .= '<div class="info-row"><span class="info-label">' . esc_html($label) . ':</span><span class="info-value">' . esc_html($value) . '</span></div>';
                }
            }
        }
        
        $message .= '
                    </div>
                    
                    <p style="color: rgba(255,255,255,0.7);">Please keep this information safe for your records.</p>
                </div>
                <div class="footer">
                    <p>Thank you for using ZonaTech NG!</p>
                </div>
            </div>
        </body>
        </html>';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <' . sanitize_email(ZONATECH_SUPPORT_EMAIL) . '>'
        );
        
        wp_mail($user_email, $subject, $message, $headers);
    }
    
    /**
     * Generate slip from API response
     * This would typically download or generate a PDF slip
     * 
     * @param int $request_id
     * @param string $slip_type (regular, standard, premium, vnin)
     * @return string|WP_Error File URL or error
     */
    public function generate_slip($request_id, $slip_type = 'regular') {
        global $wpdb;
        $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
        
        $request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_nin WHERE id = %d",
            $request_id
        ));
        
        if (!$request) {
            return new WP_Error('not_found', 'Request not found.');
        }
        
        $api_response = json_decode($request->api_response ?? '{}', true);
        
        if (empty($api_response) || isset($api_response['error'])) {
            return new WP_Error('no_data', 'No verification data available. Please verify first.');
        }
        
        // Check if the API response includes a slip URL
        if (isset($api_response['slip_url'])) {
            return $api_response['slip_url'];
        }
        
        // Otherwise, mark as needing manual slip generation
        return new WP_Error('manual_required', 'Slip generation requires manual processing. Please use the admin dashboard.');
    }
}

// Initialize the class
ZonaTech_GVerifyer_API::get_instance();