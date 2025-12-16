<?php
/**
 * Elementor Product Service Recommendation Widget
 */

if (!defined('ABSPATH')) {
    exit;
}

class Elementor_Product_Service_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'product_service_recommendation';
    }
    
    public function get_title() {
        return __('Product Service Recommendation', 'impreza-child');
    }
    
    public function get_icon() {
        return 'eicon-product-info';
    }
    
    public function get_categories() {
        return ['woocommerce-elements'];
    }
    
    public function get_keywords() {
        return ['product', 'service', 'recommendation', 'amelia', 'booking'];
    }
    
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'impreza-child'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'product_source',
            [
                'label' => __('Product Source', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'current',
                'options' => [
                    'current' => __('Current Product Page', 'impreza-child'),
                    'specific' => __('Specific Product', 'impreza-child'),
                ],
            ]
        );
        
        $this->add_control(
            'product_id',
            [
                'label' => __('Product ID', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => '',
                'condition' => [
                    'product_source' => 'specific',
                ],
                'description' => __('Enter the product ID to display its recommended service', 'impreza-child'),
            ]
        );
        
        $this->add_control(
            'show_description',
            [
                'label' => __('Show Description', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'impreza-child'),
                'label_off' => __('No', 'impreza-child'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_duration',
            [
                'label' => __('Show Duration', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'impreza-child'),
                'label_off' => __('No', 'impreza-child'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'button_text',
            [
                'label' => __('Button Text', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Book This Service', 'impreza-child'),
                'placeholder' => __('Enter button text', 'impreza-child'),
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Card Style', 'impreza-child'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'card_background',
            [
                'label' => __('Background Color', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f8f9fa',
                'selectors' => [
                    '{{WRAPPER}} .service-rec-card' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'card_border_radius',
            [
                'label' => __('Border Radius', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .service-rec-card' => 'border-radius: {{SIZE}}{{UNIT}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_shadow',
                'label' => __('Box Shadow', 'impreza-child'),
                'selector' => '{{WRAPPER}} .service-rec-card',
            ]
        );
        
        $this->end_controls_section();
        
        // Title Style
        $this->start_controls_section(
            'title_style',
            [
                'label' => __('Title', 'impreza-child'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => __('Color', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#23282d',
                'selectors' => [
                    '{{WRAPPER}} .service-rec-title' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .service-rec-title',
            ]
        );
        
        $this->end_controls_section();
        
        // Button Style
        $this->start_controls_section(
            'button_style',
            [
                'label' => __('Button', 'impreza-child'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'button_background',
            [
                'label' => __('Background Color', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#36115e',
                'selectors' => [
                    '{{WRAPPER}} .service-rec-book-btn' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .service-rec-book-btn' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'button_hover_background',
            [
                'label' => __('Hover Background', 'impreza-child'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#2a0d4a',
                'selectors' => [
                    '{{WRAPPER}} .service-rec-book-btn:hover' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Determine product ID
        $product_id = null;
        if ($settings['product_source'] === 'specific' && !empty($settings['product_id'])) {
            $product_id = intval($settings['product_id']);
        }
        
        // Get service recommendation class
        if (!class_exists('Product_Service_Recommendations')) {
            return;
        }
        
        $service_rec = new Product_Service_Recommendations();
        $service = $service_rec->get_product_service($product_id);
        
        if (!$service) {
            // Show placeholder in editor
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-info">';
                echo __('No service linked to this product. Configure a service in the product settings.', 'impreza-child');
                echo '</div>';
            }
            return;
        }
        
        // Render the service card
        $service_fee = floatval(get_option('gos_service_fee_amount', 50.00));
        $price = floatval($service->price) + $service_fee;
        $duration = intval($service->duration);
        $hours = floor($duration / 60);
        $minutes = $duration % 60;
        $duration_text = '';
        
        if ($hours > 0) {
            $duration_text = $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
            if ($minutes > 0) {
                $duration_text .= ' ' . $minutes . ' min';
            }
        } else {
            $duration_text = $minutes . ' min';
        }
        
        ?>
        <div class="product-service-recommendation elementor-widget" data-service-id="<?php echo esc_attr($service->id); ?>">
            <div class="service-rec-card">
                <div class="service-rec-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Professional Service Available</span>
                </div>
                
                <div class="service-rec-content">
                    <div class="service-rec-header">
                        <h3 class="service-rec-title"><?php echo esc_html($service->name); ?></h3>
                        <div class="service-rec-meta">
                            <span class="service-rec-price"><?php echo wc_price($price); ?></span>
                            <?php if ($settings['show_duration'] === 'yes' && $duration > 0): ?>
                                <span class="service-rec-duration">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <?php echo esc_html($duration_text); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($settings['show_description'] === 'yes' && !empty($service->description)): ?>
                        <div class="service-rec-description">
                            <?php echo wp_kses_post(wpautop($service->description)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="service-rec-actions">
                        <a href="#" 
                           class="button service-rec-book-btn" 
                           data-service-id="<?php echo esc_attr($service->id); ?>"
                           data-service-name="<?php echo esc_attr($service->name); ?>">
                            <?php echo esc_html($settings['button_text']); ?>
                        </a>
                        <a href="/services/" class="service-rec-learn-more">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    protected function content_template() {
        ?>
        <#
        var showDescription = settings.show_description === 'yes';
        var showDuration = settings.show_duration === 'yes';
        #>
        <div class="product-service-recommendation elementor-widget">
            <div class="service-rec-card">
                <div class="service-rec-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Professional Service Available</span>
                </div>
                
                <div class="service-rec-content">
                    <div class="service-rec-header">
                        <h3 class="service-rec-title">Service Name</h3>
                        <div class="service-rec-meta">
                            <span class="service-rec-price">$99.00</span>
                            <# if (showDuration) { #>
                                <span class="service-rec-duration">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    1 hour
                                </span>
                            <# } #>
                        </div>
                    </div>
                    
                    <# if (showDescription) { #>
                        <div class="service-rec-description">
                            <p>Service description goes here.</p>
                        </div>
                    <# } #>
                    
                    <div class="service-rec-actions">
                        <a href="#" class="button service-rec-book-btn">
                            {{{ settings.button_text }}}
                        </a>
                        <a href="/services/" class="service-rec-learn-more">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
