<?php
/**
 * Shared registry for available wholesale pricing rules.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Registry of available wholesale pricing rule handlers. */
class Wholesale_Pricing_Rule_Registry {
	/** Free processors and extension processors use the same registration map. */
	public static function handlers(): array {
		return (array) apply_filters(
			'wholesalex_rule_handlers',
			array(
				'regular'          => Wholesale_Pricing_Regular_Discount::class,
				'product_discount' => Wholesale_Pricing_Product_Discount::class,
				'cart_discount'    => Wholesale_Pricing_Cart_Discount::class,
				'bogo_discount'    => Wholesale_Pricing_Bogo_Discount::class,
			)
		);
	}

	/**
	 * Create a registered processor; absent implementations stay unavailable.
	 *
	 * @param string $key Processor key.
	 * @return object|null
	 */
	public static function processor( string $key ) {
		$handlers = self::handlers();
		$class    = $handlers[ $key ] ?? null;
		return is_string( $class ) && class_exists( $class ) ? new $class() : null;
	}

	/** Return registered product target callbacks. */
	public static function target_handlers(): array {
		return (array) apply_filters( 'wholesalex_product_target_handlers', array() );
	}

	/** Return registered condition callbacks. */
	public static function condition_handlers(): array {
		return (array) apply_filters( 'wholesalex_condition_handlers', array() );
	}

	/** Return registered restriction callbacks. */
	public static function restriction_handlers(): array {
		return (array) apply_filters( 'wholesalex_restriction_handlers', array() );
	}

	/**
	 * Read the processor identifier from a saved rule.
	 *
	 * @param array $rule Saved rule.
	 * @return string
	 */
	public static function rule_key( array $rule ): string {
		$type = (string) ( $rule['rule_type'] ?? 'wholesale_pricing' );
		if ( 'tier_pricing' === $type ) {
			return 'tiered';
		}
		return 'wholesale_pricing' === $type ? (string) ( $rule['discount_type'] ?? 'regular' ) : $type;
	}

	/**
	 * Find the first unavailable implementation without modifying saved data.
	 *
	 * @param array $rule Saved rule.
	 * @return string
	 */
	public static function unavailable_feature( array $rule ): string {
		$key      = self::rule_key( $rule );
		$handlers = self::handlers();
		if ( ! isset( $handlers[ $key ] ) || ! is_string( $handlers[ $key ] ) || ! class_exists( $handlers[ $key ] ) ) {
			return '' !== $key ? $key : 'rule_type';
		}
		$target = (string) ( $rule['product_filter'] ?? 'all_products' );
		if ( ! in_array( $target, array( 'all_products', 'specific_products', 'specific_variations', 'specific_categories' ), true ) ) {
			$handlers = self::target_handlers();
			if ( ! isset( $handlers[ $target ] ) || ! is_callable( $handlers[ $target ] ) ) {
				return $target;
			}
		}
		$unavailable_rows = self::unavailable_condition_rows( array_merge( (array) ( $rule['conditions']['tiers'] ?? array() ), (array) ( $rule['cart']['conditions'] ?? array() ) ) );
		if ( array() !== $unavailable_rows ) {
			$condition = reset( $unavailable_rows );
			return (string) ( $condition['_conditions_for'] ?? $condition['field'] ?? '' );
		}

		$handlers         = self::restriction_handlers();
		$runtime_versions = (array) apply_filters( 'wholesalex_restriction_runtime_versions', array() );
		foreach ( (array) ( $rule['restrictions'] ?? array() ) as $flag => $enabled ) {
			// Persisted boolean switches enable features; other values are their parameters.
			if ( ( true === $enabled || ( preg_match( '/(^enable_|_combined_variations$)/', (string) $flag ) && ! empty( $enabled ) ) ) && ( ! isset( $handlers[ $flag ] ) || ! is_callable( $handlers[ $flag ] ) || 1 !== (int) ( $runtime_versions[ $flag ] ?? 0 ) ) ) {
				return (string) $flag;
			}
		}
		return '';
	}
	/**
	 * Select exact condition rows whose actual implementation is unavailable.
	 *
	 * @param array $conditions Submitted or authoritative condition rows.
	 * @return array Ordered unavailable rows without normalization.
	 */
	public static function unavailable_condition_rows( array $conditions ): array {
		$rows     = array();
		$aliases  = (array) apply_filters(
			'wholesalex_condition_field_aliases',
			array(
				'cart_total' => 'cart_total_value',
				'item_count' => 'cart_total_qty',
			)
		);
		$handlers = self::condition_handlers();
		foreach ( $conditions as $condition ) {
			if ( ! is_array( $condition ) ) {
				continue;
			}
			$field = (string) ( $condition['_conditions_for'] ?? $condition['field'] ?? '' );
			$field = $aliases[ $field ] ?? $field;
			if ( '' !== $field && ! in_array( $field, array( 'cart_total_qty', 'cart_total_value', 'cart_total_weight' ), true ) && ( ! isset( $handlers[ $field ] ) || ! is_callable( $handlers[ $field ] ) ) ) {
				$rows[] = $condition;
			}
		}
		return $rows;
	}
}
