<?php

defined( 'ABSPATH' ) || exit;

// For Backward Compatibility.
// Load colors.
$bg        = get_option( 'woocommerce_email_background_color' );
$body      = get_option( 'woocommerce_email_body_background_color' );
$base      = get_option( 'woocommerce_email_base_color' );
$base_text = wc_light_or_dark( $base, '#202020', '#ffffff' );
$text      = get_option( 'woocommerce_email_text_color' );

// Pick a contrasting color for links.
$link_color = wc_hex_is_light( $base ) ? $base : $base_text;

if ( wc_hex_is_light( $body ) ) {
	$link_color = wc_hex_is_light( $base ) ? $base_text : $base;
}

$bg_darker_10    = wc_hex_darker( $bg, 10 );
$body_darker_10  = wc_hex_darker( $body, 10 );
$base_lighter_20 = wc_hex_lighter( $base, 20 );
$base_lighter_40 = wc_hex_lighter( $base, 40 );
$text_lighter_20 = wc_hex_lighter( $text, 20 );
$text_lighter_40 = wc_hex_lighter( $text, 40 );

?>
<style type="text/css"> 
				body{
					background-color: <?php echo esc_attr( $bg ); ?>;
					padding: 0;
					text-align: center;
				}

				#outer_wrapper {
					background-color: <?php echo esc_attr( $bg ); ?>;
				}

				#wrapper {
					margin: 0 auto;
					padding: 70px 0;
					-webkit-text-size-adjust: none !important;
					width: 100%;
					max-width: 600px;
				}

				#template_container {
					box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1) !important;
					background-color: <?php echo esc_attr( $body ); ?>;
					border: 1px solid <?php echo esc_attr( $bg_darker_10 ); ?>;
					border-radius: 3px !important;
				}

				#template_header {
					background-color: <?php echo esc_attr( $base ); ?>;
					border-radius: 3px 3px 0 0 !important;
					color: <?php echo esc_attr( $base_text ); ?>;
					border-bottom: 0;
					font-weight: bold;
					line-height: 100%;
					vertical-align: middle;
					font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
				}

				#template_header h1,
				#template_header h1 a {
					color: <?php echo esc_attr( $base_text ); ?>;
					background-color: inherit;
				}

				#template_header_image img {
					margin-left: 0;
					margin-right: 0;
				}

				#template_footer td {
					padding: 0;
					border-radius: 6px;
				}

				#template_footer #credit {
					border: 0;
					color: <?php echo esc_attr( $text_lighter_40 ); ?>;
					font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
					font-size: 12px;
					line-height: 150%;
					text-align: center;
					padding: 24px 0;
				}

				#template_footer #credit p {
					margin: 0 0 16px;
				}

				#body_content {
					background-color: <?php echo esc_attr( $body ); ?>;
				}

				#body_content table td {
					padding: 48px 48px 32px;
				}

				#body_content table td td {
					padding: 12px;
				}

				#body_content table td th {
					padding: 12px;
				}

				#body_content td ul.wc-item-meta {
					font-size: small;
					margin: 1em 0 0;
					padding: 0;
					list-style: none;
				}

				#body_content td ul.wc-item-meta li {
					margin: 0.5em 0 0;
					padding: 0;
				}

				#body_content td ul.wc-item-meta li p {
					margin: 0;
				}

				#body_content p {
					margin: 0 0 16px;
				}

				#body_content_inner {
					color: <?php echo esc_attr( $text_lighter_20 ); ?>;
					font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
					font-size: 14px;
					line-height: 150%;
					text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
				}

				.td {
					color: <?php echo esc_attr( $text_lighter_20 ); ?>;
					border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
					vertical-align: middle;
				}

				.address {
					padding: 12px;
					color: <?php echo esc_attr( $text_lighter_20 ); ?>;
					border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
				}

				.text {
					color: <?php echo esc_attr( $text ); ?>;
					font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
				}

				.link {
					color: <?php echo esc_attr( $link_color ); ?>;
				}

				#header_wrapper {
					padding: 36px 48px;
					display: block;
				}

				h1 {
					color: <?php echo esc_attr( $base ); ?>;
					font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
					font-size: 30px;
					font-weight: 300;
					line-height: 150%;
					margin: 0;
					text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
					text-shadow: 0 1px 0 <?php echo esc_attr( $base_lighter_20 ); ?>;
				}

				h2 {
					color: <?php echo esc_attr( $base ); ?>;
					display: block;
					font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
					font-size: 18px;
					font-weight: bold;
					line-height: 130%;
					margin: 0 0 18px;
					text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
				}

				h3 {
					color: <?php echo esc_attr( $base ); ?>;
					display: block;
					font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
					font-size: 16px;
					font-weight: bold;
					line-height: 130%;
					margin: 16px 0 8px;
					text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
				}

				a {
					color: <?php echo esc_attr( $link_color ); ?>;
					font-weight: normal;
					text-decoration: underline;
				}

				img {
					border: none;
					display: inline-block;
					font-size: 14px;
					font-weight: bold;
					height: auto;
					outline: none;
					text-decoration: none;
					text-transform: capitalize;
					vertical-align: middle;
					margin-<?php echo is_rtl() ? 'left' : 'right'; ?>: 10px;
					max-width: 100%;
				}

				/**
				* Media queries are not supported by all email clients, however they do work on modern mobile
				* Gmail clients and can help us achieve better consistency there.
				*/
				@media screen and (max-width: 600px) {
					#header_wrapper {
						padding: 27px 36px !important;
						font-size: 24px;
					}

					#body_content table > tbody > tr > td {
						padding: 10px !important;
					}

					#body_content_inner {
						font-size: 10px !important;
					}
				}
			</style>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></title>
		
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
		<table width="100%" id="outer_wrapper">
			<tr>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
				<td width="600">
					<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
						<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
							<tr>
								<td align="center" valign="top">
									<div id="template_header_image">
										<?php
										$img = get_option( 'woocommerce_email_header_image' );

										if ( $img ) {
											echo '<p style="margin-top:0;"><img src="' . esc_url( $img ) . '" alt="' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '" /></p>';
										}
										?>
									</div>
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container">
										<tr>
											<td align="center" valign="top">
												<!-- Header -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header">
													<tr>
														<td id="header_wrapper">
															<h1><?php echo esc_html( $email_heading ); ?></h1>
														</td>
													</tr>
												</table>
												<!-- End Header -->
											</td>
										</tr>
										<tr>
											<td align="center" valign="top">
												<!-- Body -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
													<tr>
														<td valign="top" id="body_content">
															<!-- Content -->
															<table border="0" cellpadding="20" cellspacing="0" width="100%">
																<tr>
																	<td valign="top">
																		<div id="body_content_inner">
																			<p>
																				<?php echo wp_kses_post( wpautop( isset( $email_content ) ? $email_content : '' ) ); ?>
																			</p>

																		</div>
																	</td>
																</tr>
															</table>
															<!-- End Content -->
														</td>
													</tr>
												</table>
												<!-- End Body -->
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td align="center" valign="top">
									<!-- Footer -->
									<table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer">
										<tr>
											<td valign="top">
												<table border="0" cellpadding="10" cellspacing="0" width="100%">
													<tr>
														<td colspan="2" valign="middle" id="credit">
															<?php
															echo wp_kses_post(
																wpautop(
																	wptexturize(
																		apply_filters( 'wholealex_email_footer_text', get_option( 'woocommerce_email_footer_text' ) )
																	)
																)
															);
															?>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									<!-- End Footer -->
								</td>
							</tr>
						</table>
					</div>
				</td>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
			</tr>
		</table>
	</body>
</html>
