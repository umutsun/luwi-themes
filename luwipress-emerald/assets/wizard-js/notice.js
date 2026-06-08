/* LuwiPress Emerald — wizard activation notice (dismiss handler). */
(function ($) {
	'use strict';
	$(document).on('click', '[data-lwp-wizard-dismiss]', function (e) {
		e.preventDefault();
		var $notice = $(this).closest('.luwipress-emerald-notice');
		$.post(LWP_EMERALD_WIZARD.ajaxurl, {
			action:    'luwipress_emerald_wizard_dismiss',
			_ajax_nonce: LWP_EMERALD_WIZARD.nonce
		}).always(function () {
			$notice.fadeOut(180, function () { $notice.remove(); });
		});
	});

	// Native dismiss button (the × icon).
	$(document).on('click', '.luwipress-emerald-notice .notice-dismiss', function () {
		$.post(LWP_EMERALD_WIZARD.ajaxurl, {
			action:    'luwipress_emerald_wizard_dismiss',
			_ajax_nonce: LWP_EMERALD_WIZARD.nonce
		});
	});
})(jQuery);
