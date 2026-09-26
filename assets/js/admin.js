/**
 * HKDEV Shop Elements — Admin panel scripts.
 *
 * The "Checkout Fields" tabs are driven by hidden radio inputs so they keep
 * working without JavaScript. This file only adds enhancements: remembering
 * the active tab, row ordering (drag & drop + move buttons) and visual state
 * for the toggle switches.
 */
(function ($) {
    'use strict';

    var TAB_STORAGE_KEY = 'hkdev_active_tab';

    /**
     * Sync the hidden order input + row numbers after a reorder.
     *
     * @param {Object} $tbody The tbody element being sorted.
     */
    function syncOrder($tbody) {
        var group = $tbody.data('group');
        var keys = [];

        $tbody.find('> tr').each(function (index) {
            var $row = $(this);
            $row.find('.hkdev-order-input').val(index);
            $row.find('.hkdev-row-position').text(index + 1);
            keys.push($row.data('key'));
        });

        var $orderInput = $('#hkdev-order-' + group);
        if ($orderInput.length) {
            $orderInput.val(keys.join(','));
        }
    }

    /**
     * Remember / restore the active tab. The actual switching is pure CSS.
     */
    function initTabs() {
        var $radios = $('.hkdev-tab-radio');
        if (!$radios.length) {
            return;
        }

        try {
            var saved = window.localStorage.getItem(TAB_STORAGE_KEY);
            if (saved) {
                var $match = $radios.filter('[value="' + saved + '"]');
                if ($match.length) {
                    $match.prop('checked', true);
                }
            }
        } catch (err) { /* localStorage unavailable */ }

        $radios.on('change', function () {
            try {
                window.localStorage.setItem(TAB_STORAGE_KEY, $(this).val());
            } catch (err) { /* ignore */ }
        });
    }

    /**
     * Move-up / move-down buttons next to each row.
     */
    function initRowButtons() {
        $('.hkdev-fields-table').on('click', '.hkdev-row-up', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            var $prev = $row.prev('tr');
            if ($prev.length) {
                $row.insertBefore($prev);
                syncOrder($row.closest('tbody'));
            }
        }).on('click', '.hkdev-row-down', function (e) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            var $next = $row.next('tr');
            if ($next.length) {
                $row.insertAfter($next);
                syncOrder($row.closest('tbody'));
            }
        });
    }

    /**
     * Initialize sortable tables (jQuery UI with a native fallback).
     */
    function initSortable() {
        $('.hkdev-sortable').each(function () {
            var $tbody = $(this);

            if ($.fn.sortable) {
                try {
                    $tbody.sortable({
                        items: '> tr',
                        handle: '.hkdev-drag-handle',
                        axis: 'y',
                        placeholder: 'hkdev-sortable-placeholder',
                        forcePlaceholderSize: true,
                        tolerance: 'pointer',
                        start: function (event, ui) {
                            ui.item.addClass('hkdev-dragging');
                        },
                        stop: function (event, ui) {
                            ui.item.removeClass('hkdev-dragging');
                        },
                        update: function () {
                            syncOrder($tbody);
                        }
                    });
                    return;
                } catch (err) {
                    // Fall through to the native implementation below.
                }
            }

            initNativeDragDrop($tbody);
        });
    }

    /**
     * Native HTML5 drag and drop fallback.
     *
     * @param {Object} $tbody The tbody element.
     */
    function initNativeDragDrop($tbody) {
        var draggedRow = null;

        $tbody.find('tr').attr('draggable', 'true').on('dragstart', function (e) {
            if (!$(e.originalEvent.target).closest('.hkdev-drag-handle').length) {
                e.preventDefault();
                return;
            }
            draggedRow = this;
            $(this).addClass('hkdev-dragging');
            try {
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                e.originalEvent.dataTransfer.setData('text/plain', $(this).data('key'));
            } catch (err) { /* ignore */ }
        }).on('dragend', function () {
            $(this).removeClass('hkdev-dragging');
            $tbody.find('tr').removeClass('hkdev-drop-above hkdev-drop-below');
            draggedRow = null;
            syncOrder($tbody);
        });

        $tbody.on('dragover', function (e) {
            e.preventDefault();
            try {
                e.originalEvent.dataTransfer.dropEffect = 'move';
            } catch (err) { /* ignore */ }
            if (!draggedRow) {
                return;
            }

            var $target = $(e.target).closest('tr');
            if (!$target.length || $target[0] === draggedRow) {
                return;
            }

            $tbody.find('tr').removeClass('hkdev-drop-above hkdev-drop-below');
            var rect = $target[0].getBoundingClientRect();
            if (e.originalEvent.clientY > rect.top + rect.height / 2) {
                $target.addClass('hkdev-drop-below');
            } else {
                $target.addClass('hkdev-drop-above');
            }
        }).on('drop', function (e) {
            e.preventDefault();
            var $target = $(e.target).closest('tr');
            if (!draggedRow || !$target.length || $target[0] === draggedRow) {
                $tbody.find('tr').removeClass('hkdev-drop-above hkdev-drop-below');
                return;
            }

            var rect = $target[0].getBoundingClientRect();
            if (e.originalEvent.clientY > rect.top + rect.height / 2) {
                $target.after(draggedRow);
            } else {
                $target.before(draggedRow);
            }

            $(draggedRow).removeClass('hkdev-dragging');
            $tbody.find('tr').removeClass('hkdev-drop-above hkdev-drop-below');
            draggedRow = null;
            syncOrder($tbody);
        });
    }

    /**
     * Keep the row's "disabled" styling in sync with its toggle switches.
     */
    function initToggles() {
        $('.hkdev-toggle input').each(function () {
            var $input = $(this);
            var $row = $input.closest('tr');

            if (!$row.length) {
                return;
            }

            $input.on('change', function () {
                $row.toggleClass('hkdev-field-disabled', !$input.is(':checked'));
            }).trigger('change');
        });
    }

    /**
     * Small focus highlight for the text inputs.
     */
    function initFieldInputs() {
        $('.hkdev-field-input')
            .on('focus', function () {
                $(this).closest('tr').addClass('hkdev-field-focused');
            })
            .on('blur', function () {
                $(this).closest('tr').removeClass('hkdev-field-focused');
            });
    }

    /**
     * Turn the accent colour field into a WordPress colour picker.
     */
    function initColorPickers() {
        if (!$.fn.wpColorPicker) {
            return;
        }

        $('.hkdev-color-input').each(function () {
            var $input = $(this);
            if ($input.data('hkdevColorReady')) {
                return;
            }
            $input.data('hkdevColorReady', true);
            $input.wpColorPicker({
                change: function (event, ui) {
                    var hex = ui.color.toString();
                    var key = $input.closest('[data-color-key]').data('color-key');
                    if (key) {
                        $('.hkdev-theme-live-swatch[data-color-key="' + key + '"]').css('background-color', hex);
                    }
                },
                clear: function () {
                    var key = $input.closest('[data-color-key]').data('color-key');
                    if (key) {
                        $('.hkdev-theme-live-swatch[data-color-key="' + key + '"]').css('background-color', 'transparent');
                    }
                }
            });
        });
    }

    /**
     * Widget manager grid — card state + bulk enable/disable controls.
     */
    function initWidgetManager() {
        var $wrap = $('.hkdev-widget-manager-wrap');
        if (!$wrap.length) {
            return;
        }

        function syncCard($card) {
            var $input = $card.find('.hkdev-toggle input');
            $card.toggleClass('is-enabled', $input.is(':checked'));
            $card.toggleClass('is-disabled', !$input.is(':checked'));
        }

        $wrap.on('change', '.hkdev-widget-card .hkdev-toggle input', function () {
            syncCard($(this).closest('.hkdev-widget-card'));
        });

        $wrap.on('click', '.hkdev-widget-card', function (e) {
            if ($(e.target).closest('.hkdev-toggle').length) {
                return;
            }
            var $input = $(this).find('.hkdev-toggle input');
            $input.prop('checked', !$input.is(':checked')).trigger('change');
        });

        $wrap.on('click', '.hkdev-widget-enable-group', function (e) {
            e.preventDefault();
            $(this).closest('.hkdev-widget-group').find('.hkdev-toggle input').prop('checked', true).trigger('change');
        });

        $wrap.on('click', '.hkdev-widget-disable-group', function (e) {
            e.preventDefault();
            $(this).closest('.hkdev-widget-group').find('.hkdev-toggle input').prop('checked', false).trigger('change');
        });

        $wrap.on('click', '.hkdev-widget-enable-all', function (e) {
            e.preventDefault();
            $wrap.find('.hkdev-toggle input').prop('checked', true).trigger('change');
        });

        $wrap.on('click', '.hkdev-widget-disable-all', function (e) {
            e.preventDefault();
            $wrap.find('.hkdev-toggle input').prop('checked', false).trigger('change');
        });
    }

    /**
     * Run every enhancement independently so a single failure can never stop
     * the others (in particular the tabs).
     */
    function init() {
        [
            initTabs,
            initRowButtons,
            initSortable,
            initToggles,
            initFieldInputs,
            initColorPickers,
            initWidgetManager
        ].forEach(function (setup) {
            try {
                setup();
            } catch (err) {
                if (window.console && window.console.error) {
                    window.console.error('[hkdev-admin]', err);
                }
            }
        });
    }

    $(init);

})(jQuery);
