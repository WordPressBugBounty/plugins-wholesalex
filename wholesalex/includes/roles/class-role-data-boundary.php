<?php
/** Authoritative ownership boundary for role writes.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Preserve provider data while applying the fields implemented by Free. */
final class Role_Data_Boundary {
	/** Free role record fields, independent of provider presentation schemas. */
	private const ROLE_FIELDS = array( 'id', 'role_id', 'label', 'removeable', '_role_status', '_role_title', 'user_status', '_display_price', '_payment_methods', '_payment_method_rules', '_shipping_method_rules', '_shipping_method_promo', '_shipping_methods', '_disable_coupon', '_exclude_users', '_auto_role_migration', '_role_migration_threshold_value' );
	/** Free restriction domain fields, including their legacy mirrors. */
	private const RESTRICTION_FIELDS = array( '_disable_coupon', '_exclude_users', '_tax_rule', '_tax_enabled', '_tax_exempted', '_tax_class', '_tax_exempted_country', '_exempted_country', '_tax_filter', '_tax_products', '_tax_exclude_products', '_tax_exclude_users', '_tax_exclude_users_list' );

	/**
	 * Prepare a complete canonical record before any storage mutation.
	 *
	 * Providers return a complete validated record or WP_Error, never a flag.
	 * Omissions preserve saved values. A delete removes the whole record, so it
	 * does not need a provider; a provider can still block it with WP_Error.
	 *
	 * @param array  $incoming Submitted record.
	 * @param array  $stored   Authoritative option record.
	 * @param string $operation Save or delete.
	 * @return array|\WP_Error
	 */
	public static function prepare( array $incoming, array $stored, string $operation = 'save' ) {
		$next    = $stored;
		$unknown = false;
		$changes = array(
			'role'         => array(),
			'restrictions' => array(),
		);
		foreach ( $incoming as $key => $value ) {
			if ( '_restrictions' === $key ) {
				if ( ! is_array( $value ) ) {
					return new \WP_Error( 'wholesalex_invalid_role', __( 'Role restrictions must be an object.', 'wholesalex' ), array( 'status' => 400 ) );
				}
				$next[ $key ] = isset( $stored[ $key ] ) && is_array( $stored[ $key ] ) ? $stored[ $key ] : array();
				foreach ( $value as $field => $setting ) {
					if ( '_tax_rule' === $field && is_array( $setting ) ) {
						$next[ $key ][ $field ] = self::merge_tax_patch( $setting, is_array( $next[ $key ][ $field ] ?? null ) ? $next[ $key ][ $field ] : array() );
					} elseif ( in_array( $field, self::RESTRICTION_FIELDS, true ) ) {
						$next[ $key ][ $field ] = wholesalex()->sanitize( $setting );
					} elseif ( ! array_key_exists( $field, $next[ $key ] ) || $next[ $key ][ $field ] !== $setting ) {
						$unknown                           = true;
						$changes['restrictions'][ $field ] = $setting;
					}
				}
			} elseif ( in_array( $key, self::ROLE_FIELDS, true ) || is_numeric( $key ) ) {
				$next[ $key ] = wholesalex()->sanitize( $value );
			} elseif ( ! array_key_exists( $key, $stored ) || $stored[ $key ] !== $value ) {
				$unknown                 = true;
				$changes['role'][ $key ] = $value;
			}
		}
		/**
		 * Supply an actual provider-owned validator/result for role operations.
		 *
		 * @param null|array|\WP_Error $result    Canonical validated record.
		 * @param array               $incoming  Submitted role.
		 * @param array               $stored    Authoritative stored role.
		 * @param string              $operation Save or delete.
		 * @param array               $base      Free-owned merged record.
		 * @param array               $changes   Unowned role/restrictions changes.
		 */
		$result = apply_filters( 'wholesalex_role_provider_result', null, $incoming, $stored, $operation, $next, $changes );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( is_array( $result ) ) {
			return $result;
		}
		if ( $unknown ) {
			return new \WP_Error( 'wholesalex_provider_unavailable', __( 'This role operation requires its settings provider.', 'wholesalex' ), array( 'status' => 409 ) );
		}
		return $next;
	}

	/**
	 * Preserve omitted tax children; selector lists replace their saved selection.
	 *
	 * @param array $patch  Submitted tax fields.
	 * @param array $stored Authoritative saved tax object.
	 * @return array
	 */
	private static function merge_tax_patch( array $patch, array $stored ): array {
		$next = $stored;
		foreach ( $patch as $key => $value ) {
			if ( array_key_exists( $key, $stored ) && $value === $stored[ $key ] ) {
				continue;
			}
			if ( 'exclude_users' === $key && is_array( $value ) ) {
				$next[ $key ] = is_array( $stored[ $key ] ?? null ) ? $stored[ $key ] : array();
				foreach ( $value as $child => $setting ) {
					if ( ! array_key_exists( $child, $next[ $key ] ) || $setting !== $next[ $key ][ $child ] ) {
						$next[ $key ][ $child ] = wholesalex()->sanitize( $setting );
					}
				}
			} else {
				$next[ $key ] = wholesalex()->sanitize( $value );
			}
		}
		return $next;
	}
}
