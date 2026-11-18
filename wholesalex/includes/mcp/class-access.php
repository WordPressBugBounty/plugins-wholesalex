<?php
/**
 * Access policy for the WholesaleX AI tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Decides who may use the WholesaleX AI tools and which operations are allowed.
 *
 * Every entry point asks this class: the MCP transport, each ability's
 * permission check and each ability's execution. The rules therefore live in
 * one place and cannot drift apart.
 *
 * Two independent conditions must both hold:
 * 1. The connected WordPress user is an administrator (`manage_options`).
 *    This capability is deliberately not filterable, so shop managers,
 *    marketplace vendors and other roles never gain AI access through the
 *    wider WholesaleX menu access settings.
 * 2. The store owner turned the tools on and the access level permits the
 *    operation. The level is stored in its own option so that settings
 *    imports, resets or the general settings screen never change it.
 */
final class Access {

	/**
	 * Option holding the AI tools settings.
	 *
	 * @var string
	 */
	const OPTION = 'wholesalex_mcp_settings';

	/**
	 * Capability required for every AI tool request.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Access level: AI tools are turned off. This is the default.
	 *
	 * @var string
	 */
	const LEVEL_OFF = 'off';

	/**
	 * Access level: AI tools may only read data.
	 *
	 * @var string
	 */
	const LEVEL_READ = 'read';

	/**
	 * Access level: AI tools may read, create and edit data.
	 *
	 * @var string
	 */
	const LEVEL_WRITE = 'write';

	/**
	 * Access level: AI tools may also delete data.
	 *
	 * @var string
	 */
	const LEVEL_FULL = 'full';

	/**
	 * Operation kind: reading data.
	 *
	 * @var string
	 */
	const READ = 'read';

	/**
	 * Operation kind: creating or editing data.
	 *
	 * @var string
	 */
	const WRITE = 'write';

	/**
	 * Operation kind: deleting data.
	 *
	 * @var string
	 */
	const DELETE = 'delete';

	/**
	 * Return every supported access level, from most to least restrictive.
	 *
	 * @return string[]
	 */
	public static function levels(): array {
		return array( self::LEVEL_OFF, self::LEVEL_READ, self::LEVEL_WRITE, self::LEVEL_FULL );
	}

	/**
	 * Access levels with their label and description, for screens.
	 *
	 * @return array<string, string[]>
	 */
	public static function level_labels(): array {
		return array(
			self::LEVEL_OFF   => array(
				__( 'Off', 'wholesalex' ),
				__( 'AI assistants cannot use WholesaleX.', 'wholesalex' ),
			),
			self::LEVEL_READ  => array(
				__( 'Read only', 'wholesalex' ),
				__( 'Look up roles, rules, the registration form, products and customers. Nothing can be changed.', 'wholesalex' ),
			),
			self::LEVEL_WRITE => array(
				__( 'Read and write', 'wholesalex' ),
				__( 'Also create and edit roles, pricing rules, dynamic rules, registration fields and settings. Nothing can be deleted. Recommended for day-to-day use.', 'wholesalex' ),
			),
			self::LEVEL_FULL  => array(
				__( 'Full access', 'wholesalex' ),
				__( 'Also delete roles, rules and registration fields. Text that customers type, such as names, can reach the assistant and may try to steer it, so turn this on only while you need it and review what it does.', 'wholesalex' ),
			),
		);
	}

	/**
	 * Return the configured access level.
	 *
	 * Anything other than a known level, including a missing or corrupted
	 * option, resolves to "off".
	 *
	 * @return string
	 */
	public static function level(): string {
		$settings = get_option( self::OPTION, array() );
		$level    = is_array( $settings ) && isset( $settings['access'] ) && is_string( $settings['access'] ) ? $settings['access'] : self::LEVEL_OFF;

		return in_array( $level, self::levels(), true ) ? $level : self::LEVEL_OFF;
	}

	/**
	 * Store a new access level.
	 *
	 * Callers are responsible for authorizing the change; the admin screen
	 * checks the capability and a nonce before calling this.
	 *
	 * @param string $level New access level.
	 * @return bool True when the level is valid and stored.
	 */
	public static function set_level( string $level ): bool {
		if ( ! in_array( $level, self::levels(), true ) ) {
			return false;
		}

		$settings           = get_option( self::OPTION, array() );
		$settings           = is_array( $settings ) ? $settings : array();
		$settings['access'] = $level;

		update_option( self::OPTION, $settings, false );

		return self::level() === $level;
	}

	/**
	 * Whether the AI tools are turned on at any level.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return self::LEVEL_OFF !== self::level();
	}

	/**
	 * Whether an access level permits an operation.
	 *
	 * @param string      $operation One of the READ, WRITE or DELETE constants.
	 * @param string|null $level     Level to test. Defaults to the configured level.
	 * @return bool
	 */
	public static function level_allows( string $operation, $level = null ): bool {
		$level = is_string( $level ) ? $level : self::level();
		$rank  = array(
			self::LEVEL_OFF   => 0,
			self::LEVEL_READ  => 1,
			self::LEVEL_WRITE => 2,
			self::LEVEL_FULL  => 3,
		);
		$need  = array(
			self::READ   => 1,
			self::WRITE  => 2,
			self::DELETE => 3,
		);

		if ( ! isset( $rank[ $level ], $need[ $operation ] ) ) {
			return false;
		}

		return $rank[ $level ] >= $need[ $operation ];
	}

	/**
	 * Whether the current user may use the AI tools at all.
	 *
	 * @return bool
	 */
	public static function current_user_is_allowed(): bool {
		return is_user_logged_in() && current_user_can( self::CAPABILITY );
	}

	/**
	 * Authorize one operation for the current request.
	 *
	 * The messages are written for the AI assistant and the store owner, and
	 * never reveal more than whether the tools are available.
	 *
	 * @param string $operation One of the READ, WRITE or DELETE constants.
	 * @return true|\WP_Error
	 */
	public static function check( string $operation ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'wholesalex_mcp_unauthenticated',
				__( 'Sign in with a WordPress administrator account, through OAuth or an application password, to use the WholesaleX AI tools.', 'wholesalex' ),
				array( 'status' => 401 )
			);
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return new \WP_Error(
				'wholesalex_mcp_forbidden',
				__( 'Only store administrators can use the WholesaleX AI tools.', 'wholesalex' ),
				array( 'status' => 403 )
			);
		}

		$level = self::level();
		if ( self::level_allows( $operation, $level ) ) {
			return true;
		}

		if ( self::LEVEL_OFF === $level ) {
			$message = __( 'The WholesaleX AI tools are turned off. An administrator can turn them on in WholesaleX > MCP Tools.', 'wholesalex' );
		} elseif ( self::DELETE === $operation && self::LEVEL_WRITE === $level ) {
			$message = __( 'Deleting is turned off for the WholesaleX AI tools. An administrator can allow it with Full access in WholesaleX > MCP Tools. The new level applies right away, so try again once it is changed.', 'wholesalex' );
		} elseif ( self::DELETE === $operation ) {
			$message = __( 'The WholesaleX AI tools are in read-only mode. An administrator can allow deleting with Full access in WholesaleX > MCP Tools. The new level applies right away, so try again once it is changed.', 'wholesalex' );
		} else {
			$message = __( 'The WholesaleX AI tools are in read-only mode. An administrator can allow changes with Read and write in WholesaleX > MCP Tools. The new level applies right away, so try again once it is changed.', 'wholesalex' );
		}

		return new \WP_Error( 'wholesalex_mcp_access_level', $message, array( 'status' => 403 ) );
	}
}
