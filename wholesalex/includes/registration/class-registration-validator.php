<?php
/**
 * Registration policy consumed before customer creation.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Standard fields only; provider-owned fields keep their provider contract.
 */
final class Registration_Validator {
	/**
	 * Validate trusted standard definitions and the server challenge.
	 *
	 * @param array  $post Unslashed submission.
	 * @param array  $fields Saved fields.
	 * @param string $role Authorised registration role.
	 * @return true|\WP_Error
	 */
	public static function validate( $post, $fields, $role ) {
		$errors = array();
		foreach ( $fields as $field ) {
			if ( ! WholesaleX_CommonUtils::is_standard_registration_field( $field ) ) {
				continue;
			}
			$excluded = isset( $field['excludeRoles'] ) && is_array( $field['excludeRoles'] ) ? array_column( $field['excludeRoles'], 'value' ) : array();
			if ( in_array( $role, $excluded, true ) ) {
				continue;
			}
			$confirmation = 'user_confirm_email' === $field['name'] || ( 'user_confirm_pass' === $field['name'] && isset( $post['user_pass'] ) && '' !== $post['user_pass'] );
			if ( empty( $field['required'] ) && ! $confirmation ) {
				continue;
			}
			$name  = $field['name'];
			$value = $post[ $name ] ?? null;
			if ( ! is_string( $value ) || '' === $value || ( 'user_pass' !== $name && 'user_confirm_pass' !== $name && '' === trim( $value ) ) ) {
				$errors[ $name ] = __( 'This field is required.', 'wholesalex' );
			} elseif ( 'termCondition' === ( $field['type'] ?? '' ) && $name !== $value ) {
				$errors[ $name ] = __( 'Please accept the terms and conditions.', 'wholesalex' );
			}
		}
		$password = $post['user_pass'] ?? '';
		if ( ! is_string( $password ) || ( isset( $post['user_confirm_pass'] ) && ( ! is_string( $post['user_confirm_pass'] ) || $password !== $post['user_confirm_pass'] ) ) ) {
			$errors['user_pass'] = __( 'Password and Confirm password does not match!', 'wholesalex' );
		}
		if ( isset( $post['user_confirm_email'] ) && ( ! is_string( $post['user_confirm_email'] ) || ! isset( $post['user_email'] ) || $post['user_confirm_email'] !== $post['user_email'] ) ) {
			$errors['user_confirm_email'] = __( 'Email and confirmation email do not match.', 'wholesalex' );
		}
		foreach ( $fields as $field ) {
			if ( 'user_pass' !== ( $field['name'] ?? '' ) || ! is_string( $password ) || '' === $password || ! WholesaleX_CommonUtils::is_standard_registration_field( $field ) ) {
				continue;
			}
			$patterns = array(
				'uppercase_condition'         => '/[A-Z]/',
				'lowercase_condition'         => '/[a-z]/',
				'special_character_condition' => '/[!@#$%^&*()_+=\\-]/',
			);
			foreach ( (array) ( $field['passwordStrength'] ?? array() ) as $condition ) {
				$key = is_array( $condition ) ? ( $condition['value'] ?? '' ) : '';
				if ( ( isset( $patterns[ $key ] ) && ! preg_match( $patterns[ $key ], $password ) ) || ( 'min_length_condition' === $key && preg_match_all( '/./us', $password ) < 8 ) ) {
					$errors['user_pass'] = __( 'Password does not meet the configured strength requirements.', 'wholesalex' );
				}
			}
		}
		if ( $errors ) {
			return new \WP_Error( 'registration_validation', __( 'Please check the registration fields.', 'wholesalex' ), $errors );
		}
		require_once dirname( __DIR__ ) . '/recaptcha/class-recaptcha.php';
		return Recaptcha::instance()->validate_registration( $post );
	}
}
