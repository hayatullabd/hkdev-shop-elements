<?php
/**
 * HKDEV Header Main Widget
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
 * Dedicated Header Main (logo / search / cart) widget.
 */
class HeaderMainWidget extends HeaderWidget {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_header_main';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Header Main', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-header';
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'header', 'main', 'logo', 'search', 'cart', 'account' ];
	}

	/**
	 * Render only the main header row.
	 *
	 * @return string
	 */
	protected function get_header_part() {
		return 'main';
	}
}

