( function ( $ ) {
	$( document ).ready( function () {
		const $quantityInput = $( '[name=quantity]' );
		const quantityPrices = wholesalexPriceTableData.quantityPrices || [];
		const cartQuantity = wholesalexPriceTableData.cartQuantity || 0;

		function updatePricing() {
			const quantity =
				cartQuantity + parseInt( $quantityInput.val() ) || 1;
			let matchedTier = null;
			let matchedMin = 0;

			quantityPrices.forEach( ( tier ) => {
				const minQty = parseInt( tier[ '_min_quantity' ] );
				if ( quantity >= minQty && minQty >= matchedMin ) {
					matchedTier = minQty;
					matchedMin = minQty;
				}
			} );

			$( '.wsx-price-table-row' ).removeClass( 'active' );
			if ( matchedTier ) {
				$(
					'.wsx-price-table-row[data-min="' + matchedTier + '"]'
				).addClass( 'active' );
			}
		}

		// Listen to native input events
		if ( $quantityInput.length ) {
			$quantityInput
				.on( 'input change', updatePricing )
				.trigger( 'change' );
		}

		// Compatibility for WowStore
		$( '.wopb-builder-cart-minus' ).on( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();

			const currentVal = parseInt( $quantityInput.val() ) || 1;
			if ( currentVal > 1 ) {
				$quantityInput.val( currentVal - 1 ).trigger( 'change' );
			}
		} );

		// WowStore Plus button
		$( '.wopb-builder-cart-plus' ).on( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();

			const currentVal = parseInt( $quantityInput.val() ) || 1;
			$quantityInput.val( currentVal + 1 ).trigger( 'change' );
		} );
	} );
} )( jQuery );
