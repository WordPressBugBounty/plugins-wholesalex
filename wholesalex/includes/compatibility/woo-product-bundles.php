<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Preserve existing include paths and template overrides.
/**
 * Compatibility with product bundle and composite pricing.
 *
 * @package WHOLESALEX
 * @since 2.0.14
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preserve prices managed by product bundle plugins.
 */
class WHOLESALEX_WooProduct_Bundles {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'wholesalex_ignore_dynamic_price', array( $this, 'preserve_bundle_price' ), 10, 4 );
		add_filter( 'wholesalex_ignore_wholesale_pricing', array( $this, 'preserve_bundle_price' ), 10, 4 );
		// Use the same filter name the main plugin applies: wholesalex_ignore_dynamic_price
		// It passes ( $ignore, $p_product, $context ) so we accept three args and
		// detect bundle cart items so the dynamic pricing engine can skip them.
		add_filter( 'wholesalex_ignore_dynamic_price', array( $this, 'is_product_bundle' ), 10, 3 );
	}

	/**
	 * Leave bundle parent and child prices to the bundle's pricing engine.
	 *
	 * Cart product objects carry prices for the selected variations and bundle
	 * discounts. Replacing them with catalog prices restores the initial bundle
	 * price. Match objects rather than IDs so a standalone copy of a bundled
	 * variation can still receive wholesale pricing in the same cart.
	 *
	 * @param bool        $ignore    Existing exclusion.
	 * @param \WC_Product $product   Product being priced.
	 * @param string      $context   Pricing context.
	 * @param array|null  $cart_item Current cart line, when available.
	 * @return bool
	 */
	public function preserve_bundle_price( $ignore, $product, $context = '', $cart_item = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Shared pricing filter signature.
		if ( $ignore || ! $product instanceof \WC_Product ) {
			return $ignore;
		}

		if ( $product->is_type( array( 'yith_bundle', 'woosb', 'bundle', 'composite' ) ) ) {
			return true;
		}

		if ( null !== $cart_item ) {
			return $this->has_bundle_cart_data( $cart_item );
		}

		// Read contents directly: get_cart() can trigger session loading while
		// product price filters are already running during session restoration.
		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->cart_contents as $item ) {
				if ( isset( $item['data'] ) && $item['data'] === $product && $this->has_bundle_cart_data( $item ) ) {
					return true;
				}
			}
		}

		return $ignore;
	}

	/**
	 * Identify bundle lines, including WPC Composite parents and components.
	 *
	 * @param array $item Cart line.
	 * @return bool
	 */
	private function has_bundle_cart_data( $item ) {
		return isset( $item['bundled_by'] ) || isset( $item['woosb_parent_id'] ) ||
			isset( $item['wooco_ids'] ) || isset( $item['wooco_parent_id'] ) || isset( $item['wooco_parent_key'] );
	}

	/**
	 * Determine whether we should ignore dynamic pricing for a product because
	 * it is part of a WPC Product Bundle.
	 *
	 * @param bool       $ignore  Current ignore flag.
	 * @param WC_Product $p_product Product object.
	 * @param string     $context Context string where filter is applied.
	 * @return bool True to ignore dynamic pricing for this product.
	 */
	public function is_product_bundle( $ignore, $p_product, $context = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Preserve the public callback signature.
		// Only proceed if WPC Product Bundles is active.
		if ( ! function_exists( 'is_plugin_active' ) || ! is_plugin_active( 'woo-product-bundle/wpc-product-bundles.php' ) ) {
			return $ignore;
		}
		global $post;

		// Be defensive: if product is not a WC_Product, return original value.
		if ( ! is_object( $p_product ) || ! method_exists( $p_product, 'get_id' ) ) {
			return $ignore;
		}

		$p_product_id = (int) $p_product->get_id();

		// Check if bundle info is stored on current product (when it's the bundle parent).
		if ( isset( $post->ID ) && $post->ID && is_product() ) {
			$bundle_ids = get_post_meta( $post->ID, 'woosb_ids', true );
			if ( ! empty( $bundle_ids ) && is_array( $bundle_ids ) ) {
				// Check if this is the bundle parent page, ignore dynamic pricing.
				// Also check if current product is a child in this bundle.
				foreach ( $bundle_ids as $bundled_item ) {
					if ( isset( $bundled_item['id'] ) && (int) $bundled_item['id'] === $p_product_id ) {
						return true;
					}
				}
			}
		}

		// Check if this is a bundled child product in cart context.
		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {
				// Check if cart item is part of a bundle (has parent or is parent).
				if ( isset( $cart_item['woosb_ids'] ) || isset( $cart_item['woosb_parent_id'] ) ) {
					if ( isset( $cart_item['data'] ) && $cart_item['data'] === $p_product ) {
						return true;
					}
				}
			}
		}

		return $ignore;
	}
}
