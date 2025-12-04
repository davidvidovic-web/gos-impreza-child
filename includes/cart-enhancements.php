<?php
/**
 * Cart & Checkout Enhancements
 * 
 * Handles cart display, checkout functionality, and Amelia integration
 * for WooCommerce cart, checkout, and minicart components.
 * 
 * Moved from gos-service-location-check plugin to child theme.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Impreza_Child_Cart_Enhancements {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Enqueue cart display scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_cart_assets'), 25);
        
        // CRITICAL: Fix WooCommerce cookies for HTTP staging environments
        if (!is_ssl()) {
            add_filter('woocommerce_cookie_secure', '__return_false', 1);
            add_filter('secure_auth_cookie', '__return_false', 1);
            add_filter('woocommerce_set_cookie_enabled', '__return_true', 1);
        }
        
        // Ensure WooCommerce session is started for guest users
        add_action('init', array($this, 'ensure_wc_session'), 5);
        
        // Force enable guest checkout for Amelia bookings
        add_filter('pre_option_woocommerce_enable_guest_checkout', array($this, 'force_guest_checkout'));
        
        // Ensure cart is not empty before checkout
        add_action('template_redirect', array($this, 'check_cart_before_checkout'));
        
        // Modify WooCommerce cart item data display for Amelia appointments
        add_filter('woocommerce_get_item_data', array($this, 'modify_cart_item_display'), 100, 2);
        
        // Add booking_id to cart item data when Amelia adds to cart
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_booking_id_to_cart'), 999, 3);
        
        // Hook into cart item loaded from session to inject data
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'inject_data_from_session'), 999, 3);
        
        // Store custom fields/addons in WooCommerce order metadata
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_custom_fields_to_order_item'), 10, 4);
        
        // Remove thumbnail for Amelia booking items
        add_filter('woocommerce_cart_item_thumbnail', array($this, 'remove_amelia_thumbnail'), 10, 3);
        add_filter('woocommerce_order_item_thumbnail', array($this, 'remove_amelia_thumbnail_checkout'), 10, 2);
    }
    
    /**
     * Enqueue cart display scripts and styles
     * 
     * NOTE: cart-enhancements.js is already enqueued via functions.php
     * (impreza_child_enqueue_cart_checkout_assets function)
     * This method is kept for potential future use but doesn't enqueue anything
     */
    public function enqueue_cart_assets() {
        // Scripts are enqueued via functions.php to avoid duplication
        // This class handles the PHP side (session management, data processing, etc.)
    }
    
    /**
     * Ensure WooCommerce session is started for guest users
     */
    public function ensure_wc_session() {
        if (class_exists('WC') && !is_admin()) {
            // Check if this is coming from Amelia
            if (isset($_REQUEST['ameliaBooking']) || 
                isset($_REQUEST['amelia']) || 
                (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'amelia') !== false)) {
                
                // CRITICAL: Fix for HTTP staging/development environments
                if (!is_ssl() && !defined('COOKIEPATH')) {
                    add_filter('woocommerce_cookie_secure', '__return_false', 999);
                    add_filter('secure_auth_cookie', '__return_false', 999);
                }
                
                // Ensure WooCommerce is loaded
                if (function_exists('WC') && WC()->session) {
                    if (!WC()->session->has_session()) {
                        WC()->session->set_customer_session_cookie(true);
                    }
                    
                    // Force regenerate session for HTTP environments
                    if (!is_ssl()) {
                        WC()->cart->get_cart();
                    }
                }
            }
        }
    }
    
    /**
     * Force enable guest checkout for Amelia bookings
     */
    public function force_guest_checkout($value) {
        if (isset($_REQUEST['ameliaBooking']) || 
            isset($_REQUEST['amelia']) ||
            (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'amelia') !== false)) {
            return 'yes';
        }
        
        return $value;
    }
    
    /**
     * Check if cart is empty before checkout and redirect if needed
     */
    public function check_cart_before_checkout() {
        if (function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url()) {
            if (function_exists('WC') && WC()->cart && WC()->cart->is_empty()) {
                $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
                
                if (strpos($referrer, 'amelia') !== false) {
                    if (function_exists('wc_add_notice')) {
                        wc_add_notice(__('Your booking session has expired. Please try booking again.', 'impreza-child'), 'error');
                    }
                }
                
                wp_safe_redirect(wc_get_page_permalink('shop'));
                exit;
            }
        }
    }
    
    /**
     * Modify WooCommerce cart item data display for Amelia appointments
     * Add custom fields and addons to the cart display
     */
    public function modify_cart_item_display($item_data, $cart_item) {
        // Check if this is an Amelia appointment
        if (!isset($cart_item['ameliabooking'])) {
            return $item_data;
        }
        
        // Get custom fields and addons from cart item data
        $custom_fields = isset($cart_item['gos_custom_fields']) ? $cart_item['gos_custom_fields'] : array();
        $addons = isset($cart_item['gos_extras']) ? $cart_item['gos_extras'] : array();
        
        // Fallback: Try to get from transients if not in cart item
        if (empty($custom_fields) && empty($addons)) {
            $service_id = isset($cart_item['ameliabooking']['serviceId']) ? $cart_item['ameliabooking']['serviceId'] : null;
            $booking_id = '';
            
            if (isset($cart_item['gos_booking_id'])) {
                $booking_id = $cart_item['gos_booking_id'];
            } else {
                if (session_id() === '') {
                    session_start();
                }
                $booking_id = session_id();
            }
            
            if ($booking_id && $service_id) {
                $custom_fields_key = 'gos_custom_fields_' . $booking_id . '_' . $service_id;
                $addons_key = 'gos_extras_' . $booking_id . '_' . $service_id;
                
                $custom_fields = get_transient($custom_fields_key);
                $addons = get_transient($addons_key);
            }
        }
        
        // Build grouped custom fields HTML
        $custom_fields_html = '';
        if (!empty($custom_fields) && is_array($custom_fields)) {
            $custom_fields_html = '<div class="gos-custom-fields-group">';
            $custom_fields_html .= '<strong class="gos-group-title collapsed">Service Details</strong>';
            $custom_fields_html .= '<ul class="gos-field-list collapsed">';
            
            foreach ($custom_fields as $field_id => $field_data) {
                if (is_array($field_data)) {
                    $label = isset($field_data['label']) ? $field_data['label'] : 'Field ' . $field_id;
                    $value = isset($field_data['value']) ? $field_data['value'] : '';
                    $option_label = isset($field_data['optionLabel']) ? $field_data['optionLabel'] : $value;
                    $price = isset($field_data['price']) ? floatval($field_data['price']) : 0;
                    
                    // Clean option label
                    $option_label = preg_replace('/\s*\(\+?\$[\d,.]+\)\s*/', '', $option_label);
                    $option_label = str_replace(array('\\"', "\\'"), array('"', "'"), $option_label);
                    
                    if (!empty($option_label)) {
                        $custom_fields_html .= '<li><span class="gos-field-label">' . esc_html($label) . '</span> ';
                        $custom_fields_html .= '<span class="gos-field-value">' . esc_html($option_label);
                        if ($price > 0) {
                            $custom_fields_html .= ' <span class="gos-field-price">(+$' . number_format($price, 2) . ')</span>';
                        }
                        $custom_fields_html .= '</span></li>';
                    }
                }
            }
            
            $custom_fields_html .= '</ul></div>';
        }
        
        // Build grouped addons HTML
        $addons_html = '';
        if (!empty($addons) && is_array($addons)) {
            $addons_html = '<div class="gos-addons-group">';
            $addons_html .= '<strong class="gos-group-title collapsed">Add-ons</strong>';
            $addons_html .= '<ul class="gos-addon-list collapsed">';
            
            foreach ($addons as $addon_id => $addon_data) {
                if (is_array($addon_data) && isset($addon_data['selected']) && $addon_data['selected']) {
                    $name = isset($addon_data['name']) ? $addon_data['name'] : 'Add-on ' . $addon_id;
                    $price = isset($addon_data['price']) ? floatval($addon_data['price']) : 0;
                    $quantity = isset($addon_data['quantity']) ? intval($addon_data['quantity']) : 1;
                    
                    $addons_html .= '<li><span class="gos-addon-name">' . esc_html($name);
                    if ($quantity > 1) {
                        $addons_html .= ' <span class="gos-addon-qty">(x' . $quantity . ')</span>';
                    }
                    $addons_html .= '</span> <span class="gos-addon-price">(+$' . number_format($price * $quantity, 2) . ')</span></li>';
                }
            }
            
            $addons_html .= '</ul></div>';
        }
        
        // Find "Appointment Info" and add our data after it
        $appointment_info_index = -1;
        foreach ($item_data as $index => $data) {
            if (isset($data['key']) && $data['key'] === 'Appointment Info:') {
                $appointment_info_index = $index;
                break;
            }
        }
        
        // Insert custom fields and addons after Appointment Info
        if ($appointment_info_index !== -1 && (!empty($custom_fields_html) || !empty($addons_html))) {
            if (!empty($custom_fields_html)) {
                array_splice($item_data, $appointment_info_index + 1, 0, array(
                    array(
                        'key' => '',
                        'value' => $custom_fields_html,
                        'display' => ''
                    )
                ));
                $appointment_info_index++;
            }
            
            if (!empty($addons_html)) {
                array_splice($item_data, $appointment_info_index + 1, 0, array(
                    array(
                        'key' => '',
                        'value' => $addons_html,
                        'display' => ''
                    )
                ));
            }
        } else {
            // Fallback: Add at the end
            if (!empty($custom_fields_html)) {
                $item_data[] = array('key' => '', 'value' => $custom_fields_html, 'display' => '');
            }
            if (!empty($addons_html)) {
                $item_data[] = array('key' => '', 'value' => $addons_html, 'display' => '');
            }
        }
        
        return $item_data;
    }
    
    /**
     * Add booking_id to cart item data when Amelia adds item to cart
     * Also adds a unique cart identifier to prevent merging
     */
    public function add_booking_id_to_cart($cart_item_data, $product_id, $variation_id) {
        // Check if this is an Amelia booking
        if (!isset($cart_item_data['ameliabooking'])) {
            return $cart_item_data;
        }
        
        $service_id = isset($cart_item_data['ameliabooking']['serviceId']) ? $cart_item_data['ameliabooking']['serviceId'] : null;
        
        if (!$service_id) {
            return $cart_item_data;
        }
        
        // Get booking_id from session for this specific service
        if (session_id() === '') {
            session_start();
        }
        
        $booking_id = isset($_SESSION['gos_current_booking_' . $service_id]) ? $_SESSION['gos_current_booking_' . $service_id] : null;
        $unified_data = null;
        
        if ($booking_id) {
            $fallback_key = 'gos_unified_fallback_' . $service_id . '_' . $booking_id;
            $unified_id = get_transient($fallback_key);
            
            if ($unified_id) {
                $unified_data = get_transient($unified_id);
                
                // Verify the service_id matches
                if ($unified_data && isset($unified_data['service_id']) && $unified_data['service_id'] == $service_id && isset($unified_data['booking_id'])) {
                    $cart_item_data['gos_booking_id'] = $unified_data['booking_id'];
                } else {
                    $unified_data = null;
                }
            }
        }
        
        // Fallback to session_id
        if (!isset($cart_item_data['gos_booking_id'])) {
            if (session_id() === '') {
                session_start();
            }
            $cart_item_data['gos_booking_id'] = session_id();
        }
        
        // Store custom data directly in cart item data
        if ($unified_data && isset($unified_data['service_id']) && $unified_data['service_id'] == $service_id) {
            if (isset($unified_data['custom_fields'])) {
                $cart_item_data['gos_custom_fields'] = $unified_data['custom_fields'];
            }
            if (isset($unified_data['extras'])) {
                $cart_item_data['gos_extras'] = $unified_data['extras'];
            }
            
            // Clean up transients after successful cart addition
            $booking_id = $unified_data['booking_id'];
            delete_transient('gos_unified_fallback_' . $service_id . '_' . $booking_id);
            delete_transient('gos_unified_' . $booking_id . '_' . $service_id);
            delete_transient('gos_custom_fields_' . $booking_id . '_' . $service_id);
            delete_transient('gos_extras_' . $booking_id . '_' . $service_id);
            
            if (isset($_SESSION['gos_current_booking_' . $service_id])) {
                unset($_SESSION['gos_current_booking_' . $service_id]);
            }
        }
        
        // CRITICAL: Add unique identifier to prevent WooCommerce from merging cart items
        $unique_parts = array();
        
        if (isset($cart_item_data['ameliabooking']['serviceId'])) {
            $unique_parts[] = 'service_' . $cart_item_data['ameliabooking']['serviceId'];
        }
        
        if (isset($cart_item_data['ameliabooking']['bookingStart'])) {
            $unique_parts[] = 'time_' . $cart_item_data['ameliabooking']['bookingStart'];
        }
        
        if (isset($cart_item_data['ameliabooking']['providerId'])) {
            $unique_parts[] = 'provider_' . $cart_item_data['ameliabooking']['providerId'];
        }
        
        if (isset($cart_item_data['ameliabooking']['id'])) {
            $unique_parts[] = 'appt_' . $cart_item_data['ameliabooking']['id'];
        }
        
        if (isset($cart_item_data['ameliabooking']['bookings'][0]['id'])) {
            $unique_parts[] = 'booking_' . $cart_item_data['ameliabooking']['bookings'][0]['id'];
        }
        
        // Include custom fields/addons in unique key
        if (!empty($cart_item_data['gos_custom_fields'])) {
            $unique_parts[] = 'cf_' . md5(serialize($cart_item_data['gos_custom_fields']));
        }
        if (!empty($cart_item_data['gos_extras'])) {
            $unique_parts[] = 'ex_' . md5(serialize($cart_item_data['gos_extras']));
        }
        
        // Always add timestamp + random to guarantee uniqueness
        $unique_parts[] = 'uniq_' . microtime(true) . '_' . mt_rand();
        
        $cart_item_data['gos_unique_cart_key'] = md5(implode('_', $unique_parts));
        
        return $cart_item_data;
    }
    
    /**
     * Re-inject custom data when cart item is loaded from session
     */
    public function inject_data_from_session($cart_item, $values, $cart_item_key) {
        // If we already have the data, we're done
        if (!empty($cart_item['gos_custom_fields']) || !empty($cart_item['gos_extras'])) {
            return $cart_item;
        }
        
        // Check if this is an Amelia booking
        if (empty($cart_item['ameliabooking'])) {
            return $cart_item;
        }
        
        $service_id = isset($cart_item['ameliabooking']['serviceId']) ? intval($cart_item['ameliabooking']['serviceId']) : 0;
        if (!$service_id) {
            return $cart_item;
        }
        
        // Try to get booking ID from the cart item
        $booking_id = null;
        if (isset($cart_item['ameliabooking']['bookings'][0]['id'])) {
            $booking_id = intval($cart_item['ameliabooking']['bookings'][0]['id']);
        }
        
        if (!$booking_id) {
            return $cart_item;
        }
        
        // Load unified data from transient
        $transients = get_transient('gos_unified_fallback_' . $service_id . '_' . $booking_id);
        if (!$transients || empty($transients['unified_id'])) {
            return $cart_item;
        }
        
        $unified_data = get_transient($transients['unified_id']);
        if (empty($unified_data)) {
            return $cart_item;
        }
        
        // Inject custom data
        if (!empty($unified_data['custom_fields'])) {
            $cart_item['gos_custom_fields'] = $unified_data['custom_fields'];
        }
        if (!empty($unified_data['extras'])) {
            $cart_item['gos_extras'] = $unified_data['extras'];
        }
        
        return $cart_item;
    }
    
    /**
     * Remove thumbnail for Amelia booking items in cart
     */
    public function remove_amelia_thumbnail($image, $cart_item, $cart_item_key) {
        // Check if this is an Amelia booking (has ameliabooking data)
        if (isset($cart_item['ameliabooking'])) {
            return '';
        }
        
        return $image;
    }
    
    /**
     * Remove thumbnail for Amelia booking items in checkout order review
     */
    public function remove_amelia_thumbnail_checkout($image, $item) {
        // Check if this is an Amelia booking order item
        $item_data = $item->get_data();
        $meta_data = $item->get_meta_data();
        
        // Check for Amelia-specific meta data
        foreach ($meta_data as $meta) {
            $meta_data_array = $meta->get_data();
            if (isset($meta_data_array['key']) && strpos($meta_data_array['key'], 'Appointment Info') !== false) {
                return '';
            }
        }
        
        return $image;
    }
    
    /**
     * Add custom fields and addons to WooCommerce order line item metadata
     */
    public function add_custom_fields_to_order_item($item, $cart_item_key, $values, $order) {
        // Check if this is an Amelia booking
        if (!isset($values['ameliabooking'])) {
            return;
        }
        
        // Get custom data
        $custom_fields = isset($values['gos_custom_fields']) ? $values['gos_custom_fields'] : array();
        $extras = isset($values['gos_extras']) ? $values['gos_extras'] : array();
        
        if (empty($custom_fields) && empty($extras)) {
            return;
        }
        
        // Add custom fields as order item meta
        if (!empty($custom_fields) && is_array($custom_fields)) {
            foreach ($custom_fields as $field_id => $field_data) {
                if (is_array($field_data)) {
                    $label = isset($field_data['label']) ? $field_data['label'] : 'Custom Field ' . $field_id;
                    $value = isset($field_data['value']) ? $field_data['value'] : '';
                    $option_label = isset($field_data['optionLabel']) ? $field_data['optionLabel'] : $value;
                    $price = isset($field_data['price']) ? floatval($field_data['price']) : 0;
                    
                    $option_label = preg_replace('/\s*\(\+?\$[\d,.]+\)\s*/', '', $option_label);
                    
                    if (!empty($option_label)) {
                        $display_value = $option_label;
                        if ($price > 0) {
                            $display_value .= ' (+$' . number_format($price, 2) . ')';
                        }
                        
                        $item->add_meta_data('_gos_cf_' . $field_id, $display_value, true);
                        $item->add_meta_data($label, $display_value, true);
                    }
                }
            }
        }
        
        // Add addons/extras as order item meta
        if (!empty($extras) && is_array($extras)) {
            foreach ($extras as $extra_id => $extra_data) {
                if (is_array($extra_data) && isset($extra_data['selected']) && $extra_data['selected']) {
                    $name = isset($extra_data['name']) ? $extra_data['name'] : 'Add-on ' . $extra_id;
                    $price = isset($extra_data['price']) ? floatval($extra_data['price']) : 0;
                    $quantity = isset($extra_data['quantity']) ? intval($extra_data['quantity']) : 1;
                    
                    $display_value = $name;
                    if ($quantity > 1) {
                        $display_value .= ' (x' . $quantity . ')';
                    }
                    if ($price > 0) {
                        $display_value .= ' (+$' . number_format($price * $quantity, 2) . ')';
                    }
                    
                    $item->add_meta_data('_gos_extra_' . $extra_id, $display_value, true);
                    $item->add_meta_data('Add-on: ' . $name, $display_value, true);
                }
            }
        }
    }
}

// Initialize the cart enhancements
new Impreza_Child_Cart_Enhancements();
