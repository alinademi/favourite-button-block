<?php
/**
 * Favourite Button integration tests.
 */

class Fav_Btn_Integration_Test extends WP_UnitTestCase {

	private Fav_Btn_Favorites_Repository $repository;

	public function set_up(): void {
		parent::set_up();
		$this->repository = new Fav_Btn_Favorites_Repository();
		do_action( 'rest_api_init' );
	}

	public function tear_down(): void {
		wp_set_current_user( 0 );
		unset( $_SERVER['HTTP_ORIGIN'] );
		parent::tear_down();
	}

	public function test_toggle_adds_and_removes_user_meta_and_updates_count(): void {
		$user_id = self::factory()->user->create();
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		$added = $this->repository->toggle( $user_id, $post_id );

		$this->assertSame(
			[
				'favorited' => true,
				'count'     => 1,
			],
			$added
		);
		$this->assertTrue( $this->repository->exists( $user_id, $post_id ) );
		$this->assertContains( (string) $post_id, get_user_meta( $user_id, Fav_Btn_Favorites_Repository::USER_META_KEY, false ), true );

		$removed = $this->repository->toggle( $user_id, $post_id );

		$this->assertSame(
			[
				'favorited' => false,
				'count'     => 0,
			],
			$removed
		);
		$this->assertFalse( $this->repository->exists( $user_id, $post_id ) );
		$this->assertSame( array(), get_user_meta( $user_id, Fav_Btn_Favorites_Repository::USER_META_KEY, false ) );
	}

	public function test_repeated_removals_cannot_make_count_negative(): void {
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$user_a  = self::factory()->user->create();
		$user_b  = self::factory()->user->create();

		$this->repository->toggle( $user_a, $post_id );
		$this->repository->toggle( $user_b, $post_id );
		$this->assertSame( 2, $this->repository->get_count( $post_id ) );

		$this->repository->handle_user_deletion( $user_a );
		$this->repository->handle_user_deletion( $user_a );
		$this->repository->handle_user_deletion( $user_b );
		$this->repository->handle_user_deletion( $user_b );

		$this->assertSame( 0, $this->repository->get_count( $post_id ) );
	}

	public function test_user_deletion_decrements_each_favourited_post(): void {
		$user_id = self::factory()->user->create();
		$post_a  = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$post_b  = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		$this->repository->toggle( $user_id, $post_a );
		$this->repository->toggle( $user_id, $post_b );
		wp_delete_user( $user_id );

		$this->assertSame( 0, $this->repository->get_count( $post_a ) );
		$this->assertSame( 0, $this->repository->get_count( $post_b ) );
	}

	public function test_post_deletion_removes_all_user_favourites(): void {
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$user_a  = self::factory()->user->create();
		$user_b  = self::factory()->user->create();

		$this->repository->toggle( $user_a, $post_id );
		$this->repository->toggle( $user_b, $post_id );
		wp_delete_post( $post_id, true );

		$this->assertFalse( $this->repository->exists( $user_a, $post_id ) );
		$this->assertFalse( $this->repository->exists( $user_b, $post_id ) );
	}

	public function test_rest_rejects_unpublished_posts_and_anonymous_toggle(): void {
		$draft_id = self::factory()->post->create( array( 'post_status' => 'draft' ) );

		$state = new WP_REST_Request( 'GET', '/fav-btn/v1/state' );
		$state->set_param( 'post_id', $draft_id );
		$this->assertSame( 400, rest_get_server()->dispatch( $state )->get_status() );

		$published_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$toggle       = new WP_REST_Request( 'POST', '/fav-btn/v1/toggle' );
		$toggle->set_param( 'post_id', $published_id );
		$this->assertSame( 401, rest_get_server()->dispatch( $toggle )->get_status() );
	}

	public function test_state_returns_public_and_authenticated_shapes(): void {
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$user_id = self::factory()->user->create();
		$request = new WP_REST_Request( 'GET', '/fav-btn/v1/state' );
		$request->set_param( 'post_id', $post_id );

		$public = rest_get_server()->dispatch( $request );
		$this->assertSame(
			[
				'loggedIn'  => false,
				'favorited' => false,
				'count'     => 0,
				'nonce'     => null,
			],
			$public->get_data()
		);
		$this->assertStringContainsString( 'no-store', $public->get_headers()['Cache-Control'] );

		$this->repository->toggle( $user_id, $post_id );
		wp_set_current_user( $user_id );
		$authenticated = rest_get_server()->dispatch( $request )->get_data();

		$this->assertTrue( $authenticated['loggedIn'] );
		$this->assertTrue( $authenticated['favorited'] );
		$this->assertSame( 1, $authenticated['count'] );
		$this->assertIsString( $authenticated['nonce'] );
		$this->assertNotSame( '', $authenticated['nonce'] );
	}

	public function test_rendered_shell_contains_no_user_state_or_count(): void {
		$post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$user_id = self::factory()->user->create();
		$this->repository->toggle( $user_id, $post_id );
		wp_set_current_user( $user_id );

		if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'fav-btn/favourite-button' ) ) {
			fav_btn_init();
		}

		$block = new WP_Block(
			array(
				'blockName' => 'fav-btn/favourite-button',
				'attrs'     => array( 'showCount' => true ),
				'innerHTML' => '',
			),
			array( 'postId' => $post_id )
		);
		$html  = $block->render();

		$this->assertStringContainsString( 'data-pending="true"', $html );
		$this->assertStringContainsString( 'disabled', $html );
		$this->assertStringNotContainsString( wp_create_nonce( 'wp_rest' ), $html );
		$this->assertStringNotContainsString( '>1<', $html );
		$this->assertStringNotContainsString( 'aria-pressed="true"', $html );
	}
}
