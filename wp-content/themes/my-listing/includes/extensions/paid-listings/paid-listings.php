<?php
/**
 * Paid Listings module.
 *
 * @since   1.6
 * @license GNU General Public License v3.0 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @copyright:
 *     2019 27collective
 *     2017 Astoundify
 *     2015 Automattic
 */

namespace MyListing\Ext\Paid_Listings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Paid_Listings {

	public static function init() {
		// bail early if WooCommerce is not active or Paid Listings aren't enabled
		if ( ! ( class_exists( '\WooCommerce' ) && mylisting_get_setting( 'paid_listings_enabled' ) ) ) {
			return;
		}

		// Load Functions.
		require_once locate_template( 'includes/extensions/paid-listings/functions.php' );

		// Migrate WPJM WC Paid Listing DB.
		WCPL_Importer::instance();

		// Load User Packages.
		User_Packages::instance();

		// load claims
		if ( mylisting_get_setting( 'claims_enabled' ) ) {
			\MyListing\Src\Claims\Claims::instance();
		}

		// Submission steps handler.
		Submission::instance();

		// WooCommerce.
		require_once locate_template( 'includes/extensions/paid-listings/class-woocommerce.php' );

		// Switch Package.
		if ( apply_filters( 'case27_paid_listing_allow_switch_package', true ) ) {
			Switch_Package::instance();
		}

		// WC Subscriptions.
		Subscriptions\Subscriptions::instance();
	}
}
