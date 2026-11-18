<?php
/**
 * WholesaleX settings write operations shared by every entry point.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/admin/class-provider-settings-preservation.php';

/**
 * Saves the WholesaleX settings stored in the `wholesalex_settings` option.
 *
 * The Settings screen sends every field at once; the AI tools send only the
 * fields they change. Both go through `save()`, so the checks below apply to
 * either. Settings owned by add-ons are left to their own resources by
 * `Provider_Settings_Preservation`.
 */
final class Settings_Service {

	/**
	 * Keys only onboarding may change.
	 *
	 * @var string[]
	 */
	const PROTECTED_KEYS = array( '_settings_onboarding_completed', 'isOnboarding' );

	/**
	 * Check changes against the rules every entry point shares.
	 *
	 * @param array $changes Sanitized changes.
	 * @return true|\WP_Error
	 */
	public static function validate( array $changes ) {
		if ( 'yes' === ( $changes['wsx_addon_recaptcha'] ?? '' ) ) {
			$site_key   = $changes['_settings_google_recaptcha_v3_site_key'] ?? wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
			$secret_key = $changes['_settings_google_recaptcha_v3_secret_key'] ?? wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
			if ( empty( $site_key ) || empty( $secret_key ) ) {
				return new \WP_Error( 'wholesalex_settings_recaptcha_keys', __( 'Enter both reCAPTCHA keys in Registration Form > Form Settings before enabling reCAPTCHA.', 'wholesalex' ) );
			}
		}

		if ( 'email_confirmation_require' === ( $changes['_settings_user_status_option'] ?? '' ) && 'no' === get_option( 'wholesalex_email_verification_email_status' ) ) {
			return new \WP_Error(
				'wholesalex_settings_email_confirmation',
				/* translators: %1s - Plugin Name, %2s - Plugin Name,  */
				sprintf( __( 'To Active "Email Confirmation" Please Enable %1$s Email Template from "Dashboard > %2$s > Emails".', 'wholesalex' ), wholesalex()->get_plugin_name(), wholesalex()->get_plugin_name() )
			);
		}

		return true;
	}

	/**
	 * Sanitize, check and store settings changes.
	 *
	 * Only the given keys change; every other stored value stays as it is.
	 *
	 * @param array $changes Changes keyed by setting key.
	 * @return true|\WP_Error
	 */
	public static function save( array $changes ) {
		$changes = wholesalex()->sanitize( $changes );
		$valid   = self::validate( $changes );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		// Completion is managed by onboarding, not stale settings forms.
		foreach ( self::PROTECTED_KEYS as $key ) {
			unset( $changes[ $key ] );
		}
		$changes = Admin\Provider_Settings_Preservation::prepare( $changes );
		wholesalex()->set_setting_multiple( $changes );

		return true;
	}
}
