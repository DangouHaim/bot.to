<?php
/**
 * WC Subscriptions Payments Setup.
 *
 * @since 1.6
 */

namespace MyListing\Ext\Paid_Listings\Subscriptions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Payments {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		// Subscription Synchronisation.
		// activate sync (process meta) for listing package.
		if ( class_exists( 'WC_Subscriptions_Synchroniser' ) && method_exists( '\WC_Subscriptions_Synchroniser', 'save_subscription_meta' ) ) {
			add_action( 'woocommerce_process_product_meta_job_package_subscription', '\WC_Subscriptions_Synchroniser::save_subscription_meta', 10 );
		}

		// Prevent listing linked to product(subs) never expire automatically.
		add_action( 'added_post_meta', array( $this, 'updated_post_meta' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'updated_post_meta' ), 10, 4 );

		// When listing expires, adjust user package usage and delete package & user package meta in listing.
		add_action( 'publish_to_expired', array( $this, 'check_expired_listing' ) );

		// Change user package usage when trash/untrash listing.
		add_action( 'wp_trash_post', array( $this, 'wp_trash_post' ) );
		add_action( 'untrash_post', array( $this, 'untrash_post' ) );

		/* === SUBS ENDED. === */

		// Subscription Paused (on Hold).
		add_action( 'woocommerce_subscription_status_on-hold', array( $this, 'subscription_ended' ) );

		// Subscription Ended.
		add_action( 'woocommerce_scheduled_subscription_expiration', array( $this, 'subscription_ended' ) );

		// When a subscription ends after remaining unpaid.
		add_action( 'woocommerce_scheduled_subscription_end_of_prepaid_term', array( $this, 'subscription_ended' ) );

		// When the subscription status changes to cancelled.
		add_action( 'woocommerce_subscription_status_cancelled', array( $this, 'subscription_ended' ) );

		// Subscription is expired.
		add_action( 'woocommerce_subscription_status_expired', array( $this, 'subscription_ended' ) );

		/* === SUBS STARTS. === */

		// Subscription starts ( status changes to active ).
		add_action( 'woocommerce_subscription_status_active', array( $this, 'subscription_activated' ) );

		/* === SUBS RENEWED. === */

		// When the subscription is renewed.
		add_action( 'woocommerce_subscription_renewal_payment_complete', array( $this, 'subscription_renewed' ) );

		/* === SUBS SWITCHED (UPGRADE/DOWNGRADE). === */

		// When the subscription is switched and a new subscription is created.
		add_action( 'woocommerce_subscriptions_switched_item', array( $this, 'subscription_switched' ), 10, 3 );

