/**
 * Product Service Recommendations JavaScript
 * Handles add/remove service from cart on product pages
 */

(function($) {
    'use strict';
    
    // Access ServiceStateManager from global scope
    var ServiceStateManager = window.ServiceStateManager || null;
    
    /**
     * Helper to reset button text properly
     */
    function resetButtonText($btn, text) {
        $btn.html(text).prop('disabled', false);
    }
    
    /**
     * Update product page button state (Add/Remove)
     */
    function updateProductPageButton(serviceId, isAdded) {
        var $productBtn = $('.service-rec-add-link[data-service-id="' + serviceId + '"]');
        
        if ($productBtn.length) {
            if (isAdded) {
                $productBtn.addClass('added');
                resetButtonText($productBtn, 'Remove');
            } else {
                $productBtn.removeClass('added');
                resetButtonText($productBtn, 'Add');
            }
        }
    }
    
    /**
     * Add service to cart via AJAX
     */
    function addServiceToCart(serviceId, serviceName, $btn) {
        // Add spinner
        $btn.html('<span class="service-loading-spinner"></span>').prop('disabled', true);
        
        $.ajax({
            url: cartServiceRecs.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_amelia_service_to_cart',
                service_id: serviceId,
                service_name: serviceName,
                nonce: cartServiceRecs.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update local state
                    if (ServiceStateManager) {
                        ServiceStateManager.addService(serviceId);
                    }
                    
                    // Update button state
                    updateProductPageButton(serviceId, true);
                    
                    // Trigger WooCommerce fragments refresh to update cart
                    $(document.body).trigger('wc_fragment_refresh');
                    
                    // Open cart drawer after brief delay
                    setTimeout(function() {
                        $('.w-cart-link').trigger('click');
                    }, 300);
                } else {
                    resetButtonText($btn, 'Add');
                    alert(response.data.message || 'Failed to add service to cart');
                }
            },
            error: function() {
                resetButtonText($btn, 'Add');
                alert('Error adding service to cart. Please try again.');
            }
        });
    }
    
    /**
     * Remove service from cart via AJAX
     */
    function removeServiceFromCart(serviceId, serviceName, $btn) {
        // Add spinner
        $btn.html('<span class="service-loading-spinner"></span>').prop('disabled', true);
        
        $.ajax({
            url: cartServiceRecs.ajaxurl,
            type: 'POST',
            data: {
                action: 'remove_amelia_service_from_cart',
                service_id: serviceId,
                nonce: cartServiceRecs.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update local state
                    if (ServiceStateManager) {
                        ServiceStateManager.removeService(serviceId);
                    }
                    
                    // Update button state
                    resetButtonText($btn, 'Add');
                    updateProductPageButton(serviceId, false);
                    
                    // Trigger WooCommerce fragments refresh
                    $(document.body).trigger('wc_fragment_refresh');
                } else {
                    resetButtonText($btn, 'Remove');
                    alert(response.data.message || 'Failed to remove service from cart');
                }
            },
            error: function() {
                resetButtonText($btn, 'Remove');
                alert('Error removing service from cart. Please try again.');
            }
        });
    }
    
    /**
     * Check if service has Amelia booking in cart
     */
    function checkAmeliaBookingInCart(serviceId, callback) {
        $.ajax({
            url: cartServiceRecs.ajaxurl,
            type: 'POST',
            data: {
                action: 'check_amelia_booking_in_cart',
                service_id: serviceId,
                nonce: cartServiceRecs.nonce
            },
            success: function(response) {
                if (response.success && callback) {
                    callback(response.data.has_booking);
                }
            }
        });
    }
    
    /**
     * Check product service in cart on page load
     * Hide the recommendation if Amelia booking exists
     */
    function checkProductServiceInCart() {
        var $btn = $('.service-rec-add-link');
        var $recommendation = $('.product-service-recommendation');
        
        if (!$btn.length || !$recommendation.length) {
            return;
        }
        
        var serviceId = $btn.data('service-id');
        
        if (!serviceId) {
            return;
        }
        
        // First check if Amelia booking exists - if so, hide the entire recommendation
        checkAmeliaBookingInCart(serviceId, function(hasAmeliaBooking) {
            if (hasAmeliaBooking) {
                // Hide the entire recommendation section
                $recommendation.hide();
                return;
            }
            
            // Otherwise check localStorage for custom service state
            if (ServiceStateManager && ServiceStateManager.isServiceInCart(serviceId)) {
                $btn.addClass('added');
                resetButtonText($btn, 'Remove');
            } else {
                $btn.removeClass('added');
                resetButtonText($btn, 'Add');
            }
        });
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Handle Add/Remove button clicks
        $(document).on('click', '.service-rec-add-link', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var serviceId = $btn.data('service-id');
            var serviceName = $btn.data('service-name');
            var isAdded = $btn.hasClass('added');
            
            if (!serviceId || !serviceName) {
                console.error('Missing service ID or name');
                return;
            }
            
            if (isAdded) {
                // Remove from cart
                removeServiceFromCart(serviceId, serviceName, $btn);
            } else {
                // Add to cart
                addServiceToCart(serviceId, serviceName, $btn);
            }
        });
        
        // Check initial state when ServiceStateManager is ready
        if (ServiceStateManager) {
            ServiceStateManager.syncFromServer(function() {
                checkProductServiceInCart();
            });
        } else {
            // ServiceStateManager not loaded yet, try again
            setTimeout(function() {
                ServiceStateManager = window.ServiceStateManager;
                if (ServiceStateManager) {
                    ServiceStateManager.syncFromServer(function() {
                        checkProductServiceInCart();
                    });
                }
            }, 500);
        }
        
        // Listen for state changes
        $(document).on('ameliaServiceStateChanged', function() {
            checkProductServiceInCart();
        });
        
        // Re-check when cart fragments are updated (e.g., after Amelia booking is added)
        $(document.body).on('wc_fragments_refreshed wc_fragments_loaded updated_cart_totals', function() {
            if (ServiceStateManager) {
                ServiceStateManager.syncFromServer(function() {
                    checkProductServiceInCart();
                });
            } else {
                // Fallback if ServiceStateManager not available
                checkProductServiceInCart();
            }
        });
        
        // Also re-check when drawer is opened/closed
        $(document).on('ameliaServiceStateChanged', function() {
            checkProductServiceInCart();
        });
    });
    
})(jQuery);
