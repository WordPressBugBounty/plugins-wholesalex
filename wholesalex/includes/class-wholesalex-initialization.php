<?php
/**
 * WholesaleX Initialization. Initialize All Files And Dependencies
 *
 * @link              https://www.wpxpo.com/
 * @since             1.0.0
 * @package           WholesaleX
 */

use WHOLESALEX\Scripts;
use WHOLESALEX\Xpo;

defined( 'ABSPATH' ) || exit;


/**
 * WholesaleX_Initialization Class
 */
class WholesaleX_Initialization {
	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		require_once WHOLESALEX_PATH . 'includes/recaptcha/init.php';
		// Admin Assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Frontend Assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );

		// Preserve notices from WordPress and other plugins.
		add_action( 'in_admin_header', array( $this, 'remove_notices' ) );

		add_filter( 'admin_body_class', array( $this, 'add_wholesalex_class_on_backend' ) );
	}

	/**
	 * Load All Required Dependencies
	 */
	private function load_dependencies() {
		require_once WHOLESALEX_PATH . 'includes/class-wholesalex-role-preview.php';
		require_once WHOLESALEX_PATH . 'includes/pricing/class-tier-pricing-calculator.php';
		require_once WHOLESALEX_PATH . 'includes/class-registration-context.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-overview.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-role.php';
		require_once WHOLESALEX_PATH . 'includes/class-wholesalex-menu.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-profile.php';
		require_once WHOLESALEX_PATH . 'includes/class-wholesalex-request-api.php';
		require_once WHOLESALEX_PATH . 'includes/class-wholesalex-product.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-category.php';
		require_once WHOLESALEX_PATH . 'includes/class-wholesalex-shortcodes.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-email.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-email-manager.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-dynamic-rules.php';
		require_once WHOLESALEX_PATH . 'includes/user-roles/class-payment-method.php';
		require_once WHOLESALEX_PATH . 'includes/user-roles/class-shipping-method.php';
		require_once WHOLESALEX_PATH . 'includes/user-roles/class-tax-rules.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/class-wholesale-pricing.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/class-wholesale-pricing-rule-registry.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/rules/class-product-discount.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/class-import-wholesalepricing.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/class-wholesale-pricing-rest-api.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/rules/class-wholesale-pricing-condition-engine.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/rules/class-rule-regular-discount.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/rules/class-rule-cart-discount.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/rules/class-rule-bogo-discount.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/rules/class-wholesale-pricing-rule-engine.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/compatibility/wowaddons/class-wowaddons-compatibility.php';
		require_once WHOLESALEX_PATH . 'includes/wholesale-pricing/compatibility/product-addons-ultimate/class-product-addons-ultimate-compatibility.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-settings.php';
		require_once WHOLESALEX_PATH . 'includes/class-wholesalex-scripts.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-registration.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-orders.php';
		require_once WHOLESALEX_PATH . 'includes/class-wholesalex-import-export.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-users.php';
		require_once WHOLESALEX_PATH . 'includes/options/Addons.php';
		require_once WHOLESALEX_PATH . 'includes/menu/class-wholesalex-request-role-change.php';
		require_once WHOLESALEX_PATH . 'includes/compatibility/woocommerce-bookings.php';
		require_once WHOLESALEX_PATH . 'includes/compatibility/woo-product-bundles.php';
		require_once WHOLESALEX_PATH . 'includes/compatibility/aeila_currency_switcher.php';
		require_once WHOLESALEX_PATH . 'includes/durbin/class-xpo.php';
		require_once WHOLESALEX_PATH . 'includes/admin/notice/class-notice.php';
		require_once WHOLESALEX_PATH . 'includes/class-wholesalex-common-utils.php';

		/** Register extensions after shared classes load and before runtime objects are created. */
		do_action( 'wholesalex_register_extensions' );

		do_action( 'wholesalex_after_core_loaded' );

		new \WHOLESALEX\Wholesalex_Role_Preview();
		new \WHOLESALEX\WHOLESALEX_Role();
		new \WHOLESALEX\WHOLESALEX_Registration();
		new \WHOLESALEX\User_Roles_Payment_Method();
		new \WHOLESALEX\User_Roles_Shipping_Method();
		new \WHOLESALEX\User_Roles_Tax_Rules();

		new \WHOLESALEX\WHOLESALEX_Dynamic_Rules();
		new \WHOLESALEX\Import_Wholesale_Pricing();
		new \WHOLESALEX\Wholesale_Pricing_Rest_Api();
		new \WHOLESALEX\Wholesale_Pricing_Rule_Engine();
		new \WHOLESALEX\Wholesale_Pricing_WowAddons_Compatibility();
		new \WHOLESALEX\Wholesale_Pricing_Product_Addons_Ultimate_Compatibility();

		new \WHOLESALEX\Addons();
		new \WHOLESALEX\WHOLESALEX_Users();
		new \WHOLESALEX\WHOLESALEX_Email();
		new \WHOLESALEX\Settings();
		new \WHOLESALEX\WHOLESALEX_Category();
		new \WHOLESALEX\WHOLESALEX_Menu();
		new \WHOLESALEX\WHOLESALEX_Profile();
		new \WHOLESALEX\WHOLESALEX_Product();
		new \WHOLESALEX\WHOLESALEX_Request_API();
		new \WHOLESALEX\WHOLESALEX_Shortcodes();
		new \WHOLESALEX\WHOLESALEX_Email_Manager();
		new \WHOLESALEX\WHOLESALEX_Orders();
		new \WHOLESALEX\ImportExport();
		new \WHOLESALEX\WHOLESALEX_Overview();
		new \WHOLESALEX\WHOLESALEX_RequstRoleChange();
		new \WHOLESALEX\WHOLESALEX_Woocommerce_Bookings();
		new \WHOLESALEX\WHOLESALEX_WooProduct_Bundles();
		new \WHOLESALEX\Includes\Admin\Notice\Notice();

		add_action( 'template_redirect', array( $this, 'wholesalex_process_user_email_confirmation' ) );
	}


	/**
	 * Admin Enque Scripts
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		$page   = wholesalex()->get_current_admin_page_slug();
		if ( ! $screen || ( ! ( $page && wholesalex()->is_wholesalex_page( $page ) ) && false === strpos( $screen->id, 'wholesalex' ) && 'wsx_conversation' !== $screen->post_type && ! in_array( $screen->id, array( 'product', 'edit-product', 'edit-product_cat', 'profile', 'user-edit', 'user', 'users', 'dashboard', 'shop_order', 'woocommerce_page_wc-orders' ), true ) && ! ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) ) ) {
			return; }

		// On the main Dashboard, WholesaleX only has the "Last Month Insights" widget,
		// which is itself limited to this capability; skip loading anything for other visitors.
		if ( 'dashboard' === $screen->id && ! current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) ) ) {
			return;
		}

		Scripts::register_backend_scripts();
		Scripts::register_backend_style();

		// wholesalex-admin.css / wholesalex_public have no dashboard-specific rules; the
		// dashboard widget enqueues its own dedicated bundle from its own render callback.
		if ( 'dashboard' !== $screen->id ) {
			wp_enqueue_style( 'wholesalex' );
			wp_enqueue_style( 'wholesalex_public' );
		}
		wp_enqueue_script( 'wholesalex' );
		$localize_data = array(
			'url'                  => WHOLESALEX_URL,
			'nonce'                => wp_create_nonce( 'wholesalex-registration' ),
			'ajax'                 => admin_url( 'admin-ajax.php' ),
			'wholesalex_roles'     => get_option( '_wholesalex_roles' ),
			'currency_symbol'      => get_woocommerce_currency_symbol(),
			'currency_pos'         => get_option( 'woocommerce_currency_pos', 'left' ),
			'current_version'      => WHOLESALEX_VER,
			'recaptcha_status'     => wholesalex()->get_setting( 'wsx_addon_recaptcha' ),
			'pro_link'             => wholesalex()->get_premium_link(),
			'ver'                  => WHOLESALEX_VER,
			'settings'             => wholesalex()->get_setting(),
			'logo_url'             => apply_filters( 'wholesalex_logo_url', WHOLESALEX_URL . 'assets/icons/wholesalex-logo.svg' ),
			'plugin_name'          => wholesalex()->get_plugin_name(),
			'dynamic_rules_access' => wholesalex()->get_dynamic_rules_access(),
			'is_admin_interface'   => is_admin(),
			'helloBar'             => \WHOLESALEX\Includes\Admin\Notice\Notice::get_hellobar_config(),
			'i18n'                 => array(
				'smart_tags' => __( 'Available Smart Tags: ', 'wholesalex' ),
			),
			'is_rtl_support'       => is_rtl(),
		);

		$localize_data = apply_filters(
			'wholesalex_backend_localize_data',
			array_merge( $localize_data, Xpo::get_wow_products_details() )
		);
		// Strip credentials after filters because integrations can replace the settings payload.
		if ( isset( $localize_data['settings'] ) && is_array( $localize_data['settings'] ) ) {
			unset( $localize_data['settings']['_settings_google_recaptcha_v3_secret_key'] );
		}
		wp_localize_script( 'wholesalex', 'wholesalex', $localize_data );
		do_action( 'wholesalex_script_data_ready', 'wholesalex', 'admin' );
	}

	/**
	 * Frontend Enque Scripts
	 */
	public function frontend_enqueue_scripts() {
		Scripts::register_frontend_scripts();
		Scripts::register_fronend_style();
		wp_enqueue_style( 'wholesalex' );
		wp_enqueue_style( 'wholesalex_public' );
		wp_enqueue_script( 'wholesalex' );

		do_action( 'wholesalex_after_frontend_enqueue_scripts' );

		$frontend_localize_data = apply_filters(
			'wholesalex_frontend_localize_data',
			array(
				'url'                => WHOLESALEX_URL,
				'nonce'              => wp_create_nonce( 'wholesalex-registration' ),
				'ajax'               => admin_url( 'admin-ajax.php' ),
				'recaptcha_status'   => wholesalex()->get_setting( 'wsx_addon_recaptcha' ),
				'ver'                => WHOLESALEX_VER,
				'settings'           => wholesalex()->get_setting(),
				'logo_url'           => apply_filters( 'wholesalex_logo_url', WHOLESALEX_URL . 'assets/icons/wholesalex-logo.svg' ),
				'plugin_name'        => wholesalex()->get_plugin_name(),
				'is_admin_interface' => is_admin(),
				'i18n'               => array(
					'cannot_register_message_for_logged_in_user' => __( 'You cannot register while you are logged in.', 'wholesalex' ),
					'is_required' => __( 'is required', 'wholesalex' ),
					'register'    => __( 'Register', 'wholesalex' ),

				),
				'cart_url'           => wc_get_cart_url(),
				'currency_pos'       => get_option( 'woocommerce_currency_pos', 'left' ),
				'currency_symbol'    => get_woocommerce_currency_symbol(),
				'is_required'        => __( 'is required', 'wholesalex' ),

			)
		);
		// Strip credentials after filters because integrations can replace the settings payload.
		if ( isset( $frontend_localize_data['settings'] ) && is_array( $frontend_localize_data['settings'] ) ) {
			unset( $frontend_localize_data['settings']['_settings_google_recaptcha_v3_secret_key'] );
		}
		wp_localize_script( 'wholesalex', 'wholesalex', $frontend_localize_data );
		do_action( 'wholesalex_script_data_ready', 'wholesalex', 'frontend' );
	}

	/**
	 * Process WholesaleX User Email Confirmation
	 */
	public function wholesalex_process_user_email_confirmation() {

		if ( isset( $_REQUEST['confirmation_code'], $_REQUEST['user_id'] ) && is_string( $_REQUEST['confirmation_code'] ) && is_scalar( $_REQUEST['user_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The per-user confirmation secret is verified below.
			$__user_id                   = absint( wp_unslash( $_REQUEST['user_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Emailed confirmation link; authorised by the secret code.
			$__confirmation_code         = get_user_meta( $__user_id, '__wholesalex_email_confirmation_code', true );
			$confirmation_status         = get_user_meta( $__user_id, '__wholesalex_account_confirmed', true );
			$requested_confirmation_code = sanitize_text_field( wp_unslash( $_REQUEST['confirmation_code'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Emailed confirmation link; authorised by the secret code.

			if ( $confirmation_status ) {
				wc_add_notice( __( ' Your account is already confirmed!. ', 'wholesalex' ), 'notice' );
			} elseif ( is_string( $__confirmation_code ) && '' !== $__confirmation_code && hash_equals( $__confirmation_code, $requested_confirmation_code ) ) {
				/**
				 * Filter how long a confirmation link stays valid.
				 *
				 * @param int $ttl Lifetime in seconds.
				 */
				$link_ttl  = (int) apply_filters( 'wholesalex_email_confirmation_link_ttl', 2 * DAY_IN_SECONDS );
				$issued_at = (int) get_user_meta( $__user_id, '__wholesalex_email_confirmation_code_time', true );

				// Links issued before this meta existed keep working; new ones expire.
				if ( $issued_at > 0 && $link_ttl > 0 && ( time() - $issued_at ) > $link_ttl ) {
					wc_add_notice( __( 'This confirmation link has expired. Please request a new confirmation email.', 'wholesalex' ), 'error' );
					return;
				}

				$__registration_role = get_user_meta( $__user_id, '__wholesalex_registration_role', true );
				$assigned_role       = get_user_meta( $__user_id, '__wholesalex_role', true );
				$scope_error         = $assigned_role ? null : \WHOLESALEX\Registration_Context::approval_error( $__user_id, $__registration_role );
				if ( is_wp_error( $scope_error ) ) {
					wc_add_notice( $scope_error->get_error_message(), 'error' );
					return;
				}
				update_user_meta( $__user_id, '__wholesalex_account_confirmed', true );
				update_user_meta( $__user_id, '__wholesalex_status', 'active' );

				// The secret is single use: drop it once the account is confirmed.
				delete_user_meta( $__user_id, '__wholesalex_email_confirmation_code' );
				delete_user_meta( $__user_id, '__wholesalex_email_confirmation_code_time' );

				wholesalex()->change_role( $__user_id, $assigned_role ? $assigned_role : $__registration_role );
				wc_add_notice( __( '<strong>Success:</strong> Your account is successfully confirmed. ', 'wholesalex' ) );
				do_action( 'wholesalex_user_email_verified', $__user_id );
			}
		}
	}

	/**
	 * Add wholesalex class on backend
	 *
	 * @param array $classes Classes.
	 * @return array
	 */
	public function add_wholesalex_class_on_backend( $classes ) {

		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$post_type = $screen && $screen->post_type ? $screen->post_type : '';
		$page      = wholesalex()->get_current_admin_page_slug();

		if ( ( '' !== $page && wholesalex()->is_wholesalex_page( $page ) ) || ( '' !== $post_type && wholesalex()->is_wholesalex_page( $post_type ) ) ) {
			$classes .= ' wholesalex_backend_body';
		}
		return $classes;
	}

	/**
	 * Visually hide unrelated notices on WholesaleX admin pages.
	 *
	 * The notice hooks are left intact so WordPress and third-party notices can
	 * continue to run. Error notices, update nags, and WholesaleX notices remain
	 * visible.
	 *
	 * @return void
	 */
	public function remove_notices() {
		$page = wholesalex()->get_current_admin_page_slug();
		if ( '' === $page || ! wholesalex()->is_wholesalex_page( $page ) ) {
			return;
		}

		echo '<style id="wholesalex-hide-admin-notices">#wpbody-content > .notice:not(.notice-error):not(.error):not(.update-nag):not(.wsx-notice), #wpbody-content > .updated:not(.notice-error):not(.wsx-notice) { display: none !important; }</style>';
	}
}
