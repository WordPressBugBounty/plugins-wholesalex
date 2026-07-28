<?php
/**
 * WholesaleX Wholesale Pricing - Product Discount Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculates product-discount prices independently from wholesale pricing.
 *
 * Product discounts intentionally have no quantity or order-value restrictions.
 * Product, customer, and schedule eligibility remain the responsibility of the
 * shared wholesale-pricing condition engine.
 */
class Wholesale_Pricing_Product_Discount {

	/**
	 * Calculate the discounted product price.
	 *
	 * @param array       $rule       Normalized product-discount rule.
	 * @param \WC_Product $product    Current product.
	 * @param float       $base_price Price before this rule is applied.
	 * @return float|false Discounted price, or false for invalid settings.
	 */
	public function calculate( array $rule, \WC_Product $product, float $base_price ) {
		$discount_rule = isset( $rule['rule'] ) && is_array( $rule['rule'] ) ? $rule['rule'] : array();

		if ( ! is_numeric( $discount_rule['amount'] ?? null ) || (float) $discount_rule['amount'] <= 0 || $base_price <= 0 ) {
			return false;
		}

		$discount = array(
			'_discount_type'   => isset( $discount_rule['amount_type'] ) ? $discount_rule['amount_type'] : 'percentage',
			'_discount_amount' => $discount_rule['amount'],
		);

		$price = Wholesale_Pricing_Rule_Engine::calculate_discounted_price( $discount, $base_price );

		return false === $price ? false : (float) $price;
	}

	/**
	 * Get the frontend label for the discounted price.
	 *
	 * An explicitly empty label remains empty. The default label is translated
	 * at render time and custom labels can be localized through the filter.
	 *
	 * @param array $rule Normalized product-discount rule.
	 * @return string
	 */
	public function get_label( array $rule ): string {
		$label = isset( $rule['rule']['label'] ) ? trim( (string) $rule['rule']['label'] ) : '';

		if ( '' === $label ) {
			return '';
		}

		if ( 'Product discount' === $label ) {
			return __( 'Product discount', 'wholesalex' );
		}

		return (string) apply_filters( 'wholesalex_product_discount_label', $label, $rule );
	}
}
