/**
 * Cart Service Recommendations
 * Fetches and displays Amelia service recommendations based on cart products
 */

(function($) {
    'use strict';
    
    var isInitialized = false;
    var recommendationsFetched = false;
    
    // Access ServiceStateManager from booking-popup.js (global scope)
    var ServiceStateManager = window.ServiceStateManager || null;
    
    // Cache recommendations to avoid refetching
    var cachedRecommendations = null;
    var cacheTimestamp = null;
    var CACHE_DURATION = 30000; // 30 seconds
    
    // Wait for cart drawer to be ready
    function initServiceRecommendations() {
        // Check if we have the cart drawer and WooCommerce
        if (!$('.cart-side-drawer').length) {
            // Drawer might not be in DOM yet, try again after a delay
            setTimeout(initServiceRecommendations, 100);
            return;
        }
        
        if (typeof wc_add_to_cart_params === 'undefined') {
            return;
        }
        
        isInitialized = true;
        
        // Enable cart icon immediately once drawer is found
        enableCartIcon();
        
        // Fetch recommendations on initial load if cart has items
        if ($('.cart-side-drawer .cart_list li').length > 0) {
            fetchAndDisplayRecommendations(false); // Don't show skeleton on initial load
            // Also update cart total from server to ensure it's correct
            updateCartTotalFromServer();
        }
        
        // Fetch recommendations when product is added
        $(document.body).on('added_to_cart', function(e) {
            recommendationsFetched = false; // Reset so it fetches fresh data
            cachedRecommendations = null; // Invalidate cache
            fetchAndDisplayRecommendations(false); // Fetch in background
        });
        
        // Fetch recommendations when cart is updated (item removed, quantity changed, etc.)
        $(document.body).on('wc_fragments_refreshed wc_fragments_loaded removed_from_cart', function(e) {
            recommendationsFetched = false; // Reset so it fetches fresh data
            cachedRecommendations = null; // Invalidate cache
            fetchAndDisplayRecommendations(false); // Fetch in background
            updateCartTotalFromServer();
        });
        
        // Watch for drawer to become active and fetch recommendations
        var drawerObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    var $drawer = $('.cart-side-drawer');
                    if ($drawer.hasClass('active')) {
                        // Check if we can use cached data
                        var now = Date.now();
                        if (cachedRecommendations && cacheTimestamp && (now - cacheTimestamp < CACHE_DURATION)) {
                            // Check if cached data has any recommendations after filtering
                            var filteredRecs = cachedRecommendations.filter(function(service) {
                                return !service.has_amelia_booking;
                            });
                            
                            if (filteredRecs.length > 0) {
                                // Show skeleton loader only if we have recommendations to show
                                showLoadingSkeleton();
                                // Use cached data for instant display
                                displayRecommendations(cachedRecommendations);
                            }
                        } else {
                            // Fetch fresh data first to determine if we should show skeleton
                            if (!recommendationsFetched) {
                                fetchAndDisplayRecommendations(true); // Pass true to potentially show skeleton after check
                            } else {
                                // Make sure recommendations are visible
                                if ($('.cart-service-recommendations').length === 0) {
                                    recommendationsFetched = false;
                                    fetchAndDisplayRecommendations(true);
                                }
                                // Always update cart total when drawer opens
                                updateCartTotalFromServer();
                            }
                        }
                    }
                }
            });
        });
        
        // Start observing the drawer for class changes
        var drawerElement = document.querySelector('.cart-side-drawer');
        if (drawerElement) {
            drawerObserver.observe(drawerElement, { attributes: true });
        }
    }
    
    function enableCartIcon() {
        $('.w-cart-link').removeClass('disabled').css({
            'pointer-events': '',
            'opacity': ''
        });
    }
    
    /**
     * Show loading skeleton while recommendations are being fetched
     */
    function showLoadingSkeleton() {
        // Don't show if recommendations already visible
        if ($('.cart-service-recommendations:not(.skeleton)').length > 0) {
            return;
        }
        
        // Remove any existing skeleton
        $('.cart-service-recommendations.skeleton').remove();
        
        var $productList = $('.cart-drawer-content ul.product_list_widget, .cart-drawer-content ul.cart_list');
        
        if (!$productList.length) {
            $productList = $('.cart-drawer-content .widget_shopping_cart_content');
            if (!$productList.length) {
                return;
            }
        }
        
        var html = '<div class="cart-service-recommendations skeleton">';
        html += '<div class="service-rec-header">';
        html += '<span class="service-rec-title">Need professional help?</span>';
        html += '<span class="service-rec-subtitle">We can help with installation</span>';
        html += '</div>';
        html += '<div class="service-rec-list">';
        html += '<div class="service-rec-item skeleton-item">';
        html += '<div class="service-rec-row">';
        html += '<span class="skeleton-text skeleton-text-lg"></span>';
        html += '<span class="skeleton-text skeleton-text-sm"></span>';
        html += '</div>';
        html += '<div class="service-rec-actions">';
        html += '<span class="skeleton-button"></span>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        $productList.after(html);
    }
    
    /**
     * Fetch current cart total from server and update display
     * This ensures the displayed total always matches server-side calculation
     */
    function updateCartTotalFromServer() {
        if (typeof cartServiceRecs === 'undefined') {
            return;
        }
        
        $.ajax({
            url: cartServiceRecs.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_current_cart_total',
                nonce: cartServiceRecs.nonce
            },
            success: function(response) {
                if (response.success && response.data.total_html) {
                    updateCartTotal(response.data.total_html);
                }
            }
        });
    }
    
    /**
     * Fetch service recommendations from server
     * @param {boolean} showSkeletonOnSuccess - Whether to show skeleton loader while fetching
     */
    function fetchAndDisplayRecommendations(showSkeletonOnSuccess) {
        // Check if cartServiceRecs is defined
        if (typeof cartServiceRecs === 'undefined') {
            return;
        }
        
        $.ajax({
            url: cartServiceRecs.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_cart_service_recommendations',
                nonce: cartServiceRecs.nonce
            },
            success: function(response) {
                if (response.success && response.data.recommendations) {
                    // Cache the results
                    cachedRecommendations = response.data.recommendations;
                    cacheTimestamp = Date.now();
                    
                    // Filter out services with Amelia bookings before showing skeleton
                    var filteredRecs = response.data.recommendations.filter(function(service) {
                        return !service.has_amelia_booking;
                    });
                    
                    // Only show skeleton if we have recommendations to display
                    if (showSkeletonOnSuccess && filteredRecs.length > 0) {
                        showLoadingSkeleton();
                    }
                    
                    displayRecommendations(response.data.recommendations);
                    recommendationsFetched = true;
                } else {
                    // Remove skeleton if no recommendations
                    $('.cart-service-recommendations.skeleton').remove();
                    recommendationsFetched = true;
                }
            },
            error: function(xhr, status, error) {
                // Remove skeleton on error
                $('.cart-service-recommendations.skeleton').remove();
                recommendationsFetched = true;
            }
        });
    }
    
    /**
     * Display service recommendations in cart drawer
     * Creates ONE header with all services listed below
     * Filters out services that already have Amelia bookings
     */
    function displayRecommendations(recommendations) {
        // Check if drawer is open - if not, wait a bit
        if (!$('.cart-side-drawer').hasClass('active')) {
            setTimeout(function() {
                displayRecommendations(recommendations);
            }, 300);
            return;
        }
        
        // Remove skeleton and any existing recommendations
        $('.cart-service-recommendations').remove();
        
        if (!recommendations || recommendations.length === 0) {
            return;
        }
        
        // Filter out services that have Amelia bookings (has_amelia_booking flag)
        recommendations = recommendations.filter(function(service) {
            return !service.has_amelia_booking;
        });
        
        // If no recommendations left after filtering, return
        if (recommendations.length === 0) {
            return;
        }
        
        // Find the product list in the cart drawer
        var $productList = $('.cart-drawer-content ul.product_list_widget, .cart-drawer-content ul.cart_list');
        
        if (!$productList.length) {
            // Try alternate selectors
            $productList = $('.cart-drawer-content .widget_shopping_cart_content');
            if (!$productList.length) {
                return;
            }
        }
        
        // Create recommendations HTML
        var html = '<div class="cart-service-recommendations">';
        html += '<div class="service-rec-header">';
        html += '<span class="service-rec-title">Need professional help?</span>';
        html += '<span class="service-rec-subtitle">We can help with installation</span>';
        html += '</div>';
        html += '<div class="service-rec-list">';
        
        // Show all recommended services (one per linked product)
        recommendations.forEach(function(service) {
            var price = service.price ? '$' + parseFloat(service.price).toFixed(2) : 'Free';
            var inCart = service.in_cart || false;
            var btnText = inCart ? 'Remove' : 'Add';
            var btnClass = inCart ? 'service-rec-add-link added' : 'service-rec-add-link';
            
            html += '<div class="service-rec-item" data-service-id="' + service.id + '" data-service-price="' + (service.price || 0) + '">';
            html += '<div class="service-rec-row">';
            html += '<span class="service-rec-name">' + escapeHtml(service.name) + '</span>';
            html += '<span class="service-rec-price">' + price + '</span>';
            html += '</div>';
            html += '<div class="service-rec-actions">';
            html += '<button class="' + btnClass + '" data-service-id="' + service.id + '">' + btnText + '</button>';
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        html += '</div>';
        
        // Insert after product list
        $productList.after(html);
        
        // Attach click handlers to add/remove buttons
        attachServiceButtonHandlers();
    }
    
    /**
     * Attach click handlers to service add/remove buttons
     */
    function attachServiceButtonHandlers() {
        $('.service-rec-add-link').off('click').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var serviceId = $btn.data('service-id');
            var isAdded = $btn.hasClass('added');
            
            if (isAdded) {
                removeServiceFromCart(serviceId, $btn);
            } else {
                addServiceToCart(serviceId, $btn);
            }
        });
    }
    
    /**
     * Add service to cart (store in session)
     */
    function addServiceToCart(serviceId, $btn) {
        // Get service price from data attribute
        var $serviceItem = $btn.closest('.service-rec-item');
        var servicePrice = parseFloat($serviceItem.data('service-price')) || 0;
        
        // Update button state and price immediately for perceived speed
        $btn.prop('disabled', true).text('...');
        updateCartTotalOptimistically(servicePrice, true);
        
        // Add loading state to recommendations section
        $('.cart-service-recommendations').addClass('loading');
        
        $.ajax({
            url: cartServiceRecs.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_amelia_service_to_cart',
                nonce: cartServiceRecs.nonce,
                service_id: serviceId
            },
            success: function(response) {
                if (response.success) {
                    // Update state manager
                    if (ServiceStateManager) {
                        ServiceStateManager.addService(serviceId);
                    }
                    
                    // Update button state
                    $btn.addClass('added').text('Remove').prop('disabled', false);
                    
                    // Remove loading state
                    $('.cart-service-recommendations').removeClass('loading');
                    
                    // Apply cart fragments if returned
                    if (response.data && response.data.fragments) {
                        $.each(response.data.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                    }
                    
                    // Trigger cart fragments refresh to update mini cart
                    $(document.body).trigger('wc_fragment_refresh');
                    
                    // Get accurate total from server after a brief delay
                    setTimeout(function() {
                        updateCartTotalFromServer();
                    }, 200);
                } else {
                    // Revert optimistic update on error
                    updateCartTotalOptimistically(servicePrice, false);
                    $('.cart-service-recommendations').removeClass('loading');
                    var errorMsg = (response.data && response.data.message) ? response.data.message : 'Failed to add service';
                    alert(errorMsg);
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                // Revert optimistic update on error
                updateCartTotalOptimistically(servicePrice, false);
                $('.cart-service-recommendations').removeClass('loading');
                alert('Error adding service to cart');
                $btn.prop('disabled', false).text('Add');
            }
        });
    }
    
    /**
     * Remove service from cart
     */
    function removeServiceFromCart(serviceId, $btn) {
        // Get service price from data attribute
        var $serviceItem = $btn.closest('.service-rec-item');
        var servicePrice = parseFloat($serviceItem.data('service-price')) || 0;
        
        // Update button state and price immediately for perceived speed
        $btn.prop('disabled', true).text('...');
        updateCartTotalOptimistically(servicePrice, false);
        
        // Add loading state to recommendations section
        $('.cart-service-recommendations').addClass('loading');
        
        $.ajax({
            url: cartServiceRecs.ajaxurl,
            type: 'POST',
            data: {
                action: 'remove_amelia_service_from_cart',
                nonce: cartServiceRecs.nonce,
                service_id: serviceId
            },
            success: function(response) {
                if (response.success) {
                    // Update state manager
                    if (ServiceStateManager) {
                        ServiceStateManager.removeService(serviceId);
                    }
                    
                    // Update button state
                    $btn.removeClass('added').text('Add').prop('disabled', false);
                    
                    // Remove loading state
                    $('.cart-service-recommendations').removeClass('loading');
                    
                    // Trigger cart fragments refresh to update mini cart
                    $(document.body).trigger('wc_fragment_refresh');
                    
                    // Get accurate total from server after a brief delay
                    setTimeout(function() {
                        updateCartTotalFromServer();
                    }, 200);
                } else {
                    // Revert optimistic update on error
                    updateCartTotalOptimistically(servicePrice, true);
                    $('.cart-service-recommendations').removeClass('loading');
                    var errorMsg = (response.data && response.data.message) ? response.data.message : 'Failed to remove service';
                    alert(errorMsg);
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                // Revert optimistic update on error
                updateCartTotalOptimistically(servicePrice, true);
                $('.cart-service-recommendations').removeClass('loading');
                alert('Error removing service from cart');
                $btn.prop('disabled', false).text('Remove');
            }
        });
    }
    
    /**
     * Optimistically update cart total (immediate feedback while waiting for server)
     */
    function updateCartTotalOptimistically(servicePrice, isAdding) {
        var selectors = [
            '.woocommerce-mini-cart__total .woocommerce-Price-amount bdi',
            '.woocommerce-mini-cart__total .amount bdi',
            '.cart-drawer-content .total .woocommerce-Price-amount bdi',
            '.cart-drawer-content .total .amount bdi',
            '.w-cart-dropdown .total .amount bdi'
        ];
        
        // Add loading indicator to total
        $('.woocommerce-mini-cart__total, .cart-drawer-content .total, .cart-drawer-content p.total').addClass('updating');
        
        for (var i = 0; i < selectors.length; i++) {
            var $element = $(selectors[i]);
            if ($element.length) {
                // Extract current price
                var currentText = $element.text();
                var currentPrice = parseFloat(currentText.replace(/[^0-9.]/g, ''));
                
                // Calculate new price
                var newPrice = isAdding ? currentPrice + servicePrice : currentPrice - servicePrice;
                
                // Update display with currency symbol
                var currencySymbol = currentText.match(/[^\d.,\s]+/)?.[0] || '$';
                $element.html(currencySymbol + newPrice.toFixed(2));
                break;
            }
        }
    }
    
    /**
     * Update cart total display without full refresh
     */
    function updateCartTotal(formattedTotal) {
        // Extract the price value from the HTML
        var $temp = $('<div>').html(formattedTotal);
        var priceText = $temp.find('bdi').text() || $temp.text();
        
        // Try multiple selectors to find the cart total
        var selectors = [
            '.woocommerce-mini-cart__total .woocommerce-Price-amount bdi',
            '.woocommerce-mini-cart__total .amount bdi',
            '.cart-drawer-content .total .woocommerce-Price-amount bdi',
            '.cart-drawer-content .total .amount bdi',
            '.w-cart-dropdown .total .amount bdi',
            '.cart_totals .order-total .woocommerce-Price-amount bdi'
        ];
        
        for (var i = 0; i < selectors.length; i++) {
            var $element = $(selectors[i]);
            if ($element.length) {
                $element.html(priceText);
                break;
            }
        }
        
        // Remove loading indicator
        $('.woocommerce-mini-cart__total, .cart-drawer-content .total, .cart-drawer-content p.total').removeClass('updating');
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Handle remove button click for services on cart page and mini cart
     */
    function handleCartPageServiceRemoval() {
        $(document).on('click', '.remove-amelia-service', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var serviceId = $link.data('service-id');
            var serviceName = $link.data('service-name');
            var $item = $link.closest('tr.amelia-service-item, li.amelia-service-item');
            
            if (!serviceId) {
                return;
            }
            
            // Determine if this is mini cart or cart page
            var isMiniCart = $item.is('li');
            
            // Add loading state
            $item.css('opacity', '0.5');
            $link.css('pointer-events', 'none');
            
            // Remove service via AJAX
            $.ajax({
                url: cartServiceRecs.ajaxurl,
                type: 'POST',
                data: {
                    action: 'remove_amelia_service_from_cart',
                    nonce: cartServiceRecs.nonce,
                    service_id: serviceId
                },
                success: function(response) {
                    if (response.success) {
                        // Update state manager
                        if (ServiceStateManager) {
                            ServiceStateManager.removeService(serviceId);
                        }
                        
                        // Apply cart fragments if returned
                        if (response.data && response.data.fragments) {
                            $.each(response.data.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                        }
                        
                        if (isMiniCart) {
                            // For mini cart, trigger fragment refresh
                            $(document.body).trigger('wc_fragment_refresh');
                            
                            // Remove the item with animation
                            $item.fadeOut(300, function() {
                                $item.remove();
                            });
                        } else {
                            // For cart page, trigger WooCommerce cart update
                            $(document.body).trigger('wc_update_cart');
                            
                            // Remove the row with animation
                            $item.fadeOut(300, function() {
                                $item.remove();
                            });
                        }
                    } else {
                        var errorMsg = (response.data && response.data.message) ? response.data.message : 'Failed to remove service';
                        alert(errorMsg);
                        $item.css('opacity', '1');
                        $link.css('pointer-events', '');
                    }
                },
                error: function() {
                    alert('Error removing service from cart');
                    $item.css('opacity', '1');
                    $link.css('pointer-events', '');
                }
            });
        });
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        initServiceRecommendations();
        handleCartPageServiceRemoval();
    });
    
})(jQuery);
