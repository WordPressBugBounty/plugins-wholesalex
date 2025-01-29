(function ($) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 */



	// Conversation Toggle
	$(document).on('click', '.wsx-new-conversation', function(e) {
		e.preventDefault();
        if ($(this).hasClass('active')) {
			$(this).parent().siblings('.wsx-new-conversation-container').slideUp();
			$(this).removeClass('active');
        } else {
			$('.wsx-new-conversation-container').slideUp();
			$('.wsx-new-conversation').removeClass('active');
			$(this).parent().siblings('.wsx-new-conversation-container').slideToggle();
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
			const { buy_x_get_one, buy_x_get_y } = wholesalex_bogo_single.content;
			let markupToPrepend = '';
		
			if (buy_x_get_one) {
				markupToPrepend += buy_x_get_one;
			}
		
			if (buy_x_get_y) {
				markupToPrepend += buy_x_get_y;
			}
		
			// If both are present, wrap in a <div>, otherwise add them directly
			if (markupToPrepend) {
				const prependMarkup = (buy_x_get_one && buy_x_get_y) 
					? `<div class="wsx-combined-bogo-badge">${markupToPrepend}</div>` 
					: markupToPrepend;
		
				$('.woocommerce-product-gallery').prepend(prependMarkup);
			}
		}
		

	});

	//Product Variation Specific Step Control
	$("body").on("show_variation", ".single_variation_wrap", function (event, variation) {
		let $quantityInput = $(this).parent().find("[name=quantity]");
		let step = variation.step || 1; // Default step to 1 if not provided
		let maxQty = parseFloat(variation.max_qty) || 999999999; // Default max_qty to a large number
		let minQty = parseFloat(variation.min_qty) || 0; // Default min_qty to 0
	
		// Set step and trigger change event
		$quantityInput.attr("step", step).trigger("change");
	
		// Parse and adjust current quantity value
		let currentQty = parseFloat($quantityInput.val()) || minQty;
		currentQty = Math.max(minQty, Math.min(minQty, maxQty)); // Ensure within min and max range
	
		// Adjust quantity if step is defined
		if (step) {
			let remainder = currentQty % step;
			if (remainder !== 0) {
				let increaseBy = step - remainder;
				let decreaseBy = remainder;
				currentQty = currentQty + increaseBy <= maxQty ? currentQty + increaseBy : currentQty - decreaseBy;
			}
		}
	
		// Update input attributes and value
		$quantityInput
			.val(currentQty)
			.attr("min", minQty)
			.attr("max", maxQty)
			.trigger("change");
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