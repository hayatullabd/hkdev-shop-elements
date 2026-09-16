/**
 * HKDEV Blog — category tab filtering.
 *
 * Mirrors the shop grid behaviour: clicking a tab swaps the card list for that
 * category without reloading the page. The widget's own settings travel in the
 * `data-hkdev-blog-config` JSON attribute, so the AJAX request rebuilds exactly
 * the same query as the first render.
 *
 * @package HkdevShopElements
 */
(function ($) {
    'use strict';

    var cfg = (typeof hkdev_elements_ajax !== 'undefined') ? hkdev_elements_ajax : null;

    if (!cfg || !cfg.ajax_url) {
        return;
    }

    var ACTION = 'hkdev_elements_filter_blog';
    var NONCE = (cfg.nonces && cfg.nonces.blog_filter) ? cfg.nonces.blog_filter : '';

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
            action: ACTION,
            nonce: NONCE,
            config: $wrap.attr('data-hkdev-blog-config') || '{}',
            category: $btn.attr('data-slug') || ''
        }).done(function (response) {
            if (response && response.success && response.data && typeof response.data.html === 'string') {
                $items.html(response.data.html);

                // Let themes / other plugins react (e.g. re-init lazy loaders).
                $wrap.trigger('hkdev:blog-filtered', [response.data.count || 0]);
            }
        }).always(function () {
            $btn.removeClass('is-loading');
            $loader.removeClass('is-visible');
        });
    });
})(jQuery);
