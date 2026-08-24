<?php
/**
 * WholesaleX Wholesale Pricing – Storage
 *
 * All wholesale-pricing rules are stored in a single wp_options row keyed by
 * rule ID, exactly mirroring the dynamic-rules pattern already used in the
 * plugin.
 *
 * Option key : __wholesalex_pricing_rules  (autoload = no – admin-only data)
 * Global cache: $GLOBALS['wholesalex_pricing_rules']
 *
 * Usage
 * -----
 *   $rule  = Wholesale_Pricing::get_rule( $id );
 *   $saved = Wholesale_Pricing::save_rule( $id, $data );
 *           Wholesale_Pricing::delete_rule( $id );
 *   $all   = Wholesale_Pricing::get_rules_for_js();   // indexed array for JS
 *
 * @package WHOLESALEX
 * @since   1.0.0
 */

namespace WHOLESALEX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wholesale_Pricing
 *
 * Thin wrapper around wp_options for persisting wholesale-pricing rules.
 * All heavy sanitisation lives in the REST API layer; this class just
 * reads and writes the option.
 */
class Wholesale_Pricing {

	const OPTION_KEY = '__wholesalex_pricing_rules';
	const GLOBAL_KEY = 'wholesalex_pricing_rules';

	/**
	 * Return the current wholesale-pricing management context.
	 *
	 * Marketplace integration plugins use this filter to identify vendor REST
	 * requests without coupling the core plugin to Dokan or WCFM.
	 *
	 * @return array{is_vendor: bool, vendor_id: int, owner_type: string, can_manage: bool}
	 */
	public static function get_manager_context(): array {
		/*
		 * Marketplace plugins may identify an account as a seller even when the
		 * account is also a site administrator. Administrators must always keep
		 * the global rule view; otherwise activating a marketplace integration
		 * can make existing, unowned/admin rules appear to have disappeared.
		 */
		if ( current_user_can( 'manage_options' ) ) {
			return array(
				'is_vendor' => false,
				'vendor_id' => 0,
				'owner_type' => 'admin',
				'can_manage' => true,
			);
		}

		$global_manager_capability = apply_filters( 'wholesalex_capability_access', 'manage_options' );

		if ( current_user_can( $global_manager_capability ) ) {
			return array(
				'is_vendor' => false,
				'vendor_id' => 0,
				'owner_type' => 'admin',
				'can_manage' => true,
			);
		}

		$context = apply_filters(
			'wholesalex_wholesale_pricing_manager_context',
			array(
				'is_vendor' => false,
				'vendor_id' => 0,
				'owner_type' => 'admin',
				'can_manage' => false,
			)
		);

		return array(
			'is_vendor' => ! empty( $context['is_vendor'] ),
			'vendor_id' => isset( $context['vendor_id'] ) ? absint( $context['vendor_id'] ) : 0,
			'owner_type' => isset( $context['owner_type'] ) ? sanitize_key( $context['owner_type'] ) : 'admin',
			'can_manage' => ! empty( $context['can_manage'] ),
		);
	}

	/**
	 * Return rules visible to the current pricing manager.
	 *
	 * @return array[]
	 */
	public static function get_rules_for_current_manager(): array {
		$context = self::get_manager_context();
		$rules   = self::get_all_rules();

		if ( ! $context['is_vendor'] ) {
			return array_values( $rules );
		}

		return array_values(
			array_filter(
				$rules,
				static function ( $rule ) use ( $context ) {
					return is_array( $rule )
						&& $context['vendor_id'] === absint( $rule['owner_id'] ?? 0 )
						&& $context['owner_type'] === sanitize_key( $rule['owner_type'] ?? '' );
				}
			)
		);
	}

	/**
	 * Check whether the current pricing manager may mutate a rule.
	 *
	 * @param array $rule Rule data.
	 * @return bool
	 */
	public static function current_manager_owns_rule( array $rule ): bool {
		$context = self::get_manager_context();

		if ( ! $context['is_vendor'] ) {
			return true;
		}

		return $context['vendor_id'] === absint( $rule['owner_id'] ?? 0 )
			&& $context['owner_type'] === sanitize_key( $rule['owner_type'] ?? '' );
	}

	// ── Readers ──────────────────────────────────────────────────────────────

	/**
	 * Return all rules as an associative array keyed by rule ID.
	 *
	 * Uses the in-memory global cache populated by Functions.php at plugin
	 * init so every request pays at most one DB read.
	 *
	 * @return array<string, array>
	 */
	public static function get_all_rules(): array {
		if ( isset( $GLOBALS[ self::GLOBAL_KEY ] ) && is_array( $GLOBALS[ self::GLOBAL_KEY ] ) ) {
			return $GLOBALS[ self::GLOBAL_KEY ];
		}
		$rules = get_option( self::OPTION_KEY, array() );
		$rules = is_array( $rules ) ? $rules : array();

		$GLOBALS[ self::GLOBAL_KEY ] = $rules; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- GLOBAL_KEY contains the WholesaleX-prefixed cache key.
		return $rules;
	}

	/**
	 * Return a single rule by ID, or null when not found.
	 *
	 * @param string $id Rule ID.
	 * @return array|null
	 */
	public static function get_rule( string $id ): ?array {
		$rules = self::get_all_rules();
		return isset( $rules[ $id ] ) ? $rules[ $id ] : null;
	}

	/**
	 * Return all rules as a flat indexed array (for the React frontend).
	 *
	 * @return array[]
	 */
	public static function get_rules_for_js(): array {
		return array_values( self::get_all_rules() );
	}

	// ── Writers ───────────────────────────────────────────────────────────────

	/**
	 * Insert or update a rule.
	 *
	 * The caller (REST API layer) is responsible for sanitising $data before
	 * passing it here.
	 *
	 * @param string $id   Rule ID (used as the array key).
	 * @param array  $data Sanitised rule data.
	 * @return array The stored rule.
	 */
	public static function save_rule( string $id, array $data ): array {
		$rules        = self::get_all_rules();
		$rules[ $id ] = $data;

		update_option( self::OPTION_KEY, $rules, false ); // autoload=false
		$GLOBALS[ self::GLOBAL_KEY ] = $rules; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- GLOBAL_KEY contains the WholesaleX-prefixed cache key.

		/**
		 * Fires after a wholesale-pricing rule is saved.
		 *
		 * @param string $id   Rule ID.
		 * @param array  $data Saved rule data.
		 */
		do_action( 'wholesalex_wholesale_pricing_rule_saved', $id, $data );

		return $data;
	}

	/**
	 * Delete a rule by ID.
	 *
	 * @param string $id Rule ID.
	 * @return bool True when deleted, false when the rule did not exist.
	 */
	public static function delete_rule( string $id ): bool {
		$rules = self::get_all_rules();

		if ( ! isset( $rules[ $id ] ) ) {
			return false;
		}

		unset( $rules[ $id ] );

		update_option( self::OPTION_KEY, $rules, false );
		$GLOBALS[ self::GLOBAL_KEY ] = $rules; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- GLOBAL_KEY contains the WholesaleX-prefixed cache key.

		/**
		 * Fires after a wholesale-pricing rule is deleted.
		 *
		 * @param string $id Rule ID.
		 */
		do_action( 'wholesalex_wholesale_pricing_rule_deleted', $id );

		return true;
	}
}
