<?php
/**
 * OAuth sign-in for the WholesaleX AI tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-server.php';
require_once __DIR__ . '/class-oauth-store.php';

/**
 * Lets web apps such as ChatGPT, Claude and Gemini connect with OAuth 2.1.
 *
 * Those apps connect from the internet and have no field for an application
 * password. They find this sign-in on their own: the MCP endpoint answers an
 * unauthenticated request with a `WWW-Authenticate` header that points to the
 * resource metadata (RFC 9728), which names this site as the authorization
 * server (RFC 8414). ChatGPT then registers itself (RFC 7591), Claude may
 * identify itself with a client ID metadata document instead, and an
 * administrator approves the connection on a consent screen on this site.
 *
 * Safeguards:
 * - PKCE with S256 is required, codes work once and expire after ten minutes.
 * - Codes are only sent back to trusted app hosts or to this computer.
 * - Access tokens only authenticate requests to the WholesaleX MCP endpoint,
 *   never the rest of the REST API, and the endpoint still requires an
 *   administrator and the configured access level on every request.
 * - Refresh tokens rotate on every use, and an administrator can disconnect
 *   any app on the AI Tools screen.
 * - Everything is off while the AI tools are off.
 */
final class OAuth {

	/**
	 * REST route of the authorization server, under the WholesaleX namespace.
	 *
	 * @var string
	 */
	const ROUTE = 'oauth';

	/**
	 * Path of the consent screen, relative to the site address.
	 *
	 * @var string
	 */
	const AUTHORIZE_PATH = 'wholesalex-oauth/authorize';

	/**
	 * Scopes offered to apps. Access itself follows the configured access
	 * level; `offline_access` tells apps such as ChatGPT that refresh tokens
	 * are issued.
	 *
	 * @var string[]
	 */
	const SCOPES = array( 'wholesalex', 'offline_access' );

	/**
	 * Apps one address may register per window.
	 *
	 * @var int
	 */
	const REGISTER_LIMIT = 60;

	/**
	 * Length of the registration window, in seconds.
	 *
	 * @var int
	 */
	const REGISTER_WINDOW = 3600;

	/**
	 * Whether the current request carried an access token that did not work.
	 *
	 * @var bool
	 */
	private static $invalid_token = false;

	/**
	 * Whether the current user was signed in with an access token.
	 *
	 * @var bool
	 */
	private static $token_user = false;

