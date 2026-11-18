<?php
/**
 * Registration form write operations shared by every entry point.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and saves the registration form built in the form builder.
 *
 * The form builder REST route and the AI tools both save through this class,
 * so every save runs the same extension preservation and sanitization. The
 * sanitization was moved here unchanged from `WHOLESALEX_Registration`.
 *
 * The field operations add, edit, move and remove one field at a time on top
 * of the saved form, the same way the builder does.
 */
final class Registration_Form_Service {

	/**
	 * Option holding the form builder data as JSON.
	 *
	 * @var string
	 */
	const OPTION = 'wholesalex_registration_form';

	/**
	 * Fields that the registration form cannot work without.
	 *
	 * The builder does not let these be removed, and they always stay
	 * required and active.
	 *
	 * @var string[]
	 */
	const ESSENTIAL_FIELDS = array( 'user_email', 'user_pass' );

	/**
	 * Fields the builder always marks as required.
	 *
	 * @var string[]
	 */
	const ALWAYS_REQUIRED_FIELDS = array( 'user_email', 'user_pass', 'user_confirm_email', 'user_confirm_pass' );

	/**
	 * Field properties that identify a field and its place; they never change.
	 *
	 * @var string[]
	 */
	const IDENTITY_KEYS = array( 'id', 'name', 'type', 'custom_field', 'parent', 'columnPosition' );

	/**
	 * Standard account fields the free registration form supports.
	 *
	 * @return array<string, array{type: string, label: string}> Keyed by field name.
	 */
	public static function standard_fields(): array {
		return array(
			'first_name'                   => array(
				'type'  => 'text',
				'label' => __( 'First Name', 'wholesalex' ),
			),
			'last_name'                    => array(
				'type'  => 'text',
				'label' => __( 'Last Name', 'wholesalex' ),
			),
			'user_email'                   => array(
				'type'  => 'email',
				'label' => __( 'Email', 'wholesalex' ),
			),
			'user_confirm_email'           => array(
				'type'  => 'email',
				'label' => __( 'Confirm Email', 'wholesalex' ),
			),
			'user_login'                   => array(
				'type'  => 'text',
				'label' => __( 'Username', 'wholesalex' ),
			),
			'user_pass'                    => array(
				'type'  => 'password',
				'label' => __( 'Password', 'wholesalex' ),
			),
			'user_confirm_pass'            => array(
				'type'  => 'password',
				'label' => __( 'Confirm Password', 'wholesalex' ),
			),
			'display_name'                 => array(
				'type'  => 'text',
				'label' => __( 'Display Name', 'wholesalex' ),
			),
			'nickname'                     => array(
				'type'  => 'text',
				'label' => __( 'Nickname', 'wholesalex' ),
			),
			'description'                  => array(
				'type'  => 'textarea',
				'label' => __( 'User Bio', 'wholesalex' ),
			),
			'url'                          => array(
				'type'  => 'url',
				'label' => __( 'Website', 'wholesalex' ),
			),
			'wholesalex_registration_role' => array(
				'type'  => 'select',
				'label' => __( 'Select Role', 'wholesalex' ),
			),
			'wholesalex_term_condition'    => array(
				'type'  => 'termCondition',
				'label' => __( 'Terms and Conditions', 'wholesalex' ),
			),
		);
	}

	/**
	 * Return the saved form, or the default form when nothing is saved yet.
	 *
	 * @return array
	 */
	public static function get_form(): array {
		$form = WholesaleX_CommonUtils::get_new_form_builder_data();
		$rows = array();
		foreach ( is_array( $form['registrationFields'] ?? null ) ? $form['registrationFields'] : array() as $row ) {
			if ( is_array( $row ) && is_array( $row['columns'] ?? null ) ) {
				$row['columns'] = array_values( $row['columns'] );
			}
			$rows[] = $row;
		}
		$form['registrationFields'] = $rows;
		return $form;
	}

	/**
	 * Sanitize a JSON payload from the form builder.
	 *
	 * @param string $payload JSON payload, slashed as the builder sends it.
	 * @return string|\WP_Error Sanitized form as JSON.
	 */
	public static function prepare_payload( $payload ) {
		$decoded = json_decode( wp_unslash( $payload ), true );

		if ( ! is_array( $decoded ) ) {
			return new \WP_Error( 'invalid_form_builder_payload', __( 'Invalid registration form data.', 'wholesalex' ) );
		}

		return self::prepare_form( $decoded );
	}

