jQuery(function ($) {
	var data = window.hkdevPluginRow || {};
	var selector = '.deactivate #deactivate-' + String(data.pluginSlug || '').replace('/', '\\/');

	$(document).on('click', selector, function (event) {
		event.preventDefault();

		var deactivateUrl = $(this).attr('href');
		var $dialog = $('#hkdev-deactivate-dialog');
		var $validation = $('#hkdev-deactivate-validation');

		if (!$dialog.length || typeof $dialog.dialog !== 'function') {
			window.location.href = deactivateUrl;
			return;
		}

		$validation.text('');

		$dialog.dialog({
			modal: true,
			width: 560,
			resizable: false,
			dialogClass: 'hkdev-deactivate-ui-dialog',
			buttons: [
				{
					text: data.submitLabel || 'Submit & Deactivate',
					class: 'button button-primary',
					click: function () {
						var reason = $('input[name="hkdev_reason"]:checked').val() || '';
						var feedback = $('#hkdev-deactivate-feedback').val() || '';

						if (!reason) {
							$validation.text(data.validationLabel || 'Please choose a reason first.');
							return;
						}

						$(this).parent().find('button').prop('disabled', true);

						$.post(ajaxurl, {
							action: data.ajaxAction || 'hkdev_elements_deactivation_feedback',
							nonce: data.nonce || '',
							reason: reason,
							feedback: feedback
						}).always(function () {
							window.location.href = deactivateUrl;
						});
					}
				},
				{
					text: data.skipLabel || 'Skip & Deactivate',
					class: 'button',
					click: function () {
						window.location.href = deactivateUrl;
					}
				}
			],
			close: function () {
				$validation.text('');
			}
		});
	});
});
