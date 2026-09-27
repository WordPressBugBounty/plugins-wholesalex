<?php
/**
 * WholesaleX Wholesale Pricing - Condition Engine
 *
 * Own condition/targeting engine for wholesale-pricing rules. This keeps the
 * new wholesale-pricing backend independent from Dynamic Rules internals while
 * preserving the same targeting behavior.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles user targeting, product targeting, and advanced conditions.
 */
class Wholesale_Pricing_Condition_Engine {

	/**
	 * Convert a saved wholesale-pricing product filter into the runtime filter shape.
	 *
	 * @param array $rule Saved wholesale-pricing rule.
	 * @return array{filter: array, priority: int}
	 */
	public static function get_runtime_product_filter( array $rule ): array {
		$filter_type = isset( $rule['product_filter'] ) ? $rule['product_filter'] : 'all_products';
		$filter      = array(
			'include_products'   => array(),
			'include_attributes' => array(),
			'include_brands'     => array(),
			'include_cats'       => array(),
			'include_variations' => array(),
			'include_skus'       => array(),
			'exclude_products'   => array(),
			'exclude_attributes' => array(),
			'exclude_brands'     => array(),
			'exclude_cats'       => array(),
			'exclude_variations' => array(),
			'is_all_products'    => 'all_products' === $filter_type,
		);
		$priority    = 10;

		if ( 'specific_products' === $filter_type ) {
			$filter['include_products'] = self::pluck_select_values( is_array( $rule['products'] ?? null ) ? $rule['products'] : array() );
			$priority                   = 50;
		} elseif ( 'specific_variations' === $filter_type ) {
			$filter['include_variations'] = self::pluck_select_values( is_array( $rule['products'] ?? null ) ? $rule['products'] : array() );
			$priority                     = 60;
		} elseif ( 'specific_categories' === $filter_type ) {
			$filter['include_cats'] = self::pluck_select_values( isset( $rule['categories'] ) ? $rule['categories'] : array() );
			$priority               = 40;
		} elseif ( ! in_array( $filter_type, array( 'all_products', 'specific_products', 'specific_variations', 'specific_categories' ), true ) ) {
			$filter = apply_filters( 'wholesalex_premium_product_filter', $filter, $filter_type, $rule );
			if ( ! is_array( $filter ) || empty( $filter['_premium_target'] ) ) {
				$filter = array_merge(
					$filter,
					array(
						'_unavailable'    => true,
						'is_all_products' => false,
					)
				);
			}
			$priority = isset( $filter['_priority'] ) ? absint( $filter['_priority'] ) : 10;
		}

		if ( 'all_products' === $filter_type || 'specific_categories' === $filter_type ) {
			$filter['exclude_products'] = self::pluck_select_values( isset( $rule['exclude_products'] ) ? $rule['exclude_products'] : array() );
		}

		$filter = self::expand_translated_filter_ids( $filter );

		// Include every descendant at runtime without changing the saved selection.
		$selected_categories = $filter['include_cats'];
		foreach ( $selected_categories as $category_id ) {
			$children = get_term_children( (int) $category_id, 'product_cat' );
			if ( ! is_wp_error( $children ) ) {
				$filter['include_cats'] = array_merge( $filter['include_cats'], $children );
			}
		}
		$filter['include_cats'] = array_values( array_unique( array_map( 'absint', $filter['include_cats'] ) ) );

		return array(
			'filter'   => $filter,
			'priority' => $priority,
		);
	}

