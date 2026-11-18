<?php
/**
 * Dynamic rule types available to the AI tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Describes the dynamic rule types, audiences, product scopes and conditions.
 *
 * Only types whose data WholesaleX stores and applies are listed. Extensions
 * add their own types with the `wholesalex_mcp_dynamic_rule_types` filter,
 * using the field format documented in `Fields`.
 */
final class Dynamic_Rule_Types {

	/**
	 * Return the rule types keyed by stored type.
	 *
	 * Each type has `label`, `description`, `fields` and an optional
	 * `validate` callable that receives the stored payload.
	 *
	 * @return array<string, array>
	 */
	public static function types(): array {
		$discount_type = array(
			'kind'        => 'enum',
			'storage'     => '_discount_type',
			'enum'        => array( 'percentage', 'amount', 'fixed' ),
			'default'     => 'percentage',
			'description' => __( 'percentage: a percent off. amount: this amount off each item. fixed: each item costs this amount.', 'wholesalex' ),
		);
		$amount        = array(
			'kind'        => 'number',
			'storage'     => '_discount_amount',
			'minimum'     => 0,
			'required'    => true,
			'description' => __( 'Discount value in the store currency, or 0 to 100 for a percentage.', 'wholesalex' ),
		);
		$discount_name = array(
			'kind'        => 'text',
			'storage'     => '_discount_name',
			'max_length'  => 100,
			'description' => __( 'Optional discount name shown to shoppers.', 'wholesalex' ),
		);
		$gateways      = array(
			'kind'        => 'gateways',
			'storage'     => '_payment_gateways',
			'required'    => true,
			'description' => __( 'Payment method IDs. Find them with wholesalex-list-checkout-options.', 'wholesalex' ),
		);

		$types = array(
			'product_discount'  => array(
				'label'       => __( 'Product discount', 'wholesalex' ),
				'description' => __( 'Lowers product prices for the targeted customers and products.', 'wholesalex' ),
				'fields'      => array(
					'discount_type' => $discount_type,
					'amount'        => $amount,
					'discount_name' => $discount_name,
				),
				'validate'    => array( __CLASS__, 'validate_discount' ),
			),
			'cart_discount'     => array(
				'label'       => __( 'Cart discount', 'wholesalex' ),
				'description' => __( 'Discounts the whole cart for the targeted customers.', 'wholesalex' ),
				'fields'      => array(
					'discount_type' => $discount_type,
					'amount'        => $amount,
					'discount_name' => $discount_name,
				),
				'validate'    => array( __CLASS__, 'validate_discount' ),
			),
			'payment_discount'  => array(
				'label'       => __( 'Payment method discount', 'wholesalex' ),
				'description' => __( 'Discounts the order when the customer pays with one of the selected payment methods.', 'wholesalex' ),
				'fields'      => array(
					'payment_method_ids' => $gateways,
					'discount_type'      => $discount_type,
					'amount'             => $amount,
					'discount_name'      => $discount_name,
				),
				'validate'    => array( __CLASS__, 'validate_discount' ),
			),
			'payment_order_qty' => array(
				'label'       => __( 'Required quantity for payment methods', 'wholesalex' ),
				'description' => __( 'Only offers the selected payment methods when the order has at least this many items.', 'wholesalex' ),
				'fields'      => array(
					'payment_method_ids' => $gateways,
					'minimum_quantity'   => array(
						'kind'        => 'integer',
						'storage'     => '_order_quantity',
						'minimum'     => 1,
						'required'    => true,
						'description' => __( 'Minimum number of items in the order.', 'wholesalex' ),
					),
				),
			),
			'buy_x_get_one'     => array(
				'label'       => __( 'Buy X get one free', 'wholesalex' ),
				'description' => __( 'For every X items of the same product in the cart, one of them is free.', 'wholesalex' ),
				'fields'      => array(
					'buy_quantity'           => array(
						'kind'        => 'integer',
						'storage'     => '_minimum_purchase_count',
						'minimum'     => 2,
						'default'     => 2,
						'description' => __( 'X: for every this many items of a product in the cart, one item is free. For example, 3 means one free item for every three in the cart.', 'wholesalex' ),
					),
					'once_per_order'         => array(
						'kind'        => 'yes_no',
						'storage'     => '_per_cart_once',
						'default'     => false,
						'description' => __( 'Give at most one free item per order.', 'wholesalex' ),
					),
					'show_badge'             => array(
						'kind'        => 'yes_no',
						'storage'     => '_buy_x_get_product_badge_enable',
						'default'     => false,
						'description' => __( 'Show an offer badge on product images in the shop and on product pages.', 'wholesalex' ),
					),
					'badge_label'            => array(
						'kind'        => 'text',
						'storage'     => '_product_badge_label',
						'max_length'  => 50,
						'description' => __( 'Badge text, for example "BOGO Free".', 'wholesalex' ),
					),
					'badge_background_color' => array(
						'kind'        => 'color',
						'storage'     => '_product_badge_bg_color',
						'description' => __( 'Badge background color, for example #5a40e8.', 'wholesalex' ),
					),
					'badge_text_color'       => array(
						'kind'        => 'color',
						'storage'     => '_product_badge_text_color',
						'description' => __( 'Badge text color, for example #ffffff.', 'wholesalex' ),
					),
					'badge_position'         => array(
						'kind'        => 'enum',
						'storage'     => '_product_badge_position',
						'enum'        => array( 'left', 'right' ),
						'description' => __( 'Badge position on the product image.', 'wholesalex' ),
					),
					'badge_style'            => array(
						'kind'        => 'enum',
						'storage'     => '_product_badge_styles',
						'enum'        => array( 'style_one', 'style_two', 'style_three', 'style_four', 'style_five' ),
						'description' => __( 'Badge shape, as shown in the rule editor.', 'wholesalex' ),
					),
				),
			),
			'shipping_rule'     => array(
				'label'       => __( 'Shipping rule', 'wholesalex' ),
				'description' => __( 'Limits the targeted customers to selected shipping methods of one shipping zone.', 'wholesalex' ),
				'fields'      => array(
					'shipping_zone_id'    => array(
						'kind'        => 'shipping_zone',
						'storage'     => '__shipping_zone',
						'required'    => true,
						'description' => __( 'Shipping zone ID. 0 is "Rest of the World". Find zones with wholesalex-list-checkout-options.', 'wholesalex' ),
					),
					'shipping_method_ids' => array(
						'kind'        => 'shipping_methods',
						'storage'     => '_shipping_zone_methods',
						'zone_field'  => 'shipping_zone_id',
						'required'    => true,
						'description' => __( 'Shipping method instance IDs from that zone that stay available.', 'wholesalex' ),
					),
				),
			),
			'min_order_qty'     => array(
				'label'       => __( 'Minimum order quantity', 'wholesalex' ),
				'description' => __( 'Requires a minimum quantity of each targeted product.', 'wholesalex' ),
				'fields'      => array(
					'minimum_quantity' => array(
						'kind'        => 'integer',
						'storage'     => '_min_order_qty',
						'minimum'     => 1,
						'required'    => true,
						'description' => __( 'Minimum quantity of each product.', 'wholesalex' ),
					),
					'step'             => array(
						'kind'        => 'integer',
						'storage'     => '_min_order_qty_step',
						'minimum'     => 1,
						'default'     => 1,
						'description' => __( 'Quantities must grow in steps of this size.', 'wholesalex' ),
					),
					'checkout_only'    => array(
						'kind'        => 'yes_no',
						'storage'     => '_min_order_qty_disable',
						'default'     => false,
						'description' => __( 'When true, shoppers can add any quantity on shop and product pages, and the minimum is only enforced at checkout.', 'wholesalex' ),
					),
				),
			),
			'tax_rule'          => array(
				'label'       => __( 'Tax rule', 'wholesalex' ),
				'description' => __( 'Makes the targeted customers tax exempt, or charges them a different tax class.', 'wholesalex' ),
				'fields'      => array(
					'tax_exempt'           => array(
						'kind'        => 'yes_no',
						'storage'     => '_tax_exempted',
						'required'    => true,
						'description' => __( 'true: no tax is charged. false: the tax_class is charged instead.', 'wholesalex' ),
					),
					'tax_class'            => array(
						'kind'        => 'tax_class',
						'storage'     => '_tax_class',
						'description' => __( 'Tax class slug or name, used when tax_exempt is false. "standard" is the standard rate. Find classes with wholesalex-list-checkout-options.', 'wholesalex' ),
					),
					'exempt_country_codes' => array(
						'kind'        => 'countries',
						'storage'     => '_exempted_country',
						'description' => __( 'Two-letter country codes, used when tax_exempt is true, to limit the exemption to those countries. Empty means every country.', 'wholesalex' ),
					),
				),
			),
		);

		/**
		 * Filters the dynamic rule types offered by the AI tools.
		 *
		 * Add an entry keyed by the stored `_rule_type`. The rule data is
		 * stored under the same key, as the rule editor does. Only add types
		 * whose data your code saves and applies.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, array> $types Rule types.
		 */
		$types = apply_filters( 'wholesalex_mcp_dynamic_rule_types', $types );

		$valid = array();
		foreach ( is_array( $types ) ? $types : array() as $type => $definition ) {
			if ( is_string( $type ) && 1 === preg_match( '/^[a-z0-9_]+$/', $type ) && is_array( $definition ) && isset( $definition['label'], $definition['description'] ) && is_array( $definition['fields'] ?? null ) ) {
				$valid[ $type ] = $definition;
			}
		}
		return $valid;
	}

