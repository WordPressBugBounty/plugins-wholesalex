<?php
/**
 * WholesaleX User Roles - Payment Method Rules
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles role-level payment method discounts and extra charges.
 */
class User_Roles_Payment_Method {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_fees' ) );
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'add_checkout_payment_refresh_script' ) );
	}

	/**
	 * Add role-level payment method fees to the cart.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	public function add_fees( $cart ) {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$rules = $this->get_current_role_rules();
		if ( empty( $rules ) ) {
			return;
		}

		$fees = $this->calculate_fees( $rules );
		if ( empty( $fees ) ) {
			return;
		}

		$fee_names = array();
		foreach ( $fees as $fee ) {
			if ( isset( $fee['discount'] ) && 0 !== $fee['discount'] ) {
				$is_taxable = apply_filters( 'wholesalex_payment_gateway_discount_is_taxable', false );
				$name       = $this->get_unique_fee_name( $fee['name'], $fee_names );
				$cart->add_fee( $name, -1 * (float) $fee['discount'], $is_taxable );
			} elseif ( isset( $fee['charge'] ) && 0 !== $fee['charge'] ) {
				$is_taxable = apply_filters( 'wholesalex_extra_charge_is_taxable', true );
				$name       = $this->get_unique_fee_name( $fee['name'], $fee_names );
				$cart->add_fee( $name, (float) $fee['charge'], $is_taxable );
			}
		}
	}

	/**
	 * Add checkout refresh script when role payment rules exist.
	 *
	 * @return void
	 */
	public function add_checkout_payment_refresh_script() {
		static $script_added = false;

		if ( $script_added || empty( $this->get_current_role_rules() ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		$script_added = true;
		?>
		<script type="text/javascript">
			jQuery(function($) {
				$('form.woocommerce-checkout').on('change', 'input[name="payment_method"]', function() {
					$('body').trigger('update_checkout');
				});
			})
		</script>
		<?php
	}

	/**
	 * Calculate role-level payment method discount and extra charge rules.
	 *
	 * @param array $rules Payment method rules keyed by gateway id.
	 * @return array Hash of fee data.
	 */
	public function calculate_fees( $rules ) {
		$hash             = array();
		$selected_gateway = WC()->session->get( 'chosen_payment_method' );

		if ( empty( $selected_gateway ) || empty( $rules[ $selected_gateway ] ) || ! isset( WC()->cart ) || null === WC()->cart->get_cart() ) {
			return $hash;
		}

		$method_rules = $this->normalize_rule( $rules[ $selected_gateway ] );

		if ( '' !== $method_rules['discount']['value'] ) {
			$discount_context = $this->get_discount_cart_context( $method_rules['discount'] );
			$required_qty     = $method_rules['discount']['min_qty'];

			if ( $discount_context['has_match'] && ( '' === $required_qty || $discount_context['quantity'] >= (float) $required_qty ) ) {
				$discount_type   = $method_rules['discount']['type'];
				$discount_amount = 'percentage' === $discount_type ? ( $discount_context['total'] * (float) $method_rules['discount']['value'] ) / 100 : (float) $method_rules['discount']['value'];

				if ( $discount_amount > 0 ) {
					$hash[ 'role_payment_discount_' . $selected_gateway ] = array(
						'discount' => $discount_amount,
						'name'     => apply_filters( 'wholesalex_role_payment_method_discount_name', __( 'Payment Method Discount!', 'wholesalex' ), $selected_gateway, $method_rules ),
					);
				}
			}
		}

		if ( 'yes' === $method_rules['extra_charge']['enabled'] && '' !== $method_rules['extra_charge']['value'] ) {
			$cart_total = wholesalex()->get_cart_total();
			$charge     = 'percentage' === $method_rules['extra_charge']['type'] ? ( $cart_total * (float) $method_rules['extra_charge']['value'] ) / 100 : (float) $method_rules['extra_charge']['value'];

			if ( $charge > 0 ) {
				$hash[ 'role_payment_extra_charge_' . $selected_gateway ] = array(
					'charge' => $charge,
					'name'   => apply_filters( 'wholesalex_role_payment_method_extra_charge_name', __( 'Payment Method Extra Charge:', 'wholesalex' ), $selected_gateway, $method_rules ),
				);
			}
		}

		return $hash;
	}

	/**
	 * Get payment method rules for the current role.
	 *
	 * @return array
	 */
	private function get_current_role_rules() {
		$role_id = wholesalex()->get_current_user_role();
		if ( empty( $role_id ) ) {
			return array();
		}

		$role_content = wholesalex()->get_roles( 'by_id', $role_id );
		if ( isset( $role_content['_payment_method_rules'] ) && is_array( $role_content['_payment_method_rules'] ) ) {
			return $role_content['_payment_method_rules'];
		}

		return array();
	}

	/**
	 * Get a unique fee name within this role payment method run.
	 *
	 * @param string $name Fee name.
	 * @param array  $fee_names Existing fee names.
	 * @return string
	 */
	private function get_unique_fee_name( $name, &$fee_names ) {
		if ( isset( $fee_names[ $name ] ) ) {
			$name = wp_unique_id( $name );
		}

		$fee_names[ $name ] = true;
		return $name;
	}

	/**
	 * Normalise role payment method rules. Supports the current nested schema and previous flat keys.
	 *
	 * @param array $rules Gateway rule data.
	 * @return array
	 */
	private function normalize_rule( $rules ) {
		$discount     = isset( $rules['discount'] ) && is_array( $rules['discount'] ) ? $rules['discount'] : array();
		$extra_charge = isset( $rules['extra_charge'] ) && is_array( $rules['extra_charge'] ) ? $rules['extra_charge'] : array();

		return array(
			'discount'     => array(
				'value'            => isset( $discount['value'] ) ? $discount['value'] : ( isset( $rules['_discount_amount'] ) ? $rules['_discount_amount'] : '' ),
				'type'             => $this->normalize_amount_type( isset( $discount['type'] ) ? $discount['type'] : ( isset( $rules['_discount_type'] ) ? $rules['_discount_type'] : 'percentage' ) ),
				'min_qty'          => isset( $discount['min_qty'] ) ? $discount['min_qty'] : ( isset( $rules['_min_qty'] ) ? $rules['_min_qty'] : '' ),
				'product_filter'   => $this->normalize_product_filter( isset( $discount['product_filter'] ) ? $discount['product_filter'] : ( isset( $rules['_filter_type'] ) ? $rules['_filter_type'] : 'all_products' ) ),
				'products'         => isset( $discount['products'] ) && is_array( $discount['products'] ) ? $discount['products'] : ( isset( $rules['_products'] ) && is_array( $rules['_products'] ) ? $rules['_products'] : array() ),
				'categories'       => isset( $discount['categories'] ) && is_array( $discount['categories'] ) ? $discount['categories'] : ( isset( $rules['_categories'] ) && is_array( $rules['_categories'] ) ? $rules['_categories'] : array() ),
				'brands'           => isset( $discount['brands'] ) && is_array( $discount['brands'] ) ? $discount['brands'] : ( isset( $rules['_brands'] ) && is_array( $rules['_brands'] ) ? $rules['_brands'] : array() ),
				'attributes'       => isset( $discount['attributes'] ) && is_array( $discount['attributes'] ) ? $discount['attributes'] : ( isset( $rules['_attributes'] ) && is_array( $rules['_attributes'] ) ? $rules['_attributes'] : array() ),
				'exclude_products' => isset( $discount['exclude_products'] ) && is_array( $discount['exclude_products'] ) ? $discount['exclude_products'] : ( isset( $rules['_exclude_products'] ) && is_array( $rules['_exclude_products'] ) ? $rules['_exclude_products'] : array() ),
			),
			'extra_charge' => array(
				'enabled' => isset( $extra_charge['enabled'] ) ? $extra_charge['enabled'] : ( isset( $rules['_enable_extra_charge'] ) ? $rules['_enable_extra_charge'] : ( isset( $rules['_extra_charge'] ) && '' !== $rules['_extra_charge'] ? 'yes' : 'no' ) ),
				'value'   => isset( $extra_charge['value'] ) ? $extra_charge['value'] : ( isset( $rules['_extra_charge'] ) ? $rules['_extra_charge'] : '' ),
				'type'    => $this->normalize_amount_type( isset( $extra_charge['type'] ) ? $extra_charge['type'] : ( isset( $rules['_extra_charge_type'] ) ? $rules['_extra_charge_type'] : 'percentage' ) ),
			),
		);
	}

	/**
	 * Normalize payment rule amount type.
	 *
	 * @param string $type Amount type.
	 * @return string
	 */
	private function normalize_amount_type( $type ) {
		return 'percentage' === $type ? 'percentage' : 'amount';
	}

	/**
	 * Normalize payment rule product filter.
	 *
	 * @param string $product_filter Product filter.
	 * @return string
	 */
	private function normalize_product_filter( $product_filter ) {
		$filter_map = array(
			'products_in_list' => 'specific_products',
			'cat_in_list'      => 'categories',
			'brand_in_list'    => 'brands',
			'att_in_list'      => 'attributes',
		);

		if ( isset( $filter_map[ $product_filter ] ) ) {
			return $filter_map[ $product_filter ];
		}

		return in_array( $product_filter, array( 'specific_products', 'categories', 'brands', 'attributes' ), true ) ? $product_filter : 'all_products';
	}

	/**
	 * Build eligible cart total and quantity for a role payment discount rule.
	 *
	 * @param array $discount Discount rule data.
	 * @return array
	 */
	private function get_discount_cart_context( $discount ) {
		$filter_values     = $this->get_filter_values( $discount );
		$excluded_products = $this->get_product_ids( $discount['exclude_products'] );
		$is_all_products   = 'all_products' === $discount['product_filter'];
		$total             = 0.0;
		$quantity          = 0;

		if ( ! $is_all_products && empty( $filter_values ) ) {
			return array(
				'total'     => 0,
				'quantity'  => 0,
				'has_match' => false,
			);
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
			$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
			$product_ids  = array_filter( array( $product_id, $variation_id ) );

			if ( ! empty( $excluded_products ) && ! empty( array_intersect( $product_ids, $excluded_products ) ) ) {
				continue;
			}

			if ( ! $is_all_products && ! $this->cart_item_matches_filter( $cart_item, $discount['product_filter'], $filter_values ) ) {
				continue;
			}

			$total    += isset( $cart_item['line_total'] ) ? (float) $cart_item['line_total'] : 0;
			$quantity += isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;

			if ( apply_filters( 'wholesalex_role_payment_discount_on_tax', false ) && isset( $cart_item['line_tax'] ) ) {
				$total += (float) $cart_item['line_tax'];
			}
		}

		return array(
			'total'     => $total,
			'quantity'  => $quantity,
			'has_match' => $quantity > 0,
		);
	}

	/**
	 * Get selected ids for the active product filter.
	 *
	 * @param array $discount Discount rule data.
	 * @return array
	 */
	private function get_filter_values( $discount ) {
		switch ( $discount['product_filter'] ) {
			case 'specific_products':
				return $this->get_product_ids( $discount['products'] );
			case 'categories':
				return $this->get_selected_ids( $discount['categories'] );
			case 'brands':
				return $this->get_selected_ids( $discount['brands'] );
			case 'attributes':
				return $this->get_selected_ids( $discount['attributes'] );
			default:
				return array();
		}
	}

	/**
	 * Check whether a cart item matches the configured product filter.
	 *
	 * @param array  $cart_item Cart item.
	 * @param string $product_filter Product filter.
	 * @param array  $filter_values Selected ids.
	 * @return bool
	 */
	private function cart_item_matches_filter( $cart_item, $product_filter, $filter_values ) {
		$product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
		$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
		$product_ids  = array_filter( array( $product_id, $variation_id ) );

		switch ( $product_filter ) {
			case 'specific_products':
				return ! empty( array_intersect( $product_ids, $filter_values ) );
			case 'categories':
				return ! empty( array_intersect( wc_get_product_term_ids( $product_id, 'product_cat' ), $filter_values ) );
			case 'brands':
				$brand_taxonomy = $this->get_brand_taxonomy();
				return $brand_taxonomy && ! empty( array_intersect( wc_get_product_term_ids( $product_id, $brand_taxonomy ), $filter_values ) );
			case 'attributes':
				return ! empty( array_intersect( $this->get_product_attribute_ids( $product_id ), $filter_values ) );
			default:
				return true;
		}
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
	 * Get all registered attribute ids used by a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array
	 */
	private function get_product_attribute_ids( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return array();
		}

		$attribute_ids          = array();
		$attributes             = $product->get_attributes();
		$registered_attributes  = wc_get_attribute_taxonomies();
		$taxonomy_to_id_map     = array();

		foreach ( $registered_attributes as $registered_attr ) {
			$taxonomy_name                        = wc_attribute_taxonomy_name( $registered_attr->attribute_name );
			$taxonomy_to_id_map[ $taxonomy_name ] = (int) $registered_attr->attribute_id;
		}

		foreach ( $attributes as $attribute ) {
			if ( ! is_object( $attribute ) || ! method_exists( $attribute, 'is_taxonomy' ) || ! $attribute->is_taxonomy() ) {
				continue;
			}

			$taxonomy = $attribute->get_taxonomy();
			if ( isset( $taxonomy_to_id_map[ $taxonomy ] ) ) {
				$attribute_ids[] = $taxonomy_to_id_map[ $taxonomy ];
			}
		}

		return array_values( array_unique( $attribute_ids ) );
	}

	/**
	 * Extract product ids from a multiselect value list.
	 *
	 * @param array $products Product multiselect data.
	 * @return array
	 */
	private function get_product_ids( $products ) {
		return $this->get_selected_ids( $products );
	}

	/**
	 * Extract ids from a multiselect value list.
	 *
	 * @param array $items Multiselect data.
	 * @return array
	 */
	private function get_selected_ids( $items ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return array();
		}

		$ids = array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) && isset( $item['value'] ) ) {
				$ids[] = (int) $item['value'];
			} elseif ( is_numeric( $item ) ) {
				$ids[] = (int) $item;
			}
		}

		return array_values( array_filter( array_unique( $ids ) ) );
	}
}
