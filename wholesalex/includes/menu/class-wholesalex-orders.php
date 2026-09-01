<?php
/**
 * Orders Action.
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

/**
 * WholesaleX Category Class.
 */
class WHOLESALEX_Orders {

	/**
	 * Order Constructor
	 */
	public function __construct() {
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_type_column_on_order_page' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'populate_data_on_order_type_column' ), 10, 2 );
	}

	/**
	 * Add Order Type Column On Order Page.
	 *
	 * @param array $columns Order Columns.
	 * @return array
	 */
	public function add_order_type_column_on_order_page( $columns ) {
		$columns = array_slice( $columns, 0, 4, true )
		+ array( 'wholesalex_order_type' => __( 'Order Type', 'wholesalex' ) )
		+ array_slice( $columns, 4, null, true );
		return $columns;
	}

	/**
	 * Populate Data on Order Type Column on Orders page
	 *
	 * @param string $column Order Page Column.
	 * @param int    $order_id Order ID.
	 */
	public function populate_data_on_order_type_column( $column, $order_id ) {

		if ( 'wholesalex_order_type' === $column ) {
			$order      = wc_get_order( $order_id );
			$order_type = $order->get_meta( '__wholesalex_order_type' );
			if ( empty( $order_type ) ) {
				$customer_id = $order->get_user_id();
				if ( $customer_id ) {
					$user_role  = get_user_meta( $customer_id, '__wholesalex_role', true );
					$order_type = in_array( $user_role, array( '', 'wholesalex_guest', 'wholesalex_b2c_users' ), true ) ? 'b2c' : 'b2b';
				} else {
					$order_type = 'guest';
				}
			}
			if ( 'b2c' === $order_type && 0 === $order->get_user_id() ) {
				$order_type = 'guest';
			}
			if ( 'b2b' === $order_type ) {
				$role_id    = $order->get_meta( '__wholesalex_order_role' );
				$role_id    = $role_id ? $role_id : get_user_meta( $order->get_user_id(), '__wholesalex_role', true );
				$role_title = wholesalex()->get_role_name_by_role_id( $role_id );
				if ( $role_title ) {
					/* translators: 1: Plugin name, 2: B2B role title. */
					$__custom_meta_value = sprintf( __( '%1$s B2B (%2$s)', 'wholesalex' ), wholesalex()->get_plugin_name(), $role_title );
				} else {
					/* translators: %s: Plugin Name */
					$__custom_meta_value = sprintf( __( '%s B2B', 'wholesalex' ), wholesalex()->get_plugin_name() );
				}
				$__custom_meta_value = apply_filters( 'wholesalex_order_meta_b2b_value', $__custom_meta_value, $order, $role_title );
				echo esc_html( $__custom_meta_value );
			} elseif ( 'b2c' === $order_type ) {
				/* translators: %s: Plugin Name */
				$__custom_meta_value = apply_filters( 'wholesalex_order_meta_b2c_value', sprintf( __( '%s B2C', 'wholesalex' ), wholesalex()->get_plugin_name() ) );
				echo esc_html( $__custom_meta_value );
			} elseif ( 'guest' === $order_type ) {
				/* translators: %s: Plugin Name */
				$__custom_meta_value = apply_filters( 'wholesalex_order_meta_guest_value', sprintf( __( '%s Guest', 'wholesalex' ), wholesalex()->get_plugin_name() ) );
				echo esc_html( $__custom_meta_value );
			}
		}
	}
}
