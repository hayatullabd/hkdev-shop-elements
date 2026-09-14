<?php
/**
 * HKDEV Section Heading Widget (HKDEV Shop Elements plugin).
 *
 * A compact "Category Name — View All" strip: optional accent bar + heading
 * (+ optional subtitle) on the left, "View All" link pinned to the right,
 * optionally wrapped in a light bordered box.
 *
 * Controls come from the shared Heading_Controls trait and the markup is
 * rendered by Shop_Engine so this widget, the Shop Grid and the Trending
 * widget all look and behave identically.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;

/**
 * Class Section_Heading_Widget
 */
class Section_Heading_Widget extends Widget_Base {

	use Heading_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_section_heading';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Section Heading', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-heading';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'hkdev-shop-elements' ];
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'heading', 'title', 'section', 'view all', 'category', 'best selling' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-shop-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_heading_controls();

		// Friendlier defaults for the standalone widget.
		$this->update_control( 'heading_text', [ 'default' => esc_html__( 'Category Name', 'hkdev-shop-elements' ) ] );
		$this->update_control( 'heading_link_text', [ 'default' => esc_html__( 'View All', 'hkdev-shop-elements' ) ] );
	}

	/**
	 * Render the widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		echo \HkdevShopElements\Includes\Shop_Engine::instance()->shop_heading_html( $this->get_heading_config( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
