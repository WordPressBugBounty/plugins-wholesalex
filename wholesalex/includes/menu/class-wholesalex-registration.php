<?php
/**
 * WholesaleX Registration
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

use Exception;
use WHOLESALEX\WholesaleX_CommonUtils;
use WP_Error;

/**
 * WholesaleX Registration class
 */
class WHOLESALEX_Registration {

	/**
	 * Custom Fields
	 *
	 * @var array
	 */
	public $woo_custom_fields = array();

	/**
	 * Registration Fields
	 *
	 * @var array
	 */
	public $registration_fields = array();

	/**
	 * Verified context captured before registration hooks run.
	 *
	 * @var array|null
	 */
	private $authorized_registration_context = null;

	/**
	 * Registration Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'registration_form_builder_restapi_callback' ) );
		add_filter( 'wp_authenticate_user', array( $this, 'check_status' ), 10, 2 );
		add_filter( 'wholesalex_registration_form_user_login_option', array( $this, 'user_login_option' ) );
		add_filter( 'wholesalex_registration_form_user_status_option', array( $this, 'user_status_option' ), 10, 3 );

		add_filter( 'wholesalex_registration_form_after_registration_redirect_url', array( $this, 'after_registration_redirect' ), 10, 3 );
		add_filter( 'wholesalex_registration_form_after_registration_success_message', array( $this, 'after_registration_success_message' ) );
		add_action( 'wholesalex_registration_form_user_status_email_confirmation_require', array( $this, 'confirmation_email_after_registration' ), 10, 2 );
		add_action( 'wholesalex_registration_form_user_status_auto_approve', array( $this, 'auto_approve_after_registration' ), 10, 3 );
		add_action( 'wholesalex_registration_form_user_auto_login', array( $this, 'auto_login_after_registration' ) );
		add_filter( 'woocommerce_login_redirect', array( $this, 'login_redirect' ), 10, 2 );
		add_action( 'wholesalex_registration_form_user_status_admin_approve', array( $this, 'user_registration_admin_approval_need' ) );

		add_action( 'init', array( $this, 'register_block' ) );
		add_filter( 'render_block_wholesalex/forms', array( $this, 'render_forms_block' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		add_action( 'wp_ajax_nopriv_wholesalex_process_registration', array( $this, 'process_registration' ) );
		add_action( 'wp_ajax_wholesalex_process_registration', array( $this, 'process_registration' ) );

		add_action( 'template_redirect', array( $this, 'show_wholesalex_notice' ) );
	}


	/**
	 * Enqueue Block Editor Assets
	 *
	 * @since 1.0.0
	 */
	public function enqueue_block_editor_assets() {
		$slug = apply_filters( 'wholesalex_registration_form_builder_submenu_slug', 'wholesalex-registration' );

		wp_enqueue_script( 'wholesalex_forms_block', WHOLESALEX_URL . 'assets/js/wholesalex_forms_block.js', array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components' ), WHOLESALEX_VER, true );
		wp_enqueue_style( 'wholesalex_forms_block', WHOLESALEX_URL . 'assets/js/wholesalex_forms_block.css', array(), WHOLESALEX_VER );
		wp_localize_script(
			'wholesalex_forms_block',
			'wholesalex_block_data',
			array(
				'form_builder_url' => admin_url( 'admin.php?page=wholesalex-registration' ),
				'url'              => WHOLESALEX_URL,
			)
		);
	}

	/**
	 * Register Block
	 *
	 * @since 1.0.0
	 */
	public function register_block() {
		register_block_type(
			'wholesalex/form',
			array(
				'editor_script'   => 'wholesalex_forms_block',
				'editor_style'    => 'wholesalex_forms_block',
				'render_callback' => array( $this, 'render_block' ),
			)
		);
		$this->set_custom_fields();
	}

	/**
	 * Render Block
	 *
	 * @since 1.0.0
	 */
	public function render_block() {
		return '';
	}

	/**
	 * Let the builder control login visibility for new and previously saved blocks.
	 *
	 * Keep the saved markup intact for block validation and leave standalone
	 * registration shortcodes unchanged. The combined shortcode checks the
	 * builder's login setting before rendering.
	 *
	 * @param string $block_content Saved block markup.
	 * @return string
	 */
	public function render_forms_block( $block_content ) {
		return preg_replace( '/\[wholesalex_registration(?=[\s\]])/', '[wholesalex_login_registration', $block_content );
	}

	/**
	 * User Registration Form Output
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function output() {
		/**
		 * Enqueue Script
		 *
		 * @since 1.1.0 Enqueue Script (Reconfigure Build File)
		 */
		$is_woo_username = get_option( 'woocommerce_registration_generate_username' );
		wp_enqueue_script( 'wholesalex_form_builder' );
		// Added Password Condition.
		$password_condition_options = array(
			array(
				'value' => 'uppercase_condition',
				'name'  => 'Uppercase',
			),
			array(
				'value' => 'lowercase_condition',
				'name'  => 'Lowercase',
			),
			array(
				'value' => 'special_character_condition',
				'name'  => 'Special Character',
			),
			array(
				'value' => 'min_length_condition',
				'name'  => 'Min 8 Length',
			),
		);

		// Add File Support Type.

		$form_regi_data    = WholesaleX_CommonUtils::get_default_registration_form_fields();
		$default_form_data = WholesaleX_CommonUtils::get_empty_form();
		wp_localize_script(
			'wholesalex_form_builder',
			'whx_form_builder',
			apply_filters(
				'wholesalex_form_builder_script_data',
				array(
					'is_woo_username'            => $is_woo_username,
					'login_form_data'            => wp_json_encode( $default_form_data['loginFields'] ),
					'form_data'                  => wp_json_encode( $form_regi_data ),
					'roles'                      => wholesalex()->get_roles( 'store_mode_roles_option' ),
					'privacy_policy_text'        => wc_get_privacy_policy_text( 'registration' ),
					'password_condition_options' => $password_condition_options,

				)
			)
		);
		?>
		<div id="wholesalex_registration_form_builder__root"></div>
		<?php
	}

