<?php
/**
 * OtaPay.ng Integration Class
 * For automatic WAEC and NECO scratch card purchases
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_OtaPay {
    
    private static $instance = null;
    private $api_url = 'https://app.otapay.ng/api/exampin/';
    private $api_key;
    private $enabled;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->api_key = get_option('zonatech_otapay_api_key', '');
        $this->enabled = get_option('zonatech_otapay_enabled', '0') === '1';
        
        add_action('wp_ajax_zonatech_test_otapay', array($this, 'test_connection'));
    }
    
    /**
     * Check if OtaPay is enabled and configured
     */
    public function is_available() {
        return $this->enabled && !empty($this->api_key);
    }
    
    /**
     * Get provider ID for card type
     * Provider 1 = WAEC
     * Provider 2 = NECO
     */
    public function get_provider_id($card_type) {
        $providers = array(
            'waec' => '1',
            'neco' => '2'
        );
        
        return isset($providers[$card_type]) ? $providers[$card_type] : null;
    }
    
    /**
     * Purchase exam PINs from OtaPay
     * 
     * @param string $card_type - 'waec' or 'neco'
     * @param int $quantity - Number of PINs to purchase
     * @param string $reference - Unique transaction reference
     * @return array - Result with status and pins
     */
    public function purchase_pins($card_type, $quantity = 1, $reference = null) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'message' => 'OtaPay is not configured or enabled'
            );
        }
        
        $provider_id = $this->get_provider_id($card_type);
        
        if (!$provider_id) {
            return array(
                'success' => false,
                'message' => 'Invalid card type. Only WAEC and NECO are supported via OtaPay.'
            );
        }
        
        // Generate reference if not provided
        if (empty($reference)) {
            $reference = 'ZONA_' . time() . '_' . wp_rand(1000, 9999);
        }
        
        // Prepare payload
        $payload = array(
            'provider' => $provider_id,
            'quantity' => strval($quantity),
            'ref' => $reference
        );
        
        // Make API request
        $response = wp_remote_post($this->api_url, array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => wp_json_encode($payload)
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'API returned error code: ' . $response_code,
                'data' => $data
            );
        }
        
        // Check response status
        if (isset($data['status']) && strtolower($data['status']) === 'success') {
            // Extract PINs from response
            // API returns: {"status": "success", "pins": "123456,123456,123456"}
            $pins = '';
            if (isset($data['pins'])) {
                $pins = $data['pins'];
            } elseif (isset($data['pin'])) {
                $pins = $data['pin'];
            } elseif (isset($data['token'])) {
                $pins = $data['token'];
            }
            
            // Convert comma-separated PINs to array
            $pins_array = array_filter(array_map('trim', explode(',', $pins)));
            
            return array(
                'success' => true,
                'message' => 'PINs purchased successfully',
                'pins' => $pins_array,
                'pins_string' => $pins,
                'reference' => $reference,
                'card_type' => $card_type,
                'quantity' => count($pins_array)
            );
        }
        
        return array(
            'success' => false,
            'message' => isset($data['msg']) ? $data['msg'] : 'Unknown error from OtaPay',
            'data' => $data
        );
    }
    
    /**
     * Process scratch card purchase after payment
     * Called when payment is verified
     */
    public function process_purchase($user_id, $card_type, $purchase_id) {
        if (!$this->is_available()) {
            return array(
                'success' => false,
                'message' => 'OtaPay not available'
            );
        }
        
        // Only process WAEC and NECO via OtaPay
        if (!in_array($card_type, array('waec', 'neco'))) {
            return array(
                'success' => false,
                'message' => 'Card type not supported via OtaPay'
            );
        }
        
        global $wpdb;
        $table_cards = $wpdb->prefix . 'zonatech_scratch_cards';
        
        // Generate unique reference
        $reference = 'ZONA_OTAPAY_' . $purchase_id . '_' . time();
        
        // Purchase from OtaPay
        $result = $this->purchase_pins($card_type, 1, $reference);
        
        if ($result['success'] && !empty($result['pins'])) {
            $pin = $result['pins'][0];
            $serial = 'OTA' . date('Ymd') . strtoupper(substr(md5($reference), 0, 6));
            
            // Insert the card into database
            $inserted = $wpdb->insert($table_cards, array(
                'card_type' => $card_type,
                'serial_number' => $serial,
                'pin' => $pin,
                'status' => 'sold',
                'user_id' => $user_id,
                'sold_at' => current_time('mysql'),
                'created_at' => current_time('mysql')
            ));
            
            if ($inserted) {
                // Log activity
                ZonaTech_Activity_Log::log(
                    $user_id,
                    'otapay_purchase',
                    sprintf('Purchased %s PIN via OtaPay: %s', strtoupper($card_type), $serial)
                );
                
                return array(
                    'success' => true,
                    'message' => 'PIN purchased successfully',
                    'pin' => $pin,
                    'serial' => $serial,
                    'card_type' => $card_type
                );
            }
        }
        
        // Log failure
        $error_message = isset($result['message']) ? $result['message'] : 'Unknown error';
        ZonaTech_Activity_Log::log(
            $user_id,
            'otapay_failed',
            sprintf('Failed to purchase %s PIN via OtaPay: %s', strtoupper($card_type), $error_message)
        );
        
        $return_message = isset($result['message']) ? $result['message'] : 'Failed to purchase PIN from OtaPay';
        return array(
            'success' => false,
            'message' => $return_message
        );
    }
    
    /**
     * Test OtaPay connection (admin only)
     */
    public function test_connection() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        if (!$this->is_available()) {
            wp_send_json_error(array('message' => 'OtaPay is not configured'));
        }
        
        // We can't really test without making a purchase
        // Just verify the API key is set
        wp_send_json_success(array(
            'message' => 'OtaPay is configured and enabled',
            'api_url' => $this->api_url
        ));
    }
}

// Initialize
ZonaTech_OtaPay::get_instance();