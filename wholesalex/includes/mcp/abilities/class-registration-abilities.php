<?php
/**
 * Registration form tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP\Abilities;

use WHOLESALEX\MCP\Access;
use WHOLESALEX\MCP\Fields;
use WHOLESALEX\MCP\Lookup;
use WHOLESALEX\MCP\Schema;
use WHOLESALEX\Registration_Form_Service;
use WHOLESALEX\WholesaleX_CommonUtils;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the registration form and adds, edits, moves and removes its fields.
 *
 * Writes go through `Registration_Form_Service`, which saves the form the
 * same way the form builder does, so extension data is preserved and every
 * value is sanitized.
 */
final class Registration_Abilities {

	/**
	 * Password strength rules, tool value => label stored with the rule.
	 *
	 * @var array<string, string>
	 */
	const PASSWORD_RULES = array(
		'uppercase_condition'         => 'Uppercase',
		'lowercase_condition'         => 'Lowercase',
		'special_character_condition' => 'Special Character',
		'min_length_condition'        => 'Min 8 Length',
	);

	/**
	 * Return the tool definitions.
	 *
	 * @return array<string, array>
	 */
	public static function definitions(): array {
		return array(
			'wholesalex/get-registration-form'     => array(
				'label'        => __( 'Get the registration form', 'wholesalex' ),
				'description'  => __( 'Returns the fields of the WholesaleX registration form in display order, and the standard account fields that are not on the form yet.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::no_input(),
				'callback'     => array( __CLASS__, 'get_form' ),
			),
			'wholesalex/add-registration-field'    => array(
				'label'        => __( 'Add a registration form field', 'wholesalex' ),
				'description'  => __( 'Adds a standard account field to the WholesaleX registration form, for example first_name, last_name or the role selector. By default the field goes in a new row at the end of the form.', 'wholesalex' ),
				'operation'    => Access::WRITE,
				'input_schema' => self::field_schema( true ),
				'callback'     => array( __CLASS__, 'add_field' ),
			),
			'wholesalex/update-registration-field' => array(
				'label'        => __( 'Update a registration form field', 'wholesalex' ),
				'description'  => __( 'Changes a field on the WholesaleX registration form, such as its label, placeholder, whether it is required or active, or its position. Only the properties you send change.', 'wholesalex' ),
				'operation'    => Access::WRITE,
				'idempotent'   => true,
				'input_schema' => self::field_schema( false ),
				'callback'     => array( __CLASS__, 'update_field' ),
			),
			'wholesalex/remove-registration-field' => array(
				'label'        => __( 'Remove a registration form field', 'wholesalex' ),
				'description'  => __( 'Removes a field from the WholesaleX registration form. Email and password cannot be removed. To hide a field temporarily, update it with active false instead.', 'wholesalex' ),
				'operation'    => Access::DELETE,
				'input_schema' => Schema::object( array( 'name' => self::name_schema() ), array( 'name' ) ),
				'callback'     => array( __CLASS__, 'remove_field' ),
			),
		);
	}

	/**
	 * Return the form.
	 *
	 * @return array
	 */
	public static function get_form(): array {
		return self::describe( Registration_Form_Service::get_form() );
	}

	/**
	 * Add a field.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function add_field( array $input ) {
		$name     = (string) $input['name'];
		$standard = Registration_Form_Service::standard_fields();
		if ( isset( $standard[ $name ] ) ) {
			$field = array(
				'type'         => $standard[ $name ]['type'],
				'label'        => $standard[ $name ]['label'],
				'name'         => $name,
				'isLabelHide'  => false,
				'placeholder'  => $standard[ $name ]['label'],
				'custom_field' => false,
				'status'       => true,
			);
			if ( in_array( $name, array( 'user_confirm_pass', 'user_confirm_email', 'user_email', 'user_pass', 'wholesalex_registration_role', 'wholesalex_term_condition' ), true ) ) {
				$field['required'] = true;
			}
			if ( 'wholesalex_term_condition' === $name ) {
				$field['default_text'] = __( 'I agree to the Terms and Conditions {Privacy Policy}', 'wholesalex' );
			}
		} else {
			$field = array( 'name' => $name );
		}

		$changes = self::to_storage( $input, $field );
		if ( is_wp_error( $changes ) ) {
			return $changes;
		}
		$field = array_replace( $field, $changes );
		if ( ! isset( $standard[ $name ] ) && empty( $field['custom_field'] ) ) {
			/* translators: %s: comma-separated field names. */
			return Fields::invalid( sprintf( __( 'Unknown field. The registration form supports these fields: %s.', 'wholesalex' ), implode( ', ', array_keys( $standard ) ) ) );
		}

