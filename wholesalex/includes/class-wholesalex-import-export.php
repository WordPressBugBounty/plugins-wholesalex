<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Preserve the established import-export include path.
/**
 * Import Export Handler
 *
 * @package WholesaleX
 * @since 1.1.6
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * WholesaleX Import Export Class
 */
class ImportExport {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_wholesalex_export_users_columns', array( $this, 'export_users_columns' ) );
		add_action( 'wp_ajax_wholesalex_export_users', array( $this, 'export_users' ) );
		add_action( 'wp_ajax_wholesalex_import_users', array( $this, 'import_users' ) );
		add_action( 'wp_ajax_wholesalex_process_import_users', array( $this, 'wholesalex_process_import_users' ) );
		add_action( 'wp_ajax_wholesalex_cancel_import_users', array( $this, 'cancel_customer_import' ) );
		add_action( Dynamic_Rule_Import_Jobs::EXPIRY_HOOK, array( Dynamic_Rule_Import_Jobs::class, 'expire' ), 10, 2 );
		add_action( Customer_Import_Jobs::EXPIRY_HOOK, array( Customer_Import_Jobs::class, 'expire' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'cleanup_customer_imports' ) );

		add_action( 'admin_init', array( $this, 'export_dynamic_rules' ) );
		add_action( 'admin_init', array( $this, 'export_roles' ) );
		add_action( 'admin_init', array( $this, 'roles_importer' ) );
		add_action( 'admin_init', array( $this, 'dynamic_rules_importer' ) );
	}

	/**
	 * Get Class Instance
	 *
	 * @return ImportExport
	 */
	public static function get_instance() {
		$instance = new self();
		return $instance;
	}

	/**
	 * Get Default Exportable Users Columns
	 *
	 * @return array
	 */
	public static function exportable_user_columns() {
		return self::get_instance()->get_default_columns();
	}

	/**
	 * Get Exportable Columns on JSON Format
	 *
	 * @return void
	 */
	public function export_users_columns() {
		if ( $this->export_import_allowed() && true === User_Operation_Policy::authorize( 'read', get_current_user_id() ) ) {
			wp_send_json_success( $this->get_default_columns() );
		}
	}

	/**
	 * Return true if WholesaleX Import and  export is allowed for current user, false otherwise.
	 *
	 * @return bool Whether current user can perform export.
	 */
	protected function export_import_allowed() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get Default User Columns List
	 *
	 * @since 1.1.6
	 */
	public function get_default_columns() {

		$columns = apply_filters(
			'wholesalex_user_export_default_columns',
			array(
				'user_id'                   => 'User ID',
				'username'                  => 'Username',
				'first_name'                => 'First Name',
				'last_name'                 => 'Last Name',
				'nickname'                  => 'Nickname',
				'display_name'              => 'Display Name',
				'email'                     => 'Email',
				'bio'                       => 'Biographical Info',
				'avatar'                    => 'Avatar',
				'password'                  => 'Password',
				'wholesalex_role'           => sprintf( '%s Role', wholesalex()->get_plugin_name() ),
				'wholesalex_account_status' => sprintf( '%s Account Status', wholesalex()->get_plugin_name() ),
				'billing_first_name'        => 'Billing: First Name',
				'billing_last_name'         => 'Billing: Last Name',
				'billing_company'           => 'Billing: Company',
				'billing_address_1'         => 'Billing: Address Line 1',
				'billing_city'              => 'Billing: City',
				'billing_postcode'          => 'Billing: Post Code/ZIP',
				'billing_country'           => 'Billing: Country/Region',
				'billing_state'             => 'Billing: State/County',
				'billing_phone'             => 'Billing: Phone',
				'shipping_first_name'       => 'Shipping: First Name',
				'shipping_last_name'        => 'Shipping: Last Name',
				'shipping_company'          => 'Shipping: Company',
				'shipping_address_1'        => 'Shipping: Address Line 1',
				'shipping_city'             => 'Shipping: City',
				'shipping_postcode'         => 'Shipping: Post Code/ZIP',
				'shipping_country'          => 'Shipping: Country/Region',
				'shipping_state'            => 'Shipping: State/County',
				'shipping_phone'            => 'Shipping: Phone',
			)
		);

		return $columns;
	}

	/**
	 * Get Query Users For Specific Export
	 *
	 * @param integer $user_per_page User Per Page.
	 * @param integer $page Page.
	 * @param string  $status Account Status.
	 * @param string  $search_query Search Query.
	 * @param string  $role Role.
	 * @param string  $user_ids User IDs.
	 * @return array
	 */
	public function get_wholesale_users( $user_per_page = -1, $page = 1, $status = '', $search_query = '', $role = '', $user_ids = '' ) {
		if ( true !== User_Operation_Policy::authorize( 'read', get_current_user_id() ) ) {
			return array();
		}
		$user_fields = array( 'ID', 'user_login', 'display_name', 'user_email', 'user_registered' );
		$meta_query  = array(
			'relation' => 'OR',
			array(
				'key'     => '__wholesalex_status',
				'value'   => '',
				'compare' => '!=',
			),
			array(
				'key'     => '__wholesalex_role',
				'value'   => '',
				'compare' => '!=',
			),
		);

		if ( '' !== $status && '' !== $role ) {
			$meta_query = array(
				'relation' => 'AND',
				array(
					'key'     => '__wholesalex_status',
					'value'   => $status,
					'compare' => '=',
				),
				array(
					'key'     => '__wholesalex_role',
					'value'   => $role,
					'compare' => '=',
				),
			);
		}

		if ( '' !== $status && '' === $role ) {
			$meta_query = array(
				array(
					'key'     => '__wholesalex_status',
					'value'   => $status,
					'compare' => '=',
				),
			);
		}
		if ( '' !== $role && '' === $status ) {
			$meta_query = array(
				array(
					'key'     => '__wholesalex_role',
					'value'   => $role,
					'compare' => '=',
				),
			);
		}

		$args = array(
			'meta_query' => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Export filtering depends on WholesaleX role and status metadata.
			'orderby'    => 'registered',
			'order'      => 'DESC',
			'number'     => $user_per_page,
			'paged'      => $page,
			'fields'     => 'all',
			'search'     => '*' . $search_query . '*',
		);

		// If user IDs are passed, add 'include' to filter by those IDs.
		if ( ! empty( $user_ids ) ) {
			$user_ids_array  = explode( ',', $user_ids );
			$user_ids_array  = array_map( 'trim', $user_ids_array );
			$args['include'] = $user_ids_array;
		}

		$args['blog_id'] = get_current_blog_id();
		$users           = get_users( $args );
		return array_values(
			array_filter(
				$users,
				static function ( $user ) {
					return true === User_Operation_Policy::authorize( 'read', get_current_user_id(), $user->ID );
				}
			)
		);
	}

	/**
	 * Export Users
	 *
	 * @return void
	 */
	public function export_users() {
		$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wholesalex-registration' ) ) {
			return;
		}
		if ( ! $this->export_import_allowed() || true !== User_Operation_Policy::authorize( 'read', get_current_user_id() ) ) {
			return;
		}
		$user_status       = isset( $_POST['getFilterStatus'] ) ? sanitize_text_field( wp_unslash( $_POST['getFilterStatus'] ) ) : '';
		$search_query      = isset( $_POST['getSearchValue'] ) ? sanitize_text_field( wp_unslash( $_POST['getSearchValue'] ) ) : '';
		$user_role         = isset( $_POST['getFilterRole'] ) ? sanitize_text_field( wp_unslash( $_POST['getFilterRole'] ) ) : '';
		$selected_user_ids = isset( $_POST['getSelectedUserIds'] ) ? sanitize_text_field( wp_unslash( $_POST['getSelectedUserIds'] ) ) : array();
		$export_all        = isset( $_POST['exportAll'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['exportAll'] ) );
		if ( $export_all ) {
			$selected_user_ids = '';
		}
		$selected_user_data = $this->get_wholesale_users( -1, 1, $user_status, $search_query, $user_role, $selected_user_ids );
		$exportable_columns = isset( $_POST['columns'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['columns'] ) ), true ) : array();

		$data  = array();
		$users = $selected_user_data;
		foreach ( $users as $user ) {
			$user_data       = array();
			$user_data['id'] = $user->ID;
			foreach ( $exportable_columns as $column ) {
				switch ( $column ) {
					case 'user_id':
						$user_data[ $column ] = $user->ID;
						break;
					case 'username':
						$user_data[ $column ] = $user->user_login;
						break;
					case 'first_name':
						$user_data[ $column ] = $user->first_name;
						break;
					case 'last_name':
						$user_data[ $column ] = $user->last_name;
						break;
					case 'nickname':
						$user_data[ $column ] = $user->nickname;
						break;
					case 'display_name':
						$user_data[ $column ] = $user->display_name;
						break;
					case 'email':
						$user_data[ $column ] = $user->user_email;
						break;
					case 'bio':
						$user_data[ $column ] = $user->description;
						break;
					case 'avatar':
						$user_data[ $column ] = get_user_meta( $user->ID, 'avatar', true );
						break;
					case 'password':
						$user_data[ $column ] = '';
						break;
					case 'wholesalex_role':
						$user_data[ $column ] = get_user_meta( $user->ID, '__wholesalex_role', true );
						if ( is_numeric( $user_data[ $column ] ) ) {
							$user_data[ $column ] = 'whx_old:' . $user_data[ $column ];
						}
						break;
					case 'wholesalex_account_status':
						$user_data[ $column ] = get_user_meta( $user->ID, '__wholesalex_status', true );
						break;
					case 'billing_first_name':
					case 'billing_last_name':
					case 'billing_company':
					case 'billing_address_1':
					case 'billing_city':
					case 'billing_country':
					case 'billing_state':
					case 'billing_phone':
					case 'shipping_first_name':
					case 'shipping_last_name':
					case 'shipping_company':
					case 'shipping_address_1':
					case 'shipping_city':
					case 'shipping_country':
					case 'shipping_state':
					case 'shipping_phone':
						$user_data[ $column ] = get_user_meta( $user->ID, $column, true );
						break;
					case 'billing_postcode':
					case 'shipping_postcode':
						$value                = get_user_meta( $user->ID, $column, true );
						$user_data[ $column ] = '' !== $value ? $value : get_user_meta( $user->ID, str_replace( 'postcode', 'post_code', $column ), true );
						break;
				}
			}
			$user_data = apply_filters( 'wholesalex_user_export_column_data', $user_data );
			$data[]    = $user_data;
		}
		$rows = array( $exportable_columns );
		foreach ( $data as $row ) {
			$row_data = array();
			foreach ( $exportable_columns as $column ) {
				$row_data[] = isset( $row[ $column ] ) ? $row[ $column ] : '';
			}
			$rows[] = $row_data;
		}
		$csv_data = $this->serialize_customer_csv( $rows );
		if ( is_wp_error( $csv_data ) ) {
			wp_send_json_error( array( 'message' => __( 'The customer export could not be generated.', 'wholesalex' ) ), 500 );
			return;
		}
		wp_send_json_success( $csv_data );
	}

	/**
	 * Quote actual CSV records and neutralize spreadsheet formula prefixes.
	 *
	 * @param array $rows Headers and data rows in export order.
	 * @return string|\WP_Error
	 */
	protected function serialize_customer_csv( $rows ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- In-memory CSV serialization requires a stream.
		$stream = fopen( 'php://temp', 'w+' );
		if ( false === $stream ) {
			return new \WP_Error( 'wholesalex_csv_export_failed' );
		}
		try {
			foreach ( $rows as $row ) {
				$cells = array_map(
					static function ( $value ) {
						$value = is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value );
						if ( preg_match( '/^[\x00-\x20]*[=+\-@]|^[\t\r\n]/', $value ) ) {
							$value = "'" . $value;
						}
						return $value;
					},
					$row
				);
				if ( false === fputcsv( $stream, $cells, ',', '"', '' ) ) {
					return new \WP_Error( 'wholesalex_csv_export_failed' );
				}
			}
			rewind( $stream );
			$csv = stream_get_contents( $stream );
			return is_string( $csv ) ? $csv : new \WP_Error( 'wholesalex_csv_export_failed' );
		} finally {
			fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the in-memory CSV stream.
		}
	}

	/**
	 * Private job repository; no customer CSV is stored under uploads.
	 *
	 * @return Customer_Import_Jobs
	 */
	protected function get_customer_import_jobs() {
		return new Customer_Import_Jobs();
	}

	/**
	 * Validate a real PHP upload before creating independent private job state.
	 * Failed uploads never delete or replace existing jobs.
	 *
	 * @param array $file PHP upload information.
	 * @return array|\WP_Error
	 */
	public function save_csv_file( $file ) {
		$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wholesalex-registration' ) || ! $this->customer_import_allowed() ) {
			return new \WP_Error( 'wholesalex_import_forbidden', __( 'You cannot import customers.', 'wholesalex' ) );
		}
		if ( ! is_array( $file ) || ! isset( $file['name'], $file['tmp_name'], $file['error'] ) || ! is_string( $file['name'] ) || ! is_string( $file['tmp_name'] ) || UPLOAD_ERR_OK !== $file['error'] || 'csv' !== strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'wholesalex_import_upload', __( 'Upload a valid customer CSV file.', 'wholesalex' ) );
		}
		$size = filesize( $file['tmp_name'] );
		if ( false === $size || $size < 1 || $size > Customer_Import_Jobs::MAX_BYTES ) {
			return new \WP_Error( 'wholesalex_import_size', __( 'The customer CSV must be nonempty and no larger than 5 MB.', 'wholesalex' ) );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read only PHP's verified temporary upload; nothing is moved into public storage.
		$csv = file_get_contents( $file['tmp_name'] );
		if ( ! is_string( $csv ) || strlen( $csv ) !== $size ) {
			return new \WP_Error( 'wholesalex_import_upload', __( 'The customer CSV could not be read completely.', 'wholesalex' ) );
		}
		$total = $this->count_customer_csv( $csv );
		if ( is_wp_error( $total ) ) {
			return $total;
		}
		$batch  = isset( $_POST['process_per_iteration'] ) ? filter_var( wp_unslash( $_POST['process_per_iteration'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[0-9]{1,3}$/D' ) ) ) : '10';
		$update = isset( $_POST['update_existing'] ) ? filter_var( wp_unslash( $_POST['update_existing'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^(?:yes|no)$/D' ) ) ) : 'no';
		$find   = isset( $_POST['find_user_by'] ) ? filter_var( wp_unslash( $_POST['find_user_by'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^(?:username|email)$/D' ) ) ) : 'username';
		if ( ! is_scalar( $batch ) || ! ctype_digit( (string) $batch ) || (int) $batch < 1 || (int) $batch > 100 || ! in_array( $update, array( 'yes', 'no' ), true ) || ! in_array( $find, array( 'username', 'email' ), true ) ) {
			return new \WP_Error( 'wholesalex_import_settings', __( 'The customer import settings are invalid.', 'wholesalex' ) );
		}
		return $this->get_customer_import_jobs()->create(
			$csv,
			array(
				'total'                 => $total,
				'process_per_iteration' => (int) $batch,
				'update_existing'       => $update,
				'find_user_by'          => $find,
			)
		);
	}

	/**
	 * Validate CSV shape and count nonempty records using an in-memory stream.
	 *
	 * @param string $csv Uploaded bytes.
	 * @return int|\WP_Error
	 */
	protected function count_customer_csv( $csv ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Customer CSV parsing uses an in-memory stream, never a public file.
		$stream = fopen( 'php://memory', 'w+' );
		if ( false === $stream ) {
			return new \WP_Error( 'wholesalex_import_csv', __( 'The customer CSV could not be read.', 'wholesalex' ) );
		}
		try {
			fwrite( $stream, $csv ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Populate the private in-memory CSV stream.
			rewind( $stream );
			$columns = fgetcsv( $stream, 0, ',', '"', '' );
			if ( ! is_array( $columns ) || count( $columns ) !== count( array_unique( $columns ) ) || array_diff( array_keys( $this->get_default_columns() ), $columns ) ) {
				return new \WP_Error( 'wholesalex_import_columns', __( 'The customer CSV is missing required columns or has duplicate headings.', 'wholesalex' ) );
			}
			$count = 0;
			// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Read each CSV record once.
			while ( false !== ( $row = fgetcsv( $stream, 0, ',', '"', '' ) ) ) {
				if ( array( null ) === $row ) {
					continue;
				}
				if ( count( $row ) !== count( $columns ) ) {
					return new \WP_Error( 'wholesalex_import_row', __( 'The customer CSV contains an incomplete row.', 'wholesalex' ) );
				}
				++$count;
			}
			return $count > 0 ? $count : new \WP_Error( 'wholesalex_import_empty', __( 'The customer CSV has no customer rows.', 'wholesalex' ) );
		} finally {
			fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the private in-memory CSV stream.
		}
	}

	/**
	 * Upload csv file
	 */
	public function import_users() {
		$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wholesalex-registration' ) ) {
			return;
		}

		if ( ! $this->customer_import_allowed() ) {
			return;
		}

		$response = array(
			'log'             => '',
			'message'         => __( 'You must upload a valid csv file to import users', 'wholesalex' ),
			'insert_count'    => 0,
			'update_count'    => 0,
			'skipped_count'   => 0,
			'total'           => 0,
			'process'         => 0,
			'update_existing' => 'no',
		);
		if ( isset( $_FILES['file'] ) && is_array( $_FILES['file'] ) && isset( $_FILES['file']['name'] ) && is_string( $_FILES['file']['name'] ) ) {
			$file           = array(
				'name'     => sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) ),
				'type'     => isset( $_FILES['file']['type'] ) ? sanitize_mime_type( wp_unslash( $_FILES['file']['type'] ) ) : '',
				'tmp_name' => isset( $_FILES['file']['tmp_name'] ) ? sanitize_text_field( $_FILES['file']['tmp_name'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Temporary upload path is only passed to WordPress upload handling.
				'error'    => isset( $_FILES['file']['error'] ) ? absint( $_FILES['file']['error'] ) : 0,
				'size'     => isset( $_FILES['file']['size'] ) ? absint( $_FILES['file']['size'] ) : 0,
			);
			$file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
			if ( 'csv' !== $file_extension ) {
				return wp_send_json_success( $response );
			}

			// valid csv.
			// upload this csv.
			$upload_status = $this->save_csv_file( $file );

			if ( is_wp_error( $upload_status ) ) {
				wp_send_json_error( array( 'message' => $upload_status->get_error_message() ), 400 );
				return;
			}
			$response['total']    = $upload_status['total'];
			$response['process']  = $upload_status['process'];
			$response['job_id']   = $upload_status['id'];
			$response['revision'] = $upload_status['revision'];
			$response['message']  = '';

			wp_send_json_success( $response );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Require the WordPress operation capability before staging customer data.
	 *
	 * @return bool
	 */
	protected function customer_import_allowed() {
		return $this->export_import_allowed() && ( current_user_can( 'create_users' ) || current_user_can( 'edit_users' ) );
	}

	/**
	 * Dispose of only an owned job after nonce and core capability checks.
	 *
	 * @return void
	 */
	public function cancel_customer_import() {
		$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wholesalex-registration' ) || ! $this->customer_import_allowed() ) {
			wp_send_json_error( array( 'message' => __( 'You cannot cancel this import.', 'wholesalex' ) ), 403 );
			return;
		}
		$id     = isset( $_POST['job_id'] ) && is_string( $_POST['job_id'] ) ? filter_var( wp_unslash( $_POST['job_id'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D' ) ) ) : '';
		$jobs   = $this->get_customer_import_jobs();
		$result = $jobs->run(
			$id,
			static function ( $job ) use ( $jobs ) {
				$next          = $job;
				$next['state'] = 'cancelled';
				$next['csv']   = '';
				++$next['revision'];
				return $jobs->save( $job, $next );
			}
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 );
			return;
		}
		wp_send_json_success(
			array(
				'job_id'    => $id,
				'cancelled' => true,
			)
		);
	}

	/**
	 * Remove expired temporary jobs and obsolete public staging on admin visits.
	 *
	 * @return void
	 */
	public function cleanup_customer_imports() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$this->get_customer_import_jobs()->cleanup_expired();
		if ( ! get_option( '__wholesalex_customer_import_private_v1' ) && Customer_Import_Cleanup::legacy() ) {
			add_option( '__wholesalex_customer_import_private_v1', true, '', false );
		}
	}

	/**
	 * Normalize the supported legacy wholesale-role CSV prefix.
	 *
	 * @param string $role Wholesale role from the CSV.
	 * @return string
	 */
	protected function normalize_import_role( $role ) {
		return 0 === strpos( $role, 'whx_old:' ) ? substr( $role, 8 ) : $role;
	}

	/**
	 * Authorize the entire staged CSV before the first customer mutation.
	 *
	 * Each polling request repeats this check because permissions and membership
	 * can change between chunks. The stream is restored to the first data row.
	 *
	 * @param resource $handle CSV stream.
	 * @param array    $columns CSV headings.
	 * @param bool     $is_update Whether existing customers are updated.
	 * @param array    $stats Import settings.
	 * @return true|\WP_Error
	 */
	protected function preflight_customer_import( $handle, $columns, $is_update, $stats ) {
		if ( ! is_array( $columns ) || array_diff( array_keys( $this->get_default_columns() ), $columns ) ) {
			return new \WP_Error( 'wholesalex_import_columns', __( 'The customer CSV is missing required columns.', 'wholesalex' ) );
		}
		$mapped     = array_flip( $columns );
		$data_start = ftell( $handle );
		$find_by    = isset( $stats['find_user_by'] ) ? $stats['find_user_by'] : 'username';
		if ( $is_update && ! in_array( $find_by, array( 'username', 'email' ), true ) ) {
			return new \WP_Error( 'wholesalex_import_lookup', __( 'Invalid customer lookup field.', 'wholesalex' ) );
		}

		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Preflight every staged CSV row before any customer write.
		while ( false !== ( $row = fgetcsv( $handle, 0, ',', '"', '' ) ) ) {
			if ( array( null ) === $row ) {
				continue;
			}
			if ( count( $row ) !== count( $columns ) ) {
				return new \WP_Error( 'wholesalex_import_row', __( 'The customer CSV contains an incomplete row.', 'wholesalex' ) );
			}
			$email = $row[ $mapped['email'] ];
			if ( ! is_email( $email ) ) {
				continue;
			}
			$username = $row[ $mapped['username'] ];
			$target   = $is_update ? get_user_by( 'email' === $find_by ? 'email' : 'login', 'email' === $find_by ? $email : $username ) : false;
			if ( ( $is_update && ! $target ) || ( ! $is_update && email_exists( $email ) ) ) {
				continue;
			}
			$allowed = $this->authorize_customer_import_record( $row, $mapped, $is_update, $target );
			if ( true !== $allowed ) {
				return $allowed;
			}
		}
		fseek( $handle, $data_start );
		return true;
	}

	/**
	 * Authorize the parsed target both during preflight and before mutation.
	 *
	 * @param array          $row Parsed CSV record.
	 * @param array          $mapped Column indexes.
	 * @param bool           $is_update Whether an existing user will be updated.
	 * @param \WP_User|false $target Existing target, or false for creation.
	 * @return true|\WP_Error
	 */
	protected function authorize_customer_import_record( array $row, array $mapped, $is_update, $target ) {
		$actor_id  = get_current_user_id();
		$target_id = $target ? $target->ID : 0;
		$role      = $this->normalize_import_role( $row[ $mapped['wholesalex_role'] ] );
		$status    = $row[ $mapped['wholesalex_account_status'] ];
		$context   = array( 'status' => $status );
		if ( '' !== $role ) {
			$context['role'] = $role;
		}
		$allowed = User_Operation_Policy::authorize( $is_update ? 'edit' : 'create', $actor_id, $target_id, $context );
		if ( true !== $allowed ) {
			return $allowed;
		}
		if ( '' !== $role && $is_update && get_user_meta( $target_id, '__wholesalex_role', true ) !== $role ) {
			$allowed = User_Operation_Policy::authorize( 'promote', $actor_id, $target_id, $context );
			if ( true !== $allowed ) {
				return $allowed;
			}
		}
		if ( '' !== $status ) {
			if ( ! in_array( $status, array( 'active', 'pending', 'reject', 'decline', 'inactive' ), true ) ) {
				return new \WP_Error( 'wholesalex_import_status', __( 'Invalid customer account status.', 'wholesalex' ) );
			}
			if ( $is_update && get_user_meta( $target_id, '__wholesalex_status', true ) !== $status ) {
				return User_Operation_Policy::authorize( 'active' === $status ? 'approve' : 'reject', $actor_id, $target_id );
			}
		}
		return true;
	}

	/**
	 * Process Import Users
	 *
	 * @return void
	 */
	public function wholesalex_process_import_users() {
		if ( ! isset( $_POST['nonce'] ) || ! is_string( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'wholesalex-registration' ) || ! $this->customer_import_allowed() ) {
			wp_send_json_error( array( 'message' => __( 'You cannot process this customer import.', 'wholesalex' ) ), 403 );
			return;
		}
		$id       = isset( $_POST['job_id'] ) && is_string( $_POST['job_id'] ) ? filter_var( wp_unslash( $_POST['job_id'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/D' ) ) ) : '';
		$revision = isset( $_POST['revision'] ) ? filter_var( wp_unslash( $_POST['revision'] ), FILTER_VALIDATE_REGEXP, array( 'options' => array( 'regexp' => '/^[0-9]{1,9}$/D' ) ) ) : null;
		if ( ! is_scalar( $revision ) || ! ctype_digit( (string) $revision ) || strlen( (string) $revision ) > 9 ) {
			wp_send_json_error( array( 'message' => __( 'The import checkpoint is invalid.', 'wholesalex' ) ), 400 );
			return;
		}
		$jobs   = $this->get_customer_import_jobs();
		$result = $jobs->run(
			$id,
			function ( $job ) use ( $jobs, $revision ) {
				if ( (int) $revision > $job['revision'] ) {
					return new \WP_Error( 'wholesalex_import_revision', __( 'The import checkpoint is invalid.', 'wholesalex' ) );
				}
				if ( isset( $job['last_response'] ) && ( (int) $revision < $job['revision'] || 'complete' === $job['state'] ) ) {
					return $job['last_response'];
				}
				if ( 'pending' !== $job['state'] ) {
					return new \WP_Error( 'wholesalex_import_interrupted', __( 'The import was interrupted or cancelled. Upload the CSV again to start a new job.', 'wholesalex' ) );
				}
				$claimed          = $job;
				$claimed['state'] = 'processing';
				$claimed          = $jobs->save( $job, $claimed );
				if ( is_wp_error( $claimed ) ) {
					return $claimed;
				}
				$chunk = $this->process_customer_import_chunk( $claimed, $jobs );
				if ( is_wp_error( $chunk ) ) {
					$failed          = $claimed;
					$failed['state'] = 'failed';
					$failed['csv']   = '';
					$jobs->save( $claimed, $failed );
					return $chunk;
				}
				$next = $chunk['stats'];
				++$next['revision'];
				$chunk['response']['job_id']   = $next['id'];
				$chunk['response']['revision'] = $next['revision'];
				$next['last_response']         = $chunk['response'];
				$saved                         = $jobs->save( $claimed, $next );
				return is_wp_error( $saved ) ? $saved : $chunk['response'];
			}
		);
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 409 );
			return;
		}
		wp_send_json_success( $result );
	}

	/**
	 * Process one claimed job chunk; never terminate the request while locked.
	 *
	 * @param array                $stats Owned immutable upload settings and cursor.
	 * @param Customer_Import_Jobs $jobs Coordinated private job repository.
	 * @return array|\WP_Error
	 */
	protected function process_customer_import_chunk( $stats, $jobs ) {
		$max_process = isset( $stats['process_per_iteration'] ) ? $stats['process_per_iteration'] : 10;
		// Check if a previous end position is stored.
		$start_from       = isset( $stats['previous_position'] ) ? $stats['previous_position'] : 1;
		$current_position = isset( $stats['current_position'] ) ? $stats['current_position'] : 1;

		$is_update = 'yes' === $stats['update_existing'];

		$response = array(
			'log'           => isset( $stats['log'] ) ? $stats['log'] : '',
			'message'       => '',
			'insert_count'  => isset( $stats['insert_count'] ) ? $stats['insert_count'] : 0,
			'update_count'  => isset( $stats['update_count'] ) ? $stats['update_count'] : 0,
			'skipped_count' => isset( $stats['skipped_count'] ) ? $stats['skipped_count'] : 0,
			'total'         => isset( $stats['total'] ) ? $stats['total'] : 0,
			'process'       => isset( $stats['process'] ) ? $stats['process'] : 0,
		);
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decode private CSV data only; it is never evaluated as executable code.
		$csv = base64_decode( $stats['csv'], true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Parse private CSV bytes only in memory.
		$handle = fopen( 'php://memory', 'w+' );
		if ( false === $handle || ! is_string( $csv ) ) {
			return new \WP_Error( 'wholesalex_import_stream', __( 'The private customer CSV could not be read.', 'wholesalex' ) );
		}
		fwrite( $handle, $csv ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Populate the private in-memory CSV stream.
		rewind( $handle );
		$filters_added = false;
		try {

			if ( false !== $handle ) {

				// Get the length of the first row.

				$columns = fgetcsv( $handle, 0, ',', '"', '' );

				$permission = $this->preflight_customer_import( $handle, $columns, $is_update, $stats );
				if ( is_wp_error( $permission ) ) {
					return $permission;
				}

				$filters_added = true;
				add_filter( 'send_password_change_email', '__return_false' );
				add_filter( 'woocommerce_email_change_notification', '__return_false' );
				$mapped_column = array_flip( $columns );
				$log           = '';
				$row_count     = isset( $stats['row_count'] ) ? $stats['row_count'] : 1;

				if ( 1 === $start_from ) {
					// Set the file pointer to the last processed position.
					fseek( $handle, ftell( $handle ) );

				} else {
					fseek( $handle, $start_from );
				}

				// Assume the chunk drains the file; the row-limit break below clears this.
				$reached_end_of_file = true;
				// Rows can be skipped without advancing the file position, so seed this
				// from the stored value rather than leaving it undefined on an empty chunk.
				$current_position = isset( $stats['previous_position'] ) ? $stats['previous_position'] : 0;

				$rows_in_chunk = 0;
				// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Read each CSV row once and stop at end of file.
				while ( $rows_in_chunk < $max_process && ( $data = fgetcsv( $handle, 0, ',', '"', '' ) ) !== false ) {
					$claim = $jobs->assert_claim( $stats );
					if ( is_wp_error( $claim ) ) {
						return $claim;
					}
					++$rows_in_chunk;
					$user_extra_data_upsate = array();

					$current_position = ftell( $handle );
					if ( array( null ) === $data ) {
						continue;
					}
					if ( count( $data ) !== count( $columns ) ) {
						return new \WP_Error( 'wholesalex_import_row', __( 'The customer CSV contains an incomplete row.', 'wholesalex' ) );
					}
					$username = $data[ $mapped_column['username'] ];
					$email    = $data[ $mapped_column['email'] ];
					$password = $data[ $mapped_column['password'] ];

					$log .= "Row $row_count: ";
					++$row_count;

					if ( empty( $email ) ) {
						++$response['skipped_count'];
						$log .= "Email is mandatory! , Skipped\n";
						continue;
					}

					$first_name   = $data[ $mapped_column['first_name'] ];
					$last_name    = $data[ $mapped_column['last_name'] ];
					$display_name = $data[ $mapped_column['display_name'] ];

					if ( ! is_email( $email ) ) {
						$errors[] = array( "$email is not a Valid Email!" );
						$log     .= "$email is not a Valid Email! , Skipped\n";
						++$response['skipped_count'];
						++$response['process'];
						continue;
					}

					$is_username_exist = username_exists( $username );
					$is_email_exist    = email_exists( $email );

					if ( $is_update ) {
						$flag         = false;
						$find_user_by = isset( $stats['find_user_by'] ) ? $stats['find_user_by'] : 'username';
						switch ( $find_user_by ) {
							case 'username':
								$user = get_user_by( 'login', $username );
								if ( $user && $is_username_exist ) {
									$log .= "$username found!. \n";
								} else {
									$log .= "$username Not found!. \n";
									++$response['process'];
									++$response['skipped_count'];
									$flag = true;
									break;
								}
								break;
							case 'email':
								$user = get_user_by( 'email', $email );
								if ( $user && $is_email_exist ) {
									$log .= "$email found!. \n";
								} else {
									$log .= "$email Not found!. \n";
									++$response['process'];
									++$response['skipped_count'];
									$flag = true;
									break;
								}

								break;

							default:
								$flag = true;
								break;
						}
						if ( $flag ) {
							continue;
						}
						$permission = $this->authorize_customer_import_record( $data, $mapped_column, true, $user );
						if ( true !== $permission ) {
							return $permission;
						}
					} else {
						if ( $is_email_exist ) {
							$log .= "Skipped\n";
							++$response['skipped_count'];
							++$response['process'];
							continue;
						}
						$permission = $this->authorize_customer_import_record( $data, $mapped_column, false, false );
						if ( true !== $permission ) {
							return $permission;
						}
						$user_id = wc_create_new_customer( $email, $username, $password );

						if ( is_wp_error( $user_id ) ) {
							$__error_messages = $user_id->get_error_messages();
							$log             .= 'User Created Failed. Errors: ';
							if ( is_array( $__error_messages ) ) {
								foreach ( $__error_messages as $error_message ) {
									$errors[] = $error_message;
									$log     .= $error_message . ',';
								}
							}

							$log .= "Skipped\n";
							++$response['skipped_count'];
							++$response['process'];
							continue;
						} else {
							$log .= "User Created. User ID $user_id ";
							++$response['insert_count'];
							++$response['process'];
						}
						$created[] = $user_id;
						$user      = get_userdata( $user_id );
					}

					if ( ! is_object( $user ) ) {
						$log .= "User Object Not Found.\n";
						++$response['skipped_count'];
						++$response['process'];
						continue;
					}

					$user_data       = array();
					$user_data['ID'] = $user->ID;

					if ( ! email_exists( $email ) && $email !== $user->user_email ) {
						$user_data['user_email'] = $email;
						$log                    .= "$username Email Updated. ";
					}

					if ( ! empty( $password ) && $is_update ) {
						$user_data['user_pass']   = $password;
						$log                     .= 'Password Updated.';
						$user_extra_data_upsate[] = 'password';
					}

					// Define an array of fields and their corresponding meta keys or user data keys.
					$fields         = array(
						'first_name'   => 'first_name',
						'last_name'    => 'last_name',
						'display_name' => 'display_name',
						'nickname'     => 'nickname',
						'bio'          => 'description',
						'avatar'       => 'avatar',
					);
					$user_data_full = get_userdata( $user->ID );

					foreach ( $fields as $field_key => $meta_key ) {
						$field_value   = 'nickname' === $field_key || 'bio' === $field_key || 'avatar' === $field_key ? $data[ $mapped_column[ $field_key ] ] : $$field_key;
						$current_value = ( 'display_name' === $field_key ) ? $user_data_full->display_name : get_user_meta( $user->ID, $meta_key, true );

						// phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- CSV strings and stored metadata retain their existing value comparison.
						if ( ! empty( $field_value ) && $field_value != $current_value ) {
							$user_data[ $meta_key ]   = $field_value;
							$log                     .= ucfirst( str_replace( '_', ' ', $field_key ) ) . ' Updated.';
							$user_extra_data_upsate[] = $field_key;
						}
					}

					if ( count( $user_data ) > 1 ) {
						wp_update_user( $user_data );
					}
					if ( count( $data ) > 0 && $is_update ) {
						++$response['process'];
						++$response['update_count'];
					}

					$billing_shipping_fields = array(
						'billing_first_name'  => 'Billing First Name',
						'billing_last_name'   => 'Billing Last Name',
						'billing_company'     => 'Billing Company',
						'billing_address_1'   => 'Billing Address 1',
						'billing_city'        => 'Billing City',
						'billing_postcode'    => 'Billing Postcode',
						'billing_country'     => 'Billing Country',
						'billing_state'       => 'Billing State',
						'billing_phone'       => 'Billing Phone',
						'shipping_first_name' => 'Shipping First Name',
						'shipping_last_name'  => 'Shipping Last Name',
						'shipping_company'    => 'Shipping Company',
						'shipping_address_1'  => 'Shipping Address 1',
						'shipping_city'       => 'Shipping City',
						'shipping_postcode'   => 'Shipping PostCode',
						'shipping_country'    => 'Shipping Country',
						'shipping_state'      => 'Shipping State',
						'shipping_phone'      => 'Shipping Phone',
					);

					foreach ( $billing_shipping_fields as $field_key => $label ) {
						if ( ! empty( $data[ $mapped_column[ $field_key ] ] ) && get_user_meta( $user->ID, $field_key, true ) !== $data[ $mapped_column[ $field_key ] ] ) {
							update_user_meta( $user->ID, $field_key, $data[ $mapped_column[ $field_key ] ] );
							$log                     .= $label . ' Updated.';
							$user_extra_data_upsate[] = $label;
						}
					}

					if ( ! empty( $data[ $mapped_column['wholesalex_account_status'] ] ) ) {
						$previous_status = get_user_meta( $user->ID, '__wholesalex_status', true );
						require_once __DIR__ . '/registration/class-confirmation-service.php';
						$result = Confirmation_Service::set_status( $user->ID, $data[ $mapped_column['wholesalex_account_status'] ] );
						if ( is_wp_error( $result ) ) {
							return $result;
						}
						if ( $previous_status !== $data[ $mapped_column['wholesalex_account_status'] ] ) {
							$log .= sprintf( '%s Account Status Updated.', wholesalex()->get_plugin_name() );
						}
					}

					$data[ $mapped_column['wholesalex_role'] ] = $this->normalize_import_role( $data[ $mapped_column['wholesalex_role'] ] );

					if ( ! empty( $data[ $mapped_column['wholesalex_role'] ] ) && get_user_meta( $user->ID, '__wholesalex_role', true ) !== $data[ $mapped_column['wholesalex_role'] ] ) {
						wholesalex()->change_role( $user->ID, $data[ $mapped_column['wholesalex_role'] ] );
						$log .= sprintf( '%s Role Updated', wholesalex()->get_plugin_name() );
					}

					$log = apply_filters( 'wholesalex_customer_import_export_log', $log, $is_update );

					do_action( 'wholesalex_import_userdata', $user->ID, $data, $mapped_column, $is_update );
					if ( $is_update ) {
						do_action( 'wholesalex_user_profile_update_notify', $user->ID, $user_extra_data_upsate );
					}
					$user_extra_data_upsate = array();
				}

				$reached_end_of_file        = ftell( $handle ) >= strlen( $csv );
				$stats['insert_count']      = $response['insert_count'];
				$stats['update_count']      = $response['update_count'];
				$stats['previous_position'] = $current_position;
				$stats['skipped_count']     = $response['skipped_count'];
				$stats['process']           = $response['process'];
				$stats['log']              .= $log;
				$stats['row_count']         = $row_count;

				$response['total'] = $stats['total'];

				if ( $reached_end_of_file ) {
					// Some skipped rows never advance the processed counter, so align it
					// with the total once the file is drained; otherwise the browser,
					// which polls until processed reaches total, would never stop.
					$response['process'] = $response['total'];
					$stats['process']    = $response['total'];
				}

				$response['log'] = $stats['log'];

				// The staged file holds customer data. Remove it once the reader has
				// drained the file, rather than relying on the processed-row counter,
				// which does not advance for every skipped row.
				if ( $reached_end_of_file ) {
					$stats['csv'] = '';
				}
			}

			$stats['state'] = $reached_end_of_file ? 'complete' : 'pending';
			return array(
				'stats'    => $stats,
				'response' => $response,
			);
		} finally {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close private CSV memory before unlocking or sending JSON.
			if ( $filters_added ) {
				remove_filter( 'send_password_change_email', '__return_false' );
				remove_filter( 'woocommerce_email_change_notification', '__return_false' );
			}
		}
	}

	/**
	 * Serve the generated file.
	 */
	public function download_export_file() {
		if ( isset( $_GET['action'], $_GET['nonce'] ) && is_string( $_GET['action'] ) && is_string( $_GET['nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['nonce'] ) ), 'product-csv' ) && 'download_product_csv' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) { // WPCS: input var ok, sanitization ok.
			include_once WC_ABSPATH . 'includes/export/class-wc-product-csv-exporter.php'; // @codingStandardsIgnoreLine.
			$exporter = new \WC_Product_CSV_Exporter();

			if ( ! empty( $_GET['filename'] ) ) { // WPCS: input var ok.
				$exporter->set_filename( sanitize_text_field( wp_unslash( $_GET['filename'] ) ) ); // WPCS: input var ok, sanitization ok.
			}

			$exporter->export();
		}
	}

	/**
	 * Export Roles
	 *
	 * @return void
	 */
	public function export_roles() {
		if ( ! isset( $_GET['nonce'] ) || ! is_string( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['nonce'] ) ), 'whx-export-roles' ) ) {
			return;
		}
		if ( ! $this->export_import_allowed() ) {
			return;
		}
		if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'export-roles-csv' ) { // WPCS: input var ok, sanitization ok.
			include_once WHOLESALEX_PATH . 'includes/export/class-wholesalex-roles-csv-exporter.php';
			$exporter = new \WHOLESALEX\WHOLESALEX_Role_CSV_Exporter();
			$exporter->set_filename( 'wholesalex_roles.csv' ); // WPCS: input var ok, sanitization ok.
			$exporter->generate_file();
			$exporter->export();
		}
	}

	/**
	 * Export Dynamic Rules
	 *
	 * @return void
	 */
	public function export_dynamic_rules() {
		if ( ! isset( $_GET['nonce'] ) || ! is_string( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['nonce'] ) ), 'whx-export-dynamic-rules' ) ) {
			return;
		}
		if ( ! $this->export_import_allowed() ) {
			return;
		}
		if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'export-dynamic-rule-csv' ) {
			$selected_ids = isset( $_GET['exported_ids'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_GET['exported_ids'] ) ) ) : array();
			$export_all   = isset( $_GET['export_all'] ) && 'yes' === sanitize_text_field( wp_unslash( $_GET['export_all'] ) );
			foreach ( wholesalex()->get_dynamic_rules() as $record ) {
				if ( ! $export_all && ! in_array( (string) $record['id'], $selected_ids, true ) ) {
					continue;
				}
				if ( 'quantity_based' === ( $record['_rule_type'] ?? '' ) ) {
					$provided = apply_filters( 'wholesalex_dynamic_rule_provider_result', null, 'export', $record, $record );
					if ( is_wp_error( $provided ) ) {
						return $provided; }
					if ( ! is_array( $provided ) ) {
						return new \WP_Error( 'wholesalex_provider_unavailable', __( 'The saved rule requires its pricing provider.', 'wholesalex' ), array( 'status' => 409 ) );
					}
				}
			}
			// WPCS: input var ok, sanitization ok.
			include_once WHOLESALEX_PATH . 'includes/export/class-wholesalex-dynamic-rule-exporter.php';
			$exporter = new \WHOLESALEX\WHOLESALEX_Dynamic_Rule_CSV_Exporter();
			$exporter->set_filename( 'wholesalex_dynamic_rules.csv' ); // WPCS: input var ok, sanitization ok.
			$exporter->generate_file();
			$exporter->export();
		}
	}

	/**
	 * The roles importer.
	 *
	 * This has a custom screen - the Tools > Import item is a placeholder.
	 * If we're on that screen, redirect to the custom one.
	 */
	public function roles_importer() {

		if ( $this->export_import_allowed() ) {
			include_once WHOLESALEX_PATH . 'includes/import/class-wholesalex-role-csv-importer.php';
			include_once WHOLESALEX_PATH . 'includes/import/class-wholesalex-role-csv-importer-controller.php';
			$importer = new WHOLESALEX_Role_CSV_Importer_Controller();
		}
	}
	/**
	 * The dynamic rules importer importer.
	 *
	 * This has a custom screen - the Tools > Import item is a placeholder.
	 * If we're on that screen, redirect to the custom one.
	 */
	public function dynamic_rules_importer() {
		if ( $this->export_import_allowed() ) {
			include_once WHOLESALEX_PATH . 'includes/import/class-wholesalex-dynamic-rule-csv-importer.php';
			include_once WHOLESALEX_PATH . 'includes/import/class-wholesalex-dynamic-rule-csv-importer-controller.php';
			$importer = new WHOLESALEX_Dynamic_Rule_CSV_Importer_Controller();
		}
	}

	/**
	 * Check whether a file is a valid CSV file.
	 *
	 * @param string $file File path.
	 * @param bool   $check_path Whether to also check the file is located in a valid location (Default: true).
	 * @return bool
	 */
	public static function is_file_valid_csv( $file, $check_path = true ) {
		return wc_is_file_valid_csv( $file, $check_path );
	}
}
