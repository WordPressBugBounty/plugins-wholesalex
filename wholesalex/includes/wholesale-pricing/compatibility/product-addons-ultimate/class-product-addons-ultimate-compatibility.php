<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Preserve the existing loader path and public class name.
/**
 * Product Add-Ons Ultimate compatibility for WholesaleX product pricing.
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Apply wholesale discounts to the product before adding Plugin Republic options.
 */
class Wholesale_Pricing_Product_Addons_Ultimate_Compatibility {

	/**
	 * Add-on amounts for the current totals pass, keyed by cart item key.
	 *
	 * @var array<string, float>
	 */
	private $addon_prices = array();

	/**
	 * Cart objects whose price already includes add-ons.
	 *
	 * @var array<string, bool>
	 */
	private $composed_products = array();

	/**
	 * Object currently resolving legacy pricing without the wholesale cart cache.
	 *
	 * @var \WC_Product|null
	 */
	private $resolving_product;

	/**
	 * Register only when Product Add-Ons Ultimate is loaded.
	 */
	public function __construct() {
		if ( did_action( 'plugins_loaded' ) ) {
			$this->register_hooks();
		} else {
			add_action( 'plugins_loaded', array( $this, 'register_hooks' ), 20 );
		}
	}

	/**
	 * Keep integration hooks dormant when the add-ons plugin is inactive.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		if ( ! defined( 'PEWC_PLUGIN_VERSION' ) || ! function_exists( 'pewc_wc_calculate_total' ) ) {
			return;
		}

		// Legacy pricing runs at 5, PEWC at 10 and wholesale pricing at 999.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'prepare_cart_prices' ), 4 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'compose_cart_prices' ), 1002 );
		add_filter( 'wholesalex_ignore_wholesale_pricing', array( $this, 'ignore_composed_price' ), 20, 3 );
		add_filter( 'pewc_price_with_extras_before_calc_totals', array( $this, 'preserve_mini_cart_price' ), 20, 2 );
	}

	/**
	 * Check for additive options on an ordinary product cart line.
	 *
	 * Replacement calculations and child products retain their existing pricing.
	 * Flat-rate options are separate PEWC cart fees, not part of the unit delta.
	 *
	 * @param array $item Cart item.
	 * @return bool
	 */
	private function supports_item( array $item ): bool {
		$extras = $item['product_extras'] ?? array();

		return isset( $item['data'] ) && $item['data'] instanceof \WC_Product
			&& $item['data']->is_type( array( 'simple', 'variation' ) )
			&& empty( $item['free_product'] )
			&& empty( $item['_wholesalex_wp_bxgy_free_item'] )
			&& empty( $extras['use_calc_set_price'] )
			&& empty( $extras['products'] )
			&& empty( $extras['child_fields'] )
			&& isset( $extras['original_price'], $extras['price_with_extras'] )
			&& is_numeric( $extras['original_price'] )
			&& is_numeric( $extras['price_with_extras'] );
	}

	/**
	 * Expose only the product base to both WholesaleX pricing engines.
	 *
	 * Read PEWC's current delta on every pass: its quantity-dependent calculation
	 * fields may have changed it since the previous pass. Never cache it in session.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function prepare_cart_prices( $cart ): void {
		$this->addon_prices      = array();
		$this->composed_products = array();

		if ( ! $cart instanceof \WC_Cart || ( is_admin() && ! wp_doing_ajax() ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $key => $item ) {
			if ( ! $this->supports_item( $item ) ) {
				continue;
			}

			$product = $item['data'];
			$extras  = $item['product_extras'];

			// The new engine recalculates at 999. Bypass its previous cart cache
			// here while leaving legacy role prices and the tier resolver active.
			$this->resolving_product = $product;
			try {
				$sale_price = $product->get_sale_price();
				$base_price = '' !== $sale_price && false !== $sale_price && is_numeric( $sale_price )
					? $sale_price
					: $product->get_regular_price();
			} finally {
				$this->resolving_product = null;
			}

			if ( ! is_numeric( $base_price ) ) {
				continue;
			}

			$this->addon_prices[ $key ] = (float) $extras['price_with_extras'] - (float) $extras['original_price'];
			$product->set_price( $base_price );
			$cart->cart_contents[ $key ]['product_extras']['original_price']    = (float) $base_price;
			$cart->cart_contents[ $key ]['product_extras']['price_with_extras'] = (float) $base_price;
		}
	}

	/**
	 * Add options once, after wholesale and tier prices have been resolved.
	 *
	 * Synchronizing PEWC's two prices also keeps its mini-cart callback and cart
	 * display filters consistent, and preserves the delta when the session reloads.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function compose_cart_prices( $cart ): void {
		if ( ! $cart instanceof \WC_Cart ) {
			return;
		}

		foreach ( $this->addon_prices as $key => $addon_price ) {
			if ( ! isset( $cart->cart_contents[ $key ] ) || ! $this->supports_item( $cart->cart_contents[ $key ] ) ) {
				continue;
			}

			$product = $cart->cart_contents[ $key ]['data'];
			$base    = (float) $product->get_price( 'edit' );
			$price   = max( 0, $base + $addon_price );

			$product->set_price( $price );
			$cart->cart_contents[ $key ]['product_extras']['original_price']    = $base;
			$cart->cart_contents[ $key ]['product_extras']['price_with_extras'] = $price;
			$this->composed_products[ spl_object_hash( $product ) ]             = true;
		}

		$this->addon_prices = array();
	}

	/**
	 * Let WooCommerce read the final unit price for these exact cart objects.
	 *
	 * @param bool        $ignore Existing decision.
	 * @param \WC_Product $product Product object.
	 * @param string      $context Price context.
	 * @return bool
	 */
	public function ignore_composed_price( $ignore, $product, $context ): bool {
		if ( $ignore || ! $product instanceof \WC_Product ) {
			return (bool) $ignore;
		}

		if ( $product === $this->resolving_product && 'sale_price' === $context ) {
			return true;
		}

		return 'price' === $context && isset( $this->composed_products[ spl_object_hash( $product ) ] );
	}

	/**
	 * Protect PEWC's mini-cart assignment, including restored cart sessions.
	 *
	 * @param float|string $price PEWC unit price.
	 * @param array        $item Cart item.
	 * @return float|string
	 */
	public function preserve_mini_cart_price( $price, $item ) {
		if ( doing_action( 'woocommerce_before_mini_cart_contents' ) && $this->supports_item( $item ) ) {
			$this->composed_products[ spl_object_hash( $item['data'] ) ] = true;
		}

		return $price;
	}
}
