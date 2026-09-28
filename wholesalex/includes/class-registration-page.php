<?php
/**
 * Lifecycle and Gutenberg content for the managed registration page.
 *
 * @package WholesaleX
 */
namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

class Registration_Page {
	const OPTION = 'wholesalex_registration_page_id';
	const SLUG   = 'wholesale-register';

	/** Register authenticated page controls. */
	public static function register_routes() {
		register_rest_route( 'wholesalex/v1', '/registration-page', array(
			'methods'             => array( 'GET', 'POST' ),
			'callback'            => array( __CLASS__, 'request' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' ) && current_user_can( 'edit_pages' );
			},
		) );
	}

	/** Create once on installation; reactivation must respect intentional deletion. */
	public static function install() {
		if ( false === get_option( self::OPTION, false ) ) {
			self::create();
		}
	}

	/** Use the same choices as the Gutenberg form block. */
	public static function options() {
		$options = array(
			array( 'value' => 'global', 'label' => __( 'Global', 'wholesalex' ) ),
			array( 'value' => 'all_b2b', 'label' => __( 'B2B Global Form', 'wholesalex' ) ),
		);
		foreach ( wholesalex()->get_roles() as $id => $role ) {
			if ( 'wholesalex_guest' !== $id ) {
				$options[] = array( 'value' => $id, 'label' => $role['_role_title'] );
			}
		}
		return $options;
	}

	/** Resolve availability from WordPress, never from a stale boolean option. */
	private static function page() {
		$id   = absint( get_option( self::OPTION ) );
		$page = $id ? get_post( $id ) : null;
		return $page && 'page' === $page->post_type && ! in_array( $page->post_status, array( 'trash', 'auto-draft' ), true ) ? $page : null;
	}

	/** Find the first form, including forms nested inside layout blocks. */
	private static function find_form( $blocks ) {
		foreach ( $blocks as $block ) {
			if ( 'wholesalex/forms' === $block['blockName'] ) {
				return $block;
			}
			$found = self::find_form( $block['innerBlocks'] );
			if ( $found ) {
				return $found;
			}
		}
		return null;
	}

	/** Return page links and current selection from the actual Gutenberg content. */
	public static function state() {
		$page           = self::page();
		$page_available = (bool) $page;
		$form           = $page ? self::find_form( parse_blocks( $page->post_content ) ) : null;
		return array(
			'available' => $page_available,
			'url'       => $page ? get_permalink( $page ) : home_url( '/' . self::SLUG . '/' ),
			'edit_url'  => $page ? get_edit_post_link( $page->ID, 'raw' ) : '',
			'role'      => $form ? ( $form['attrs']['registration_role'] ?? '' ) : 'all_b2b',
			'options'   => self::options(),
		);
	}

	/** Match the static markup produced by the Gutenberg block's save function. */
	private static function form_block( $role, $tag = 'wholesalex_login_registration' ) {
		$html = '<div class="wp-block-wholesalex-forms wholesalex-form">[' . $tag . " registration_role='" . esc_attr( $role ) . "']</div>";
		return array(
			'blockName'    => 'wholesalex/forms',
			'attrs'        => array( 'registration_role' => $role, 'tag' => $tag ),
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
	}

	/** Create the canonical URL without taking over an unrelated page. */
	public static function create() {
		if ( self::page() ) {
			return self::state();
		}
		if ( self::SLUG !== wp_unique_post_slug( self::SLUG, 0, 'publish', 'page', 0 ) ) {
			return new \WP_Error( 'registration_page_conflict', __( 'The wholesale-register URL is already used by another page. Change that page’s slug before creating the registration page.', 'wholesalex' ), array( 'status' => 409 ) );
		}
		$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'Wholesale Registration and Login', 'wholesalex' ),
			'post_name'    => self::SLUG,
			'post_content' => wp_slash( serialize_block( self::form_block( 'all_b2b' ) ) ),
		), true );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		update_option( self::OPTION, $id );
		return self::state();
	}

	/** Update only the form; retain other page content and the login toggle. */
	private static function replace_form( &$blocks, $role ) {
		foreach ( $blocks as &$block ) {
			if ( 'wholesalex/forms' === $block['blockName'] ) {
				$tag   = $block['attrs']['tag'] ?? 'wholesalex_registration';
				$tag   = in_array( $tag, array( 'wholesalex_registration', 'wholesalex_login_registration' ), true ) ? $tag : 'wholesalex_login_registration';
				$block['attrs']['registration_role'] = $role;
				$shortcode = '[' . $tag . " registration_role='" . esc_attr( $role ) . "']";
				$replace = static function ( $html ) use ( $shortcode ) {
					return preg_replace_callback( '/\[wholesalex_(?:login_)?registration\b[^\]]*\]/', static function () use ( $shortcode ) {
						return $shortcode;
					}, $html );
				};
				$block['innerHTML'] = $replace( $block['innerHTML'] );
				foreach ( $block['innerContent'] as &$content ) {
					if ( is_string( $content ) ) {
						$content = $replace( $content );
					}
				}
				return true;
			}
			if ( self::replace_form( $block['innerBlocks'], $role ) ) {
				return true;
			}
		}
		return false;
	}

	/** REST nonces are checked by WordPress cookie authentication. */
	public static function request( $request ) {
		if ( 'GET' === $request->get_method() ) {
			return self::state();
		}
		if ( 'create' === $request->get_param( 'action' ) ) {
			if ( ! current_user_can( 'publish_pages' ) ) {
				return new \WP_Error( 'registration_page_forbidden', __( 'You cannot publish pages.', 'wholesalex' ), array( 'status' => 403 ) );
			}
			return self::create();
		}
		$role = $request->get_param( 'role' );
		if ( ! is_string( $role ) || ! in_array( $role, array_column( self::options(), 'value' ), true ) ) {
			return new \WP_Error( 'registration_form_invalid', __( 'Select a valid form.', 'wholesalex' ), array( 'status' => 400 ) );
		}
		$page = self::page();
		if ( ! $page ) {
			return new \WP_Error( 'registration_page_missing', __( 'Create the registration page first.', 'wholesalex' ), array( 'status' => 404 ) );
		}
		if ( ! current_user_can( 'edit_post', $page->ID ) ) {
			return new \WP_Error( 'registration_page_forbidden', __( 'You cannot edit this page.', 'wholesalex' ), array( 'status' => 403 ) );
		}
		$blocks = parse_blocks( $page->post_content );
		if ( ! self::replace_form( $blocks, $role ) ) {
			$blocks[] = self::form_block( $role );
		}
		$result = wp_update_post( array( 'ID' => $page->ID, 'post_content' => wp_slash( serialize_blocks( $blocks ) ) ), true );
		return is_wp_error( $result ) ? $result : self::state();
	}
}
