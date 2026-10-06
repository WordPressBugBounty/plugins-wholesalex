<?php //phpcs:ignore
namespace WHOLESALEX\Includes\Admin\Notice;

use WHOLESALEX\Xpo;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/interface-notice-type.php';
require_once __DIR__ . '/class-notice-assets.php';
require_once __DIR__ . '/class-abstract-campaign-notice.php';
require_once __DIR__ . '/types/class-banner-notice.php';
require_once __DIR__ . '/types/class-content-notice.php';
require_once __DIR__ . '/types/class-image-notice.php';
require_once __DIR__ . '/class-promo-links.php';

/**
 * Plugin Notice
 */
class Notice {


	/**
	 * Campaigns showing on this request. Null until first worked out.
	 *
	 * @var Abstract_Campaign_Notice[]|null
	 */
	private $active_campaigns = null;

	/**
	 * Notice Priority
	 *
	 * @var int $plugin_notice_priority
	 */
	private $plugin_notice_priority = 30;

	/**
	 * Notice Priority
	 *
	 * @var string $plugin_notice_priority
	 */
	private $plugin_notice_priority_key = 'wholesalex';

	/**
	 * Notice Constructor
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices_callback' ) );
		add_action( 'admin_init', array( $this, 'set_dismiss_notice_callback' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );

		add_filter( 'xpo_active_notice_lists', array( $this, 'handle_xpo_active_notice_lists' ), 99, 1 );

		// Upgrade link on the Plugins page and upgrade item in the WholesaleX menu.
		new Promo_Links();
	}

	/**
	 * Registers REST API endpoints.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		if ( ! self::get_hellobar_keys() ) {
			return;
		}
		$routes = array(
			// Hello Bar.
			array(
				'endpoint'            => 'hello_bar',
				'methods'             => 'POST',
				'callback'            => array( $this, 'hello_bar_callback' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'type' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'hello_bar' ),
					),
					'id'   => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => self::get_hellobar_keys(),
					),
				),
			),
		);

		foreach ( $routes as $route ) {
			register_rest_route(
				'wsx/v1',
				$route['endpoint'],
				array(
					array(
						'methods'             => $route['methods'],
						'callback'            => $route['callback'],
						'permission_callback' => $route['permission_callback'],
						'args'                => isset( $route['args'] ) ? $route['args'] : array(),
					),
				)
			);
		}
	}

	/** Return the first eligible dashboard hello bar, or null. */
	public static function get_hellobar_config() {
		if ( ! current_user_can( 'manage_options' ) || ! apply_filters( 'wsx_show_upsell', apply_filters( 'wholesalex_show_upgrade_menu', true ) ) ) {
			return null;
		}
		try {
			require_once __DIR__ . '/class-campaigns.php';
			$now = time();
			foreach ( Campaigns::hellobars() as $promo ) {
				$start = strtotime( $promo['start'] );
				$end   = strtotime( $promo['end'] );
				if ( false === $start || false === $end || $now < $start || $now > $end || 'hide' === Xpo::get_transient_without_cache( $promo['id'] ) ) {
					continue;
				}
				return $promo;
			}
		} catch ( \Throwable $e ) {
			self::log_error( $e );
		}
		return null;
	}

	/**
	 * Hello bar ids the dashboard may dismiss, from Campaigns::hellobar_keys().
	 *
	 * class-campaigns.php is edited for every sale, so it is loaded inside a try: a mistake in it
	 * empties this list (hello bars can't be closed until it's fixed) instead of breaking wp-admin.
	 *
	 * @return string[]
	 */
	private static function get_hellobar_keys() {
		try {
			require_once __DIR__ . '/class-campaigns.php';
			$keys = Campaigns::hellobar_keys();
		} catch ( \Throwable $e ) {
			return array();
		}
		return is_array( $keys ) ? array_values( array_filter( $keys, 'is_string' ) ) : array();
	}

