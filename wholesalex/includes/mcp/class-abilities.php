<?php
/**
 * Registers the WholesaleX abilities.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

use WHOLESALEX\MCP\Abilities\Dynamic_Rule_Abilities;
use WHOLESALEX\MCP\Abilities\Lookup_Abilities;
use WHOLESALEX\MCP\Abilities\Pricing_Rule_Abilities;
use WHOLESALEX\MCP\Abilities\Registration_Abilities;
use WHOLESALEX\MCP\Abilities\Role_Abilities;
use WHOLESALEX\MCP\Abilities\Settings_Abilities;
use WHOLESALEX\MCP\Abilities\Store_Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Turns WholesaleX tool definitions into WordPress abilities.
 *
 * Each definition is wrapped the same way:
 * - It is registered at every level while the tools are on. AI clients load
 *   the tool list once per session and are never told it changed, so hiding
 *   tools by level would keep a raised level from working until the client
 *   reconnects.
 * - Its permission callback and its execution both check the access policy,
 *   so a tool above the current level is refused with the reason, and the
 *   check still holds if another plugin replaces the permission callback
 *   through the `wp_register_ability_args` filter.
 * - Changes and refused attempts are written to the activity log.
 * - Unexpected exceptions become a plain error without internal details.
 *
 * The abilities are not exposed through the WordPress REST API, and they are
 * not exposed through the MCP Adapter's shared default server unless the
 * `wholesalex_mcp_expose_on_default_server` filter allows it. AI assistants
 * reach them through the dedicated WholesaleX MCP server.
 */
final class Abilities {

	/**
	 * Ability category slug.
	 *
	 * @var string
	 */
	const CATEGORY = 'wholesalex';

