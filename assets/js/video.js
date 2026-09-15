/**
 * HKDEV Video Embed — lite YouTube poster (loads the player on click).
 */
(function ($) {
    'use strict';

    // High-res posters are not available for every video; fall back to the
    // standard one. Image error events do not bubble, so bind each image
    // directly instead of delegating from the document.
    function bindPosterFallback($scope) {
        var $images = $scope ? $scope.find('.hkdev-yt-lite img') : $('.hkdev-yt-lite img');

        $images.each(function () {
            var img = this;

            if (img.dataset.hkdevFallbackBound) {
                return;
            }
            img.dataset.hkdevFallbackBound = '1';

            var fallback = $(img).closest('.hkdev-yt-lite').attr('data-youtube-fallback');

            if (!fallback) {
                return;
            }

            img.onerror = function () {
                if (img.dataset.hkdevFallback) {
                    return;
                }
                img.dataset.hkdevFallback = '1';
                img.src = fallback;
            };

            if (img.complete && img.naturalWidth === 0) {
                img.onerror();
            }
        });
    }

    // Elementor renders - and re-renders - widgets long after DOM ready, so the
    // poster fallback has to be rebound for each fresh render.
    function hookElementor() {
        if (!window.elementorFrontend || !elementorFrontend.hooks) {
            return false;
        }

        elementorFrontend.hooks.addAction('frontend/element_ready/global', function ($scope) {
            bindPosterFallback($scope);
        });

        return true;
    }

    $(function () {
        bindPosterFallback();

        // If this file loads after Elementor already fired its init event, the
        // listener below would never run - hook straight away in that case.
        if (!hookElementor()) {
            $(window).on('elementor/frontend/init', hookElementor);
        }

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
