<?php
/**
 * WholesaleX User Roles - Replace Cart With Quote
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles role-level add-to-cart replacement with request-quote buttons.
 */
class User_Roles_Cart_To_Quote {

	/**
	 * Cached active status for the current request.
	 *
	 * @var bool|null
	 */
	private $is_enabled = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_is_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'filter_is_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 20, 5 );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'replace_loop_add_to_cart_link' ), 20, 2 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_single_quote_button' ), 31 );
		add_filter( 'woocommerce_available_variation', array( $this, 'filter_available_variation' ), 20, 3 );
		add_action( 'wp_head', array( $this, 'hide_variable_cart_controls' ) );
		add_action( 'wp_footer', array( $this, 'prepare_request_quote_modal' ), 1 );
	}

	/**
	 * Disable normal purchasing for roles that use quote replacement.
	 *
	 * @param bool        $is_purchasable Purchasable status.
	 * @param \WC_Product $product Product object.
	 * @return bool
	 */
	public function filter_is_purchasable( $is_purchasable, $product ) {
		if ( $this->should_replace_cart_with_quote() && $product instanceof \WC_Product && ! $product->is_type( 'variable' ) ) {
			return false;
		}

		return $is_purchasable;
	}

	/**
	 * Prevent direct add-to-cart requests for quote-only roles.
	 *
	 * @param bool  $passed Add-to-cart status.
	 * @param int   $product_id Product ID.
	 * @param int   $quantity Quantity.
	 * @param int   $variation_id Variation ID.
	 * @param array $variations Variation attributes.
	 * @return bool
	 */
	public function validate_add_to_cart( $passed, $product_id, $quantity = 1, $variation_id = 0, $variations = array() ) {
		if ( ! $this->should_replace_cart_with_quote() ) {
			return $passed;
		}

		wc_add_notice( __( 'This product is available by quote request only.', 'wholesalex' ), 'notice' );
		return false;
	}

	/**
	 * Replace catalog add-to-cart buttons with quote buttons.
	 *
	 * @param string      $link Add-to-cart HTML.
	 * @param \WC_Product $product Product object.
	 * @return string
	 */
	public function replace_loop_add_to_cart_link( $link, $product ) {
		if ( ! $this->should_replace_cart_with_quote() ) {
			return $link;
		}

		return $this->get_request_quote_button_html( $product, 'loop' );
	}

	/**
	 * Render quote button on single product pages.
	 *
	 * @return void
	 */
	public function render_single_quote_button() {
		$product = $this->get_current_product();

		if ( ! $this->should_replace_cart_with_quote() ) {
			return;
		}

		if ( $product instanceof \WC_Product && $product->is_type( 'variable' ) ) {
			return;
		}

		echo wp_kses_post( $this->get_request_quote_button_html( $product, 'single' ) );
	}

	/**
	 * Mark variations as quote-only for quote replacement roles.
	 *
	 * @param array       $variation_data Variation data.
	 * @param \WC_Product $product Parent product.
	 * @param \WC_Product $variation Variation product.
	 * @return array
	 */
	public function filter_available_variation( $variation_data, $product, $variation ) {
		if ( ! $this->should_replace_cart_with_quote() ) {
			return $variation_data;
		}

		$variation_data['is_purchasable']    = false;
		$variation_data['availability_html'] .= $this->get_request_quote_button_html( $variation, 'variation' );

		return $variation_data;
	}

	/**
	 * Hide native variation quantity and add-to-cart controls when quote replacement is active.
	 *
	 * @return void
	 */
	public function hide_variable_cart_controls() {
		if ( ! function_exists( 'is_product' ) || ! is_product() || ! $this->should_replace_cart_with_quote() ) {
			return;
		}

		$product = $this->get_current_product();
		if ( ! $product instanceof \WC_Product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		?>
		<style id="wholesalex-role-replace-cart-quote-variable-css">
			.single-product form.variations_form.cart .woocommerce-variation-add-to-cart,
			.single-product form.variations_form.cart .single_variation_wrap > .ct-cart-actions:not(.wholesalex-role-replace-cart-quote),
			.single-product .woocommerce-variation-add-to-cart .quantity,
			.single-product .woocommerce-variation-add-to-cart .single_add_to_cart_button,
			.single-product form.variations_form.cart .single_variation_wrap .quantity,
			.single-product form.variations_form.cart .single_variation_wrap .ct-quantity,
			.single-product form.variations_form.cart .single_variation_wrap input.qty,
			.single-product form.variations_form.cart .quantity,
			.single-product form.variations_form.cart .ct-quantity,
			.single-product form.variations_form.cart input.qty {
				display: none !important;
			}

			.single-product form.variations_form.cart .single_variation .wholesalex_request_custom_quote_btn,
			.single-product form.variations_form.cart .single_variation .wholesalex-role-replace-cart-quote-button,
			.single-product form.variations_form.cart .single_variation .wholesalex-role-replace-cart-quote,
			.single-product form.variations_form.cart .wholesalex_request_custom_quote_btn,
			.single-product form.variations_form.cart .wholesalex-role-replace-cart-quote-button,
			.single-product form.variations_form.cart .wholesalex-role-replace-cart-quote {
				width: 100% !important;
				max-width: 100% !important;
			}

			.single-product form.variations_form.cart .wholesalex_request_custom_quote_btn,
			.single-product form.variations_form.cart .wholesalex-role-replace-cart-quote-button {
				display: flex !important;
				justify-content: center;
				text-align: center;
			}
		</style>
		<?php
	}

	/**
	 * Let the Request a Quote addon prepare its modal and click handling.
	 *
	 * @return void
	 */
	public function prepare_request_quote_modal() {
		if ( ! $this->should_replace_cart_with_quote() ) {
			return;
		}

		do_action( 'wholesalex_after_hidden_price_quote', $this->get_raq_data() );
	}

	/**
	 * Check whether current role should use quote buttons instead of cart buttons.
	 *
	 * @return bool
	 */
	private function should_replace_cart_with_quote() {
		if ( null !== $this->is_enabled ) {
			return $this->is_enabled;
		}

		$this->is_enabled = false;

		if ( $this->is_admin_request() ) {
			return $this->is_enabled;
		}

		if ( ! $this->is_request_quote_addon_enabled() ) {
			return $this->is_enabled;
		}

		$restrictions = $this->get_current_role_restrictions();
		if ( empty( $restrictions ) || $this->is_current_user_excluded( $restrictions ) ) {
			return $this->is_enabled;
		}

		$this->is_enabled = $this->is_truthy( isset( $restrictions['_replace_cart_with_quote'] ) ? $restrictions['_replace_cart_with_quote'] : false )
			|| $this->is_truthy( isset( $restrictions['_raq_replace_add_to_cart_with_quote'] ) ? $restrictions['_raq_replace_add_to_cart_with_quote'] : false );

		return $this->is_enabled;
	}

	/**
	 * Check whether the Request a Quote addon is active.
	 *
	 * @return bool
	 */
	private function is_request_quote_addon_enabled() {
		return $this->is_truthy( wholesalex()->get_setting( 'wsx_addon_raq' ) );
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
			$role_content['_restrictions'] = array();
		}

		$restrictions = $role_content['_restrictions'];
		foreach ( array( '_replace_cart_with_quote', '_raq_replace_add_to_cart_with_quote', '_exclude_users' ) as $key ) {
			if ( ! array_key_exists( $key, $restrictions ) && array_key_exists( $key, $role_content ) ) {
				$restrictions[ $key ] = $role_content[ $key ];
			}
		}

		return $restrictions;
	}

	/**
	 * Get the current product, including early frontend hooks where global $product is not ready.
	 *
	 * @return \WC_Product|null
	 */
	private function get_current_product() {
		global $product;

		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'get_queried_object_id' ) ) {
			return null;
		}

		$product_id = absint( get_queried_object_id() );
		return $product_id ? wc_get_product( $product_id ) : null;
	}

	/**
	 * Check whether current user is excluded from general role restrictions.
	 *
	 * @param array $restrictions Role restrictions.
	 * @return bool
	 */
	private function is_current_user_excluded( array $restrictions ) {
		if ( ! is_user_logged_in() || empty( $restrictions['_exclude_users'] ) || ! is_array( $restrictions['_exclude_users'] ) ) {
			return false;
		}

		$user_id = (int) apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		return in_array( $user_id, $this->get_selected_ids( $restrictions['_exclude_users'] ), true );
	}

	/**
	 * Build request quote button HTML.
	 *
	 * @param \WC_Product $product Product object.
	 * @param string      $context Render context.
	 * @return string
	 */
	private function get_request_quote_button_html( $product, $context = 'loop' ) {
		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		$rule = array(
			'filter'             => 'all_products',
			'products'           => array(),
			'exclude_products'   => array(),
			'show_request_quote' => true,
		);

		$addon_button = apply_filters( 'wholesalex_role_replace_cart_with_quote_button_html', '', $product, $context, $rule );
		if ( empty( $addon_button ) ) {
			$addon_button = apply_filters( 'wholesalex_role_price_visibility_request_quote_button_html', '', $product, $context, $rule );
		}

		if ( ! empty( $addon_button ) ) {
			return $addon_button;
		}

		$product_id  = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$product_id  = 'variation' === $context ? $product->get_id() : $product_id;
		$button_text = apply_filters( 'wholesalex_role_replace_cart_with_quote_button_text', __( 'Add to quote', 'wholesalex' ), $product, $context );

		if ( 'single' === $context ) {
			$theme_button_class = function_exists( 'wc_wp_theme_get_element_class_name' ) ? wc_wp_theme_get_element_class_name( 'button' ) : '';
			$classes = array(
				'button',
				'wholesalex_request_custom_quote_btn',
				'wholesalex-role-replace-cart-quote-button',
				'wsx-single-product',
				$theme_button_class,
			);

			return sprintf(
				'<div class="ct-cart-actions wholesalex-role-replace-cart-quote"><button type="button" data-quantity="1" data-product_id="%1$d" data-product_sku="%2$s" class="%3$s">%4$s</button></div>',
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
			'wholesalex-role-replace-cart-quote-button',
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
	 * Get Request a Quote data in the shape expected by the quote addon.
	 *
	 * @return array
	 */
	private function get_raq_data() {
		return array(
			'include_product'             => array(),
			'exclude_product'             => array(),
			'include_cat'                 => array(),
			'exclude_cat'                 => array(),
			'include_variations'          => array(),
			'exclude_variations'          => array(),
			'include_brands'              => array(),
			'exclude_brands'              => array(),
			'include_attributes'          => array(),
			'exclude_attributes'          => array(),
			'is_all_products'             => true,
			'hidden_is_all_products'      => false,
			'hidden_request_product_list' => array(),
			'hidden_exclude_products'     => array(),
			'hidden_include_cats'         => array(),
			'hidden_exclude_cats'         => array(),
			'hidden_include_variations'   => array(),
			'hidden_exclude_variations'   => array(),
			'request_quote_enable'        => true,
		);
	}

	/**
	 * Extract ids from multiselect values.
	 *
	 * @param array $items Multiselect items.
	 * @return array
	 */
	private function get_selected_ids( $items ) {
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
	 * Normalize bool-like UI values.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private function is_truthy( $value ) {
		return true === $value || 1 === $value || '1' === $value || 'yes' === $value;
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
