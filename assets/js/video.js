/**
 * HKDEV Video Embed — lite YouTube poster (loads the player on click).
 */
(function ($) {
    'use strict';

    $(function () {
        // High-res posters are not always available; fall back to hqdefault.
        $(document).on('error', '.hkdev-yt-lite img', function () {
            var fallback = $(this).closest('.hkdev-yt-lite').attr('data-youtube-fallback');

            if (fallback && !this.dataset.hkdevFallback) {
                this.dataset.hkdevFallback = '1';
                this.src = fallback;
            }
        });

        $(document).on('click', '.hkdev-yt-lite', function (e) {
            var $link = $(this);
            var embed = $link.attr('data-youtube-embed');

            // No embed URL (or no JS support for it): let the link open YouTube.
            if (!embed) {
                return;
            }

            e.preventDefault();

            // referrerpolicy is required by YouTube: without a Referer the
            // player answers "Error 153: Video player configuration error".
            var $iframe = $('<iframe>', {
                src: embed,
                title: $link.attr('aria-label') || 'YouTube video',
                frameborder: '0',
                referrerpolicy: 'strict-origin-when-cross-origin',
                allow: 'accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share',
                allowfullscreen: 'allowfullscreen'
            });

            // Keep .hkdev-yt-lite: the stylesheet positions the iframe through
            // it. The extra state class only disables the poster overlay/hover.
            $link.addClass('hkdev-yt-playing').empty().append($iframe);
        });
    });
}(jQuery));