	/**
	 * Return who a dynamic rule can apply to.
	 *
	 * @return array<string, array>
	 */
	public static function audiences(): array {
		return array(
			'everyone'            => array(
				'storage'     => 'all',
				'description' => __( 'every shopper, including guests.', 'wholesalex' ),
			),
			'logged_in_customers' => array(
				'storage'     => 'all_users',
				'description' => __( 'every signed-in customer.', 'wholesalex' ),
			),
			'all_b2b_roles'       => array(
				'storage'     => 'all_roles',
				'description' => __( 'customers in any WholesaleX B2B role.', 'wholesalex' ),
			),
			'specific_customers'  => array(
				'storage'     => 'specific_users',
				'ids'         => 'customer_ids',
				'kind'        => 'customers',
				'rows_key'    => 'specific_users',
				'description' => __( 'the customers in customer_ids.', 'wholesalex' ),
			),
			'specific_roles'      => array(
				'storage'     => 'specific_roles',
				'ids'         => 'role_ids',
				'kind'        => 'roles',
				'rows_key'    => 'specific_roles',
				'description' => __( 'customers in the roles in role_ids.', 'wholesalex' ),
			),
		);
	}

	/**
	 * Return which products a dynamic rule can cover.
	 *
	 * @return array<string, array>
	 */
	public static function product_scopes(): array {
		return array(
			'all_products'       => array(
				'storage'     => 'all_products',
				'description' => __( 'every product.', 'wholesalex' ),
			),
			'include_products'   => array(
				'storage'     => 'products_in_list',
				'ids'         => 'product_ids',
				'kind'        => 'products',
				'rows_key'    => 'products_in_list',
				'description' => __( 'only the products in product_ids.', 'wholesalex' ),
			),
			'exclude_products'   => array(
				'storage'     => 'products_not_in_list',
				'ids'         => 'product_ids',
				'kind'        => 'products',
				'rows_key'    => 'products_not_in_list',
				'description' => __( 'every product except those in product_ids.', 'wholesalex' ),
			),
			'include_categories' => array(
				'storage'     => 'cat_in_list',
				'ids'         => 'category_ids',
				'kind'        => 'categories',
				'rows_key'    => 'cat_in_list',
				'description' => __( 'only products in the categories in category_ids.', 'wholesalex' ),
			),
			'exclude_categories' => array(
				'storage'     => 'cat_not_in_list',
				'ids'         => 'category_ids',
				'kind'        => 'categories',
				'rows_key'    => 'cat_not_in_list',
				'description' => __( 'every product except those in the categories in category_ids.', 'wholesalex' ),
			),
			'include_variations' => array(
				'storage'     => 'attribute_in_list',
				'ids'         => 'variation_ids',
				'kind'        => 'variations',
				'rows_key'    => 'attribute_in_list',
				'description' => __( 'only the product variations in variation_ids.', 'wholesalex' ),
			),
			'exclude_variations' => array(
				'storage'     => 'attribute_not_in_list',
				'ids'         => 'variation_ids',
				'kind'        => 'variations',
				'rows_key'    => 'attribute_not_in_list',
				'description' => __( 'every product except the variations in variation_ids.', 'wholesalex' ),
			),
		);
	}

