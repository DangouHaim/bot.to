<?php

namespace MyListing\Src;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Cache {

	private static $group = 'mylisting_';

	public static function get_version( $key ) {
		$version = get_transient( self::$group . $key . '_version' );
		if ( $version === false ) {
			return self::update_version( $key );
		}

		return $version;
	}

	public static function update_version( $key ) {
		set_transient( self::$group . $key . '_version', $version = time() );
		return $version;
	}
}