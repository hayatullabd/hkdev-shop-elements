/**
 * HKDEV Footer — footer.js
 *
 * AJAX newsletter signup (when no external form action is set) and the
 * floating back-to-top button. Delegated events so the site-wide footer and
 * the Elementor widget both work. No dependencies beyond jQuery.
 */
(function ($) {
	'use strict';

	$(function () {
		var i18n = window.hkdevFooterL10n || {};

		// ---- Newsletter signup (built-in storage) ---------------------
		$(document).on('submit', '.hkdev-footer-news-form[data-hkdev-news-ajax="1"]', function (e) {
			e.preventDefault();

			var $form = $(this);
			var $input = $form.find('.hkdev-footer-news-input');
			var $btn = $form.find('.hkdev-footer-news-btn');
			var $msg = $form.closest('.hkdev-footer-col').find('.hkdev-footer-news-msg');

			var email = $.trim($input.val());

			if (!email || email.indexOf('@') === -1) {
				$msg.removeClass('is-success').addClass('is-error is-visible').text(i18n.invalid || 'Please enter a valid email address.');
				return;
			}

			if ($btn.hasClass('is-loading')) {
				return;
			}

			$btn.addClass('is-loading');
			$msg.removeClass('is-success is-error').removeClass('is-visible').text('');

			$.post(
				i18n.ajaxUrl || window.ajaxurl,
				{
					action: i18n.action || 'hkdev_elements_footer_subscribe',
					nonce: i18n.subscribeNonce || '',
					email: email
				}
			).done(function (response) {
				var message = (response && response.data && response.data.message) || '';

				if (response && response.success) {
					$msg.removeClass('is-error').addClass('is-success is-visible').text(message || i18n.success || 'Thanks for subscribing!');
					$input.val('');
				} else {
					$msg.removeClass('is-success').addClass('is-error is-visible').text(message || i18n.error || 'Something went wrong. Please try again.');
				}
			}).fail(function () {
				$msg.removeClass('is-success').addClass('is-error is-visible').text(i18n.error || 'Something went wrong. Please try again.');
			}).always(function () {
				$btn.removeClass('is-loading');
			});
		});

		// ---- Back to top ---------------------------------------------
		var $top = $('.hkdev-footer-top');

		if ($top.length) {
			var toggleTop = function () {
				$top.toggleClass('is-visible', $(window).scrollTop() > 300);
			};

			$(window).on('scroll', toggleTop);
			toggleTop();

			$(document).on('click', '.hkdev-footer-top', function (e) {
				e.preventDefault();
				$('html, body').animate({ scrollTop: 0 }, 480);
			});
		}
	});
}(jQuery));
