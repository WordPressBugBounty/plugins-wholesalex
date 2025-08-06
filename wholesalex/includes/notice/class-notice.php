<?php //phpcs:ignore
namespace WHOLESALEX;


defined( 'ABSPATH' ) || exit;

use WHOLESALEX\DurbinClient;
use WHOLESALEX\Xpo;

/**
 * Plugin Notice
 */
class Notice {


	/**
	 * Notice version
	 *
	 * @var string
	 */
	private $notice_version = 'v2.1.1';

	/**
	 * Notice JS/CSS applied
	 *
	 * @var boolean
	 */
	private $notice_js_css_applied = false;


	/**
	 * Notice Constructor
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices_callback' ) );
		add_action( 'admin_init', array( $this, 'set_dismiss_notice_callback' ) );

		// REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );

		// Woocommerce Install Action.
		add_action( 'wp_ajax_wsx_install', array( $this, 'install_activate_plugin' ) );
	}


	/**
	 * Registers REST API endpoints.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		$routes = array(
			// Hello Bar.
			array(
				'endpoint'            => 'hello_bar',
				'methods'             => 'POST',
				'callback'            => array( $this, 'hello_bar_callback' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		);

		foreach ( $routes as $route ) {
			register_rest_route(
				'wsx',
				$route['endpoint'],
				array(
					array(
						'methods'             => $route['methods'],
						'callback'            => $route['callback'],
						'permission_callback' => $route['permission_callback'],
					),
				)
			);
		}
	}

	/**
	 * Handles Hello Bar dismissal action via REST API .
	 *
	 * @param \WP_REST_Request $request REST request object .
	 * @return \WP_REST_Response
	 */
	public function hello_bar_callback( \WP_REST_Request $request ) {
		$request_params = $request->get_params();
		$type           = isset( $request_params['type'] ) ? $request_params['type'] : '';
		$duration       = isset( $request_params['duration'] ) ? $request_params['duration'] : null;

		if ( 'hello_bar' === $type ) {
			Xpo::set_transient_without_cache( 'wsx_helloBar', 'hide', $duration );
		}

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

		// Durbin notice dismiss.
		if ( isset( $_GET['wsx_durbin_key'] ) && $_GET['wsx_durbin_key'] ) {
			$durbin_key = sanitize_text_field( $_GET['wsx_durbin_key'] );
			Xpo::set_transient_without_cache( 'wsx_durbin_notice_' . $durbin_key, 'off' );

			if ( isset( $_GET['wsx_get_durbin'] ) && 'get' === $_GET['wsx_get_durbin'] ) {
				DurbinClient::send( DurbinClient::ACTIVATE_ACTION );
			}
		}

		// Install notice dismiss.
		if ( isset( $_GET['wsx_install_key'] ) && $_GET['wsx_install_key'] ) {
			$install_key = sanitize_text_field( $_GET['wsx_install_key'] );
			Xpo::set_transient_without_cache( 'wsx_install_notice_' . $install_key, 'off' );
		}

		if ( isset( $_GET['disable_wsx_notice'] ) ) {
			$notice_key = sanitize_text_field( $_GET['disable_wsx_notice'] );
			if ( isset( $_GET['wsx_interval'] ) && '' != $_GET['wsx_interval'] ) {
				$interval = (int) $_GET['wsx_interval'];
				Xpo::set_transient_without_cache( 'wsx_get_pro_notice_' . $notice_key, 'off', $interval );
			} else {
				Xpo::set_transient_without_cache( 'wsx_get_pro_notice_' . $notice_key, 'off' );
			}
		}
	}

	/**
	 * Admin Notices Callback
	 *
	 * @return void
	 */
	public function admin_notices_callback() {
		$this->other_plugin_install_notice_callback( 'required' );
		$this->wsx_dashboard_notice_callback();
		$this->wsx_dashboard_durbin_notice_callback();
	}

	/**
	 * Admin Dashboard Notice Callback
	 *
	 * @return void
	 */
	public function wsx_dashboard_notice_callback() {
		$this->wsx_dashboard_content_notice();
		$this->wsx_dashboard_banner_notice();
	}

