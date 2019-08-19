<?php
/**
 * `Bookmark` quick action.
 *
 * @since 2.0
 */

require_once( get_template_directory() . "/partials/bookmark-counter.php");

$is_bookmarked = mylisting()->bookmarks()->is_bookmarked( $listing->get_id(), get_current_user_id() );
$active_label = ! empty( $action['active_label'] ) ? $action['active_label'] : $action['label'];
?>

<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a
    	href="#"
    	class="mylisting-bookmark-item <?php echo $is_bookmarked ? 'bookmarked' : '' ?>"
    	data-listing-id="<?php echo esc_attr( $listing->get_id() ) ?>"
    	data-nonce="<?php echo esc_attr( wp_create_nonce( 'c27_bookmark_nonce' ) ) ?>"
    	data-label="<?php echo esc_attr( $action['label'] ) ?>"
		data-active-label="<?php echo esc_attr( $active_label ) ?>"
		data-count="<? echo esc_attr( get_bookmarks_count($listing->get_id()) ) ?>"
	>
		<span class="center single-post-bookmark-counter"><? echo esc_attr( get_bookmarks_count($listing->get_id()) ) ?></span>
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span class="action-label"><?php echo $is_bookmarked ? $active_label : $action['label'] ?></span>
    </a>
</li>