<?php
/**
 * NIN Service Template - NIN Verification & Validation
 */

if (!defined('ABSPATH')) exit;
$is_guest = !is_user_logged_in();

// Prices
$validation_price = 2300;
$regular_slip_price = 280;
$standard_slip_price = 280;
$premium_slip_price = 300;
$vnin_slip_price = 300;
?>

<div class="zonatech-container">
    <div class="zonatech-wrapper">
        <!-- Header -->
        <div class="zonatech-header glass-effect">
            <div class="zonatech-logo">
                <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                <span>ZonaTech NG</span>
            </div>
            <nav class="zonatech-nav">
                <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
                <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
                <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>"><i class="fas fa-credit-card"></i> Scratch Cards</a>
                <a href="<?php echo site_url('/zonatech-nin-service/'); ?>" class="active"><i class="fas fa-id-card"></i> NIN Services</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </nav>
            
            <div class="hamburger-menu" id="hamburger-menu">
                <span></span><span></span><span></span>
            </div>
        </div>
        
        <div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>
        <nav class="mobile-nav" id="mobile-nav">
            <div class="mobile-nav-header">
                <div class="zonatech-logo"><img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img"><span>ZonaTech NG</span></div>
                <button class="mobile-nav-close" id="mobile-nav-close"><i class="fas fa-times"></i></button>
            </div>
            <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
            <a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book-open"></i> Past Questions</a>
            <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>"><i class="fas fa-credit-card"></i> Scratch Cards</a>
            <a href="<?php echo site_url('/zonatech-nin-service/'); ?>" class="active"><i class="fas fa-id-card"></i> NIN Services</a>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="<?php echo site_url('/zonatech-register/'); ?>"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </nav>
        
        <?php if ($is_guest): ?>
        <div class="glass-card glass-effect-purple" style="max-width: 700px; margin: 0 auto 2rem; text-align: center;">
            <div style="width: 80px; height: 80px; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; background: rgba(139, 92, 246, 0.2); border-radius: 50%; font-size: 2rem; color: var(--zona-purple-light);">
                <i class="fas fa-user-lock"></i>
            </div>
            <h3 class="text-white"><i class="fas fa-lock"></i> Login Required</h3>
            <p class="text-muted" style="margin-bottom: 1.5rem;">Create an account or login to access NIN services.</p>
            <div class="d-flex justify-center gap-2" style="flex-wrap: wrap;">
                <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create Account</a>
                <a href="<?php echo site_url('/zonatech-login/'); ?>" class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> Login</a>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mb-3">
            <h1 class="text-white" style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fas fa-id-card" style="color: #8b5cf6;"></i> NIN Services</h1>
            <p class="text-muted">Choose from our professional NIN services below</p>
        </div>
        
        <!-- Service Cards -->
        <div class="cards-grid" style="max-width: 800px; margin: 0 auto 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; <?php echo $is_guest ? 'opacity: 0.6; pointer-events: none;' : ''; ?>">
            
            <!-- NIN Verification Card -->
            <div class="service-card animate-card" onclick="showServiceForm('verification')" style="cursor: pointer;">
                <div class="service-card-icon" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1)); color: #22c55e;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="service-card-title">NIN Verification</h3>
                <p class="service-card-desc">Verify your NIN and download your NIN slip with multiple verification methods</p>
                <p class="service-card-price" style="color: #22c55e;">From ₦<?php echo number_format($regular_slip_price); ?></p>
                <button class="btn btn-primary btn-sm" style="margin-top: 1rem;"><i class="fas fa-arrow-right"></i> Get Started</button>
            </div>
            
            <!-- NIN Validation Card -->
            <div class="service-card animate-card" onclick="showServiceForm('validation')" style="cursor: pointer;">
                <div class="service-card-icon" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(59, 130, 246, 0.1)); color: #3b82f6;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="service-card-title">NIN Validation</h3>
                <p class="service-card-desc">Validate your NIN for SIM registration, banking, and other official purposes</p>
                <p class="service-card-price" style="color: #3b82f6;">₦<?php echo number_format($validation_price); ?></p>
                <button class="btn btn-primary btn-sm" style="margin-top: 1rem;"><i class="fas fa-arrow-right"></i> Get Started</button>
            </div>
        </div>
        
        <!-- Service Forms Container -->
        <div id="service-forms-container" style="display: none; max-width: 700px; margin: 0 auto;">
            
            <!-- NIN Verification Form -->
            <div id="form-verification" class="glass-card service-form" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 class="text-white" style="margin: 0;"><i class="fas fa-check-circle" style="color: #22c55e;"></i> NIN Verification</h3>
                    <button onclick="hideServiceForm()" class="btn btn-sm" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;"><i class="fas fa-times"></i> Close</button>
                </div>
                
                <form id="verification-form" onsubmit="submitNINVerification(event)">
                    <!-- Verification Method -->
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-search"></i> Preferred Verification Method *</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; margin-top: 0.5rem;">
                            <label class="radio-card" style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="verification_method" value="nin_number" checked style="margin-right: 10px;" onchange="updateVerificationFields()">
                                <span class="text-white" style="font-size: 0.9rem;"><i class="fas fa-id-badge"></i> NIN Number</span>
                            </label>
                            <label class="radio-card" style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="verification_method" value="phone_number" style="margin-right: 10px;" onchange="updateVerificationFields()">
                                <span class="text-white" style="font-size: 0.9rem;"><i class="fas fa-phone"></i> Phone Number</span>
                            </label>
                            <label class="radio-card" style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="verification_method" value="tracking_id" style="margin-right: 10px;" onchange="updateVerificationFields()">
                                <span class="text-white" style="font-size: 0.9rem;"><i class="fas fa-hashtag"></i> Tracking ID</span>
                            </label>
                            <label class="radio-card" style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="verification_method" value="demographic" style="margin-right: 10px;" onchange="updateVerificationFields()">
                                <span class="text-white" style="font-size: 0.9rem;"><i class="fas fa-user"></i> Demographic Info</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Slip Type -->
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-file-alt"></i> Select Slip Type *</label>
                        <select name="slip_type" id="slip_type" class="form-control" onchange="updateVerificationPrice()" required>
                            <option value="regular" data-price="<?php echo $regular_slip_price; ?>">Regular Slip - ₦<?php echo number_format($regular_slip_price); ?></option>
                            <option value="standard" data-price="<?php echo $standard_slip_price; ?>">Standard Slip - ₦<?php echo number_format($standard_slip_price); ?></option>
                            <option value="premium" data-price="<?php echo $premium_slip_price; ?>">Premium Slip - ₦<?php echo number_format($premium_slip_price); ?></option>
                            <option value="vnin" data-price="<?php echo $vnin_slip_price; ?>">VNIN Slip - ₦<?php echo number_format($vnin_slip_price); ?></option>
                        </select>
                    </div>
                    
                    <!-- NIN Number Fields (default) -->
                    <div id="nin-number-fields">
                        <div class="form-group">
                            <label class="text-white"><i class="fas fa-id-badge"></i> 11-Digit NIN Number *</label>
                            <input type="text" name="nin" class="form-control" placeholder="Enter your 11-digit NIN" maxlength="11" pattern="\d{11}">
                        </div>
                    </div>
                    
                    <!-- Phone Number Fields (hidden) -->
                    <div id="phone-number-fields" style="display: none;">
                        <div class="form-group">
                            <label class="text-white"><i class="fas fa-phone"></i> Phone Number (Nigerian Format) *</label>
                            <input type="tel" name="phone_nin" class="form-control" placeholder="e.g., 08012345678" maxlength="11" pattern="0[7-9][0-1]\d{8}">
                        </div>
                    </div>
                    
                    <!-- Tracking ID Fields (hidden) -->
                    <div id="tracking-id-fields" style="display: none;">
                        <div class="form-group">
                            <label class="text-white"><i class="fas fa-hashtag"></i> Tracking ID Number *</label>
                            <input type="text" name="tracking_id" class="form-control" placeholder="Enter your tracking ID">
                        </div>
                    </div>
                    
                    <!-- Demographic Fields (hidden) -->
                    <div id="demographic-fields" style="display: none;">
                        <div class="row" style="display: flex; gap: 1rem;">
                            <div class="form-group" style="flex: 1;">
                                <label class="text-white"><i class="fas fa-user"></i> First Name *</label>
                                <input type="text" name="first_name" class="form-control" placeholder="First name">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="text-white"><i class="fas fa-user"></i> Last Name *</label>
                                <input type="text" name="last_name" class="form-control" placeholder="Last name">
                            </div>
                        </div>
                        <div class="row" style="display: flex; gap: 1rem;">
                            <div class="form-group" style="flex: 1;">
                                <label class="text-white"><i class="fas fa-calendar"></i> Date of Birth *</label>
                                <input type="date" name="date_of_birth" class="form-control">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="text-white"><i class="fas fa-venus-mars"></i> Gender *</label>
                                <select name="gender" class="form-control">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" name="email" class="form-control" value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->user_email) : ''; ?>" required>
                    </div>
                    
                    <!-- Price Display -->
                    <div id="verification-price-display" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 10px; padding: 1rem; margin: 1.5rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="text-white"><strong>Service Fee:</strong></span>
                            <span id="verification-price" style="color: #22c55e; font-size: 1.5rem; font-weight: 700;">₦<?php echo number_format($regular_slip_price); ?></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;"><i class="fas fa-check-circle"></i> Verify NIN</button>
                </form>
            </div>
            
            <!-- NIN Validation Form -->
            <div id="form-validation" class="glass-card service-form" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 class="text-white" style="margin: 0;"><i class="fas fa-shield-alt" style="color: #3b82f6;"></i> NIN Validation</h3>
                    <button onclick="hideServiceForm()" class="btn btn-sm" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;"><i class="fas fa-times"></i> Close</button>
                </div>
                
                <form id="validation-form" onsubmit="submitNINValidation(event)">
                    <!-- Validation Type -->
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-list"></i> Select Validation Type *</label>
                        <div style="display: grid; gap: 0.5rem; margin-top: 0.5rem;">
                            <label class="radio-card" style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="validation_type" value="sim_validation" checked style="margin-right: 10px;">
                                <span class="text-white" style="font-size: 0.9rem;"><i class="fas fa-sim-card"></i> SIM Validation</span>
                            </label>
                            <label class="radio-card" style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="validation_type" value="vnin_validation" style="margin-right: 10px;">
                                <span class="text-white" style="font-size: 0.9rem;"><i class="fas fa-mobile-alt"></i> V.NIN Validation</span>
                            </label>
                            <label class="radio-card" style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="validation_type" value="update_records" style="margin-right: 10px;">
                                <span class="text-white" style="font-size: 0.9rem;"><i class="fas fa-sync-alt"></i> Update Records Validation</span>
                            </label>
                            <label class="radio-card" style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="validation_type" value="bank_validation" style="margin-right: 10px;">
                                <span class="text-white" style="font-size: 0.9rem;"><i class="fas fa-university"></i> Bank Validation</span>
                            </label>
                            <label class="radio-card" style="display: flex; align-items: center; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer;">
                                <input type="radio" name="validation_type" value="modification_validation" style="margin-right: 10px;">
                                <span class="text-white" style="font-size: 0.9rem;"><i class="fas fa-edit"></i> Modification Validation</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- NIN Number -->
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-id-badge"></i> 11-Digit NIN Number *</label>
                        <input type="text" name="nin" class="form-control" placeholder="Enter your 11-digit NIN" maxlength="11" pattern="\d{11}" required>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="form-group">
                        <label class="text-white"><i class="fas fa-envelope"></i> Email *</label>
                        <input type="email" name="email" class="form-control" value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->user_email) : ''; ?>" required>
                    </div>
                    
                    <!-- Disclaimer -->
                    <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 10px; padding: 1rem; margin: 1.5rem 0;">
                        <h4 style="color: #f59e0b; margin: 0 0 0.5rem 0; font-size: 0.95rem;"><i class="fas fa-exclamation-triangle"></i> Important Notice</h4>
                        <ul style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin: 0; padding-left: 1.2rem; line-height: 1.6;">
                            <li>This validation is valid for a <strong style="color: #f59e0b;">minimum of 1 hour</strong> and <strong style="color: #f59e0b;">not more than 48 hours</strong> from the time of issuance.</li>
                            <li><strong style="color: #ef4444;">No refunds</strong> will be processed for incorrect NIN entries. Please verify your NIN before submission.</li>
                            <li>The validation result is intended for official use and should be presented within the validity period.</li>
                            <li>For any issues, contact our support team immediately after receiving your validation.</li>
                        </ul>
                    </div>
                    
                    <!-- Price Display -->
                    <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 10px; padding: 1rem; margin: 1.5rem 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="text-white"><strong>Service Fee:</strong></span>
                            <span style="color: #3b82f6; font-size: 1.5rem; font-weight: 700;">₦<?php echo number_format($validation_price); ?></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;"><i class="fas fa-credit-card"></i> Pay & Validate NIN</button>
                </form>
            </div>
        </div>
        
        <!-- Features -->
        <div class="section" style="margin-top: 2rem;">
            <div class="section-header">
                <h2 class="text-white"><i class="fas fa-check-circle"></i> Why Choose Us?</h2>
            </div>
            <div class="cards-grid" style="max-width: 900px; margin: 0 auto;">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <div class="feature-content">
                        <h4 class="text-white">Fast Delivery</h4>
                        <p>Documents delivered within 24 hours via email</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="feature-content">
                        <h4 class="text-white">100% Secure</h4>
                        <p>Your data is encrypted and securely handled</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <div class="feature-content">
                        <h4 class="text-white">24/7 Support</h4>
                        <p>Get help via WhatsApp or email anytime</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="zonatech-footer">
            <div class="footer-content">
                <div class="footer-logo"><img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="footer-logo-img"><span>ZonaTech NG</span></div>
                <div class="footer-social">
                    <a href="https://wa.me/234<?php echo substr(ZONATECH_WHATSAPP_NUMBER, 1); ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    <a href="mailto:<?php echo ZONATECH_SUPPORT_EMAIL; ?>"><i class="fas fa-envelope"></i></a>
                </div>
                <p class="footer-copyright">© <?php echo date('Y'); ?> ZonaTech NG. All rights reserved.</p>
            </div>
        </footer>
    </div>
