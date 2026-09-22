/**
 * HKDEV Hero Slider — fade or slide transitions with arrows, dots, autoplay
 * progress bar, touch swipe, hover pause and keyboard navigation.
 *
 * Config mirrors the Elementor Slides widget: transition, infinite loop,
 * pause on hover, pause on interaction and the navigation mode are all read
 * from data attributes. Every instance owns its own state, so several sliders
 * can sit on one page.
 */
(function ($) {
    'use strict';

    function initHero($root) {
        // Elementor can hand us the same slider more than once (it fires both
        // the global and the widget-scoped ready hooks), so never bind twice.
        if ($root.data('hkdevHeroInit')) {
            return;
        }
        $root.data('hkdevHeroInit', true);

        const $slides = $root.find('.hkdev-hero-slide');
        const $track = $root.find('.hkdev-hero-slides');
        const count = $slides.length;

        if (!count) {
            return;
        }

        const el = $root.get(0);
        const $dotsWrap = $root.find('.hkdev-hero-dots');
        const $progress = $root.find('.hkdev-hero-progress');

        const transition = $root.attr('data-transition') === 'slide' ? 'slide' : 'fade';
        const autoplay = String($root.attr('data-autoplay')) === '1' && count > 1;
        const delay = Math.max(1000, parseInt($root.attr('data-delay'), 10) || 5000);
        const loop = String($root.attr('data-loop')) === '1';
        const pauseHover = String($root.attr('data-pause-hover')) === '1';
        const pauseInteraction = String($root.attr('data-pause-interaction')) === '1';

        let current = 0;
        let timer = null;
        let rafId = null;
        let progressWidth = 0;
        let lastTime = 0;
        let hovered = false;
        let stoppedByInteraction = false;

        /* ---------------- Dots ---------------- */
        let $dots = $();

        if ($dotsWrap.length && count > 1) {
            for (let s = 0; s < count; s++) {
                $('<button>', {
                    type: 'button',
                    class: 'hkdev-hero-dot',
                    'data-slide': s,
                    'aria-label': 'Go to slide ' + (s + 1)
                }).appendTo($dotsWrap);
            }
            $dots = $dotsWrap.children('.hkdev-hero-dot');
            $dotsWrap.on('click', '.hkdev-hero-dot', function () {
                var i = parseInt($(this).attr('data-slide'), 10);
                if (!isNaN(i)) {
                    goTo(i);
                    userAction();
                }
            });
        }

        /* ---------------- Rendering ---------------- */
        function render() {
            $slides.each(function (index) {
                const active = index === current;
                $(this).toggleClass('is-active', active);

                // Slide mode keeps every slide on screen while the track moves,
                // so take the off-screen ones out of the tab / a11y order.
                if (transition === 'slide') {
                    if (active) {
                        this.removeAttribute('inert');
                    } else {
                        this.setAttribute('inert', '');
                    }
                }
            });

            $dots.each(function (i) {
                var dist = Math.abs(i - current);
                $(this)
                    .toggleClass('is-active', i === current)
                    .toggleClass('is-near', dist === 1)
                    .toggleClass('is-far', dist === 2)
                    .toggleClass('is-off', count > 7 && dist > 3);
            }

            if (transition === 'slide' && $track.length) {
                $track.css('transform', 'translateX(-' + (current * 100) + '%)');
            }

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
            if (current >= count - 1) {
                if (!loop) {
                    return;
                }
                current = 0;
            } else {
                current += 1;
            }

            render();
        }

        function prev() {
            if (current <= 0) {
                if (!loop) {
                    return;
                }
                current = count - 1;
            } else {
                current -= 1;
            }

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

        function tick() {
            // Elementor replaces the widget markup on every settings change;
            // when that happens this instance is detached, so it has to retire
            // rather than keep an interval running against dead nodes.
            if (!el.isConnected) {
                stop();
                return;
            }

            if (!loop && current >= count - 1) {
                stop();
                return;
            }

            next();
        }

        function start() {
            stop();

            if (!autoplay || hovered || stoppedByInteraction) {
                return;
            }

            timer = setInterval(tick, delay);
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

        // Any manual navigation either restarts autoplay or, when Pause on
        // Interaction is on, ends it for this page view.
        function userAction() {
            if (pauseInteraction) {
                stoppedByInteraction = true;
                stop();
                return;
            }

            restart();
        }

        /* ---------------- Arrows ---------------- */
        $root.on('click', '.hkdev-hero-prev', function () {
            prev();
            userAction();
        });

        $root.on('click', '.hkdev-hero-next', function () {
            next();
            userAction();
        });

        /* ---------------- Pause on hover ---------------- */
        if (pauseHover) {
            $root.on('mouseenter', function () {
                hovered = true;
                stop();
            });

            $root.on('mouseleave', function () {
                hovered = false;
                start();
            });
        }

        /* ---------------- Touch swipe ---------------- */
        let touchStartX = 0;

        // Bound natively so the listeners can be passive.
        el.addEventListener('touchstart', function (e) {
            touchStartX = e.changedTouches[0].screenX;
            hovered = true;
            stop();
        }, { passive: true });

        el.addEventListener('touchend', function (e) {
            const endX = e.changedTouches[0].screenX;
            const threshold = 50;
            let swiped = false;

            if (endX < touchStartX - threshold) {
                next();
                swiped = true;
            } else if (endX > touchStartX + threshold) {
                prev();
                swiped = true;
            }

            hovered = false;

            if (swiped && pauseInteraction) {
                stoppedByInteraction = true;
                return;
            }

            start();
        }, { passive: true });

        /* ---------------- Keyboard (while focused) ---------------- */
        $root.on('keydown', function (e) {
            if (e.key === 'ArrowLeft') {
                prev();
                userAction();
            } else if (e.key === 'ArrowRight') {
                next();
                userAction();
            }
        });

        render();
        start();
    }

    function initSliders($scope) {
        const $roots = $scope
            ? ($scope.hasClass('hkdev-hero') ? $scope : $scope.find('.hkdev-hero'))
            : $('.hkdev-hero');

        $roots.each(function () {
            initHero($(this));
        });
    }

    // Elementor renders - and re-renders - widgets long after DOM ready, so
    // without this hook a slider dropped in or edited in the editor would never
    // start: it would only work on the published page.
    function hookElementor() {
        if (!window.elementorFrontend || !elementorFrontend.hooks) {
            return false;
        }

        elementorFrontend.hooks.addAction('frontend/element_ready/global', function ($scope) {
            initSliders($scope);
        });

        return true;
    }

    $(function () {
        initSliders();

        // If this file loads after Elementor has already fired its init event,
        // the listener below would never run - so hook straight away when the
        // frontend object is already up.
        if (!hookElementor()) {
            $(window).on('elementor/frontend/init', hookElementor);
        }
    });
}(jQuery));
