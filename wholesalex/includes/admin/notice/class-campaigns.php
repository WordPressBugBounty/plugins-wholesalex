<?php
/**
 * Campaign registry.
 *
 * @package WHOLESALEX\Notice
 */

namespace WHOLESALEX\Includes\Admin\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * Every dated campaign notice WholesaleX can show. This is the only file to edit for a new sale.
 *
 * To add a campaign, copy an entry from the matching list and change it:
 * - Use a new `key` every time. Reusing an old key brings back a dismissal the user made last time.
 * - Write dates as 'YYYY-MM-DD HH:MM Asia/Dhaka'. Start at 00:00, end at 23:59.
 * - Only list the settings that differ from the defaults in the notice class
 *   (Banner_Notice, Content_Notice, Image_Notice).
 *
 * Past campaigns stay here as examples. They have ended, so they never display.
 *
 * When two campaigns are live at once they all show, in this order: banners, content notices, image banners.
 */
final class Campaigns {

	const FLASH_SALE_KEY   = 'wsx_helloBar_flash_sale_2026_october1';
	const FLASH_SALE_START = '2026-10-04 00:00:00 Asia/Dhaka';
	const FLASH_SALE_END   = '2026-10-16 23:59:59 Asia/Dhaka';
	const FLASH_SALE_URL   = 'https://getwholesalex.com/pricing/';
	const HALLOWEEN_KEY    = 'wsx_helloBar_halloween_2026';
	const HALLOWEEN_START  = '2026-10-27 00:00:00 Asia/Dhaka';
	const HALLOWEEN_END    = '2026-11-03 23:59:59 Asia/Dhaka';
	const HALLOWEEN_HELLOBAR_URL = 'https://getwholesalex.com/pricing/?utm_source=db-wholesalex&utm_medium=halloween-hellobar&utm_campaign=wholesalex-dashboard';
	const HALLOWEEN_PLUGIN_META_URL = 'https://getwholesalex.com/pricing/?utm_source=db-wholesalex&utm_medium=halloween-plugin-meta&utm_campaign=wholesalex-dashboard';

	/**
	 * All campaigns, in display order.
	 *
	 * @return Abstract_Campaign_Notice[]
	 */
	public static function all() {
		return array_merge( self::banners(), self::content_notices(), self::image_banners() );
	}

	/** Dashboard hello bars, using the same sale window and URL as admin notices. */
	public static function hellobars() {
		return array(
			array(
				'id'         => self::HALLOWEEN_KEY,
				'start'      => self::HALLOWEEN_START,
				'end'        => self::HALLOWEEN_END,
				'heading'    => __( 'Halloween Offer:', 'wholesalex' ),
				'text'       => __( 'Enjoy up to 60% Off on', 'wholesalex' ),
				'highlight'  => __( 'WholesaleX Pro', 'wholesalex' ),
				'buttonText' => __( 'Upgrade Now', 'wholesalex' ),
				'url'        => self::HALLOWEEN_HELLOBAR_URL,
			),
			array(
				'id'         => self::FLASH_SALE_KEY,
				'start'      => self::FLASH_SALE_START,
				'end'        => self::FLASH_SALE_END,
				'heading'    => __( 'Flash Sale:', 'wholesalex' ),
				'text'       => __( 'Enjoy up to 60% OFF on', 'wholesalex' ),
				'highlight'  => __( 'WholesaleX Pro', 'wholesalex' ),
				'buttonText' => __( 'Upgrade Now', 'wholesalex' ),
				'url'        => self::FLASH_SALE_URL,
			),
		);
	}

	/** Known ids accepted by the hello-bar dismissal endpoint. */
	public static function hellobar_keys() {
		return array_column( self::hellobars(), 'id' );
	}

	/** Return seasonal sidebar copy only during the Halloween campaign. */
	public static function sidebar_offer() {
		$now = time();
		if ( $now < strtotime( self::HALLOWEEN_START ) || $now > strtotime( self::HALLOWEEN_END ) ) {
			return null;
		}
		return array(
			'heading'    => __( 'WholesaleX Pro Halloween Sale - Up to 60% Off', 'wholesalex' ),
			'text'       => __( 'Upgrade to WholesaleX Pro and unlock all premium features at a special Halloween discount.', 'wholesalex' ),
			'benefits'   => array(
				__( 'Quantity Based Tiered Pricing', 'wholesalex' ),
				__( 'Custom Registration Fields', 'wholesalex' ),
				__( 'Order value and quantity restrictions', 'wholesalex' ),
				__( 'Bulk Order Form/Table', 'wholesalex' ),
				__( 'Request a Quote', 'wholesalex' ),
			),
			'buttonText' => __( 'Upgrade Now', 'wholesalex' ),
			'url'        => 'https://getwholesalex.com/pricing/?utm_source=db-wholesalex&utm_medium=halloween-sidebar&utm_campaign=wholesalex-dashboard',
		);
	}

