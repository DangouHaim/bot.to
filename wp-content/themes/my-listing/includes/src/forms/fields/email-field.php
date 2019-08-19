<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Email_Field extends Base_Field {

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? sanitize_email( $_POST[ $this->key ] )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();
		if ( ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
			// translators: Placeholder %s is the label for the required field.
			throw new \Exception( sprintf( _x( '%s must be a valid email address.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
		}
	}

	public function field_props() {
		$this->props['type'] = 'email';
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