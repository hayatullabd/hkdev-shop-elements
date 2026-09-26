/**
 * Apply Color Theme tokens on the widget wrapper and copy chrome themes to <html>.
 */
(function () {
	'use strict';

	function themes() {
		return window.hkdevColorThemes || {};
	}

	function applyVars(el, slug) {
		if (!el || !slug) {
			return;
		}
		var map = themes()[slug];
		el.setAttribute('data-card-theme', slug);
		if (!map) {
			return;
		}
		Object.keys(map).forEach(function (prop) {
			el.style.setProperty(prop, map[prop]);
		});
	}

	function applyDocumentTheme(slug) {
		if (!slug) {
			return;
		}
		applyVars(document.documentElement, slug);
		if (document.body) {
			document.body.setAttribute('data-card-theme', slug);
		}
	}

	function syncRootFromWidget() {
		var root = document.querySelector('[data-hkdev-theme-root][data-card-theme]');
		if (!root) {
			return;
		}
		applyDocumentTheme(root.getAttribute('data-card-theme'));
	}

	function isChromeWidget(type, el) {
		if (el && el.getAttribute && el.getAttribute('data-hkdev-theme-root')) {
			return true;
		}
		return !!(type && /hkdev_header|hkdev_footer|hkdev_checkout|hkdev_cart|hkdev_account/.test(type));
	}

	function bindEditor() {
		if (!window.elementor || !elementor.channels || !elementor.channels.editor) {
			return false;
		}
		elementor.channels.editor.on('change', function (controlView, elementView) {
			if (!controlView || !controlView.model) {
				return;
			}
			var name = controlView.model.get('name');
			if (name !== 'card_theme' && name !== 'hkdev_color_theme') {
				return;
			}
			var slug = typeof controlView.getControlValue === 'function' ? controlView.getControlValue() : '';
			if (!slug || !elementView || !elementView.$el || !elementView.$el[0]) {
				return;
			}
			applyVars(elementView.$el[0], slug);
			var type = elementView.model ? elementView.model.get('widgetType') : '';
			if (isChromeWidget(type, elementView.$el[0])) {
				applyDocumentTheme(slug);
			}
		});
		return true;
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', syncRootFromWidget);
	} else {
		syncRootFromWidget();
	}

	if (!bindEditor()) {
		window.addEventListener('elementor/frontend/init', bindEditor);
		if (window.jQuery) {
			window.jQuery(window).on('elementor:init', bindEditor);
		}
	}
})();
