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

            var $iframe = $('<iframe>', {
                src: embed,
                title: $link.attr('aria-label') || 'YouTube video',
                frameborder: '0',
                allow: 'accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture',
                allowfullscreen: 'allowfullscreen'
            });

            $link.removeClass('hkdev-yt-lite').addClass('hkdev-yt-playing').empty().append($iframe);
        });
    });
}(jQuery));
