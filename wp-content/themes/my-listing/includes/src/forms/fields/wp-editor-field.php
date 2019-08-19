<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Wp_Editor_Field extends Base_Field {

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? wp_kses_post( trim( stripslashes( $_POST[ $this->key ] ) ) )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();
		$this->validateMinLength( true );
		$this->validateMaxLength( true );
	}

	public function field_props() {
		$this->props['type'] = 'wp-editor';
		$this->props['editor-controls'] = 'basic';
		$this->props['allow-shortcodes'] = false;
		$this->props['minlength'] = '';
		$this->props['maxlength'] = '';
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getEditorControlsField();
		$this->getAllowShortcodesField();

		$this->getMinLengthField();
		$this->getMaxLengthField();

		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
	}

	protected function getEditorControlsField() { ?>
		<div class="form-group">
			<label>Editor Controls</label>
			<label><input type="radio" v-model="field['editor-controls']" value="basic"> Basic Controls</label>
			<label><input type="radio" v-model="field['editor-controls']" value="advanced"> Advanced Controls</label>
			<label><input type="radio" v-model="field['editor-controls']" value="all"> All Controls</label>
		</div>
	<?php }

	protected function getAllowShortcodesField() { ?>
		<div class="form-group">
			<label></label>
			<label><input type="checkbox" v-model="field['allow-shortcodes']"> Allow shortcodes in the editor?</label>
		</div>
	<?php }
}