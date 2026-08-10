<?php
/**
 * WholesaleX User Roles - Tax Rules
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles role-level tax exemption and tax class mapping rules.
 */
class User_Roles_Tax_Rules {

	/**
	 * Cached tax rule for the current request.
	 *
	 * @var array|null
	 */
	private $rule = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_get_tax_class', array( $this, 'filter_product_tax_class' ), 20, 2 );
		add_filter( 'woocommerce_product_variation_get_tax_class', array( $this, 'filter_product_tax_class' ), 20, 2 );
		add_filter( 'woocommerce_package_rates', array( $this, 'maybe_zero_shipping_taxes' ), 20, 2 );
		add_filter( 'woocommerce_order_is_vat_exempt', array( $this, 'filter_order_tax_exempt' ), 20, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'maybe_mark_order_tax_exempt' ), 20 );
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'maybe_mark_order_tax_exempt' ), 20 );
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'maybe_zero_order_item_taxes' ), 20, 2 );
	}

	/**
	 * Keep an all-products exemption active when WooCommerce recalculates an order.
	 *
	 * @param bool      $is_exempt Existing VAT-exempt status.
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function filter_order_tax_exempt( $is_exempt, $order ) {
		if ( $is_exempt || $this->is_admin_request() || ! $order instanceof \WC_Order || ! $this->is_full_order_tax_exempt( $order ) ) {
			return $is_exempt;
		}

		$order->update_meta_data( 'is_vat_exempt', 'yes' );
		return true;
	}

	/**
	 * Persist an all-products exemption on newly created orders.
	 *
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	public function maybe_mark_order_tax_exempt( $order ) {
		if ( $this->is_admin_request() || ! $order instanceof \WC_Order || ! $this->is_full_order_tax_exempt( $order ) ) {
			return;
		}

		$order->update_meta_data( 'is_vat_exempt', 'yes' );
	}

	/**
	 * Check whether the current role rule exempts the complete order.
	 *
	 * @param \WC_Order|null $order Order object.
	 * @return bool
	 */
	private function is_full_order_tax_exempt( $order = null ) {
		$rule = $this->get_rule();
		if ( empty( $rule ) || 'yes' !== $rule['tax_exempted'] || 'all_products' !== $rule['filter'] ) {
			return false;
		}

		if ( ! $order instanceof \WC_Order ) {
			return true;
		}

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product = $item->get_product();
			if ( ! $product || ! $this->product_matches_rule( $product, $rule ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Keep scoped exemptions intact when WooCommerce recalculates order taxes.
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @param array          $calculate_tax_for Tax location.
	 * @return void
	 */
	public function maybe_zero_order_item_taxes( $item, $calculate_tax_for ) {
		if ( ! $item instanceof \WC_Order_Item_Product || $this->is_admin_request() ) {
			return;
		}

		if ( $this->is_product_tax_exempt( $item->get_product() ) ) {
			$item->set_taxes( false );
		}
	}

	/**
	 * Check whether the current role rule exempts a product.
	 *
	 * @param \WC_Product|false $product Product object.
	 * @return bool
	 */
	private function is_product_tax_exempt( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		$rule = $this->get_rule();
		return ! empty( $rule ) && 'yes' === $rule['tax_exempted'] && $this->product_matches_rule( $product, $rule );
	}

	/**
	 * Apply role tax rule to matching products.
	 *
	 * @param string      $tax_class Existing tax class.
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	public function filter_product_tax_class( $tax_class, $product ) {
		if ( $this->is_admin_request() || ! $product instanceof \WC_Product ) {
			return $tax_class;
		}

		$rule = $this->get_rule();
		if ( empty( $rule ) || ! $this->product_matches_rule( $product, $rule ) ) {
			return $tax_class;
		}

		if ( 'yes' === $rule['tax_exempted'] ) {
			return 'zero-rate';
		}

		if ( 'no' === $rule['tax_exempted'] && '' !== $rule['tax_class'] ) {
			return $rule['tax_class'];
		}

		return $tax_class;
	}

	/**
	 * Remove shipping taxes when a role-level all-products tax exemption applies.
	 *
	 * @param array $rates Shipping rates.
	 * @param array $package Shipping package.
	 * @return array
	 */
	public function maybe_zero_shipping_taxes( $rates, $package ) {
		$rule = $this->get_rule();
		if ( empty( $rule ) || 'yes' !== $rule['tax_exempted'] || 'all_products' !== $rule['filter'] ) {
			return $rates;
		}

		foreach ( $rates as $rate_key => $rate ) {
			if ( empty( $rate->taxes ) || ! is_array( $rate->taxes ) ) {
				continue;
			}

			$rates[ $rate_key ]->taxes = array_map(
				function () {
					return 0;
				},
				$rate->taxes
			);
		}

		return $rates;
	}

	/**
	 * Get normalized tax rule for the current role.
	 *
	 * @return array
	 */
	private function get_rule() {
		if ( null !== $this->rule ) {
			return $this->rule;
		}

		$this->rule = array();

		$restrictions = $this->get_current_role_restrictions();
		if ( empty( $restrictions ) || $this->is_current_user_excluded( $restrictions ) ) {
			return $this->rule;
		}

		$data = isset( $restrictions['_tax_rule'] ) && is_array( $restrictions['_tax_rule'] ) ? $restrictions['_tax_rule'] : array();
		if ( ! $this->is_restriction_enabled( $restrictions, '_tax_enabled', $data ) ) {
			return $this->rule;
		}

		$rule = array(
			'tax_exempted'     => isset( $data['tax_exempted'] ) ? (string) $data['tax_exempted'] : ( isset( $restrictions['_tax_exempted'] ) ? (string) $restrictions['_tax_exempted'] : '' ),
			'tax_class'        => isset( $data['tax_class'] ) ? (string) $data['tax_class'] : ( isset( $restrictions['_tax_class'] ) ? (string) $restrictions['_tax_class'] : '' ),
			'countries'        => isset( $data['countries'] ) ? $data['countries'] : ( isset( $restrictions['_tax_exempted_country'] ) ? $restrictions['_tax_exempted_country'] : ( isset( $restrictions['_exempted_country'] ) ? $restrictions['_exempted_country'] : array() ) ),
			'filter'           => $this->normalize_product_filter( isset( $data['filter'] ) ? $data['filter'] : ( isset( $restrictions['_tax_filter'] ) ? $restrictions['_tax_filter'] : 'specific_products' ) ),
			'products'         => isset( $data['products'] ) ? $data['products'] : ( isset( $restrictions['_tax_products'] ) ? $restrictions['_tax_products'] : array() ),
			'exclude_products' => isset( $data['exclude_products'] ) ? $data['exclude_products'] : ( isset( $restrictions['_tax_exclude_products'] ) ? $restrictions['_tax_exclude_products'] : array() ),
		);

		if ( ! in_array( $rule['tax_exempted'], array( 'yes', 'no' ), true ) ) {
			return $this->rule;
		}

		if ( 'no' === $rule['tax_exempted'] && '' === $rule['tax_class'] ) {
			return $this->rule;
		}

		if ( ! $this->country_matches_rule( $rule ) ) {
			return $this->rule;
		}

		if ( in_array( $rule['filter'], array( 'specific_products', 'specific_variations' ), true ) && empty( $this->get_selected_ids( $rule['products'] ) ) ) {
			return $this->rule;
		}

		if ( in_array( $rule['filter'], array( 'categories', 'brands' ), true ) && empty( $this->get_selected_ids( $rule['products'] ) ) ) {
			return $this->rule;
		}

		$this->rule = $rule;
		return $this->rule;
	}

	/**
	 * Get current role restrictions.
	 *
	 * @return array
	 */
	private function get_current_role_restrictions() {
		$role_id = wholesalex()->get_current_user_role();
		if ( empty( $role_id ) ) {
			return array();
		}

		$role_content = wholesalex()->get_roles( 'by_id', $role_id );
		if ( empty( $role_content['_restrictions'] ) || ! is_array( $role_content['_restrictions'] ) ) {
			return array();
		}

		return $role_content['_restrictions'];
	}

	/**
	 * Check whether current user is excluded from tax rules.
	 *
	 * @param array $restrictions Role restrictions.
	 * @return bool
	 */
	private function is_current_user_excluded( array $restrictions ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$data    = isset( $restrictions['_tax_rule'] ) && is_array( $restrictions['_tax_rule'] ) ? $restrictions['_tax_rule'] : array();
		$enabled = isset( $data['exclude_users']['enabled'] ) ? $data['exclude_users']['enabled'] : ( isset( $restrictions['_tax_exclude_users'] ) ? $restrictions['_tax_exclude_users'] : false );

		if ( ! $this->is_truthy( $enabled ) ) {
			return false;
		}

		$users   = isset( $data['exclude_users']['users'] ) ? $data['exclude_users']['users'] : ( isset( $restrictions['_tax_exclude_users_list'] ) ? $restrictions['_tax_exclude_users_list'] : array() );
		$user_id = (int) apply_filters( 'wholesalex_set_current_user', get_current_user_id() );

		return in_array( $user_id, $this->get_selected_ids( $users ), true );
	}

	/**
	 * Check if customer country matches the tax rule.
	 *
	 * @param array $rule Tax rule.
	 * @return bool
	 */
	private function country_matches_rule( array $rule ) {
		$countries = $this->get_selected_values( $rule['countries'] );
		if ( empty( $countries ) ) {
			return true;
		}

		if ( ! is_a( WC()->customer, 'WC_Customer' ) ) {
			return false;
		}

		$tax_based_on = get_option( 'woocommerce_tax_based_on' );
		$country      = 'shipping' === $tax_based_on ? WC()->customer->get_shipping_country() : WC()->customer->get_billing_country();

		return in_array( $country, $countries, true );
	}

	/**
	 * Check whether product matches a tax rule.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $rule Tax rule.
	 * @return bool
	 */
	private function product_matches_rule( $product, array $rule ) {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;
		$product_ids  = array_values( array_filter( array_map( 'absint', array( $product_id, $variation_id ) ) ) );

		if ( ! empty( array_intersect( $product_ids, $this->get_selected_ids( $rule['exclude_products'] ) ) ) ) {
			return false;
		}

		$selected_ids = $this->get_selected_ids( $rule['products'] );

		switch ( $rule['filter'] ) {
			case 'all_products':
				return true;
			case 'specific_products':
				return ! empty( array_intersect( $product_ids, $selected_ids ) );
			case 'specific_variations':
				return $variation_id && in_array( $variation_id, $selected_ids, true );
			case 'categories':
				return ! empty( $selected_ids ) && ! empty( array_intersect( wc_get_product_term_ids( $product_id, 'product_cat' ), $selected_ids ) );
			case 'brands':
				$brand_taxonomy = $this->get_brand_taxonomy();
				return $brand_taxonomy && ! empty( $selected_ids ) && ! empty( array_intersect( wc_get_product_term_ids( $product_id, $brand_taxonomy ), $selected_ids ) );
			default:
				return false;
		}
	}

	/**
	 * Normalize product filter keys from UI/legacy values.
	 *
	 * @param string $filter Product filter.
	 * @return string
	 */
	private function normalize_product_filter( $filter ) {
		$map = array(
			'all'               => 'all_products',
			'cat_in_list'       => 'categories',
			'brand_in_list'     => 'brands',
			'products_in_list'  => 'specific_products',
			'attribute_in_list' => 'specific_variations',
		);

		if ( isset( $map[ $filter ] ) ) {
			return $map[ $filter ];
		}

		return in_array( $filter, array( 'all_products', 'specific_products', 'specific_variations', 'categories', 'brands' ), true ) ? $filter : 'specific_products';
	}

	/**
	 * Extract IDs from multiselect values.
	 *
	 * @param array $items Multiselect items.
	 * @return array
	 */
	private function get_selected_ids( $items ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return array();
		}

		$ids = array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) && isset( $item['value'] ) ) {
				$ids[] = (int) preg_replace( '/[^0-9]/', '', (string) $item['value'] );
			} elseif ( is_numeric( $item ) ) {
				$ids[] = (int) $item;
			}
		}

		return array_values( array_filter( array_unique( $ids ) ) );
	}

	/**
	 * Extract string values from multiselect values.
	 *
	 * @param array $items Multiselect items.
	 * @return array
	 */
	private function get_selected_values( $items ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return array();
		}

		$values = array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) && isset( $item['value'] ) ) {
				$values[] = (string) $item['value'];
			} elseif ( is_scalar( $item ) ) {
				$values[] = (string) $item;
			}
		}

		return array_values( array_filter( array_unique( $values ) ) );
	}

	/**
	 * Get the active WooCommerce brand taxonomy used by the selector.
	 *
	 * @return string
	 */
	private function get_brand_taxonomy() {
		foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand' ) as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				return $taxonomy;
			}
		}

		return '';
	}

	/**
	 * Normalize bool-like UI values.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function is_truthy( $value ) {
		return true === $value || 1 === $value || '1' === $value || 'yes' === $value;
	}

	/**
	 * Check whether a role restriction section is enabled.
	 *
	 * @param array  $restrictions Role restrictions.
	 * @param string $key Enabled flag key.
	 * @param array  $data Nested rule data.
	 * @return bool
	 */
	private function is_restriction_enabled( $restrictions, $key, $data = array() ) {
		if ( array_key_exists( $key, $restrictions ) ) {
			return $this->is_truthy( $restrictions[ $key ] );
		}

		if ( is_array( $data ) && array_key_exists( 'enabled', $data ) ) {
			return $this->is_truthy( $data['enabled'] );
		}

		return true;
	}

	/**
	 * Check whether current request is an admin-only request.
	 *
	 * @return bool
	 */
	private function is_admin_request() {
		return is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX );
	}
}
