<?php
/**
 * WholesaleX > MCP Tools admin screen.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Lets the store owner turn the AI tools on or off, shows how to connect and
 * lists the apps that signed in with OAuth.
 *
 * The screen and its form handlers require `manage_options` and a nonce.
 */
final class Admin_Page {

	/**
	 * Admin page slug.
	 *
	 * @var string
	 */
	const SLUG = 'wholesalex-ai-tools';

	/**
	 * Form action that saves the access level.
	 *
	 * @var string
	 */
	const SAVE_ACTION = 'wholesalex_mcp_save';

	/**
	 * Form action that clears the activity log.
	 *
	 * @var string
	 */
	const CLEAR_ACTION = 'wholesalex_mcp_clear_activity';

	/**
	 * Number of activity entries shown on the screen. Older ones are in the
	 * WooCommerce log.
	 *
	 * @var int
	 */
	const RECENT_ACTIVITY = 10;

	/**
	 * Form action that disconnects an app that signed in with OAuth.
	 *
	 * @var string
	 */
	const REVOKE_ACTION = 'wholesalex_mcp_oauth_revoke';

	/** Register the admin view and its existing form handlers. */
	public static function init(): void {
		add_action( 'admin_post_' . self::SAVE_ACTION, array( __CLASS__, 'save' ) );
		add_action( 'admin_post_' . self::CLEAR_ACTION, array( __CLASS__, 'clear_activity' ) );
		add_action( 'admin_post_' . self::REVOKE_ACTION, array( __CLASS__, 'revoke_connection' ) );
		add_action( 'wp_ajax_wholesalex_mcp_screen', array( __CLASS__, 'read_screen' ) );
		add_action( 'wp_ajax_' . self::SAVE_ACTION, array( __CLASS__, 'save' ) );
		add_action( 'wp_ajax_' . self::CLEAR_ACTION, array( __CLASS__, 'clear_activity' ) );
		add_action( 'wp_ajax_' . self::REVOKE_ACTION, array( __CLASS__, 'revoke_connection' ) );
		add_filter( 'removable_query_args', array( __CLASS__, 'removable_query_args' ) );
	}

	/**
	 * Remove redirect messages after WordPress has displayed this page.
	 *
	 * @param string[] $args Removable query arguments.
	 * @return string[]
	 */
	public static function removable_query_args( $args ): array {
		return array_merge( (array) $args, array( 'wholesalex-mcp-updated', 'wholesalex-mcp-cleared', 'wholesalex-mcp-disconnected' ) );
	}