	/**
	 * Check whether a user can use a saved rule.
	 *
	 * @param array  $rule    Saved wholesale-pricing rule.
	 * @param string $role_id Current WholesaleX role ID.
	 * @param int    $user_id Current user ID, or zero for a guest.
	 * @return bool
	 */
	public static function user_role_matches( array $rule, string $role_id, int $user_id = 0 ): bool {
		$role_filter = isset( $rule['user_role_filter'] ) ? $rule['user_role_filter'] : 'all_b2b';

		if ( 'all' === $role_filter ) {
			return true;
		}

		if ( 'all_users' === $role_filter ) {
			if ( 0 === $user_id ) {
				return false;
			}

			$excluded_users = apply_filters( 'wholesalex_dynamic_rules_exclude_users', array() );
			$excluded_users = is_array( $excluded_users ) ? array_map( 'absint', $excluded_users ) : array();

			return ! in_array( $user_id, $excluded_users, true );
		}

		if ( 'all_b2b' === $role_filter ) {
			if ( '' === $role_id ) {
				return false;
			}

			$excluded_roles = apply_filters(
				'wholesalex_dynamic_rules_exclude_roles',
				array( 'wholesalex_guest', 'wholesalex_b2c_users' )
			);

			return ! is_array( $excluded_roles ) || ! in_array( $role_id, $excluded_roles, true );
		}

		if ( 'specific_users' === $role_filter ) {
			if ( 0 === $user_id || empty( $rule['specific_users'] ) || ! is_array( $rule['specific_users'] ) ) {
				return false;
			}

			foreach ( $rule['specific_users'] as $user ) {
				$value = is_array( $user ) ? (string) ( $user['value'] ?? '' ) : (string) $user;
				if ( ( is_numeric( $value ) && (int) $value === $user_id ) || 'user_' . $user_id === $value ) {
					return true;
				}
			}

			return false;
		}

		if ( 'specific_roles' !== $role_filter || empty( $rule['user_roles'] ) || ! is_array( $rule['user_roles'] ) ) {
			return false;
		}

		foreach ( $rule['user_roles'] as $role ) {
			$value = is_array( $role ) ? (string) ( $role['value'] ?? '' ) : (string) $role;
			if ( $value === $role_id || 'role_' . $role_id === $value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the conflict-resolution priority for a rule's user targeting.
	 *
	 * More narrowly targeted rules win when product targeting is equal.
	 *
	 * @param array $rule Saved wholesale-pricing rule.
	 * @return int
	 */
	public static function get_user_targeting_priority( array $rule ): int {
		$priorities  = array(
			'all'            => 10,
			'all_users'      => 20,
			'all_b2b'        => 30,
			'specific_roles' => 50,
			'specific_users' => 60,
		);
		$role_filter = isset( $rule['user_role_filter'] ) ? $rule['user_role_filter'] : 'all_b2b';

		return isset( $priorities[ $role_filter ] ) ? $priorities[ $role_filter ] : $priorities['all_b2b'];
	}

	/**
	 * Check whether a product matches a runtime wholesale-pricing rule filter.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $rule    Normalized runtime rule.
	 * @return bool
	 */
	public static function is_product_eligible_for_rule( \WC_Product $product, array $rule ): bool {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;

		$owner_id = absint( $rule['owner_id'] ?? 0 );
		if ( $owner_id && absint( get_post_field( 'post_author', $product_id ) ) !== $owner_id ) {
			return false;
		}

		return self::product_ids_match_filter( $product_id, $variation_id, $rule['filter'] );
	}

	/**
	 * Check product/variation IDs against a runtime product filter.
	 *
	 * @param int   $product_id   Parent/simple product ID.
	 * @param int   $variation_id Variation ID.
	 * @param array $filter       Runtime product filter.
	 * @return bool
	 */
	public static function product_ids_match_filter( int $product_id, int $variation_id, array $filter ): bool {
		$product_ids = array();

		if ( ! empty( $filter['exclude_products'] ) ) {
			$product_ids = self::get_product_id_candidates( $product_id, $variation_id );
			if ( ! empty( array_intersect( $product_ids, $filter['exclude_products'] ) ) ) {
				return false;
			}
		}

		if ( ! empty( $filter['_unavailable'] ) ) {
			return false; }

		if ( ! empty( $filter['is_all_products'] ) ) {
			return true;
		}

		if ( ! empty( $filter['_premium_target'] ) ) {
			$handlers                = Wholesale_Pricing_Rule_Registry::target_handlers();
			$key                     = $filter['_premium_target'];
			$product                 = wc_get_product( $variation_id ? $variation_id : $product_id );
			$rule                    = (array) $filter['_premium_rule'];
			$rule['_runtime_filter'] = $filter;
			return $product instanceof \WC_Product && isset( $handlers[ $key ] ) && is_callable( $handlers[ $key ] )
				? (bool) $handlers[ $key ]( $product, $rule ) : false;
		}

		if ( empty( $product_ids ) ) {
			$product_ids = self::get_product_id_candidates( $product_id, $variation_id );
		}

		$cats = wc_get_product_term_ids( $product_id, 'product_cat' );

		if ( $variation_id > 0 && ! empty( $filter['include_variations'] ) && in_array( $variation_id, $filter['include_variations'], true ) ) {
			return true;
		}

		if ( ! empty( $filter['exclude_variations'] ) && empty( array_intersect( $product_ids, $filter['exclude_variations'] ) ) ) {
			return true;
		}

		if ( ! empty( $filter['include_products'] ) && ! empty( array_intersect( $product_ids, $filter['include_products'] ) ) ) {
			return true;
		}

		if ( ! empty( $filter['include_cats'] ) && ! empty( array_intersect( $cats, $filter['include_cats'] ) ) ) {
			return true;
		}

		if ( ! empty( $filter['exclude_cats'] ) && empty( array_intersect( $cats, $filter['exclude_cats'] ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return all IDs that can represent a cart line for product targeting.
	 *
	 * WooCommerce cart rows can expose a parent product ID, a variation ID, and
	 * a product-object ID. Specific-product targeting must treat these as one
	 * product family so cart conditions such as "Cart - Total Quantity" combine
	 * quantities across all targeted products.
	 *
	 * @param int $product_id   Product ID from the cart line.
	 * @param int $variation_id Variation ID from the cart line.
	 * @return array<int>
	 */
	private static function get_product_id_candidates( int $product_id, int $variation_id ): array {
		$ids = array( $product_id, $variation_id );

		foreach ( $ids as $id ) {
			if ( $id <= 0 ) {
				continue;
			}

			$product = wc_get_product( $id );
			if ( $product instanceof \WC_Product && $product->get_parent_id() ) {
				$ids[] = (int) $product->get_parent_id();
			}
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	}

	/**
	 * Check advanced cart/customer conditions.
	 *
	 * @param array $conditions  Conditions array with a tiers key.
	 * @param array $rule_filter Runtime product filter.
	 * @param array $context     Optional runtime condition context.
	 * @return bool
	 */
	public static function check_rule_conditions( array $conditions, array $rule_filter = array(), array $context = array() ): bool {
		if ( empty( $conditions['tiers'] ) || ! is_array( $conditions['tiers'] ) ) {
			return true;
		}

		return self::are_conditions_fulfilled( $conditions['tiers'], $rule_filter, $context );
	}

	/**
	 * Check customer-scoped conditions before product-specific work.
	 *
	 * @param array $tiers Condition tiers.
	 * @return bool
	 */
	public static function is_user_order_count_purchase_amount_condition_passed( array $tiers ): bool {
		foreach ( $tiers as $tier ) {
			if ( ! isset( $tier['_conditions_for'], $tier['_conditions_operator'], $tier['_conditions_value'] ) ) {
				continue;
			}

			$field = self::normalize_condition_field( (string) $tier['_conditions_for'] );
			if ( in_array( $field, array( 'cart_total_qty', 'cart_total_value', 'cart_total_weight' ), true ) ) {
				continue; }
			$handlers = Wholesale_Pricing_Rule_Registry::condition_handlers();
			if ( isset( $handlers[ $field ] ) ) {
				if ( ! is_callable( $handlers[ $field ] ) || ! $handlers[ $field ]( $tier, array() ) ) {
					return false; }
				continue;
			}
			return false;
		}

		return true;
	}

	/**
	 * Check whether all condition tiers pass.
	 *
	 * @param array $tiers       Condition tiers.
	 * @param array $rule_filter Runtime product filter.
	 * @param array $context     Optional runtime condition context.
	 * @return bool
	 */
	private static function are_conditions_fulfilled( array $tiers, array $rule_filter = array(), array $context = array() ): bool {
		$cart_data = self::get_filtered_cart_data( $rule_filter, $context );

		foreach ( $tiers as $tier ) {
			if ( ! isset( $tier['_conditions_for'], $tier['_conditions_operator'], $tier['_conditions_value'] ) ) {
				continue;
			}

			$field    = self::normalize_condition_field( (string) $tier['_conditions_for'] );
			$operator = $tier['_conditions_operator'];
			$value    = (float) $tier['_conditions_value'];

			switch ( $field ) {
				case 'cart_total_qty':
					$actual = $cart_data['qty'];
					break;
				case 'cart_total_value':
					$actual = $cart_data['value'];
					break;
				case 'cart_total_weight':
					$actual = $cart_data['weight'];
					break;
				default:
					$handlers = Wholesale_Pricing_Rule_Registry::condition_handlers();
					if ( isset( $handlers[ $field ] ) ) {
						if ( ! is_callable( $handlers[ $field ] ) || ! $handlers[ $field ]( $tier, $context ) ) {
							return false; }
						continue 2;
					}
					$actual = apply_filters( 'wholesalex_wholesale_pricing_condition_value', null, $field, $tier, $rule_filter );
					if ( null === $actual ) {
						return false;
					}
					break;
			}

			if ( ! self::is_condition_passed( $operator, $value, (float) $actual ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Normalize condition field aliases used by older/newer admin UIs.
	 *
	 * @param string $field Raw condition field.
	 * @return string
	 */
	private static function normalize_condition_field( string $field ): string {
		return (string) apply_filters( 'wholesalex_premium_condition_field', $field );
	}

	/**
	 * Compare one condition against its actual value.
	 *
	 * @param string $operator        Operator slug.
	 * @param float  $condition_value Expected value.
	 * @param float  $actual_value    Actual value.
	 * @return bool
	 */
	private static function is_condition_passed( string $operator, float $condition_value, float $actual_value ): bool {
		switch ( $operator ) {
			case 'greater':
				return $actual_value > $condition_value;
			case 'less':
				return $actual_value < $condition_value;
			case 'equal':
				return $actual_value === $condition_value;
			case 'not_equal':
				return $actual_value !== $condition_value;
			case 'greater_equal':
				return $actual_value >= $condition_value;
			case 'less_equal':
				return $actual_value <= $condition_value;
			default:
				return false;
		}
	}

	/**
	 * Return cart totals filtered by product targeting.
	 *
	 * @param array $rule_filter Runtime product filter.
	 * @param array $context     Optional runtime condition context.
	 * @return array{qty: int, value: float, weight: float}
	 */
	private static function get_filtered_cart_data( array $rule_filter, array $context = array() ): array {
		$data = array(
			'qty'    => 0,
			'value'  => 0.0,
			'weight' => 0.0,
		);

		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( ! self::cart_item_matches_filter( $cart_item, $rule_filter ) ) {
					continue;
				}

				$product  = isset( $cart_item['data'] ) && $cart_item['data'] instanceof \WC_Product ? $cart_item['data'] : false;
				$quantity = absint( $cart_item['quantity'] ?? 0 );

				$data['qty']   += $quantity;
				$data['value'] += isset( $cart_item['line_subtotal'] ) ? (float) $cart_item['line_subtotal'] : ( $product ? (float) $product->get_price( 'edit' ) * $quantity : 0.0 );

				if ( $product && $product->get_weight() ) {
					$data['weight'] += (float) $product->get_weight() * $quantity;
				}
			}
		}

		if ( ! empty( $context['preview_cart_item'] ) && is_array( $context['preview_cart_item'] ) ) {
			$preview_item = $context['preview_cart_item'];

			if ( self::cart_item_matches_filter( $preview_item, $rule_filter ) ) {
				$product  = isset( $preview_item['data'] ) && $preview_item['data'] instanceof \WC_Product ? $preview_item['data'] : false;
				$quantity = absint( $preview_item['quantity'] ?? 0 );

				$data['qty']   += $quantity;
				$data['value'] += isset( $preview_item['line_subtotal'] ) ? (float) $preview_item['line_subtotal'] : ( $product ? (float) $product->get_price( 'edit' ) * $quantity : 0.0 );

				if ( $product && $product->get_weight() ) {
					$data['weight'] += (float) $product->get_weight() * $quantity;
				}
			}
		}

		return $data;
	}

	/**
	 * Check whether a cart item matches a product filter.
	 *
	 * @param array $cart_item   WooCommerce cart item.
	 * @param array $rule_filter Runtime product filter.
	 * @return bool
	 */
	private static function cart_item_matches_filter( array $cart_item, array $rule_filter ): bool {
		$product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
		$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;

		return self::product_ids_match_filter( $product_id, $variation_id, $rule_filter );
	}

	/**
	 * Extract numeric values from select items.
	 *
	 * @param array $items Select item list.
	 * @return array
	 */
	private static function pluck_select_values( array $items ): array {
		$values = array();

		foreach ( $items as $item ) {
			$values[] = is_array( $item ) ? absint( $item['value'] ?? 0 ) : absint( $item );
		}

		return array_values( array_filter( $values ) );
	}

	/**
	 * Expand saved product/category/brand filters to include translated IDs.
	 *
	 * Polylang stores translated products and product terms as separate posts/terms.
	 * A rule authored against one language should therefore match the translated
	 * siblings at runtime without forcing merchants to select every duplicate.
	 *
	 * @param array $filter Runtime product filter.
	 * @return array
	 */
	private static function expand_translated_filter_ids( array $filter ): array {
		if ( ! apply_filters( 'wholesalex_wholesale_pricing_expand_translated_filter_ids', true, $filter ) ) {
			return $filter;
		}

		foreach ( array( 'include_products', 'exclude_products', 'include_variations', 'exclude_variations' ) as $key ) {
			if ( ! empty( $filter[ $key ] ) ) {
				$filter[ $key ] = self::expand_translated_post_ids( $filter[ $key ] );
			}
		}

		foreach ( array( 'include_cats', 'exclude_cats' ) as $key ) {
			if ( ! empty( $filter[ $key ] ) ) {
				$filter[ $key ] = self::expand_translated_term_ids( $filter[ $key ], array( 'product_cat' ) );
			}
		}

		return $filter;
	}

	/**
	 * Expand product IDs with their Polylang/WPML translations.
	 *
	 * @param array $post_ids Product or variation IDs.
	 * @return array
	 */
	private static function expand_translated_post_ids( array $post_ids ): array {
		$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );

		if ( empty( $post_ids ) ) {
			return $post_ids;
		}

		$expanded = $post_ids;

		foreach ( $post_ids as $post_id ) {
			if ( function_exists( 'pll_get_post_translations' ) ) {
				$translations = pll_get_post_translations( $post_id );

				if ( is_array( $translations ) ) {
					$expanded = array_merge( $expanded, array_map( 'absint', $translations ) );
				}
			}

			$post_type = get_post_type( $post_id );
			$post_type = $post_type ? $post_type : 'product';
			$expanded  = array_merge( $expanded, self::get_wpml_object_translation_ids( $post_id, $post_type ) );
		}

		return array_values( array_unique( array_filter( $expanded ) ) );
	}

	/**
	 * Expand taxonomy term IDs with their Polylang/WPML translations.
	 *
	 * @param array $term_ids   Term IDs.
	 * @param array $taxonomies Allowed taxonomies for the selected filter.
	 * @return array
	 */
	private static function expand_translated_term_ids( array $term_ids, array $taxonomies ): array {
		$term_ids   = array_values( array_unique( array_filter( array_map( 'absint', $term_ids ) ) ) );
		$taxonomies = array_values( array_filter( $taxonomies, 'taxonomy_exists' ) );

		if ( empty( $term_ids ) || empty( $taxonomies ) ) {
			return $term_ids;
		}

		$expanded = $term_ids;

		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id );

			if ( ! $term instanceof \WP_Term || ! in_array( $term->taxonomy, $taxonomies, true ) ) {
				continue;
			}

			if ( function_exists( 'pll_get_term_translations' ) ) {
				$translations = pll_get_term_translations( $term_id );

				if ( is_array( $translations ) ) {
					$expanded = array_merge( $expanded, array_map( 'absint', $translations ) );
				}
			}

			$expanded = array_merge( $expanded, self::get_wpml_object_translation_ids( $term_id, $term->taxonomy ) );
		}

		return array_values( array_unique( array_filter( $expanded ) ) );
	}

	/**
	 * Get translated object IDs from WPML when available.
	 *
	 * @param int    $object_id   Source object ID.
	 * @param string $object_type Post type or taxonomy.
	 * @return array
	 */
	private static function get_wpml_object_translation_ids( int $object_id, string $object_type ): array {
		if ( false === has_filter( 'wpml_object_id' ) || false === has_filter( 'wpml_active_languages' ) ) {
			return array();
		}

		$languages = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook is provided by WPML.

		if ( empty( $languages ) || ! is_array( $languages ) ) {
			return array();
		}

		$translations = array();

		foreach ( array_keys( $languages ) as $language_code ) {
			$translated_id = apply_filters( 'wpml_object_id', $object_id, $object_type, false, $language_code ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook is provided by WPML.

			if ( $translated_id ) {
				$translations[] = absint( $translated_id );
			}
		}

		return array_values( array_unique( array_filter( $translations ) ) );
	}
}
