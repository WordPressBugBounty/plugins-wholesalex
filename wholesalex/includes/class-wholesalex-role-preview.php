<?php
/**
 * Storefront role preview for store managers.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Keep the preview separate from the account's assigned role. */
class Wholesalex_Role_Preview {
	const CAPABILITY   = 'manage_woocommerce';
	const META_KEY     = '_wholesalex_preview_role';
	const QUERY_KEY    = 'wholesalex_preview_role';
	const NONCE_ACTION = 'wholesalex_preview_role';

	/** Register before the pricing engines load their request context. */
	public function __construct() {
		add_filter( 'wholesalex_user_role', array( $this, 'filter_role' ), 10, 2 );
		add_filter( 'wholesalex_pricing_user_eligible', array( $this, 'filter_eligibility' ), 10, 2 );
		add_action( 'init', array( $this, 'handle_switch' ), 1 );
		add_action( 'admin_bar_menu', array( $this, 'add_menu' ), 81 );
		add_action( 'send_headers', array( $this, 'prevent_page_cache' ) );
	}

	/**
	 * Get wholesale roles, excluding guest and retail contexts.
	 *
	 * @return array
	 */
	public function get_roles() {
		$roles = wholesalex()->get_roles();
		$roles = is_array( $roles ) ? $roles : array();
		unset( $roles['wholesalex_guest'], $roles['wholesalex_b2c_users'] );
		return $roles;
	}

	/**
	 * Get the valid selection belonging to the current authorized viewer.
	 *
	 * @return string
	 */
	public function get_selected_role() {
		if ( ! is_user_logged_in() || ! current_user_can( self::CAPABILITY ) ) {
			return '';
		}
		$role = get_user_meta( get_current_user_id(), self::META_KEY, true );
		return is_string( $role ) && isset( $this->get_roles()[ $role ] ) ? $role : '';
	}

	/**
	 * Check whether this request consumes storefront pricing.
	 *
	 * @return bool
	 */
	private function is_storefront_request() {
		if ( wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}
		if ( is_admin() ) {
			// Do not let an admin order AJAX request inherit a storefront preview.
			$action = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context detection only; no mutation.
			return wp_doing_ajax() && in_array( $action, array( 'woocommerce_get_variation', 'woocommerce_get_refreshed_fragments', 'woocommerce_add_to_cart', 'woocommerce_update_order_review', 'woocommerce_checkout' ), true );
		}
		// Pricing initializes on wp_loaded, before REST_REQUEST is defined.
		$route = isset( $_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) ? wp_unslash( $_GET['rest_route'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request classification.
		if ( isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$route = $GLOBALS['wp']->query_vars['rest_route'];
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		// Settings can resolve a role during plugins_loaded, before WP_Rewrite exists.
		// Keep REST classification available then without calling get_rest_url().
		if ( isset( $GLOBALS['wp_rewrite'] ) ) {
			$rest_url = get_rest_url();
		} else {
			$prefix = rest_get_url_prefix();
			if ( 0 === strpos( (string) get_option( 'permalink_structure' ), '/index.php/' ) ) {
				$prefix = 'index.php/' . $prefix;
			}
			$rest_url = get_home_url( null, $prefix, 'rest' );
		}
		$rest_path = wp_parse_url( $rest_url, PHP_URL_PATH );
		if ( is_string( $path ) && is_string( $rest_path ) && '/' !== $rest_path && 0 === strpos( $path, trailingslashit( $rest_path ) ) ) {
			$route = '/' . substr( $path, strlen( trailingslashit( $rest_path ) ) );
		}
		if ( '' !== $route || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return is_string( $route ) && 0 === strpos( $route, '/wc/store/' );
		}

		return true;
	}

	/**
	 * Resolve the role without modifying user metadata or WordPress capabilities.
	 *
	 * @param string $role Assigned role.
	 * @param int    $user_id User being priced.
	 * @return string
	 */
	public function filter_role( $role, $user_id ) {
		if ( get_current_user_id() !== (int) $user_id ) {
			return $role;
		}
		$selected = $this->get_selected_role();
		return '' !== $selected && $this->is_storefront_request() ? $selected : $role;
	}

	/**
	 * Preview an approved role even when a manager has no customer approval record.
	 *
	 * @param bool $eligible Customer pricing eligibility.
	 * @param int  $user_id Customer ID.
	 * @return bool
	 */
	public function filter_eligibility( $eligible, $user_id ) {
		return $eligible || '' !== $this->filter_role( '', $user_id );
	}

	/** Process an authenticated switch, then reload with a fresh pricing context. */
	public function handle_switch() {
		if ( is_admin() || wp_doing_ajax() || ! isset( $_GET[ self::QUERY_KEY ] ) || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$role  = $_GET[ self::QUERY_KEY ]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified below before use.
		$nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified immediately below.
		if ( ! is_string( $role ) || ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), self::NONCE_ACTION ) ) {
			return;
		}
		$role = sanitize_key( wp_unslash( $role ) );
		if ( 'none' === $role ) {
			delete_user_meta( get_current_user_id(), self::META_KEY );
		} elseif ( isset( $this->get_roles()[ $role ] ) ) {
			update_user_meta( get_current_user_id(), self::META_KEY, $role );
		} else {
			return;
		}
		nocache_headers();
		wp_safe_redirect( remove_query_arg( array( self::QUERY_KEY, '_wpnonce' ) ) );
		exit;
	}

	/** Keep preview HTML out of shared page caches. */
	public function prevent_page_cache() {
		if ( '' !== $this->get_selected_role() && $this->is_storefront_request() ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Shared page-cache convention.
			}
			nocache_headers();
		}
	}

	/**
	 * Place the selector directly after WordPress's Edit product item.
	 *
	 * @param \WP_Admin_Bar $bar Admin bar.
	 */
	public function add_menu( $bar ) {
		if ( is_admin() || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$selected = $this->get_selected_role();
		// Keep Exit preview reachable when navigating away from the product.
		if ( '' === $selected && ( ! is_product() || ! $bar->get_node( 'edit' ) ) ) {
			return;
		}
		$roles = $this->get_roles();
		if ( ! $roles ) {
			return;
		}
		$title = __( 'Preview as wholesale role', 'wholesalex' );
		if ( '' !== $selected ) {
			/* translators: %s: wholesale role name. */
			$title = sprintf( __( 'Previewing as: %s', 'wholesalex' ), $roles[ $selected ]['_role_title'] ?? $selected );
		}
		$bar->add_node(
			array(
				'id'    => 'wholesalex-role-preview',
				'title' => esc_html( $title ),
			)
		);
		$choices = array();
		foreach ( $roles as $id => $role ) {
			$choices[ $id ] = $role['_role_title'] ?? $id;
		}
		if ( '' !== $selected ) {
			$choices['none'] = __( 'Exit preview', 'wholesalex' );
		}
		foreach ( $choices as $id => $label ) {
			if ( $id === $selected ) {
				/* translators: %s: selected wholesale role name. */
				$label = sprintf( __( '%s (previewing)', 'wholesalex' ), $label );
			}
			$bar->add_node(
				array(
					'id'     => 'wholesalex-role-preview-' . $id,
					'parent' => 'wholesalex-role-preview',
					'title'  => esc_html( $label ),
					'href'   => esc_url( wp_nonce_url( add_query_arg( self::QUERY_KEY, $id ), self::NONCE_ACTION ) ),
				)
			);
		}
	}
}
