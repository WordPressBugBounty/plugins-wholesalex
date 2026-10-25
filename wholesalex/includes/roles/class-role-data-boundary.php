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
	 * Omissions preserve saved values; deletion is an explicit operation.
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
					if ( in_array( $field, self::RESTRICTION_FIELDS, true ) ) {
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
		if ( 'delete' === $operation ) {
			foreach ( $stored as $key => $value ) {
				if ( '_restrictions' === $key && is_array( $value ) ) {
					foreach ( array_diff( array_keys( $value ), self::RESTRICTION_FIELDS ) as $field ) {
						$changes['restrictions'][ $field ] = $value[ $field ];
						$unknown                           = true;
					}
				} elseif ( ! in_array( $key, self::ROLE_FIELDS, true ) && ! is_numeric( $key ) ) {
					$unknown                 = true;
					$changes['role'][ $key ] = $value;
				}
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
}
