<?php

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook is provided by WooCommerce.


$wholesalex_user_id = 0;

if ( isset( $user ) && is_object( $user ) && ! empty( $user->ID ) ) {
	$wholesalex_user_id = (int) $user->ID;
}

// From `$user_login` (username) passed to the template.
if ( empty( $wholesalex_user_id ) && ! empty( $user_login ) ) {
	$wholesalex_maybe_user = get_user_by( 'login', $user_login );
	if ( $wholesalex_maybe_user && ! empty( $wholesalex_maybe_user->ID ) ) {
		$wholesalex_user_id = (int) $wholesalex_maybe_user->ID;
	}
}

// From email if available.
if ( empty( $wholesalex_user_id ) && isset( $user ) && is_object( $user ) && ! empty( $user->user_email ) ) {
	$wholesalex_maybe_user = get_user_by( 'email', $user->user_email );
	if ( $wholesalex_maybe_user && ! empty( $wholesalex_maybe_user->ID ) ) {
		$wholesalex_user_id = (int) $wholesalex_maybe_user->ID;
	}
}

// Final fallback to first admin only if we truly cannot determine the user.
if ( empty( $wholesalex_user_id ) ) {
	$wholesalex_admin_users = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
		)
	);
	$wholesalex_user_id = ! empty( $wholesalex_admin_users ) ? (int) $wholesalex_admin_users[0]->ID : 1;
}

$user_email = ( isset( $user ) && is_object( $user ) ) ? $user->user_email : wp_get_current_user()->user_email; //phpcs:ignore

?>

<p>
	<p><?php printf( esc_html_x( 'Hi,', 'WholesaleX Registration Pending(Admin) Email', 'wholesalex' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php /* translators: 1: User Profile URL, 2: Username */ ?>
	<p><?php printf( _x( 'A new user, <a class="wsx-link" href="%1$s">%2$s</a>, has registered and is awaiting your approval to access our platform.', 'WholesaleX Registration Pending(Admin) Email', 'wholesalex' ), admin_url( 'user-edit.php?user_id=' . $wholesalex_user_id ), $user_login ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<p><?php printf( esc_html_x( 'Please review the registration details and take the necessary steps to grant access to the new User.', 'WholesaleX Registration Pending(Admin) Email', 'wholesalex' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

</p>
<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Hook is provided by WooCommerce.
