<?php
/**
 * Countdown banner notice.
 *
 * @package WHOLESALEX\Notice
 */

namespace WHOLESALEX\Includes\Admin\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * Full-width banner: left image, text with a countdown, right image, over a background image.
 */
class Banner_Notice extends Abstract_Campaign_Notice {

	/**
	 * Template views/banner.php.
	 */
	const VIEW = 'banner';

	/**
	 * File names looked up inside `image_dir`.
	 *
	 * @var array
	 */
	const IMAGE_FILES = array(
		'left'  => 'offer.png',
		'right' => 'btn.png',
		'bg'    => 'bg.png',
	);

	/**
	 * Banner settings.
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'image_dir'          => '', // Folder under assets/img/dashboard_banner/ holding offer.png, btn.png and bg.png.
				'left_image'         => '', // Overrides image_dir/offer.png.
				'right_image'        => '', // Overrides image_dir/btn.png.
				'bg_image'           => '', // Overrides image_dir/bg.png.
				'text'               => '',
				'brand_color'        => '#6c3bff',
				'countdown_duration' => 259200, // Seconds; 3 days.
				'countdown_color'    => '#3CF357',
			)
		);
	}

	/**
	 * Image URL for one slot.
	 *
	 * @param string $slot `left`, `right` or `bg`.
	 * @return string Unescaped URL.
	 */
	public function get_image( $slot ) {
		$explicit = $this->get( $slot . '_image' );
		if ( ! empty( $explicit ) ) {
			return $explicit;
		}
		if ( ! isset( self::IMAGE_FILES[ $slot ] ) || ! $this->get( 'image_dir' ) ) {
			return '';
		}
		return WHOLESALEX_URL . 'assets/img/dashboard_banner/' . $this->get( 'image_dir' ) . '/' . self::IMAGE_FILES[ $slot ];
	}

	/**
	 * The banner shows a countdown.
	 *
	 * @return bool
	 */
	public function uses_countdown_script() {
		return true;
	}
}
