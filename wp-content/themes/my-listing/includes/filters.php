<?php

namespace MyListing\Includes;

class Filters {
    use \MyListing\Src\Traits\Instantiatable;

	protected $actions = [
        'tgmpa_register',
        'case27_footer',
        'after_switch_theme',
	];

	protected $filters = [
		'query_vars',
        'get_the_archive_title',
        'case27_featured_service_content',
        'body_class',
        'admin_menu',
	];

	public function __construct() {
		$this->add_actions();
		$this->add_filters();

        add_action( 'edited_term', [ $this, 'edited_term' ], 30, 3 );
        add_action( 'create_term', [ $this, 'edited_term' ], 30, 3 );
        add_action( 'delete_term', [ $this, 'edited_term' ], 30, 3 );

        add_filter( 'case27\listing\cover\field\job_location', [ $this, 'filter_listing_address' ] );
        add_filter( 'case27\listing\preview\info_field\job_location', [ $this, 'filter_listing_address' ] );
        add_filter( 'case27\listing\preview\button\job_location', [ $this, 'filter_listing_address' ] );
        add_filter( 'case27\listing\preview\detail\job_location', [ $this, 'filter_listing_address' ] );
        add_filter( 'case27\listing\preview\quick_view\job_location', [ $this, 'filter_listing_address' ] );

        add_filter( 'option_category_base', function( $base ) {
            if ( ! $base || $base == 'category' ) {
                return 'post-category';
            }

            return $base;
        });

        add_filter( 'option_tag_base', function( $base ) {
            if ( ! $base || $base == 'tag' ) {
                return 'post-tag';
            }

            return $base;
        });

        add_filter( 'pre_option_job_category_base', function( $base ) {
            if ( ! $base || $base == 'listing-category' || $base == 'job-category' ) {
                return 'category';
            }

            return $base;
        });
	}

	public function register_action( $action ) {
		if ( ! in_array( $action, $this->actions ) ) {
			$this->actions[] = $action;
		}
	}

	public function register_filter( $filter ) {
		if ( ! in_array( $filter, $this->filters ) ) {
			$this->filters[] = $filter;
		}
	}

    /*
     * Register Filters.
     */
	public function add_filters() {
		foreach ($this->filters as $callback => $filter) {
			$callback = !is_numeric($callback) ? $callback : $filter;
            $priority = 10; $accepted_args = 1;

            if (is_array($filter)) {
                $_filter = $filter;

                $filter = $_filter['filter'];
                $callback = $_filter['callback'];
                $priority = $_filter['priority'];
                $accepted_args = $_filter['accepted_args'];
            }

			add_filter( $filter, array($this, "filter_{$callback}"), $priority, $accepted_args );
		}
	}

    /*
     * Register Actions.
     */
	public function add_actions() {
		foreach ( $this->actions as $callback => $action ) {
			$callback = ! is_numeric($callback) ? $callback : $action;
			add_action( $action, array( $this, "action_{$callback}" ) );
		}
	}

    public function filter_query_vars( $vars ) {
    	$vars[] = 'listing_type';
    	return $vars;
    }

    public function filter_woocommerce_locate_template( $template ) {
    	$_template_name = explode( '/templates/', $template );
    	$template_name = array_pop( $_template_name );
    	$template_path = CASE27_INTEGRATIONS_DIR . "/woocommerce/templates/{$template_name}";

    	if ( locate_template("includes/integrations/woocommerce/templates/{$template_name}") && file_exists($template_path) ) {
            // do_action("case27_woocommerce_template_{$template_name}_before")
			return $template_path;
            // do_action("case27_woocommerce_template_{$template_name}_after")
		}

    	return $template;
    }

    public function filter_body_class( $classes ) {
        if ( is_singular( 'job_listing' ) ) {
            global $post;
            $listing = \MyListing\Src\Listing::get( $post );

            if ( $post->_case27_listing_type ) {
                $classes[] = "single-listing";
                $classes[] = "type-{$post->_case27_listing_type}";
            }

            if ( $post->_package_id ) {
                $classes[] = "package-{$post->_package_id}";
            }

            if ( $listing->get_data( '_claimed' ) ) {
                $classes[] = 'c27-verified';
            }
        }

        $classes[] = 'my-listing';

        return $classes;
    }

    public function action_after_switch_theme() {
        flush_rewrite_rules();
    }

    public function filter_get_the_archive_title( $title ) {
        if ( ! class_exists('WooCommerce') ) return $title;

        if ( is_woocommerce() ) {
            $title = woocommerce_page_title(false);
        } elseif ( is_cart() || is_checkout() || is_account_page() || is_page() ) {
            $title = get_the_title();
        } elseif ( is_home() ) {
            $title = apply_filters( 'the_title', get_the_title( get_option( 'page_for_posts' ) ) );
        }

        return $title;
    }

