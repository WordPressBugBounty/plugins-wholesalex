<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- Preserve the established public class and loader filename for compatibility.
/**
 * Role change Action.
 *
 * @package WHOLESALEX
 * @since 2.0.13
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * WholesaleX Category Class.
 */
class WHOLESALEX_RequstRoleChange {

	/**
	 * Order Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'role_change_request_callback' ) );
		add_action( 'rest_api_init', array( $this, 'admin_request_handle_callback' ) );
		add_action( 'rest_api_init', array( $this, 'admin_response_action' ) );
		$is_role_switcher_option_enable = wholesalex()->get_setting( '_settings_role_switcher_option', '' );
		if ( 'yes' === $is_role_switcher_option_enable ) {
			add_action( 'woocommerce_account_dashboard', array( $this, 'request_to_change_role_action' ) );
		}
		add_action( 'woocommerce_account_dashboard', array( $this, 'enqueue_wp_api_fetch' ) );
	}

	/**
	 *  Remove B2C and guest user roles from the roles array.
	 *
	 * @param array $roles The array of roles.
	 * @param bool  $remove_b2c_guest Whether to remove B2C and guest roles.
	 * @return array Filtered roles array.
	 */
	public function wholesalex_remove_b2c_guest_user_role( $roles, $remove_b2c_guest ) {

		if ( $remove_b2c_guest ) {
			return array_filter(
				$roles,
				function ( $role ) {
					return ! in_array( $role['id'], array( 'wholesalex_b2c_users', 'wholesalex_guest' ), true );
				}
			);
		}

		return $roles;
	}

