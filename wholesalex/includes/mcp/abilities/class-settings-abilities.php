<?php
/**
 * Settings tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP\Abilities;

use WHOLESALEX\MCP\Access;
use WHOLESALEX\MCP\Schema;
use WHOLESALEX\MCP\Settings_Catalog;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and changes the WholesaleX settings, including the settings of the
 * active add-ons and the WholesaleX emails.
 *
 * Changes are checked in full before anything is saved, so one invalid value
 * leaves every setting unchanged. Saving goes through the same code the
 * Settings screen uses.
 */
final class Settings_Abilities {

	/**
	 * Maximum number of settings changed in one call.
	 *
	 * @var int
	 */
	const MAX_CHANGES = 50;

	/**
	 * Return the tool definitions.
	 *
	 * @return array<string, array>
	 */
	public static function definitions(): array {
		return array(
			'wholesalex/get-settings'    => array(
				'label'        => __( 'Get WholesaleX settings', 'wholesalex' ),
				'description'  => __( 'Lists the WholesaleX settings with their current values, grouped by the sections of the Settings screen: general, price display, private store, registration and login, texts, emails and the active add-ons. Each setting has a key, a label, a type, its allowed values and whether it can be changed here. Call this before changing settings to find the right keys. Every section together is long, so pass a section or a search word when you know what you need.', 'wholesalex' ),
				'operation'    => Access::READ,
				'input_schema' => Schema::object(
					array(
						'section' => Schema::string( __( 'Only this section ID, for example "general" or "email_templates". Leave out for every section.', 'wholesalex' ), 64 ),
						'search'  => Schema::string( __( 'Only settings whose label or key contains this text.', 'wholesalex' ), 100 ),
					)
				),
				'callback'     => array( __CLASS__, 'get' ),
			),
			'wholesalex/update-settings' => array(
				'label'        => __( 'Update WholesaleX settings', 'wholesalex' ),
				'description'  => __( 'Changes one or more WholesaleX settings by key, as listed by wholesalex-get-settings. Every setting on the WholesaleX Settings screen can be changed, including the store mode, private store, registration approval and login options. Toggles take "yes" or "no", choices take one of the allowed values, an order takes every allowed value once, comma-separated, and addresses must be on this site. Only settings marked read-only, such as email recipients, cannot be changed here. Every value is checked before anything is saved. Changes apply to the live store.', 'wholesalex' ),
				'operation'    => Access::WRITE,
				'idempotent'   => true,
				'input_schema' => Schema::object(
					array(
						'changes' => Schema::object_list(
							__( 'Settings to change.', 'wholesalex' ),
							Schema::object(
								array(
									'key'   => Schema::string( __( 'Setting key from wholesalex-get-settings.', 'wholesalex' ), 200, 1 ),
									'value' => Schema::string( __( 'New value, as text.', 'wholesalex' ), 5000 ),
								),
								array( 'key', 'value' )
							),
							self::MAX_CHANGES
						),
					),
					array( 'changes' )
				),
				'callback'     => array( __CLASS__, 'update' ),
			),
		);
	}

