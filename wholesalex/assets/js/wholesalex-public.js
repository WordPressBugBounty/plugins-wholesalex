(function ($) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 */



	// Conversation Toggle
	$(document).on('click', '.wsx-title', function(e) {
		e.preventDefault();
        if ($(this).hasClass('active')) {
			$(this).parent().siblings('.wsx-content').slideUp();
			$(this).removeClass('active');
        } else {
			$('.wsx-content').slideUp();
			$('.wsx-title').removeClass('active');
			$(this).parent().siblings('.wsx-content').slideToggle();
			$(this).toggleClass('active');
        }
    });

	//remove wholesalex header from frontend menu pages
	//wholesalex dynamic rules in dokan menu pages
	$("#_wholesalex_dynamic_rules>.wholesalex_header_wrapper").remove();

	const controlRegistrationForm = ()=>{
		// Check User Role Selection Field
		let selectedRole = $('#wholesalex_registration_role').val();
		if(selectedRole) {
			let whxCustomFields = $('.wsx-field');
			whxCustomFields.each(function (i) {
				let excludeRoles = this.getAttribute('data-wsx-exclude');
				if(excludeRoles) {
					excludeRoles = excludeRoles.split(' ');
					if(!excludeRoles.includes(selectedRole)) {
						$(this).show();
						$(this).find('.wsx-field-required').prop('required','true');
					} else {
						$(this).hide();
						$(this).find('.wsx-field-required').removeAttr('required');
					}
				}

			});
		} else {
			$(".wsx-field[style*='display: none'] > .wsx-field-required").removeAttr("required");
		}
	}
	controlRegistrationForm();
	$('#wholesalex_registration_role').change(controlRegistrationForm);

	jQuery(document).ready(function($) {
		//For Block base Theme in Shop Page
		$('.wholesalex-bogo-badge-container').each(function() {
			let bogoBadgeMarkup = $(this);
			const blockThemeClass = bogoBadgeMarkup.closest('li').find('.wc-block-components-product-image');
			if ( blockThemeClass.length > 0 ) {
				blockThemeClass.prepend(bogoBadgeMarkup);
			}
		});

		//Bogo Badge in Single Page
		if ( typeof wholesalex_bogo_single !== 'undefined' ) {
			if (wholesalex_bogo_single.content.buy_x_get_one) {
				$('.woocommerce-product-gallery').prepend(wholesalex_bogo_single.content.buy_x_get_one);
			}
			if (wholesalex_bogo_single.content.buy_x_get_y) {
				$('.woocommerce-product-gallery').prepend(wholesalex_bogo_single.content.buy_x_get_y);
			}
		}

	});
	
})(jQuery);


const conversationOpen = () => {
	const conversationID = event.target.getAttribute('data-conv-id');
	const nonce = event.target.getAttribute('data-security');
	const formData = new FormData();
	formData.append('action', 'conversation_status_change');
	formData.append('conversationID', conversationID);
	formData.append('nonce', nonce);

	fetch(
		wholesalex.ajax, {
		method: 'POST',
		body: formData,
	})
	.then(res => res.json())
	.then(res => {
	})
}