</div>

<!-- Success Modal -->
<div id="nin-success-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: linear-gradient(135deg, #1a1a2e, #16213e); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 20px; padding: 40px; max-width: 500px; text-align: center; margin: 20px;">
        <div style="width: 80px; height: 80px; background: rgba(34, 197, 94, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i class="fas fa-check-circle" style="font-size: 40px; color: #22c55e;"></i>
        </div>
        <h2 style="color: #ffffff; margin-bottom: 15px;">Request Submitted!</h2>
        <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px;">
            Your request has been received. You will receive your document via email within <strong style="color: #22c55e;">24 hours</strong>.
        </p>
        <button onclick="closeSuccessModal()" class="btn btn-primary" style="padding: 12px 40px;"><i class="fas fa-check"></i> Got It!</button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var hamburger = $('#hamburger-menu');
    var mobileNav = $('#mobile-nav');
    var mobileNavOverlay = $('#mobile-nav-overlay');
    var mobileNavClose = $('#mobile-nav-close');
    
    hamburger.on('click', function() {
        hamburger.toggleClass('active');
        mobileNav.toggleClass('active');
        mobileNavOverlay.toggleClass('active');
    });
    mobileNavClose.on('click', function() {
        hamburger.removeClass('active');
        mobileNav.removeClass('active');
        mobileNavOverlay.removeClass('active');
    });
    mobileNavOverlay.on('click', function() {
        hamburger.removeClass('active');
        mobileNav.removeClass('active');
        mobileNavOverlay.removeClass('active');
    });
});

