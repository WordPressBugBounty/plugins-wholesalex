<?php
/**
 * Dynamic rule tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP\Abilities;

use WHOLESALEX\Dynamic_Rule_Record;
use WHOLESALEX\Dynamic_Rules_Service;
use WHOLESALEX\MCP\Access;
use WHOLESALEX\MCP\Dynamic_Rule_Types;
use WHOLESALEX\MCP\Fields;
use WHOLESALEX\MCP\Pro_Features;
use WHOLESALEX\MCP\Rule_Targets;
use WHOLESALEX\MCP\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Lists, reads, creates, updates and deletes dynamic rules.
 *
 * Dynamic rules cover discounts, payment and shipping restrictions, minimum
 * quantities, tax exemptions and "buy X get one" offers. Writes go through
 * `Dynamic_Rules_Service`, the same path the rule editor uses.
 */
final class Dynamic_Rule_Abilities {

	/**
	 * Return the tool definitions.
	 *
	 * @return array<string, array>
	 */
	public static function definitions(): array {
		$guidance = __( 'Legacy tool. Use only when the user explicitly requests dynamic rules or identifies an existing dynamic rule to manage. For all role restrictions, including general, product visibility, purchasing, checkout limits, price visibility and tax rules, use User Roles restrictions with wholesalex-update-role by default. Never fall back to dynamic rules when a Pro role control is unavailable.', 'wholesalex' );
		return array(
			'wholesalex/list-dynamic-rules'  => array(
				'label'        => __( 'List dynamic rules', 'wholesalex' ),
				'description'  => $guidance . ' ' . __( 'Lists WholesaleX dynamic rules, newest first, with their type, status, audience, product scope and schedule.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::object(
					Schema::list_properties( __( 'Only rules whose title contains this text.', 'wholesalex' ) ) + array(
						'type'   => Schema::string( __( 'Only rules of this type, for example product_discount.', 'wholesalex' ), 50 ),
						'active' => Schema::boolean( __( 'true lists only active rules, false only inactive rules.', 'wholesalex' ) ),
					)
				),
				'callback'     => array( __CLASS__, 'list_rules' ),
			),
			'wholesalex/get-dynamic-rule'    => array(
				'label'        => __( 'Get a dynamic rule', 'wholesalex' ),
				'description'  => $guidance . ' ' . __( 'Returns every setting of one dynamic rule.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::object( array( 'id' => self::id_schema() ), array( 'id' ) ),
				'callback'     => array( __CLASS__, 'get_rule' ),
			),
			'wholesalex/create-dynamic-rule' => array(
				'label'        => __( 'Create a dynamic rule', 'wholesalex' ),
				'description'  => $guidance . ' ' . __( 'Creates a dynamic rule. Pick a type, then put that type\'s settings in the object named after the type. New rules are inactive unless active is true.', 'wholesalex' ),
				'operation'    => Access::WRITE,
				'input_schema' => self::rule_schema( true ),
				'callback'     => array( __CLASS__, 'create_rule' ),
			),
			'wholesalex/update-dynamic-rule' => array(
				'label'        => __( 'Update a dynamic rule', 'wholesalex' ),
				'description'  => $guidance . ' ' . __( 'Changes an existing dynamic rule. Only the fields you send change; everything else keeps its value. Use active to turn the rule on or off. The rule type cannot change; create a new rule instead.', 'wholesalex' ),
				'operation'    => Access::WRITE,
				'idempotent'   => true,
				'input_schema' => self::rule_schema( false ),
				'callback'     => array( __CLASS__, 'update_rule' ),
			),
			'wholesalex/delete-dynamic-rule' => array(
				'label'        => __( 'Delete a dynamic rule', 'wholesalex' ),
				'description'  => $guidance . ' ' . __( 'Permanently deletes a dynamic rule. To pause a rule instead, update it with active false.', 'wholesalex' ),
				'operation'    => Access::DELETE,
				'input_schema' => Schema::object( array( 'id' => self::id_schema() ), array( 'id' ) ),
				'callback'     => array( __CLASS__, 'delete_rule' ),
			),
		);
	}

	/**
	 * List rules.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function list_rules( array $input ): array {
		$search        = strtolower( trim( (string) ( $input['search'] ?? '' ) ) );
		$type_filter   = isset( $input['type'] ) ? (string) $input['type'] : null;
		$active_filter = isset( $input['active'] ) ? (bool) $input['active'] : null;
		$rules         = array();
		foreach ( wholesalex()->get_dynamic_rules() as $id => $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}
			$rule['id'] = (string) ( $rule['id'] ?? $id );
			if ( null !== $type_filter && (string) ( $rule['_rule_type'] ?? '' ) !== $type_filter ) {
				continue;
			}
			if ( null !== $active_filter && self::is_active( $rule ) !== $active_filter ) {
				continue;
			}
			if ( '' !== $search && false === strpos( strtolower( (string) ( $rule['_rule_title'] ?? '' ) ), $search ) ) {
				continue;
			}
			$rules[] = $rule;
		}

		usort(
			$rules,
			static function ( $a, $b ) {
				return strnatcmp( (string) $b['id'], (string) $a['id'] );
			}
		);

		$offset = max( 0, (int) ( $input['offset'] ?? 0 ) );
		$limit  = max( 1, min( 100, (int) ( $input['limit'] ?? 20 ) ) );

		return array(
			'total' => count( $rules ),
			'rules' => array_map(
				static function ( $rule ) {
					return Dynamic_Rule_Abilities::format( $rule, false );
				},
				array_slice( $rules, $offset, $limit )
			),
		);
	}

	/**
	 * Get one rule.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function get_rule( array $input ) {
		$rule = self::find( (string) $input['id'] );
		return is_wp_error( $rule ) ? $rule : array( 'rule' => self::format( $rule, true ) );
	}

	/**
	 * Create a rule.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function create_rule( array $input ) {
		$types = Dynamic_Rule_Types::types();
		$type  = (string) ( $input['type'] ?? '' );
		if ( ! isset( $types[ $type ] ) ) {
			if ( in_array( $type, Pro_Features::DYNAMIC_TYPES, true ) ) {
				return Pro_Features::required();
			}
			/* translators: %s: allowed rule types. */
			return Fields::invalid( sprintf( __( 'Unknown rule type. Use one of: %s.', 'wholesalex' ), implode( ', ', array_keys( $types ) ) ) );
		}
		$foreign = self::foreign_settings( $input, $types, $type );
		if ( is_wp_error( $foreign ) ) {
			return $foreign;
		}

