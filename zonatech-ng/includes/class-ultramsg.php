<?php
/**
 * UltraMsg WhatsApp API Integration Class
 * 
 * Provides automatic WhatsApp messaging functionality using UltraMsg API
 * Free tier: 500 messages/month
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_UltraMsg {
    
    private static $instance = null;
    private $instance_id;
    private $token;
    private $api_url;
    private $base_api_url = 'https://api.ultramsg.com/';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->instance_id = get_option('zonatech_ultramsg_instance_id', '');
        $this->token = get_option('zonatech_ultramsg_token', '');
        $custom_api_url = get_option('zonatech_ultramsg_api_url', '');
        
        // Use custom API URL if provided, otherwise build from instance ID
        if (!empty($custom_api_url)) {
            // Ensure URL ends with /
            $this->api_url = rtrim($custom_api_url, '/') . '/';
        } elseif (!empty($this->instance_id)) {
            $this->api_url = $this->base_api_url . $this->instance_id . '/';
        } else {
            $this->api_url = $this->base_api_url;
        }
    }
    
    /**
     * Check if UltraMsg is configured
     */
    public function is_configured() {
        return (!empty($this->instance_id) || !empty($this->api_url)) && !empty($this->token);
    }
    
    /**
     * Send a WhatsApp message
     * 
     * @param string $phone_number Phone number (with country code, e.g., 2348012345678)
     * @param string $message Message text to send
     * @return array Response with success status and message
     */
    public function send_message($phone_number, $message) {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'UltraMsg is not configured. Please add your API URL/Instance ID and Token in Settings.'
            );
        }
        
        // Format phone number - ensure it has country code
        $phone_number = $this->format_phone_number($phone_number);
        
        // API URL already includes instance ID, just append the endpoint
        $url = $this->api_url . 'messages/chat';
        
        $body = array(
            'token' => $this->token,
            'to' => $phone_number,
            'body' => $message,
            'priority' => 10,
            'referenceId' => ''
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => $body,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('UltraMsg Error: ' . $response->get_error_message());
            return array(
                'success' => false,
                'message' => 'Failed to send message: ' . $response->get_error_message()
            );
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Handle different success response formats
        $is_sent = (isset($response_body['sent']) && ($response_body['sent'] === 'true' || $response_body['sent'] === true)) ||
                   (isset($response_body['message']) && $response_body['message'] === 'ok');
        
        if ($is_sent) {
            return array(
                'success' => true,
                'message' => 'WhatsApp message sent successfully',
                'data' => $response_body
            );
        } else {
            $error_message = $response_body['error'] ?? $response_body['message'] ?? 'Unknown error';
            error_log('UltraMsg Error Response: ' . wp_json_encode($response_body));
            return array(
                'success' => false,
                'message' => 'Failed to send message: ' . $error_message,
                'data' => $response_body
            );
        }
    }
    
    /**
     * Send a WhatsApp image/document
     * 
     * @param string $phone_number Phone number
     * @param string $image_url URL of the image/document
     * @param string $caption Optional caption
     * @return array Response
     */
    public function send_image($phone_number, $image_url, $caption = '') {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'UltraMsg is not configured.'
            );
        }
        
        $phone_number = $this->format_phone_number($phone_number);
        
        // API URL already includes instance ID
        $url = $this->api_url . 'messages/image';
        
        $body = array(
            'token' => $this->token,
            'to' => $phone_number,
            'image' => $image_url,
            'caption' => $caption
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => $body,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Failed to send image: ' . $response->get_error_message()
            );
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        $is_sent = (isset($response_body['sent']) && ($response_body['sent'] === 'true' || $response_body['sent'] === true)) ||
                   (isset($response_body['message']) && $response_body['message'] === 'ok');
        
        if ($is_sent) {
            return array(
                'success' => true,
                'message' => 'WhatsApp image sent successfully',
                'data' => $response_body
            );
        } else {
            $error_message = $response_body['error'] ?? $response_body['message'] ?? 'Unknown error';
            return array(
                'success' => false,
                'message' => 'Failed to send image: ' . $error_message
            );
        }
    }
    
    /**
     * Send a WhatsApp document
     * 
     * @param string $phone_number Phone number
     * @param string $document_url URL of the document
     * @param string $filename Filename for the document
     * @param string $caption Optional caption
     * @return array Response
     */
    public function send_document($phone_number, $document_url, $filename = '', $caption = '') {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'UltraMsg is not configured.'
            );
        }
        
        $phone_number = $this->format_phone_number($phone_number);
        
        // API URL already includes instance ID
        $url = $this->api_url . 'messages/document';
        
        $body = array(
            'token' => $this->token,
            'to' => $phone_number,
            'document' => $document_url,
            'filename' => $filename,
            'caption' => $caption
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => $body,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Failed to send document: ' . $response->get_error_message()
            );
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        $is_sent = (isset($response_body['sent']) && ($response_body['sent'] === 'true' || $response_body['sent'] === true)) ||
                   (isset($response_body['message']) && $response_body['message'] === 'ok');
        
        if ($is_sent) {
            return array(
                'success' => true,
                'message' => 'WhatsApp document sent successfully',
                'data' => $response_body
            );
        } else {
            $error_message = $response_body['error'] ?? $response_body['message'] ?? 'Unknown error';
            return array(
                'success' => false,
                'message' => 'Failed to send document: ' . $error_message
            );
        }
    }
    
    /**
     * Get instance status
     * 
     * @return array Status information
     */
    public function get_status() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'UltraMsg is not configured.'
            );
        }
        
        // API URL already includes instance ID
        $url = $this->api_url . 'instance/status?token=' . $this->token;
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Failed to get status: ' . $response->get_error_message()
            );
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        return array(
            'success' => true,
            'data' => $response_body
        );
    }
    
    /**
     * Test the connection
     * 
     * @return array Test result
     */
    public function test_connection() {
        $status = $this->get_status();
        
        if (!$status['success']) {
            return $status;
        }
        
        $data = $status['data'];
        
        // Handle different API response formats
        // Check for direct status.accountStatus structure
        if (isset($data['status']) && isset($data['status']['accountStatus'])) {
            $account_status = $data['status']['accountStatus'];
        }
        // Check for status directly as a string
        elseif (isset($data['status']) && is_string($data['status'])) {
            $account_status = $data['status'];
        }
        // Check for accountStatus at root level
        elseif (isset($data['accountStatus'])) {
            $account_status = $data['accountStatus'];
        }
        // Check for connected status
        elseif (isset($data['connected'])) {
            $account_status = $data['connected'] ? 'authenticated' : 'disconnected';
        }
        else {
            // Log the full response for debugging
            error_log('UltraMsg Status Response: ' . wp_json_encode($data));
            
            // If we have any data, try to extract useful info
            if (!empty($data)) {
                $status_text = is_array($data) ? wp_json_encode($data) : strval($data);
                return array(
                    'success' => false,
                    'message' => 'Unexpected API response. Check settings or try again. Response: ' . substr($status_text, 0, 200)
                );
            }
            
            return array(
                'success' => false,
                'message' => 'Could not determine connection status. Please verify your Instance ID and Token are correct.'
            );
        }
        
        // Ensure account_status is a string
        if (is_array($account_status)) {
            $account_status = isset($account_status['value']) ? $account_status['value'] : wp_json_encode($account_status);
        }
        
        if ($account_status === 'authenticated' || $account_status === 'connected') {
            $phone = '';
            if (isset($data['status']['displayedPhonenumber'])) {
                $phone = $data['status']['displayedPhonenumber'];
            } elseif (isset($data['displayedPhonenumber'])) {
                $phone = $data['displayedPhonenumber'];
            } elseif (isset($data['phone'])) {
                $phone = $data['phone'];
            }
            
            return array(
                'success' => true,
                'message' => 'UltraMsg is connected and ready to send messages!',
                'phone' => $phone ?: 'Connected'
            );
        } elseif ($account_status === 'init' || $account_status === 'loading') {
            return array(
                'success' => false,
                'message' => 'Please scan the QR code in your UltraMsg dashboard to authenticate WhatsApp.'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Account status: ' . strval($account_status) . '. Please check your UltraMsg dashboard.'
            );
        }
    }
    
    /**
     * Format phone number to include country code
     * 
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    private function format_phone_number($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, assume Nigerian number and add 234
        if (substr($phone, 0, 1) === '0') {
            $phone = '234' . substr($phone, 1);
        }
        
        // If doesn't start with +, add it
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Send NIN service notification to admin
     * 
     * @param object $purchase Purchase object
     * @param array $meta_data Form metadata
     * @param object $user User object
     * @param string $service_name Service display name
     * @return array Result
     */
    public function send_nin_notification_to_admin($purchase, $meta_data, $user, $service_name) {
        // Get admin WhatsApp number from settings
        $admin_phone = get_option('zonatech_admin_whatsapp', '');
        
        if (empty($admin_phone)) {
            return array(
                'success' => false,
                'message' => 'Admin WhatsApp number not configured.'
            );
        }
        
        // Build notification message
        $message_parts = array();
        $message_parts[] = "🔔 *NEW " . strtoupper($service_name) . " REQUEST*";
        $message_parts[] = "";
        $message_parts[] = "📋 *Service Details:*";
        $message_parts[] = "• Service: " . $service_name;
        $message_parts[] = "• Reference: " . $purchase->reference;
        $message_parts[] = "• Amount Paid: ₦" . number_format($purchase->amount);
        $message_parts[] = "";
        $message_parts[] = "👤 *Customer Details:*";
        $message_parts[] = "• Name: " . $user->display_name;
        $message_parts[] = "• Email: " . $user->user_email;
        
        // Add NIN-specific details based on service type
        if ($purchase->purchase_type === 'nin_verification') {
            $verification_method = $meta_data['verification_method'] ?? 'nin_number';
            $slip_type = $meta_data['slip_type'] ?? 'regular';
            $message_parts[] = "";
            $message_parts[] = "📝 *Verification Details:*";
            $message_parts[] = "• Method: " . ucwords(str_replace('_', ' ', $verification_method));
            $message_parts[] = "• Slip Type: " . ucfirst($slip_type);
            
            if ($verification_method === 'nin_number' && !empty($meta_data['nin'])) {
                $message_parts[] = "• NIN: " . $meta_data['nin'];
            } elseif ($verification_method === 'phone_number' && !empty($meta_data['phone_nin'])) {
                $message_parts[] = "• Phone: " . $meta_data['phone_nin'];
            } elseif ($verification_method === 'tracking_id' && !empty($meta_data['tracking_id'])) {
                $message_parts[] = "• Tracking ID: " . $meta_data['tracking_id'];
            } elseif ($verification_method === 'demographic') {
                $message_parts[] = "• First Name: " . ($meta_data['first_name'] ?? '');
                $message_parts[] = "• Last Name: " . ($meta_data['last_name'] ?? '');
                $message_parts[] = "• DOB: " . ($meta_data['date_of_birth'] ?? '');
                $message_parts[] = "• Gender: " . ucfirst($meta_data['gender'] ?? '');
            }
        } elseif ($purchase->purchase_type === 'nin_validation') {
            $validation_type = $meta_data['validation_type'] ?? '';
            $message_parts[] = "";
            $message_parts[] = "📝 *Validation Details:*";
            $message_parts[] = "• Type: " . ucwords(str_replace('_', ' ', $validation_type));
            $message_parts[] = "• NIN: " . ($meta_data['nin'] ?? '');
        }
        
        $message_parts[] = "";
        $message_parts[] = "📅 Date: " . date('M j, Y g:i A');
        $message_parts[] = "";
        $message_parts[] = "⚡ *Please process this request ASAP!*";
        
        $message = implode("\n", $message_parts);
        
        return $this->send_message($admin_phone, $message);
    }
}

// Note: Class is initialized on demand via ZonaTech_UltraMsg::get_instance()
// Do NOT auto-initialize here to avoid issues with WordPress loading order