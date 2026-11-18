<?php
/**
 * Storage for the WholesaleX AI tools OAuth sign-in.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the registered OAuth clients, authorization codes and connections.
 *
 * Clients and connections live in two options that are not autoloaded and
 * are changed with compare-and-swap, so requests running at the same time
 * never undo each other's changes. Authorization codes live in transients
 * for ten minutes and work once.
 * Only SHA-256 hashes of codes, tokens and client secrets are stored, so a
 * copy of the database never contains a working credential.
 */
final class OAuth_Store {

	/**
	 * Option holding the clients that registered themselves.
	 *
	 * @var string
	 */
	const CLIENTS_OPTION = 'wholesalex_mcp_oauth_clients';

	/**
	 * Option holding the connections, one per app and administrator.
	 *
	 * @var string
	 */
	const GRANTS_OPTION = 'wholesalex_mcp_oauth_grants';

	/**
	 * Transient prefix of authorization codes.
	 *
	 * @var string
	 */
	const CODE_PREFIX = 'wsx_mcp_oauth_code_';

	/**
	 * Lifetime of an authorization code, in seconds.
	 *
	 * @var int
	 */
	const CODE_TTL = 600;

	/**
	 * Lifetime of an access token, in seconds.
	 *
	 * @var int
	 */
	const ACCESS_TTL = 3600;

	/**
	 * Lifetime of a refresh token, in seconds. Each refresh starts it again,
	 * so a connection only expires after 90 days without use.
	 *
	 * @var int
	 */
	const REFRESH_TTL = 7776000;

	/**
	 * Maximum number of registered clients.
	 *
	 * @var int
	 */
	const MAX_CLIENTS = 100;

	/**
	 * Maximum number of connections.
	 *
	 * @var int
	 */
	const MAX_GRANTS = 100;

	/**
	 * Age after which a client that never connected is removed, in seconds.
	 *
	 * @var int
	 */
	const UNUSED_CLIENT_TTL = 86400;

	/**
	 * Minimum time between two "last used" updates of a connection, in seconds.
	 *
	 * @var int
	 */
	const TOUCH_INTERVAL = 300;

	/**
	 * Maximum retained refresh hashes before a fresh sign-in is required.
	 *
	 * @var int
	 */
	const MAX_REFRESH_HISTORY = 1024;

	/**
	 * Hash a code, token or secret for storage.
	 *
	 * @param string $secret Plain value.
	 * @return string
	 */
	public static function hash( string $secret ): string {
		return hash( 'sha256', $secret );
	}

	/**
	 * Create a random credential.
	 *
	 * @param string $prefix Prefix that tells credentials apart in logs.
	 * @return string
	 */
	public static function random( string $prefix ): string {
		return $prefix . bin2hex( random_bytes( 32 ) );
	}

	/**
	 * Registered clients, keyed by client ID.
	 *
	 * @return array<string, array>
	 */
	public static function clients(): array {
		$clients = get_option( self::CLIENTS_OPTION, array() );
		return is_array( $clients ) ? $clients : array();
	}

	/**
	 * Return one registered client.
	 *
	 * @param string $client_id Client ID.
	 * @return array|null
	 */
	public static function client( string $client_id ): ?array {
		$clients = self::clients();
		return isset( $clients[ $client_id ] ) && is_array( $clients[ $client_id ] ) ? $clients[ $client_id ] : null;
	}

	/**
	 * Register a client.
	 *
	 * @param string   $name          Client name shown on the consent screen.
	 * @param string[] $redirect_uris Allowed redirect URIs.
	 * @param string   $auth_method   Token endpoint authentication method.
	 * @return array{0: array, 1: string}|null The client and its plain secret ('' for public clients), or null when the store is full.
	 */
	public static function add_client( string $name, array $redirect_uris, string $auth_method ): ?array {
		$secret = 'none' === $auth_method ? '' : self::random( 'wsxcs_' );
		$client = array(
			'client_id'     => 'wsxc_' . bin2hex( random_bytes( 16 ) ),
			'client_name'   => $name,
			'redirect_uris' => array_values( $redirect_uris ),
			'auth_method'   => $auth_method,
			'secret_hash'   => '' === $secret ? '' : self::hash( $secret ),
			'created'       => time(),
		);

		$saved = self::change_option(
			self::CLIENTS_OPTION,
			static function ( array $clients ) use ( $client ) {
				$clients = self::prune_clients( $clients );
				if ( count( $clients ) >= self::MAX_CLIENTS ) {
					return null;
				}
				$clients[ $client['client_id'] ] = $client;
				return $clients;
			}
		);

		return $saved ? array( $client, $secret ) : null;
	}

