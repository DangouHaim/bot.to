<?php
/**
 * WC Subscriptions Integrations Setup.
 *
 * @since 1.6
 */

namespace MyListing\Ext\Paid_Listings\Subscriptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Subscriptions {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		// WC Subscriptions must be enabled to use this feature.
		if ( ! class_exists( '\WC_Subscriptions' ) ) {
			return;
		}

		// Add listing as valid subscription.
		add_filter( 'woocommerce_is_subscription', [ $this, 'woocommerce_is_subscription' ], 10, 2 );

		// Add product type.
		add_filter( 'woocommerce_subscription_product_types', [ $this, 'add_subscription_product_types' ] );
		add_filter( 'product_type_selector', [ $this, 'add_product_type_selector' ] );

		// Product Class.
		add_filter( 'woocommerce_product_class' , [ $this, 'set_product_class' ], 10, 3 );

		// Add to cart.
		add_action( 'woocommerce_job_package_subscription_add_to_cart', '\WC_Subscriptions::subscription_add_to_cart', 30 );

		Payments::instance();
	}

	/**
	 * Is this a subscription product?
	 *
	 * @since 1.6
	 */
	public function woocommerce_is_subscription( $is_subscription, $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product && $product->is_type( [ 'job_package_subscription' ] ) ) {
			$is_subscription = true;
		}
		return $is_subscription;
	}

	/**
	 * Types for subscriptions.
	 *
	 * @since 1.6
	 */
	public function add_subscription_product_types( $types ) {
		$types[] = 'job_package_subscription';
		return $types;
	}

	/**
	 * Add the product type selector.
	 *
	 * @since 1.6
	 */
	public function add_product_type_selector( $types ) {
		$types['job_package_subscription'] = __( 'Listing Subscription', 'my-listing' );
		return $types;
	}

	/**
	 * Set Product Class to Load.
	 *
	 * @since 1.6
	 * @param string $classname Current classname found.
	 * @param string $product_type Current product type.
	 */
	public function set_product_class( $classname, $product_type ) {
		if ( $product_type === 'job_package_subscription' ) {
			$classname = '\MyListing\Ext\Paid_Listings\Product_Subscription';
		}

		return $classname;
	}
}
