<?php
/**
 * WholesaleX Dynamic Rules - Shipping Rule Handler
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WC_Shipping_Free_Shipping;

/**
 * Rule_Shipping
 */
class Rule_Shipping {

	/**
	 * Normalize saved shipping method values to comparable strings.
	 *
	 * Role settings save scalar instance IDs, while profile and dynamic rule
	 * fields save option arrays with a value key.
	 *
	 * @param array $methods Shipping methods.
	 * @return array
	 */
	private function normalize_method_ids( $methods ) {
		if ( ! is_array( $methods ) ) {
			return array();
		}

		$method_ids = array();
		foreach ( $methods as $method ) {
			if ( is_array( $method ) && isset( $method['value'] ) ) {
				$method = $method['value'];
			} elseif ( is_object( $method ) && isset( $method->value ) ) {
				$method = $method->value;
			}

			if ( '' === $method || null === $method ) {
				continue;
			}

			$method_ids[] = (string) $method;
		}

		return array_values( array_unique( $method_ids ) );
	}

	/**
	 * Get comparable identifiers for a WooCommerce shipping rate.
	 *
	 * @param \WC_Shipping_Rate $rate Shipping rate.
	 * @return array
	 */
	private function get_rate_ids( $rate ) {
		$rate_ids = array();

		$instance_id = method_exists( $rate, 'get_instance_id' ) ? $rate->get_instance_id() : '';
		if ( '' === $instance_id && isset( $rate->instance_id ) ) {
			$instance_id = $rate->instance_id;
		}
		if ( '' !== $instance_id && null !== $instance_id ) {
			$rate_ids[] = (string) $instance_id;
		}

		$rate_id = method_exists( $rate, 'get_id' ) ? $rate->get_id() : '';
		if ( '' === $rate_id && isset( $rate->id ) ) {
			$rate_id = $rate->id;
		}
		if ( '' !== $rate_id && null !== $rate_id ) {
			$rate_ids[] = (string) $rate_id;
		}

		return array_values( array_unique( $rate_ids ) );
	}

	/**
	 * Check whether a rate is part of the allowed shipping method set.
	 *
	 * @param \WC_Shipping_Rate $rate Shipping rate.
	 * @param array             $allowed_methods Allowed method IDs.
	 * @return bool
	 */
	private function is_rate_allowed( $rate, $allowed_methods ) {
		return ! empty( array_intersect( $this->get_rate_ids( $rate ), $allowed_methods ) );
	}

	/**
	 * Add WholesaleX shipping context to WooCommerce shipping packages.
	 *
	 * WooCommerce caches package rates by a package hash. Without the role,
	 * profile, and dynamic-rule shipping context in the package, checkout can
	 * keep showing stale methods after role settings change.
	 *
	 * @param array $packages Shipping packages.
	 * @param array $data Shipping rule data.
	 * @return array
	 */
	public function add_shipping_context_to_packages( $packages, $data ) {
		if ( ! is_array( $packages ) ) {
			return $packages;
		}

		$context = array(
			'role_id' => (string) wholesalex()->get_current_user_role(),
			'profile' => array(
				'method_type' => isset( $data['profile']['method_type'] ) ? $data['profile']['method_type'] : '',
				'zone'        => isset( $data['profile']['zone'] ) ? (string) $data['profile']['zone'] : '',
				'methods'     => isset( $data['profile']['methods'] ) ? $this->normalize_method_ids( $data['profile']['methods'] ) : array(),
			),
			'roles'   => isset( $data['roles'] ) ? $this->normalize_method_ids( $data['roles'] ) : array(),
			'rules'   => array(),
		);

		if ( isset( $data['rules'] ) && is_array( $data['rules'] ) ) {
			foreach ( $data['rules'] as $rule ) {
				$context['rules'][] = array(
					'id'         => isset( $rule['id'] ) ? (string) $rule['id'] : '',
					'methods'    => isset( $rule['rule']['_shipping_zone_methods'] ) ? $this->normalize_method_ids( $rule['rule']['_shipping_zone_methods'] ) : array(),
					'filter'     => isset( $rule['filter'] ) ? $rule['filter'] : array(),
					'conditions' => isset( $rule['conditions'] ) ? $rule['conditions'] : array(),
				);
			}
		}

		$context_json = wp_json_encode( $context );
		$context_hash = md5( is_string( $context_json ) ? $context_json : '' );
		foreach ( $packages as $package_key => $package ) {
			if ( is_array( $package ) ) {
				$packages[ $package_key ]['wholesalex_shipping_context'] = $context_hash;
			}
		}

		return $packages;
	}

