<?php
/**
 * Class xpo.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Core class for managing plugin actions and integrations.
 *
 * @package WHOLESALEX
 */
class Xpo {

	/**
	 * Get the pricing URL.
	 *
	 * @param array $params Legacy campaign parameters retained for compatibility.
	 * @return string
	 */
	public static function generate_utm_link( $params ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Preserve the public method signature.
		return 'https://getwholesalex.com/pricing/';
	}


	/**
	 * Get WOW Products Details.
	 *
	 * @return array
	 */
	public static function get_wow_products_details() {
		// Preserve the bundle's data shape without inspecting unrelated plugins.
		return array(
			'products'        => array(),
			'products_active' => array(),
		);
	}
}
