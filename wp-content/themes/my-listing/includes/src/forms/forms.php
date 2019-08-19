<?php

namespace MyListing\Src\Forms;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Forms {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		// load posted form class for processing
		add_action( 'init', [ $this, 'load_posted_form' ] );

		// add 'my-listings' dashboard page
		add_action( 'mylisting/dashboard/endpoints-init', [ $this, 'user_listings_page' ] );

		// handle listing actions
		add_action( 'wp', [ $this, 'handle_listing_actions' ] );
		add_action( 'mylisting/user-listings/handle-action:delete', [ $this, 'handle_delete_action' ] );
		add_action( 'mylisting/user-listings/handle-view:edit', [ $this, 'render_edit_listing_form' ] );
	}

	public function load_posted_form() {
		if ( ! empty( $_POST['job_manager_form'] ) ) {
			$form = $_POST['job_manager_form'];

			if ( $form === 'submit-listing' ) {
				Add_Listing_Form::instance();
			}

			if ( $form === 'edit-listing' ) {
				Edit_Listing_Form::instance();
			}
		}
	}

	public function user_listings_page( $wc_endpoints ) {
		$wc_endpoints->add_page( [
			'endpoint' => 'my-listings',
			'title' => __( 'My Listings', 'my-listing' ),
			'template' => [ $this, 'user_listings_page_content' ],
			'show_in_menu' => true,
			'order' => 2,
		] );
	}

	public function user_listings_page_content() {
		// If doing an action, show conditional content if needed
		if ( ! empty( $_REQUEST['action'] ) ) {
			$action = sanitize_title( $_REQUEST['action'] );
			if ( has_action( 'mylisting/user-listings/handle-view:' . $action ) ) {
				return do_action( 'mylisting/user-listings/handle-view:' . $action );
			}
		}

		// get user listings
		$query = new \WP_Query;
		$query_args = [
			'post_type' => 'job_listing',
			'post_status' => [ 'publish', 'expired', 'pending' ],
			'ignore_sticky_posts' => 1,
			'posts_per_page' => 12,
			'paged' => ! empty( $_GET['pg'] ) ? absint( $_GET['pg'] ) : 1,
			'orderby' => 'date',
			'order' => 'DESC',
			'author' => get_current_user_id(),
		];

		$listings = array_filter( array_map( function( $item ) {
			return \MyListing\Src\Listing::get( $item );
		}, $query->query( $query_args ) ) );

		$stats = mylisting()->stats()->get_user_stats( get_current_user_id() );

		mylisting_locate_template( 'templates/dashboard/my-listings.php', compact( 'query', 'listings', 'stats' ) );
	}

	public function handle_listing_actions() {
		if ( ! class_exists( '\WooCommerce' ) || ! is_wc_endpoint_url( 'my-listings' ) || empty( $_REQUEST['action'] ) || empty( $_REQUEST['job_id'] ) ) {
			return;
		}

		try {
			if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'mylisting_dashboard_actions' ) ) {
				throw new \Exception( _x( 'Invalid request.', 'User Dashboard > Listings > Actions', 'my-listing' ) );
			}

			$action = sanitize_title( $_REQUEST['action'] );
			$listing = \MyListing\Src\Listing::get( $_REQUEST['job_id'] );
			if ( ! ( $listing && $listing->editable_by_current_user() ) ) {
				throw new \Exception( _x( 'Invalid listing.', 'User Dashboard > Listings > Actions', 'my-listing' ) );
			}

			do_action( 'mylisting/user-listings/handle-action:'.$action, $listing );
		} catch ( \Exception $e ) {
			$this->add_action_message( $e->getMessage() );
		}
	}

	public function handle_delete_action( $listing ) {
		wp_trash_post( $listing->get_id() );
		$this->add_action_message( sprintf( _x( '%s has been deleted', 'User Dashboard > Listings > Actions', 'my-listing' ), $listing->get_name() ), 'error' );
	}

	public function render_edit_listing_form() {
		Edit_Listing_Form::instance()->render();
	}

	public function add_action_message( $message, $type = 'message' ) {
		add_action( 'mylisting/user-listings/before', function() use ( $message, $type ) {
			printf( '<div class="job-manager-%s">%s</div>', esc_attr( $type ), $message );
		} );
	}
}