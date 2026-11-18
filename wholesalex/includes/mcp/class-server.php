<?php
/**
 * The WholesaleX MCP server.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the dedicated WholesaleX server on the MCP Adapter.
 *
 * The server lives at `/wp-json/wholesalex/mcp`. Its transport only accepts
 * signed-in administrators while the AI tools are turned on, and every tool
 * checks the same policy again when it runs.
 *
 * WholesaleX does not bundle or start the MCP Adapter. The server is created
 * only when the MCP Adapter plugin, or another plugin that ships it, runs the
 * `mcp_adapter_init` action.
 */
final class Server {

	/**
	 * Server ID on the MCP Adapter.
	 *
	 * @var string
	 */
	const ID = 'wholesalex';

	/**
	 * REST namespace of the server endpoint.
	 *
	 * @var string
	 */
	const ROUTE_NAMESPACE = 'wholesalex';

	/**
	 * REST route of the server endpoint.
	 *
	 * @var string
	 */
	const ROUTE = 'mcp';

	/**
	 * MCP Adapter HTTP transport class.
	 *
	 * @var string
	 */
	const TRANSPORT = 'WP\\MCP\\Transport\\HttpTransport';

	/**
	 * Plugin file of the MCP Adapter plugin from WordPress.org.
	 *
	 * @var string
	 */
	const ADAPTER_PLUGIN = 'mcp-adapter/mcp-adapter.php';

	/**
	 * Return the endpoint URL AI clients connect to.
	 *
	 * @return string
	 */
	public static function endpoint(): string {
		return rest_url( self::ROUTE_NAMESPACE . '/' . self::ROUTE );
	}

	/**
	 * Whether an MCP Adapter runs on this site, so the server endpoint exists.
	 *
	 * Either the MCP Adapter plugin started it, which loads the adapter class
	 * on every request, or WooCommerce starts its bundled copy on REST requests
	 * because its MCP feature is on. The class is checked without the
	 * autoloader: WooCommerce ships the adapter in its Composer class map, so
	 * autoloading would find it even when nothing starts it and the endpoint
	 * does not exist.
	 *
	 * @return bool
	 */
	public static function adapter_available(): bool {
		$running = class_exists( 'WP\\MCP\\Core\\McpAdapter', false )
			|| ( class_exists( 'Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) && \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'mcp_integration' ) );

		return $running && class_exists( self::TRANSPORT );
	}

	/**
	 * Whether the MCP Adapter plugin is installed but not active.
	 *
	 * @return bool
	 */
	public static function adapter_installed_inactive(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return file_exists( WP_PLUGIN_DIR . '/' . self::ADAPTER_PLUGIN ) && ! is_plugin_active( self::ADAPTER_PLUGIN );
	}

	/**
	 * Create the server. Runs on the `mcp_adapter_init` action.
	 *
	 * @param object $adapter MCP Adapter instance.
	 * @return void
	 */
	public static function create( $adapter ): void {
		if ( ! Access::is_enabled() || ! is_object( $adapter ) || ! method_exists( $adapter, 'create_server' ) || ! class_exists( self::TRANSPORT ) ) {
			return;
		}

		/**
		 * Filters the abilities served as tools by the WholesaleX MCP server.
		 *
		 * @since 3.2.0
		 *
		 * @param string[] $tools Ability names.
		 */
		$tools = apply_filters( 'wholesalex_mcp_server_tools', Abilities::registered_names() );
		$tools = is_array( $tools ) ? array_values( array_unique( array_filter( $tools, 'is_string' ) ) ) : array();
		if ( empty( $tools ) ) {
			return;
		}

		$debug         = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$error_handler = $debug && class_exists( 'WP\\MCP\\Infrastructure\\ErrorHandling\\ErrorLogMcpErrorHandler' )
			? 'WP\\MCP\\Infrastructure\\ErrorHandling\\ErrorLogMcpErrorHandler'
			: null;
		$observability = class_exists( 'WP\\MCP\\Infrastructure\\Observability\\NullMcpObservabilityHandler' )
			? 'WP\\MCP\\Infrastructure\\Observability\\NullMcpObservabilityHandler'
			: null;

		try {
			$adapter->create_server(
				self::ID,
				self::ROUTE_NAMESPACE,
				self::ROUTE,
				__( 'WholesaleX', 'wholesalex' ),
				self::instructions(),
				WHOLESALEX_VER,
				array( self::TRANSPORT ),
				$error_handler,
				$observability,
				$tools,
				array(),
				array(),
				array( __CLASS__, 'transport_permission' )
			);
		} catch ( \Throwable $error ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'The WholesaleX MCP server could not start: ' . $error->getMessage(), array( 'source' => 'wholesalex-mcp' ) );
			}
		}
	}

