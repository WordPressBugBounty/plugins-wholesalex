<?php
/**
 * WholesaleX Dynamic Rules - Main Orchestrator
 *
 * Contains fields, price engine, dispatch logic, and common functions.
 * Delegates to rule handlers, condition engine, REST API, and data provider.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Shipping_Zones;

/**
 * Dynamic_Rules - Main orchestrator class
 */
class Dynamic_Rules {

	// ─── Properties ──────────────────────────────────────────────

	// Core pricing and discount properties.
	/**
	 * Discount src.
	 *
	 * @var string|float
	 */
	public $discount_src = '';
	/**
	 * First sale price generator.
	 *
	 * @var string|float
	 */
	public $first_sale_price_generator = '';
	/**
	 * Is wholesalex base price applied.
	 *
	 * @var bool
	 */
	public $is_wholesalex_base_price_applied = false;
	/**
	 * Price.
	 *
	 * @var string|float
	 */
	public $price = '';

	// Internal state management.
	/**
	 * Valid dynamic rules.
	 *
	 * @var array
	 */
	private $valid_dynamic_rules = array();
	/**
	 * Current rules used by the single cart fee callback.
	 *
	 * @var array
	 */
	private $cart_rules = array();
	/**
	 * Whether the cart fee callback has already been registered.
	 *
	 * @var bool
	 */
	private $cart_fee_callback_registered = false;
	/**
	 * Rule data.
	 *
	 * @var array
	 */
	private $rule_data = array();
	/**
	 * Current shipping zone.
	 *
	 * @var string|float
	 */
	private $current_shipping_zone = '';
	/**
	 * Cached shipping method id.
	 *
	 * @var array
	 */
	private $cached_shipping_method_id = array();

	/**
	 * Cu order counts.
	 *
	 * @var int
	 */
	public static $cu_order_counts = 0;
	/**
	 * Cu total spent.
	 *
	 * @var int
	 */
	public static $cu_total_spent = 0;
	/**
	 * Total cart counts.
	 *
	 * @var string|float
	 */
	public static $total_cart_counts = '';
	/**
	 * Total unique item on cart.
	 *
	 * @var string|float
	 */
	public static $total_unique_item_on_cart = '';

	// ─── Rule Handler Instances (Active Only) ────────────────────

	// Core rule handlers actually used by the orchestrator.
	/**
	 * Rule tax.
	 *
	 * @var Rule_Tax
	 */
	private $rule_tax;
	/**
	 * Rule shipping.
	 *
	 * @var Rule_Shipping
	 */
	private $rule_shipping;
	/**
	 * Rule payment gateway.
	 *
	 * @var Rule_Payment_Gateway
	 */
	private $rule_payment_gateway;
	/**
	 * Rule buy x get one.
	 *
	 * @var Rule_Buy_X_Get_One
	 */
	private $rule_buy_x_get_one;
	/**
	 * Rule cart discount.
	 *
	 * @var Rule_Cart_Discount
	 */
	private $rule_cart_discount;
	/**
	 * Rule payment discount.
	 *
	 * @var Rule_Payment_Discount
	 */
	private $rule_payment_discount;
	/**
	 * Rule min order qty.
	 *
	 * @var Rule_Min_Order_Qty
	 */
	private $rule_min_order_qty;

	// ─── Constructor ─────────────────────────────────────────────

