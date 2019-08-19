<?php
/**
 * Listing submission handler.
 *
 * @since 1.0.0
 */

namespace MyListing\Ext\Paid_Listings;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Submission {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {

		// Account is required, because $0 package will skip order flow.
		add_filter( 'mylisting/settings/submission_requires_account', '__return_true' );

		// Register post status.
		add_action( 'init', [ $this, 'register_post_status' ] );
		add_filter( 'job_manager_valid_submit_job_statuses', [ $this, 'add_valid_listing_status' ] );

		// Submit Job.
		add_filter( 'submit_job_steps', [ $this, 'submit_listing_steps' ], 20 );

		// Implement field visibility by package.
		add_filter( 'mylisting/submission/fields', [ $this, 'listing_fields_visibility' ], 30, 2 );
		add_filter( 'mylisting/admin/submission/fields', [ $this, 'listing_fields_visibility' ], 30, 2 );

		// @todo: Handle cases when listing goes from published to pending when edited (new wpjm setting).
		// Decrease package count for listings that go from pending approval to trash.
		add_action( 'pending_to_trash', [ $this, 'decrease_package_count' ] );

		// Increase package count when listing is untrashed and status is set to pending approval.
		add_action( 'trash_to_pending', [ $this, 'increase_package_count' ] );
	}

	/**
	 * Register Listing Status.
	 *
	 * @since 1.0.0
	 */
	public function register_post_status() {
		register_post_status( 'pending_payment', [
			'label'                     => esc_html__( 'Pending Payment', 'my-listing' ),
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
			// translators: %s is label count.
			'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'my-listing' ),
		] );
	}

	/**
	 * Set "Pending Payment" as Valid Status.
	 *
	 * @since 1.0
	 */
	public function add_valid_listing_status( $statuses ) {
		$statuses[] = 'pending_payment';
		$statuses[] = 'expired';
		$statuses[] = 'publish';
		return $statuses;
	}

	/**
	 * Submit listing steps.
	 *
	 * @since 1.0
	 */
	public function submit_listing_steps( $steps ) {
		// retrieve and sanitize active listing and type ids if available
		$listing_id = ! empty( $_REQUEST['job_id'] ) ? absint( $_REQUEST['job_id'] ) : false;
		$listing_type = ! empty( $_REQUEST['listing_type'] ) ? $_REQUEST['listing_type'] : false;

		// if a listing id is available and valid, get the listing type instance from it (e.g. on prevew step handler)
		if ( $listing_id && ( $listing = \MyListing\Src\Listing::get( $listing_id ) ) && $listing->type ) {
			$type = $listing->type;
			// mlog( 'Type ID retrieved from given listing: '.$listing->get_id() );

		// if the lsiting id isn't available yet, e.g. in add listing form step handler, then retrieve the listing type from request
		} elseif ( $listing_type && ( $listing_type_obj = \MyListing\Ext\Listing_Types\Listing_Type::get_by_name( $listing_type ) ) ) {
			$type = $listing_type_obj;
			// mlog( 'Type ID retrieved from request.' );

		// otherwise, invalid listing type
		} else {
			$type = false;
			// mlog( 'No listing type was found.' );
		}

		// Check if paid listings are disabled for the active listing type.
		if ( $type && $type->settings['packages']['enabled'] === false ) {
			return $steps;
		}

		$steps['wc-choose-package'] = [
			'name'     => __( 'Choose a package', 'my-listing' ),
			'view'     => [ $this, 'choose_package' ],
			'handler'  => [ $this, 'choose_package_handler' ],
			'priority' => 5,
		];

		$steps['wc-process-package'] = [
			'name'     => '',
			'view'     => false,
			'handler'  => [ $this, 'process_package_handler' ],
			'priority' => 25,
		];

		return $steps;
	}

