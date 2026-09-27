<?php
/**
 * WholesaleX Email Template
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * WholesaleX Email Class
 */
class WHOLESALEX_Email {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_email_template_restapi' ) );

		add_filter( 'wholealex_email_footer_text', array( $this, 'replace_email_footer_smart_tags' ) );

		add_filter( 'woocommerce_email_classes', array( $this, 'wholesalex_add_email_classes' ) );

		add_filter( 'woocommerce_email_actions', array( $this, 'wholesalex_add_email_actions' ) );
	}

	/**
	 * Add WholesaleX Email Classes
	 *
	 * @param array $email_classes WC email classes.
	 * @return array
	 */
	public function wholesalex_add_email_classes( $email_classes ) {
		$email_classes['WholesaleX_New_User_Auto_Approved_Email']                       = include WHOLESALEX_PATH . '/includes/emails/class-wholesalex-new-user-auto-approved.php';
		$email_classes['WholesaleX_Admin_New_User_Notification_Email']                  = include WHOLESALEX_PATH . '/includes/emails/class-wholesalex-new-user-registered.php';
		$email_classes['WholesaleX_New_User_Verification_Email']                        = include WHOLESALEX_PATH . '/includes/emails/class-wholesalex-new-user-verification.php';
		$email_classes['WholesaleX_New_User_Pending_For_Approval_Email']                = include WHOLESALEX_PATH . '/includes/emails/class-wholesalex-new-user-pending-for-approval.php';
		$email_classes['WholesaleX_Admin_New_User_Awating_Approval_Notification_Email'] = include WHOLESALEX_PATH . '/includes/emails/class-wholesalex-new-user-approval-require.php';
		$email_classes['WholesaleX_New_User_Approved_Email']                            = include WHOLESALEX_PATH . '/includes/emails/class-wholesalex-new-user-approved.php';
		$email_classes['WholesaleX_New_User_Verified_Email']                            = include WHOLESALEX_PATH . '/includes/emails/class-wholesalex-new-user-email-verified.php';
		$email_classes['WholesaleX_New_User_Rejected_Email']                            = include WHOLESALEX_PATH . '/includes/emails/class-wholesalex-new-user-rejected.php';
		$email_classes['WholesaleX_User_Profile_Update_Notification_Email']             = include WHOLESALEX_PATH . '/includes/emails/class-wholesalex-user-update.php';
		return $email_classes;
	}

	/**
	 * Add Email Actions
	 *
	 * @param array $actions Email Actions.
	 * @return array
	 */
	public function wholesalex_add_email_actions( $actions ) {
		$actions[] = 'wholesalex_registration_form_user_status_auto_approve';
		$actions[] = 'wholesalex_user_email_confirmation';
		$actions[] = 'wholesalex_registration_form_user_status_admin_approve';
		$actions[] = 'wholesalex_set_status_active';
		$actions[] = 'wholesalex_set_status_reject';
		$actions[] = 'wholesalex_user_email_verified';
		$actions[] = 'wholesalex_user_profile_update_notify';
		return $actions;
	}


	/**
	 * Register Email template rest api
	 *
	 * @return void
	 */
	public function register_email_template_restapi() {
		register_rest_route(
			'wholesalex/v1',
			'/email_templates/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'email_template_rest_callback' ),
					'permission_callback' => function () {
						return current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Sanitize Multiple Email Fields
	 *
	 * @param string $emails Emails.
	 * @return array
	 */
	public function sanitize_multiple_email_fields( $emails ) {
		$recipients = array_map( 'trim', explode( ',', $emails ) );
		$recipients = array_filter( $recipients, 'is_email' );
		return implode( ', ', $recipients );
	}

	/**
	 * Email Template Rest API Callback
	 *
	 * @param object $server Server.
	 * @return void
	 */
	public function email_template_rest_callback( $server ) {
		$post = $server->get_params();
		if ( ! isset( $post['nonce'] ) || ! is_string( $post['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $post['nonce'] ) ), 'wholesalex-registration' ) ) {
			return;
		}

		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		$response = array(
			'status' => false,
			'data'   => array(),
		);

		switch ( $type ) {
			case 'get':
				$response['status'] = true;
				$response['data']   = self::get_email_templates();
				break;
			case 'update_status':
				$template_name = isset( $post['template_name'] ) && is_string( $post['template_name'] ) ? sanitize_text_field( $post['template_name'] ) : '';
				$status        = isset( $post['enabled'] ) && is_string( $post['enabled'] ) ? sanitize_text_field( $post['enabled'] ) : '';
				if ( isset( self::get_email_templates()[ $template_name ] ) && in_array( $status, array( 'yes', 'no' ), true ) ) {
					$template_key_name            = 'woocommerce_' . $template_name . '_settings';
					$template_settings            = (array) get_option( $template_key_name, array() );
					$template_settings['enabled'] = $status;

					update_option( $template_key_name, $template_settings );
					$response['status']       = true;
					$response['template_key'] = $template_name;
					$response['enabled']      = $status;
					$response['data']         = 'yes' === $status ? __( 'Successfully enabled', 'wholesalex' ) : __( 'Successfully disabled', 'wholesalex' );
				}
				break;
			case 'save_template':
				$template_name = isset( $post['template_name'] ) && is_string( $post['template_name'] ) ? sanitize_text_field( $post['template_name'] ) : '';
				if ( isset( self::get_email_templates()[ $template_name ] ) && isset( $post['template'] ) && is_array( $post['template'] ) ) {
					$template_key_name = 'woocommerce_' . $template_name . '_settings';
					$template_settings = (array) get_option( $template_key_name, array() );
					if ( isset( $post['template']['recipient'] ) ) {
						$template_settings['recipient'] = $this->sanitize_multiple_email_fields( $post['template']['recipient'] );
					}
					if ( isset( $post['template']['subject'] ) ) {
						$template_settings['subject'] = wp_kses_post( $post['template']['subject'] );
					}
					if ( isset( $post['template']['heading'] ) ) {
						$template_settings['heading'] = wp_kses_post( $post['template']['heading'] );
					}
					if ( isset( $post['template']['additional_content'] ) ) {
						$template_settings['additional_content'] = wp_kses_post( $post['template']['additional_content'] );
					}
					if ( isset( $post['template']['email_type'] ) ) {
						$template_settings['email_type'] = wp_kses_post( $post['template']['email_type'] );
					}

					update_option( $template_key_name, $template_settings );
					$response['status'] = true;
					$response['data']   = __( 'Success', 'wholesalex' );
				}
				break;

			default:
				// code...
				break;
		}

		wp_send_json( $response );
	}

	/**
	 * Get All WholesaleX Email Templates
	 *
	 * @return array
	 */
	public static function get_email_templates() {
		$admin_email_template_ids = array(
			'wholesalex_new_user_approval_required',
			'wholesalex_new_user_registered',
		);

		$templates_ids = apply_filters(
			'wholesalex_email_templates',
			array(
				'wholesalex_new_user_approval_required'  => array(
					'enabled'            => 'yes',
					'subject'            => __( 'A New user registered and awaiting your approval.', 'wholesalex' ),
					'heading'            => __( 'A New user registered and awaiting your approval.', 'wholesalex' ),
					'additional_content' => __( 'We look forward to seeing you soon.', 'wholesalex' ),
					'email_type'         => 'html',
					/* translators: %s: Plugin Name */
					'title'              => sprintf( __( '%s: New User Approval Required', 'wholesalex' ), wholesalex()->get_plugin_name() ),
					'smart_tags'         => array(
						'{date}'       => __( 'Show The Current Date', 'wholesalex' ),
						'{admin_name}' => __( 'Show Site Admin Name', 'wholesalex' ),
						'{site_name}'  => __( 'Show Site Name', 'wholesalex' ),
					),
				),
				'wholesalex_new_user_approved'           => array(
					'enabled'            => 'yes',
					/* translators: %s Site Title */
					'subject'            => sprintf( __( 'Your %s Registration Request Approved', 'wholesalex' ), '{site_title}' ),
					/* translators: %s Site Title */
					'heading'            => sprintf( __( 'Your %s Registration Request Approved', 'wholesalex' ), '{site_title}' ),
					'additional_content' => '',
					'email_type'         => 'html',
					/* translators: %s: Plugin Name */
					'title'              => sprintf( __( '%s: Registration Approve', 'wholesalex' ), wholesalex()->get_plugin_name() ),
					'smart_tags'         => array(
						'{date}'       => __( 'Show The Current Date', 'wholesalex' ),
						'{admin_name}' => __( 'Show Site Admin Name', 'wholesalex' ),
						'{site_name}'  => __( 'Show Site Name', 'wholesalex' ),
					),
				),
				'wholesalex_new_user_auto_approve'       => array(
					'enabled'            => 'yes',
					/* translators: %s Site Title */
					'subject'            => sprintf( __( 'Your %s account successfully created!', 'wholesalex' ), '{site_title}' ),
					/* translators: %s Site Title */
					'heading'            => sprintf( __( 'Welcome to %s', 'wholesalex' ), '{site_title}' ),
					'additional_content' => '',
					'email_type'         => 'html',
					/* translators: %s: Plugin Name */
					'title'              => sprintf( __( '%s: New User (Auto Approved)', 'wholesalex' ), wholesalex()->get_plugin_name() ),
					'smart_tags'         => array(
						'{date}'       => __( 'Show The Current Date', 'wholesalex' ),
						'{admin_name}' => __( 'Show Site Admin Name', 'wholesalex' ),
						'{site_name}'  => __( 'Show Site Name', 'wholesalex' ),
					),
				),
				'wholesalex_new_user_email_verified'     => array(
					'enabled'            => 'yes',
					'subject'            => __( 'Congratulations! Your Account is Now Verified and Approved', 'wholesalex' ),
					'heading'            => __( 'Congratulations! Your Account is Now Verified and Approved', 'wholesalex' ),
					'additional_content' => '',
					'email_type'         => 'html',
					/* translators: %s: Plugin Name */
					'title'              => sprintf( __( '%s: Email Verified', 'wholesalex' ), wholesalex()->get_plugin_name() ),
					'smart_tags'         => array(
						'{date}'       => __( 'Show The Current Date', 'wholesalex' ),
						'{admin_name}' => __( 'Show Site Admin Name', 'wholesalex' ),
						'{site_name}'  => __( 'Show Site Name', 'wholesalex' ),
					),
				),
				'wholesalex_registration_pending'        => array(
					'enabled'            => 'yes',
					'subject'            => __( 'Registration Request Received', 'wholesalex' ),
					'heading'            => __( 'Registration Request Received', 'wholesalex' ),
					'additional_content' => '',
					'email_type'         => 'html',
					/* translators: %s: Plugin Name */
					'title'              => sprintf( __( '%s: Registration Pending For Approval', 'wholesalex' ), wholesalex()->get_plugin_name() ),
					'smart_tags'         => array(
						'{date}'       => __( 'Show The Current Date', 'wholesalex' ),
						'{admin_name}' => __( 'Show Site Admin Name', 'wholesalex' ),
						'{site_name}'  => __( 'Show Site Name', 'wholesalex' ),
					),
				),
				'wholesalex_new_user_registered'         => array(
					'enabled'            => 'yes',
					'subject'            => __( 'A New User Has been registered', 'wholesalex' ),
					'heading'            => __( 'A New User Has been registered', 'wholesalex' ),
					'additional_content' => '',
					'email_type'         => 'html',
					/* translators: %s: Plugin Name */
					'title'              => sprintf( __( '%s: New User Registered', 'wholesalex' ), wholesalex()->get_plugin_name() ),
					'smart_tags'         => array(
						'{date}'       => __( 'Show The Current Date', 'wholesalex' ),
						'{admin_name}' => __( 'Show Site Admin Name', 'wholesalex' ),
						'{site_name}'  => __( 'Show Site Name', 'wholesalex' ),
					),
				),
				'wholesalex_registration_rejected'       => array(
					'enabled'            => 'yes',
					/* translators: %s Site Title */
					'subject'            => sprintf( __( 'Your %s Registration Request Rejected', 'wholesalex' ), '{site_title}' ),
					/* translators: %s Site Title */
					'heading'            => sprintf( __( 'Your %s Registration Request Rejected', 'wholesalex' ), '{site_title}' ),
					'additional_content' => '',
					'email_type'         => 'html',
					/* translators: %s: Plugin Name */
					'title'              => sprintf( __( '%s: Registration Rejected', 'wholesalex' ), wholesalex()->get_plugin_name() ),
					'smart_tags'         => array(
						'{date}'       => __( 'Show The Current Date', 'wholesalex' ),
						'{admin_name}' => __( 'Show Site Admin Name', 'wholesalex' ),
						'{site_name}'  => __( 'Show Site Name', 'wholesalex' ),
					),

				),
				'wholesalex_new_user_email_verification' => array(
					'enabled'            => 'yes',
					'subject'            => __( 'Account Registration Confirmation - Action Required', 'wholesalex' ),
					'heading'            => __( 'Account Registration Confirmation - Action Required', 'wholesalex' ),
					'additional_content' => '',
					'email_type'         => 'html',
					/* translators: %s: Plugin Name */
					'title'              => sprintf( __( '%s: Email Verification', 'wholesalex' ), wholesalex()->get_plugin_name() ),
					'smart_tags'         => array(
						'{date}'       => __( 'Show The Current Date', 'wholesalex' ),
						'{admin_name}' => __( 'Show Site Admin Name', 'wholesalex' ),
						'{site_name}'  => __( 'Show Site Name', 'wholesalex' ),
					),

				),

			)
		);

		$templates_data = array();

		foreach ( $templates_ids as $template_id => $template ) {
			$template_key_name = 'woocommerce_' . $template_id . '_settings';
			$template_settings = get_option( $template_key_name, $template );

			if ( in_array( $template_id, $admin_email_template_ids, true ) ) {
				$template['recipient'] = get_option( 'admin_email' );
			}

			$templates_data[ $template_id ] = wp_parse_args( $template_settings, $template );

		}

		return $templates_data;
	}

	/**
	 * Save Email Template
	 *
	 * @param string $template_name Email Template Name.
	 * @param array  $template Email Tamplate.
	 * @return void
	 */
	public function save_email_template( $template_name, $template ) {
		$saved_templates                   = get_option( '__wholesalex_email_templates', array() );
		$saved_templates[ $template_name ] = $template;
		update_option( '__wholesalex_email_templates', $saved_templates );
	}

	/**
	 * Email Page Content
	 *
	 * @return void
	 */
	public static function email_page_content() {
		wp_enqueue_script( 'whx_email_templates' );
		wp_enqueue_style( 'whx_email_templates' );

		wp_localize_script(
			'whx_email_templates',
			'whx_email_templates',
			array(
				'i18n' => array(),
			)
		);
		?>
			<div id='wholesalex_email_templates_root'></div>
		<?php
	}


	/**
	 * Replace Email Footer Smart Tag
	 *
	 * @param string $new_string Tag.
	 * @return string
	 */
	public function replace_email_footer_smart_tags( $new_string ) {
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );

		return str_replace(
			array(
				'{site_title}',
				'{site_address}',
				'{site_url}',
				'{woocommerce}',
				'{WooCommerce}',
			),
			array(
				wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				$domain,
				$domain,
				'<a class="wsx-link" href="https://woocommerce.com">WooCommerce</a>',
				'<a class="wsx-link" href="https://woocommerce.com">WooCommerce</a>',
			),
			$new_string
		);
	}
}
