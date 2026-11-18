<?php
/**
 * Wholesale pricing rule types available to the AI tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Describes the wholesale pricing rule types, audiences, product scopes and
 * conditions.
 *
 * Each type stores its settings in one key of the rule, as the pricing editor
 * does: `regular` for wholesale prices, `product_discount`, `cart` and `bogo`.
 * Extensions add their own types with the `wholesalex_mcp_pricing_rule_types`
 * filter.
 */
final class Pricing_Rule_Types {

	/**
	 * Return the rule types keyed by stored `rule_type`.
	 *
	 * Each type has `label`, `description`, `storage_key`, `fields` and an
	 * optional `validate` callable that receives the stored settings. A type
	 * whose settings live in top-level rule fields sets `storage_key` to an
	 * empty string and provides `to_storage` (settings, stored rule => rule
	 * fields or WP_Error) and `from_storage` (stored rule => settings).
	 *
	 * @return array<string, array>
	 */
	public static function types(): array {
		$amount_type = array(
			'kind'        => 'enum',
			'storage'     => 'amount_type',
			'enum'        => array( 'percentage', 'amount', 'fixed' ),
			'default'     => 'percentage',
			'description' => __( 'percentage: a percent off. amount: this amount off the price. fixed: the price becomes this amount.', 'wholesalex' ),
		);
		$amount      = array(
			'kind'        => 'number',
			'storage'     => 'amount',
			'minimum'     => 0,
			'required'    => true,
			'description' => __( 'Discount value in the store currency, or 0 to 100 for a percentage.', 'wholesalex' ),
		);
		$badge       = array(
			'show_badge'             => array(
				'kind'        => 'boolean',
				'storage'     => 'show_badge',
				'default'     => false,
				'description' => __( 'Show an offer badge on product images.', 'wholesalex' ),
			),
			'badge_label'            => array(
				'kind'        => 'text',
				'storage'     => 'badge_label',
				'max_length'  => 50,
				'description' => __( 'Badge text.', 'wholesalex' ),
			),
			'badge_style'            => array(
				'kind'        => 'enum',
				'storage'     => 'badge_style',
				'enum'        => array( 'style_one', 'style_two', 'style_three', 'style_four', 'style_five' ),
				'description' => __( 'Badge shape, as shown in the pricing editor.', 'wholesalex' ),
			),
			'badge_position'         => array(
				'kind'        => 'enum',
				'storage'     => 'badge_position',
				'enum'        => array( 'left', 'right' ),
				'description' => __( 'Badge position on the product image.', 'wholesalex' ),
			),
			'badge_background_color' => array(
				'kind'        => 'color',
				'storage'     => 'badge_bg_color',
				'default'     => '#5a40e8',
				'description' => __( 'Badge background color, for example #5a40e8.', 'wholesalex' ),
			),
			'badge_text_color'       => array(
				'kind'        => 'color',
				'storage'     => 'badge_text_color',
				'default'     => '#ffffff',
				'description' => __( 'Badge text color, for example #ffffff.', 'wholesalex' ),
			),
		);

		$types = array(
			'wholesale_pricing' => array(
				'label'       => __( 'Wholesale price', 'wholesalex' ),
				'description' => __( 'Sets a wholesale price on the targeted products for the targeted customers, for example 20% off for the Wholesale role.', 'wholesalex' ),
				'storage_key' => 'regular',
				'fields'      => array(
					'amount_type' => $amount_type,
					'amount'      => $amount,
					'label'       => array(
						'kind'        => 'text',
						'storage'     => 'label',
						'max_length'  => 100,
						'description' => __( 'Optional price label shown to shoppers, for example "Wholesale price".', 'wholesalex' ),
					),
				),
				'validate'    => array( __CLASS__, 'validate_amount' ),
			),
			'product_discount'  => array(
				'label'       => __( 'Product discount', 'wholesalex' ),
				'description' => __( 'Discounts the targeted products, shown as a sale discount. The amount must be above 0 for the rule to be active.', 'wholesalex' ),
				'storage_key' => 'product_discount',
				'fields'      => array(
					'amount_type' => $amount_type,
					'amount'      => $amount,
					'label'       => array(
						'kind'        => 'text',
						'storage'     => 'label',
						'max_length'  => 100,
						'default'     => __( 'Product discount', 'wholesalex' ),
						'description' => __( 'Discount label shown to shoppers.', 'wholesalex' ),
					),
				),
				'validate'    => array( __CLASS__, 'validate_amount' ),
			),
			'cart_discount'     => array(
				'label'       => __( 'Cart discount', 'wholesalex' ),
				'description' => __( 'Discounts the whole cart for the targeted customers, usually combined with cart conditions such as a minimum quantity or value.', 'wholesalex' ),
				'storage_key' => 'cart',
				'fields'      => array(
					'discount_type'           => array(
						'kind'        => 'enum',
						'storage'     => 'discount_type',
						'enum'        => array( 'percentage', 'amount' ),
						'default'     => 'percentage',
						'description' => __( 'percentage: a percent off the cart. amount: this amount off the cart.', 'wholesalex' ),
					),
					'amount'                  => array(
						'kind'        => 'number',
						'storage'     => 'discount_amount',
						'minimum'     => 0,
						'required'    => true,
						'description' => __( 'Discount value in the store currency, or 0 to 100 for a percentage.', 'wholesalex' ),
					),
					'discount_name'           => array(
						'kind'        => 'text',
						'storage'     => 'discount_name',
						'max_length'  => 100,
						'description' => __( 'Discount name shown in the cart.', 'wholesalex' ),
					),
					'show_conditions'         => array(
						'kind'        => 'boolean',
						'storage'     => 'show_cart_discount_conditions',
						'description' => __( 'Show shoppers what they need to add to unlock the discount.', 'wholesalex' ),
					),
					'show_label_before_promo' => array(
						'kind'        => 'boolean',
						'storage'     => 'show_label_before_promo',
						'description' => __( 'Show label_text before the promotion text.', 'wholesalex' ),
					),
					'label_text'              => array(
						'kind'        => 'text',
						'storage'     => 'label_text',
						'max_length'  => 100,
						'description' => __( 'Promotion label, for example "Cart Discount".', 'wholesalex' ),
					),
					'promo_text'              => array(
						'kind'        => 'text',
						'storage'     => 'promo_desc_text',
						'max_length'  => 200,
						'description' => __( 'Promotion description, for example "After adding to the cart".', 'wholesalex' ),
					),
				),
				'validate'    => array( __CLASS__, 'validate_cart' ),
			),
			'bogo_discount'     => array(
				'label'       => __( 'Buy X get 1 discounted', 'wholesalex' ),
				'description' => __( 'For every X units of a targeted product in the cart, one unit is free. Shown as "Buy X Get 1 Discounted" on the Wholesale Price screen.', 'wholesalex' ),
				'storage_key' => 'bogo',
				'fields'      => array(
					'buy_quantity'    => array(
						'kind'        => 'integer',
						'storage'     => 'buy_x_qty',
						'minimum'     => 2,
						'default'     => 2,
						'description' => __( 'X: for every this many units of a product in the cart, one unit is free.', 'wholesalex' ),
					),
					'once_per_order'  => array(
						'kind'        => 'boolean',
						'storage'     => 'per_cart_once',
						'default'     => false,
						'description' => __( 'Give at most one free unit per order.', 'wholesalex' ),
					),
					'show_promo_text' => array(
						'kind'        => 'boolean',
						'storage'     => 'show_promo_text',
						'description' => __( 'Show the offer on product pages.', 'wholesalex' ),
					),
					'heading_text'    => array(
						'kind'        => 'text',
						'storage'     => 'heading_text',
						'max_length'  => 100,
						'description' => __( 'Offer heading on product pages.', 'wholesalex' ),
					),
					'offer_text'      => array(
						'kind'        => 'text',
						'storage'     => 'offer_text',
						'max_length'  => 100,
						'description' => __( 'Offer text on product pages, for example "100% Off on 1 Product".', 'wholesalex' ),
					),
				) + $badge,
			),
		);

		/**
		 * Filters the wholesale pricing rule types offered by the AI tools.
		 *
		 * Add an entry keyed by the stored `rule_type`, with `storage_key`
		 * naming the rule key that holds its settings. Only add types whose
		 * data your code saves and applies.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, array> $types Rule types.
		 */
		$types = apply_filters( 'wholesalex_mcp_pricing_rule_types', $types );

		$valid = array();
		foreach ( is_array( $types ) ? $types : array() as $type => $definition ) {
			if ( is_string( $type ) && 1 === preg_match( '/^[a-z0-9_]+$/', $type ) && is_array( $definition ) && isset( $definition['label'], $definition['description'], $definition['storage_key'] ) && is_array( $definition['fields'] ?? null ) ) {
				$valid[ $type ] = $definition;
			}
		}
		return $valid;
	}

