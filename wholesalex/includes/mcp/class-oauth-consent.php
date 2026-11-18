<?php
/**
 * Consent screen for the WholesaleX AI tools OAuth sign-in.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Asks a store administrator to approve an AI app such as ChatGPT or Claude.
 *
 * Visitors who are not signed in are sent to the WordPress login first. The
 * screen names the app, the address it returns to, the account and the
 * current access level, and needs a nonce for the decision. It cannot be
 * shown inside a frame.
 */
final class OAuth_Consent {

	/**
	 * Authorization request parameters carried through login and the form.
	 *
	 * @var string[]
	 */
	const PARAMS = array( 'response_type', 'client_id', 'redirect_uri', 'state', 'code_challenge', 'code_challenge_method', 'scope', 'resource' );

	/**
	 * Handle a request to the consent screen.
	 *
	 * @return void
	 */
	public static function handle(): void {
		nocache_headers();
		header( 'X-Frame-Options: DENY' );
		header( "Content-Security-Policy: frame-ancestors 'none'" );
		header( 'Referrer-Policy: no-referrer' );

		// phpcs:disable WordPress.Security.NonceVerification -- The decision is checked with a nonce in decide(); reading the request itself changes nothing.
		$is_post = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'];
		$params  = $is_post ? wp_unslash( $_POST ) : wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated in OAuth::validate_authorization_request().
		// phpcs:enable WordPress.Security.NonceVerification
		$params = array_intersect_key( (array) $params, array_flip( array_merge( self::PARAMS, array( 'decision', '_wpnonce' ) ) ) );

		$request = OAuth::validate_authorization_request( $params );
		if ( is_wp_error( $request ) ) {
			$data = $request->get_error_data();
			if ( is_array( $data ) && ! empty( $data['redirect_uri'] ) ) {
				self::send_back(
					$data['redirect_uri'],
					array(
						'error'             => $request->get_error_code(),
						'error_description' => $request->get_error_message(),
						'state'             => $data['state'],
					)
				);
			}
			self::render_error( $request->get_error_message() );
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( self::request_url( $params ) ) );
			exit;
		}