	/**
	 * WholesaleX Registration Form Builder Rest Api Callback
	 *
	 * @return void
	 */
	public function registration_form_builder_restapi_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/builder_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'builder_action_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			'wholesalex/v1',
			'/blockPreview/forms',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_form_preview' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Get Form Preview
	 *
	 * @param object $server Server.
	 * @since 1.0.0
	 */
	public function get_form_preview( $server ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Retain the established callback signature for compatibility.
		$form_regi_data = WholesaleX_CommonUtils::get_new_form_builder_data();

		wp_send_json_success(
			array(
				'formdata' => wp_json_encode( $form_regi_data ),
			)
		);
	}

	/**
	 * Registration Form Builder Action
	 *
	 * @param object $server Server.
	 * @return mixed
	 */
	public function builder_action_callback( $server ) {
		$post = $server->get_params();
		if ( ! isset( $post['nonce'] ) || ! is_string( $post['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $post['nonce'] ) ), 'wholesalex-registration' ) ) {
			return;
		}

		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		if ( 'post' === $type ) {

			if ( isset( $post['data'] ) ) {
				$form_data = $this->sanitize_form_builder_payload( $post['data'] );

				if ( is_wp_error( $form_data ) ) {
					wp_send_json_error(
						array(
							'message' => $form_data->get_error_message(),
						)
					);
				}

				update_option( 'wholesalex_registration_form', $form_data );

				$GLOBALS['wholesalex_registration_fields'] = WholesaleX_CommonUtils::get_form_fields();

				wp_send_json_success(
					array(
						'form_data' => $form_data,
					)
				);
			} else {
				wp_send_json_error();
			}
		} elseif ( 'get' === $type ) {
			$__roles_options = wholesalex()->get_roles( 'store_mode_roles_option' );

			wp_send_json_success(
				array(
					'roles'     => $__roles_options,
					'form_data' => get_option( 'wholesalex_registration_form' ),
				)
			);
		}
	}

	/**
	 * Sanitize form builder payload before saving.
	 *
	 * @param string $payload JSON payload.
	 * @return string|WP_Error
	 */
	private function sanitize_form_builder_payload( $payload ) {
		$decoded = json_decode( wp_unslash( $payload ), true );

		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'invalid_form_builder_payload', __( 'Invalid registration form data.', 'wholesalex' ) );
		}

		$decoded = $this->sanitize_form_builder_value( $decoded );
		$decoded = WholesaleX_CommonUtils::normalize_default_registration_template( $decoded );

		return wp_json_encode( $decoded );
	}