	/**
	 * List settings.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function get( array $input ) {
		$sections = Settings_Catalog::sections();
		$only     = (string) ( $input['section'] ?? '' );
		if ( '' !== $only && ! isset( $sections[ $only ] ) ) {
			/* translators: %s: section IDs */
			return new \WP_Error( 'wholesalex_mcp_setting_section', sprintf( __( 'Unknown section. Use one of: %s.', 'wholesalex' ), implode( ', ', array_keys( $sections ) ) ) );
		}
		$search = strtolower( (string) ( $input['search'] ?? '' ) );

		$result = array();
		foreach ( $sections as $id => $section ) {
			if ( '' !== $only && $only !== $id ) {
				continue;
			}
			$values = call_user_func( $section['read'] );
			$values = is_array( $values ) ? $values : array();
			$fields = array();
			foreach ( $section['fields'] as $key => $field ) {
				if ( '' !== $search && false === strpos( strtolower( $key . ' ' . $field['label'] ), $search ) ) {
					continue;
				}
				$fields[] = self::describe( $key, $field, $values );
			}
			if ( ! empty( $fields ) ) {
				$result[] = array(
					'id'       => $id,
					'label'    => $section['label'],
					'settings' => $fields,
				);
			}
		}
		return array( 'sections' => $result );
	}

	/**
	 * Describe one setting for the assistant.
	 *
	 * @param string $key    Setting key.
	 * @param array  $field  Field definition.
	 * @param array  $values Current values of its section.
	 * @return array
	 */
	private static function describe( string $key, array $field, array $values ): array {
		$value = array_key_exists( $key, $values ) ? $values[ $key ] : ( $field['default'] ?? '' );
		if ( 'order' === $field['type'] ) {
			$value = implode( ', ', array_map( 'strval', (array) $value ) );
		} elseif ( 'toggle' === $field['type'] ) {
			$value = in_array( $value, array( 'yes', true, 1, '1' ), true ) ? 'yes' : 'no';
		} else {
			$value = is_scalar( $value ) ? (string) $value : '';
		}
		$setting = array(
			'key'   => $key,
			'label' => $field['label'],
			'type'  => $field['type'],
			'value' => $value,
		);
		if ( ! empty( $field['options'] ) ) {
			$setting['options'] = $field['options'];
		}
		if ( ! empty( $field['help'] ) ) {
			$setting['help'] = $field['help'];
		}
		if ( ! empty( $field['read_only'] ) ) {
			$setting['read_only'] = $field['read_only'];
		}
		return $setting;
	}

	/**
	 * Change settings.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function update( array $input ) {
		$sections = Settings_Catalog::sections();
		$owner    = array();
		foreach ( $sections as $id => $section ) {
			foreach ( array_keys( $section['fields'] ) as $key ) {
				$owner[ $key ] = $id;
			}
		}

		// Check every change before saving anything.
		$by_section = array();
		$errors     = array();
		$protected  = false;
		foreach ( (array) ( $input['changes'] ?? array() ) as $change ) {
			$key = (string) ( $change['key'] ?? '' );
			if ( ! isset( $owner[ $key ] ) ) {
				/* translators: %s: setting key */
				$errors[] = sprintf( __( '%s: unknown setting. Find keys with wholesalex-get-settings.', 'wholesalex' ), $key );
				continue;
			}
			$field = $sections[ $owner[ $key ] ]['fields'][ $key ];
			if ( ! empty( $field['read_only'] ) ) {
				/* translators: 1: setting label, 2: reason */
				$errors[]  = sprintf( __( '%1$s cannot be changed by AI assistants. %2$s', 'wholesalex' ), $field['label'], $field['read_only'] );
				$protected = true;
				continue;
			}
			$value = Settings_Catalog::normalize( $key, $field, (string) ( $change['value'] ?? '' ) );
			if ( is_wp_error( $value ) ) {
				$errors[] = $value->get_error_message();
				continue;
			}
			$by_section[ $owner[ $key ] ][ $key ] = $value;
		}
		if ( ! empty( $errors ) ) {
			// A separate code lets the activity log show attempts on protected settings.
			return new \WP_Error( $protected ? 'wholesalex_mcp_setting_read_only' : 'wholesalex_mcp_setting_invalid', __( 'Nothing was saved.', 'wholesalex' ) . ' ' . implode( ' ', $errors ) );
		}
		if ( empty( $by_section ) ) {
			return new \WP_Error( 'wholesalex_mcp_setting_invalid', __( 'Send at least one setting to change.', 'wholesalex' ) );
		}

		$valid = Settings_Catalog::validate_batch( $sections, $by_section );
		if ( is_wp_error( $valid ) ) {
			return new \WP_Error( $valid->get_error_code(), __( 'Nothing was saved.', 'wholesalex' ) . ' ' . $valid->get_error_message() );
		}

		$saved = array();
		foreach ( $by_section as $id => $changes ) {
			$result = call_user_func( $sections[ $id ]['save'], $changes );
			if ( true !== $result ) {
				$message = is_wp_error( $result ) ? $result->get_error_message() : __( 'The settings section could not be saved.', 'wholesalex' );
				if ( ! empty( $saved ) ) {
					/* translators: 1: error message, 2: setting keys */
					$message = sprintf( __( '%1$s These settings were already saved: %2$s.', 'wholesalex' ), $message, implode( ', ', $saved ) );
				}
				return new \WP_Error(
					is_wp_error( $result ) ? $result->get_error_code() : 'wholesalex_mcp_setting_failed',
					$message,
					array(
						'saved_keys'     => $saved,
						'failed_section' => $id,
					)
				);
			}
			$saved = array_merge( $saved, array_keys( $changes ) );
		}

		// Report the stored values, as the screens will show them.
		$sections = Settings_Catalog::sections();
		$updated  = array();
		foreach ( $by_section as $id => $changes ) {
			$values = call_user_func( $sections[ $id ]['read'] );
			foreach ( array_keys( $changes ) as $key ) {
				$updated[] = self::describe( $key, $sections[ $id ]['fields'][ $key ], is_array( $values ) ? $values : array() );
			}
		}
		return array(
			'id'      => substr( implode( ',', $saved ), 0, 100 ),
			'updated' => $updated,
		);
	}
}