		// When the subscription is switched and only the item is changed.
		add_action( 'woocommerce_subscription_item_switched', array( $this, 'subscription_item_switched' ), 10, 4 );
	}

	/**
	 * Get subscription type for pacakge by ID.
	 *
	 * @since 1.6
	 */
	public function get_package_subscription_type( $product_id ) {
		$subscription_type = get_post_meta( $product_id, '_package_subscription_type', true );
		return empty( $subscription_type ) ? 'package' : $subscription_type;
	}

	/**
	 * Prevent listings linked to subscriptions from expiring.
	 *
	 * @since 1.6
	 */
	public function updated_post_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'job_listing' === get_post_type( $object_id ) && '' !== $meta_value && '_job_expires' === $meta_key ) {
			$_package_id = get_post_meta( $object_id, '_package_id', true );
			$package     = wc_get_product( $_package_id );

			if ( $package && 'job_package_subscription' === $package->get_type() && 'listing' === $package->get_package_subscription_type() ) {
				update_post_meta( $object_id, '_job_expires', '' ); // Never expire automatically.
			}
		}
	}

	/**
	 * If a listing is expired, the pack may need it's listing count changing.
	 *
	 * @since 1.6
	 */
	public function check_expired_listing( $post ) {
		if ( 'job_listing' === $post->post_type ) {
			$package_product_id = get_post_meta( $post->ID, '_package_id', true );
			$package_id         = get_post_meta( $post->ID, '_user_package_id', true );
			$package_product    = get_post( $package_product_id );

			if ( $package_product_id ) {
				$subscription_type = $this->get_package_subscription_type( $package_product_id );

				if ( 'listing' === $subscription_type ) {
					$user_package = get_post( $package_id );
					if ( $user_package ) {
						$new_count = absint( $user_package->_count );
						$new_count --;

						update_post_meta( $user_package->ID, '_count', max( 0, $new_count ) );

					}
					// Remove package meta after adjustment.
					delete_post_meta( $post->ID, '_package_id' );
					delete_post_meta( $post->ID, '_user_package_id' );
				}
			}
		}
	}

	/**
	 * If a listing gets trashed/deleted, the pack may need it's listing count changing.
	 *
	 * @since 1.6
	 */
	public function wp_trash_post( $post_id ) {
		if ( $post_id > 0 ) {
			$post_type = get_post_type( $post_id );

			if ( 'job_listing' === $post_type ) {
				$package_product_id = get_post_meta( $post_id, '_package_id', true );
				$package_id         = get_post_meta( $post_id, '_user_package_id', true );
				$package_product    = get_post( $package_product_id );

				if ( $package_product_id ) {
					$subscription_type = $this->get_package_subscription_type( $package_product_id );

					if ( 'listing' === $subscription_type ) {
						$user_package = get_post( $package_id );
						if ( $user_package ) {
							$new_count = absint( $user_package->_count );
							$new_count --;

							update_post_meta( $user_package->ID, '_count', max( 0, $new_count ) );
						}
					}
				}
			}
		}
	}

	/**
	 * If a listing gets restored, the pack may need it's listing count changing.
	 *
	 * @since 1.6
	 */
	public function untrash_post( $post_id ) {
		if ( $post_id > 0 ) {
			$post_type = get_post_type( $post_id );

			if ( 'job_listing' === $post_type ) {
				$package_product_id = get_post_meta( $post_id, '_package_id', true );
				$package_id         = get_post_meta( $post_id, '_user_package_id', true );
				$package_product    = get_post( $package_product_id );

				if ( $package_product_id ) {
					$subscription_type = $this->get_package_subscription_type( $package_product_id );

					if ( 'listing' === $subscription_type ) {
						$user_package = get_post( $package_id );
						if ( $user_package ) {
							$new_count = absint( $user_package->_count );
							$new_count = $new_count + 1;

							update_post_meta( $user_package->ID, '_count', max( 0, $new_count ) );
						}
					}
				}
			}
		}
	}

	/**
	 * Subscription has expired - cancel listing packs.
	 *
	 * @since 1.6
	 */
	public function subscription_ended( $subscription ) {
		foreach ( $subscription->get_items() as $item ) {
			$subscription_type = $this->get_package_subscription_type( $item['product_id'] );
			$user_packages = case27_paid_listing_get_user_packages( [
				'posts_per_page' => 1,
				'meta_query' => [
					'relation' => 'AND',
					[ 'key' => '_order_id', 'value' => $subscription->get_id() ],
					[ 'key' => '_product_id', 'value' => [ $item['product_id'] ], 'compare' => 'IN' ],
				],
			] );
			$user_package = $user_packages && is_array( $user_packages ) && isset( $user_packages[0] ) ? $user_packages[0] : false;

			if ( $user_package ) {
				// Delete the package.
				wp_delete_post( $user_package, true ); // @todo:maybe force delete.

				// Expire listings posted with package.
				if ( 'listing' === $subscription_type ) {
					$listing_ids = case27_paid_listing_get_listings_in_package( $user_package );

					foreach ( $listing_ids as $listing_id ) {
						$old_status = get_post_status( $listing_id );
						wp_update_post( [
							'ID' => $listing_id,
							'post_status' => 'expired',
						] );

						// Make a record of the subscription ID in case of re-activation.
						update_post_meta( $listing_id, '_expired_subscription_id', $subscription->get_id() );

						// Also save the old listing status.
						update_post_meta( $listing_id, '_expired_subscription_status', $old_status );
					}
				}
			}
		}

		delete_post_meta( $subscription->get_id(), 'wc_paid_listings_subscription_packages_processed' );
	}

	/**
	 * Subscription activated.
	 *
	 * @since 1.6
	 */
	public function subscription_activated( $subscription ) {
		global $wpdb;

		if ( get_post_meta( $subscription->get_id(), 'wc_paid_listings_subscription_packages_processed', true ) ) {
			return;
		}

		// Remove any old packages for this subscription.
		$user_packages = case27_paid_listing_get_user_packages( [
			'posts_per_page' => 1,
			'meta_query' => [ [ 'key' => '_order_id', 'value' => $subscription->get_id() ] ],
		] );

		foreach ( $user_packages as $user_package ) {
			wp_delete_post( $user_package, true ); // @todo: maybe force delete.
		}

		foreach ( $subscription->get_items() as $item ) {
			$product           = wc_get_product( $item['product_id'] );
			$subscription_type = $this->get_package_subscription_type( $item['product_id'] );

			// Give user packages for this subscription.
			if ( $product->is_type( array( 'job_package_subscription' ) ) && $subscription->get_user_id() && ! isset( $item['switched_subscription_item_id'] ) ) {

				// Give package to user.
				$user_package_id = case27_paid_listing_add_package( array(
					'user_id'        => $subscription->get_user_id(),
					'order_id'       => $subscription->get_id(),
					'product_id'     => $product->get_id(),
					'duration'       => $product->get_duration(),
					'limit'          => $product->get_limit(),
					'featured'       => $product->is_listing_featured(),
					'use_for_claims' => $product->is_use_for_claims(),
				) );

				/**
				 * If the subscription is associated with listings, see if any,
				 * already match this ID and approve them (useful on re-activation of a sub).
				 */
				$listing_ids = array();
				if ( 'listing' === $subscription_type ) {
					$listing_ids = (array) $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s", '_expired_subscription_id', $subscription->get_id() ) );
				}

				$listing_ids[] = isset( $item['job_id'] ) ? $item['job_id'] : '';
				$listing_ids   = array_unique( array_filter( array_map( 'absint', $listing_ids ) ) );

				foreach ( $listing_ids as $listing_id ) {
					if ( in_array( get_post_status( $listing_id ), array( 'pending_payment', 'expired' ), true ) ) {
						$old_status = get_post_meta( $listing_id, '_expired_subscription_status', true );
						delete_post_meta( $listing_id, '_expired_subscription_id' );
						delete_post_meta( $listing_id, '_expired_subscription_status' );

						// Add user package info to listing.
						update_post_meta( $listing_id, '_user_package_id', $user_package_id );

						// Update listing status.
						wp_update_post( [
							'ID' => $listing_id,
							'post_status' => ( in_array( $old_status, ['pending', 'publish'] ) ? $old_status : ( mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish' ) ),
						] );

						case27_paid_listing_user_package_increase_count( $user_package_id );

						// Update package status.
						$status = case27_paid_listing_get_proper_status( $user_package_id );
						if ( $status && get_post_status( $user_package_id ) !== $status ) {
							wp_update_post( [
								'ID' => $user_package_id,
								'post_status' => $status,
							] );
						}
					}
				}

				// Listing has already been published, trigger the set expiry function.
				if ( ! empty( $item['job_id'] ) && ! mylisting_get_setting( 'submission_requires_approval' ) && 'listing' === $subscription_type ) {
					if ( $listing = get_post( $item['job_id'] ) ) {
						update_post_meta( $listing->ID, '_job_expires', '' );
					}
				}

				// User package created & make sure listing ID is set.
				if ( ! empty( $item['is_claim'] ) && $user_package_id && isset( $item['job_id'] ) ) {
					// Check listing.
					$listing = get_post( $item['job_id'] );
					if ( $listing && 'job_listing' === $listing->post_type ) {
						$claim_id = \MyListing\Src\Claims\Claims::create( [
							'listing_id'      => absint( $listing->ID ),
							'user_package_id' => absint( $user_package_id ),
							'user_id'         => absint( $subscription->get_user_id() ),
						] );
					}
				}
			}
		}

		update_post_meta( $subscription->get_id(), 'wc_paid_listings_subscription_packages_processed', true );
	}

	/**
	 * Subscription renewed - renew the listing pack.
	 *
	 * @since 1.6
	 */
	public function subscription_renewed( $subscription ) {
		global $wpdb;

		foreach ( $subscription->get_items() as $item ) {
			$product           = wc_get_product( $item['product_id'] );
			$subscription_type = $this->get_package_subscription_type( $item['product_id'] );

			// Renew packages which refresh every term.
			if ( 'package' === $subscription_type ) {
				$user_packages = case27_paid_listing_get_user_packages( [
					'posts_per_page' => 1,
					'meta_query' => [
						'relation' => 'AND',
						[ 'key' => '_order_id', 'value' => $subscription->get_id() ],
						[ 'key' => '_product_id', 'value' => [ $item['product_id'] ], 'compare' => 'IN' ],
					],
				] );

				$user_package = $user_packages && is_array( $user_packages ) && isset( $user_packages[0] ) ? $user_packages[0] : false;
				if ( ! $user_package ) {
					$user_package_id = case27_paid_listing_add_package( array(
						'user_id'        => $subscription->get_user_id(),
						'order_id'       => $subscription->get_id(),
						'product_id'     => $product->get_id(),
						'duration'       => $product->get_duration(),
						'limit'          => $product->get_limit(),
						'featured'       => $product->is_listing_featured(),
						'use_for_claims' => $product->is_use_for_claims(),
					) );
				}
			} else { // Otherwise the listings stay active, but we can ensure they are synced in terms of featured status etc.
				// @todo: Update featured status.
				// Currently not supported.
			}
		}
	}

	/**
	 * When switching a subscription we need to update old listings.
	 * No need to give the user a new package; that is still handled by the orders class.
	 *
	 * @since 1.6
	 *
	 * @param object $order             WC Order.
	 * @param object $subscription      WC Subscription.
	 * @param int    $new_order_item_id New order Item ID.
	 * @param int    $old_order_item_id Old order Item ID.
	 */
	public function subscription_item_switched( $order, $subscription, $new_order_item_id, $old_order_item_id ) {
		global $wpdb;

		$new_order_item = \WC_Subscriptions_Order::get_item_by_id( $new_order_item_id );
		$old_order_item = \WC_Subscriptions_Order::get_item_by_id( $old_order_item_id );

		$new_subscription = (object) [
			'id'           => $subscription->id,
			'subscription' => $subscription,
			'product_id'   => $new_order_item['product_id'],
			'product'      => wc_get_product( $new_order_item['product_id'] ),
			'type'         => $this->get_package_subscription_type( $new_order_item['product_id'] ),
		];

		$old_subscription = (object) [
			'id'           => $subscription->id,
			'subscription' => $subscription,
			'product_id'   => $old_order_item['product_id'],
			'product'      => wc_get_product( $old_order_item['product_id'] ),
			'type'         => $this->get_package_subscription_type( $old_order_item['product_id'] ),
		];

		$this->switch_package( $subscription->get_user_id(), $new_subscription, $old_subscription );
	}

	/**
	 * When switching a subscription we need to update old listings.
	 * No need to give the user a new package; that is still handled by the orders class.
	 *
	 * @since 1.6
	 *
	 * @param object $subscription    WC Subscription.
	 * @param array  $new_order_item  New order Item ID.
	 * @param array  $old_order_item  Old order Item ID.
	 */
	public function subscription_switched( $subscription, $new_order_item, $old_order_item ) {
		global $wpdb;

		$new_subscription = (object) [
			'id'         => $subscription->id,
			'product_id' => $new_order_item['product_id'],
			'product'    => wc_get_product( $new_order_item['product_id'] ),
			'type'       => $this->get_package_subscription_type( $new_order_item['product_id'] ),
		];

		$old_subscription = (object) [
			'id'         => $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d ", $new_order_item['switched_subscription_item_id'] ) ),
			'product_id' => $old_order_item['product_id'],
			'product'    => wc_get_product( $old_order_item['product_id'] ),
			'type'       => $this->get_package_subscription_type( $old_order_item['product_id'] ),
		];

		$this->switch_package( $subscription->get_user_id(), $new_subscription, $old_subscription );
	}

	/**
	 * Handle Switch Event.
	 *
	 * @since 1.6
	 *
	 * @param int    $user_id          User ID.
	 * @param object $new_subscription New Subscription.
	 * @param object $old_subscription Old Subscription.
	 */
	public function switch_package( $user_id, $new_subscription, $old_subscription ) {
		global $wpdb;

		// Get the user package.
		$legacy_id = isset( $old_subscription->subscription->order->id ) ? $old_subscription->subscription->order->id : $old_subscription->id;
		$user_packages = case27_paid_listing_get_user_packages( [
			'posts_per_page' => 1,
			'meta_query' => [
				'relation' => 'AND',
				[ 'key' => '_order_id', 'value' => [ $old_subscription->id, $legacy_id ], 'compare' => 'IN' ],
				[ 'key' => '_product_id', 'value' => [ $old_subscription->product_id ], 'compare' => 'IN' ],
			],
		] );

		$user_package = $user_packages && is_array( $user_packages ) && isset( $user_packages[0] ) ? $user_packages[0] : false;
		$user_package = case27_paid_listing_get_package( $user_package->ID );

		if ( $user_package ) {
			// If invalid, abort.
			if ( ! $new_subscription->product->is_type( array( 'job_package_subscription' ) ) ) {
				return false;
			}

			// Give new package to user.
			$new_product = wc_get_product( $new_subscription->product_id );
			$switching_to_package_id = case27_paid_listing_add_package( array(
				'user_id'        => $user_id,
				'order_id'       => $new_subscription->id,
				'product_id'     => $new_subscription->product_id,
				'duration'       => $new_product->get_duration(),
				'limit'          => $new_product->get_limit(),
				'featured'       => $new_product->is_listing_featured(),
				'use_for_claims' => $new_product->is_use_for_claims(),
			) );

			// Upgrade?
			$is_upgrade = ( 0 === $new_subscription->product->get_limit() || $new_subscription->product->get_limit() >= $user_package->get_count() );

			// Delete the old package.
			wp_delete_post( $user_package->get_id(), false );

			// Update old listings.
			if ( 'listing' === $new_subscription->type && $switching_to_package_id ) {
				$listing_ids = case27_paid_listing_get_listings_in_package( $user_package->get_id() );

				foreach ( $listing_ids as $listing_id ) {
					// If we are not upgrading, expire the old listing.
					if ( ! $is_upgrade ) {
						wp_update_post( [
							'ID' => $listing_id,
							'post_status' => 'expired',
						] );
					} else {
						case27_paid_listing_user_package_increase_count( $switching_to_package_id );
						// Change the user package ID and package ID.
						update_post_meta( $listing_id, '_user_package_id', $switching_to_package_id );
						update_post_meta( $listing_id, '_package_id', $new_subscription->product_id );
					} // End if().

					// Featured or not.
					update_post_meta( $listing_id, '_featured', $new_subscription->product->is_listing_featured() ? 1 : 0 );

				}
			}
		}
	}
}
