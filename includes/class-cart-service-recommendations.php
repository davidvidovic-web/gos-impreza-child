<?php
/**
 * Cart Service Recommendations
 * Recommends Amelia services based on products in WooCommerce cart
 */

if (!defined('ABSPATH')) {
    exit;
}

class Cart_Service_Recommendations {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'amelia_services';
        
        // AJAX handler for getting recommendations
        add_action('wp_ajax_get_cart_service_recommendations', array($this, 'ajax_get_recommendations'));
        add_action('wp_ajax_nopriv_get_cart_service_recommendations', array($this, 'ajax_get_recommendations'));
        
        // AJAX handlers for adding/removing services
        add_action('wp_ajax_add_amelia_service_to_cart', array($this, 'ajax_add_service_to_cart'));
        add_action('wp_ajax_nopriv_add_amelia_service_to_cart', array($this, 'ajax_add_service_to_cart'));
        add_action('wp_ajax_remove_amelia_service_from_cart', array($this, 'ajax_remove_service_from_cart'));
        add_action('wp_ajax_nopriv_remove_amelia_service_from_cart', array($this, 'ajax_remove_service_from_cart'));
        add_action('wp_ajax_get_current_cart_total', array($this, 'ajax_get_current_cart_total'));
        add_action('wp_ajax_nopriv_get_current_cart_total', array($this, 'ajax_get_current_cart_total'));
        
        // AJAX handler for checking if service is in cart
        add_action('wp_ajax_check_service_in_cart', array($this, 'ajax_check_service_in_cart'));
        add_action('wp_ajax_nopriv_check_service_in_cart', array($this, 'ajax_check_service_in_cart'));
        
        // AJAX handler for checking if Amelia booking exists in cart
        add_action('wp_ajax_check_amelia_booking_in_cart', array($this, 'ajax_check_amelia_booking_in_cart'));
        add_action('wp_ajax_nopriv_check_amelia_booking_in_cart', array($this, 'ajax_check_amelia_booking_in_cart'));
        
        // AJAX handler for getting all cart service IDs
        add_action('wp_ajax_get_all_cart_services', array($this, 'ajax_get_all_cart_services'));
        add_action('wp_ajax_nopriv_get_all_cart_services', array($this, 'ajax_get_all_cart_services'));
        
        // AJAX handler for getting service booking URL
        add_action('wp_ajax_get_service_booking_url', array($this, 'ajax_get_service_booking_url'));
        add_action('wp_ajax_nopriv_get_service_booking_url', array($this, 'ajax_get_service_booking_url'));
        
        // Note: Services are displayed as line items (not fees) to avoid showing twice
        // They appear in the products list with their prices included in subtotal
        
        // Display services as line items in cart/checkout
        add_action('woocommerce_review_order_after_cart_contents', array($this, 'display_services_in_checkout'));
        add_action('woocommerce_cart_contents', array($this, 'display_services_in_cart'));
        
        // Display services in mini cart drawer
        add_action('woocommerce_mini_cart_contents', array($this, 'display_services_in_mini_cart'));
        
        // Store service ID when redirecting to Amelia booking page
        add_action('template_redirect', array($this, 'track_amelia_booking_redirect'));
        
        // Remove service from cart when Amelia booking is completed
        add_action('woocommerce_before_calculate_totals', array($this, 'remove_service_after_amelia_booking'), 999);
        
        // Also remove immediately when Amelia adds to cart
        add_action('woocommerce_add_to_cart', array($this, 'remove_service_on_amelia_add'), 10, 6);
        
        // Adjust cart subtotal to include service prices on cart page
        add_filter('woocommerce_cart_subtotal', array($this, 'adjust_cart_subtotal'), 10, 3);
        
        // Adjust cart total to include service prices
        add_filter('woocommerce_calculated_total', array($this, 'adjust_cart_total'), 10, 2);
        
