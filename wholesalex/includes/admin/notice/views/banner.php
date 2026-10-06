<?php
/**
 * Countdown banner notice.
 *
 * @package WHOLESALEX\Notice
 *
 * @var \WHOLESALEX\Includes\Admin\Notice\Banner_Notice $notice
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	class="wsx-notice-wrapper wsx-banner-notice wsx-notice notice"
	style="
		border-left: 3px solid <?php echo esc_attr( $notice->get( 'brand_color' ) ); ?>;
		<?php if ( $notice->get_image( 'bg' ) ) : ?>background-image: url('<?php echo esc_url( $notice->get_image( 'bg' ) ); ?>');<?php endif; ?>
">
	<a
		class="wc-dismiss-notice dashicons dashicons-no-alt"
		style="
			position: absolute;
			top: 1px;
			right: 1px;
			border-radius: 50%;
			background-color: black;
			color: white;
			font-size: 14px;
			display: flex;
			align-items: center;
			justify-content: center;
		"
		aria-label="<?php esc_html_e( 'Close Banner', 'wholesalex' ); ?>"
		href="<?php echo esc_url( $notice->get_dismiss_url() ); ?>">
	</a>

	<a class="wsx-banner-link" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $notice->get_url() ); ?>">
		<div class="wsx-banner-content">
			<img alt="" class="wsx-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice->get_image( 'left' ) ); ?>" />
			<div class="wsx-banner-main">
				<span class="wsx-banner-main-text">
					<?php echo esc_html( $notice->get( 'text' ) ); ?>
				</span>
				<div
					class="wsx-notice-countdown"
					style="
						color: <?php echo esc_attr( $notice->get( 'countdown_color' ) ); ?>;
					"
					data-notice-key="<?php echo esc_attr( $notice->get_key() . '-countdown' ); ?>"
					data-duration="<?php echo esc_attr( $notice->get( 'countdown_duration' ) ); ?>">
					00:00:00:00
				</div>
			</div>
			<img alt="" class="wsx-banner-side-image" loading="lazy" src="<?php echo esc_url( $notice->get_image( 'right' ) ); ?>" />
		</div>
	</a>
</div>
