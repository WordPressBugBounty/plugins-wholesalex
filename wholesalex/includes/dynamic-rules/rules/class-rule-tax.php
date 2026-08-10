<?php
/**
 * WholesaleX Dynamic Rules - Tax Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rule_Tax
 */
class Rule_Tax {

	/**
	 * Product page notices (tax-free flags).
	 *
	 * @var array
	 */
	public $product_page_notices = array();

	/**
	 * Whether order exemption hooks have been registered for this request.
	 *
	 * @var bool
	 */
	private $order_tax_exemption_hooks_added = false;

	/**
	 * Whether the current rule evaluation exempts the complete order.
	 *
	 * @var bool
	 */
	private $is_full_order_tax_exempt = false;

	/**
	 * Active product-scoped tax exemption rules.
	 *
	 * @var array
	 */
	private $tax_exemption_rules = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'maybe_zero_order_item_taxes' ), 20, 2 );
	}

	/**
	 * Handle Tax Related Rules and Other Stuffs.
	 * First will check For profile exemption, if found then do it
	 * otherwise will check for dynamic rules.
	 *
	 * @param array $data Data.
	 * Priority: Profile > Dynamic Rules.
	 */
	public function handle( $data ) {
		$current_user_id = get_current_user_id();
		$customer        = new \WC_Customer( $current_user_id );

		if ( null === WC()->customer ) {
			WC()->customer = $customer;
		}

		if ( is_admin() || null === WC()->customer ) {
			return;
		}

		$status                 = false;
		$is_customer_vat_exempt = false;

		if ( isset( $data['profile_exemption'] ) && 'yes' === $data['profile_exemption'] ) {
			$is_customer_vat_exempt = true;
			$status                 = true;
		}
		if ( isset( $data['profile_exemption'] ) && 'no' === $data['profile_exemption'] ) {
			$is_customer_vat_exempt = false;
			$status                 = true;
		}

		if ( ! $status && isset( $data['rules'] ) && is_array( $data['rules'] ) && ! empty( $data['rules'] ) ) {
			$is_customer_vat_exempt = '';

			foreach ( $data['rules'] as $rule ) {
				if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
					continue;
				}

				if ( isset( $rule['rule']['_tax_exempted'] ) && 'yes' === $rule['rule']['_tax_exempted'] ) {
					$rule_key                                = isset( $rule['id'] ) ? (string) $rule['id'] : md5( wp_json_encode( $rule ) );
					$this->tax_exemption_rules[ $rule_key ] = $rule;
				}

				if ( isset( $rule['filter']['is_all_products'] ) && $rule['filter']['is_all_products'] ) {
					$is_tax_exempt          = isset( $rule['rule']['_tax_exempted'] ) ? $rule['rule']['_tax_exempted'] : '';
					$is_based_on_country    = isset( $rule['rule']['_exempted_country'] ) ? $rule['rule']['_exempted_country'] : false;
					$is_customer_vat_exempt = 'yes' === $is_tax_exempt ? true : false;
					if ( $is_based_on_country ) {
						$allowed_country = Dynamic_Rules_Condition_Engine::get_multiselect_values( $is_based_on_country );
						$user_country    = '';

						if ( is_a( WC()->customer, 'WC_Customer' ) ) {
							$tax_setting = get_option( 'woocommerce_tax_based_on' );
							if ( 'shipping' === $tax_setting ) {
								$user_country = WC()->customer->get_shipping_country();
							} else {
								$user_country = WC()->customer->get_billing_country();
							}
						} else {
							$user_country = 'NAC';
						}

						if ( ! in_array( $user_country, $allowed_country, true ) ) {
							$is_customer_vat_exempt = false;
						}
					}

					$this->add_tax_class_filter( 'woocommerce_product_get_tax_class', $rule );
					$this->add_tax_class_filter( 'woocommerce_product_variation_get_tax_class', $rule );

					wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
				} else {
					$this->add_tax_class_filter( 'woocommerce_product_get_tax_class', $rule );
					$this->add_tax_class_filter( 'woocommerce_product_variation_get_tax_class', $rule );
				}
			}

			if ( $is_customer_vat_exempt ) {
				add_filter(
					'woocommerce_package_rates',
					function ( $rates, $package ) {
						foreach ( $rates as $rate_key => $rate ) {
							$rates[ $rate_key ]->taxes = array_map(
								function () {
									return 0;
								},
								$rate->taxes
							);
						}
						return $rates;
					},
					20,
					2
				);
			}
		}
		$this->is_full_order_tax_exempt = (bool) $is_customer_vat_exempt;
		if ( $this->is_full_order_tax_exempt ) {
			$this->add_order_tax_exemption_hooks();

			add_filter(
				'woocommerce_product_get_tax_class',
				function () {
					return 'zero-rate';
				}
			);
			add_filter(
				'woocommerce_product_variation_get_tax_class',
				function () {
					return 'zero-rate';
				}
			);
		}
	}

	/**
	 * Keep full tax exemptions active during classic and Store API checkout.
	 *
	 * @return void
	 */
	private function add_order_tax_exemption_hooks() {
		if ( $this->order_tax_exemption_hooks_added ) {
			return;
		}

		add_filter( 'woocommerce_order_is_vat_exempt', array( $this, 'filter_order_tax_exempt' ), 20, 2 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'mark_order_tax_exempt' ), 20 );
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'mark_order_tax_exempt' ), 20 );
		$this->order_tax_exemption_hooks_added = true;
	}

	/**
	 * Prevent WooCommerce order recalculation from restoring tax.
	 *
	 * @param bool      $is_exempt Existing VAT-exempt status.
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function filter_order_tax_exempt( $is_exempt, $order ) {
		if ( ! $this->is_full_order_tax_exempt ) {
			return $is_exempt;
		}

		if ( $order instanceof \WC_Order ) {
			$order->update_meta_data( 'is_vat_exempt', 'yes' );
		}

		return true;
	}

	/**
	 * Persist the exemption on a newly created order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	public function mark_order_tax_exempt( $order ) {
		if ( $this->is_full_order_tax_exempt && $order instanceof \WC_Order ) {
			$order->update_meta_data( 'is_vat_exempt', 'yes' );
		}
	}

	/**
	 * Reapply a scoped exemption after WooCommerce calculates an order item.
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @param array          $calculate_tax_for Tax location.
	 * @return void
	 */
	public function maybe_zero_order_item_taxes( $item, $calculate_tax_for ) {
		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}

		if ( $this->is_product_tax_exempt( $item->get_product() ) ) {
			$item->set_taxes( false );
		}
	}

	/**
	 * Check whether any active dynamic tax rule exempts a product.
	 *
	 * @param \WC_Product|false $product Product object.
	 * @return bool
	 */
	private function is_product_tax_exempt( $product ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}

		foreach ( $this->tax_exemption_rules as $rule ) {
			if ( $this->rule_matches_product_and_country( $product, $rule ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check product and country filters for a dynamic tax exemption rule.
	 *
	 * @param \WC_Product $product Product object.
	 * @param array       $rule Dynamic rule.
	 * @return bool
	 */
	private function rule_matches_product_and_country( $product, $rule ) {
		if ( ! $product instanceof \WC_Product || empty( $rule['filter'] ) ) {
			return false;
		}

		$product_id   = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$variation_id = $product->get_parent_id() ? $product->get_id() : 0;
		if ( ! Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $product_id, $variation_id, $rule['filter'] ) ) {
			return false;
		}

		$is_based_on_country = isset( $rule['rule']['_exempted_country'] ) ? $rule['rule']['_exempted_country'] : false;
		if ( ! $is_based_on_country ) {
			return true;
		}

		$allowed_country = Dynamic_Rules_Condition_Engine::get_multiselect_values( $is_based_on_country );
		if ( ! is_a( WC()->customer, 'WC_Customer' ) ) {
			return false;
		}

		$tax_setting = get_option( 'woocommerce_tax_based_on' );
		$user_country = 'shipping' === $tax_setting ? WC()->customer->get_shipping_country() : WC()->customer->get_billing_country();

		return in_array( $user_country, $allowed_country, true );
	}

	/**
	 * Add tax class filter for a specific hook.
	 *
	 * @param string $hook  WooCommerce filter hook name.
	 * @param array  $rule  Rule data.
	 */
	private function add_tax_class_filter( $hook, $rule ) {
		add_filter(
			$hook,
			function ( $tax_class, $product ) use ( $rule ) {
				$rule_tax_class = isset( $rule['rule']['_tax_class'] ) ? $rule['rule']['_tax_class'] : '';
				$is_tax_exempt  = isset( $rule['rule']['_tax_exempted'] ) ? $rule['rule']['_tax_exempted'] : '';
				if ( ! $this->rule_matches_product_and_country( $product, $rule ) ) {
					return $tax_class;
				}

				if ( 'yes' === $is_tax_exempt ) {
					$tax_class = 'zero-rate';
					if ( ! isset( $this->product_page_notices[ $product->get_id() ] ) || ! is_array( $this->product_page_notices[ $product->get_id() ] ) ) {
						$this->product_page_notices[ $product->get_id() ] = array();
					}
					$this->product_page_notices[ $product->get_id() ]['tax_free'] = true;
				} else {
					$tax_class = $rule_tax_class;
				}
				if ( isset( $rule['id'] ) ) {
					wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
				}
				return $tax_class;
			},
			10,
			2
		);
	}
}
