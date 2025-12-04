<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if (!function_exists('chld_thm_cfg_locale_css')):
	function chld_thm_cfg_locale_css($uri)
	{
		if (empty($uri) && is_rtl() && file_exists(get_template_directory() . '/rtl.css'))
			$uri = get_template_directory_uri() . '/rtl.css';
		return $uri;
	}
endif;
add_filter('locale_stylesheet_uri', 'chld_thm_cfg_locale_css');

// END ENQUEUE PARENT ACTION

/**
 * Theme version - update when making changes
 */
define('IMPREZA_CHILD_VERSION', '1.0.1');

/**
 * Enqueue main global styles and scripts
 */
function impreza_child_enqueue_global_assets()
{
	// Main global styles
	wp_enqueue_style(
		'impreza-child-main',
		get_stylesheet_directory_uri() . '/assets/css/main.min.css',
		array(),
		IMPREZA_CHILD_VERSION
	);

	// Main global scripts
	wp_enqueue_script(
		'impreza-child-main',
		get_stylesheet_directory_uri() . '/assets/js/main.min.js',
		array('jquery'),
		IMPREZA_CHILD_VERSION,
		true
	);
}
add_action('wp_enqueue_scripts', 'impreza_child_enqueue_global_assets', 20);

/**
 * Enqueue Cart Side Drawer styles and scripts
 * Converts the default dropdown cart to a sliding side drawer
 */
function impreza_child_enqueue_cart_drawer()
{
	// Only load on frontend and if WooCommerce is active
	if (is_admin() || ! class_exists('WooCommerce')) {
		return;
	}

	// Enqueue side drawer CSS
	wp_enqueue_style(
		'impreza-child-cart-drawer',
		get_stylesheet_directory_uri() . '/assets/css/cart-side-drawer.min.css',
		array(),
		IMPREZA_CHILD_VERSION
	);

	// Enqueue side drawer JavaScript
	wp_enqueue_script(
		'impreza-child-cart-drawer',
		get_stylesheet_directory_uri() . '/assets/js/cart-side-drawer.min.js',
		array('jquery', 'wc-add-to-cart', 'wc-cart-fragments'),
		IMPREZA_CHILD_VERSION,
		true
	);

	// Enqueue service state manager (shared utility)
	wp_enqueue_script(
		'service-state-manager',
		get_stylesheet_directory_uri() . '/assets/js/service-state-manager.min.js',
		array('jquery'),
		IMPREZA_CHILD_VERSION,
		true
	);
	
	// Localize service state manager
	wp_localize_script('service-state-manager', 'cartServiceRecs', array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'nonce'    => wp_create_nonce('cart_service_recs_nonce')
	));

	// Enqueue cart service recommendations CSS & JS
	wp_enqueue_style(
		'cart-service-recommendations',
		get_stylesheet_directory_uri() . '/assets/css/cart-service-recommendations.min.css',
		array(),
		IMPREZA_CHILD_VERSION
	);

	wp_enqueue_script(
		'cart-service-recommendations',
		get_stylesheet_directory_uri() . '/assets/js/cart-service-recommendations.min.js',
		array('jquery', 'impreza-child-cart-drawer', 'service-state-manager'),
		IMPREZA_CHILD_VERSION,
		true
	);



	// Add inline CSS to disable cart icon until JS initializes
	wp_add_inline_style('impreza-child-cart-drawer', '
		.w-cart-link.cart-initializing {
			pointer-events: none !important;
			opacity: 0.6 !important;
			cursor: not-allowed !important;
		}
	');
}
add_action('wp_enqueue_scripts', 'impreza_child_enqueue_cart_drawer', 20);

/**
 * Add class to cart icon to disable it initially
 */
function impreza_child_disable_cart_icon_initially()
{
	if (! class_exists('WooCommerce')) {
		return;
	}
?>
	<script type="text/javascript">
		(function() {
			// Add initializing class as early as possible
			var style = document.createElement('style');
			style.textContent = '.w-cart-link { pointer-events: none !important; opacity: 0.6 !important; }';
			document.head.appendChild(style);

			// Remove the style once cart drawer is ready (much faster than waiting for full load)
			var checkInterval = setInterval(function() {
				if (document.querySelector('.cart-side-drawer')) {
					style.remove();
					clearInterval(checkInterval);
				}
			}, 50);

			// Fallback: remove after 2 seconds max
			setTimeout(function() {
				style.remove();
				clearInterval(checkInterval);
			}, 2000);
		})();
	</script>
<?php
}
add_action('wp_head', 'impreza_child_disable_cart_icon_initially', 1);

/**
 * Enqueue WooCommerce styles in admin area
 * This ensures proper styling for order details and meta fields
 */
