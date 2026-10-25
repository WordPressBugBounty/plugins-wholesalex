<?php
/**
 * Account confirmation state and lifecycle boundary.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Serializes consumption, issuance and credential/status changes per account.
 */
final class Confirmation_Service {
	/**
	 * Locks.
	 *
	 * @var array
	 */
	private static $locks = array();
	/**
	 * Updates.
	 *
	 * @var array
	 */
	private static $updates = array();
	/**
	 * State.
	 *
	 * @var string
	 */
	const STATE = '__wholesalex_confirmation_state';

	/**
	 * Neutral registration participation marker attached before customer creation.
	 *
	 * @var string
	 */
	const INITIALIZATION = '__wholesalex_registration_initialization';

	/**
	 * Register.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'update_user_metadata', array( __CLASS__, 'guard_metadata' ), 10, 5 );
		add_filter( 'add_user_metadata', array( __CLASS__, 'guard_add_metadata' ), 10, 5 );
		add_filter( 'delete_user_metadata', array( __CLASS__, 'guard_delete_metadata' ), 10, 5 );
		add_filter( 'wp_pre_insert_user_data', array( __CLASS__, 'guard_credentials' ), 10, 3 );
		add_action( 'profile_update', array( __CLASS__, 'finish_credentials' ), 10, 1 );
		add_action( 'password_reset', array( __CLASS__, 'password_reset' ), 10, 1 );
	}

	/**
	 * Persist a requested status before callers publish success side effects.
	 *
	 * @param int    $id Account ID.
	 * @param string $status Requested status.
	 * @return true|\WP_Error
	 */
	public static function set_status( $id, $status ) {
		$error = new \WP_Error( 'wholesalex_status_write_failed', __( 'The account status could not be saved. Please try again.', 'wholesalex' ), array( 'status' => 503 ) );
		if ( ! in_array( $status, array( 'active', 'pending', 'reject', 'inactive' ), true ) ) {
			return $error;
		}
		if ( ! self::acquire( $id ) ) {
			self::revoke_locked( $id );
			return $error;
		}
		try {
			$current = get_user_meta( $id, '__wholesalex_status', true );
			if ( $status === $current ) {
				return self::complete_initialization( $id, $status, $error );
			}
			self::revoke_locked( $id );
			if ( ! update_user_meta( $id, '__wholesalex_status', $status ) ) {
				return $error;
			}
			wp_cache_delete( $id, 'user_meta' );
			return get_user_meta( $id, '__wholesalex_status', true ) === $status ? self::complete_initialization( $id, $status, $error ) : $error;
		} finally {
			self::release( $id );
		}
	}

	/**
	 * Complete a new signup only after active state was authoritatively verified.
	 *
	 * @param int       $id Account ID.
	 * @param string    $status Verified status.
	 * @param \WP_Error $error Failure result.
	 * @return true|\WP_Error
	 */
	private static function complete_initialization( $id, $status, $error ) {
		if ( in_array( $status, array( 'pending', 'reject', 'inactive' ), true ) ) {
			\WP_Session_Tokens::get_instance( $id )->destroy_all();
		}
		$marker = get_user_meta( $id, self::INITIALIZATION, true );
		if ( 'active' !== $status || '' === $marker || 'complete' === $marker ) {
			return true;
		}
		if ( 'required' !== $marker || ! update_user_meta( $id, self::INITIALIZATION, 'complete' ) ) {
			return $error;
		}
		wp_cache_delete( $id, 'user_meta' );
		return 'complete' === get_user_meta( $id, self::INITIALIZATION, true ) ? true : $error;
	}

