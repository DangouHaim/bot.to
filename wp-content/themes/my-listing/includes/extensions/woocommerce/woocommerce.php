<?php

namespace MyListing\Ext\WooCommerce;

if ( ! defined('ABSPATH') ) {
	exit;
}

class WooCommerce {
	use \MyListing\Src\Traits\Instantiatable;

	public $endpoints;

	public function __construct() {
		if ( ! class_exists( '\WooCommerce' ) ) {
			return;
		}

		$this->endpoints = Endpoints::instance();
		do_action( 'mylisting/dashboard/endpoints-init', $this->endpoints );

		$this->templates = Templates::instance();
		$this->shop = Shop::instance();
		require_once locate_template( 'includes/extensions/woocommerce/general.php' );

		// Init request handlers.
		Requests\Get_Products::instance();

        // WooCommerce scripts.
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 30 );

        // persist login notices to the redirected page
        add_action( 'woocommerce_login_failed', [ $this, 'persist_login_notices' ] );
        add_filter( 'woocommerce_show_page_title', '__return_false' );
	}

	// Wrapper.
	public function add_dashboard_page( $page ) {
		if ( ! $this->endpoints ) {
			return false;
		}

		$this->endpoints->add_page( $page );
	}

    /**
     * Register/deregister WooCommerce scripts.
     *
     * @since 1.7.0
     */
    public function enqueue_scripts() {
        if ( ! is_user_logged_in() ) {
            wp_enqueue_script( 'wc-password-strength-meter' );
        }

        if ( is_account_page() ) {
            // Include charting library.
            wp_enqueue_script( 'chartist', c27()->template_uri( 'assets/vendor/chartist/chartist.js' ), [], CASE27_THEME_VERSION, true );
            wp_enqueue_style( 'chartist', c27()->template_uri( 'assets/vendor/chartist/chartist.css' ), [], CASE27_THEME_VERSION );

            // Dashboard scripts and styles.
            wp_enqueue_style( 'mylisting-dashboard' );
            wp_enqueue_script( 'mylisting-dashboard' );
        }
    }

	// Wrapper.
	public function wrap_page_in_block( $page ) {
		$this->templates->wrap_page_in_block( $page );
	}

	/**
	 * Persist the login notices to the redirect page.
	 *
	 * @since 1.0
	 */
	public function persist_login_notices() {
		if ( ! empty( $_POST['redirect'] ) ) {
    		if ( ! WC()->session->has_session() ) {
    			WC()->session->set_customer_session_cookie( true );
    		}

    		wc_set_notices( wc_get_notices() );
    		wp_redirect( $_POST['redirect'] );
    		exit;
    	}
	}
}
