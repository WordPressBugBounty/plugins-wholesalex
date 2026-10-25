<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName, WordPress.Files.FileName.NotHyphenatedLowercase -- Preserve the public Functions.php include path.
/**
 * Common Functions.
 *
 * @package WHOLESALEX\Functions
 * @since v.1.0.0
 */

namespace WHOLESALEX;

use WP_Query;
use WP_User;
use WHOLESALEX\WHOLESALEX_Email;


defined( 'ABSPATH' ) || exit;

/**
 * Functions class.
 */
class Functions {

	/**
	 * Whether this admin record has configured, persisted tiers to warn about.
	 * Returns only a flag; it does not expose or enable premium pricing data.
	 *
	 * @param string $type Record type: profile, product, or category.
	 * @param int    $id   Record ID.
	 * @return bool
	 */
	public function has_saved_page_tiers( $type, $id ) {
		$id = absint( $id );
		if ( ! $id || ! in_array( $type, array( 'profile', 'product', 'category' ), true ) ) {
			return false;
		}

		$has_rows = static function ( $rows ) {
			foreach ( is_array( $rows ) ? $rows : array() as $row ) {
				foreach ( array( '_discount_type', '_discount_amount', '_min_quantity' ) as $field ) {
					if ( is_array( $row ) && isset( $row[ $field ] ) && is_scalar( $row[ $field ] ) && '' !== trim( (string) $row[ $field ] ) ) {
						return true;
					}
				}
			}
			return false;
		};

		if ( 'profile' === $type ) {
			$data = get_user_meta( $id, '__wholesalex_profile_discounts', true );
			return $has_rows( $data['_profile_discounts']['tiers'] ?? array() );
		}

		$meta_type = 'product' === $type ? 'post' : 'term';
		foreach ( $this->get_roles( 'ids' ) as $role_id ) {
			if ( $has_rows( get_metadata( $meta_type, $id, $role_id . '_tiers', true ) ) ) {
				return true;
			}
		}

		if ( 'category' === $type ) {
			$legacy = get_option( '__wholesalex_category_discounts', array() );
			foreach ( (array) ( $legacy[ $id ] ?? array() ) as $role ) {
				if ( is_array( $role ) && $has_rows( $role['tiers'] ?? array() ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {

		if ( ! isset( $GLOBALS['wholesalex_settings'] ) ) {
			$settings = get_option( 'wholesalex_settings', array() );
			$settings = is_array( $settings ) ? $settings : array();

			// Upgrade B2C-only stores before any role or pricing settings are read.
			$normalized_settings = self::normalize_store_mode( $settings );
			if ( $normalized_settings !== $settings ) {
				// Option-update callbacks may call wholesalex() again.
				$GLOBALS['wholesalex_settings'] = $normalized_settings;
				update_option( 'wholesalex_settings', $normalized_settings );
				$settings = $normalized_settings;
			}

			// The reCAPTCHA settings UI has always displayed v3 as its default.
			// Keep the runtime value in sync so an untouched radio does not fall
			// through to the v2 branches before the first explicit selection.
			if ( ! isset( $settings['recaptcha_version'] ) ) {
				$settings['recaptcha_version'] = 'recaptcha_v3';
			}

			$GLOBALS['wholesalex_settings'] = $settings;
		}
		if ( ! isset( $GLOBALS['wholesalex_single_product_settings'] ) ) {
			$GLOBALS['wholesalex_single_product_settings'] = get_option( '__wholesalex_single_product_settings', array() );
		}
		if ( ! isset( $GLOBALS['wholesalex_single_product_discounts'] ) ) {
			$GLOBALS['wholesalex_single_product_discounts'] = get_option( '__wholesalex_single_product_discounts', array() );
		}
		if ( ! isset( $GLOBALS['wholesalex_dynamic_rules'] ) ) {
			$GLOBALS['wholesalex_dynamic_rules'] = get_option( '__wholesalex_dynamic_rules', array() );
		}
		if ( ! isset( $GLOBALS['wholesalex_pricing_rules'] ) ) {
			$GLOBALS['wholesalex_pricing_rules'] = get_option( '__wholesalex_pricing_rules', array() );
		}
		if ( ! isset( $GLOBALS['wholesalex_roles'] ) ) {
			$GLOBALS['wholesalex_roles'] = get_option( '_wholesalex_roles', array() );
		}
		if ( ! isset( $GLOBALS['wholesalex_category_settings'] ) ) {
			$GLOBALS['wholesalex_category_settings'] = get_option( '__wholesalex_category_settings', array() );
		}
		if ( ! isset( $GLOBALS['wholesalex_category_discounts'] ) ) {
			$GLOBALS['wholesalex_category_discounts'] = get_option( '__wholesalex_category_discounts', array() );
		}
		if ( ! isset( $GLOBALS['wholesalex_profile_settings'] ) ) {
			$GLOBALS['wholesalex_profile_settings'] = get_option( '__wholesalex_profile_settings', array() );
		}
		if ( ! isset( $GLOBALS['wholesalex_profile_discounts'] ) ) {
			$GLOBALS['wholesalex_profile_discounts'] = get_option( '__wholesalex_profile_discounts', array() );
		}
	}

	/**
	 * Backward compatibility only; will be removed after users migrate.
	 *
	 * WordPress.org feedback: 2026-09-16 rejected locked rule creation; the
	 * 2026-10-10 review reference rejected legacy-only access under Guideline 5.
	 * Free rule availability must stay unconditional; entry points enforce authorization.
	 *
	 * @param array|null $rules Optional scoped rules for integration metadata.
	 * @return array
	 */
	public function get_dynamic_rules_access( $rules = null ) {
		$rules = is_array( $rules ) ? $rules : $this->get_dynamic_rules();
		return array(
			'has_rules'  => ! empty( $rules ),
			'can_view'   => true,
			'can_create' => true,
			'mode'       => 'full',
		);
	}

	/**
	 * Compatibility adapter; Dynamic Rules have no installation access policy.
	 *
	 * @return bool
	 */
	public function can_create_dynamic_rules() {
		return true;
	}

	/**
	 * Initialize the installation's onboarding state once, before defaults exist.
	 *
	 * @return string Persistent onboarding status.
	 */
	public function get_onboarding_status() {
		$status = get_option( 'wholesalex_onboarding_status', null );
		if ( null !== $status ) {
			return $status;
		}

		$settings = get_option( 'wholesalex_settings', array() );
		$legacy   = $settings['_settings_onboarding_completed'] ?? null;
		if ( get_option( 'wholesalex_onboarding_completed', false ) || 'yes' === $legacy ) {
			$status = 'completed';
		} else {
			$status = 'pending';
			foreach ( array( 'wholesalex_settings', 'wholesalex_installation_date', '_wholesalex_roles', '__wholesalex_initial_setup', '__wholesalex_dynamic_rules', '__wholesalex_pricing_rules' ) as $option ) {
				if ( null !== get_option( $option, null ) ) {
					$status = 'completed';
					break;
				}
			}
		}

		// add_option cannot overwrite a state initialized by another request.
		add_option( 'wholesalex_onboarding_status', $status, '', false );
		return get_option( 'wholesalex_onboarding_status', 'pending' );
	}

	/**
	 * Check if the product price is hidden.
	 *
	 * @param string $product_id Type.
	 *
	 * @return boolean
	 */
	public function is_price_hidden( $product_id = '' ) {
		$current_id    = get_current_user_id();
		$transient_key = 'whx_hidden_prices_' . $current_id;
		$data          = get_transient( $transient_key );
		if ( isset( $data[ $current_id ] ) && is_array( $data[ $current_id ] ) ) {
			$dynamic_rules = $data[ $current_id ];
		} else {
			$dynamic_rules = array();
		}

		$is_hidden = false;
		$cats      = wc_get_product_term_ids( $product_id, 'product_cat' );

		if ( ! empty( $dynamic_rules['is_all_products'] ) ) {
			$is_hidden = true;
		} elseif ( ! empty( $dynamic_rules['include_product'] ) ) {
			$is_hidden = in_array( $product_id, $dynamic_rules['include_product'], true );
		} elseif ( ! empty( $dynamic_rules['exclude_product'] ) ) {
			$is_hidden = ! in_array( $product_id, $dynamic_rules['exclude_product'], true );
		} elseif ( ! empty( $dynamic_rules['include_cat'] ) ) {
			$is_hidden = ! empty( array_intersect( $cats, $dynamic_rules['include_cat'] ) );
		} elseif ( ! empty( $dynamic_rules['exclude_cat'] ) ) {
			$is_hidden = empty( array_intersect( $cats, $dynamic_rules['exclude_cat'] ) );
		}
		/**
		 * Allow other price-visibility sources (for example role restrictions)
		 * to participate in the shared hidden-price check.
		 *
		 * @param bool $is_hidden  Whether the product price is hidden.
		 * @param int  $product_id Product or variation ID.
		 */
		return (bool) apply_filters( 'wholesalex_is_price_hidden', $is_hidden, absint( $product_id ) );
	}

	/**
	 * Get All WholesaleX Roles
	 *
	 * @param string $type Type.
	 * @param mixed  $id ID.
	 * @return array $roles_options.
	 */
	public function get_roles( $type = '', $id = '' ) {
		$__roles = $GLOBALS['wholesalex_roles'];

		$__plugin_status = wholesalex()->get_setting( '_settings_status' );
		$__plugin_status = empty( $__plugin_status ) ? 'b2b' : $__plugin_status;

		$__enable_guest = apply_filters( 'wholesalex_enable_guest', true );
		if ( 'ids' === $type ) {
			return array_keys( $__roles );
		}
		if ( '' === $id && ( '' === $type || 'all' === $type ) ) {
			return isset( $__roles ) ? $__roles : array();
		} else {
			$roles_option            = array();
			$mapped_roles            = array();
			$b2b_roles_option        = array();
			$b2b_mapped_roles        = array();
			$b2c_roles_option        = array();
			$b2c_mapped_roles        = array();
			$store_mode_roles        = array();
			$store_mode_roles_option = array();
			$store_mode_mapped_roles = array();
			$guest_role_option       = array(
				'value' => 'wholesalex_guest',
				'name'  => 'Guest Users',
			);
			if ( isset( $GLOBALS['wholesalex_roles'] ) ) {
				foreach ( $__roles as $role ) {
					if ( ! ( isset( $role['id'] ) && isset( $role['_role_title'] ) ) ) {
						continue;
					}
					$is_b2c_role = in_array( $role['id'], array( 'wholesalex_b2c_users', 'wholesalex_guest' ), true );
					$is_visible  = 'b2b_n_b2c' === $__plugin_status
						|| ( 'b2b' === $__plugin_status && ! $is_b2c_role );

					if ( $is_visible && ( 'wholesalex_guest' !== $role['id'] || $__enable_guest ) ) {
						$store_mode_roles[ $role['id'] ]        = $role;
						$store_mode_roles_option[]              = array(
							'value' => $role['id'],
							'name'  => $role['_role_title'],
						);
						$store_mode_mapped_roles[ $role['id'] ] = $role['_role_title'];
					}

					$roles_option[]              = array(
						'value' => $role['id'],
						'name'  => $role['_role_title'],
					);
					$mapped_roles[ $role['id'] ] = $role['_role_title'];

					if ( ! ( 'wholesalex_b2c_users' === $role['id'] || 'wholesalex_guest' === $role['id'] ) ) {
						$b2b_roles_option[]              = array(
							'value' => $role['id'],
							'name'  => $role['_role_title'],
						);
						$b2b_mapped_roles[ $role['id'] ] = $role['_role_title'];
					} elseif ( ( 'wholesalex_b2c_users' === $role['id'] || ( $__enable_guest && 'wholesalex_guest' === $role['id'] ) ) ) {
						$b2c_roles_option[]              = array(
							'value' => $role['id'],
							'name'  => $role['_role_title'],
						);
						$b2c_mapped_roles[ $role['id'] ] = $role['_role_title'];
					}
				}
			}
			if ( '' === $id ) {
				switch ( $type ) {
					case 'store_mode_roles':
						return $store_mode_roles;
					case 'store_mode_roles_option':
						return $store_mode_roles_option;
					case 'store_mode_mapped_roles':
						return $store_mode_mapped_roles;
					case 'roles_option':
						return $roles_option;
					case 'mapped_roles':
						return $mapped_roles;
					case 'b2b_roles_option':
						if ( 'b2b' === $__plugin_status || 'b2b_n_b2c' === $__plugin_status ) {
							return $b2b_roles_option;
						} else {
							return array();
						}
					case 'b2b_mapped_roles':
						if ( 'b2b' === $__plugin_status || 'b2b_n_b2c' === $__plugin_status ) {
							return $b2b_mapped_roles;
						} else {
							return array();
						}
					case 'b2c_roles_option':
						if ( 'b2b_n_b2c' === $__plugin_status ) {
							return $b2c_roles_option;
						} else {
							return array( $guest_role_option );
						}
					case 'b2c_mapped_roles':
						if ( 'b2b_n_b2c' === $__plugin_status ) {
							return $b2c_mapped_roles;
						} else {
							return array();
						}
				}
			}
			if ( 'by_id' === $type && '' !== $id ) {
				return isset( $__roles[ $id ] ) ? $__roles[ $id ] : array();
			}
		}
	}

	/**
	 * Get the built-in WholesaleX roles that must exist on every site.
	 *
	 * @return array
	 */
	public function get_default_roles() {
		return apply_filters(
			'wholesalex_initial_roles',
			array(
				'wholesalex_b2c_users'     => array(
					'id'          => 'wholesalex_b2c_users',
					'_role_title' => __( 'B2C Users', 'wholesalex' ),
					'removeable'  => false,
				),
				'wholesalex_guest'         => array(
					'id'          => 'wholesalex_guest',
					'_role_title' => __( 'Guest Users', 'wholesalex' ),
					'removeable'  => false,
				),
				'wholesalex_b2b_wholesale' => array(
					'id'          => 'wholesalex_b2b_wholesale',
					'_role_title' => __( 'Wholesale User', 'wholesalex' ),
					'removeable'  => true,
				),
			)
		);
	}

	/**
	 * Get roles that are required system roles and cannot be deleted.
	 *
	 * @return array
	 */
	public function get_protected_role_ids() {
		return apply_filters(
			'wholesalex_protected_role_ids',
			array(
				'wholesalex_b2c_users',
				'wholesalex_guest',
			)
		);
	}

	/**
	 * Check whether a WholesaleX role may be deleted.
	 *
	 * @param string $role_id Role ID.
	 * @return bool
	 */
	public function is_deletable_role( $role_id ) {
		$role_id = sanitize_key( $role_id );
		if ( empty( $role_id ) ) {
			return false;
		}

		return ! in_array( $role_id, $this->get_protected_role_ids(), true );
	}

	/**
	 * Ensure default WholesaleX roles exist in both plugin data and WP roles.
	 *
	 * @return array Role IDs created in plugin role data during this call.
	 */
	public function ensure_default_roles() {
		$roles         = get_option( '_wholesalex_roles', array() );
		$roles         = is_array( $roles ) ? $roles : array();
		$created_roles = array();
		$updated       = false;
		$deleted_roles = get_option( '_wholesalex_deleted_default_roles', array() );
		$deleted_roles = is_array( $deleted_roles ) ? $deleted_roles : array();

		foreach ( $this->get_default_roles() as $role_id => $default_role ) {
			if ( empty( $default_role['id'] ) || empty( $default_role['_role_title'] ) ) {
				continue;
			}

			$role_id = sanitize_key( $default_role['id'] );
			if ( empty( $role_id ) ) {
				continue;
			}
			$default_role['id'] = $role_id;
			if (
				'wholesalex_b2b_wholesale' === $role_id
				&& in_array( $role_id, $deleted_roles, true )
			) {
				continue;
			}

			if ( empty( $roles[ $role_id ] ) || ! is_array( $roles[ $role_id ] ) ) {
				$roles[ $role_id ] = $default_role;
				$created_roles[]   = $role_id;
				$updated           = true;
			} else {
				if ( empty( $roles[ $role_id ]['id'] ) ) {
					$roles[ $role_id ]['id'] = $role_id;
					$updated                 = true;
				}
				if ( empty( $roles[ $role_id ]['_role_title'] ) ) {
					$roles[ $role_id ]['_role_title'] = $default_role['_role_title'];
					$updated                          = true;
				}
				if (
					'wholesalex_b2b_wholesale' === $role_id
					&& 'B2B: Wholesale Role' === $roles[ $role_id ]['_role_title']
				) {
					$roles[ $role_id ]['_role_title'] = $default_role['_role_title'];
					$updated                          = true;
				}
				if ( ! array_key_exists( 'removeable', $roles[ $role_id ] ) ) {
					$roles[ $role_id ]['removeable'] = false;
					$updated                         = true;
				}
			}

			if ( ! wp_roles()->is_role( $role_id ) ) {
				add_role( $role_id, $roles[ $role_id ]['_role_title'], array( 'read' => true ) );
			}
		}

		if ( $updated ) {
			update_option( '_wholesalex_roles', $roles );
			$GLOBALS['wholesalex_roles'] = get_option( '_wholesalex_roles', array() );
		} else {
			$GLOBALS['wholesalex_roles'] = $roles;
		}

		return $created_roles;
	}

	/**
	 * Get All Users
	 *
	 * @since 1.0.0
	 * @since 1.0.9 wholesalex_get_users_query filter added
	 */
	public function get_users() {
		$users        = get_users(
			apply_filters(
				'wholesalex_get_users_query',
				array(
					'fields' => array( 'ID', 'user_login' ),
				)
			)
		);
		$user_options = array();
		$mapped_users = array();
		foreach ( $users as $user ) {
			$user_options[]                      = array(
				'name'  => $user->user_login,
				'value' => 'user_' . $user->ID,
			);
			$mapped_users[ 'user_' . $user->ID ] = $user->user_login;
		}
		return array(
			'user_options' => $user_options,
			'mapped_users' => $mapped_users,
		);
	}

	/**
	 * Assign New Role To User
	 *
	 * @param int    $user_id User ID.
	 * @param string $new_role_id New Role ID.
	 * @param string $prev_role_id Previous Role ID.
	 * @return void
	 */
	public function change_role( $user_id, $new_role_id, $prev_role_id = '' ) {
		if ( ! ( isset( $new_role_id ) && ! empty( $new_role_id ) ) ) {
			return;
		}
		$wholesalex_roles = wholesalex()->get_roles( 'ids' );
		// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Keep compatibility with role IDs supplied by existing integrations.
		if ( ! in_array( $new_role_id, $wholesalex_roles ) ) {
			return;
		}
		$user = new WP_User( $user_id );
		do_action( 'wholesalex_before_role_update', $user_id, $new_role_id, $prev_role_id );
		if ( '' !== $prev_role_id ) {
			$user->remove_role( $prev_role_id );
			$user->remove_cap( $prev_role_id );
		}
		$user->add_role( $new_role_id );
		/**
		 * Add User Cap as wholesalex role.
		 *
		 * @since 1.1.2
		 */
		$user->add_cap( $new_role_id );

		update_user_meta( $user_id, '__wholesalex_role', $new_role_id );

		do_action( 'wholesalex_user_role_updated', $user_id, $prev_role_id, $new_role_id );
	}

	/**
	 * Assign administrator users to the default B2B role when they do not have a
	 * WholesaleX role yet.(Fresh Installation)
	 *
	 * @param string $role_id Role ID.
	 * @return void
	 */
	public function assign_admin_wholesale_role( $role_id = 'wholesalex_b2b_wholesale' ) {
		if ( empty( wholesalex()->get_roles( 'by_id', $role_id ) ) ) {
			return;
		}

		$admin_users = get_users(
			array(
				'role'   => 'administrator',
				'fields' => array( 'ID' ),
			)
		);

		foreach ( $admin_users as $admin ) {
			$existing_role = get_user_meta( $admin->ID, '__wholesalex_role', true );
			if ( empty( $existing_role ) ) {
				// Default admin assignment does not require registration approval.
				if ( '' === get_user_meta( $admin->ID, '__wholesalex_status', true ) &&
					'' === get_user_meta( $admin->ID, '__wholesalex_registration_role', true ) ) {
					require_once __DIR__ . '/registration/class-confirmation-service.php';
					$result = Confirmation_Service::set_status( $admin->ID, 'active' );
					if ( is_wp_error( $result ) ) {
						return $result;
					}
				}
				wholesalex()->change_role( $admin->ID, $role_id );
			}
		}

		update_option( '_wholesalex_default_admin_role_assigned', 'yes' );
	}

	/**
	 * Remove a WholesaleX role from users that currently have it.
	 *
	 * @param string $role_id Role ID.
	 * @return void
	 */
	public function unassign_users_from_role( $role_id ) {
		$current_role_users      = get_users(
			array(
				'fields'     => array( 'ID' ),
				'meta_key'   => '__wholesalex_role', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Role selection uses the stored user metadata; WordPress has no indexed lookup for these plugin keys.
				'meta_value' => $role_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Role selection uses the stored user metadata; WordPress has no indexed lookup for these plugin keys.
			)
		);
		$registration_role_users = get_users(
			array(
				'fields'     => array( 'ID' ),
				'meta_key'   => '__wholesalex_registration_role', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Role selection uses the stored user metadata; WordPress has no indexed lookup for these plugin keys.
				'meta_value' => $role_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Role selection uses the stored user metadata; WordPress has no indexed lookup for these plugin keys.
			)
		);
		$user_ids                = array_unique(
			array_merge(
				wp_list_pluck( $current_role_users, 'ID' ),
				wp_list_pluck( $registration_role_users, 'ID' )
			)
		);

		foreach ( $user_ids as $user_id ) {
			$wp_user = new WP_User( $user_id );
			$wp_user->remove_role( $role_id );
			$wp_user->remove_cap( $role_id );
			delete_user_meta( $user_id, '__wholesalex_role', $role_id );
			delete_user_meta( $user_id, '__wholesalex_registration_role', $role_id );
		}
	}

	/**
	 * Delete a B2B WholesaleX role and clean related user assignments.
	 *
	 * @param string $role_id Role ID.
	 * @return bool|\WP_Error
	 */
	public function delete_role( $role_id ) {
		$role_id = sanitize_key( $role_id );
		if ( ! $this->is_deletable_role( $role_id ) ) {
			return false;
		}

		$roles = get_option( '_wholesalex_roles', array() );
		$roles = is_array( $roles ) ? $roles : array();
		if ( empty( $roles[ $role_id ] ) ) {
			return false;
		}

		require_once __DIR__ . '/roles/class-role-data-boundary.php';
		$prepared = Role_Data_Boundary::prepare( array(), $roles[ $role_id ], 'delete' );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		unset( $roles[ $role_id ] );
		update_option( '_wholesalex_roles', $roles );
		$GLOBALS['wholesalex_roles'] = $roles;

		if ( wp_roles()->is_role( $role_id ) ) {
			remove_role( $role_id );
		}

		$this->unassign_users_from_role( $role_id );

		if ( array_key_exists( $role_id, $this->get_default_roles() ) ) {
			$deleted_roles = get_option( '_wholesalex_deleted_default_roles', array() );
			$deleted_roles = is_array( $deleted_roles ) ? $deleted_roles : array();
			if ( ! in_array( $role_id, $deleted_roles, true ) ) {
				$deleted_roles[] = $role_id;
				update_option( '_wholesalex_deleted_default_roles', $deleted_roles );
			}
		}

		return true;
	}

	/**
	 * Set Link with the Parameters
	 *
	 * @param STRING $url Url.
	 * @param STRING $tag Tag.
	 * @since v.1.0.0
	 * @return STRING | URL with Arg
	 */
	public function get_premium_link( $url = '', $tag = 'go_premium' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Keep the public method signature compatible with existing callers.
		return $url ? $url : 'https://www.wpxpo.com/wholesalex/pricing/';
	}

	/**
	 * Get Global Plugin Settings
	 *
	 * @since 1.0.0
	 * @param STRING $key Key of the Option.
	 * @param STRING $new_default Default Value.
	 * @return ARRAY | STRING
	 * @since 1.2.4 Add Default Option
	 */
	public function get_setting( $key = '', $new_default = '' ) {
		$data = self::normalize_store_mode( $GLOBALS['wholesalex_settings'] );
		// Merge legacy page-specific settings until the unified toggle is saved.
		if ( is_array( $data ) && ! isset( $data['_settings_login_to_view_price'] ) ) {
			$data['_settings_login_to_view_price'] = ( 'yes' === ( $data['_settings_login_to_view_price_product_page'] ?? 'no' ) || 'yes' === ( $data['_settings_login_to_view_price_product_list'] ?? 'no' ) ) ? 'yes' : 'no';
		}
		if ( '' !== $key ) {
			return isset( $data[ $key ] ) ? $data[ $key ] : $new_default;
		} else {
			return $data;
		}
	}


	/**
	 * Map the retired B2C-only store mode to hybrid without changing other data.
	 * Also used by the option sanitizer for legacy imports and direct updates.
	 *
	 * @param mixed $settings Settings option value.
	 * @return mixed
	 */
	public static function normalize_store_mode( $settings ) {
		if ( is_array( $settings ) && isset( $settings['_settings_status'] ) && 'b2c' === $settings['_settings_status'] ) {
			$settings['_settings_status'] = 'b2b_n_b2c';
		}
		return $settings;
	}

	/**
	 * Set Option Settings
	 *
	 * @since 1.0.0
	 * @param STRING $key Key of the Option .
	 * @param STRING $val Value of the Option .
	 */
	public function set_setting( $key = '', $val = '' ) {
		if ( '' !== $key ) {
			$data         = $GLOBALS['wholesalex_settings'];
			$data[ $key ] = $val;
			$data         = self::normalize_store_mode( $data );
			update_option( 'wholesalex_settings', $data );
			$GLOBALS['wholesalex_settings'] = $data;
		}
	}

	/**
	 * Set Single Product Discounts
	 *
	 * @param mixed $id Product ID.
	 * @param array $discounts Single Product Discounts.
	 * @since 1.0.0
	 * @since 1.1.5 Add rolewise price on product meta.
	 */
	public function save_single_product_discount( $id = '', $discounts = array() ) {
		foreach ( $discounts as $values ) {
			if ( ! is_array( $values ) ) {
				return new \WP_Error( 'wholesalex_invalid_record', __( 'Invalid price record.', 'wholesalex' ) );
			}
			$unknown = array_diff( array_keys( $values ), array( 'wholesalex_base_price', 'wholesalex_sale_price' ) );
			if ( $unknown ) {
				// A provider owns the entire command, including its non-tier values.
				// It must validate and persist together before any Free write occurs.
				$result = apply_filters( 'wholesalex_product_discount_write_result', null, $id, $discounts );
				return null !== $result ? $result : new \WP_Error( 'wholesalex_provider_unavailable', __( 'The provider for these values is unavailable.', 'wholesalex' ) );
			}
		}
		if ( '' !== $id && ! empty( $discounts ) ) {
			foreach ( $discounts as $role_name => $value ) {
				$base_price_meta_name = $role_name . '_base_price';
				$sale_price_meta_name = $role_name . '_sale_price';
				$price_meta_name      = $role_name . '_price';

				if ( isset( $value['wholesalex_sale_price'] ) && ! empty( $value['wholesalex_sale_price'] ) ) {
					update_post_meta( $id, $price_meta_name, $value['wholesalex_sale_price'] );
				} elseif ( ! empty( $value['wholesalex_base_price'] ) ) {
					update_post_meta( $id, $price_meta_name, $value['wholesalex_base_price'] );
				}

				// Update Base Price.
				if ( isset( $value['wholesalex_base_price'] ) ) {
					update_post_meta( $id, $base_price_meta_name, $value['wholesalex_base_price'] );
				}
				// Update Sale Price.
				if ( isset( $value['wholesalex_sale_price'] ) ) {
					update_post_meta( $id, $sale_price_meta_name, $value['wholesalex_sale_price'] );
				}
			}
		}
	}

	/**
	 * Get Single Product Discounts
	 *
	 * @param mixed $id Product ID.
	 * @return array
	 * @since 1.0.0
	 * @since 1.1.5 Updated Meta key on single product discounts
	 */
	public function get_single_product_discount( $id = '' ) {

		$data     = array();
		$role_ids = wholesalex()->get_roles( 'ids' );
		foreach ( $role_ids as $role_id ) {
			$sale_price                                = get_post_meta( $id, $role_id . '_sale_price', true );
			$base_price                                = get_post_meta( $id, $role_id . '_base_price', true );
			$data[ $role_id ]['wholesalex_sale_price'] = $sale_price ? $sale_price : '';
			$data[ $role_id ]['wholesalex_base_price'] = $base_price ? $base_price : '';
		}
		/**
		 * Filter a product's role-based prices.
		 *
		 * @param array      $data Role-based prices keyed by role ID.
		 * @param int|string $id   Product ID.
		 */
		return apply_filters( 'wholesalex_single_product_discount', $data, $id );
	}
	/**
	 * Save Single Product Settings
	 *
	 * @param mixed $id Product ID.
	 * @param array $settings Single Product WholesaleX Setting Data.
	 * @return void
	 */
	public function save_single_product_settings( $id = '', $settings = array() ) {
		if ( '' !== $id && ! empty( $settings ) ) {
			$data        = $GLOBALS['wholesalex_single_product_settings'];
			$data[ $id ] = $settings;
			update_option( '__wholesalex_single_product_settings', $data );
			$GLOBALS['wholesalex_single_product_settings'] = $data;
		}
	}


	/**
	 * Save Category Visibiltiy Settings
	 *
	 * @param mixed $id Category ID.
	 * @param array $settings Category WholesaleX Setting Data.
	 * @return void
	 */
	public function save_category_visibility_settings( $id = '', $settings = array() ) {
		if ( '' !== $id && ! empty( $settings ) ) {
			$data        = $GLOBALS['wholesalex_category_settings'];
			$data[ $id ] = $settings;
			update_option( '__wholesalex_category_settings', $data );
			$GLOBALS['wholesalex_category_settings'] = $data;
		}
	}
	/**
	 * Get Category Visibiltiy Settings
	 *
	 * @param mixed $id Category ID.
	 * @return array
	 */
	public function get_category_visibility_settings( $id = '' ) {
		$data = $GLOBALS['wholesalex_category_settings'];
		if ( '' !== $id ) {
			return isset( $data[ $id ] ) ? $data[ $id ] : array();
		} else {
			return $data;
		}
	}
	/**
	 * Save Category Discounts
	 *
	 * This plugin does not register category-level role discounts. The action
	 * below lets an extension that adds such a field persist its own data.
	 *
	 * @param mixed $id Category ID.
	 * @param array $discounts Category WholesaleX Discounts Data.
	 * @return mixed Provider result or WP_Error when unavailable.
	 */
	public function save_category_discounts( $id = '', $discounts = array() ) {
		$result = apply_filters( 'wholesalex_save_category_discount_provider', null, $id, $discounts );
		return null !== $result ? $result : new \WP_Error( 'wholesalex_provider_unavailable', __( 'The provider for these values is unavailable.', 'wholesalex' ) );
	}

	/**
	 * Get Category Discounts
	 *
	 * @param mixed $id Category ID.
	 * @return array
	 */
	public function get_category_discounts( $id = '' ) {
		/**
		 * Filter a category's role-based discounts.
		 *
		 * @param array      $discounts Discounts keyed by role ID.
		 * @param int|string $id        Category ID, or '' for all categories.
		 */
		return (array) apply_filters( 'wholesalex_category_discounts', array(), $id );
	}


	/**
	 * Get Single Product Settings
	 *
	 * @param mixed  $id Product ID.
	 * @param string $key Setting Key.
	 * @return array
	 * @since 1.0.0
	 */
	public function get_single_product_setting( $id = '', $key = '' ) {
		$data = $GLOBALS['wholesalex_single_product_settings'];
		if ( '' !== $id ) {
			if ( '' !== $key ) {
				return isset( $data[ $id ][ $key ] ) ? $data[ $id ][ $key ] : wholesalex()->get_single_product_default_settings( $key );
			}
			return isset( $data[ $id ] ) ? $data[ $id ] : array();
		} else {
			return $data;
		}
	}


	/**
	 * Get Dynamic Rules
	 *
	 * @param mixed $id Rule ID.
	 * @return array If Rule ID specify, return specific rule otherwise return all roles.
	 */
	public function get_dynamic_rules( $id = '' ) {
		$__rules = $GLOBALS['wholesalex_dynamic_rules'];
		if ( '' === $id ) {
			return ( isset( $__rules ) && is_array( $__rules ) ) ? $__rules : array();
		} else {
			return isset( $__rules[ $id ] ) ? $__rules[ $id ] : array();
		}
	}

	/**
	 * Get Dynamic Rules
	 *
	 * @return boolean
	 */
	public function is_any_dynamic_rules_active() {
		$__rules         = $GLOBALS['wholesalex_dynamic_rules'];
		$all_deactivated = true;

		foreach ( $__rules as $rule ) {
			if ( in_array( $rule['_rule_status'] ?? '', array( true, 1, '1', 'yes' ), true ) ) {
				$all_deactivated = false;
				break;
			}
		}
		return $all_deactivated;
	}


	/**
	 * Get Dynamic Rules By User ID
	 *
	 * @param string $user_id User ID.
	 * @return array
	 * @since 1.2.4 (With Dokan Integration)
	 */
	public function get_dynamic_rules_by_user_id( $user_id = '' ) {
		if ( '' === $user_id ) {
			$user_id = get_current_user_id();
		}

		$rules      = $this->get_dynamic_rules();
		$user_rules = array();

		foreach ( $rules as $rule ) {
			// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Stored author IDs and caller IDs may be integers or numeric strings.
			if ( isset( $rule['created_by'] ) && $rule['created_by'] == $user_id ) {
				$user_rules[] = $rule;
			}
		}

		return $user_rules;
	}

	/**
	 * Serialize rule-option mutations against authoritative persisted data.
	 *
	 * @param string   $option_key Option name.
	 * @param string   $global_key Request cache name.
	 * @param callable $callback Receives the current array; returns its replacement or WP_Error.
	 * @return true|\WP_Error
	 */
	public function mutate_rule_option( $option_key, $global_key, callable $callback ) {
		global $wpdb;
		$error = new \WP_Error( 'wholesalex_rule_write_failed', __( 'The rules could not be saved. Please try again.', 'wholesalex' ), array( 'status' => 503 ) );
		if ( ! $wpdb || ! is_callable( array( $wpdb, 'get_var' ) ) || ! is_callable( array( $wpdb, 'prepare' ) ) ) {
			return $error;
		}
		$lock = 'wsx-rules:' . substr( hash( 'sha256', $wpdb->options . ':' . $option_key ), 0, 40 );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Connection-owned mutation lock.
		if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) {
			return $error;
		}
		try {
			$current = $this->get_option_without_cache( $option_key, array() );
			$current = is_array( $current ) ? $current : array();
			$next    = $callback( $current );
			if ( is_wp_error( $next ) ) {
				return $next;
			}
			if ( ! is_array( $next ) ) {
				return $error;
			}
			if ( $next !== $current ) {
				// update_option reads these caches before deciding whether a write is needed.
				wp_cache_delete( $option_key, 'options' );
				wp_cache_delete( 'alloptions', 'options' );
				wp_cache_delete( 'notoptions', 'options' );
				update_option( $option_key, $next );
				if ( $next !== $this->get_option_without_cache( $option_key, array() ) ) {
					return $error;
				}
			}
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Callers provide the established WholesaleX request-cache key.
			$GLOBALS[ $global_key ] = $next;
			return true;
		} finally {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Release this connection's lock.
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}

	/**
	 * Atomically mutate the legacy dynamic rule store.
	 *
	 * @param callable $callback Rule mutation.
	 * @return true|\WP_Error
	 */
	public function mutate_dynamic_rules( callable $callback ) {
		$result = $this->mutate_rule_option( '__wholesalex_dynamic_rules', 'wholesalex_dynamic_rules', $callback );
		if ( ! is_wp_error( $result ) ) {
			delete_transient( 'wholesalex_available_dynamic_rules' );
		}
		return $result;
	}

	/**
	 * Set Dynamic Rules
	 *
	 * @param string  $_id Rule ID.
	 * @param array   $_rule Rule.
	 * @param string  $_type Type.
	 * @param boolean $is_frontend Is the request come from fronend.
	 * @return bool|\WP_Error|null
	 * @since 1.0.0
	 * @since 1.0.1 Usages Count Added
	 * @since 1.2.4 Is frontend check added
	 */
	public function set_dynamic_rules( $_id = '', $_rule = array(), $_type = '', $is_frontend = false ) {
		if ( '' !== $_id && ! empty( $_rule ) ) {

			try {
				$result = $this->mutate_dynamic_rules(
					function ( $__rules ) use ( $_id, $_rule, $_type, $is_frontend ) {
						$existing = isset( $__rules[ $_id ] ) && is_array( $__rules[ $_id ] ) ? $__rules[ $_id ] : array();
						require_once __DIR__ . '/dynamic-rules/class-dynamic-rule-record.php';
						$authority = apply_filters( 'wholesalex_dynamic_rule_import_authority', $existing, $_id );
						$authority = empty( $existing ) && is_array( $authority ) ? $authority : $existing;
						$canonical = 'delete' === $_type ? $existing : Dynamic_Rule_Record::merge( $authority, $_rule, $_id );
						if ( is_wp_error( $canonical ) ) {
								return $canonical;
						}
						if ( isset( $existing['limit']['usages_count'] ) ) {
							$canonical['limit']['usages_count'] = $existing['limit']['usages_count'];
						}
						$canonical = 'delete' === $_type ? $canonical : apply_filters( 'wholesalex_save_dynamic_rules', $canonical, $is_frontend );
						/**
						 * Providers return canonical owned fragments, never a raw submitted object.
						 * The optional fifth argument supplies the canonical generic record.
						 */
						$provider = apply_filters( 'wholesalex_dynamic_rule_provider_result', null, $_type, $_rule, $existing, $canonical );
						if ( is_wp_error( $provider ) ) {
							return $provider;
						}
						$provider_owned = 'quantity_based' === ( $canonical['_rule_type'] ?? '' ) || 'quantity_based' === ( $existing['_rule_type'] ?? '' );
						if ( $provider_owned && ! is_array( $provider ) ) {
							return new \WP_Error( 'wholesalex_provider_unavailable', __( 'The saved rule requires its pricing provider.', 'wholesalex' ), array( 'status' => 409 ) );
						}
						if ( is_array( $provider ) ) {
							$canonical = array_replace( $canonical, $provider );
						}

						$__rules[ $_id ] = $canonical;
						if ( 'delete' === $_type ) {
							unset( $__rules[ $_id ] );
						}
						return $__rules;
					}
				);
				if ( is_wp_error( $result ) ) {
					return $result;
				}
				delete_transient( 'wholesalex_available_dynamic_rules' );

				do_action( 'wholesalex_dynamic_rules_updated', $_id );
				return true;
			} finally {
				do_action( 'wholesalex_dynamic_rule_write_finished', $_id );
			}
		}
	}
	/**
	 * Set wholesalex roles
	 *
	 * @param string $_id Role ID.
	 * @param array  $_role Role.
	 * @param string $_type Type.
	 * @return bool|null|\WP_Error
	 */
	public function set_roles( $_id = '', $_role = array(), $_type = '' ) {
		if ( ! is_array( $_role ) ) {
			return new \WP_Error( 'wholesalex_invalid_role', __( 'Invalid role data.', 'wholesalex' ), array( 'status' => 400 ) );
		}
		if ( '' !== $_id && ! empty( $_role ) ) {
			if ( 'delete' === $_type ) {
				return $this->delete_role( $_id );
			}
			require_once __DIR__ . '/roles/class-role-data-boundary.php';
			$__roles = get_option( '_wholesalex_roles', array() );
			$__roles = is_array( $__roles ) ? $__roles : array();
			$stored  = isset( $__roles[ $_id ] ) && is_array( $__roles[ $_id ] ) ? $__roles[ $_id ] : array();
			$_role   = Role_Data_Boundary::prepare( $_role, $stored );
			if ( is_wp_error( $_role ) ) {
				return $_role;
			}

			// System role names are identifiers in the UI and must not be renamed,
			// including by requests that bypass the disabled admin field.
			if ( in_array( $_id, array( 'wholesalex_b2c_users', 'wholesalex_guest' ), true ) ) {
				$default_roles = $this->get_default_roles();
				if ( ! empty( $__roles[ $_id ]['_role_title'] ) ) {
					$_role['_role_title'] = $__roles[ $_id ]['_role_title'];
				} elseif ( ! empty( $default_roles[ $_id ]['_role_title'] ) ) {
					$_role['_role_title'] = $default_roles[ $_id ]['_role_title'];
				}
			}

			if ( isset( $__roles[ $_id ] ) && ! empty( $__roles[ $_id ] ) ) {
				// update.
				$__roles[ $_id ] = $_role;

			} else {
				$__roles[ $_id ] = $_role;
				add_role( $_id, $_role['_role_title'], array( 'read' => true ) );
			}

			$deleted_roles = get_option( '_wholesalex_deleted_default_roles', array() );
			$deleted_roles = is_array( $deleted_roles ) ? $deleted_roles : array();
			if ( in_array( $_id, $deleted_roles, true ) ) {
				$deleted_roles = array_values( array_diff( $deleted_roles, array( $_id ) ) );
				update_option( '_wholesalex_deleted_default_roles', $deleted_roles );
			}

			update_option( '_wholesalex_roles', $__roles );
			$GLOBALS['wholesalex_roles'] = $__roles;
			return true;
		}
	}


	/**
	 * Get User Role
	 *
	 * @param mixed $user_id User ID.
	 * @return string
	 * @since 1.0.0
	 */
	public function get_user_role( $user_id = '' ) {
		if ( '' === $user_id || ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( ! is_user_logged_in() ) {
			return 'wholesalex_guest';
		}

		$role = get_user_meta( $user_id, '__wholesalex_role', true );
		$role = ! empty( $role ) ? $role : 'wholesalex_b2c_users';

		/** Filter the effective pricing role without changing the assigned account role. */
		return apply_filters( 'wholesalex_user_role', $role, (int) $user_id );
	}


	/**
	 * Get Form Data
	 *
	 * @param string $role_id Role ID.
	 * @param string $type Form Data Type.
	 * @param bool   $is_only_b2b Does Dropdown Contain All B2B Roles.
	 * @return array Form Data.
	 * @since 1.0.0
	 * @since 1.0.3 Billing and Registration Field Merged At Checkout Registration Issue Fixed
	 * @since 1.1.10 Get Only B2B Role in Form Dropdown option Added
	 */
	public function get_form_data( $role_id = '', $type = 'registration', $is_only_b2b = false ) {
		$__form_data = get_option( '__wholesalex_registration_form' );
		if ( empty( $__form_data ) ) {
			return array();
		}

		$__form_data = json_decode( $__form_data, true );

		if ( ! is_array( $__form_data ) ) {
			return array();
		}

		if ( ! empty( $role_id ) ) {
			$__registration_form_data = array();
			$__billing_form_data      = array();

			foreach ( $__form_data as $field ) {
				$__exclude_status = false;
				if ( isset( $field['excludeRoles'] ) && ! empty( $field['excludeRoles'] ) ) {
					foreach ( $field['excludeRoles'] as $role ) {
						if ( isset( $role['value'] ) && $role_id === $role['value'] ) {
							$__exclude_status = true;
							break;
						}
					}
				}
				if ( $__exclude_status ) {
					continue;
				}

				if ( isset( $field['enableForRegistration'] ) && $field['enableForRegistration'] ) {
					array_push( $__registration_form_data, $field );
				}
				if ( isset( $field['enableForBillingForm'] ) && $field['enableForBillingForm'] ) {
					array_push( $__billing_form_data, $field );
				}
			}
			if ( 'registration' === $type ) {
				return $__registration_form_data;
			}
			if ( 'billing' === $type ) {
				return $__billing_form_data;
			}
		} else {
			$__registration_form_data = array();
			$__fields                 = array();
			$__billing_form_data      = array();

			$__roles        = wholesalex()->get_roles( 'store_mode_roles_option' );
			$__roles_option = array();
			foreach ( $__roles as $id => $role ) {
				if ( $is_only_b2b && isset( $role['value'] ) && 'wholesalex_b2c_users' === $role['value'] ) {
					continue;
				}
				if ( isset( $role['value'] ) && 'wholesalex_guest' !== $role['value'] ) {
					array_push( $__roles_option, $role );
				}
			}

			foreach ( $__form_data as $field ) {
				if ( 'user_email' === $field['name'] || 'user_pass' === $field['name'] ) {
					array_push( $__registration_form_data, $field );
				} else {
					$field['dependsOn'] = isset( $field['excludeRoles'] ) ? $field['excludeRoles'] : $field['excludeRoles'];
					if ( isset( $field['enableForRegistration'] ) && $field['enableForRegistration'] ) {
						array_push( $__fields, $field );
					}
					if ( isset( $field['enableForBillingForm'] ) && $field['enableForBillingForm'] ) {
						array_push( $__billing_form_data, $field );
					}
				}
			}

			$__select_role_dropdown = array(
				'id'       => 9999999,
				'type'     => 'select',
				'title'    => apply_filters( 'wholesalex_global_registration_form_select_roles_title', __( 'Select Registration Roles', 'wholesalex' ) ),
				'name'     => 'registration_role',
				'option'   => $__roles_option,
				'empty'    => true,
				'required' => true,
			);
			array_push( $__registration_form_data, $__select_role_dropdown );
			if ( 'registration' === $type ) {
				return array_merge( $__registration_form_data, $__fields );
			}
			if ( 'billing' === $type ) {
				return $__billing_form_data;
			}
		}
	}


	/**
	 * Persist a complete settings snapshot after an authorized admin patch.
	 * The caller holds the backing-option lock and has merged owned leaves.
	 *
	 * @param array $settings Complete saved-domain settings.
	 * @return void
	 */
	public function save_admin_settings_data( array $settings ) {
		$settings = self::normalize_store_mode( $settings );
		update_option( 'wholesalex_settings', $settings );
		$GLOBALS['wholesalex_settings'] = $settings;
	}


	/**
	 * Set Option Settings Multiple
	 *
	 * @since 1.0.0
	 * @param array $settings_data An array of settings keys and value.
	 */
	public function set_setting_multiple( $settings_data ) {
		$data = $GLOBALS['wholesalex_settings'];
		foreach ( $settings_data as $key => $val ) {
			if ( '' !== $key ) {
				$data[ $key ] = $val;
			}
		}
		$data = self::normalize_store_mode( $data );
		update_option( 'wholesalex_settings', $data );
		$GLOBALS['wholesalex_settings'] = $data;
	}

	/**
	 * Get Current User Role
	 *
	 * @return mixed Role ID | false
	 * @since 1.0.0
	 */
	public function get_current_user_role() {
		if ( ! is_user_logged_in() ) {
			return 'wholesalex_guest';
		}

		$user_id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		return $this->get_user_role( $user_id );
	}



	/**
	 * Get Quantity Based Discount Priority
	 *
	 * @return array Quantity Based Discount Priorities
	 * @since 1.2.4 Settings Default Value Added
	 */
	public function get_quantity_based_discount_priorities() {
		$default_priorities = array( 'profile', 'single_product', 'category', 'wholesale_pricing', 'dynamic_rule' );

		$priorities = wholesalex()->get_setting( '_settings_quantity_based_discount_priority', $default_priorities );
		$priorities = is_array( $priorities ) ? array_values( array_unique( $priorities ) ) : array();
		$allowed    = $default_priorities;

		$priorities = array_values( array_intersect( $priorities, $allowed ) );

		// Preserve existing custom ordering and migrate Wholesale Pricing directly
		// before the legacy Dynamic Rule source.
		if ( ! in_array( 'wholesale_pricing', $priorities, true ) ) {
			$dynamic_rule_position = array_search( 'dynamic_rule', $priorities, true );
			$insert_at             = false === $dynamic_rule_position ? count( $priorities ) : $dynamic_rule_position;
			array_splice( $priorities, $insert_at, 0, array( 'wholesale_pricing' ) );
		}

		foreach ( $allowed as $source ) {
			if ( ! in_array( $source, $priorities, true ) ) {
				$priorities[] = $source;
			}
		}

		return $priorities;
	}

	/**
	 * WholesaleX Sanitizer
	 *
	 * @param array $data .
	 * @since 1.0.0
	 * @return array $data Sanitized Array
	 */
	public function sanitize( $data ) {
		if ( ! is_array( $data ) ) {
			return is_scalar( $data ) ? sanitize_text_field( $data ) : '';
		}
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$data[ $key ] = $this->sanitize( $value );
			} else {
				$data[ $key ] = sanitize_text_field( $value );
			}
		}
		return $data;
	}

	/**
	 * Get the admin page slug for the screen being rendered.
	 *
	 * WordPress builds plugin screen ids as "{parent}_page_{slug}" or
	 * "toplevel_page_{slug}", so the current page is read from the screen
	 * rather than from an unauthenticated request value.
	 *
	 * @return string Page slug, or an empty string outside a plugin page.
	 */
	public function get_current_admin_page_slug() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return '';
		}

		$screen = get_current_screen();

		if ( ! $screen || ! is_string( $screen->id ) ) {
			return '';
		}

		$position = strpos( $screen->id, '_page_' );

		return false === $position ? '' : sanitize_key( substr( $screen->id, $position + 6 ) );
	}

	/**
	 * Sanitize a block of CSS declarations before it is enqueued inline.
	 *
	 * Stripping markup alone is not sufficient for CSS, so this also drops
	 * comments and the constructs that let a stylesheet execute script or pull
	 * in remote resources.
	 *
	 * @param string $css CSS declarations.
	 * @return string Sanitized CSS, or an empty string when nothing is left.
	 */
	public function sanitize_inline_css( $css ) {
		if ( ! is_string( $css ) || '' === $css ) {
			return '';
		}

		$css = wp_strip_all_tags( $css );
		$css = preg_replace( '#/\*.*?\*/#s', '', $css );
		$css = preg_replace( '#(?:@import|@charset|expression\s*\(|javascript\s*:|vbscript\s*:|behaviou?r\s*:|-moz-binding)#i', '', $css );
		$css = str_replace( array( '<', '>', '\\' ), '', (string) $css );

		// Compare origins, not URL prefixes; keep quotes around paths containing spaces.
		$site = wp_parse_url( site_url() );
		$css  = preg_replace_callback(
			'#url\(\s*([\'"]?)(.*?)\1\s*\)#is',
			function ( $matches ) use ( $site ) {
				// Browsers remove URL controls that PHP may interpret as path characters.
				if ( preg_match( '/[\x00-\x1F\x7F]/', $matches[2] ) ) {
					return 'none';
				}
				$value           = trim( $matches[2] );
				$url             = wp_parse_url( $value );
				$is_relative     = is_array( $url ) && ! isset( $url['scheme'] ) && ! isset( $url['host'] );
				$is_inline_image = preg_match( '#^data:image/(?:png|gif|jpe?g|webp|avif);base64,[a-z0-9+/=]+$#i', $value );
				$is_same_origin  = is_array( $site ) && is_array( $url )
					&& isset( $site['scheme'], $site['host'], $url['scheme'], $url['host'] )
					&& strtolower( $site['scheme'] ) === strtolower( $url['scheme'] )
					&& strtolower( $site['host'] ) === strtolower( $url['host'] )
					&& ( $site['port'] ?? ( 'https' === strtolower( $site['scheme'] ) ? 443 : 80 ) ) === ( $url['port'] ?? ( 'https' === strtolower( $url['scheme'] ) ? 443 : 80 ) )
					&& ! isset( $url['user'] ) && ! isset( $url['pass'] );

				if ( '' === $value || $is_relative || $is_inline_image || $is_same_origin ) {
					return 'url(' . $matches[1] . $value . $matches[1] . ')';
				}

				return 'none';
			},
			(string) $css
		);

		return trim( (string) $css );
	}

	/**
	 * Decode and recursively sanitize a JSON request payload.
	 *
	 * Sanitizing the raw JSON string before decoding is unsafe: strip_tags()
	 * removes everything between a "<" and a ">" that appear in different
	 * values, which silently drops keys, and percent-encoded sequences such as
	 * %20 are stripped from URLs. The payload is therefore decoded first and
	 * every decoded value is sanitized afterwards.
	 *
	 * @param string $raw_json Raw JSON string taken from the request.
	 * @return array|null Sanitized array, or null when the payload is not valid JSON.
	 */
	public function sanitize_json_input( $raw_json ) {
		if ( ! is_string( $raw_json ) || '' === $raw_json ) {
			return null;
		}

		$decoded = json_decode( $raw_json, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return null;
		}

		return map_deep( $decoded, 'sanitize_text_field' );
	}

	/**
	 * Get WholesaleX Role Name by role Id
	 *
	 * @param string $role_id WholesaleX Role ID.
	 * @return string Role Name.
	 */
	public function get_role_name_by_role_id( $role_id = '' ) {
		$__role_content = wholesalex()->get_roles( 'by_id', $role_id );
		$__title        = isset( $__role_content['_role_title'] ) ? $__role_content['_role_title'] : '';
		return $__title;
	}

	/**
	 * Insert Into Array at specific position
	 *
	 * @param array $new_array Initial Array.
	 * @param array $insert new element of array with key.
	 * @param int   $position The Position where new elements are inserted.
	 * @return array Updated Array.
	 */
	public function insert_into_array( $new_array, $insert, $position = '' ) {
		if ( empty( $position ) || '' === $position ) {
			$position = count( $new_array );
		}
		return array_slice( $new_array, 0, $position, true ) + $insert + array_slice( $new_array, $position, null, true );
	}

	/**
	 * Get Hidden Product Ids For Current User
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Bug Fixed.
	 * @since 1.2.4 Specific User Product Visibility Control Will Work Non WholesaleX Users
	 */
	public function hidden_product_ids() {
		$__role = wholesalex()->get_current_user_role();

		$__single_products = wholesalex()->get_single_product_setting();

		$__product_ids_hidden_for_current_user = array();
		$__product_ids_hidden_for_guest        = array();
		$__product_ids_hidden_for_b2c          = array();
		$__product_ids_hidden_for_b2b          = array();

		foreach ( $__single_products as $id => $data ) {

			if ( isset( $data['_hide_for_visitors'] ) && 'yes' === $data['_hide_for_visitors'] ) {
				array_push( $__product_ids_hidden_for_guest, $id );
			}

			if ( isset( $data['_hide_for_b2c'] ) && 'yes' === $data['_hide_for_b2c'] ) {
				array_push( $__product_ids_hidden_for_b2c, $id );
			}
			if ( is_user_logged_in() && isset( $data['_hide_for_b2b_role_n_user'] ) ) {
				$__user_id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );

				if ( 'user_specific' === $data['_hide_for_b2b_role_n_user'] ) {
					if ( isset( $data['_hide_for_users'] ) && is_array( $data['_hide_for_users'] ) ) {
						$__hide_for_users = $data['_hide_for_users'];
						foreach ( $__hide_for_users as $users ) {
							if ( isset( $users['value'] ) && 'user_' . $__user_id === $users['value'] ) {
								array_push( $__product_ids_hidden_for_current_user, $id );
								break;
							}
						}
					}
				}

				if ( 'b2b_specific' === $data['_hide_for_b2b_role_n_user'] ) {
					if ( isset( $data['_hide_for_roles'] ) && is_array( $data['_hide_for_roles'] ) ) {
						$__hide_for_roles = $data['_hide_for_roles'];
						foreach ( $__hide_for_roles as $roles ) {
							if ( isset( $roles['value'] ) && $roles['value'] === $__role ) {
								array_push( $__product_ids_hidden_for_b2b, $id );
								break;
							}
						}
					}
				}

				if ( 'b2b_all' === $data['_hide_for_b2b_role_n_user'] ) {
					array_push( $__product_ids_hidden_for_b2b, $id );
				}
			}
		}

		switch ( $__role ) {
			case 'wholesalex_guest':
				return $__product_ids_hidden_for_guest;
			case 'wholesalex_b2c_users':
				return array_unique( array_merge( $__product_ids_hidden_for_current_user, $__product_ids_hidden_for_b2c ) );
			case '':
				return array();
			default:
				return array_unique( array_merge( $__product_ids_hidden_for_current_user, $__product_ids_hidden_for_b2b ) );
		}

		return array();
	}

	/**
	 * Get WholesaleX Hidden Product and Categories ID
	 *
	 * @param string $type Type of Hidden Items. product | category.
	 * @return array Hidden Items id.
	 * @since 1.0.0
	 * @since 1.2.4 Specific User Product Visibility Control Will Work Non WholesaleX Users
	 */
	public function hidden_ids( $type = 'product' ) {
		$__role = wholesalex()->get_current_user_role();

		$__visibility_settings = array();
		switch ( $type ) {
			case 'product':
				$__visibility_settings = wholesalex()->get_single_product_setting();
				break;
			case 'category':
				$__visibility_settings = wholesalex()->get_category_visibility_settings();
				break;
		}

		$__ids_hidden_for_current_user = array();
		$__ids_hidden_for_guest        = array();
		$__ids_hidden_for_b2c          = array();
		$__ids_hidden_for_b2b          = array();

		if ( is_array( $__visibility_settings ) ) {

			foreach ( $__visibility_settings as $id => $data ) {

				if ( isset( $data['_hide_for_visitors'] ) && 'yes' === $data['_hide_for_visitors'] ) {
					array_push( $__ids_hidden_for_guest, $id );
				}

				if ( isset( $data['_hide_for_b2c'] ) && 'yes' === $data['_hide_for_b2c'] ) {
					array_push( $__ids_hidden_for_b2c, $id );
				}
				if ( is_user_logged_in() && isset( $data['_hide_for_b2b_role_n_user'] ) ) {
					$__user_id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );

					if ( 'user_specific' === $data['_hide_for_b2b_role_n_user'] ) {
						if ( isset( $data['_hide_for_users'] ) && is_array( $data['_hide_for_users'] ) ) {
							$__hide_for_users = $data['_hide_for_users'];
							foreach ( $__hide_for_users as $users ) {
								if ( isset( $users['value'] ) && 'user_' . $__user_id === $users['value'] ) {
									array_push( $__ids_hidden_for_current_user, $id );
									break;
								}
							}
						}
					}

					if ( 'b2b_specific' === $data['_hide_for_b2b_role_n_user'] ) {
						if ( isset( $data['_hide_for_roles'] ) && is_array( $data['_hide_for_roles'] ) ) {
							$__hide_for_roles = $data['_hide_for_roles'];
							foreach ( $__hide_for_roles as $roles ) {
								if ( isset( $roles['value'] ) && $roles['value'] === $__role ) {
									array_push( $__ids_hidden_for_b2b, $id );
									break;
								}
							}
						}
					}

					if ( 'b2b_all' === $data['_hide_for_b2b_role_n_user'] ) {
						array_push( $__ids_hidden_for_b2b, $id );
					}
				}
			}
		}

		switch ( $__role ) {
			case 'wholesalex_guest':
				return $__ids_hidden_for_guest;
			case 'wholesalex_b2c_users':
				return array_unique( array_merge( $__ids_hidden_for_current_user, $__ids_hidden_for_b2c ) );
			case '':
				return array();
			default:
				return array_unique( array_merge( $__ids_hidden_for_current_user, $__ids_hidden_for_b2b ) );
		}

		return array();
	}

