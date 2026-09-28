<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Preserve the established activator include path.
/**
 * WholesaleX Activator
 *
 * @link              https://www.wpxpo.com/
 * @since             1.0.0
 * @package           WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * WholesaleX Activator Class
 */
class Activator {

	/**
	 * Activator Constructor
	 */
	public function __construct() {
		// Initialize new installations without resetting completed onboarding on reactivation.
		wholesalex()->get_onboarding_status();
		$this->init_set_data();
		$had_roles            = (bool) get_option( '_wholesalex_roles' );
		$created_roles        = $this->init_roles();
		require_once WHOLESALEX_PATH . 'includes/class-registration-page.php';
		Registration_Page::install();
		$default_b2b_role_id  = apply_filters( 'wholesalex_default_b2b_role_id', 'wholesalex_b2b_wholesale' );
		$default_role_ids     = array_keys( wholesalex()->get_default_roles() );
		$role_ids             = wholesalex()->get_roles( 'ids' );
		$has_only_defaults    = empty( array_diff( $role_ids, $default_role_ids ) );
		$has_default_b2b_role = ! empty( wholesalex()->get_roles( 'by_id', $default_b2b_role_id ) );
		$should_assign_admins = 'yes' !== get_option( '_wholesalex_default_admin_role_assigned' )
			&& $has_default_b2b_role
			&& ( ! $had_roles || in_array( $default_b2b_role_id, $created_roles, true ) || $has_only_defaults );

		if ( $should_assign_admins ) {
			$this->assign_admin_wholesale_role();
		}

		add_action( 'activated_plugin', array( $this, 'activation_redirect' ) );
	}

	/**
	 * Initialize Settings data
	 *
	 * @return void
	 * @since 1.0.0
	 * @since 1.0.1  _settings_show_form_for_logged_in and _settings_message_for_logged_in_user Default Value Added.
	 * @since 1.0.2 Plugin Default Status set B2B and B2C.
	 */
	public function init_set_data() {
		$data      = get_option( 'wholesalex_settings', array() );
		$init_data = array(
			// Recaptcha.
			'_settings_google_recaptcha_v3_allowed_score' => '0.5',
			'recaptcha_version'                           => 'recaptcha_v3',
			// Addons.
			'wsx_addon_recaptcha'                         => 'no',
			// General.
			'_settings_status'                            => 'b2b_n_b2c',
			'_settings_show_table'                        => 'yes',
			'_settings_enable_custom_priority_order'      => 'no',
			'_settings_quantity_based_discount_priority'  => array( 'profile', 'single_product', 'category', 'wholesale_pricing' ),
			'_settings_display_price_shop_page'           => 'woocommerce_default_tax',
			'_settings_display_price_cart_checkout'       => 'woocommerce_default_tax',
			// Registration & Login.
			'_settings_user_login_option'                 => 'manual_login',
			'_settings_user_status_option'                => 'admin_approve',
			'_settings_redirect_url_registration'         => get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ),
			'_settings_redirect_url_login'                => get_permalink( get_option( 'woocommerce_shop_page_id' ) ),
			'_settings_registration_success_message'      => __( 'Thank you for registering. Your account will be reviewed by us & approve manually. Please wait to be approved.', 'wholesalex' ),
			'_settings_enable_separate_page_b2b'          => 'no',
			'_settings_seperate_page_b2b'                 => get_option( 'woocommerce_myaccount_page_id' ),
			'_settings_show_form_for_logged_in'           => 'yes',
			'_settings_message_for_logged_in_user'        => __( 'Sorry You Are Not Allowed To View This Form', 'wholesalex' ),
			// Price.
			'_settings_price_text'                        => __( 'Wholesale Price:', 'wholesalex' ),
			'_settings_price_text_product_list_page'      => __( 'Wholesale Price:', 'wholesalex' ),
			'_settings_price_product_list_page'           => 'pricing_range',
			'_settings_primary_color'                     => '#2FC4A7',
			'settings_primary_hover_color'                => '#24A88F',
			'_settings_text_color'                        => '#272727',
			'_settings_border_color'                      => '#E5E5E5',
		);
		if ( empty( $data ) ) {
			update_option( 'wholesalex_settings', $init_data );
			$GLOBALS['wholesalex_settings'] = $init_data;
		} else {
			foreach ( $init_data as $key => $single ) {
				if ( ! isset( $data[ $key ] ) ) {
					$data[ $key ] = $single;
				}
			}
			update_option( 'wholesalex_settings', $data );
			$GLOBALS['wholesalex_settings'] = $data;
		}
		$installation_date = get_option( 'wholesalex_installation_date' );
		if ( ! $installation_date ) {
			update_option( 'wholesalex_installation_date', gmdate( 'U' ) );
		}
	}
	/**
	 * Initial Role.
	 * After plugin activation default role is create.
	 *
	 * @return array Role IDs created during this call.
	 */
	public function init_roles() {
		return wholesalex()->ensure_default_roles();
	}

	/**
	 * Assign all administrator users to the B2B Wholesale Role if they do not
	 * already have a WholesaleX role assigned.
	 *
	 * @return void
	 */
	public function assign_admin_wholesale_role() {
		$default_b2b_role_id = apply_filters( 'wholesalex_default_b2b_role_id', 'wholesalex_b2b_wholesale' );
		wholesalex()->assign_admin_wholesale_role( $default_b2b_role_id );
	}

	/**
	 * Activation Redirect
	 *
	 * @param string $plugin Plugin Slug.
	 * @since 1.0.1
	 * @since 1.1.0 Initial Setup Wizard Added
	 */
	public function activation_redirect( $plugin ) {
		if ( ! is_admin() || ! current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) )
			|| wp_doing_ajax() || wp_doing_cron()
			|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			|| ( defined( 'WP_CLI' ) && WP_CLI )
			|| ( defined( 'WP_INSTALLING' ) && WP_INSTALLING )
			|| 'pending' !== wholesalex()->get_onboarding_status() ) {
			return;
		}
		// Confirm this is the authorized single-plugin activation request before
		// reading anything else from it. Bulk activations carry the bulk nonce and
		// must not trigger the onboarding redirect.
		$activation_nonce = isset( $_REQUEST['_wpnonce'] ) && is_string( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

		if ( '' === $activation_nonce || ! wp_verify_nonce( $activation_nonce, 'activate-plugin_' . $plugin ) ) {
			return;
		}

		if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) || ( isset( $_POST['action'] ) && is_string( $_POST['action'] ) && 'activate-selected' === sanitize_key( wp_unslash( $_POST['action'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.NonceVerification.Missing -- The activation nonce is verified above; these only distinguish bulk activation.
			return;
		}
		if ( WHOLESALEX_BASE === $plugin ) {
			if ( ! class_exists( 'woocommerce' ) ) {
				return;
			}
			$menu_slug = apply_filters( 'wholesalex_plugin_menu_slug', 'wholesalex' );
			wp_safe_redirect( admin_url( 'admin.php?page=' . rawurlencode( $menu_slug ) ) . '#/onboarding' );
			exit();
		}
	}
}
