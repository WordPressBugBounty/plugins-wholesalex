<?php
/**
 * Text and button notice.
 *
 * @package WHOLESALEX\Notice
 *
 * @var \WHOLESALEX\Includes\Admin\Notice\Content_Notice $notice
 */

defined( 'ABSPATH' ) || exit;

$url = $notice->get_url();
?>
<div class="wsx-content-notice-wrapper wsx-notice notice"
style="border-left: 3px solid <?php echo esc_attr( $notice->get( 'border_color' ) ); ?>;"
>
	<?php
	if ( $notice->get( 'is_discount_logo' ) ) {
		?>
		<div class="wsx-content-notice-discout-icon"> <img alt="" src="<?php echo esc_url( $notice->get( 'icon' ) ); ?>"/>  </div>
		<?php
	} else {
		?>
		<div class="wsx-content-notice-icon"> <img alt="" src="<?php echo esc_url( $notice->get( 'icon' ) ); ?>"/>  </div>
		<?php
	}
	?>

	<div class="wsx-notice-content-wrapper">
		<div class="">
			<strong><?php echo esc_html( $notice->get( 'content_heading' ) ); ?> </strong>
		<?php echo $notice->get_subheading_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from wp_kses_post() and esc_html() in Content_Notice. ?>
		</div>
		<div class="wsx-content-notice-buttons">
	<?php if ( $notice->get( 'is_discount_logo' ) ) : ?>
				<a class="wsx-content-discount_btn" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $notice->get( 'button_text' ) ); ?>
				</a>
			<?php else : ?>
				<a class="wsx-content-notice-btn button button-primary" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" style="background-color: <?php echo ! empty( $notice->get( 'background_color' ) ) ? esc_attr( $notice->get( 'background_color' ) ) : '#6c3bff'; ?>;">
				<?php echo esc_html( $notice->get( 'button_text' ) ); ?>

				</a>
			<?php endif; ?>
		</div>
	</div>
	<a href="<?php echo esc_url( $notice->get_dismiss_url() ); ?>" class="wsx-content-notice-close"><span class="wsx-content-notice-close-icon dashicons dashicons-dismiss"> </span></a>
</div>
