( function ( $ ) {
	'use strict';
	$( document ).on( 'click', '.wholesalex-discount-new', function ( e ) {
		e.preventDefault();
		$( '.wholesalex-repetable' ).append(
			$( '.wholesalex-predefined' ).html()
		);
	} );

	$( document ).on( 'click', '.wholesalex-discount-remove', function ( e ) {
		e.preventDefault();
		$( this ).closest( '.wholesalex-discount' ).remove();
	} );

	$( document ).on( 'change', '#wholesalex_quantity_based', function ( e ) {
		e.preventDefault();
		if ( $( this ).is( ':checked' ) ) {
			$( '.wholesalex-discount-wrap' ).show();
		} else {
			$( '.wholesalex-discount-wrap' ).hide();
		}
	} );

	$( document ).on(
		'click',
		'.wholesalex-single-group-clone',
		function ( e ) {
			e.preventDefault();
			$( '.wholesalex-single-repeated-field' ).append(
				$( '.wholesalex-single-repeated-clone-hidden' ).html()
			);
		}
	);
	$( document ).on( 'click', '.wholesalex-single-remove', function ( e ) {
		e.preventDefault();
		$( this ).closest( '.wholesalex-repetable-field' ).remove();
	} );

	// Search Users
	$( document ).on( 'paste keyup', '.wsx-search-box', function ( e ) {
		e.preventDefault();

		const searchBoxValue = $( '.wsx-search-box' ).val().trim();
		const searchResult = $( '.wsx-search-result' );

		if ( searchBoxValue ) {
			searchResult.removeClass( 'wsx-d-none' ).addClass( 'wsx-d-block' );
			searchResult.html( '<li>Searching...</li>' );
		} else {
			searchResult.removeClass( 'wsx-d-block' ).addClass( 'wsx-d-none' );
			searchResult.html( '' );
		}

		$.ajax( {
			url: wholesalex.ajax,
			type: 'POST',
			data: {
				action: 'wsx_users',
				term: $( '.wsx-search-box' ).val(),
				wpnonce: wholesalex.nonce,
			},
			success( res ) {
				if ( res.data && res.data.trim() !== '' ) {
					searchResult.html( res.data );
				} else {
					searchResult.html( '<li>No matches found</li>' );
				}
			},
			error() {
				// const errorMessage = `Error occurred: ${xhr.statusText || 'Unknown error'} - ${xhr.responseText || ''}`;
				// searchResult.html(`<li>${errorMessage}</li>`);
				// console.log( errorMessage );
			},
		} );
	} );
	$( document ).on( 'click', 'ul.wsx-search-result li', function ( e ) {
		e.preventDefault();

		$( '.wsx-search-content' )
			.removeClass( 'wsx-d-none' )
			.addClass( 'wsx-d-block' );
		$( 'input[name="started_by"]' ).val( $( this ).data( 'id' ) );
		$( '.wsx-search-box' ).val( '' );
		$( '.wsx-search-result' ).html( '' );
		$( '.wsx-search-image' )
			.attr( 'src', $( this ).data( 'image' ) )
			.show();
		$( '.wsx-search-name' ).text( $( this ).data( 'name' ) );
		$( '.wsx-search-email' ).text( $( this ).data( 'email' ) );
		$( '.wsx-search-result' )
			.removeClass( 'wsx-d-block' )
			.addClass( 'wsx-d-none' );
	} );

	// Addons Enable Option
	$( document ).on( 'click', '.wsx-addons-enable', function () {
		const that = this;
		const addonName = $( that ).attr( 'id' );
		$.ajax( {
			url: wholesalex.ajax,
			type: 'POST',
			data: {
				action: 'wsx_addon',
				addon: addonName,
				value: that.checked ? 'yes' : '',
				wpnonce: wholesalex.nonce,
			},
			success( data ) {
				if ( data.success ) {
					location.reload();
				} else {
					let msg = '';
					msg =
						'<div class="notice notice-error is-dismissible whx_addon_notice"><p><strong>ERROR: </strong>' +
						data.data +
						'.</p><button type="button" class="notice-dismiss" onclick="javascript: return jQuery(this).parent().remove();"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
					jQuery(
						'.wholesalex-editor__row.wholesalex-editor__heading'
					).before( msg );
					document.getElementById( addonName ).checked = false;
				}
			},
			error() {
				// console.log(
				// 	'Error occured.please try again' +
				// 		xhr.statusText +
				// 		xhr.responseText
				// );
			},
		} );
	} );

	// Email Enable Option
	$( document ).on( 'click', '.wsx-email-enable', function () {
		const that = this;
		const emailName = $( that ).attr( 'id' );
		$.ajax( {
			url: wholesalex.ajax,
			type: 'POST',
			data: {
				action: 'save_wholesalex_email_settings',
				id: emailName,
				value: that.checked,
				nonce: wholesalex.nonce,
			},
			success( data ) {
				if ( data.success ) {
					location.reload();
				} else {
					document.getElementById( emailName ).checked = false;
				}
			},
			error() {
				// console.log(
				// 	'Error occured.please try again' +
				// 		xhr.statusText +
				// 		xhr.responseText
				// );
			},
		} );
	} );

	$( document ).on( 'click', '.wholesalex_rule_on_more', function ( e ) {
		jQuery( '.wholesalex_rule_modal' ).css( 'display', 'none' );
		const element = '.wholesalex_rule_modal.' + e.target.id;
		jQuery( element ).css( 'display', 'block' );
	} );
	$( document ).on( 'click', '.modal-close-btn', function () {
		jQuery( '.wholesalex_rule_modal' ).css( 'display', 'none' );
	} );

	const count = document.querySelectorAll( '#quote_accept_button' ).length;
	if ( count ) {
		document
			.querySelectorAll( '#quote_accept_button' )
			.forEach( ( element ) => {
				element.remove();
			} );
	}

	// if(document.body.contains(document.getElementById("wholesalex_initial_setup_wizard"))) {
	//     $(".admin_page_wholesalex-setup-wizard #wpadminbar").remove();
	//     $(".admin_page_wholesalex-setup-wizard #adminmenumain").remove();
	// }

	const wholesalexEmailTemplates = [
		'wholesalex_new_user_approval_required',
		'wholesalex_new_user_approved',
		'wholesalex_new_user_auto_approve',
		'wholesalex_new_user_email_verified',
		'wholesalex_registration_pending',
		'wholesalex_new_user_registered',
		'wholesalex_registration_rejected',
		'wholesalex_new_user_email_verification',
		'wholesalex_raq_make_offer',
		'wholesalex_raq_expiring_offer',
		'wholesalex_subaccount_create',
		'wholesalex_subaccount_order_approval_require',
		'wholesalex_subaccount_order_pending',
		'wholesalex_subaccount_order_placed',
		'wholesalex_subaccount_order_reject',
	];
	// In admin emails, modify email path for theme folder.
	wholesalexEmailTemplates.forEach( ( element ) => {
		if ( $( `#woocommerce_${ element }_enabled` ).val() !== undefined ) {
			const text = $( '.template_html' ).html();
			const newtext = text.replace( '/woocommerce/', '/' );
			$( '.template_html' ).html( newtext );
			$( '.template_html p a:nth-child(2)' ).remove();
			$( '.template_html a' ).remove();
		}
	} );

	// Move notice into after heading
	//  $(document).ready( function() {
	//     const noticeWrapper = $('.wsx-notice-wrapper');
	//     if ( noticeWrapper.length > 0  ) {
	//         setTimeout( function() {
	//             noticeWrapper.each(function(e){
	//                 const notice = $(this);
	//                 if($('#wpwrap .wrap .wp-header-end').length>0) {
	//                     $('#wpwrap .wrap .wp-header-end').after(notice);
	//                 } else {
	//                     $('#wpwrap .wrap h1').after(notice);
	//                 }
	//             });
	//         }, 100);
	//     }
	// });

	$( 'a[href="admin.php?page=go_wholesalex_pro"]' ).each( function () {
		$( this ).attr( 'target', '_blank' );
	} );
} )( jQuery );

