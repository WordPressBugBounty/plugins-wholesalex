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
	private $notice_version = 'v102';

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
				'wsx/v1',
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
	 * Hellobar config
	 *
	 * @return array
	 */
	public static function get_hellobar_config() {
		return array(
			'wsx_helloBar_flash_sale_2026_1'      => Xpo::get_transient_without_cache( 'wsx_helloBar_flash_sale_2026_1' ),
			'wsx_helloBar_final_hour_sale_2026_1' => Xpo::get_transient_without_cache( 'wsx_helloBar_final_hour_sale_2026_1' ),
		);
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
		$id             = isset( $request_params['id'] ) ? $request_params['id'] : '';

		if ( 'hello_bar' === $type && ! empty( $id ) ) {
			Xpo::set_transient_without_cache( $id, 'hide', 1296000 );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Hello Bar Action performed', 'wow-table-rate-shipping' ),
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

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['wpnonce'] ?? '' ) ), 'wsx-nonce' ) ) {
			return;
		}

		$durbin_key = sanitize_text_field( wp_unslash( $_GET['wsx_durbin_key'] ?? '' ) );

		// Durbin notice dismiss.
		if ( ! empty( $durbin_key ) ) {
			Xpo::set_transient_without_cache( 'wsx_durbin_notice_' . $durbin_key, 'off' );

			if ( 'get' === sanitize_text_field( wp_unslash( $_GET['wsx_get_durbin'] ?? '' ) ) ) {
				DurbinClient::send( DurbinClient::ACTIVATE_ACTION );
			}
		}

		// Install notice dismiss.
		$install_key = sanitize_text_field( wp_unslash( $_GET['wsx_install_key'] ?? '' ) );
		if ( ! empty( $install_key ) ) {
			Xpo::set_transient_without_cache( 'wsx_install_notice_' . $install_key, 'off' );
		}

		$notice_key = sanitize_text_field( wp_unslash( $_GET['disable_wsx_notice'] ?? '' ) );
		if ( ! empty( $notice_key ) ) {
			$interval = (int) sanitize_text_field( wp_unslash( $_GET['wsx_interval'] ?? '' ) );
			if ( ! empty( $interval ) ) {
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
		$this->wsx_dashboard_notice_callback();
		$this->wsx_dashboard_durbin_notice_callback();
	}

	/**
	 * Admin Dashboard Notice Callback
	 *
	 * @return void
	 */
	public function wsx_dashboard_notice_callback() {
		$this->wsx_dashboard_banner_notice();
	}

	/**
	 * Dashboard Banner Notice
	 *
	 * @return void
	 */
	public function wsx_dashboard_banner_notice() {
		$wsx_db_nonce  = wp_create_nonce( 'wsx-nonce' );
		$banner_notices = array(
			array(
				'key'                => 'wsx_flash_sale_2026_1',
				'start'              => '2026-02-19 00:00 Asia/Dhaka',
				'end'                => '2026-02-23 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#6c6cff',

				'left_image'         => WHOLESALEX_URL . '/assets/img/banners/flash_sale/left_image.png',
				'right_image'        => WHOLESALEX_URL . '/assets/img/banners/flash_sale/right_image.png',
				'bg_image'           => WHOLESALEX_URL . '/assets/img/banners/flash_sale/bg.png',
				'text'               => 'Hurry Before It Ends!',
				'countdown_duration' => 259200, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'flash_sale',
					)
				),

				'visibility'         => ! Xpo::is_lc_active(),
			),
			array(
				'key'                => 'wsx_final_hour_sale_2026_1',
				'start'              => '2026-02-25 00:00 Asia/Dhaka',
				'end'                => '2026-03-01 23:59 Asia/Dhaka', // format YY-MM-DD always set time 23:59 and zone Asia/Dhaka.

				'brand_color'        => '#6c6cff',

				'left_image'         => WHOLESALEX_URL . '/assets/img/banners/final_hour/left_image.png',
				'right_image'        => WHOLESALEX_URL . '/assets/img/banners/flash_sale/right_image.png',
				'bg_image'           => WHOLESALEX_URL . '/assets/img/banners/flash_sale/bg.png',
				'text'               => 'Hurry Before It Ends!',
				'countdown_duration' => 172800, // Duration in seconds.
				'countdown_color'    => '#3CF357',
				'url'                => Xpo::generate_utm_link(
					array(
						'utmKey' => 'final_hour',
					)
				),

				'visibility'         => ! Xpo::is_lc_active(),
			),
		);

		foreach ( $banner_notices as $notice ) {
			$notice_key = isset( $notice['key'] ) ? $notice['key'] : $this->notice_version;
			if (isset($_GET['disable_wsx_notice']) && $notice_key === sanitize_text_field(wp_unslash($_GET['disable_wsx_notice']))) { // phpcs:ignore
				return;
			}

			$current_time = gmdate( 'U' );
			$notice_start = gmdate( 'U', strtotime( $notice['start'] ) );
			$notice_end   = gmdate( 'U', strtotime( $notice['end'] ) );
			if ( $current_time >= $notice_start && $current_time <= $notice_end && $notice['visibility'] ) {

				$notice_transient = Xpo::get_transient_without_cache( 'wsx_get_pro_notice_' . $notice_key );

				if ( 'off' === $notice_transient ) {
					return;
				}

				if ( ! $this->notice_js_css_applied ) {
					$this->wsx_banner_notice_js();
					$this->notice_js_css_applied = true;
				}
				$query_args = array(
					'disable_wsx_notice' => $notice_key,
					'wpnonce'             => $wsx_db_nonce,
				);
				if ( isset( $notice['repeat_interval'] ) && $notice['repeat_interval'] ) {
					$query_args['wsx_interval'] = $notice['repeat_interval'];
				}
				?>
				<style type="text/css">
					.wsx-notice-wrapper.wsx-banner-notice {
						height: auto !important;
						min-height: 90px;
						padding: 0 !important;
						position: relative;
						box-sizing: border-box;
						background-repeat: no-repeat;
						background-size: cover;
						background-position: center;
					}

					.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-link {
						width: 100%;
						text-decoration: none;
						display: block;
					}

					.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-content {
						display: flex;
						justify-content: space-between;
						align-items: center;
						max-width: 1358px;
						margin: 0 auto;
						padding: 10px 16px;
						gap: 16px;
					}

					.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-side-image {
						display: block;
						max-width: 100%;
						height: auto;
					}

					.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-main {
						display: flex;
						flex-direction: column;
						gap: 4px;
						align-items: center;
						justify-content: center;
						font-weight: 700;
						font-size: 28px;
						color: #fff;
						line-height: 32px;
						text-align: center;
					}

					@media screen and (max-width: 1100px) {
						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-content {
							flex-direction: column;
						}
					}

					@media screen and (max-width: 782px) {
						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-content {
							justify-content: center;
							padding: 12px 32px 12px 12px;
						}

						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-main {
							font-size: 22px;
							line-height: 28px;
						}
					}

					@media screen and (max-width: 480px) {
						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-content {
							padding: 10px 32px 10px 10px;
						}

						.wsx-notice-wrapper.wsx-banner-notice .wsx-banner-main {
							font-size: 18px;
							line-height: 24px;
						}
					}
				</style>
				<div
					class="wsx-notice-wrapper wsx-banner-notice notice"
					style="
						border-left: 3px solid <?php echo esc_attr( $notice['brand_color'] ); ?>;
						background-image: url('<?php echo esc_attr( $notice['bg_image'] ); ?>');
				">
					<a
						class="wc-dismiss-notice dashicons dashicons-no-alt"
						style="
							position: absolute;
							top: 1px;
							right: 1px;
							border-radius: 50%;
							background-color: black;
							color: white;
							font-size: 14px;
							display: flex;
							align-items: center;
							justify-content: center;
						"
						aria-label="<?php esc_html_e( 'Close Banner', 'wow-table-rate-shipping' ); ?>"
						href="<?php echo esc_url( add_query_arg( $query_args ) ); ?>">
					</a>

					<a class="wsx-banner-link" target="_blank" href="<?php echo esc_url( $notice['url'] ); ?>">
						<div class="wsx-banner-content">
							<img class="wsx-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice['left_image'] ); ?>" />
							<div class="wsx-banner-main">
								<span>
									<?php echo esc_html( $notice['text'] ); ?>
								</span>
								<div
									class="wsx-notice-countdown"
									style="
										color: <?php echo esc_attr( $notice['countdown_color'] ); ?>;
									"
									data-notice-key="<?php echo esc_attr( $notice_key . '-countdown' ); ?>"
									data-duration="<?php echo esc_attr( $notice['countdown_duration'] ); ?>">
									00:00:00:00
								</div>
							</div>
							<img class="wsx-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice['right_image'] ); ?>" />
						</div>
					</a>
				</div>
				<?php
			}
		}
	}

	/**
	 * Banner JS
	 *
	 * @return void
	 */
	public function wsx_banner_notice_js() {
		?>
		<script type="text/javascript">
			jQuery(function($) {
				'use strict';

				const storagePrefix = 'wsx_notice_countdown_';

				const formatCountdown = function(seconds) {
					const days = Math.floor(seconds / 86400);
					const hours = Math.floor((seconds % 86400) / 3600);
					const minutes = Math.floor((seconds % 3600) / 60);
					const secs = seconds % 60;

					return String(days).padStart(2, '0') + ':' + String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
				};

				const parseDurationToSeconds = function(duration) {
					if (typeof duration === 'number' && Number.isFinite(duration) && duration > 0) {
						return Math.floor(duration);
					}

					const durationString = String(duration || '').trim();
					if (/^\d+$/.test(durationString)) {
						return parseInt(durationString, 10);
					}

					return 0;
				};

				const nowInSeconds = function() {
					return Math.floor(Date.now() / 1000);
				};

				$('.wsx-notice-countdown').each(function() {
					const countdownElement = $(this);
					const noticeKey = String(countdownElement.data('noticeKey') || '');
					const duration = parseDurationToSeconds(countdownElement.data('duration'));

					if (!noticeKey || duration <= 0) {
						return;
					}

					const storageKey = storagePrefix + noticeKey;
					let endAt = 0;

					try {
						const storedDataRaw = window.localStorage.getItem(storageKey);
						if (storedDataRaw) {
							const storedData = JSON.parse(storedDataRaw);
							if (storedData && parseInt(storedData.duration, 10) === duration) {
								endAt = parseInt(storedData.endAt, 10) || 0;
							}
						}
					} catch (error) {
						endAt = 0;
					}

					const saveTimerState = function(nextEndAt) {
						try {
							window.localStorage.setItem(
								storageKey,
								JSON.stringify({
									endAt: nextEndAt,
									duration: duration,
								})
							);
						} catch (error) {
							// No-op.
						}
					};

					const resetTimer = function(currentTime) {
						endAt = currentTime + duration;
						saveTimerState(endAt);
					};

					const tick = function() {
						const currentTime = nowInSeconds();

						if (endAt <= currentTime) {
							resetTimer(currentTime);
						}

						const remaining = Math.max(endAt - currentTime, 0);
						countdownElement.text(formatCountdown(remaining));
					};

					if (endAt <= nowInSeconds()) {
						resetTimer(nowInSeconds());
					}

					tick();
					window.setInterval(tick, 1000);
				});
			});
		</script>
		<?php
	}


	/**
	 * The Durbin Html
	 *
	 * @return void
	 */
	public function wsx_dashboard_durbin_notice_callback() {
		$durbin_key = 'wsx_durbin_dc1';

		if (
			isset($_GET['wsx_durbin_key']) || // phpcs:ignore
			'off' === Xpo::get_transient_without_cache( 'wsx_durbin_notice_' . $durbin_key )
		) {
			return;
		}

		if ( ! $this->notice_js_css_applied ) {
			$this->notice_js_css_applied = true;
		}

		$wsx_db_nonce = wp_create_nonce( 'wsx-nonce' );

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
				width: 100%;
				box-sizing: border-box;
			}

			.wsx-consent-content {
				display: flex;
				justify-content: flex-start;
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
				background-color: rgb(38, 38, 38);
				color: #fff;
			}
		</style>
		<div class="wsx-consent-box wsx-notice-wrapper notice data_collection_notice">
			<div class="wsx-consent-content">
				<div class="wsx-consent-text">
					<div class="wsx-consent-text-first"><?php esc_html_e( 'Want to help make WowShipping even more awesome?', 'wow-table-rate-shipping' ); ?></div>
					<div class="wsx-consent-text-last">
						<?php esc_html_e( 'Allow us to collect diagnostic data and usage information. see ', 'wow-table-rate-shipping' ); ?>
						<a href="https://www.wpxpo.com/data-collection-policy/" target="_blank"><?php esc_html_e( 'what we collect.', 'wow-table-rate-shipping' ); ?></a>
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
										'wsx_get_durbin' => 'get',
										'wpnonce'         => $wsx_db_nonce,
									)
								)
							);
					?>
					class="wsx-notice-close"><?php esc_html_e( 'Accept & Close', 'wow-table-rate-shipping' ); ?></a>
			</div>
			<a href=
			<?php
					echo esc_url(
						add_query_arg(
							array(
								'wsx_durbin_key' => $durbin_key,
								'wpnonce'         => $wsx_db_nonce,
							)
						)
					);
			?>
				class="wsx-notice-close"
				style="
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
				">
				<span
					style="font-size: 14px;"
					class="wsx-notice-close-icon dashicons dashicons-dismiss"> </span></a>
		</div>
		<?php
	}

	/**
	 * Plugin Install and Active Action
	 *
	 * @return void
	 */
	public function install_activate_plugin() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpnonce'] ?? '' ) ), 'wsx-nonce' ) ) {
			wp_send_json_error( esc_html__( 'Invalid nonce.', 'wow-table-rate-shipping' ) );
		}

		if ( ! isset( $_POST['install_plugin'] ) || ! Flags::is_user_admin() ) {
			wp_send_json_error( esc_html__( 'Invalid request.', 'wow-table-rate-shipping' ) );
		}
		$plugin_slug = sanitize_text_field( wp_unslash( $_POST['install_plugin'] ) );

		Xpo::install_and_active_plugin( $plugin_slug );

		$action = sanitize_text_field(wp_unslash($_POST['action'] ?? '')); // phpcs:ignore

		if (wp_doing_ajax() || is_network_admin() || isset($_GET['activate-multi']) || 'activate-selected' === $action) { //phpcs:ignore
			die();
		}

		wp_send_json_success( admin_url( 'admin.php?page=wsx-dashboard#dashboard' ) );
	}

	/**
	 * Installation Notice CSS
	 *
	 * @return void
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
				border-left: 3px solid #86a62c;
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
				border-left-color: #86a62c !important;
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
	 * @return void
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
							action: 'wsx_install',
							wpnonce: '<?php echo esc_js( wp_create_nonce( 'wsx-nonce' ) ); ?>',
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