	/**
	 * Handle Shipping related all kind of rules and options.
	 *
	 * @param array $data Data.
	 * Priority: Profile > User Role > Dynamic Rules.
	 */
	public function handle( $data ) {
		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) use ( $data ) {
				return $this->add_shipping_context_to_packages( $packages, $data );
			}
		);

		add_filter(
			'woocommerce_package_rates',
			function ( $package_rates, $package ) use ( $data ) {
				$data['roles'] = isset( $data['roles'] ) ? $this->normalize_method_ids( $data['roles'] ) : array();

				// Force Free Shipping For Specific.
				if ( isset( $data['profile']['method_type'] ) && 'force_free_shipping' === $data['profile']['method_type'] ) {
					$free_shipping = new WC_Shipping_Free_Shipping( 'wholesalex_free_shipping' );
					/* translators: %s: Plugin Name */
					$free_shipping->title = apply_filters( 'wholesalex_free_shipping_title', sprintf( __( '%s Free Shipping', 'wholesalex' ), wholesalex()->get_plugin_name() ) );
					$free_shipping->calculate_shipping( $package );
					return apply_filters( 'wholesalex_available_shipping_methods', $free_shipping->rates, $package_rates, $package, $data );
				}

				if ( isset( $data['profile']['method_type'] ) && 'specific_shipping_methods' === $data['profile']['method_type'] ) {
					$all_methods     = $package_rates;
					$allowed_methods = isset( $data['profile']['methods'] ) ? $this->normalize_method_ids( $data['profile']['methods'] ) : array();

					foreach ( $package_rates as $rate_key => $rate ) {
						if ( ! $this->is_rate_allowed( $rate, $allowed_methods ) ) {
							unset( $package_rates[ $rate_key ] );
						}
					}
					return apply_filters( 'wholesalex_available_shipping_methods', $package_rates, $all_methods, $package, $data );
				}

				if ( empty( $data['roles'] ) ) {
					foreach ( $package_rates as $rate ) {
						$data['roles'] = array_merge( $data['roles'], $this->get_rate_ids( $rate ) );
					}
					$data['roles'] = array_values( array_unique( $data['roles'] ) );
				}

				if ( ! empty( $data['rules'] ) ) {
					$allowed_rates = array();
					foreach ( $data['rules'] as $rule ) {
						if ( isset( $rule['conditions'] ) && ! Dynamic_Rules_Condition_Engine::check_rule_conditions( $rule['conditions'], $rule['filter'] ) ) {
							continue;
						}

						$available_methods = array();

						$temp_available_methods = $data['roles'];

						if ( ! empty( $rule['rule']['_shipping_zone_methods'] ) ) {
							$available_methods = $this->normalize_method_ids( $rule['rule']['_shipping_zone_methods'] );
						}

						if ( isset( $rule['filter']['is_all_products'] ) && $rule['filter']['is_all_products'] ) {
							$temp_available_methods = array_intersect( $temp_available_methods, $available_methods );
							wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
						} elseif ( isset( $package['contents'] ) ) {
							foreach ( $package['contents'] as $item ) {
								if ( Dynamic_Rules_Condition_Engine::is_eligible_for_rule( $item['product_id'], $item['variation_id'], $rule['filter'] ) ) {
									$temp_available_methods = array_intersect( $temp_available_methods, $available_methods );
									wholesalex()->set_usages_dynamic_rule_id( $rule['id'] );
								} elseif ( apply_filters( 'wholesalex_shipping_rule_merged_all_methods', false ) ) {
										$temp_available_methods = array_merge( $temp_available_methods, $data['roles'] );
								} else {
									$temp_available_methods = array_diff( $data['roles'], $temp_available_methods );
								}
							}
						}

						foreach ( $package_rates as $rate_key => $rate ) {
							if ( $this->is_rate_allowed( $rate, $temp_available_methods ) ) {
								$allowed_rates[ $rate_key ] = $package_rates[ $rate_key ];
							}
						}
					}

					if ( ! empty( $allowed_rates ) ) {
						return apply_filters( 'wholesalex_available_shipping_methods', $allowed_rates, $package_rates, $package, $data );
					}
				}

				$allowed_rates = array();
				foreach ( $package_rates as $rate_key => $rate ) {
					if ( $this->is_rate_allowed( $rate, $data['roles'] ) ) {
						$allowed_rates[ $rate_key ] = $package_rates[ $rate_key ];
					}
				}
				return apply_filters( 'wholesalex_available_shipping_methods', $allowed_rates, $package_rates, $package, $data );
			},
			10,
			2
		);
	}
}
