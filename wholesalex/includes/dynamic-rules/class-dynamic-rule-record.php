<?php
/**
 * Generic legacy rule domain ownership and authoritative merges.
 *
 * @package WHOLESALEX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Preserve provider data while applying only Free-owned rule edits. */
final class Dynamic_Rule_Record {
	/** Free processor payload fields, without presentation metadata. */
	const PAYLOADS = array(
		'product_discount'  => array( '_discount_type', '_discount_amount', '_discount_name' ),
		'cart_discount'     => array( '_discount_type', '_discount_amount', '_discount_name' ),
		'payment_discount'  => array( '_discount_type', '_discount_amount', '_discount_name', '_payment_gateways' ),
		'payment_order_qty' => array( '_payment_gateways', '_order_quantity' ),
		'tax_rule'          => array( '_tax_exempted', '_tax_class', '_exempted_country' ),
		'shipping_rule'     => array( '__shipping_zone', '_shipping_zone_methods' ),
		'min_order_qty'     => array( '_min_order_qty', '_min_order_qty_disable', '_min_order_qty_step' ),
		'buy_x_get_one'     => array( '_minimum_purchase_count', '_per_cart_once', '_buy_x_get_product_badge_enable', '_product_badge_label', '_product_badge_bg_color', '_product_badge_text_color', '_product_badge_position', '_product_badge_styles' ),
	);
	/** Selector values consumed by generic Free target matching. */
	const SELECTORS = array( 'specific_users', 'specific_roles', 'products_in_list', 'products_not_in_list', 'cat_in_list', 'cat_not_in_list', 'attribute_in_list', 'attribute_not_in_list' );
	/** Nested selector payload fields consumed by Free processors. */
	const LIST_FIELDS = array( '_payment_gateways', '_exempted_country', '_shipping_zone_methods' );

	/**
	 * Merge an incoming patch over authoritative storage, sanitizing owned values only.
	 *
	 * @param array  $stored Authoritative record.
	 * @param array  $patch Incoming owned changes.
	 * @param string $id Stable repository identifier.
	 * @return array|\WP_Error
	 */
	public static function merge( array $stored, array $patch, $id ) {
		$result = $stored;
		foreach ( array( '_rule_status', '_rule_title', '_rule_type', '_rule_for', '_product_filter' ) as $key ) {
			if ( array_key_exists( $key, $patch ) ) {
				if ( ! is_scalar( $patch[ $key ] ) && null !== $patch[ $key ] ) {
					return self::invalid();
				}
				$result[ $key ] = '_rule_status' === $key && is_bool( $patch[ $key ] ) ? ( $patch[ $key ] ? 'yes' : 'no' ) : sanitize_text_field( $patch[ $key ] ?? '' );
			} elseif ( ! array_key_exists( $key, $result ) ) {
				$result[ $key ] = '';
			}
		}
		$result['id']         = $id;
		$result['created_by'] = $stored['created_by'] ?? get_current_user_id();
		foreach ( self::PAYLOADS as $key => $fields ) {
			// Inactive payloads are authoritative and are not round-tripped by a different editor.
			if ( $key !== $result['_rule_type'] || ! array_key_exists( $key, $patch ) ) {
				continue;
			}
			if ( ! is_array( $patch[ $key ] ) ) {
				return self::invalid();
			}
			$result[ $key ] = self::fields( is_array( $stored[ $key ] ?? null ) ? $stored[ $key ] : array(), $patch[ $key ], $fields );
			if ( is_wp_error( $result[ $key ] ) ) {
				return $result[ $key ];
			}
		}
		if ( array_key_exists( 'limit', $patch ) ) {
			if ( ! is_array( $patch['limit'] ) ) {
				return self::invalid();
			}
			$result['limit'] = self::fields( is_array( $stored['limit'] ?? null ) ? $stored['limit'] : array(), $patch['limit'], array( '_start_date', '_end_date', '_usage_limit', 'usages_count' ) );
			if ( is_wp_error( $result['limit'] ) ) {
				return $result['limit'];
			}
		}
		foreach ( self::SELECTORS as $key ) {
			if ( ! in_array( $key, array( $result['_rule_for'], $result['_product_filter'] ), true ) || ! array_key_exists( $key, $patch ) ) {
				continue;
			}
			$result[ $key ] = self::rows( $stored[ $key ] ?? array(), $patch[ $key ], array( 'value', 'name' ), 'value' );
			if ( is_wp_error( $result[ $key ] ) ) {
				return $result[ $key ];
			}
		}
		if ( array_key_exists( 'conditions', $patch ) ) {
			if ( ! is_array( $patch['conditions'] ) ) {
				return self::invalid(); }
			if ( array_key_exists( 'tiers', $patch['conditions'] ) && ! is_array( $patch['conditions']['tiers'] ) ) {
				return self::invalid(); }
			$conditions = is_array( $stored['conditions'] ?? null ) ? $stored['conditions'] : array();
			$rows       = is_array( $patch['conditions'] ) ? ( $patch['conditions']['tiers'] ?? null ) : array();
			if ( null !== $rows ) {
				$conditions['tiers'] = self::rows( $conditions['tiers'] ?? array(), $rows, array( '_id', 'id', '_conditions_for', '_conditions_operator', '_conditions_value' ), '_id' );
				if ( is_wp_error( $conditions['tiers'] ) ) {
					return $conditions['tiers'];
				}
			}
			$result['conditions'] = $conditions;
		}
		$validation = self::validate( $result );
		return is_wp_error( $validation ) ? $validation : $result;
	}

