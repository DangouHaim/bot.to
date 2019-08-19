<?php

namespace MyListing\Src\Endpoints;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Endpoints {

	/**
	 * Register endpoints.
	 *
	 * @since 2.1
	 */
	public static function init() {
		new File_Upload_Endpoint;
		new Quick_View_Endpoint;
		new Posts_List_Endpoint;
		new Products_List_Endpoint;
		new Users_List_Endpoint;
		new Explore_Terms_Endpoint;
		Term_List_Endpoint::instance();
	}

}