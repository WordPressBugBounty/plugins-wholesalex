<?php
/**
 * Role Action.
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

use WC_Data_Store;
use WC_Shipping_Zone;

/**
 * WholesaleX Role Class.
 */
class WHOLESALEX_Role {
	/**
	 * Constructor
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'delete_selected_role_callback' ) );
		add_action( 'rest_api_init', array( $this, 'save_role_callback' ) );
		add_action( 'admin_init', array( $this, 'ensure_default_roles' ), 1 );
		add_filter( 'option_woocommerce_tax_display_shop', array( $this, 'tax_display' ) );
		add_filter( 'option_woocommerce_tax_display_cart', array( $this, 'tax_display' ) );

		/**
		 * Rolewise Hide/Disable Coupons
		 *
		 * @since 1.0.4
		 */
		add_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_fields' ) );
		add_filter( 'wholesalex_user_subaccount_create_permission', array( $this, 'restrict_subaccount_creation' ), 10, 2 );

		/**
		 * Auto Role Migration
		 *
		 * @since 1.0.4
		 */
		add_action( 'wholesalex_before_dynamic_rules_loaded', array( $this, 'auto_wholesalex_role_migration' ) );

		add_action( 'admin_init', array( $this, 'conditionally_filter_additional_role' ) );

		add_filter( 'wholesalex_csv_role_import_mapping_options', array( $this, 'set_import_column_value' ) );
		add_filter( 'wholesalex_csv_role_import_mapping_default_columns', array( $this, 'set_import_mapping_default_column' ) );
		add_filter( 'wholesalex_role_importer_parsed_data', array( $this, 'parse_import_data' ) );
	}

	/**
	 * Repair required default roles if a fresh/partial installation missed them.
	 *
	 * @return void
	 */
	public function ensure_default_roles() {
		$created_roles        = wholesalex()->ensure_default_roles();
		$default_b2b_role_id  = apply_filters( 'wholesalex_default_b2b_role_id', 'wholesalex_b2b_wholesale' );
		$default_role_ids     = array_keys( wholesalex()->get_default_roles() );
		$role_ids             = wholesalex()->get_roles( 'ids' );
		$has_only_defaults    = empty( array_diff( $role_ids, $default_role_ids ) );
		$has_default_b2b_role = ! empty( wholesalex()->get_roles( 'by_id', $default_b2b_role_id ) );

		if (
			'yes' !== get_option( '_wholesalex_default_admin_role_assigned' )
			&& $has_default_b2b_role
			&& ( in_array( $default_b2b_role_id, $created_roles, true ) || $has_only_defaults )
		) {
			wholesalex()->assign_admin_wholesale_role( $default_b2b_role_id );
		}
	}

	/**
	 * Conditionally adds a filter to modify editable roles on specific admin pages.
	 *
	 * This function checks if the current admin page is one of the user profile-related pages
	 * (profile, add new user, edit user). If it is, it adds a filter to remove specific roles
	 * (e.g., 'wholesalex') from the list of roles that can be assigned to users.
	 *
	 * Hooked to the 'admin_init' action.
	 *
	 * @return void
	 */
	public function conditionally_filter_additional_role() {
		global $pagenow;

		// Check if we are on one of the targeted pages.
		$target_pages = array( 'profile.php', 'user-new.php', 'user-edit.php' );

		if ( in_array( $pagenow, $target_pages, true ) ) {
			add_filter( 'editable_roles', array( $this, 'make_wholesalex_roles_not_editable' ) );
		}
	}

	/**
	 * Delete Selected Role Callback
	 *
	 * @return void
	 */
	public function delete_selected_role_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/delete_roles/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'delete_selected_roles' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Delete Selected Roles
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function delete_selected_roles( \WP_REST_Request $request ) {
		$roles_to_delete = $request->get_param( 'roles' );
		if ( ! is_array( $roles_to_delete ) || empty( $roles_to_delete ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid roles', 'wholesalex' ),
				),
				400
			);
		}
		$roles_option = get_option( '_wholesalex_roles', array() );
		if ( empty( $roles_option ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No roles found', 'wholesalex' ),
				),
				400
			);
		}

		$deleted_roles = array();
		foreach ( $roles_to_delete as $role_id ) {
			$role_id = sanitize_key( $role_id );
			if ( isset( $roles_option[ $role_id ] ) && wholesalex()->delete_role( $role_id ) ) {
				$deleted_roles[] = $role_id;
			}
		}

		if ( empty( $deleted_roles ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No deletable B2B roles found.', 'wholesalex' ),
				),
				400
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'roles'   => $deleted_roles,
			),
			200
		);
	}

	/**
	 * Save WholesaleX Role Actions
	 *
	 * @return void
	 */
	public function save_role_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/role_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'role_action_callback' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Role Action Callback : Get and Save Role
	 *
	 * @param object $server Server.
	 * @return mixed
	 */
	public function role_action_callback( $server ) {
		$post = $server->get_params();
		if ( ! ( isset( $post['nonce'] ) && wp_verify_nonce( sanitize_key( $post['nonce'] ), 'wholesalex-registration' ) ) ) {
			return;
		}

		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		if ( 'post' === $type ) {

			$_id   = sanitize_text_field( $post['id'] );
			$_role = wp_unslash( $post['role'] );
			$_role = json_decode( $_role, true );
			$_role = wholesalex()->sanitize( $_role );
			$_flag = true;
			if ( isset( $post['check'] ) ) {
				if ( empty( wholesalex()->get_roles( 'by_id', $_id ) ) ) {
					$_flag = false;
				}
			}
			$is_delete = isset( $post['delete'] ) && $post['delete'];
			if ( $is_delete && ! wholesalex()->is_deletable_role( $_id ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Only B2B roles can be deleted.', 'wholesalex' ),
					),
					400
				);
			}

			$_saved = $_flag && wholesalex()->set_roles( $_id, $_role, $is_delete ? 'delete' : '' );
			if ( $is_delete && ! $_saved ) {
				wp_send_json_error(
					array(
						'message' => __( 'Role could not be deleted.', 'wholesalex' ),
					),
					400
				);
			}

			wp_send_json_success(
				array(
					'message' => $_flag ? __( 'Successfully Saved.', 'wholesalex' ) : __( 'Before Status Update, You Have to save role.', 'wholesalex' ),
				)
			);

		} elseif ( 'get' === $type ) {
			$__roles = array_values( wholesalex()->get_roles( 'store_mode_roles' ) );
			if ( empty( $__roles ) ) {
				$__roles = array(
					array(
						'id'    => 1,
						'label' => __( 'New Role', 'wholesalex' ),
					),
				);
			}
			self::inject_wtrs_shipping_methods( $__roles );
			$data            = array();
			$data['default'] = self::get_role_fields();
			$data['value']   = $__roles;
			wp_send_json_success( $data );
		} elseif ( 'get_users_by_role_id' === $type ) {
			$_role_id = isset( $post['id'] ) ? sanitize_text_field( $post['id'] ) : '';
			if ( ! $_role_id ) {
				wp_send_json_success( array() );
			} else {
				$__users_options = $this->get_users_by_role_id( $_role_id );

				wp_send_json_success( $__users_options );

			}
		}
	}

	/**
	 * Returns all currently enabled WTRS (WoW Table Rate Shipping) instance IDs
	 * across every WooCommerce shipping zone including Rest of World.
	 *
	 * @return string[]
	 */
	private static function get_current_wtrs_instance_ids(): array {
		static $ids = null;

		if ( null !== $ids ) {
			return $ids;
		}

		$ids = array();
		if ( ! defined( 'WTRS_VER' ) ) {
			return $ids;
		}

		$data_store = \WC_Data_Store::load( 'shipping-zone' );
		foreach ( $data_store->get_zones() as $zone_raw ) {
			$zone = new \WC_Shipping_Zone( $zone_raw );
			foreach ( $zone->get_shipping_methods() as $method ) {
				if ( $method->is_enabled() && 'wtrs_wc_method' === $method->id ) {
					$ids[] = strval( $method->get_instance_id() );
				}
			}
		}
		$rot = new \WC_Shipping_Zone( 0 );
		foreach ( $rot->get_shipping_methods() as $method ) {
			if ( $method->is_enabled() && 'wtrs_wc_method' === $method->id ) {
				$ids[] = strval( $method->get_instance_id() );
			}
		}
		return $ids;
	}

	/**
	 * Auto-check every active WTRS shipping method for each WholesaleX role.
	 *
	 * WowShipping owns the enabled state of its methods. WholesaleX mirrors that
	 * state at runtime so an active WowShipping rule is selected in User Roles and
	 * remains available at checkout without requiring a separate role save.
	 *
	 * @param array $roles Roles array passed by reference.
	 * @return void
	 */
	public static function inject_wtrs_shipping_methods( array &$roles ) {
		$wtrs_ids = self::get_current_wtrs_instance_ids();

		if ( empty( $wtrs_ids ) ) {
			return;
		}

		foreach ( $roles as &$role ) {
			$existing = isset( $role['_shipping_methods'] )
				? array_values( array_filter( (array) $role['_shipping_methods'] ) )
				: array();

			$role['_shipping_methods'] = array_values(
				array_unique( array_merge( $existing, $wtrs_ids ) )
			);
		}
		unset( $role );
	}

	/**
	 * Apply automatic WTRS selections to one role for the current request.
	 *
	 * Keeping this normalization in one place ensures every shipping-rate
	 * handler uses the same effective role without persisting implicit choices.
	 *
	 * @param array $role Role configuration.
	 * @return array
	 */
	public static function get_role_with_wtrs_shipping_methods( array $role ): array {
		$roles = array( $role );
		self::inject_wtrs_shipping_methods( $roles );

		return $roles[0];
	}

	/**
	 * WholesaleX Role Sub Menu Page Callback
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function role_content_callback() {
		/**
		 * Enqueue Script
		 *
		 * @since 1.1.0 Enqueue Script (Reconfigure Build File)
		 */
		wp_enqueue_script( 'wholesalex_roles' );

		$__roles = array_values( wholesalex()->get_roles( 'store_mode_roles' ) );
		if ( empty( $__roles ) ) {
			$__roles = array(
				array(
					'id'    => 1,
					'label' => __( 'New Role', 'wholesalex' ),
				),
			);
		}
		self::inject_wtrs_shipping_methods( $__roles );

			wp_localize_script(
				'wholesalex_roles',
				'whx_roles',
				array(
					'fields'      => self::get_role_fields(),
					'data'        => $__roles,
					'nonce'       => wp_create_nonce( 'whx-export-roles' ),
					'tax_classes' => \WHOLESALEX\Dynamic_Rules::get_tax_classes(),
					'i18n'        => array(
					),
				)
			);
		?>
		<div id="_wholesalex_role"></div>
		<?php
		do_action( 'wholesalex_role_after_content_render' );
	}

	/**
	 * Roles Fields
	 *
	 * @since 1.0.0
	 * @since 1.0.4 Role Settings Section Added.
	 */
	public static function get_role_fields() {
		$available_payment_gateways = WC()->payment_gateways->payment_gateways();
		$payment_gateways           = array();
		foreach ( $available_payment_gateways as $key => $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				$gateway_title = trim( (string) $gateway->get_title() );
				$payment_gateways[ $key ] = '' !== $gateway_title ? $gateway_title : $gateway->get_method_title();
			}
		}
		$__shipping_sections = array();

		$data_store         = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones          = $data_store->get_zones();
		$zones              = array();
		$__shipping_zones   = array();
		$__shipping_methods = array();

		foreach ( $raw_zones as $raw_zone ) {
			$zone                  = new WC_Shipping_Zone( $raw_zone );
			$zone_id               = $zone->get_id();
			$zone_name             = $zone->get_zone_name();
			$zone_shipping_methods = $zone->get_shipping_methods();
			$shipping_methods      = array();
			$method_types          = array();
			$zone_wtrs_defaults    = array( '' );

			foreach ( $zone_shipping_methods as $key => $method ) {
				if ( $method->is_enabled() ) {
					$method_instance_id                      = $method->get_instance_id();
					$method_title                            = $method->get_title();
					$shipping_methods[ $method_instance_id ] = $method_title;
					$method_types[ $method_instance_id ]     = $method->id;
					$__shipping_methods[ $zone_id ][]        = array(
						'value' => $method_instance_id,
						'name'  => $method_title,
					);
					$__shipping_method_options[ $zone_id . ':' . $method_instance_id ] = $zone_name . ' : ' . $method_title;
					if ( 'wtrs_wc_method' === $method->id ) {
						$zone_wtrs_defaults[] = strval( $method_instance_id );
					}
				}
			}

			$__shipping_sections[ $zone_id ] = array(
				'type'  => 'shipping_zone',
				'label' => $zone_name,
				'attr'  => array(
					'_shipping_methods' => array(
						'type'         => 'checkbox',
						'label'        => '',
						'options'      => $shipping_methods,
						'method_types' => $method_types,
						'default'      => $zone_wtrs_defaults,
						'help'         => __( 'If no methods are selected, all methods are available for this role.', 'wholesalex' ),
					),
				),
			);

			$__shipping_zones['']         = __( 'Choose Shipping Zone...', 'wholesalex' );
			$__shipping_zones[ $zone_id ] = $zone_name;
			$zones[]                      = array(
				'name'            => $zone_name,
				'value'           => $zone_id,
				'shipping_method' => $shipping_methods,
			);
		}

		// Add "Rest of the World" zone if any of its shipping methods are enabled.
		$rest_of_the_world     = new WC_Shipping_Zone( 0 );
		$zone_id               = $rest_of_the_world->get_id();
		$zone_name             = $rest_of_the_world->get_zone_name();
		$zone_shipping_methods = $rest_of_the_world->get_shipping_methods();
		$shipping_methods      = array();
		$method_types          = array();
		$has_enabled_method    = false;
		$rot_wtrs_defaults     = array( '' );

		foreach ( $zone_shipping_methods as $key => $method ) {
			if ( $method->is_enabled() ) {
				$method_instance_id                      = $method->get_instance_id();
				$method_title                            = $method->get_title();
				$shipping_methods[ $method_instance_id ] = $method_title;
				$method_types[ $method_instance_id ]     = $method->id;
				$__shipping_methods[ $zone_id ][]        = array(
					'value' => $method_instance_id,
					'name'  => $method_title,
				);
				$__shipping_method_options[ $zone_id . ':' . $method_instance_id ] = $zone_name . ' : ' . $method_title;
				$has_enabled_method = true;
				if ( 'wtrs_wc_method' === $method->id ) {
					$rot_wtrs_defaults[] = strval( $method_instance_id );
				}
			}
		}

		if ( $has_enabled_method ) {
			$__shipping_sections[ $zone_id ] = array(
				'type'  => 'shipping_zone',
				'label' => $zone_name,
				'attr'  => array(
					'_shipping_methods' => array(
						'type'         => 'checkbox',
						'label'        => '',
						'options'      => $shipping_methods,
						'method_types' => $method_types,
						'default'      => $rot_wtrs_defaults,
						'help'         => __( 'If no methods are selected, all methods are available for this role.', 'wholesalex' ),
					),
				),
			);

			$__shipping_zones[ $zone_id ] = $zone_name;
			$zones[]                      = array(
				'name'            => $zone_name,
				'value'           => $zone_id,
				'shipping_method' => $shipping_methods,
			);
		}
		return apply_filters(
			'wholesalex_role_fields',
			array(
				'create_n_save_btn' => array(
					'type' => 'buttons',
					'attr' => array(
						'create' => array(
							'type'  => 'button',
							'label' => __( 'Add New B2B Role', 'wholesalex' ),
						),
					),
				),
				'_role'             => array(
					'label' => __( 'Create New Role', 'wholesalex' ),
					'type'  => 'role',
					'attr'  => array(
						'_rule_title_n_status_section' => array(
							'label' => '',
							'type'  => 'title_n_status',
							'_id'   => 1,
							'attr'  => array(
								'_role_title' => array(
									'type'        => 'text',
									'label'       => __( 'Name', 'wholesalex' ),
									'placeholder' => __( 'Name', 'wholesalex' ),
									'default'     => '',
									'help'        => '',
								),
								'save_role'   => array(
									'type'  => 'button',
									'label' => __( 'Save', 'wholesalex' ),
								),
							),
						),
						'role_id_section'              => array(
							'label' => '',
							'type'  => 'role_id',
							'_id'   => 1,
							'attr'  => array(
								'role_id' => array(
									'type'        => 'text',
									'label'       => wholesalex()->get_language_n_text( version_compare( WHOLESALEX_VER, '1.0.2', '>=' ) ? '_language_wholesalex_role_id' : wholesalex()->get_setting( '_language_wholesalex_role_id' ), __( 'Role ID', 'wholesalex' ) ),
									'placeholder' => '',
									'default'     => '',
									'help'        => '',
								),
							),
						),
						'regi_url_form_section'        => array(
							'label' => __( 'Registration Form', 'wholesalex' ),
							'type'  => 'role_setting',
							'attr'  => array(
								'user_status'          => array(
									'type'     => 'radio',
									'label'    => __( 'Registration Approval Method', 'wholesalex' ),
									'options'  => array(
										'global_setting' => __( 'Use Global Setting', 'wholesalex' ),
										'email_confirmation_require' => __( 'Email Confirmation Required', 'wholesalex' ),
										'auto_approve'   => __( 'Automatically Approve Account', 'wholesalex' ),
										'admin_approve'  => __( 'Admin Approval Required', 'wholesalex' ),
									),
									'default'  => 'global_setting',
									'help'     => __( 'The global registration approval method is configured in the Registration Form settings. Override it here only for this role.', 'wholesalex' ),
									'excludes' => apply_filters( 'wholesalex_exclude_regi_form_field', array( 'wholesalex_guest' ) ),
								),
								'after_login_redirect' => array(
									'type'     => 'url',
									'label'    => __( 'Redirected to Page URL (After Login)', 'wholesalex' ),
									'help'     => '',
									'default'  => wholesalex()->get_setting( '_settings_redirect_url_login' ),
									'excludes' => apply_filters( 'wholesalex_exclude_regi_form_field', array( 'wholesalex_guest', 'wholesalex_b2c_users' ) ),
								),
								'after_registration_redirect' => array(
									'type'     => 'url',
									'label'    => __( 'Redirected to Page URL (After Registration)', 'wholesalex' ),
									'help'     => '',
									'default'  => wholesalex()->get_setting( '_settings_redirect_url_registration' ),
									'excludes' => apply_filters( 'wholesalex_exclude_regi_form_field', array( 'wholesalex_guest', 'wholesalex_b2c_users' ) ),
								),
							),
						),
						'display_prices_section'       => array(
							'label' => '',
							'type'  => 'display_price',
							'_id'   => 1,
							'attr'  => array(
								'_display_price' => array(
									'type'    => 'radio',
									'label'   => __( 'Display Prices', 'wholesalex' ),
									'options' => array(
										''     => __( 'Default', 'wholesalex' ),
										'incl' => __( 'Include Tax', 'wholesalex' ),
										'excl' => __( 'Exclude Tax', 'wholesalex' ),
									),
									'default' => '',
									'help'    => '',
								),
							),
						),
						'payment_methods_section'      => array(
							'label' => '',
							'type'  => 'payment_method',
							'_id'   => 1,
							'attr'  => array(
								'_payment_methods' => array(
									'type'    => 'slider',
									'label'   => __( 'Available Payment Methods', 'wholesalex' ),
									'options' => $payment_gateways,
									'default' => array( '' ),
									'help'    => '',
								),
							),
						),
						'shipping_methods_section'     => array(
							'label' => 'Shipping Methods',
							'type'  => 'shipping_section',
							'_id'   => 1,
							'attr'  => $__shipping_sections,
						),
						'settings_section'             => array(
							'label' => __( 'Role Setting', 'wholesalex' ),
							'type'  => 'role_setting',
							'attr'  => array(
								'_disable_coupon' => array(
									'type'    => 'slider',
									'label'   => '',
									'help'    => '',
									'options' => array( '_disable_coupon' => __( 'Disable Coupons For This Role', 'wholesalex' ) ),
									'desc'    => 'Disable Coupons For This Role',
									'default' => 'no',
								),
							),
						),
						'settings_combined_migration_field' => array(
							'label' => __( 'Role Setting', 'wholesalex' ),
							'type'  => 'role_setting',
							'attr'  => array(
								'_auto_role_migration' => array(
									'type'     => 'slider',
									'label'    => '',
									'help'     => '',
									'default'  => 'no',
									'options'  => array( '_auto_role_migration' => __( 'Enable Auto Role Migration', 'wholesalex' ) ),
									'desc'     => __( 'Enable Auto Role Migration', 'wholesalex' ),
									'excludes' => apply_filters( 'wholesalex_exclude_auto_role_migration_field', array( 'wholesalex_guest', 'wholesalex_b2c_users' ) ),
								),
								'_role_migration_threshold_value' => array(
									'type'       => 'number',
									'label'      => __( 'Minimum Purchase Amount Required to Qualify for This Role', 'wholesalex' ),
									'depends_on' => array(
										array(
											'key'   => '_auto_role_migration',
											'value' => 'yes',
										),
									),
									'help'       => '',
									'default'    => '',
									'excludes'   => apply_filters( 'wholesalex_exclude_auto_role_migration_field', array( 'wholesalex_guest', 'wholesalex_b2c_users' ) ),
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Available Payment Gateways
	 *
	 * @param object $gateways Payment Gateways.
	 * @since 1.0.0
	 * @since 1.0.3 Updated
	 * @since 1.2.19 Profile Gateway Override Added
	 */
	public function available_payment_gateways( $gateways ) {
		$__role_id         = wholesalex()->get_current_user_role();
		$__role_content    = wholesalex()->get_roles( 'by_id', $__role_id );
		$__payment_methods = array();
		if ( isset( $__role_content['_payment_methods'] ) && ! empty( $__role_content['_payment_methods'] ) ) {
			$__payment_methods = $__role_content['_payment_methods'];
		}
		$__payment_methods = array_filter( $__payment_methods );

		$__available_gateways = array();

		// Get Profile Gateways.
		$__profile_settings = get_user_meta( get_current_user_id(), '__wholesalex_profile_settings', true );

		$__profile_gateways = array();

		if ( isset( $__profile_settings['_wholesalex_profile_override_payment_gateway'] ) && 'yes' === $__profile_settings['_wholesalex_profile_override_payment_gateway'] ) {
			if ( isset( $__profile_settings['_wholesalex_profile_payment_gateways'] ) && ! empty( $__profile_settings['_wholesalex_profile_payment_gateways'] ) ) {
				$__profile_gateways = $__profile_settings['_wholesalex_profile_payment_gateways'];
			}
		}

		foreach ( $__profile_gateways as $key => $value ) {
			$__payment_methods[] = $value['value'];
		}

		foreach ( $__payment_methods as $method ) {
			if ( isset( $gateways[ $method ] ) && null !== $gateways[ $method ] ) {
				$__available_gateways[ $method ] = $gateways[ $method ];
			}
		}

		if ( empty( $__payment_methods ) ) {
			return $gateways;
		}

		return $__available_gateways;
	}

	/**
	 * Available Shipping Methods
	 *
	 * @param object $shipping_methods Shipping Methods.
	 */
	public function available_shipping_methods( $shipping_methods ) {
		$__role_id          = wholesalex()->get_current_user_role();
		$__role_content     = wholesalex()->get_roles( 'by_id', $__role_id );
		$__shipping_methods = array();
		if ( isset( $__role_content['_shipping_methods'] ) && ! empty( $__role_content['_shipping_methods'] ) ) {
			$__shipping_methods = $__role_content['_shipping_methods'];
		}
		$__shipping_methods = array_filter( $__shipping_methods );

		$__available_shipping_methods = array();

		foreach ( $shipping_methods as $rate_key => $rate ) {

			if ( in_array( $rate->instance_id, $__shipping_methods ) ) { //phpcs:ignore
				$__available_shipping_methods[ $rate_key ] = $rate;
			}
		}

		if ( empty( $__shipping_methods ) || empty( $__available_shipping_methods ) ) {
			return $shipping_methods;
		}

		return $__available_shipping_methods;
	}


	/**
	 * Filter Rolewise Shipping Methods
	 *
	 * @param object $package_rates Package Rates.
	 * @param object $package Package.
	 * @return object shipping methods.
	 * @since 1.0.4
	 */
	public function filter_shipping_methods( $package_rates, $package ) {
		$__role_id          = wholesalex()->get_current_user_role();
		$__role_content     = wholesalex()->get_roles( 'by_id', $__role_id );
		$__shipping_methods = array();
		if ( isset( $__role_content['_shipping_methods'] ) && ! empty( $__role_content['_shipping_methods'] ) ) {
			$__shipping_methods = $__role_content['_shipping_methods'];
		}

		$__shipping_methods = array_filter( $__shipping_methods );

		$__available_shipping_methods = array();

		foreach ( $package_rates as $rate_key => $rate ) {
			if (in_array($rate->instance_id, $__shipping_methods)) { //phpcs:ignore
				$__available_shipping_methods[ $rate_key ] = $rate;
			}
		}

		if ( ! empty( $__available_shipping_methods ) ) {
			return $__available_shipping_methods;
		}

		return $package_rates;
	}

	/**
	 * Display Prices Including Taxes
	 *
	 * @param string $option Include tax or Exclude Tax in Shop.
	 */
	public function tax_display( $option ) {
		if ( is_admin() ) {
			return $option;
		}
		$__role_id      = wholesalex()->get_current_user_role();
		$__role_content = wholesalex()->get_roles( 'by_id', $__role_id );
		if ( isset( $__role_content['_display_price'] ) && ! empty( $__role_content['_display_price'] ) ) {
			$option = $__role_content['_display_price'];
		}
		return $option;
	}

	/**
	 * Rolewise Hide/Disable Coupons
	 *
	 * @param bool $enabled Coupon Fields Enable Status.
	 * @return bool
	 * @since 1.0.4
	 */
	public function hide_coupon_fields( $enabled ) {
		if ( ! is_user_logged_in() ) {
			return $enabled;
		}

		$user_id      = (int) apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		$restrictions = $this->get_general_restrictions_for_user( $user_id );
		if ( $this->is_user_excluded_from_general_restrictions( $user_id, $restrictions ) ) {
			return $enabled;
		}

		if ( $this->is_truthy( isset( $restrictions['_disable_coupon'] ) ? $restrictions['_disable_coupon'] : false ) ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Restrict subaccount creation for users whose role enables the general restriction.
	 *
	 * @param bool $has_permission Existing permission status.
	 * @param int  $user_id User attempting to create a subaccount.
	 * @return bool
	 */
	public function restrict_subaccount_creation( $has_permission, $user_id ) {
		if ( ! $has_permission ) {
			return false;
		}

		$user_id      = (int) apply_filters( 'wholesalex_set_current_user', absint( $user_id ) );
		$restrictions = $this->get_general_restrictions_for_user( $user_id );
		if ( $this->is_user_excluded_from_general_restrictions( $user_id, $restrictions ) ) {
			return true;
		}

		return ! $this->is_truthy( isset( $restrictions['_restrict_subaccount'] ) ? $restrictions['_restrict_subaccount'] : false );
	}

	/**
	 * Get general restrictions for a user, including legacy top-level role fields.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	private function get_general_restrictions_for_user( $user_id ) {
		$role_id      = wholesalex()->get_user_role( $user_id );
		$role_content = wholesalex()->get_roles( 'by_id', $role_id );
		$restrictions = isset( $role_content['_restrictions'] ) && is_array( $role_content['_restrictions'] )
			? $role_content['_restrictions']
			: array();

		foreach ( array( '_disable_coupon', '_restrict_subaccount', '_exclude_users' ) as $key ) {
			if ( ! array_key_exists( $key, $restrictions ) && array_key_exists( $key, $role_content ) ) {
				$restrictions[ $key ] = $role_content[ $key ];
			}
		}

		return $restrictions;
	}

	/**
	 * Check whether a user is excluded from the role's general restrictions.
	 *
	 * @param int   $user_id User ID.
	 * @param array $restrictions Role restrictions.
	 * @return bool
	 */
	private function is_user_excluded_from_general_restrictions( $user_id, array $restrictions ) {
		if ( empty( $restrictions['_exclude_users'] ) || ! is_array( $restrictions['_exclude_users'] ) ) {
			return false;
		}

		$excluded_user_ids = array();
		foreach ( $restrictions['_exclude_users'] as $item ) {
			$value = is_array( $item ) && isset( $item['value'] ) ? $item['value'] : $item;
			if ( is_numeric( $value ) || is_string( $value ) ) {
				$excluded_user_ids[] = (int) preg_replace( '/[^0-9]/', '', (string) $value );
			}
		}

		return in_array( (int) $user_id, array_filter( array_unique( $excluded_user_ids ) ), true );
	}

	/**
	 * Normalize bool-like role values.
	 *
	 * @param mixed $value Role setting value.
	 * @return bool
	 */
	private function is_truthy( $value ) {
		return true === $value || 1 === $value || '1' === $value || 'yes' === $value;
	}

	/**
	 * Auto WholesaleX Role Migration
	 *
	 * @param int $user_id User ID.
	 * @since 1.0.4
	 */
	public function auto_wholesalex_role_migration( $user_id ) {
		$__roles = array_values( wholesalex()->get_roles() );
		if ( empty( $__roles ) ) {
			$__roles = array(
				array(
					'id'    => 1,
					'label' => 'New Role',
				),
			);
		}
		$__current_user_role = wholesalex()->get_current_user_role();

		foreach ( $__roles as $role ) {
			if ( isset( $role['id'] ) && $__current_user_role === $role['id'] ) {
				continue;
			}
			if ( ! isset( $role['_auto_role_migration'] ) || ! isset( $role['_role_migration_threshold_value'] ) ) {
				continue;
			}
			if ( 'yes' === $role['_auto_role_migration'] && $role['_role_migration_threshold_value'] && WHOLESALEX_Dynamic_Rules::$cu_total_spent ) {
				if ( $role['_role_migration_threshold_value'] <= WHOLESALEX_Dynamic_Rules::$cu_total_spent ) {
					wholesalex()->change_role( $user_id, $role['id'], $__current_user_role );
					do_action( 'wholesalex_role_auto_migrate', $role['id'], $__current_user_role );
				}
			}
		}
	}


	/**
	 * Get Users By Role ID
	 *
	 * @param int|string $role_id Role ID.
	 *
	 * @since 1.0.9
	 */
	public function get_users_by_role_id( $role_id ) {
		$users        = get_users(
			array(
				'fields'     => array( 'ID', 'user_login' ),
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- WholesaleX roles, status, and account type are stored as user metadata.
					'relation' => 'AND',
					array(
						'key'     => '__wholesalex_role',
						'value'   => $role_id,
						'compare' => '=',
					),
					array(
						'key'     => '__wholesalex_status',
						'value'   => 'active',
						'compare' => '=',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '__wholesalex_account_type',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '__wholesalex_account_type',
							'value'   => 'subaccount',
							'compare' => '!=',
						),
					),
				),
			)
		);
		$user_options = array();
		foreach ( $users as $user ) {
			$full_name = $user->display_name;
			if ( empty( $full_name ) ) {
				$full_name = $user->user_login;
			}
			$user_options[] = array(
				'name'     => $full_name,
				'value'    => 'user_' . $user->ID,
				'fullName' => $full_name,
			);
		}
		return $user_options;
	}


	/**
	 * Make WholesaleX Role Not Editable.
	 *
	 * @param array $roles WP Roles.
	 * @return array
	 * @since 1.0.10
	 */
	public function make_wholesalex_roles_not_editable( $roles ) {
		foreach ( $roles as $key => $value ) {
			// Remove WholesaleX Roles.
			if ( is_numeric( $key ) || preg_match( '/^wholesalex_/', $key ) ) {
				unset( $roles[ $key ] );
			}
		}
		return $roles;
	}

	/**
	 * Set Import Column Value
	 *
	 * @param array $options Options.
	 * @return array
	 */
	public function set_import_column_value( $options ) {
		if ( ! isset( $options['_raq_replace_add_to_cart_with_quote'] ) ) {
			$options['_raq_replace_add_to_cart_with_quote'] = __( 'Replace Add to Cart With Add to Quote', 'wholesalex' );
		}

		return $options;
	}

	/**
	 * Set Import Mapping Default Column
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function set_import_mapping_default_column( $columns ) {
		if ( ! isset( $columns[ __( 'Replace Add to Cart With Add to Quote', 'wholesalex' ) ] ) ) {
			$columns[ __( 'Replace Add to Cart With Add to Quote', 'wholesalex' ) ] = '_raq_replace_add_to_cart_with_quote';
		}

		return $columns;
	}

	/**
	 * Parse Import Data
	 *
	 * @param array $data Import Data.
	 * @return array
	 */
	public function parse_import_data( $data ) {
		if ( isset( $data['_raq_replace_add_to_cart_with_quote'] ) ) {
			$value = sanitize_text_field( $data['_raq_replace_add_to_cart_with_quote'] );
			if ( ! ( 'yes' === $value || 'no' === $value ) ) {
				$value = '';
			}
		}

		return $data;
	}
}