function showServiceForm(serviceType) {
    document.querySelectorAll('.service-form').forEach(function(form) { form.style.display = 'none'; });
    document.getElementById('service-forms-container').style.display = 'block';
    document.getElementById('form-' + serviceType).style.display = 'block';
    document.getElementById('service-forms-container').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function hideServiceForm() {
    document.getElementById('service-forms-container').style.display = 'none';
}

function updateVerificationFields() {
    var method = document.querySelector('input[name="verification_method"]:checked').value;
    
    // Hide all field sections
    document.getElementById('nin-number-fields').style.display = 'none';
    document.getElementById('phone-number-fields').style.display = 'none';
    document.getElementById('tracking-id-fields').style.display = 'none';
    document.getElementById('demographic-fields').style.display = 'none';
    
    // Remove required from all optional inputs
    document.querySelectorAll('#nin-number-fields input, #phone-number-fields input, #tracking-id-fields input, #demographic-fields input, #demographic-fields select').forEach(function(input) {
        input.removeAttribute('required');
    });
    
    // Show and require appropriate fields
    if (method === 'nin_number') {
        document.getElementById('nin-number-fields').style.display = 'block';
        document.querySelector('#nin-number-fields input[name="nin"]').setAttribute('required', 'required');
    } else if (method === 'phone_number') {
        document.getElementById('phone-number-fields').style.display = 'block';
        document.querySelector('#phone-number-fields input[name="phone_nin"]').setAttribute('required', 'required');
    } else if (method === 'tracking_id') {
        document.getElementById('tracking-id-fields').style.display = 'block';
        document.querySelector('#tracking-id-fields input[name="tracking_id"]').setAttribute('required', 'required');
    } else if (method === 'demographic') {
        document.getElementById('demographic-fields').style.display = 'block';
        document.querySelectorAll('#demographic-fields input[name="first_name"], #demographic-fields input[name="last_name"], #demographic-fields input[name="date_of_birth"], #demographic-fields select[name="gender"]').forEach(function(input) {
            input.setAttribute('required', 'required');
        });
    }
}

function updateVerificationPrice() {
    var select = document.getElementById('slip_type');
    var selectedOption = select.options[select.selectedIndex];
    var price = parseInt(selectedOption.getAttribute('data-price'));
    document.getElementById('verification-price').textContent = '₦' + price.toLocaleString();
}

function submitNINVerification(event) {
    event.preventDefault();
    var form = event.target;
    var formData = new FormData(form);
    var metaData = {};
    formData.forEach(function(value, key) { metaData[key] = value; });
    
    var select = document.getElementById('slip_type');
    var selectedOption = select.options[select.selectedIndex];
    var amount = parseInt(selectedOption.getAttribute('data-price'));
    
    initiateNINPayment('nin_verification', amount, metaData);
}

function submitNINValidation(event) {
    event.preventDefault();
    var form = event.target;
    var formData = new FormData(form);
    var metaData = {};
    formData.forEach(function(value, key) { metaData[key] = value; });
    
    var amount = <?php echo $validation_price; ?>;
    
    initiateNINPayment('nin_validation', amount, metaData);
}

function initiateNINPayment(serviceType, amount, metaData) {
    if (typeof ZonaTechPayment !== 'undefined' && typeof ZonaTechPayment.initiatePayment === 'function') {
        ZonaTechPayment.initiatePayment(serviceType, amount, metaData);
    } else {
        jQuery.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_initialize_payment',
                nonce: zonatech_ajax.nonce,
                payment_type: serviceType,
                amount: amount,
                meta_data: JSON.stringify(metaData)
            },
            success: function(response) {
                if (response.success) {
                    var handler = PaystackPop.setup({
                        key: response.data.public_key,
                        email: response.data.email,
                        amount: response.data.amount,
                        currency: response.data.currency,
                        ref: response.data.reference,
                        metadata: response.data.metadata,
                        callback: function(r) { verifyNINPayment(r.reference); }
                    });
                    handler.openIframe();
                } else {
                    alert(response.data.message || 'Payment initialization failed');
                }
            }
        });
    }
}

function verifyNINPayment(reference) {
    jQuery.ajax({
        url: zonatech_ajax.ajax_url,
        type: 'POST',
        data: { action: 'zonatech_verify_payment', nonce: zonatech_ajax.nonce, reference: reference },
        success: function(response) {
            if (response.success) { showSuccessModal(); hideServiceForm(); }
            else { alert(response.data.message || 'Payment verification failed'); }
        }
    });
}

function showSuccessModal() { document.getElementById('nin-success-modal').style.display = 'flex'; }
function closeSuccessModal() { document.getElementById('nin-success-modal').style.display = 'none'; }

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateVerificationFields();
});
</script>