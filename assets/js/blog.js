/**
 * HKDEV Blog — category tab filtering + "Load more".
 *
 * Mirrors the shop grid behaviour: clicking a tab swaps the card list for that
 * category without reloading the page, and the load-more button appends the
 * next page of whichever category is currently selected. The widget's own
 * settings travel in the `data-hkdev-blog-config` JSON attribute, so every AJAX
 * request rebuilds exactly the same query as the first render.
 *
 * @package HkdevShopElements
 */
(function ($) {
    'use strict';

    var cfg = (typeof hkdev_elements_ajax !== 'undefined') ? hkdev_elements_ajax : null;

    if (!cfg || !cfg.ajax_url) {
        return;
    }

    var FILTER_ACTION = 'hkdev_elements_filter_blog';
    var MORE_ACTION = 'hkdev_elements_load_more_blog';
    var NONCE = (cfg.nonces && cfg.nonces.blog_filter) ? cfg.nonces.blog_filter : '';

    function $moreBtn($wrap) {
        return $wrap.find('.hkdev-blog-loadmore').first();
    }

    function idle($btn) {
        $btn.removeClass('is-loading').prop('disabled', false)
            .find('.hkdev-blog-lm-label').text($btn.attr('data-label') || 'Load More');
    }

    /**
     * Put the button back to its first-page state.
     *
     * Used after a tab switch, which replaces the grid with page 1 of another
     * category: when that page already holds every post of the category there is
     * nothing left to load. The button is only hidden (never removed), so
     * switching back to a bigger category brings it straight back.
     */
    function resetLoadMore($wrap, maxPages) {
        var $btn = $moreBtn($wrap);
        if (!$btn.length) {
            return;
        }

        var pages = parseInt(maxPages, 10);
        if (isNaN(pages)) {
            pages = parseInt($btn.data('maxPages'), 10) || 1;
        }

        $btn.data('page', 1).data('maxPages', pages);
        idle($btn);
        $btn.closest('.hkdev-blog-loadmore-wrap').toggleClass('is-empty', pages <= 1);
    }

    $(document).on('click', '.hkdev-blog-tab-item', function () {
        var $btn = $(this);
        var $wrap = $btn.closest('[data-hkdev-blog]');

        if (!$wrap.length || $btn.hasClass('is-active') || $btn.hasClass('is-loading')) {
            return;
        }

        var $items = $wrap.find('.hkdev-blog-items').first();
        var $loader = $wrap.find('.hkdev-blog-loader').first();

        if (!$items.length) {
            return;
        }

        $wrap.find('.hkdev-blog-tab-item').removeClass('is-active').attr('aria-selected', 'false');
        $btn.addClass('is-active').attr('aria-selected', 'true').addClass('is-loading');
        $loader.addClass('is-visible');

        $.post(cfg.ajax_url, {
            action: FILTER_ACTION,
            nonce: NONCE,
            config: $wrap.attr('data-hkdev-blog-config') || '{}',
            category: $btn.attr('data-slug') || ''
        }).done(function (response) {
            if (response && response.success && response.data && typeof response.data.html === 'string') {
                $items.html(response.data.html);

                // The grid now holds page 1 of the selected category.
                resetLoadMore($wrap, response.data.max_pages);

                // Let themes / other plugins react (e.g. re-init lazy loaders).
                $wrap.trigger('hkdev:blog-filtered', [response.data.count || 0]);
            }
        }).always(function () {
            $btn.removeClass('is-loading');
            $loader.removeClass('is-visible');
        });
    });

    // Category tab navigation — same as the header menu (free wheel/drag,
    // arrows slide by most of the viewport). Chip styles stay on the tabs.
    (function () {
        $('.hkdev-blog-tabs').each(function () {
            var $wrap = $(this);
            var $scroll = $wrap.find('.hkdev-blog-tabs-scroll');
            var el = $scroll[0];
            if (!el) return;

            var $prev = $wrap.find('.hkdev-blog-tabs-arrow.hkdev-blog-tabs-prev');
            var $next = $wrap.find('.hkdev-blog-tabs-arrow.hkdev-blog-tabs-next');
            var down = false;
            var startX = 0;
            var startScroll = 0;
            var moved = false;

            function max() {
                return Math.max(0, el.scrollWidth - el.clientWidth);
            }

            function scrollable() {
                return el.scrollWidth > el.clientWidth + 1;
            }

            function sync() {
                if (max() <= 1) {
                    $prev.addClass('is-hidden');
                    $next.addClass('is-hidden');
                    return;
                }
                $prev.removeClass('is-hidden');
                $next.removeClass('is-hidden');
                $prev.toggleClass('is-disabled', el.scrollLeft <= 1);
                $next.toggleClass('is-disabled', el.scrollLeft >= max() - 1);
            }

            function step(direction) {
                el.scrollBy({
                    left: direction * Math.max(200, Math.round(el.clientWidth * 0.7)),
                    behavior: 'smooth'
                });
            }

            el.addEventListener('wheel', function (e) {
                if (!scrollable()) return;
                var delta = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;
                if (!delta) return;
                if ((delta < 0 && el.scrollLeft <= 0) || (delta > 0 && el.scrollLeft >= max() - 1)) return;
                e.preventDefault();
                el.scrollLeft += delta;
            }, { passive: false });

            el.addEventListener('pointerdown', function (e) {
                if (e.pointerType !== 'mouse' || !scrollable()) return;
                down = true;
                moved = false;
                startX = e.clientX;
                startScroll = el.scrollLeft;
                $scroll.addClass('is-dragging');
            });

            window.addEventListener('pointermove', function (e) {
                if (!down) return;
                var diff = e.clientX - startX;
                if (Math.abs(diff) > 3) moved = true;
                el.scrollLeft = startScroll - diff;
            });

            window.addEventListener('pointerup', function () {
                if (!down) return;
                down = false;
                $scroll.removeClass('is-dragging');
            });

            el.addEventListener('click', function (e) {
                if (moved) {
                    e.preventDefault();
                    e.stopPropagation();
                    moved = false;
                }
            }, true);

            $prev.on('click', function (e) { e.preventDefault(); step(-1); });
            $next.on('click', function (e) { e.preventDefault(); step(1); });
            $scroll.on('scroll', sync);
            $(window).on('resize orientationchange load', sync);
            sync();
        });
    })();

    $(document).on('click', '.hkdev-blog-loadmore', function () {
        var $btn = $(this);
        var $wrap = $btn.closest('[data-hkdev-blog]');
        var $items = $wrap.find('.hkdev-blog-items').first();

        if (!$wrap.length || !$items.length || $btn.hasClass('is-loading')) {
            return;
        }

        var nextPage = (parseInt($btn.data('page'), 10) || 1) + 1;
        var $activeTab = $wrap.find('.hkdev-blog-tab-item.is-active').first();

        var payload = {
            action: MORE_ACTION,
            nonce: NONCE,
            config: $wrap.attr('data-hkdev-blog-config') || '{}',
            paged: nextPage
        };

        // Without tabs the widget's own category filter (inside the config) has
        // to stay untouched.
        if ($activeTab.length) {
            payload.category = $activeTab.attr('data-slug') || '';
        }

        $btn.addClass('is-loading').prop('disabled', true)
            .find('.hkdev-blog-lm-label').text($btn.attr('data-loading-label') || 'Loading...');

        $.post(cfg.ajax_url, payload).done(function (response) {
            var data = (response && response.success && response.data) ? response.data : null;

            if (!data || typeof data.html !== 'string') {
                return;
            }

            if (data.html) {
                $items.append(data.html);
                $wrap.trigger('hkdev:blog-loaded', [data.page || nextPage]);
            }

            if (data.has_more) {
                $btn.data('page', data.page || nextPage).data('maxPages', data.max_pages);
            } else {
                $btn.closest('.hkdev-blog-loadmore-wrap').addClass('is-empty');
            }
        }).always(function () {
            idle($btn);
        });
    });
})(jQuery);
