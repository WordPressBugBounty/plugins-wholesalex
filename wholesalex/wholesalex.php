<?php
/**
 * WholesaleX
 *
 * @link    https://www.wpxpo.com/
 * @since   1.0.0
 * @package WholesaleX
 *
 * Plugin Name:             WholesaleX – All-in-One B2B Solution with Wholesale Pricing
 * Plugin URI:              https://getwholesalex.com/
 * Description:             The WholesaleX plugin is a brand-new, highly-promising WooCommerce B2B solution to set up a conversion-focused B2B store for selling wholesale products. It offers everything required to operate an effective B2B store.
 * Version:                 3.1.2
 * Author:                  Wholesale Team
 * Author URI:              https://getwholesalex.com/
 * License:                 GPLv3
 * License URI:             http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:             wholesalex
 * Domain Path:             /languages
 * Requires Plugins:        woocommerce
 * WC requires at least:    4.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Plugin Defined.
define( 'WHOLESALEX_VER', '3.1.2' );
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
