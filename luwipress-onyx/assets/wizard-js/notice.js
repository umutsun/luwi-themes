/* LuwiPress Onyx — wizard activation notice (dismiss handler). */
(function ($) {
	'use strict';
	$(document).on('click', '[data-lwp-wizard-dismiss]', function (e) {
		e.preventDefault();
		var $notice = $(this).closest('.luwipress-onyx-notice');
		$.post(LWP_ONYX_WIZARD.ajaxurl, {
			action:    'luwipress_onyx_wizard_dismiss',
			_ajax_nonce: LWP_ONYX_WIZARD.nonce
		}).always(function () {
			$notice.fadeOut(180, function () { $notice.remove(); });
		});
	});

	// Native dismiss button (the × icon).
	$(document).on('click', '.luwipress-onyx-notice .notice-dismiss', function () {
		$.post(LWP_ONYX_WIZARD.ajaxurl, {
			action:    'luwipress_onyx_wizard_dismiss',
			_ajax_nonce: LWP_ONYX_WIZARD.nonce
		});
	});
})(jQuery);
