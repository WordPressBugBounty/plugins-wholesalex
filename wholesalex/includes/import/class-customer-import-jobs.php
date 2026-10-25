<?php
/**
 * Private, site/actor-owned customer CSV jobs. No CSV is persisted in uploads.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Coordinate resumable imports without exposing passwords through file URLs. */
final class Customer_Import_Jobs {

	/** Maximum uploaded CSV bytes. */
	const MAX_BYTES = 5242880;
	/** Private option namespace. */
	const PREFIX = '__wholesalex_customer_import_job_';
	/** Expiry hook; arguments identify only the private job. */
	const EXPIRY_HOOK = 'wholesalex_customer_import_job_expire';
	/**
	 * Current coordination context; no reentrant job execution is permitted.
	 *
	 * @var array|null
	 */
	private $lock;

	/**
	 * Require core operation capability before accessing private job data.
	 *
	 * @return bool
	 */
	private function allowed() {
		return get_current_user_id() > 0 && current_user_can( 'manage_options' ) && ( current_user_can( 'create_users' ) || current_user_can( 'edit_users' ) );
	}

	/**
	 * Validate an opaque identity before deriving an option key.
	 *
	 * @param string $id Job UUID.
	 * @return bool
	 */
	private static function valid_id( $id ) {
		return is_string( $id ) && (bool) preg_match( '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D', $id );
	}

	/**
	 * Construct only an actor-specific key in the current site's options table.
	 *
	 * @param int    $actor Owner.
	 * @param string $id Validated UUID.
	 * @return string
	 */
	private static function key( $actor, $id ) {
		return self::PREFIX . $actor . '_' . $id;
	}

