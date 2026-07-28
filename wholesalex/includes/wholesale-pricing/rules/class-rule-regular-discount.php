<?php
/**
 * WholesaleX Wholesale Pricing - Regular Discount Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles regular wholesale-pricing discounts.
 */
class Wholesale_Pricing_Regular_Discount {

	/**
	 * Calculate the discounted price for a regular wholesale-pricing rule.
	 *
	 * @param array       $rule       Normalized wholesale-pricing rule entry.
	 * @param \WC_Product $product    Current product.
	 * @param float       $base_price Price before this rule is applied.
	 * @return float|false Discounted price, or false when no valid discount exists.
	 */
	public function calculate( array $rule, \WC_Product $product, float $base_price ) {
		if ( empty( $rule['rule']['amount'] ) || $base_price <= 0 ) {
			return false;
		}

		$discount = array(
			'_discount_type'   => isset( $rule['rule']['amount_type'] ) ? $rule['rule']['amount_type'] : 'percentage',
			'_discount_amount' => $rule['rule']['amount'],
		);

		$price = Wholesale_Pricing_Rule_Engine::calculate_discounted_price( $discount, $base_price );

		return false === $price ? false : (float) $price;
	}

	/**
	 * Return the label used when a regular discount needs a display name.
	 *
	 * @param array $rule Normalized wholesale-pricing rule entry.
	 * @return string
	 */
	public function get_label( array $rule ): string {
		if ( ! empty( $rule['rule']['label'] ) ) {
			return $rule['rule']['label'];
		}

		return __( 'Wholesale price:', 'wholesalex' );
	}
}
