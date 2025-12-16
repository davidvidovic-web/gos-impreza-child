<?php
/**
 * WPBakery Product Service Recommendation Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class Product_Service_WPBakery_Widget {
    
    public function __construct() {
        // Register WPBakery widget
        add_action('vc_before_init', array($this, 'register_widget'));
    }
    
    /**
     * Register WPBakery Widget
     */
    public function register_widget() {
        if (!function_exists('vc_map')) {
            return;
        }
        
        vc_map(array(
            'name' => __('Product Service Recommendation', 'impreza-child'),
            'base' => 'product_service_recommendation',
            'category' => __('WooCommerce', 'impreza-child'),
            'description' => __('Display recommended Amelia service for a product', 'impreza-child'),
            'icon' => 'icon-wpb-woocommerce',
            'params' => array(
                // Product Source
                array(
                    'type' => 'dropdown',
                    'heading' => __('Product Source', 'impreza-child'),
                    'param_name' => 'source',
                    'value' => array(
                        __('Current Product Page', 'impreza-child') => 'current',
                        __('Specific Product', 'impreza-child') => 'specific',
                    ),
                    'std' => 'current',
                    'description' => __('Choose product source', 'impreza-child'),
                ),
                
                // Specific Product ID
                array(
                    'type' => 'autocomplete',
                    'heading' => __('Select Product', 'impreza-child'),
                    'param_name' => 'product_id',
                    'settings' => array(
                        'multiple' => false,
                        'sortable' => false,
                        'unique_values' => true,
                    ),
                    'dependency' => array(
                        'element' => 'source',
                        'value' => 'specific',
                    ),
                    'description' => __('Select a specific product', 'impreza-child'),
                ),
                
                // Show Description
                array(
                    'type' => 'checkbox',
                    'heading' => __('Show Service Description', 'impreza-child'),
                    'param_name' => 'show_description',
                    'value' => array(__('Yes', 'impreza-child') => 'yes'),
                    'std' => 'yes',
                ),
                
                // Show Duration
                array(
                    'type' => 'checkbox',
                    'heading' => __('Show Duration', 'impreza-child'),
                    'param_name' => 'show_duration',
                    'value' => array(__('Yes', 'impreza-child') => 'yes'),
                    'std' => 'yes',
                ),
                
                // Button Text
                array(
                    'type' => 'textfield',
                    'heading' => __('Button Text', 'impreza-child'),
                    'param_name' => 'button_text',
                    'value' => 'Book This Service',
                    'description' => __('Text for the booking button', 'impreza-child'),
                ),
                
                // Card Background Color
                array(
                    'type' => 'colorpicker',
                    'heading' => __('Card Background Color', 'impreza-child'),
                    'param_name' => 'card_bg_color',
                    'value' => '#ffffff',
                    'group' => 'Styling',
                ),
                
                // Card Border Color
                array(
                    'type' => 'colorpicker',
                    'heading' => __('Card Border Color', 'impreza-child'),
                    'param_name' => 'card_border_color',
                    'value' => 'rgba(54, 17, 94, 0.2)',
                    'group' => 'Styling',
                ),
                
                // Badge Background
                array(
                    'type' => 'colorpicker',
                    'heading' => __('Badge Background Color', 'impreza-child'),
                    'param_name' => 'badge_bg_color',
                    'value' => 'rgba(54, 17, 94, 0.05)',
                    'group' => 'Styling',
                ),
                
                // Badge Text Color
                array(
                    'type' => 'colorpicker',
                    'heading' => __('Badge Text Color', 'impreza-child'),
                    'param_name' => 'badge_text_color',
                    'value' => '#36115e',
                    'group' => 'Styling',
                ),
                
                // Button Color
                array(
                    'type' => 'colorpicker',
                    'heading' => __('Button Color', 'impreza-child'),
                    'param_name' => 'button_color',
                    'value' => '#36115e',
                    'group' => 'Styling',
                ),
                
                // Button Text Color
                array(
                    'type' => 'colorpicker',
                    'heading' => __('Button Text Color', 'impreza-child'),
                    'param_name' => 'button_text_color',
                    'value' => '#ffffff',
                    'group' => 'Styling',
                ),
            ),
        ));
        
        // The shortcode is already registered by Product_Service_Recommendations class
        // We just need to make sure WPBakery can use it
    }
}

// Initialize
new Product_Service_WPBakery_Widget();

// Add product autocomplete support for WPBakery
add_filter('vc_autocomplete_product_service_recommendation_product_id_callback', 'impreza_child_product_autocomplete', 10, 1);
add_filter('vc_autocomplete_product_service_recommendation_product_id_render', 'impreza_child_product_render', 10, 1);

function impreza_child_product_autocomplete($search_string) {
    $results = array();
    
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        's' => $search_string,
        'posts_per_page' => 10,
    );
    
    $products = get_posts($args);
    
    if ($products) {
        foreach ($products as $product) {
            $results[] = array(
                'value' => $product->ID,
                'label' => $product->post_title,
            );
        }
    }
    
    return $results;
}

function impreza_child_product_render($data) {
    $value = isset($data['value']) ? $data['value'] : '';
    
    if (!$value) {
        return array();
    }
    
    $product = get_post($value);
    
    if ($product) {
        return array(
            'value' => $product->ID,
            'label' => $product->post_title,
        );
    }
    
    return array();
}
