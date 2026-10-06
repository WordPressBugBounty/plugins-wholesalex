<?php
/**
 * Notice assets.
 *
 * @package WHOLESALEX\Notice
 */

namespace WHOLESALEX\Includes\Admin\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * The stylesheet and script shared by every notice. Queue them only when a notice will show.
 */
final class Notice_Assets {

	/**
	 * Handle of the stylesheet shared by every notice.
	 */
	const STYLESHEET = 'wsx-notice';

	/**
	 * Handle of the countdown script.
	 */
	const COUNTDOWN_SCRIPT = 'wsx-notice-countdown';

	/**
	 * Queue includes/admin/wsx-notice.css.
	 *
	 * @return void
	 */
	public static function enqueue_stylesheet() {
		wp_enqueue_style( self::STYLESHEET, WHOLESALEX_URL . 'includes/admin/wsx-notice.css', array(), WHOLESALEX_VER );
	}

	/**
	 * Queue the countdown script in the footer.
	 *
	 * @return void
	 */
	public static function enqueue_countdown_script() {
		wp_enqueue_script( self::COUNTDOWN_SCRIPT, WHOLESALEX_URL . 'includes/admin/wsx-notice-countdown.js', array( 'jquery' ), WHOLESALEX_VER, true );
	}
}
