<?php
/**
 * Settings the WholesaleX AI tools can read and change.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

use WHOLESALEX\Settings;
use WHOLESALEX\Settings_Service;
use WHOLESALEX\WHOLESALEX_Email;

defined( 'ABSPATH' ) || exit;

/**
 * Describes the Settings screen to the AI tools and saves through the same
 * code paths the screen uses.
 *
 * A section has an `id`, a `label`, `fields` keyed by setting key, a `read`
 * callback returning the current values and a `save` callback taking the
 * changed values. Field types are `toggle`, `choice`, `order`, `text`,
 * `textarea`, `url`, `url_list`, `email_list`, `color` and `number`.
 *
 * Safeguards:
 * - Secrets, such as the reCAPTCHA secret key, are never listed.
 * - Every setting on the WholesaleX Settings screen can be changed. Email
 *   recipients, and settings extensions or the
 *   `wholesalex_mcp_read_only_settings` filter mark, are read-only here.
 * - Every value is checked against its field before anything is saved, and
 *   redirect addresses must stay on this site.
 */
final class Settings_Catalog {

	/**
	 * Field types the AI tools understand.
	 *
	 * @var string[]
	 */
	const TYPES = array( 'toggle', 'choice', 'order', 'text', 'textarea', 'url', 'url_list', 'email_list', 'color', 'number' );

	/**
	 * Settings screen text fields holding comma-separated addresses.
	 *
	 * @var string[]
	 */
	const URL_LISTS = array( '_settings_private_store_whitelist_url' );

	/**
	 * Return every section, including those added by extensions.
	 *
	 * @return array<string, array>
	 */
	public static function sections(): array {
		$sections = self::core_sections();
		$email    = self::email_section();
		if ( null !== $email ) {
			$sections[ $email['id'] ] = $email;
		}

		/**
		 * Filters the settings sections offered by the WholesaleX AI tools.
		 *
		 * Add sections for settings your code owns. Each section needs `id`,
		 * `label`, `fields` (keyed by setting key, each with `label`, `type`
		 * and optionally `options`, `help`, `read_only`, `minimum`, `maximum`),
		 * a `read` callback returning current values and a `save` callback that
		 * takes the changed values and returns true or a WP_Error. Save through
		 * the same code your settings screen uses. Add a side-effect-free
		 * `validate` callback (section changes, all section changes) to preflight
		 * service dependencies before any section writes.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, array> $sections Sections keyed by ID.
		 */
		$sections = apply_filters( 'wholesalex_mcp_settings_sections', $sections );

		$valid = array();
		foreach ( is_array( $sections ) ? $sections : array() as $id => $section ) {
			if ( is_string( $id ) && is_array( $section ) && isset( $section['label'], $section['fields'], $section['read'], $section['save'] ) && is_array( $section['fields'] ) && is_callable( $section['read'] ) && is_callable( $section['save'] ) && ( ! isset( $section['validate'] ) || is_callable( $section['validate'] ) ) ) {
				$section['id']     = $id;
				$section['fields'] = self::clean_fields( $section['fields'] );
				$valid[ $id ]      = $section;
			}
		}
		return $valid;
	}

	/**
	 * Check shared service rules and extension preflights before any batch write.
	 * Core sections share one option, so validate their combined proposed changes.
	 *
	 * @param array $sections   Validated catalog.
	 * @param array $by_section Normalized changes keyed by section ID.
	 * @return true|\WP_Error
	 */
	public static function validate_batch( array $sections, array $by_section ) {
		$core = array();
		foreach ( $by_section as $id => $changes ) {
			if ( array( Settings_Service::class, 'save' ) === $sections[ $id ]['save'] ) {
				$core = array_replace( $core, $changes );
			}
		}
		if ( ! empty( $core ) ) {
			$valid = Settings_Service::validate( $core );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		}
		foreach ( $by_section as $id => $changes ) {
			if ( isset( $sections[ $id ]['validate'] ) ) {
				$valid = call_user_func( $sections[ $id ]['validate'], $changes, $by_section );
				if ( true !== $valid ) {
					return is_wp_error( $valid ) ? $valid : new \WP_Error( 'wholesalex_mcp_setting_invalid', __( 'The settings section could not be validated.', 'wholesalex' ) );
				}
			}
		}
		return true;
	}

