<?php
/**
 * Product
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

/**
 * WholesaleX Product Class
 */
class WHOLESALEX_Product {

	/**
	 * Rule on Lists
	 *
	 * @var array
	 */
	public $rule_on_lists = array();
	/**
	 * Rule on Lists
	 *
	 * @var array
	 */
	public $rule_on_products_lists = array();

	/**
	 * Rule on Products Lists Data
	 *
	 * @var array
	 */
	public $rule_on_products_lists_data = array();

	/**
	 * Product Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_options_pricing', array( $this, 'wholesalex_single_product_settings' ) );
		add_filter( 'woocommerce_product_after_variable_attributes', array( $this, 'wholesalex_single_product_settings' ), 10, 3 );
		add_action( 'woocommerce_process_product_meta_simple', array( $this, 'wholesalex_product_meta_save' ) );
		add_action( 'woocommerce_process_product_meta_yith_bundle', array( $this, 'wholesalex_product_meta_save' ) );
		add_action( 'woocommerce_save_product_variation', array( $this, 'wholesalex_product_meta_save' ) );
		add_action( 'rest_api_init', array( $this, 'get_product_callback' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_custom_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'wsx_tab_data' ) );
		add_action( 'save_post', array( $this, 'product_settings_data_save' ) );
		/**
		 * Use of Pre Get Posts Hook instead of woocommerce_product_query.
		 *
		 * @since 1.0.2
		 */
		add_filter( 'pre_get_posts', array( $this, 'control_single_product_visibility' ) );
		add_filter( 'woocommerce_product_query', array( $this, 'control_single_product_visibility' ) );
		add_action( 'template_redirect', array( $this, 'redirect_from_hidden_products' ) );
		add_action( 'woocommerce_check_cart_items', array( $this, 'prevent_checkout_hidden_products' ) );
		/**
		 * Remove Hidden Product From Related Products.
		 *
		 * @since 1.0.2
		 */
		add_filter( 'woocommerce_related_products', array( $this, 'filter_hidden_products' ) );

		/**
		 * Add WholesaleX Rule on Column on All Products Page.
		 *
		 * @since 1.0.4
		 */
		add_filter( 'manage_edit-product_columns', array( $this, 'add_wholesalex_rule_on_column_on_product_page' ) );

		add_action( 'manage_product_posts_custom_column', array( $this, 'populate_data_on_wholesalex_rule_on_column' ), 10, 2 );

		/**
		 * Add More Tier Layout
		 *
		 * @since 1.0.6 Tier layouts added on v1.0.1 But Code was refactored on v1.0.6.
		 */
		if ( wholesalex()->is_pro_active() && version_compare( WHOLESALEX_PRO_VER, '1.0.6', '>=' ) ) {
			add_filter( 'wholesalex_single_product_tier_layout', array( $this, 'add_more_tier_layouts' ), 20 );
			add_filter( 'wholesalex_settings_product_tier_layout', array( $this, 'add_more_tier_layouts' ), 20 );
		} else {
			add_filter( 'wholesalex_single_product_tier_layout', array( $this, 'add_more_tier_layouts' ), 1 );
			add_filter( 'wholesalex_settings_product_tier_layout', array( $this, 'add_more_tier_layouts' ), 1 );
		}

		add_action( 'woocommerce_process_product_meta', array( $this, 'after_product_update' ), 1 );

		/**
		 * WooCommerce Importer and Exporter Integration On Single Product WholesaleX Rolewise Price.
		 *
		 * @since 1.1.5
		 */
		add_filter( 'woocommerce_product_export_product_default_columns', array( $this, 'add_wholesale_rolewise_column_exporter' ), 99999 );
		$wholesalex_roles = wholesalex()->get_roles( 'b2b_roles_option' );
		foreach ( $wholesalex_roles as $role ) {
			$base_price_meta_key = $role['value'] . '_base_price';
			$sale_price_meta_key = $role['value'] . '_sale_price';
			add_filter( "woocommerce_product_export_product_column_{$base_price_meta_key}", array( $this, 'export_column_value' ), 99999, 3 );
			add_filter( "woocommerce_product_export_product_column_{$sale_price_meta_key}", array( $this, 'export_column_value' ), 99999, 3 );
		}

