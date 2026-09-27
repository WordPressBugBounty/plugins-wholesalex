<?php
/**
 * WholesaleX Menu
 *
 * @package WHOLESALEX
 * @since 1.0.0
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/**
 * WholesaleX Menu Class.
 */
class WHOLESALEX_Menu {

	/**
	 * Menu Constructor
	 */
	public function __construct() {
		add_filter( 'plugin_row_meta', array( $this, 'plugin_settings_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . WHOLESALEX_BASE, array( $this, 'plugin_action_links_callback' ) );
	}

	/**
	 * Settings Pro Update Link
	 *
	 * @param ARRAY $links Plugin Action Links.
	 * @since v.1.0.0
	 * @return ARRAY
	 */
	public function plugin_action_links_callback( $links ) {

		$setting_link                        = array();
		$setting_link['wholesalex_settings'] = '<a href="' . esc_url( admin_url( 'admin.php?page=wholesalex-settings' ) ) . '">' . esc_html__( 'Settings', 'wholesalex' ) . '</a>';
		return array_merge( $setting_link, $links );
	}

	/**
	 * Plugin Page Menu Add
	 *
	 * @param ARRAY  $links Plugin Action Links.
	 * @param STRING $file Plugin File.
	 * @since v.1.0.0
	 * @return ARRAY
	 */
	public function plugin_settings_meta( $links, $file ) {
		if ( strpos( $file, 'wholesalex.php' ) !== false ) {
			$new_links = array(
				'wholesalex_docs'    => '<a href="https://getwholesalex.com/documentation/" target="_blank">' . esc_html__( 'Docs', 'wholesalex' ) . '</a>',
				'wholesalex_support' => '<a href="' . esc_url( 'https://getwholesalex.com/contact/' ) . '" target="_blank">' . esc_html__( 'Support', 'wholesalex' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}
}
