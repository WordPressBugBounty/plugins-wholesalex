<?php
/**
 * Group Action.
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;
/**
 * WholesaleX RequestAPI Class.
 */
class WHOLESALEX_Request_API {

	/**
	 * RequestAPI Constructor
	 */
	public function __construct() {
		// Addon Enable - Disable.
		add_action( 'wp_ajax_wsx_addon', array( $this, 'addon_active_callback' ) );
	}
	/**
	 * Addon Activation Callback
	 *
	 * @return void
	 * @since 1.0.0
	 * @since 1.0.5 Added wholesalex_{addon_name}_before_active and after_active hook.
	 *              Added wholesalex_{addon_name}_error Filter Hook
	 */
	public function addon_active_callback() {
		if ( ! current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) ) ) {
			wp_send_json_error( __( 'You are not allowed to manage addons.', 'wholesalex' ), 403 );
			return;
		}
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! is_string( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wholesalex-registration' ) ) {
			wp_send_json_error( __( 'Invalid addon request.', 'wholesalex' ), 403 );
			return;
		}
		$addon_name  = isset( $_POST['addon'] ) && is_string( $_POST['addon'] ) ? sanitize_key( wp_unslash( $_POST['addon'] ) ) : '';
		$addon_value = isset( $_POST['value'] ) && is_string( $_POST['value'] ) ? sanitize_key( wp_unslash( $_POST['value'] ) ) : '';
		// This legacy Free adapter can only update genuine Free addon controls.
		if ( ! in_array( $addon_name, array( 'wsx_addon_recaptcha' ), true ) || ! in_array( $addon_value, array( 'yes', 'no' ), true ) ) {
			wp_send_json_error( __( 'Unknown Free addon or invalid value.', 'wholesalex' ), 422 );
			return;
		}
		require_once __DIR__ . '/admin/class-provider-settings-preservation.php';
		\WHOLESALEX\Admin\Provider_Settings_Preservation::prepare( array() );
		if ( 'yes' === $addon_value && ( ! wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' ) || ! wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' ) ) ) {
			wp_send_json_error( __( 'Enter both reCAPTCHA keys before enabling reCAPTCHA.', 'wholesalex' ), 422 );
			return;
		}
		$error = apply_filters( 'wholesalex_' . $addon_name . '_error', '', $addon_value );
		if ( '' !== $error ) {
			wp_send_json_error( $error, 422 );
			return; }
		do_action( 'wholesalex_' . $addon_name . '_before_status_update', $addon_value );
		$addon_data                = (array) get_option( 'wholesalex_settings', array() );
		$addon_data[ $addon_name ] = $addon_value;
		update_option( 'wholesalex_settings', $addon_data );
		$GLOBALS['wholesalex_settings'] = $addon_data;
		if ( get_option( 'wholesalex_settings', array() ) !== $addon_data ) {
			wp_send_json_error( __( 'Addon could not be saved.', 'wholesalex' ), 500 );
			return;
		}
		do_action( 'wholesalex_' . $addon_name . '_after_status_update', $addon_value );
		wp_send_json_success();
	}
}
