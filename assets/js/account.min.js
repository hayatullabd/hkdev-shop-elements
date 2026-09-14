/**
 * HKDEV My Account — tab switching, profile/address/password AJAX.
 */
(function ($) {
    'use strict';

    $(function () {
        var $root = $('#hkdev-account-root');
        if (!$root.length) {
            return;
        }

        // Tab switching
        $root.on('click', '.hkdev-account-nav a[data-tab]', function (e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            $root.find('.hkdev-account-nav li').removeClass('active');
            $(this).closest('li').addClass('active');
            $root.find('.hkdev-account-tab').removeClass('active');
            $root.find('#hkdev-account-' + tab).addClass('active');
        });

        // Helper
        function showMessage($form, type, message) {
            var $msg = $form.hasClass('hkdev-account-form') ? $form.siblings('.hkdev-account-message').addBack().parent().find('.hkdev-account-message').first() : $form.find('.hkdev-account-message');
            $msg.removeClass('is-error is-success').addClass('is-' + type).html(message).show();
        }

        // Profile form
        $root.on('submit', '#hkdev-profile-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.hkdev-account-submit');
            $btn.prop('disabled', true);

            $.ajax({
                url: hkdevElementsAjax.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=hkdev_elements_account_update_profile',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showMessage($form, 'success', response.data.message);
                    } else {
                        showMessage($form, 'error', response.data.message);
                    }
                    $btn.prop('disabled', false);
                },
                error: function () {
                    showMessage($form, 'error', 'An error occurred. Please try again.');
                    $btn.prop('disabled', false);
                }
            });
        });

        // Address forms
        $root.on('submit', '.hkdev-address-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.hkdev-account-submit');
            var type = $form.data('address-type');

            $btn.prop('disabled', true);

            var data = $form.serialize() + '&action=hkdev_elements_account_update_address&address_type=' + type;

            $.ajax({
                url: hkdevElementsAjax.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showMessage($form, 'success', response.data.message);
                    } else {
                        showMessage($form, 'error', response.data.message);
                    }
                    $btn.prop('disabled', false);
                },
                error: function () {
                    showMessage($form, 'error', 'An error occurred. Please try again.');
                    $btn.prop('disabled', false);
                }
            });
        });

        // Password form
        $root.on('submit', '#hkdev-password-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.hkdev-account-submit');
            $btn.prop('disabled', true);

            $.ajax({
                url: hkdevElementsAjax.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=hkdev_elements_account_change_password',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showMessage($form, 'success', response.data.message);
                        $form[0].reset();
                    } else {
                        showMessage($form, 'error', response.data.message);
                    }
                    $btn.prop('disabled', false);
                },
                error: function () {
                    showMessage($form, 'error', 'An error occurred. Please try again.');
                    $btn.prop('disabled', false);
                }
            });
        });
    });
})(jQuery);
