<?php
/**
 * Bootstrap for the WholesaleX AI tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-access.php';
require_once __DIR__ . '/class-activity-log.php';
require_once __DIR__ . '/class-oauth.php';

/**
 * Connects WholesaleX to the WordPress Abilities API and the MCP Adapter.
 *
 * Only the access policy and the OAuth sign-in hooks load on every request.
 * The tool code loads when WordPress builds its abilities registry or the MCP
 * Adapter starts, and only while the store owner has turned the AI tools on.
 */
final class MCP {

	/**
	 * Register the hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'mcp_adapter_init', array( __CLASS__, 'create_server' ) );
		OAuth::init();

		if ( is_admin() ) {
			require_once __DIR__ . '/class-admin-page.php';
			Admin_Page::init();
		}
	}

	/**
	 * Load the tool code.
	 *
	 * @return void
	 */
	public static function load(): void {
		$root = dirname( __DIR__ );
		require_once $root . '/dynamic-rules/class-dynamic-rule-record.php';
		require_once $root . '/dynamic-rules/class-dynamic-rules-data-provider.php';
		require_once $root . '/dynamic-rules/class-dynamic-rules-service.php';
		require_once $root . '/wholesale-pricing/class-wholesale-pricing-service.php';
		require_once $root . '/registration/class-registration-form-service.php';
		require_once $root . '/roles/class-role-service.php';
		require_once $root . '/settings/class-settings-service.php';

		require_once __DIR__ . '/class-schema.php';
		require_once __DIR__ . '/class-lookup.php';
		require_once __DIR__ . '/class-fields.php';
		require_once __DIR__ . '/class-rule-targets.php';
		require_once __DIR__ . '/class-dynamic-rule-types.php';
		require_once __DIR__ . '/class-pricing-rule-types.php';
		require_once __DIR__ . '/class-pro-features.php';
		require_once __DIR__ . '/class-support.php';
		add_filter( 'mcp_adapter_pre_tool_call', array( Pro_Features::class, 'filter_tool_input' ), 10, 2 );
		add_filter( 'wp_ability_validate_input', array( Pro_Features::class, 'filter_validation' ), 10, 3 );
		require_once __DIR__ . '/abilities/class-store-abilities.php';
		require_once __DIR__ . '/abilities/class-role-abilities.php';
		require_once __DIR__ . '/abilities/class-pricing-rule-abilities.php';
		require_once __DIR__ . '/abilities/class-dynamic-rule-abilities.php';
		require_once __DIR__ . '/abilities/class-registration-abilities.php';
		require_once __DIR__ . '/abilities/class-lookup-abilities.php';
		require_once __DIR__ . '/class-settings-catalog.php';
		require_once __DIR__ . '/abilities/class-settings-abilities.php';
		require_once __DIR__ . '/class-abilities.php';
		require_once __DIR__ . '/class-server.php';
	}

	/**
	 * Register the ability category.
	 *
	 * @return void
	 */
	public static function register_category(): void {
		if ( ! Access::is_enabled() ) {
			return;
		}
		self::load();
		Abilities::register_category();
	}

	/**
	 * Register the abilities.
	 *
	 * @return void
	 */
	public static function register_abilities(): void {
		if ( ! Access::is_enabled() ) {
			return;
		}
		self::load();
		Abilities::register();
	}

	/**
	 * Create the WholesaleX MCP server.
	 *
	 * @param object $adapter MCP Adapter instance.
	 * @return void
	 */
	public static function create_server( $adapter ): void {
		if ( ! Access::is_enabled() ) {
			return;
		}
		self::load();
		Server::create( $adapter );
	}
}
