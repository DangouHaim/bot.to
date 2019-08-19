<?php

namespace MyListing\Ext\Paid_Listings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrapper class for `case27_user_package` post type.
 *
 * @since 1.6
 */
class Payment_Package {

	/**
	 * Contains the `case27_user_package` payment package \WP_Post object.
	 * @since 1.6
	 */
	private $package = '';

	/**
	 * Contains the WC Product object this payment package was created from.
	 * @since 1.6
	 */
	private $product = '';

	/**
	 * Contains the user object for the owner of this payment package.
	 * @since 1.6
	 */
	private $user = '';

	/**
	 * @param int $post_id Post ID.
	 * @since 1.6
	 */
	public function __construct( $post_id ) {
		$this->package = get_post( $post_id );
	}

	/**
	 * Checks if payment package is set.
	 *
	 * @since 1.6
	 */
	public function has_package() {
		return ! empty( $this->package );
	}

	/**
	 * Get payment package id.
	 *
	 * @since 1.6
	 */
	public function get_id() {
		return absint( $this->has_package() ? $this->package->ID : false );
	}

	/**
	 * Get payment package status.
	 *
	 * @since 1.6
	 */
	public function get_status() {
		if ( $this->has_package() ) {
			return $this->package->post_status;
		}
		return false;
	}

	/**
	 * Get WC Product object.
	 *
	 * @since 1.6
	 */
	public function get_product() {
		if ( empty( $this->product ) && $this->has_package() ) {
			$this->product = wc_get_product( $this->package->_product_id );
		}
		return $this->product;
	}

	/**
	 * Get WC Product ID.
	 *
	 * @since 1.6
	 */
	public function get_product_id() {
		return $this->get_product() ? $this->get_product()->get_id() : 0;
	}

	/**
	 * Get title: Use Product Name.
	 *
	 * @since 1.6
	 */
	public function get_title() {
		return $this->get_product() ? $this->get_product()->get_name() : '#' . $this->get_id();
	}

	/**
	 * Is payment package featured.
	 *
	 * @since 1.6
	 */
	public function is_featured() {
		return $this->package ? ( $this->package->_featured ? true : false ) : false;
	}

	/**
	 * Is payment package a claim payment package.
	 *
	 * @since 1.6
	 */
	public function is_use_for_claims() {
		return $this->package ? ( $this->package->_use_for_claims ? true : false ) : false;
	}

	/**
	 * Get payment package limit.
	 *
	 * @since 1.6
	 */
	public function get_limit() {
		return $this->package ? absint( $this->package->_limit ) : false;
	}

	/**
	 * Get payment package listing count.
	 *
	 * @since 1.6
	 */
	public function get_count() {
		return $this->package ? absint( $this->package->_count ) : false;
	}

	/**
	 * Get payment package remaining count.
	 *
	 * @since 1.6
	 */
	public function get_remaining_count() {
		return absint( absint( $this->get_limit() ) - absint( $this->get_count() ) );
	}

	/**
	 * Get payment package duration for listings.
	 *
	 * @since 1.6
	 */
	public function get_duration() {
		return $this->package ? absint( $this->package->_duration ) : false;
	}

	/**
	 * Get payment package order ID.
	 *
	 * @since 1.6
	 */
	public function get_order_id() {
		return $this->package ? absint( $this->package->_order_id ) : false;
	}

	/**
	 * Get payment package owner's user ID.
	 *
	 * @since 1.6
	 */
	public function get_user_id() {
		return $this->package ? absint( $this->package->_user_id ) : false;
	}
}
