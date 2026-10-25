<?php
/**
 * Remove only obsolete customer CSV staging, including unsafe public aliases.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Legacy privacy cleanup is independent of merchant-data retention preferences. */
final class Customer_Import_Cleanup {
	/**
	 * Delete known staged CSVs without traversing or following directory symlinks.
	 *
	 * @return bool Whether all known temporary staging was removed.
	 */
	public static function legacy() {
		$uploads = wp_upload_dir( null, false );
		if ( ! isset( $uploads['basedir'] ) || ! is_string( $uploads['basedir'] ) || ! is_dir( $uploads['basedir'] ) ) {
			return false;
		}
		$base = realpath( $uploads['basedir'] );
		if ( false === $base ) {
			return false;
		}
		$dir = $base . '/wholesalex_import_data';
		if ( is_link( $dir ) ) {
			wp_delete_file( $dir );
			if ( is_link( $dir ) ) {
				return false;
			}
		} elseif ( is_dir( $dir ) ) {
			$real = realpath( $dir );
			if ( false === $real || dirname( $real ) !== $base ) {
				return false;
			}
			$stats = get_option( '__wholesalex_customer_import_export_stats', array() );
			$name  = is_array( $stats ) && isset( $stats['file_name'] ) && is_string( $stats['file_name'] ) ? $stats['file_name'] : '';
			$files = glob( $real . '/wholesalex_users*.csv' );
			$files = is_array( $files ) ? array_filter(
				$files,
				static function ( $file ) {
					return (bool) preg_match( '/^wholesalex_users(?:-[a-zA-Z0-9]{32}(?:-\d+)?)?\.csv$/D', basename( $file ) );
				}
			) : array();
			if ( '' !== $name && basename( $name ) === $name && 'csv' === strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
				$files[] = $real . '/' . $name;
			}
			foreach ( array_unique( $files ) as $file ) {
				if ( is_file( $file ) || is_link( $file ) ) {
					wp_delete_file( $file );
					if ( file_exists( $file ) || is_link( $file ) ) {
						return false;
					}
				}
			}
		}
		delete_option( '__wholesalex_customer_import_export_stats' );
		return true;
	}
}
