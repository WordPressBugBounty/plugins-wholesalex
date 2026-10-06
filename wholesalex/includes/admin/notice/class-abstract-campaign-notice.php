<?php
/**
 * Base class for dated campaign notices.
 *
 * @package WHOLESALEX\Notice
 */

namespace WHOLESALEX\Includes\Admin\Notice;

use WHOLESALEX\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * A notice that shows between two dates until the user dismisses it.
 *
 * Every rule about *when* a campaign shows lives here. A subclass sets VIEW, adds its own
 * settings in get_defaults(), and ships views/{VIEW}.php for the markup. Styles for every
 * notice live in includes/admin/wsx-notice.css.
 */
abstract class Abstract_Campaign_Notice implements Notice_Type {

	/**
	 * Transient prefix that marks a campaign as dismissed. Don't change it: past dismissals are stored under it.
	 */
	const DISMISS_PREFIX = 'wsx_get_pro_notice_';

	/**
	 * Names the template in views/. Set by each subclass.
	 */
	const VIEW = '';

	/**
	 * Campaign settings, merged over get_defaults().
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Constructor.
	 *
	 * @param array $args Campaign settings. `key`, `start` and `end` are required.
	 */
	public function __construct( array $args ) {
		$this->args = wp_parse_args( $args, $this->get_defaults() );
	}

	/**
	 * Settings every campaign accepts. Subclasses merge their own on top.
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array(
			'key'             => '',
			'start'           => '',   // e.g. '2026-05-18 00:00 Asia/Dhaka'.
			'end'             => '',   // e.g. '2026-05-21 23:59 Asia/Dhaka'.
			'url'             => '',   // Full link. Leave empty to build one from `utm_key`.
			'utm_key'         => '',   // Key passed to Xpo::generate_utm_link().
			'visibility'      => null, // null shows the notice only while no Pro licence is active.
			'repeat_interval' => 0,    // Seconds before a dismissed notice may return. 0 hides it for good.
		);
	}

	/**
	 * Read one setting.
	 *
	 * @param string $name Setting name.
	 * @return mixed Null when the setting doesn't exist.
	 */
	public function get( $name ) {
		return isset( $this->args[ $name ] ) ? $this->args[ $name ] : null;
	}

	/**
	 * Unique campaign key.
	 *
	 * @return string
	 */
	public function get_key() {
		return (string) $this->args['key'];
	}

	/**
	 * Whether the notice should show on this request.
	 *
	 * Cheap checks run first; the dismissal check reads the database.
	 *
	 * @return bool
	 */
	public function should_display() {
		if ( '' === $this->get_key() || $this->is_hidden_by_request() || ! $this->is_running() || ! $this->is_visible() ) {
			return false;
		}
		return ! $this->is_dismissed();
	}

	/**
	 * Whether the current time falls inside the campaign window.
	 *
	 * @return bool
	 */
	public function is_running() {
		$now = time();
		$start = is_string( $this->args['start'] ) ? strtotime( $this->args['start'] ) : false;
		$end   = is_string( $this->args['end'] ) ? strtotime( $this->args['end'] ) : false;
		if ( false === $start || false === $end ) {
			return false; // Unreadable date: never show rather than guess.
		}
		return $now >= $start && $now <= $end;
	}

	/**
	 * Whether this campaign is meant for the current site.
	 *
	 * @return bool
	 */
	protected function is_visible() {
		if ( null === $this->args['visibility'] ) {
			return (bool) apply_filters( 'wsx_show_upsell', apply_filters( 'wholesalex_show_upgrade_menu', true ) ); // Pro returns false while the licence is valid.
		}
		return (bool) $this->args['visibility'];
	}

	/**
	 * Whether the user has dismissed this campaign.
	 *
	 * @return bool
	 */
	protected function is_dismissed() {
		return 'off' === Xpo::get_transient_without_cache( self::DISMISS_PREFIX . $this->get_key() );
	}

	/**
	 * Hide the notice on the page load that follows clicking its close link.
	 *
	 * @return bool
	 */
	protected function is_hidden_by_request() {
		return isset( $_GET['wsx_notice'] ) && $this->get_key() === sanitize_text_field( wp_unslash( $_GET['wsx_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; hides the notice on this page view.
	}

	/**
	 * Link that dismisses the notice. Handled by Notice::set_dismiss_notice_callback().
	 *
	 * @return string Unescaped URL.
	 */
	public function get_dismiss_url() {
		$query_args = array(
			'wsx_notice' => $this->get_key(),
			'wpnonce'     => wp_create_nonce( 'wsx-nonce' ),
		);
		return add_query_arg( $query_args );
	}

	/**
	 * Where the campaign links to.
	 *
	 * @return string Unescaped URL.
	 */
	public function get_url() {
		if ( ! empty( $this->args['url'] ) ) {
			return $this->args['url'];
		}
		return Xpo::generate_utm_link( array( 'utmKey' => $this->args['utm_key'] ) );
	}

	/**
	 * Whether this notice needs the countdown script.
	 *
	 * @return bool
	 */
	public function uses_countdown_script() {
		return false;
	}

	/**
	 * Queue the notice stylesheet, and the countdown script when this notice needs one.
	 *
	 * Notice calls this on admin_enqueue_scripts so the styles land in <head>. render() calls it again
	 * in case the notice is printed some other way; WordPress then prints the late styles in the footer.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		Notice_Assets::enqueue_stylesheet();
		if ( $this->uses_countdown_script() ) {
			Notice_Assets::enqueue_countdown_script();
		}
	}


	/**
	 * Print the notice. The template in views/ reads this object as `$notice`.
	 *
	 * @return void
	 */
	public function render() {
		$view = __DIR__ . '/views/' . static::VIEW . '.php';
		if ( '' === static::VIEW || ! is_readable( $view ) ) {
			return;
		}
		$this->enqueue_assets();
		$notice = $this; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Used by the template.
		include $view;
	}
}
