<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WordPress.Files.FileName.NotHyphenatedLowercase -- Preserve existing include paths and template overrides.

/**
 * Addons Page
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Setting Class
 */
class Addons {

	/**
	 * Setting Constructor
	 */
	public function __construct() {
		add_filter( 'wholesalex_addons_config', array( $this, 'addons_config' ), 1 );

		add_action( 'rest_api_init', array( $this, 'register_addons_restapi' ) );
	}

	/**
	 * Register addon restapi route
	 *
	 * @return void
	 */
	public function register_addons_restapi() {
		register_rest_route(
			'wholesalex/v1',
			'/addons/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'addon_restapi_callback' ),
					'permission_callback' => function () {
						return current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) )
							&& current_user_can( 'install_plugins' )
							&& current_user_can( 'activate_plugins' );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Addon RestAPI Callback
	 *
	 * @param \WP_REST_Request $server Full details about the request.
	 * @return array
	 */
	public function addon_restapi_callback( $server ) {
		$post = $server->get_params();
		if ( ! isset( $post['nonce'] ) || ! is_string( $post['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $post['nonce'] ) ), 'wholesalex-registration' ) ) {
			return array(
				'status' => false,
				'data'   => array(),
			);
		}

		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		$response = array(
			'status' => false,
			'data'   => array(),
		);

		switch ( $type ) {
			case 'get':
				$response['status'] = true;
				$response['data']   = $this->get_addons();
				break;
			case 'post':
				$request_for = isset( $post['request_for'] ) ? sanitize_text_field( $post['request_for'] ) : '';
				$addon       = isset( $post['addon'] ) && is_string( $post['addon'] ) ? sanitize_key( $post['addon'] ) : '';
				$addons      = $this->get_addons();

				if ( 'install_plugin' === $request_for || in_array( $addon, array( 'wsx_addon_dokan_integration', 'wsx_addon_wcfm_integration', 'wsx_addon_migration_integration' ), true ) ) {
					$response['data'] = __( 'Plugin installation and activation are no longer available here. Use the WordPress Plugins screen.', 'wholesalex' );
					return $response;
				}

				$addon_name  = isset( $addons[ $addon ] ) ? $addon : '';
				$addon_value = isset( $post['status'] ) && is_string( $post['status'] ) ? sanitize_key( $post['status'] ) : '';
				if ( ! current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) ) || ! $addon_name || ! in_array( $addon_value, array( 'yes', 'no' ), true ) ) {
					$response['data'] = __( 'Update Failed!', 'wholesalex' );
					return $response;
				}

				// $addon_name is validated above against the known addons config, so it is safe to use in the dynamic hook names below.
				do_action( 'wholesalex_' . $addon_name . '_before_status_update', $addon_value );
				$error = apply_filters( 'wholesalex_' . $addon_name . '_error', '', $addon_value );
				if ( '' === $error ) {
					$addon_data                                    = wholesalex()->get_setting();
					$addon_data[ $addon_name ]                     = $addon_value;
					$GLOBALS['wholesalex_settings'][ $addon_name ] = $addon_value;
					update_option( 'wholesalex_settings', $addon_data );
					do_action( 'wholesalex_' . $addon_name . '_after_status_update', $addon_value );
					$response['status'] = true;
					$response['data']   = __( 'Successfully Updated!', 'wholesalex' );
				} else {
					$response['status'] = false;
					$response['data']   = __( 'Update Failed!', 'wholesalex' );
				}
				break;

			default:
				break;
		}

		return $response;
	}

	/**
	 * Get All Addons
	 *
	 * @return array
	 */
	public function get_addons() {
		$addons_data = apply_filters( 'wholesalex_addons_config', array() );

		return $addons_data;
	}

	/**
	 * Check if Dokan Plugin is Active
	 *
	 * @param string $plugin_name Plugin Name.
	 * @return bool
	 */
	public function check_required_plugins( $plugin_name ) {
		// Ensure the `is_plugin_active` function is available.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Define plugin paths for each supported plugin.
		$plugin_paths = array(
			'dokan'     => array(
				'dokan-lite/dokan.php',
			),
			'wcfm'      => array(
				'wc-frontend-manager/wc_frontend_manager.php',
			),
			'migration' => array(
				'wholesalex-migration-tool/wholesalex-migration-tool.php',
			),
		);

		// Check if the plugin name exists in the paths array.
		if ( ! isset( $plugin_paths[ $plugin_name ] ) ) {
			return false;
		}

		// Loop through plugin paths for the requested plugin and check if any are active.
		foreach ( $plugin_paths[ $plugin_name ] as $plugin_path ) {
			if ( is_plugin_active( $plugin_path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Pro Addons Config
	 *
	 * @param object $config Addon Configuration.
	 * @return object $config .
	 * @since 1.0.0
	 * @since 1.0.4 Add Bulk Order.
	 */
	public function addons_config( $config ) {
		$plugin_install_page = self_admin_url( 'plugin-install.php' );
		$plugins_page        = self_admin_url( 'plugins.php' );
		$can_install         = current_user_can( 'install_plugins' );
		$can_activate        = current_user_can( 'activate_plugins' );
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed_plugins = get_plugins();

		$config['wsx_addon_bulkorder'] = array(
			'name'                => __( 'Bulk Order Form', 'wholesalex' ),
			'desc'                => __( 'Help buyers place large orders quickly. They can build purchase lists, update them at any time, and complete the order directly from their “My Account” page.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/bulkorder.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/bulk-order/',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'         => 'https://getwholesalex.com/bulk-order/',
			'video'               => 'https://www.youtube.com/embed/uwHojBY0lZk',
			'status'              => wholesalex()->get_setting( 'wsx_addon_bulkorder' ),
			'setting_id'          => '#bulkorder',
			'lock_status'         => true,
		);

		$config['wsx_addon_subaccount'] = array(
			'name'                => __( 'Subaccounts ', 'wholesalex' ),
			'desc'                => __( 'Allow registered users to create subaccounts with customized permissions. Subaccount holders can perform allowed tasks on behalf of the main account owner.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/subaccount.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/subaccounts/',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'         => 'https://getwholesalex.com/subaccounts-in-woocommerce-b2b-stores/',
			'video'               => 'https://www.youtube.com/embed/cO4AYwkXyco',
			'status'              => wholesalex()->get_setting( 'wsx_addon_subaccount' ),
			'setting_id'          => '#subaccounts',
			'lock_status'         => true,
		);

		$config['wsx_addon_raq'] = array(
			'name'                   => __( 'Request a Quote', 'wholesalex' ),
			'desc'                   => __( 'Allow buyers to request custom quotes for the products they want. You can set personalized pricing, negotiate, and finalize purchase terms with ease.', 'wholesalex' ),
			'img'                    => WHOLESALEX_URL . 'assets/img/addons/raq.svg',
			'docs'                   => 'https://getwholesalex.com/docs/request-a-quote/',
			'live'                   => '',
			'is_pro'                 => true,
			'is_different_plugin'    => false,
			'depends_on'             => apply_filters( 'wholesalex_addon_raq_depends_on', array( 'wsx_addon_conversation' => __( 'Conversation', 'wholesalex' ) ) ),
			'eligible_price_ids'     => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'            => 'https://getwholesalex.com/request-a-quote/',
			'video'                  => 'https://www.youtube.com/embed/jOIdNj18OEI',
			'status'                 => wholesalex()->get_setting( 'wsx_addon_raq' ),
			'setting_id'             => '#raq',
			'lock_status'            => true,
			'is_conversation_active' => wholesalex()->get_setting( 'wsx_addon_conversation' ),
		);

		$config['wsx_addon_conversation'] = array(
			'name'                => __( 'Conversations', 'wholesalex' ),
			'desc'                => __( 'Enable messaging so customers can communicate with one another directly from their “My Account” dashboard and keep all conversations centralized.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/conversation.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/conversation/',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'         => 'https://getwholesalex.com/conversation/',
			'video'               => 'https://www.youtube.com/embed/-g9t44AhSRw?si=LO4EmZX87MaNyFjl',
			'status'              => wholesalex()->get_setting( 'wsx_addon_conversation' ),
			'setting_id'          => '#conversation',
			'lock_status'         => true,
		);

		$config['wsx_addon_wallet'] = array(
			'name'                => __( 'WholesaleX Wallet', 'wholesalex' ),
			'desc'                => __( 'Activate a store wallet that buyers can use as a payment method. They can deposit, store a balance, and check out faster with wallet funds.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/wallet.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/wallet/',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '1', '2', '3', '4', '5', '6', '7' ),
			'moreFeature'         => 'https://getwholesalex.com/wallet/',
			'video'               => 'https://www.youtube.com/embed/r4_V2ZW4p4I?si=ZO3-iZaffU8s7Cfw',
			'status'              => wholesalex()->get_setting( 'wsx_addon_wallet' ),
			'setting_id'          => '#wallet',
			'lock_status'         => true,
		);

		$config['wsx_addon_whitelabel'] = array(
			'name'                => __( 'White Label', 'wholesalex' ),
			'desc'                => __( 'Add your own branding while working on client sites using WholesaleX. Customize the plugin’s appearance to match your brand identity.', 'wholesalex' ),
			'img'                 => WHOLESALEX_URL . 'assets/img/addons/whitelabel.svg',
			'docs'                => 'https://getwholesalex.com/docs/wholesalex/add-on/white-label/',
			'live'                => '',
			'is_pro'              => true,
			'is_different_plugin' => false,
			'eligible_price_ids'  => array( '1', '2', '3', '4', '5', '6', '7' ),
			'status'              => wholesalex()->get_setting( 'wsx_addon_whitelabel' ),
			'lock_status'         => true,
			'setting_id'          => '#whitelabel',
			'video'               => 'https://www.youtube.com/embed/xMTJYQFbWEw',
		);

		$config['wsx_addon_dokan_integration'] = array(
			'name'                 => __( 'WholesaleX for Dokan', 'wholesalex' ),
			'desc'                 => __( 'Turn your store into a B2B multi-vendor marketplace. Create and manage wholesale discounts, dynamic rules, and user roles. Easily handle conversations between vendors and customers.', 'wholesalex' ),
			'img'                  => WHOLESALEX_URL . 'assets/img/addons/dokan_integration.svg',
			'docs'                 => 'https://getwholesalex.com/docs/wholesalex/wholesalex-for-dokan/',
			'live'                 => '',
			'is_pro'               => false,
			'is_different_plugin'  => true,
			'eligible_price_ids'   => array( 1, 2, 3, 4, 5, 6, 7 ),
			'status'               => function_exists( 'wholesalex_dokan_run' ),
			'lock_status'          => false,
			'setting_id'           => '#dokan_wholesalex',
			'is_installed'         => isset( $installed_plugins['multi-vendor-marketplace-b2b-for-wholesalex-dokan/multi-vendor-marketplace-b2b-for-wholesalex-dokan.php'] ),
			'install_slug'         => $can_install ? 'multi-vendor-marketplace-b2b-for-wholesalex-dokan' : '',
			'install_url'          => $can_install ? add_query_arg(
				array(
					'tab'  => 'search',
					'type' => 'term',
					's'    => 'multi-vendor-marketplace-b2b-for-wholesalex-dokan',
				),
				$plugin_install_page
			) : '',
			'manage_url'           => $can_activate ? $plugins_page : '',
			'plugin_url'           => 'https://wordpress.org/plugins/multi-vendor-marketplace-b2b-for-wholesalex-dokan/',
			'video'                => 'https://www.youtube.com/embed/4UatlL2-XXo',
			// Translators: %s is the name of the required plugin with an HTML link.
			'depends_message'      => sprintf( __( 'This addon require %s plugin', 'wholesalex' ), '<a href="https://wordpress.org/plugins/dokan-lite/" target="_blank">Dokan</a>' ),
			'is_dependency_active' => $this->check_required_plugins( 'dokan' ),
		);

		$config['wsx_addon_wcfm_integration'] = array(
			'name'                 => __( 'WholesaleX for WCFM', 'wholesalex' ),
			'desc'                 => __( 'Enable vendors to set wholesale prices and discounts in your WCFM-powered marketplace. Manage user conversations and streamline your B2B multi-vendor store operations.', 'wholesalex' ),
			'img'                  => WHOLESALEX_URL . 'assets/img/addons/wcfm_integration.svg',
			'docs'                 => 'https://getwholesalex.com/docs/wholesalex/wcfm-marketplace-integration/',
			'live'                 => '',
			'is_pro'               => false,
			'is_different_plugin'  => true,
			'eligible_price_ids'   => array( 1, 2, 3, 4, 5, 6, 7 ),
			'status'               => function_exists( 'wholesalex_wcfm_run' ),
			'lock_status'          => false,
			'setting_id'           => '#wcfm_wholesalex',
			'is_installed'         => isset( $installed_plugins['wholesalex-wcfm-b2b-multivendor-marketplace/wholesalex-wcfm-b2b-multivendor-marketplace.php'] ),
			'install_slug'         => $can_install ? 'wholesalex-wcfm-b2b-multivendor-marketplace' : '',
			'install_url'          => $can_install ? add_query_arg(
				array(
					'tab'  => 'search',
					'type' => 'term',
					's'    => 'wholesalex-wcfm-b2b-multivendor-marketplace',
				),
				$plugin_install_page
			) : '',
			'manage_url'           => $can_activate ? $plugins_page : '',
			'plugin_url'           => 'https://wordpress.org/plugins/wholesalex-wcfm-b2b-multivendor-marketplace/',
			'video'                => 'https://www.youtube.com/embed/2OLOqyvv5rE',
			// Translators: %s is the name of the required plugin with an HTML link.
			'depends_message'      => sprintf( __( 'This addon require %s plugin', 'wholesalex' ), '<a href="https://wordpress.org/plugins/wc-frontend-manager/" target="_blank">WCFM – Frontend Manager</a>' ),
			'is_dependency_active' => $this->check_required_plugins( 'wcfm' ),
		);

		$config['wsx_addon_migration_integration'] = array(
			'name'                 => __( 'WholesaleX Migration Tool', 'wholesalex' ),
			'desc'                 => __( 'Import your data from B2BKing and Wholesale Suite into WholesaleX with the built-in migration tool. Move everything over smoothly and continue selling without disruption.', 'wholesalex' ),
			'img'                  => WHOLESALEX_URL . 'assets/img/addons/migration_tool_icon.png',
			'docs'                 => 'https://getwholesalex.com/docs/wholesalex/wholesalex-migration-tool/',
			'live'                 => '',
			'is_pro'               => false,
			'is_different_plugin'  => true,
			'eligible_price_ids'   => array( 1, 2, 3, 4, 5, 6, 7 ),
			'status'               => function_exists( 'wholesalex_migration_run' ),
			'lock_status'          => false,
			'setting_id'           => '#migration_wholesalex',
			'is_installed'         => isset( $installed_plugins['wholesalex-migration-tool/wholesalex-migration-tool.php'] ),
			'install_slug'         => $can_install ? 'wholesalex-migration-tool' : '',
			'install_url'          => $can_install ? add_query_arg(
				array(
					'tab'  => 'search',
					'type' => 'term',
					's'    => 'wholesalex-migration-tool',
				),
				$plugin_install_page
			) : '',
			'manage_url'           => $can_activate ? $plugins_page : '',
			'plugin_url'           => 'https://wordpress.org/plugins/wholesalex-migration-tool/',
			'video'                => 'https://www.youtube.com/embed/KVRg10OcfTI',
			// Translators: %s is the name of the required plugin with an HTML link.
			'is_dependency_active' => $this->check_required_plugins( 'migration' ),
		);
		return $config;
	}
}
