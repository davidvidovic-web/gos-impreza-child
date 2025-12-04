/**
 * Cart Display Enhancements - Collapsible Sections
 * 
 * Handles toggle functionality for Service Details and Selected Add-ons
 * in the WooCommerce cart, checkout, and minicart.
 * 
 * Moved from gos-service-location-check plugin to Impreza-child theme.
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        initCollapsibleSections();
    });

    // Also reinitialize on cart updates
    $(document.body).on('updated_cart_totals updated_checkout wc_fragments_refreshed', function() {
        initCollapsibleSections();
    });

    function initCollapsibleSections() {
        // Remove existing event handlers to prevent duplicates
        $('.gos-group-title').off('click.gosCollapse');
        
        // Add click handler
        $('.gos-group-title').on('click.gosCollapse', function() {
            const $title = $(this);
            // Find the ul within the same parent container
            const $list = $title.parent().find('ul.gos-field-list, ul.gos-addon-list');
            
            // Toggle collapsed class on both elements
            $title.toggleClass('collapsed');
            $list.toggleClass('collapsed');
        });
    }

})(jQuery);
