<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Preserve the established common-utils include path.
/**
 * WholesaleX Initialization. Initialize All Files And Dependencies
 *
 * @link              https://www.wpxpo.com/
 * @since             1.0.0
 * @package           WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;


/**
 * WholesaleX_Initialization Class
 */
class WholesaleX_CommonUtils {
	/**
	 * Builder-only helper text for the registration role field.
	 *
	 * @return string
	 */
	public static function get_registration_role_builder_help_message() {
		return __( 'This field will only be shown on the Global Registration Form.', 'wholesalex' );
	}

	/**
	 * Translate built-in form builder text without changing custom text.
	 *
	 * @param mixed $text Text to translate.
	 * @return mixed
	 */
	public static function translate_form_builder_default_text( $text ) {
		if ( ! is_string( $text ) || '' === $text ) {
			return $text;
		}

		$translations = apply_filters(
			'wholesalex_form_builder_default_text_map',
			array(
				'Please select'                       => __( 'Please select', 'wholesalex' ),
				'Select Role'                         => __( 'Select Role', 'wholesalex' ),
				'Select Roles'                        => __( 'Select Roles', 'wholesalex' ),
				'Select Registration Roles'           => __( 'Select Registration Roles', 'wholesalex' ),
				'Select Option'                       => __( 'Select Option', 'wholesalex' ),
				'Username'                            => __( 'Username', 'wholesalex' ),
				'Username or Email'                   => __( 'Username or Email', 'wholesalex' ),
				'Email'                               => __( 'Email', 'wholesalex' ),
				'Password'                            => __( 'Password', 'wholesalex' ),
				'Confirm Password'                    => __( 'Confirm Password', 'wholesalex' ),
				'First Name'                          => __( 'First Name', 'wholesalex' ),
				'Last Name'                           => __( 'Last Name', 'wholesalex' ),
				'User Bio'                            => __( 'User Bio', 'wholesalex' ),
				'Nickname'                            => __( 'Nickname', 'wholesalex' ),
				'Display Name'                        => __( 'Display Name', 'wholesalex' ),
				'Website'                             => __( 'Website', 'wholesalex' ),
				'Confirm Email'                       => __( 'Confirm Email', 'wholesalex' ),
				'Term and Condition'                  => __( 'Term and Condition', 'wholesalex' ),
				'Remember me'                         => __( 'Remember me', 'wholesalex' ),
				'Register'                            => __( 'Register', 'wholesalex' ),
				'Log in'                              => __( 'Log in', 'wholesalex' ),
				'Login'                               => __( 'Login', 'wholesalex' ),
				'Sign In to Your Account'             => __( 'Sign In to Your Account', 'wholesalex' ),
				"Don't have an account? Sign up now!" => __( "Don't have an account? Sign up now!", 'wholesalex' ),
				'Text'                                => __( 'Text', 'wholesalex' ),
				'Text Area'                           => __( 'Text Area', 'wholesalex' ),
				'Radio'                               => __( 'Radio', 'wholesalex' ),
				'Checkbox'                            => __( 'Checkbox', 'wholesalex' ),
				'File'                                => __( 'File', 'wholesalex' ),
				'Select'                              => __( 'Select', 'wholesalex' ),
				'Number'                              => __( 'Number', 'wholesalex' ),
				'Date'                                => __( 'Date', 'wholesalex' ),
				'Value 1'                             => __( 'Value 1', 'wholesalex' ),
				'Value 2'                             => __( 'Value 2', 'wholesalex' ),
				'Value 3'                             => __( 'Value 3', 'wholesalex' ),
				'I agree to the Terms and Conditions {Privacy Policy}' => __( 'I agree to the Terms and Conditions {Privacy Policy}', 'wholesalex' ),
			)
		);

		return isset( $translations[ $text ] ) ? $translations[ $text ] : $text;
	}

	/**
	 * Translate built-in default labels/options in a form-builder field.
	 *
	 * @param array $field Form field.
	 * @return array
	 */
	public static function translate_form_builder_field( $field ) {
		if ( ! is_array( $field ) ) {
			return $field;
		}

		if (
			isset( $field['name'], $field['help_message'] )
			&& 'wholesalex_registration_role' === $field['name']
			&& in_array(
				$field['help_message'],
				array(
					'This field will only be shown on the Global Registration Form.',
					self::get_registration_role_builder_help_message(),
				),
				true
			)
		) {
			unset( $field['help_message'] );
		}

		foreach ( array( 'label', 'placeholder', 'help_message', 'default_text', 'password_strength_message' ) as $key ) {
			if ( isset( $field[ $key ] ) ) {
				$field[ $key ] = self::translate_form_builder_default_text( $field[ $key ] );
			}
		}

		if ( isset( $field['option'] ) && is_array( $field['option'] ) ) {
			foreach ( $field['option'] as $index => $option ) {
				if ( isset( $option['name'] ) ) {
					$field['option'][ $index ]['name'] = self::translate_form_builder_default_text( $option['name'] );
				}
			}
		}

		return $field;
	}

