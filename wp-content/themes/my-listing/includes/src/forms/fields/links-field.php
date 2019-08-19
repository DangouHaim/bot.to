<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Links_Field extends Base_Field {

	public function get_posted_value() {
		$value = ! empty( $_POST[ $this->key ] ) ? (array) $_POST[ $this->key ] : [];
		$links = array_map( function( $val ) {
			if ( ! is_array( $val ) || empty( $val['network'] ) || empty( $val['url'] ) ) {
				return false;
			}

			return [
				'network' => sanitize_text_field( stripslashes( $val['network'] ) ),
				'url' => esc_url_raw( $val['url'] ),
			];
		}, $value );

		return array_filter( $links );
	}

	public function validate() {
		$value = $this->get_posted_value();
		//
	}

	public function field_props() {
		$this->props['type'] = 'links';
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
	}

}