	/**
	 * Dashboard Banner Notice
	 *
	 * @return void
	 */
	public function wsx_dashboard_banner_notice() {
		$wsx_db_nonce   = wp_create_nonce( 'wsx-dashboard-nonce' );
		$banner_notices = array(
			array(
				'key'        => 'wsx_summer_sale_25',
				'start'      => '2025-06-23 00:00 Asia/Dhaka',
				'end'        => '2025-07-06 23:59 Asia/Dhaka',
				'banner_src' => WHOLESALEX_URL . 'assets/img/wholesale.png',
				'url'        => Xpo::generate_utm_link(
					array(
						'utmKey' => 'summer_db',
					)
				),
				'visibility' => ! Xpo::is_lc_active(),
			),
			array(
				'key'        => 'wsx_summer_sale_25_V2',
				'start'      => '2025-07-06 00:00 Asia/Dhaka',
				'end'        => '2025-07-09 23:59 Asia/Dhaka',
				'banner_src' => WHOLESALEX_URL . 'assets/img/wholesale_2.png',
				'url'        => Xpo::generate_utm_link(
					array(
						'utmKey' => 'summer_db',
					)
				),
				'visibility' => ! Xpo::is_lc_active(),
			),
		);

		foreach ( $banner_notices as $key => $notice ) {
			$notice_key = isset( $notice['key'] ) ? $notice['key'] : $this->notice_version;
			if ( isset( $_GET['disable_wsx_notice'] ) && $notice_key === $_GET['disable_wsx_notice'] ) {
				return;
			}

			$current_time = gmdate( 'U' );
			$notice_start = gmdate( 'U', strtotime( $notice['start'] ) );
			$notice_end   = gmdate( 'U', strtotime( $notice['end'] ) );
			if ( $current_time >= $notice_start && $current_time <= $notice_end && $notice['visibility'] ) {

				$notice_transient = Xpo::get_transient_without_cache( 'wsx_get_pro_notice_' . $notice_key );

				if ( 'off' !== $notice_transient ) {
					if ( ! $this->notice_js_css_applied ) {
						$this->wsx_banner_notice_css();
						$this->notice_js_css_applied = true;
					}
					$query_args = array(
						'disable_wsx_notice' => $notice_key,
						'wsx_db_nonce'       => $wsx_db_nonce,
					);
					if ( isset( $notice['repeat_interval'] ) && $notice['repeat_interval'] ) {
						$query_args['wsx_interval'] = $notice['repeat_interval'];
					}
					?>
					<div class="wsx-notice-wrapper notice wc-install wsx-free-notice">
						<div class="wc-install-body wsx-image-banner">
							<a class="wc-dismiss-notice" href="
							<?php
							echo esc_url(
								add_query_arg(
									$query_args
								)
							);
							?>
							"><?php esc_html_e( 'Dismiss', 'wholesalex' ); ?></a>
							<a class="wsx-btn-image" target="_blank" href="<?php echo esc_url( $notice['url'] ); ?>">
								<img loading="lazy" src="<?php echo esc_url( $notice['banner_src'] ); ?>" alt="Discount Banner"/>
							</a>
						</div>
					</div>
					<?php
				}
			}
		}
	}

