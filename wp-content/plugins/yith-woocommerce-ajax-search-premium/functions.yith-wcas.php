<?php
/**
 * Functions
 *
 * @author YITH
 * @package YITH WooCommerce Ajax Search Premium
 * @version 1.2
 */

if ( !defined( 'YITH_WCAS' ) ) { exit; } // Exit if accessed directly

if ( !function_exists( 'yit_get_shop_categories' ) ) {
	function yith_wcas_get_shop_categories( $show_all = true ) {

		$args = apply_filters( 'yith_wcas_form_cat_args', array(
			'hide_empty' => 1,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => 1
		) );

		if ( ! $show_all ) {
			$args = array_merge( $args, array( 'parent' => 0, 'hierarchical' => 0 ) );
		}

		$terms = get_terms( 'product_cat', apply_filters( 'yith_wcas_form_cat_args', $args ) );

		return $terms;
	}
}

function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}