	/** Validate domain values without broadening malformed restrictions.
	 *
	 * @param array $rule Candidate record.
	 * @return true|\WP_Error
	 */
	public static function validate( array $rule ) {
		if ( empty( $rule['_rule_type'] ) || empty( $rule['_rule_for'] ) || empty( $rule['_product_filter'] ) ) {
			return self::invalid();
		}

		foreach ( $rule['conditions']['tiers'] ?? array() as $tier ) {
			if ( is_array( $tier ) && '' === ( $tier['_conditions_for'] ?? '' ) && '' === ( $tier['_conditions_operator'] ?? '' ) && '' === ( $tier['_conditions_value'] ?? '' ) ) {
				continue; }
			if ( ! is_array( $tier ) || ! in_array( $tier['_conditions_operator'] ?? '', array( 'greater', 'greater_equal', 'less', 'less_equal', 'equal', 'not_equal' ), true ) || empty( $tier['_conditions_for'] ) || ! is_numeric( $tier['_conditions_value'] ?? null ) || ! is_finite( (float) $tier['_conditions_value'] ) || (float) $tier['_conditions_value'] < 0 ) {
				return self::invalid();
			}
		}
		$type = $rule['_rule_type'] ?? '';
		foreach ( array( '_discount_amount', '_order_quantity', '_min_order_qty', '_min_order_qty_step', '_minimum_purchase_count' ) as $key ) {
			$value = $rule[ $type ][ $key ] ?? '';
			if ( '' !== $value && ( ! is_numeric( $value ) || ! is_finite( (float) $value ) || (float) $value < 0 ) ) {
				return self::invalid(); }
		}
		$limit = $rule['limit'] ?? array();
		foreach ( array( '_usage_limit', 'usages_count' ) as $key ) {
			$value = $limit[ $key ] ?? '';
			if ( '' !== $value && ( ! is_numeric( $value ) || (float) $value < 0 || floor( (float) $value ) !== (float) $value ) ) {
				return self::invalid(); }
		}
		$dates = array();
		foreach ( array( '_start_date', '_end_date' ) as $key ) {
			$value = $limit[ $key ] ?? '';
			if ( '' !== $value ) {
				$dates[ $key ] = self::parse_schedule_date( $value );
				if ( ! $dates[ $key ] ) {
					return self::invalid(); }
			}
		}
		if ( isset( $dates['_start_date'], $dates['_end_date'] ) && $dates['_start_date'] > $dates['_end_date'] ) {
			return self::invalid(); }

		return true;
	}

