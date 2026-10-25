<?php
/** Saved-domain adapters for the neutral admin transport.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX\Admin\Rest;

defined( 'ABSPATH' ) || exit;

/** Keeps allowlists and domain semantics separate from HTTP. */
final class Admin_Resource_Repository {
	/**
	 * Return the authoritative backing option.
	 *
	 * @param int $product_id Product ID or zero for settings.
	 * @return string
	 */
	public function storage_key( $product_id ) {
		return $product_id ? '__wholesalex_single_product_settings' : 'wholesalex_settings';
	}
	/**
	 * Return allowed saved-domain values, without UI schemas.
	 *
	 * @param int $product_id Product ID or zero for settings.
	 * @return array
	 */
	public function rules( $product_id ) {
		return $product_id ? array(
			'_settings_product_visibility._hide_for_b2c' => array( 'yes', 'no' ),
			'_settings_product_visibility._hide_for_visitors' => array( 'yes', 'no' ),
		) : array(
			'_settings_status'          => array( 'b2b', 'b2b_n_b2c' ),
			'_is_sale_or_regular_Price' => array( 'is_regular_price', 'is_sale_price' ),
			'_settings_disable_coupon'  => array( 'yes', 'no' ),
		);
	}
	/**
	 * Read fresh persisted values through the existing product repository.
	 *
	 * @param int $product_id Product ID or zero for settings.
	 * @return array
	 */
	public function raw( $product_id ) {
		// Refresh both WP's option cache and the legacy repository's request snapshot.
		$key = $this->storage_key( $product_id );
		wp_cache_delete( $key, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		$stored = (array) get_option( $key, array() );
		if ( $product_id ) {
			$GLOBALS['wholesalex_single_product_settings'] = $stored;
			return (array) wholesalex()->get_single_product_setting( $product_id );
		}
		$GLOBALS['wholesalex_settings'] = $stored;
		return $stored;
	}
	/**
	 * Expose only owned leaf paths.
	 *
	 * @param array $raw Complete saved resource.
	 * @param int   $product_id Product ID or zero for settings.
	 * @return array
	 */
	public function project( array $raw, $product_id ) {
		$data = array();
		foreach ( $this->rules( $product_id ) as $path => $values ) {
			$parts = explode( '.', $path );
			if ( 2 === count( $parts ) ) {
				if ( isset( $raw[ $parts[0] ] ) && is_array( $raw[ $parts[0] ] ) && array_key_exists( $parts[1], $raw[ $parts[0] ] ) ) {
					$data[ $path ] = $raw[ $parts[0] ][ $parts[1] ];
				}
			} elseif ( array_key_exists( $path, $raw ) ) {
				$data[ $path ] = $raw[ $path ];
			}
		}
		return $data;
	}
	/**
	 * Resolve domain defaults without manufacturing saved overrides.
	 *
	 * @param array $data Owned persisted values.
	 * @param int   $product_id Product ID or zero for settings.
	 * @return array
	 */
	public function effective( array $data, $product_id ) {
		if ( $product_id ) {
			return $data + array(
				'_settings_product_visibility._hide_for_b2c' => 'no',
				'_settings_product_visibility._hide_for_visitors' => 'no',
			);
		}
		return \WHOLESALEX\Functions::normalize_store_mode( $data ) + array(
			'_settings_status'          => 'b2b',
			'_is_sale_or_regular_Price' => 'is_regular_price',
			'_settings_disable_coupon'  => 'no',
		);
	}
	/**
	 * Reject unknown keys, invalid enums and conflicting resets.
	 *
	 * @param array $changes Changed leaf paths.
	 * @param array $resets Removed leaf paths.
	 * @param int   $product_id Product ID or zero for settings.
	 * @return bool|\WP_Error
	 */
	public function validate( array $changes, array $resets, $product_id ) {
		$rules = $this->rules( $product_id );
		foreach ( $changes as $key => $value ) {
			if ( ! isset( $rules[ $key ] ) || ! in_array( $value, $rules[ $key ], true ) ) {
				return new \WP_Error(
					'wholesalex_invalid_field',
					'Invalid field value.',
					array(
						'status' => 422,
						'field'  => $key,
					)
				);
			}
		}
		foreach ( $resets as $key ) {
			if ( ! is_string( $key ) || ! isset( $rules[ $key ] ) || array_key_exists( $key, $changes ) ) {
				return new \WP_Error( 'wholesalex_invalid_reset', 'Invalid or conflicting reset.', array( 'status' => 422 ) );
			}
		}
		return true;
	}
	/**
	 * Merge owned leaves and persist through existing repositories.
	 *
	 * @param array $raw Complete saved resource.
	 * @param array $changes Changed leaf paths.
	 * @param array $resets Removed leaf paths.
	 * @param int   $product_id Product ID or zero for settings.
	 * @return bool
	 */
	public function save( array $raw, array $changes, array $resets, $product_id ) {
		foreach ( $changes as $path => $value ) {
			$parts = explode( '.', $path );
			if ( 2 === count( $parts ) ) {
				$raw[ $parts[0] ][ $parts[1] ] = $value; } else {
							$raw[ $path ] = $value; }
		}
		foreach ( $resets as $path ) {
			$parts = explode( '.', $path );
			if ( 2 === count( $parts ) ) {
				unset( $raw[ $parts[0] ][ $parts[1] ] ); } else {
							unset( $raw[ $path ] ); }
		}
		if ( $product_id ) {
			wholesalex()->save_single_product_settings( $product_id, $raw ); } else {
					$raw = \WHOLESALEX\Functions::normalize_store_mode( $raw );
			wholesalex()->save_admin_settings_data( $raw ); }
			$saved = $this->raw( $product_id );
			return $this->project( $saved, $product_id ) === $this->project( $raw, $product_id );
	}
}
