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

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * Class Section_Heading_Widget
 */
class Section_Heading_Widget extends Widget_Base {

	use Heading_Controls;
	use Style_Controls;

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

		$this->register_sh_extra_style_controls();
	}

	/**
	 * Extra Style controls for the block itself.
	 *
	 * @return void
	 */
	protected function register_sh_extra_style_controls() {
		$this->start_controls_section(
			'sh_style_block',
			[
				'label' => esc_html__( 'Block', 'hkdev-shop-elements' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->hkdev_dimensions( 'sh_block_margin', esc_html__( 'Block Margin', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-sh-heading', 'margin' );
		$this->hkdev_dimensions( 'sh_block_padding', esc_html__( 'Block Padding', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-sh-heading', 'padding' );
		$this->hkdev_color( 'sh_block_bg', esc_html__( 'Block Background', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-sh-heading', 'background-color' );
		$this->hkdev_shadow( 'sh_block_shadow', esc_html__( 'Block Box Shadow', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-sh-heading' );
		$this->hkdev_slider( 'sh_accent_w', esc_html__( 'Accent Bar Width', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-sh-heading .hkdev-sh-heading-accent', 'width', 0, 14 );
		$this->hkdev_slider( 'sh_accent_h', esc_html__( 'Accent Bar Height', 'hkdev-shop-elements' ), '{{WRAPPER}} .hkdev-sh-heading .hkdev-sh-heading-accent', 'height', 6, 60 );

		$this->end_controls_section();
	}

	/**
	 * Render the widget output.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		echo \HkdevShopElements\Includes\Core\ShopEngine::instance()->shop_heading_html( $this->get_heading_config( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
