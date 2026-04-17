<?php
/**
 * Payment Template
 */

if (!defined('ABSPATH')) exit;
?>

<div class="zonatech-container">
    <div class="zonatech-wrapper">
        <div class="glass-card" style="max-width: 500px; margin: 3rem auto; text-align: center;">
            <i class="fas fa-credit-card" style="font-size: 3rem; color: var(--zona-purple); margin-bottom: 1rem;"></i>
            <h2 class="text-white"><i class="fas fa-shopping-cart"></i> Payment</h2>
            <p class="text-muted">Please complete your payment to continue.</p>
            
            <div id="payment-details" style="margin: 2rem 0;">
                <!-- Payment details will be injected here by JavaScript -->
            </div>
            
            <a href="<?php echo home_url('/zonatech-dashboard/'); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>