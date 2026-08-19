<?php
/**
 * WholesaleX Wholesale Pricing - Rule Engine
 *
 * Loads active wholesale-pricing rules, evaluates targeting/schedule/restrictions,
 * and delegates price calculation to regular and tiered rule handlers.
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend rule engine for wholesale_pricing rules.
 */
class Wholesale_Pricing_Rule_Engine {

	/**
	 * Active wholesale-pricing rules for the current request.
	 *
	 * @var array
	 */
	private $valid_rules = array();

	/**
	 * Active cart discount rules for the current request.
	 *
	 * @var array
	 */
	private $valid_cart_discount_rules = array();

	/**
	 * Active BOGO discount rules for the current request.
	 *
	 * @var array
	 */
	private $valid_bogo_discount_rules = array();

	/**
	 * Active Buy X Get Y discount rules for the current request.
	 *
	 * @var array
	 */
	private $valid_bxgy_discount_rules = array();

	/**
	 * Regular discount handler.
	 *
	 * @var Wholesale_Pricing_Regular_Discount
	 */
	private $regular_discount;

	/**
	 * Product discount handler.
	 *
	 * @var Wholesale_Pricing_Product_Discount
	 */
	private $product_discount;

	/**
	 * Tiered discount handler.
	 *
	 * @var Wholesale_Pricing_Tiered_Discount
	 */
	private $tiered_discount;

	/**
	 * Cart discount handler.
	 *
	 * @var Wholesale_Pricing_Cart_Discount
	 */
	private $cart_discount;

	/**
	 * BOGO discount handler.
	 *
	 * @var Wholesale_Pricing_Bogo_Discount
	 */
	private $bogo_discount;

	/**
	 * Buy X Get Y discount handler.
	 *
	 * @var Wholesale_Pricing_Bxgy_Discount
	 */
	private $bxgy_discount;

	/**
	 * Cache calculated prices by product/quantity to avoid repeated work.
	 *
	 * @var array
	 */
	private $price_cache = array();

	/**
	 * Whether runtime rules have been loaded for this request.
	 *
	 * @var bool
	 */
	private $rules_loaded = false;

	/**
	 * Product IDs whose cart tier price has been set on the product object.
	 * The filter_price hook defers to set_price() for these to avoid
	 * overwriting the tier-adjusted value during calculate_totals().
	 *
	 * @var array<int, float>
	 */
	private $cart_tier_prices = array();

	/**
	 * Per-request wholesale base prices used as the tier calculation base.
	 * Keyed by product ID. Populated from the live product price (set by
	 * Dynamic Rules) on the first woocommerce_before_calculate_totals call
	 * and reused on subsequent same-request recalculations to prevent
	 * compounding. Never stored in WC session so stale values cannot
	 * survive across page loads when rules change.
	 *
	 * @var array<int, float>
	 */
	private $cart_tier_base_prices = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->regular_discount = new Wholesale_Pricing_Regular_Discount();
		$this->product_discount = new Wholesale_Pricing_Product_Discount();
		$this->tiered_discount  = new Wholesale_Pricing_Tiered_Discount();
		$this->cart_discount    = new Wholesale_Pricing_Cart_Discount();
		$this->bogo_discount    = new Wholesale_Pricing_Bogo_Discount();
		$this->bxgy_discount    = new Wholesale_Pricing_Bxgy_Discount();

