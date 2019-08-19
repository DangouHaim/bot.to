<?php

if ( ! defined( 'CASE27_THEME_DIR' ) ) {
	define( 'CASE27_THEME_DIR', get_template_directory() );
}

if ( ! defined( 'CASE27_INTEGRATIONS_DIR' ) ) {
	define( 'CASE27_INTEGRATIONS_DIR', CASE27_THEME_DIR . '/includes/integrations' );
}

if ( ! defined( 'CASE27_ASSETS_DIR' ) ) {
	define( 'CASE27_ASSETS_DIR', CASE27_THEME_DIR . '/assets' );
}

if ( ! defined( 'CASE27_ENV' ) ) {
	define( 'CASE27_ENV', 'production' );
}

if ( ! defined( 'PT_OCDI_PATH' ) ) {
	define( 'PT_OCDI_PATH', trailingslashit( CASE27_THEME_DIR ) . 'includes/extensions/demo-import/plugin/' );
}

if ( ! defined( 'PT_OCDI_URL' ) ) {
	define( 'PT_OCDI_URL', trailingslashit( get_template_directory_uri() ) . 'includes/extensions/demo-import/plugin/' );
}

if ( ! defined( 'CASE27_THEME_VERSION' ) ) {
	if (CASE27_ENV == 'dev') {
		define( 'CASE27_THEME_VERSION', rand(1, 10e3) );
	} else {
		define( 'CASE27_THEME_VERSION', wp_get_theme( get_template() )->get('Version') );
	}
}

if ( ! defined( 'ELEMENTOR_PARTNER_ID' ) ) {
	define( 'ELEMENTOR_PARTNER_ID', 2124 );
}

// Load textdomain early to include strings that are localized before
// the 'after_setup_theme' is called.
load_theme_textdomain( 'my-listing', CASE27_THEME_DIR . '/languages' );

// Load classes.
require_once CASE27_THEME_DIR . '/includes/autoload.php';

function getVideoSection($url) {
	if(strpos(strtolower($url), "youtube.com") !== false
	|| strpos(strtolower($url), "youtu.be") !== false) {
		return convertYoutube($url);
	}
	return convertMp4($url);
}

function convertMp4($string) {
    return '<video style="background: #000;" width="320" height="240" controls="">
				<source src="' . $string . '" type="video/mp4">
				Your browser does not support the video.
			</video>';
}

function convertYoutube($string) {
    return "<iframe style='border: none;' width='320' height='240' src='" . getYoutubeEmbedUrl($string) ."' allowfullscreen></iframe>";
}

function getYoutubeEmbedUrl($url)
{
    $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
	$longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

    if (preg_match($longUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }

    if (preg_match($shortUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }
    return 'https://www.youtube.com/embed/' . $youtube_id ;
}

function getGrid($query) {

	$atts = $_POST["atts"];

	$data = Array();
	$data = c27()->merge_options( [
		'template' => 'grid',
		'posts_per_page' => 6,
		'category' => '',
		'tag' => '',
		'region' => '',
		'include' => '',
		'listing_types' => '',
		'is_edit_mode' => false,
		'columns' => ['lg' => 3, 'md' => 3, 'sm' => 2, 'xs' => 1],
		'order_by' => 'date',
		'order' => 'DESC',
		'order_by_priority' => true,
		'priority_levels' => [],
		'show_promoted_badge' => 'yes',
		'query_method' => 'filters',
		'query_string' => $query,
	], $data );

	$data["columns"] = ['lg' => $atts["lg"], 'md' => $atts["md"], 
	'sm' => $atts["sm"], 'xs' => $atts["xs"]];

	$data["posts_per_page"] = $atts["perpage"];

	if ( ! ( $query_string = parse_url( $data['query_string'], PHP_URL_QUERY ) ) ) {
		return;
	}
	
	if ( ! ( $query_args = wp_parse_args( $query_string ) ) ) {
		return;
	}
	
	if ( ! empty( $query_args['pg'] ) ) {
		$query_args['page'] = max( 0, absint( $query_args['pg'] ) - 1 );
	}

	$aliases = array_merge(
		\MyListing\Src\Listing::$aliases,
		[
			'date_from' => 'job_date_from',
			'date_to' => 'job_date_to',
			'lat' => 'search_location_lat',
			'lng' => 'search_location_lng',
		]
	);

	foreach ( $query_args as $key => $query_arg ) {
		if ( ! empty( $aliases[ $key ] ) ) {
			$query_args[ $aliases[ $key ] ] = $query_arg;
			unset( $query_args[ $key ] );
		}
	}

	$listings_query = \MyListing\Src\Queries\Explore_Listings::instance()->run( [
		'listing_type' => ! empty( $query_args['type'] ) ? $query_args['type'] : false,
		'form_data' => c27()->merge_options( [
			'per_page' => $data['posts_per_page'],
		], (array) $query_args ),
		'return_query' => true,
	] );

	if ( ! $listings_query instanceof \WP_Query ) {
		return false;
	}

	$_POST["data"] = $data;

	return $listings_query;
}

function renderGridView($atts) {
	
	$_POST["atts"] = $atts;
	
	$listings = getGrid('?type=' . $atts["type"] . '&pg=' . $atts["page"])->posts;
	
	$_POST["listings"] = $listings;

	ob_start();
	get_template_part( "gridView" );
	return ob_get_clean();
}

add_shortcode( 'gridview', 'renderGridView' );

add_action( 'wp_ajax_renderGridViewAjax',  'renderGridViewAjax' );
add_action( 'wp_ajax_nopriv_renderGridViewAjax','renderGridViewAjax');

if( ! function_exists( 'renderGridViewAjax' ) ){
  function renderGridViewAjax(){
    
    if(isset($_POST["query"])) {
		$_POST["isajax"] = true;
		
		$listings = getGrid($_POST["query"])->posts;
		$_POST["listings"] = $listings;

		ob_start();
        get_template_part( "gridView" );
		
		wp_send_json( ob_get_clean() );
    }

    die();
  }
}

add_action( 'wp_ajax_saveBotLinksAjax',  'SaveBotLinksAjax' );
add_action( 'wp_ajax_nopriv_saveBotLinksAjax','SaveBotLinksAjax');

if( ! function_exists( 'SaveBotLinksAjax' ) ){
  function SaveBotLinksAjax(){
	if(isset($_POST["botLinks"])) {
		set_transient( 'botLinks', $_POST["botLinks"], 60 * 60 );
	} else {
		set_transient( 'botLinks', "", 60 * 60 );
	}
    die();
  }
}

add_action( 'wp_ajax_getBotLinksAjax',  'GetBotLinksAjax' );
add_action( 'wp_ajax_nopriv_getBotLinksAjax','GetBotLinksAjax');

if( ! function_exists( 'GetBotLinksAjax' ) ){
  function GetBotLinksAjax(){
	if($_POST["job_id"] && get_post_meta( $_POST["job_id"], "botLinks", true) ) {

		wp_send_json( get_post_meta($_POST["job_id"], "botLinks", true) );

	} else {

		wp_send_json( get_transient( 'botLinks' ) );

	}
	die();
  }
}

function GetIconByTerm($term) {
	$icon = $term->description;
	if(empty($icon)) {
		$icon = "fas fa-robot";
	}
	return $icon;
}