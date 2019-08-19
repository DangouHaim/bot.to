<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Related_Listing_Field extends Base_Field {

	public function get_posted_value() {
		return isset( $_POST[ $this->key ] )
			? sanitize_text_field( stripslashes( $_POST[ $this->key ] ) )
			: '';
	}

	public function validate() {
		$value = $this->get_posted_value();
		//
	}

	public function field_props() {
		$this->props['type'] = 'related-listing';
		$this->props['listing_type'] = '';
	}

	public function get_editor_options() {
		$this->getLabelField();
		$this->getKeyField();
		$this->getPlaceholderField();
		$this->getDescriptionField();
		$this->getRelationTypeField();
		$this->getRequiredField();
		$this->getShowInSubmitFormField();
		$this->getShowInAdminField();
	}

	protected function getRelationTypeField() { ?>
		<div class="form-group full-width">
			<label>Related Listing Type</label>
			<div class="select-wrapper">
				<select v-model="field.listing_type">
					<?php foreach ( \MyListing\Ext\Listing_Types\Editor::$store['listing-types'] as $listing_type ): ?>
						<option value="<?php echo $listing_type->post_name ?>"><?php echo $listing_type->post_title ?></option>
					<?php endforeach ?>
				</select>
			</div>
		</div>
	<?php }
}