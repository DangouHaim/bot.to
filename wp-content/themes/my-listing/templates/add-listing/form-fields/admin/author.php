<?php

global $post;

// get current author
$selected = ( $post instanceof \WP_Post ) ? get_user_by( 'id', $post->post_author ) : false;
?>
<select
	name="job_author" id="job_author" class="custom-select" data-mylisting-ajax="true"
	data-mylisting-ajax-url="mylisting_list_users" placeholder="<?php echo _x( 'Select author', 'Author field', 'my-listing' ) ?>"
>
	<?php if ( $selected instanceof \WP_User ): ?>
		<option value="<?php echo esc_attr( $selected->ID ) ?>" selected="selected"><?php echo esc_attr( $selected->display_name ) ?></option>
	<?php endif ?>
</select>
