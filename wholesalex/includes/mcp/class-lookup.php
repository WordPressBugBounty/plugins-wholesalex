<?php
/**
 * Record lookups for the WholesaleX AI tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

use WHOLESALEX\Dynamic_Rules_Data_Provider;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the IDs an AI assistant sends into the stored selector format.
 *
 * WholesaleX stores product, category, user and role selections as
 * `{value, name}` rows so its admin screens can show labels. The AI tools only
 * accept IDs. This class checks that every ID exists and builds the rows from
 * real store data, so an assistant can never store a label that does not
 * match the record or point a rule at a record that does not exist.
 */
final class Lookup {

	/**
	 * Build selector rows for parent products.
	 *
	 * @param array $ids Product IDs.
	 * @return array|\WP_Error
	 */
	public static function product_rows( array $ids ) {
		return self::post_rows( $ids, 'product', __( 'Products not found', 'wholesalex' ), 'wholesalex-search-products' );
	}

	/**
	 * Build selector rows for product variations.
	 *
	 * @param array $ids Variation IDs.
	 * @return array|\WP_Error
	 */
	public static function variation_rows( array $ids ) {
		return self::post_rows( $ids, 'product_variation', __( 'Product variations not found', 'wholesalex' ), 'wholesalex-search-products' );
	}

	/**
	 * Build selector rows for product categories.
	 *
	 * @param array $ids Category term IDs.
	 * @return array|\WP_Error
	 */
	public static function category_rows( array $ids ) {
		$rows    = array();
		$missing = array();
		foreach ( self::int_ids( $ids ) as $id ) {
			$term = get_term( $id, 'product_cat' );
			if ( ! $term instanceof \WP_Term ) {
				$missing[] = $id;
				continue;
			}
			$rows[] = array(
				'value' => $id,
				'name'  => $term->name,
			);
		}
		return empty( $missing ) ? $rows : self::not_found( __( 'Product categories not found', 'wholesalex' ), $missing, 'wholesalex-search-categories' );
	}

	/**
	 * Build selector rows for customers.
	 *
	 * The stored label matches the one the admin screens create.
	 *
	 * @param array $ids User IDs.
	 * @return array|\WP_Error
	 */
	public static function user_rows( array $ids ) {
		$rows    = array();
		$missing = array();
		foreach ( self::int_ids( $ids ) as $id ) {
			$user = get_userdata( $id );
			if ( ! $user ) {
				$missing[] = $id;
				continue;
			}
			$rows[] = array(
				'value' => $id,
				'name'  => $user->display_name . '(' . $user->user_email . ')',
			);
		}
		return empty( $missing ) ? $rows : self::not_found( __( 'Customers not found', 'wholesalex' ), $missing, 'wholesalex-search-customers' );
	}

	/**
	 * Build selector rows for WholesaleX roles.
	 *
	 * @param array $ids Role IDs.
	 * @return array|\WP_Error
	 */
	public static function role_rows( array $ids ) {
		$rows    = array();
		$missing = array();
		foreach ( self::string_ids( $ids ) as $id ) {
			$role = wholesalex()->get_roles( 'by_id', $id );
			if ( empty( $role ) || ! is_array( $role ) ) {
				$missing[] = $id;
				continue;
			}
			$rows[] = array(
				'value' => $id,
				'name'  => (string) ( $role['_role_title'] ?? $id ),
			);
		}
		return empty( $missing ) ? $rows : self::not_found( __( 'WholesaleX roles not found', 'wholesalex' ), $missing, 'wholesalex-list-roles' );
	}

	/**
	 * Return the enabled WooCommerce payment methods.
	 *
	 * @return array<string, string> Gateway titles keyed by gateway ID.
	 */
	public static function enabled_gateways(): array {
		$gateways = array();
		foreach ( Dynamic_Rules_Data_Provider::get_payment_gateway_options() as $option ) {
			if ( is_array( $option ) && isset( $option['value'] ) ) {
				$gateways[ (string) $option['value'] ] = (string) ( $option['name'] ?? $option['value'] );
			}
		}
		return $gateways;
	}

