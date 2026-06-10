<?php
/**
 * Favourite storage and count management.
 *
 * @package FavBtn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps canonical user favourites and derived post counts in sync.
 *
 * Each favourite is stored as its own user-meta row. The post-meta count is
 * updated atomically so concurrent requests cannot overwrite each other.
 */
final class Fav_Btn_Favorites_Repository {

	public const USER_META_KEY  = 'fav_btn_favourite';
	public const COUNT_META_KEY = '_fav_btn_count';

	/**
	 * Check whether a user has favourited a post.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 */
	public function exists( int $user_id, int $post_id ): bool {
		global $wpdb;

		return (bool) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT EXISTS(
					SELECT 1 FROM {$wpdb->usermeta}
					WHERE user_id = %d AND meta_key = %s AND meta_value = %s
				)",
				$user_id,
				self::USER_META_KEY,
				(string) $post_id
			)
		);
	}

	/**
	 * Get the public favourite count.
	 *
	 * @param int $post_id Post ID.
	 */
	public function get_count( int $post_id ): int {
		return max( 0, (int) get_post_meta( $post_id, self::COUNT_META_KEY, true ) );
	}

	/**
	 * Toggle a user's favourite and return the new state.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return array{favorited: bool, count: int}
	 */
	public function toggle( int $user_id, int $post_id ): array {
		$favorited = ! $this->exists( $user_id, $post_id );

		if ( $favorited ) {
			add_user_meta( $user_id, self::USER_META_KEY, $post_id );
			$this->adjust_count( $post_id, 1 );
		} else {
			// Remove every match so an accidental duplicate repairs itself.
			delete_user_meta( $user_id, self::USER_META_KEY, $post_id );
			$this->adjust_count( $post_id, -1 );
		}

		return array(
			'favorited' => $favorited,
			'count'     => $this->get_count( $post_id ),
		);
	}

	/**
	 * Update counts before WordPress deletes a user's metadata.
	 *
	 * @param int $user_id User ID.
	 */
	public function handle_user_deletion( int $user_id ): void {
		$values   = get_user_meta( $user_id, self::USER_META_KEY, false );
		$post_ids = array_map( 'intval', is_array( $values ) ? $values : array() );

		foreach ( array_unique( $post_ids ) as $post_id ) {
			$this->adjust_count( $post_id, -1 );
		}
	}

	/**
	 * Remove favourites for a permanently deleted post.
	 *
	 * @param int $post_id Post ID.
	 */
	public function handle_post_deletion( int $post_id ): void {
		delete_metadata( 'user', 0, self::USER_META_KEY, $post_id, true );
	}

	/**
	 * Apply an atomic count change and clear WordPress's metadata cache.
	 *
	 * @param int $post_id Post ID.
	 * @param int $delta   Amount to add.
	 */
	private function adjust_count( int $post_id, int $delta ): void {
		global $wpdb;

		// The unique flag prevents concurrent requests from creating two rows.
		add_post_meta( $post_id, self::COUNT_META_KEY, 0, true );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				 SET meta_value = GREATEST( 0, CAST( meta_value AS SIGNED ) + %d )
				 WHERE post_id = %d AND meta_key = %s",
				$delta,
				$post_id,
				self::COUNT_META_KEY
			)
		);

		// Direct SQL does not clear WordPress's metadata cache.
		wp_cache_delete( $post_id, 'post_meta' );
	}
}
