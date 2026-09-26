<?php
/**
 * HKDEV Header Bottom Widget
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\\HeaderWidget' ) ) {
	require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/header-widget.php';
}

/**
 * Dedicated Header Bottom (navigation) widget.
 */
class HeaderBottomWidget extends HeaderWidget {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_header_bottom';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Header Bottom', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-nav-menu';
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'header', 'bottom', 'menu', 'nav', 'navigation', 'navbar' ];
	}

	/**
	 * Render only the bottom navigation row.
	 *
	 * @return string
	 */
	protected function get_header_part() {
		return 'bottom';
	}
}