	/**
	 * Register the WholesaleX ability category.
	 *
	 * @return void
	 */
	public static function register_category(): void {
		if ( ! Access::is_enabled() || ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( self::CATEGORY ) ) {
			return;
		}
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'WholesaleX', 'wholesalex' ),
				'description' => __( 'Wholesale customer roles, role restrictions, pricing rules and registration forms managed by WholesaleX.', 'wholesalex' ),
			)
		);
	}

	/**
	 * Register every ability while the AI tools are on.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! Access::is_enabled() || ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		foreach ( self::definitions() as $name => $definition ) {
			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $name ) ) {
				continue;
			}
			wp_register_ability( $name, self::to_ability_args( $name, $definition ) );
		}
	}

	/**
	 * Return the names of the registered WholesaleX abilities.
	 *
	 * Calling this initializes the abilities registry when needed.
	 *
	 * @return string[]
	 */
	public static function registered_names(): array {
		if ( ! Access::is_enabled() || ! function_exists( 'wp_has_ability' ) ) {
			return array();
		}
		$names = array();
		foreach ( array_keys( self::definitions() ) as $name ) {
			if ( wp_has_ability( $name ) ) {
				$names[] = $name;
			}
		}
		return $names;
	}

	/**
	 * Return every valid tool definition, keyed by ability name.
	 *
	 * Extensions add tools with the `wholesalex_mcp_ability_definitions`
	 * filter. Definitions are validated here, so a malformed extension entry
	 * is skipped instead of breaking every other tool.
	 *
	 * @return array<string, array>
	 */
	public static function definitions(): array {
		$definitions = array_merge(
			Store_Abilities::definitions(),
			Role_Abilities::definitions(),
			Pricing_Rule_Abilities::definitions(),
			Dynamic_Rule_Abilities::definitions(),
			Registration_Abilities::definitions(),
			Lookup_Abilities::definitions(),
			Settings_Abilities::definitions()
		);

		/**
		 * Filters the WholesaleX AI tool definitions.
		 *
		 * Each definition is an array with `label`, `description`, `operation`
		 * ("read", "write" or "delete"), `input_schema`, `callback` and,
		 * optionally, `output_schema` and `idempotent`. Every definition gets
		 * the administrator-only permission check, the access level check and
		 * activity logging automatically. Names must match
		 * `wholesalex/<lowercase-name>`.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, array> $definitions Definitions keyed by ability name.
		 */
		$definitions = apply_filters( 'wholesalex_mcp_ability_definitions', $definitions );

		$valid = array();
		foreach ( is_array( $definitions ) ? $definitions : array() as $name => $definition ) {
			if ( self::is_valid_definition( $name, $definition ) ) {
				$valid[ $name ] = $definition;
			}
		}
		return $valid;
	}

	/**
	 * Check the shape of one definition.
	 *
	 * @param mixed $name       Ability name.
	 * @param mixed $definition Definition.
	 * @return bool
	 */
	private static function is_valid_definition( $name, $definition ): bool {
		return is_string( $name )
			&& 1 === preg_match( '/^wholesalex\/[a-z0-9-]+$/', $name )
			&& is_array( $definition )
			&& isset( $definition['label'], $definition['description'], $definition['operation'], $definition['input_schema'], $definition['callback'] )
			&& is_string( $definition['label'] )
			&& is_string( $definition['description'] )
			&& in_array( $definition['operation'], array( Access::READ, Access::WRITE, Access::DELETE ), true )
			&& is_array( $definition['input_schema'] )
			&& is_callable( $definition['callback'] )
			&& ( ! isset( $definition['output_schema'] ) || is_array( $definition['output_schema'] ) );
	}

	/**
	 * Build the `wp_register_ability()` arguments for one definition.
	 *
	 * @param string $name       Ability name.
	 * @param array  $definition Definition.
	 * @return array
	 */
	private static function to_ability_args( string $name, array $definition ): array {
		$operation  = $definition['operation'];
		$readonly   = Access::READ === $operation;
		$idempotent = isset( $definition['idempotent'] ) ? (bool) $definition['idempotent'] : Access::WRITE !== $operation;

		/**
		 * Filters whether WholesaleX tools are also listed on the MCP Adapter's
		 * shared default server.
		 *
		 * The dedicated WholesaleX server is always available. The shared
		 * server is off by default because every plugin's public abilities
		 * meet there, behind a transport that only requires a signed-in user.
		 * The administrator-only check still applies to every call.
		 *
		 * @since 3.2.0
		 *
		 * @param bool   $expose Whether to expose the tool. Default false.
		 * @param string $name   Ability name.
		 */
		$on_default_server = (bool) apply_filters( 'wholesalex_mcp_expose_on_default_server', false, $name );

		$args = array(
			'label'               => $definition['label'],
			'description'         => $definition['description'],
			'category'            => self::CATEGORY,
			'input_schema'        => $definition['input_schema'],
			'execute_callback'    => static function ( $input = array() ) use ( $name, $definition ) {
				return Abilities::execute( $name, $definition, $input );
			},
			'permission_callback' => static function () use ( $name, $operation ) {
				return Abilities::authorize( $name, $operation );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'      => $readonly,
					'destructive'   => Access::DELETE === $operation,
					'idempotent'    => $idempotent,
					'openWorldHint' => false,
				),
				'show_in_rest' => false,
				'mcp'          => array(
					'public' => $on_default_server,
					'type'   => 'tool',
				),
			),
		);
		if ( isset( $definition['output_schema'] ) ) {
			$args['output_schema'] = $definition['output_schema'];
		}
		return $args;
	}

	/**
	 * Permission callback shared by every WholesaleX ability.
	 *
	 * @param string $name      Ability name.
	 * @param string $operation Operation kind.
	 * @return true|\WP_Error
	 */
	public static function authorize( string $name, string $operation ) {
		$allowed = Access::check( $operation );
		if ( is_wp_error( $allowed ) && is_user_logged_in() ) {
			Activity_Log::record( $name, Activity_Log::DENIED, '', $allowed->get_error_code() );
		}
		return $allowed;
	}

	/**
	 * Execute callback shared by every WholesaleX ability.
	 *
	 * @param string $name       Ability name.
	 * @param array  $definition Definition.
	 * @param mixed  $input      Validated input.
	 * @return mixed|\WP_Error
	 */
	public static function execute( string $name, array $definition, $input ) {
		// Defense in depth: the permission callback can be replaced by other code.
		$allowed = self::authorize( $name, $definition['operation'] );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		// Core validates input against the schema but passes it on as sent, so
		// coerce it to the schema types: (bool) "false" would otherwise be true.
		$input = rest_sanitize_value_from_schema( is_array( $input ) ? $input : array(), $definition['input_schema'], 'input' );
		if ( is_wp_error( $input ) ) {
			return $input;
		}
		$input = is_array( $input ) ? $input : array();
		try {
			$result = Pro_Features::check_input( $name, $input );
			if ( ! is_wp_error( $result ) ) {
				$result = call_user_func( $definition['callback'], $input );
			}
		} catch ( \Throwable $error ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( $name . ': ' . $error->getMessage(), array( 'source' => 'wholesalex-mcp' ) );
			}
			$result = new \WP_Error( 'wholesalex_mcp_failed', __( 'The WholesaleX tool stopped because of an unexpected error. Details are in the WooCommerce log under "wholesalex-mcp".', 'wholesalex' ) );
		}

		if ( Access::READ !== $definition['operation'] ) {
			Activity_Log::record(
				$name,
				is_wp_error( $result ) ? Activity_Log::ERROR : Activity_Log::SUCCESS,
				self::object_id( $result, $input ),
				is_wp_error( $result ) ? (string) $result->get_error_code() : ''
			);
		}

		return $result;
	}

	/**
	 * Find the ID of the record a tool changed, for the activity log.
	 *
	 * @param mixed $result Tool result.
	 * @param array $input  Tool input.
	 * @return string
	 */
	private static function object_id( $result, array $input ): string {
		if ( is_array( $result ) ) {
			if ( isset( $result['id'] ) && is_scalar( $result['id'] ) ) {
				return (string) $result['id'];
			}
			foreach ( $result as $value ) {
				if ( is_array( $value ) && isset( $value['id'] ) && is_scalar( $value['id'] ) ) {
					return (string) $value['id'];
				}
			}
		}
		foreach ( array( 'id', 'name' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ) {
				return (string) $input[ $key ];
			}
		}
		return '';
	}
}
