<?php
/**
 * HKDEV Wishlist Widget (HKDEV Shop Elements plugin).
 *
 * Drop it on a "Wishlist" page: it lists every saved product using the same
 * product card as the Shop Grid, with a heart button on each card to unsave it.
 *
 * @package HkdevShopElements
 */

namespace HkdevShopElements\Includes\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use HkdevShopElements\Includes\Wishlist_Engine;

/**
 * Class Wishlist_Widget
 */
class Wishlist_Widget extends Widget_Base {

	use Style_Controls;

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'hkdev_wishlist';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'HKDEV Wishlist', 'hkdev-shop-elements' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-heart';
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
		return [ 'wishlist', 'favourite', 'favorite', 'saved', 'heart', 'woocommerce' ];
	}

	/**
	 * Style handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return [ 'hkdev-elements-wishlist-style', 'hkdev-elements-shop-style', 'hkdev-elements-checkout-style', 'hkdev-elements-fontawesome' ];
	}

	/**
	 * Script handles this widget depends on.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return [ 'hkdev-elements-wishlist-js', 'hkdev-elements-shop-js' ];
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Content', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => esc_html__( 'Heading', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'My Wishlist', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'subtitle',
			[
				'label'   => esc_html__( 'Subtitle', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);

		$this->add_control(
			'show_count',
			[
				'label'        => esc_html__( 'Show Item Count', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'add_all',
			[
				'label'        => esc_html__( 'Add All to Cart Button', 'hkdev-shop-elements' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Adds every saved product to the cart in one click. Variable products are skipped because they need options.', 'hkdev-shop-elements' ),
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => esc_html__( 'Columns', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
			]
		);

		$this->add_control(
			'empty_text',
			[
				'label'     => esc_html__( 'Empty Message', 'hkdev-shop-elements' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Your wishlist is empty.', 'hkdev-shop-elements' ),
				'separator' => 'before',
			]
		);

		$this->add_control(
			'empty_btn',
			[
				'label'   => esc_html__( 'Empty Button Text', 'hkdev-shop-elements' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Continue Shopping', 'hkdev-shop-elements' ),
			]
		);

		$this->end_controls_section();

		// Same card controls as the Shop Grid widget.
		$this->register_style_sections( '{{WRAPPER}} .hkdev-wishlist' );
	}

	/**
	 * Render the widget.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! class_exists( Wishlist_Engine::class ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		echo Wishlist_Engine::instance()->wishlist_shortcode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			[
				'columns'    => isset( $settings['columns'] ) ? $settings['columns'] : '4',
				'title'      => isset( $settings['title'] ) ? $settings['title'] : '',
				'subtitle'   => isset( $settings['subtitle'] ) ? $settings['subtitle'] : '',
				'show_count' => ( isset( $settings['show_count'] ) && 'yes' === $settings['show_count'] ) ? 'yes' : 'no',
				'add_all'    => ( ! isset( $settings['add_all'] ) || 'yes' === $settings['add_all'] ) ? 'yes' : 'no',
				'empty_text' => isset( $settings['empty_text'] ) ? $settings['empty_text'] : '',
				'empty_btn'  => isset( $settings['empty_btn'] ) ? $settings['empty_btn'] : '',
			]
		);
	}
}
