<?php
/**
 * WholesaleX role tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP\Abilities;

use WHOLESALEX\MCP\Access;
use WHOLESALEX\MCP\Fields;
use WHOLESALEX\MCP\Lookup;
use WHOLESALEX\MCP\Pro_Features;
use WHOLESALEX\MCP\Schema;
use WHOLESALEX\Role_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Lists, reads, creates, updates and deletes WholesaleX roles.
 *
 * Writes go through `Role_Service` and `Functions::set_roles()`, which
 * validate every field through `Role_Data_Boundary`, as the role editor does.
 */
final class Role_Abilities {

	/**
	 * Registration approval values, tool value => stored value.
	 *
	 * @var array<string, string>
	 */
	const APPROVAL = array(
		'global_setting'     => 'global_setting',
		'email_confirmation' => 'email_confirmation_require',
		'auto_approve'       => 'auto_approve',
		'admin_approval'     => 'admin_approve',
	);

	/**
	 * Price display values, tool value => stored value.
	 *
	 * @var array<string, string>
	 */
	const TAX_DISPLAY = array(
		'store_default' => '',
		'including_tax' => 'incl',
		'excluding_tax' => 'excl',
	);

	/**
	 * Return the tool definitions.
	 *
	 * @return array<string, array>
	 */
	public static function definitions(): array {
		return array(
			'wholesalex/list-roles'  => array(
				'label'        => __( 'List WholesaleX roles', 'wholesalex' ),
				'description'  => __( 'Lists every WholesaleX customer role, such as Wholesale or Distributor, plus the built-in B2C and guest roles, with their main settings. Use the role IDs with the rule and registration tools.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::no_input(),
				'callback'     => array( __CLASS__, 'list_roles' ),
			),
			'wholesalex/get-role'    => array(
				'label'        => __( 'Get a WholesaleX role', 'wholesalex' ),
				'description'  => __( 'Returns the settings and customer count of one WholesaleX role, including all available User Roles restrictions and exclusions. Read this before changing general, product visibility, purchase, checkout, price visibility or tax restrictions.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::object( array( 'id' => self::id_schema() ), array( 'id' ) ),
				'callback'     => array( __CLASS__, 'get_role' ),
			),
			'wholesalex/create-role' => array(
				'label'        => __( 'Create a WholesaleX role', 'wholesalex' ),
				'description'  => __( 'Creates a B2B customer role, for example "Gold Wholesaler", with the available User Roles restrictions. Set wholesale prices and discounts with the pricing rule tools.', 'wholesalex' ),
				'operation'    => Access::WRITE,
				'input_schema' => self::role_schema( true ),
				'callback'     => array( __CLASS__, 'create_role' ),
			),
			'wholesalex/update-role' => array(
				'label'        => __( 'Update a WholesaleX role', 'wholesalex' ),
				'description'  => __( 'Changes the settings and all available User Roles restrictions of a WholesaleX role. This is the default tool for general restrictions, product visibility, non-purchasable products, checkout quantity/value limits, price visibility, quotes and tax rules. Additional restriction controls require active licensed Pro and any related add-ons. Read the role first; preserve unrelated targets, exclusions and restriction groups. Only supplied fields change. Lists replace their saved selections. Built-in roles cannot be renamed. Use dynamic rules only when the user explicitly requests them.', 'wholesalex' ),
				'operation'    => Access::WRITE,
				'idempotent'   => true,
				'input_schema' => self::role_schema( false ),
				'callback'     => array( __CLASS__, 'update_role' ),
			),
			'wholesalex/delete-role' => array(
				'label'        => __( 'Delete a WholesaleX role', 'wholesalex' ),
				'description'  => __( 'Permanently deletes a B2B role. Customers who had it lose the role and fall back to the default B2C role. Built-in roles cannot be deleted. Check the customer count with wholesalex-get-role first.', 'wholesalex' ),
				'operation'    => Access::DELETE,
				'input_schema' => Schema::object( array( 'id' => self::id_schema() ), array( 'id' ) ),
				'callback'     => array( __CLASS__, 'delete_role' ),
			),
		);
	}

	/**
	 * List roles.
	 *
	 * @return array
	 */
	public static function list_roles(): array {
		$roles = array();
		foreach ( wholesalex()->get_roles() as $id => $role ) {
			if ( is_array( $role ) && ! empty( $role['_role_title'] ) ) {
				$roles[] = self::format( (string) ( $role['id'] ?? $id ), $role, false );
			}
		}
		return array(
			'total' => count( $roles ),
			'roles' => $roles,
		);
	}

	/**
	 * Get one role.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function get_role( array $input ) {
		$id   = (string) $input['id'];
		$role = self::find( $id );
		return is_wp_error( $role ) ? $role : array( 'role' => self::format( $id, $role, true ) );
	}

	/**
	 * Create a role.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function create_role( array $input ) {
		$error = Pro_Features::check_input( 'wholesalex/create-role', $input );
		if ( is_wp_error( $error ) ) {
			return $error;
		}
		$changes = self::to_storage( $input, array(), '' );
		if ( is_wp_error( $changes ) ) {
			return $changes;
		}
		$id = Role_Service::create( $changes );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$role = self::find( $id );
		return is_wp_error( $role ) ? $role : array( 'role' => self::format( $id, $role, true ) );
	}

	/**
	 * Update a role.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function update_role( array $input ) {
		$error = Pro_Features::check_input( 'wholesalex/update-role', $input );
		if ( is_wp_error( $error ) ) {
			return $error;
		}
		$id       = (string) $input['id'];
		$existing = self::find( $id );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}
		$changes = self::to_storage( $input, $existing, $id );
		if ( is_wp_error( $changes ) ) {
			return $changes;
		}
		$saved = Role_Service::update( $id, $changes );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		$role = self::find( $id );
		return is_wp_error( $role ) ? $role : array( 'role' => self::format( $id, $role, true ) );
	}

	/**
	 * Delete a role.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function delete_role( array $input ) {
		$id   = (string) $input['id'];
		$role = self::find( $id );
		if ( is_wp_error( $role ) ) {
			return $role;
		}
		$customers = Role_Service::count_customers( $id );
		$deleted   = Role_Service::delete( $id );
		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}
		return array(
			'deleted'              => true,
			'id'                   => $id,
			'title'                => (string) ( $role['_role_title'] ?? '' ),
			'customers_unassigned' => $customers,
		);
	}

	/**
	 * Convert tool input into stored role fields.
	 *
	 * @param array  $input    Tool input.
	 * @param array  $existing Stored role, empty for a new role.
	 * @param string $id       Role ID, empty for a new role.
	 * @return array|\WP_Error
	 */
	private static function to_storage( array $input, array $existing, string $id ) {
		$changes = array();
		$kind    = self::kind( $id );

		if ( array_key_exists( 'title', $input ) ) {
			$changes['_role_title'] = sanitize_text_field( (string) $input['title'] );
		}
		if ( array_key_exists( 'registration_approval', $input ) ) {
			if ( 'guest' === $kind ) {
				return Fields::invalid( __( 'Guests do not register, so the guest role has no registration approval setting.', 'wholesalex' ) );
			}
			$changes['user_status'] = self::APPROVAL[ (string) $input['registration_approval'] ];
		}
		if ( array_key_exists( 'tax_display', $input ) ) {
			$changes['_display_price'] = self::TAX_DISPLAY[ (string) $input['tax_display'] ];
		}
		if ( array_key_exists( 'payment_method_ids', $input ) ) {
			$gateways = Lookup::gateway_ids( (array) $input['payment_method_ids'] );
			if ( is_wp_error( $gateways ) ) {
				return $gateways;
			}
			$changes['_payment_methods'] = $gateways;
		}
		if ( array_key_exists( 'shipping_method_ids', $input ) ) {
			$methods = Lookup::shipping_method_ids( (array) $input['shipping_method_ids'] );
			if ( is_wp_error( $methods ) ) {
				return $methods;
			}
			$changes['_shipping_methods'] = $methods;
		}
		if ( array_key_exists( 'disable_coupons', $input ) ) {
			// The role editor writes the restriction and its legacy mirror together.
			$disabled                   = (bool) $input['disable_coupons'];
			$changes['_restrictions']   = array( '_disable_coupon' => $disabled );
			$changes['_disable_coupon'] = $disabled ? 'yes' : 'no';
		}
		if ( array_key_exists( 'auto_assign_by_spend', $input ) || array_key_exists( 'spend_threshold', $input ) ) {
			if ( 'b2b' !== $kind && '' !== $id ) {
				return Fields::invalid( __( 'Only B2B roles can be assigned automatically by customer spend.', 'wholesalex' ) );
			}
			if ( array_key_exists( 'auto_assign_by_spend', $input ) ) {
				$changes['_auto_role_migration'] = ! empty( $input['auto_assign_by_spend'] ) ? 'yes' : 'no';
			}
			if ( array_key_exists( 'spend_threshold', $input ) ) {
				$changes['_role_migration_threshold_value'] = (string) ( 0 + $input['spend_threshold'] );
			}
			$enabled   = 'yes' === ( $changes['_auto_role_migration'] ?? ( $existing['_auto_role_migration'] ?? 'no' ) );
			$threshold = $changes['_role_migration_threshold_value'] ?? ( $existing['_role_migration_threshold_value'] ?? '' );
			if ( $enabled && ( ! is_numeric( $threshold ) || (float) $threshold <= 0 ) ) {
				return Fields::invalid( __( 'Automatic assignment needs a spend_threshold above 0.', 'wholesalex' ) );
			}
		}

		/**
		 * Filters the stored role fields built from AI tool input.
		 *
		 * Extensions that add role settings with
		 * `wholesalex_mcp_role_input_properties` convert their input here.
		 * Return a WP_Error to reject the input with a message for the AI
		 * assistant.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $changes  Stored role fields to save.
		 * @param array  $input    Tool input.
		 * @param array  $existing Stored role, empty for a new role.
		 * @param string $id       Role ID, empty for a new role.
		 */
		$changes = apply_filters( 'wholesalex_mcp_role_to_storage', $changes, $input, $existing, $id );
		if ( ! is_array( $changes ) && ! is_wp_error( $changes ) ) {
			return Fields::invalid( __( 'The role settings could not be prepared.', 'wholesalex' ) );
		}
		return $changes;
	}

	/**
	 * Convert a stored role into tool output.
	 *
	 * @param string $id   Role ID.
	 * @param array  $role Stored role.
	 * @param bool   $full Whether to include the customer count.
	 * @return array
	 */
	private static function format( string $id, array $role, bool $full ): array {
		$restrictions = is_array( $role['_restrictions'] ?? null ) ? $role['_restrictions'] : array();
		$coupons      = $restrictions['_disable_coupon'] ?? ( $role['_disable_coupon'] ?? false );
		$approval     = array_search( (string) ( $role['user_status'] ?? 'global_setting' ), self::APPROVAL, true );
		$tax_display  = array_search( (string) ( $role['_display_price'] ?? '' ), self::TAX_DISPLAY, true );
		$output       = array(
			'id'                    => $id,
			'title'                 => (string) ( $role['_role_title'] ?? '' ),
			'kind'                  => self::kind( $id ),
			'deletable'             => wholesalex()->is_deletable_role( $id ),
			'registration_approval' => false === $approval ? 'global_setting' : (string) $approval,
			'tax_display'           => false === $tax_display ? 'store_default' : (string) $tax_display,
			'all_payment_methods'   => ! array_key_exists( '_payment_methods', $role ),
			'payment_method_ids'    => array_values( array_filter( array_map( 'strval', (array) ( $role['_payment_methods'] ?? array() ) ), 'strlen' ) ),
			'shipping_method_ids'   => array_values( array_filter( array_map( 'strval', (array) ( $role['_shipping_methods'] ?? array() ) ), 'strlen' ) ),
			'disable_coupons'       => in_array( $coupons, array( true, 1, '1', 'yes' ), true ),
			'auto_assign_by_spend'  => 'yes' === ( $role['_auto_role_migration'] ?? 'no' ),
			'spend_threshold'       => is_numeric( $role['_role_migration_threshold_value'] ?? null ) ? (float) $role['_role_migration_threshold_value'] : 0,
		);
		if ( $full ) {
			$output['customer_count'] = Role_Service::count_customers( $id );
		}

		/**
		 * Filters the role data returned by the WholesaleX AI tools.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $output Role data for the AI assistant.
		 * @param array  $role   Stored role.
		 * @param string $id     Role ID.
		 */
		return apply_filters( 'wholesalex_mcp_role_output', $output, $role, $id );
	}

	/**
	 * Find a stored role.
	 *
	 * @param string $id Role ID.
	 * @return array|\WP_Error
	 */
	private static function find( string $id ) {
		$role = wholesalex()->get_roles( 'by_id', $id );
		if ( empty( $role ) || ! is_array( $role ) ) {
			return new \WP_Error(
				'wholesalex_mcp_not_found',
				sprintf(
					/* translators: %s: role ID. */
					__( 'Role "%s" not found. Use the wholesalex-list-roles tool to find role IDs.', 'wholesalex' ),
					$id
				),
				array( 'status' => 404 )
			);
		}
		return $role;
	}

	/**
	 * Return the kind of a role.
	 *
	 * @param string $id Role ID, empty for a new role.
	 * @return string "b2c", "guest" or "b2b".
	 */
	private static function kind( string $id ): string {
		if ( 'wholesalex_b2c_users' === $id ) {
			return 'b2c';
		}
		return 'wholesalex_guest' === $id ? 'guest' : 'b2b';
	}

	/**
	 * Schema of a role ID.
	 *
	 * @return array
	 */
	private static function id_schema(): array {
		return Schema::string( __( 'Role ID from wholesalex-list-roles, for example wholesalex_b2b_role_1730000000000.', 'wholesalex' ), 100, 1 );
	}

	/**
	 * Input schema of the create and update tools.
	 *
	 * @param bool $for_create Whether the schema is for creating a role.
	 * @return array
	 */
	private static function role_schema( bool $for_create ): array {
		$properties = array();
		if ( ! $for_create ) {
			$properties['id'] = self::id_schema();
		}
		$properties['title']                 = Schema::string( __( 'Role name shown to the store owner, for example "Gold Wholesaler".', 'wholesalex' ), 100, 1 );
		$properties['registration_approval'] = Schema::enum( __( 'How new registrations for this role are approved. global_setting: use the registration form setting. email_confirmation: the customer confirms their email. auto_approve: approved at once. admin_approval: an administrator approves each account.', 'wholesalex' ), array_keys( self::APPROVAL ) );
		$properties['tax_display']           = Schema::enum( __( 'How prices are shown to this role. store_default: the WooCommerce setting. including_tax or excluding_tax: always that way.', 'wholesalex' ), array_keys( self::TAX_DISPLAY ) );
		$properties['payment_method_ids']    = Schema::id_list( $for_create ? __( 'Payment methods this role can use at checkout. Omit to allow every payment method that is enabled now; methods enabled later must be added with wholesalex-update-role. An empty list blocks every payment method. Find IDs with wholesalex-list-checkout-options.', 'wholesalex' ) : __( 'Payment methods this role can use at checkout. An empty list blocks every payment method. Find IDs with wholesalex-list-checkout-options.', 'wholesalex' ), 'string', 50 );
		$properties['shipping_method_ids']   = Schema::id_list( __( 'Shipping method instance IDs this role can use. An empty list allows every shipping method. Find IDs with wholesalex-list-checkout-options.', 'wholesalex' ), 'string', 100 );
		$properties['disable_coupons']       = Schema::boolean( __( 'true hides coupon fields from customers with this role.', 'wholesalex' ) );
		$properties['auto_assign_by_spend']  = Schema::boolean( __( 'B2B roles only. true moves customers into this role once their total spend reaches spend_threshold.', 'wholesalex' ) );
		$properties['spend_threshold']       = Schema::number( __( 'Total spend, in the store currency, that qualifies a customer for this role.', 'wholesalex' ), 0 );

		/**
		 * Filters the role properties accepted by the WholesaleX AI tools.
		 *
		 * Convert added properties with `wholesalex_mcp_role_to_storage`.
		 *
		 * @since 3.2.0
		 *
		 * @param array $properties Property schemas keyed by name.
		 * @param bool  $for_create Whether the schema is for creating a role.
		 */
		$properties = apply_filters( 'wholesalex_mcp_role_input_properties', $properties, $for_create );

		return Schema::object( is_array( $properties ) ? $properties : array(), $for_create ? array( 'title' ) : array( 'id' ) );
	}
}