	/**
	 * Validate payment method IDs.
	 *
	 * @param array $ids Gateway IDs.
	 * @return string[]|\WP_Error
	 */
	public static function gateway_ids( array $ids ) {
		$enabled = self::enabled_gateways();
		$ids     = self::string_ids( $ids );
		$missing = array_values( array_diff( $ids, array_keys( $enabled ) ) );
		return empty( $missing ) ? $ids : self::not_found( __( 'Enabled payment methods not found', 'wholesalex' ), $missing, 'wholesalex-list-checkout-options' );
	}

	/**
	 * Build selector rows for payment methods.
	 *
	 * @param array $ids Gateway IDs.
	 * @return array|\WP_Error
	 */
	public static function gateway_rows( array $ids ) {
		$ids = self::gateway_ids( $ids );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		$enabled = self::enabled_gateways();
		$rows    = array();
		foreach ( $ids as $id ) {
			$rows[] = array(
				'value' => $id,
				'name'  => $enabled[ $id ],
			);
		}
		return $rows;
	}

	/**
	 * Return every shipping zone with its enabled methods.
	 *
	 * The "Rest of the World" zone uses ID 0.
	 *
	 * @return array[] Zones with `id`, `name` and `methods` (instance ID => title).
	 */
	public static function shipping_zones(): array {
		if ( ! class_exists( '\WC_Shipping_Zones' ) ) {
			return array();
		}
		$zones = array();
		$raw   = \WC_Shipping_Zones::get_zones();
		$raw[] = array( 'zone_id' => 0 );
		foreach ( $raw as $item ) {
			$zone    = new \WC_Shipping_Zone( (int) $item['zone_id'] );
			$methods = array();
			foreach ( $zone->get_shipping_methods() as $method ) {
				if ( $method->is_enabled() ) {
					$methods[ (string) $method->get_instance_id() ] = (string) $method->get_title();
				}
			}
			$zones[ (int) $zone->get_id() ] = array(
				'id'      => (int) $zone->get_id(),
				'name'    => (string) $zone->get_zone_name(),
				'methods' => $methods,
			);
		}
		return $zones;
	}

	/**
	 * Validate a shipping zone ID.
	 *
	 * @param int $zone_id Zone ID; 0 is "Rest of the World".
	 * @return true|\WP_Error
	 */
	public static function shipping_zone( int $zone_id ) {
		$zones = self::shipping_zones();
		return isset( $zones[ $zone_id ] ) ? true : self::not_found( __( 'Shipping zone not found', 'wholesalex' ), array( $zone_id ), 'wholesalex-list-checkout-options' );
	}

	/**
	 * Build selector rows for shipping methods of one zone.
	 *
	 * @param int   $zone_id      Zone ID.
	 * @param array $instance_ids Shipping method instance IDs.
	 * @return array|\WP_Error
	 */
	public static function shipping_method_rows( int $zone_id, array $instance_ids ) {
		$zones = self::shipping_zones();
		if ( ! isset( $zones[ $zone_id ] ) ) {
			return self::not_found( __( 'Shipping zone not found', 'wholesalex' ), array( $zone_id ), 'wholesalex-list-checkout-options' );
		}
		$rows    = array();
		$missing = array();
		foreach ( self::string_ids( $instance_ids ) as $id ) {
			if ( ! isset( $zones[ $zone_id ]['methods'][ $id ] ) ) {
				$missing[] = $id;
				continue;
			}
			$rows[] = array(
				'value' => $id,
				'name'  => $zones[ $zone_id ]['methods'][ $id ],
			);
		}
		return empty( $missing ) ? $rows : self::not_found( __( 'Shipping methods not found in this zone', 'wholesalex' ), $missing, 'wholesalex-list-checkout-options' );
	}

