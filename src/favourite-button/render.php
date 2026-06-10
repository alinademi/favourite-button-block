<?php
/**
 * Render the cache-safe button shell.
 *
 * The browser loads user state and the current count after the page renders.
 * Keeping them out of this markup makes it safe for full-page caches.
 *
 * @var array<string, mixed> $attributes
 * @var WP_Block $block
 *
 * @package FavBtn
 */

$fav_btn_context_post_id = $block->context['postId'] ?? 0;
$fav_btn_post_id         = $fav_btn_context_post_id ? absint( $fav_btn_context_post_id ) : (int) get_the_ID();

// A block outside the post loop has nothing to favourite.
if ( ! $fav_btn_post_id ) {
	return;
}

$fav_btn_show_count = ! empty( $attributes['showCount'] );
?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	data-post-id="<?php echo esc_attr( $fav_btn_post_id ); ?>"
	data-show-count="<?php echo esc_attr( $fav_btn_show_count ? '1' : '0' ); ?>"
	data-rest-url="<?php echo esc_url( rest_url( 'fav-btn/v1' ) ); ?>"
>
	<button
		type="button"
		class="fav-btn-button"
		data-label="<?php echo esc_attr__( 'Add to favourites', 'favourite-button' ); ?>"
		data-favorited-label="<?php echo esc_attr__( 'Remove from favourites', 'favourite-button' ); ?>"
		data-added-label="<?php echo esc_attr__( 'Added to favourites', 'favourite-button' ); ?>"
		data-removed-label="<?php echo esc_attr__( 'Removed from favourites', 'favourite-button' ); ?>"
		data-error-label="<?php echo esc_attr__( 'Could not update favourites. Try again.', 'favourite-button' ); ?>"
		data-unavailable-label="<?php echo esc_attr__( 'Favourites unavailable.', 'favourite-button' ); ?>"
		data-count-label="<?php echo esc_attr__( 'Favourite count:', 'favourite-button' ); ?>"
		data-pending="true"
		aria-pressed="false"
		disabled
	>
		<span class="fav-btn-label"><?php esc_html_e( 'Add to favourites', 'favourite-button' ); ?></span>
		<span class="fav-btn-count" hidden></span>
	</button>
	<a
		class="fav-btn-login fav-btn-button"
		href="<?php echo esc_url( wp_login_url( (string) get_permalink( $fav_btn_post_id ) ) ); ?>"
		hidden
	>
		<span class="fav-btn-label"><?php esc_html_e( 'Log in to add to favourites', 'favourite-button' ); ?></span>
		<span class="fav-btn-count" hidden></span>
	</a>
	<span class="screen-reader-text fav-btn-status" aria-live="polite"></span>
</div>
