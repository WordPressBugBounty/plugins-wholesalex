<?php
/**
 * WholesaleX User Roles - Checkout Restriction Rules
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles role-level product, quantity, and order value checkout restrictions.
 */
class User_Roles_Checkout_Restriction {

	/**
	 * Cached checkout rule for the current request.
	 *
	 * @var array|null
	 */
	private $rule = null;

	/**
	 * Cached validation result for the current request.
	 *
	 * @var array|null
	 */
	private $validation = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_check_cart_items', array( $this, 'validate_cart_items' ), 9 );
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'maybe_remove_checkout_button' ), 1 );
		add_filter( 'woocommerce_order_button_html', array( $this, 'maybe_remove_checkout_order_button' ), 20 );
		add_action( 'woocommerce_before_cart', array( $this, 'render_promotional_texts' ), 12 );
	}

	/**
	 * Add cart notices when checkout restriction is not satisfied.
	 *
	 * @return void
	 */
	public function validate_cart_items() {
		$validation = $this->get_validation_result();
		if ( ! empty( $validation['is_valid'] ) ) {
			return;
		}

		foreach ( $validation['messages'] as $message ) {
			wc_add_notice( $message, 'error' );
		}
	}

	/**
	 * Hide cart checkout button when restriction is not satisfied.
	 *
	 * @return void
	 */
	public function maybe_remove_checkout_button() {
		$validation = $this->get_validation_result();
		if ( empty( $validation['is_valid'] ) ) {
			// Cart builders may replace WooCommerce's named button callback with an
			// anonymous callback. Clear the checkout action so replacements cannot
			// bypass an active restriction.
			remove_all_actions( 'woocommerce_proceed_to_checkout' );
		}
	}

	/**
	 * Hide checkout place-order button when restriction is not satisfied.
	 *
	 * @param string $button Button HTML.
	 * @return string
	 */
	public function maybe_remove_checkout_order_button( $button ) {
		$validation = $this->get_validation_result();
		return empty( $validation['is_valid'] ) ? '' : $button;
	}

	/**
	 * Render optional promotional text on the cart page.
	 *
	 * @return void
	 */
	public function render_promotional_texts() {
		if ( ! is_cart() ) {
			return;
		}

		$rule = $this->get_rule();
		if ( empty( $rule ) || empty( $rule['promotions']['enabled'] ) ) {
			return;
		}

		$messages = $this->get_promotion_messages( $rule );
		foreach ( $messages as $message ) {
			wc_print_notice( $message, 'notice' );
		}
	}

	/**
	 * Get validation result.
	 *
	 * @return array
	 */
	private function get_validation_result() {
		if ( null !== $this->validation ) {
			return $this->validation;
		}

		$this->validation = array(
			'is_valid' => true,
			'messages' => array(),
		);

		$rule = $this->get_rule();
		if ( empty( $rule ) || ! WC()->cart ) {
			return $this->validation;
		}

		$cart_totals           = $this->get_matching_cart_totals( $rule );
		$messages              = array();
		$has_quantity_limits   = ! empty( $rule['quantity']['enabled'] ) && (
			absint( $rule['quantity']['min'] ) > 0 ||
			absint( $rule['quantity']['max'] ) > 0
		);
		$has_value_limits      = ! empty( $rule['order_value']['enabled'] ) && (
			(float) $rule['order_value']['min'] > 0 ||
			(float) $rule['order_value']['max'] > 0
		);
		$has_configured_limits = $has_quantity_limits || $has_value_limits;

		if ( ! empty( $rule['quantity']['enabled'] ) ) {
			$min = isset( $rule['quantity']['min'] ) ? absint( $rule['quantity']['min'] ) : 0;
			$max = isset( $rule['quantity']['max'] ) ? absint( $rule['quantity']['max'] ) : 0;

			if ( $min > 0 && $cart_totals['quantity'] < $min ) {
				$messages[] = $this->replace_smart_tags(
					! empty( $rule['quantity']['min_message'] ) ? $rule['quantity']['min_message'] : __( 'You have to add minimum {minimum_qty} item(s) to checkout.', 'wholesalex' ),
					array(
						'{minimum_qty}'  => $min,
						'{cart_quantity}' => $cart_totals['quantity'],
					)
				);
			}

			if ( $max > 0 && $cart_totals['quantity'] > $max ) {
				$messages[] = $this->replace_smart_tags(
					! empty( $rule['quantity']['max_message'] ) ? $rule['quantity']['max_message'] : __( 'You can add maximum {maximum_qty} item(s) to checkout.', 'wholesalex' ),
					array(
						'{maximum_qty}'  => $max,
						'{cart_quantity}' => $cart_totals['quantity'],
					)
				);
			}
		}

		if ( ! empty( $rule['order_value']['enabled'] ) ) {
			$min = isset( $rule['order_value']['min'] ) ? (float) $rule['order_value']['min'] : 0.0;
			$max = isset( $rule['order_value']['max'] ) ? (float) $rule['order_value']['max'] : 0.0;

			if ( $min > 0 && $cart_totals['subtotal'] < $min ) {
				$messages[] = $this->replace_smart_tags(
					! empty( $rule['order_value']['min_message'] ) ? $rule['order_value']['min_message'] : __( 'Minimum checkout amount is {minimum_amount}.', 'wholesalex' ),
					array(
						'{minimum_amount}' => wc_price( $min ),
						'{cart_value}'     => wc_price( $cart_totals['subtotal'] ),
					)
				);
			}

			if ( $max > 0 && $cart_totals['subtotal'] > $max ) {
				$messages[] = $this->replace_smart_tags(
					! empty( $rule['order_value']['max_message'] ) ? $rule['order_value']['max_message'] : __( 'Maximum checkout amount is {maximum_amount}.', 'wholesalex' ),
					array(
						'{maximum_amount}' => wc_price( $max ),
						'{cart_value}'     => wc_price( $cart_totals['subtotal'] ),
					)
				);
			}
		}

		// With no numeric limits, the product filter itself defines which cart
		// items are restricted from checkout, matching checkout dynamic rules.
		if ( ! $has_configured_limits && $cart_totals['quantity'] > 0 ) {
			$messages[] = ! empty( $rule['warning'] )
				? $rule['warning']
				: __( 'Please fulfill the cart limitation to checkout.', 'wholesalex' );
		} elseif ( ! empty( $messages ) && ! empty( $rule['warning'] ) ) {
			array_unshift( $messages, $rule['warning'] );
		}

		$this->validation = array(
			'is_valid' => empty( $messages ),
			'messages' => array_values( array_unique( array_filter( $messages ) ) ),
		);

		return $this->validation;
	}

	/**
	 * Build promotional messages for active checkout limits.
	 *
	 * @param array $rule Checkout rule.
	 * @return array
	 */
	private function get_promotion_messages( array $rule ) {
		$cart_totals = $this->get_matching_cart_totals( $rule );
		$messages    = array();

		if ( ! empty( $rule['quantity']['enabled'] ) ) {
			$template = ! empty( $rule['promotions']['quantity_text'] ) ? $rule['promotions']['quantity_text'] : __( 'Add {min_quantity} to {max_quantity} product(s) to checkout.', 'wholesalex' );
			$messages[] = $this->replace_smart_tags(
				$template,
				array(
					'{min_quantity}'  => isset( $rule['quantity']['min'] ) ? absint( $rule['quantity']['min'] ) : 0,
					'{max_quantity}'  => isset( $rule['quantity']['max'] ) ? absint( $rule['quantity']['max'] ) : 0,
					'{cart_quantity}' => $cart_totals['quantity'],
				)
			);
		}

		if ( ! empty( $rule['order_value']['enabled'] ) ) {
			$template = ! empty( $rule['promotions']['order_value_text'] ) ? $rule['promotions']['order_value_text'] : __( 'Spend {min_value} to {max_value} to checkout.', 'wholesalex' );
			$messages[] = $this->replace_smart_tags(
				$template,
				array(
					'{min_value}'  => wc_price( isset( $rule['order_value']['min'] ) ? (float) $rule['order_value']['min'] : 0 ),
					'{max_value}'  => wc_price( isset( $rule['order_value']['max'] ) ? (float) $rule['order_value']['max'] : 0 ),
					'{cart_value}' => wc_price( $cart_totals['subtotal'] ),
				)
			);
		}

		return array_values( array_filter( $messages ) );
	}

	/**
	 * Normalize checkout restriction rule for the current role.
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

		$data = isset( $restrictions['_checkout_restriction'] ) && is_array( $restrictions['_checkout_restriction'] ) ? $restrictions['_checkout_restriction'] : array();
		if ( ! $this->is_restriction_enabled( $restrictions, '_checkout_enabled', $data ) ) {
			return $this->rule;
		}

		$rule = array(
			'filter'           => $this->normalize_product_filter( isset( $data['filter'] ) ? $data['filter'] : ( isset( $restrictions['_checkout_filter'] ) ? $restrictions['_checkout_filter'] : 'specific_products' ) ),
			'products'         => isset( $data['products'] ) ? $data['products'] : ( isset( $restrictions['_checkout_products'] ) ? $restrictions['_checkout_products'] : array() ),
			'exclude_products' => isset( $data['exclude_products'] ) ? $data['exclude_products'] : ( isset( $restrictions['_checkout_exclude_products'] ) ? $restrictions['_checkout_exclude_products'] : array() ),
			'quantity'         => array(
				'enabled'     => $this->is_truthy( isset( $data['quantity']['enabled'] ) ? $data['quantity']['enabled'] : ( isset( $restrictions['_enable_min_max_quantity'] ) ? $restrictions['_enable_min_max_quantity'] : false ) ),
				'min'         => isset( $data['quantity']['min'] ) ? $data['quantity']['min'] : ( isset( $restrictions['_min_quantity'] ) ? $restrictions['_min_quantity'] : '' ),
				'max'         => isset( $data['quantity']['max'] ) ? $data['quantity']['max'] : ( isset( $restrictions['_max_quantity'] ) ? $restrictions['_max_quantity'] : '' ),
				'min_message' => isset( $data['quantity']['min_message'] ) ? $data['quantity']['min_message'] : ( isset( $restrictions['_min_quantity_warning'] ) ? $restrictions['_min_quantity_warning'] : '' ),
				'max_message' => isset( $data['quantity']['max_message'] ) ? $data['quantity']['max_message'] : ( isset( $restrictions['_max_quantity_warning'] ) ? $restrictions['_max_quantity_warning'] : '' ),
			),
			'order_value'      => array(
				'enabled'     => $this->is_truthy( isset( $data['order_value']['enabled'] ) ? $data['order_value']['enabled'] : ( isset( $restrictions['_enable_min_max_order_value'] ) ? $restrictions['_enable_min_max_order_value'] : false ) ),
				'min'         => isset( $data['order_value']['min'] ) ? $data['order_value']['min'] : ( isset( $restrictions['_min_order_value'] ) ? $restrictions['_min_order_value'] : '' ),
				'max'         => isset( $data['order_value']['max'] ) ? $data['order_value']['max'] : ( isset( $restrictions['_max_order_value'] ) ? $restrictions['_max_order_value'] : '' ),
				'min_message' => isset( $data['order_value']['min_message'] ) ? $data['order_value']['min_message'] : ( isset( $restrictions['_min_order_value_warning'] ) ? $restrictions['_min_order_value_warning'] : '' ),
				'max_message' => isset( $data['order_value']['max_message'] ) ? $data['order_value']['max_message'] : ( isset( $restrictions['_max_order_value_warning'] ) ? $restrictions['_max_order_value_warning'] : '' ),
			),
			'warning'          => isset( $data['warning'] ) ? (string) $data['warning'] : ( isset( $restrictions['_checkout_warning'] ) ? (string) $restrictions['_checkout_warning'] : '' ),
			'promotions'       => array(
				'enabled'          => $this->is_truthy( isset( $data['promotions']['enabled'] ) ? $data['promotions']['enabled'] : ( isset( $restrictions['show_promotions_on_sp'] ) ? $restrictions['show_promotions_on_sp'] : false ) ),
				'quantity_text'    => isset( $data['promotions']['quantity_text'] ) ? $data['promotions']['quantity_text'] : ( isset( $restrictions['only_total_cart_quantity_promo_text'] ) ? $restrictions['only_total_cart_quantity_promo_text'] : '' ),
				'order_value_text' => isset( $data['promotions']['order_value_text'] ) ? $data['promotions']['order_value_text'] : ( isset( $restrictions['only_total_cart_value_promo_text'] ) ? $restrictions['only_total_cart_value_promo_text'] : '' ),
			),
		);

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
	 * Check whether current user is excluded from checkout restriction.
	 *
	 * @param array $restrictions Role restrictions.
	 * @return bool
	 */
	private function is_current_user_excluded( array $restrictions ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$data = isset( $restrictions['_checkout_restriction'] ) && is_array( $restrictions['_checkout_restriction'] ) ? $restrictions['_checkout_restriction'] : array();

		$enabled = isset( $data['exclude_users']['enabled'] ) ? $data['exclude_users']['enabled'] : ( isset( $restrictions['_checkout_exclude_users'] ) ? $restrictions['_checkout_exclude_users'] : false );
		if ( ! $this->is_truthy( $enabled ) ) {
			return false;
		}

		$users   = isset( $data['exclude_users']['users'] ) ? $data['exclude_users']['users'] : ( isset( $restrictions['_checkout_exclude_users_list'] ) ? $restrictions['_checkout_exclude_users_list'] : array() );
		$user_id = (int) apply_filters( 'wholesalex_set_current_user', get_current_user_id() );

		return in_array( $user_id, $this->get_selected_ids( $users ), true );
	}

	/**
	 * Get matched cart quantity and subtotal.
	 *
	 * @param array $rule Checkout rule.
	 * @return array
	 */
	private function get_matching_cart_totals( array $rule ) {
		$totals = array(
			'quantity' => 0,
			'subtotal' => 0.0,
		);

		if ( ! WC()->cart ) {
			return $totals;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
				continue;
			}

			if ( ! $this->cart_item_matches_rule( $cart_item, $rule ) ) {
				continue;
			}

			$totals['quantity'] += isset( $cart_item['quantity'] ) ? absint( $cart_item['quantity'] ) : 0;
			$totals['subtotal'] += isset( $cart_item['line_subtotal'] ) ? (float) $cart_item['line_subtotal'] : ( (float) $cart_item['data']->get_price() * absint( $cart_item['quantity'] ) );
		}

		return $totals;
	}

	/**
	 * Check whether cart item matches checkout filter.
	 *
	 * @param array $cart_item Cart item.
	 * @param array $rule Checkout rule.
	 * @return bool
	 */
	private function cart_item_matches_rule( array $cart_item, array $rule ) {
		$product_id   = isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;
		$variation_id = isset( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0;
		$product_ids  = array_values( array_filter( array( $product_id, $variation_id ) ) );

		if ( ! empty( array_intersect( $product_ids, $this->get_selected_ids( $rule['exclude_products'] ) ) ) ) {
			return false;
		}

		$selected_ids = $this->get_selected_ids( $rule['products'] );

		switch ( $rule['filter'] ) {
			case 'all_products':
				return true;
			case 'specific_products':
				return ! empty( array_intersect( $product_ids, $selected_ids ) );
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
			'all'              => 'all_products',
			'cat_in_list'      => 'categories',
			'brand_in_list'    => 'brands',
			'products_in_list' => 'specific_products',
		);

		if ( isset( $map[ $filter ] ) ) {
			return $map[ $filter ];
		}

		return in_array( $filter, array( 'all_products', 'specific_products', 'categories', 'brands' ), true ) ? $filter : 'specific_products';
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
	 * Replace simple smart tags in a message.
	 *
	 * @param string $message Message template.
	 * @param array  $tags Smart tags.
	 * @return string
	 */
	private function replace_smart_tags( $message, array $tags ) {
		foreach ( $tags as $tag => $value ) {
			$message = str_replace( $tag, (string) $value, $message );
		}

		return $message;
	}
}