	/**
	 * Recursively sanitize form builder values while preserving arrays and scalar types.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private function sanitize_form_builder_value( $value ) {
		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $key => $item ) {
				$sanitized_key               = is_string( $key ) ? sanitize_text_field( $key ) : $key;
				$sanitized[ $sanitized_key ] = $this->sanitize_form_builder_value( $item );
			}

			return $sanitized;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( $value );
	}





	/**
	 * Email Confirmation After Registration
	 *
	 * @param int|string $user_id User ID.
	 * @param string     $registration_role Registration Role.
	 * @return void
	 */
	public function confirmation_email_after_registration( $user_id, $registration_role ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Preserve the existing callback or public method signature.
		update_user_meta( $user_id, '__wholesalex_status', 'pending' );

		$confirmation_code = bin2hex( random_bytes( 16 ) );
		update_user_meta( $user_id, '__wholesalex_email_confirmation_code', $confirmation_code );
		update_user_meta( $user_id, '__wholesalex_email_confirmation_code_time', time() );
		update_user_meta( $user_id, '__wholesalex_account_confirmed', false );
		do_action( 'wholesalex_user_email_confirmation', $user_id, $confirmation_code );
	}


	/**
	 * Auto Approve After Registration
	 *
	 * @param int|string $user_id User ID.
	 * @param string     $registration_role Registration Role.
	 * @param string     $password Password.
	 * @return void
	 */
	public function auto_approve_after_registration( $user_id, $registration_role, $password = '' ) {
		$error = Registration_Context::approval_error( $user_id, $registration_role );
		if ( is_wp_error( $error ) ) {
			update_user_meta( $user_id, '__wholesalex_status', 'pending' );
			return;
		}
		wholesalex()->change_role( $user_id, $registration_role );
		update_user_meta( $user_id, '__wholesalex_status', 'active' );
		do_action( 'wholesalex_set_status_active', $user_id, $password );
		do_action( 'wholesalex_user_auto_approval', $user_id );
	}
	/**
	 * Auto Login After Registration
	 *
	 * Auto Login Will Work only if user status is auto approved.
	 *
	 * @param int|string $user_id User ID.
	 */
	public function auto_login_after_registration( $user_id ) {
		$__user_status = wholesalex()->get_user_status( $user_id );
		if ( 'pending' === $__user_status ) {
			$registration_role = get_user_meta( $user_id, '__wholesalex_registration_role', true );
			$user_login_option = apply_filters( 'wholesalex_registration_form_user_status_option', 'admin_approve', $user_id, $registration_role );
			switch ( $user_login_option ) {
				case 'admin_approve':
					/* translators: %s: Account Status */
					$message = sprintf( esc_html__( 'Your account Status is %s. Please Contact with Site Administration to approve your account.', 'wholesalex' ), $__user_status );
					return new WP_Error( 'admin_approval_pending', $message );
				case 'email_confirmation_require':
					$message = esc_html__( 'Please confirm your account by clicking the confirmation link, that already sent your registered email.', 'wholesalex' );
					return new WP_Error( 'email_confirmation_require', $message );

				default:
					// code...
					break;
			}
		}
		if ( 'active' !== $__user_status ) {
			return new WP_Error( 'admin_approval_pending', esc_html__( 'Your account is not active. Please contact the site administrator.', 'wholesalex' ) );
		}
		wc_set_customer_auth_cookie( $user_id );
		do_action( 'wholesalex_user_auto_login', $user_id );
	}

	/**
	 * Generate Verification Code
	 *
	 * @param int $len length of verification code.
	 * @throws Exception If the generated string does not meet the criteria.
	 */
	public function generate_confirmation_code( $len ) {
		$characters        = '0123456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_-[]{}!@$';
		$characters_length = strlen( $characters );
		$random_string     = '';
		for ( $i = 0; $i < $len; $i++ ) {
			$random_character = $characters[ wp_rand( 0, $characters_length - 1 ) ];
			$random_string   .= $random_character;
		}
		$random_string = sanitize_user( $random_string );
		if ( ( preg_match( '([a-zA-Z].*[0-9]|[0-9].*[a-zA-Z].*[_\W])', $random_string ) === 1 ) && ( strlen( $random_string ) === $len ) ) {
			return $random_string;
		} else {
			return call_user_func( array( $this, 'generate_confirmation_code' ), $len );
		}
	}

