<?php
/**
 * Notice contract.
 *
 * @package WHOLESALEX\Notice
 */

namespace WHOLESALEX\Includes\Admin\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * Anything the Notice class can show in wp-admin.
 */
interface Notice_Type {

	/**
	 * Unique key. Also names the transient that remembers a dismissal.
	 *
	 * @return string
	 */
	public function get_key();

	/**
	 * Whether the notice should show on this request.
	 *
	 * @return bool
	 */
	public function should_display();

	/**
	 * Queue the stylesheets and scripts the notice needs.
	 *
	 * @return void
	 */
	public function enqueue_assets();

	/**
	 * Print the notice markup.
	 *
	 * @return void
	 */
	public function render();
}
