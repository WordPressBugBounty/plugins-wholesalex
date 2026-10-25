<?php
/**
 * WholesaleX Wholesale Pricing - Rule Engine
 *
 * Loads active wholesale-pricing rules, evaluates targeting/schedule/restrictions,
 * and delegates additional computed results to extension providers.
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
	 * Whether shared pricing hooks have already been attached.
	 *
	 * @var bool
	 */
	private $pricing_hooks_registered = false;

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
	 * Isolated builder requests never use the administrator's live cart.
	 *
	 * @var bool
	 */
	private $builder_preview = false;




	/**
	 * Constructor.
	 *
	 * @param bool $register_hooks Whether to register storefront hooks.
	 */
	public function __construct( bool $register_hooks = true ) {
		$this->regular_discount = Wholesale_Pricing_Rule_Registry::processor( 'regular' );
		$this->product_discount = Wholesale_Pricing_Rule_Registry::processor( 'product_discount' );
		$this->cart_discount    = Wholesale_Pricing_Rule_Registry::processor( 'cart_discount' );
		$this->bogo_discount    = Wholesale_Pricing_Rule_Registry::processor( 'bogo_discount' );

		if ( ! $register_hooks ) {
			return;
		}

		add_action( 'wp_loaded', array( $this, 'load_valid_rules' ) );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'update_cart_item_prices' ), 999 );
		do_action( 'wholesalex_wholesale_pricing_engine_ready', $this );
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

		$user_id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		$role_id = wholesalex()->get_user_role( $user_id );

		$rules = Wholesale_Pricing::get_all_rules();

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}
			if ( isset( $rule['conditions'] ) && ( ! is_array( $rule['conditions'] ) || ( isset( $rule['conditions']['tiers'] ) && ! is_array( $rule['conditions']['tiers'] ) ) ) ) {
				continue; }
			if ( isset( $rule['cart']['conditions'] ) && ! is_array( $rule['cart']['conditions'] ) ) {
				continue; }

			if ( Wholesale_Pricing::has_provider_record( $rule ) ) {
				continue;
			}

			$unavailable_feature = Wholesale_Pricing_Rule_Registry::unavailable_feature( $rule );
			if ( '' !== $unavailable_feature ) {
				do_action( 'wholesalex_wholesale_pricing_rule_unavailable', (string) ( $rule['id'] ?? '' ), $unavailable_feature, $rule );
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

			if ( ! in_array( $rule['rule_type'] ?? '', array( 'wholesale_pricing', 'product_discount' ), true ) ) {
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

		if ( ! empty( $this->valid_rules ) ) {
			$this->register_pricing_hooks();
		}

		if ( ! empty( $this->valid_cart_discount_rules ) ) {
			$this->register_cart_discount_hooks();
		}

		if ( ! empty( $this->valid_bogo_discount_rules ) ) {
			$this->register_bogo_discount_hooks();
		}
	}

	/**
	 * Register price hooks used by product, variation, and cart totals.
	 *
	 * @return void
	 */
	public function register_pricing_hooks(): void {
		if ( $this->pricing_hooks_registered ) {
			return; }
		$this->pricing_hooks_registered = true;
		add_filter( 'woocommerce_product_get_price', array( $this, 'filter_price' ), 8, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'filter_price' ), 8, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( $this, 'filter_sale_price' ), 8, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'filter_sale_price' ), 8, 2 );
		add_filter( 'woocommerce_variation_prices_sale_price', array( $this, 'filter_sale_price' ), 8, 2 );
		add_filter( 'woocommerce_variation_prices_price', array( $this, 'filter_variation_price' ), 8, 2 );
		add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'variation_price_hash' ), 8, 3 );
		add_filter( 'woocommerce_get_price_html', array( $this, 'filter_price_html' ), 20, 2 );
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

		$price_data = $this->get_product_price_data( $product );

		return apply_filters( 'wholesalex_wholesale_pricing_price', false === $price_data ? $price : $price_data['price'], $product, $price );
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
			add_action( 'wholesalex_after_frontend_enqueue_scripts', array( $this, 'enqueue_promo_toggle_assets' ), 10 );
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
			add_action( 'wholesalex_after_frontend_enqueue_scripts', array( $this, 'enqueue_promo_toggle_assets' ), 10 );
		}

		if ( $this->bogo_discount->has_badge_rules( $this->valid_bogo_discount_rules ) ) {
			add_filter( 'wopb_after_loop_image', array( $this, 'wopb_render_bogo_discount_badge' ), 10 );
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_bogo_discount_badge' ), 10 );
			add_action( 'wholesalex_after_frontend_enqueue_scripts', array( $this, 'enqueue_single_product_bogo_discount_badge' ), 10 );
			add_action( 'wp_head', array( $this, 'add_bogo_discount_badge_css' ), 7 );
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
			echo wp_kses_post( $this->bogo_discount->get_badge_markup( $this->valid_bogo_discount_rules, false ) );
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
	 * Enqueue the shared promo popup toggle script on single product pages.
	 *
	 * Registered independently of promo rendering so the handler is present for
	 * variation markup that WooCommerce loads over AJAX.
	 *
	 * @return void
	 */
	public function enqueue_promo_toggle_assets(): void {
		if ( ! is_product() ) {
			return;
		}

		if ( $this->cart_discount ) {
			$this->cart_discount->enqueue_promo_toggle_script();
		} elseif ( $this->bogo_discount ) {
			$this->bogo_discount->enqueue_promo_toggle_script();
		}
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
			wp_add_inline_style( 'wholesalex', wholesalex()->sanitize_inline_css( $css ) );
		}
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

		$price_data = $this->get_product_price_data( $product );

		return apply_filters( 'wholesalex_wholesale_pricing_price', false === $price_data ? $sale_price : $price_data['price'], $product, $sale_price );
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

		$price_data = $this->get_product_price_data( $product );

		return apply_filters( 'wholesalex_wholesale_pricing_price', false === $price_data ? $price : $price_data['price'], $product, $price );
	}

	/**
	 * Render wholesale-pricing price HTML.
	 *
	 * Regular discounts use the rule label as the visible wholesale-price text.
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

		if ( ! is_user_logged_in() && 'yes' === wholesalex()->get_setting( '_settings_login_to_view_price', 'no' ) ) {
			return $price_html;
		}
		if ( 'yes' === wholesalex()->get_setting( '_settings_hide_retail_price' ) && 'yes' === wholesalex()->get_setting( '_settings_hide_wholesalex_price' ) ) {
			return ''; }

		if ( $product->is_type( 'variable' ) ) {
			$range_data = $this->get_variable_price_range_data( $product );

			if ( false !== $range_data ) {
				return $this->format_variable_price_range_html( $product, $range_data );
			}

			return $price_html;
		}

		$price_data    = $this->get_product_price_data( $product );
		$provided_html = apply_filters( 'wholesalex_wholesale_pricing_price_html', null, $product, $price_data, $price_html, $this );
		if ( null !== $provided_html ) {
			return $provided_html;
		}

		if ( false === $price_data || empty( $price_data['rule'] ) ) {
			return $price_html;
		}

		$base_price      = $this->get_base_price( $product );
		$wholesale_price = (float) $price_data['price'];

		if ( $base_price <= 0 || $wholesale_price < 0 || $wholesale_price >= $base_price ) {
			return $price_html;
		}

		$base_html      = wc_price( wc_get_price_to_display( $product, array( 'price' => $base_price ) ) );
		$wholesale_html = wc_price( wc_get_price_to_display( $product, array( 'price' => $wholesale_price ) ) );
		$rule           = $price_data['rule'];

		if ( 'regular' === $rule['discount_type'] ) {
			$label                 = $this->get_discount_label( $rule );
			$label                 = '' !== $label ? '<span class="wsx-wholesale-price-label">' . esc_html( $label ) . '</span>' : '';
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

			$variation_active = $variation->is_purchasable() && ( ! method_exists( $variation, 'variation_is_visible' ) || $variation->variation_is_visible() );
			if ( ! $variation_active ) {
				continue;
			}

			$price_data      = $this->get_product_price_data( $variation );
			$base_price      = $this->get_base_price( $variation );
			$has_discount    = false !== $price_data && $price_data['price'] > 0 && $price_data['price'] < $base_price;
			$effective_price = $has_discount ? $price_data['price'] : $variation->get_price();

			if ( ! is_numeric( $effective_price ) ) {
				continue;
			}

			// Unmatched variations still contribute their actual price to the range.
			$base_prices[]      = wc_get_price_to_display( $variation, array( 'price' => $base_price ) );
			$wholesale_prices[] = wc_get_price_to_display( $variation, array( 'price' => $effective_price ) );
			if ( $has_discount && false === $applied_rule ) {
				$applied_rule = $price_data['rule'];
			}
		}

		if ( false === $applied_rule || empty( $wholesale_prices ) ) {
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

		$base_min  = (float) $range_data['base_min'];
		$base_max  = (float) $range_data['base_max'];
		$base_html = $base_min === $base_max ? wc_price( $base_min ) : wc_format_price_range( $base_min, $base_max );
		$rule      = isset( $range_data['rule'] ) && is_array( $range_data['rule'] ) ? $range_data['rule'] : array();

		$provided_html = apply_filters( 'wholesalex_wholesale_pricing_range_html', null, $product, $range_data, $base_html, $wholesale_html );
		if ( null !== $provided_html ) {
			return $provided_html;
		}

		if ( isset( $rule['discount_type'] ) && 'regular' === $rule['discount_type'] ) {
			$label                 = $this->get_discount_label( $rule );
			$label                 = '' !== $label ? '<span class="wsx-wholesale-price-label">' . esc_html( $label ) . '</span>' : '';
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
	 * Get wholesale price markup.
	 *
	 * @param string $label_html Label html.
	 * @param string $price_html Price html.
	 * @param string $suffix_html Suffix html.
	 */
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

		return ' <small class="woocommerce-price-suffix">' . wp_kses_post( $suffix ) . '</small>';
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
		$hash[]  = 'wsx_wholesale_pricing_' . md5( wp_json_encode( $context ) );
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

		foreach ( $fees as $fee_key => $fee ) {
			if ( empty( $fee['discount'] ) || empty( $fee['name'] ) ) {
				continue;
			}

			// Keep the saved label intact; only the internal fee ID must be unique.
			$cart->fees_api()->add_fee(
				array(
					'id'      => 'wholesalex_wholesale_cart_discount_' . $fee_key,
					'name'    => $fee['name'],
					'amount'  => -1 * (float) $fee['discount'],
					'taxable' => (bool) apply_filters( 'wholesalex_cart_discount_is_taxable', false, $fee ),
				)
			);

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
	 * Return calculated wholesale-pricing data for a product.
	 *
	 * @param \WC_Product $product  Product object.
	 * @param int|null    $quantity Optional quantity context.
	 * @return array|false
	 */
	private function get_product_price_data( \WC_Product $product, ?int $quantity = null ) {
		$cache_key = $product->get_id() . ':' . ( null === $quantity ? 'auto' : $quantity );

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

			$price = $this->calculate_regular_price( $rule, $product, $base_price );
			if ( false !== $price ) {
				$candidate = array(
					'price' => $price,
					'rule'  => $rule,
				);
			}

			if ( false === $candidate ) {
				continue;
			}

			if ( false === $best_price_data || (float) $candidate['price'] < (float) $best_price_data['price'] ) {
				$best_price_data = $candidate;
			}
		}

		$this->price_cache[ $cache_key ] = apply_filters( 'wholesalex_wholesale_pricing_price_data', $best_price_data, $product, $quantity, $base_price, $this );
		return $this->price_cache[ $cache_key ];
	}

	/**
	 * Prepare an isolated, read-only builder rule set for an explicit audience.
	 *
	 * @param array  $rules Saved rules visible to the manager.
	 * @param array  $draft Current sanitized edit.
	 * @param string $role_id Selected role.
	 * @param int    $user_id Selected user, or zero for role-only preview.
	 * @return void
	 */
	public function prepare_builder_rules( array $rules, array $draft, string $role_id, int $user_id ): void {
		$this->builder_preview = true;
		$this->rules_loaded    = true;
		$this->valid_rules     = array();
		$this->price_cache     = array();
		foreach ( $rules as $rule ) {
			if ( is_array( $rule ) && (string) ( $rule['id'] ?? '' ) !== (string) ( $draft['id'] ?? '' ) ) {
				$this->append_builder_rule( $rule, $role_id, $user_id, false );
			}
		}
		// Preview the current edit as enabled, without saving or activating it.
		$draft['status'] = 'active';
		$this->append_builder_rule( $draft, $role_id, $user_id, true );
		usort( $this->valid_rules, array( $this, 'compare_by_priority' ) );
	}

	/**
	 * Retain storefront availability, schedules, targeting, and extension checks.
	 *
	 * @param array  $rule Saved or sanitized draft rule.
	 * @param string $role_id Selected role.
	 * @param int    $user_id Selected user.
	 * @param bool   $draft Whether this is the current edit.
	 * @return void
	 */
	private function append_builder_rule( array $rule, string $role_id, int $user_id, bool $draft ): void {
		if ( Wholesale_Pricing::has_provider_record( $rule ) || '' !== Wholesale_Pricing_Rule_Registry::unavailable_feature( $rule ) ) {
			return;
		}
		if ( ! $this->is_rule_available_for_user( $rule, $role_id, $user_id ) ) {
			return;
		}
		$runtime             = $this->normalize_rule_for_runtime( $rule, $role_id );
		$runtime['is_draft'] = $draft;
		$this->valid_rules[] = $runtime;
	}

	/**
	 * Return matching pricing rules and the lowest price for a builder product.
	 *
	 * @param \WC_Product $product Product or variation being previewed.
	 * @return array
	 */
	public function get_builder_product_pricing( \WC_Product $product ): array {
		$base       = $this->get_base_price( $product );
		$candidates = array();
		$winner     = null;
		foreach ( $this->valid_rules as $rule ) {
			$quantity = $this->get_display_quantity_for_rule( $product, $rule );
			if ( $base <= 0 || ! $this->can_rule_price_product( $rule, $product, $quantity, true ) ) {
				continue;
			}
			$price = $this->calculate_regular_price( $rule, $product, $base );
			if ( false === $price ) {
				continue;
			}
			$discount     = $rule['rule'];
			$price        = (float) $price;
			$candidates[] = array(
				'id'          => $rule['id'],
				'title'       => $rule['title'],
				'rule_type'   => $rule['rule_type'],
				'is_draft'    => $rule['is_draft'],
				'price'       => $price,
				'discount'    => max( 0, $base - $price ),
				'amount'      => $discount['_discount_amount'] ?? $discount['amount'] ?? 0,
				'amount_type' => $discount['_discount_type'] ?? $discount['amount_type'] ?? 'percentage',
				'quantity'    => $quantity,
				'label'       => $this->get_discount_label( $rule ),
				'applied'     => false,
			);
			if ( null !== $price && ( null === $winner || $price < $candidates[ $winner ]['price'] ) ) {
				$winner = count( $candidates ) - 1;
			}
		}
		if ( null !== $winner ) {
			$candidates[ $winner ]['applied'] = true;
		}
		return array(
			'product_id' => $product->get_id(),
			'name'       => $product->get_name(),
			'base_price' => $base,
			'price'      => null === $winner ? $base : $candidates[ $winner ]['price'],
			'label'      => null === $winner ? '' : $candidates[ $winner ]['label'],
			'rules'      => $candidates,
		);
	}



	/**
	 * Calculate a non-tiered price with its dedicated rule handler.
	 *
	 * @param array       $rule       Normalized runtime rule.
	 * @param \WC_Product $product    Current product.
	 * @param float       $base_price Price before the discount.
	 * @return float|false
	 */
	public function calculate_regular_price( array $rule, \WC_Product $product, float $base_price ) {
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
	 * Check whether a rule can price a product in the current context.
	 *
	 * @param array       $rule     Normalized rule.
	 * @param \WC_Product $product  Product object.
	 * @param int|null    $quantity Optional quantity context.
	 * @param bool        $preview_conditions Whether display pricing may preview cart conditions.
	 * @return bool
	 */
	public function can_rule_price_product( array $rule, \WC_Product $product, ?int $quantity = null, bool $preview_conditions = false ): bool {
		if ( ! $this->is_product_eligible_for_rule( $product, $rule ) ) {
			return false;
		}

		if ( ! $this->conditions_pass( $rule, $product, $quantity, $preview_conditions ) ) {
			return false;
		}

		if ( 'product_discount' === ( $rule['rule_type'] ?? '' ) ) {
			return true;
		}

		return (bool) apply_filters( 'wholesalex_wholesale_pricing_rule_eligible', true, $rule, $product, $quantity, $this );
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

		if ( ! $this->schedule_is_active( isset( $rule['schedule'] ) && is_array( $rule['schedule'] ) ? $rule['schedule'] : array() ) ) {
			return false;
		}

		if ( ! Wholesale_Pricing_Condition_Engine::user_role_matches( $rule, $role_id, $user_id, $this->builder_preview ) ) {
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
	 * Get runtime restrictions.
	 *
	 * @param array $restrictions Configured rule restrictions.
	 */
	private function get_runtime_restrictions( array $restrictions ): array {
		return $restrictions;
	}

	/**
	 * Normalize a saved rule into a runtime entry.
	 *
	 * @param array  $rule    Raw saved rule.
	 * @param string $role_id Current role.
	 * @return array
	 */
	public function normalize_rule_for_runtime( array $rule, string $role_id ): array {
		$filter_data = Wholesale_Pricing_Condition_Engine::get_runtime_product_filter( $rule );

		$restrictions = isset( $rule['restrictions'] ) && is_array( $rule['restrictions'] ) ? $rule['restrictions'] : array();

		return array(
			'id'                  => isset( $rule['id'] ) ? sanitize_text_field( $rule['id'] ) : '',
			'title'               => isset( $rule['title'] ) ? $rule['title'] : '',
			'owner_id'            => absint( $rule['owner_id'] ?? 0 ),
			'owner_type'          => sanitize_key( $rule['owner_type'] ?? 'admin' ),
			'rule_type'           => 'product_discount' === ( $rule['rule_type'] ?? '' ) ? 'product_discount' : 'wholesale_pricing',
			'discount_type'       => 'regular',
			'rule'                => 'product_discount' === ( $rule['rule_type'] ?? '' ) ? ( $rule['product_discount'] ?? array() ) : ( $rule['regular'] ?? array() ),
			'filter'              => $filter_data['filter'],
			'conditions'          => array( 'tiers' => isset( $rule['conditions']['tiers'] ) ? wholesalex()->filter_empty_conditions( $rule['conditions']['tiers'] ) : array() ),
			'restrictions'        => 'product_discount' === ( $rule['rule_type'] ?? '' ) ? array() : $this->get_runtime_restrictions( $restrictions ),
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
	 * Check whether the current product matches a rule's product targeting.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $rule    Normalized rule.
	 * @return bool
	 */
	public function is_product_eligible_for_rule( \WC_Product $product, array $rule ): bool {
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
	public function conditions_pass( array $rule, ?\WC_Product $product = null, ?int $quantity = null, bool $preview_conditions = false ): bool {
		$context = (array) apply_filters( 'wholesalex_wholesale_pricing_condition_context', array(), $rule, $product, $quantity, $preview_conditions, $this );
		if ( $this->builder_preview ) {
			$context['ignore_live_cart'] = true;
		}
		return Wholesale_Pricing_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'], $context );
	}

	/**
	 * Check whether the current request is safe for pre-cart condition previews.
	 *
	 * @return bool
	 */
	public function can_preview_conditions_for_request(): bool {
		if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return base price for discount calculation.
	 *
	 * @param \WC_Product $product Product object.
	 * @return float
	 */
	public function get_base_price( \WC_Product $product ): float {
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
	public function product_sale_is_active( \WC_Product $product ): bool {
		$sale_price = $product->get_sale_price( 'edit' );

		if ( '' === $sale_price || null === $sale_price ) {
			return false;
		}

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Preserve the existing local-time comparison contract for saved schedules.
		$now  = time();
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
	 * Check a wholesale-pricing schedule. End date is inclusive for the saved date.
	 *
	 * @param array $schedule Schedule data.
	 * @return bool
	 */
	public function schedule_is_active( array $schedule ): bool {
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
	public function get_cart_quantity( \WC_Product $product, array $rule ): int {
		if ( $this->builder_preview ) {
			return 1;
		}
		if ( ! WC()->cart ) {
			return 0;
		}

		$quantity = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {

			$cart_product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( (int) $cart_product_id === (int) $product->get_id() ) {
				$quantity += absint( $cart_item['quantity'] );
			}
		}

		$provided = apply_filters( 'wholesalex_wholesale_pricing_cart_quantity', $quantity, $product, $rule, null );
		return is_numeric( $provided ) && is_finite( (float) $provided ) && (float) $provided >= 0 && floor( (float) $provided ) === (float) $provided && (float) $provided <= PHP_INT_MAX && (int) $provided >= 0 ? (int) $provided : $quantity;
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
	public function get_cart_quantity_for_update( \WC_Product $product, array $rule, string $cart_item_key, int $new_quantity ): int {
		if ( ! WC()->cart ) {
			return $new_quantity;
		}

		$quantity = 0;

		foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
			$item_quantity = $key === $cart_item_key ? $new_quantity : absint( $cart_item['quantity'] );

			$cart_product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( (int) $cart_product_id === (int) $product->get_id() ) {
				$quantity += $item_quantity;
			}
		}

		$provided = apply_filters(
			'wholesalex_wholesale_pricing_cart_quantity',
			$quantity,
			$product,
			$rule,
			array(
				'cart_item_key' => $cart_item_key,
				'new_quantity'  => $new_quantity,
			)
		);
		return is_numeric( $provided ) && is_finite( (float) $provided ) && (float) $provided >= 0 && floor( (float) $provided ) === (float) $provided && (float) $provided <= PHP_INT_MAX && (int) $provided >= 0 ? (int) $provided : $quantity;
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
	public function get_display_quantity_for_rule( \WC_Product $product, array $rule ): int {
		$quantity = max( 1, $this->get_cart_quantity( $product, $rule ) );
		$result   = apply_filters( 'wholesalex_wholesale_pricing_display_quantity', $quantity, $product, $rule, $this );
		return is_numeric( $result ) && is_finite( (float) $result ) && (float) $result >= 1 && (float) $result < PHP_INT_MAX ? max( 1, (int) $result ) : $quantity;
	}

	/**
	 * Compare two runtime rules by product/user targeting priority.
	 *
	 * @param array $a First rule.
	 * @param array $b Second rule.
	 * @return int
	 */
	public function compare_by_priority( array $a, array $b ): int {
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
	public function set_discounted_product( int $product_id ): void {
		if ( is_admin() || ! WC()->session ) {
			return;
		}

		$products                = WC()->session->get( '__wholesalex_discounted_products' );
		$products                = is_array( $products ) ? $products : array();
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

		$rules             = WC()->session->get( '__wholesalex_wholesale_pricing_cart_discount_rules' );
		$rules             = is_array( $rules ) ? $rules : array();
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

		$rules             = WC()->session->get( '__wholesalex_wholesale_pricing_bogo_discount_rules' );
		$rules             = is_array( $rules ) ? $rules : array();
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
	/**
	 * Apply non-tier and computed provider prices to cart lines.
	 *
	 * @param \WC_Cart $cart Current cart.
	 * @return void
	 */
	public function update_cart_item_prices( $cart ): void {
		if ( ! $cart instanceof \WC_Cart || ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) ) {
			return;
		}
		$this->load_valid_rules();
		$this->price_cache = array();
		do_action( 'wholesalex_wholesale_pricing_cart_start', $cart );
		foreach ( $cart->get_cart() as $key => $item ) {
			if ( empty( $item['data'] ) || ! $item['data'] instanceof \WC_Product ) {
				continue;
			}
			$data = $this->get_product_price_data( $item['data'], absint( $item['quantity'] ) );
			$data = apply_filters( 'wholesalex_wholesale_pricing_cart_price_data', $data, $item, $key, $cart, $this );
			if ( is_array( $data ) && isset( $data['price'] ) && is_numeric( $data['price'] ) ) {
				$item['data']->set_price( $data['price'] );
				$this->set_discounted_product( $item['data']->get_id() );
			}
		}
	}

	/** Return canonical non-tier rules for computed providers. */
	public function get_runtime_rules(): array {
		$this->load_valid_rules();
		return $this->valid_rules;
	}
}
