<?php
/**
 * Compatibility for the removed promotional notice system.
 *
 * @package WholesaleX
 */

namespace WHOLESALEX;

defined( 'ABSPATH' ) || exit;

/** Retain the public class and localized configuration contract without promotions. */
class Notice {
	/**
	 * Initialize the notice handler.
	 */
	public function __construct() {}

	/** Existing admin bundles consume an empty configuration when no banner exists. */
	public static function get_hellobar_config() {
		return array();
	}
}