	/**
	 * Sales for the "Upgrade to Pro" link on the Plugins page. The first live one replaces the link's
	 * text and destination; outside them it reads "Upgrade to Pro". Dates as for campaigns.
	 *
	 * @return array[]
	 */
	public static function plugin_link_offers() {
		return array(
			array(
				'start' => self::HALLOWEEN_START,
				'end'   => self::HALLOWEEN_END,
				'text'  => __( 'Upgrade to Pro - Up to 60% Off', 'wholesalex' ),
				'url'   => self::HALLOWEEN_PLUGIN_META_URL,
			),
			array(
				'start' => '2026-07-06 00:00 Asia/Dhaka',
				'end'   => '2026-08-01 23:59 Asia/Dhaka',
				'text'  => __( 'Summer Sale - Up to 50% OFF', 'wholesalex' ),
			),
			array(
				'start' => self::FLASH_SALE_START,
				'end'   => self::FLASH_SALE_END,
				'text'  => __( 'Up to 60% Off', 'wholesalex' ),
				'url'   => self::FLASH_SALE_URL,
			),
		);
	}

	/**
	 * Sales for the "Upgrade to Pro" item in the WholesaleX menu. The first live one replaces the item's
	 * text; outside them it reads "Upgrade to Pro". Dates as for campaigns.
	 *
	 * @return array[]
	 */
	public static function menu_offers() {
		return array(
			array(
				'start' => '2026-01-01 00:00 Asia/Dhaka',
				'end'   => '2026-02-15 23:59 Asia/Dhaka',
				'text'  => __( 'New Year Offer!', 'wholesalex' ),
			),
		);
	}

	/**
	 * Countdown banners. Images come from assets/img/dashboard_banner/{image_dir}/.
	 *
	 * @return Banner_Notice[]
	 */
	private static function banners() {
		return array(
			new Banner_Notice(
				array(
					'key'             => 'wsx_countdown_banner_sale_2026_summer_v1',
					'start'           => '2026-06-28 00:00 Asia/Dhaka',
					'end'             => '2026-06-30 23:59 Asia/Dhaka',
					'left_image'      => WHOLESALEX_URL . 'assets/img/banners/wholesalex_logo.svg',
					'right_image'     => WHOLESALEX_URL . 'assets/img/banners/discount.svg',
					'text'            => 'Hurry Before It Ends!',
					'brand_color'     => '#6c3bff',
					'countdown_color' => '#3CF357',
				)
			),
		);
	}

	/**
	 * Text and button notices.
	 *
	 * @return Content_Notice[]
	 */
	private static function content_notices() {
		return array(
			new Content_Notice(
				array(
					'key'                => self::FLASH_SALE_KEY,
					'start'              => self::FLASH_SALE_START,
					'end'                => self::FLASH_SALE_END,
					'url'                => self::FLASH_SALE_URL,
					'content_heading'    => __( 'Flash Sale:', 'wholesalex' ),
					/* translators: %s: discount percentage */
					'content_subheading' => __( 'Enjoy up to %s on WholesaleX Pro.', 'wholesalex' ),
					'discount_content'   => '  60% OFF',
					'icon'               => WHOLESALEX_URL . 'assets/img/banners/discount.svg',
					'is_discount_logo'   => true,
				)
			),
		);
	}

	/**
	 * Full-image banners.
	 *
	 * @return Image_Notice[]
	 */
	private static function image_banners() {
		return array(
			new Image_Notice(
				array(
					'key'        => 'wsx_image_banner_sale_2026_summer_v2',
					'start'      => '2026-08-09 00:00 Asia/Dhaka',
					'end'        => '2026-08-16 23:59 Asia/Dhaka',
					'banner_src' => WHOLESALEX_URL . 'assets/img/banners/wholesalex_discount.svg',
				)
			),
		);
	}
}
