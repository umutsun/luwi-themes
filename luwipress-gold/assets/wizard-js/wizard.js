/* LuwiPress Gold — onboarding wizard front-end. */
(function ($) {
	'use strict';

	var ajaxurl  = LWP_GOLD_WIZARD.ajaxurl;
	var nonce    = LWP_GOLD_WIZARD.nonce;
	var i18n     = (LWP_GOLD_WIZARD_DATA && LWP_GOLD_WIZARD_DATA.i18n) || {};

	var $root  = $('.luwipress-gold-wizard');
	if (!$root.length) return;

	var snapshot = {};
	try {
		snapshot = JSON.parse($root.attr('data-snapshot') || '{}');
	} catch (e) {}

	var brand = {};

	/* ─── Step navigation ─── */
	function goTo(step) {
		step = parseInt(step, 10) || 1;
		$root.find('.lwp-wizard-panel').removeClass('is-active');
		$root.find('.lwp-wizard-panel[data-panel="' + step + '"]').addClass('is-active');

		$root.find('.lwp-wizard-steps li').each(function () {
			var idx = parseInt($(this).data('step'), 10);
			$(this).toggleClass('is-active', idx === step);
			$(this).toggleClass('is-done', idx < step);
		});

		// Step 4 → re-fetch plugin status fresh.
		if (step === 4) refreshPluginStatus();

		// Smooth scroll to top of panel.
		$('html,body').animate({ scrollTop: $root.offset().top - 32 }, 250);
	}

	$root.on('click', '.lwp-next', function () {
		goTo($(this).data('target'));
	});
	$root.on('click', '.lwp-back', function () {
		goTo($(this).data('target'));
	});

	/* ─── Step 3 — collect brand fields ─── */
	$root.on('input change', '[data-brand]', function () {
		var key = $(this).data('brand');
		var val = $(this).val();
		brand[key] = val;
	});

	/* ─── Step 3 — AI toggle + generate ─── */
	var $aiSlots   = $root.find('#lwp-ai-slots');
	var $aiActions = $root.find('#lwp-ai-actions');
	var $aiMsg     = $root.find('#lwp-ai-msg');

	$root.on('change', '#lwp-ai-toggle', function () {
		var on = !!this.checked;
		$aiSlots.prop('hidden', !on);
		$aiActions.prop('hidden', !on);
		$.post(ajaxurl, {
			action: 'luwipress_gold_wizard_step',
			step: 'ai_toggle',
			_ajax_nonce: nonce,
			payload: JSON.stringify({ enable: on })
		}).done(function () {
			$aiMsg.text(on
				? 'AI generation enabled. Click "Generate now" to draft your copy.'
				: 'AI generation disabled, cache cleared.');
		});
	});
	if ($('#lwp-ai-toggle').is(':checked')) {
		$aiSlots.prop('hidden', false);
		$aiActions.prop('hidden', false);
	}

	$root.on('click', '#lwp-ai-generate', function () {
		var $btn = $(this);
		$btn.prop('disabled', true).text('Generating…');
		$aiMsg.text('Sending prompts to LuwiPress AI…');
		$root.find('.lwp-ai-slot-status').text('…');

		$.post(ajaxurl, {
			action: 'luwipress_gold_wizard_step',
			step: 'ai_generate',
			_ajax_nonce: nonce
		}).done(function (resp) {
			$btn.prop('disabled', false).text('Generate again');
			if (!resp || !resp.success) {
				$aiMsg.text((resp && resp.data && resp.data.message) || 'AI generation failed.');
				return;
			}
			$aiMsg.text('Done — you can also re-run after editing the brand fields above.');
			var results = resp.data.results || {};
			Object.keys(results).forEach(function (slot) {
				var entry = results[slot];
				var $slot = $root.find('.lwp-ai-slot[data-slot="' + slot + '"]');
				$slot.find('[data-status]').text(entry.is_default ? 'fallback' : 'AI');
				var preview = (entry.text || '').slice(0, 220);
				$slot.find('[data-text]').html('<em>' + escapeHtml(preview) + (entry.text && entry.text.length > 220 ? '…' : '') + '</em>');
			});
		}).fail(function () {
			$btn.prop('disabled', false).text('Generate now');
			$aiMsg.text('Network error.');
		});
	});

	$root.on('click', '#lwp-ai-flush', function () {
		$aiMsg.text('Clearing cache…');
		$.post(ajaxurl, {
			action: 'luwipress_gold_wizard_step',
			step: 'ai_flush',
			_ajax_nonce: nonce
		}).done(function () {
			$aiMsg.text('Cache cleared. Next page render will regenerate.');
			$root.find('.lwp-ai-slot-status').text('—');
		});
	});

	/* ─── Step 4 — re-check plugin status ─── */
	$root.on('click', '#lwp-recheck', function () {
		refreshPluginStatus();
	});

	function refreshPluginStatus() {
		$.post(ajaxurl, {
			action:      'luwipress_gold_wizard_step',
			step:        'plugin_status',
			_ajax_nonce: nonce
		}).done(function (resp) {
			if (!resp || !resp.success) return;
			var plugins = resp.data.plugins || {};
			Object.keys(plugins).forEach(function (key) {
				var $row = $root.find('.lwp-plugin[data-plugin="' + key + '"]');
				if (!$row.length) return;
				var p = plugins[key];
				$row.removeClass('is-active is-installed is-missing');
				if (p.active) {
					$row.addClass('is-active');
					$row.find('.lwp-plugin-status').html('<span class="lwp-status lwp-status--ok">✅ Active</span>');
				} else if (p.installed) {
					$row.addClass('is-installed');
					$row.find('.lwp-plugin-status').html('<a href="' + p.activate_url + '" class="button button-secondary">Activate</a>');
				} else {
					$row.addClass('is-missing');
					$row.find('.lwp-plugin-status').html('<a href="' + p.install_url + '" class="button button-primary" target="_blank">Install</a>');
				}
			});
		});
	}

	/* ─── Step 5 — apply ─── */
	$root.on('click', '#lwp-apply-run', function () {
		var $btn = $(this);
		$btn.prop('disabled', true).text(i18n.importing || 'Importing…');

		var path = $root.find('input[name="lwp_path"]:checked').val() || 'use_existing';

		// Slug-conflict resolution opt-in. Checkbox renders only when
		// detector found at least one collision; treat absence as off.
		var $resolveCb = $root.find('#lwp-resolve-conflicts');
		var resolveSlugConflicts = $resolveCb.length > 0 ? !!$resolveCb.prop('checked') : false;

		setProgress(8);
		log(i18n.detecting || 'Scanning…', 'info');

		$.post(ajaxurl, {
			action:      'luwipress_gold_wizard_step',
			step:        'apply',
			_ajax_nonce: nonce,
			payload:     JSON.stringify({
				path: path,
				brand: brand,
				resolve_slug_conflicts: resolveSlugConflicts
			})
		}).done(function (resp) {
			if (!resp || !resp.success) {
				log((resp && resp.data && resp.data.message) || (i18n.errored || 'Error'), 'err');
				setProgress(100);
				$btn.prop('disabled', false).text('Retry');
				return;
			}
			renderApplyLog(resp.data);
			setProgress(100);

			// Mark wizard as completed.
			$.post(ajaxurl, {
				action:      'luwipress_gold_wizard_step',
				step:        'complete',
				_ajax_nonce: nonce
			});

			$root.find('.lwp-apply-done').show();
			$root.find('#lwp-apply-run').hide();
		}).fail(function (xhr) {
			log('AJAX error: ' + xhr.status + ' ' + xhr.statusText, 'err');
			setProgress(100);
			$btn.prop('disabled', false).text('Retry');
		});
	});

	function setProgress(pct) {
		$root.find('.lwp-apply-bar').css('width', pct + '%');
	}

	function log(message, kind) {
		kind = kind || 'info';
		$root.find('.lwp-apply-log').append(
			'<li class="lwp-log--' + kind + '">' + escapeHtml(message) + '</li>'
		);
	}

	function renderApplyLog(data) {
		(data.actions || []).forEach(function (a) {
			var msg = (a.detail && a.detail.op) || a.op || '';
			if (a.status === 'ok') {
				log('✓ ' + msg + (a.result && a.result.template_id ? ' → template ID ' + a.result.template_id : ''), 'ok');
			} else {
				log('✗ ' + msg + ' — ' + (a.error || ''), 'err');
			}
		});
		(data.pages || []).forEach(function (p) {
			if (p.status === 'ok') {
				var line = '✓ page: ' + p.slug;
				if (p.result && p.result.created) line += ' (created)';
				if (p.result && p.result.reused)  line += ' (reused)';
				log(line, 'ok');
			} else {
				log('✗ page: ' + p.slug + ' — ' + (p.error || ''), 'err');
			}
		});
		(data.options || []).forEach(function (o) {
			log('• option: ' + o, 'info');
		});
		log(i18n.completed || 'Done.', 'ok');
	}

	function escapeHtml(s) {
		return String(s || '').replace(/[&<>"']/g, function (c) {
			return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
		});
	}
})(jQuery);
