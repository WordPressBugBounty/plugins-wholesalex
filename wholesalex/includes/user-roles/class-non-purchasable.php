<?php
/**
 * WholesaleX User Roles - Non-Purchasable Product Rules
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles role-level non-purchasable product restrictions.
 */
class User_Roles_Non_Purchasable {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_is_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'filter_is_purchasable' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 5 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'replace_loop_add_to_cart_link' ), 10, 2 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'display_non_purchasable_message' ), 31 );
		add_filter( 'woocommerce_available_variation', array( $this, 'filter_available_variation' ), 10, 3 );
	}

	/**
	 * Make role non-purchasable products not purchasable.
	 *
	 * @param bool        $is_purchasable Purchasable status.
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	public function filter_is_purchasable( $is_purchasable, $product ) {
		if ( $this->is_product_non_purchasable( $product ) ) {
			return false;
		}

		return $is_purchasable;
	}

	/**
	 * Prevent direct add-to-cart for role non-purchasable products.
	 *
	 * @param bool  $passed Add-to-cart status.
	 * @param int   $product_id Product ID.
	 * @param int   $quantity Quantity.
	 * @param int   $variation_id Variation ID.
	 * @param array $variations Variation attributes.
	 * @return bool
	 */
	public function validate_add_to_cart( $passed, $product_id, $quantity = 1, $variation_id = 0, $variations = array() ) {
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! $this->is_product_non_purchasable( $product ) ) {
			return $passed;
		}

		wc_add_notice( $this->get_non_purchasable_message(), 'error' );
		return false;
	}

	/**
	 * Show the normal product detail action instead of leaving a blank loop action.
	 *
	 * @param string      $link Add-to-cart HTML.
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	public function replace_loop_add_to_cart_link( $link, $product ) {
		if ( ! $this->is_product_non_purchasable( $product ) ) {
			return $link;
		}

		return sprintf(
			'<a href="%s" data-quantity="1" class="%s">%s</a>',
			esc_url( $product->get_permalink() ),
			esc_attr(
				implode(
					' ',
					array_filter(
						array(
							'button',
							'product_type_' . $product->get_type(),
						)
					)
				)
			),
			esc_html__( 'Read more', 'woocommerce' )
		);
	}

	/**
	 * Display custom non-purchasable message on single product pages.
	 *
	 * @return void
	 */
	public function display_non_purchasable_message() {
		global $product;

		if ( ! $this->is_product_non_purchasable( $product ) ) {
			return;
		}

		echo '<p class="wholesalex-role-non-purchasable-message">' . esc_html( $this->get_non_purchasable_message() ) . '</p>';
	}

	/**
	 * Mark restricted variations as non-purchasable.
	 *
	 * @param array       $variation_data Variation data.
	 * @param \WC_Product $product Parent product.
	 * @param \WC_Product $variation Variation product.
	 * @return array
	 */
	public function filter_available_variation( $variation_data, $product, $variation ) {
		if ( ! $this->is_product_non_purchasable( $variation ) ) {
			return $variation_data;
		}

		$variation_data['is_purchasable']    = false;
		$variation_data['availability_html'] .= '<p class="wholesalex-role-non-purchasable-message">' . esc_html( $this->get_non_purchasable_message() ) . '</p>';

		return $variation_data;
	}

	/**
	 * Check if a product is non-purchasable for the current role/user.
	 *
	 * @param mixed $product Product object.
	 * @return bool
	 */
	private function is_product_non_purchasable( $product ) {
		if ( ! is_object( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return false;
		}

		$restrictions = $this->get_current_role_restrictions();
		if ( empty( $restrictions ) || ! $this->is_restriction_enabled( $restrictions, '_non_purchasable_enabled' ) || $this->is_current_user_excluded( $restrictions, '_non_purchasable' ) ) {
			return false;
		}

		$filter            = $this->normalize_product_filter( isset( $restrictions['_non_purchasable_filter'] ) ? $restrictions['_non_purchasable_filter'] : 'specific_products' );
		$selected          = isset( $restrictions['_non_purchasable_products'] ) ? $restrictions['_non_purchasable_products'] : array();
		$excluded_products = isset( $restrictions['_non_purchasable_exclude_products'] ) ? $restrictions['_non_purchasable_exclude_products'] : array();

		return $this->product_matches_rule( $product, $filter, $selected, $excluded_products );
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
	 * Check whether current user is excluded from a restriction group.
	 *
	 * @param array  $restrictions Role restrictions.
	 * @param string $prefix Restriction prefix.
	 * @return bool
	 */
	private function is_current_user_excluded( $restrictions, $prefix ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$enabled_key = $prefix . '_exclude_users';
		$list_key    = $prefix . '_exclude_users_list';

		if ( ! $this->is_truthy( isset( $restrictions[ $enabled_key ] ) ? $restrictions[ $enabled_key ] : false ) ) {
			return false;
		}

		$user_id = (int) apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		return in_array( $user_id, $this->get_selected_ids( isset( $restrictions[ $list_key ] ) ? $restrictions[ $list_key ] : array() ), true );
	}

	/**
	 * Check if product matches a restriction rule.
	 *
	 * @param \WC_Product $product Product object.
	 * @param string      $filter Product filter.
	 * @param array       $selected Selected values.
	 * @param array       $excluded_products Excluded products.
	 * @return bool
	 */
	private function product_matches_rule( $product, $filter, $selected, $excluded_products = array() ) {
		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;
		$product_ids  = array_values( array_filter( array_map( 'absint', array( $product_id, $variation_id ) ) ) );

		if ( ! empty( array_intersect( $product_ids, $this->get_selected_ids( $excluded_products ) ) ) ) {
			return false;
		}

		$selected_ids = $this->get_selected_ids( $selected );

		switch ( $filter ) {
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
	 * Extract ids from multiselect values.
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
	 * @return bool
	 */
	private function is_restriction_enabled( $restrictions, $key ) {
		return ! array_key_exists( $key, $restrictions ) || $this->is_truthy( $restrictions[ $key ] );
	}

	/**
	 * Get non-purchasable warning text.
	 *
	 * @return string
	 */
	private function get_non_purchasable_message() {
		$restrictions = $this->get_current_role_restrictions();
		$message      = isset( $restrictions['_non_purchasable_warning'] ) ? trim( (string) $restrictions['_non_purchasable_warning'] ) : '';

		return '' !== $message ? $message : __( 'This product is non purchasable.', 'wholesalex' );
	}
}
