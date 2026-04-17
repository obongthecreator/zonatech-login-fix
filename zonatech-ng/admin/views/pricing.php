<?php
/**
 * Admin Pricing Management Page
 * 
 * Allows admins to change prices for all services
 */

if (!defined('ABSPATH')) exit;

// Get current prices (from options with fallback to constants)
$subject_price = ZonaTech_Admin::get_price('zonatech_subject_price', 'ZONATECH_SUBJECT_PRICE');
$monthly_price = ZonaTech_Admin::get_price('zonatech_monthly_price', 'ZONATECH_MONTHLY_PRICE');
$sixmonth_price = ZonaTech_Admin::get_price('zonatech_6month_price', 'ZONATECH_6MONTH_PRICE');
$free_questions_limit = ZonaTech_Admin::get_price('zonatech_free_questions_limit', 'ZONATECH_FREE_QUESTIONS_LIMIT');

$scratch_card_price = ZonaTech_Admin::get_price('zonatech_scratch_card_price', 'ZONATECH_SCRATCH_CARD_PRICE');
$waec_card_price = ZonaTech_Admin::get_price('zonatech_waec_card_price', 'ZONATECH_WAEC_CARD_PRICE');
$neco_card_price = ZonaTech_Admin::get_price('zonatech_neco_card_price', 'ZONATECH_NECO_CARD_PRICE');
$jamb_card_price = ZonaTech_Admin::get_price('zonatech_jamb_card_price', 'ZONATECH_SCRATCH_CARD_PRICE');