	/**
	 * Drop secret and malformed fields, and apply the read-only policy.
	 *
	 * @param array $fields Fields keyed by setting key.
	 * @return array
	 */
	private static function clean_fields( array $fields ): array {
		$read_only = self::read_only_settings();
		$clean     = array();
		foreach ( $fields as $key => $field ) {
			if ( ! is_string( $key ) || self::is_secret( $key ) || ! is_array( $field ) || ! in_array( $field['type'] ?? '', self::TYPES, true ) ) {
				continue;
			}
			$field['label'] = wp_strip_all_tags( (string) ( $field['label'] ?? $key ) );
			if ( isset( $read_only[ $key ] ) ) {
				$field['read_only'] = $read_only[ $key ];
			}
			$clean[ $key ] = $field;
		}
		return $clean;
	}

	/**
	 * Whether a setting key holds a secret that must never leave the site.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function is_secret( string $key ): bool {
		return 1 === preg_match( '/secret|password|passwd|token|license|api_key|private_key/i', $key );
	}

	/**
	 * Settings the AI tools may read but not change, with the reason.
	 *
	 * @return array<string, string>
	 */
	public static function read_only_settings(): array {
		/**
		 * Filters the settings the WholesaleX AI tools may read but not change.
		 *
		 * Every Settings screen setting can be changed by default. Add a key
		 * with the reason the assistant should give, for example to keep the
		 * store mode under manual control.
		 *
		 * @since 3.2.0
		 *
		 * @param array<string, string> $settings Reasons keyed by setting key.
		 */
		$settings = apply_filters( 'wholesalex_mcp_read_only_settings', array() );
		return is_array( $settings ) ? array_filter( $settings, 'is_string' ) : array();
	}

	/**
	 * Build the sections of the WholesaleX Settings screen.
	 *
	 * @return array<string, array>
	 */
	private static function core_sections(): array {
		if ( ! class_exists( Settings::class ) || ! method_exists( Settings::class, 'get_option_settings' ) ) {
			return array();
		}
		$sections = array();
		foreach ( (array) Settings::get_option_settings() as $id => $definition ) {
			if ( ! is_string( $id ) || '_save' === $id || ! is_array( $definition ) ) {
				continue;
			}
			$fields = array();
			foreach ( $definition as $group_key => $group ) {
				if ( 0 === strpos( (string) $group_key, 'attr' ) && is_array( $group ) ) {
					self::collect_core_fields( $group, '', $fields );
				}
			}
			if ( empty( $fields ) ) {
				continue;
			}
			$sections[ $id ] = array(
				'id'     => $id,
				'label'  => wp_strip_all_tags( (string) ( $definition['label'] ?? $id ) ),
				'fields' => $fields,
				'read'   => array( __CLASS__, 'read_core' ),
				'save'   => array( Settings_Service::class, 'save' ),
			);
		}
		return $sections;
	}

	/**
	 * Turn screen field definitions into catalog fields. Container fields,
	 * such as the promotion and text groups, are flattened.
	 *
	 * @param array  $group  Field definitions keyed by setting key.
	 * @param string $prefix Label of the containing group.
	 * @param array  $fields Collected fields, by reference.
	 * @return void
	 */
	private static function collect_core_fields( array $group, string $prefix, array &$fields ): void {
		foreach ( $group as $key => $definition ) {
			if ( ! is_string( $key ) || ! is_array( $definition ) || ! isset( $definition['type'] ) || ! is_string( $definition['type'] ) ) {
				continue;
			}
			$label = wp_strip_all_tags( (string) ( $definition['label'] ?? $key ) );
			if ( isset( $definition['attr'] ) && is_array( $definition['attr'] ) ) {
				self::collect_core_fields( $definition['attr'], $label, $fields );
				continue;
			}
			$field = array(
				'label' => '' === $prefix ? $label : $prefix . ': ' . $label,
				'help'  => wp_strip_all_tags( (string) ( $definition['help'] ?? $definition['desc'] ?? '' ) ),
			);
			switch ( $definition['type'] ) {
				case 'slider':
					$field['type'] = 'toggle';
					break;
				case 'radio':
				case 'select':
					$field['type']    = 'choice';
					$field['options'] = self::options( $definition['options'] ?? array() );
					break;
				case 'priorityDragList':
					$field['type']    = 'order';
					$field['options'] = array_combine( array_map( 'strval', (array) ( $definition['options'] ?? array() ) ), array_map( 'strval', (array) ( $definition['options'] ?? array() ) ) );
					break;
				case 'textarea':
					$field['type'] = 'textarea';
					break;
				case 'text':
					if ( in_array( $key, self::URL_LISTS, true ) ) {
						$field['type'] = 'url_list';
					} else {
						$field['type'] = false !== strpos( $key, '_url' ) ? 'url' : 'text';
					}
					break;
				default:
					continue 2;
			}
			if ( isset( $definition['default'] ) && is_scalar( $definition['default'] ) ) {
				$field['default'] = (string) $definition['default'];
			}
			$fields[ $key ] = $field;
		}
	}

