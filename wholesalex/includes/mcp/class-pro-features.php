<?php
/**
 * Pro feature discovery and upgrade guidance for the AI tools.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP;

use WHOLESALEX\MCP\Abilities\Registration_Abilities;
use WHOLESALEX\MCP\Abilities\Role_Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Describes Pro without registering its implementations or widening schemas.
 */
final class Pro_Features {

	/** Pricing page; prices and plan details are deliberately not cached here. */
	const PRICING_URL = 'https://getwholesalex.com/pricing/';

	/** Pro dynamic rule identifiers, matching the licensed MCP integration. */
	const DYNAMIC_TYPES = array( 'quantity_based', 'extra_charge', 'buy_x_get_y', 'max_order_qty', 'hidden_price', 'non_purchasable', 'restrict_product_visibility', 'restrict_checkout' );

	/** Pro wholesale pricing identifiers. */
	const PRICING_TYPES = array( 'tier_pricing', 'buy_x_get_y' );

	/** Pro settings section identifiers. */
	const SECTIONS = array( 'addon_bulkorder', 'addon_subaccount', 'addon_raq', 'addon_conversation', 'addon_wallet', 'addon_whitelabel', 'bxgy_promotions', 'design', 'addon_emails' );

	/** Pro registration field properties. */
	const FIELD_PROPERTIES = array( 'custom_type', 'options', 'min_length', 'max_length', 'allowed_file_types', 'max_file_size_mb' );

	/** Pro User Roles restriction properties. */
	const ROLE_PROPERTIES = array( 'product_visibility', 'non_purchasable', 'checkout_restriction', 'price_visibility', 'tax_rule', 'replace_cart_with_quote', 'restrict_subaccount_creation', 'general_excluded_customer_ids' );

	/**
	 * Whether Pro considers its features active, including its license check.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return function_exists( 'wholesalex_pro' ) && wholesalex_pro()->is_active();
	}

	/**
	 * Informational catalogue, distinct from the executable rule type lists.
	 *
	 * @return array
	 */
	public static function overview(): array {
		$active   = self::is_active();
		$overview = array(
			'active'   => $active,
			'features' => array(
				__( 'Tiered wholesale pricing and quantity-based dynamic discounts.', 'wholesalex' ),
				__( 'Buy X Get Y free products, payment method extra charges and maximum order quantities.', 'wholesalex' ),
				__( 'Additional User Roles MCP controls supplied by the separate Pro add-on: product and price visibility, purchase and checkout restrictions, tax configuration, cart-to-quote, restrictions on subaccount creation and customer exclusions.', 'wholesalex' ),
				__( 'Conditions based on customer order count and lifetime purchase total.', 'wholesalex' ),
				__( 'Custom registration fields, choice options, text limits and file upload limits.', 'wholesalex' ),
				__( 'Bulk Order Form, Subaccounts, Request a Quote, Conversations, Wallet and White Label add-on settings, promotion display, tier table design and add-on emails.', 'wholesalex' ),
			),
			'guidance' => __( 'This is an informational Pro catalogue. Only rule types, role restrictions and settings in the current tool schemas are executable through MCP. Use User Roles restrictions by default for all role restriction requests when their Pro controls are available. Existing tax and coupon features in the Free role editor remain available there without Pro. Individual subaccount creation requires the separate Pro get-subaccount-options and create-subaccount tools in the current schema, explicit user authorization, required account fields and selected permissions. Add-on settings alone do not create accounts. Editing/deleting subaccounts and processing quotes, conversations or wallet transactions remain unavailable. Some settings are read-only; Request a Quote also requires Conversations.', 'wholesalex' ) . ' ' . self::instructions(),
		);
		if ( ! $active ) {
			$overview['pricing_url'] = self::PRICING_URL;
		}
		return $overview;
	}

	/**
	 * Current Pro guidance for server instructions and tool discovery.
	 *
	 * @return string
	 */
	public static function instructions(): string {
		if ( self::is_active() ) {
			return __( 'WholesaleX Pro is already active. Help the user with supported Pro features. For unavailable features, check related add-on settings, plugin updates and refreshing the AI client tools. Do not include pricing links or suggest purchasing Pro, upgrading or comparing plans.', 'wholesalex' );
		}
		return __( 'When the user asks about Pro features or requests an unavailable Pro feature, read the overview pro status and catalogue, explain the relevant benefit, and include the pricing link: https://getwholesalex.com/pricing/. If Pro is inactive, explain that the feature requires the separate WholesaleX Pro add-on installed and active with a valid license; existing owners can activate it. Only offer this upgrade guidance for relevant Pro questions or requests. Refer to the pricing page for current prices and plan inclusion. If the overview shows Pro has become active, stop showing pricing links and purchase or upgrade suggestions.', 'wholesalex' );
	}

	/**
	 * Explain an unavailable known Pro feature without implying a new purchase
	 * is necessary for a store that already has active Pro.
	 *
	 * @return \WP_Error
	 */
	public static function required(): \WP_Error {
		if ( self::is_active() ) {
			return new \WP_Error(
				'wholesalex_mcp_pro_unavailable',
				__( 'WholesaleX Pro is active, but this feature is unavailable through the current MCP tools. Check the related add-on, update WholesaleX and WholesaleX Pro, then reconnect your AI client to refresh its tools.', 'wholesalex' )
			);
		}
		$message = __( 'This feature requires the WholesaleX Pro add-on to be installed and active with a valid license. If you already own Pro, activate the add-on and its license, then reconnect your AI client. Otherwise, compare plans and upgrade:', 'wholesalex' );
		return new \WP_Error(
			'wholesalex_mcp_pro_required',
			$message . ' ' . self::PRICING_URL,
			array( 'pricing_url' => self::PRICING_URL )
		);
	}

