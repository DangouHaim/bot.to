<?php
/**
 * `Bookmark Counter` quick action.
 *
 * @since 2.0
 */

function get_bookmarks_count($postId) {
	global $wpdb;
	$query = "SELECT COUNT(umeta_id) FROM wp_usermeta WHERE meta_key = '_case27_user_bookmarks' AND meta_value LIKE '%i:" . $postId . ";%'";
	return $wpdb->get_var($query);
}
?>