	/**
	 * Drop clients that never connected within a day, then the oldest unused
	 * ones while the store is full.
	 *
	 * @param array $clients Registered clients.
	 * @return array
	 */
	private static function prune_clients( array $clients ): array {
		$in_use = array_flip( array_column( self::grants(), 'client_id' ) );
		$unused = array();
		foreach ( $clients as $id => $client ) {
			if ( isset( $in_use[ $id ] ) ) {
				continue;
			}
			if ( (int) ( $client['created'] ?? 0 ) < time() - self::UNUSED_CLIENT_TTL ) {
				unset( $clients[ $id ] );
				continue;
			}
			$unused[ $id ] = (int) ( $client['created'] ?? 0 );
		}

		asort( $unused );
		foreach ( array_keys( $unused ) as $id ) {
			if ( count( $clients ) < self::MAX_CLIENTS ) {
				break;
			}
			unset( $clients[ $id ] );
		}

		return $clients;
	}

	/**
	 * Store an authorization code.
	 *
	 * @param string $code Plain code.
	 * @param array  $data Client, redirect URI, PKCE challenge, scope, resource and user.
	 * @return void
	 */
	public static function save_code( string $code, array $data ): void {
		$data['resource'] = OAuth::resource();
		$data['expires']  = time() + self::CODE_TTL;
		set_transient( self::CODE_PREFIX . self::hash( $code ), $data, self::CODE_TTL );
	}

	/**
	 * Return an authorization code and delete it, so it works only once.
	 *
	 * Only the request whose delete removed the code may use it, so two
	 * requests sent at the same time cannot both exchange it.
	 *
	 * @param string $code Plain code.
	 * @return array|null
	 */
	public static function take_code( string $code ): ?array {
		$key     = self::CODE_PREFIX . self::hash( $code );
		$data    = get_transient( $key );
		$deleted = delete_transient( $key );

		if ( ! $deleted || ! is_array( $data ) || (int) ( $data['expires'] ?? 0 ) < time() ) {
			return null;
		}
		return $data;
	}

	/**
	 * Connections, keyed by connection ID. Expired ones are left out.
	 *
	 * @return array<string, array>
	 */
	public static function grants(): array {
		return self::active( get_option( self::GRANTS_OPTION, array() ) );
	}

	/**
	 * Leave out expired connections.
	 *
	 * @param mixed $grants Stored connections.
	 * @return array<string, array>
	 */
	private static function active( $grants ): array {
		return array_filter(
			is_array( $grants ) ? $grants : array(),
			static function ( $grant ) {
				return is_array( $grant ) && (int) ( $grant['refresh_expires'] ?? 0 ) > time();
			}
		);
	}

	/**
	 * Change a stored list with compare-and-swap.
	 *
	 * The new value is written only if the stored value is still the one the
	 * change was based on; otherwise the change runs again on the newer value.
	 * This keeps a disconnected app from coming back through a refresh that
	 * was running at the same time, and keeps a refresh token single-use.
	 *
	 * @param string   $option Option name.
	 * @param callable $change Gets the current list and returns the new list, or null to leave it unchanged. May run more than once.
	 * @return bool Whether the new list was stored.
	 */
	public static function change_option( string $option, callable $change ): bool {
		global $wpdb;

		for ( $attempt = 0; $attempt < 5; $attempt++ ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Compare-and-swap needs the stored value, not a cached copy.
			$stored  = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option ) );
			$current = null === $stored ? array() : maybe_unserialize( $stored );
			$new     = $change( is_array( $current ) ? $current : array() );
			if ( ! is_array( $new ) ) {
				return false;
			}

