<?php

namespace MyListing\Src\Forms\Fields;

if ( ! defined('ABSPATH') ) {
	exit;
}

abstract class Base_Field implements \JsonSerializable {

	public
		$key,
		$listing,
		$listing_type,
		$props = [
			'type'                => 'text',
			'slug'                => 'custom-field',
			'default'             => '',
			'reusable'            => true,
			'priority'            => 10,
			'is_custom'           => true,
			'label'               => 'Custom Field',
			'default_label'       => 'Custom Field',
			'placeholder'         => '',
			'description'         => '',
			'required'            => false,
			'show_in_admin'       => true,
			'show_in_submit_form' => true,
			'conditional_logic' => false,
			'conditions' => [ [ [ 'key' => '__listing_package', 'compare' => '==', 'value' => '', ] ] ],
		];

	public function __construct( $props = [], $listing = null, $listing_type = null ) {
		$this->field_props();
		$this->listing = $listing;
		$this->listing_type = $listing_type;

		// override props
		$this->set_props( $props );
		$this->key = $this->props['slug'];
	}

	// get and sanitize the posted field value in add listing form
	abstract public function get_posted_value();

	// validate after submission in add listing form
	abstract public function validate();

	// update listing with the field value
	public function update() {
		$value = $this->get_posted_value();
		update_post_meta( $this->listing->get_id(), '_'.$this->key, $value );
	}

	// set field props
	abstract public function field_props();

	// render field settings in the listing type editor
	abstract public function get_editor_options();

	final public function print_editor_options() {
		ob_start(); ?>
		<div class="field-settings-wrapper" v-if="field.type == '<?php echo esc_attr( $this->props['type'] ) ?>'">
			<?php $this->get_editor_options(); ?>
			<?php $this->get_visibility_settings() ?>
		</div>
		<?php return ob_get_clean();
	}

	/**
	 * When an object of this type is serialized, simply output it's props.
	 *
	 * @since 1.0
	 */
	public function jsonSerialize() {
		return $this->props;
	}

