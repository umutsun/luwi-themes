/* LuwiPress Sapphire — wizard activation notice (dismiss handler). */
(function ($) {
	'use strict';
	$(document).on('click', '[data-lwp-wizard-dismiss]', function (e) {
		e.preventDefault();
		var $notice = $(this).closest('.luwipress-sapphire-notice');
		$.post(LWP_SAPPHIRE_WIZARD.ajaxurl, {
			action:    'luwipress_sapphire_wizard_dismiss',
			_ajax_nonce: LWP_SAPPHIRE_WIZARD.nonce
		}).always(function () {
			$notice.fadeOut(180, function () { $notice.remove(); });
		});
	});

	// Native dismiss button (the × icon).
	$(document).on('click', '.luwipress-sapphire-notice .notice-dismiss', function () {
		$.post(LWP_SAPPHIRE_WIZARD.ajaxurl, {
			action:    'luwipress_sapphire_wizard_dismiss',
			_ajax_nonce: LWP_SAPPHIRE_WIZARD.nonce
		});
	});
})(jQuery);
