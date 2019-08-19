<?php

namespace MyListing\Includes;

class App {
	use \MyListing\Src\Traits\Instantiatable;

	private $classes;

	public function __construct() {
		//
	}

	public function register( $name, $instance = null ) {
		if ( is_array( $name ) ) {
			foreach ( $name as $classname => $classinstance ) {
				$this->classes[ $classname ] = $classinstance;
			}
			return;
		}

		$this->classes[ $name ] = $instance;
	}

	public function __call( $method, $params ) {
		if ( isset( $this->classes[ $method ] ) ) {
			return $this->classes[ $method ];
		}

		return null;
	}
}
