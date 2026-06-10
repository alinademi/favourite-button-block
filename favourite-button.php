<?php
/**
 * Plugin Name:       Favourite Button Block
 * Description:       Logged-in users can favourite posts.
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Ali Nademi
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       favourite-button
 *
 * @package FavBtn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-fav-btn-favorites-repository.php';
require_once __DIR__ . '/includes/class-fav-btn-rest-controller.php';

$fav_btn_repository = new Fav_Btn_Favorites_Repository();
$fav_btn_rest       = new Fav_Btn_Rest_Controller( $fav_btn_repository );

/**
 * Register blocks from the generated manifest.
 */
function fav_btn_init(): void {
	wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
}
add_action( 'init', 'fav_btn_init' );

add_action( 'rest_api_init', array( $fav_btn_rest, 'register_routes' ) );

add_action(
	'delete_user',
	function ( int $user_id ) use ( $fav_btn_repository ): void {
		$fav_btn_repository->handle_user_deletion( $user_id );
	}
);

add_action(
	'before_delete_post',
	function ( int $post_id ) use ( $fav_btn_repository ): void {
		$fav_btn_repository->handle_post_deletion( $post_id );
	}
);
