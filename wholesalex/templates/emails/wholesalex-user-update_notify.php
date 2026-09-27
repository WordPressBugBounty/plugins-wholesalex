<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- Preserve existing include paths and template overrides.
/**
 * Wholesalex user update notify.
 *
 * @package WholesaleX
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook is provided by WooCommerce.
$wholesalex_user                 = get_user_by( 'login', $user_login );
$wholesalex_updated_profile_data = implode( ', ', $updated_data );

?>

<p>
	<?php /* translators: %1$s is a URL, %2$s is a user login name */ ?>
	<p> <?php printf( 'Hello <a class="wsx-link" href="%1$s">%2$s</a>', esc_url( admin_url( 'user-edit.php?user_id=' . $wholesalex_user->ID ) ), esc_html( $user_login ) ); ?> </p>
	<?php /* translators: %s: Updated user profile data. */ ?>
	<p><?php echo esc_html( sprintf( __( 'Please note that the admin(s) made changes to your User Data: %s', 'wholesalex' ), $wholesalex_updated_profile_data ) ); ?></p>
	<p><?php echo esc_html__( 'Please review these changes immediately.', 'wholesalex' ); ?></p>

</p>
<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook is provided by WooCommerce.
