/* global hkdevWishlistL10n, jQuery */
/**
 * HKDEV Wishlist — AJAX add / remove.
 *
 * One delegated handler drives every button the plugin renders (product cards,
 * cart rows, single product page), so buttons injected by later AJAX calls
 * (cart refresh, load more) keep working without rebinding.
 */
(function ($) {
	'use strict';

	var cfg = (typeof hkdevWishlistL10n !== 'undefined' && hkdevWishlistL10n) ? hkdevWishlistL10n : {};
	var AJAX_URL = cfg.ajaxUrl || '/wp-admin/admin-ajax.php';
	var ACTION = cfg.action || 'hkdev_elements_wishlist_toggle';
	var MOVE_ACTION = cfg.moveAction || 'hkdev_elements_wishlist_move';
	var NONCE = cfg.nonce || '';

	/* ------------------------------------------------------------ helpers -- */

	function setButton($btn, active) {
		var $icon = $btn.find('i').first();

		$btn.toggleClass('is-active', active).attr('aria-pressed', active ? 'true' : 'false');

		$icon.toggleClass('fa-solid', active).toggleClass('fa-regular', !active);

		var $label = $btn.find('.hkdev-wishlist-label, .screen-reader-text');
		if ($label.length) {
			$label.text(active ? ($btn.attr('data-label-added') || 'In Wishlist') : ($btn.attr('data-label-add') || 'Add to Wishlist'));
		}
	}

	// The same product can be on screen more than once (grid + cart + wishlist
	// page), so every button is re-synced from the authoritative ID list.
	function syncButtons(ids) {
		var list = (ids || []).map(Number);

		$('.hkdev-wishlist-btn').each(function () {
			var $btn = $(this);
			setButton($btn, list.indexOf(Number($btn.attr('data-product-id'))) !== -1);
		});
	}

	// Header icon (and mobile panel link): badge value, filled heart and the
	// "has items" tint all follow the live count.
	function syncHeader(count) {
		var has = count > 0;

		$('.hkdev-header-wishlist').toggleClass('has-items', has);
		$('.hkdev-header-wishlist-count, .hkdev-header-wishlist-count-inline').text(count);
		$('.hkdev-header-wishlist i').toggleClass('fa-solid', has).toggleClass('fa-regular', !has);
	}

	function syncCount(count) {
		count = parseInt(count, 10) || 0;

		$('.hkdev-wishlist-count').text(count).attr('data-count', count);
		$('.hkdev-account-wishlist-count').text(count);
		$('.hkdev-wishlist').attr('data-count', count);

		syncHeader(count);
	}

	function updateEmptyState($root) {
		var remaining = $root.find('.hkdev-product-card').length;

		$root.find('.hkdev-wishlist-grid').prop('hidden', remaining === 0);
		$root.find('.hkdev-wishlist-empty').prop('hidden', remaining > 0);
	}

	// Removing from the wishlist page takes the card away straight away.
	function removeCard(productId) {
		$('.hkdev-wishlist').each(function () {
			var $root = $(this);
			var $cards = $root.find('.hkdev-product-card').filter(function () {
				return Number($(this).find('.hkdev-wishlist-btn').attr('data-product-id')) === Number(productId);
			});

			if (!$cards.length) {
				return;
			}

			$cards.remove();
			updateEmptyState($root);
		});
	}

	/* --------------------------------------------------------------- init -- */

	$(document).on('click', '.hkdev-wishlist-btn', function (event) {
		event.preventDefault();

		var $btn = $(this);

		if ($btn.hasClass('is-loading')) {
			return;
		}

		var productId = parseInt($btn.attr('data-product-id'), 10);
		if (!productId) {
			return;
		}

		$btn.addClass('is-loading');

		$.ajax({
			url: AJAX_URL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: ACTION,
				nonce: NONCE,
				product_id: productId
			}
		}).done(function (response) {
			if (!response || !response.success || !response.data) {
				return;
			}

			syncButtons(response.data.ids);
			syncCount(response.data.count);

			if (!response.data.active) {
				removeCard(productId);
			}

			$(document.body).trigger('hkdev_wishlist_updated', [response.data]);
		}).always(function () {
			$btn.removeClass('is-loading');
		});
	});

	/* --------------------------------------------- move cart item --> list -- */

	$(document).on('click', '.hkdev-item-move-btn', function (event) {
		event.preventDefault();

		var $btn = $(this);

		if ($btn.hasClass('is-loading')) {
			return;
		}

		var cartKey = $btn.attr('data-key') || $btn.closest('[data-key]').data('key');
		if (!cartKey) {
			return;
		}

		$btn.addClass('is-loading');

		$.ajax({
			url: AJAX_URL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: MOVE_ACTION,
				nonce: NONCE,
				cart_key: cartKey
			}
		}).done(function (response) {
			if (!response || !response.success || !response.data) {
				return;
			}

			var data = response.data;

			syncButtons(data.ids);
			syncCount(data.count);

			// Same behaviour as the plugin's own cart AJAX: an emptied cart is
			// redrawn by a reload so the empty state markup is used.
			if (data.is_empty) {
				window.location.reload();
				return;
			}

			var $items = $('#hkdev-cart-items-area');
			var $totals = $('#hkdev-cart-totals-area');

			if (data.items_html && $items.length) {
				$items.html(data.items_html);
			}
			if (data.totals_html && $totals.length) {
				$totals.html(data.totals_html);
			}

			$(document.body).trigger('updated_cart_totals').trigger('wc_fragment_refresh');
		}).always(function () {
			$btn.removeClass('is-loading');
		});
	});
})(jQuery);