        // Ensure cart totals include services when fragments load
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'ensure_cart_totals_updated'), 99);
        
        // Force cart recalculation when cart is loaded from session
        add_action('woocommerce_cart_loaded_from_session', array($this, 'recalculate_cart_on_load'), 99);
        
        // Prevent cart hash from being cached when services are present
        add_filter('woocommerce_cart_hash', array($this, 'modify_cart_hash'), 99, 2);
        
        // Force recalculation on every cart get_cart_contents_total call
        add_action('woocommerce_before_calculate_totals', array($this, 'force_recalculation_before_totals'), 1);
        
        // Hook into mini cart display to ensure totals are correct
        add_filter('woocommerce_widget_cart_item_quantity', array($this, 'trigger_cart_calculation'), 10, 3);
        
        // Override cart contents count to include services
        add_filter('woocommerce_cart_contents_count', array($this, 'adjust_cart_contents_count'));
    }
    
    /**
     * Get Amelia services from database
     */
    private function get_amelia_services() {
        global $wpdb;
        
        // Check if Amelia table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) {
            return array();
        }
        
        $services = $wpdb->get_results(
            "SELECT s.id, s.name, s.status, s.price, s.duration, s.categoryId, s.description
             FROM $this->table_name s
             WHERE s.status IN ('visible', 'hidden')
             ORDER BY s.name ASC"
        );
        
        return $services ? $services : array();
    }
    
    /**
     * Get services linked to products in cart
     * 
     * Each product can have ONE linked service.
     * Multiple products with different services = all services shown (stacked).
     * Multiple products with SAME service = service shown once.
     * 
     * @param array $cart_items Array of cart item objects
     * @return array Array of services linked to cart products
     */
    private function match_services_to_products($cart_items) {
        if (empty($cart_items)) {
            return array();
        }
        
        $matched_services = array();
        $services = $this->get_amelia_services();
        
        if (empty($services)) {
            return array();
        }
        
        // Loop through each product in cart
        foreach ($cart_items as $cart_item) {
            // Get product data
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            
            // Get linked Amelia service ID from product meta (one per product)
            $service_id = get_post_meta($product_id, '_amelia_service_id', true);
            
            // Skip if no service linked to this product
            if (empty($service_id)) {
                continue;
            }
            
            // Find the service in our services array
            $service = null;
            foreach ($services as $s) {
                if ($s->id == $service_id) {
                    $service = $s;
                    break;
                }
            }
            
            // If service found, add to matched services
            if ($service) {
                if (!isset($matched_services[$service_id])) {
                    $matched_services[$service_id] = array(
                        'service' => $service,
                        'matched_products' => array()
                    );
                }
                
                $matched_services[$service_id]['matched_products'][] = array(
                    'product_id' => $product_id,
                    'product_name' => $product->get_name()
                );
            }
        }
        
        // Return all matched services (we'll show them all)
        return $matched_services;
    }
    
    /**
     * AJAX handler to get service recommendations
     */
    public function ajax_get_recommendations() {
        // Verify nonce but allow to continue if it fails (for logged out users)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cart_service_recs_nonce')) {
            // Silently fail for nonce issues - user might be logged out or session expired
            wp_send_json_success(array('recommendations' => array()));
            return;
        }
        
        // Get cart items
        if (!WC()->cart) {
            wp_send_json_error(array('message' => 'Cart not available'));
            return;
        }
        
        $cart_items = WC()->cart->get_cart();
        
        if (empty($cart_items)) {
            wp_send_json_success(array('recommendations' => array()));
            return;
        }
        
        // Get matched services
        $matched_services = $this->match_services_to_products($cart_items);
        
        // Get services already in session
        $cart_services = WC()->session ? WC()->session->get('amelia_cart_services', array()) : array();
        
        // Get Amelia service IDs that are already booked in cart
        $amelia_booked_services = $this->get_amelia_services_in_cart($cart_items);
        
        // Format for output
        $recommendations = array();
        foreach ($matched_services as $service_id => $match_data) {
            // Skip if this service already has an Amelia booking in cart
            if (in_array($service_id, $amelia_booked_services)) {
                continue;
            }
            
            $service = $match_data['service'];
            $service_fee = floatval(get_option('gos_service_fee_amount', 50.00));
            $recommendations[] = array(
                'id' => $service->id,
                'name' => $service->name,
                'price' => floatval($service->price) + $service_fee,
                'duration' => $service->duration,
                'description' => $service->description,
                'matched_products' => $match_data['matched_products'],
                'in_cart' => isset($cart_services[$service_id]),
                'has_amelia_booking' => false
            );
        }
        
        wp_send_json_success(array('recommendations' => $recommendations));
    }
    
    /**
     * AJAX handler to add Amelia service to cart
     */
    public function ajax_add_service_to_cart() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cart_service_recs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        if (!$service_id) {
            wp_send_json_error(array('message' => 'Invalid service ID'));
            return;
        }
        
        // Get service details
        $service = $this->get_service_by_id($service_id);
        
        if (!$service) {
            wp_send_json_error(array('message' => 'Service not found'));
            return;
        }
        
        // Store in session
        if (!isset(WC()->session)) {
            wp_send_json_error(array('message' => 'Session not available'));
            return;
        }
        
        $cart_services = WC()->session->get('amelia_cart_services', array());
        
        // Add service to session if not already there
        if (!isset($cart_services[$service_id])) {
            $service_fee = floatval(get_option('gos_service_fee_amount', 50.00));
            $cart_services[$service_id] = array(
                'service_id' => $service->id,
                'name' => $service->name,
                'price' => floatval($service->price) + $service_fee,
                'duration' => intval($service->duration)
            );
            
            WC()->session->set('amelia_cart_services', $cart_services);
            
            // Force cart recalculation and fragments refresh
            WC()->cart->calculate_totals();
            
            // Mark cart as needing refresh
            WC()->session->set('cart_totals_needs_refresh', true);
        }
        
        // Return cart fragments to update cart count and content
        $data = array(
            'message' => 'Service added to cart',
            'service' => $cart_services[$service_id],
            'fragments' => $this->get_refreshed_fragments()
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler to remove Amelia service from cart
     */
    public function ajax_remove_service_from_cart() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cart_service_recs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        if (!$service_id) {
            wp_send_json_error(array('message' => 'Invalid service ID'));
            return;
        }
        
        // Remove from session
        if (!isset(WC()->session)) {
            wp_send_json_error(array('message' => 'Session not available'));
            return;
        }
        
        $cart_services = WC()->session->get('amelia_cart_services', array());
        
        if (isset($cart_services[$service_id])) {
            unset($cart_services[$service_id]);
            WC()->session->set('amelia_cart_services', $cart_services);
            
            // Force cart recalculation
            WC()->cart->calculate_totals();
            
            // Mark cart as needing refresh
            WC()->session->set('cart_totals_needs_refresh', true);
        }
        
        // Return cart fragments to update cart count and content
        $data = array(
            'message' => 'Service removed from cart',
            'fragments' => $this->get_refreshed_fragments()
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler to get current cart total
     * This ensures frontend always displays the correct total including services
     */
    public function ajax_get_current_cart_total() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cart_service_recs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        if (!WC()->cart) {
            wp_send_json_error(array('message' => 'Cart not available'));
            return;
        }
        
        // Force recalculation
        WC()->cart->calculate_totals();
        $total = WC()->cart->get_total('');
        
        wp_send_json_success(array(
            'total' => $total,
            'total_html' => wc_price($total)
        ));
    }
    
    /**
     * AJAX handler to check if a service is in cart
     */
    public function ajax_check_service_in_cart() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cart_service_recs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        if (!$service_id) {
            wp_send_json_error(array('message' => 'Invalid service ID'));
            return;
        }
        
        // Get services from session
        $cart_services = WC()->session ? WC()->session->get('amelia_cart_services', array()) : array();
        $in_cart = isset($cart_services[$service_id]);
        
        wp_send_json_success(array(
            'in_cart' => $in_cart,
            'service_id' => $service_id
        ));
    }
    
    /**
     * AJAX handler to check if Amelia booking exists in cart for a service
     */
    public function ajax_check_amelia_booking_in_cart() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cart_service_recs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        if (!$service_id) {
            wp_send_json_error(array('message' => 'Invalid service ID'));
            return;
        }
        
        // Check if Amelia booking exists for this service
        $has_booking = false;
        
        if (WC()->cart) {
            $cart_items = WC()->cart->get_cart();
            $amelia_services = $this->get_amelia_services_in_cart($cart_items);
            $has_booking = in_array($service_id, $amelia_services);
        }
        
        wp_send_json_success(array(
            'has_booking' => $has_booking,
            'service_id' => $service_id
        ));
    }
    
    /**
     * AJAX handler to get all cart service IDs
     * Used for syncing state across widgets
     */
    public function ajax_get_all_cart_services() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cart_service_recs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Get services from session
        $cart_services = WC()->session ? WC()->session->get('amelia_cart_services', array()) : array();
        $service_ids = !empty($cart_services) ? array_keys($cart_services) : array();
        
        wp_send_json_success(array(
            'service_ids' => array_map('intval', $service_ids)
        ));
    }
    
    /**
     * AJAX handler to get service booking URL
     */
    public function ajax_get_service_booking_url() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cart_service_recs_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        
        if (!$service_id) {
            wp_send_json_error(array('message' => 'Invalid service ID'));
            return;
        }
        
        // Check if plugin helper function exists
        if (!function_exists('gos_get_service_linked_page')) {
            // Load the plugin file if not already loaded
            $plugin_file = WP_PLUGIN_DIR . '/gos-service-location-check/includes/admin-service-page-links-tab.php';
            if (file_exists($plugin_file)) {
                require_once $plugin_file;
            }
        }
        
        // Use plugin's helper function to get service-specific page
        $page_id = false;
        if (function_exists('gos_get_service_linked_page')) {
            $page_id = gos_get_service_linked_page($service_id);
        } else {
            // Fall back to direct option check
            $page_id = get_option("gos_service_link_{$service_id}", false);
            if ($page_id && get_post_status($page_id) !== 'publish') {
                $page_id = false;
            }
        }
        
        if ($page_id) {
            $url = get_permalink($page_id);
            wp_send_json_success(array(
                'url' => $url,
                'page_id' => $page_id
            ));
        } else {
            // No service page configured
            wp_send_json_error(array(
                'message' => 'No booking page configured for this service. Please configure in Service Page Links settings.'
            ));
        }
    }
    
    /**
     * Get service by ID
     */
    private function get_service_by_id($service_id) {
        global $wpdb;
        
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $service_id
        ));
        
        return $service;
    }
    
    /**
     * Get Amelia service IDs that are already booked in cart
     * 
     * @param array $cart_items WooCommerce cart items
     * @return array Array of service IDs that have Amelia bookings
     */
    private function get_amelia_services_in_cart($cart_items) {
        $amelia_service_ids = array();
        
        foreach ($cart_items as $cart_item) {
            // Check if this cart item has Amelia booking data
            if (isset($cart_item['ameliabooking']) && is_array($cart_item['ameliabooking'])) {
                // Extract the service ID from Amelia's booking data
                if (isset($cart_item['ameliabooking']['serviceId'])) {
                    $amelia_service_ids[] = intval($cart_item['ameliabooking']['serviceId']);
                }
            }
        }
        
        return $amelia_service_ids;
    }
    
    /**
     * Get refreshed cart fragments including updated cart count
     */
    private function get_refreshed_fragments() {
        $fragments = array();
        
        // Get cart contents count including services
        $cart_count = WC()->cart->get_cart_contents_count();
        $cart_services = WC()->session ? WC()->session->get('amelia_cart_services', array()) : array();
        $total_count = $cart_count + count($cart_services);
        
        // Update cart count fragment
        ob_start();
        ?>
        <span class="w-cart-quantity"><?php echo esc_html($total_count); ?></span>
        <?php
        $fragments['.w-cart-quantity'] = ob_get_clean();
        
        // Trigger WooCommerce to generate other fragments
        $fragments = apply_filters('woocommerce_add_to_cart_fragments', $fragments);
        
        return $fragments;
    }
    
    /**
     * Add Amelia services as fees to WooCommerce cart
     * DISABLED: Services are now displayed as line items only
     * They appear in the products list, not in the fees/totals section
     */
    public function add_service_fees_to_cart() {
        // Services are displayed as line items in cart/checkout
        // We don't add them as fees to avoid duplicate display
        return;
    }
    
    /**
     * Force cart recalculation when loaded from session
     * This ensures service fees are included on page load
     */
    public function recalculate_cart_on_load($cart) {
        if (WC()->session) {
            $cart_services = WC()->session->get('amelia_cart_services', array());
            
            // Only recalculate if there are services in session
            if (!empty($cart_services)) {
                // Mark cart as needing calculation
                $cart->set_cart_contents_total(null);
                $cart->set_fee_total(null);
                $cart->set_total(null);
                
                // Cart will automatically trigger calculate_fees hook
                $cart->calculate_totals();
                
                // Force WooCommerce to update the cart hash to invalidate cached fragments
                WC()->cart->persistent_cart_update();
            }
        }
    }
    
    /**
     * Force recalculation before totals are calculated
     * This ensures services are always included
     */
    public function force_recalculation_before_totals($cart) {
        // This is a dummy function just to ensure we're in the calculation flow
        // The actual service fees are added via woocommerce_cart_calculate_fees
    }
    
    /**
     * Trigger cart calculation when mini cart is displayed
     * This ensures the total shown includes service fees
     */
    public function trigger_cart_calculation($quantity_html, $cart_item, $cart_item_key) {
        // Force cart to recalculate if services are present
        if (WC()->session) {
            $cart_services = WC()->session->get('amelia_cart_services', array());
            if (!empty($cart_services) && WC()->cart) {
                // Don't call calculate_totals here as it causes recursion
                // Just ensure the session data is available
            }
        }
        return $quantity_html;
    }
    
    /**
     * Adjust cart contents count to include services
     */
    public function adjust_cart_contents_count($count) {
        if (WC()->session) {
            $cart_services = WC()->session->get('amelia_cart_services', array());
            if (!empty($cart_services)) {
                $count += count($cart_services);
            }
        }
        return $count;
    }
    
    /**
     * Modify cart hash to include service data
     * This ensures cart fragments are regenerated when services change
     */
    public function modify_cart_hash($hash, $cart) {
        if (WC()->session) {
            $cart_services = WC()->session->get('amelia_cart_services', array());
            
            // Include service IDs in hash so cart updates when services change
            if (!empty($cart_services)) {
                $service_ids = implode(',', array_keys($cart_services));
                $hash = md5($hash . $service_ids);
            }
        }
        return $hash;
    }
    
    /**
     * Ensure cart totals are recalculated when fragments are updated
     */
    public function ensure_cart_totals_updated($fragments) {
        // Force cart recalculation to include service fees
        if (WC()->cart && WC()->session) {
            $cart_services = WC()->session->get('amelia_cart_services', array());
            
            // Only recalculate if there are services in session
            if (!empty($cart_services)) {
                // Force cart calculation
                WC()->cart->calculate_totals();
                
                // Get the new total
                $cart_total = WC()->cart->get_total('');
                
                // Update ALL cart total fragments to ensure they show correct price
                // We need to update the actual fragment keys that WooCommerce generates
                foreach ($fragments as $key => $value) {
                    // Look for any fragment containing cart total/amount
                    if (strpos($key, '.total') !== false || strpos($key, 'amount') !== false || strpos($key, 'w-cart') !== false) {
                        // Parse the HTML and replace the price
                        if (preg_match('/<bdi>(.*?)<\/bdi>/', $value, $matches)) {
                            $fragments[$key] = str_replace($matches[0], '<bdi>' . wc_price($cart_total) . '</bdi>', $value);
                        } elseif (preg_match('/<span class="amount">(.*?)<\/span>/', $value, $matches)) {
                            $fragments[$key] = str_replace($matches[0], '<span class="amount">' . wc_price($cart_total) . '</span>', $value);
                        }
                    }
                }
            }
        }
        return $fragments;
    }
    
    /**
     * Adjust cart subtotal to include service prices
     * Since we display services as line items (not fees), we need to add their prices to the subtotal
     * This works on both cart and checkout pages
     */
    public function adjust_cart_subtotal($cart_subtotal, $compound, $cart) {
        if (!WC()->session) {
            return $cart_subtotal;
        }
        
        $cart_services = WC()->session->get('amelia_cart_services', array());
        
        if (empty($cart_services)) {
            return $cart_subtotal;
        }
        
        // Calculate total service price
        $service_total = 0;
        foreach ($cart_services as $service) {
            $service_total += floatval($service['price']);
        }
        
        // Add service total to subtotal
        $new_subtotal = $cart->subtotal + $service_total;
        
        return wc_price($new_subtotal);
    }
    
    /**
     * Adjust cart total to include service prices
     * This ensures the final total includes services even though they're not fees
     */
    public function adjust_cart_total($total, $cart) {
        if (!WC()->session) {
            return $total;
        }
        
        $cart_services = WC()->session->get('amelia_cart_services', array());
        
        if (empty($cart_services)) {
            return $total;
        }
        
        // Calculate total service price
        $service_total = 0;
        foreach ($cart_services as $service) {
            $service_total += floatval($service['price']);
        }
        
        // Add service total to cart total
        return $total + $service_total;
    }
    
    /**
     * Display services as line items in checkout review table
     */
    public function display_services_in_checkout() {
        if (!WC()->session) {
            return;
        }
        
        $cart_services = WC()->session->get('amelia_cart_services', array());
        
        if (empty($cart_services)) {
            return;
        }
        
        foreach ($cart_services as $service_id => $service) {
            ?>
            <tr class="cart_item amelia-service-item">
                <td class="product-name">
                    <?php echo esc_html($service['name']); ?>
                    <strong class="product-quantity">&nbsp;×&nbsp;1</strong>
                    <br>
                    <a href="#" class="book-service-now" 
                       data-service-id="<?php echo esc_attr($service_id); ?>"
                       data-service-name="<?php echo esc_attr($service['name']); ?>"
                       style="font-size: 0.85em; color: rgba(141, 69, 252, 1); text-decoration: underline;">
                        Complete booking →
                    </a>
                    <span class="service-booking-info" 
                          title="Schedule your service appointment"
                          style="display: inline-block; margin-left: 4px; color: #999; cursor: help; font-size: 0.85em;">ⓘ</span>
                </td>
                <td class="product-total">
                    <?php echo wc_price($service['price']); ?>
                </td>
            </tr>
            <?php
        }
    }
    
    /**
     * Display services as line items in cart page
     */
    public function display_services_in_cart() {
        if (!WC()->session) {
            return;
        }
        
        $cart_services = WC()->session->get('amelia_cart_services', array());
        
        if (empty($cart_services)) {
            return;
        }
        
        foreach ($cart_services as $service_id => $service) {
            ?>
            <tr class="woocommerce-cart-form__cart-item cart_item amelia-service-item" data-service-id="<?php echo esc_attr($service_id); ?>">
                <td class="product-remove">
                    <a href="#" 
                       class="remove remove-amelia-service" 
                       aria-label="<?php echo esc_attr(sprintf(__('Remove %s from cart', 'woocommerce'), $service['name'])); ?>" 
                       data-service-id="<?php echo esc_attr($service_id); ?>"
                       data-service-name="<?php echo esc_attr($service['name']); ?>">×</a>
                </td>
                <td class="product-thumbnail">
                    <!-- Empty cell for alignment -->
                </td>
                <td class="product-name" data-title="<?php esc_attr_e('Product', 'woocommerce'); ?>">
                    <?php echo esc_html($service['name']); ?>
                    <br>
                    <a href="#" class="book-service-now" 
                       data-service-id="<?php echo esc_attr($service_id); ?>"
                       data-service-name="<?php echo esc_attr($service['name']); ?>"
                       style="font-size: 0.9em; color: rgba(141, 69, 252, 1); text-decoration: underline;">
                        Complete booking →
                    </a>
                    <span class="service-booking-info" 
                          title="Schedule your service appointment"
                          style="display: inline-block; margin-left: 4px; color: #999; cursor: help; font-size: 0.9em;">ⓘ</span>
                </td>
                <td class="product-price" data-title="<?php esc_attr_e('Price', 'woocommerce'); ?>">
                    <?php echo wc_price($service['price']); ?>
                </td>
                <td class="product-quantity" data-title="<?php esc_attr_e('Quantity', 'woocommerce'); ?>">
                    1
                </td>
                <td class="product-subtotal" data-title="<?php esc_attr_e('Subtotal', 'woocommerce'); ?>">
                    <?php echo wc_price($service['price']); ?>
                </td>
            </tr>
            <?php
        }
    }
    
    /**
     * Display services in mini cart drawer
     */
    public function display_services_in_mini_cart() {
        if (!WC()->session) {
            return;
        }
        
        $cart_services = WC()->session->get('amelia_cart_services', array());
        
        if (empty($cart_services)) {
            return;
        }
        
        foreach ($cart_services as $service_id => $service) {
            ?>
            <li class="woocommerce-mini-cart-item mini_cart_item amelia-service-item" data-service-id="<?php echo esc_attr($service_id); ?>">
                <a href="#" 
                   class="remove remove_from_cart_button remove-amelia-service" 
                   aria-label="<?php echo esc_attr(sprintf(__('Remove %s from cart', 'woocommerce'), $service['name'])); ?>"
                   data-service-id="<?php echo esc_attr($service_id); ?>"
                   data-service-name="<?php echo esc_attr($service['name']); ?>">×</a>
                <span class="product-name">
                    <?php echo esc_html($service['name']); ?>
                </span>
                <span class="quantity">
                    1 × <?php echo wc_price($service['price']); ?>
                </span>
            </li>
            <?php
        }
    }
    
    /**
     * Output service recommendations template in footer
     */
    public function output_service_recommendations_template() {
        ?>
        <div id="cart-service-recommendations-template" style="display: none;">
            <div class="cart-service-recommendations">
                <div class="service-rec-header">
                    <span class="service-rec-title">Need professional help?</span>
                    <span class="service-rec-subtitle">We can help with installation</span>
                </div>
                <div class="service-rec-list">
                    <!-- Services will be injected here by JavaScript -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Track when user is redirected to Amelia booking page
     * Store the service ID they're booking in session
     */
    public function track_amelia_booking_redirect() {
        // Check if ameliaService parameter is present
        if (isset($_GET['ameliaService']) && WC()->session) {
            $service_id = intval($_GET['ameliaService']);
            
            if ($service_id > 0) {
                // Store that this service is being booked
                WC()->session->set('amelia_service_being_booked', $service_id);
            }
        }
    }
    
    /**
     * Remove custom service immediately when Amelia adds a booking to cart
     * This hook fires right when an item is added, before cart totals are calculated
     * 
     * @param string $cart_item_key Cart item key
     * @param int $product_id Product ID
     * @param int $quantity Quantity added
     * @param int $variation_id Variation ID
     * @param array $variation Variation data
     * @param array $cart_item_data Cart item data
     */
    public function remove_service_on_amelia_add($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        // Check if this is an Amelia booking
        if (!isset($cart_item_data['ameliabooking']) || !is_array($cart_item_data['ameliabooking'])) {
            return;
        }
        
        // Extract the service ID from Amelia's booking data
        if (!isset($cart_item_data['ameliabooking']['serviceId'])) {
            return;
        }
        
        $amelia_service_id = intval($cart_item_data['ameliabooking']['serviceId']);
        
        // Check if we have this service in our custom services
        if (!WC()->session) {
            return;
        }
        
        $cart_services = WC()->session->get('amelia_cart_services', array());
        
        // Remove the matching custom service
        if (isset($cart_services[$amelia_service_id])) {
            unset($cart_services[$amelia_service_id]);
            WC()->session->set('amelia_cart_services', $cart_services);
            
            // Clear the tracking flag if it exists
            if (WC()->session->get('amelia_service_being_booked')) {
                WC()->session->__unset('amelia_service_being_booked');
            }
        }
    }
    
    /**
     * Remove service from cart when Amelia booking is completed
     * This prevents duplicate charges since Amelia adds its own cart item
     * This is a backup in case the immediate removal above doesn't catch it
     */
    public function remove_service_after_amelia_booking($cart) {
        // Only proceed if we have a valid session
        if (!WC()->session) {
            return;
        }
        
        // Get our stored services
        $cart_services = WC()->session->get('amelia_cart_services', array());
        
        if (empty($cart_services)) {
            return;
        }
        
        // Check if Amelia has added any services to the cart
        $cart_items = WC()->cart->get_cart();
        $amelia_service_ids = array();
        
        // Look for Amelia's actual booking products using the correct ameliabooking key
        foreach ($cart_items as $cart_item) {
            // Check if this cart item has Amelia booking data
            if (isset($cart_item['ameliabooking']) && is_array($cart_item['ameliabooking'])) {
                // Extract the service ID from Amelia's booking data
                if (isset($cart_item['ameliabooking']['serviceId'])) {
                    $amelia_service_ids[] = intval($cart_item['ameliabooking']['serviceId']);
                }
            }
        }
        
        // If we found any Amelia bookings, remove matching custom services
        if (!empty($amelia_service_ids)) {
            $services_removed = false;
            
            foreach ($amelia_service_ids as $service_id) {
                // Remove the custom service that matches this Amelia booking
                if (isset($cart_services[$service_id])) {
                    unset($cart_services[$service_id]);
                    $services_removed = true;
                }
            }
            
            // Update session if any services were removed
            if ($services_removed) {
                WC()->session->set('amelia_cart_services', $cart_services);
                
                // Clear the tracking flag if it exists
                if (WC()->session->get('amelia_service_being_booked')) {
                    WC()->session->__unset('amelia_service_being_booked');
                }
            }
        }
    }
}

// Initialize
new Cart_Service_Recommendations();