	/**
	 * Dashboard Content Notice
	 *
	 * @return void
	 */
	public function wsx_dashboard_content_notice() {

		$content_notices = array(
			array(
				'key'                => 'wsx_dashboard_content_notice1',
				'start'              => '2025-08-04 00:00 Asia/Dhaka',
				'end'                => '2025-08-14 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'summer_db',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Final Hour Sales Alert:', 'wholesalex' ),
				'content_subheading' => __( 'WholesaleX on Sale – Get %s on this B2B Wholesale Store-building Solution!', 'wholesalex' ),
				'discount_content'   => 'up to 50% OFF',
				'border_color'       => '#FEAD01',
				'icon'               => WHOLESALEX_URL . 'assets/icons/logo_sm.svg',
				'button_text'        => __( 'Upgrade to Pro &nbsp;➣', 'wholesalex' ),
				'is_discount_logo'   => false,
				'background_color'   => '#FEAD01',
			),
			array(
				'key'                => 'wsx_dashboard_content_notice2',
				'start'              => '2025-08-18 00:00 Asia/Dhaka',
				'end'                => '2025-08-29 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'summer_db2',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Massive Sales Alert:', 'wholesalex' ),
				'content_subheading' => __( '<strong> WholesaleX </strong> on Sale - Enjoy %s on this B2B Wholesale Store-building Solution!', 'wholesalex' ),
				'discount_content'   => 'up to 55% OFF',
				'border_color'       => '#000000',
				'icon'               => WHOLESALEX_URL . 'assets/icons/55_orange.svg',
				'button_text'        => __( 'Claim Your Discount!', 'wholesalex' ),
				'is_discount_logo'   => true,
			),
			array(
				'key'                => 'wsx_dashboard_content_notice3',
				'start'              => '2025-09-01 00:00 Asia/Dhaka',
				'end'                => '2025-09-21 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'summer_db3',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Grab the Flash Sale Offer:', 'wholesalex' ),
				'content_subheading' => __( 'Sale on WholesaleX - Enjoy %s on the complete B2B Wholesale Solution!', 'wholesalex' ),
				'discount_content'   => 'up to 50% OFF',
				'border_color'       => '#6C6CFF',
				'icon'               => WHOLESALEX_URL . 'assets/icons/logo_sm.svg',
				'button_text'        => __( 'Upgrade to Pro &nbsp;➣', 'wholesalex' ),
				'is_discount_logo'   => false,
				'background_color'   => '#6C6CFF',
			),
			array(
				'key'                => 'wsx_dashboard_content_notice4',
				'start'              => '2025-09-21 00:00 Asia/Dhaka',
				'end'                => '2025-09-30 23:59 Asia/Dhaka',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'summer_db4',
					)
				),
				'visibility'         => ! Xpo::is_lc_active(),
				'content_heading'    => __( 'Exclusive Sale is Live:', 'wholesalex' ),
				'content_subheading' => __( 'Sale on <strong> WholesaleX </strong> - Enjoy %s on the complete B2B Wholesale Solution!', 'wholesalex' ),
				'discount_content'   => 'up to 55% OFF',
				'border_color'       => '#000000',
				'icon'               => WHOLESALEX_URL . 'assets/icons/55_green.svg',
				'button_text'        => __( 'Claim Your Discount!', 'wholesalex' ),
				'is_discount_logo'   => true,
			),
		);

		$wsx_db_nonce = wp_create_nonce( 'wsx-dashboard-nonce' );

		foreach ( $content_notices as $key => $notice ) {
			$notice_key = isset( $notice['key'] ) ? $notice['key'] : $this->notice_version;
			if ( isset( $_GET['disable_wsx_notice'] ) && $notice_key === $_GET['disable_wsx_notice'] ) {
				return;
			}
			$border_color = $notice['border_color'];

			$current_time = gmdate( 'U' );
			if ( $current_time > strtotime( $notice['start'] ) && $current_time < strtotime( $notice['end'] ) && $notice['visibility'] ) {

				$notice_transient = Xpo::get_transient_without_cache( 'wsx_get_pro_notice_' . $notice_key );

				if ( 'off' !== $notice_transient ) {
					if ( ! $this->notice_js_css_applied ) {
						$this->wsx_banner_notice_css();
						$this->notice_js_css_applied = true;
					}
					$query_args = array(
						'disable_wsx_notice' => $notice_key,
						'wsx_db_nonce'       => $wsx_db_nonce,
					);
					if ( isset( $notice['repeat_interval'] ) && $notice['repeat_interval'] ) {
						$query_args['wsx_interval'] = $notice['repeat_interval'];
					}

					$url = isset( $notice['url'] ) ? $notice['url'] : Xpo::generate_utm_link(
						array(
							'utmKey' => 'summer_db',
						)
					);

					?>
					<div class="wsx-notice-wrapper notice data_collection_notice" 
					style="border-left: 3px solid <?php echo esc_attr( $border_color ); ?>;"
					> 
						<?php
						if ( $notice['is_discount_logo'] ) {
							?>
								<div class="wsx-notice-discout-icon"> <img src="<?php echo esc_url( $notice['icon'] ); ?>"/>  </div>
							<?php
						} else {
							?>
								<div class="wsx-notice-icon"> <img src="<?php echo esc_url( $notice['icon'] ); ?>"/>  </div>
							<?php
						}
						?>
						
						<div class="wsx-notice-content-wrapper">
							<div class="">
								<strong><?php printf( esc_html( $notice['content_heading'] ) ); ?> </strong>
								<?php
								printf(
									wp_kses_post( $notice['content_subheading'] ),
									'<strong>' . esc_html( $notice['discount_content'] ) . '</strong>'
								);
								?>
							</div>
							<div class="wsx-notice-buttons">
								<?php if ( isset( $notice['is_discount_logo'] ) && $notice['is_discount_logo'] ) : ?>
									<a class="wsx-discount_btn" href="<?php echo esc_url( $url ); ?>" target="_blank">
										<?php echo esc_html( $notice['button_text'] ); ?>
									</a>
								<?php else : ?>
									<a class="wsx-notice-btn button button-primary" 
										href="<?php echo esc_url( $url ); ?>" 
										target="_blank" 
										style="background-color: <?php echo ! empty( $notice['background_color'] ) ? esc_attr( $notice['background_color'] ) : '#00A464'; ?>;">
											<?php echo esc_html( $notice['button_text'] ); ?>
										</a>
								<?php endif; ?>
							</div>
						</div>
						<a href=
						<?php
						echo esc_url(
							add_query_arg(
								$query_args
							)
						);
						?>
						class="wsx-notice-close"><span class="wsx-notice-close-icon dashicons dashicons-dismiss"> </span></a>
					</div>
					<?php
				}
			}
		}
	}

	/**
	 * Admin Banner CSS File
	 *
	 * @since v.1.0.7
	 * @param NULL
	 * @return STRING
	 */
	public function wsx_banner_notice_css() {
		?>
		<style id="wsx-notice-css" type="text/css">
			.wsx-notice-wrapper {
				border: 1px solid #c3c4c7;
				border-left: 3px solid #00a464;
				margin: 15px 0px !important;
				display: flex;
				align-items: center;
				/* background: #F7F9FF; */
				width: 100%;
				padding: 10px 0px;
				position: relative;
				box-sizing: border-box;
			}
			.wsx-notice-wrapper.notice, .wsx-free-notice.wc-install.notice {
				margin: 10px 0px;
				width: calc( 100% - 20px );
			}
			.wrap .wsx-notice-wrapper.notice, .wrap .wsx-free-notice.wc-install {
				width: 100%;
			}
			.wsx-notice-icon {
				margin-left: 15px;
				margin-right: 3px;
			}
			.wsx-notice-discout-icon {
				margin-left: 10px;
			}
			.wsx-notice-btn {
				font-weight: 700;
				text-transform: uppercase !important;
				padding: 3px 10px !important;
				/* background-color: #6C6CFF !important; */
				border: none !important;
			}
			.wsx-notice-icon img {
				max-width: 42px;
				/* width: 100%; */
				height: 70px;
			}
			.wsx-notice-discout-icon img {
				height: 70px;
				width: 70px;
			}
			.wsx-notice-content-wrapper {
				display: flex;
				flex-direction: column;
				gap: 8px;
				font-size: 14px;
				line-height: 20px;
				margin-left: 15px;
			}
			.wsx-notice-buttons {
				display: flex;
				align-items: center;
				gap: 15px;
			}
			.wsx-discount_btn {
				background-color: #ffffff;
				text-decoration: none;
				border: 1px solid #6C6CFF;
				padding: 5px 10px;
				border-radius: 5px;
				font-weight: 500;
				text-transform: uppercase;
				color: #6C6CFF !important;
			}
			.wsx-notice-dont-save-money {
				font-size: 12px;
			}
			.wsx-notice-close {
				position: absolute;
				right: 2px;
				top: 5px;
				text-decoration: unset;
				color: #b6b6b6;
				font-family: dashicons;
				font-size: 16px;
				font-style: normal;
				font-weight: 400;
				line-height: 20px;
			}
			.wsx-notice-close-icon {
				font-size: 14px;
			}
			.wsx-free-notice.wc-install {
				display: flex;
				align-items: center;
				background: #fff;
				margin-top: 20px;
				width: 100%;
				box-sizing: border-box;
				border: 1px solid #ccd0d4;
				padding: 4px;
				border-radius: 4px;
				border-left: 3px solid #00a464;
				line-height: 0;
			}   
			.wsx-free-notice.wc-install img {
				margin-right: 0; 
				max-width: 100%;
			}
			.wsx-free-notice .wc-install-body {
				-ms-flex: 1;
				flex: 1;
				position: relative;
				padding: 10px;
			}
			.wsx-free-notice .wc-install-body.wsx-image-banner{
				padding: 0px;
			}
			.wsx-free-notice .wc-install-body h3 {
				margin-top: 0;
				font-size: 24px;
				margin-bottom: 15px;
			}
			.wsx-install-btn {
				margin-top: 15px;
				display: inline-block;
			}
			.wsx-free-notice .wc-install .dashicons{
				display: none;
				animation: dashicons-spin 1s infinite;
				animation-timing-function: linear;
			}
			.wsx-free-notice.wc-install.loading .dashicons {
				display: inline-block;
				margin-top: 12px;
				margin-right: 5px;
			}
			.wsx-free-notice .wc-install-body h3 {
				font-size: 20px;
				margin-bottom: 5px;
			}
			.wsx-free-notice .wc-install-body > div {
				max-width: 100%;
				margin-bottom: 10px;
			}
			.wsx-free-notice .button-hero {
				padding: 8px 14px !important;
				min-height: inherit !important;
				line-height: 1 !important;
				box-shadow: none;
				border: none;
				transition: 400ms;
			}
			.wsx-free-notice .wsx-btn-notice-pro {
				background: #2271b1;
				color: #fff;
			}
			.wsx-free-notice .wsx-btn-notice-pro:hover,
			.wsx-free-notice .wsx-btn-notice-pro:focus {
				background: #185a8f;
			}
			.wsx-free-notice .button-hero:hover,
			.wsx-free-notice .button-hero:focus {
				border: none;
				box-shadow: none;
			}
			@keyframes dashicons-spin {
				0% {
					transform: rotate( 0deg );
				}
				100% {
					transform: rotate( 360deg );
				}
			}
			.wsx-free-notice .wc-dismiss-notice {
				color: #fff;
				background-color: #000000;
				padding-top: 0px;
				position: absolute;
				right: 0;
				top: 0px;
				padding: 10px 10px 14px;
				border-radius: 0 0 0 4px;
				display: inline-block;
				transition: 400ms;
			}
			.wsx-free-notice .wc-dismiss-notice:hover {
				color:red;
			}
			.wsx-free-notice .wc-dismiss-notice .dashicons{
				display: inline-block;
				text-decoration: none;
				animation: none;
				font-size: 16px;
			}
			/* ===== Eid Banner Css ===== */
			.wsx-free-notice .wc-install-body {
				background: linear-gradient(90deg,rgb(0,110,188) 0%,rgb(2,17,196) 100%);
			}
			.wsx-free-notice p{
				color: #fff;
				margin: 5px 0px;
				font-size: 16px;
				font-weight: 300;
				letter-spacing: 1px;
			}
			.wsx-free-notice p.wsx-enjoy-offer {
				display: inline;
				font-weight: bold;
				
			}
			.wsx-free-notice .wsx-get-now {
				font-size: 14px;
				color: #fff;
				background: #14a8ff;
				padding: 8px 12px;
				border-radius: 4px;
				text-decoration: none;
				margin-left: 10px;
				position: relative;
				top: -4px;
				transition: 400ms;
			}
			.wsx-free-notice .wsx-get-now:hover{
				background: #068fe0;
			}
			.wsx-free-notice .wsx-dismiss {
				color: #fff;
				background-color: #000964;
				padding-top: 0px;
				position: absolute;
				right: 0;
				top: 0px;
				padding: 10px 8px 12px;
				border-radius: 0 0 0 4px;
				display: inline-block;
				transition: 400ms;
			}
			.wsx-free-notice .wsx-dismiss:hover {
				color: #d2d2d2;
			}
			/*----- wsx Into Notice ------*/
			.notice.notice-success.wsx-notice {
				border-left-color: #4D4DFF;
				padding: 0;
			}
			.wsx-notice-container {
				display: flex;
			}
			.wsx-notice-container a{
				text-decoration: none;
			}
			.wsx-notice-container a:visited{
				color: white;
			}
			.wsx-notice-container img {
				height: 100px; 
				width: 100px;
			}
			.wsx-notice-image {
				padding-top: 15px;
				padding-left: 12px;
				padding-right: 12px;
				background-color: #f4f4ff;
			}
			.wsx-notice-image img{
				max-width: 100%;
			}
			.wsx-notice-content {
				width: 100%;
				padding: 16px;
				display: flex;
				flex-direction: column;
				gap: 8px;
			}
			.wsx-notice-wsx-button {
				max-width: fit-content;
				padding: 8px 15px;
				font-size: 16px;
				color: white;
				background-color: #4D4DFF;
				border: none;
				border-radius: 2px;
				cursor: pointer;
				margin-top: 6px;
				text-decoration: none;
			}
			.wsx-notice-heading {
				font-size: 18px;
				font-weight: 500;
				color: #1b2023;
			}
			.wsx-notice-content-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			.wsx-notice-close .dashicons-no-alt {
				font-size: 25px;
				height: 26px;
				width: 25px;
				cursor: pointer;
				color: #585858;
			}
			.wsx-notice-close .dashicons-no-alt:hover {
				color: red;
			}
			.wsx-notice-content-body {
				font-size: 14px;
				color: #343b40;
			}
			.wsx-notice-wholesalex-button:hover {
				background-color: #6C6CFF;
				color: white;
			}
			span.wsx-bold {
				font-weight: bold;
			}
			a.wsx-pro-dismiss:focus {
				outline: none;
				box-shadow: unset;
			}
			.wsx-free-notice .loading, .wsx-notice .loading {
				width: 16px;
				height: 16px;
				border: 3px solid #FFF;
				border-bottom-color: transparent;
				border-radius: 50%;
				display: inline-block;
				box-sizing: border-box;
				animation: rotation 1s linear infinite;
				margin-left: 10px;
			}
			a.wsx-notice-wsx-button:hover {
				color: #fff !important;
			}
			@keyframes rotation {
				0% {
					transform: rotate(0deg);
				}
				100% {
					transform: rotate(360deg);
				}
			}
		</style>
		<?php
	}

	/**
	 * The Durbin Html
	 *
	 * @return STRING | HTML
	 */
	public function wsx_dashboard_durbin_notice_callback() {
		$durbin_key = 'wsx_durbin_dc1';
		if (
			isset( $_GET['wsx_durbin_key'] ) ||
			'off' === Xpo::get_transient_without_cache( 'wsx_durbin_notice_' . $durbin_key ) ||
			defined( 'WHOLESALEX_PRO_VER' )
		) {
			return;
		}
		if ( ! $this->notice_js_css_applied ) {
			$this->wsx_banner_notice_css();
			$this->notice_js_css_applied = true;
		}
		?>
		<style>
				.wsx-consent-box {
					width: 656px;
					padding: 16px;
					border: 1px solid #070707;
					border-left-width: 4px;
					border-radius: 4px;
					background-color: #fff;
					position: relative;
				}
				.wsx-consent-content {
					display: flex;
					justify-content: space-between;
					align-items: flex-end;
					gap: 26px;
				}
 
				.wsx-consent-text-first {
					font-size: 14px;
					font-weight: 600;
					color: #070707;
				}
				.wsx-consent-text-last {
					margin: 4px 0 0;
					font-size: 14px;
					color: #070707;
				}
 
				.wsx-consent-accept {
					background-color: #070707;
					color: #fff;
					border: none;
					padding: 6px 10px;
					border-radius: 4px;
					cursor: pointer;
					font-size: 12px;
					font-weight: 600;
					text-decoration: none;
				}
				.wsx-consent-accept:hover {
					background-color:rgb(38, 38, 38);
					color: #fff;
				}
			</style>
			<div class="wsx-consent-box wsx-notice-wrapper notice data_collection_notice">
			<div class="wsx-consent-content">
			<div class="wsx-consent-text">
			<div class="wsx-consent-text-first"><?php esc_html_e( 'Want to help make Wholesalex even more awesome?', 'wholesalex' ); ?></div>
			<div class="wsx-consent-text-last">
					<?php esc_html_e( 'Allow us to collect diagnostic data and usage information. see ', 'wholesalex' ); ?>
			<a href="https://www.wpxpo.com/data-collection-policy/" target="_blank" ><?php esc_html_e( 'what we collect.', 'wholesalex' ); ?></a>
			</div>
			</div>
			<a
									class="wsx-consent-accept"
									href=
					<?php
									echo esc_url(
										add_query_arg(
											array(
												'wsx_durbin_key' => $durbin_key,
												'wsx_get_durbin'  => 'get',
											)
										)
									);
					?>
									class="wsx-notice-close"
			><?php esc_html_e( 'Accept & Close', 'wholesalex' ); ?></a>
			</div>
			<a href=
					<?php
								echo esc_url(
									add_query_arg(
										array(
											'wsx_durbin_key' => $durbin_key,
										)
									)
								);
					?>
								class="wsx-notice-close"
			>
				<span class="wsx-notice-close-icon dashicons dashicons-dismiss"> </span></a>
			</div>
		<?php
	}



	/**
	 * Woocommerce Notice HTML
	 *
	 * @since v.1.0.0
	 * @return STRING | HTML
	 */
	public function other_plugin_install_notice_callback( $type = '' ) {
		$install_key_tran = 'woocommerce';
		$plugin_slug      = 'woocommerce';
		if ( 'required' !== $type ) {
			if ( isset( $_GET['wsx_install_key'] ) ||
				'off' === Xpo::get_transient_without_cache( 'wsx_install_notice_' . $install_key_tran, )
			) {
				return;
			}
		}

		if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
			return;
		}

		$this->install_notice_css();
		$this->install_notice_js();
		?>
			<div class="wsx-pro-notice wsx-wc-install wc-install">
				<img width="100" src="<?php echo esc_url( WHOLESALEX_URL . 'assets/img/woocommerce.png' ); ?>" alt="logo" />
				<div class="wsx-install-body">
					<h3><?php esc_html_e( 'Welcome to Wholesalex.', 'wholesalex' ); ?></h3>
					<p><?php esc_html_e( 'Wholesalex is a WooCommerce-based plugin. So you need to installed & activate WooCommerce to start using Wholesalex.', 'wholesalex' ); ?></p>
					<div class="wsx-install-btn-wrap">
						<a class="wc-install-btn wsx-install-btn button button-primary" data-plugin-slug="<?php echo esc_attr( $plugin_slug ); ?>" href="#"><span class="dashicons dashicons-image-rotate"></span><?php file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ? esc_html_e( ' Activate WooCommerce', 'wholesalex' ) : esc_html_e( ' Install WooCommerce', 'wholesalex' ); ?></a>
						<?php if ( 'required' !== $type ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'wsx_install_key' => $install_key_tran ) ) ); ?>" class="wsx-install-cancel wc-dismiss-notice">
								<?php esc_html_e( 'Discard', 'wholesalex' ); ?>
							</a>
						<?php endif; ?>
					</div>
					<div id="installation-msg"></div>
				</div>
			</div>
		<?php
	}

	/**
	 * Plugin Install and Active Action
	 *
	 * @since v.1.6.8
	 * @return STRING | Redirect URL
	 */
	public function install_activate_plugin() {
		if ( ! isset( $_POST['install_plugin'] ) ) {
			return wp_send_json_error( esc_html__( 'Invalid request.', 'wholesalex' ) );
		}
		$plugin_slug = sanitize_text_field( wp_unslash( $_POST['install_plugin'] ) );

		Xpo::install_and_active_plugin( $plugin_slug );

		if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) || isset( $_POST['action'] ) && 'activate-selected' == sanitize_text_field( $_POST['action'] ) ) { //phpcs:ignore
			return;
		}

		return wp_send_json_success( admin_url( 'admin.php?page=wsx-dashboard#dashboard' ) );
	}

	/**
	 * Installation Notice CSS
	 *
	 * @since v.1.0.0
	 */
	public function install_notice_css() {
		?>
		<style type="text/css">
			.wsx-wc-install {
				display: flex;
				align-items: center;
				background: #fff;
				margin-top: 30px !important;
				/*width: calc(100% - 65px);*/
				border: 1px solid #ccd0d4;
				padding: 4px !important;
				border-radius: 4px;
				border-left: 3px solid #46b450;
				line-height: 0;
				gap: 15px;
				padding: 15px 10px !important;
			}
			.wsx-wc-install img {
				width: 100px;
			}
			.wsx-install-body {
				-ms-flex: 1;
				flex: 1;
			}
			.wsx-install-body.wsx-image-banner {
				padding: 0px !important;
			}
			.wsx-install-body.wsx-image-banner img {
				width: 100%;
			}
			.wsx-install-body>div {
				max-width: 450px;
				margin-bottom: 20px !important;
			}
			.wsx-install-body h3 {
				margin: 0 !important;
				font-size: 20px;
				margin-bottom: 10px !important;
				line-height: 1;
			}
			.wsx-pro-notice .wc-install-btn,
			.wp-core-ui .wsx-wc-active-btn {
				display: inline-flex;
				align-items: center;
				padding: 3px 20px !important;
			}
			.wsx-pro-notice.loading .wc-install-btn {
				opacity: 0.7;
				pointer-events: none;
			}
			.wsx-wc-install.wc-install .dashicons {
				display: none;
				animation: dashicons-spin 1s infinite;
				animation-timing-function: linear;
			}
			.wsx-wc-install.wc-install.loading .dashicons {
				display: inline-block;
				margin-right: 5px !important;
			}
			@keyframes dashicons-spin {
				0% {
					transform: rotate(0deg);
				}
				100% {
					transform: rotate(360deg);
				}
			}
			.wsx-wc-install .wc-dismiss-notice {
				position: relative;
				text-decoration: none;
				float: right;
				right: 5px;
				display: flex;
				align-items: center;
			}
			.wsx-wc-install .wc-dismiss-notice .dashicons {
				display: flex;
				text-decoration: none;
				animation: none;
				align-items: center;
			}
			.wsx-pro-notice {
				position: relative;
				border-left: 3px solid #00a464;
			}
			.wsx-pro-notice .wsx-install-body h3 {
				font-size: 20px;
				margin-bottom: 5px !important;
			}
			.wsx-pro-notice .wsx-install-body>div {
				max-width: 800px;
				margin-bottom: 0 !important;
			}
			.wsx-pro-notice .button-hero {
				padding: 8px 14px !important;
				min-height: inherit !important;
				line-height: 1 !important;
				box-shadow: none;
				border: none;
				transition: 400ms;
				background: #46b450;
			}
			.wsx-pro-notice .button-hero:hover,
			.wp-core-ui .wsx-pro-notice .button-hero:active {
				background: #389e41;
			}
			.wsx-pro-notice .wsx-btn-notice-pro {
				background: #e5561e;
				color: #fff;
			}
			.wsx-pro-notice .wsx-btn-notice-pro:hover,
			.wsx-pro-notice .wsx-btn-notice-pro:focus {
				background: #ce4b18;
			}
			.wsx-pro-notice .button-hero:hover,
			.wsx-pro-notice .button-hero:focus {
				border: none;
				box-shadow: none;
			}
			.wsx-pro-notice .wsx-promotional-dismiss-notice {
				background-color: #000000;
				padding-top: 0px !important;
				position: absolute;
				right: 0;
				top: 0px;
				padding: 10px 10px 14px !important;
				border-radius: 0 0 0 4px;
				border: 1px solid;
				display: inline-block;
				color: #fff;
			}
			.wsx-eid-notice p {
				margin: 0 !important;
				color: #f7f7f7;
				font-size: 16px;
			}
			.wsx-eid-notice p.wsx-eid-offer {
				color: #fff;
				font-weight: 700;
				font-size: 18px;
			}
			.wsx-eid-notice p.wsx-eid-offer a {
				background-color: #ffc160;
				padding: 8px 12px !important;
				border-radius: 4px;
				color: #000;
				font-size: 14px;
				margin-left: 3px !important;
				text-decoration: none;
				font-weight: 500;
				position: relative;
				top: -4px;
			}
			.wsx-eid-notice p.wsx-eid-offer a:hover {
				background-color: #edaa42;
			}
			.wsx-install-body .wsx-promotional-dismiss-notice {
				right: 4px;
				top: 3px;
				border-radius: unset !important;
				padding: 10px 8px 12px !important;
				text-decoration: none;
			}
			.wsx-notice {
				background: #fff;
				border: 1px solid #c3c4c7;
				border-left-color: #00a464 !important;
				border-left-width: 4px;
				border-radius: 4px 0px 0px 4px;
				box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
				padding: 0px !important;
				margin: 40px 20px 0 2px !important;
				clear: both;
			}
			.wsx-notice .wsx-notice-container {
				display: flex;
				width: 100%;
			}
			.wsx-notice .wsx-notice-container a {
				text-decoration: none;
			}
			.wsx-notice .wsx-notice-container a:visited {
				color: white;
			}
			.wsx-notice .wsx-notice-container img {
				width: 100%;
				max-width: 30px !important;
				padding: 12px !important;
			}
			.wsx-notice .wsx-notice-image {
				display: flex;
				align-items: center;
				flex-direction: column;
				justify-content: center;
				background-color: #f4f4ff;
			}
			.wsx-notice .wsx-notice-image img {
				max-width: 100%;
			}
			.wsx-notice .wsx-notice-content {
				width: 100%;
				margin: 5px !important;
				padding: 8px !important;
				display: flex;
				flex-direction: column;
				gap: 0px;
			}
			.wsx-notice .wsx-notice-wsx-button {
				max-width: fit-content;
				text-decoration: none;
				padding: 7px 12px !important;
				font-size: 12px;
				color: white;
				border: none;
				border-radius: 2px;
				cursor: pointer;
				margin-top: 6px !important;
				background-color: #e5561e;
			}
			.wsx-notice-heading {
				font-size: 18px;
				font-weight: 500;
				color: #1b2023;
			}
			.wsx-notice-content-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			.wsx-notice-close .dashicons-no-alt {
				font-size: 25px;
				height: 26px;
				width: 25px;
				cursor: pointer;
				color: #585858;
			}
			.wsx-notice-close .dashicons-no-alt:hover {
				color: red;
			}
			.wsx-notice-content-body {
				font-size: 12px;
				color: #343b40;
			}
			.wsx-bold {
				font-weight: bold;
			}
			a.wsx-pro-dismiss:focus {
				outline: none;
				box-shadow: unset;
			}
			.wsx-free-notice .loading,
			.wsx-notice .loading {
				width: 16px;
				height: 16px;
				border: 3px solid #FFF;
				border-bottom-color: transparent;
				border-radius: 50%;
				display: inline-block;
				box-sizing: border-box;
				animation: rotation 1s linear infinite;
				margin-left: 10px !important;
			}
			a.wsx-notice-wsx-button:hover {
				color: #fff !important;
			}
			.wsx-notice .wsx-link-wrap {
				margin-top: 10px !important;
			}
			.wsx-notice .wsx-link-wrap a {
				margin-right: 4px !important;
			}
			.wsx-notice .wsx-link-wrap a:hover {
				background-color: #ce4b18;
			}
			body .wsx-notice .wsx-link-wrap>a.wsx-notice-skip {
				background: none !important;
				border: 1px solid #e5561e;
				color: #e5561e;
				padding: 6px 15px !important;
			}
			body .wsx-notice .wsx-link-wrap>a.wsx-notice-skip:hover {
				background: #ce4b18 !important;
			}
			@keyframes rotation {
				0% {
					transform: rotate(0deg);
				}
				100% {
					transform: rotate(360deg);
				}
			}

			.wsx-install-btn-wrap {
				display: flex;
				align-items: stretch;
				gap: 10px;
			}
			.wsx-install-btn-wrap .wsx-install-cancel {
				position: static !important;
				padding: 3px 20px;
				border: 1px solid #a0a0a0;
				border-radius: 2px;
			}
		</style>
		<?php
	}

	/**
	 * Installation Notice JS
	 *
	 * @since v.1.0.0
	 */
	public function install_notice_js() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				'use strict';
				$(document).on('click', '.wc-install-btn.wsx-install-btn', function(e) {
					e.preventDefault();
					const $that = $(this);
					console.log($that.attr('data-plugin-slug'));
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {
							install_plugin: $that.attr('data-plugin-slug'),
							action: 'wsx_install'
						},
						beforeSend: function() {
							$that.parents('.wc-install').addClass('loading');
						},
						success: function(response) {
							window.location.reload()
						},
						complete: function() {
							// $that.parents('.wc-install').removeClass('loading');
						}
					});
				});
			});
		</script>
		<?php
	}
}
