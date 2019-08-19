<?php

namespace MyListing\Includes;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Post_Types {
	use \MyListing\Src\Traits\Instantiatable;

	public function __construct() {
		// register listing post type and custom post statuses
		add_action( 'init', [ $this, 'register_post_types' ], 0 );
		add_action( 'after_switch_theme', [ $this, 'register_post_types' ], 0 );

		// register listing taxonomies
		add_action( 'init', [ $this, 'register_taxonomies' ], 0 );

		// add custom css classes
		add_filter( 'post_class', [ $this, 'listing_classes' ], 10, 3 );

		// when a user gets deleted, also remove his listings
		add_filter( 'post_types_to_delete_with_user', [ $this, 'delete_post_types_with_user' ], 10 );

		// calculate listing expiry on status change
		add_action( 'pending_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'pending_payment_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'preview_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'draft_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'auto-draft_to_publish', [ $this, 'set_expiry' ] );
		add_action( 'expired_to_publish', [ $this, 'set_expiry' ] );

		// schedule cron jobs
		$this->schedule_cron_jobs();

		// handle cron jobs
		add_action( 'mylisting/schedule:hourly', [ $this, 'check_for_expired_listings' ] );
		add_action( 'mylisting/schedule:daily', [ $this, 'delete_old_previews' ] );

		// update cache version
		add_action( 'save_post', [ $this, 'set_listing_cache_version' ] );
		add_action( 'delete_post', [ $this, 'set_listing_cache_version' ] );
		add_action( 'trash_post', [ $this, 'set_listing_cache_version' ] );

		// Delete attachment on delete post. 'delete_post' hook is too late.
		add_action( 'before_delete_post', [ $this, 'delete_listing_attachments' ] );

		// display a badge with the pending listings count in the admin menu
		add_filter( 'mylisting/admin/menu-item:edit.php?post_type=job_listing', [ $this, 'pending_listings_badge' ] );
	}

	/**
	 * Register listing post type and custom post statuses.
	 *
	 * @since 2.1
	 */
	public function register_post_types() {
		$permalink_structure = \MyListing\Src\Permalinks::get_permalink_structure();
		register_post_type( 'job_listing', [
			'labels' => [
				'name'               => _x( 'Listings', 'Listing post type labels', 'my-listing' ),
				'singular_name'      => _x( 'Listing', 'Listing post type labels', 'my-listing' ),
				'menu_name'          => _x( 'Listings', 'Listing post type labels', 'my-listing' ),
				'all_items'          => _x( 'All Listings', 'Listing post type labels', 'my-listing' ),
				'add_new'            => _x( 'Add new', 'Listing post type labels', 'my-listing' ),
				'add_new_item'       => _x( 'Add Listing', 'Listing post type labels', 'my-listing' ),
				'edit'               => _x( 'Edit', 'Listing post type labels', 'my-listing' ),
				'edit_item'          => _x( 'Edit Listing', 'Listing post type labels', 'my-listing' ),
				'new_item'           => _x( 'New Listing', 'Listing post type labels', 'my-listing' ),
				'view'               => _x( 'View Listing', 'Listing post type labels', 'my-listing' ),
				'view_item'          => _x( 'View Listing', 'Listing post type labels', 'my-listing' ),
				'search_items'       => _x( 'Search Listings', 'Listing post type labels', 'my-listing' ),
				'not_found'          => _x( 'No listings found', 'Listing post type labels', 'my-listing' ),
				'not_found_in_trash' => _x( 'No listings found in trash', 'Listing post type labels', 'my-listing' ),
				'parent'             => _x( 'Parent Listing', 'Listing post type labels', 'my-listing' ),
			],
			'rewrite' => [
				'slug'       => $permalink_structure['job_base'],
				'with_front' => false,
				'feeds'      => true,
				'pages'      => true, // enable pagination in listing archive page (e.g. site/listings/page/2/)
			],
			'description'         => '',
			'public'              => true,
			'show_ui'             => true,
			'capability_type'     => 'page',
			'map_meta_cap'        => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'hierarchical'        => false,
			'query_var'           => true,
			'supports'            => [ 'title', 'custom-fields', 'publicize', 'thumbnail', 'comments' ],
			'menu_position'       => 3,
			'has_archive'         => _x( 'listings', 'Listing post type archive slug', 'my-listing' ),
			'show_in_nav_menus'   => false,
			'delete_with_user'    => true,
		] );

		// register `expired` listing status
		register_post_status( 'expired', [
			'label'                     => _x( 'Expired', 'post status', 'my-listing' ),
			'public'                    => false, // @todo: make 'public' status optional, so expired listings can also be accessed from url (optionally).
			'protected'                 => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// translators: Placeholder %s is the number of expired posts of this type.
			'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', 'my-listing' ),
		] );

