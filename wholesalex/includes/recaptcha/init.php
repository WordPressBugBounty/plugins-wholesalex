<?php
/**
 * Recaptcha Integration Init
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'init', 'wholesalex_recaptcha_init' );
/**
 * WholesaleX Recaptcha Init
 *
 * @return void
 * @since 1.0.0
 */
function wholesalex_recaptcha_init() {
	$is_enable  = 'yes' === wholesalex()->get_setting( 'wsx_addon_recaptcha' );
	$site_key   = wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' );
	$secret_key = wholesalex()->get_setting( '_settings_google_recaptcha_v3_secret_key' );
	if ( $is_enable && $site_key && $secret_key ) {
		require_once WHOLESALEX_PATH . 'includes/recaptcha/class-recaptcha.php';
		new \WHOLESALEX\Recaptcha();
	}
}


/**
 * WholesaleX Recaptcha Instance
 *
 * @return Recaptcha WholesaleX Recaptcha Instance.
 * @since 1.0.0
 */
function wholesalex_recaptcha() {
	require_once WHOLESALEX_PATH . 'includes/recaptcha/class-recaptcha.php';
	return WHOLESALEX\Recaptcha::instance();
}
