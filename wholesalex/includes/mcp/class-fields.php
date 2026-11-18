<?php
/**
 * Declarative field mapping for rule settings.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

defined( 'ABSPATH' ) || exit;

/**
 * Converts rule settings between the AI tool format and the stored format.
 *
 * Rule types describe their settings as a list of fields. Each field has a
 * friendly name used by the AI tools, a `storage` key used by WholesaleX and a
 * `kind` that decides how the value is validated and converted:
 *
 * - text:             string, sanitized, limited by `max_length`.
 * - number, integer:  numeric, limited by `minimum` and `maximum`.
 * - enum:             one of `enum`.
 * - yes_no:           boolean in the tools, "yes" or "no" in storage.
 * - boolean:          boolean in both.
 * - color:            hex color such as "#5a40e8".
 * - gateways:         payment method IDs, stored as selector rows.
 * - countries:        country codes, stored as selector rows.
 * - shipping_zone:    shipping zone ID.
 * - shipping_methods: shipping method IDs of the zone named by `zone_field`.
 * - tax_class:        tax class slug or name.
 * - products:         product IDs, stored as selector rows.
 * - custom:           `schema`, `to_storage` and `from_storage` callables.
 *
 * Extensions use the same format to describe their own rule types.
 */
final class Fields {

	/**
	 * Build the input schema of a settings object.
	 *
	 * @param array  $fields      Field definitions keyed by friendly name.
	 * @param string $description Object description.
	 * @param bool   $for_create  Whether required fields are marked required.
	 * @return array
	 */
	public static function schema( array $fields, string $description, bool $for_create ): array {
		$properties = array();
		$required   = array();
		foreach ( $fields as $name => $field ) {
			$properties[ $name ] = self::field_schema( $field );
			if ( $for_create && ! empty( $field['required'] ) ) {
				$required[] = $name;
			}
		}
		return Schema::object( $properties, $required, $description );
	}

	/**
	 * Convert friendly settings into a stored payload.
	 *
	 * Only the provided settings are converted. When `$stored` is empty the
	 * payload is new, so required settings must be present and defaults apply.
	 *
	 * @param array  $fields Field definitions keyed by friendly name.
	 * @param array  $input  Friendly settings.
	 * @param array  $stored Stored payload, empty for a new payload.
	 * @param string $label  Label used in error messages.
	 * @return array|\WP_Error Stored payload with the changes applied.
	 */
	public static function to_storage( array $fields, array $input, array $stored, string $label ) {
		$is_new = empty( $stored );
		$merged = $stored;

		foreach ( $fields as $name => $field ) {
			if ( ! $is_new || array_key_exists( $name, $input ) ) {
				continue;
			}
			if ( array_key_exists( 'default', $field ) ) {
				$input[ $name ] = $field['default'];
			} elseif ( ! empty( $field['required'] ) ) {
				return self::invalid(
					sprintf(
						/* translators: 1: setting name, 2: rule type. */
						__( 'The "%1$s" setting is required for %2$s.', 'wholesalex' ),
						$name,
						$label
					)
				);
			}
		}

		foreach ( $fields as $name => $field ) {
			if ( ! array_key_exists( $name, $input ) ) {
				continue;
			}
			$value = self::convert( $name, $field, $input[ $name ], $input, $merged, $fields );
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			$merged[ $field['storage'] ] = $value;
		}

		return $merged;
	}

	/**
	 * Convert a stored payload into friendly settings.
	 *
	 * @param array $fields  Field definitions keyed by friendly name.
	 * @param mixed $payload Stored payload.
	 * @return array
	 */
	public static function from_storage( array $fields, $payload ): array {
		$payload  = is_array( $payload ) ? $payload : array();
		$settings = array();
		foreach ( $fields as $name => $field ) {
			if ( ! array_key_exists( $field['storage'], $payload ) ) {
				continue;
			}
			$value = $payload[ $field['storage'] ];
			switch ( $field['kind'] ) {
				case 'number':
					$settings[ $name ] = is_numeric( $value ) ? (float) $value : null;
					break;
				case 'integer':
				case 'shipping_zone':
					$settings[ $name ] = is_numeric( $value ) ? (int) $value : null;
					break;
				case 'yes_no':
					$settings[ $name ] = 'yes' === $value || true === $value;
					break;
				case 'boolean':
					$settings[ $name ] = (bool) $value;
					break;
				case 'gateways':
				case 'countries':
				case 'shipping_methods':
					$settings[ $name ] = wp_list_pluck( Lookup::references( $value, 'string' ), 'id' );
					break;
				case 'products':
					$settings[ $name ] = wp_list_pluck( Lookup::references( $value ), 'id' );
					break;
				case 'custom':
					$settings[ $name ] = isset( $field['from_storage'] ) && is_callable( $field['from_storage'] ) ? call_user_func( $field['from_storage'], $value, $payload ) : $value;
					break;
				default:
					$settings[ $name ] = is_scalar( $value ) ? (string) $value : $value;
			}
			if ( null === $settings[ $name ] ) {
				unset( $settings[ $name ] );
			}
		}
		return $settings;
	}

