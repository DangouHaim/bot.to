<?php
/**
 * The template for WP Admin > Listings > Taxonomies screen.
 *
 * @since 2.1
 */
if ( ! defined('ABSPATH') ) {
	exit;
} ?>

<div class="wrap mylisting-settings-wrap">
	<form class="mylisting-options" method="post" action="options.php">
		<?php
		if ( ! empty( $_GET['settings-updated'] ) ) {
			flush_rewrite_rules();
			echo '<div class="updated"><p>' . esc_html__( 'Settings successfully saved!', 'my-listing' ) . '</p></div>';
		}

		settings_fields( 'mylisting_custom_taxonomies' ); ?>

		<div class="m-form-section">
			<h3 class="m-group-title"><?php _ex( 'Custom Taxonomies', 'WP Admin > Listings > Taxonomies', 'my-listing' ) ?></h3>
			<p class="description"><?php _ex( 'You can manage/add/remove custom listing taxonomies in this page.', 'WP Admin > Listings > Taxonomies', 'my-listing' ) ?></p>
			<?php // @todo: links to docs on using custom taxonomies ?>

			<div id="settings-taxonomies" style="max-width: 550px; margin-top: 30px;">
				<section class="section tabs-content" id="section-result-template">
				    <div class="fields-wrapper">
				        <div class="fields-draggable">
				            <div class="taxonomy-fields" id="c27-custom-taxonomies"></div>

				            <a class="btn btn-outline-dashed" id="c27-add-taxonomy">
				                <?php esc_html_e('Add Taxonomy', 'my-listing'); ?>
				            </a>
				        </div>
				    </div>
				</section>
			</div>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'my-listing' ); ?>" />
			</p>
		</div>
	</form>
</div>

<script type="text/html" id="tmpl-c27-custom-taxonomies">

    <# data.taxonomies.forEach( ( settings, id ) => { #>

    <div class="head-button field" draggable="false" data-taxonomy>
        <h5>
            <span class="prefix">+</span>
            <span data-label>{{{ settings.label }}}</span>
            <span class="actions">
                <span title="<?php esc_attr_e('Delete this button', 'my-listing'); ?>" data-delete-btn><i class="mi delete"></i></span>
            </span>
        </h5>

        <div class="edit">
            <div class="form-group">
                <label><?php esc_html_e('Label', 'my-listing'); ?></label>
                <input name="job_manager_custom_taxonomy[{{{data.count}}}][label]" type="text" class="regular-text" value="{{{ settings.label }}}" data-field-label />
            </div>

            <div class="form-group">
                <label><?php esc_html_e('Taxonomy Slug', 'my-listing'); ?></label>
                <input name="job_manager_custom_taxonomy[{{{data.count}}}][slug]" type="text" class="regular-text" value="{{{ settings.slug }}}" data-field-slug {{{ ! settings.can_edit_slug ? 'readonly' : '' }}}/>
            </div>
        </div>
    </div>

    <# data.count++
    }); #>
</script>