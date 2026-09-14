/**
 * HKDEV Order Tracking — AJAX tracking form.
 */
(function ($) {
    'use strict';

    $(function () {
        var $root = $('#hkdev-tracking-root');
        if (!$root.length) {
            return;
        }

        var $form = $root.find('#hkdev-tracking-form');
        var $message = $root.find('.hkdev-tracking-message');
        var $result = $root.find('.hkdev-tracking-result');

        function showMessage(type, message) {
            $message.removeClass('is-error is-success')
                .addClass('is-' + type)
                .html(message)
                .show();
        }

        $form.on('submit', function (e) {
            e.preventDefault();
            var $btn = $form.find('.hkdev-tracking-submit');
            var $spinner = $btn.find('.hkdev-tracking-spinner');
            var $btnText = $btn.find('.hkdev-tracking-btn-text');

            $result.removeClass('is-visible');
            $message.hide();
            $btn.prop('disabled', true);
            $spinner.show();
            $btnText.hide();

            $.ajax({
                url: hkdevElementsAjax.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=hkdev_elements_track_order',
                dataType: 'json',
                success: function (response) {
                    $btn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.show();

                    if (response.success) {
                        showMessage('success', response.data.message);
                        renderTrackingResults(response.data.orders);
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.show();
                    showMessage('error', 'An error occurred. Please try again.');
                }
            });
        });

        function buildOrderHtml(data) {
            var html = '';

            // Header
            html += '<div class="hkdev-tracking-result-header">';
            html += '<h4>Order #' + data.order_id + '</h4>';
            html += '<span class="hkdev-tracking-result-status status-' + data.status + '">' + data.status_name + '</span>';
            html += '</div>';

            // Progress Steps
            if (data.status_progress) {
                html += '<div class="hkdev-tracking-progress"><div class="hkdev-tracking-progress-steps">';
                $.each(data.status_progress, function (key, step) {
                    html += '<div class="hkdev-tracking-step ' + step.state + '">';
                    html += '<div class="hkdev-tracking-step-icon"><i class="fa-solid ' + step.icon + '"></i></div>';
                    html += '<span class="hkdev-tracking-step-label">' + step.label + '</span>';
                    html += '</div>';
                });
                html += '</div></div>';
            }

            // Order Details
            html += '<div class="hkdev-tracking-details">';
            html += '<div class="hkdev-tracking-detail-item"><label>Order Date</label><span>' + data.order_date + '</span></div>';
            html += '<div class="hkdev-tracking-detail-item"><label>Total</label><span>' + data.total + '</span></div>';
            html += '<div class="hkdev-tracking-detail-item"><label>Payment</label><span>' + (data.payment_method || '-') + '</span></div>';
            html += '<div class="hkdev-tracking-detail-item"><label>Phone</label><span>' + (data.billing.phone || '-') + '</span></div>';
            html += '</div>';

            // Billing & Shipping
            html += '<div class="hkdev-tracking-details">';
            html += '<div class="hkdev-tracking-detail-item"><label>Billing Address</label><span>' + (data.billing.address ? data.billing.name + ', ' + data.billing.address : '-') + '</span></div>';
            html += '<div class="hkdev-tracking-detail-item"><label>Shipping Address</label><span>' + (data.shipping.address ? (data.shipping.name || data.billing.name) + ', ' + data.shipping.address : '-') + '</span></div>';
            html += '</div>';

            // Items
            if (data.items && data.items.length) {
                html += '<div class="hkdev-tracking-items"><h5>Order Items</h5>';
                $.each(data.items, function (i, item) {
                    html += '<div class="hkdev-tracking-item">';
                    if (item.image) {
                        html += '<img src="' + item.image + '" alt="">';
                    }
                    html += '<div class="hkdev-tracking-item-info"><h6>' + item.name + '</h6><span>Qty: ' + item.quantity + '</span></div>';
                    html += '<span class="hkdev-tracking-item-total">' + item.total + '</span>';
                    html += '</div>';
                });
                html += '</div>';
            }

            // Notes
            if (data.notes && data.notes.length) {
                html += '<div class="hkdev-tracking-items"><h5>Order Updates</h5>';
                $.each(data.notes, function (i, note) {
                    html += '<div class="hkdev-tracking-item">';
                    html += '<div class="hkdev-tracking-item-info"><h6>' + note.content + '</h6><span>' + note.date + '</span></div>';
                    html += '</div>';
                });
                html += '</div>';
            }

            return html;
        }

        function renderTrackingResults(orders) {
            if (!orders || !orders.length) {
                return;
            }

            var html = '';
            for (var i = 0; i < orders.length; i++) {
                if (i > 0) {
                    html += '<div class="hkdev-tracking-order-sep"></div>';
                }
                html += '<div class="hkdev-tracking-order-block">' + buildOrderHtml(orders[i]) + '</div>';
            }

            // .show() clears the inline display:none that the template ships with.
            $result.html(html).addClass('is-visible').show();
        }
    });
})(jQuery);
