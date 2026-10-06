/**
 * Countdown for WholesaleX banner notices. Keeps each timer in localStorage so it survives page loads,
 * and restarts it when it runs out.
 */
jQuery( function ( $ ) {
	'use strict';

	const storagePrefix = 'wsx_notice_countdown_';

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

	const parseDurationToSeconds = function ( duration ) {
		if (
			typeof duration === 'number' &&
			Number.isFinite( duration ) &&
			duration > 0
		) {
			return Math.floor( duration );
		}

		const durationString = String( duration || '' ).trim();
		if ( /^\d+$/.test( durationString ) ) {
			return parseInt( durationString, 10 );
		}

		return 0;
	};

	const nowInSeconds = function () {
		return Math.floor( Date.now() / 1000 );
	};

	$( '.wsx-notice-countdown' ).each( function () {
		const countdownElement = $( this );
		const noticeKey = String( countdownElement.data( 'noticeKey' ) || '' );
		const duration = parseDurationToSeconds(
			countdownElement.data( 'duration' )
		);

		if ( ! noticeKey || duration <= 0 ) {
			return;
		}

		const storageKey = storagePrefix + noticeKey;
		let endAt = 0;

		try {
			const storedDataRaw = window.localStorage.getItem( storageKey );
			if ( storedDataRaw ) {
				const storedData = JSON.parse( storedDataRaw );
				if (
					storedData &&
					parseInt( storedData.duration, 10 ) === duration
				) {
					endAt = parseInt( storedData.endAt, 10 ) || 0;
				}
			}
		} catch ( error ) {
			endAt = 0;
		}

		const saveTimerState = function ( nextEndAt ) {
			try {
				window.localStorage.setItem(
					storageKey,
					JSON.stringify( {
						endAt: nextEndAt,
						duration,
					} )
				);
			} catch ( error ) {
				// No-op.
			}
		};

		const resetTimer = function ( currentTime ) {
			endAt = currentTime + duration;
			saveTimerState( endAt );
		};

		const tick = function () {
			const currentTime = nowInSeconds();

			if ( endAt <= currentTime ) {
				resetTimer( currentTime );
			}

			const remaining = Math.max( endAt - currentTime, 0 );
			countdownElement.text( formatCountdown( remaining ) );
		};

		if ( endAt <= nowInSeconds() ) {
			resetTimer( nowInSeconds() );
		}

		tick();
		window.setInterval( tick, 1000 );
	} );
} );
