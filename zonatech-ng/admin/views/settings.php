<?php
/**
 * Admin Settings Page
 * 
 * Allows admins to configure API keys and support contact information
 */

if (!defined('ABSPATH')) exit;

// Get current values
$whatsapp = get_option('zonatech_whatsapp_number', ZONATECH_WHATSAPP_NUMBER);
$support_email = get_option('zonatech_support_email', ZONATECH_SUPPORT_EMAIL);
?>
<div class="wrap zonatech-admin zonatech-settings-page">
    <h1><span class="dashicons dashicons-admin-settings"></span> ZonaTech NG Settings</h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php">
        <?php settings_fields('zonatech_settings'); ?>
        
        <!-- Paystack Integration -->
        <div class="zonatech-admin-section">
            <h2><span class="dashicons dashicons-money-alt"></span> Paystack Integration</h2>
            <p class="description">
                Get your API keys from <a href="https://dashboard.paystack.com/#/settings/developer" target="_blank">Paystack Dashboard</a>.
                Use Test keys for testing and Live keys for production.
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="zonatech_paystack_public_key">Public Key</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="zonatech_paystack_public_key" 
                               name="zonatech_paystack_public_key" 
                               value="<?php echo esc_attr(get_option('zonatech_paystack_public_key')); ?>" 
                               class="regular-text code"
                               placeholder="pk_test_xxxxx or pk_live_xxxxx">
                        <p class="description">Your Paystack public key (starts with pk_)</p>
                        <?php if (!empty(ZONATECH_PAYSTACK_PUBLIC_KEY)): ?>
                            <span class="status-indicator status-success">✓ Configured</span>
                        <?php else: ?>
                            <span class="status-indicator status-warning">⚠ Not configured</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_paystack_secret_key">Secret Key</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="zonatech_paystack_secret_key" 
                               name="zonatech_paystack_secret_key" 
                               value="<?php echo esc_attr(get_option('zonatech_paystack_secret_key')); ?>" 
                               class="regular-text code"
                               placeholder="sk_test_xxxxx or sk_live_xxxxx">
                        <p class="description">Your Paystack secret key (starts with sk_). Keep this secret!</p>
                        <?php if (!empty(ZONATECH_PAYSTACK_SECRET_KEY)): ?>
                            <span class="status-indicator status-success">✓ Configured</span>
                        <?php else: ?>
                            <span class="status-indicator status-warning">⚠ Not configured</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Webhook URL</th>
                    <td>
                        <code class="webhook-url"><?php echo esc_url(home_url('/wp-admin/admin-ajax.php?action=zonatech_paystack_webhook')); ?></code>
                        <button type="button" class="button button-small copy-webhook-btn" onclick="copyWebhookUrl()">
                            <span class="dashicons dashicons-clipboard" style="margin-top: 3px;"></span> Copy
                        </button>
                        <p class="description">Set this URL in your Paystack dashboard webhook settings.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Support Contact Info -->
        <div class="zonatech-admin-section">
            <h2><span class="dashicons dashicons-phone"></span> Support Contact Information</h2>
            <p class="description">Contact information displayed to users for support inquiries.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="zonatech_whatsapp_number">WhatsApp Number</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="zonatech_whatsapp_number" 
                               name="zonatech_whatsapp_number" 
                               value="<?php echo esc_attr($whatsapp); ?>" 
                               class="regular-text"
                               placeholder="e.g., 08012345678">
                        <p class="description">WhatsApp number for customer support (Nigerian format or with country code).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_support_email">Support Email</label>
                    </th>
                    <td>
                        <input type="email" 
                               id="zonatech_support_email" 
                               name="zonatech_support_email" 
                               value="<?php echo esc_attr($support_email); ?>" 
                               class="regular-text"
                               placeholder="e.g., support@zonatechng.com">
                        <p class="description">Email address for customer support inquiries.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button('Save Settings', 'primary', 'submit', true, array('id' => 'save-settings-btn')); ?>
    </form>
    
    <!-- Quick Links -->
    <div class="zonatech-admin-section">
        <h2><span class="dashicons dashicons-admin-links"></span> Quick Admin Links</h2>
        <div class="admin-quick-links">
            <a href="<?php echo admin_url('admin.php?page=zonatech-pricing'); ?>" class="quick-link-card">
                <span class="dashicons dashicons-money-alt"></span>
                <span>Manage Pricing</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=zonatech-questions'); ?>" class="quick-link-card">
                <span class="dashicons dashicons-book"></span>
                <span>Manage Questions</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=zonatech-cards'); ?>" class="quick-link-card">
                <span class="dashicons dashicons-tickets-alt"></span>
                <span>Scratch Cards</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=zonatech-users'); ?>" class="quick-link-card">
                <span class="dashicons dashicons-groups"></span>
                <span>Manage Users</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=zonatech-feedback'); ?>" class="quick-link-card">
                <span class="dashicons dashicons-feedback"></span>
                <span>View Feedback</span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=zonatech-activity'); ?>" class="quick-link-card">
                <span class="dashicons dashicons-clock"></span>
                <span>Activity Log</span>
            </a>
        </div>
    </div>
    
    <!-- Integration Guide -->
    <div class="zonatech-admin-section">
        <h2><span class="dashicons dashicons-info"></span> Integration Setup Guide</h2>
        
        <div class="integration-guide">
            <div class="guide-section">
                <h3><span class="dashicons dashicons-money-alt"></span> Paystack Setup</h3>
                <ol>
                    <li>Create an account at <a href="https://paystack.com" target="_blank">paystack.com</a></li>
                    <li>Go to Settings → API Keys & Webhooks</li>
                    <li>Copy your Public and Secret keys (use Test keys for testing)</li>
                    <li>Paste them in the fields above</li>
                    <li>Set up the webhook URL shown above in your Paystack dashboard</li>
                </ol>
            </div>
            
            <div class="guide-section">
                <h3><span class="dashicons dashicons-id"></span> NIN Verification (Advanced)</h3>
                <p>For real NIN verification, integrate with these providers:</p>
                <ul>
                    <li><a href="https://nimc.gov.ng" target="_blank">NIMC API</a> - Official government API</li>
                    <li><a href="https://dojah.io" target="_blank">Dojah</a> - Third-party provider</li>
                    <li><a href="https://prembly.com" target="_blank">Prembly (Identitypass)</a></li>
                    <li><a href="https://youverify.co" target="_blank">Youverify</a></li>
                </ul>
            </div>
            
            <div class="guide-section">
                <h3><span class="dashicons dashicons-tickets-alt"></span> Scratch Card Integration</h3>
                <p>For real scratch cards, partner with authorized resellers or integrate with:</p>
                <ul>
                    <li><a href="https://vtpass.com" target="_blank">VTpass</a></li>
                    <li><a href="https://baxi.ng" target="_blank">Baxi</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.zonatech-settings-page .zonatech-admin-section {
    background: #fff;
    padding: 20px 25px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.zonatech-settings-page .zonatech-admin-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #8b5cf6;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #1d2327;
}
.zonatech-settings-page .zonatech-admin-section h2 .dashicons {
    color: #8b5cf6;
}
.zonatech-settings-page .status-indicator {
    display: inline-block;
    margin-left: 10px;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.zonatech-settings-page .status-success {
    background: #d1fae5;
    color: #065f46;
}
.zonatech-settings-page .status-warning {
    background: #fef3c7;
    color: #92400e;
}
.zonatech-settings-page .webhook-url {
    display: inline-block;
    padding: 8px 12px;
    background: #f0f0f1;
    border-radius: 4px;
    font-size: 12px;
    word-break: break-all;
}
.zonatech-settings-page .copy-webhook-btn {
    margin-left: 10px;
    vertical-align: middle;
}
.zonatech-settings-page .admin-quick-links {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 15px;
    margin-top: 15px;
}
.zonatech-settings-page .quick-link-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px 15px;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    border-radius: 8px;
    text-decoration: none;
    color: #1d2327;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}
.zonatech-settings-page .quick-link-card:hover {
    border-color: #8b5cf6;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
}
.zonatech-settings-page .quick-link-card .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #8b5cf6;
    margin-bottom: 8px;
}
.zonatech-settings-page .quick-link-card span:last-child {
    font-size: 13px;
    text-align: center;
}
.zonatech-settings-page .integration-guide {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 15px;
}
.zonatech-settings-page .guide-section {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #8b5cf6;
}
.zonatech-settings-page .guide-section h3 {
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #1d2327;
}
.zonatech-settings-page .guide-section .dashicons {
    color: #8b5cf6;
}
.zonatech-settings-page .guide-section ol,
.zonatech-settings-page .guide-section ul {
    margin: 0;
    padding-left: 20px;
}
.zonatech-settings-page .guide-section li {
    padding: 5px 0;
    font-size: 13px;
}
@media (max-width: 1400px) {
    .zonatech-settings-page .admin-quick-links {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 1200px) {
    .zonatech-settings-page .integration-guide {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 768px) {
    .zonatech-settings-page .admin-quick-links {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function copyWebhookUrl() {
    var webhookUrl = document.querySelector('.webhook-url').textContent;
    navigator.clipboard.writeText(webhookUrl).then(function() {
        var btn = document.querySelector('.copy-webhook-btn');
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="dashicons dashicons-yes" style="margin-top: 3px;"></span> Copied!';
        setTimeout(function() {
            btn.innerHTML = originalHtml;
        }, 2000);
    }).catch(function(err) {
        alert('Failed to copy: ' + err);
    });
}
</script>