	/**
	 * Return the cart condition fields, stored value => description.
	 *
	 * @return array<string, string>
	 */
	public static function condition_fields(): array {
		$fields = array(
			'cart_total_qty'    => __( 'number of items in the cart.', 'wholesalex' ),
			'cart_total_value'  => __( 'cart total in the store currency.', 'wholesalex' ),
			'cart_total_weight' => __( 'cart weight in the store weight unit.', 'wholesalex' ),
		);

		/**
		 * Filters the cart condition fields offered by the dynamic rule AI tools.
		 *
		 * Only add fields that your code evaluates.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, string> $fields Stored condition value => description.
		 */
		$fields = apply_filters( 'wholesalex_mcp_dynamic_rule_condition_fields', $fields );
		return is_array( $fields ) ? $fields : array();
	}

	/**
	 * Reject percentages above 100.
	 *
	 * @param array $payload Stored payload.
	 * @return true|\WP_Error
	 */
	public static function validate_discount( array $payload ) {
		if ( 'percentage' === ( $payload['_discount_type'] ?? '' ) && is_numeric( $payload['_discount_amount'] ?? null ) && (float) $payload['_discount_amount'] > 100 ) {
			return Fields::invalid( __( 'A percentage discount cannot be more than 100.', 'wholesalex' ) );
		}
		return true;
	}
}