	/**
	 * Check User Approval Status
	 *
	 * @param WP_User $user user object.
	 * @since 1.0.0
	 * @since 1.1.10 Admin Approval Require Restriction Remove For WooCommerce Registration Form Users.
	 */
	public function check_status( $user ) {
		// Check if $user is a WP_User object.
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( user_can( $user->ID, 'manage_options' ) ) {
			return $user;
		}

		$registration_role = get_user_meta( $user->ID, '__wholesalex_registration_role', true );
		$user_login_option = apply_filters( 'wholesalex_registration_form_user_status_option', 'admin_approve', $user->ID, $registration_role );
		$status            = get_the_author_meta( '__wholesalex_status', $user->ID );

		if ( 'admin_approve' === $user_login_option ) {
			$message = '';
			switch ( $status ) {
				case 'pending':
					/* translators: %s: Account Status */
					$message = sprintf( esc_html__( 'Your account Status is %s. Please Contact with Site Administration to approve your account.', 'wholesalex' ), $status );
					break;
				case 'inactive':
					/* translators: %s: Account Status */
					$message = sprintf( esc_html__( 'Your account Status is %s. Please Contact with Site Administration to Active your account.', 'wholesalex' ), $status );
					break;
				case 'reject':
					/* translators: %s: Account Status */
					$message = sprintf( esc_html__( 'Your account Status is %s. Please Contact with Site Administration to Active your account.', 'wholesalex' ), $status );
					break;

				default:
					// code...
					break;
			}
			if ( '' !== $message ) {
				return new WP_Error( 'admin_approval_pending', $message );
			}
		} elseif ( 'email_confirmation_require' === $user_login_option && 'pending' === $status ) {
			$message = esc_html__( 'Please confirm your account by clicking the confirmation link, that already sent your registered email.', 'wholesalex' );
			return new WP_Error( 'email_confirmation_require', $message );
		}
		return $user;
	}

