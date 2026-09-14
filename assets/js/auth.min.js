/**
 * HKDEV Auth Modal — login / register / reset password AJAX handlers.
 */
(function ($) {
    'use strict';

    $(function () {
        var $modal = $('#hkdev-auth-modal');
        if (!$modal.length) {
            return;
        }

        // Open modal
        $(document).on('click', '.hkdev-auth-open', function (e) {
            e.preventDefault();
            var form = $(this).data('form') || 'login';
            switchForm(form);
            openModal();
        });

        function openModal() {
            $modal.addClass('is-open').attr('aria-hidden', 'false');
            $('body').addClass('hkdev-auth-modal-open');
        }

        function closeModal() {
            $modal.removeClass('is-open').attr('aria-hidden', 'true');
            $('body').removeClass('hkdev-auth-modal-open');
        }

        // Close modal
        $modal.on('click', '.hkdev-auth-modal-close, .hkdev-auth-modal-overlay', function (e) {
            e.preventDefault();
            closeModal();
        });

        $(document).on('keyup', function (e) {
            if ('Escape' === e.key && $modal.hasClass('is-open')) {
                closeModal();
            }
        });

        // Switch forms
        function switchForm(form) {
            $modal.find('.hkdev-auth-form').hide();
            $modal.find('.hkdev-auth-' + form + '-form').show();
            $modal.find('.hkdev-auth-message').hide().removeClass('is-error is-success');
        }

        $modal.on('click', '.hkdev-auth-switch', function (e) {
            e.preventDefault();
            switchForm($(this).data('switch'));
        });

        // Toggle password visibility
        $modal.on('click', '.hkdev-auth-toggle-password', function () {
            var $input = $(this).closest('.hkdev-auth-password-wrap').find('input');
            var $icon = $(this).find('i');
            if ('password' === $input.attr('type')) {
                $input.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Helper to show message
        function showMessage($form, type, message) {
            $form.closest('.hkdev-auth-form').find('.hkdev-auth-message')
                .removeClass('is-error is-success')
                .addClass('is-' + type)
                .html(message)
                .show();
        }

        // Login form submit
        $modal.on('submit', '#hkdev-login-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.hkdev-auth-submit');
            var $spinner = $btn.find('.hkdev-auth-spinner');
            var $btnText = $btn.find('.hkdev-auth-btn-text');

            $btn.prop('disabled', true);
            $spinner.show();
            $btnText.hide();

            $.ajax({
                url: hkdevElementsAjax.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showMessage($form, 'success', response.data.message);
                        setTimeout(function () {
                            window.location.href = response.data.redirect || window.location.href;
                        }, 1000);
                    } else {
                        showMessage($form, 'error', response.data.message);
                        $btn.prop('disabled', false);
                        $spinner.hide();
                        $btnText.show();
                    }
                },
                error: function () {
                    showMessage($form, 'error', 'An error occurred. Please try again.');
                    $btn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.show();
                }
            });
        });

        // Register form submit
        $modal.on('submit', '#hkdev-register-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.hkdev-auth-submit');
            var $spinner = $btn.find('.hkdev-auth-spinner');
            var $btnText = $btn.find('.hkdev-auth-btn-text');

            var password = $form.find('input[name="password"]').val();
            var password2 = $form.find('input[name="password2"]').val();
            if (password !== password2) {
                showMessage($form, 'error', 'Passwords do not match.');
                return;
            }

            $btn.prop('disabled', true);
            $spinner.show();
            $btnText.hide();

            $.ajax({
                url: hkdevElementsAjax.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showMessage($form, 'success', response.data.message);
                        setTimeout(function () {
                            window.location.href = response.data.redirect || window.location.href;
                        }, 1000);
                    } else {
                        showMessage($form, 'error', response.data.message);
                        $btn.prop('disabled', false);
                        $spinner.hide();
                        $btnText.show();
                    }
                },
                error: function () {
                    showMessage($form, 'error', 'An error occurred. Please try again.');
                    $btn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.show();
                }
            });
        });

        // Reset form submit
        $modal.on('submit', '#hkdev-reset-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.hkdev-auth-submit');
            var $spinner = $btn.find('.hkdev-auth-spinner');
            var $btnText = $btn.find('.hkdev-auth-btn-text');

            $btn.prop('disabled', true);
            $spinner.show();
            $btnText.hide();

            $.ajax({
                url: hkdevElementsAjax.ajax_url,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showMessage($form, 'success', response.data.message);
                    } else {
                        showMessage($form, 'error', response.data.message);
                    }
                    $btn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.show();
                },
                error: function () {
                    showMessage($form, 'error', 'An error occurred. Please try again.');
                    $btn.prop('disabled', false);
                    $spinner.hide();
                    $btnText.show();
                }
            });
        });
    });
})(jQuery);