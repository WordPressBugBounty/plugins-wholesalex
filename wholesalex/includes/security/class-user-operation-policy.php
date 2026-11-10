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
	 * Whether editable roles are being read for wholesale assignment.
	 *
	 * @var bool
	 */
	private static $validating_wholesale_role = false;

	/**
	 * Distinguish wholesale assignment checks from the native role dropdown.
	 *
	 * @return bool
	 */
	public static function is_validating_wholesale_role() {
		return self::$validating_wholesale_role;
	}

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
			return self::denied( 'invalid_actor_or_operation' );
		}

		$self_service = 'edit' === $operation && ! empty( $context['self_service'] ) && $actor_id === $target_id;
		if ( 'request' !== $operation && ! $self_service && ! user_can( $actor_id, apply_filters( 'wholesalex_capability_access', 'manage_options' ) ) ) {
			return self::denied( 'missing_management_capability' );
		}
		if ( isset( $context['role'] ) && ! self::is_editable_wholesale_role( $context['role'] ) ) {
			$data    = array( 'status' => 400 );
			$message = __( 'This wholesale role cannot be assigned.', 'wholesalex' );
			if ( current_user_can( 'manage_options' ) && is_string( $context['role'] ) ) {
				$role = $context['role'];
				if ( '' === $role || sanitize_key( $role ) !== $role || in_array( $role, array( 'administrator', 'wholesalex_guest' ), true ) ) {
					$data['reason'] = 'invalid_role_id';
				} elseif ( ! in_array( $role, array_map( 'strval', wholesalex()->get_roles( 'ids' ) ), true ) ) {
					$data['reason'] = 'unconfigured_wholesale_role';
					$message        = __( 'The selected role is not configured in WholesaleX. Select a role from WholesaleX User Roles.', 'wholesalex' );
				} else {
					$data['reason'] = 'role_not_editable';
					$message        = __( 'The selected WholesaleX role is unavailable in WordPress editable roles. Check that the role exists and your role-management settings allow assigning it.', 'wholesalex' );
				}
			}
			return new \WP_Error( 'wholesalex_invalid_user_role', $message, $data );
		}
		if ( 'create' === $operation ) {
			if ( ! user_can( $actor_id, 'create_users' ) || ( ( isset( $context['role'] ) || ( isset( $context['status'] ) && 'active' === $context['status'] ) ) && ! user_can( $actor_id, 'promote_users' ) ) ) {
				return self::denied( 'missing_creation_capability' );
			}
			if ( ! $target_id ) {
				return true;
			}
		}
		if ( 'read' === $operation && ! $target_id ) {
			return user_can( $actor_id, 'list_users' ) ? true : self::denied( 'missing_list_capability' );
		}

		$target = get_userdata( $target_id );
		if ( ! $target || ! is_user_member_of_blog( $target_id, get_current_blog_id() ) ) {
			return self::denied( 'invalid_site_target' );
		}
		if ( 'read' === $operation ) {
			return user_can( $actor_id, 'list_users' ) && user_can( $actor_id, 'edit_user', $target_id ) ? true : self::denied( 'missing_read_capability' );
		}
		if ( $self_service ) {
			return user_can( $actor_id, 'edit_user', $target_id ) ? true : self::denied( 'missing_edit_capability' );
		}
		// Submitting a request records intent only; approval still enforces protected targets.
		if ( 'request' === $operation ) {
			return $actor_id === $target_id && isset( $context['role'] ) ? true : self::denied( 'invalid_role_request' );
		}
		// Role requests change wholesale membership only, never account status or administrator roles.
		if ( in_array( $operation, array( 'accept_role_request', 'reject_role_request' ), true ) ) {
			if ( ! user_can( $actor_id, 'edit_users' ) || ! user_can( $actor_id, 'edit_user', $target_id ) ) {
				return self::denied( 'missing_edit_capability' );
			}
			if ( ( is_multisite() && is_super_admin( $target_id ) && ! is_super_admin( $actor_id ) ) || ( ( in_array( 'administrator', (array) $target->roles, true ) || user_can( $target_id, 'manage_options' ) ) && ! user_can( $actor_id, 'manage_options' ) ) ) {
				return self::denied( 'protected_target' );
			}
			if ( 'accept_role_request' === $operation && ( ! user_can( $actor_id, 'promote_users' ) || ! user_can( $actor_id, 'promote_user', $target_id ) ) ) {
				return self::denied( 'missing_promotion_capability' );
			}
			return true;
		}
		// Administrators may approve wholesale membership without removing admin access.
		// Network targets still require a network administrator; destructive actions
		// and general wholesale overrides remain blocked for protected accounts.
		$admin_approval = 'approve' === $operation && user_can( $actor_id, 'manage_options' ) && ( ! is_multisite() || ! is_super_admin( $target_id ) || is_super_admin( $actor_id ) );
		if ( ! $admin_approval && ( ( is_multisite() && is_super_admin( $target_id ) ) || in_array( 'administrator', (array) $target->roles, true ) || user_can( $target_id, 'manage_options' ) ) ) {
			return self::denied( 'protected_target' );
		}
		if ( ! $admin_approval && $actor_id === $target_id && in_array( $operation, array( 'create', 'promote', 'delete', 'approve', 'reject' ), true ) ) {
			return self::denied( 'self_operation' );
		}
		if ( 'delete' === $operation ) {
			return user_can( $actor_id, 'delete_users' ) && user_can( $actor_id, 'delete_user', $target_id ) ? true : self::denied( 'missing_delete_capability' );
		}
		if ( ! user_can( $actor_id, 'edit_users' ) || ! user_can( $actor_id, 'edit_user', $target_id ) ) {
			return self::denied( 'missing_edit_capability' );
		}
		if ( in_array( $operation, array( 'promote', 'approve' ), true ) && ( ! user_can( $actor_id, 'promote_users' ) || ! user_can( $actor_id, 'promote_user', $target_id ) ) ) {
			return self::denied( 'missing_promotion_capability' );
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
		// Our native-dropdown filter hides wholesale roles for presentation only.
		// Keep every other editable_roles restriction in force during validation.
		$previous                        = self::$validating_wholesale_role;
		self::$validating_wholesale_role = true;
		try {
			$editable_roles = get_editable_roles();
		} finally {
			self::$validating_wholesale_role = $previous;
		}
		return in_array( $role_id, array_map( 'strval', wholesalex()->get_roles( 'ids' ) ), true ) && isset( $editable_roles[ $role_id ] );
	}

	/**
	 * Return a consistent denial with diagnostics restricted to administrators.
	 *
	 * @param string $reason Failed authorization check.
	 * @return \WP_Error
	 */
	private static function denied( $reason ) {
		$data = array( 'status' => 403 );
		if ( current_user_can( 'manage_options' ) ) {
			$data['reason'] = $reason;
		}
		return new \WP_Error( 'wholesalex_user_operation_forbidden', __( 'You are not allowed to perform this user operation.', 'wholesalex' ), $data );
	}
}