	/**
	 * Normalize choice options to value => label strings.
	 *
	 * @param mixed $options Screen options.
	 * @return array<string, string>
	 */
	private static function options( $options ): array {
		$clean = array();
		foreach ( (array) $options as $value => $label ) {
			$clean[ (string) $value ] = wp_strip_all_tags( is_scalar( $label ) ? (string) $label : (string) $value );
		}
		return $clean;
	}

	/**
	 * Current values of the WholesaleX Settings screen.
	 *
	 * @return array
	 */
	public static function read_core(): array {
		$stored = get_option( 'wholesalex_settings', array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Build the Email Templates section.
	 *
	 * @return array|null
	 */
	private static function email_section(): ?array {
		if ( ! class_exists( WHOLESALEX_Email::class ) || ! method_exists( WHOLESALEX_Email::class, 'update_template' ) || ! function_exists( 'WC' ) ) {
			return null;
		}
		$fields = array();
		foreach ( WHOLESALEX_Email::get_email_templates() as $id => $template ) {
			$title                                 = wp_strip_all_tags( (string) ( $template['title'] ?? $id ) );
			$fields[ $id . '.enabled' ]            = array(
				'type'  => 'toggle',
				'label' => $title . ': ' . __( 'Enabled', 'wholesalex' ),
			);
			$fields[ $id . '.subject' ]            = array(
				'type'  => 'text',
				'label' => $title . ': ' . __( 'Subject', 'wholesalex' ),
				'help'  => __( 'Smart tags such as {site_title} work here.', 'wholesalex' ),
			);
			$fields[ $id . '.heading' ]            = array(
				'type'  => 'text',
				'label' => $title . ': ' . __( 'Heading', 'wholesalex' ),
			);
			$fields[ $id . '.additional_content' ] = array(
				'type'  => 'textarea',
				'label' => $title . ': ' . __( 'Additional content', 'wholesalex' ),
			);
			$fields[ $id . '.recipient' ]          = array(
				'type'      => 'email_list',
				'label'     => $title . ': ' . __( 'Recipients', 'wholesalex' ),
				'read_only' => __( 'Changing who receives store emails could send customer data elsewhere. Change it on the Email Templates screen.', 'wholesalex' ),
			);
		}
		if ( empty( $fields ) ) {
			return null;
		}
		return array(
			'id'       => 'email_templates',
			'label'    => __( 'Email Templates', 'wholesalex' ),
			'fields'   => $fields,
			'read'     => array( __CLASS__, 'read_emails' ),
			'save'     => array( __CLASS__, 'save_emails' ),
			'validate' => array( __CLASS__, 'validate_emails' ),
		);
	}

	/**
	 * Current values of the WholesaleX emails.
	 *
	 * @return array
	 */
	public static function read_emails(): array {
		$values = array();
		foreach ( WHOLESALEX_Email::get_email_templates() as $id => $template ) {
			foreach ( array( 'enabled', 'subject', 'heading', 'additional_content', 'recipient' ) as $part ) {
				$values[ $id . '.' . $part ] = isset( $template[ $part ] ) && is_scalar( $template[ $part ] ) ? (string) $template[ $part ] : '';
			}
		}
		return $values;
	}

	/**
	 * Validate every email change before any template is written.
	 *
	 * @param array $changes Values keyed by "template.part".
	 * @return true|\WP_Error
	 */
	public static function validate_emails( array $changes ) {
		foreach ( $changes as $key => $value ) {
			list( $id, $part ) = array_pad( explode( '.', (string) $key, 2 ), 2, '' );
			$valid             = WHOLESALEX_Email::validate_template_change( $id, array( $part => $value ) );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		}
		return true;
	}

	/**
	 * Save preflighted email changes, retaining runtime validation.
	 *
	 * @param array $changes Values keyed by "template.part".
	 * @return true|\WP_Error
	 */
	public static function save_emails( array $changes ) {
		$valid = self::validate_emails( $changes );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		$by_template = array();
		foreach ( $changes as $key => $value ) {
			list( $id, $part )           = array_pad( explode( '.', (string) $key, 2 ), 2, '' );
			$by_template[ $id ][ $part ] = $value;
		}
		foreach ( $by_template as $id => $parts ) {
			if ( isset( $parts['enabled'] ) ) {
				$result = WHOLESALEX_Email::set_template_status( $id, (string) $parts['enabled'] );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				unset( $parts['enabled'] );
			}
			if ( ! empty( $parts ) ) {
				$result = WHOLESALEX_Email::update_template( $id, $parts );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
		}
		return true;
	}

	/**
	 * Check and normalize one value for a field.
	 *
	 * @param string $key   Setting key.
	 * @param array  $field Field definition.
	 * @param string $value Value sent by the assistant.
	 * @return string|string[]|\WP_Error
	 */
	public static function normalize( string $key, array $field, string $value ) {
		$value = trim( $value );
		/* translators: 1: setting label, 2: setting key */
		$prefix = sprintf( __( '%1$s (%2$s):', 'wholesalex' ), $field['label'], $key ) . ' ';
		switch ( $field['type'] ) {
			case 'toggle':
				$map  = array(
					'yes'   => 'yes',
					'true'  => 'yes',
					'on'    => 'yes',
					'1'     => 'yes',
					'no'    => 'no',
					'false' => 'no',
					'off'   => 'no',
					'0'     => 'no',
				);
				$flag = strtolower( $value );
				return isset( $map[ $flag ] ) ? $map[ $flag ] : new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . __( 'use "yes" or "no".', 'wholesalex' ) );
			case 'choice':
				if ( isset( $field['options'][ $value ] ) ) {
					return $value;
				}
				$match = array_search( strtolower( $value ), array_map( 'strtolower', $field['options'] ), true );
				/* translators: %s: allowed values */
				return false !== $match ? (string) $match : new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . sprintf( __( 'use one of: %s.', 'wholesalex' ), implode( ', ', array_keys( $field['options'] ) ) ) );
			case 'order':
				$items   = array_values( array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' ) );
				$allowed = array_keys( $field['options'] );
				$sorted  = $items;
				sort( $sorted );
				sort( $allowed );
				/* translators: %s: allowed values */
				return $sorted === $allowed ? $items : new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . sprintf( __( 'send every one of these values once, comma-separated, in the new order: %s.', 'wholesalex' ), implode( ', ', array_keys( $field['options'] ) ) ) );
			case 'url':
				if ( '' === $value ) {
					return '';
				}
				$url = self::site_url( $value );
				return '' !== $url ? $url : new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . __( 'use an address on this site.', 'wholesalex' ) );
			case 'url_list':
				$urls = array();
				foreach ( array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' ) as $item ) {
					$url = self::site_url( $item );
					if ( '' === $url ) {
						return new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . __( 'use comma-separated full addresses on this site.', 'wholesalex' ) );
					}
					$urls[] = $url;
				}
				return implode( ',', $urls );
			case 'email_list':
				foreach ( array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' ) as $email ) {
					if ( ! is_email( $email ) ) {
						return new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . __( 'use comma-separated email addresses.', 'wholesalex' ) );
					}
				}
				return $value;
			case 'color':
				// Empty means the default; sections that store colors decide how to reset.
				return '' === $value || 1 === preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? $value : new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . __( 'use a color such as #5a40e8, or nothing for the default.', 'wholesalex' ) );
			case 'number':
				$minimum = $field['minimum'] ?? null;
				$maximum = $field['maximum'] ?? null;
				if ( ! is_numeric( $value ) || ( null !== $minimum && (float) $value < $minimum ) || ( null !== $maximum && (float) $value > $maximum ) ) {
					/* translators: 1: minimum, 2: maximum */
					return new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . sprintf( __( 'use a number from %1$s to %2$s.', 'wholesalex' ), null === $minimum ? '-' : $minimum, null === $maximum ? '-' : $maximum ) );
				}
				return $value;
			case 'textarea':
				return strlen( $value ) <= 5000 ? sanitize_textarea_field( $value ) : new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . __( 'use at most 5000 characters.', 'wholesalex' ) );
			default:
				return strlen( $value ) <= 1000 ? sanitize_text_field( $value ) : new \WP_Error( 'wholesalex_mcp_setting_invalid', $prefix . __( 'use at most 1000 characters.', 'wholesalex' ) );
		}
	}

	/**
	 * Clean an address and keep it only when it is on this site.
	 *
	 * @param string $value Address sent by the assistant.
	 * @return string The cleaned address, or an empty string.
	 */
	private static function site_url( string $value ): string {
		$url  = esc_url_raw( $value, array( 'http', 'https' ) );
		$host = wp_parse_url( $url, PHP_URL_HOST );
		return '' !== $url && strtolower( (string) $host ) === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ? $url : '';
	}
}
