/**
 * HKDEV Header — header.js
 *
 * Off-canvas mobile menu, categories dropdown, mobile submenu accordion and
 * the sticky-header shadow. No dependencies beyond jQuery.
 */
(function ($) {
	'use strict';

	$(function () {
		var $panel = $('.hkdev-header-panel').first();
		var $overlay = $('.hkdev-header-overlay').first();

		function openPanel() {
			$panel.addClass('is-open').attr('aria-hidden', 'false');
			$overlay.addClass('is-open');
			$('body').addClass('hkdev-header-locked');
		}

		function closePanel() {
			$panel.removeClass('is-open').attr('aria-hidden', 'true');
			$overlay.removeClass('is-open');
			$('body').removeClass('hkdev-header-locked');
		}

		// ---- Off-canvas panel -----------------------------------------
		$(document).on('click', '.hkdev-header-hamburger', function (e) {
			e.preventDefault();
			openPanel();
		});

		$(document).on('click', '.hkdev-header-panel-close, .hkdev-header-overlay', function (e) {
			e.preventDefault();
			closePanel();
		});

		$(document).on('keyup', function (e) {
			if ('Escape' === e.key) {
				closePanel();
			}
		});

		// ---- Categories dropdown --------------------------------------
		$(document).on('click', '.hkdev-header-cats-btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			$(this).closest('.hkdev-header-cats').toggleClass('is-open');
		});

		$(document).on('click', function (e) {
			if (!$(e.target).closest('.hkdev-header-cats').length) {
				$('.hkdev-header-cats').removeClass('is-open');
			}
		});

		// ---- Mobile submenu accordion ---------------------------------
		if ($panel.length) {
			$panel.find('.hkdev-header-menu li.menu-item-has-children').each(function () {
				var $li = $(this);
				var $link = $li.children('a').first();

				if (!$link.length || $li.children('.hkdev-submenu-toggle').length) {
					return;
				}

				var $icon = 'fa-solid fa-chevron-down';
				var $toggle = $(
					'<button type="button" class="hkdev-submenu-toggle" aria-label="Toggle submenu"><i class="' +
						$icon +
						'"></i></button>'
				);

				$link.after($toggle);
			});
		}

		$(document).on('click', '.hkdev-header-panel-nav .hkdev-submenu-toggle', function (e) {
			e.preventDefault();
			e.stopPropagation();

			$(this).toggleClass('is-open');
			$(this).closest('li.menu-item-has-children').toggleClass('is-submenu-open');
		});

		// Tapping the parent link on mobile toggles the submenu instead of navigating.
		$(document).on('click', '.hkdev-header-panel-nav .menu-item-has-children > a', function (e) {
			if ($(e.target).closest('.hkdev-submenu-toggle').length) {
				return;
			}

			var $toggle = $(this).siblings('.hkdev-submenu-toggle').first();

			if ($toggle.length) {
				e.preventDefault();
				$toggle.trigger('click');
			}
		});

		// ---- Live product search --------------------------------------
		var searchTimer = null;
		var searchRequest = null;

		function escapeHtml(value) {
			return $('<div>').text(value === null || value === undefined ? '' : value).html();
		}

		function searchWrap($form) {
			return $form.closest('.hkdev-header-search, .hkdev-header-panel-search');
		}

		function closeResults() {
			$('.hkdev-header-search-results').removeClass('is-open').empty();
		}

		function renderResults($wrap, data) {
			var $box = $wrap.find('.hkdev-header-search-results');
			var items = (data && data.items) ? data.items : [];
			var html = '';

			if (!items.length) {
				html += '<div class="hkdev-hs-empty"><i class="fa-solid fa-magnifying-glass"></i> ' +
					escapeHtml(hkdevHeaderL10n.noResults) + ' <strong>' + escapeHtml(data.term) + '</strong></div>';
			} else {
				html += '<ul class="hkdev-hs-list">';

				$.each(items, function (index, item) {
					html += '<li><a href="' + item.url + '">' +
						'<span class="hkdev-hs-thumb"><img src="' + item.img + '" alt=""></span>' +
						'<span class="hkdev-hs-info">' +
						'<span class="hkdev-hs-title">' + escapeHtml(item.title) + '</span>' +
						'<span class="hkdev-hs-price">' + (item.price || '') + '</span>' +
						'</span></a></li>';
				});

				html += '</ul>';
				html += '<a class="hkdev-hs-all" href="' + data.search_url + '">' +
					escapeHtml(hkdevHeaderL10n.viewAll) + ' <i class="fa-solid fa-arrow-right"></i></a>';
			}

			$box.html(html).addClass('is-open');
		}

		$(document).on('input', '.hkdev-header-search-input', function () {
			var $input = $(this);
			var $wrap = searchWrap($input.closest('.hkdev-header-search-form'));
			var term = $.trim($input.val());

			$wrap.toggleClass('has-value', term.length > 0);

			if (searchTimer) {
				clearTimeout(searchTimer);
			}

			if (term.length < 2) {
				closeResults();
				return;
			}

			searchTimer = setTimeout(function () {
				$wrap.addClass('is-loading');

				if (searchRequest) {
					searchRequest.abort();
				}

				searchRequest = $.post(hkdevHeaderL10n.ajaxUrl, {
					action: hkdevHeaderL10n.action,
					nonce: hkdevHeaderL10n.searchNonce,
					term: term
				}).done(function (response) {
					if (response && response.success) {
						renderResults($wrap, response.data);
					}
				}).always(function () {
					$wrap.removeClass('is-loading');
				});
			}, 280);
		});

		$(document).on('click', '.hkdev-header-search-clear', function (e) {
			e.preventDefault();

			var $wrap = searchWrap($(this).closest('.hkdev-header-search-form'));
			$wrap.find('.hkdev-header-search-input').val('').trigger('focus');
			$wrap.removeClass('has-value');
			closeResults();
		});

		$(document).on('submit', '.hkdev-header-search-form', function () {
			closeResults();
		});

		$(document).on('click', function (e) {
			if (!$(e.target).closest('.hkdev-header-search-form, .hkdev-header-search-results').length) {
				closeResults();
			}
		});

		$(document).on('keyup', '.hkdev-header-search-input', function (e) {
			if ('Escape' === e.key) {
				closeResults();
			}
		});

		// Clicking a result inside the off-canvas panel should close it.
		$(document).on('click', '.hkdev-header-search-results a', function () {
			closeResults();
			closePanel();
		});

		// ---- Mini cart -------------------------------------------------
		var $mini = $('.hkdev-mini-cart').first();
		var $miniOverlay = $('.hkdev-mini-cart-overlay').first();

		function openMiniCart() {
			if (!$mini.length) {
				return;
			}

			$mini.addClass('is-open').attr('aria-hidden', 'false');
			$miniOverlay.addClass('is-open');
			$('body').addClass('hkdev-header-locked');
		}

		function closeMiniCart() {
			if (!$mini.length) {
				return;
			}

			$mini.removeClass('is-open').attr('aria-hidden', 'true');
			$miniOverlay.removeClass('is-open');
			$('body').removeClass('hkdev-header-locked');
		}

		function updateFloatCart(count, total) {
			var $btn = $('.hkdev-float-cart');

			if (!$btn.length) {
				return;
			}

			var label = count === 1 ? hkdevHeaderL10n.itemOne : hkdevHeaderL10n.itemMany;

			$btn.find('.hkdev-float-cart-count').text(label.replace('%d', count));
			$btn.toggleClass('is-empty', count < 1);

			if ('undefined' !== typeof total) {
				$btn.find('.hkdev-float-cart-total').html(total);
			}
		}

		function refreshMiniCart(extra) {
			if (!$mini.length) {
				return;
			}

			$mini.addClass('is-loading');

			var payload = $.extend(
				{
					action: hkdevHeaderL10n.cartAction,
					nonce: hkdevHeaderL10n.cartNonce
				},
				extra || {}
			);

			return $.post(hkdevHeaderL10n.ajaxUrl, payload).done(function (response) {
				if (!response || !response.success) {
					return;
				}

				$mini.find('.hkdev-mini-cart-body').html(response.data.html);
				$('.hkdev-header-cart-count, .hkdev-mini-cart-count-inline').text(response.data.count);
				updateFloatCart(response.data.count, response.data.total);
			}).always(function () {
				$mini.removeClass('is-loading');
			});
		}

		function applyQty($item, value) {
			var $input = $item.find('.hkdev-mc-qty');
			var min = parseInt($input.attr('data-min'), 10) || 1;
			var maxAttr = $input.attr('data-max');
			var max = maxAttr ? parseInt(maxAttr, 10) : 0;
			var key = $item.attr('data-key');
			var qty = parseInt(value, 10);

			if (isNaN(qty) || qty < min) {
				qty = min;
			}

			if (max > 0 && qty > max) {
				qty = max;
			}

			if (!key || String(qty) === String($input.val())) {
				$input.val(qty);
				return;
			}

			$input.val(qty);
			refreshMiniCart({ qty_key: key, qty_value: qty });
		}

		$(document).on('click', '.hkdev-header-cart, .hkdev-float-cart', function (e) {
			if ('1' !== $(this).attr('data-mini-cart')) {
				return;
			}

			e.preventDefault();
			openMiniCart();
			refreshMiniCart();
		});

		$(document).on('click', '.hkdev-mini-cart-close, .hkdev-mini-cart-overlay', function (e) {
			e.preventDefault();
			closeMiniCart();
		});

		$(document).on('click', '.hkdev-mc-remove', function (e) {
			e.preventDefault();

			var key = $(this).attr('data-key');

			if (key) {
				refreshMiniCart({ remove_key: key });
			}
		});

		$(document).on('click', '.hkdev-mc-step', function (e) {
			e.preventDefault();

			var $item = $(this).closest('.hkdev-mc-item');
			var $input = $item.find('.hkdev-mc-qty');
			var step = parseInt($(this).attr('data-step'), 10) || 1;
			var current = parseInt($input.val(), 10);

			if (isNaN(current)) {
				current = 1;
			}

			applyQty($item, current + step);
		});

		$(document).on('change', '.hkdev-mc-qty', function () {
			applyQty($(this).closest('.hkdev-mc-item'), this.value);
		});

		// Numbers only while typing.
		$(document).on('input', '.hkdev-mc-qty', function () {
			this.value = this.value.replace(/[^0-9]/g, '');
		});

		// Stay in sync after any WooCommerce cart change.
		$(document.body).on('added_to_cart removed_from_cart wc_fragment_refresh updated_cart_totals', function () {
			if ($mini.length) {
				refreshMiniCart();
			}
		});

		$(document).on('keyup', function (e) {
			if ('Escape' === e.key) {
				closeMiniCart();
			}
		});

		// ---- Horizontal menu scrolling (wheel + drag) -----------------
		var $nav = $('.hkdev-header-nav').first();

		if ($nav.length) {
			var navEl = $nav[0];
			var navDown = false;
			var navStartX = 0;
			var navStartScroll = 0;
			var navMoved = false;

			var navScrollable = function () {
				return navEl.scrollWidth > navEl.clientWidth + 1;
			};

			// Vertical wheel over the menu scrolls it sideways.
			navEl.addEventListener(
				'wheel',
				function (e) {
					if (!navScrollable()) {
						return;
					}

					var delta = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;

					if (!delta) {
						return;
					}

					// At either end let the page scroll normally instead of trapping it.
					var maxScroll = navEl.scrollWidth - navEl.clientWidth;

					if ((delta < 0 && navEl.scrollLeft <= 0) || (delta > 0 && navEl.scrollLeft >= maxScroll - 1)) {
						return;
					}

					e.preventDefault();
					navEl.scrollLeft += delta;
				},
				{ passive: false }
			);

			// Grab and drag with the mouse (touch uses native scrolling).
			navEl.addEventListener('pointerdown', function (e) {
				if ('mouse' !== e.pointerType || !navScrollable()) {
					return;
				}

				navDown = true;
				navMoved = false;
				navStartX = e.clientX;
				navStartScroll = navEl.scrollLeft;
				$nav.addClass('is-dragging');
			});

			window.addEventListener('pointermove', function (e) {
				if (!navDown) {
					return;
				}

				var diff = e.clientX - navStartX;

				if (Math.abs(diff) > 3) {
					navMoved = true;
				}

				navEl.scrollLeft = navStartScroll - diff;
			});

			window.addEventListener('pointerup', function () {
				if (!navDown) {
					return;
				}

				navDown = false;
				$nav.removeClass('is-dragging');
			});

			// A drag should not open the link sitting under the cursor.
			$nav.on('click', 'a', function (e) {
				if (navMoved) {
					e.preventDefault();
					navMoved = false;
				}
			});
		}

		// ---- Sticky shadow + auto-hide on scroll ----------------------
		var $sticky = $('.hkdev-header-wrap.hkdev-header-sticky').first();

		if ($sticky.length) {
			var autoHide = $sticky.hasClass('hkdev-header-autohide');
			var $topbar = $sticky.find('.hkdev-header-topbar').first();

			// "Top bar only" mode changes the header height, which moves the page
			// content and fires extra scroll events. A settle window after every
			// toggle keeps those events from flipping the state back and forth.
			var HIDE_AFTER = 120; // never hide this close to the top
			var UP_DELTA   = 12;  // px of upward travel needed to reveal
			var DOWN_DELTA = 10;  // px of downward travel needed to hide
			var LOCK_MS    = 520; // longer than the CSS collapse transition

			var lastTop = $(window).scrollTop();
			var hidden = false;
			var lockUntil = 0;
			var ticking = false;

			// Give the collapsible bar its real height so max-height animates
			// smoothly and never clips the content on narrow screens.
			var measureTopbar = function () {
				if ($topbar.length && $topbar[0].scrollHeight) {
					$sticky[0].style.setProperty('--hd-topbar-h', $topbar[0].scrollHeight + 'px');
				}
			};

			var setHidden = function (state) {
				if (state === hidden) {
					return;
				}
				hidden = state;
				$sticky.toggleClass('is-hidden', state);
				lockUntil = Date.now() + LOCK_MS;
			};

			var applyScroll = function () {
				ticking = false;

				var top = $(window).scrollTop();
				var now = Date.now();

				$sticky.toggleClass('is-stuck', top > 10);

				if (!autoHide) {
					lastTop = top;
					return;
				}

				// Near the top the header always stays visible.
				if (top <= HIDE_AFTER) {
					setHidden(false);
					lastTop = top;
					return;
				}

				// Waiting for the show/hide transition to settle: only re-baseline,
				// never toggle. This is what stops the endless blinking.
				if (now < lockUntil) {
					lastTop = top;
					return;
				}

				var diff = top - lastTop;

				if (!hidden && diff >= DOWN_DELTA) {
					setHidden(true);
				} else if (hidden && diff <= -UP_DELTA) {
					setHidden(false);
				}

				lastTop = top;
			};

			var onScroll = function () {
				if (ticking) {
					return;
				}
				ticking = true;
				if (window.requestAnimationFrame) {
					window.requestAnimationFrame(applyScroll);
				} else {
					window.setTimeout(applyScroll, 16);
				}
			};

			$(window).on('scroll', onScroll);

			// Mobile browsers fire scroll events while the URL bar collapses or the
			// orientation changes – re-baseline instead of toggling the header.
			$(window).on('resize orientationchange', function () {
				measureTopbar();
				lastTop = $(window).scrollTop();
				lockUntil = Date.now() + 300;
			});

			measureTopbar();
			applyScroll();
		}
	});
})(jQuery);
