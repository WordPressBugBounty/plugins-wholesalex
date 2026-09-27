<?php
/**
 * Shared tier pricing calculation, including historical saved records.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX\Pricing;

defined( 'ABSPATH' ) || exit;
/**
 * Calculate the applicable price from saved tiers.
 */
class Tier_Pricing_Calculator {
	/**
	 * Evaluate tiers against the current price and quantity.
	 *
	 * @param array $tiers Configured tier records.
	 * @param array $context Pricing context or runtime evaluation data.
	 * @return array
	 */
	public function evaluate( array $tiers, array $context ): array {
		$result = array(
			'status'     => 'no_match',
			'price'      => false,
			'id'         => false,
			'tier_index' => null,
			'reason'     => '',
		);
		if ( empty( $tiers ) ) {
			return $result;
		}
		// Preserve the existing sorting and last-matching-tier semantics.
		array_multisort( array_column( $tiers, '_min_quantity' ), SORT_ASC, $tiers );
		foreach ( $tiers as $index => $tier ) {
			if ( ! isset( $tier['_discount_type'], $tier['_discount_amount'], $tier['_min_quantity'] ) ) {
				continue;
			}
			if ( ( $context['quantity'] ?? 0 ) >= $tier['_min_quantity'] ) {
				$result['status']     = 'matched';
				$result['price']      = wholesalex()->calculate_sale_price( $tier, $context['base_price'] ?? '' );
				$result['id']         = $tier['_id'] ?? ( $tier['id'] ?? '' );
				$result['tier_index'] = $index;
			}
		}
		return $result;
	}
}