	/**
	 * Transport permission: signed-in administrators while the tools are on.
	 *
	 * Refused connections from signed-in users, such as a shop manager's
	 * application password, are recorded in the activity log.
	 *
	 * @return true|\WP_Error
	 */
	public static function transport_permission() {
		$allowed = Access::check( Access::READ );
		if ( is_wp_error( $allowed ) && is_user_logged_in() ) {
			Activity_Log::record( 'wholesalex/connect', Activity_Log::DENIED, '', (string) $allowed->get_error_code() );
		}
		return $allowed;
	}

	/**
	 * Instructions sent to AI clients when they connect.
	 *
	 * @return string
	 */
	public static function instructions(): string {
		return __( 'Tools for WholesaleX, the wholesale and B2B extension of this WooCommerce store. Start with wholesalex-get-store-overview. Find product, category, customer, role and checkout IDs with the search and list tools before creating rules. Read settings with wholesalex-get-settings before changing them with wholesalex-update-settings; read-only settings must be changed by the store owner on the Settings screen. Changes apply to the live store: new rules start inactive unless active is true, so tell the store owner what you created, and confirm with them before deleting anything. Prices use the store currency, and dates use YYYY-MM-DD in the store timezone.', 'wholesalex' ) . ' ' . self::rule_guidance() . ' ' . __( 'Use the current tool schemas and settings to determine what MCP can actually do; never claim an unavailable action succeeded or substitute a different rule type.', 'wholesalex' ) . ' ' . Pro_Features::instructions() . ' ' . Support::instructions();
	}

	/**
	 * Default rule selection for dedicated and shared MCP servers.
	 *
	 * @return string
	 */
	public static function rule_guidance(): string {
		return __( 'Use User Roles restrictions by default for ALL role restriction requests. Find the intended roles with wholesalex-list-roles, read each with wholesalex-get-role, then use wholesalex-update-role. With active licensed Pro, use product_visibility to hide products; non_purchasable to block purchases while keeping prices visible; checkout_restriction for matching cart quantity and order value limits or blocking checkout; price_visibility to hide prices and optionally show_request_quote; tax_rule for tax exemptions, countries and classes; replace_cart_with_quote for role-wide quote replacement; restrict_subaccount_creation to allow or block subaccount creation by role; general_excluded_customer_ids for general exemptions. For individual subaccount creation, use the separate Pro wholesalex-get-subaccount-options and wholesalex-create-subaccount tools when present. Resolve the parent customer, collect username, email, full name and requested permissions, and create only with the user\'s authorization. Never request or expose passwords; use the configured password-setup email. Editing and deleting individual subaccounts are unavailable. Do not claim an unavailable operation succeeded. disable_coupons is also available. Pro restriction fields are available only while Pro is active and compatible; check current schemas and related add-on settings. Preserve existing targets, exclusions and unrelated restrictions. If the audience is unspecified, ask which roles should be affected; never assume every role. Do not substitute matching-cart totals for per-product limits or claim unsupported behavior. Use pricing rule tools for wholesale prices and discounts. Dynamic rules are legacy: use dynamic rule tools only when the user explicitly requests dynamic rules or identifies an existing dynamic rule to manage. Do not proactively suggest or create dynamic rules, including as a fallback when a role restriction is unavailable.', 'wholesalex' );
	}
}