		$record = array(
			'_rule_title'  => sanitize_text_field( (string) $input['title'] ),
			'_rule_type'   => $type,
			'_rule_status' => ! empty( $input['active'] ) ? 'yes' : 'no',
		);
		$input += array( 'product_scope' => 'all_products' );

		$patch = self::build_patch( $input, array(), $types[ $type ], $type );
		if ( is_wp_error( $patch ) ) {
			return $patch;
		}
		$record = array_replace( $record, $patch );

		$id    = self::new_id();
		$saved = Dynamic_Rules_Service::save( $id, $record );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return self::saved( $id );
	}

	/**
	 * Update a rule.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function update_rule( array $input ) {
		$id       = (string) $input['id'];
		$existing = self::find( $id );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		$types      = Dynamic_Rule_Types::types();
		$type       = (string) ( $existing['_rule_type'] ?? '' );
		$definition = $types[ $type ] ?? null;
		if ( null === $definition && in_array( $type, Pro_Features::DYNAMIC_TYPES, true ) && ( ! empty( $input['active'] ) || array_key_exists( $type, $input ) ) ) {
			return Pro_Features::required();
		}
		$foreign = self::foreign_settings( $input, $types, $type );
		if ( is_wp_error( $foreign ) ) {
			return $foreign;
		}
		if ( null === $definition && array_key_exists( $type, $input ) ) {
			return Fields::invalid( __( 'This rule type is managed by an extension that is not active, so its settings cannot be changed here.', 'wholesalex' ) );
		}

		$patch = array();
		if ( array_key_exists( 'title', $input ) ) {
			$patch['_rule_title'] = sanitize_text_field( (string) $input['title'] );
		}
		if ( array_key_exists( 'active', $input ) ) {
			$patch['_rule_status'] = ! empty( $input['active'] ) ? 'yes' : 'no';
		}

		$changes = self::build_patch( $input, $existing, $definition ?? array( 'fields' => array() ), $type );
		if ( is_wp_error( $changes ) ) {
			return $changes;
		}
		$patch = array_replace( $patch, $changes );
		if ( empty( $patch ) ) {
			return array( 'rule' => self::format( $existing, true ) );
		}

		$saved = Dynamic_Rules_Service::save( $id, array_replace( $existing, $patch ) );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return self::saved( $id );
	}

	/**
	 * Delete a rule.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function delete_rule( array $input ) {
		$id   = (string) $input['id'];
		$rule = self::find( $id );
		if ( is_wp_error( $rule ) ) {
			return $rule;
		}
		$deleted = Dynamic_Rules_Service::delete( $id );
		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}
		return array(
			'deleted' => true,
			'id'      => $id,
			'title'   => (string) ( $rule['_rule_title'] ?? '' ),
		);
	}

	/**
	 * Convert a stored rule into tool output.
	 *
	 * @param array $rule Stored rule.
	 * @param bool  $full Whether to include settings, conditions and selections.
	 * @return array
	 */
	public static function format( array $rule, bool $full ): array {
		$types = Dynamic_Rule_Types::types();
		$type  = (string) ( $rule['_rule_type'] ?? '' );
		$limit = is_array( $rule['limit'] ?? null ) ? $rule['limit'] : array();

		$output = array(
			'id'         => (string) ( $rule['id'] ?? '' ),
			'title'      => (string) ( $rule['_rule_title'] ?? '' ),
			'type'       => $type,
			'type_label' => (string) ( $types[ $type ]['label'] ?? $type ),
			'active'     => self::is_active( $rule ),
		);

		$audience = Rule_Targets::describe_choice( Dynamic_Rule_Types::audiences(), $rule, '_rule_for', 'applies_to' );
		$scope    = Rule_Targets::describe_choice( Dynamic_Rule_Types::product_scopes(), $rule, '_product_filter', 'product_scope' );
		if ( ! $full ) {
			$audience = array( 'applies_to' => $audience['applies_to'] );
			$scope    = array( 'product_scope' => $scope['product_scope'] );
		}
		$output += $audience + $scope;

		$output['start_date']  = (string) ( $limit['_start_date'] ?? '' );
		$output['end_date']    = (string) ( $limit['_end_date'] ?? '' );
		$output['usage_limit'] = is_numeric( $limit['_usage_limit'] ?? null ) ? (int) $limit['_usage_limit'] : 0;
		$output['times_used']  = is_numeric( $limit['usages_count'] ?? null ) ? (int) $limit['usages_count'] : 0;

		if ( $full ) {
			$payload              = is_array( $rule[ $type ] ?? null ) ? $rule[ $type ] : array();
			$output['settings']   = isset( $types[ $type ] ) ? Fields::from_storage( $types[ $type ]['fields'], $payload ) : $payload;
			$output['conditions'] = Rule_Targets::conditions_from_storage( $rule['conditions']['tiers'] ?? array() );
			$output['editable']   = isset( $types[ $type ] );
		}
		return $output;
	}

	/**
	 * Build the stored changes for targets, settings, conditions and schedule.
	 *
	 * @param array  $input      Tool input.
	 * @param array  $existing   Stored rule, empty for a new rule.
	 * @param array  $definition Rule type definition.
	 * @param string $type       Rule type.
	 * @return array|\WP_Error
	 */
	private static function build_patch( array $input, array $existing, array $definition, string $type ) {
		$patch = array();

		$ok = Rule_Targets::apply_choice( Dynamic_Rule_Types::audiences(), 'applies_to', '_rule_for', $input, $existing, $patch );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		$ok = Rule_Targets::apply_choice( Dynamic_Rule_Types::product_scopes(), 'product_scope', '_product_filter', $input, $existing, $patch );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		if ( empty( $existing ) || array_key_exists( $type, $input ) ) {
			$stored  = is_array( $existing[ $type ] ?? null ) ? $existing[ $type ] : array();
			$payload = Fields::to_storage( $definition['fields'], is_array( $input[ $type ] ?? null ) ? $input[ $type ] : array(), $stored, (string) ( $definition['label'] ?? $type ) );
			if ( is_wp_error( $payload ) ) {
				return $payload;
			}
			if ( isset( $definition['validate'] ) && is_callable( $definition['validate'] ) ) {
				$valid = call_user_func( $definition['validate'], $payload );
				if ( is_wp_error( $valid ) ) {
					return $valid;
				}
			}
			$patch[ $type ] = $payload;
		}

		if ( array_key_exists( 'conditions', $input ) ) {
			$rows = Rule_Targets::conditions_to_storage( $input['conditions'], Dynamic_Rule_Types::condition_fields() );
			if ( is_wp_error( $rows ) ) {
				return $rows;
			}
			$conditions          = is_array( $existing['conditions'] ?? null ) ? $existing['conditions'] : array();
			$conditions['tiers'] = $rows;
			$patch['conditions'] = $conditions;
		}

		$limit = self::limit_patch( $input, is_array( $existing['limit'] ?? null ) ? $existing['limit'] : array() );
		if ( is_wp_error( $limit ) ) {
			return $limit;
		}
		if ( null !== $limit ) {
			$patch['limit'] = $limit;
		}

		return $patch;
	}

	/**
	 * Build the stored schedule and usage limit.
	 *
	 * @param array $input    Tool input.
	 * @param array $existing Stored limit.
	 * @return array|null|\WP_Error Complete limit, or null when unchanged.
	 */
	private static function limit_patch( array $input, array $existing ) {
		$keys = array(
			'start_date'  => '_start_date',
			'end_date'    => '_end_date',
			'usage_limit' => '_usage_limit',
		);
		if ( empty( array_intersect_key( $input, $keys ) ) ) {
			return null;
		}

		$limit = $existing;
		foreach ( array( 'start_date', 'end_date' ) as $field ) {
			if ( ! array_key_exists( $field, $input ) ) {
				continue;
			}
			$date = (string) $input[ $field ];
			if ( '' !== $date && ! Dynamic_Rule_Record::parse_schedule_date( $date ) ) {
				/* translators: %s: field name. */
				return Fields::invalid( sprintf( __( '"%s" is not a valid calendar date.', 'wholesalex' ), $field ) );
			}
			$limit[ $keys[ $field ] ] = $date;
		}
		if ( array_key_exists( 'usage_limit', $input ) ) {
			$usage                 = (int) $input['usage_limit'];
			$limit['_usage_limit'] = $usage > 0 ? (string) $usage : '';
		}

		$start = (string) ( $limit['_start_date'] ?? '' );
		$end   = (string) ( $limit['_end_date'] ?? '' );
		if ( '' !== $start && '' !== $end && Dynamic_Rule_Record::parse_schedule_date( $start ) > Dynamic_Rule_Record::parse_schedule_date( $end ) ) {
			return Fields::invalid( __( 'The start date must be on or before the end date.', 'wholesalex' ) );
		}
		return $limit;
	}

	/**
	 * Reject settings objects of other rule types.
	 *
	 * @param array  $input Tool input.
	 * @param array  $types Rule types.
	 * @param string $type  Rule type of this rule.
	 * @return true|\WP_Error
	 */
	private static function foreign_settings( array $input, array $types, string $type ) {
		foreach ( array_keys( $types ) as $other ) {
			if ( $other !== $type && array_key_exists( $other, $input ) ) {
				return Fields::invalid(
					sprintf(
						/* translators: 1: settings object, 2: rule type. */
						__( 'The "%1$s" settings do not apply to a %2$s rule. Send settings in the object named after the rule type.', 'wholesalex' ),
						$other,
						$type
					)
				);
			}
		}
		return true;
	}

	/**
	 * Find a stored rule.
	 *
	 * @param string $id Rule ID.
	 * @return array|\WP_Error
	 */
	private static function find( string $id ) {
		$rule = wholesalex()->get_dynamic_rules( $id );
		if ( empty( $rule ) || ! is_array( $rule ) ) {
			return new \WP_Error(
				'wholesalex_mcp_not_found',
				sprintf(
					/* translators: %s: rule ID. */
					__( 'Dynamic rule "%s" not found. Use the wholesalex-list-dynamic-rules tool to find rule IDs.', 'wholesalex' ),
					$id
				),
				array( 'status' => 404 )
			);
		}
		$rule['id'] = (string) ( $rule['id'] ?? $id );
		return $rule;
	}

	/**
	 * Return the saved rule after a write.
	 *
	 * @param string $id Rule ID.
	 * @return array|\WP_Error
	 */
	private static function saved( string $id ) {
		$rule = self::find( $id );
		return is_wp_error( $rule ) ? $rule : array( 'rule' => self::format( $rule, true ) );
	}

	/**
	 * Generate a rule ID in the rule editor's format that is not in use.
	 *
	 * @return string
	 */
	private static function new_id(): string {
		$rules = wholesalex()->get_dynamic_rules();
		$id    = (int) floor( microtime( true ) * 1000 );
		while ( isset( $rules[ (string) $id ] ) ) {
			++$id;
		}
		return (string) $id;
	}

	/**
	 * Whether a stored rule is active.
	 *
	 * @param array $rule Stored rule.
	 * @return bool
	 */
	private static function is_active( array $rule ): bool {
		return in_array( $rule['_rule_status'] ?? '', array( true, 1, '1', 'yes' ), true );
	}

	/**
	 * Schema of a rule ID.
	 *
	 * @return array
	 */
	private static function id_schema(): array {
		return Schema::string( __( 'Dynamic rule ID from wholesalex-list-dynamic-rules.', 'wholesalex' ), 64, 1 );
	}

	/**
	 * Input schema of the create and update tools.
	 *
	 * @param bool $for_create Whether the schema is for creating a rule.
	 * @return array
	 */
	private static function rule_schema( bool $for_create ): array {
		$types     = Dynamic_Rule_Types::types();
		$audiences = Dynamic_Rule_Types::audiences();
		$scopes    = Dynamic_Rule_Types::product_scopes();

		$properties = array();
		if ( ! $for_create ) {
			$properties['id'] = self::id_schema();
		}
		$properties['title'] = Schema::string( __( 'Rule name shown in the WholesaleX admin.', 'wholesalex' ), 200, 1 );
		if ( $for_create ) {
			$type_help = array();
			foreach ( $types as $type => $definition ) {
				$type_help[] = $type . ': ' . $definition['description'];
			}
			$properties['type'] = Schema::enum( __( 'Rule type.', 'wholesalex' ) . ' ' . implode( ' ', $type_help ), array_keys( $types ) );
		}
		$properties['active']        = Schema::boolean( $for_create ? __( 'Whether the rule applies right away. Defaults to false so the store owner can review it first.', 'wholesalex' ) : __( 'true turns the rule on, false turns it off.', 'wholesalex' ) );
		$properties['applies_to']    = Schema::enum( Rule_Targets::choices_description( __( 'Who the rule applies to.', 'wholesalex' ), $audiences ), array_keys( $audiences ) );
		$properties['customer_ids']  = Schema::id_list( __( 'Customer user IDs, for applies_to specific_customers. Find them with wholesalex-search-customers.', 'wholesalex' ), 'integer', 500 );
		$properties['role_ids']      = Schema::id_list( __( 'WholesaleX role IDs, for applies_to specific_roles. Find them with wholesalex-list-roles.', 'wholesalex' ), 'string', 50 );
		$properties['product_scope'] = Schema::enum( Rule_Targets::choices_description( $for_create ? __( 'Which products the rule covers. Defaults to all_products.', 'wholesalex' ) : __( 'Which products the rule covers.', 'wholesalex' ), $scopes ), array_keys( $scopes ) );
		$properties['product_ids']   = Schema::id_list( __( 'Product IDs, for the include_products and exclude_products scopes. Find them with wholesalex-search-products.', 'wholesalex' ) );
		$properties['category_ids']  = Schema::id_list( __( 'Product category IDs, for the include_categories and exclude_categories scopes. Find them with wholesalex-search-categories.', 'wholesalex' ) );
		$properties['variation_ids'] = Schema::id_list( __( 'Product variation IDs, for the include_variations and exclude_variations scopes. Find them with wholesalex-search-products.', 'wholesalex' ) );
		foreach ( $types as $type => $definition ) {
			// Types without settings, such as visibility restrictions, act through their targets alone.
			if ( empty( $definition['fields'] ) ) {
				continue;
			}
			/* translators: 1: rule type label, 2: rule type. */
			$properties[ $type ] = Fields::schema( $definition['fields'], sprintf( __( 'Settings for a %1$s rule. Only send this object when the rule type is %2$s.', 'wholesalex' ), $definition['label'], $type ), $for_create );
		}
		$properties['conditions']  = Rule_Targets::conditions_schema( Dynamic_Rule_Types::condition_fields() );
		$properties['start_date']  = Schema::date( __( 'First day the rule applies, YYYY-MM-DD in the store timezone. Use with end_date for a time-limited campaign. An empty string removes the date.', 'wholesalex' ), true );
		$properties['end_date']    = Schema::date( __( 'Last day the rule applies, YYYY-MM-DD in the store timezone. An empty string removes the date.', 'wholesalex' ), true );
		$properties['usage_limit'] = Schema::integer( __( 'How many times the rule can be used in total. 0 means unlimited.', 'wholesalex' ), 0, 10000000 );

		return Schema::object( $properties, $for_create ? array( 'title', 'type', 'applies_to' ) : array( 'id' ) );
	}
}
