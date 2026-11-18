<?php
/**
 * WholesaleX role write operations shared by every entry point.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Creates, updates and deletes WholesaleX roles.
 *
 * Storage validation stays in `Functions::set_roles()` and
 * `Role_Data_Boundary`; this class adds the rules every entry point shares,
 * such as role ID generation and the default payment methods of a new role.
 */
final class Role_Service {

	/**
	 * Prefix of role IDs created by WholesaleX.
	 *
	 * @var string
	 */
	const ID_PREFIX = 'wholesalex_b2b_role_';

	/**
	 * Built-in roles that cannot be renamed or deleted.
	 *
	 * @var string[]
	 */
	const SYSTEM_ROLES = array( 'wholesalex_b2c_users', 'wholesalex_guest' );

	/**
	 * Give a role every enabled payment method when it has no explicit selection.
	 *
	 * An explicit selection, including an empty one, is kept as it is.
	 *
	 * @param array $role Role fields.
	 * @return array
	 */
	public static function with_default_payment_methods( array $role ): array {
		if ( ! array_key_exists( '_payment_methods', $role ) ) {
			$role['_payment_methods'] = self::enabled_payment_method_ids();
		}
		return $role;
	}

	/**
	 * Return the IDs of the enabled WooCommerce payment methods.
	 *
	 * @return string[]
	 */
	public static function enabled_payment_method_ids(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways ) {
			return array();
		}
		$ids = array();
		foreach ( WC()->payment_gateways->payment_gateways() as $id => $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				$ids[] = (string) $id;
			}
		}
		return $ids;
	}

	/**
	 * Generate a role ID that is not in use, in the admin screen's format.
	 *
	 * @return string
	 */
	public static function generate_id(): string {
		$number = (int) floor( microtime( true ) * 1000 );
		while ( ! empty( wholesalex()->get_roles( 'by_id', self::ID_PREFIX . $number ) ) || wp_roles()->is_role( self::ID_PREFIX . $number ) ) {
			++$number;
		}
		return self::ID_PREFIX . $number;
	}

	/**
	 * Create a role.
	 *
	 * @param array $role Role fields in the stored format. `_role_title` is required.
	 * @return string|\WP_Error New role ID.
	 */
	public static function create( array $role ) {
		$title = isset( $role['_role_title'] ) && is_string( $role['_role_title'] ) ? trim( $role['_role_title'] ) : '';
		if ( '' === $title ) {
			return new \WP_Error( 'wholesalex_invalid_role', __( 'A role needs a name.', 'wholesalex' ), array( 'status' => 400 ) );
		}

		$id                  = self::generate_id();
		$role                = self::with_default_payment_methods( $role );
		$role['id']          = $id;
		$role['_role_title'] = $title;

		$saved = wholesalex()->set_roles( $id, $role );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		if ( true !== $saved || empty( wholesalex()->get_roles( 'by_id', $id ) ) ) {
			return new \WP_Error( 'wholesalex_role_not_saved', __( 'The role could not be saved.', 'wholesalex' ), array( 'status' => 500 ) );
		}
		return $id;
	}

	/**
	 * Update an existing role. Omitted fields keep their saved values.
	 *
	 * @param string $id      Role ID.
	 * @param array  $changes Changed fields in the stored format.
	 * @return true|\WP_Error
	 */
	public static function update( string $id, array $changes ) {
		$existing = wholesalex()->get_roles( 'by_id', $id );
		if ( empty( $existing ) ) {
			return self::not_found( $id );
		}
		if ( empty( $changes ) ) {
			return true;
		}
		if ( in_array( $id, self::SYSTEM_ROLES, true ) && isset( $changes['_role_title'] ) && ( $existing['_role_title'] ?? '' ) !== $changes['_role_title'] ) {
			return new \WP_Error( 'wholesalex_system_role', __( 'Built-in roles cannot be renamed.', 'wholesalex' ), array( 'status' => 400 ) );
		}

		$saved = wholesalex()->set_roles( $id, $changes );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return true === $saved ? true : new \WP_Error( 'wholesalex_role_not_saved', __( 'The role could not be saved.', 'wholesalex' ), array( 'status' => 500 ) );
	}

	/**
	 * Delete a role and unassign its customers.
	 *
	 * @param string $id Role ID.
	 * @return true|\WP_Error
	 */
	public static function delete( string $id ) {
		if ( empty( wholesalex()->get_roles( 'by_id', $id ) ) ) {
			return self::not_found( $id );
		}
		if ( ! wholesalex()->is_deletable_role( $id ) ) {
			return new \WP_Error( 'wholesalex_system_role', __( 'Only B2B roles can be deleted.', 'wholesalex' ), array( 'status' => 400 ) );
		}

		$deleted = wholesalex()->delete_role( $id );
		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}
		return $deleted ? true : new \WP_Error( 'wholesalex_role_not_deleted', __( 'Role could not be deleted.', 'wholesalex' ), array( 'status' => 500 ) );
	}

	/**
	 * Count the customers assigned to a role.
	 *
	 * @param string $id Role ID.
	 * @return int
	 */
	public static function count_customers( string $id ): int {
		$query = new \WP_User_Query(
			array(
				'meta_key'    => '__wholesalex_role', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- WholesaleX stores the assigned role as user metadata.
				'meta_value'  => $id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- WholesaleX stores the assigned role as user metadata.
				'fields'      => 'ID',
				'number'      => 1,
				'count_total' => true,
			)
		);
		return (int) $query->get_total();
	}

	/**
	 * Build a "role not found" error.
	 *
	 * @param string $id Role ID.
	 * @return \WP_Error
	 */
	private static function not_found( string $id ): \WP_Error {
		return new \WP_Error(
			'wholesalex_role_not_found',
			sprintf(
				/* translators: %s: role ID. */
				__( 'Role "%s" not found.', 'wholesalex' ),
				$id
			),
			array( 'status' => 404 )
		);
	}
}