	/**
	 * Validate shipping method instance IDs across every zone.
	 *
	 * @param array $instance_ids Shipping method instance IDs.
	 * @return string[]|\WP_Error
	 */
	public static function shipping_method_ids( array $instance_ids ) {
		$known = array();
		foreach ( self::shipping_zones() as $zone ) {
			$known = array_merge( $known, array_map( 'strval', array_keys( $zone['methods'] ) ) );
		}
		$ids     = self::string_ids( $instance_ids );
		$missing = array_values( array_diff( $ids, $known ) );
		return empty( $missing ) ? $ids : self::not_found( __( 'Shipping methods not found', 'wholesalex' ), $missing, 'wholesalex-list-checkout-options' );
	}

	/**
	 * Build selector rows for shipping countries.
	 *
	 * @param array $codes ISO 3166-1 alpha-2 country codes.
	 * @return array|\WP_Error
	 */
	public static function country_rows( array $codes ) {
		$countries = function_exists( 'WC' ) && WC()->countries ? WC()->countries->get_shipping_countries() : array();
		$rows      = array();
		$missing   = array();
		foreach ( self::string_ids( $codes ) as $code ) {
			$code = strtoupper( $code );
			if ( ! isset( $countries[ $code ] ) ) {
				$missing[] = $code;
				continue;
			}
			$rows[] = array(
				'value' => $code,
				'name'  => html_entity_decode( (string) $countries[ $code ], ENT_QUOTES, 'UTF-8' ),
			);
		}
		return empty( $missing ) ? $rows : self::not_found( __( 'Countries not available for shipping in this store', 'wholesalex' ), $missing, '' );
	}

	/**
	 * Return the store's additional tax classes.
	 *
	 * @return array<string, string> Tax class names keyed by slug.
	 */
	public static function tax_classes(): array {
		if ( ! class_exists( '\WC_Tax' ) ) {
			return array();
		}
		$classes = array();
		foreach ( \WC_Tax::get_tax_classes() as $name ) {
			$classes[ sanitize_title( $name ) ] = (string) $name;
		}
		return $classes;
	}

	/**
	 * Resolve a tax class slug or name to the stored value.
	 *
	 * WholesaleX stores tax class names, as its admin screens do. "standard"
	 * or an empty string selects the standard rate.
	 *
	 * @param string $value Tax class slug or name.
	 * @return string|\WP_Error
	 */
	public static function tax_class( string $value ) {
		$value = trim( $value );
		if ( '' === $value || 'standard' === strtolower( $value ) ) {
			return '';
		}
		$classes = self::tax_classes();
		if ( isset( $classes[ sanitize_title( $value ) ] ) ) {
			return $classes[ sanitize_title( $value ) ];
		}
		return self::not_found( __( 'Tax class not found', 'wholesalex' ), array( $value ), 'wholesalex-list-checkout-options' );
	}