		// register `preview` listing status
		register_post_status( 'preview', [
				'label'                     => _x( 'Preview', 'post status', 'my-listing' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				// translators: Placeholder %s is the number of posts in a preview state.
				'label_count'               => _n_noop( 'Preview <span class="count">(%s)</span>', 'Preview <span class="count">(%s)</span>', 'my-listing' ),
		] );

		// register listing types
		register_post_type( 'case27_listing_type', [
			'labels'             => [
				'name'               => _x( 'Listing Types', 'post type general name', 'my-listing' ),
				'singular_name'      => _x( 'Listing Type', 'post type singular name', 'my-listing' ),
				'menu_name'          => _x( 'Listing Types', 'admin menu', 'my-listing' ),
				'name_admin_bar'     => _x( 'Listing Type', 'add new on admin bar', 'my-listing' ),
				'add_new'            => _x( 'Add New', 'Listing Type', 'my-listing' ),
				'add_new_item'       => __( 'Add New Listing Type', 'my-listing' ),
				'new_item'           => __( 'New Listing Type', 'my-listing' ),
				'edit_item'          => __( 'Edit Listing Type', 'my-listing' ),
				'view_item'          => __( 'View Listing Type', 'my-listing' ),
				'all_items'          => __( 'Listing Types', 'my-listing' ),
				'search_items'       => __( 'Search Listing Types', 'my-listing' ),
				'parent_item_colon'  => __( 'Parent Listing Types:', 'my-listing' ),
				'not_found'          => __( 'No Listing Types found.', 'my-listing' ),
				'not_found_in_trash' => __( 'No Listing Types found in Trash.', 'my-listing' )
			],
			'description'        => __( 'Create and manage custom listing types.', 'my-listing' ),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => [ 'slug' => 'case27_listing_type' ],
			'capability_type'    => 'page',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_icon'          => 'dashicons-menu',
			'supports'           => [ 'title', 'thumbnail' ],
		] );
	}