	/**
	 * Get the default registration form colors.
	 *
	 * @return array
	 */
	public static function get_form_builder_default_appearance_colors() {
		$template = json_decode( file_get_contents( __DIR__ . '/data/registration-template-1.json' ), true );
		return $template['style']['appearance']['defaultAdvancedColors'];
	}

		/**
		 * Get Empty Form
		 *
		 * @return array
		 */
	public static function get_empty_form() {
		$default_form = array(
			'registrationFormHeader' =>
			array(
				'isShowFormTitle'   => true,
				'isHideDescription' => false,
				'title'             => __( 'Register', 'wholesalex' ),
				'description'       => __( "Don't have an account? Sign up now!", 'wholesalex' ),
				'styles'            =>
				array(
					'title'       =>
						array(
							'color'     => '#343A46',
							'size'      => 24,
							'weight'    => 500,
							'transform' => '',
							'padding'   => '',
						),
					'description' =>
						array(
							'color'     => '#343A46',
							'size'      => 14,
							'weight'    => 400,
							'transform' => '',
							'padding'   => '',
						),
				),
			),
			'loginFormHeader'        =>
			array(
				'isShowFormTitle'   => true,
				'isHideDescription' => false,
				'title'             => __( 'Login', 'wholesalex' ),
				'description'       => __( 'Sign In to Your Account', 'wholesalex' ),
				'styles'            =>
				array(
					'title'       =>
					array(
						'color'     => '#343A46',
						'size'      => 24,
						'weight'    => 500,
						'transform' => '',
						'padding'   => '',
					),
					'description' =>
					array(
						'color'     => '#343A46',
						'size'      => 14,
						'weight'    => 400,
						'transform' => '',
						'padding'   => '',
					),
				),
			),
			'settings'               =>
			array(
				'inputVariation'  => 'variation_1',
				'isShowLoginForm' => false,
			),
			'fieldsName'             =>
			array(),
			'loginFields'            =>
			array(
				0 =>
				array(
					'id'            => 'login_row_1',
					'type'          => 'row',
					'columns'       =>
					array(
						0 =>
							array(
								'type'           => 'text',
								'label'          => __( 'Username or email address', 'wholesalex' ),
								'name'           => 'username',
								'isLabelHide'    => false,
								'placeholder'    => __( 'Username or Email', 'wholesalex' ),
								'columnPosition' => 'left',
								'parent'         => 'login_row_1',
								'isRequired'     => true,
							),
					),
					'isMultiColumn' => false,
				),
				1 =>
				array(
					'id'            => 'login_row_2',
					'type'          => 'row',
					'columns'       =>
					array(
						0 =>
							array(
								'type'           => 'password',
								'label'          => __( 'Password', 'wholesalex' ),
								'name'           => 'password',
								'isLabelHide'    => false,
								'placeholder'    => __( 'Password', 'wholesalex' ),
								'columnPosition' => 'left',
								'parent'         => 'login_row_2',
								'isRequired'     => true,
							),
					),
					'isMultiColumn' => false,
				),
				2 =>
				array(
					'id'            => 'login_row_3',
					'type'          => 'row',
					'columns'       =>
					array(
						0 =>
						array(
							'type'           => 'checkbox',
							'label'          => '',
							'name'           => 'rememberme',
							'isLabelHide'    => true,
							'columnPosition' => 'left',
							'option'         =>
							array(
								0 =>
									array(
										'name'  => __( 'Remember me', 'wholesalex' ),
										'value' => 'rememberme',
									),
							),
							'parent'         => 'row_3438998',
							'excludeRoles'   =>
							array(),
						),
					),
					'isMultiColumn' => false,
				),
			),
			'registrationFields'     => self::get_default_registration_form_fields(),
			'registrationFormButton' =>
			array(
				'title' => __( 'Register', 'wholesalex' ),
			),
			'loginFormButton'        =>
			array(
				'title' => __( 'Log in', 'wholesalex' ),
			),
			'style'                  =>
			array(
				'color'       =>
				array(
					'field'     =>
					array(
						'signIn' =>
						array(
							'normal'  =>
							array(
								'label'       => '#343A46',
								'text'        => '#343A46',
								'background'  => '#FFF',
								'border'      => '#E9E9F0',
								'placeholder' => '#6C6E77',
							),
							'active'  =>
							array(
								'label'       => '#343A46',
								'text'        => '#343A46',
								'background'  => '#FFF',
								'border'      => '#111111',
								'placeholder' => '#6C6E77',
							),
							'warning' =>
							array(
								'label'       => '#343A46',
								'text'        => '#FF6C6C',
								'background'  => '#FFF',
								'border'      => '#FF6C6C',
								'placeholder' => '#6C6E77',
							),
						),
						'signUp' =>
						array(
							'normal'  =>
							array(
								'label'       => '#343A46',
								'text'        => '#343A46',
								'background'  => '#FFF',
								'border'      => '#E9E9F0',
								'placeholder' => '#6C6E77',
							),
							'active'  =>
							array(
								'label'       => '#343A46',
								'text'        => '#343A46',
								'background'  => '#FFF',
								'border'      => '#111111',
								'placeholder' => '#6C6E77',
							),
							'warning' =>
							array(
								'label'       => '#343A46',
								'text'        => '#FF6C6C',
								'background'  => '#FFF',
								'border'      => '#FF6C6C',
								'placeholder' => '#6C6E77',
							),
						),
					),
					'button'    =>
					array(
						'signIn' =>
						array(
							'normal' =>
							array(
								'text'       => '#fff',
								'background' => '#111111',
								'border'     => '',
							),
							'hover'  =>
							array(
								'text'       => '#fff',
								'background' => '#333333',
								'border'     => '',
							),
						),
						'signUp' =>
						array(
							'normal' =>
							array(
								'text'       => '#fff',
								'background' => '#111111',
								'border'     => '',
							),
							'hover'  =>
							array(
								'text'       => '#fff',
								'background' => '#333333',
								'border'     => '',
							),
						),
					),
					'container' =>
					array(
						'main'   =>
						array(
							'background' => '#FFF',
							'border'     => '#E9E9F0',
						),
						'signIn' =>
						array(
							'background' => '#FFF',
							'border'     => '',
						),
						'signUp' =>
						array(
							'background' => '#FFF',
							'border'     => '',
						),
					),
				),
				'typography'  =>
				array(
					'field'  =>
					array(
						'label' =>
						array(
							'size'      => 14,
							'weight'    => 500,
							'transform' => '',
						),
						'input' =>
						array(
							'size'      => 14,
							'weight'    => 400,
							'transform' => '',
						),
					),
					'button' =>
					array(
						'size'      => 14,
						'weight'    => 500,
						'transform' => '',
					),
				),
				'sizeSpacing' =>
				array(
					'input'     =>
					array(
						'width'        => 395,
						'border'       => 1,
						'borderRadius' => 2,
						'padding'      => 16,
					),
					'button'    =>
					array(
						'width'        => 50,
						'border'       => 0,
						'borderRadius' => 2,
						'padding'      => 13,
						'align'        => 'left',
					),
					'container' =>
					array(
						'main'   =>
						array(
							'width'        => '1200',
							'border'       => 1,
							'borderRadius' => 16,
							'padding'      => 0,
							'align'        => '',
							'separator'    => 1,
						),
						'signIn' =>
						array(
							'width'        => '',
							'border'       => 0,
							'borderRadius' => 16,
							'padding'      => 54,
							'align'        => '',
							'separator'    => '',
						),
						'signUp' =>
						array(
							'width'        => '',
							'border'       => 0,
							'borderRadius' => 16,
							'padding'      => 54,
							'align'        => '',
							'separator'    => '',
						),
					),
				),
				'appearance'  =>
				array(
					'advancedColors' => self::get_form_builder_default_appearance_colors(),
					'container'      => array(
						'color' => '#ffffff',
					),
				),
			),
		);
		return $default_form;
	}

