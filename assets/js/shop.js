jQuery(function($) {
    "use strict";

    // Dynamic AJAX config from THIS plugin (standalone). Falls back to the
    // hkdev-shop theme object only when present, then to WP default.
    const ajaxUrl = (typeof hkdev_elements_ajax !== 'undefined' && hkdev_elements_ajax.ajax_url)
        ? hkdev_elements_ajax.ajax_url
        : ((typeof hkdev_ajax_obj !== 'undefined' && hkdev_ajax_obj.ajax_url)
            ? hkdev_ajax_obj.ajax_url
            : '/wp-admin/admin-ajax.php');
    const filterAction = 'hkdev_elements_filter_products';
    const ajaxNonces = (typeof hkdev_elements_ajax !== 'undefined' && hkdev_elements_ajax.nonces) ? hkdev_elements_ajax.nonces : {};
    const filterNonce = ajaxNonces.shop_filter || '';

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

    // ==========================================================
    // CAROUSEL (Swiper)
    // ==========================================================
    const carouselDefaults = {
        mobile: 2,
        tablet: 3,
        gap: 20,
        speed: 600,
        autoplay: false,
        delay: 5000,
        loop: false,
        arrows: true,
        dots: true
    };

    function hkdevCarouselConfig($wrapper) {
        const raw = $wrapper.attr('data-carousel');
        if (!raw) return carouselDefaults;

        try {
            return $.extend({}, carouselDefaults, JSON.parse(raw));
        } catch (e) {
            return carouselDefaults;
        }
    }

    function initHkdevSwiper(wrapper) {
        // Accepts the wrapper element or its id. Resolving purely from an id
        // meant a wrapper without one looked up '#undefined', bailed, and left
        // the carousel hidden for good.
        const $wrapper = (wrapper && wrapper.jquery) ? wrapper : $('#' + wrapper);
        if (!$wrapper.length) return;

        const wrapperId = $wrapper.attr('id') || '';

        const $container = $wrapper.find('.hkdev-swiper-container');
        if (!$container.length) return;

        const el = $container[0];

        // Swiper missing / blocked – never leave the slides invisible.
        if (typeof Swiper === 'undefined') {
            $container.removeClass('hkdev-loading-carousel');
            return;
        }

        // Re-init safe: drop the previous instance before building a new one.
        if (el.swiper && typeof el.swiper.destroy === 'function') {
            el.swiper.destroy(true, true);
        }

        const cfg = hkdevCarouselConfig($wrapper);
        const desktopCols = parseInt($wrapper.data('columns'), 10) || 4;
        const $dots = $wrapper.find('.hkdev-carousel-dots');

        const options = {
            slidesPerView: cfg.mobile,
            spaceBetween: cfg.gap,
            speed: cfg.speed,
            grabCursor: true,
            watchSlidesProgress: true,
            roundLengths: true,
            keyboard: { enabled: true, onlyInViewport: true },
            observer: true,
            observeParents: true,
            loop: !!cfg.loop,
            autoplay: cfg.autoplay
                ? { delay: cfg.delay, disableOnInteraction: false, pauseOnMouseEnter: true }
                : false,
            breakpoints: {
                768: { slidesPerView: cfg.tablet, spaceBetween: cfg.gap },
                1024: { slidesPerView: desktopCols, spaceBetween: cfg.gap }
            },
            on: {
                init: function () {
                    $container.removeClass('hkdev-loading-carousel');
                }
            }
        };

        if (cfg.dots && $dots.length) {
            options.pagination = { el: $dots[0], clickable: true };
        }

        // The arrow selectors are built from the wrapper id, so only wire the
        // navigation when there is one.
        if (cfg.arrows && wrapperId) {
            options.navigation = {
                nextEl: '.hkdev-next-' + wrapperId,
                prevEl: '.hkdev-prev-' + wrapperId
            };
        }

        try {
            new Swiper(el, options);
        } catch (e) {
            // A broken or duplicate Swiper build must never leave the slides
            // hidden: reveal them and stop.
            $container.removeClass('hkdev-loading-carousel');
            return;
        }

        // Safety net: if init never fires, reveal the slides anyway.
        window.setTimeout(function () {
            if (!el.swiper) {
                $container.removeClass('hkdev-loading-carousel');
            }
        }, 1500);
    }

    function initAllHkdevCarousels(scope) {
        $('.hkdev-shop-wrapper[data-style="carousel"]', scope || document).each(function () {
            initHkdevSwiper($(this));
        });
    }

    // Elements outside the Elementor render pipeline (shortcodes, theme).
    initAllHkdevCarousels();

    // Elementor builds widgets after DOM ready – including the editor preview –
    // so the carousel must also initialise from the element_ready hook,
    // otherwise the preview stays blank.
    (function registerElementorCarousels() {
        if (typeof window.elementorFrontend === 'undefined' || !window.elementorFrontend.hooks) {
            $(window).on('elementor/frontend/init', registerElementorCarousels);
            return;
        }

        ['hkdev_shop_grid', 'hkdev_related_products', 'hkdev_category_carousel'].forEach(function (widget) {
            window.elementorFrontend.hooks.addAction(
                'frontend/element_ready/' + widget + '.default',
                function ($scope) {
                    initAllHkdevCarousels($scope);
                }
            );
        });
    })();

    // Category Tabs Filter AJAX
    $('.hkdev-tab-item').on('click', function() {
        var $btn = $(this), 
            $wrapper = $btn.closest('.hkdev-shop-wrapper'), 
            $grid = $wrapper.find('.hkdev-shop-grid'), 
            $loader = $wrapper.find('.hkdev-ajax-loader');
            
        if($btn.hasClass('active')) return;
        
        $wrapper.find('.hkdev-tab-item').removeClass('active'); 
        $btn.addClass('active');
        $loader.fadeIn(150).css('display', 'flex'); 
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: filterAction,
                nonce: filterNonce,
                category: $btn.attr('data-slug'),
                exclude: $wrapper.data('exclude'), 
                include_children: $wrapper.data('include_children'),
                type: $wrapper.data('type'),
                limit: $wrapper.data('limit'),
                days: $wrapper.data('days'),
                order_by: $wrapper.data('order_by'),
                tags: $wrapper.data('tags'),
                brands: $wrapper.data('brands'),
                on_sale: $wrapper.data('on_sale'),
                featured: $wrapper.data('featured'),
                stock_status: $wrapper.data('stock_status'),
                image_size: $wrapper.data('image_size'),
                hover_img: $wrapper.attr('data-hover-img'),
                style: $wrapper.data('style')
            },
            success: function(response) { 
                $grid.html(response); 
                $loader.fadeOut(150); 
                resetLoadMore($wrapper);
                if($wrapper.data('style') === 'carousel') {
                    initHkdevSwiper($wrapper);
                }
            }
        });
    });

    // ==========================================================
    // LOAD MORE (shop grid)
    // ==========================================================
    const loadMoreAction = 'hkdev_elements_load_more_products';

    // Put the button back to its first-page state (used after a tab switch,
    // which replaces the grid with page 1 of another category).
    function resetLoadMore($wrapper) {
        const $btn = $wrapper.find('.hkdev-load-more');
        if (!$btn.length) return;

        const $wrap = $btn.closest('.hkdev-load-more-wrap');
        $btn.data('page', 1).removeClass('is-loading').prop('disabled', false)
            .find('.hkdev-lm-label').text($btn.data('label') || 'Load More');

        // The tab response holds page 1 of the newly selected category: when it
        // has fewer cards than the limit there is nothing left to load. The
        // button is only hidden (never removed), so switching back to a bigger
        // category brings it straight back.
        const limit = parseInt($wrapper.data('limit'), 10) || 0;
        // Ignore Swiper's loop-mode clones when counting the visible slides.
        const shown = $wrapper.find('.hkdev-shop-grid').first().children(':not(.swiper-slide-duplicate)').length;
        $wrap.toggleClass('is-empty', limit > 0 && shown < limit);
    }

    $(document).on('click', '.hkdev-load-more', function () {
        const $btn = $(this);
        const $wrapper = $btn.closest('.hkdev-shop-wrapper');
        const $grid = $wrapper.find('.hkdev-shop-grid').first();

        if (!$wrapper.length || !$grid.length || $btn.hasClass('is-loading')) {
            return;
        }

        const nextPage = (parseInt($btn.data('page'), 10) || 1) + 1;
        const $activeTab = $wrapper.find('.hkdev-tab-item.active');
        const category = $activeTab.length
            ? ($activeTab.attr('data-slug') || '')
            : ($wrapper.data('category') || '');

        $btn.addClass('is-loading').prop('disabled', true)
            .find('.hkdev-lm-label').text($btn.attr('data-loading-label') || 'Loading...');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: loadMoreAction,
                nonce: filterNonce,
                page: nextPage,
                category: category,
                exclude: $wrapper.data('exclude'),
                include_children: $wrapper.data('include_children'),
                type: $wrapper.data('type'),
                limit: $wrapper.data('limit'),
                days: $wrapper.data('days'),
                order_by: $wrapper.data('order_by'),
                tags: $wrapper.data('tags'),
                brands: $wrapper.data('brands'),
                on_sale: $wrapper.data('on_sale'),
                featured: $wrapper.data('featured'),
                stock_status: $wrapper.data('stock_status'),
                image_size: $wrapper.data('image_size'),
                hover_img: $wrapper.attr('data-hover-img'),
                style: $wrapper.data('style')
            },
            success: function (response) {
                const data = (response && response.success && response.data) ? response.data : null;

                // No usable payload: put the button back the way it was.
                if (!data) {
                    $btn.removeClass('is-loading').prop('disabled', false)
                        .find('.hkdev-lm-label').text($btn.data('label') || 'Load More');
                    return;
                }

                if (data.html) {
                    $grid.append(data.html);

                    // Carousel: let Swiper pick up the new slides.
                    const swiperEl = $wrapper.find('.hkdev-swiper-container')[0];
                    if (swiperEl && swiperEl.swiper && typeof swiperEl.swiper.update === 'function') {
                        swiperEl.swiper.update();
                    }
                }

                $btn.removeClass('is-loading').prop('disabled', false)
                    .find('.hkdev-lm-label').text($btn.data('label') || 'Load More');

                if (data.has_more && data.html) {
                    $btn.data('page', data.page);
                } else {
                    // Every product is on screen – hide the button; the wrap keeps
                    // it so a later tab switch can bring it back.
                    $btn.closest('.hkdev-load-more-wrap').addClass('is-empty');
                }
            },
            error: function () {
                $btn.removeClass('is-loading').prop('disabled', false)
                    .find('.hkdev-lm-label').text($btn.data('label') || 'Load More');
            }
        });
    });

    // ==========================================================
    // VARIATION MODAL (variable products)
    // ==========================================================
    const addToCartAction = 'hkdev_elements_ajax_add_to_cart';
    const addToCartNonce = ajaxNonces.add_to_cart || '';

    function vmEscape(str) {
        return $('<div>').text(str === null || str === undefined ? '' : str).html();
    }

    function vmSelectedAttrs($modal) {
        const selected = {};
        let complete = true;

        $modal.find('.hkdev-vm-attr').each(function() {
            const key = $(this).data('attribute');
            const $opt = $(this).find('.hkdev-vm-option.selected');
            const val = $opt.length ? $opt.attr('data-value') : '';

            if (val) {
                selected[key] = String(val);
            } else {
                complete = false;
            }
        });

        return { selected: selected, complete: complete };
    }

    function vmUpdate($modal) {
        const variations = $modal.data('variations') || [];
        const $btns = $modal.find('.hkdev-vm-add-btn, .hkdev-vm-view-btn, .hkdev-vm-buy-btn');
        const state = vmSelectedAttrs($modal);

        if (!state.complete) {
            $modal.find('.hkdev-vm-stock').text('Select options');
            $btns.attr('data-variation-id', '').data('variation-id', '');
            $modal.data('matched', null);
            return;
        }

        let match = null;
        for (let i = 0; i < variations.length; i++) {
            const v = variations[i];
            let ok = true;
            for (const key in state.selected) {
                const vVal = v.attributes ? v.attributes[key] : undefined;
                if (vVal === undefined || vVal === '' || vVal === null) continue;
                if (String(vVal).toLowerCase() !== state.selected[key].toLowerCase()) {
                    ok = false;
                    break;
                }
            }
            if (ok) { match = v; break; }
        }

        if (!match) {
            $modal.find('.hkdev-vm-stock').text('Not available');
            $btns.attr('data-variation-id', '').data('variation-id', '');
            $modal.data('matched', null);
            return;
        }

        if (match.price_html) $modal.find('.hkdev-vm-price').html(match.price_html);
        if (match.image) $modal.find('.hkdev-vm-thumb').attr('src', match.image);

        let stockText = 'In stock';
        if (!match.is_in_stock) {
            stockText = 'Out of stock';
        } else if (match.stock_qty !== null && match.stock_qty !== undefined && match.stock_qty !== '') {
            stockText = 'In stock (' + match.stock_qty + ')';
        }
        $modal.find('.hkdev-vm-stock').text(stockText);

        $btns.attr('data-variation-id', match.variation_id).data('variation-id', match.variation_id);
        $modal.data('matched', match);
    }

    function vmClose($modal) {
        if (!$modal || !$modal.length) return;
        $modal.hide();
        $('body').removeClass('hkdev-vm-open');
    }

    $(document).on('click', '.hkdev-open-variation', function(e) {
        e.preventDefault();

        const $btn = $(this);
        // The modal lives inside the Shop Grid wrapper, inside the Catalog
        // widget wrapper for [hkdev_catalog].
        const $wrapper = $btn.closest('.hkdev-shop-wrapper, .hkdev-catalog');
        const $modal = $wrapper.find('.hkdev-variation-modal').first();
        if (!$modal.length) return;

        const $card = $btn.closest('.hkdev-product-card');
        const $data = $card.find('.hkdev-variation-data').first();
        const attributes = $data.data('attributes') || [];
        const variations = $data.data('variations') || [];

        const name = $.trim($card.find('.hkdev-title').first().text());
        const img = $card.find('.hkdev-img-box img').first().attr('src') || '';

        $modal.find('.hkdev-vm-title').text(name);
        $modal.find('.hkdev-vm-thumb').attr('src', img).attr('alt', name);

        let html = '';
        attributes.forEach(function(group) {
            html += '<div class="hkdev-vm-attr" data-attribute="' + vmEscape(group.key) + '">';
            html += '<span class="hkdev-vm-attr-label">' + vmEscape(group.label) + ' <em>*</em></span>';
            html += '<div class="hkdev-vm-options">';
            group.options.forEach(function(opt) {
                html += '<button type="button" class="hkdev-vm-option" data-value="' + vmEscape(opt.value) + '">' + vmEscape(opt.label) + '</button>';
            });
            html += '</div></div>';
        });
        $modal.find('.hkdev-vm-attributes').html(html);

        const cardPrice = $card.find('.hkdev-price-container').first().html() || '';
        $modal.find('.hkdev-vm-price').html(cardPrice);
        $modal.find('.hkdev-vm-stock').text('Select options');
        $modal.find('.hkdev-vm-add-btn, .hkdev-vm-view-btn, .hkdev-vm-buy-btn').attr('data-variation-id', '').data('variation-id', '');

        $modal.data('variations', variations);
        $modal.data('product-id', $btn.attr('data-product-id'));
        $modal.data('matched', null);

        $modal.css('display', 'flex');
        $('body').addClass('hkdev-vm-open');
    });

    $(document).on('click', '.hkdev-vm-option', function() {
        const $opt = $(this);
        $opt.closest('.hkdev-vm-options').find('.hkdev-vm-option').removeClass('selected');
        $opt.addClass('selected');
        vmUpdate($opt.closest('.hkdev-variation-modal'));
    });

    $(document).on('click', '.hkdev-vm-close, .hkdev-vm-overlay', function() {
        vmClose($(this).closest('.hkdev-variation-modal'));
    });

    $(document).on('keyup', function(e) {
        if (e.key === 'Escape') {
            vmClose($('.hkdev-variation-modal:visible'));
            closeCheckoutModal();
        }
    });

    $(document).on('click', '.hkdev-vm-add-btn, .hkdev-vm-view-btn', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const $modal = $btn.closest('.hkdev-variation-modal');
        const productId = $modal.data('product-id');
        const variationId = $btn.attr('data-variation-id');
        const matched = $modal.data('matched');

        if (!variationId) {
            showToast('Please select the product options first.', 'error');
            return;
        }
        if (matched && matched.is_in_stock === false) {
            showToast('This option is out of stock.', 'error');
            return;
        }
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).css('opacity', '0.7');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: addToCartAction,
                nonce: addToCartNonce,
                product_id: productId,
                variation_id: variationId,
                quantity: 1
            },
            success: function(res) {
                $btn.prop('disabled', false).css('opacity', '1');
                if (res && res.success) {
                    $(document.body).trigger('added_to_cart', [res.data.fragments, res.data.cart_hash, $btn]);
                    vmClose($modal);
                } else {
                    showToast('Could not add product to cart. Try again.', 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).css('opacity', '1');
                showToast('Server error occurred. Please try again.', 'error');
            }
        });
    });

    // ==========================================================
    // CHECKOUT MODAL (opened by the "Buy Now" buttons)
    // ==========================================================
    const checkoutModalAction = 'hkdev_elements_co_modal';
    const checkoutModalScope = (typeof hkdev_elements_ajax !== 'undefined' && hkdev_elements_ajax.checkout_modal_scope)
        ? hkdev_elements_ajax.checkout_modal_scope
        : 'both';
    const checkoutPageUrl = (typeof hkdev_elements_ajax !== 'undefined' && hkdev_elements_ajax.checkout_url)
        ? hkdev_elements_ajax.checkout_url
        : '/checkout/';

    function shouldOpenCheckoutModal(productType) {
        if (checkoutModalScope === 'none') return false;
        if (checkoutModalScope === 'both') return true;
        return checkoutModalScope === productType;
    }

    function ensureCheckoutModal() {
        let $m = $('#hkdev-co-modal');
        if (!$m.length) {
            $m = $(
                '<div class="hkdev-co-modal" id="hkdev-co-modal">' +
                    '<div class="hkdev-co-modal-overlay"></div>' +
                    '<div class="hkdev-co-modal-box">' +
                        '<button type="button" class="hkdev-co-modal-close" aria-label="Close">&times;</button>' +
                        '<div class="hkdev-co-modal-body">' +
                            '<div class="hkdev-co-modal-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading checkout...</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
            $('body').append($m);
        }
        return $m;
    }

    function openCheckoutModal() {
        const $m = ensureCheckoutModal();
        $m.find('.hkdev-co-modal-body').html(
            '<div class="hkdev-co-modal-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading checkout...</div>'
        );
        $m.addClass('is-open');
        $('body').addClass('hkdev-co-modal-open');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: checkoutModalAction,
                security: ajaxNonces.co_modal || ''
            },
            success: function(res) {
                if (res && res.success) {
                    $m.find('.hkdev-co-modal-body').html(res.data.html);
                    if (typeof window.hkdevInitCheckoutSelect2 === 'function') {
                        window.hkdevInitCheckoutSelect2($m);
                    }
                } else {
                    $m.find('.hkdev-co-modal-body').html(
                        '<div class="hkdev-co-modal-loading">Could not load checkout. Please try again.</div>'
                    );
                }
            },
            error: function() {
                $m.find('.hkdev-co-modal-body').html(
                    '<div class="hkdev-co-modal-loading">Could not load checkout. Please try again.</div>'
                );
            }
        });
    }

    function closeCheckoutModal() {
        $('#hkdev-co-modal').removeClass('is-open');
        $('body').removeClass('hkdev-co-modal-open');
    }

    $(document).on('click', '.hkdev-co-modal-close, .hkdev-co-modal-overlay', function() {
        closeCheckoutModal();
    });

    $(document).on('click', '.hkdev-vm-buy-btn', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const $modal = $btn.closest('.hkdev-variation-modal');
        const productId = $modal.data('product-id');
        const variationId = $btn.attr('data-variation-id');
        const matched = $modal.data('matched');

        if (!variationId) {
            showToast('Please select the product options first.', 'error');
            return;
        }
        if (matched && matched.is_in_stock === false) {
            showToast('This option is out of stock.', 'error');
            return;
        }
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).css('opacity', '0.7');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: addToCartAction,
                nonce: addToCartNonce,
                product_id: productId,
                variation_id: variationId,
                quantity: 1
            },
            success: function(res) {
                $btn.prop('disabled', false).css('opacity', '1');
                if (res && res.success) {
                    $(document.body).trigger('added_to_cart', [res.data.fragments, res.data.cart_hash, $btn]);
                    vmClose($modal);
                    if (shouldOpenCheckoutModal('variable')) {
                        openCheckoutModal();
                    } else {
                        window.location.href = checkoutPageUrl;
                    }
                } else {
                    showToast('Could not add product to cart. Try again.', 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).css('opacity', '1');
                showToast('Server error occurred. Please try again.', 'error');
            }
        });
    });

    // Simple product "Buy Now" — add to cart via AJAX, then open the checkout
    // modal (or redirect to the checkout page when the modal is disabled).
    $(document).on('click', '.hkdev-buy-now', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const productId = $btn.attr('data-product-id');
        const btnCheckoutUrl = $btn.attr('data-checkout_url') || checkoutPageUrl;

        if (!productId) return;
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).css('opacity', '0.7');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: addToCartAction,
                nonce: addToCartNonce,
                product_id: productId,
                variation_id: 0,
                quantity: 1
            },
            success: function(res) {
                $btn.prop('disabled', false).css('opacity', '1');
                if (res && res.success) {
                    $(document.body).trigger('added_to_cart', [res.data.fragments, res.data.cart_hash, $btn]);
                    if (shouldOpenCheckoutModal('simple')) {
                        openCheckoutModal();
                    } else {
                        window.location.href = btnCheckoutUrl;
                    }
                } else {
                    showToast('Could not add product to cart. Try again.', 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).css('opacity', '1');
                showToast('Server error occurred. Please try again.', 'error');
            }
        });
    });

    // Global Add to Cart Toast & Button state handler
    $(document.body).on('added_to_cart', function(e, f, h, $button) {
        $('.added_to_cart').remove(); // Default woocommerce 'view cart' link remove
        
        // Show Toast Notification
        var $toast = $('#hkdev-toast-master');
        if($toast.length) {
            $toast.show().addClass('show');
            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(() => $toast.hide(), 400);
            }, 2000);
        }

        // Logic to keep button enabled everywhere
        if ($button) {
            if ($button.hasClass('hkdev-cart-btn')) {
                // If it is our Grid Button, just change 'Buy Now' to 'Checkout' without disabling Add to Cart
                var $orderBtn = $button.closest('.hkdev-action-group').find('.hkdev-order-btn');
                if($orderBtn.length) {
                    $orderBtn.text('Checkout').attr('href', $orderBtn.data('checkout_url'));
                }
                
                // Remove WooCommerce loading class manually to reset the button state
                $button.removeClass('loading disabled').prop('disabled', false).css({'pointer-events': 'auto', 'opacity': '1'});
            } else {
                // If it is Single Product Page Button, re-enable it immediately
                $button.removeClass('disabled').prop('disabled', false).css({'pointer-events': 'auto', 'opacity': '1'});
            }
        }
    });
});
