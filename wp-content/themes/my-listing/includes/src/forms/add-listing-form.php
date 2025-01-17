<?php

namespace MyListing\Src\Forms;

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Handles frontend Add Listing form.
 *
 * @since 2.1
 */
class Add_Listing_Form extends Base_Form {
	use \MyListing\Src\Traits\Instantiatable;

	// form name
	public $form_name = 'submit-listing';

	// listing id
	protected $job_id;

	public function __construct() {
		add_action( 'wp', array( $this, 'process' ) );
		if ( $this->use_recaptcha_field() ) {
			add_action( 'mylisting/add-listing/form-fields/end', [ $this, 'display_recaptcha_field' ] );
			add_action( 'mylisting/submission/validate-fields', [ $this, 'validate_recaptcha_field' ] );
		}

		// "skip preview" functionality
		add_filter( 'submit_job_steps', [ $this, 'maybe_skip_preview' ] );

		$this->steps = (array) apply_filters( 'submit_job_steps', [
			'submit'  => array(
				'name'     => __( 'Submit Details', 'my-listing' ),
				'view'     => array( $this, 'submit' ),
				'handler'  => array( $this, 'submit_handler' ),
				'priority' => 10,
			),
			'preview' => array(
				'name'     => __( 'Preview', 'my-listing' ),
				'view'     => array( $this, 'preview' ),
				'handler'  => array( $this, 'preview_handler' ),
				'priority' => 20,
			),
			'done'    => array(
				'name'     => __( 'Done', 'my-listing' ),
				'view'     => array( $this, 'done' ),
				'priority' => 30,
			),
		] );

		uasort( $this->steps, array( $this, 'sort_by_priority' ) );

		// get step
		if ( isset( $_POST['step'] ) ) {
			$this->step = is_numeric( $_POST['step'] ) ? max( absint( $_POST['step'] ), 0 ) : array_search( intval( $_POST['step'] ), array_keys( $this->steps ), true );
		} elseif ( ! empty( $_GET['step'] ) ) {
			$this->step = is_numeric( $_GET['step'] ) ? max( absint( $_GET['step'] ), 0 ) : array_search( intval( $_GET['step'] ), array_keys( $this->steps ), true );
		}

		$this->job_id = ! empty( $_REQUEST['job_id'] ) ? absint( $_REQUEST['job_id'] ) : 0;

		if ( ! \MyListing\Src\Listing::user_can_edit( $this->job_id ) ) {
			$this->job_id = 0;
		}

		// Load job details.
		if ( $this->job_id ) {
			$job_status = get_post_status( $this->job_id );
			if ( 'expired' === $job_status ) {
				if ( ! \MyListing\Src\Listing::user_can_edit( $this->job_id ) ) {
					$this->job_id = 0;
					$this->step = 0;
				}
			} elseif ( ! in_array( $job_status, apply_filters( 'job_manager_valid_submit_job_statuses', [ 'preview' ] ), true ) ) {
				$this->job_id = 0;
				$this->step = 0;
			}
		}
	}

	/**
	 * Gets the submitted job ID.
	 *
	 * @return int
	 */
	public function get_job_id() {
		return absint( $this->job_id );
	}

	/**
	 * Initializes the fields used in the form.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		// default fields
		$this->fields = [
			'job_title' => [
				'label'       => __( 'Title', 'my-listing' ),
				'type'        => 'text',
				'required'    => true,
				'placeholder' => '',
				'priority'    => 1,
				'slug' => 'job_title',
			],
			'job_description' => [
				'label'    => __( 'Description', 'my-listing' ),
				'type'     => 'wp-editor',
				'required' => true,
				'priority' => 5,
				'slug' => 'job_description',
			],
		];

		$listing = null;
		$type = null;

		// Submit listing form: Listing type is passed as a POST parameter.
		if ( $type_slug = c27()->get_submission_listing_type() ) {
			$type = \MyListing\Ext\Listing_Types\Listing_Type::get_by_name( $type_slug );
		}

		// Edit listing form: Listing ID is available as a GET parameter.
		if ( ! empty( $_REQUEST['job_id'] ) ) {
			$listing = \MyListing\Src\Listing::get( $_REQUEST['job_id'] );
			if ( ! ( $listing && $listing->type ) ) {
				return;
			}

			$type = $listing->type;
		}

		// If a listing type wasn't retrieved, return empty fields.
		if ( ! $type ) {
			return;
		}

		// get fields from listing type object
		$fields = $type->get_fields();

		// filter out fields set to be hidden from the frontend submission form
		$fields = array_filter( $fields, function( $field ) {
			return isset( $field['show_in_submit_form'] ) && $field['show_in_submit_form'] == true;
		} );

		$fields = apply_filters( 'mylisting/submission/fields', $fields, $listing );

		$this->fields = $fields;
	}

	/**
	 * Use reCAPTCHA field on the form?
	 *
	 * @return bool
	 */
	public function use_recaptcha_field() {
		if ( ! $this->is_recaptcha_available() ) {
			return false;
		}
		return mylisting_get_setting( 'recaptcha_show_in_submission' );
	}

