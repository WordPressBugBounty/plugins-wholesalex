<?php
/**
 * Wholesale pricing CSV importer.
 *
 * Handles CSV uploads and imports for the five Wholesale Pricing rule types:
 * wholesale_pricing, product_discount, cart_discount, bogo_discount, and buy_x_get_y.
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Import wholesale pricing rules from CSV.
 */
class Import_Wholesale_Pricing {

	const ERROR_LOG_OPTION = 'wholesalex_wholesale_pricing_import_error_log';
	const MAPPING_OPTION   = 'wholesalex_wholesale_pricing_import_mapping';

	/**
	 * Uploaded/import file path.
	 *
	 * @var string
	 */
	protected $file = '';

	/**
	 * Whether existing rules should be updated.
	 *
	 * @var bool
	 */
	protected $update_existing = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'wp_ajax_wholesalex_wholesale_pricing_import_upload_file', array( $this, 'handle_file_upload' ) );
		add_action( 'wp_ajax_wholesalex_wholesale_pricing_run_importer', array( $this, 'handle_import' ) );
		add_action( 'wp_ajax_wholesalex_do_ajax_wholesale_pricing_import', array( $this, 'do_ajax_import' ) );

		$this->file            = isset( $_REQUEST['file'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['file'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->update_existing = isset( $_REQUEST['update_existing'] ) && 'yes' === sanitize_text_field( wp_unslash( $_REQUEST['update_existing'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Validate current request.
	 *
	 * @return bool
	 */
	protected function is_request_allowed() {
		$nonce   = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		$context = Wholesale_Pricing::get_manager_context();
		return wp_verify_nonce( $nonce, 'wholesalex-registration' ) && ! $context['is_vendor'] && $context['can_manage'];
	}

	/**
	 * Check whether the file is a CSV/TXT import file.
	 *
	 * @param string $file File name/path.
	 * @return bool
	 */
	protected function is_valid_csv( $file ) {
		if ( function_exists( 'wc_is_file_valid_csv' ) && wc_is_file_valid_csv( $file, false ) ) {
			return true;
		}

		$extension = strtolower( pathinfo( (string) $file, PATHINFO_EXTENSION ) );
		return in_array( $extension, array( 'csv', 'txt' ), true );
	}

	/**
	 * Upload the selected CSV file and return headers for mapping.
	 *
	 * @return void
	 */
	public function handle_file_upload() {
		if ( ! $this->is_request_allowed() ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'You do not have permission to import wholesale pricing rules.', 'wholesalex' ),
				)
			);
		}

		$file = $this->handle_upload();
		if ( is_wp_error( $file ) ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => $file->get_error_message(),
				)
			);
		}

		$this->file = $file;
		$rows       = $this->read_csv_rows( $file, 1 );
		$headers    = isset( $rows['headers'] ) ? $rows['headers'] : array();

		wp_send_json(
			array(
				'status'          => true,
				'message'         => __( 'Success', 'wholesalex' ),
				'headers'         => $headers,
				'mapped_items'    => $this->auto_map_columns( $headers ),
				'sample'          => isset( $rows['rows'][0] ) ? $rows['rows'][0] : array(),
				'mapping_options' => $this->get_mapping_options(),
				'file'            => $this->file,
			)
		);
	}

	/**
	 * Upload file with WordPress upload handling.
	 *
	 * @return string|WP_Error
	 */
	protected function handle_upload() {
		if ( ! isset( $_FILES['import'] ) ) {
			return new WP_Error( 'wholesalex_wholesale_pricing_import_empty', __( 'File is empty. Please upload a CSV file.', 'wholesalex' ) );
		}

		$file_name = isset( $_FILES['import']['name'] ) ? wc_clean( wp_unslash( $_FILES['import']['name'] ) ) : '';
		if ( ! $this->is_valid_csv( $file_name ) ) {
			return new WP_Error( 'wholesalex_wholesale_pricing_import_invalid', __( 'Invalid file type. The importer supports CSV and TXT file formats.', 'wholesalex' ) );
		}

		$upload = wp_handle_upload(
			$_FILES['import'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			array(
				'test_form' => false,
				'test_type' => false,
				'mimes'     => array(
					'csv' => 'text/csv',
					'txt' => 'text/plain',
				),
			)
		);

		if ( isset( $upload['error'] ) ) {
			return new WP_Error( 'wholesalex_wholesale_pricing_import_upload_error', $upload['error'] );
		}

		$attachment = array(
			'post_title'     => basename( $upload['file'] ),
			'post_content'   => $upload['url'],
			'post_mime_type' => $upload['type'],
			'guid'           => $upload['url'],
			'context'        => 'import',
			'post_status'    => 'private',
		);

		$id = wp_insert_attachment( $attachment, $upload['file'] );
		wp_schedule_single_event( time() + DAY_IN_SECONDS, 'importer_scheduled_cleanup', array( $id ) );

		return $upload['file'];
	}

	/**
	 * Return importer mapping options.
	 *
	 * @return array
	 */
	protected function get_mapping_options() {
		return array_merge(
			self::get_csv_columns(),
			array(
				'product_discount' => __( 'Dynamic Rule Product Discount Data', 'wholesalex' ),
				'cart_discount'    => __( 'Dynamic Rule Cart Discount Data', 'wholesalex' ),
				'buy_x_get_one'    => __( 'Dynamic Rule BOGO Discount Data', 'wholesalex' ),
				'buy_x_get_y'      => __( 'Dynamic Rule Buy X Get Y Data', 'wholesalex' ),
			)
		);
	}

	/**
	 * Return the canonical export/import columns.
	 *
	 * Keep this list synced with the parser so exported files can be edited and
	 * imported again without custom mapping.
	 *
	 * @return array
	 */
	public static function get_csv_columns() {
		return array(
			'id'                                   => __( 'ID', 'wholesalex' ),
			'status'                               => __( 'Status', 'wholesalex' ),
			'title'                                => __( 'Title', 'wholesalex' ),
			'rule_type'                            => __( 'Rule Type', 'wholesalex' ),
			'product_filter'                       => __( 'Product Filter', 'wholesalex' ),
			'products'                             => __( 'Products', 'wholesalex' ),
			'categories'                           => __( 'Categories', 'wholesalex' ),
			'brands'                               => __( 'Brands', 'wholesalex' ),
			'attributes'                           => __( 'Attributes', 'wholesalex' ),
			'skus'                                 => __( 'SKUs', 'wholesalex' ),
			'user_role_filter'                     => __( 'User Role Filter', 'wholesalex' ),
			'user_roles'                           => __( 'User Roles', 'wholesalex' ),
			'specific_users'                       => __( 'Specific Users', 'wholesalex' ),
			'discount_type'                        => __( 'Discount Type', 'wholesalex' ),
			'regular_amount'                       => __( 'Regular Discount Amount', 'wholesalex' ),
			'regular_amount_type'                  => __( 'Regular Discount Amount Type', 'wholesalex' ),
			'regular_label'                        => __( 'Regular Discount Label', 'wholesalex' ),
			'tiers'                                => __( 'Tiered Pricing Data', 'wholesalex' ),
			'cart_discount_type'                   => __( 'Cart Discount Type', 'wholesalex' ),
			'cart_discount_amount'                 => __( 'Cart Discount Amount', 'wholesalex' ),
			'cart_discount_name'                   => __( 'Cart Discount Name', 'wholesalex' ),
			'bogo_buy_x_qty'                       => __( 'BOGO Buy Quantity', 'wholesalex' ),
			'bogo_per_cart_once'                   => __( 'BOGO Once Per Cart', 'wholesalex' ),
			'bogo_show_badge'                      => __( 'BOGO Show Badge', 'wholesalex' ),
			'bogo_badge_label'                     => __( 'BOGO Badge Label', 'wholesalex' ),
			'bogo_badge_style'                     => __( 'BOGO Badge Style', 'wholesalex' ),
			'bogo_badge_position'                  => __( 'BOGO Badge Position', 'wholesalex' ),
			'bogo_badge_bg_color'                  => __( 'BOGO Badge Background Color', 'wholesalex' ),
			'bogo_badge_text_color'                => __( 'BOGO Badge Text Color', 'wholesalex' ),
			'bxgy_min_qty'                         => __( 'Buy X Get Y Minimum Quantity', 'wholesalex' ),
			'bxgy_free_products'                   => __( 'Buy X Get Y Free Products', 'wholesalex' ),
			'bxgy_free_item_count'                 => __( 'Buy X Get Y Free Item Count', 'wholesalex' ),
			'bxgy_per_cart_once'                   => __( 'Buy X Get Y Once Per Cart', 'wholesalex' ),
			'bxgy_show_free_item'                  => __( 'Buy X Get Y Show Free Item', 'wholesalex' ),
			'bxgy_show_badge'                      => __( 'Buy X Get Y Show Badge', 'wholesalex' ),
			'bxgy_badge_label'                     => __( 'Buy X Get Y Badge Label', 'wholesalex' ),
			'bxgy_badge_style'                     => __( 'Buy X Get Y Badge Style', 'wholesalex' ),
			'bxgy_badge_position'                  => __( 'Buy X Get Y Badge Position', 'wholesalex' ),
			'bxgy_badge_bg_color'                  => __( 'Buy X Get Y Badge Background Color', 'wholesalex' ),
			'bxgy_badge_text_color'                => __( 'Buy X Get Y Badge Text Color', 'wholesalex' ),
			'restriction_enable_quantity_limits'   => __( 'Enable Quantity Limits', 'wholesalex' ),
			'restriction_min_quantity'             => __( 'Minimum Quantity', 'wholesalex' ),
			'restriction_max_quantity'             => __( 'Maximum Quantity', 'wholesalex' ),
			'restriction_min_quantity_message'     => __( 'Minimum Quantity Message', 'wholesalex' ),
			'restriction_max_quantity_message'     => __( 'Maximum Quantity Message', 'wholesalex' ),
			'restriction_enable_quantity_step'     => __( 'Enable Quantity Step', 'wholesalex' ),
			'restriction_quantity_step'            => __( 'Quantity Step', 'wholesalex' ),
			'restriction_enable_value_limits'      => __( 'Enable Value Limits', 'wholesalex' ),
			'restriction_min_amount'               => __( 'Minimum Amount', 'wholesalex' ),
			'restriction_max_amount'               => __( 'Maximum Amount', 'wholesalex' ),
			'restriction_min_amount_message'       => __( 'Minimum Amount Message', 'wholesalex' ),
			'restriction_max_amount_message'       => __( 'Maximum Amount Message', 'wholesalex' ),
			'restriction_tiered_combined_variations' => __( 'Tiered Combined Variations', 'wholesalex' ),
			'design_table_style'                   => __( 'Design Table Style', 'wholesalex' ),
			'design_vertical_style'                => __( 'Design Vertical Style', 'wholesalex' ),
			'design_border_radius'                 => __( 'Design Border Radius', 'wholesalex' ),
			'design_table_heading'                 => __( 'Design Table Heading', 'wholesalex' ),
			'design_header_bg_color'               => __( 'Design Header Background Color', 'wholesalex' ),
			'design_header_text_color'             => __( 'Design Header Text Color', 'wholesalex' ),
			'design_border_color'                  => __( 'Design Border Color', 'wholesalex' ),
			'design_active_row_bg_color'           => __( 'Design Active Row Background Color', 'wholesalex' ),
			'design_active_row_text_color'         => __( 'Design Active Row Text Color', 'wholesalex' ),
			'design_text_color'                    => __( 'Design Text Color', 'wholesalex' ),
			'design_font_size'                     => __( 'Design Font Size', 'wholesalex' ),
			'design_price_display'                 => __( 'Design Price Display', 'wholesalex' ),
			'design_discount_text_color'           => __( 'Design Discount Text Color', 'wholesalex' ),
			'design_discount_bg_color'             => __( 'Design Discount Background Color', 'wholesalex' ),
			'conditions'                           => __( 'Conditions Data', 'wholesalex' ),
			'start_date'                           => __( 'Start Date', 'wholesalex' ),
			'end_date'                             => __( 'End Date', 'wholesalex' ),
		);
	}

	/**
	 * Export selected wholesale pricing rules as a CSV file.
	 *
	 * @return void
	 */
	public function handle_export() {
		$nonce_value = isset( $_GET['nonce'] ) ? sanitize_key( wp_unslash( $_GET['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce_value, 'whx-export-wholesale-pricing' ) ) {
			return;
		}
		if ( empty( $_GET['action'] ) || 'export-wholesale-pricing-csv' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			return;
		}
		$context = Wholesale_Pricing::get_manager_context();
		if ( $context['is_vendor'] || ! $context['can_manage'] ) {
			return;
		}

		$exported_ids = isset( $_GET['exported_ids'] ) ? sanitize_text_field( wp_unslash( $_GET['exported_ids'] ) ) : '';
		$exported_ids = array_filter( array_map( 'sanitize_text_field', array_map( 'trim', explode( ',', $exported_ids ) ) ) );
		$selected     = array_flip( array_map( 'strval', $exported_ids ) );
		$export_all   = isset( $_GET['export_all'] ) && 'yes' === sanitize_text_field( wp_unslash( $_GET['export_all'] ) );
		$rules        = Wholesale_Pricing::get_all_rules();
		$columns      = self::get_csv_columns();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wholesalex_wholesale_pricing.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $output, array_values( $columns ) );

		foreach ( $rules as $rule ) {
			if ( ! $export_all && ! isset( $selected[ (string) $rule['id'] ] ) ) {
				continue;
			}
			fputcsv( $output, $this->generate_export_row( $rule, array_keys( $columns ) ) );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Generate one CSV row in canonical column order.
	 *
	 * @param array $rule Rule data.
	 * @param array $column_keys Column keys.
	 * @return array
	 */
	protected function generate_export_row( $rule, $column_keys ) {
		$row = array();
		foreach ( $column_keys as $column_key ) {
			$row[] = $this->get_export_column_value( $rule, $column_key );
		}
		return $row;
	}

	/**
	 * Get one export column value.
	 *
	 * @param array  $rule Rule data.
	 * @param string $column_key Column key.
	 * @return string
	 */
	protected function get_export_column_value( $rule, $column_key ) {
		switch ( $column_key ) {
			case 'id':
			case 'status':
			case 'title':
			case 'rule_type':
			case 'product_filter':
			case 'user_role_filter':
			case 'discount_type':
				return isset( $rule[ $column_key ] ) ? (string) $rule[ $column_key ] : '';

			case 'products':
			case 'categories':
			case 'brands':
			case 'attributes':
			case 'skus':
			case 'user_roles':
			case 'specific_users':
				return $this->serialize_select_items( isset( $rule[ $column_key ] ) ? $rule[ $column_key ] : array() );

			case 'regular_amount':
				return isset( $rule['product_discount']['amount'] )
					? (string) $rule['product_discount']['amount']
					: ( isset( $rule['regular']['amount'] ) ? (string) $rule['regular']['amount'] : '' );

			case 'regular_amount_type':
				return isset( $rule['product_discount']['amount_type'] )
					? (string) $rule['product_discount']['amount_type']
					: ( isset( $rule['regular']['amount_type'] ) ? (string) $rule['regular']['amount_type'] : '' );

			case 'regular_label':
				return isset( $rule['product_discount']['label'] )
					? (string) $rule['product_discount']['label']
					: ( isset( $rule['regular']['label'] ) ? (string) $rule['regular']['label'] : '' );

			case 'tiers':
				return $this->serialize_tiers( isset( $rule['tiers'] ) ? $rule['tiers'] : array() );

			case 'cart_discount_type':
				return isset( $rule['cart']['discount_type'] ) ? (string) $rule['cart']['discount_type'] : '';

			case 'cart_discount_amount':
				return isset( $rule['cart']['discount_amount'] ) ? (string) $rule['cart']['discount_amount'] : '';

			case 'cart_discount_name':
				return isset( $rule['cart']['discount_name'] ) ? (string) $rule['cart']['discount_name'] : '';

			case 'conditions':
				return $this->serialize_conditions( isset( $rule['conditions']['tiers'] ) ? $rule['conditions']['tiers'] : array() );

			case 'start_date':
				return isset( $rule['schedule']['start_date'] ) ? (string) $rule['schedule']['start_date'] : '';

			case 'end_date':
				return isset( $rule['schedule']['end_date'] ) ? (string) $rule['schedule']['end_date'] : '';
		}

		if ( 0 === strpos( $column_key, 'restriction_' ) ) {
			$key   = substr( $column_key, strlen( 'restriction_' ) );
			$value = isset( $rule['restrictions'][ $key ] ) ? $rule['restrictions'][ $key ] : '';
			return is_bool( $value ) ? $this->export_bool( $value ) : (string) $value;
		}

		if ( 0 === strpos( $column_key, 'design_' ) ) {
			$key   = substr( $column_key, strlen( 'design_' ) );
			$value = isset( $rule['design'][ $key ] ) ? $rule['design'][ $key ] : '';
			return is_bool( $value ) ? $this->export_bool( $value ) : (string) $value;
		}

		if ( 0 === strpos( $column_key, 'bogo_' ) ) {
			return $this->get_nested_export_value( $rule, 'bogo', substr( $column_key, strlen( 'bogo_' ) ) );
		}

		if ( 0 === strpos( $column_key, 'bxgy_' ) ) {
			return $this->get_nested_export_value( $rule, 'bxgy', substr( $column_key, strlen( 'bxgy_' ) ) );
		}

		return '';
	}

	/**
	 * Get a nested export value.
	 *
	 * @param array  $rule Rule data.
	 * @param string $bucket Bucket name.
	 * @param string $key Bucket field key.
	 * @return string
	 */
	protected function get_nested_export_value( $rule, $bucket, $key ) {
		$value = isset( $rule[ $bucket ][ $key ] ) ? $rule[ $bucket ][ $key ] : '';
		if ( is_bool( $value ) ) {
			return $this->export_bool( $value );
		}
		if ( is_array( $value ) ) {
			return $this->serialize_select_items( $value );
		}
		return (string) $value;
	}

	/**
	 * Serialize MultiSelect items for round-trip import.
	 *
	 * @param array $items Items.
	 * @return string
	 */
	protected function serialize_select_items( $items ) {
		if ( ! is_array( $items ) ) {
			return '';
		}

		$values = array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) ) {
				$value    = isset( $item['value'] ) ? (string) $item['value'] : '';
				$name     = isset( $item['name'] ) ? (string) $item['name'] : '';
				$values[] = $name ? $value . '(' . $name . ')' : $value;
			} else {
				$values[] = (string) $item;
			}
		}
		return implode( ';', array_filter( $values, 'strlen' ) );
	}

	/**
	 * Serialize pricing tiers.
	 *
	 * @param array $tiers Tiers.
	 * @return string
	 */
	protected function serialize_tiers( $tiers ) {
		if ( ! is_array( $tiers ) ) {
			return '';
		}

		$out = array();
		foreach ( $tiers as $tier ) {
			if ( ! is_array( $tier ) ) {
				continue;
			}
			$out[] = implode(
				',',
				array(
					'id:' . ( isset( $tier['id'] ) ? $tier['id'] : '' ),
					'min_qty:' . ( isset( $tier['min_qty'] ) ? $tier['min_qty'] : '' ),
					'max_qty:' . ( isset( $tier['max_qty'] ) && null !== $tier['max_qty'] ? $tier['max_qty'] : '' ),
					'amount:' . ( isset( $tier['amount'] ) ? $tier['amount'] : '' ),
					'amount_type:' . ( isset( $tier['amount_type'] ) ? $tier['amount_type'] : 'percentage' ),
				)
			);
		}
		return implode( ';', $out );
	}

	/**
	 * Serialize advanced conditions.
	 *
	 * @param array $conditions Conditions.
	 * @return string
	 */
	protected function serialize_conditions( $conditions ) {
		if ( ! is_array( $conditions ) ) {
			return '';
		}

		$out = array();
		foreach ( $conditions as $condition ) {
			if ( ! is_array( $condition ) ) {
				continue;
			}
			$out[] = implode(
				',',
				array(
					'_conditions_for:' . ( isset( $condition['_conditions_for'] ) ? $condition['_conditions_for'] : '' ),
					'_conditions_operator:' . ( isset( $condition['_conditions_operator'] ) ? $condition['_conditions_operator'] : '' ),
					'_conditions_value:' . ( isset( $condition['_conditions_value'] ) ? $condition['_conditions_value'] : '' ),
				)
			);
		}
		return implode( ';', $out );
	}

	/**
	 * Export boolean values consistently.
	 *
	 * @param bool $value Bool value.
	 * @return string
	 */
	protected function export_bool( $value ) {
		return $value ? 'yes' : 'no';
	}

	/**
	 * Auto-map likely column names.
	 *
	 * @param array $headers CSV headers.
	 * @return array
	 */
	protected function auto_map_columns( $headers ) {
		$defaults = array(
			'id' => 'id',
			'status' => 'status',
			'rulestatus' => 'status',
			'title' => 'title',
			'ruletitle' => 'title',
			'type' => 'rule_type',
			'ruletype' => 'rule_type',
			'applicableon' => 'product_filter',
			'productfilter' => 'product_filter',
			'products' => 'products',
			'productinlists' => 'products',
			'categories' => 'categories',
			'categoriesinlists' => 'categories',
			'brands' => 'brands',
			'brandinlists' => 'brands',
			'variations' => 'attributes',
			'variationinlists' => 'attributes',
			'attributes' => 'attributes',
			'sku' => 'skus',
			'skus' => 'skus',
			'skuinlists' => 'skus',
			'applicablefor' => 'user_role_filter',
			'userrolefilter' => 'user_role_filter',
			'applicableroles' => 'user_roles',
			'userroles' => 'user_roles',
			'applicableusers' => 'specific_users',
			'specificusers' => 'specific_users',
			'users' => 'specific_users',
			'discounttype' => 'discount_type',
			'productdiscountdata' => 'product_discount',
			'cartdiscountdata' => 'cart_discount',
			'bogodiscountdata' => 'buy_x_get_one',
			'buyxgetonedata' => 'buy_x_get_one',
			'buyxgetydata' => 'buy_x_get_y',
			'conditionsdata' => 'conditions',
			'startdate' => 'start_date',
			'enddate' => 'end_date',
		);

		foreach ( $this->get_mapping_options() as $field_key => $field_label ) {
			$defaults[ $this->normalize_key( $field_key ) ]   = $field_key;
			$defaults[ $this->normalize_key( $field_label ) ] = $field_key;
		}

		$user_mapping = get_user_option( self::MAPPING_OPTION );
		if ( ! empty( $user_mapping ) && is_array( $user_mapping ) ) {
			return $user_mapping;
		}

		$mapped = array();
		foreach ( $headers as $idx => $header ) {
			$key             = $this->normalize_key( $header );
			$mapped[ $idx ] = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
		}

		return $mapped;
	}

	/**
	 * Store selected mapping before import.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! $this->is_request_allowed() ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'You do not have permission to import wholesale pricing rules.', 'wholesalex' ),
				)
			);
		}

		$this->file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
		if ( ! is_file( $this->file ) || ! $this->is_valid_csv( $this->file ) ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'The file does not exist, please try again.', 'wholesalex' ),
				)
			);
		}

		if ( empty( $_POST['map_from'] ) || empty( $_POST['map_to'] ) ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'Please map at least one column before importing.', 'wholesalex' ),
				)
			);
		}

		$mapping_from = wc_clean( wp_unslash( $_POST['map_from'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$mapping_to   = wc_clean( wp_unslash( $_POST['map_to'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		update_user_option( get_current_user_id(), self::MAPPING_OPTION, $mapping_to );

		wp_send_json(
			array(
				'status'             => true,
				'mapping'            => array(
					'from' => array_values( $mapping_from ),
					'to'   => array_values( $mapping_to ),
				),
				'file'               => $this->file,
				'delimiter'          => ',',
				'character_encoding' => 'UTF-8',
			)
		);
	}

	/**
	 * Run the import.
	 *
	 * @return void
	 */
	public function do_ajax_import() {
		if ( ! $this->is_request_allowed() ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'You do not have permission to import wholesale pricing rules.', 'wholesalex' ),
				)
			);
		}

		$file = isset( $_POST['file'] ) ? wc_clean( wp_unslash( $_POST['file'] ) ) : '';
		if ( ! is_file( $file ) || ! $this->is_valid_csv( $file ) ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'Invalid import file.', 'wholesalex' ),
				)
			);
		}

		$mapping = isset( $_POST['mapping'] ) ? (array) wc_clean( wp_unslash( $_POST['mapping'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$from    = isset( $mapping['from'] ) && is_array( $mapping['from'] ) ? array_values( $mapping['from'] ) : array();
		$to      = isset( $mapping['to'] ) && is_array( $mapping['to'] ) ? array_values( $mapping['to'] ) : array();

		$rows    = $this->read_csv_rows( $file );
		$results = $this->import_rows( $rows, $from, $to );
		$errors  = array();

		foreach ( array_merge( $results['failed'], $results['skipped'] ) as $error ) {
			if ( is_wp_error( $error ) ) {
				$error_data = $error->get_error_data();
				$errors[]   = array(
					'id'      => isset( $error_data['id'] ) ? esc_html( $error_data['id'] ) : '',
					'message' => wp_kses_post( $error->get_error_message() ),
				);
			}
		}

		wp_send_json(
			array(
				'status'        => true,
				'position'      => 'done',
				'percentage'    => 100,
				'imported'      => count( $results['imported'] ),
				'failed'        => count( $results['failed'] ),
				'updated'       => count( $results['updated'] ),
				'skipped'       => count( $results['skipped'] ),
				'errors'        => $errors,
				'updated_rules' => Wholesale_Pricing::get_rules_for_js(),
			)
		);
	}

	/**
	 * Read CSV file.
	 *
	 * @param string $file File path.
	 * @param int    $limit Optional row limit.
	 * @return array
	 */
	protected function read_csv_rows( $file, $limit = -1 ) {
		$headers = array();
		$rows    = array();
		$handle  = fopen( $file, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( false === $handle ) {
			return array(
				'headers' => $headers,
				'rows'    => $rows,
			);
		}

		$headers = fgetcsv( $handle, 0, ',', '"', "\0" );
		$headers = is_array( $headers ) ? array_map( array( $this, 'clean_csv_cell' ), $headers ) : array();

		if ( isset( $headers[0] ) ) {
			$headers[0] = $this->remove_utf8_bom( $headers[0] );
		}

		while ( false !== ( $row = fgetcsv( $handle, 0, ',', '"', "\0" ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			if ( ! count( array_filter( $row ) ) ) {
				continue;
			}
			$rows[] = array_map( array( $this, 'clean_csv_cell' ), $row );
			if ( $limit > 0 && count( $rows ) >= $limit ) {
				break;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return array(
			'headers' => $headers,
			'rows'    => $rows,
		);
	}

	/**
	 * Import parsed CSV rows.
	 *
	 * @param array $csv     CSV data.
	 * @param array $from    Source headers.
	 * @param array $to      Target fields.
	 * @return array
	 */
	protected function import_rows( $csv, $from, $to ) {
		$results = array(
			'imported' => array(),
			'failed'   => array(),
			'updated'  => array(),
			'skipped'  => array(),
		);

		$headers = isset( $csv['headers'] ) ? $csv['headers'] : array();
		$rows    = isset( $csv['rows'] ) ? $csv['rows'] : array();

		$map = array();
		foreach ( $headers as $idx => $header ) {
			$mapped_to = isset( $to[ $idx ] ) ? sanitize_key( $to[ $idx ] ) : '';
			if ( '' !== $mapped_to ) {
				$map[ $idx ] = $mapped_to;
			}
		}

		foreach ( $rows as $row_index => $row ) {
			$raw = array();
			foreach ( $map as $idx => $field ) {
				$raw[ $field ] = isset( $row[ $idx ] ) ? $row[ $idx ] : '';
			}

			$result = $this->process_row( $raw, $row_index + 2 );
			if ( is_wp_error( $result ) ) {
				$results['failed'][] = $result;
			} elseif ( isset( $result['skipped'] ) && $result['skipped'] ) {
				$results['skipped'][] = new WP_Error(
					'wholesalex_wholesale_pricing_import_skipped',
					$result['message'],
					array(
						'id'  => $result['id'],
						'row' => $row_index + 2,
					)
				);
			} elseif ( ! empty( $result['updated'] ) ) {
				$results['updated'][] = $result['id'];
			} else {
				$results['imported'][] = $result['id'];
			}
		}

		return $results;
	}

	/**
	 * Process one mapped CSV row.
	 *
	 * @param array $raw Raw mapped row.
	 * @param int   $line CSV line number.
	 * @return array|WP_Error
	 */
	protected function process_row( $raw, $line ) {
		$rule_type = $this->parse_rule_type( isset( $raw['rule_type'] ) ? $raw['rule_type'] : '' );

		if ( empty( $rule_type ) ) {
			foreach ( array( 'product_discount', 'cart_discount', 'buy_x_get_one', 'buy_x_get_y' ) as $legacy_key ) {
				if ( ! empty( $raw[ $legacy_key ] ) ) {
					$rule_type = $this->legacy_rule_type_from_key( $legacy_key );
					break;
				}
			}
		}

		if ( empty( $rule_type ) ) {
			return new WP_Error(
				'wholesalex_wholesale_pricing_import_invalid_rule_type',
				__( 'Only Wholesale Pricing, Product Discount, Cart Discount, BOGO Discount, and Buy X Get Y rules can be imported.', 'wholesalex' ),
				array(
					'id'  => isset( $raw['id'] ) ? $raw['id'] : '',
					'row' => $line,
				)
			);
		}

		$id       = ! empty( $raw['id'] ) ? sanitize_text_field( $raw['id'] ) : (string) floor( microtime( true ) * 1000 ) . $line;
		$exists   = null !== Wholesale_Pricing::get_rule( $id );
		$updating = false;

		if ( $exists && ! $this->update_existing ) {
			return array(
				'id'      => $id,
				'skipped' => true,
				'message' => __( 'A wholesale pricing rule with this ID already exists.', 'wholesalex' ),
			);
		}

		if ( $this->update_existing && ! $exists ) {
			return array(
				'id'      => $id,
				'skipped' => true,
				'message' => __( 'No matching wholesale pricing rule exists to update.', 'wholesalex' ),
			);
		}

		$base = $exists ? Wholesale_Pricing::get_rule( $id ) : $this->get_default_rule( $id, $rule_type );
		$rule = $this->merge_row_into_rule( $base, $raw, $rule_type );
		$rule['id']        = $id;
		$rule['rule_type'] = $rule_type;
		$updating          = $exists;

		Wholesale_Pricing::save_rule( $id, $rule );

		return array(
			'id'      => $id,
			'updated' => $updating,
		);
	}

	/**
	 * Merge mapped CSV data into a rule.
	 *
	 * @param array  $rule      Existing/default rule.
	 * @param array  $raw       Raw mapped row.
	 * @param string $rule_type Rule type.
	 * @return array
	 */
	protected function merge_row_into_rule( $rule, $raw, $rule_type ) {
		if ( isset( $raw['title'] ) && '' !== $raw['title'] ) {
			$rule['title'] = sanitize_text_field( $raw['title'] );
		}
		if ( isset( $raw['status'] ) && '' !== $raw['status'] ) {
			$rule['status'] = $this->parse_status( $raw['status'] );
		}

		$rule = $this->merge_targeting( $rule, $raw );
		$rule = $this->merge_restrictions( $rule, $raw );
		$rule = $this->merge_schedule_and_conditions( $rule, $raw );

		if ( 'wholesale_pricing' === $rule_type ) {
			$rule = $this->merge_pricing_rule( $rule, $raw );
		} elseif ( 'product_discount' === $rule_type ) {
			$rule = $this->merge_product_discount_rule( $rule, $raw );
		} elseif ( 'cart_discount' === $rule_type ) {
			$rule = $this->merge_cart_rule( $rule, $raw );
		} elseif ( 'bogo_discount' === $rule_type ) {
			$rule = $this->merge_bogo_rule( $rule, $raw );
		} elseif ( 'buy_x_get_y' === $rule_type ) {
			$rule = $this->merge_bxgy_rule( $rule, $raw );
		}

		return $rule;
	}

	/**
	 * Merge product and role targeting.
	 *
	 * @param array $rule Rule.
	 * @param array $raw Raw row.
	 * @return array
	 */
	protected function merge_targeting( $rule, $raw ) {
		if ( isset( $raw['product_filter'] ) && '' !== $raw['product_filter'] ) {
			$rule['product_filter'] = $this->parse_product_filter( $raw['product_filter'] );
		}

		foreach ( array( 'products', 'categories', 'brands', 'attributes', 'skus' ) as $field ) {
			if ( isset( $raw[ $field ] ) && '' !== $raw[ $field ] ) {
				$rule[ $field ] = $this->parse_select_items( $raw[ $field ], $field );
			}
		}

		if ( isset( $raw['user_role_filter'] ) && '' !== $raw['user_role_filter'] ) {
			$rule['user_role_filter'] = $this->parse_user_role_filter( $raw['user_role_filter'] );
		}
		if ( isset( $raw['user_roles'] ) && '' !== $raw['user_roles'] ) {
			$rule['user_roles']       = $this->parse_roles( $raw['user_roles'] );
			$rule['user_role_filter'] = empty( $rule['user_roles'] ) ? 'all_b2b' : 'specific_roles';
		}
		if ( isset( $raw['specific_users'] ) && '' !== $raw['specific_users'] ) {
			$rule['specific_users']   = $this->parse_users( $raw['specific_users'] );
			$rule['user_role_filter'] = empty( $rule['specific_users'] ) ? 'all_b2b' : 'specific_users';
		}

		return $rule;
	}

	/**
	 * Merge restrictions.
	 *
	 * @param array $rule Rule.
	 * @param array $raw Raw row.
	 * @return array
	 */
	protected function merge_restrictions( $rule, $raw ) {
		$map = array(
			'restriction_enable_quantity_limits' => 'enable_quantity_limits',
			'restriction_min_quantity' => 'min_quantity',
			'restriction_max_quantity' => 'max_quantity',
			'restriction_min_quantity_message' => 'min_quantity_message',
			'restriction_max_quantity_message' => 'max_quantity_message',
			'restriction_enable_quantity_step' => 'enable_quantity_step',
			'restriction_quantity_step' => 'quantity_step',
			'restriction_enable_value_limits' => 'enable_value_limits',
			'restriction_min_amount' => 'min_amount',
			'restriction_max_amount' => 'max_amount',
			'restriction_min_amount_message' => 'min_amount_message',
			'restriction_max_amount_message' => 'max_amount_message',
			'restriction_tiered_combined_variations' => 'tiered_combined_variations',
		);

		foreach ( $map as $csv_key => $restriction_key ) {
			if ( ! isset( $raw[ $csv_key ] ) || '' === $raw[ $csv_key ] ) {
				continue;
			}
			$is_bool = in_array( $restriction_key, array( 'enable_quantity_limits', 'enable_quantity_step', 'enable_value_limits', 'tiered_combined_variations' ), true );
			$rule['restrictions'][ $restriction_key ] = $is_bool ? $this->parse_bool( $raw[ $csv_key ] ) : sanitize_text_field( $raw[ $csv_key ] );
		}

		return $rule;
	}

	/**
	 * Merge schedule and condition data.
	 *
	 * @param array $rule Rule.
	 * @param array $raw Raw row.
	 * @return array
	 */
	protected function merge_schedule_and_conditions( $rule, $raw ) {
		if ( isset( $raw['start_date'] ) && '' !== $raw['start_date'] ) {
			$rule['schedule']['start_date'] = $this->parse_date( $raw['start_date'] );
		}
		if ( isset( $raw['end_date'] ) && '' !== $raw['end_date'] ) {
			$rule['schedule']['end_date'] = $this->parse_date( $raw['end_date'] );
		}
		if ( isset( $raw['conditions'] ) && '' !== $raw['conditions'] ) {
			$rule['conditions'] = $this->parse_conditions( $raw['conditions'] );
		}

		return $rule;
	}

	/**
	 * Merge wholesale pricing fields.
	 *
	 * @param array $rule Rule.
	 * @param array $raw Raw row.
	 * @return array
	 */
	protected function merge_pricing_rule( $rule, $raw ) {
		if ( isset( $raw['product_discount'] ) && '' !== $raw['product_discount'] ) {
			$legacy = $this->parse_key_value_list( $raw['product_discount'] );
			$raw    = array_merge(
				$raw,
				array(
					'regular_amount'      => isset( $legacy['_discount_amount'] ) ? $legacy['_discount_amount'] : '',
					'regular_amount_type' => isset( $legacy['_discount_type'] ) ? $legacy['_discount_type'] : '',
					'regular_label'       => isset( $legacy['_discount_name'] ) ? $legacy['_discount_name'] : '',
				)
			);
		}

		if ( isset( $raw['discount_type'] ) && '' !== $raw['discount_type'] ) {
			$discount_type = $this->normalize_key( $raw['discount_type'] );
			$rule['discount_type'] = 'tiered' === $discount_type ? 'tiered' : 'regular';
		}
		if ( isset( $raw['regular_amount'] ) && '' !== $raw['regular_amount'] ) {
			$rule['regular']['amount'] = sanitize_text_field( $raw['regular_amount'] );
		}
		if ( isset( $raw['regular_amount_type'] ) && '' !== $raw['regular_amount_type'] ) {
			$rule['regular']['amount_type'] = $this->parse_discount_amount_type( $raw['regular_amount_type'] );
		}
		if ( isset( $raw['regular_label'] ) && '' !== $raw['regular_label'] ) {
			$rule['regular']['label'] = sanitize_text_field( $raw['regular_label'] );
		}
		if ( isset( $raw['tiers'] ) && '' !== $raw['tiers'] ) {
			$rule['discount_type'] = 'tiered';
			$rule['tiers']         = $this->parse_tiers( $raw['tiers'] );
		}

		$design_map = array(
			'design_table_style' => 'table_style',
			'design_vertical_style' => 'vertical_style',
			'design_border_radius' => 'border_radius',
			'design_table_heading' => 'table_heading',
			'design_header_bg_color' => 'header_bg_color',
			'design_header_text_color' => 'header_text_color',
			'design_border_color' => 'border_color',
			'design_active_row_bg_color' => 'active_row_bg_color',
			'design_active_row_text_color' => 'active_row_text_color',
			'design_text_color' => 'text_color',
			'design_font_size' => 'font_size',
			'design_price_display' => 'price_display',
			'design_discount_text_color' => 'discount_text_color',
			'design_discount_bg_color' => 'discount_bg_color',
		);

		foreach ( $design_map as $csv_key => $design_key ) {
			if ( ! isset( $raw[ $csv_key ] ) || '' === $raw[ $csv_key ] ) {
				continue;
			}
			$rule['design'][ $design_key ] = $this->sanitize_design_value( $design_key, $raw[ $csv_key ] );
		}

		return $rule;
	}

	/**
	 * Merge product discount fields without importing any restrictions.
	 *
	 * @param array $rule Rule.
	 * @param array $raw  Raw row.
	 * @return array
	 */
	protected function merge_product_discount_rule( $rule, $raw ) {
		if ( isset( $raw['product_discount'] ) && '' !== $raw['product_discount'] ) {
			$legacy = $this->parse_key_value_list( $raw['product_discount'] );
			$raw    = array_merge(
				$raw,
				array(
					'regular_amount'      => isset( $legacy['_discount_amount'] ) ? $legacy['_discount_amount'] : '',
					'regular_amount_type' => isset( $legacy['_discount_type'] ) ? $legacy['_discount_type'] : '',
					'regular_label'       => isset( $legacy['_discount_name'] ) ? $legacy['_discount_name'] : '',
				)
			);
		}

		if ( isset( $raw['regular_amount'] ) && '' !== $raw['regular_amount'] ) {
			$rule['product_discount']['amount'] = sanitize_text_field( $raw['regular_amount'] );
		}
		if ( isset( $raw['regular_amount_type'] ) && '' !== $raw['regular_amount_type'] ) {
			$rule['product_discount']['amount_type'] = $this->parse_discount_amount_type( $raw['regular_amount_type'] );
		}
		if ( isset( $raw['regular_label'] ) && '' !== $raw['regular_label'] ) {
			$rule['product_discount']['label'] = sanitize_text_field( $raw['regular_label'] );
		}

		$rule['restrictions'] = array();
		return $rule;
	}

	/**
	 * Merge cart discount fields.
	 *
	 * @param array $rule Rule.
	 * @param array $raw Raw row.
	 * @return array
	 */
	protected function merge_cart_rule( $rule, $raw ) {
		if ( isset( $raw['cart_discount'] ) && '' !== $raw['cart_discount'] ) {
			$legacy = $this->parse_key_value_list( $raw['cart_discount'] );
			$raw    = array_merge(
				$raw,
				array(
					'cart_discount_type'   => isset( $legacy['_discount_type'] ) ? $legacy['_discount_type'] : '',
					'cart_discount_amount' => isset( $legacy['_discount_amount'] ) ? $legacy['_discount_amount'] : '',
					'cart_discount_name'   => isset( $legacy['_discount_name'] ) ? $legacy['_discount_name'] : '',
				)
			);
		}

		if ( isset( $raw['cart_discount_type'] ) && '' !== $raw['cart_discount_type'] ) {
			$rule['cart']['discount_type'] = $this->parse_discount_amount_type( $raw['cart_discount_type'] );
		}
		if ( isset( $raw['cart_discount_amount'] ) && '' !== $raw['cart_discount_amount'] ) {
			$rule['cart']['discount_amount'] = sanitize_text_field( $raw['cart_discount_amount'] );
		}
		if ( isset( $raw['cart_discount_name'] ) && '' !== $raw['cart_discount_name'] ) {
			$rule['cart']['discount_name'] = sanitize_text_field( $raw['cart_discount_name'] );
		}

		return $rule;
	}

	/**
	 * Merge BOGO fields.
	 *
	 * @param array $rule Rule.
	 * @param array $raw Raw row.
	 * @return array
	 */
	protected function merge_bogo_rule( $rule, $raw ) {
		if ( isset( $raw['buy_x_get_one'] ) && '' !== $raw['buy_x_get_one'] ) {
			$legacy = $this->parse_key_value_list( $raw['buy_x_get_one'] );
			$raw    = array_merge(
				$raw,
				array(
					'bogo_buy_x_qty' => isset( $legacy['_minimum_purchase_count'] ) ? $legacy['_minimum_purchase_count'] : '',
				)
			);
		}

		if ( isset( $raw['bogo_buy_x_qty'] ) && '' !== $raw['bogo_buy_x_qty'] ) {
			$rule['bogo']['buy_x_qty'] = absint( $raw['bogo_buy_x_qty'] );
		}
		if ( isset( $raw['bogo_per_cart_once'] ) && '' !== $raw['bogo_per_cart_once'] ) {
			$rule['bogo']['per_cart_once'] = $this->parse_bool( $raw['bogo_per_cart_once'] );
		}

		return $this->merge_badge_fields( $rule, $raw, 'bogo' );
	}

	/**
	 * Merge Buy X Get Y fields.
	 *
	 * @param array $rule Rule.
	 * @param array $raw Raw row.
	 * @return array
	 */
	protected function merge_bxgy_rule( $rule, $raw ) {
		if ( isset( $raw['buy_x_get_y'] ) && '' !== $raw['buy_x_get_y'] ) {
			$legacy = $this->parse_key_value_list( $raw['buy_x_get_y'] );
			$raw    = array_merge(
				$raw,
				array(
					'bxgy_min_qty'         => isset( $legacy['_minimum_purchase_count'] ) ? $legacy['_minimum_purchase_count'] : '',
					'bxgy_free_products'   => isset( $legacy['_free_item'] ) ? $legacy['_free_item'] : '',
					'bxgy_free_item_count' => isset( $legacy['_free_item_quantity'] ) ? $legacy['_free_item_quantity'] : '',
				)
			);
		}

		if ( isset( $raw['bxgy_min_qty'] ) && '' !== $raw['bxgy_min_qty'] ) {
			$rule['bxgy']['min_qty'] = absint( $raw['bxgy_min_qty'] );
		}
		if ( isset( $raw['bxgy_free_products'] ) && '' !== $raw['bxgy_free_products'] ) {
			$rule['bxgy']['free_products'] = $this->parse_select_items( $raw['bxgy_free_products'], 'products' );
		}
		if ( isset( $raw['bxgy_free_item_count'] ) && '' !== $raw['bxgy_free_item_count'] ) {
			$rule['bxgy']['free_item_count'] = absint( $raw['bxgy_free_item_count'] );
		}
		if ( isset( $raw['bxgy_per_cart_once'] ) && '' !== $raw['bxgy_per_cart_once'] ) {
			$rule['bxgy']['per_cart_once'] = $this->parse_bool( $raw['bxgy_per_cart_once'] );
		}
		if ( isset( $raw['bxgy_show_free_item'] ) && '' !== $raw['bxgy_show_free_item'] ) {
			$rule['bxgy']['show_free_item'] = $this->parse_bool( $raw['bxgy_show_free_item'] );
		}

		return $this->merge_badge_fields( $rule, $raw, 'bxgy' );
	}

	/**
	 * Merge badge fields for BOGO/BXGY.
	 *
	 * @param array  $rule Rule.
	 * @param array  $raw Raw row.
	 * @param string $bucket Rule data bucket.
	 * @return array
	 */
	protected function merge_badge_fields( $rule, $raw, $bucket ) {
		$prefix = $bucket . '_';
		$map    = array(
			'show_badge' => 'show_badge',
			'badge_label' => 'badge_label',
			'badge_style' => 'badge_style',
			'badge_position' => 'badge_position',
			'badge_bg_color' => 'badge_bg_color',
			'badge_text_color' => 'badge_text_color',
		);

		foreach ( $map as $csv_suffix => $key ) {
			$csv_key = $prefix . $csv_suffix;
			if ( ! isset( $raw[ $csv_key ] ) || '' === $raw[ $csv_key ] ) {
				continue;
			}

			if ( 'show_badge' === $key ) {
				$rule[ $bucket ][ $key ] = $this->parse_bool( $raw[ $csv_key ] );
			} elseif ( 'badge_position' === $key ) {
				$rule[ $bucket ][ $key ] = $this->parse_badge_position( $raw[ $csv_key ] );
			} elseif ( 'badge_style' === $key ) {
				$rule[ $bucket ][ $key ] = $this->parse_badge_style( $raw[ $csv_key ] );
			} elseif ( in_array( $key, array( 'badge_bg_color', 'badge_text_color' ), true ) ) {
				$rule[ $bucket ][ $key ] = sanitize_hex_color( $raw[ $csv_key ] ) ? sanitize_hex_color( $raw[ $csv_key ] ) : $rule[ $bucket ][ $key ];
			} else {
				$rule[ $bucket ][ $key ] = sanitize_text_field( $raw[ $csv_key ] );
			}
		}

		return $rule;
	}

	/**
	 * Get default rule shape.
	 *
	 * @param string $id Rule ID.
	 * @param string $rule_type Rule type.
	 * @return array
	 */
	protected function get_default_rule( $id, $rule_type ) {
		$rule = array(
			'id'               => $id,
			'title'            => '',
			'status'           => 'inactive',
			'rule_type'        => $rule_type,
			'product_filter'   => 'all_products',
			'products'         => array(),
			'categories'       => array(),
			'brands'           => array(),
			'attributes'       => array(),
			'skus'             => array(),
			'user_role_filter' => 'all_b2b',
			'user_roles'       => array(),
			'specific_users'   => array(),
			'restrictions'     => array(
				'enable_quantity_limits'       => false,
				'min_quantity'                 => '',
				'max_quantity'                 => '',
				'min_quantity_message'         => '',
				'max_quantity_message'         => '',
				'enable_quantity_step'         => false,
				'quantity_step'                => '',
				'enable_value_limits'          => false,
				'min_amount'                   => '',
				'max_amount'                   => '',
				'min_amount_message'           => '',
				'max_amount_message'           => '',
				'tiered_combined_variations'   => false,
			),
			'schedule'         => array(
				'start_date' => '',
				'end_date'   => '',
			),
			'conditions'       => array(
				'tiers' => array(),
			),
		);

		if ( 'product_discount' === $rule_type ) {
			$rule['discount_type']    = 'regular';
			$rule['restrictions']     = array();
			$rule['product_discount'] = array(
				'amount'      => '',
				'amount_type' => 'percentage',
				'label'       => __( 'Product discount', 'wholesalex' ),
			);
		} elseif ( 'cart_discount' === $rule_type ) {
			$rule['cart'] = array(
				'discount_type'                  => 'percentage',
				'discount_amount'                => '',
				'discount_name'                  => '',
				'show_cart_discount_conditions'  => true,
				'show_label_before_promo'        => false,
				'label_text'                     => 'Cart Discount',
				'promo_desc_text'                => 'After adding to the cart',
				'condition_texts'                => $this->get_default_condition_texts(),
			);
		} elseif ( 'bogo_discount' === $rule_type ) {
			$rule['bogo'] = array(
				'buy_x_qty'               => '',
				'per_cart_once'           => false,
				'show_promo_text'         => true,
				'offer_text'              => '100% Off on 1 Product',
				'promo_text_popup'        => 'Buy at least {required_quantity} products',
				'promo_text_cart'         => '{product_title} (Buy X Get 1 Discounted)',
				'show_badge'              => false,
				'badge_label'             => 'Buy X Get 1 Discounted',
				'badge_style'             => 'style_one',
				'badge_position'          => '',
				'badge_bg_color'          => '#5a40e8',
				'badge_text_color'        => '#ffffff',
			);
		} elseif ( 'buy_x_get_y' === $rule_type ) {
			$rule['bxgy'] = array(
				'min_qty'          => '',
				'free_products'    => array(),
				'free_item_count'  => '',
				'per_cart_once'    => false,
				'badge_label'      => 'Buy X Get Y Free',
				'show_free_item'   => true,
				'show_badge'       => true,
				'badge_style'      => 'style_one',
				'badge_position'   => '',
				'badge_bg_color'   => '#5a40e8',
				'badge_text_color' => '#ffffff',
			);
		} else {
			$rule['discount_type'] = 'regular';
			$rule['regular']       = array(
				'amount'      => '',
				'amount_type' => 'percentage',
				'label'       => '',
			);
			$rule['tiers']         = array();
			$rule['design']        = array(
				'table_style'           => 'table_style',
				'vertical_style'        => false,
				'border_radius'         => false,
				'table_heading'         => 'Buy More, Save More',
				'header_bg_color'       => '#F7F7F7',
				'header_text_color'     => '#3A3A3A',
				'border_color'          => '#E5E5E5',
				'active_row_bg_color'   => '#6C6CFF',
				'active_row_text_color' => '#FFFFFF',
				'text_color'            => '#494949',
				'font_size'             => 14,
				'price_display'         => 'fixed',
				'discount_text_color'   => '#ffffff',
				'discount_bg_color'     => '#070707',
				'column_priority'       => array(
					array(
						'label'  => 'Quantity_Range',
						'value'  => 'Quantity Range',
						'status' => true,
					),
					array(
						'label'  => 'Discount',
						'value'  => 'Discount',
						'status' => true,
					),
					array(
						'label'  => 'Price_Per_Unit',
						'value'  => 'Price Per Unit',
						'status' => true,
					),
				),
			);
		}

		return $rule;
	}

	/**
	 * Default condition text values.
	 *
	 * @return array
	 */
	protected function get_default_condition_texts() {
		return array(
			'cart_total_qty_less_conditions_text'                   => __( 'Keep your items below {max_value} to qualify.', 'wholesalex' ),
			'cart_total_qty_less_equal_conditions_text'             => __( 'Keep your items at {max_value} or less to qualify.', 'wholesalex' ),
			'cart_total_qty_greater_conditions_text'                => __( 'Add more than {min_value} items to qualify.', 'wholesalex' ),
			'cart_total_qty_greater_equal_conditions_text'          => __( 'Add {min_value} or more items to qualify.', 'wholesalex' ),
			'cart_total_qty_greater_less_conditions_text'           => __( 'Add more than {min_value} but less than {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_qty_greater_equal_less_equal_conditions_text' => __( 'Add {min_value} to {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_qty_greater_less_equal_conditions_text'     => __( 'Add more than {min_value} to {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_qty_greater_equal_less_conditions_text'     => __( 'Add {min_value} to less than {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_less_conditions_text'                => __( 'Keep your weight below {max_value} to qualify.', 'wholesalex' ),
			'cart_total_weight_less_equal_conditions_text'          => __( 'Keep your total weight at {max_value} or less to qualify.', 'wholesalex' ),
			'cart_total_weight_greater_conditions_text'             => __( 'Add more than {min_value} in weight to qualify.', 'wholesalex' ),
			'cart_total_weight_greater_equal_conditions_text'       => __( 'Add {min_value} or more in weight to qualify.', 'wholesalex' ),
			'cart_total_weight_greater_less_conditions_text'        => __( 'Add more than {min_value} but less than {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_greater_equal_less_equal_conditions_text' => __( 'Add {min_value} to {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_greater_less_equal_conditions_text'  => __( 'Add more than {min_value} to {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_greater_equal_less_conditions_text'  => __( 'Add {min_value} to less than {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_value_less_conditions_text'                 => __( 'Keep your spend below ${max_value} to qualify.', 'wholesalex' ),
			'cart_total_value_less_equal_conditions_text'           => __( 'Keep your spend at ${max_value} or less to qualify.', 'wholesalex' ),
			'cart_total_value_greater_conditions_text'              => __( 'Spend more than ${min_value} to qualify.', 'wholesalex' ),
			'cart_total_value_greater_equal_conditions_text'        => __( 'Spend ${min_value} or more to qualify.', 'wholesalex' ),
			'cart_total_value_greater_less_conditions_text'         => __( 'Spend more than ${min_value} but less than ${max_value} to unlock this offer.', 'wholesalex' ),
			'cart_total_value_greater_equal_less_equal_conditions_text' => __( 'Spend between ${min_value} and ${max_value} to unlock this offer.', 'wholesalex' ),
			'cart_total_value_greater_less_equal_conditions_text'   => __( 'Spend more than ${min_value} and up to ${max_value} to unlock this offer.', 'wholesalex' ),
			'cart_total_value_greater_equal_less_conditions_text'   => __( 'Spend ${min_value} or more but less than ${max_value} to unlock this offer.', 'wholesalex' ),
		);
	}

	/**
	 * Parse a rule type.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function parse_rule_type( $value ) {
		$normalized = $this->normalize_key( $value );
		$map        = array(
			'wholesalepricing' => 'wholesale_pricing',
			'productdiscount' => 'product_discount',
			'regular' => 'wholesale_pricing',
			'tiered' => 'wholesale_pricing',
			'cartdiscount' => 'cart_discount',
			'bogo' => 'bogo_discount',
			'bogodiscount' => 'bogo_discount',
			'buyxgetone' => 'bogo_discount',
			'buyxgetonefree' => 'bogo_discount',
			'buyxgety' => 'buy_x_get_y',
			'buyxgetydiscount' => 'buy_x_get_y',
		);

		if ( in_array( $value, array( 'wholesale_pricing', 'product_discount', 'cart_discount', 'bogo_discount', 'buy_x_get_y' ), true ) ) {
			return $value;
		}

		return isset( $map[ $normalized ] ) ? $map[ $normalized ] : '';
	}

	/**
	 * Map dynamic-rule data column to wholesale-pricing type.
	 *
	 * @param string $key Legacy key.
	 * @return string
	 */
	protected function legacy_rule_type_from_key( $key ) {
		$map = array(
			'product_discount' => 'product_discount',
			'cart_discount'    => 'cart_discount',
			'buy_x_get_one'    => 'bogo_discount',
			'buy_x_get_y'      => 'buy_x_get_y',
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}

	/**
	 * Parse product filter.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function parse_product_filter( $value ) {
		$normalized = $this->normalize_key( $value );
		$map        = array(
			'allproducts' => 'all_products',
			'specificproducts' => 'specific_products',
			'products' => 'specific_products',
			'productsinlist' => 'specific_products',
			'productinlist' => 'specific_products',
			'specificcategories' => 'specific_categories',
			'categories' => 'specific_categories',
			'catinlist' => 'specific_categories',
			'categoriesinlist' => 'specific_categories',
			'brands' => 'brands',
			'brandinlist' => 'brands',
			'attributes' => 'attributes',
			'variations' => 'attributes',
			'attributeinlist' => 'attributes',
			'variationinlist' => 'attributes',
			'sku' => 'sku',
			'skus' => 'sku',
			'skuinlist' => 'sku',
		);

		if ( in_array( $value, array( 'all_products', 'specific_products', 'specific_categories', 'brands', 'attributes', 'sku' ), true ) ) {
			return $value;
		}

		return isset( $map[ $normalized ] ) ? $map[ $normalized ] : 'all_products';
	}

	/**
	 * Parse role filter.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function parse_user_role_filter( $value ) {
		$normalized = $this->normalize_key( $value );
		if ( in_array( $value, array( 'all', 'all_users', 'all_b2b', 'specific_users', 'specific_roles' ), true ) ) {
			return $value;
		}

		$map = array(
			'allregisteredandguestusers' => 'all',
			'registeredandguestusers'    => 'all',
			'allusers'                   => 'all_users',
			'allregisteredusers'         => 'all_users',
			'allb2broles'                => 'all_b2b',
			'allroles'                   => 'all_b2b',
			'specificusers'              => 'specific_users',
			'specificuser'               => 'specific_users',
			'applicableusers'             => 'specific_users',
			'specificroles'              => 'specific_roles',
			'specificrole'               => 'specific_roles',
			'applicableroles'             => 'specific_roles',
		);

		return isset( $map[ $normalized ] ) ? $map[ $normalized ] : 'all_b2b';
	}

	/**
	 * Parse roles into MultiSelect shape.
	 *
	 * @param string $value Raw value.
	 * @return array
	 */
	protected function parse_roles( $value ) {
		$out = array();
		foreach ( $this->split_list( $value ) as $role_id ) {
			$role_id    = preg_replace( '/\\(.*\\)/', '', $role_id );
			$role_id    = sanitize_text_field( trim( $role_id ) );
			$role_title = wholesalex()->get_role_name_by_role_id( $role_id );
			if ( $role_title ) {
				$out[] = array(
					'name'  => esc_attr( $role_title ),
					'value' => $role_id,
				);
			}
		}
		return $out;
	}

	/**
	 * Parse users into MultiSelect shape.
	 *
	 * @param string $value Raw value.
	 * @return array
	 */
	protected function parse_users( $value ) {
		$out = array();
		foreach ( $this->split_multi_value_list( $value ) as $user_value ) {
			$user_id = absint( $this->extract_id( $user_value ) );
			$user    = $user_id ? get_userdata( $user_id ) : false;
			if ( ! $user ) {
				continue;
			}

			$out[] = array(
				'name'  => esc_attr( $user->display_name . '(' . $user->user_email . ')' ),
				'value' => $user->ID,
			);
		}

		return $out;
	}

	/**
	 * Parse products/categories/brands/attributes/SKUs into MultiSelect shape.
	 *
	 * @param string $value Raw value.
	 * @param string $type Item type.
	 * @return array
	 */
	protected function parse_select_items( $value, $type ) {
		$out = array();
		foreach ( $this->split_multi_value_list( $value ) as $item ) {
			if ( 'skus' === $type ) {
				$sku = trim( (string) $item );
				$sku = 0 === strpos( $sku, 'sku:' ) ? substr( $sku, 4 ) : $sku;

				// Exported selections use value(name). The SKU is also the name,
				// so find a suffix that exactly repeats the preceding value. This
				// remains safe when the SKU itself contains parentheses.
				$position = strpos( $sku, '(' );
				while ( false !== $position ) {
					$candidate = substr( $sku, 0, $position );
					if ( substr( $sku, $position ) === '(' . $candidate . ')' ) {
						$sku = $candidate;
						break;
					}
					$position = strpos( $sku, '(', $position + 1 );
				}

				$sku = sanitize_text_field( $sku );
				if ( '' !== $sku ) {
					$out[] = array(
						'name'  => esc_attr( $sku ),
						'value' => 'sku:' . $sku,
					);
				}
				continue;
			}

			$item_id = $this->extract_id( $item );
			$label   = '';

			if ( 'products' === $type ) {
				$product = wc_get_product( $item_id );
				$label   = $product ? $product->get_title() : '';
			} elseif ( 'categories' === $type ) {
				$term  = get_term_by( 'id', $item_id, 'product_cat' );
				$label = $term ? $term->name : '';
			} elseif ( 'brands' === $type ) {
				foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand' ) as $taxonomy ) {
					$term = get_term_by( 'id', $item_id, $taxonomy );
					if ( $term ) {
						$label = $term->name;
						break;
					}
				}
			} else {
				$label = sanitize_text_field( preg_replace( '/\\(.*\\)/', '', $item ) );
			}

			if ( '' !== $label ) {
				$out[] = array(
					'name'  => esc_attr( $label ),
					'value' => 'attributes' === $type ? sanitize_text_field( $item_id ) : absint( $item_id ),
				);
			}
		}
		return $out;
	}

	/**
	 * Parse key-value list: key:value;key:value.
	 *
	 * @param string $value Raw value.
	 * @return array
	 */
	protected function parse_key_value_list( $value ) {
		$out = array();
		foreach ( $this->split_list( $value, ';' ) as $part ) {
			$bits = explode( ':', $part, 2 );
			if ( isset( $bits[0], $bits[1] ) ) {
				$out[ trim( $bits[0] ) ] = trim( $bits[1] );
			}
		}
		return $out;
	}

	/**
	 * Parse tier rows.
	 *
	 * @param string $value Raw value.
	 * @return array
	 */
	protected function parse_tiers( $value ) {
		$tiers = array();
		foreach ( $this->split_list( $value, ';' ) as $tier_data ) {
			$data = $this->parse_key_value_list( str_replace( ',', ';', $tier_data ) );
			$tiers[] = array(
				'id'          => isset( $data['id'] ) ? sanitize_text_field( $data['id'] ) : (string) floor( microtime( true ) * 1000 ) . count( $tiers ),
				'min_qty'     => isset( $data['min_qty'] ) ? absint( $data['min_qty'] ) : absint( isset( $data['min'] ) ? $data['min'] : 1 ),
				'max_qty'     => isset( $data['max_qty'] ) && '' !== $data['max_qty'] ? absint( $data['max_qty'] ) : ( isset( $data['max'] ) && '' !== $data['max'] ? absint( $data['max'] ) : null ),
				'amount'      => isset( $data['amount'] ) ? sanitize_text_field( $data['amount'] ) : sanitize_text_field( isset( $data['discount'] ) ? $data['discount'] : '' ),
				'amount_type' => $this->parse_discount_amount_type( isset( $data['amount_type'] ) ? $data['amount_type'] : ( isset( $data['discount_type'] ) ? $data['discount_type'] : 'percentage' ) ),
			);
		}
		return $tiers;
	}

	/**
	 * Parse conditions into rule shape.
	 *
	 * @param string $value Raw value.
	 * @return array
	 */
	protected function parse_conditions( $value ) {
		$tiers = array();
		foreach ( $this->split_list( $value, ';' ) as $condition_data ) {
			$data     = $this->parse_key_value_list( str_replace( ',', ';', $condition_data ) );
			$field    = isset( $data['_conditions_for'] ) ? $data['_conditions_for'] : ( isset( $data['field'] ) ? $data['field'] : '' );
			$operator = isset( $data['_conditions_operator'] ) ? $data['_conditions_operator'] : ( isset( $data['operator'] ) ? $data['operator'] : '' );
			$amount   = isset( $data['_conditions_value'] ) ? $data['_conditions_value'] : ( isset( $data['value'] ) ? $data['value'] : '' );
			if ( '' === $field || '' === $operator || '' === $amount ) {
				continue;
			}
			$tiers[] = array(
				'_conditions_for'      => sanitize_text_field( $field ),
				'_conditions_operator' => sanitize_text_field( $operator ),
				'_conditions_value'    => sanitize_text_field( $amount ),
			);
		}

		return array( 'tiers' => $tiers );
	}

	/**
	 * Parse amount type.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function parse_discount_amount_type( $value ) {
		$normalized = $this->normalize_key( $value );
		if ( in_array( $normalized, array( 'amount', 'fixed' ), true ) ) {
			return $normalized;
		}
		return 'percentage';
	}

	/**
	 * Parse badge style.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function parse_badge_style( $value ) {
		$value = sanitize_text_field( $value );
		return in_array( $value, array( 'style_one', 'style_two', 'style_three', 'style_four', 'style_five' ), true ) ? $value : 'style_one';
	}

	/**
	 * Parse badge position.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function parse_badge_position( $value ) {
		$normalized = $this->normalize_key( $value );
		if ( 'left' === $normalized ) {
			return 'left';
		}
		if ( 'right' === $normalized ) {
			return 'right';
		}
		return '';
	}

	/**
	 * Sanitize a design value by design key.
	 *
	 * @param string $key Design key.
	 * @param string $value Raw value.
	 * @return mixed
	 */
	protected function sanitize_design_value( $key, $value ) {
		if ( in_array( $key, array( 'vertical_style', 'border_radius' ), true ) ) {
			return $this->parse_bool( $value );
		}
		if ( in_array( $key, array( 'header_bg_color', 'header_text_color', 'border_color', 'active_row_bg_color', 'active_row_text_color', 'text_color', 'discount_text_color', 'discount_bg_color' ), true ) ) {
			return sanitize_hex_color( $value ) ? sanitize_hex_color( $value ) : '';
		}
		if ( 'font_size' === $key ) {
			return absint( $value );
		}
		if ( 'table_style' === $key ) {
			return 'classicstyle' === $this->normalize_key( $value ) || 'classic_style' === $value ? 'classic_style' : 'table_style';
		}
		if ( 'price_display' === $key ) {
			return 'discount' === $this->normalize_key( $value ) ? 'discount' : 'fixed';
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Parse status.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function parse_status( $value ) {
		return $this->parse_bool( $value ) || 'active' === strtolower( trim( $value ) ) ? 'active' : 'inactive';
	}

	/**
	 * Parse a boolean-ish value.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	protected function parse_bool( $value ) {
		return in_array( strtolower( trim( (string) $value ) ), array( '1', 'yes', 'true', 'active', 'enabled', 'enable', 'on' ), true );
	}

	/**
	 * Parse date.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function parse_date( $value ) {
		$value = trim( (string) $value );
		return preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])/', $value ) ? current( explode( ' ', $value ) ) : '';
	}

	/**
	 * Split escaped list values.
	 *
	 * @param string $value Raw list.
	 * @param string $separator Separator.
	 * @return array
	 */
	protected function split_list( $value, $separator = ';' ) {
		$value = str_replace( '\\,', '::comma::', (string) $value );
		$value = str_replace( '\\;', '::semicolon::', $value );
		$parts = explode( $separator, $value );
		$parts = array_map(
			function ( $part ) {
				return trim( str_replace( array( '::comma::', '::semicolon::' ), array( ',', ';' ), $part ) );
			},
			$parts
		);
		return array_filter( $parts, 'strlen' );
	}

	/**
	 * Split entity list values separated by semicolon or comma.
	 *
	 * @param string $value Raw list.
	 * @return array
	 */
	protected function split_multi_value_list( $value ) {
		$separator = false !== strpos( (string) $value, ';' ) ? ';' : ',';
		return $this->split_list( $value, $separator );
	}

	/**
	 * Extract an ID from common "123(Name)" or "product_123" forms.
	 *
	 * @param string $value Raw item value.
	 * @return string
	 */
	protected function extract_id( $value ) {
		$value = trim( (string) $value );
		if ( preg_match( '/(\d+)/', $value, $match ) ) {
			return $match[1];
		}
		return sanitize_text_field( preg_replace( '/\\(.*\\)/', '', $value ) );
	}

	/**
	 * Clean a CSV cell.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function clean_csv_cell( $value ) {
		return wc_clean( wp_unslash( (string) $value ) );
	}

	/**
	 * Normalize a text key for flexible matching.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function normalize_key( $value ) {
		return strtolower( preg_replace( '/[^a-z0-9]/', '', (string) $value ) );
	}

	/**
	 * Remove UTF-8 BOM from a header.
	 *
	 * @param string $value Header value.
	 * @return string
	 */
	protected function remove_utf8_bom( $value ) {
		if ( 'efbbbf' === substr( bin2hex( $value ), 0, 6 ) ) {
			return substr( $value, 3 );
		}
		return $value;
	}
}