	/**
	 * Sanitize a decoded form while preserving extension-owned data.
	 *
	 * @param array $decoded Proposed form.
	 * @return string|\WP_Error Sanitized form as JSON.
	 */
	public static function prepare_form( array $decoded ) {
		$stored = get_option( self::OPTION, '' );
		$saved  = is_string( $stored ) ? json_decode( $stored, true ) : null;
		$saved  = is_array( $saved ) ? $saved : array();
		$saved  = WholesaleX_CommonUtils::normalize_migrated_registration_fields( $saved );
		if ( ! $stored && get_option( '__wholesalex_registration_form', '' ) ) {
			// Preserve the authoritative converted legacy form on its first save.
			$saved = WholesaleX_CommonUtils::get_new_form_builder_data();
		}
		$decoded = Registration_Provider::preserve_form( $decoded, $saved );
		if ( is_wp_error( $decoded ) ) {
			return $decoded;
		}
		$preserved = $decoded;
		$decoded   = self::sanitize_value( $decoded );
		// Provider-owned records have their own validator; preserve their exact stored shape.
		foreach ( $preserved['registrationFields'] ?? array() as $row_index => $row ) {
			foreach ( $row as $key => $value ) {
				if ( ! in_array( $key, Registration_Provider::standard_row_keys(), true ) ) {
					$decoded['registrationFields'][ $row_index ][ $key ] = $value;
				}
			}
			foreach ( $row['columns'] ?? array() as $index => $field ) {
				if ( ! WholesaleX_CommonUtils::is_standard_registration_field( $field ) ) {
					$decoded['registrationFields'][ $row_index ]['columns'][ $index ] = $field;
				} else {
					foreach ( $field as $key => $value ) {
						if ( ! in_array( $key, Registration_Provider::standard_field_keys(), true ) ) {
							$decoded['registrationFields'][ $row_index ]['columns'][ $index ][ $key ] = $value;
						}
					}
				}
			}
		}
		foreach ( array_diff( array_keys( $preserved ), array_keys( WholesaleX_CommonUtils::get_empty_form() ) ) as $key ) {
			$decoded[ $key ] = $preserved[ $key ];
		}
		$decoded = Registration_Provider::preserve_unknown( $decoded, $saved, Registration_Provider::base_form_shape() );

		return wp_json_encode( $decoded );
	}

	/**
	 * Sanitize and save a JSON payload from the form builder.
	 *
	 * @param string $payload JSON payload, slashed as the builder sends it.
	 * @return string|\WP_Error Saved form as JSON.
	 */
	public static function save_payload( $payload ) {
		$form_data = self::prepare_payload( $payload );
		if ( is_wp_error( $form_data ) ) {
			return $form_data;
		}
		self::store( $form_data );
		return $form_data;
	}

	/**
	 * Sanitize and save a complete form.
	 *
	 * @param array $form Proposed form.
	 * @return array|\WP_Error Saved form.
	 */
	public static function save_form( array $form ) {
		$form_data = self::prepare_form( $form );
		if ( is_wp_error( $form_data ) ) {
			return $form_data;
		}
		self::store( $form_data );
		return self::get_form();
	}

	/**
	 * Find a field by its name.
	 *
	 * @param array  $form Form data.
	 * @param string $name Field name.
	 * @return int[]|null Row index and column index, or null when absent.
	 */
	public static function locate( array $form, string $name ) {
		foreach ( $form['registrationFields'] ?? array() as $row_index => $row ) {
			foreach ( ( is_array( $row ) ? ( $row['columns'] ?? array() ) : array() ) as $column_index => $field ) {
				if ( is_array( $field ) && ( $field['name'] ?? null ) === $name ) {
					return array( $row_index, $column_index );
				}
			}
		}
		return null;
	}