	/**
	 * Returns the login redirect URL.
	 *
	 * @param string  $redirect Default redirect URL.
	 * @param WP_User $user WP_User object.
	 * @return string Redirect URL.
	 * @since 1.2.4 login to view prices redirect url check added
	 */
	public function login_redirect( $redirect, $user ) {
		$view_price_product_list   = wholesalex()->get_setting( '_settings_login_to_view_price_product_list', 'no' );
		$view_price_product_single = wholesalex()->get_setting( '_settings_login_to_view_price_product_page', 'no' );
		$url                       = esc_url_raw( wholesalex()->get_setting( '_settings_redirect_url_login', get_permalink( get_option( 'woocommerce_shop_page_id' ) ) ) );

		if ( 'yes' === $view_price_product_list || 'yes' === $view_price_product_single ) {
			// Only trust the submitted redirect when the login form's own nonce verifies.
			$login_nonce_verified = false;
			if ( isset( $_POST['woocommerce-login-nonce'] ) && is_string( $_POST['woocommerce-login-nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['woocommerce-login-nonce'] ) ), 'woocommerce-login' ) ) {
				$login_nonce_verified = true;
			} elseif ( isset( $_POST['wholesalex-login-nonce'] ) && is_string( $_POST['wholesalex-login-nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wholesalex-login-nonce'] ) ), 'wholesalex-login' ) ) {
				$login_nonce_verified = true;
			} elseif ( isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-login' ) ) {
				$login_nonce_verified = true;
			}

			$product_redirect = $login_nonce_verified && isset( $_POST['redirect'] ) && is_string( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : $url;
			$product_redirect = wp_validate_redirect( $product_redirect, $url );
			$product_redirect = remove_query_arg( array( 'wc_error', 'password-reset' ), $product_redirect );

			if ( ! empty( $product_redirect ) ) {
				$parsed_url          = wp_parse_url( $product_redirect );
				$query_string        = isset( $parsed_url['query'] ) ? $parsed_url['query'] : '';
				$redirect_url_prices = '';

				if ( ! empty( $query_string ) ) {
					parse_str( $query_string, $query_params );
					$redirect_url_prices = isset( $query_params['redirect'] ) ? $query_params['redirect'] : '';
				}

				$redirect_url_view_prices = isset( $_GET['redirect'] ) && is_string( $_GET['redirect'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['redirect'] ) ), '' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This read-only redirect hint is validated against the site's allowed hosts.
				$redirect_url_view_prices = empty( $redirect_url_view_prices ) ? $product_redirect : $redirect_url_view_prices;

				if ( $redirect_url_view_prices ) {
					return $redirect_url_view_prices;
				}
			}
		}

		$redirect = wc_get_page_permalink( 'myaccount' );
		return ( $url ) ? $url : $redirect;
	}


	/**
	 * User Login Option Settings
	 *
	 * @param string $option Option.
	 * @since 1.0.0 Reposition on v1.0..7
	 */
	public function user_login_option( $option ) {
		$__user_login_option = wholesalex()->get_setting( '_settings_user_login_option', 'manual_login' );
		if ( ! empty( $__user_login_option ) ) {
			return $__user_login_option;
		}
		return $option;
	}

	/**
	 * User Status Options
	 *
	 * @param string $option Option.
	 * @param int    $user_id User ID.
	 * @param string $regi_role Registration Role.
	 * @since 1.0.0 Reposition on v1.0..7
	 */
	public function user_status_option( $option, $user_id, $regi_role ) {
		$__user_status_option = wholesalex()->get_setting( '_settings_user_status_option', 'admin_approve' );

		$role_content = wholesalex()->get_roles( 'by_id', $regi_role );

		if ( isset( $role_content['user_status'] ) && 'global_setting' !== $role_content['user_status'] ) {
			return $role_content['user_status'];
		}
		if ( ! empty( $__user_status_option ) ) {
			return $__user_status_option;
		}
		return $option;
	}


	/**
	 * After Registration Form Redirect Settings.
	 *
	 * @param string $redirect_url Redirect Url.
	 * @param int    $user_id User ID.
	 * @param string $registration_role Registration Role.
	 * @since 1.0.0 Reposition on v1.0..7
	 */
	public function after_registration_redirect( $redirect_url, $user_id, $registration_role ) {
		$__redirect_url = wholesalex()->get_setting( '_settings_redirect_url_registration', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );

		if ( ! empty( $__redirect_url ) ) {
			$redirect_url = esc_url_raw( $__redirect_url );
		}

		return $redirect_url;
	}

	/**
	 * After Registration Success Message
	 *
	 * @param string $message Message.
	 * @since 1.0.0 Reposition on v1.0..7
	 */
	public function after_registration_success_message( $message ) {
		$__registration_success_message = wholesalex()->get_setting( '_settings_registration_success_message', __( 'Thank you for registering. Your account will be reviewed by us & approve manually. Please wait to be approved.', 'wholesalex' ) );
		if ( ! empty( $__registration_success_message ) ) {
			$__registration_success_message = esc_html( $__registration_success_message );
			return $__registration_success_message;
		}

		return $message;
	}


	/**
	 * Process User Registration Admin Approval Need Settings
	 *
	 * @param string $user_id User ID.
	 * @return void
	 * @since 1.1.6
	 */
	public function user_registration_admin_approval_need( $user_id ) {
		// Replace any status initialized by customer-creation hooks before applying role approval.
		update_user_meta( $user_id, '__wholesalex_status', 'pending' );
		set_transient( 'wholesalex_registration_approval_required_email_status_' . $user_id, true );
		do_action( 'wholesalex_set_user_approval_needed', $user_id );
	}

	/**
	 * Disable WooCommerce new user email for the users who registered throw the wholesalex registration form.
	 *
	 * @return void
	 * @since 1.1.6
	 */
	public function disable_woocommerce_new_user_email() {
		$is_disable = wholesalex()->get_setting( '_settings_disable_woocommerce_new_user_email' );
		if ( 'yes' === $is_disable ) {
			add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false' );
		}
	}

	/**
	 * Check User Registration Status
	 *
	 * @param mixed $field Field.
	 * @return string
	 * @since 1.1.6
	 */
	public function check_depends( $field ) {
		$exclude_string = '';
		if ( isset( $field['excludeRoles'] ) && ! empty( $field['excludeRoles'] ) && is_array( $field['excludeRoles'] ) ) {
			foreach ( $field['excludeRoles'] as $role ) {
				$exclude_string .= $role['value'] . ' ';
			}
		}
		return $exclude_string;
	}

	/**
	 * Set Custom Fields
	 *
	 * @return void
	 */
	public function set_custom_fields() {
		// Extensions can request fields during plugins_loaded, before translations are ready.
		if ( ! did_action( 'init' ) ) {
			add_action( 'init', array( $this, 'set_custom_fields' ), 0 );
			return;
		}

		$GLOBALS['wholesalex_registration_fields'] = WholesaleX_CommonUtils::get_form_fields();
		$this->woo_custom_fields                   = $GLOBALS['wholesalex_registration_fields']['woo_custom_fields'];
		$this->registration_fields                 = $GLOBALS['wholesalex_registration_fields']['wholesalex_fields'];
	}

	/**
	 * Get existing public registration role IDs from role select options.
	 *
	 * @param array $options Role select options.
	 * @return array
	 */
	protected function get_registration_role_ids_from_options( $options ) {
		$role_ids       = array();
		$existing_roles = array_column( wholesalex()->get_roles( 'store_mode_roles_option' ), 'value' );

		foreach ( (array) $options as $option ) {
			if ( empty( $option['value'] ) || 'wholesalex_guest' === $option['value'] ) {
				continue;
			}

			$role_id = sanitize_text_field( $option['value'] );
			if ( in_array( $role_id, $existing_roles, true ) ) {
				$role_ids[] = $role_id;
			}
		}

		return array_values( array_unique( $role_ids ) );
	}

	/**
	 * Load allowed roles from the submitted form's saved server configuration.
	 *
	 * @return array|null Array of allowed role IDs, or null when the context is missing/invalid.
	 */
	private function get_posted_registration_allowed_roles() {
		$this->authorized_registration_context = Registration_Context::posted();
		return null !== $this->authorized_registration_context ? $this->authorized_registration_context['allowed_roles'] : null;
	}

	/**
	 * Validate a public registration role request.
	 *
	 * @param string     $role_id       Requested WholesaleX role ID.
	 * @param array|null $allowed_roles Allowed role IDs for the current form.
	 * @return bool
	 */
	protected function is_registration_role_allowed( $role_id, $allowed_roles ) {
		if ( empty( $role_id ) ) {
			return true;
		}

		$role_id        = sanitize_text_field( $role_id );
		$existing_roles = array_column( wholesalex()->get_roles( 'store_mode_roles_option' ), 'value' );

		if ( 'wholesalex_guest' === $role_id || ! in_array( $role_id, $existing_roles, true ) ) {
			return false;
		}

		if ( ! is_array( $allowed_roles ) ) {
			return false;
		}

		return in_array( $role_id, $allowed_roles, true );
	}

	/**
	 * Get and authorize the role requested by the custom registration form.
	 *
	 * The regular registration nonce is public and is not an authorization
	 * control. Roles are resolved from the identified form's saved configuration.
	 *
	 * @return string|null Authorized role ID, or null for an invalid request.
	 */
	private function get_authorized_posted_registration_role() {
		$this->authorized_registration_context = null;
		if ( ! isset( $_POST['wholesalex-registration-nonce'] ) || ! is_string( $_POST['wholesalex-registration-nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wholesalex-registration-nonce'] ) ), 'wholesalex-registration' ) ) {
			return null;
		}

