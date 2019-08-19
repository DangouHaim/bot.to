<?php

namespace MyListing\Ext\Listing_Relations;

class Listing_Relations {
	use \MyListing\Src\Traits\Instantiatable;

	private $table_version, $current_version;

	public function __construct() {
		/**
		 * @todo {
		 *     add content block through filter
		 *     add field preset through filter
		 *     single pages > related_listings tab settings
		 *     move ajax endpoint handlet from src/queries to extensions/listing-relations
		 *     preview card footer section
		 *     single listing host block
		 * }
		 */

		$this->table_version = '0.21';
		$this->current_version = get_option( 'mylisting_relations_table_version' );
		$this->setup_tables();
	}

	public function setup_tables() {
		if ( $this->table_version === $this->current_version ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'mylisting_relations';
		$sql = "CREATE TABLE $table_name (
			parent_listing_id bigint(20) unsigned NOT NULL,
			child_listing_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (parent_listing_id, child_listing_id),
			FOREIGN KEY (parent_listing_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE,
			FOREIGN KEY (child_listing_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE
		);";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'mylisting_relations_table_version', $this->table_version );
	}
}