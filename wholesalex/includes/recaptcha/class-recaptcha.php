<?php
/**
 * WholesaleX Recaptcha
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

use Exception;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WholesaleX Recaptcha Class
 *
 * @since 1.0.0
 */
class Recaptcha {
	/**
	 * Verification results shared by login hooks during this request only.
	 *
	 * @var array
	 */
	private $verification_results = array();


	/**
	 * Shortcodes Constructor
	 */
	public function __construct() {
		if ( 'yes' !== wholesalex()->get_setting( 'wsx_addon_recaptcha' ) ) {
			return;
		}
		add_action( 'wholesalex_before_process_user_registration', array( $this, 'add_recaptcha_on_registration_form' ), 10 );
		add_filter( 'woocommerce_registration_errors', array( $this, 'validate_wc_registration' ), 10, 3 );
		if ( ! wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' ) || ! wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' ) || ! in_array( wholesalex()->get_setting( 'recaptcha_version' ), array( 'recaptcha_v2', 'recaptcha_v3' ), true ) ) {
			return;
		}
		add_filter( 'wholesalex_register_scripts', array( $this, 'register_recaptcha' ) );
		add_action( 'wholesalex_before_process_user_login', array( $this, 'add_recaptcha_on_login_form' ), 10 );
		add_action( 'wholesalex_before_process_conversation', array( $this, 'add_recaptcha' ), 10, 1 );
		add_action( 'wholesalex_before_registration_form_render', array( $this, 'add_recaptcha_script' ) );
		add_action( 'wholesalex_conversation_metabox_content', array( $this, 'add_recaptcha_script' ) );
		add_action( 'wholesalex_conversation_metabox_content_account_page', array( $this, 'add_recaptcha_script' ) );
		add_action( 'wholesalex_before_conversation_create', array( $this, 'add_recaptcha' ) );
		add_action( 'wholesalex_before_conversation_reply', array( $this, 'add_recaptcha' ) );
		/**
		 * WooCommerce Login Recaptcha Verification
		 *
		 * @since 1.0.1
		 */
		add_action( 'woocommerce_login_form', array( $this, 'process_recaptcha_script' ) );
		add_filter( 'wp_authenticate_user', array( $this, 'recaptcha_on_wc_login' ) );
		add_action( 'woocommerce_login_form', array( $this, 'add_recaptcha_v2_to_login_form' ) );
		add_action( 'woocommerce_register_form', array( $this, 'add_recaptcha_v2_to_registration_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_recaptcha_v2_script_for_registration' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'add_recaptcha_v2_login' ) );
		add_action( 'login_form', array( $this, 'add_recaptcha_v2_widget' ) );
		add_action( 'wholesalex_login_form', array( $this, 'add_recaptcha_v2_widget' ) );
		add_action( 'wholesalex_login_form', array( $this, 'add_recaptcha_wholesalex_login' ) );
		add_action( 'wholesalex_registration_form', array( $this, 'add_recaptcha_v2_to_registration_form' ) );
	}

	/**
	 * Build the reCAPTCHA form integration script without heredoc syntax.
	 *
	 * Dynamic values are JSON encoded before being embedded in JavaScript.
	 *
	 * @param string      $form_selector Form selector.
	 * @param string      $button_name   Submit button name.
	 * @param string      $button_value  Submit button value.
	 * @param string|null $site_key      Optional site key. Uses the existing JavaScript variable when null.
	 * @return string
	 */
	private function get_recaptcha_form_script( $form_selector, $button_name, $button_value, $site_key = null ) {
		$lines = array(
			'jQuery(document).ready(function($) {',
			'    $(' . wp_json_encode( $form_selector ) . ').each(function() {',
			'        if ($(this).find(".g-recaptcha").length) { return; }',
			'        $("<div>").addClass("g-recaptcha").attr("data-sitekey", recaptchaSiteKey)',
			'            .insertAfter($(this).find(".woocommerce-form__input").last());',
			'    });',
			'',
			'    $(' . wp_json_encode( $form_selector ) . ').submit(function(e) {',
			'        if ($(this).find(".g-recaptcha-response").val() === "") {',
			'            e.preventDefault();',
			'            alert("Please complete the reCAPTCHA checkbox.");',
			'            return false;',
			'        }',
			'        $("<input>")',
			'            .attr({',
			'                name: ' . wp_json_encode( $button_name ) . ',',
			'                id: ' . wp_json_encode( $button_name ) . ',',
			'                type: "hidden",',
			'                value: ' . wp_json_encode( $button_value ),
			'            })',
			'            .appendTo(this);',
			'    });',
			'});',
		);

		if ( null !== $site_key ) {
			array_splice( $lines, 1, 0, '    var recaptchaSiteKey = ' . wp_json_encode( $site_key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';' );
		}

		return implode( "\n", $lines );
	}

	/**
	 * Enqueue reCAPTCHA and attach it to the WholesaleX registration form.
	 *
	 * @return void
	 */
	public function add_recaptcha_wholesalex_login() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			wp_enqueue_script(
				'wholesalex-google-recaptcha-v2',
				'https://www.google.com/recaptcha/api.js',
				array(),
				WHOLESALEX_VER,
				true
			);
		}

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
				wp_enqueue_script( 'wholesalex-google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js', array(), WHOLESALEX_VER, true );
				$inline_script = $this->get_recaptcha_form_script(
					'form.woocommerce-form.woocommerce-form-register.register',
					'register',
					'Register',
					$site_key
				);

				// Add inline script to the enqueued reCAPTCHA script.
				wp_add_inline_script( 'wholesalex-google-recaptcha-v2', $inline_script );
		}
	}



	/**
	 * Enqueue the configured reCAPTCHA v2 login script.
	 *
	 * @return void
	 */
	public function add_recaptcha_v2_login() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			wp_enqueue_script(
				'wholesalex-google-recaptcha-v2',
				'https://www.google.com/recaptcha/api.js',
				array(),
				WHOLESALEX_VER,
				true
			);
		}
	}

	/**
	 * Render the configured reCAPTCHA v2 widget.
	 *
	 * @return void
	 */
	public function add_recaptcha_v2_widget() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			?>
			<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<br>
			<?php
		}
	}

	/**
	 * Render reCAPTCHA v2 on the login form.
	 *
	 * @return void
	 */
	public function add_recaptcha_v2_to_login_form() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			?>
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
				<br>
			<?php
		}
	}

	/**
	 * Enqueue and initialize reCAPTCHA v2 for account registration.
	 *
	 * @return void
	 */
	public function enqueue_recaptcha_v2_script_for_registration() {
		if ( is_page( 'my-account' ) || is_wc_endpoint_url( 'register' ) ) {
			$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
			$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

			if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
				wp_enqueue_script( 'wholesalex-google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js', array(), WHOLESALEX_VER, true );
				$inline_script = $this->get_recaptcha_form_script(
					'form.woocommerce-form.woocommerce-form-register.register',
					'register',
					'Register',
					$site_key
				);

				// Add inline script to the enqueued reCAPTCHA script.
				wp_add_inline_script( 'wholesalex-google-recaptcha-v2', $inline_script );
			}
		}
	}

	/**
	 * Render reCAPTCHA v2 on the registration form.
	 *
	 * @return void
	 */
	public function add_recaptcha_v2_to_registration_form() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v2' === $recaptcha_version && ! empty( $site_key ) ) {
			?>
			<div style="margin-top:15px;" class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<br>
			<?php
		}
	}
	/**
	 * Contains instance of this class.
	 *
	 * @var Recaptcha
	 * @since 1.0.0
	 */

	protected static $_instance = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Keep the established singleton property for subclasses.


	/**
	 * Recaptcha instance
	 *
	 * @return class object
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * Validate a registration challenge using verified service fields only.
	 *
	 * @param array $post Unslashed submitted form.
	 * @return true|WP_Error
	 */
	public function validate_registration( $post ) {
		if ( 'yes' !== wholesalex()->get_setting( 'wsx_addon_recaptcha' ) ) {
			return true;
		}
		$version = wholesalex()->get_setting( 'recaptcha_version' );
		$secret  = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
		$site    = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
		$field   = 'recaptcha_v2' === $version ? 'g-recaptcha-response' : 'token';
		$token   = $post[ $field ] ?? null;
		$error   = new WP_Error( 'recaptcha_error', __( 'Verification failed. Please complete the challenge and try again.', 'wholesalex' ) );
		if ( ! in_array( $version, array( 'recaptcha_v2', 'recaptcha_v3' ), true ) || ! is_string( $secret ) || '' === $secret || ! is_string( $site ) || '' === $site || ! is_string( $token ) || '' === trim( $token ) || strlen( $token ) > 8192 ) {
			return $error;
		}
		$key = hash( 'sha256', 'registration:' . $version . ':' . $secret . ':' . $token );
		if ( isset( $this->verification_results[ $key ] ) ) {
			return $this->verification_results[ $key ];
		}
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout'     => 5,
				'redirection' => 0,
				'body'        => array(
					'secret'   => $secret,
					'response' => $token,
				),
			)
		);
		$parsed   = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
		$valid    = ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) && is_array( $parsed ) && true === ( $parsed['success'] ?? null );
		$host     = wp_parse_url( home_url(), PHP_URL_HOST );
		$valid    = $valid && is_string( $host ) && isset( $parsed['hostname'] ) && is_string( $parsed['hostname'] ) && strtolower( $host ) === strtolower( $parsed['hostname'] );
		if ( 'recaptcha_v3' === $version ) {
			$minimum = apply_filters( 'wholesalex_recaptcha_minimum_score_allow', 0.5 );
			$score   = $parsed['score'] ?? null;
			$valid   = $valid && 'submit' === ( $parsed['action'] ?? null ) && ( is_float( $score ) || is_int( $score ) ) && is_finite( (float) $score ) && $score >= 0 && $score <= 1 && is_numeric( $minimum ) && $minimum >= 0 && $minimum <= 1 && $score >= $minimum;
		}
		if ( 'recaptcha_v2' === $version ) {
			$valid = $valid && ! array_key_exists( 'score', $parsed ?? array() ) && ! array_key_exists( 'action', $parsed ?? array() );
		}
		$this->verification_results[ $key ] = $valid ? true : $error;
		return $this->verification_results[ $key ];
	}

	/**
	 * Consume the same verification contract before WooCommerce creation.
	 *
	 * @param WP_Error $errors Existing registration errors.
	 * @return WP_Error
	 */
	public function validate_wc_registration( $errors ) {
		$nonce = null;
		if ( isset( $_POST['woocommerce-register-nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Validated below to identify the WooCommerce registration form.
			$nonce = is_string( $_POST['woocommerce-register-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce-register-nonce'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Validated below before use.
		} elseif ( isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Validated below before use.
			$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Validated below before use.
		}
		if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'woocommerce-register' ) ) {
			// Checkout/API customer creation does not render this registration widget.
			return $errors;
		}
		$result = $this->validate_registration( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the originating form before creation.
		if ( is_wp_error( $result ) ) {
			$errors->add( $result->get_error_code(), $result->get_error_message() );
		}
		return $errors;
	}

	/**
	 * Parse recaptcha Response
	 *
	 * @param string $response reCaptcha response token.
	 * @return array
	 * @since 1.0.0
	 */
	private function parse_recaptcha_response( $response ) {
		$secret_key = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
		$cache_key  = hash( 'sha256', $secret_key . ':' . $response );
		if ( isset( $this->verification_results[ $cache_key ] ) ) {
			return $this->verification_results[ $cache_key ];
		}

		// Tokens are single-use. The pre-login action and authentication filter
		// must share the result, never verify the same token twice with Google.
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret'   => $secret_key,
					'response' => $response,
				),
			)
		);
		$error    = array(
			'success'     => false,
			'error-codes' => array( 'verification-unavailable' ),
		);
		$parsed   = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $parsed ) || ! isset( $parsed['success'] ) ) {
			$parsed = $error;
		}
		if ( ! empty( $parsed['success'] ) && ( ! isset( $parsed['score'] ) || ! is_numeric( $parsed['score'] ) ) ) {
			$parsed = array(
				'success'     => false,
				'error-codes' => array( 'invalid-score' ),
			);
		}
		if ( ! empty( $parsed['success'] ) && $parsed['score'] < apply_filters( 'wholesalex_recaptcha_minimum_score_allow', 0.5 ) ) {
			$parsed['error-codes'] = array( 'low-score' );
		}
		if ( ! empty( $parsed['success'] ) && ! $this->is_site_response( $parsed, true ) ) {
			$parsed = array(
				'success'     => false,
				'error-codes' => array( 'invalid-site-response' ),
			);
		}
		$this->verification_results[ $cache_key ] = $parsed;
		return $parsed;
	}

	/**
	 * Confirm a verified response was issued for this site (and the expected action).
	 *
	 * Registration applies the same hostname and action checks, so every
	 * reCAPTCHA entry point accepts the same tokens.
	 *
	 * @param array $parsed       Decoded siteverify response.
	 * @param bool  $check_action Whether the v3 action must be "submit".
	 * @return bool
	 */
	private function is_site_response( $parsed, $check_action ) {
		$hosts = (array) apply_filters( 'wholesalex_recaptcha_allowed_hostnames', array( wp_parse_url( home_url(), PHP_URL_HOST ) ) );
		$hosts = array_map( 'strtolower', array_filter( $hosts, 'is_string' ) );
		if ( ! isset( $parsed['hostname'] ) || ! is_string( $parsed['hostname'] ) || ! in_array( strtolower( $parsed['hostname'] ), $hosts, true ) ) {
			return false;
		}
		return ! $check_action || 'submit' === ( $parsed['action'] ?? null );
	}

	/**
	 * Recaptcha Error Code
	 *
	 * @param string $code recaptcha error code.
	 * @return string Error Message.
	 * @since 1.0.0
	 */
	private function recaptcha_error_message( $code ) {
		switch ( $code ) {
			case 'verification-unavailable':
				return __( 'Verification is temporarily unavailable. Please try again.', 'wholesalex' );
			case 'invalid-score':
				return __( 'No valid v3 score was returned. Please check the configured reCAPTCHA version and keys.', 'wholesalex' );
			case 'low-score':
				return __( 'Verification did not meet the required score. Please try again.', 'wholesalex' );
			case 'invalid-site-response':
				return __( 'Verification was not issued for this site. Please reload the page and try again.', 'wholesalex' );
			case 'missing-input-secret':
				return __( 'The secret parameter is missing.', 'wholesalex' );
			case 'invalid-input-secret':
				return __( 'The secret parameter is invalid or malformed.', 'wholesalex' );
			case 'missing-input-response':
				return __( 'The response parameter is missing.', 'wholesalex' );
			case 'invalid-input-response':
				return __( 'The response parameter is invalid or malformed.', 'wholesalex' );
			case 'bad-request':
				return __( 'The request is invalid or malformed.', 'wholesalex' );
			case 'timeout-or-duplicate':
				return __( 'The response is no longer valid: either is too old or has been used previously.', 'wholesalex' );
			default:
				return __( 'Unknown!', 'wholesalex' );
		}
	}

	/**
	 * WholesaleX Process Recaptcha in user registration form
	 *
	 * @param object $post_data User Input Data.
	 * @throws Exception Recaptcha Error.
	 * @since 1.0.0
	 */
	public function add_recaptcha( $post_data ) {
		$__site_key        = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
		$__secret_key      = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
		$__token           = isset( $post_data['token'] ) && is_string( $post_data['token'] ) ? $post_data['token'] : '';
		$__minimum_score   = apply_filters( 'wholesalex_recaptcha_minimum_score_allow', 0.5 );
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		if ( $__site_key && $__secret_key && 'recaptcha_v3' === $recaptcha_version ) {
			$parsed_response = $this->parse_recaptcha_response( $__token );
			if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] && ( $parsed_response['score'] ?? 0 ) >= $__minimum_score ) ) {
				$error_header = __( 'reCAPTCHA v3:', 'wholesalex' );
				if ( function_exists( 'wc_add_notice' ) && wp_doing_ajax() ) {
					try {
						wc_add_notice( $error_header . $this->recaptcha_error_message( $parsed_response['error-codes'][0] ?? '' ), 'error' );
						wp_send_json_error( array( 'messages' => wc_print_notices( true ) ) );
					} catch ( Exception $e ) {
						throw new Exception( 'reCAPTCHA v3 Error!' );
					}
				} else {
					$referer = isset( $post_data['_wp_http_referer'] ) && is_string( $post_data['_wp_http_referer'] ) ? wp_unslash( $post_data['_wp_http_referer'] ) : wp_get_referer();
					wp_safe_redirect( esc_url_raw( $referer ? $referer : home_url( '/' ) ) );
					exit();
				}
			}
		}
	}

	/**
	 * WholesaleX Process Recaptcha in user registration form
	 *
	 * @since 1.0.0
	 */
	public function add_recaptcha_on_registration_form() {
		$result = $this->validate_registration( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Called only after the registration handler verifies its nonce.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'error_messages' => array( 'recaptcha' => $result->get_error_message() ) ) );
		}
		return $result;
	}

	/**
	 * Validate an opaque response without changing bytes sent to its verifier.
	 *
	 * @param mixed $token Submitted response.
	 * @return string|false Unchanged bounded token, or false for invalid input.
	 */
	private function validate_response_token( $token ) {
		return is_string( $token ) && '' !== trim( $token ) && strlen( $token ) <= 8192 ? $token : false;
	}

	/**
	 * Verify a v2 login token once per request, independently of the login URL.
	 *
	 * @param mixed $token Submitted response token.
	 * @return true|WP_Error
	 */
	private function validate_v2_login( $token ) {
		$error = new WP_Error( 'recaptcha_error', __( '<strong>reCAPTCHA v2:</strong> Invalid reCAPTCHA. Please check the box.', 'wholesalex' ) );
		if ( false === $this->validate_response_token( $token ) ) {
			return $error;
		}
		$secret = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
		$key    = hash( 'sha256', 'login:recaptcha_v2:' . $secret . ':' . $token );
		if ( isset( $this->verification_results[ $key ] ) ) {
			return $this->verification_results[ $key ];
		}
		$response                           = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout'     => 5,
				'redirection' => 0,
				'body'        => array(
					'secret'   => $secret,
					'response' => $token,
				),
			)
		);
		$parsed                             = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
		$valid                              = ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) && is_array( $parsed ) && true === ( $parsed['success'] ?? null ) && $this->is_site_response( $parsed, false );
		$this->verification_results[ $key ] = $valid ? true : $error;
		return $this->verification_results[ $key ];
	}

	/**
	 * Validate reCAPTCHA before processing a WholesaleX login.
	 */
	public function add_recaptcha_on_login_form() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );

		if ( 'recaptcha_v3' === $recaptcha_version ) {
			if ( isset( $_POST['wholesalex-login-nonce'] ) && is_string( $_POST['wholesalex-login-nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wholesalex-login-nonce'] ) ), 'wholesalex-login' ) ) {
				$data = array(
					'error_messages' => array(),
				);

				$__site_key      = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
				$__secret_key    = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
				$__token         = isset( $_POST['token'] ) && is_string( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
				$__minimum_score = apply_filters( 'wholesalex_recaptcha_minimum_score_allow', 0.5 );

				if ( $__site_key && $__secret_key ) {
					// A missing token means the verification never ran, so the login must not continue.
					if ( '' === $__token ) {
						$error_header                        = __( 'reCAPTCHA v3:', 'wholesalex' );
						$data['error_messages']['recaptcha'] = wc_print_notice( $error_header . __( ' Verification is required. Please reload the page and try again.', 'wholesalex' ), 'error', array(), true );
						wp_send_json_error( $data );
					}

					$parsed_response = $this->parse_recaptcha_response( $__token );
					if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] && $parsed_response['score'] >= $__minimum_score ) ) {
						$error_code                          = isset( $parsed_response['error-codes'][0] ) ? $parsed_response['error-codes'][0] : '';
						$error_header                        = __( 'reCAPTCHA v3:', 'wholesalex' );
						$data['error_messages']['recaptcha'] = wc_print_notice( $error_header . ' ' . $this->recaptcha_error_message( $error_code ), 'error', array(), true );
						wp_send_json_error( $data );
					}
				}
			}
		} elseif ( 'recaptcha_v2' === $recaptcha_version && isset( $_POST['wholesalex-login-nonce'] ) && is_string( $_POST['wholesalex-login-nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wholesalex-login-nonce'] ) ), 'wholesalex-login' ) ) {
			// Preserve the intentional administrator recovery exemption. WordPress
			// still checks the submitted password when wp_signon() runs.
			$username = isset( $_POST['username'] ) && is_string( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
			$user     = get_user_by( 'login', trim( $username ) );
			if ( ! $user && is_email( trim( $username ) ) ) {
				$user = get_user_by( 'email', trim( $username ) );
			}
			if ( $user instanceof \WP_User && in_array( 'administrator', (array) $user->roles, true ) ) {
				return;
			}
			$token  = isset( $_POST['g-recaptcha-response'] ) && is_string( $_POST['g-recaptcha-response'] ) ? filter_var( wp_unslash( $_POST['g-recaptcha-response'] ), FILTER_CALLBACK, array( 'options' => array( $this, 'validate_response_token' ) ) ) : null;
			$result = $this->validate_v2_login( $token );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'error_messages' => array( 'recaptcha' => wc_print_notice( $result->get_error_message(), 'error', array(), true ) ) ) );
			}
		}
	}

	/**
	 * Add Recaptcha Script in user registration form
	 *
	 * @since 1.0.0
	 */
	public function add_recaptcha_script() {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );

		if ( 'recaptcha_v3' === $recaptcha_version ) {
			wp_enqueue_script( 'wholesalex-google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $site_key, array(), WHOLESALEX_VER, true );
			wp_add_inline_script( 'wholesalex-google-recaptcha-v3', 'var recaptchaSiteKey=' . wp_json_encode( $site_key ) );
		} else {
			if ( ! $site_key ) {
				return;
			}

			wp_enqueue_script(
				'wholesalex-google-recaptcha-v2',
				'https://www.google.com/recaptcha/api.js',
				array(),
				WHOLESALEX_VER,
				true
			);
			wp_add_inline_script(
				'wholesalex-google-recaptcha-v2',
				'var recaptchaSiteKey=' . wp_json_encode( $site_key )
			);
		}
	}

	/**
	 * Register Google Recaptcha Script
	 *
	 * @param array $scripts Scripts Array.
	 * @since 1.0.0
	 */
	public function register_recaptcha( $scripts ) {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$site_key          = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
		if ( ! $site_key ) {
			return $scripts;
		}
		if ( 'recaptcha_v3' === $recaptcha_version ) {

			$scripts['wholesalex-google-recaptcha-v3'] = array(
				'src'       => 'https://www.google.com/recaptcha/api.js?render=' . $site_key,
				'deps'      => array(),
				'ver'       => WHOLESALEX_VER,
				'in_footer' => true,
			);
		} else {
			$scripts['wholesalex-google-recaptcha-v2'] = array(
				'src'       => 'https://www.google.com/recaptcha/api.js',
				'deps'      => array(),
				'ver'       => WHOLESALEX_VER,
				'in_footer' => true,
			);

			$inline_script = $this->get_recaptcha_form_script(
				'form.woocommerce-form.woocommerce-form-login.login',
				'login',
				'Login'
			);

			wp_add_inline_script( 'wholesalex-google-recaptcha-v2', $inline_script );
		}

		return $scripts;
	}

	/**
	 * Process Recaptcha on WooCommerce Login
	 *
	 * @param WP_User|WP_Error $user     WP_User or WP_Error object if a previous
	 *                                   callback failed authentication.
	 * @since 1.0.1
	 */
	public function recaptcha_on_wc_login( $user ) {
		$recaptcha_version = wholesalex()->get_setting( 'recaptcha_version' );
		$__site_key        = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
		$__secret_key      = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
		$__token           = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Authentication callbacks do not receive a plugin-specific nonce.
		$__minimum_score   = apply_filters( 'wholesalex_recaptcha_minimum_score_allow', 0.5 );

		// The reCAPTCHA script is injected into the WooCommerce login form, so a
		// request carrying that form's nonce must also carry a token. Other login
		// flows (wp-login.php, XML-RPC, application passwords) never receive one.
		$is_wc_login_form = false;
		// WooCommerce accepts both request nonce names, with the primary taking precedence.
		$login_nonce = isset( $_REQUEST['woocommerce-login-nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['woocommerce-login-nonce'] ) ) : ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified immediately below.
		if ( is_string( $login_nonce ) && wp_verify_nonce( sanitize_key( $login_nonce ), 'woocommerce-login' ) ) {
			$is_wc_login_form = true;
		}
		$recaptcha_active = 'yes' === wholesalex()->get_setting( 'wsx_addon_recaptcha' );

		if ( 'recaptcha_v3' === $recaptcha_version ) {
			if ( $__site_key && $__secret_key ) {
				if ( '' === $__token ) {
					if ( $recaptcha_active && $is_wc_login_form ) {
						return new WP_Error(
							'recaptcha_error',
							__( '<strong>reCAPTCHA v3:</strong> Verification is required. Please reload the page and try again.', 'wholesalex' )
						);
					}
				} else {
					$parsed_response = $this->parse_recaptcha_response( $__token );

					if ( ! ( isset( $parsed_response['success'] ) && $parsed_response['success'] && $parsed_response['score'] >= $__minimum_score ) ) {

						return new WP_Error(
							'recaptcha_error',
							'<strong>' . esc_html__( 'reCAPTCHA v3:', 'wholesalex' ) . '</strong> ' . $this->recaptcha_error_message( $parsed_response['error-codes'][0] ?? '' )
						);
					}
				}
			}
		} elseif ( 'recaptcha_v2' === $recaptcha_version && $__site_key && $__secret_key ) {
			if ( is_wp_error( $user ) || ( $user instanceof \WP_User && in_array( 'administrator', (array) $user->roles, true ) ) ) {
				return $user;
			}
			// Only forms that render the v2 widget can answer it. XML-RPC, REST and other
			// plugins' logins never receive one, so requiring it there would only lock them out.
			$is_plugin_login_form = false;
			if ( isset( $_POST['wholesalex-login-nonce'] ) && is_string( $_POST['wholesalex-login-nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wholesalex-login-nonce'] ) ), 'wholesalex-login' ) ) {
				$is_plugin_login_form = true;
			}
			$is_widget_form = $is_wc_login_form || $is_plugin_login_form || ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] );
			if ( ! $is_widget_form ) {
				return $user;
			}
			$token  = isset( $_POST['g-recaptcha-response'] ) && is_string( $_POST['g-recaptcha-response'] ) ? filter_var( wp_unslash( $_POST['g-recaptcha-response'] ), FILTER_CALLBACK, array( 'options' => array( $this, 'validate_response_token' ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Authentication callbacks do not receive a plugin-specific nonce.
			$result = $this->validate_v2_login( $token );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return $user;
	}

	/**
	 * Process Recaptcha Script
	 */
	public function process_recaptcha_script() {
		if ( 'yes' === wholesalex()->get_setting( 'wsx_addon_recaptcha' ) && 'recaptcha_v3' === wholesalex()->get_setting( 'recaptcha_version' ) ) {
			$site_key = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
			if ( ! $site_key ) {
				return;
			}
			// Install the submit guard before loading Google's API; neither script depends on jQuery.
			wp_enqueue_script( 'wholesalex-recaptcha-v3-login', WHOLESALEX_URL . 'includes/recaptcha/login-v3.js', array(), filemtime( WHOLESALEX_PATH . 'includes/recaptcha/login-v3.js' ), true );
			wp_add_inline_script(
				'wholesalex-recaptcha-v3-login',
				'window.wholesalexRecaptchaLogin = ' . wp_json_encode(
					array(
						'siteKey'      => $site_key,
						'errorMessage' => __( 'reCAPTCHA verification could not be completed. Please try again.', 'wholesalex' ),
					),
					JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
				) . ';',
				'before'
			);
			$this->add_recaptcha_script();
			return;
		}
		$this->add_recaptcha_script();
		$site_key = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
		if ( 'yes' !== wholesalex()->get_setting( 'wsx_addon_recaptcha' ) || ! $site_key ) {
			return;
		}
		wp_enqueue_script( 'jquery' );
		wp_add_inline_script(
			'wholesalex-google-recaptcha-v2',
			$this->get_recaptcha_form_script( 'form.woocommerce-form.woocommerce-form-login.login', 'login', 'Login', $site_key )
		);
	}
}
