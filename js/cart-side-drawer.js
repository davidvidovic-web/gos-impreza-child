/**
 * Cart Side Drawer - Child Theme
 * Converts Impreza's dropdown cart to a sliding side drawer
 * Fully compatible with Impreza's cart system and builder
 */

(function($) {
	'use strict';

	var CartSideDrawer = {

		// Initialize
		init: function() {
			// Wait for parent theme to initialize
			this.waitForParent(function() {
				this.createDrawer();
				this.bindEvents();
				this.overrideParentBehavior();
			}.bind(this));
		},

	// Wait for parent theme's cart to be ready
	waitForParent: function(callback) {
		var attempts = 0;
		var maxAttempts = 50; // 5 seconds
		
		var checkInterval = setInterval(function() {
			attempts++;
			if ($('.w-cart').length > 0 && $('.w-cart-link').length > 0) {
				clearInterval(checkInterval);
				callback();
			} else if (attempts >= maxAttempts) {
				clearInterval(checkInterval);
			}
		}, 100);
	},		// Create drawer HTML structure using Impreza's cart dropdown
		createDrawer: function() {
			if ($('.cart-side-drawer').length > 0) {
				return; // Already exists
			}

			// Get Impreza's dropdown cart content
			var $existingDropdown = $('.w-cart-dropdown').first();
			var dropdownContent = $existingDropdown.length ? $existingDropdown.html() : '';

		var drawerHTML = 
			'<div class="cart-drawer-overlay"></div>' +
			'<div class="cart-side-drawer">' +
				'<div class="cart-drawer-header">' +
					'<h3>Shopping Cart</h3>' +
					'<button class="cart-drawer-close" aria-label="Close cart">×</button>' +
				'</div>' +
				'<div class="cart-drawer-content">' +
					dropdownContent +
				'</div>' +
			'</div>';

		$('body').append(drawerHTML);

		// Cache elements
		this.$drawer = $('.cart-side-drawer');
		this.$overlay = $('.cart-drawer-overlay');
		this.$content = $('.cart-drawer-content');			// Store reference to Impreza's cart
			this.$wcart = $('.w-cart').first();
		},

	// Bind all events
	bindEvents: function() {
		var self = this;

		// Close drawer on overlay click
		this.$overlay.on('click', function() {
			self.closeDrawer();
		});

		// Close drawer on close button click
		$(document).on('click', '.cart-drawer-close', function() {
			self.closeDrawer();
		});

		// Close on ESC key
		$(document).on('keydown', function(e) {
			if (e.keyCode === 27 && self.$drawer.hasClass('active')) {
				self.closeDrawer();
			}
		});

		// WooCommerce events - hook into Impreza's cart system
		$(document.body).on('added_to_cart', function(e, fragments, cart_hash, $button) {
			self.handleAddToCart(fragments, $button);
		});
		
		// Prevent default form submission on product pages - force AJAX
		$(document).on('submit', 'form.cart', function(e) {
			var $form = $(this);
			var $btn = $form.find('.single_add_to_cart_button');
			
			// Check if it's a simple product (has AJAX support)
			if ($btn.hasClass('ajax_add_to_cart') || !$btn.hasClass('disabled')) {
				// Let WooCommerce handle AJAX add to cart
				return true;
			}
			
			// For other product types, convert to AJAX
			if ($btn.length && !$btn.hasClass('disabled')) {
				e.preventDefault();
				
				var product_id = $form.find('[name="add-to-cart"]').val() || $form.find('[name="product_id"]').val();
				var quantity = $form.find('[name="quantity"]').val() || 1;
				var variation_id = $form.find('[name="variation_id"]').val() || 0;
				var variation = {};
				
				// Get variation data
				$form.find('.variations select').each(function() {
					var $select = $(this);
					variation[$select.attr('name')] = $select.val();
				});
				
				// Add loading state
				$btn.addClass('loading').prop('disabled', true);
				
				// AJAX add to cart
				$.ajax({
					url: wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
					type: 'POST',
					data: {
						product_id: product_id,
						quantity: quantity,
						variation_id: variation_id,
						variation: variation
					},
					success: function(response) {
						if (response.error) {
							alert(response.error_message || 'Error adding to cart');
							$btn.removeClass('loading').prop('disabled', false);
						} else {
							// Trigger added to cart event
							$(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
							$btn.removeClass('loading').prop('disabled', false);
						}
					},
					error: function() {
						alert('Error adding to cart');
						$btn.removeClass('loading').prop('disabled', false);
					}
				});
				
				return false;
			}
		});

		// Update drawer when Impreza updates cart fragments
		$(document.body).on('wc_fragments_refreshed wc_fragments_loaded', function() {
			self.syncFromDropdown();
		});
		
		// Reinitialize collapsible sections on cart/checkout updates
		$(document.body).on('updated_cart_totals updated_checkout', function() {
			self.initCollapsibleSections();
		});
	},

	// Override parent theme cart behavior
	overrideParentBehavior: function() {
		var self = this;
		
		// Prevent the default dropdown from showing
		$('.w-cart').removeClass('opened');
		
		// Remove hover events that show dropdown (Impreza uses hover on desktop)
		$('.w-cart').off('mouseenter mouseleave');
		
		// Check if we're on cart or checkout pages
		var isCartOrCheckout = $('body').hasClass('woocommerce-cart') || 
		                       $('body').hasClass('woocommerce-checkout');
		
		// If on cart/checkout, restore normal cart link behavior
		if (isCartOrCheckout) {
			// Restore original href if it was modified
			$('.w-cart-link').each(function() {
				var $link = $(this);
				var originalHref = $link.attr('data-original-href');
				if (originalHref) {
					$link.attr('href', originalHref);
				}
			});
			// Don't override click behavior - let it navigate to cart page
			return;
		}
		
		// Impreza's cart link structure: <a class="w-cart-link" href="/cart">
		// We need to prevent navigation but keep the element clickable
		
		// Method 1: Replace href with javascript:void(0)
		$('.w-cart-link').each(function() {
			var $link = $(this);
			var href = $link.attr('href');
			if (href && href.indexOf('/cart') !== -1) {
				$link.attr('data-original-href', href);
				$link.attr('href', 'javascript:void(0);');
			}
		});
		
		// Method 2: Add click handler that prevents default and opens drawer
		// Using native JS with capture phase to run before any other handlers
		var cartLink = document.querySelector('.w-cart-link');
		if (cartLink) {
			cartLink.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				self.openDrawer();
				return false;
			}, true); // Capture phase - runs first
		}
		
		// Method 3: jQuery fallback for dynamically added elements
		$(document).on('click.drawer', '.w-cart-link', function(e) {
			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();
			self.openDrawer();
			return false;
		});
		
		// Also handle clicks on child elements (icon, quantity badge)
		$(document).on('click.drawer', '.w-cart-icon, .w-cart-quantity', function(e) {
			e.preventDefault();
			e.stopPropagation();
			self.openDrawer();
			return false;
		});
	},		// Open the drawer
		openDrawer: function() {
			// Sync content from Impreza's dropdown
			this.syncFromDropdown();
			
			// Show drawer
			this.$drawer.addClass('active');
			this.$overlay.addClass('active');
			$('body').addClass('cart-drawer-open');
			
			// Initialize collapse buttons when drawer opens
			this.initCollapsibleSections();
		},

		// Close the drawer
		closeDrawer: function() {
			this.$drawer.removeClass('active');
			this.$overlay.removeClass('active');
			$('body').removeClass('cart-drawer-open');
		},

		// Handle product added to cart
		handleAddToCart: function(fragments, $button) {
			var self = this;
			
			// Impreza's notification shows automatically
			// Just sync content and open drawer after brief delay
			setTimeout(function() {
				self.openDrawer();
			}, 500);
		},

		// Sync drawer content from Impreza's dropdown
		syncFromDropdown: function() {
			var $existingDropdown = $('.w-cart-dropdown').first();
			if ($existingDropdown.length) {
				// Remove old service recommendations - they will be re-fetched
				this.$content.find('.cart-service-recommendations').remove();
				
				// Update content from dropdown
				this.$content.html($existingDropdown.html());
				
				// Initialize collapse buttons after content is synced
				this.initCollapsibleSections();
			}
		},

		// Initialize collapsible sections (Service Details & Selected Add-ons)
		initCollapsibleSections: function() {
			// Remove any existing handlers to prevent duplicates
			$('.cart-side-drawer .gos-group-title').off('click.gosCollapse');
			
			// Add click handler for collapsible sections
			$('.cart-side-drawer .gos-group-title').on('click.gosCollapse', function() {
				var $title = $(this);
				var $list = $title.parent().find('ul.gos-field-list, ul.gos-addon-list');
				
				// Toggle collapsed class
				$title.toggleClass('collapsed');
				$list.toggleClass('collapsed');
			});
		}
	};

	// Initialize when DOM is ready
	$(document).ready(function() {
		// Wait a bit for parent theme scripts to load
		setTimeout(function() {
			CartSideDrawer.init();
		}, 500);
	});

	// Make it accessible globally if needed
	window.CartSideDrawer = CartSideDrawer;

})(jQuery);