	/**
	 * Register the hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'parse_request', array( __CLASS__, 'route_front_request' ), 1 );
		add_filter( 'determine_current_user', array( __CLASS__, 'authenticate' ), 30 );
		add_filter( 'rest_authentication_errors', array( __CLASS__, 'restrict_token' ), 99 );
		add_filter( 'application_password_is_api_request', array( __CLASS__, 'skip_application_passwords' ) );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'add_challenge' ), 10, 3 );
		add_filter( 'rest_exposed_cors_headers', array( __CLASS__, 'expose_challenge' ) );
	}

	/**
	 * Whether apps can sign in: enabled tools, pretty permalinks and secure transport.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return Access::is_enabled() && '' !== (string) get_option( 'permalink_structure' ) && self::secure_transport();
	}

	/**
	 * Require HTTPS for the configured origins and the actual request.
	 * WordPress must be configured to recognize TLS at a trusted reverse proxy;
	 * client-supplied forwarding headers are deliberately not trusted here.
	 * Plain HTTP is limited to loopback origins in local/development environments.
	 *
	 * @return bool
	 */
	public static function secure_transport(): bool {
		$urls     = array( home_url(), self::issuer(), self::resource() );
		$https    = true;
		$loopback = true;
		foreach ( $urls as $url ) {
			$parts    = wp_parse_url( $url );
			$https    = $https && 'https' === ( $parts['scheme'] ?? '' ) && ! empty( $parts['host'] );
			$loopback = $loopback && in_array( strtolower( $parts['host'] ?? '' ), array( 'localhost', '127.0.0.1', '[::1]' ), true ) && in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true );
		}
		if ( $https && is_ssl() ) {
			return true;
		}
		return $loopback && in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	}

	/**
	 * Whether a stored grant or authorization code belongs to this MCP endpoint.
	 *
	 * @param mixed $audience Stored resource.
	 * @return bool
	 */
	public static function resource_matches( $audience ): bool {
		return is_string( $audience ) && '' !== $audience && untrailingslashit( $audience ) === untrailingslashit( self::resource() );
	}

	/**
	 * Whether web apps can reach the sign-in, with a message for the store owner.
	 *
	 * @return array{0: bool, 1: string}
	 */
	public static function readiness(): array {
		if ( ! Server::adapter_available() ) {
			return array( false, __( 'Activate the MCP Adapter plugin. Without it there is no server to connect to.', 'wholesalex' ) );
		}
		if ( ! Access::is_enabled() ) {
			return array( false, __( 'Choose an access level above and save it.', 'wholesalex' ) );
		}
		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			return array( false, __( 'Turn on pretty permalinks in Settings > Permalinks.', 'wholesalex' ) );
		}
		$home = home_url();
		$host = strtolower( (string) wp_parse_url( $home, PHP_URL_HOST ) );
		if ( self::is_private_host( $host ) ) {
			/* translators: %s: site host name, for example wholesalex.local */
			return array( false, sprintf( __( '%s only works on this computer. ChatGPT, Claude and Gemini need a public HTTPS address.', 'wholesalex' ), $host ) );
		}
		if ( 'https' !== wp_parse_url( $home, PHP_URL_SCHEME ) ) {
			return array( false, __( 'Your site address must start with https://. Change it in Settings > General once your host has an SSL certificate.', 'wholesalex' ) );
		}
		if ( ! self::secure_transport() ) {
			return array( false, __( 'OAuth requires HTTPS for this request and the REST endpoints. If your host uses a reverse proxy, configure WordPress to recognize its trusted HTTPS connections.', 'wholesalex' ) );
		}
		return array( true, __( 'Ready for ChatGPT, Claude and Gemini', 'wholesalex' ) );
	}

	/**
	 * Whether a host only works on this computer or a private network.
	 *
	 * @param string $host Host name.
	 * @return bool
	 */
	private static function is_private_host( string $host ): bool {
		if ( 'localhost' === $host ) {
			return true;
		}
		foreach ( array( '.local', '.localhost', '.test', '.example', '.invalid', '.internal', '.lan', '.home.arpa' ) as $suffix ) {
			if ( substr( $host, -strlen( $suffix ) ) === $suffix ) {
				return true;
			}
		}
		$ip = trim( $host, '[]' );
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		}
		return false;
	}

	/**
	 * The MCP endpoint the tokens are for.
	 *
	 * @return string
	 */
	public static function resource(): string {
		return Server::endpoint();
	}

	/**
	 * Issuer identifier of the authorization server.
	 *
	 * @return string
	 */
	public static function issuer(): string {
		return untrailingslashit( rest_url( Server::ROUTE_NAMESPACE . '/' . self::ROUTE ) );
	}

	/**
	 * Address of the consent screen.
	 *
	 * @return string
	 */
	public static function authorize_url(): string {
		return home_url( '/' . self::AUTHORIZE_PATH );
	}

	/**
	 * Address of an authorization server REST route.
	 *
	 * @param string $path Path below the issuer.
	 * @return string
	 */
	private static function endpoint( string $path ): string {
		return rest_url( Server::ROUTE_NAMESPACE . '/' . self::ROUTE . '/' . $path );
	}

	/**
	 * Hosts that may receive authorization codes and publish client ID
	 * metadata documents. Apps on this computer, such as Claude Code, use
	 * http://localhost or http://127.0.0.1 and are always allowed.
	 *
	 * @return string[]
	 */
	public static function trusted_hosts(): array {
		/**
		 * Filters the app hosts that may receive WholesaleX AI tools sign-ins.
		 *
		 * Every host added here can receive a token that acts as an
		 * administrator once someone approves it, so only add hosts of AI apps
		 * you trust.
		 *
		 * @since 3.2.0
		 *
		 * @param string[] $hosts Host names.
		 */
		$hosts = apply_filters( 'wholesalex_mcp_oauth_trusted_hosts', array( 'claude.ai', 'claude.com', 'chatgpt.com', 'chat.openai.com', 'vscode.dev' ) );
		return is_array( $hosts ) ? array_map( 'strtolower', array_filter( $hosts, 'is_string' ) ) : array();
	}

	/**
	 * Whether an app may register this redirect URI.
	 *
	 * @param string $uri Redirect URI.
	 * @return bool
	 */
	public static function redirect_uri_allowed( string $uri ): bool {
		$parts = wp_parse_url( $uri );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) || isset( $parts['fragment'] ) || isset( $parts['user'] ) || strlen( $uri ) > 2000 ) {
			return false;
		}
		$host = strtolower( $parts['host'] );
		if ( 'http' === $parts['scheme'] ) {
			return in_array( $host, array( 'localhost', '127.0.0.1', '[::1]' ), true );
		}
		// Google documents these six callback patterns for Connected Apps.
		// They are redirect destinations only, not trusted metadata publishers.
		// phpcs:ignore PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- OAuth redirect host allowlist; no remote scripts, styles or images are loaded.
		if ( in_array( $host, array( 'oauth-redirect.googleusercontent.com', 'oauth-redirect-sandbox.googleusercontent.com', 'oauth-redirect-test.googleusercontent.com' ), true ) ) {
			return 'https' === $parts['scheme']
				&& ( ! isset( $parts['port'] ) || 443 === $parts['port'] )
				&& 1 === preg_match( '#^/(?:r|a)/[A-Za-z0-9][A-Za-z0-9._~-]*$#D', $parts['path'] ?? '' );
		}
		return 'https' === $parts['scheme'] && in_array( $host, self::trusted_hosts(), true );
	}

	/**
	 * Find a client by ID: one that registered itself, or one identified by a
	 * client ID metadata document on a trusted host.
	 *
	 * @param string $client_id Client ID.
	 * @return array|null
	 */
	public static function client( string $client_id ): ?array {
		if ( 0 === strpos( $client_id, 'https://' ) ) {
			return self::metadata_client( $client_id );
		}
		return OAuth_Store::client( $client_id );
	}

	/**
	 * Load a client from its client ID metadata document.
	 *
	 * Only documents on trusted hosts are fetched, so this never requests an
	 * address an attacker chose. The client is treated as public and protected
	 * by PKCE. Documents that prefer `private_key_jwt` are accepted when they
	 * also list `none`: ChatGPT's does, and it then uses `none` because the
	 * server metadata does not offer `private_key_jwt`.
	 *
	 * @param string $url Client ID, which is the document address.
	 * @return array|null
	 */
	private static function metadata_client( string $url ): ?array {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['path'] ) || '/' === $parts['path'] ) {
			return null;
		}
		if ( ! in_array( strtolower( $parts['host'] ), self::trusted_hosts(), true ) ) {
			self::log( sprintf( 'Client ID metadata document %s refused: the host is not trusted. Add it with the wholesalex_mcp_oauth_trusted_hosts filter if you trust the app.', $url ) );
			return null;
		}

		$key    = 'wsx_mcp_oauth_cimd_' . md5( $url );
		$client = get_transient( $key );
		if ( is_array( $client ) ) {
			return $client;
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 5,
				'redirection'         => 0,
				'limit_response_size' => 10240,
				'headers'             => array( 'Accept' => 'application/json' ),
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			self::log( sprintf( 'Client ID metadata document %s could not be fetched: %s', $url, is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response ) ) );
			return null;
		}
		$document = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $document ) || ( $document['client_id'] ?? '' ) !== $url ) {
			self::log( sprintf( 'Client ID metadata document %s refused: it is not JSON or its client_id differs from its address.', $url ) );
			return null;
		}
		$method    = is_string( $document['token_endpoint_auth_method'] ?? null ) ? $document['token_endpoint_auth_method'] : 'none';
		$supported = isset( $document['token_endpoint_auth_methods_supported'] ) && is_array( $document['token_endpoint_auth_methods_supported'] ) ? $document['token_endpoint_auth_methods_supported'] : array( $method );
		if ( 'none' !== $method && ! in_array( 'none', $supported, true ) ) {
			self::log( sprintf( 'Client ID metadata document %s refused: the app cannot sign in as a public client (token_endpoint_auth_method %s).', $url, $method ) );
			return null;
		}
		$uris = array_values( array_filter( (array) ( $document['redirect_uris'] ?? array() ), array( __CLASS__, 'is_allowed_redirect_value' ) ) );
		if ( empty( $uris ) ) {
			self::log( sprintf( 'Client ID metadata document %s refused: none of its redirect URIs is on a trusted host.', $url ) );
			return null;
		}

		$client = array(
			'client_id'     => $url,
			'client_name'   => self::client_name( $document['client_name'] ?? '', $parts['host'] ),
			'redirect_uris' => $uris,
			'auth_method'   => 'none',
			'secret_hash'   => '',
			'created'       => time(),
		);
		set_transient( $key, $client, HOUR_IN_SECONDS );

		return $client;
	}

	/**
	 * Whether a redirect URI was registered by the client.
	 *
	 * Apps on this computer, such as Claude Code, register a loopback address
	 * and listen on whichever port is free, so the port of an http loopback
	 * address is ignored (RFC 8252, section 7.3). Everything else must match.
	 *
	 * @param string   $uri        Redirect URI from the request.
	 * @param string[] $registered Registered redirect URIs.
	 * @return bool
	 */
	private static function redirect_uri_registered( string $uri, array $registered ): bool {
		if ( in_array( $uri, $registered, true ) ) {
			return true;
		}
		$request = wp_parse_url( $uri );
		if ( ! is_array( $request ) || 'http' !== ( $request['scheme'] ?? '' ) || ! in_array( strtolower( $request['host'] ?? '' ), array( 'localhost', '127.0.0.1', '[::1]' ), true ) ) {
			return false;
		}
		foreach ( $registered as $candidate ) {
			$parts = wp_parse_url( $candidate );
			if ( ! is_array( $parts ) || 'http' !== ( $parts['scheme'] ?? '' ) ) {
				continue;
			}
			$same = strtolower( $parts['host'] ?? '' ) === strtolower( $request['host'] )
				&& ( $parts['path'] ?? '' ) === ( $request['path'] ?? '' )
				&& ( $parts['query'] ?? '' ) === ( $request['query'] ?? '' )
				&& ! isset( $request['fragment'] )
				&& ! isset( $request['user'] );
			if ( $same ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Write a sign-in problem to the WooCommerce log, source "wholesalex-mcp".
	 *
	 * @param string $message Message for the developer.
	 * @return void
	 */
	private static function log( string $message ): void {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->warning( 'OAuth: ' . $message, array( 'source' => 'wholesalex-mcp' ) );
		}
	}

	/**
	 * Whether a value is an allowed redirect URI string.
	 *
	 * @param mixed $uri Value.
	 * @return bool
	 */
	public static function is_allowed_redirect_value( $uri ): bool {
		return is_string( $uri ) && self::redirect_uri_allowed( $uri );
	}

	/**
	 * Clean an app name for display.
	 *
	 * @param mixed  $name     Name sent by the app.
	 * @param string $fallback Name to use when none was sent.
	 * @return string
	 */
	private static function client_name( $name, string $fallback ): string {
		$name = is_string( $name ) ? trim( sanitize_text_field( $name ) ) : '';
		return '' === $name ? $fallback : mb_substr( $name, 0, 80 );
	}

	/**
	 * Register the authorization server routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		if ( ! self::is_available() ) {
			return;
		}
		// These are public OAuth endpoints (RFC 9728, 8414, 7591, 6749): apps call them
		// before they have a token. Each one validates its own input; none exposes store data.
		$namespace = Server::ROUTE_NAMESPACE;
		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/protected-resource',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_resource_metadata' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/\.well-known/(?P<document>oauth-authorization-server|openid-configuration)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_server_metadata' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'register_client' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/revoke',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'revoke_token' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/token',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'token' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Protected resource metadata (RFC 9728).
	 *
	 * @return array
	 */
	public static function resource_metadata(): array {
		return array(
			'resource'                 => self::resource(),
			'authorization_servers'    => array( self::issuer() ),
			'scopes_supported'         => self::SCOPES,
			'bearer_methods_supported' => array( 'header' ),
			'resource_name'            => 'WholesaleX',
		);
	}

	/**
	 * Authorization server metadata (RFC 8414).
	 *
	 * @return array
	 */
	public static function server_metadata(): array {
		return array(
			'issuer'                                     => self::issuer(),
			'authorization_endpoint'                     => self::authorize_url(),
			'token_endpoint'                             => self::endpoint( 'token' ),
			'registration_endpoint'                      => self::endpoint( 'register' ),
			'revocation_endpoint'                        => self::endpoint( 'revoke' ),
			'revocation_endpoint_auth_methods_supported' => array( 'none', 'client_secret_post', 'client_secret_basic' ),
			'scopes_supported'                           => self::SCOPES,
			'response_types_supported'                   => array( 'code' ),
			'response_modes_supported'                   => array( 'query' ),
			'grant_types_supported'                      => array( 'authorization_code', 'refresh_token' ),
			'token_endpoint_auth_methods_supported'      => array( 'none', 'client_secret_post', 'client_secret_basic' ),
			'code_challenge_methods_supported'           => array( 'S256' ),
			'client_id_metadata_document_supported'      => true,
			'authorization_response_iss_parameter_supported' => true,
		);
	}

	/**
	 * REST callback for the protected resource metadata.
	 *
	 * @return \WP_REST_Response
	 */
	public static function rest_resource_metadata(): \WP_REST_Response {
		return new \WP_REST_Response( self::resource_metadata() );
	}

	/**
	 * REST callback for the authorization server metadata.
	 *
	 * @return \WP_REST_Response
	 */
	public static function rest_server_metadata(): \WP_REST_Response {
		return new \WP_REST_Response( self::server_metadata() );
	}

	/**
	 * Serve the well-known discovery documents and the consent screen.
	 *
	 * Runs early on front-end requests. Requests for any other address are
	 * left alone.
	 *
	 * @return void
	 */
	public static function route_front_request(): void {
		// Cheap string checks first: ordinary page views return here without reading any option.
		$path = self::request_path();
		if ( 0 !== strpos( $path, '/.well-known/' ) && false === strpos( $path, '/' . self::AUTHORIZE_PATH ) ) {
			return;
		}
		if ( ! self::is_available() ) {
			return;
		}
		$resource = untrailingslashit( (string) wp_parse_url( self::resource(), PHP_URL_PATH ) );
		$issuer   = untrailingslashit( (string) wp_parse_url( self::issuer(), PHP_URL_PATH ) );

		if ( '/.well-known/oauth-protected-resource' . $resource === $path ) {
			self::send_discovery( self::resource_metadata() );
		}
		if ( in_array( $path, array( '/.well-known/oauth-authorization-server' . $issuer, '/.well-known/openid-configuration' . $issuer ), true ) ) {
			self::send_discovery( self::server_metadata() );
		}
		if ( untrailingslashit( (string) wp_parse_url( self::authorize_url(), PHP_URL_PATH ) ) === $path ) {
			require_once __DIR__ . '/class-oauth-consent.php';
			OAuth_Consent::handle();
			exit;
		}
	}

	/**
	 * Send a discovery document and stop.
	 *
	 * @param array $document Document.
	 * @return void
	 */
	private static function send_discovery( array $document ): void {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
		header( 'Access-Control-Allow-Headers: MCP-Protocol-Version, Content-Type' );
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'OPTIONS' === $_SERVER['REQUEST_METHOD'] ) {
			status_header( 204 );
			exit;
		}
		header( 'Cache-Control: public, max-age=300' );
		wp_send_json( $document, 200 );
	}

	/**
	 * Path of the current request, without a trailing slash.
	 *
	 * @return string
	 */
	private static function request_path(): string {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only compared with known paths.
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		return untrailingslashit( rawurldecode( $path ) );
	}

	/**
	 * Path of a REST route, worked out without the rewrite system so it also
	 * works when the current user is determined early in the request.
	 *
	 * @param string $route Route, for example "wholesalex/mcp".
	 * @return string
	 */
	private static function rest_path( string $route ): string {
		$home = (string) wp_parse_url( (string) get_option( 'home' ), PHP_URL_PATH );
		return untrailingslashit( $home ) . '/' . rest_get_url_prefix() . '/' . $route;
	}

	/**
	 * Authenticate a request to the MCP endpoint with an access token.
	 *
	 * Runs on `determine_current_user` after cookies and application
	 * passwords. Tokens are ignored on every other address, and when a
	 * `rest_route` parameter would make WordPress serve a different route
	 * from the same address.
	 *
	 * @param int|false $user_id User found so far.
	 * @return int|false
	 */
	public static function authenticate( $user_id ) {
		if ( ! empty( $user_id ) || self::rest_path( Server::ROUTE_NAMESPACE . '/' . Server::ROUTE ) !== self::request_path() ) {
			return $user_id;
		}
		if ( isset( $_GET['rest_route'] ) || isset( $_POST['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- Only checks that the parameter is absent.
			return $user_id;
		}
		if ( ! preg_match( '/^Bearer\s+(\S+)$/i', self::authorization_header(), $match ) ) {
			return $user_id;
		}
		if ( ! self::is_available() ) {
			return $user_id;
		}

		$grant = OAuth_Store::find_by_access( $match[1] );
		$user  = $grant && self::resource_matches( $grant['resource'] ?? null ) ? get_userdata( (int) $grant['user_id'] ) : false;
		if ( ! $user ) {
			self::$invalid_token = true;
			return $user_id;
		}

		OAuth_Store::touch( $grant );
		self::$token_user = true;
		return (int) $user->ID;
	}

	/**
	 * Refuse a REST request signed in with an access token unless it is the
	 * request to the MCP endpoint, whatever route WordPress resolved.
	 *
	 * @param \WP_Error|null|true $result Authentication result so far.
	 * @return \WP_Error|null|true
	 */
	public static function restrict_token( $result ) {
		if ( ! self::$token_user || is_wp_error( $result ) ) {
			return $result;
		}
		$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) && is_string( $GLOBALS['wp']->query_vars['rest_route'] ) ? untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] ) : '';
		if ( '/' . Server::ROUTE_NAMESPACE . '/' . Server::ROUTE === $route ) {
			return $result;
		}
		return new \WP_Error( 'wholesalex_mcp_token_scope', __( 'This access token only works on the WholesaleX MCP endpoint.', 'wholesalex' ), array( 'status' => 403 ) );
	}

	/**
	 * Stop WordPress from reading an app's client ID and secret on the token
	 * endpoint as an application password.
	 *
	 * @param bool $is_api_request Whether application passwords may be used.
	 * @return bool
	 */
	public static function skip_application_passwords( $is_api_request ) {
		$route = Server::ROUTE_NAMESPACE . '/' . self::ROUTE . '/';
		if ( in_array( self::request_path(), array( self::rest_path( $route . 'token' ), self::rest_path( $route . 'register' ), self::rest_path( $route . 'revoke' ) ), true ) ) {
			return false;
		}
		return $is_api_request;
	}

	/**
	 * Tell apps where to sign in when the MCP endpoint refuses a request.
	 *
	 * @param \WP_HTTP_Response $response Response.
	 * @param \WP_REST_Server   $server   REST server.
	 * @param \WP_REST_Request  $request  Request.
	 * @return \WP_HTTP_Response
	 */
	public static function add_challenge( $response, $server, $request ) {
		if ( ! $response instanceof \WP_HTTP_Response || 401 !== $response->get_status() || ! self::is_available() ) {
			return $response;
		}
		if ( '/' . Server::ROUTE_NAMESPACE . '/' . Server::ROUTE !== untrailingslashit( $request->get_route() ) ) {
			return $response;
		}
		$challenge = 'Bearer resource_metadata="' . self::endpoint( 'protected-resource' ) . '", scope="' . implode( ' ', self::SCOPES ) . '"';
		if ( self::$invalid_token ) {
			$challenge .= ', error="invalid_token"';
		}
		$response->header( 'WWW-Authenticate', $challenge );
		return $response;
	}

	/**
	 * Let browser-based MCP clients read the sign-in challenge.
	 *
	 * @param string[] $headers Exposed headers.
	 * @return string[]
	 */
	public static function expose_challenge( $headers ): array {
		$headers   = (array) $headers;
		$headers[] = 'WWW-Authenticate';
		return $headers;
	}

	/**
	 * The raw Authorization header of the current request.
	 *
	 * @return string
	 */
	private static function authorization_header(): string {
		foreach ( array( 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) && is_string( $_SERVER[ $key ] ) ) {
				return trim( wp_unslash( $_SERVER[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Credentials are compared, never output.
			}
		}
		if ( function_exists( 'getallheaders' ) ) {
			foreach ( (array) getallheaders() as $name => $value ) {
				if ( 'authorization' === strtolower( (string) $name ) ) {
					return trim( (string) $value );
				}
			}
		}
		return '';
	}

	/**
	 * Client ID and secret sent with HTTP Basic authentication.
	 *
	 * @return array{0: string, 1: string}
	 */
	private static function basic_credentials(): array {
		$header = self::authorization_header();
		if ( 0 === stripos( $header, 'basic ' ) ) {
			$decoded = base64_decode( substr( $header, 6 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- HTTP Basic credentials.
			if ( false !== $decoded && false !== strpos( $decoded, ':' ) ) {
				list( $id, $secret ) = explode( ':', $decoded, 2 );
				return array( rawurldecode( $id ), rawurldecode( $secret ) );
			}
		}
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && is_string( $_SERVER['PHP_AUTH_USER'] ) ) {
			$secret = isset( $_SERVER['PHP_AUTH_PW'] ) && is_string( $_SERVER['PHP_AUTH_PW'] ) ? $_SERVER['PHP_AUTH_PW'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Credentials are compared, never output.
			return array( (string) wp_unslash( $_SERVER['PHP_AUTH_USER'] ), (string) wp_unslash( $secret ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Credentials are compared, never output.
		}
		return array( '', '' );
	}

	/**
	 * Read one string parameter.
	 *
	 * @param array  $params Parameters.
	 * @param string $name   Name.
	 * @return string
	 */
	public static function param( array $params, string $name ): string {
		return isset( $params[ $name ] ) && is_string( $params[ $name ] ) && strlen( $params[ $name ] ) <= 2000 ? $params[ $name ] : '';
	}

	/**
	 * Validate an authorization request from the consent screen.
	 *
	 * Problems with the client or redirect URI are shown on the screen, since
	 * the app cannot be trusted with the answer. Other problems are sent back
	 * to the app; their error data holds the redirect URI and state.
	 *
	 * @param array $params Request parameters.
	 * @return array|\WP_Error
	 */
	public static function validate_authorization_request( array $params ) {
		$client_id = self::param( $params, 'client_id' );
		if ( '' === $client_id ) {
			return new \WP_Error( 'invalid_request', __( 'This page opens on its own while an AI app connects, so it cannot be used directly. In ChatGPT or Claude, add the server address from WholesaleX > MCP Tools and choose OAuth.', 'wholesalex' ) );
		}
		$client = self::client( $client_id );
		if ( ! $client ) {
			return new \WP_Error( 'invalid_client', __( 'This app is not registered with your store. If you typed a client ID into the app\'s advanced OAuth settings, clear it so the app registers itself. Otherwise remove WholesaleX from the app and add the server address again.', 'wholesalex' ) );
		}

		$redirect = self::param( $params, 'redirect_uri' );
		if ( '' === $redirect && 1 === count( $client['redirect_uris'] ) ) {
			$redirect = $client['redirect_uris'][0];
		}
		if ( ! self::redirect_uri_registered( $redirect, $client['redirect_uris'] ) ) {
			return new \WP_Error( 'invalid_redirect_uri', __( 'The app asked to send you back to an address it did not register, so the sign-in was stopped.', 'wholesalex' ) );
		}

		$back = array(
			'redirect_uri' => $redirect,
			'state'        => self::param( $params, 'state' ),
		);
		if ( 'code' !== self::param( $params, 'response_type' ) ) {
			return new \WP_Error( 'unsupported_response_type', 'Only response_type=code is supported.', $back );
		}
		$challenge = self::param( $params, 'code_challenge' );
		if ( 'S256' !== self::param( $params, 'code_challenge_method' ) || ! preg_match( '/^[A-Za-z0-9\-._~]{43,128}$/', $challenge ) ) {
			return new \WP_Error( 'invalid_request', 'PKCE with code_challenge_method=S256 is required.', $back );
		}
		$resource = self::param( $params, 'resource' );
		if ( '' !== $resource && untrailingslashit( $resource ) !== untrailingslashit( self::resource() ) ) {
			return new \WP_Error( 'invalid_target', 'The resource must be the WholesaleX MCP endpoint.', $back );
		}

		$requested = preg_split( '/\s+/', self::param( $params, 'scope' ), -1, PREG_SPLIT_NO_EMPTY );
		$scope     = array_unique( array_merge( array( self::SCOPES[0] ), array_intersect( (array) $requested, self::SCOPES ) ) );

		return array(
			'client'         => $client,
			'client_id'      => $client['client_id'],
			'redirect_uri'   => $redirect,
			'state'          => $back['state'],
			'code_challenge' => $challenge,
			'scope'          => implode( ' ', $scope ),
		);
	}

	/**
	 * Dynamic client registration (RFC 7591).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function register_client( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! self::is_available() ) {
			return self::oauth_error( 'temporarily_unavailable', 'OAuth requires enabled AI tools, pretty permalinks and HTTPS.', 403 );
		}
		$body = $request->get_json_params();
		$body = is_array( $body ) ? $body : $request->get_body_params();

		$uris = isset( $body['redirect_uris'] ) && is_array( $body['redirect_uris'] ) ? array_values( $body['redirect_uris'] ) : array();
		if ( empty( $uris ) || count( $uris ) > 10 ) {
			return self::oauth_error( 'invalid_redirect_uri', 'Send between one and ten redirect_uris.' );
		}
		foreach ( $uris as $uri ) {
			if ( ! self::is_allowed_redirect_value( $uri ) ) {
				return self::oauth_error( 'invalid_redirect_uri', 'Redirect URIs must use HTTPS on a trusted app host, or http://localhost. Store owners can add hosts with the wholesalex_mcp_oauth_trusted_hosts filter.' );
			}
		}

		$method = isset( $body['token_endpoint_auth_method'] ) && is_string( $body['token_endpoint_auth_method'] ) ? $body['token_endpoint_auth_method'] : 'client_secret_basic';
		if ( ! in_array( $method, array( 'none', 'client_secret_post', 'client_secret_basic' ), true ) ) {
			return self::oauth_error( 'invalid_client_metadata', 'token_endpoint_auth_method must be none, client_secret_post or client_secret_basic.' );
		}
		$grant_types = isset( $body['grant_types'] ) && is_array( $body['grant_types'] ) ? $body['grant_types'] : array( 'authorization_code', 'refresh_token' );
		if ( array_diff( $grant_types, array( 'authorization_code', 'refresh_token' ) ) ) {
			return self::oauth_error( 'invalid_client_metadata', 'Only the authorization_code and refresh_token grant types are supported.' );
		}
		$response_types = isset( $body['response_types'] ) && is_array( $body['response_types'] ) ? $body['response_types'] : array( 'code' );
		if ( array_diff( $response_types, array( 'code' ) ) ) {
			return self::oauth_error( 'invalid_client_metadata', 'Only the code response type is supported.' );
		}

		if ( self::register_limit_reached() ) {
			$response = self::oauth_error( 'temporarily_unavailable', 'Too many registrations from this address. Try again later.', 429 );
			$response->header( 'Retry-After', (string) self::REGISTER_WINDOW );
			return $response;
		}

		$host  = (string) wp_parse_url( $uris[0], PHP_URL_HOST );
		$added = OAuth_Store::add_client( self::client_name( $body['client_name'] ?? '', $host ), $uris, $method );
		if ( null === $added ) {
			return self::oauth_error( 'temporarily_unavailable', 'Too many apps are registered. Try again later.', 503 );
		}
		list( $client, $secret ) = $added;

		$data = array(
			'client_id'                  => $client['client_id'],
			'client_id_issued_at'        => $client['created'],
			'client_name'                => $client['client_name'],
			'redirect_uris'              => $client['redirect_uris'],
			'grant_types'                => array( 'authorization_code', 'refresh_token' ),
			'response_types'             => array( 'code' ),
			'token_endpoint_auth_method' => $method,
			'scope'                      => implode( ' ', self::SCOPES ),
		);
		if ( '' !== $secret ) {
			$data['client_secret']            = $secret;
			$data['client_secret_expires_at'] = 0;
		}

		return self::no_store( new \WP_REST_Response( $data, 201 ) );
	}

	/**
	 * Whether the caller registered too many apps recently.
	 *
	 * Registration is open to anyone (RFC 7591) and every registration rewrites
	 * a stored option, so each address gets a small allowance per window. Only
	 * REMOTE_ADDR is used: forwarding headers can be forged by the caller. Sites
	 * behind a proxy can pass the real address with the
	 * `wholesalex_mcp_oauth_register_ip` filter.
	 *
	 * @return bool
	 */
	private static function register_limit_reached(): bool {
		/**
		 * Filters how many apps one address may register per hour. Return 0 to
		 * turn the limit off.
		 *
		 * @since 3.2.0
		 *
		 * @param int $limit Registrations per address per hour.
		 */
		$limit = (int) apply_filters( 'wholesalex_mcp_oauth_register_limit', self::REGISTER_LIMIT );
		if ( $limit < 1 ) {
			return false;
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		/**
		 * Filters the address the registration limit counts against.
		 *
		 * @since 3.2.0
		 *
		 * @param string $ip Address of the caller.
		 */
		$ip    = (string) apply_filters( 'wholesalex_mcp_oauth_register_ip', $ip );
		$key   = 'wsx_mcp_oauth_reg_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return true;
		}
		set_transient( $key, $count + 1, self::REGISTER_WINDOW );
		return false;
	}

	/**
	 * Token revocation (RFC 7009): an app that is removed by the user can tell
	 * the site to forget its connection.
	 *
	 * The app must identify itself like at the token endpoint, and can only
	 * revoke its own tokens. A token that is unknown, expired or belongs to
	 * another app is answered with success and changes nothing, so the endpoint
	 * cannot be used to find out which tokens exist. Revoking either token ends
	 * the whole connection.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function revoke_token( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! self::is_available() ) {
			return self::oauth_error( 'temporarily_unavailable', 'OAuth requires enabled AI tools, pretty permalinks and HTTPS.', 403 );
		}
		$params = $request->get_body_params();
		$params = empty( $params ) && is_array( $request->get_json_params() ) ? $request->get_json_params() : $params;

		list( $basic_id, $basic_secret ) = self::basic_credentials();
		$client_id                       = '' !== $basic_id ? $basic_id : self::param( $params, 'client_id' );
		$secret                          = '' !== $basic_id ? $basic_secret : self::param( $params, 'client_secret' );
		$client                          = '' === $client_id ? null : self::client( $client_id );
		if ( ! $client ) {
			return self::oauth_error( 'invalid_client', 'Unknown client.', 401 );
		}
		if ( 'none' !== $client['auth_method'] && ( '' === $secret || ! hash_equals( (string) $client['secret_hash'], OAuth_Store::hash( $secret ) ) ) ) {
			return self::oauth_error( 'invalid_client', 'Client authentication failed.', 401 );
		}

		$grant = OAuth_Store::find_by_token( self::param( $params, 'token' ) );
		if ( $grant && $grant['client_id'] === $client['client_id'] ) {
			$removed = OAuth_Store::revoke( (string) $grant['id'] );
			if ( $removed ) {
				Activity_Log::record( 'wholesalex/oauth-revoke', Activity_Log::SUCCESS, (string) $removed['client_name'], '', (int) $removed['user_id'] );
			}
		}

		return self::no_store( new \WP_REST_Response( new \stdClass(), 200 ) );
	}

	/**
	 * Token endpoint: exchanges authorization codes and refresh tokens.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function token( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! self::is_available() ) {
			return self::oauth_error( 'temporarily_unavailable', 'OAuth requires enabled AI tools, pretty permalinks and HTTPS.', 403 );
		}
		$params = $request->get_body_params();
		$params = empty( $params ) && is_array( $request->get_json_params() ) ? $request->get_json_params() : $params;

		list( $basic_id, $basic_secret ) = self::basic_credentials();
		$client_id                       = '' !== $basic_id ? $basic_id : self::param( $params, 'client_id' );
		$secret                          = '' !== $basic_id ? $basic_secret : self::param( $params, 'client_secret' );
		$client                          = '' === $client_id ? null : self::client( $client_id );
		if ( ! $client ) {
			return self::oauth_error( 'invalid_client', 'Unknown client.', 401 );
		}
		if ( 'none' !== $client['auth_method'] && ( '' === $secret || ! hash_equals( (string) $client['secret_hash'], OAuth_Store::hash( $secret ) ) ) ) {
			return self::oauth_error( 'invalid_client', 'Client authentication failed.', 401 );
		}

		$resource = self::param( $params, 'resource' );
		if ( '' !== $resource && untrailingslashit( $resource ) !== untrailingslashit( self::resource() ) ) {
			return self::oauth_error( 'invalid_target', 'The resource must be the WholesaleX MCP endpoint.' );
		}

		$grant_type = self::param( $params, 'grant_type' );
		if ( 'authorization_code' === $grant_type ) {
			$code = OAuth_Store::take_code( self::param( $params, 'code' ) );
			if ( ! $code || $code['client_id'] !== $client['client_id'] || ! self::resource_matches( $code['resource'] ?? null ) ) {
				return self::oauth_error( 'invalid_grant', 'The authorization code is invalid, expired or already used.' );
			}
			$redirect = self::param( $params, 'redirect_uri' );
			if ( '' !== $redirect && $redirect !== $code['redirect_uri'] ) {
				return self::oauth_error( 'invalid_grant', 'redirect_uri does not match the authorization request.' );
			}
			$verifier = self::param( $params, 'code_verifier' );
			$expected = rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- PKCE S256 compares base64url( sha256( verifier ) ), RFC 7636.
			if ( ! preg_match( '/^[A-Za-z0-9\-._~]{43,128}$/', $verifier ) || ! hash_equals( (string) $code['code_challenge'], $expected ) ) {
				return self::oauth_error( 'invalid_grant', 'code_verifier does not match the code challenge.' );
			}
			if ( ! self::user_may_connect( (int) $code['user_id'] ) ) {
				return self::oauth_error( 'invalid_grant', 'The approving user is no longer a store administrator.' );
			}
			$tokens = OAuth_Store::issue( $client, (int) $code['user_id'], (string) $code['scope'], self::resource() );
			return $tokens ? self::token_response( $tokens ) : self::oauth_error( 'invalid_grant', 'The connection could not be saved. Sign in again.' );
		}

		if ( 'refresh_token' === $grant_type ) {
			$refresh = self::param( $params, 'refresh_token' );
			$grant   = OAuth_Store::find_by_refresh( $refresh );
			if ( ! $grant || $grant['client_id'] !== $client['client_id'] ) {
				OAuth_Store::revoke_reused_refresh( $refresh, $client['client_id'] );
				return self::oauth_error( 'invalid_grant', 'The refresh token is invalid, expired or revoked.' );
			}
			if ( ! self::resource_matches( $grant['resource'] ?? null ) ) {
				return self::oauth_error( 'invalid_grant', 'The connection belongs to a different MCP resource. Sign in again.' );
			}
			if ( ! self::user_may_connect( (int) $grant['user_id'] ) ) {
				OAuth_Store::revoke( $grant['id'] );
				return self::oauth_error( 'invalid_grant', 'The approving user is no longer a store administrator.' );
			}
			$tokens = OAuth_Store::issue( $client, (int) $grant['user_id'], (string) $grant['scope'], (string) $grant['resource'], $grant );
			return $tokens ? self::token_response( $tokens ) : self::oauth_error( 'invalid_grant', 'The refresh token is invalid, expired or revoked.' );
		}

		return self::oauth_error( 'unsupported_grant_type', 'Use authorization_code or refresh_token.' );
	}

	/**
	 * Whether a user may approve and keep an AI app connection.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function user_may_connect( int $user_id ): bool {
		$user = get_userdata( $user_id );
		return $user && user_can( $user, Access::CAPABILITY );
	}

	/**
	 * Build a token response.
	 *
	 * @param array $tokens Issued tokens.
	 * @return \WP_REST_Response
	 */
	private static function token_response( array $tokens ): \WP_REST_Response {
		return self::no_store(
			new \WP_REST_Response(
				array(
					'access_token'  => $tokens['access_token'],
					'token_type'    => 'Bearer',
					'expires_in'    => $tokens['expires_in'],
					'refresh_token' => $tokens['refresh_token'],
					'scope'         => $tokens['scope'],
				)
			)
		);
	}

	/**
	 * Build an OAuth error response.
	 *
	 * @param string $code        Error code.
	 * @param string $description Description for the app developer.
	 * @param int    $status      HTTP status.
	 * @return \WP_REST_Response
	 */
	private static function oauth_error( string $code, string $description, int $status = 400 ): \WP_REST_Response {
		return self::no_store(
			new \WP_REST_Response(
				array(
					'error'             => $code,
					'error_description' => $description,
				),
				$status
			)
		);
	}

	/**
	 * Stop caches from storing a credential response.
	 *
	 * @param \WP_REST_Response $response Response.
	 * @return \WP_REST_Response
	 */
	private static function no_store( \WP_REST_Response $response ): \WP_REST_Response {
		$response->header( 'Cache-Control', 'no-store' );
		$response->header( 'Pragma', 'no-cache' );
		return $response;
	}
}
