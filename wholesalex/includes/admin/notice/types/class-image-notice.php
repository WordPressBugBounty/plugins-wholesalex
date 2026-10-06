<?php
/**
 * Image banner notice.
 *
 * @package WHOLESALEX\Notice
 */

namespace WHOLESALEX\Includes\Admin\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * A single linked image with a close button.
 */
class Image_Notice extends Abstract_Campaign_Notice {

	/**
	 * Template views/image.php.
	 */
	const VIEW = 'image';

	/**
	 * Image banner settings.
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array_merge(
			parent::get_defaults(),
			array(
				'banner_src'  => '',
				'close_color' => '#000000',
			)
		);
	}
}
