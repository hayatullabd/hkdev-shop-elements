<?php
/**
 * HKDEV Category Grid Widget
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\\Category_Carousel_Widget' ) ) {
	require_once HKDEV_ELEMENTS_PATH . 'includes/widgets/category-carousel-widget.php';
}

/**
 * Dedicated category grid widget.
 */
class Category_Grid_Widget extends Category_Carousel_Widget {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_category_grid';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Category Grid', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'category', 'categories', 'grid', 'product cat', 'responsive' ];
	}

	/**
	 * Force category widget layout style to grid.
	 *
	 * @return string
	 */
	protected function get_widget_layout_style() {
		return 'grid';
	}
}

if ( ! class_exists( __NAMESPACE__ . '\\CategoryGridWidget' ) ) {
	class CategoryGridWidget extends Category_Grid_Widget {}
}
