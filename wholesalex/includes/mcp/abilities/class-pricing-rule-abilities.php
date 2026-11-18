<?php
/**
 * Wholesale pricing rule tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP\Abilities;

use WHOLESALEX\MCP\Access;
use WHOLESALEX\MCP\Fields;
use WHOLESALEX\MCP\Lookup;
use WHOLESALEX\MCP\Pricing_Rule_Types;
use WHOLESALEX\MCP\Pro_Features;
use WHOLESALEX\MCP\Rule_Targets;
use WHOLESALEX\MCP\Schema;
use WHOLESALEX\Wholesale_Pricing;
use WHOLESALEX\Wholesale_Pricing_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Lists, reads, creates, updates and deletes wholesale pricing rules.
 *
 * Writes go through `Wholesale_Pricing_Service`, the same path the
 * Wholesale Pricing editor uses, so ownership checks, sanitization, provider
 * dispatch and schedule validation all apply.
 */
final class Pricing_Rule_Abilities {

	/**
	 * Return the tool definitions.
	 *
	 * @return array<string, array>
	 */
	public static function definitions(): array {
		return array(
			'wholesalex/list-pricing-rules'  => array(
				'label'        => __( 'List wholesale pricing rules', 'wholesalex' ),
				'description'  => __( 'Lists WholesaleX wholesale pricing rules, newest first, with their type, status, audience, product scope and schedule. Pricing rules set wholesale prices, product discounts, cart discounts and buy X get one offers.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::object(
					Schema::list_properties( __( 'Only rules whose title contains this text.', 'wholesalex' ) ) + array(
						'type'   => Schema::string( __( 'Only rules of this type, for example wholesale_pricing.', 'wholesalex' ), 50 ),
						'active' => Schema::boolean( __( 'true lists only active rules, false only inactive rules.', 'wholesalex' ) ),
					)
				),
				'callback'     => array( __CLASS__, 'list_rules' ),
			),
			'wholesalex/get-pricing-rule'    => array(
				'label'        => __( 'Get a wholesale pricing rule', 'wholesalex' ),
				'description'  => __( 'Returns every setting of one wholesale pricing rule.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::object( array( 'id' => self::id_schema() ), array( 'id' ) ),
				'callback'     => array( __CLASS__, 'get_rule' ),
			),
			'wholesalex/create-pricing-rule' => array(
				'label'        => __( 'Create a wholesale pricing rule', 'wholesalex' ),
				'description'  => __( 'Creates a wholesale pricing rule, for example 20% off all products for the Wholesale role, or a scheduled sale campaign. Pick a type, then put that type\'s settings in the object named after the type. New rules are inactive unless active is true.', 'wholesalex' ),
				'operation'    => Access::WRITE,
				'input_schema' => self::rule_schema( true ),
				'callback'     => array( __CLASS__, 'create_rule' ),
			),
			'wholesalex/update-pricing-rule' => array(
				'label'        => __( 'Update a wholesale pricing rule', 'wholesalex' ),
				'description'  => __( 'Changes an existing wholesale pricing rule. Only the fields you send change; everything else keeps its value. Use active to turn the rule on or off. The rule type cannot change; create a new rule instead.', 'wholesalex' ),
				'operation'    => Access::WRITE,
				'idempotent'   => true,
				'input_schema' => self::rule_schema( false ),
				'callback'     => array( __CLASS__, 'update_rule' ),
			),
			'wholesalex/delete-pricing-rule' => array(
				'label'        => __( 'Delete a wholesale pricing rule', 'wholesalex' ),
				'description'  => __( 'Deletes a wholesale pricing rule so it no longer affects prices. A backup copy is kept in the database. To pause a rule instead, update it with active false.', 'wholesalex' ),
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
		foreach ( Wholesale_Pricing::get_rules_for_current_manager() as $rule ) {
			if ( ! is_array( $rule ) || '' === (string) ( $rule['id'] ?? '' ) ) {
				continue;
			}
			if ( null !== $type_filter && (string) ( $rule['rule_type'] ?? '' ) !== $type_filter ) {
				continue;
			}
			if ( null !== $active_filter && ( 'active' === ( $rule['status'] ?? '' ) ) !== $active_filter ) {
				continue;
			}
			if ( '' !== $search && false === strpos( strtolower( (string) ( $rule['title'] ?? '' ) ), $search ) ) {
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
					return Pricing_Rule_Abilities::format( $rule, false );
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
		$types = Pricing_Rule_Types::types();
		$type  = (string) ( $input['type'] ?? '' );
		if ( ! isset( $types[ $type ] ) ) {
			if ( in_array( $type, Pro_Features::PRICING_TYPES, true ) ) {
				return Pro_Features::required();
			}
			/* translators: %s: allowed rule types. */
			return Fields::invalid( sprintf( __( 'Unknown rule type. Use one of: %s.', 'wholesalex' ), implode( ', ', array_keys( $types ) ) ) );
		}
		$foreign = self::foreign_settings( $input, $types, $type );
		if ( is_wp_error( $foreign ) ) {
			return $foreign;
		}

		$id     = self::new_id();
		$rule   = array(
			'id'        => $id,
			'title'     => sanitize_text_field( (string) $input['title'] ),
			'status'    => ! empty( $input['active'] ) ? 'active' : 'inactive',
			'rule_type' => $type,
		);
		$input += array( 'product_scope' => 'all_products' );

		$patch = self::build_patch( $input, array(), $types[ $type ], $type );
		if ( is_wp_error( $patch ) ) {
			return $patch;
		}
		return self::save( $id, array_replace( $rule, $patch ) );
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

		$types      = Pricing_Rule_Types::types();
		$type       = (string) ( $existing['rule_type'] ?? '' );
		$definition = $types[ $type ] ?? null;
		if ( null === $definition && in_array( $type, Pro_Features::PRICING_TYPES, true ) && ( ! empty( $input['active'] ) || array_key_exists( $type, $input ) ) ) {
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
			$patch['title'] = sanitize_text_field( (string) $input['title'] );
		}
		if ( array_key_exists( 'active', $input ) ) {
			$patch['status'] = ! empty( $input['active'] ) ? 'active' : 'inactive';
		}

		$changes = self::build_patch(
			$input,
			$existing,
			$definition ?? array(
				'fields'      => array(),
				'storage_key' => '',
			),
			$type
		);
		if ( is_wp_error( $changes ) ) {
			return $changes;
		}
		$patch = array_replace( $patch, $changes );
		if ( empty( $patch ) ) {
			return array( 'rule' => self::format( $existing, true ) );
		}
		return self::save( $id, $patch );
	}

	/**
	 * Delete a rule, keeping a backup copy.
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
		$deleted = Wholesale_Pricing_Service::delete( $id, true );
		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}
		return array(
			'deleted' => true,
			'id'      => $id,
			'title'   => (string) ( $rule['title'] ?? '' ),
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
		$types    = Pricing_Rule_Types::types();
		$type     = (string) ( $rule['rule_type'] ?? '' );
		$schedule = is_array( $rule['schedule'] ?? null ) ? $rule['schedule'] : array();

		$output = array(
			'id'         => (string) ( $rule['id'] ?? '' ),
			'title'      => (string) ( $rule['title'] ?? '' ),
			'type'       => $type,
			'type_label' => (string) ( $types[ $type ]['label'] ?? $type ),
			'active'     => 'active' === ( $rule['status'] ?? '' ),
		);

		$audience = Rule_Targets::describe_choice( Pricing_Rule_Types::audiences(), $rule, 'user_role_filter', 'applies_to' );
		$scope    = Rule_Targets::describe_choice( Pricing_Rule_Types::product_scopes(), $rule, 'product_filter', 'product_scope' );
		if ( ! $full ) {
			$audience = array( 'applies_to' => $audience['applies_to'] );
			$scope    = array( 'product_scope' => $scope['product_scope'] );
		}
		$output += $audience + $scope;

		$output['start_date'] = (string) ( $schedule['start_date'] ?? '' );
		$output['end_date']   = (string) ( $schedule['end_date'] ?? '' );

		if ( $full ) {
			$output['excluded_products'] = Lookup::references( $rule['exclude_products'] ?? array() );
			if ( isset( $types[ $type ]['from_storage'] ) && is_callable( $types[ $type ]['from_storage'] ) ) {
				$output['settings'] = call_user_func( $types[ $type ]['from_storage'], $rule );
			} elseif ( isset( $types[ $type ] ) && '' !== (string) $types[ $type ]['storage_key'] ) {
				$key                = $types[ $type ]['storage_key'];
				$output['settings'] = Fields::from_storage( $types[ $type ]['fields'], $rule[ $key ] ?? array() );
			}
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

		$ok = Rule_Targets::apply_choice( Pricing_Rule_Types::audiences(), 'applies_to', 'user_role_filter', $input, $existing, $patch );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		$ok = Rule_Targets::apply_choice( Pricing_Rule_Types::product_scopes(), 'product_scope', 'product_filter', $input, $existing, $patch );
		if ( is_wp_error( $ok ) ) {
			return $ok;
		}
		if ( array_key_exists( 'exclude_product_ids', $input ) ) {
			$rows = Lookup::product_rows( (array) $input['exclude_product_ids'] );
			if ( is_wp_error( $rows ) ) {
				return $rows;
			}
			$patch['exclude_products'] = $rows;
		}

		$key      = (string) ( $definition['storage_key'] ?? '' );
		$settings = is_array( $input[ $type ] ?? null ) ? $input[ $type ] : array();
		if ( isset( $definition['to_storage'] ) && is_callable( $definition['to_storage'] ) ) {
			// The type maps its settings onto top-level rule fields itself.
			if ( empty( $existing ) || array_key_exists( $type, $input ) ) {
				$changes = call_user_func( $definition['to_storage'], $settings, $existing );
				if ( is_wp_error( $changes ) ) {
					return $changes;
				}
				$patch = array_replace( $patch, is_array( $changes ) ? $changes : array() );
			}
		} elseif ( '' !== $key && ( empty( $existing ) || array_key_exists( $type, $input ) ) ) {
			$stored   = is_array( $existing[ $key ] ?? null ) ? $existing[ $key ] : array();
			$settings = Fields::to_storage( $definition['fields'], $settings, $stored, (string) $definition['label'] );
			if ( is_wp_error( $settings ) ) {
				return $settings;
			}
			if ( isset( $definition['validate'] ) && is_callable( $definition['validate'] ) ) {
				$valid = call_user_func( $definition['validate'], $settings );
				if ( is_wp_error( $valid ) ) {
					return $valid;
				}
			}
			$patch[ $key ] = $settings;
		}

		if ( array_key_exists( 'conditions', $input ) ) {
			$rows = Rule_Targets::conditions_to_storage( $input['conditions'], Pricing_Rule_Types::condition_fields() );
			if ( is_wp_error( $rows ) ) {
				return $rows;
			}
			$conditions          = is_array( $existing['conditions'] ?? null ) ? $existing['conditions'] : array();
			$conditions['tiers'] = $rows;
			$patch['conditions'] = $conditions;
		}

		if ( array_key_exists( 'start_date', $input ) || array_key_exists( 'end_date', $input ) ) {
			$schedule = is_array( $existing['schedule'] ?? null ) ? $existing['schedule'] : array();
			foreach ( array( 'start_date', 'end_date' ) as $field ) {
				if ( array_key_exists( $field, $input ) ) {
					$schedule[ $field ] = (string) $input[ $field ];
				}
			}
			$start = (string) ( $schedule['start_date'] ?? '' );
			$end   = (string) ( $schedule['end_date'] ?? '' );
			if ( '' !== $start && '' !== $end && $start > $end ) {
				return Fields::invalid( __( 'The start date must be on or before the end date.', 'wholesalex' ) );
			}
			$patch['schedule'] = $schedule;
		}

		$overlap = self::included_and_excluded( array_replace( $existing, $patch ) );
		if ( ! empty( $overlap ) ) {
			return Fields::invalid(
				sprintf(
					/* translators: %s: product IDs. */
					__( 'Products %s are in both product_ids and exclude_product_ids. The included list wins for these products, so remove them from one of the lists.', 'wholesalex' ),
					implode( ', ', $overlap )
				)
			);
		}

		return $patch;
	}

	/**
	 * Return the product IDs a rule both includes by ID and excludes.
	 *
	 * @param array $rule Stored rule.
	 * @return int[]
	 */
	private static function included_and_excluded( array $rule ): array {
		if ( 'specific_products' !== (string) ( $rule['product_filter'] ?? '' ) ) {
			return array();
		}
		$included = wp_list_pluck( Lookup::references( $rule['products'] ?? array() ), 'id' );
		$excluded = wp_list_pluck( Lookup::references( $rule['exclude_products'] ?? array() ), 'id' );
		return array_values( array_intersect( $included, $excluded ) );
	}

	/**
	 * Save through the shared service and return the stored rule.
	 *
	 * @param string $id   Rule ID.
	 * @param array  $rule Rule fields to save.
	 * @return array|\WP_Error
	 */
	private static function save( string $id, array $rule ) {
		$result = Wholesale_Pricing_Service::save( $id, $rule );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$saved = self::find( $id );
		return is_wp_error( $saved ) ? $saved : array( 'rule' => self::format( $saved, true ) );
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
		$rule = Wholesale_Pricing::get_rule( $id );
		if ( ! is_array( $rule ) || ! Wholesale_Pricing::current_manager_owns_rule( $rule ) ) {
			return new \WP_Error(
				'wholesalex_mcp_not_found',
				sprintf(
					/* translators: %s: rule ID. */
					__( 'Pricing rule "%s" not found. Use the wholesalex-list-pricing-rules tool to find rule IDs.', 'wholesalex' ),
					$id
				),
				array( 'status' => 404 )
			);
		}
		return $rule;
	}

	/**
	 * Generate a rule ID in the pricing editor's format that is not in use.
	 *
	 * @return string
	 */
	private static function new_id(): string {
		$rules = Wholesale_Pricing::get_all_rules();
		$id    = (int) floor( microtime( true ) * 1000 );
		while ( isset( $rules[ (string) $id ] ) ) {
			++$id;
		}
		return (string) $id;
	}

	/**
	 * Schema of a rule ID.
	 *
	 * @return array
	 */
	private static function id_schema(): array {
		return Schema::string( __( 'Pricing rule ID from wholesalex-list-pricing-rules.', 'wholesalex' ), 64, 1 );
	}

	/**
	 * Input schema of the create and update tools.
	 *
	 * @param bool $for_create Whether the schema is for creating a rule.
	 * @return array
	 */
	private static function rule_schema( bool $for_create ): array {
		$types     = Pricing_Rule_Types::types();
		$audiences = Pricing_Rule_Types::audiences();
		$scopes    = Pricing_Rule_Types::product_scopes();

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
		$properties['active']              = Schema::boolean( $for_create ? __( 'Whether the rule applies right away. Defaults to false so the store owner can review it first.', 'wholesalex' ) : __( 'true turns the rule on, false turns it off.', 'wholesalex' ) );
		$properties['applies_to']          = Schema::enum( Rule_Targets::choices_description( __( 'Who the rule applies to.', 'wholesalex' ), $audiences ), array_keys( $audiences ) );
		$properties['customer_ids']        = Schema::id_list( __( 'Customer user IDs, for applies_to specific_customers. Find them with wholesalex-search-customers.', 'wholesalex' ), 'integer', 500 );
		$properties['role_ids']            = Schema::id_list( __( 'WholesaleX role IDs, for applies_to specific_roles. Find them with wholesalex-list-roles.', 'wholesalex' ), 'string', 50 );
		$properties['product_scope']       = Schema::enum( Rule_Targets::choices_description( $for_create ? __( 'Which products the rule covers. Defaults to all_products.', 'wholesalex' ) : __( 'Which products the rule covers.', 'wholesalex' ), $scopes ), array_keys( $scopes ) );
		$properties['product_ids']         = Schema::id_list( __( 'Product IDs, for the include_products scope. Find them with wholesalex-search-products.', 'wholesalex' ) );
		$properties['category_ids']        = Schema::id_list( __( 'Product category IDs, for the include_categories scope. Find them with wholesalex-search-categories.', 'wholesalex' ) );
		$properties['variation_ids']       = Schema::id_list( __( 'Product variation IDs, for the include_variations scope. Find them with wholesalex-search-products.', 'wholesalex' ) );
		$properties['exclude_product_ids'] = Schema::id_list( __( 'Product IDs the rule never applies to, whatever the scope. Send an empty list to remove all exclusions.', 'wholesalex' ) );
		foreach ( $types as $type => $definition ) {
			if ( empty( $definition['fields'] ) ) {
				continue;
			}
			/* translators: 1: rule type label, 2: rule type. */
			$properties[ $type ] = Fields::schema( $definition['fields'], sprintf( __( 'Settings for a %1$s rule. Only send this object when the rule type is %2$s.', 'wholesalex' ), $definition['label'], $type ), $for_create );
		}
		$properties['conditions'] = Rule_Targets::conditions_schema( Pricing_Rule_Types::condition_fields() );
		$properties['start_date'] = Schema::date( __( 'First day the rule applies, YYYY-MM-DD in the store timezone. Use with end_date for a time-limited campaign. An empty string removes the date.', 'wholesalex' ), true );
		$properties['end_date']   = Schema::date( __( 'Last day the rule applies, YYYY-MM-DD in the store timezone. An empty string removes the date.', 'wholesalex' ), true );

		return Schema::object( $properties, $for_create ? array( 'title', 'type', 'applies_to' ) : array( 'id' ) );
	}
}