	/**
	 * Return who a pricing rule can apply to.
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
				'storage'     => 'all_b2b',
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
				'rows_key'    => 'user_roles',
				'description' => __( 'customers in the roles in role_ids.', 'wholesalex' ),
			),
		);
	}

	/**
	 * Return which products a pricing rule can cover.
	 *
	 * @return array<string, array>
	 */
	public static function product_scopes(): array {
		$scopes = array(
			'all_products'       => array(
				'storage'     => 'all_products',
				'description' => __( 'every product.', 'wholesalex' ),
			),
			'include_products'   => array(
				'storage'     => 'specific_products',
				'ids'         => 'product_ids',
				'kind'        => 'products',
				'rows_key'    => 'products',
				'description' => __( 'only the products in product_ids.', 'wholesalex' ),
			),
			'include_variations' => array(
				'storage'     => 'specific_variations',
				'ids'         => 'variation_ids',
				'kind'        => 'variations',
				'rows_key'    => 'products',
				'description' => __( 'only the product variations in variation_ids.', 'wholesalex' ),
			),
			'include_categories' => array(
				'storage'     => 'specific_categories',
				'ids'         => 'category_ids',
				'kind'        => 'categories',
				'rows_key'    => 'categories',
				'description' => __( 'only products in the categories in category_ids.', 'wholesalex' ),
			),
		);

		/**
		 * Filters the product scopes offered by the pricing rule AI tools.
		 *
		 * Entries use the format documented in `Rule_Targets`. Only add scopes
		 * whose selections your code stores and applies.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, array> $scopes Product scopes.
		 */
		$scopes = apply_filters( 'wholesalex_mcp_pricing_rule_product_scopes', $scopes );
		return is_array( $scopes ) ? $scopes : array();
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
		 * Filters the cart condition fields offered by the pricing rule AI tools.
		 *
		 * Only add fields that your code evaluates.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, string> $fields Stored condition value => description.
		 */
		$fields = apply_filters( 'wholesalex_mcp_pricing_rule_condition_fields', $fields );
		return is_array( $fields ) ? $fields : array();
	}

	/**
	 * Reject percentages above 100 for price amounts.
	 *
	 * @param array $settings Stored settings.
	 * @return true|\WP_Error
	 */
	public static function validate_amount( array $settings ) {
		if ( 'percentage' === ( $settings['amount_type'] ?? '' ) && is_numeric( $settings['amount'] ?? null ) && (float) $settings['amount'] > 100 ) {
			return Fields::invalid( __( 'A percentage discount cannot be more than 100.', 'wholesalex' ) );
		}
		return true;
	}

	/**
	 * Reject percentages above 100 for cart discounts.
	 *
	 * @param array $settings Stored settings.
	 * @return true|\WP_Error
	 */
	public static function validate_cart( array $settings ) {
		if ( 'percentage' === ( $settings['discount_type'] ?? '' ) && is_numeric( $settings['discount_amount'] ?? null ) && (float) $settings['discount_amount'] > 100 ) {
			return Fields::invalid( __( 'A percentage discount cannot be more than 100.', 'wholesalex' ) );
		}
		return true;
	}
}