	/**
	 * Register listing taxonomies.
	 *
	 * @since 2.1
	 */
	public function register_taxonomies() {
		$permalink_structure = \MyListing\Src\Permalinks::get_permalink_structure();

		// register categories
		register_taxonomy( 'job_listing_category', 'job_listing', [
			'labels' => [
				'name'              => _x( 'Categories', 'Category taxonomy labels', 'my-listing' ),
				'singular_name'     => _x( 'Category', 'Category taxonomy labels', 'my-listing' ),
				'menu_name'         => _x( 'Categories', 'Category taxonomy labels', 'my-listing' ),
				'search_items'      => _x( 'Search Categories', 'Category taxonomy labels', 'my-listing' ),
				'all_items'         => _x( 'All Categories', 'Category taxonomy labels', 'my-listing' ),
				'parent_item'       => _x( 'Parent Category', 'Category taxonomy labels', 'my-listing' ),
				'parent_item_colon' => _x( 'Parent Category:', 'Category taxonomy labels', 'my-listing' ),
				'edit_item'         => _x( 'Edit Category', 'Category taxonomy labels', 'my-listing' ),
				'update_item'       => _x( 'Update Category', 'Category taxonomy labels', 'my-listing' ),
				'add_new_item'      => _x( 'Add New Category', 'Category taxonomy labels', 'my-listing' ),
				'new_item_name'     => _x( 'New Category Name', 'Category taxonomy labels', 'my-listing' ),
			],
			'rewrite' => [
				'slug' => $permalink_structure['category_base'],
				'with_front'   => false,
				'hierarchical' => false,
			],
			'hierarchical'          => true,
			'update_count_callback' => '_update_post_term_count',
			'show_ui'               => true,
			'show_tagcloud'         => false,
			'public'                => true,
		] );

		// register regions
		register_taxonomy( 'region', 'job_listing', [
			'labels' => [
				'name'                       => _x( 'Regions', 'taxonomy general name', 'my-listing' ),
				'singular_name'              => _x( 'Region', 'taxonomy singular name', 'my-listing' ),
				'search_items'               => __( 'Search Regions', 'my-listing' ),
				'popular_items'              => __( 'Popular Regions', 'my-listing' ),
				'all_items'                  => __( 'All Regions', 'my-listing' ),
				'parent_item'                => __( 'Parent Region', 'my-listing' ),
				'parent_item_colon'          => __( 'Parent Region:', 'my-listing' ),
				'edit_item'                  => __( 'Edit Region', 'my-listing' ),
				'update_item'                => __( 'Update Region', 'my-listing' ),
				'add_new_item'               => __( 'Add New Region', 'my-listing' ),
				'new_item_name'              => __( 'New Region Name', 'my-listing' ),
				'separate_items_with_commas' => __( 'Separate Regions with commas', 'my-listing' ),
				'add_or_remove_items'        => __( 'Add or remove Regions', 'my-listing' ),
				'choose_from_most_used'      => __( 'Choose from the most used Regions', 'my-listing' ),
				'not_found'                  => __( 'No Regions found.', 'my-listing' ),
				'menu_name'                  => __( 'Regions', 'my-listing' ),
			],
			'rewrite' => [
				'slug' => $permalink_structure['region_base'],
				'with_front'   => false,
				'hierarchical' => false,
			],
			'hierarchical' => true,
			'show_ui' => true,
			'update_count_callback' => '_update_post_term_count',
		] );

		// register tags
		register_taxonomy( 'case27_job_listing_tags', 'job_listing', [
			'labels' => [
				'name'                       => _x( 'Tags', 'taxonomy general name', 'my-listing' ),
				'singular_name'              => _x( 'Tag', 'taxonomy singular name', 'my-listing' ),
				'search_items'               => __( 'Search Tags', 'my-listing' ),
				'popular_items'              => __( 'Popular Tags', 'my-listing' ),
				'all_items'                  => __( 'All Tags', 'my-listing' ),
				'parent_item'                => null,
				'parent_item_colon'          => null,
				'edit_item'                  => __( 'Edit Tag', 'my-listing' ),
				'update_item'                => __( 'Update Tag', 'my-listing' ),
				'add_new_item'               => __( 'Add New Tag', 'my-listing' ),
				'new_item_name'              => __( 'New Tag Name', 'my-listing' ),
				'separate_items_with_commas' => __( 'Separate Tags with commas', 'my-listing' ),
				'add_or_remove_items'        => __( 'Add or remove Tags', 'my-listing' ),
				'choose_from_most_used'      => __( 'Choose from the most used Tags', 'my-listing' ),
				'not_found'                  => __( 'No Tags found.', 'my-listing' ),
				'menu_name'                  => __( 'Tags', 'my-listing' ),
			],
			'rewrite' => [
				'slug'         => $permalink_structure['tag_base'],
				'with_front'   => false,
				'hierarchical' => false,
			],
			'hierarchical'          => false,
			'show_ui'               => true,
			'update_count_callback' => '_update_post_term_count',
			'public'                => true,
		] );
	}

	/**
	 * Add custom listing classes to the `get_post_class()` method.
	 *
	 * @since 2.1
	 */
	public function listing_classes( $classes, $class, $post_id ) {
		$post = get_post( $post_id );
		if ( empty( $post ) || $post->post_type !== 'job_listing' ) {
			return $classes;
		}

		$classes[] = 'job_listing';
		$classes[] = (bool) $post->_featured ? 'job_position_featured' : '';

		return $classes;
	}

	/**
	 * Calculate listing expiry on status change.
	 *
	 * @param WP_Post $post
	 */
	public function set_expiry( $post ) {
		if ( 'job_listing' !== $post->post_type ) {
			return;
		}

		// See if it is already set.
		if ( metadata_exists( 'post', $post->ID, '_job_expires' ) ) {
			$expires = get_post_meta( $post->ID, '_job_expires', true );
			if ( $expires && strtotime( $expires ) < current_time( 'timestamp' ) ) {
				update_post_meta( $post->ID, '_job_expires', '' );
			}
		}

		// See if the user has set the expiry manually.
		if ( ! empty( $_POST['job_expires'] ) ) {
			update_post_meta( $post->ID, '_job_expires', date( 'Y-m-d', strtotime( sanitize_text_field( $_POST['job_expires'] ) ) ) );
		} elseif ( ! isset( $expires ) ) {
			// No manual setting? Lets generate a date if there isn't already one.
			$expires = \MyListing\Src\Listing::calculate_expiry( $post->ID );
			update_post_meta( $post->ID, '_job_expires', $expires );

			// In case we are saving a post, ensure post data is updated so the field is not overridden.
			if ( isset( $_POST['job_expires'] ) ) {
				$_POST['job_expires'] = $expires;
			}
		}
	}