	/**
	 * Initialize handlers and register hooks.
	 */
	public function __construct() {
		add_filter( 'wholesalex_rule_target_matches', array( __CLASS__, 'provider_target_matches' ), 10, 4 );
		add_filter( 'wholesalex_rule_conditions_match', array( __CLASS__, 'provider_conditions_match' ), 10, 3 );
		add_filter( 'wholesalex_rule_target_data', array( __CLASS__, 'provider_target_data' ), 10, 2 );

		$this->instantiate_handlers();

		// REST API is self-contained in Dynamic_Rules_Rest_Api class.
		// No hook registration needed here.

		// Order / Cart session hooks.
		// Classic checkout (shortcode-based) fires woocommerce_checkout_create_order.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'add_custom_meta_on_wholesale_order' ), 10 );
		// WooCommerce Blocks / Store API checkout never fires woocommerce_checkout_create_order;
		// it uses woocommerce_store_api_checkout_update_order_meta instead.
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'add_custom_meta_on_wholesale_order' ), 10 );
		add_action( 'woocommerce_checkout_order_created', array( $this, 'record_order_rule_usage' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'record_order_rule_usage' ) );
		add_action( 'woocommerce_update_cart_action_cart_updated', array( $this, 'update_discounted_product' ) );

		// PPOM compatibility.
		add_filter( 'ppom_product_price', array( $this, 'product_price' ), 10, 2 );
		add_filter( 'ppom_product_price_on_cart', array( $this, 'set_price_on_ppom' ), 10, 2 );

		// ProductX compatibility.
		add_filter( 'wopb_query_args', array( $this, 'modify_wopb_query_args' ) );

		// Main dispatch.
		add_action( 'wp_loaded', array( $this, 'get_valid_dynamic_rules' ) );
		add_action( 'rest_api_init', array( $this, 'get_valid_dynamic_rules_for_rest' ), 1 );

		// Admin order item AJAX runs as the store manager/admin. Use the selected
		// order customer so manual orders receive that customer's B2B pricing.
		add_filter( 'wholesalex_set_current_user', array( $this, 'set_admin_order_customer_as_current_user' ) );
		add_filter( 'wholesalex_dynamic_rule_user_id', array( $this, 'set_admin_order_customer_as_current_user' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_order_pricing_context_script' ), 99 );
		add_action( 'woocommerce_ajax_order_items_added', array( $this, 'apply_admin_order_customer_pricing_to_added_items' ), 1, 2 );
		add_action( 'woocommerce_before_save_order_item', array( $this, 'apply_admin_order_customer_pricing_before_item_save' ), 5 );

		// WooCommerce Blocks.
		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			$this->action_after_woo_block_loaded();
		} else {
			add_action( 'woocommerce_blocks_loaded', array( $this, 'action_after_woo_block_loaded' ) );
		}

		// BOGO badges - delegate to Rule_Buy_X_Get_One.
		add_filter( 'wopb_after_loop_image', array( $this->rule_buy_x_get_one, 'wopb_wholesalex_bogo_display_sale_badge' ), 10 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this->rule_buy_x_get_one, 'wholesalex_bogo_display_sale_badge' ), 10 );
		add_action( 'wholesalex_after_frontend_enqueue_scripts', array( $this->rule_buy_x_get_one, 'wholesalex_bogo_single_page_display_sale_badge' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this->rule_buy_x_get_one, 'wholesalex_bogo_badge_add_custom_css' ), 20 );

		// Astra renders its mini-cart in a separate AJAX fragment request. Register
		// this compatibility filter before pricing rules are loaded on wp_loaded so
		// it is also available during fragment-only requests.
		add_filter( 'woocommerce_widget_cart_item_quantity', array( $this, 'filter_astra_mini_cart_item_quantity' ), 999, 3 );

		// Share the legacy resolver's selected tier source with Wholesale Pricing
		// so only the winning engine changes the cart price and renders a table.
	}

	/**
	 * Return the tier candidate selected by the shared pricing priority order.
	 *
	 * @param array           $tier_data Existing tier data.
	 * @param int|\WC_Product $product Product object or ID.
	 * @return array
	 */
	public function get_active_tier_data( $tier_data, $product ) {
		$product_id = is_object( $product ) && is_callable( array( $product, 'get_id' ) ) ? $product->get_id() : absint( $product );
		$resolved   = $this->get_active_tier( $product_id );
		return empty( $resolved ) ? $tier_data : $resolved;
	}

	/**
	 * Whether the pricing engine has resolved tier data for a product in this request.
	 *
	 * @param int $product_id Product or variation ID.
	 * @return bool
	 */
	public function has_active_tier( $product_id ) {
		return ! empty( $this->get_active_tier( $product_id ) );
	}

	/**
	 * Tier data resolved for a product in this request.
	 *
	 * @param int $product_id Product or variation ID.
	 * @return array
	 */
	public function get_active_tier( $product_id ) {
		return apply_filters( 'wholesalex_dynamic_rule_resolved_data', array(), $product_id );
	}

	/**
	 * Instantiate only the rule handlers that are actively used by the orchestrator.
	 * Unused handlers are removed to optimize memory and performance.
	 */
	private function instantiate_handlers() {
		// Core rule handlers used in main dispatch.
		$this->rule_tax             = new Rule_Tax();
		$this->rule_shipping        = new Rule_Shipping();
		$this->rule_payment_gateway = new Rule_Payment_Gateway();
		$this->rule_min_order_qty   = new Rule_Min_Order_Qty();

		// Cart and discount handlers used in fee calculation.
		$this->rule_buy_x_get_one    = new Rule_Buy_X_Get_One();
		$this->rule_cart_discount    = new Rule_Cart_Discount();
		$this->rule_payment_discount = new Rule_Payment_Discount();

		// Note: REST API is self-contained and instantiates itself.
		// Note: Unused rule handlers (product_discount, max_order_qty, etc.)
		// are removed to optimize performance and reduce memory usage.
	}

	/**
	 * Evaluate generic targeting through the public provider contract.
	 *
	 * @param bool|null $matched Previously supplied result.
	 * @param int       $product_id Product ID.
	 * @param int       $variation_id Variation ID.
	 * @param array     $filter Parsed targeting data.
	 * @return bool
	 */
	public static function provider_target_matches( $matched, $product_id, $variation_id, $filter ) {
		return is_bool( $matched ) ? $matched : Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $filter );
	}
	/**
	 * Evaluate generic conditions through the public provider contract.
	 *
	 * @param bool|null $matched Previously supplied result.
	 * @param array     $conditions Condition rows.
	 * @param array     $filter Parsed targeting data.
	 * @return bool
	 */
	public static function provider_conditions_match( $matched, $conditions, $filter ) {
		return is_bool( $matched ) ? $matched : Dynamic_Rules_Condition_Engine::check_rule_conditions( $conditions, $filter );
	}
	/**
	 * Parse generic target data through the public provider contract.
	 *
	 * @param array $parsed Previously supplied target data.
	 * @param array $record Raw rule record.
	 * @return array
	 */
	public static function provider_target_data( $parsed, $record ) {
		return ! empty( $parsed ) ? $parsed : Dynamic_Rules_Condition_Engine::get_filtered_rules( $record );
	}

	// ─── Pass-through to Condition Engine ─────────────────────────

	/**
	 * Check rule conditions.
	 *
	 * @param array $conditions Conditions.
	 * @param array $rule_filter Product targeting filter.
	 */
	public static function check_rule_conditions( $conditions, $rule_filter = array() ) {
		return Dynamic_Rules_Condition_Engine::check_rule_conditions( $conditions, $rule_filter );
	}

	/**
	 * Is eligible for rule.
	 *
	 * @param int   $product_id Product ID.
	 * @param int   $variation_id Variation ID.
	 * @param array $filter Filter.
	 */
	public static function is_eligible_for_rule( $product_id, $variation_id, $filter ) {
		return Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $filter );
	}

	/**
	 * Get multiselect values.
	 *
	 * @param array  $data Data.
	 * @param string $type Type.
	 */
	public static function get_multiselect_values( $data, $type = 'value' ) {
		return Dynamic_Rules_Condition_Engine::get_multiselect_values( $data, $type );
	}

	/**
	 * Get product attributes.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function get_product_attributes( $product_id ) {
		return Dynamic_Rules_Condition_Engine::get_product_attributes( $product_id );
	}

	/**
	 * Get filtered rules.
	 *
	 * @param array $discount Discount.
	 */
	public function get_filtered_rules( $discount ) {
		return Dynamic_Rules_Condition_Engine::get_filtered_rules( $discount );
	}

	/**
	 * Compare by priority.
	 *
	 * @param array $a A.
	 * @param array $b B.
	 */
	public function compare_by_priority( $a, $b ) {
		return Dynamic_Rules_Condition_Engine::compare_by_priority( $a, $b );
	}

	/**
	 * Compare by priority reverse.
	 *
	 * @param array $a A.
	 * @param array $b B.
	 */
	public function compare_by_priority_reverse( $a, $b ) {
		return Dynamic_Rules_Condition_Engine::compare_by_priority_reverse( $a, $b );
	}

	/**
	 * Check whether one configured pricing source has priority over another.
	 *
	 * A source that is not available for the current store must not participate in
	 * priority comparisons. For example, new stores do not have dynamic rules in
	 * their pricing priority configuration.
	 *
	 * @param array  $flipped_priority Pricing source names mapped to their positions.
	 * @param string $source           Pricing source to check.
	 * @param string $other_source     Pricing source to compare against.
	 * @return bool
	 */
	public function has_higher_pricing_priority( $flipped_priority, $source, $other_source ) {
		return isset( $flipped_priority[ $source ], $flipped_priority[ $other_source ] )
			&& $flipped_priority[ $source ] < $flipped_priority[ $other_source ];
	}

	/**
	 * Check whether a rule is within its configured usage and date limits.
	 *
	 * @param array      $__limits Limits.
	 * @param int|string $rule_id Rule identifier.
	 */
	public static function has_limit( $__limits, $rule_id = 0 ) {
		return Dynamic_Rules_Condition_Engine::has_limit( $__limits, $rule_id );
	}

	/**
	 * Check whether the configured rule conditions are fulfilled.
	 *
	 * @param array $conditions Conditions.
	 * @param array $rule_filter Product targeting filter.
	 */
	public static function is_conditions_fullfiled( $conditions, $rule_filter = array() ) {
		return Dynamic_Rules_Condition_Engine::is_conditions_fullfiled( $conditions, $rule_filter );
	}

	/**
	 * Delegate a condition check to the legacy condition engine.
	 *
	 * @param string     $operator Comparison operator.
	 * @param float      $condition_value Threshold.
	 * @param float|null $actual_value Runtime value.
	 */
	public static function is_condition_passed( $operator, $condition_value, $actual_value = null ) {
		return null !== $actual_value && Dynamic_Rules_Condition_Engine::is_condition_passed( $operator, $condition_value, $actual_value );
	}

	/**
	 * Is user order count purchase amount condition passed.
	 *
	 * @param array $conditions Conditions.
	 */
	public static function is_user_order_count_purchase_amount_condition_passed( $conditions ) {
		return Dynamic_Rules_Condition_Engine::is_user_order_count_purchase_amount_condition_passed( $conditions );
	}

	/**
	 * Restore smart tags.
	 *
	 * @param array  $smart_tags Smart tags.
	 * @param string $new_string Template containing smart-tag placeholders.
	 */
	public function restore_smart_tags( $smart_tags, $new_string ) {
		return Dynamic_Rules_Condition_Engine::restore_smart_tags( $smart_tags, $new_string );
	}

	/**
	 * Filter empty items.
	 *
	 * @param array $item Item.
	 */
	public function filter_empty_items( $item ) {
		return Dynamic_Rules_Condition_Engine::filter_empty_items( $item );
	}

	// ─── Pass-through to Data Provider ───────────────────────────

	/**
	 * Get tax classes.
	 */
	public static function get_tax_classes() {
		return Dynamic_Rules_Data_Provider::get_tax_classes();
	}

	/**
	 * Get shipping zones.
	 */
	public static function get_shipping_zones() {
		return Dynamic_Rules_Data_Provider::get_shipping_zones();
	}

	// ─── Static API Methods ──────────────────────────────────────

	/**
	 * Return dynamic rules available to the current user.
	 */
	public static function dynamic_rules_get() {
		$__dynamic_rules = array_values( wholesalex()->get_dynamic_rules() );

		// Use is_admin() for true admin page loads, or current_user_can() for
		// REST API requests where is_admin() returns false even for admins.
		if ( is_admin() || current_user_can( 'manage_options' ) ) {
			$__dynamic_rules = wholesalex()->get_dynamic_rules();
		} else {
			$__dynamic_rules = wholesalex()->get_dynamic_rules_by_user_id( get_current_user_id() );
		}
		$__dynamic_rules = apply_filters( 'wholesalex_get_all_dynamic_rules', array_values( $__dynamic_rules ) );
		if ( empty( $__dynamic_rules ) ) {
			$__dynamic_rules = array();
		}
		return $__dynamic_rules;
	}

	// ─── Simple Utility Methods ──────────────────────────────────


	/**
	 * Distinguish a valid numeric price, including zero, from an absent sale.
	 *
	 * @param mixed $price Candidate price.
	 * @return bool
	 */
	private function is_available_price( $price ): bool {
		return is_numeric( $price ) && is_finite( (float) $price ) && (float) $price >= 0;
	}

	/**
	 * Get actual discount price.
	 *
	 * @param \WC_Product  $product Product.
	 * @param float|string $regular_price Regular price.
	 * @param float|string $sale_price Sale price.
	 */
	public function get_actual_discount_price( $product, $regular_price, $sale_price ) {
		$is_regular_price = wholesalex()->get_setting( '_is_sale_or_regular_Price', 'is_regular_price' );
		if ( 'is_regular_price' === $is_regular_price ) {
			return $regular_price;
		} else {
			$actual_sale_price = $sale_price ? $sale_price : get_post_meta( $product->get_id(), '_sale_price', true );
			if ( ! isset( $actual_sale_price ) || empty( $actual_sale_price ) || '' === $actual_sale_price ) {
				$actual_sale_price = $regular_price;
			}
			return $actual_sale_price;
		}
	}

	/**
	 * Apply product-specific discounts to the sale price.
	 *
	 * @param float|string $sale_price Sale price.
	 * @param \WC_Product  $product Product.
	 */
	public function single_product_discounts( $sale_price, $product ) {
		$__product_id = $product->get_id();
		$this->set_initial_sale_price_to_session( __FUNCTION__, $__product_id, $sale_price );
		$__discounts_result = apply_filters(
			'wholesalex_single_product_discount_action',
			array(
				'sale_price' => $sale_price,
				'product'    => $product,
			)
		);
		$sale_price         = $__discounts_result['sale_price'];
		if ( isset( $__discounts_result['discount_src'] ) ) {
			$this->discount_src = $__discounts_result['discount_src'];
		}
		if ( empty( $sale_price ) ) {
			return;
		} else {
			$this->set_discounted_product( $__product_id );
			$this->price = $sale_price;
			return $sale_price;
		}
	}

	/**
	 * Apply category discounts to the sale price.
	 *
	 * @param float|string $sale_price Sale price.
	 * @param \WC_Product  $product Product.
	 */
	public function category_discounts( $sale_price, $product ) {
		$__product_id = $product->get_id();
		$this->set_initial_sale_price_to_session( __FUNCTION__, $__product_id, $sale_price );
		$__discounts_result = apply_filters(
			'wholesalex_category_discount_action',
			array(
				'sale_price' => $sale_price,
				'product'    => $product,
			)
		);
		$sale_price         = $__discounts_result['sale_price'];
		if ( isset( $__discounts_result['discount_src'] ) ) {
			$this->discount_src = $__discounts_result['discount_src'];
		}
		if ( empty( $sale_price ) ) {
			return;
		} else {
			$this->set_discounted_product( $__product_id );
			$this->price = $sale_price;
			return $sale_price;
		}
	}

	// ─── Session / Order Helpers ─────────────────────────────────

	/**
	 * Resolve the WholesaleX order type from a WholesaleX user role.
	 *
	 * @param string $__user_role WholesaleX user role.
	 * @return string
	 */
	public function get_order_type_from_user_role( $__user_role ) {
		if ( 'wholesalex_guest' === $__user_role ) {
			return 'guest';
		}

		return in_array( $__user_role, array( '', 'wholesalex_b2c_users' ), true ) ? 'b2c' : 'b2b';
	}

	/**
	 * Set discounted product.
	 *
	 * @param int $product_id Product ID.
	 */
	public function set_discounted_product( $product_id ) {
		if ( is_admin() || null === WC()->session ) {
			return;
		}
		$__discounted_product = null !== WC()->session ? WC()->session->get( '__wholesalex_discounted_products' ) : '';
		if ( ! ( isset( $__discounted_product ) && is_array( $__discounted_product ) ) ) {
			$__discounted_product = array();
		}
		$__discounted_product[ $product_id ] = true;
		WC()->session->set( '__wholesalex_discounted_products', $__discounted_product );
	}

	/**
	 * Add custom meta on wholesale order.
	 *
	 * @param \WC_Order $order Order.
	 */
	public function add_custom_meta_on_wholesale_order( $order ) {
		if ( is_admin() ) {
			return;
		}

		$__user_role = wholesalex()->get_current_user_role();
		$order_type  = $this->get_order_type_from_user_role( $__user_role );
		$order->update_meta_data( '__wholesalex_order_type', $order_type );
		if ( 'b2b' === $order_type ) {
			$order->update_meta_data( '__wholesalex_order_role', $__user_role );
		}

		if ( null === WC()->session ) {
			return;
		}

		$__discounted_product = WC()->session->get( '__wholesalex_discounted_products' );
		$__dynamic_rule_id    = WC()->session->get( '__wholesalex_used_dynamic_rule' );
		if ( ! empty( $__dynamic_rule_id ) ) {
			$order->update_meta_data( '__wholesalex_dynamic_rule_ids', $__dynamic_rule_id );
		}

		$__ordered_discounted_product = array();
		$items                        = $order->get_items();
		foreach ( $items as $item ) {
			$product_id           = $item->get_product_id();
			$product_variation_id = $item->get_variation_id();
			if ( isset( $__discounted_product[ $product_id ] ) ) {
				$__ordered_discounted_product[] = $product_id;
			}
			if ( isset( $__discounted_product[ $product_variation_id ] ) ) {
				$__ordered_discounted_product[] = $product_variation_id;
			}
		}
		if ( ! empty( $__ordered_discounted_product ) ) {
			$order->update_meta_data( '__wholesalex_discounted_products', array_unique( $__ordered_discounted_product ) );
		}
		WC()->session->set( '__wholesalex_discounted_products', array() );
	}

	/** Count a placed order once. Cancellations do not restore lifetime redemptions.
	 *
	 * The order identity is stored with its usage record in the same atomic option
	 * mutation as the count, so a retried checkout hook cannot increment twice.
	 *
	 * @param \WC_Order $order Persisted checkout order.
	 * @throws \RuntimeException When usage persistence fails.
	 * @return void
	 */
	public function record_order_rule_usage( $order ) {
		if ( ! $order instanceof \WC_Order || ! $order->get_id() ) {
			return; }
		$ids = $order->get_meta( '__wholesalex_dynamic_rule_ids', true );
		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return; }
		$order_id = (string) $order->get_id();
		$result   = wholesalex()->mutate_dynamic_rules(
			static function ( $rules ) use ( $ids, $order_id ) {
				foreach ( $ids as $id => $used ) {
					if ( isset( $rules[ $id ] ) && ( ( isset( $rules[ $id ]['limit'] ) && ! is_array( $rules[ $id ]['limit'] ) ) || ( isset( $rules[ $id ]['limit']['usage_orders'] ) && ! is_array( $rules[ $id ]['limit']['usage_orders'] ) ) ) ) {
						return new \WP_Error( 'wholesalex_invalid_usage', __( 'The rule usage record is invalid.', 'wholesalex' ) );
					}
					if ( 1 !== (int) $used || ! isset( $rules[ $id ] ) || isset( $rules[ $id ]['limit']['usage_orders'][ $order_id ] ) ) {
						continue; }
					$rules[ $id ]['limit']['usages_count']              = (int) ( $rules[ $id ]['limit']['usages_count'] ?? 0 ) + 1;
					$rules[ $id ]['limit']['usage_orders'][ $order_id ] = true;
				}
				return $rules;
			}
		);
		if ( is_wp_error( $result ) ) {
			throw new \RuntimeException( esc_html( $result->get_error_message() ) );
		}
	}

	/**
	 * Update discounted product.
	 *
	 * @param bool $cart_updated Cart updated.
	 */
	public function update_discounted_product( $cart_updated ) {
		if ( is_admin() || null === WC()->session ) {
			return $cart_updated;
		}
		WC()->session->set( '__wholesalex_discounted_products', array() );
		WC()->session->set( '__wholesalex_used_dynamic_rule', array() );
		return $cart_updated;
	}

	// ─── Currency Compatibility ──────────────────────────────────

	/**
	 * Convert a displayed price back to the base currency.
	 *
	 * @param float|string $price Price.
	 */
	public function price_after_currency_changed( $price ) {
		$price = floatval( $price );
		if ( defined( 'WOPB_VER' ) && defined( 'WOPB_PRO_VER' ) && class_exists( 'WOPB_PRO\Currency_Switcher_Action' ) ) {
			$current_currency_code = wopb_function()->get_setting( 'wopb_current_currency' );
			$default_currency      = wopb_function()->get_setting( 'wopb_default_currency' );
			$current_currency      = \WOPB_PRO\Currency_Switcher_Action::get_currency( $current_currency_code );
			if ( ! $current_currency ) {
				$current_currency = $default_currency;
			}
			if ( $current_currency_code !== $default_currency ) {
				$wopb_current_currency_rate = floatval( ( isset( $current_currency['wopb_currency_rate'] ) && $current_currency['wopb_currency_rate'] > 0 && ! ( '' === $current_currency['wopb_currency_rate'] ) ) ? $current_currency['wopb_currency_rate'] : 1 );
				$wopb_current_exchange_fee  = floatval( ( isset( $current_currency['wopb_currency_exchange_fee'] ) && $current_currency['wopb_currency_exchange_fee'] >= 0 && ! ( '' === $current_currency['wopb_currency_exchange_fee'] ) ) ? $current_currency['wopb_currency_exchange_fee'] : 0 );
				$total_rate                 = ( $wopb_current_currency_rate + $wopb_current_exchange_fee );
				return $price / $total_rate;
			}
		}
		if ( defined( 'WOOMULTI_CURRENCY_F_VERSION' ) && function_exists( 'wmc_revert_price' ) ) {
			$curcy = \WOOMULTI_CURRENCY_F_Data::get_ins();
			if ( $curcy->get_enable() ) {
				$price = wmc_revert_price( $price );
			}
		}
		if ( defined( 'YAY_CURRENCY_VERSION' ) && function_exists( 'Yay_Currency\\plugin_init' ) ) {
			if ( method_exists( '\Yay_Currency\Helpers\Helper', 'default_currency_code' ) && method_exists( '\Yay_Currency\Helpers\YayCurrencyHelper', 'detect_current_currency' ) ) {
				$applied_currency    = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
				$is_default_currency = \Yay_Currency\Helpers\Helper::default_currency_code() === $applied_currency['currency'];
				if ( ! $is_default_currency ) {
					$total_rate = \Yay_Currency\Helpers\YayCurrencyHelper::get_rate_fee( $applied_currency );
					return ( floatval( $price / $total_rate ) );
				}
			}
		}
		if ( defined( 'WOOCS_VERSION' ) && class_exists( 'WOOCS' ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- WOOCS exposes this global variable as its integration API.
			global $WOOCS;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- WOOCS exposes this global variable as its integration API.
			if ( isset( $WOOCS ) && $WOOCS->is_multiple_allowed ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- WOOCS exposes this global variable as its integration API.
				$price = $WOOCS->woocs_back_convert_price( $price );
			}
		}
		return $price;
	}

	// ─── Plugin Compatibility Helpers ────────────────────────────

	/**
	 * Check whether subscription schemes are available for the product.
	 *
	 * @param \WC_Product $product Product.
	 */
	public function is_enable_subscriptions_product_woo( $product ) {
		if ( in_array( 'woocommerce-all-products-for-subscriptions/woocommerce-all-products-for-subscriptions.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- active_plugins is a WordPress core filter.
			if ( class_exists( 'WCS_ATT_Product_Schemes' ) ) {
				if ( \WCS_ATT_Product_Schemes::get_subscription_schemes( $product ) ) {
					return true;
				} else {
					return false;
				}
			}
		}
	}

	/**
	 * Set initial sale price to session.
	 *
	 * @param string       $__function_name Pricing callback that supplied the initial price.
	 * @param int|string   $id Id.
	 * @param float|string $sale_price Sale price.
	 */
	public function set_initial_sale_price_to_session( $__function_name, $id, $sale_price ) {
		if ( isset( WC()->session ) && ! is_admin() ) {
			if ( $__function_name === $this->first_sale_price_generator ) {
				$__wholesale_products        = WC()->session->get( '__wholesalex_wholesale_products' );
				$__wholesale_products[ $id ] = $this->price_after_currency_changed( $sale_price );
				WC()->session->set( '__wholesalex_wholesale_products', $__wholesale_products );
			}
		}
	}

	/**
	 * Return the displayed product price for PPOM.
	 *
	 * @param float|string $price Price.
	 * @param \WC_Product  $product Product.
	 */
	public function product_price( $price, $product ) {
		if ( ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) ) {
			if ( empty( $product->get_sale_price() ) ) {
				$price = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
			} else {
				$price = wc_get_price_to_display( $product, array( 'price' => $product->get_sale_price() ) );
			}
		}
		return $price;
	}

	/**
	 * Supply the cart item sale price to PPOM.
	 *
	 * @param float|string $price Price.
	 * @param array        $cart_item Cart item.
	 */
	public function set_price_on_ppom( $price, $cart_item ) {
		$__product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$__product    = wc_get_product( $__product_id );
		return $__product->get_sale_price();
	}

	/**
	 * Exclude hidden wholesale products from ProductX queries.
	 *
	 * @param array $query_args Query args.
	 */
	public function modify_wopb_query_args( $query_args ) {
		$query_args['post__not_in'] = isset( $query_args['post__not_in'] ) ? array_merge( $query_args['post__not_in'], (array) wholesalex()->hidden_product_ids() ) : (array) wholesalex()->hidden_product_ids(); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Hidden products must be excluded from the third-party query.
		return $query_args;
	}

	/**
	 * Check whether YITH bundle data references the product.
	 *
	 * @param int $product_id Product ID.
	 */
	private function is_product_in_bundle( $product_id ) {
		if ( ! is_plugin_active( 'yith-woocommerce-product-bundles/init.php' ) ) {
			return false;
		}
		$bundle_data = get_post_meta( $product_id, '_yith_wcpb_bundle_data', true );
		if ( ! empty( $bundle_data ) ) {
			return true;
		}
		$args  = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bundle membership is stored only in YITH product metadata.
				array(
					'key'     => '_yith_wcpb_bundle_data',
					'value'   => '"' . $product_id . '"',
					'compare' => 'LIKE',
				),
			),
		);
		$query = new \WP_Query( $args );
		if ( $query->have_posts() ) {
			return true;
		}
		return false;
	}

	/**
	 * Calculate actual sale price.
	 *
	 * @param int  $product_id Product ID.
	 * @param bool $is_variable Whether the product has variations.
	 */
	public function calculate_actual_sale_price( $product_id, $is_variable = false ) {
		$__current_role_id = wholesalex()->get_current_user_role();
		$price             = floatval( get_post_meta( $product_id, '_price', true ) );
		$regular_price     = floatval( get_post_meta( $product_id, '_regular_price', true ) );
		$sale_price        = floatval( get_post_meta( $product_id, '_sale_price', true ) );
		if ( $is_variable ) {
			return $price;
		}
		if ( isset( $__current_role_id ) ) {
			$_user_role_base_price = floatval( get_post_meta( $product_id, $__current_role_id . '_base_price', true ) );
			$_user_role_sale_price = floatval( get_post_meta( $product_id, $__current_role_id . '_sale_price', true ) );
			if ( ! empty( $_user_role_sale_price ) ) {
				return $_user_role_sale_price;
			} elseif ( ! empty( $_user_role_base_price ) ) {
				return $_user_role_base_price;
			}
		}
		$price = $sale_price && 0.0 !== $sale_price ? $sale_price : $regular_price;
		return $price;
	}

	/**
	 * Get the Aelia sale price in the requested currency.
	 *
	 * @param int         $product_id Product ID.
	 * @param string|null $currency Currency.
	 */
	public function get_converted_sale_price_from_aelia( $product_id, $currency = null ) {
		if ( class_exists( 'WC_Aelia_CurrencyPrices_Manager' ) && method_exists( 'WC_Aelia_CurrencyPrices_Manager', 'Instance' ) && function_exists( 'aelia_get_object_aux_data' ) ) {
			$product = wc_get_product( $product_id );
			if ( ! $product instanceof \WC_Product ) {
				return false;
			}
			if ( ! $currency ) {
				$currency = get_woocommerce_currency();
			}
			$converted_product    = \WC_Aelia_CurrencyPrices_Manager::Instance()->convert_product_prices( $product, $currency );
			$converted_sale_price = aelia_get_object_aux_data( $converted_product, 'sale_price' );
			return ( null !== $converted_sale_price ) ? $converted_sale_price : $product->get_sale_price();
		}
		return false;
	}

	/**
	 * Make product non purchasable and remove add to cart.
	 *
	 * @param \WC_Product|false $product Product.
	 */
	public function make_product_non_purchasable_and_remove_add_to_cart( $product = false ) {
		add_filter( 'woocommerce_is_purchasable', '__return_false' );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		if ( $product ) {
			remove_all_actions( 'woocommerce_' . $product->get_type() . '_add_to_cart' );
		}
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	}

	// ─── WooCommerce Blocks ──────────────────────────────────────

	/**
	 * Register WooCommerce Blocks integrations.
	 */
	public function action_after_woo_block_loaded() {
		if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			return;
		}
		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'wholesalex-payment-discount',
				'callback'  => function ( $data ) {
					if ( isset( $data['selected_gateway'] ) ) {
						WC()->session->set( 'chosen_payment_method', $data['selected_gateway'] );
					}
				},
			)
		);
	}

	// ─── Price Calculation Methods ───────────────────────────────

	/**
	 * Get role base sale price.
	 *
	 * @param \WC_Product $product Product.
	 * @param int|string  $user_id User or pricing role identifier.
	 */
	public function get_role_base_sale_price( $product, $user_id = '' ) {
		return get_post_meta( $product->get_id(), $user_id . '_sale_price', true );
	}

	/** Scope non-global creators to products they still own.
	 *
	 * This also prevents historical vendor campaigns from affecting other sellers
	 * after a product changes author, independently of who views the storefront.
	 *
	 * @param array $rule Saved campaign.
	 * @return array
	 */
	private function scope_rule_to_owner( array $rule ): array {
		$owner      = (int) ( $rule['created_by'] ?? 0 );
		$capability = apply_filters( 'wholesalex_capability_access', 'manage_options' );
		if ( ! $owner || user_can( $owner, 'manage_options' ) || user_can( $owner, $capability ) ) {
			return $rule; }
		if ( ! in_array( $rule['_rule_type'] ?? '', array( 'product_discount', 'min_order_qty', 'buy_x_get_one' ), true ) || 'products_in_list' !== ( $rule['_product_filter'] ?? '' ) || ! is_array( $rule['products_in_list'] ?? null ) ) {
			return array(); }
		$rule['products_in_list'] = array_values(
			array_filter(
				$rule['products_in_list'],
				static function ( $item ) use ( $owner ) {
					$id      = is_array( $item ) && is_scalar( $item['value'] ?? null ) ? absint( $item['value'] ) : 0;
					$product = $id ? wc_get_product( $id ) : false;
					return $product && (int) get_post_field( 'post_author', $product->get_parent_id() ? $product->get_parent_id() : $id ) === $owner;
				}
			)
		);
		return empty( $rule['products_in_list'] ) ? array() : $rule;
	}

	/**
	 * Load price rules for REST requests.
	 *
	 * REST callbacks can run before `wp_loaded`, so product price endpoints may
	 * otherwise read catalog prices before WholesaleX price filters are registered.
	 *
	 * @return void
	 */
	public function get_valid_dynamic_rules_for_rest() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$this->get_valid_dynamic_rules();
		}
	}

	/**
	 * Resolve the customer selected on the WooCommerce admin order screen.
	 *
	 * WooCommerce's add/recalculate order item requests are authenticated as the
	 * admin user, but the pricing context should be the order customer.
	 *
	 * @param int|string $user_id Current resolved user ID.
	 * @return int|string
	 */
	public function set_admin_order_customer_as_current_user( $user_id ) {
		if ( ! is_admin() || ! wp_doing_ajax() ) {
			return $user_id;
		}

		if ( ! $this->is_verified_admin_order_request() ) {
			return $user_id;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- is_verified_admin_order_request() verified the WooCommerce order screen nonce above.
		if ( '' === $action || 0 !== strpos( $action, 'woocommerce_' ) ) {
			return $user_id;
		}

		$order_customer_id = $this->get_admin_order_request_customer_id();
		return $order_customer_id ? $order_customer_id : $user_id;
	}

	/**
	 * Pass the selected admin order customer into WooCommerce order-item AJAX.
	 *
	 * WooCommerce does not include the unsaved `#customer_user` value when adding
	 * products to a manual order. Without this bridge PHP only sees the admin user.
	 *
	 * @return void
	 */
	public function enqueue_admin_order_pricing_context_script() {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$is_order_screen = in_array( $screen->id, array( 'shop_order', 'woocommerce_page_wc-orders', 'admin_page_wc-orders' ), true ) || 'shop_order' === $screen->post_type;
		if ( ! $is_order_screen || ! wp_script_is( 'wc-admin-order-meta-boxes', 'enqueued' ) ) {
			return;
		}

		wp_add_inline_script(
			'wc-admin-order-meta-boxes',
			"jQuery(function($){\n" .
			"\tvar addCustomerContext=function(event,data){\n" .
			"\t\tvar customerId=$('#customer_user').val();\n" .
			"\t\tif(customerId){data.customer_user=customerId;}\n" .
			"\t\treturn data;\n" .
			"\t};\n" .
			"\t$('#woocommerce-order-items')\n" .
			"\t\t.on('woocommerce_order_meta_box_add_items_ajax_data',addCustomerContext)\n" .
			"\t\t.on('woocommerce_order_meta_box_recalculate_ajax_data',addCustomerContext)\n" .
			"\t\t.on('woocommerce_order_meta_box_save_line_items_ajax_data',addCustomerContext);\n" .
			'});'
		);
	}

	/**
	 * Re-price newly added manual order items for the selected order customer.
	 *
	 * @param array    $added_items Added order items.
	 * @param WC_Order $order       Order object.
	 * @return void
	 */
	public function apply_admin_order_customer_pricing_to_added_items( $added_items, $order ) {
		if ( ! is_admin() || ! wp_doing_ajax() || ! $order instanceof \WC_Order ) {
			return;
		}

		$customer_id = $this->get_admin_order_request_customer_id();
		if ( ! $customer_id ) {
			$customer_id = absint( $order->get_customer_id( 'edit' ) );
		}
		if ( ! $customer_id ) {
			return;
		}

		$force_customer = function () use ( $customer_id ) {
			return $customer_id;
		};

		add_filter( 'wholesalex_set_current_user', $force_customer, 1 );
		add_filter( 'wholesalex_dynamic_rule_user_id', $force_customer, 1 );
		$this->get_valid_dynamic_rules( $customer_id );

		foreach ( $added_items as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			$quantity   = max( 1, (int) $item->get_quantity() );
			$unit_price = $this->get_admin_order_customer_unit_price( $product, $order, $customer_id, $quantity );
			if ( false === $unit_price ) {
				continue;
			}

			$line_total = wc_format_decimal( $unit_price * $quantity );
			$item->set_subtotal( $line_total );
			$item->set_total( $line_total );
			$item->save();
		}

		remove_filter( 'wholesalex_set_current_user', $force_customer, 1 );
		remove_filter( 'wholesalex_dynamic_rule_user_id', $force_customer, 1 );

		$order->calculate_totals( false );
		$order->save();
	}

	/**
	 * Re-price manual order items when the admin clicks Recalculate.
	 *
	 * @param WC_Order_Item $item Order item.
	 * @return void
	 */
	public function apply_admin_order_customer_pricing_before_item_save( $item ) {
		if ( ! is_admin() || ! wp_doing_ajax() || ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}

		if ( ! $this->is_verified_admin_order_request() ) {
			return;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- is_verified_admin_order_request() verified the WooCommerce order screen nonce above.
		if ( 'woocommerce_calc_line_taxes' !== $action ) {
			return;
		}

		$order = wc_get_order( $item->get_order_id() );
		if ( ! $order ) {
			return;
		}

		$customer_id = $this->get_admin_order_request_customer_id();
		if ( ! $customer_id ) {
			$customer_id = absint( $order->get_customer_id( 'edit' ) );
		}
		if ( ! $customer_id ) {
			return;
		}

		$force_customer = function () use ( $customer_id ) {
			return $customer_id;
		};
		add_filter( 'wholesalex_set_current_user', $force_customer, 1 );
		add_filter( 'wholesalex_dynamic_rule_user_id', $force_customer, 1 );
		$this->get_valid_dynamic_rules( $customer_id );

		$product = $item->get_product();
		if ( $product ) {
			$quantity   = max( 1, (int) $item->get_quantity() );
			$unit_price = $this->get_admin_order_customer_unit_price( $product, $order, $customer_id, $quantity );
			if ( false !== $unit_price ) {
				$line_total = wc_format_decimal( $unit_price * $quantity );
				$item->set_subtotal( $line_total );
				$item->set_total( $line_total );
			}
		}

		remove_filter( 'wholesalex_set_current_user', $force_customer, 1 );
		remove_filter( 'wholesalex_dynamic_rule_user_id', $force_customer, 1 );
	}

	/**
	 * Get a tax-exclusive unit price for an admin-created order item.
	 *
	 * @param WC_Product $product     Product object.
	 * @param WC_Order   $order       Order object.
	 * @param int        $customer_id Customer ID.
	 * @param int        $quantity    Line quantity.
	 * @return float|false
	 */
	private function get_admin_order_customer_unit_price( $product, $order, $customer_id, $quantity ) {
		$product_id = $product->get_id();
		$parent_id  = $product->get_parent_id();

		$force_customer = function () use ( $customer_id ) {
			return $customer_id;
		};
		$product_count  = function ( $count, $count_product_id ) use ( $product_id, $parent_id, $quantity ) {
			$count_product_id = absint( $count_product_id );
			if ( in_array( $count_product_id, array_filter( array( $product_id, $parent_id ) ), true ) ) {
				return $quantity;
			}
			return $count;
		};
		$category_count = function ( $count, $cat_id ) use ( $product_id, $parent_id, $quantity ) {
			$term_product_id = $parent_id ? $parent_id : $product_id;
			if ( has_term( absint( $cat_id ), 'product_cat', $term_product_id ) ) {
				return max( (int) $count, $quantity );
			}
			return $count;
		};

		add_filter( 'wholesalex_set_current_user', $force_customer, 1 );
		add_filter( 'wholesalex_dynamic_rule_user_id', $force_customer, 1 );
		add_filter( 'wholesalex_cart_count', $product_count, 20, 2 );
		add_filter( 'wholesalex_category_cart_count', $category_count, 20, 2 );

		$priced_product = wc_get_product( $product_id );
		$price          = false;
		if ( $priced_product ) {
			$price = $priced_product->get_sale_price();
			if ( '' === $price || null === $price || false === $price ) {
				$price = $priced_product->get_regular_price();
			}
		}
		$unit_price = $priced_product ? wc_get_price_excluding_tax(
			$priced_product,
			array(
				'qty'   => 1,
				'price' => $price,
				'order' => $order,
			)
		) : false;

		remove_filter( 'wholesalex_set_current_user', $force_customer, 1 );
		remove_filter( 'wholesalex_dynamic_rule_user_id', $force_customer, 1 );
		remove_filter( 'wholesalex_cart_count', $product_count, 20 );
		remove_filter( 'wholesalex_category_cart_count', $category_count, 20 );

		return is_numeric( $unit_price ) ? (float) $unit_price : false;
	}

	/**
	 * Verify the nonce WooCommerce sends with its admin order screen requests.
	 *
	 * These callbacks read the order customer so pricing can be recalculated,
	 * and run inside WooCommerce's own AJAX actions, each of which sends its
	 * nonce in the "security" field.
	 *
	 * @return bool True when the request carries a valid WooCommerce order nonce.
	 */
	private function is_verified_admin_order_request() {
		$nonce = isset( $_REQUEST['security'] ) && is_string( $_REQUEST['security'] ) ? sanitize_key( wp_unslash( $_REQUEST['security'] ) ) : '';

		if ( '' === $nonce ) {
			return false;
		}

		foreach ( array( 'order-item', 'calc-totals', 'get-customer-details' ) as $action ) {
			if ( wp_verify_nonce( $nonce, $action ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the selected customer from an admin order AJAX request.
	 *
	 * @return int
	 */
	private function get_admin_order_request_customer_id() {
		if ( ! $this->is_verified_admin_order_request() ) {
			return 0;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- is_verified_admin_order_request() verified the WooCommerce order screen nonce above.
		$request_customer_id = $this->get_customer_id_from_request_values( $_REQUEST );
		if ( $request_customer_id ) {
			return $request_customer_id;
		}

		$order_id = 0;
		foreach ( array( 'order_id', 'post_id', 'id' ) as $key ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				$order_id = absint( wp_unslash( $_REQUEST[ $key ] ) );
				break;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $order_id ) {
			return 0;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return 0;
		}

		return absint( $order->get_customer_id( 'edit' ) );
	}

	/**
	 * Extract customer ID from raw request data or serialized order form data.
	 *
	 * @param array $values Request values.
	 * @return int
	 */
	private function get_customer_id_from_request_values( $values ) {
		foreach ( array( 'customer_user', '_customer_user', 'customer_id', 'user_id' ) as $key ) {
			if ( isset( $values[ $key ] ) ) {
				$customer_id = absint( wp_unslash( $values[ $key ] ) );
				if ( $customer_id ) {
					return $customer_id;
				}
			}
		}

		if ( empty( $values['data'] ) || ! is_string( $values['data'] ) ) {
			return 0;
		}

		$parsed_data = array();
		wp_parse_str( wp_unslash( $values['data'] ), $parsed_data );
		return $this->get_customer_id_from_request_values( $parsed_data );
	}

	/**
	 * Get role regular price.
	 *
	 * @param \WC_Product $product Product.
	 * @param int|string  $user_id User or pricing role identifier.
	 */
	public function get_role_regular_price( $product, $user_id = '' ) {
		return get_post_meta( $product->get_id(), $user_id . '_base_price', true );
	}

	/**
	 * Get the schedule status of a native WooCommerce sale.
	 *
	 * Read the unfiltered product values so WholesaleX price filters cannot make
	 * an expired native sale appear active while evaluating its schedule.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string One of active, pending, expired, or an empty string.
	 */
	private function get_native_sale_schedule_status( $product ) {
		$sale_price = $product->get_sale_price( 'edit' );

		if ( '' === $sale_price || null === $sale_price ) {
			return '';
		}

		$now       = time();
		$sale_from = $product->get_date_on_sale_from( 'edit' );
		$sale_to   = $product->get_date_on_sale_to( 'edit' );

		if ( $sale_from && $sale_from->getTimestamp() > $now ) {
			return 'pending';
		}

		if ( $sale_to && $sale_to->getTimestamp() < $now ) {
			return 'expired';
		}

		return 'active';
	}

	/**
	 * Calculate regular price.
	 *
	 * @param float|string $regular_price Regular price.
	 * @param \WC_Product  $product Product.
	 * @param array        $data Data.
	 */
	public function calculate_regular_price( $regular_price, $product, $data ) {
		if ( isset( $data['role_id'] ) && ! empty( $data['role_id'] ) && $data['eligible'] ) {
			$rrp           = get_post_meta( $product->get_id(), $data['role_id'] . '_base_price', true );
			$regular_price = $rrp ? floatval( $rrp ) : $regular_price;
			if ( $rrp ) {
				wholesalex()->set_wholesalex_regular_prices( $product->get_id(), $rrp );
			}
		}
		$is_regular_price = wholesalex()->get_setting( '_is_sale_or_regular_Price', 'is_regular_price' );
		if ( 'is_sale_price' === $is_regular_price ) {
			$role_sale_price    = floatval( $this->get_role_base_sale_price( $product, $data['role_id'] ) );
			$role_regular_price = get_post_meta( $product->get_id(), $data['role_id'] . '_base_price', true );
			if ( 0.0 === $role_sale_price && ! empty( $role_regular_price ) ) {
				return $regular_price;
			}
			$db_sale_price          = floatval( get_post_meta( $product->get_id(), '_sale_price', true ) );
			$get_session_sale_price = 0;
			if ( WC()->session && null !== WC()->session->get( 'wsx_sale_price' ) ) {
				$get_session_sale_price = floatval( WC()->session->get( 'wsx_sale_price' ) );
			}
			if ( $get_session_sale_price ) {
				if ( $role_sale_price === $get_session_sale_price ) {
					return $regular_price;
				} elseif ( $get_session_sale_price === $db_sale_price ) {
					return $regular_price;
				} else {
					return $this->get_actual_discount_price( $product, $regular_price, $role_sale_price );
				}
			} else {
				return $this->get_actual_discount_price( $product, $regular_price, $role_sale_price );
			}
		}
		return $regular_price;
	}

	/**
	 * Calculate sale price.
	 *
	 * @param float|string $sale_price Sale price.
	 * @param \WC_Product  $product Product.
	 * @param array        $data Data.
	 */
	public function calculate_sale_price( $sale_price, $product, $data ) {
		$parent_id     = $product->get_parent_id();
		$product_id    = $product->get_id();
		$regular_price = $product->get_regular_price();

		$current_role       = wholesalex()->get_current_user_role();
		$role_sale_price    = floatval( $this->get_role_base_sale_price( $product, $current_role ) );
		$role_regular_price = floatval( $this->get_role_regular_price( $product, $current_role ) );
		$has_rolewise_price = $role_sale_price || $role_regular_price;
		$sale_schedule      = $this->get_native_sale_schedule_status( $product );

		if ( 'pending' === $sale_schedule && ! $has_rolewise_price ) {
			return $regular_price;
		}

		if ( 'expired' === $sale_schedule && ! $has_rolewise_price ) {
			$sale_price = false;
		}

		if ( ! $role_sale_price && $role_regular_price ) {
			$sale_price = false;
		}
		$base_price = $product->get_regular_price();
		if ( $has_rolewise_price ) {
			$base_price = $this->get_actual_discount_price( $product, $role_regular_price, $role_sale_price ? $role_sale_price : $role_regular_price );
		}

		$is_regular_price = wholesalex()->get_setting( '_is_sale_or_regular_Price', 'is_regular_price' );
		$base_price       = $this->price_after_currency_changed( $base_price );
		$rrs              = false;
		$previous_sp      = $sale_price;

		if ( $has_rolewise_price || 'is_regular_price' === $is_regular_price ) {
			$sale_price = false;
		}

		if ( $data['eligible'] ) {
			$priority         = wholesalex()->get_quantity_based_discount_priorities();
			$flipped_priority = array_flip( $priority );
			if ( isset( $data['role_id'] ) && ! empty( $data['role_id'] ) ) {
				if ( 'is_regular_price' === $is_regular_price ) {
					if ( $role_sale_price ) {
						$rrs = $role_sale_price;
					} else {
						$rrs = get_post_meta( $product_id, $data['role_id'] . '_base_price', true );
					}
				} else {
					$rrs = get_post_meta( $product_id, $data['role_id'] . '_sale_price', true );
				}
			} else {
				$rrs = false;
			}

			$applied_discount_src = '';

			if ( $this->has_higher_pricing_priority( $flipped_priority, 'dynamic_rule', 'single_product' ) ) {
				if ( ! empty( $data['product_discount'] ) ) {
					foreach ( $data['product_discount'] as $pd ) {
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $product_id, $product_id, $pd['filter'] ) ) {
							if ( ! empty( $pd['conditions']['tiers'] ) ) {
								wholesalex()->set_rule_data(
									$pd['id'],
									$product_id,
									'product_discount',
									array(
										'value'        => $pd['rule']['_discount_amount'],
										'type'         => $pd['rule']['_discount_type'],
										'conditions'   => $pd['conditions'],
										'who_priority' => $pd['who_priority'],
										'applied_on_priority' => $pd['applied_on_priority'],
										'end_date'     => $pd['end_date'],
									)
								);
							}
							if ( isset( $pd['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $pd['conditions'], $pd['filter'] ) ) {
								continue;
							}
							$sale_price           = wholesalex()->calculate_sale_price( $pd['rule'], $base_price );
							$applied_discount_src = 'product_discount';
						}
					}
				}
				if ( '' === $applied_discount_src && $rrs ) {
					$sale_price = floatval( $rrs );
				}
			} elseif ( isset( $flipped_priority['dynamic_rule'] ) && ! $rrs && ! empty( $data['product_discount'] ) ) {
				foreach ( $data['product_discount'] as $pd ) {
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $parent_id ? $parent_id : $product_id, $product_id, $pd['filter'] ) ) {
						if ( ! empty( $pd['conditions']['tiers'] ) ) {
							wholesalex()->set_rule_data(
								$pd['id'],
								$product_id,
								'product_discount',
								array(
									'value'               => $pd['rule']['_discount_amount'],
									'type'                => $pd['rule']['_discount_type'],
									'conditions'          => $pd['conditions'],
									'who_priority'        => $pd['who_priority'],
									'applied_on_priority' => $pd['applied_on_priority'],
									'end_date'            => $pd['end_date'],
								)
							);
						}
						if ( isset( $pd['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $pd['conditions'], $pd['filter'] ) ) {
							continue;
						}
						$sale_price           = wholesalex()->calculate_sale_price( $pd['rule'], $base_price );
						$applied_discount_src = 'product_discount';
					}
				}
			} elseif ( $rrs ) {
				$sale_price = floatval( $rrs );
			}

			$computed = apply_filters(
				'wholesalex_dynamic_rule_price_result',
				null,
				$product,
				$data,
				array(
					'base_price'           => $base_price,
					'sale_price'           => $sale_price,
					'applied_discount_src' => $applied_discount_src,
					'priority'             => $priority,
				)
			);
			if ( is_array( $computed ) && isset( $computed['price'] ) && is_numeric( $computed['price'] ) && is_finite( (float) $computed['price'] ) && (float) $computed['price'] >= 0 ) {
				$sale_price = (float) $computed['price'];
			}
		}

		if ( $previous_sp !== $sale_price && is_numeric( $sale_price ) && (float) $sale_price >= 0 ) {
			wholesalex()->set_wholesalex_wholesale_prices( $product_id, $sale_price );
		}
		if ( wholesalex()->get_wholesalex_regular_prices( $product_id ) && ! $this->is_available_price( wholesalex()->get_wholesalex_wholesale_prices( $product_id ) ) ) {
			$previous_sp = '';
		}
		if ( $this->is_available_price( wholesalex()->get_wholesalex_wholesale_prices( $product_id ) ) ) {
			$this->set_discounted_product( $product_id );
		}
		if ( is_numeric( $sale_price ) && (float) $sale_price >= 0 ) {
			if ( WC()->session ) {
				WC()->session->set( 'wsx_sale_price', $sale_price );
			}
		}
		return is_numeric( $sale_price ) && (float) $sale_price >= 0 ? $sale_price : $previous_sp;
	}

	/**
	 * Forward a computed source result supplied by an external pricing provider.
	 *
	 * @param string $priority Pricing source.
	 * @param array  $data Pricing context.
	 * @param int    $product_id Product ID.
	 * @param int    $parent_id Parent ID.
	 * @param float  $base_price Original price.
	 * @param float  $cart_qty Cart quantity.
	 * @param bool   $first_tier Include preview records.
	 * @return array
	 */
	public function get_priority_wise_tier_price( $priority, $data, $product_id, $parent_id, $base_price, $cart_qty, $first_tier = false ) {
		return apply_filters(
			'wholesalex_priority_tier_result',
			array(
				'src'   => false,
				'price' => false,
				'tiers' => array(),
			),
			$priority,
			$data,
			$product_id,
			$parent_id,
			$base_price,
			$cart_qty,
			$first_tier
		);
	}

	// ─── Price Display ───────────────────────────────────────────

	/**
	 * Variation price hash.
	 *
	 * @param array       $hash Hash.
	 * @param \WC_Product $product Product.
	 * @param bool        $for_display For display.
	 */
	public function variation_price_hash( $hash, $product, $for_display = false ) {
		$user_id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		$context = array(
			'user_id'     => (string) $user_id,
			'role_id'     => (string) wholesalex()->get_user_role( $user_id ),
			'rules'       => md5( wp_json_encode( $this->valid_dynamic_rules ) ),
			'cart'        => $this->get_variation_price_cart_hash(),
			'currency'    => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'woocommerce_currency' ),
			'tax_display' => function_exists( 'get_option' ) ? get_option( 'woocommerce_tax_display_shop' ) : '',
			'for_display' => (bool) $for_display,
		);
		$hash[]  = apply_filters( 'wholesalex_variation_prices_hash', md5( wp_json_encode( $context ) ), $product, $context );
		return $hash;
	}

	/**
	 * Return a stable cart fingerprint for cart-sensitive variation pricing.
	 *
	 * WooCommerce stores all variation price hash variants inside one product
	 * transient, so this must change only when cart values that can affect
	 * Dynamic Rules pricing change.
	 *
	 * @return string
	 */
	private function get_variation_price_cart_hash() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return '';
		}

		$cart_items = array();
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id   = isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;
			$variation_id = isset( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0;
			$key          = $product_id . ':' . $variation_id;

			if ( ! isset( $cart_items[ $key ] ) ) {
				$cart_items[ $key ] = array(
					'product_id'    => $product_id,
					'variation_id'  => $variation_id,
					'quantity'      => 0,
					'line_subtotal' => 0.0,
				);
			}

			$cart_items[ $key ]['quantity']      += isset( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 0;
			$cart_items[ $key ]['line_subtotal'] += isset( $cart_item['line_subtotal'] ) ? (float) $cart_item['line_subtotal'] : 0.0;
		}

		ksort( $cart_items, SORT_STRING );

		foreach ( $cart_items as $key => $cart_item ) {
			$cart_items[ $key ]['line_subtotal'] = wc_format_decimal(
				$cart_item['line_subtotal'],
				function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2
			);
		}

		return md5( wp_json_encode( $cart_items ) );
	}

	/**
	 * Format sale price.
	 *
	 * @param float|string $regular_price Regular price.
	 * @param float|string $sale_price Sale price.
	 * @param bool         $is_wholesalex_sale_price_applied Whether WholesaleX supplied the sale price.
	 */
	public function format_sale_price( $regular_price, $sale_price, $is_wholesalex_sale_price_applied ) {
		global $product;
		$has_regular_price = is_numeric( $regular_price ) ? $this->is_available_price( $regular_price ) : ! empty( $regular_price );
		$has_sale_price    = is_numeric( $sale_price ) ? $this->is_available_price( $sale_price ) : ! empty( $sale_price );
		$sale_text         = '';
		if ( class_exists( 'Aelia_Integration_Helper' ) && \Aelia_Integration_Helper::aelia_currency_switcher_active() ) {
			$active_currency = get_woocommerce_currency();
			$product_id      = $product->get_id();
			$base_currency   = \Aelia_Integration_Helper::get_product_base_currency( $product_id );
			$wholesale_price = \Aelia_Integration_Helper::convert( $sale_price, $active_currency, $base_currency );
			return wc_price( floatval( $wholesale_price ) );
		}
		if ( is_product() ) {
			$sale_text = wholesalex()->get_setting( '_settings_price_text', __( 'Wholesale Price:', 'wholesalex' ) );
		} else {
			$sale_text = wholesalex()->get_setting( '_settings_price_text_product_list_page', __( 'Wholesale Price:', 'wholesalex' ) );
		}
		$__hide_regular_price   = wholesalex()->get_setting( '_settings_hide_retail_price' ) ?? '';
		$__hide_wholesale_price = wholesalex()->get_setting( '_settings_hide_wholesalex_price' ) ?? '';
		if ( $this->is_enable_subscriptions_product_woo( $product ) ) {
			$sale_text = '';
		}
		if ( ! $is_wholesalex_sale_price_applied ) {
			$sale_text = '';
		}
		if ( '' !== trim( (string) $sale_text ) ) {
			$sale_text = rtrim( (string) $sale_text ) . ' ';
		}
		// Variable products can supply already formatted price ranges. Casting
		// their HTML to a float turns the entire range into a zero price.
		$regular_price_html = is_numeric( $regular_price ) ? wc_price( $regular_price ) : $regular_price;
		$sale_price_html    = is_numeric( $sale_price ) ? wc_price( $sale_price ) : $sale_price;
		if ( ! is_admin() ) {
			if ( 'yes' === (string) $__hide_wholesale_price && 'yes' === (string) $__hide_regular_price ) {
				return apply_filters( 'wholesalex_regular_sale_price_hidden_text', wholesalex()->get_language_n_text( '_language_price_is_hidden', 'Price is hidden!' ) );
			}
			if ( 'yes' === (string) $__hide_regular_price && $has_sale_price ) {
				return $sale_text . $sale_price_html;
			}
			if ( 'yes' === (string) $__hide_wholesale_price && $has_regular_price && $is_wholesalex_sale_price_applied ) {
				return $regular_price_html;
			}
		}
		if ( $sale_price === $regular_price ) {
			return '<ins>' . $sale_text . $sale_price_html . '</ins>';
		}
		if ( $has_sale_price && $has_regular_price ) {
			return '<del aria-hidden="true">' . $regular_price_html . '</del> <ins>' . $sale_text . $sale_price_html . '</ins>';
		}
		if ( $has_sale_price ) {
			return '<ins>' . $sale_text . $sale_price_html . '</ins>';
		}
		if ( $has_regular_price ) {
			return $regular_price_html;
		}
	}

	/**
	 * Get product quantity in cart.
	 *
	 * @param int $product_id Product ID.
	 */
	public function get_product_quantity_in_cart( $product_id ) {
		$quantity_in_cart = 0;
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$cart_product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
				if ( (string) $cart_product_id === (string) $product_id ) {
					$quantity_in_cart += $cart_item['quantity'];
				}
			}
		}
		return $quantity_in_cart;
	}

	// ─── Main Dispatch: get_valid_dynamic_rules ──────────────────

	/**
	 * Get valid dynamic rules.
	 *
	 * @param int|string $user_id User or pricing role identifier.
	 */
	public function get_valid_dynamic_rules( $user_id = '' ) {
		if ( is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// REST initialization can reload rules after wp_loaded in the same request.
		$this->valid_dynamic_rules = array();

		$user_id = ( isset( $user_id ) && ! empty( $user_id ) ) ? $user_id : get_current_user_id();
		$user_id = apply_filters( 'wholesalex_set_current_user', $user_id );

		if ( ! isset( $GLOBALS['wholesalex_rule_data'] ) ) {
			$GLOBALS['wholesalex_rule_data'] = array();
		}

		if ( is_user_logged_in() ) {
			// Used by automatic role migration.
			self::$cu_total_spent = wc_get_customer_total_spent( $user_id );
			// Sync to facade for backward compatibility.
			WHOLESALEX_Dynamic_Rules::$cu_total_spent = self::$cu_total_spent;
		}

		self::$total_cart_counts                     = false;
		WHOLESALEX_Dynamic_Rules::$total_cart_counts = false;

		do_action( 'wholesalex_before_dynamic_rules_loaded', $user_id );
		$plugins_status = wholesalex()->get_setting( '_settings_status', 'b2b' );
		$is_eligible    = true;

		if ( 'b2b' === $plugins_status && 'active' !== wholesalex()->get_user_status( $user_id ) ) {
			$is_eligible = false;
		}

		$is_eligible = apply_filters( 'wholesalex_pricing_user_eligible', $is_eligible, $user_id );

		$__discounts = wholesalex()->get_dynamic_rules();
		$__role      = wholesalex()->get_user_role( $user_id );

		foreach ( $__discounts as $discount ) {
			$discount = is_array( $discount ) ? $this->scope_rule_to_owner( $discount ) : array();
			if ( ! $discount ) {
				continue; }
			if ( isset( $discount['conditions'] ) && ( ! is_array( $discount['conditions'] ) || ( isset( $discount['conditions']['tiers'] ) && ! is_array( $discount['conditions']['tiers'] ) ) ) ) {
				continue; }
			if ( in_array( $discount['_rule_status'] ?? '', array( true, 1, '1', 'yes' ), true ) && isset( $discount['_product_filter'] ) ) {
				if ( isset( $discount['conditions']['tiers'] ) && ! empty( $discount['conditions']['tiers'] ) && ! Dynamic_Rules_Condition_Engine::is_user_order_count_purchase_amount_condition_passed( $discount['conditions']['tiers'] ) ) {
					continue;
				}

				$__role_for       = $discount['_rule_for'];
				$__for_me         = false;
				$who_priority     = 10;
				$product_priority = 10;
				switch ( $__role_for ) {
					case 'specific_roles':
						foreach ( $discount['specific_roles'] as $role ) {
							if ( (string) $role['value'] === (string) $__role || 'role_' . $__role === $role['value'] ) {
								$__for_me     = true;
								$who_priority = 20;
								break;
							}
						}
						break;
					case 'specific_users':
						foreach ( $discount['specific_users'] as $user ) {
							if ( ( is_numeric( $user['value'] ) && (int) $user['value'] === (int) $user_id ) || ( 'user_' . $user_id === $user['value'] ) ) {
								$__for_me     = true;
								$who_priority = 10;
								break;
							}
						}
						break;
					case 'all_roles':
						if ( empty( $__role ) ) {
							break;
						}
						$__exclude_roles = apply_filters( 'wholesalex_dynamic_rules_exclude_roles', array( 'wholesalex_guest', 'wholesalex_b2c_users' ) );
						if ( is_array( $__exclude_roles ) && ! empty( $__exclude_roles ) ) {
							if ( ! in_array( $__role, $__exclude_roles, true ) ) {
								$__for_me     = true;
								$who_priority = 30;
								break;
							}
						} else {
							$__for_me     = true;
							$who_priority = 30;
						}
						break;
					case 'all_users':
						if ( ! $user_id ) {
							break; }
						$__exclude_users = apply_filters( 'wholesalex_dynamic_rules_exclude_users', array() );
						if ( is_array( $__exclude_users ) && ! empty( $__exclude_users ) ) {
							if ( ! in_array( (string) $user_id, array_map( 'strval', $__exclude_users ), true ) ) {
								$__for_me     = true;
								$who_priority = 40;
								break;
							}
						} elseif ( 0 !== $user_id ) {
							$__for_me     = true;
							$who_priority = 40;
						}
						break;
					case 'all':
						$__for_me     = true;
						$who_priority = 50;
						break;
				}
				if ( ! $__for_me ) {
					continue;
				}

				$include_products   = array();
				$include_cats       = array();
				$include_brands     = array();
				$include_variations = array();
				$include_attributes = array();
				$exclude_products   = array();
				$exclude_cats       = array();
				$exclude_brands     = array();
				$exclude_variations = array();
				$exclude_attributes = array();
				$include_skus       = array();
				$exclude_skus       = array();
				$is_all_products    = false;

					$is_dynamic_rules_apply_in_backend = apply_filters( 'is_dynamic_rules_work_in_backend', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the established public filter for backward compatibility.
				if ( ! is_admin() && '' !== $is_dynamic_rules_apply_in_backend ) {
					$rule_filter_data   = Dynamic_Rules_Condition_Engine::get_filtered_rules( $discount );
					$include_products   = $rule_filter_data['include_products'];
					$include_cats       = $rule_filter_data['include_cats'];
					$include_brands     = $rule_filter_data['include_brands'];
					$include_variations = $rule_filter_data['include_variations'];
					$include_attributes = $rule_filter_data['include_attributes'];
					$exclude_products   = $rule_filter_data['exclude_products'];
					$exclude_cats       = $rule_filter_data['exclude_cats'];
					$exclude_brands     = $rule_filter_data['exclude_brands'];
					$exclude_variations = $rule_filter_data['exclude_variations'];
					$exclude_attributes = $rule_filter_data['exclude_attributes'];
					$include_skus       = $rule_filter_data['include_skus'];
					$exclude_skus       = $rule_filter_data['exclude_skus'];
					$is_all_products    = $rule_filter_data['is_all_products'];
					$product_priority   = $rule_filter_data['product_priority'];
				} elseif ( is_admin() && $is_dynamic_rules_apply_in_backend ) {
					$rule_filter_data   = Dynamic_Rules_Condition_Engine::get_filtered_rules( $discount );
					$include_products   = $rule_filter_data['include_products'];
					$include_cats       = $rule_filter_data['include_cats'];
					$include_brands     = $rule_filter_data['include_brands'];
					$include_variations = $rule_filter_data['include_variations'];
					$include_attributes = $rule_filter_data['include_attributes'];
					$exclude_products   = $rule_filter_data['exclude_products'];
					$exclude_cats       = $rule_filter_data['exclude_cats'];
					$exclude_brands     = $rule_filter_data['exclude_brands'];
					$exclude_variations = $rule_filter_data['exclude_variations'];
					$exclude_attributes = $rule_filter_data['exclude_attributes'];
					$include_skus       = $rule_filter_data['include_skus'];
					$exclude_skus       = $rule_filter_data['exclude_skus'];
					$is_all_products    = $rule_filter_data['is_all_products'];
					$product_priority   = $rule_filter_data['product_priority'];
				}
			} else {
				continue;
			}

			if ( isset( $discount['limit'] ) && ! empty( $discount['limit'] ) ) {
				if ( ! Dynamic_Rules_Condition_Engine::has_limit( $discount['limit'], $discount['id'] ) ) {
					continue;
				}
			}

			if ( ! isset( $discount['_rule_for'] ) ) {
				continue;
			}
			if ( empty( $discount['_rule_type'] ) ) {
				continue;
			}

			if ( ! ( isset( $this->valid_dynamic_rules[ $discount['_rule_type'] ] ) && is_array( $this->valid_dynamic_rules[ $discount['_rule_type'] ] ) ) ) {
				$this->valid_dynamic_rules[ $discount['_rule_type'] ] = array();
			}

			$rule_type = $discount['_rule_type'];
			$frule     = ! empty( $discount[ $rule_type ] ) ? $discount[ $rule_type ] : null;
			// Older first saves omitted the untouched Percentage selector.
			if ( 'product_discount' === $rule_type && is_array( $frule ) && empty( $frule['_discount_type'] ) ) {
				$frule['_discount_type'] = 'percentage';
			}
			if ( 'min_order_qty' === $rule_type && empty( $discount['min_order_qty']['_min_order_qty'] ) ) {
				$frule = null;
			}

			/**
			 * Filter the runtime payload of a dynamic rule.
			 *
			 * Return null to skip the rule. Rule types this plugin does not
			 * handle are skipped unless an extension supplies their payload.
			 *
			 * @param mixed  $frule     Rule payload, or null.
			 * @param string $rule_type Rule type.
			 * @param array  $discount  Saved rule.
			 * @param self   $handler   Dynamic rules handler.
			 */
			$frule = apply_filters( 'wholesalex_dr_rule_payload', $frule, $rule_type, $discount, $this );

			if ( null !== $frule ) {
				$this->valid_dynamic_rules[ $discount['_rule_type'] ][] = array(
					'id'                  => $discount['id'],
					'filter'              => array(
						'include_products'   => $include_products,
						'include_attributes' => $include_attributes,
						'include_brands'     => $include_brands,
						'include_cats'       => $include_cats,
						'include_variations' => $include_variations,
						'include_skus'       => $include_skus,
						'exclude_products'   => $exclude_products,
						'exclude_attributes' => $exclude_attributes,
						'exclude_brands'     => $exclude_brands,
						'exclude_cats'       => $exclude_cats,
						'exclude_variations' => $exclude_variations,
						'exclude_skus'       => $exclude_skus,
						'is_all_products'    => $is_all_products,
					),
					'rule'                => $frule,
					'conditions'          => array( 'tiers' => isset( $discount['conditions']['tiers'] ) ? wholesalex()->filter_empty_conditions( $discount['conditions']['tiers'] ) : array() ),
					'who_priority'        => $who_priority,
					'applied_on_priority' => $product_priority,
					'end_date'            => isset( $discount['limit']['_end_date'] ) ? $discount['limit']['_end_date'] : false,
				);
			}
		}

		foreach ( $this->valid_dynamic_rules as $key => $value ) {
			usort( $this->valid_dynamic_rules[ $key ], array( $this, 'compare_by_priority' ) );
		}

		do_action( 'wholesalex_valid_dynamic_rules', $this->valid_dynamic_rules );

		$profile_settings = get_user_meta( $user_id, '__wholesalex_profile_settings', true );

		// ── Tax Rules → delegate to Rule_Tax ──
		$is_tax_exempt = '';
		if ( isset( $profile_settings['_wholesalex_profile_override_tax_exemption'] ) ) {
			$is_tax_exempt = $profile_settings['_wholesalex_profile_override_tax_exemption'];
		}
		if ( isset( $this->valid_dynamic_rules['tax_rule'] ) && ! empty( $this->valid_dynamic_rules['tax_rule'] ) ) {
			usort( $this->valid_dynamic_rules['tax_rule'], array( $this, 'compare_by_priority' ) );
		}
		$this->valid_dynamic_rules['tax_rule'] = isset( $this->valid_dynamic_rules['tax_rule'] ) ? $this->valid_dynamic_rules['tax_rule'] : array();
		if ( ! empty( $this->valid_dynamic_rules['tax_rule'] ) || $is_tax_exempt ) {
			$this->rule_tax->handle(
				array(
					'profile_exemption' => $is_tax_exempt,
					'rules'             => $this->valid_dynamic_rules['tax_rule'],
				)
			);
		}

		// ── Shipping Rules → delegate to Rule_Shipping ──
		if ( isset( $this->valid_dynamic_rules['shipping_rule'] ) && ! empty( $this->valid_dynamic_rules['shipping_rule'] ) ) {
			usort( $this->valid_dynamic_rules['shipping_rule'], array( $this, 'compare_by_priority' ) );
		}
		$profile_shipping_data = array();
		if ( isset( $profile_settings['_wholesalex_profile_override_shipping_method'] ) && 'yes' === $profile_settings['_wholesalex_profile_override_shipping_method'] ) {
			$profile_shipping_data['method_type'] = isset( $profile_settings['_wholesalex_profile_shipping_method_type'] ) ? $profile_settings['_wholesalex_profile_shipping_method_type'] : '';
			$profile_shipping_data['zone']        = isset( $profile_settings['_wholesalex_profile_shipping_zone'] ) ? $profile_settings['_wholesalex_profile_shipping_zone'] : '';
			$profile_shipping_data['methods']     = isset( $profile_settings['_wholesalex_profile_shipping_zone_methods'] ) ? $profile_settings['_wholesalex_profile_shipping_zone_methods'] : array();
		}
		$__role_content = wholesalex()->get_roles( 'by_id', $__role );
		if ( is_array( $__role_content ) ) {
			$__role_content = WHOLESALEX_Role::get_role_with_wtrs_shipping_methods( $__role_content );
		}
		$__shipping_methods = array();
		if ( isset( $__role_content['_shipping_methods'] ) && ! empty( $__role_content['_shipping_methods'] ) ) {
			$__shipping_methods = $__role_content['_shipping_methods'];
		}
		$__shipping_methods                         = array_filter( $__shipping_methods );
		$this->valid_dynamic_rules['shipping_rule'] = isset( $this->valid_dynamic_rules['shipping_rule'] ) ? $this->valid_dynamic_rules['shipping_rule'] : array();
		if ( ! empty( $profile_shipping_data ) || ! empty( $__shipping_methods ) || ! empty( $this->valid_dynamic_rules['shipping_rule'] ) ) {
			$this->rule_shipping->handle(
				array(
					'profile' => $profile_shipping_data,
					'roles'   => $__shipping_methods,
					'rules'   => $this->valid_dynamic_rules['shipping_rule'],
				)
			);
		}

		// ── Payment Gateway Rules → delegate to Rule_Payment_Gateway ──
		$profile_gateway_data = array();
		if ( isset( $profile_settings['_wholesalex_profile_override_payment_gateway'] ) && 'yes' === $profile_settings['_wholesalex_profile_override_payment_gateway'] ) {
			if ( isset( $profile_settings['_wholesalex_profile_payment_gateways'] ) && ! empty( $profile_settings['_wholesalex_profile_payment_gateways'] ) ) {
				$profile_gateway_data = $profile_settings['_wholesalex_profile_payment_gateways'];
			}
		}
		$payment_related_rules = array();
		if ( isset( $this->valid_dynamic_rules['payment_order_qty'] ) && ! empty( $this->valid_dynamic_rules['payment_order_qty'] ) ) {
			usort( $this->valid_dynamic_rules['payment_order_qty'], array( $this, 'compare_by_priority' ) );
			$payment_related_rules = $this->valid_dynamic_rules['payment_order_qty'];
		}
		$role_payment_methods            = array();
		$has_role_payment_method_setting = is_array( $__role_content ) && array_key_exists( '_payment_methods', $__role_content );
		if ( isset( $__role_content['_payment_methods'] ) && ! empty( $__role_content['_payment_methods'] ) ) {
			$role_payment_methods = $__role_content['_payment_methods'];
			$role_payment_methods = array_filter( $role_payment_methods );
		}
		$this->rule_payment_gateway->handle(
			array(
				'profile'                         => $profile_gateway_data,
				'rules'                           => $payment_related_rules,
				'roles'                           => $role_payment_methods,
				'has_role_payment_method_setting' => $has_role_payment_method_setting,
			)
		);

		// ── Cart Fees → aggregate from rule handlers ──
		$cart_related_data = array();
		if ( isset( $this->valid_dynamic_rules['buy_x_get_one'] ) && ! empty( $this->valid_dynamic_rules['buy_x_get_one'] ) ) {
			usort( $this->valid_dynamic_rules['buy_x_get_one'], array( $this, 'compare_by_priority' ) );
			$cart_related_data['buy_x_get_one'] = $this->valid_dynamic_rules['buy_x_get_one'];
		}
		if ( isset( $this->valid_dynamic_rules['cart_discount'] ) && ! empty( $this->valid_dynamic_rules['cart_discount'] ) ) {
			usort( $this->valid_dynamic_rules['cart_discount'], array( $this, 'compare_by_priority' ) );
			$cart_related_data['cart_discount'] = $this->valid_dynamic_rules['cart_discount'];
		}
		if ( isset( $this->valid_dynamic_rules['payment_discount'] ) && ! empty( $this->valid_dynamic_rules['payment_discount'] ) ) {
			usort( $this->valid_dynamic_rules['payment_discount'], array( $this, 'compare_by_priority' ) );
			$cart_related_data['payment_discount'] = $this->valid_dynamic_rules['payment_discount'];
		}
		$cart_related_data = apply_filters( 'wholesalex_dr_cart_related_data', $cart_related_data );
		$this->handle_cart( $cart_related_data );

		// Pass all valid dynamic rules to BOGO badge handler (needs buy_x_get_one and buy_x_get_y keys).
		$this->rule_buy_x_get_one->set_valid_rules( $this->valid_dynamic_rules );

		// ── Min/Max Order Quantity → delegate to Rule_Min_Order_Qty ──
		$min_max_data = array(
			'min_order_qty' => array(),
			'max_order_qty' => array(),
		);
		if ( isset( $this->valid_dynamic_rules['min_order_qty'] ) && ! empty( $this->valid_dynamic_rules['min_order_qty'] ) ) {
			usort( $this->valid_dynamic_rules['min_order_qty'], array( $this, 'compare_by_priority' ) );
			$min_max_data['min_order_qty'] = $this->valid_dynamic_rules['min_order_qty'];
		}
		$min_max_data = apply_filters( 'wholesalex_dr_min_max_rules', $min_max_data, $this->valid_dynamic_rules );
		if ( ! is_plugin_active( 'woocommerce-min-max-quantities/woocommerce-min-max-quantities.php' ) ) {
			$this->rule_min_order_qty->handle( $min_max_data );
		}

		// ── Discounts (price filters) ──
		$discounts_releated_data = array(
			'user_id'          => $user_id,
			'role_id'          => $__role,
			'plugin_status'    => $plugins_status,
			'eligible'         => $is_eligible,
			'product_discount' => array(),
		);
		if ( isset( $this->valid_dynamic_rules['product_discount'] ) && ! empty( $this->valid_dynamic_rules['product_discount'] ) ) {
			usort( $this->valid_dynamic_rules['product_discount'], array( $this, 'compare_by_priority' ) );
			$discounts_releated_data['product_discount'] = $this->valid_dynamic_rules['product_discount'];
		}
		$discounts_releated_data = apply_filters( 'wholesalex_dr_discounts', $discounts_releated_data );
		$this->handle_discounts( $discounts_releated_data );

		// ── Shipping zone detection ──
		if ( ! empty( $this->valid_dynamic_rules['shipping_rule'] ) && ! is_null( WC()->cart ) ) {
			$shipping_packages = WC()->cart->get_shipping_packages();
			if ( ! empty( $shipping_packages ) && is_array( $shipping_packages ) ) {
				$first_package = reset( $shipping_packages );
				if ( is_array( $first_package ) && ! empty( $first_package ) ) {
					$shipping_zone = wc_get_shipping_zone( $first_package );
					if ( is_object( $shipping_zone ) ) {
						$this->current_shipping_zone = $shipping_zone->get_id();
					}
				}
			}
		}

		// ── Single product page promo hooks ──
		add_action(
			'woocommerce_before_add_to_cart_form',
			function () use ( $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt ) {
				global $product;
				if ( $product->is_type( 'simple' ) ) {
					$this->handle_single_product_page_promo( $product, $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt, true );
				}
			}
		);

		add_action(
			'woocommerce_available_variation',
			function ( $variation_array, $product, $variation ) use ( $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt ) {
				$variation_array['availability_html'] .= $this->handle_single_product_page_promo( $variation, $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt, false );
				return $variation_array;
			},
			10,
			3
		);

		if ( 'yes' === wholesalex()->get_setting( '_settings_hide_retail_price' ) && 'yes' === wholesalex()->get_setting( '_settings_hide_wholesalex_price' ) ) {
			$this->make_product_non_purchasable_and_remove_add_to_cart();
		}

		// ── Pro rule dispatching ──
		do_action( 'wholesalex_dr_after_valid_rules_dispatch', $this->valid_dynamic_rules, $discounts_releated_data, $min_max_data, $cart_related_data );
	}

	// ─── Handle Cart Discount and charges calculation for Buy X Get Y, Cart Discount and Payment Discount rules ────────────

	/**
	 * Handle cart.
	 *
	 * @param array $rules Rules.
	 */
	public function handle_cart( $rules ) {
		// Reloads replace the active rules, including when none remain eligible.
		$this->cart_rules = $rules;
		if ( $this->cart_fee_callback_registered || empty( $rules ) ) {
			return;
		}
		$this->cart_fee_callback_registered = true;

		$rule_buy_x     = $this->rule_buy_x_get_one;
		$rule_cart_disc = $this->rule_cart_discount;
		$rule_pay_disc  = $this->rule_payment_discount;

		add_action(
			'woocommerce_cart_calculate_fees',
			function ( $cart ) use ( $rule_buy_x, $rule_cart_disc, $rule_pay_disc ) {
				if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
					return;
				}

				$rules = $this->cart_rules;
				if ( empty( $rules ) ) {
					return;
				}
				$cart_fees = array();

				if ( isset( $rules['buy_x_get_one'] ) && ! empty( $rules['buy_x_get_one'] ) ) {
					$cart_fees['buy_x_get_one'] = $rule_buy_x->calculate( $rules['buy_x_get_one'] );
				}
				if ( isset( $rules['cart_discount'] ) && ! empty( $rules['cart_discount'] ) ) {
					$cart_fees['cart_discount'] = $rule_cart_disc->calculate( $rules['cart_discount'] );
				}
				if ( isset( $rules['payment_discount'] ) && ! empty( $rules['payment_discount'] ) ) {
					$cart_fees['payment_discount'] = $rule_pay_disc->calculate( $rules['payment_discount'] );
				}

				// Pro extra_charge and other fees via filter.
				$cart_fees = apply_filters( 'wholesalex_dr_cart_fees', $cart_fees, $rules, WC()->session->get( 'chosen_payment_method' ) );

				if ( ! empty( $cart_fees ) ) {
					$coupon_names = array();
					foreach ( $cart_fees as $fee_type => $fees ) {
						foreach ( $fees as $fee_key => $discount ) {
							if ( isset( $discount['discount'] ) && 0 !== $discount['discount'] ) {
								$__is_taxable = apply_filters( 'wholesalex_payment_gateway_discount_is_taxable', false );
								if ( 'cart_discount' === $fee_type ) {
									// Separate fee identity from the customer-facing discount label.
									$cart->fees_api()->add_fee(
										array(
											'id'      => 'wholesalex_dynamic_cart_discount_' . $fee_key,
											'name'    => $discount['name'],
											'amount'  => -1 * floatval( $discount['discount'] ),
											'taxable' => $__is_taxable,
										)
									);
									continue;
								}

								if ( ! isset( $coupon_names[ $discount['name'] ] ) ) {
									$coupon_names[ $discount['name'] ] = true;
								} else {
									$discount['name']                  = wp_unique_id( $discount['name'] );
									$coupon_names[ $discount['name'] ] = true;
								}
								$cart->add_fee( $discount['name'], -1 * floatval( $discount['discount'] ), $__is_taxable );
							} elseif ( isset( $discount['charge'] ) && 0 !== $discount['charge'] ) {
								$__is_taxable = apply_filters( 'wholesalex_extra_charge_is_taxable', true );
								if ( ! isset( $coupon_names[ $discount['name'] ] ) ) {
									$coupon_names[ $discount['name'] ] = true;
								} else {
									$discount['name']                  = wp_unique_id( $discount['name'] );
									$coupon_names[ $discount['name'] ] = true;
								}
								$cart->add_fee( $discount['name'], floatval( $discount['charge'] ), $__is_taxable );
							}
						}
					}
					if ( isset( $rules['payment_discount'] ) && ( ! empty( $rules['payment_discount'] ) || isset( $rules['extra_charge'] ) ) && ! empty( $rules['extra_charge'] ) ) {
						add_action(
							'woocommerce_review_order_before_payment',
							function () {
								if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) :
									?>
							<script type="text/javascript">
								jQuery(function($) {
									$('form.woocommerce-checkout').on('change', 'input[name="payment_method"]', function() {
										$('body').trigger('update_checkout');
									});
								})
							</script>
									<?php
								endif;
							}
						);
					}
				}
			}
		);
	}

	// ─── Handle Discounts (Price Filter Registration) ────────────

	/**
	 * Handle discounts.
	 *
	 * @param array $data Data.
	 */
	public function handle_discounts( $data ) {
		/**
		 * Register storefront tier pricing table hooks.
		 *
		 * @param array         $data   Discount data for the current customer.
		 * @param Dynamic_Rules $engine Pricing engine (public helpers only).
		 */
		do_action( 'wholesalex_register_tier_table_hooks', $data, $this );

		add_filter(
			'woocommerce_product_get_regular_price',
			function ( $regular_price, $product ) use ( $data ) {
				$product_id = $product->get_id();
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'regular_price' ) ) {
					return $regular_price; }
				if ( $this->is_product_in_bundle( $product_id ) ) {
					return $regular_price; }
				$regular_price = $this->calculate_regular_price( $regular_price, $product, $data );
				return ( ! empty( $regular_price ) ) ? (float) $regular_price : $regular_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_product_variation_get_regular_price',
			function ( $regular_price, $product ) use ( $data ) {
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'regular_price' ) ) {
					return $regular_price; }
				$regular_price = $this->calculate_regular_price( $regular_price, $product, $data );
				return ( ! empty( $regular_price ) ) ? (float) $regular_price : $regular_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_variation_prices_regular_price',
			function ( $regular_price, $product ) use ( $data ) {
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'regular_price' ) ) {
					return $regular_price; }
				$regular_price = $this->calculate_regular_price( $regular_price, $product, $data );
				return ( ! empty( $regular_price ) ) ? (float) $regular_price : $regular_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_product_get_sale_price',
			function ( $sale_price, $product ) use ( $data ) {
				$product_id = $product->get_id();
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'sale_price' ) ) {
					return $sale_price; }
				if ( $this->is_product_in_bundle( $product_id ) ) {
					return $sale_price; }
				$sale_price = $this->calculate_sale_price( $sale_price, $product, $data );
				return ( ! empty( $sale_price ) ) ? (float) $sale_price : $sale_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_product_variation_get_sale_price',
			function ( $sale_price, $product ) use ( $data ) {
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'sale_price' ) ) {
					return $sale_price; }
				$sale_price = $this->calculate_sale_price( $sale_price, $product, $data );
				return ( ! empty( $sale_price ) ) ? (float) $sale_price : $sale_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_variation_prices_sale_price',
			function ( $sale_price, $product ) use ( $data ) {
				if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'sale_price' ) ) {
					return $sale_price; }
				$sale_price = $this->calculate_sale_price( $sale_price, $product, $data );
				return ( ! empty( $sale_price ) ) ? (float) $sale_price : $sale_price;
			},
			9,
			2
		);

		add_filter(
			'woocommerce_variation_prices_price',
			function ( $price, $product ) use ( $data ) {
				// if price is not set then return the price as it is, this will avoid the issue of showing 0 price for variable products when no price is set.
				if ( '' === $price ) {
					return $price;
				}
				$sale_price          = $this->calculate_sale_price( '', $product, $data );
				$regular_price       = $this->calculate_regular_price( $price, $product, $data );
				$to_be_display_price = $this->is_available_price( $sale_price ) ? $sale_price : $regular_price;
				return ( ! empty( $to_be_display_price ) ) ? (float) $to_be_display_price : $to_be_display_price;
			},
			9,
			2
		);

		add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'variation_price_hash' ), 9, 3 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'woocommerce_get_price_html' ), 9, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'update_cart_price' ), 5 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'set_cart_item_price_to_display' ), 10, 2 );
	}

	// ─── woocommerce_get_price_html, update_cart_price, set_cart_item_price_to_display ──
	// These large methods reference $this-> properties and remain in the orchestrator.
	// For brevity in this file, they delegate back to the original facade which includes them.
	// They are loaded via the facade's require of this file.

	/**
	 * Woocommerce get price html.
	 *
	 * @param string      $price_html Price html.
	 * @param \WC_Product $product Product.
	 */
	public function woocommerce_get_price_html( $price_html, $product ) {
		if ( ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) || ! ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) ) {
			return $price_html;
		}
		if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'price_html' ) ) {
			return $price_html;
		}
		do_action( 'wholesalex_dynamic_rule_get_price_html' );

		$regular_price      = $product->get_regular_price();
		$current_role       = wholesalex()->get_current_user_role();
		$role_sale_price    = floatval( $this->get_role_base_sale_price( $product, $current_role ) );
		$role_regular_price = floatval( $this->get_role_regular_price( $product, $current_role ) );
		$has_rolewise_price = $role_sale_price || $role_regular_price;

		if ( ! is_user_logged_in() ) {
			if ( 'yes' === wholesalex()->get_setting( '_settings_login_to_view_price', 'no' ) ) {
				$lvp_url     = wholesalex()->get_setting( '_settings_login_to_view_price_login_url', get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) );
				$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
				$lvp_url     = esc_url( add_query_arg( 'redirect', $request_uri, $lvp_url ) );
				$this->make_product_non_purchasable_and_remove_add_to_cart( $product );
				return '<div><a class="wsx-link" href="' . $lvp_url . '">' . esc_html( wholesalex()->get_language_n_text( '_language_login_to_see_prices', __( 'Login to see prices', 'wholesalex' ) ) ) . '</a></div>';
			}
		}

		if ( 'pending' === $this->get_native_sale_schedule_status( $product ) && ! $has_rolewise_price ) {
			return '<span class="scheduled-sale-price">' . wc_price( $regular_price ) . '</span>';
		}

		$rp = $product->get_regular_price();
		if ( $rp ) {
			$rp = wc_get_price_to_display( $product, array( 'price' => $rp ) ); }
		$sp = $product->get_sale_price();
		if ( $this->is_available_price( $sp ) ) {
			$sp = wc_get_price_to_display( $product, array( 'price' => $sp ) ); }
		$db_price = $product->get_price( 'edit' );

		$is_woo_custom_price = get_post_meta( $product->get_id(), '_product_addons', true );
		if ( ( wholesalex()->is_plugin_installed_and_activated( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && $sp ) || ( $rp && is_array( $is_woo_custom_price ) && ! empty( $is_woo_custom_price ) ) ) {
			add_filter(
				'woocommerce_available_variation',
				// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Retain the established callback signature for compatibility.
				function ( $data, $variation ) use ( $rp, $sp ) {
					$data['display_price'] = $this->is_available_price( $sp ) ? $sp : $rp;
					return $data;
				},
				10,
				2
			);
		}

		if ( ! ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) ) {
			$is_wholesale_price_applied = $this->is_available_price( wholesalex()->get_wholesalex_wholesale_prices( $product->get_id() ) );
			// phpcs:ignore Universal.Operators.StrictComparisons -- Preserve numeric equality between stored price strings and runtime numbers.
			if ( $sp == $db_price ) {
				return apply_filters( 'wholesalex_get_price_html', $price_html, $product );
			}
			$price_html = $this->format_sale_price( $rp, $sp, $is_wholesale_price_applied ) . $product->get_price_suffix();
		}

		if ( $product->is_type( 'variable' ) ) {
			$variations_ids             = $product->get_visible_children();
			$variation_sale_prices      = array();
			$variation_regular_prices   = array();
			$is_wholesale_price_applied = false;
			foreach ( $variations_ids as $variation_id ) {
				$variation_obj = wc_get_product( $variation_id );
				if ( ! $variation_obj instanceof \WC_Product ) {
					continue;
				}
				$regular_price = $variation_obj->get_regular_price();
				if ( ! is_numeric( $regular_price ) ) {
					continue;
				}

				// Calculate before reading the request-local wholesale marker. Cached
				// variation ranges do not run the pricing filters that populate it.
				$sale_price = $variation_obj->get_sale_price();
				if ( $this->is_available_price( wholesalex()->get_wholesalex_wholesale_prices( $variation_id ) ) ) {
					$is_wholesale_price_applied = true;
				}

				// Keep undiscounted variations in the range when only some qualify.
				$effective_price            = $this->is_available_price( $sale_price ) ? $sale_price : $regular_price;
				$variation_sale_prices[]    = wc_get_price_to_display( $variation_obj, array( 'price' => $effective_price ) );
				$variation_regular_prices[] = wc_get_price_to_display( $variation_obj, array( 'price' => $regular_price ) );
			}
			if ( $is_wholesale_price_applied && ! empty( $variation_sale_prices ) ) {
				$min_sp     = min( $variation_sale_prices );
				$max_sp     = max( $variation_sale_prices );
				$min_rp     = min( $variation_regular_prices );
				$max_rp     = max( $variation_regular_prices );
				$sp         = ( $min_sp !== $max_sp ) ? wc_format_price_range( $min_sp, $max_sp ) : $min_sp;
				$rp         = ( $min_rp !== $max_rp ) ? wc_format_price_range( $min_rp, $max_rp ) : $min_rp;
				$price_html = $this->format_sale_price( $rp, $sp, $is_wholesale_price_applied ) . $product->get_price_suffix();
			}
		}
		return apply_filters( 'wholesalex_get_price_html', $price_html, $product );
	}

	/**
	 * Set cart item price to display.
	 *
	 * @param float|string $price Price.
	 * @param array        $cart_item Cart item.
	 */
	public function set_cart_item_price_to_display( $price, $cart_item ) {
		$product = $cart_item['data'];
		if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'cart_item_price', $cart_item ) ) {
			return $price;
		}
		if ( wholesalex()->is_plugin_installed_and_activated( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && isset( $cart_item['addons'] ) && is_array( $cart_item['addons'] ) ) {
			$price = $product->is_on_sale() ? wc_price( $product->get_sale_price() ) : wc_price( $product->get_regular_price() );
		}
		return $price;
	}

	/**
	 * Keep Astra's classic mini-cart unit price in sync with the calculated line.
	 *
	 * Astra rebuilds the header mini-cart through an AJAX fragment. WooCommerce's
	 * mini-cart template reads WC_Product::get_price() before WholesaleX's tier
	 * resolver (which filters the sale price) has necessarily run in that request.
	 * The product can therefore render at its retail price even though the cart
	 * line and subtotal were calculated with the correct wholesale or tier price.
	 *
	 * @param string $html          Existing mini-cart quantity HTML.
	 * @param array  $cart_item     WooCommerce cart item.
	 * @param string $cart_item_key WooCommerce cart item key.
	 * @return string
	 */
	public function filter_astra_mini_cart_item_quantity( $html, $cart_item, $cart_item_key ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Retain the established callback signature for compatibility.
		if ( ! defined( 'ASTRA_THEME_VERSION' ) && ! class_exists( 'Astra_Woocommerce' ) ) {
			return $html;
		}

		// Astra Pro's quantity-input mini-cart already displays a line subtotal,
		// not the "quantity x unit price" markup affected by this issue.
		if ( false !== strpos( $html, 'ast-mini-cart-price-wrap' ) ) {
			return $html;
		}
		if ( false !== strpos( $html, 'wholesalex-role-price-hidden-text' ) ) {
			return $html;
		}

		$product  = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		$quantity = isset( $cart_item['quantity'] ) ? (float) $cart_item['quantity'] : 0;

		if (
			! $product instanceof \WC_Product ||
			$quantity <= 0 ||
			! isset( $cart_item['line_subtotal'] ) ||
			! is_numeric( $cart_item['line_subtotal'] )
		) {
			return $html;
		}

		$product_id = $product->get_id();

		$resolved_html = apply_filters( 'wholesalex_dynamic_rule_cart_quantity_html', null, $html, $cart_item );
		if ( is_string( $resolved_html ) ) {
			return $resolved_html;
		}

			$wholesale_price = wholesalex()->get_wholesalex_wholesale_prices( $product_id );
		if ( false === $wholesale_price || ! is_numeric( $wholesale_price ) ) {
			return $html;
		}

			// Wholesale-only prices have no active tier. Use the calculated line
			// so add-ons and currency adjustments remain included. Line subtotals
			// already exclude tax, regardless of how catalog prices were entered.
			$line_price = (float) $cart_item['line_subtotal'];
		if ( WC()->cart && WC()->cart->display_prices_including_tax() ) {
			$line_price += isset( $cart_item['line_subtotal_tax'] ) ? (float) $cart_item['line_subtotal_tax'] : 0.0;
		}

			return '<span class="quantity">' . sprintf(
				'%s &times; %s',
				esc_html( $cart_item['quantity'] ),
				wc_price( $line_price / $quantity )
			) . '</span>';
	}

	/**
	 * Update cart price.
	 *
	 * @param \WC_Cart $cart Cart.
	 */
	public function update_cart_price( $cart ) {
		if ( ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) || ! is_object( $cart ) ) {
			return;
		}
		// Bulk additions and cart updates can calculate totals multiple times in
		// one request. Resolve each line again from its regular/sale price so new
		// items and changed quantities receive the current wholesale price.
		foreach ( $cart->get_cart() as $cart_item ) {
			$woo_custom_price = 0;
			$product          = $cart_item['data'];
			$product_id       = $product->get_id();
			$quantity         = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
			if ( ! empty( $cart_item['free_product'] ) ) {
				$product->set_price( 0 );
				continue;
			}
			if ( apply_filters( 'wholesalex_ignore_dynamic_price', false, $product, 'cart_totals', $cart_item ) ) {
				continue; }
			if ( $this->is_product_in_bundle( $product_id ) ) {
				continue; }

			if ( wholesalex()->is_plugin_installed_and_activated( 'woocommerce-product-addons/woocommerce-product-addons.php' ) && isset( $cart_item['addons'] ) && is_array( $cart_item['addons'] ) && ! empty( $cart_item['addons'] ) ) {
				$sale_price = $product->get_sale_price();
				$base_price = isset( $cart_item['addons_price_before_calc'] ) ? floatval( $cart_item['addons_price_before_calc'] ) : ( $this->is_available_price( $sale_price ) ? $sale_price : $product->get_regular_price() );
				foreach ( $cart_item['addons'] as $addon ) {
					if ( isset( $addon['price'], $addon['price_type'] ) ) {
						switch ( $addon['price_type'] ) {
							case 'flat_fee':
								$woo_custom_price += floatval( $addon['price'] );
								break;
							case 'percentage_based':
								$woo_custom_price += ( $base_price * floatval( $addon['price'] ) / 100 );
								break;
							case 'quantity_based':
								$woo_custom_price += floatval( $addon['price'] );
								break;
						}
					}
				}
				$product->set_price( max( 0, $this->price_after_currency_changed( $base_price + $woo_custom_price ) ) );
				continue;
			}

			$sale_price = $product->get_sale_price();
			$price      = $this->is_available_price( $sale_price ) ? $sale_price : $product->get_regular_price();

			$prad_option_price = 0;
			if ( function_exists( 'WC' ) && defined( 'PRAD_VER' ) && isset( $cart_item['prad_selection']['price'] ) ) {
				$prad_option_price = floatval( $cart_item['prad_selection']['price'] );
			}

			if ( ! $product->is_type( 'simple' ) ) {
				if ( function_exists( 'WC' ) && wholesalex()->is_plugin_installed_and_activated( 'product-extras-for-woocommerce/product-extras-for-woocommerce.php' ) && isset( $cart_item['product_extras']['price_with_extras'] ) ) {
					$product->set_price( max( 0, $this->price_after_currency_changed( floatval( $cart_item['product_extras']['price_with_extras'] ) ) ) );
				} else {
					$base = $price;
					if ( $prad_option_price > 0 && $quantity > 0 ) {
						$base += $prad_option_price; }
					$product->set_price( max( 0, $this->price_after_currency_changed( $base ) ) );
				}
			} elseif ( function_exists( 'WC' ) && wholesalex()->is_plugin_installed_and_activated( 'product-extras-for-woocommerce/product-extras-for-woocommerce.php' ) && isset( $cart_item['product_extras']['price_with_extras'] ) ) {
					$product->set_price( max( 0, $this->price_after_currency_changed( floatval( $cart_item['product_extras']['price_with_extras'] ) ) ) );
			} else {
				$base = $price;
				if ( $prad_option_price > 0 ) {
					$base += $prad_option_price; }
				$product->set_price( max( 0, $this->price_after_currency_changed( $base ) ) );
			}
		}
	}


	/**
	 * Render the tier pricing table for the current product.
	 *
	 * Kept for third-party callers; the table is provided by the extension.
	 */
	public function wholesalex_product_price_table() {
		do_action( 'wholesalex_render_product_tier_table' );
	}


	// ─── Single Product Page Promo ───────────────────────────────
	// This 540+ line method with its helpers remains in the facade for now.
	// It will be fully migrated in phase 2.

	/**
	 * Build or render product promotion details for eligible rules.
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $cart_related_data Cart related data.
	 * @param array       $payment_related_rules Payment related rules.
	 * @param array       $profile_shipping_data Profile shipping data.
	 * @param array       $min_max_data Min max data.
	 * @param bool        $is_tax_exempt Is tax exempt.
	 * @param bool        $is_echo Whether to render the generated markup.
	 */
	public function handle_single_product_page_promo( $product, $cart_related_data, $payment_related_rules, $profile_shipping_data, $min_max_data, $is_tax_exempt, $is_echo = false ) {
		do_action( 'wholesalex_before_add_to_cart_form', $product );

		if ( 'yes' === wholesalex()->get_setting( 'show_promotions_on_sp', 'no' ) ) {

			$this->check_for_product_discounts( $product );

			$this->check_for_cart_releated_discounts( $product, $cart_related_data );

			$this->check_for_free_shipping( $product, $profile_shipping_data );
		}

		if ( ! empty( $min_max_data ) ) {
			// Minimum.
			if ( 'yes' === wholesalex()->get_setting( 'show_order_qty_text_on_sp', 'no' ) && isset( $min_max_data['min_order_qty'] ) && ! empty( $min_max_data['min_order_qty'] ) ) {
				foreach ( $min_max_data['min_order_qty'] as $rule ) {
					if ( isset( $rule['conditions'] ) && ! self::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
						continue;
					}
					$is_all_products = $rule['filter']['is_all_products'];

					if ( self::is_eligible_for_rule( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(), $product->get_parent_id() ? $product->get_id() : 0, $rule['filter'] ) || $is_all_products ) {
						wholesalex()->set_rule_data(
							$rule['id'],
							$product->get_id(),
							'min_order_qty',
							array(
								'conditions'          => $rule['conditions'] ? $rule['conditions'] : array(),
								'minimum_qty'         => $rule['rule']['_min_order_qty'],
								'who_priority'        => $rule['who_priority'],
								'applied_on_priority' => $rule['applied_on_priority'],
								'end_date'            => $rule['end_date'],
							)
						);
					}
				}
			}
			/**
			 * Fires after the single product page's order-quantity rule data is prepared.
			 *
			 * @param \WC_Product $product      Product being displayed.
			 * @param array       $min_max_data Order-quantity rules.
			 */
			do_action( 'wholesalex_dr_single_product_min_max_rule_data', $product, $min_max_data );
		}

		$modal_content = '';
		if ( ! $is_echo ) {
			ob_start();
		}

		if ( ! empty( wholesalex()->get_rule_data( $product->get_id() ) ) ) {

			ob_start();

			if ( 'yes' === wholesalex()->get_setting( 'show_product_discounts_text', 'no' ) ) {

				$product_discounts = wholesalex()->get_rule_data( $product->get_id(), 'product_discount' );

				if ( ! empty( $product_discounts ) ) {

					usort( $product_discounts, array( $this, 'compare_by_priority_reverse' ) );
					?>
					<div class="wsx-sp-product-discounts">
						<?php
						if ( 'yes' === wholesalex()->get_setting( 'product_discount_rule_sp_show_rule_info', 'yes' ) ) {
							?>
							<div class="wsx-sp-rule-info">
								<div class="wsx-font-14 wsx-font-medium">
									<?php echo esc_html( wholesalex()->get_setting( 'product_discount_rule_info_rule_type_text', __( 'Product Discount', 'wholesalex' ) ) ); ?>
								</div>
								<?php
								if ( count( $product_discounts ) > 1 ) {
									?>
									<div class="wsx-font-14">
										<?php echo esc_html( wholesalex()->get_setting( 'dynamic_rule_promotional_explainer_text_single_discount', __( 'You can avail one of the following offers by completing the requirements.', 'wholesalex' ) ) ); ?>
									</div>
									<?php
								}
								?>
							</div>
							<?php
						}
						?>

						<div class="wsx-sp-discounts-cards wsx-p-4 wsx-br-sm wsx-mt-8 wsx-bg-promotion">
							<?php
							foreach ( $product_discounts as $cd ) {

								if ( 'percentage' === $cd['type'] ) {
									$heading_text = $cd['value'] . __( ' % OFF', 'wholesalex' );
								} elseif ( 'amount' === $cd['type'] ) {
									$heading_text = wc_price( $cd['value'] ) . __( ' OFF', 'wholesalex' );
								} elseif ( 'fixed' === $cd['type'] ) {
									$heading_text = '<del>' . wc_price( $product->get_price() ) . '</del>. to <ins>' . wc_price( $cd['value'] ) . '</ins>';
								}

								$conditions = 'yes' === wholesalex()->get_setting( 'show_discount_conditions_on_sp', 'no' ) && isset( $cd['conditions']['tiers'] ) ? $this->generate_rule_conditions_markup( $cd['conditions']['tiers'] ) : '';
								$validity   = '';
								if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
									$validity = $cd['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . $this->restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $cd['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
								}
								?>
								<div class="wsx-single-product-discount-card wsx-cart-discount-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
									<div class="wsx-font-18 wsx-font-medium" style="color: var(--color-notice)"> <?php echo wp_kses_post( $heading_text ); ?></div>
									<div class="wsx-font-14"><?php echo wp_kses_post( $conditions . $validity ); ?></div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
					<?php
				}
			}
			// Cart discount promo HTML — managed in Rule_Cart_Discount::render_promo_html().
			$this->rule_cart_discount->render_promo_html( $product, array( $this, 'generate_rule_conditions_markup' ) );
			if ( 'yes' === wholesalex()->get_setting( 'show_payment_method_discount_promo_text_sp', 'no' ) ) {

				$payment_discount = wholesalex()->get_rule_data( $product->get_id(), 'payment_discount' );

				if ( ! empty( $payment_discount ) ) {
					usort( $payment_discount, array( $this, 'compare_by_priority_reverse' ) );
					?>
					<div class="wsx-sp-payment-discounts">
						<?php
						if ( 'yes' === wholesalex()->get_setting( 'payment_discount_rule_sp_show_rule_info', 'yes' ) ) {
							?>
							<div class="wsx-sp-rule-info">
								<div class="wsx-font-14 wsx-font-medium">
									<?php echo esc_html( wholesalex()->get_setting( 'payment_method_discount_label_text', __( 'Payment Method Discount', 'wholesalex' ) ) ); ?>
								</div>
								<?php
								if ( count( $payment_discount ) > 1 ) {
									?>
									<div class="wsx-font-14">
										<?php echo esc_html( wholesalex()->get_setting( 'dynamic_rule_promotional_explainer_text_single_discount', __( 'You can avail one of the following offers by completing the requirements.', 'wholesalex' ) ) ); ?>
									</div>
									<?php
								}
								?>
							</div>
							<?php
						}
						?>

						<div class="wsx-sp-discounts-cards wsx-p-4 wsx-br-sm wsx-mt-8 wsx-bg-promotion">
							<?php
							foreach ( $payment_discount as $pd ) {
								if ( 'percentage' === $pd['type'] ) {
									$heading_text = $pd['value'] . __( ' % OFF', 'wholesalex' );
								} elseif ( 'amount' === $pd['type'] ) {
									$heading_text = wc_price( $pd['value'] ) . __( ' OFF', 'wholesalex' );
								} elseif ( 'fixed' === $pd['type'] ) {
									$heading_text = '<del>' . wc_price( $product->get_price() ) . '</del>. to <ins>' . wc_price( $pd['value'] ) . '</ins>';
								}
								$desc       = '<span class="wsx-font-14">' . __( 'Use ', 'wholesalex' ) . implode( ',', $pd['gateways'] ) . ' </span>';
								$conditions = 'yes' === wholesalex()->get_setting( 'show_discount_conditions_on_sp', 'no' ) && isset( $pd['conditions']['tiers'] ) ? $this->generate_rule_conditions_markup( $pd['conditions']['tiers'] ) : '';
								$validity   = '';
								if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
									$validity = $pd['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . $this->restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $pd['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
								}
								?>
								<div class="wsx-single-product-discount-card wsx-payment-discount-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
									<div class="wsx-font-18"><?php echo wp_kses_post( $heading_text ); ?></div>
									<div class="wsx-font-14"><?php echo wp_kses_post( $desc . $conditions . $validity ); ?> </div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
					<?php
				}
			}
			if ( 'yes' === wholesalex()->get_setting( 'show_bogo_discount_promo_text_on_sp', 'no' ) ) {

				$buy_x_get_one = wholesalex()->get_rule_data( $product->get_id(), 'buy_x_get_one' );

				if ( ! empty( $buy_x_get_one ) ) {
					usort( $buy_x_get_one, array( $this, 'compare_by_priority_reverse' ) );

					?>
					<div class="wsx-sp-bogo-discounts">
						<?php
						if ( 'yes' === wholesalex()->get_setting( 'bogo_discount_rule_sp_show_rule_info', 'yes' ) ) {
							?>
							<div class="wsx-sp-rule-info">
								<div class="wsx-font-14 wsx-font-medium">
									<?php echo esc_html( wholesalex()->get_setting( 'bogo_discount_rule_info_rule_type_text', __( 'BOGO Discount', 'wholesalex' ) ) ); ?>
								</div>
								<?php
								if ( count( $buy_x_get_one ) > 1 ) {
									?>
									<div class="wsx-font-14">
										<?php echo esc_html( wholesalex()->get_setting( 'dynamic_rule_promotional_explainer_text_multiple_discount', __( 'You can avail following offers by completing the requirements.', 'wholesalex' ) ) ); ?>
									</div>
									<?php
								}
								?>
							</div>
							<?php
						}
						?>

						<div class="wsx-sp-discounts-cards wsx-p-4 wsx-br-sm wsx-mt-8 wsx-bg-promotion">
							<?php
							foreach ( $buy_x_get_one as $pd ) {
								$min_qty      = $pd['minimum_qty'];
								$heading_text = wholesalex()->get_setting( 'bogo_discount_free_text_on_sp', __( 'Get 1 Free', 'wholesalex' ) );
								$desc         = '<span class="wsx-font-14">' . $this->restore_smart_tags(
									array(
										'{required_quantity}' => $min_qty,
										'{product_title}' => $product->get_title(),
									),
									wholesalex()->get_setting( 'bogo_discounts_promo_sp_desc_text_on_sp', __( 'Buy at least {required_quantity} products', 'wholesalex' ) )
								) . ' </span>';
								$conditions   = 'yes' === wholesalex()->get_setting( 'show_discount_conditions_on_sp', 'no' ) && isset( $pd['conditions']['tiers'] ) ? $this->generate_rule_conditions_markup( $pd['conditions']['tiers'] ) : '';
								$validity     = '';
								if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
									$validity = $pd['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . $this->restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $pd['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
								}
								?>
								<div class="wsx-single-product-discount-card wsx-sp-bogo-discount-cart wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
									<div class="wsx-font-18"><?php echo wp_kses_post( $heading_text ); ?></div>
									<div class="wsx-font-14"><?php echo wp_kses_post( $desc . $conditions . $validity ); ?></div>
								</div>
								<?php
							}
							?>
						</div>
					</div>
					<?php
				}
			}
			if ( 'yes' === wholesalex()->get_setting( 'show_free_shipping_promo_text_on_sp', 'no' ) ) {

				$free_shipping = wholesalex()->get_rule_data( $product->get_id(), 'free_shipping' );

				if ( ! empty( $free_shipping ) ) {
					usort( $free_shipping, array( $this, 'compare_by_priority_reverse' ) );

					ob_start();
					foreach ( $free_shipping as $pd ) {
						if ( isset( $pd['conditions']['tiers'] ) && ! empty( $pd['conditions']['tiers'] ) ) {
							$heading_text = wholesalex()->get_setting( 'free_shipping_heading_text_on_sp', __( 'Free Shipping', 'wholesalex' ) );
							$conditions   = 'yes' === wholesalex()->get_setting( 'show_discount_conditions_on_sp', 'no' ) && isset( $pd['conditions']['tiers'] ) ? $this->generate_rule_conditions_markup( $pd['conditions']['tiers'] ) : '';
							$validity     = '';
							if ( 'yes' === wholesalex()->get_setting( 'show_discounts_validity_text_on_sp', 'no' ) ) {
								$validity = $pd['end_date'] ? '<div class="wsx-single-product-discount-card-validity wsx-mt-4" style="color: var(--color-warning);">' . $this->restore_smart_tags( array( '{end_date}' => gmdate( 'Y-m-d', strtotime( $pd['end_date'] . ' +1 day' ) ) ), wholesalex()->get_setting( 'discounts_validity_text', 'Valid till: {end_date}' ) ) . '</div>' : '';
							}
							?>
							<div class="wsx-single-product-discount-card  wsx-sp-free-shipping-cart wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
								<div class="wsx-font-18"><?php echo wp_kses_post( $heading_text ); ?></div>
								<div class="wsx-font-14"> <?php echo wp_kses_post( $conditions . $validity ); ?></div>
							</div>
							<?php
						}
					}
					$conditional_free_shipping_content = ob_get_clean();
					if ( $conditional_free_shipping_content ) {
						?>
						<div class="wsx-free-shipping-discounts">
							<div class="wsx-sp-discounts-cards wsx-p-4 wsx-br-sm wsx-mt-8 wsx-bg-promotion">
								<?php echo wp_kses_post( $conditional_free_shipping_content ); ?>
							</div>
						</div>
						<?php
					}
				}
			}

			$modal_content = ob_get_clean();

			$modal_content = trim( apply_filters( 'wholesalex_promotions_frontend_popup_modal_content', $modal_content ) );
			if ( $modal_content ) {
				do_action( 'wholesalex_promotions_popup_header' );
				?>
				<div class="wsx-d-flex wsx-item-center wsx-gap-12 wsx-mb-24">
					<div class="wsx-font-14"> <?php echo esc_html__( 'Promotions:', 'wholesalex' ); ?></div>
					<div class="wsx-relative">
						<div class="wsx-font-12 wsx-bg-secondary wsx-br-md wsx-pt-4 wsx-pb-6 wsx-plr-10 wsx-color-text-reverse wsx-curser-pointer wsx-btn-icon"
							id="wsx-sp-dr-view-more" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
							<?php echo esc_html( wholesalex()->get_setting( 'promo_button_text_on_sp', __( 'Get exclusive offers', 'wholesalex' ) ) ); ?>
							<div class="wsx-icon" id="wsx-icon-angle-down"
								style="margin-bottom: -4px; transition: all 0.3s;"><svg xmlns="http://www.w3.org/2000/svg"
									width="20" height="20" fill="none">
									<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
										d="m5 7.5 5 5 5-5" />
								</svg></div>
						</div>

						<div class="wsx-dr-single-product-discounts-modal wsx-absolute wsx-down-4 wsx-z-999 wsx-card wsx-plr-8 wsx-pt-10 wsx-pb-10 wsx-width-70v wsx-width-250 wsx-shadow-primary"
							id="wsx-dr-single-product-discounts-modal-<?php echo esc_attr( $product->get_id() ); ?>" style="display:none;">
							<div class="wsx-font-14 wsx-font-bold wsx-text-center wsx-color-text-medium wsx-mb-8">
								<?php echo esc_html__( 'Conditional Discount Offers', 'wholesalex' ); ?></div>
							<?php echo wp_kses_post( $modal_content ); ?>
						</div>
					</div>
				</div>
				<?php
				do_action( 'wholesalex_promotions_popup_footer' );
			}
			if ( 'yes' === wholesalex()->get_setting( 'show_order_qty_text_on_sp', 'no' ) && ( ! empty( wholesalex()->get_rule_data( $product->get_id() )['min_order_qty'] ) || ! empty( wholesalex()->get_rule_data( $product->get_id() )['max_order_qty'] ) ) ) {
				$min_qty = '';
				if ( isset( wholesalex()->get_rule_data( $product->get_id() )['min_order_qty'] ) ) {
					foreach ( wholesalex()->get_rule_data( $product->get_id() )['min_order_qty'] as $rule ) {
						$min_qty = $rule['minimum_qty'];
					}
				}
				$max_qty = '';
				if ( isset( wholesalex()->get_rule_data( $product->get_id() )['max_order_qty'] ) ) {
					foreach ( wholesalex()->get_rule_data( $product->get_id() )['max_order_qty'] as $rule ) {
						$max_qty = $rule['maximum_qty'];
					}
				}

				if ( $max_qty && $min_qty ) {
					$message = $this->restore_smart_tags(
						array(
							'{minimum_qty}'   => $min_qty,
							'{maximum_qty}'   => $max_qty,
							'{product_title}' => $product->get_title(),
						),
						wholesalex()->get_setting( 'min_max_both_order_qty_promo_text', __( 'You can add minimum {minimum_qty} and maximum {maximum_qty} quantity of this product', 'wholesalex' ) )
					);
				} elseif ( $min_qty ) {
					$message = $this->restore_smart_tags(
						array(
							'{minimum_qty}'   => $min_qty,
							'{product_title}' => $product->get_title(),
						),
						wholesalex()->get_setting( 'only_minimum_order_qty_promo_text', __( 'You have to add minimun {minimum_qty} quantity', 'wholesalex' ) )
					);
				} elseif ( $max_qty ) {
					$message = $this->restore_smart_tags(
						array(
							'{maximum_qty}'   => $max_qty,
							'{product_title}' => $product->get_title(),
						),
						wholesalex()->get_setting( 'only_maximum_order_qty_promo_text', __( 'You can add maximum {maximum_qty} quantity', 'wholesalex' ) )
					);
				}
				?>
				<div class="wsx-single-product-discount-card wsx-mt-10 wsx-min-max-sp-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion"><?php echo esc_html( $message ); ?> </div>
				<?php
			}

			if ( 'yes' === wholesalex()->get_setting( 'show_free_shipping_promo_text_on_sp', 'no' ) ) {

				$free_shipping    = wholesalex()->get_rule_data( $product->get_id(), 'free_shipping' );
				$is_free_shipping = false;

				if ( ! empty( $free_shipping ) ) {
					foreach ( $free_shipping as $pd ) {
						if ( ! isset( $pd['conditions']['tiers'] ) || empty( $pd['conditions']['tiers'] ) ) {
							$is_free_shipping = true;
							break;
						}
					}
				}

				if ( $is_free_shipping ) {
					$shipping_text = wholesalex()->get_setting( 'free_shipping_text_on_sp', __( 'Free Shipping', 'wholesalex' ) );
					?>
					<div class="wsx-single-product-discount-card wsx-mt-10 wsx-free-shipping-sp-card wsx-p-8 wsx-br-md wsx-bg-base1 wsx-border-default wsx-bc-promotion">
						<?php echo esc_html( $shipping_text ); ?> </div>
					<?php
				}
			}

			do_action( 'wholesalex_product_rule_promotions', $product, $this );

			if ( $modal_content ) {
				?>
				<script type="text/javascript">
					(function($) {
						'use strict';
						let view_more = $("#wsx-sp-dr-view-more");
						view_more.on('click', function(e) {
							const product_id = view_more.data('product-id');
							$("#wsx-dr-single-product-discounts-modal-" + product_id).slideToggle(100);
							const icon = $("#wsx-icon-angle-down");
							if (icon.hasClass('rotated')) {
								icon.removeClass('rotated').css('transform', 'rotate(0deg)');
							} else {
								icon.addClass('rotated').css('transform', 'rotate(180deg)');
							}
						});

						$(document).click(function(e) {
							if ($(e.target).closest('.wsx-dr-single-product-discounts-modal').length != 0) return false;
							if ($(e.target).closest('#wsx-sp-dr-view-more').length != 0) return false;
							$('.wsx-dr-single-product-discounts-modal').hide(100);
							$("#wsx-icon-angle-down").removeClass('rotated').css('transform', 'rotate(0deg)');
						});

					})(jQuery);
				</script>
				<?php
			}
		}

		if ( ! $is_echo ) {
			return ob_get_clean();
		}
	}

	/**
	 * Generate rule conditions markup.
	 *
	 * @param array $conditions Conditions.
	 */
	public function generate_rule_conditions_markup( $conditions ) {
		$data   = array();
		$markup = '<div>';
		foreach ( $conditions as $condition ) {
			if ( isset( $condition['_conditions_for'], $condition['_conditions_operator'], $condition['_conditions_value'] ) ) {
				$con_value = floatval( $condition['_conditions_value'] );
				if ( in_array( $condition['_conditions_operator'], array( 'greater', 'less' ), true ) ) {
					$labels = array(
						'cart_total_qty'    => __( 'Product quantity', 'wholesalex' ),
						'cart_total_value'  => __( 'Cart value', 'wholesalex' ),
						'cart_total_weight' => __( 'Cart weight', 'wholesalex' ),
					);
					$label  = $labels[ $condition['_conditions_for'] ] ?? '';
					if ( '' !== $label ) {
						/* translators: 1: condition name, 2: exclusive limit. */
						$template = 'greater' === $condition['_conditions_operator'] ? __( '%1$s must be greater than %2$s.', 'wholesalex' ) : __( '%1$s must be less than %2$s.', 'wholesalex' );
						$markup  .= '<div>' . esc_html( sprintf( $template, $label, (string) $con_value ) ) . '</div>';
					}
					continue;
				}
				if ( 'less_equal' === $condition['_conditions_operator'] || 'less' === $condition['_conditions_operator'] ) {
					if ( ! isset( $data[ $condition['_conditions_for'] ]['less'] ) ) {
						$data[ $condition['_conditions_for'] ]['less'] = $con_value;
					}
					$data[ $condition['_conditions_for'] ]['less'] = min( $data[ $condition['_conditions_for'] ]['less'], $con_value );
				}
				if ( 'greater_equal' === $condition['_conditions_operator'] || 'greater' === $condition['_conditions_operator'] ) {
					if ( ! isset( $data[ $condition['_conditions_for'] ]['greater'] ) ) {
						$data[ $condition['_conditions_for'] ]['greater'] = $con_value;
					}
					$data[ $condition['_conditions_for'] ]['greater'] = max( $data[ $condition['_conditions_for'] ]['greater'], $con_value );
				}
			}
		}

		if ( isset( $data['cart_total_weight'] ) ) {
			$weight_unit = get_option( 'woocommerce_weight_unit' );
		}
		foreach ( $data as $con_name => $cons ) {
			switch ( $con_name ) {
				case 'cart_total_value':
					if ( isset( $cons['greater'], $cons['less'] ) ) {
						$cons['greater'] = wc_price( $cons['greater'] );
						$cons['less']    = wc_price( $cons['less'] );
						$markup         .= '<div>' . $this->restore_smart_tags(
							array(
								'{min_value}' => $cons['greater'],
								'{max_value}' => $cons['less'],
							),
							wholesalex()->get_setting( 'cart_total_value_min_max_conditions_text', __( 'Spend {min_value} to {max_value}', 'wholesalex' ) )
						) . '</div>';
					} elseif ( isset( $cons['greater'] ) ) {
						$cons['greater'] = wc_price( $cons['greater'] );
						$markup         .= '<div>' . $this->restore_smart_tags( array( '{max_value}' => $cons['greater'] ), wholesalex()->get_setting( 'cart_total_value_min_conditions_text', __( 'Spend min {max_value}', 'wholesalex' ) ) ) . '</div>';
					} elseif ( isset( $cons['less'] ) ) {
						$cons['less'] = wc_price( $cons['less'] );
						$markup      .= '<div>' . $this->restore_smart_tags( array( '{min_value}' => $cons['less'] ), wholesalex()->get_setting( 'cart_total_value_max_conditions_text', __( 'Spend upto {min_value}', 'wholesalex' ) ) ) . '</div>';
					}
					break;
				case 'cart_total_qty':
					if ( isset( $cons['greater'] ) && isset( $cons['less'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags(
							array(
								'{min_value}' => $cons['greater'],
								'{max_value}' => $cons['less'],
							),
							wholesalex()->get_setting( 'cart_total_qty_min_max_conditions_text', __( 'Add {min_value} to {max_value} product(s) to cart', 'wholesalex' ) )
						) . '</div>';
					} elseif ( isset( $cons['greater'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags( array( '{max_value}' => $cons['greater'] ), wholesalex()->get_setting( 'cart_total_qty_min_conditions_text', __( 'Buy minimum {max_value} product(s) to get the discount', 'wholesalex' ) ) ) . '</div>';
					} elseif ( isset( $cons['less'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags( array( '{min_value}' => $cons['less'] ), wholesalex()->get_setting( 'cart_total_qty_max_conditions_text', __( 'Don&apos;t buy more than {min_value} product(s) to get the discount', 'wholesalex' ) ) ) . '</div>';
					}
					break;
				case 'cart_total_weight':
					if ( isset( $cons['greater'], $cons['less'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags(
							array(
								'{min_value}' => $cons['greater'],
								'{max_value}' => $cons['less'],
								'{unit}'      => $weight_unit,
							),
							wholesalex()->get_setting( 'cart_total_weight_min_max_conditions_text', __( 'Add {min_value} to {max_value} {unit} to cart', 'wholesalex' ) )
						) . '</div>';
					} elseif ( isset( $cons['greater'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags(
							array(
								'{max_value}' => $cons['greater'],
								'{unit}'      => $weight_unit,
							),
							wholesalex()->get_setting( 'cart_total_weight_min_conditions_text', __( 'Add min {max_value} {unit} to cart', 'wholesalex' ) )
						) . '</div>';
					} elseif ( isset( $cons['less'] ) ) {
						$markup .= '<div>' . $this->restore_smart_tags(
							array(
								'{min_value}' => $cons['less'],
								'{unit}'      => $weight_unit,
							),
							wholesalex()->get_setting( 'cart_total_weight_max_conditions_text', __( 'Add up to {min_value} {unit} to cart', 'wholesalex' ) )
						) . '</div>';
					}
					break;
				default:
					break;
			}
		}
		$markup .= '</div>';
		return $markup;
	}

	/**
	 * Check for product discounts.
	 *
	 * @param \WC_Product $product Product.
	 */
	public function check_for_product_discounts( $product ) {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;

		if ( ! isset( $this->valid_dynamic_rules['product_discount'] ) || empty( $this->valid_dynamic_rules['product_discount'] ) ) {
			return;
		}

		if ( 'yes' !== wholesalex()->get_setting( 'show_product_discounts_text', 'no' ) ) {
			return;
		}

		foreach ( $this->valid_dynamic_rules['product_discount'] as $pd ) {
			if ( ! empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'product_discount', $pd['id'] ) ) ) {
				continue;
			}

			if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $pd['filter'] ) ) {
				wholesalex()->set_rule_data(
					$pd['id'],
					$variation_id ? $variation_id : $product_id,
					'product_discount',
					array(
						'value'               => $pd['rule']['_discount_amount'],
						'type'                => $pd['rule']['_discount_type'],
						'conditions'          => $pd['conditions'],
						'who_priority'        => $pd['who_priority'],
						'applied_on_priority' => $pd['applied_on_priority'],
						'end_date'            => $pd['end_date'],
					)
				);
			}
		}
	}

	/**
	 * Check for free shipping.
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $profile_shipping_data Profile shipping data.
	 */
	public function check_for_free_shipping( $product, $profile_shipping_data ) {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;

		$is_profile_free_shipping = false;
		if ( ! empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'free_shipping', 'profile' ) ) ) {
			$is_profile_free_shipping = true;
		}

		if ( ! empty( $profile_shipping_data ) ) {
			if ( isset( $profile_shipping_data['method_type'] ) && 'force_free_shipping' === $profile_shipping_data['method_type'] ) {
				$is_profile_free_shipping = true;
			}

			if ( ! $is_profile_free_shipping && isset( $profile_shipping_data['method_type'] ) && 'specific_shipping_methods' === $profile_shipping_data['method_type'] ) {
				foreach ( $profile_shipping_data['methods'] as $method ) {
					if ( ! isset( $this->cached_shipping_method_id[ $method['value'] ] ) ) {
						$zone = WC_Shipping_Zones::get_shipping_method( $method['value'] );
						$this->cached_shipping_method_id[ $method['value'] ] = $zone->id;
					}
					if ( 'free_shipping' === $this->cached_shipping_method_id[ $method['value'] ] ) {
						$is_profile_free_shipping = true;
						break;
					}
				}
			}
		}

		if ( $is_profile_free_shipping ) {
			wholesalex()->set_rule_data(
				'profile',
				$variation_id ? $variation_id : $product_id,
				'free_shipping',
				array(
					'conditions'          => array(),
					'end_date'            => false,
					'who_priority'        => 10,
					'applied_on_priority' => 10,
				),
			);
		} elseif ( ! empty( $this->valid_dynamic_rules['shipping_rule'] ) ) {
			foreach ( $this->valid_dynamic_rules['shipping_rule'] as $sr ) {
				if ( empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'free_shipping', $sr['id'] ) ) ) {
					if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $sr['filter'] ) ) {
						if ( $sr['rule']['__shipping_zone'] === $this->current_shipping_zone && ! $is_profile_free_shipping ) {
							$methods = $sr['rule']['_shipping_zone_methods'];
							foreach ( $methods as $method ) {
								if ( ! isset( $this->cached_shipping_method_id[ $method['value'] ] ) ) {
									$zone = WC_Shipping_Zones::get_shipping_method( $method['value'] );
									$this->cached_shipping_method_id[ $method['value'] ] = $zone->id;
								}
								if ( 'free_shipping' === $this->cached_shipping_method_id[ $method['value'] ] ) {
									wholesalex()->set_rule_data(
										$sr['id'],
										$variation_id ? $variation_id : $product_id,
										'free_shipping',
										array(
											'conditions'   => $sr['conditions'] ? $sr['conditions'] : array(),
											'who_priority' => $sr['who_priority'],
											'applied_on_priority' => $sr['applied_on_priority'],
											'end_date'     => $sr['end_date'],
										),
									);
									break;
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Register eligible cart-related discount rules for a product on the single product page.
	 *
	 * Iterates over active cart_discount, payment_discount, and buy_x_get_one rules
	 *
	 * The raw `$cart_related_data` is passed through the
	 * `wholesalex_dr_cart_related_data` filter before processing, allowing third-party
	 * plugins or Pro add-ons to inject or modify rules at runtime.
	 *
	 * Called by:
	 *   - `handle_single_product_page_promo()` (line 2406) — triggered on the single
	 *     product page when `show_promotions_on_sp` setting is enabled.
	 *
	 * Settings that gate each rule type:
	 *   - `show_cart_discount_text`                  → cart_discount rules
	 *   - `show_payment_method_discount_promo_text_sp` → payment_discount rules
	 *   - `show_bogo_discount_promo_text_on_sp`        → buy_x_get_one rules
	 *
	 * @param WC_Product $product          The current product being rendered on the single product page.
	 * @param array      $cart_related_data Associative array of cart-related rule sets, keyed by rule type
	 *                                      (e.g. 'cart_discount', 'payment_discount', 'buy_x_get_one').
	 *
	 * @return void
	 */
	public function check_for_cart_releated_discounts( $product, $cart_related_data ) {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;
		$rules        = apply_filters( 'wholesalex_dr_cart_related_data', $cart_related_data );

		if ( ! empty( $rules ) ) {
			// Cart discount promo data registration — managed in Rule_Cart_Discount::register_promo_data().
			if ( isset( $rules['cart_discount'] ) && ! empty( $rules['cart_discount'] ) ) {
				$this->rule_cart_discount->register_promo_data( $product, $rules['cart_discount'] );
			}
			if ( 'yes' === wholesalex()->get_setting( 'show_payment_method_discount_promo_text_sp', 'no' ) && isset( $rules['payment_discount'] ) && ! empty( $rules['payment_discount'] ) ) {
				foreach ( $rules['payment_discount'] as $rule ) {
					if ( empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'payment_discount', $rule['id'] ) ) ) {
						$gateways_name = Dynamic_Rules_Condition_Engine::get_multiselect_values( $rule['rule']['_payment_gateways'], 'name' );
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $rule['filter'] ) ) {
							wholesalex()->set_rule_data(
								$rule['id'],
								$variation_id ? $variation_id : $product_id,
								'payment_discount',
								array(
									'type'                => ! empty( $rule['rule']['_discount_type'] ) ? $rule['rule']['_discount_type'] : 'percentage',
									'value'               => $rule['rule']['_discount_amount'],
									'conditions'          => $rule['conditions'],
									'gateways'            => $gateways_name,
									'who_priority'        => $rule['who_priority'],
									'applied_on_priority' => $rule['applied_on_priority'],
									'end_date'            => $rule['end_date'],
								)
							);
						}
					}
				}
			}
			if ( 'yes' === wholesalex()->get_setting( 'show_bogo_discount_promo_text_on_sp', 'no' ) && isset( $rules['buy_x_get_one'] ) && ! empty( $rules['buy_x_get_one'] ) ) {
				foreach ( $rules['buy_x_get_one'] as $rule ) {
					if ( empty( wholesalex()->get_rule_data( $variation_id ? $variation_id : $product_id, 'buy_x_get_one', $rule['id'] ) ) ) {
						if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $rule['filter'] ) ) {
							wholesalex()->set_rule_data(
								$rule['id'],
								$variation_id ? $variation_id : $product_id,
								'buy_x_get_one',
								array(
									'minimum_qty'         => $rule['rule']['_minimum_purchase_count'],
									'conditions'          => $rule['conditions'] ? $rule['conditions'] : array(),
									'who_priority'        => $rule['who_priority'],
									'applied_on_priority' => $rule['applied_on_priority'],
									'end_date'            => $rule['end_date'],
								)
							);
						}
					}
				}
			}
		}
	}
}
