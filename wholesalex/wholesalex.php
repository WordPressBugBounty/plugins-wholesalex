<?php
/**
 * WholesaleX
 *
 * @link    https://www.wpxpo.com/
 * @since   1.0.0
 * @package WholesaleX
 *
 * Plugin Name:             WholesaleX – Wholesale Pricing & B2B Solution
 * Plugin URI:              https://getwholesalex.com/
 * Description:             Wholesale pricing, B2B customer roles, registration forms and private-store controls for WooCommerce.
 * Version:                 3.1.6
 * Author:                  Wholesale Team
 * Author URI:              https://getwholesalex.com/
 * License:                 GPLv3
 * License URI:             http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:             wholesalex
 * Domain Path:             /languages
 * Requires Plugins:        woocommerce
 * Requires at least:       6.8
 * Requires PHP:            7.4
 * WC requires at least:    4.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Plugin Defined.
define( 'WHOLESALEX_VER', '3.1.6' );
// Public host protocol; feature results still require actual registered providers.
define( 'WHOLESALEX_PROVIDER_API', 1 );
define( 'WHOLESALEX_URL', plugin_dir_url( __FILE__ ) );
define( 'WHOLESALEX_BASE', plugin_basename( __FILE__ ) );
define( 'WHOLESALEX_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function wholesalex_is_woocommerce_active() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return is_plugin_active( 'woocommerce/woocommerce.php' ) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' );
}

/**
 * Declare compatibility with WooCommerce HPOS and checkout blocks.
 *
 * @since 1.5.0
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

if ( ! function_exists( 'wholesalex' ) ) {
	/**
	 * Common Function.
	 */
	function wholesalex() {
		include_once WHOLESALEX_PATH . 'includes/Functions.php';
		return new \WHOLESALEX\Functions();
	}
}

/**
 * Register provider compatibility before the main plugin initialization.
 */
function wholesalex_register_compatibility() {
	require_once WHOLESALEX_PATH . 'includes/compatibility/class-provider-contract.php';
	\WHOLESALEX\Compatibility\Provider_Contract::protect();
	add_filter( 'woocommerce_get_variation_prices_hash', array( '\WHOLESALEX\Compatibility\Provider_Contract', 'price_cache_context' ) );
}
add_action( 'plugins_loaded', 'wholesalex_register_compatibility', 1 );

/**
 * Begins Execution of the Plugin.
 */
function wholesalex_run() {
	static $initialized = false;
	if ( $initialized ) {
		return;
	}
	$initialized = true;
	// Migrate active installations on updates, before any defaults are created.
	wholesalex()->get_onboarding_status();
	add_filter( 'sanitize_option_wholesalex_settings', array( '\WHOLESALEX\Functions', 'normalize_store_mode' ) );

	require_once WHOLESALEX_PATH . 'includes/class-registration-page.php';
	add_action( 'rest_api_init', array( '\WHOLESALEX\Registration_Page', 'register_routes' ) );

	require_once WHOLESALEX_PATH . 'includes/class-wholesalex-scripts.php';

	if ( function_exists( 'WC' ) ) {
		include_once WHOLESALEX_PATH . 'includes/class-wholesalex-initialization.php';
		new WholesaleX_Initialization();
	}
}

add_action( 'plugins_loaded', 'wholesalex_run', 20 );

/**
 * The code that runs during plugin activation.
 */
function wholesalex_activate_action() {
	if ( ! wholesalex_is_woocommerce_active() ) {
		deactivate_plugins( WHOLESALEX_BASE );
		wp_die(
			esc_html__( 'WholesaleX requires WooCommerce to be installed and active. Please activate WooCommerce first.', 'wholesalex' ),
			esc_html__( 'Plugin dependency check failed', 'wholesalex' ),
			array( 'back_link' => true )
		);
	}

	require_once WHOLESALEX_PATH . 'includes/class-wholesalex-activator.php';
	new \WHOLESALEX\Activator();
}

/**
 * The code that runs during plugin deactivation.
 */
function wholesalex_deactivate_action() {
	require_once WHOLESALEX_PATH . 'includes/class-wholesalex-deactivator.php';
	WholesaleX_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'wholesalex_activate_action' );
register_deactivation_hook( __FILE__, 'wholesalex_deactivate_action' );
