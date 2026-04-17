<?php
/**
 * Paystack Integration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class ZonaTech_Paystack {
    
    private static $instance = null;
    private $secret_key;
    private $public_key;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->secret_key = get_option('zonatech_paystack_secret_key', '');
        $this->public_key = get_option('zonatech_paystack_public_key', '');
        
        add_action('wp_ajax_zonatech_initialize_payment', array($this, 'initialize_payment'));
        add_action('wp_ajax_zonatech_verify_payment', array($this, 'verify_payment'));
        add_action('wp_ajax_nopriv_zonatech_paystack_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_zonatech_paystack_webhook', array($this, 'handle_webhook'));
    }
    
    public function initialize_payment() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to make payment.'));
            return;
        }
        
        // Check if Paystack is configured
        if (empty($this->public_key) || empty($this->secret_key)) {
            wp_send_json_error(array('message' => 'Payment system is not configured. Please contact support at ' . ZONATECH_SUPPORT_EMAIL));
            return;
        }
        
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        $payment_type = sanitize_text_field($_POST['payment_type'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $meta_data = isset($_POST['meta_data']) ? json_decode(stripslashes($_POST['meta_data']), true) : array();
        
        if (empty($payment_type)) {
            wp_send_json_error(array('message' => 'Invalid payment type.'));
            return;
        }
        
        // Validate amount based on payment type
        $valid_amounts = array(
            'subject' => ZONATECH_SUBJECT_PRICE,
            'category' => defined('ZONATECH_CATEGORY_PRICE') ? ZONATECH_CATEGORY_PRICE : 5000,
            'subscription' => defined('ZONATECH_MONTHLY_PRICE') ? ZONATECH_MONTHLY_PRICE : 5000, // Default to monthly
            'nin_slip' => ZONATECH_NIN_SLIP_PRICE,
            'nin_standard_slip' => ZONATECH_NIN_STANDARD_SLIP_PRICE,
            'scratch_card' => ZONATECH_SCRATCH_CARD_PRICE,
            // NIN Services
            'nin_slip_download' => defined('ZONATECH_NIN_SLIP_DOWNLOAD_PRICE') ? ZONATECH_NIN_SLIP_DOWNLOAD_PRICE : 1300,
            'nin_modification' => defined('ZONATECH_NIN_MODIFICATION_PRICE') ? ZONATECH_NIN_MODIFICATION_PRICE : 3800,
            'nin_dob_correction' => defined('ZONATECH_NIN_DOB_CORRECTION_PRICE') ? ZONATECH_NIN_DOB_CORRECTION_PRICE : 5300,
            // NIN Verification & Validation Services - prices set dynamically below
            'nin_verification' => 280, // Default - will be updated based on slip type
            'nin_validation' => 2300,
        );
        
        // Handle subscription pricing based on plan
        if ($payment_type === 'subscription' && isset($meta_data['plan'])) {
            $plan = strtolower($meta_data['plan']);
            if ($plan === 'sixmonth') {
                $valid_amounts['subscription'] = defined('ZONATECH_6MONTH_PRICE') ? ZONATECH_6MONTH_PRICE : 25000;
            } else {
                $valid_amounts['subscription'] = defined('ZONATECH_MONTHLY_PRICE') ? ZONATECH_MONTHLY_PRICE : 5000;
            }
        }
        
        // Handle dynamic NIN verification pricing based on slip type
        if ($payment_type === 'nin_verification' && isset($meta_data['slip_type'])) {
            $slip_prices = array(
                'regular' => 280,
                'standard' => 280,
                'premium' => 300,
                'vnin' => 300
            );
            $slip_type = strtolower($meta_data['slip_type']);
            $valid_amounts['nin_verification'] = isset($slip_prices[$slip_type]) ? $slip_prices[$slip_type] : 280;
        }
        
        // Handle dynamic scratch card pricing
        if ($payment_type === 'scratch_card' && isset($meta_data['card_type'])) {
            $card_type = strtolower($meta_data['card_type']);
            if ($card_type === 'waec') {
                $valid_amounts['scratch_card'] = defined('ZONATECH_WAEC_CARD_PRICE') ? ZONATECH_WAEC_CARD_PRICE : 3850;
            } elseif ($card_type === 'neco') {
                $valid_amounts['scratch_card'] = defined('ZONATECH_NECO_CARD_PRICE') ? ZONATECH_NECO_CARD_PRICE : 2550;
            }
        }
        
        if (!isset($valid_amounts[$payment_type])) {
            wp_send_json_error(array('message' => 'Invalid payment type: ' . $payment_type));
            return;
        }
        
        // Use the server-side amount to prevent tampering (ignore client-side amount)
        $amount = $valid_amounts[$payment_type];
        
        if ($amount <= 0) {
            wp_send_json_error(array('message' => 'Invalid payment amount for ' . $payment_type));
            return;
        }
        
        $reference = 'ZONA_' . time() . '_' . wp_rand(1000, 9999);
        
        // Create pending purchase record
        global $wpdb;
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        
        $item_name = $this->get_item_name($payment_type, $meta_data);
        
        $wpdb->insert($table_purchases, array(
            'user_id' => $user_id,
            'purchase_type' => $payment_type,
            'item_name' => $item_name,
            'amount' => $amount,
            'reference' => $reference,
            'status' => 'pending',
            'meta_data' => wp_json_encode($meta_data)
        ));
        
        // Return data for Paystack inline
        wp_send_json_success(array(
            'reference' => $reference,
            'email' => $user->user_email,
            'amount' => $amount * 100, // Convert to kobo
            'public_key' => $this->public_key,
            'currency' => 'NGN',
            'metadata' => array(
                'user_id' => $user_id,
                'payment_type' => $payment_type,
                'item_name' => $item_name,
                'custom_fields' => array(
                    array(
                        'display_name' => 'Customer Name',
                        'variable_name' => 'customer_name',
                        'value' => $user->display_name
                    ),
                    array(
                        'display_name' => 'Payment Type',
                        'variable_name' => 'payment_type',
                        'value' => $payment_type
                    )
                )
            )
        ));
    }
    
    public function verify_payment() {
        check_ajax_referer('zonatech_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please login to verify payment.'));
        }
        
        $reference = sanitize_text_field($_POST['reference'] ?? '');
        
        if (empty($reference)) {
            wp_send_json_error(array('message' => 'Invalid payment reference.'));
        }
        
        // Verify with Paystack API
        $response = wp_remote_get(
            'https://api.paystack.co/transaction/verify/' . $reference,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->secret_key
                )
            )
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Could not verify payment. Please contact support.'));
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$body['status'] || $body['data']['status'] !== 'success') {
            wp_send_json_error(array('message' => 'Payment verification failed.'));
        }
        
        // Update purchase record
        global $wpdb;
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_purchases WHERE reference = %s",
            $reference
        ));
        
        if (!$purchase) {
            wp_send_json_error(array('message' => 'Purchase record not found.'));
        }
        
        if ($purchase->status === 'completed') {
            wp_send_json_success(array('message' => 'Payment already processed.'));
        }
        
        // Mark as completed
        $wpdb->update(
            $table_purchases,
            array('status' => 'completed'),
            array('reference' => $reference)
        );
        
        // Process the purchase based on type
        $this->process_purchase($purchase);
        
        // Send purchase confirmation email
        $this->send_purchase_email($purchase);
        
        // Log activity
        $user_id = get_current_user_id();
        ZonaTech_Activity_Log::log(
            $user_id,
            'payment_completed',
            sprintf('Payment of ₦%s completed for %s', number_format($purchase->amount), $purchase->item_name),
            array('reference' => $reference, 'amount' => $purchase->amount)
        );
        
        wp_send_json_success(array(
            'message' => 'Payment successful!',
            'purchase' => array(
                'type' => $purchase->purchase_type,
                'item' => $purchase->item_name,
                'amount' => $purchase->amount
            )
        ));
    }
    
    private function send_purchase_email($purchase) {
        $user = get_userdata($purchase->user_id);
        if (!$user) return;
        
        $to = $user->user_email;
        $subject_line = '🎉 Purchase Successful - ' . sanitize_text_field($purchase->item_name);
        
        // Get dashboard URL based on purchase type
        $action_url = site_url('/zonatech-dashboard/');
        $action_text = 'View Your Dashboard';
        
        if ($purchase->purchase_type === 'subject') {
            $action_url = site_url('/zonatech-dashboard/#my-subjects');
            $action_text = 'View My Subjects';
        } elseif ($purchase->purchase_type === 'subscription' || $purchase->purchase_type === 'category') {
            $action_url = site_url('/zonatech-past-questions/');
            $action_text = 'Access Past Questions';
        } elseif ($purchase->purchase_type === 'scratch_card') {
            $action_url = site_url('/zonatech-scratch-cards/');
            $action_text = 'View My Cards';
        }
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #0f0f23; color: #ffffff; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: linear-gradient(145deg, #1a1a2e 0%, #16161a 100%); border-radius: 16px; padding: 40px; border: 1px solid rgba(139, 92, 246, 0.2); }
                .header { text-align: center; margin-bottom: 30px; }
                .logo { font-size: 28px; color: #8b5cf6; margin-bottom: 10px; }
                h2 { color: #22c55e; margin: 0; }
                .content { line-height: 1.8; color: #a0a0a0; }
                .purchase-details { background: rgba(139, 92, 246, 0.1); border-radius: 12px; padding: 20px; margin: 20px 0; border-left: 4px solid #8b5cf6; }
                .purchase-details p { margin: 8px 0; color: #ffffff; }
                .amount { font-size: 24px; font-weight: bold; color: #22c55e; }
                .btn { display: inline-block; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">🎓 ZonaTech NG</div>
                    <h2>Payment Successful!</h2>
                </div>
                <div class="content">
                    <p>Hi ' . esc_html($user->first_name ?: $user->display_name) . ',</p>
                    <p>Great news! Your payment has been processed successfully. Here are your purchase details:</p>
                    
                    <div class="purchase-details">
                        <p><strong>Item:</strong> ' . esc_html($purchase->item_name) . '</p>
                        <p><strong>Reference:</strong> ' . esc_html($purchase->reference) . '</p>
                        <p><strong>Amount Paid:</strong> <span class="amount">₦' . number_format($purchase->amount) . '</span></p>
                        <p><strong>Date:</strong> ' . date('F j, Y, g:i A') . '</p>
                    </div>
                    
                    <p>You can now access your purchase from your dashboard:</p>
                    
                    <center>
                        <a href="' . esc_url($action_url) . '" class="btn">' . esc_html($action_text) . '</a>
                    </center>
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
        
        wp_mail($to, $subject_line, $message, $headers);
    }
    
    private function process_purchase($purchase) {
        global $wpdb;
        $meta_data = json_decode($purchase->meta_data, true);
        
        // Normalize exam_type to lowercase to prevent case-mismatch issues
        if (isset($meta_data['exam_type'])) {
            $meta_data['exam_type'] = strtolower($meta_data['exam_type']);
        }
        
        switch ($purchase->purchase_type) {
            case 'subscription':
                // Handle subscription with expiration date based on plan
                $table_access = $wpdb->prefix . 'zonatech_user_access';
                $plan = $meta_data['plan'] ?? 'monthly';
                
                // Calculate expiration date based on plan
                if ($plan === 'sixmonth') {
                    $expires_at = date('Y-m-d H:i:s', strtotime('+6 months'));
                } else {
                    $expires_at = date('Y-m-d H:i:s', strtotime('+1 month'));
                }
                
                $wpdb->insert($table_access, array(
                    'user_id' => $purchase->user_id,
                    'exam_type' => $meta_data['exam_type'] ?? '',
                    'category' => $meta_data['category'] ?? '',
                    'subject' => '',
                    'purchase_id' => $purchase->id,
                    'expires_at' => $expires_at
                ));
                break;
            
            case 'category':
                // Grant access to all subjects in a category (1 month default for backward compatibility)
                $table_access = $wpdb->prefix . 'zonatech_user_access';
                $wpdb->insert($table_access, array(
                    'user_id' => $purchase->user_id,
                    'exam_type' => $meta_data['exam_type'] ?? '',
                    'category' => $meta_data['category'] ?? '',
                    'subject' => '',
                    'purchase_id' => $purchase->id,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 month'))
                ));
                break;
                
            case 'subject':
                // Legacy: Grant access to individual subject (1 month)
                $table_access = $wpdb->prefix . 'zonatech_user_access';
                $wpdb->insert($table_access, array(
                    'user_id' => $purchase->user_id,
                    'exam_type' => $meta_data['exam_type'] ?? '',
                    'subject' => $meta_data['subject'] ?? '',
                    'category' => '',
                    'purchase_id' => $purchase->id,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+1 month'))
                ));
                break;
                
            case 'scratch_card':
                // Assign a scratch card - try OtaPay first for WAEC/NECO
                $table_cards = $wpdb->prefix . 'zonatech_scratch_cards';
                $card_type = $meta_data['card_type'] ?? '';
                
                // Check if OtaPay is available for this card type
                $otapay = ZonaTech_OtaPay::get_instance();
                if ($otapay->is_available() && in_array($card_type, array('waec', 'neco'))) {
                    // Try to purchase from OtaPay
                    $otapay_result = $otapay->process_purchase($purchase->user_id, $card_type, $purchase->id);
                    if ($otapay_result['success']) {
                        // Card was purchased and assigned via OtaPay
                        break;
                    }
                    // If OtaPay fails, fall back to local stock
                }
                
                // Fallback to local stock
                $card = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_cards WHERE card_type = %s AND status = 'available' LIMIT 1",
                    $card_type
                ));
                
                if ($card) {
                    $wpdb->update(
                        $table_cards,
                        array(
                            'status' => 'sold',
                            'user_id' => $purchase->user_id,
                            'purchase_id' => $purchase->id,
                            'sold_at' => current_time('mysql')
                        ),
                        array('id' => $card->id)
                    );
                }
                break;
                
            case 'nin_slip':
                // Process NIN slip request
                $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
                $wpdb->update(
                    $table_nin,
                    array(
                        'status' => 'paid',
                        'purchase_id' => $purchase->id
                    ),
                    array(
                        'user_id' => $purchase->user_id,
                        'nin_number' => $meta_data['nin_number'] ?? '',
                        'status' => 'pending'
                    )
                );
                break;
                
            case 'nin_standard_slip':
                // Process NIN standard slip request
                $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
                $wpdb->update(
                    $table_nin,
                    array(
                        'status' => 'paid',
                        'purchase_id' => $purchase->id
                    ),
                    array(
                        'user_id' => $purchase->user_id,
                        'nin_number' => $meta_data['nin_number'] ?? '',
                        'status' => 'pending'
                    )
                );
                break;
            
            case 'nin_slip_download':
            case 'nin_modification':
            case 'nin_dob_correction':
                // Create NIN service request for admin fulfillment
                $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
                $wpdb->insert($table_nin, array(
                    'user_id' => $purchase->user_id,
                    'nin_number' => $meta_data['nin'] ?? '',
                    'service_type' => $purchase->purchase_type,
                    'form_data' => wp_json_encode($meta_data),
                    'status' => 'paid',
                    'purchase_id' => $purchase->id
                ));
                
                // Send confirmation email to user
                $this->send_nin_service_confirmation_email($purchase, $meta_data);
                break;
            
            case 'nin_verification':
            case 'nin_validation':
                // Create NIN verification/validation request for admin fulfillment
                $table_nin = $wpdb->prefix . 'zonatech_nin_requests';
                $wpdb->insert($table_nin, array(
                    'user_id' => $purchase->user_id,
                    'nin_number' => $meta_data['nin'] ?? $meta_data['phone_nin'] ?? '',
                    'service_type' => $purchase->purchase_type,
                    'form_data' => wp_json_encode($meta_data),
                    'status' => 'paid',
                    'purchase_id' => $purchase->id
                ));
                
                // Send confirmation email to user
                $this->send_nin_service_confirmation_email($purchase, $meta_data);
                
                // Send WhatsApp and Email notification to admin
                $this->send_admin_nin_notification($purchase, $meta_data);
                break;
        }
    }
    
    /**
     * Send WhatsApp and Email notification to admin for NIN service requests
     */
    private function send_admin_nin_notification($purchase, $meta_data) {
        $user = get_userdata($purchase->user_id);
        if (!$user) return;
        
        // Determine service type display name
        $service_names = array(
            'nin_verification' => 'NIN Verification',
            'nin_validation' => 'NIN Validation',
        );
        $service_name = $service_names[$purchase->purchase_type] ?? 'NIN Service';
        
        // Send email notification to admin
        $admin_email = get_option('zonatech_admin_email', '');
        if (empty($admin_email) && defined('ZONATECH_SUPPORT_EMAIL')) {
            $admin_email = ZONATECH_SUPPORT_EMAIL;
        }
        if (empty($admin_email)) {
            $admin_email = get_option('admin_email');
        }
        
        $this->send_admin_email_notification($admin_email, $purchase, $meta_data, $user, $service_name);
        
        // Send WhatsApp notification via UltraMsg if configured
        if (class_exists('ZonaTech_UltraMsg')) {
            $ultramsg = ZonaTech_UltraMsg::get_instance();
            if ($ultramsg->is_configured()) {
                $ultramsg->send_nin_notification_to_admin($purchase, $meta_data, $user, $service_name);
            }
        }
    }
    
    /**
     * Send email notification to admin for NIN service requests
     */
    private function send_admin_email_notification($admin_email, $purchase, $meta_data, $user, $service_name) {
        $subject_line = '🔔 New ' . $service_name . ' Request - ' . $purchase->reference;
        
        // Build HTML details based on service type
        $details_html = '';
        if ($purchase->purchase_type === 'nin_verification') {
            $verification_method = $meta_data['verification_method'] ?? 'nin_number';
            $slip_type = $meta_data['slip_type'] ?? 'regular';
            $details_html .= '<p><strong>Verification Method:</strong> ' . esc_html(ucwords(str_replace('_', ' ', $verification_method))) . '</p>';
            $details_html .= '<p><strong>Slip Type:</strong> ' . esc_html(ucfirst($slip_type)) . '</p>';
            
            if ($verification_method === 'nin_number' && !empty($meta_data['nin'])) {
                $details_html .= '<p><strong>NIN:</strong> ' . esc_html($meta_data['nin']) . '</p>';
            } elseif ($verification_method === 'phone_number' && !empty($meta_data['phone_nin'])) {
                $details_html .= '<p><strong>Phone Number:</strong> ' . esc_html($meta_data['phone_nin']) . '</p>';
            } elseif ($verification_method === 'tracking_id' && !empty($meta_data['tracking_id'])) {
                $details_html .= '<p><strong>Tracking ID:</strong> ' . esc_html($meta_data['tracking_id']) . '</p>';
            } elseif ($verification_method === 'demographic') {
                $details_html .= '<p><strong>First Name:</strong> ' . esc_html($meta_data['first_name'] ?? '') . '</p>';
                $details_html .= '<p><strong>Last Name:</strong> ' . esc_html($meta_data['last_name'] ?? '') . '</p>';
                $details_html .= '<p><strong>Date of Birth:</strong> ' . esc_html($meta_data['date_of_birth'] ?? '') . '</p>';
                $details_html .= '<p><strong>Gender:</strong> ' . esc_html(ucfirst($meta_data['gender'] ?? '')) . '</p>';
            }
        } elseif ($purchase->purchase_type === 'nin_validation') {
            $validation_type = $meta_data['validation_type'] ?? '';
            $details_html .= '<p><strong>Validation Type:</strong> ' . esc_html(ucwords(str_replace('_', ' ', $validation_type))) . '</p>';
            $details_html .= '<p><strong>NIN:</strong> ' . esc_html($meta_data['nin'] ?? '') . '</p>';
        }
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; background: #0f0f23; color: #ffffff; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: linear-gradient(145deg, #1a1a2e 0%, #16161a 100%); border-radius: 16px; padding: 30px; border: 1px solid rgba(239, 68, 68, 0.3); }
                .header { text-align: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
                .alert-icon { font-size: 40px; margin-bottom: 10px; }
                h2 { color: #f59e0b; margin: 0; font-size: 22px; }
                .content { line-height: 1.8; color: #e0e0e0; }
                .info-box { background: rgba(139, 92, 246, 0.1); border-radius: 12px; padding: 20px; margin: 20px 0; border-left: 4px solid #8b5cf6; }
                .info-box h3 { color: #a78bfa; margin: 0 0 15px 0; }
                .info-box p { margin: 8px 0; }
                .customer-box { background: rgba(34, 197, 94, 0.1); border-radius: 12px; padding: 20px; margin: 20px 0; border-left: 4px solid #22c55e; }
                .customer-box h3 { color: #22c55e; margin: 0 0 15px 0; }
                .amount { font-size: 24px; font-weight: bold; color: #22c55e; }
                .btn { display: inline-block; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 5px; }
                .btn-whatsapp { background: #25D366; color: #ffffff; }
                .btn-dashboard { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #ffffff; }
                .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="alert-icon">🔔</div>
                    <h2>New ' . esc_html($service_name) . ' Request!</h2>
                </div>
                <div class="content">
                    <div class="customer-box">
                        <h3>👤 Customer Information</h3>
                        <p><strong>Name:</strong> ' . esc_html($user->display_name) . '</p>
                        <p><strong>Email:</strong> ' . esc_html($user->user_email) . '</p>
                        <p><strong>User ID:</strong> ' . esc_html($purchase->user_id) . '</p>
                    </div>
                    
                    <div class="info-box">
                        <h3>📋 Service Details</h3>
                        <p><strong>Service:</strong> ' . esc_html($service_name) . '</p>
                        <p><strong>Reference:</strong> ' . esc_html($purchase->reference) . '</p>
                        <p><strong>Amount Paid:</strong> <span class="amount">₦' . number_format($purchase->amount) . '</span></p>
                        <p><strong>Date:</strong> ' . date('F j, Y, g:i A') . '</p>
                        ' . $details_html . '
                    </div>
                    
                    <div style="text-align: center; margin-top: 25px;">
                        <p style="color: #f59e0b; font-weight: bold;">⚡ Please process this request as soon as possible!</p>
                        <a href="' . esc_url(admin_url('admin.php?page=zonatech-dashboard#nin-requests')) . '" class="btn btn-dashboard">
                            📊 Go to Admin Dashboard
                        </a>
                    </div>
                </div>
                <div class="footer">
                    <p>This is an automated notification from ZonaTech NG</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $from_email = defined('ZONATECH_SUPPORT_EMAIL') ? ZONATECH_SUPPORT_EMAIL : get_option('admin_email');
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <' . sanitize_email($from_email) . '>'
        );
        
        wp_mail($admin_email, $subject_line, $message, $headers);
    }
    
    private function send_nin_service_confirmation_email($purchase, $meta_data) {
        $user = get_userdata($purchase->user_id);
        if (!$user) return;
        
        $to = $user->user_email;
        $first_name = $user->first_name ?: $user->display_name;
        $service_names = array(
            'nin_slip_download' => 'NIN Slip Download',
            'nin_modification' => 'NIN Data Modification',
            'nin_dob_correction' => 'NIN Date of Birth Correction',
            'nin_verification' => 'NIN Verification',
            'nin_validation' => 'NIN Validation'
        );
        $service_name = $service_names[$purchase->purchase_type] ?? 'NIN Service';
        $subject_line = '✅ Payment Successful - ' . $service_name . ' Request Confirmed | ZonaTech NG';
        
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: "Segoe UI", Arial, sans-serif; background-color: #0f0f1a; color: #ffffff; margin: 0; padding: 20px; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
                .header { background: linear-gradient(135deg, #10b981, #059669); padding: 40px 30px; text-align: center; }
                .header-icon { font-size: 60px; margin-bottom: 15px; }
                .header h1 { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -0.5px; }
                .header p { margin: 10px 0 0; font-size: 15px; opacity: 0.9; }
                .content { padding: 35px 30px; }
                .greeting { font-size: 18px; margin-bottom: 20px; color: #e0e0e0; }
                .greeting strong { color: #ffffff; }
                .success-message { background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.1)); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 20px; margin: 25px 0; text-align: center; }
                .success-message h2 { color: #10b981; margin: 0 0 10px; font-size: 18px; }
                .success-message p { margin: 0; color: #a0e0c8; font-size: 14px; }
                .info-box { background: rgba(139, 92, 246, 0.08); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px; padding: 25px; margin: 25px 0; }
                .info-box h3 { color: #a78bfa; margin: 0 0 20px; font-size: 16px; border-bottom: 1px solid rgba(139, 92, 246, 0.2); padding-bottom: 12px; }
                .info-row { display: table; width: 100%; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
                .info-row:last-child { border-bottom: none; }
                .info-label { display: table-cell; width: 40%; color: rgba(255,255,255,0.5); font-size: 14px; }
                .info-value { display: table-cell; text-align: right; color: #ffffff; font-weight: 600; font-size: 14px; }
                .processing-box { background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(124, 58, 237, 0.1)); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 12px; padding: 25px; margin: 25px 0; text-align: center; }
                .processing-box .icon { font-size: 40px; margin-bottom: 15px; }
                .processing-box h3 { color: #a78bfa; margin: 0 0 10px; font-size: 17px; }
                .processing-box p { color: rgba(255,255,255,0.8); margin: 0; font-size: 14px; line-height: 1.7; }
                .timeline { background: rgba(0,0,0,0.2); border-radius: 12px; padding: 20px; margin: 25px 0; }
                .timeline h4 { color: #f59e0b; margin: 0 0 15px; font-size: 15px; }
                .timeline-step { display: flex; align-items: flex-start; margin-bottom: 15px; }
                .timeline-step:last-child { margin-bottom: 0; }
                .timeline-step .step-icon { background: rgba(139, 92, 246, 0.2); color: #a78bfa; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 12px; flex-shrink: 0; }
                .timeline-step .step-text { color: rgba(255,255,255,0.7); font-size: 13px; padding-top: 4px; }
                .timeline-step.completed .step-icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
                .footer { padding: 25px 30px; text-align: center; background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.05); }
                .footer p { color: rgba(255,255,255,0.5); font-size: 12px; margin: 5px 0; }
                .footer a { color: #8b5cf6; text-decoration: none; }
                .highlight-time { color: #f59e0b; font-weight: 700; font-size: 16px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="header-icon">✅</div>
                    <h1>Payment Successful!</h1>
                    <p>Your ' . esc_html($service_name) . ' request has been received</p>
                </div>
                <div class="content">
                    <p class="greeting">Dear <strong>' . esc_html($first_name) . '</strong>,</p>
                    
                    <div class="success-message">
                        <h2>🎉 Thank You for Your Payment!</h2>
                        <p>We have successfully received your payment and your details have been submitted for processing.</p>
                    </div>
                    
                    <div class="info-box">
                        <h3>📋 Transaction Details</h3>
                        <div class="info-row">
                            <span class="info-label">Service</span>
                            <span class="info-value">' . esc_html($service_name) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Reference Number</span>
                            <span class="info-value">' . esc_html($purchase->reference) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Amount Paid</span>
                            <span class="info-value" style="color: #10b981;">₦' . number_format($purchase->amount) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Transaction Date</span>
                            <span class="info-value">' . date('F j, Y') . '</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Transaction Time</span>
                            <span class="info-value">' . date('g:i A') . ' (WAT)</span>
                        </div>
                    </div>
                    
                    <div class="processing-box">
                        <div class="icon">⏳</div>
                        <h3>Your Request is Currently Being Processed</h3>
                        <p>Our team has received your request and is working on it. You will receive your document via email within the next <span class="highlight-time">24 hours</span>.</p>
                    </div>
                    
                    <div class="timeline">
                        <h4>📍 What Happens Next?</h4>
                        <div class="timeline-step completed">
                            <div class="step-icon">✓</div>
                            <div class="step-text"><strong>Payment Received</strong> - Your payment has been confirmed</div>
                        </div>
                        <div class="timeline-step completed">
                            <div class="step-icon">✓</div>
                            <div class="step-text"><strong>Details Submitted</strong> - Your information is now in our system</div>
                        </div>
                        <div class="timeline-step">
                            <div class="step-icon">3</div>
                            <div class="step-text"><strong>Processing</strong> - Our team is working on your request</div>
                        </div>
                        <div class="timeline-step">
                            <div class="step-icon">4</div>
                            <div class="step-text"><strong>Delivery</strong> - You\'ll receive your document via email</div>
                        </div>
                    </div>
                    
                    <p style="text-align: center; color: rgba(255,255,255,0.6); font-size: 13px; margin-top: 25px;">
                        Please keep this email for your records. If you have any questions about your request, please contact our support team with your reference number.
                    </p>
                </div>
                <div class="footer">
                    <p style="font-size: 14px; color: #ffffff; margin-bottom: 10px;">Thank you for choosing <strong>ZonaTech NG</strong>!</p>
                    <p>For support, contact us at <a href="mailto:' . esc_attr(ZONATECH_SUPPORT_EMAIL) . '">' . esc_html(ZONATECH_SUPPORT_EMAIL) . '</a></p>
                    <p style="margin-top: 15px; font-size: 11px;">© ' . date('Y') . ' ZonaTech NG. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ZonaTech NG <' . sanitize_email(ZONATECH_SUPPORT_EMAIL) . '>'
        );
        
        wp_mail($to, $subject_line, $message, $headers);
    }
    
    public function handle_webhook() {
        // Get and validate input
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            $this->log_webhook_error('Empty webhook payload');
            http_response_code(400);
            exit('Empty payload');
        }
        
        // Verify webhook signature
        if (!$this->verify_webhook_signature($input)) {
            $this->log_webhook_error('Invalid signature', array(
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ));
            http_response_code(400);
            exit('Invalid signature');
        }
        
        // Decode and validate JSON
        $event = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_webhook_error('Invalid JSON', array('error' => json_last_error_msg()));
            http_response_code(400);
            exit('Invalid JSON');
        }
        
        // Validate required fields
        if (!isset($event['event']) || !isset($event['data']['reference'])) {
            $this->log_webhook_error('Missing required fields', array('event' => $event));
            http_response_code(400);
            exit('Missing required fields');
        }
        
        if ($event['event'] === 'charge.success') {
            $reference = sanitize_text_field($event['data']['reference']);
            
            global $wpdb;
            $table_purchases = $wpdb->prefix . 'zonatech_purchases';
            
            $purchase = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_purchases WHERE reference = %s AND status = 'pending'",
                $reference
            ));
            
            if ($purchase) {
                $wpdb->update(
                    $table_purchases,
                    array('status' => 'completed'),
                    array('reference' => $reference)
                );
                
                $this->process_purchase($purchase);
                
                // Log successful webhook
                if (class_exists('ZonaTech_Activity_Log')) {
                    ZonaTech_Activity_Log::log(
                        $purchase->user_id,
                        'webhook_processed',
                        'Payment webhook processed successfully',
                        array('reference' => $reference)
                    );
                }
            }
        }
        
        http_response_code(200);
        exit('Webhook processed');
    }
    
    private function log_webhook_error($message, $data = array()) {
        // Log to WordPress error log
        error_log('ZonaTech Paystack Webhook Error: ' . $message . ' - ' . wp_json_encode($data));
    }
    
    private function verify_webhook_signature($input) {
        if (!isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'])) {
            $this->log_webhook_error('Missing signature header');
            return false;
        }
        
        if (empty($this->secret_key)) {
            $this->log_webhook_error('Secret key not configured');
            return false;
        }
        
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'];
        $computed = hash_hmac('sha512', $input, $this->secret_key);
        
        return hash_equals($signature, $computed);
    }
    
    private function get_item_name($payment_type, $meta_data) {
        switch ($payment_type) {
            case 'subscription':
                $category_names = array(
                    'science' => 'Science',
                    'arts' => 'Arts',
                    'business' => 'Business/Commercial'
                );
                $cat_name = $category_names[$meta_data['category'] ?? ''] ?? ucfirst($meta_data['category'] ?? '');
                $plan = isset($meta_data['plan']) && $meta_data['plan'] === 'sixmonth' ? '6-Month' : 'Monthly';
                return sprintf(
                    '%s %s Subscription (%s)',
                    strtoupper($meta_data['exam_type'] ?? ''),
                    $cat_name,
                    $plan
                );
            case 'category':
                $category_names = array(
                    'science' => 'Science',
                    'arts' => 'Arts',
                    'business' => 'Business/Commercial'
                );
                $cat_name = $category_names[$meta_data['category'] ?? ''] ?? ucfirst($meta_data['category'] ?? '');
                return sprintf(
                    '%s %s Subjects (All)',
                    strtoupper($meta_data['exam_type'] ?? ''),
                    $cat_name
                );
            case 'subject':
                return sprintf(
                    '%s %s Past Questions',
                    strtoupper($meta_data['exam_type'] ?? ''),
                    $meta_data['subject'] ?? ''
                );
            case 'scratch_card':
                return sprintf('%s Scratch Card/PIN', strtoupper($meta_data['card_type'] ?? ''));
            case 'nin_slip':
                return 'Premium NIN Slip';
            case 'nin_standard_slip':
                return 'Standard NIN Slip';
            case 'nin_slip_download':
                return 'NIN Slip Download';
            case 'nin_modification':
                return 'NIN Data Modification';
            case 'nin_dob_correction':
                return 'NIN Date of Birth Correction';
            case 'nin_verification':
                $slip_type = $meta_data['slip_type'] ?? 'regular';
                return 'NIN Verification (' . ucfirst($slip_type) . ' Slip)';
            case 'nin_validation':
                $validation_type = $meta_data['validation_type'] ?? '';
                return 'NIN Validation (' . ucwords(str_replace('_', ' ', $validation_type)) . ')';
            default:
                return 'ZonaTech Purchase';
        }
    }
    
    public static function get_user_purchases($user_id, $limit = 10) {
        global $wpdb;
        $table_purchases = $wpdb->prefix . 'zonatech_purchases';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_purchases WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ));
    }
}

// Initialize
ZonaTech_Paystack::get_instance();