    /**
     * Register theme required plugins using TGM Plugin Activation library.
     *
     * @since 1.0
     */
    public function action_tgmpa_register() {
        // List of plugins to install.
        $plugins = [
            [
                'name' => __( 'Elementor', 'my-listing' ),
                'slug' => 'elementor',
                'required' => true, // If false, the plugin is only 'recommended' instead of required.
                'force_activation' => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
            ],
            [
                'name' => __( 'WooCommerce', 'my-listing' ),
                'slug' => 'woocommerce',
                'required' => true,
                'force_activation' => true,
            ],
            [
                'name' => __( 'Contact Form 7', 'my-listing' ),
                'slug' => 'contact-form-7',
                'required' => false,
                'force_activation' => false,
            ],
        ];

        // Array of configuration settings.
        $config = array(
            'id'           => 'case27',
            'default_path' => c27()->template_path('includes/plugins/'),
            'dismissable'  => true,
            'is_automatic' => true,
        );

        tgmpa( $plugins, $config );
    }

    public function filter_case27_featured_service_content( $content ) {
        if ( ! trim( $content ) ) {
            return $content;
        }

        $dom = new \DOMDocument;
        $dom->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );

        foreach ( ['h1', 'h2', 'h3'] as $tagSelector) {
            foreach ( $dom->getElementsByTagName( $tagSelector ) as $tag ) {
                $tag->setAttribute( 'class', $tag->getAttribute( 'class' ) . ' case27-primary-text' );
            }
        }

        return $dom->saveHTML();
    }

    public function action_case27_footer() {
        ?>
        <style type="text/css">
            <?php echo $GLOBALS['case27_custom_styles'] ?>
        </style>
        <?php

        if ( c27()->get_setting('custom_code') ) {
            echo c27()->get_setting('custom_code');
        }
    }

    public function filter_admin_menu() {
        $user = wp_get_current_user();

        if ( ! in_array( 'administrator', $user->roles ) ) {
            remove_menu_page( 'ai1wm_export' );
            remove_submenu_page( 'ai1wm_export', 'ai1wm_import' );
            remove_submenu_page( 'ai1wm_export', 'ai1wm_backups' );
        }
    }

    public function edited_term( $term_id, $tt_id, $taxonomy ) {
        update_option( 'listings_tax_' . $taxonomy . '_version', time() );
    }

    public function filter_listing_address( $address ) {
        if ( ! apply_filters( 'case27\listing\location\short_address', true ) ) {
            return $address;
        }

        $parts = explode(',', $address);
        return trim( $parts[0] );
    }
}

// @todo: relocate
add_action( 'edited_term_taxonomy', function( $term, $tax ) {
    if ( in_array( $tax, array_merge( [ 'job_listing_category', 'case27_job_listing_tags', 'region' ], mylisting_custom_taxonomies( 'slug', 'slug' ) ) ) ) {
        $query = new \WP_Query([
            'posts_per_page' => -1,
            'post_type' => 'job_listing',
            'post_status' => 'publish',
            'tax_query' => [[
                'taxonomy' => $tax,
                'field' => 'id',
                'terms' => $term,
            ]],
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        update_term_meta( $term, 'listings_full_count', $query->post_count );
        update_option( 'listings_tax_' . $tax . '_version', time() );
    }
}, 50, 2 );

add_filter( 'comment_form_defaults', function( $fields ) {
    if ( class_exists( 'WooCommerce' ) ) {
        $login_url = add_query_arg(
            'redirect_to',
            urlencode( apply_filters( 'the_permalink', get_permalink( get_the_ID() ), get_the_ID() ) ),
            wc_get_page_permalink('myaccount')
        );

        $fields['must_log_in'] = '<p class="must-log-in">' . sprintf(
            __( 'You must be <a href="%s" class="ml-login-form">logged in</a> to post a comment.', 'my-listing' ),
            esc_url( $login_url )
        ) . '</p>';
    }

    return $fields;
} );

add_filter( 'comment_reply_link', function( $link, $args, $comment, $post ) {
    if ( class_exists( 'WooCommerce' ) && get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
        $link = sprintf( '<a rel="nofollow" class="comment-reply-login ml-login-form" href="%s">%s</a>',
            esc_url( add_query_arg(
                'redirect_to',
                urlencode( apply_filters( 'the_permalink', get_permalink( get_the_ID() ), get_the_ID() ) ),
                wc_get_page_permalink('myaccount')
            ) ),
            $args['login_text']
        );
    }

    return $link;
}, 30, 4 );