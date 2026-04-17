/**
 * ZonaTech NG - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        ZonaTechAdmin.init();
    });
    
    const ZonaTechAdmin = {
        init: function() {
            this.initTabs();
            this.initConfirmations();
        },
        
        initTabs: function() {
            $('.zonatech-admin-tabs a').on('click', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');
                
                $('.zonatech-admin-tabs a').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.zonatech-tab-content').hide();
                $('#' + tab).show();
            });
        },
        
        initConfirmations: function() {
            $('[data-confirm]').on('click', function(e) {
                const message = $(this).data('confirm');
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        }
    };
    
    window.ZonaTechAdmin = ZonaTechAdmin;
    
})(jQuery);