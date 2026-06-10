<?php
/**
 * Favourite button REST endpoints.
 *
 * @package FavBtn
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Serves public counts and authenticated favourite toggles.
 */
final class Fav_Btn_Rest_Controller {

	public const REST_NAMESPACE = 'fav-btn/v1';

	/**
	 * Favourite storage.
	 *
	 * @var Fav_Btn_Favorites_Repository
	 */
	private Fav_Btn_Favorites_Repository $repository;

	/**
	 * Set the favourite storage dependency.
	 *
	 * @param Fav_Btn_Favorites_Repository $repository Favourite storage.
	 */
	public function __construct( Fav_Btn_Favorites_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register the state and toggle routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/state',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_state' ),
				'permission_callback' => '__return_true',
				'args'                => $this->post_id_args(),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/toggle',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle' ),
				// Core validates the REST nonce before checking this callback.
				'permission_callback' => 'is_user_logged_in',
				'args'                => $this->post_id_args(),
			)
		);
	}

	/**
	 * Build the shared post ID argument schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function post_id_args(): array {
		return array(
			'post_id' => array(
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( $this, 'validate_post_id' ),
			),
		);
	}

	/**
	 * Allow only published posts.
	 *
	 * REST validation callbacks may receive scalar values before sanitization.
	 *
	 * @param mixed $value Requested post ID.
	 */
	public function validate_post_id( $value ): bool {
		$post = get_post( absint( $value ) );
		return $post instanceof WP_Post && 'publish' === $post->post_status;
	}

	/**
	 * Return the public count and, for same-origin users, personal state.
	 *
	 * @param WP_REST_Request $request REST request.
	 */
	public function get_state( WP_REST_Request $request ): WP_REST_Response {
		$post_id   = (int) $request['post_id'];
		$user_id   = $this->is_same_origin_request() ? $this->resolve_user_id() : 0;
		$logged_in = $user_id > 0;

		return $this->no_store(
			array(
				'loggedIn'  => $logged_in,
				'favorited' => $logged_in && $this->repository->exists( $user_id, $post_id ),
				'count'     => $this->repository->get_count( $post_id ),
				'nonce'     => $logged_in ? wp_create_nonce( 'wp_rest' ) : null,
			)
		);
	}

	/**
	 * Recover the user from the login cookie for the nonce bootstrap request.
	 */
	private function resolve_user_id(): int {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			$user_id = (int) wp_validate_auth_cookie( '', 'logged_in' );
			if ( $user_id ) {
				wp_set_current_user( $user_id );
			}
		}

		return $user_id;
	}

	/**
	 * Check whether the request origin matches the REST API origin.
	 *
	 * Requests without an Origin header are valid same-origin or server
	 * requests. Cross-origin requests still receive public anonymous state.
	 */
	private function is_same_origin_request(): bool {
		$origin = get_http_origin();

		if ( ! $origin ) {
			return true;
		}

		$origin_parts = wp_parse_url( $origin );
		$rest_parts   = wp_parse_url( rest_url() );

		if (
			! $origin_parts ||
			! $rest_parts ||
			empty( $origin_parts['scheme'] ) ||
			empty( $origin_parts['host'] ) ||
			empty( $rest_parts['scheme'] ) ||
			empty( $rest_parts['host'] )
		) {
			return false;
		}

		$origin_scheme = strtolower( (string) $origin_parts['scheme'] );
		$rest_scheme   = strtolower( (string) $rest_parts['scheme'] );
		$origin_host   = strtolower( (string) $origin_parts['host'] );
		$rest_host     = strtolower( (string) $rest_parts['host'] );
		$origin_port   = isset( $origin_parts['port'] ) ? (int) $origin_parts['port'] : ( 'https' === $origin_scheme ? 443 : 80 );
		$rest_port     = isset( $rest_parts['port'] ) ? (int) $rest_parts['port'] : ( 'https' === $rest_scheme ? 443 : 80 );

		return $origin_scheme === $rest_scheme
			&& $origin_host === $rest_host
			&& $origin_port === $rest_port;
	}

	/**
	 * Toggle the current user's favourite.
	 *
	 * @param WP_REST_Request $request REST request.
	 */
	public function toggle( WP_REST_Request $request ): WP_REST_Response {
		return $this->no_store(
			$this->repository->toggle( get_current_user_id(), (int) $request['post_id'] )
		);
	}

	/**
	 * Build a response that intermediary caches must not store.
	 *
	 * @param array<string, bool|int|string|null> $data Response data.
	 */
	private function no_store( array $data ): WP_REST_Response {
		$response = new WP_REST_Response( $data );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		return $response;
	}
}