	/**
	 * Add a field to the form.
	 *
	 * @param array $field     Field definition. `name` and `type` are required.
	 * @param array $placement Optional `beside` (name of a field whose row
	 *                         receives this field as its second column) or
	 *                         `row_position` (zero-based row index).
	 * @return array|\WP_Error Saved form.
	 */
	public static function add_field( array $field, array $placement = array() ) {
		$form = self::get_form();
		$name = (string) ( $field['name'] ?? '' );
		if ( '' === $name || empty( $field['type'] ) ) {
			return self::error( __( 'A field needs a name and a type.', 'wholesalex' ) );
		}
		if ( null !== self::locate( $form, $name ) ) {
			return self::error(
				sprintf(
					/* translators: %s: field name. */
					__( 'The field "%s" is already on the registration form. Update it instead.', 'wholesalex' ),
					$name
				)
			);
		}

		$rows        = $form['registrationFields'];
		$field['id'] = self::new_field_id();
		$beside      = isset( $placement['beside'] ) ? (string) $placement['beside'] : '';

		if ( '' !== $beside ) {
			$location = self::locate( array( 'registrationFields' => $rows ), $beside );
			if ( null === $location ) {
				return self::not_on_form( $beside );
			}
			$row = $rows[ $location[0] ];
			if ( count( $row['columns'] ?? array() ) >= 2 ) {
				return self::error(
					sprintf(
						/* translators: %s: field name. */
						__( 'The row with "%s" already has two fields. Choose another field or add a new row.', 'wholesalex' ),
						$beside
					)
				);
			}
			$field['parent']         = $row['id'] ?? '';
			$field['columnPosition'] = 'right';
			$row['columns'][]        = $field;
			$row['isMultiColumn']    = true;
			$rows[ $location[0] ]    = $row;
		} else {
			$row_id                  = self::new_row_id( $rows );
			$field['parent']         = $row_id;
			$field['columnPosition'] = 'left';
			$position                = isset( $placement['row_position'] ) ? max( 0, min( (int) $placement['row_position'], count( $rows ) ) ) : count( $rows );
			array_splice(
				$rows,
				$position,
				0,
				array(
					array(
						'id'            => $row_id,
						'type'          => 'row',
						'columns'       => array( $field ),
						'isMultiColumn' => false,
					),
				)
			);
		}

		$form['registrationFields'] = $rows;
		return self::save_form( $form );
	}

	/**
	 * Change the properties of a field and optionally move it to another row position.
	 *
	 * @param string   $name         Field name.
	 * @param array    $changes      Field properties to change, in the stored format.
	 * @param int|null $row_position Zero-based row index to move the field to.
	 * @return array|\WP_Error Saved form.
	 */
	public static function update_field( string $name, array $changes, $row_position = null ) {
		$form     = self::get_form();
		$location = self::locate( $form, $name );
		if ( null === $location ) {
			return self::not_on_form( $name );
		}

		$changes = array_diff_key( $changes, array_flip( self::IDENTITY_KEYS ) );
		if ( in_array( $name, self::ESSENTIAL_FIELDS, true ) && ( ( array_key_exists( 'status', $changes ) && ! $changes['status'] ) || ( array_key_exists( 'required', $changes ) && ! $changes['required'] ) ) ) {
			return self::error(
				sprintf(
					/* translators: %s: field name. */
					__( 'The "%s" field must stay active and required, or customers cannot register.', 'wholesalex' ),
					$name
				)
			);
		}
		if ( in_array( $name, self::ALWAYS_REQUIRED_FIELDS, true ) && array_key_exists( 'required', $changes ) && ! $changes['required'] ) {
			return self::error(
				sprintf(
					/* translators: %s: field name. */
					__( 'The "%s" field is always required.', 'wholesalex' ),
					$name
				)
			);
		}

		$rows = $form['registrationFields'];

		$rows[ $location[0] ]['columns'][ $location[1] ] = array_replace( $rows[ $location[0] ]['columns'][ $location[1] ], $changes );

		if ( null !== $row_position ) {
			$rows = self::move( $rows, $location, (int) $row_position );
		}

		$form['registrationFields'] = $rows;
		return self::save_form( $form );
	}

	/**
	 * Remove a field from the form.
	 *
	 * @param string $name Field name.
	 * @return array|\WP_Error Saved form.
	 */
	public static function remove_field( string $name ) {
		if ( in_array( $name, self::ESSENTIAL_FIELDS, true ) ) {
			return self::error(
				sprintf(
					/* translators: %s: field name. */
					__( 'The "%s" field cannot be removed, or customers cannot register.', 'wholesalex' ),
					$name
				)
			);
		}

		$form     = self::get_form();
		$location = self::locate( $form, $name );
		if ( null === $location ) {
			return self::not_on_form( $name );
		}

		$rows  = $form['registrationFields'];
		$field = $rows[ $location[0] ]['columns'][ $location[1] ];
		$rows  = self::detach( $rows, $location );

		// Extension fields are only deleted when the deletion is explicit.
		if ( ! WholesaleX_CommonUtils::is_standard_registration_field( $field ) ) {
			$form['_registrationProviderDeletes'] = array( Registration_Provider::identity( $field ) );
		}

		$form['registrationFields'] = $rows;
		return self::save_form( $form );
	}

