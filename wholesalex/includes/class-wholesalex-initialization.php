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

		add_filter( 'admin_body_class', array( $this, 'add_wholesalex_class_on_backend' ) );
	}

	/**
	 * Load shared class dependencies before their consumers are declared.
	 *
	 * Also available to isolated integrations that load individual core classes.
	 *
	 * @return void
	 */
	public static function load_shared_dependencies() {
		require_once __DIR__ . '/security/class-user-operation-policy.php';
		require_once __DIR__ . '/class-registration-provider.php';
		require_once __DIR__ . '/import/class-customer-import-jobs.php';
		require_once __DIR__ . '/import/class-customer-import-cleanup.php';
		require_once __DIR__ . '/import/class-dynamic-rule-import-jobs.php';
		require_once __DIR__ . '/admin/rest/class-admin-resource-repository.php';
	}

	/**
	 * Load All Required Dependencies
	 */
	private function load_dependencies() {
		self::load_shared_dependencies();

		require_once WHOLESALEX_PATH . 'includes/class-wholesalex-role-preview.php';
		require_once WHOLESALEX_PATH . 'includes/class-registration-context.php';
		require_once WHOLESALEX_PATH . 'includes/registration/class-confirmation-service.php';
		\WHOLESALEX\Confirmation_Service::register();
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

		/**
		 * Register extensions after shared classes load and before runtime objects are created.
		 */
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
		require_once WHOLESALEX_PATH . 'includes/admin/rest/class-admin-resource-controller.php';
		$admin_resources = new \WHOLESALEX\Admin\Rest\Admin_Resource_Controller();
		add_action( 'rest_api_init', array( $admin_resources, 'register_routes' ) );
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
		add_action( 'woocommerce_before_customer_login_form', array( $this, 'confirmation_resend_form' ) );
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
			'show_promotions'      => wholesalex()->can_show_promotions(),
			'logo_url'             => apply_filters( 'wholesalex_logo_url', WHOLESALEX_URL . 'assets/icons/wholesalex-logo.svg' ),
			'plugin_name'          => wholesalex()->get_plugin_name(),
			'dynamic_rules_access' => wholesalex()->get_dynamic_rules_access(),
			'is_admin_interface'   => is_admin(),
			'helloBar'             => \WHOLESALEX\Includes\Admin\Notice\Notice::get_hellobar_config(),
			'sidebarOffer'         => \WHOLESALEX\Includes\Admin\Notice\Notice::get_sidebar_config(),
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
		// Product/profile editors need display settings, not the global admin configuration.
		if ( ! current_user_can( 'manage_options' ) ) {
			$setting_keys              = array(
				'recaptcha_version',
				'_settings_google_recaptcha_v3_site_key',
				'wsx_addon_recaptcha',
				'_settings_primary_button_color',
				'_settings_primary_color',
				'_settings_primary_hover_color',
				'_settings_text_color',
				'_settings_border_color',
				'dynamic_rule_per_page',
			);
			$settings                  = isset( $localize_data['settings'] ) && is_array( $localize_data['settings'] ) ? $localize_data['settings'] : array();
			$localize_data['settings'] = array_filter( array_intersect_key( $settings, array_flip( $setting_keys ) ), 'is_scalar' );
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
		// Public settings are an explicit scalar contract, enforced after provider filters.
		$public_setting_keys                = array(
			'recaptcha_version',
			'_settings_google_recaptcha_v3_site_key',
			'wsx_addon_recaptcha',
			'_settings_primary_button_color',
			'_settings_primary_color',
			'_settings_primary_hover_color',
			'_settings_text_color',
			'_settings_border_color',
		);
		$frontend_settings                  = isset( $frontend_localize_data['settings'] ) && is_array( $frontend_localize_data['settings'] ) ? $frontend_localize_data['settings'] : array();
		$frontend_localize_data['settings'] = array_filter( array_intersect_key( $frontend_settings, array_flip( $public_setting_keys ) ), 'is_scalar' );
		wp_localize_script( 'wholesalex', 'wholesalex', $frontend_localize_data );
		do_action( 'wholesalex_script_data_ready', 'wholesalex', 'frontend' );
	}

	/**
	 * Process WholesaleX User Email Confirmation
	 */
	public function wholesalex_process_user_email_confirmation() {
		require_once __DIR__ . '/registration/class-confirmation-service.php';
		if ( isset( $_POST['wholesalex_resend_confirmation'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The context-bound nonce is verified in the service.
			$id    = isset( $_POST['user_id'] ) && is_string( $_POST['user_id'] ) && ctype_digit( (string) filter_var( wp_unslash( $_POST['user_id'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[0-9]+$/D' ) ) ) ) ? absint( filter_var( wp_unslash( $_POST['user_id'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[0-9]+$/D' ) ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in the service.
			$token = isset( $_POST['confirmation_code'] ) && is_string( $_POST['confirmation_code'] ) ? filter_var( wp_unslash( $_POST['confirmation_code'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^(?:[a-f0-9]{32}|[a-f0-9]{64})$/D' ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in the service.
			$nonce = isset( $_POST['wholesalex-resend-nonce'] ) && is_string( $_POST['wholesalex-resend-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wholesalex-resend-nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in the service.
			\WHOLESALEX\Confirmation_Service::resend( $id, $token, $nonce );
			wc_add_notice( __( 'If this account is eligible, a new confirmation email has been sent. Please check your inbox.', 'wholesalex' ), 'notice' );
			return;
		}
		if ( isset( $_REQUEST['confirmation_code'], $_REQUEST['user_id'] ) && is_string( $_REQUEST['confirmation_code'] ) && is_string( $_REQUEST['user_id'] ) && ctype_digit( (string) filter_var( wp_unslash( $_REQUEST['user_id'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[0-9]+$/D' ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Authorised by the identity-bound emailed bearer.
			$result = \WHOLESALEX\Confirmation_Service::confirm( absint( filter_var( wp_unslash( $_REQUEST['user_id'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[0-9]+$/D' ) ) ) ), filter_var( wp_unslash( $_REQUEST['confirmation_code'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^(?:[a-f0-9]{32}|[a-f0-9]{64})$/D' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Authorised by the identity-bound emailed bearer.
			wc_add_notice( is_wp_error( $result ) ? $result->get_error_message() : __( 'Your account is successfully confirmed.', 'wholesalex' ), is_wp_error( $result ) ? 'error' : 'success' );
		}
	}

	/**
	 * Render bearer-bound recovery on the configured account page.
	 *
	 * @return void
	 */
	public function confirmation_resend_form() {
		if ( ! isset( $_REQUEST['confirmation_code'], $_REQUEST['user_id'] ) || ! is_string( $_REQUEST['confirmation_code'] ) || ! is_string( $_REQUEST['user_id'] ) || ! ctype_digit( (string) filter_var( wp_unslash( $_REQUEST['user_id'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[0-9]+$/D' ) ) ) ) || ! preg_match( '/^(?:[a-f0-9]{32}|[a-f0-9]{64})$/D', filter_var( wp_unslash( $_REQUEST['confirmation_code'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^(?:[a-f0-9]{32}|[a-f0-9]{64})$/D' ) ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Rendering a form grants no account authority.
			return;
		}
		$id    = absint( filter_var( wp_unslash( $_REQUEST['user_id'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[0-9]+$/D' ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Rendering only.
		$token = filter_var( wp_unslash( $_REQUEST['confirmation_code'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^(?:[a-f0-9]{32}|[a-f0-9]{64})$/D' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Rendering only.
		?>
		<form method="post" action="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
			<input type="hidden" name="user_id" value="<?php echo esc_attr( $id ); ?>">
			<input type="hidden" name="confirmation_code" value="<?php echo esc_attr( $token ); ?>">
			<?php wp_nonce_field( \WHOLESALEX\Confirmation_Service::nonce_action( $id, $token ), 'wholesalex-resend-nonce' ); ?>
			<button type="submit" name="wholesalex_resend_confirmation" value="1"><?php esc_html_e( 'Resend confirmation email', 'wholesalex' ); ?></button>
		</form>
		<?php
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
}