		if ( ! isset( $_POST['wholesalex_registration_role'] ) || '' === $_POST['wholesalex_registration_role'] ) {
			return null;
		}

		if ( ! is_string( $_POST['wholesalex_registration_role'] ) ) {
			return null;
		}

		$role_id       = sanitize_text_field( wp_unslash( $_POST['wholesalex_registration_role'] ) );
		$allowed_roles = $this->get_posted_registration_allowed_roles();

		if ( '' === $role_id || ! $this->is_registration_role_allowed( $role_id, $allowed_roles ) ) {
			return null;
		}

		$this->authorized_registration_context['role'] = $role_id;
		return $role_id;
	}


	/**
	 * Process Registration
	 *
	 * @return void
	 * @since 1.0.0 Reposition on v1.0..7
	 * @throws \Exception If the generated string does not meet the criteria.
	 */
	public function process_registration() {

		if ( isset( $_POST['wholesalex-registration-nonce'] ) && is_string( $_POST['wholesalex-registration-nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wholesalex-registration-nonce'] ) ), 'wholesalex-registration' ) ) {
			$data = array(
				'error_messages' => array(),
			);
			if ( isset( $_POST['user_email'] ) ) {

				try {
					$__registration_role = $this->get_authorized_posted_registration_role();
					if ( null === $__registration_role ) {
						$data['error_messages']['other_error'] = __( 'This registration form has expired or the selected role is not allowed. Please reload the page and try again. If the problem continues, contact the store administrator.', 'wholesalex' );
						throw new \Exception();
					}

					$registration_context = $this->authorized_registration_context;

					if ( isset( $_POST['user_pass'] ) && isset( $_POST['user_confirm_pass'] ) && ! ( sanitize_text_field( wp_unslash( $_POST['user_pass'] ) ) === sanitize_text_field( wp_unslash( $_POST['user_confirm_pass'] ) ) ) ) {
						$data['error_messages']['user_pass'] = __( 'Password and Confirm password does not match!', 'wholesalex' );
						wp_send_json_error( $data );
					}
					do_action( 'wholesalex_before_process_user_registration' );

					$user_email = isset( $_POST['user_email'] ) && is_string( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
					$password   = isset( $_POST['user_pass'] ) && is_string( $_POST['user_pass'] ) ? sanitize_text_field( wp_unslash( $_POST['user_pass'] ) ) : '';
					$user_name  = '';

					if ( ! is_email( $user_email ) ) {
						$data['error_messages']['user_email'] = __( 'Enter a valid email address.', 'wholesalex' );
					}
					foreach ( $this->registration_fields as $field ) {
						if ( 'termCondition' === ( $field['type'] ?? '' ) && WholesaleX_CommonUtils::is_standard_registration_field( $field ) && ! empty( $field['required'] ) ) {
							$excluded_roles = isset( $field['excludeRoles'] ) && is_array( $field['excludeRoles'] ) ? array_column( $field['excludeRoles'], 'value' ) : array();
							if ( ! in_array( $__registration_role, $excluded_roles, true ) ) {
								$consent = $_POST[ $field['name'] ] ?? null;
								if ( ! is_string( $consent ) || $field['name'] !== wp_unslash( $consent ) ) {
									$data['error_messages'][ $field['name'] ] = __( 'Please accept the terms and conditions.', 'wholesalex' );
								}
							}
						}
						if ( 'user_pass' === $field['name'] && ! empty( $field['required'] ) && '' === $password ) {
							$data['error_messages']['user_pass'] = __( 'Password is Required!', 'wholesalex' );
							break;
						}
					}
					if ( ! empty( $data['error_messages'] ) ) {
						throw new \Exception();
					}

					// Disable WooCommerce Account Creation Email For wholesalex users.
					add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false' );

					/**
					 * Allow site owners to drop third-party `woocommerce_registration_errors`
					 * validators that conflict with the WholesaleX registration form.
					 *
					 * Nothing is removed by default: clearing the hook outright also disables
					 * anti-spam and security plugins that legitimately validate registrations.
					 * Return an array of callables to remove only the conflicting ones.
					 *
					 * @since 1.0.2
					 * @since 3.0.9 No longer removes every callback by default.
					 *
					 * @param array $callbacks Callbacks to remove, each as array( callable, priority ).
					 */
					$conflicting_validators = (array) apply_filters( 'wholesalex_remove_registration_error_filters', array() );
					foreach ( $conflicting_validators as $validator ) {
						if ( is_array( $validator ) && isset( $validator[0] ) ) {
							remove_filter( 'woocommerce_registration_errors', $validator[0], isset( $validator[1] ) ? (int) $validator[1] : 10 );
						}
					}
					do_action( 'wholesalex_before_create_registered_customer' );

					$userdata   = array();
					$submission = apply_filters(
						'wholesalex_registration_submission',
						array(
							'meta'   => array(),
							'errors' => array(),
						),
						$this->registration_fields
					);
					if ( ! empty( $submission['errors'] ) ) {
						$data['error_messages'] = array_merge( $data['error_messages'], $submission['errors'] );
						throw new \Exception();
					}
					$usermeta = $submission['meta'];

					foreach ( $this->registration_fields as $field ) {
						if ( ! WholesaleX_CommonUtils::is_standard_registration_field( $field ) ) {
							continue; }
						if ( isset( $_POST[ $field['name'] ] ) && ! empty( $_POST[ $field['name'] ] ) ) {
							$value = '';
							switch ( $field['type'] ) {
								case 'text':
								case 'password':
								case 'select':
									$value = sanitize_text_field( wp_unslash( $_POST[ $field['name'] ] ) );
									break;
								case 'textarea':
									$value = sanitize_textarea_field( wp_unslash( $_POST[ $field['name'] ] ) );
									break;
								case 'url':
									$value = sanitize_url( wp_unslash( $_POST[ $field['name'] ] ) );
									break;
								case 'email':
									$value = sanitize_email( wp_unslash( $_POST[ $field['name'] ] ) );
									break;

								default:
									break;
							}

							$length_error = apply_filters( 'wholesalex_registration_field_error', '', $field, $value );
							if ( $length_error ) {
								$data['error_messages'][ $field['name'] ] = $length_error;
								throw new \Exception();
							}

							if ( 'user_login' === $field['name'] ) {
								$user_name = $value;
								continue;
							}
							if ( 'user_pass' === $field['name'] || 'display_name' === $field['name'] || 'nickname' === $field['name'] || 'first_name' === $field['name'] || 'last_name' === $field['name'] ) {
								if ( 'user_pass' === $field['name'] ) {
									$password = $value;
									continue;
								}
								$userdata[ $field['name'] ] = $value;
								continue;
							}
							if ( 'description' === $field['name'] ) {
								$userdata[ $field['name'] ] = $value;
								continue;
							}
							if ( 'url' === $field['name'] ) {
								$userdata['user_url'] = esc_url_raw( $value );
								continue;
							}
							if ( 'user_email' === $field['name'] ) {
								$user_email = $value;
								continue;
							}

							if ( 'wholesalex_registration_role' === $field['name'] ) {
								// Use only the role authorized before any registration hooks run.
								$usermeta['__wholesalex_registration_role'] = $__registration_role;
							}
						}
					}
					if ( $__registration_role ) {
						$usermeta['__wholesalex_registration_role'] = $__registration_role;
					}
					// Set protected provenance after custom fields, before any approval action.
					$usermeta['__wholesalex_registration_context'] = $registration_context;

					$registered_user_id = wc_create_new_customer( $user_email, $user_name, $password, $userdata );
					if ( is_wp_error( $registered_user_id ) ) {

						$errors = $registered_user_id->get_error_codes();
						foreach ( $errors as $error_code ) {
							switch ( $error_code ) {
								case 'registration-error-invalid-email':
								case 'registration-error-email-exists':
									$data['error_messages']['user_email'] = $registered_user_id->get_error_message( $error_code );
									break;
								case 'registration-error-invalid-username':
								case 'registration-error-username-exists':
									$data['error_messages']['user_login'] = $registered_user_id->get_error_message( $error_code );
									break;
								case 'registration-error-missing-password':
									$data['error_messages']['user_pass'] = $registered_user_id->get_error_message( $error_code );
									break;
								default:
									$data['error_messages']['other_error'] = $registered_user_id->get_error_message( $error_code );
									break;
							}
						}

						throw new \Exception();

					} else {
						if ( is_array( $usermeta ) ) {
							foreach ( $usermeta as $key => $value ) {
								if ( 'user_confirm_email' === $key || 'user_confirm_password' === $key ) {
									continue;
								}
								add_user_meta( $registered_user_id, $key, $value );
							}
						}

						do_action( 'wholesalex_registration_customer_created', $registered_user_id, $submission );

						$__user_status_option = apply_filters( 'wholesalex_registration_form_user_status_option', 'admin_approve', $registered_user_id, $__registration_role );

						do_action( 'wholesalex_registration_form_user_status_' . $__user_status_option, $registered_user_id, $__registration_role );

						$__user_login_option = apply_filters( 'wholesalex_registration_form_user_login_option', 'manual_login' );
						do_action( 'wholesalex_registration_form_user_' . $__user_login_option, $registered_user_id, $__registration_role );

						$__redirect_url = apply_filters( 'wholesalex_registration_form_after_registration_redirect_url', wc_get_page_permalink( 'myaccount' ), $registered_user_id, $__registration_role );

						$__redirect_url = add_query_arg( 'wsx-notice', 'regi_success', $__redirect_url );
						$__redirect_url = add_query_arg( 'wsx-nonce', wp_create_nonce( 'wsx_notice' ), $__redirect_url );

						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Apply WooCommerce's existing registration redirect filter for compatibility.
						$data['redirect'] = wp_validate_redirect( apply_filters( 'woocommerce_registration_redirect', $__redirect_url, $registered_user_id ), wc_get_page_permalink( 'myaccount' ) );

						wp_send_json_success( $data );

					}
				} catch ( \Exception $th ) {
					wp_send_json_error( $data );
				}
			} else {
				if ( ! isset( $_POST['user_email'] ) ) {
					$data['error_messages']['user_email'] = __( 'Email is Required!', 'wholesalex' );
				}
				wp_send_json_error( $data );
			}
		} else {
			wp_send_json_error( array( 'error_messages' => array( 'other_error' => __( 'This registration form has expired. Please reload the page and try again.', 'wholesalex' ) ) ) );
		}
	}


	/**
	 * Show Wholesalex Notice
	 *
	 * @return void
	 * @since 1.0.0 Reposition on v1.0..7
	 * @throws \Exception If the generated string does not meet the criteria.
	 */
	public function show_wholesalex_notice() {

		if ( isset( $_GET['wsx-notice'], $_GET['wsx-nonce'] ) && is_string( $_GET['wsx-notice'] ) && is_string( $_GET['wsx-nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['wsx-nonce'] ) ), 'wsx_notice' ) ) {
			$notice_type = sanitize_text_field( wp_unslash( $_GET['wsx-notice'] ) );
			switch ( $notice_type ) {
				case 'regi_success':
					$__success_message = wholesalex()->get_setting( '_settings_registration_success_message', __( 'Thank you for registering. Your account will be reviewed by us & approve manually. Please wait to be approved.', 'wholesalex' ) );
					wc_add_notice( $__success_message, 'success' );

					break;

				default:
					// code...
					break;
			}
		}
	}

	/**
	 * Get Multiselect Values
	 *
	 * @param array $data Array.
	 * @return array
	 */
	public function get_multiselect_values( $data ) {
		$allowed_methods = array();
		foreach ( $data as $method ) {
			$allowed_methods[] = $method['value'];
		}
		return $allowed_methods;
	}
}
