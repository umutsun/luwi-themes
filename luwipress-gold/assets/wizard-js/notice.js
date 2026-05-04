/* LuwiPress Gold — wizard activation notice (dismiss handler). */
(function ($) {
	'use strict';
	$(document).on('click', '[data-lwp-wizard-dismiss]', function (e) {
		e.preventDefault();
		var $notice = $(this).closest('.luwipress-gold-notice');
		$.post(LWP_GOLD_WIZARD.ajaxurl, {
			action:    'luwipress_gold_wizard_dismiss',
			_ajax_nonce: LWP_GOLD_WIZARD.nonce
		}).always(function () {
			$notice.fadeOut(180, function () { $notice.remove(); });
		});
	});

	// Native dismiss button (the × icon).
	$(document).on('click', '.luwipress-gold-notice .notice-dismiss', function () {
		$.post(LWP_GOLD_WIZARD.ajaxurl, {
			action:    'luwipress_gold_wizard_dismiss',
			_ajax_nonce: LWP_GOLD_WIZARD.nonce
		});
	});
})(jQuery);