	/**
	 * Filter Empty Tiers
	 *
	 * @param array $tiers Discounts Tier.
	 * @return array Updated Tiers.
	 */
	public function filter_empty_conditions( $tiers ) {
		if ( null === $tiers || '' === $tiers ) {
			return array(); }
		if ( ! is_array( $tiers ) ) {
			return array( array( '_invalid' => true ) ); }
		$result = array();
		foreach ( $tiers as $tier ) {
			if ( ! is_array( $tier ) ) {
				$result[] = array( '_invalid' => true );
				continue; }
			$field    = $tier['_conditions_for'] ?? $tier['field'] ?? '';
			$operator = $tier['_conditions_operator'] ?? $tier['operator'] ?? '';
			$value    = $tier['_conditions_value'] ?? $tier['value'] ?? '';
			if ( '' === $field && '' === $operator && '' === $value ) {
				continue; }
			$fields    = array(
				'item_count' => 'cart_total_qty',
				'cart_total' => 'cart_total_value',
			);
			$operators = array(
				'<' => 'less',
				'>' => 'greater',
				'=' => 'equal',
			);
			if ( ! is_string( $field ) || ! is_string( $operator ) ) {
				$result[] = array( '_invalid' => true );
				continue; }
			$tier['_conditions_for']      = $fields[ $field ] ?? $field;
			$tier['_conditions_operator'] = $operators[ $operator ] ?? $operator;
			$tier['_conditions_value']    = $value;
			$result[]                     = $tier;
		}
		return $result;
	}
	/**
	 * Get Discounts For Current User
	 *
	 * @param string $type Type.
	 * @param mixed  $product_id Product ID.
	 * @since 1.2.4 Active status check if plugin status is only b2b mode
	 */
	public function get_discounts( $type = '', $product_id = -1 ) {

		if ( '' !== $type && -1 !== $product_id ) {

			$__role            = wholesalex()->get_current_user_role();
			$__current_user_id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
			$__discounts       = array();

			$__eligible_for_discounts = true;
			$plugins_status           = wholesalex()->get_setting( '_settings_status', 'b2b' );

			if ( 'b2b' === $plugins_status ) {
				if ( ! ( ( $__current_user_id && 'active' === wholesalex()->get_user_status( $__current_user_id ) ) ) ) {
					$__eligible_for_discounts = false;
				}
			}

			if ( 'wholesalex_guest' === wholesalex()->get_current_user_role() ) {
				$__eligible_for_discounts = true;
			}

			switch ( $type ) {
				case 'category':
					return apply_filters( 'wholesalex_resolved_page_discounts', array(), $type, $product_id, $__eligible_for_discounts, $__role );
				case 'product':
					$__discounts = wholesalex()->get_single_product_discount( $product_id );
					if ( ! isset( $__discounts[ $__role ] ) ) {
						return;
					}
					$__discounts = $__discounts[ $__role ];

					$__regular_price = isset( $__discounts['wholesalex_base_price'] ) ? $__discounts['wholesalex_base_price'] : '';

					$__sale_price = isset( $__discounts['wholesalex_sale_price'] ) ? $__discounts['wholesalex_sale_price'] : '';

					return apply_filters(
						'wholesalex_resolved_page_discounts',
						array(
							'regular_price' => $__eligible_for_discounts ? $__regular_price : '',
							'sale_price'    => $__eligible_for_discounts ? $__sale_price : '',
						),
						$type,
						$product_id,
						$__eligible_for_discounts,
						$__role
					);

				default:
					// code...
					break;
			}
		}
	}

