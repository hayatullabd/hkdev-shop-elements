jQuery(document).ready(function($) {
    "use strict";

    // Language Helper for JS
    function hkdevJsT(key) {
        const dict = {
            'temp_error': 'Temporary issue, please reload the page.',
            'server_error': 'Server error! Please reload the page.',
            'confirm_remove': 'Are you sure you want to remove this item?',
            'empty_coupon': 'Please enter a coupon code!'
        };
        return dict[key] || key;
    }

    // AJAX config from THIS plugin (standalone). Falls back to the hkdev-shop
    // theme object only when present, then to WP default.
    const ajaxUrl = (typeof hkdev_elements_ajax !== 'undefined' && hkdev_elements_ajax.ajax_url)
        ? hkdev_elements_ajax.ajax_url
        : ((typeof hkdev_ajax_obj !== 'undefined' && hkdev_ajax_obj.ajax_url)
            ? hkdev_ajax_obj.ajax_url
            : '/wp-admin/admin-ajax.php');
    const updateCartAction = 'hkdev_elements_update_cart_ajax';
    const securityNonce = (typeof hkdev_elements_ajax !== 'undefined' && hkdev_elements_ajax.nonces)
        ? (hkdev_elements_ajax.nonces.cart_update || '')
        : ($('#hkdev_cart_nonce').val() || '');

    // Function to trigger AJAX update
    function hkdev_update_cart(type, key = '', val = '') {
        const $container = $('#hkdev-cart-root');
        const $loader = $('#hkdev-cart-global-loader');

        $container.css('pointer-events', 'none');
        $loader.css('display', 'flex');

        let data = {
            action: updateCartAction,
            security: securityNonce,
            update_type: type,
            cart_key: key
        };

        if (type === 'qty') data.new_qty = val;
        if (type === 'apply_coupon' || type === 'remove_coupon') data.coupon_code = val;

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: data,
            success: function(response) {
                if (response.success) {
                    if (response.data.is_empty) {
                        location.reload(); // Reload to show empty cart template
                    } else {
                        $('#hkdev-cart-items-area').html(response.data.items_html);
                        $('#hkdev-cart-totals-area').html(response.data.totals_html);
                        $container.css('pointer-events', 'auto');
                        $loader.fadeOut(200);

                        // Update cart widget count (Mini Cart)
                        $(document.body).trigger('wc_fragment_refresh');

                        // 🔥 CRITICAL ADDITION: Trigger native WooCommerce events so
                        // BOGO confetti and other plugins keep working after AJAX.
                        $(document.body).trigger('updated_cart_totals');
                        $(document.body).trigger('updated_wc_div');
                    }
                } else {
                    alert(hkdevJsT('temp_error'));
                    location.reload();
                }
            },
            error: function() {
                alert(hkdevJsT('server_error'));
                location.reload();
            }
        });
    }

    // Handle Quantity Plus / Minus
    $(document).on('click', '.hkdev-qty-mod', function() {
        const $btn = $(this);
        const key = $btn.closest('[data-key]').data('key');
        const $valElement = $btn.siblings('.hkdev-qty-val');
        let currentQty = parseInt($valElement.text());

        let newQty = ($btn.data('act') === 'plus') ? currentQty + 1 : currentQty - 1;
        if (newQty < 1) return; // Prevent 0 or negative

        $valElement.text(newQty); // Instant UI update
        hkdev_update_cart('qty', key, newQty);
    });

    // Handle Remove Item
    $(document).on('click', '.hkdev-item-remove-btn', function() {
        if (!confirm(hkdevJsT('confirm_remove'))) return;
        const key = $(this).closest('[data-key]').data('key');
        hkdev_update_cart('remove', key);
    });

    // Handle Apply Coupon
    $(document).on('click', '#hkdev-apply-coupon-btn', function(e) {
        e.preventDefault();
        const code = $('#hkdev-coupon-input').val().trim();
        if (code === '') {
            alert(hkdevJsT('empty_coupon'));
            return;
        }
        hkdev_update_cart('apply_coupon', '', code);
    });

    // Handle Remove Coupon
    $(document).on('click', '.hkdev-remove-coupon', function(e) {
        e.preventDefault();
        const code = $(this).data('coupon');
        hkdev_update_cart('remove_coupon', '', code);
    });

});
