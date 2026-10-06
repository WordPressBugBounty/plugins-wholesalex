<?php
/**
 * Text and button notice.
 *
 * @package WHOLESALEX\Notice
 */

namespace WHOLESALEX\Includes\Admin\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * Standard admin notice: icon, a heading with an offer line, and an upgrade button.
 */
class Content_Notice extends Abstract_Campaign_Notice {

	/**
	 * Template views/content.php.
	 */
	const VIEW = 'content';

	/**
	 * Content notice settings.
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'utm_key'            => 'content_notice',
				'content_heading'    => '',
				'content_subheading' => '',    // May contain one %s, replaced by `discount_content` in bold.
				'discount_content'   => '',
				'border_color'       => '#6c3bff',
				'icon'               => '',
				'is_discount_logo'   => false, // true: square discount logo and outline button.
				'button_text'        => __( 'Upgrade Now!', 'wholesalex' ),
				'background_color'   => '',    // Button colour when is_discount_logo is false.
			)
		);
	}

	/**
	 * The offer line with `discount_content` in bold in place of its %s.
	 *
	 * The line is usually translated, and on PHP 8 a stray % or an extra placeholder in a
	 * translation makes sprintf() throw. Fall back to a plain replacement instead.
	 *
	 * @return string Safe HTML.
	 */
	public function get_subheading_html() {
		$format   = wp_kses_post( (string) $this->get( 'content_subheading' ) );
		$discount = '<strong>' . esc_html( $this->get( 'discount_content' ) ) . '</strong>';

		try {
			$html = sprintf( $format, $discount );
		} catch ( \Throwable $e ) {
			$html = false;
		}

		if ( false === $html ) { // PHP 7.4 returns false instead of throwing.
			$html = str_replace( array( '%1$s', '%s' ), $discount, $format );
		}
		return $html;
	}
}