	/**
	 * Get Default Registration Form Fields
	 *
	 * @return array
	 */
	public static function get_new_form_builder_data() {
		$form_data = '';

		$old_form = get_option( '__wholesalex_registration_form' );

		$new_form = get_option( 'wholesalex_registration_form' );

		$default_form = self::get_empty_form();
		if ( ! $new_form && $old_form ) {
			$old_form = json_decode( $old_form, true );
			foreach ( $old_form as $field ) {
				$field['columnPosition'] = 'left';
				$field['parent']         = wp_unique_id( 'whx_form' );
				$field['label']          = $field['title'];
				$field['status']         = true;
				$field['conditions']     = array(
					'status'   => 'show',
					'relation' => 'all',
					'tiers'    => array(
						array(
							'_id'       => '1',
							'condition' => '',
							'value'     => '',
							'field'     => '',
							'src'       => 'registration_form',
						),
					),
				);
				if ( ! isset( $field['option'] ) ) {
					$field['option'] = array(
						array(
							'name'  => __( 'Select Option', 'wholesalex' ),
							'value' => '',
						),
					);
				}
				unset( $field['title'] );
				$field['migratedFromOldBuilder']      = true;
				$default_form['registrationFields'][] =
				array(
					'id'            => $field['parent'],
					'type'          => 'row',
					'columns'       => array( $field ),
					'isMultiColumn' => false,
				);

			}

			$form_data = $default_form;

		} else {
			$form_data = json_decode( $new_form, true );
		}

		$form_data = is_array( $form_data ) ? $form_data : $default_form;
		$original_form_data = $form_data;
		$form_data = self::normalize_default_registration_template( $form_data );
		if ( ( $new_form && is_array( json_decode( $new_form, true ) ) && $original_form_data !== $form_data ) || ( ! $new_form && is_array( $old_form ) ) ) {
			update_option( 'wholesalex_registration_form', wp_json_encode( $form_data ) );
		}

		return $form_data;
	}