	/**
	 * MySQL/MariaDB connection-owned advisory lock, bounded to three seconds.
	 * Unsupported platforms/busy locks deny confirmation instead of guessing.
	 *
	 * @param int $id Account ID.
	 * @return bool
	 */
	private static function acquire( $id ) {
		global $wpdb;
		if ( isset( self::$locks[ $id ] ) ) {
			++self::$locks[ $id ];
			return true;
		}
		if ( self::$locks || ! $wpdb || ! ( $wpdb instanceof \wpdb ) || ! is_callable( array( $wpdb, 'get_var' ) ) || ! is_callable( array( $wpdb, 'prepare' ) ) ) {
			return false;
		}
		$name = 'wsx-confirm:' . substr( hash( 'sha256', $wpdb->users . ':' . $id ), 0, 40 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Connection lock, not cacheable account data.
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $name ) ) ) {
			return false;
		}
		self::$locks[ $id ] = 1;
		wp_cache_delete( $id, 'user_meta' );
		clean_user_cache( $id );
		return true;
	}

	/**
	 * Release.
	 *
	 * @param int $id Account ID.
	 * @return void
	 */
	private static function release( $id ) {
		global $wpdb;
		if ( ! isset( self::$locks[ $id ] ) || --self::$locks[ $id ] > 0 ) {
			return;
		}
		unset( self::$locks[ $id ] );
		$name = 'wsx-confirm:' . substr( hash( 'sha256', $wpdb->users . ':' . $id ), 0, 40 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Release the connection-owned lock.
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
	}

	/**
	 * Error.
	 *
	 * @return \WP_Error
	 */
	private static function error() {
		return new \WP_Error( 'confirmation_invalid', __( 'This confirmation link cannot be used. Please request a new confirmation email.', 'wholesalex' ) );
	}

	/**
	 * Pending.
	 *
	 * @param int $id Account ID.
	 * @return bool
	 */
	private static function pending( $id ) {
		$role = get_user_meta( $id, '__wholesalex_registration_role', true );
		return 'pending' === get_user_meta( $id, '__wholesalex_status', true ) && ! get_user_meta( $id, '__wholesalex_account_confirmed', true ) && is_string( $role ) && '' !== $role && ! is_wp_error( Registration_Context::approval_error( $id, $role ) );
	}

	/**
	 * Issue/reissue only for a verified pending registration scope.
	 *
	 * @param int $id Account ID.
	 * @return string|\WP_Error Raw token for the verification email.
	 */
	public static function issue( $id ) {
		if ( ! self::acquire( $id ) ) {
			return self::error();
		}
		try {
			$user = get_userdata( $id );
			if ( ! $user || ! self::pending( $id ) ) {
				return self::error();
			}
			$ttl = (int) apply_filters( 'wholesalex_email_confirmation_link_ttl', 2 * DAY_IN_SECONDS );
			if ( $ttl <= 0 ) {
				return self::error();
			}
			$token = bin2hex( random_bytes( 32 ) );
			$state = array(
				'phase'        => 'pending',
				'user_id'      => (int) $id,
				'email'        => $user->user_email,
				'role'         => get_user_meta( $id, '__wholesalex_registration_role', true ),
				'context'      => get_user_meta( $id, '__wholesalex_registration_context', true ),
				'hash'         => hash( 'sha256', $token ),
				'issued_at'    => time(),
				'expires_at'   => time() + $ttl,
				'resend_after' => time() + 60,
			);
			if ( ! update_user_meta( $id, self::STATE, $state ) ) {
				return self::error();
			}
			update_user_meta( $id, '__wholesalex_email_confirmation_code', $token );
			update_user_meta( $id, '__wholesalex_email_confirmation_code_time', $state['issued_at'] );
			return $token;
		} finally {
			self::release( $id );
		}
	}

	/**
	 * Consume once, while lifecycle writers share the same atomic boundary.
	 *
	 * @param int    $id Account ID.
	 * @param string $token Secret from the email.
	 * @return true|\WP_Error
	 */
	public static function confirm( $id, $token ) {
		if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) || ! self::acquire( $id ) ) {
			return self::error();
		}
		try {
			$state = get_user_meta( $id, self::STATE, true );
			$user  = get_userdata( $id );
			if ( ! self::matches( $id, $token, $state, $user ) || ! self::pending( $id ) || $state['issued_at'] > time() || $state['expires_at'] <= time() ) {
				return self::error();
			}
			$claimed          = $state;
			$claimed['phase'] = 'consumed';
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic compare-and-swap; update_user_meta can insert when the previous state was deleted.
			$changed = $wpdb->update(
				$wpdb->usermeta,
				array( 'meta_value' => maybe_serialize( $claimed ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Atomic token claim is constrained by indexed user_id, not an unbounded metadata search.
				array(
					'user_id'    => $id,
					'meta_key'   => self::STATE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Fixed state key within this indexed user_id; required to avoid claiming unrelated metadata.
					'meta_value' => maybe_serialize( $state ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Compare previous state under indexed user_id so deleted or replaced tokens cannot be recreated.
				),
				array( '%s' ),
				array( '%d', '%s', '%s' )
			);
			wp_cache_delete( $id, 'user_meta' );
			if ( 1 !== $changed ) {
				return self::error();
			}
			// Consume before membership writes; failures never leave a reusable bearer.
			delete_user_meta( $id, '__wholesalex_email_confirmation_code' );
			delete_user_meta( $id, '__wholesalex_email_confirmation_code_time' );
			if ( ! update_user_meta( $id, '__wholesalex_account_confirmed', true ) || ! update_user_meta( $id, '__wholesalex_status', 'active' ) ) {
				return self::error();
			}
			if ( is_wp_error( self::complete_initialization( $id, 'active', self::error() ) ) ) {
				return self::error();
			}
			$assigned = get_user_meta( $id, '__wholesalex_role', true );
			wholesalex()->change_role( $id, $assigned ? $assigned : $state['role'] );
			do_action( 'wholesalex_user_email_verified', $id );
			return true;
		} finally {
			self::release( $id );
		}
	}

	/**
	 * Matches.
	 *
	 * @param int          $id ID.
	 * @param string       $token Token.
	 * @param mixed        $state State.
	 * @param object|false $user User.
	 * @return bool
	 */
	private static function matches( $id, $token, $state, $user ) {
		return $user && is_array( $state ) && 'pending' === ( $state['phase'] ?? null ) && ( $state['user_id'] ?? null ) === (int) $id && ( $state['email'] ?? null ) === $user->user_email && get_user_meta( $id, '__wholesalex_registration_role', true ) === ( $state['role'] ?? null ) && get_user_meta( $id, '__wholesalex_registration_context', true ) === ( $state['context'] ?? null ) && isset( $state['hash'], $state['issued_at'], $state['expires_at'], $state['resend_after'] ) && is_int( $state['issued_at'] ) && is_int( $state['expires_at'] ) && is_int( $state['resend_after'] ) && is_string( $state['hash'] ) && hash_equals( $state['hash'], hash( 'sha256', $token ) );
	}

	/**
	 * Resend uses possession of the original bearer plus a context-bound nonce.
	 * All callers receive the same response; a lock protects the rate boundary.
	 *
	 * @param int    $id ID.
	 * @param string $token Original token (expired is allowed).
	 * @param string $nonce Context-bound nonce.
	 * @return void
	 */
	public static function resend( $id, $token, $nonce ) {
		if ( ! is_string( $token ) || ! preg_match( '/^(?:[a-f0-9]{32}|[a-f0-9]{64})$/D', $token ) || ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, self::nonce_action( $id, $token ) ) || ! self::acquire( $id ) ) {
			return;
		}
		try {
			$state  = get_user_meta( $id, self::STATE, true );
			$legacy = ! is_array( $state ) && is_string( get_user_meta( $id, '__wholesalex_email_confirmation_code', true ) ) && hash_equals( get_user_meta( $id, '__wholesalex_email_confirmation_code', true ), $token );
			if ( ( ! $legacy && ! self::matches( $id, $token, $state, get_userdata( $id ) ) ) || ! self::pending( $id ) || ( ! $legacy && time() < $state['resend_after'] ) ) {
				return;
			}
			$new_token = self::issue( $id );
			if ( ! is_wp_error( $new_token ) ) {
				do_action( 'wholesalex_user_email_confirmation', $id, $new_token );
			}
		} finally {
			self::release( $id );
		}
	}

	/**
	 * Nonce action.
	 *
	 * @param int    $id ID.
	 * @param string $token Token.
	 * @return string
	 */
	public static function nonce_action( $id, $token ) {
		return 'wholesalex-resend-confirmation:' . $id . ':' . hash( 'sha256', $token );
	}

	/**
	 * Revoke locked.
	 *
	 * @param int $id ID.
	 * @return void
	 */
	private static function revoke_locked( $id ) {
		delete_user_meta( $id, self::STATE );
		delete_user_meta( $id, '__wholesalex_email_confirmation_code' );
		delete_user_meta( $id, '__wholesalex_email_confirmation_code_time' );
	}

	/**
	 * Guard metadata.
	 *
	 * @param mixed  $check Short circuit.
	 * @param int    $id ID.
	 * @param string $key Key.
	 * @param mixed  $value Value.
	 * @param mixed  $prev Previous value.
	 * @return mixed
	 */
	public static function guard_metadata( $check, $id, $key, $value, $prev ) {
		if ( null !== $check || isset( self::$locks[ $id ] ) || ! in_array( $key, array( '__wholesalex_status', '__wholesalex_registration_role', '__wholesalex_registration_context' ), true ) ) {
			return $check;
		}
		if ( ! self::acquire( $id ) ) {
			self::revoke_locked( $id );
			return false;
		}
		try {
			if ( ( '__wholesalex_status' === $key && 'pending' !== $value ) || get_user_meta( $id, $key, true ) !== $value ) {
				self::revoke_locked( $id );
			}
			return update_user_meta( $id, $key, $value, $prev );
		} finally {
			self::release( $id );
		}
	}

	/**
	 * Guard add metadata.
	 *
	 * @param mixed  $check Short circuit.
	 * @param int    $id ID.
	 * @param string $key Key.
	 * @param mixed  $value Value.
	 * @param bool   $unique Unique.
	 * @return mixed
	 */
	public static function guard_add_metadata( $check, $id, $key, $value, $unique ) {
		if ( null !== $check || isset( self::$locks[ $id ] ) || ! in_array( $key, array( '__wholesalex_status', '__wholesalex_registration_role', '__wholesalex_registration_context' ), true ) ) {
			return $check;
		}
		if ( ! self::acquire( $id ) ) {
			self::revoke_locked( $id );
			return false;
		}
		try {
			self::revoke_locked( $id );
			return add_user_meta( $id, $key, $value, $unique );
		} finally {
			self::release( $id );
		}
	}

	/**
	 * Guard delete metadata.
	 *
	 * @param mixed  $check Short circuit.
	 * @param int    $id ID.
	 * @param string $key Key.
	 * @param mixed  $value Value.
	 * @param bool   $all All users.
	 * @return mixed
	 */
	public static function guard_delete_metadata( $check, $id, $key, $value, $all ) {
		if ( null !== $check || isset( self::$locks[ $id ] ) || ! in_array( $key, array( '__wholesalex_status', '__wholesalex_registration_role', '__wholesalex_registration_context' ), true ) ) {
			return $check;
		}
		if ( $all ) {
			return false;
		}
		if ( ! self::acquire( $id ) ) {
			self::revoke_locked( $id );
			return false;
		}
		try {
			self::revoke_locked( $id );
			return delete_user_meta( $id, $key, $value );
		} finally {
			self::release( $id );
		}
	}

	/**
	 * Guard credentials.
	 *
	 * @param array $data User database fields.
	 * @param bool  $update Updating.
	 * @param int   $id ID.
	 * @return array
	 */
	public static function guard_credentials( $data, $update, $id ) {
		$user = $update ? get_userdata( $id ) : false;
		if ( ! $user || ( $data['user_email'] === $user->user_email && $data['user_pass'] === $user->user_pass ) ) {
			return $data;
		}
		if ( ! get_user_meta( $id, self::STATE, true ) && ! get_user_meta( $id, '__wholesalex_email_confirmation_code', true ) ) {
			return $data;
		}
		if ( ! self::acquire( $id ) ) {
			// Core rejects an empty user-data array with WP_Error before writing.
			// Never report success while silently retaining the previous credentials.
			return array();
		}
		self::$updates[ $id ] = true;
		self::revoke_locked( $id );
		return $data;
	}

	/**
	 * Finish credentials.
	 *
	 * @param int $id ID.
	 * @return void
	 */
	public static function finish_credentials( $id ) {
		if ( isset( self::$updates[ $id ] ) ) {
			unset( self::$updates[ $id ] );
			self::release( $id );
		}
	}

	/**
	 * Password reset.
	 *
	 * @param object $user User being reset.
	 * @return void
	 */
	public static function password_reset( $user ) {
		if ( self::acquire( $user->ID ) ) {
			try {
				self::revoke_locked( $user->ID );
			} finally {
				self::release( $user->ID );
			}
		} else {
			// Revocation is monotonic even if a different request holds the lock.
			self::revoke_locked( $user->ID );
		}
	}
}
