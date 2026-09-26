<?php
/**
 * HKDEV Shop Carousel Widget
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dedicated Shop Carousel Elementor widget.
 */
class Shop_Carousel_Widget extends Shop_Widget {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_shop_carousel';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Shop Carousel', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'shop', 'carousel', 'slider', 'products', 'product slider', 'product carousel', 'woocommerce' ];
	}

	/**
	 * Force shortcode layout style to carousel.
	 *
	 * @return string
	 */
	protected function get_widget_layout_style() {
		return 'carousel';
	}
}

if ( ! class_exists( __NAMESPACE__ . '\\ShopCarouselWidget' ) ) {
	class ShopCarouselWidget extends Shop_Carousel_Widget {}
}