// const openWholesaleXGetProPopUp = () => {
// 	const proPopup = document.getElementById( 'wholesalex-pro-popup' );
// 	if ( proPopup ) {
// 		proPopup.style.display = 'flex';
// 	}
// };

// const closeWholesaleXGetProPopUp = () => {
// 	const proPopup = document.getElementById( 'wholesalex-pro-popup' );
// 	const closeButton = document.getElementById( 'wholesalex-close-pro-popup' );

// 	if ( proPopup && closeButton ) {
// 		closeButton.onclick = function ( event ) {
// 			event.preventDefault();
// 			proPopup.style.display = 'none';
// 		};
// 	}
// };

// Add active class to the current menu item
jQuery( document ).ready( function ( $ ) {
	// Add custom class to the top-level menu
	// $('#toplevel_page_wholesalex > ul').addClass('wsx-top-level-menu');
	$( '#toplevel_page_wholesalex > ul > li' ).removeClass( 'current' );
} );

function updateActiveLink() {
	const currentUrl = window.location.href;
	const menuItems = document.querySelectorAll( '.wp-submenu a' );

	menuItems.forEach( ( menuItem ) => {
		if ( menuItem.href === currentUrl ) {
			menuItem.style.fontWeight = '500';
			menuItem.style.color = '#ffffff';
			menuItem.style.boxShadow = 'inset 4px 0 0 0 currentColor';
			// menuItem.classList.add('wsx-font-bold', 'wsx-color-text-reverse');
		} else {
			menuItem.style.fontWeight = '';
			menuItem.style.color = '';
			menuItem.style.boxShadow = '';
			// menuItem.classList.remove('wsx-font-bold', 'wsx-color-text-reverse');
		}
	} );
}

document.addEventListener( 'DOMContentLoaded', () => {
	updateActiveLink();

	// Listen for hash changes (when the URL changes)
	window.addEventListener( 'hashchange', updateActiveLink );

	// Listen for popstate events (when navigating back/forward)
	window.addEventListener( 'popstate', updateActiveLink );
} );