		add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'import_column_mapping' ) );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array( $this, 'import_column_mapping' ) );
		add_filter( 'woocommerce_product_import_inserted_product_object', array( $this, 'process_import' ), 10, 2 );

		add_action( 'woocommerce_variable_product_bulk_edit_actions', array( $this, 'variable_product_bulk_edit_actions' ) );

		add_action( 'wp_ajax_wholesalex_bulk_edit_variations', array( $this, 'handle_wholesalex_bulk_edit_variations' ) );

		if ( 'yes' === wholesalex()->get_setting( 'b2b_stock_management_status', 'no' ) ) {
			$this->b2b_stock_management();
		}
		add_action( 'admin_footer', array( $this, 'render_wsx_product_rule_modal' ) );
		add_action( 'yith_wcpb_after_product_bundle_options_tab', array( $this, 'wsx_single_product_role_wise_price_option_added' ) );
	}

	/**
	 * Compatibility with Bundle Product Plugin.
	 */
	public function wsx_single_product_role_wise_price_option_added() {

		$post_id   = get_the_ID();
		$discounts = array();
		if ( $post_id ) {
			$product = wc_get_product( $post_id );
			if ( $product ) {
				$is_variable = 'variable' === $product->get_type();
				if ( $is_variable ) {
					if ( $product->has_child() ) {
						$childrens = $product->get_children();
						foreach ( $childrens as $key => $child_id ) {
							$discounts[ $child_id ] = wholesalex()->get_single_product_discount( $child_id );
						}
					}
				} else {
					$discounts[ $post_id ] = wholesalex()->get_single_product_discount( $post_id );
				}
			}
		}
		wp_localize_script(
			'wholesalex_components',
			'wholesalex_single_product',
			array(
				'fields'    => self::get_product_fields(),
				'discounts' => $discounts,
			),
		);

		?>
		<div class="wsx-bundle-product-wrapper"></div>
		<?php
	}

	/**
	 * Add WholesaleX Rule on Column on Product Page
	 *
	 * @return void
	 */
	public function b2b_stock_management() {
		add_action( 'woocommerce_product_options_stock_status', array( $this, 'b2b_stock_manage_fields_on_simple_product' ) );
		add_action( 'woocommerce_variation_options_inventory', array( $this, 'b2b_stock_manage_fields_on_product_variation' ), 10, 3 );

		add_action( 'save_post', array( $this, 'save_b2b_stock_manage_fields_for_simple_product' ) );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_b2b_stock_manage_fields_for_product_variation' ), 10, 2 );

		add_filter( 'woocommerce_admin_stock_html', array( $this, 'admin_stock_html' ), 10, 2 );

		if ( ! ( is_admin() && ! wp_doing_ajax() ) ) {

			add_filter( 'woocommerce_product_get_stock_status', array( $this, 'b2b_get_stock_status' ), 10, 2 );
			add_filter( 'woocommerce_product_get_stock_quantity', array( $this, 'b2b_get_stock_quantity' ), 10, 2 );
			add_action( 'woocommerce_product_get_backorders', array( $this, 'b2b_get_backorders' ), 10, 2 );

			// Product Variation.
			add_filter( 'woocommerce_product_variation_get_stock_status', array( $this, 'b2b_variation_get_stock_status' ), 10, 2 );
			add_filter( 'woocommerce_product_variation_get_stock_quantity', array( $this, 'b2b_variation_get_stock_quantity' ), 10, 2 );
			add_filter( 'woocommerce_product_variation_get_backorders', array( $this, 'b2b_variation_get_backorders' ), 10, 2 );
		}

		// Remove All woocommerce stock related action and will rewrite this action.
		remove_action( 'woocommerce_payment_complete', 'wc_maybe_reduce_stock_levels' );
		remove_action( 'woocommerce_order_status_completed', 'wc_maybe_reduce_stock_levels' );
		remove_action( 'woocommerce_order_status_processing', 'wc_maybe_reduce_stock_levels' );
		remove_action( 'woocommerce_order_status_on-hold', 'wc_maybe_reduce_stock_levels' );
		remove_action( 'woocommerce_order_status_cancelled', 'wc_maybe_increase_stock_levels' );
		remove_action( 'woocommerce_order_status_pending', 'wc_maybe_increase_stock_levels' );

		// rewrite all woocommerce store related action.
		add_action( 'woocommerce_payment_complete', array( $this, 'maybe_reduce_stock_levels' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_reduce_stock_levels' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_reduce_stock_levels' ) );
		add_action( 'woocommerce_order_status_on-hold', array( $this, 'maybe_reduce_stock_levels' ) );

		add_action( 'woocommerce_order_status_cancelled', array( $this, 'maybe_increase_stock_levels' ) );
		add_action( 'woocommerce_order_status_pending', array( $this, 'maybe_increase_stock_levels' ) );
	}

	/**
	 * All Products Stock Columns : WholesaleX B2B Stock Added.
	 *
	 * @param string $stock_html stock html.
	 * @param object $product Product.
	 * @return string
	 */
	public function admin_stock_html( $stock_html, $product ) {
		$product_id = $product->get_id();

		$separate_b2b_stock_status = get_post_meta( $product_id, 'wholesalex_b2b_separate_stock_status', true );
		$b2b_stock                 = get_post_meta( $product_id, 'wholesalex_b2b_stock', true );
		$b2b_backorders            = get_post_meta( $product_id, 'wholesalex_b2b_backorders', true );
		$stock_status              = get_post_meta( $product_id, 'wholesalex_b2b_stock_status', true );

		if ( $product->is_on_backorder() ) {
			$stock_html = '<mark class="onbackorder">' . __( 'On backorder', 'woocommerce' ) . '</mark>';
		} elseif ( $product->is_in_stock() ) {
			$stock_html = '<mark class="instock">' . __( 'In stock', 'woocommerce' ) . '</mark>';
		} else {
			$stock_html = '<mark class="outofstock">' . __( 'Out of stock', 'woocommerce' ) . '</mark>';
		}

		if ( $product->managing_stock() ) {
			$stock_html .= ' (' . wc_stock_amount( $product->get_stock_quantity() ) . ')';
		}

		$b2b_stock_html = '';

		$backorder_status = $product->managing_stock() && ( 'yes' === $b2b_backorders || 'notify' === $b2b_backorders ) && intval( $b2b_stock ) <= 0;

		if ( 'onbackorder' === $stock_status || $backorder_status ) {
			$b2b_stock_html = '<mark class="onbackorder">' . __( 'On backorder', 'woocommerce' ) . '</mark>';
		} elseif ( 'outofstock' !== $stock_status ) {
			$b2b_stock_html = '<mark class="instock">' . __( 'In stock', 'woocommerce' ) . '</mark>';
		} else {
			$b2b_stock_html = '<mark class="outofstock">' . __( 'Out of stock', 'woocommerce' ) . '</mark>';
		}

		if ( $product->managing_stock() && 'outofstock' !== $stock_status ) {
			if ( 'yes' === $separate_b2b_stock_status ) {
				$b2b_stock_html .= ' (' . wc_stock_amount( $b2b_stock ) . ')';
			} else {
				$b2b_stock_html .= ' (' . wc_stock_amount( $product->get_stock_quantity() ) . ')';
			}
		}

		$output = sprintf( '<span> <strong>B2C:</strong> %s <br/> <strong>B2B:</strong> %s </span>', $stock_html, $b2b_stock_html );

		return $output;
	}

	/**
	 * B2B Stock Manage Fields
	 *
	 * @return void
	 */
	public function b2b_stock_manage_fields_on_simple_product() {
		global $post;
		$product_id = $post->ID;

		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
			$separate_b2b_stock_status = get_post_meta( $product_id, 'wholesalex_b2b_separate_stock_status', true );
			$b2b_stock                 = get_post_meta( $product_id, 'wholesalex_b2b_stock', true );
			$b2b_backorders            = get_post_meta( $product_id, 'wholesalex_b2b_backorders', true );

			?>
			<div class="stock_fields show_if_simple show_if_variable wholesalex_stock_management_fields">
			<?php

			woocommerce_wp_select(
				array(
					'id'            => 'wholesalex_b2b_separate_stock_status',
					'value'         => $separate_b2b_stock_status,
					'label'         => __( 'Separate Stock For WholesaleX B2B User?', 'wholesalex' ),
					'options'       => array(
						'yes' => __( 'Yes', 'wholesalex' ),
						'no'  => __( 'No', 'wholesalex' ),
					),
					'wrapper_class' => '',
					'desc_tip'      => true,
					'description'   => esc_html__( 'By selecting Yes, Separate Stock Will be managed for B2B Users. If Select No, then same stock will be used for all.', 'wholesalex' ),
				)
			);

			woocommerce_wp_text_input(
				array(
					'id'                => 'wholesalex_b2b_stock',
					'value'             => wc_stock_amount( $b2b_stock ?? 1 ),
					'label'             => __( 'WholesaleX B2B Stock Quantity', 'wholesalex' ),
					'desc_tip'          => true,
					'description'       => __( 'Stock quantity. If this is a variable product this value will be used to control stock for all variations, unless you define stock at variation level.', 'wholesalex' ),
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => 'any',
					),
					'data_type'         => 'stock',
				)
			);

			?>
				<input type="hidden" name="_original_wholesalex_b2b_stock" value="<?php echo esc_attr( wc_stock_amount( $b2b_stock ?? 1 ) ); ?>" />
			<?php

			$backorder_args = array(
				'id'          => 'wholesalex_b2b_backorders',
				'value'       => $b2b_backorders,
				'label'       => __( 'Allow WholesaleX B2B Users backorders?', 'wholesalex' ),
				'options'     => wc_get_product_backorder_options(),
				'desc_tip'    => true,
				'description' => __( 'If managing stock, this controls whether or not backorders are allowed. If enabled, stock quantity can go below 0.', 'wholesalex' ),
			);

			/**
			 * Allow 3rd parties to control whether "Allow backorder?" option will use radio buttons or a select.
			 *
			 * @since 7.6.0
			 *
			 * @param bool If false, "Allow backorders?" will be shown as a select. Default: it will use radio buttons.
			 */
			if ( apply_filters( 'woocommerce_product_allow_backorder_use_radio', true ) ) {
				woocommerce_wp_radio( $backorder_args );
			} else {
				woocommerce_wp_select( $backorder_args );
			}

			?>
				</div>
			<?php
		}

		$stock_status_options = wc_get_product_stock_status_options();
		$b2b_stock_status     = get_post_meta( $product_id, 'wholesalex_b2b_stock_status', true );
		$stock_status_count   = count( $stock_status_options );
		$stock_status_args    = array(
			'id'            => 'wholesalex_b2b_stock_status',
			'value'         => $b2b_stock_status,
			'wrapper_class' => 'stock_status_field hide_if_variable hide_if_external hide_if_grouped wholesalex_stock_management_fields',
			'label'         => __( 'WholesaleX B2B Stock status', 'wholesalex' ),
			'options'       => $stock_status_options,
			'desc_tip'      => true,
			'description'   => __( 'Controls whether or not the product is listed as "in stock" or "out of stock" on the frontend for wholesalex b2b users', 'wholesalex' ),
		);

		if ( apply_filters( 'woocommerce_product_stock_status_use_radio', $stock_status_count <= 3 && $stock_status_count >= 1 ) ) {
			woocommerce_wp_radio( $stock_status_args );
		} else {
			woocommerce_wp_select( $stock_status_args );
		}
	}

	/**
	 * Save B2B Stock Manage Fields for Simple Product
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function save_b2b_stock_manage_fields_for_simple_product( $product_id ) {

		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		$nonce = isset( $_POST['meta-box-order-nonce'] ) ? sanitize_key( $_POST['meta-box-order-nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'meta-box-order' ) ) {
			return;
		}

		$product = wc_get_product( $product_id );
		if ( is_a( $product, 'WC_Product' ) || is_a( $product, 'WC_Product_Variation' ) ) {
			$product_id = $product->get_id();
		} else {
			return;
		}

		if ( isset( $_POST['wholesalex_b2b_stock_status'] ) ) {
			$stock_status = sanitize_text_field( wp_unslash( $_POST['wholesalex_b2b_stock_status'] ) );
			update_post_meta( $product_id, 'wholesalex_b2b_stock_status', $stock_status );
		}
		if ( isset( $_POST['wholesalex_b2b_stock'] ) ) {
			$stock_count    = sanitize_text_field( wp_unslash( $_POST['wholesalex_b2b_stock'] ) );
			$original_value = isset( $_POST['_original_wholesalex_b2b_stock'] ) ? sanitize_text_field( wp_unslash( $_POST['_original_wholesalex_b2b_stock'] ) ) : '';
			$current_stock  = get_post_meta( $product_id, 'wholesalex_b2b_stock', true );

			if ( empty( $original_value ) ) {
				$original_value = $current_stock;
			}

			if ( intval( $current_stock ) === intval( $original_value ) ) {
				// Seems .
				update_post_meta( $product_id, 'wholesalex_b2b_stock', $stock_count );
			} else {
				/* translators: %1s Product ID, %2s Stock Count. */
				\WC_Admin_Meta_Boxes::add_error( sprintf( __( 'The stock has not been updated because the value has changed since editing. Product %1$d has %2$d units in stock.', 'wholesalex' ), $product_id, $current_stock ) );
			}
		}

		if ( isset( $_POST['wholesalex_b2b_backorders'] ) ) {
			$backorder_status = sanitize_text_field( wp_unslash( $_POST['wholesalex_b2b_backorders'] ) );
			update_post_meta( $product_id, 'wholesalex_b2b_backorders', $backorder_status );
		}
		if ( isset( $_POST['wholesalex_b2b_separate_stock_status'] ) ) {
			$stock_status = sanitize_text_field( wp_unslash( $_POST['wholesalex_b2b_separate_stock_status'] ) );
			update_post_meta( $product_id, 'wholesalex_b2b_separate_stock_status', $stock_status );
		}
	}

	/**
	 * WholesaleX B2B Stock Management options inventory action.
	 *
	 * @since v.todo
	 *
	 * @param int     $loop           Position in the loop.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Post data.
	 */
	public function b2b_stock_manage_fields_on_product_variation( $loop, $variation_data, $variation ) {
		$product_id                = $variation->ID;
		$separate_b2b_stock_status = get_post_meta( $product_id, 'wholesalex_b2b_variable_separate_stock_status', true );
		$b2b_stock                 = get_post_meta( $product_id, 'wholesalex_b2b_variable_stock', true );
		$b2b_backorders            = get_post_meta( $product_id, 'wholesalex_b2b_variable_backorders', true );

		woocommerce_wp_select(
			array(
				'id'            => "wholesalex_b2b_separate_stock_status_{$product_id}",
				'value'         => $separate_b2b_stock_status,
				'label'         => __( 'Separate Stock For WholesaleX B2B User?', 'wholesalex' ),
				'options'       => array(
					'yes' => __( 'Yes', 'wholesalex' ),
					'no'  => __( 'No', 'wholesalex' ),
				),
				'wrapper_class' => 'form-row form-row-first',
				'desc_tip'      => true,
				'description'   => esc_html__( 'By selecting Yes, Separate Stock Will be managed for B2B Users. If Select No, then same stock will be used for all.', 'wholesalex' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => "wholesalex_b2b_variable_stock_{$product_id}",
				'name'              => "wholesalex_b2b_variable_stock_{$product_id}",
				'value'             => wc_stock_amount( $b2b_stock ),
				'label'             => __( 'WholesaleX B2B Users Stock quantity', 'wholesalex' ),
				'desc_tip'          => true,
				'description'       => __( "Enter a number to set stock quantity at the variation level. Use a variation's 'Manage stock?' check box above to enable/disable stock management at the variation level.", 'wholesalex' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => 'any',
				),
				'data_type'         => 'stock',
				'wrapper_class'     => 'form-row form-row-first',
			)
		);

		?>
			<input type="hidden" name="<?php echo esc_attr( 'variable_original_wholesalex_b2b_stock_' . $product_id ); ?>" value="<?php echo esc_attr( wc_stock_amount( $b2b_stock ) ); ?>" />
		<?php

		woocommerce_wp_select(
			array(
				'id'            => "wholesalex_b2b_variable_backorders_{$product_id}",
				'name'          => "wholesalex_b2b_variable_backorders_{$product_id}",
				'value'         => $b2b_backorders,
				'label'         => __( 'Allow WholesaleX B2B Users backorders?', 'wholesalex' ),
				'options'       => wc_get_product_backorder_options(),
				'desc_tip'      => true,
				'description'   => __( 'If managing stock, this controls whether or not backorders are allowed. If enabled, stock quantity can go below 0.', 'wholesalex' ),
				'wrapper_class' => 'form-row form-row-last',
			)
		);
	}

	/**
	 * Save Variation Stock Manage Fields
	 *
	 * @param int $variation_id Variation Id.
	 * @return void
	 */
	public function save_b2b_stock_manage_fields_for_product_variation( $variation_id ) {

		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		$nonce = isset( $_POST['security'] ) ? sanitize_key( $_POST['security'] ) : '';
		if ( empty( $nonce ) ) {
			$nonce = isset( $_POST['meta-box-order-nonce'] ) ? sanitize_key( $_POST['meta-box-order-nonce'] ) : '';
		}
		$is_nonce_verify = false;
		if ( wp_verify_nonce( $nonce, 'save-variations' ) || wp_verify_nonce( $nonce, 'meta-box-order' ) ) {
			$is_nonce_verify = true;
		}

		if ( ! $is_nonce_verify ) {
			return;
		}
		if ( isset( $_POST[ 'wholesalex_b2b_variable_stock_' . $variation_id ] ) ) {
			$stock_count    = sanitize_text_field( wp_unslash( $_POST[ 'wholesalex_b2b_variable_stock_' . $variation_id ] ) );
			$original_value = isset( $_POST[ 'variable_original_wholesalex_b2b_stock_' . $variation_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'variable_original_wholesalex_b2b_stock_' . $variation_id ] ) ) : '';
			$current_stock  = get_post_meta( $variation_id, 'wholesalex_b2b_variable_stock', true );

			if ( empty( $original_value ) ) {
				$original_value = $current_stock;
			}

			if ( intval( $current_stock ) === intval( $original_value ) ) {
				// Seems good.
				update_post_meta( $variation_id, 'wholesalex_b2b_variable_stock', $stock_count );
			} else {
				/* translators: %1s Product ID, %2s Stock Count. */
				\WC_Admin_Meta_Boxes::add_error( sprintf( __( 'The stock has not been updated because the value has changed since editing. Product %1$d has %2$d units in stock.', 'wholesalex' ), $variation_id, $current_stock ) );
			}
		}

		if ( isset( $_POST[ 'wholesalex_b2b_variable_backorders_' . $variation_id ] ) ) {
			$backorder_status = sanitize_text_field( wp_unslash( $_POST[ 'wholesalex_b2b_variable_backorders_' . $variation_id ] ) );
			update_post_meta( $variation_id, 'wholesalex_b2b_variable_backorders', $backorder_status );
		}

		if ( isset( $_POST[ 'wholesalex_b2b_separate_stock_status_' . $variation_id ] ) ) {
			$backorder_status = sanitize_text_field( wp_unslash( $_POST[ 'wholesalex_b2b_separate_stock_status_' . $variation_id ] ) );
			update_post_meta( $variation_id, 'wholesalex_b2b_variable_separate_stock_status', $backorder_status );
		}
	}

	/**
	 * Set B2B Stock Status
	 *
	 * @param string $stock_status Default Stock Status.
	 * @param Object $product Product.
	 * @return string stock status
	 */
	public function b2b_get_stock_status( $stock_status, $product ) {

		if ( wholesalex()->is_active_b2b_user() ) {

			if ( $product->get_manage_stock() ) {
				// Manage Stock Enabled.

				if ( ! intval( $product->get_stock_quantity() ) ) {
					$stock_status = 'outofstock';
				}

				if ( 'no' !== $product->get_backorders() ) {
					$stock_status = 'instock'; // For allowing backorder.
				}
			} else {
				// Manage Stock Disabled.
				$stock_status = get_post_meta( $product->get_id(), 'wholesalex_b2b_stock_status', true );
			}

			if ( empty( $stock_status ) ) {
				$stock_status = 'instock';
			}
		}

		return $stock_status;
	}

	/**
	 * Set B2B Stock Quantity
	 *
	 * @param int|string $quantity Stock Quantity.
	 * @param Object     $product Product.
	 * @return int stock quantity
	 */
	public function b2b_get_stock_quantity( $quantity, $product ) {

		if ( wholesalex()->is_active_b2b_user() ) {
			$product_id                = $product->get_id();
			$separate_b2b_stock_status = get_post_meta( $product_id, 'wholesalex_b2b_separate_stock_status', true );
			if ( 'yes' == $separate_b2b_stock_status ) {
				$quantity = get_post_meta( $product_id, 'wholesalex_b2b_stock', true );
			}
		}

		return $quantity;
	}

	/**
	 * Set B2B Backorders
	 *
	 * @param string $status Backorder Status
	 * @param Object $product Product.
	 * @return string backorder status
	 */
	public function b2b_get_backorders( $status, $product ) {

		if ( wholesalex()->is_active_b2b_user() ) {
			$product_id = $product->get_id();
			$status     = get_post_meta( $product_id, 'wholesalex_b2b_backorders', true );
		}
		return $status;
	}

	/**
	 * Get Variation Product B2B Stock Status
	 *
	 * @param string $stock_status Stock Status.
	 * @param Object $product Product.
	 * @return string stock status.
	 */
	public function b2b_variation_get_stock_status( $stock_status, $product ) {

		if ( wholesalex()->is_active_b2b_user() ) {

			if ( $product->get_manage_stock() ) {
				// Manage Stock Enabled.

				if ( ! intval( $product->get_stock_quantity() ) ) {
					$stock_status = 'outofstock';
				}

				if ( 'no' !== $product->get_backorders() ) {
					$stock_status = 'instock'; // For allowing backorder.
				}
			} else {
				// Manage Stock Disabled.
				$stock_status = get_post_meta( $product->get_id(), 'wholesalex_b2b_stock_status', true );
			}

			if ( empty( $stock_status ) ) {
				$stock_status = 'instock';
			}
		}
		return $stock_status;
	}

	/**
	 * Get B2B Variation Stock quantity
	 *
	 * @param int|string $quantity stock quantity.
	 * @param Object     $product Product.
	 * @return int stock quantity
	 */
	public function b2b_variation_get_stock_quantity( $quantity, $product ) {
		if ( wholesalex()->is_active_b2b_user() ) {
			$product_id   = $product->get_id();
			$manage_stock = $product->get_manage_stock();

			if ( $manage_stock ) { // The variation manages its own stock.
				$separate_b2b_stock_status = get_post_meta( $product_id, 'wholesalex_b2b_variable_separate_stock_status', true );
				if ( 'yes' === $separate_b2b_stock_status ) {
					$quantity = get_post_meta( $product_id, 'wholesalex_b2b_variable_stock', true );
				}
			} elseif ( 'parent' === $manage_stock ) { // The variation inherits stock from the parent product.
				$parent_id                 = $product->get_parent_id();
				$separate_b2b_stock_status = get_post_meta( $parent_id, 'wholesalex_b2b_separate_stock_status', true );
				if ( 'yes' === $separate_b2b_stock_status ) {
					$quantity = get_post_meta( $parent_id, 'wholesalex_b2b_stock', true );
				}
			}
		}
		return $quantity;
	}

	/**
	 * Set Variation B2B Backorders
	 * Inspired From WooCommerce Core
	 *
	 * @param string $status Backorder Status.
	 * @param Object $product Product.
	 * @return string backorder status
	 */
	public function b2b_variation_get_backorders( $status, $product ) {

		if ( wholesalex()->is_active_b2b_user() ) {
			$product_id = $product->get_id();
			$status     = get_post_meta( $product_id, 'wholesalex_b2b_variable_backorders', true );
		}
		return $status;
	}

	/**
	 * Reduce stock levels for items within an order, if stock has not already been reduced for the items.
	 * Inspired From WooCommerce Core
	 *
	 * @param int|WC_Order $order_id Order ID or order instance.
	 */
	public function reduce_b2b_stock( $order_id ) {
		if ( is_a( $order_id, 'WC_Order' ) ) {
			$order    = $order_id;
			$order_id = $order->get_id();
		} else {
			$order = wc_get_order( $order_id );
		}
		// We need an order, and a store with stock management to continue.
		if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) || ! apply_filters( 'woocommerce_can_reduce_order_stock', true, $order ) ) {
			return;
		}

		$changes     = array();
		$customer_id = $order->get_customer_id();

		// Loop over all items.
		foreach ( $order->get_items() as $item ) {
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			// Only reduce stock once for each item.
			$product            = $item->get_product();
			$item_stock_reduced = $item->get_meta( '_reduced_stock', true );

			if ( $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
				continue;
			}

			/**
			 * Filter order item quantity.
			 *
			 * @param int|float             $quantity Quantity.
			 * @param WC_Order              $order    Order data.
			 * @param WC_Order_Item_Product $item Order item data.
			 */
			$qty       = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
			$item_name = $product->get_formatted_name();
			$new_stock = $this->b2b_update_product_stock( $product, $qty, 'decrease', false, $customer_id );

			if ( is_wp_error( $new_stock ) ) {
				/* translators: %s item name. */
				$order->add_order_note( sprintf( __( 'Unable to reduce stock for item %s.', 'woocommerce' ), $item_name ) );
				continue;
			}

			$item->add_meta_data( '_reduced_stock', $qty, true );
			$item->save();

			$change    = array(
				'product' => $product,
				'from'    => $new_stock + $qty,
				'to'      => $new_stock,
			);
			$changes[] = $change;

			/**
			 * Fires when stock reduced to a specific line item
			 *
			 * @param WC_Order_Item_Product $item Order item data.
			 * @param array $change  Change Details.
			 * @param WC_Order $order  Order data.
			 * @since 7.6.0
			 */
			do_action( 'woocommerce_reduce_order_item_stock', $item, $change, $order );
		}

		$this->trigger_stock_change_notifications( $order, $changes, $customer_id );

		do_action( 'woocommerce_reduce_order_stock', $order );
	}

	/**
	 * After stock change events, triggers emails and adds order notes.
	 * Inspired From WooCommerce Core
	 *
	 * @param WC_Order $order order object.
	 * @param array    $changes Array of changes.
	 */
	public function trigger_stock_change_notifications( $order, $changes ) {
		if ( empty( $changes ) ) {
			return;
		}

		$order_notes     = array();
		$no_stock_amount = absint( get_option( 'woocommerce_notify_no_stock_amount', 0 ) );

		foreach ( $changes as $change ) {
			$order_notes[]    = $change['product']->get_formatted_name() . ' ' . $change['from'] . '&rarr;' . $change['to'];
			$low_stock_amount = absint( wc_get_low_stock_amount( wc_get_product( $change['product']->get_id() ) ) );
			if ( $change['to'] <= $no_stock_amount ) {
				do_action( 'woocommerce_no_stock', wc_get_product( $change['product']->get_id() ) );
			} elseif ( $change['to'] <= $low_stock_amount ) {
				do_action( 'woocommerce_low_stock', wc_get_product( $change['product']->get_id() ) );
			}

			if ( $change['to'] < 0 ) {
				do_action(
					'woocommerce_product_on_backorder',
					array(
						'product'  => wc_get_product( $change['product']->get_id() ),
						'order_id' => $order->get_id(),
						'quantity' => abs( $change['from'] - $change['to'] ),
					)
				);
			}
		}

		$order->add_order_note( __( 'Stock levels reduced:', 'woocommerce' ) . ' ' . implode( ', ', $order_notes ) . ' ' . __( 'Wholesale Customer', 'wholesalex' ) );
	}

	/**
	 * Update a product's stock amount directly.
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
	 * Ignores manage stock setting on the product and sets quantities directly in the db: post meta and lookup tables.
	 * Uses locking to update the quantity. If the lock is not acquired, change is lost.
	 * Inspired From WooCommerce Core
	 *
	 * @param  int            $product_id_with_stock Product ID.
	 * @param  int|float|null $stock_quantity Stock quantity.
	 * @param  string         $operation Set, increase and decrease.
	 * @return int|float New stock level.
	 */
	public function update_product_stock( $product_id_with_stock, $stock_quantity = null, $operation = 'set' ) {
		global $wpdb;

		$stock_meta_key = '_stock';
		$product        = wc_get_product( $product_id_with_stock );

		if ( $product->is_type( 'simple' ) || $product->is_type( 'variable' ) ) {
			// simple product.
			$separate_b2b_stock_status = get_post_meta( $product_id_with_stock, 'wholesalex_b2b_separate_stock_status', true );
			if ( 'yes' == $separate_b2b_stock_status ) {
				$stock_meta_key = 'wholesalex_b2b_stock';
			}
		} elseif ( $product->is_type( 'variation' ) ) {
			// variable product.
			$separate_b2b_stock_status = get_post_meta( $product_id_with_stock, 'wholesalex_b2b_variable_separate_stock_status', true );
			if ( 'yes' === $separate_b2b_stock_status ) {
				$stock_meta_key = 'wholesalex_b2b_variable_stock';
			}
		}

		// Ensures a row exists to update.
		add_post_meta( $product_id_with_stock, $stock_meta_key, 0, true );

		if ( 'set' === $operation ) {
			$new_stock = wc_stock_amount( $stock_quantity );

			// Generate SQL.
			$sql = $wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = %f WHERE post_id = %d AND meta_key = %s;",
				$new_stock,
				$product_id_with_stock,
				$stock_meta_key
			);
		} else {
			$current_stock = wc_stock_amount(
				$wpdb->get_var(//phpcs:ignore
					$wpdb->prepare(
						"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s;",
						$product_id_with_stock,
						$stock_meta_key
					)
				)
			);

			// Calculate new value for filter below. Set multiplier to subtract or add the meta_value.
			switch ( $operation ) {
				case 'increase':
					$new_stock  = $current_stock + wc_stock_amount( $stock_quantity );
					$multiplier = 1;
					break;
				default:
					$new_stock  = $current_stock - wc_stock_amount( $stock_quantity );
					$multiplier = -1;
					break;
			}

			// Generate SQL.
			$sql = $wpdb->prepare(
				"UPDATE {$wpdb->postmeta} SET meta_value = meta_value + (%f * %d) WHERE post_id = %d AND meta_key = %s;",
				wc_stock_amount( $stock_quantity ),
				$multiplier,
				$product_id_with_stock,
				$stock_meta_key
			);
		}

		$sql = apply_filters( 'woocommerce_update_product_stock_query', $sql, $product_id_with_stock, $new_stock, $operation );

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		// Cache delete is required (not only) to set correct data for lookup table (which reads from cache).
		// Sometimes I wonder if it shouldn't be part of update_lookup_table.
		wp_cache_delete( $product_id_with_stock, 'post_meta' );

		$datastore = \WC_Data_Store::load( 'product' );
		$datastore->update_lookup_table( $product_id_with_stock, 'wc_product_meta_lookup' );

		/**
		 * Fire an action for this direct update so it can be detected by other code.
		 *
		 * @param int $product_id_with_stock Product ID that was updated directly.
		 */
		do_action( 'woocommerce_updated_product_stock', $product_id_with_stock );

		return $new_stock;
	}

	/**
	 * Update a product's stock amount.
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
	 * Inspired From WooCommerce Core
	 *
	 * @param  int|WC_Product $product        Product ID or product instance.
	 * @param  int|null       $stock_quantity Stock quantity.
	 * @param  string         $operation      Type of operation, allows 'set', 'increase' and 'decrease'.
	 * @param  bool           $updating       If true, the product object won't be saved here as it will be updated later.
	 * @return bool|int|null
	 */
	public function b2b_update_product_stock( $product, $stock_quantity = null, $operation = 'set', $updating = false ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product ) {
			return false;
		}

		if ( ! is_null( $stock_quantity ) && $product->managing_stock() ) {
			// Some products (variations) can have their stock managed by their parent. Get the correct object to be updated here.
			$product_id_with_stock = $product->get_stock_managed_by_id();
			$product_with_stock    = $product_id_with_stock !== $product->get_id() ? wc_get_product( $product_id_with_stock ) : $product;
			$data_store            = \WC_Data_Store::load( 'product' );

			// Fire actions to let 3rd parties know the stock is about to be changed.
			if ( $product_with_stock->is_type( 'variation' ) ) {
				do_action( 'woocommerce_variation_before_set_stock', $product_with_stock );
			} else {
				do_action( 'woocommerce_product_before_set_stock', $product_with_stock );
			}

			// Update the database.
			$new_stock = $this->update_product_stock( $product_id_with_stock, $stock_quantity, $operation );

			// Update the product object.
			$data_store->read_stock_quantity( $product_with_stock, $new_stock );

			// If this is not being called during an update routine, save the product so stock status etc is in sync, and caches are cleared.
			if ( ! $updating ) {
				$product_with_stock->save();
			}

			// Fire actions to let 3rd parties know the stock changed.
			if ( $product_with_stock->is_type( 'variation' ) ) {
				do_action( 'woocommerce_variation_set_stock', $product_with_stock );
			} else {
				do_action( 'woocommerce_product_set_stock', $product_with_stock );
			}

			return $product_with_stock->get_stock_quantity();
		}
		return $product->get_stock_quantity();
	}

	/**
	 * When a payment is complete, we can reduce stock levels for items within an order.
	 * If customer is B2B Then we reduce b2b stock level, otherwise woocommerce will handle it.
	 * Inspired From WooCommerce Core
	 *
	 * @param int $order_id Order ID.
	 */
	public function maybe_reduce_stock_levels( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$customer_id = $order->get_customer_id();

		if ( ! wholesalex()->is_active_b2b_user( $customer_id ) ) {
			wc_maybe_reduce_stock_levels( $order_id );
			return;
		}

		$stock_reduced  = $order->get_data_store()->get_stock_reduced( $order_id );
		$trigger_reduce = apply_filters( 'woocommerce_payment_complete_reduce_order_stock', ! $stock_reduced, $order_id );

		// Only continue if we're reducing stock.
		if ( ! $trigger_reduce ) {
			return;
		}

		$this->reduce_b2b_stock( $order );

		// Ensure stock is marked as "reduced" in case payment complete or other stock actions are called.
		$order->get_data_store()->set_stock_reduced( $order_id, true );
	}

	/**
	 * When a payment is cancelled, restore stock.
	 * Inspired From WooCommerce Core
	 *
	 * @param int $order_id Order ID.
	 */
	public function maybe_increase_stock_levels( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$customer_id = $order->get_customer_id();

		if ( ! wholesalex()->is_active_b2b_user( $customer_id ) ) {
			wc_maybe_increase_stock_levels( $order_id );
			return;
		}

		$stock_reduced    = $order->get_data_store()->get_stock_reduced( $order_id );
		$trigger_increase = (bool) $stock_reduced;

		// Only continue if we're increasing stock.
		if ( ! $trigger_increase ) {
			return;
		}

		$this->increase_b2b_stock( $order );

		// Ensure stock is not marked as "reduced" anymore.
		$order->get_data_store()->set_stock_reduced( $order_id, false );
	}

	/**
	 * Increase stock levels for items within an order.
	 * Inspired From WooCommerce Core
	 *
	 * @param int|WC_Order $order_id Order ID or order instance.
	 */
	public function increase_b2b_stock( $order_id ) {
		if ( is_a( $order_id, 'WC_Order' ) ) {
			$order    = $order_id;
			$order_id = $order->get_id();
		} else {
			$order = wc_get_order( $order_id );
		}

		// We need an order, and a store with stock management to continue.
		if ( ! $order || 'yes' !== get_option( 'woocommerce_manage_stock' ) || ! apply_filters( 'woocommerce_can_restore_order_stock', true, $order ) ) {
			return;
		}

		$changes = array();

		// Loop over all items.
		foreach ( $order->get_items() as $item ) {
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			// Only increase stock once for each item.
			$product            = $item->get_product();
			$item_stock_reduced = $item->get_meta( '_reduced_stock', true );

			if ( ! $item_stock_reduced || ! $product || ! $product->managing_stock() ) {
				continue;
			}

			$item_name = $product->get_formatted_name();
			$new_stock = $this->b2b_update_product_stock( $product, $item_stock_reduced, 'increase' );

			if ( is_wp_error( $new_stock ) ) {
				/* translators: %s item name. */
				$order->add_order_note( sprintf( __( 'Unable to restore stock for item %s.', 'woocommerce' ), $item_name ) );
				continue;
			}

			$item->delete_meta_data( '_reduced_stock' );
			$item->save();

			$changes[] = $item_name . ' ' . ( $new_stock - $item_stock_reduced ) . '&rarr;' . $new_stock;
		}

		if ( $changes ) {
			$order->add_order_note( __( 'Stock levels increased:', 'woocommerce' ) . ' ' . implode( ', ', $changes ) . ' ' . __( 'Wholesale Customer', 'wholesalex' ) );
		}

		do_action( 'woocommerce_restore_order_stock', $order );
	}



	/**
	 * Process Import
	 *
	 * @param object $product Product Object.
	 * @param array  $data Data.
	 * @return void
	 * @since 1.1.5
	 */
	public function process_import( $product, $data ) {

		$product_id = $product->get_id();
		$roles      = wholesalex()->get_roles( 'b2b_roles_option' );

		foreach ( $roles as $role ) {
			$base_price_column_id = $role['value'] . '_base_price';
			$sale_price_column_id = $role['value'] . '_sale_price';
			if ( isset( $data[ $base_price_column_id ] ) && ! empty( $data[ $base_price_column_id ] ) ) {
				update_post_meta( $product_id, $base_price_column_id, $data[ $base_price_column_id ] );
			}
			if ( isset( $data[ $sale_price_column_id ] ) && ! empty( $data[ $sale_price_column_id ] ) ) {
				update_post_meta( $product_id, $sale_price_column_id, $data[ $sale_price_column_id ] );
			}
		}
	}

	/**
	 * Save Wholesalex Category
	 *
	 * @since 1.0.0
	 */
	public function get_product_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/product_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'product_action_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * WholesaleX Tab in Single Product Edit Page
	 *
	 * @param array $tabs Single Product Page Tabs.
	 * @return array Updated Tabs.
	 */
	public function product_custom_tab( $tabs ) {
		$tabs['wholesalex'] = array(
			'label'    => wholesalex()->get_plugin_name(),
			'priority' => 15,
			'target'   => 'wsx_tab_data',
			'class'    => array( 'hide_if_grouped' ),
		);

		return $tabs;
	}

	/**
	 * WholesaleX Custom Tab Data.
	 *
	 * @return void
	 */
	public function wsx_tab_data() {
		global $post;
		/**
		 * Enqueue Script
		 *
		 * @since 1.1.0 Enqueue Script (Reconfigure Build File)
		 */
		wp_enqueue_script( 'wholesalex_product' );
		wp_localize_script(
			'wholesalex_product',
			'wholesalex_product',
			array(
				'roles' => wholesalex()->get_roles( 'b2b_roles_option' ),
			)
		);
		$settings = wholesalex()->get_single_product_setting();

		wp_localize_script(
			'wholesalex_components',
			'wholesalex_product_tab',
			array(
				'fields'   => self::get_product_settings(),
				'settings' => isset( $settings[ $post->ID ] ) ? $settings[ $post->ID ] : array(),
			),
		);
		?>
		<div class="panel woocommerce_options_panel" id="wsx_tab_data"></div>
		<?php
	}

	/**
	 * Save Product Setting Data
	 *
	 * @param mixed $post_id Product ID.
	 */
	public function product_settings_data_save( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST['meta-box-order-nonce'] ) ? sanitize_key( $_POST['meta-box-order-nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'meta-box-order' ) ) {
			return;
		}

		if ( isset( $_POST['wholesalex_product_settings'] ) ) {
			$product_settings = wholesalex()->sanitize( json_decode( wp_unslash( $_POST['wholesalex_product_settings'] ), true ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wholesalex()->save_single_product_settings( $post_id, $product_settings );
		}
	}



	/**
	 * Get Category actions
	 *
	 * @param object $server Server.
	 * @return void
	 */
	public function product_action_callback( $server ) {
		$post = $server->get_params();
		if ( ! ( isset( $post['nonce'] ) && wp_verify_nonce( sanitize_key( $post['nonce'] ), 'wholesalex-registration' ) ) ) {
			return;
		}
		$type    = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';
		$post_id = isset( $post['postId'] ) ? sanitize_text_field( $post['postId'] ) : '';
		$is_tab  = isset( $post['isTab'] ) ? sanitize_text_field( $post['isTab'] ) : '';
		if ( 'get' === $type ) {

			if ( $is_tab ) {
				wp_send_json_success(
					array(
						'default' => self::get_product_settings(),
						'value'   => wholesalex()->get_single_product_setting( $post_id ),
					),
				);
			}

			wp_send_json_success(
				array(
					'default' => self::get_product_fields(),
					'value'   => wholesalex()->get_single_product_discount( $post_id ),
				)
			);
		}
	}

	/**
	 * WholesaleX Single Product Settings
	 */
	public function wholesalex_single_product_settings() {

		$post_id   = get_the_ID();
		$discounts = array();
		if ( $post_id ) {
			$product = wc_get_product( $post_id );
			if ( $product ) {
				$is_variable = 'variable' === $product->get_type();
				if ( $is_variable ) {
					if ( $product->has_child() ) {
						$childrens = $product->get_children();
						foreach ( $childrens as $key => $child_id ) {
							$discounts[ $child_id ] = wholesalex()->get_single_product_discount( $child_id );
						}
					}
				} else {
					$discounts[ $post_id ] = wholesalex()->get_single_product_discount( $post_id );
				}
			}
		}
		wp_localize_script(
			'wholesalex_components',
			'wholesalex_single_product',
			array(
				'fields'    => self::get_product_fields(),
				'discounts' => $discounts,
			),
		);
		?>
		<div class="wsx-single-product-settings-wrapper"></div>
		<?php
	}
	/**
	 * Filters out invalid tier pricing data from WholesaleX product meta.
	 *
	 * This function iterates through the provided product data and removes any
	 * tier pricing entries where the `_discount_type`, `_discount_amount`, or
	 * `_min_quantity` fields are empty. It ensures that only valid tier pricing
	 * data is retained.
	 *
	 * @param array $products_data Array containing product pricing data for different roles.
	 *
	 * @return array Filtered $products_data with invalid tiers removed.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function filter_tiers( $products_data ) {
		foreach ( $products_data as $role => &$role_data ) {
			if ( isset( $role_data['tiers'] ) && is_array( $role_data['tiers'] ) ) {
				$role_data['tiers'] = array_filter(
					$role_data['tiers'],
					function ( $tier ) {
						return ! empty( $tier['_discount_type'] ) && ! empty( $tier['_discount_amount'] ) && ! empty( $tier['_min_quantity'] );
					}
				);
			}
		}
		return $products_data;
	}

	/**
	 * Save WholesaleX Product Meta
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
	public function wholesalex_product_meta_save( $post_id ) {

		$nonce = isset( $_POST['security'] ) ? sanitize_key( $_POST['security'] ) : '';
		if ( empty( $nonce ) ) {
			$nonce = isset( $_POST['meta-box-order-nonce'] ) ? sanitize_key( $_POST['meta-box-order-nonce'] ) : '';
		}
		$is_nonce_verify = false;
		if ( wp_verify_nonce( $nonce, 'save-variations' ) || wp_verify_nonce( $nonce, 'meta-box-order' ) ) {
			$is_nonce_verify = true;
		}

		if ( $is_nonce_verify && isset( $_POST[ 'wholesalex_single_product_tiers_' . $post_id . '_simple' ] ) ) {
			$product_discounts = wholesalex()->sanitize( json_decode( wp_unslash( $_POST[ 'wholesalex_single_product_tiers_' . $post_id . '_simple' ] ), true ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// filter the tier value which is empty.
			$filtered_products_data = $this->filter_tiers( $product_discounts );

			// if the sale price is greater than base price, make the sale price empty.
			foreach ( $filtered_products_data as &$role ) {
				$sale_price = $role['wholesalex_sale_price'];
				$base_price = $role['wholesalex_base_price'];

				if ( floatval( $sale_price ) > floatval( $base_price ) ) {
					$role['wholesalex_sale_price'] = '';
				}
			}

			wholesalex()->save_single_product_discount( $post_id, $filtered_products_data );
		}

		$product      = wc_get_product( $post_id );
		$product_type = $product ? $product->get_type() : 'Unknown';
		if ( 'yith_bundle' === $product_type && $is_nonce_verify && isset( $_POST[ 'wholesalex_single_product_tiers_' . $post_id . '_yith' ] ) ) {
			$product_discounts = wholesalex()->sanitize( json_decode( wp_unslash( $_POST[ 'wholesalex_single_product_tiers_' . $post_id . '_yith' ] ), true ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			// filter the tier value which is empty.
			$filtered_products_data = $this->filter_tiers( $product_discounts );
			wholesalex()->save_single_product_discount( $post_id, $filtered_products_data );
		}
	}

	/**
	 * Control Single Product Visibility
	 *
	 * @param WP_Query $q Query Object.
	 * @since 1.0.0
	 * @since 1.0.3 Added post type checking.
	 * @since 1.2.4 Add Filter wholesalex_is_admin_dashboard
	 */
	public function control_single_product_visibility( $q ) {
		$is_admin_dashboard = apply_filters( 'wholesalex_is_admin_dashboard', is_admin() );
		if ( $is_admin_dashboard ) {
			return $q;
		}
		$post_type = $q->get( 'post_type' );

		$product_cat = $q->get( 'product_cat' );

		if ( 'product' === $post_type || ( '' !== $product_cat && ! $is_admin_dashboard ) ) {
			$__role = wholesalex()->get_current_user_role();
			if ( 'wholesalex_guest' === $__role ) {
				$__hide_for_guest_global = apply_filters( 'wholesalex_hide_all_products_for_guest', wholesalex()->get_setting( '_settings_hide_all_products_from_guest' ) );
				if ( 'yes' === $__hide_for_guest_global ) {
					$q->set( 'post__in', (array) array( '9999999' ) );
				}
			}
			if ( 'wholesalex_b2c_users' === $__role ) {
				$__hide_for_b2c_global = apply_filters( 'wholesalex_hide_all_products_for_b2c', wholesalex()->get_setting( '_settings_hide_products_from_b2c' ) );
				if ( 'yes' === $__hide_for_b2c_global ) {
					$q->set( 'post__in', (array) array( '9999999' ) );
				}
			}
			$existing_ids = isset( $q->query['post__not_in'] ) ? (array) $q->query['post__not_in'] : array();
			$hidden_ids   = (array) wholesalex()->hidden_product_ids();
			$q->set( 'post__not_in', array_merge( $existing_ids, $hidden_ids ) ); // Exclude "$q->query['post__not_in']" WowStore Product with WholesaleX.

		}
		return $q;
	}

	/**
	 * WholesaleX Redirect From Hidden Products
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function redirect_from_hidden_products() {
		if ( is_product() ) {
			$__role      = wholesalex()->get_current_user_role();
			$__is_hidden = false;
			if ( 'wholesalex_guest' === $__role ) {
				$__hide_for_guest_global = apply_filters( 'wholesalex_hide_all_products_for_guest', wholesalex()->get_setting( '_settings_hide_all_products_from_guest' ) );
				if ( 'yes' === $__hide_for_guest_global ) {
					$__is_hidden = true;
				}
			}
			if ( 'wholesalex_b2c_users' === $__role ) {
				$__hide_for_b2c_global = apply_filters( 'wholesalex_hide_all_products_for_b2c', wholesalex()->get_setting( '_settings_hide_products_from_b2c' ) );
				if ( 'yes' === $__hide_for_b2c_global ) {
					$__is_hidden = true;
				}
			}
			$__id = get_the_ID();
			if ( in_array( $__id, wholesalex()->hidden_product_ids(), true ) || $__is_hidden ) {
				/* translators: %s: Product Name */
				wc_add_notice( sprintf( __( 'Sorry, you are not allowed to see %s product.', 'wholesalex' ), get_the_title( get_the_ID() ) ), 'notice' );
				$previous_url = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
				$redirect_url = ! empty( $previous_url ) ? $previous_url : home_url();
				wp_safe_redirect( $redirect_url );
				exit();
			}
		}
	}

	/**
	 * Prevent Checkout If any cart has any wholesalex hidden product
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Allow Hidden to checkout hook added
	 */
	public function prevent_checkout_hidden_products() {
		if ( ! ( isset( WC()->cart ) && ! empty( WC()->cart ) ) ) {
			return;
		}
		$allow_hidden_product_to_checkout = apply_filters( 'wholesalex_allow_hidden_product_to_checkout', false );
		if ( $allow_hidden_product_to_checkout ) {
			return;
		}
		$__role      = wholesalex()->get_current_user_role();
		$__is_hidden = false;
		if ( 'wholesalex_guest' === $__role ) {
			$__hide_for_guest_global = apply_filters( 'wholesalex_hide_all_products_for_guest', wholesalex()->get_setting( '_settings_hide_all_products_from_guest' ) );
			if ( 'yes' === $__hide_for_guest_global ) {
				$__is_hidden = true;
			}
		}
		if ( 'wholesalex_b2c_users' === $__role ) {
			$__hide_for_b2c_global = apply_filters( 'wholesalex_hide_all_products_for_b2c', wholesalex()->get_setting( '_settings_hide_products_from_b2c' ) );
			if ( 'yes' === $__hide_for_b2c_global ) {
				$__is_hidden = true;
			}
		}

		$__hide_regular_price = wholesalex()->get_setting( '_settings_hide_retail_price' ) ?? '';

		$__hide_wholesale_price = wholesalex()->get_setting( '_settings_hide_wholesalex_price' ) ?? '';

		if ( ! is_admin() ) {
			if ( 'yes' === (string) $__hide_wholesale_price && 'yes' === (string) $__hide_regular_price ) {
				$__is_hidden = true;
			}
		}
		foreach ( WC()->cart->get_cart() as $key => $cart_item ) {

			$__product_id = '';

			if ( $cart_item['variation_id'] ) {
				$__product    = wc_get_product( $cart_item['variation_id'] );
				$__product_id = $__product->get_parent_id();
			} elseif ( $cart_item['product_id'] ) {
				$__product_id = $cart_item['product_id'];
			}
			if ( in_array( $__product_id, wholesalex()->hidden_product_ids(), true ) || $__is_hidden ) {
				// Remove Hidden Product From Cart.
				WC()->cart->remove_cart_item( $key );
				/* translators: %s: Product Name */
				wc_add_notice( sprintf( __( 'Sorry, you are not allowed to checkout %s product.', 'wholesalex' ), get_the_title( $__product_id ) ), 'error' );
			}
		}
	}

	/**
	 * Get Product Settings Field
	 *
	 * @return array Product Settings Fields.
	 */
	public static function get_product_settings() {
		$__roles_options = wholesalex()->get_roles( 'b2b_roles_option' );
		$__users_options = wholesalex()->get_users()['user_options'];

		// Key changed _settings_tier_layout to _settings_tier_layout_single_product.
		// Reset Default Data.
		return apply_filters(
			'wholesalex_single_product_settings_field',
			array(
				'_product_settings_tab' => array(
					'type' => 'custom_tab',
					'attr' => array(
						'_settings_tire_price_product_layout' => array(
							'type'    => 'radio',
							'label'   => __( 'Tier Price Table Style', 'wholesalex' ),
							'options' => array(
								'table_style'   => __( 'Table Style', 'wholesalex' ),
								'classic_style' => __( 'Classic Style', 'wholesalex' ),
							),
							'help'    => __( 'Set Individual Product Tier Price Table Style', 'wholesalex' ),
							'default' => 'table_style',
						),

						'_settings_vertical_product_style' => array(
							'type'    => 'slider',
							'label'   => __( 'Tier Price Table Vertical Style', 'wholesalex' ),
							'desc'    => __( 'Tier Price Table Vertical Style', 'wholesalex' ),
							'default' => 'no',
						),

						'_settings_show_tierd_pricing_table' => array(
							'type'    => 'slider',
							'label'   => __( 'Show Tierd Pricing Table', 'wholesalex' ),
							'help'    => '',
							'default' => 'yes',
						),
						'_settings_product_visibility'     => array(
							'label' => __( 'Visibility', 'wholesalex' ),
							'type'  => 'visibility_section',
							'attr'  => array(
								'_hide_for_b2c'      => array(
									'type'    => 'slider',
									'label'   => __( 'Hide product for B2C', 'wholesalex' ),
									'help'    => '',
									'default' => 'no',
								),
								'_hide_for_visitors' => array(
									'type'    => 'slider',
									'label'   => __( 'Hide product for Visitors', 'wholesalex' ),
									'help'    => '',
									'default' => 'no',
								),
								'_hide_for_b2b_role_n_user' => array(
									'type'    => 'select',
									'label'   => __( 'Hide B2B Role and Users', 'wholesalex' ),
									'options' => array(
										''              => __( 'Choose Options...', 'wholesalex' ),
										'b2b_all'       => __( 'All B2B Users', 'wholesalex' ),
										'b2b_specific'  => __( 'Specific B2B Roles', 'wholesalex' ),
										'user_specific' => __( 'Specific Register Users', 'wholesalex' ),
									),
									'help'    => '',
									'default' => '',
								),
								'_hide_for_roles'    => array(
									'type'        => 'multiselect',
									'label'       => '',
									'options'     => $__roles_options,
									'placeholder' => __( 'Choose Roles...', 'wholesalex' ),
									'default'     => array(),
								),
								'_hide_for_users'    => array(
									'type'        => 'multiselect',
									'label'       => '',
									'options'     => $__users_options,
									'placeholder' => __( 'Choose Users...', 'wholesalex' ),
									'default'     => array(),
								),
							),
						),
					),
				),
			),
		);
	}


	/**
	 * Single Product Field Return.
	 */
	public static function get_product_fields() {
		$b2b_roles   = wholesalex()->get_roles( 'b2b_roles_option' );
		$b2c_roles   = wholesalex()->get_roles( 'b2c_roles_option' );
		$__b2b_roles = array();
		foreach ( $b2b_roles as $role ) {
			if ( ! ( isset( $role['value'] ) && isset( $role['value'] ) ) ) {
				continue;
			}
			$__b2b_roles[ $role['value'] ] = array(
				'label'    => $role['name'],
				'type'     => 'tiers',
				'is_pro'   => true,
				'pro_data' => array(
					'type'  => 'limit',
					'value' => 3,
				),
				'attr'     => array(
					'_prices'               => array(
						'type' => 'prices',
						'attr' => array(
							'wholesalex_base_price' => array(
								'type'    => 'number',
								'label'   => __( 'Base Price', 'wholesalex' ),
								'default' => '',
							),
							'wholesalex_sale_price' => array(
								'type'    => 'number',
								'label'   => __( 'Sale Price', 'wholesalex' ),
								'default' => '',
							),
						),
					),
					$role['value'] . 'tier' => array(
						'type'   => 'tier',
						'_tiers' => array(
							'columns'     => array(
								__( 'Discount Type', 'wholesalex' ),
								/* translators: %s: WholesaleX Role Name */
								sprintf( __( ' %s Price', 'wholesalex' ), $role['name'] ),
								__( 'Min Quantity', 'wholesalex' ),
							),
							'data'        => array(
								'_discount_type'   => array(
									'type'    => 'select',
									'options' => array(
										''            => __( 'Choose Discount Type...', 'wholesalex' ),
										'amount'      => __( 'Discount Amount', 'wholesalex' ),
										'percentage'  => __( 'Discount Percentage', 'wholesalex' ),
										'fixed_price' => __( 'Fixed Price', 'wholesalex' ),
									),
									'default' => '',
									'label'   => __( 'Discount Type', 'wholesalex' ),
								),
								'_discount_amount' => array(
									'type'        => 'number',
									'placeholder' => '',
									'default'     => '',
									'label'       => /* translators: %s: WholesaleX Role Name */
									sprintf( __( 'Price', 'wholesalex' ) ),
								),
								'_min_quantity'    => array(
									'type'        => 'number',
									'placeholder' => '',
									'default'     => '',
									'label'       => __( 'Min Quantity', 'wholesalex' ),
								),
							),
							'add'         => array(
								'type'  => 'button',
								'label' => __( 'Add Price Tier', 'wholesalex' ),
							),
							'upgrade_pro' => array(
								'type'  => 'button',
								'label' => __( 'Go For Unlimited Price Tiers', 'wholesalex' ),
							),
						),
					),
				),
			);
		}

		$__b2c_roles = array();
		foreach ( $b2c_roles as $role ) {
			if ( ! ( isset( $role['value'] ) && isset( $role['value'] ) ) ) {
				continue;
			}
			$__b2c_roles[ $role['value'] ] = array(
				'label'    => $role['name'],
				'type'     => 'tiers',
				'is_pro'   => true,
				'pro_data' => array(
					'type'  => 'limit',
					'value' => 2,
				),
				'attr'     => array(
					$role['value'] . 'tier' => array(
						'type'   => 'tier',
						'_tiers' => array(
							'columns'     => array(
								__( 'Discount Type', 'wholesalex' ),
								/* translators: %s: WholesaleX Role Name */
								sprintf( __( ' %s Price', 'wholesalex' ), $role['name'] ),
								__( 'Min Quantity', 'wholesalex' ),
							),
							'data'        => array(
								'_discount_type'   => array(
									'type'    => 'select',
									'options' => array(
										''            => __( 'Choose Discount Type...', 'wholesalex' ),
										'amount'      => __( 'Discount Amount', 'wholesalex' ),
										'percentage'  => __( 'Discount Percentage', 'wholesalex' ),
										'fixed_price' => __( 'Fixed Price', 'wholesalex' ),
									),
									'label'   => __( 'Discount Type', 'wholesalex' ),
									'default' => '',
								),
								'_discount_amount' => array(
									'type'        => 'number',
									'placeholder' => '',
									'label'       => /* translators: %s: WholesaleX Role Name */
									sprintf( __( 'Price', 'wholesalex' ) ),
									'default'     => '',
								),
								'_min_quantity'    => array(
									'type'        => 'number',
									'placeholder' => '',
									'default'     => '',
									'label'       => __( 'Min Quantity', 'wholesalex' ),
								),
							),
							'add'         => array(
								'type'  => 'button',
								'label' => __( 'Add Price Tier', 'wholesalex' ),
							),
							'upgrade_pro' => array(
								'type'  => 'button',
								'label' => __( 'Go For Unlimited Price Tiers', 'wholesalex' ),
							),
						),
					),
				),
			);
		}

		return apply_filters(
			'wholesalex_single_product_fields',
			array(
				'_b2c_section' => array(
					'label' => '',
					'attr'  => apply_filters( 'wholesalex_single_product_b2c_roles_tier_fields', $__b2c_roles ),
				),
				'_b2b_section' => array(
					/* translators: %s - Plugin Name */
					'label' => sprintf( __( '%s B2B Special', 'wholesalex' ), wholesalex()->get_plugin_name() ),
					'attr'  => apply_filters( 'wholesalex_single_product_b2b_roles_tier_fields', $__b2b_roles ),
				),
			),
		);
	}

	/**
	 * Filter Hidden Products
	 *
	 * @param array $args Related Products.
	 * @return array related products excluding hidden products.
	 * @since 1.0.2
	 */
	public function filter_hidden_products( $args ) {
		return array_diff( $args, wholesalex()->hidden_product_ids() );
	}

	/**
	 * Add WholesaleX Rule On Column On Product Page.
	 *
	 * @param array $columns Order Columns.
	 * @return array
	 * @since 1.0.4
	 */
	public function add_wholesalex_rule_on_column_on_product_page( $columns ) {
		/* translators: %s - Plugin Name */
		$columns['wsx_rule_on'] = sprintf( __( '%s Rules', 'wholesalex' ), wholesalex()->get_plugin_name() );
		return $columns;
	}

	/**
	 * Rule on All Variation List Modals
	 *
	 * @param string|int $product_id Product Id.
	 * @return void
	 * @since 1.4.7
	 */
	public function list_parent_modal( $product_id ) {
		?>
		<div class="wholesalex_rule_modal <?php echo esc_attr( 'product_' . $product_id ); ?>">
			<div class="modal_content">
				<div class="modal_header">
					<div class="modal-close-btn">
						<span class="close-modal-icon dashicons dashicons-no-alt" ></span>
					</div>
				</div>
				<div class="wholesalex_rule_on_lists">
					<?php
					$product = wc_get_product( $product_id );
					if ( $product->has_child() ) {
						$childrens = $product->get_children();
						foreach ( $childrens as $key => $child_id ) {
							if ( isset( $this->rule_on_lists[ $child_id ] ) && is_array( $this->rule_on_lists[ $child_id ] ) ) {
								foreach ( $this->rule_on_lists[ $child_id ] as $rule_on ) {
									echo wp_kses_post( $rule_on );
								}
							}
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Rule on List Modals
	 *
	 * @param string|int $product_id Product Id.
	 * @return void
	 * @since 1.0.4
	 */
	public function list_modal( $product_id ) {
		?>
		<div class="wholesalex_rule_modal <?php echo esc_attr( 'product_' . $product_id ); ?>">
			<div class="modal_content">
				<div class="modal_header">
					<div class="modal-close-btn">
						<span class="close-modal-icon dashicons dashicons-no-alt" ></span>
					</div>
				</div>
				<div class="wholesalex_rule_on_lists">
					<?php
					if ( isset( $this->rule_on_lists[ $product_id ] ) && is_array( $this->rule_on_lists[ $product_id ] ) ) {
						foreach ( $this->rule_on_lists[ $product_id ] as $rule_on ) {
							echo wp_kses_post( $rule_on );
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Populate Data on WholesaleX Rule On Column on Products page
	 *
	 * @param string $column Products Page Column.
	 * @param int    $product_id Product ID.
	 * @since 1.0.4
	 */
	public function populate_data_on_wholesalex_rule_on_column( $column, $product_id ) {

		if ( 'wsx_rule_on' === $column ) {

			$product = wc_get_product( $product_id );
			// Single Products.
			if ( $product->has_child() ) {
				$childrens = $product->get_children();
				foreach ( $childrens as $key => $child_id ) {
					$__discounts = wholesalex()->get_single_product_discount( $child_id );
					$status      = $this->wholesalex_rule_on( $__discounts, $child_id, 'Singles', $product_id );
				}
			} else {

				$__discounts = wholesalex()->get_single_product_discount( $product_id );

				$status = $this->wholesalex_rule_on( $__discounts, $product_id, 'Single' );
			}

			// Profile.
			$users = get_users(
				array(
					'fields'   => 'ids',
					'meta_key' => '__wholesalex_profile_discounts',
				)
			);

			$__parent_id = $product->get_parent_id();

			$__cat_ids = wc_get_product_term_ids( 0 === $__parent_id ? $product_id : $__parent_id, 'product_cat' );

			foreach ( $users as $user_id ) {
				$discounts = get_user_meta( $user_id, '__wholesalex_profile_discounts', true );
				if ( isset( $discounts['_profile_discounts']['tiers'] ) ) {
					$discounts = wholesalex()->filter_empty_tier( $discounts['_profile_discounts']['tiers'] );
				} else {
					$discounts = array();
				}

				if ( ! empty( $discounts ) ) {
					foreach ( $discounts as $discount ) {
						if ( ! isset( $discount['_product_filter'] ) ) {
							continue;
						}
						$__has_discount = true;
						switch ( $discount['_product_filter'] ) {
							case 'all_products':
								$this->rule_on_message( $product_id, 'Profile', $user_id );
								break;
							case 'products_in_list':
								if ( ! isset( $discount['products_in_list'] ) ) {
									break;
								}
								foreach ( $discount['products_in_list'] as $list ) {
									if ( (int) $product_id === (int) $list['value'] ) {
										$this->rule_on_message( $product_id, 'Profile', $user_id );
										break;
									}
								}
								break;
							case 'products_not_in_list':
								if ( ! isset( $discount['products_not_in_list'] ) ) {
									break;
								}
								$__flag = true;
								foreach ( $discount['products_not_in_list'] as $list ) {
									if ( isset( $list['value'] ) && (int) $product_id === (int) $list['value'] ) {
										$__flag = false;
									}
								}
								if ( $__flag ) {
									$__has_discount = true;
									$this->rule_on_message( $product_id, 'Profile', $user_id );
								}
								break;
							case 'cat_in_list':
								if ( ! isset( $discount['cat_in_list'] ) ) {
									break;
								}
								foreach ( $discount['cat_in_list'] as $list ) {
									if (in_array($list['value'], $__cat_ids)) { //phpcs:ignore
										$this->rule_on_message( $product_id, 'Profile', $user_id );
											break;
									}
								}
								break;
							case 'cat_not_in_list':
								if ( ! isset( $discount['cat_not_in_list'] ) ) {
									break;
								}
								$__flag = true;
								foreach ( $discount['cat_not_in_list'] as $list ) {
									if (in_array($list['value'], $__cat_ids)) { //phpcs:ignore
										$__flag = false;
									}
								}
								if ( $__flag ) {
									$__has_discount = true;
									if ( $__has_discount ) {
										$this->rule_on_message( $product_id, 'Profile', $user_id );
									}
								}
								break;
							case 'attribute_in_list':
								if ( ! isset( $discount['attribute_in_list'] ) ) {
									break;
								}
								if ( 'product_variation' === $product->post_type ) {
									foreach ( $discount['attribute_in_list'] as $list ) {
										if ( isset( $list['value'] ) && (int) $product_id === (int) $list['value'] ) {
											$this->rule_on_message( $product_id, 'Profile', $user_id );
											break;
										}
									}
								}
								break;
							case 'attribute_not_in_list':
								if ( ! isset( $discount['attribute_not_in_list'] ) ) {
									break;
								}
								if ( 'product_variation' === $product->post_type ) {
									$__flag = true;
									foreach ( $discount['attribute_not_in_list'] as $list ) {
										if ( isset( $list['value'] ) && (int) $product_id === (int) $list['value'] ) {
											$__flag = false;
										}
									}
									if ( $__flag ) {
										$__has_discount = true;
										if ( $__has_discount ) {
											$this->rule_on_message( $product_id, 'Profile', $user_id );
										}
									}
								}
								break;
						}
					}
				}
			}

			// Category.

			foreach ( $__cat_ids as $cat_id ) {
				$__discounts = wholesalex()->get_category_discounts( $cat_id );
				$status      = $this->wholesalex_rule_on( $__discounts, $product_id, 'Category', $cat_id );
				if ( $status ) {
					break;
				}
			}

			// Dynamic Rules.
			$__discounts = wholesalex()->get_dynamic_rules();
			foreach ( $__discounts as $discount ) {
				$__has_discount  = false;
				$__product_id    = $product->get_id();
				$__parent_id     = $product->get_parent_id();
				$__cat_ids       = wc_get_product_term_ids( 0 === $__parent_id ? $__product_id : $__parent_id, 'product_cat' );
				$__regular_price = $product->get_regular_price();
				$__for           = '';
				$__src_id        = '';

				if ( isset( $discount['_rule_status'] ) && ! empty( $discount['_rule_status'] ) && isset( $discount['_product_filter'] ) ) {
					switch ( $discount['_product_filter'] ) {
						case 'all_products':
							$__has_discount = true;
							$__for          = 'all_products';
							$__src_id       = -1;
							break;
						case 'products_in_list':
							if ( ! isset( $discount['products_in_list'] ) ) {
								break;
							}
							foreach ( $discount['products_in_list'] as $list ) {
								if ( (int) $__product_id === (int) $list['value'] ) {
									$__has_discount = true;
									$__for          = 'product';
									$__src_id       = $__product_id;
									break;
								}
							}
							break;
						case 'products_not_in_list':
							if ( ! isset( $discount['products_not_in_list'] ) ) {
								break;
							}
							$__flag = true;
							foreach ( $discount['products_not_in_list'] as $list ) {
								if ( isset( $list['value'] ) && (int) $__product_id === (int) $list['value'] ) {
									$__flag = false;
								}
							}
							if ( $__flag ) {
								$__has_discount = true;
								$__for          = 'product';
								$__src_id       = $__product_id;
							}
							break;
						case 'cat_in_list':
							if ( ! isset( $discount['cat_in_list'] ) ) {
								break;
							}
							foreach ( $discount['cat_in_list'] as $list ) {
							if (in_array($list['value'], $__cat_ids)) { //phpcs:ignore
									$__has_discount = true;
									$__for          = 'cat';
									$__src_id       = $list['value'];
									break;
								}
							}

							break;

						case 'cat_not_in_list':
							if ( ! isset( $discount['cat_not_in_list'] ) ) {
								break;
							}
							$__flag = true;
							foreach ( $discount['cat_not_in_list'] as $list ) {
							if (in_array($list['value'], $__cat_ids)) { //phpcs:ignore
									$__flag = false;
								}
							}
							if ( $__flag ) {
								$__has_discount = true;
								$__for          = 'cat';
								$__src_id       = isset( $__cat_ids[0] ) ? $__cat_ids[0] : '';
							}
							break;
						case 'attribute_in_list':
							if ( ! isset( $discount['attribute_in_list'] ) ) {
								break;
							}
							if ( 'product_variation' === $product->post_type ) {
								foreach ( $discount['attribute_in_list'] as $list ) {
									if ( isset( $list['value'] ) && (int) $__product_id === (int) $list['value'] ) {
											$__has_discount = true;
											$__for          = 'variation';
											$__src_id       = $__product_id;
											break;
									}
								}
							}
							break;
						case 'attribute_not_in_list':
							if ( ! isset( $discount['attribute_not_in_list'] ) ) {
								break;
							}
							if ( 'product_variation' === $product->post_type ) {
								$__flag = true;
								foreach ( $discount['attribute_not_in_list'] as $list ) {
									if ( isset( $list['value'] ) && (int) $__product_id === (int) $list['value'] ) {
											$__flag = false;
									}
								}
								if ( $__flag ) {
									$__has_discount = true;
									$__for          = 'variation';
									$__src_id       = $__product_id;
								}
							}
							break;
					}
				}
				if ( ! $__has_discount ) {
					continue;
				}

				if ( ! isset( $discount['_rule_type'] ) || ! isset( $discount[ $discount['_rule_type'] ] ) ) {
					continue;
				}

				$__rule_type = '';

				switch ( $discount['_rule_type'] ) {
					case 'product_discount':
						$__rule_type = 'Product Discount';

						break;
					case 'quantity_based':
						if ( ! isset( $discount['quantity_based']['tiers'] ) ) {
							break;
						}
						$__rule_type = 'Quantity Based';

						break;

					case 'payment_discount':
						$__rule_type = 'Payment Discount';
						break;
					case 'payment_order_qty':
						$__rule_type = 'Payment Order Quantity';
						break;
					case 'tax_rule':
						$__rule_type = 'Tax Rule';
						break;
					case 'extra_charge':
						$__rule_type = 'Extra Charge';
						break;
					case 'cart_discount':
						$__rule_type = 'Cart Discount';
						break;
					case 'shipping_rule':
						$__rule_type = 'Shipping Rule';
						break;
					case 'buy_x_get_y':
						$__rule_type = 'Buy X Get Y';
						break;
					case 'buy_x_get_one':
						$__rule_type = __( 'BOGO Discounts (Buy X Get One Free)', 'wholesalex' );
						break;
				}

				if ( ! isset( $discount['_rule_for'] ) ) {
					continue;
				}

				$__role_for = $discount['_rule_for'];
				switch ( $__role_for ) {
					case 'specific_roles':
						foreach ( $discount['specific_roles'] as $role ) {
							if ( '' !== $__rule_type ) {
								$this->rule_on_message( $product_id, 'dynamic', $role['name'], $__rule_type, $discount['_rule_title'], $discount['id'] );
							}
						}
						break;
					case 'specific_users':
						foreach ( $discount['specific_users'] as $user ) {
							$this->rule_on_message( $product_id, 'dynamic', 'user', $__rule_type, $discount['_rule_title'], $discount['id'] );
						}
						break;
					case 'all_roles':
						$this->rule_on_message( $product_id, 'dynamic', 'All Roles', $__rule_type, $discount['_rule_title'], $discount['id'] );
						break;
					case 'all_users':
						$this->rule_on_message( $product_id, 'dynamic', 'All Users', $__rule_type, $discount['_rule_title'], $discount['id'] );
						break;
					case 'all':
						$this->rule_on_message( $product_id, 'dynamic', 'All Users & Guest Users', $__rule_type, $discount['_rule_title'], $discount['id'] );
						break;
				}
			}

			$product = wc_get_product( $product_id );
			if ( $product && 'variable' === $product->get_type() ) {
				$is_any_variation_apply = false;
				$product                = wc_get_product( $product_id );
				if ( $product->has_child() ) {
					$childrens = $product->get_children();
					foreach ( $childrens as $key => $child_id ) {
						if ( isset( $this->rule_on_lists[ $child_id ] ) ) {
							$is_any_variation_apply = true;
							break;
						}
					}
				}
				if ( $is_any_variation_apply ) {
					?>
					<span class="wholesalex_rule_on_more" id="<?php echo esc_attr( 'product_' . $product_id ); ?>"> <?php echo esc_html( __( 'Show Variations', 'wholesalex' ) ); ?> </span> 
																		<?php
																			$this->list_parent_modal( $product_id );
				}
			}
			if ( isset( $this->rule_on_lists[ $product_id ] ) ) {
				$this->rule_on_lists[ $product_id ] = array_unique( $this->rule_on_lists[ $product_id ] );
				if ( count( $this->rule_on_lists[ $product_id ] ) > 3 ) {
					$__count = 0;
					if ( isset( $this->rule_on_lists[ $product_id ] ) && is_array( $this->rule_on_lists[ $product_id ] ) ) {
						foreach ( $this->rule_on_lists[ $product_id ] as $key => $value ) {
							echo wp_kses_post( $value );
							++$__count;
							if ( 3 === $__count ) {
								break;
							}
						}
					}
					?>
						<span class="wholesalex_rule_on_more" id="<?php echo esc_attr( 'product_' . $product_id ); ?>">More+</span>
					<?php
					$this->list_modal( $product_id );
				} elseif ( isset( $this->rule_on_lists[ $product_id ] ) && is_array( $this->rule_on_lists[ $product_id ] ) ) {
					foreach ( $this->rule_on_lists[ $product_id ] as $key => $value ) {
						echo wp_kses_post( $value );
					}
				}
			}
			if ( is_array( $this->rule_on_products_lists ) && ! empty( $this->rule_on_products_lists ) ) {
				$total_repeated_dynamic_rules = isset( $this->rule_on_products_lists[ $product_id ]['dynamic'] ) && is_array( $this->rule_on_products_lists[ $product_id ]['dynamic'] ) ? count( $this->rule_on_products_lists[ $product_id ]['dynamic'] ) : 0;
				$total_unique_dynamic_rules   = isset( $this->rule_on_products_lists[ $product_id ]['dynamic'] ) && is_array( $this->rule_on_products_lists[ $product_id ]['dynamic'] ) ? count( array_unique( array_column( $this->rule_on_products_lists[ $product_id ]['dynamic'], 3 ) ) ) : 0;
				$total_rules_apply            = array_sum( array_map( 'count', array_filter( $this->rule_on_products_lists[ $product_id ], 'is_array' ) ) );
				echo '<div 
						id="wsx-wholesalex-product-rule-openModalBtn" 
						class="wsx-product-rule-list" data-product-id="' . esc_attr( $product_id ) . '">
						<div> ' . esc_html( is_numeric( $total_unique_dynamic_rules ) ? ( $total_rules_apply - $total_repeated_dynamic_rules ) + $total_unique_dynamic_rules : $total_rules_apply ) . ' Rules Applied</div>
							<div style="line-height:0">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none">
								<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="m4 6 4 4 4-4"/>
							</svg>
						</div>
					</div>';
				$this->rule_on_products_lists_data[] = $this->rule_on_products_lists;
			}
			$this->rule_on_products_lists = array();
		}
	}


	/**
	 * Check Product Has Any WholesaleX Rule
	 *
	 * @param array      $__discounts Discounts.
	 * @param int|string $product_id Product ID.
	 * @param string     $rule_src Rule Src.
	 * @param string     $cat_id Category ID.
	 * @return boolean
	 * @since 1.0.4
	 */
	public function wholesalex_rule_on( $__discounts, $product_id, $rule_src, $cat_id = '' ) {
		$has_rule = false;
		foreach ( $__discounts as $role_id => $discount ) {

			$_temp          = $discount;
			$_temp['tiers'] = wholesalex()->filter_empty_tier( $_temp['tiers'] );

			if ( ! empty( $_temp['wholesalex_base_price'] ) || ! empty( $_temp['wholesalex_sale_price'] ) || ! empty( $_temp['tiers'] ) ) {
				$product        = wc_get_product( $product_id );
				$parent_id      = $product->get_parent_id();
				$variation_name = '';
				if ( $parent_id ) {
					$variation_name = $product->get_name();
				}
				$_role_name = wholesalex()->get_role_name_by_role_id( $role_id );
				if ( $product && $product->is_type( 'variation' ) ) {
					if ( ( ( isset( $_temp['wholesalex_base_price'] ) && ! empty( $_temp['wholesalex_base_price'] ) ) || ( isset( $_temp['wholesalex_sale_price'] ) && ! empty( $_temp['wholesalex_sale_price'] ) ) ) && ( isset( $_temp['tiers'] ) && ! empty( $_temp['tiers'] ) ) ) {
						if ( 'Singles' === $rule_src ) {
							$this->rule_on_message( $cat_id, $rule_src, $_role_name, 'Product Discount & Quantity Based Discount kk', $product_id, $variation_name );
						} else {
							$this->rule_on_message( $product_id, $rule_src, $_role_name, 'Product Discount & Quantity Based Discount', $product_id );
						}
					} elseif ( ( ( isset( $_temp['wholesalex_base_price'] ) && ! empty( $_temp['wholesalex_base_price'] ) ) || ( isset( $_temp['wholesalex_sale_price'] ) && ! empty( $_temp['wholesalex_sale_price'] ) ) ) ) {
						if ( 'Singles' === $rule_src ) {
							$this->rule_on_message( $cat_id, $rule_src, $_role_name, 'Product Discount kk', $product_id, $variation_name );
						} else {
							$this->rule_on_message( $product_id, $rule_src, $_role_name, 'Product Discount', $product_id );
						}
					} elseif ( 'Category' === $rule_src ) {
							$this->rule_on_message( $product_id, $rule_src, $_role_name, 'Quantity Based Discount', $cat_id );
					} elseif ( 'Singles' === $rule_src ) {
						$this->rule_on_message( $cat_id, $rule_src, $_role_name, 'Quantity Based Discount kk', $product_id, $variation_name );
					} else {
						$this->rule_on_message( $product_id, $rule_src, $_role_name, 'Quantity Based Discount', $product_id );
					}
				} elseif ( ( ( isset( $_temp['wholesalex_base_price'] ) && ! empty( $_temp['wholesalex_base_price'] ) ) ||
						( isset( $_temp['wholesalex_sale_price'] ) && ! empty( $_temp['wholesalex_sale_price'] ) ) )
						&& ( isset( $_temp['tiers'] ) && ! empty( $_temp['tiers'] ) ) ) {

					if ( 'Singles' === $rule_src ) {
						$this->rule_on_message( $cat_id, $rule_src, $_role_name, 'Product Discount & Quantity Based Discount kk', $product_id, $variation_name );
					} else {
						$this->rule_on_message( $product_id, $rule_src, $_role_name, 'Product Discount & Quantity Based Discount', $product_id );
					}
				} elseif ( ( ( isset( $_temp['wholesalex_base_price'] ) && ! empty( $_temp['wholesalex_base_price'] ) ) ||
							( isset( $_temp['wholesalex_sale_price'] ) && ! empty( $_temp['wholesalex_sale_price'] ) ) ) ) {
					if ( 'Singles' === $rule_src ) {
						$this->rule_on_message( $cat_id, $rule_src, $_role_name, 'Product Discount kk', $product_id, $variation_name );
					} else {
						$this->rule_on_message( $product_id, $rule_src, $_role_name, 'Product Discount', $product_id );
					}
				} elseif ( 'Category' === $rule_src ) {
						$this->rule_on_message( $product_id, $rule_src, $_role_name, 'Quantity Based Discount', $cat_id );
				} elseif ( 'Category' === $rule_src ) {
					$this->rule_on_message( $product_id, $rule_src, $_role_name, 'Quantity Based Discount', $product_id );
				} elseif ( 'Singles' === $rule_src ) {
					$this->rule_on_message( $cat_id, $rule_src, $_role_name, 'Quantity Based Discount kk', $product_id, $variation_name );
				} else {
					$this->rule_on_message( $product_id, $rule_src, $_role_name, 'Quantity Based Discount', $product_id );
				}
				$has_rule = true;
			}
		}

		return $has_rule;
	}

	/**
	 * Rule On Message.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $type Type.
	 * @param string $rule_src Rule Source.
	 * @param string $rule_type Rule Type.
	 * @param string $rule_title Rule Title.
	 * @param string $rule_id Rule ID.
	 * @return void
	 */
	public function rule_on_message( $product_id, $type, $rule_src, $rule_type = '', $rule_title = '', $rule_id = '' ) {
		$product       = wc_get_product( $product_id );
		$product_title = $product->get_name();
		if ( isset( $product_title ) && ! empty( $product_title ) ) {
			$product_title = $product_title;
			$this->rule_on_products_lists[ $product_id ]['product_title'] = $product_title;
		}

		if ( 'Profile' === $type ) {
			$user_info = get_userdata( $rule_src );
			if ( $user_info ) {
				$this->rule_on_products_lists[ $product_id ][ $type ][] = array( $rule_src, $user_info->user_login, 'Quantity Based Discount', $rule_src );
			}
		} elseif ( 'dynamic' === $type ) {
			$this->rule_on_products_lists[ $product_id ][ $type ][] = array( $rule_title, $rule_type, $rule_src, (int) $rule_id );
		} elseif ( 'Category' === $type ) {
			$this->rule_on_products_lists[ $product_id ][ $type ][] = array( $rule_src, $rule_type, $rule_title );
		} elseif ( 'Single' === $type ) {
			$this->rule_on_products_lists[ $product_id ][ $type ][] = array( $rule_src, $rule_type, $rule_title );
		} elseif ( 'Singles' === $type ) {
			$this->rule_on_products_lists[ $product_id ][ $type ][ $rule_title ][] = array( $rule_src, $rule_type, $rule_title, $rule_id );
		}
	}
	/**
	 * Product Page Popup for Wholesalex
	 *
	 * @return void
	 */
	public function render_wsx_product_rule_modal() {
		echo $this->wsx_product_rule_modal();
		echo $this->wholesalex_product_rule_modal_js();
	}

	/**
	 * Product Rule Modal
	 *
	 * @return string
	 */
	public function wsx_product_rule_modal() {
		ob_start();
		?>
		<!-- The Modal -->
		<div id="wsx-product-rule-myModal" class="wsx-product-rule-modal">
			<div class="wsx-product-rule-modal-content">
				<span class="wsx-product-rule-modal-close">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="m12 4-8 8m0-8 8 8"/></svg>
				</span>
	
				<div class="wsx-product-rule-product-title"></div>

				<!-- Menu Tabs -->
				<div class="wsx-product-rule-menu-container">
					<div class="wsx-product-rule-menu-title">Rules Source</div>
					<div class="wsx-product-rule-menu-tabs">
						<div class="wsx-product-rule-menu-tab wsx-dynamic-count active" data-target="dynamicRules">Dynamic</div>
						<div class="wsx-product-rule-menu-tab wsx-single-count" data-target="individualProduct">Individual Product</div>
						<div class="wsx-product-rule-menu-tab wsx-category-count" data-target="category">Category</div>
						<div class="wsx-product-rule-menu-tab wsx-profile-count" data-target="userProfile">Profile</div>
					</div>
				</div>

				<!-- Tab Contents -->
				<div class="wsx-product-rule-tab-container active wsx-scrollbar" id="dynamicRules">
					<div class="wsx-product-dynamic-data"></div>
				</div>
				
				<div class="wsx-product-rule-tab-container wsx-scrollbar" id="individualProduct">
					<div class="wsx-product-individual-data"></div>
				</div>
				
				<div class="wsx-product-rule-tab-container wsx-scrollbar" id="productVariables">
					<div class="wsx-product-variable-data"></div>
				</div>
				
				<div class="wsx-product-rule-tab-container wsx-scrollbar" id="category">
					<div class="wsx-product-category-data"></div>
				</div>
				
				<div class="wsx-product-rule-tab-container wsx-scrollbar" id="userProfile">
					<div class="wsx-product-user-data"></div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Wholesalex Product Rule Modal JS
	 *
	 * @return void
	 */
	public function wholesalex_product_rule_modal_js() {
		$product_data = $this->rule_on_products_lists_data;
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				'use strict';
					$(document).on('click','#wsx-wholesalex-product-rule-openModalBtn', function (e) {
						const selectedProductId = $(this).data('product-id');
						const productData = <?php echo json_encode( $product_data ); ?>;
						let apply_rules_count = 0;
						// Loop through the productData array and check if the product ID exists
						let productInfo = null;
						for (let i = 0; i < productData.length; i++) {
							if (productData[i][selectedProductId]) {
								productInfo = productData[i][selectedProductId];
								break;
							}
						}

						// If productInfo exists, generate lists for "Single", "Profile", "Category", and "dynamic"
						if (productInfo) {
							let dynamicHtml = '';
							let singleHtml = '';
							let categoryHtml = '';
							let profileHtml = '';
							
							// Function to create list items for each section
							function generateList( title, dataArray, freq = null ) {
								// let html = `<h3>${title}</h3><ul>`;
								let html = '';
								if( title == 'Dynamic') {
									html+=`<div class="wsx-product-rule-tab-header wsx-row-item-4">
												<div class="wsx-product-rule-tab-header-item">Rule Title</div>
												<div class="wsx-product-rule-tab-header-item">Rule Type</div>
												<div class="wsx-product-rule-tab-header-item">Applied For</div>
												<div class="wsx-product-rule-tab-header-item">Edit</div>
											</div>
											<div class="wsx-product-rule-menu-wrapper wsx-scrollbar">
												<div class="wsx-product-rule-tab-body">`;
								} else if (title == 'Singles') {
									html+=`<div class="wsx-product-rule-tab-header wsx-row-item-3">
												<div class="wsx-product-rule-tab-header-item">Variations</div>
												<div class="wsx-product-rule-tab-header-item"></div>
												<div class="wsx-product-rule-tab-header-item">Edit</div>
											</div>
											<div class="wsx-product-rule-menu-wrapper wsx-scrollbar">
												<div class="wsx-product-rule-tab-body">`;
								} else if (title == 'Single') {
									html+=`<div class="wsx-product-rule-tab-header wsx-row-item-3">
												<div class="wsx-product-rule-tab-header-item">Roles</div>
												<div class="wsx-product-rule-tab-header-item">Discount (Rule) Type</div>
												<div class="wsx-product-rule-tab-header-item">Edit</div>
											</div>
											<div class="wsx-product-rule-menu-wrapper wsx-scrollbar">
												<div class="wsx-product-rule-tab-body">`;
								} else if (title == 'Category') {
									html+=`<div class="wsx-product-rule-tab-header wsx-row-item-3">
												<div class="wsx-product-rule-tab-header-item">Roles</div>
												<div class="wsx-product-rule-tab-header-item">Discount (Rule) Type</div>
												<div class="wsx-product-rule-tab-header-item">Edit</div>
											</div>
											<div class="wsx-product-rule-menu-wrapper wsx-scrollbar">
												<div class="wsx-product-rule-tab-body">`;
								} else {
									html+=`<div class="wsx-product-rule-tab-header wsx-row-item-4">
												<div class="wsx-product-rule-tab-header-item">User ID</div>
												<div class="wsx-product-rule-tab-header-item">User Name</div>
												<div class="wsx-product-rule-tab-header-item">Discount (Rule) Type</div>
												<div class="wsx-product-rule-tab-header-item">Edit</div>
											</div>
											<div class="wsx-product-rule-menu-wrapper wsx-scrollbar">
												<div class="wsx-product-rule-tab-body">`;
								}
								let currentUrl = window.location.href;
								let newUrl = currentUrl.replace("?post_type=product", "?page=wholesalex#/dynamic-rules/edit/1726571948750");
								dataArray.forEach(function(item) {
									if( title == 'Dynamic') {
										const role_id = item[3];
										html +='<div class="wsx-product-rule-tab-row wsx-row-item-4">';
										item.forEach(function(value, index) {
											if (index == 3 ) {
												html += `<a target='_blank' class="wsx-link wsx-product-rule-tab-row-item wsx-product-rule-edit" href="${currentUrl.replace("?post_type=product", `?page=wholesalex#/dynamic-rules/edit/${value}`)}"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.333 1.333 12 4l-7.333 7.333H2V8.667l7.333-7.334ZM2 14.667h12"/></svg></a>`;
											} else if( index == 2 ) {
												if( value != 'All Roles' && value != 'All Users' ){
													if( value == 'user' ) {
														html += `<div class="wsx-product-rule-tab-row-item">${freq[role_id]} Users</div> `;
													}else{
														html += `<div class="wsx-product-rule-tab-row-item">${freq[role_id]} Roles</div> `;
													}
												}else{
													html += `<div class="wsx-product-rule-tab-row-item">${value}</div> `;
												}
											} else{
													html += `<div class="wsx-product-rule-tab-row-item wsx-ellipsis">${value}</div> `;
												}
										});
										html += '</div>';
									} 
									else if ( title == 'Singles' ) {
											
										let variationId = item[0];
										let variations = item[1];
										let firstVariation = variations[0];
										html += `<div class="wsx-accordion-wrapper wsx-text-space-nowrap wsx-w-fit">`;
											html += '<div class="wsx-product-rule-tab-row wsx-border-none wsx-bg-gray wsx-row-item-3">';
												html += `<div class="wsx-product-rule-tab-row-item wsx-ellipsis">${firstVariation[3]}</div>`; // Product Name
								
												html+= `<div class="wsx-product-rule-tab-row-item"><div class="wsx-btn-toggle-show"><div class="wsx-btn-toggle-text">Show Rules</div> <div class="wsx-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m6 9 6 6 6-6"/></svg></div></div></div>`;

												// Product ID
												html += `<a target='_blank' class="wsx-link wsx-product-rule-tab-row-item wsx-product-rule-edit" href='/wp-admin/post.php?post=${selectedProductId}&action=edit'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.333 1.333 12 4l-7.333 7.333H2V8.667l7.333-7.334ZM2 14.667h12"/></svg></a>`; // Product ID
											html += '</div>';
											html += `<div class="wsx-product-rule-container wsx-p-20">`;
												html+=`<div class="wsx-product-rule-tab-header wsx-row-item-2 wsx-w-auto">
													<div class="wsx-product-rule-tab-header-item">Roles</div>
													<div class="wsx-product-rule-tab-header-item">Discount (Rule) Type</div>
												</div>`;
												// Loop through all variations for the popup
												html += `<div class="wsx-product-rule-tab-body">`
													variations.forEach(function(variation) {
														html += `<div class="wsx-product-rule-tab-row wsx-row-item-2 wsx-bg-base1 wsx-w-auto"><div class="wsx-role-name wsx-product-rule-tab-row-item wsx-ellipsis">${variation[0]}</div><div class="wsx-product-rule-tab-row-item wsx-role-discount-type">${variation[1]}</div></div>`;
													});
												html += `</div>`;
											html += `</div>`;
										html += `</div>`;
									}
									else if ( title == 'Single' ) {
										const suffix = (title == 'Single' ? '/wp-admin/post.php?post' : '/wp-admin/term.php?taxonomy=product_cat&tag_ID');
										const prefix = (title == 'Single' ? 'action=edit' : 'post_type=product');
										html +='<div class="wsx-product-rule-tab-row wsx-row-item-3">';
										item.forEach(function(value, index) {
											if ( index == 2 ) {
												html += `<a target='_blank' class="wsx-link wsx-product-rule-tab-row-item wsx-product-rule-edit" href='${suffix}=${value}&${prefix}'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.333 1.333 12 4l-7.333 7.333H2V8.667l7.333-7.334ZM2 14.667h12"/></svg></a>`;
											}else{
												html += `<div class="wsx-product-rule-tab-row-item wsx-ellipsis">${value}</div> `;
											}
										});
										html += '</div>';
									}
									else if ( title == 'Category' ) {
										const suffix = (title == 'Single' ? '/wp-admin/post.php?post' : '/wp-admin/term.php?taxonomy=product_cat&tag_ID');
										const prefix = (title == 'Single' ? 'action=edit' : 'post_type=product');
										html +='<div class="wsx-product-rule-tab-row wsx-row-item-3">';
										item.forEach(function(value, index) {
											if ( index == 2 ) {
												html += `<a target='_blank' class="wsx-link wsx-product-rule-tab-row-item wsx-product-rule-edit" href='${suffix}=${value}&${prefix}'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.333 1.333 12 4l-7.333 7.333H2V8.667l7.333-7.334ZM2 14.667h12"/></svg></a>`;
											}else{
												html += `<div class="wsx-product-rule-tab-row-item wsx-ellipsis">${value}</div> `;
											}
										});
										html += '</div>';
									} 
									else {
										html +='<div class="wsx-product-rule-tab-row wsx-row-item-4">';
										item.forEach(function(value, index) {
											if ( index == 3 ) {
												html += `<a target='_blank' class="wsx-link wsx-product-rule-tab-row-item wsx-product-rule-edit" href='/wp-admin/user-edit.php?user_id=${value}'><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.333 1.333 12 4l-7.333 7.333H2V8.667l7.333-7.334ZM2 14.667h12"/></svg></a>`;
											}else{
												html += `<div class="wsx-product-rule-tab-row-item wsx-ellipsis">${value}</div> `;
											}
										});
										html += '</div>';
									}
								});
								
								// html += '</ul>';
								html += '</div>'+
									'</div>';
								return html;
							}
							const updateCountAndHtml = (type, title, data, isVariableProducr = false) => {
								
								if ( data === undefined && isVariableProducr == false ) {
									$(`.wsx-${type.toLowerCase()}-count`).text(title);
								}else if (data) {
									const resultArray = Array.isArray(data) ? data : Object.entries(data).map(([key, value]) => [key, value]);
									const html = generateList(type, resultArray);
									apply_rules_count += resultArray.length;
									$(`.wsx-${ (type === 'Singles' ? 'single' : type.toLowerCase() ) }-count`).text(`${title} (${resultArray.length})`);
									return html;
								}
								return `<div class="wsx-text-center"><svg xmlns="http://www.w3.org/2000/svg" width="150" height="168" viewBox="0 0 288 168" fill="none"><path fill="#070707" d="M26.59 129.909V159h-3.408L7.33 136.159h-.285V159H3.523v-29.091h3.409l15.909 22.898h.284v-22.898h3.466Zm15.019 29.546c-1.97 0-3.698-.469-5.185-1.407-1.477-.937-2.632-2.249-3.466-3.934-.823-1.686-1.235-3.656-1.235-5.909 0-2.273.412-4.257 1.235-5.952.834-1.695 1.99-3.012 3.466-3.949 1.487-.938 3.215-1.406 5.185-1.406 1.97 0 3.693.468 5.17 1.406 1.487.937 2.642 2.254 3.466 3.949.834 1.695 1.25 3.679 1.25 5.952 0 2.253-.416 4.223-1.25 5.909-.823 1.685-1.979 2.997-3.465 3.934-1.478.938-3.201 1.407-5.171 1.407Zm0-3.012c1.496 0 2.727-.383 3.693-1.15.966-.767 1.681-1.776 2.145-3.026.464-1.25.696-2.604.696-4.062 0-1.459-.232-2.818-.696-4.077-.464-1.26-1.179-2.278-2.145-3.054-.966-.777-2.197-1.165-3.693-1.165s-2.727.388-3.693 1.165c-.966.776-1.681 1.794-2.145 3.054-.464 1.259-.696 2.618-.696 4.077 0 1.458.232 2.812.696 4.062.464 1.25 1.179 2.259 2.145 3.026.966.767 2.197 1.15 3.693 1.15ZM76.414 159h-8.977v-29.091h9.375c2.822 0 5.237.582 7.244 1.747 2.008 1.156 3.547 2.817 4.617 4.986 1.07 2.159 1.605 4.744 1.605 7.756 0 3.03-.54 5.639-1.62 7.826-1.079 2.178-2.65 3.855-4.715 5.029-2.065 1.165-4.574 1.747-7.529 1.747Zm-5.454-3.125h5.227c2.405 0 4.399-.464 5.98-1.392 1.582-.928 2.76-2.249 3.537-3.963.777-1.714 1.165-3.755 1.165-6.122 0-2.349-.384-4.371-1.15-6.066-.768-1.704-1.913-3.011-3.438-3.92-1.525-.919-3.423-1.378-5.696-1.378H70.96v22.841Zm30.753 3.636c-1.383 0-2.637-.26-3.764-.781-1.127-.53-2.022-1.292-2.685-2.287-.663-1.004-.994-2.216-.994-3.636 0-1.25.246-2.263.739-3.04a5.221 5.221 0 0 1 1.974-1.847 10.387 10.387 0 0 1 2.727-.994 33.4 33.4 0 0 1 3.026-.54c1.325-.17 2.4-.298 3.224-.383.834-.095 1.44-.251 1.818-.469.389-.218.583-.597.583-1.136v-.114c0-1.401-.384-2.49-1.151-3.267-.757-.776-1.908-1.165-3.452-1.165-1.6 0-2.855.351-3.764 1.051-.909.701-1.548 1.449-1.917 2.245l-3.182-1.137c.568-1.325 1.326-2.358 2.273-3.096a8.526 8.526 0 0 1 3.125-1.563 12.968 12.968 0 0 1 3.352-.454c.701 0 1.506.085 2.415.255a7.738 7.738 0 0 1 2.656 1.009c.862.511 1.577 1.283 2.145 2.315.568 1.032.852 2.415.852 4.148V159h-3.352v-2.955h-.171c-.227.474-.606.981-1.136 1.52-.53.54-1.236.999-2.117 1.378-.88.379-1.955.568-3.224.568Zm.511-3.011c1.326 0 2.444-.26 3.353-.781.918-.521 1.609-1.193 2.074-2.017.473-.824.71-1.691.71-2.6v-3.068c-.142.171-.455.327-.938.469-.473.132-1.022.251-1.647.355-.616.095-1.217.18-1.804.256-.578.066-1.047.123-1.407.17-.871.114-1.685.298-2.443.554-.748.246-1.354.62-1.818 1.122-.455.493-.682 1.165-.682 2.017 0 1.165.431 2.046 1.293 2.642.87.587 1.974.881 3.309.881Zm24.656-19.318v2.841h-11.307v-2.841h11.307Zm-8.011-5.227h3.352v20.795c0 .947.137 1.657.412 2.131.284.464.644.776 1.08.937.445.152.913.227 1.406.227.369 0 .672-.018.909-.056l.568-.114.682 3.011a6.681 6.681 0 0 1-.952.256c-.407.095-.923.142-1.548.142a6.75 6.75 0 0 1-2.784-.611 5.517 5.517 0 0 1-2.244-1.861c-.588-.833-.881-1.884-.881-3.153v-21.704Zm19.034 27.556c-1.382 0-2.637-.26-3.764-.781-1.127-.53-2.022-1.292-2.685-2.287-.662-1.004-.994-2.216-.994-3.636 0-1.25.246-2.263.739-3.04a5.216 5.216 0 0 1 1.974-1.847 10.39 10.39 0 0 1 2.727-.994 33.48 33.48 0 0 1 3.026-.54 138.22 138.22 0 0 1 3.224-.383c.834-.095 1.44-.251 1.819-.469.388-.218.582-.597.582-1.136v-.114c0-1.401-.383-2.49-1.151-3.267-.757-.776-1.908-1.165-3.451-1.165-1.601 0-2.855.351-3.764 1.051-.91.701-1.549 1.449-1.918 2.245l-3.182-1.137c.568-1.325 1.326-2.358 2.273-3.096a8.523 8.523 0 0 1 3.125-1.563 12.973 12.973 0 0 1 3.352-.454c.701 0 1.506.085 2.415.255a7.738 7.738 0 0 1 2.656 1.009c.862.511 1.577 1.283 2.145 2.315.568 1.032.852 2.415.852 4.148V159h-3.352v-2.955h-.17c-.228.474-.606.981-1.137 1.52-.53.54-1.236.999-2.116 1.378-.881.379-1.956.568-3.225.568Zm.512-3.011c1.325 0 2.443-.26 3.352-.781.919-.521 1.61-1.193 2.074-2.017.473-.824.71-1.691.71-2.6v-3.068c-.142.171-.454.327-.937.469-.474.132-1.023.251-1.648.355-.616.095-1.217.18-1.804.256-.578.066-1.047.123-1.406.17-.872.114-1.686.298-2.444.554-.748.246-1.354.62-1.818 1.122-.454.493-.682 1.165-.682 2.017 0 1.165.431 2.046 1.293 2.642.871.587 1.974.881 3.31.881Zm26.431 2.5v-29.091h17.444v3.125h-13.921v9.83h12.614v3.125h-12.614V159h-3.523Zm29.845.455c-1.97 0-3.698-.469-5.185-1.407-1.477-.937-2.633-2.249-3.466-3.934-.824-1.686-1.236-3.656-1.236-5.909 0-2.273.412-4.257 1.236-5.952.833-1.695 1.989-3.012 3.466-3.949 1.487-.938 3.215-1.406 5.185-1.406 1.969 0 3.693.468 5.17 1.406 1.487.937 2.642 2.254 3.466 3.949.833 1.695 1.25 3.679 1.25 5.952 0 2.253-.417 4.223-1.25 5.909-.824 1.685-1.979 2.997-3.466 3.934-1.477.938-3.201 1.407-5.17 1.407Zm0-3.012c1.496 0 2.727-.383 3.693-1.15.966-.767 1.681-1.776 2.145-3.026.464-1.25.696-2.604.696-4.062 0-1.459-.232-2.818-.696-4.077-.464-1.26-1.179-2.278-2.145-3.054-.966-.777-2.197-1.165-3.693-1.165-1.497 0-2.728.388-3.694 1.165-.966.776-1.68 1.794-2.145 3.054-.464 1.259-.696 2.618-.696 4.077 0 1.458.232 2.812.696 4.062.465 1.25 1.179 2.259 2.145 3.026.966.767 2.197 1.15 3.694 1.15Zm28.313-6.363v-12.898h3.352V159h-3.352v-3.693h-.227c-.512 1.108-1.307 2.05-2.387 2.827-1.079.767-2.443 1.15-4.09 1.15-1.364 0-2.576-.298-3.637-.895-1.06-.606-1.894-1.515-2.5-2.727-.606-1.222-.909-2.76-.909-4.617v-13.863h3.352v13.636c0 1.591.445 2.86 1.336 3.807.899.947 2.045 1.42 3.437 1.42.833 0 1.681-.213 2.543-.639.871-.426 1.6-1.079 2.187-1.96.597-.881.895-2.003.895-3.366Zm12.405-4.205V159h-3.353v-21.818h3.239v3.409h.284a6.232 6.232 0 0 1 2.33-2.671c1.041-.681 2.386-1.022 4.034-1.022 1.477 0 2.77.303 3.878.909 1.107.596 1.969 1.505 2.585 2.727.615 1.212.923 2.746.923 4.602V159h-3.352v-13.636c0-1.714-.445-3.05-1.335-4.006-.891-.966-2.112-1.449-3.665-1.449-1.07 0-2.027.232-2.87.696-.833.464-1.491 1.141-1.974 2.031-.483.891-.724 1.97-.724 3.239Zm27.844 13.58c-1.818 0-3.423-.46-4.815-1.378-1.392-.928-2.481-2.235-3.267-3.921-.786-1.695-1.179-3.698-1.179-6.008 0-2.292.393-4.281 1.179-5.966.786-1.686 1.88-2.988 3.281-3.906 1.402-.919 3.021-1.378 4.858-1.378 1.421 0 2.543.236 3.367.71.833.464 1.467.994 1.903 1.591.445.587.791 1.07 1.037 1.449h.284v-10.739h3.352V159h-3.238v-3.352h-.398c-.246.397-.597.899-1.051 1.505-.455.597-1.103 1.132-1.946 1.606-.843.464-1.965.696-3.367.696Zm.455-3.012c1.345 0 2.481-.35 3.409-1.051.928-.71 1.634-1.69 2.116-2.94.483-1.26.725-2.713.725-4.361 0-1.629-.237-3.054-.71-4.276-.474-1.231-1.175-2.187-2.103-2.869-.928-.691-2.073-1.037-3.437-1.037-1.421 0-2.604.365-3.551 1.094-.938.72-1.643 1.7-2.117 2.94-.464 1.231-.696 2.614-.696 4.148 0 1.553.237 2.964.711 4.233.482 1.259 1.193 2.263 2.13 3.011.947.739 2.121 1.108 3.523 1.108Zm20.142-26.534-.284 20.909h-3.295l-.284-20.909h3.863Zm-1.932 29.318c-.7 0-1.302-.251-1.803-.753a2.459 2.459 0 0 1-.753-1.804c0-.7.251-1.302.753-1.804a2.461 2.461 0 0 1 1.803-.752c.701 0 1.303.251 1.804.752.502.502.753 1.104.753 1.804 0 .464-.118.891-.355 1.279a2.646 2.646 0 0 1-.923.937 2.431 2.431 0 0 1-1.279.341Z"/><path fill="#FEAD01" d="M76 24c0-6.627 5.373-12 12-12h31.029a12 12 0 0 0 8.486-3.515l4.97-4.97A12.002 12.002 0 0 1 140.971 0H192c6.627 0 12 5.373 12 12v72c0 6.627-5.373 12-12 12H88c-6.627 0-12-5.373-12-12V24Z"/><ellipse cx="118.223" cy="36.445" fill="#070707" rx="6.222" ry="9.333"/><ellipse cx="161.778" cy="36.444" fill="#070707" rx="6.222" ry="9.333"/><path stroke="#070707" stroke-width="3.111" d="M167.306 76.889C164.477 64.419 153.326 55.11 140 55.11c-13.325 0-24.476 9.309-27.306 21.778"/></svg></div>`;
							};

							if ( productInfo.Singles != undefined ) {
								singleHtml += updateCountAndHtml('Singles', 'Product Variables', productInfo.Singles);
							}
							singleHtml += updateCountAndHtml('Single', 'Individual Product', productInfo.Single, productInfo.Singles != undefined ? true : false);
							profileHtml = updateCountAndHtml('Profile', 'Profile', productInfo.Profile);
							categoryHtml = updateCountAndHtml('Category', 'Category', productInfo.Category);

							if ( productInfo.dynamic === undefined ) {
								$('.wsx-dynamic-count').text('Dynamic');
							} 
							else if (productInfo.dynamic) {
								const idFrequency = {};
								const uniqueEntries = [];
								productInfo.dynamic.forEach(function(entry) {
									const id = entry[3];
									if (!idFrequency[id]) {
										idFrequency[id] = 0;
										uniqueEntries.push(entry);
									}
									idFrequency[id]++;
								});
								dynamicHtml = generateList('Dynamic', uniqueEntries, idFrequency);
								let dynamicIds = productInfo.dynamic.map(item => item[3]);
								let uniqueIdLength = new Set(dynamicIds).size;
								$('.wsx-dynamic-count').text(`Dynamic Rules (${ ( !isNaN( uniqueIdLength ) ? uniqueIdLength : productInfo.dynamic.length ) })`);
								apply_rules_count += ( !isNaN( uniqueIdLength ) ? uniqueIdLength : 0 );
							}

							//Product Title with Rule Count
							if (productInfo.product_title) {
								const title_with_rule = `${productInfo.product_title} <span class="wsx-product-rule-rules-count">(${apply_rules_count} Rules)</span>`
								$('.wsx-product-rule-product-title').html(title_with_rule);
							}
							// Display the generated HTML in their respective containers
							$('.wsx-product-dynamic-data').html(dynamicHtml);   // For dynamic section
							$('.wsx-product-individual-data').html(singleHtml); // For individual/single section
							$('.wsx-product-category-data').html(categoryHtml); // For category section
							$('.wsx-product-user-data').html(profileHtml);      // For profile/user section
						} else {
							// If the product ID does not exist, clear the sections and show a message
							$('.wsx-product-dynamic-data').html('<span>No data available for this product.</span>');
						}
						//Show more or show less
						$('.wsx-btn-toggle-show').on('click', function () {
							$(this).toggleClass('active');
							$(this).closest('.wsx-accordion-wrapper').find('.wsx-product-rule-container').toggleClass('active');
							if ($(this).hasClass('active')) {
								$(this).find('.wsx-btn-toggle-text').text('Hide Rules');
							} else {
								$(this).find('.wsx-btn-toggle-text').text('Show Rules');
							}
						});

						$('#wsx-product-rule-myModal').css('display', 'flex').hide().fadeIn(300);
					})
					// Close the modal
					$('.wsx-product-rule-modal-close').on('click', function() {
						$('#wsx-product-rule-myModal').css('display', 'none').show().fadeOut(300);
					});
					// Close the modal when clicking outside of the modal content
					$(window).on('click', function(event) {
						if ($(event.target).is('#wsx-product-rule-myModal')) {
							$('#wsx-product-rule-myModal').css('display', 'none').show().fadeOut(300);
						}
					});
					// Switch between tab contents
					$('.wsx-product-rule-menu-tab').on('click', function() {
						// Remove active class from all tabs
						$('.wsx-product-rule-menu-tab').removeClass('active');
						// Add active class to clicked tab
						$(this).addClass('active');
						// Hide all tab content
						$('.wsx-product-rule-tab-container').removeClass('active');
						// Show clicked tab's content
						var target = $(this).data('target');
						$('#' + target).addClass('active');
					});
					
				});
		</script>
		<?php
	}
	/**
	 * Add More Tier Layouts
	 *
	 * @param array $existing_layouts Existing Layout.
	 * @return array
	 * @since 1.0.6 Tier Layouts added on v1.0.1 but Refactored on v1.0.6
	 */
	public function add_more_tier_layouts( $existing_layouts ) {
		$new_layouts = array(
			'pro_layout_four'  => WHOLESALEX_URL . '/assets/img/layout_four.png',
			'pro_layout_five'  => WHOLESALEX_URL . '/assets/img/layout_five.png',
			'pro_layout_six'   => WHOLESALEX_URL . '/assets/img/layout_six.png',
			'pro_layout_seven' => WHOLESALEX_URL . '/assets/img/layout_seven.png',
			'pro_layout_eight' => WHOLESALEX_URL . '/assets/img/layout_eight.png',
		);
		return array_merge( $existing_layouts, $new_layouts );
	}


	/**
	 * After Product Update : ProductX Filter Integration.
	 *
	 * @param string|int $post_id Post ID.
	 * @return void
	 * @since 1.1.5
	 */
	public function after_product_update( $post_id ) {
		$product = wc_get_product( $post_id );
		if ( $product->is_type( 'variable' ) ) {
			$role_ids = wholesalex()->get_roles( 'ids' );
			foreach ( $role_ids as $role_id ) {
				$base_price_meta_key = $role_id . '_base_price';
				$sale_price_meta_key = $role_id . '_sale_price';
				$price_meta_key      = $role_id . '_price';
				delete_post_meta( $post_id, $price_meta_key );
				foreach ( $product->get_available_variations() as $variation ) {
					$base_price = get_post_meta( $variation['variation_id'], $base_price_meta_key, true );
					$sale_price = get_post_meta( $variation['variation_id'], $sale_price_meta_key, true );
					if ( $sale_price ) {
						add_post_meta( $post_id, $price_meta_key, $sale_price );
					} elseif ( $base_price ) {
						add_post_meta( $post_id, $price_meta_key, $base_price );
					}
				}
			}
		}
	}



	/**
	 * Import Column Mapping: WC Importer and Exporter Plugin Integration
	 *
	 * @param array $columns Columns.
	 * @return array
	 * @since 1.1.5
	 */
	public function import_column_mapping( $columns ) {
		$roles = wholesalex()->get_roles( 'b2b_roles_option' );

		foreach ( $roles as $role ) {
			$columns[ $role['value'] . '_base_price' ] = $role['name'] . ' Base Price';
			$columns[ $role['value'] . '_sale_price' ] = $role['name'] . ' Sale Price';
		}
		return $columns;
	}

	/**
	 * Export Column Value
	 *
	 * @param mixed  $value Value.
	 * @param object $product Product.
	 * @param string $column_name Column Name.
	 * @since 1.1.5
	 */
	public function export_column_value( $value, $product, $column_name ) {
		$id    = $product->get_id();
		$value = get_post_meta( $id, $column_name, true );

		return $value;
	}


	/**
	 * Add WholesaleX Rolewise Column to WC Exporter
	 *
	 * @param array $columns Columns.
	 * @return array
	 * @since 1.1.5
	 */
	public function add_wholesale_rolewise_column_exporter( $columns ) {
		$roles = wholesalex()->get_roles( 'b2b_roles_option' );

		foreach ( $roles as $role ) {
			$columns[ $role['value'] . '_base_price' ] = $role['name'] . ' Base Price';
			$columns[ $role['value'] . '_sale_price' ] = $role['name'] . ' Sale Price';
		}
		return $columns;
	}


	/**
	 * Variable Product WholesaleX Rolewise base and Sale Price Bulk Action Options
	 *
	 * @return void
	 */
	public function variable_product_bulk_edit_actions() {
		$wholesalex_roles = wholesalex()->get_roles( 'b2b_roles_option' );

		$plugin_name       = wholesalex()->get_plugin_name();
		$optiongroup_label = $plugin_name . ' ' . __( 'Rolewise Pricing', 'wholesalex' );
		?>
		<optgroup label="<?php echo esc_html( $optiongroup_label ); ?>">
		<?php
		foreach ( $wholesalex_roles as $role ) {
			$option_name_base = $role['name'] . ' ' . __( 'Base Price' );
			$option_name_sale = $role['name'] . ' ' . __( 'Sale Price' );

			$option_value_base = 'wholesalex_product_price_' . $role['value'] . '_base';
			$option_value_sale = 'wholesalex_product_price_' . $role['value'] . '_sale';
			?>
				<option value="<?php echo esc_attr( $option_value_base ); ?>"><?php echo esc_html( $option_name_base ); ?></option>
				<option value="<?php echo esc_attr( $option_value_sale ); ?>"><?php echo esc_html( $option_name_sale ); ?></option>
			<?php
		}
		?>
		</optgroup>
		<?php
	}

	/**
	 * Handle WholesaleX Bulk Edit Variations
	 *
	 * @return void
	 */
	public function handle_wholesalex_bulk_edit_variations() {
		check_ajax_referer( 'bulk-edit-variations', 'security' );
		// Check permissions again and make sure we have what we need.
		if ( ! current_user_can( 'edit_products' ) || ! isset( $_POST['product_id'] ) || ! isset( $_POST['bulk_action'] ) ) {
			wp_die( -1 );
		}

		$product_id  = absint( sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) );
		$bulk_action = sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) );
		$data        = ! empty( $_POST['data'] ) ? wc_clean( wp_unslash( $_POST['data'] ) ) : array();
		$variations  = array();

		$variations = get_posts(
			array(
				'post_parent'    => $product_id,
				'posts_per_page' => -1,
				'post_type'      => 'product_variation',
				'fields'         => 'ids',
				'post_status'    => array( 'publish', 'private' ),
			)
		);

		$wholesalex_roles = wholesalex()->get_roles( 'b2b_roles_option' );

		foreach ( $wholesalex_roles as $role ) {
			if ( 'wholesalex_product_price_' . $role['value'] . '_base' === $bulk_action ) {
				foreach ( $variations as $variation_id ) {
					wholesalex()->save_single_product_discount( $variation_id, array( $role['value'] => array( 'wholesalex_base_price' => $data['value'] ) ) );
				}
			} elseif ( 'wholesalex_product_price_' . $role['value'] . '_sale' === $bulk_action ) {
				foreach ( $variations as $variation_id ) {
					wholesalex()->save_single_product_discount( $variation_id, array( $role['value'] => array( 'wholesalex_sale_price' => $data['value'] ) ) );
				}
			}
		}
		wp_send_json_success( 'Success' );
	}
}
