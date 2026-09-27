<?php
/**
 * Server-side registration form scope and approval authorization.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Resolve public registration roles from trusted form configuration. */
class Registration_Context {

	/**
	 * Normalize a trusted shortcode's registration_role attribute.
	 *
	 * @param mixed $scope Configured attribute.
	 * @return string
	 */
	public static function normalize_scope( $scope ) {
		return is_string( $scope ) && '' !== $scope ? $scope : 'global';
	}

	/**
	 * Equivalent forms on the same page share a scope; other pages never do.
	 *
	 * @param int    $post_id Source page.
	 * @param string $scope Configured attribute.
	 * @return string
	 */
	public static function form_id( $post_id, $scope ) {
		return absint( $post_id ) . ':' . hash( 'sha256', self::normalize_scope( $scope ) );
	}

	/**
	 * Read saved configuration, without executing shortcodes or trusting POST.
	 * Includes the Forms block (which saves a shortcode), synced patterns and
	 * shortcode widgets saved in Elementor/Beaver Builder/Breakdance data.
	 *
	 * @param int   $post_id Source post.
	 * @param array $visited Cycle guard for reusable content.
	 * @return array
	 */
	private static function saved_scopes( $post_id, $visited = array() ) {
		if ( isset( $visited[ $post_id ] ) || count( $visited ) >= 20 ) {
			return array();
		}
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return array();
		}
		$visited[ $post_id ] = true;
		$tags                = (array) apply_filters( 'wholesalex_registration_shortcode_tags', array( 'wholesalex_registration', 'wholesalex_login_registration' ) );
		$scopes              = self::scopes_in_value( $post->post_content, $tags );
		foreach ( array( '_elementor_data', '_fl_builder_data', '_breakdance_data' ) as $key ) {
			$scopes = array_merge( $scopes, self::scopes_in_value( get_post_meta( $post_id, $key, true ), $tags ) );
		}
		$blocks = parse_blocks( $post->post_content );
		while ( $blocks ) {
			$block = array_pop( $blocks );
			if ( 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) ) {
				$scopes = array_merge( $scopes, self::saved_scopes( absint( $block['attrs']['ref'] ), $visited ) );
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$blocks = array_merge( $blocks, $block['innerBlocks'] );
			}
		}
		/**
		 * Supply trusted scopes for forms placed by PHP templates or other builders.
		 * Resolve these from saved configuration, never request parameters.
		 *
		 * @param string[] $scopes Saved registration_role attributes.
		 * @param int      $post_id Page containing the forms.
		 */
		$scopes = apply_filters( 'wholesalex_registration_form_scopes', $scopes, $post_id );
		return array_values( array_unique( array_filter( (array) $scopes, 'is_string' ) ) );
	}

	/**
	 * Extract actual shortcode attributes from saved text or builder trees.
	 *
	 * @param mixed $value Saved content.
	 * @param array $tags Registration shortcode names.
	 * @param int   $depth Recursion guard.
	 * @return array
	 */
	private static function scopes_in_value( $value, $tags, $depth = 0 ) {
		if ( $depth > 30 ) {
			return array();
		}
		$scopes = array();
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				$scopes = array_merge( $scopes, self::scopes_in_value( $item, $tags, $depth + 1 ) );
			}
		} elseif ( is_string( $value ) && '' !== $value ) {
			$decoded = json_decode( $value, true );
			if ( is_array( $decoded ) ) {
				return self::scopes_in_value( $decoded, $tags, $depth + 1 );
			}
			if ( preg_match_all( '/' . get_shortcode_regex( $tags ) . '/s', $value, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $match ) {
					if ( '[' === $match[1] && ']' === $match[6] ) {
						continue;
					}
					$atts     = shortcode_parse_atts( $match[3] );
					$scopes[] = self::normalize_scope( isset( $atts['registration_role'] ) ? $atts['registration_role'] : '' );
				}
			}
		}
		return $scopes;
	}

	/**
	 * Resolve a form identifier exclusively against its current saved page.
	 *
	 * @param string $form_id Form identifier.
	 * @return array|null
	 */
	public static function resolve( $form_id ) {
		if ( ! is_string( $form_id ) || ! preg_match( '/^([1-9][0-9]*):[a-f0-9]{64}$/D', $form_id, $matches ) ) {
			return null;
		}
		$post_id = absint( $matches[1] );
		foreach ( self::saved_scopes( $post_id ) as $scope ) {
			if ( hash_equals( self::form_id( $post_id, $scope ), $form_id ) ) {
				$roles = array_column( wholesalex()->get_roles( 'store_mode_roles_option' ), 'value' );
				$roles = array_values( array_diff( $roles, array( '', 'wholesalex_guest' ) ) );
				if ( 'all_b2b' === $scope ) {
					$roles = array_values( array_diff( $roles, array( 'wholesalex_b2c_users' ) ) );
				} elseif ( 'global' !== $scope ) {
					$roles = in_array( $scope, $roles, true ) ? array( $scope ) : array();
				}
				return array(
					'form_id'       => $form_id,
					'post_id'       => $post_id,
					'allowed_roles' => $roles,
				);
			}
		}
		return null;
	}

	/** Validate the form nonce and source page, then load roles from the server. */
	public static function posted() {
		if (
			! isset( $_POST['wholesalex_registration_form'], $_POST['wholesalex_registration_form_nonce'], $_POST['_wp_http_referer'] )
			|| ! is_string( $_POST['wholesalex_registration_form'] )
			|| ! is_string( $_POST['wholesalex_registration_form_nonce'] )
			|| ! is_string( $_POST['_wp_http_referer'] )
		) {
			return null;
		}
		$form_id = sanitize_text_field( wp_unslash( $_POST['wholesalex_registration_form'] ) );
		$nonce   = sanitize_key( wp_unslash( $_POST['wholesalex_registration_form_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'wholesalex-registration-form|' . $form_id ) ) {
			return null;
		}
		$context = self::resolve( $form_id );
		if ( null === $context || post_password_required( $context['post_id'] ) ) {
			return null;
		}
		// A referrer is only a consistency check, never proof of authorization.
		$referer = esc_url_raw( wp_unslash( $_POST['_wp_http_referer'] ) );
		if ( 0 === strpos( $referer, '/' ) && 0 !== strpos( $referer, '//' ) ) {
			$home    = wp_parse_url( home_url( '/' ) );
			$referer = $home['scheme'] . '://' . $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . $referer;
		}
		if ( wp_parse_url( $referer, PHP_URL_HOST ) !== wp_parse_url( home_url( '/' ), PHP_URL_HOST ) || url_to_postid( $referer ) !== $context['post_id'] ) {
			return null;
		}
		return $context;
	}

	/** Read the current WooCommerce form scope for delayed approvals. */
	public static function woo_roles() {
		$fields = WholesaleX_CommonUtils::get_form_fields();
		$roles  = array();
		foreach ( $fields['woo_custom_fields'] as $field ) {
			if ( isset( $field['name'] ) && 'wholesalex_registration_role' === $field['name'] ) {
				$roles = array_column( isset( $field['option'] ) ? $field['option'] : array(), 'value' );
				break;
			}
		}
		return array_values( array_intersect( array_diff( $roles, array( '', 'wholesalex_guest' ) ), array_column( wholesalex()->get_roles( 'store_mode_roles_option' ), 'value' ) ) );
	}

	/**
	 * Revalidate both the originally authorized role and the current form scope.
	 * Legacy pending requests without provenance require explicit admin role assignment.
	 *
	 * @param int    $user_id Registrant.
	 * @param string $role Requested role.
	 * @return \WP_Error|null
	 */
	public static function approval_error( $user_id, $role ) {
		if ( '' === $role ) {
			return null;
		}
		$context = get_user_meta( $user_id, '__wholesalex_registration_context', true );
		$roles   = array();
		if ( is_array( $context ) && isset( $context['role'], $context['form_id'] ) && $role === $context['role'] ) {
			if ( 'woocommerce' === $context['form_id'] ) {
				$roles = self::woo_roles();
			} else {
				$current = self::resolve( $context['form_id'] );
				$roles   = null !== $current ? $current['allowed_roles'] : array();
			}
		}
		if ( ! is_string( $role ) || ! in_array( $role, $roles, true ) ) {
			return new \WP_Error( 'wholesalex_registration_scope_invalid', __( 'The requested registration role can no longer be verified for its original form. An administrator must review this account and assign an appropriate WholesaleX role before approval.', 'wholesalex' ) );
		}
		return null;
	}
}