		$placement = array();
		if ( isset( $input['beside_field'] ) ) {
			$placement['beside'] = (string) $input['beside_field'];
		}
		if ( isset( $input['row_position'] ) ) {
			$placement['row_position'] = (int) $input['row_position'];
		}
		$form = Registration_Form_Service::add_field( $field, $placement );
		// Extensions may give a custom field a unique stored name.
		return is_wp_error( $form ) ? $form : self::describe( $form, (string) $field['name'] );
	}

	/**
	 * Update a field.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function update_field( array $input ) {
		$name     = (string) $input['name'];
		$form     = Registration_Form_Service::get_form();
		$location = Registration_Form_Service::locate( $form, $name );
		if ( null === $location ) {
			return self::not_on_form( $name );
		}
		$existing = $form['registrationFields'][ $location[0] ]['columns'][ $location[1] ];
		$changes  = self::to_storage( $input, $existing );
		if ( is_wp_error( $changes ) ) {
			return $changes;
		}
		$saved = Registration_Form_Service::update_field( $name, $changes, isset( $input['row_position'] ) ? (int) $input['row_position'] : null );
		return is_wp_error( $saved ) ? $saved : self::describe( $saved, $name );
	}

	/**
	 * Remove a field.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function remove_field( array $input ) {
		$name  = (string) $input['name'];
		$saved = Registration_Form_Service::remove_field( $name );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}
		return array(
			'removed' => true,
			'name'    => $name,
		) + self::describe( $saved );
	}

	/**
	 * Convert tool input into stored field properties.
	 *
	 * @param array $input Tool input.
	 * @param array $field Current field definition.
	 * @return array|\WP_Error
	 */
	private static function to_storage( array $input, array $field ) {
		$name    = (string) ( $field['name'] ?? '' );
		$changes = array();
		$map     = array(
			'label'       => 'label',
			'placeholder' => 'placeholder',
			'help_text'   => 'help_message',
		);
		foreach ( $map as $tool_key => $stored_key ) {
			if ( array_key_exists( $tool_key, $input ) ) {
				$changes[ $stored_key ] = sanitize_text_field( (string) $input[ $tool_key ] );
			}
		}
		foreach ( array(
			'required'   => 'required',
			'active'     => 'status',
			'hide_label' => 'isLabelHide',
		) as $tool_key => $stored_key ) {
			if ( array_key_exists( $tool_key, $input ) ) {
				$changes[ $stored_key ] = (bool) $input[ $tool_key ];
			}
		}
		if ( array_key_exists( 'hidden_for_role_ids', $input ) ) {
			$rows = Lookup::role_rows( (array) $input['hidden_for_role_ids'] );
			if ( is_wp_error( $rows ) ) {
				return $rows;
			}
			$changes['excludeRoles'] = $rows;
		}
		if ( array_key_exists( 'terms_text', $input ) || array_key_exists( 'terms_link', $input ) ) {
			if ( 'wholesalex_term_condition' !== $name ) {
				return Fields::invalid( __( 'terms_text and terms_link only apply to the wholesalex_term_condition field.', 'wholesalex' ) );
			}
			if ( array_key_exists( 'terms_text', $input ) ) {
				$changes['default_text'] = sanitize_text_field( (string) $input['terms_text'] );
			}
			if ( array_key_exists( 'terms_link', $input ) ) {
				$link = trim( (string) $input['terms_link'] );
				$url  = esc_url_raw( $link, array( 'http', 'https' ) );
				if ( '' !== $link && '' === $url ) {
					return Fields::invalid( __( 'terms_link must be an http or https URL.', 'wholesalex' ) );
				}
				$changes['term_link'] = $url;
			}
		}
		if ( array_key_exists( 'password_rules', $input ) || array_key_exists( 'password_rules_message', $input ) ) {
			if ( 'user_pass' !== $name ) {
				return Fields::invalid( __( 'password_rules and password_rules_message only apply to the user_pass field.', 'wholesalex' ) );
			}
			if ( array_key_exists( 'password_rules', $input ) ) {
				$rules  = array();
				$labels = self::PASSWORD_RULES;
				foreach ( array_unique( array_map( 'strval', (array) $input['password_rules'] ) ) as $rule ) {
					if ( array_key_exists( $rule, $labels ) ) {
						$rules[] = array(
							'name'  => $labels[ $rule ],
							'value' => $rule,
						);
					}
				}
				$changes['passwordStrength'] = $rules;
			}
			if ( array_key_exists( 'password_rules_message', $input ) ) {
				$changes['password_strength_message'] = sanitize_text_field( (string) $input['password_rules_message'] );
			}
		}

		/**
		 * Filters the stored field properties built from AI tool input.
		 *
		 * Extensions that add field properties with
		 * `wholesalex_mcp_registration_field_properties` convert their input
		 * here. Return a WP_Error to reject the input.
		 *
		 * @since 3.2.0
		 *
		 * @param array $changes Stored field properties to save.
		 * @param array $input   Tool input.
		 * @param array $field   Current field definition.
		 */
		$changes = apply_filters( 'wholesalex_mcp_registration_field_to_storage', $changes, $input, $field );
		if ( ! is_array( $changes ) && ! is_wp_error( $changes ) ) {
			return Fields::invalid( __( 'The field settings could not be prepared.', 'wholesalex' ) );
		}
		return $changes;
	}

	/**
	 * Describe the form for tool output.
	 *
	 * @param array  $form  Form data.
	 * @param string $focus Name of the field that was just changed, if any.
	 * @return array
	 */
	private static function describe( array $form, string $focus = '' ): array {
		$fields = array();
		$used   = array();
		foreach ( $form['registrationFields'] ?? array() as $row_index => $row ) {
			foreach ( ( is_array( $row ) ? ( $row['columns'] ?? array() ) : array() ) as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				$name          = (string) ( $field['name'] ?? '' );
				$used[ $name ] = true;
				$fields[]      = self::describe_field( $field, (int) $row_index );
			}
		}

		$unused = array();
		foreach ( Registration_Form_Service::standard_fields() as $name => $standard ) {
			if ( ! isset( $used[ $name ] ) ) {
				$unused[] = array(
					'name'  => $name,
					'type'  => $standard['type'],
					'label' => $standard['label'],
				);
			}
		}

		$output = array(
			'fields'                 => $fields,
			'unused_standard_fields' => $unused,
		);
		if ( '' !== $focus ) {
			foreach ( $fields as $field ) {
				if ( $field['name'] === $focus ) {
					$output = array( 'field' => $field ) + $output;
					break;
				}
			}
		}
		return $output;
	}

	/**
	 * Describe one field for tool output.
	 *
	 * @param array $field     Stored field.
	 * @param int   $row_index Row position.
	 * @return array
	 */
	private static function describe_field( array $field, int $row_index ): array {
		$output = array(
			'name'                => (string) ( $field['name'] ?? '' ),
			'type'                => (string) ( $field['type'] ?? '' ),
			'label'               => (string) ( $field['label'] ?? '' ),
			'active'              => ! empty( $field['status'] ),
			'required'            => ! empty( $field['required'] ),
			'placeholder'         => (string) ( $field['placeholder'] ?? '' ),
			'help_text'           => (string) ( $field['help_message'] ?? '' ),
			'hide_label'          => ! empty( $field['isLabelHide'] ),
			'hidden_for_role_ids' => wp_list_pluck( Lookup::references( $field['excludeRoles'] ?? array(), 'string' ), 'id' ),
			'standard'            => WholesaleX_CommonUtils::is_standard_registration_field( $field ),
			'row_position'        => $row_index,
			'column'              => 'right' === ( $field['columnPosition'] ?? 'left' ) ? 'right' : 'left',
		);
		if ( 'wholesalex_term_condition' === $output['name'] ) {
			$output['terms_text'] = (string) ( $field['default_text'] ?? '' );
			$output['terms_link'] = (string) ( $field['term_link'] ?? '' );
		}
		if ( 'user_pass' === $output['name'] ) {
			$output['password_rules']         = wp_list_pluck( Lookup::references( $field['passwordStrength'] ?? array(), 'string' ), 'id' );
			$output['password_rules_message'] = (string) ( $field['password_strength_message'] ?? '' );
		}
		return $output;
	}

	/**
	 * Build an error for a field that is not on the form.
	 *
	 * @param string $name Field name.
	 * @return \WP_Error
	 */
	private static function not_on_form( string $name ): \WP_Error {
		return new \WP_Error(
			'wholesalex_mcp_not_found',
			sprintf(
				/* translators: %s: field name. */
				__( 'The field "%s" is not on the registration form. Use the wholesalex-get-registration-form tool to see the fields.', 'wholesalex' ),
				$name
			),
			array( 'status' => 404 )
		);
	}

	/**
	 * Schema of a field name.
	 *
	 * @return array
	 */
	private static function name_schema(): array {
		$schema            = Schema::string( __( 'Field name, for example first_name. Standard fields: ', 'wholesalex' ) . implode( ', ', array_keys( Registration_Form_Service::standard_fields() ) ) . '.', 128, 1 );
		$schema['pattern'] = '^[a-zA-Z0-9_-]+$';
		return $schema;
	}

	/**
	 * Input schema of the add and update tools.
	 *
	 * @param bool $for_add Whether the schema is for adding a field.
	 * @return array
	 */
	private static function field_schema( bool $for_add ): array {
		$properties = array(
			'name'                   => self::name_schema(),
			'label'                  => Schema::string( __( 'Label shown above the field.', 'wholesalex' ), 200 ),
			'placeholder'            => Schema::string( __( 'Placeholder text inside the field.', 'wholesalex' ), 200 ),
			'help_text'              => Schema::string( __( 'Help text shown below the field.', 'wholesalex' ), 300 ),
			'required'               => Schema::boolean( __( 'Whether customers must fill in the field. Email, password and their confirmations are always required.', 'wholesalex' ) ),
			'active'                 => Schema::boolean( __( 'Whether the field is shown on the form. Email and password always stay active.', 'wholesalex' ) ),
			'hide_label'             => Schema::boolean( __( 'Whether to hide the label.', 'wholesalex' ) ),
			'hidden_for_role_ids'    => Schema::id_list( __( 'Hide the field when the customer registers for one of these WholesaleX roles. Send an empty list to show it for every role.', 'wholesalex' ), 'string', 50 ),
			'terms_text'             => Schema::string( __( 'wholesalex_term_condition only: the agreement text. Text in {curly braces} becomes the terms_link.', 'wholesalex' ), 500 ),
			'terms_link'             => Schema::string( __( 'wholesalex_term_condition only: URL of the terms page.', 'wholesalex' ), 500 ),
			'password_rules'         => array(
				'type'        => 'array',
				'description' => __( 'user_pass only: password requirements.', 'wholesalex' ),
				'items'       => Schema::enum( __( 'Password requirement.', 'wholesalex' ), array_keys( self::PASSWORD_RULES ) ),
				'maxItems'    => count( self::PASSWORD_RULES ),
				'uniqueItems' => true,
			),
			'password_rules_message' => Schema::string( __( 'user_pass only: message shown when the password does not meet the requirements.', 'wholesalex' ), 300 ),
			'row_position'           => Schema::integer( __( 'Zero-based row position. Without beside_field the field gets its own row at this position.', 'wholesalex' ), 0, 200 ),
		);
		if ( $for_add ) {
			$properties['beside_field'] = Schema::string( __( 'Name of a field that is alone in its row. The new field is placed next to it as a second column.', 'wholesalex' ), 128 );
		}

		/**
		 * Filters the field properties accepted by the registration form AI tools.
		 *
		 * Convert added properties with `wholesalex_mcp_registration_field_to_storage`.
		 *
		 * @since 3.2.0
		 *
		 * @param array $properties Property schemas keyed by name.
		 * @param bool  $for_add    Whether the schema is for adding a field.
		 */
		$properties = apply_filters( 'wholesalex_mcp_registration_field_properties', $properties, $for_add );

		return Schema::object( is_array( $properties ) ? $properties : array(), array( 'name' ) );
	}
}