	/**
	 * Convert stored selector rows into references for tool output.
	 *
	 * @param mixed  $rows    Stored rows.
	 * @param string $id_type "integer" or "string".
	 * @return array[]
	 */
	public static function references( $rows, string $id_type = 'integer' ): array {
		$references = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$value = is_array( $row ) ? ( $row['value'] ?? null ) : $row;
			if ( ! is_scalar( $value ) || '' === (string) $value ) {
				continue;
			}
			$name         = is_array( $row ) && isset( $row['name'] ) && is_scalar( $row['name'] ) ? (string) $row['name'] : '';
			$references[] = array(
				'id'   => 'integer' === $id_type ? absint( preg_replace( '/^user_/', '', (string) $value ) ) : (string) $value,
				'name' => $name,
			);
		}
		return $references;
	}

	/**
	 * Convert stored customer rows into references without email addresses.
	 *
	 * @param mixed $rows Stored rows.
	 * @return array[]
	 */
	public static function user_references( $rows ): array {
		$references = array();
		foreach ( self::references( $rows ) as $reference ) {
			$user         = $reference['id'] ? get_userdata( $reference['id'] ) : false;
			$references[] = array(
				'id'   => $reference['id'],
				'name' => $user ? $user->display_name : __( '(deleted user)', 'wholesalex' ),
			);
		}
		return $references;
	}

	/**
	 * Build selector rows for one kind of record.
	 *
	 * @param string $kind "products", "variations", "categories", "customers" or "roles".
	 * @param array  $ids  Record IDs.
	 * @return array|\WP_Error
	 */
	public static function rows_for( string $kind, array $ids ) {
		switch ( $kind ) {
			case 'products':
				return self::product_rows( $ids );
			case 'variations':
				return self::variation_rows( $ids );
			case 'categories':
				return self::category_rows( $ids );
			case 'customers':
				return self::user_rows( $ids );
			case 'roles':
				return self::role_rows( $ids );
		}
		return new \WP_Error( 'wholesalex_mcp_invalid_input', __( 'Unsupported selection.', 'wholesalex' ), array( 'status' => 400 ) );
	}

	/**
	 * Convert stored selector rows of one kind into references for tool output.
	 *
	 * @param string $kind "products", "variations", "categories", "customers" or "roles".
	 * @param mixed  $rows Stored rows.
	 * @return array[]
	 */
	public static function references_for( string $kind, $rows ): array {
		if ( 'customers' === $kind ) {
			return self::user_references( $rows );
		}
		return self::references( $rows, 'roles' === $kind ? 'string' : 'integer' );
	}

	/**
	 * Build rows for posts of one type.
	 *
	 * @param array  $ids       Post IDs.
	 * @param string $post_type Expected post type.
	 * @param string $label     Error label.
	 * @param string $tool      Tool that finds valid IDs.
	 * @return array|\WP_Error
	 */
	private static function post_rows( array $ids, string $post_type, string $label, string $tool ) {
		$rows    = array();
		$missing = array();
		foreach ( self::int_ids( $ids ) as $id ) {
			$post = get_post( $id );
			if ( ! $post || $post_type !== $post->post_type || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
				$missing[] = $id;
				continue;
			}
			$rows[] = array(
				'value' => $id,
				'name'  => '#' . $id . ' ' . $post->post_title,
			);
		}
		return empty( $missing ) ? $rows : self::not_found( $label, $missing, $tool );
	}

	/**
	 * Normalize positive integer IDs and drop duplicates.
	 *
	 * @param array $ids Raw IDs.
	 * @return int[]
	 */
	public static function int_ids( array $ids ): array {
		return array_values( array_unique( array_filter( array_map( 'absint', array_filter( $ids, 'is_scalar' ) ) ) ) );
	}

	/**
	 * Normalize string IDs and drop duplicates.
	 *
	 * @param array $ids Raw IDs.
	 * @return string[]
	 */
	public static function string_ids( array $ids ): array {
		$ids = array_map(
			static function ( $id ) {
				return sanitize_text_field( (string) $id );
			},
			array_filter( $ids, 'is_scalar' )
		);
		return array_values( array_unique( array_filter( $ids, 'strlen' ) ) );
	}

	/**
	 * Build a "not found" error that tells the assistant how to recover.
	 *
	 * @param string $label   What was not found.
	 * @param array  $missing Missing IDs.
	 * @param string $tool    Tool that lists valid IDs, if any.
	 * @return \WP_Error
	 */
	private static function not_found( string $label, array $missing, string $tool ): \WP_Error {
		$message = sprintf(
			/* translators: 1: what was not found, 2: comma-separated IDs. */
			__( '%1$s: %2$s.', 'wholesalex' ),
			$label,
			implode( ', ', array_map( 'strval', array_slice( $missing, 0, 20 ) ) )
		);
		if ( '' !== $tool ) {
			$message .= ' ' . sprintf(
				/* translators: %s: tool name. */
				__( 'Use the %s tool to find valid IDs.', 'wholesalex' ),
				$tool
			);
		}
		return new \WP_Error( 'wholesalex_mcp_not_found', $message, array( 'status' => 404 ) );
	}
}
