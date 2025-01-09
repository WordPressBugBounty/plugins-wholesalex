<?php

/**
 * WholesaleX Overview
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

use DateTime;
use WP_Query;
use WP_User_Query;

/**
 * WholesaleX Overview Class
 */
class WHOLESALEX_Overview
{
	/**
	 * Migration Tool Active
	 *
	 * @var bool
	 */
	public $is_migration_tool_active = false;

	/**
	 * Overview Constructor
	 */
	public function __construct()
	{
		add_action('admin_menu', array($this, 'overview_submenu_page_callback'), 1);
		add_action('rest_api_init', array($this, 'overview_callback'));
		add_action('admin_menu', array($this, 'go_pro_menu_page'), 99999);

		add_filter('wholesalex_capability_access', array($this, 'wholesalex_menus_access'));
		global $wpdb;
		add_action('wp_dashboard_setup', array($this, 'my_custom_dashboard_widgets'));
		add_action('wp_ajax_wholesalex_migration_tool_install', array($this, 'install_callback'));
		$this->init_migrations();
	}

	/**
	 * Check b2bking or wholesale Suite is Exist or Not
	 *
	 * @return void
	 */
	public function init_migrations()
	{
		$status = false;
		if ( function_exists('b2bkingcore_run') ) {
			 // Ensure the is_plugin_active() function is available
			 if ( !function_exists('is_plugin_active') ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			if ( is_plugin_active('b2bking-wholesale-for-woocommerce/b2bking.php') ) {
				$status = true;
			}
		}
		if (file_exists(WP_PLUGIN_DIR . '/woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php')) {
			 // Ensure the is_plugin_active() function is available
			 if ( !function_exists('is_plugin_active') ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			if (is_plugin_active('woocommerce-wholesale-prices/woocommerce-wholesale-prices.bootstrap.php')) {
				$status = true;
			}
		}
		$this->is_migration_tool_active = $status;
	}

	public function migration_tools_content()
	{
		if (method_exists('\WholesaleXMigrationTool', 'migration_tools_content') && is_plugin_active('wholesalex-migration-tool/wholesalex-migration-tool.php')) {
			\WholesaleXMigrationTool::migration_tools_content();
		} else {
			$this->wholesalex_migration_tool_notice_js();
?>

			<div class="wsx-header-wrapper">
				<div class="wsx-header wsx-migration-header">
					<div class="wsx-logo">
						<img src="<?php echo esc_url( WHOLESALEX_URL . '/assets/img/logo-option.svg' ); ?>" class="wsx-logo" />
						<span class="wsx-version"><?php echo esc_html( 'v' . WHOLESALEX_VER ) ?></span>
					</div>

					<div class="wsx-header-content wsx-flex-wrap wsx-gap-12">
						<span class="wsx-nav-link"> <?php echo esc_html( __('WholesaleX Migration Tool', 'wholesalex') ) ?> </span>

						<div class="wsx-btn-group wsx-text-space-nowrap">

							<?php if (!wholesalex()->is_pro_active()): ?>
								<div class="wsx-btn wsx-bg-secondary wsx-btn-icon wsx-text-space-nowrap" data-target="">
									<span class="wsx-icon  ">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none">
											<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
												stroke-width="1.5" d="m20.2 7.8-7.7 7.7-4-4-5.7 5.7"></path>
											<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
												stroke-width="1.5" d="M15 7h6v6"></path>
										</svg>
									</span>Upgrade to Pro
								</div>
							<?php endif; ?>
							<div class="wsx-dropdown">
								<div class="wsx-icon wsx-color-tertiary wsx-header-action">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
										<path fill="currentColor" fill-rule="evenodd"
											d="M10.001 18.333a8.334 8.334 0 1 0 0-16.667 8.334 8.334 0 0 0 0 16.667ZM8.613 8.148c0-.238.078-.615.288-.906.175-.242.475-.482 1.1-.482.765 0 1.163.405 1.297.81.142.427.021.903-.422 1.198-.73.487-1.268.958-1.552 1.62-.25.584-.25 1.225-.249 1.826v.102h1.853c0-.759.016-1.005.1-1.198.062-.148.218-.371.876-.81a2.866 2.866 0 0 0 1.152-3.323c-.39-1.175-1.505-2.078-3.055-2.078-1.228 0-2.084.533-2.604 1.253a3.51 3.51 0 0 0-.636 1.988h1.852Zm2.314 6.945V13.24H9.074v1.852h1.854Z"
											clip-rule="evenodd"></path>
									</svg>
								</div>
								<div class="wsx-dropdown-content-wrapper wsx-modal-menu-wrapper">
									<ul class="wsx-list wsx-d-flex wsx-flex-column wsx-gap-16">
										<?php
											$helpLinks = [
												['iconClass' => 'dashicons-phone', 'label' => 'Get Supports', 'link' => 'https://getwholesalex.com/contact/?utm_source=wholesalex-menu&amp;utm_medium=features_page-support&amp;utm_campaign=wholesalex-DB'],
												['iconClass' => 'dashicons-book', 'label' => 'Getting Started Guide', 'link' => 'https://getwholesalex.com/docs/wholesalex/getting-started/?utm_source=wholesalex-menu&amp;utm_medium=features_page-guide&amp;utm_campaign=wholesalex-DB'],
												['iconClass' => 'dashicons-facebook-alt', 'label' => 'Join Community', 'https://www.facebook.com/groups/wholesalexcommunity'],
												['iconClass' => 'dashicons-book', 'label' => 'Feature Request', 'link' => 'https://getwholesalex.com/roadmap/?utm_source=wholesalex-menu&amp;utm_medium=features_page-feature_request&amp;utm_campaign=wholesalex-DB'],
												['iconClass' => 'dashicons-youtube', 'label' => 'YouTube Tutorials', 'link' => 'https://www.youtube.com/@WholesaleX'],
												['iconClass' => 'dashicons-book', 'label' => 'Documentation', 'link' => 'https://getwholesalex.com/documentation/?utm_source=wholesalex-menu&amp;utm_medium=features_page-documentation&amp;utm_campaign=wholesalex-DB'],
												['iconClass' => 'dashicons-edit', 'label' => 'What’s New', 'link' => 'https://getwholesalex.com/roadmap/?utm_source=wholesalex-menu&amp;utm_medium=features_page-what’s_new&amp;utm_campaign=wholesalex-DB']
											];
											foreach ($helpLinks as $help) {
										?>
												<li class="wsx-list-item">
													<a href="<?php echo esc_url($help['link']) ?>" class="wsx-link wsx-list-link wsx-d-flex wsx-item-center wsx-gap-8" target="_blank">
														<span class="dashicons <?php echo esc_attr($help['iconClass']) ?> wsx-list-icon"></span>
														<span class="wsx-list-label"><?php echo esc_html($help['label']) ?></span>
													</a>
												</li>
										<?php
											}
										?>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="wsx-wrapper">
				<div class="wsx-container">
					<div class="wsx-card wsx-migration-card wc-install wsx-lg-p-32 wsx-gap-100 wsx-d-flex wsx-item-center wsx-lg-flex-column wsx-xl-gap-48 wsx-lg-text-center">
						<div class="wsx-wrapper-box">
							<div class="wsx-title wsx-font-28 wsx-mb-16">Migrate Your Data to WholesaleX</div>
							<div class="wsx-font-20 wsx-lh-normal wsx-mb-48">To ensure a smooth transition and retain all your previous B2B data, you'll need to install our Migration Tool.</div>
							<a class="wsx-link wsx-btn wsx-btn-icon wsx-migration-tool-btn" href="<?php echo esc_url(add_query_arg(array('action' => 'wholesalex_migration_tool_install'), admin_url())); ?>">
								<span class="wsx-btn-loading-icon wsx-anim-rotation"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.34 17a4.773 4.773 0 0 1 1.458-6.34l.002-.002a4.778 4.778 0 0 1 5.484.094l3.432 2.496a4.778 4.778 0 0 0 5.485.094l.002-.002A4.77 4.77 0 0 0 20.66 7m-3.658 13.66a4.774 4.774 0 0 1-6.34-1.458l-.002-.003a4.778 4.778 0 0 1 .095-5.484l2.495-3.432a4.778 4.778 0 0 0 .094-5.484l-.004-.002A4.772 4.772 0 0 0 7 3.34m12.07 1.59c3.906 3.905 3.906 10.236 0 14.141-3.905 3.906-10.236 3.906-14.141 0-3.905-3.905-3.905-10.236 0-14.141 3.905-3.905 10.236-3.905 14.141 0Z"/></svg></span>
								<span class="wsx-install-label"><?php echo esc_html_e('Install & Activate WholesaleX Migration Tool', 'wholesalex'); ?></span>
								<span  class="wsx-icon wsx-anim-icon-left"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M2 8h11.333m-4-4.667L14 8l-4.667 4.667"/></svg></span>
							</a>
						</div>
						<div class="wsx-migration-tool-img">
							<img src="<?php echo esc_url( WHOLESALEX_URL . '/assets/img/migration-image.png' ); ?>" alt="">
						</div>
					</div>
				</div>
			</div>
		<?php
		}
	}

	/** Notice Script. */
	public function wholesalex_migration_tool_notice_js()
	{
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				'use strict';
				$(document).on('click', '.wsx-migration-tool-btn', function(e) {
					e.preventDefault();
					const $that = $(this);
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {
							install_plugin: 'wholesalex-migration-tool',
							action: 'wholesalex_migration_tool_install'
						},
						beforeSend: function() {
							$('.wsx-btn-loading-icon').addClass('loading');
							$('.wsx-icon').css('display', 'block');
						},
						success: function(data) {
							$('#installation-msg').html(data);
							$('.wsx-install-label').text('Installing & Activating...');
							$('.wsx-icon').css('display', 'none');
						},
						complete: function() {
							location.reload();
							$('.wsx-btn-loading-icon').removeClass('loading');
						}
					});
				});
				$('.wsx-header-action').on('click', function() {
					const $menu = $('.wsx-modal-menu-wrapper');
					if ($menu.css('display') === 'none') {
						$menu.css('display', 'block');
					} else {
						$menu.css('display', 'none');
					}
				});

			});
		</script>
	<?php
	}


	/** Install MIgration Tool Callback */
	public function install_callback()
	{
		wholesalex()->wsx_migration_install_callback();
	}



	public function wholesalex_menus_access()
	{
		$menu_access = wholesalex()->get_setting('_settings_access_shop_manager_with_wxs_menu', '');
		return ($menu_access == 'yes' ? 'manage_woocommerce' : 'manage_options');
	}
	/**
	 * Overview Menu callback
	 *
	 * @return void
	 */
	public function overview_submenu_page_callback()
	{

		$wholesalex_menu_icon = plugins_url( 'wholesalex/assets/img/icon-sm.svg' );
		$wholesalex_menu_icon = apply_filters('wholesalex_menu_icon', $wholesalex_menu_icon);

		$menu_name = apply_filters('wholesalex_plugin_menu_name', __('WholesaleX', 'wholesalex'));
		$menu_slug = apply_filters('wholesalex_plugin_menu_slug', 'wholesalex');
		add_menu_page(
			$menu_name,
			$menu_name,
			apply_filters('wholesalex_capability_access', 'manage_options'),
			$menu_slug,
			array($this, 'output'),
			$wholesalex_menu_icon,
			59
		);
		add_submenu_page(
			$menu_slug,
			__('Dashboard', 'wholesalex'),
			__('Dashboard', 'wholesalex'),
			apply_filters('wholesalex_capability_access', 'manage_options'),
			$menu_slug,
			array($this, 'output'),
		);

		// Sub Menu List Start
		$manage_options_cap     = apply_filters( 'wholesalex_capability_access', 'manage_options' );
		$is_white_label_enabled = wholesalex()->get_setting( 'wsx_addon_whitelabel' );
		$submenus = array(
			array(
				'title'      => __( 'Dynamic Rules', 'wholesalex' ),
				'menu_title' => __( 'Dynamic Rules', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/dynamic-rules',
				'callback'   => array( $this, 'render_submenu_page' ),   // Single callback
				'identifier' => 'dynamic_rules',
			),
			array(
				'title'      => __( 'User Roles', 'wholesalex' ),
				'menu_title' => __( 'User Roles', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/user-role',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'user_roles',
			),
			array(
				'title'      => __( 'Registration Form', 'wholesalex' ),
				'menu_title' => __( 'Registration Form', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/registration',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'registration',
			),
			array(
				'title'      => __( 'Addons', 'wholesalex' ),
				'menu_title' => __( 'Addons', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/addons',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'addons',
			),
			array(
				'title'      => __( 'Users', 'wholesalex' ),
				'menu_title' => __( 'Users', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/users',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'users',
			),
			array(
				'title'      => __( 'Emails', 'wholesalex' ),
				'menu_title' => __( 'Emails', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/emails',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'emails',
			),
			array(
				'title'      => __( 'Settings', 'wholesalex' ),
				'menu_title' => __( 'Settings', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => '/settings',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'settings',
			),
		);

			if ( $is_white_label_enabled != 'yes' ) {
				$pro_submenu = array(
					'title'      => __( 'Features', 'wholesalex' ),
					'menu_title' => __( 'Features', 'wholesalex' ),
					'capability' => $manage_options_cap,
					'slug'       => '/features',
					'callback'   => array( $this, 'render_submenu_page' ),
					'identifier' => 'features',
				);
				array_splice($submenus, 8, 0, array($pro_submenu));
			}
			if ( $is_white_label_enabled != 'yes' ) {
				$pro_submenu = array(
					'title'      => __( 'Quick Support', 'wholesalex' ),
					'menu_title' => __( 'Quick Support', 'wholesalex' ),
					'capability' => $manage_options_cap,
					'slug'       => '/quick-support',
					'callback'   => array( $this, 'render_submenu_page' ),
					'identifier' => 'quick-support',
				);
				array_splice($submenus, 9, 0, array($pro_submenu));
			}

		// If Pro plugin is active, add the Pro submenu in the second position
		if ( function_exists('wholesalex_pro') &&  (version_compare(WHOLESALEX_PRO_VER,'1.5.7','>')) && wholesalex()->is_pro_active() && wholesalex()->get_setting( 'wsx_addon_conversation' ) == 'yes' ) {
			$menu_title             = apply_filters( 'wholesalex_addon_conversation_title', __( 'Conversations', 'wholesalex' ) );
			if ( class_exists( '\WHOLESALEX_PRO\Conversation' ) ) {
				$__pending_conversation = \WHOLESALEX_PRO\Conversation::get_open_conversation_count();
			} else {
				$__pending_conversation = 0;
			}

			$pro_submenu = array(
				'title'      => __( 'Conversations', 'wholesalex' ),
				'menu_title' => $__pending_conversation ? sprintf( __( '%1$s <span class="menu-counter"><span>%2$d</span></span>', 'wholesalex' ), $menu_title, $__pending_conversation ) : $menu_title,
				'capability' => $manage_options_cap,
				'slug'       => '/conversation',
				'callback'   => array( $this, 'render_submenu_page' ),
				'identifier' => 'conversation',
			);
			array_splice($submenus, 3, 0, array($pro_submenu));
		}
		if ( file_exists( WP_PLUGIN_DIR . '/wholesalex-pro/wholesalex-pro.php' ) ) {
			$menu_title             = apply_filters( 'wholesalex_addon_conversation_title', __( 'Conversations', 'wholesalex' ) );
			$status = apply_filters('wholesalex_show_license_page',true);
			if ( $status && $is_white_label_enabled != 'yes' ) {
				$pro_submenu = array(
					'title'      => __( 'License', 'wholesalex' ),
					'menu_title' => __( 'License', 'wholesalex' ),
					'capability' => $manage_options_cap,
					'slug'       => '/license',
					'callback'   => array( $this, 'render_submenu_page' ),
					'identifier' => 'license',
				);
				array_splice($submenus, 10, 0, array($pro_submenu));
			}
		}
		if ( $this->is_migration_tool_active ) {
			$slug = apply_filters('wholesalex_support_submenu_slug', 'wholesalex-migration');
			$pro_submenu = array(
				'title'      => __( 'Migration Tool', 'wholesalex' ),
				'menu_title' => __( 'Migration Tool', 'wholesalex' ),
				'capability' => $manage_options_cap,
				'slug'       => $slug,
				'callback'   => array( $this, 'migration_tools_content' ),
				'identifier' => 'migration_tool',
			);
			array_splice($submenus, 11, 0, array($pro_submenu));
		}


		foreach ($submenus as $submenu) {
			add_submenu_page(
				wholesalex()->get_menu_slug(),
				$submenu['title'],
				$submenu['menu_title'],
				$submenu['capability'],
				$submenu['slug'] !== 'wholesalex-migration' ? wholesalex()->get_menu_slug(). '#' .$submenu['slug'] : $submenu['slug'],
				$submenu['callback']
			);
		}
	}

	/**
	 * Overview Actions
	 *
	 * @since 1.0.0
	 */
	public function overview_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/overview_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array($this, 'overview_action_callback'),
					'permission_callback' => function () {
						return current_user_can(apply_filters('wholesalex_capability_access', 'manage_options'));
					},
					'args'                => array(),
				),
			)
		);
	}


	public function overview_action_callback( $server ) {
		$post = $server->get_params();
		if (!(isset($post['nonce']) && wp_verify_nonce(sanitize_key($post['nonce']), 'wholesalex-registration'))) {
			return;
		}

		$response = array(
			'status' => false,
			'data'   => array(),
		);

		$type = isset($post['type']) ? sanitize_text_field($post['type']) : '';
		if ('post' === $type) {
			wp_send_json($response);
		} elseif ('get' === $type) {
			$request_for = isset($post['request_for']) ? sanitize_text_field($post['request_for']) : '';
			$start_date  = isset($post['start_date']) ? sanitize_text_field($post['start_date']) : '';
			$end_date    = isset($post['end_date']) ? sanitize_text_field($post['end_date']) : '';
			$period      = isset($post['period']) ? sanitize_text_field($post['period']) : 'day';
			switch ($request_for) {
				case 'top_customers':
					$response['status'] = true;
					$response['data']   = $this->get_top_customers(10);
					break;
				case 'recent_orders':
					$response['status'] = true;
					$response['data']   = $this->get_b2b_recent_orders(10);
					break;
				case 'pending_registrations':
					$response['status'] = true;
					$response['data']   = $this->get_pending_users(10);
					break;
				case 'new_registrations_count':
					$response['status'] = true;
					$response['data']   = $this->get_new_registrations_count();
					break;
				case 'new_messages_count':
					$response['status'] = true;
					$response['data']   = $this->get_new_messages_count();
					break;
				case 'b2b_customer_count':
					$response['status'] = true;
					$response['data']   = $this->get_b2b_customer_count($start_date, $end_date);
					break;
				case 'b2b_order_data':
					$response['status'] = true;
					$response['data']   = $this->get_b2b_order_data($start_date, $end_date, $period);
					break;
				default:
					// code...
					break;
			}
		}

		wp_send_json($response);
	}

	public function my_custom_dashboard_widgets() {
		wp_add_dashboard_widget(
			'custom_help_widget',          // Widget slug
			sprintf( '%s - Last Month Insights', wholesalex()->get_plugin_name() ),              // Title
			array($this, 'wholesalex_wp_dashboard_callback') // Display function
		);
	}

	/**
	 * WholesaleX Wordpress Dashboard Widget
	 *
	 * @return void
	 */
	public function wholesalex_wp_dashboard_callback() {
		$current_date = new DateTime();
		$first_day_of_last_month = $current_date->modify('first day of last month')->format('Y-m-d');
		$last_day_of_last_month = $current_date->modify('last day of this month')->format('Y-m-d');
		$wsx_sales_summary = $this->get_b2b_order_data($first_day_of_last_month, $last_day_of_last_month);
		$wsx_b2b_customer_count = $this->get_b2b_customer_count($first_day_of_last_month, $last_day_of_last_month);
	?>

		<div id="wholesalex-wp-dashboard" class="wsx-wrapper wsx-column-2 wsx-sm-column-1 wsx-gap-8">
			<?php
			$cards = [
				['b2b_customer', WHOLESALEX_URL . '/assets/icons/dashboard-customer.svg', __( 'Customer No. (B2B)', 'wholesalex' ), $wsx_b2b_customer_count],
				['b2b_total_order', WHOLESALEX_URL . '/assets/icons/dashboard-order.svg', __( 'Total Order (B2B)', 'wholesalex' ), $wsx_sales_summary['total_orders']],
				['b2b_total_sale', WHOLESALEX_URL . '/assets/icons/dashboard-sale.svg', __( 'Total Sale (B2B)', 'wholesalex' ), $wsx_sales_summary['total_sales']],
				['b2b_net_earning', WHOLESALEX_URL . '/assets/icons/dashboard-revenue.svg', __( 'Net Revenue (B2B)', 'wholesalex' ), $wsx_sales_summary['net_revenue'], true],
				['b2b_gross_sale', WHOLESALEX_URL . '/assets/icons/dashboard-gross.svg', __( 'Gross Sale (B2B)', 'wholesalex' ), $wsx_sales_summary['gross_sales'], true],
				['average_order_value_sale', WHOLESALEX_URL . '/assets/icons/dashboard-order-value.svg', __( 'Average Order (B2B)', 'wholesalex' ), $wsx_sales_summary['average_order_value'], true],
			];

			foreach ($cards as $card) {
				$is_img = isset($card[4]) && $card[4];
			?>
				<div class="wsx-card wsx-p-16 wsx-dashboard-sale-summary-card-<?php echo esc_attr( $card[0] ); ?>">
					<div class="wsx-title-wrap">
						<div class="wsx-font-14 wsx-font-medium"><?php echo esc_attr( $card[2] ); ?></div>
					</div>
					<div class="wsx-d-flex wsx-item-center wsx-gap-12">
						<img class="wsx-icon" src="<?php echo esc_attr( $card[1] ); ?>">
						<div class="wsx-title wsx-color-text-dark"><?php echo $card[3] ; ?></div>
					</div>
				</div>
			<?php } ?>
		</div>
	<?php
	}

	/**
	 * Get b2b customer count.
	 *
	 * @param string $start_date Start Date.
	 * @param string $end_date End Date.
	 * @return int
	 */
	public function get_b2b_customer_count($start_date = '', $end_date = '')
	{
		$start_date = gmdate('Y-m-d H:i:s', strtotime($start_date));
		$end_date   = gmdate('Y-m-d H:i:s', strtotime('+1 DAY', strtotime($end_date)));

		// Meta key and excluded values
		$meta_key       = '__wholesalex_role';
		$exclude_values = array('', 'wholesalex_guest', 'wholesalex_b2c_users');

		// Meta query to exclude non-B2B users
		$meta_query = array(
			'relation' => 'AND',
			array(
				'key'     => $meta_key,
				'value'   => $exclude_values,
				'compare' => 'NOT IN',
			),
		);

		// Date query if both start and end dates are provided
		if (!empty($start_date) && !empty($end_date)) {
			$date_query = array(
				array(
					'column'    => 'user_registered',
					'after'     => $start_date,
					'before'    => $end_date,
					'inclusive' => true,
					'compare'   => 'BETWEEN',
					'type'      => 'DATETIME',
				),
			);
			$meta_query[] = $date_query; // Adding date query to existing meta query
		}

		// User query arguments
		$args = array(
			'meta_query'  => $meta_query,
			'count_total' => true, // Only retrieve the count
		);

		// Run the user query
		$user_query = new WP_User_Query($args);

		// Get the total count of users
		$user_count = $user_query->get_total();

		return $user_count;
	}

	/**
	 * Get All Addons
	 *
	 * @return array
	 */
	public function get_addons() {
		$addons_data = apply_filters( 'wholesalex_addons_config', array() );

		return $addons_data;
	}

	/**
	 * Output Function
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function output()
	{
		/**
		 * Enqueue Script
		 *
		 * @since 1.1.0 Enqueue Script (Reconfigure Build File)
		 */
		wp_enqueue_script('wholesalex_overview');
		wp_enqueue_style('wc-components');
		$user_heading_data = array();

		// Prepare as heading data.
		foreach ( \WHOLESALEX\WHOLESALEX_Users::get_wholesalex_users_columns() as $key => $value ) {
			$data               = array();
			$data['all_select'] = '';
			$data['name']       = $key;
			$data['title']      = $value;
			if ( 'action' == $key ) {
				$data['type'] = '3dot';
			} elseif ( 'wallet_balance' == $key || 'account_type' == $key ) {
				$data['type'] = 'html';
			}elseif ( 'transaction' == $key ) {
				$data['type'] = 'btn';
			} else {
				$data['type'] = 'text';
			}

			$user_heading_data[ $key ] = $data;
		}

		$user_heading_data['user_id']['status']           = 'yes';
		$user_heading_data['username']['status']          = 'yes';
		$user_heading_data['full_name']['status']         = 'yes';
		$user_heading_data['email']['status']             = 'yes';
		$user_heading_data['registration_date']['status'] = 'yes';
		$user_heading_data['wholesalex_role']['status']   = 'yes';
		$user_heading_data['wholesalex_status']['status'] = 'yes';
		$user_heading_data['action']['status']            = 'yes';
		$user_heading_data['transaction']['status']       = 'yes';


		$conversation_slug = apply_filters('wholesalex_addon_conversation_endpoint', 'wholesalex-conversation');
		$users_slug        = apply_filters('wholesalex_users_submenu_slug', 'wholesalex-users');
		$setting_slug 	   = apply_filters( 'wholesalex_settings_submenu_slug', 'wholesalex-settings' );

		//regis start
		$is_woo_username = get_option( 'woocommerce_registration_generate_username' );
		wp_enqueue_script( 'wholesalex_form_builder' );
		//Added Password Condition
		$password_condition_options = [
			['value' => 'uppercase_condition', 'name' => 'Uppercase'],
			['value' => 'lowercase_condition', 'name' => 'Lowercase'],
			['value' => 'special_character_condition', 'name' => 'Special Character'],
			['value' => 'min_length_condition', 'name' => 'Min 8 Length']
		];

		//Add File Support Type Array
		$file_condition_options = [
			['value' => 'jpg', 'name' => 'JPG'],
			['value' => 'jpeg', 'name' => 'JPEG'],
			['value' => 'png', 'name' => 'PNG'],
			['value' => 'txt', 'name' => 'TXT'],
			['value' => 'pdf', 'name' => 'PDF'],
			['value' => 'doc', 'name' => 'DOC'],
			['value' => 'docx', 'name' => 'DOCX']
		];

		$formData = wholesalex()->get_new_form_builder_data();
		$defaultFormData = wholesalex()->get_empty_form();
		//regis end

		//User Roles
		$__roles = array_values( wholesalex()->get_roles() );
		if ( empty( $__roles ) ) {
			$__roles = array(
				array(
					'id'    => 1,
					'label' => __('New Role','wholesalex'),
				),
			);
		}

		wp_localize_script(
			'wholesalex_overview',
			'wholesalex_overview',
			apply_filters(
				'wholesalex_overview_localize_data',
					array(
						/**
						 * Wizard Translation Start
						 */
						'wsx_wizard_url'                        => WHOLESALEX_URL,
						'wsx_wizard_nonce'                      => wp_create_nonce( 'wholesalex-setup-wizard' ) ?: menu_page_url('wholesalex-settings', false),
						'wsx_wizard_ajax'                       => admin_url( 'admin-ajax.php' ),
						'wsx_wizard_plugin_install_nonce'       => wp_create_nonce( 'updates' ),
						'wsx_wizard_is_pro_active'              => wholesalex()->is_pro_active(),
						'wsx_wizard_addons'                     => $this->get_addons(),
						'wsx_wizard_setting_url'                => menu_page_url('wholesalex-settings', false) ?: get_dashboard_url(),
						'wsx_wizard_dashboard_url'              => menu_page_url('wholesalex', false) ?: get_dashboard_url(),
						'wsx_wizard_site_name'                  => get_bloginfo('name'),
						'wsx_wizard___wholesalex_initial_setup' => get_option('__wholesalex_initial_setup', false),
						'wsx_wizard_woocommer_installed'        => file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php'),
						'wsx_wizard_productx_installed'         => file_exists(WP_PLUGIN_DIR . '/product-blocks/product-blocks.php'),
						  /**
						 * Wizard Translation Stop
						 */

						/**
						 * Conversation Translation Start
						 */
						'top_customer_heading'         => $this->prepare_as_heading_data($this->get_top_customers_columns()),
						'recent_order_heading'         => $this->prepare_as_heading_data($this->get_recent_orders_columns()),
						'pending_registration_heading' => $this->prepare_as_heading_data($this->get_pending_registrations_columns()),
						'wholesalex_conversation'      => menu_page_url($conversation_slug, false),
						'wholesalex_users'             => menu_page_url($users_slug, false),
						/**
						 * Conversation Translation Stop
						 */

						/**
						 * Addon Translation Start
						 */
						'addons'      				   => $this->get_addons(),
						'setting_url' 				   => menu_page_url( $setting_slug, false ),
						/**
						 * Addon Translation Stop
						 */

						/**
						 * RTL Support start
						 */
						'is_rtl_support'      				   => is_rtl(),
						/**
						 * RTL Support stop
						 */

						/**
						 * WSX Users Translation Start
						 */
						'whx_users_heading'            => $user_heading_data,
						'whx_users_user_per_page'      => 10,
						'whx_users_bulk_actions'       => \WHOLESALEX\WHOLESALEX_Users::get_wholesalex_users_bulk_actions(),
						'whx_users_statuses'           => wholesalex()->insert_into_array(
							array( '' => __( 'Select Status', 'wholesalex' ) ),
							\WHOLESALEX\WHOLESALEX_Users::get_user_statuses(),
							0
						),
						'whx_users_exportable_columns' 	=> ImportExport::exportable_user_columns(),
						'whx_users_roles' 				=> \WHOLESALEX\WHOLESALEX_Users::get_role_options(),
						/**
						 * WSX Users Translation Start
						 */


						/**
						 * Registration From Translation Start
						 */
						'whx_form_builder_is_woo_username'			  => $is_woo_username,
						'whx_form_builder_login_form_data'			  => wp_json_encode($defaultFormData['loginFields']),
						'whx_form_builder_form_data'				  => wp_json_encode( $formData ),
						'whx_form_builder_roles'					  => wholesalex()->get_roles( 'roles_option' ),
						'whx_form_builder_whitelabel_enabled'		  => 'yes' == wholesalex()->get_setting( 'wsx_addon_whitelabel'	) && function_exists( 'wholesalex_whitelabel_init' ),
						'whx_form_builder_slug'						  => wholesalex()->get_setting(	'registration_form_buidler_submenu_slug' ),
						'whx_form_builder_privacy_policy_text'		  => wc_get_privacy_policy_text( 'registration'	),
						'whx_form_builder_password_condition_options' => $password_condition_options,
						'whx_form_builder_file_condition_options'	  => $file_condition_options,
						'whx_form_builder_billing_fields'			  => array(
						''											=> __( 'No Mapping', 'wholesalex' ),
						'whx_form_builder_billing_first_name'		=> __( 'Billing	First Name', 'wholesalex' ),
						'whx_form_builder_billing_last_name'		=> __( 'Billing	Last Name',	'wholesalex' ),
						'whx_form_builder_billing_company'			=> __( 'Billing	Company', 'wholesalex' ),
						'whx_form_builder_billing_address_1'		=> __( 'Billing	Address	1',	'wholesalex' ),
						'whx_form_builder_billing_address_2'		=> __( 'Billing	Address	2',	'wholesalex' ),
						'whx_form_builder_billing_city'				=> __( 'Billing	City', 'wholesalex'	),
						'whx_form_builder_billing_postcode'			=> __( 'Billing	Post Code',	'wholesalex' ),
						'whx_form_builder_billing_country'			=> __( 'Billing	Country', 'wholesalex' ),
						'whx_form_builder_billing_state'			=> __( 'Billing	State',	'wholesalex' ),
						'whx_form_builder_billing_email'			=> __( 'Billing	Email',	'wholesalex' ),
						'whx_form_builder_billing_phone'			=> __( 'Billing	Phone',	'wholesalex' ),
						'whx_form_builder_custom_user_meta_mapping'	=> __( 'Custom User	Meta Mapping', 'wholesalex'	),
						),
						/**
						 * Registration From Translation Stop
						 */

						/**
						 * Setting Translation Start
						 */
						'whx_settings_fields' => \WHOLESALEX\Settings::get_option_settings(),
						'whx_settings_data'   => wholesalex()->get_setting(),
						/**
						 * Setting Translation Stop
						 */

						/**
						 * User Role Translation Start
						 */
						'whx_roles_fields' => \WHOLESALEX\WHOLESALEX_Role::get_role_fields(),
						'whx_roles_data'   => $__roles,
						'whx_roles_nonce'  => wp_create_nonce( 'whx-export-roles' ),
						/**
						 * User Role Translation Stop
						 */

						/**
						 * Dynamic Rules Translation Start
						 */
						'whx_dr_fields' => \WHOLESALEX\WHOLESALEX_Dynamic_Rules::get_dynamic_rules_field(),
						'whx_dr_rule'   => \WHOLESALEX\WHOLESALEX_Dynamic_Rules::dynamic_rules_get(),
						'whx_dr_nonce'  => wp_create_nonce( 'whx-export-dynamic-rules' ),
						'whx_dr_currency'   => get_woocommerce_currency_symbol(),
						/**
						 * Dynamic Rules Translation Stop
						 */

						'i18n' => array(
							'select_a_date_range_to_view_sale_data'	=> __('Select a	date range to view sale	data', 'wholesalex'),
							'select_a_date_range'					=> __('Select a	date range', 'wholesalex'),
							'date_range_title'					=> __('Date Range', 'wholesalex'),
							'presets'								=> __('Presets', 'wholesalex'),
							'custom'								=> __('Custom',	'wholesalex'),
							'reset'									=> __('Reset', 'wholesalex'),
							'update'								=> __('Update',	'wholesalex'),
							'today'									=> __('Today', 'wholesalex'),
							'yesterday'								=> __('Yesterday', 'wholesalex'),
							'week_to_date'							=> __('Week	to date', 'wholesalex'),
							'last_week'								=> __('Last	week', 'wholesalex'),
							'month_to_date'							=> __('Month to date', 'wholesalex'),
							'last_month'							=> __('Last	month',	'wholesalex'),
							'quarter_to_date'						=> __('Quarter to date', 'wholesalex'),
							'last_quarter'							=> __('Last	quarter', 'wholesalex'),
							'year_to_date'							=> __('Year	to date', 'wholesalex'),
							'last_year'								=> __('Last	year', 'wholesalex'),
							'dashboard'								=> __('Dashboard', 'wholesalex'),
							'new_messages'							=> __('New Messages', 'wholesalex'),
							'view_all'								=> __('View	All', 'wholesalex'),
							'new_registrations'						=> __('New Registrations', 'wholesalex'),
							'approve'								=> __('Approve', 'wholesalex'),
							'no_new_orders_found'					=> __('No New Orders Found!', 'wholesalex'),
							'pending_registration_in_empty'			=> __('Pending Registration	in Empty!',	'wholesalex'),
							'view_order'							=> __('View	Order',	'wholesalex'),
							'review'								=> __('Review',	'wholesalex'),
							'no_new_customer_found'					=> __('No New Customer Found!',	'wholesalex'),
							'customer_no_b2b'						=> __('Customer	No.	(B2B)',	'wholesalex'),
							'total_order_b2b'						=> __('Total Order (B2B)', 'wholesalex'),
							'total_sale_b2b'						=> __('Total Sale (B2B)', 'wholesalex'),
							'net_revenue_b2b'						=> __('Net Revenue (B2B)', 'wholesalex'),
							'gross_sale_b2b'						=> __('Gross Sale (B2B)', 'wholesalex'),
							'average_order_value_b2b'				=> __('Average Order (B2B)', 'wholesalex'),
							'full_access_to_dynamic_rules'			=> __('Full	Access to Dynamic Rules', 'wholesalex'),
							'multiple_pricing_tiers'				=> __('Multiple	Pricing	Tiers',	'wholesalex'),
							'bulk_order_form'						=> __('Bulk	Order Form', 'wholesalex'),
							'request_a_quote'						=> __('Request A Quote', 'wholesalex'),
							'subaccounts'							=> __('Subaccounts', 'wholesalex'),
							'conversation'							=> __('Conversation', 'wholesalex'),
							'wholesalex_wallet'						=> __('WholesaleX Wallet', 'wholesalex'),
							'and_much_more'							=> __('And Much	More!',	'wholesalex'),
							'unlock_message_pro'					=> __('Unlock the full potential of WholesaleX to create and manage	WooCommerce	B2B	or B2B+B2C stores with ease!', 'wholesalex'),
							'join_the_community_message'			=> __('Join	the	Facebook community of WholesaleX to stay up-to-date	and	share your thoughts	and	feedback.',	'wholesalex'),
							'feature_request_message'				=> __('Can’t find your desired feature?	Let	us know	your requirements. We will definitely take them	into our consideration.', 'wholesalex'),
							'getting_started_guide'					=> __('Getting Started Guides',	'wholesalex'),
							'how_to_create_the_dynamic_rule'		=> __('How to Create the Dynamic Rules', 'wholesalex'),
							'how_to_create_user_roles'				=> __('How to Create User Roles', 'wholesalex'),
							'how_to_create_registration_form'		=> __('How to Create a Registration	Form', 'wholesalex'),
							'wholesalex_blog'						=> __('WholesaleX Blog', 'wholesalex'),
							'join_wholesalex_community'				=> __('Join	WholesaleX Community', 'wholesalex'),
							'request_a_feature'						=> __('Request a Feature', 'wholesalex'),
							'pro_features'							=> __('Pro Features', 'wholesalex'),
							'wholesalex_community'					=> __('WholesaleX Community', 'wholesalex'),
							'feature_request'						=> __('Feature Request', 'wholesalex'),
							'news_tips_updates'						=> __('News, Tips &	Updates', 'wholesalex'),
							'sale_summary_b2b'						=> __('Sales Summary (B2B)', 'wholesalex'),
							'top_customers'							=> __('Top Customers', 'wholesalex'),
							'recent_orders'							=> __('Recent Orders', 'wholesalex'),
							'pending_registrations'					=> __('Pending Registrations', 'wholesalex'),
							'select_a_preset_period'				=> __('select a	preset period',	'wholesalex'),

							/**
							 *
							 * User Translation Text Start
							 *
							 */
							'whx_users_users'											=> __('Users','wholesalex'),
							'whx_users_edit'											=> __('Edit','wholesalex'),
							'whx_users_active'											=> __('Active','wholesalex'),
							'whx_users_reject'											=> __('Reject','wholesalex'),
							'whx_users_pending'											=> __('Pending','wholesalex'),
							'whx_users_delete'											=> __('Delete','wholesalex'),
							'whx_users_selected_users'									=> __('Selected	Users','wholesalex'),
							'whx_users_apply'											=> __('Apply','wholesalex'),
							'whx_users_import'											=> __('Import','wholesalex'),
							'whx_users_export'											=> __('Export','wholesalex'),
							'whx_users_columns'											=> __('Columns','wholesalex'),
							'whx_users_no_users_found'									=> __('No Users	Found!','wholesalex'),
							'whx_users_showing'											=> __('Showing','wholesalex'),
							'whx_users_pages'											=> __('Pages','wholesalex'),
							'whx_users_of'												=> __('of','wholesalex'),
							'whx_users_please_select_valid_csv_file'					=> __('Please Select a valid csv file to process import!','wholesalex'),
							'whx_users_please_wait_to_complete_existing_import_request'	=> __('Please Wait to complete existing	import request!','wholesalex'),
							'whx_users_error_occured'									=> __('Error Occured!','wholesalex'),
							'whx_users_import_successful'								=> __('Import Sucessful','wholesalex'),
							'whx_users_users_updated'									=> __('Users Updated','wholesalex'),
							'whx_users_users_inserted'									=> __('Users Inserted','wholesalex'),
							'whx_users_users_skipped'									=> __('Users Skipped','wholesalex'),
							'whx_users_download'										=> __('Download','wholesalex'),
							'whx_users_log_for_more_info'								=> __('Log For More	Info','wholesalex'),
							'whx_users_close'											=> __('Close','wholesalex'),
							'whx_users_username'										=> __('Username','wholesalex'),
							'whx_users_email'											=> __('Email','wholesalex'),
							'whx_users_upload_csv'										=> __('Upload CSV','wholesalex'),
							'whx_users_you_can_upload_only_csv_file'					=> __('You can upload only csv file	format','wholesalex'),
							'whx_users_update_existing_users'							=> __('Update Existing Users','wholesalex'),
							'whx_users_update_existing_users_message'					=> __('Selecting "Update Existing Users" will only update existing users. No new user will be added.','wholesalex'),
							'whx_users_find_existing_user_by'							=> __('Find	Existing Users By:','wholesalex'),
							'whx_users_option_to_detect_user'							=> __("Option to detect	user from the uploaded CSV's email or username field.",'wholesalex'),
							'whx_users_process_per_iteration'							=> __("Process Per Iteration",'wholesalex'),
							'whx_users_low_process_ppi'									=> __("Low process per iteration (PPI) increases the import's accuracy and success rate. A (PPI) higher	than your server's maximum execution time might	fail the import.",'wholesalex'),
							'whx_users_import'											=> __("Import",'wholesalex'),
							'whx_users_import_users'									=> __("Import Users",'wholesalex'),
							'whx_users_select_fields_to_export'							=> __("Select Fields to Export",'wholesalex'),
							'whx_users_csv_comma_warning'								=> __("Warning:	If any of the fields contain a comma (,), it might break the CSV file. Ensure the selected column value	contains no comma(,).",'wholesalex'),
							'whx_users_download_csv'									=> __("Download	CSV",'wholesalex'),
							'whx_users_export_users'									=> __("Export Users",'wholesalex'),
							/**
							 *
							 * User Translation Text Stop
							 *
							 */

							/**
							 *
							 * Email Translation Text Start
							 *
							 */
							'whx_email_templates_admin_email_recipient'	   => __('Admin	Email Recipient','wholesalex'),
							'whx_email_templates_subject'				   => __('Subject','wholesalex'),
							'whx_email_templates_heading'				   => __('Heading','wholesalex'),
							'whx_email_templates_additional_content'	   => __('Additional Content','wholesalex'),
							'whx_email_templates_smart_tag_used'		   => __('Smart	Tag	Used','wholesalex'),
							'whx_email_templates_email_type'			   => __('Email	Type','wholesalex'),
							'whx_email_templates_smart_tags'			   => __('Smart	Tags','wholesalex'),
							'whx_email_templates_save_changes'			   => __('Save Changes','wholesalex'),
							'whx_email_templates_status'				   => __('Status','wholesalex'),
							'whx_email_templates_email_template'		   => __('Email	Template','wholesalex'),
							'whx_email_templates_content_type'			   => __('Content Type','wholesalex'),
							'whx_email_templates_action'				   => __('Action','wholesalex'),
							'whx_email_templates_edit'					   => __('Edit','wholesalex'),
							'whx_email_templates_unlock'				   => __('UNLOCK','wholesalex'),
							'whx_email_templates_unlock_full_email_access' => __('Unlock Full Email	Access with','wholesalex'),
							'whx_email_templates_with_wholesalex_pro'	   => __('With WholesaleX Pro','wholesalex'),
							'whx_email_templates_upgrade_pro_message'	   => __('We are sorry,	but	only a limited number of emails	are	available on the free version. Please upgrade to a pro plan	to get full	access.','wholesalex'),
							'whx_email_templates_upgrade_to_pro_btn'	   => __('Upgrade to Pro  ➤','wholesalex'),
							'whx_email_templates_emails'				   => __('Emails','wholesalex'),
							/**
							 *
							 * Email Translation Text Stop
							 *
							 */

							/**
							 *
							 * Registration From and Builder Translation Text Start
							 *
							 */
							 'whx_form_builder_registration_form'	  				=>__('Registration Form','wholesalex'),
							 'whx_form_builder_go_back'	  							=>__('Go Back','wholesalex'),
							 'whx_form_builder_form_builder'          				=> __( 'Form Builder', 'wholesalex' ),
							 'whx_form_builder_save_form_changes'     				=> __( 'Save Changes', 'wholesalex' ),
							 'whx_form_builder_get_shortcodes'        				=> __( 'Get Shortcodes', 'wholesalex' ),
							 'whx_form_builder_styling_n_formatting'  				=> __( 'Styling & Formatting', 'wholesalex' ),
							 'whx_form_builder_premade_design'        				=> __( 'Premade Design', 'wholesalex' ),
							 'whx_form_builder_show_login_form'       				=> __( 'Show Login Form', 'wholesalex' ),
							 'whx_form_builder_form_title'            				=> __( 'Form Title', 'wholesalex' ),
							 'whx_form_builder_form_title_setting'    				=> __( 'Form Title Setting', 'wholesalex' ),
							 'whx_form_builder_hide_form_description' 				=> __( 'Hide Form Description', 'wholesalex' ),
							 'whx_form_builder_title'                 				=> __( 'Title', 'wholesalex' ),
							 'whx_form_builder_description'           				=> __( 'Description', 'wholesalex' ),
							 'whx_form_builder_style'                 				=> __( 'Style', 'wholesalex' ),
							 'whx_form_builder_choose_input_style'    				=> __( 'Choose Input Style', 'wholesalex' ),
							 'whx_form_builder_font_size'             				=> __( 'Font Size', 'wholesalex' ),
							 'whx_form_builder_font_weight'           				=> __( 'Font Weight', 'wholesalex' ),
							 'whx_form_builder_font_case'             				=> __( 'Font Case', 'wholesalex' ),
							 // Fields Inserter
							 'whx_form_builder_default_fields'        				=> __( 'Default User Fields', 'wholesalex' ),
							 'whx_form_builder_extra_fields'          				=> __( 'Extra Fields', 'wholesalex' ),
							 // Already Field Used Message
							 'whx_form_builder_already_used'          				=> __( 'This field is already used!', 'wholesalex' ),
							 'whx_form_builder_select_field' 						=> __('Select Field','wholesalex'),
							 'whx_form_builder_field' 								=> __('Field','wholesalex'),
							 'whx_form_builder_select_condition'					=> __('Select Condition','wholesalex'),
							 'whx_form_builder_condition' 							=> __('Condition','wholesalex'),
							 'whx_form_builder_equal' 								=> __('Equal','wholesalex'),
							 'whx_form_builder_not_equal' 							=> __('Not Equal','wholesalex'),
							 'whx_form_builder_value' 								=> __('Value','wholesalex'),
							 'whx_form_builder_custom_field_name_warning'			=> __("Note: Make sure field name does not contain any space or special character. 'wholesalex_cf_'  prefix will be added to the field name.",'wholesalex'),
							 'whx_form_builder_edit_field'							=> __('Edit Field','wholesalex'),
							 'whx_form_builder_field_condition'						=> __('Field Condition','wholesalex'),
							 'whx_form_builder_field_status'						=> __('Field Status','wholesalex'),
							 'whx_form_builder_required'							=> __('Required','wholesalex'),
							 'whx_form_builder_hide_label'							=> __('Hide Label','wholesalex'),
							 'whx_form_builder_exclude_role_items'					=> __('Exclude Role Items','wholesalex'),
							 'whx_form_builder_password_condition_label'			=> __('Password Strength Condition','wholesalex'),
							 'whx_form_builder_choose_password_strength'			=> __('Choose Password Strength...','wholesalex'),
							 'whx_form_builder_choose_file_type'					=> __('Choose File Type...','wholesalex'),
							 'whx_form_builder_password_strength_message'			=> __('Strength Message','wholesalex'),
							 'whx_form_builder_choose_roles'						=> __('Choose Roles...','wholesalex'),
							 'whx_form_builder_field_label'							=> __('Field Label','wholesalex'),
							 'whx_form_builder_label'								=> __('Label','wholesalex'),
							 'whx_form_builder_field_name'							=> __('Field Name','wholesalex'),
							 'whx_form_builder_options'								=> __('Options','wholesalex'),
							 'whx_form_builder_you_cant_edit_role_selection_field'	=> __('You cannot edit Role selection options','wholesalex'),
							 'whx_form_builder_placeholder'							=> __('Placeholder','wholesalex'),
							 'whx_form_builder_help_message'						=> __('Help Message','wholesalex'),
							 'whx_form_builder_term_condition'						=> __('Terms and Conditions','wholesalex'),
							 'whx_form_builder_term_link'							=> __('Terms/Policy Link','wholesalex'),
							 'whx_form_builder_term_link_placeholder'				=> __('Placeholder (Terms/Policy Link)','wholesalex'),
							 'whx_form_builder_term_condition_placeholder'			=> __('I agree to the Terms and Conditions {Privacy Policy}','wholesalex'),
							 'whx_form_builder_term_condition_link_label_warning'	=> __("Note: The provided link will be embedded within the {smart tag}.",'wholesalex'),
							 'whx_form_builder_term_condition_label_warning'		=> __("Note: Terms/Policy Link will be embedded within the smart tag (for example {Privacy Policy}) . Smart tag name (text) can be changed within the brackets.",'wholesalex'),
							 'whx_form_builder_allowed_file_types'					=> __('Allowed File Types (Comma Separated)','wholesalex'),
							 'whx_form_builder_maximum_allowed_file_size_in_bytes'	=> __('Maximum Allowed File Size in Bytes','wholesalex'),
							 'whx_form_builder_show'								=> __('Show','wholesalex'),
							 'whx_form_builder_hide'								=> __('Hide','wholesalex'),
							 'whx_form_builder_visibility'							=> __('Visibility','wholesalex'),
							 'whx_form_builder_all'									=> __('All','wholesalex'),
							 'whx_form_builder_any'									=> __('Any','wholesalex'),
							 'whx_form_builder_operator'							=> __('Operator','wholesalex'),
							 'whx_form_builder_operator_tooltip'					=> __('When the rule will be triggered? Upon matching all conditions or any of the added conditions.','wholesalex'),
							 'whx_form_builder_visibility_tooltip'					=> __('Select whether you want to display or hide this field when the conditions match.','wholesalex'),
							 'whx_form_builder_woocommerce_option'					=> __('WooCommerce Option','wholesalex'),
							 'whx_form_builder_add_woocommerce_registration'		=> __('Add WooCommerce Registration','wholesalex'),
							 'whx_form_builder_add_custom_field_to_billing'			=> __('Add Custom Field to Billing','wholesalex'),
							 'whx_form_builder_required_in_billing'					=> __('Required in Billing','wholesalex'),
							 'whx_form_builder_reset'								=> __('Reset','wholesalex'),
							 'whx_form_builder_reset_successful'					=> __('Reset Successful','wholesalex'),
							 'whx_form_builder_field_settings'						=> __('Field Settings','wholesalex'),
							 'whx_form_builder_video'								=> __('Video','wholesalex'),
							 // Text transform options
							 'whx_form_builder_none' 								=> __('None','wholesalex'),
							 'whx_form_builder_uppercase' 							=> __('Uppercase','wholesalex'),
							 'whx_form_builder_lowercase' 							=> __('Lowercase','wholesalex'),
							 'whx_form_builder_capitalize' 							=> __('Capitalize','wholesalex'),
							 // Fields
							 'whx_form_builder_color_settings' 						=> __('Color Settings','wholesalex'),
							 'whx_form_builder_sign_in' 							=> __('Sign In','wholesalex'),
							 'whx_form_builder_sign_up' 							=> __('Sign Up','wholesalex'),
							 'whx_form_builder_main' 								=> __('Main','wholesalex'),
							 'whx_form_builder_container' 							=> __('Container','wholesalex'),
							 'whx_form_builder_separator' 							=> __('Separator','wholesalex'),
							 'whx_form_builder_padding' 							=> __('Padding','wholesalex'),
							 'whx_form_builder_border_radius' 						=> __('Border Radius','wholesalex'),
							 'whx_form_builder_border' 								=> __('Border','wholesalex'),
							 'whx_form_builder_max_width' 							=> __('Max Width','wholesalex'),
							 'whx_form_builder_alignment' 							=> __('Alignment','wholesalex'),
							 'whx_form_builder_left' 								=> __('Left','wholesalex'),
							 'whx_form_builder_center' 								=> __('Center','wholesalex'),
							 'whx_form_builder_right' 								=> __('Right','wholesalex'),
							 'whx_form_builder_size_and_spacing_settings' 			=> __('Size and Spacing Settings','wholesalex'),
							 'whx_form_builder_input' 								=> __('Input','wholesalex'),
							 'whx_form_builder_button' 								=> __('Button','wholesalex'),
							 'whx_form_builder_weight' 								=> __('Weight','wholesalex'),
							 'whx_form_builder_transform' 							=> __('Transform','wholesalex'),
							 'whx_form_builder_size' 								=> __('Size','wholesalex'),
							 'whx_form_builder_typography_settings' 				=> __('Typography Settings','wholesalex'),
							 'whx_form_builder_background' 							=> __('Background','wholesalex'),
							 'whx_form_builder_text' 								=> __('Text','wholesalex'),
							 'whx_form_builder_placeholder' 						=> __('Placeholder','wholesalex'),
							 'whx_form_builder_label' 								=> __('Label','wholesalex'),
							 'whx_form_builder_input_text' 							=> __('Input Text','wholesalex'),
							 'whx_form_builder_state' 								=> __('State','wholesalex'),
							 'whx_form_builder_normal' 								=> __('Normal','wholesalex'),
							 'whx_form_builder_active' 								=> __('Active','wholesalex'),
							 'whx_form_builder_warning' 							=> __('Warning','wholesalex'),
							 'whx_form_builder_hover' 								=> __('Hover','wholesalex'),
							 'whx_form_builder_width' 								=> __('Width','wholesalex'),
							 // Premade Modal
							 'whx_form_builder_premade_heading' 					=> __('Create a Form Right Away Using a Pre-made Designs','wholesalex'),
							 'whx_form_builder_use_design' 							=> __('Use Design','wholesalex'),
							 //Shortcode Modal
							 'whx_form_builder_specific_shortcode_list' 			=> __('Specific Shortcode List','wholesalex'),
							 'whx_form_builder_with_login_form' 					=> __('With Login Form','wholesalex'),
							 'whx_form_builder_only_login_form' 					=> __('Global Login Form','wholesalex'),
							 'whx_form_builder_regi_form_with_all_roles' 			=> __('Registration Form with All Roles.','wholesalex'),
							 'whx_form_builder_login_form_with_all_roles' 			=> __('Login Form with All Roles.','wholesalex'),
							 'whx_form_builder_copy_to_clipboard' 					=> __('Copy To Clipboard','wholesalex'),
							 'whx_form_builder_b2b_global_form' 					=> __('B2B Global Form','wholesalex'),
							 'whx_form_builder_regi_form_only_for' 					=> __('Registration Form only for','wholesalex'),
							 'whx_form_builder_role' 								=> __('Role.','wholesalex'),
							 //pro popup
							 'whx_form_builder_unlock' 								=> __("UNLOCK",'wholesalex'),
							 'whx_form_builder_unlock_heading' 						=> __("Unlock all Features with",'wholesalex'),
							 'whx_form_builder_unlock_desc' 						=> __("We are sorry, but unfortunately, this feature is unavailable in the free version. Please upgrade to a pro plan to unlock all features.",'wholesalex'),
							 'whx_form_builder_upgrade_to_pro' 						=> __("Upgrade to Pro  ➤",'wholesalex'),
							/**
							 *
							 * Registration From and Builder Translation Text Stop
							 *
							 */


							/**
							 *
							 * Settings Translation Text Start
							 *
							 */
							'whx_settings_settings' 		=> __('Settings','wholesalex'),
							'whx_settings_unlock' 			=> __("UNLOCK",'wholesalex'),
							'whx_settings_unlock_heading'	=> __("Unlock All Features",'wholesalex'),
							'whx_settings_unlock_desc' 		=> __("We are sorry, but unfortunately, this feature is unavailable in the free version. Please upgrade to a pro plan to unlock all features.",'wholesalex'),
							'whx_settings_upgrade_to_pro' 	=> __("Upgrade to Pro  ➤",'wholesalex'),
							/**
							 *
							 * Settings Translation Text Stop
							 *
							 */


							/**
							 *
							 * User Role Translation Text Start
							 *
							 */
							'whx_roles_user_roles'								=> __('User Roles','wholesalex'),
							'whx_roles_no_shipping_zone_found'              	=> __('No Shipping Zones Found!','wholesalex'),
							'whx_roles_please_fill_role_name_field'         	=> __('Please Fill Role Name Field','wholesalex'),
							'whx_roles_successfully_deleted'                	=> __('Succesfully Deleted.','wholesalex'),
							'whx_roles_successfully_saved'                  	=> __('Succesfully Saved.','wholesalex'),
							'whx_roles_add_new_b2b_role'                    	=> __('Add New B2B Role','wholesalex'),
							'whx_roles_import'                              	=> __('Import','wholesalex'),
							'whx_roles_export'                              	=> __('Export','wholesalex'),
							'whx_roles_b2b_role'                            	=> __('B2B Role: ','wholesalex'),
							'whx_roles_untitled_role'                       	=> __('Untitled Role','wholesalex'),
							'whx_roles_delete_this_role'                    	=> __('Delete','wholesalex'),
							'whx_roles_edit_this_role'                      	=> __('Edit','wholesalex'),
							'whx_roles_duplicate_role'                      	=> __('Duplicate','wholesalex'),
							'whx_roles_untitled'                            	=> __('Untitled','wholesalex'),
							'whx_roles_duplicate_of'                        	=> __('Duplicate of ','wholesalex'),
							'whx_roles_show_hide_role_details'              	=> __('Show/Hide Role Details.','wholesalex'),
							'whx_roles_csv_fields_to_roles'                 	=> __('Map CSV Fields to Roles','wholesalex'),
							'whx_roles_select_field_from_csv_file'          	=> __('Select fields from your CSV file to map against role fields, or to ignore during import.','wholesalex'),
							'whx_roles_column_name'                         	=> __('Column name','wholesalex'),
							'whx_roles_map_to_field'                        	=> __('Map to field','wholesalex'),
							'whx_roles_do_not_import'                       	=> __('Do not import','wholesalex'),
							'whx_roles_run_the_importer'                    	=> __('Run the importer','wholesalex'),
							'whx_roles_importing'                           	=> __('Importing','wholesalex'),
							'whx_roles_your_roles_are_now_being_imported'   	=> __('Your roles are now being imported..','wholesalex'),
							'whx_roles_upload_csv'                          	=> __('Upload CSV','wholesalex'),
							'whx_roles_you_can_upload_only_csv_file_format' 	=> __('You can upload only csv file format','wholesalex'),
							'whx_roles_update_existing_roles'               	=> __('Update Existing Roles','wholesalex'),
							'whx_roles_update_existing_roles_help_message'  	=> __('Selecting "Update Existing Roles" will only update existing roles. No new role will be added.','wholesalex'),
							'whx_roles_continue'                            	=> __('Continue','wholesalex'),
							'whx_roles_error_occured'                       	=> __('Eror Occured!','wholesalex'),
							'whx_roles_import_complete'                     	=> __('Import Complete!','wholesalex'),
							'whx_roles_role_imported'                       	=> __(' Role Imported.','wholesalex'),
							'whx_roles_role_updated'                        	=> __(' Role Updated.','wholesalex'),
							'whx_roles_role_skipped'                        	=> __(' Role Skipped.','wholesalex'),
							'whx_roles_role_failed'                         	=> __(' Role Failed.','wholesalex'),
							'whx_roles_view_error_logs'                     	=> __(' View Error Logs','wholesalex'),
							'whx_roles_role'                                	=> __('Role','wholesalex'),
							'whx_roles_reason_for_failure'                  	=> __('Reason for failure','wholesalex'),
							'whx_roles_import_user_roles'                   	=> __('Import User Roles','wholesalex'),
							'whx_roles_b2c_users'                           	=> __('B2C Users','wholesalex'),
							'whx_roles_guest_users'                         	=> __('Guest Users','wholesalex'),
							/**
							 *
							 * User Role Translation Text Stop
							 *
							 */

							/**
							 *
							 * Dynamic Rules Translation Text Start
							 *
							 */
							'whx_dr_dynamic_rules'													=> __('Dynamic Rules','wholesalex'),
							'whx_dr_please_fill_all_fields'											=> __('Please Fill All Fields.','wholesalex'),
							'whx_dr_minimum_product_quantity_should_greater_then_free_product_qty'	=> __('Minimum Product Quantity	Should Greater then	Free Product Quantity.','wholesalex'),
							'whx_dr_rule_title'														=> __('Rule	Title','wholesalex'),
							'whx_dr_create_dynamic_rule'											=> __('Create Dynamic Rule','wholesalex'),
							'whx_dr_import'															=> __('Import','wholesalex'),
							'whx_dr_export'															=> __('Export','wholesalex'),
							'whx_dr_untitled'														=> __('Untitled','wholesalex'),
							'whx_dr_duplicate_of'													=> __('Duplicate of ','wholesalex'),
							'whx_dr_delete_this_rule'												=> __('Delete this Rule','wholesalex'),
							'whx_dr_delete_condition'												=> __('Delete Condition','wholesalex'),
							'whx_dr_duplicate_this'													=> __('Duplicate','wholesalex'),
							'whx_dr_duplicate_this_rule'											=> __('Duplicate this Rule','wholesalex'),
							'whx_dr_show_hide_rule_details'											=> __('Show/Hide Rule Details.','wholesalex'),
							'whx_dr_vendor'															=> __('Vendor #','wholesalex'),
							'whx_dr_untitled_rule'													=> __('Untitled	Rule','wholesalex'),
							'whx_dr_error_occured'													=> __('Error Occured!','wholesalex'),
							'whx_dr_map_csv_fields_to_dynamic_rules'								=> __('Map CSV Fields to Dynamic Rules','wholesalex'),
							'whx_dr_select_field_from_csv_msg'										=> __('Select fields from your CSV file	to map against role	fields,	or to ignore during	import.','wholesalex'),
							'whx_dr_column_name'													=> __('Column name','wholesalex'),
							'whx_dr_map_to_field'													=> __('Map to field','wholesalex'),
							'whx_dr_do_not_import'													=> __('Do not import','wholesalex'),
							'whx_dr_run_the_importer'												=> __('Run the importer','wholesalex'),
							'whx_dr_importing'														=> __('Importing','wholesalex'),
							'whx_dr_upload_csv'														=> __('Upload CSV','wholesalex'),
							'whx_dr_you_can_upload_only_csv_file_format'							=> __('You can upload only csv file	format','wholesalex'),
							'whx_dr_your_dynamic_rules_are_now_being_importing'						=> __('Your	Dynamic	Rules are now being	imported..','wholesalex'),
							'whx_dr_update_existing_rules'											=> __('Update Existing Rules','wholesalex'),
							'whx_dr_select_update_exising_rule_msg'									=> __('Selecting "Update Existing Rules" will only update existing rules. No new rules will	be added.','wholesalex'),
							'whx_dr_continue'														=> __('Continue','wholesalex'),
							'whx_dr_dynamic_rule_imported'											=> __('	Dynamic	Rules Imported.','wholesalex'),
							'whx_dr_dynamic_rule_updated'											=> __('	Dynamic	Rules Updated.','wholesalex'),
							'whx_dr_dynamic_rule_skipped'											=> __('	Dynamic	Rules Skipped.','wholesalex'),
							'whx_dr_dynamic_rule_failed'											=> __('	Dynamic	Rules Failed.','wholesalex'),
							'whx_dr_view_error_logs'												=> __('View	Error Logs','wholesalex'),
							'whx_dr_dynamic_rule'													=> __('Dynamic Rule','wholesalex'),
							'whx_dr_reason_for_failure'												=> __('Reason for failure','wholesalex'),
							'whx_dr_import_dynamic_rules'											=> __('Import Dynamic Rules','wholesalex'),
							'whx_dr_latest'															=> __('Latest','wholesalex'),
							'whx_dr_oldest'															=> __('Oldest','wholesalex'),
							'whx_dr_save'															=> __('Save','wholesalex'),
							'whx_dr_condition_label'												=> __('Condition','wholesalex'),
							'whx_dr_operator_label'													=> __('Operator','wholesalex'),
							'whx_dr_amount_label'													=> __('Amount','wholesalex'),
							'whx_dr_add_new_condition'												=> __('Add New Condition','wholesalex'),
							/**
							 *
							 * Dynamic Rules Translation Text Stop
							 *
							 */

							/**
							 *
							 * Feature Translation Text Start
							 *
							 */
							'whx_features_features'										=> __('Features','wholesalex'),
							'whx_features_restrict_guest_access'						=> __('Restrict	Guest Access','wholesalex'),
							'whx_features_bulk_order'									=> __('Bulk	Order','wholesalex'),
							'whx_features_request_a_quote'								=> __('Request A Quote', 'wholesalex'),
							'whx_features_wholesale_pricing'							=> __('Wholesale Pricing', 'wholesalex'),
							'whx_features_registration_form'							=> __('Registration	Form', 'wholesalex'),
							'whx_features_subaccounts_management'						=> __('Subaccounts Management',	'wholesalex'),
							'whx_features_wallet_management'							=> __('Wallet Management', 'wholesalex'),
							'whx_features_dynamic_discount_rules'						=> __('Dynamic Discount	Rules',	'wholesalex'),
							'whx_features_conversations_built_in_messaging'				=> __('Conversations Built-in Messaging', 'wholesalex'),
							'whx_features_create_unlimited_user_roles'					=> __('Create Unlimited	User Roles', 'wholesalex'),
							'whx_features_tax_control'									=> __('Tax Control', 'wholesalex'),
							'whx_features_import_and_export_role_base_sale_price'		=> __('Import and Export Role Base/Sale	Price',	'wholesalex'),
							'whx_features_import_export_customer'						=> __('Import Export Customer',	'wholesalex'),
							'whx_features_automatic_approval_for_b2b_registration'		=> __('Automatic Approval For B2B Registration', 'wholesalex'),
							'whx_features_manual_approval_for_b2b_registration'			=> __('Manual Approval For B2B Registration', 'wholesalex'),
							'whx_features_email_notifications_for_different_actions'	=> __('Email Notifications For Different Actions', 'wholesalex'),
							'whx_features_control_redirect_urls'						=> __('Control Redirect	URLs', 'wholesalex'),
							'whx_features_visibility_control'							=> __('Visibility Control',	'wholesalex'),
							'whx_features_shipping_control'								=> __('Shipping	Control', 'wholesalex'),
							'whx_features_force_free_shipping'							=> __('Force Free Shipping', 'wholesalex'),
							'whx_features_payment_gateway_control'						=> __('Payment Gateway Control', 'wholesalex'),
							'whx_features_extra_charge'									=> __('Extra Charge', 'wholesalex'),
							'whx_features_bogo_discounts'								=> __('BOGO	Discounts',	'wholesalex'),
							'whx_features_show_login_to_view_prices'					=> __('Show	Login to view prices', 'wholesalex'),
							'whx_features_buy_x_get_y'									=> __('Buy X Get Y', 'wholesalex'),
							'whx_features_control_order_quantity'						=> __('Control Order Quantity',	'wholesalex'),
							'whx_features_google_rrecaptcha_v3_integration'				=> __('Google RreCAPTCHA V3 Integration', 'wholesalex'),
							'whx_features_quote_request'								=> __('Quote Request', 'wholesalex'),
							'whx_features_conversation_with_store_owner'				=> __('Conversation	With Store Owner', 'wholesalex'),
							'whx_features_auto_role_migration'							=> __('Auto	Role Migration', 'wholesalex'),
							'whx_features_rolewise_credit_limit'						=> __('Rolewise	Credit Limit', 'wholesalex'),
							'whx_features_conditions_and_limits'						=> __('Conditions and Limits', 'wholesalex'),
							'whx_features_restrict_guest_access_desc'					=> __('Make	your wholesale area	private	for	registered users and restrict guest	users.', 'wholesalex'),
							'whx_features_bulk_order_desc'								=> __('Allow your customers	to order products in bulk or create	purchase lists to order	later.', 'wholesalex'),
							'whx_features_request_a_quote_desc'							=> __('Let the potential buyers	send quote requests	to you directly	from the cart page.', 'wholesalex'),
							'whx_features_wholesale_pricing_desc'						=> __('Effortlessly	manage wholesale pricing based on multiple wholesale/b2b user roles.', 'wholesalex'),
							'whx_features_registration_form_desc'						=> __('Create a	custom registration	form with custom fields	for	effective customer acquisition.', 'wholesalex'),
							'whx_features_subaccounts_management_desc'					=> __('Let your	registered B2B customers create	subaccounts	with necessary user	access.', 'wholesalex'),
							'whx_features_wallet_management_desc'						=> __('Let B2B customers add funds to their	digital	wallets	and	use	it as a	payment	method.', 'wholesalex'),
							'whx_features_dynamic_discount_rules_desc'					=> __('Effectively manage discounted wholesale pricing using the dynamic discount rules.', 'wholesalex'),
							'whx_features_conversations_built_in_messaging_desc'		=> __('Let your	registered customers communicate with you with the in-built	conversation system.', 'wholesalex'),
							'whx_features_the_most_complete_woocommerce'				=> __('The Most	Complete WooCommerce', 'wholesalex'),
							'whx_features_b2b_n_b2c'									=> __('B2B + B2C', 'wholesalex'),
							'whx_features_hybrid_solution'								=> __('Hybrid Solution', 'wholesalex'),
							'whx_features_checkout_the_main_attractive'					=> __('Check out the main attractive features at a glance',	'wholesalex'),
							'whx_features_explore_more'									=> __('Explore More', 'wholesalex'),
							'whx_features_wholesalex_core'								=> __('WholesaleX Core', 'wholesalex'),
							'whx_features_components'									=> __('Components',	'wholesalex'),
							'whx_features_explore_more_features'						=> __('Explore More	Features', 'wholesalex'),
							/**
							 *
							 * Feature Translation Text Stop
							 *
							 */

							/**
							 *
							 * Quick Support Translation Text Start
							 *
							 */
							'whx_support_quick_support'					   => __('Quick	Support','wholesalex'),
							'whx_support_technical_support'				   => __('Technical	Support','wholesalex'),
							'whx_support_free_support'					   => __('Free Support (WordPress ORG)','wholesalex'),
							'whx_support_presale_question'				   => __('Presale Questions','wholesalex'),
							'whx_support_license_activation_issue'		   => __('License Activation Issues','wholesalex'),
							'whx_support_bug_report'					   => __('Bug Report','wholesalex'),
							'whx_support_compatibility_issue'			   => __('Compatibility	Issues','wholesalex'),
							'whx_support_feature_request'				   => __('Feature Request','wholesalex'),
							'whx_support_getting_started_with_wholesalex'  => __('Getting Started with WholesaleX','wholesalex'),
							'whx_support_dynamic_pricing_n_discount_rules' => __('Dynamic Pricing &	Discount Rules','wholesalex'),
							'whx_support_wholesale_user_roles'			   => __('Wholesale	User Roles','wholesalex'),
							'whx_support_regi_form_builder'				   => __('Registration Form	Builder','wholesalex'),
							'whx_support_wholesalex_addons'				   => __('WholeasaleX Addons','wholesalex'),
							'whx_support_how_to_create_private_store'	   => __('How to Create	a Private Store','wholesalex'),
							'whx_support_please_select_support_type'	   => __('Please Select	Support	type.','wholesalex'),
							'whx_support_please_fill_all_the_input_field'  => __('Please Fill all the Input	Field..','wholesalex'),
							'whx_support_having_difficulties'			   => __('Having Difficulties? We are here to help','wholesalex'),
							'whx_support_let_us_know'					   => __('Let us know how we can help you.','wholesalex'),
							'whx_support_pro'							   => __('(Pro)','wholesalex'),
							'whx_support_create_a_ticket'				   => __('Create a Ticket','wholesalex'),
							'whx_support_select_support_type'			   => __('Select Support Type from above','wholesalex'),
							'whx_support_name'							   => __('Name','wholesalex'),
							'whx_support_email'							   => __('Email','wholesalex'),
							'whx_support_subject'						   => __('Subject','wholesalex'),
							'whx_support_desc_label'					   => __('Explain your Problem','wholesalex'),
							'whx_support_desc'							   => __('Description','wholesalex'),
							'whx_support_type_here'						   => __('Type here','wholesalex'),
							'whx_support_you_can_contact_in_support'	   => __('You can Contact in Support via our ','wholesalex'),
							'whx_support_contact_form'					   => __('Contact Form','wholesalex'),
							'whx_support_submit_ticket'					   => __('Submit Ticket','wholesalex'),
							'whx_support_wholesalex_community'			   => __('WowCommerce Community','wholesalex'),
							'whx_support_join_community'				   => __('Join Community','wholesalex'),
							'whx_support_useful_guides'					   => __('Useful Guides','wholesalex'),
							'whx_support_check_out_in_depth_docs'		   => __('Check	out	the	in depth documentation','wholesalex'),
							'whx_support_doc'					   		   => __('Doc','wholesalex'),
							'whx_support_tutorial'					   	   => __('Tutorial','wholesalex'),
							'whx_support_join_wholesalex_community_msg'	   => __('Join the Facebook	community of WholesaleX	to stay	up-to-date and share your thoughts and feedback.','wholesalex'),
							/**
							 *
							 * Quick Support Translation Text Stop
							 *
							 */
						)
					)
			)
		);

		wp_set_script_translations('wholesalex_overview', 'wholesalex', WHOLESALEX_PATH . 'languages/');
		wp_enqueue_style('wholesalex');
	?>
		<div id="wholesalex-overview"></div>
	<?php	
	}
	/**
	 * Get Top Customer Columns.
	 *
	 * @return array
	 */
	public function get_top_customers_columns()
	{
		$columns = array(
			'name_n_email'    => __('Name and Email', 'wholesalex'),
			'wallet_balance'  => __('Wallet Balance', 'wholesalex'),
			'total_purchase'  => __('Total Purchase', 'wholesalex'),
			/* translators: %s - Plugin Name */
			'wholesalex_role' => wp_sprintf(__('%s Role', 'wholesalex'), wholesalex()->get_plugin_name()),
		);

		if (!('yes' === wholesalex()->get_setting('wsx_addon_wallet') && wholesalex()->is_pro_active())) {
			unset($columns['wallet_balance']);
		}

		$columns = apply_filters('wholesalex_dashboard_top_customer_columns', $columns);

		return $columns;
	}

	/**
	 * Get Recent Orders Columns
	 *
	 * @return array
	 */
	public function get_recent_orders_columns()
	{
		$columns = array(
			'ID'            	=> __('Order ID', 'wholesalex'),
			'customer_name' 	=> __('Name', 'wholesalex'),
			'order_date'    	=> __('Date', 'wholesalex'),
			'order_status'  	=> __('Status', 'wholesalex'),
			'view_order'    	=> __('Action', 'wholesalex'),
		);

		$columns = apply_filters('wholesalex_dashboard_recent_orders_columns', $columns);

		return $columns;
	}

	/**
	 * Get Pending Registrations Column.
	 *
	 * @return array
	 */
	public function get_pending_registrations_columns()
	{
		$columns = array(
			'name_n_email'      => __('Name and Email', 'wholesalex'),
			'user_registered'   => __('Regi. Date', 'wholesalex'),
			'registration_role' => __('Regi. Role', 'wholesalex'),
			'edit_user'         => __('Action', 'wholesalex'),
		);

		$columns = apply_filters('wholesalex_dashboard_pending_registrations_columns', $columns);

		return $columns;
	}

	/**
	 * Prepare Column as Heading Data.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function prepare_as_heading_data($columns)
	{
		$user_heading_data = array();
		foreach ($columns as $key => $value) {
			$data          = array();
			$data['name']  = $key;
			$data['title'] = $value;
			switch ($key) {
				case 'wallet_balance':
					$data['type'] = 'html';
					break;
				case 'total_purchase':
					$data['type'] = 'html';
					break;
				case 'name_n_email':
					$data['type'] = 'name_n_email';
					break;
				case 'view_order':
					$data['type'] = 'view_order';
					break;

				case 'edit_user':
					$data['type'] = 'edit_user';
					break;
				default:
					$data['type'] = 'text';
					break;
			}

			$user_heading_data[$key] = $data;
		}

		return $user_heading_data;
	}

	/**
	 * Add Go Pro Menu Page
	 *
	 * @return void
	 * @since 1.1.2
	 */
	public function go_pro_menu_page()
	{
		if (!wholesalex()->is_pro_active()) {
			// $title = sprintf('<span class="wholesalex-submenu-title__upgrade-to-pro"><span class="dashicons dashicons-star-filled"></span>%s</span>', __('Upgrade to Pro', 'wholesalex'));
			$title = sprintf('<div class="wsx-d-flex wsx-item-center wsx-gap-8 wsx-color-lime "><div class="wsx-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" stroke="currentColor" viewBox="0 0 32 32"><path fill="currentColor" d="m3.488 13.184 6.272 6.112-1.472 8.608L16 23.84l7.712 4.064-1.472-8.608 6.272-6.112-8.64-1.248L16 4.128l-3.872 7.808z"/></svg></div>%s</div>', __('Upgrade to Pro', 'wholesalex'));
			add_submenu_page(
				'wholesalex',
				'',
				$title,
				apply_filters('wholesalex_capability_access', 'manage_options'),
				'go_wholesalex_pro',
				array($this, 'go_pro_redirect')
			);
		}
	}

	/**
	 * Go Pro Redirect From Dashboard
	 *
	 * @since 1.1.2
	 */
	public function go_pro_redirect()
	{
		if (isset($_GET['page']) && 'go_wholesalex_pro' === sanitize_text_field($_GET['page'])) { //phpcs:ignore
			wp_redirect('https://getwholesalex.com/pricing/?utm_source=wholesalex-plugins&utm_medium=go_pro&utm_campaign=wholesalex-DB'); //phpcs:ignore
			die();
		} else {
			return;
		}
	}


	/**
	 * Get top customers
	 *
	 * @param integer $limit Limit.
	 * @return array
	 */
	public function get_top_customers($limit = 10)
	{
		// For Getting Top B2B Users Data. (Based on Sales).
		$active_user_query = new WP_User_Query(
			array(
				'fields'     => array('display_name', 'user_email', 'ID'),
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => '__wholesalex_status',
						'value'   => 'active',
						'compare' => '=',
					),
					array(
						'key'     => '__wholesalex_role',
						'value'   => array('', 'wholesalex_guest', 'wholesalex_b2c_users'),
						'compare' => '!=',
					),

				),
				'number'     => $limit,

			)
		);

		$active_users_data = (array) $active_user_query->get_results();

		foreach ($active_users_data as $key => $value) {
			$__temp                   = (array) $value;
			$__id                     = $__temp['id'];
			$__temp['avatar_url']     = get_avatar_url($__id);
			$__temp['total_purchase'] = wc_get_customer_total_spent($__id);
			if (wholesalex()->is_pro_active() && function_exists('wholesalex_wallet')) {
				$__temp['wallet_balance'] = wholesalex_wallet()->get_wholesalex_balance($__id);
			}
			$__temp['wholesalex_role'] = wholesalex()->get_role_name_by_role_id(get_user_meta($__id, '__wholesalex_role', true));
			$active_users_data[$key] = $__temp;
		}

		$__sort_colum = array_column($active_users_data, 'total_purchase');
		array_multisort($__sort_colum, SORT_DESC, $active_users_data);

		return $active_users_data;
	}

	/**
	 * Get b2b recent orders
	 *
	 * @param integer $limit limit.
	 * @return array
	 */
	public function get_b2b_recent_orders($limit = 10)
	{
		$args = array(
			'status'      => array('completed', 'on-hold'),
			'limit'       => 10,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'meta_query'  => array(
				array(
					'key'     => '__wholesalex_order_type',
					'value'   => 'b2b',
					'compare' => '=',
				),
			),
		);

		$orders = wc_get_orders($args);
		$b2b_recent_orders = array();
		foreach ($orders as $order_id) {
			$order = wc_get_order($order_id);
			// Get order details
			$order_data = array(
				'ID'            => $order->get_id(),
				'customer_id'   => $order->get_user_id(),
				'order_date'    => $order->get_date_created()->date('Y-m-d'),
				'order_status'  => $order->get_status(),
			);

			$__user_data = get_userdata($order_data['customer_id']);
			$order_data['customer_name'] = $__user_data ? $__user_data->display_name : 'Guest';
			$order_data['order_status'] = wc_get_order_status_name($order_data['order_status']);
			$order_data['view_order'] = admin_url('post.php?post=' . $order_data['ID'] . '&action=edit');
			$b2b_recent_orders[] = $order_data;
		}
		return $b2b_recent_orders;
	}

	/**
	 * Get pending users.
	 *
	 * @param integer $limit limit.
	 * @return array
	 */
	public function get_pending_users($limit = 10)
	{
		$pending_user_query = new WP_User_Query(
			array(
				'meta_key'     => '__wholesalex_status',
				'meta_value'   => 'pending',
				'meta_compare' => '=',
				'fields'       => array('display_name', 'user_email', 'ID', 'user_registered'),
				'count_total'  => true,
				'number'       => $limit,
			)
		);

		$pending_user_data = (array) $pending_user_query->get_results();

		foreach ($pending_user_data as $key => $value) {
			$__temp                      = (array) $value;
			$__id                        = $__temp['id'];
			$__temp['avatar_url']        = get_avatar_url($__id);
			$__temp['registration_role'] = wholesalex()->get_role_name_by_role_id(get_user_meta($__id, '__wholesalex_registration_role', true));
			$__temp['edit_user']         = get_edit_user_link($__id);
			$pending_user_data[$key]   = $__temp;
		}

		return $pending_user_data;
	}

	/**
	 * Get new registration count.
	 *
	 * @return int
	 */
	public function get_new_registrations_count()
	{
		$pending_user_query = new WP_User_Query(
			array(
				'meta_key'     => '__wholesalex_status',
				'meta_value'   => 'pending',
				'meta_compare' => '=',
				'fields'       => array('display_name', 'user_email', 'ID', 'user_registered'),
				'count_total'  => true,
			)
		);

		return $pending_user_query->get_total();
	}

	/**
	 * Get new messages count.
	 *
	 * @return int
	 */
	public function get_new_messages_count()
	{
		$messages_query = new WP_Query(
			array(
				'post_type'    => 'wsx_conversation',
				'post_status'  => 'publish',
				'meta_key'     => '__conversation_status',
				'meta_value'   => 'open',
				'meta_compare' => '=',
			)
		);

		return $messages_query->found_posts;
	}

	/**
	 * Get Previews Growth orders.
	 *
	 * @return int
	 */
	public function calculateGrowthRate($currentValue, $previousValue) {
		$currentValue = (float) $currentValue;
		$previousValue = (float) $previousValue;

		if ($previousValue == 0) {
			// Avoid division by zero; return 100% growth if previous sales are 0
			return $currentValue > 0 ? 100 : 0;
		}
		// Calculate growth percentage
		$growthPercentage = (($currentValue - $previousValue) / $previousValue) * 100;
		return (int) round($growthPercentage);
	}
	// Helper methods
	private function formatNumber($number) {
		return number_format($number, 2, '.', '');
	}

	/**
	 * Determine the period based on the number of days between two dates.
	 *
	 * @param string $start_date The start date.
	 * @param string $end_date The end date.
	 * @return string The period.
	 */
	public function determinePeriod($start_date, $end_date) {
		// Convert dates to DateTime objects
		$start_date_obj = new DateTime($start_date);
		$end_date_obj = new DateTime($end_date);

		// Calculate the difference in days
		$interval = $start_date_obj->diff($end_date_obj);
		$days = $interval->days;

		// Determine the period based on the number of days
		if ($days == 7) {
			return 'Week';
		} elseif ($days == 30 || $days == 31) {
			return 'Month';
		} elseif ($days == 90 || $days == 91 || $days == 92) {
			return 'Quarter';
		} elseif ($days == 365) {
			return 'Year';
		} elseif ($days > 365) {
			return $days . ' Days';
		} else {
			return $days . ' Days';
		}
	}

	/**
	 * Get b2b order data.
	 *
	 * @param string $start_date start date.
	 * @param string $end_date end data.
	 * @param string $period period.
	 * @return array
	 */
	public function get_b2b_order_data($start_date = '', $end_date = '', $period = '') {
		global $wpdb;

		$start_date = gmdate('Y-m-d H:i:s', strtotime($start_date));
		$end_date   = gmdate('Y-m-d H:i:s', strtotime('+1 DAY', strtotime($end_date)));

		$period = $this->determinePeriod($start_date, $end_date);

		// Calculate the previous period date range
		$start_date_obj = new DateTime($start_date);
		$end_date_obj = new DateTime($end_date);
		$interval = $start_date_obj->diff($end_date_obj);

		$prev_start_date = $start_date_obj->sub($interval)->format('Y-m-d H:i:s');
		$prev_end_date = $end_date_obj->sub($interval)->format('Y-m-d H:i:s');

		$graph_data   = array();
		$graph_legend = array();

		$startDate = new DateTime($start_date);
		$endDate   = new DateTime($end_date);

		$currentDate = clone $startDate;

		while ($currentDate < $endDate) {
			$formattedDate = $currentDate->format('Y-m-d');
			$graph_data[$formattedDate] = 0;
			$graph_legend[]             = $formattedDate;
			$currentDate->modify('+1 day');
		}

		$args = array(
			'status' => array('wc-completed', 'wc-refunded'),
			'date_paid' => $start_date . '...' . $end_date,
			'meta_key' => '__wholesalex_order_type',
			'meta_value' => 'b2b',
			'limit' => -1,
		);

		$orders = wc_get_orders($args);

		$total_sales = 0;
		$gross_sales = 0;
		$net_revenue = 0;
		$total_orders = 0;
		$graph_data = [];
		$gross_graph_data = [];
		$revenue_graph_data = [];

		// Initialize the date range
		$date_range = new \DatePeriod(
			new \DateTime($start_date),
			new \DateInterval('P1D'),
			(new \DateTime($end_date))->modify('+1 day')
		);

		// Prepare the graph data for each date in the range
		foreach ($date_range as $date) {
			$formatted_date = $date->format('Y-m-d');
			$graph_data[$formatted_date] = 0;
			$gross_graph_data[$formatted_date] = 0;
			$revenue_graph_data[$formatted_date] = 0;
		}

		// Process each order
		foreach ($orders as $order) {
			$total_orders++;
			$total_sales += $order->get_total();
			$gross_sales += $order->get_subtotal();
			$net_revenue += $order->get_total() - $order->get_total_refunded();

			$paid_date = $order->get_date_paid()->date('Y-m-d');

			// Update the respective graph data if the paid date exists in the date range
			if (isset($graph_data[$paid_date])) {
				$graph_data[$paid_date] += $order->get_total();
				$graph_data[$paid_date] = number_format($graph_data[$paid_date], 2, '.', '');
			}

			if (isset($gross_graph_data[$paid_date])) {
				$gross_graph_data[$paid_date] += $order->get_subtotal();
				$gross_graph_data[$paid_date] = number_format($gross_graph_data[$paid_date], 2, '.', '');
			}

			if (isset($revenue_graph_data[$paid_date])) {
				$revenue_graph_data[$paid_date] += $order->get_total() - $order->get_total_refunded();
				$revenue_graph_data[$paid_date] = number_format($revenue_graph_data[$paid_date], 2, '.', '');
			}
		}

		// Fetch previous period data
		$prev_args = array(
			'status' => array('wc-completed', 'wc-refunded'),
			'date_paid' => $prev_start_date . '...' . $prev_end_date,
			'meta_key' => '__wholesalex_order_type',
			'meta_value' => 'b2b',
			'limit' => -1,
		);

		$prev_orders = wc_get_orders($prev_args);

		$prev_total_sales = 0;
		$prev_gross_sales = 0;
		$prev_net_revenue = 0;
		$prev_total_orders = 0;

		// Process each previous period order
		foreach ($prev_orders as $order) {
			$prev_total_orders++;
			$prev_total_sales += $order->get_total();
			$prev_gross_sales += $order->get_subtotal();
			$prev_net_revenue += $order->get_total() - $order->get_total_refunded();
		}
		//number_format($prev_total_sales, 2, '.', ''),
		$data = array(
			'total_sales'           => wc_price($total_sales),
			'gross_sales'           => wc_price($gross_sales),
			'net_revenue'           => wc_price($net_revenue),
			'average_order_value'   => $total_orders != 0 ? wc_price(round($total_sales / $total_orders, 2)) : wc_price(0),
			'total_orders'          => $total_orders,
			'sales_graph'           => $graph_data,
			'gross_graph'           => $gross_graph_data,
			'revenue_graph'         => $revenue_graph_data,
			'order_graph'           => $graph_data, // Assuming you still want the sales graph for orders
			'graph_legend'          => $graph_legend,
			'prev_average_order'    => $prev_total_orders != 0 && $total_orders != 0 ? $this->calculateGrowthRate(($total_sales / $total_orders), ($prev_total_sales / $prev_total_orders)) : 0,
			'prev_total_sales'      => $this->calculateGrowthRate($total_sales, $this->formatNumber($prev_total_sales)),
			'prev_gross_sales'      => $this->calculateGrowthRate($gross_sales, $this->formatNumber($prev_gross_sales)),
			'prev_net_revenue'      => $this->calculateGrowthRate($net_revenue, $this->formatNumber($prev_net_revenue)),
			'prev_total_order' 		=> $this->calculateGrowthRate($total_orders, $this->formatNumber($prev_total_orders)),
			'period' 				=> $period,
		);

		return $data;
	}
}
