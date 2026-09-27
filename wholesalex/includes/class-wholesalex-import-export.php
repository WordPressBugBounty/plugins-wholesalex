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
	 * Name of the staged import file for the current request.
	 *
	 * @var string
	 */
	protected $import_file_name = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_wholesalex_export_users_columns', array( $this, 'export_users_columns' ) );
		add_action( 'wp_ajax_wholesalex_export_users', array( $this, 'export_users' ) );
		add_action( 'wp_ajax_wholesalex_import_users', array( $this, 'import_users' ) );
		add_action( 'wp_ajax_wholesalex_process_import_users', array( $this, 'wholesalex_process_import_users' ) );

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
		if ( $this->export_import_allowed() ) {
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

		$users = get_users( $args );
		return $users;
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
		if ( ! $this->export_import_allowed() ) {
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
					case 'billing_postcode':
						if ( get_user_meta( $user->ID, $column, true ) ) {
							$user_data[ $column ] = get_user_meta( $user->ID, $column, true );

						} else {
							$user_data[ $column ] = get_user_meta( $user->ID, 'billing_post_code', true );

						}
						break;
					case 'billing_country':
					case 'billing_state':
					case 'shipping_first_name':
					case 'shipping_last_name':
					case 'shipping_company':
					case 'shipping_address_1':
					case 'shipping_city':
					case 'shipping_postcode':
						if ( get_user_meta( $user->ID, $column, true ) ) {
							$user_data[ $column ] = get_user_meta( $user->ID, $column, true );

						} else {
							$user_data[ $column ] = get_user_meta( $user->ID, 'shipping_post_code', true );

						}
						break;
					case 'shipping_country':
					case 'shipping_state':
						$user_data[ $column ] = get_user_meta( $user->ID, $column, true );
						if ( ! $user_data[ $column ] ) {
							$user_data[ $column ] = ' ';
						}
						// replace comma with dash.
						$user_data[ $column ] = str_replace( ',', '-', $user_data[ $column ] );
						break;
				}
			}
			$user_data = apply_filters( 'wholesalex_user_export_column_data', $user_data );
			$data[]    = $user_data;
		}
		$csv_data = '';

		// Add column headers to the CSV data.
		$csv_data .= implode( ',', $exportable_columns ) . "\n";

		// Add data rows to the CSV data.
		foreach ( $data as $row ) {
			$row_data = array();
			foreach ( $exportable_columns as $column ) {
				$row_data[] = isset( $row[ $column ] ) ? $row[ $column ] : '';
			}
			$csv_data .= implode( ',', $row_data ) . "\n";
		}

		wp_send_json_success( $csv_data );
	}


	/**
	 * Return the directory used for staged import files, creating and hardening it.
	 *
	 * The staged CSV contains customer data, so the directory is protected against
	 * direct web access and directory listing on Apache, LiteSpeed, IIS and nginx
	 * setups that honour these files.
	 *
	 * @return string Absolute path with a trailing slash.
	 */
	protected function get_import_dir() {
		$upload_dir = wp_upload_dir();
		$target_dir = trailingslashit( $upload_dir['basedir'] ) . 'wholesalex_import_data/';

		if ( ! is_dir( $target_dir ) ) {
			wp_mkdir_p( $target_dir );
		}

		$guards = array(
			'.htaccess'  => "Require all denied\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n",
			'index.php'  => "<?php\n// Silence is golden.\n",
			'index.html' => '',
			'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>\n",
		);

		// WP_Filesystem may be unavailable or need credentials on some hosts, and
		// the uploads directory is always writable by PHP directly, so write the
		// guards with the filesystem API when it is ready and fall back otherwise.
		global $wp_filesystem;
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$has_filesystem = WP_Filesystem() && is_object( $wp_filesystem );

		foreach ( $guards as $guard_file => $contents ) {
			$guard_path = $target_dir . $guard_file;
			if ( file_exists( $guard_path ) ) {
				continue;
			}
			if ( $has_filesystem ) {
				$wp_filesystem->put_contents( $guard_path, $contents, FS_CHMOD_FILE );
			} else {
				@file_put_contents( $guard_path, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents,WordPress.PHP.NoSilencedErrors.Discouraged -- Local guard-file fallback when WP_Filesystem requires unavailable credentials; preserve non-fatal import handling.
			}
		}

		return $target_dir;
	}

	/**
	 * Return the staged import file recorded for the current import, if it exists.
	 *
	 * @return string Absolute path, or an empty string when no staged file is recorded.
	 */
	protected function get_staged_import_file() {
		$stats = get_option( '__wholesalex_customer_import_export_stats', array() );
		$name  = is_array( $stats ) && isset( $stats['file_name'] ) ? basename( (string) $stats['file_name'] ) : '';

		if ( '' === $name ) {
			return '';
		}

		$path = $this->get_import_dir() . $name;

		return is_file( $path ) ? $path : '';
	}

	/**
	 * Remove any staged import file and the progress record that points at it.
	 *
	 * @return void
	 */
	protected function delete_staged_import_file() {
		$stats = get_option( '__wholesalex_customer_import_export_stats', array() );
		$name  = is_array( $stats ) && isset( $stats['file_name'] ) ? basename( (string) $stats['file_name'] ) : '';

		if ( '' !== $name ) {
			$path = $this->get_import_dir() . $name;
			if ( is_file( $path ) ) {
				wp_delete_file( $path );
			}
		}

		// Remove files left by earlier versions, which used a fixed, guessable name.
		$legacy = $this->get_import_dir() . 'wholesalex_users.csv';
		if ( is_file( $legacy ) ) {
			wp_delete_file( $legacy );
		}
	}

	/**
	 * Save csv file
	 *
	 * @param string $file File Path.
	 */
	public function save_csv_file( $file ) {
		$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wholesalex-registration' ) ) {
			return;
		}

		if ( ! $this->export_import_allowed() ) {
			return;
		}

		// Discard anything staged by a previous run before staging the new upload.
		$this->delete_staged_import_file();
		delete_option( '__wholesalex_customer_import_export_stats' );

		$target_dir = $this->get_import_dir();

		// An unguessable name keeps the staged customer data out of reach even if
		// the directory guards above are not honoured by the web server.
		$this->import_file_name = 'wholesalex_users-' . wp_generate_password( 32, false, false ) . '.csv';

		$overrides = array(
			'test_form' => false,
			'mimes'     => self::get_valid_csv_filetypes(),
		);

		add_filter( 'upload_dir', array( $this, 'change_upload_dir' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'change_import_file_name' ) );
		$upload_success = wp_handle_upload( $file, $overrides );
		remove_filter( 'wp_handle_upload_prefilter', array( $this, 'change_import_file_name' ) );
		remove_filter( 'upload_dir', array( $this, 'change_upload_dir' ) );

		if ( ( is_object( $upload_success ) && ! is_null( $upload_success->url ) ) || is_array( $upload_success ) ) {
			// Record the name WordPress actually wrote; it sanitizes the requested
			// name and appends a suffix if anything already occupies that path.
			$stored_name = ( is_array( $upload_success ) && ! empty( $upload_success['file'] ) )
				? basename( $upload_success['file'] )
				: $this->import_file_name;

			// count csv row and update in db.
			$file_path                      = $target_dir . $stored_name;
			$row_count                      = $this->count_and_filter_csv( $file_path );
			$stats                          = get_option( '__wholesalex_customer_import_export_stats', array() );
			$stats['file_name']             = $stored_name;
			$stats['total']                 = $row_count;
			$stats['process']               = 0;
			$stats['update_existing']       = isset( $_POST['update_existing'] ) ? sanitize_text_field( wp_unslash( $_POST['update_existing'] ) ) : 'no';
			$process_per_iteration          = isset( $_POST['process_per_iteration'] ) ? sanitize_text_field( wp_unslash( $_POST['process_per_iteration'] ) ) : null;
			$stats['process_per_iteration'] = null !== $process_per_iteration ? sanitize_text_field( $process_per_iteration ) : 10;
			if ( $stats['update_existing'] ) {
				$stats['find_user_by'] = isset( $_POST['find_user_by'] ) ? sanitize_text_field( wp_unslash( $_POST['find_user_by'] ) ) : 'username';
			}
			$stats['log'] = '';
			update_option( '__wholesalex_customer_import_export_stats', $stats );
		}

		return $upload_success;
	}

	/**
	 * Change Import File Name
	 *
	 * @param array $file File Data.
	 * @return array
	 */
	public function change_import_file_name( $file ) {
		if ( ! empty( $this->import_file_name ) ) {
			$file['name'] = $this->import_file_name;
		}
		return $file;
	}

	/**
	 * Change Upload Directory
	 *
	 * @param string $dir Directory.
	 * @return string
	 */
	public function change_upload_dir( $dir ) {
		return array(
			'path'   => $dir['basedir'] . '/wholesalex_import_data',
			'url'    => $dir['baseurl'] . '/wholesalex_import_data',
			'subdir' => '/wholesalex_import_data',
		) + $dir;
	}

	/**
	 * Get all the valid filetypes for a CSV file.
	 *
	 * @return array
	 */
	protected static function get_valid_csv_filetypes() {
		return apply_filters(
			'wholesalex_csv_users_import_valid_filetypes',
			array(
				'csv' => 'text/csv',
				'txt' => 'text/plain',
			)
		);
	}


	/**
	 * Upload csv file
	 */
	public function import_users() {
		$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wholesalex-registration' ) ) {
			return;
		}

		if ( ! $this->export_import_allowed() ) {
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

			if ( $upload_status ) {
				$import_stats = get_option( '__wholesalex_customer_import_export_stats', array() );
				// user data upload successful.
				$response['total']   = isset( $import_stats['total'] ) ? $import_stats['total'] : 0;
				$response['process'] = isset( $import_stats['process'] ) ? $import_stats['process'] : 0;
				$response['message'] = '';
			}

			wp_send_json_success( $response );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Filter empty row from csv and cound valid row
	 *
	 * @param string $file_path file path.
	 * @return int
	 */
	public function count_and_filter_csv( $file_path ) {
		$row_count = 0;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CSV parsing requires a stream resource.
		$handle = fopen( $file_path, 'r+' );
		if ( false !== $handle ) {
			$columns = fgetcsv( $handle );

			$mapped_column = array_flip( $columns );
			// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Read each CSV row once and stop at end of file.
			while ( false !== ( $data = fgetcsv( $handle ) ) ) {
				$email = $data[ $mapped_column['email'] ];

				if ( ! empty( array_filter( $data ) ) || empty( $email ) ) {
					++$row_count;
				}
			}
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the CSV stream resource.
		}

		return $row_count;
	}


	/**
	 * Process Import Users
	 *
	 * @return void
	 */
	public function wholesalex_process_import_users() {
		if ( ! isset( $_POST['nonce'] ) || ! is_string( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), 'wholesalex-registration' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wholesalex' ) ), 403 );
		}

		if ( ! $this->export_import_allowed() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to import users.', 'wholesalex' ) ), 403 );
		}

		add_filter( 'send_password_change_email', '__return_false' );
		add_filter( 'woocommerce_email_change_notification', '__return_false' );

		$stats = get_option( '__wholesalex_customer_import_export_stats', array() );

		$max_process = isset( $stats['process_per_iteration'] ) ? $stats['process_per_iteration'] : 10;
		// Check if a previous end position is stored.
		$start_from       = isset( $stats['previous_position'] ) ? $stats['previous_position'] : 1;
		$current_position = isset( $stats['current_position'] ) ? $stats['current_position'] : 1;

		$is_update = isset( $_POST['update_existing'] ) ? 'yes' === $_POST['update_existing'] : false;

		$response = array(
			'log'           => isset( $stats['log'] ) ? $stats['log'] : '',
			'message'       => '',
			'insert_count'  => isset( $stats['insert_count'] ) ? $stats['insert_count'] : 0,
			'update_count'  => isset( $stats['update_count'] ) ? $stats['update_count'] : 0,
			'skipped_count' => isset( $stats['skipped_count'] ) ? $stats['skipped_count'] : 0,
			'total'         => isset( $stats['total'] ) ? $stats['total'] : 0,
			'process'       => isset( $stats['process'] ) ? $stats['process'] : 0,
		);
		// wholesalex custom import csv folder.
		$file_path = $this->get_staged_import_file();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- CSV parsing requires a stream resource.
		$handle = '' !== $file_path ? fopen( $file_path, 'r' ) : false;

		if ( false === $handle ) {
			// No staged file left to read: the import is over. The browser polls
			// until processed reaches total, so report completion rather than
			// echoing a count that can never advance.
			$response['process'] = $response['total'];
			wp_send_json_success( $response );
		}

		if ( false !== $handle ) {

			// Get the length of the first row.

			$columns = fgetcsv( $handle );

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

			// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- Read each CSV row once and stop at end of file.
			while ( ( $data = fgetcsv( $handle ) ) !== false ) {
				$user_extra_data_upsate = array();

				$current_position = ftell( $handle );
				$username         = $data[ $mapped_column['username'] ];
				$email            = $data[ $mapped_column['email'] ];
				$password         = $data[ $mapped_column['password'] ];

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
				} else {
					if ( $is_email_exist ) {
						$log .= "Skipped\n";
						++$response['skipped_count'];
						++$response['process'];
						continue;
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

				if ( ! empty( $data[ $mapped_column['wholesalex_role'] ] ) ) {
					$temp = explode( ':', $data[ $mapped_column['wholesalex_role'] ] );

					if ( 'whx_old' === $temp[0] ) {
						$data[ $mapped_column['wholesalex_role'] ] = $temp[1];
					}
				}

				if ( ! empty( $data[ $mapped_column['wholesalex_role'] ] ) && get_user_meta( $user->ID, '__wholesalex_role', true ) !== $data[ $mapped_column['wholesalex_role'] ] ) {
					wholesalex()->change_role( $user->ID, $data[ $mapped_column['wholesalex_role'] ] );
					$log .= sprintf( '%s Role Updated', wholesalex()->get_plugin_name() );
				}
				if ( ! empty( $data[ $mapped_column['wholesalex_account_status'] ] ) && get_user_meta( $user->ID, '__wholesalex_status', true ) !== $data[ $mapped_column['wholesalex_account_status'] ] ) {
					update_user_meta( $user->ID, '__wholesalex_status', $data[ $mapped_column['wholesalex_account_status'] ] );
					$log .= sprintf( '%s Account Status Updated.', wholesalex()->get_plugin_name() );
				}

				$log = apply_filters( 'wholesalex_customer_import_export_log', $log, $is_update );

				do_action( 'wholesalex_import_userdata', $user->ID, $data, $mapped_column, $is_update );
				if ( $is_update ) {
					do_action( 'wholesalex_user_profile_update_notify', $user->ID, $user_extra_data_upsate );
				}
				$user_extra_data_upsate = array();
				if ( ( $row_count - 1 ) >= $max_process ) {
					$reached_end_of_file = false;
					break;
				}
			}

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

			update_option( '__wholesalex_customer_import_export_stats', $stats );

			$response['log'] = $stats['log'];

			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the CSV stream resource.

			// The staged file holds customer data. Remove it once the reader has
			// drained the file, rather than relying on the processed-row counter,
			// which does not advance for every skipped row.
			if ( $reached_end_of_file ) {
				$this->delete_staged_import_file();
			}
		}

		wp_send_json_success( $response );
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
		if ( isset( $_GET['action'] ) && sanitize_text_field( wp_unslash( $_GET['action'] ) ) === 'export-dynamic-rule-csv' ) { // WPCS: input var ok, sanitization ok.
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
