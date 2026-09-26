<?php
/**
 * HKDEV Header Upper Widget
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
 * Dedicated Header Upper (top bar) widget.
 */
class HeaderUpperWidget extends HeaderWidget {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_header_upper';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Header Upper', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-site-identity';
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'header', 'topbar', 'upper', 'announcement', 'social' ];
	}

	/**
	 * Render only the upper header row.
	 *
	 * @return string
	 */
	protected function get_header_part() {
		return 'upper';
	}
}

