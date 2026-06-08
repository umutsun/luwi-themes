/* LuwiPress Amber — wizard activation notice (dismiss handler). */
(function ($) {
	'use strict';
	$(document).on('click', '[data-lwp-wizard-dismiss]', function (e) {
		e.preventDefault();
		var $notice = $(this).closest('.luwipress-amber-notice');
		$.post(LWP_AMBER_WIZARD.ajaxurl, {
			action:    'luwipress_amber_wizard_dismiss',
			_ajax_nonce: LWP_AMBER_WIZARD.nonce
		}).always(function () {
			$notice.fadeOut(180, function () { $notice.remove(); });
		});
	});

	// Native dismiss button (the × icon).
	$(document).on('click', '.luwipress-amber-notice .notice-dismiss', function () {
		$.post(LWP_AMBER_WIZARD.ajaxurl, {
			action:    'luwipress_amber_wizard_dismiss',
			_ajax_nonce: LWP_AMBER_WIZARD.nonce
		});
	});
})(jQuery);
