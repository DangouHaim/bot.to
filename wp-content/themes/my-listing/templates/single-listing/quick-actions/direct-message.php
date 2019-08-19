<?php
/**
 * `Direct message` quick action.
 *
 * @since 2.1
 */
if ( c27()->get_setting( 'messages_enabled', true ) === false ) {
	return;
}
?>
<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="#" class="cts-open-chat" data-user-id="<?php echo absint( $listing->get_author_id() ) ?>">
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>