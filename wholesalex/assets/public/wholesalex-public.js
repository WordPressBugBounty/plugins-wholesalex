/* eslint-disable no-undef */
/* eslint-disable camelcase */
( function ( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 */

	// Conversation Toggle
	$( document ).on( 'click', '.wsx-new-conversation', function ( e ) {
		e.preventDefault();
		if ( $( this ).hasClass( 'active' ) ) {
			$( this )
				.parent()
				.siblings( '.wsx-new-conversation-container' )
				.slideUp();
			$( this ).removeClass( 'active' );
		} else {
			$( '.wsx-new-conversation-container' ).slideUp();
			$( '.wsx-new-conversation' ).removeClass( 'active' );
			$( this )
				.parent()
				.siblings( '.wsx-new-conversation-container' )
				.slideToggle();
			$( this ).toggleClass( 'active' );
		}
	} );

	//remove wholesalex header from frontend menu pages
	//wholesalex dynamic rules in dokan menu pages
	$( '#_wholesalex_dynamic_rules>.wholesalex_header_wrapper' ).remove();

	jQuery( document ).ready( function () {
		//For Block base Theme in Shop Page
		$( '.wholesalex-bogo-badge-container' ).each( function () {
			const bogoBadgeMarkup = $( this );
			const blockThemeClass = bogoBadgeMarkup
				.closest( 'li' )
				.find( '.wc-block-components-product-image' );
			if ( blockThemeClass.length > 0 ) {
				blockThemeClass.prepend( bogoBadgeMarkup );
			}
		} );

		//Bogo Badge in Single Page
		if ( typeof wholesalex_bogo_single !== 'undefined' ) {
			const { buy_x_get_one, buy_x_get_y } =
				wholesalex_bogo_single.content;
			let markupToPrepend = '';

			if ( buy_x_get_one ) {
				markupToPrepend += buy_x_get_one;
			}

			if ( buy_x_get_y ) {
				markupToPrepend += buy_x_get_y;
			}

			// If both are present, wrap in a <div>, otherwise add them directly
			if ( markupToPrepend ) {
				const prependMarkup =
					buy_x_get_one && buy_x_get_y
						? `<div class="wholesalex-bogo-badge-container wsx-combined-bogo-badge">${ markupToPrepend }</div>`
						: markupToPrepend;

				$( '.woocommerce-product-gallery' ).prepend( prependMarkup );
			}
		}
	} );

	//Product Variation Specific Step Control
	$( 'body' ).on(
		'show_variation',
		'.single_variation_wrap',
		function ( event, variation ) {
			const $quantityInput = $( this ).parent().find( '[name=quantity]' );
			const step = variation.step || 1; // Default step to 1 if not provided
			const maxQty = parseFloat( variation.max_qty ) || 999999999; // Default max_qty to a large number
			const minQty = parseFloat( variation.min_qty ) || 1; // Default min_qty to 0

			// Set step and trigger change event
			$quantityInput.attr( 'step', step ).trigger( 'change' );

			// Parse and adjust current quantity value
			let currentQty = minQty ?? parseFloat( $quantityInput.val() );
			currentQty = Math.max( minQty, Math.min( currentQty, maxQty ) ); // Ensure within min and max range

			// Update input attributes and value
			$quantityInput
				.val( currentQty )
				.attr( 'min', minQty )
				.attr( 'max', maxQty )
				.trigger( 'change' );
		}
	);
} )( jQuery );

jQuery( document ).ready( function ( $ ) {
	const warningSelector = '.wc-block-components-notice-banner__content'; // Adjust selector if needed
	const checkoutButtonSelector = '.wc-block-cart__submit-button';

	function toggleCheckoutButton() {
		if ( $( warningSelector ).length > 0 ) {
			$( checkoutButtonSelector ).hide();
		} else {
			$( checkoutButtonSelector ).show();
		}
	}

	toggleCheckoutButton(); // Initial check

	// Observe for dynamic changes
	const observer = new MutationObserver( toggleCheckoutButton );
	observer.observe( document.body, { childList: true, subtree: true } );
} );
