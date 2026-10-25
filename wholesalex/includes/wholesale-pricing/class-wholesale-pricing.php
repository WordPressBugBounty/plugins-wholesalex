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
				'is_vendor'  => false,
				'vendor_id'  => 0,
				'owner_type' => 'admin',
				'can_manage' => true,
			);
		}

		$global_manager_capability = apply_filters( 'wholesalex_capability_access', 'manage_options' );

		if ( current_user_can( $global_manager_capability ) ) {
			return array(
				'is_vendor'  => false,
				'vendor_id'  => 0,
				'owner_type' => 'admin',
				'can_manage' => true,
			);
		}

		$context = apply_filters(
			'wholesalex_wholesale_pricing_manager_context',
			array(
				'is_vendor'  => false,
				'vendor_id'  => 0,
				'owner_type' => 'admin',
				'can_manage' => false,
			)
		);

		return array(
			'is_vendor'  => ! empty( $context['is_vendor'] ),
			'vendor_id'  => isset( $context['vendor_id'] ) ? absint( $context['vendor_id'] ) : 0,
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
						&& absint( $rule['owner_id'] ?? 0 ) === $context['vendor_id']
						&& sanitize_key( $rule['owner_type'] ?? '' ) === $context['owner_type'];
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

		return absint( $rule['owner_id'] ?? 0 ) === $context['vendor_id']
			&& sanitize_key( $rule['owner_type'] ?? '' ) === $context['owner_type'];
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
			$rules = $GLOBALS[ self::GLOBAL_KEY ];
		} else {
			$rules = get_option( self::OPTION_KEY, array() );
			$rules = is_array( $rules ) ? $rules : array();
		}

		$rules = apply_filters( 'wholesalex_wholesale_pricing_records', $rules );

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
	public static function save_rule( string $id, array $data ) {
		$existing = self::get_rule( $id );
		$provider = self::provider_operation( 'save', $id, $data, $existing );
		if ( null !== $provider ) {
			return $provider; }
		$saved  = null;
		$result = wholesalex()->mutate_rule_option(
			self::OPTION_KEY,
			self::GLOBAL_KEY,
			static function ( $rules ) use ( $id, $data, &$saved ) {
				$existing = $rules[ $id ] ?? null;
				if ( is_array( $existing ) && ( ! self::current_manager_owns_rule( $existing ) || ( self::has_provider_record( $existing ) && ! empty( array_diff( array_keys( $data ), array( 'title' ) ) ) ) ) ) {
					return new \WP_Error( 'wholesalex_rule_conflict', __( 'This rule changed. Reload it before saving.', 'wholesalex' ), array( 'status' => 409 ) );
				}
				$validation = self::validate_provider_write( $data, $existing );
				if ( is_wp_error( $validation ) ) {
					return $validation;
				}
				$owned = array( 'id', 'title', 'status', 'rule_type', 'discount_type', 'product_filter', 'products', 'categories', 'exclude_products', 'user_role_filter', 'user_roles', 'specific_users', 'regular', 'product_discount', 'cart', 'bogo', 'conditions', 'schedule', 'owner_id', 'owner_type', 'created_by' );
				$owned = (array) apply_filters( 'wholesalex_pricing_owned_fields', $owned );
				$data  = array_intersect_key( $data, array_flip( $owned ) );
				foreach ( $data as $key => $value ) {
					if ( is_array( $value ) && is_array( $existing[ $key ] ?? null ) && ( in_array( $key, array( 'cart', 'conditions' ), true ) || ( array() !== $value && array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) ) ) {
						$data[ $key ] = array_replace( $existing[ $key ], $value );
					}
				}
				$data       = array_replace( is_array( $existing ) ? $existing : array(), $data );
				$data['id'] = $id;

				$rules[ $id ] = $data;
				$saved        = $data;
				return $rules;
			}
		);
		if ( is_wp_error( $result ) ) {
			return $result; }
		$data = $saved;

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
	 * Validate ownership before a direct or preflight storage operation.
	 *
	 * @param array      $data Submitted fields.
	 * @param array|null $existing Authoritative stored record.
	 * @return true|WP_Error
	 */
	public static function validate_provider_write( array $data, $existing = null ) {
		foreach ( array(
			'conditions' => 'tiers',
			'cart'       => 'conditions',
		) as $container => $field ) {
			if ( ! array_key_exists( $container, $data ) || ( is_array( $data[ $container ] ) && ! array_key_exists( $field, $data[ $container ] ) ) ) {
				continue;
			}
			$stored_rows    = is_array( $existing[ $container ][ $field ] ?? null ) ? $existing[ $container ][ $field ] : array();
			$submitted_rows = is_array( $data[ $container ][ $field ] ?? null ) ? $data[ $container ][ $field ] : array();
			if ( Wholesale_Pricing_Rule_Registry::unavailable_condition_rows( $stored_rows ) !== Wholesale_Pricing_Rule_Registry::unavailable_condition_rows( $submitted_rows ) ) {
				return new \WP_Error( 'wholesalex_provider_unavailable', __( 'The provider for this pricing operation is unavailable.', 'wholesalex' ), array( 'status' => 409 ) );
			}
		}
		$candidate = array_replace( is_array( $existing ) ? $existing : array(), $data );
		if ( '' !== Wholesale_Pricing_Rule_Registry::unavailable_feature( $candidate ) ) {
			$base_fields = array( 'id', 'title', 'status', 'regular', 'product_discount', 'cart', 'bogo', 'products', 'categories', 'exclude_products', 'user_role_filter', 'user_roles', 'specific_users', 'schedule', 'owner_id', 'owner_type', 'created_by' );
			foreach ( $data as $key => $value ) {
				if ( ! in_array( $key, $base_fields, true ) && ( ! is_array( $existing ) || ! array_key_exists( $key, $existing ) || $existing[ $key ] !== $value ) ) {
					return new \WP_Error( 'wholesalex_provider_unavailable', __( 'The provider for this pricing operation is unavailable.', 'wholesalex' ) );
				}
			}
		}
		return true;
	}

	/**
	 * Delete a rule by ID.
	 *
	 * @param string $id Rule ID.
	 * @return bool True when deleted, false when the rule did not exist.
	 */
	public static function delete_rule( string $id ) {
		$existing = self::get_rule( $id );
		if ( ! $existing ) {
			return false; }
		$provider = self::provider_operation( 'delete', $id, array(), $existing );
		if ( null !== $provider ) {
			return $provider; }
		$result = wholesalex()->mutate_rule_option(
			self::OPTION_KEY,
			self::GLOBAL_KEY,
			static function ( $rules ) use ( $id ) {
				if ( isset( $rules[ $id ] ) && ( self::has_provider_record( $rules[ $id ] ) || ! self::current_manager_owns_rule( $rules[ $id ] ) ) ) {
					return new \WP_Error( 'wholesalex_rule_conflict', __( 'This rule changed. Reload it before deleting.', 'wholesalex' ), array( 'status' => 409 ) );
				}
				unset( $rules[ $id ] );
				return $rules;
			}
		);
		if ( is_wp_error( $result ) ) {
			return $result; }

		/**
		 * Fires after a wholesale-pricing rule is deleted.
		 *
		 * @param string $id Rule ID.
		 */
		do_action( 'wholesalex_wholesale_pricing_rule_deleted', $id );

		return true;
	}
	/**
	 * Detect provider data without validating or executing it.
	 *
	 * @param array $rule Stored or submitted rule.
	 * @return bool
	 */
	public static function has_provider_record( array $rule ): bool {
		return 'tier_pricing' === ( $rule['rule_type'] ?? '' ) || 'tiered' === ( $rule['discount_type'] ?? '' );
	}

	/**
	 * Dispatch an operation to its owner before any storage mutation.
	 *
	 * @param string     $operation Command name.
	 * @param string     $id Rule identifier.
	 * @param array      $payload Submitted values.
	 * @param array|null $authoritative Authoritative stored rule.
	 * @return mixed Canonical result, error, or null for a Free-owned operation.
	 */
	public static function provider_operation( string $operation, string $id, array $payload, $authoritative = null ) {
		$is_provider = self::has_provider_record( $payload ) || ( is_array( $authoritative ) && self::has_provider_record( $authoritative ) );
		if ( ! $is_provider && ! array_key_exists( 'tiers', $payload ) ) {
			return null;
		}
		if ( 'save' === $operation && is_array( $authoritative ) && self::has_provider_record( $authoritative ) ) {
			$changed = array();
			foreach ( $payload as $key => $value ) {
				if ( ! array_key_exists( $key, $authoritative ) || $value !== $authoritative[ $key ] ) {
					$changed[] = $key; }
			}
			if ( empty( array_diff( $changed, array( 'title' ) ) ) ) {
				return null; }
		}
		if ( ! $is_provider && is_array( $authoritative ) && array_key_exists( 'tiers', $payload ) && array_key_exists( 'tiers', $authoritative ) && $payload['tiers'] === $authoritative['tiers'] ) {
			return null;
		}
		// A narrow Free patch may update the title without touching owned data.
		if ( 'save' === $operation && is_array( $authoritative ) && empty( array_diff( array_keys( $payload ), array( 'title' ) ) ) ) {
			return null;
		}
		$result = apply_filters( 'wholesalex_wholesale_pricing_provider_result', null, $operation, $id, $payload, $authoritative );
		return null !== $result ? $result : new \WP_Error( 'wholesalex_provider_unavailable', __( 'The provider for this pricing operation is unavailable.', 'wholesalex' ), array( 'status' => 409 ) );
	}
}