	/**
	 * Create an independent private upload; never delete another existing job.
	 *
	 * @param string $csv Validated complete CSV bytes.
	 * @param array  $settings Validated server-derived import settings.
	 * @return array|\WP_Error
	 */
	public function create( $csv, $settings ) {
		global $wpdb;
		if ( ! $this->allowed() || ! is_string( $csv ) || '' === $csv || strlen( $csv ) > self::MAX_BYTES || ! is_array( $settings ) || ! isset( $settings['total'], $settings['process_per_iteration'], $settings['update_existing'], $settings['find_user_by'] ) || ! is_int( $settings['total'] ) || $settings['total'] < 1 || ! is_int( $settings['process_per_iteration'] ) || $settings['process_per_iteration'] < 1 || $settings['process_per_iteration'] > 100 || ! in_array( $settings['update_existing'], array( 'yes', 'no' ), true ) || ! in_array( $settings['find_user_by'], array( 'username', 'email' ), true ) ) {
			return self::error();
		}
		$job = array_intersect_key( $settings, array_flip( array( 'total', 'process_per_iteration', 'update_existing', 'find_user_by' ) ) );
		$job = array_merge(
			$job,
			array(
				'id'            => wp_generate_uuid4(),
				'actor_id'      => get_current_user_id(),
				'blog_id'       => get_current_blog_id(),
				'version'       => 1,
				'revision'      => 0,
				'state'         => 'pending',
				'csv'           => base64_encode( $csv ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Private CSV byte preservation, not executable code obfuscation.
				'csv_hash'      => hash( 'sha256', $csv ),
				'expires_at'    => time() + DAY_IN_SECONDS,
				'process'       => 0,
				'insert_count'  => 0,
				'update_count'  => 0,
				'skipped_count' => 0,
				'log'           => '',
			)
		);
		$key = self::key( $job['actor_id'], $job['id'] );
		if ( ! self::valid_id( $job['id'] ) ) {
			return self::error();
		}
		// SQL diagnostics must never expose the uploaded customer/password payload.
		$previous_errors = $wpdb->suppress_errors( true );
		try {
			$created = add_option( $key, $job, '', false );
		} finally {
			$wpdb->suppress_errors( $previous_errors );
		}
		if ( ! $created ) {
			// A collision or failed insertion does not grant ownership of this key.
			return self::error();
		}
		if ( get_option( $key ) !== $job ) {
			delete_option( $key );
			return self::error();
		}
		if ( ! wp_schedule_single_event( $job['expires_at'], self::EXPIRY_HOOK, array( $job['actor_id'], $job['id'] ) ) ) {
			delete_option( $key );
			return self::error();
		}
		return $job;
	}

	/**
	 * Load only the current actor's current-site job after authority checks.
	 *
	 * @param string $id Job UUID.
	 * @return array|\WP_Error
	 */
	public function load( $id ) {
		if ( ! $this->allowed() || ! self::valid_id( $id ) ) {
			return self::error();
		}
		$job = get_option( self::key( get_current_user_id(), $id ) );
		if ( ! is_array( $job ) || ! isset( $job['id'], $job['actor_id'], $job['blog_id'], $job['version'], $job['revision'], $job['state'], $job['csv'], $job['csv_hash'], $job['expires_at'] ) || $id !== $job['id'] || get_current_user_id() !== $job['actor_id'] || get_current_blog_id() !== $job['blog_id'] || 1 !== $job['version'] || ! is_int( $job['revision'] ) || $job['revision'] < 0 || ! in_array( $job['state'], array( 'pending', 'processing', 'complete', 'failed', 'cancelled' ), true ) ) {
			return self::error();
		}
		if ( $job['expires_at'] <= time() ) {
			delete_option( self::key( $job['actor_id'], $id ) );
			return self::error();
		}
		if ( in_array( $job['state'], array( 'pending', 'processing' ), true ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Validate private uploaded CSV bytes, never evaluate their contents.
			$csv = is_string( $job['csv'] ) ? base64_decode( $job['csv'], true ) : false;
			if ( ! is_string( $csv ) || strlen( $csv ) > self::MAX_BYTES || hash( 'sha256', $csv ) !== $job['csv_hash'] ) {
				return self::error();
			}
		}
		return $job;
	}

	/**
	 * Execute a server callback under a bounded, connection-fenced job lock.
	 * Callers send JSON only after this method returns and releases coordination.
	 *
	 * @param string   $id Job UUID.
	 * @param callable $callback Trusted server callback, never request supplied.
	 * @return mixed
	 */
	public function run( $id, $callback ) {
		global $wpdb;
		if ( $this->lock || ! $this->allowed() || ! self::valid_id( $id ) || ! is_callable( $callback ) || ! isset( $wpdb->options, $wpdb->prefix ) ) {
			return self::error();
		}
		$name = 'wsx_import:' . substr( hash( 'sha256', DB_NAME . ':' . $wpdb->options . ':' . self::key( get_current_user_id(), $id ) ), 0, 50 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- MySQL permits same-session GET_LOCK reentry; reject existing ownership before acquiring.
		if ( null !== $wpdb->get_var( $wpdb->prepare( 'SELECT IS_USED_LOCK(%s)', $name ) ) ) {
			return self::error();
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Session-local advisory lock cannot use query caches.
		$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,0)', $name ) );
		if ( '1' !== (string) $acquired ) {
			return self::error();
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bind coordination to the exact database session.
		$connection = $wpdb->get_var( 'SELECT CONNECTION_ID()' );
		$this->lock = array(
			'database'   => $wpdb,
			'connection' => (string) $connection,
			'name'       => $name,
			'blog_id'    => get_current_blog_id(),
			'options'    => $wpdb->options,
			'actor_id'   => get_current_user_id(),
			'id'         => $id,
		);
		try {
			if ( ! $this->owns_lock() ) {
				return self::error();
			}
			$job = $this->load( $id );
			return is_wp_error( $job ) ? $job : call_user_func( $callback, $job );
		} catch ( \Throwable $error ) {
			$current = $this->load( $id );
			if ( ! is_wp_error( $current ) && $this->owns_lock() ) {
				$failed          = $current;
				$failed['state'] = 'failed';
				$failed['csv']   = '';
				$this->save( $current, $failed );
			}
			return self::error();
		} finally {
			if ( $this->owns_lock() ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Release only the exact owned session's lock.
				$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
			}
			$this->lock = null;
		}
	}

	/**
	 * Reject changed blog, actor, connection or database ownership.
	 *
	 * @return bool
	 */
	private function owns_lock() {
		global $wpdb;
		if ( ! $this->lock || $wpdb !== $this->lock['database'] || get_current_blog_id() !== $this->lock['blog_id'] || get_current_user_id() !== $this->lock['actor_id'] || $wpdb->options !== $this->lock['options'] ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Verify the lock owner after any possible wpdb reconnect.
		$owner = $wpdb->get_var( $wpdb->prepare( 'SELECT IS_USED_LOCK(%s)', $this->lock['name'] ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fencing requires the current session identity.
		$current = $wpdb->get_var( 'SELECT CONNECTION_ID()' );
		return ctype_digit( $this->lock['connection'] ) && (int) $this->lock['connection'] > 0 && $this->lock['connection'] === (string) $owner && $this->lock['connection'] === (string) $current;
	}

	/**
	 * Stop work before the next customer row if its claim or session changed.
	 *
	 * @param array $claimed Original processing checkpoint.
	 * @return true|\WP_Error
	 */
	public function assert_claim( $claimed ) {
		$id = is_array( $claimed ) ? ( $claimed['id'] ?? null ) : null;
		if ( ! $this->allowed() || ! $this->owns_lock() || ! is_array( $claimed ) || 'processing' !== ( $claimed['state'] ?? null ) || $this->lock['id'] !== $id ) {
			return self::error();
		}
		$current = get_option( self::key( $this->lock['actor_id'], $this->lock['id'] ) );
		return $current === $claimed && $claimed['expires_at'] > time() && $this->owns_lock() ? true : self::error();
	}

	/**
	 * Persist only a byte-exact current checkpoint while the job lock is owned.
	 *
	 * @param array $expected Previously loaded job.
	 * @param array $next Next checkpoint.
	 * @return array|\WP_Error
	 */
	public function save( $expected, $next ) {
		global $wpdb;
		if ( ! $this->owns_lock() || ! preg_match( '/^[A-Za-z0-9_]+$/D', $wpdb->options ) || ! is_array( $expected ) || ! is_array( $next ) ) {
			return self::error();
		}
		$identity = array( $this->lock['id'], $this->lock['actor_id'], $this->lock['blog_id'] );
		$before   = array( $expected['id'] ?? null, $expected['actor_id'] ?? null, $expected['blog_id'] ?? null );
		$after    = array( $next['id'] ?? null, $next['actor_id'] ?? null, $next['blog_id'] ?? null );
		if ( $identity !== $before || $identity !== $after ) {
			return self::error();
		}
		$key      = self::key( $expected['actor_id'], $expected['id'] );
		$previous = $wpdb->suppress_errors();
		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Byte-exact private option CAS; only a validated core table identifier is interpolated.
			$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->options}` SET option_value=%s WHERE option_name=%s AND BINARY option_value=BINARY %s", maybe_serialize( $next ), $key, maybe_serialize( $expected ) ) );
		} finally {
			$wpdb->suppress_errors( $previous );
			wp_cache_delete( $key, 'options' );
		}
		return $this->owns_lock() && get_option( $key ) === $next ? $next : self::error();
	}

	/**
	 * Cron removes only an expired job in this site's private namespace.
	 *
	 * @param int    $actor Owner saved in cron arguments.
	 * @param string $id Job UUID.
	 * @return void
	 */
	public static function expire( $actor, $id ) {
		if ( ! is_int( $actor ) || $actor <= 0 || ! self::valid_id( $id ) ) {
			return;
		}
		$key      = self::key( $actor, $id );
		$job      = get_option( $key );
		$identity = array( $actor, $id, get_current_blog_id() );
		$stored   = is_array( $job ) ? array( $job['actor_id'] ?? null, $job['id'] ?? null, $job['blog_id'] ?? null ) : null;
		if ( $identity === $stored && isset( $job['expires_at'] ) && $job['expires_at'] <= time() ) {
			delete_option( $key );
		}
	}

	/**
	 * Admin fallback for expired jobs when WP-Cron is disabled or delayed.
	 *
	 * @return void
	 */
	public function cleanup_expired() {
		global $wpdb;
		if ( ! isset( $wpdb->options ) || ! preg_match( '/^[A-Za-z0-9_]+$/D', $wpdb->options ) ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Enumerate only temporary-job names; private CSV payloads are not selected in bulk.
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM `{$wpdb->options}` WHERE option_name LIKE %s", $wpdb->esc_like( self::PREFIX ) . '%' ) );
		foreach ( $keys as $key ) {
			$job = get_option( $key );
			if ( is_array( $job ) && isset( $job['actor_id'], $job['id'] ) ) {
				self::expire( $job['actor_id'], $job['id'] );
			}
			// Do not accumulate potentially large private payloads in runtime cache.
			wp_cache_delete( $key, 'options' );
			unset( $job );
		}
	}

	/**
	 * Remove temporary jobs on uninstall regardless of merchant-data preference.
	 *
	 * @return void
	 */
	public function cleanup_all() {
		global $wpdb;
		if ( ! isset( $wpdb->options ) || ! preg_match( '/^[A-Za-z0-9_]+$/D', $wpdb->options ) ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Uninstall enumerates only names in the exact temporary-job namespace.
		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM `{$wpdb->options}` WHERE option_name LIKE %s", $wpdb->esc_like( self::PREFIX ) . '%' ) );
		foreach ( $keys as $key ) {
			delete_option( $key );
		}
		wp_clear_scheduled_hook( self::EXPIRY_HOOK );
	}

	/**
	 * Return a generic error without customer data or passwords.
	 *
	 * @return \WP_Error
	 */
	private static function error() {
		return new \WP_Error( 'wholesalex_customer_import_job_error', __( 'The import job is unavailable, expired, or busy. Please upload the CSV again if an earlier request was interrupted.', 'wholesalex' ) );
	}
}