	/**
	 * Convert legacy template designs while preserving form content and settings.
	 *
	 * @param array $form_data Form builder configuration.
	 * @return array
	 */
	public static function normalize_default_registration_template( $form_data ) {
		$form_data['settings'] = isset( $form_data['settings'] ) && is_array( $form_data['settings'] ) ? $form_data['settings'] : array();
		if ( 'premade_1' !== ( $form_data['settings']['premadeTemplate'] ?? '' ) ) {
			$template = json_decode( file_get_contents( __DIR__ . '/data/registration-template-1.json' ), true );
			foreach ( array_keys( $form_data['settings'] ) as $key ) {
				if ( 0 === strpos( $key, 'appearance_' ) ) {
					unset( $form_data['settings'][ $key ] );
				}
			}
			$form_data['settings'] = array_merge( $form_data['settings'], $template['settings'] );
			$form_data['style'] = $template['style'];
			foreach ( array( 'registrationFormHeader', 'loginFormHeader' ) as $key ) {
				$form_data[ $key ] = isset( $form_data[ $key ] ) && is_array( $form_data[ $key ] ) ? $form_data[ $key ] : array();
				$form_data[ $key ]['styles'] = $template[ $key ]['styles'];
			}
		}
		if ( 3 !== ( $form_data['settings']['appearance_colorDefaultsVersion'] ?? null ) || isset( $form_data['settings']['appearance_colorTheme'] ) ) {
			$template = json_decode( file_get_contents( __DIR__ . '/data/registration-template-1.json' ), true );
			// Existing Classic forms keep their custom colors; retired palettes receive Classic defaults once.
			if ( 'classic' !== ( $form_data['settings']['appearance_colorTheme'] ?? '' ) ) {
				$form_data['style']['color'] = $template['style']['color'];
				$form_data['style']['appearance']['advancedColors'] = $template['style']['appearance']['advancedColors'];
				$form_data['style']['appearance']['container']['color'] = $template['style']['appearance']['container']['color'];
			}
			$form_data['style']['appearance']['defaultAdvancedColors'] = $template['style']['appearance']['defaultAdvancedColors'];
			$form_data['settings']['appearance_colorDefaultsVersion'] = 3;
		}
		unset( $form_data['settings']['appearance_colorTheme'] );
		$form_data['settings']['inputStyle'] = 'variation_1';
		$form_data['settings']['inputVariation'] = 'variation_1';
		return $form_data;
	}