	/**
	 * Validate common rules among all field types,
	 * then run the unique validations for each field.
	 *
	 * @since 2.1
	 */
	public function check_validity() {
		$value = $this->get_posted_value();

		// required field check
		if ( $this->props['required'] && empty( $value ) ) {
			// translators: Placeholder %s is the label for the required field.
			throw new \Exception( sprintf( _x( '%s is a required field.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
		}

		// if field isn't required, then no validation is needed for empty values
		if ( empty( $value ) ) {
			return;
		}

		// otherwise, run validations
		$this->validate();
	}

	/**
	 * Common validation rule among field with an option list,
	 * e.g. select, multiselect, checkbox, and radio fields.
	 *
	 * @since 2.1
	 */
	public function validateSelectedOption() {
		$value = $this->get_posted_value();
		$has_options = is_array( $this->props['options'] ) && ! empty( $this->props['options'] );

		foreach ( (array) $value as $option ) {
			if ( $has_options && ! in_array( $option, array_keys( $this->props['options'] ) ) ) {
				// translators: %s is the field label.
				throw new \Exception( sprintf( _x( 'Invalid value supplied for %s.', 'Add listing form', 'my-listing' ), $this->props['label'] ) );
			}
		}
	}

	public function validateMinLength( $strip_tags = false ) {
		$value = $this->get_posted_value();
		if ( $strip_tags ) {
			$value = wp_strip_all_tags( $value );
		}

		if ( is_numeric( $this->props['minlength'] ) && mb_strlen( $value ) < $this->props['minlength'] ) {
			// translators: %1$s is the field label; %2%s is the minimum characters allowed.
			throw new \Exception( sprintf(
				_x( '%1$s can\'t be shorter than %2$s characters.', 'Add listing form', 'my-listing' ),
				$this->props['label'],
				absint( $this->props['minlength'] )
			) );
		}
	}

	public function validateMaxLength( $strip_tags = false ) {
		$value = $this->get_posted_value();
		if ( $strip_tags ) {
			$value = wp_strip_all_tags( $value );
		}

		if ( is_numeric( $this->props['maxlength'] ) && mb_strlen( $value ) > $this->props['maxlength'] ) {
			// translators: %1$s is the field label; %2%s is the maximum characters allowed.
			throw new \Exception( sprintf(
				_x( '%1$s can\'t be longer than %2$s characters.', 'Add listing form', 'my-listing' ),
				$this->props['label'],
				absint( $this->props['maxlength'] )
			) );
		}
	}

	/**
	 * Merge custom props to the $props array.
	 *
	 * @since 2.1
	 */
	public function set_props( $props ) {
		foreach ( $props as $key => $value ) {
			if ( isset( $this->props[ $key ] ) ) {
				$this->props[ $key ] = $value;
			}
		}
	}

	/**
	 * Set the listing for this field if available.
	 *
	 * @since 2.1
	 */
	public function set_listing( $listing ) {
		$this->listing = $listing;
	}

	/**
	 * Set the listing type for this field if available.
	 *
	 * @since 2.1
	 */
	public function set_listing_type( $listing_type ) {
		$this->listing_type = $listing_type;
	}

	/**
	 * Renders the label setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getLabelField() { ?>
		<div class="form-group">
			<label>Label</label>
			<input type="text" v-model="field.label" @input="fieldsTab().setKey(field, field.label)">

			<?php c27()->get_partial('admin/input-language', ['object' => 'field.label_l10n']) ?>
		</div>
	<?php }

	/**
	 * Renders the "field key" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getKeyField() { ?>
		<div class="form-group" v-if="field.is_custom">
			<label>Key</label>
			<input type="text" v-model="field.slug" @input="fieldsTab().setKey(field, field.slug)">
		</div>
	<?php }

	/**
	 * Renders the placeholder setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getPlaceholderField() { ?>
		<div class="form-group">
			<label>Placeholder</label>
			<input type="text" v-model="field.placeholder">

			<?php c27()->get_partial('admin/input-language', ['object' => 'field.placeholder_l10n']) ?>
		</div>
	<?php }

	/**
	 * Renders the description setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getDescriptionField() { ?>
		<div class="form-group">
			<label>Description</label>
			<input type="text" v-model="field.description">

			<?php c27()->get_partial('admin/input-language', ['object' => 'field.description_l10n']) ?>
		</div>
	<?php }

	/**
	 * Renders an icon picker setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getIconField() { ?>
		<div class="form-group">
			<label>Icon</label>
			<iconpicker v-model="field.icon"></iconpicker>
		</div>
	<?php }

	/**
	 * Renders a "is required?" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getRequiredField() { ?>
		<div class="form-group full-width">
			<label><input type="checkbox" v-model="field.required" :disabled="field.slug == 'job_title'"> Required field</label>
		</div>
	<?php }

	/**
	 * Renders a "is multiple?" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getMultipleField() { ?>
		<div class="form-group full-width">
			<label><input type="checkbox" v-model="field.multiple"> Multiple?</label>
		</div>
	<?php }

	/**
	 * Renders a "show in submit" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getShowInSubmitFormField() { ?>
		<div class="form-group full-width">
			<label><input type="checkbox" v-model="field.show_in_submit_form" :disabled="field.slug == 'job_title'"> Show in submit form</label>
		</div>
	<?php }

	/**
	 * Renders a "show in admin" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getShowInAdminField() { ?>
		<div class="form-group full-width">
			<label><input type="checkbox" v-model="field.show_in_admin" :disabled="field.slug == 'job_title'"> Show in admin edit page</label>
		</div>
	<?php }

	/**
	 * Renders "options" setting for fields that support it in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getOptionsField() { ?>
		<div class="form-group full-width options-field field-options-input">
			<hr>
			<label>Options</label>

			<div class="options-list" v-show="!state.fields.editingOptions">
				<div class="form-group" v-for="(value, key, index) in field.options">
					<input type="text" v-model="field.options[key]" disabled="disabled">
				</div>
			</div>

			<div v-show="!state.fields.editingOptions && !Object.keys(field.options).length">
				<small><em>No options added yet.</em></small>
			</div>

			<textarea
				id="custom_field_options"
				v-show="state.fields.editingOptions"
				placeholder="Add each option in a new line."
				@keyup="fieldsTab().editFieldOptions($event, field)"
				cols="50" rows="7"
				>{{ Object.keys(field.options).map(function(el) { return el === field.options[el] ? field.options[el] : el + ' : ' + field.options[el]; }).join('\n') }}</textarea>
			<small v-show="state.fields.editingOptions"><em>Put each option in a new line. You can specify both a value and label like this: <code>red : Red</code></em></small>
			<br><br v-show="state.fields.editingOptions || Object.keys(field.options).length">
			<button @click.prevent="state.fields.editingOptions = !state.fields.editingOptions;" class="btn btn-primary">{{ state.fields.editingOptions ? 'Save Options' : 'Add/Edit Options' }}</button>
		</div>
	<?php }

	/**
	 * Renders a "allowed product types" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	public function getAllowedProductTypesField() { ?>
		<div class="form-group">
			<label>Allowed product types</label>
			<select multiple="multiple" v-model="field['product-type']">
				<?php foreach ( \MyListing\Ext\Listing_Types\Editor::$store['product-types'] as $type => $label ): ?>
					<option value="<?php echo esc_attr( $type ) ?>"><?php echo $label ?></option>
				<?php endforeach ?>
			</select>
			<p class="form-description">Leave empty for all</p>
		</div>
	<?php }

	/**
	 * Renders a "format" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getFormatField() { ?>
		<div class="form-group full-width">
			<label>Format</label>
			<div class="select-wrapper">
				<select v-model="field.format">
					<option value="date">Date</option>
					<option value="datetime">Date + Time</option>
				</select>
			</div>
		</div>
	<?php }

	/**
	 * Renders a "minimum value" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getMinField() { ?>
		<div class="form-group">
			<label>Minimum value</label>
			<input type="number" v-model="field.min" step="any">
		</div>
	<?php }

	/**
	 * Renders a "maximum value" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getMaxField() { ?>
		<div class="form-group">
			<label>Maximum value</label>
			<input type="number" v-model="field.max" step="any">
		</div>
	<?php }

	/**
	 * Renders a "minlength" setting in the field settings in the listing type editor.
	 *
	 * @since 2.1
	 */
	protected function getMinLengthField() { ?>
		<div class="form-group">
			<label>Min length (characters)</label>
			<input type="number" v-model="field.minlength">
		</div>
	<?php }

	/**
	 * Renders a "maxlength" setting in the field settings in the listing type editor.
	 *
	 * @since 2.1
	 */
	protected function getMaxLengthField() { ?>
		<div class="form-group">
			<label>Max length (characters)</label>
			<input type="number" v-model="field.maxlength">
		</div>
	<?php }

	/**
	 * Renders a "step size" setting in the field settings in the listing type editor.
	 *
	 * @since 1.0
	 */
	protected function getStepField() { ?>
		<div class="form-group">
			<label>Step size</label>
			<input type="number" v-model="field.step" step="any">
		</div>
	<?php }

	/**
	 * Renders field visibility settings in the listing type editor.
	 *
	 * @since 1.5
	 */
	protected function get_visibility_settings() { ?>
		<div class="field-visibility" v-show="field.slug != 'job_title'">
			<div class="form-group full-width">
				<label><input type="checkbox" v-model="field.conditional_logic"> Enable package visibility</label>
			</div>

			<div class="visibility-rules" v-show="field.conditional_logic">
				<label>Show this field if</label>
				<p></p>
				<div class="conditions">
					<div class="condition-group" v-for="conditionGroup, groupKey in field.conditions" v-if="conditionGroup.length">
						<div class="or-divider">
							or
						</div>
						<div class="condition" v-for="condition in conditionGroup">
							<div class="form-group">
								<select v-model="condition.key">
									<option value="__listing_package">Listing Package</option>
								</select>
							</div>

							<div class="form-group">
								<!-- <div class="select-wrapper"> -->
									<select v-model="condition.compare">
										<option value="==">is equal to</option>
										<!-- <option value="!=">is not equal to</option> -->
									</select>
								<!-- </div> -->
							</div>

							<div class="form-group">
								<div class="select-wrapper">
									<select v-model="condition.value">
										<option value="--none--">No Package</option>
										<option v-for="package in settings.packages.used" :value="package.package">{{ packages().getPackageTitle(package) }}</option>
									</select>
								</div>
							</div>

							<span class="actions" @click="conditions().deleteConditionGroup(conditionGroup, field)">
								<span><i class="mi close"></i></span>
							</span>
						</div>
					</div>

					<button class="btn btn-primary" @click.prevent="conditions().addOrCondition(field)">Add Rule</button>
					<!-- <pre>{{ field.conditions }}</pre> -->
				</div>
			</div>
		</div>
	<?php }
}