	/**
	 * Category Cart Count. It count how many product are in cart of given cat id.
	 *
	 * @param mixed $cat_id Category ID.
	 * @return int Product Count.
	 * @since 1.0.0
	 */
	public function category_cart_count( $cat_id ) {
		static $cache_category_id = array();

		// Return cached result if available.
		if ( isset( $cache_category_id[ $cat_id ] ) ) {
			return $cache_category_id[ $cat_id ];
		}

		$cat_count = 0;

		if ( isset( WC()->cart ) && ! WC()->cart->is_empty() ) {
			// Cache product objects by ID to avoid repeated wc_get_product calls.
			static $product_cache = array();

			// Cache has_term results to avoid duplicate term queries.
			static $has_term_cache = array();

			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$__product_id = $cart_item['product_id'];

				if ( ! isset( $product_cache[ $__product_id ] ) ) {
					$product_cache[ $__product_id ] = wc_get_product( $__product_id );
				}

				$__product = $product_cache[ $__product_id ];

				if ( ! $__product ) {
					continue;
				}

				// Handle product variation.
				if ( 'product_variation' === $__product->get_type() ) {
					$__product_id = $__product->get_parent_id();
				}

				$cache_key = $__product_id . '-' . $cat_id;

				if ( ! isset( $has_term_cache[ $cache_key ] ) ) {
					$has_term_cache[ $cache_key ] = has_term( $cat_id, 'product_cat', $__product_id );
				}

				if ( $has_term_cache[ $cache_key ] ) {
					$cat_count += $cart_item['quantity'];
				}
			}
		}

