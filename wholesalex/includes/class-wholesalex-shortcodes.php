<?php
/**
 * Shortcodes
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

use WHOLESALEX\WholesaleX_CommonUtils;

/**
 * WholesaleX Shortcodes Class
 *
 * @since 1.0.0
 */
class WHOLESALEX_Shortcodes {
	/**
	 * Stores the names of fields used in the registration form.
	 *
	 * This array is typically used to check for specific field names.
	 * when rendering or validating the registration form dynamically.
	 *
	 * @var string[]
	 */

	public $registration_form_felds_name = array();


	/**
	 * Shortcodes Constructor
	 */
	public function __construct() {
		/**
		 * Shortcode For WholesaleX Login and Registration Form (Combined)
		 *
		 * @since 1.0.1
		 */

		add_shortcode( 'wholesalex_registration', array( $this, 'registration_shortcode' ), 10 );
		add_shortcode( 'wholesalex_login_registration', array( $this, 'login_registration_shortcode' ), 10 );
		add_shortcode( 'wholesalex_login', array( $this, 'login_shortcode' ), 10 );
		add_action( 'init', array( $this, 'wholesalex_handle_password_reset' ) );
		/**
		 * Filters the list of CSS class names for the current post.
		 *
		 * @param string[] $classes An array of post class names.
		 * @return string[] An array of post class names.
		 */
		add_filter(
			'post_class',
			function ( array $classes ): array {
				array_push( $classes, '_wholesalex wsx-wholesalex-product' );
				return $classes;
			},
			10,
			1
		);

		add_action( 'wholesalex_before_registration_form_render', array( $this, 'enqueue_password_meter' ) );

		add_action( 'wp_ajax_nopriv_wholesalex_process_login', array( $this, 'process_login' ) );
		add_action( 'wp_ajax_wholesalex_process_login', array( $this, 'process_login' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action(
			'wholesalex_before_registration_form_render',
			function () {
				if ( ! is_admin() && function_exists( 'wc_print_notices' ) ) {
					woocommerce_output_all_notices();
				}
			}
		);

		do_action( 'wholesalex_shortcodes_registered', $this );
	}

	/**
	 * Display the forgot password form
	 *
	 * @return string
	 */
	public function wholesalex_forgot_password_form() {
		if ( isset( $_GET['reset'], $_GET['_wpnonce'] ) && is_string( $_GET['reset'] ) && is_string( $_GET['_wpnonce'] ) && 'true' === $_GET['reset'] && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wholesalex_reset_password' ) ) {
			echo '<div class="woocommerce-message">' . esc_html__( 'A password reset email has been sent. Please check your inbox.', 'wholesalex' ) . '</div>';
		}

		ob_start();
		?>
			<form method="post" class="wsx-lost-password-form">
				<p><?php esc_html_e( 'Lost your password? Please enter your email address. You will receive a link to create a new password via email.', 'wholesalex' ); ?></p>
				<p>
					<input class="wsx-input" type="email" name="user_email" id="user_email" placeholder="<?php esc_attr_e( 'Your email address', 'wholesalex' ); ?>" required>
				</p>
				<p>
					<input type="submit" class="wsx-input wsx-password-reset-btn" name="wholesalex_reset_password" value="<?php esc_attr_e( 'Reset Password', 'wholesalex' ); ?>">
				</p>
			</form>
		<?php
		return ob_get_clean();
	}


	/**
	 * Handle form submission and trigger password reset email
	 *
	 * @return void
	 */
	public function wholesalex_handle_password_reset() {
		if ( isset( $_POST['wholesalex_reset_password'], $_POST['wholesalex_reset_password_nonce'] ) && is_string( $_POST['wholesalex_reset_password_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wholesalex_reset_password_nonce'] ) ), 'wholesalex_reset_password_action' ) ) {
			if ( isset( $_POST['user_email'] ) ) {
				$email = sanitize_email( wp_unslash( $_POST['user_email'] ) );
			} else {
				$email = '';
			}

			if ( empty( $email ) ) {
				wc_add_notice( 'Please enter a valid email address.', 'error' );
				return;
			}

			// Check if user exists.
			$user = get_user_by( 'email', $email );

			if ( ! $user ) {
				wc_add_notice( 'No user found with this email address.', 'error' );
				return;
			}

			// Generate password reset key.
			$key = get_password_reset_key( $user );
			if ( is_wp_error( $key ) ) {
				wc_add_notice( $key->get_error_message(), 'error' );
				return;
			}
			$reset_link = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );
			$this->wholesalex_send_password_reset_email( $user->user_login, $user->user_email, $reset_link );
			wp_safe_redirect( add_query_arg( 'reset', 'true', get_permalink() ) );
			exit;
		}
	}

	/**
	 * Send password reset email
	 *
	 * @param string $user_login User login.
	 * @param string $user_email User email.
	 * @param string $reset_link Reset link.
	 * @return void
	 */
	public function wholesalex_send_password_reset_email( $user_login, $user_email, $reset_link ) {
		$mailer        = WC()->mailer();
		$email_heading = esc_html__( 'Password Reset Request', 'wholesalex' );
		ob_start();
		?>
		<p><?php printf( /* translators: %s: User login name */ esc_html__( 'Hi %s,', 'wholesalex' ), esc_html( $user_login ) ); ?></p>
		<p>
		<?php
		printf(
			/* translators: %s: User login name */
			esc_html__( 'Someone has requested a new password for the following account on %s:', 'wholesalex' ),
			esc_html( get_bloginfo( 'name' ) )
		);
		?>
		</p>
		<p><strong><?php esc_html_e( 'Username:', 'wholesalex' ); ?></strong> <?php echo esc_html( $user_login ); ?></p>
		<p><?php esc_html_e( 'If you didn’t make this request, just ignore this email. If you’d like to proceed:', 'wholesalex' ); ?></p>
		<p><a class="wsx-link" href="<?php echo esc_url( $reset_link ); ?>"><?php esc_html_e( 'Click here to reset your password', 'wholesalex' ); ?></a></p>
		<p><?php esc_html_e( 'Thanks for reading.', 'wholesalex' ); ?></p>
		<?php
		$message = ob_get_clean();
		$mailer->send(
			sanitize_email( $user_email ),
			esc_html__( 'Password Reset Request', 'wholesalex' ),
			$mailer->wrap_message( $email_heading, $message )
		);
	}


	/**
	 * Enqueue Form Scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		$is_elementor_builder  = isset( $_GET['elementor-preview'] ) && sanitize_text_field( wp_unslash( $_GET['elementor-preview'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder preview detection.
		$is_breakdance_builder = isset( $_GET['breakdance_iframe'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['breakdance_iframe'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only builder preview detection.

		if ( has_shortcode( $post->post_content, 'wholesalex_registration' ) || has_shortcode( $post->post_content, 'wholesalex_login_registration' ) || has_shortcode( $post->post_content, 'wholesalex_login' ) || ( function_exists( 'has_block' ) && has_block( 'wholesalex/forms' ) ) || apply_filters( 'wholesalex_form_content_requires_style', false, $post->post_content ) || $is_breakdance_builder || $is_elementor_builder ) {
			wp_enqueue_style( 'whx_form', WHOLESALEX_URL . 'assets/css/whx_form.css', array(), WHOLESALEX_VER );
		}
	}

	/**
	 * Current Page Password Lost Check
	 *
	 * @return bool
	 */
	public function check_current_page_is_lost_password() {
		$current_url = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ? 'https' : 'http' ) . '://' . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . ( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		if ( strpos( $current_url, 'lost-password' ) !== false || strpos( $current_url, '?reset=true' ) !== false ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * From Validation
	 *
	 * @param mixed $columns columns.
	 * @return mixed
	 */
	private function is_form_row_valid( $columns ) {
		$status = false;
		foreach ( $columns as $field ) {
			$status = isset( $field['status'] ) ? $field['status'] : true;
			if ( $status ) {
				break;
			}
		}

		return $status;
	}

	/**
	 * Get Select Role Field
	 *
	 * @param bool $is_only_b2b is only b2b.
	 * @return mixed
	 */
	private function get_select_role_field( $is_only_b2b = false ) {
		$__roles        = wholesalex()->get_roles( 'store_mode_roles_option' );
		$__roles_option = array(
			array(
				'name'  => __( 'Select Role', 'wholesalex' ),
				'value' => '',
			),
		);
		foreach ( $__roles as $id => $role ) {
			if ( $is_only_b2b && isset( $role['value'] ) && 'wholesalex_b2c_users' === $role['value'] ) {
				continue;
			}
			if ( isset( $role['value'] ) && 'wholesalex_guest' !== $role['value'] ) {
				array_push( $__roles_option, $role );
			}
		}
		$__select_role_dropdown = array(
			'id'       => 9999999,
			'type'     => 'select',
			'label'    => apply_filters( 'wholesalex_global_registration_form_select_roles_title', __( 'Select Registration Roles', 'wholesalex' ) ),
			'name'     => 'wholesalex_registration_role',
			'option'   => $__roles_option,
			'empty'    => true,
			'required' => true,
		);

		$field = array(
			'id'            => 'wsx-select-role',
			'type'          => 'row',
			'columns'       => array( $__select_role_dropdown ),
			'isMultiColumn' => false,
		);

		return $field;
	}

	/**
	 * Identify the saved form. Submitted role lists are never authorization data.
	 *
	 * @param string $scope Configured registration_role attribute.
	 * @return void
	 */
	private function render_registration_role_context_fields( $scope ) {
		$form_id = Registration_Context::form_id( get_queried_object_id(), $scope );
		?>
		<input type="hidden" name="wholesalex_registration_form" value="<?php echo esc_attr( $form_id ); ?>" />
		<input type="hidden" name="wholesalex_registration_form_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wholesalex-registration-form|' . $form_id ) ); ?>" />
		<?php
	}

	/**
	 * Generate Form Field
	 *
	 * @param mixed $row row.
	 * @param mixed $is_role_wise is rolewise.
	 * @param mixed $input_variation input variation.
	 * @param mixed $is_only_b2b is only b2b.
	 * @return void
	 */
	private function render_columns( $row, $is_role_wise, $input_variation, $is_only_b2b = false ) {
		$columns            = $row['columns'];
		$multi_column_class = isset( $row['isMultiColumn'] ) && $row['isMultiColumn'] ? 'double-column' : '';

		if ( $this->is_form_row_valid( $columns ) ) {

			$row_class = "wsx-reg-form-row {$multi_column_class}";
			?>
			<div class="<?php echo esc_attr( $row_class ); ?>">
				<?php
				foreach ( $columns as $field ) {
					$exclude = $this->check_depends( $field );
					if ( $is_role_wise ) {
						$exclude_roles = explode( ' ', $exclude );
						if ( in_array( $is_role_wise, $exclude_roles, true ) ) {
							continue;
						}
					}
					$required_class = isset( $field['required'] ) && $field['required'] ? 'wsx-field-required' : '';
					$display_none   = ( $exclude && ! $is_role_wise ) ? 'display:none' : '';
					$field_name     = $field['name'];
					$field_position = isset( $field['columnPosition'] ) ? $field['columnPosition'] : 'left';
					$field_class    = "wholesalex-registration-form-column {$field_position} wsx-field {$required_class} wsx-field-{$field_name}";
					?>
						<div data-wsx-exclude="<?php echo esc_attr( $exclude ); ?>" class="<?php echo esc_attr( $field_class ); ?>" style="<?php echo esc_attr( $display_none ); ?>">

						<?php
						$this->registration_form_felds_name[] = $field_name;
						$this->generate_form_field( $field, $is_role_wise, $input_variation, $is_only_b2b );
						?>
						</div>
						<?php
				}
				?>
			</div>
			<?php

		}
	}

	/**
	 * Render the form based on the type and provided data.
	 *
	 * @param string $type The type of form (registration or login).
	 * @param array  $form_data The form data.
	 * @param array  $input_variation The input variation.
	 * @param bool   $is_rolewise Whether the form is role-specific.
	 * @param bool   $is_only_b2b Whether the form is only for B2B.
	 * @param string $role The role for the form.
	 */
	private function render_form( $type, $form_data, $input_variation, $is_rolewise = false, $is_only_b2b = false, $role = '' ) {
		$this->registration_form_felds_name = array();
		$default_form                       = WholesaleX_CommonUtils::get_empty_form();
		$initial_form_data                  = WholesaleX_CommonUtils::get_default_registration_form_fields();
		if ( 'registration' === $type ) {
			$enctype             = 'multipart/form-data';
			$wrapper_class       = 'wsx-reg-fields';
			$heading_class       = 'wsx-reg-form-heading';
			$heading_title_class = 'wsx-reg-form-heading-text';
			$heading_desc_class  = 'wholesalex-registration-form-subtitle-text';
			$button_class        = 'wsx-register-btn';
			$header              = $form_data['registrationFormHeader'];
			$button              = $form_data['registrationFormButton'];
			$fields              = ( isset( $form_data['registrationFields'] ) ? $form_data['registrationFields'] : $initial_form_data );
		} else {
			$enctype             = 'application/x-www-form-urlencoded';
			$wrapper_class       = 'wsx-login-fields';
			$heading_class       = 'wholesalex-login-form-title';
			$heading_title_class = 'wsx-login-form-title-text';
			$heading_desc_class  = 'wholesalex-login-form-subtitle-text';
			$button_class        = 'wsx-login-btn';
			$header              = $form_data['loginFormHeader'];
			$fields              = $default_form['loginFields'];
			$button              = $form_data['loginFormButton'];
		}

		$allowed_html = array(
			'div'    => array(
				'class' => array(),
			),
			'span'   => array(
				'class' => array(),
			),
			'button' => array(
				'class' => array(),
			),
		);

		?>
	<form class="wholesalex-<?php echo esc_attr( $type ); ?>-form" enctype="<?php echo esc_attr( $enctype ); ?>">
		<div class="wsx-form-field-warning-message other_error" role="alert" aria-live="polite"></div>
		<?php
		if ( isset( $form_data['settings']['isShowFormTitle'] ) && $form_data['settings']['isShowFormTitle'] ) {
			$output  = '<div class="%s">';
			$output .= '<div class="%s">%s</div>';

			if ( isset( $header['isHideDescription'] ) && ! $header['isHideDescription'] ) {
				$output .= '<div class="%s">%s</div>';
			}

			$output .= '</div>';

			echo wp_kses(
				sprintf(
					$output,
					esc_attr( $heading_class ),
					esc_attr( $heading_title_class ),
					isset( $header['title'] ) ? esc_html( $header['title'] ) : '',
					isset( $header['description'] ) ? esc_attr( $heading_desc_class ) : '',
					isset( $header['description'] ) ? esc_html( $header['description'] ) : ''
				),
				$allowed_html
			);
		}
		?>
		<div class="wholesalex-fields-wrapper <?php echo esc_attr( $wrapper_class ); ?> wsx-fields-container">
		<?php
		foreach ( $fields as $row ) {
			$this->render_columns( $row, $is_rolewise, $input_variation, $is_only_b2b );
		}
		if ( 'registration' === $type ) {
			if ( $is_rolewise ) {
				?>
				<input type="hidden" name="wholesalex_registration_role" value="<?php echo esc_attr( $role ); ?>">
				<?php
			} elseif ( ! in_array( 'wholesalex_registration_role', $this->registration_form_felds_name, true ) ) {
				$select_role_field = $this->get_select_role_field( $is_only_b2b );
				$this->render_columns( $select_role_field, $is_rolewise, $input_variation );
			}
			$this->render_registration_role_context_fields( $role );
		}
		?>
		</div>

		<input type="hidden" name="action" value="wholesalex_process_<?php echo esc_attr( $type ); ?>" />

		<?php
		do_action( 'wholesalex_' . $type . '_form' );
		wp_nonce_field( 'wholesalex-' . $type, 'wholesalex-' . $type . '-nonce' );
		$align_class   = isset( $form_data['styles']['layout']['button']['align'] ) ? sanitize_html_class( $form_data['styles']['layout']['button']['align'] ) : '';
		$button_class .= ' ' . $align_class;

		$output = sprintf(
			'<div class="%s"><button class="%s">%s</button></div>',
			esc_attr( 'wsx-form-btn-wrapper' ),
			esc_attr( $button_class ),
			isset( $button['title'] ) ? esc_html( $button['title'] ) : ''
		);

		echo wp_kses( $output, $allowed_html );

		if ( 'login' === $type ) {
			?>
			<div class="wsx-reg-form-row ">
				<div class="wholesalex-registration-form-column left wsx-field woocommerce-LostPassword lost_password">
					<a class="wsx-link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'wholesalex' ); ?></a>
				</div>
			</div>
			<?php
		}
		?>
	</form>
		<?php
	}


	/**
	 * Get Form Style
	 *
	 * @param mixed $atts form array.
	 * @return array
	 */
	private function render_registration_shortcode( $atts = array() ) {
		$atts            = array_change_key_case( (array) $atts, CASE_LOWER );
		$form_data       = WholesaleX_CommonUtils::get_new_form_builder_data();
		$input_variation = $form_data['settings']['inputStyle'] ?? 'variation_1';
		$is_role_wise    = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' !== $atts['registration_role'] && 'global' !== $atts['registration_role'] ? $atts['registration_role'] : false;
		$is_only_b2b     = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' === $atts['registration_role'] ? $atts['registration_role'] : false;
		$wrapper         = wp_unique_id( 'whx_wrapper' );

		ob_start();
		if ( ! wp_style_is( 'whx_form' ) ) {
			wp_enqueue_style( 'whx_form', WHOLESALEX_URL . 'assets/css/whx_form.css', array(), WHOLESALEX_VER );
		}
		$this->load_form_js( $wrapper );
		$this->add_form_inline_css( $this->get_vars_css( $this->get_form_style( $form_data['style'], $form_data['loginFormHeader']['styles'], $form_data['registrationFormHeader']['styles'], $form_data['settings'] ) ) );

		do_action( 'wholesalex_before_registration_form_render' );

		?>
			<div id="<?php echo esc_attr( $wrapper ); ?>" class="wholesalex-form-wrapper wsx-form-wrapper_frontend wsx-without-login wsx_<?php echo esc_attr( $input_variation ); ?>">
			<div class="wholesalex_circular_loading__wrapper">
				<div class="wholesalex_loading_spinner">
					<svg viewBox="25 25 50 50" class="move_circular">
						<circle
							cx="50"
							cy="50"
							r="20"
							fill="none"
							class="wholesalex_circular_loading_icon"
						></circle>
					</svg>
				</div>
			</div>
			<?php $this->render_form( 'registration', $form_data, $input_variation, $is_role_wise, $is_only_b2b, isset( $atts['registration_role'] ) ? $atts['registration_role'] : '' ); ?>
			</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Login Registration Shortcode
	 *
	 * @param mixed $atts form array.
	 * @return array
	 */
	private function render_login_registration_shortcode( $atts = array() ) {
		$form_data = WholesaleX_CommonUtils::get_new_form_builder_data();

		if ( ! $this->is_login_form_enabled( $form_data ) ) {
			return $this->render_registration_shortcode( $atts );
		}

		$input_variation = $form_data['settings']['inputStyle'] ?? 'variation_1';

		$is_role_wise = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' !== $atts['registration_role'] && 'global' !== $atts['registration_role'] ? $atts['registration_role'] : false;
		$is_only_b2b  = isset( $atts['registration_role'] ) && ! empty( $atts['registration_role'] ) && 'all_b2b' === $atts['registration_role'] ? $atts['registration_role'] : false;
		$wrapper      = wp_unique_id( 'whx_wrapper' );

		ob_start();
		if ( ! wp_style_is( 'whx_form' ) ) {
			wp_enqueue_style( 'whx_form', WHOLESALEX_URL . 'assets/css/whx_form.css', array(), WHOLESALEX_VER );
		}
		$this->load_form_js( $wrapper );

		$this->add_form_inline_css( $this->get_vars_css( $this->get_form_style( $form_data['style'], $form_data['loginFormHeader']['styles'], $form_data['registrationFormHeader']['styles'], $form_data['settings'] ) ) );

		do_action( 'wholesalex_before_registration_form_render' );

		?>

			<div id="<?php echo esc_attr( $wrapper ); ?>" class="wholesalex-form-wrapper wsx-form-wrapper_frontend wsx_<?php echo esc_attr( $input_variation ); ?>">
			<div class="wholesalex_circular_loading__wrapper">
				<div class="wholesalex_loading_spinner">
					<svg viewBox="25 25 50 50" class="move_circular">
						<circle
							cx="50"
							cy="50"
							r="20"
							fill="none"
							class="wholesalex_circular_loading_icon"
						></circle>
					</svg>
				</div>
			</div>
			<?php $this->render_form( 'login', $form_data, $input_variation ); ?>
			<span class='wsx-form-separator'></span>
			<?php $this->render_form( 'registration', $form_data, $input_variation, $is_role_wise, $is_only_b2b, isset( $atts['registration_role'] ) ? $atts['registration_role'] : '' ); ?>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check whether the saved builder configuration allows the login form.
	 *
	 * @param array $form_data Form builder data.
	 * @return bool
	 */
	private function is_login_form_enabled( $form_data ) {
		return isset( $form_data['settings']['isShowLoginForm'] ) && rest_sanitize_boolean( $form_data['settings']['isShowLoginForm'] );
	}

	/**
	 * Render Login Shortcode
	 *
	 * @return array
	 */
	private function render_login_shortcode() {
		$form_data       = WholesaleX_CommonUtils::get_new_form_builder_data();
		$input_variation = $form_data['settings']['inputStyle'] ?? 'variation_1';

		$wrapper = wp_unique_id( 'whx_wrapper' );

		ob_start();
		if ( ! wp_style_is( 'whx_form' ) ) {
			wp_enqueue_style( 'whx_form', WHOLESALEX_URL . 'assets/css/whx_form.css', array(), WHOLESALEX_VER );
		}
		$this->load_form_js( $wrapper );
		$this->add_form_inline_css( $this->get_vars_css( $this->get_form_style( $form_data['style'], $form_data['loginFormHeader']['styles'], $form_data['registrationFormHeader']['styles'], $form_data['settings'] ) ) );
		do_action( 'wholesalex_before_registration_form_render' );
		?>
			<div id="<?php echo esc_attr( $wrapper ); ?>" class="wholesalex-form-wrapper wsx-form-wrapper_frontend wsx_<?php echo esc_attr( $input_variation ); ?>">
			<div class="wholesalex_circular_loading__wrapper">
				<div class="wholesalex_loading_spinner">
					<svg viewBox="25 25 50 50" class="move_circular">
						<circle
							cx="50"
							cy="50"
							r="20"
							fill="none"
							class="wholesalex_circular_loading_icon"
						></circle>
					</svg>
				</div>
			</div>
			<?php $this->render_form( 'login', $form_data, $input_variation ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Registration Form
	 *
	 * @param array $atts    Shortcode attributes. Default empty.
	 * @return string Shortcode output.
	 * @since 1.0.0
	 */
	public function registration_shortcode( $atts = array() ) {
		if ( is_user_logged_in() && is_singular() ) {
			$__form_view_for_logged_in_user = wholesalex()->get_setting( '_settings_show_form_for_logged_in', 'yes' );

			$__message_for_logged_in_user = wholesalex()->get_setting( '_settings_message_for_logged_in_user' );
			if ( 'yes' !== $__form_view_for_logged_in_user ) {
				if ( is_admin() || ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_print_notices' ) ) {
					return;
				}
				?>
				<div>
				<?php
				wc_add_notice( $__message_for_logged_in_user, 'error' );
				wc_print_notices();
				?>
					<a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>"> <?php echo esc_html( wholesalex()->get_language_n_text( '_language_logout_to_see_this_form', __( 'Logout to See this form', 'wholesalex' ) ) ); ?></a>
					</div>
				<?php
				return;
			}
		}
		if ( $this->check_current_page_is_lost_password() ) {
			return $this->render_registration_shortcode( $atts );
		} else {
			return '';
		}
	}
	/**
	 * Login And Registration Form
	 *
	 * @param  array $atts Shortcode Attributes. Default Empty.
	 * @return string Shortcode output.
	 * @since 1.0.1
	 * @since 1.2.4 _settings_redirect_url_login Field Deafult Settings Param Added
	 */
	public function login_registration_shortcode( $atts = array() ) {

		$atts = array_merge(
			array(
				'lost_password' => 'false',
			),
			$atts
		);

		if ( is_user_logged_in() && is_singular() ) {
			$__form_view_for_logged_in_user = wholesalex()->get_setting( '_settings_show_form_for_logged_in', 'yes' );
			$__message_for_logged_in_user   = wholesalex()->get_setting( '_settings_message_for_logged_in_user' );
			if ( 'yes' !== $__form_view_for_logged_in_user ) {
				if ( is_admin() || ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_print_notices' ) ) {
					return;
				}
				?>
				<div>
				<?php
				wc_add_notice( $__message_for_logged_in_user, 'error' );
				wc_print_notices();
				?>
					<a class="wsx-link" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>"> <?php echo esc_html( wholesalex()->get_language_n_text( '_language_logout_to_see_this_form', __( 'Logout to See this form', 'wholesalex' ) ) ); ?></a>
					</div>
				<?php
				return;
			}
		}
		if ( $this->check_current_page_is_lost_password() ) {
			return $this->render_login_registration_shortcode( $atts );
		} elseif ( 'true' === $atts['lost_password'] ) {
				return $this->wholesalex_forgot_password_form();
		} elseif ( 'true' === $atts['lost_password'] ) {
				return $this->wholesalex_forgot_password_form();
		} else {
			return '';
		}
	}

	/**
	 * Login Form
	 *
	 * @param  array $atts Shortcode Attributes. Default Empty.
	 * @return string Shortcode output.
	 * @since 1.4.9
	 * @since 1.4.9 _settings_redirect_url_login Field Default Settings Param Added
	 */
	public function login_shortcode( $atts = array() ) {
		if ( is_user_logged_in() && is_singular() ) {
			$__form_view_for_logged_in_user = wholesalex()->get_setting( '_settings_show_form_for_logged_in', 'yes' );
			$__message_for_logged_in_user   = wholesalex()->get_setting( '_settings_message_for_logged_in_user' );
			if ( 'yes' !== $__form_view_for_logged_in_user ) {
				if ( is_admin() || ! function_exists( 'wc_add_notice' ) || ! function_exists( 'wc_print_notices' ) ) {
					return;
				}
				?>
				<div>
				<?php
				wc_add_notice( $__message_for_logged_in_user, 'error' );
				wc_print_notices();
				?>
					<a class="wsx-link" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>"> <?php echo esc_html( wholesalex()->get_language_n_text( '_language_logout_to_see_this_form', __( 'Logout to See this form', 'wholesalex' ) ) ); ?></a>
					</div>
				<?php
				return;
			}
		}
		if ( $this->check_current_page_is_lost_password() ) {
			return $this->render_login_shortcode( $atts );
		} elseif ( 'true' === $atts['lost_password'] ) {
				return $this->wholesalex_forgot_password_form();
		} elseif ( 'true' === $atts['lost_password'] ) {
				return $this->wholesalex_forgot_password_form();
		} else {
			return '';
		}
	}

	/**
	 * Load Form JS
	 *
	 * @param string $wrapper is the wrapper id.
	 * @return void
	 * @since 1.0.0
	 */
	public function load_form_js( $wrapper = '' ) {
		add_action(
			'wp_footer',
			function () use ( $wrapper ) {
				$this->form_js( $wrapper );
			}
		);
	}

	/**
	 * Form JS
	 *
	 * @param string $wrapper is the wrapper id.
	 * @return void
	 * @since 1.0.0
	 */
	public function form_js( $wrapper ) {
		$form_data           = WholesaleX_CommonUtils::get_new_form_builder_data();
		$initial_form_data   = WholesaleX_CommonUtils::get_default_registration_form_fields();
		$registration_fields = ( isset( $form_data['registrationFields'] ) ? $form_data['registrationFields'] : $initial_form_data );

		$password_condition = array();
		$password_message   = '';
		foreach ( $registration_fields as $row ) {
			$columns = $row['columns'];
			foreach ( $columns as $field ) {
				if ( isset( $field['status'] ) && $field['status'] ) {
					if ( 'user_pass' === $field['name'] && isset( $field['passwordStrength'] ) ) {
						foreach ( $field['passwordStrength'] as $value ) {
							$password_condition[] = $value['value'];
						}
						$password_message = isset( $field['password_strength_message'] ) ? $field['password_strength_message'] : '';
					}
				}
			}
		}
		$conditions_js_array = '["' . implode( '", "', $password_condition ) . '"]';

		?>
		<script type="text/javascript">
			(function ($) {
				'use strict';
				/**
				 * All of the code for your public-facing JavaScript source
				 * should reside in this file.
				 */
				var password_message = <?php echo wp_json_encode( $password_message ); ?>;
				var wrapper = $(`#<?php echo esc_attr( $wrapper ); ?>`);

				$(document).ready(function() {

					password_message = <?php echo wp_json_encode( $password_message ); ?>;
					wrapper = $(`#<?php echo esc_attr( $wrapper ); ?>`);

					if(! wrapper) {
						return;
					}

					const controlRegistrationForm = ()=>{
					// Check User Role Selection Field
						let selectedRole = wrapper.find('#wholesalex_registration_role').val();
						if(selectedRole) {
							let whxCustomFields = wrapper.find('.wsx-field');
							whxCustomFields.each(function (i) {
								let excludeRoles = this.getAttribute('data-wsx-exclude');
								if(excludeRoles) {
									excludeRoles = excludeRoles.split(' ');
									if(!excludeRoles.includes(selectedRole)) {
										$(this).show();
										$(this).find('.wsx-field-required').prop('required','true');
									} else {
										$(this).hide();
										$(this).find('.wsx-field-required').removeAttr('required');
									}
								}
							});
						} else {
							$(".wsx-field[style*='display: none'] > .wsx-field-required").removeAttr("required");
						}
					}

					const checkConfirmPassword = ()=>{
						wrapper.find("#user_confirm_pass").prop('required',true);
						let confirmPassword = wrapper.find("#user_confirm_pass").val();
						let password = wrapper.find("#reg_password").val(); //woocommerce password
						let whxFormPassword = wrapper.find("#user_pass").val();

						if(password && password.length) {
							wrapper.find('.woocommerce-form-register__submit').prop('disabled',true); // Disable Register button
						}

						if(whxFormPassword && whxFormPassword.length) {
							wrapper.find('.wsx-register-btn').prop('disabled',true); // Disable Register button
						}


						if(!confirmPassword) {
							confirmPassword = wrapper.find("#user_confirm_password").val();
						}


						if( confirmPassword ) {
							// For WC
							if( confirmPassword!==password) {
								wrapper.find(".whx-field-error.user_confirm_pass").empty();
								wrapper.find(".whx-field-error.user_confirm_pass").append("Password and Confirm Password Does not match!");
							} else {
								wrapper.find(".whx-field-error.user_confirm_pass").empty();
								wrapper.find('.woocommerce-form-register__submit').prop('disabled',false);
							}

							// For WholesaleX Form
							if(confirmPassword !=whxFormPassword) {
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_pass`).empty();
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_pass`).empty();

								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_password`).empty();
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_pass`).empty();

								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_pass`).append('Password and Confirm Password Does not match!');
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_password`).append('Password and Confirm Password Does not match!');
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_pass`).append('Password and Confirm Password Does not match!');

							} else {
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_pass`).empty();
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_pass`).empty();
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_password`).empty();
								wrapper.find('.wsx-register-btn').prop('disabled',false); // Disable Register button

							}
						} else {
							wrapper.find('.wsx-register-btn').prop('disabled',false); // Disable Register button
						}
					}

					const checkConfirmEmail = ()=>{

						wrapper.find("#user_confirm_email").prop('required',true);
						let confirmEmail = wrapper.find("#user_confirm_email").val();
						let email = wrapper.find("#reg_email").val(); //woocommerce password
						let whxFormEmail = wrapper.find("#user_email").val();


						if(email && email.length) {
							wrapper.find('.woocommerce-form-register__submit').prop('disabled',true); // Disable Register button
						}

						if(whxFormEmail && whxFormEmail.length) {
							wrapper.find('.wsx-register-btn').prop('disabled',true); // Disable Register button
						}

						if( confirmEmail ) {

							// For WC
							if( confirmEmail!==email) {
								wrapper.find(".whx-field-error.user_confirm_email").empty();
								wrapper.find(".whx-field-error.user_confirm_email").append("Email and Confirm Email Does not match!");
							} else {
								wrapper.find(".whx-field-error.user_confirm_email").empty();
								wrapper.find('.woocommerce-form-register__submit').prop('disabled',false);
							}

							// For WholesaleX Form
							if(confirmEmail !=whxFormEmail) {
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_email`).empty();
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_email`).empty();

								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_email`).append('Email and Confirm Email Does not match!');
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_email`).append('Email and Confirm Email Does not match!');
								// $('.wsx-register-btn').prop('disabled',true); // Disable Register button


							} else {
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_confirm_email`).empty();
								wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.user_email`).empty();
								wrapper.find('.wsx-register-btn').prop('disabled',false); // Disable Register button

							}
						} else {
							wrapper.find('.wsx-register-btn').prop('disabled',false); // Disable Register button
						}
					}

					const checkRequiredField = ()=> {
						let isValid=true;
						wrapper.find(".wsx-field-required input, .wsx-field-required select, .wsx-field-required textarea").on('focusout input', function() {
							// Check the validity of the current field
							let fieldValue = $(this).val().trim();
							let fieldName = $(this).attr("name").replace(/\[\]/g, '');

							if (!fieldValue) {
								isValid = false;
								wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')} ${wholesalex.is_required}!`);
								wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

							} else {
								wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).text("");
								wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

							}
						});

						// Validate at least one checkbox is checked in each checkbox group
						wrapper.find(".wsx-field-required .wsx-form-checkbox").each(function () {
							let checkboxes = $(this).find("input[type='checkbox']");
							let checkboxGroupName = $(this).find("input[type='checkbox']").attr("name").replace(/\[\]/g, '');

							if (checkboxes.length > 0 && checkboxes.filter(":checked").length === 0) {
								isValid = false;
							} else {
								// Clear warning message for the checkbox group
								wrapper.find(".wsx-form-field-warning-message." + checkboxGroupName).text("");
							}
						});


					}
					// Function to validate the password
					function validatePassword(password, conditions) {
						var messages = [];
						if (conditions.includes('uppercase_condition')) {
							if (!/[A-Z]/.test(password)) {
								messages.push('At least one uppercase letter <br>');
							}
						}
						if (conditions.includes('lowercase_condition')) {
							if (!/[a-z]/.test(password)) {
								messages.push('At least one lowercase letter <br>');
							}
						}
						if (conditions.includes('special_character_condition')) {
							if (!/[!@#$%^&*()_+=\\-]/.test(password)) {
								messages.push('At least one special character  <br>');
							}
						}
						if (conditions.includes('min_length_condition')) {
							if (password.length < 8) {
								messages.push('Minimum 8 characters <br>');
							}
						}
						return messages;
					}
					//Check Password Validation
					const checkPassWordRequiredFields = ()=> {
						let isPasswordValid = true;
						wrapper.find(".wsx-field-required input, .wsx-field-required select, .wsx-field-required textarea, .wsx-field-required radio").each(function() {
						let fieldName = $(this).attr("name");
								if(fieldName) {
									fieldName = fieldName.replace(/\[\]/g, '');
								}
						if ( $(this).attr("name") === 'user_pass') {
							let passwordConditions = <?php echo wp_json_encode( $conditions_js_array ); ?>;
								// Validate button click event
								let password = $(this).val();
								let validationMessages = validatePassword( password, passwordConditions );
								if (validationMessages.length > 0) {
									isPasswordValid = false;
									if(wrapper.find(`.wsx-field-${fieldName}`).css('display') !== 'none') {
										const $warningMessage = wrapper.find(`.wsx-form-field-warning-message.${fieldName}`);
										(password_message && password_message.length > 0) ?  $warningMessage.text(password_message) : $warningMessage.html(validationMessages);
										wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');
								}
								} else {
									wrapper.find('#message').html('Password is valid!').css('color', 'green');
								}
						}
					});
						return isPasswordValid;
					}

					const checkRequiredFields = ()=> {

						// Validate required fields
						let isValid = true;


						wrapper.find(".wsx-field-required input, .wsx-field-required select, .wsx-field-required textarea, .wsx-field-required radio").each(function() {
							if ($(this).val().trim() === "") {
								let fieldName = $(this).attr("name");
								if(fieldName) {
									fieldName = fieldName.replace(/\[\]/g, '');
								}

								if(wrapper.find(`.wsx-field-${fieldName}`).css('display') !== 'none') {
										isValid = false;
										wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')} ${wholesalex.is_required}!`);
										wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');
								}
							} else {
								// $(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

							}
						});

						// Validate at least one checkbox is checked in each checkbox group
						wrapper.find(".wsx-field-required .wsx-form-checkbox").each(function () {
							let checkboxes = $(this).find("input[type='checkbox']");
							let fieldName = '';
							if(checkboxes.length) {
								fieldName = checkboxes[0].name;
								fieldName = fieldName.replace(/\[\]/g, '');
							}

							if(wrapper.find(`.wsx-field-${fieldName}`).css('display') !== 'none') {

								if (checkboxes.length > 0 && checkboxes.filter(":checked").length === 0) {
									isValid = false;

									wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')}${wholesalex.is_required}!`);
									wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

								} else {
									wrapper.find(this).closest('.wsx-form-field').find('.wsx-form-field-warning-message').text("");
									wrapper.find(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

								}

							}

						});
						// Validate at least one checkbox is checked in each checkbox group
						wrapper.find(".wsx-field-required .wsx-field-radio").each(function () {
							let checkboxes = $(this).find("input[type='radio']");
							let fieldName = '';
							if(checkboxes.length) {
								fieldName = checkboxes[0].name;
								fieldName = fieldName.replace(/\[\]/g, '');
							}

							if(wrapper.find(`.wsx-field-${fieldName}`).css('display') !== 'none') {

								if (checkboxes.length > 0 && checkboxes.filter(":checked").length === 0) {
									isValid = false;

									$(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')} ${wholesalex.is_required}!`);
									$(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

								} else {
									$(this).closest('.wsx-form-field').find('.wsx-form-field-warning-message').text("");
									$(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

								}

							}

						});

						return isValid;

					}
					const checkLoginRequiredFields = ()=> {

						// Validate required fields
						let isValid = true;


						wrapper.find(".wholesalex-login-form .wsx-field-required input").each(function() {
							if ($(this).val().trim() === "") {
								let fieldName = $(this).attr("name");
								if(fieldName) {
									fieldName = fieldName.replace(/\[\]/g, '');
								}

								if($(`.wsx-field-${fieldName}`).css('display') !== 'none') {
									isValid = false;
									$(`.wsx-form-field-warning-message.${fieldName}`).text(`${fieldName.replace('_', ' ')} ${wholesalex.is_required}!`);
									$(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');
								}
							} else {
								// $(`.wsx-form-field-warning-message.${fieldName}`).parent().find('.wsx-form-field').removeClass('wsx-field-warning');

							}
						});



						return isValid;

					}

					const handleHiddenRow = ()=>{
						wrapper.find('.wsx-reg-form-row').each(function () {
							const $row = $(this);
							const allChildrenHidden = $row.children().toArray().every(function (child) {
								return $(child).css('display') === 'none';
							});

							if (allChildrenHidden) {
								$row.css('display', 'none');
							} else {
								if ($row.hasClass('double-column')) {
									$row.css('display', 'flex');
								} else {
									$row.css('display', 'block');
								}
							}
						});
					}


		<?php do_action( 'wholesalex_registration_inline_script', $registration_fields ); ?>
					const processRegistration = (formObject)=>{
						const entries = Object.fromEntries(formObject.entries());

						// Check if reCAPTCHA is empty
						const recaptchaValue = entries['g-recaptcha-response'];

						if (typeof recaptchaValue === 'string' && recaptchaValue.trim() === '') {
							alert('Please complete the reCAPTCHA checkbox.');
							return;
						}
						wrapper.find('.wholesalex_circular_loading__wrapper').show();
						$.ajax({
							url: wholesalex.ajax,
							type: 'POST',
							data: formObject,
							contentType: false,
							processData: false,
							success: function (response) {
								if(Object.keys(response['data']['error_messages']).length) {
									const wc_notice = $('.woocommerce-notices-wrapper');
									if(wc_notice) {
										wc_notice.empty();
									}
									wrapper.find('.wholesalex-registration-form .wsx-form-field-warning-message').empty();
									Object.keys(response['data']['error_messages']).map((err)=>{
										wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.${err}`).append(response['data']['error_messages'][err]);
										wrapper.find(`.wholesalex-registration-form .wsx-form-field-warning-message.${err}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

										if(err=='recaptcha') {
												if(wc_notice) {
													wc_notice.append(response['data']['error_messages'][err]);
												}
											}
									})
								} else {
									if(response['data']['redirect']) {
										window.location.href = response['data']['redirect'];
									}
								}
								wrapper.find('.wholesalex_circular_loading__wrapper').hide();
							},
							error: function (jqXHR, textStatus, errorThrown) {
								wrapper.find('.wholesalex_circular_loading__wrapper').hide();
							}
						});
					}
					wrapper.find('#wholesalex_registration_role').change(controlRegistrationForm);

					wrapper.trigger('wholesalex:field-change');
					handleHiddenRow();

					wrapper.find('.wsx-field input, .wsx-field textarea, .wsx-field radio, .wsx-field select').on('change input',function(e){
						checkConfirmPassword();
						checkConfirmEmail();
						controlRegistrationForm();
						wrapper.trigger('wholesalex:field-change');
						checkRequiredField();
						handleHiddenRow();
					});


					checkConfirmPassword();
					checkConfirmEmail();


					// Process Registration
					wrapper.find('.wsx-register-btn').on('click',function(e){
						// e.preventDefault();

						if($(this).closest('form')[0].checkValidity()){
							e.preventDefault();
						}

						if(!checkRequiredFields()) {
							return;
						}
						if(!checkPassWordRequiredFields()) {
							return;
						}

						const formObject = new FormData(wrapper.find('.wholesalex-registration-form')[0]);


						// process_registration
						if (wholesalex.recaptcha_status === 'yes' && typeof grecaptcha !== 'undefined' ) {
								let site_key      = "<?php echo esc_attr( wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' ) ); ?>";
								grecaptcha.ready(function () {
									try {
										grecaptcha.execute(site_key, { action: 'submit' }).then(function (token) {
											formObject.append('token',token);
											processRegistration(formObject);
										});
									} catch (error) {
										processRegistration(formObject);
									}

								});

						} else {
							processRegistration(formObject);
						}
					});

					wrapper.find('.wholesalex-login-form').find('input, select').on('change input',function(e){
						wrapper.find('.wholesalex-login-form .wsx-form-field').removeClass('wsx-field-warning');
						wrapper.find('.wholesalex-login-form .wsx-form-field-warning-message').empty();
					});



					// Process Login
					wrapper.find('.wsx-login-btn').on('click',function(e){
						if($(this).closest('form')[0].checkValidity()){
							e.preventDefault();
						}

						if(!checkLoginRequiredFields()) {
							return;
						}
						const processLogin  = ()=>{
							$.ajax({
								url: wholesalex.ajax,
								type: 'POST',
								data: formObject,
								contentType: false,
								processData: false,
								success: function (response) {
									if(Object.keys(response['data']['error_messages']).length) {
										const wc_notice = $('.woocommerce-notices-wrapper');
										if(wc_notice) {
											wc_notice.empty();
										}
										wrapper.find('.wholesalex-login-form .wsx-form-field-warning-message').empty();
										Object.keys(response['data']['error_messages']).map((err)=>{
											wrapper.find(`.wholesalex-login-form .wsx-form-field-warning-message.${err}`).append(response['data']['error_messages'][err]);
											wrapper.find(`.wholesalex-login-form .wsx-form-field-warning-message.${err}`).parent().find('.wsx-form-field').addClass('wsx-field-warning');

											if(err=='recaptcha') {
												if(wc_notice) {
													wc_notice.append(response['data']['error_messages'][err]);
												}
											}
										})
									} else {
										if(response['data']['redirect']) {
											window.location.href = response['data']['redirect'];
										}
									}
								},
								error: function (jqXHR, textStatus, errorThrown) {
								}
							});
						}

						const formObject = new FormData(wrapper.find('.wholesalex-login-form')[0]);
							if (wholesalex.recaptcha_status === 'yes' && wholesalex.settings.recaptcha_version!="recaptcha_v2" && typeof grecaptcha !== 'undefined' ) {
								let site_key      = "<?php echo esc_attr( wholesalex()->get_setting( '_settings_google_recaptcha_v3_site_key' ) ); ?>";
								grecaptcha.ready(function () {
									try {
										grecaptcha.execute(site_key, { action: 'submit' }).then(function (token) {
											formObject.append('token',token);
											processLogin();
										});
									} catch (error) {
									}

								});
								processLogin();

						} else {
							processLogin();
						}
					});

				});


			})(jQuery);
		</script>
		<?php
	}



	/**
	 * Get Form Style
	 *
	 * @param  array $style Style Array.
	 * @param  array $login_header_style Login Header Style Array.
	 * @param  array $registration_header_style Registration Header Style Array.
	 * @param  array $settings Settings Array.
	 * @return array Style Array.
	 * @since 1.0.0
	 */
	public function get_form_style( $style, $login_header_style, $registration_header_style, $settings = array() ) {

		$_style = array(

			// Color
			// Field Sign Up Normal.

			'--wsx-input-color'                          => isset( $style['color']['field']['signUp']['normal']['text'] ) ? $style['color']['field']['signUp']['normal']['text'] : null,
			'--wsx-input-bg'                             => isset( $style['color']['field']['signUp']['normal']['background'] ) ? $style['color']['field']['signUp']['normal']['background'] : null,
			'--wsx-input-border-color'                   => isset( $style['color']['field']['signUp']['normal']['border'] ) ? $style['color']['field']['signUp']['normal']['border'] : null,
			'--wsx-input-placeholder-color'              => isset( $style['color']['field']['signUp']['normal']['placeholder'] ) ? $style['color']['field']['signUp']['normal']['placeholder'] : null,
			'--wsx-form-label-color'                     => isset( $style['color']['field']['signUp']['normal']['label'] ) ? $style['color']['field']['signUp']['normal']['label'] : null,

			// Field Sign Up Active.
			'--wsx-input-focus-color'                    => isset( $style['color']['field']['signUp']['active']['text'] ) ? $style['color']['field']['signUp']['active']['text'] : null,
			'--wsx-input-focus-bg'                       => isset( $style['color']['field']['signUp']['active']['background'] ) ? $style['color']['field']['signUp']['active']['background'] : null,
			'--wsx-input-focus-border-color'             => isset( $style['color']['field']['signUp']['active']['border'] ) ? $style['color']['field']['signUp']['active']['border'] : null,
			'--wsx-form-label-color-active'              => isset( $style['color']['field']['signUp']['active']['label'] ) ? $style['color']['field']['signUp']['active']['label'] : null,

			// Field Sign Up Warning.

			'--wsx-input-warning-color'                  => isset( $style['color']['field']['signUp']['warning']['text'] ) ? $style['color']['field']['signUp']['warning']['text'] : null,
			'--wsx-input-warning-bg'                     => isset( $style['color']['field']['signUp']['warning']['background'] ) ? $style['color']['field']['signUp']['warning']['background'] : null,
			'--wsx-input-warning-border-color'           => isset( $style['color']['field']['signUp']['warning']['border'] ) ? $style['color']['field']['signUp']['warning']['border'] : null,
			'--wsx-form-label-color-warning'             => isset( $style['color']['field']['signUp']['warning']['label'] ) ? $style['color']['field']['signUp']['warning']['label'] : null,

			// Field Sign In Normal.

			'--wsx-login-input-color'                    => isset( $style['color']['field']['signIn']['normal']['text'] ) ? $style['color']['field']['signIn']['normal']['text'] : null,
			'--wsx-login-input-bg'                       => isset( $style['color']['field']['signIn']['normal']['background'] ) ? $style['color']['field']['signIn']['normal']['background'] : null,
			'--wsx-login-input-border-color'             => isset( $style['color']['field']['signIn']['normal']['border'] ) ? $style['color']['field']['signIn']['normal']['border'] : null,
			'--wsx-login-input-placeholder-color'        => isset( $style['color']['field']['signIn']['normal']['placeholder'] ) ? $style['color']['field']['signIn']['normal']['placeholder'] : null,
			'--wsx-login-form-label-color'               => isset( $style['color']['field']['signIn']['normal']['label'] ) ? $style['color']['field']['signIn']['normal']['label'] : null,

			// Field Sign In Active.
			'--wsx-login-input-focus-color'              => isset( $style['color']['field']['signIn']['active']['text'] ) ? $style['color']['field']['signIn']['active']['text'] : null,
			'--wsx-login-input-focus-bg'                 => isset( $style['color']['field']['signIn']['active']['background'] ) ? $style['color']['field']['signIn']['active']['background'] : null,
			'--wsx-login-input-focus-border-color'       => isset( $style['color']['field']['signIn']['active']['border'] ) ? $style['color']['field']['signIn']['active']['border'] : null,
			'--wsx-login-form-label-color-active'        => isset( $style['color']['field']['signIn']['active']['label'] ) ? $style['color']['field']['signIn']['active']['label'] : null,

			// Field Sign In Warning.
			'--wsx-login-input-warning-color'            => isset( $style['color']['field']['signIn']['warning']['text'] ) ? $style['color']['field']['signIn']['warning']['text'] : null,
			'--wsx-login-input-warning-bg'               => isset( $style['color']['field']['signIn']['warning']['background'] ) ? $style['color']['field']['signIn']['warning']['background'] : null,
			'--wsx-login-input-warning-border-color'     => isset( $style['color']['field']['signIn']['warning']['border'] ) ? $style['color']['field']['signIn']['warning']['border'] : null,
			'--wsx-login-form-label-color-warning'       => isset( $style['color']['field']['signIn']['warning']['label'] ) ? $style['color']['field']['signIn']['warning']['label'] : null,

			// Button Sign UP Normal.
			'--wsx-form-button-color'                    => isset( $style['color']['button']['signUp']['normal']['text'] ) ? $style['color']['button']['signUp']['normal']['text'] : null,
			'--wsx-form-button-bg'                       => isset( $style['color']['button']['signUp']['normal']['background'] ) ? $style['color']['button']['signUp']['normal']['background'] : null,
			'--wsx-form-button-border-color'             => isset( $style['color']['button']['signUp']['normal']['border'] ) ? $style['color']['button']['signUp']['normal']['border'] : null,

			// Button Sign UP Hover.
			'--wsx-form-button-hover-color'              => isset( $style['color']['button']['signUp']['hover']['text'] ) ? $style['color']['button']['signUp']['hover']['text'] : null,
			'--wsx-form-button-hover-bg'                 => isset( $style['color']['button']['signUp']['hover']['background'] ) ? $style['color']['button']['signUp']['hover']['background'] : null,
			'--wsx-form-button-hover-border-color'       => isset( $style['color']['button']['signUp']['hover']['border'] ) ? $style['color']['button']['signUp']['hover']['border'] : null,

			// Button Sign In Normal.
			'--wsx-login-form-button-color'              => isset( $style['color']['button']['signIn']['normal']['text'] ) ? $style['color']['button']['signIn']['normal']['text'] : null,
			'--wsx-login-form-button-bg'                 => isset( $style['color']['button']['signIn']['normal']['background'] ) ? $style['color']['button']['signIn']['normal']['background'] : null,
			'--wsx-login-form-button-border-color'       => isset( $style['color']['button']['signIn']['normal']['border'] ) ? $style['color']['button']['signIn']['normal']['border'] : null,

			// Button Sign In Hover.
			'--wsx-login-form-button-hover-color'        => isset( $style['color']['button']['signIn']['hover']['text'] ) ? $style['color']['button']['signIn']['hover']['text'] : null,
			'--wsx-login-form-button-hover-bg'           => isset( $style['color']['button']['signIn']['hover']['background'] ) ? $style['color']['button']['signIn']['hover']['background'] : null,
			'--wsx-login-form-button-hover-border-color' => isset( $style['color']['button']['signIn']['hover']['border'] ) ? $style['color']['button']['signIn']['hover']['border'] : null,

			// Container Main.
			'--wsx-form-container-bg'                    => isset( $style['color']['container']['main']['background'] ) ? $style['color']['container']['main']['background'] : null,
			'--wsx-form-container-border-color'          => isset( $style['color']['container']['main']['border'] ) ? $style['color']['container']['main']['border'] : null,

			// Container Sign UP.
			'--wsx-form-reg-bg'                          => isset( $style['color']['container']['signUp']['background'] ) ? $style['color']['container']['signUp']['background'] : null,
			'--wsx-form-reg-border-color'                => isset( $style['color']['container']['signUp']['border'] ) ? $style['color']['container']['signUp']['border'] : null,

			// Container Sign IN.
			'--wsx-login-bg'                             => isset( $style['color']['container']['signIn']['background'] ) ? $style['color']['container']['signIn']['background'] : null,
			'--wsx-login-border-color'                   => isset( $style['color']['container']['signIn']['border'] ) ? $style['color']['container']['signIn']['border'] : null,

			// Typography.
			// Field - Label.
			'--wsx-form-label-font-size'                 => isset( $style['typography']['field']['label']['size'] ) ? $style['typography']['field']['label']['size'] . 'px' : null,
			'--wsx-form-label-weight'                    => isset( $style['typography']['field']['label']['weight'] ) ? $style['typography']['field']['label']['weight'] : null,
			'--wsx-form-label-case-transform'            => isset( $style['typography']['field']['label']['transform'] ) ? $style['typography']['field']['label']['transform'] . 'px' : null,
			// Field - Input.
			'--wsx-input-font-size'                      => isset( $style['typography']['field']['input']['size'] ) ? $style['typography']['field']['input']['size'] . 'px' : null,
			'--wsx-input-weight'                         => isset( $style['typography']['field']['input']['weight'] ) ? $style['typography']['field']['input']['weight'] : null,
			'--wsx-input-case-transform'                 => isset( $style['typography']['field']['input']['transform'] ) ? $style['typography']['field']['input']['transform'] : null,

			// Button.

			'--wsx-form-button-font-size'                => isset( $style['typography']['button']['size'] ) ? $style['typography']['button']['size'] . 'px' : null,
			'--wsx-form-button-weight'                   => isset( $style['typography']['button']['weight'] ) ? $style['typography']['button']['weight'] : null,
			'--wsx-form-button-case-transform'           => isset( $style['typography']['button']['transform'] ) ? $style['typography']['button']['transform'] : null,

			// Size and Spacing
			// Input.
			'--wsx-input-padding'                        => isset( $style['sizeSpacing']['input']['padding'] ) ? $style['sizeSpacing']['input']['padding'] . 'px' : null,
			'--wsx-input-width'                          => isset( $style['sizeSpacing']['input']['width'] ) ? $style['sizeSpacing']['input']['width'] . 'px' : null,
			'--wsx-input-border-width'                   => isset( $style['sizeSpacing']['input']['border'] ) ? $style['sizeSpacing']['input']['border'] . 'px' : null,
			'--wsx-input-border-radius'                  => isset( $style['sizeSpacing']['input']['borderRadius'] ) ? $style['sizeSpacing']['input']['borderRadius'] . 'px' : null,

			// Button.
			'--wsx-form-button-padding'                  => isset( $style['sizeSpacing']['button']['padding'] ) ? $style['sizeSpacing']['button']['padding'] . 'px' : null,
			'--wsx-form-button-width'                    => isset( $style['sizeSpacing']['button']['width'] ) ? $style['sizeSpacing']['button']['width'] . '%' : null,
			'--wsx-form-button-border-width'             => isset( $style['sizeSpacing']['button']['border'] ) ? $style['sizeSpacing']['button']['border'] . 'px' : null,
			'--wsx-form-button-border-radius'            => isset( $style['sizeSpacing']['button']['borderRadius'] ) ? $style['sizeSpacing']['button']['borderRadius'] . 'px' : null,
			'--wsx-form-button-align'                    => isset( $style['sizeSpacing']['button']['align'] ) ? $style['sizeSpacing']['button']['align'] : null,

			// Container - Main.
			'--wsx-form-container-width'                 => isset( $style['sizeSpacing']['container']['main']['width'] ) ? $style['sizeSpacing']['container']['main']['width'] . 'px' : null,
			'--wsx-form-container-border-width'          => isset( $style['sizeSpacing']['container']['main']['border'] ) ? $style['sizeSpacing']['container']['main']['border'] . 'px' : null,
			'--wsx-form-container-border-radius'         => isset( $style['sizeSpacing']['container']['main']['borderRadius'] ) ? $style['sizeSpacing']['container']['main']['borderRadius'] . 'px' : null,
			'--wsx-form-container-padding'               => isset( $style['sizeSpacing']['container']['main']['padding'] ) ? $style['sizeSpacing']['container']['main']['padding'] . 'px' : null,
			'--wsx-form-container-separator'             => isset( $style['sizeSpacing']['container']['main']['separator'] ) ? $style['sizeSpacing']['container']['main']['separator'] . 'px' : null,
			'--wsx-form-container-separator-space'       => isset( $style['sizeSpacing']['container']['main']['separatorSpace'] ) ? $style['sizeSpacing']['container']['main']['separatorSpace'] . 'px' : '0px',

			// Container - Sign In.
			'--wsx-login-width'                          => isset( $style['sizeSpacing']['container']['signIn']['width'] ) ? $style['sizeSpacing']['container']['signIn']['width'] . 'px' : null,
			'--wsx-login-border-width'                   => isset( $style['sizeSpacing']['container']['signIn']['border'] ) ? $style['sizeSpacing']['container']['signIn']['border'] . 'px' : null,
			'--wsx-login-padding'                        => isset( $style['sizeSpacing']['container']['signIn']['padding'] ) ? $style['sizeSpacing']['container']['signIn']['padding'] . 'px' : null,
			'--wsx-login-border-radius'                  => isset( $style['sizeSpacing']['container']['signIn']['borderRadius'] ) ? $style['sizeSpacing']['container']['signIn']['borderRadius'] . 'px' : null,

			// Container - Sign Up.
			'--wsx-form-reg-width'                       => isset( $style['sizeSpacing']['container']['signUp']['width'] ) ? $style['sizeSpacing']['container']['signUp']['width'] . 'px' : null,
			'--wsx-form-reg-border-width'                => isset( $style['sizeSpacing']['container']['signUp']['border'] ) ? $style['sizeSpacing']['container']['signUp']['border'] . 'px' : null,
			'--wsx-form-reg-padding'                     => isset( $style['sizeSpacing']['container']['signUp']['padding'] ) ? $style['sizeSpacing']['container']['signUp']['padding'] . 'px' : null,
			'--wsx-form-reg-border-radius'               => isset( $style['sizeSpacing']['container']['signUp']['borderRadius'] ) ? $style['sizeSpacing']['container']['signUp']['borderRadius'] . 'px' : null,

			'--wsx-login-title-font-size'                => isset( $login_header_style['title']['size'] ) ? $login_header_style['title']['size'] . 'px' : null,
			'--wsx-login-title-case-transform'           => isset( $login_header_style['title']['transform'] ) ? $login_header_style['title']['transform'] : null,
			'--wsx-login-title-font-weight'              => isset( $login_header_style['title']['weight'] ) ? $login_header_style['title']['weight'] : null,
			'--wsx-login-title-color'                    => isset( $login_header_style['title']['color'] ) ? $login_header_style['title']['color'] : null,

			'--wsx-login-description-font-size'          => isset( $login_header_style['description']['size'] ) ? $login_header_style['description']['size'] . 'px' : null,
			'--wsx-login-description-case-transform'     => isset( $login_header_style['description']['transform'] ) ? $login_header_style['description']['transform'] : null,
			'--wsx-login-description-font-weight'        => isset( $login_header_style['description']['weight'] ) ? $login_header_style['description']['weight'] : null,
			'--wsx-login-description-color'              => isset( $login_header_style['description']['color'] ) ? $login_header_style['description']['color'] : null,

			'--wsx-reg-title-font-size'                  => isset( $registration_header_style['title']['size'] ) ? $registration_header_style['title']['size'] . 'px' : null,
			'--wsx-reg-title-case-transform'             => isset( $registration_header_style['title']['transform'] ) ? $registration_header_style['title']['transform'] : null,
			'--wsx-reg-title-font-weight'                => isset( $registration_header_style['title']['weight'] ) ? $registration_header_style['title']['weight'] : null,
			'--wsx-reg-title-color'                      => isset( $registration_header_style['title']['color'] ) ? $registration_header_style['title']['color'] : null,

			'--wsx-reg-description-font-size'            => isset( $registration_header_style['description']['size'] ) ? $registration_header_style['description']['size'] . 'px' : null,
			'--wsx-reg-description-case-transform'       => isset( $registration_header_style['description']['transform'] ) ? $registration_header_style['description']['transform'] : null,
			'--wsx-reg-description-font-weight'          => isset( $registration_header_style['description']['weight'] ) ? $registration_header_style['description']['weight'] : null,
			'--wsx-reg-description-color'                => isset( $registration_header_style['description']['color'] ) ? $registration_header_style['description']['color'] : null,
		);

		// Advanced appearance colors are the canonical color controls for the new builder.
		$get_advanced_color = function ( $form_type, $key, $legacy_key = null, $fallback = null ) use ( $style ) {
			if ( isset( $style['appearance']['advancedColors'][ $form_type ][ $key ] ) && '' !== $style['appearance']['advancedColors'][ $form_type ][ $key ] ) {
				return $style['appearance']['advancedColors'][ $form_type ][ $key ];
			}

			if ( $legacy_key && isset( $style['appearance']['advancedColors'][ $form_type ][ $legacy_key ] ) && '' !== $style['appearance']['advancedColors'][ $form_type ][ $legacy_key ] ) {
				return $style['appearance']['advancedColors'][ $form_type ][ $legacy_key ];
			}

			return $fallback;
		};

		$_signup_primary          = $get_advanced_color( 'signup', 'primaryColor', null, $_style['--wsx-form-button-bg'] );
		$_signup_text_primary     = $get_advanced_color( 'signup', 'textPrimary', null, $_style['--wsx-input-color'] );
		$_signup_text_secondary   = $get_advanced_color( 'signup', 'textSecondary', null, $_style['--wsx-input-placeholder-color'] ? $_style['--wsx-input-placeholder-color'] : $_style['--wsx-form-label-color'] );
		$_signup_background       = $get_advanced_color( 'signup', 'background', null, $_style['--wsx-form-reg-bg'] );
		$_signup_border           = $get_advanced_color( 'signup', 'borderColor', 'fieldBorder', $_style['--wsx-input-border-color'] );
		$_signup_input_background = $get_advanced_color( 'signup', 'inputBackground', 'fieldBackground', $_style['--wsx-input-bg'] );
		$_signup_button_text      = $get_advanced_color( 'signup', 'buttonText', 'overTextColor', $_style['--wsx-form-button-color'] );
		$_signup_link             = $get_advanced_color( 'signup', 'link', null, $_signup_primary );
		$_signup_container_color  = $get_advanced_color( 'signup', 'containerColor', null, isset( $style['appearance']['container']['color'] ) ? $style['appearance']['container']['color'] : null );

		$_login_primary          = $get_advanced_color( 'login', 'primaryColor', null, $_style['--wsx-login-form-button-bg'] );
		$_login_text_primary     = $get_advanced_color( 'login', 'textPrimary', null, $_style['--wsx-login-input-color'] );
		$_login_text_secondary   = $get_advanced_color( 'login', 'textSecondary', null, $_style['--wsx-login-input-placeholder-color'] ? $_style['--wsx-login-input-placeholder-color'] : $_style['--wsx-login-form-label-color'] );
		$_login_background       = $get_advanced_color( 'login', 'background', null, $_style['--wsx-login-bg'] );
		$_login_border           = $get_advanced_color( 'login', 'borderColor', 'fieldBorder', $_style['--wsx-login-input-border-color'] );
		$_login_input_background = $get_advanced_color( 'login', 'inputBackground', 'fieldBackground', $_style['--wsx-login-input-bg'] );
		$_login_button_text      = $get_advanced_color( 'login', 'buttonText', 'overTextColor', $_style['--wsx-login-form-button-color'] );
		$_login_link             = $get_advanced_color( 'login', 'link', null, $_login_primary );
		$_login_container_color  = $get_advanced_color( 'login', 'containerColor', null, isset( $style['appearance']['container']['color'] ) ? $style['appearance']['container']['color'] : null );

		$_container_color = isset( $style['appearance']['container']['color'] ) && '' !== $style['appearance']['container']['color']
			? $style['appearance']['container']['color']
			: ( $_signup_container_color ? $_signup_container_color : ( $_login_container_color ? $_login_container_color : $_style['--wsx-form-container-bg'] ) );

		$_style['--wsx-input-color']                    = $_signup_text_primary;
		$_style['--wsx-input-bg']                       = $_signup_input_background;
		$_style['--wsx-input-border-color']             = $_signup_border;
		$_style['--wsx-input-placeholder-color']        = $_signup_text_secondary;
		$_style['--wsx-form-label-color']               = $_signup_text_secondary;
		$_style['--wsx-input-focus-color']              = $_signup_text_primary;
		$_style['--wsx-input-focus-bg']                 = $_signup_input_background;
		$_style['--wsx-input-focus-border-color']       = $_signup_primary ? $_signup_primary : $_signup_border;
		$_style['--wsx-form-label-color-active']        = $_signup_text_primary;
		$_style['--wsx-form-button-color']              = $_signup_button_text;
		$_style['--wsx-form-button-bg']                 = $_signup_primary;
		$_style['--wsx-form-button-border-color']       = ! empty( $_style['--wsx-form-button-border-color'] ) ? $_style['--wsx-form-button-border-color'] : $_signup_primary;
		$_style['--wsx-form-button-hover-color']        = $_signup_button_text;
		$_style['--wsx-form-button-hover-bg']           = ! empty( $_style['--wsx-form-button-hover-bg'] ) ? $_style['--wsx-form-button-hover-bg'] : $_signup_primary;
		$_style['--wsx-form-button-hover-border-color'] = ! empty( $_style['--wsx-form-button-hover-border-color'] ) ? $_style['--wsx-form-button-hover-border-color'] : $_signup_primary;
		$_style['--wsx-form-reg-bg']                    = $_signup_background;
		$_style['--wsx-form-reg-border-color']          = ! empty( $_style['--wsx-form-reg-border-color'] ) ? $_style['--wsx-form-reg-border-color'] : $_signup_border;
		$_style['--wsx-reg-title-color']                = $_signup_text_primary;
		$_style['--wsx-reg-description-color']          = $_signup_text_secondary;
		$_style['--wsx-form-link-color']                = $_signup_link;

		$_style['--wsx-login-input-color']                    = $_login_text_primary;
		$_style['--wsx-login-input-bg']                       = $_login_input_background;
		$_style['--wsx-login-input-border-color']             = $_login_border;
		$_style['--wsx-login-input-placeholder-color']        = $_login_text_secondary;
		$_style['--wsx-login-form-label-color']               = $_login_text_secondary;
		$_style['--wsx-login-input-focus-color']              = $_login_text_primary;
		$_style['--wsx-login-input-focus-bg']                 = $_login_input_background;
		$_style['--wsx-login-input-focus-border-color']       = $_login_primary ? $_login_primary : $_login_border;
		$_style['--wsx-login-form-label-color-active']        = $_login_text_primary;
		$_style['--wsx-login-form-button-color']              = $_login_button_text;
		$_style['--wsx-login-form-button-bg']                 = $_login_primary;
		$_style['--wsx-login-form-button-border-color']       = ! empty( $_style['--wsx-login-form-button-border-color'] ) ? $_style['--wsx-login-form-button-border-color'] : $_login_primary;
		$_style['--wsx-login-form-button-hover-color']        = $_login_button_text;
		$_style['--wsx-login-form-button-hover-bg']           = ! empty( $_style['--wsx-login-form-button-hover-bg'] ) ? $_style['--wsx-login-form-button-hover-bg'] : $_login_primary;
		$_style['--wsx-login-form-button-hover-border-color'] = ! empty( $_style['--wsx-login-form-button-hover-border-color'] ) ? $_style['--wsx-login-form-button-hover-border-color'] : $_login_primary;
		$_style['--wsx-login-bg']                             = $_login_background;
		$_style['--wsx-login-border-color']                   = ! empty( $_style['--wsx-login-border-color'] ) ? $_style['--wsx-login-border-color'] : $_login_border;
		$_style['--wsx-login-title-color']                    = $_login_text_primary;
		$_style['--wsx-login-description-color']              = $_login_text_secondary;
		$_style['--wsx-login-link-color']                     = $_login_link;

		$_style['--wsx-form-container-bg'] = $_container_color;
		if ( null !== $_container_color && '' !== $_container_color ) {
			$_style['--wsx-appearance-container-color'] = $_container_color;
		}
		$_style['--wsx-appearance-adv-signup-field-border']   = $_signup_border;
		$_style['--wsx-appearance-adv-signup-field-bg']       = $_signup_input_background;
		$_style['--wsx-appearance-adv-signup-text-primary']   = $_signup_text_primary;
		$_style['--wsx-appearance-adv-signup-text-secondary'] = $_signup_text_secondary;
		$_style['--wsx-appearance-adv-signup-over-text']      = $_signup_button_text;
		$_style['--wsx-appearance-adv-login-field-border']    = $_login_border;
		$_style['--wsx-appearance-adv-login-field-bg']        = $_login_input_background;
		$_style['--wsx-appearance-adv-login-text-primary']    = $_login_text_primary;
		$_style['--wsx-appearance-adv-login-text-secondary']  = $_login_text_secondary;
		$_style['--wsx-appearance-adv-login-over-text']       = $_login_button_text;

		// Appearance System overrides.
		$_appearance_field_size_map  = array(
			'small'  => '36px',
			'medium' => '40px',
			'large'  => '48px',
		);
		$_appearance_button_size_map = array(
			'small'  => '36px',
			'medium' => '40px',
			'large'  => '48px',
		);
		$_appearance_field_shape_map = array(
			'sharp'   => '0px',
			'rounded' => '4px',
			'pill'    => '50px',
		);

		$_style['--wsx-appearance-field-size']   = isset( $settings['appearance_fieldSize'] ) && isset( $_appearance_field_size_map[ $settings['appearance_fieldSize'] ] )
			? $_appearance_field_size_map[ $settings['appearance_fieldSize'] ]
			: '40px';
		$_style['--wsx-appearance-button-size']  = isset( $settings['appearance_buttonSize'] ) && isset( $_appearance_button_size_map[ $settings['appearance_buttonSize'] ] )
			? $_appearance_button_size_map[ $settings['appearance_buttonSize'] ]
			: '40px';
		$_style['--wsx-appearance-button-width'] = isset( $settings['appearance_buttonWidth'] ) && 'full' === $settings['appearance_buttonWidth']
			? '100%'
			: ( ! empty( $_style['--wsx-form-button-width'] ) ? $_style['--wsx-form-button-width'] : '100%' );
		$_style['--wsx-appearance-field-shape']  = isset( $settings['appearance_fieldShape'] ) && isset( $_appearance_field_shape_map[ $settings['appearance_fieldShape'] ] )
			? $_appearance_field_shape_map[ $settings['appearance_fieldShape'] ]
			: '4px';

		// Appearance system — container (only set when user has configured them).
		if ( ! empty( $style['appearance']['container']['color'] ) ) {
			$_style['--wsx-appearance-container-color'] = $style['appearance']['container']['color'];
		}
		if ( isset( $style['appearance']['container']['padding'] ) && '' !== $style['appearance']['container']['padding'] ) {
			$_style['--wsx-appearance-container-padding'] = intval( $style['appearance']['container']['padding'] ) . 'px';
		}
		if ( isset( $style['appearance']['container']['radius'] ) && '' !== $style['appearance']['container']['radius'] ) {
			$_style['--wsx-appearance-container-radius'] = intval( $style['appearance']['container']['radius'] ) . 'px';
		}

		return $_style;
	}

	/**
	 * Queue the per-form CSS custom properties.
	 *
	 * The main form stylesheet is enqueued on wp_enqueue_scripts and is already
	 * printed by the time a shortcode renders, so the declarations are attached
	 * to a handle registered here and printed with the late styles instead.
	 *
	 * @param string $css Sanitized CSS declarations.
	 * @return void
	 */
	private function add_form_inline_css( $css ) {
		$css = wholesalex()->sanitize_inline_css( (string) $css );

		if ( '' === $css ) {
			return;
		}

		if ( ! wp_style_is( 'whx_form_inline', 'registered' ) ) {
			wp_register_style( 'whx_form_inline', false, array( 'whx_form' ), WHOLESALEX_VER );
		}

		wp_enqueue_style( 'whx_form_inline' );
		wp_add_inline_style( 'whx_form_inline', ':root { ' . $css . ' }' );
	}

	/**
	 * Get CSS
	 *
	 * @param  mixed $vars Variables.
	 * @return string CSS.
	 * @since 1.0.0
	 */
	private function get_vars_css( $vars ) {

		$result = '';

		foreach ( $vars as $name => $value ) {
			$name  = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $name );
			$value = trim( preg_replace( '/[{}<>;\\\\]/', '', wp_strip_all_tags( (string) $value ) ) );
			if ( '' === $name ) {
				continue;
			}
			$result .= "{$name}: {$value};\n";
		}

		return $result;
	}

	/**
	 * Check Depends
	 *
	 * @param  mixed $field Variables.
	 * @return string CSS.
	 * @since 1.0.0
	 */
	public function check_depends( $field ) {
		$exclude_string = '';
		if ( isset( $field['excludeRoles'] ) && ! empty( $field['excludeRoles'] ) && is_array( $field['excludeRoles'] ) ) {
			foreach ( $field['excludeRoles'] as $role ) {
				$exclude_string .= $role['value'] . ' ';
			}
		}
		return $exclude_string;
	}

	/**
	 * Render Term & Condition Field
	 *
	 * @param array $field Field Array.
	 * @param bool  $is_label_hide Is Label Hide.
	 * @return void
	 */
	public function render_term_condition_field( $field, $is_label_hide ) {
		?>
		<div class="wsx-form-field wsx-form-checkbox">
			<?php
			$term_link        = '';
			$term_link_markup = '';
			if ( isset( $field['term_link'] ) && $field['term_link'] ) {
				$term_link = $field['term_link'];
			}
			if ( ! empty( $term_link ) && isset( $field['default_text'] ) && $field['default_text'] ) {
				preg_match_all( '/\{([^}]*)\}/', $field['default_text'], $matches );
				if ( ! empty( $matches[1] ) ) {
					$term_link        = sanitize_url( $term_link );
					$found            = false;
					$term_link_markup = preg_replace_callback(
						'/\{([^}]*)\}/',
						function ( $matched_text ) use ( $term_link, &$found ) {
							if ( ! $found ) {
								$found = true;
								return '<a class="wsx-link" href="' . $term_link . '">' . $matched_text[1] . '</a>';
							}
							return $matched_text[0];
						},
						$field['default_text']
					);
				} else {
					$term_link_markup = str_replace( array( '{', '}' ), '', $field['default_text'] );
				}
			} else {
				$term_link_markup = str_replace( array( '{', '}' ), '', $field['default_text'] );
			}

			if ( ! $is_label_hide ) {
				?>
				<div class="wsx-field-heading">
					<?php
					if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
						?>
						<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
							<?php echo esc_html( $field['label'] ); ?>
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
																<span aria-label="required">*</span>
								<?php
							}
							?>
						</div>
					<?php endif; ?>
				</div>
				<?php
			}
			?>
			<div class="wsx-field-content">
				<div class="wholesalex-field-wrap">
					<input type="checkbox" class="input-checkbox" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $field['name'] ); ?>" />
					<label class="wsx-label wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo wp_kses_post( $term_link_markup ); ?></label>
				</div>
			</div>
			<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
				<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
			<?php endif; ?>
		</div>
			<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
		<?php
	}

	/**
	 * Gemnerate Form Field
	 *
	 * @param  array  $field Field Array.
	 * @param  bool   $is_role_wise Is Rolewise.
	 * @param  string $input_variation Input Variation.
	 * @param  bool   $is_only_b2b Is Only B2B.
	 * @return array Role Field.
	 */
	public function generate_form_field( $field, $is_role_wise = false, $input_variation = '', $is_only_b2b = false ) {
		if ( ! apply_filters( 'wholesalex_registration_field_available', WholesaleX_CommonUtils::is_standard_form_field( $field ), $field ) ) {
			return;
		}
		if ( ! isset( $field['name'] ) ) {
			return;
		}
		if ( $is_role_wise && 'wholesalex_registration_role' === $field['name'] ) {
			return;
		}
		$exclude = $this->check_depends( $field );

		if ( $is_role_wise ) {
			$exclude_roles = explode( ' ', $exclude );
			if ( in_array( $is_role_wise, $exclude_roles, true ) ) {
				return;
			}
		}
		if ( ( ! $is_role_wise || $is_only_b2b ) && 'select' === $field['type'] && 'wholesalex_registration_role' === $field['name'] ) {
			$field['option'] = $this->get_select_role_field( $is_only_b2b )['columns'][0]['option'];
		}
		$field         = WholesaleX_CommonUtils::translate_form_builder_field( $field );
		$is_label_hide = isset( $field['isLabelHide'] ) && $field['isLabelHide'];

		ob_start();
		switch ( $input_variation ) {
			case 'variation_1':
			case 'variation_3':
				switch ( apply_filters( 'wholesalex_registration_render_type', $field['type'], $input_variation ) ) {
					case 'text':
					case 'email':
						?>
						<div  class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
								<?php
								if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
									?>
									<label class='wsx-label wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
										<?php echo esc_html( $field['label'] ); ?>
										<?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
											<?php
										}
										?>
									</label>
								<?php endif; ?>
								</div>
								<?php
							}
							?>

							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
						</div>

						<?php
						if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) :
							?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
										<label class='wsx-label wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
										</label>
									<?php endif; ?>
								</div>
								<?php
							}
							?>

							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php
						if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) :
							?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<div class="wsx-form-field wsx-form-checkbox">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
										</div>
									<?php endif; ?>
								</div>

								<?php
							}
							?>

							<div class="wsx-field-content">
								<?php foreach ( $field['option'] as $option ) : ?>
									<div class="wholesalex-field-wrap">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $field['name'] . '_' . $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<label class="wsx-label wsx-label" for="<?php echo esc_attr( $field['name'] . '_' . $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
									</div>
								<?php endforeach; ?>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php endif; ?>
						</div>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;
					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					case 'tel':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
										<?php
										if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
											?>
											<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
												<?php echo esc_html( $field['label'] ); ?>
												<?php
												if ( isset( $field['required'] ) && $field['required'] ) {
													?>
														<span aria-label="required">*</span>
													<?php
												}
												?>
											</label>
										<?php endif; ?>
									</div>
								<?php
							}

							?>

							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='tel' name="<?php echo esc_attr( $field['name'] ); ?>"   placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
						</div>
						<?php
						if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) :
							?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'url':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
								<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
										<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
														<span aria-label="required">*</span>
												<?php
											}
											?>
										</label>
									<?php endif; ?>
								</div>
								<?php
							}
							?>
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='url' name="<?php echo esc_attr( $field['name'] ); ?>"   placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
						<?php endif; ?>
								<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
											<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
											</label>
										<?php endif; ?>
									</div>
									<?php
							}

							?>

							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='password' name="<?php echo esc_attr( $field['name'] ); ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>"  placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
						<?php endif; ?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<div class="wsx-form-field">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php
									if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) :
										?>
											<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
											<?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
											</label>
										<?php endif; ?>
									</div>
									<?php
							}
							?>

							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"></textarea>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
						<?php endif; ?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					default:
						break;
				}
				break;
			case 'variation_2':
				switch ( apply_filters( 'wholesalex_registration_render_type', $field['type'], $input_variation ) ) {
					case 'text':
					case 'email':
					case 'url':
					case 'tel':
						?>
						<div class="wsx-form-field wsx-outline-focus">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>" />
							<div class='wsx-form-label wsx-clone-label'><?php echo esc_html( $field['label'] ); ?>
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
							<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<div class="wsx-form-field wsx-outline-focus">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" />
							<div class='wsx-form-label wsx-clone-label'><?php echo esc_html( $field['label'] ); ?>
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>">
									<?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
								</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea">
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-field__textarea" name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="<?php echo esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ); ?>"></textarea>
							<div class='wsx-form-label wsx-clone-label'><?php echo esc_html( $field['label'] ); ?>
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
							<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus wsx-form-select">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<div class='wsx-form-label wsx-clone-label'><?php echo esc_html( $field['label'] ); ?>
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
							<?php
							if ( ! $is_label_hide ) {
								?>
									<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
											<div class='wsx-form-label'><?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
												</div>
										<?php endif; ?>

									</div>
									<?php
							}
							?>
							<div class="wsx-field-content">
							<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input type="checkbox" class="wsx-checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<div><?php echo esc_html( $option['name'] ); ?></div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;
					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;
					default:
						break;
				}
				break;
			case 'variation_4':
				switch ( apply_filters( 'wholesalex_registration_render_type', $field['type'], $input_variation ) ) {
					case 'text':
					case 'email':
					case 'url':
					case 'tel':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>" />
							<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
																								<span aria-label="required">*</span>
												<?php
								}
								?>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<!-- wsx-form-field--focused -->

						<label class="wsx-label wsx-form-field wsx-outline-focus" for="<?php echo esc_attr( $field['name'] ); ?>">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" />
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<div  class="wsx-form-label">
									<?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
																								<span aria-label="required">*</span>
												<?php
									}
									?>
								</div>
							<?php endif; ?>
						</label>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea">
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-field__textarea"  name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"></textarea>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
																							<span aria-label="required">*</span>
											<?php
								}
								?>
												</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
						<?php
						if ( ! $is_label_hide ) {
							?>
									<div class="wsx-field-heading">
								<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
											<?php
											if ( isset( $field['required'] ) && $field['required'] ) {
												?>
													<span aria-label="required">*</span>
												<?php
											}
											?>
										</div>
									<?php endif; ?>

									</div>
									<?php
						}
						?>

							<div class="wsx-field-content">
							<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<label class="wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					default:
						break;
				}
				break;
			case 'variation_5':
				switch ( apply_filters( 'wholesalex_registration_render_type', $field['type'], $input_variation ) ) {
					case 'text':
					case 'email':
					case 'url':
					case 'tel':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>" />
							<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
							</label>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<!-- wsx-form-field--focused -->

						<label class="wsx-label wsx-form-field wsx-outline-focus" for="<?php echo esc_attr( $field['name'] ); ?>">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" />
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<div  class="wsx-form-label">
									<?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
								</div>
							<?php endif; ?>
						</label>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea">
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-field__textarea"  name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="<?php echo isset( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : ''; ?>"></textarea>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label" for="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
														<span aria-label="required">*</span>
										<?php
									}
									?>
								</label>
							<?php endif; ?>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
						<?php
						if ( ! $is_label_hide ) {
							?>
									<div class="wsx-field-heading">
								<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
											<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
												<?php
												if ( isset( $field['required'] ) && $field['required'] ) {
													?>
													<span aria-label="required">*</span>
													<?php
												}
												?>
											</div>
										<?php endif; ?>

									</div>
									<?php
						}

						?>

							<div class="wsx-field-content">
						<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<div for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					default:
						break;
				}
				break;
			case 'variation_6':
				$variation_6_placeholder  = isset( $field['label'] ) ? (string) $field['label'] : '';
				$variation_6_placeholder .= isset( $field['required'] ) && $field['required'] ? '*' : '';

				switch ( apply_filters( 'wholesalex_registration_render_type', $field['type'], $input_variation ) ) {
					case 'text':
					case 'email':
					case 'url':
					case 'tel':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo esc_attr( $variation_6_placeholder ); ?>" />
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?>  </span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input" id="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo esc_attr( $variation_6_placeholder ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" />
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea">
							<textarea class="wsx-textarea wsx-form-field__textarea" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="<?php echo esc_attr( $variation_6_placeholder ); ?>"></textarea>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'select':
						?>
						<!-- wsx-form-field--focused -->
						<?php
						if ( ! $is_label_hide ) {
							?>
							<div class="wsx-field-heading">
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<div class="wsx-form-label">
									<?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
								</div>
							<?php endif; ?>

							</div>
							<?php
						}

						?>

						<div class="wsx-form-field wsx-outline-focus wsx-form-select">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
								<?php
								if ( ! $is_label_hide ) {
									?>
									<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
											<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
												<?php
												if ( isset( $field['required'] ) && $field['required'] ) {
													?>
														<span aria-label="required">*</span>
													<?php
												}
												?>
											</div>
										<?php endif; ?>

									</div>
									<?php
								}

								?>

							<div class="wsx-field-content">
						<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<div for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
								<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
									<?php
								endif;
								?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					default:
						break;
				}
				break;
			case 'variation_7':
				switch ( apply_filters( 'wholesalex_registration_render_type', $field['type'], $input_variation ) ) {
					case 'text':
					case 'email':
					case 'url':
					case 'tel':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>"  name="<?php echo esc_attr( $field['name'] ); ?>" placeholder=" " />
							<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
								<?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
							</label>
							<div class="wsx-clone-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'password':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<input type="<?php echo esc_attr( $field['type'] ); ?>" class="wsx-input wsx-form-field__input"  id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>" placeholder=" " />
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label  class="wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
							<div class="wsx-clone-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
							<?php
							if ( isset( $field['required'] ) && $field['required'] ) {
								?>
									<span aria-label="required">*</span>
								<?php
							}
							?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'textarea':
						?>
						<div class="wsx-form-field wsx-outline-focus wsx-form-textarea wsx-formBuilder-input-width">
							<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" class="wsx-form-field__textarea" name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder=" "></textarea>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label" for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
										<span aria-label="required">*</span>
									<?php
								}
								?>
								</label>
							<?php endif; ?>
							<div class="wsx-clone-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?></div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;
					case 'select':
						?>
						<!-- wsx-form-field--focused -->

						<div class="wsx-form-field wsx-outline-focus wsx-form-select wsx-formBuilder-input-width">
							<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
									<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
								<label class="wsx-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
								</label>
							<?php endif; ?>
							<div class="wsx-clone-label wsx-form-label"><?php echo esc_html( $field['label'] ); ?>
								<?php
								if ( isset( $field['required'] ) && $field['required'] ) {
									?>
											<span aria-label="required">*</span>
									<?php
								}
								?>
							</div>
						</div>
						<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
							<?php
						endif;
						?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'checkbox':
						?>
						<!-- wsx-form-field--focused -->
						<div class="wsx-form-field wsx-form-checkbox">
						<?php
						if ( ! $is_label_hide ) {
							?>
								<div class="wsx-field-heading">
								<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?></div>
									<?php endif; ?>
								</div>

								<?php
						}

						?>
							<div class="wsx-field-content">
						<?php foreach ( $field['option'] as $option ) : ?>
									<label class="wsx-label wholesalex-field-wrap" for="<?php echo esc_attr( $field['name'] ); ?>">
										<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
										<div for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></div>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
							<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
						<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
						<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					default:
						break;
				}
				break;
			case 'variation_9':
			case 'variation_8':
				switch ( apply_filters( 'wholesalex_registration_render_type', $field['type'], $input_variation ) ) {
					case 'text':
					case 'email':
						?>
							<!-- wsx-form-field--focused -->

							<label class="wsx-label wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
						<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>">
										<?php echo esc_html( $field['label'] ); ?>
										<?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
											<?php
										}
										?>
									</div>
								<?php endif; ?>
								<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  placeholder="<?php echo esc_attr( $field['placeholder'] ); ?>" />
							</label>
									<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
										<?php
									endif;
									?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
								<?php
						break;

					case 'select':
						?>
							<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
									<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
										<?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
										<span aria-label="required">*</span>
											<?php
										}
										?>
									</label>
								<?php endif; ?>
								<select class="wsx-select" name="<?php echo esc_attr( $field['name'] ); ?>" id="<?php echo esc_attr( $field['name'] ); ?>">
								<?php foreach ( $field['option'] as $option ) : ?>
										<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'checkbox':
						?>
							<div class="wsx-form-field wsx-form-checkbox">
						<?php
						if ( ! $is_label_hide ) {
							?>
										<div class="wsx-field-heading">
										<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
												<div class='wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
													<?php
													if ( isset( $field['required'] ) && $field['required'] ) {
														?>
															<span aria-label="required">*</span>
														<?php
													}
													?>
												</div>
											<?php endif; ?>

										</div>
											<?php
						}
						?>
								<div class="wsx-field-content">
							<?php foreach ( $field['option'] as $option ) : ?>
										<div class="wholesalex-field-wrap">
											<input class="wsx-checkbox" type="checkbox" id="<?php echo esc_attr( $option['value'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>[]" value="<?php echo esc_attr( $option['value'] ); ?>" />
											<label class="wsx-label" for="<?php echo esc_attr( $option['name'] ); ?>"><?php echo esc_html( $option['name'] ); ?></label>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
									<?php
								endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'termCondition':
						$this->render_term_condition_field( $field, $is_label_hide );
						break;

					case 'tel':
						?>
							<!-- wsx-form-field--focused -->
							<div class="wsx-form-field">
								<?php
								if ( ! $is_label_hide ) {
									?>
										<div class="wsx-field-heading">
									<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
												<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
												<?php
												if ( isset( $field['required'] ) && $field['required'] ) {
													?>
														<span aria-label="required">*</span>
													<?php
												}
												?>
												</label>
											<?php endif; ?>

										</div>
										<?php
								}
								?>
								<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='tel' name="<?php echo esc_attr( $field['name'] ); ?>"   />
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'url':
						?>
							<!-- wsx-form-field--focused -->
							<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
								<div class="wsx-field-heading wsx-outline-focus wsx-formBuilder-input-width">
								<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
										<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
										<?php
										if ( isset( $field['required'] ) && $field['required'] ) {
											?>
												<span aria-label="required">*</span>
												<?php
										}
										?>
												</label>
									<?php endif; ?>

								</div>
								<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='url' name="<?php echo esc_attr( $field['name'] ); ?>"  />
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
									<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;

					case 'password':
						?>
							<!-- wsx-form-field--focused -->

							<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
									<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
									</label>
								<?php endif; ?>
								<input class="wsx-input" id="<?php echo esc_attr( $field['name'] ); ?>" type='password' name="<?php echo esc_attr( $field['name'] ); ?>" size="<?php echo isset( $field['size'] ) ? esc_attr( $field['size'] ) : ''; ?>"  placeholder="Type Password" />
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;
					case 'textarea':
						?>
							<!-- wsx-form-field--focused -->
							<div class="wsx-form-field wsx-outline-focus wsx-formBuilder-input-width">
							<?php if ( ! isset( $field['isLabelHide'] ) || ! $field['isLabelHide'] ) : ?>
									<label class='wsx-label wsx-form-label' for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>
									<?php
									if ( isset( $field['required'] ) && $field['required'] ) {
										?>
											<span aria-label="required">*</span>
										<?php
									}
									?>
												</label>
								<?php endif; ?>
								<textarea class="wsx-textarea" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>"  rows="<?php echo isset( $field['rows'] ) ? esc_attr( $field['rows'] ) : ''; ?>" cols="<?php echo isset( $field['cols'] ) ? esc_attr( $field['cols'] ) : ''; ?>" placeholder="Write Message..."></textarea>
							</div>
							<?php if ( isset( $field['help_message'] ) && ! empty( $field['help_message'] ) ) : ?>
								<span class='wsx-form-field-help-message'><?php echo esc_html( $field['help_message'] ); ?></span>
								<?php
							endif;
							?>
							<span class='wsx-form-field-warning-message <?php echo esc_attr( $field['name'] ); ?>'></span>
							<?php
						break;
					default:
							// code...
						break;
				}
				break;

			default:
				// code...
				break;
		}

		$output = ob_get_clean();

		echo wp_kses( apply_filters( 'wholesalex_registration_form_field', $output, $field['type'], $field['name'], $input_variation, $field ), $this->get_form_field_allowed_html() );
	}





	/**
	 * Sanitize Form Data
	 *
	 * @param array $form_data Form Data.
	 */
	public function sanitize_form_data( $form_data ) {
		$data = array();
		foreach ( $form_data as $key => $value ) {
			$key = sanitize_key( $key );
			switch ( $key ) {
				case 'description':
					$data[ $key ] = sanitize_textarea_field( $value );
					break;
				case ( preg_match( '#^textarea(.*)$#i', $key ) ? true : false ):
					$data[ $key ] = sanitize_textarea_field( $value );
					break;
				case 'user_pass':
				case 'display_name':
				case 'nickname':
				case 'first_name':
				case 'last_name':
				case ( preg_match( '#^text(.*)$#i', $key ) ? true : false ):
						$data[ $key ] = sanitize_text_field( $value );
					break;
				case 'url':
					$data[ $key ] = sanitize_url( $value );
					break;
				case ( preg_match( '#^email(.*)$#i', $key ) ? true : false ):
				case 'user_email':
					$data[ $key ] = sanitize_email( $value );
					break;
				case ( preg_match( '#^select(.*)$#i', $key ) ? true : false ):
				case ( preg_match( '#^checkbox(.*)$#i', $key ) ? true : false ):
				case ( preg_match( '#^number(.*)$#i', $key ) ? true : false ):
				case ( preg_match( '#^radio(.*)$#i', $key ) ? true : false ):
				case ( preg_match( '#^date(.*)$#i', $key ) ? true : false ):
					if ( is_array( $value ) ) {
						$data[ $key ] = wholesalex()->sanitize( $value );
					} else {
						$data[ $key ] = sanitize_text_field( $value );
					}
					break;
				case 'user_login':
					$data[ $key ] = sanitize_user( $value );
					break;

				default:
					if ( is_array( $value ) ) {
						$data[ $key ] = wholesalex()->sanitize( $value );
					} else {
						$data[ $key ] = sanitize_text_field( $value );
					}
					break;
			}
		}

		return $data;
	}

	/**
	 * Enqueue WooCommerce Passowrd Meter For wholesalex registration form
	 *
	 * @return void
	 */
	public function enqueue_password_meter() {
		$status = apply_filters( 'wholesalex_registration_form_password_strength_meter_status', true );
		if ( $status ) {
			wp_enqueue_script( 'woocommerce' );
			wp_enqueue_script( 'wc-password-strength-meter' );
		}
	}


	/**
	 * Process User login
	 *
	 * @return void
	 * @throws \Exception Exception.
	 */
	public function process_login() {
		if ( ! isset( $_POST['wholesalex-login-nonce'] ) || ! is_string( $_POST['wholesalex-login-nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wholesalex-login-nonce'] ) ), 'wholesalex-login' ) ) {
			wp_send_json_error( array( 'error_messages' => array( 'other_error' => __( 'This login form has expired. Please reload the page and try again.', 'wholesalex' ) ) ), 403 );
		}

		if ( isset( $_POST['username'], $_POST['password'] ) && is_string( $_POST['username'] ) && is_string( $_POST['password'] ) ) {
			do_action( 'wholesalex_before_process_user_login' );

			$data = array(
				'error_messages' => array(),
			);
			try {
				$creds = array(
					'user_login'    => trim( sanitize_text_field( wp_unslash( $_POST['username'] ) ) ),
					'user_password' => wp_unslash( $_POST['password'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitizing a password changes the credential before WordPress authenticates it.
					'remember'      => isset( $_POST['rememberme'] ),
				);

				$validation_error = new \WP_Error();
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Invoke the existing WooCommerce extension hook.
				$validation_error = apply_filters( 'woocommerce_process_login_errors', $validation_error, $creds['user_login'], $creds['user_password'] );

				if ( $validation_error->get_error_code() ) {
					$data['error_messages']['validation_error'] = $validation_error->get_error_message();
					throw new \Exception();
				}

				if ( empty( $creds['user_login'] ) ) {
					$data['error_messages']['username'] = __( 'Username is Required!', 'wholesalex' );

				}
				if ( empty( $creds['user_password'] ) ) {
					$data['error_messages']['password'] = __( 'Password is Required!', 'wholesalex' );

				}

				if ( ! empty( $data['error_messages'] ) ) {
					throw new \Exception();
				}

				// On multisite, ensure user exists on current site, if not add them before allowing login.
				if ( is_multisite() ) {
					$user_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );

					if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
						add_user_to_blog( get_current_blog_id(), $user_data->ID, 'customer' );
					}
				}

				// Peform the login.
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Invoke the existing WooCommerce extension hook.
				$user = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), is_ssl() );

				if ( is_wp_error( $user ) ) {
					switch ( $user->get_error_code() ) {
						case 'invalid_username':
							$data['error_messages']['username'] = $user->get_error_message();
							break;
						case 'incorrect_password':
							$data['error_messages']['password'] = $user->get_error_message();
							break;
						case 'invalid_email':
							$data['error_messages']['username'] = $user->get_error_message();
							break;
						case 'recaptcha_error':
							$data['error_messages']['recaptcha'] = wc_print_notice( $user->get_error_message(), 'error', array(), true );
							break;

						default:
							// code...
							$data['error_messages'][ $user->get_error_code() ] = $user->get_error_message();
							break;
					}

					throw new \Exception();
				} else {

					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Invoke the existing WooCommerce extension hook.
					$data['redirect'] = wp_validate_redirect( apply_filters( 'woocommerce_login_redirect', wc_get_page_permalink( 'myaccount' ), $user ), wc_get_page_permalink( 'myaccount' ) );
					wp_send_json_success( $data );
				}
			} catch ( \Exception $e ) {
				wp_send_json_success( $data );
			}
		}

		wp_send_json_error( array( 'error_messages' => array( 'other_error' => __( 'Invalid login request.', 'wholesalex' ) ) ), 400 );
	}

	/**
	 * Allowed HTML for registration/login form fields.
	 *
	 * @return array
	 */
	public function get_form_field_allowed_html() {
		$allowed = wp_kses_allowed_html( 'post' );
		$common  = array(
			'id'           => true,
			'class'        => true,
			'name'         => true,
			'type'         => true,
			'value'        => true,
			'placeholder'  => true,
			'required'     => true,
			'disabled'     => true,
			'readonly'     => true,
			'checked'      => true,
			'selected'     => true,
			'multiple'     => true,
			'accept'       => true,
			'min'          => true,
			'max'          => true,
			'step'         => true,
			'maxlength'    => true,
			'minlength'    => true,
			'pattern'      => true,
			'autocomplete' => true,
			'rows'         => true,
			'cols'         => true,
			'size'         => true,
			'for'          => true,
			'style'        => true,
			'title'        => true,
		);
		// Seed from a tag kses already allows so the global attributes (class, id, style, aria-*, data-*) carry over.
		$globals = isset( $allowed['span'] ) ? $allowed['span'] : array();
		foreach ( array( 'input', 'select', 'option', 'textarea', 'label', 'optgroup' ) as $tag ) {
			$allowed[ $tag ] = array_merge( $globals, isset( $allowed[ $tag ] ) ? $allowed[ $tag ] : array(), $common );
		}
		$allowed['svg']  = array(
			'xmlns'   => true,
			'width'   => true,
			'height'  => true,
			'fill'    => true,
			'viewbox' => true,
			'class'   => true,
		);
		$allowed['path'] = array(
			'd'               => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
		);
		return $allowed;
	}
}
