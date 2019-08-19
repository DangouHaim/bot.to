<?php

namespace MyListing\Src\Admin;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Single_Listing_Screen {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'save_post', [ $this, 'save_post' ], 1, 2 );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'mylisting/admin/save-listing-data', [ $this, 'save_listing_fields' ], 20, 2 );
		add_action( 'mylisting/admin/save-listing-data', [ $this, 'save_listing_settings' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'remove_taxonomy_metaboxes' ] );

		// display custom post statuses
		foreach ( [ 'post', 'post-new' ] as $hook ) {
			add_action( "admin_footer-{$hook}.php", [ $this, 'display_custom_post_statuses' ] );
		}
	}

	/**
	 * Handles `save_post` action.
	 *
	 * @since 2.1
	 */
	public function save_post( $post_id, $post ) {
		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) || $post->post_type !== 'job_listing' ) {
			return;
		}

		if ( is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || empty( $_POST['mylisting_save_fields_nonce'] ) || ! wp_verify_nonce( $_POST['mylisting_save_fields_nonce'], 'save_meta_data' ) ) {
			return;
		}

		do_action( 'mylisting/admin/save-listing-data', $post_id, \MyListing\Src\Listing::get( $post ) );
		do_action( 'job_manager_save_job_listing', $post_id, $post ); // legacy
	}

	/**
	 * Get the list of fields to be shown in
	 * the backend edit listing form.
	 *
	 * @since 2.1
	 */
	public function get_listing_fields() {
		global $post;

		$listing = \MyListing\Src\Listing::get( $post );
		if ( ! ( $listing && $listing->type ) ) {
			return [];
		}

		// get fields for this listing type
		$fields = $listing->type->get_fields();

		// Filter out fields set to be hidden from the backend submission form.
		$fields = array_filter( $fields, function( $field ) {
			return isset( $field['show_in_admin'] ) && $field['show_in_admin'] == true;
		} );

		// allow modifiying fields through filters
		$fields = apply_filters( 'mylisting/admin/submission/fields', $fields, $listing );

		// unset the title field on backend, to make use of the post title input in wp backend
		if ( isset( $fields['job_title'] ) ) {
			unset( $fields['job_title'] );
		}

		// in backend form, description field must be shown with high priority,
		// regardless of the order in the listing type editor
		if ( isset( $fields['job_description'] ) && is_array( $fields['job_description'] ) ) {
			$fields['job_description']['priority'] = 0.2;
		}

		// add expiry and author fields for permissible users
		if ( current_user_can( 'edit_others_posts' ) ) {
			$fields['job_author'] = array(
				'label'    => __( 'Listing Author', 'my-listing' ),
				'type'     => 'author',
				'priority' => 240,
			);

			$fields['job_expires'] = [
				'slug' => 'job_expires',
				'label' => __( 'Listing Expiry Date', 'my-listing' ),
				'type' => 'date',
				'required' => false,
				'placeholder' => '',
				'priority' => 250,
				'description' => '',
			];
		}

		// order by priority
		uasort( $fields, function( $a, $b ) {
			$first = isset( $a['priority'] ) ? $a['priority'] : 0;
			$second = isset( $b['priority'] ) ? $b['priority'] : 0;
			return $first - $second;
		} );

		return $fields;
	}

	/**
	 * Handles the saving of listing data fields.
	 *
	 * @since 2.1
	 */
	public function save_listing_fields( $post_id, $listing ) {
		foreach ( $this->get_listing_fields() as $key => $fieldarr ) {
			// check if field class exists
			$fieldclass = sprintf( '\MyListing\Src\Forms\Fields\%s_Field', c27()->file2class( $fieldarr['type'] ) );
			if ( ! class_exists( $fieldclass ) ) {
				mlog()->warn( sprintf( 'No class handler for field type %s found.', c27()->file2class( $fieldarr['type'] ) ) );
				continue;
			}

			// initiate class
			$field = new $fieldclass( $fieldarr );
			$field->set_listing( $listing );
			$field->set_listing_type( $listing->type );

			// update
			$field->update();
		}

		// set title
		update_post_meta( $post_id, '_job_title', $listing->get_name() );

		// update post description to have the same value as 'job_description'
		remove_action( 'save_post', [ $this, 'save_post' ], 1 );
		wp_update_post( [
			'ID' => $post_id,
			'post_content' => get_post_meta( $post_id, '_job_description', true ),
		] );
		add_action( 'save_post', [ $this, 'save_post' ], 1, 2 );

		// set post status to expired if already expired
		$expiry_date = get_post_meta( $post_id, '_job_expires', true );
		$today_date = date( 'Y-m-d', current_time( 'timestamp' ) );
		$is_listing_expired = $expiry_date && $today_date > $expiry_date;
		if ( $is_listing_expired && ! $this->is_listing_status_changing( null, 'draft' ) ) {
			remove_action( 'save_post', [ $this, 'save_post' ], 1 );
			if ( $this->is_listing_status_changing( 'expired', 'publish' ) ) {
				update_post_meta( $post_id, '_job_expires', \MyListing\Src\Listing::calculate_expiry( $post_id ) );
			} else {
				wp_update_post( [
					'ID' => $post_id,
					'post_status' => 'expired',
				] );
			}
			add_action( 'save_post', [ $this, 'save_post' ], 1, 2 );
		}
	}

	/**
	 * Handle changing the listing type, author, expiry date...
	 * in backend edit listing page.
	 *
	 * @since 2.0
	 */
	public function save_listing_settings( $post_id, $listing ) {
        if ( isset( $_POST['_case27_listing_type'] ) ) {
        	update_post_meta( $post_id, '_case27_listing_type', $_POST['_case27_listing_type'] );
        }

        if ( ! empty( $_POST['job_expires'] ) ) {
			update_post_meta( $post_id, '_job_expires', date( 'Y-m-d', strtotime( sanitize_text_field( $_POST['job_expires'] ) ) ) );
        } else {
        	mylisting_get_setting( 'submission_default_duration' )
        		? update_post_meta( $post_id, '_job_expires', \MyListing\Src\Listing::calculate_expiry( $post_id ) )
        		: delete_post_meta( $post_id, '_job_expires' );
        }

        if ( ! empty( $_POST['job_author'] ) ) {
			remove_action( 'save_post', [ $this, 'save_post' ], 1 );
			wp_update_post( [
				'ID' => $post_id,
				'post_author' => $_POST['job_author'] > 0 ? absint( $_POST['job_author'] ) : 0,
			] );
			add_action( 'save_post', [ $this, 'save_post' ], 1, 2 );
        }
	}

	/**
	 * Add custom meta boxes to single listing screen.
	 *
	 * @since 1.7.0
	 */
	public function add_meta_boxes() {
		// listing fields metabox
		add_meta_box(
			'job_listing_data',
			_x( 'Listing data', 'Listing fields metabox title', 'my-listing' ),
			[ $this, 'fields_metabox_content' ],
			'job_listing',
			'normal',
			'high'
		);

		// sidebar settings metabox
		add_meta_box(
			'cts_listing_sidebar_settings',
			_x( 'Listing Settings', 'Listing sidebar settings in wp-admin', 'my-listing' ),
			[ $this, 'sidebar_metabox_content' ],
			'job_listing',
			'side',
			'default'
		);
	}

	/**
	 * The sidebar settings box contents.
	 *
	 * @since 1.7.0
	 */
	public function sidebar_metabox_content( $listing ) {
		do_action( 'mylisting/admin/listing/sidebar-settings', \MyListing\Src\Listing::get( $listing ) );
	}

	/**
	 * The fields metabox contents.
	 *
	 * @since 2.1
	 */
	public function fields_metabox_content( $post ) {
		global $thepostid;
		$thepostid = $post->ID;
		$listing = \MyListing\Src\Listing::get( $post );

		echo '<div class="wp_job_manager_meta_data">';

		wp_nonce_field( 'save_meta_data', 'mylisting_save_fields_nonce' );

		require locate_template( 'templates/add-listing/form-fields/admin/select-listing-type.php' );

		echo '</div></div></div><div class="ml-admin-listing-form">';
		wp_enqueue_style( 'mylisting-admin-add-listing' );

		foreach ( $this->get_listing_fields() as $key => $field ) {
			if ( $key === 'job_title' ) {
				$field['value'] = $listing->get_name();
			} elseif ( $key === 'job_description' ) {
				$field['value'] = $listing->get_field( 'description' );
			} elseif ( $field['type'] === 'term-select' ) {
				$field['value'] = wp_get_object_terms( $listing->get_id(), $field['taxonomy'], [
					'orderby' => 'term_order',
					'order' => 'ASC',
					'fields' => 'ids',
				] );
			} else {
				$field['value'] = get_post_meta( $listing->get_id(), '_'.$key, true );
			}

			require locate_template( 'templates/add-listing/form-fields/admin/default.php' );
		}

		// @todo test this
		$user_edited_date = get_post_meta( $post->ID, '_job_edited', true );
		if ( $user_edited_date ) {
			echo '<p class="form-field">';
			echo '<em>' . sprintf( esc_html__( 'Listing was last modified by the user on %s.', 'my-listing' ), esc_html( date_i18n( get_option( 'date_format' ), $user_edited_date ) ) ) . '</em>';
			echo '</p>';
		}

		echo '</div><div><div><div>';
		echo '</div>';
	}

	/**
	 * Workaround to show custom post statuses in the
	 * status dropdown in admin edit listing page.
	 *
	 * @since 2.1
	 */
	public function display_custom_post_statuses() {
		global $post, $post_type;

		// Abort if we're on the wrong post type, but only if we got a restriction.
		if ( 'job_listing' !== $post_type ) {
			return;
		}

		// Get all non-builtin post status and add them as <option>.
		$options = '';
		$display = '';
		foreach ( \MyListing\Src\Listing::get_post_statuses() as $status => $name ) {
			$selected = selected( $post->post_status, $status, false );

			// If we one of our custom post status is selected, remember it.
			if ( $selected ) {
				$display = $name;
			}

			// Build the options.
			$options .= "<option{$selected} value='{$status}'>" . esc_html( $name ) . '</option>';
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function($) {
				<?php if ( ! empty( $display ) ) : ?>
					jQuery( '#post-status-display' ).html( <?php echo wp_json_encode( $display ); ?> );
				<?php endif; ?>

				var select = jQuery( '#post-status-select' ).find( 'select' );
				jQuery( select ).html( <?php echo wp_json_encode( $options ); ?> );
			} );
		</script>
		<?php
	}

	/**
	 * Checks if the listing status is being changed from $from_status to $to_status.
	 *
	 * @since 2.1
	 */
	private function is_listing_status_changing( $from_status, $to_status ) {
		return isset( $_POST['post_status'] ) && isset( $_POST['original_post_status'] )
			   && $_POST['original_post_status'] !== $_POST['post_status']
			   && ( null === $from_status || $from_status === $_POST['original_post_status'] )
			   && $to_status === $_POST['post_status'];
	}

	/**
	 * Remove taxonomy metaboxes in edit listing screen, since taxonomies
	 * can be edited through the listing form fields.
	 *
	 * @since 2.1
	 */
	public function remove_taxonomy_metaboxes() {
		remove_meta_box( 'job_listing_categorydiv', 'job_listing', 'normal' );
		remove_meta_box( 'regiondiv', 'job_listing', 'normal' );
		remove_meta_box( 'tagsdiv-case27_job_listing_tags', 'job_listing', 'normal' );
		foreach ( mylisting_custom_taxonomies() as $slug => $label ) {
			remove_meta_box( $slug.'div', 'job_listing', 'normal' );
		}
	}
}
