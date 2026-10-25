( function () {
	'use strict';
	if ( window.wholesalexRecaptchaLoginBound ) {
		return;
	}
	window.wholesalexRecaptchaLoginBound = true;

	document.addEventListener(
		'submit',
		function ( event ) {
			const form = event.target;
			if ( ! form.matches( 'form.woocommerce-form-login' ) ) {
				return;
			}
			const config = window.wholesalexRecaptchaLogin;
			if ( ! config || ! config.siteKey ) {
				return;
			}
			event.preventDefault();
			event.stopImmediatePropagation();
			if ( form.dataset.recaptchaPending === 'yes' ) {
				return;
			}
			form.dataset.recaptchaPending = 'yes';
			const notice = form.querySelector( '.wholesalex-recaptcha-error' );
			if ( notice ) {
				notice.remove();
			}
			// A previous token must never survive a failed retry.
			form.querySelectorAll( 'input[name="token"]' ).forEach(
				function ( input ) {
					input.remove();
				}
			);

			function setField( name, value ) {
				let input = form.querySelector( 'input[name="' + name + '"]' );
				if ( ! input ) {
					input = document.createElement( 'input' );
					input.type = 'hidden';
					input.name = name;
					form.appendChild( input );
				}
				input.value = value;
			}

			// Also cover a delayed API, a blocked request, and ready/execute never resolving.
			new Promise( function ( resolve, reject ) {
				let finished = false;
				let poll;
				const timeout = setTimeout( function () {
					finish( new Error( 'reCAPTCHA timed out' ) );
				}, 15000 );
				function finish( error, token ) {
					if ( finished ) {
						return;
					}
					finished = true;
					clearTimeout( timeout );
					clearTimeout( poll );
					if ( error ) {
						reject( error );
					} else {
						resolve( token );
					}
				}
				function waitForApi() {
					const api = window.grecaptcha;
					if (
						! api ||
						typeof api.ready !== 'function' ||
						typeof api.execute !== 'function'
					) {
						poll = setTimeout( waitForApi, 100 );
						return;
					}
					try {
						api.ready( function () {
							if ( finished ) {
								return;
							}
							try {
								Promise.resolve(
									api.execute( config.siteKey, {
										action: 'submit',
									} )
								).then(
									function ( token ) {
										if (
											typeof token !== 'string' ||
											! token.trim()
										) {
											finish(
												new Error(
													'Empty reCAPTCHA token'
												)
											);
											return;
										}
										finish( null, token );
									},
									function ( error ) {
										finish(
											error ||
												new Error( 'reCAPTCHA failed' )
										);
									}
								);
							} catch ( error ) {
								finish( error );
							}
						} );
					} catch ( error ) {
						finish( error );
					}
				}
				waitForApi();
			} )
				.then( function ( token ) {
					setField( 'token', token );
					setField( 'login', 'Login' );
					// Native submit avoids recursion and form controls shadowing form.submit.
					window.HTMLFormElement.prototype.submit.call( form );
				} )
				.catch( function () {
					const error = document.createElement( 'div' );
					error.className =
						'woocommerce-error wholesalex-recaptcha-error';
					error.setAttribute( 'role', 'alert' );
					error.textContent = config.errorMessage;
					form.prepend( error );
				} )
				.finally( function () {
					delete form.dataset.recaptchaPending;
				} );
		},
		true
	);
} )();
