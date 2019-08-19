<?php
/**
 * WooCommerce Integrations
 *
 * @since 1.6
 */

namespace MyListing\Ext\Paid_Listings;

if ( ! defined('ABSPATH') ) {
	exit;
}

class WooCommerce {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {

		/* = PRODUCT = */

		// Add custom product type.
		add_filter( 'product_type_selector', [ $this, 'add_product_type' ] );

		// Product Class.
		add_filter( 'woocommerce_product_class' , [ $this, 'set_product_class' ], 10, 3 );

		// HTML.
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'product_data_html' ] );

		// Save Product Data.
		add_filter( 'woocommerce_process_product_meta_job_package', [ $this, 'save_product_data' ] );
		add_filter( 'woocommerce_process_product_meta_job_package_subscription', [ $this, 'save_product_data' ] );

		/* = CART = */

		// Use simple add to cart.
		add_action( 'woocommerce_job_package_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );

		// Get cart item from session.
		add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'get_cart_item_from_session' ], 10, 2 );

		// Save listing on checkout.
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'checkout_create_order_line_item' ], 10, 4 );

		// Display listing in cart.
		add_filter( 'woocommerce_get_item_data', [ $this, 'get_listing_in_cart' ], 10, 2 );

		// Disable guest checkout when purchasing listing and enable checkout signup.
		add_filter( 'option_woocommerce_enable_signup_and_login_from_checkout', [ $this, 'enable_signup_and_login_from_checkout' ] );
		add_filter( 'option_woocommerce_enable_guest_checkout', [ $this, 'enable_guest_checkout' ] );

		/* = ORDER = */

		// Thank you page.
		add_action( 'woocommerce_thankyou', [ $this, 'woocommerce_thankyou' ], 5 );

		// Process order.
		add_action( 'woocommerce_order_status_processing', [ $this, 'order_paid' ] );
		add_action( 'woocommerce_order_status_completed', [ $this, 'order_paid' ] );
		add_action( 'woocommerce_order_status_cancelled', [ $this, 'order_cancelled' ] );

		// Disable repeat product purchase, if enabled for the product.
		add_filter( 'mylisting/paid-listings/product/is-purchasable', [ $this, 'disable_repeat_purchase' ], 10, 2 );
		add_filter( 'woocommerce_is_purchasable', [ $this, 'disable_repeat_purchase' ], 10, 2 );
		add_action( 'woocommerce_single_product_summary', [ $this, 'purchase_disabled_message' ], 31 );

		// hide product meta fields
		add_filter( 'woocommerce_order_item_display_meta_key', [ $this, 'display_meta_key' ] );
		add_filter( 'woocommerce_order_item_display_meta_value', [ $this, 'display_meta_value' ], 10, 2 );
	}

	/**
	 * Add "Listing Package" Product Type
	 *
	 * @since 1.6
	 */
	public function add_product_type( $types ) {
		$types['job_package'] = esc_html__( 'Listing Package', 'my-listing' );
		return $types;
	}

	/**
	 * Set Product Class to Load.
	 *
	 * @since 1.6
	 */
	public function set_product_class( $classname, $product_type ) {
		if ( $product_type === 'job_package' ) {
			return '\MyListing\Ext\Paid_Listings\Product';
		}

		return $classname;
	}

	/**
	 * Product Data
	 *
	 * @since 1.6
	 */
	public function product_data_html() {
		global $post;
		$post_id = $post->ID;
		?>
		<div class="options_group listing-package-options show_if_job_package <?php echo esc_attr( class_exists( '\WC_Subscriptions' ) ? 'show_if_job_package_subscription' : '' );?>">

			<?php if ( class_exists( '\WC_Subscriptions' ) ) : ?>
				<?php woocommerce_wp_select( [
					'id' => '_job_listing_package_subscription_type',
					'wrapper_class' => 'show_if_job_package_subscription',
					'label' => __( 'Subscription Type', 'my-listing' ),
					'description' => __( 'Choose how subscriptions affect this package', 'my-listing' ),
					'value' => get_post_meta( $post_id, '_package_subscription_type', true ),
					'desc_tip' => true,
					'options' => [
						'listing' => __( 'Link the subscription to posted listings (renew posted listings every subscription term)', 'my-listing' ),
						'package' => __( 'Link the subscription to the package (renew listing limit every subscription term)', 'my-listing' ),
					],
				] ) ?>
			<?php endif ?>

			<?php woocommerce_wp_text_input( [
				'id'                => '_job_listing_limit',
				'label'             => __( 'Listing limit', 'my-listing' ),
				'description'       => __( 'The number of listings a user can post with this package.', 'my-listing' ),
				'value'             => ( $limit = get_post_meta( $post_id, '_job_listing_limit', true ) ) ? $limit : '',
				'placeholder'       => __( 'Unlimited', 'my-listing' ),
				'type'              => 'number',
				'desc_tip'          => true,
				'custom_attributes' => [
					'min'   => '',
					'step' 	=> '1',
				],
			] ) ?>

			<?php woocommerce_wp_text_input( [
				'id'                => '_job_listing_duration',
				'label'             => __( 'Listing duration', 'my-listing' ),
				'description'       => __( 'The number of days that the listing will be active.', 'my-listing' ),
				'value'             => get_post_meta( $post_id, '_job_listing_duration', true ),
				'placeholder'       => mylisting_get_setting( 'submission_default_duration' ),
				'desc_tip'          => true,
				'type'              => 'number',
				'custom_attributes' => [
					'min'   => '',
					'step' 	=> '1',
				],
			] ) ?>

			<?php woocommerce_wp_checkbox( [
				'id'                => '_job_listing_featured',
				'label'             => __( 'Feature Listings?', 'my-listing' ),
				'description'       => __( 'Feature this listing - it will be styled differently and sticky.', 'my-listing' ),
				'value'             => get_post_meta( $post_id, '_job_listing_featured', true ),
			] ) ?>

			<?php woocommerce_wp_checkbox( [
				'id'                => '_use_for_claims',
				'label'             => __( 'Use for Claim?', 'my-listing' ),
				'description'       => __( 'Allow this package to be an option for claiming a listing.', 'my-listing' ),
				'value'             => get_post_meta( $post_id, '_use_for_claims', true ),
			] ) ?>

			<?php woocommerce_wp_checkbox( [
				'id'                => '_disable_repeat_purchase',
				'label'             => __( 'Disable repeat purchase?', 'my-listing' ),
				'description'       => __( 'If checked, this package can only be bought once per user. This can be useful for free listing packages, where you only want to allow the free package to be used once.', 'my-listing' ),
				'value'             => get_post_meta( $post_id, '_disable_repeat_purchase', true ),
			] ) ?>

			<script type="text/javascript">
				jQuery( function( $ ) {
					$( '.pricing' ).addClass( 'show_if_job_package' );
					$( '._tax_status_field' ).closest( 'div' ).addClass( 'show_if_job_package' );
					$( '#product-type' ).change( function(e) {
						$('#_job_listing_package_subscription_type').change();
					} ).change();
					<?php if ( class_exists( '\WC_Subscriptions' ) ) : ?>
						$('._tax_status_field').closest('div').addClass( 'show_if_job_package_subscription' );
						$('.show_if_subscription, .options_group.pricing').addClass( 'show_if_job_package_subscription' );
						$('#_job_listing_package_subscription_type').change(function() {
							if ( $( '#product-type' ).val() === 'job_package' ) {
								$('#_job_listing_duration').closest('.form-field').show();
								return;
							}

							if ( $(this).val() === 'listing' ) {
								$('#_job_listing_duration').closest('.form-field').hide().val('');
							} else {
								$('#_job_listing_duration').closest('.form-field').show();
							}
						}).change();
					<?php endif; ?>
				} );
			</script>
		</div>
		<?php
	}

	/**
	 * Save Product Data
	 *
	 * @since 1.6
	 * @param int $post_id Product ID.
	 */
	public function save_product_data( $post_id ) {
		// Limit.
		if ( ! empty( $_POST['_job_listing_limit'] ) ) {
			update_post_meta( $post_id, '_job_listing_limit', absint( $_POST['_job_listing_limit'] ) );
		} else {
			delete_post_meta( $post_id, '_job_listing_limit' );
		}

		// Duration.
		if ( ! empty( $_POST['_job_listing_duration'] ) ) {
			update_post_meta( $post_id, '_job_listing_duration', absint( $_POST['_job_listing_duration'] ) );
		} else {
			delete_post_meta( $post_id, '_job_listing_duration' );
		}

		// Featured.
		if ( ! empty( $_POST['_job_listing_featured'] ) ) {
			update_post_meta( $post_id, '_job_listing_featured', 'yes' );
		} else {
			update_post_meta( $post_id, '_job_listing_featured', 'no' );
		}

		// Use for Claims.
		if ( ! empty( $_POST['_use_for_claims'] ) ) {
			update_post_meta( $post_id, '_use_for_claims', 'yes' );
		} else {
			update_post_meta( $post_id, '_use_for_claims', 'no' );
		}

		// Disable repeat purchase.
		if ( ! empty( $_POST['_disable_repeat_purchase'] ) ) {
			update_post_meta( $post_id, '_disable_repeat_purchase', 'yes' );
		} else {
			update_post_meta( $post_id, '_disable_repeat_purchase', 'no' );
		}

		// Subscription type.
		if ( isset( $_POST['_job_listing_package_subscription_type'] ) ) {
			$type = 'package' === $_POST['_job_listing_package_subscription_type'] ? 'package' : 'listing';
			update_post_meta( $post_id, '_package_subscription_type', $type );
		}
	}

	/**
	 * Get the data from the session on page load
	 *
	 * @since 1.6
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {
		if ( ! empty( $values['job_id'] ) ) {
			$cart_item['job_id'] = $values['job_id'];
			$cart_item['is_claim'] = ! empty( $values['is_claim'] ) ? true : false;
		}

		return $cart_item;
	}

	/**
	 * Set the order line item's meta data prior to being saved.
	 *
	 * @since 1.6
	 *
	 * @param WC_Order_Item_Product $order_item
	 * @param string                $cart_item_key  The hash used to identify the item in the cart
	 * @param array                 $cart_item_data The cart item's data.
	 * @param WC_Order              $order          The order or subscription object to which the line item relates
	 */
	public function checkout_create_order_line_item( $order_item, $cart_item_key, $cart_item_data, $order ) {
		if ( isset( $cart_item_data['job_id'] ) ) {
			$order_item->update_meta_data( '_job_id', $cart_item_data['job_id'] );
			if ( isset( $cart_item_data['is_claim'] ) ) {
				$order_item->update_meta_data( '_is_claim', $cart_item_data['is_claim'] ? 1 : 0 );
			}
		}
	}

	/**
	 * Output listing name in cart
	 *
	 * @since 1.6
	 */
	public function get_listing_in_cart( $data, $cart_item ) {
		if ( isset( $cart_item['job_id'] ) ) {
			$data[] = [
				'name'  => isset( $cart_item['is_claim'] ) && $cart_item['is_claim'] ? esc_html__( 'Claim for', 'my-listing' ) : esc_html__( 'Listing', 'my-listing' ),
				'value' => get_the_title( absint( $cart_item['job_id'] ) ),
			];
		}

		return $data;
	}

	/**
	 * When cart contains a listing package, always set to "yes".
	 *
	 * @since 1.6
	 */
	public function enable_signup_and_login_from_checkout( $value ) {
		global $woocommerce;
		$contain_listing = false;
		if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				$product = $cart_item['data'];
				if ( $product instanceof WC_Product && $product->is_type( [ 'job_package', 'job_package_subscription' ] ) ) {
					$contain_listing = true;
				}
			}
		}

		return $contain_listing ? 'yes' : $value;
	}

	/**
	 * When cart contains a listing package, always set to "no".
	 *
	 * @since 1.6
	 */
	public function enable_guest_checkout( $value ) {
		global $woocommerce;
		$contain_listing = false;
		if ( ! empty( $woocommerce->cart->cart_contents ) ) {
			foreach ( $woocommerce->cart->cart_contents as $cart_item ) {
				$product = $cart_item['data'];
				if ( $product instanceof WC_Product && $product->is_type( [ 'job_package', 'job_package_subscription' ] ) ) {
					$contain_listing = true;
				}
			}
		}

		return $contain_listing ? 'no' : $value;
	}


	/**
	 * Thank you page after checkout completed.
	 *
	 * @since 1.6
	 */
	public function woocommerce_thankyou( $order_id ) {
		global $wp_post_types;
		$order = wc_get_order( $order_id );
		$is_paid = in_array( $order->get_status(), [ 'completed', 'processing' ] );

		foreach ( $order->get_items() as $item ) {
			if ( ! isset( $item['job_id'] ) ) {
				continue;
			}

			$listing_status = get_post_status( $item['job_id'] );
			$is_claim = ! empty( $item['is_claim'] ) ? true : false;

			if ( $is_claim ) {
				if ( $is_paid ) {
					echo wpautop( sprintf( __( 'Your claim to %s has been submitted successfully.', 'my-listing' ), get_the_title( $item['job_id'] ) ) );
				} else {
					echo wpautop( sprintf( __( 'Your claim to %s will be processed after the order is completed.', 'my-listing' ), get_the_title( $item['job_id'] ) ) );
				}
			} else {
				switch ( get_post_status( $item['job_id'] ) ) {
					case 'pending' :
						echo wpautop( sprintf( __( '%s has been submitted successfully and will be visible once approved.', 'my-listing' ), get_the_title( $item['job_id'] ) ) );
					break;
					case 'pending_payment' :
					case 'expired' :
						echo wpautop( sprintf( __( '%s has been submitted successfully and will be visible once payment has been confirmed.', 'my-listing' ), get_the_title( $item['job_id'] ) ) );
					break;
					default :
						echo wpautop( sprintf( __( '%s has been submitted successfully.', 'my-listing' ), get_the_title( $item['job_id'] ) ) );
					break;
				}

				do_action( 'mylisting/submission/order-placed', $item['job_id'] );
			}
			?>

			<p class="job-manager-submitted-paid-listing-actions">
				<?php
				if ( get_post_status( $item['job_id'] ) === 'publish' ) {
					echo '<a class="button" href="' . get_permalink( $item['job_id'] ) . '">' . __( 'View Listing', 'my-listing' ) . '</a> ';
				} elseif ( get_option( 'job_manager_job_dashboard_page_id' ) ) {
					echo '<a class="button" href="' . esc_url( wc_get_account_endpoint_url( 'my-listings' ) ) . '">' . __( 'Go to Dashboard', 'my-listing' ) . '</a> ';
				}
				?>
			</p>

			<?php
		}
	}

	/**
	 * Triggered when an order is paid
	 *
	 * @since 1.6
	 */
	public function order_paid( $order_id ) {
		$order = wc_get_order( $order_id );

		// Bail if already processed. Using WCPL prefix for back-compat.
		if ( get_post_meta( $order_id, 'wc_paid_listings_packages_processed', true ) ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$product = wc_get_product( $item['product_id'] );
			if ( ! ( $product->is_type( [ 'job_package' ] ) && $order->get_customer_id() ) ) {
				continue;
			}

			// Give packages to user
			$user_package_id = false;
			for ( $i = 0; $i < $item['qty']; $i++ ) {
				$user_package_id = case27_paid_listing_add_package( [
					'user_id'        => $order->get_customer_id(),
					'order_id'       => $order_id,
					'product_id'     => $product->get_id(),
					'duration'       => $product->get_duration(),
					'limit'          => $product->get_limit(),
					'featured'       => $product->is_listing_featured(),
					'use_for_claims' => $product->is_use_for_claims(),
				] );

				// user package created & make sure listing id is set
				if ( ! ( $user_package_id && isset( $item['job_id'] ) ) ) {
					continue;
				}

				// validate listing
				$listing = get_post( $item['job_id'] );
				if ( ! ( $listing && $listing->post_type === 'job_listing' ) ) {
					continue;
				}

				// Add user package info to listing.
				update_post_meta( $item['job_id'], '_user_package_id', $user_package_id );

				// Create claim.
				if ( ! empty( $item['is_claim'] ) ) {
					$claim_id = \MyListing\Src\Claims\Claims::create( [
						'listing_id'      => absint( $listing->ID ),
						'user_package_id' => absint( $user_package_id ),
						'user_id'         => absint( $order->get_customer_id() ),
					] );
				} else {
					// Update listing status.
					wp_update_post( [
						'ID' => $listing->ID,
						'post_status' => mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish',
					] );

					// Listing has already been published, trigger the set expiry function.
					if ( ! mylisting_get_setting( 'submission_requires_approval' ) ) {
						$expires = \MyListing\Src\Listing::calculate_expiry( $listing->ID );
						update_post_meta( $listing->ID, '_job_expires', $expires );
					}

					// Increase package count.
					case27_paid_listing_user_package_increase_count( $user_package_id );

					// Update package status.
					$status = case27_paid_listing_get_proper_status( $user_package_id );
					if ( $status && get_post_status( $user_package_id ) !== $status ) {
						wp_update_post( [
							'ID'          => $user_package_id,
							'post_status' => $status,
						] );
					}
				}
			}
		}

		// mark order as processed
		update_post_meta( $order_id, 'wc_paid_listings_packages_processed', true );
	}

	/**
	 * Fires when a order was canceled. Looks for listing
	 * packages in order and deletes the package if found.
	 *
	 * @since 1.6
	 */
	public function order_cancelled( $order_id ) {
		$packages = case27_paid_listing_get_user_packages( [
			'post_status' => 'any',
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => '_order_id',
					'value'   => $order_id,
					'compare' => 'IN',
				],
			],
		] );

		if ( $packages && is_array( $packages ) ) {
			foreach ( $packages as $package_id ) {
				wp_update_post( [
					'ID'          => $package_id,
					'post_status' => 'case27_cancelled',
				] );
			}
		}
	}

	/**
	 * Disables repeat purchase for packages.
	 * Useful for allowing users to only have one free package plan.
	 *
	 * @since  1.6.3
	 *
	 * @param  bool        $purchasable
	 * @param  \WC_Product $product
	 * @return bool        $purchasable
	 */
	public function disable_repeat_purchase( $purchasable, $product ) {
	    if ( ! $product->is_type( [ 'job_package', 'job_package_subscription' ] ) ) {
	        return $purchasable;
	    }

	    if ( $product->get_meta( '_disable_repeat_purchase' ) !== 'yes' ) {
	        return $purchasable;
	    }

	    if ( wc_customer_bought_product( wp_get_current_user()->user_email, get_current_user_id(), $product->get_id() ) ) {
	        $purchasable = false;
	    }

	    return $purchasable;
	}

	/**
	 * Shows a "purchase disabled" message to the customer.
	 *
	 * @since 1.6.3
	 */
	public function purchase_disabled_message() {
	    global $product;

	    if ( ! $product->is_type( [ 'job_package', 'job_package_subscription' ] ) ) {
	        return false;
	    }

	    if ( $product->get_meta( '_disable_repeat_purchase' ) !== 'yes' ) {
	        return false;
	    }

	    if ( wc_customer_bought_product( wp_get_current_user()->user_email, get_current_user_id(), $product->get_id() ) ) {
	    	printf(
	    		'<div class="woocommerce"><div class="woocommerce-info wc-nonpurchasable-message">%s</div></div>',
	    		__( 'You\'ve already purchased this product! It can only be purchased once.', 'my-listing' )
	    	);
	    }
	}

	/**
	 * Display a nicename instead of the meta key in advanced order settings.
	 *
	 * @since 2.1
	 */
	public function display_meta_key( $display_key ) {
		if ( $display_key === '_job_id' ) {
			return _x( 'Listing ID', 'Product meta', 'my-listing' );
		}

		if ( $display_key === '_is_claim' ) {
			return _x( 'Type', 'Product meta', 'my-listing' );
		}

		return $display_key;
	}

	/**
	 * Display a formatted value instead of the default meta value in advanced order settings.
	 *
	 * @since 2.1
	 */
	public function display_meta_value( $value, $meta ) {
		if ( $meta->key === '_is_claim' ) {
			return (bool) $value ? _x( 'Claim', 'Product meta', 'my-listing' ) : _x( 'Submission', 'Product meta', 'my-listing' );
		}

		return $value;
	}
}

WooCommerce::instance();
