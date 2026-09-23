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

    function syncVideoHead($item) {
        var name = $.trim($item.find('.hkdev-rv-name-input').val());
        $item.find('.hkdev-rv-repeater-head strong').text(name || 'Video review');
    }

    function syncProofHead($item) {
        var name = $.trim($item.find('.hkdev-rv-name-input').val());
        $item.find('.hkdev-rv-repeater-head strong').text(name || 'Social proof');
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

            $list.append($clone);

            if (type === 'video') {
                reindexRepeater($list, 'hkdev_rv_videos');
                syncVideoHead($clone);
            } else {
                reindexRepeater($list, 'hkdev_rv_proofs');
                syncProofHead($clone);
            }

            initProductSelect($clone);
        });

        $root.on('click', '.hkdev-rv-remove-row', function (e) {
            e.preventDefault();
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
            });

            frame.open();
        });

        $root.on('click', '.hkdev-rv-clear-thumb', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.hkdev-rv-repeater-item');
            $item.find('.hkdev-rv-thumb-id, .hkdev-rv-thumb-url').val('');
            $item.find('.hkdev-rv-thumb-preview').removeClass('is-visible').attr('src', '');
        });

        $root.on('click', '.hkdev-rv-pick-gallery', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.hkdev-rv-repeater-item');
            var frame = wp.media({
                title: 'Select review images',
                button: { text: 'Use images' },
                multiple: true,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var ids = [];
                var $prev = $item.find('.hkdev-rv-gallery-previews').empty();
                frame.state().get('selection').each(function (model) {
                    var att = model.toJSON();
                    ids.push(att.id);
                    $prev.append($('<img>', { src: att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url, alt: '' }));
                });
                $item.find('.hkdev-rv-gallery-ids').val(ids.join(','));
            });

            frame.open();
        });

        $root.on('click', '.hkdev-rv-clear-gallery', function (e) {
            e.preventDefault();
            var $item = $(this).closest('.hkdev-rv-repeater-item');
            $item.find('.hkdev-rv-gallery-ids').val('');
            $item.find('.hkdev-rv-gallery-previews').empty();
        });
    }

    $(function () {
        initReviewTabs();
        initRepeaters();
        initMedia();
        initProductSelect($('.hkdev-reviews-admin'));
    });
})(jQuery);
