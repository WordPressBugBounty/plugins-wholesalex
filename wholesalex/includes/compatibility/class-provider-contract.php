<?php
/**
 * Prevent known incompatible provider bootstraps from calling removed APIs.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX\Compatibility;

defined( 'ABSPATH' ) || exit;

/** Compatibility guard only; provides no premium behavior. */
final class Provider_Contract {
	/**
	 * Separate cached prices across public host protocol revisions.
	 *
	 * @param array $hash WooCommerce variation price context.
	 * @return array
	 */
	public static function price_cache_context( array $hash ): array {
		$hash[] = 'wholesalex_host_provider_api_1';
		return $hash;
	}

	/** Keep known older Pro integration callbacks out of the new host lifecycle. */
	public static function protect(): void {
		if ( ! function_exists( 'wholesalex_pro' ) || ( defined( 'WHOLESALEX_PRO_PROVIDER_API' ) && 1 === WHOLESALEX_PRO_PROVIDER_API ) ) {
			return;
		}
		$integration = '\\WHOLESALEX_PRO\\Compatibility\\Free_Integration';
		remove_action( 'wholesalex_register_extensions', array( $integration, 'register' ) );
		remove_action( 'plugins_loaded', array( $integration, 'register_legacy' ), 30 );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
	}

	/** Explain the paired update required without changing plugin activation. */
	public static function notice(): void {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Update WholesaleX Pro to a compatible release to restore its features. Your saved pricing records are preserved.', 'wholesalex' ) . '</p></div>';
		}
	}
}
