<?php
/**
 * Product Service Recommendations
 * Displays recommended Amelia services on individual product pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Product_Service_Recommendations {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'amelia_services';
        
        // Add service recommendations to product page (after add to cart button)
        add_action('woocommerce_after_add_to_cart_button', array($this, 'display_service_recommendation'));
        
        // Register shortcode
        add_shortcode('product_service_recommendation', array($this, 'shortcode_output'));
    }
    
    /**
     * Get Amelia service by ID
     */
    private function get_amelia_service($service_id) {
        global $wpdb;
        
        // Check if Amelia table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") != $this->table_name) {
            return null;
        }
        
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT s.id, s.name, s.status, s.price, s.duration, s.categoryId, s.description
             FROM $this->table_name s
             WHERE s.id = %d AND s.status IN ('visible', 'hidden')
             LIMIT 1",
            $service_id
        ));
        
        return $service;
    }
    
    /**
     * Get service linked to a product
     */
    public function get_product_service($product_id = null) {
        if (!$product_id) {
            global $product;
            if (!$product) {
                return null;
            }
            $product_id = $product->get_id();
        }
        
        // Get linked Amelia service ID from product meta
        $service_id = get_post_meta($product_id, '_amelia_service_id', true);
        
        if (empty($service_id)) {
            return null;
        }
        
        // Get service details
        return $this->get_amelia_service($service_id);
    }
    
    /**
     * Display service recommendation on product page
     */
    public function display_service_recommendation() {
        $service = $this->get_product_service();
        
        if (!$service) {
            return;
        }
        
        $this->render_service_card($service);
    }
    
    /**
     * Shortcode output
     * Usage: [product_service_recommendation]
     * Or: [product_service_recommendation product_id="123"]
     */
    public function shortcode_output($atts) {
        $atts = shortcode_atts(array(
            'product_id' => null,
            'show_description' => 'yes',
            'show_duration' => 'yes',
            'button_text' => 'Book This Service',
            'card_bg_color' => '',
            'card_border_color' => '',
            'badge_bg_color' => '',
            'badge_text_color' => '',
            'button_color' => '',
            'button_text_color' => '',
        ), $atts, 'product_service_recommendation');
        
        $product_id = $atts['product_id'] ? intval($atts['product_id']) : null;
        $service = $this->get_product_service($product_id);
        
        if (!$service) {
            return '';
        }
        
        ob_start();
        $this->render_service_card($service, 'product', $atts);
        return ob_get_clean();
    }
    
    /**
     * Render service recommendation card
     */
    public function render_service_card($service, $context = 'product', $custom_atts = array()) {
        // Check if this service already has an Amelia booking in cart
        if ($this->has_amelia_booking_in_cart($service->id)) {
            // Don't show the recommendation if Amelia booking already exists
            return;
        }
        
        $service_fee = floatval(get_option('gos_service_fee_amount', 50.00));
        $price = floatval($service->price) + $service_fee;
        
        ?>
        <div class="product-service-recommendation" data-service-id="<?php echo esc_attr($service->id); ?>">
            <div class="service-rec-header">
                <span class="service-rec-title">Need professional help?</span>
                <span class="service-rec-subtitle">We can help with installation</span>
            </div>
            <div class="service-rec-list">
                <div class="service-rec-item" data-service-id="<?php echo esc_attr($service->id); ?>" data-service-price="<?php echo esc_attr($price); ?>">
                    <div class="service-rec-row">
                        <span class="service-rec-name"><?php echo esc_html($service->name); ?></span>
                        <span class="service-rec-price"><?php echo wc_price($price); ?></span>
                    </div>
                    <div class="service-rec-actions">
                        <button class="service-rec-add-link" 
                                data-service-id="<?php echo esc_attr($service->id); ?>"
                                data-service-name="<?php echo esc_attr($service->name); ?>">
                            Add
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Check if a service already has an Amelia booking in cart
     * 
     * @param int $service_id Service ID to check
     * @return bool True if Amelia booking exists for this service
     */
    private function has_amelia_booking_in_cart($service_id) {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }
        
        $cart_items = WC()->cart->get_cart();
        
        foreach ($cart_items as $cart_item) {
            // Check if this cart item has Amelia booking data
            if (isset($cart_item['ameliabooking']) && is_array($cart_item['ameliabooking'])) {
                // Check if the service ID matches
                if (isset($cart_item['ameliabooking']['serviceId']) && 
                    intval($cart_item['ameliabooking']['serviceId']) === intval($service_id)) {
                    return true;
                }
            }
        }
        
        return false;
    }
}

// Initialize
new Product_Service_Recommendations();