function impreza_child_enqueue_admin_styles() {
	// Only load on admin pages and if WooCommerce is active
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	
	// Enqueue WooCommerce customizations CSS in admin
	wp_enqueue_style(
		'impreza-child-woocommerce-admin',
		get_stylesheet_directory_uri() . '/css/woocommerce.css',
		array(),
		filemtime( get_stylesheet_directory() . '/css/woocommerce.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'impreza_child_enqueue_admin_styles' );

/**
 * Load Cart Enhancements
 * Handles cart display, checkout functionality, and Amelia integration
 */
function impreza_child_load_cart_enhancements()
{
	// Only load if WooCommerce is active
	if (! class_exists('WooCommerce')) {
		return;
	}

	// Include the cart enhancements class
	$cart_enhancements_file = get_stylesheet_directory() . '/includes/cart-enhancements.php';
	if (file_exists($cart_enhancements_file)) {
		require_once $cart_enhancements_file;
	}
}
add_action('after_setup_theme', 'impreza_child_load_cart_enhancements');

/**
 * Load Cart Service Recommendations
 * Recommends Amelia services based on cart products
 */
function impreza_child_load_service_recommendations()
{
	// Only load if WooCommerce is active
	if (! class_exists('WooCommerce')) {
		return;
	}

	// Include the service recommendations class
	$recommendations_file = get_stylesheet_directory() . '/includes/class-cart-service-recommendations.php';
	if (file_exists($recommendations_file)) {
		require_once $recommendations_file;
	}
}
add_action('after_setup_theme', 'impreza_child_load_service_recommendations');

/**
 * Load Product Service Recommendations
 * Displays recommended Amelia services on product pages
 */
function impreza_child_load_product_service_recommendations()
{
	// Only load if WooCommerce is active
	if (! class_exists('WooCommerce')) {
		return;
	}

	// Include the product service recommendations class
	$product_recs_file = get_stylesheet_directory() . '/includes/class-product-service-recommendations.php';
	if (file_exists($product_recs_file)) {
		require_once $product_recs_file;
	}
}
add_action('after_setup_theme', 'impreza_child_load_product_service_recommendations');

/**
 * Load Booking Popup Handler
 * Manages incomplete service bookings and confirmation popups
 */
function impreza_child_load_booking_popup_handler()
{
	// Only load if WooCommerce is active
	if (! class_exists('WooCommerce')) {
		return;
	}
}

/**
 * Enqueue Cart & Checkout Enhancements
 */
function impreza_child_enqueue_cart_checkout_assets()
{
	// Only load on cart or checkout pages
	if (!is_cart() && !is_checkout()) {
		return;
	}

	wp_enqueue_style(
		'cart-enhancements',
		get_stylesheet_directory_uri() . '/assets/css/cart-enhancements.min.css',
		array(),
		IMPREZA_CHILD_VERSION
	);

	wp_enqueue_script(
		'cart-enhancements',
		get_stylesheet_directory_uri() . '/assets/js/cart-enhancements.min.js',
		array('jquery'),
		IMPREZA_CHILD_VERSION,
		true
	);
}
add_action('wp_enqueue_scripts', 'impreza_child_enqueue_cart_checkout_assets');

/**
 * Enqueue Product Service Recommendations Assets
 */
function impreza_child_enqueue_product_service_assets()
{
	// Only load on product pages
	if (!is_product() || !class_exists('WooCommerce')) {
		return;
	}

	wp_enqueue_style(
		'product-service-recommendations',
		get_stylesheet_directory_uri() . '/assets/css/product-service-recommendations.min.css',
		array(),
		IMPREZA_CHILD_VERSION
	);

	// Enqueue service state manager (shared utility)
	wp_enqueue_script(
		'service-state-manager',
		get_stylesheet_directory_uri() . '/assets/js/service-state-manager.min.js',
		array('jquery'),
		IMPREZA_CHILD_VERSION,
		true
	);

	wp_enqueue_script(
		'product-service-recommendations',
		get_stylesheet_directory_uri() . '/assets/js/product-service-recommendations.min.js',
		array('jquery', 'service-state-manager'),
		IMPREZA_CHILD_VERSION,
		true
	);
}
add_action('wp_enqueue_scripts', 'impreza_child_enqueue_product_service_assets');

/**
 * Register Elementor Widgets
 */
function impreza_child_register_elementor_widgets()
{
	// Make sure Elementor is active
	if (! did_action('elementor/loaded')) {
		return;
	}

	// Include widget file
	$widget_file = get_stylesheet_directory() . '/includes/elementor-widgets/product-service-widget.php';
	if (file_exists($widget_file)) {
		require_once $widget_file;

		// Register widget
		\Elementor\Plugin::instance()->widgets_manager->register(new \Elementor_Product_Service_Widget());
	}
}
add_action('elementor/widgets/register', 'impreza_child_register_elementor_widgets');

/**
 * Register WPBakery Widgets
 */
function impreza_child_register_wpbakery_widgets()
{
	// Make sure WPBakery is active
	if (! function_exists('vc_map')) {
		return;
	}

	// Include widget file
	$widget_file = get_stylesheet_directory() . '/includes/wpbakery-widgets/product-service-widget.php';
	if (file_exists($widget_file)) {
		require_once $widget_file;
	}
}
add_action('vc_before_init', 'impreza_child_register_wpbakery_widgets');

/**
 * Add Amelia Service dropdown field to WooCommerce products
 */
function impreza_add_amelia_service_field()
{
	// Make sure WooCommerce functions are available
	if (! function_exists('woocommerce_wp_select')) {
		return;
	}

	global $wpdb;

	// Get all Amelia services
	$amelia_table = $wpdb->prefix . 'amelia_services';
	$services = array('' => '-- No service --');

	// Check if Amelia table exists
	if ($wpdb->get_var("SHOW TABLES LIKE '$amelia_table'") == $amelia_table) {
		$amelia_services = $wpdb->get_results(
			"SELECT s.id, s.name, s.price, s.duration, c.name as category_name
			 FROM $amelia_table s
			 LEFT JOIN {$wpdb->prefix}amelia_categories c ON s.categoryId = c.id
			 WHERE s.status = 'visible'
			 ORDER BY c.name ASC, s.name ASC"
		);

		if ($amelia_services) {
			foreach ($amelia_services as $service) {
				$price = $service->price ? ' - $' . number_format($service->price, 2) : '';
				$duration = $service->duration ? ' (' . round($service->duration / 60) . ' min)' : '';
				$category = $service->category_name ? '[' . $service->category_name . '] ' : '';
				$label = $category . $service->name . $duration . $price;
				$services[$service->id] = $label;
			}
		}
	}

	echo '<div class="options_group">';

	woocommerce_wp_select(array(
		'id'          => '_amelia_service_id',
		'label'       => __('Related Amelia Service', 'woocommerce'),
		'description' => __('Select a service to recommend when this product is added to cart', 'woocommerce'),
		'desc_tip'    => true,
		'options'     => $services,
		'value'       => get_post_meta(get_the_ID(), '_amelia_service_id', true)
	));

	echo '</div>';
}
add_action('woocommerce_product_options_general_product_data', 'impreza_add_amelia_service_field');

/**
 * Save Amelia Service field for WooCommerce products
 */
function impreza_save_amelia_service_field($post_id)
{
	$amelia_service_id = isset($_POST['_amelia_service_id']) ? sanitize_text_field($_POST['_amelia_service_id']) : '';
	update_post_meta($post_id, '_amelia_service_id', $amelia_service_id);
}
add_action('woocommerce_process_product_meta', 'impreza_save_amelia_service_field');

/**
 * Hide specific Amelia appointment fields from cart display
 * This filters WCPA's output which wraps Amelia data
 */
add_filter('woocommerce_get_item_data', 'impreza_child_hide_amelia_fields', 999, 2);
function impreza_child_hide_amelia_fields($item_data, $cart_item)
{
	// Only process if there's Amelia booking data
	if (! isset($cart_item['ameliabooking']) && ! isset($cart_item['wcpa_data'])) {
		return $item_data;
	}

	// Loop through the item data
	foreach ($item_data as $key => $data) {
		// Check if the value contains HTML
		if (isset($data['value']) && is_string($data['value'])) {
			// Remove employee line (case insensitive)
			$filtered_value = preg_replace(
				'/<p><strong>employee:<\/strong>.*?<\/p>\s*/i',
				'',
				$data['value']
			);

			// Remove Total Number of People line (case insensitive)
			$filtered_value = preg_replace(
				'/<p><strong>Total Number of People:<\/strong>.*?<\/p>\s*/i',
				'',
				$filtered_value
			);

			// Update the value
			$item_data[$key]['value'] = $filtered_value;
		}
	}

	return $item_data;
}

/**
 * Alternative: Filter WCPA data before it's rendered
 * This modifies the raw data that WCPA uses
 */
add_filter('wcpa_cart_item_meta_data', 'impreza_child_filter_wcpa_amelia_data', 10, 2);
function impreza_child_filter_wcpa_amelia_data($meta_data, $cart_item)
{
	// Only process if there's Amelia booking data
	if (! isset($cart_item['ameliabooking'])) {
		return $meta_data;
	}

	// Filter the meta data
	if (is_array($meta_data)) {
		foreach ($meta_data as $key => $data) {
			if (isset($data['value']) && is_string($data['value'])) {
				// Remove employee and Total Number of People
				$filtered_value = preg_replace(
					'/<p><strong>employee:<\/strong>.*?<\/p>\s*/i',
					'',
					$data['value']
				);
				$filtered_value = preg_replace(
					'/<p><strong>Total Number of People:<\/strong>.*?<\/p>\s*/i',
					'',
					$filtered_value
				);

				$meta_data[$key]['value'] = $filtered_value;
			}
		}
	}

	return $meta_data;
}

/**
 * Filter Amelia appointment labels to remove employee and persons count
 * This hooks into Amelia's data generation before it's displayed
 */
add_filter('woocommerce_get_item_data', 'impreza_child_filter_amelia_appointment_labels', 999, 2);
function impreza_child_filter_amelia_appointment_labels($item_data, $cart_item)
{
	// Only process Amelia appointments
	if (! isset($cart_item['ameliabooking'])) {
		return $item_data;
	}

	// Loop through item data and filter the values
	foreach ($item_data as $key => $data) {
		if (! isset($data['value']) || ! is_string($data['value'])) {
			continue;
		}

		// Split by PHP_EOL or newlines to get individual lines
		$lines = preg_split('/\r\n|\r|\n/', $data['value']);
		$filtered_lines = array();

		foreach ($lines as $line) {
			$line_lower = strtolower($line);

			// Skip lines containing employee or total number of people
			if (strpos($line_lower, 'employee') !== false && strpos($line_lower, '</strong>') !== false) {
				continue;
			}
			if (strpos($line_lower, 'total number of people') !== false) {
				continue;
			}

			$filtered_lines[] = $line;
		}

		// Update the value with filtered lines
		$item_data[$key]['value'] = implode(PHP_EOL, $filtered_lines);
	}

	return $item_data;
}

/**
 * Filter order item meta to hide employee and persons fields
 * This applies when orders are placed and displayed
 */
add_filter('woocommerce_order_item_display_meta_key', 'impreza_child_filter_order_meta_key', 20, 3);
function impreza_child_filter_order_meta_key($display_key, $meta, $item)
{
	// Hide these specific meta keys
	$hidden_keys = array('employee', 'Employee', 'Total Number of People', 'total number of people');

	if (in_array($meta->key, $hidden_keys, true)) {
		return false; // Return false to hide this meta key
	}

	return $display_key;
}

/**
 * Filter order item meta value to remove employee and persons lines from HTML
 * This applies to the Appointment Info field that contains all the details
 */
add_filter('woocommerce_order_item_display_meta_value', 'impreza_child_filter_order_meta_value', 20, 3);
function impreza_child_filter_order_meta_value($display_value, $meta, $item)
{
	// Only process if it's a string value
	if (! is_string($display_value)) {
		return $display_value;
	}

	// Remove employee line with regex (case insensitive)
	$filtered_value = preg_replace(
		'/<br\s*\/?>\s*<strong>employee:<\/strong>.*?(?=<br|$)/i',
		'',
		$display_value
	);

	// Also try without <br> tags
	$filtered_value = preg_replace(
		'/<strong>employee:<\/strong>.*?<br\s*\/?>/i',
		'',
		$filtered_value
	);

	// Remove Total Number of People line
	$filtered_value = preg_replace(
		'/<br\s*\/?>\s*<strong>Total Number of People:<\/strong>.*?(?=<br|$)/i',
		'',
		$filtered_value
	);

	// Also try without <br> tags
	$filtered_value = preg_replace(
		'/<strong>Total Number of People:<\/strong>.*?<br\s*\/?>/i',
		'',
		$filtered_value
	);

	// Clean up any double <br> tags
	$filtered_value = preg_replace('/<br\s*\/?>\s*<br\s*\/?>/', '<br>', $filtered_value);

	return $filtered_value;
}

/**
 * Prevent employee and persons fields from being saved to order meta
 * This runs when the order is being created
 */
add_action('woocommerce_checkout_create_order_line_item', 'impreza_child_filter_order_line_item_meta', 20, 4);
function impreza_child_filter_order_line_item_meta($item, $cart_item_key, $values, $order)
{
	// Get all item meta
	$item_meta = $item->get_meta_data();

	foreach ($item_meta as $meta) {
		$meta_key = $meta->key;
		$meta_value = $meta->value;

		// Remove specific meta keys
		if (in_array(strtolower($meta_key), array('employee', 'total number of people'))) {
			$item->delete_meta_data($meta_key);
			continue;
		}

		// Filter values that contain HTML with employee/persons info
		if (is_string($meta_value) && (strpos($meta_value, '<strong>employee:</strong>') !== false || strpos($meta_value, '<strong>Total Number of People:</strong>') !== false)) {
			// Remove employee line
			$filtered_value = preg_replace(
				'/<br\s*\/?>\s*<strong>employee:<\/strong>.*?(?=<br|$)/i',
				'',
				$meta_value
			);
			$filtered_value = preg_replace(
				'/<strong>employee:<\/strong>.*?<br\s*\/?>/i',
				'',
				$filtered_value
			);

			// Remove Total Number of People line
			$filtered_value = preg_replace(
				'/<br\s*\/?>\s*<strong>Total Number of People:<\/strong>.*?(?=<br|$)/i',
				'',
				$filtered_value
			);
			$filtered_value = preg_replace(
				'/<strong>Total Number of People:<\/strong>.*?<br\s*\/?>/i',
				'',
				$filtered_value
			);

			// Clean up double breaks
			$filtered_value = preg_replace('/<br\s*\/?>\s*<br\s*\/?>/', '<br>', $filtered_value);

			// Update the meta value
			$item->update_meta_data($meta_key, $filtered_value);
		}
	}
}

/**
 * Format order item meta labels - remove "Add-on:" prefix and clean up labels
 */
add_filter('woocommerce_order_item_display_meta_key', 'impreza_child_format_order_meta_labels', 30, 3);
function impreza_child_format_order_meta_labels($display_key, $meta, $item)
{
	// Remove "Add-on: " prefix from labels
	$display_key = preg_replace('/^Add-on:\s*/i', '', $display_key);

	// Remove trailing colons and extra spaces
	$display_key = preg_replace('/:+$/', '', $display_key);
	$display_key = trim($display_key);

	// If the label is just ":" or empty, hide it
	if ($display_key === ':' || $display_key === '') {
		return false;
	}

	return $display_key;
}

/**
 * Format order item meta values - clean up the display
 * This filter runs when displaying meta on order received page and emails
 */
add_filter('woocommerce_order_item_display_meta_value', 'impreza_child_format_order_meta_values', 30, 3);
function impreza_child_format_order_meta_values($display_value, $meta, $item)
{
	if (! is_string($display_value)) {
		return $display_value;
	}

	// Remove employee lines - match exact pattern from rendered HTML
	// Pattern 1: <br><br><strong>employee:</strong> Name<br>
	$display_value = preg_replace(
		'/<br\s*\/?>\s*<br\s*\/?>\s*<strong>employee:<\/strong>\s*[^<]*<br\s*\/?>/i',
		'',
		$display_value
	);

	// Pattern 2: <br><strong>employee:</strong> Name<br>
	$display_value = preg_replace(
		'/<br\s*\/?>\s*<strong>employee:<\/strong>\s*[^<]*<br\s*\/?>/i',
		'',
		$display_value
	);

	// Pattern 3: Without <strong> tags
	$display_value = preg_replace(
		'/<br\s*\/?>\s*<br\s*\/?>\s*employee:\s*[^<]*<br\s*\/?>/i',
		'',
		$display_value
	);

	// Remove Total Number of People lines
	// Pattern 1: <br><br><strong>Total Number of People:</strong> 1
	$display_value = preg_replace(
		'/<br\s*\/?>\s*<br\s*\/?>\s*<strong>Total Number of People:<\/strong>\s*[^<]*(?:<br|$)/i',
		'',
		$display_value
	);

	// Pattern 2: <br><strong>Total Number of People:</strong> 1
	$display_value = preg_replace(
		'/<br\s*\/?>\s*<strong>Total Number of People:<\/strong>\s*[^<]*(?:<br|$)/i',
		'',
		$display_value
	);

	// Pattern 3: Without <strong> tags
	$display_value = preg_replace(
		'/<br\s*\/?>\s*<br\s*\/?>\s*Total Number of People:\s*[^<]*(?:<br|$)/i',
		'',
		$display_value
	);

	// Remove "Add-on: " prefix from values wrapped in <p> tags
	$display_value = preg_replace('/<p>Add-on:\s*/i', '<p>', $display_value);

	// Clean up duplicate addon text in values like "Cable Concealment - Hide wires... (+$40.00)"
	// If value already contains the price, remove the repeated description
	if (preg_match('/\(\+\$[\d.]+\)/', $display_value)) {
		// Extract just the price part
		preg_match('/^(.*?)\s*\(\+\$[\d.]+\)$/', strip_tags($display_value), $matches);
		if (isset($matches[1])) {
			// Keep the original HTML structure but clean the text
			$display_value = preg_replace(
				'/^(.*?)\s*-\s*(.*?)\s*(\(\+\$[\d.]+\))$/',
				'$1 $3',
				strip_tags($display_value)
			);
		}
	}

	// Clean up multiple consecutive line breaks (3+ becomes 2)
	$display_value = preg_replace('/(<br\s*\/?>\s*){3,}/', '<br><br>', $display_value);

	// Remove leading line breaks
	$display_value = preg_replace('/^(<br\s*\/?>)+/', '', $display_value);

	// Remove excessive trailing line breaks (keep max 2)
	$display_value = preg_replace('/(<br\s*\/?>\s*){3,}$/', '<br><br>', $display_value);

	return $display_value;
}

/**
 * Hide employee and Total Number of People from order item meta display
 * This applies to order received page and order emails
 * Using woocommerce_hidden_order_itemmeta for universal coverage
 */
add_filter('woocommerce_hidden_order_itemmeta', 'impreza_child_hide_order_item_meta_keys', 10, 1);
function impreza_child_hide_order_item_meta_keys($hidden_meta_keys)
{
	// Add the meta keys we want to hide
	$hidden_meta_keys[] = 'employee';
	$hidden_meta_keys[] = 'Employee';
	$hidden_meta_keys[] = 'Total Number of People';
	$hidden_meta_keys[] = 'total number of people';

	// Hide internal WCPA/custom field keys that have display label duplicates
	// Pattern: _gos_cf_* (internal keys) - these have duplicate display versions
	$hidden_meta_keys[] = '_gos_cf_70';   // TV Size internal key
	$hidden_meta_keys[] = '_gos_cf_71';   // Surface type internal key
	$hidden_meta_keys[] = '_gos_cf_72';   // Dismount internal key
	$hidden_meta_keys[] = '_gos_cf_74';   // TV mount internal key
	$hidden_meta_keys[] = '_gos_cf_77';   // Helper available internal key
	$hidden_meta_keys[] = '_gos_cf_99';   // Mount in place internal key
	$hidden_meta_keys[] = '_gos_extra_1'; // Cable concealment internal key
	$hidden_meta_keys[] = '_gos_extra_3'; // Network connection internal key
	$hidden_meta_keys[] = '_gos_booking_id'; // Booking ID internal key

	// Also hide any meta key that starts with just ":" (colon only)
	// These are the keys with no proper label

	return $hidden_meta_keys;
}

/**
 * Additional filter at display time to clean HTML output
 * This catches any remaining employee/people info that made it through
 */
add_filter('woocommerce_display_item_meta', 'impreza_child_clean_item_meta_html', 20, 3);
function impreza_child_clean_item_meta_html($html, $item, $args)
{
	if (empty($html)) {
		return $html;
	}

	// Remove employee lines from final HTML output
	$html = preg_replace(
		'/<br\s*\/?>\s*<br\s*\/?>\s*<strong>employee:<\/strong>\s*[^<]*<br\s*\/?>/i',
		'',
		$html
	);
	$html = preg_replace(
		'/<br\s*\/?>\s*<strong>employee:<\/strong>\s*[^<]*<br\s*\/?>/i',
		'',
		$html
	);

	// Remove Total Number of People lines from final HTML output
	$html = preg_replace(
		'/<br\s*\/?>\s*<br\s*\/?>\s*<strong>Total Number of People:<\/strong>\s*[^<]*(?:<br|$)/i',
		'',
		$html
	);
	$html = preg_replace(
		'/<br\s*\/?>\s*<strong>Total Number of People:<\/strong>\s*[^<]*(?:<br|$)/i',
		'',
		$html
	);

	// Clean up excessive line breaks
	$html = preg_replace('/(<br\s*\/?>\s*){3,}/', '<br><br>', $html);
	
	// Remove leading <br> tags
	$html = preg_replace('/^(\s*<br\s*\/?>\s*)+/', '', $html);
	
	// Remove trailing <br> tags
	$html = preg_replace('/(\s*<br\s*\/?>\s*)+$/', '', $html);

	return $html;
}

/**
 * Filter formatted order item meta to clean up content
 * This removes employee/people info from within meta values
 */
add_filter('woocommerce_order_item_get_formatted_meta_data', 'impreza_child_hide_order_meta_fields', 10, 2);
function impreza_child_hide_order_meta_fields($formatted_meta, $item)
{
	if (! is_array($formatted_meta)) {
		return $formatted_meta;
	}

	foreach ($formatted_meta as $key => $meta) {
		// Skip if no display value
		if (! isset($meta->display_value) || ! is_string($meta->display_value)) {
			continue;
		}

		// Check the display_key (label) for unwanted fields
		$label_lower = strtolower($meta->display_key);

		// Remove entries with just ":" as the label (duplicates with internal keys)
		if (trim($meta->display_key) === ':') {
			unset($formatted_meta[$key]);
			continue;
		}

		// Remove employee and people count fields
		if (
			strpos($label_lower, 'employee') !== false ||
			strpos($label_lower, 'total number of people') !== false
		) {
			unset($formatted_meta[$key]);
			continue;
		}

		// Remove "Add-on:" prefix from display labels
		if (strpos($meta->display_key, 'Add-on:') === 0) {
			$meta->display_key = trim(str_replace('Add-on:', '', $meta->display_key));
		}

		// Clean the value of embedded employee/people info
		$original_value = $meta->display_value;
		$cleaned_value = $meta->display_value;

		// Remove employee lines - match the exact pattern from the HTML
		// Pattern: <br><br><strong>employee:</strong> David Vidović<br>
		$cleaned_value = preg_replace(
			'/<br\s*\/?>\s*<br\s*\/?>\s*<strong>employee:<\/strong>[^<]*<br\s*\/?>/i',
			'',
			$cleaned_value
		);

		// Also match without double br at start
		$cleaned_value = preg_replace(
			'/<br\s*\/?>\s*<strong>employee:<\/strong>[^<]*<br\s*\/?>/i',
			'',
			$cleaned_value
		);

		// Remove Total Number of People lines
		// Pattern: <br><br><strong>Total Number of People:</strong> 1
		$cleaned_value = preg_replace(
			'/<br\s*\/?>\s*<br\s*\/?>\s*<strong>Total Number of People:<\/strong>[^<]*(?:<br|$)/i',
			'',
			$cleaned_value
		);

		// Also match without double br at start
		$cleaned_value = preg_replace(
			'/<br\s*\/?>\s*<strong>Total Number of People:<\/strong>[^<]*(?:<br|$)/i',
			'',
			$cleaned_value
		);

		// Clean up multiple consecutive line breaks (3 or more becomes 2)
		$cleaned_value = preg_replace('/(<br\s*\/?>\s*){3,}/', '<br><br>', $cleaned_value);

		// Clean up leading line breaks
		$cleaned_value = preg_replace('/^(<br\s*\/?>)+/', '', $cleaned_value);

		// Clean up trailing line breaks (but keep some spacing)
		$cleaned_value = preg_replace('/(<br\s*\/?>\s*){3,}$/', '<br><br>', $cleaned_value);

		// Strip HTML tags from the display value to show clean text
		// This converts <p>32\" (+$50.00)</p> to just 32" (+$50.00)
		$cleaned_value = strip_tags($cleaned_value);

		// Remove backslashes (unescape quotes)
		$cleaned_value = stripslashes($cleaned_value);

		// Update the meta value
		$formatted_meta[$key]->display_value = $cleaned_value;
	}

	return $formatted_meta;
}

/**
 * Filter the order item name display to remove appointment info
 */
add_filter('woocommerce_order_item_name', 'impreza_child_filter_order_item_name', 10, 3);
function impreza_child_filter_order_item_name($item_name, $item, $is_visible)
{
	// Remove employee and Total Number of People from the item name
	$cleaned_name = preg_replace(
		'/<br\s*\/?>\s*<br\s*\/?>\s*<strong>employee:<\/strong>[^<]*<br\s*\/?>/i',
		'',
		$item_name
	);

	$cleaned_name = preg_replace(
		'/<br\s*\/?>\s*<strong>employee:<\/strong>[^<]*<br\s*\/?>/i',
		'',
		$cleaned_name
	);

	$cleaned_name = preg_replace(
		'/<br\s*\/?>\s*<br\s*\/?>\s*<strong>Total Number of People:<\/strong>[^<]*(?:<br|$)/i',
		'',
		$cleaned_name
	);

	$cleaned_name = preg_replace(
		'/<br\s*\/?>\s*<strong>Total Number of People:<\/strong>[^<]*(?:<br|$)/i',
		'',
		$cleaned_name
	);

	return $cleaned_name;
}

/**
 * Filter the entire order table HTML to remove employee/people fields
 * This catches content added after our other filters run
 */
add_filter('woocommerce_order_item_meta_end', 'impreza_child_clean_after_item_meta', 10, 3);
function impreza_child_clean_after_item_meta($item_id, $item, $order)
{
	// Placeholder hook for monitoring content added after item meta
	// Currently no action needed as output buffering handles cleanup
}

/**
 * Filter the product name output in order tables to remove appointment info
 * This uses output buffering to catch everything
 */
add_action('woocommerce_order_item_meta_start', 'impreza_child_start_item_buffer', 1, 3);
function impreza_child_start_item_buffer($item_id, $item, $order)
{
	ob_start();
}

add_action('woocommerce_order_item_meta_end', 'impreza_child_end_item_buffer_and_clean', 999, 3);
function impreza_child_end_item_buffer_and_clean($item_id, $item, $order)
{
	
	$content = ob_get_clean();

	
	$cleaned = $content;

	// Pattern 1: Remove employee line (may or may not have <br> before it)
	// Matches: <strong>employee:</strong> David Vidović<br>
	// Also matches: <br><strong>employee:</strong> David Vidović<br>
	$cleaned = preg_replace(
		'/(?:<br\s*\/?>\s*)?<strong>employee:<\/strong>[^<\r\n]*(?:<br\s*\/?>|$)/i',
		'',
		$cleaned
	);

	// Pattern 2: Remove Total Number of People line (may or may not have <br> before it)
	// Matches: <strong>Total Number of People:</strong> 1
	// Also matches: <br><strong>Total Number of People:</strong> 1<br>
	$cleaned = preg_replace(
		'/(?:<br\s*\/?>\s*)?<strong>Total Number of People:<\/strong>[^<\r\n]*(?:<br\s*\/?>|$)/i',
		'',
		$cleaned
	);

	// Clean up excessive <br> tags
	// Remove 3+ consecutive <br> tags (reduce to 2)
	$cleaned = preg_replace('/(<br\s*\/?>\s*){3,}/', '<br><br>', $cleaned);
	
	// Remove leading <br> tags at the start
	$cleaned = preg_replace('/^(\s*<br\s*\/?>\s*)+/', '', $cleaned);
	
	// Remove trailing <br> tags at the end
	$cleaned = preg_replace('/(\s*<br\s*\/?>\s*)+$/', '', $cleaned);
	
	// Clean up <br> tags around "Appointment Info" text
	$cleaned = preg_replace('/<br\s*\/?>\s*Appointment Info\s*<br\s*\/?>/i', '<br>Appointment Info<br>', $cleaned);
	$cleaned = preg_replace('/Appointment Info(\s*<br\s*\/?>\s*){2,}/', 'Appointment Info<br><br>', $cleaned);

	echo $cleaned;
}

/**
 * Final cleanup using output buffering on the entire page
 * This catches appointment info added after all WooCommerce hooks
 */
add_action('template_redirect', 'impreza_child_start_final_buffer');
function impreza_child_start_final_buffer()
{
	// Only buffer on order received page
	if (is_wc_endpoint_url('order-received') || is_order_received_page()) {
		ob_start('impreza_child_clean_final_output');
	}
}

function impreza_child_clean_final_output($buffer)
{
	// Remove employee line - match with optional <br> before/after
	$buffer = preg_replace(
		'/(?:<br\s*\/?>\s*)?<strong>employee:<\/strong>[^<\r\n]*(?:<br\s*\/?>)?/i',
		'',
		$buffer
	);

	// Remove Total Number of People line - match with optional <br> before/after  
	$buffer = preg_replace(
		'/(?:<br\s*\/?>\s*)?<strong>Total Number of People:<\/strong>[^<\r\n]*(?:<br\s*\/?>)?/i',
		'',
		$buffer
	);

	// Clean up excessive <br> tags in appointment info sections
	// Remove 4+ consecutive <br> tags (reduce to 2)
	$buffer = preg_replace('/(<br\s*\/?>\s*){4,}/', '<br><br>', $buffer);
	
	// Clean up 3 consecutive <br> tags (reduce to 2)
	$buffer = preg_replace('/(<br\s*\/?>\s*){3}/', '<br><br>', $buffer);
	
	// Clean up <br> tags around "Appointment Info" section
	$buffer = preg_replace('/<br\s*\/?>\s*Appointment Info\s*(<br\s*\/?>\s*){2,}/', '<br>Appointment Info<br><br>', $buffer);
	
	// Clean up excessive breaks before <hr> tags in appointment info
	$buffer = preg_replace('/(<br\s*\/?>\s*){2,}<hr/', '<br><hr', $buffer);
	
	// Clean up excessive breaks after <hr> tags
	$buffer = preg_replace('/<hr[^>]*>(\s*<br\s*\/?>\s*){2,}/', '<hr style="margin-top: 16px; margin-bottom: 10px"><br>', $buffer);

	return $buffer;
}

/**
 * Clean appointment info from WooCommerce email content
 * Applies to all WooCommerce order emails (new order, processing, completed, etc.)
 */
add_filter('woocommerce_mail_content', 'impreza_child_clean_email_content');
function impreza_child_clean_email_content($email_content)
{
	// Remove employee line - match with optional line breaks and various HTML formatting
	$email_content = preg_replace(
		'/(?:<br\s*\/?>\s*)?<strong>employee:<\/strong>[^<\r\n]*(?:<br\s*\/?>)?/i',
		'',
		$email_content
	);

	// Remove Total Number of People line
	$email_content = preg_replace(
		'/(?:<br\s*\/?>\s*)?<strong>Total Number of People:<\/strong>[^<\r\n]*(?:<br\s*\/?>)?/i',
		'',
		$email_content
	);

	// Plain text version - remove lines starting with "employee:" or "Total Number of People:"
	$email_content = preg_replace(
		'/^employee:.*$/im',
		'',
		$email_content
	);

	$email_content = preg_replace(
		'/^Total Number of People:.*$/im',
		'',
		$email_content
	);

	// Clean up multiple consecutive line breaks
	$email_content = preg_replace('/(<br\s*\/?>\s*){3,}/', '<br><br>', $email_content);
	$email_content = preg_replace('/\n\n\n+/', "\n\n", $email_content);

	return $email_content;
}

/**
 * Ensure our meta filters apply to email context
 * Force the formatted meta filter to run even in email templates
 */
add_action('woocommerce_email_order_details', 'impreza_child_ensure_email_meta_cleaned', 5);
function impreza_child_ensure_email_meta_cleaned($order)
{
	// This hook fires before email order details are rendered
	// Our existing woocommerce_order_item_get_formatted_meta_data filter
	// will automatically apply when the email template loops through items

	// Nothing to do here - just ensuring the hook priority is set correctly
	// so our filters run before the email content is generated
}