		$cat_count = apply_filters( 'wholesalex_category_cart_count', $cat_count, $cat_id );

		// Save to static cache or runtime cache before returning.
		$cache_category_id[ $cat_id ] = $cat_count;

		return $cat_count;
	}

	/**
	 * Get Product Count at cart
	 *
	 * @param int $product_id Product or Variation ID.
	 * @return int Product count at cart.
	 * @since 1.0.0
	 */
	public function cart_count( $product_id = false ) {
		$__quantity = 0;
		if ( ! is_null( WC()->cart ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( $product_id ) {
					if ( ! empty( $cart_item['data'] ) && in_array( $product_id, array( $cart_item['product_id'], $cart_item['variation_id'] ), true ) ) {
						$__is_parent_rule_apply = apply_filters( 'wholesalex_apply_parent_rule_to_variations', false );  // Add This Filter TO Work Dynamic Rule For Combine Variation Product Like Quantity Base Discount.
						if ( $__is_parent_rule_apply ) {
							$__quantity += $cart_item['quantity'];
						} else {
							$__quantity = $cart_item['quantity'];
							break; // stop the loop if product is found.
						}
					}
				} elseif ( ! isset( $cart_item['free_product'] ) ) {
						$__quantity += $cart_item['quantity'];
				}
			}
		}

		$__quantity = apply_filters( 'wholesalex_cart_count', $__quantity, $product_id );

		return $__quantity;
	}

	/**
	 * Get Cart Total Amount
	 *
	 * @param string $product_id Product ID.
	 * @return int If product id specified then return cart subtotal price of specific product otherwise return total cart content price.
	 * @since 1.0.0
	 */
	public function get_cart_total( $product_id = '' ) {
		if ( ! isset( WC()->cart ) || null === WC()->cart->get_cart() ) {
			return 0;
		}

		if ( ! empty( $product_id ) && isset( WC()->cart ) ) {

			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( isset( $cart_item['product_id'] ) && isset( $cart_item['variation_id'] ) && isset( $cart_item['line_total'] ) && in_array( $product_id, array( $cart_item['product_id'], $cart_item['variation_id'] ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Product IDs may arrive as numeric strings from callers.
					return $cart_item['line_total'];
				}
			}
		} else {
			$__is_enable_tax = ( 'yes' === wholesalex()->get_setting( '_settings_allow_tax_with_cart_total_amount' ) );
			$__total         = 0.0;
			$__with_tax      = apply_filters( 'wholesalex_get_cart_total_with_tax', ( $__is_enable_tax ? true : false ) );
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( isset( $cart_item['line_total'] ) ) {
					if ( $__with_tax && isset( $cart_item['line_tax'] ) ) {
						$__total = $__total + $cart_item['line_total'] + $cart_item['line_tax'];
					} else {
						$__total = $__total + $cart_item['line_total'];
					}
				}
			}
			return (float) $__total;
		}

		return 0;
	}

	/**
	 * Get Cart Total Weight
	 *
	 * @return float Total Weight of cart.
	 * @since 1.0.0
	 */
	public function get_cart_total_weight() {
		$__total = 0.0;
		if ( ! isset( WC()->cart ) ) {
			return $__total;
		}
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$__item_weight = $cart_item['data']->get_weight();
			if ( ! empty( $__item_weight ) ) {
				$__total = $__total + ( $__item_weight * $cart_item['quantity'] );
			}
		}
		return (float) $__total;
	}


	/**
	 * Get WholesaleX User Status
	 *
	 * @param string $id User ID.
	 * @return string user status.
	 */
	public function get_user_status( $id = '' ) {
		if ( empty( $id ) ) {
			$id = apply_filters( 'wholesalex_set_current_user', get_current_user_id() );
		}

		$__user_status = get_user_meta( $id, '__wholesalex_status', true );
		$__user_status = ( $__user_status && ! empty( $__user_status ) ) ? $__user_status : '';
		return $__user_status;
	}

	/**
	 * Get Language and Text Settings
	 *
	 * @param string $from_setting Settings Text.
	 * @param string $default Default Text.
	 * @return string
	 * @since 1.0.0
	 * @since 1.0.2 Updated and Backward Compatibility Added.
	 */
	public function get_language_n_text( $from_setting, $default ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- Retain the public parameter name for named-argument compatibility.

		if ( version_compare( WHOLESALEX_VER, '1.0.2', '>=' ) > 0 ) {
			if ( isset( $GLOBALS['wholesalex_settings'][ $from_setting ] ) ) {
				return $this->get_setting( $from_setting );
			} else {
				return $default;
			}
		} elseif ( ! empty( $from_setting ) ) {
				return $from_setting;
		} else {
			return $default;
		}
	}

	/**
	 * Calculate Per Unit Sale Price
	 *
	 * @param array        $tier Tier Array.
	 * @param string|float $regular_price Regular Price.
	 * @since 1.0.0
	 * @since 1.0.3 Fixed Price Issue Fixed
	 */
	public function calculate_sale_price( $tier, $regular_price ) {
		if ( ! ( isset( $tier['_discount_type'] ) && isset( $tier['_discount_amount'] ) && ! empty( $regular_price ) ) ) {
			return;
		}
		$__sale_price = '';
		switch ( $tier['_discount_type'] ) {
			case 'percentage':
				$__sale_price = max( 0, (float) $regular_price - ( ( (float) $regular_price * floatval( $tier['_discount_amount'] ) ) / 100.00 ) );
				break;
			case 'amount':
				$__sale_price = max( 0, (float) $regular_price - floatval( $tier['_discount_amount'] ) );
				break;
			case 'fixed_price':
				$__sale_price = max( 0, (float) floatval( $tier['_discount_amount'] ) );
				break;
			case 'fixed':
				$__sale_price = max( 0, (float) floatval( $tier['_discount_amount'] ) );
				break;
		}
		return number_format( (float) $__sale_price, 2, '.', '' );
	}

	/**
	 * Dynamic Rules Usages Count Handle
	 *
	 * @param int $rule_id Dynamic Rule ID.
	 * @return void
	 */
	public function set_usages_dynamic_rule_id( $rule_id ) {
		if ( is_admin() || null === WC()->session ) {
			return;
		}
		$__dynamic_rule_id = WC()->session->get( '__wholesalex_used_dynamic_rule' );
		if ( ! ( isset( $__dynamic_rule_id ) && is_array( $__dynamic_rule_id ) ) ) {
			$__dynamic_rule_id = array();
		}
		$__dynamic_rule_id[ $rule_id ] = true;

		WC()->session->set( '__wholesalex_used_dynamic_rule', $__dynamic_rule_id );
	}


	/**
	 * Get Single Product Default Settings
	 *
	 * @param string $key Setting name.
	 * @return string
	 */
	public function get_single_product_default_settings( $key = '' ) {
		if ( '' !== $key ) {
			switch ( $key ) {
				case '_settings_override_tax_extemption':
					return 'disable';
				case '_settings_override_shipping_role':
					return 'disable';
				case '_settings_override_tax_extemption':
					return 'disable';
				default:
					return '';
			}
		}
	}

	/**
	 * Get All Product Ids
	 *
	 * @since 1.0.2
	 */
	public function get_all_product_ids() {
		$ids = new WP_Query(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'fields'      => 'ids',
			)
		);

		return $ids;
	}

	/**
	 * Check Any String Start With
	 *
	 * @param string $str Main String.
	 * @param string $begin_with Begin With String.
	 * @return bool
	 * @since 1.0.10
	 */
	public function start_with( $str, $begin_with ) {
		$len = strlen( $begin_with );
		return ( substr( $str, 0, $len ) === $begin_with );
	}


	/**
	 * Get WholesaleX Rolewise Sale and Base Price
	 *
	 * @param string|int $user_id User Id.
	 * @param string|int $product_id Product ID.
	 * @return array
	 */
	public function get_wholesalex_rolewise_single_product_price( $user_id = '', $product_id = '' ) {
		if ( ! ( $user_id && $product_id ) ) {
			return false;
		}
		$user_role_id = apply_filters( 'wholesalex_user_role', get_user_meta( $user_id, '__wholesalex_role', true ), (int) $user_id );
		$product      = wc_get_product( $product_id );
		if ( ! $product ) {
			return false;
		}
		$sale_price = $product->get_sale_price();
		$base_price = $product->get_regular_price();
		if ( $user_role_id && function_exists( 'wholesalex' ) ) {
			// All Roles Price.
			$prices = wholesalex()->get_single_product_discount( $product_id );

			// Price For Current User Role.
			$price = isset( $prices[ $user_role_id ] ) ? $prices[ $user_role_id ] : array();

			$sale_price = isset( $price['wholesalex_sale_price'] ) ? $price['wholesalex_sale_price'] : $sale_price;
			$base_price = isset( $price['wholesalex_base_price'] ) ? $price['wholesalex_base_price'] : $base_price;
		}

		return array(
			'base_price' => $base_price,
			'sale_price' => $sale_price,
		);
	}


	/**
	 * Check is wholesalex page
	 *
	 * @param string $page Page Name.
	 * @return boolean
	 * @since 1.1.7
	 */
	public function is_wholesalex_page( $page ) {
		$status = false;
		switch ( $page ) {
			case 'wholesalex-settings':
			case 'wholesalex-users':
			case 'wholesalex-pro-features':
			case 'wholesalex_role':
			case 'wholesalex-email':
			case 'wholesalex_wholesale_pricing':
			case 'wholesalex-wholesale-pricing':
			case 'wholesalex_dynamic_rules':
			case 'wholesalex-registration':
			case 'wholesalex':
			case 'wholesalex-analytics':
			case 'wholesalex-help':
			case 'wholesalex-our-products':
			case 'wholesalex-user-role-requests':
			case 'wholesalex-support':
			case 'wholesalex-migration':
				$status = true;
				break;

			default:
				break;
		}
		if ( wholesalex()->get_setting( 'plugin_menu_slug' ) === $page || wholesalex()->get_setting( 'dynamic_rule_submenu_slug' ) === $page || wholesalex()->get_setting( 'emails_submenu_slug' ) === $page ||
			wholesalex()->get_setting( 'registration_form_buidler_submenu_slug' ) === $page || wholesalex()->get_setting( 'role_submenu_slug' ) === $page || wholesalex()->get_setting( 'settings_submenu_slug' ) === $page || wholesalex()->get_setting( 'users_submenu_slug' ) === $page
		) {
			$status = true;
		}

		if ( ! $page ) {
			$status = false;
		}

		return (bool) apply_filters( 'wholesalex_is_plugin_page', $status, $page );
	}

	/**
	 * Get Email Templates
	 *
	 * @param string $email_subject Email Subject.
	 * @param string $email_content Email Content.
	 * @param string $css Email CSS.
	 * @return array
	 * @since 1.1.7
	 */
	public function get_email_template( $email_subject, $email_content, $css ) {
		ob_start();
		wc_get_template(
			'email_template.php',
			array(
				'subject'       => $email_subject,
				'css'           => $css,
				'email_content' => $email_content,
			),
			WHOLESALEX_PATH . 'includes/',
			WHOLESALEX_PATH . 'includes/'
		);
		return ob_get_clean();
	}


	/**
	 * Send Email
	 *
	 * @param string $template Email Template.
	 * @param string $to Email To.
	 * @param array  $smart_tags Smart Tags.
	 * @return array
	 * @since 1.1.7
	 */
	public function send_email( $template, $to, $smart_tags ) {

		$email_templates = WHOLESALEX_EMAIL::get_email_templates();
		$email_template  = $email_templates[ $template ];

		$subject = $email_template['subject'];
		$content = $email_template['content'];
		foreach ( $smart_tags as $key => $value ) {
			$subject = str_replace( $key, $value, $subject );
			$content = str_replace( $key, $value, $content );
		}

		$css     = '';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $to, $subject, wholesalex()->get_email_template( $subject, $content, $css ), $headers );

		if ( isset( $email_template['admin_notification'] ) && $email_template['admin_notification'] ) {
			wp_mail( get_option( 'admin_email' ), $subject, wholesalex()->get_email_template( $subject, $content, $css ), $headers );
		}

		return true;
	}

	/**
	 * Unlock Options that are locked for different pricing plans.
	 *
	 * @param array $options Locked Options.
	 * @return array Unlocked Options
	 * @since 1.0.6
	 */
	public function unlock_options( $options ) {
		return apply_filters( 'wholesalex_extension_options', is_array( $options ) ? $options : array() );
	}

	/**
	 * Unlock Tier Layouts that are locked for different pricing plans.
	 *
	 * @param array $options Locked Options.
	 * @return array Unlocked Options
	 * @since 1.0.6
	 */
	public function unlock_layouts( $options ) {
		return apply_filters( 'wholesalex_extension_layouts', is_array( $options ) ? $options : array() );
	}

	/**
	 * Helper function for logging
	 *
	 * For valid levels, see `WC_Log_Levels` class
	 *
	 * Description of levels:
	 *     'emergency': System is unusable.
	 *     'alert': Action must be taken immediately.
	 *     'critical': Critical conditions.
	 *     'error': Error conditions.
	 *     'warning': Warning conditions.
	 *     'notice': Normal but significant condition.
	 *     'info': Informational messages.
	 *     'debug': Debug-level messages.
	 *
	 * @param string $message Message to log.
	 * @param string $level Log level.
	 *
	 * @return void
	 */
	public function log( $message, $level = 'debug' ) {
		$logger  = wc_get_logger();
		$context = array( 'source' => 'wholesalex' );

		$logger->log( $level, $message, $context );
	}


	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		$plugin_name = apply_filters( 'wholesalex_plugin_name', __( 'WholesaleX', 'wholesalex' ) );
		return $plugin_name;
	}

	/**
	 * Whether plugin promotions may be displayed to the current user.
	 *
	 * @return bool
	 */
	public function can_show_promotions() {
		// Providers may suppress promotions; installation permission is always required.
		return current_user_can( 'install_plugins' ) && (bool) apply_filters( 'wholesalex_can_show_promotions', true );
	}

	/** Get the filtered admin menu slug. */
	public function get_menu_slug() {
		$menu_slug = apply_filters( 'wholesalex_plugin_menu_slug', 'wholesalex' );
		return $menu_slug;
	}

	/**
	 * Run an option operation against fresh database values.
	 *
	 * WordPress may populate its caches during the operation. Clear them again
	 * afterwards, including when a filter throws. Leave the cache backend and
	 * independent external-cache transients unchanged.
	 *
	 * @param string[] $options Option names involved in the operation.
	 * @param callable $callback WordPress API operation.
	 * @return mixed
	 */
	private function without_option_cache( $options, $callback ) {
		$clear = static function () use ( $options ) {
			foreach ( $options as $option ) {
				wp_cache_delete( $option, 'options' );
			}
			wp_cache_delete( 'alloptions', 'options' );
			wp_cache_delete( 'notoptions', 'options' );
		};

		$clear();

		try {
			return $callback();
		} finally {
			$clear();
		}
	}

	/**
	 * Get Option Value bypassing cache
	 * Inspired By WordPress Core get_option
	 *
	 * @param string  $option Option Name.
	 * @param boolean $default_value option default value.
	 * @return mixed
	 */
	public function get_option_without_cache( $option, $default_value = false ) {
		if ( is_scalar( $option ) ) {
			$option = trim( (string) $option );
		}

		if ( ! is_string( $option ) || '' === $option ) {
			return false;
		}

		return $this->without_option_cache(
			array( $option ),
			function () use ( $option, $default_value ) {
				$found        = true;
				$mark_missing = static function ( $value ) use ( &$found ) {
					$found = false;
					return $value;
				};

				add_filter( "default_option_{$option}", $mark_missing, PHP_INT_MAX );

				try {
					$value = get_option( $option, $default_value );
				} finally {
					remove_filter( "default_option_{$option}", $mark_missing, PHP_INT_MAX );
				}

				if ( ! $found ) {
					return apply_filters( "wholesalex_default_option_{$option}", $value, $option );
				}

				return apply_filters( "wholesalex_option_{$option}", $value, $option );
			}
		);
	}

	/**
	 * Get Plugin URL
	 *
	 * @return string
	 */
	public function badge_image_display() {
		$badge_image = apply_filters(
			'wholesalex_settings_product_tier_layouts',
			array(
				'style_one'   => WHOLESALEX_URL . '/assets/icons/badge-style-one.svg',
				'style_two'   => WHOLESALEX_URL . '/assets/icons/badge-style-two.svg',
				'style_three' => WHOLESALEX_URL . '/assets/icons/badge-style-three.svg',
				'style_four'  => WHOLESALEX_URL . '/assets/icons/badge-style-four.svg',
				'style_five'  => WHOLESALEX_URL . '/assets/icons/badge-style-five.svg',
			)
		);
		return $badge_image;
	}


	/**
	 * Get Transient Value bypassing cache
	 * Inspired By WordPress Core get_transient
	 *
	 * @param string $transient Transient Name.
	 * @return mixed
	 */
	public function get_transient_without_cache( $transient ) {
		return $this->without_option_cache(
			array( '_transient_' . $transient, '_transient_timeout_' . $transient ),
			function () use ( $transient ) {
				$transient_option  = '_transient_' . $transient;
				$transient_timeout = '_transient_timeout_' . $transient;
				$timeout           = $this->get_option_without_cache( $transient_timeout );

				if ( false !== $timeout && $timeout < time() ) {
					delete_option( $transient_option );
					delete_option( $transient_timeout );
					$value = false;
				}

				if ( ! isset( $value ) ) {
					$value = $this->get_option_without_cache( $transient_option );
				}

				return apply_filters( "wholesalex_transient_{$transient}", $value, $transient );
			}
		);
	}

	/**
	 * Set transient without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @param string  $transient Transient Name.
	 * @param mixed   $value Transient Value.
	 * @param integer $expiration Time until expiration in seconds.
	 * @return bool
	 */
	public function set_transient_without_cache( $transient, $value, $expiration = 0 ) {
		return $this->without_option_cache(
			array( '_transient_' . $transient, '_transient_timeout_' . $transient ),
			function () use ( $transient, $value, $expiration ) {
				$expiration = (int) $expiration;

				$transient_timeout = '_transient_timeout_' . $transient;
				$transient_option  = '_transient_' . $transient;

				$result = false;

				if ( false === $this->get_option_without_cache( $transient_option ) ) {
					$autoload = 'yes';
					if ( $expiration ) {
						$autoload = 'no';
						$this->add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
					}
					$result = $this->add_option_without_cache( $transient_option, $value, $autoload );
				} else {
					/*
					 * If expiration is requested, but the transient has no timeout option,
					 * delete, then re-create transient rather than update.
					 */
					$update = true;

					if ( $expiration ) {
						if ( false === $this->get_option_without_cache( $transient_timeout ) ) {
							delete_option( $transient_option );
							$this->add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
							$result = $this->add_option_without_cache( $transient_option, $value, 'no' );
							$update = false;
						} else {
							update_option( $transient_timeout, time() + $expiration );
						}
					}

					if ( $update ) {
						$result = update_option( $transient_option, $value );
					}
				}

				return $result;
			}
		);
	}

	/**
	 * Add option without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @param string $option option name.
	 * @param string $value option value.
	 * @param string $autoload whether to load WordPress startup.
	 * @return bool
	 */
	public function add_option_without_cache( $option, $value = '', $autoload = 'yes' ) {
		if ( is_scalar( $option ) ) {
			$option = trim( (string) $option );
		}

		if ( ! is_string( $option ) || '' === $option ) {
			return false;
		}

		return $this->without_option_cache(
			array( $option ),
			function () use ( $option, $value, $autoload ) {
				/*
				 * Make sure the option doesn't already exist.
				 */
				if ( apply_filters( "wholesalex_default_option_{$option}", false, $option, false ) !== $this->get_option_without_cache( $option ) ) {
					return false;
				}

				return add_option( $option, $value, '', $autoload );
			}
		);
	}

	/**
	 * Check current user is a b2b user or has admin access
	 *
	 * @param int $user_id User ID.
	 * @return boolean
	 * @since 1.1.3
	 */
	public function is_active_b2b_user( $user_id = '' ) {
		$user_id = '' === $user_id ? get_current_user_id() : (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}
		$account_role = get_user_meta( $user_id, '__wholesalex_role', true );
		return is_string( $account_role ) && '' !== $account_role && ! in_array( $account_role, array( 'wholesalex_guest', 'wholesalex_b2c_users' ), true ) && 'active' === get_user_meta( $user_id, '__wholesalex_status', true );
	}


	/**
	 * Update Registration Form
	 *
	 * @param array $data Data.
	 * @return void
	 */
	public function update_registration_form( $data ) {
		update_option( '__wholesalex_registration_form', wp_json_encode( $data ) );
	}

	/**
	 * Update Dynamic Rules
	 *
	 * @param array $data Dynamic Rules.
	 * @return void
	 */
	public function update_dynamic_rules( $data ) {
		update_option( '__wholesalex_dynamic_rules', $data );
	}


	/**
	 * Set Productwise Rule Data.
	 *
	 * @param int|string $rule_id Rule ID.
	 * @param int|string $product_id Product ID.
	 * @param string     $type Rule Type.
	 * @param array      $data Rule Data.
	 * @return void
	 */
	public function set_rule_data( $rule_id, $product_id, $type, $data ) {
		if ( ! isset( $GLOBALS['wholesalex_rule_data'][ $product_id ] ) ) {
			$GLOBALS['wholesalex_rule_data'][ $product_id ] = array();
		}
		if ( ! isset( $GLOBALS['wholesalex_rule_data'][ $product_id ][ $type ] ) ) {
			$GLOBALS['wholesalex_rule_data'][ $product_id ][ $type ][ $rule_id ] = array();
		}
		$GLOBALS['wholesalex_rule_data'][ $product_id ][ $type ][ $rule_id ] = $data;
	}

	/**
	 * Get Productwise Rule Data
	 *
	 * @param int|string $product_id Product ID.
	 * @param string     $type Product Type.
	 * @param string     $rule_id Rule ID.
	 * @return array
	 */
	public function get_rule_data( $product_id, $type = '', $rule_id = '' ) {
		if ( $type && $rule_id ) {
			if ( isset( $GLOBALS['wholesalex_rule_data'][ $product_id ][ $type ][ $rule_id ] ) ) {
				return $GLOBALS['wholesalex_rule_data'][ $product_id ][ $type ][ $rule_id ];
			} else {
				return array();
			}
		}
		if ( $type ) {
			if ( isset( $GLOBALS['wholesalex_rule_data'][ $product_id ][ $type ] ) ) {
				return $GLOBALS['wholesalex_rule_data'][ $product_id ][ $type ];
			} else {
				return array();
			}
		}
		if ( isset( $GLOBALS['wholesalex_rule_data'][ $product_id ] ) ) {
			return $GLOBALS['wholesalex_rule_data'][ $product_id ];
		}
		return array();
	}

	/**
	 * Set Productwise Rule Data.
	 *
	 * @param mixed $product_id Product ID.
	 * @return array
	 */
	public function get_wholesalex_wholesale_prices( $product_id ) {
		if ( ! isset( $GLOBALS['wholesalex_wholesale_prices'][ $product_id ] ) ) {
			return false;
		}
		return $GLOBALS['wholesalex_wholesale_prices'][ $product_id ];
	}

	/**
	 * Set Productwise Rule Data.
	 *
	 * @param mixed $product_id Product ID.
	 * @param mixed $price Price.
	 * @return void
	 */
	public function set_wholesalex_wholesale_prices( $product_id, $price ) {
		if ( ! isset( $GLOBALS['wholesalex_wholesale_prices'] ) || ! is_array( $GLOBALS['wholesalex_wholesale_prices'] ) ) {
			$GLOBALS['wholesalex_wholesale_prices'] = array();
		}
		$GLOBALS['wholesalex_wholesale_prices'][ $product_id ] = $price;
	}

	/**
	 * Set Productwise Rule Data.
	 *
	 * @param mixed $product_id Product ID.
	 * @return array
	 */
	public function get_wholesalex_regular_prices( $product_id ) {
		if ( ! isset( $GLOBALS['wholesalex_regular_prices'][ $product_id ] ) ) {
			return false;
		}
		return $GLOBALS['wholesalex_regular_prices'][ $product_id ];
	}

	/**
	 * Set Productwise Rule Data.
	 *
	 * @param mixed $product_id Product ID.
	 * @param mixed $price Price.
	 * @return void
	 */
	public function set_wholesalex_regular_prices( $product_id, $price ) {
		if ( ! isset( $GLOBALS['wholesalex_regular_prices'] ) || ! is_array( $GLOBALS['wholesalex_regular_prices'] ) ) {
			$GLOBALS['wholesalex_regular_prices'] = array();
		}
		$GLOBALS['wholesalex_regular_prices'][ $product_id ] = $price;
	}

	/**
	 * Smart Tag Generate for Email Template
	 *
	 * @param mixed ...$args Arguments.
	 * @return mixed
	 */
	public function smart_tag_name( ...$args ) {
		$current_date = gmdate( 'F j, Y' );
		$admin_email  = get_option( 'admin_email' );
		$admin_user   = get_user_by( 'email', $admin_email );
		$admin_name   = $admin_user ? $admin_user->display_name : '';
		$site_name    = get_bloginfo( 'name' );
		$result       = array();

		foreach ( $args as $arg ) {
			switch ( $arg ) {
				case '{date}':
					$result[ $arg ] = $current_date;
					break;
				case '{site_name}':
					$result[ $arg ] = $site_name;
					break;
				case '{admin_name}':
					$result[ $arg ] = $admin_name;
					break;
			}
		}
		return $result;
	}
	/**
	 * Check if a plugin is installed and activated.
	 *
	 * @param string $plugin_path The relative path to the plugin file (e.g., 'woocommerce/woocommerce.php').
	 * @return bool True if the plugin is installed and activated, false otherwise.
	 */
	public function is_plugin_installed_and_activated( $plugin_path ) {
		if ( is_plugin_active( $plugin_path ) ) {
			return true;
		}

		return false;
	}
}