	/**
	 * Handles Hello Bar dismissal action via REST API .
	 *
	 * @param \WP_REST_Request $request REST request object .
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function hello_bar_callback( \WP_REST_Request $request ) {
		$type = $request->get_param( 'type' );
		$id   = $request->get_param( 'id' );

		// The route's args already reject unknown ids; check again in case this is called directly.
		if ( 'hello_bar' !== $type || ! is_string( $id ) || ! in_array( $id, self::get_hellobar_keys(), true ) ) {
			return new \WP_Error( 'wsx_invalid_hellobar', __( 'Unknown hello bar.', 'wholesalex' ), array( 'status' => 400 ) );
		}
		Xpo::set_transient_without_cache( $id, 'hide', 15 * DAY_IN_SECONDS );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Hello Bar Action performed', 'wholesalex' ),
			),
			200
		);
	}

	/**
	 * Set Notice Dismiss Callback
	 *
	 * @return void
	 */
	public function set_dismiss_notice_callback() {

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['wpnonce'] ?? '' ) ), 'wsx-nonce' ) ) {
			return;
		}

		$notice_key = sanitize_text_field( wp_unslash( $_GET['wsx_notice'] ?? '' ) );
		if ( '' === $notice_key ) {
			return;
		}

		try {
			require_once __DIR__ . '/class-campaigns.php';
			foreach ( Campaigns::all() as $campaign ) {
				if ( $campaign instanceof Abstract_Campaign_Notice && $campaign->get_key() === $notice_key ) {
					// Duration belongs to the campaign, never to the query string.
					Xpo::set_transient_without_cache( Abstract_Campaign_Notice::DISMISS_PREFIX . $notice_key, 'off', max( 0, (int) $campaign->get( 'repeat_interval' ) ) );
					$this->active_campaigns = null;
					break;
				}
			}
		} catch ( \Throwable $e ) {
			self::log_error( $e );
		}
	}

	/**
	 * Admin Notices Callback
	 *
	 * @return void
	 */
	public function admin_notices_callback() {
		$this->wsx_dashboard_notice_callback();
	}

	/**
	 * Admin Dashboard Notice Callback
	 *
	 * @return void
	 */
	public function wsx_dashboard_notice_callback() {
		if ( $this->is_available_for_notice() ) {
			$this->wsx_dashboard_banner_notice();
			$this->wsx_dashboard_content_notice();
			$this->wsx_dashboard_image_banner_notice();

		}
	}

	/**
	 * Dashboard Banner Notice
	 *
	 * @param bool $return_bool True to only report whether a banner would show.
	 * @return bool|void
	 */
	public function wsx_dashboard_banner_notice( $return_bool = false ) {
		return $this->render_campaigns( Banner_Notice::class, $return_bool );
	}

	/**
	 * Queue the stylesheets and scripts for the notices that will show on this page.
	 *
	 * Runs on admin_enqueue_scripts, before admin_notices, so the styles load in <head>.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( $this->is_available_for_notice() ) {
			foreach ( $this->get_active_campaigns() as $campaign ) {
				$campaign->enqueue_assets();
			}
		}
	}

	/**
	 * Banner JS
	 *
	 * @deprecated The countdown script is now the file includes/admin/wsx-notice-countdown.js. This queues it.
	 *
	 * @return void
	 */
	public function wsx_banner_notice_js() {
		Notice_Assets::enqueue_countdown_script();
	}

	/**
	 * Dashboard Content Notice
	 *
	 * @param bool $return_bool True to only report whether a content notice would show.
	 * @return bool|void
	 */
	public function wsx_dashboard_content_notice( $return_bool = false ) {
		return $this->render_campaigns( Content_Notice::class, $return_bool );
	}

	/**
	 * Handle Plugin Notice for all plugins
	 *
	 * @param array $active_lists Lists of all active plugin notice.
	 * @return array
	 */
	public function handle_xpo_active_notice_lists( $active_lists ) {
		if ( ! empty( $this->get_active_campaigns() ) ) {
			$active_lists[ $this->plugin_notice_priority_key ] = $this->plugin_notice_priority;
		}
		return $active_lists;
	}

	/**
	 * Handle Plugin Notice for all plugins
	 *
	 * @return bool
	 */
	public function is_available_for_notice() {
		$active_notices = apply_filters( 'xpo_active_notice_lists', array() );
		if ( empty( $active_notices ) ) {
			return true;
		}
		asort( $active_notices );
		return array_key_first( $active_notices ) === $this->plugin_notice_priority_key;
	}

	/**
	 * Dashboard Image Banner Notice
	 *
	 * @param bool $return_bool True to only report whether an image banner would show.
	 * @return bool|void
	 */
	public function wsx_dashboard_image_banner_notice( $return_bool = false ) {
		return $this->render_campaigns( Image_Notice::class, $return_bool );
	}

	/**
	 * Campaigns that should show on this request, in display order.
	 *
	 * Worked out once per request: the xpo_active_notice_lists filter and the render pass both need it,
	 * and each campaign's dismissal check is an uncached database read.
	 *
	 * @param string $type Optional class name to keep only one kind of campaign.
	 * @return Abstract_Campaign_Notice[]
	 */
	private function get_active_campaigns( $type = '' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return array();
		}

		// WholesaleX pages already show the dashboard hello bar.
		if ( wholesalex()->is_wholesalex_page( wholesalex()->get_current_admin_page_slug() ) ) {
			return array();
		}

		if ( null === $this->active_campaigns ) {
			$this->active_campaigns = array();

			// The registry is the file edited for every sale. Load it here, not at the top, so a
			// mistake in it (even a syntax error) hides the campaigns instead of breaking wp-admin.
			try {
				require_once __DIR__ . '/class-campaigns.php';
				$campaigns = Campaigns::all();
			} catch ( \Throwable $e ) {
				self::log_error( $e );
				$campaigns = array();
			}

			foreach ( $campaigns as $campaign ) {
				try {
					if ( $campaign instanceof Abstract_Campaign_Notice && $campaign->should_display() ) {
						$this->active_campaigns[] = $campaign;
					}
				} catch ( \Throwable $e ) {
					self::log_error( $e );
				}
			}
		}

		if ( '' === $type ) {
			return $this->active_campaigns;
		}
		return array_values(
			array_filter(
				$this->active_campaigns,
				function ( $campaign ) use ( $type ) {
					return $campaign instanceof $type;
				}
			)
		);
	}

	/**
	 * Print every live campaign of one kind, or report whether there is one.
	 *
	 * @param string $type        Campaign class name.
	 * @param bool   $return_bool True to only report whether one would show.
	 * @return bool|void
	 */
	private function render_campaigns( $type, $return_bool ) {
		$campaigns = $this->get_active_campaigns( $type );

		if ( $return_bool ) { // Early return for Other plugin notice.
			return ! empty( $campaigns );
		}

		foreach ( $campaigns as $campaign ) {
			$this->render_safely( $campaign );
		}
	}

	/**
	 * Print one notice. It's buffered, so a notice that fails halfway leaves no broken markup behind.
	 *
	 * @param Notice_Type $notice Notice to print.
	 * @return void
	 */
	private function render_safely( Notice_Type $notice ) {
		ob_start();
		try {
			$notice->render();
			ob_end_flush();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			self::log_error( $e );
		}
	}

	/**
	 * Record a notice failure when debugging. Notices are never worth breaking wp-admin for.
	 *
	 * @param \Throwable $e What went wrong.
	 * @return void
	 */
	private static function log_error( \Throwable $e ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WholesaleX notice: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-only.
		}
	}
}