		if ( $is_post ) {
			self::decide( $request, $params );
		}
		self::render_consent( $request, $params );
	}

	/**
	 * Address of this screen with the authorization request, for the login redirect.
	 *
	 * @param array $params Request parameters.
	 * @return string
	 */
	private static function request_url( array $params ): string {
		$query = array();
		foreach ( self::PARAMS as $name ) {
			$value = OAuth::param( $params, $name );
			if ( '' !== $value ) {
				$query[ $name ] = rawurlencode( $value );
			}
		}
		return add_query_arg( $query, OAuth::authorize_url() );
	}

	/**
	 * Nonce action, tied to this app, return address and PKCE challenge.
	 *
	 * @param array $request Validated request.
	 * @return string
	 */
	private static function nonce_action( array $request ): string {
		return 'wholesalex_oauth_authorize|' . $request['client_id'] . '|' . $request['redirect_uri'] . '|' . $request['code_challenge'];
	}

	/**
	 * Apply the administrator's decision.
	 *
	 * @param array $request Validated request.
	 * @param array $params  Request parameters.
	 * @return void
	 */
	private static function decide( array $request, array $params ): void {
		if ( ! wp_verify_nonce( OAuth::param( $params, '_wpnonce' ), self::nonce_action( $request ) ) ) {
			self::render_error( __( 'This sign-in page expired. Go back to the app and connect again.', 'wholesalex' ) );
		}

		if ( 'allow' !== OAuth::param( $params, 'decision' ) ) {
			self::send_back(
				$request['redirect_uri'],
				array(
					'error'             => 'access_denied',
					'error_description' => 'The store administrator declined the connection.',
					'state'             => $request['state'],
				)
			);
		}

		if ( ! OAuth::user_may_connect( get_current_user_id() ) ) {
			self::render_error( __( 'Only store administrators can connect AI apps to WholesaleX.', 'wholesalex' ) );
		}

		$code = OAuth_Store::random( 'wsxac_' );
		OAuth_Store::save_code(
			$code,
			array(
				'client_id'      => $request['client_id'],
				'redirect_uri'   => $request['redirect_uri'],
				'code_challenge' => $request['code_challenge'],
				'scope'          => $request['scope'],
				'user_id'        => get_current_user_id(),
			)
		);
		Activity_Log::record( 'wholesalex/oauth-connect', Activity_Log::SUCCESS, $request['client']['client_name'] );

		self::send_back(
			$request['redirect_uri'],
			array(
				'code'  => $code,
				'state' => $request['state'],
				'iss'   => OAuth::issuer(),
			)
		);
	}

	/**
	 * Return to the app with a result and stop.
	 *
	 * @param string $redirect_uri Registered redirect URI.
	 * @param array  $args         Query arguments.
	 * @return void
	 */
	private static function send_back( string $redirect_uri, array $args ): void {
		$args = array_filter(
			$args,
			static function ( $value ) {
				return '' !== $value && null !== $value;
			}
		);
		wp_redirect( add_query_arg( array_map( 'rawurlencode', $args ), $redirect_uri ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- The app registered this address and it is on a trusted host, so it may leave this site.
		exit;
	}

	/**
	 * Render the consent screen and stop.
	 *
	 * @param array $request Validated request.
	 * @param array $params  Request parameters.
	 * @return void
	 */
	private static function render_consent( array $request, array $params ): void {
		$user     = wp_get_current_user();
		$app      = $request['client']['client_name'];
		$host     = (string) wp_parse_url( $request['redirect_uri'], PHP_URL_HOST );
		$site     = get_bloginfo( 'name' );
		$labels   = Access::level_labels();
		$level    = $labels[ Access::level() ];
		$is_admin = OAuth::user_may_connect( (int) $user->ID );
		// Apps identified by a metadata document on a trusted host have a verified name; self-registered apps chose theirs.
		$verified = 0 === strpos( (string) $request['client_id'], 'https://' );
		$switch   = wp_logout_url( wp_login_url( self::request_url( $params ) ) );

		$abilities = array(
			Access::READ   => __( 'Look up roles, rules, the registration form, products and customers', 'wholesalex' ),
			Access::WRITE  => __( 'Create and edit roles, pricing rules, dynamic rules and registration fields', 'wholesalex' ),
			Access::DELETE => __( 'Delete roles, rules and registration fields', 'wholesalex' ),
		);

		ob_start();
		?>
		<h1 class="wsx-title">
			<?php
			/* translators: 1: AI app name, for example ChatGPT, 2: store name */
			echo esc_html( sprintf( __( '%1$s wants to connect to %2$s', 'wholesalex' ), $app, $site ) );
			?>
		</h1>
		<p class="wsx-lead">
			<?php
			/* translators: %s: address the app returns to, for example chatgpt.com */
			echo esc_html( sprintf( __( 'It will manage WholesaleX for you and return you to %s.', 'wholesalex' ), $host ) );
			?>
		</p>

		<div class="wsx-account">
			<span class="wsx-avatar" aria-hidden="true"><?php echo esc_html( self::initial( $user->display_name ) ); ?></span>
			<span class="wsx-account__text">
				<strong><?php echo esc_html( $user->display_name ); ?></strong>
				<span><?php echo esc_html( $user->user_email ); ?></span>
			</span>
			<a class="wsx-link" href="<?php echo esc_url( $switch ); ?>"><?php esc_html_e( 'Switch account', 'wholesalex' ); ?></a>
		</div>

		<?php if ( $is_admin ) : ?>
			<div class="wsx-access">
				<p class="wsx-access__label"><?php esc_html_e( 'What it can do', 'wholesalex' ); ?></p>
				<p class="wsx-access__level"><?php echo esc_html( $level[0] ); ?></p>
				<ul class="wsx-access__list">
					<?php foreach ( $abilities as $operation => $ability ) : ?>
						<?php $allowed = Access::level_allows( $operation ); ?>
						<li class="<?php echo esc_attr( $allowed ? 'is-on' : 'is-off' ); ?>">
							<span class="screen-reader-text"><?php echo $allowed ? esc_html__( 'Allowed:', 'wholesalex' ) : esc_html__( 'Not allowed:', 'wholesalex' ); ?></span>
							<?php echo esc_html( $ability ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php if ( ! $verified ) : ?>
				<p class="wsx-note">
					<?php
					/* translators: %s: address the app returns to, for example chatgpt.com */
					echo esc_html( sprintf( __( 'The app chose its own name, so it is not verified. Only continue if you opened this from an app you trust and you recognise %s.', 'wholesalex' ), $host ) );
					?>
				</p>
			<?php endif; ?>
			<p class="wsx-note"><?php esc_html_e( 'Changes apply to your live store. Only allow apps you trust and opened yourself.', 'wholesalex' ); ?></p>
		<?php else : ?>
			<p class="wsx-alert"><?php esc_html_e( 'Only store administrators can connect AI apps to WholesaleX. Switch to an administrator account to continue.', 'wholesalex' ); ?></p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( OAuth::authorize_url() ); ?>" class="wsx-actions">
			<?php foreach ( self::PARAMS as $name ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( OAuth::param( $params, $name ) ); ?>" />
			<?php endforeach; ?>
			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( self::nonce_action( $request ) ) ); ?>" />
			<button type="submit" name="decision" value="deny" class="wsx-button wsx-button--ghost"><?php esc_html_e( 'Cancel', 'wholesalex' ); ?></button>
			<?php if ( $is_admin ) : ?>
				<button type="submit" name="decision" value="allow" class="wsx-button"><?php esc_html_e( 'Allow access', 'wholesalex' ); ?></button>
			<?php endif; ?>
		</form>

		<p class="wsx-footer"><?php esc_html_e( 'You can disconnect this app at any time in WholesaleX > MCP Tools.', 'wholesalex' ); ?></p>
		<?php
		/* translators: %s: AI app name */
		self::render_page( sprintf( __( 'Connect %s to WholesaleX', 'wholesalex' ), $app ), (string) ob_get_clean() );
	}

	/**
	 * First letter of a name, for the avatar. WordPress does not polyfill
	 * mb_strtoupper(), so hosts without mbstring fall back to strtoupper().
	 *
	 * @param string $name Display name.
	 * @return string
	 */
	private static function initial( string $name ): string {
		$letter = mb_substr( $name, 0, 1 );
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $letter ) : strtoupper( $letter );
	}

	/**
	 * Render an error and stop.
	 *
	 * @param string $message Message for the store owner.
	 * @return void
	 */
	private static function render_error( string $message ): void {
		ob_start();
		?>
		<h1 class="wsx-title"><?php esc_html_e( 'The app could not connect', 'wholesalex' ); ?></h1>
		<p class="wsx-alert"><?php echo esc_html( $message ); ?></p>
		<p class="wsx-footer"><a class="wsx-link" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to the store', 'wholesalex' ); ?></a></p>
		<?php
		self::render_page( __( 'The app could not connect', 'wholesalex' ), (string) ob_get_clean(), 400 );
	}

	/**
	 * Print a complete page and stop.
	 *
	 * @param string $title  Page title.
	 * @param string $body   Escaped body markup.
	 * @param int    $status HTTP status.
	 * @return void
	 */
	private static function render_page( string $title, string $body, int $status = 200 ): void {
		wp_register_style( 'wholesalex-oauth-consent', plugin_dir_url( __FILE__ ) . 'assets/oauth-consent.css', array(), WHOLESALEX_VER );
		status_header( $status );
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php echo esc_attr( get_option( 'blog_charset' ) ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex, nofollow" />
	<meta name="referrer" content="no-referrer" />
	<title><?php echo esc_html( $title ); ?></title>
		<?php wp_print_styles( 'wholesalex-oauth-consent' ); ?>
</head>
<body>
	<main class="wsx-card">
		<img class="wsx-logo" src="<?php echo esc_url( WHOLESALEX_URL . 'assets/icons/wholesalex-logo.svg' ); ?>" alt="WholesaleX" />
		<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped output above. ?>
	</main>
</body>
</html>
		<?php
		exit;
	}
}