	/** Parse the date-only and ISO formats emitted by supported editors.
	 *
	 * @param mixed $value Stored calendar boundary.
	 * @return \DateTimeImmutable|false
	 */
	public static function parse_schedule_date( $value ) {
		if ( ! is_string( $value ) ) {
			return false; }
		$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value, $timezone );
			return $date && $date->format( 'Y-m-d' ) === $value ? $date : false;
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-](?:0\d|1[0-4]):[0-5]\d)$/', $value ) ) {
			return false; }
		try {
			$date = new \DateTimeImmutable( $value, $timezone );
			return $date->format( 'Y-m-d\TH:i:s' ) === substr( $value, 0, 19 ) ? $date : false;
		} catch ( \Exception $error ) {
			return false; }
	}

	/**
	 * Merge scalar fields and known selectors without sanitizing saved unknown values.
	 *
	 * @param array $stored Stored field values.
	 * @param array $patch Changed values.
	 * @param array $keys Owned field names.
	 * @return array|\WP_Error
	 */
	private static function fields( array $stored, array $patch, array $keys ) {
		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $patch ) ) {
				continue;
			}
			if ( in_array( $key, self::LIST_FIELDS, true ) ) {
				$value = self::rows( $stored[ $key ] ?? array(), $patch[ $key ], array( 'value', 'name' ), 'value' );
			} elseif ( is_scalar( $patch[ $key ] ) || null === $patch[ $key ] ) {
				$value = sanitize_text_field( $patch[ $key ] ?? '' );
			} else {
				return self::invalid();
			}
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			$stored[ $key ] = $value;
		}
		return $stored;
	}

	/**
	 * Merge selected rows by stable identity; only idless historical rows use position.
	 *
	 * @param mixed  $stored Stored rows.
	 * @param mixed  $patch Changed rows, or the legacy empty string.
	 * @param array  $keys Owned row fields.
	 * @param string $identity Stable identity field.
	 * @return array|string|\WP_Error
	 */
	private static function rows( $stored, $patch, array $keys, $identity ) {
		if ( '' === $patch ) {
			return '';
		}
		if ( ! is_array( $patch ) ) {
			return self::invalid();
		}
		$stored = is_array( $stored ) ? $stored : array();
		$result = array();
		foreach ( $patch as $index => $row ) {
			if ( ! is_array( $row ) ) {
				return self::invalid();
			}
			$base = array();
			$id   = $row[ $identity ] ?? ( '_id' === $identity ? ( $row['id'] ?? null ) : null );
			if ( null !== $id && ! is_scalar( $id ) ) {
				return self::invalid();
			}
			foreach ( $stored as $saved_index => $saved ) {
				$saved_id = is_array( $saved ) ? ( $saved[ $identity ] ?? ( '_id' === $identity ? ( $saved['id'] ?? null ) : null ) ) : null;
				if ( ( null !== $id && null !== $saved_id && (string) $id === (string) $saved_id ) || ( null === $id && null === $saved_id && $index === $saved_index ) ) {
					$base = is_array( $saved ) ? $saved : array();
					break;
				}
			}
			$merged = self::fields( $base, $row, $keys );
			if ( is_wp_error( $merged ) ) {
				return $merged;
			}
			$result[] = $merged;
		}
		return $result;
	}

	/**
	 * Reject invalid owned domain input.
	 *
	 * @return \WP_Error
	 */
	private static function invalid() {
		return new \WP_Error( 'wholesalex_invalid_rule_data', __( 'Invalid rule field data.', 'wholesalex' ), array( 'status' => 400 ) );
	}
}
