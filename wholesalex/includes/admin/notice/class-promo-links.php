<?php
/**
 * Campaign links integrated with WholesaleX's existing upgrade menu.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX\Includes\Admin\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * Add current campaign offers to the plugin's existing upgrade links.
 */
class Promo_Links {
	/** Register links without adding a duplicate submenu. */
	public function __construct() {
		add_filter( 'plugin_action_links_' . WHOLESALEX_BASE, array( $this, 'add_plugin_action_link' ) );
	}

	/**
	 * Add the upgrade or renewal link, respecting Pro's existing filters.
	 *
	 * @param string[] $links Existing plugin action links.
	 * @return string[]
	 */
	public function add_plugin_action_link( $links ) {
		if ( ! apply_filters( 'wholesalex_show_upgrade_menu', true ) ) {
			return $links;
		}
		$offer            = self::live_offer( 'plugin_link_offers' );
		$text             = apply_filters( 'wholesalex_upgrade_menu_label', $offer ? $offer['text'] : __( 'Upgrade to Pro', 'wholesalex' ) );
		$url              = apply_filters( 'wholesalex_upgrade_menu_url', ! empty( $offer['url'] ) ? $offer['url'] : 'https://getwholesalex.com/pricing/' );
		$links['wsx_pro'] = '<a style="color: #FF2400; font-weight: bold;" target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
		return $links;
	}

	/**
	 * Supply seasonal copy for the existing menu.
	 *
	 * @param string $label Existing upgrade menu label.
	 * @return string
	 */
	public static function menu_label( $label ) {
		$offer = self::live_offer( 'menu_offers' );
		return $offer ? $offer['text'] : $label;
	}

	/**
	 * Return the first current offer, safely ignoring malformed campaign data.
	 *
	 * @param string $method Campaign provider method name.
	 * @return array|null
	 */
	private static function live_offer( $method ) {
		try {
			require_once __DIR__ . '/class-campaigns.php';
			$now = time();
			foreach ( call_user_func( array( Campaigns::class, $method ) ) as $offer ) {
				if ( ! isset( $offer['start'], $offer['end'], $offer['text'] ) || ! is_string( $offer['start'] ) || ! is_string( $offer['end'] ) ) {
					continue;
				}
				$start = strtotime( $offer['start'] );
				$end   = strtotime( $offer['end'] );
				if ( false !== $start && false !== $end && $now >= $start && $now <= $end ) {
					return $offer;
				}
			}
		} catch ( \Throwable $e ) {
			return null;
		}
		return null;
	}
}
