<?php
/**
 * WordPress operation and target authorization for WholesaleX user handlers.
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Authorize an operation before reading account data or changing it. */
class User_Operation_Policy {

	/**
	 * Check the actor, current site, operation capabilities and requested role.
	 *
	 * A zero target is valid only for creation and listing. Native profile hooks
	 * may explicitly opt into ordinary self-service fields; that context never
	 * authorizes wholesale settings, roles or account status changes.
	 *
	 * @param string $operation Operation name.
	 * @param int    $actor_id Acting WordPress user.
	 * @param int    $target_id Target user, or zero for creation/listing.
	 * @param array  $context Optional role, status or self_service context.
	 * @return true|\WP_Error
	 */
	public static function authorize( $operation, $actor_id, $target_id = 0, array $context = array() ) {
		$actor_id  = absint( $actor_id );
		$target_id = absint( $target_id );
		$actor     = get_userdata( $actor_id );
		if ( ! $actor || ! in_array( $operation, array( 'create', 'edit', 'promote', 'delete', 'approve', 'reject', 'read', 'request', 'accept_role_request', 'reject_role_request' ), true ) ) {
			return self::denied();
		}

		$self_service = 'edit' === $operation && ! empty( $context['self_service'] ) && $actor_id === $target_id;
		if ( 'request' !== $operation && ! $self_service && ! user_can( $actor_id, apply_filters( 'wholesalex_capability_access', 'manage_options' ) ) ) {
			return self::denied();
		}
		if ( isset( $context['role'] ) && ! self::is_editable_wholesale_role( $context['role'] ) ) {
			return new \WP_Error( 'wholesalex_invalid_user_role', __( 'This wholesale role cannot be assigned.', 'wholesalex' ), array( 'status' => 400 ) );
		}
		if ( 'create' === $operation ) {
			if ( ! user_can( $actor_id, 'create_users' ) || ( ( isset( $context['role'] ) || ( isset( $context['status'] ) && 'active' === $context['status'] ) ) && ! user_can( $actor_id, 'promote_users' ) ) ) {
				return self::denied();
			}
			if ( ! $target_id ) {
				return true;
			}
		}
		if ( 'read' === $operation && ! $target_id ) {
			return user_can( $actor_id, 'list_users' ) ? true : self::denied();
		}

		$target = get_userdata( $target_id );
		if ( ! $target || ! is_user_member_of_blog( $target_id, get_current_blog_id() ) ) {
			return self::denied();
		}
		if ( 'read' === $operation ) {
			return user_can( $actor_id, 'list_users' ) && user_can( $actor_id, 'edit_user', $target_id ) ? true : self::denied();
		}
		if ( $self_service ) {
			return user_can( $actor_id, 'edit_user', $target_id ) ? true : self::denied();
		}
		// Submitting a request records intent only; approval still enforces protected targets.
		if ( 'request' === $operation ) {
			return $actor_id === $target_id && isset( $context['role'] ) ? true : self::denied();
		}
		// Role requests change wholesale membership only, never account status or administrator roles.
		if ( in_array( $operation, array( 'accept_role_request', 'reject_role_request' ), true ) ) {
			if ( ! user_can( $actor_id, 'edit_users' ) || ! user_can( $actor_id, 'edit_user', $target_id ) ) {
				return self::denied();
			}
			if ( ( is_multisite() && is_super_admin( $target_id ) && ! is_super_admin( $actor_id ) ) || ( ( in_array( 'administrator', (array) $target->roles, true ) || user_can( $target_id, 'manage_options' ) ) && ! user_can( $actor_id, 'manage_options' ) ) ) {
				return self::denied();
			}
			if ( 'accept_role_request' === $operation && ( ! user_can( $actor_id, 'promote_users' ) || ! user_can( $actor_id, 'promote_user', $target_id ) ) ) {
				return self::denied();
			}
			return true;
		}
		// Network administrators and site administrators are never wholesale targets.
		if ( ( is_multisite() && is_super_admin( $target_id ) ) || in_array( 'administrator', (array) $target->roles, true ) || user_can( $target_id, 'manage_options' ) ) {
			return self::denied();
		}
		if ( $actor_id === $target_id && in_array( $operation, array( 'create', 'promote', 'delete', 'approve', 'reject' ), true ) ) {
			return self::denied();
		}
		if ( 'delete' === $operation ) {
			return user_can( $actor_id, 'delete_users' ) && user_can( $actor_id, 'delete_user', $target_id ) ? true : self::denied();
		}
		if ( ! user_can( $actor_id, 'edit_users' ) || ! user_can( $actor_id, 'edit_user', $target_id ) ) {
			return self::denied();
		}
		if ( in_array( $operation, array( 'promote', 'approve' ), true ) && ( ! user_can( $actor_id, 'promote_users' ) || ! user_can( $actor_id, 'promote_user', $target_id ) ) ) {
			return self::denied();
		}
		return true;
	}

	/**
	 * Intersect configured wholesale roles with WordPress editable roles.
	 *
	 * @param string $role_id Requested role ID.
	 * @return bool
	 */
	public static function is_editable_wholesale_role( $role_id ) {
		if ( ! is_string( $role_id ) || '' === $role_id || sanitize_key( $role_id ) !== $role_id || 'wholesalex_guest' === $role_id || 'administrator' === $role_id ) {
			return false;
		}
		if ( ! function_exists( 'get_editable_roles' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		$editable_roles = get_editable_roles();
		return in_array( $role_id, array_map( 'strval', wholesalex()->get_roles( 'ids' ) ), true ) && isset( $editable_roles[ $role_id ] );
	}

	/**
	 * Return a consistent denial without disclosing target details.
	 *
	 * @return \WP_Error
	 */
	private static function denied() {
		return new \WP_Error( 'wholesalex_user_operation_forbidden', __( 'You are not allowed to perform this user operation.', 'wholesalex' ), array( 'status' => 403 ) );
	}
}
