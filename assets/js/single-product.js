jQuery(document).ready(function($) {
    "use strict";

    // Language Helper for JS
    function hkdevJsT(key) {
        const dict = {
            'off_text': 'Off!',
            'in_stock': 'In Stock',
            'out_of_stock': 'Out Of Stock',
            'select_variation_alert': 'Please select the product options first.',
            'add_to_cart_fail': 'Could not add product to cart. Try again.',
            'out_of_stock_alert': 'This product is out of stock.',
            'server_error': 'Server error occurred. Please try again.',
            'complete_order': 'Complete Order'
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
    const addToCartAction = 'hkdev_elements_ajax_add_to_cart';
    const addToCartNonce = (typeof hkdev_elements_ajax !== 'undefined' && hkdev_elements_ajax.nonces)
        ? (hkdev_elements_ajax.nonces.add_to_cart || '')
        : ((typeof hkdev_ajax_obj !== 'undefined') ? hkdev_ajax_obj.cart_nonce : '');

    // Nice in-page toast (replaces the native browser alert()).
    function showToast(message, type) {
        type = type || 'error';
        let $toast = $('#hkdev-shop-toast');
        if (!$toast.length) {
            $toast = $(
                '<div id="hkdev-shop-toast" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) scale(.9);z-index:999999;display:flex;align-items:center;gap:12px;padding:16px 22px;border-radius:14px;background:#fff;color:#141a14;font-family:inherit;font-size:15px;font-weight:600;line-height:1.3;box-shadow:0 12px 40px rgba(0,0,0,.18);border-left:5px solid #e5533d;opacity:0;visibility:hidden;transition:all .35s cubic-bezier(.175,.885,.32,1.275);max-width:90vw;">' +
                    '<span class="hkdev-shop-toast-icon" style="font-size:22px;color:#e5533d;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-circle-xmark"></i></span>' +
                    '<span class="hkdev-shop-toast-msg"></span>' +
                '</div>'
            );
            $('body').append($toast);
        }
        $toast.find('.hkdev-shop-toast-msg').text(message);
        const isError = type === 'error';
        const color = isError ? '#e5533d' : '#03a550';
        $toast.css('borderLeftColor', color);
        $toast.find('.hkdev-shop-toast-icon').css('color', color);
        $toast.find('.hkdev-shop-toast-icon i').attr('class', isError ? 'fa-solid fa-circle-xmark' : 'fa-solid fa-circle-check');
        $toast.css({ opacity: 1, visibility: 'visible', transform: 'translate(-50%,-50%) scale(1)' });
        clearTimeout($toast.data('timer'));
        $toast.data('timer', setTimeout(function () {
            $toast.css({ opacity: 0, visibility: 'hidden', transform: 'translate(-50%,-50%) scale(.9)' });
        }, 3500));
    }

    // 1. Quantity Plus/Minus Buttons
    $(document).on('click', '.hkdev-sp-qty-btn', function() {
        const $input = $('#hkdev-sp-qty-field');
        let val = parseInt($input.val()) || 1;

        if ($(this).hasClass('plus')) {
            val += 1;
        } else if ($(this).hasClass('minus')) {
            val -= 1;
        }

        if (val < 1) val = 1;
        $input.val(val);
    });

    // 2. Variations Data
    const $spWrapper = $('.hkdev-sp-wrapper').first();
    const productType = $spWrapper.data('product-type') || 'simple';
    const variations = $('.hkdev-sp-variable-options').data('variations') || [];

    function setPurchaseButtonsState(isEnabled) {
        const $atc = $('#hkdev-sp-add-to-cart');
        const $buy = $('#hkdev-sp-buy-now');
        const $qty = $('#hkdev-sp-qty-field');
        const $qtyBtns = $('.hkdev-sp-qty-btn');

        if ($buy.hasClass('checkout-active')) {
            $atc.prop('disabled', true).addClass('is-out-of-stock').css('opacity', '0.55');
            $buy.prop('disabled', false).removeClass('is-out-of-stock').css('opacity', '1');
            $qty.prop('disabled', true);
            $qtyBtns.prop('disabled', true);
            return;
        }

        if (isEnabled) {
            $atc.prop('disabled', false).removeClass('is-out-of-stock').css('opacity', '1');
            $buy.prop('disabled', false).removeClass('is-out-of-stock').css('opacity', '1');
            $qty.prop('disabled', false);
            $qtyBtns.prop('disabled', false);
        } else {
            $atc.prop('disabled', true).addClass('is-out-of-stock').css('opacity', '0.55');
            $buy.prop('disabled', true).addClass('is-out-of-stock').css('opacity', '0.55');
            $qty.prop('disabled', true);
            $qtyBtns.prop('disabled', true);
        }
    }

    if ('variable' === productType) {
        setPurchaseButtonsState(false);
    } else if ('no' === String($spWrapper.data('in-stock'))) {
        setPurchaseButtonsState(false);
    }

    // 3. Image + Video Gallery Logic
    function updateMainImage(index) {
        const $thumbs = $('.hkdev-sp-thumb');
        if(index >= $thumbs.length) index = 0;
        if(index < 0) index = $thumbs.length - 1;

        const $target = $thumbs.eq(index);
        const mediaType = $target.data('type') || 'image';

        $thumbs.removeClass('active');
        $target.addClass('active');

        $('#hkdev-sp-viewport').removeClass('zoomed-active');

        const $zoomInner = $('#hkdev-sp-zoom-inner');
        const $player = $('#hkdev-sp-video-player');

        if ('video' === mediaType) {
            // Show the video player, fully hide the image container.
            $zoomInner.stop(true, true).fadeOut(100);
            const embedHtml = $target.data('video-embed') || '';
            if ($player.length && embedHtml) {
                $player.stop(true, true).fadeOut(100, function() {
                    $(this).html(embedHtml).stop(true, true).fadeIn(200);
                });
            } else if ($player.length) {
                $player.stop(true, true).fadeOut(100);
            }
        } else {
            // Show the image, hide the video player.
            const fullSrc = $target.data('full');
            if ($player.length) {
                $player.stop(true, true).fadeOut(100).empty();
            }
            if ($zoomInner.is(':hidden')) {
                // Coming back from a video: swap the src and show the
                // container directly (no fadeOut needed on a hidden node).
                $('#hkdev-sp-main-img').attr('src', fullSrc);
                $zoomInner.stop(true, true).fadeIn(200);
            } else {
                // Normal image navigation: fade the image itself and swap
                // the src in the callback (matches the proven pre-video path).
                $('#hkdev-sp-main-img').stop(true, true).fadeOut(100, function() {
                    $(this).attr('src', fullSrc).fadeIn(200);
                });
            }
        }

        const container = $('.hkdev-sp-thumbnails');
        if ($target.length) {
            container.animate({
                scrollLeft: $target.position().left + container.scrollLeft() - (container.width() / 2) + ($target.width() / 2)
            }, 200);
        }
    }

    $(document).on('click', '.hkdev-sp-thumb', function() {
        updateMainImage($('.hkdev-sp-thumb').index(this));
    });

    $(document).on('click', '#hkdev-sp-next-img', function(e) {
        e.stopPropagation();
        updateMainImage($('.hkdev-sp-thumb.active').index() + 1);
    });

    $(document).on('click', '#hkdev-sp-prev-img', function(e) {
        e.stopPropagation();
        updateMainImage($('.hkdev-sp-thumb.active').index() - 1);
    });

    // YouTube lite embed in the gallery viewport (click poster -> load player).
    // Same pattern as video.js: keep the .hkdev-sp-vp-lite link as the container,
    // empty it and append the iframe inside it. Removing the link itself would
    // also remove the iframe that was placed inside it.
    $(document).on('click', '.hkdev-sp-vp-lite', function(e) {
        e.preventDefault();
        const $this = $(this);
        const embedUrl = $this.attr('data-youtube-embed');
        if (!embedUrl) {
            return;
        }
        // referrerpolicy is required by YouTube to avoid "Error 153".
        const $iframe = $('<iframe>', {
            class: 'hkdev-sp-vp-iframe',
            src: embedUrl,
            frameborder: '0',
            referrerpolicy: 'strict-origin-when-cross-origin',
            allow: 'accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share',
            allowfullscreen: 'allowfullscreen',
            loading: 'lazy'
        }).css({ width: '100%', height: '100%', display: 'block' });

        // Keep the link as the positioning container; drop the poster content.
        $this.addClass('hkdev-sp-vp-playing').empty().append($iframe);
    });

     // 4. FAQ accordion (Product FAQ section)
    $(document).on('click', '.hkdev-sp-faq-question', function() {
        const isOpen = $(this).attr('aria-expanded') === 'true';
        $(this).attr('aria-expanded', !isOpen);
        $(this).siblings('.hkdev-sp-faq-answer').stop(true, true).slideToggle(200);
    });

    // 5. Image Zoom Feature (class-driven so CSS !important wins over hover scale)
    $(document).on('click', '#hkdev-sp-zoom-btn, #hkdev-sp-zoom-container', function(e) {
        if($(e.target).closest('.hkdev-sp-arrow').length) return;

        const $viewport = $('#hkdev-sp-viewport');
        $viewport.toggleClass('zoomed-active');

        if(!$viewport.hasClass('zoomed-active')) {
            // Reset zoom origin when turning zoom off.
            $('#hkdev-sp-main-img').css('transform-origin', 'center center');
        }
    });

    $(document).on('mousemove', '#hkdev-sp-zoom-container', function(e) {
        if(!$('#hkdev-sp-viewport').hasClass('zoomed-active')) return;

        const rect = this.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;

        $('#hkdev-sp-main-img').css('transform-origin', x + '% ' + y + '%');
    });

    // 6. Variation Selection logic
    $(document).on('click', '.hkdev-sp-swatch-item', function() {
        const row = $(this).closest('.hkdev-sp-variation-row');
        row.find('.hkdev-sp-swatch-item').removeClass('selected');
        $(this).addClass('selected');
        row.find('.selected-val').text($(this).attr('data-label') || $(this).text() || $(this).attr('data-value'));
        updateVariation();
    });

    function updateVariation() {
        let selectedAttrs = {};
        let allSelected = true;

        $('.hkdev-sp-variation-row').each(function() {
            const attr = $(this).data('attribute');
            const val = $(this).find('.hkdev-sp-swatch-item.selected').attr('data-value');

            if (val !== undefined && val !== "") {
                selectedAttrs[attr] = val;
            } else {
                allSelected = false;
            }
        });

        if (!allSelected) {
            setPurchaseButtonsState(false);
            return;
        }

        const match = variations.find(v => {
            return Object.keys(selectedAttrs).every(key => {
                let vAttrVal = v.attributes[key];
                let selectedVal = selectedAttrs[key];
                if (vAttrVal === "") return true;
                return String(vAttrVal).toLowerCase() === String(selectedVal).toLowerCase();
            });
        });

        if (match) {
            $('.hkdev-sp-price-box').html(match.price_html);

            const $badge = $('.hkdev-sp-sale-badge');
            if (match.discount_percentage > 0) {
                $badge.text(match.discount_percentage + '% ' + hkdevJsT('off_text')).show();
            } else {
                $badge.hide();
            }

            if (match.image && match.image.src) {
                $('#hkdev-sp-main-img').attr('src', match.image.src);
                // Re-activate the thumb that matches the variation image so
                // arrow navigation keeps working after a variation change.
                var $vThumb = $('.hkdev-sp-thumb').removeClass('active').filter(function() {
                    return $(this).data('full') === match.image.src;
                });
                ($vThumb.length ? $vThumb : $('.hkdev-sp-thumb').first()).addClass('active');
            }

            $('.sku-val').text(match.sku || 'N/A');
            $('.stock-val').html(match.is_in_stock ? '<span class="in-stock-pill">' + hkdevJsT('in_stock') + '</span>' : '<span class="out-stock-pill">' + hkdevJsT('out_of_stock') + '</span>');

            $('#hkdev-sp-add-to-cart, #hkdev-sp-buy-now').attr('data-variation-id', match.variation_id).data('variation-id', match.variation_id);
            setPurchaseButtonsState(!!match.is_in_stock);
        } else {
            setPurchaseButtonsState(false);
        }
    }

    // 7. AJAX Add to Cart & Buy Now
    $(document).off('click', '#hkdev-sp-add-to-cart, #hkdev-sp-buy-now').on('click', '#hkdev-sp-add-to-cart, #hkdev-sp-buy-now', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const isBuyNow = $btn.attr('id') === 'hkdev-sp-buy-now';

        if (isBuyNow && $btn.hasClass('checkout-active')) {
            window.location.href = $btn.data('checkout-url');
            return;
        }

        let pId = $btn.attr('data-product-id');
        let vId = $btn.attr('data-variation-id');
        let qty = $('#hkdev-sp-qty-field').val() || 1;

        if ($('.hkdev-sp-variable-options').length > 0 && (!vId || vId == 0 || vId === "0")) {
            showToast(hkdevJsT('select_variation_alert'), 'error');
            return;
        }

        if ($btn.prop('disabled') || $btn.hasClass('is-out-of-stock')) {
            return;
        }

        $btn.prop('disabled', true).css('opacity', '0.7');

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: {
                action: addToCartAction,
                nonce: addToCartNonce,
                product_id: pId,
                variation_id: vId,
                quantity: qty
            },
            success: function(res) {
                if (res.success) {
                    $(document.body).trigger('added_to_cart', [res.data.fragments, res.data.cart_hash, $btn]);
                    if (isBuyNow) {
                        window.location.href = $btn.data('checkout-url');
                    } else {
                        if ($btn.attr('id') === 'hkdev-sp-add-to-cart') {
                            $('#hkdev-sp-buy-now').addClass('checkout-active').find('.btn-text').text(hkdevJsT('complete_order'));
                        }
                    }
                } else {
                    const msg = (res.data && res.data.message) ? res.data.message : hkdevJsT('add_to_cart_fail');
                    showToast(msg, 'error');
                    setPurchaseButtonsState(false);
                }

                if ('variable' === productType) {
                    updateVariation();
                } else if ('no' === String($spWrapper.data('in-stock'))) {
                    setPurchaseButtonsState(false);
                } else {
                    setPurchaseButtonsState(true);
                }
            },
            error: function() {
                if ('variable' === productType) {
                    updateVariation();
                } else if ('no' === String($spWrapper.data('in-stock'))) {
                    setPurchaseButtonsState(false);
                } else {
                    setPurchaseButtonsState(true);
                }
                showToast(hkdevJsT('server_error'), 'error');
            }
        });
    });

    // 8. Description & Review Tabs
    $(document).on('click', '.hkdev-sp-tab-link', function() {
        $('.hkdev-sp-tab-link, .hkdev-sp-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + $(this).data('tab')).addClass('active');
    });

    // 9. Size Chart Modal Logic
    $(document).on('click', '#hkdev-size-chart-btn', function(e) {
        e.preventDefault();
        $('#hkdev-size-chart-modal').css('display', 'flex').hide().fadeIn(200);
    });

    $(document).on('click', '#hkdev-size-chart-close', function() {
        $('#hkdev-size-chart-modal').fadeOut(200);
    });

    $(document).on('click', '#hkdev-size-chart-modal', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
        }
    });

});
