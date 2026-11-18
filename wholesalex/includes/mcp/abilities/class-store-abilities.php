<?php
/**
 * Store overview tool.
 *
 * @package WholesaleX
 * @since   3.2.0
 */

namespace WHOLESALEX\MCP\Abilities;

use WHOLESALEX\Functions;
use WHOLESALEX\MCP\Access;
use WHOLESALEX\MCP\Dynamic_Rule_Types;
use WHOLESALEX\MCP\Pricing_Rule_Types;
use WHOLESALEX\MCP\Pro_Features;
use WHOLESALEX\MCP\Schema;
use WHOLESALEX\MCP\Server;
use WHOLESALEX\MCP\Support;
use WHOLESALEX\Registration_Form_Service;
use WHOLESALEX\Wholesale_Pricing;

defined( 'ABSPATH' ) || exit;

/**
 * Gives an AI assistant the store context it needs before making changes.
 */
final class Store_Abilities {

	/**
	 * Return the tool definitions.
	 *
	 * @return array<string, array>
	 */
	public static function definitions(): array {
		return array(
			'wholesalex/get-store-overview' => array(
				'label'        => __( 'Get the WholesaleX store overview', 'wholesalex' ),
				'description'  => __( 'Returns the store mode, currency, weight unit, today\'s date in the store timezone, how many roles, rules and registration fields exist, and which rule types are available. Also returns rule selection guidance, Pro activation status, an informational Pro feature catalogue and support guidance for missing features. Call this first to understand the store and when asked about feature availability.', 'wholesalex' ) . ' ' . Server::rule_guidance() . ' ' . Pro_Features::instructions() . ' ' . Support::instructions(),
				'operation'    => Access::READ,
				'input_schema' => Schema::no_input(),
				'callback'     => array( __CLASS__, 'overview' ),
			),
		);
	}

	/**
	 * Build the overview.
	 *
	 * @return array
	 */
	public static function overview(): array {
		$dynamic_rules = wholesalex()->get_dynamic_rules();
		$pricing_rules = Wholesale_Pricing::get_rules_for_current_manager();
		$settings      = Functions::normalize_store_mode( get_option( 'wholesalex_settings', array() ) );

		$fields = 0;
		foreach ( Registration_Form_Service::get_form()['registrationFields'] ?? array() as $row ) {
			$fields += is_array( $row ) ? count( (array) ( $row['columns'] ?? array() ) ) : 0;
		}

		return array(
			'store_name'         => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ),
			'wholesalex_version' => WHOLESALEX_VER,
			'store_mode'         => is_array( $settings ) && 'b2b_n_b2c' === ( $settings['_settings_status'] ?? '' ) ? 'b2b_and_b2c' : 'b2b_only',
			'currency'           => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			'currency_symbol'    => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '',
			'weight_unit'        => (string) get_option( 'woocommerce_weight_unit', 'kg' ),
			'timezone'           => wp_timezone_string(),
			'today'              => wp_date( 'Y-m-d' ),
			'ai_access_level'    => Access::level(),
			'rule_guidance'      => Server::rule_guidance(),
			'pro'                => Pro_Features::overview(),
			'support'            => Support::overview(),
			'counts'             => array(
				'roles'                => count( wholesalex()->get_roles( 'ids' ) ),
				'dynamic_rules'        => count( $dynamic_rules ),
				'active_dynamic_rules' => count(
					array_filter(
						$dynamic_rules,
						static function ( $rule ) {
							return is_array( $rule ) && in_array( $rule['_rule_status'] ?? '', array( true, 1, '1', 'yes' ), true );
						}
					)
				),
				'pricing_rules'        => count( $pricing_rules ),
				'active_pricing_rules' => count(
					array_filter(
						$pricing_rules,
						static function ( $rule ) {
							return is_array( $rule ) && 'active' === ( $rule['status'] ?? '' );
						}
					)
				),
				'registration_fields'  => $fields,
			),
			'dynamic_rule_types' => self::type_summary( Dynamic_Rule_Types::types() ),
			'pricing_rule_types' => self::type_summary( Pricing_Rule_Types::types() ),
		);
	}

	/**
	 * Summarize rule types.
	 *
	 * @param array $types Rule types.
	 * @return array[]
	 */
	private static function type_summary( array $types ): array {
		$summary = array();
		foreach ( $types as $type => $definition ) {
			$summary[] = array(
				'type'        => $type,
				'label'       => (string) $definition['label'],
				'description' => (string) $definition['description'],
			);
		}
		return $summary;
	}
}
