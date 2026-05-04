/* LuwiPress Gold — Customizer live preview.
 *
 * Listens for postMessage transport on the 8 brand color settings and
 * swaps the matching CSS custom property in :root without reloading.
 * Everything else uses the default `refresh` transport.
 */
(function ($) {
	'use strict';

	function setToken(name, value) {
		document.documentElement.style.setProperty('--' + name, value);
	}

	var bindings = {
		luwipress_gold_color_primary:       'primary',
		luwipress_gold_color_primary_light: 'primary-light',
		luwipress_gold_color_accent:        'accent',
		luwipress_gold_color_sale:          'sale',
		luwipress_gold_color_icon_red:      'icon-red',
		luwipress_gold_color_ink:           'ink',
		luwipress_gold_color_bg:            'bg',
		luwipress_gold_color_black:         'black'
	};

	Object.keys(bindings).forEach(function (settingId) {
		wp.customize(settingId, function (value) {
			value.bind(function (next) {
				if (!next) return;
				if (!/^#[0-9a-fA-F]{3,8}$/.test(next)) return;
				setToken(bindings[settingId], next);
			});
		});
	});
})(jQuery);
