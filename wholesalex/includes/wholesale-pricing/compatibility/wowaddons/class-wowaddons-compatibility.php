<?php
/**
 * Wholesale Pricing compatibility for WowAddons.
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Keep WholesaleX wholesale-pricing rules compatible with WowAddons cart totals.
 */
class Wholesale_Pricing_WowAddons_Compatibility {

	/**
	 * WowAddons composes product + addon prices at priority 999999.
	 */
	private const AFTER_WOWADDONS_CART_PRICE_PRIORITY = 1000000;

	/**
	 * Product object hashes whose cart prices were composed by WowAddons.
	 *
	 * @var array<string, true>
	 */
	private $wowaddons_priced_products = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action(
			'woocommerce_before_calculate_totals',
			array( $this, 'capture_wowaddons_priced_cart_items' ),
			self::AFTER_WOWADDONS_CART_PRICE_PRIORITY
		);
		add_filter( 'wholesalex_ignore_wholesale_pricing', array( $this, 'ignore_wholesale_filter_for_wowaddons_price' ), 20, 3 );
	}

	/**
	 * Track cart product objects after WowAddons has added option prices.
	 *
	 * Wholesale Pricing sets the discounted/tiered product price early in cart
	 * calculation. WowAddons then adds selected option prices and calls
	 * WC_Product::set_price(). Without this handoff, the WholesaleX price filter
	 * can later return only the wholesale product price and hide the addon cost
	 * from mini-cart/cart totals.
	 *
	 * @param \WC_Cart $cart WooCommerce cart.
	 * @return void
	 */
	public function capture_wowaddons_priced_cart_items( $cart ): void {
		$this->wowaddons_priced_products = array();

		if ( ! $this->is_wowaddons_active() || ! $cart instanceof \WC_Cart ) {
			return;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
				continue;
			}

			if ( empty( $cart_item['prad_selection']['price'] ) ) {
				continue;
			}

			$this->wowaddons_priced_products[ spl_object_hash( $cart_item['data'] ) ] = true;
		}
	}

	/**
	 * Let WooCommerce read WowAddons' composed cart price for tracked products.
	 *
	 * @param bool        $ignore  Whether Wholesale Pricing should be ignored.
	 * @param \WC_Product $product Product object.
	 * @param string      $context Price filter context.
	 * @return bool
	 */
	public function ignore_wholesale_filter_for_wowaddons_price( $ignore, $product, $context ): bool {
		if ( $ignore || ! $product instanceof \WC_Product || empty( $this->wowaddons_priced_products ) ) {
			return (bool) $ignore;
		}

		if ( ! in_array( $context, array( 'price', 'sale_price', 'variation_price' ), true ) ) {
			return (bool) $ignore;
		}

		return isset( $this->wowaddons_priced_products[ spl_object_hash( $product ) ] );
	}

	/**
	 * Check whether WowAddons is available in the current request.
	 *
	 * @return bool
	 */
	private function is_wowaddons_active(): bool {
		return defined( 'PRAD_VER' ) || function_exists( 'product_addons' );
	}
}