	/**
	 * When a user gets deleted, also remove his listings.
	 *
	 * @since 2.1
	 */
	public function delete_post_types_with_user( $types ) {
		$types[] = 'job_listing';
		return $types;
	}

	public function schedule_cron_jobs() {
		if ( ! wp_next_scheduled( 'mylisting/schedule:hourly' ) ) {
			wp_schedule_event( time(), 'hourly', 'mylisting/schedule:hourly' );
		}

		if ( ! wp_next_scheduled( 'mylisting/schedule:daily' ) ) {
			wp_schedule_event( time(), 'daily', 'mylisting/schedule:daily' );
		}

		if ( ! wp_next_scheduled( 'mylisting/schedule:twicedaily' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'mylisting/schedule:twicedaily' );
		}
	}

	/**
	 * Maintenance task to expire listings.
	 */
	public function check_for_expired_listings() {
		global $wpdb;

		// Change status to expired.
		$listing_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
				LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
				WHERE postmeta.meta_key = '_job_expires'
				AND postmeta.meta_value > 0
				AND postmeta.meta_value < %s
				AND posts.post_status = 'publish'
				AND posts.post_type = 'job_listing'",
				date( 'Y-m-d', current_time( 'timestamp' ) )
			)
		);

		if ( $listing_ids ) {
			foreach ( $listing_ids as $listing_id ) {
				wp_update_post( [
					'ID' => $listing_id,
					'post_status' => 'expired',
				] );
			}
		}

		// Delete old expired listings.
		if ( apply_filters( 'mylisting/delete-expired-listings', false ) ) {
			$listing_ids = $wpdb->get_col(
				$wpdb->prepare( "
					SELECT posts.ID FROM {$wpdb->posts} as posts
					WHERE posts.post_type = 'job_listing'
					AND posts.post_modified < %s
					AND posts.post_status = 'expired'",
					date( 'Y-m-d', strtotime( '-' . apply_filters( 'mylisting/expired-listings-days-limit', 30 ) . ' days', current_time( 'timestamp' ) ) )
				)
			);

			if ( $listing_ids ) {
				foreach ( $listing_ids as $listing_id ) {
					wp_trash_post( $listing_id );
				}
			}
		}
	}

	/**
	 * Deletes old previewed listings after 30 days to keep the DB clean.
	 */
	public function delete_old_previews() {
		global $wpdb;

		// Delete old previewed listings.
		$listing_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT posts.ID FROM {$wpdb->posts} as posts
				WHERE posts.post_type = 'job_listing'
				AND posts.post_modified < %s
				AND posts.post_status = 'preview'",
				date( 'Y-m-d', strtotime( '-30 days', current_time( 'timestamp' ) ) )
			)
		);

		if ( $listing_ids ) {
			foreach ( $listing_ids as $listing_id ) {
				wp_delete_post( $listing_id, true );
			}
		}
	}

	public function set_listing_cache_version( $post_id ) {
		if ( get_post_type( $post_id ) === 'job_listing' ) {
			mlog( '[POST TYPES] Updating cache version for listings.' );
			\MyListing\Src\Cache::update_version( 'get_listings' );
		}
	}

	public function delete_listing_attachments( $post_id ) {
		if ( 'job_listing' !== get_post_type( $post_id ) ) {
			return;
		}

		// Get all attachments IDs. Maybe need settings to enable this.
		$att_ids = get_posts( [
			'numberposts' => -1,
			'post_type'   => 'attachment',
			'fields'      => 'ids',
			'post_status' => 'any',
			'post_parent' => $post_id,
		] );

		// Delete each attachments.
		if ( $att_ids && is_array( $att_ids ) ) {
			foreach( $att_ids as $id ) {
				wp_delete_attachment( $id, true );
			}
		}
	}

	/**
	* Display a badge with the pending listings count in the admin menu.
	*
	* @since 2.1.1
	*/
	public function pending_listings_badge( $menu_item ) {
		$counts = wp_count_posts( 'job_listing', 'readable' );
		if ( is_object( $counts) && ! empty( $counts->pending ) && absint( $counts->pending ) > 0 ) {
			$menu_item[0] .= sprintf( ' <span class="awaiting-mod update-plugins">%s</span>', number_format_i18n( $counts->pending ) );
		}

		return $menu_item;
	}
}
