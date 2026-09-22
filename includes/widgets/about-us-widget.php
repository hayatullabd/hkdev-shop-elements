<?php
/**
 * About Us page-ready widget.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class About_Us_Widget extends Policy_Link_Base_Widget {

	public function get_name() {
		return 'hkdev_about_us_link';
	}

	public function get_title() {
		return esc_html__( 'HKDEV About Us', 'hkdev-shop-elements' );
	}

	public function get_icon() {
		return 'eicon-site-identity';
	}

	public function get_keywords() {
		return [ 'about us', 'about', 'story', 'page' ];
	}

	protected function default_page_title() {
		return esc_html__( 'About Us', 'hkdev-shop-elements' );
	}

	protected function default_page_content() {
		return wp_kses_post(
			'<p>We started with a simple mission: make trusted products easily accessible with honest service and fair pricing.</p><p>Our team works every day to ensure quality, fast response, and a smooth shopping experience from order to delivery.</p><p>Customer trust is our strongest asset. We continuously improve our platform, support, and logistics based on real customer feedback.</p><p>Thank you for choosing us as your shopping partner.</p>'
		);
	}
}

