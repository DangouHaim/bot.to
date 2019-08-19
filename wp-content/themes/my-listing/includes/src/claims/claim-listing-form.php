<?php

namespace MyListing\Src\Claims;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Claim_Listing_Form extends \MyListing\Src\Forms\Base_Form {
	use \MyListing\Src\Traits\Instantiatable;

	public
		$step = 0,
		$listing,
		$listing_id;

	public function __construct() {
		$this->listing_id = ! empty( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : 0;
		$this->listing = \MyListing\Src\Listing::get( $this->listing_id );
		if ( ! ( $this->listing_id && $this->listing && $this->listing->type ) ) {
			return;
		}

		$this->form_name = 'case27_paid_listing_submit_claim';

		// steps
		$this->steps = (array) [
			'login_register' => [
				'name'     => __( 'Login / Register', 'my-listing' ),
				'view'     => [ $this, 'login_register_view' ],
				'handler'  => [ $this, 'login_register_handler' ],
				'priority' => 1,
				'submit'   => __( 'Register Account &rarr;', 'my-listing' ),
			],
			'claim_package' => [
				'name'     => __( 'Choose a package', 'my-listing' ),
				'view'     => [ $this, 'claim_package_view' ],
				'handler'  => [ $this, 'claim_package_handler' ],
				'priority' => 2,
				'submit'   => __( 'Select Package &rarr;', 'my-listing' ),
			],
			'claim_listing' => [
				'name'     => __( 'Claim Listing', 'my-listing' ),
				'view'     => [ $this, 'claim_listing_view' ],
				'priority' => 4,
				'submit'   => __( 'Submit Claim &rarr;', 'my-listing' ),
			],
		];

		uasort( $this->steps, [ $this, 'sort_by_priority' ] );

		// Get step.
		if ( isset( $_POST['step'] ) ) {
			$this->step = is_numeric( $_POST['step'] ) ? max( absint( $_POST['step'] ), 0 ) : array_search( $_POST['step'], array_keys( $this->steps ) );
		} elseif ( ! empty( $_GET['step'] ) ) {
			$this->step = is_numeric( $_GET['step'] ) ? max( absint( $_GET['step'] ), 0 ) : array_search( $_GET['step'], array_keys( $this->steps ) );
		}
	}

	/**
	 * Login Register View
	 *
	 * @since 1.6
	 */
	public function login_register_view() {
		//
	}


	/**
	 * Login Register Handler. Also handle all request.
	 *
	 * @since 1.6
	 */
	public function login_register_handler() {
		mlog('claim handler');

		// Current Claim URL:
		$claim_url = add_query_arg( 'listing_id', $this->listing->get_id(), get_permalink() );

		// If not logged in, redirect user to login/register page.
		if ( ! is_user_logged_in() ) {
			return wp_safe_redirect( add_query_arg( [
				'redirect_to' => $claim_url,
				'notice' => 'login-required',
			], wc_get_page_permalink('myaccount') ) );
		}

		// User visiting claim page to claim a listing, check if they already submit claim.
		if ( ! isset( $_GET['_claim_id'] ) && is_user_logged_in() ) {
			$claims = get_posts( [
				'post_type' => 'claim',
				'posts_per_page' => 1,
				'fields' => 'ids',
				'meta_query' => [
					'relation' => 'AND',
					[ 'key' => '_listing_id', 'value' => absint( $this->listing_id ) ],
					[ 'key' => '_user_id', 'value' => absint( get_current_user_id() ) ],
				],
			] );

			// Claim found. Bail. Use it.
			if ( $claims && isset( $claims[0] ) ) {
				wp_safe_redirect( esc_url_raw( add_query_arg( '_claim_id', absint( $claims[0] ), $claim_url ) ) );
				exit;
			}
		}

		// Go to next step.
		$this->next_step();

		// If claim already set, move to next step.
		if ( isset( $_GET['_claim_id'] ) || $this->listing->is_claimed() ) {
			$this->next_step();
			return;
		}

		if ( ! empty( $_POST['listing_package'] ) ) {
			$listing = \MyListing\Src\Listing::get( $this->listing_id );
			if ( ! ( $listing && $listing->type && \MyListing\Ext\Paid_Listings\Util::validate_package( $_POST['listing_package'], $listing->type->get_slug() ) ) ) {
				return $this->add_error( _x( 'Failed to create claim.', 'Claim listing form', 'my-listing' ) );
			}

			// package is valid
			$package = get_post( $_POST['listing_package'] );

			// product selected
			if ( $package->post_type === 'product' ) {
				$product = wc_get_product( $package );
				if ( ! ( $product && $product->is_type( [ 'job_package', 'job_package_subscription' ] ) && get_post_meta( $product->get_id(), '_use_for_claims', true ) ) ) {
					return $this->add_error( _x( 'Invalid product selected.', 'Claim listing form', 'my-listing' ) );
				}

				$skip_checkout = apply_filters( 'mylisting\packages\free\skip-checkout', true ) === true;

				// If `skip-checkout` setting is enabled for free products,
				// create the user package and assign it to the listing.
				if ( $product->get_price() == 0 && $skip_checkout && $product->get_meta( '_disable_repeat_purchase' ) !== 'yes' ) {
					$user_package_id = case27_paid_listing_add_package( [
						'product_id'     => $product->get_id(),
						'duration'       => $product->get_duration(),
						'limit'          => $product->get_limit(),
						'featured'       => $product->is_listing_featured(),
						'use_for_claims' => $product->is_use_for_claims(),
					] );

					// Use it.
					if ( $user_package_id ) {
						$claim_id = \MyListing\Src\Claims\Claims::create( [
							'listing_id'      => $this->listing_id,
							'user_package_id' => $user_package_id,
						] );

						// Refresh, use claim ID in URL.
						wp_safe_redirect( esc_url_raw( add_query_arg( '_claim_id', absint( $claim_id ), $claim_url ) ) );
						exit;
					}

					return;
				}

				// otherwise add product to cart
				return case27_paid_listing_use_product_to_listing( $product->get_id(), $this->listing_id, $is_claim = true );
			}

			// user package selected
			if ( $package->post_type === 'case27_user_package' && is_user_logged_in() ) {
				$package = case27_paid_listing_get_package( $package->ID );
				if ( ! ( $package->has_package() && $package->get_status() === 'publish' && $package->is_use_for_claims() ) ) {
					return $this->add_error( _x( 'Invalid package selected.', 'Claim listing form', 'my-listing' ) );
				}

				// create claim
				$claim_id = \MyListing\Src\Claims\Claims::create( [
					'listing_id'      => $this->listing_id,
					'user_package_id' => $package->get_id(),
				] );

				if ( ! $claim_id ) {
					return $this->add_error( _x( 'Something went wrong. Please try again.', 'Claim listing form', 'my-listing' ) );
				}

				// success
				wp_safe_redirect( esc_url_raw( add_query_arg( '_claim_id', absint( $claim_id ), $claim_url ) ) );
				exit;
			}
		}
	}

	/**
	 * Select Claim Package View.
	 *
	 * @since 1.6
	 */
	public function claim_package_view() {
		$listing = \MyListing\Src\Listing::get( $this->listing_id );
		if ( ! ( $listing && $listing->type ) ) {
			return;
		}

		if ( $listing->get_author_id() === absint( get_current_user_id() ) ) {
			echo '<div class="job-manager-error">' . __( 'Invalid request. You cannot claim your own listing.', 'my-listing' ) . '</div>';
			return;
		}

		$tree = \MyListing\Ext\Paid_Listings\Util::get_package_tree_for_listing_type( $listing->type );
		$tree = array_filter( $tree, function( $item ) {
			return $item['product']->is_use_for_claims();
		} );
		?>
		<section class="i-section c27-packages">
			<div class="container">
				<div class="row section-title">
					<h2 class="case27-primary-text"><?php _e( 'Choose a Package', 'my-listing' ) ?></h2>
					<p><?php _e( 'Pricing', 'my-listing' ) ?></p>
				</div>
				<form method="post" id="job_package_selection">
					<div class="job_listing_packages">
						<?php require locate_template( 'templates/add-listing/choose-package.php' ) ?>
					</div>
				</form>
			</div>
		</section>
		<?php
	}

	/**
	 * Claim Listing View. Display claim info.
	 *
	 * @since 1.6
	 */
	public function claim_listing_view() {
		$claim_id = isset( $_GET['_claim_id'] ) ? $_GET['_claim_id'] : false;
		$listing_id = $this->listing_id;

		// validate
		$listing = get_post( $listing_id );
		if ( ! $listing || 'job_listing' !== $listing->post_type ) {
			echo '<div class="job-manager-error">' . __( 'Invalid listing.', 'my-listing' ) . '</div>';
			return;
		}

		// already claimed
		if ( $listing->_claimed && absint( get_current_user_id() ) !== absint( $listing->post_author ) ) {
			echo '<div class="job-manager-error">' . __( 'Invalid request. Listing already claimed.', 'my-listing' ) . '</div>';
			return;
		}

		// check claim
		$claim = get_post( $claim_id );
		if ( ! $claim || 'claim' !== $claim->post_type || absint( $listing_id ) !== absint( $claim->_listing_id ) || absint( get_current_user_id() ) !== absint( $claim->_user_id ) ) {
			echo '<div class="job-manager-error">' . __( 'Invalid claim.', 'my-listing' ) . '</div>';
			return;
		}

		// valid
		$redirect_url = add_query_arg( 'claim-id', $claim->ID, wc_get_account_endpoint_url( _x( 'claim-requests', 'Claims user dashboard page slug', 'my-listing' ) ) );
		?>
		<script type="text/javascript">
		    window.location = <?php echo wp_json_encode( $redirect_url ) ?>;
		</script>
		<?php
		exit;
	}
}
