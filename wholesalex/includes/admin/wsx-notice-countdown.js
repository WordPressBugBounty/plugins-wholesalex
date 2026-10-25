/**
 * Countdown for WholesaleX banner notices. Uses the campaign’s fixed end timestamp across all page loads.
 */
jQuery( function ( $ ) {
	'use strict';

	const formatCountdown = function ( seconds ) {
		const days = Math.floor( seconds / 86400 );
		const hours = Math.floor( ( seconds % 86400 ) / 3600 );
		const minutes = Math.floor( ( seconds % 3600 ) / 60 );
		const secs = seconds % 60;

		return (
			String( days ).padStart( 2, '0' ) +
			':' +
			String( hours ).padStart( 2, '0' ) +
			':' +
			String( minutes ).padStart( 2, '0' ) +
			':' +
			String( secs ).padStart( 2, '0' )
		);
	};

	$( '.wsx-notice-countdown' ).each( function () {
		const element = $( this );
		const endAt = Number( element.data( 'endAt' ) );
		if ( ! Number.isFinite( endAt ) || endAt <= 0 ) {
			return;
		}
		const tick = function () {
			const remaining = Math.max(
				endAt - Math.floor( Date.now() / 1000 ),
				0
			);
			element.text( formatCountdown( remaining ) );
		};
		tick();
		window.setInterval( tick, 1000 );
	} );
} );
