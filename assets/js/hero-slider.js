/**
 * HKDEV Hero Slider — fade slides with arrows, dots, autoplay progress bar,
 * touch swipe, hover pause and keyboard navigation.
 *
 * Every instance owns its own state, so several sliders can sit on one page.
 */
(function ($) {
    'use strict';

    function initHero($root) {
        const $slides = $root.find('.hkdev-hero-slide');
        const count = $slides.length;

        if (!count) {
            return;
        }

        const $dotsWrap = $root.find('.hkdev-hero-dots');
        const $progress = $root.find('.hkdev-hero-progress');
        const autoplay = String($root.attr('data-autoplay')) === '1' && count > 1;
        const delay = Math.max(1000, parseInt($root.attr('data-delay'), 10) || 5000);

        let current = 0;
        let timer = null;
        let rafId = null;
        let progressWidth = 0;
        let lastTime = 0;
        let paused = false;

        /* ---------------- Dots ---------------- */
        let $dots = $();

        if ($dotsWrap.length) {
            for (let i = 0; i < count; i++) {
                $('<button>', {
                    type: 'button',
                    class: 'hkdev-hero-dot',
                    'aria-label': 'Go to slide ' + (i + 1)
                })
                    .on('click', function () {
                        goTo(i);
                        restart();
                    })
                    .appendTo($dotsWrap);
            }

            $dots = $dotsWrap.children('.hkdev-hero-dot');
        }

        /* ---------------- Rendering ---------------- */
        function render() {
            $slides.each(function (index) {
                $(this).toggleClass('is-active', index === current);
            });

            $dots.each(function (index) {
                $(this).toggleClass('is-active', index === current);
            });

            progressWidth = 0;

            if ($progress.length) {
                $progress.css('width', '0%');
            }
        }

        function goTo(index) {
            current = index;
            render();
        }

        function next() {
            current = (current + 1) % count;
            render();
        }

        function prev() {
            current = (current - 1 + count) % count;
            render();
        }

        /* ---------------- Autoplay + progress ---------------- */
        function startProgress() {
            if (!$progress.length || !autoplay) {
                return;
            }

            progressWidth = 0;
            lastTime = performance.now();

            function step(now) {
                progressWidth += ((now - lastTime) / delay) * 100;
                lastTime = now;

                if (progressWidth <= 100) {
                    $progress.css('width', progressWidth + '%');
                    rafId = requestAnimationFrame(step);
                }
            }

            rafId = requestAnimationFrame(step);
        }

        function stopProgress() {
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
        }

        function start() {
            stop();

            if (!autoplay || paused) {
                return;
            }

            timer = setInterval(next, delay);
            startProgress();
        }

        function stop() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
            stopProgress();
        }

        function restart() {
            stop();
            start();
        }

        /* ---------------- Arrows ---------------- */
        $root.on('click', '.hkdev-hero-prev', function () {
            prev();
            restart();
        });

        $root.on('click', '.hkdev-hero-next', function () {
            next();
            restart();
        });

        /* ---------------- Pause on hover ---------------- */
        $root.on('mouseenter', function () {
            paused = true;
            stop();
        });

        $root.on('mouseleave', function () {
            paused = false;
            start();
        });

        /* ---------------- Touch swipe ---------------- */
        const el = $root.get(0);
        let touchStartX = 0;

        // Bound natively so the listener can be passive.
        el.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
            paused = true;
            stop();
        }, { passive: true });

        el.addEventListener('touchend', function (e) {
            const endX = e.changedTouches[0].screenX;
            const threshold = 50;

            if (endX < touchStartX - threshold) {
                next();
            } else if (endX > touchStartX + threshold) {
                prev();
            }

            paused = false;
            start();
        }, { passive: true });

        /* ---------------- Keyboard (while focused) ---------------- */
        $root.on('keydown', function (e) {
            if (e.key === 'ArrowLeft') {
                prev();
                restart();
            } else if (e.key === 'ArrowRight') {
                next();
                restart();
            }
        });

        render();
        start();
    }

    $(function () {
        $('.hkdev-hero').each(function () {
            initHero($(this));
        });
    });
}(jQuery));