	/**
	 * Get Default Registration Form Fields
	 */
	public static function get_default_registration_form_fields() {
		$is_woo_username     = get_option( 'woocommerce_registration_generate_username' );
		$registration_fields = array(
			...( isset( $is_woo_username ) && 'no' === $is_woo_username ? array(
				array(
					'id'            => 'regi_3',
					'type'          => 'row',
					'columns'       => array(
						array(
							'status'         => true,
							'type'           => 'text',
							'label'          => __( 'Username', 'wholesalex' ),
							'name'           => 'user_login',
							'isLabelHide'    => false,
							'placeholder'    => '',
							'columnPosition' => 'left',
							'parent'         => 'regi_3',
							'required'       => true,
							'conditions'     => array(
								'status'   => 'show',
								'relation' => 'all',
								'tiers'    => array(
									array(
										'_id'       => strval( time() ),
										'condition' => '',
										'field'     => '',
										'value'     => '',
										'src'       => 'registration_form',
									),
								),
							),
						),
					),
					'isMultiColumn' => false,
				),
			) : array() ),
			array(
				'id'            => 'regi_1',
				'type'          => 'row',
				'columns'       => array(
					array(
						'status'         => true,
						'type'           => 'email',
						'label'          => __( 'Email', 'wholesalex' ),
						'name'           => 'user_email',
						'isLabelHide'    => false,
						'placeholder'    => '',
						'columnPosition' => 'left',
						'parent'         => 'regi_1',
						'required'       => true,
						'conditions'     => array(
							'status'   => 'show',
							'relation' => 'all',
							'tiers'    => array(
								array(
									'_id'       => strval( time() ),
									'condition' => '',
									'field'     => '',
									'value'     => '',
									'src'       => 'registration_form',
								),
							),
						),
					),
				),
				'isMultiColumn' => false,
			),
			array(
				'id'            => 'regi_2',
				'type'          => 'row',
				'columns'       => array(
					array(
						'status'         => true,
						'type'           => 'password',
						'label'          => __( 'Password', 'wholesalex' ),
						'name'           => 'user_pass',
						'isLabelHide'    => false,
						'placeholder'    => '',
						'columnPosition' => 'left',
						'parent'         => 'regi_2',
						'required'       => true,
						'conditions'     => array(
							'status'   => 'show',
							'relation' => 'all',
							'tiers'    => array(
								array(
									'_id'       => strval( time() ),
									'condition' => '',
									'field'     => '',
									'value'     => '',
									'src'       => 'registration_form',
								),
							),
						),
					),
				),
				'isMultiColumn' => false,
			),
		);

		return $registration_fields;
	}

	/**
	 * Login controls share the field renderer, but cannot populate registration metadata.
	 *
	 * @param array $field Field definition.
	 * @return bool
	 */
	public static function is_standard_form_field( $field ) {
		$login_types = array(
			'username'   => 'text',
			'password'   => 'password',
			'rememberme' => 'checkbox',
		);
		return self::is_standard_registration_field( $field ) || (
			empty( $field['custom_field'] ) && isset( $login_types[ $field['name'] ?? '' ] )
			&& ( $field['type'] ?? '' ) === $login_types[ $field['name'] ]
		);
	}

	/**
	 * Standard account fields supplied by the base registration form.
	 *
	 * @param array $field Field definition.
	 * @return bool
	 */
	public static function is_standard_registration_field( $field ) {
		$types = array(
			'user_login'                   => 'text',
			'first_name'                   => 'text',
			'last_name'                    => 'text',
			'display_name'                 => 'text',
			'nickname'                     => 'text',
			'description'                  => 'textarea',
			'user_email'                   => 'email',
			'user_confirm_email'           => 'email',
			'user_pass'                    => 'password',
			'user_confirm_pass'            => 'password',
			'url'                          => 'url',
			'wholesalex_registration_role' => 'select',
			'wholesalex_term_condition'    => 'termCondition',
		);
		return empty( $field['custom_field'] ) && isset( $types[ $field['name'] ?? '' ] )
			&& ( $field['type'] ?? '' ) === $types[ $field['name'] ];
	}

	/** Collect the active fields supplied by the base form and registered extensions. */
	public static function get_form_fields() {
		$woo_custom_fields   = array();
		$registration_fields = array();
		$billing_fields      = array();
		$myaccount_fields    = array();

		$fields = self::get_new_form_builder_data();

		if ( isset( $fields['registrationFields'] ) && is_array( $fields['registrationFields'] ) ) {
			foreach ( $fields['registrationFields'] as $row ) {
				if ( isset( $row['columns'] ) && is_array( $row['columns'] ) ) {
					foreach ( $row['columns'] as $field ) {
						if ( ( isset( $field['status'] ) && $field['status'] ) ) {
							$field = self::translate_form_builder_field( $field );

							if ( apply_filters( 'wholesalex_registration_field_available', self::is_standard_registration_field( $field ), $field ) ) {
								$registration_fields[] = $field;
							}
						}
					}
				}
			}
		}

		return apply_filters(
			'wholesalex_registration_runtime_fields',
			array(
				'woo_custom_fields' => $woo_custom_fields,
				'wholesalex_fields' => $registration_fields,
				'billing_fields'    => $billing_fields,
				'myaccount_fields'  => $myaccount_fields,
			),
			$fields
		);
	}
}