	/**
	 * URL of the screen.
	 *
	 * @param array $args Extra query arguments.
	 * @return string
	 */
	public static function url( array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => self::SLUG ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * Save the access level.
	 *
	 * @return void
	 */
	public static function save(): void {
		self::authorize_action( self::SAVE_ACTION );

		$level = isset( $_POST['wholesalex_mcp_access'] ) ? sanitize_key( wp_unslash( $_POST['wholesalex_mcp_access'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- authorize_action() verifies the action-specific nonce before this read.
		$saved = Access::set_level( $level );
		if ( $saved ) {
			Activity_Log::record( 'wholesalex/access-level', Activity_Log::SUCCESS, $level );
		}

		if ( wp_doing_ajax() ) {
			if ( ! $saved ) {
				wp_send_json_error( array( 'message' => __( 'Choose a valid access level.', 'wholesalex' ) ), 400 );
			}
			self::action_response( __( 'AI tools access saved.', 'wholesalex' ) );
		}

		wp_safe_redirect( self::url( array( 'wholesalex-mcp-updated' => $saved ? '1' : '0' ) ) );
		exit;
	}

	/**
	 * Clear the activity log.
	 *
	 * @return void
	 */
	public static function clear_activity(): void {
		self::authorize_action( self::CLEAR_ACTION );
		Activity_Log::clear();
		if ( wp_doing_ajax() ) {
			self::action_response( __( 'Activity cleared.', 'wholesalex' ) );
		}
		wp_safe_redirect( self::url( array( 'wholesalex-mcp-cleared' => '1' ) ) );
		exit;
	}

	/**
	 * Disconnect an app that signed in with OAuth.
	 *
	 * @return void
	 */
	public static function revoke_connection(): void {
		self::authorize_action( self::REVOKE_ACTION );
		$grant = OAuth_Store::revoke( isset( $_POST['grant'] ) ? sanitize_key( wp_unslash( $_POST['grant'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- authorize_action() verifies the action-specific nonce before this read.
		if ( $grant ) {
			Activity_Log::record( 'wholesalex/oauth-disconnect', Activity_Log::SUCCESS, (string) $grant['client_name'] );
		}
		if ( wp_doing_ajax() ) {
			if ( ! $grant ) {
				wp_send_json_error( array( 'message' => __( 'That app was already disconnected.', 'wholesalex' ) ), 404 );
			}
			self::action_response( __( 'App disconnected.', 'wholesalex' ) );
		}
		wp_safe_redirect( self::url( array( 'wholesalex-mcp-disconnected' => $grant ? '1' : '0' ) ) );
		exit;
	}


	/**
	 * Verify permissions and the action-specific nonce for both request transports.
	 *
	 * @param string $action Nonce action.
	 * @return void
	 */
	private static function authorize_action( string $action ): void {
		if ( ! current_user_can( Access::CAPABILITY ) ) {
			if ( wp_doing_ajax() ) {
				wp_send_json_error( array( 'message' => __( 'You are not allowed to change the WholesaleX AI tools.', 'wholesalex' ) ), 403 );
			}
			wp_die( esc_html__( 'You are not allowed to change the WholesaleX AI tools.', 'wholesalex' ), '', array( 'response' => 403 ) );
		}
		if ( wp_doing_ajax() ) {
			if ( ! check_ajax_referer( $action, '_wpnonce', false ) ) {
				wp_send_json_error( array( 'message' => __( 'This page expired. Refresh the page and try again.', 'wholesalex' ) ), 403 );
			}
		} else {
			check_admin_referer( $action );
		}
	}

	/**
	 * Return authoritative presentation data after an asynchronous mutation.
	 *
	 * @param string $message Success notification.
	 * @return void
	 */
	private static function action_response( string $message ): void {
		wp_send_json_success(
			array(
				'screen'  => self::screen_data(),
				'message' => $message,
			)
		);
	}


	/**
	 * When a connection was last used. Connections idle for a day or more also
	 * say how long, so one that an app dropped without telling the site stands out.
	 *
	 * @param int    $time   Unix time of the last use.
	 * @param string $format Date format.
	 * @return string
	 */
	private static function last_used_label( int $time, string $format ): string {
		$date = wp_date( $format, $time );
		if ( time() - $time < DAY_IN_SECONDS ) {
			return $date;
		}
		/* translators: 1: date and time of the last use, 2: how long ago, for example "4 days" */
		return sprintf( __( '%1$s (%2$s ago)', 'wholesalex' ), $date, human_time_diff( $time ) );
	}

	/**
	 * Toast to show after one of the screen's own redirects.
	 *
	 * @return array{type: string, message: string}|null
	 */
	public static function feedback(): ?array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only flags set by our own redirects.
		$updated = isset( $_GET['wholesalex-mcp-updated'] ) ? sanitize_key( wp_unslash( $_GET['wholesalex-mcp-updated'] ) ) : '';
		$cleared = isset( $_GET['wholesalex-mcp-cleared'] );
		$revoked = isset( $_GET['wholesalex-mcp-disconnected'] ) ? sanitize_key( wp_unslash( $_GET['wholesalex-mcp-disconnected'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ( '1' === $revoked ) {
			return array(
				'type'    => 'success',
				'message' => __( 'App disconnected.', 'wholesalex' ),
			);
		}
		if ( '0' === $revoked ) {
			return array(
				'type'    => 'error',
				'message' => __( 'That app was already disconnected.', 'wholesalex' ),
			);
		}
		if ( '1' === $updated ) {
			return array(
				'type'    => 'success',
				'message' => __( 'AI tools access saved.', 'wholesalex' ),
			);
		}
		if ( '0' === $updated ) {
			return array(
				'type'    => 'error',
				'message' => __( 'AI tools access could not be saved.', 'wholesalex' ),
			);
		}
		if ( $cleared ) {
			return array(
				'type'    => 'success',
				'message' => __( 'Activity cleared.', 'wholesalex' ),
			);
		}
		return null;
	}


	/** Render the shared React admin shell for the MCP submenu. */
	public static function render(): void {
		if ( ! current_user_can( Access::CAPABILITY ) ) {
			return;
		}
		do_action( 'wholesalex_render_admin_app' );
	}

	/** Read presentation data only after verifying the administrator and nonce. */
	public static function read_screen(): void {
		if ( ! current_user_can( Access::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Only administrators can use these tools.', 'wholesalex' ) ), 403 );
		}
		check_ajax_referer( 'wholesalex_mcp_screen' );
		wp_send_json_success( self::screen_data() );
	}

	/**
	 * Build the React view model without exposing OAuth secrets or token hashes.
	 *
	 * @return array
	 */
	private static function screen_data(): array {
		MCP::load();
		$level     = Access::level();
		$endpoint  = Server::endpoint();
		$abilities = function_exists( 'wp_register_ability' );
		$adapter   = Server::adapter_available();
		$inactive  = ! $adapter && Server::adapter_installed_inactive();
		$passwords = function_exists( 'wp_is_application_passwords_available' ) && wp_is_application_passwords_available();
		$web       = OAuth::readiness();
		$rows      = array(
			array(
				__( 'WordPress Abilities API', 'wholesalex' ),
				$abilities,
				$abilities ? __( 'Available', 'wholesalex' ) : __( 'Requires WordPress 6.9 or newer', 'wholesalex' ),
			),
			array(
				__( 'MCP Adapter plugin', 'wholesalex' ),
				$adapter,
				$adapter ? __( 'Active', 'wholesalex' ) : ( $inactive ? __( 'Installed but not active. Activate it so AI assistants have a server to connect to.', 'wholesalex' ) : __( 'Install the free MCP Adapter plugin from WordPress.org', 'wholesalex' ) ),
			),
			array(
				__( 'Application passwords', 'wholesalex' ),
				$passwords,
				$passwords ? __( 'Available', 'wholesalex' ) : __( 'Not available. They need HTTPS, and some security plugins or hosts turn them off.', 'wholesalex' ),
			),
			array(
				__( 'Sign-in for ChatGPT, Claude and Gemini', 'wholesalex' ),
				$web[0],
				$web[1],
			),
		);
		$username  = wp_get_current_user()->user_login;
		$desktop   = (string) wp_json_encode(
			array(
				'mcpServers' => array(
					'wholesalex' => array(
						'command' => 'npx',
						'args'    => array( '-y', '@automattic/mcp-wordpress-remote@0.5.4' ),
						'env'     => array(
							'WP_API_URL'      => $endpoint,
							'WP_API_USERNAME' => $username,
							'WP_API_PASSWORD' => 'YOUR-APPLICATION-PASSWORD',
						),
					),
				),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
		$gemini    = (string) wp_json_encode(
			array(
				'mcpServers' => array(
					'wholesalex' => array(
						'httpUrl' => $endpoint,
						'headers' => array( 'Authorization' => 'Basic BASE64-OF-USERNAME:APPLICATION-PASSWORD' ),
					),
				),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
		$header    = "printf '" . $username . ":YOUR-APPLICATION-PASSWORD' | base64";
		$claude    = 'claude mcp add --transport http wholesalex ' . $endpoint . ' --header "Authorization: Basic BASE64-OF-USERNAME:APPLICATION-PASSWORD"';

		$web_ready   = OAuth::readiness();
		$claude_ai   = array(
			__( 'In Claude, open <strong>Customize &gt; Connectors</strong> and click <strong>Add custom connector</strong>. On a Team or Enterprise plan, an Owner adds it in <strong>Organization settings &gt; Connectors &gt; Add &gt; Custom &gt; Web</strong>.', 'wholesalex' ),
			__( 'Enter <strong>WholesaleX</strong> as the name and paste the server address into <strong>MCP server URL</strong>:', 'wholesalex' ),
			__( 'Leave <strong>Advanced settings</strong> empty, click <strong>Add</strong>, then click <strong>Connect</strong>.', 'wholesalex' ),
			__( 'Your store opens in a new window. Log in as an administrator if asked, check the details and click <strong>Allow access</strong>.', 'wholesalex' ),
			__( 'In a chat, click <strong>+</strong>, open <strong>Connectors</strong> and turn on <strong>WholesaleX</strong>. Then ask: "Give me an overview of my WholesaleX store."', 'wholesalex' ),
		);
		$chatgpt     = array(
			__( 'In ChatGPT, open <strong>Plugins</strong>, click <strong>+</strong> and choose <strong>Add custom MCP server</strong>. If you do not see it, your plan or workspace admin has to allow custom MCP servers (developer mode) first.', 'wholesalex' ),
			__( 'Enter <strong>WholesaleX</strong> as the name. Under <strong>Connection</strong>, keep <strong>Server URL</strong> selected and paste the server address:', 'wholesalex' ),
			__( 'Set <strong>Authentication</strong> to <strong>OAuth</strong>. Leave <strong>Advanced OAuth settings</strong> as they are; ChatGPT finds them on its own.', 'wholesalex' ),
			__( 'Tick <strong>I understand and want to continue</strong> and click <strong>Create as a plugin</strong>.', 'wholesalex' ),
			__( 'Your store opens in a new window. Log in as an administrator if asked, check the details and click <strong>Allow access</strong>.', 'wholesalex' ),
			__( 'Start a new chat, pick <strong>WholesaleX</strong> from the <strong>+</strong> menu and ask: "Give me an overview of my WholesaleX store."', 'wholesalex' ),
		);
		$gemini_web  = array(
			__( 'In Gemini, open <strong>Settings &gt; Connected Apps</strong> and choose <strong>Custom</strong>.', 'wholesalex' ),
			__( 'Paste this address into <strong>MCP Server URL</strong>:', 'wholesalex' ),
			__( 'Leave <strong>Client ID</strong> and <strong>Client secret</strong> empty so Gemini can register automatically, then click <strong>Next</strong>.', 'wholesalex' ),
			__( 'Sign in to your store as a WordPress administrator, review the request and click <strong>Allow access</strong>. Your Google account and WordPress administrator account can use different email addresses.', 'wholesalex' ),
			__( 'Return to Gemini and select <strong>WholesaleX</strong> in a chat. Ask: "Give me an overview of my WholesaleX store."', 'wholesalex' ),
		);
		$notes       = array(
			array( 'dashicons-shield', __( 'Only administrators can use these tools. Shop managers and marketplace vendors cannot, even when they can open the WholesaleX menu.', 'wholesalex' ) ),
			array( 'dashicons-update', __( 'Changes apply to your live store. New rules start inactive unless the assistant turns them on. Try new prompts on a staging site first and keep backups.', 'wholesalex' ) ),
			array( 'dashicons-lock', __( 'Revoke an application password in Users > Profile to disconnect an assistant at once, or set access to Off.', 'wholesalex' ) ),
			array( 'dashicons-privacy', __( 'Data the assistant reads, such as rule names and customer names, goes to the AI service you connect; your assistant requests the data. The only outside request WholesaleX makes is during an OAuth sign-in, when it reads the app\'s public sign-in details from chatgpt.com or claude.ai. No store data is sent.', 'wholesalex' ) ),
		);
		$format      = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$connections = array();
		$grants      = OAuth_Store::grants();
		uasort(
			$grants,
			static function ( $a, $b ) {
				return (int) $b['last_used'] <=> (int) $a['last_used'];
			}
		);
		foreach ( $grants as $grant ) {
			$user          = get_userdata( (int) $grant['user_id'] );
			$connections[] = array(
				'id'       => (string) $grant['id'],
				'name'     => (string) $grant['client_name'],
				'host'     => (string) ( $grant['redirect_host'] ?? '' ),
				'user'     => $user ? $user->display_name : '#' . (int) $grant['user_id'],
				'created'  => wp_date( $format, (int) $grant['created'] ),
				'lastUsed' => self::last_used_label( (int) $grant['last_used'], $format ),
			);
		}
		$tools = array();
		foreach ( Abilities::definitions() as $name => $definition ) {
			$tools[] = array(
				'name'      => str_replace( '/', '-', $name ),
				'label'     => $definition['label'],
				'operation' => $definition['operation'],
				'available' => Access::level_allows( $definition['operation'], $level ),
			);
		}
		$outcomes = array(
			Activity_Log::SUCCESS => array( 'is-on', __( 'Done', 'wholesalex' ) ),
			Activity_Log::ERROR   => array( 'is-warn', __( 'Failed', 'wholesalex' ) ),
			Activity_Log::DENIED  => array( 'is-off', __( 'Refused', 'wholesalex' ) ),
		);
		$activity = array();
		foreach ( Activity_Log::entries( self::RECENT_ACTIVITY ) as $entry ) {
			$user       = get_userdata( (int) ( $entry['user_id'] ?? 0 ) );
			$activity[] = array(
				'time'    => wp_date( $format, (int) ( $entry['time'] ?? 0 ) ),
				'user'    => $user ? $user->display_name : '#' . (int) ( $entry['user_id'] ?? 0 ),
				'tool'    => str_replace( '/', '-', (string) ( $entry['tool'] ?? '' ) ),
				'object'  => (string) ( $entry['object'] ?? '' ),
				'outcome' => $outcomes[ $entry['outcome'] ?? '' ] ?? array( 'is-off', (string) ( $entry['outcome'] ?? '' ) ),
				'code'    => (string) ( $entry['code'] ?? '' ),
			);
		}
		$actions = array();
		foreach ( array(
			'save'   => self::SAVE_ACTION,
			'clear'  => self::CLEAR_ACTION,
			'revoke' => self::REVOKE_ACTION,
		) as $key => $action ) {
			$actions[ $key ] = array(
				'action' => $action,
				'nonce'  => wp_create_nonce( $action ),
			);
		}
		$adapter_action = null;
		if ( $inactive && current_user_can( 'activate_plugins' ) ) {
			$adapter_action = array(
				'label' => __( 'Activate MCP Adapter', 'wholesalex' ),
				'url'   => admin_url( 'plugins.php?plugin_status=inactive&s=mcp-adapter' ),
			);
		} elseif ( ! $adapter && ! $inactive && current_user_can( 'install_plugins' ) ) {
			$adapter_action = array(
				'label' => __( 'Find MCP Adapter', 'wholesalex' ),
				'url'   => admin_url( 'plugin-install.php?s=mcp-adapter&tab=search&type=term' ),
			);
		}
		return array(
			'level'               => $level,
			'levels'              => Access::level_labels(),
			'endpoint'            => $endpoint,
			'status'              => $rows,
			'adapterAction'       => $adapter_action,
			'ready'               => $web_ready,
			'localSetupAvailable' => $adapter && Access::is_enabled(),
			'guides'              => array(
				'claude'  => $claude_ai,
				'chatgpt' => $chatgpt,
				'gemini'  => $gemini_web,
			),
			'snippets'            => array(
				'desktop' => $desktop,
				'header'  => $header,
				'claude'  => $claude,
				'gemini'  => $gemini,
			),
			'profileUrl'          => admin_url( 'profile.php#application-passwords-section' ),
			'postUrl'             => admin_url( 'admin-post.php' ),
			'actions'             => $actions,
			'connections'         => $connections,
			'tools'               => $tools,
			'activity'            => $activity,
			'logUrl'              => function_exists( 'wc_get_logger' ) ? admin_url( 'admin.php?page=wc-status&tab=logs&source=wholesalex-mcp' ) : '',
			'notes'               => $notes,
		);
	}
}
