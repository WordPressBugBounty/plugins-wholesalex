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
	$is_enable = 'yes' === wholesalex()->get_setting( 'wsx_addon_recaptcha' );
	if ( $is_enable ) {
		require_once WHOLESALEX_PATH . 'includes/recaptcha/class-recaptcha.php';
		\WHOLESALEX\Recaptcha::instance();
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
