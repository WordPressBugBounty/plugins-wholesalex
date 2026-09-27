<?php
/**
 * WholesaleX Dynamic Rules - Condition Engine
 *
 * Centralises all condition-checking, eligibility, filter parsing,
 * priority comparison, and helper utilities used across rule handlers.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic Rules Condition Engine
 */
class Dynamic_Rules_Condition_Engine {

	/**
	 * Check if a date/usage limit is still valid.
	 *
	 * @param array $limit  Limit data with optional _end_date and _usage_limit.
	 * @param int   $rule_id Rule ID.
	 * @return bool
	 */
	public static function has_limit( $limit, $rule_id ) {
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Preserve the existing local-time comparison contract for saved schedules.
		$current_date = current_time( 'timestamp' );

		if ( isset( $limit['_start_date'] ) && ! empty( $limit['_start_date'] ) ) {
			$start_date = strtotime( $limit['_start_date'] );
			if ( $current_date < $start_date ) {
				return false;
			}
		}

		if ( isset( $limit['_end_date'] ) && ! empty( $limit['_end_date'] ) ) {
			$end_date = strtotime( $limit['_end_date'] . ' +1 day' );
			if ( $current_date > $end_date ) {
				return false;
			}
		}

		if ( isset( $limit['_usage_limit'] ) && ! empty( $limit['_usage_limit'] ) ) {
			$usage = get_option( 'wholesalex_dynamic_rule_' . $rule_id . '_usage', 0 );
			if ( $usage >= $limit['_usage_limit'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check a single condition operator.
	 *
	 * @param string $operator  Comparison operator (greater, less, etc.).
	 * @param float  $condition_value  The threshold value.
	 * @param float  $actual_value     The actual value to compare.
	 * @return bool
	 */
	public static function is_condition_passed( $operator, $condition_value, $actual_value ) {
		switch ( $operator ) {
			case 'greater':
				return $actual_value > $condition_value;
			case 'less':
				return $actual_value < $condition_value;
			case 'equal':
				// phpcs:ignore Universal.Operators.StrictComparisons -- Preserve numeric equality between stored price strings and runtime numbers.
				return $actual_value == $condition_value;
			case 'not_equal':
				// phpcs:ignore Universal.Operators.StrictComparisons -- Preserve numeric equality between stored price strings and runtime numbers.
				return $actual_value != $condition_value;
			case 'greater_equal':
				return $actual_value >= $condition_value;
			case 'less_equal':
				return $actual_value <= $condition_value;
			default:
				return false;
		}
	}

	/**
	 * Check if all tiers/conditions are fulfilled.
	 *
	 * Evaluates cart total qty, value, weight, order count, and total purchase conditions.
	 *
	 * @param array $tiers      Array of condition tiers.
	 * @param array $rule_filter Optional filter for eligible products.
	 * @return bool
	 */
	public static function is_conditions_fullfiled( $tiers, $rule_filter = array() ) {
		if ( ! function_exists( 'WC' ) || ! isset( WC()->cart ) || null === WC()->cart ) {
			return false;
		}

		$cart_total_qty    = 0;
		$cart_total_value  = 0;
		$cart_total_weight = 0;
		$has_eligible_item = empty( $rule_filter );

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $rule_filter ) ) {
				if ( ! self::is_eligible_for_rule( $cart_item['product_id'], $cart_item['variation_id'], $rule_filter ) ) {
					continue;
				}
				$has_eligible_item = true;
			}

			$product = $cart_item['data'];
			$qty     = (int) $cart_item['quantity'];

			$cart_total_qty   += $qty;
			$cart_total_value += ( $product->get_price() * $qty );

			$weight = $product->get_weight();
			if ( $weight ) {
				$cart_total_weight += ( floatval( $weight ) * $qty );
			}
		}

		if ( ! $has_eligible_item ) {
			return false;
		}

		$all_passed = true;

		foreach ( $tiers as $tier ) {
			if ( ! isset( $tier['_conditions_for'], $tier['_conditions_operator'], $tier['_conditions_value'] ) ) {
				continue;
			}

			$condition_for   = $tier['_conditions_for'];
			$operator        = $tier['_conditions_operator'];
			$condition_value = floatval( $tier['_conditions_value'] );

			switch ( $condition_for ) {
				case 'cart_total_qty':
					if ( ! self::is_condition_passed( $operator, $condition_value, $cart_total_qty ) ) {
						$all_passed = false;
					}
					break;
				case 'cart_total_value':
					if ( ! self::is_condition_passed( $operator, $condition_value, $cart_total_value ) ) {
						$all_passed = false;
					}
					break;
				case 'cart_total_weight':
					if ( ! self::is_condition_passed( $operator, $condition_value, $cart_total_weight ) ) {
						$all_passed = false;
					}
					break;
				default:
					// Conditions this plugin does not evaluate fail unless an extension handles them.
					$all_passed = (bool) apply_filters( 'wholesalex_dynamic_rules_condition_check', false, $tier, $condition_for, $operator, $condition_value );
					break;
			}

			if ( ! $all_passed ) {
				return false;
			}
		}

		return $all_passed;
	}

	/**
	 * Check customer-scoped (non-cart) conditions for user-scope checks.
	 *
	 * @param array $tiers Condition tiers.
	 * @return bool
	 */
	public static function is_user_order_count_purchase_amount_condition_passed( $tiers ) {
		$all_passed = true;

		foreach ( $tiers as $tier ) {
			if ( ! isset( $tier['_conditions_for'], $tier['_conditions_operator'], $tier['_conditions_value'] ) ) {
				continue;
			}

			$condition_for   = $tier['_conditions_for'];
			$operator        = $tier['_conditions_operator'];
			$condition_value = floatval( $tier['_conditions_value'] );

			if ( in_array( $condition_for, array( 'cart_total_qty', 'cart_total_value', 'cart_total_weight' ), true ) ) {
				continue;
			}
			// Customer-scoped conditions this plugin does not evaluate fail unless an extension handles them.
			$all_passed = (bool) apply_filters( 'wholesalex_dynamic_rules_condition_check', false, $tier, $condition_for, $operator, $condition_value );

			if ( ! $all_passed ) {
				return false;
			}
		}

		return $all_passed;
	}

	/**
	 * Wrapper for is_conditions_fullfiled that unpacks conditions array.
	 *
	 * @param mixed $conditions Conditions array with 'tiers' key.
	 * @param mixed $rule_filter Product targeting filter.
	 * @return bool
	 */
	public static function check_rule_conditions( $conditions, $rule_filter = array() ) {
		if ( isset( $conditions, $conditions['tiers'] ) && ! empty( $conditions['tiers'] ) ) {
			if ( ! self::is_conditions_fullfiled( $conditions['tiers'], $rule_filter ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Parse product filter from discount rule configuration and return normalised filter array.
	 *
	 * @param array $discount Discount rule data.
	 * @return array Normalised filter with include/exclude lists and priority.
	 */
	public static function get_filtered_rules( $discount ) {
		$include_products    = array();
		$include_cats        = array();
		$include_brands      = array();
		$include_variations  = array();
		$include_attributes  = array();
		$include_skus        = array();
		$exclude_products    = array();
		$exclude_skus        = array();
		$exclude_cats        = array();
		$exclude_brands      = array();
		$exclude_variations  = array();
		$exclude_attributes  = array();
		$premium_unavailable = false;
		$is_all_products     = false;
		$product_priority    = 10;

		switch ( $discount['_product_filter'] ) {
			case 'all_products':
				$is_all_products  = true;
				$product_priority = 50;
				break;
			case 'products_in_list':
				if ( ! isset( $discount['products_in_list'] ) ) {
					break;
				}
				foreach ( $discount['products_in_list'] as $list ) {
					if ( isset( $list['value'] ) ) {
						array_push( $include_products, (int) $list['value'] );
						$product_priority = 20;
					}
				}
				break;
			case 'products_not_in_list':
				if ( ! isset( $discount['products_not_in_list'] ) ) {
					break;
				}
				foreach ( $discount['products_not_in_list'] as $list ) {
					if ( isset( $list['value'] ) ) {
						array_push( $exclude_products, (int) $list['value'] );
						$product_priority = 20;
					}
				}
				break;
			case 'cat_in_list':
				if ( ! isset( $discount['cat_in_list'] ) ) {
					break;
				}
				foreach ( $discount['cat_in_list'] as $list ) {
					if ( isset( $list['value'] ) ) {
						array_push( $include_cats, (int) $list['value'] );
						$product_priority = 30;
					}
				}
				break;
			case 'cat_not_in_list':
				if ( ! isset( $discount['cat_not_in_list'] ) ) {
					break;
				}
				foreach ( $discount['cat_not_in_list'] as $list ) {
					if ( isset( $list['value'] ) ) {
						array_push( $exclude_cats, (int) $list['value'] );
						$product_priority = 30;
					}
				}
				break;
			case 'brand_in_list':
			case 'brand_not_in_list':
			case 'att_in_list':
			case 'att_not_in_list':
				$premium_target = apply_filters( 'wholesalex_legacy_premium_target_parse', null, $discount );
				if ( is_array( $premium_target ) ) {
					$include_brands     = isset( $premium_target['include_brands'] ) ? $premium_target['include_brands'] : array();
					$exclude_brands     = isset( $premium_target['exclude_brands'] ) ? $premium_target['exclude_brands'] : array();
					$include_attributes = isset( $premium_target['include_attributes'] ) ? $premium_target['include_attributes'] : array();
					$exclude_attributes = isset( $premium_target['exclude_attributes'] ) ? $premium_target['exclude_attributes'] : array();
					$product_priority   = 30;
				} else {
					$premium_unavailable = true;
				}
				break;
			case 'attribute_in_list':
				if ( ! isset( $discount['attribute_in_list'] ) ) {
					break;
				}
				foreach ( $discount['attribute_in_list'] as $list ) {
					if ( isset( $list['value'] ) ) {
						array_push( $include_variations, (int) $list['value'] );
						$product_priority = 10;
					}
				}
				break;
			case 'attribute_not_in_list':
				if ( ! isset( $discount['attribute_not_in_list'] ) ) {
					break;
				}
				foreach ( $discount['attribute_not_in_list'] as $list ) {
					if ( isset( $list['value'] ) ) {
						array_push( $exclude_variations, (int) $list['value'] );
						$product_priority = 10;
					}
				}
				break;
			case 'sku_in_list':
			case 'sku_not_in_list':
				$premium_target = apply_filters( 'wholesalex_legacy_premium_target_parse', null, $discount );
				if ( is_array( $premium_target ) ) {
					$include_skus     = isset( $premium_target['include_skus'] ) ? $premium_target['include_skus'] : array();
					$exclude_skus     = isset( $premium_target['exclude_skus'] ) ? $premium_target['exclude_skus'] : array();
					$product_priority = 20;
				} else {
					$premium_unavailable = true;
				}
				break;
		}

		$include_skus = array_values( array_unique( array_map( 'intval', $include_skus ) ) );
		$exclude_skus = array_values( array_unique( array_map( 'intval', $exclude_skus ) ) );

		return array(
			'include_products'    => $include_products,
			'include_cats'        => $include_cats,
			'include_brands'      => $include_brands,
			'include_attributes'  => $include_attributes,
			'include_variations'  => $include_variations,
			'include_skus'        => $include_skus,
			'exclude_products'    => $exclude_products,
			'exclude_cats'        => $exclude_cats,
			'exclude_brands'      => $exclude_brands,
			'exclude_attributes'  => $exclude_attributes,
			'exclude_variations'  => $exclude_variations,
			'exclude_skus'        => $exclude_skus,
			'is_all_products'     => $is_all_products,
			'product_priority'    => $product_priority,
			'premium_unavailable' => $premium_unavailable,
		);
	}

	/**
	 * Resolve an SKU filter value to every product/variation ID using that SKU.
	 *
	 * Older rules saved a product ID for SKU filters. Newer rule options save the
	 * SKU itself using the "sku:" prefix, so this supports both formats.
	 *
	 * @param mixed $value Saved SKU filter value.
	 * @return array Product and variation IDs sharing the resolved SKU.
	 */

	/**
	 * Check if given product is eligible for a rule's filter.
	 *
	 * @param int|string $product_id   Product ID.
	 * @param int|string $variation_id Variation ID.
	 * @param array      $filter       Normalised filter array.
	 * @return bool
	 */
	public static function is_eligible_for_rule( $product_id, $variation_id, $filter ) {
		$product_id   = absint( $product_id );
		$variation_id = absint( $variation_id );
		$cats         = wc_get_product_term_ids( $product_id, 'product_cat' );

		if ( ! empty( $filter['premium_unavailable'] ) ) {
			return false;
		}

		$status = false;

		if ( isset( $filter['is_all_products'] ) && $filter['is_all_products'] ) {
			return true;
		}

		if ( ! empty( $filter['include_variations'] ) && in_array( $variation_id, $filter['include_variations'], true ) ) {
			$status = true;
		} elseif ( ! empty( $filter['exclude_variations'] ) && ! in_array( $variation_id, $filter['exclude_variations'], true ) ) {
			$status = true;
		} elseif ( ! empty( $filter['include_products'] ) && in_array( $product_id, $filter['include_products'], true ) ) {
			$status = true;
		} elseif ( ! empty( $filter['exclude_products'] ) && ! in_array( $product_id, $filter['exclude_products'], true ) ) {
			$status = true;
		} elseif ( ! empty( $filter['include_cats'] ) && ! empty( array_intersect( $cats, $filter['include_cats'] ) ) ) {
			$status = true;
		} elseif ( ! empty( $filter['exclude_cats'] ) && empty( array_intersect( $cats, $filter['exclude_cats'] ) ) ) {
			$status = true;
		} elseif ( ! empty( $filter['include_brands'] ) || ! empty( $filter['exclude_brands'] ) || ! empty( $filter['include_attributes'] ) || ! empty( $filter['exclude_attributes'] ) || ! empty( $filter['include_skus'] ) || ! empty( $filter['exclude_skus'] ) ) {
			$status = (bool) apply_filters( 'wholesalex_legacy_premium_target_match', false, $product_id, $variation_id, $filter );
		}

		return $status;
	}

	/**
	 * Get all attribute IDs for a product.
	 *
	 * @param int|string $product_id Product ID.
	 * @return array
	 */
	public static function get_product_attributes( $product_id ) {
		return apply_filters( 'wholesalex_legacy_product_attribute_ids', array(), $product_id );
	}

	/**
	 * Compare rules by priority (descending – higher priority first).
	 *
	 * @param array $a Rule A.
	 * @param array $b Rule B.
	 * @return int
	 */
	public static function compare_by_priority( $a, $b ) {
		if ( $a['applied_on_priority'] === $b['applied_on_priority'] ) {
			if ( $a['who_priority'] === $b['who_priority'] ) {
				return 0;
			}
			return ( $a['who_priority'] > $b['who_priority'] ) ? -1 : 1;
		}
		return ( $a['applied_on_priority'] > $b['applied_on_priority'] ) ? -1 : 1;
	}

	/**
	 * Compare rules by priority (ascending – lower priority first).
	 *
	 * @param array $a Rule A.
	 * @param array $b Rule B.
	 * @return int
	 */
	public static function compare_by_priority_reverse( $a, $b ) {
		if ( $a['applied_on_priority'] === $b['applied_on_priority'] ) {
			if ( $a['who_priority'] === $b['who_priority'] ) {
				return 0;
			}
			return ( $a['who_priority'] < $b['who_priority'] ) ? -1 : 1;
		}
		return ( $a['applied_on_priority'] < $b['applied_on_priority'] ) ? -1 : 1;
	}

	/**
	 * Extract values (or names) from a multiselect field array.
	 *
	 * @param array  $data  Array of {value, name} items.
	 * @param string $type  'value' or 'name'.
	 * @return array
	 */
	public static function get_multiselect_values( $data, $type = 'value' ) {
		$allowed_methods = array();
		foreach ( $data as $method ) {
			if ( 'name' === $type ) {
				$allowed_methods[] = $method['name'];
			} else {
				$allowed_methods[] = $method['value'];
			}
		}
		return $allowed_methods;
	}

	/**
	 * Replace smart-tag placeholders in a string.
	 *
	 * @param array  $smart_tags Key => value replacements.
	 * @param string $new_string Template string.
	 * @return string
	 */
	public static function restore_smart_tags( $smart_tags, $new_string ) {
		foreach ( $smart_tags as $key => $value ) {
			$new_string = str_replace( $key, $value, $new_string );
		}
		return $new_string;
	}

	/**
	 * Filter empty items from array.
	 *
	 * @param mixed $item Array item.
	 * @return bool
	 */
	public static function filter_empty_items( $item ) {
		foreach ( $item as $key => $value ) {
			if ( empty( $value ) ) {
				return false;
			}
		}
		return true;
	}
}