			$value = maybe_serialize( $new );
			if ( $value === $stored ) {
				return true;
			}
			if ( null === $stored ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- INSERT IGNORE fails when another request created the option first; add_option() would overwrite it.
				$saved = 1 === $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off')", $option, $value ) );
				wp_cache_delete( 'notoptions', 'options' );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Updates only if no other request changed the value meanwhile.
				$saved = 1 === $wpdb->update(
					$wpdb->options,
					array( 'option_value' => $value ),
					array(
						'option_name'  => $option,
						'option_value' => $stored,
					)
				);
			}
			wp_cache_delete( $option, 'options' );
			if ( $saved ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Issue new tokens for a connection.
	 *
	 * A new sign-in replaces the earlier connection of the same app and
	 * administrator. A refresh passes the connection its refresh token
	 * belongs to and rotates both tokens; it fails if the app was
	 * disconnected and revokes the family if that token was consumed meanwhile.
	 *
	 * @param array      $client   Client.
	 * @param int        $user_id  Administrator who approved the connection.
	 * @param string     $scope    Granted scope.
	 * @param string     $audience Server the tokens are valid for.
	 * @param array|null $current  Connection to refresh, as found by its refresh token, or null for a new sign-in.
	 * @return array{access_token: string, refresh_token: string, expires_in: int, scope: string}|null Null when the connection could not be stored or refreshed.
	 */
	public static function issue( array $client, int $user_id, string $scope, string $audience, ?array $current = null ): ?array {
		$now      = time();
		$access   = self::random( 'wsxat_' );
		$refresh  = self::random( 'wsxrt_' );
		$host     = (string) wp_parse_url( (string) ( $client['redirect_uris'][0] ?? '' ), PHP_URL_HOST );
		$grant_id = null === $current ? bin2hex( random_bytes( 8 ) ) : (string) $current['id'];
		$issued   = false;

		$saved = self::change_option(
			self::GRANTS_OPTION,
			static function ( array $grants ) use ( $client, $user_id, $scope, $audience, $current, $now, $access, $refresh, $host, $grant_id, &$issued ) {
				$issued  = false;
				$grants  = self::active( $grants );
				$history = array();

				if ( null === $current ) {
					foreach ( $grants as $id => $grant ) {
						if ( $grant['client_id'] === $client['client_id'] && (int) $grant['user_id'] === $user_id ) {
							unset( $grants[ $id ] );
						}
					}
				} else {
					$stored = $grants[ $grant_id ] ?? null;
					if ( ! $stored || $stored['client_id'] !== $client['client_id'] || (int) $stored['user_id'] !== $user_id || $stored['resource'] !== $audience ) {
						return null;
					}
					$history = array_filter(
						(array) ( $stored['refresh_history'] ?? array() ),
						static function ( $expires ) use ( $now ) {
							return (int) $expires > $now;
						}
					);
					if ( ! hash_equals( (string) $stored['refresh_hash'], (string) $current['refresh_hash'] ) ) {
						// A competing request already rotated this token. Reuse ends the family.
						if ( isset( $history[ $current['refresh_hash'] ] ) ) {
							unset( $grants[ $grant_id ] );
							return $grants;
						}
						return null;
					}
					if ( count( $history ) >= self::MAX_REFRESH_HISTORY ) {
						// Never silently forget a still-valid token's replay history.
						unset( $grants[ $grant_id ] );
						return $grants;
					}
					$history[ $stored['refresh_hash'] ] = (int) $stored['refresh_expires'];
				}

				$grants[ $grant_id ] = array(
					'id'              => $grant_id,
					'client_id'       => $client['client_id'],
					'client_name'     => $client['client_name'],
					'redirect_host'   => $host,
					'user_id'         => $user_id,
					'scope'           => $scope,
					'resource'        => $audience,
					'access_hash'     => self::hash( $access ),
					'access_expires'  => $now + self::ACCESS_TTL,
					'refresh_hash'    => self::hash( $refresh ),
					'refresh_expires' => $now + self::REFRESH_TTL,
					'refresh_history' => $history,
					'created'         => (int) ( $grants[ $grant_id ]['created'] ?? $now ),
					'last_used'       => (int) ( $grants[ $grant_id ]['last_used'] ?? $now ),
				);

				// Keep the most recently used connections when the store is full.
				if ( count( $grants ) > self::MAX_GRANTS ) {
					uasort(
						$grants,
						static function ( $a, $b ) {
							return (int) $b['last_used'] <=> (int) $a['last_used'];
						}
					);
					$grants = array_slice( $grants, 0, self::MAX_GRANTS, true );
				}
				$issued = isset( $grants[ $grant_id ] );
				return $grants;
			}
		);
		if ( ! $saved || ! $issued ) {
			return null;
		}

		return array(
			'access_token'  => $access,
			'refresh_token' => $refresh,
			'expires_in'    => self::ACCESS_TTL,
			'scope'         => $scope,
		);
	}

	/**
	 * Find the connection an access token belongs to.
	 *
	 * @param string $token Plain access token.
	 * @return array|null
	 */
	public static function find_by_access( string $token ): ?array {
		$hash = self::hash( $token );
		foreach ( self::grants() as $grant ) {
			if ( hash_equals( (string) $grant['access_hash'], $hash ) ) {
				return (int) $grant['access_expires'] > time() ? $grant : null;
			}
		}
		return null;
	}

	/**
	 * Find the connection a refresh token belongs to.
	 *
	 * @param string $token Plain refresh token.
	 * @return array|null
	 */
	public static function find_by_refresh( string $token ): ?array {
		$hash = self::hash( $token );
		foreach ( self::grants() as $grant ) {
			if ( hash_equals( (string) $grant['refresh_hash'], $hash ) ) {
				return $grant;
			}
		}
		return null;
	}

	/**
	 * Revoke a family when its authenticated client presents a consumed refresh token.
	 * Unknown tokens and tokens belonging to other clients never affect a connection.
	 *
	 * @param string $token     Presented refresh token.
	 * @param string $client_id Authenticated client ID.
	 * @return void
	 */
	public static function revoke_reused_refresh( string $token, string $client_id ): void {
		$hash = self::hash( $token );
		self::change_option(
			self::GRANTS_OPTION,
			static function ( array $grants ) use ( $hash, $client_id ) {
				foreach ( self::active( $grants ) as $id => $grant ) {
					if ( $client_id === $grant['client_id'] && (int) ( $grant['refresh_history'][ $hash ] ?? 0 ) > time() ) {
						unset( $grants[ $id ] );
						return $grants;
					}
				}
				return null;
			}
		);
	}

	/**
	 * Find the connection a token belongs to, whether it is the access token
	 * or the refresh token, and even when the access token has expired. Used
	 * to revoke: an app that is being removed may send either one.
	 *
	 * @param string $token Plain access or refresh token.
	 * @return array|null
	 */
	public static function find_by_token( string $token ): ?array {
		if ( '' === $token ) {
			return null;
		}
		$hash = self::hash( $token );
		foreach ( self::grants() as $grant ) {
			if ( hash_equals( (string) $grant['access_hash'], $hash ) || hash_equals( (string) $grant['refresh_hash'], $hash ) ) {
				return $grant;
			}
		}
		return null;
	}

	/**
	 * Record that a connection was used, at most once every few minutes.
	 *
	 * @param array $grant Connection.
	 * @return void
	 */
	public static function touch( array $grant ): void {
		if ( (int) $grant['last_used'] > time() - self::TOUCH_INTERVAL ) {
			return;
		}
		$grant_id = (string) $grant['id'];
		self::change_option(
			self::GRANTS_OPTION,
			static function ( array $grants ) use ( $grant_id ) {
				$grants = self::active( $grants );
				if ( ! isset( $grants[ $grant_id ] ) ) {
					return null;
				}
				$grants[ $grant_id ]['last_used'] = time();
				return $grants;
			}
		);
	}

	/**
	 * Remove a connection. Its tokens stop working at once.
	 *
	 * @param string $grant_id Connection ID.
	 * @return array|null The removed connection.
	 */
	public static function revoke( string $grant_id ): ?array {
		$removed = null;
		$saved   = self::change_option(
			self::GRANTS_OPTION,
			static function ( array $grants ) use ( $grant_id, &$removed ) {
				$grants  = self::active( $grants );
				$removed = $grants[ $grant_id ] ?? null;
				if ( null === $removed ) {
					return null;
				}
				unset( $grants[ $grant_id ] );
				return $grants;
			}
		);
		return $saved ? $removed : null;
	}
}
