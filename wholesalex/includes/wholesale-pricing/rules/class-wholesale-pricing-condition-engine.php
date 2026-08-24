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

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Handles user targeting, product targeting, and advanced conditions.
 */
class Wholesale_Pricing_Condition_Engine
{

	/**
	 * Per-request cache for lifetime customer condition values.
	 *
	 * @var array<int, array{order_count?: int, total_spent?: float}>
	 */
	private static $customer_lifetime_cache = array();

	/**
	 * Convert a saved wholesale-pricing product filter into the runtime filter shape.
	 *
	 * @param array $rule Saved wholesale-pricing rule.
	 * @return array{filter: array, priority: int}
	 */
	public static function get_runtime_product_filter(array $rule): array
	{
		$filter_type = isset($rule['product_filter']) ? $rule['product_filter'] : 'all_products';
		$filter = array(
			'include_products' => array(),
			'include_attributes' => array(),
			'include_brands' => array(),
			'include_cats' => array(),
			'include_variations' => array(),
			'include_skus' => array(),
			'exclude_products' => array(),
			'exclude_attributes' => array(),
			'exclude_brands' => array(),
			'exclude_cats' => array(),
			'exclude_variations' => array(),
			'is_all_products' => 'all_products' === $filter_type,
		);
		$priority = 10;

		if ('specific_products' === $filter_type) {
			$filter['include_products'] = self::pluck_select_values(isset($rule['products']) ? $rule['products'] : array());
			$priority = 50;
		} elseif ('specific_categories' === $filter_type) {
			$filter['include_cats'] = self::pluck_select_values(isset($rule['categories']) ? $rule['categories'] : array());
			$priority = 40;
		} elseif ('brands' === $filter_type) {
			$filter['include_brands'] = self::pluck_select_values(isset($rule['brands']) ? $rule['brands'] : array());
			$priority = 40;
		} elseif ('attributes' === $filter_type) {
			$filter['include_attributes'] = self::pluck_select_values(isset($rule['attributes']) ? $rule['attributes'] : array());
			$priority = 40;
		} elseif ('sku' === $filter_type) {
			$filter['include_skus'] = self::get_product_ids_for_sku_items(isset($rule['skus']) ? $rule['skus'] : array());
			$priority = 50;
		}

		if ('all_products' === $filter_type || 'specific_categories' === $filter_type) {
			$filter['exclude_products'] = self::pluck_select_values(isset($rule['exclude_products']) ? $rule['exclude_products'] : array());
		}

		$filter = self::expand_translated_filter_ids($filter);

		return array(
			'filter' => $filter,
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
	public static function user_role_matches(array $rule, string $role_id, int $user_id = 0): bool
	{
		$role_filter = isset($rule['user_role_filter']) ? $rule['user_role_filter'] : 'all_b2b';

		if ('all' === $role_filter) {
			return true;
		}

		if ('all_users' === $role_filter) {
			if (0 === $user_id) {
				return false;
			}

			$excluded_users = apply_filters('wholesalex_dynamic_rules_exclude_users', array());
			$excluded_users = is_array($excluded_users) ? array_map('absint', $excluded_users) : array();

			return !in_array($user_id, $excluded_users, true);
		}

		if ('all_b2b' === $role_filter) {
			if ('' === $role_id) {
				return false;
			}

			$excluded_roles = apply_filters(
				'wholesalex_dynamic_rules_exclude_roles',
				array('wholesalex_guest', 'wholesalex_b2c_users')
			);

			return !is_array($excluded_roles) || !in_array($role_id, $excluded_roles, true);
		}

		if ('specific_users' === $role_filter) {
			if (0 === $user_id || empty($rule['specific_users']) || !is_array($rule['specific_users'])) {
				return false;
			}

			foreach ($rule['specific_users'] as $user) {
				$value = is_array($user) ? (string) ($user['value'] ?? '') : (string) $user;
				if ((is_numeric($value) && (int) $value === $user_id) || 'user_' . $user_id === $value) {
					return true;
				}
			}

			return false;
		}

		if ('specific_roles' !== $role_filter || empty($rule['user_roles']) || !is_array($rule['user_roles'])) {
			return false;
		}

		foreach ($rule['user_roles'] as $role) {
			$value = is_array($role) ? (string) ($role['value'] ?? '') : (string) $role;
			if ($value === $role_id || $value === 'role_' . $role_id) {
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
	public static function get_user_targeting_priority(array $rule): int
	{
		$priorities = array(
			'all' => 10,
			'all_users' => 20,
			'all_b2b' => 30,
			'specific_roles' => 50,
			'specific_users' => 60,
		);
		$role_filter = isset($rule['user_role_filter']) ? $rule['user_role_filter'] : 'all_b2b';

		return isset($priorities[$role_filter]) ? $priorities[$role_filter] : $priorities['all_b2b'];
	}

	/**
	 * Check whether a product matches a runtime wholesale-pricing rule filter.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $rule    Normalized runtime rule.
	 * @return bool
	 */
	public static function is_product_eligible_for_rule(\WC_Product $product, array $rule): bool
	{
		$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;

		$owner_id = absint($rule['owner_id'] ?? 0);
		if ($owner_id && $owner_id !== absint(get_post_field('post_author', $product_id))) {
			return false;
		}

		return self::product_ids_match_filter($product_id, $variation_id, $rule['filter']);
	}

	/**
	 * Check product/variation IDs against a runtime product filter.
	 *
	 * @param int   $product_id   Parent/simple product ID.
	 * @param int   $variation_id Variation ID.
	 * @param array $filter       Runtime product filter.
	 * @return bool
	 */
	public static function product_ids_match_filter(int $product_id, int $variation_id, array $filter): bool
	{
		$product_ids = array();

		if (!empty($filter['exclude_products'])) {
			$product_ids = self::get_product_id_candidates($product_id, $variation_id);
			if (!empty(array_intersect($product_ids, $filter['exclude_products']))) {
				return false;
			}
		}

		if (!empty($filter['is_all_products'])) {
			return true;
		}

		if (empty($product_ids)) {
			$product_ids = self::get_product_id_candidates($product_id, $variation_id);
		}

		$cats = wc_get_product_term_ids($product_id, 'product_cat');
		$brands = self::get_product_brand_ids($product_id);
		$attributes = self::get_product_attribute_ids($product_id);

		if (!empty($filter['include_variations']) && !empty(array_intersect($product_ids, $filter['include_variations']))) {
			return true;
		}

		if (!empty($filter['exclude_variations']) && empty(array_intersect($product_ids, $filter['exclude_variations']))) {
			return true;
		}

		if (!empty($filter['include_products']) && !empty(array_intersect($product_ids, $filter['include_products']))) {
			return true;
		}

		if (!empty($filter['include_cats']) && !empty(array_intersect($cats, $filter['include_cats']))) {
			return true;
		}

		if (!empty($filter['exclude_cats']) && empty(array_intersect($cats, $filter['exclude_cats']))) {
			return true;
		}

		if (!empty($filter['include_brands']) && !empty(array_intersect($brands, $filter['include_brands']))) {
			return true;
		}

		if (!empty($filter['exclude_brands']) && empty(array_intersect($brands, $filter['exclude_brands']))) {
			return true;
		}

		if (!empty($filter['include_attributes']) && !empty(array_intersect($attributes, $filter['include_attributes']))) {
			return true;
		}

		if (!empty($filter['include_skus']) && !empty(array_intersect($product_ids, $filter['include_skus']))) {
			return true;
		}

		if (!empty($filter['exclude_attributes']) && !empty($attributes) && empty(array_intersect($attributes, $filter['exclude_attributes']))) {
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
	private static function get_product_id_candidates(int $product_id, int $variation_id): array
	{
		$ids = array($product_id, $variation_id);

		foreach ($ids as $id) {
			if ($id <= 0) {
				continue;
			}

			$product = wc_get_product($id);
			if ($product instanceof \WC_Product && $product->get_parent_id()) {
				$ids[] = (int) $product->get_parent_id();
			}
		}

		return array_values(array_unique(array_filter(array_map('absint', $ids))));
	}

	/**
	 * Check advanced cart/customer conditions.
	 *
	 * @param array $conditions  Conditions array with a tiers key.
	 * @param array $rule_filter Runtime product filter.
	 * @param array $context     Optional runtime condition context.
	 * @return bool
	 */
	public static function check_rule_conditions(array $conditions, array $rule_filter = array(), array $context = array()): bool
	{
		if (empty($conditions['tiers']) || !is_array($conditions['tiers'])) {
			return true;
		}

		return self::are_conditions_fulfilled($conditions['tiers'], $rule_filter, $context);
	}

	/**
	 * Check order-count and total-purchase conditions before product-specific work.
	 *
	 * @param array $tiers Condition tiers.
	 * @return bool
	 */
	public static function is_user_order_count_purchase_amount_condition_passed(array $tiers): bool
	{
		foreach ($tiers as $tier) {
			if (!isset($tier['_conditions_for'], $tier['_conditions_operator'], $tier['_conditions_value'])) {
				continue;
			}

			$field = self::normalize_condition_field((string) $tier['_conditions_for']);
			if (!in_array($field, array('order_count', 'pro_order_count', 'total_purchase', 'pro_total_purchase'), true)) {
				continue;
			}

			if (self::is_pro_condition_field($field) && !self::is_pro_active()) {
				return false;
			}

			$actual = in_array($field, array('order_count', 'pro_order_count'), true) ? self::get_customer_order_count() : self::get_customer_total_spent();

			if (!self::is_condition_passed($tier['_conditions_operator'], (float) $tier['_conditions_value'], (float) $actual)) {
				return false;
			}
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
	private static function are_conditions_fulfilled(array $tiers, array $rule_filter = array(), array $context = array()): bool
	{
		$cart_data = self::get_filtered_cart_data($rule_filter, $context);

		foreach ($tiers as $tier) {
			if (!isset($tier['_conditions_for'], $tier['_conditions_operator'], $tier['_conditions_value'])) {
				continue;
			}

			$field = self::normalize_condition_field((string) $tier['_conditions_for']);
			$operator = $tier['_conditions_operator'];
			$value = (float) $tier['_conditions_value'];


			switch ($field) {
				case 'cart_total_qty':
					$actual = $cart_data['qty'];
					break;
				case 'cart_total_value':
					$actual = $cart_data['value'];
					break;
				case 'cart_total_weight':
					$actual = $cart_data['weight'];
					break;
				case 'order_count':
				case 'pro_order_count':
					if (self::is_pro_condition_field($field) && !self::is_pro_active()) {
						return false;
					}
					$actual = self::get_customer_order_count();
					break;
				case 'total_purchase':
				case 'pro_total_purchase':
					if (self::is_pro_condition_field($field) && !self::is_pro_active()) {
						return false;
					}
					$actual = self::get_customer_total_spent();
					break;
				default:
					$actual = apply_filters('wholesalex_wholesale_pricing_condition_value', null, $field, $tier, $rule_filter);
					if (null === $actual) {
						return false;
					}
					break;
			}

			if (!self::is_condition_passed($operator, $value, (float) $actual)) {
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
	private static function normalize_condition_field(string $field): string
	{
		$aliases = array(
			'lifetime_order_count' => 'pro_order_count',
			'lifetime_orders' => 'pro_order_count',
			'user_order_count' => 'pro_order_count',
			'lifetime_purchase' => 'pro_total_purchase',
			'lifetime_spend' => 'pro_total_purchase',
			'lifetime_total_spent' => 'pro_total_purchase',
			'user_total_purchase' => 'pro_total_purchase',
		);

		return isset($aliases[$field]) ? $aliases[$field] : $field;
	}

	/**
	 * Check whether a condition field requires WholesaleX Pro.
	 *
	 * @param string $field Normalized condition field.
	 * @return bool
	 */
	private static function is_pro_condition_field(string $field): bool
	{
		return in_array($field, array('pro_order_count', 'pro_total_purchase'), true);
	}

	/**
	 * Check whether WholesaleX Pro is active for Pro-only conditions.
	 *
	 * @return bool
	 */
	private static function is_pro_active(): bool
	{
		return method_exists(wholesalex(), 'is_pro_active') && wholesalex()->is_pro_active();
	}

	/**
	 * Compare one condition against its actual value.
	 *
	 * @param string $operator        Operator slug.
	 * @param float  $condition_value Expected value.
	 * @param float  $actual_value    Actual value.
	 * @return bool
	 */
	private static function is_condition_passed(string $operator, float $condition_value, float $actual_value): bool
	{
		switch ($operator) {
			case 'greater':
				return $actual_value > $condition_value;
			case 'less':
				return $actual_value < $condition_value;
			case 'equal':
				return $actual_value == $condition_value;
			case 'not_equal':
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
	 * Return cart totals filtered by product targeting.
	 *
	 * @param array $rule_filter Runtime product filter.
	 * @param array $context     Optional runtime condition context.
	 * @return array{qty: int, value: float, weight: float}
	 */
	private static function get_filtered_cart_data(array $rule_filter, array $context = array()): array
	{
		$data = array(
			'qty' => 0,
			'value' => 0.0,
			'weight' => 0.0,
		);

		if (function_exists('WC') && WC()->cart) {
			foreach (WC()->cart->get_cart() as $cart_item) {
				if (!self::cart_item_matches_filter($cart_item, $rule_filter)) {
					continue;
				}

				$product = isset($cart_item['data']) && $cart_item['data'] instanceof \WC_Product ? $cart_item['data'] : false;
				$quantity = absint($cart_item['quantity'] ?? 0);

				$data['qty'] += $quantity;
				$data['value'] += isset($cart_item['line_subtotal']) ? (float) $cart_item['line_subtotal'] : ($product ? (float) $product->get_price('edit') * $quantity : 0.0);

				if ($product && $product->get_weight()) {
					$data['weight'] += (float) $product->get_weight() * $quantity;
				}
			}
		}

		if (!empty($context['preview_cart_item']) && is_array($context['preview_cart_item'])) {
			$preview_item = $context['preview_cart_item'];

			if (self::cart_item_matches_filter($preview_item, $rule_filter)) {
				$product = isset($preview_item['data']) && $preview_item['data'] instanceof \WC_Product ? $preview_item['data'] : false;
				$quantity = absint($preview_item['quantity'] ?? 0);

				$data['qty'] += $quantity;
				$data['value'] += isset($preview_item['line_subtotal']) ? (float) $preview_item['line_subtotal'] : ($product ? (float) $product->get_price('edit') * $quantity : 0.0);

				if ($product && $product->get_weight()) {
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
	private static function cart_item_matches_filter(array $cart_item, array $rule_filter): bool
	{
		$product_id = isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0;
		$variation_id = isset($cart_item['variation_id']) ? (int) $cart_item['variation_id'] : 0;

		return self::product_ids_match_filter($product_id, $variation_id, $rule_filter);
	}

	/**
	 * Extract numeric values from select items.
	 *
	 * @param array $items Select item list.
	 * @return array
	 */
	private static function pluck_select_values(array $items): array
	{
		$values = array();

		foreach ($items as $item) {
			$values[] = is_array($item) ? absint($item['value'] ?? 0) : absint($item);
		}

		return array_values(array_filter($values));
	}

	/**
	 * Resolve selected SKU values to their matching product and variation IDs.
	 *
	 * Current selections use the `sku:` prefix so numeric SKUs remain distinct
	 * from legacy selections, which stored a product ID as the value.
	 *
	 * @param array $items Selected SKU items.
	 * @return array
	 */
	private static function get_product_ids_for_sku_items(array $items): array
	{
		$product_ids = array();

		foreach ($items as $item) {
			$value = is_array($item) ? ($item['value'] ?? '') : $item;
			if (!is_scalar($value)) {
				continue;
			}

			$raw_value = trim((string) $value);
			$sku = '';

			if ('' === $raw_value) {
				continue;
			}

			if (0 === strpos($raw_value, 'sku:')) {
				$sku = substr($raw_value, 4);
			} elseif (is_numeric($raw_value)) {
				$product_id = absint($raw_value);
				if ($product_id) {
					$product_ids[] = $product_id;
					$product = function_exists('wc_get_product') ? wc_get_product($product_id) : false;
					$sku = $product ? $product->get_sku() : get_post_meta($product_id, '_sku', true);
				}
			} else {
				$sku = $raw_value;
			}

			$sku = trim((string) $sku);
			if ('' !== $sku) {
				$product_ids = array_merge($product_ids, self::get_product_ids_by_sku($sku));
			}
		}

		return array_values(array_unique(array_filter(array_map('absint', $product_ids))));
	}

	/**
	 * Get every product and variation ID with an exact SKU match.
	 *
	 * @param string $sku Product SKU.
	 * @return array
	 */
	private static function get_product_ids_by_sku(string $sku): array
	{
		static $cache = array();

		$sku = trim($sku);
		if ('' === $sku) {
			return array();
		}

		$cache_key = md5($sku);
		if (isset($cache[$cache_key])) {
			return $cache[$cache_key];
		}

		global $wpdb;

		$product_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT pm.post_id
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE pm.meta_key = '_sku'
				   AND pm.meta_value = %s
				   AND p.post_type IN ('product', 'product_variation')
				   AND p.post_status NOT IN ('trash', 'auto-draft')",
				$sku
			)
		);

		$cache[$cache_key] = array_values(array_unique(array_map('absint', $product_ids)));

		return $cache[$cache_key];
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
	private static function expand_translated_filter_ids(array $filter): array
	{
		if (!apply_filters('wholesalex_wholesale_pricing_expand_translated_filter_ids', true, $filter)) {
			return $filter;
		}

		foreach (array('include_products', 'exclude_products') as $key) {
			if (!empty($filter[$key])) {
				$filter[$key] = self::expand_translated_post_ids($filter[$key]);
			}
		}

		foreach (array('include_cats', 'exclude_cats') as $key) {
			if (!empty($filter[$key])) {
				$filter[$key] = self::expand_translated_term_ids($filter[$key], array('product_cat'));
			}
		}

		foreach (array('include_brands', 'exclude_brands') as $key) {
			if (!empty($filter[$key])) {
				$filter[$key] = self::expand_translated_term_ids($filter[$key], self::get_supported_brand_taxonomies());
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
	private static function expand_translated_post_ids(array $post_ids): array
	{
		$post_ids = array_values(array_unique(array_filter(array_map('absint', $post_ids))));

		if (empty($post_ids)) {
			return $post_ids;
		}

		$expanded = $post_ids;

		foreach ($post_ids as $post_id) {
			if (function_exists('pll_get_post_translations')) {
				$translations = pll_get_post_translations($post_id);

				if (is_array($translations)) {
					$expanded = array_merge($expanded, array_map('absint', $translations));
				}
			}

			$expanded = array_merge($expanded, self::get_wpml_object_translation_ids($post_id, 'product'));
		}

		return array_values(array_unique(array_filter($expanded)));
	}

	/**
	 * Expand taxonomy term IDs with their Polylang/WPML translations.
	 *
	 * @param array $term_ids   Term IDs.
	 * @param array $taxonomies Allowed taxonomies for the selected filter.
	 * @return array
	 */
	private static function expand_translated_term_ids(array $term_ids, array $taxonomies): array
	{
		$term_ids = array_values(array_unique(array_filter(array_map('absint', $term_ids))));
		$taxonomies = array_values(array_filter($taxonomies, 'taxonomy_exists'));

		if (empty($term_ids) || empty($taxonomies)) {
			return $term_ids;
		}

		$expanded = $term_ids;

		foreach ($term_ids as $term_id) {
			$term = get_term($term_id);

			if (!$term instanceof \WP_Term || !in_array($term->taxonomy, $taxonomies, true)) {
				continue;
			}

			if (function_exists('pll_get_term_translations')) {
				$translations = pll_get_term_translations($term_id);

				if (is_array($translations)) {
					$expanded = array_merge($expanded, array_map('absint', $translations));
				}
			}

			$expanded = array_merge($expanded, self::get_wpml_object_translation_ids($term_id, $term->taxonomy));
		}

		return array_values(array_unique(array_filter($expanded)));
	}

	/**
	 * Get translated object IDs from WPML when available.
	 *
	 * @param int    $object_id   Source object ID.
	 * @param string $object_type Post type or taxonomy.
	 * @return array
	 */
	private static function get_wpml_object_translation_ids(int $object_id, string $object_type): array
	{
		if (false === has_filter('wpml_object_id') || false === has_filter('wpml_active_languages')) {
			return array();
		}

		$languages = apply_filters('wpml_active_languages', null, array('skip_missing' => 0)); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook is provided by WPML.

		if (empty($languages) || !is_array($languages)) {
			return array();
		}

		$translations = array();

		foreach (array_keys($languages) as $language_code) {
			$translated_id = apply_filters('wpml_object_id', $object_id, $object_type, false, $language_code); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook is provided by WPML.

			if ($translated_id) {
				$translations[] = absint($translated_id);
			}
		}

		return array_values(array_unique(array_filter($translations)));
	}

	/**
	 * Return supported brand taxonomies that exist on the site.
	 *
	 * @return array
	 */
	private static function get_supported_brand_taxonomies(): array
	{
		return array_values(array_filter(array('product_brand', 'pwb-brand', 'yith_product_brand'), 'taxonomy_exists'));
	}

	/**
	 * Return supported brand term IDs for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private static function get_product_brand_ids(int $product_id): array
	{
		$brand_ids = array();

		foreach (array('product_brand', 'pwb-brand', 'yith_product_brand') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$terms = wc_get_product_term_ids($product_id, $taxonomy);
			if (!empty($terms) && !is_wp_error($terms)) {
				$brand_ids = array_merge($brand_ids, array_map('absint', $terms));
			}
		}

		return array_values(array_unique($brand_ids));
	}

	/**
	 * Return WooCommerce attribute taxonomy IDs assigned to a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private static function get_product_attribute_ids(int $product_id): array
	{
		$product = wc_get_product($product_id);

		if (!$product) {
			return array();
		}

		$attributes = $product->get_attributes();
		if (empty($attributes)) {
			return array();
		}

		$taxonomy_to_id_map = array();
		foreach (wc_get_attribute_taxonomies() as $registered_attr) {
			$taxonomy_name = wc_attribute_taxonomy_name($registered_attr->attribute_name);
			$taxonomy_to_id_map[$taxonomy_name] = absint($registered_attr->attribute_id);
		}

		$attribute_ids = array();
		foreach ($attributes as $attribute) {
			$taxonomy = '';

			if (is_object($attribute) && method_exists($attribute, 'is_taxonomy')) {
				if (!$attribute->is_taxonomy()) {
					continue;
				}
				$taxonomy = $attribute->get_taxonomy();
			} elseif (is_string($attribute) && taxonomy_exists($attribute)) {
				$taxonomy = $attribute;
			} elseif (is_array($attribute) && !empty($attribute['name']) && taxonomy_exists($attribute['name'])) {
				$taxonomy = $attribute['name'];
			}

			if ($taxonomy && isset($taxonomy_to_id_map[$taxonomy])) {
				$attribute_ids[] = $taxonomy_to_id_map[$taxonomy];
			}
		}

		return array_values(array_unique($attribute_ids));
	}

	/**
	 * Return current customer's WooCommerce order count.
	 *
	 * @return int
	 */
	private static function get_customer_order_count(): int
	{
		$user_id = self::get_condition_user_id();

		if ($user_id <= 0) {
			return 0;
		}

		if (isset(self::$customer_lifetime_cache[$user_id]['order_count'])) {
			return absint(self::$customer_lifetime_cache[$user_id]['order_count']);
		}

		$count = self::calculate_customer_order_count($user_id);

		self::$customer_lifetime_cache[$user_id]['order_count'] = $count;

		return $count;
	}

	/**
	 * Return current customer's WooCommerce total spent.
	 *
	 * @return float
	 */
	private static function get_customer_total_spent(): float
	{
		$user_id = self::get_condition_user_id();

		if ($user_id <= 0) {
			return 0.0;
		}

		if (isset(self::$customer_lifetime_cache[$user_id]['total_spent'])) {
			return (float) self::$customer_lifetime_cache[$user_id]['total_spent'];
		}

		$total = self::calculate_customer_total_spent($user_id);

		self::$customer_lifetime_cache[$user_id]['total_spent'] = $total;

		return $total;
	}

	/**
	 * Return the user ID that condition checks should evaluate.
	 *
	 * @return int
	 */
	private static function get_condition_user_id(): int
	{
		$user_id = apply_filters('wholesalex_set_current_user', get_current_user_id());
		return absint($user_id);
	}

	/**
	 * Calculate lifetime order count directly for the current request.
	 *
	 * Avoids stale WooCommerce customer meta so cart-discount conditions match
	 * the customer's actual order history on the cart and checkout pages.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private static function calculate_customer_order_count(int $user_id): int
	{
		if (!function_exists('wc_get_orders')) {
			return absint(wc_get_customer_order_count($user_id));
		}

		$statuses = array_keys(wc_get_order_statuses());
		$statuses = apply_filters('wholesalex_wholesale_pricing_lifetime_order_count_statuses', $statuses, $user_id);

		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status' => $statuses,
				'limit' => -1,
				'return' => 'ids',
			)
		);

		return is_array($orders) ? count($orders) : absint(wc_get_customer_order_count($user_id));
	}

	/**
	 * Calculate lifetime purchase amount directly for the current request.
	 *
	 * @param int $user_id User ID.
	 * @return float
	 */
	private static function calculate_customer_total_spent(int $user_id): float
	{
		if (!function_exists('wc_get_orders')) {
			return (float) wc_get_customer_total_spent($user_id);
		}

		$statuses = wc_get_is_paid_statuses();
		$statuses = apply_filters('wholesalex_wholesale_pricing_lifetime_purchase_statuses', $statuses, $user_id);
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status' => $statuses,
				'limit' => -1,
			)
		);

		if (!is_array($orders)) {
			return (float) wc_get_customer_total_spent($user_id);
		}

		$total = 0.0;

		foreach ($orders as $order) {
			if ($order instanceof \WC_Order) {
				$total += (float) $order->get_total();
			}
		}

		return $total;
	}
}
