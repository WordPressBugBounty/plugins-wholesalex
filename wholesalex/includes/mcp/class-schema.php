<?php
/**
 * JSON Schema builders for the WholesaleX AI tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the input and output schemas of the WholesaleX abilities.
 *
 * The schemas are validated by WordPress before any tool runs, and they are
 * shown to AI assistants as tool definitions. They therefore stay inside the
 * subset of JSON Schema that every major assistant understands: no union
 * types, no null types and no schema composition keywords.
 */
final class Schema {

	/**
	 * Date pattern accepted for rule schedules (YYYY-MM-DD).
	 *
	 * @var string
	 */
	const DATE_PATTERN = '^[0-9]{4}-[0-9]{2}-[0-9]{2}$';

	/**
	 * Date pattern that also accepts an empty string, used to clear a date.
	 *
	 * @var string
	 */
	const OPTIONAL_DATE_PATTERN = '^([0-9]{4}-[0-9]{2}-[0-9]{2})?$';

	/**
	 * Build a closed object schema.
	 *
	 * @param array  $properties  Property schemas keyed by name.
	 * @param array  $required    Required property names.
	 * @param string $description Optional description.
	 * @return array
	 */
	public static function object( array $properties, array $required = array(), string $description = '' ): array {
		$schema = array(
			'type'                 => 'object',
			'properties'           => $properties,
			'additionalProperties' => false,
		);
		if ( ! empty( $required ) ) {
			$schema['required'] = array_values( $required );
		}
		if ( '' !== $description ) {
			$schema['description'] = $description;
		}
		return $schema;
	}

	/**
	 * Build a string schema.
	 *
	 * @param string $description Description.
	 * @param int    $max_length  Maximum length.
	 * @param int    $min_length  Minimum length.
	 * @return array
	 */
	public static function string( string $description, int $max_length = 200, int $min_length = 0 ): array {
		$schema = array(
			'type'        => 'string',
			'description' => $description,
			'maxLength'   => $max_length,
		);
		if ( $min_length > 0 ) {
			$schema['minLength'] = $min_length;
		}
		return $schema;
	}

	/**
	 * Build a string enumeration schema.
	 *
	 * @param string $description Description.
	 * @param array  $values      Allowed values.
	 * @return array
	 */
	public static function enum( string $description, array $values ): array {
		return array(
			'type'        => 'string',
			'description' => $description,
			'enum'        => array_values( array_unique( array_map( 'strval', $values ) ) ),
		);
	}

	/**
	 * Build a boolean schema.
	 *
	 * @param string $description Description.
	 * @return array
	 */
	public static function boolean( string $description ): array {
		return array(
			'type'        => 'boolean',
			'description' => $description,
		);
	}

	/**
	 * Build an integer schema.
	 *
	 * @param string   $description Description.
	 * @param int|null $minimum     Minimum value.
	 * @param int|null $maximum     Maximum value.
	 * @return array
	 */
	public static function integer( string $description, $minimum = null, $maximum = null ): array {
		$schema = array(
			'type'        => 'integer',
			'description' => $description,
		);
		if ( null !== $minimum ) {
			$schema['minimum'] = $minimum;
		}
		if ( null !== $maximum ) {
			$schema['maximum'] = $maximum;
		}
		return $schema;
	}

	/**
	 * Build a number schema.
	 *
	 * @param string         $description Description.
	 * @param int|float|null $minimum     Minimum value.
	 * @param int|float|null $maximum     Maximum value.
	 * @return array
	 */
	public static function number( string $description, $minimum = null, $maximum = null ): array {
		$schema = array(
			'type'        => 'number',
			'description' => $description,
		);
		if ( null !== $minimum ) {
			$schema['minimum'] = $minimum;
		}
		if ( null !== $maximum ) {
			$schema['maximum'] = $maximum;
		}
		return $schema;
	}

	/**
	 * Build a calendar date schema.
	 *
	 * @param string $description Description.
	 * @param bool   $allow_empty Whether an empty string may clear the date.
	 * @return array
	 */
	public static function date( string $description, bool $allow_empty = false ): array {
		return array(
			'type'        => 'string',
			'description' => $description,
			'pattern'     => $allow_empty ? self::OPTIONAL_DATE_PATTERN : self::DATE_PATTERN,
			'maxLength'   => 10,
		);
	}

	/**
	 * Build a list of record IDs.
	 *
	 * @param string $description Description.
	 * @param string $item_type   "integer" or "string".
	 * @param int    $max_items   Maximum number of IDs.
	 * @return array
	 */
	public static function id_list( string $description, string $item_type = 'integer', int $max_items = 200 ): array {
		$item = 'integer' === $item_type
			? array(
				'type'    => 'integer',
				'minimum' => 1,
			)
			: array(
				'type'      => 'string',
				'minLength' => 1,
				'maxLength' => 100,
			);

		return array(
			'type'        => 'array',
			'description' => $description,
			'items'       => $item,
			'maxItems'    => $max_items,
			'uniqueItems' => true,
		);
	}

	/**
	 * Build a list of objects.
	 *
	 * @param string $description Description.
	 * @param array  $item        Item object schema.
	 * @param int    $max_items   Maximum number of items.
	 * @return array
	 */
	public static function object_list( string $description, array $item, int $max_items = 20 ): array {
		return array(
			'type'        => 'array',
			'description' => $description,
			'items'       => $item,
			'maxItems'    => $max_items,
		);
	}

	/**
	 * Input schema for a tool without parameters.
	 *
	 * @return array
	 */
	public static function no_input(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(),
			'additionalProperties' => false,
		);
	}

	/**
	 * Common pagination and search parameters for list tools.
	 *
	 * @param string $search_description Description of the search parameter.
	 * @return array
	 */
	public static function list_properties( string $search_description ): array {
		return array(
			'search' => self::string( $search_description, 100 ),
			'limit'  => self::integer( __( 'Maximum number of results to return. Defaults to 20.', 'wholesalex' ), 1, 100 ),
			'offset' => self::integer( __( 'Number of results to skip, for paging through long lists. Defaults to 0.', 'wholesalex' ), 0, 100000 ),
		);
	}
}