		add_action( 'wp_loaded', array( $this, 'load_valid_rules' ) );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'update_cart_item_prices' ), 999 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'sync_bxgy_free_cart_items' ), 1000 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_bxgy_free_cart_item_prices' ), 1001 );
		add_filter( 'wholesalex_wholesale_pricing_tier_result', array( $this, 'provide_priority_tier_result' ), 10, 6 );
	}

	/**
	 * Supply Wholesale Pricing tiers to the shared legacy priority resolver.
	 *
	 * @param array $result Existing priority result.
	 * @param int   $product_id Product or variation ID.
	 * @param int   $parent_id Parent product ID.
	 * @param float $base_price Tier calculation base price.
	 * @param int   $quantity Current cart quantity.
	 * @param bool  $first_tier Whether inactive tiers should be exposed for display.
	 * @return array
	 */
	public function provide_priority_tier_result( $result, $product_id, $parent_id, $base_price, $quantity, $first_tier = false ): array {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return is_array( $result ) ? $result : array();
		}

		$tier_data = $this->get_tier_price_data( $product, max( 0, absint( $quantity ) ), (float) $base_price );

		if ( false === $tier_data ) {
			return is_array( $result ) ? $result : array();
		}

		return array(
			'src'               => 'wholesale_pricing_tier',
			'price'             => $tier_data['price'],
			'tiers'             => $tier_data['tiers'],
			'id'                => ! empty( $tier_data['tier']['_id'] ) ? $tier_data['tier']['_id'] : '',
			'base_price'        => $tier_data['base_price'],
			'wholesale_rule_id' => $tier_data['rule']['id'],
		);
	}

	/**
	 * Build the valid-rule list and register WooCommerce hooks.
	 *
	 * @return void
	 */
	public function load_valid_rules(): void {
		if ( $this->rules_loaded ) {
			return;
		}

		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$this->rules_loaded              = true;
		$this->valid_rules               = array();
		$this->valid_cart_discount_rules = array();
		$this->valid_bogo_discount_rules = array();
		$this->valid_bxgy_discount_rules = array();

		$user_id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		$role_id = wholesalex()->get_user_role( $user_id );

		$rules = Wholesale_Pricing::get_all_rules();

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}

			if ( ! $this->is_pro_feature_available() && $this->uses_pro_product_targeting( $rule ) ) {
				continue;
			}

			if ( 'cart_discount' === ( $rule['rule_type'] ?? '' ) ) {
				if ( ! $this->is_cart_discount_rule_available_for_user( $rule, $role_id, $user_id ) ) {
					continue;
				}

				$this->valid_cart_discount_rules[] = $this->normalize_cart_discount_rule_for_runtime( $rule, $role_id );
				continue;
			}

			if ( 'bogo_discount' === ( $rule['rule_type'] ?? '' ) ) {
				if ( ! $this->is_bogo_discount_rule_available_for_user( $rule, $role_id, $user_id ) ) {
					continue;
				}

				$this->valid_bogo_discount_rules[] = $this->normalize_bogo_discount_rule_for_runtime( $rule, $role_id );
				continue;
			}

			if ( 'buy_x_get_y' === ( $rule['rule_type'] ?? '' ) ) {
				if ( ! $this->is_pro_feature_available() ) {
					continue;
				}

				if ( ! $this->is_bxgy_discount_rule_available_for_user( $rule, $role_id, $user_id ) ) {
					continue;
				}

				$this->valid_bxgy_discount_rules[] = $this->normalize_bxgy_discount_rule_for_runtime( $rule, $role_id );
				continue;
			}

			if ( ! $this->is_rule_available_for_user( $rule, $role_id, $user_id ) ) {
				continue;
			}

			$this->valid_rules[] = $this->normalize_rule_for_runtime( $rule, $role_id );
		}

		usort( $this->valid_rules, array( $this, 'compare_by_priority' ) );
		usort( $this->valid_cart_discount_rules, array( $this, 'compare_by_priority' ) );
		usort( $this->valid_bogo_discount_rules, array( $this, 'compare_by_priority' ) );
		usort( $this->valid_bxgy_discount_rules, array( $this, 'compare_by_priority' ) );

		if ( ! empty( $this->valid_rules ) ) {
			$this->register_pricing_hooks();
			$this->register_restriction_hooks();
			$this->register_tier_table_hooks();
		}

		if ( ! empty( $this->valid_cart_discount_rules ) ) {
			$this->register_cart_discount_hooks();
		}

		if ( ! empty( $this->valid_bogo_discount_rules ) ) {
			$this->register_bogo_discount_hooks();
		}

		if ( ! empty( $this->valid_bxgy_discount_rules ) ) {
			$this->register_bxgy_discount_hooks();
		}
	}

	/**
	 * Register price hooks used by product, variation, and cart totals.
	 *
	 * @return void
	 */
	private function register_pricing_hooks(): void {
		add_filter( 'woocommerce_product_get_price', array( $this, 'filter_price' ), 8, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'filter_price' ), 8, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( $this, 'filter_sale_price' ), 8, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'filter_sale_price' ), 8, 2 );
		add_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'filter_sale_price' ), 8, 2 );
		add_filter( 'woocommerce_variation_prices_price', array( $this, 'filter_variation_price' ), 8, 2 );
		add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'variation_price_hash' ), 8, 3 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_price_html' ), 20, 2 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'filter_cart_item_price_html' ), 99, 2 );
	}

	/**
	 * Apply wholesale pricing to the active WooCommerce product price.
	 *
	 * WooCommerce uses get_price() for catalog/single-product price HTML and
	 * get_sale_price() for sale metadata. Both must be filtered for the new
	 * wholesale-pricing rule type to behave like native product pricing.
	 *
	 * @param string|float $price   Current product price.
	 * @param \WC_Product  $product Product object.
	 * @return string|float
	 */
	public function filter_price( $price, $product ) {
		if ( ! $product instanceof \WC_Product || apply_filters( 'wholesalex_ignore_wholesale_pricing', false, $product, 'price' ) ) {
			return $price;
		}

		$active_tier_data = apply_filters( 'wholesalex_active_tier_data', array(), $product );
		if ( ! empty( $active_tier_data['src'] ) && 'wholesale_pricing_tier' !== $active_tier_data['src'] ) {
			return $price;
		}

		// During cart total calculation the tier engine has already called
		// set_price() with the correct quantity-based tier price. Do not
		// overwrite it with a quantity-agnostic cached value.
		if ( isset( $this->cart_tier_prices[ $product->get_id() ] ) ) {
			return $this->cart_tier_prices[ $product->get_id() ];
		}

		$price_data = $this->get_product_price_data( $product );

		return false === $price_data ? $price : $price_data['price'];
	}

	/**
	 * Register quantity/value restriction hooks.
	 *
	 * @return void
	 */
	private function register_restriction_hooks(): void {
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'filter_quantity_input_args' ), 10, 2 );
		add_filter( 'woocommerce_available_variation', array( $this, 'filter_variation_quantity_limits' ), 10, 3 );
		add_filter( 'woocommerce_loop_add_to_cart_args', array( $this, 'filter_loop_add_to_cart_args' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart_quantity' ), 10, 5 );
		add_filter( 'woocommerce_update_cart_validation', array( $this, 'validate_cart_update_quantity' ), 10, 4 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'validate_cart_items' ) );
	}

	/**
	 * Register single-product tier table hooks.
	 *
	 * @return void
	 */
	private function register_tier_table_hooks(): void {
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_simple_product_tier_table' ), 10 );
		add_filter( 'woocommerce_available_variation', array( $this, 'append_variation_tier_table' ), 20, 3 );
	}

	/**
	 * Register cart discount hooks for cart and checkout totals.
	 *
	 * @return void
	 */
	private function register_cart_discount_hooks(): void {
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_cart_discounts' ), 20 );

		if ( $this->cart_discount->has_promo_rules( $this->valid_cart_discount_rules ) ) {
			add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_simple_product_cart_discount_promo' ), 10 );
			add_filter( 'woocommerce_available_variation', array( $this, 'append_variation_cart_discount_promo' ), 20, 3 );
		}
	}

	/**
	 * Register BOGO discount hooks for fees, promos, and badges.
	 *
	 * @return void
	 */
	private function register_bogo_discount_hooks(): void {
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_bogo_discounts' ), 20 );

		if ( $this->bogo_discount->has_promo_rules( $this->valid_bogo_discount_rules ) ) {
			add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_simple_product_bogo_discount_promo' ), 10 );
			add_filter( 'woocommerce_available_variation', array( $this, 'append_variation_bogo_discount_promo' ), 20, 3 );
		}

		if ( $this->bogo_discount->has_badge_rules( $this->valid_bogo_discount_rules ) ) {
			add_filter( 'wopb_after_loop_image', array( $this, 'wopb_render_bogo_discount_badge' ), 10 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_bogo_discount_badge' ), 10 );
			add_action( 'wholesalex_after_frontend_enqueue_scripts', array( $this, 'enqueue_single_product_bogo_discount_badge' ), 10 );
			add_action( 'wp_head', array( $this, 'add_bogo_discount_badge_css' ), 7 );
		}
	}

	/**
	 * Register Buy X Get Y promo and badge hooks.
	 *
	 * @return void
	 */
	private function register_bxgy_discount_hooks(): void {
		if ( $this->bxgy_discount->has_promo_rules( $this->valid_bxgy_discount_rules ) ) {
			add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_simple_product_bxgy_discount_promo' ), 10 );
			add_filter( 'woocommerce_available_variation', array( $this, 'append_variation_bxgy_discount_promo' ), 20, 3 );
		}

		if ( $this->bxgy_discount->has_badge_rules( $this->valid_bxgy_discount_rules ) ) {
			add_filter( 'wopb_after_loop_image', array( $this, 'wopb_render_bxgy_discount_badge' ), 10 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_bxgy_discount_badge' ), 10 );
			add_action( 'wholesalex_after_frontend_enqueue_scripts', array( $this, 'enqueue_single_product_bxgy_discount_badge' ), 10 );
			add_action( 'wp_head', array( $this, 'add_bxgy_discount_badge_css' ), 7 );
		}
	}

	/**
	 * Render wholesale-pricing cart discount promo popup on simple product pages.
	 *
	 * @return void
	 */
	public function render_simple_product_cart_discount_promo(): void {
		global $product;

		if ( ! $product instanceof \WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		$this->load_valid_rules();

		if ( empty( $this->valid_cart_discount_rules ) ) {
			return;
		}

		$this->cart_discount->render_promo_html( $product, $this->valid_cart_discount_rules );
	}

	/**
	 * Append wholesale-pricing cart discount promo popup to variation availability.
	 *
	 * @param array       $variation_array Variation data.
	 * @param \WC_Product $product         Parent variable product.
	 * @param \WC_Product $variation       Variation product.
	 * @return array
	 */
	public function append_variation_cart_discount_promo( $variation_array, $product, $variation ): array {
		if ( ! $variation instanceof \WC_Product ) {
			return $variation_array;
		}

		$this->load_valid_rules();

		if ( empty( $this->valid_cart_discount_rules ) ) {
			return $variation_array;
		}

		$variation_array['availability_html'] = ( $variation_array['availability_html'] ?? '' ) . $this->cart_discount->get_promo_html( $variation, $this->valid_cart_discount_rules );

		return $variation_array;
	}

	/**
	 * Render wholesale-pricing BOGO promo popup on simple product pages.
	 *
	 * @return void
	 */
	public function render_simple_product_bogo_discount_promo(): void {
		global $product;

		if ( ! $product instanceof \WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		$this->load_valid_rules();

		if ( empty( $this->valid_bogo_discount_rules ) ) {
			return;
		}

		$this->bogo_discount->render_promo_html( $product, $this->valid_bogo_discount_rules );
	}

	/**
	 * Append wholesale-pricing BOGO promo popup to variation availability.
	 *
	 * @param array       $variation_array Variation data.
	 * @param \WC_Product $product         Parent variable product.
	 * @param \WC_Product $variation       Variation product.
	 * @return array
	 */
	public function append_variation_bogo_discount_promo( $variation_array, $product, $variation ): array {
		if ( ! $variation instanceof \WC_Product ) {
			return $variation_array;
		}

		$this->load_valid_rules();

		if ( empty( $this->valid_bogo_discount_rules ) ) {
			return $variation_array;
		}

		$variation_array['availability_html'] = ( $variation_array['availability_html'] ?? '' ) . $this->bogo_discount->get_promo_html( $variation, $this->valid_bogo_discount_rules );

		return $variation_array;
	}

	/**
	 * Render wholesale-pricing BOGO badges on shop/archive product cards.
	 *
	 * @return void
	 */
	public function render_bogo_discount_badge(): void {
		echo $this->bogo_discount->get_badge_markup( $this->valid_bogo_discount_rules, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render wholesale-pricing BOGO badges for WowStore loop images.
	 *
	 * @return string
	 */
	public function wopb_render_bogo_discount_badge(): string {
		return $this->bogo_discount->get_badge_markup( $this->valid_bogo_discount_rules, false );
	}

	/**
	 * Queue single-product BOGO badge insertion.
	 *
	 * @return void
	 */
	public function enqueue_single_product_bogo_discount_badge(): void {
		if ( ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$this->bogo_discount->enqueue_single_badge_script( $this->valid_bogo_discount_rules, $product );
	}

	/**
	 * Add per-rule BOGO badge CSS.
	 *
	 * @return void
	 */
	public function add_bogo_discount_badge_css(): void {
		if ( ! is_shop() && ! is_product() && ! is_product_category() && ! is_product_tag() ) {
			return;
		}

		$css = $this->bogo_discount->get_badge_css( $this->valid_bogo_discount_rules );

		if ( '' !== trim( $css ) ) {
			wp_add_inline_style( 'wholesalex', $css );
		}
	}

	/**
	 * Render wholesale-pricing Buy X Get Y free-product promo on simple product pages.
	 *
	 * @return void
	 */
	public function render_simple_product_bxgy_discount_promo(): void {
		global $product;

		if ( ! $product instanceof \WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		$this->load_valid_rules();

		if ( empty( $this->valid_bxgy_discount_rules ) ) {
			return;
		}

		$this->bxgy_discount->render_promo_html( $product, $this->valid_bxgy_discount_rules );
	}

	/**
	 * Append wholesale-pricing Buy X Get Y promo to variation availability.
	 *
	 * @param array       $variation_array Variation data.
	 * @param \WC_Product $product         Parent variable product.
	 * @param \WC_Product $variation       Variation product.
	 * @return array
	 */
	public function append_variation_bxgy_discount_promo( $variation_array, $product, $variation ): array {
		if ( ! $variation instanceof \WC_Product ) {
			return $variation_array;
		}

		$this->load_valid_rules();

		if ( empty( $this->valid_bxgy_discount_rules ) ) {
			return $variation_array;
		}

		$variation_array['availability_html'] = ( $variation_array['availability_html'] ?? '' ) . $this->bxgy_discount->get_promo_html( $variation, $this->valid_bxgy_discount_rules );

		return $variation_array;
	}

	/**
	 * Render wholesale-pricing Buy X Get Y badges on shop/archive product cards.
	 *
	 * @return void
	 */
	public function render_bxgy_discount_badge(): void {
		echo $this->bxgy_discount->get_badge_markup( $this->valid_bxgy_discount_rules, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render wholesale-pricing Buy X Get Y badges for WowStore loop images.
	 *
	 * @return string
	 */
	public function wopb_render_bxgy_discount_badge(): string {
		return $this->bxgy_discount->get_badge_markup( $this->valid_bxgy_discount_rules, false );
	}

	/**
	 * Queue single-product Buy X Get Y badge insertion.
	 *
	 * @return void
	 */
	public function enqueue_single_product_bxgy_discount_badge(): void {
		if ( ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_queried_object_id() );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$this->bxgy_discount->enqueue_single_badge_script( $this->valid_bxgy_discount_rules, $product );
	}

	/**
	 * Add per-rule Buy X Get Y badge CSS.
	 *
	 * @return void
	 */
	public function add_bxgy_discount_badge_css(): void {
		if ( ! is_shop() && ! is_product() && ! is_product_category() && ! is_product_tag() ) {
			return;
		}

		$css = $this->bxgy_discount->get_badge_css( $this->valid_bxgy_discount_rules );

		if ( '' !== trim( $css ) ) {
			wp_add_inline_style( 'wholesalex', $css );
		}
	}

	/**
	 * Sync Buy X Get Y free cart lines when cart totals are calculated.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function sync_bxgy_free_cart_items( $cart ): void {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}

		$this->load_valid_rules();

		$this->bxgy_discount->sync_free_cart_items( $cart, $this->valid_bxgy_discount_rules );
	}

	/**
	 * Keep Buy X Get Y free cart lines at zero price.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function set_bxgy_free_cart_item_prices( $cart ): void {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$this->bxgy_discount->set_free_cart_item_prices( $cart );
	}

	/**
	 * Apply wholesale pricing to product sale price.
	 *
	 * @param string|float $sale_price Current sale price.
	 * @param \WC_Product  $product    Product object.
	 * @return string|float
	 */
	public function filter_sale_price( $sale_price, $product ) {
		if ( ! $product instanceof \WC_Product || apply_filters( 'wholesalex_ignore_wholesale_pricing', false, $product, 'sale_price' ) ) {
			return $sale_price;
		}

		$active_tier_data = apply_filters( 'wholesalex_active_tier_data', array(), $product );
		if ( ! empty( $active_tier_data['src'] ) && 'wholesale_pricing_tier' !== $active_tier_data['src'] ) {
			return $sale_price;
		}

		if ( isset( $this->cart_tier_prices[ $product->get_id() ] ) ) {
			return $this->cart_tier_prices[ $product->get_id() ];
		}

		$price_data = $this->get_product_price_data( $product );

		return false === $price_data ? $sale_price : $price_data['price'];
	}

	/**
	 * Apply wholesale pricing to variation price arrays.
	 *
	 * @param string|float $price   Current price.
	 * @param \WC_Product  $product Product object.
	 * @return string|float
	 */
	public function filter_variation_price( $price, $product ) {
		if ( ! $product instanceof \WC_Product || apply_filters( 'wholesalex_ignore_wholesale_pricing', false, $product, 'variation_price' ) ) {
			return $price;
		}

		$active_tier_data = apply_filters( 'wholesalex_active_tier_data', array(), $product );
		if ( ! empty( $active_tier_data['src'] ) && 'wholesale_pricing_tier' !== $active_tier_data['src'] ) {
			return $price;
		}

		if ( isset( $this->cart_tier_prices[ $product->get_id() ] ) ) {
			return $this->cart_tier_prices[ $product->get_id() ];
		}

		$price_data = $this->get_product_price_data( $product );

		return false === $price_data ? $price : $price_data['price'];
	}

	/**
	 * Render wholesale-pricing price HTML.
	 *
	 * Regular discounts use the rule label as the visible wholesale-price text.
	 * Tiered discounts keep the compact sale/regular format used by the existing
	 * tiered-pricing design.
	 *
	 * @param string      $price_html Existing price HTML.
	 * @param \WC_Product $product    Product object.
	 * @return string
	 */
	public function filter_price_html( $price_html, $product ): string {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return $price_html;
		}

		if ( ! $product instanceof \WC_Product || apply_filters( 'wholesalex_ignore_wholesale_pricing', false, $product, 'price_html' ) ) {
			return $price_html;
		}

		$active_tier_data = apply_filters( 'wholesalex_active_tier_data', array(), $product );
		if ( ! empty( $active_tier_data['src'] ) && 'wholesale_pricing_tier' !== $active_tier_data['src'] ) {
			return $price_html;
		}

		if ( $product->is_type( 'variable' ) ) {
			$range_data = $this->get_variable_price_range_data( $product );

			if ( false !== $range_data ) {
				return $this->format_variable_price_range_html( $product, $range_data );
			}

			return $price_html;
		}

		$price_data = $this->get_product_price_data( $product );

		if ( false === $price_data || empty( $price_data['rule'] ) ) {
			return $price_html;
		}

		$base_price      = $this->get_base_price( $product );
		$wholesale_price = (float) $price_data['price'];

		if ( $base_price <= 0 || $wholesale_price <= 0 || $wholesale_price >= $base_price ) {
			return $price_html;
		}

		$base_html      = wc_price( wc_get_price_to_display( $product, array( 'price' => $base_price ) ) );
		$wholesale_html = wc_price( wc_get_price_to_display( $product, array( 'price' => $wholesale_price ) ) );
		$rule           = $price_data['rule'];

		if ( 'regular' === $rule['discount_type'] ) {
			$label = $this->get_discount_label( $rule );
			$label = '' !== $label ? '<span class="wsx-wholesale-price-label">' . esc_html( $label ) . '</span>' : '';
			$price_visibility_html = $this->get_price_visibility_html(
				$base_html,
				$this->get_wholesale_price_markup( $label, $wholesale_html, $this->get_price_suffix_html( $product, 'wholesale' ) ),
				$product
			);

			if ( '' !== $price_visibility_html ) {
				return $price_visibility_html;
			}

			return '<del class="wsx-wholesale-regular-price" aria-hidden="true">' .
				wp_kses_post( $base_html ) .
				'</del> <ins class="wsx-wholesale-price-wrap">' .
				$this->get_wholesale_price_markup( $label, $wholesale_html, $this->get_price_suffix_html( $product, 'wholesale' ) ) .
				'</ins>';
		}

		$price_visibility_html = $this->get_price_visibility_html(
			$base_html,
			$this->get_wholesale_price_markup( '', $wholesale_html, $this->get_price_suffix_html( $product, 'wholesale' ) ),
			$product
		);

		if ( '' !== $price_visibility_html ) {
			return $price_visibility_html;
		}

		return '<ins class="wsx-wholesale-price-wrap">' .
			$this->get_wholesale_price_markup( '', $wholesale_html, $this->get_price_suffix_html( $product, 'wholesale' ) ) .
			'</ins> <del class="wsx-wholesale-regular-price" aria-hidden="true">' .
			wp_kses_post( $base_html ) .
			'</del>';
	}

	/**
	 * Calculate wholesale min/max prices for a variable product parent.
	 *
	 * @param \WC_Product $product Variable product.
	 * @return array|false
	 */
	private function get_variable_price_range_data( \WC_Product $product ) {
		$variation_ids = $product->get_children();

		if ( empty( $variation_ids ) ) {
			return false;
		}

		$wholesale_prices = array();
		$base_prices      = array();
		$applied_rule     = false;

		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation instanceof \WC_Product || ! $variation->exists() ) {
				continue;
			}

			$price_data = $this->get_product_price_data( $variation );

			if ( false === $price_data ) {
				continue;
			}

			$base_price       = $this->get_base_price( $variation );
			$wholesale_price  = (float) $price_data['price'];
			$variation_active = $variation->is_purchasable() && ( ! method_exists( $variation, 'variation_is_visible' ) || $variation->variation_is_visible() );

			if ( ! $variation_active || $base_price <= 0 || $wholesale_price <= 0 || $wholesale_price >= $base_price ) {
				continue;
			}

			$base_prices[]      = wc_get_price_to_display( $variation, array( 'price' => $base_price ) );
			$wholesale_prices[] = wc_get_price_to_display( $variation, array( 'price' => $wholesale_price ) );
			$applied_rule       = false === $applied_rule ? $price_data['rule'] : $applied_rule;
		}

		if ( empty( $wholesale_prices ) ) {
			return false;
		}

		return array(
			'min'      => min( $wholesale_prices ),
			'max'      => max( $wholesale_prices ),
			'base_min' => min( $base_prices ),
			'base_max' => max( $base_prices ),
			'rule'     => $applied_rule,
		);
	}

	/**
	 * Format the initial variable-product wholesale price range.
	 *
	 * @param \WC_Product $product    Variable product.
	 * @param array       $range_data Range data from get_variable_price_range_data().
	 * @return string
	 */
	private function format_variable_price_range_html( \WC_Product $product, array $range_data ): string {
		$min = (float) $range_data['min'];
		$max = (float) $range_data['max'];

		if ( $min === $max ) {
			$wholesale_html = wc_price( $min );
		} else {
			$wholesale_html = wc_format_price_range( $min, $max );
		}

		$base_min = (float) $range_data['base_min'];
		$base_max = (float) $range_data['base_max'];
		$base_html = $base_min === $base_max ? wc_price( $base_min ) : wc_format_price_range( $base_min, $base_max );
		$rule      = isset( $range_data['rule'] ) && is_array( $range_data['rule'] ) ? $range_data['rule'] : array();

		if ( isset( $rule['discount_type'] ) && 'regular' === $rule['discount_type'] ) {
			$label = $this->get_discount_label( $rule );
			$label = '' !== $label ? '<span class="wsx-wholesale-price-label">' . esc_html( $label ) . '</span>' : '';
			$price_visibility_html = $this->get_price_visibility_html(
				$base_html,
				$this->get_wholesale_price_markup( $label, $wholesale_html, $this->get_price_suffix_html( $product, 'wholesale' ) ),
				$product
			);

			if ( '' !== $price_visibility_html ) {
				return $price_visibility_html;
			}

			return '<del class="wsx-wholesale-regular-price" aria-hidden="true">' .
				wp_kses_post( $base_html ) .
				'</del> <ins class="wsx-wholesale-price-wrap">' .
				$this->get_wholesale_price_markup( $label, $wholesale_html, $this->get_price_suffix_html( $product, 'wholesale' ) ) .
				'</ins>';
		}

		$price_visibility_html = $this->get_price_visibility_html(
			$base_html,
			$this->get_wholesale_price_markup( '', $wholesale_html, $this->get_price_suffix_html( $product, 'wholesale' ) ),
			$product
		);

		if ( '' !== $price_visibility_html ) {
			return $price_visibility_html;
		}

		return '<ins class="wsx-wholesale-price-wrap">' .
			$this->get_wholesale_price_markup( '', $wholesale_html, $this->get_price_suffix_html( $product, 'wholesale' ) ) .
			'</ins> <del class="wsx-wholesale-regular-price" aria-hidden="true">' .
			wp_kses_post( $base_html ) .
			'</del>';
	}

	private function get_wholesale_price_markup( string $label_html, string $price_html, string $suffix_html = '' ): string {
		return '<span class="wsx-wholesale-price">' .
			wp_kses_post( $label_html ) .
			'<span class="wsx-wholesale-price-amount">' .
			wp_kses_post( $price_html ) .
			wp_kses_post( $suffix_html ) .
			'</span></span>';
	}

	/**
	 * Apply global price visibility settings to wholesale-pricing display HTML.
	 *
	 * These settings control displayed price parts only. They do not disable
	 * wholesale-pricing calculations or product purchasability.
	 *
	 * @param string      $base_html      Retail/base price HTML.
	 * @param string      $wholesale_html Wholesale price HTML.
	 * @param \WC_Product $product        Product object.
	 * @return string
	 */
	private function get_price_visibility_html( string $base_html, string $wholesale_html, \WC_Product $product ): string {
		$hide_retail_price    = 'yes' === (string) wholesalex()->get_setting( '_settings_hide_retail_price' );
		$hide_wholesale_price = 'yes' === (string) wholesalex()->get_setting( '_settings_hide_wholesalex_price' );

		if ( $hide_retail_price ) {
			return '<ins class="wsx-wholesale-price-wrap">' .
				wp_kses_post( $wholesale_html ) .
				'</ins>';
		}

		if ( $hide_wholesale_price ) {
			return wp_kses_post( $base_html ) .
				$this->get_price_suffix_html( $product, 'regular' );
		}

		return '';
	}

	/**
	 * Get the configured WholesaleX price suffix for custom wholesale-pricing HTML.
	 *
	 * @param \WC_Product $product Product object.
	 * @param string      $price_type Price type. Accepts wholesale|regular.
	 * @return string
	 */
	private function get_price_suffix_html( \WC_Product $product, string $price_type = 'wholesale' ): string {
		$setting_key = 'regular' === $price_type
			? '_settings_regular_price_suffix'
			: '_settings_wholesalex_price_suffix';
		$suffix      = wholesalex()->get_setting( $setting_key );

		if ( empty( $suffix ) ) {
			return '';
		}

		if ( '{price_including_tax}' === $suffix ) {
			$suffix = wc_price( wc_get_price_including_tax( $product ) );
		} elseif ( '{price_excluding_tax}' === $suffix ) {
			$suffix = wc_price( wc_get_price_excluding_tax( $product ) );
		}

		return '<small class="woocommerce-price-suffix">' . wp_kses_post( $suffix ) . '</small>';
	}

	/**
	 * Keep WooCommerce variation price cache unique per stable pricing context.
	 *
	 * @param array       $hash    Price hash.
	 * @param \WC_Product $product Product object.
	 * @param bool        $for_display Whether prices are being loaded for display.
	 * @return array
	 */
	public function variation_price_hash( $hash, $product, $for_display = false ): array {
		$user_id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		$context = array(
			'user_id'     => (string) $user_id,
			'role_id'     => (string) wholesalex()->get_user_role( $user_id ),
			'rules'       => md5( wp_json_encode( $this->valid_rules ) ),
			'cart'        => $this->get_variation_price_cart_hash(),
			'currency'    => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'woocommerce_currency' ),
			'tax_display' => get_option( 'woocommerce_tax_display_shop' ),
			'for_display' => (bool) $for_display,
		);
		$hash[] = 'wsx_wholesale_pricing_' . md5( wp_json_encode( $context ) );
		return $hash;
	}

	/**
	 * Return a stable cart fingerprint for cart-sensitive variation pricing.
	 *
	 * WooCommerce stores all variation price hash variants inside one product
	 * transient, so this must change only when cart values that can affect
	 * wholesale pricing change.
	 *
	 * @return string
	 */
	private function get_variation_price_cart_hash(): string {
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
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
	 * Set cart item product prices before WooCommerce calculates totals.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function update_cart_item_prices( $cart ): void {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}

		$this->load_valid_rules();

		if ( empty( $this->valid_rules ) ) {
			return;
		}

		// Flush the price cache so quantity-aware tier prices are recalculated
		// from cart quantities rather than returning stale catalog-display values.
		$this->price_cache      = array();
		$this->cart_tier_prices = array();

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
				continue;
			}

			$active_tier_data = apply_filters( 'wholesalex_active_tier_data', array(), $cart_item['data'] );
			if ( ! empty( $active_tier_data['src'] ) && 'wholesale_pricing_tier' !== $active_tier_data['src'] ) {
				continue;
			}

			// Remove any legacy session value so stale base prices from previous
			// requests can never influence tier calculation.
			if ( isset( $cart->cart_contents[ $cart_item_key ]['_wsx_wholesale_pricing_tier_base_price'] ) ) {
				unset( $cart->cart_contents[ $cart_item_key ]['_wsx_wholesale_pricing_tier_base_price'] );
			}

			// The shared priority resolver has already calculated this source's
			// quantity-aware price from its stable pre-tier base. Recalculating from
			// the mutated cart product would compound the tier on every pricing pass
			// (for example 8.64 -> 6.91 -> 5.53 for a 20% tier).
			if (
				'wholesale_pricing_tier' === ( $active_tier_data['src'] ?? '' ) &&
				array_key_exists( 'price', $active_tier_data ) &&
				false !== $active_tier_data['price'] &&
				is_numeric( $active_tier_data['price'] )
			) {
				$resolved_tier_price = max( 0, (float) $active_tier_data['price'] );
				$cart_item['data']->set_price( $resolved_tier_price );
				$this->cart_tier_prices[ $cart_item['data']->get_id() ] = $resolved_tier_price;
				$cart->cart_contents[ $cart_item_key ]['_wsx_wholesale_pricing_tier_applied'] = true;
				$this->set_discounted_product( $cart_item['data']->get_id() );
				continue;
			}

			$tier_base_price = $this->get_cart_item_tier_base_price( $cart_item );
			$price_data      = $this->get_product_price_data( $cart_item['data'], absint( $cart_item['quantity'] ), $tier_base_price );

			if ( false !== $price_data ) {
				if ( null !== $tier_base_price ) {
					// Store in the request-scoped property only — never in WC
					// session, so stale base prices cannot persist across page
					// loads when rules are updated.
					$this->cart_tier_base_prices[ $cart_item['data']->get_id() ] = $tier_base_price;
				}

				$cart_item['data']->set_price( $price_data['price'] );

				// Remember the tier-adjusted price so filter_price() does not
				// overwrite it with the quantity-agnostic cached value when
				// WooCommerce reads get_price() during calculate_totals().
				$this->cart_tier_prices[ $cart_item['data']->get_id() ] = (float) $price_data['price'];

				if ( ! empty( $price_data['tier'] ) ) {
					$cart->cart_contents[ $cart_item_key ]['_wsx_wholesale_pricing_tier_applied'] = true;
				} else {
					unset( $cart->cart_contents[ $cart_item_key ]['_wsx_wholesale_pricing_tier_applied'] );
				}

				$this->set_discounted_product( $cart_item['data']->get_id() );
			}
		}
	}

	/**
	 * Display the final cart-item unit price after wholesale-pricing tiers run.
	 *
	 * @param string $price_html Existing cart item price HTML.
	 * @param array  $cart_item  WooCommerce cart item.
	 * @return string
	 */
	public function filter_cart_item_price_html( $price_html, $cart_item ): string {
		if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
			return $price_html;
		}

		if ( empty( $cart_item['_wsx_wholesale_pricing_tier_applied'] ) ) {
			return $price_html;
		}

		return wc_price(
			wc_get_price_to_display(
				$cart_item['data'],
				array(
					'price' => $cart_item['data']->get_price( 'edit' ),
				)
			)
		);
	}

	/**
	 * Apply eligible cart discount rules as WooCommerce negative fees.
	 *
	 * WooCommerce renders fee names in both cart and checkout totals, so the
	 * saved cart-discount label becomes the customer-facing discount label.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function apply_cart_discounts( $cart ): void {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}

		$this->load_valid_rules();

		if ( empty( $this->valid_cart_discount_rules ) ) {
			return;
		}

		$fees = $this->cart_discount->calculate( $this->valid_cart_discount_rules );

		if ( empty( $fees ) ) {
			return;
		}

		$fee_names = array();

		foreach ( $fees as $fee ) {
			if ( empty( $fee['discount'] ) || empty( $fee['name'] ) ) {
				continue;
			}

			$fee_name = $fee['name'];

			if ( isset( $fee_names[ $fee_name ] ) ) {
				$fee_name = wp_unique_id( $fee_name );
			}

			$fee_names[ $fee_name ] = true;
			$cart->add_fee( $fee_name, -1 * (float) $fee['discount'], (bool) apply_filters( 'wholesalex_cart_discount_is_taxable', false, $fee ) );

			if ( ! empty( $fee['rule_id'] ) ) {
				$this->set_discounted_cart_rule( (string) $fee['rule_id'] );
			}
		}
	}

	/**
	 * Apply eligible BOGO rules as WooCommerce negative fees.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function apply_bogo_discounts( $cart ): void {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}

		$this->load_valid_rules();

		if ( empty( $this->valid_bogo_discount_rules ) ) {
			return;
		}

		$fees = $this->bogo_discount->calculate( $this->valid_bogo_discount_rules );

		if ( empty( $fees ) ) {
			return;
		}

		$fee_names = array();

		foreach ( $fees as $fee ) {
			if ( empty( $fee['discount'] ) || empty( $fee['name'] ) ) {
				continue;
			}

			$fee_name = $fee['name'];

			if ( isset( $fee_names[ $fee_name ] ) ) {
				$fee_name = wp_unique_id( $fee_name );
			}

			$fee_names[ $fee_name ] = true;
			$cart->add_fee( $fee_name, -1 * (float) $fee['discount'], (bool) apply_filters( 'wholesalex_wholesale_pricing_bogo_discount_is_taxable', false, $fee ) );

			if ( ! empty( $fee['rule_id'] ) ) {
				$this->set_discounted_bogo_rule( (string) $fee['rule_id'] );
			}
		}
	}

	/**
	 * Return the stable base price that cart tier discounts should use.
	 *
	 * Dynamic Rules runs just before this engine and may already set the cart
	 * product object price to a wholesale/product-discount value such as 3.60.
	 * Store that value as the tier base so a 20% tier becomes 2.88, while later
	 * cart recalculations do not compound from 2.88.
	 *
	 * @param array $cart_item WooCommerce cart item.
	 * @return float|null
	 */
	private function get_cart_item_tier_base_price( array $cart_item ): ?float {
		$product = $cart_item['data'] ?? null;

		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		// Use the request-scoped value if already captured this request.
		// This prevents compounding when WooCommerce recalculates totals
		// more than once within a single PHP request.
		if ( isset( $this->cart_tier_base_prices[ $product->get_id() ] ) ) {
			return $this->cart_tier_base_prices[ $product->get_id() ];
		}

		// On the first calculation of the request, Dynamic Rules (priority 5)
		// has already called set_price() with the current wholesale price.
		// Capture that as the tier base, but do not mistake WooCommerce's
		// native sale price for a wholesale base when discounts are configured
		// to apply on the regular price.
		$current_price = (float) $product->get_price( 'edit' );
		$base_price    = $this->get_base_price( $product );

		if ( $this->is_sale_price_base_for_regular_discount( $product, $current_price ) ) {
			return null;
		}

		if ( $current_price > 0 && $base_price > 0 && $current_price < $base_price ) {
			return $current_price;
		}

		return null;
	}

	/**
	 * Set product quantity min/max values on simple product forms.
	 *
	 * @param array       $args    Quantity input args.
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	public function filter_quantity_input_args( $args, $product ): array {
		if ( ! $product instanceof \WC_Product ) {
			return $args;
		}

		$restriction = $this->get_product_quantity_restriction( $product );

		if ( empty( $restriction ) ) {
			return $args;
		}

		if ( $restriction['min'] > 0 ) {
			$args['min_value'] = $restriction['min'];
		}
		if ( $restriction['max'] > 0 ) {
			$args['max_value'] = $restriction['max'];
		}
		if ( $restriction['step'] > 0 ) {
			$args['step'] = $restriction['step'];
		}

		return $args;
	}

	/**
	 * Set min/max values for variation quantity inputs.
	 *
	 * @param array       $variation_data Variation data.
	 * @param \WC_Product $product        Parent product.
	 * @param \WC_Product $variation      Variation product.
	 * @return array
	 */
	public function filter_variation_quantity_limits( $variation_data, $product, $variation ): array {
		if ( ! $variation instanceof \WC_Product ) {
			return $variation_data;
		}

		$restriction = $this->get_product_quantity_restriction( $variation );

		if ( empty( $restriction ) ) {
			return $variation_data;
		}

		if ( $restriction['min'] > 0 ) {
			$variation_data['min_qty'] = $restriction['min'];
		}
		if ( $restriction['max'] > 0 ) {
			$variation_data['max_qty'] = $restriction['max'];
		}
		if ( $restriction['step'] > 0 ) {
			$variation_data['step'] = $restriction['step'];
		}

		return $variation_data;
	}

	/**
	 * Use the minimum quantity as loop add-to-cart quantity where applicable.
	 *
	 * @param array       $args    Loop button args.
	 * @param \WC_Product $product Product object.
	 * @return array
	 */
	public function filter_loop_add_to_cart_args( $args, $product ): array {
		if ( ! $product instanceof \WC_Product || ! $product->is_type( 'simple' ) ) {
			return $args;
		}

		$restriction = $this->get_product_quantity_restriction( $product );

		if ( ! empty( $restriction['min'] ) ) {
			$args['quantity'] = $restriction['min'];
		}

		return $args;
	}

	/**
	 * Validate add-to-cart quantity and product-value restrictions.
	 *
	 * @param bool $passed       Current validation status.
	 * @param int  $product_id   Product ID.
	 * @param int  $quantity     Quantity being added.
	 * @param int  $variation_id Variation ID.
	 * @return bool
	 */
	public function validate_add_to_cart_quantity( $passed, $product_id, $quantity, $variation_id = 0 ) {
		if ( ! $passed ) {
			return $passed;
		}

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product ) {
			return $passed;
		}

		$requested_quantity = absint( $quantity );
		$restriction        = $this->get_product_quantity_restriction( $product );

		if ( ! empty( $restriction ) ) {
			$new_quantity = $this->get_cart_quantity( $product, $restriction['rule'] ) + $requested_quantity;
			$passed       = $this->validate_quantity_against_restriction( $passed, $product, $restriction, $new_quantity );
		}

		if ( ! $passed ) {
			return false;
		}

		$value_quantity    = $this->get_cart_quantity( $product, array( 'restrictions' => array() ) ) + $requested_quantity;
		$value_restriction = $this->get_product_value_restriction( $product, $value_quantity );

		if ( empty( $value_restriction ) ) {
			return $passed;
		}

		return $this->validate_value_against_restriction( $passed, $product, $value_restriction, $value_quantity );
	}

	/**
	 * Validate cart quantity update restrictions.
	 *
	 * @param bool   $passed        Current validation status.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $values        Cart item data.
	 * @param int    $quantity      New quantity.
	 * @return bool
	 */
	public function validate_cart_update_quantity( $passed, $cart_item_key, $values, $quantity ) {
		if ( ! $passed || empty( $values['data'] ) || ! $values['data'] instanceof \WC_Product ) {
			return $passed;
		}

		$requested_quantity = absint( $quantity );
		$restriction        = $this->get_product_quantity_restriction( $values['data'] );

		if ( ! empty( $restriction ) ) {
			$rule              = isset( $restriction['rule'] ) ? $restriction['rule'] : array();
			$updated_quantity  = $this->get_cart_quantity_for_update( $values['data'], $rule, $cart_item_key, $requested_quantity );
			$passed            = $this->validate_quantity_against_restriction( $passed, $values['data'], $restriction, $updated_quantity );
		}

		if ( ! $passed ) {
			return false;
		}

		$value_quantity    = $this->get_cart_quantity_for_update( $values['data'], array( 'restrictions' => array() ), $cart_item_key, $requested_quantity );
		$value_restriction = $this->get_product_value_restriction( $values['data'], $value_quantity );

		if ( empty( $value_restriction ) ) {
			return $passed;
		}

		return $this->validate_value_against_restriction( $passed, $values['data'], $value_restriction, $value_quantity );
	}

	/**
	 * Validate product-level quantity and order value restrictions before checkout.
	 *
	 * @return void
	 */
	public function validate_cart_items(): void {
		if ( ! WC()->cart ) {
			return;
		}

		$shown_notices = array();

		foreach ( $this->valid_rules as $rule ) {
			if ( ! $this->conditions_pass( $rule ) ) {
				continue;
			}

			if ( ! $this->rule_uses_quantity_limits( $rule ) && ! $this->rule_uses_value_limits( $rule ) ) {
				continue;
			}

			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
					continue;
				}

				$product = $cart_item['data'];

				if ( ! $this->is_product_eligible_for_rule( $product, $rule ) ) {
					continue;
				}

				$quantity = $this->get_cart_quantity( $product, $rule );

				if ( $this->rule_uses_value_limits( $rule ) ) {
					$this->maybe_add_amount_notice( $shown_notices, $rule, $product, $quantity );
				}

				if ( $this->rule_uses_quantity_limits( $rule ) ) {
					$this->maybe_add_quantity_notice( $shown_notices, $rule, $product, $quantity );
				}
			}
		}
	}

	/**
	 * Render the tier table for simple products.
	 *
	 * @return void
	 */
	public function render_simple_product_tier_table(): void {
		global $product;

		if ( ! $product instanceof \WC_Product || ! $product->is_type( 'simple' ) ) {
			return;
		}

		echo $this->get_tier_table_markup( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is generated internally and escaped at field level.
	}

	/**
	 * Append tier table markup to variation availability HTML.
	 *
	 * @param array       $variation_data Variation data.
	 * @param \WC_Product $product        Parent product.
	 * @param \WC_Product $variation      Variation product.
	 * @return array
	 */
	public function append_variation_tier_table( $variation_data, $product, $variation ): array {
		if ( $variation instanceof \WC_Product ) {
			$variation_data['availability_html'] .= $this->get_tier_table_markup( $variation );
		}

		return $variation_data;
	}

	/**
	 * Return calculated wholesale-pricing data for a product.
	 *
	 * @param \WC_Product $product  Product object.
	 * @param int|null    $quantity Optional quantity context.
	 * @param float|null  $tier_base_price Optional pre-tier cart base price.
	 * @return array|false
	 */
	private function get_product_price_data( \WC_Product $product, ?int $quantity = null, ?float $tier_base_price = null ) {
		$cache_key = $product->get_id() . ':' . ( null === $quantity ? 'auto' : $quantity );

		if ( null !== $tier_base_price ) {
			$cache_key .= ':tier_base_' . number_format( $tier_base_price, 6, '.', '' );
		}

		if ( isset( $this->price_cache[ $cache_key ] ) ) {
			return $this->price_cache[ $cache_key ];
		}

		$base_price = $this->get_base_price( $product );

		if ( $base_price <= 0 ) {
			$this->price_cache[ $cache_key ] = false;
			return false;
		}

		$best_price_data = false;

		foreach ( $this->valid_rules as $rule ) {
			$is_display_context = null === $quantity && $this->can_preview_conditions_for_request();
			$rule_quantity      = null === $quantity ? max( 1, $this->get_cart_quantity( $product, $rule ) ) : $quantity;

			if ( $is_display_context ) {
				$rule_quantity = $this->get_display_quantity_for_rule( $product, $rule );
			}

			if ( ! $this->can_rule_price_product( $rule, $product, $rule_quantity, $is_display_context ) ) {
				continue;
			}

			$candidate = false;

			if ( 'tiered' === $rule['discount_type'] ) {
				$effective_tier_base_price = $this->get_tier_base_price( $product, $rule, $base_price, $rule_quantity, $tier_base_price );
				$result                    = $this->tiered_discount->calculate( $rule, $product, $effective_tier_base_price, $rule_quantity );

				if ( false !== $result['price'] ) {
					$candidate = array(
						'price' => $result['price'],
						'rule'  => $rule,
						'tier'  => $result['tier'],
					);
				}
			} else {
				$price = $this->calculate_regular_price( $rule, $product, $base_price );

				if ( false !== $price ) {
					$candidate = array(
						'price' => $price,
						'rule'  => $rule,
						'tier'  => false,
					);
				}
			}

			if ( false === $candidate ) {
				continue;
			}

			if ( false === $best_price_data || (float) $candidate['price'] < (float) $best_price_data['price'] ) {
				$best_price_data = $candidate;
			}
		}

		$this->price_cache[ $cache_key ] = $best_price_data;
		return $this->price_cache[ $cache_key ];
	}

	/**
	 * Return the winning Wholesale Pricing tier candidate for a product.
	 *
	 * Active tier prices retain the new engine's existing lowest-price behavior.
	 * When no tier is active yet, the first eligible rule supplies the preview
	 * table so the shared source priority can still select one table.
	 *
	 * @param \WC_Product $product Product object.
	 * @param int         $quantity Quantity context.
	 * @param float|null  $preferred_base_price Base selected by the legacy resolver.
	 * @return array|false
	 */
	private function get_tier_price_data( \WC_Product $product, int $quantity, ?float $preferred_base_price = null ) {
		$this->load_valid_rules();

		$base_price = $this->get_base_price( $product );
		if ( $base_price <= 0 ) {
			return false;
		}

		$preview    = false;
		$best_price = false;

		foreach ( $this->valid_rules as $rule ) {
			if ( 'tiered' !== ( $rule['discount_type'] ?? '' ) ) {
				continue;
			}

			$can_preview = $this->is_product_eligible_for_rule( $product, $rule ) && $this->conditions_pass( $rule );
			$can_price   = $this->can_rule_price_product( $rule, $product, max( 1, $quantity ) );
			if ( ! $can_preview && ! $can_price ) {
				continue;
			}

			$tiers = $this->tiered_discount->get_tiers( $rule );
			if ( empty( $tiers ) ) {
				continue;
			}

			$effective_base = $this->get_tier_base_price( $product, $rule, $base_price, max( 1, $quantity ), $preferred_base_price );
			$calculation    = $can_price
				? $this->tiered_discount->calculate( $rule, $product, $effective_base, $quantity )
				: array(
					'price' => false,
					'tier'  => false,
				);
			$candidate      = array(
				'price'      => $calculation['price'],
				'tier'       => $calculation['tier'],
				'tiers'      => $tiers,
				'rule'       => $rule,
				'base_price' => $effective_base,
			);

			if ( false === $preview && $can_preview ) {
				$preview = $candidate;
			}

			if ( false !== $calculation['price'] && ( false === $best_price || (float) $calculation['price'] < (float) $best_price['price'] ) ) {
				$best_price = $candidate;
			}
		}

		return false !== $best_price ? $best_price : $preview;
	}

	/**
	 * Calculate a non-tiered price with its dedicated rule handler.
	 *
	 * @param array       $rule       Normalized runtime rule.
	 * @param \WC_Product $product    Current product.
	 * @param float       $base_price Price before the discount.
	 * @return float|false
	 */
	private function calculate_regular_price( array $rule, \WC_Product $product, float $base_price ) {
		if ( 'product_discount' === ( $rule['rule_type'] ?? '' ) ) {
			return $this->product_discount->calculate( $rule, $product, $base_price );
		}

		return $this->regular_discount->calculate( $rule, $product, $base_price );
	}

	/**
	 * Return the frontend label from the handler that owns the rule.
	 *
	 * @param array $rule Normalized runtime rule.
	 * @return string
	 */
	private function get_discount_label( array $rule ): string {
		if ( 'product_discount' === ( $rule['rule_type'] ?? '' ) ) {
			return $this->product_discount->get_label( $rule );
		}

		return $this->regular_discount->get_label( $rule );
	}

	/**
	 * Return the effective base price for a tiered rule.
	 *
	 * Tiered wholesale-pricing discounts should stack on top of an applicable
	 * regular wholesale price. For example, when a product has a regular
	 * wholesale price of 3.60 and a tier rule gives 20% off, the tier should
	 * calculate from 3.60 instead of the catalog price.
	 *
	 * @param \WC_Product $product    Product object.
	 * @param array       $tier_rule  Tiered runtime rule.
	 * @param float       $base_price Original base price.
	 * @param int         $quantity   Quantity context.
	 * @param float|null  $preferred_base_price Preferred pre-discount base price.
	 * @return float
	 */
	private function get_tier_base_price( \WC_Product $product, array $tier_rule, float $base_price, int $quantity, ?float $preferred_base_price = null ): float {
		$base_candidates = array( $base_price );

		foreach ( $this->valid_rules as $rule ) {
			if ( 'regular' !== ( $rule['discount_type'] ?? '' ) ) {
				continue;
			}

			if ( ! $this->can_rule_price_product( $rule, $product, $quantity ) ) {
				continue;
			}

			// Product discounts are a separate campaign type and do not become
			// the base for a wholesale tier campaign.
			if ( 'product_discount' === ( $rule['rule_type'] ?? '' ) ) {
				continue;
			}

			$regular_price = $this->regular_discount->calculate( $rule, $product, $base_price );

			if ( false === $regular_price ) {
				continue;
			}

			$regular_price = (float) $regular_price;

			if ( $regular_price > 0 ) {
				$base_candidates[] = $regular_price;
			}
		}

		if (
			null !== $preferred_base_price &&
			$preferred_base_price > 0 &&
			! $this->is_sale_price_base_for_regular_discount( $product, $preferred_base_price ) &&
			! $this->price_matches_rule_tier( $tier_rule, $base_candidates, $preferred_base_price )
		) {
			$base_candidates[] = $preferred_base_price;
		}

		$existing_price = $this->get_existing_wholesale_price( $product );

		if (
			$existing_price > 0 &&
			! $this->is_sale_price_base_for_regular_discount( $product, $existing_price ) &&
			! $this->price_matches_rule_tier( $tier_rule, $base_candidates, $existing_price )
		) {
			$base_candidates[] = $existing_price;
		}

		$tier_base_price = min( $base_candidates );

		return apply_filters(
			'wholesalex_wholesale_pricing_tier_base_price',
			$tier_base_price,
			$product,
			$tier_rule,
			$base_price,
			$quantity
		);
	}

	/**
	 * Check whether a candidate base is already a tier result for this rule.
	 *
	 * @param array $tier_rule       Tiered runtime rule.
	 * @param array $base_prices     Valid pre-tier base-price candidates.
	 * @param float $candidate_price Candidate tier base price.
	 * @return bool
	 */
	private function price_matches_rule_tier( array $tier_rule, array $base_prices, float $candidate_price ): bool {
		if ( $candidate_price <= 0 ) {
			return false;
		}

		$tiers = $this->tiered_discount->get_tiers( $tier_rule );

		foreach ( $base_prices as $base_price ) {
			if ( ! is_numeric( $base_price ) || (float) $base_price <= 0 ) {
				continue;
			}

			foreach ( $tiers as $tier ) {
				$tier_price = self::calculate_discounted_price( $tier, (float) $base_price );

				if ( false !== $tier_price && abs( (float) $tier_price - $candidate_price ) < 0.0001 ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Return an already calculated WholesaleX price for this product.
	 *
	 * Dynamic Rules stores product-discount wholesale prices in the shared
	 * WholesaleX price map before tier table rendering/cart totals. Using it
	 * here keeps new wholesale-pricing tiers consistent with the legacy
	 * quantity-based discount behavior.
	 *
	 * @param \WC_Product $product Product object.
	 * @return float
	 */
	private function get_existing_wholesale_price( \WC_Product $product ): float {
		$product_ids = array( $product->get_id() );

		if ( $product->get_parent_id() ) {
			$product_ids[] = $product->get_parent_id();
		}

		foreach ( array_unique( array_map( 'absint', $product_ids ) ) as $product_id ) {
			$price = wholesalex()->get_wholesalex_wholesale_prices( $product_id );

			if ( false !== $price && is_numeric( $price ) && (float) $price > 0 ) {
				return (float) $price;
			}
		}

		return 0.0;
	}

	/**
	 * Return the first applicable tier table markup for a product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	private function get_tier_table_markup( \WC_Product $product ): string {
		$active_tier_data = apply_filters( 'wholesalex_active_tier_data', array(), $product );
		if ( ! empty( $active_tier_data['src'] ) && 'wholesale_pricing_tier' !== $active_tier_data['src'] ) {
			return '';
		}

		$base_price = $this->get_base_price( $product );
		if ( $base_price <= 0 ) {
			return '';
		}

		$cart_quantity = 0;
		foreach ( $this->valid_rules as $rule ) {
			if ( 'tiered' === ( $rule['discount_type'] ?? '' ) ) {
				$cart_quantity = max( $cart_quantity, $this->get_cart_quantity( $product, $rule ) );
			}
		}

		$preferred_base = ! empty( $active_tier_data['base_price'] ) ? (float) $active_tier_data['base_price'] : $base_price;
		$tier_data      = $this->get_tier_price_data( $product, $cart_quantity, $preferred_base );
		if ( false === $tier_data ) {
			return '';
		}

		$rule   = $tier_data['rule'];
		$markup = $this->tiered_discount->render_table( $rule, $product, $tier_data['base_price'], $cart_quantity );

		return '' === $markup ? '' : '<div class="wsx-wholesale-pricing-rule-' . esc_attr( $rule['id'] ) . '">' . $markup . '</div>';
	}

	/**
	 * Check whether a rule can price a product in the current context.
	 *
	 * @param array       $rule     Normalized rule.
	 * @param \WC_Product $product  Product object.
	 * @param int|null    $quantity Optional quantity context.
	 * @param bool        $preview_conditions Whether display pricing may preview cart conditions.
	 * @return bool
	 */
	private function can_rule_price_product( array $rule, \WC_Product $product, ?int $quantity = null, bool $preview_conditions = false ): bool {
		if ( ! $this->is_product_eligible_for_rule( $product, $rule ) ) {
			return false;
		}

		if ( ! $this->conditions_pass( $rule, $product, $quantity, $preview_conditions ) ) {
			return false;
		}

		if ( 'product_discount' === ( $rule['rule_type'] ?? '' ) ) {
			return true;
		}

		return $this->restrictions_pass_for_pricing( $rule, $product, $quantity );
	}

	/**
	 * Check rule availability before product-specific checks.
	 *
	 * @param array  $rule    Raw saved rule.
	 * @param string $role_id Current WholesaleX role ID.
	 * @param int    $user_id Current user ID.
	 * @return bool
	 */
	private function is_rule_available_for_user( array $rule, string $role_id, int $user_id ): bool {
		if (
			'active' !== ( $rule['status'] ?? '' ) ||
			! in_array( $rule['rule_type'] ?? '', array( 'wholesale_pricing', 'product_discount' ), true )
		) {
			return false;
		}

		if ( 'tiered' === ( $rule['discount_type'] ?? '' ) && ! $this->is_pro_feature_available() ) {
			return false;
		}

		if ( ! $this->schedule_is_active( isset( $rule['schedule'] ) && is_array( $rule['schedule'] ) ? $rule['schedule'] : array() ) ) {
			return false;
		}

		if ( ! Wholesale_Pricing_Condition_Engine::user_role_matches( $rule, $role_id, $user_id ) ) {
			return false;
		}

		if ( isset( $rule['conditions']['tiers'] ) && ! empty( $rule['conditions']['tiers'] ) ) {
			if ( ! Wholesale_Pricing_Condition_Engine::is_user_order_count_purchase_amount_condition_passed( $rule['conditions']['tiers'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check cart discount rule availability before cart-specific checks.
	 *
	 * @param array  $rule    Raw saved rule.
	 * @param string $role_id Current WholesaleX role ID.
	 * @param int    $user_id Current user ID.
	 * @return bool
	 */
	private function is_cart_discount_rule_available_for_user( array $rule, string $role_id, int $user_id ): bool {
		if ( 'active' !== ( $rule['status'] ?? '' ) || 'cart_discount' !== ( $rule['rule_type'] ?? '' ) ) {
			return false;
		}

		if ( ! $this->schedule_is_active( isset( $rule['schedule'] ) && is_array( $rule['schedule'] ) ? $rule['schedule'] : array() ) ) {
			return false;
		}

		if ( ! Wholesale_Pricing_Condition_Engine::user_role_matches( $rule, $role_id, $user_id ) ) {
			return false;
		}

		if ( isset( $rule['conditions']['tiers'] ) && ! empty( $rule['conditions']['tiers'] ) ) {
			if ( ! Wholesale_Pricing_Condition_Engine::is_user_order_count_purchase_amount_condition_passed( $rule['conditions']['tiers'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check BOGO rule availability before product/cart-specific checks.
	 *
	 * @param array  $rule    Raw saved rule.
	 * @param string $role_id Current WholesaleX role ID.
	 * @param int    $user_id Current user ID.
	 * @return bool
	 */
	private function is_bogo_discount_rule_available_for_user( array $rule, string $role_id, int $user_id ): bool {
		if ( 'active' !== ( $rule['status'] ?? '' ) || 'bogo_discount' !== ( $rule['rule_type'] ?? '' ) ) {
			return false;
		}

		if ( ! $this->schedule_is_active( isset( $rule['schedule'] ) && is_array( $rule['schedule'] ) ? $rule['schedule'] : array() ) ) {
			return false;
		}

		if ( ! Wholesale_Pricing_Condition_Engine::user_role_matches( $rule, $role_id, $user_id ) ) {
			return false;
		}

		if ( isset( $rule['conditions']['tiers'] ) && ! empty( $rule['conditions']['tiers'] ) ) {
			if ( ! Wholesale_Pricing_Condition_Engine::is_user_order_count_purchase_amount_condition_passed( $rule['conditions']['tiers'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check Buy X Get Y rule availability before product/cart-specific checks.
	 *
	 * @param array  $rule    Raw saved rule.
	 * @param string $role_id Current WholesaleX role ID.
	 * @param int    $user_id Current user ID.
	 * @return bool
	 */
	private function is_bxgy_discount_rule_available_for_user( array $rule, string $role_id, int $user_id ): bool {
		if ( 'active' !== ( $rule['status'] ?? '' ) || 'buy_x_get_y' !== ( $rule['rule_type'] ?? '' ) ) {
			return false;
		}

		if ( ! $this->schedule_is_active( isset( $rule['schedule'] ) && is_array( $rule['schedule'] ) ? $rule['schedule'] : array() ) ) {
			return false;
		}

		if ( ! Wholesale_Pricing_Condition_Engine::user_role_matches( $rule, $role_id, $user_id ) ) {
			return false;
		}

		if ( isset( $rule['conditions']['tiers'] ) && ! empty( $rule['conditions']['tiers'] ) ) {
			if ( ! Wholesale_Pricing_Condition_Engine::is_user_order_count_purchase_amount_condition_passed( $rule['conditions']['tiers'] ) ) {
				return false;
			}
		}

		$bxgy = isset( $rule['bxgy'] ) && is_array( $rule['bxgy'] ) ? $rule['bxgy'] : array();

		return ! empty( $bxgy['min_qty'] ) && ! empty( $bxgy['free_products'] ) && ! empty( $bxgy['free_item_count'] );
	}

	/**
	 * Check whether Pro-only wholesale-pricing features may run.
	 *
	 * @return bool
	 */
	private function is_pro_feature_available(): bool {
		return method_exists( wholesalex(), 'is_pro_active' ) && wholesalex()->is_pro_active();
	}

	/**
	 * Check whether a rule uses a Pro-only product target.
	 *
	 * @param array $rule Saved rule.
	 * @return bool
	 */
	private function uses_pro_product_targeting( array $rule ): bool {
		return in_array( $rule['product_filter'] ?? '', array( 'brands', 'attributes', 'sku' ), true );
	}

	/**
	 * Strip Pro-only restriction flags from runtime rules when Pro is inactive.
	 *
	 * @param array $restrictions Saved restriction data.
	 * @return array
	 */
	private function get_runtime_restrictions( array $restrictions ): array {
		if ( $this->is_pro_feature_available() ) {
			return $restrictions;
		}

		$restrictions['enable_quantity_limits']      = false;
		$restrictions['enable_quantity_step']        = false;
		$restrictions['enable_value_limits']         = false;
		$restrictions['tiered_combined_variations'] = false;
		$restrictions['min_quantity']                = '';
		$restrictions['max_quantity']                = '';
		$restrictions['quantity_step']               = '';
		$restrictions['min_amount']                  = '';
		$restrictions['max_amount']                  = '';
		$restrictions['min_quantity_message']        = '';
		$restrictions['max_quantity_message']        = '';
		$restrictions['min_amount_message']          = '';
		$restrictions['max_amount_message']          = '';

		return $restrictions;
	}

	/**
	 * Normalize a saved rule into a runtime entry.
	 *
	 * @param array  $rule    Raw saved rule.
	 * @param string $role_id Current role.
	 * @return array
	 */
	private function normalize_rule_for_runtime( array $rule, string $role_id ): array {
		$filter_data = Wholesale_Pricing_Condition_Engine::get_runtime_product_filter( $rule );

		$restrictions = isset( $rule['restrictions'] ) && is_array( $rule['restrictions'] ) ? $rule['restrictions'] : array();

		return array(
			'id'                  => isset( $rule['id'] ) ? sanitize_text_field( $rule['id'] ) : '',
			'title'               => isset( $rule['title'] ) ? $rule['title'] : '',
			'owner_id'            => absint( $rule['owner_id'] ?? 0 ),
			'owner_type'          => sanitize_key( $rule['owner_type'] ?? 'admin' ),
			'rule_type'           => 'product_discount' === ( $rule['rule_type'] ?? '' ) ? 'product_discount' : 'wholesale_pricing',
			'discount_type'       => isset( $rule['discount_type'] ) && 'tiered' === $rule['discount_type'] ? 'tiered' : 'regular',
			'rule'                => 'product_discount' === ( $rule['rule_type'] ?? '' )
				? ( isset( $rule['product_discount'] ) ? $rule['product_discount'] : array() )
				: ( 'tiered' === ( $rule['discount_type'] ?? '' ) ? array( 'tiers' => isset( $rule['tiers'] ) ? $rule['tiers'] : array() ) : ( isset( $rule['regular'] ) ? $rule['regular'] : array() ) ),
			'filter'              => $filter_data['filter'],
			'conditions'          => array( 'tiers' => isset( $rule['conditions']['tiers'] ) ? wholesalex()->filter_empty_conditions( $rule['conditions']['tiers'] ) : array() ),
			'restrictions'        => 'product_discount' === ( $rule['rule_type'] ?? '' ) ? array() : $this->get_runtime_restrictions( $restrictions ),
			'design'              => isset( $rule['design'] ) && is_array( $rule['design'] ) ? $rule['design'] : array(),
			'schedule'            => isset( $rule['schedule'] ) && is_array( $rule['schedule'] ) ? $rule['schedule'] : array(),
			'who_priority'        => Wholesale_Pricing_Condition_Engine::get_user_targeting_priority( $rule ),
			'applied_on_priority' => $filter_data['priority'],
			'role_id'             => $role_id,
		);
	}

	/**
	 * Normalize a saved cart discount rule into a runtime entry.
	 *
	 * @param array  $rule    Raw saved cart discount rule.
	 * @param string $role_id Current role.
	 * @return array
	 */
	private function normalize_cart_discount_rule_for_runtime( array $rule, string $role_id ): array {
		$filter_data = Wholesale_Pricing_Condition_Engine::get_runtime_product_filter( $rule );
		$cart        = isset( $rule['cart'] ) && is_array( $rule['cart'] ) ? $rule['cart'] : array();

		return array(
			'id'                  => isset( $rule['id'] ) ? sanitize_text_field( $rule['id'] ) : '',
			'title'               => isset( $rule['title'] ) ? $rule['title'] : '',
			'rule_type'           => 'cart_discount',
			'rule'                => array(
				'_discount_type'   => isset( $cart['discount_type'] ) ? sanitize_key( $cart['discount_type'] ) : 'percentage',
				'_discount_amount' => isset( $cart['discount_amount'] ) ? $cart['discount_amount'] : '',
				'_discount_name'   => isset( $cart['discount_name'] ) ? $cart['discount_name'] : '',
				'_discount_label'  => isset( $cart['label_text'] ) ? $cart['label_text'] : '',
			),
			'cart'                => $cart,
			'filter'              => $filter_data['filter'],
			'conditions'          => array( 'tiers' => isset( $rule['conditions']['tiers'] ) ? wholesalex()->filter_empty_conditions( $rule['conditions']['tiers'] ) : array() ),
			'schedule'            => isset( $rule['schedule'] ) && is_array( $rule['schedule'] ) ? $rule['schedule'] : array(),
			'who_priority'        => Wholesale_Pricing_Condition_Engine::get_user_targeting_priority( $rule ),
			'applied_on_priority' => $filter_data['priority'],
			'role_id'             => $role_id,
		);
	}

	/**
	 * Normalize a saved BOGO discount rule into a runtime entry.
	 *
	 * @param array  $rule    Raw saved BOGO discount rule.
	 * @param string $role_id Current role.
	 * @return array
	 */
	private function normalize_bogo_discount_rule_for_runtime( array $rule, string $role_id ): array {
		$filter_data = Wholesale_Pricing_Condition_Engine::get_runtime_product_filter( $rule );
		$bogo        = isset( $rule['bogo'] ) && is_array( $rule['bogo'] ) ? $rule['bogo'] : array();

		return array(
			'id'                  => isset( $rule['id'] ) ? sanitize_text_field( $rule['id'] ) : '',
			'title'               => isset( $rule['title'] ) ? $rule['title'] : '',
			'rule_type'           => 'bogo_discount',
			'rule'                => array(
				'_minimum_purchase_count' => isset( $bogo['buy_x_qty'] ) ? absint( $bogo['buy_x_qty'] ) : 1,
				'_per_cart_once'          => ! empty( $bogo['per_cart_once'] ),
			),
			'bogo'                => $bogo,
			'filter'              => $filter_data['filter'],
			'conditions'          => array( 'tiers' => isset( $rule['conditions']['tiers'] ) ? wholesalex()->filter_empty_conditions( $rule['conditions']['tiers'] ) : array() ),
			'schedule'            => isset( $rule['schedule'] ) && is_array( $rule['schedule'] ) ? $rule['schedule'] : array(),
			'who_priority'        => Wholesale_Pricing_Condition_Engine::get_user_targeting_priority( $rule ),
			'applied_on_priority' => $filter_data['priority'],
			'role_id'             => $role_id,
		);
	}

	/**
	 * Normalize a saved Buy X Get Y discount rule into a runtime entry.
	 *
	 * @param array  $rule    Raw saved Buy X Get Y discount rule.
	 * @param string $role_id Current role.
	 * @return array
	 */
	private function normalize_bxgy_discount_rule_for_runtime( array $rule, string $role_id ): array {
		$filter_data = Wholesale_Pricing_Condition_Engine::get_runtime_product_filter( $rule );
		$bxgy        = isset( $rule['bxgy'] ) && is_array( $rule['bxgy'] ) ? $rule['bxgy'] : array();

		return array(
			'id'                  => isset( $rule['id'] ) ? sanitize_text_field( $rule['id'] ) : '',
			'title'               => isset( $rule['title'] ) ? $rule['title'] : '',
			'rule_type'           => 'buy_x_get_y',
			'rule'                => array(
				'_minimum_purchase_count'           => isset( $bxgy['min_qty'] ) ? absint( $bxgy['min_qty'] ) : 1,
				'_free_item'                        => isset( $bxgy['free_products'] ) && is_array( $bxgy['free_products'] ) ? $bxgy['free_products'] : array(),
				'_free_item_count'                  => isset( $bxgy['free_item_count'] ) ? absint( $bxgy['free_item_count'] ) : 1,
				'_per_cart_once'                    => ! empty( $bxgy['per_cart_once'] ),
				'_buy_x_get_product_badge_enable'   => ! empty( $bxgy['show_badge'] ) ? 'yes' : 'no',
				'_product_badge_label'              => isset( $bxgy['badge_label'] ) ? $bxgy['badge_label'] : '',
				'_product_badge_styles'             => isset( $bxgy['badge_style'] ) ? $bxgy['badge_style'] : 'style_one',
				'_product_badge_position'           => isset( $bxgy['badge_position'] ) ? $bxgy['badge_position'] : '',
				'_product_badge_bg_color'           => isset( $bxgy['badge_bg_color'] ) ? $bxgy['badge_bg_color'] : '#5a40e8',
				'_product_badge_text_color'         => isset( $bxgy['badge_text_color'] ) ? $bxgy['badge_text_color'] : '#ffffff',
			),
			'bxgy'                => $bxgy,
			'filter'              => $filter_data['filter'],
			'conditions'          => array( 'tiers' => isset( $rule['conditions']['tiers'] ) ? wholesalex()->filter_empty_conditions( $rule['conditions']['tiers'] ) : array() ),
			'schedule'            => isset( $rule['schedule'] ) && is_array( $rule['schedule'] ) ? $rule['schedule'] : array(),
			'who_priority'        => Wholesale_Pricing_Condition_Engine::get_user_targeting_priority( $rule ),
			'applied_on_priority' => $filter_data['priority'],
			'role_id'             => $role_id,
		);
	}

	/**
	 * Check whether the current product matches a rule's product targeting.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $rule    Normalized rule.
	 * @return bool
	 */
	private function is_product_eligible_for_rule( \WC_Product $product, array $rule ): bool {
		return Wholesale_Pricing_Condition_Engine::is_product_eligible_for_rule( $product, $rule );
	}

	/**
	 * Check advanced conditions for a rule.
	 *
	 * @param array            $rule               Normalized rule.
	 * @param \WC_Product|null $product            Product object.
	 * @param int|null         $quantity           Preview quantity.
	 * @param bool             $preview_conditions Whether display pricing may preview cart conditions.
	 * @return bool
	 */
	private function conditions_pass( array $rule, ?\WC_Product $product = null, ?int $quantity = null, bool $preview_conditions = false ): bool {
		return Wholesale_Pricing_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'], $this->get_condition_preview_context( $rule, $product, $quantity, $preview_conditions ) );
	}

	/**
	 * Check whether the current request is safe for pre-cart condition previews.
	 *
	 * @return bool
	 */
	private function can_preview_conditions_for_request(): bool {
		if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Build a temporary cart context for product-page price previews.
	 *
	 * Regular min/max rules are evaluated before add-to-cart. In that display
	 * context, cart conditions can use the quantity required by a quantity or
	 * product-value limit instead of an empty cart. Cart totals pass an explicit
	 * quantity and do not use this preview context.
	 *
	 * @param array            $rule               Normalized rule.
	 * @param \WC_Product|null $product            Product object.
	 * @param int|null         $quantity           Preview quantity.
	 * @param bool             $preview_conditions Whether preview context is allowed.
	 * @return array
	 */
	private function get_condition_preview_context( array $rule, ?\WC_Product $product, ?int $quantity, bool $preview_conditions ): array {
		if ( ! $preview_conditions || ! $product instanceof \WC_Product || null === $quantity || ( ! $this->rule_uses_quantity_limits( $rule ) && ! $this->rule_uses_value_limits( $rule ) ) ) {
			return array();
		}

		if ( ! $this->quantity_is_inside_limits( $rule['restrictions'], $quantity ) ) {
			return array();
		}

		$product_id    = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id  = $product->get_parent_id() ? $product->get_id() : 0;
		$product_value = $this->get_product_value( $product, $quantity, $rule );

		return array(
			'preview_cart_item' => array(
				'product_id'     => $product_id,
				'variation_id'   => $variation_id,
				'quantity'       => $quantity,
				'line_subtotal'  => $product_value,
				'data'           => $product,
			),
		);
	}

	/**
	 * Check quantity/value restrictions before applying a price.
	 *
	 * @param array       $rule     Normalized rule.
	 * @param \WC_Product $product  Product object.
	 * @param int|null    $quantity Optional quantity context.
	 * @return bool
	 */
	private function restrictions_pass_for_pricing( array $rule, \WC_Product $product, ?int $quantity ): bool {
		$restrictions = $rule['restrictions'];
		$qty          = null === $quantity ? $this->get_cart_quantity( $product, $rule ) : $quantity;

		if ( $this->rule_uses_quantity_limits( $rule ) ) {
			if ( ! $this->quantity_is_inside_limits( $restrictions, $qty ) ) {
				return false;
			}
		}

		if ( $this->rule_uses_value_limits( $rule ) ) {
			if ( ! $this->amount_is_inside_limits( $restrictions, $this->get_product_value( $product, $qty, $rule ) ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Return base price for discount calculation.
	 *
	 * @param \WC_Product $product Product object.
	 * @return float
	 */
	private function get_base_price( \WC_Product $product ): float {
		$current_role = wholesalex()->get_current_user_role();
		$role_regular = (float) get_post_meta( $product->get_id(), $current_role . '_base_price', true );
		$role_sale    = (float) get_post_meta( $product->get_id(), $current_role . '_sale_price', true );
		$regular      = (float) $product->get_regular_price( 'edit' );
		$sale         = $this->product_sale_is_active( $product ) ? (float) $product->get_sale_price( 'edit' ) : 0.0;
		$source       = wholesalex()->get_setting( '_is_sale_or_regular_Price', 'is_regular_price' );

		if ( 'is_sale_price' === $source ) {
			if ( $role_sale > 0 ) {
				return $role_sale;
			}
			if ( $role_regular > 0 ) {
				return $role_regular;
			}
			return $sale > 0 ? $sale : $regular;
		}

		return $role_regular > 0 ? $role_regular : $regular;
	}

	/**
	 * Check native WooCommerce sale schedule.
	 *
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	private function product_sale_is_active( \WC_Product $product ): bool {
		$sale_price = $product->get_sale_price( 'edit' );

		if ( '' === $sale_price || null === $sale_price ) {
			return false;
		}

		$now  = current_time( 'timestamp' );
		$from = get_post_meta( $product->get_id(), '_sale_price_dates_from', true );
		$to   = get_post_meta( $product->get_id(), '_sale_price_dates_to', true );

		if ( $from && $now < (int) $from ) {
			return false;
		}
		if ( $to && $now > (int) $to ) {
			return false;
		}

		return true;
	}

	/**
	 * Check whether a captured lower price is only the sale price.
	 *
	 * When product discounts are configured to apply on the regular price,
	 * cart-tier discounts must ignore native/role sale prices as calculation
	 * bases. Otherwise a 20 amount tier on a 65 regular / 55 sale product
	 * incorrectly becomes 35 instead of 45.
	 *
	 * @param \WC_Product $product Product object.
	 * @param float       $price   Candidate lower base price.
	 * @return bool
	 */
	private function is_sale_price_base_for_regular_discount( \WC_Product $product, float $price ): bool {
		if ( $price <= 0 || 'is_regular_price' !== wholesalex()->get_setting( '_is_sale_or_regular_Price', 'is_regular_price' ) ) {
			return false;
		}

		$role_sale_price = (float) get_post_meta( $product->get_id(), wholesalex()->get_current_user_role() . '_sale_price', true );
		$sale_price      = $this->product_sale_is_active( $product ) ? (float) $product->get_sale_price( 'edit' ) : 0.0;

		return (
			$sale_price > 0 &&
			abs( $price - $sale_price ) < 0.000001
		) || (
			$role_sale_price > 0 &&
			abs( $price - $role_sale_price ) < 0.000001
		);
	}

	/**
	 * Check a wholesale-pricing schedule. End date is inclusive for the saved date.
	 *
	 * @param array $schedule Schedule data.
	 * @return bool
	 */
	private function schedule_is_active( array $schedule ): bool {
		$timezone = wp_timezone();
		$now      = new \DateTimeImmutable( 'now', $timezone );
		$start    = false;
		$end      = false;

		if ( ! empty( $schedule['start_date'] ) ) {
			$start = $this->get_schedule_boundary( $schedule['start_date'], false, $timezone );
			if ( false === $start || $now < $start ) {
				return false;
			}
		}

		if ( ! empty( $schedule['end_date'] ) ) {
			$end = $this->get_schedule_boundary( $schedule['end_date'], true, $timezone );
			if ( false === $end || $now >= $end ) {
				return false;
			}
		}

		if ( false !== $start && false !== $end && $start >= $end ) {
			return false;
		}

		return true;
	}

	/**
	 * Build a schedule boundary in the configured WordPress timezone.
	 *
	 * End dates use the first valid instant of the following local calendar date
	 * as an exclusive boundary, preserving 11:59:59 PM across DST transitions.
	 *
	 * @param mixed         $value      Saved schedule value.
	 * @param bool          $is_end     Whether to build an end boundary.
	 * @param \DateTimeZone $timezone   WordPress timezone.
	 * @return \DateTimeImmutable|false
	 */
	private function get_schedule_boundary( $value, bool $is_end, \DateTimeZone $timezone ) {
		$value = trim( (string) $value );

		try {
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
				$date   = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, $timezone );
				$errors = \DateTimeImmutable::getLastErrors();

				if (
					false === $date ||
					( is_array( $errors ) && ( 0 < $errors['warning_count'] || 0 < $errors['error_count'] ) ) ||
					$value !== $date->format( 'Y-m-d' )
				) {
					return false;
				}

				$calendar_date = $value;
			} else {
				$calendar_date = ( new \DateTimeImmutable( $value, $timezone ) )
					->setTimezone( $timezone )
					->format( 'Y-m-d' );
			}

			if ( $is_end ) {
				$calendar_date = ( new \DateTimeImmutable( $calendar_date, new \DateTimeZone( 'UTC' ) ) )
					->modify( '+1 day' )
					->format( 'Y-m-d' );
			}

			$boundary = \DateTimeImmutable::createFromFormat( '!Y-m-d', $calendar_date, $timezone );
			$errors   = \DateTimeImmutable::getLastErrors();

			if (
				false === $boundary ||
				( is_array( $errors ) && ( 0 < $errors['warning_count'] || 0 < $errors['error_count'] ) ) ||
				$calendar_date !== $boundary->format( 'Y-m-d' )
			) {
				return false;
			}

			return $boundary;
		} catch ( \Exception $exception ) {
			return false;
		}
	}

	/**
	 * Get current cart quantity for a product.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $rule    Normalized rule.
	 * @return int
	 */
	private function get_cart_quantity( \WC_Product $product, array $rule ): int {
		if ( ! WC()->cart ) {
			return 0;
		}

		$quantity           = 0;
		$product_id         = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id       = $product->get_parent_id() ? $product->get_id() : 0;
		$combine_variations = ! empty( $rule['restrictions']['tiered_combined_variations'] );

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( $combine_variations && $variation_id && (int) $cart_item['product_id'] === (int) $product_id ) {
				$quantity += absint( $cart_item['quantity'] );
				continue;
			}

			$cart_product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( (int) $cart_product_id === (int) $product->get_id() ) {
				$quantity += absint( $cart_item['quantity'] );
			}
		}

		return $quantity;
	}

	/**
	 * Get cart quantity while simulating a pending cart item update.
	 *
	 * @param \WC_Product $product       Product object.
	 * @param array       $rule          Normalized rule.
	 * @param string      $cart_item_key Cart item being updated.
	 * @param int         $new_quantity  New quantity for that cart item.
	 * @return int
	 */
	private function get_cart_quantity_for_update( \WC_Product $product, array $rule, string $cart_item_key, int $new_quantity ): int {
		if ( ! WC()->cart ) {
			return $new_quantity;
		}

		$quantity           = 0;
		$product_id         = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id       = $product->get_parent_id() ? $product->get_id() : 0;
		$combine_variations = ! empty( $rule['restrictions']['tiered_combined_variations'] );

		foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
			$item_quantity = $key === $cart_item_key ? $new_quantity : absint( $cart_item['quantity'] );

			if ( $combine_variations && $variation_id && (int) $cart_item['product_id'] === (int) $product_id ) {
				$quantity += $item_quantity;
				continue;
			}

			$cart_product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( (int) $cart_product_id === (int) $product->get_id() ) {
				$quantity += $item_quantity;
			}
		}

		return $quantity;
	}

	/**
	 * Get a product line's value at the rule's wholesale price.
	 *
	 * Calculate the regular discount directly instead of reading get_price().
	 * The latter is filtered by this engine and would recursively re-enter the
	 * restriction check. If a rule cannot produce a valid wholesale price, fall
	 * back to its base price so malformed rules do not make products unbuyable.
	 *
	 * @param \WC_Product $product  Product object.
	 * @param int         $quantity Product quantity.
	 * @param array       $rule     Normalized wholesale-pricing rule.
	 * @return float
	 */
	private function get_product_value( \WC_Product $product, int $quantity, array $rule ): float {
		$base_price      = $this->get_base_price( $product );
		$wholesale_price = $this->calculate_regular_price( $rule, $product, $base_price );
		$unit_price      = false === $wholesale_price ? $base_price : (float) $wholesale_price;

		return $unit_price * max( 0, $quantity );
	}

	/**
	 * Find the highest-priority quantity restriction for a product.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array|false
	 */
	private function get_product_quantity_restriction( \WC_Product $product ) {
		foreach ( $this->valid_rules as $rule ) {
			$restrictions        = $rule['restrictions'];
			$preview_conditions  = $this->can_preview_conditions_for_request();
			$quantity            = $preview_conditions ? $this->get_display_quantity_for_rule( $product, $rule ) : null;

			if ( ! $this->rule_uses_quantity_limits( $rule ) || ! $this->is_product_eligible_for_rule( $product, $rule ) || ! $this->conditions_pass( $rule, $product, $quantity, $preview_conditions ) ) {
				continue;
			}

			return array(
				'min'     => isset( $restrictions['min_quantity'] ) ? absint( $restrictions['min_quantity'] ) : 0,
				'max'     => isset( $restrictions['max_quantity'] ) ? absint( $restrictions['max_quantity'] ) : 0,
				'step'    => ! empty( $restrictions['enable_quantity_step'] ) && isset( $restrictions['quantity_step'] ) ? absint( $restrictions['quantity_step'] ) : 0,
				'message' => array(
					'min' => isset( $restrictions['min_quantity_message'] ) ? $restrictions['min_quantity_message'] : '',
					'max' => isset( $restrictions['max_quantity_message'] ) ? $restrictions['max_quantity_message'] : '',
				),
				'rule'    => $rule,
			);
		}

		return false;
	}

	/**
	 * Find the highest-priority order-value restriction for a product.
	 *
	 * @param \WC_Product $product  Product object.
	 * @param int         $quantity Resulting product quantity.
	 * @return array|false
	 */
	private function get_product_value_restriction( \WC_Product $product, int $quantity ) {
		foreach ( $this->valid_rules as $rule ) {
			if (
				! $this->rule_uses_value_limits( $rule ) ||
				! $this->is_product_eligible_for_rule( $product, $rule ) ||
				! $this->conditions_pass( $rule, $product, $quantity, $this->can_preview_conditions_for_request() )
			) {
				continue;
			}

			$restrictions = $rule['restrictions'];

			return array(
				'min'     => isset( $restrictions['min_amount'] ) ? (float) $restrictions['min_amount'] : 0.0,
				'max'     => isset( $restrictions['max_amount'] ) ? (float) $restrictions['max_amount'] : 0.0,
				'message' => array(
					'min' => isset( $restrictions['min_amount_message'] ) ? $restrictions['min_amount_message'] : '',
					'max' => isset( $restrictions['max_amount_message'] ) ? $restrictions['max_amount_message'] : '',
				),
				'rule'    => $rule,
			);
		}

		return false;
	}

	/**
	 * Tiered pricing owns its quantity ranges through tiers, so rule-level
	 * min/max quantity restrictions only apply to regular wholesale discounts.
	 *
	 * @param array $rule Normalized rule.
	 * @return bool
	 */
	private function rule_uses_quantity_limits( array $rule ): bool {
		return 'regular' === ( $rule['discount_type'] ?? 'regular' ) && ! empty( $rule['restrictions']['enable_quantity_limits'] );
	}

	/**
	 * Order-value restrictions are available only for regular wholesale pricing.
	 *
	 * @param array $rule Normalized rule.
	 * @return bool
	 */
	private function rule_uses_value_limits( array $rule ): bool {
		return 'regular' === ( $rule['discount_type'] ?? 'regular' ) && ! empty( $rule['restrictions']['enable_value_limits'] );
	}

	/**
	 * Return a sensible quantity when pricing is rendered before a shopper has
	 * selected a quantity. Regular min/max discounts should preview from the
	 * quantity needed to satisfy their minimum limits instead of failing against
	 * a default quantity of 1.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $rule    Normalized rule.
	 * @return int
	 */
	private function get_display_quantity_for_rule( \WC_Product $product, array $rule ): int {
		$display_quantity = max( 1, $this->get_cart_quantity( $product, $rule ) );

		if ( $this->rule_uses_quantity_limits( $rule ) ) {
			$min = isset( $rule['restrictions']['min_quantity'] ) ? absint( $rule['restrictions']['min_quantity'] ) : 0;

			if ( $min > 0 ) {
				$display_quantity = max( $display_quantity, $min );
			}
		}

		if ( $this->rule_uses_value_limits( $rule ) ) {
			$minimum_amount = isset( $rule['restrictions']['min_amount'] ) ? (float) $rule['restrictions']['min_amount'] : 0.0;
			$unit_price     = $this->get_product_value( $product, 1, $rule );

			if ( $minimum_amount > 0 && $unit_price > 0 ) {
				$display_quantity = max( $display_quantity, (int) ceil( $minimum_amount / $unit_price ) );
			}
		}

		return $display_quantity;
	}

	/**
	 * Validate a quantity against min/max restrictions and add a notice if needed.
	 *
	 * @param bool        $passed      Current validation state.
	 * @param \WC_Product $product     Product object.
	 * @param array       $restriction Restriction data.
	 * @param int         $quantity    Quantity to validate.
	 * @return bool
	 */
	private function validate_quantity_against_restriction( bool $passed, \WC_Product $product, array $restriction, int $quantity ): bool {
		if ( $restriction['min'] > 0 && $quantity < $restriction['min'] ) {
			wc_add_notice( $this->get_quantity_notice( $product, $restriction, 'min' ), 'error' );
			return false;
		}

		if ( $restriction['max'] > 0 && $quantity > $restriction['max'] ) {
			wc_add_notice( $this->get_quantity_notice( $product, $restriction, 'max' ), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Validate a product's base-price value against min/max restrictions.
	 *
	 * @param bool        $passed      Current validation state.
	 * @param \WC_Product $product     Product object.
	 * @param array       $restriction Restriction data.
	 * @param int         $quantity    Resulting product quantity.
	 * @return bool
	 */
	private function validate_value_against_restriction( bool $passed, \WC_Product $product, array $restriction, int $quantity ): bool {
		$product_value = $this->get_product_value( $product, $quantity, $restriction['rule'] );

		if ( ! $this->amount_is_inside_limits( $restriction, $product_value ) ) {
			$type = $restriction['min'] > 0 && $product_value < $restriction['min'] ? 'min' : 'max';
			wc_add_notice( $this->get_amount_notice( $product, $restriction, $type, $product_value ), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Add cart item quantity notices when restrictions fail.
	 *
	 * @param array       $shown_notices Notice de-duplication map.
	 * @param array       $rule          Normalized rule.
	 * @param \WC_Product $product       Product object.
	 * @param int         $quantity      Cart item quantity.
	 * @return void
	 */
	private function maybe_add_quantity_notice( array &$shown_notices, array $rule, \WC_Product $product, int $quantity ): void {
		$restrictions = $rule['restrictions'];

		$restriction = array(
			'min'     => isset( $restrictions['min_quantity'] ) ? absint( $restrictions['min_quantity'] ) : 0,
			'max'     => isset( $restrictions['max_quantity'] ) ? absint( $restrictions['max_quantity'] ) : 0,
			'message' => array(
				'min' => isset( $restrictions['min_quantity_message'] ) ? $restrictions['min_quantity_message'] : '',
				'max' => isset( $restrictions['max_quantity_message'] ) ? $restrictions['max_quantity_message'] : '',
			),
		);

		if ( $restriction['min'] > 0 && $quantity < $restriction['min'] ) {
			$key = $rule['id'] . ':min:' . $product->get_id();
			if ( empty( $shown_notices[ $key ] ) ) {
				wc_add_notice( $this->get_quantity_notice( $product, $restriction, 'min' ), 'error' );
				$shown_notices[ $key ] = true;
			}
		} elseif ( $restriction['max'] > 0 && $quantity > $restriction['max'] ) {
			$key = $rule['id'] . ':max:' . $product->get_id();
			if ( empty( $shown_notices[ $key ] ) ) {
				wc_add_notice( $this->get_quantity_notice( $product, $restriction, 'max' ), 'error' );
				$shown_notices[ $key ] = true;
			}
		}
	}

	/**
	 * Add order amount notices when restrictions fail.
	 *
	 * @param array       $shown_notices Notice de-duplication map.
	 * @param array       $rule          Normalized rule.
	 * @param \WC_Product $product       Product object.
	 * @param int         $quantity      Product quantity.
	 * @return void
	 */
	private function maybe_add_amount_notice( array &$shown_notices, array $rule, \WC_Product $product, int $quantity ): void {
		$restrictions = $rule['restrictions'];
		$restriction  = array(
			'min'     => isset( $restrictions['min_amount'] ) ? (float) $restrictions['min_amount'] : 0.0,
			'max'     => isset( $restrictions['max_amount'] ) ? (float) $restrictions['max_amount'] : 0.0,
			'message' => array(
				'min' => isset( $restrictions['min_amount_message'] ) ? $restrictions['min_amount_message'] : '',
				'max' => isset( $restrictions['max_amount_message'] ) ? $restrictions['max_amount_message'] : '',
			),
			'rule'    => $rule,
		);
		$product_value = $this->get_product_value( $product, $quantity, $rule );

		if ( $restriction['min'] > 0 && $product_value < $restriction['min'] ) {
			$type = 'min';
		} elseif ( $restriction['max'] > 0 && $product_value > $restriction['max'] ) {
			$type = 'max';
		} else {
			return;
		}

		$key = $rule['id'] . ':amount_' . $type . ':' . $product->get_id();
		if ( empty( $shown_notices[ $key ] ) ) {
			wc_add_notice( $this->get_amount_notice( $product, $restriction, $type, $product_value ), 'error' );
			$shown_notices[ $key ] = true;
		}
	}

	/**
	 * Build an order-value restriction notice.
	 *
	 * @param \WC_Product $product       Product object.
	 * @param array       $restriction   Restriction data.
	 * @param string      $type          min|max.
	 * @param float       $product_value Current product value.
	 * @return string
	 */
	private function get_amount_notice( \WC_Product $product, array $restriction, string $type, float $product_value ): string {
		if ( 'min' === $type ) {
			$message = ! empty( $restriction['message']['min'] ) ? $restriction['message']['min'] : __( 'You must spend at least {minimum_amount} on this product to add it to your cart.', 'wholesalex' );
		} else {
			$message = ! empty( $restriction['message']['max'] ) ? $restriction['message']['max'] : __( 'You can spend up to {maximum_amount} on this product.', 'wholesalex' );
		}

		return $this->replace_smart_tags(
			$message,
			array(
				'{minimum_amount}' => wc_price( $restriction['min'] ),
				'{maximum_amount}' => wc_price( $restriction['max'] ),
				'{product_value}'  => wc_price( $product_value ),
				'{product_title}'  => $product->get_name(),
			)
		);
	}

	/**
	 * Build a quantity restriction notice.
	 *
	 * @param \WC_Product $product     Product object.
	 * @param array       $restriction Restriction data.
	 * @param string      $type        min|max.
	 * @return string
	 */
	private function get_quantity_notice( \WC_Product $product, array $restriction, string $type ): string {
		if ( 'min' === $type ) {
			$message = ! empty( $restriction['message']['min'] ) ? $restriction['message']['min'] : __( 'You have to add minimum {minimum_qty} quantity of {product_title}.', 'wholesalex' );
			return $this->replace_smart_tags(
				$message,
				array(
					'{minimum_qty}'   => $restriction['min'],
					'{product_title}' => $product->get_name(),
				)
			);
		}

		$message = ! empty( $restriction['message']['max'] ) ? $restriction['message']['max'] : __( 'You can add maximum {maximum_qty} quantity of {product_title}.', 'wholesalex' );
		return $this->replace_smart_tags(
			$message,
			array(
				'{maximum_qty}'   => $restriction['max'],
				'{product_title}' => $product->get_name(),
			)
		);
	}

	/**
	 * Replace simple smart tags in a message.
	 *
	 * @param string $message Message template.
	 * @param array  $tags    Tag replacements.
	 * @return string
	 */
	private function replace_smart_tags( string $message, array $tags ): string {
		foreach ( $tags as $tag => $value ) {
			$message = str_replace( $tag, (string) $value, $message );
		}

		return $message;
	}

	/**
	 * Check quantity min/max.
	 *
	 * @param array $restrictions Rule restrictions.
	 * @param int   $quantity     Quantity.
	 * @return bool
	 */
	private function quantity_is_inside_limits( array $restrictions, int $quantity ): bool {
		$min = isset( $restrictions['min_quantity'] ) ? absint( $restrictions['min_quantity'] ) : 0;
		$max = isset( $restrictions['max_quantity'] ) ? absint( $restrictions['max_quantity'] ) : 0;

		if ( $min > 0 && $quantity < $min ) {
			return false;
		}

		if ( $max > 0 && $quantity > $max ) {
			return false;
		}

		return true;
	}

	/**
	 * Check amount min/max.
	 *
	 * @param array $restrictions Rule restrictions.
	 * @param float $amount       Amount.
	 * @return bool
	 */
	private function amount_is_inside_limits( array $restrictions, float $amount ): bool {
		$min = isset( $restrictions['min_amount'] ) ? (float) $restrictions['min_amount'] : ( isset( $restrictions['min'] ) ? (float) $restrictions['min'] : 0.0 );
		$max = isset( $restrictions['max_amount'] ) ? (float) $restrictions['max_amount'] : ( isset( $restrictions['max'] ) ? (float) $restrictions['max'] : 0.0 );

		if ( $min > 0 && $amount < $min ) {
			return false;
		}

		if ( $max > 0 && $amount > $max ) {
			return false;
		}

		return true;
	}

	/**
	 * Compare two runtime rules by product/user targeting priority.
	 *
	 * @param array $a First rule.
	 * @param array $b Second rule.
	 * @return int
	 */
	private function compare_by_priority( array $a, array $b ): int {
		if ( $a['applied_on_priority'] === $b['applied_on_priority'] ) {
			return $b['who_priority'] <=> $a['who_priority'];
		}

		return $b['applied_on_priority'] <=> $a['applied_on_priority'];
	}

	/**
	 * Track discounted products for order meta compatibility.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	private function set_discounted_product( int $product_id ): void {
		if ( is_admin() || ! WC()->session ) {
			return;
		}

		$products = WC()->session->get( '__wholesalex_discounted_products' );
		$products = is_array( $products ) ? $products : array();
		$products[ $product_id ] = true;

		WC()->session->set( '__wholesalex_discounted_products', $products );
	}

	/**
	 * Track applied wholesale-pricing cart discount rules in session.
	 *
	 * @param string $rule_id Rule ID.
	 * @return void
	 */
	private function set_discounted_cart_rule( string $rule_id ): void {
		if ( is_admin() || ! WC()->session ) {
			return;
		}

		$rules = WC()->session->get( '__wholesalex_wholesale_pricing_cart_discount_rules' );
		$rules = is_array( $rules ) ? $rules : array();
		$rules[ $rule_id ] = true;

		WC()->session->set( '__wholesalex_wholesale_pricing_cart_discount_rules', $rules );
	}

	/**
	 * Track applied wholesale-pricing BOGO rules in session.
	 *
	 * @param string $rule_id Rule ID.
	 * @return void
	 */
	private function set_discounted_bogo_rule( string $rule_id ): void {
		if ( is_admin() || ! WC()->session ) {
			return;
		}

		$rules = WC()->session->get( '__wholesalex_wholesale_pricing_bogo_discount_rules' );
		$rules = is_array( $rules ) ? $rules : array();
		$rules[ $rule_id ] = true;

		WC()->session->set( '__wholesalex_wholesale_pricing_bogo_discount_rules', $rules );
	}

	/**
	 * Calculate a discounted price from a standard WholesaleX discount array.
	 *
	 * @param array $discount   Discount shape with _discount_type/_discount_amount.
	 * @param float $base_price Base price.
	 * @return string|false
	 */
	public static function calculate_discounted_price( array $discount, float $base_price ) {
		if ( empty( $discount['_discount_type'] ) || '' === (string) ( $discount['_discount_amount'] ?? '' ) || $base_price <= 0 ) {
			return false;
		}

		$amount = (float) $discount['_discount_amount'];

		switch ( $discount['_discount_type'] ) {
			case 'percentage':
				$price = max( 0, $base_price - ( ( $base_price * $amount ) / 100 ) );
				break;
			case 'amount':
				$price = max( 0, $base_price - $amount );
				break;
			case 'fixed':
			case 'fixed_price':
				$price = max( 0, $amount );
				break;
			default:
				return false;
		}

		return number_format( (float) $price, 2, '.', '' );
	}
}
