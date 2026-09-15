/**
 * HKDEV Customer Reviews — tabs, modals, image slider and sorting.
 */
(function ($) {
    'use strict';

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function nl2br(value) {
        return escapeHtml(value).replace(/\r?\n/g, '<br>');
    }

    function initRoot($root) {
        var data = { videos: [], proofs: [] };

        var $dataEl = $root.find('.hkdev-rv-json').first();
        if ($dataEl.length) {
            try {
                var parsed = JSON.parse($dataEl.text());
                if (parsed) {
                    data = parsed;
                }
            } catch (e) {
                data = { videos: [], proofs: [] };
            }
        }
        if (!$.isArray(data.videos)) {
            data.videos = [];
        }
        if (!$.isArray(data.proofs)) {
            data.proofs = [];
        }

        /* ---------------- Tabs ---------------- */
        var $tabBtns = $root.find('.hkdev-rv-tab-btn');
        var $panels = $root.find('.hkdev-rv-panel');

        $tabBtns.on('click', function () {
            var tab = $(this).data('tab');
            $tabBtns.removeClass('is-active').attr('aria-selected', 'false');
            $(this).addClass('is-active').attr('aria-selected', 'true');
            $panels.removeClass('is-active');
            $root.find('.hkdev-rv-panel[data-panel="' + tab + '"]').addClass('is-active');
        });

        /* ---------------- Sorting ---------------- */
        var $grid = $root.find('.hkdev-rv-proof-grid');
        var $select = $root.find('.hkdev-rv-filter-select');

        function applySort(mode) {
            if (!$grid.length) {
                return;
            }

            var cards = $grid.children('.hkdev-rv-pcard').get();

            cards.sort(function (a, b) {
                var orderA = parseInt($(a).attr('data-order'), 10) || 0;
                var orderB = parseInt($(b).attr('data-order'), 10) || 0;

                if (mode === 'highest') {
                    var ratingA = parseInt($(a).attr('data-rating'), 10) || 0;
                    var ratingB = parseInt($(b).attr('data-rating'), 10) || 0;
                    if (ratingB !== ratingA) {
                        return ratingB - ratingA;
                    }
                }

                return orderA - orderB;
            });

            $.each(cards, function (i, el) {
                $grid.append(el);
            });
        }

        var initialSort = $select.length ? $select.val() : $grid.attr('data-sort');
        applySort(initialSort || 'recent');

        $select.on('change', function () {
            applySort($(this).val());
        });

        /* ---------------- Video modal ---------------- */
        var $vmodal = $root.find('.hkdev-rv-vmodal').first();
        var $vmedia = $vmodal.find('.hkdev-rv-vmodal-media');

        function openVideo(index) {
            var item = data.videos[index];
            if (!item) {
                return;
            }

            var media = item.media || {};
            if (media.type === 'video') {
                $vmedia.html('<video src="' + escapeHtml(media.url) + '" controls autoplay playsinline></video>');
            } else if (media.type === 'iframe') {
                // referrerpolicy is required by YouTube: without a Referer the
                // player answers "Error 153: Video player configuration error".
                var frameId = 'hkdev-rv-player-' + Date.now();
                $vmedia.html(
                    '<iframe id="' + frameId + '"' +
                    ' src="' + escapeHtml(media.url) + '"' +
                    ' title="' + escapeHtml(item.name || 'Video review') + '"' +
                    ' referrerpolicy="strict-origin-when-cross-origin"' +
                    ' frameborder="0"' +
                    ' allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share"' +
                    ' allowfullscreen></iframe>'
                );
            } else {
                $vmedia.empty();
            }

            $vmodal.find('.hkdev-rv-vmodal-name').text(item.name || '');
            $vmodal.find('.hkdev-rv-vmodal-stars').html(item.stars || '');
            $vmodal.find('.hkdev-rv-vmodal-badge').text(item.badge || '');
            $vmodal.find('.hkdev-rv-vmodal-quote').html(item.quote ? nl2br(item.quote) : '');
            $vmodal.find('.hkdev-rv-vmodal-product').html(item.product || '');

            $vmodal.prop('hidden', false);
            $('body').addClass('hkdev-rv-locked');
        }

        $root.on('click', '.hkdev-rv-vcard', function () {
            openVideo($(this).attr('data-open-video'));
        });

        $root.on('keydown', '.hkdev-rv-vcard', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openVideo($(this).attr('data-open-video'));
            }
        });

        /* ---------------- Proof (image) modal ---------------- */
        var $pmodal = $root.find('.hkdev-rv-pmodal').first();
        var $sliderImg = $pmodal.find('.hkdev-rv-slider-img');
        var $sliderBtn = $pmodal.find('.hkdev-rv-slider-btn');
        var $sliderCounter = $pmodal.find('.hkdev-rv-slider-counter');
        var slider = { images: [], index: 0 };

        function renderSlide() {
            var total = slider.images.length;

            if (!total) {
                $sliderImg.attr('src', '').hide();
                $sliderBtn.hide();
                $sliderCounter.text('');
                return;
            }

            if (slider.index < 0) {
                slider.index = total - 1;
            }
            if (slider.index >= total) {
                slider.index = 0;
            }

            $sliderImg.attr('src', slider.images[slider.index]).show();

            if (total > 1) {
                $sliderBtn.show();
                $sliderCounter.text((slider.index + 1) + ' / ' + total);
            } else {
                $sliderBtn.hide();
                $sliderCounter.text('');
            }
        }

        function openProof(index) {
            var item = data.proofs[index];
            if (!item) {
                return;
            }

            slider.images = item.images || [];
            slider.index = 0;
            renderSlide();

            $pmodal.find('.hkdev-rv-pmodal-name').text(item.name || '');
            $pmodal.find('.hkdev-rv-pmodal-badge').text(item.badge || '');
            $pmodal.find('.hkdev-rv-pmodal-stars').html(item.stars || '');
            $pmodal.find('.hkdev-rv-pmodal-quote').html(item.quote ? nl2br(item.quote) : '');
            $pmodal.find('.hkdev-rv-pmodal-product').html(item.product || '');

            $pmodal.prop('hidden', false);
            $('body').addClass('hkdev-rv-locked');
        }

        $root.on('click', '.hkdev-rv-pcard', function () {
            openProof($(this).attr('data-open-proof'));
        });

        $root.on('keydown', '.hkdev-rv-pcard', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openProof($(this).attr('data-open-proof'));
            }
        });

        $pmodal.on('click', '.hkdev-rv-prev', function (e) {
            e.stopPropagation();
            slider.index -= 1;
            renderSlide();
        });

        $pmodal.on('click', '.hkdev-rv-next', function (e) {
            e.stopPropagation();
            slider.index += 1;
            renderSlide();
        });

        /* ---------------- Close ---------------- */
        function closeModals() {
            $root.find('.hkdev-rv-vmodal, .hkdev-rv-pmodal').prop('hidden', true);
            $vmedia.empty();
            $sliderImg.attr('src', '');
            $('body').removeClass('hkdev-rv-locked');
        }

        $root.on('click', '.hkdev-rv-modal-close, .hkdev-rv-pmodal-close', function (e) {
            e.stopPropagation();
            closeModals();
        });

        $root.find('.hkdev-rv-vmodal, .hkdev-rv-pmodal').on('click', function (e) {
            if (e.target === this) {
                closeModals();
            }
        });
    }

    $(function () {
        $('.hkdev-rv').each(function () {
            initRoot($(this));
        });

        $(document).on('keydown', function (e) {
            if (e.key !== 'Escape') {
                return;
            }
            $('.hkdev-rv-vmodal:not([hidden]), .hkdev-rv-pmodal:not([hidden])').each(function () {
                var $modal = $(this);
                $modal.prop('hidden', true);
                $modal.find('.hkdev-rv-vmodal-media').empty();
                $modal.find('.hkdev-rv-slider-img').attr('src', '');
            });
            $('body').removeClass('hkdev-rv-locked');
        });
    });
}(jQuery));
