<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Preserve the existing loader path and public class name.
/**
 * Wholesale pricing CSV importer.
 *
 * Handles generic CSV uploads and imports. Extension providers supply
 * their own columns, mapped row handlers, and serializers.
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

		if ( isset( $_REQUEST['nonce'] ) && is_string( $_REQUEST['nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['nonce'] ) ), 'wholesalex-registration' ) ) {
			$this->file            = isset( $_REQUEST['file'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['file'] ) ) : '';
			$this->update_existing = isset( $_REQUEST['update_existing'] ) && 'yes' === sanitize_text_field( wp_unslash( $_REQUEST['update_existing'] ) );
		}
	}

	/**
	 * Validate current request.
	 *
	 * @return bool
	 */
	protected function is_request_allowed() {
		if ( ! isset( $_POST['nonce'] ) || ! is_string( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'wholesalex-registration' ) ) {
			return false;
		}
		$context = Wholesale_Pricing::get_manager_context();
		return ! $context['is_vendor'] && $context['can_manage'];
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
			wp_send_json_error(
				array(
					'status'  => false,
					'message' => __( 'Security check failed or you do not have permission to import wholesale pricing rules.', 'wholesalex' ),
				),
				403
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
		if ( is_wp_error( $rows ) ) {
			( new Dynamic_Rule_Import_Jobs() )->delete( $file );
			wp_send_json(
				array(
					'status'  => false,
					'message' => $rows->get_error_message(),
				),
				400
			);
		}
		$headers = isset( $rows['headers'] ) ? $rows['headers'] : array();

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
		if ( ! isset( $_POST['nonce'] ) || ! is_string( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'wholesalex-registration' ) ) {
			return new WP_Error( 'wholesalex_wholesale_pricing_import_nonce', __( 'Security check failed.', 'wholesalex' ) );
		}
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_FILES['import'] ) || ! is_array( $_FILES['import'] ) ) {
			return new WP_Error( 'invalid_upload', __( 'Please upload a CSV file.', 'wholesalex' ) );
		}
		$upload = $_FILES['import']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validate PHP upload identity, type and size before reading bytes.
		if ( ! isset( $upload['name'], $upload['tmp_name'], $upload['error'], $upload['size'] ) || ! is_string( $upload['name'] ) || ! is_string( $upload['tmp_name'] ) || UPLOAD_ERR_OK !== $upload['error'] || ! is_uploaded_file( $upload['tmp_name'] ) || ! $this->is_valid_csv( $upload['name'] ) || $upload['size'] > Dynamic_Rule_Import_Jobs::MAX_BYTES ) {
			return new WP_Error( 'invalid_upload', __( 'Please upload a valid CSV file of at most 5 MB.', 'wholesalex' ) );
		}
		$csv = file_get_contents( $upload['tmp_name'], false, null, 0, Dynamic_Rule_Import_Jobs::MAX_BYTES + 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read only the verified PHP upload with a strict byte limit.
		return ( new Dynamic_Rule_Import_Jobs() )->create( $csv, 'pricing' );
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
		return apply_filters(
			'wholesalex_wholesale_pricing_csv_columns',
			array(
				'schema_version'        => __( 'CSV Schema Version', 'wholesalex' ),
				'exclude_products'      => __( 'Excluded Products', 'wholesalex' ),
				'owner_id'              => __( 'Owner ID', 'wholesalex' ),
				'owner_type'            => __( 'Owner Type', 'wholesalex' ),
				'created_by'            => __( 'Created By', 'wholesalex' ),
				'cart_presentation'     => __( 'Cart Presentation Data', 'wholesalex' ),
				'bogo_presentation'     => __( 'BOGO Presentation Data', 'wholesalex' ),
				'id'                    => __( 'ID', 'wholesalex' ),
				'status'                => __( 'Status', 'wholesalex' ),
				'title'                 => __( 'Title', 'wholesalex' ),
				'rule_type'             => __( 'Rule Type', 'wholesalex' ),
				'product_filter'        => __( 'Product Filter', 'wholesalex' ),
				'products'              => __( 'Products', 'wholesalex' ),
				'categories'            => __( 'Categories', 'wholesalex' ),
				'user_role_filter'      => __( 'User Role Filter', 'wholesalex' ),
				'user_roles'            => __( 'User Roles', 'wholesalex' ),
				'specific_users'        => __( 'Specific Users', 'wholesalex' ),
				'discount_type'         => __( 'Discount Type', 'wholesalex' ),
				'regular_amount'        => __( 'Regular Discount Amount', 'wholesalex' ),
				'regular_amount_type'   => __( 'Regular Discount Amount Type', 'wholesalex' ),
				'regular_label'         => __( 'Regular Discount Label', 'wholesalex' ),
				'cart_discount_type'    => __( 'Cart Discount Type', 'wholesalex' ),
				'cart_discount_amount'  => __( 'Cart Discount Amount', 'wholesalex' ),
				'cart_discount_name'    => __( 'Cart Discount Name', 'wholesalex' ),
				'bogo_buy_x_qty'        => __( 'BOGO Buy Quantity', 'wholesalex' ),
				'bogo_heading_text'     => __( 'BOGO Heading Text', 'wholesalex' ),
				'bogo_per_cart_once'    => __( 'BOGO Once Per Cart', 'wholesalex' ),
				'bogo_show_badge'       => __( 'BOGO Show Badge', 'wholesalex' ),
				'bogo_badge_label'      => __( 'BOGO Badge Label', 'wholesalex' ),
				'bogo_badge_style'      => __( 'BOGO Badge Style', 'wholesalex' ),
				'bogo_badge_position'   => __( 'BOGO Badge Position', 'wholesalex' ),
				'bogo_badge_bg_color'   => __( 'BOGO Badge Background Color', 'wholesalex' ),
				'bogo_badge_text_color' => __( 'BOGO Badge Text Color', 'wholesalex' ),
				'conditions'            => __( 'Conditions Data', 'wholesalex' ),
				'start_date'            => __( 'Start Date', 'wholesalex' ),
				'end_date'              => __( 'End Date', 'wholesalex' ),
			)
		);
	}

	/**
	 * Export selected wholesale pricing rules as a CSV file.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! isset( $_GET['nonce'] ) || ! is_string( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['nonce'] ) ), 'whx-export-wholesale-pricing' ) ) {
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

		foreach ( $rules as $id => $rule ) {
			if ( ! $export_all && ! isset( $selected[ (string) $id ] ) ) {
				continue; }
			$check = Wholesale_Pricing::provider_operation( 'validate_export', (string) $id, array(), $rule );
			if ( is_wp_error( $check ) ) {
				wp_die( esc_html( $check->get_error_message() ), esc_html__( 'Pricing export unavailable', 'wholesalex' ), array( 'response' => 409 ) ); }
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wholesalex_wholesale_pricing.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- fputcsv needs a writable output stream for the CSV download.
		fputcsv( $output, array_values( $columns ) );

		foreach ( $rules as $rule ) {
			if ( ! $export_all && ! isset( $selected[ (string) $rule['id'] ] ) ) {
				continue;
			}
			fputcsv( $output, $this->generate_export_row( $rule, array_keys( $columns ) ) );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the CSV stream opened above.
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
			$value = (string) $this->get_export_column_value( $rule, $column_key );
			// A leading apostrophe is escaped too, making schema v2 decoding unambiguous.
			$row[] = preg_match( '/^[\x00-\x20]*[=+@\-]|^[\x00-\x20\']/', $value ) ? "'" . $value : $value;
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
		$value = apply_filters( 'wholesalex_wholesale_pricing_csv_value', null, $rule, $column_key );
		if ( null !== $value ) {
			return $value; }
		switch ( $column_key ) {
			case 'schema_version':
				return '2';
			case 'owner_id':
			case 'owner_type':
			case 'created_by':
				return isset( $rule[ $column_key ] ) ? (string) $rule[ $column_key ] : '';
			case 'exclude_products':
				return wp_json_encode( $rule['exclude_products'] ?? array() );
			case 'cart_presentation':
			case 'bogo_presentation':
				$bucket = 'cart_presentation' === $column_key ? 'cart' : 'bogo';
				$fields = self::presentation_fields( $bucket );
				return wp_json_encode( array_intersect_key( $rule[ $bucket ] ?? array(), array_flip( $fields ) ) );

			case 'bogo_heading_text':
				return 'bogo_discount' === ( $rule['rule_type'] ?? '' )
					? (string) ( $rule['bogo']['heading_text'] ?? __( 'Buy X Get 1 Discounted', 'wholesalex' ) )
					: '';

			case 'id':
			case 'status':
			case 'title':
			case 'rule_type':
			case 'product_filter':
			case 'user_role_filter':
			case 'discount_type':
				return isset( $rule[ $column_key ] ) ? (string) $rule[ $column_key ] : '';

			case 'products':
			case 'user_roles':
			case 'specific_users':
				return $this->serialize_select_items( isset( $rule[ $column_key ] ) ? $rule[ $column_key ] : array() );

			case 'categories':
				return $this->serialize_categories( isset( $rule['categories'] ) ? $rule['categories'] : array() );

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

		if ( 0 === strpos( $column_key, 'bogo_' ) ) {
			return $this->get_nested_export_value( $rule, 'bogo', substr( $column_key, strlen( 'bogo_' ) ) );
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
	 * Serialize category names and explicit IDs in a single CSV cell.
	 *
	 * @param array $items Selected categories.
	 * @return string
	 */
	protected function serialize_categories( $items ) {
		$categories = array();
		foreach ( (array) $items as $item ) {
			$id = absint( is_array( $item ) ? ( $item['value'] ?? 0 ) : $item );
			if ( ! $id ) {
				continue;
			}
			$name         = is_array( $item ) ? (string) ( $item['name'] ?? '' ) : '';
			$name         = html_entity_decode( $name, ENT_QUOTES, 'UTF-8' );
			$name         = str_replace( array( ',', ';' ), array( '\\,', '\\;' ), $name );
			$categories[] = trim( $name . ' (ID: ' . $id . ')' );
		}
		return implode( ', ', $categories );
	}

	/**
	 * Serialize SKUs as comma-separated values without a column prefix.
	 *
	 * @param array $items Selected SKUs.
	 * @return string
	 */


	/**
	 * Serialize advanced conditions.
	 *
	 * @param array $conditions Conditions.
	 * @return string
	 */
	protected function serialize_conditions( $conditions ) {
		return is_array( $conditions ) ? 'json:' . wp_json_encode( array( 'tiers' => $conditions ) ) : '';
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
			'id'                  => 'id',
			'status'              => 'status',
			'rulestatus'          => 'status',
			'title'               => 'title',
			'ruletitle'           => 'title',
			'type'                => 'rule_type',
			'ruletype'            => 'rule_type',
			'applicableon'        => 'product_filter',
			'productfilter'       => 'product_filter',
			'products'            => 'products',
			'productinlists'      => 'products',
			'categories'          => 'categories',
			'categoriesinlists'   => 'categories',
			'applicablefor'       => 'user_role_filter',
			'userrolefilter'      => 'user_role_filter',
			'applicableroles'     => 'user_roles',
			'userroles'           => 'user_roles',
			'applicableusers'     => 'specific_users',
			'specificusers'       => 'specific_users',
			'users'               => 'specific_users',
			'discounttype'        => 'discount_type',
			'productdiscountdata' => 'product_discount',
			'cartdiscountdata'    => 'cart_discount',
			'bogodiscountdata'    => 'buy_x_get_one',
			'buyxgetonedata'      => 'buy_x_get_one',
			'conditionsdata'      => 'conditions',
			'startdate'           => 'start_date',
			'enddate'             => 'end_date',
		);

		$defaults = apply_filters( 'wholesalex_pricing_csv_aliases', $defaults );
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
			$key            = $this->normalize_key( $header );
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
			wp_send_json_error(
				array(
					'status'  => false,
					'message' => __( 'Security check failed or you do not have permission to import wholesale pricing rules.', 'wholesalex' ),
				),
				403
			);
		}

		if ( ! isset( $_POST['nonce'] ) || ! is_string( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'wholesalex-registration' ) ) {
			wp_send_json_error(
				array(
					'status'  => false,
					'message' => __( 'Security check failed.', 'wholesalex' ),
				),
				403
			);
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$this->file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
		$rows       = $this->read_csv_rows( $this->file, 1 );
		if ( is_wp_error( $rows ) ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'The file does not exist, please try again.', 'wholesalex' ),
				)
			);
		}

		if ( empty( $_POST['map_from'] ) || empty( $_POST['map_to'] ) || ! is_array( $_POST['map_from'] ) || ! is_array( $_POST['map_to'] ) || count( $_POST['map_from'] ) !== count( $_POST['map_to'] ) ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'Please map at least one column before importing.', 'wholesalex' ),
				)
			);
		}

		$mapping_from = map_deep( wp_unslash( $_POST['map_from'] ), 'sanitize_text_field' );
		$mapping_to   = map_deep( wp_unslash( $_POST['map_to'] ), 'sanitize_text_field' );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! $this->valid_mapping( $mapping_from, $mapping_to ) ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'Invalid CSV column mapping.', 'wholesalex' ),
				),
				400
			);
		}

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
			wp_send_json_error(
				array(
					'status'  => false,
					'message' => __( 'Security check failed or you do not have permission to import wholesale pricing rules.', 'wholesalex' ),
				),
				403
			);
		}

		if ( ! isset( $_POST['nonce'] ) || ! is_string( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'wholesalex-registration' ) ) {
			wp_send_json_error(
				array(
					'status'  => false,
					'message' => __( 'Security check failed.', 'wholesalex' ),
				),
				403
			);
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified above.
		$file = isset( $_POST['file'] ) && is_string( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
		$rows = $this->read_csv_rows( $file );
		if ( is_wp_error( $rows ) ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'Invalid import file.', 'wholesalex' ),
				)
			);
		}

		$mapping = isset( $_POST['mapping'] ) ? (array) map_deep( wp_unslash( $_POST['mapping'] ), 'sanitize_text_field' ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$from = isset( $mapping['from'] ) && is_array( $mapping['from'] ) ? array_values( $mapping['from'] ) : array();
		$to   = isset( $mapping['to'] ) && is_array( $mapping['to'] ) ? array_values( $mapping['to'] ) : array();

		$results = $this->import_rows( $rows, $from, $to );
		( new Dynamic_Rule_Import_Jobs() )->delete( $file );
		$errors = array();

		foreach ( array_merge( $results['failed'], $results['skipped'] ) as $error ) {
			if ( is_wp_error( $error ) ) {
				$error_data = $error->get_error_data();
				$errors[]   = array(
					'id'      => isset( $error_data['id'] ) ? esc_html( $error_data['id'] ) : ( isset( $error_data['rule_id'] ) ? esc_html( $error_data['rule_id'] ) : '' ),
					'message' => wp_kses_post( $error->get_error_message() ),
				);
			}
		}

		wp_send_json(
			array(
				'status'        => empty( $results['aborted'] ),
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
		$handle  = ( new Dynamic_Rule_Import_Jobs() )->open( $file, 'pricing' );

		if ( is_wp_error( $handle ) ) {
			return $handle;
		}

		$headers = fgetcsv( $handle, 0, ',', '"', "\0" );
		$headers = is_array( $headers ) ? array_map( array( $this, 'clean_csv_cell' ), $headers ) : array();

		if ( ! count( array_filter( $headers ) ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Release the private memory stream.
			return new WP_Error( 'invalid_csv', __( 'CSV header is missing.', 'wholesalex' ) );
		}

		if ( isset( $headers[0] ) ) {
			$headers[0] = $this->remove_utf8_bom( $headers[0] );
		}

		while ( false !== ( $row = fgetcsv( $handle, 0, ',', '"', "\0" ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Read each CSV row once and stop at end of file.
			if ( ! count( array_filter( $row ) ) ) {
				continue;
			}
			if ( count( $headers ) !== count( $row ) ) {
				fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Release the private memory stream.
				return new WP_Error( 'invalid_csv', __( 'CSV row does not match its header.', 'wholesalex' ) );
			}
			$rows[] = array_map( array( $this, 'clean_csv_cell' ), $row );
			if ( $limit > 0 && count( $rows ) >= $limit ) {
				break;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the CSV stream opened above.

		return array(
			'headers' => $headers,
			'rows'    => $rows,
		);
	}

	/**
	 * Require scalar, matching mapping arrays and known target fields.
	 *
	 * @param array $from Source headings.
	 * @param array $to Target fields.
	 * @return bool
	 */
	private function valid_mapping( $from, $to ) {
		if ( ! is_array( $from ) || ! is_array( $to ) || count( $from ) !== count( $to ) || empty( $to ) ) {
			return false;
		}
		$allowed = array_keys( $this->get_mapping_options() );
		foreach ( $from as $value ) {
			if ( ! is_string( $value ) ) {
				return false;
			}
		}
		foreach ( $to as $value ) {
			if ( ! is_string( $value ) || ( '' !== $value && ! in_array( $value, $allowed, true ) ) ) {
				return false;
			}
		}
		return true;
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
			'aborted'  => false,
		);

		if ( is_wp_error( $csv ) ) {
			$results['aborted']  = true;
			$results['failed'][] = $csv;
			return $results;
		}

		if ( ! $this->valid_mapping( $from, $to ) ) {
			$results['aborted']  = true;
			$results['failed'][] = new WP_Error( 'invalid_csv_mapping', __( 'Invalid CSV column mapping.', 'wholesalex' ) );
			return $results;
		}

		$headers = isset( $csv['headers'] ) ? $csv['headers'] : array();
		$rows    = isset( $csv['rows'] ) ? $csv['rows'] : array();

		$map = array();
		foreach ( $headers as $idx => $header ) {
			$mapped_to = isset( $to[ $idx ] ) ? sanitize_key( $to[ $idx ] ) : '';
			if ( '' !== $mapped_to ) {
				$map[ $idx ] = $mapped_to;
			}
		}

		// Validate the entire requested batch before the first storage write.
		foreach ( $rows as $row_index => $row ) {
			$raw = array();
			foreach ( $map as $idx => $field ) {
				$raw[ $field ] = isset( $row[ $idx ] ) ? $row[ $idx ] : '';
			}
			$check = $this->process_row( $raw, $row_index + 2, true );
			if ( is_wp_error( $check ) ) {
				$results['aborted']  = true;
				$results['failed'][] = $check;
				return $results;
			}
		}

		foreach ( $rows as $row_index => $row ) {
			$raw = array();
			foreach ( $map as $idx => $field ) {
				$raw[ $field ] = isset( $row[ $idx ] ) ? $row[ $idx ] : '';
			}

			$result = $this->process_row( $raw, $row_index + 2 );
			if ( is_wp_error( $result ) ) {
				$results['aborted']  = true;
				$results['failed'][] = $result;
				return $results;
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
	 * @param bool  $dry_run Whether to validate without persisting changes.
	 * @return array|WP_Error
	 */
	protected function process_row( $raw, $line, $dry_run = false ) {
		$id       = isset( $raw['id'] ) ? sanitize_text_field( $raw['id'] ) : '';
		$existing = '' !== $id ? Wholesale_Pricing::get_rule( $id ) : null;
		$provided = apply_filters( 'wholesalex_wholesale_pricing_csv_import', null, $raw, $line, $dry_run, $this, $existing );
		if ( null !== $provided ) {
			return $provided; }
		if ( ! empty( $raw['tiers'] ) || 'tiered' === ( $raw['discount_type'] ?? '' ) || in_array( $this->normalize_key( $raw['rule_type'] ?? '' ), array( 'tierpricing', 'quantitybaseddiscount', 'tierpricingquantitybaseddiscount', 'tiered' ), true ) || ( is_array( $existing ) && Wholesale_Pricing::has_provider_record( $existing ) ) ) {
			return new WP_Error( 'wholesalex_provider_unavailable', __( 'The provider for this pricing import is unavailable.', 'wholesalex' ) );
		}

		$known = array_merge( array_keys( $this->get_mapping_options() ), array( 'tiers' ) );
		foreach ( $raw as $key => $value ) {
			if ( '' !== $value && ! in_array( $key, $known, true ) ) {
				return new WP_Error( 'wholesalex_provider_unavailable', __( 'The provider for this pricing import is unavailable.', 'wholesalex' ) );
			}
		}

		$rule_type = $this->parse_rule_type( isset( $raw['rule_type'] ) ? $raw['rule_type'] : '' );

		if ( empty( $rule_type ) ) {
			foreach ( array( 'product_discount', 'cart_discount', 'buy_x_get_one' ) as $legacy_key ) {
				if ( ! empty( $raw[ $legacy_key ] ) ) {
					$rule_type = $this->legacy_rule_type_from_key( $legacy_key );
					break;
				}
			}
		}

		$rule_type = apply_filters( 'wholesalex_pricing_csv_legacy_type', $rule_type, $raw );
		if ( empty( $rule_type ) ) {
			return new WP_Error(
				'wholesalex_wholesale_pricing_import_invalid_rule_type',
				__( 'Only Wholesale Pricing, Tier Pricing/ Quantity Based Discount, Product Discount, Cart Discount, BOGO Discount, and Buy X Get Y rules can be imported.', 'wholesalex' ),
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

		$base     = $exists ? Wholesale_Pricing::get_rule( $id ) : $this->get_default_rule( $id, $rule_type );
		$settings = $this->validate_import_settings( $raw, $base );
		if ( is_wp_error( $settings ) ) {
			return $settings;
		}

		$rule = $this->merge_row_into_rule( $base, $raw, $rule_type );
		if ( '' === ( $rule['product_filter'] ?? '' ) ) {
			return new WP_Error( 'wholesalex_provider_unavailable', __( 'The provider for this pricing import is unavailable.', 'wholesalex' ) );
		}
		$rule['id']        = $id;
		$rule['rule_type'] = $rule_type;
		$updating          = $exists;

		$input_validation = ( new Wholesale_Pricing_Rest_Api( false ) )->validate_input( $rule, $exists ? $base : array() );
		if ( is_wp_error( $input_validation ) ) {
			return $input_validation;
		}

		$validation = Wholesale_Pricing::validate_provider_write( $rule, $exists ? $base : null );
		if ( is_wp_error( $validation ) ) {
			return $validation; }

		if ( ! $dry_run ) {
			$saved = Wholesale_Pricing::save_rule( $id, $rule );
			if ( is_wp_error( $saved ) ) {
				return $saved; }
		}

		return array(
			'id'      => $id,
			'updated' => $updating,
		);
	}

	/**
	 * Shared strict calendar-date schedule validation for CSV and REST.
	 *
	 * @param array $schedule Schedule to validate.
	 * @return true|WP_Error
	 */
	public static function validate_schedule( $schedule ) {
		if ( ! is_array( $schedule ) ) {
			return new WP_Error( 'invalid_schedule', __( 'Invalid rule schedule.', 'wholesalex' ), array( 'status' => 400 ) );
		}
		foreach ( array( 'start_date', 'end_date' ) as $key ) {
			$value = $schedule[ $key ] ?? '';
			if ( '' === $value ) {
				continue;
			}
			if ( ! is_string( $value ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
				return new WP_Error( 'invalid_schedule', __( 'Schedule dates must be valid YYYY-MM-DD dates.', 'wholesalex' ), array( 'status' => 400 ) );
			}
		}
		if ( ! empty( $schedule['start_date'] ) && ! empty( $schedule['end_date'] ) && $schedule['start_date'] > $schedule['end_date'] ) {
			return new WP_Error( 'invalid_schedule', __( 'The schedule end date must not precede its start date.', 'wholesalex' ), array( 'status' => 400 ) );
		}
		return true;
	}

	/**
	 * Presentation fields supported by the Free CSV schema.
	 *
	 * @param string $bucket Rule bucket.
	 * @return array
	 */
	private static function presentation_fields( $bucket ) {
		return 'cart' === $bucket ? array( 'show_cart_discount_conditions', 'show_label_before_promo', 'label_text', 'promo_desc_text', 'condition_texts' ) : array( 'show_promo_text', 'offer_text', 'promo_text_popup', 'promo_text_cart' );
	}

	/**
	 * Reject invalid scopes and schedules before any row is persisted.
	 *
	 * @param array $raw Mapped cells.
	 * @param array $base Existing rule.
	 * @return true|WP_Error
	 */
	private function validate_import_settings( $raw, $base ) {
		if ( isset( $raw['schema_version'] ) && '' !== $raw['schema_version'] && ! in_array( $raw['schema_version'], array( '1', '2' ), true ) ) {
			return new WP_Error( 'invalid_csv_schema', __( 'Unsupported CSV schema version.', 'wholesalex' ) );
		}
		foreach ( array( 'exclude_products', 'cart_presentation', 'bogo_presentation' ) as $field ) {
			if ( ! empty( $raw[ $field ] ) && ! is_array( json_decode( $raw[ $field ], true ) ) ) {
				return new WP_Error( 'invalid_csv_data', __( 'Invalid structured CSV data.', 'wholesalex' ) );
			}
		}
		$owner = isset( $raw['owner_id'] ) && '' !== $raw['owner_id'] ? $raw['owner_id'] : ( $base['owner_id'] ?? 0 );
		$type  = isset( $raw['owner_type'] ) && '' !== $raw['owner_type'] ? $raw['owner_type'] : ( $base['owner_type'] ?? 'admin' );
		if ( '' !== (string) $owner && ( ! ctype_digit( (string) $owner ) || ( (int) $owner > 0 && ( ! current_user_can( 'manage_options' ) || ! get_userdata( (int) $owner ) || '' === $type || 'admin' === $type ) ) || ( 0 === (int) $owner && ! in_array( $type, array( '', 'admin' ), true ) ) ) ) {
			return new WP_Error( 'invalid_csv_owner', __( 'The CSV owner is invalid. Rebind the rule to an existing vendor before importing.', 'wholesalex' ) );
		}
		$schedule = is_array( $base['schedule'] ?? null ) ? $base['schedule'] : array();
		foreach ( array( 'start_date', 'end_date' ) as $key ) {
			if ( isset( $raw[ $key ] ) && '' !== $raw[ $key ] ) {
				$schedule[ $key ] = $raw[ $key ];
			}
		}
		return self::validate_schedule( $schedule );
	}

	/**
	 * Merge mapped CSV data into a rule.
	 *
	 * @param array  $rule      Existing/default rule.
	 * @param array  $raw       Raw mapped row.
	 * @param string $rule_type Rule type.
	 * @return array
	 */
	public function merge_row_into_rule( $rule, $raw, $rule_type ) {
		if ( '2' === ( $raw['schema_version'] ?? '' ) ) {
			foreach ( $raw as $key => $value ) {
				if ( is_string( $value ) && 0 === strpos( $value, "'" ) ) {
					$raw[ $key ] = substr( $value, 1 );
				}
			}
		}
		foreach ( array( 'owner_id', 'created_by' ) as $key ) {
			if ( isset( $raw[ $key ] ) && '' !== $raw[ $key ] ) {
				$rule[ $key ] = absint( $raw[ $key ] );
			}
		}
		if ( ! empty( $raw['owner_type'] ) ) {
			$rule['owner_type'] = sanitize_key( $raw['owner_type'] );
		}
		if ( isset( $raw['exclude_products'] ) && '' !== $raw['exclude_products'] ) {
			$rule['exclude_products'] = json_decode( $raw['exclude_products'], true );
		}
		foreach ( array( 'cart', 'bogo' ) as $bucket ) {
			if ( ! empty( $raw[ $bucket . '_presentation' ] ) ) {
				$presentation = json_decode( $raw[ $bucket . '_presentation' ], true );
				foreach ( array_intersect_key( $presentation, array_flip( self::presentation_fields( $bucket ) ) ) as $key => $value ) {
					$rule[ $bucket ][ $key ] = is_array( $value ) ? map_deep( $value, 'sanitize_text_field' ) : ( is_bool( $value ) ? $value : sanitize_text_field( $value ) );
				}
			}
		}

		if ( isset( $raw['title'] ) && '' !== $raw['title'] ) {
			$rule['title'] = sanitize_text_field( $raw['title'] );
		}
		if ( isset( $raw['status'] ) && '' !== $raw['status'] ) {
			$rule['status'] = $this->parse_status( $raw['status'] );
		}

		$rule = $this->merge_targeting( $rule, $raw );
		$rule = $this->merge_schedule_and_conditions( $rule, $raw );

		if ( in_array( $rule_type, array( 'wholesale_pricing' ), true ) ) {
			$rule = $this->merge_pricing_rule( $rule, $raw );

		} elseif ( 'product_discount' === $rule_type ) {
			$rule = $this->merge_product_discount_rule( $rule, $raw );
		} elseif ( 'cart_discount' === $rule_type ) {
			$rule = $this->merge_cart_rule( $rule, $raw );
		} elseif ( 'bogo_discount' === $rule_type ) {
			$rule = $this->merge_bogo_rule( $rule, $raw );
		}

		return apply_filters( 'wholesalex_pricing_csv_merge', $rule, $raw, $rule_type );
	}

	/**
	 * Merge product and role targeting.
	 *
	 * @param array $rule Rule configuration.
	 * @param array $raw Raw row.
	 * @return array
	 */
	protected function merge_targeting( $rule, $raw ) {
		if ( isset( $raw['product_filter'] ) && '' !== $raw['product_filter'] ) {
			$rule['product_filter'] = $this->parse_product_filter( $raw['product_filter'] );
		}

		foreach ( array( 'products', 'categories' ) as $field ) {
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
	 * @param array $rule Rule configuration.
	 * @param array $raw Raw row.
	 * @return array
	 */
	/**
	 * Merge schedule and condition data.
	 *
	 * @param array $rule Rule configuration.
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
	 * @param array $rule Rule configuration.
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
			$discount_type         = $this->normalize_key( $raw['discount_type'] );
			$rule['discount_type'] = 'regular';
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

		return $rule;
	}

	/**
	 * Merge product discount fields without importing any restrictions.
	 *
	 * @param array $rule Rule configuration.
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

		return $rule;
	}

	/**
	 * Merge cart discount fields.
	 *
	 * @param array $rule Rule configuration.
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
	 * @param array $rule Rule configuration.
	 * @param array $raw Raw row.
	 * @return array
	 */
	protected function merge_bogo_rule( $rule, $raw ) {
		// A supplied blank heading is intentional; an absent column preserves the current value.
		if ( isset( $raw['bogo_heading_text'] ) ) {
			$rule['bogo']['heading_text'] = sanitize_text_field( $raw['bogo_heading_text'] );
		}

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
	 * @param array $rule Rule configuration.
	 * @param array $raw Raw row.
	 * @return array
	 */
	/**
	 * Merge badge fields for BOGO/BXGY.
	 *
	 * @param array  $rule Rule configuration.
	 * @param array  $raw Raw row.
	 * @param string $bucket Rule data bucket.
	 * @return array
	 */
	protected function merge_badge_fields( $rule, $raw, $bucket ) {
		$prefix = $bucket . '_';
		$map    = array(
			'show_badge'       => 'show_badge',
			'badge_label'      => 'badge_label',
			'badge_style'      => 'badge_style',
			'badge_position'   => 'badge_position',
			'badge_bg_color'   => 'badge_bg_color',
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
	public function get_default_rule( $id, $rule_type ) {
		$rule = array(
			'id'               => $id,
			'title'            => '',
			'status'           => 'inactive',
			'rule_type'        => $rule_type,
			'product_filter'   => 'all_products',
			'products'         => array(),
			'categories'       => array(),
			'user_role_filter' => 'all_b2b',
			'user_roles'       => array(),
			'specific_users'   => array(),
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
			$rule['product_discount'] = array(
				'amount'      => '',
				'amount_type' => 'percentage',
				'label'       => __( 'Product discount', 'wholesalex' ),
			);
		} elseif ( 'cart_discount' === $rule_type ) {
			$rule['cart'] = array(
				'discount_type'                 => 'percentage',
				'discount_amount'               => '',
				'discount_name'                 => '',
				'show_cart_discount_conditions' => true,
				'show_label_before_promo'       => false,
				'label_text'                    => 'Cart Discount',
				'promo_desc_text'               => 'After adding to the cart',
				'condition_texts'               => $this->get_default_condition_texts(),
			);
		} elseif ( 'bogo_discount' === $rule_type ) {
			$rule['bogo'] = array(
				'buy_x_qty'        => '',
				'heading_text'     => __( 'Buy X Get 1 Discounted', 'wholesalex' ),
				'per_cart_once'    => false,
				'show_promo_text'  => true,
				'offer_text'       => '100% Off on 1 Product',
				'promo_text_popup' => 'Buy at least {required_quantity} products',
				'promo_text_cart'  => '{product_title} (Buy X Get 1 Discounted)',
				'show_badge'       => false,
				'badge_label'      => 'Buy X Get 1 Discounted',
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

		}

		return apply_filters( 'wholesalex_pricing_csv_defaults', $rule, $rule_type );
	}

	/**
	 * Default condition text values.
	 *
	 * @return array
	 */
	protected function get_default_condition_texts() {
		return array(
			'cart_total_qty_less_conditions_text'          => __( 'Keep your items below {max_value} to qualify.', 'wholesalex' ),
			'cart_total_qty_less_equal_conditions_text'    => __( 'Keep your items at {max_value} or less to qualify.', 'wholesalex' ),
			'cart_total_qty_greater_conditions_text'       => __( 'Add more than {min_value} items to qualify.', 'wholesalex' ),
			'cart_total_qty_greater_equal_conditions_text' => __( 'Add {min_value} or more items to qualify.', 'wholesalex' ),
			'cart_total_qty_greater_less_conditions_text'  => __( 'Add more than {min_value} but less than {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_qty_greater_equal_less_equal_conditions_text' => __( 'Add {min_value} to {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_qty_greater_less_equal_conditions_text' => __( 'Add more than {min_value} to {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_qty_greater_equal_less_conditions_text' => __( 'Add {min_value} to less than {max_value} items to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_less_conditions_text'       => __( 'Keep your weight below {max_value} to qualify.', 'wholesalex' ),
			'cart_total_weight_less_equal_conditions_text' => __( 'Keep your total weight at {max_value} or less to qualify.', 'wholesalex' ),
			'cart_total_weight_greater_conditions_text'    => __( 'Add more than {min_value} in weight to qualify.', 'wholesalex' ),
			'cart_total_weight_greater_equal_conditions_text' => __( 'Add {min_value} or more in weight to qualify.', 'wholesalex' ),
			'cart_total_weight_greater_less_conditions_text' => __( 'Add more than {min_value} but less than {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_greater_equal_less_equal_conditions_text' => __( 'Add {min_value} to {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_greater_less_equal_conditions_text' => __( 'Add more than {min_value} to {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_weight_greater_equal_less_conditions_text' => __( 'Add {min_value} to less than {max_value} in weight to unlock this offer.', 'wholesalex' ),
			'cart_total_value_less_conditions_text'        => __( 'Keep your spend below ${max_value} to qualify.', 'wholesalex' ),
			'cart_total_value_less_equal_conditions_text'  => __( 'Keep your spend at ${max_value} or less to qualify.', 'wholesalex' ),
			'cart_total_value_greater_conditions_text'     => __( 'Spend more than ${min_value} to qualify.', 'wholesalex' ),
			'cart_total_value_greater_equal_conditions_text' => __( 'Spend ${min_value} or more to qualify.', 'wholesalex' ),
			'cart_total_value_greater_less_conditions_text' => __( 'Spend more than ${min_value} but less than ${max_value} to unlock this offer.', 'wholesalex' ),
			'cart_total_value_greater_equal_less_equal_conditions_text' => __( 'Spend between ${min_value} and ${max_value} to unlock this offer.', 'wholesalex' ),
			'cart_total_value_greater_less_equal_conditions_text' => __( 'Spend more than ${min_value} and up to ${max_value} to unlock this offer.', 'wholesalex' ),
			'cart_total_value_greater_equal_less_conditions_text' => __( 'Spend ${min_value} or more but less than ${max_value} to unlock this offer.', 'wholesalex' ),
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
			'productdiscount'  => 'product_discount',
			'regular'          => 'wholesale_pricing',
			'cartdiscount'     => 'cart_discount',
			'bogo'             => 'bogo_discount',
			'bogodiscount'     => 'bogo_discount',
			'buyxgetone'       => 'bogo_discount',
			'buyxgetonefree'   => 'bogo_discount',
		);

		if ( in_array( $value, array( 'wholesale_pricing', 'product_discount', 'cart_discount', 'bogo_discount' ), true ) ) {
			return $value;
		}

		return apply_filters( 'wholesalex_pricing_csv_rule_type', isset( $map[ $normalized ] ) ? $map[ $normalized ] : '', $value );
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
		$normalized = $this->normalize_key( strtolower( (string) $value ) );
		$map        = array(
			'allproducts'        => 'all_products',
			'specificproducts'   => 'specific_products',
			'specificvariations' => 'specific_variations',
			'products'           => 'specific_products',
			'productsinlist'     => 'specific_products',
			'productinlist'      => 'specific_products',
			'specificcategories' => 'specific_categories',
			'categories'         => 'specific_categories',
			'catinlist'          => 'specific_categories',
			'categoriesinlist'   => 'specific_categories',
		);

		if ( in_array( $value, array( 'all_products', 'specific_products', 'specific_variations', 'specific_categories' ), true ) ) {
			return $value;
		}

		return apply_filters( 'wholesalex_pricing_csv_product_filter', isset( $map[ $normalized ] ) ? $map[ $normalized ] : '', $value );
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
			'applicableusers'            => 'specific_users',
			'specificroles'              => 'specific_roles',
			'specificrole'               => 'specific_roles',
			'applicableroles'            => 'specific_roles',
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
		$provided = apply_filters( 'wholesalex_pricing_csv_selection', null, $value, $type );
		if ( null !== $provided ) {
			return $provided; }
		$out           = array();
		$category_list = 'categories' === $type && ( preg_match( '/^categories:\s*/i', trim( (string) $value ) ) || preg_match( '/\(ID:\s*\d+\)$/i', trim( (string) $value ) ) );
		if ( $category_list ) {
			$value = preg_replace( '/^categories:\s*/i', '', trim( (string) $value ), 1 );
			$items = $this->split_list( $value, ',' );
		} else {
			$items = $this->split_multi_value_list( $value );
		}
		foreach ( $items as $item ) {
			if ( $category_list ) {
				if ( ! preg_match( '/\(ID:\s*(\d+)\)$/i', $item, $match ) ) {
					continue;
				}
				$item_id = $match[1];
			} else {
				$item_id = $this->extract_id( $item );
			}
			$label = '';

			if ( 'products' === $type ) {
				$product = wc_get_product( $item_id );
				$label   = $product ? $product->get_title() : '';
				if ( $product && $product->is_type( 'variation' ) ) {
					$label = '#' . $product->get_id() . ' ' . $product->get_name();
				}
			} elseif ( 'categories' === $type ) {
				$term  = get_term_by( 'id', $item_id, 'product_cat' );
				$label = $term ? $term->name : '';
			} else {
				$label = sanitize_text_field( preg_replace( '/\\(.*\\)/', '', $item ) );
			}

			if ( '' !== $label ) {
				$out[] = array(
					'name'  => esc_attr( $label ),
					'value' => absint( $item_id ),
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
	public function parse_key_value_list( $value ) {
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
	 * Parse conditions into rule shape.
	 *
	 * @param string $value Raw value.
	 * @return array
	 */
	protected function parse_conditions( $value ) {
		if ( 0 === strpos( $value, 'json:' ) ) {
			$decoded = json_decode( substr( $value, 5 ), true );
			return is_array( $decoded ) && isset( $decoded['tiers'] ) && is_array( $decoded['tiers'] ) ? $decoded : new WP_Error( 'invalid_csv_conditions', __( 'Invalid CSV conditions.', 'wholesalex' ) );
		}
		$tiers = array();
		foreach ( $this->split_list( $value, ';' ) as $condition_data ) {
			$data     = $this->parse_key_value_list( str_replace( ',', ';', $condition_data ) );
			$field    = isset( $data['_conditions_for'] ) ? $data['_conditions_for'] : ( isset( $data['field'] ) ? $data['field'] : '' );
			$operator = isset( $data['_conditions_operator'] ) ? $data['_conditions_operator'] : ( isset( $data['operator'] ) ? $data['operator'] : '' );
			$amount   = isset( $data['_conditions_value'] ) ? $data['_conditions_value'] : ( isset( $data['value'] ) ? $data['value'] : '' );
			if ( '' === $field || '' === $operator || '' === $amount ) {
				return new WP_Error( 'invalid_csv_conditions', __( 'Invalid CSV conditions.', 'wholesalex' ) );
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
	public function parse_discount_amount_type( $value ) {
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
	public function parse_bool( $value ) {
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
	public function split_list( $value, $separator = ';' ) {
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
		// CSV stream bytes are not slashed request data. Individual field parsers sanitize them.
		return trim( (string) $value );
	}

	/**
	 * Normalize a text key for flexible matching.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function normalize_key( $value ) {
		return preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $value ) );
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
	/** Whether the generic import is in update mode. */
	public function updates_existing(): bool {
		return (bool) $this->update_existing; }
}
