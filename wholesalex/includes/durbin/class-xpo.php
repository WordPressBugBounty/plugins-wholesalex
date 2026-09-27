<?php
/**
 * Class xpo.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Core class for managing plugin actions and integrations.
 *
 * @package REVX
 */
class Xpo {

	/**
	 * Get Option Value bypassing cache
	 * Inspired By WordPress Core get_option
	 *
	 * @since v.1.0.7
	 * @param string  $option Option Name.
	 * @param boolean $default_value option default value.
	 * @return mixed
	 */
	public static function get_option_without_cache( $option, $default_value = false ) {
		global $wpdb;

		if ( is_scalar( $option ) ) {
			$option = trim( $option );
		}

		if ( empty( $option ) ) {
			return false;
		}

		$value = $default_value;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- This helper deliberately bypasses the options cache; values are passed through wpdb::prepare.

		if ( is_object( $row ) ) {
			$value = $row->option_value;
		} else {
			return apply_filters( "wsx_default_option_{$option}", $default_value, $option ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the existing dynamic WSX filter for backward compatibility.
		}

		return apply_filters( "wsx_option_{$option}", maybe_unserialize( $value ), $option ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the existing dynamic WSX filter for backward compatibility.
	}

	/**
	 * Add option without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @since v.1.0.7
	 * @param string $option option name.
	 * @param string $value option value.
	 * @param string $autoload whether to load WordPress startup.
	 * @return bool
	 */
	public static function add_option_without_cache( $option, $value = '', $autoload = 'yes' ) {
		global $wpdb;

		if ( is_scalar( $option ) ) {
			$option = trim( $option );
		}

		if ( empty( $option ) ) {
			return false;
		}

		wp_protect_special_option( $option );

		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		$value = sanitize_option( $option, $value );

		/*
		* Make sure the option doesn't already exist.
		*/

		if ( apply_filters( "wsx_default_option_{$option}", false, $option, false ) !== self::get_option_without_cache( $option ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the existing dynamic WSX filter for backward compatibility.
			return false;
		}

		$serialized_value = maybe_serialize( $value );
		$autoload         = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';

		$result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- This helper deliberately bypasses the options cache; values are passed through wpdb::prepare.
		if ( ! $result ) {
			return false;
		}

		return true;
	}

	/**
	 * Get Transient Value bypassing cache
	 * Inspired By WordPress Core get_transient
	 *
	 * @since v.1.0.7
	 * @param string $transient Transient Name.
	 * @return mixed
	 */
	public static function get_transient_without_cache( $transient ) {
		$transient_option  = '_transient_' . $transient;
		$transient_timeout = '_transient_timeout_' . $transient;
		$timeout           = self::get_option_without_cache( $transient_timeout );

		if ( false !== $timeout && $timeout < time() ) {
			delete_option( $transient_option );
			delete_option( $transient_timeout );
			$value = false;
		}

		if ( ! isset( $value ) ) {
			$value = self::get_option_without_cache( $transient_option );
		}

		return apply_filters( "wsx_transient_{$transient}", $value, $transient ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the existing dynamic WSX filter for backward compatibility.
	}

	/**
	 * Set transient without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @since v.1.0.7
	 * @param string  $transient Transient Name.
	 * @param mixed   $value Transient Value.
	 * @param integer $expiration Time until expiration in seconds.
	 * @return bool
	 */
	public static function set_transient_without_cache( $transient, $value, $expiration = 0 ) {
		$expiration = (int) $expiration;

		$transient_timeout = '_transient_timeout_' . $transient;
		$transient_option  = '_transient_' . $transient;

		$result = false;

		if ( false === self::get_option_without_cache( $transient_option ) ) {
			$autoload = 'yes';
			if ( $expiration ) {
				$autoload = 'no';
				self::add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
			}
			$result = self::add_option_without_cache( $transient_option, $value, $autoload );
		} else {
			/*
			* If expiration is requested, but the transient has no timeout option,
			* delete, then re-create transient rather than update.
			*/
			$update = true;

			if ( $expiration ) {
				if ( false === self::get_option_without_cache( $transient_timeout ) ) {
					delete_option( $transient_option );
					self::add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
					$result = self::add_option_without_cache( $transient_option, $value, 'no' );
					$update = false;
				} else {
					update_option( $transient_timeout, time() + $expiration );
				}
			}

			if ( $update ) {
				$result = update_option( $transient_option, $value );
			}
		}

		return $result;
	}

	/**
	 * Get the pricing URL.
	 *
	 * @param array $params Legacy campaign parameters retained for compatibility.
	 * @return string
	 */
	public static function generate_utm_link( $params ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Preserve the public method signature.
		return 'https://getwholesalex.com/pricing/';
	}


	/**
	 * Get WOW Products Details.
	 *
	 * @return array
	 */
	public static function get_wow_products_details() {
		// Preserve the bundle's data shape without inspecting unrelated plugins.
		return array(
			'products'        => array(),
			'products_active' => array(),
		);
	}
}
