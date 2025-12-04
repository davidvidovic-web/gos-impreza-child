/**
 * Service State Manager
 * Manages Amelia service cart state across widgets
 * Shared utility for all service recommendation widgets
 */

(function($) {
    'use strict';
    
    /**
     * Centralized service state manager
     * Uses localStorage for cross-tab sync and custom events for same-page sync
     */
    var ServiceStateManager = {
        storageKey: 'amelia_cart_services_state',
        
        // Get current services in cart from localStorage
        getServicesInCart: function() {
            try {
                var data = localStorage.getItem(this.storageKey);
                return data ? JSON.parse(data) : [];
            } catch(e) {
                return [];
            }
        },
        
        // Check if specific service is in cart
        isServiceInCart: function(serviceId) {
            var services = this.getServicesInCart();
            return services.indexOf(parseInt(serviceId)) !== -1;
        },
        
        // Add service to state
        addService: function(serviceId) {
            var services = this.getServicesInCart();
            serviceId = parseInt(serviceId);
            
            if (services.indexOf(serviceId) === -1) {
                services.push(serviceId);
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify(services));
                    this.triggerStateChange();
                } catch(e) {
                    console.error('Failed to save to localStorage:', e);
                }
            }
        },
        
        // Remove service from state
        removeService: function(serviceId) {
            var services = this.getServicesInCart();
            serviceId = parseInt(serviceId);
            var index = services.indexOf(serviceId);
            
            if (index !== -1) {
                services.splice(index, 1);
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify(services));
                    this.triggerStateChange();
                } catch(e) {
                    console.error('Failed to save to localStorage:', e);
                }
            }
        },
        
        // Sync state from server (authoritative source)
        syncFromServer: function(callback) {
            $.ajax({
                url: cartServiceRecs.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_all_cart_services',
                    nonce: cartServiceRecs.nonce
                },
                success: function(response) {
                    if (response.success && response.data.service_ids) {
                        var serverServices = response.data.service_ids.map(function(id) {
                            return parseInt(id);
                        });
                        
                        try {
                            localStorage.setItem(ServiceStateManager.storageKey, JSON.stringify(serverServices));
                            ServiceStateManager.triggerStateChange();
                            
                            if (callback) callback(true);
                        } catch(e) {
                            console.error('Failed to sync to localStorage:', e);
                            if (callback) callback(false);
                        }
                    } else {
                        if (callback) callback(false);
                    }
                },
                error: function() {
                    if (callback) callback(false);
                }
            });
        },
        
        // Trigger custom event when state changes
        triggerStateChange: function() {
            $(document).trigger('ameliaServiceStateChanged', [this.getServicesInCart()]);
        }
    };
    
    // Expose globally for other widgets to use
    window.ServiceStateManager = ServiceStateManager;
    
    /**
     * Handle "Complete booking" links on cart/checkout pages
     */
    $(document).ready(function() {
        $(document).on('click', '.book-service-now', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var serviceId = $link.data('service-id');
            var serviceName = $link.data('service-name');
            
            if (serviceId && serviceName) {
                // Add loading spinner to link
                var originalHtml = $link.html();
                $link.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>').css('pointer-events', 'none');
                
                // Store service ID for Amelia
                if (typeof(Storage) !== "undefined") {
                    sessionStorage.setItem('amelia_preselect_service', serviceId);
                }
                
                // Get service-specific booking URL from admin settings
                $.ajax({
                    url: cartServiceRecs.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_service_booking_url',
                        service_id: serviceId,
                        nonce: cartServiceRecs.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.url) {
                            var url = response.data.url;
                            
                            // Add service parameter to URL
                            if (url.indexOf('?') > -1) {
                                url += '&ameliaService=' + serviceId;
                            } else {
                                url += '?ameliaService=' + serviceId;
                            }
                            
                            // Redirect to service-specific page
                            window.location.href = url;
                        } else {
                            // No booking page configured
                            $link.html(originalHtml).css('pointer-events', '');
                            
                            var errorMsg = (response.data && response.data.message) ? response.data.message : 'No booking page configured for this service. Please contact support.';
                            alert(errorMsg);
                        }
                    },
                    error: function() {
                        // Error fetching URL
                        $link.html(originalHtml).css('pointer-events', '');
                        alert('Error loading booking page. Please try again.');
                    }
                });
            }
        });
        
        // Sync from server on page load
        ServiceStateManager.syncFromServer();
        
        // Re-sync from server when cart fragments are updated
        $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
            ServiceStateManager.syncFromServer();
        });
    });
    
})(jQuery);
