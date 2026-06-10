<?php
/**
 * Remove plugin data when WordPress uninstalls the plugin.
 *
 * @package FavBtn
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove every favourite row across all users.
delete_metadata( 'user', 0, 'fav_btn_favourite', '', true );

// Remove every stored post count.
delete_post_meta_by_key( '_fav_btn_count' );