	/**
	 * Request to change role
	 */
	public function request_to_change_role_action() {
		$__roles         = $GLOBALS['wholesalex_roles'];
		$current_user_id = get_current_user_id();
		// Account membership must not inherit a manager's storefront pricing preview.
		$current_role = get_user_meta( $current_user_id, '__wholesalex_role', true );
		$current_role = ! empty( $current_role ) ? $current_role : 'wholesalex_b2c_users';
		$request      = get_user_meta( $current_user_id, 'wsx_change_role_request', true );

		$show_role_request_ui = true;

		if ( is_array( $request ) && isset( $request['status'] ) && 'pending' === $request['status'] ) {
			$show_role_request_ui = false;
		}
		$current_role_name = '';
		if ( isset( $__roles[ $current_role ] ) && isset( $__roles[ $current_role ]['_role_title'] ) ) {
			$current_role_name = $__roles[ $current_role ]['_role_title'];
		}

		$role_titles = array_filter(
			array_map(
				function ( $role ) use ( $current_role ) {
					if ( (string) $role['id'] === (string) $current_role ) {
						return null;
					}
					return array(
						'id'    => $role['id'],
						'title' => $role['_role_title'],
					);
				},
				$__roles
			)
		);

		$role_titles = array_values( array_filter( $role_titles ) );

		// ADDED THIS FILTER TO REMOVE B2C AND GUEST USER ROLES IF ANYONE WANTS TO REMOVE.
		$remove_b2c_guest = apply_filters( 'wsx_remove_b2c_guest_user_role', false ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Preserve the existing WSX-prefixed public filter for backward compatibility.
		if ( $remove_b2c_guest ) {
			$role_titles = $this->wholesalex_remove_b2c_guest_user_role( $role_titles, true );
		}

		?>
		<div style="margin-bottom: 30px;">
			<h2 style="margin-bottom: 15px; font-size: 24px;"><?php echo esc_html__( 'Want to Switch Your User Role?', 'wholesalex' ); ?></h2>
			<?php if ( ! empty( $current_role_name ) ) : ?>
				<p style="margin-bottom: 15px; font-size: 16px;">
					<?php echo esc_html__( 'Your current role is', 'wholesalex' ); ?>
					<span style="font-weight: bold; color: black;">
						<?php echo esc_html( $current_role_name ); ?>
					</span>
				</p>
			<?php endif; ?>
			
			<?php if ( $show_role_request_ui ) : ?>
				<div style="display: flex; gap: 10px; align-items: center;">
					<select id="roleSelect" style="padding: 8px; font-size: 16px; flex: 1;">
						<option value=""><?php echo esc_html__( 'Choose a Role You Want to Switch to', 'wholesalex' ); ?></option>
						<?php
						if ( ! empty( $role_titles ) ) {
							foreach ( $role_titles as $role ) {
								echo '<option value="' . esc_attr( $role['id'] ) . '">' . esc_html( $role['title'] ) . '</option>';
							}
						} else {
							echo '<option value="">No roles available</option>';
						}
						?>
					</select>

					<div id="sendRequestBtn" class="custom-button">
						<?php echo esc_html__( 'Send Request', 'wholesalex' ); ?>
					</div>
				</div>
				<?php else : ?>
					<p><?php echo esc_html__( 'You have already submitted a role change request. Please wait for an admin to respond.', 'wholesalex' ); ?></p>
				<?php endif; ?>
				<!-- Success Message (hidden by default) -->
				<div id="successMessage" style="display: none; margin-top: 10px; padding: 10px 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;">	
					<?php echo esc_html__( 'Your request has been sent successfully!', 'wholesalex' ); ?>
				</div>
		</div>
		<script>
			

			document.addEventListener('DOMContentLoaded', function () {
				const sendRequestBtn = document.getElementById('sendRequestBtn');
				const roleSelect = document.getElementById('roleSelect');
				const successMessage = document.getElementById('successMessage');

				if (!sendRequestBtn || !roleSelect) {
					return;
				}
				const requestUrl = <?php echo wp_json_encode( rest_url( 'wholesalex/v1/role_change_request_action/' ) ); ?>;
				const requestNonce = <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>;

				sendRequestBtn.addEventListener('click', async function () {
					if (roleSelect.value !== '') {
						// If a role is selected, show the success message
						const isConfirmed = confirm('Sure you want to switch to this role?');

						if (isConfirmed) {
							const selectedRole = roleSelect.value;

							const userData = {
								selected_role: selectedRole
							};

							try {
								const res = await fetch(requestUrl, {
									method: 'POST',
									credentials: 'same-origin',
									headers: {
										'Content-Type': 'application/json',
										'X-WP-Nonce': requestNonce
									},
									body: JSON.stringify(userData)
								});

								const result = await res.json();
								if (res.ok && result.success) {
									sendRequestBtn.style.display = 'none';
									roleSelect.disabled = true;
									successMessage.style.display = 'block';

									setTimeout(() => {
										successMessage.style.display = 'none';
									}, 2000);
								} else {
									throw new Error(result.message || (result.data && result.data.message) || <?php echo wp_json_encode( __( 'Error occurred while changing role.', 'wholesalex' ) ); ?>);
								}
							} catch (error) {
								console.error('Request failed', error);
								alert(error.message || <?php echo wp_json_encode( __( 'Error occurred while changing role.', 'wholesalex' ) ); ?>);
							}

						}
					} else {
						// If no role is selected, alert the user
						alert('<?php echo esc_js( __( 'Please select a role first.', 'wholesalex' ) ); ?>');
					}
				});
			});
		</script>
	<style>
		.custom-button {
			padding: 8px 16px;
			font-size: 16px;
			background-color: #0071a1;
			color: white;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			white-space: nowrap;
			transition: background-color 0.3s, transform 0.2s, box-shadow 0.2s;
		}

		.custom-button:hover {
			background-color:rgb(0, 137, 196);
			box-shadow: 0 4px 8px rgba(0,0,0,0.2);
		}
	</style>

		<?php
	}



	/**
	 * Role Change Request Rest API Callback
	 *
	 * @since 2.0.13
	 */
	public function role_change_request_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/role_change_request_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'role_change_restapi_action' ),
					'permission_callback' => array( $this, 'check_user_logged_in' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Admin handle the Request Rest API Callback
	 *
	 * @since 2.0.14
	 */
	public function admin_request_handle_callback() {
		register_rest_route(
			'wholesalex/v1',
			'/role_change_request/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'admin_handle_role_change_request' ),
					'permission_callback' => function () {
						return current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Admin handle the Request Rest API Callback
	 *
	 * @since 2.0.14
	 */
	public function admin_response_action() {
		register_rest_route(
			'wholesalex/v1',
			'/role_change_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'admin_role_change_action' ),
					'permission_callback' => function () {
						return current_user_can( apply_filters( 'wholesalex_capability_access', 'manage_options' ) );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Admin accept or reject role change request
	 *
	 * @param object $server data.
	 *
	 * @since 2.0.14
	 */
	public function admin_role_change_action( $server ) {
		$post = $server->get_params();

		// Nonce validation.
		if ( ! isset( $post['nonce'] ) || ! is_string( $post['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $post['nonce'] ) ), 'wholesalex-registration' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request. Please refresh the page and try again.', 'wholesalex' ) ) );
			return;
		}
		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		$response = array(
			'status' => false,
			'data'   => array(),
		);

		switch ( $type ) {
			case 'update_status':
				$action = isset( $post['user_action'] ) ? sanitize_text_field( $post['user_action'] ) : '';
				$id     = isset( $post['id'] ) ? sanitize_text_field( $post['id'] ) : '';

				// Validate action and user ID.
				if ( empty( $action ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid Action!', 'wholesalex' ) ) );
					return;
				}
				if ( empty( $id ) || ! get_userdata( $id ) ) {
					wp_send_json_error( array( 'message' => __( 'Invalid User ID!', 'wholesalex' ) ) );
					return;
				}

				$operation = 'accept' === $action ? 'accept_role_request' : ( 'reject' === $action ? 'reject_role_request' : '' );
				$error     = User_Operation_Policy::authorize( $operation, get_current_user_id(), $id );
				if ( is_wp_error( $error ) ) {
					return $error;
				}

				if ( 'accept' === $action ) {
					$request = get_user_meta( $id, 'wsx_change_role_request', true );
					if ( isset( $request['selected_role'] ) && ! empty( $request['selected_role'] ) ) {
						$error = User_Operation_Policy::authorize( 'accept_role_request', get_current_user_id(), $id, array( 'role' => $request['selected_role'] ) );
						if ( is_wp_error( $error ) ) {
							return $error;
						}
						wholesalex()->change_role( $id, $request['selected_role'] );
						delete_user_meta( $id, 'wsx_change_role_request' );
					} else {
						wp_send_json_error( array( 'message' => __( 'Role not specified in request.', 'wholesalex' ) ) );
						return;
					}
				} elseif ( 'reject' === $action ) {
					delete_user_meta( $id, 'wsx_change_role_request' );
				}

				$response['status'] = true;
				$response['data']   = ( 'accept' === $action ) ? __( 'Role has been updated.', 'wholesalex' ) : __( 'Request has been rejected.', 'wholesalex' );
				break;
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid request type.', 'wholesalex' ) ) );
				return;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Role changes requested data
	 *
	 * @param object $server data.
	 *
	 * @since 2.0.14
	 */
	public function admin_handle_role_change_request( $server ) {
		$post           = $server->get_params();
		$search_query   = isset( $post['search'] ) ? sanitize_text_field( $post['search'] ) : '';
		$items_per_page = isset( $post['itemsPerPage'] ) ? (int) $post['itemsPerPage'] : 10;

		$error = User_Operation_Policy::authorize( 'read', get_current_user_id(), 0 );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		// Get all users with the meta key 'wsx_change_role_request'.
		$users = get_users(
			array(
				'blog_id'      => get_current_blog_id(),
				'meta_key'     => 'wsx_change_role_request', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Role-change requests are persisted as user metadata.
				'meta_compare' => 'EXISTS',
			)
		);

		$result = array();

		foreach ( $users as $user ) {
			if ( is_wp_error( User_Operation_Policy::authorize( 'read', get_current_user_id(), $user->ID ) ) ) {
				continue;
			}
			$request_data = get_user_meta( $user->ID, 'wsx_change_role_request', true );

			// Optional: only return requests that are pending.
			if ( isset( $request_data['status'] ) && 'pending' === $request_data['status'] ) {
				// Prepare user data.
				$user_data = array(
					'user_id'        => $user->ID,
					'username'       => $user->user_login,
					'email'          => $user->user_email,
					'full_name'      => $user->display_name,
					'current_role'   => $request_data['prev_role'],
					'requested_role' => $this->get_role_title( $request_data['selected_role'] ),
					'status'         => $request_data['status'],
				);

				// If there's a search query, filter by username.
				if ( $search_query ) {
					if ( stripos( $user_data['username'], $search_query ) === false ) {
						continue; // Skip if username doesn't match.
					}
				}

				$result[] = $user_data;
			}
		}

		$response           = array();
		$response['data']   = array(
			'total_users' => count( $result ),
			'users'       => $result,
		);
		$response['status'] = true;

		return rest_ensure_response( $response );
	}


	/**
	 * Get role change title
	 *
	 * @param id $id role_id.
	 *
	 * @since 2.0.14
	 */
	public function get_role_title( $id ) {
		return wholesalex()->get_role_name_by_role_id( $id );
	}


	/**
	 * Check wheather the user is logged in or not
	 *
	 * @since 2.0.14
	 */
	public function check_user_logged_in() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		return true;
	}

	/**
	 * Check wheather the user is logged in or not
	 *
	 * @param object $data data.
	 *
	 * @since 2.0.14
	 */
	public function role_change_restapi_action( $data ) {
		$user_id       = get_current_user_id();
		$selected_role = isset( $data['selected_role'] ) && is_string( $data['selected_role'] ) ? sanitize_key( $data['selected_role'] ) : '';
		$error         = User_Operation_Policy::authorize( 'request', $user_id, $user_id, array( 'role' => $selected_role ) );
		if ( is_wp_error( $error ) ) {
			return $error;
		}
		$user      = get_userdata( $user_id );
		$name      = $user->display_name;
		$email     = $user->user_email;
		$role_id   = get_user_meta( $user_id, '__wholesalex_role', true );
		$role_id   = $role_id ? $role_id : get_user_meta( $user_id, '__wholesalex_registration_role', true );
		$prev_role = wholesalex()->get_role_name_by_role_id( $role_id );

		// Store role change request in user meta.
		$request_data = array(
			'name'          => $name,
			'prev_role'     => $prev_role,
			'selected_role' => $selected_role,
			'email'         => $email,
			'status'        => 'pending',
			'date'          => current_time( 'mysql' ),
		);

		update_user_meta( $user_id, 'wsx_change_role_request', $request_data );

		wp_send_json_success(
			array(
				'status'  => 'success',
				'message' => __( 'Role change request has been submitted.', 'wholesalex' ),
			)
		);
	}

	/**
	 * Validation for Enque api.
	 *
	 * @since 2.0.14
	 */
	public function enqueue_wp_api_fetch() {
		wp_enqueue_script( 'wp-api' );
	}


	/**
	 * Get User Request column
	 *
	 * @return array
	 */
	public static function get_wholesalex_user_role_change_request_column() {
		$columns = array(
			'user_id'        => __( 'ID', 'wholesalex' ),
			'username'       => __( 'Username', 'wholesalex' ),
			'full_name'      => __( 'Full Name', 'wholesalex' ),
			'email'          => __( 'Email', 'wholesalex' ),
			'requested_date' => __( 'Date', 'wholesalex' ),
			'current_role'   => __( 'Current Role', 'wholesalex' ),
			'requested_role' => __( 'Requested Role', 'wholesalex' ),
		);

		$columns = apply_filters( 'wholesalex_user_role_change_request_columns', $columns );

		$columns = wholesalex()->insert_into_array( $columns, array( 'action' => __( 'Action', 'wholesalex' ) ) );

		return $columns;
	}
}
