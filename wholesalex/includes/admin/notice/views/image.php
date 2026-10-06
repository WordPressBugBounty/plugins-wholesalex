<?php
/**
 * Image banner notice.
 *
 * @package WHOLESALEX\Notice
 *
 * @var \WHOLESALEX\Includes\Admin\Notice\Image_Notice $notice
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wsx-notice-wrapper wsx-image-notice-wrapper wsx-notice notice wsx-free-notice">
	<div class="wsx-image-banner">
		<a class="wc-dismiss-notice wsx-content-notice-close" href="<?php echo esc_url( $notice->get_dismiss_url() ); ?>"><span class="wsx-content-notice-close-icon dashicons dashicons-dismiss" style="color: <?php echo esc_attr( $notice->get( 'close_color' ) ); ?>;"> </span></a>
		<a class="wsx-btn-image" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $notice->get_url() ); ?>">
			<img loading="lazy" src="<?php echo esc_url( $notice->get( 'banner_src' ) ); ?>" alt="Discount Banner"/>
		</a>
	</div>
</div>

