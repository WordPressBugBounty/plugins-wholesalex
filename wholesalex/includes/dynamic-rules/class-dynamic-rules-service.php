<?php
/**
 * Dynamic rule write operations shared by every entry point.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * Saves, toggles and deletes dynamic rules.
 *
 * The admin REST route and the AI tools both call this class, so every write
 * runs the same storage validation, provider hooks and extension filters.
 * Authorization stays with the entry points: the REST route checks the
 * manager and vendor scope, and the AI tools allow administrators only.
 */
final class Dynamic_Rules_Service {

	/**
	 * Create or update a rule.
	 *
	 * @param string $id          Rule ID.
	 * @param array  $data        Rule fields in the stored format.
	 * @param bool   $is_frontend Whether the request comes from a storefront editor.
	 * @return true|\WP_Error
	 */
	public static function save( string $id, array $data, bool $is_frontend = false ) {
		/**
		 * Filters a dynamic rule before it is saved.
		 *
		 * @param array $data Rule fields in the stored format.
		 */
		$data   = apply_filters( 'wholesalex_dynamic_rule_data_before_save', $data );
		$result = wholesalex()->set_dynamic_rules( $id, $data, '', $is_frontend );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Fires after a dynamic rule is saved.
		 *
		 * @param array $data Rule fields that were submitted.
		 */
		do_action( 'wholesalex_dynamic_rule_data_after_save', $data );

		return true;
	}

	/**
	 * Change the status of an existing rule.
	 *
	 * @param string $id     Rule ID.
	 * @param mixed  $status New `_rule_status` value, for example "yes" or "no".
	 * @return true|\WP_Error True when saved or when the rule does not exist.
	 */
	public static function set_status( string $id, $status ) {
		$existing = wholesalex()->get_dynamic_rules( $id );
		if ( empty( $existing ) ) {
			return true;
		}

		$existing['_rule_status'] = $status;
		$result                   = wholesalex()->set_dynamic_rules( $id, $existing );

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Delete a rule.
	 *
	 * @param string $id Rule ID.
	 * @return true|\WP_Error True when deleted or when the rule does not exist.
	 */
	public static function delete( string $id ) {
		$existing = wholesalex()->get_dynamic_rules( $id );
		if ( empty( $existing ) ) {
			return true;
		}

		$result = wholesalex()->set_dynamic_rules( $id, $existing, 'delete' );

		return is_wp_error( $result ) ? $result : true;
	}
}