	/**
	 * Move a field to a row position, giving it its own row.
	 *
	 * @param array $rows         Rows.
	 * @param int[] $location     Row index and column index of the field.
	 * @param int   $row_position Target zero-based row index.
	 * @return array Rows.
	 */
	private static function move( array $rows, array $location, int $row_position ): array {
		$row = $rows[ $location[0] ];
		if ( 1 === count( $row['columns'] ) ) {
			array_splice( $rows, $location[0], 1 );
			$position = max( 0, min( $row_position, count( $rows ) ) );
			array_splice( $rows, $position, 0, array( $row ) );
			return array_values( $rows );
		}

		$field = $row['columns'][ $location[1] ];
		$rows  = self::detach( $rows, $location );
		$id    = self::new_row_id( $rows );

		$field['parent']         = $id;
		$field['columnPosition'] = 'left';
		$position                = max( 0, min( $row_position, count( $rows ) ) );
		array_splice(
			$rows,
			$position,
			0,
			array(
				array(
					'id'            => $id,
					'type'          => 'row',
					'columns'       => array( $field ),
					'isMultiColumn' => false,
				),
			)
		);
		return array_values( $rows );
	}

	/**
	 * Take a field out of its row, removing the row when it becomes empty.
	 *
	 * @param array $rows     Rows.
	 * @param int[] $location Row index and column index of the field.
	 * @return array Rows.
	 */
	private static function detach( array $rows, array $location ): array {
		$row = $rows[ $location[0] ];
		array_splice( $row['columns'], $location[1], 1 );
		if ( empty( $row['columns'] ) ) {
			array_splice( $rows, $location[0], 1 );
			return array_values( $rows );
		}
		$row['isMultiColumn']                = false;
		$row['columns']                      = array_values( $row['columns'] );
		$row['columns'][0]['columnPosition'] = 'left';
		$rows[ $location[0] ]                = $row;
		return array_values( $rows );
	}

	/**
	 * Generate a row ID in the builder's format that is not in use.
	 *
	 * @param array $rows Existing rows.
	 * @return string
	 */
	private static function new_row_id( array $rows ): string {
		$used = array();
		foreach ( $rows as $row ) {
			if ( is_array( $row ) && isset( $row['id'] ) && is_scalar( $row['id'] ) ) {
				$used[ (string) $row['id'] ] = true;
			}
		}
		$number = (int) substr( (string) self::milliseconds(), 6 );
		while ( isset( $used[ 'row_' . $number ] ) ) {
			++$number;
		}
		return 'row_' . $number;
	}

	/**
	 * Generate a field ID in the builder's format.
	 *
	 * @return string
	 */
	private static function new_field_id(): string {
		return self::milliseconds() . '_' . strtolower( wp_generate_password( 11, false, false ) );
	}

	/**
	 * Current Unix time in milliseconds.
	 *
	 * @return int
	 */
	private static function milliseconds(): int {
		return (int) floor( microtime( true ) * 1000 );
	}

	/**
	 * Save sanitized form JSON and refresh the request cache.
	 *
	 * @param string $form_data Sanitized form as JSON.
	 * @return void
	 */
	private static function store( string $form_data ): void {
		update_option( self::OPTION, $form_data );
		$GLOBALS['wholesalex_registration_fields'] = WholesaleX_CommonUtils::get_form_fields();
	}

	/**
	 * Recursively sanitize form builder values while preserving arrays and scalar types.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private static function sanitize_value( $value ) {
		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $key => $item ) {
				$sanitized_key               = is_string( $key ) ? sanitize_text_field( $key ) : $key;
				$sanitized[ $sanitized_key ] = self::sanitize_value( $item );
			}

			return $sanitized;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Build a validation error.
	 *
	 * @param string $message Message.
	 * @return \WP_Error
	 */
	private static function error( string $message ): \WP_Error {
		return new \WP_Error( 'wholesalex_invalid_registration_field', $message, array( 'status' => 400 ) );
	}

	/**
	 * Build an error for a field that is not on the form.
	 *
	 * @param string $name Field name.
	 * @return \WP_Error
	 */
	private static function not_on_form( string $name ): \WP_Error {
		return new \WP_Error(
			'wholesalex_registration_field_not_found',
			sprintf(
				/* translators: %s: field name. */
				__( 'The field "%s" is not on the registration form.', 'wholesalex' ),
				$name
			),
			array( 'status' => 404 )
		);
	}
}
