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

if ( ! class_exists( __NAMESPACE__ . '\\Header_Widget' ) ) {
	require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/header-widget.php';
}

/**
 * Dedicated Header Bottom (navigation) widget.
 */
class Header_Bottom_Widget extends Header_Widget {

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

if ( ! class_exists( __NAMESPACE__ . '\\HeaderBottomWidget' ) ) {
	class HeaderBottomWidget extends Header_Bottom_Widget {}
}
