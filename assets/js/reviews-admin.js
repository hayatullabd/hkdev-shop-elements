/**
 * HKDEV Customer Reviews — admin repeater + media pickers + product search.
 */
(function ($) {
    'use strict';

    function reindexRepeater($list, prefix) {
        $list.find('.hkdev-rv-repeater-item').each(function (index) {
            var $item = $(this);
            $item.attr('data-index', index);
            $item.find('[name^="' + prefix + '"]').each(function () {
                var $el = $(this);
                var name = $el.attr('name');
                if (!name) {
                    return;
                }
                $el.attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
            });
        });
    }

    function emptyLabel($item) {
        var $rep = $item.closest('.hkdev-rv-repeater');
        return ($rep.data('empty-label') || 'New item').toString();
    }

    function syncVideoHead($item) {
        var name = $.trim($item.find('.hkdev-rv-name-input').val());
        $item.find('.hkdev-rv-accordion-title').text(name || emptyLabel($item));
        var rating = parseInt($item.find('input[name*="[rating]"]').val(), 10) || 5;
        rating = Math.max(1, Math.min(5, rating));
        $item.find('.hkdev-rv-meta-rating').text(rating + '★');
    }

    function syncProofHead($item) {
        var name = $.trim($item.find('.hkdev-rv-name-input').val());
        $item.find('.hkdev-rv-accordion-title').text(name || emptyLabel($item));
        var rating = parseInt($item.find('input[name*="[rating]"]').val(), 10) || 5;
        rating = Math.max(1, Math.min(5, rating));
        $item.find('.hkdev-rv-meta-rating').text(rating + '★');
        var count = $item.find('.hkdev-rv-gallery-previews img').length;
        $item.find('.hkdev-rv-meta-images').text(count === 1 ? '1 img' : count + ' imgs');
        var $thumb = $item.find('.hkdev-rv-accordion-thumb').first();
        var src = $item.find('.hkdev-rv-gallery-previews img').first().attr('src') || '';
        if (src) {
            $thumb.attr('src', src).prop('hidden', false).addClass('is-visible');
        } else {
            $thumb.attr('src', '').prop('hidden', true).removeClass('is-visible');
        }
    }

    function syncVideoThumb($item, url) {
        var $thumb = $item.find('.hkdev-rv-accordion-thumb').first();
        if (url) {
            $thumb.attr('src', url).prop('hidden', false).addClass('is-visible');
        } else {
            $thumb.attr('src', '').prop('hidden', true).removeClass('is-visible');
        }
    }

    function openAccordionItem($item) {
        $item.addClass('is-open');
        $item.find('.hkdev-rv-accordion-toggle').attr('aria-expanded', 'true');
    }

    function initAccordion($root) {
        $root.on('click', '.hkdev-rv-accordion-toggle', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.hkdev-rv-accordion-item');
            var willOpen = !$item.hasClass('is-open');

            $item.toggleClass('is-open', willOpen);
            $(this).attr('aria-expanded', willOpen ? 'true' : 'false');
        });
    }

    function destroyProductSelect($select) {
        if (!$select || !$select.length) {
            return;
        }
        if ($select.hasClass('enhanced') && $.fn.selectWoo) {
            try {
                $select.selectWoo('destroy');
            } catch (err) { /* ignore */ }
        }
        $select.removeClass('enhanced select2-hidden-accessible');
        $select.siblings('.select2-container').remove();
    }

    function resetProductSelect($select) {
        destroyProductSelect($select);
        $select.empty().append($('<option value=""></option>'));
    }

    function normalizeProductResults(data) {
        var terms = [];

        if (!data || data === 0 || data === '0' || data === -1 || data === '-1') {
            return { results: [] };
        }

        if (data.results && $.isArray(data.results)) {
            return { results: data.results };
        }

        if ($.isArray(data)) {
            $.each(data, function (i, row) {
                if (row && row.id) {
                    terms.push({ id: row.id, text: row.text });
                }
            });
            return { results: terms };
        }

        if (typeof data === 'object') {
            $.each(data, function (id, text) {
                terms.push({ id: id, text: text });
            });
        }

        return { results: terms };
    }

    function initProductSelect($scope) {
        if (!$.fn.selectWoo) {
            return;
        }

        var $root = $scope && $scope.length ? $scope : $('.hkdev-reviews-admin');
        var cfg = window.hkdevRvAdmin || {};
        var ajaxUrl = cfg.ajaxUrl || window.ajaxurl || '';

        $root.find('.hkdev-rv-product-search').filter(':not(.enhanced)').each(function () {
            var $el = $(this);

            $el.selectWoo({
                allowClear: true,
                placeholder: $el.data('placeholder') || cfg.placeholder || '',
                width: '100%',
                minimumInputLength: parseInt($el.data('minimum_input_length'), 10) || cfg.minInput || 1,
                ajax: {
                    url: ajaxUrl,
                    type: 'GET',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        var term = params.term || '';

                        if (cfg.wcSearchNonce) {
                            return {
                                term: term,
                                action: cfg.wcSearchAction || 'woocommerce_json_search_products',
                                security: cfg.wcSearchNonce
                            };
                        }

                        return {
                            term: term,
                            action: cfg.searchAction || 'hkdev_rv_search_products',
                            security: cfg.searchNonce || ''
                        };
                    },
                    transport: function (params, success, failure) {
                        var request = $.ajax(params);

                        request.then(function (data) {
                            success(data);
                        }, function (xhr) {
                            if (cfg.searchNonce && cfg.wcSearchNonce) {
                                var fallback = $.ajax({
                                    url: ajaxUrl,
                                    type: 'GET',
                                    dataType: 'json',
                                    data: {
                                        term: params.data.term,
                                        action: cfg.searchAction || 'hkdev_rv_search_products',
                                        security: cfg.searchNonce
                                    }
                                });
                                fallback.then(success, failure);
                                return;
                            }
                            failure(xhr);
                        });

                        return request;
                    },
                    processResults: function (data) {
                        return normalizeProductResults(data);
                    },
                    cache: true
                }
            }).addClass('enhanced');
        });
    }

    function initReviewTabs() {
        var $wrap = $('.hkdev-rv-tabs-wrap');
        if (!$wrap.length) {
            return;
        }

        var key = 'hkdev_rv_active_tab';
        try {
            var saved = window.localStorage.getItem(key);
            if (saved) {
                var $radio = $wrap.find('.hkdev-tab-radio[value="' + saved + '"]');
                if ($radio.length) {
                    $radio.prop('checked', true);
                }
            }
        } catch (err) { /* ignore */ }

        $wrap.on('change', '.hkdev-tab-radio', function () {
            try {
                window.localStorage.setItem(key, $(this).val());
            } catch (err) { /* ignore */ }
        });
    }

    function initRepeaters() {
        var $root = $('.hkdev-reviews-admin');
        if (!$root.length) {
            return;
        }

        $root.on('click', '.hkdev-rv-add-row', function (e) {
            e.preventDefault();
            var type = $(this).data('type');
            var $repeater = $(this).closest('.hkdev-rv-repeater');
            var $list = $repeater.find('.hkdev-rv-repeater-list');
            var $clone = $list.find('.hkdev-rv-repeater-item').last().clone(false, false);

            $clone.find('.select2-container').remove();
            $clone.find('input[type="text"], input[type="url"], input[type="number"], textarea').val('');
            $clone.find('input[type="number"]').filter('[name*="[rating]"]').val('5');
            resetProductSelect($clone.find('.hkdev-rv-product-search'));
            $clone.find('.hkdev-rv-thumb-id, .hkdev-rv-gallery-ids, .hkdev-rv-thumb-url').val('');
            $clone.find('.hkdev-rv-thumb-preview').removeClass('is-visible').attr('src', '');
            $clone.find('.hkdev-rv-gallery-previews').empty();
            $clone.removeClass('is-open');
            $clone.find('.hkdev-rv-accordion-toggle').attr('aria-expanded', 'false');

            $list.append($clone);

            if (type === 'video') {
                reindexRepeater($list, 'hkdev_rv_videos');
                syncVideoHead($clone);
                syncVideoThumb($clone, '');
            } else {
                reindexRepeater($list, 'hkdev_rv_proofs');
                syncProofHead($clone);
            }

            $list.find('.hkdev-rv-accordion-item').not($clone).removeClass('is-open').find('.hkdev-rv-accordion-toggle').attr('aria-expanded', 'false');
            openAccordionItem($clone);

            initProductSelect($clone);
        });

        $root.on('click', '.hkdev-rv-remove-row', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $item = $(this).closest('.hkdev-rv-repeater-item');
            var $list = $item.closest('.hkdev-rv-repeater-list');
            var prefix = $list.closest('.hkdev-rv-repeater').data('type') === 'video' ? 'hkdev_rv_videos' : 'hkdev_rv_proofs';

            if ($list.find('.hkdev-rv-repeater-item').length <= 1) {
                $item.find('input[type="text"], input[type="url"], textarea').val('');
                $item.find('input[type="number"]').val('5');
                resetProductSelect($item.find('.hkdev-rv-product-search'));
                $item.find('.hkdev-rv-thumb-id, .hkdev-rv-gallery-ids, .hkdev-rv-thumb-url').val('');
                $item.find('.hkdev-rv-thumb-preview').removeClass('is-visible').attr('src', '');
                $item.find('.hkdev-rv-gallery-previews').empty();
                syncVideoHead($item);
                syncProofHead($item);
                return;
            }

            $item.remove();
            reindexRepeater($list, prefix);
        });

        $root.on('input', '.hkdev-rv-repeater[data-type="video"] .hkdev-rv-name-input', function () {
            syncVideoHead($(this).closest('.hkdev-rv-repeater-item'));
        });

        $root.on('input', '.hkdev-rv-repeater[data-type="proof"] .hkdev-rv-name-input', function () {
            syncProofHead($(this).closest('.hkdev-rv-repeater-item'));
        });

        $root.on('input change', '.hkdev-rv-repeater[data-type="video"] input[name*="[rating]"]', function () {
            syncVideoHead($(this).closest('.hkdev-rv-repeater-item'));
        });

        $root.on('input change', '.hkdev-rv-repeater[data-type="proof"] input[name*="[rating]"]', function () {
            syncProofHead($(this).closest('.hkdev-rv-repeater-item'));
        });
    }

    function parseGalleryIds(raw) {
        if (!raw) {
            return [];
        }
        return String(raw)
            .split(',')
            .map(function (part) {
                return parseInt(part.trim(), 10);
            })
            .filter(function (id) {
                return id > 0;
            });
    }

    function appendGalleryThumb($prev, att) {
        var id = att.id;
        var src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
        var $wrap = $('<span class="hkdev-rv-gallery-thumb"></span>').attr('data-id', id);
        $wrap.append($('<img>', { src: src, alt: '' }));
        $wrap.append(
            $('<button type="button" class="hkdev-rv-gallery-remove" aria-label="Remove"></button>').text('\u00d7')
        );
        $prev.append($wrap);
    }

    function setGalleryIds($item, ids) {
        var unique = [];
        $.each(ids, function (_, id) {
            id = parseInt(id, 10);
            if (id > 0 && unique.indexOf(id) === -1) {
                unique.push(id);
            }
        });
        $item.find('.hkdev-rv-gallery-ids').val(unique.join(','));
        syncProofHead($item);
    }

    function initMedia() {
        if (typeof wp === 'undefined' || !wp.media) {
            return;
        }

        var $root = $('.hkdev-reviews-admin');

        $root.on('click', '.hkdev-rv-pick-thumb', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.hkdev-rv-repeater-item');
            var frame = wp.media({
                title: 'Select thumbnail',
                button: { text: 'Use image' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $item.find('.hkdev-rv-thumb-id').val(attachment.id);
                $item.find('.hkdev-rv-thumb-url').val(attachment.url);
                $item.find('.hkdev-rv-thumb-preview').addClass('is-visible').attr('src', attachment.url);
                syncVideoThumb($item, attachment.url);
            });

            frame.open();
        });

        $root.on('click', '.hkdev-rv-clear-thumb', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.hkdev-rv-repeater-item');
            $item.find('.hkdev-rv-thumb-id, .hkdev-rv-thumb-url').val('');
            $item.find('.hkdev-rv-thumb-preview').removeClass('is-visible').attr('src', '');
            syncVideoThumb($item, '');
        });

        $root.on('click', '.hkdev-rv-pick-gallery', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.hkdev-rv-repeater-item');
            var existingIds = parseGalleryIds($item.find('.hkdev-rv-gallery-ids').val());

            var frame = wp.media({
                title: 'Review images (select multiple)',
                button: { text: 'Use selected images' },
                multiple: 'add',
                library: { type: 'image' }
            });

            frame.on('open', function () {
                var selection = frame.state().get('selection');
                selection.reset();
                existingIds.forEach(function (id) {
                    var attachment = wp.media.attachment(id);
                    attachment.fetch();
                    selection.add(attachment);
                });
            });

            frame.on('select', function () {
                var ids = [];
                var $prev = $item.find('.hkdev-rv-gallery-previews').empty();
                frame.state().get('selection').each(function (model) {
                    var att = model.toJSON();
                    ids.push(att.id);
                    appendGalleryThumb($prev, att);
                });
                setGalleryIds($item, ids);
            });

            frame.open();
        });

        $root.on('click', '.hkdev-rv-gallery-remove', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $thumb = $(this).closest('.hkdev-rv-gallery-thumb');
            var removeId = parseInt($thumb.attr('data-id'), 10);
            var $item = $(this).closest('.hkdev-rv-repeater-item');
            var ids = parseGalleryIds($item.find('.hkdev-rv-gallery-ids').val()).filter(function (id) {
                return id !== removeId;
            });
            $thumb.remove();
            setGalleryIds($item, ids);
        });

        $root.on('click', '.hkdev-rv-clear-gallery', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.hkdev-rv-repeater-item');
            $item.find('.hkdev-rv-gallery-ids').val('');
            $item.find('.hkdev-rv-gallery-previews').empty();
            syncProofHead($item);
        });
    }

    function syncExpectedRowCounts($root) {
        $root.find('.hkdev-rv-repeater').each(function () {
            var $rep = $(this);
            var count = $rep.find('.hkdev-rv-repeater-list .hkdev-rv-repeater-item').length;
            $rep.find('.hkdev-rv-expected-rows').first().val(String(Math.max(0, count)));
        });
    }

    function syncProductSelectValues($root) {
        $root.find('.hkdev-rv-product-search').each(function () {
            var $el = $(this);
            var val = '';

            if ($el.hasClass('enhanced') && $.fn.selectWoo) {
                try {
                    val = $el.selectWoo('val');
                } catch (err) {
                    val = $el.val();
                }
            } else {
                val = $el.val();
            }

            if ($.isArray(val)) {
                val = val.length ? val[0] : '';
            }

            $el.val(val || '');
        });
    }

    $(function () {
        var $root = $('.hkdev-reviews-admin');
        initReviewTabs();
        initAccordion($root);
        initRepeaters();
        initMedia();
        initProductSelect($root);

        $('#hkdev-reviews-settings').on('submit', function () {
            syncExpectedRowCounts($root);
            syncProductSelectValues($root);
        });
    });
})(jQuery);