	/**
	 * Displays the form.
	 */
	public function submit() {
		$this->init_fields();
		// Load data if neccessary.
		if ( $this->job_id && ( $listing = \MyListing\Src\Listing::get( $this->job_id ) ) ) {
			
			foreach ( $this->fields as $key => $field ) {
				// form has been submitted, value is retrieved from $_POST through `validate_fields` method.
				if ( isset( $field['value'] ) ) {
					continue;
				}

				if ( $key === 'job_title' ) {
					$this->fields['job_title']['value'] = $listing->get_name();
				} elseif ( $key === 'job_description' ) {
					$this->fields['job_description']['value'] = $listing->get_field( 'description' );
				} elseif ( $field['type'] === 'term-select' ) {
					$this->fields[ $key ]['value'] = wp_get_object_terms( $listing->get_id(), $field['taxonomy'], [
						'orderby' => 'term_order',
						'order' => 'ASC',
						'fields' => 'ids',
					] );
				} else {
					$this->fields[ $key ]['value'] = get_post_meta( $listing->get_id(), '_'.$key, true );
				}
			}
		}

		mylisting_locate_template( 'templates/add-listing/submit-form.php', [
			'form' => $this->form_name,
			'job_id' => $this->get_job_id(),
			'action' => $this->get_action(),
			'fields' => $this->fields,
			'step'=> $this->get_step(),
			'submit_button_text' => apply_filters( 'submit_job_form_submit_button_text', __( 'Preview', 'my-listing' ) ),
		] );
	}

	/**
	 * Handles the submission of form data.
	 *
	 * @throws \Exception On validation error.
	 */
	public function submit_handler() {
		if ( empty( $_POST['submit_job'] ) ) {
			return;
		}

		// get the listing type
		if ( ! empty( $this->job_id ) ) {
			$listing = \MyListing\Src\Listing::get( $this->job_id );
			$type = $listing ? $listing->type : false;
		} elseif ( $type_slug = c27()->get_submission_listing_type() ) {
			$type = \MyListing\Ext\Listing_Types\Listing_Type::get_by_name( $type_slug );
		}

		$this->listing_type = $type;

		// if field validation throws any errors, cancel submission
		$this->validate_fields();
		if ( ! empty( $this->errors ) ) {
			return;
		}

		$description = isset( $this->fields['job_description'] ) ? $this->fields['job_description']['value'] : '';

		// validation passed successfully, update the listing
		$this->save_listing( $this->fields['job_title']['value'], $description, $this->job_id ? '' : 'preview' );
		$this->update_listing_data();

		// add custom listing data
		do_action( 'mylisting/submission/save-listing-data', $this->job_id, $this->fields );

		// successful, show next step
		$this->step++;
	}

	public function validate_fields() {
		try {
			// check if it's a valid listing type
			if ( ! $this->listing_type ) {
				throw new \Exception( _x( 'Invalid listing type', 'Add listing form', 'my-listing' ) );
			}

			// make sure the user is logged in if submission requires an account
			if ( mylisting_get_setting( 'submission_requires_account' ) && ! is_user_logged_in() ) {
				throw new \Exception( _x( 'You must be signed in to post a new listing.', 'Add listing form', 'my-listing' ) );
			}
		} catch ( \Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}

		// get form fields
		$this->init_fields();

		// field validation
		foreach ( $this->fields as $key => $fieldarr ) {
			// check if field class exists
			$fieldclass = sprintf( '\MyListing\Src\Forms\Fields\%s_Field', c27()->file2class( $fieldarr['type'] ) );
			if ( ! class_exists( $fieldclass ) ) {
				continue;
			}

			// get posted value
			$field = new $fieldclass( $fieldarr );
			$field->set_listing_type( $this->listing_type );
			$value = $field->get_posted_value();

			// save posted value
			$this->fields[ $key ]['value'] = $value;

			// validate values
			try {
				$field->check_validity();
			} catch ( \Exception $e ) {
				$this->add_error( $e->getMessage() );
			}
		}

		// custom validation rules
		try {
			do_action( 'mylisting/submission/validate-fields', $this->fields );
		} catch( \Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}
	}

	/**
	 * Updates or creates a listing from posted data.
	 *
	 * @since 2.1
	 */
	protected function save_listing( $post_title, $post_content, $status = 'preview' ) {
		$data = [
			'post_title' => $post_title,
			'post_name' => sanitize_title( $post_title ), // update slug
			'post_content' => $post_content,
			'post_type' => 'job_listing',
			'comment_status' => 'open',
		];

		if ( $status ) {
			$data['post_status'] = $status;
		}

		$data = apply_filters( 'mylisting/submission/save-listing-arr', $data, $this->job_id );

		if ( $this->job_id ) {
			$data['ID'] = $this->job_id;
			wp_update_post( $data );
		} else {
			$this->job_id = wp_insert_post( $data );
		}
	}

