<?php
/**
 * Audience, product scope and condition mapping for rules.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Converts who a rule applies to, which products it covers and its cart
 * conditions between the AI tool format and the stored format.
 *
 * A choice list maps friendly names to stored values:
 *
 *     'specific_roles' => array(
 *         'storage'     => 'specific_roles', // Stored filter value.
 *         'ids'         => 'role_ids',       // Tool field holding the IDs, if any.
 *         'kind'        => 'roles',          // Lookup kind for those IDs.
 *         'rows_key'    => 'user_roles',     // Rule key holding the selector rows.
 *         'description' => '...',
 *     )
 *
 * Extensions can add choices for other record kinds with a `resolve`
 * callable that turns IDs into selector rows or a WP_Error, and a `describe`
 * callable that turns stored rows into `{id, name}` references.
 */
final class Rule_Targets {

	/**
	 * Condition operators offered by the rule editors.
	 *
	 * @var string[]
	 */
	const OPERATORS = array( 'less', 'less_equal', 'greater_equal', 'greater' );

	/**
	 * Apply a friendly choice, such as an audience or a product scope, to a patch.
	 *
	 * @param array  $choices      Choice definitions keyed by friendly name.
	 * @param string $choice_field Tool field holding the choice, for example "applies_to".
	 * @param string $filter_key   Rule key holding the stored filter value.
	 * @param array  $input        Tool input.
	 * @param array  $existing     Stored rule, empty for a new rule.
	 * @param array  $patch        Patch being built; receives the filter and rows.
	 * @return true|\WP_Error
	 */
	public static function apply_choice( array $choices, string $choice_field, string $filter_key, array $input, array $existing, array &$patch ) {
		$current = self::friendly_choice( $choices, $existing[ $filter_key ] ?? '' );
		$choice  = array_key_exists( $choice_field, $input ) ? (string) $input[ $choice_field ] : $current;

		// Reject IDs that do not belong to the chosen option.
		$owners = array();
		foreach ( $choices as $name => $definition ) {
			if ( ! empty( $definition['ids'] ) ) {
				$owners[ $definition['ids'] ][] = (string) $name;
			}
		}
		foreach ( $owners as $ids_field => $names ) {
			if ( array_key_exists( $ids_field, $input ) && ! in_array( $choice, $names, true ) ) {
				return Fields::invalid(
					sprintf(
						/* translators: 1: field name, 2: choice field, 3: allowed values. */
						__( '"%1$s" only applies when "%2$s" is %3$s.', 'wholesalex' ),
						$ids_field,
						$choice_field,
						implode( ' or ', $names )
					)
				);
			}
		}

		if ( '' === $choice || ! isset( $choices[ $choice ] ) ) {
			if ( array_key_exists( $choice_field, $input ) ) {
				/* translators: 1: field name, 2: allowed values. */
				return Fields::invalid( sprintf( __( '"%1$s" must be one of: %2$s.', 'wholesalex' ), $choice_field, implode( ', ', array_keys( $choices ) ) ) );
			}
			return true;
		}

		$definition = $choices[ $choice ];
		$changed    = $choice !== $current;
		if ( array_key_exists( $choice_field, $input ) ) {
			$patch[ $filter_key ] = $definition['storage'];
		}

		$ids_field = $definition['ids'] ?? '';
		if ( '' === $ids_field ) {
			return true;
		}
		if ( ! array_key_exists( $ids_field, $input ) ) {
			if ( $changed || empty( $existing ) ) {
				/* translators: 1: IDs field, 2: choice field, 3: choice. */
				return Fields::invalid( sprintf( __( 'Provide "%1$s" when "%2$s" is %3$s.', 'wholesalex' ), $ids_field, $choice_field, $choice ) );
			}
			return true;
		}

		$ids = is_array( $input[ $ids_field ] ) ? $input[ $ids_field ] : array();
		if ( empty( $ids ) ) {
			/* translators: %s: IDs field. */
			return Fields::invalid( sprintf( __( '"%s" needs at least one ID.', 'wholesalex' ), $ids_field ) );
		}
		$rows = isset( $definition['resolve'] ) && is_callable( $definition['resolve'] )
			? call_user_func( $definition['resolve'], $ids )
			: Lookup::rows_for( (string) ( $definition['kind'] ?? '' ), $ids );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}
		$patch[ $definition['rows_key'] ] = $rows;
		$patch[ $filter_key ]             = $definition['storage'];
		return true;
	}

	/**
	 * Describe a stored choice for tool output.
	 *
	 * @param array  $choices    Choice definitions keyed by friendly name.
	 * @param array  $rule       Stored rule.
	 * @param string $filter_key Rule key holding the stored filter value.
	 * @param string $choice_key Output key for the choice.
	 * @return array Output fields, including the selected records when any.
	 */
	public static function describe_choice( array $choices, array $rule, string $filter_key, string $choice_key ): array {
		$stored = (string) ( $rule[ $filter_key ] ?? '' );
		$choice = self::friendly_choice( $choices, $stored );
		$output = array( $choice_key => '' !== $choice ? $choice : $stored );
		if ( '' !== $choice && ! empty( $choices[ $choice ]['ids'] ) ) {
			$definition = $choices[ $choice ];
			$kind       = (string) ( $definition['kind'] ?? '' );
			$rows       = $rule[ $definition['rows_key'] ] ?? array();

			$output[ self::output_key( $kind ) ] = isset( $definition['describe'] ) && is_callable( $definition['describe'] )
				? call_user_func( $definition['describe'], $rows )
				: Lookup::references_for( $kind, $rows );
		}
		return $output;
	}

	/**
	 * Return the friendly name of a stored filter value.
	 *
	 * @param array  $choices Choice definitions keyed by friendly name.
	 * @param string $stored  Stored filter value.
	 * @return string Friendly name, or an empty string when unknown.
	 */
	public static function friendly_choice( array $choices, $stored ): string {
		foreach ( $choices as $name => $definition ) {
			if ( (string) $definition['storage'] === (string) $stored ) {
				return (string) $name;
			}
		}
		return '';
	}

	/**
	 * Build a description that lists every choice.
	 *
	 * @param string $intro   First sentence.
	 * @param array  $choices Choice definitions keyed by friendly name.
	 * @return string
	 */
	public static function choices_description( string $intro, array $choices ): string {
		$parts = array( $intro );
		foreach ( $choices as $name => $definition ) {
			$parts[] = $name . ': ' . ( $definition['description'] ?? '' );
		}
		return implode( ' ', $parts );
	}

	/**
	 * Build the input schema for cart conditions.
	 *
	 * @param array $fields Condition fields: stored value => description.
	 * @return array
	 */
	public static function conditions_schema( array $fields ): array {
		$field_help = array();
		foreach ( $fields as $field => $description ) {
			$field_help[] = $field . ': ' . $description;
		}
		return Schema::object_list(
			__( 'Optional cart conditions. The rule only applies while every condition is met. Send an empty list to remove all conditions.', 'wholesalex' ),
			Schema::object(
				array(
					'field'    => Schema::enum( implode( ' ', $field_help ), array_keys( $fields ) ),
					'operator' => Schema::enum( __( 'Comparison: less, less_equal, greater_equal or greater.', 'wholesalex' ), self::OPERATORS ),
					'value'    => Schema::number( __( 'Value to compare against, 0 or more.', 'wholesalex' ), 0 ),
				),
				array( 'field', 'operator', 'value' )
			),
			10
		);
	}

	/**
	 * Convert friendly conditions into stored condition rows.
	 *
	 * @param mixed $conditions Friendly conditions.
	 * @param array $fields     Allowed condition fields.
	 * @return array|\WP_Error
	 */
	public static function conditions_to_storage( $conditions, array $fields ) {
		$rows = array();
		foreach ( is_array( $conditions ) ? array_values( $conditions ) : array() as $index => $condition ) {
			$field    = is_array( $condition ) ? (string) ( $condition['field'] ?? '' ) : '';
			$operator = is_array( $condition ) ? (string) ( $condition['operator'] ?? '' ) : '';
			$value    = is_array( $condition ) ? ( $condition['value'] ?? null ) : null;
			if ( ! isset( $fields[ $field ] ) || ! in_array( $operator, self::OPERATORS, true ) || ! is_numeric( $value ) || (float) $value < 0 ) {
				/* translators: %d: condition number. */
				return Fields::invalid( sprintf( __( 'Condition %d is not valid. Use a listed field, a listed operator and a value of 0 or more.', 'wholesalex' ), $index + 1 ) );
			}
			$rows[] = array(
				'_id'                  => (string) ( (int) floor( microtime( true ) * 1000 ) ) . '_' . $index,
				'_conditions_for'      => $field,
				'_conditions_operator' => $operator,
				'_conditions_value'    => (string) ( 0 + $value ),
			);
		}
		return $rows;
	}

	/**
	 * Convert stored condition rows into friendly conditions.
	 *
	 * @param mixed $rows Stored rows.
	 * @return array[]
	 */
	public static function conditions_from_storage( $rows ): array {
		$conditions = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) || '' === (string) ( $row['_conditions_for'] ?? '' ) ) {
				continue;
			}
			$conditions[] = array(
				'field'    => (string) $row['_conditions_for'],
				'operator' => (string) ( $row['_conditions_operator'] ?? '' ),
				'value'    => is_numeric( $row['_conditions_value'] ?? null ) ? (float) $row['_conditions_value'] : 0,
			);
		}
		return $conditions;
	}

	/**
	 * Output key for selected records of one kind.
	 *
	 * @param string $kind Lookup kind.
	 * @return string
	 */
	private static function output_key( string $kind ): string {
		return in_array( $kind, array( 'products', 'variations', 'categories', 'customers', 'roles' ), true ) ? $kind : 'items';
	}
}