	/**
	 * Choose Package View
	 *
	 * @since 1.0
	 */
	public function choose_package() {
		if ( empty( $_REQUEST['listing_type'] ) || ! ( $type_obj = get_page_by_path( $_REQUEST['listing_type'], OBJECT, 'case27_listing_type' ) ) ) {
			return;
		}

		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();
		$type = \MyListing\Ext\Listing_Types\Listing_Type::get( $type_obj );
		$tree = Util::get_package_tree_for_listing_type( $type );

		$listing_id = ! empty( $_GET['job_id'] ) ? absint( $_GET['job_id'] ) : $form->get_job_id();
		?>
		<section class="i-section c27-packages">
			<div class="container">
				<div class="row section-title">
					<h2 class="case27-primary-text"><?php echo apply_filters( 'mylisting/paid-listings/choose-package/title', __( 'Choose a Package', 'my-listing' ) ) ?></h2>
					<p><?php echo apply_filters( 'mylisting/paid-listings/choose-package/description', '' ) ?></p>
				</div>
				<form method="post" id="job_package_selection">
					<div class="job_listing_packages">

						<?php require locate_template( 'templates/add-listing/choose-package.php' ) ?>

						<div class="hidden">
							<input type="hidden" name="job_id" value="<?php echo esc_attr( $listing_id ) ?>">
							<input type="hidden" name="step" value="<?php echo esc_attr( $form->get_step() ) ?>">
							<input type="hidden" name="job_manager_form" value="<?php echo esc_attr( $form->form_name ) ?>">
							<?php if ( ! empty( $_REQUEST['listing_type'] ) ): ?>
								<input type="hidden" name="listing_type" value="<?php echo esc_attr( $_REQUEST['listing_type'] ) ?>">
							<?php endif ?>
						</div>
					</div>
				</form>
			</div>
		</section>
		<?php
	}

	/**
	 * Choose package step handler.
	 *
	 * @since 1.0.0
	 */
	public function choose_package_handler() {
		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();
		try {
			if ( empty( $_POST['listing_package'] ) || empty( $_REQUEST['listing_type'] ) ) {
				throw new \Exception( _x( 'No package selected.', 'Listing submission', 'my-listing' ) );
			}

			if ( ! Util::validate_package( $_POST['listing_package'], $_REQUEST['listing_type'] ) ) {
				throw new \Exception( _x( 'Chosen package is not valid.', 'Listing submission', 'my-listing' ) );
			}

			// Package is valid.
			$package = get_post( $_POST['listing_package'] );

			// Store selection in cookie.
			wc_setcookie( 'chosen_package_id', absint( $package->ID ) );

			// Go to next step.
			$form->next_step();
		} catch (\Exception $e) {
			// Log error message and reset form step.
			$form->add_error( $e->getMessage() );
			$form->set_step( array_search( 'wc-choose-package', array_keys( $form->get_steps() ) ) );
		}
	}

	/**
	 * Process package step handler.
	 *
	 * @since 1.0
	 */
	public function process_package_handler() {
		$form = \MyListing\Src\Forms\Add_Listing_Form::instance();
		$listing_id = $form->get_job_id();

		try {
			if ( empty( $_COOKIE['chosen_package_id'] ) || ! $listing_id ) {
				throw new \Exception( _x( 'Couldn\'t process package.', 'Listing submission', 'my-listing' ) );
			}

			$package = get_post( $_COOKIE['chosen_package_id'] );
			if ( ! $package || ! in_array( $package->post_type, [ 'product', 'case27_user_package' ] ) ) {
				throw new \Exception( _x( 'Invalid package.', 'Listing submission', 'my-listing' ) );
			}

			$assignment = Util::assign_package_to_listing( $package->ID, $listing_id );
			if ( $assignment === false ) {
				throw new \Exception( _x( 'Couldn\'t assign package to listing.', 'Listing submission', 'my-listing' ) );
			}

			// Go to next step.
			$form->next_step();
		} catch (\Exception $e) {
			// Log error message.
			$form->add_error( $e->getMessage() );
		}
	}

	/**
	 * Field visibility conditions handler.
	 *
	 * @since 1.0
	 */
	public function listing_fields_visibility( $fields, $listing ) {
		return array_filter( $fields, function( $field ) use ( $listing ) {
			$conditions = new \MyListing\Src\Conditions( $field, $listing );
			return $conditions->passes();
		} );
	}

	public function decrease_package_count( $post ) {
		if ( $post->post_type !== 'job_listing' || ! $post->_user_package_id ) {
			return false;
		}

		// Check if package exists.
		if ( ! ( $package = get_post( $post->_user_package_id ) ) ) {
			return false;
		}

		// Update package count.
		case27_paid_listing_user_package_decrease_count( $package->ID );

		// Update package status.
		$status = case27_paid_listing_get_proper_status( $package );
		if ( $status && $package->post_status !== $status ) {
			wp_update_post( array(
				'ID'          => $package->ID,
				'post_status' => $status,
			) );
		}
	}

	public function increase_package_count( $post ) {
		if ( $post->post_type !== 'job_listing' || ! $post->_user_package_id ) {
			return false;
		}

		// Check if package exists.
		if ( ! ( $package = get_post( $post->_user_package_id ) ) ) {
			return false;
		}

		// Update package count.
		case27_paid_listing_user_package_increase_count( $package->ID );

		// Update package status.
		$status = case27_paid_listing_get_proper_status( $package );
		if ( $status && $package->post_status !== $status ) {
			wp_update_post( array(
				'ID'          => $package->ID,
				'post_status' => $status,
			) );
		}
	}
}