	/**
	 * Sets listing meta and terms based on posted values.
	 *
	 * @since 2.1
	 */
	protected function update_listing_data() {
		$listing = \MyListing\Src\Listing::get( $this->job_id );

		/**
		 * Attach the listing type to the listing. Ensures it's the submission form,
		 * before the listing with preview status has been created.
		 *
		 * @since 2.0
		 */
		if ( empty( $_REQUEST['job_id'] ) && ( $listing_type = c27()->get_submission_listing_type() ) ) {
			if ( $type = \MyListing\Ext\Listing_Types\Listing_Type::get_by_name( $listing_type ) ) {
				update_post_meta( $this->job_id, '_case27_listing_type', $type->get_slug() );
			}
		}

		/**
		 * Update listing meta data.
		 *
		 * @since 2.1
		 */
		foreach ( $this->fields as $key => $fieldarr ) {
			// check if field class exists
			$fieldclass = sprintf( '\MyListing\Src\Forms\Fields\%s_Field', c27()->file2class( $fieldarr['type'] ) );
			if ( ! class_exists( $fieldclass ) ) {
				continue;
			}

			// initiate class
			$field = new $fieldclass( $fieldarr );
			$field->set_listing( $listing );
			$field->set_listing_type( $listing->type );

			// update
			$field->update();
		}

		if ( isset( $_POST['job_description'] ) ) {
			update_post_meta( $this->job_id, '_job_description', wp_kses_post( $_POST['job_description'] ) );
		}

		update_post_meta( $this->job_id, 'botLinks', false );

		if(get_transient("botLinks")) {
			update_post_meta( $this->job_id, 'botLinks', get_transient("botLinks") );
		}
	}

	/**
	 * Displays preview of Job Listing.
	 */
	public function preview() {
		if ( ! $this->job_id ) {
			mlog()->warn( 'No listing id provided.' );
			return;
		}

		global $post;
		$post = get_post( $this->job_id );
		$post->post_status = 'preview';
		setup_postdata( $post );
		mylisting_locate_template( 'templates/add-listing/preview.php', [ 'form' => $this ] );
		wp_reset_postdata();
	}

	/**
	 * Handles the preview step form response.
	 */
	public function preview_handler() {
		if ( ! $_POST ) {
			return;
		}

		// Edit = show submit form again.
		if ( ! empty( $_POST['edit_job'] ) ) {
			$this->step--;
		}

		// Continue = change listing status then show next screen.
		if ( ! empty( $_POST['continue'] ) ) {
			$listing = \MyListing\Src\Listing::get( $this->job_id );

			if ( in_array( $listing->get_status(), [ 'preview', 'expired' ], true ) ) {
				// Reset expiry.
				delete_post_meta( $listing->get_id(), '_job_expires' );

				wp_update_post( [
					'ID' => $listing->get_id(),
					'post_status' => apply_filters( 'submit_job_post_status', mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish', $listing->get_data() ),
					'post_date' => current_time( 'mysql' ),
					'post_date_gmt' => current_time( 'mysql', 1 ),
					'post_author' => get_current_user_id(),
				] );
			}

			$this->step++;
		}
	}

	/**
	 * Displays the final screen after a listing has been submitted.
	 */
	public function done() {
		// handle skip preview
		$this->skip_preview_handler( $this->job_id );

		// done, force get listing
		$listing = \MyListing\Src\Listing::force_get( $this->job_id );
		do_action( 'mylisting/submission/done', $this->job_id );
		do_action( 'job_manager_job_submitted', $this->job_id );

		if ( $listing ) {
			require locate_template( 'templates/add-listing/done.php' );
		}
	}

	/**
	 * Handle "Skip preview" button functionality in Add Listing page.
	 *
	 * @since 2.0
	 */
	public function maybe_skip_preview( $steps ) {
		if ( ! empty( $_POST['submit_job'] ) && $_POST['submit_job'] === 'submit--no-preview' && isset( $steps['preview'] ) ) {
			unset( $steps['preview'] );
		}

		return $steps;
	}

	/**
	 * Handle "Skip preview" when paid listings are disabled.
	 *
	 * @since 2.0
	 */
	public function skip_preview_handler( $listing_id ) {
		$listing = \MyListing\Src\Listing::force_get( $listing_id );
		if ( ! ( $listing && in_array( $listing->get_status(), [ 'preview', 'expired' ], true ) ) ) {
			return;
		}

		delete_post_meta( $listing->get_id(), '_job_expires' );
		wp_update_post( [
			'ID' => $listing->get_id(),
			'post_status' => apply_filters( 'submit_job_post_status', mylisting_get_setting( 'submission_requires_approval' ) ? 'pending' : 'publish', $listing->get_data() ),
			'post_date' => current_time( 'mysql' ),
			'post_date_gmt' => current_time( 'mysql', 1 ),
			'post_author' => get_current_user_id(),
		] );
	}
}
