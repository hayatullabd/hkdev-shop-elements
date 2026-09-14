/* global hkdev_elements_ajax, jQuery */
/**
 * HKDEV Catalog — AJAX search / filter / sort / load-more.
 *
 * Works for the [hkdev_catalog] shortcode, the Elementor Catalog widget and
 * the WooCommerce Shop / category archive bar (which navigates with GET).
 */
(function ($) {
	'use strict';

	var ACTION = 'hkdev_elements_catalog_filter';

	function ajaxUrl() {
		if (typeof hkdev_elements_ajax !== 'undefined' && hkdev_elements_ajax.ajax_url) {
			return hkdev_elements_ajax.ajax_url;
		}
		return window.location.origin + '/wp-admin/admin-ajax.php';
	}

	function isArchive($cat) {
		return String($cat.attr('data-archive')) === '1';
	}

	function lockedSlugs($cat, key) {
		return ($cat.attr('data-locked-' + key) || '').split(',').map(function (slug) {
			return slug.trim();
		}).filter(Boolean);
	}

	/* ---------------------------------------------------------- collecting -- */

	function collect($cat) {
		var params = {};

		$cat.find('.hkdev-cat-panel input[name], .hkdev-cat-panel select[name]').serializeArray().forEach(function (f) {
			if (typeof params[f.name] === 'undefined') {
				params[f.name] = f.value;
			} else {
				if (!Array.isArray(params[f.name])) {
					params[f.name] = [params[f.name]];
				}
				params[f.name].push(f.value);
			}
		});

		var search = $cat.find('.hkdev-cat-search-input').val();
		if (search) {
			params.hk_s = search;
		}

		var sort = $cat.find('.hkdev-cat-sort-select').val();
		if (sort && sort !== 'newest') {
			params.hk_sort = sort;
		}

		// Only send the price when it is narrower than the full range.
		var $price = $cat.find('.hkdev-cat-price');
		if ($price.length) {
			var boundMin = parseFloat($price.attr('data-min'));
			var boundMax = parseFloat($price.attr('data-max'));
			var min = parseFloat($price.find('.hkdev-cat-price-min').val());
			var max = parseFloat($price.find('.hkdev-cat-price-max').val());
			if (!isNaN(min) && min > boundMin) {
				params.hk_min = min;
			}
			if (!isNaN(max) && max < boundMax) {
				params.hk_max = max;
			}
		}

		// The current category / tag page always wins over the filter panel.
		var lockCats = lockedSlugs($cat, 'cats');
		if (lockCats.length) {
			params.hk_cat = lockCats;
		}
		var lockTags = lockedSlugs($cat, 'tags');
		if (lockTags.length) {
			params.hk_tag = lockTags;
		}

		return params;
	}

	function toQuery(params) {
		var parts = [];
		Object.keys(params).forEach(function (key) {
			var value = params[key];
			if (Array.isArray(value)) {
				value.forEach(function (item) {
					parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(item));
				});
			} else {
				parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
			}
		});
		return parts.join('&');
	}

	function withCatalogQuery(query) {
		var base = window.location.href.split('#')[0].split('?')[0];
		var kept = window.location.search.replace(/^\?/, '').split('&').filter(function (pair) {
			if (!pair) {
				return false;
			}
			var key = decodeURIComponent(pair.split('=')[0]);
			return key.indexOf('hk_') !== 0 && key !== 'paged' && key !== 'page';
		});
		var merged = kept.concat(query ? query.split('&') : []).filter(Boolean).join('&');
		return base + (merged ? '?' + merged : '');
	}

	/* -------------------------------------------------------------- render -- */

	function updateDrawerCount($cat) {
		var count = $cat.find('.hkdev-cat-panel input:checked').filter(function () {
			return this.value !== '' && this.name !== 'hk_rating';
		}).length;
		count += $cat.find('.hkdev-cat-panel input[name="hk_rating"]:checked').filter(function () {
			return this.value !== '';
		}).length;

		var $badge = $cat.find('.hkdev-cat-drawer-count');
		if (count > 0) {
			$badge.text(count).prop('hidden', false);
		} else {
			$badge.prop('hidden', true);
		}
	}

	function syncUrl($cat, params, page, replace) {
		var query = toQuery(params);
		if (page && page > 1) {
			query = query ? query + '&hk_page=' + page : 'hk_page=' + page;
		}
		var url = withCatalogQuery(query);
		if (window.history && window.history.replaceState) {
			window.history[replace ? 'replaceState' : 'pushState']({ hkcat: 1 }, '', url);
		}
	}

	function apply($cat, options) {
		options = options || {};
		var params = collect($cat);

		if (isArchive($cat)) {
			window.location.href = withCatalogQuery(toQuery(params));
			return;
		}

		var data = $.extend({}, params, {
			action: ACTION,
			nonce: $cat.attr('data-nonce'),
			columns: $cat.attr('data-columns'),
			per_page: $cat.attr('data-per-page'),
			hover_img: $cat.attr('data-hover-img'),
			hk_page: options.page || 1,
			hk_append: options.append ? 1 : 0,
			hk_locked_cats: lockedSlugs($cat, 'cats').join(','),
			hk_locked_tags: lockedSlugs($cat, 'tags').join(',')
		});

		$cat.addClass('is-loading');

		$.ajax({
			url: ajaxUrl(),
			type: 'POST',
			data: data
		}).done(function (response) {
			if (!response || !response.success || !response.data) {
				return;
			}
			var d = response.data;
			var $grid = $cat.find('.hkdev-cat-grid');

			if (options.append) {
				$grid.append(d.html);
			} else {
				$grid.html(d.html);
			}
			$cat.find('.hkdev-cat-chips').html(d.chips_html || '');
			$cat.find('.hkdev-cat-count').html(d.count_html || '');
			$cat.find('.hkdev-cat-more').prop('hidden', !d.has_more);

			if (!options.append) {
				syncUrl($cat, params, 1);
			} else {
				syncUrl($cat, params, d.page, true);
			}
			updateDrawerCount($cat);
		}).always(function () {
			$cat.removeClass('is-loading');
		});
	}

	/* ----------------------------------------------------------- form sync -- */

	function paramsFromUrl() {
		var out = {};
		var query = window.location.search.replace(/^\?/, '');
		if (!query) {
			return out;
		}
		query.split('&').forEach(function (pair) {
			if (!pair) {
				return;
			}
			var idx = pair.indexOf('=');
			var key = decodeURIComponent(idx < 0 ? pair : pair.slice(0, idx));
			if (key.indexOf('hk_') !== 0) {
				return;
			}
			var value = decodeURIComponent(idx < 0 ? '' : pair.slice(idx + 1));
			if (typeof out[key] === 'undefined') {
				out[key] = value;
			} else {
				if (!Array.isArray(out[key])) {
					out[key] = [out[key]];
				}
				out[key].push(value);
			}
		});
		return out;
	}

	function syncFormFromUrl($cat) {
		var params = paramsFromUrl();
		var has = Object.keys(params).length > 0;

		$cat.find('.hkdev-cat-search-input').val(params.hk_s || '');
		if (params.hk_sort) {
			$cat.find('.hkdev-cat-sort-select').val(params.hk_sort);
		}

		$cat.find('.hkdev-cat-check input').each(function () {
			var name = this.name;
			var value = params[name];
			if (this.type === 'radio') {
				this.checked = String(value) === String(this.value);
			} else if (name === 'hk_stock' || name === 'hk_sale') {
				this.checked = String(value) === '1';
			} else {
				this.checked = Array.isArray(value) ? value.indexOf(this.value) >= 0 : String(value) === this.value;
			}
		});

		if (params.hk_min) {
			$cat.find('.hkdev-cat-price-min').val(params.hk_min);
		}
		if (params.hk_max) {
			$cat.find('.hkdev-cat-price-max').val(params.hk_max);
		}
		updatePriceLabels($cat);
		updateDrawerCount($cat);

		return has;
	}

	/* ------------------------------------------------------------- init ----- */

	function updatePriceLabels($cat) {
		var $price = $cat.find('.hkdev-cat-price');
		if (!$price.length) {
			return;
		}
		var min = $price.find('.hkdev-cat-price-min').val();
		var max = $price.find('.hkdev-cat-price-max').val();
		$price.find('.hkdev-cat-price-min-range').val(min);
		$price.find('.hkdev-cat-price-max-range').val(max);
	}

	function openDrawer($cat) {
		$cat.addClass('cat-open');
	}

	function closeDrawer($cat) {
		$cat.removeClass('cat-open');
	}

	var searchTimer = null;

	function init($cat) {
		if (!$cat.length || $cat.data('hkcat-init')) {
			return;
		}
		$cat.data('hkcat-init', true);

		// Sync the form to the URL state (deep links / back button).
		if (!isArchive($cat)) {
			syncFormFromUrl($cat);
		}

		$cat.on('change', '.hkdev-cat-panel input[type="checkbox"], .hkdev-cat-panel input[type="radio"], .hkdev-cat-sort-select', function () {
			apply($cat);
		});

		$cat.on('input', '.hkdev-cat-search-input', function () {
			window.clearTimeout(searchTimer);
			searchTimer = window.setTimeout(function () {
				apply($cat);
			}, 400);
		});

		$cat.on('input', '.hkdev-cat-price-min-range, .hkdev-cat-price-max-range', function () {
			var $price = $cat.find('.hkdev-cat-price');
			var min = $price.find('.hkdev-cat-price-min-range').val();
			var max = $price.find('.hkdev-cat-price-max-range').val();
			if (parseFloat(min) > parseFloat(max)) {
				if ($(this).hasClass('hkdev-cat-price-min-range')) {
					$price.find('.hkdev-cat-price-max-range').val(min);
					max = min;
				} else {
					$price.find('.hkdev-cat-price-min-range').val(max);
					min = max;
				}
			}
			$price.find('.hkdev-cat-price-min').val(min);
			$price.find('.hkdev-cat-price-max').val(max);
		});

		$cat.on('change', '.hkdev-cat-price-min, .hkdev-cat-price-max', function () {
			updatePriceLabels($cat);
			apply($cat);
		});

		$cat.on('click', '.hkdev-cat-more', function () {
			var page = parseInt($cat.data('page') || 1, 10) + 1;
			apply($cat, { page: page, append: true });
		});

		$cat.on('click', '.hkdev-cat-chip', function () {
			var key = $(this).attr('data-key');
			var value = $(this).attr('data-value');
			if (typeof value !== 'undefined') {
				$cat.find('.hkdev-cat-panel input[name="' + key + '[]"]').filter(function () {
					return this.value === value;
				}).prop('checked', false);
			} else if (key === 'hk_min' || key === 'hk_max') {
				var $price = $cat.find('.hkdev-cat-price');
				$price.find('.hkdev-cat-price-min, .hkdev-cat-price-min-range').val($price.attr('data-min'));
				$price.find('.hkdev-cat-price-max, .hkdev-cat-price-max-range').val($price.attr('data-max'));
			} else {
				$cat.find('.hkdev-cat-panel input[name="' + key + '"]').prop('checked', false);
			}
			apply($cat);
		});

		$cat.on('click', '.hkdev-cat-clear', function () {
			$cat.find('.hkdev-cat-panel input[type="checkbox"], .hkdev-cat-panel input[type="radio"]').prop('checked', false);
			$cat.find('.hkdev-cat-panel input[name="hk_rating"][value=""]').prop('checked', true);
			$cat.find('.hkdev-cat-search-input').val('');
			$cat.find('.hkdev-cat-sort-select').val('newest');
			var $price = $cat.find('.hkdev-cat-price');
			$price.find('.hkdev-cat-price-min, .hkdev-cat-price-min-range').val($price.attr('data-min'));
			$price.find('.hkdev-cat-price-max, .hkdev-cat-price-max-range').val($price.attr('data-max'));
			apply($cat);
		});

		$cat.on('click', '.hkdev-cat-drawer-toggle', function () {
			openDrawer($cat);
		});
		$cat.on('click', '.hkdev-cat-panel-close, .hkdev-cat-overlay', function () {
			closeDrawer($cat);
		});
		$cat.on('click', '.hkdev-cat-apply', function () {
			if (isArchive($cat)) {
				window.location.href = withCatalogQuery(toQuery(collect($cat)));
			} else {
				closeDrawer($cat);
			}
		});

		$cat.on('click', '.hkdev-cat-group-head', function () {
			$(this).closest('.hkdev-cat-group').toggleClass('is-collapsed');
		});

		$cat.on('keydown', '.hkdev-cat-search-input', function (event) {
			if (event.key === 'Enter') {
				event.preventDefault();
				apply($cat);
			}
		});
	}

	function initAll(scope) {
		var $scope = scope ? $(scope) : $(document);
		$scope.find('.hkdev-catalog').each(function () {
			init($(this));
		});
	}

	$(function () {
		initAll();
	});

	$(window).on('elementor/frontend/init', function () {
		if (typeof window.elementorFrontend === 'undefined') {
			return;
		}
		window.elementorFrontend.hooks.addAction('frontend/element_ready/hkdev_catalog.default', function ($scope) {
			initAll($scope);
		});
	});

	$(window).on('popstate', function () {
		$('.hkdev-catalog:not([data-archive="1"])').each(function () {
			var $cat = $(this);
			syncFormFromUrl($cat);
			apply($cat);
		});
	});
})(jQuery);