	/**
	 * Explain an absent Pro adapter without implying Free editor features are locked.
	 *
	 * @return \WP_Error
	 */
	public static function role_integration_required(): \WP_Error {
		$error = self::required();
		return new \WP_Error(
			$error->get_error_code(),
			__( 'The requested role restriction MCP control is supplied by the separate WholesaleX Pro add-on. Existing Free role-editor features remain available without a Pro license.', 'wholesalex' ) . ' ' . $error->get_error_message(),
			$error->get_error_data()
		);
	}

	/**
	 * Identify known Pro input absent from the current providers.
	 *
	 * Unknown identifiers retain normal validation errors. This never grants
	 * permission, changes data or adds unavailable features to an input schema.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Tool input.
	 * @return \WP_Error|null
	 */
	public static function check_input( string $name, array $input ) {
		// Validation filters can run before WordPress checks ability permissions.
		// Do not inspect providers or disclose Pro status to unauthorized callers.
		$operation = 'wholesalex/get-settings' === $name ? Access::READ : Access::WRITE;
		if ( is_wp_error( Access::check( $operation ) ) ) {
			return null;
		}
		$dynamic = in_array( $name, array( 'wholesalex/create-dynamic-rule', 'wholesalex/update-dynamic-rule' ), true );
		$pricing = in_array( $name, array( 'wholesalex/create-pricing-rule', 'wholesalex/update-pricing-rule' ), true );
		if ( $dynamic || $pricing ) {
			$types = $dynamic ? Dynamic_Rule_Types::types() : Pricing_Rule_Types::types();
			$known = $dynamic ? self::DYNAMIC_TYPES : self::PRICING_TYPES;
			foreach ( $known as $type ) {
				if ( ! isset( $types[ $type ] ) && ( ( $input['type'] ?? null ) === $type || array_key_exists( $type, $input ) ) ) {
					return self::required();
				}
			}
			$fields = $dynamic ? Dynamic_Rule_Types::condition_fields() : Pricing_Rule_Types::condition_fields();
			$known  = $dynamic ? array( 'order_count', 'total_purchase' ) : array( 'pro_order_count', 'pro_total_purchase' );
			foreach ( is_array( $input['conditions'] ?? null ) ? $input['conditions'] : array() as $condition ) {
				$field = is_array( $condition ) ? ( $condition['field'] ?? null ) : null;
				if ( is_string( $field ) && in_array( $field, $known, true ) && ! isset( $fields[ $field ] ) ) {
					return self::required();
				}
			}
		}

		if ( in_array( $name, array( 'wholesalex/create-role', 'wholesalex/update-role' ), true ) ) {
			$properties = Role_Abilities::definitions()[ $name ]['input_schema']['properties'];
			foreach ( self::ROLE_PROPERTIES as $property ) {
				if ( array_key_exists( $property, $input ) && ! isset( $properties[ $property ] ) ) {
					return self::role_integration_required();
				}
			}
		}

		if ( in_array( $name, array( 'wholesalex/add-registration-field', 'wholesalex/update-registration-field' ), true ) ) {
			$properties = Registration_Abilities::definitions()[ $name ]['input_schema']['properties'];
			foreach ( self::FIELD_PROPERTIES as $property ) {
				if ( 'custom_type' === $property && 'wholesalex/update-registration-field' === $name ) {
					continue;
				}
				if ( array_key_exists( $property, $input ) && ! isset( $properties[ $property ] ) ) {
					return self::required();
				}
			}
		}

		if ( in_array( $name, array( 'wholesalex/get-settings', 'wholesalex/update-settings' ), true ) ) {
			$sections = Settings_Catalog::sections();
			$section  = $input['section'] ?? null;
			if ( is_string( $section ) && in_array( $section, self::SECTIONS, true ) && ! isset( $sections[ $section ] ) ) {
				return self::required();
			}
			// Recognize exact add-on toggles; arbitrary unknown keys stay unknown.
			foreach ( is_array( $input['changes'] ?? null ) ? $input['changes'] : array() as $change ) {
				$key = is_array( $change ) ? ( $change['key'] ?? null ) : null;
				foreach ( array( 'bulkorder', 'subaccount', 'raq', 'conversation', 'wallet', 'whitelabel' ) as $addon ) {
					if ( 'wsx_addon_' . $addon === $key && ! isset( $sections[ 'addon_' . $addon ]['fields'][ $key ] ) ) {
						return self::required();
					}
				}
			}
		}
		return null;
	}

	/**
	 * Explain known Pro inputs before the adapter runs closed-schema validation.
	 * The adapter has already checked the tool's permissions at this point.
	 *
	 * @param mixed  $input Tool arguments or an earlier filter's error.
	 * @param string $name  MCP tool name.
	 * @return mixed
	 */
	public static function filter_tool_input( $input, string $name ) {
		if ( ! is_array( $input ) || 0 !== strpos( $name, 'wholesalex-' ) ) {
			return $input;
		}
		return self::check_input( 'wholesalex/' . substr( $name, strlen( 'wholesalex-' ) ), $input ) ?? $input;
	}

	/**
	 * Add the same explanation to direct abilities on WordPress 7.1+.
	 * Also covers adapters without the pre-tool-call filter on those versions.
	 *
	 * @param true|\WP_Error $validity Schema validation result.
	 * @param mixed          $input    Ability input.
	 * @param string         $name     Ability name.
	 * @return true|\WP_Error
	 */
	public static function filter_validation( $validity, $input, string $name ) {
		if ( ! is_wp_error( $validity ) || 'ability_invalid_input' !== $validity->get_error_code() || ! is_array( $input ) || 0 !== strpos( $name, 'wholesalex/' ) ) {
			return $validity;
		}
		return self::check_input( $name, $input ) ?? $validity;
	}
}
