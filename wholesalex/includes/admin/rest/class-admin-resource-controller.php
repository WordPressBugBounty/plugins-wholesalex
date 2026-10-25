<?php
/** Authorized, revision-checked admin resources.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX\Admin\Rest;

defined( 'ABSPATH' ) || exit;

/** Transport only: repositories own fields and persisted schemas. */
final class Admin_Resource_Controller {
	/**
	 * Saved-domain adapter.
	 *
	 * @var Admin_Resource_Repository
	 */
	private $repository;
	/**
	 * Create the repository adapter.
	 */
	public function __construct() {
		$this->repository = new Admin_Resource_Repository();
	}
	/**
	 * Register authorized GET and PATCH routes.
	 */
	public function register_routes() {
		foreach ( array( '/admin/settings', '/admin/products/(?P<id>[1-9][0-9]*)' ) as $route ) {
			register_rest_route(
				'wholesalex/v2',
				$route,
				array(
					'methods'             => array( 'GET', 'PATCH' ),
					'callback'            => array( $this, 'handle' ),
					'permission_callback' => array( $this, 'permission' ),
				)
			);
		}
	}
	/**
	 * Authorize the exact target before reading or writing.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return bool|\WP_Error
	 */
	public function permission( $request ) {
		$id      = (int) $request->get_param( 'id' );
		$allowed = $id ? in_array( get_post_type( $id ), array( 'product', 'product_variation' ), true ) && current_user_can( 'edit_post', $id ) && current_user_can( 'manage_woocommerce' ) : current_user_can( 'manage_options' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers manage_woocommerce for store managers.
		return $allowed ? true : new \WP_Error( 'wholesalex_forbidden', 'You cannot access this resource.', array( 'status' => 403 ) );
	}
	/**
	 * Build a canonical saved-domain response.
	 *
	 * @param array $raw Saved repository values.
	 * @param int   $id Product ID or zero for settings.
	 * @return array
	 */
	private function snapshot( array $raw, $id ) {
		$encoded = wp_json_encode( $raw );
		if ( false === $encoded ) {
			return new \WP_Error( 'wholesalex_encoding', 'Unable to encode resource revision.', array( 'status' => 500 ) );
		}
		$data = $this->repository->project( $raw, $id );
		return array(
			'data'         => $data,
			'effective'    => $this->repository->effective( $data, $id ),
			'capabilities' => array( 'edit' => true ),
			'revision'     => hash( 'sha256', $encoded ),
		);
	}
	/**
	 * Read or atomically validate and patch a resource.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return array|\WP_Error
	 */
	public function handle( $request ) {
		$permission = $this->permission( $request );
		if ( is_wp_error( $permission ) ) {
			return $permission; }
		$id = (int) $request->get_param( 'id' );
		if ( 'GET' === $request->get_method() ) {
			return $this->snapshot( $this->repository->raw( $id ), $id ); }
		if ( strlen( $request->get_body() ) > 16384 ) {
			return new \WP_Error( 'wholesalex_malformed', 'Request body is too large.', array( 'status' => 400 ) );
		}
		$body   = $request->get_json_params();
		$object = json_decode( $request->get_body() );
		if ( ! is_array( $body ) || ! is_object( $object ) || ! isset( $object->changes, $body['resets'], $body['revision'] ) || ! is_object( $object->changes ) || ! is_array( $object->resets ) || ! is_array( $body['resets'] ) || array_values( $body['resets'] ) !== $body['resets'] || ! is_string( $body['revision'] ) || ! preg_match( '/^[a-f0-9]{64}$/', $body['revision'] ) || array_diff( array_keys( $body ), array( 'changes', 'resets', 'revision' ) ) ) {
			return new \WP_Error( 'wholesalex_malformed', 'Expected changes, resets and revision.', array( 'status' => 400 ) );
		}
		$valid = $this->repository->validate( $body['changes'], $body['resets'], $id );
		if ( is_wp_error( $valid ) ) {
			return $valid; }
		global $wpdb;
		// Products share one authoritative option, so the lock must cover all rows.
		// Connection-scoped locks cannot expire underneath an in-flight writer.
		$lock = 'wsx-admin-' . hash( 'sha1', $wpdb->prefix . $this->repository->storage_key( $id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Connection-owned synchronization has no core API; caching would grant a false lock.
		$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 1)', $lock ) );
		if ( '1' !== (string) $acquired ) {
			return new \WP_Error( 'wholesalex_busy', 'Resource is busy; retry.', array( 'status' => 409 ) ); }
		try {
			$raw      = $this->repository->raw( $id );
			$snapshot = $this->snapshot( $raw, $id );
			if ( is_wp_error( $snapshot ) ) {
				return $snapshot;
			}
			if ( ! hash_equals( $snapshot['revision'], $body['revision'] ) ) {
				return new \WP_Error( 'wholesalex_stale', 'Resource changed; reload before saving.', array( 'status' => 409 ) );
			}
			if ( ! $this->repository->save( $raw, $body['changes'], $body['resets'], $id ) ) {
				return new \WP_Error( 'wholesalex_storage', 'Unable to save resource.', array( 'status' => 500 ) );
			}
			return $this->snapshot( $this->repository->raw( $id ), $id );
		} finally {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Release the real connection-owned lock in finally; this operation cannot use cached results.
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}
}
