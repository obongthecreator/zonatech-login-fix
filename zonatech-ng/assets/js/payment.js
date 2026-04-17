/**
 * ZonaTech NG - Payment JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        ZonaTechPayment.init();
    });
    
    const ZonaTechPayment = {
        init: function() {
            this.initPaymentButtons();
            this.initNINService();
            this.initScratchCards();
        },
        
        // Initialize payment buttons
        initPaymentButtons: function() {
            const self = this;
            
            // Buy subject access
            $(document).on('click', '#buy-subject-btn', function() {
                const examType = $(this).data('exam');
                const subject = $(this).data('subject');
                
                self.initiatePayment('subject', zonatech_ajax.subject_price, {
                    exam_type: examType,
                    subject: subject
                });
            });
            
            // Buy scratch card
            $(document).on('click', '.buy-scratch-card-btn', function() {
                const cardType = $(this).data('card-type');
                
                // Get price based on card type
                let price = zonatech_ajax.scratch_card_price;
                if (cardType === 'waec' && zonatech_ajax.waec_card_price) {
                    price = zonatech_ajax.waec_card_price;
                } else if (cardType === 'neco' && zonatech_ajax.neco_card_price) {
                    price = zonatech_ajax.neco_card_price;
                }
                
                self.initiatePayment('scratch_card', price, {
                    card_type: cardType
                });
            });
            
            // Buy NIN slip
            $(document).on('click', '#buy-nin-slip-btn', function() {
                const nin = $(this).data('nin');
                
                self.initiatePayment('nin_slip', zonatech_ajax.nin_price, {
                    nin_number: nin
                });
            });
        },
        
        // Initiate payment
        initiatePayment: function(paymentType, amount, metaData) {
            const self = this;
            
            // Check if Paystack is configured
            if (!zonatech_ajax.paystack_configured) {
                ZonaTechNotify.show('Payment system is not configured. Please contact support at ' + zonatech_ajax.support_email, 'error', 6000);
                return;
            }
            
            // Show loading
            ZonaTechNotify.show('Initializing payment...', 'info');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_initialize_payment',
                    nonce: zonatech_ajax.nonce,
                    payment_type: paymentType,
                    amount: amount,
                    meta_data: JSON.stringify(metaData)
                },
                success: function(response) {
                    if (response.success) {
                        self.openPaystack(response.data);
                    } else {
                        ZonaTechNotify.show(response.data.message || 'Payment initialization failed. Please try again.', 'error', 5000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Payment initialization error:', error);
                    ZonaTechNotify.show('Failed to initialize payment. Please check your connection and try again.', 'error', 5000);
                }
            });
        },
        
        // Open Paystack inline
        openPaystack: function(data) {
            const self = this;
            
            // Check if PaystackPop is available
            if (typeof PaystackPop === 'undefined') {
                ZonaTechNotify.show('Payment gateway is loading. Please wait and try again.', 'warning', 4000);
                return;
            }
            
            // Check if we have a valid public key
            if (!data.public_key || data.public_key.length < 10) {
                ZonaTechNotify.show('Payment configuration error. Please contact support.', 'error', 5000);
                return;
            }
            
            try {
                const handler = PaystackPop.setup({
                    key: data.public_key,
                    email: data.email,
                    amount: data.amount,
                    currency: data.currency,
                    ref: data.reference,
                    metadata: data.metadata,
                    onClose: function() {
                        ZonaTechNotify.show('Payment window closed.', 'info');
                    },
                    callback: function(response) {
                        self.verifyPayment(response.reference);
                    }
                });
                
                handler.openIframe();
            } catch (error) {
                console.error('Paystack error:', error);
                ZonaTechNotify.show('Failed to open payment window. Please try again.', 'error', 5000);
            }
        },
        
        // Verify payment
        verifyPayment: function(reference) {
            ZonaTechNotify.show('Verifying payment...', 'info');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_verify_payment',
                    nonce: zonatech_ajax.nonce,
                    reference: reference
                },
                success: function(response) {
                    if (response.success) {
                        ZonaTechNotify.show(response.data.message, 'success', 5000);
                        
                        // Redirect based on purchase type
                        setTimeout(function() {
                            if (response.data.purchase && response.data.purchase.type === 'scratch_card') {
                                // Redirect to scratch cards page to view purchased card
                                if ($('#user-cards-container').length) {
                                    window.location.reload();
                                } else {
                                    window.location.href = zonatech_ajax.site_url + '/zonatech-scratch-cards/';
                                }
                            } else if (response.data.purchase && (response.data.purchase.type === 'subscription' || response.data.purchase.type === 'category')) {
                                // Redirect to past questions page for subscription/category purchases
                                window.location.href = zonatech_ajax.site_url + '/zonatech-past-questions/';
                            } else if (response.data.purchase && response.data.purchase.type === 'subject') {
                                // Redirect to dashboard's My Subjects section
                                window.location.href = zonatech_ajax.site_url + '/zonatech-dashboard/#my-subjects';
                            } else {
                                window.location.reload();
                            }
                        }, 2000);
                    } else {
                        ZonaTechNotify.show(response.data.message, 'error');
                    }
                },
                error: function() {
                    ZonaTechNotify.show('Failed to verify payment. Please contact support.', 'error');
                }
            });
        },
        
        // NIN Service
        initNINService: function() {
            const self = this;
            
            // Verify NIN
            $('#verify-nin-btn').on('click', function() {
                const nin = $('#nin-input').val().trim();
                
                if (!nin) {
                    ZonaTechNotify.show('Please enter your NIN number.', 'warning');
                    return;
                }
                
                if (!/^\d{11}$/.test(nin)) {
                    ZonaTechNotify.show('NIN must be 11 digits.', 'error');
                    return;
                }
                
                const btn = $(this);
                const originalText = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
                
                $.ajax({
                    url: zonatech_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'zonatech_verify_nin',
                        nonce: zonatech_ajax.nonce,
                        nin: nin
                    },
                    success: function(response) {
                        btn.prop('disabled', false).html(originalText);
                        
                        if (response.success) {
                            self.showNINResult(response.data.data, nin);
                        } else {
                            ZonaTechNotify.show(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).html(originalText);
                        ZonaTechNotify.show('Verification failed. Please try again.', 'error');
                    }
                });
            });
        },
        
        // Show NIN verification result
        showNINResult: function(data, nin) {
            const resultContainer = $('#nin-result');
            
            resultContainer.html(`
                <div class="glass-card" style="margin-top: 1.5rem;">
                    <div class="d-flex align-center gap-2 mb-2">
                        <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                        <h4 style="margin: 0;">NIN Verified</h4>
                    </div>
                    <p><strong>NIN:</strong> ${data.nin}</p>
                    <p><strong>Status:</strong> <span class="badge badge-success">Verified</span></p>
                    <p class="text-muted">${data.note}</p>
                    <div class="mt-2">
                        <p style="font-size: 1.25rem; font-weight: 700; color: var(--zona-purple);">
                            ₦${zonatech_ajax.nin_price.toLocaleString()}
                        </p>
                        <button class="btn btn-primary" id="buy-nin-slip-btn" data-nin="${nin}">
                            <i class="fas fa-download"></i> Download Premium NIN Slip
                        </button>
                    </div>
                </div>
            `).show();
        },
        
        // Scratch Cards
        initScratchCards: function() {
            // Load user cards
            if ($('#user-cards-container').length) {
                this.loadUserCards();
            }
        },
        
        // Load user's purchased cards
        loadUserCards: function() {
            const container = $('#user-cards-container');
            container.html('<div class="loading"><div class="spinner"></div></div>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_get_user_cards',
                    nonce: zonatech_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.cards.length > 0) {
                        let html = '<div class="cards-grid">';
                        
                        response.data.cards.forEach(function(card) {
                            html += `
                                <div class="glass-card">
                                    <div class="d-flex justify-between align-center mb-2">
                                        <span class="badge badge-primary">${card.card_type.toUpperCase()}</span>
                                        <small class="text-muted">${new Date(card.sold_at).toLocaleDateString()}</small>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">PIN:</small>
                                        <p style="font-family: monospace; font-size: 1.1rem; margin: 0.25rem 0; word-break: break-all;">
                                            ${card.pin}
                                        </p>
                                    </div>
                                    <div>
                                        <small class="text-muted">Serial:</small>
                                        <p style="font-family: monospace; font-size: 0.9rem; margin: 0.25rem 0;">
                                            ${card.serial_number}
                                        </p>
                                    </div>
                                    <button class="btn btn-sm btn-secondary mt-2 copy-pin-btn" data-pin="${card.pin}">
                                        <i class="fas fa-copy"></i> Copy PIN
                                    </button>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        container.html(html);
                        
                        // Copy PIN handler
                        container.find('.copy-pin-btn').on('click', function() {
                            const pin = $(this).data('pin');
                            navigator.clipboard.writeText(pin).then(function() {
                                ZonaTechNotify.show('PIN copied to clipboard!', 'success');
                            });
                        });
                    } else {
                        container.html(`
                            <div class="empty-state">
                                <i class="fas fa-credit-card"></i>
                                <p>You haven't purchased any scratch cards yet.</p>
                            </div>
                        `);
                    }
                },
                error: function() {
                    container.html('<div class="alert alert-error">Failed to load cards. Please refresh.</div>');
                }
            });
        }
    };
    
    window.ZonaTechPayment = ZonaTechPayment;
    
})(jQuery);