	/**
	 * Build the schema of one field.
	 *
	 * @param array $field Field definition.
	 * @return array
	 */
	private static function field_schema( array $field ): array {
		$description = (string) ( $field['description'] ?? '' );
		switch ( $field['kind'] ) {
			case 'number':
				return Schema::number( $description, $field['minimum'] ?? null, $field['maximum'] ?? null );
			case 'integer':
				return Schema::integer( $description, $field['minimum'] ?? null, $field['maximum'] ?? null );
			case 'shipping_zone':
				return Schema::integer( $description, 0 );
			case 'enum':
				return Schema::enum( $description, $field['enum'] );
			case 'yes_no':
			case 'boolean':
				return Schema::boolean( $description );
			case 'color':
				$schema            = Schema::string( $description, 7 );
				$schema['pattern'] = '^#[0-9a-fA-F]{6}$';
				return $schema;
			case 'gateways':
			case 'shipping_methods':
				return Schema::id_list( $description, 'string', 50 );
			case 'countries':
				return Schema::id_list( $description, 'string', 250 );
			case 'products':
				return Schema::id_list( $description, 'integer', 200 );
			case 'custom':
				return is_array( $field['schema'] ?? null ) ? $field['schema'] : Schema::string( $description );
			default:
				return Schema::string( $description, (int) ( $field['max_length'] ?? 200 ) );
		}
	}

	/**
	 * Validate and convert one friendly value.
	 *
	 * @param string $name   Friendly field name.
	 * @param array  $field  Field definition.
	 * @param mixed  $value  Friendly value.
	 * @param array  $input  All friendly settings, for dependent fields.
	 * @param array  $stored Stored payload so far.
	 * @param array  $fields All field definitions.
	 * @return mixed|\WP_Error Stored value.
	 */
	private static function convert( string $name, array $field, $value, array $input, array $stored, array $fields ) {
		switch ( $field['kind'] ) {
			case 'number':
			case 'integer':
				if ( ! is_numeric( $value ) || ! is_finite( (float) $value ) ) {
					return self::invalid( self::message( $name, __( 'must be a number', 'wholesalex' ) ) );
				}
				$number = 'integer' === $field['kind'] ? (int) $value : (float) $value;
				if ( isset( $field['minimum'] ) && $number < $field['minimum'] ) {
					/* translators: %s: minimum value. */
					return self::invalid( self::message( $name, sprintf( __( 'must be at least %s', 'wholesalex' ), $field['minimum'] ) ) );
				}
				if ( isset( $field['maximum'] ) && $number > $field['maximum'] ) {
					/* translators: %s: maximum value. */
					return self::invalid( self::message( $name, sprintf( __( 'must be at most %s', 'wholesalex' ), $field['maximum'] ) ) );
				}
				return (string) $number;
			case 'enum':
				if ( ! is_string( $value ) || ! in_array( $value, $field['enum'], true ) ) {
					/* translators: %s: allowed values. */
					return self::invalid( self::message( $name, sprintf( __( 'must be one of: %s', 'wholesalex' ), implode( ', ', $field['enum'] ) ) ) );
				}
				return $value;
			case 'yes_no':
				return rest_sanitize_boolean( $value ) ? 'yes' : 'no';
			case 'boolean':
				return (bool) rest_sanitize_boolean( $value );
			case 'color':
				$color = is_string( $value ) ? sanitize_hex_color( $value ) : null;
				if ( empty( $color ) ) {
					return self::invalid( self::message( $name, __( 'must be a hex color such as #5a40e8', 'wholesalex' ) ) );
				}
				return $color;
			case 'gateways':
				if ( ! empty( $field['required'] ) && empty( $value ) ) {
					return self::invalid( self::message( $name, __( 'needs at least one payment method', 'wholesalex' ) ) );
				}
				return Lookup::gateway_rows( (array) $value );
			case 'countries':
				return Lookup::country_rows( (array) $value );
			case 'shipping_zone':
				$zone = absint( $value );
				$ok   = Lookup::shipping_zone( $zone );
				return is_wp_error( $ok ) ? $ok : (string) $zone;
			case 'shipping_methods':
				$zone_field = (string) ( $field['zone_field'] ?? '' );
				$zone_key   = isset( $fields[ $zone_field ] ) ? $fields[ $zone_field ]['storage'] : '';
				$zone       = array_key_exists( $zone_field, $input ) ? $input[ $zone_field ] : ( $stored[ $zone_key ] ?? null );
				if ( ! is_numeric( $zone ) ) {
					/* translators: %s: setting name. */
					return self::invalid( self::message( $name, sprintf( __( 'needs the "%s" setting', 'wholesalex' ), $zone_field ) ) );
				}
				return Lookup::shipping_method_rows( (int) $zone, (array) $value );
			case 'tax_class':
				return Lookup::tax_class( is_scalar( $value ) ? (string) $value : '' );
			case 'products':
				return Lookup::product_rows( (array) $value );
			case 'custom':
				return isset( $field['to_storage'] ) && is_callable( $field['to_storage'] ) ? call_user_func( $field['to_storage'], $value, $input, $stored ) : $value;
			default:
				if ( ! is_scalar( $value ) ) {
					return self::invalid( self::message( $name, __( 'must be text', 'wholesalex' ) ) );
				}
				$text = sanitize_text_field( (string) $value );
				return mb_substr( $text, 0, (int) ( $field['max_length'] ?? 200 ) );
		}
	}

	/**
	 * Build a "setting is invalid" sentence.
	 *
	 * @param string $name   Setting name.
	 * @param string $reason Reason.
	 * @return string
	 */
	private static function message( string $name, string $reason ): string {
		/* translators: 1: setting name, 2: reason. */
		return sprintf( __( 'The "%1$s" setting %2$s.', 'wholesalex' ), $name, $reason );
	}

	/**
	 * Build a validation error.
	 *
	 * @param string $message Message.
	 * @return \WP_Error
	 */
	public static function invalid( string $message ): \WP_Error {
		return new \WP_Error( 'wholesalex_mcp_invalid_input', $message, array( 'status' => 400 ) );
	}
}