$nin_slip_price = ZonaTech_Admin::get_price('zonatech_nin_slip_price', 'ZONATECH_NIN_SLIP_PRICE');
$nin_standard_slip_price = ZonaTech_Admin::get_price('zonatech_nin_standard_slip_price', 'ZONATECH_NIN_STANDARD_SLIP_PRICE');
$nin_slip_download_price = ZonaTech_Admin::get_price('zonatech_nin_slip_download_price', 'ZONATECH_NIN_SLIP_DOWNLOAD_PRICE');
$nin_modification_price = ZonaTech_Admin::get_price('zonatech_nin_modification_price', 'ZONATECH_NIN_MODIFICATION_PRICE');
$nin_dob_correction_price = ZonaTech_Admin::get_price('zonatech_nin_dob_correction_price', 'ZONATECH_NIN_DOB_CORRECTION_PRICE');
?>
<div class="wrap zonatech-admin zonatech-pricing-page">
    <h1><span class="dashicons dashicons-money-alt"></span> Pricing Management</h1>
    <p class="description">Manage prices for all ZonaTech NG services. Changes take effect immediately.</p>
    
    <div id="pricing-message" style="display: none;"></div>
    
    <form id="zonatech-pricing-form" method="post">
        <?php wp_nonce_field('zonatech_nonce', 'pricing_nonce'); ?>
        
        <!-- Past Questions & Subscriptions -->
        <div class="zonatech-admin-section">
            <h2><span class="dashicons dashicons-book"></span> Past Questions & Subscriptions</h2>
            <p class="description">Pricing for past questions access and subscription plans.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="zonatech_subject_price">Single Subject Price</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_subject_price" 
                                   name="zonatech_subject_price" 
                                   value="<?php echo esc_attr($subject_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="100">
                        </div>
                        <p class="description">Price per subject for past questions access.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_monthly_price">Monthly Subscription</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_monthly_price" 
                                   name="zonatech_monthly_price" 
                                   value="<?php echo esc_attr($monthly_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="100">
                        </div>
                        <p class="description">Monthly subscription price for unlimited access.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_6month_price">6-Month Subscription</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_6month_price" 
                                   name="zonatech_6month_price" 
                                   value="<?php echo esc_attr($sixmonth_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="100">
                        </div>
                        <p class="description">6-month subscription price for unlimited access.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_free_questions_limit">Free Questions Limit</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="zonatech_free_questions_limit" 
                               name="zonatech_free_questions_limit" 
                               value="<?php echo esc_attr($free_questions_limit); ?>" 
                               class="regular-text"
                               min="0"
                               step="1">
                        <p class="description">Number of free questions before payment is required.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Scratch Cards -->
        <div class="zonatech-admin-section">
            <h2><span class="dashicons dashicons-tickets-alt"></span> Scratch Cards</h2>
            <p class="description">Pricing for examination scratch cards (WAEC, NECO, JAMB).</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="zonatech_waec_card_price">WAEC Scratch Card</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_waec_card_price" 
                                   name="zonatech_waec_card_price" 
                                   value="<?php echo esc_attr($waec_card_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="50">
                        </div>
                        <p class="description">Price for WAEC result checker scratch card.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_neco_card_price">NECO Scratch Card</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_neco_card_price" 
                                   name="zonatech_neco_card_price" 
                                   value="<?php echo esc_attr($neco_card_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="50">
                        </div>
                        <p class="description">Price for NECO result checker scratch card.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_jamb_card_price">JAMB Scratch Card</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_jamb_card_price" 
                                   name="zonatech_jamb_card_price" 
                                   value="<?php echo esc_attr($jamb_card_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="50">
                        </div>
                        <p class="description">Price for JAMB result checker scratch card.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_scratch_card_price">Default Card Price</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_scratch_card_price" 
                                   name="zonatech_scratch_card_price" 
                                   value="<?php echo esc_attr($scratch_card_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="50">
                        </div>
                        <p class="description">Default price for other scratch card types.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- NIN Services -->
        <div class="zonatech-admin-section">
            <h2><span class="dashicons dashicons-id"></span> NIN Services</h2>
            <p class="description">Pricing for National Identification Number (NIN) services.</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="zonatech_nin_slip_price">Premium NIN Slip</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_nin_slip_price" 
                                   name="zonatech_nin_slip_price" 
                                   value="<?php echo esc_attr($nin_slip_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="100">
                        </div>
                        <p class="description">Premium NIN slip with full details.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_nin_standard_slip_price">Standard NIN Slip</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_nin_standard_slip_price" 
                                   name="zonatech_nin_standard_slip_price" 
                                   value="<?php echo esc_attr($nin_standard_slip_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="100">
                        </div>
                        <p class="description">Standard NIN slip with basic details.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_nin_slip_download_price">NIN Slip Download</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_nin_slip_download_price" 
                                   name="zonatech_nin_slip_download_price" 
                                   value="<?php echo esc_attr($nin_slip_download_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="100">
                        </div>
                        <p class="description">Direct NIN slip download service.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_nin_modification_price">NIN Data Modification</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_nin_modification_price" 
                                   name="zonatech_nin_modification_price" 
                                   value="<?php echo esc_attr($nin_modification_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="100">
                        </div>
                        <p class="description">NIN data modification/update service.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="zonatech_nin_dob_correction_price">Date of Birth Correction</label>
                    </th>
                    <td>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₦</span>
                            <input type="number" 
                                   id="zonatech_nin_dob_correction_price" 
                                   name="zonatech_nin_dob_correction_price" 
                                   value="<?php echo esc_attr($nin_dob_correction_price); ?>" 
                                   class="regular-text"
                                   min="0"
                                   step="100">
                        </div>
                        <p class="description">Date of birth correction service.</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <p class="submit">
            <button type="submit" class="button button-primary button-hero" id="save-pricing-btn">
                <span class="dashicons dashicons-saved" style="margin-top: 4px;"></span>
                Save All Prices
            </button>
            <span class="spinner" id="pricing-spinner" style="float: none; margin-left: 10px;"></span>
        </p>
    </form>
    
    <!-- Pricing Summary -->
    <div class="zonatech-admin-section">
        <h2><span class="dashicons dashicons-chart-pie"></span> Current Pricing Summary</h2>
        <div class="pricing-summary-grid">
            <div class="pricing-summary-card">
                <h3><span class="dashicons dashicons-book"></span> Past Questions</h3>
                <ul>
                    <li><strong>Per Subject:</strong> ₦<?php echo number_format($subject_price); ?></li>
                    <li><strong>Monthly:</strong> ₦<?php echo number_format($monthly_price); ?></li>
                    <li><strong>6 Months:</strong> ₦<?php echo number_format($sixmonth_price); ?></li>
                    <li><strong>Free Questions:</strong> <?php echo $free_questions_limit; ?></li>
                </ul>
            </div>
            <div class="pricing-summary-card">
                <h3><span class="dashicons dashicons-tickets-alt"></span> Scratch Cards</h3>
                <ul>
                    <li><strong>WAEC:</strong> ₦<?php echo number_format($waec_card_price); ?></li>
                    <li><strong>NECO:</strong> ₦<?php echo number_format($neco_card_price); ?></li>
                    <li><strong>JAMB:</strong> ₦<?php echo number_format($jamb_card_price); ?></li>
                </ul>
            </div>
            <div class="pricing-summary-card">
                <h3><span class="dashicons dashicons-id"></span> NIN Services</h3>
                <ul>
                    <li><strong>Premium Slip:</strong> ₦<?php echo number_format($nin_slip_price); ?></li>
                    <li><strong>Standard Slip:</strong> ₦<?php echo number_format($nin_standard_slip_price); ?></li>
                    <li><strong>Download:</strong> ₦<?php echo number_format($nin_slip_download_price); ?></li>
                    <li><strong>Modification:</strong> ₦<?php echo number_format($nin_modification_price); ?></li>
                    <li><strong>DOB Correction:</strong> ₦<?php echo number_format($nin_dob_correction_price); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.zonatech-pricing-page .zonatech-admin-section {
    background: #fff;
    padding: 20px 25px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.zonatech-pricing-page .zonatech-admin-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #8b5cf6;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #1d2327;
}
.zonatech-pricing-page .zonatech-admin-section h2 .dashicons {
    color: #8b5cf6;
}
.zonatech-pricing-page .price-input-wrapper {
    display: flex;
    align-items: center;
    gap: 5px;
}
.zonatech-pricing-page .currency-symbol {
    font-size: 16px;
    font-weight: bold;
    color: #1d2327;
    background: #f0f0f1;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-right: none;
    border-radius: 4px 0 0 4px;
}
.zonatech-pricing-page .price-input-wrapper input {
    border-radius: 0 4px 4px 0 !important;
    max-width: 150px;
}
.zonatech-pricing-page .form-table th {
    width: 200px;
    padding: 20px 10px 20px 0;
}
.zonatech-pricing-page .form-table td {
    padding: 15px 10px;
}
.zonatech-pricing-page .pricing-summary-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 15px;
}
.zonatech-pricing-page .pricing-summary-card {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #8b5cf6;
}
.zonatech-pricing-page .pricing-summary-card h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
    display: flex;
    align-items: center;
    gap: 8px;
}
.zonatech-pricing-page .pricing-summary-card .dashicons {
    color: #8b5cf6;
}
.zonatech-pricing-page .pricing-summary-card ul {
    margin: 0;
    padding: 0;
    list-style: none;
}
.zonatech-pricing-page .pricing-summary-card li {
    padding: 5px 0;
    border-bottom: 1px solid rgba(139, 92, 246, 0.2);
    font-size: 14px;
}
.zonatech-pricing-page .pricing-summary-card li:last-child {
    border-bottom: none;
}
.zonatech-pricing-page .button-hero {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 12px 25px !important;
    font-size: 15px !important;
}
#pricing-message {
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}
#pricing-message.success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}
#pricing-message.error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}
.spin-icon {
    margin-top: 4px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
@media (max-width: 1200px) {
    .zonatech-pricing-page .pricing-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 768px) {
    .zonatech-pricing-page .pricing-summary-grid {
        grid-template-columns: 1fr;
    }
    .zonatech-pricing-page .form-table th {
        width: 150px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#zonatech-pricing-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $('#save-pricing-btn');
        var $spinner = $('#pricing-spinner');
        var $message = $('#pricing-message');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin-icon"></span> Saving...');
        $spinner.addClass('is-active');
        $message.hide();
        
        var formData = {
            action: 'zonatech_save_pricing',
            nonce: zonatech_admin.nonce
        };
        
        // Collect all pricing fields
        $form.find('input[name^="zonatech_"]').each(function() {
            formData[$(this).attr('name')] = $(this).val();
        });
        
        $.ajax({
            url: zonatech_admin.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    $message.removeClass('error').addClass('success')
                        .html('<strong>Success!</strong> ' + response.data.message)
                        .fadeIn();
                    
                    // Scroll to top to show message
                    $('html, body').animate({ scrollTop: 0 }, 300);
                    
                    // Reload page after 1.5 seconds to update summary
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $message.removeClass('success').addClass('error')
                        .html('<strong>Error!</strong> ' + (response.data.message || 'Failed to save pricing.'))
                        .fadeIn();
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error saving pricing:', status, error);
                $message.removeClass('success').addClass('error')
                    .html('<strong>Error!</strong> Failed to save pricing. Please try again.')
                    .fadeIn();
                $btn.prop('disabled', false).html(originalText);
            },
            complete: function() {
                $spinner.removeClass('is-active');
            }
        });
    });
});
</script>