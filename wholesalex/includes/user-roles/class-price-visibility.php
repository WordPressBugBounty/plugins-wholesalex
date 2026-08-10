<?php
/**
 * WholesaleX User Roles - Price Visibility Rules
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles role-level price visibility restrictions.
 */
class User_Roles_Price_Visibility {

	/**
	 * Cached price visibility rule for the current request.
	 *
	 * @var array|null
	 */
	private $rule = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_price_html', array( $this, 'hide_price_html' ), 999, 2 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_is_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'filter_is_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 20, 5 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'replace_loop_add_to_cart_link' ), 20, 2 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'remove_single_add_to_cart' ), 1 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_quote_button' ), 21 );
		add_filter( 'woocommerce_available_variation', array( $this, 'filter_available_variation' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'hide_cart_item_price' ), 999, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'hide_cart_item_price' ), 999, 3 );
		add_action( 'wp_footer', array( $this, 'prepare_request_quote_modal' ), 1 );
	}

	/**
	 * Replace product price HTML with the role hidden-price text.
	 *
	 * @param string      $price_html Existing price HTML.
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	public function hide_price_html( $price_html, $product ) {
		if ( $this->is_admin_request() || ! $this->is_product_price_hidden( $product ) ) {
			return $price_html;
		}

		return '<span class="wholesalex-role-price-hidden-text">' . esc_html( $this->get_hidden_price_text() ) . '</span>';
	}

	/**
	 * Make hidden-price products non-purchasable.
	 *
	 * Request Quote is an optional replacement for the purchase UI. A hidden
	 * price must not leave the normal purchase flow available when that option
	 * is disabled.
	 *
	 * @param bool        $is_purchasable Purchasable status.
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	public function filter_is_purchasable( $is_purchasable, $product ) {
		if ( $this->is_product_price_hidden( $product ) ) {
			return false;
		}

		return $is_purchasable;
	}

	/**
	 * Remove the native purchase form from matched single-product pages.
	 *
	 * The explicit hook removal covers product types whose templates do not
	 * check the parent product's purchasable status before rendering.
	 *
	 * @return void
	 */
	public function remove_single_add_to_cart() {
		global $product;

		if ( ! $this->is_product_price_hidden( $product ) ) {
			return;
		}

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	}

	/**
	 * Prevent direct add-to-cart requests for hidden-price products.
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
		if ( ! $this->is_product_price_hidden( $product ) ) {
			return $passed;
		}

		wc_add_notice( $this->get_hidden_price_text(), 'notice' );
		return false;
	}

	/**
	 * Replace shop/archive add-to-cart buttons with Request Quote buttons.
	 *
	 * @param string      $link Add-to-cart HTML.
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	public function replace_loop_add_to_cart_link( $link, $product ) {
		if ( ! $this->should_replace_purchase_flow( $product ) ) {
			return $link;
		}

		return $this->get_request_quote_button_html( $product, 'loop' );
	}

	/**
	 * Render Request Quote button on single product pages.
	 *
	 * @return void
	 */
	public function render_single_quote_button() {
		global $product;

		if ( ! $this->should_replace_purchase_flow( $product ) ) {
			return;
		}

		echo wp_kses_post( $this->get_request_quote_button_html( $product, 'single' ) );
	}

	/**
	 * Hide variation price and disable variation purchase when needed.
	 *
	 * @param array       $variation_data Variation data.
	 * @param \WC_Product $product Parent product.
	 * @param \WC_Product $variation Variation product.
	 * @return array
	 */
	public function filter_available_variation( $variation_data, $product, $variation ) {
		if ( ! $this->is_product_price_hidden( $variation ) ) {
			return $variation_data;
		}

		$variation_data['price_html'] = '<span class="wholesalex-role-price-hidden-text">' . esc_html( $this->get_hidden_price_text() ) . '</span>';

		$variation_data['is_purchasable'] = false;

		if ( $this->should_replace_purchase_flow( $variation ) ) {
			$variation_data['availability_html'] .= $this->get_request_quote_button_html( $variation, 'variation' );
		}

		return $variation_data;
	}

	/**
	 * Replace cart/checkout line prices for hidden-price products.
	 *
	 * @param string $price_html Existing price HTML.
	 * @param array  $cart_item Cart item.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function hide_cart_item_price( $price_html, $cart_item, $cart_item_key ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $this->is_product_price_hidden( $product ) ) {
			return $price_html;
		}

		return '<span class="wholesalex-role-price-hidden-text">' . esc_html( $this->get_hidden_price_text() ) . '</span>';
	}

	/**
	 * Let the Request a Quote addon print and wire its existing modal.
	 *
	 * This runs in the footer after catalog/single product buttons have already
	 * rendered, so the RAQ addon's dynamic-rule button hooks do not duplicate
	 * the role-level buttons on the current request.
	 *
	 * @return void
	 */
	public function prepare_request_quote_modal() {
		$rule = $this->get_rule();
		if ( empty( $rule['show_request_quote'] ) ) {
			return;
		}

		do_action( 'wholesalex_after_hidden_price_quote', $this->get_raq_hidden_price_data( $rule ) );
	}

	/**
	 * Check whether a product price is hidden for the current role/user.
	 *
	 * @param mixed $product Product object.
	 * @return bool
	 */
	private function is_product_price_hidden( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		$rule = $this->get_rule();
		if ( empty( $rule ) ) {
			return false;
		}

		return $this->product_matches_rule( $product, $rule['filter'], $rule['products'], $rule['exclude_products'] );
	}

	/**
	 * Check whether hidden-price products should replace purchase UI with quote UI.
	 *
	 * @param mixed $product Product object.
	 * @return bool
	 */
	private function should_replace_purchase_flow( $product ) {
		$rule = $this->get_rule();
		return ! empty( $rule['show_request_quote'] ) && $this->is_product_price_hidden( $product );
	}

	/**
	 * Get normalized price visibility rule for the current role.
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

		$data = isset( $restrictions['_price_visibility'] ) && is_array( $restrictions['_price_visibility'] ) ? $restrictions['_price_visibility'] : array();
		if ( ! $this->is_restriction_enabled( $restrictions, '_price_visibility_enabled', $data ) ) {
			return $this->rule;
		}

		$rule = array(
			'filter'             => $this->normalize_product_filter( isset( $data['filter'] ) ? $data['filter'] : ( isset( $restrictions['_price_visibility_filter'] ) ? $restrictions['_price_visibility_filter'] : 'specific_products' ) ),
			'products'           => isset( $data['products'] ) ? $data['products'] : ( isset( $restrictions['_price_visibility_products'] ) ? $restrictions['_price_visibility_products'] : array() ),
			'exclude_products'   => isset( $data['exclude_products'] ) ? $data['exclude_products'] : ( isset( $restrictions['_price_visibility_exclude_products'] ) ? $restrictions['_price_visibility_exclude_products'] : array() ),
			'hidden_text'        => isset( $data['hidden_text'] ) ? (string) $data['hidden_text'] : ( isset( $restrictions['_price_is_hidden_text'] ) ? (string) $restrictions['_price_is_hidden_text'] : '' ),
			'show_request_quote' => $this->is_truthy( isset( $data['show_request_quote'] ) ? $data['show_request_quote'] : ( isset( $restrictions['_hide_price_show_request_quote'] ) ? $restrictions['_hide_price_show_request_quote'] : false ) ),
		);

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
	 * Check whether current user is excluded from price visibility rules.
	 *
	 * @param array $restrictions Role restrictions.
	 * @return bool
	 */
	private function is_current_user_excluded( array $restrictions ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$data    = isset( $restrictions['_price_visibility'] ) && is_array( $restrictions['_price_visibility'] ) ? $restrictions['_price_visibility'] : array();
		$enabled = isset( $data['exclude_users']['enabled'] ) ? $data['exclude_users']['enabled'] : ( isset( $restrictions['_price_visibility_exclude_users'] ) ? $restrictions['_price_visibility_exclude_users'] : false );

		if ( ! $this->is_truthy( $enabled ) ) {
			return false;
		}

		$users   = isset( $data['exclude_users']['users'] ) ? $data['exclude_users']['users'] : ( isset( $restrictions['_price_visibility_exclude_users_list'] ) ? $restrictions['_price_visibility_exclude_users_list'] : array() );
		$user_id = (int) apply_filters( 'wholesalex_set_current_user', get_current_user_id() );

		return in_array( $user_id, $this->get_selected_ids( $users ), true );
	}

	/**
	 * Check if product matches a price visibility rule.
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
	 * Build Request Quote button HTML.
	 *
	 * @param \WC_Product $product Product object.
	 * @param string      $context Render context.
	 * @return string
	 */
	private function get_request_quote_button_html( $product, $context = 'loop' ) {
		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$rule = $this->get_rule();

		/**
		 * Allows the Request a Quote addon to provide its native button markup.
		 *
		 * Return a non-empty string from this filter to keep quote click handling
		 * entirely inside the quote addon.
		 */
		$addon_button = apply_filters( 'wholesalex_role_price_visibility_request_quote_button_html', '', $product, $context, $rule );
		if ( ! empty( $addon_button ) ) {
			return $addon_button;
		}

		$product_id  = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$product_id  = 'variation' === $context ? $product->get_id() : $product_id;
		$button_text = wholesalex()->get_language_n_text( '_language_hidden_price_request_quote_btn_text', __( 'Request Quote', 'wholesalex' ) );

		if ( 'single' === $context ) {
			$classes = array(
				'button',
				'wholesalex_request_custom_quote_btn',
				'wholesalex-role-request-quote-button',
				'wsx-single-product',
				wc_wp_theme_get_element_class_name( 'button' ),
			);

			return sprintf(
				'<div class="ct-cart-actions wholesalex-role-price-visibility-quote"><button type="button" data-quantity="1" data-product_id="%1$d" data-product_sku="%2$s" class="%3$s">%4$s</button></div>',
				absint( $product_id ),
				esc_attr( $product->get_sku() ),
				esc_attr( implode( ' ', array_filter( $classes ) ) ),
				esc_html( $button_text )
			);
		}

		$classes = array(
			'wsx-link',
			'button',
			'wholesalex_request_custom_quote_btn',
			'wholesalex-role-request-quote-button',
			'product_type_' . $product->get_type(),
		);

		return sprintf(
			'<a href="#" data-quantity="1" data-product_id="%1$d" data-product_sku="%2$s" class="%3$s" rel="nofollow">%4$s</a>',
			absint( $product_id ),
			esc_attr( $product->get_sku() ),
			esc_attr( implode( ' ', array_filter( $classes ) ) ),
			esc_html( $button_text )
		);
	}

	/**
	 * Convert the role price rule to the RAQ hidden-price data shape.
	 *
	 * @param array $rule Normalized role price visibility rule.
	 * @return array
	 */
	private function get_raq_hidden_price_data( array $rule ) {
		$selected_ids = $this->get_selected_ids( $rule['products'] );
		$excluded_ids = $this->get_selected_ids( $rule['exclude_products'] );

		$data = array(
			'include_product'             => array(),
			'exclude_product'             => $excluded_ids,
			'include_cat'                 => array(),
			'exclude_cat'                 => array(),
			'include_variations'          => array(),
			'exclude_variations'          => array(),
			'include_brands'              => array(),
			'exclude_brands'              => array(),
			'include_attributes'          => array(),
			'exclude_attributes'          => array(),
			'is_all_products'             => false,
			'hidden_is_all_products'      => false,
			'hidden_request_product_list' => array(),
			'hidden_exclude_products'     => $excluded_ids,
			'hidden_include_cats'         => array(),
			'hidden_exclude_cats'         => array(),
			'hidden_include_variations'   => array(),
			'hidden_exclude_variations'   => array(),
			'request_quote_enable'        => ! empty( $rule['show_request_quote'] ),
		);

		switch ( $rule['filter'] ) {
			case 'all_products':
				$data['is_all_products']        = true;
				$data['hidden_is_all_products'] = true;
				break;
			case 'specific_products':
				$data['include_product']             = $selected_ids;
				$data['hidden_request_product_list'] = $selected_ids;
				break;
			case 'specific_variations':
				$data['hidden_include_variations'] = $selected_ids;
				break;
			case 'categories':
				$data['include_cat']         = $selected_ids;
				$data['hidden_include_cats'] = $selected_ids;
				break;
			case 'brands':
				$data['include_brands'] = $selected_ids;
				break;
		}

		return $data;
	}

	/**
	 * Get hidden-price text.
	 *
	 * @return string
	 */
	private function get_hidden_price_text() {
		$rule = $this->get_rule();
		$text = isset( $rule['hidden_text'] ) ? trim( (string) $rule['hidden_text'] ) : '';

		if ( '' === $text ) {
			$text = wholesalex()->get_language_n_text( '_language_price_is_hidden', __( 'Price is hidden!', 'wholesalex' ) );
		}

		return apply_filters( 'wholesalex_role_price_visibility_hidden_text', $text, $rule );
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
