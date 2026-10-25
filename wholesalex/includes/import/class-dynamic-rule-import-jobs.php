<?php
/**
 * Private actor-owned dynamic-rule uploads.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Keep CSV bytes outside public uploads and accept only opaque job identities. */
final class Dynamic_Rule_Import_Jobs {
	const PREFIX      = '__wholesalex_dynamic_import_';
	const EXPIRY_HOOK = 'wholesalex_dynamic_import_expire';
	const MAX_BYTES   = 5242880;

	/**
	 * Validate a job identity before deriving its option key.
	 *
	 * @param string $id Job identity.
	 * @return string|false
	 */
	private function key( $id ) {
		return current_user_can( 'manage_options' ) && get_current_user_id() > 0 && is_string( $id ) && preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D', $id ) ? self::PREFIX . get_current_user_id() . '_' . $id : false;
	}

	/**
	 * Store a bounded upload without creating a public file.
	 *
	 * @param string $csv CSV bytes.
	 * @param string $purpose Import schema.
	 * @return string|\WP_Error
	 */
	public function create( $csv, $purpose = 'dynamic' ) {
		$id  = wp_generate_uuid4();
		$key = $this->key( $id );
		if ( ! $key || ! in_array( $purpose, array( 'dynamic', 'pricing' ), true ) || ! is_string( $csv ) || '' === $csv || strlen( $csv ) > self::MAX_BYTES ) {
			return $this->error();
		}
		$job = array(
			'csv'     => $csv,
			'purpose' => $purpose,
			'expires' => time() + DAY_IN_SECONDS,
			'blog'    => get_current_blog_id(),
		);
		if ( ! add_option( $key, $job, '', false ) ) {
			return $this->error();
		}
		if ( get_option( $key ) !== $job || ! wp_schedule_single_event( $job['expires'], self::EXPIRY_HOOK, array( get_current_user_id(), $id ) ) ) {
			delete_option( $key );
			return $this->error();
		}
		return $id;
	}

	/**
	 * Open current actor's CSV in a request-local memory stream.
	 *
	 * @param string $id Job identity, never a path.
	 * @param string $purpose Import schema.
	 * @return resource|\WP_Error
	 */
	public function open( $id, $purpose = 'dynamic' ) {
		$key = $this->key( $id );
		$job = $key ? get_option( $key ) : false;
		if ( ! is_array( $job ) || ( $job['purpose'] ?? '' ) !== $purpose || ! isset( $job['expires'], $job['csv'], $job['blog'] ) || get_current_blog_id() !== $job['blog'] || $job['expires'] <= time() || ! is_string( $job['csv'] ) || strlen( $job['csv'] ) > self::MAX_BYTES ) {
			return $this->error();
		}
		$stream = fopen( 'php://memory', 'w+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Request-local memory stream never creates a public file.
		if ( ! $stream || strlen( $job['csv'] ) !== fwrite( $stream, $job['csv'] ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Write private CSV bytes only into the request-local memory stream.
			if ( is_resource( $stream ) ) {
				fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Release the request-local memory stream.
			}
			return $this->error();
		}
		rewind( $stream );
		return $stream;
	}

	/**
	 * Remove a completed or failed upload immediately.
	 *
	 * @param string $id Job identity.
	 * @return void
	 */
	public function delete( $id ) {
		$key = $this->key( $id );
		if ( $key ) {
			delete_option( $key );
			wp_clear_scheduled_hook( self::EXPIRY_HOOK, array( get_current_user_id(), $id ) );
		}
	}

	/**
	 * Remove an abandoned upload on its owning site's cron.
	 *
	 * @param int    $actor Original actor.
	 * @param string $id Job identity.
	 * @return void
	 */
	public static function expire( $actor, $id ) {
		if ( ! is_int( $actor ) || $actor < 1 || ! is_string( $id ) || ! preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D', $id ) ) {
			return;
		}
		$key = self::PREFIX . $actor . '_' . $id;
		$job = get_option( $key );
		if ( is_array( $job ) && isset( $job['expires'], $job['blog'] ) && get_current_blog_id() === $job['blog'] && $job['expires'] <= time() ) {
			delete_option( $key );
		}
	}

	/**
	 * Generic failure without private paths or contents.
	 *
	 * @return \WP_Error
	 */
	private function error() {
		return new \WP_Error( 'wholesalex_dynamic_import_unavailable', __( 'The import is unavailable or expired. Please upload the CSV again.', 'wholesalex' ) );
	}
}
